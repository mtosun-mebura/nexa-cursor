<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\Notification;
use App\Models\User;
use App\Support\IncidentCatalog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IncidentNotifier
{
    /**
     * @return Collection<int, User>
     */
    public function superAdmins(): Collection
    {
        $roleId = DB::table(config('permission.table_names.roles'))
            ->where('name', 'super-admin')
            ->whereIn('guard_name', ['web', 'api'])
            ->value('id');

        if (! $roleId) {
            return collect();
        }

        $pivot = config('permission.table_names.model_has_roles');
        $morphKey = config('permission.column_names.model_morph_key') ?: 'model_id';
        $rolePivotKey = config('permission.column_names.role_pivot_key') ?: 'role_id';

        $ids = DB::table($pivot)
            ->where($rolePivotKey, $roleId)
            ->whereIn('model_type', array_unique([User::class, 'App\\Models\\User']))
            ->pluck($morphKey)
            ->unique()
            ->filter()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return User::query()->whereIn('id', $ids)->get();
    }

    public function notifySuperAdminsOfNewIncident(Incident $incident): void
    {
        $incident->loadMissing(['company', 'reporter']);
        $url = route('admin.incidents.index', ['open' => $incident->id]);
        $companyName = (string) ($incident->company?->name ?: 'NEXA');
        $reporterName = $this->displayName($incident->reporter);
        $kindLabel = IncidentCatalog::kindLabel((string) $incident->kind);
        $priority = $incident->priority ?: IncidentCatalog::PRIORITY_NORMAL;

        foreach ($this->superAdmins() as $admin) {
            if ((int) $admin->id === (int) $incident->reporter_user_id) {
                continue;
            }

            try {
                Notification::query()->create([
                    'user_id' => $admin->id,
                    'company_id' => $incident->company_id,
                    'type' => IncidentCatalog::NOTIFICATION_TYPE,
                    'category' => IncidentCatalog::NOTIFICATION_CATEGORY,
                    'title' => 'Nieuw incident '.$incident->reference,
                    'message' => $reporterName.' van '.$companyName.' heeft een '.$kindLabel.' gemeld: '.$incident->title,
                    'priority' => $priority,
                    'action_url' => $url,
                    'data' => json_encode([
                        'incident_id' => $incident->id,
                        'incident_reference' => $incident->reference,
                        'sender_id' => $incident->reporter_user_id,
                        'sender_email' => $incident->reporter?->email,
                        'event' => 'incident_created',
                    ]),
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to notify super-admin of incident', [
                    'incident_id' => $incident->id,
                    'admin_id' => $admin->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function notifyReporterIncidentHandled(Incident $incident): void
    {
        $incident->loadMissing(['reporter', 'resolvedBy']);
        $reporter = $incident->reporter;
        if (! $reporter) {
            return;
        }

        $url = route('admin.incidents.index', ['open' => $incident->id]);
        $handlerName = $this->displayName($incident->resolvedBy) ?: 'NEXA Support';
        $note = trim((string) $incident->resolution_note);
        $message = $handlerName.' heeft incident '.$incident->reference.' afgehandeld.';
        if ($note !== '') {
            $message .= ' Toelichting: '.$note;
        }

        try {
            Notification::query()->create([
                'user_id' => $reporter->id,
                'company_id' => $incident->company_id,
                'type' => IncidentCatalog::NOTIFICATION_TYPE,
                'category' => IncidentCatalog::NOTIFICATION_CATEGORY,
                'title' => 'Incident '.$incident->reference.' is afgehandeld',
                'message' => $message,
                'priority' => 'normal',
                'action_url' => $url,
                'data' => json_encode([
                    'incident_id' => $incident->id,
                    'incident_reference' => $incident->reference,
                    'sender_id' => $incident->resolved_by_user_id,
                    'sender_email' => $incident->resolvedBy?->email,
                    'event' => 'incident_handled',
                ]),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to notify reporter of handled incident', [
                'incident_id' => $incident->id,
                'reporter_id' => $reporter->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function displayName(?User $user): string
    {
        if (! $user) {
            return '';
        }

        $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

        return $name !== '' ? $name : (string) $user->email;
    }
}
