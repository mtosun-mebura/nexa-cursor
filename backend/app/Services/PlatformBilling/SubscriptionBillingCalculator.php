<?php

namespace App\Services\PlatformBilling;

use App\Models\CompanyBillingProfile;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class SubscriptionBillingCalculator
{
    public const BILLING_ANCHOR_DAY = 1;

    /**
     * @return array<int, array{key: string, label: string, fraction: float, days: int, days_in_month: int}>
     */
    public function advanceCoverageSegments(CompanyBillingProfile $profile, ?CarbonInterface $asOf = null): array
    {
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();

        if ($this->isEnded($profile, $asOf)) {
            return [];
        }

        if (! $profile->subscription_start_date) {
            $month = $asOf->copy()->startOfMonth();

            return [$this->segmentForMonth($month, $month->copy()->endOfMonth())];
        }

        $start = $this->resolvedStartDate($profile, $asOf);
        if (! $start || $start->greaterThan($asOf)) {
            return [];
        }

        if ($start->isSameDay($asOf) && $start->day === self::BILLING_ANCHOR_DAY) {
            $month = $start->copy()->startOfMonth();

            return [$this->segmentForMonth($month, $month->copy()->endOfMonth())];
        }

        if ($start->isSameMonth($asOf)) {
            $partial = $this->segmentForMonth($start, $start->copy()->endOfMonth());
            $nextMonth = $start->copy()->addMonthNoOverflow()->startOfMonth();

            if ($this->monthIsWithinContract($profile, $nextMonth)) {
                return [
                    $partial,
                    $this->segmentForMonth($nextMonth, $nextMonth->copy()->endOfMonth()),
                ];
            }

            return [$partial];
        }

        $month = $asOf->copy()->startOfMonth();

        if (! $this->monthIsWithinContract($profile, $month)) {
            return [];
        }

        return [$this->segmentForMonth($month, $month->copy()->endOfMonth())];
    }

    public function proratedSubscriptionAmount(CompanyBillingProfile $profile, ?CarbonInterface $asOf = null): float
    {
        $monthly = $profile->resolveMonthlyAmount();
        if ($monthly <= 0) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($this->advanceCoverageSegments($profile, $asOf) as $segment) {
            $total += round($profile->subscriptionBaseAmount() * $segment['fraction'], 2);
        }

        $discountPercent = $profile->discountPercent();
        if ($discountPercent > 0) {
            $total = round($total * (1 - ($discountPercent / 100)), 2);
        }

        return round($total, 2);
    }

    public function proratedSubscriptionDiscountAmount(CompanyBillingProfile $profile, ?CarbonInterface $asOf = null): float
    {
        $gross = 0.0;
        foreach ($this->advanceCoverageSegments($profile, $asOf) as $segment) {
            $gross += round($profile->subscriptionBaseAmount() * $segment['fraction'], 2);
        }

        $percent = $profile->discountPercent();
        if ($percent <= 0 || $gross <= 0) {
            return 0.0;
        }

        return round($gross * ($percent / 100), 2);
    }

    public function subscriptionLineDescriptionForSegment(string $baseLabel, array $segment): string
    {
        if ($segment['fraction'] >= 0.999) {
            return $baseLabel.' — '.$segment['label'];
        }

        return $baseLabel.' — '.$segment['label'].' ('.$segment['days'].'/'.$segment['days_in_month'].' dagen)';
    }

    public function mollieSubscriptionStartDate(CompanyBillingProfile $profile, ?CarbonInterface $asOf = null): ?string
    {
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();

        if (! $profile->subscription_start_date) {
            return $asOf->copy()->addMonthNoOverflow()->day(self::BILLING_ANCHOR_DAY)->toDateString();
        }

        $start = $this->resolvedStartDate($profile, $asOf);
        if (! $start) {
            return null;
        }

        if ($start->isSameMonth($asOf) && $start->day !== self::BILLING_ANCHOR_DAY) {
            return $start->copy()->addMonthsNoOverflow(2)->day(self::BILLING_ANCHOR_DAY)->toDateString();
        }

        if ($start->greaterThan($asOf)) {
            return $start->copy()->day(self::BILLING_ANCHOR_DAY)->toDateString();
        }

        return $asOf->copy()->addMonthNoOverflow()->day(self::BILLING_ANCHOR_DAY)->toDateString();
    }

    public function mollieSubscriptionTimes(CompanyBillingProfile $profile, ?CarbonInterface $asOf = null): ?int
    {
        $end = $this->resolvedEndDate($profile);
        $subscriptionStart = $this->mollieSubscriptionStartDate($profile, $asOf);
        if (! $end || ! $subscriptionStart) {
            return null;
        }

        $start = Carbon::parse($subscriptionStart)->startOfDay();
        $lastChargeMonth = $end->copy()->subMonthNoOverflow()->startOfMonth();
        if ($lastChargeMonth->lessThan($start)) {
            return null;
        }

        return $start->diffInMonths($lastChargeMonth) + 1;
    }

    public function shouldCancelMollieSubscription(CompanyBillingProfile $profile, ?CarbonInterface $asOf = null): bool
    {
        $end = $this->resolvedEndDate($profile);
        if (! $end || ! $profile->mollie_subscription_id) {
            return false;
        }

        $asOf = Carbon::parse($asOf ?? now())->startOfDay();

        return $asOf->greaterThanOrEqualTo($end->copy()->startOfDay());
    }

    public function isBillable(CompanyBillingProfile $profile, ?CarbonInterface $asOf = null): bool
    {
        if ($profile->billing_mode === CompanyBillingProfile::MODE_FREE) {
            return false;
        }

        $asOf = Carbon::parse($asOf ?? now())->startOfDay();

        if ($this->isEnded($profile, $asOf)) {
            return false;
        }

        if ($profile->subscription_start_date) {
            $start = $this->resolvedStartDate($profile, $asOf);
            if (! $start || $start->greaterThan($asOf)) {
                return false;
            }
        }

        return true;
    }

    public function billingPeriodLabelForDate(CarbonInterface $date): string
    {
        return $date->format('Y-m');
    }

    public function resolvedStartDate(CompanyBillingProfile $profile, ?CarbonInterface $fallback = null): ?Carbon
    {
        if ($profile->subscription_start_date) {
            return Carbon::parse($profile->subscription_start_date)->startOfDay();
        }

        if ($fallback) {
            return Carbon::parse($fallback)->startOfDay();
        }

        return null;
    }

    public function resolvedEndDate(CompanyBillingProfile $profile): ?Carbon
    {
        if (! $profile->subscription_end_date) {
            return null;
        }

        return Carbon::parse($profile->subscription_end_date)->startOfDay();
    }

    public function isEnded(CompanyBillingProfile $profile, ?CarbonInterface $asOf = null): bool
    {
        $end = $this->resolvedEndDate($profile);
        if (! $end) {
            return false;
        }

        $asOf = Carbon::parse($asOf ?? now())->startOfDay();

        return $asOf->greaterThanOrEqualTo($end);
    }

    private function monthIsWithinContract(CompanyBillingProfile $profile, CarbonInterface $month): bool
    {
        $monthStart = Carbon::parse($month)->startOfMonth();
        $start = $this->resolvedStartDate($profile, $monthStart);
        if ($start && $monthStart->lessThan($start->copy()->startOfMonth())) {
            return false;
        }

        $end = $this->resolvedEndDate($profile);
        if ($end && $monthStart->greaterThanOrEqualTo($end->copy()->startOfMonth())) {
            return false;
        }

        return true;
    }

    /**
     * @return array{key: string, label: string, fraction: float, days: int, days_in_month: int}
     */
    private function segmentForMonth(CarbonInterface $from, CarbonInterface $to): array
    {
        $from = Carbon::parse($from)->startOfDay();
        $to = Carbon::parse($to)->startOfDay();
        $month = $from->copy()->startOfMonth();
        $daysInMonth = $month->daysInMonth;
        $days = (int) ($from->diffInDays($to) + 1);

        return [
            'key' => $month->format('Y-m'),
            'label' => $month->translatedFormat('F Y'),
            'fraction' => $days / $daysInMonth,
            'days' => $days,
            'days_in_month' => $daysInMonth,
        ];
    }
}
