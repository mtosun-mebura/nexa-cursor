<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\Company;
use App\Models\NexaSuiteMarketplaceSetting;
use App\Models\TaxiNetworkInviteCode;
use App\Models\TaxiNetworkPartnership;
use App\Models\User;
use App\Services\MarketplaceCompanyRegistrationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class TaxiNetworkPartnershipService
{
    /** Maandpakketten die als network-owner (ritten uitzetten) mogen koppelen. */
    public const PAID_OWNER_PACKAGE_KEYS = ['start', 'pro', 'business'];

    public function __construct(
        protected TaxiDispatchSettingsService $dispatchSettings
    ) {}

    /**
     * Owner (eigen ritten via network uitzetten) vereist een betaald maandabonnement.
     * Fee-only marketplace mag wél partner zijn (invite delen / accepteren), niet zelf partners koppelen.
     */
    public function companyCanInitiateNetworkPartnerships(Company|int|null $company): bool
    {
        if ($company === null) {
            return false;
        }
        if (! $company instanceof Company) {
            $company = Company::query()->find((int) $company);
        }
        if (! $company || ! $company->is_active) {
            return false;
        }

        $key = strtolower(trim((string) ($company->package_key ?? '')));
        // Fee-only marketplace: alleen als super-admin “marketplace mag network-owner zijn” aanzet.
        if ($key === strtolower(MarketplaceCompanyRegistrationService::PACKAGE_KEY)) {
            return NexaSuiteMarketplaceSetting::marketplaceMayOwnNetwork();
        }
        // Geen pakket gekoppeld: legacy tenants blijven werken tot er een pakket is.
        if ($key === '') {
            return true;
        }

        return in_array($key, self::PAID_OWNER_PACKAGE_KEYS, true);
    }

    public function assertCompanyCanInitiateNetworkPartnerships(Company|int $company): void
    {
        if ($this->companyCanInitiateNetworkPartnerships($company)) {
            return;
        }

        throw new InvalidArgumentException(
            'Alleen tenants met een betaald maandabonnement (Start, Pro of Business) mogen network-partners koppelen. '
            .'Marketplace (alleen fee) kan wel als uitvoerder gekoppeld worden; over die ritten geldt de NEXA-fee.'
        );
    }

    /**
     * Active invite code for a company (creates one if missing/expired).
     */
    public function ensureActiveInviteCode(int $companyId, ?User $actor = null, bool $forceRotate = false): TaxiNetworkInviteCode
    {
        if ($companyId <= 0) {
            throw new InvalidArgumentException('Ongeldig bedrijf.');
        }

        $existing = TaxiNetworkInviteCode::query()
            ->where('company_id', $companyId)
            ->whereNull('revoked_at')
            ->orderByDesc('id')
            ->first();

        if ($existing && ! $forceRotate && $existing->isUsable()) {
            return $existing;
        }

        if ($existing && $forceRotate) {
            $existing->forceFill(['revoked_at' => now()])->save();
        }

        return TaxiNetworkInviteCode::query()->create([
            'company_id' => $companyId,
            'code' => $this->generateUniqueCode(),
            'expires_at' => now()->addDays(30),
            'max_uses' => null,
            'use_count' => 0,
            'auto_accept' => false,
            'created_by_user_id' => $actor?->id,
        ]);
    }

    public function rotateInviteCode(int $companyId, ?User $actor = null): TaxiNetworkInviteCode
    {
        return $this->ensureActiveInviteCode($companyId, $actor, true);
    }

    public function setAutoAccept(int $companyId, bool $autoAccept): TaxiNetworkInviteCode
    {
        $code = $this->ensureActiveInviteCode($companyId);
        $code->forceFill(['auto_accept' => $autoAccept])->save();

        return $code->fresh();
    }

    /**
     * Owner redeems partner's invite code → pending or accepted link.
     *
     * @return array{partnership: TaxiNetworkPartnership, auto_accepted: bool}
     */
    public function redeemInviteCode(int $ownerCompanyId, string $rawCode, ?User $actor = null): array
    {
        $code = $this->normalizeCode($rawCode);
        if ($code === '') {
            throw new InvalidArgumentException('Vul een invite-code in.');
        }

        $invite = TaxiNetworkInviteCode::query()
            ->where('code', $code)
            ->first();

        if (! $invite || ! $invite->isUsable()) {
            throw new InvalidArgumentException('Deze invite-code is ongeldig of verlopen.');
        }

        $partnerCompanyId = (int) $invite->company_id;
        if ($partnerCompanyId === $ownerCompanyId) {
            throw new InvalidArgumentException('Je kunt je eigen invite-code niet gebruiken.');
        }

        $this->assertCompanyCanInitiateNetworkPartnerships($ownerCompanyId);

        if (! Company::query()->whereKey($partnerCompanyId)->where('is_active', true)->exists()) {
            throw new InvalidArgumentException('Dit partnerbedrijf is niet beschikbaar.');
        }

        // Marketplace↔marketplace of marketplace-as-owner is al geblokkeerd via assert hierboven.
        // Partner mag marketplace (fee-only) of elk ander actief bedrijf zijn.

        return DB::transaction(function () use ($ownerCompanyId, $partnerCompanyId, $invite, $actor) {
            $existing = TaxiNetworkPartnership::query()
                ->where('owner_company_id', $ownerCompanyId)
                ->where('partner_company_id', $partnerCompanyId)
                ->lockForUpdate()
                ->first();

            if ($existing?->status === TaxiNetworkPartnership::STATUS_ACCEPTED) {
                throw new InvalidArgumentException('Deze partner is al gekoppeld.');
            }

            $autoAccept = (bool) $invite->auto_accept;
            $now = now();

            $attrs = [
                'status' => $autoAccept
                    ? TaxiNetworkPartnership::STATUS_ACCEPTED
                    : TaxiNetworkPartnership::STATUS_PENDING,
                'invite_code_id' => $invite->id,
                'requested_by_company_id' => $ownerCompanyId,
                'acted_by_user_id' => $autoAccept ? $actor?->id : null,
                'accepted_at' => $autoAccept ? $now : null,
                'declined_at' => null,
                'revoked_at' => null,
            ];

            if ($existing) {
                $existing->fill($attrs)->save();
                $partnership = $existing;
            } else {
                $partnership = TaxiNetworkPartnership::query()->create(array_merge([
                    'owner_company_id' => $ownerCompanyId,
                    'partner_company_id' => $partnerCompanyId,
                ], $attrs));
            }

            $invite->increment('use_count');

            if ($autoAccept) {
                $this->syncOwnerPartnerIds($ownerCompanyId);
            } else {
                try {
                    app(TaxiNetworkPartnershipMailer::class)->sendPartnerRequest($partnership);
                } catch (Throwable $e) {
                    report($e);
                }
            }

            return [
                'partnership' => $partnership->fresh(['partnerCompany', 'ownerCompany']),
                'auto_accepted' => $autoAccept,
            ];
        });
    }

    public function acceptPartnership(TaxiNetworkPartnership $partnership, int $actingCompanyId, ?User $actor = null): TaxiNetworkPartnership
    {
        if ((int) $partnership->partner_company_id !== $actingCompanyId) {
            throw new RuntimeException('Alleen de uitgenodigde partner mag dit verzoek accepteren.');
        }

        if (! $partnership->isPending()) {
            throw new InvalidArgumentException('Dit verzoek is niet meer openstaand.');
        }

        $partnership->forceFill([
            'status' => TaxiNetworkPartnership::STATUS_ACCEPTED,
            'accepted_at' => now(),
            'declined_at' => null,
            'revoked_at' => null,
            'acted_by_user_id' => $actor?->id,
        ])->save();

        $this->syncOwnerPartnerIds((int) $partnership->owner_company_id);

        return $partnership->fresh(['ownerCompany', 'partnerCompany']);
    }

    public function declinePartnership(TaxiNetworkPartnership $partnership, int $actingCompanyId, ?User $actor = null): TaxiNetworkPartnership
    {
        if ((int) $partnership->partner_company_id !== $actingCompanyId) {
            throw new RuntimeException('Alleen de uitgenodigde partner mag dit verzoek afwijzen.');
        }

        if (! $partnership->isPending()) {
            throw new InvalidArgumentException('Dit verzoek is niet meer openstaand.');
        }

        $partnership->forceFill([
            'status' => TaxiNetworkPartnership::STATUS_DECLINED,
            'declined_at' => now(),
            'acted_by_user_id' => $actor?->id,
        ])->save();

        return $partnership->fresh();
    }

    public function revokePartnership(TaxiNetworkPartnership $partnership, int $actingCompanyId, ?User $actor = null): TaxiNetworkPartnership
    {
        $ownerId = (int) $partnership->owner_company_id;
        $partnerId = (int) $partnership->partner_company_id;

        if ($actingCompanyId !== $ownerId && $actingCompanyId !== $partnerId) {
            throw new RuntimeException('Geen toegang tot deze koppeling.');
        }

        if (! in_array($partnership->status, [
            TaxiNetworkPartnership::STATUS_ACCEPTED,
            TaxiNetworkPartnership::STATUS_PENDING,
        ], true)) {
            throw new InvalidArgumentException('Deze koppeling kan niet meer worden ingetrokken.');
        }

        $partnership->forceFill([
            'status' => TaxiNetworkPartnership::STATUS_REVOKED,
            'revoked_at' => now(),
            'acted_by_user_id' => $actor?->id,
        ])->save();

        $this->syncOwnerPartnerIds($ownerId);

        return $partnership->fresh();
    }

    /**
     * Partner IDs from accepted partnerships (privacy-safe source for dispatch).
     *
     * @return list<int>
     */
    public function acceptedPartnerIdsForOwner(int $ownerCompanyId): array
    {
        return TaxiNetworkPartnership::query()
            ->where('owner_company_id', $ownerCompanyId)
            ->where('status', TaxiNetworkPartnership::STATUS_ACCEPTED)
            ->orderBy('partner_company_id')
            ->pluck('partner_company_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0 && $id !== $ownerCompanyId)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Merge invite-based partners + optional super-admin manual IDs into dispatch settings.
     */
    public function syncOwnerPartnerIds(int $ownerCompanyId): void
    {
        $fromPartnerships = $this->acceptedPartnerIdsForOwner($ownerCompanyId);
        $manual = $this->dispatchSettings->networkManualPartnerCompanyIds($ownerCompanyId);
        $merged = array_values(array_unique(array_merge($fromPartnerships, $manual)));

        $this->dispatchSettings->setNetworkPartnerCompanyIds($merged, $ownerCompanyId);
    }

    /**
     * @return Collection<int, TaxiNetworkPartnership>
     */
    public function pendingIncomingForPartner(int $partnerCompanyId): Collection
    {
        return TaxiNetworkPartnership::query()
            ->with('ownerCompany:id,name')
            ->where('partner_company_id', $partnerCompanyId)
            ->where('status', TaxiNetworkPartnership::STATUS_PENDING)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return Collection<int, TaxiNetworkPartnership>
     */
    public function acceptedAsOwner(int $ownerCompanyId): Collection
    {
        return TaxiNetworkPartnership::query()
            ->with('partnerCompany:id,name,is_active')
            ->where('owner_company_id', $ownerCompanyId)
            ->where('status', TaxiNetworkPartnership::STATUS_ACCEPTED)
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, TaxiNetworkPartnership>
     */
    public function pendingOutgoingAsOwner(int $ownerCompanyId): Collection
    {
        return TaxiNetworkPartnership::query()
            ->with('partnerCompany:id,name')
            ->where('owner_company_id', $ownerCompanyId)
            ->where('status', TaxiNetworkPartnership::STATUS_PENDING)
            ->orderByDesc('id')
            ->get();
    }

    private function generateUniqueCode(): string
    {
        for ($i = 0; $i < 12; $i++) {
            $code = strtoupper(Str::random(8));
            $code = preg_replace('/[^A-Z0-9]/', '', $code) ?? '';
            $code = substr(str_replace(['O', '0', 'I', '1', 'L'], ['A', '2', 'B', '3', 'C'], $code), 0, 8);
            if (strlen($code) < 8) {
                $code = strtoupper(substr(bin2hex(random_bytes(6)), 0, 8));
            }
            if (! TaxiNetworkInviteCode::query()->where('code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException('Kon geen unieke invite-code genereren.');
    }

    private function normalizeCode(string $raw): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($raw)) ?? '');
    }
}
