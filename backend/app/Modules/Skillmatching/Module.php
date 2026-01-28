<?php

namespace App\Modules\Skillmatching;

use App\Modules\Base\Module as BaseModule;

class Module extends BaseModule
{
    public function getName(): string
    {
        return 'skillmatching';
    }

    public function getDisplayName(): string
    {
        return 'Nexa Skillmatching';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return 'Vacature matching en interview management systeem';
    }

    public function getIcon(): string
    {
        return 'ki-filled ki-briefcase';
    }

    public function registerMenuItems(): array
    {
        return [
            [
                'key' => 'vacancies',
                'title' => 'Vacatures',
                'route' => 'admin.skillmatching.vacancies.index',
                'icon' => 'ki-filled ki-briefcase',
                'permission' => 'skillmatching.vacancies.view',
                'order' => 10,
            ],
            [
                'key' => 'matches',
                'title' => 'Matches',
                'route' => 'admin.skillmatching.matches.index',
                'icon' => 'ki-filled ki-abstract-26',
                'permission' => 'skillmatching.matches.view',
                'order' => 20,
            ],
            [
                'key' => 'interviews',
                'title' => 'Interviews',
                'route' => 'admin.skillmatching.interviews.index',
                'icon' => 'ki-filled ki-calendar',
                'permission' => 'skillmatching.interviews.view',
                'order' => 30,
            ],
            [
                'key' => 'branches',
                'title' => 'Branches',
                'route' => 'admin.branches.index',
                'icon' => 'ki-filled ki-tag',
                'permission' => 'view-branches',
                'order' => 5,
            ],
        ];
    }

    public function registerPermissions(): array
    {
        return [
            'skillmatching.vacancies.view',
            'skillmatching.vacancies.create',
            'skillmatching.vacancies.edit',
            'skillmatching.vacancies.delete',
            'skillmatching.matches.view',
            'skillmatching.matches.create',
            'skillmatching.interviews.view',
            'skillmatching.interviews.create',
            'skillmatching.interviews.edit',
        ];
    }
}
