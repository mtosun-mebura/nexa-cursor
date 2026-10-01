<?php

namespace App\Services;

use App\Models\GeneralSetting;
use App\Models\Incident;
use App\Models\Notification;
use App\Models\User;
use App\Support\IncidentCatalog;
use App\Support\NexaBranding;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

        $this->emailSupportOfNewIncident($incident);
    }

    /**
     * Stuur een e-mail naar het geconfigureerde supportadres wanneer een klant
     * (geen super-admin) een incident aanmaakt.
     */
    public function emailSupportOfNewIncident(Incident $incident): void
    {
        $incident->loadMissing(['company', 'reporter']);

        $reporter = $incident->reporter;
        if ($reporter && $reporter->isSuperAdmin()) {
            return;
        }

        $to = $this->notificationEmail();
        if ($to === '') {
            return;
        }

        try {
            $url = route('admin.incidents.index', ['open' => $incident->id]);
            $html = view('emails.incident-created', [
                'incident' => $incident,
                'url' => $url,
                'companyName' => (string) ($incident->company?->name ?: 'Onbekend bedrijf'),
                'reporterName' => $this->displayName($reporter) ?: 'Onbekend',
                'reporterEmail' => (string) ($reporter?->email ?: ''),
                'kindLabel' => IncidentCatalog::kindLabel((string) $incident->kind),
                'priorityLabel' => IncidentCatalog::priorityLabel((string) ($incident->priority ?: IncidentCatalog::PRIORITY_NORMAL)),
                'pageUrl' => trim((string) ($incident->page_url ?: '')),
                'nexaLogoHtml' => NexaBranding::EMAIL_LOGO_PLACEHOLDER,
            ])->render();

            $fromAddress = config('mail.from.address', 'noreply@nexasuite.nl');
            $fromName = config('mail.from.name', 'Nexa Suite');
            $subject = 'Nieuw incident '.$incident->reference.': '.$incident->title;

            Mail::html($html, function ($message) use ($to, $subject, $fromAddress, $fromName) {
                $message->to($to)
                    ->subject($subject)
                    ->from($fromAddress, $fromName);
            });
        } catch (\Throwable $e) {
            Log::error('Failed to email support about new incident', [
                'incident_id' => $incident->id,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function notificationEmail(): string
    {
        $email = trim((string) GeneralSetting::get(
            IncidentCatalog::SETTING_NOTIFICATION_EMAIL,
            IncidentCatalog::DEFAULT_NOTIFICATION_EMAIL
        ));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return IncidentCatalog::DEFAULT_NOTIFICATION_EMAIL;
        }

        return strtolower($email);
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
