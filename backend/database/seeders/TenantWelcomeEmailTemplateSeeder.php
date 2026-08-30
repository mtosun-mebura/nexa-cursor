<?php

namespace Database\Seeders;

use App\Services\TenantWelcomeEmailTemplateService;
use Illuminate\Database\Seeder;

class TenantWelcomeEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        app(TenantWelcomeEmailTemplateService::class)->ensureExists();
    }
}
