<?php

namespace App\Services;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\Invoice;
use App\Models\InvoiceSetting;
use App\Models\PaymentReminder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class InvoiceReminderService
{
    public function __construct(
        protected EmailTemplateService $emailTemplates,
        protected InvoicePdfService $pdf,
        protected EnvService $env
    ) {}

    public function defaultRecipientEmail(Invoice $invoice): ?string
    {
        $email = trim((string) ($invoice->customer_email ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        $candidate = $invoice->jobMatch?->candidate;
        if ($candidate) {
            $email = trim((string) ($candidate->email ?? ''));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        }

        $company = $invoice->company;
        if ($company) {
            foreach ([$company->email ?? null, $company->contact_email ?? null] as $candidateEmail) {
                $email = trim((string) ($candidateEmail ?? ''));
                if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return $email;
                }
            }
        }

        return null;
    }

    public function send(Invoice $invoice, string $email): PaymentReminder
    {
        $email = trim($email);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => ['Vul een geldig e-mailadres in.'],
            ]);
        }

        if ($invoice->status === 'paid') {
            throw ValidationException::withMessages([
                'email' => ['Deze factuur is al betaald; een aanmaning is niet nodig.'],
            ]);
        }

        $invoice->update(['customer_email' => $email]);
        $invoice = $invoice->fresh();

        $pdfBytes = $this->pdf->generateAndStore($invoice)['bytes'];

        $this->sendReminderEmail($invoice, $email, $pdfBytes);

        $existingReminders = $invoice->reminders()->count();
        $reminderType = match (true) {
            $existingReminders >= 2 => 'final',
            $existingReminders === 1 => 'second',
            default => 'first',
        };

        return PaymentReminder::create([
            'invoice_id' => $invoice->id,
            'company_id' => $invoice->company_id,
            'reminder_type' => $reminderType,
            'sent_to_email' => $email,
            'message' => 'Aanmaning voor factuur '.$invoice->invoice_number,
            'sent_at' => now(),
        ]);
    }

    protected function sendReminderEmail(Invoice $invoice, string $toEmail, string $pdfBytes): void
    {
        $companyId = (int) ($invoice->company_id ?? 0);
        $template = EmailTemplate::query()
            ->where('type', 'reminder')
            ->where('is_active', true)
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            })
            ->orderByDesc('company_id')
            ->first();

        $company = $invoice->company ?? Company::find($companyId);
        $details = is_array($invoice->company_details) ? $invoice->company_details : [];
        $variables = [
            'CUSTOMER_NAME' => $invoice->customer_name ?? 'klant',
            'CUSTOMER_EMAIL' => $toEmail,
            'INVOICE_NUMBER' => $invoice->invoice_number,
            'INVOICE_DATE' => $invoice->invoice_date?->format('d-m-Y') ?? '',
            'DUE_DATE' => $invoice->due_date?->format('d-m-Y') ?? '',
            'INVOICE_TOTAL' => '€'.number_format((float) $invoice->total_amount, 2, ',', '.'),
            'COMPANY_NAME' => $details['name'] ?? ($company->name ?? ''),
            'PAYMENT_TERMS_DAYS' => (string) InvoiceSetting::paymentTermsDaysForInvoice($invoice),
        ];

        if ($template) {
            $subject = $this->emailTemplates->parseTemplateVariables($template->subject, $variables);
            $htmlContent = $this->emailTemplates->parseTemplateVariables($template->html_content, $variables);
            $textContent = $template->text_content
                ? $this->emailTemplates->parseTemplateVariables($template->text_content, $variables)
                : strip_tags($htmlContent);
        } else {
            $subject = 'Aanmaning factuur '.$invoice->invoice_number;
            $htmlContent = '<p>Beste '.e($variables['CUSTOMER_NAME']).',</p>'
                .'<p>Wij herinneren u eraan dat factuur <strong>'.e($invoice->invoice_number).'</strong> '
                .'met vervaldatum <strong>'.e($variables['DUE_DATE']).'</strong> nog openstaat.</p>'
                .'<p>Openstaand bedrag: <strong>'.e($variables['INVOICE_TOTAL']).'</strong>.</p>'
                .'<p>In de bijlage vindt u de factuur opnieuw. Wij verzoeken u vriendelijk het bedrag zo spoedig mogelijk te voldoen.</p>'
                .'<p>Met vriendelijke groet,<br>'.e($variables['COMPANY_NAME']).'</p>';
            $textContent = strip_tags($htmlContent);
        }

        $toName = $invoice->customer_name ?? $toEmail;
        $this->env->applyMailConfigToRuntime();
        $from = $this->env->resolveMailFromHeaders();
        $companyReplyTo = trim((string) ($details['email'] ?? ($company->email ?? '')));

        try {
            Mail::send([], [], function ($message) use (
                $toEmail,
                $toName,
                $subject,
                $htmlContent,
                $textContent,
                $invoice,
                $pdfBytes,
                $from,
                $companyReplyTo,
                $details
            ) {
                $message->to($toEmail, $toName)
                    ->subject($subject)
                    ->from($from['from_address'], $from['from_name']);

                if ($companyReplyTo !== '' && filter_var($companyReplyTo, FILTER_VALIDATE_EMAIL)) {
                    $message->replyTo($companyReplyTo, (string) ($details['name'] ?? ''));
                }

                if ($from['smtp_username'] !== '') {
                    try {
                        $symfonyMessage = $message->getSymfonyMessage();
                        $symfonyMessage->getHeaders()->remove('Sender');
                        $symfonyMessage->getHeaders()->addMailboxHeader('Sender', $from['smtp_username']);
                    } catch (\Throwable) {
                        // Sender header is optioneel
                    }
                }

                if ($htmlContent) {
                    $message->html($htmlContent);
                }
                if ($textContent) {
                    $message->text($textContent);
                }
                $message->attachData(
                    $pdfBytes,
                    'factuur-'.$invoice->invoice_number.'.pdf',
                    ['mime' => 'application/pdf']
                );
            });
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'not authorized to send on behalf of')
                || str_contains($e->getMessage(), '550 5.7.1')) {
                throw ValidationException::withMessages([
                    'email' => [
                        'De mailserver weigert verzending. Controleer Mail Server Instellingen (From-adres / SMTP-gebruiker).',
                    ],
                ]);
            }

            throw $e;
        }
    }
}
