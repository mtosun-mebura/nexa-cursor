<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\Company;
use App\Models\CustomerLoginCode;
use App\Models\TenantCustomerEmail;
use App\Models\User;
use App\Services\CompanyEmailLogoService;
use App\Services\EmailTemplateService;
use App\Services\EnvService;
use App\Services\TenantCustomerMailService;
use App\Services\TenantOnboardingService;
use App\Support\NexaBranding;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TaxiAppFirstLoginService
{
    public const CHANNEL_DRIVER = 'driver';

    public const CHANNEL_CONTRACT = 'contract';

    public const ROLE_CHAUFFEUR = 'chauffeur';

    public const ROLE_CONTRACTANT = 'contractant';

    public const ROLE_CONTRACTOUDER = 'contractouder';

    public const CODE_LENGTH = 6;

    public const FIRST_LOGIN_REQUIRED_MESSAGE = 'Er is nog geen wachtwoord ingesteld. Vraag een inlogcode aan om zelf een wachtwoord te kiezen.';

    public function __construct(
        protected TaxiDriverEligibilityService $drivers,
        protected TaxiContractPortalAccessService $contractAccess,
        protected TaxiAppLoginCodeEmailTemplateService $codeTemplates,
        protected EmailTemplateService $parser,
        protected CompanyEmailLogoService $logos,
        protected EnvService $env,
        protected TenantCustomerMailService $customerMail,
    ) {}

    /**
     * @param  list<string>  $roleNames
     */
    public function welcomeRoleForRoles(array $roleNames): ?string
    {
        if ($this->drivers->rolesIncludeChauffeur($roleNames)) {
            return self::ROLE_CHAUFFEUR;
        }

        $normalized = array_map(static fn ($name) => strtolower(trim((string) $name)), $roleNames);
        if (in_array(self::ROLE_CONTRACTANT, $normalized, true)) {
            return self::ROLE_CONTRACTANT;
        }
        if (in_array(self::ROLE_CONTRACTOUDER, $normalized, true)) {
            return self::ROLE_CONTRACTOUDER;
        }

        return null;
    }

    public function channelForRole(?string $role): ?string
    {
        return match ($role) {
            self::ROLE_CHAUFFEUR => self::CHANNEL_DRIVER,
            self::ROLE_CONTRACTANT, self::ROLE_CONTRACTOUDER => self::CHANNEL_CONTRACT,
            default => null,
        };
    }

    public function welcomeTemplateType(string $role): ?string
    {
        return match ($role) {
            self::ROLE_CHAUFFEUR => TaxiAppUserWelcomeEmailTemplateService::TYPE_CHAUFFEUR,
            self::ROLE_CONTRACTANT => TaxiAppUserWelcomeEmailTemplateService::TYPE_CONTRACTANT,
            self::ROLE_CONTRACTOUDER => TaxiAppUserWelcomeEmailTemplateService::TYPE_CONTRACTOUDER,
            default => null,
        };
    }

    public function purposeForChannel(string $channel): string
    {
        return $channel === self::CHANNEL_CONTRACT
            ? CustomerLoginCode::PURPOSE_CONTRACT
            : CustomerLoginCode::PURPOSE_DRIVER;
    }

    public function loginUrlForChannel(string $channel): string
    {
        return $channel === self::CHANNEL_CONTRACT
            ? url('/taxi/contract')
            : url('/taxi/chauffeur');
    }

    public function appNameForChannel(string $channel): string
    {
        return $channel === self::CHANNEL_CONTRACT
            ? 'het contractportaal'
            : 'de chauffeur-app';
    }

    public function needsFirstLogin(User $user): bool
    {
        if (Schema::hasColumn('users', 'must_change_password') && $user->must_change_password) {
            return true;
        }

        return Schema::hasColumn('users', 'password_must_be_set') && (bool) $user->password_must_be_set;
    }

    /**
     * @return list<string>
     */
    public static function appRoleNames(): array
    {
        return array_values(array_unique(array_merge(
            TaxiDriverEligibilityService::CHAUFFEUR_ROLE_NAMES,
            [self::ROLE_CONTRACTANT, self::ROLE_CONTRACTOUDER]
        )));
    }

    public function userMayUseChannel(User $user, string $channel): bool
    {
        $companyId = (int) ($user->company_id ?? 0);
        if ($companyId <= 0) {
            return false;
        }

        if ($channel === self::CHANNEL_DRIVER) {
            return $this->drivers->isChauffeurForCompany($user, $companyId)
                || $this->drivers->rolesIncludeChauffeur($user->webRoleNames());
        }

        if ($channel === self::CHANNEL_CONTRACT) {
            return $this->contractAccess->userMayAccessPortal($user);
        }

        return false;
    }

    /**
     * @return array{ok: bool, status: int, message: string, retry_after?: int}
     */
    public function requestCode(string $email, string $channel, string $ip): array
    {
        $email = strtolower(trim($email));
        $channel = $this->normalizeChannel($channel);

        $ipKey = 'taxi-app-code-ip:'.$ip;
        $emailKey = 'taxi-app-code-email:'.$channel.':'.$email;

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

        if (! $user) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Dit e-mailadres is niet bekend.',
            ];
        }

        if (Schema::hasColumn('users', 'is_active') && $user->is_active === false) {
            return [
                'ok' => false,
                'status' => 403,
                'message' => 'Dit account is uitgeschakeld. Neem contact op met je werkgever.',
            ];
        }

        if (! $this->userMayUseChannel($user, $channel)) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => $channel === self::CHANNEL_CONTRACT
                    ? 'Dit e-mailadres heeft geen toegang tot het contractportaal.'
                    : 'Dit e-mailadres heeft geen toegang tot de chauffeur-app.',
            ];
        }

        if (! $this->needsFirstLogin($user)) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Dit account is al geactiveerd. Log in met je wachtwoord.',
            ];
        }

        $purpose = $this->purposeForChannel($channel);
        $cooldown = max(30, (int) config('taxi-dispatch.app_first_login_code_cooldown_seconds', 60));
        $recent = CustomerLoginCode::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->where('created_at', '>=', now()->subSeconds($cooldown))
            ->latest('id')
            ->first();

        if ($recent) {
            $elapsed = max(0, (int) $recent->created_at->diffInSeconds(now()));
            $wait = max(1, $cooldown - $elapsed);

            return [
                'ok' => false,
                'status' => 429,
                'message' => 'Er is zojuist een code verstuurd. Kijk in je inbox of vraag over '.$wait.' seconde(n) een nieuwe aan.',
                'retry_after' => $wait,
            ];
        }

        $this->invalidateOpenCodes($user, $purpose);

        $expiresMinutes = max(5, (int) config('taxi-dispatch.app_first_login_code_expires_minutes', 15));
        $code = str_pad((string) random_int(0, (10 ** self::CODE_LENGTH) - 1), self::CODE_LENGTH, '0', STR_PAD_LEFT);

        CustomerLoginCode::query()->create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($expiresMinutes),
        ]);

        $mailed = $this->sendCodeMail($user, $channel, $code, $expiresMinutes);
        if (! $mailed) {
            return [
                'ok' => false,
                'status' => 503,
                'message' => 'De code kon nu niet per e-mail worden verstuurd. Probeer het zo opnieuw of neem contact op met je werkgever.',
            ];
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'We hebben een eenmalige code gestuurd naar '.$this->maskEmail($email).'. Die is '.$expiresMinutes.' minuten geldig.',
        ];
    }

    /**
     * @return array{ok: bool, status: int, message: string, user?: User}
     */
    public function verifyAndSetPassword(string $email, string $code, string $password, string $channel, string $ip): array
    {
        $email = strtolower(trim($email));
        $channel = $this->normalizeChannel($channel);
        $code = preg_replace('/\s+/', '', $code) ?? '';

        $attemptKey = 'taxi-app-code-verify:'.$channel.':'.$email.':'.$ip;
        if (RateLimiter::tooManyAttempts($attemptKey, 8)) {
            return $this->tooMany($attemptKey);
        }
        RateLimiter::hit($attemptKey, 900);

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if (! $user || ! $this->userMayUseChannel($user, $channel) || ! $this->needsFirstLogin($user)) {
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
                'message' => 'Vul de '.self::CODE_LENGTH.'-cijferige code uit je e-mail in.',
            ];
        }

        $purpose = $this->purposeForChannel($channel);
        $records = CustomerLoginCode::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $match = null;
        foreach ($records as $record) {
            if (Hash::check($code, $record->code_hash)) {
                $match = $record;
                break;
            }
        }

        if (! $match) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Deze code is onjuist of niet meer geldig. Vraag een nieuwe code aan.',
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
        $this->invalidateOpenCodes($user, $purpose);

        $payload = [
            'password' => Hash::make($password),
        ];
        if (Schema::hasColumn('users', 'must_change_password')) {
            $payload['must_change_password'] = false;
        }
        if (Schema::hasColumn('users', 'password_must_be_set')) {
            $payload['password_must_be_set'] = false;
        }
        if (Schema::hasColumn('users', 'email_verified_at') && ! $user->email_verified_at) {
            $payload['email_verified_at'] = now();
        }

        $user->forceFill($payload)->save();
        RateLimiter::clear($attemptKey);

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Wachtwoord opgeslagen. Je bent ingelogd.',
            'user' => $user->fresh(),
        ];
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

    public function unusablePasswordHash(): string
    {
        return Hash::make(Str::password(48));
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

    private function invalidateOpenCodes(User $user, string $purpose): void
    {
        CustomerLoginCode::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);
    }

    private function sendCodeMail(User $user, string $channel, string $code, int $expiresMinutes): bool
    {
        $companyId = (int) ($user->company_id ?? 0);
        $company = $companyId > 0 ? Company::query()->find($companyId) : null;
        $companyName = $company?->name ?: 'NEXA Taxi';
        $loginUrl = $this->loginUrlForChannel($channel);
        $appName = $this->appNameForChannel($channel);

        $variables = array_merge(
            [
                'USER_NAME' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: (string) $user->email,
                'USER_EMAIL' => (string) $user->email,
                'COMPANY_NAME' => e($companyName),
                'APP_NAME' => $appName,
                'LOGIN_CODE' => $code,
                'LOGIN_URL' => $loginUrl,
                'CODE_EXPIRES_MINUTES' => (string) $expiresMinutes,
            ],
            $this->logos->templateVariable($companyId > 0 ? $companyId : null, $companyName),
            NexaBranding::emailLogoTemplateVariable()
        );

        $template = $this->codeTemplates->resolveActiveTemplate($companyId > 0 ? $companyId : null);
        if ($template) {
            $subject = $this->parser->parseTemplateVariables($template->subject, $variables);
            $html = $this->parser->parseTemplateVariables($template->html_content, $variables);
            $text = $template->text_content
                ? $this->parser->parseTemplateVariables($template->text_content, $variables)
                : strip_tags($html);
        } else {
            $defaults = $this->codeTemplates->defaultPayload(null);
            $subject = $this->parser->parseTemplateVariables((string) $defaults['subject'], $variables);
            $html = $this->parser->parseTemplateVariables($this->codeTemplates->defaultHtmlContent(), $variables);
            $text = $this->parser->parseTemplateVariables($this->codeTemplates->defaultTextContent(), $variables);
        }

        $payload = [
            'company_id' => $companyId > 0 ? $companyId : null,
            'type' => TenantCustomerEmail::TYPE_LOGIN_CODE,
            'to_email' => (string) $user->email,
            'to_name' => $variables['USER_NAME'],
            'subject' => $subject,
            'html' => $html,
            'text' => $text,
            'related_type' => 'user',
            'related_id' => $user->id,
            'meta' => ['channel' => $channel],
        ];

        $deliverable = $this->env->isMailDeliverableToInbox($companyId > 0 ? $companyId : null)
            || app()->environment('testing');

        if (! $deliverable) {
            Log::warning('App-inlogcode niet per e-mail verstuurd: geen SMTP.', [
                'user_id' => $user->id,
                'channel' => $channel,
            ]);
            if (app()->environment('local')) {
                Log::info('DEV: app first-login code', [
                    'email' => $user->email,
                    'login_code' => $code,
                    'channel' => $channel,
                ]);
            }
            $this->customerMail->record($payload, TenantCustomerEmail::STATUS_FAILED, 'Geen bruikbare SMTP-configuratie voor deze tenant.');

            return app()->environment('local');
        }

        $record = $this->customerMail->send($payload);

        return $record->status === TenantCustomerEmail::STATUS_SENT;
    }

    private function normalizeChannel(string $channel): string
    {
        return $channel === self::CHANNEL_CONTRACT
            ? self::CHANNEL_CONTRACT
            : self::CHANNEL_DRIVER;
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email, 2);
        if (count($parts) !== 2) {
            return $email;
        }
        $name = $parts[0];
        $visible = mb_substr($name, 0, 1);

        return $visible.'***@'.$parts[1];
    }

    private function burnDummyHash(): void
    {
        Hash::check('password', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
    }
}
