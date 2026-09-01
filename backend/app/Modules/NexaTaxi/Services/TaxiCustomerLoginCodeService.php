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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TaxiCustomerLoginCodeService
{
    public const CODE_LENGTH = 6;

    public function __construct(
        protected TaxiCustomerLoginCodeEmailTemplateService $emailTemplateService,
        protected EmailTemplateService $templateParser,
        protected CompanyEmailLogoService $companyLogos,
        protected EnvService $env
    ) {}

    /**
     * Genereer code, sla op en verstuur e-mail. Retourneert false bij mislukte verzending.
     *
     * @param  array{resent_from_id?: int}  $logMeta
     */
    public function issueAndSend(
        User $user,
        ?int $companyId,
        string $loginUrl,
        ?int $expiresMinutes = null,
        string $mailType = TenantCustomerEmail::TYPE_LOGIN_CODE,
        array $logMeta = []
    ): bool {
        $expiresMinutes = $expiresMinutes ?? app(TaxiDispatchSettingsService::class)
            ->customerLoginCodeExpiresMinutes($companyId && $companyId > 0 ? $companyId : null);

        $code = str_pad((string) random_int(0, 10 ** self::CODE_LENGTH - 1), self::CODE_LENGTH, '0', STR_PAD_LEFT);

        CustomerLoginCode::create([
            'user_id' => $user->id,
            'purpose' => CustomerLoginCode::PURPOSE_CUSTOMER,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($expiresMinutes),
        ]);

        $mailCompanyId = $companyId && $companyId > 0 ? $companyId : null;
        $mailType = $mailType === TenantCustomerEmail::TYPE_WELCOME
            ? TenantCustomerEmail::TYPE_WELCOME
            : TenantCustomerEmail::TYPE_LOGIN_CODE;

        $template = $this->emailTemplateService->resolveActiveTemplate($companyId);

        $companyName = $companyId ? (Company::query()->find($companyId)?->name) : null;
        $companyName = $companyName ?: 'NEXA Taxi';

        $variables = [
            'USER_NAME' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: ($user->email ?? ''),
            'USER_EMAIL' => (string) ($user->email ?? ''),
            'COMPANY_NAME' => $companyName,
            'LOGIN_CODE' => $code,
            'LOGIN_URL' => $loginUrl,
            'CODE_EXPIRES_MINUTES' => (string) $expiresMinutes,
        ];

        $variables = array_merge(
            $variables,
            $this->companyLogos->templateVariable(
                $companyId && $companyId > 0 ? $companyId : null,
                $companyName
            )
        );

        if ($template) {
            $subject = $this->templateParser->parseTemplateVariables($template->subject, $variables);
            $htmlContent = $this->templateParser->parseTemplateVariables($template->html_content, $variables);
            $textContent = $template->text_content
                ? $this->templateParser->parseTemplateVariables($template->text_content, $variables)
                : strip_tags($htmlContent);
        } else {
            $defaults = $this->emailTemplateService->defaultPayload(null);
            $subject = $this->templateParser->parseTemplateVariables((string) $defaults['subject'], $variables);
            $htmlContent = $this->templateParser->parseTemplateVariables($this->emailTemplateService->defaultHtmlContent(), $variables);
            $textContent = $this->templateParser->parseTemplateVariables($this->emailTemplateService->defaultTextContent(), $variables);
        }

        $payload = [
            'company_id' => $mailCompanyId,
            'type' => $mailType,
            'to_email' => (string) $user->email,
            'to_name' => $variables['USER_NAME'] ?: $user->email,
            'subject' => $subject,
            'html' => $htmlContent,
            'text' => $textContent,
            'related_type' => 'user',
            'related_id' => $user->id,
            'resent_from_id' => $logMeta['resent_from_id'] ?? null,
        ];

        $customerMail = app(TenantCustomerMailService::class);

        if (! $this->env->isMailDeliverableToInbox($mailCompanyId)) {
            Log::warning('Inlogcode-e-mail niet verstuurd: geen bruikbare SMTP voor deze tenant.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'company_id' => $mailCompanyId,
                'mailer' => config('mail.default'),
            ]);
            if (app()->environment('local')) {
                Log::info('DEV: eenmalige inlogcode (alleen in log, niet per e-mail)', [
                    'email' => $user->email,
                    'login_code' => $code,
                    'login_url' => $loginUrl,
                ]);
            }

            $customerMail->record($payload, TenantCustomerEmail::STATUS_FAILED, 'Geen bruikbare SMTP-configuratie voor deze tenant.');

            return false;
        }

        $record = $customerMail->send($payload);

        if ($record->status === TenantCustomerEmail::STATUS_SENT) {
            return true;
        }

        Log::error('Kon inlogcode-e-mail niet versturen.', [
            'user_id' => $user->id,
            'email' => $user->email,
            'mailer' => config('mail.default'),
            'error' => $record->error_message,
        ]);

        if (app()->environment('local')) {
            Log::info('DEV: eenmalige inlogcode (verzending mislukt)', [
                'email' => $user->email,
                'login_code' => $code,
                'login_url' => $loginUrl,
            ]);
        }

        return false;
    }
}
