<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Modules\NexaTaxi\Services\TaxiCustomerAcceptEmailTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiCustomerAcceptEmailTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function saving_for_tenant_creates_own_copy_from_global_fallback(): void
    {
        $company = Company::query()->create(['name' => 'Taxi BV']);

        EmailTemplate::query()->create([
            'type' => 'taxi_ride_accepted',
            'company_id' => null,
            'name' => 'Globaal',
            'subject' => 'Globaal onderwerp',
            'html_content' => '<p>Globaal</p>',
            'text_content' => 'Globaal tekst',
            'is_active' => true,
        ]);

        $service = app(TaxiCustomerAcceptEmailTemplateService::class);
        $service->saveForCompany($company->id, [
            'subject' => 'Tenant onderwerp',
            'html_content' => '<p>Tenant HTML</p>',
            'text_content' => 'Tenant tekst',
        ], true);

        $tenant = EmailTemplate::query()
            ->where('type', 'taxi_ride_accepted')
            ->where('company_id', $company->id)
            ->first();

        $this->assertNotNull($tenant);
        $this->assertSame('Tenant onderwerp', $tenant->subject);
        $this->assertSame('<p>Tenant HTML</p>', $tenant->html_content);

        $active = $service->resolveActiveTemplate($company->id);
        $this->assertNotNull($active);
        $this->assertSame($tenant->id, $active->id);
    }
}
