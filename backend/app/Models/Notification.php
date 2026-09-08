<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'company_id', 'type', 'category', 'title', 'message', 'priority', 'read_at', 'action_url', 'data', 'scheduled_at', 'location_id', 'file_path', 'file_name', 'file_size', 'original_notification_id', 'email_template_id'
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'scheduled_at' => 'datetime',
    ];

    /**
     * Get the user that owns the notification.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company that owns the notification.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the email template associated with the notification.
     */
    public function emailTemplate()
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    /**
     * Meldingen in de admin-drawer: stappenplan (taxi_setup) is tenant-gebonden.
     * Super-admin zonder tenant ziet die niet; met tenant-switch alle meldingen van die tenant.
     */
    public static function queryVisibleInAdminDrawer(User $user, ?int $selectedTenantId = null): \Illuminate\Database\Eloquent\Builder
    {
        $setupType = \App\Modules\NexaTaxi\Services\TaxiTenantSetupService::NOTIFICATION_TYPE;
        $setupIds = self::uniqueTaxiSetupIdsFor($user, $selectedTenantId);
        $query = static::query();

        $personalWithoutSetup = function ($q) use ($user, $setupType): void {
            $q->where('user_id', $user->id)
                ->where(function ($type) use ($setupType) {
                    $type->whereNull('type')->orWhere('type', '!=', $setupType);
                });
        };

        if ($user->isSuperAdmin() || $user->hasRole('super-admin')) {
            if ($selectedTenantId === null || $selectedTenantId <= 0) {
                return $query->where($personalWithoutSetup);
            }

            return $query->where(function ($q) use ($personalWithoutSetup, $selectedTenantId, $setupType, $setupIds) {
                $q->where($personalWithoutSetup)
                    ->orWhere(function ($tenant) use ($selectedTenantId, $setupType) {
                        $tenant->where('company_id', $selectedTenantId)
                            ->where(function ($type) use ($setupType) {
                                $type->whereNull('type')->orWhere('type', '!=', $setupType);
                            });
                    });
                if ($setupIds !== []) {
                    $q->orWhereIn('id', $setupIds);
                }
            });
        }

        $companyId = (int) ($user->company_id ?? 0);
        $query->where(function ($q) use ($personalWithoutSetup, $setupIds) {
            $q->where($personalWithoutSetup);
            if ($setupIds !== []) {
                $q->orWhereIn('id', $setupIds);
            }
        });
        if ($companyId > 0) {
            $query->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            });
        }

        return $query;
    }

    /**
     * @return list<int>
     */
    private static function uniqueTaxiSetupIdsFor(User $user, ?int $selectedTenantId): array
    {
        $setupType = \App\Modules\NexaTaxi\Services\TaxiTenantSetupService::NOTIFICATION_TYPE;
        $query = static::query()
            ->where('type', $setupType)
            ->whereNull('archived_at');

        if ($user->isSuperAdmin() || $user->hasRole('super-admin')) {
            if ($selectedTenantId === null || $selectedTenantId <= 0) {
                return [];
            }
            $query->where('company_id', $selectedTenantId);
        } else {
            $companyId = (int) ($user->company_id ?? 0);
            if ($companyId <= 0) {
                return [];
            }
            $query->where('company_id', $companyId);
        }

        return $query->selectRaw('MIN(id) as id')
            ->groupBy('company_id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public static function userCanAccessDrawerNotification(User $user, self $notification, ?int $selectedTenantId = null): bool
    {
        $setupType = \App\Modules\NexaTaxi\Services\TaxiTenantSetupService::NOTIFICATION_TYPE;
        $isSuper = $user->isSuperAdmin() || $user->hasRole('super-admin');
        $companyId = (int) ($notification->company_id ?? 0);

        if ($notification->type === $setupType) {
            if ($isSuper) {
                return $selectedTenantId !== null && $selectedTenantId > 0 && $companyId === (int) $selectedTenantId;
            }

            return $companyId > 0 && $companyId === (int) ($user->company_id ?? 0);
        }

        if ((int) $notification->user_id === (int) $user->id) {
            return true;
        }

        return $isSuper
            && $selectedTenantId !== null
            && $selectedTenantId > 0
            && $companyId === (int) $selectedTenantId;
    }
}


