<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\Company;
use App\Models\CustomerLoginCode;
use App\Models\User;
use App\Services\CompanyEmailLogoService;
use App\Services\EmailTemplateService;
use App\Services\EnvService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
     */
    public function issueAndSend(User $user, ?int $companyId, string $loginUrl, ?int $expiresMinutes = null): bool
    {
        $expiresMinutes = $expiresMinutes ?? app(TaxiDispatchSettingsService::class)
            ->customerLoginCodeExpiresMinutes($companyId && $companyId > 0 ? $companyId : null);

        $code = str_pad((string) random_int(0, 10 ** self::CODE_LENGTH - 1), self::CODE_LENGTH, '0', STR_PAD_LEFT);

        CustomerLoginCode::create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($expiresMinutes),
        ]);

        $mailCompanyId = $companyId && $companyId > 0 ? $companyId : null;

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

            return false;
        }

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

        $this->env->applyMailConfigToRuntime($mailCompanyId);
        $from = $this->env->resolveMailFromHeaders($mailCompanyId);

        $recipientName = $variables['USER_NAME'] ?: $user->email;

        try {
            Mail::send([], [], function ($message) use ($user, $subject, $htmlContent, $textContent, $from, $companyId, $companyName, $recipientName) {
                try {
                    $htmlBody = $this->companyLogos->embedInHtml(
                        $htmlContent,
                        $message,
                        $companyId && $companyId > 0 ? $companyId : null,
                        $companyName
                    );
                } catch (\Throwable $logoError) {
                    Log::warning('Logo embed mislukt voor inlogcode-mail, verstuur zonder ingesloten logo.', [
                        'error' => $logoError->getMessage(),
                    ]);
                    $htmlBody = $htmlContent;
                }

                $message->to($user->email, $recipientName)
                    ->subject($subject)
                    ->from($from['from_address'], $from['from_name'])
                    ->html($htmlBody)
                    ->text($textContent);

                if ($from['smtp_username'] !== '') {
                    try {
                        $symfonyMessage = $message->getSymfonyMessage();
                        $symfonyMessage->getHeaders()->remove('Sender');
                        $symfonyMessage->getHeaders()->addMailboxHeader('Sender', $from['smtp_username']);
                    } catch (\Throwable) {
                        // optioneel
                    }
                }
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('Kon inlogcode-e-mail niet versturen.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'mailer' => config('mail.default'),
                'error' => $e->getMessage(),
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
}
