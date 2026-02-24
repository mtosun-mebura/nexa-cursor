<?php

namespace App\Modules\Skillmatching;

use App\Modules\Base\Module as BaseModule;
use Database\Seeders\CandidateSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CompanySeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\InterviewMatchSeeder;
use Database\Seeders\MatchSeeder;
use Database\Seeders\NotificationSeeder;
use Database\Seeders\PipelineTemplateSeeder;
use Database\Seeders\StageTypeSeeder;
use Database\Seeders\VacancySeeder;

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

    /**
     * Schema voor deze module (gebruikt voor DB-connectie bij PostgreSQL).
     */
    public function getSchemaName(): ?string
    {
        return 'nexa_skillmatching';
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
                'route' => 'admin.skillmatching.branches.index',
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

    /**
     * Dummydata-seeders voor Skillmatching (vacatures, kandidaten, matches, etc.).
     */
    public function getDummySeeders(): array
    {
        return [
            CategorySeeder::class,
            StageTypeSeeder::class,
            PipelineTemplateSeeder::class,
            CompanySeeder::class,
            VacancySeeder::class,
            CandidateSeeder::class,
            MatchSeeder::class,
            InterviewMatchSeeder::class,
            NotificationSeeder::class,
            EmailTemplateSeeder::class,
        ];
    }
}
