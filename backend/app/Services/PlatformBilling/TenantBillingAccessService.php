<?php

namespace App\Services\PlatformBilling;

use App\Models\Company;
use App\Models\CompanyBillingProfile;
use App\Models\PlatformInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;

class TenantBillingAccessService
{
    public const NONE = 'none';

    public const BOOKINGS = 'bookings';

    public const FULL = 'full';

    public const SOURCE_DUNNING = 'dunning';

    public const SOURCE_MANUAL = 'manual';

    public function restrictionFor(?Company $company): string
    {
        if (! $company || ! $this->accessRestrictionColumnExists()) {
            return self::NONE;
        }

        $mode = $company->relationLoaded('billingProfile')
            ? $company->billingProfile?->access_restriction
            : $company->billingProfile()->value('access_restriction');

        return in_array($mode, [self::BOOKINGS, self::FULL], true) ? $mode : self::NONE;
    }

    private function accessRestrictionColumnExists(): bool
    {
        static $exists = null;

        if ($exists === null) {
            $exists = Schema::hasTable('company_billing_profiles')
                && Schema::hasColumn('company_billing_profiles', 'access_restriction');
        }

        return $exists;
    }

    public function isBookingBlocked(?Company $company): bool
    {
        return in_array($this->restrictionFor($company), [self::BOOKINGS, self::FULL], true);
    }

    public function isFullyBlocked(?Company $company): bool
    {
        return $this->restrictionFor($company) === self::FULL;
    }

    public function jsonBookingDenied(int $status = 403): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->bookingDeniedMessage(),
        ], $status);
    }

    public function bookingDeniedMessage(): string
    {
        return 'Online boeken is tijdelijk niet beschikbaar omdat de NEXA-factuur nog openstaat.';
    }

    public function fullBlockMessage(): string
    {
        return 'Deze omgeving is tijdelijk geblokkeerd wegens een openstaande NEXA-factuur. Neem contact op met NEXA.';
    }

    public function applyRestriction(
        CompanyBillingProfile $profile,
        string $mode,
        string $source,
        ?PlatformInvoice $invoice = null,
    ): void {
        $mode = in_array($mode, [self::BOOKINGS, self::FULL], true) ? $mode : self::BOOKINGS;
        $current = in_array($profile->access_restriction, [self::BOOKINGS, self::FULL], true)
            ? $profile->access_restriction
            : self::NONE;

        if ($current === self::FULL && $mode === self::BOOKINGS) {
            $mode = self::FULL;
        }

        $profile->fill([
            'access_restriction' => $mode,
            'access_restriction_source' => $source,
            'access_restricted_at' => $profile->access_restricted_at ?? now(),
            'access_restricted_invoice_id' => $invoice?->id ?? $profile->access_restricted_invoice_id,
        ]);
        $profile->save();
    }

    public function clearRestriction(CompanyBillingProfile $profile, bool $waiveOverdueBlocks = false): void
    {
        $companyId = (int) $profile->company_id;

        $profile->fill([
            'access_restriction' => self::NONE,
            'access_restriction_source' => null,
            'access_restricted_at' => null,
            'access_restricted_invoice_id' => null,
        ]);
        $profile->save();

        if ($waiveOverdueBlocks && $companyId > 0) {
            PlatformInvoice::query()
                ->where('company_id', $companyId)
                ->whereNull('paid_at')
                ->where(function ($q) {
                    $q->whereNotNull('blocked_at')->orWhereNotNull('second_reminder_sent_at');
                })
                ->whereNull('block_waived_at')
                ->update(['block_waived_at' => now()]);
        }
    }

    public function overdueBlockMode(CompanyBillingProfile $profile): string
    {
        $mode = (string) ($profile->overdue_block_mode ?? self::BOOKINGS);

        return $mode === self::FULL ? self::FULL : self::BOOKINGS;
    }
}
