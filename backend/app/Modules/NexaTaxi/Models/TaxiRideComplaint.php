<?php

namespace App\Modules\NexaTaxi\Models;

use Illuminate\Database\Eloquent\Model;

class TaxiRideComplaint extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_DISMISSED = 'dismissed';

    public const CATEGORIES = [
        'late' => 'Te laat / niet gekomen',
        'unsafe_driving' => 'Onveilig rijgedrag',
        'rude' => 'Onbeleefd / ongepast gedrag',
        'dirty_vehicle' => 'Vies of defect voertuig',
        'wrong_route' => 'Verkeerde route / omweg',
        'payment' => 'Betaling / prijs',
        'other' => 'Overig',
    ];

    protected $table = 'taxi_ride_complaints';

    protected $fillable = [
        'company_id',
        'ride_request_id',
        'driver_id',
        'vehicle_id',
        'category',
        'subject',
        'description',
        'customer_name',
        'customer_email',
        'customer_phone',
        'preferred_contact',
        'status',
        'admin_notes',
        'handled_by',
        'handled_at',
        'resolved_at',
    ];

    protected $casts = [
        'handled_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public static function statusLabels(): array
    {
        return [
            self::STATUS_OPEN => 'Open',
            self::STATUS_IN_PROGRESS => 'In behandeling',
            self::STATUS_RESOLVED => 'Afgehandeld',
            self::STATUS_DISMISSED => 'Afgewezen',
        ];
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? (string) $this->category;
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? (string) $this->status;
    }
}
