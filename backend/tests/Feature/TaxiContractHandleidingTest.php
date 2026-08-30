<?php

namespace Tests\Feature;

use App\Modules\NexaTaxi\Controllers\ContractPortalAppController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiContractHandleidingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        View::addNamespace('taxi', app_path('Modules/NexaTaxi/Resources/views'));

        if (! Route::has('taxi.contract.index')) {
            Route::middleware('web')
                ->prefix('taxi/contract')
                ->name('taxi.contract.')
                ->group(app_path('Modules/NexaTaxi/Routes/contract-web.php'));
        }
        if (! Route::has('taxi.contract.handleiding')) {
            Route::get('/taxi/contract/handleiding', [ContractPortalAppController::class, 'handleiding'])
                ->name('taxi.contract.handleiding');
        }
        if (! Route::has('taxi.contract.manifest')) {
            Route::get('/taxi/contract/manifest.webmanifest', [ContractPortalAppController::class, 'manifest'])
                ->name('taxi.contract.manifest');
        }
    }

    #[Test]
    public function handleiding_page_explains_each_contract_step(): void
    {
        $this->get('/taxi/contract/handleiding')
            ->assertOk()
            ->assertSee('Handleiding contract', false)
            ->assertSee('1. App op je telefoon', false)
            ->assertSee('Inloggen', false)
            ->assertSee('Inlogcode aanvragen', false)
            ->assertSee('Vandaag', false)
            ->assertSee('Planning', false)
            ->assertSee('Afmelden', false)
            ->assertSee('Navigatie', false)
            ->assertSee('Start navigatie', false)
            ->assertSee('tussenstops', false)
            ->assertSee('Google Maps', false)
            ->assertSee('vijf tabbladen', false)
            ->assertSee('Tabbladen onderin', false)
            ->assertSee('feature-contract-vandaag.png', false)
            ->assertSee('feature-contract-vandaag-ouder.png', false)
            ->assertSee('feature-contract-vandaag-ouder-bereikt.png', false)
            ->assertSee('Contractant: alle reizigers', false)
            ->assertSee('Ouder: één reiziger', false)
            ->assertSee('bestemming bereikt', false)
            ->assertSee('feature-contract-planning.png', false)
            ->assertSee('eerst de naam, daaronder Ophalen', false)
            ->assertSee('feature-contract-navigatie-map.png', false)
            ->assertSee('class="guide-zoom"', false)
            ->assertSee('Tik om te vergroten', false)
            ->assertSee('Terug naar de contract-app', false);
    }

    #[Test]
    public function contract_app_shows_banner_and_profile_link_to_handleiding(): void
    {
        $html = view('taxi::contract-portal.index', [
            'apiBase' => 'http://localhost/api/taxi/v1/contract',
            'pollMs' => 15000,
            'appUrl' => 'http://localhost/taxi/contract',
            'guideUrl' => 'http://localhost/taxi/contract/handleiding',
            'faviconUrl' => '/favicon.ico',
            'faviconType' => 'image/x-icon',
        ])->render();

        $this->assertStringContainsString('data-main-tab="navigation"', $html);
        $this->assertStringContainsString('id="btn-start-navigation"', $html);
        $this->assertStringContainsString('Inlogcode aanvragen', $html);
        $this->assertStringContainsString('id="guide-hint"', $html);
        $this->assertStringContainsString('Na wegklikken vind je die altijd terug onder', $html);
        $this->assertStringContainsString('profile-guide-link', $html);
        $this->assertStringContainsString('/taxi/contract/handleiding', $html);
        $this->assertStringNotContainsString('id="install-hint"', $html);
        $this->assertStringNotContainsString('Hoe installeren', $html);
    }
}
