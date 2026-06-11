<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\Invoice;
use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Traits\UsesModuleDatabase;
use App\Modules\NexaTaxi\Services\TaxiBookingSummaryText;
use App\Services\ModuleDatabaseService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class TaxiPortalDataService
{
    use UsesModuleDatabase;

    public function __construct(
        protected ModuleDatabaseService $moduleDb
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function listRidesForCustomer(User $user): array
    {
        $conn = $this->moduleConnection();

        if (! Schema::connection($conn)->hasTable('ride_requests')) {
            return [];
        }

        return $this->customerRidesQuery($user, $conn)
            ->orderByDesc('pickup_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(fn (RideRequest $ride) => $this->rideToPortalPayload($ride))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listInvoicesForCustomer(User $user): array
    {
        if (! Schema::hasTable('invoices')) {
            return [];
        }

        $conn = $this->moduleConnection();
        /** @var Collection<int, RideRequest> $rideRows */
        $rideRows = $this->customerRidesQuery($user, $conn)->get();

        if ($rideRows->isEmpty()) {
            return [];
        }

        $rideIds = $rideRows->pluck('id')->map(fn ($id) => (int) $id)->all();
        $invoiceIds = $rideRows->pluck('invoice_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();

        $companyId = $this->resolveCompanyIdForPortal($user);

        $query = Invoice::query()
            ->where('module', Invoice::MODULE_TAXI)
            ->where(function (Builder $q) use ($rideIds, $invoiceIds) {
                $q->whereIn('module_reference_id', $rideIds);
                if ($invoiceIds !== []) {
                    $q->orWhereIn('id', $invoiceIds);
                }
            });

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query
            ->orderByDesc('invoice_number')
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->filter(fn (Invoice $invoice) => $this->invoiceVisibleInPortal($invoice, $rideRows))
            ->map(fn (Invoice $invoice) => $this->invoiceToPortalPayload($invoice, $rideRows))
            ->values()
            ->all();
    }

    public function customerOwnsInvoice(User $user, Invoice $invoice): bool
    {
        if ($invoice->module !== Invoice::MODULE_TAXI) {
            return false;
        }

        $companyId = $this->resolveCompanyIdForPortal($user);
        if ($companyId && (int) $invoice->company_id !== $companyId) {
            return false;
        }

        $conn = $this->moduleConnection();
        $rideQuery = $this->customerRidesQuery($user, $conn);

        if ($invoice->module_reference_id) {
            $ride = (clone $rideQuery)->whereKey((int) $invoice->module_reference_id)->first();
            if ($ride && $this->canViewInvoicePdf($ride, $invoice)) {
                return true;
            }
        }

        if ($invoice->id && Schema::connection($conn)->hasColumn('ride_requests', 'invoice_id')) {
            $ride = (clone $rideQuery)->where('invoice_id', $invoice->id)->first();
            if ($ride && $this->canViewInvoicePdf($ride, $invoice)) {
                return true;
            }
        }

        if ($invoice->module === Invoice::MODULE_TAXI) {
            return false;
        }

        $email = $this->normalizedEmail($user);

        return $email !== ''
            && $invoice->customer_email
            && mb_strtolower(trim($invoice->customer_email)) === $email;
    }

    /**
     * @return array<string, mixed>
     */
    public function profilePayload(User $user): array
    {
        return [
            'first_name' => (string) ($user->first_name ?? ''),
            'last_name' => (string) ($user->last_name ?? ''),
            'email' => (string) ($user->email ?? ''),
            'phone' => (string) ($user->phone ?? ''),
        ];
    }

    public function updateProfile(User $user, array $data): User
    {
        $user->forceFill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
        ])->save();

        return $user->fresh();
    }

    public function updatePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Huidig wachtwoord is onjuist.'],
            ]);
        }

        $user->forceFill([
            'password' => $newPassword,
        ])->save();
    }

    public function findRideForCustomer(User $user, int $rideId): ?RideRequest
    {
        $conn = $this->moduleConnection();
        if (! Schema::connection($conn)->hasTable('ride_requests')) {
            return null;
        }

        return $this->customerRidesQuery($user, $conn)->whereKey($rideId)->first();
    }

    public function customerOwnsRide(User $user, RideRequest $ride): bool
    {
        return $this->findRideForCustomer($user, (int) $ride->id) !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rideDetailPayload(User $user, int $rideId): array
    {
        $ride = $this->findRideForCustomer($user, $rideId);
        if (! $ride) {
            throw ValidationException::withMessages([
                'ride' => ['Rit niet gevonden.'],
            ]);
        }

        $summary = app(TaxiBookingSummaryText::class)->build($ride);
        $invoice = app(TaxiRideInvoiceService::class)->findInvoiceForRide($ride);
        $amount = $ride->final_price ?? $ride->quoted_price;

        return [
            ...$this->rideToPortalPayload($ride),
            'summary_text' => $summary,
            'summary_lines' => array_values(array_filter(explode("\n", $summary))),
            'customer_name' => (string) ($ride->customer_name ?: '—'),
            'customer_phone' => (string) ($ride->customer_phone ?: '—'),
            'customer_email' => (string) ($ride->customer_email ?: '—'),
            'passengers' => (int) ($ride->passengers ?? 1),
            'payment_method' => (string) ($ride->payment_method ?? '—'),
            'payment_status' => (string) ($ride->payment_status ?? '—'),
            'distance_km' => $ride->distance_km,
            'duration_minutes' => $ride->duration_minutes,
            'customer_note' => trim((string) ($ride->customer_note ?? '')),
            'invoice_number' => $invoice?->invoice_number,
            'invoice_id' => $this->portalInvoiceIdForRide($ride, $invoice),
            'has_invoice' => $this->canViewInvoicePdf($ride, $invoice),
            'can_view_invoice_pdf' => $this->canViewInvoicePdf($ride, $invoice),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboardPayload(User $user, string $chartPeriod = 'day'): array
    {
        $chartPeriod = $this->normalizeChartPeriod($chartPeriod);
        $conn = $this->moduleConnection();
        if (! Schema::connection($conn)->hasTable('ride_requests')) {
            return [
                'stats' => [
                    'total_rides' => 0,
                    'completed_rides' => 0,
                    'upcoming_rides' => 0,
                    'total_spent' => '€ 0,00',
                    'total_spent_raw' => 0.0,
                    'invoice_count' => 0,
                ],
                'chart' => $this->emptyChartPayload($chartPeriod),
            ];
        }

        $rides = $this->customerRidesQuery($user, $conn)->orderByDesc('pickup_at')->get();

        $now = now();
        $completed = $rides->filter(fn (RideRequest $r) => $r->status === RideRequest::STATUS_COMPLETED);
        $upcoming = $rides->filter(function (RideRequest $r) use ($now) {
            if (in_array($r->status, [RideRequest::STATUS_COMPLETED, RideRequest::STATUS_CANCELLED], true)) {
                return false;
            }

            return $r->pickup_at === null || $r->pickup_at->gte($now);
        });

        $totalSpent = $rides
            ->filter(fn (RideRequest $r) => $r->status !== RideRequest::STATUS_CANCELLED)
            ->sum(fn (RideRequest $r) => $this->rideCostAmount($r));

        $invoiceCount = 0;
        if (Schema::hasTable('invoices') && $rides->isNotEmpty()) {
            $invoiceCount = count($this->listInvoicesForCustomer($user));
        }

        return [
            'stats' => [
                'total_rides' => $rides->count(),
                'completed_rides' => $completed->count(),
                'upcoming_rides' => $upcoming->count(),
                'total_spent' => $this->formatMoney($totalSpent > 0 ? $totalSpent : null),
                'total_spent_raw' => round((float) $totalSpent, 2),
                'invoice_count' => $invoiceCount,
            ],
            'chart' => $this->buildChartPayload($rides, $chartPeriod),
        ];
    }

    /**
     * @return Builder<RideRequest>
     */
    protected function customerRidesQuery(User $user, string $conn): Builder
    {
        $query = RideRequest::on($conn)->newQuery();
        $companyId = $this->resolveCompanyIdForPortal($user);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $userId = (int) $user->id;
        $email = $this->normalizedEmail($user);
        $hasCustomerUserId = Schema::connection($conn)->hasColumn('ride_requests', 'customer_user_id');

        if (! $hasCustomerUserId && $email === '') {
            return $query->whereRaw('1 = 0');
        }

        $query->where(function (Builder $q) use ($userId, $email, $hasCustomerUserId) {
            $matched = false;

            if ($hasCustomerUserId) {
                $q->where('customer_user_id', $userId);
                $matched = true;
            }

            if ($email !== '') {
                $method = $matched ? 'orWhere' : 'where';
                $q->{$method}(function (Builder $q2) use ($email, $hasCustomerUserId, $userId) {
                    $q2->whereRaw('LOWER(TRIM(customer_email)) = ?', [$email]);
                    if ($hasCustomerUserId) {
                        $q2->where(function (Builder $q3) use ($userId) {
                            $q3->whereNull('customer_user_id')
                                ->orWhere('customer_user_id', '!=', $userId);
                        });
                    }
                });
            }
        });

        return $query;
    }

    protected function resolveCompanyIdForPortal(User $user): ?int
    {
        if ($user->company_id) {
            return (int) $user->company_id;
        }

        if (app()->bound('resolved_tenant_id')) {
            $resolved = (int) app('resolved_tenant_id');

            return $resolved > 0 ? $resolved : null;
        }

        return null;
    }

    protected function normalizedEmail(User $user): string
    {
        $email = trim((string) ($user->email ?? ''));

        return $email !== '' ? mb_strtolower($email) : '';
    }

    /**
     * @return array<string, mixed>
     */
    protected function rideToPortalPayload(RideRequest $ride): array
    {
        $status = (string) $ride->status;
        $labels = RideRequest::statusLabels();
        $amount = $ride->final_price ?? $ride->quoted_price;
        $invoiceId = $this->portalInvoiceIdForRide($ride);

        return [
            'id' => (int) $ride->id,
            'from' => (string) $ride->pickup_address,
            'to' => (string) $ride->dropoff_address,
            'route' => trim($ride->pickup_address).' → '.trim($ride->dropoff_address),
            'at' => $ride->pickup_at?->format('d-m-Y H:i') ?? '—',
            'pickup_at_iso' => $ride->pickup_at?->toIso8601String(),
            'status' => $status,
            'status_label' => $labels[$status] ?? $status,
            'status_badge' => $this->rideStatusBadge($status),
            'amount' => $this->formatMoney($amount),
            'amount_raw' => $amount !== null ? (float) $amount : null,
            'invoice_id' => $invoiceId,
            'has_invoice' => $invoiceId !== null,
            'can_view_invoice_pdf' => $invoiceId !== null,
        ];
    }

    protected function portalInvoiceIdForRide(RideRequest $ride, ?Invoice $knownInvoice = null): ?int
    {
        if ($ride->payment_status !== RideRequest::PAYMENT_STATUS_PAID) {
            return null;
        }

        if ($ride->invoice_id) {
            return (int) $ride->invoice_id;
        }

        if ($knownInvoice) {
            return (int) $knownInvoice->id;
        }

        $invoice = app(TaxiRideInvoiceService::class)->findInvoiceForRide($ride);

        return $invoice ? (int) $invoice->id : null;
    }

    protected function canViewInvoicePdf(RideRequest $ride, ?Invoice $invoice = null): bool
    {
        return $this->portalInvoiceIdForRide($ride, $invoice) !== null;
    }

    /**
     * @param  Collection<int, RideRequest>  $rideRows
     */
    protected function invoiceVisibleInPortal(Invoice $invoice, Collection $rideRows): bool
    {
        $ride = $rideRows->firstWhere('id', (int) $invoice->module_reference_id)
            ?? $rideRows->firstWhere('invoice_id', (int) $invoice->id);

        return $ride instanceof RideRequest && $this->canViewInvoicePdf($ride, $invoice);
    }

    /**
     * @param  Collection<int, RideRequest>  $rideRows
     * @return array<string, mixed>
     */
    protected function invoiceToPortalPayload(Invoice $invoice, Collection $rideRows): array
    {
        $ride = $rideRows->firstWhere('id', (int) $invoice->module_reference_id)
            ?? $rideRows->firstWhere('invoice_id', (int) $invoice->id);

        return [
            'id' => (int) $invoice->id,
            'invoice_number' => (string) $invoice->invoice_number,
            'ride_id' => $invoice->module_reference_id ? (int) $invoice->module_reference_id : ($ride?->id),
            'date' => $invoice->invoice_date?->format('d-m-Y') ?? $invoice->created_at?->format('d-m-Y') ?? '—',
            'date_iso' => $invoice->invoice_date?->toIso8601String()
                ?? $invoice->created_at?->toIso8601String(),
            'status' => (string) $invoice->status,
            'status_label' => $this->invoiceStatusLabel((string) $invoice->status),
            'status_badge' => $this->invoiceStatusBadge((string) $invoice->status),
            'amount' => $this->formatMoney($invoice->total_amount),
            'amount_raw' => (float) $invoice->total_amount,
            'has_pdf' => true,
            'from' => $ride instanceof RideRequest ? trim((string) $ride->pickup_address) : null,
            'to' => $ride instanceof RideRequest ? trim((string) $ride->dropoff_address) : null,
            'route' => $ride instanceof RideRequest
                ? trim((string) $ride->pickup_address).' → '.trim((string) $ride->dropoff_address)
                : null,
        ];
    }

    protected function rideStatusBadge(string $status): string
    {
        return match ($status) {
            RideRequest::STATUS_DRAFT => 'secondary',
            RideRequest::STATUS_QUOTED => 'mono',
            RideRequest::STATUS_PENDING_PAYMENT => 'pending_payment',
            RideRequest::STATUS_PENDING_DISPATCH => 'pending_dispatch',
            RideRequest::STATUS_OFFERED => 'offered',
            RideRequest::STATUS_ACCEPTED => 'accepted',
            RideRequest::STATUS_ASSIGNED => 'assigned',
            RideRequest::STATUS_COMPLETED => 'success',
            RideRequest::STATUS_CANCELLED => 'destructive',
            default => 'secondary',
        };
    }

    protected function invoiceStatusBadge(string $status): string
    {
        return match ($status) {
            'paid' => 'success',
            'sent' => 'invoice_sent',
            'in_progress' => 'invoice_progress',
            'overdue', 'cancelled' => 'destructive',
            'draft' => 'secondary',
            default => 'secondary',
        };
    }

    protected function invoiceStatusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'Concept',
            'in_progress' => 'In behandeling',
            'sent' => 'Verzonden',
            'paid' => 'Betaald',
            'overdue' => 'Achterstallig',
            'cancelled' => 'Geannuleerd',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    protected function formatMoney(mixed $amount): string
    {
        if ($amount === null || $amount === '') {
            return '—';
        }

        return '€ '.number_format((float) $amount, 2, ',', '.');
    }

    protected function rideCostAmount(RideRequest $ride): float
    {
        $amount = $ride->final_price ?? $ride->quoted_price;
        if ($amount === null || $amount === '') {
            return 0.0;
        }

        return round((float) $amount, 2);
    }

    protected function chartDateForRide(RideRequest $ride): ?Carbon
    {
        if ($ride->pickup_at) {
            return $ride->pickup_at->copy();
        }

        if ($ride->created_at) {
            return $ride->created_at->copy();
        }

        return null;
    }

    /**
     * @param  Collection<int, RideRequest>  $rides
     * @return array{labels: list<string>, amounts: list<float>, has_data: bool, total: float, period: string, period_label: string}
     */
    protected function buildChartPayload(Collection $rides, string $period): array
    {
        $period = $this->normalizeChartPeriod($period);
        $now = now();
        $labels = [];
        $amountsByKey = [];

        if ($period === 'month') {
            for ($i = 11; $i >= 0; $i--) {
                $month = $now->copy()->subMonths($i)->startOfMonth();
                $key = $month->format('Y-m');
                $labels[] = $month->locale('nl')->isoFormat('MMM YYYY');
                $amountsByKey[$key] = 0.0;
            }
        } elseif ($period === 'year') {
            for ($i = 4; $i >= 0; $i--) {
                $year = $now->copy()->subYears($i)->startOfYear();
                $key = $year->format('Y');
                $labels[] = $year->format('Y');
                $amountsByKey[$key] = 0.0;
            }
        } else {
            for ($i = 29; $i >= 0; $i--) {
                $day = $now->copy()->subDays($i)->startOfDay();
                $key = $day->format('Y-m-d');
                $labels[] = $day->locale('nl')->isoFormat('D MMM');
                $amountsByKey[$key] = 0.0;
            }
        }

        foreach ($rides as $ride) {
            if ($ride->status === RideRequest::STATUS_CANCELLED) {
                continue;
            }

            $chartDate = $this->chartDateForRide($ride);
            if (! $chartDate) {
                continue;
            }

            $key = match ($period) {
                'month' => $chartDate->format('Y-m'),
                'year' => $chartDate->format('Y'),
                default => $chartDate->format('Y-m-d'),
            };

            if (! array_key_exists($key, $amountsByKey)) {
                continue;
            }

            $amountsByKey[$key] += $this->rideCostAmount($ride);
        }

        $amounts = array_values(array_map(
            static fn (float $v): float => round($v, 2),
            $amountsByKey
        ));
        $total = round((float) array_sum($amounts), 2);

        return [
            'labels' => $labels,
            'amounts' => $amounts,
            'has_data' => $total >= 0.01,
            'total' => $total,
            'period' => $period,
            'period_label' => $this->chartPeriodLabel($period),
        ];
    }

    protected function normalizeChartPeriod(string $period): string
    {
        return in_array($period, ['day', 'month', 'year'], true) ? $period : 'day';
    }

    protected function chartPeriodLabel(string $period): string
    {
        return match ($this->normalizeChartPeriod($period)) {
            'month' => 'Laatste 12 maanden',
            'year' => 'Laatste 5 jaar',
            default => 'Laatste 30 dagen',
        };
    }

    /**
     * @return array{labels: list<string>, amounts: list<float>, has_data: bool, total: float, period: string, period_label: string}
     */
    protected function emptyChartPayload(string $period = 'day'): array
    {
        $period = $this->normalizeChartPeriod($period);
        $labels = [];
        $amounts = [];
        $now = now();

        if ($period === 'month') {
            for ($i = 11; $i >= 0; $i--) {
                $month = $now->copy()->subMonths($i)->startOfMonth();
                $labels[] = $month->locale('nl')->isoFormat('MMM YYYY');
                $amounts[] = 0.0;
            }
        } elseif ($period === 'year') {
            for ($i = 4; $i >= 0; $i--) {
                $labels[] = $now->copy()->subYears($i)->startOfYear()->format('Y');
                $amounts[] = 0.0;
            }
        } else {
            for ($i = 29; $i >= 0; $i--) {
                $day = $now->copy()->subDays($i)->startOfDay();
                $labels[] = $day->locale('nl')->isoFormat('D MMM');
                $amounts[] = 0.0;
            }
        }

        return [
            'labels' => $labels,
            'amounts' => $amounts,
            'has_data' => false,
            'total' => 0.0,
            'period' => $period,
            'period_label' => $this->chartPeriodLabel($period),
        ];
    }
}
