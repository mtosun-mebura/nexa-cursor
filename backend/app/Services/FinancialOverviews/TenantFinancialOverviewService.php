<?php

namespace App\Services\FinancialOverviews;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceSetting;
use App\Models\Payment;
use App\Modules\NexaTaxi\Models\RidePayment;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiRideInvoiceService;
use App\Services\InvoicePdfService;
use App\Services\ModuleDatabaseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class TenantFinancialOverviewService
{
    public const REPORT_INVOICES = 'invoices';

    public const REPORT_INCOME = 'income';

    public const REPORT_VAT = 'vat';

    public const REPORT_AUDIT = 'audit';

    public const MAX_INVOICE_ZIP = 500;

    public function __construct(
        protected InvoicePdfService $invoicePdfs
    ) {}

    /**
     * @return array<string, array{label: string, description: string, packed: bool}>
     */
    public static function reportTypes(): array
    {
        return [
            self::REPORT_INVOICES => [
                'label' => 'Facturen',
                'description' => 'Alle factuur-PDF’s van de periode in één zipbestand: contractfacturen, ritfacturen en overige facturen van Mollie-betalingen.',
                'packed' => true,
            ],
            self::REPORT_INCOME => [
                'label' => 'Inkomsten',
                'description' => 'Eén PDF met ontvangen betalingen en omzet in de periode.',
                'packed' => false,
            ],
            self::REPORT_VAT => [
                'label' => 'BTW-overzicht',
                'description' => 'Omzet en btw per tarief, bruikbaar voor de aangifte omzetbelasting.',
                'packed' => false,
            ],
            self::REPORT_AUDIT => [
                'label' => 'Factuurregister',
                'description' => 'Chronologische lijst van facturen voor administratie en audit.',
                'packed' => false,
            ],
        ];
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function invoicesForPeriod(int $companyId, FinancialOverviewPeriod $period, bool $includeCancelled = false): Collection
    {
        $rideInvoices = $this->ensureRideInvoicesForMolliePeriod($companyId, $period);

        $query = Invoice::query()
            ->where('company_id', $companyId)
            ->where('status', '!=', 'draft')
            ->where(function ($builder) use ($period) {
                $builder->where(function ($dates) use ($period) {
                    $dates->whereDate('invoice_date', '>=', $period->startDate())
                        ->whereDate('invoice_date', '<=', $period->endDate());
                })->orWhere(function ($dates) use ($period) {
                    $dates->whereNotNull('paid_date')
                        ->whereDate('paid_date', '>=', $period->startDate())
                        ->whereDate('paid_date', '<=', $period->endDate());
                });
            })
            ->orderBy('invoice_date')
            ->orderBy('id');

        if (! $includeCancelled) {
            $query->where('status', '!=', 'cancelled');
        }

        $invoices = $query->get()->keyBy('id');

        foreach ($rideInvoices as $invoice) {
            if ((int) $invoice->company_id !== $companyId) {
                continue;
            }
            if ($invoice->status === 'draft') {
                continue;
            }
            if (! $includeCancelled && $invoice->status === 'cancelled') {
                continue;
            }
            $invoices->put($invoice->id, $invoice);
        }

        return $invoices
            ->sortBy([
                fn (Invoice $invoice) => $invoice->invoice_date?->format('Y-m-d') ?? '',
                fn (Invoice $invoice) => (int) $invoice->id,
            ])
            ->values();
    }

    public function invoiceZip(Company $company, FinancialOverviewPeriod $period): StreamedResponse
    {
        if (! class_exists(ZipArchive::class)) {
            abort(500, 'Zip-ondersteuning ontbreekt op de server.');
        }

        $invoices = $this->invoicesForPeriod((int) $company->id, $period);
        if ($invoices->isEmpty()) {
            throw new \InvalidArgumentException('Geen facturen in deze periode.');
        }
        if ($invoices->count() > self::MAX_INVOICE_ZIP) {
            throw new \InvalidArgumentException('Te veel facturen (max. '.self::MAX_INVOICE_ZIP.'). Kies een kortere periode.');
        }

        $filename = $this->downloadBasename($company, 'facturen', $period).'.zip';
        $tmp = tempnam(sys_get_temp_dir(), 'nexa-facturen-');
        if ($tmp === false) {
            abort(500, 'Tijdelijk bestand kon niet worden aangemaakt.');
        }

        $zip = new ZipArchive;
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);
            abort(500, 'Zipbestand kon niet worden geopend.');
        }

        $usedNames = [];
        foreach ($invoices as $invoice) {
            $bytes = $this->invoicePdfs->renderPdfBytes($invoice);
            $entry = $this->uniqueZipName($usedNames, (string) $invoice->invoice_number);
            $zip->addFromString($entry, $bytes);
        }
        $zip->close();

        return response()->streamDownload(function () use ($tmp) {
            readfile($tmp);
            @unlink($tmp);
        }, $filename, [
            'Content-Type' => 'application/zip',
        ]);
    }

    public function incomePdf(Company $company, FinancialOverviewPeriod $period): \Illuminate\Http\Response
    {
        $rows = $this->incomeRows($company, $period);
        $totals = [
            'count' => count($rows),
            'amount' => round(array_sum(array_column($rows, 'amount')), 2),
        ];

        $bytes = Pdf::loadView('invoices.pdf.income-overview', $this->pdfViewData($company, $period, [
            'rows' => $rows,
            'totals' => $totals,
        ]))->setPaper('a4')->output();

        return $this->pdfResponse($bytes, $this->downloadBasename($company, 'inkomsten', $period).'.pdf');
    }

    public function vatPdf(Company $company, FinancialOverviewPeriod $period): \Illuminate\Http\Response
    {
        $invoices = $this->invoicesForPeriod((int) $company->id, $period);
        $groups = [];
        $net = 0.0;
        $vat = 0.0;
        $gross = 0.0;

        foreach ($invoices as $invoice) {
            $rate = $this->taxRatePercent($invoice);
            $key = number_format($rate, 1, '.', '');
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'rate' => $rate,
                    'count' => 0,
                    'net' => 0.0,
                    'vat' => 0.0,
                    'gross' => 0.0,
                ];
            }
            $groups[$key]['count']++;
            $groups[$key]['net'] += (float) $invoice->amount;
            $groups[$key]['vat'] += (float) $invoice->tax_amount;
            $groups[$key]['gross'] += (float) $invoice->total_amount;
            $net += (float) $invoice->amount;
            $vat += (float) $invoice->tax_amount;
            $gross += (float) $invoice->total_amount;
        }

        ksort($groups, SORT_NUMERIC);

        $bytes = Pdf::loadView('invoices.pdf.vat-overview', $this->pdfViewData($company, $period, [
            'groups' => array_values($groups),
            'totals' => [
                'count' => $invoices->count(),
                'net' => round($net, 2),
                'vat' => round($vat, 2),
                'gross' => round($gross, 2),
            ],
        ]))->setPaper('a4')->output();

        return $this->pdfResponse($bytes, $this->downloadBasename($company, 'btw-overzicht', $period).'.pdf');
    }

    public function auditPdf(Company $company, FinancialOverviewPeriod $period): \Illuminate\Http\Response
    {
        $invoices = $this->invoicesForPeriod((int) $company->id, $period, includeCancelled: true);
        $totals = [
            'count' => $invoices->count(),
            'net' => round((float) $invoices->sum(fn (Invoice $i) => (float) $i->amount), 2),
            'vat' => round((float) $invoices->sum(fn (Invoice $i) => (float) $i->tax_amount), 2),
            'gross' => round((float) $invoices->sum(fn (Invoice $i) => (float) $i->total_amount), 2),
        ];

        $bytes = Pdf::loadView('invoices.pdf.audit-register', $this->pdfViewData($company, $period, [
            'invoices' => $invoices,
            'totals' => $totals,
        ]))->setPaper('a4', 'landscape')->output();

        return $this->pdfResponse($bytes, $this->downloadBasename($company, 'factuurregister', $period).'.pdf');
    }

    /**
     * @return list<array{date: string, source: string, reference: string, customer: string, amount: float}>
     */
    public function incomeRows(Company $company, FinancialOverviewPeriod $period): array
    {
        $companyId = (int) $company->id;
        $start = $period->startDate();
        $end = $period->endDate();
        $rows = [];
        $invoiceIds = [];

        $this->ensureRideInvoicesForMolliePeriod($companyId, $period);

        $paidInvoices = Invoice::query()
            ->where('company_id', $companyId)
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('paid_date')
            ->whereDate('paid_date', '>=', $start)
            ->whereDate('paid_date', '<=', $end)
            ->orderBy('paid_date')
            ->orderBy('id')
            ->get();

        foreach ($paidInvoices as $invoice) {
            $invoiceIds[] = (int) $invoice->id;
            $rows[] = [
                'date' => $invoice->paid_date?->format('d-m-Y') ?? '',
                'sort' => $invoice->paid_date?->format('Y-m-d') ?? '',
                'source' => $this->invoiceSourceLabel($invoice),
                'reference' => (string) $invoice->invoice_number,
                'customer' => (string) ($invoice->customer_name ?: '—'),
                'amount' => (float) $invoice->total_amount,
            ];
        }

        $taxiConn = $this->taxiConnectionName();
        if ($taxiConn !== null) {
            $invoicedRideIds = Invoice::query()
                ->where('company_id', $companyId)
                ->where('module', Invoice::MODULE_TAXI)
                ->where('status', '!=', 'draft')
                ->where('status', '!=', 'cancelled')
                ->whereNotNull('module_reference_id')
                ->pluck('module_reference_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $rideQuery = RidePayment::on($taxiConn)
                ->where('status', RidePayment::STATUS_PAID)
                ->whereNotNull('paid_at')
                ->whereDate('paid_at', '>=', $start)
                ->whereDate('paid_at', '<=', $end)
                ->where(function ($builder) use ($companyId, $taxiConn) {
                    $builder->where('company_id', $companyId)
                        ->orWhereIn(
                            'ride_request_id',
                            RideRequest::on($taxiConn)->where('company_id', $companyId)->select('id')
                        );
                })
                ->orderBy('paid_at');

            foreach ($rideQuery->get() as $payment) {
                if (in_array((int) $payment->ride_request_id, $invoicedRideIds, true)) {
                    continue;
                }

                $rows[] = [
                    'date' => $payment->paid_at?->timezone(config('app.timezone'))->format('d-m-Y') ?? '',
                    'sort' => $payment->paid_at?->timezone(config('app.timezone'))->format('Y-m-d') ?? '',
                    'source' => $payment->mollie_payment_id ? 'Ritbetaling (Mollie)' : 'Ritbetaling',
                    'reference' => 'rit #'.(int) $payment->ride_request_id,
                    'customer' => '—',
                    'amount' => (float) $payment->amount,
                ];
            }
        }

        if (Schema::hasTable('payments')) {
            $payments = Payment::query()
                ->with('invoice')
                ->where('company_id', $companyId)
                ->where('status', 'paid')
                ->whereDate('paid_at', '>=', $start)
                ->whereDate('paid_at', '<=', $end)
                ->orderBy('paid_at')
                ->get();

            foreach ($payments as $payment) {
                $linkedId = (int) ($payment->invoice_id ?? 0);
                if ($linkedId > 0 && in_array($linkedId, $invoiceIds, true)) {
                    continue;
                }
                $rows[] = [
                    'date' => $payment->paid_at?->timezone(config('app.timezone'))->format('d-m-Y') ?? '',
                    'sort' => $payment->paid_at?->timezone(config('app.timezone'))->format('Y-m-d') ?? '',
                    'source' => 'Betaling',
                    'reference' => $payment->invoice?->invoice_number ?: ('#'.$payment->id),
                    'customer' => (string) ($payment->invoice?->customer_name ?: '—'),
                    'amount' => (float) $payment->amount,
                ];
            }
        }

        usort($rows, fn (array $a, array $b) => strcmp($a['sort'] ?? '', $b['sort'] ?? ''));

        return $rows;
    }

    public function previewCounts(Company $company, FinancialOverviewPeriod $period): array
    {
        $invoices = $this->invoicesForPeriod((int) $company->id, $period);
        $income = $this->incomeRows($company, $period);
        $audit = $this->invoicesForPeriod((int) $company->id, $period, includeCancelled: true);

        return [
            'invoices' => $invoices->count(),
            'income' => count($income),
            'income_amount' => round(array_sum(array_column($income, 'amount')), 2),
            'vat' => $invoices->count(),
            'audit' => $audit->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function pdfViewData(Company $company, FinancialOverviewPeriod $period, array $extra): array
    {
        $settings = InvoiceSetting::getSettingsForCompany((int) $company->id);

        return array_merge([
            'company' => $company,
            'period' => $period,
            'settings' => $settings,
            'logoDataUri' => $this->invoicePdfs->companyLogoDataUri($company),
            'generatedAt' => now(config('app.timezone'))->format('d-m-Y H:i'),
        ], $extra);
    }

    protected function pdfResponse(string $bytes, string $filename): \Illuminate\Http\Response
    {
        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    protected function downloadBasename(Company $company, string $kind, FinancialOverviewPeriod $period): string
    {
        $slug = Str::slug((string) $company->name) ?: 'tenant';

        return $slug.'-'.$kind.'-'.$period->startDate().'-'.$period->endDate();
    }

    /**
     * @param  array<string, true>  $usedNames
     */
    protected function uniqueZipName(array &$usedNames, string $invoiceNumber): string
    {
        $base = preg_replace('/[^A-Za-z0-9._-]+/', '_', $invoiceNumber) ?: 'factuur';
        $name = $base.'.pdf';
        $i = 2;
        while (isset($usedNames[$name])) {
            $name = $base.'-'.$i.'.pdf';
            $i++;
        }
        $usedNames[$name] = true;

        return $name;
    }

    protected function taxRatePercent(Invoice $invoice): float
    {
        $net = (float) $invoice->amount;
        $tax = (float) $invoice->tax_amount;
        if ($net <= 0) {
            return $tax > 0 ? 21.0 : 0.0;
        }

        return round(($tax / $net) * 100, 1);
    }

    protected function invoiceSourceLabel(Invoice $invoice): string
    {
        return match ($invoice->module) {
            Invoice::MODULE_TAXI => 'Ritfactuur',
            Invoice::MODULE_TAXI_CONTRACT => 'Contractfactuur',
            Invoice::MODULE_CUSTOMER => 'Klantfactuur',
            default => 'Factuur',
        };
    }

    /**
     * @return Collection<int, Invoice>
     */
    protected function ensureRideInvoicesForMolliePeriod(int $companyId, FinancialOverviewPeriod $period): Collection
    {
        $conn = $this->taxiConnectionName();
        if ($conn === null) {
            return collect();
        }

        $payments = RidePayment::on($conn)
            ->where('status', RidePayment::STATUS_PAID)
            ->whereNotNull('mollie_payment_id')
            ->where('mollie_payment_id', '!=', '')
            ->whereNotNull('paid_at')
            ->whereDate('paid_at', '>=', $period->startDate())
            ->whereDate('paid_at', '<=', $period->endDate())
            ->where(function ($builder) use ($companyId, $conn) {
                $builder->where('company_id', $companyId)
                    ->orWhereIn(
                        'ride_request_id',
                        RideRequest::on($conn)->where('company_id', $companyId)->select('id')
                    );
            })
            ->orderBy('paid_at')
            ->orderBy('id')
            ->get();

        if ($payments->isEmpty()) {
            return collect();
        }

        $invoiceService = app(TaxiRideInvoiceService::class);
        $collected = collect();
        $rideIds = [];

        foreach ($payments as $payment) {
            $rideId = (int) $payment->ride_request_id;
            if ($rideId > 0) {
                $rideIds[] = $rideId;
            }

            $ride = RideRequest::on($conn)->whereKey($rideId)->first();
            if (! $ride) {
                continue;
            }

            try {
                if ($ride->requiresPerLegDriverPayment()) {
                    foreach ([RideRequest::INVOICE_BILLING_HEEN, RideRequest::INVOICE_BILLING_TERUG] as $leg) {
                        $invoice = $invoiceService->ensureInvoiceForLeg($conn, $ride->fresh(), $leg, false);
                        if ($invoice) {
                            $collected->put($invoice->id, $invoice);
                        }
                    }
                } else {
                    $invoice = $invoiceService->ensureInvoiceForPaidRide($conn, $ride->fresh(), false);
                    if ($invoice) {
                        $collected->put($invoice->id, $invoice);
                    }
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($rideIds !== []) {
            Invoice::query()
                ->where('company_id', $companyId)
                ->where('module', Invoice::MODULE_TAXI)
                ->whereIn('module_reference_id', array_values(array_unique($rideIds)))
                ->get()
                ->each(function (Invoice $invoice) use ($collected) {
                    $collected->put($invoice->id, $invoice);
                });
        }

        return $collected->values();
    }

    protected function taxiConnectionName(): ?string
    {
        $candidates = [];
        try {
            $candidates[] = app(ModuleDatabaseService::class)->getModuleConnectionName('taxi');
        } catch (\Throwable) {
            // Geen taxi-moduleconnection.
        }
        $candidates[] = (string) config('database.default');

        foreach (array_unique(array_filter($candidates)) as $conn) {
            try {
                if (Schema::connection($conn)->hasTable('ride_payments')) {
                    return $conn;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }
}
