<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyBillingProfile;
use App\Models\CompanySubscriptionChange;
use App\Models\PlatformBillingLineItem;
use App\Models\PlatformBillingPackage;
use App\Models\PlatformPaymentMandate;
use App\Services\PlatformBilling\PlatformBillingService;
use App\Services\PlatformBilling\PlatformInvoicePdfService;
use App\Services\PlatformBilling\PlatformMollieRequestBuilder;
use App\Services\PlatformBilling\SubscriptionBillingCalculator;
use App\Services\PlatformBilling\TenantBillingAccessService;
use App\Services\PlatformBilling\TenantSubscriptionService;
use App\Support\Admin\AdminTenantScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminCompanyBillingProfileController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureSuperAdmin();

        $filterCompanyId = app(AdminTenantScope::class)->optionalFilterTenantId($request);
        $query = Company::query()->where('is_active', true);

        if ($filterCompanyId) {
            $query->where('id', $filterCompanyId);
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->toString();
            $driver = DB::connection()->getDriverName();
            if ($driver === 'pgsql') {
                $query->whereRaw('name ILIKE ?', ["%{$search}%"]);
            } else {
                $query->where('name', 'like', "%{$search}%");
            }
        }

        if ($request->filled('billing_mode')) {
            $mode = $request->string('billing_mode')->toString();
            if ($mode === 'none') {
                $configuredIds = CompanyBillingProfile::query()->pluck('company_id');
                $query->whereNotIn('id', $configuredIds);
            } else {
                $companyIds = CompanyBillingProfile::query()
                    ->where('billing_mode', $mode)
                    ->pluck('company_id');
                $query->whereIn('id', $companyIds);
            }
        }

        if ($request->filled('mandate_status')) {
            $mandateStatus = $request->string('mandate_status')->toString();
            if ($mandateStatus === 'none') {
                $withMandateIds = PlatformPaymentMandate::query()->pluck('company_id');
                $query->whereNotIn('id', $withMandateIds);
            } else {
                $companyIds = PlatformPaymentMandate::query()
                    ->where('status', $mandateStatus)
                    ->pluck('company_id');
                $query->whereIn('id', $companyIds);
            }
        }

        $companies = $query->orderBy('name')->paginate(20)->withQueryString();
        $companyIds = $companies->pluck('id');

        $profiles = CompanyBillingProfile::query()
            ->with(['package', 'company'])
            ->whereIn('company_id', $companyIds)
            ->get()
            ->keyBy('company_id');

        $mandates = PlatformPaymentMandate::query()
            ->whereIn('company_id', $companyIds)
            ->get()
            ->keyBy('company_id');

        $tenantOptions = Company::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.platform-billing.tenants.index', compact(
            'companies',
            'profiles',
            'mandates',
            'tenantOptions',
            'filterCompanyId',
        ));
    }

    public function edit(Company $company, PlatformBillingService $billing, PlatformMollieRequestBuilder $mollieRequests): View
    {
        $this->ensureSuperAdmin();
        app(TenantSubscriptionService::class)->syncPlatformPackagesFromPricing();
        $profile = app(TenantSubscriptionService::class)->syncBillingPackageFromCompany($company);
        $profile->load(['package', 'lineItems', 'company']);
        $catalogLineItems = PlatformBillingLineItem::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $mandate = PlatformPaymentMandate::query()->where('company_id', $company->id)->first();
        $invoicePreview = $this->buildInvoicePreviewData($company, $profile, $billing);
        $mollieRequestPlan = $mollieRequests->buildTenantRequestPlan($company, $profile, $mandate);

        $calculator = app(SubscriptionBillingCalculator::class);
        $asOf = now()->startOfDay();
        $emergencyTerminateEffectiveOn = $asOf->copy()->endOfMonth()->startOfDay();
        $subscriptionEnded = $calculator->isEnded($profile, $asOf)
            && trim((string) ($profile->pending_change_type ?? '')) !== CompanySubscriptionChange::TYPE_CANCEL;
        $emergencyCancelAlreadyScheduled = trim((string) ($profile->pending_change_type ?? '')) === CompanySubscriptionChange::TYPE_CANCEL
            && $profile->subscription_end_date
            && $profile->subscription_end_date->copy()->startOfDay()->equalTo($emergencyTerminateEffectiveOn);
        $canEmergencyTerminate = ! $subscriptionEnded
            && $profile->billing_mode !== CompanyBillingProfile::MODE_FREE
            && ! $emergencyCancelAlreadyScheduled;

        return view('admin.platform-billing.tenants.edit', compact(
            'company',
            'profile',
            'catalogLineItems',
            'mandate',
            'invoicePreview',
            'mollieRequestPlan',
            'emergencyTerminateEffectiveOn',
            'canEmergencyTerminate',
            'emergencyCancelAlreadyScheduled',
            'subscriptionEnded',
        ));
    }

    public function update(Request $request, Company $company, TenantBillingAccessService $access): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $request->merge([
            'subscription_start_date' => parse_admin_date($request->input('subscription_start_date')),
            'subscription_end_date' => parse_admin_date($request->input('subscription_end_date')),
        ]);
        $validated = $request->validate([
            'billing_mode' => 'required|in:package,custom,free',
            'custom_monthly_amount' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|integer|min:0|max:100',
            'extra_lines_discount_percent' => 'nullable|integer|min:0|max:100',
            'subscription_start_date' => 'nullable|date',
            'subscription_end_date' => 'nullable|date|after_or_equal:subscription_start_date',
            'billing_email' => 'nullable|email|max:255',
            'billing_contact_name' => 'nullable|string|max:255',
            'auto_collect_enabled' => 'sometimes|boolean',
            'overdue_block_mode' => 'required|in:bookings,full',
            'access_restriction' => 'required|in:none,bookings,full',
            'notes' => 'nullable|string|max:5000',
            'extra_lines_one_time' => 'sometimes|boolean',
            'platform_billing_line_item_ids' => 'nullable|array',
            'platform_billing_line_item_ids.*' => 'integer|exists:platform_billing_line_items,id',
        ]);

        $profile = CompanyBillingProfile::query()->firstOrCreate(
            ['company_id' => $company->id],
            ['billing_mode' => CompanyBillingProfile::MODE_PACKAGE, 'extra_lines_one_time' => true]
        );
        $profile->load(['package', 'company']);

        $selectedLineItemIds = collect($validated['platform_billing_line_item_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values();
        $previousLineItemIds = $profile->lineItems()->pluck('platform_billing_line_items.id')->sort()->values();

        $profile->fill([
            'billing_mode' => $validated['billing_mode'],
            'discount_percent' => (int) ($validated['discount_percent'] ?? 0),
            'extra_lines_discount_percent' => (int) ($validated['extra_lines_discount_percent'] ?? 0),
            'subscription_start_date' => $validated['subscription_start_date'] ?? null,
            'subscription_end_date' => $validated['subscription_end_date'] ?? null,
            'billing_email' => $validated['billing_email'] ?? null,
            'billing_contact_name' => $validated['billing_contact_name'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'auto_collect_enabled' => $request->boolean('auto_collect_enabled'),
            'overdue_block_mode' => $validated['overdue_block_mode'],
            'extra_lines_one_time' => $request->boolean('extra_lines_one_time'),
            'custom_monthly_amount' => $validated['billing_mode'] === 'custom'
                ? ($validated['custom_monthly_amount'] ?? 0)
                : null,
        ]);

        if ($validated['billing_mode'] !== 'package') {
            $profile->platform_billing_package_id = null;
            $profile->agreed_monthly_amount = null;
        }

        $profile->save();

        if ($validated['billing_mode'] === 'package') {
            $profile = app(TenantSubscriptionService::class)->syncBillingPackageFromCompany($company->fresh());
        }

        if ($this->lineItemSelectionChanged($selectedLineItemIds, $previousLineItemIds)) {
            $profile->extra_lines_applied_at = null;
        }

        $profile->save();
        $profile->lineItems()->sync($selectedLineItemIds->all());

        $desiredRestriction = $validated['access_restriction'];
        $currentRestriction = in_array($profile->access_restriction, [TenantBillingAccessService::BOOKINGS, TenantBillingAccessService::FULL], true)
            ? $profile->access_restriction
            : TenantBillingAccessService::NONE;
        if ($desiredRestriction === TenantBillingAccessService::NONE && $currentRestriction !== TenantBillingAccessService::NONE) {
            $access->clearRestriction($profile, waiveOverdueBlocks: true);
        } elseif (in_array($desiredRestriction, [TenantBillingAccessService::BOOKINGS, TenantBillingAccessService::FULL], true)
            && $desiredRestriction !== $currentRestriction) {
            $access->applyRestriction($profile, $desiredRestriction, TenantBillingAccessService::SOURCE_MANUAL);
        }

        return redirect()->route('admin.platform-billing.tenants.edit', $company)
            ->with('success', 'Tenant-facturatie opgeslagen.');
    }

    public function mollieRequestPreview(Request $request, Company $company, PlatformMollieRequestBuilder $mollieRequests): View
    {
        $this->ensureSuperAdmin();
        $profile = $this->profileFromRequest($company, $request);
        $mandate = PlatformPaymentMandate::query()->where('company_id', $company->id)->first();

        return view('admin.platform-billing.tenants.partials.mollie-request-preview', [
            'requests' => $mollieRequests->buildTenantRequestPlan($company, $profile, $mandate),
        ]);
    }

    public function invoicePreview(Request $request, Company $company, PlatformBillingService $billing): View
    {
        $this->ensureSuperAdmin();
        $profile = $this->profileFromRequest($company, $request);

        return view('admin.platform-billing.tenants.partials.invoice-preview', [
            'preview' => $this->buildInvoicePreviewData($company, $profile, $billing),
        ]);
    }

    public function invoicePreviewPdf(Request $request, Company $company, PlatformBillingService $billing, PlatformInvoicePdfService $pdf): \Symfony\Component\HttpFoundation\Response
    {
        $this->ensureSuperAdmin();
        $profile = $this->profileFromRequest($company, $request);
        $preview = $this->buildInvoicePreviewData($company, $profile, $billing);
        $bytes = $pdf->renderPreviewPdfBytes($company, $preview);
        $filename = 'factuur-'.preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) ($preview['invoice_number'] ?? 'voorbeeld')).'-voorbeeld.pdf';

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($bytes),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function requestMandate(Request $request, Company $company, PlatformBillingService $billing): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $email = trim((string) $request->input('recipient_email', ''));

        try {
            $billing->requestMandate($company, $email !== '' ? $email : null);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Mandaat-aanvraag verstuurd per e-mail (€0,01 verificatie).');
    }

    public function emergencyTerminate(Company $company, TenantSubscriptionService $subscriptions): RedirectResponse
    {
        $this->ensureSuperAdmin();

        try {
            $profile = $subscriptions->emergencyTerminateAtMonthEnd($company);
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.platform-billing.tenants.edit', $company)
                ->with('error', $e->getMessage());
        }

        $endLabel = $profile->subscription_end_date
            ? $profile->subscription_end_date->translatedFormat('j F Y')
            : 'einde van de maand';

        return redirect()
            ->route('admin.platform-billing.tenants.edit', ['company' => $company, 'saved' => 1])
            ->with('success', 'Abonnement wordt per '.$endLabel.' beëindigd. De volgende incasso is gestopt.');
    }

    private function profileFromRequest(Company $company, Request $request): CompanyBillingProfile
    {
        $profile = CompanyBillingProfile::query()->firstOrCreate(
            ['company_id' => $company->id],
            ['billing_mode' => CompanyBillingProfile::MODE_PACKAGE, 'extra_lines_one_time' => true]
        );

        $billingMode = (string) $request->input('billing_mode', $profile->billing_mode);

        $profile->fill([
            'billing_mode' => $billingMode,
            'custom_monthly_amount' => $billingMode === CompanyBillingProfile::MODE_CUSTOM
                ? max(0, (float) $request->input('custom_monthly_amount', 0))
                : null,
            'agreed_monthly_amount' => $billingMode === CompanyBillingProfile::MODE_PACKAGE
                ? $profile->agreed_monthly_amount
                : null,
            'discount_percent' => max(0, min(100, (int) $request->input('discount_percent', $profile->discount_percent ?? 0))),
            'extra_lines_discount_percent' => max(0, min(100, (int) $request->input('extra_lines_discount_percent', $profile->extra_lines_discount_percent ?? 0))),
            'subscription_start_date' => parse_admin_date($request->input('subscription_start_date')),
            'subscription_end_date' => parse_admin_date($request->input('subscription_end_date')),
            'mollie_subscription_id' => $profile->mollie_subscription_id,
            'mollie_subscription_status' => $profile->mollie_subscription_status,
            'mollie_subscription_synced_at' => $profile->mollie_subscription_synced_at,
            'billing_email' => $request->input('billing_email'),
            'billing_contact_name' => $request->input('billing_contact_name'),
            'notes' => $request->input('notes'),
            'extra_lines_one_time' => $request->boolean('extra_lines_one_time'),
            'extra_lines_applied_at' => $profile->extra_lines_applied_at,
        ]);

        if ($billingMode !== CompanyBillingProfile::MODE_PACKAGE) {
            $profile->platform_billing_package_id = null;
        }

        $lineItemIds = collect($request->input('platform_billing_line_item_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->sort()
            ->values();

        $previousLineItemIds = $profile->lineItems()->pluck('platform_billing_line_items.id')->sort()->values();
        if ($this->lineItemSelectionChanged($lineItemIds, $previousLineItemIds)) {
            $profile->extra_lines_applied_at = null;
        }

        $profile->setRelation(
            'lineItems',
            PlatformBillingLineItem::query()
                ->whereIn('id', $lineItemIds->all())
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
        );

        $profile->setRelation('company', $company);
        $profile->loadMissing('package');

        if ($profile->billing_mode === CompanyBillingProfile::MODE_PACKAGE && $profile->platform_billing_package_id) {
            $profile->setRelation(
                'package',
                PlatformBillingPackage::query()->find($profile->platform_billing_package_id)
            );
        } else {
            $profile->setRelation('package', null);
        }

        return $profile;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildInvoicePreviewData(Company $company, CompanyBillingProfile $profile, PlatformBillingService $billing): array
    {
        $previewProfile = clone $profile;
        $previewProfile->setRelation('company', $company);
        $previewProfile->setRelation('package', $profile->relationLoaded('package') ? $profile->package : null);
        $previewProfile->setRelation('lineItems', $profile->relationLoaded('lineItems') ? $profile->lineItems : collect());

        if ($previewProfile->extra_lines_one_time && $previewProfile->lineItems->isNotEmpty()) {
            $previewProfile->extra_lines_applied_at = null;
        }

        return $billing->previewInvoiceData($company, $previewProfile);
    }

    private function lineItemSelectionChanged(\Illuminate\Support\Collection $current, \Illuminate\Support\Collection $previous): bool
    {
        return $current->values()->all() !== $previous->values()->all();
    }

    private function ensureSuperAdmin(): void
    {
        if (! auth()->user()?->hasRole('super-admin')) {
            abort(403);
        }
    }
}
