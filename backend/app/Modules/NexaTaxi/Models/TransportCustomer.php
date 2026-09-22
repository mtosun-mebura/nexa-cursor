<?php

namespace App\Modules\NexaTaxi\Models;

use Illuminate\Database\Eloquent\Model;

class TransportCustomer extends Model
{
    protected $table = 'transport_customers';

    public const ORGANIZATION_TYPES = [
        'school' => 'School',
        'zorg' => 'Zorginstelling',
        'ziekenhuis' => 'Ziekenhuis',
        'zakelijk' => 'Zakelijk',
        'prive' => 'Privé',
        'shuttle' => 'Shuttle',
        'overig' => 'Overig',
    ];

    protected $fillable = [
        'company_id',
        'name',
        'organization_type',
        'contact_name',
        'contact_email',
        'contact_phone',
        'debtor_number',
        'billing_address',
        'billing_city',
        'billing_postal_code',
        'billing_country',
        'notes',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public static function organizationTypeKeys(): array
    {
        return array_keys(self::ORGANIZATION_TYPES);
    }

    public function organizationTypeKey(): string
    {
        $key = strtolower(trim((string) ($this->organization_type ?? '')));

        return array_key_exists($key, self::ORGANIZATION_TYPES) ? $key : 'overig';
    }

    public function organizationTypeLabel(): string
    {
        return self::ORGANIZATION_TYPES[$this->organizationTypeKey()];
    }

    public function billingAddressLines(): array
    {
        $street = trim((string) ($this->billing_address ?? ''));
        $postalCity = trim(implode(' ', array_filter([
            trim((string) ($this->billing_postal_code ?? '')),
            trim((string) ($this->billing_city ?? '')),
        ])));
        $country = trim((string) ($this->billing_country ?? ''));

        return array_values(array_filter([$street, $postalCity, $country], static fn ($line) => $line !== ''));
    }
}

