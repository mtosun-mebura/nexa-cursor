<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\EmailTemplate;
use App\Models\Invoice;
use App\Models\InvoiceSetting;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Services\CompanyEmailLogoService;
use App\Services\EmailTemplateService;
use App\Services\EnvService;
use App\Services\InvoicePdfService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class TaxiRideInvoiceService
{
    public function __construct(
        protected InvoicePdfService $pdf,
        protected EmailTemplateService $emailTemplates,
        protected EnvService $env,
        protected CompanyEmailLogoService $companyLogos
    ) {}

    public function ensureInvoiceForPaidRide(string $conn, RideRequest $ride, bool $generatePdf = false): ?Invoice
    {
        if ($ride->payment_status !== RideRequest::PAYMENT_STATUS_PAID) {
            return null;
        }

        $companyId = (int) ($ride->company_id ?? 0);
        if ($companyId <= 0) {
            return null;
        }

        $existing = $this->findInvoiceForRide($ride);
        if ($existing) {
            if (! $ride->invoice_id) {
                RideRequest::on($conn)->whereKey($ride->id)->update(['invoice_id' => $existing->id]);
            }

            return $existing;
        }

        return DB::transaction(function () use ($conn, $ride, $companyId, $generatePdf) {
            $ride = RideRequest::on($conn)->whereKey($ride->id)->lockForUpdate()->firstOrFail();
            $existing = $this->findInvoiceForRide($ride);
            if ($existing) {
                return $existing;
            }

            $invoice = $this->createInvoiceFromRide($ride, $companyId);
            $ride->update(['invoice_id' => $invoice->id]);

            if ($generatePdf) {
                $this->pdf->generateAndStore($invoice->fresh());
            }

            return $invoice->fresh();
        });
    }

    /**
     * Factuur voor klantportaal: aanmaken wanneer er nog geen is (geen betaalstatus vereist).
     */
    public function ensureInvoiceForCustomerPortal(string $conn, RideRequest $ride, bool $generatePdf = true): Invoice
    {
        if ($ride->status === RideRequest::STATUS_CANCELLED) {
            throw ValidationException::withMessages([
                'invoice' => ['Voor een geannuleerde rit kan geen factuur worden gemaakt.'],
            ]);
        }

        $existing = $this->findInvoiceForRide($ride);
        if ($existing) {
            if (! $ride->invoice_id) {
                RideRequest::on($conn)->whereKey($ride->id)->update(['invoice_id' => $existing->id]);
            }
            if ($generatePdf) {
                $this->pdf->generateAndStore($existing->fresh());
            }

            return $existing->fresh();
        }

        return DB::transaction(function () use ($conn, $ride, $generatePdf) {
            $ride = RideRequest::on($conn)->whereKey($ride->id)->lockForUpdate()->firstOrFail();
            $existing = $this->findInvoiceForRide($ride);
            if ($existing) {
                return $existing;
            }

            $companyId = (int) ($ride->company_id ?? 0);
            if ($companyId <= 0) {
                throw ValidationException::withMessages([
                    'invoice' => ['Rit heeft geen gekoppeld bedrijf.'],
                ]);
            }

            $invoice = $this->createInvoiceFromRide($ride, $companyId);
            $ride->update(['invoice_id' => $invoice->id]);

            if ($generatePdf) {
                $this->pdf->generateAndStore($invoice->fresh());
            }

            return $invoice->fresh();
        });
    }

    public function findInvoiceForRide(RideRequest $ride): ?Invoice
    {
        if ($ride->invoice_id) {
            $byId = Invoice::query()->find($ride->invoice_id);
            if ($byId) {
                return $byId;
            }
        }

        return Invoice::query()
            ->where('module', Invoice::MODULE_TAXI)
            ->where('module_reference_id', $ride->id)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function driverInvoicePayload(RideRequest $ride): array
    {
        $invoice = $this->findInvoiceForRide($ride);
        if (! $invoice && $ride->payment_status === RideRequest::PAYMENT_STATUS_PAID) {
            $invoice = $this->ensureInvoiceForPaidRide($ride->getConnectionName(), $ride->fresh(), false);
        }

        $isPaid = $ride->payment_status === RideRequest::PAYMENT_STATUS_PAID;

        return [
            'has_invoice' => $invoice !== null,
            'invoice_id' => $invoice?->id,
            'invoice_number' => $invoice?->invoice_number,
            'customer_email' => $invoice?->customer_email ?? $ride->customer_email,
            'customer_name' => $invoice?->customer_name ?? $ride->customer_name,
            'total_amount' => $invoice ? (float) $invoice->total_amount : null,
            'invoice_sent' => $invoice?->status === 'sent',
            'can_send' => $isPaid && ($invoice === null || $invoice->status !== 'sent'),
        ];
    }

    public function sendInvoiceToCustomer(
        string $conn,
        RideRequest $ride,
        string $email,
        ?string $invoiceNumber = null
    ): Invoice {
        if ($ride->payment_status !== RideRequest::PAYMENT_STATUS_PAID) {
            throw ValidationException::withMessages([
                'invoice' => ['De rit moet eerst betaald zijn voordat een factuur verstuurd kan worden.'],
            ]);
        }

        $email = trim($email);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => ['Vul een geldig e-mailadres in.'],
            ]);
        }

        $invoice = $this->ensureInvoiceForPaidRide($conn, $ride, false);
        if (! $invoice) {
            throw ValidationException::withMessages([
                'invoice' => ['Factuur kon niet worden aangemaakt.'],
            ]);
        }

        return DB::transaction(function () use ($invoice, $email, $invoiceNumber) {
            $invoice = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($invoiceNumber !== null && trim($invoiceNumber) !== '' && trim($invoiceNumber) !== $invoice->invoice_number) {
                $newNumber = trim($invoiceNumber);
                if (Invoice::query()->where('invoice_number', $newNumber)->where('id', '!=', $invoice->id)->exists()) {
                    throw ValidationException::withMessages([
                        'invoice_number' => ['Dit factuurnummer bestaat al.'],
                    ]);
                }
                $invoice->update(['invoice_number' => $newNumber]);
            }

            $invoice->update([
                'customer_email' => $email,
                'status' => 'sent',
            ]);

            try {
                $pdf = $this->pdf->generateAndStore($invoice->fresh());
            } catch (\Throwable $e) {
                if ($this->isGdExtensionMissing($e)) {
                    throw ValidationException::withMessages([
                        'invoice' => ['PDF kon niet worden gemaakt: installeer de PHP-extensie gd (bijv. pecl install gd).'],
                    ]);
                }
                throw $e;
            }
            $this->sendInvoiceEmail($invoice->fresh(), $pdf['bytes']);

            return $invoice->fresh();
        });
    }

    protected function createInvoiceFromRide(RideRequest $ride, int $companyId): Invoice
    {
        $settings = InvoiceSetting::getSettingsForCompany($companyId);
        $company = Company::find($companyId);
        $amount = round((float) ($ride->final_price ?? $ride->quoted_price ?? 0), 2);
        if ($amount < 0.01) {
            throw ValidationException::withMessages([
                'amount' => ['Geen geldig factuurbedrag voor deze rit.'],
            ]);
        }

        $taxRate = (float) $settings->default_tax_rate;
        $taxAmount = round($amount * ($taxRate / (100 + $taxRate)), 2);
        $netAmount = round($amount - $taxAmount, 2);
        $invoiceDate = now();
        $dueDate = $invoiceDate->copy()->addDays((int) $settings->payment_terms_days);

        $description = 'Taxirit';
        if ($ride->pickup_address && $ride->dropoff_address) {
            $description .= ': '.$ride->pickup_address.' → '.$ride->dropoff_address;
        }

        $invoiceNumber = $settings->generateInvoiceNumber();

        return Invoice::query()->create([
            'invoice_number' => $invoiceNumber,
            'company_id' => $companyId,
            'module' => Invoice::MODULE_TAXI,
            'module_reference_id' => $ride->id,
            'customer_name' => $ride->customer_name,
            'customer_email' => $ride->customer_email,
            'amount' => $netAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $amount,
            'currency' => 'EUR',
            'status' => 'paid',
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'paid_date' => $invoiceDate,
            'line_items' => [
                [
                    'description' => $description,
                    'quantity' => 1,
                    'unit_price' => $netAmount,
                    'total' => $netAmount,
                ],
            ],
            'company_details' => array_merge(
                $this->companyDetailsSnapshot($settings, $company),
                [
                    'tax_rate' => $taxRate,
                    'payment_terms_days' => (int) $settings->payment_terms_days,
                ]
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function companyDetailsSnapshot(InvoiceSetting $settings, ?Company $company): array
    {
        $location = $settings->location_id
            ? CompanyLocation::find($settings->location_id)
            : null;

        if ($location) {
            $address = trim(implode(' ', array_filter([
                $location->street,
                $location->house_number,
                $location->house_number_extension,
            ])));

            return [
                'name' => $settings->company_name ?: $company?->name,
                'address' => $address ?: $settings->company_address,
                'postal_code' => $location->postal_code ?? $settings->company_postal_code,
                'city' => $location->city ?? $settings->company_city,
                'country' => $location->country ?? $settings->company_country,
                'email' => $location->email ?? $settings->company_email ?? $company?->email,
                'phone' => $location->phone ?? $settings->company_phone ?? $company?->phone,
                'vat_number' => $settings->company_vat_number ?? $company?->kvk_number,
                'footer_text' => $settings->invoice_footer_text,
                'payment_terms_days' => (int) $settings->payment_terms_days,
            ];
        }

        return [
            'name' => $settings->company_name ?: $company?->name,
            'address' => $settings->company_address,
            'postal_code' => $settings->company_postal_code,
            'city' => $settings->company_city,
            'country' => $settings->company_country,
            'email' => $settings->company_email ?? $company?->email,
            'phone' => $settings->company_phone ?? $company?->phone,
            'vat_number' => $settings->company_vat_number ?? $company?->kvk_number,
            'footer_text' => $settings->invoice_footer_text,
            'payment_terms_days' => (int) $settings->payment_terms_days,
        ];
    }

    protected function sendInvoiceEmail(Invoice $invoice, string $pdfBytes): void
    {
        $companyId = (int) $invoice->company_id;
        $template = EmailTemplate::query()
            ->where('type', 'invoice')
            ->where('is_active', true)
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            })
            ->orderByDesc('company_id')
            ->first();

        $company = Company::find($companyId);
        $details = is_array($invoice->company_details) ? $invoice->company_details : [];
        $companyName = $details['name'] ?? ($company->name ?? '');

        $variables = array_merge([
            'CUSTOMER_NAME' => $invoice->customer_name ?? 'klant',
            'CUSTOMER_EMAIL' => $invoice->customer_email ?? '',
            'INVOICE_NUMBER' => $invoice->invoice_number,
            'INVOICE_DATE' => $invoice->invoice_date?->format('d-m-Y') ?? '',
            'COMPANY_NAME' => $companyName,
            'COMPANY_ADDRESS' => trim(($details['address'] ?? '')."\n".($details['postal_code'] ?? '').' '.($details['city'] ?? '')),
        ], $this->companyLogos->templateVariable($companyId, $companyName), $this->invoiceAmountTemplateVariables($invoice));

        if ($template) {
            $subject = $this->emailTemplates->parseTemplateVariables($template->subject, $variables);
            $htmlContent = $this->emailTemplates->parseTemplateVariables($template->html_content, $variables);
            $textContent = $template->text_content
                ? $this->emailTemplates->parseTemplateVariables($template->text_content, $variables)
                : strip_tags($htmlContent);
        } else {
            $subject = 'Factuur '.$invoice->invoice_number;
            $htmlContent = '<p>Beste '.e($variables['CUSTOMER_NAME']).',</p>'
                .'<p>In de bijlage vindt u factuur <strong>'.e($invoice->invoice_number).'</strong> van '
                .e($variables['INVOICE_DATE']).'.</p>'
                .$variables['INVOICE_AMOUNTS_HTML']
                .'<p>Met vriendelijke groet,<br>'.e($variables['COMPANY_NAME']).'</p>';
            $textContent = strip_tags($htmlContent);
        }

        $toEmail = $invoice->customer_email;
        $toName = $invoice->customer_name ?? $toEmail;

        $this->env->applyMailConfigToRuntime();
        $from = $this->env->resolveMailFromHeaders();
        $companyReplyTo = trim((string) ($details['email'] ?? ''));

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
                $details,
                $companyId,
                $companyName
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
                    $htmlBody = $this->companyLogos->embedInHtml(
                        $htmlContent,
                        $message,
                        $companyId,
                        $companyName
                    );
                    $message->html($htmlBody);
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
            if ($this->isSmtpNotAuthorizedError($e)) {
                throw ValidationException::withMessages([
                    'invoice' => [
                        'De mailserver weigert verzending: SMTP-gebruiker ('.$from['smtp_username'].') mag niet verzenden namens '
                        .$this->env->get('MAIL_FROM_ADDRESS', config('mail.from.address')).'. Pas Mail Server Instellingen aan (From-adres of SMTP-gebruiker).',
                    ],
                ]);
            }

            throw $e;
        }
    }

    protected function isSmtpNotAuthorizedError(\Throwable $e): bool
    {
        return str_contains($e->getMessage(), 'not authorized to send on behalf of')
            || str_contains($e->getMessage(), '550 5.7.1');
    }

    /**
     * @return array<string, string>
     */
    protected function invoiceAmountTemplateVariables(Invoice $invoice): array
    {
        $details = is_array($invoice->company_details) ? $invoice->company_details : [];
        $taxRate = (float) ($details['tax_rate'] ?? 21);
        $excl = (float) $invoice->amount;
        $tax = (float) $invoice->tax_amount;
        $total = (float) $invoice->total_amount;
        $taxRateLabel = $this->formatTaxRateLabel($taxRate);

        return [
            'INVOICE_AMOUNT_EXCL' => $this->formatEuro($excl),
            'INVOICE_TAX_AMOUNT' => $this->formatEuro($tax),
            'INVOICE_TAX_RATE' => (string) (int) round($taxRate),
            'INVOICE_TAX_LABEL' => 'BTW ('.$taxRateLabel.')',
            'INVOICE_TOTAL' => $this->formatEuro($total),
            'INVOICE_AMOUNTS_HTML' => $this->invoiceAmountsHtmlBlock($excl, $tax, $total, $taxRate),
            'INVOICE_AMOUNTS_TEXT' => $this->invoiceAmountsTextBlock($excl, $tax, $total, $taxRate),
        ];
    }

    protected function isGdExtensionMissing(\Throwable $e): bool
    {
        return str_contains($e->getMessage(), 'PHP GD extension is required')
            || ! extension_loaded('gd');
    }

    protected function formatEuro(float $amount): string
    {
        return '€ '.number_format($amount, 2, ',', '.');
    }

    protected function formatTaxRateLabel(float $taxRate): string
    {
        $rounded = round($taxRate, 2);
        if (abs($rounded - round($rounded)) < 0.001) {
            return (string) (int) round($rounded).'%';
        }

        return number_format($rounded, 2, ',', '.').'%';
    }

    protected function invoiceAmountsHtmlBlock(float $excl, float $tax, float $total, float $taxRate): string
    {
        $taxLabel = 'BTW ('.$this->formatTaxRateLabel($taxRate).')';

        return '<table role="presentation" style="width:100%;max-width:320px;margin:16px 0;border-collapse:collapse;font-size:14px;">'
            .$this->invoiceAmountRowHtml('Bedrag excl. BTW', $excl)
            .$this->invoiceAmountRowHtml($taxLabel, $tax)
            .$this->invoiceAmountRowHtml('Totaalbedrag', $total, true)
            .'</table>';
    }

    protected function invoiceAmountRowHtml(string $label, float $amount, bool $emphasize = false): string
    {
        $weight = $emphasize ? 'font-weight:700;border-top:1px solid #cbd5e1;padding-top:10px;' : '';
        $formatted = number_format($amount, 2, ',', '.');

        return '<tr>'
            .'<td style="text-align:left;padding:4px 12px 4px 0;vertical-align:top;'.$weight.'">'.e($label).'</td>'
            .'<td style="width:18px;text-align:right;padding:4px 2px 4px 0;vertical-align:top;white-space:nowrap;'.$weight.'">€</td>'
            .'<td style="width:88px;text-align:right;padding:4px 0;vertical-align:top;white-space:nowrap;'.$weight.'">'.$formatted.'</td>'
            .'</tr>';
    }

    protected function invoiceAmountsTextBlock(float $excl, float $tax, float $total, float $taxRate): string
    {
        $taxLabel = 'BTW ('.$this->formatTaxRateLabel($taxRate).')';

        return "Bedrag excl. BTW: {$this->formatEuro($excl)}\n"
            ."{$taxLabel}: {$this->formatEuro($tax)}\n"
            ."Totaalbedrag: {$this->formatEuro($total)}";
    }
}
