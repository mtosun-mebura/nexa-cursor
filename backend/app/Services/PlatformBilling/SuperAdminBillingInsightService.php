<?php

namespace App\Services\PlatformBilling;

use App\Enums\AiChat\AiChatIntent;
use App\Models\Company;
use App\Models\CompanyBillingProfile;
use App\Models\CompanySubscriptionChange;
use App\Models\PlatformInvoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Openstaande NEXA-facturen, abonnementen en acties — voor dashboard én AI-chat.
 */
final class SuperAdminBillingInsightService
{
    public function __construct(
        private readonly TenantSubscriptionService $subscriptions,
        private readonly TenantBillingAccessService $billingAccess,
    ) {}

    /**
     * @param  array{company_id: int, query_hint?: ?string}  $claims
     * @return array{count: int, rows: list<array<string, mixed>>, summary: array<string, mixed>}
     */
    public function execute(AiChatIntent $intent, array $claims): array
    {
        return match ($intent) {
            AiChatIntent::PlatformTenantAbonnement => $this->tenantSubscriptionResult(
                $this->resolveCompany((int) ($claims['company_id'] ?? 0), $claims['query_hint'] ?? null)
            ),
            AiChatIntent::PlatformTenantFacturen => $this->tenantSaasInvoicesResult(
                $this->resolveCompany((int) ($claims['company_id'] ?? 0), $claims['query_hint'] ?? null)
            ),
            AiChatIntent::PlatformTenantsOnbetaald => $this->unpaidThisMonthResult(),
            AiChatIntent::PlatformActiesNodig => $this->actionsChatResult(),
            default => [
                'count' => 0,
                'rows' => [],
                'summary' => ['answer' => 'Deze factuurvraag kan ik nu niet beantwoorden.'],
            ],
        };
    }

    /**
     * @return list<array{
     *     severity: string,
     *     company_id: ?int,
     *     company_name: string,
     *     title: string,
     *     detail: string,
     *     url: string
     * }>
     */
    public function dashboardActions(): array
    {
        if (! $this->platformInvoicesReady()) {
            return [];
        }

        return $this->collectActions();
    }

    /**
     * @return array{count: int, rows: list<array<string, mixed>>, summary: array<string, mixed>}
     */
    private function tenantSubscriptionResult(?Company $company): array
    {
        if ($company === null) {
            return $this->missingCompanyResult();
        }

        $snapshot = $this->subscriptions->snapshot($company);
        $end = $snapshot['contract_end_date'] ?? null;
        $trialEnds = $snapshot['trial_ends_at'] ?? null;
        $pendingType = $snapshot['pending_type'] ?? null;
        $lines = [
            $company->name.' heeft pakket '.($snapshot['current_name'] ?? 'onbekend')
                .' ('.($snapshot['current_amount_label'] ?? '€ 0,00').' per maand).',
        ];

        if ($end instanceof Carbon) {
            $lines[] = 'Contract loopt tot '.$end->translatedFormat('d F Y').'.';
        }

        if (! empty($snapshot['in_trial']) && $trialEnds instanceof Carbon) {
            $days = (int) ($snapshot['trial_days_remaining'] ?? 0);
            $lines[] = 'Proefperiode tot '.$trialEnds->translatedFormat('d F Y')
                .($days > 0 ? ' (nog '.$days.' dagen).' : '.');
        }

        if (is_string($pendingType) && $pendingType !== '') {
            $label = match ($pendingType) {
                CompanySubscriptionChange::TYPE_CANCEL => 'opzegging',
                CompanySubscriptionChange::TYPE_DOWNGRADE => 'downgrade',
                CompanySubscriptionChange::TYPE_UPGRADE => 'upgrade',
                default => $pendingType,
            };
            $effective = $snapshot['pending_effective_on'] ?? null;
            $when = $effective instanceof Carbon ? ' per '.$effective->translatedFormat('d F Y') : '';
            $lines[] = 'Er staat een '.$label.' gepland'.$when.'.';
        }

        $restriction = $this->billingAccess->restrictionFor($company);
        if ($restriction === TenantBillingAccessService::FULL) {
            $lines[] = 'De omgeving is geblokkeerd wegens een openstaande NEXA-factuur.';
        } elseif ($restriction === TenantBillingAccessService::BOOKINGS) {
            $lines[] = 'Online boeken is geblokkeerd wegens een openstaande NEXA-factuur.';
        }

        $answer = implode(' ', $lines);

        return [
            'count' => 1,
            'rows' => [[
                'company_id' => $company->id,
                'company_name' => $company->name,
                'package' => $snapshot['current_name'] ?? null,
            ]],
            'summary' => ['answer' => $answer],
        ];
    }

