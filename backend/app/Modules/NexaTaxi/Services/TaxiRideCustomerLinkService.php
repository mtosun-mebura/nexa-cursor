<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Traits\UsesModuleDatabase;
use Illuminate\Support\Facades\Schema;

class TaxiRideCustomerLinkService
{
    use UsesModuleDatabase;

    /**
     * Koppel gast-boekingen (zelfde e-mail, geen customer_user_id) aan het ingelogde account.
     */
    public function linkOrphanRidesForUser(User $user, ?int $companyId = null): int
    {
        $email = mb_strtolower(trim((string) ($user->email ?? '')));
        if ($email === '') {
            return 0;
        }

        $conn = $this->moduleConnection();
        if (! Schema::connection($conn)->hasTable('ride_requests')
            || ! Schema::connection($conn)->hasColumn('ride_requests', 'customer_user_id')) {
            return 0;
        }

        $companyId = $companyId ?? $this->resolveCompanyIdForUser($user);

        $query = RideRequest::on($conn)
            ->whereNull('customer_user_id')
            ->whereRaw('LOWER(TRIM(customer_email)) = ?', [$email]);

        if ($companyId > 0) {
            $query->where('company_id', $companyId);
        }

        return (int) $query->update(['customer_user_id' => (int) $user->id]);
    }

    protected function resolveCompanyIdForUser(User $user): ?int
    {
        if ($user->company_id) {
            return (int) $user->company_id;
        }

        if (app()->bound('resolved_tenant_id')) {
            $resolved = (int) app('resolved_tenant_id');
            if ($resolved > 0) {
                return $resolved;
            }
        }

        return null;
    }
}
