<?php

namespace Database\Seeders;

use App\Modules\NexaTaxi\Services\TaxiAppLoginCodeEmailTemplateService;
use App\Modules\NexaTaxi\Services\TaxiAppUserWelcomeEmailTemplateService;
use App\Modules\NexaTaxi\Services\TaxiCustomerAcceptEmailTemplateService;
use App\Modules\NexaTaxi\Services\TaxiCustomerLoginCodeEmailTemplateService;
use Illuminate\Database\Seeder;

class TaxiRideAcceptedEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        app(TaxiCustomerAcceptEmailTemplateService::class)->ensureGlobalTemplateExists();
        app(TaxiCustomerLoginCodeEmailTemplateService::class)->ensureGlobalTemplateExists();
        app(TaxiAppLoginCodeEmailTemplateService::class)->ensureGlobalTemplateExists();
        app(TaxiAppUserWelcomeEmailTemplateService::class)->ensureAllGlobalTemplatesExist();
    }
}
