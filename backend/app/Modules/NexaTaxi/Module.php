<?php

namespace App\Modules\NexaTaxi;

use App\Modules\Base\Module as BaseModule;

class Module extends BaseModule
{
    public function getName(): string
    {
        return 'taxi';
    }

    public function getDisplayName(): string
    {
        return 'Nexa Taxi';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return 'Boek eenvoudig een taxi met Nexa Taxi.';
    }

    /**
     * Schema voor deze module (gebruikt voor DB-connectie bij PostgreSQL).
     */
    public function getSchemaName(): ?string
    {
        return 'nexa_taxi';
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
                'route' => 'admin.taxi.vehicles.index',
                'icon' => 'ki-filled ki-car',
                'permission' => 'vehicles.view',
                'order' => 10,
            ],
            [
                'key' => 'tarieven',
                'title' => 'Tarieven',
                'route' => 'admin.taxi.tarieven.edit',
                'icon' => 'ki-filled ki-dollar',
                'permission' => 'rates.view',
                'permission_any' => ['rates.view', 'vehicles.view'], // toon ook voor company admin met alleen vehicles.view
                'order' => 15,
            ],
            [
                'key' => 'ride_requests',
                'title' => 'Ritten',
                'route' => 'admin.taxi.ride_requests.index',
                'icon' => 'ki-filled ki-calendar',
                'permission' => 'rides.view',
                'order' => 20,
            ],
            [
                'key' => 'transport_customers',
                'title' => 'Contractvervoer',
                'route' => 'admin.taxi.transport_customers.index',
                'icon' => 'ki-filled ki-people',
                'permission' => 'rides.view',
                'order' => 22,
            ],
            [
                'key' => 'dispatch_settings',
                'title' => 'Chauffeur dispatch',
                'route' => 'admin.taxi.dispatch_settings.edit',
                'icon' => 'ki-filled ki-phone',
                'permission' => 'rides.view',
                'order' => 25,
            ],
            [
                'key' => 'ai_chatbot',
                'title' => 'AI-chatbot',
                'route' => 'admin.taxi.knowledge_documents.index',
                'icon' => 'ki-filled ki-technology-2',
                'permission' => 'ai_chatbot.view',
                'permission_any' => ['ai_chatbot.view', 'rides.view', 'vehicles.view'],
                'order' => 30,
                'children' => [
                    [
                        'title' => 'Kennisbank',
                        'route' => 'admin.taxi.knowledge_documents.index',
                    ],
                    [
                        'title' => 'Instellingen',
                        'route' => 'admin.taxi.ai_chatbot.settings.edit',
                        'permission_any' => ['ai_chatbot.update', 'rides.update', 'vehicles.update'],
                    ],
                ],
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
            'ai_chatbot.view',
            'ai_chatbot.create',
            'ai_chatbot.update',
            'ai_chatbot.delete',
        ];
    }
}
