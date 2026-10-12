<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PwaMarketplaceRegisterScreenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        View::addNamespace('taxi', app_path('Modules/NexaTaxi/Resources/views'));

        if (! Route::has('taxi.app.marketplace')) {
            Route::middleware('web')->group(function () {
                Route::get('/taxi/app', [\App\Modules\NexaTaxi\Controllers\TaxiAppLauncherController::class, 'index'])
                    ->name('taxi.app.index');
                Route::get('/taxi/app/marktplaats', [\App\Modules\NexaTaxi\Controllers\TaxiAppLauncherController::class, 'marketplace'])
                    ->name('taxi.app.marketplace');
            });
            Route::getRoutes()->refreshNameLookups();
        }
    }

    #[Test]
    public function pwa_marketplace_screen_uses_native_copy_and_existing_login_actions(): void
    {
        $this->get(route('taxi.app.marketplace'))
            ->assertOk()
            ->assertSee('NEXA Taxi marktplaats', false)
            ->assertSee('Meld je taxibedrijf aan op de NEXA Taxi marktplaats.', false)
            ->assertSee('Na registratie ontvang je een code per e-mail.', false)
            ->assertSee('Aanmelden', false)
            ->assertSee('Ik heb al een code', false)
            ->assertSee('>Terug<', false)
            ->assertSee('marketplace-register', false)
            ->assertSee('marketplace-code', false)
            ->assertSee('first-verify', false)
            ->assertDontSee('ADMINISTRATIE PANEEL', false)
            ->assertDontSee('Registreren en code ontvangen', false)
            ->assertDontSee('NEXA | SUITE', false);
    }

    #[Test]
    public function pwa_launcher_opens_the_marketplace_screen_instead_of_suite_login(): void
    {
        $this->get(route('taxi.app.index'))
            ->assertOk()
            ->assertSee(route('taxi.app.marketplace', absolute: false), false)
            ->assertDontSee('/admin/login?marketplace=1', false);
    }

    #[Test]
    public function suite_login_keeps_the_standard_marketplace_registration_screen(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Taxibedrijf registreren', false)
            ->assertSee('Registreren en code ontvangen', false)
            ->assertSee('Al geregistreerd? Inloggen', false)
            ->assertSee('Terug naar inloggen', false)
            ->assertSee('Administratie paneel', false);
    }
}
