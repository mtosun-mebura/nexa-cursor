<?php

namespace App\Services;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\Notification;
use App\Models\TenantCustomerEmail;
use App\Models\User;
use App\Support\NexaBranding;
use App\Support\TenantConfigCapability;
use Illuminate\Support\Facades\Log;
use Throwable;

class TenantConfigAccessNotifier
{
    public const NOTIFICATION_TYPE = 'config_access';

    public function __construct(
        protected TenantConfigAccessGrantedEmailTemplateService $templates,
        protected EmailTemplateService $parser,
        protected CompanyEmailLogoService $logos,
        protected TenantCustomerMailService $customerMail,
    ) {}

    /**
     * @param  list<string>  $capabilityKeys
     */
    public function notifyGranted(Company $company, User $recipient, array $capabilityKeys, User $grantedBy): void
    {
        $keys = array_values(array_filter(
            $capabilityKeys,
            fn ($key) => is_string($key) && TenantConfigCapability::isValid($key)
        ));
        if ($keys === []) {
            return;
        }

        $labelsText = $this->joinDutch(TenantConfigCapability::labelsFor($keys));
        $senderName = $this->displayName($grantedBy);
        $userName = $this->displayName($recipient);
        $actionUrl = $this->actionUrl($company, $keys);
        $template = $this->templates->ensureExists();

        Notification::query()->create([
            'user_id' => $recipient->id,
            'company_id' => $company->id,
            'type' => self::NOTIFICATION_TYPE,
            'category' => 'info',
            'title' => 'heeft je een bericht gestuurd',
            'message' => 'Je hebt toegang gekregen tot '.$labelsText.'. Je kunt deze configuratie nu invullen in de admin.',
            'priority' => 'normal',
            'action_url' => $actionUrl,
            'email_template_id' => $template->id,
            'data' => json_encode([
                'sender_id' => $grantedBy->id,
                'sender_email' => $grantedBy->email,
                'capabilities' => $keys,
            ]),
        ]);

        if (! filter_var((string) $recipient->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            $this->sendEmail($company, $recipient, $userName, $senderName, $labelsText, $actionUrl, $template, $grantedBy);
        } catch (Throwable $e) {
            Log::error('Config-access grant email failed', [
                'user_id' => $recipient->id,
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  list<string>  $keys
     */
    private function actionUrl(Company $company, array $keys): string
    {
        $step = TenantConfigCapability::wizardStepFor($keys[0] ?? '');
        if ($step !== null) {
            return route('admin.companies.wizard.step', [$company, $step]);
        }

        return url('/admin');
    }

    private function sendEmail(
        Company $company,
        User $recipient,
        string $userName,
        string $senderName,
        string $labelsText,
        string $actionUrl,
        EmailTemplate $template,
        User $grantedBy,
    ): void {
        $companyName = (string) ($company->name ?: 'NEXA Suite');
        $variables = array_merge(
            [
                'USER_NAME' => $userName,
                'SENDER_NAME' => $senderName,
                'COMPANY_NAME' => e($companyName),
                'CONFIG_LABELS' => $labelsText,
                'ACTION_URL' => $actionUrl,
            ],
            $this->logos->templateVariable((int) $company->id, $companyName),
            NexaBranding::emailLogoTemplateVariable()
        );

        $subject = $this->parser->parseTemplateVariables((string) $template->subject, $variables);
        $html = $this->parser->parseTemplateVariables((string) ($template->html_content ?? ''), $variables);
        $text = $template->text_content
            ? $this->parser->parseTemplateVariables((string) $template->text_content, $variables)
            : trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        $record = $this->customerMail->send([
            'company_id' => $company->id,
            'type' => TenantCustomerEmail::TYPE_CONFIG_ACCESS,
            'to_email' => $recipient->email,
            'to_name' => $userName,
            'subject' => $subject !== '' ? $subject : 'Nieuw bericht in NEXA Suite',
            'html' => $html,
            'text' => $text,
            'related_type' => 'user',
            'related_id' => $recipient->id,
            'platform_mail' => true,
            'meta' => [
                'kind' => 'tenant_config_access_granted',
                'platform_mail' => true,
                'sender_id' => $grantedBy->id,
            ],
            'throw' => false,
        ]);

        if ($record->status !== TenantCustomerEmail::STATUS_SENT) {
            Log::error('Config-access grant email failed', [
                'user_id' => $recipient->id,
                'company_id' => $company->id,
                'error' => $record->error_message,
            ]);
        }
    }

    private function displayName(User $user): string
    {
        $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

        return $name !== '' ? $name : (string) $user->email;
    }

    /**
     * @param  list<string>  $items
     */
    private function joinDutch(array $items): string
    {
        $items = array_values($items);
        $count = count($items);
        if ($count === 0) {
            return '';
        }
        if ($count === 1) {
            return $items[0];
        }
        if ($count === 2) {
            return $items[0].' en '.$items[1];
        }

        return implode(', ', array_slice($items, 0, -1)).' en '.$items[$count - 1];
    }
}