    /**
     * @return array{count: int, rows: list<array<string, mixed>>, summary: array<string, mixed>}
     */
    private function tenantSaasInvoicesResult(?Company $company): array
    {
        if ($company === null) {
            return $this->missingCompanyResult();
        }

        if (! $this->platformInvoicesReady()) {
            return [
                'count' => 0,
                'rows' => [],
                'summary' => ['answer' => 'Er zijn nog geen NEXA-facturen beschikbaar.'],
            ];
        }

        $invoices = PlatformInvoice::query()
            ->where('company_id', $company->id)
            ->orderByDesc('invoice_date')
            ->limit(12)
            ->get();

        if ($invoices->isEmpty()) {
            return [
                'count' => 0,
                'rows' => [],
                'summary' => ['answer' => $company->name.' heeft nog geen NEXA-facturen.'],
            ];
        }

        $open = $invoices->filter(fn (PlatformInvoice $invoice) => $invoice->status !== 'paid');
        $lines = [$company->name.' — NEXA-facturen:'];
        foreach ($invoices as $invoice) {
            $lines[] = '- '.$this->formatPlatformInvoiceLine($invoice);
        }

        if ($open->isEmpty()) {
            $lines[] = 'Alle getoonde NEXA-facturen zijn betaald.';
        } else {
            $lines[] = $open->count() === 1
                ? 'Er staat nog 1 NEXA-factuur open.'
                : 'Er staan nog '.$open->count().' NEXA-facturen open.';
        }

        return [
            'count' => $invoices->count(),
            'rows' => $invoices->map(fn (PlatformInvoice $invoice) => [
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status,
                'total_amount' => (float) $invoice->total_amount,
                'due_date' => optional($invoice->due_date)?->toDateString(),
            ])->all(),
            'summary' => ['answer' => implode("\n", $lines)],
        ];
    }

    /**
     * @return array{count: int, rows: list<array<string, mixed>>, summary: array<string, mixed>}
     */
    private function unpaidThisMonthResult(): array
    {
        $invoices = $this->unpaidInvoicesThisMonth();
        if ($invoices->isEmpty()) {
            return [
                'count' => 0,
                'rows' => [],
                'summary' => ['answer' => 'Alle tenants hebben hun NEXA-factuur van deze maand betaald (of er is nog geen factuur).'],
            ];
        }

        $lines = ['Tenants met een onbetaalde NEXA-factuur deze maand:'];
        foreach ($invoices as $invoice) {
            $name = $invoice->company?->name ?? ('Bedrijf #'.$invoice->company_id);
            $lines[] = '- '.$name.': '.$this->formatPlatformInvoiceLine($invoice);
        }

        return [
            'count' => $invoices->count(),
            'rows' => $invoices->map(fn (PlatformInvoice $invoice) => [
                'company_id' => $invoice->company_id,
                'company_name' => $invoice->company?->name,
                'invoice_number' => $invoice->invoice_number,
                'total_amount' => (float) $invoice->total_amount,
            ])->all(),
            'summary' => ['answer' => implode("\n", $lines)],
        ];
    }

    /**
     * @return array{count: int, rows: list<array<string, mixed>>, summary: array<string, mixed>}
     */
    private function actionsChatResult(): array
    {
        $actions = $this->collectActions();
        if ($actions === []) {
            return [
                'count' => 0,
                'rows' => [],
                'summary' => ['answer' => 'Er zijn geen openstaande acties. Geen onbetaalde NEXA-facturen, blokkades of aflopende proefperiodes.'],
            ];
        }

        $lines = ['Dit moet er nog gebeuren:'];
        foreach ($actions as $action) {
            $lines[] = '- '.$action['company_name'].': '.$action['title'].' — '.$action['detail'];
        }

        return [
            'count' => count($actions),
            'rows' => $actions,
            'summary' => ['answer' => implode("\n", $lines)],
        ];
    }

