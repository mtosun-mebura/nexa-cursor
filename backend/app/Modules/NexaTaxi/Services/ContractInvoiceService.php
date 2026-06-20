<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\EmailTemplate;
use App\Models\Invoice;
use App\Models\InvoiceSetting;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\TransportContract;
use App\Modules\NexaTaxi\Models\TransportCustomer;
use App\Modules\NexaTaxi\Models\TransportOccurrence;
use App\Modules\NexaTaxi\Models\TransportPaymentMandate;
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
use App\Services\CompanyEmailLogoService;
use App\Services\EmailTemplateService;
use App\Services\EnvService;
use App\Services\InvoicePdfService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ContractInvoiceService
{
    public function __construct(
        protected InvoicePdfService $pdf,
        protected EmailTemplateService $emailTemplates,
        protected EnvService $env,
        protected CompanyEmailLogoService $companyLogos,
    ) {}

    public function findInvoiceForContractPeriod(int $contractId, string $period): ?Invoice
    {
        return Invoice::query()
            ->where('module', Invoice::MODULE_TAXI_CONTRACT)
            ->where('module_reference_id', $contractId)
            ->where('billing_period', $period)
            ->first();
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function invoicesForContract(int $contractId): Collection
    {
        return Invoice::query()
            ->where('module', Invoice::MODULE_TAXI_CONTRACT)
            ->where('module_reference_id', $contractId)
            ->orderByDesc('billing_period')
            ->get();
    }

    /**
     * @param  array{send_email?: bool, generate_pdf?: bool, status?: string}  $options
     */
    public function generateMonthlyInvoice(
        string $conn,
        TransportContract $contract,
        string $period,
        array $options = [],
    ): Invoice {
        if (! preg_match('/^\d{4}-\d{2}$/', $period)) {
            throw ValidationException::withMessages([
                'period' => ['Ongeldige factuurperiode. Gebruik YYYY-MM.'],
            ]);
        }

        $existing = $this->findInvoiceForContractPeriod($contract->id, $period);
        if ($existing) {
            throw ValidationException::withMessages([
                'period' => ['Er bestaat al een factuur voor deze periode.'],
            ]);
        }

        $customer = TransportCustomer::on($conn)->find($contract->transport_customer_id);
        if (! $customer) {
            throw ValidationException::withMessages([
                'contract' => ['Contractklant niet gevonden.'],
            ]);
        }

        $rideStats = $this->completedRideStatsForPeriod($conn, $contract, $period);
        $lineItems = $this->buildLineItems($contract, $period, $rideStats);
        $netAmount = round(collect($lineItems)->sum(fn (array $line) => (float) ($line['total'] ?? 0)), 2);

        if ($netAmount < 0.01) {
            throw ValidationException::withMessages([
                'amount' => ['Geen factuurbedrag voor deze periode. Controleer tarief en afgeronde ritten.'],
            ]);
        }

        $taxRate = (float) $contract->tax_rate;
        $taxAmount = round($netAmount * ($taxRate / 100), 2);
        $totalAmount = round($netAmount + $taxAmount, 2);

        $companyId = (int) $contract->company_id;
        $settings = InvoiceSetting::getSettingsForCompany($companyId);
        $invoiceDate = now();
        $dueDate = $invoiceDate->copy()->addDays((int) $contract->payment_terms_days);

        $mandate = TransportPaymentMandate::on($conn)
            ->where('transport_contract_id', $contract->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        $notes = $this->buildInvoiceNotes($mandate);
        $status = $options['status'] ?? 'draft';

        return DB::transaction(function () use (
            $contract,
            $customer,
            $companyId,
            $settings,
            $period,
            $lineItems,
            $netAmount,
            $taxAmount,
            $totalAmount,
            $taxRate,
            $invoiceDate,
            $dueDate,
            $notes,
            $status,
            $options,
            $mandate,
        ) {
            $company = Company::find($companyId);
            $invoiceNumber = $settings->generateInvoiceNumber();

            $invoice = Invoice::query()->create([
                'invoice_number' => $invoiceNumber,
                'company_id' => $companyId,
                'module' => Invoice::MODULE_TAXI_CONTRACT,
                'module_reference_id' => $contract->id,
                'billing_period' => $period,
                'customer_name' => $customer->name,
                'customer_email' => $customer->contact_email,
                'amount' => $netAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'currency' => 'EUR',
                'status' => $status,
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'line_items' => $lineItems,
                'notes' => $notes,
                'company_details' => array_merge(
                    $this->companyDetailsSnapshot($settings, $company),
                    [
                        'tax_rate' => $taxRate,
                        'payment_terms_days' => (int) $contract->payment_terms_days,
                        'contract_name' => $contract->name,
                        'billing_period' => $period,
                        'debtor_number' => $customer->debtor_number,
                        'sepa_incasso' => $mandate && $mandate->status === 'active',
                    ]
                ),
            ]);

            $invoice = $invoice->fresh();

            if ($options['generate_pdf'] ?? true) {
                $this->pdf->generateAndStore($invoice);
                $invoice = $invoice->fresh();
            }

            if ($options['send_email'] ?? false) {
                $this->sendInvoiceToCustomer($invoice);
            }

            return $invoice->fresh();
        });
    }

    public function sendInvoiceToCustomer(Invoice $invoice): Invoice
    {
        if (! $invoice->customer_email) {
            throw ValidationException::withMessages([
                'email' => ['Geen e-mailadres voor de contractklant.'],
            ]);
        }

        $pdfBytes = $invoice->pdf_path
            ? $this->pdf->renderPdfBytes($invoice)
            : $this->pdf->generateAndStore($invoice->fresh())['bytes'];

        $invoice->update(['status' => 'sent']);
        $this->sendInvoiceEmail($invoice->fresh(), $pdfBytes);

        return $invoice->fresh();
    }

    public function markInvoicePaid(Invoice $invoice, ?Carbon $paidDate = null): Invoice
    {
        if ($invoice->module !== Invoice::MODULE_TAXI_CONTRACT) {
            throw ValidationException::withMessages([
                'invoice' => ['Alleen contractfacturen kunnen hier worden afgehandeld.'],
            ]);
        }

        $invoice->update([
            'status' => 'paid',
            'paid_date' => $paidDate ?? now(),
        ]);

        return $invoice->fresh();
    }

    public function deleteDraftInvoice(Invoice $invoice): void
    {
        if ($invoice->module !== Invoice::MODULE_TAXI_CONTRACT) {
            throw ValidationException::withMessages([
                'invoice' => ['Alleen contractfacturen kunnen hier worden verwijderd.'],
            ]);
        }

        if ($invoice->status !== 'draft') {
            throw ValidationException::withMessages([
                'invoice' => ['Alleen conceptfacturen kunnen worden verwijderd. Verzonden of betaalde facturen blijven bewaard.'],
            ]);
        }

        DB::transaction(function () use ($invoice) {
            if ($invoice->pdf_path) {
                Storage::disk('local')->delete($invoice->pdf_path);
            }

            $invoice->delete();
        });
    }

    /**
     * @return array{group_rides: int, individual_rides: int, total_rides: int}
     */
    public function completedRideStatsForPeriod(
        string $conn,
        TransportContract $contract,
        string $period,
    ): array {
        [$start, $end] = $this->periodBounds($period);

        $occurrences = TransportOccurrence::on($conn)
            ->where('transport_contract_id', $contract->id)
            ->whereBetween('scheduled_date', [$start->toDateString(), $end->toDateString()])
            ->whereNotNull('ride_request_id')
            ->with('rideRequest')
            ->get();

        $group = 0;
        $individual = 0;

        foreach ($occurrences as $occurrence) {
            $ride = $occurrence->rideRequest;
            if (! $ride || $ride->status !== RideRequest::STATUS_COMPLETED) {
                continue;
            }

            if ($ride->ride_type === RideRequest::RIDE_TYPE_CONTRACT_GROUP) {
                $group++;
            } elseif ($ride->ride_type === RideRequest::RIDE_TYPE_CONTRACT_INDIVIDUAL) {
                $individual++;
            }
        }

        return [
            'group_rides' => $group,
            'individual_rides' => $individual,
            'total_rides' => $group + $individual,
        ];
    }

    /**
     * @param  array{group_rides: int, individual_rides: int, total_rides: int}  $rideStats
     * @return array<int, array<string, mixed>>
     */
    public function buildLineItems(TransportContract $contract, string $period, array $rideStats): array
    {
        $periodLabel = $this->periodLabel($period);
        $lines = [];

        if (in_array($contract->billing_model, ['fixed_monthly', 'hybrid'], true)) {
            $monthly = round((float) ($contract->monthly_amount ?? 0), 2);
            if ($monthly > 0) {
                $lines[] = [
                    'description' => 'Contractvervoer '.$periodLabel."\n"
                        .$this->buildContractSummaryText($contract, $period, $rideStats),
                    'quantity' => 1,
                    'unit_price' => $monthly,
                    'total' => $monthly,
                ];
            }
        }

        if (in_array($contract->billing_model, ['per_ride', 'hybrid'], true)) {
            $pricePerRide = round((float) ($contract->price_per_ride ?? 0), 2);
            $totalRides = (int) ($rideStats['total_rides'] ?? 0);

            if ($pricePerRide > 0 && $totalRides > 0) {
                $lines[] = [
                    'description' => 'Afgeronde contractritten '.$periodLabel
                        .' ('.$rideStats['group_rides'].' groep, '.$rideStats['individual_rides'].' individueel)',
                    'quantity' => $totalRides,
                    'unit_price' => $pricePerRide,
                    'total' => round($totalRides * $pricePerRide, 2),
                ];
            }
        }

        return $lines;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function periodBounds(string $period): array
    {
        $start = Carbon::createFromFormat('Y-m-d', $period.'-01', ContractTransportTimezone::TIMEZONE)->startOfDay();
        $end = $start->copy()->endOfMonth();

        return [$start, $end];
    }

    public function previousBillingPeriod(?Carbon $reference = null): string
    {
        $date = ($reference ?? now(ContractTransportTimezone::TIMEZONE))->copy()->subMonth();

        return $date->format('Y-m');
    }

    /**
     * @return Collection<int, TransportContract>
     */
    public function contractsDueForInvoicingToday(string $conn, ?Carbon $today = null): Collection
    {
        $today = ($today ?? now(ContractTransportTimezone::TIMEZONE))->copy();
        $day = (int) $today->day;

        return TransportContract::on($conn)
            ->where('status', 'active')
            ->where('invoice_day', $day)
            ->get();
    }

    /**
     * @param  array{group_rides: int, individual_rides: int, total_rides: int}  $rideStats
     */
    protected function buildContractSummaryText(
        TransportContract $contract,
        string $period,
        array $rideStats,
    ): string {
        return implode("\n", [
            'Abonnement: '.$contract->name,
            'Periode: '.$this->periodLabel($period),
            'Afgeronde ritten: '.$rideStats['total_rides']
                .' ('.$rideStats['group_rides'].' groep, '.$rideStats['individual_rides'].' individueel)',
        ]);
    }

    protected function buildInvoiceNotes(?TransportPaymentMandate $mandate): string
    {
        if (! $mandate || $mandate->status !== 'active') {
            return '';
        }

        return 'Betaling via SEPA-automatische incasso (mandaat '
            .($mandate->mandate_reference ?: 'actief').').';
    }

    protected function periodLabel(string $period): string
    {
        $date = Carbon::createFromFormat('Y-m-d', $period.'-01', ContractTransportTimezone::TIMEZONE);

        return $date->locale('nl')->translatedFormat('F Y');
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
        ) {
            $message->to($toEmail, $toName)
                ->subject($subject)
                ->from($from['from_address'], $from['from_name']);

            if ($companyReplyTo !== '' && filter_var($companyReplyTo, FILTER_VALIDATE_EMAIL)) {
                $message->replyTo($companyReplyTo, (string) ($details['name'] ?? ''));
            }

            $message->html($htmlContent);
            $message->text($textContent);
            $message->attachData($pdfBytes, 'factuur-'.$invoice->invoice_number.'.pdf', [
                'mime' => 'application/pdf',
            ]);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function invoiceAmountTemplateVariables(Invoice $invoice): array
    {
        $fmt = fn (float $n) => number_format($n, 2, ',', '.');

        return [
            'INVOICE_TOTAL' => '€ '.$fmt((float) $invoice->total_amount),
            'INVOICE_AMOUNT_EXCL' => '€ '.$fmt((float) $invoice->amount),
            'INVOICE_TAX' => '€ '.$fmt((float) $invoice->tax_amount),
            'INVOICE_AMOUNTS_HTML' => '<p>Totaalbedrag: <strong>€ '.$fmt((float) $invoice->total_amount).'</strong></p>',
        ];
    }
}
