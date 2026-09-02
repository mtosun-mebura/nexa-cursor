<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CustomerLoginCode;
use App\Models\TenantCustomerEmail;
use App\Models\User;
use App\Support\NexaBranding;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class AdminFirstLoginService
{
    public const CODE_LENGTH = 6;

    public const EXPIRES_MINUTES = 15;

    public const COOLDOWN_SECONDS = 60;

    public const ACTIVATION_REQUIRED_MESSAGE = 'Dit account is nog niet geactiveerd. Vraag een eenmalige code aan om het account te activeren.';

    public function __construct(
        protected AdminFirstLoginCodeEmailTemplateService $codeTemplates,
        protected EmailTemplateService $parser,
        protected CompanyEmailLogoService $logos,
        protected TenantCustomerMailService $customerMail,
    ) {}

    public function needsFirstLogin(User $user): bool
    {
        return Schema::hasColumn('users', 'password_must_be_set') && (bool) $user->password_must_be_set;
    }

    public function unusablePasswordHash(): string
    {
        return Hash::make(Str::password(48));
    }

    /**
     * @return array{must_change_password?: bool, password_must_be_set?: bool, email_verified_at?: \Carbon\CarbonInterface}
     */
    public function provisionFlags(): array
    {
        $flags = [];
        if (Schema::hasColumn('users', 'must_change_password')) {
            $flags['must_change_password'] = true;
        }
        if (Schema::hasColumn('users', 'password_must_be_set')) {
            $flags['password_must_be_set'] = true;
        }
        if (Schema::hasColumn('users', 'email_verified_at')) {
            $flags['email_verified_at'] = now();
        }

        return $flags;
    }

    /**
     * @return array{ok: bool, status: int, message: string, retry_after?: int}
     */
    public function requestCode(string $email, string $ip): array
    {
        $email = strtolower(trim($email));
        $ipKey = 'admin-first-login-ip:'.$ip;
        $emailKey = 'admin-first-login-email:'.$email;

        if (RateLimiter::tooManyAttempts($ipKey, 8)) {
            return $this->tooMany($ipKey);
        }
        if (RateLimiter::tooManyAttempts($emailKey, 5)) {
            return $this->tooMany($emailKey);
        }

        RateLimiter::hit($ipKey, 60);
        RateLimiter::hit($emailKey, 3600);

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        $this->burnDummyHash();

        if ($user) {
            $this->applyPermissionsTeam($user);
        }

        if (! $user || ! $user->canAccessAdminPanel()) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Dit e-mailadres is niet bekend voor de admin.',
            ];
        }

        if (Schema::hasColumn('users', 'is_active') && $user->is_active === false) {
            return [
                'ok' => false,
                'status' => 403,
                'message' => 'Dit account is uitgeschakeld.',
            ];
        }

        if (! $this->needsFirstLogin($user)) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Dit account is al geactiveerd. Log in met uw wachtwoord.',
            ];
        }

        $recent = CustomerLoginCode::query()
            ->where('user_id', $user->id)
            ->where('purpose', CustomerLoginCode::PURPOSE_ADMIN)
            ->whereNull('consumed_at')
            ->where('created_at', '>=', now()->subSeconds(self::COOLDOWN_SECONDS))
            ->latest('id')
            ->first();

        if ($recent) {
            $elapsed = max(0, (int) $recent->created_at->diffInSeconds(now()));
            $wait = max(1, self::COOLDOWN_SECONDS - $elapsed);

            return [
                'ok' => false,
                'status' => 429,
                'message' => 'Er is zojuist een code verstuurd. Kijk in uw inbox of vraag over '.$wait.' seconde(n) een nieuwe aan.',
                'retry_after' => $wait,
            ];
        }

        $this->invalidateOpenCodes($user);

        $code = str_pad((string) random_int(0, (10 ** self::CODE_LENGTH) - 1), self::CODE_LENGTH, '0', STR_PAD_LEFT);

        CustomerLoginCode::query()->create([
            'user_id' => $user->id,
            'purpose' => CustomerLoginCode::PURPOSE_ADMIN,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::EXPIRES_MINUTES),
        ]);

        if (! $this->sendCodeMail($user, $code)) {
            return [
                'ok' => false,
                'status' => 503,
                'message' => 'De code kon nu niet per e-mail worden verstuurd. Probeer het zo opnieuw.',
            ];
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'We hebben een eenmalige code gestuurd naar '.$this->maskEmail($email).'. Die is '.self::EXPIRES_MINUTES.' minuten geldig.',
        ];
    }

    /**
     * @return array{ok: bool, status: int, message: string, code?: string, user?: User}
     */
    public function verifyAndSetPassword(string $email, string $code, string $password, string $ip): array
    {
        $email = strtolower(trim($email));
        $code = preg_replace('/\s+/', '', $code) ?? '';

        $attemptKey = 'admin-first-login-verify:'.$email.':'.$ip;
        if (RateLimiter::tooManyAttempts($attemptKey, 8)) {
            return $this->tooMany($attemptKey);
        }
        RateLimiter::hit($attemptKey, 900);

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if ($user) {
            $this->applyPermissionsTeam($user);
        }
        if (! $user || ! $user->canAccessAdminPanel() || ! $this->needsFirstLogin($user)) {
            $this->burnDummyHash();

            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Code of e-mailadres is onjuist.',
            ];
        }

        if (strlen($code) !== self::CODE_LENGTH || ! ctype_digit($code)) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Vul de '.self::CODE_LENGTH.'-cijferige code uit uw e-mail in.',
            ];
        }

        $openCodes = CustomerLoginCode::query()
            ->where('user_id', $user->id)
            ->where('purpose', CustomerLoginCode::PURPOSE_ADMIN)
            ->whereNull('consumed_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $validCodes = $openCodes->filter(fn (CustomerLoginCode $record) => $record->expires_at && $record->expires_at->isFuture());
        if ($validCodes->isEmpty()) {
            $hadExpired = $openCodes->contains(fn (CustomerLoginCode $record) => $record->expires_at && $record->expires_at->isPast());

            return [
                'ok' => false,
                'status' => 410,
                'code' => 'code_expired',
                'message' => $hadExpired
                    ? 'Je inlogcode is verlopen. Vraag een nieuwe code aan.'
                    : 'Er is geen geldige code meer. Vraag een nieuwe code aan.',
            ];
        }

        $match = null;
        foreach ($validCodes as $record) {
            if (Hash::check($code, $record->code_hash)) {
                $match = $record;
                break;
            }
        }

        if (! $match) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Deze code is onjuist. Controleer de code uit je e-mail of vraag een nieuwe aan.',
            ];
        }

        if (! preg_match(TenantOnboardingService::PASSWORD_REGEX, $password) || strlen($password) < 8) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Kies een wachtwoord van minimaal 8 tekens, met een hoofdletter, een kleine letter en een cijfer.',
            ];
        }

        $match->update(['consumed_at' => now()]);
        $this->invalidateOpenCodes($user);

        $payload = [
            'password' => Hash::make($password),
            'must_change_password' => false,
            'password_must_be_set' => false,
            'welcome_handleiding_pending' => true,
        ];
        if (Schema::hasColumn('users', 'email_verified_at') && ! $user->email_verified_at) {
            $payload['email_verified_at'] = now();
        }

        $user->forceFill($payload)->save();
        RateLimiter::clear($attemptKey);

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Wachtwoord opgeslagen. U bent ingelogd.',
            'user' => $user->fresh(),
        ];
    }

    private function sendCodeMail(User $user, string $code): bool
    {
        $companyId = (int) ($user->company_id ?? 0);
        $company = $companyId > 0 ? Company::query()->find($companyId) : null;
        $companyName = $company?->name ?: 'NEXA Suite';
        $toName = trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: (string) $user->email;

        $variables = array_merge(
            [
                'USER_NAME' => $toName,
                'USER_EMAIL' => (string) $user->email,
                'COMPANY_NAME' => e($companyName),
                'LOGIN_CODE' => $code,
                'ADMIN_LOGIN_URL' => TenantWelcomeEmailTemplateService::ADMIN_LOGIN_URL,
                'CODE_EXPIRES_MINUTES' => (string) self::EXPIRES_MINUTES,
            ],
            $this->logos->templateVariable($companyId > 0 ? $companyId : null, $companyName),
            NexaBranding::emailLogoTemplateVariable()
        );

        $template = $this->codeTemplates->resolveActive();
        $subject = $this->parser->parseTemplateVariables((string) $template->subject, $variables);
        $html = $this->parser->parseTemplateVariables((string) ($template->html_content ?? ''), $variables);
        $text = $template->text_content
            ? $this->parser->parseTemplateVariables((string) $template->text_content, $variables)
            : trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        $record = $this->customerMail->send([
            'company_id' => $companyId > 0 ? $companyId : null,
            'type' => TenantCustomerEmail::TYPE_LOGIN_CODE,
            'to_email' => $user->email,
            'to_name' => $toName,
            'subject' => $subject !== '' ? $subject : 'Je inlogcode voor NEXA Suite',
            'html' => $html,
            'text' => $text,
            'related_type' => 'user',
            'related_id' => $user->id,
            'meta' => ['kind' => 'admin_first_login_code'],
            'throw' => false,
        ]);

        return $record && $record->status === TenantCustomerEmail::STATUS_SENT;
    }

    public static function codeMailType(): string
    {
        return AdminFirstLoginCodeEmailTemplateService::TYPE;
    }

    private function applyPermissionsTeam(User $user): void
    {
        $teamId = $user->company_id ? (int) $user->company_id : null;
        app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');
    }

    /**
     * @return array{ok: false, status: int, message: string, retry_after: int}
     */
    private function tooMany(string $key): array
    {
        $seconds = RateLimiter::availableIn($key);

        return [
            'ok' => false,
            'status' => 429,
            'message' => 'Te veel pogingen. Wacht '.$seconds.' seconde(n) en probeer het opnieuw.',
            'retry_after' => $seconds,
        ];
    }

    private function invalidateOpenCodes(User $user): void
    {
        CustomerLoginCode::query()
            ->where('user_id', $user->id)
            ->where('purpose', CustomerLoginCode::PURPOSE_ADMIN)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email, 2);
        if (count($parts) !== 2) {
            return $email;
        }
        $name = $parts[0];
        $visible = max(1, (int) floor(strlen($name) / 3));

        return substr($name, 0, $visible).str_repeat('*', max(1, strlen($name) - $visible)).'@'.$parts[1];
    }

    private function burnDummyHash(): void
    {
        Hash::check('probe', Hash::make('probe'));
    }
}