    /**
     * @return list<array{
     *     severity: string,
     *     company_id: ?int,
     *     company_name: string,
     *     title: string,
     *     detail: string,
     *     url: string
     * }>
     */
    private function collectActions(): array
    {
        $actions = [];
        $seen = [];

        foreach ($this->unpaidInvoicesThisMonth() as $invoice) {
            $key = 'unpaid-'.$invoice->id;
            $seen[$key] = true;
            $company = $invoice->company;
            $actions[] = [
                'severity' => $invoice->due_date && $invoice->due_date->lt(now()->startOfDay()) ? 'danger' : 'warning',
                'company_id' => $invoice->company_id,
                'company_name' => $company?->name ?? ('Bedrijf #'.$invoice->company_id),
                'title' => 'NEXA-factuur deze maand niet betaald',
                'detail' => $this->formatPlatformInvoiceLine($invoice),
                'url' => $this->invoiceUrl($invoice),
            ];
        }

        if ($this->platformInvoicesReady()) {
            $overdue = PlatformInvoice::query()
                ->with('company')
                ->where('status', '!=', 'paid')
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->orderBy('due_date')
                ->limit(50)
                ->get();

            foreach ($overdue as $invoice) {
                $key = 'unpaid-'.$invoice->id;
                if (isset($seen[$key])) {
                    $invoice->setRelation('company', $invoice->company);
                    $actions = $this->replaceActionDetail($actions, $invoice);
                    continue;
                }
                $seen[$key] = true;
                $company = $invoice->company;
                $actions[] = [
                    'severity' => 'danger',
                    'company_id' => $invoice->company_id,
                    'company_name' => $company?->name ?? ('Bedrijf #'.$invoice->company_id),
                    'title' => 'Achterstallige NEXA-factuur',
                    'detail' => $this->formatPlatformInvoiceLine($invoice),
                    'url' => $this->invoiceUrl($invoice),
                ];
            }

            $blocked = PlatformInvoice::query()
                ->with('company')
                ->whereNotNull('blocked_at')
                ->whereNull('block_waived_at')
                ->where('status', '!=', 'paid')
                ->orderByDesc('blocked_at')
                ->limit(50)
                ->get();

            foreach ($blocked as $invoice) {
                $company = $invoice->company;
                $actions[] = [
                    'severity' => 'danger',
                    'company_id' => $invoice->company_id,
                    'company_name' => $company?->name ?? ('Bedrijf #'.$invoice->company_id),
                    'title' => 'Omgeving geblokkeerd',
                    'detail' => 'Geblokkeerd sinds '.$invoice->blocked_at?->translatedFormat('d M Y').' ('.$this->formatPlatformInvoiceLine($invoice).').',
                    'url' => $this->invoiceUrl($invoice),
                ];
            }
        }

        $profiles = CompanyBillingProfile::query()
            ->with('company')
            ->whereIn('access_restriction', [
                TenantBillingAccessService::BOOKINGS,
                TenantBillingAccessService::FULL,
            ])
            ->limit(50)
            ->get();

        foreach ($profiles as $profile) {
            $company = $profile->company;
            if ($company === null) {
                continue;
            }
            $mode = $profile->access_restriction === TenantBillingAccessService::FULL
                ? 'volledige blokkade'
                : 'boekingen geblokkeerd';
            $actions[] = [
                'severity' => 'danger',
                'company_id' => $company->id,
                'company_name' => $company->name,
                'title' => 'Toegang beperkt ('.$mode.')',
                'detail' => 'Los de openstaande NEXA-factuur op of hef de beperking op.',
                'url' => route('admin.platform-billing.tenants.edit', $company),
            ];
        }

        $soon = now()->addDays(14)->endOfDay();
        $trialProfiles = CompanyBillingProfile::query()
            ->with('company')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>=', now()->startOfDay())
            ->where('trial_ends_at', '<=', $soon)
            ->orderBy('trial_ends_at')
            ->limit(50)
            ->get();

        foreach ($trialProfiles as $profile) {
            $company = $profile->company;
            if ($company === null) {
                continue;
            }
            $ends = Carbon::parse($profile->trial_ends_at);
            $actions[] = [
                'severity' => 'info',
                'company_id' => $company->id,
                'company_name' => $company->name,
                'title' => 'Proefperiode loopt af',
                'detail' => 'Proef tot '.$ends->translatedFormat('d F Y').'.',
                'url' => route('admin.platform-billing.tenants.edit', $company),
            ];
        }

        $pending = CompanyBillingProfile::query()
            ->with('company')
            ->whereNotNull('pending_change_type')
            ->where('pending_change_type', '!=', '')
            ->limit(50)
            ->get();

        foreach ($pending as $profile) {
            $company = $profile->company;
            if ($company === null) {
                continue;
            }
            $type = (string) $profile->pending_change_type;
            $label = match ($type) {
                CompanySubscriptionChange::TYPE_CANCEL => 'Opzegging gepland',
                CompanySubscriptionChange::TYPE_DOWNGRADE => 'Downgrade gepland',
                CompanySubscriptionChange::TYPE_UPGRADE => 'Upgrade gepland',
                default => 'Abonnementswijziging gepland',
            };
            $when = $profile->pending_change_effective_on
                ? Carbon::parse($profile->pending_change_effective_on)->translatedFormat('d F Y')
                : 'datum onbekend';
            $actions[] = [
                'severity' => $type === CompanySubscriptionChange::TYPE_CANCEL ? 'warning' : 'info',
                'company_id' => $company->id,
                'company_name' => $company->name,
                'title' => $label,
                'detail' => 'Ingang per '.$when.'.',
                'url' => route('admin.platform-billing.tenants.edit', $company),
            ];
        }

        return $actions;
    }

