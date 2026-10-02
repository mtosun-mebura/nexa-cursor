<?php

namespace App\Services\Payout;

use App\Models\Company;
use App\Models\PayoutIdentity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PayoutIdentityService
{
    /** @var list<string> */
    public const FORBIDDEN_SECRET_FIELDS = [
        'card_number',
        'pan',
        'cvc',
        'cvv',
        'iban',
        'account_number',
        'bic',
        'api_key',
        'secret',
        'access_token',
        'refresh_token',
    ];

    public function __construct(
        private readonly PayoutIdentityAuditLogger $audit,
        private readonly MollieConnectPayoutClient $mollieClient,
    ) {}

    public function ownerKeyForCompany(Company $company, string $provider = PayoutIdentity::PROVIDER_MOLLIE): string
    {
        return 'company:'.$company->id.':'.$provider;
    }

    public function ownerKeyForIndependentDriver(User $user, string $provider = PayoutIdentity::PROVIDER_MOLLIE): string
    {
        return 'user:'.$user->id.':'.$provider;
    }

    public function forCompany(Company $company, string $provider = PayoutIdentity::PROVIDER_MOLLIE): ?PayoutIdentity
    {
        return PayoutIdentity::query()
            ->where('owner_key', $this->ownerKeyForCompany($company, $provider))
            ->first();
    }

    /**
     * Create the empty company payout shell (no bank data yet). Safe to call repeatedly.
     */
    public function bootstrapCompanyIdentity(Company $company, ?User $actor = null, ?Request $request = null): PayoutIdentity
    {
        $provider = PayoutIdentity::PROVIDER_MOLLIE;
        $ownerKey = $this->ownerKeyForCompany($company, $provider);

        $identity = PayoutIdentity::query()->firstOrCreate(
            ['owner_key' => $ownerKey],
            [
                'company_id' => $company->id,
                'user_id' => null,
                'settlement_party' => PayoutIdentity::PARTY_COMPANY,
                'provider' => $provider,
                'capability_status' => PayoutIdentity::STATUS_NOT_STARTED,
            ]
        );

        if ($identity->wasRecentlyCreated) {
            $this->audit->log($identity, 'company_identity_bootstrapped', [
                'capability_status' => $identity->capability_status,
            ], $actor, $request);
        }

        return $identity;
    }

    /**
     * Accept a full IBAN from the app/admin, persist ONLY a masked label (*** + last 4).
     * The raw IBAN is never stored.
     */
    public function setCompanyBankAccount(
        Company $company,
        User $actor,
        string $iban,
        ?string $password = null,
        ?Request $request = null
    ): PayoutIdentity {
        if (! is_valid_iban($iban)) {
            throw ValidationException::withMessages([
                'iban' => ['Voer een geldig IBAN in.'],
            ]);
        }

        $normalized = normalize_iban($iban);
        $masked = $this->maskIbanLastFour($normalized);
        $identity = $this->bootstrapCompanyIdentity($company, $actor, $request);

        // First time: set immediately (still pending provider verification).
        if (! filled($identity->masked_destination)) {
            $identity->forceFill([
                'masked_destination' => $masked,
                'capability_status' => PayoutIdentity::STATUS_PENDING,
            ])->save();

            $this->audit->log($identity, 'company_bank_account_set', [
                'masked_destination' => $masked,
                'first_set' => true,
            ], $actor, $request);

            return $identity->fresh();
        }

        if ($identity->masked_destination === $masked) {
            throw ValidationException::withMessages([
                'iban' => ['Dit is al de geregistreerde rekening (zelfde eindcijfers).'],
            ]);
        }

        // Change: step-up (wachtwoord of e-mailcode) + cooling-off.
        $this->assertStepUp(
            $actor,
            (string) ($password ?? ''),
            $request,
            is_string($request?->input('confirmation_code'))
                ? (string) $request->input('confirmation_code')
                : null
        );

        $hours = max(1, (int) config('nexa_payout.destination_change_cooling_off_hours', 48));
        $eligibleAt = now()->addHours($hours);

        $identity->forceFill([
            'pending_masked_destination' => $masked,
            'pending_provider_account_id' => $identity->provider_account_id ?: 'bank-pending',
            'destination_change_requested_at' => now(),
            'destination_change_eligible_at' => $eligibleAt,
        ])->save();

        $this->audit->log($identity, 'company_bank_account_change_requested', [
            'masked_destination' => $masked,
            'eligible_at' => $eligibleAt->toIso8601String(),
            'cooling_off_hours' => $hours,
        ], $actor, $request);

        return $identity->fresh();
    }

    /**
     * Display mask: *** + last 4 digits of the IBAN. Never returns the full account.
     */
    public function maskIbanLastFour(string $iban): string
    {
        $normalized = normalize_iban($iban);
        $digits = preg_replace('/\D+/', '', $normalized) ?? '';
        $last4 = substr($digits, -4);

        if (strlen($last4) < 4) {
            throw ValidationException::withMessages([
                'iban' => ['IBAN bevat te weinig cijfers om te maskeren.'],
            ]);
        }

        return '***'.$last4;
    }

    public function companyCanReceiveSettlement(Company $company): bool
    {
        $identity = $this->forCompany($company);

        return $identity !== null && $identity->isEnabled();
    }

    /**
     * Start or resume company payout onboarding (settlement party = company).
     *
     * @param  array{provider_organization_id?: ?string, provider_account_id?: ?string, masked_destination?: ?string}  $attrs
     */
    public function ensureCompanyIdentity(Company $company, User $actor, array $attrs = [], ?Request $request = null): PayoutIdentity
    {
        $this->rejectSecretFields($attrs);

        $provider = PayoutIdentity::PROVIDER_MOLLIE;
        $ownerKey = $this->ownerKeyForCompany($company, $provider);

        $identity = PayoutIdentity::query()->firstOrCreate(
            ['owner_key' => $ownerKey],
            [
                'company_id' => $company->id,
                'user_id' => null,
                'settlement_party' => PayoutIdentity::PARTY_COMPANY,
                'provider' => $provider,
                'capability_status' => PayoutIdentity::STATUS_NOT_STARTED,
            ]
        );

        $updates = [];
        if (! empty($attrs['provider_organization_id'])) {
            $updates['provider_organization_id'] = substr(trim((string) $attrs['provider_organization_id']), 0, 128);
        }
        if (! empty($attrs['provider_account_id'])) {
            $updates['provider_account_id'] = substr(trim((string) $attrs['provider_account_id']), 0, 128);
        }
        if (array_key_exists('masked_destination', $attrs) && $attrs['masked_destination'] !== null) {
            $updates['masked_destination'] = $this->sanitizeMaskedDestination((string) $attrs['masked_destination']);
        }
        if ($identity->capability_status === PayoutIdentity::STATUS_NOT_STARTED) {
            $updates['capability_status'] = PayoutIdentity::STATUS_PENDING;
        }

        if ($updates !== []) {
            $identity->fill($updates);
            $identity->save();
        }

        $this->audit->log($identity, 'company_identity_ensured', [
            'capability_status' => $identity->capability_status,
            'has_provider_account_id' => filled($identity->provider_account_id),
        ], $actor, $request);

        return $identity->fresh();
    }

    /**
     * Independent-driver payouts are disabled until legal/PSP validation flips the config flag.
     *
     * @param  array<string, mixed>  $attrs
     */
    public function ensureIndependentDriverIdentity(User $driver, Company $company, User $actor, array $attrs = [], ?Request $request = null): PayoutIdentity
    {
        if (! config('nexa_payout.allow_independent_driver_payouts')) {
            $this->audit->log(null, 'independent_driver_payout_blocked', [
                'company_id' => $company->id,
                'driver_user_id' => $driver->id,
                'reason' => 'allow_independent_driver_payouts=false',
            ], $actor, $request);

            throw ValidationException::withMessages([
                'settlement_party' => [
                    'Directe uitbetaling aan individuele chauffeurs is niet beschikbaar. Settlement loopt via het taxibedrijf (rechtspersoon).',
                ],
            ]);
        }

        $this->rejectSecretFields($attrs);

        throw ValidationException::withMessages([
            'settlement_party' => [
                'Independent-driver payout onboarding is geconfigureerd maar nog niet geïmplementeerd voor productie.',
            ],
        ]);
    }

    public function syncFromProvider(PayoutIdentity $identity, User $actor, ?Request $request = null): PayoutIdentity
    {
        $client = $this->clientFor($identity);
        $remote = $client->fetchAccountStatus($identity);

        $identity->forceFill([
            'provider_account_id' => $remote['provider_account_id'] ?? $identity->provider_account_id,
            'provider_organization_id' => $remote['provider_organization_id'] ?? $identity->provider_organization_id,
            'capability_status' => $remote['capability_status'],
            'masked_destination' => $remote['masked_destination'] ?? $identity->masked_destination,
            'provider_meta' => $this->stripSecretsFromMeta($remote['meta'] ?? []),
            'last_synced_at' => now(),
            'verified_at' => ($remote['capability_status'] ?? null) === PayoutIdentity::STATUS_ENABLED
                ? ($identity->verified_at ?? now())
                : $identity->verified_at,
            'disabled_at' => ($remote['capability_status'] ?? null) === PayoutIdentity::STATUS_DISABLED
                ? ($identity->disabled_at ?? now())
                : null,
        ])->save();

        $this->audit->log($identity, 'synced_from_provider', [
            'capability_status' => $identity->capability_status,
        ], $actor, $request);

        return $identity->fresh();
    }

    /**
     * Mark a destination change as pending (cooling-off). Does not apply immediately.
     *
     * @param  array{provider_account_id: string, masked_destination?: ?string, password: string}  $input
     */
    public function requestDestinationChange(PayoutIdentity $identity, User $actor, array $input, ?Request $request = null): PayoutIdentity
    {
        $this->rejectSecretFields($input);
        $this->assertStepUp(
            $actor,
            (string) ($input['password'] ?? ''),
            $request,
            isset($input['confirmation_code']) ? (string) $input['confirmation_code'] : null
        );

        $newAccountId = trim((string) ($input['provider_account_id'] ?? ''));
        if ($newAccountId === '') {
            throw ValidationException::withMessages([
                'provider_account_id' => ['Nieuwe provider-account-id is verplicht.'],
            ]);
        }

        if ($newAccountId === (string) $identity->provider_account_id) {
            throw ValidationException::withMessages([
                'provider_account_id' => ['Dit is al de actieve bestemming.'],
            ]);
        }

        $hours = max(1, (int) config('nexa_payout.destination_change_cooling_off_hours', 48));
        $eligibleAt = now()->addHours($hours);

        $identity->forceFill([
            'pending_provider_account_id' => substr($newAccountId, 0, 128),
            'pending_masked_destination' => isset($input['masked_destination'])
                ? $this->sanitizeMaskedDestination((string) $input['masked_destination'])
                : null,
            'destination_change_requested_at' => now(),
            'destination_change_eligible_at' => $eligibleAt,
        ])->save();

        $this->audit->log($identity, 'destination_change_requested', [
            'eligible_at' => $eligibleAt->toIso8601String(),
            'cooling_off_hours' => $hours,
            'pending_masked_destination' => $identity->pending_masked_destination,
        ], $actor, $request);

        return $identity->fresh();
    }

    public function applyPendingDestinationChange(PayoutIdentity $identity, User $actor, ?Request $request = null): PayoutIdentity
    {
        if (! $identity->hasPendingDestinationChange()) {
            throw ValidationException::withMessages([
                'destination' => ['Er is geen openstaande bestemmingswijziging.'],
            ]);
        }

        if ($identity->destinationChangeIsCooling()) {
            throw ValidationException::withMessages([
                'destination' => [
                    'Cooling-off loopt nog tot '.$identity->destination_change_eligible_at->toIso8601String().'.',
                ],
            ]);
        }

        $previous = $identity->provider_account_id;
        $newAccountId = $identity->pending_provider_account_id;
        if ($newAccountId === 'bank-pending') {
            $newAccountId = $identity->provider_account_id;
        }

        $identity->forceFill([
            'provider_account_id' => $newAccountId,
            'masked_destination' => $identity->pending_masked_destination ?? $identity->masked_destination,
            'pending_provider_account_id' => null,
            'pending_masked_destination' => null,
            'destination_change_requested_at' => null,
            'destination_change_eligible_at' => null,
            'capability_status' => PayoutIdentity::STATUS_PENDING,
            'verified_at' => null,
        ])->save();

        $this->audit->log($identity, 'destination_change_applied', [
            'had_previous_account' => filled($previous),
        ], $actor, $request);

        return $this->syncFromProvider($identity->fresh(), $actor, $request);
    }

    public function disable(PayoutIdentity $identity, User $actor, string $password, ?Request $request = null, ?string $confirmationCode = null): PayoutIdentity
    {
        $this->assertStepUp($actor, $password, $request, $confirmationCode);

        $identity->forceFill([
            'capability_status' => PayoutIdentity::STATUS_DISABLED,
            'disabled_at' => now(),
            'pending_provider_account_id' => null,
            'pending_masked_destination' => null,
            'destination_change_requested_at' => null,
            'destination_change_eligible_at' => null,
        ])->save();

        $this->audit->log($identity, 'disabled', [], $actor, $request);

        return $identity->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    public function publicPayload(PayoutIdentity $identity): array
    {
        return [
            'id' => $identity->id,
            'company_id' => $identity->company_id,
            'settlement_party' => $identity->settlement_party,
            'provider' => $identity->provider,
            'capability_status' => $identity->capability_status,
            'masked_destination' => $identity->masked_destination,
            'verified_at' => $identity->verified_at?->toIso8601String(),
            'disabled_at' => $identity->disabled_at?->toIso8601String(),
            'has_provider_account' => filled($identity->provider_account_id),
            'pending_destination_change' => $identity->hasPendingDestinationChange(),
            'destination_change_eligible_at' => $identity->destination_change_eligible_at?->toIso8601String(),
            'destination_change_cooling' => $identity->destinationChangeIsCooling(),
            'last_synced_at' => $identity->last_synced_at?->toIso8601String(),
            'can_receive_settlement' => $identity->isEnabled(),
        ];
    }

    private function clientFor(PayoutIdentity $identity): PayoutProviderClient
    {
        return match ($identity->provider) {
            PayoutIdentity::PROVIDER_MOLLIE => $this->mollieClient,
            default => $this->mollieClient,
        };
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function rejectSecretFields(array $input): void
    {
        foreach (self::FORBIDDEN_SECRET_FIELDS as $field) {
            if (array_key_exists($field, $input) && $input[$field] !== null && $input[$field] !== '') {
                throw ValidationException::withMessages([
                    $field => ['Het opslaan van betaal- of bankgegevens is niet toegestaan. Gebruik provider-hosted onboarding.'],
                ]);
            }
        }
    }

    /**
     * Step-up: wachtwoord óf eenmalige e-mailcode (voor wie met code inlogt zonder wachtwoord).
     */
    private function assertStepUp(
        User $actor,
        string $password,
        ?Request $request,
        ?string $confirmationCode = null
    ): void {
        $password = trim($password);
        $code = preg_replace('/\s+/', '', (string) ($confirmationCode ?? '')) ?? '';

        $passwordOk = $password !== '' && Hash::check($password, (string) $actor->password);
        if ($passwordOk) {
            // ok
        } elseif ($code !== '') {
            if (! app(\App\Services\AdminFirstLoginService::class)->consumeStepUpCode($actor, $code)) {
                throw ValidationException::withMessages([
                    'confirmation_code' => ['Deze bevestigingscode is onjuist of verlopen. Vraag een nieuwe code aan.'],
                ]);
            }
            $passwordOk = true;
        }

        if (! $passwordOk) {
            throw ValidationException::withMessages([
                'password' => ['Bevestig met je wachtwoord, of vraag een eenmalige e-mailcode aan.'],
                'confirmation_code' => ['Bevestig met je wachtwoord, of vraag een eenmalige e-mailcode aan.'],
            ]);
        }

        $maxAge = max(60, (int) config('nexa_payout.step_up_password_max_age_seconds', 300));
        if ($request?->hasSession()) {
            $request->session()->put('auth.password_confirmed_at', time());
            $request->session()->put('payout.step_up_confirmed_at', time());
            $request->session()->put('payout.step_up_max_age', $maxAge);
        }
    }

    private function sanitizeMaskedDestination(string $value): string
    {
        $value = trim($value);
        $digitCount = preg_match_all('/\d/', $value) ?: 0;
        // Reject values that look like a full IBAN/PAN rather than a masked label.
        if ($digitCount >= 12 || preg_match('/\d{12,}/', $value)) {
            throw ValidationException::withMessages([
                'masked_destination' => ['Gebruik alleen een gemaskeerde weergave van de provider, geen volledige rekening- of kaartnummers.'],
            ]);
        }

        return substr($value, 0, 120);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    private function stripSecretsFromMeta(array $meta): array
    {
        foreach (self::FORBIDDEN_SECRET_FIELDS as $field) {
            unset($meta[$field]);
        }

        return $meta;
    }
}
