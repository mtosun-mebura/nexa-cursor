<?php

namespace Tests\Feature;

use App\Models\FrontendTheme;
use App\Services\CentralWelcomePageService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CentralPrijzenPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'http://localhost:8085',
            'tenancy.central_domains' => ['localhost'],
        ]);

        FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );
        app(CentralWelcomePageService::class)->syncDefaultContent();
    }

    #[Test]
    public function prijzen_page_shows_packages_and_website_minimum(): void
    {
        $this->get('http://localhost:8085/prijzen')
            ->assertOk()
            ->assertSee('€ 49,-', false)
            ->assertSee('€ 99,-', false)
            ->assertSee('€ 179,-', false)
            ->assertSee('€ 750,-', false)
            ->assertSee('Start', false)
            ->assertSee('Pro', false)
            ->assertSee('Business', false)
            ->assertSee('Website live zetten', false)
            ->assertSee('nexa-plan-absent', false)
            ->assertSee('Niet inbegrepen', false);
    }
}
