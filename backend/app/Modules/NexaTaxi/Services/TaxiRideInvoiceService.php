<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\EmailTemplate;
use App\Models\Invoice;
use App\Models\InvoiceSetting;
use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\Vehicle;
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
        if ($ride->requiresPerLegDriverPayment()) {
            return null;
        }

        if ($ride->payment_status !== RideRequest::PAYMENT_STATUS_PAID) {
            return null;
        }

        $companyId = $this->resolveCompanyIdForRide($ride);
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

    public function ensureInvoiceForLeg(
        string $conn,
        RideRequest $ride,
        string $billingPeriod,
        bool $generatePdf = false
    ): ?Invoice {
        $companyId = $this->resolveCompanyIdForRide($ride);
        if ($companyId <= 0) {
            return null;
        }

        $existing = $this->findInvoiceForRide($ride, $billingPeriod);
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($conn, $ride, $companyId, $billingPeriod, $generatePdf) {
            $ride = RideRequest::on($conn)->whereKey($ride->id)->lockForUpdate()->firstOrFail();
            $existing = $this->findInvoiceForRide($ride, $billingPeriod);
            if ($existing) {
                return $existing;
            }

            $amounts = $ride->splitReturnTripLegAmounts();
            $invoice = match ($billingPeriod) {
                RideRequest::INVOICE_BILLING_HEEN => $this->createLegInvoiceFromRide(
                    $ride,
                    $companyId,
                    RideRequest::INVOICE_BILLING_HEEN,
                    $amounts['outbound']
                ),
                RideRequest::INVOICE_BILLING_TERUG => $this->createLegInvoiceFromRide(
                    $ride,
                    $companyId,
                    RideRequest::INVOICE_BILLING_TERUG,
                    $amounts['return']
                ),
                RideRequest::INVOICE_BILLING_TOTAAL => $this->createReturnTripSummaryInvoice($ride, $companyId),
                default => null,
            };

            if (! $invoice) {
                return null;
            }

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

            $companyId = $this->resolveCompanyIdForRide($ride);
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

    public function findInvoiceForRide(RideRequest $ride, ?string $billingPeriod = null): ?Invoice
    {
        if ($billingPeriod !== null) {
            return Invoice::query()
                ->where('module', Invoice::MODULE_TAXI)
                ->where('module_reference_id', $ride->id)
                ->where('billing_period', $billingPeriod)
                ->first();
        }

        if ($ride->invoice_id) {
            $byId = Invoice::query()->find($ride->invoice_id);
            if ($byId) {
                return $byId;
            }
        }

        return Invoice::query()
            ->where('module', Invoice::MODULE_TAXI)
            ->where('module_reference_id', $ride->id)
            ->where(function ($query) {
                $query->whereNull('billing_period')->orWhere('billing_period', '');
            })
            ->first();
    }

    public function resolveSendableInvoiceBillingPeriod(RideRequest $ride): ?string
    {
        if ($ride->requiresPerLegDriverPayment()) {
            if ($ride->returnPaidAmount() !== null) {
                $returnInvoice = $this->findInvoiceForRide($ride, RideRequest::INVOICE_BILLING_TERUG);
                if (! $returnInvoice || $returnInvoice->status !== 'sent') {
                    return RideRequest::INVOICE_BILLING_TERUG;
                }
            }

            if ($ride->outboundPaidAmount() !== null) {
                $outboundInvoice = $this->findInvoiceForRide($ride, RideRequest::INVOICE_BILLING_HEEN);
                if (! $outboundInvoice || $outboundInvoice->status !== 'sent') {
                    return RideRequest::INVOICE_BILLING_HEEN;
                }
            }

            return null;
        }

        if ($ride->payment_status !== RideRequest::PAYMENT_STATUS_PAID) {
            return null;
        }

        $invoice = $this->findInvoiceForRide($ride);
        if ($invoice && $invoice->status === 'sent') {
            return null;
        }

        return '';
    }

    /**
     * @return array<string, mixed>
     */
    public function driverInvoicePayload(RideRequest $ride): array
    {
        $conn = $ride->getConnectionName();
        $sendableLeg = $this->resolveSendableInvoiceBillingPeriod($ride);
        $billingPeriod = $sendableLeg === '' ? null : $sendableLeg;

        $invoice = $billingPeriod !== null
            ? $this->findInvoiceForRide($ride, $billingPeriod)
            : $this->findInvoiceForRide($ride);

        if (! $invoice && $sendableLeg !== null) {
            try {
                if ($ride->requiresPerLegDriverPayment() && $sendableLeg !== '') {
                    $invoice = $this->ensureInvoiceForLeg($conn, $ride->fresh(), $sendableLeg, false);
                } elseif (! $ride->requiresPerLegDriverPayment()) {
                    $invoice = $this->ensureInvoiceForPaidRide($conn, $ride->fresh(), false);
                }
                if ($invoice) {
                    $ride = $ride->fresh();
                    $invoice = ($billingPeriod !== null
                        ? $this->findInvoiceForRide($ride, $billingPeriod)
                        : $this->findInvoiceForRide($ride)) ?? $invoice;
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $outboundInvoice = $ride->requiresPerLegDriverPayment()
            ? $this->findInvoiceForRide($ride, RideRequest::INVOICE_BILLING_HEEN)
            : null;
        $returnInvoice = $ride->requiresPerLegDriverPayment()
            ? $this->findInvoiceForRide($ride, RideRequest::INVOICE_BILLING_TERUG)
            : null;

        return [
            'has_invoice' => $invoice !== null,
            'invoice_id' => $invoice?->id,
            'invoice_number' => $invoice?->invoice_number,
            'customer_email' => $invoice?->customer_email ?? $ride->customer_email,
            'customer_name' => $invoice?->customer_name ?? $ride->customer_name,
            'total_amount' => $invoice ? (float) $invoice->total_amount : null,
            'invoice_sent' => $invoice?->status === 'sent',
            'invoice_leg' => $sendableLeg === '' ? null : $sendableLeg,
            'invoice_leg_label' => $ride->invoiceLegLabelForBillingPeriod($sendableLeg === '' ? null : $sendableLeg),
            'outbound_invoice_sent' => $outboundInvoice?->status === 'sent',
            'return_invoice_sent' => $returnInvoice?->status === 'sent',
            'includes_total_invoice' => $sendableLeg === RideRequest::INVOICE_BILLING_TERUG
                && $ride->returnPaidAmount() !== null,
            'can_send' => $sendableLeg !== null && $invoice?->status !== 'sent',
        ];
    }

    public function sendInvoiceToCustomer(
        string $conn,
        RideRequest $ride,
        string $email,
        ?string $invoiceNumber = null
    ): Invoice {
        $sendableLeg = $this->resolveSendableInvoiceBillingPeriod($ride);
        if ($sendableLeg === null) {
            throw ValidationException::withMessages([
                'invoice' => ['Er is momenteel geen factuur beschikbaar om te versturen.'],
            ]);
        }

        $email = trim($email);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => ['Vul een geldig e-mailadres in.'],
            ]);
        }

        if ($ride->requiresPerLegDriverPayment()) {
            $invoice = $this->ensureInvoiceForLeg($conn, $ride, $sendableLeg, false);
        } else {
            $invoice = $this->ensureInvoiceForPaidRide($conn, $ride, false);
        }

        if (! $invoice) {
            throw ValidationException::withMessages([
                'invoice' => ['Factuur kon niet worden aangemaakt.'],
            ]);
        }

        return DB::transaction(function () use ($conn, $ride, $invoice, $email, $invoiceNumber, $sendableLeg) {
            $invoice = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            $submittedNumber = $invoiceNumber !== null ? trim($invoiceNumber) : '';
            if ($submittedNumber !== '' && $submittedNumber !== $invoice->invoice_number) {
                if (Invoice::query()->where('invoice_number', $submittedNumber)->where('id', '!=', $invoice->id)->exists()) {
                    throw ValidationException::withMessages([
                        'invoice_number' => ['Dit factuurnummer bestaat al.'],
                    ]);
                }
                $invoice->update(['invoice_number' => $submittedNumber]);
            }

            $invoice->update([
                'customer_email' => $email,
                'status' => 'sent',
            ]);

            $invoice = $invoice->fresh();

            $extraAttachments = [];
            $summaryInvoice = null;

            if ($sendableLeg === RideRequest::INVOICE_BILLING_TERUG && $ride->returnPaidAmount() !== null) {
                $summaryInvoice = $this->ensureInvoiceForLeg(
                    $conn,
                    $ride->fresh(),
                    RideRequest::INVOICE_BILLING_TOTAAL,
                    false
                );
                if ($summaryInvoice) {
                    $summaryInvoice->update([
                        'customer_email' => $email,
                        'status' => 'sent',
                    ]);
                    $summaryInvoice = $summaryInvoice->fresh();
                }
            }

            try {
                $pdf = $this->pdf->generateAndStore($invoice);
                if ($summaryInvoice) {
                    $summaryPdf = $this->pdf->generateAndStore($summaryInvoice);
                    $extraAttachments[] = [
                        'bytes' => $summaryPdf['bytes'],
                        'filename' => 'totaalfactuur-'.$summaryInvoice->invoice_number.'.pdf',
                    ];
                }
            } catch (\Throwable $e) {
                if ($this->isGdExtensionMissing($e)) {
                    throw ValidationException::withMessages([
                        'invoice' => ['PDF kon niet worden gemaakt: installeer de PHP-extensie gd (bijv. pecl install gd).'],
                    ]);
                }
                throw $e;
            }

            $this->sendInvoiceEmail($invoice, $pdf['bytes'], $extraAttachments);

            return $invoice;
        });
    }

    protected function createInvoiceFromRide(RideRequest $ride, int $companyId): Invoice
    {
        $amount = round((float) ($ride->final_price ?? $ride->quoted_price ?? 0), 2);
        if ($amount < 0.01) {
            throw ValidationException::withMessages([
                'amount' => ['Geen geldig factuurbedrag voor deze rit.'],
            ]);
        }

        $description = 'Taxirit';
        if ($ride->pickup_address && $ride->dropoff_address) {
            $description .= ': '.$ride->pickup_address.' → '.$ride->dropoff_address;
        }

        return $this->createTaxiInvoice(
            $ride,
            $companyId,
            $amount,
            $description,
            null,
            null
        );
    }

    protected function createLegInvoiceFromRide(
        RideRequest $ride,
        int $companyId,
        string $billingPeriod,
        float $grossAmount
    ): Invoice {
        $amount = round($grossAmount, 2);
        if ($amount < 0.01) {
            throw ValidationException::withMessages([
                'amount' => ['Geen geldig factuurbedrag voor deze rit.'],
            ]);
        }

        $legLabel = $ride->invoiceLegLabelForBillingPeriod($billingPeriod) ?? 'Rit';
        $route = $this->legRouteDescription($ride, $billingPeriod);

        return $this->createTaxiInvoice(
            $ride,
            $companyId,
            $amount,
            'Taxirit '.strtolower($legLabel).': '.$route,
            $billingPeriod,
            'Factuur '.$legLabel
        );
    }

    protected function createReturnTripSummaryInvoice(RideRequest $ride, int $companyId): Invoice
    {
        $amounts = $ride->splitReturnTripLegAmounts();
        $outboundGross = $amounts['outbound'];
        $returnGross = $amounts['return'];
        $totalGross = round((float) ($ride->quoted_price ?? ($outboundGross + $returnGross)), 2);
        if ($outboundGross < 0.01 || $returnGross < 0.01 || $totalGross < 0.01) {
            throw ValidationException::withMessages([
                'amount' => ['Heen- en terugrit moeten betaald zijn voor een totaalfactuur.'],
            ]);
        }

        $settings = InvoiceSetting::getSettingsForCompany($companyId);
        $taxRate = (float) $settings->default_tax_rate;
        $outboundNet = $this->grossToNetAmount($outboundGross, $taxRate);
        $returnNet = $this->grossToNetAmount($returnGross, $taxRate);
        $taxAmount = round($totalGross * ($taxRate / (100 + $taxRate)), 2);
        $netAmount = round($totalGross - $taxAmount, 2);
        $invoiceDate = now();
        $dueDate = $invoiceDate->copy()->addDays((int) $settings->payment_terms_days);
        $company = Company::find($companyId);

        $outboundRoute = $this->legRouteDescription($ride, RideRequest::INVOICE_BILLING_HEEN);
        $returnRoute = $this->legRouteDescription($ride, RideRequest::INVOICE_BILLING_TERUG);

        return Invoice::query()->create([
            'invoice_number' => $settings->generateInvoiceNumber(),
            'company_id' => $companyId,
            'module' => Invoice::MODULE_TAXI,
            'module_reference_id' => $ride->id,
            'billing_period' => RideRequest::INVOICE_BILLING_TOTAAL,
            'customer_name' => $ride->customer_name,
            'customer_email' => $ride->customer_email,
            'amount' => $netAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalGross,
            'currency' => 'EUR',
            'status' => 'paid',
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'paid_date' => $invoiceDate,
            'line_items' => [
                [
                    'description' => 'Heenrit: '.$outboundRoute,
                    'quantity' => 1,
                    'unit_price' => $outboundNet,
                    'total' => $outboundNet,
                ],
                [
                    'description' => 'Terugrit: '.$returnRoute,
                    'quantity' => 1,
                    'unit_price' => $returnNet,
                    'total' => $returnNet,
                ],
            ],
            'company_details' => array_merge(
                $this->companyDetailsSnapshot($settings, $company),
                [
                    'tax_rate' => $taxRate,
                    'payment_terms_days' => (int) $settings->payment_terms_days,
                    'invoice_title' => 'Totaalfactuur',
                    'taxi_return_summary' => true,
                ]
            ),
            'notes' => 'Totaalfactuur retourrit (heen- en terugrit).',
        ]);
    }

    protected function createTaxiInvoice(
        RideRequest $ride,
        int $companyId,
        float $grossAmount,
        string $description,
        ?string $billingPeriod,
        ?string $invoiceTitle
    ): Invoice {
        $settings = InvoiceSetting::getSettingsForCompany($companyId);
        $company = Company::find($companyId);
        $taxRate = (float) $settings->default_tax_rate;
        $taxAmount = round($grossAmount * ($taxRate / (100 + $taxRate)), 2);
        $netAmount = round($grossAmount - $taxAmount, 2);
        $invoiceDate = now();
        $dueDate = $invoiceDate->copy()->addDays((int) $settings->payment_terms_days);

        $companyDetails = array_merge(
            $this->companyDetailsSnapshot($settings, $company),
            [
                'tax_rate' => $taxRate,
                'payment_terms_days' => (int) $settings->payment_terms_days,
            ]
        );
        if ($invoiceTitle) {
            $companyDetails['invoice_title'] = $invoiceTitle;
        }

        return Invoice::query()->create([
            'invoice_number' => $settings->generateInvoiceNumber(),
            'company_id' => $companyId,
            'module' => Invoice::MODULE_TAXI,
            'module_reference_id' => $ride->id,
            'billing_period' => $billingPeriod,
            'customer_name' => $ride->customer_name,
            'customer_email' => $ride->customer_email,
            'amount' => $netAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $grossAmount,
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
            'company_details' => $companyDetails,
        ]);
    }

    protected function legRouteDescription(RideRequest $ride, string $billingPeriod): string
    {
        $pickup = trim((string) ($ride->pickup_address ?? ''));
        $dropoff = trim((string) ($ride->dropoff_address ?? ''));

        if ($pickup === '' || $dropoff === '') {
            return '—';
        }

        if ($billingPeriod === RideRequest::INVOICE_BILLING_TERUG) {
            return $dropoff.' → '.$pickup;
        }

        return $pickup.' → '.$dropoff;
    }

    protected function grossToNetAmount(float $grossAmount, float $taxRate): float
    {
        $taxAmount = round($grossAmount * ($taxRate / (100 + $taxRate)), 2);

        return round($grossAmount - $taxAmount, 2);
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
            'payment_terms_days' => (int) $settings->payment_terms_days,
        ];
    }

    /**
     * @param  list<array{bytes: string, filename: string}>  $extraAttachments
     */
    protected function sendInvoiceEmail(Invoice $invoice, string $pdfBytes, array $extraAttachments = []): void
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
            $attachmentNote = $extraAttachments !== []
                ? '<p>Bij deze e-mail vindt u de factuur van de terugrit en een totaalfactuur met het gecombineerde bedrag van heen- en terugrit.</p>'
                : '';
            $subject = 'Factuur '.$invoice->invoice_number;
            $htmlContent = '<p>Beste '.e($variables['CUSTOMER_NAME']).',</p>'
                .'<p>In de bijlage vindt u factuur <strong>'.e($invoice->invoice_number).'</strong> van '
                .e($variables['INVOICE_DATE']).'.</p>'
                .$attachmentNote
                .($variables['INVOICE_PAID_NOTICE_HTML'] ?? '')
                .$variables['INVOICE_AMOUNTS_HTML']
                .'<p>Met vriendelijke groet,<br>'.e($variables['COMPANY_NAME']).'</p>';
            $textContent = ($variables['INVOICE_PAID_NOTICE_TEXT'] ?? '')
                ."\n\n"
                .($variables['INVOICE_AMOUNTS_TEXT'] ?? strip_tags($htmlContent));
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
                $extraAttachments,
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
                foreach ($extraAttachments as $attachment) {
                    $message->attachData(
                        $attachment['bytes'],
                        $attachment['filename'],
                        ['mime' => 'application/pdf']
                    );
                }
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
            'INVOICE_AMOUNTS_HTML' => $this->invoiceAmountsHtmlBlock($excl, $tax, $total, $taxRate, $invoice),
            'INVOICE_AMOUNTS_TEXT' => $this->invoiceAmountsTextBlock($excl, $tax, $total, $taxRate, $invoice),
            'INVOICE_PAID_NOTICE_HTML' => $this->invoicePaidNoticeHtml($invoice),
            'INVOICE_PAID_NOTICE_TEXT' => $this->invoicePaidNoticeText($invoice),
        ];
    }

    protected function invoicePaidNoticeHtml(Invoice $invoice): string
    {
        if (! $invoice->isPaid()) {
            return '';
        }

        $paidOn = $invoice->paid_date?->format('d-m-Y');
        $dateLine = $paidOn
            ? ' Betaald op <strong>'.e($paidOn).'</strong>.'
            : '';

        return '<p style="margin:16px 0;padding:12px 14px;background:#dcfce7;border:2px solid #16a34a;color:#14532d;font-size:14px;line-height:1.5;">'
            .'<strong>Betaling voldaan.</strong>'.$dateLine
            .' Er zijn geen openstaande bedragen op deze factuur.'
            .'</p>';
    }

    protected function invoicePaidNoticeText(Invoice $invoice): string
    {
        if (! $invoice->isPaid()) {
            return '';
        }

        $paidOn = $invoice->paid_date?->format('d-m-Y');
        $dateLine = $paidOn ? ' Betaald op '.$paidOn.'.' : '';

        return 'Betaling voldaan.'.$dateLine.' Er zijn geen openstaande bedragen op deze factuur.';
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

    protected function invoiceAmountsHtmlBlock(float $excl, float $tax, float $total, float $taxRate, ?Invoice $invoice = null): string
    {
        $taxLabel = 'BTW ('.$this->formatTaxRateLabel($taxRate).')';

        $html = '<table role="presentation" style="width:100%;max-width:320px;margin:16px 0;border-collapse:collapse;font-size:14px;">'
            .$this->invoiceAmountRowHtml('Bedrag excl. BTW', $excl)
            .$this->invoiceAmountRowHtml($taxLabel, $tax)
            .$this->invoiceAmountRowHtml('Totaalbedrag', $total, true)
            .'</table>';

        if ($invoice && $invoice->isPaid()) {
            $html .= $this->invoicePaidNoticeHtml($invoice);
        }

        return $html;
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

    protected function invoiceAmountsTextBlock(float $excl, float $tax, float $total, float $taxRate, ?Invoice $invoice = null): string
    {
        $taxLabel = 'BTW ('.$this->formatTaxRateLabel($taxRate).')';

        $text = "Bedrag excl. BTW: {$this->formatEuro($excl)}\n"
            ."{$taxLabel}: {$this->formatEuro($tax)}\n"
            ."Totaalbedrag: {$this->formatEuro($total)}";

        if ($invoice && $invoice->isPaid()) {
            $text .= "\n\n".$this->invoicePaidNoticeText($invoice);
        }

        return $text;
    }

    protected function resolveCompanyIdForRide(RideRequest $ride): int
    {
        if (! empty($ride->company_id) && (int) $ride->company_id > 0) {
            return (int) $ride->company_id;
        }

        if (! empty($ride->vehicle_id)) {
            $vehicle = $ride->relationLoaded('vehicle')
                ? $ride->vehicle
                : Vehicle::on($ride->getConnectionName())->find($ride->vehicle_id);
            if ($vehicle && ! empty($vehicle->company_id)) {
                return (int) $vehicle->company_id;
            }
        }

        if (! empty($ride->driver_id)) {
            $driver = $ride->relationLoaded('driver')
                ? $ride->driver
                : User::find($ride->driver_id);
            if ($driver && ! empty($driver->company_id)) {
                return (int) $driver->company_id;
            }
        }

        return 0;
    }
}
