<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\TenantCustomerEmail;
use App\Models\User;
use App\Modules\NexaTaxi\Services\TaxiCustomerLoginCodeService;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class TenantCustomerMailService
{
    public function __construct(
        protected EnvService $env,
        protected CompanyEmailLogoService $companyLogos,
        protected NexaDemoAccountService $demoAccounts,
        protected InvoicePdfService $invoicePdf,
    ) {}

    /**
     * Verstuur een klantmail via de tenant-SMTP en leg die vast.
     *
     * @param  array{
     *     company_id?: int|null,
     *     type: string,
     *     to_email: string,
     *     to_name?: string|null,
     *     subject: string,
     *     html?: string|null,
     *     text?: string|null,
     *     related_type?: string|null,
     *     related_id?: int|null,
     *     resent_from_id?: int|null,
     *     reply_to?: string|null,
     *     reply_to_name?: string|null,
     *     attachments?: list<array{bytes: string, filename: string, mime?: string}>,
     *     meta?: array<string, mixed>,
     *     throw?: bool
     * }  $data
     */
    public function send(array $data): TenantCustomerEmail
    {
        $companyId = isset($data['company_id']) ? (int) $data['company_id'] : 0;
        $companyId = $companyId > 0 ? $companyId : null;
        $html = (string) ($data['html'] ?? '');
        $text = (string) ($data['text'] ?? '');
        if ($text === '' && $html !== '') {
            $text = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
        $subject = (string) ($data['subject'] ?? '');
        $toEmail = trim((string) ($data['to_email'] ?? ''));
        $toName = trim((string) ($data['to_name'] ?? ''));
        $attachments = is_array($data['attachments'] ?? null) ? $data['attachments'] : [];
        $throw = (bool) ($data['throw'] ?? false);

        $attributes = [
            'company_id' => $companyId,
            'type' => (string) ($data['type'] ?? 'other'),
            'recipient_email' => $toEmail,
            'recipient_name' => $toName !== '' ? $toName : null,
            'subject' => $subject !== '' ? $subject : '(geen onderwerp)',
            'body_html' => $html !== '' ? $html : null,
            'body_text' => $text !== '' ? $text : null,
            'related_type' => $data['related_type'] ?? null,
            'related_id' => isset($data['related_id']) ? (int) $data['related_id'] : null,
            'resent_from_id' => isset($data['resent_from_id']) ? (int) $data['resent_from_id'] : null,
            'resent_count' => 0,
            'meta' => array_filter(array_merge(
                ['has_pdf' => $attachments !== []],
                is_array($data['meta'] ?? null) ? $data['meta'] : []
            ), fn ($value) => $value !== null && $value !== ''),
        ];

        if ($toEmail === '' || ! filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return $this->record($attributes, TenantCustomerEmail::STATUS_FAILED, 'Geen geldig e-mailadres.');
        }

        if ($this->demoAccounts->shouldSuppressOutgoingMail()) {
            return $this->record($attributes, TenantCustomerEmail::STATUS_SKIPPED, 'Demo: uitgaande mail onderdrukt.');
        }

        $this->env->applyMailConfigToRuntime($companyId);
        $from = $this->env->resolveMailFromHeaders($companyId);
        $companyName = $companyId ? (Company::query()->find($companyId)?->name) : null;
        $replyTo = trim((string) ($data['reply_to'] ?? ''));
        $replyToName = trim((string) ($data['reply_to_name'] ?? ''));

        try {
            Mail::send([], [], function (Message $message) use (
                $html,
                $text,
                $subject,
                $toEmail,
                $toName,
                $from,
                $companyId,
                $companyName,
                $replyTo,
                $replyToName,
                $attachments
            ) {
                $htmlBody = $html;
                if ($html !== '') {
                    try {
                        $htmlBody = $this->companyLogos->embedInHtml($html, $message, $companyId, $companyName);
                    } catch (\Throwable $logoError) {
                        Log::warning('Logo embed mislukt voor klantmail, verstuur zonder ingesloten logo.', [
                            'error' => $logoError->getMessage(),
                        ]);
                    }
                }

                $message->to($toEmail, $toName !== '' ? $toName : null)
                    ->subject($subject)
                    ->from($from['from_address'], $from['from_name']);

                if ($htmlBody !== '') {
                    $message->html($htmlBody);
                }
                if ($text !== '') {
                    $message->text($text);
                }

                if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                    $message->replyTo($replyTo, $replyToName !== '' ? $replyToName : null);
                }

                if ($from['smtp_username'] !== '') {
                    try {
                        $symfonyMessage = $message->getSymfonyMessage();
                        $symfonyMessage->getHeaders()->remove('Sender');
                        $symfonyMessage->getHeaders()->addMailboxHeader('Sender', $from['smtp_username']);
                    } catch (\Throwable) {
                        // Sender-header is optioneel
                    }
                }

                foreach ($attachments as $attachment) {
                    $bytes = $attachment['bytes'] ?? '';
                    $filename = (string) ($attachment['filename'] ?? 'bijlage');
                    if ($bytes === '') {
                        continue;
                    }
                    $message->attachData(
                        $bytes,
                        $filename,
                        ['mime' => (string) ($attachment['mime'] ?? 'application/octet-stream')]
                    );
                }
            });
        } catch (\Throwable $e) {
            $record = $this->record($attributes, TenantCustomerEmail::STATUS_FAILED, $e->getMessage());
            if ($throw) {
                throw $e;
            }

            return $record;
        }

        return $this->record($attributes, TenantCustomerEmail::STATUS_SENT);
    }

    /**
     * Leg een klantmail vast zonder te versturen (bijv. geen SMTP).
     *
     * @param  array<string, mixed>  $data
     */
    public function record(array $data, string $status, ?string $error = null): TenantCustomerEmail
    {
        $companyId = isset($data['company_id']) ? (int) $data['company_id'] : 0;
        $html = (string) ($data['html'] ?? $data['body_html'] ?? '');
        $text = (string) ($data['text'] ?? $data['body_text'] ?? '');
        $toEmail = trim((string) ($data['to_email'] ?? $data['recipient_email'] ?? ''));
        $toName = trim((string) ($data['to_name'] ?? $data['recipient_name'] ?? ''));

        return $this->persist([
            'company_id' => $companyId > 0 ? $companyId : null,
            'type' => (string) ($data['type'] ?? 'other'),
            'recipient_email' => $toEmail,
            'recipient_name' => $toName !== '' ? $toName : null,
            'subject' => (string) ($data['subject'] ?? '(geen onderwerp)'),
            'body_html' => $html !== '' ? $html : null,
            'body_text' => $text !== '' ? $text : null,
            'related_type' => $data['related_type'] ?? null,
            'related_id' => isset($data['related_id']) ? (int) $data['related_id'] : null,
            'resent_from_id' => isset($data['resent_from_id']) ? (int) $data['resent_from_id'] : null,
            'resent_count' => 0,
            'meta' => is_array($data['meta'] ?? null) ? $data['meta'] : null,
        ], $status, $error);
    }

    public function resend(TenantCustomerEmail $original): TenantCustomerEmail
    {
        if ($original->type === TenantCustomerEmail::TYPE_TENANT_WELCOME) {
            $company = $original->company_id ? Company::query()->find($original->company_id) : null;
            if (! $company) {
                throw new RuntimeException('Het bedrijf van deze welkomstmail ontbreekt.');
            }

            $result = app(TenantOnboardingService::class)->provisionOrResendWelcome($company);
            $this->markResent($original);

            $latest = TenantCustomerEmail::query()
                ->where('type', TenantCustomerEmail::TYPE_TENANT_WELCOME)
                ->where('related_type', 'user')
                ->where('related_id', $result['user']->id)
                ->where('id', '!=', $original->id)
                ->orderByDesc('id')
                ->first();

            if ($latest) {
                return $latest;
            }

            if (! $result['mailed']) {
                throw new RuntimeException('De welkomstmail kon niet opnieuw worden verstuurd.');
            }

            return $original;
        }

        if (in_array($original->type, [TenantCustomerEmail::TYPE_WELCOME, TenantCustomerEmail::TYPE_LOGIN_CODE], true)
            && $original->related_type === 'user'
            && $original->related_id
        ) {
            $user = User::query()->find($original->related_id);
            if ($user && filter_var((string) $user->email, FILTER_VALIDATE_EMAIL)) {
                $loginUrl = route('login', [
                    'code_login' => 1,
                    'email' => $user->email,
                ]);
                $sent = app(TaxiCustomerLoginCodeService::class)->issueAndSend(
                    $user,
                    $original->company_id ? (int) $original->company_id : null,
                    $loginUrl,
                    null,
                    $original->type === TenantCustomerEmail::TYPE_WELCOME
                        ? TenantCustomerEmail::TYPE_WELCOME
                        : TenantCustomerEmail::TYPE_LOGIN_CODE,
                    ['resent_from_id' => $original->id]
                );

                $this->markResent($original);

                $latest = TenantCustomerEmail::query()
                    ->where('resent_from_id', $original->id)
                    ->orderByDesc('id')
                    ->first();

                if ($latest) {
                    return $latest;
                }

                if (! $sent) {
                    throw new RuntimeException('De inlogcode-e-mail kon niet opnieuw worden verstuurd.');
                }
            }
        }

        $attachments = $this->attachmentsForResend($original);
        $copy = $this->send([
            'company_id' => $original->company_id,
            'type' => $original->type,
            'to_email' => $original->recipient_email,
            'to_name' => $original->recipient_name,
            'subject' => $original->subject,
            'html' => $original->body_html,
            'text' => $original->body_text,
            'related_type' => $original->related_type,
            'related_id' => $original->related_id,
            'resent_from_id' => $original->id,
            'attachments' => $attachments,
            'meta' => is_array($original->meta) ? $original->meta : [],
            'throw' => true,
        ]);

        $this->markResent($original);

        return $copy;
    }

    /**
     * @return list<array{bytes: string, filename: string, mime: string}>
     */
    protected function attachmentsForResend(TenantCustomerEmail $original): array
    {
        $hasPdf = (bool) (($original->meta['has_pdf'] ?? false));
        if (! $hasPdf || $original->related_type !== 'invoice' || ! $original->related_id) {
            return [];
        }

        $invoice = Invoice::query()->find($original->related_id);
        if (! $invoice) {
            return [];
        }

        try {
            $pdf = $this->invoicePdf->generateAndStore($invoice);
        } catch (\Throwable $e) {
            Log::warning('PDF voor opnieuw versturen van klantmail kon niet worden gemaakt.', [
                'tenant_customer_email_id' => $original->id,
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        if (empty($pdf['bytes'])) {
            return [];
        }

        $filename = 'factuur-'.preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $invoice->invoice_number).'.pdf';

        return [[
            'bytes' => $pdf['bytes'],
            'filename' => $filename,
            'mime' => 'application/pdf',
        ]];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function persist(array $attributes, string $status, ?string $error = null): TenantCustomerEmail
    {
        $attributes['status'] = $status;
        $attributes['error_message'] = $error;
        $attributes['sent_at'] = $status === TenantCustomerEmail::STATUS_SENT ? now() : null;

        return TenantCustomerEmail::query()->create($attributes);
    }

    protected function markResent(TenantCustomerEmail $original): void
    {
        $original->resent_count = (int) $original->resent_count + 1;
        $original->last_resent_at = now();
        $original->save();
    }

    /**
     * HTML zoals de klant die in de mailbox zag (light-mode, logo’s als URL i.p.v. cid).
     */
    public function mailboxPreviewHtml(TenantCustomerEmail $email): string
    {
        $email->loadMissing('company');
        $html = trim((string) $email->body_html);
        $companyId = $email->company_id ? (int) $email->company_id : null;
        $companyName = $email->company?->name;

        if ($html === '') {
            $text = trim((string) $email->body_text);
            $html = $text !== ''
                ? '<pre style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;white-space:pre-wrap;margin:0;">'.e($text).'</pre>'
                : '<p style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#111;">Geen inhoud vastgelegd.</p>';
        }

        $html = $this->companyLogos->injectPreviewLogoIntoHtml($html, $companyId, $companyName, true);

        $logoUrl = $this->companyLogos->adminPreviewLogoUrl($companyId);
        if (is_string($logoUrl) && $logoUrl !== '') {
            $html = preg_replace(
                '/src=(["\'])cid:[^"\']+\1/i',
                'src="'.e($logoUrl).'"',
                $html
            ) ?? $html;
        }

        $lightHead = '<meta charset="utf-8">'
            .'<meta name="viewport" content="width=device-width, initial-scale=1">'
            .'<meta name="color-scheme" content="light">'
            .'<meta name="supported-color-schemes" content="light">'
            .'<style>html,body{margin:0;padding:0;background:#ffffff;color:#111111;color-scheme:light;}</style>';

        if (preg_match('/<html[\s>]/i', $html)) {
            if (preg_match('/<head[^>]*>/i', $html)) {
                return preg_replace('/<head([^>]*)>/i', '<head$1>'.$lightHead, $html, 1) ?? $html;
            }

            return preg_replace('/<html([^>]*)>/i', '<html$1><head>'.$lightHead.'</head>', $html, 1) ?? $html;
        }

        return '<!DOCTYPE html><html lang="nl"><head>'.$lightHead.'</head>'
            .'<body style="margin:0;padding:16px;background:#ffffff;color:#111111;">'.$html.'</body></html>';
    }
}
