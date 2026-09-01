<?php

namespace App\Services;

use App\Models\Company;
use App\Models\TenantCustomerEmail;
use App\Models\User;
use App\Services\PlatformBilling\TenantSubscriptionService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class TenantOnboardingService
{
    public const PASSWORD_REGEX = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/';

    public function __construct(
        protected EmailTemplateService $templates,
        protected TenantWelcomeEmailTemplateService $welcomeTemplate,
        protected UserRoleAssignmentService $roles,
        protected NexaPricingService $pricing,
        protected TenantSubscriptionService $subscriptions,
        protected CompanyEmailLogoService $logos,
        protected TenantCustomerMailService $customerMail,
    ) {}

    /**
     * @return array{user: User, password: string|null, created: bool, mailed: bool}
     */
    public function provisionCompanyAdmin(Company $company, bool $sendWelcomeMail = true): array
    {
        $email = strtolower(trim((string) $company->email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Het bedrijf heeft geen geldig e-mailadres voor de company-admin.');
        }

        $existing = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if ($existing && (int) $existing->company_id !== (int) $company->id) {
            throw new RuntimeException('Dit e-mailadres is al in gebruik bij een andere gebruiker.');
        }

        if ($existing) {
            $this->roles->syncWebRoles($existing, ['company-admin']);
            $this->subscriptions->ensureProfile($company);

            return [
                'user' => $existing,
                'password' => null,
                'created' => false,
                'mailed' => false,
            ];
        }

        $password = $this->generateTemporaryPassword();
        $user = User::query()->create([
            'first_name' => trim((string) ($company->contact_first_name ?: 'Beheerder')) ?: 'Beheerder',
            'last_name' => trim((string) ($company->contact_last_name ?: $company->name)) ?: 'Admin',
            'email' => $email,
            'phone' => $company->phone,
            'company_id' => $company->id,
            'password' => Hash::make($password),
            'must_change_password' => true,
            'welcome_handleiding_pending' => false,
            'email_verified_at' => now(),
        ]);
        $this->roles->syncWebRoles($user, ['company-admin']);
        $this->subscriptions->ensureProfile($company);

        $mailed = false;
        if ($sendWelcomeMail) {
            try {
                $this->sendWelcomeMail($company, $user, $password);
                $mailed = true;
            } catch (\Throwable $e) {
                Log::warning('Welkomstmail tenant mislukt', [
                    'company_id' => $company->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'user' => $user,
            'password' => $password,
            'created' => true,
            'mailed' => $mailed,
        ];
    }

    /**
     * Maak de company-admin aan (indien nodig) en verstuur de welkomstmail met een nieuw tijdelijk wachtwoord.
     *
     * @return array{user: User, password: string|null, created: bool, mailed: bool}
     */
    public function provisionOrResendWelcome(Company $company): array
    {
        $email = strtolower(trim((string) $company->email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Het bedrijf heeft geen geldig e-mailadres voor de company-admin.');
        }

        $existing = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if ($existing && (int) $existing->company_id !== (int) $company->id) {
            throw new RuntimeException('Dit e-mailadres is al in gebruik bij een andere gebruiker.');
        }

        if (! $existing) {
            return $this->provisionCompanyAdmin($company, true);
        }

        $password = $this->generateTemporaryPassword();
        $existing->forceFill([
            'password' => Hash::make($password),
            'must_change_password' => true,
        ])->save();
        $this->roles->syncWebRoles($existing, ['company-admin']);
        $this->subscriptions->ensureProfile($company);

        $mailed = false;
        try {
            $this->sendWelcomeMail($company, $existing, $password);
            $mailed = true;
        } catch (\Throwable $e) {
            Log::warning('Welkomstmail tenant mislukt', [
                'company_id' => $company->id,
                'user_id' => $existing->id,
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'user' => $existing,
            'password' => $password,
            'created' => false,
            'mailed' => $mailed,
        ];
    }

    public function sendWelcomeMail(Company $company, User $user, string $temporaryPassword): void
    {
        $template = $this->welcomeTemplate->resolveActive();
        if (! $template) {
            throw new RuntimeException('De welkomstmail-template ontbreekt.');
        }

        $package = $this->pricing->packageByKey((string) ($company->package_key ?? ''));
        $packageName = (string) ($package['name'] ?? $company->package_key ?: 'NEXA');
        $features = is_array($package['features'] ?? null) ? $package['features'] : [];
        $featuresHtml = TenantWelcomeEmailTemplateService::featuresHtml($features);
        $featuresText = TenantWelcomeEmailTemplateService::featuresText($features);

        $adminLoginUrl = TenantWelcomeEmailTemplateService::ADMIN_LOGIN_URL;
        $handleidingUrl = TenantWelcomeEmailTemplateService::HANDLEIDING_URL;
        $displayName = trim($user->first_name.' '.$user->last_name);
        $toName = $displayName !== '' ? $displayName : $user->email;

        $variables = array_merge(
            [
                'USER_NAME' => $toName,
                'USER_EMAIL' => $user->email,
                'TEMP_PASSWORD' => e($temporaryPassword),
                'COMPANY_NAME' => e((string) $company->name),
                'PACKAGE_NAME' => e($packageName),
                'PACKAGE_FEATURES_HTML' => $featuresHtml,
                'PACKAGE_FEATURES_TEXT' => e($featuresText),
                'ADMIN_LOGIN_URL' => $adminLoginUrl,
                'HANDLEIDING_URL' => $handleidingUrl,
                'ACTION_URL' => $adminLoginUrl,
            ],
            $this->logos->templateVariable($company->id, (string) $company->name),
            \App\Support\NexaBranding::emailLogoTemplateVariable()
        );

        $subject = $this->templates->parseTemplateVariables((string) $template->subject, $variables);
        $html = $this->templates->parseTemplateVariables((string) ($template->html_content ?? ''), $variables);
        $text = $template->text_content
            ? $this->templates->parseTemplateVariables((string) $template->text_content, $variables)
            : trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        $record = $this->customerMail->send([
            'company_id' => $company->id,
            'type' => TenantCustomerEmail::TYPE_TENANT_WELCOME,
            'to_email' => $user->email,
            'to_name' => $toName,
            'subject' => $subject !== '' ? $subject : 'Welkom bij NEXA Suite',
            'html' => $html,
            'text' => $text,
            'related_type' => 'user',
            'related_id' => $user->id,
            'meta' => ['kind' => 'tenant_admin_welcome'],
            'throw' => true,
        ]);

        if ($record->status !== TenantCustomerEmail::STATUS_SENT) {
            throw new RuntimeException($record->error_message ?: 'De welkomstmail kon niet worden verstuurd.');
        }
    }

    public function generateTemporaryPassword(): string
    {
        do {
            $password = Str::password(12, true, true, false, false);
        } while (! preg_match(self::PASSWORD_REGEX, $password));

        return $password;
    }
}
