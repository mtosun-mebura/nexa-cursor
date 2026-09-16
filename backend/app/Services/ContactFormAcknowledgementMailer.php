<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Support\NexaBranding;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactFormAcknowledgementMailer
{
    public function __construct(
        protected EnvService $env,
        protected CompanyEmailLogoService $logos
    ) {}

    /**
     * Bevestig aan de inzender dat de contactaanvraag is ontvangen.
     *
     * @param  array<string, mixed>  $variables
     */
    public function sendForInfoRequest(EmailTemplate $template, array $variables, string $companyName): void
    {
        $toEmail = $this->customerEmail($variables);
        if ($toEmail === null) {
            return;
        }

        $firstName = $this->displayValue($variables['VOORNAAM'] ?? null);
        $lastName = $this->displayValue($variables['ACHTERNAAM'] ?? null);
        $toName = trim($firstName.' '.$lastName);
        if ($toName === '') {
            $toName = $toEmail;
        }

        $companyName = trim($companyName) !== '' ? trim($companyName) : 'NEXA Suite';
        $isPlatform = $template->company_id === null;
        if ($isPlatform) {
            $companyName = NexaContactAanvraagEmailTemplateService::FROM_NAME;
        }

        $companyId = $template->company_id ? (int) $template->company_id : null;
        $this->env->applyMailConfigToRuntime($companyId);
        $from = $this->env->resolveMailFromHeaders($companyId, $isPlatform);
        $fromEmail = $from['from_address'] ?: config('mail.from.address');
        $fromName = $isPlatform
            ? NexaContactAanvraagEmailTemplateService::FROM_NAME
            : ($from['from_name'] ?: $companyName);

        $replyTo = $template->getRecipientEmailAddress();
        $logoHtml = $isPlatform
            ? (NexaBranding::emailLogoTemplateVariable()['NEXA_LOGO'] ?? '')
            : ($this->logos->templateVariable($companyId, $companyName)['COMPANY_LOGO'] ?? '');

        $subject = 'Wij hebben uw aanvraag ontvangen';
        $viewData = [
            'greetingName' => $toName !== $toEmail ? $toName : 'heer/mevrouw',
            'companyName' => $companyName,
            'logoHtml' => $logoHtml,
        ];
        $html = view('emails.contact-acknowledgement', $viewData)->render();
        $text = $this->plainText($viewData['greetingName'], $companyName);

        Mail::send([], [], function ($message) use (
            $toEmail,
            $toName,
            $subject,
            $fromEmail,
            $fromName,
            $replyTo,
            $companyId,
            $companyName,
            $html,
            $text
        ) {
            if ($fromEmail) {
                $message->from($fromEmail, $fromName ?: $fromEmail);
            }
            $message->to($toEmail, $toName)->subject($subject);
            if (is_string($replyTo) && filter_var($replyTo, FILTER_VALIDATE_EMAIL) && strcasecmp($replyTo, $toEmail) !== 0) {
                $message->replyTo($replyTo, $fromName ?: $replyTo);
            }
            $message->html($this->logos->embedInHtml($html, $message, $companyId, $companyName));
            $message->text($text);
        });
    }

    protected function plainText(string $greetingName, string $companyName): string
    {
        return implode("\n\n", [
            'Beste '.$greetingName.',',
            'Hartelijk dank voor uw bericht via ons contactformulier. Wij bevestigen dat uw aanvraag in goede orde bij ons is binnengekomen.',
            'Ons team neemt uw verzoek zo spoedig mogelijk in behandeling en komt hierop bij u terug. U hoeft hiervoor verder niets te doen.',
            'Wilt u in de tussentijd iets toevoegen of wijzigen? Beantwoord dan gerust deze e-mail.',
            "Met vriendelijke groet,\n".$companyName,
            'Dit is een automatische bevestiging. U ontvangt zo spoedig mogelijk een persoonlijk antwoord.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    public function sendSafely(EmailTemplate $template, array $variables, string $companyName): void
    {
        try {
            $this->sendForInfoRequest($template, $variables, $companyName);
        } catch (\Throwable $e) {
            Log::warning('Contactformulier: ontvangstbevestiging niet verzonden.', [
                'error' => $e->getMessage(),
                'template_id' => $template->id,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    protected function customerEmail(array $variables): ?string
    {
        foreach (['EMAIL_AANVRAAG', 'EMAIL', 'E_MAIL'] as $key) {
            $email = $this->validEmail($variables[$key] ?? null);
            if ($email !== null) {
                return $email;
            }
        }

        foreach ($variables as $value) {
            if (! is_string($value) && ! is_numeric($value)) {
                continue;
            }
            $email = $this->validEmail($value);
            if ($email !== null) {
                return $email;
            }
        }

        return null;
    }

    protected function validEmail(mixed $value): ?string
    {
        $email = trim((string) $value);
        if ($email === '' || $email === '-') {
            return null;
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    protected function displayValue(mixed $value): string
    {
        $text = trim((string) $value);

        return ($text === '' || $text === '-') ? '' : $text;
    }
}
