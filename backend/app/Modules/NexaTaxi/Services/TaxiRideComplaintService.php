<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\TaxiDriverNotification;
use App\Modules\NexaTaxi\Models\TaxiRideComplaint;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Support\TaxiDriverComplaintSchema;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class TaxiRideComplaintService
{
    public function __construct(
        protected TaxiDriverNotificationService $notifications,
        protected TaxiDriverBlockService $blocks
    ) {}

    /**
     * @return array{filed: bool, can_file: bool, complaint_id: ?int}
     */
    public function payloadForRide(string $conn, RideRequest $ride): array
    {
        TaxiDriverComplaintSchema::ensure($conn);
        $existing = TaxiRideComplaint::on($conn)
            ->where('ride_request_id', (int) $ride->id)
            ->orderByDesc('id')
            ->first();

        $canFile = $ride->status === RideRequest::STATUS_COMPLETED
            && (int) ($ride->driver_id ?? 0) > 0
            && ! $existing;

        return [
            'filed' => (bool) $existing,
            'can_file' => $canFile,
            'complaint_id' => $existing ? (int) $existing->id : null,
        ];
    }

    /**
     * Open klachten voor admin-menu badge / pulse (optioneel gefilterd op bedrijf).
     *
     * @return array{
     *   open_count: int,
     *   latest_id: int,
     *   latest: ?array{id: int, subject: ?string, category: string, category_label: string, company_id: int, created_at: ?string}
     * }
     */
    public function adminPulse(string $conn, ?int $companyId = null): array
    {
        TaxiDriverComplaintSchema::ensure($conn);

        $base = TaxiRideComplaint::on($conn)->when(
            $companyId !== null && $companyId > 0,
            fn ($q) => $q->where('company_id', $companyId)
        );

        $openCount = (int) (clone $base)->where('status', TaxiRideComplaint::STATUS_OPEN)->count();
        $latest = (clone $base)->orderByDesc('id')->first();

        return [
            'open_count' => $openCount,
            'latest_id' => $latest ? (int) $latest->id : 0,
            'latest' => $latest ? [
                'id' => (int) $latest->id,
                'subject' => $latest->subject,
                'category' => (string) $latest->category,
                'category_label' => $latest->categoryLabel(),
                'company_id' => (int) $latest->company_id,
                'created_at' => $latest->created_at?->toIso8601String(),
            ] : null,
        ];
    }

    /**
     * @param  array{
     *   category: string,
     *   description: string,
     *   subject?: string|null,
     *   customer_name?: string|null,
     *   customer_email?: string|null,
     *   customer_phone?: string|null,
     *   preferred_contact?: string|null
     * }  $data
     */
    public function file(string $conn, RideRequest $ride, array $data): TaxiRideComplaint
    {
        TaxiDriverComplaintSchema::ensure($conn);

        if ($ride->status !== RideRequest::STATUS_COMPLETED) {
            throw ValidationException::withMessages([
                'description' => ['Je kunt pas een klacht indienen nadat de rit is afgerond.'],
            ]);
        }

        $driverId = (int) ($ride->driver_id ?? 0);
        if ($driverId <= 0) {
            throw ValidationException::withMessages([
                'description' => ['Geen chauffeur gekoppeld aan deze rit.'],
            ]);
        }

        $existing = TaxiRideComplaint::on($conn)
            ->where('ride_request_id', (int) $ride->id)
            ->exists();
        if ($existing) {
            throw ValidationException::withMessages([
                'description' => ['Voor deze rit is al een klacht ingediend.'],
            ]);
        }

        $category = (string) ($data['category'] ?? '');
        if (! array_key_exists($category, TaxiRideComplaint::CATEGORIES)) {
            throw ValidationException::withMessages([
                'category' => ['Kies een geldige categorie.'],
            ]);
        }

        $description = trim((string) ($data['description'] ?? ''));
        if ($description === '' || mb_strlen($description) < 10) {
            throw ValidationException::withMessages([
                'description' => ['Omschrijf de klacht in minimaal 10 tekens.'],
            ]);
        }
        if (mb_strlen($description) > 4000) {
            throw ValidationException::withMessages([
                'description' => ['Omschrijving mag maximaal 4000 tekens zijn.'],
            ]);
        }

        // Contact terugkoppeling loopt alleen via e-mail.
        $preferred = 'email';

        $companyId = (int) ($ride->fulfilling_company_id ?: $ride->company_id ?: 0);
        $subject = isset($data['subject']) ? trim((string) $data['subject']) : '';
        if ($subject === '') {
            $companyName = Company::query()->find($companyId)?->name;
            $subject = $companyName
                ? 'Klacht over rit #'.$ride->id.' bij '.$companyName
                : 'Klacht over rit #'.$ride->id;
        }

        $row = new TaxiRideComplaint([
            'company_id' => $companyId,
            'ride_request_id' => (int) $ride->id,
            'driver_id' => $driverId,
            'vehicle_id' => $ride->vehicle_id ? (int) $ride->vehicle_id : null,
            'category' => $category,
            'subject' => mb_substr($subject, 0, 160),
            'description' => $description,
            'customer_name' => $this->nullableTrim($data['customer_name'] ?? $ride->customer_name, 160),
            'customer_email' => $this->nullableTrim($data['customer_email'] ?? $ride->customer_email, 190),
            'customer_phone' => $this->nullableTrim($data['customer_phone'] ?? $ride->customer_phone, 40),
            'preferred_contact' => $preferred,
            'status' => TaxiRideComplaint::STATUS_OPEN,
        ]);
        $row->setConnection($conn);
        $row->save();

        return $row;
    }

    public function sendDriverMessage(
        string $conn,
        TaxiRideComplaint $complaint,
        string $title,
        string $body,
        ?int $byUserId
    ): TaxiDriverNotification {
        $title = trim($title);
        $body = trim($body);
        if ($title === '' || $body === '') {
            throw ValidationException::withMessages([
                'message' => ['Vul een titel en bericht in voor de chauffeur.'],
            ]);
        }

        if ($complaint->status === TaxiRideComplaint::STATUS_OPEN) {
            $complaint->status = TaxiRideComplaint::STATUS_IN_PROGRESS;
            $complaint->handled_by = $byUserId;
            $complaint->handled_at = now();
            $complaint->save();
        }

        return $this->notifications->notify($conn, (int) $complaint->driver_id, [
            'type' => TaxiDriverNotification::TYPE_COMPLAINT_FOLLOW_UP,
            'title' => mb_substr($title, 0, 160),
            'body' => mb_substr($body, 0, 4000),
            'company_id' => (int) $complaint->company_id,
            'related_complaint_id' => (int) $complaint->id,
            'related_ride_id' => (int) $complaint->ride_request_id,
            'created_by' => $byUserId,
            'push' => true,
        ]);
    }

    public function updateStatus(
        string $conn,
        TaxiRideComplaint $complaint,
        string $status,
        ?string $adminNotes,
        ?int $byUserId
    ): TaxiRideComplaint {
        if (! array_key_exists($status, TaxiRideComplaint::statusLabels())) {
            throw ValidationException::withMessages(['status' => ['Ongeldige status.']]);
        }

        $complaint->status = $status;
        if ($adminNotes !== null) {
            $complaint->admin_notes = trim($adminNotes) !== '' ? trim($adminNotes) : null;
        }
        $complaint->handled_by = $byUserId;
        $complaint->handled_at = now();
        if (in_array($status, [TaxiRideComplaint::STATUS_RESOLVED, TaxiRideComplaint::STATUS_DISMISSED], true)) {
            $complaint->resolved_at = now();
        }
        $complaint->save();

        return $complaint;
    }

    public function blockDriverFromComplaint(
        string $conn,
        TaxiRideComplaint $complaint,
        ?int $byUserId,
        ?string $reason = null
    ): void {
        $this->blocks->block(
            $conn,
            (int) $complaint->driver_id,
            $byUserId,
            $reason ?: ('Geblokkeerd n.a.v. klacht #'.$complaint->id),
            (int) $complaint->id,
            (int) $complaint->company_id
        );

        if ($complaint->status === TaxiRideComplaint::STATUS_OPEN) {
            $complaint->status = TaxiRideComplaint::STATUS_IN_PROGRESS;
            $complaint->handled_by = $byUserId;
            $complaint->handled_at = now();
            $complaint->save();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function detailPayload(string $conn, TaxiRideComplaint $complaint): array
    {
        $company = $complaint->company_id
            ? Company::query()->find((int) $complaint->company_id)
            : null;
        $driver = User::query()->find((int) $complaint->driver_id);
        $vehicle = $complaint->vehicle_id
            ? Vehicle::on($conn)->find((int) $complaint->vehicle_id)
            : null;
        $ride = RideRequest::on($conn)->find((int) $complaint->ride_request_id);
        $blocked = $this->blocks->isBlocked($conn, (int) $complaint->driver_id);

        return [
            'id' => (int) $complaint->id,
            'status' => $complaint->status,
            'status_label' => $complaint->statusLabel(),
            'category' => $complaint->category,
            'category_label' => $complaint->categoryLabel(),
            'subject' => $complaint->subject,
            'description' => $complaint->description,
            'customer_name' => $complaint->customer_name,
            'customer_email' => $complaint->customer_email,
            'customer_phone' => $complaint->customer_phone,
            'preferred_contact' => $complaint->preferred_contact,
            'admin_notes' => $complaint->admin_notes,
            'created_at' => $complaint->created_at?->toIso8601String(),
            'handled_at' => $complaint->handled_at?->toIso8601String(),
            'resolved_at' => $complaint->resolved_at?->toIso8601String(),
            'company' => [
                'id' => (int) $complaint->company_id,
                'name' => $company?->name,
            ],
            'driver' => [
                'id' => (int) $complaint->driver_id,
                'name' => $driver
                    ? trim(($driver->first_name ?? '').' '.($driver->last_name ?? ''))
                    : null,
                'email' => $driver?->email,
                'blocked' => $blocked,
            ],
            'vehicle' => [
                'id' => $complaint->vehicle_id ? (int) $complaint->vehicle_id : null,
                'name' => $vehicle?->name,
                'license_plate' => $vehicle?->license_plate,
            ],
            'ride' => [
                'id' => (int) $complaint->ride_request_id,
                'pickup_address' => $ride?->pickup_address,
                'dropoff_address' => $ride?->dropoff_address,
                'pickup_at' => $ride?->pickup_at?->toIso8601String(),
                'status' => $ride?->status,
            ],
        ];
    }

    protected function nullableTrim(mixed $value, int $max): ?string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return null;
        }

        return mb_substr($text, 0, $max);
    }
}
