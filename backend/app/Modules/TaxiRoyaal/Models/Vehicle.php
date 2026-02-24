<?php

namespace App\Modules\TaxiRoyaal\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $table = 'vehicles';

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'license_plate',
        'seats',
        'person_range',
        'active',
        'base_fare',
        'price_per_km',
        'price_per_min',
        'min_fare',
        'cleaning_costs',
        'notes',
        'image_url',
        'show_photo',
    ];

    protected $casts = [
        'active' => 'boolean',
        'show_photo' => 'boolean',
        'base_fare' => 'decimal:2',
        'price_per_km' => 'decimal:2',
        'price_per_min' => 'decimal:2',
        'min_fare' => 'decimal:2',
        'cleaning_costs' => 'decimal:2',
    ];

    public const TYPE_CAR = 'car';
    public const TYPE_VAN = 'van';
    public const TYPE_BUS = 'bus';
    public const PERSON_RANGE_1_4 = '1-4';
    public const PERSON_RANGE_5_8 = '5-8';

    public static function typeLabels(): array
    {
        return [
            self::TYPE_CAR => 'Auto',
            self::TYPE_VAN => 'Busje',
            self::TYPE_BUS => 'Bus',
        ];
    }

    public static function personRangeLabels(): array
    {
        return [
            self::PERSON_RANGE_1_4 => 't/m 4 personen',
            self::PERSON_RANGE_5_8 => '5 t/m 8 personen',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function rideRequests(): HasMany
    {
        return $this->hasMany(RideRequest::class, 'vehicle_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::typeLabels()[$this->type] ?? $this->type;
    }
}
