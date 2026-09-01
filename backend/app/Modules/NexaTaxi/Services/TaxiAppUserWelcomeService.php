<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\Company;
use App\Models\User;
use App\Services\CompanyEmailLogoService;
use App\Services\EmailTemplateService;
use App\Services\EnvService;
use App\Services\TenantCustomerMailService;
use App\Support\NexaBranding;
use App\Models\TenantCustomerEmail;
use Illuminate\Support\Facades\Log;

class TaxiAppUserWelcomeService
{
    public function __construct(
        protected TaxiAppUserWelcomeEmailTemplateService $templates,
        protected TaxiAppFirstLoginService $firstLogin,
        protected EmailTemplateService $parser,
        protected CompanyEmailLogoService $logos,
        protected EnvService $env,
        protected TenantCustomerMailService $customerMail,
    ) {}

    /**
     * @param  list<string>  $roleNames
     */
    public function sendIfNeeded(User $user, array $roleNames, bool $force = false): bool
    {
        $role = $this->firstLogin->welcomeRoleForRoles($roleNames);
        if ($role === null) {
            return false;
        }

        if (! $force && ! $this->firstLogin->needsFirstLogin($user)) {
            return false;
        }

        return $this->send($user, $role);
    }

    public function send(User $user, string $role): bool
    {
        $type = $this->firstLogin->welcomeTemplateType($role);
        $channel = $this->firstLogin->channelForRole($role);
        if ($type === null || $channel === null) {
            return false;
        }

        $companyId = (int) ($user->company_id ?? 0);
        $company = $companyId > 0 ? Company::query()->find($companyId) : null;
        $companyName = $company?->name ?: 'NEXA Taxi';
        $loginUrl = $this->firstLogin->loginUrlForChannel($channel);
        $appName = $this->firstLogin->appNameForChannel($channel);
        $meta = $this->templates->typeMeta($type);

        $variables = array_merge(
            [
                'USER_NAME' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: (string) $user->email,
                'USER_EMAIL' => (string) $user->email,
                'COMPANY_NAME' => e($companyName),
                'ROLE_LABEL' => $meta['role'],
                'APP_NAME' => $appName,
                'LOGIN_URL' => $loginUrl,
                'ACTION_URL' => $loginUrl,
            ],
            $this->logos->templateVariable($companyId > 0 ? $companyId : null, $companyName),
            NexaBranding::emailLogoTemplateVariable()
        );

        $template = $this->templates->resolveActiveTemplate($type, $companyId > 0 ? $companyId : null);
        if ($template) {
            $subject = $this->parser->parseTemplateVariables($template->subject, $variables);
            $html = $this->parser->parseTemplateVariables($template->html_content, $variables);
            $text = $template->text_content
                ? $this->parser->parseTemplateVariables($template->text_content, $variables)
                : strip_tags($html);
        } else {
            $defaults = $this->templates->defaultPayload($type, null);
            $subject = $this->parser->parseTemplateVariables((string) $defaults['subject'], $variables);
            $html = $this->parser->parseTemplateVariables($this->templates->defaultHtmlContent($meta), $variables);
            $text = $this->parser->parseTemplateVariables($this->templates->defaultTextContent($meta), $variables);
        }

        $payload = [
            'company_id' => $companyId > 0 ? $companyId : null,
            'type' => TenantCustomerEmail::TYPE_WELCOME,
            'to_email' => (string) $user->email,
            'to_name' => $variables['USER_NAME'],
            'subject' => $subject,
            'html' => $html,
            'text' => $text,
            'related_type' => 'user',
            'related_id' => $user->id,
            'meta' => [
                'welcome_role' => $role,
                'channel' => $channel,
            ],
        ];

        $deliverable = $this->env->isMailDeliverableToInbox($companyId > 0 ? $companyId : null)
            || app()->environment('testing');

        if (! $deliverable) {
            Log::warning('App-welkomstmail niet verstuurd: geen bruikbare SMTP.', [
                'user_id' => $user->id,
                'company_id' => $companyId,
            ]);
            $this->customerMail->record($payload, TenantCustomerEmail::STATUS_FAILED, 'Geen bruikbare SMTP-configuratie voor deze tenant.');

            return false;
        }

        $record = $this->customerMail->send($payload);

        return $record->status === TenantCustomerEmail::STATUS_SENT;
    }
}
