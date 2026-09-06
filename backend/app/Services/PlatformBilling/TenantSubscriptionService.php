<?php

namespace App\Services\PlatformBilling;

use App\Models\Company;
use App\Models\CompanyBillingProfile;
use App\Models\CompanySubscriptionChange;
use App\Models\PlatformBillingPackage;
use App\Models\PlatformPayment;
use App\Models\PlatformPaymentMandate;
use App\Services\NexaPricingService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class TenantSubscriptionService
{
    public function __construct(
        private readonly NexaPricingService $pricing,
        private readonly SubscriptionBillingCalculator $calculator,
        private readonly PlatformMollieService $mollie,
    ) {}

    public function ensureProfile(Company $company): CompanyBillingProfile
    {
        $trialStart = $company->created_at
            ? Carbon::parse($company->created_at)->startOfDay()
            : now()->startOfDay();
        $freeMonths = $this->freeMonthsForCompany($company);
        $trialEnd = $trialStart->copy()->addMonthsNoOverflow($freeMonths);

        $profile = CompanyBillingProfile::query()->firstOrCreate(
            ['company_id' => $company->id],
            [
                'billing_mode' => CompanyBillingProfile::MODE_PACKAGE,
                'extra_lines_one_time' => true,
                'auto_collect_enabled' => true,
                'agreed_monthly_amount' => $this->catalogAmountForCompany($company),
                'trial_started_at' => $trialStart->toDateString(),
                'trial_ends_at' => $trialEnd->toDateString(),
                'subscription_start_date' => $trialEnd->toDateString(),
            ]
        );

        $this->backfillTrialDates($profile, $trialStart);

        $packageKey = trim((string) ($company->package_key ?? ''));
        if (
            $packageKey !== ''
            && $profile->billing_mode === CompanyBillingProfile::MODE_PACKAGE
            && $profile->platform_billing_package_id === null
        ) {
            $this->assignBillingPackage($profile, $packageKey);
        }

        $this->lockAgreedMonthlyAmountIfMissing($profile);

        return $profile->fresh(['package', 'company']) ?? $profile;
    }

    public function syncBillingPackageFromCompany(Company $company, bool $refreshAgreedAmount = false): CompanyBillingProfile
    {
        $profile = $this->ensureProfile($company);
        $packageKey = trim((string) ($company->package_key ?? ''));
        if ($packageKey === '' || $profile->billing_mode !== CompanyBillingProfile::MODE_PACKAGE) {
            return $profile->fresh(['package', 'company']) ?? $profile;
        }

        $billingPackage = $this->ensurePlatformPackage($packageKey);
        $catalogAmount = round(max(0, (float) ($this->pricing->monthlyAmountForKey($packageKey) ?? $billingPackage->monthly_amount ?? 0)), 2);
        $packageChanged = (int) ($profile->platform_billing_package_id ?? 0) !== (int) $billingPackage->id;
        $updates = [
            'platform_billing_package_id' => $billingPackage->id,
        ];
        if ($refreshAgreedAmount || $packageChanged || $profile->agreed_monthly_amount === null) {
            $updates['agreed_monthly_amount'] = $catalogAmount;
        }

        $profile->fill($updates);
        $profile->save();
        $profile->setRelation('package', $billingPackage);
        $profile->setRelation('company', $company);

        return $profile;
    }

    private function backfillTrialDates(CompanyBillingProfile $profile, Carbon $fallbackStart): void
    {
        $dirty = false;
        if ($profile->trial_started_at === null) {
            $profile->trial_started_at = $profile->subscription_start_date
                ? Carbon::parse($profile->subscription_start_date)->toDateString()
                : $fallbackStart->toDateString();
            $dirty = true;
        }
        if ($profile->subscription_start_date === null) {
            $profile->subscription_start_date = $profile->trial_started_at
                ? Carbon::parse($profile->trial_started_at)->toDateString()
                : $fallbackStart->toDateString();
            $dirty = true;
        }
        if ($profile->trial_ends_at === null) {
            $profile->trial_ends_at = $profile->subscription_start_date
                ? Carbon::parse($profile->subscription_start_date)->toDateString()
                : $fallbackStart->toDateString();
            $dirty = true;
        }
        if ($dirty) {
            $profile->save();
        }
    }

    private function freeMonthsForCompany(Company $company): int
    {
        $key = trim((string) ($company->package_key ?? ''));
        if ($key === '') {
            return 0;
        }
        $package = $this->pricing->packageByKey($key);
        if (! is_array($package)) {
            return 0;
        }

        return $this->pricing->packageFreeMonths($package);
    }

    public function isInTrial(CompanyBillingProfile $profile, ?CarbonInterface $asOf = null): bool
    {
        if (! $profile->trial_ends_at) {
            return false;
        }
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();
        if ($this->calculator->isEnded($profile, $asOf)) {
            return false;
        }
        $ends = Carbon::parse($profile->trial_ends_at)->startOfDay();

        return $asOf->lt($ends);
    }

    public function trialDaysRemaining(CompanyBillingProfile $profile, ?CarbonInterface $asOf = null): ?int
    {
        if (! $this->isInTrial($profile, $asOf)) {
            return null;
        }
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();
        $ends = Carbon::parse($profile->trial_ends_at)->startOfDay();

        return max(0, (int) $asOf->diffInDays($ends, false));
    }

    public function hasDeclinedTrial(CompanyBillingProfile $profile): bool
    {
        return trim((string) ($profile->pending_change_type ?? '')) === CompanySubscriptionChange::TYPE_TRIAL_END;
    }

    /**
     * Stop het jaarcontract tijdens de proef: geen incasso, toegang blijft tot trial_ends_at.
     */
    public function endTrialAndDeactivate(Company $company, ?CarbonInterface $asOf = null): CompanyBillingProfile
    {
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();
        $profile = $this->ensureProfile($company);
        if (! $this->isInTrial($profile, $asOf)) {
            throw new RuntimeException('De proefperiode is al voorbij; het jaarcontract is ingegaan.');
        }
        if ($this->hasDeclinedTrial($profile)) {
            return $profile->fresh(['package', 'company']) ?? $profile;
        }

        $trialEnd = Carbon::parse($profile->trial_ends_at)->startOfDay();
        $currentKey = trim((string) ($company->package_key ?? ''));
        $fromAmount = $profile->resolveMonthlyAmount();

        $this->clearPendingChange($profile, false);
        $profile->update([
            'pending_change_type' => CompanySubscriptionChange::TYPE_TRIAL_END,
            'pending_package_key' => null,
            'pending_change_effective_on' => $trialEnd->toDateString(),
        ]);

        $this->recordChange($profile, CompanySubscriptionChange::TYPE_TRIAL_END, [
            'status' => CompanySubscriptionChange::STATUS_SCHEDULED,
            'from_package_key' => $currentKey !== '' ? $currentKey : null,
            'to_package_key' => null,
            'from_monthly_amount' => $fromAmount,
            'to_monthly_amount' => 0,
            'effective_on' => $trialEnd->toDateString(),
        ]);

        $fresh = $profile->fresh(['package', 'company']) ?? $profile;
        app(PlatformBillingService::class)->cancelMollieSubscriptionIfNeeded($fresh, $asOf);

        return $fresh->fresh(['package', 'company']) ?? $fresh;
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(Company $company, ?CarbonInterface $asOf = null): array
    {
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();
        $profile = $this->ensureProfile($company);
        $company->refresh();
        $currentKey = trim((string) ($company->package_key ?? ''));
        $currentPackage = $currentKey !== '' ? $this->pricing->packageByKey($currentKey) : null;
        $monthlyAmount = $profile->resolveMonthlyAmount();
        if ($monthlyAmount <= 0 && $currentKey !== '') {
            $monthlyAmount = (float) ($this->pricing->monthlyAmountForKey($currentKey) ?? 0);
        }
        $start = $this->contractStart($profile);
        $billingStart = $profile->subscription_start_date
            ? Carbon::parse($profile->subscription_start_date)->startOfDay()
            : $start;
        $freeMonths = $billingStart->greaterThan($start)
            ? (int) round($start->diffInMonths($billingStart))
            : 0;
        $anniversary = $this->contractAnniversary($profile);
        $pastFirstYear = $this->isPastFirstYear($profile, $asOf);
        $changeDate = $this->nextAllowedChangeDate($profile, $asOf);
        $pendingType = trim((string) ($profile->pending_change_type ?? ''));
        $pendingPackage = $profile->pending_package_key
            ? $this->pricing->packageByKey((string) $profile->pending_package_key)
            : null;

        return [
            'company' => $company,
            'profile' => $profile,
            'current_key' => $currentKey,
            'current_name' => $currentPackage['name'] ?? ($currentKey !== '' ? $currentKey : 'Geen pakket'),
            'current_amount' => $monthlyAmount,
            'current_amount_label' => $this->pricing->displayAmount(number_format($monthlyAmount, 2, '.', '')),
            'start_date' => $start,
            'contract_end_date' => $anniversary,
            'past_first_year' => $pastFirstYear,
            'change_effective_on' => $changeDate,
            'cancel_allowed' => $pendingType !== CompanySubscriptionChange::TYPE_CANCEL,
            'pending_type' => $pendingType !== '' ? $pendingType : null,
            'pending_package_key' => $profile->pending_package_key,
            'pending_package_name' => $pendingPackage['name'] ?? $profile->pending_package_key,
            'pending_effective_on' => $profile->pending_change_effective_on
                ? Carbon::parse($profile->pending_change_effective_on)->startOfDay()
                : null,
            'ended' => $this->calculator->isEnded($profile, $asOf),
            'in_trial' => $this->isInTrial($profile, $asOf),
            'trial_declined' => $this->hasDeclinedTrial($profile),
            'trial_ends_at' => $profile->trial_ends_at
                ? Carbon::parse($profile->trial_ends_at)->startOfDay()
                : null,
            'trial_days_remaining' => $this->trialDaysRemaining($profile, $asOf),
            'billing_start_date' => $billingStart,
            'free_months' => $freeMonths,
            'packages' => $this->catalogFor($company),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function catalogFor(Company $company): array
    {
        $currentKey = trim((string) ($company->package_key ?? ''));
        $currentRank = $this->pricing->packageRank($currentKey);
        $out = [];
        foreach (array_values($this->pricing->get()['packages'] ?? []) as $index => $package) {
            if (! is_array($package)) {
                continue;
            }
            $key = trim((string) ($package['key'] ?? ''));
            if ($key === '') {
                continue;
            }
            $amount = $this->pricing->monthlyAmountForKey($key) ?? 0.0;
            $out[] = [
                'key' => $key,
                'name' => (string) ($package['name'] ?? $key),
                'audience' => (string) ($package['audience'] ?? ''),
                'amount' => $amount,
                'amount_label' => $this->pricing->displayAmount((string) ($package['price'] ?? '')),
                'is_current' => strcasecmp($key, $currentKey) === 0,
                'is_upgrade' => $currentRank !== null && $index > $currentRank,
                'is_downgrade' => $currentRank !== null && $index < $currentRank,
                'features' => is_array($package['features'] ?? null) ? $package['features'] : [],
            ];
        }

        return $out;
    }

    public function contractStart(CompanyBillingProfile $profile): Carbon
    {
        if ($profile->trial_started_at) {
            return Carbon::parse($profile->trial_started_at)->startOfDay();
        }

        if ($profile->subscription_start_date) {
            return Carbon::parse($profile->subscription_start_date)->startOfDay();
        }

        $created = $profile->company?->created_at;

        return $created
            ? Carbon::parse($created)->startOfDay()
            : now()->startOfDay();
    }

    public function contractAnniversary(CompanyBillingProfile $profile): Carbon
    {
        return $this->contractStart($profile)->copy()->addYear();
    }

    public function isPastFirstYear(CompanyBillingProfile $profile, ?CarbonInterface $asOf = null): bool
    {
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();

        return $asOf->greaterThanOrEqualTo($this->contractAnniversary($profile));
    }

    public function nextAllowedChangeDate(CompanyBillingProfile $profile, ?CarbonInterface $asOf = null): Carbon
    {
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();
        if ($this->isPastFirstYear($profile, $asOf)) {
            return $asOf->copy()->endOfMonth()->startOfDay();
        }

        return $this->contractAnniversary($profile);
    }

    public function upgrade(Company $company, string $packageKey, ?CarbonInterface $asOf = null): CompanyBillingProfile
    {
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();
        $profile = $this->ensureProfile($company);
        $this->assertChangeable($profile, $asOf);
        $target = $this->assertPackage($packageKey);
        $currentKey = trim((string) ($company->package_key ?? ''));
        $this->assertDirection($currentKey, $target['key'], 'upgrade');

        $fromAmount = $profile->resolveMonthlyAmount();
        if ($fromAmount <= 0 && $currentKey !== '') {
            $fromAmount = (float) ($this->pricing->monthlyAmountForKey($currentKey) ?? 0);
        }
        $toAmount = (float) ($this->pricing->monthlyAmountForKey($target['key']) ?? 0);

        $profile->subscription_end_date = null;
        $this->clearPendingChange($profile, false);
        $this->applyPackage($company, $profile, $target['key']);
        $profile = $profile->fresh(['package', 'company']) ?? $profile;

        $proration = $this->prorationForRemainingMonth($fromAmount, $toAmount, $asOf);
        if ($proration > 0) {
            $label = 'Upgrade naar '.$target['name'].' vanaf '.$asOf->translatedFormat('j F Y');
            $this->collectProration($profile, $proration, $label);
        }

        $this->recordChange($profile, CompanySubscriptionChange::TYPE_UPGRADE, [
            'status' => CompanySubscriptionChange::STATUS_APPLIED,
            'from_package_key' => $currentKey !== '' ? $currentKey : null,
            'to_package_key' => $target['key'],
            'from_monthly_amount' => $fromAmount,
            'to_monthly_amount' => $toAmount,
            'effective_on' => $asOf->toDateString(),
            'applied_at' => now(),
        ]);

        $this->syncMolliePlan($profile->fresh(['package', 'company']) ?? $profile);

        return $profile->fresh(['package', 'company']) ?? $profile;
    }

    public function scheduleDowngrade(Company $company, string $packageKey, ?CarbonInterface $asOf = null): CompanyBillingProfile
    {
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();
        $profile = $this->ensureProfile($company);
        $this->assertChangeable($profile, $asOf);
        $target = $this->assertPackage($packageKey);
        $currentKey = trim((string) ($company->package_key ?? ''));
        $this->assertDirection($currentKey, $target['key'], 'downgrade');

        $effectiveOn = $this->nextAllowedChangeDate($profile, $asOf);
        $fromAmount = $profile->resolveMonthlyAmount();
        if ($fromAmount <= 0 && $currentKey !== '') {
            $fromAmount = (float) ($this->pricing->monthlyAmountForKey($currentKey) ?? 0);
        }
        $toAmount = (float) ($this->pricing->monthlyAmountForKey($target['key']) ?? 0);

        $profile->subscription_end_date = null;
        $this->clearPendingChange($profile, false);
        $profile->update([
            'pending_change_type' => CompanySubscriptionChange::TYPE_DOWNGRADE,
            'pending_package_key' => $target['key'],
            'pending_change_effective_on' => $effectiveOn->toDateString(),
        ]);

        $this->recordChange($profile, CompanySubscriptionChange::TYPE_DOWNGRADE, [
            'status' => CompanySubscriptionChange::STATUS_SCHEDULED,
            'from_package_key' => $currentKey !== '' ? $currentKey : null,
            'to_package_key' => $target['key'],
            'from_monthly_amount' => $fromAmount,
            'to_monthly_amount' => $toAmount,
            'effective_on' => $effectiveOn->toDateString(),
        ]);

        return $profile->fresh(['package', 'company']) ?? $profile;
    }

    public function scheduleCancel(Company $company, ?CarbonInterface $asOf = null): CompanyBillingProfile
    {
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();
        $profile = $this->ensureProfile($company);
        $this->assertChangeable($profile, $asOf);
        $effectiveOn = $this->nextAllowedChangeDate($profile, $asOf);
        $currentKey = trim((string) ($company->package_key ?? ''));
        $fromAmount = $profile->resolveMonthlyAmount();

        $this->clearPendingChange($profile, false);
        $profile->update([
            'pending_change_type' => CompanySubscriptionChange::TYPE_CANCEL,
            'pending_package_key' => null,
            'pending_change_effective_on' => $effectiveOn->toDateString(),
            'subscription_end_date' => $effectiveOn->toDateString(),
        ]);

        $this->recordChange($profile, CompanySubscriptionChange::TYPE_CANCEL, [
            'status' => CompanySubscriptionChange::STATUS_SCHEDULED,
            'from_package_key' => $currentKey !== '' ? $currentKey : null,
            'to_package_key' => null,
            'from_monthly_amount' => $fromAmount,
            'to_monthly_amount' => 0,
            'effective_on' => $effectiveOn->toDateString(),
        ]);

        $this->syncMolliePlan($profile->fresh(['package', 'company']) ?? $profile);

        return $profile->fresh(['package', 'company']) ?? $profile;
    }

    /**
     * Super-admin noodbeeindiging: altijd per einde van de lopende maand,
     * ongeacht het jaarcontract. De volgende Mollie-incasso wordt gestopt.
     */
    public function emergencyTerminateAtMonthEnd(Company $company, ?CarbonInterface $asOf = null): CompanyBillingProfile
    {
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();
        $profile = $this->ensureProfile($company);

        if ($this->calculator->isEnded($profile, $asOf)
            && trim((string) ($profile->pending_change_type ?? '')) !== CompanySubscriptionChange::TYPE_CANCEL) {
            throw new RuntimeException('Dit abonnement is al beëindigd.');
        }

        if ($profile->billing_mode === CompanyBillingProfile::MODE_FREE) {
            throw new RuntimeException('Een gratis abonnement heeft geen lopende incasso om te beëindigen.');
        }

        $effectiveOn = $asOf->copy()->endOfMonth()->startOfDay();
        $currentKey = trim((string) ($company->package_key ?? ''));
        $fromAmount = $profile->resolveMonthlyAmount();

        $alreadyScheduledForMonthEnd = trim((string) ($profile->pending_change_type ?? '')) === CompanySubscriptionChange::TYPE_CANCEL
            && $profile->pending_change_effective_on
            && Carbon::parse($profile->pending_change_effective_on)->startOfDay()->equalTo($effectiveOn)
            && $profile->subscription_end_date
            && Carbon::parse($profile->subscription_end_date)->startOfDay()->equalTo($effectiveOn);

        if ($alreadyScheduledForMonthEnd) {
            $this->syncMolliePlan($profile->fresh(['package', 'company']) ?? $profile);

            return $profile->fresh(['package', 'company']) ?? $profile;
        }

        $this->clearPendingChange($profile, true);
        $profile->update([
            'pending_change_type' => CompanySubscriptionChange::TYPE_CANCEL,
            'pending_package_key' => null,
            'pending_change_effective_on' => $effectiveOn->toDateString(),
            'subscription_end_date' => $effectiveOn->toDateString(),
        ]);

        $this->recordChange($profile, CompanySubscriptionChange::TYPE_CANCEL, [
            'status' => CompanySubscriptionChange::STATUS_SCHEDULED,
            'from_package_key' => $currentKey !== '' ? $currentKey : null,
            'to_package_key' => null,
            'from_monthly_amount' => $fromAmount,
            'to_monthly_amount' => 0,
            'effective_on' => $effectiveOn->toDateString(),
        ]);

        $this->syncMolliePlan($profile->fresh(['package', 'company']) ?? $profile);

        return $profile->fresh(['package', 'company']) ?? $profile;
    }

    public function withdrawPending(Company $company): CompanyBillingProfile
    {
        $profile = $this->ensureProfile($company);
        $type = trim((string) ($profile->pending_change_type ?? ''));
        if ($type === '') {
            throw new RuntimeException('Er staat geen wijziging klaar om in te trekken.');
        }

        if (in_array($type, [
            CompanySubscriptionChange::TYPE_CANCEL,
            CompanySubscriptionChange::TYPE_TRIAL_END,
        ], true)) {
            $profile->subscription_end_date = null;
        }

        $this->clearPendingChange($profile, true);
        $this->syncMolliePlan($profile->fresh(['package', 'company']) ?? $profile);

        return $profile->fresh(['package', 'company']) ?? $profile;
    }

    public function applyDueChanges(?CarbonInterface $asOf = null): int
    {
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();
        $count = 0;

        CompanyBillingProfile::query()
            ->with('company')
            ->whereNotNull('pending_change_type')
            ->whereNotNull('pending_change_effective_on')
            ->whereDate('pending_change_effective_on', '<=', $asOf->toDateString())
            ->chunkById(50, function ($profiles) use ($asOf, &$count) {
                foreach ($profiles as $profile) {
                    if ($this->applyDueChange($profile, $asOf)) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    public function applyDueChange(CompanyBillingProfile $profile, ?CarbonInterface $asOf = null): bool
    {
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();
        $type = trim((string) ($profile->pending_change_type ?? ''));
        $effective = $profile->pending_change_effective_on
            ? Carbon::parse($profile->pending_change_effective_on)->startOfDay()
            : null;
        if ($type === '' || ! $effective || $effective->greaterThan($asOf)) {
            return false;
        }

        $company = $profile->company;
        if (! $company) {
            return false;
        }

        if ($type === CompanySubscriptionChange::TYPE_DOWNGRADE) {
            $targetKey = trim((string) ($profile->pending_package_key ?? ''));
            if ($targetKey === '') {
                return false;
            }
            $this->applyPackage($company, $profile, $targetKey);
            $this->markScheduledApplied($profile, $type);
            $this->clearPendingChange($profile, false);
            $this->syncMolliePlan($profile->fresh(['package', 'company']) ?? $profile);

            return true;
        }

        if ($type === CompanySubscriptionChange::TYPE_CANCEL) {
            $this->markScheduledApplied($profile, $type);
            $this->clearPendingChange($profile, false);
            app(PlatformBillingService::class)->cancelMollieSubscriptionIfNeeded(
                $profile->fresh(['package', 'company']) ?? $profile,
                $asOf
            );

            return true;
        }

        if ($type === CompanySubscriptionChange::TYPE_TRIAL_END) {
            $company->update(['is_active' => false]);
            $profile->subscription_end_date = $effective->toDateString();
            $this->markScheduledApplied($profile, $type);
            $this->clearPendingChange($profile, false);
            app(PlatformBillingService::class)->cancelMollieSubscriptionIfNeeded(
                $profile->fresh(['package', 'company']) ?? $profile,
                $asOf
            );

            return true;
        }

        return false;
    }

    public function prorationForRemainingMonth(float $fromAmount, float $toAmount, CarbonInterface $asOf): float
    {
        $delta = round($toAmount - $fromAmount, 2);
        if ($delta <= 0) {
            return 0.0;
        }

        $asOf = Carbon::parse($asOf)->startOfDay();
        $end = $asOf->copy()->endOfMonth()->startOfDay();
        $daysInMonth = $asOf->daysInMonth;
        $remainingDays = (int) ($asOf->diffInDays($end) + 1);

        return round($delta * ($remainingDays / $daysInMonth), 2);
    }

    private function applyPackage(Company $company, CompanyBillingProfile $profile, string $packageKey): void
    {
        $company->update(['package_key' => $packageKey]);

        if ($profile->billing_mode === CompanyBillingProfile::MODE_FREE) {
            return;
        }

        $this->assignBillingPackage($profile, $packageKey);
    }

    private function assignBillingPackage(CompanyBillingProfile $profile, string $packageKey): void
    {
        $billingPackage = $this->ensurePlatformPackage($packageKey);
        $amount = (float) ($this->pricing->monthlyAmountForKey($packageKey) ?? $billingPackage->monthly_amount ?? 0);
        $updates = [
            'billing_mode' => CompanyBillingProfile::MODE_PACKAGE,
            'platform_billing_package_id' => $billingPackage->id,
            'custom_monthly_amount' => null,
            'agreed_monthly_amount' => round(max(0, $amount), 2),
        ];
        $profile->fill($updates);
        $profile->save();
        $profile->setRelation('package', $billingPackage);
    }

    private function lockAgreedMonthlyAmountIfMissing(CompanyBillingProfile $profile): void
    {
        if ($profile->billing_mode !== CompanyBillingProfile::MODE_PACKAGE) {
            return;
        }
        if ($profile->agreed_monthly_amount !== null) {
            return;
        }

        $amount = $this->catalogAmountForCompany($profile->company ?? $profile->company()->first());
        if ($amount === null && $profile->package) {
            $amount = round(max(0, (float) $profile->package->monthly_amount), 2);
        }
        if ($amount === null) {
            return;
        }

        $profile->agreed_monthly_amount = $amount;
        $profile->save();
    }

    private function catalogAmountForCompany(?Company $company): ?float
    {
        $key = trim((string) ($company?->package_key ?? ''));
        if ($key === '') {
            return null;
        }
        $amount = $this->pricing->monthlyAmountForKey($key);

        return $amount === null ? null : round(max(0, $amount), 2);
    }

    /**
     * Spiegel NEXA-paketten naar de facturatietabel, zodat tenant-abonnementen dezelfde prijzen gebruiken.
     *
     * @param  array<string, mixed>|null  $pricing
     */
    public function syncPlatformPackagesFromPricing(?array $pricing = null): void
    {
        $pricing = $pricing ?? $this->pricing->get();
        $seenKeys = [];

        foreach ($pricing['packages'] ?? [] as $package) {
            if (! is_array($package)) {
                continue;
            }
            $key = trim((string) ($package['key'] ?? ''));
            if ($key === '') {
                continue;
            }
            $seenKeys[] = $key;
            $this->ensurePlatformPackage($key);
        }

        if ($seenKeys === []) {
            return;
        }

        PlatformBillingPackage::query()
            ->whereNotNull('package_key')
            ->where('package_key', '!=', '')
            ->whereNotIn('package_key', $seenKeys)
            ->update(['is_active' => false]);
    }

    public function ensurePlatformPackage(string $packageKey): PlatformBillingPackage
    {
        $nexa = $this->assertPackage($packageKey);
        $amount = (float) ($this->pricing->monthlyAmountForKey($nexa['key']) ?? 0);
        $existing = PlatformBillingPackage::query()
            ->where('package_key', $nexa['key'])
            ->first();
        if (! $existing) {
            $existing = PlatformBillingPackage::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($nexa['name'])])
                ->first();
        }

        if ($existing) {
            $existing->fill([
                'package_key' => $nexa['key'],
                'name' => $nexa['name'],
                'monthly_amount' => $amount,
                'is_active' => true,
            ]);
            $existing->save();

            return $existing;
        }

        return PlatformBillingPackage::query()->create([
            'package_key' => $nexa['key'],
            'name' => $nexa['name'],
            'description' => $nexa['audience'] ?? null,
            'monthly_amount' => $amount,
            'currency' => 'EUR',
            'is_active' => true,
            'sort_order' => (int) ($this->pricing->packageRank($nexa['key']) ?? 0),
        ]);
    }

    private function collectProration(CompanyBillingProfile $profile, float $amount, string $label): void
    {
        $profile->update([
            'pending_proration_amount' => $amount,
            'pending_proration_label' => $label,
            'pending_proration_applied_at' => null,
        ]);

        $mandate = PlatformPaymentMandate::query()->where('company_id', $profile->company_id)->first();
        if (! $mandate?->isActive() || ! $mandate->mollie_customer_id) {
            return;
        }

        try {
            $remote = $this->mollie->createRecurringPayment(
                (string) $mandate->mollie_customer_id,
                (string) $mandate->mollie_mandate_id,
                $amount,
                $label,
                [
                    'company_id' => $profile->company_id,
                    'type' => 'upgrade_proration',
                ]
            );
            PlatformPayment::query()->create([
                'company_id' => $profile->company_id,
                'type' => PlatformPayment::TYPE_PRORATION,
                'mollie_payment_id' => (string) ($remote['id'] ?? ''),
                'amount' => $amount,
                'currency' => 'EUR',
                'status' => $this->mollie->mapStatus((string) ($remote['status'] ?? 'pending')),
                'mollie_payload' => $remote,
            ]);
            $profile->update(['pending_proration_applied_at' => now()]);
        } catch (Throwable $e) {
            Log::warning('Upgrade-proratie via SEPA mislukt; bedrag gaat mee op de volgende factuur', [
                'company_id' => $profile->company_id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function syncMolliePlan(CompanyBillingProfile $profile): void
    {
        $profile->loadMissing('company');
        $billing = app(PlatformBillingService::class);

        if ($this->calculator->shouldCancelMollieSubscription($profile)) {
            $billing->cancelMollieSubscriptionIfNeeded($profile);

            return;
        }

        if (! $profile->auto_collect_enabled || $profile->billing_mode === CompanyBillingProfile::MODE_FREE) {
            return;
        }

        $mandate = PlatformPaymentMandate::query()->where('company_id', $profile->company_id)->first();
        if (! $mandate?->isActive() || ! $mandate->mollie_customer_id) {
            return;
        }

        $monthlyAmount = $profile->resolveMonthlyAmount();
        if ($monthlyAmount <= 0) {
            $this->stopMollieSubscription($profile, $mandate);

            return;
        }

        if (! $this->mollie->isConfigured()) {
            return;
        }

        $times = $this->calculator->mollieSubscriptionTimes($profile);
        $startDate = $this->calculator->mollieSubscriptionStartDate($profile);
        $endLimitsCharges = $profile->subscription_end_date !== null;

        if ($endLimitsCharges && ($times === null || $times <= 0 || ! $startDate)) {
            $this->stopMollieSubscription($profile, $mandate);

            return;
        }

        $payload = [
            'amount' => [
                'currency' => 'EUR',
                'value' => number_format(max(0.01, round($monthlyAmount, 2)), 2, '.', ''),
            ],
            'interval' => '1 month',
            'description' => mb_substr('SaaS-abonnement '.($profile->company?->name ?? 'Tenant'), 0, 255),
            'method' => 'directdebit',
            'mandateId' => $mandate->mollie_mandate_id,
            'metadata' => [
                'company_id' => $profile->company_id,
                'type' => 'platform_subscription',
            ],
            'webhookUrl' => $this->mollie->webhookUrl(),
        ];
        if ($startDate) {
            $payload['startDate'] = $startDate;
        }
        if ($endLimitsCharges && $times !== null && $times > 0) {
            $payload['times'] = $times;
        }
        if (empty($payload['webhookUrl'])) {
            unset($payload['webhookUrl']);
        }

        try {
            if ($profile->hasActiveMollieSubscription() && ! $endLimitsCharges) {
                $remote = $this->mollie->updateSubscription(
                    (string) $mandate->mollie_customer_id,
                    (string) $profile->mollie_subscription_id,
                    [
                        'amount' => $payload['amount'],
                        'description' => $payload['description'],
                    ]
                );
                $profile->update([
                    'mollie_subscription_status' => (string) ($remote['status'] ?? $profile->mollie_subscription_status),
                    'mollie_subscription_synced_at' => now(),
                ]);

                return;
            }

            if ($profile->hasActiveMollieSubscription()) {
                $this->stopMollieSubscription($profile, $mandate);
            }

            $remote = $this->mollie->createSubscription((string) $mandate->mollie_customer_id, $payload);
            $profile->update([
                'mollie_subscription_id' => (string) ($remote['id'] ?? ''),
                'mollie_subscription_status' => (string) ($remote['status'] ?? 'pending'),
                'mollie_subscription_synced_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Mollie-abonnement bijwerken mislukt', [
                'company_id' => $profile->company_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function stopMollieSubscription(CompanyBillingProfile $profile, PlatformPaymentMandate $mandate): void
    {
        if (! $profile->mollie_subscription_id || ! $mandate->mollie_customer_id) {
            return;
        }

        try {
            $this->mollie->cancelSubscription(
                (string) $mandate->mollie_customer_id,
                (string) $profile->mollie_subscription_id
            );
        } catch (Throwable $e) {
            Log::warning('Mollie-abonnement stoppen mislukt', [
                'company_id' => $profile->company_id,
                'error' => $e->getMessage(),
            ]);
        }

        $profile->update([
            'mollie_subscription_status' => 'canceled',
            'mollie_subscription_synced_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function assertPackage(string $packageKey): array
    {
        $package = $this->pricing->packageByKey($packageKey);
        if ($package === null) {
            throw new RuntimeException('Onbekend pakket.');
        }

        return $package;
    }

    private function assertDirection(string $currentKey, string $targetKey, string $direction): void
    {
        $currentRank = $this->pricing->packageRank($currentKey);
        $targetRank = $this->pricing->packageRank($targetKey);
        if ($currentRank === null || $targetRank === null) {
            throw new RuntimeException('Dit pakket kan niet worden gewijzigd.');
        }
        if ($currentRank === $targetRank) {
            throw new RuntimeException('Dit is al je huidige pakket.');
        }
        if ($direction === 'upgrade' && $targetRank <= $currentRank) {
            throw new RuntimeException('Kies een hoger pakket om te upgraden.');
        }
        if ($direction === 'downgrade' && $targetRank >= $currentRank) {
            throw new RuntimeException('Kies een lager pakket om te downgraden.');
        }
    }

    private function assertChangeable(CompanyBillingProfile $profile, Carbon $asOf): void
    {
        if ($this->hasDeclinedTrial($profile)) {
            throw new RuntimeException('Activeer eerst het abonnement voordat je het pakket wijzigt of opzegt.');
        }
        if ($this->calculator->isEnded($profile, $asOf) && $profile->pending_change_type !== CompanySubscriptionChange::TYPE_CANCEL) {
            throw new RuntimeException('Dit abonnement is beëindigd.');
        }
    }

    private function clearPendingChange(CompanyBillingProfile $profile, bool $withdraw): void
    {
        if ($withdraw) {
            CompanySubscriptionChange::query()
                ->where('company_billing_profile_id', $profile->id)
                ->where('status', CompanySubscriptionChange::STATUS_SCHEDULED)
                ->update([
                    'status' => CompanySubscriptionChange::STATUS_WITHDRAWN,
                    'updated_at' => now(),
                ]);
        }

        $profile->pending_change_type = null;
        $profile->pending_package_key = null;
        $profile->pending_change_effective_on = null;
        $profile->save();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function recordChange(CompanyBillingProfile $profile, string $type, array $attributes): CompanySubscriptionChange
    {
        return CompanySubscriptionChange::query()->create(array_merge([
            'company_id' => $profile->company_id,
            'company_billing_profile_id' => $profile->id,
            'change_type' => $type,
            'requested_at' => now(),
        ], $attributes));
    }

    private function markScheduledApplied(CompanyBillingProfile $profile, string $type): void
    {
        CompanySubscriptionChange::query()
            ->where('company_billing_profile_id', $profile->id)
            ->where('change_type', $type)
            ->where('status', CompanySubscriptionChange::STATUS_SCHEDULED)
            ->update([
                'status' => CompanySubscriptionChange::STATUS_APPLIED,
                'applied_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
