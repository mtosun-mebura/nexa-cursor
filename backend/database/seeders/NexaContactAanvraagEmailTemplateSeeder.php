<?php

namespace Database\Seeders;

use App\Services\NexaContactAanvraagEmailTemplateService;
use Illuminate\Database\Seeder;

class NexaContactAanvraagEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(InfoRequestFormFieldSeeder::class);
        app(NexaContactAanvraagEmailTemplateService::class)->ensureExists();
    }
}