    /**
     * @param  list<array<string, mixed>>  $actions
     * @return list<array<string, mixed>>
     */
    private function replaceActionDetail(array $actions, PlatformInvoice $invoice): array
    {
        foreach ($actions as $i => $action) {
            if (($action['detail'] ?? '') === $this->formatPlatformInvoiceLine($invoice)
                || str_contains((string) ($action['detail'] ?? ''), (string) $invoice->invoice_number)) {
                $actions[$i]['title'] = 'NEXA-factuur deze maand niet betaald (achterstallig)';
                $actions[$i]['severity'] = 'danger';
            }
        }

        return $actions;
    }

    /**
     * @return Collection<int, PlatformInvoice>
     */
    private function unpaidInvoicesThisMonth(): Collection
    {
        if (! $this->platformInvoicesReady()) {
            return collect();
        }

        $period = now()->format('Y-m');

        return PlatformInvoice::query()
            ->with('company')
            ->where('status', '!=', 'paid')
            ->where(function ($q) use ($period) {
                $q->where('billing_period', $period)
                    ->orWhere(function ($q2) {
                        $q2->whereYear('invoice_date', now()->year)
                            ->whereMonth('invoice_date', now()->month);
                    });
            })
            ->orderBy('company_id')
            ->limit(80)
            ->get();
    }

    public function resolveCompany(int $companyId, ?string $hint): ?Company
    {
        if ($companyId > 0) {
            return Company::query()->find($companyId);
        }

        $hint = trim((string) $hint);
        if ($hint === '') {
            return null;
        }

        $like = '%'.mb_strtolower($hint).'%';

        return Company::query()
            ->whereRaw('LOWER(name) LIKE ?', [$like])
            ->orderBy('id')
            ->first();
    }

    /**
     * @return array{count: int, rows: list<array<string, mixed>>, summary: array<string, mixed>}
     */
    private function missingCompanyResult(): array
    {
        return [
            'count' => 0,
            'rows' => [],
            'summary' => [
                'answer' => 'Noem de tenantnaam (bijvoorbeeld: welk abonnement heeft Taxi Tosun?) of selecteer eerst een tenant.',
            ],
        ];
    }

    private function formatPlatformInvoiceLine(PlatformInvoice $invoice): string
    {
        $amount = number_format((float) $invoice->total_amount, 2, ',', '.');
        $status = PlatformInvoice::STATUS_LABELS[$invoice->status] ?? $invoice->status;
        $due = $invoice->due_date ? $invoice->due_date->translatedFormat('d M Y') : 'geen vervaldatum';

        return ($invoice->invoice_number ?: 'Factuur').' — €'.$amount.' — '.$status.', vervalt '.$due;
    }

    private function invoiceUrl(PlatformInvoice $invoice): string
    {
        return route('admin.platform-billing.invoices.show', $invoice);
    }

    private function platformInvoicesReady(): bool
    {
        return Schema::hasTable('platform_invoices');
    }
}
