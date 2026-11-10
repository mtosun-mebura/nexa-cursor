<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\User;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\TaxiDriverNotification;
use App\Modules\NexaTaxi\Models\TaxiDriverProfile;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Support\TaxiDriverComplaintSchema;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class TaxiDriverBlockService
{
    public function __construct(
        protected TaxiDriverNotificationService $notifications
    ) {}

    public function isBlocked(string $conn, int $driverId): bool
    {
        if ($driverId <= 0) {
            return false;
        }
        TaxiDriverComplaintSchema::ensure($conn);
        $profile = TaxiDriverProfile::on($conn)->find($driverId);

        return $profile && $profile->app_blocked_at !== null;
    }

    /**
     * @return array{blocked: bool, blocked_at: ?string, reason: ?string, complaint_id: ?int}|null
     */
    public function blockedPayload(string $conn, int $driverId): ?array
    {
        if ($driverId <= 0) {
            return null;
        }
        TaxiDriverComplaintSchema::ensure($conn);
        $profile = TaxiDriverProfile::on($conn)->find($driverId);
        if (! $profile || $profile->app_blocked_at === null) {
            return null;
        }

        return [
            'blocked' => true,
            'blocked_at' => $profile->app_blocked_at?->toIso8601String(),
            'reason' => $profile->app_blocked_reason,
            'complaint_id' => $profile->app_blocked_complaint_id ? (int) $profile->app_blocked_complaint_id : null,
        ];
    }

    public function block(
        string $conn,
        int $driverId,
        ?int $byUserId,
        ?string $reason = null,
        ?int $complaintId = null,
        ?int $companyId = null
    ): TaxiDriverProfile {
        TaxiDriverComplaintSchema::ensure($conn);
        if ($driverId <= 0) {
            throw ValidationException::withMessages(['driver_id' => ['Ongeldige chauffeur.']]);
        }

        $profile = TaxiDriverProfile::on($conn)->find($driverId);
        if (! $profile) {
            $profile = new TaxiDriverProfile([
                'driver_id' => $driverId,
                'rating_count' => 0,
                'rating_sum' => 0,
            ]);
            $profile->setConnection($conn);
        }

        $profile->app_blocked_at = now();
        $profile->app_blocked_reason = $reason !== null && trim($reason) !== ''
            ? trim($reason)
            : 'Geblokkeerd na klacht.';
        $profile->app_blocked_by = $byUserId;
        $profile->app_blocked_complaint_id = $complaintId;
        $profile->save();

        $user = User::query()->find($driverId);
        if ($user) {
            $user->tokens()->where('name', 'taxi-driver')->delete();
            try {
                $availability = DriverAvailability::on($conn)
                    ->where('driver_id', $driverId)
                    ->first();
                if ($availability && $availability->is_online) {
                    $availability->is_online = false;
                    $availability->save();
                }
            } catch (\Throwable) {
                // ignore
            }
        }

        $this->notifications->notify($conn, $driverId, [
            'type' => TaxiDriverNotification::TYPE_BLOCKED,
            'title' => 'Account geblokkeerd',
            'body' => $profile->app_blocked_reason ?: 'Je chauffeuraccount is geblokkeerd. Neem contact op met de beheerder.',
            'company_id' => $companyId,
            'related_complaint_id' => $complaintId,
            'created_by' => $byUserId,
            'push' => true,
        ]);

        return $profile;
    }

    public function unblock(string $conn, int $driverId): TaxiDriverProfile
    {
        TaxiDriverComplaintSchema::ensure($conn);
        $profile = TaxiDriverProfile::on($conn)->find($driverId);
        if (! $profile) {
            throw ValidationException::withMessages(['driver_id' => ['Chauffeur niet gevonden.']]);
        }

        $profile->app_blocked_at = null;
        $profile->app_blocked_reason = null;
        $profile->app_blocked_by = null;
        $profile->app_blocked_complaint_id = null;
        $profile->save();

        return $profile;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listBlocked(string $conn, ?int $companyId = null): array
    {
        TaxiDriverComplaintSchema::ensure($conn);
        if (! Schema::connection($conn)->hasColumn('taxi_driver_profiles', 'app_blocked_at')) {
            return [];
        }

        $rows = TaxiDriverProfile::on($conn)
            ->whereNotNull('app_blocked_at')
            ->orderByDesc('app_blocked_at')
            ->get();

        $driverIds = $rows->pluck('driver_id')->map(fn ($id) => (int) $id)->all();
        $users = User::query()->whereIn('id', $driverIds)->with('company')->get()->keyBy('id');

        $out = [];
        foreach ($rows as $row) {
            $user = $users->get((int) $row->driver_id);
            if ($companyId && $user && (int) $user->company_id !== $companyId) {
                continue;
            }
            $vehicleId = DriverAvailability::vehicleIdForDriver($conn, (int) $row->driver_id);
            $vehicleName = null;
            if ($vehicleId) {
                $vehicleName = Vehicle::on($conn)->whereKey($vehicleId)->value('name');
            }
            $out[] = [
                'driver_id' => (int) $row->driver_id,
                'driver_name' => $user
                    ? trim(($user->first_name ?? '').' '.($user->last_name ?? ''))
                    : ('#'.$row->driver_id),
                'driver_email' => $user?->email,
                'company_id' => $user?->company_id ? (int) $user->company_id : null,
                'company_name' => $user?->company?->name,
                'vehicle_id' => $vehicleId,
                'vehicle_name' => $vehicleName,
                'blocked_at' => $row->app_blocked_at?->toIso8601String(),
                'reason' => $row->app_blocked_reason,
                'complaint_id' => $row->app_blocked_complaint_id ? (int) $row->app_blocked_complaint_id : null,
            ];
        }

        return $out;
    }
}
