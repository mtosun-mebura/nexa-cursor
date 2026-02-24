<?php

namespace App\Modules\TaxiRoyaal;

use App\Modules\Base\Module as BaseModule;

class Module extends BaseModule
{
    public function getName(): string
    {
        return 'taxiroyaal';
    }

    public function getDisplayName(): string
    {
        return 'Taxi Royaal';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return 'Boek je taxi bij Taxi Royaal!';
    }

    /**
     * Schema voor deze module (gebruikt voor DB-connectie bij PostgreSQL).
     */
    public function getSchemaName(): ?string
    {
        return 'nexa_taxiroyaal';
    }

    public function getIcon(): string
    {
        return 'ki-filled ki-briefcase';
    }

    public function registerMenuItems(): array
    {
        return [
            [
                'key' => 'vehicles',
                'title' => 'Voertuigen',
                'route' => 'admin.taxiroyaal.vehicles.index',
                'icon' => 'ki-filled ki-car',
                'permission' => 'vehicles.view',
                'order' => 10,
            ],
            [
                'key' => 'tarieven',
                'title' => 'Tarieven',
                'route' => 'admin.taxiroyaal.tarieven.edit',
                'icon' => 'ki-filled ki-dollar',
                'permission' => 'rates.view',
                'permission_any' => ['rates.view', 'vehicles.view'], // toon ook voor company admin met alleen vehicles.view
                'order' => 15,
            ],
            [
                'key' => 'ride_requests',
                'title' => 'Ritten',
                'route' => 'admin.taxiroyaal.ride_requests.index',
                'icon' => 'ki-filled ki-calendar',
                'permission' => 'rides.view',
                'order' => 20,
            ],
        ];
    }

    public function registerPermissions(): array
    {
        return [
            'vehicles.view',
            'vehicles.create',
            'vehicles.update',
            'vehicles.delete',
            'rates.view',
            'rates.update',
            'rides.view',
            'rides.create',
            'rides.update',
            'rides.delete',
        ];
    }
}
