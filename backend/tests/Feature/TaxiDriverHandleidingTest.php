<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiDriverHandleidingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        View::addNamespace('taxi', app_path('Modules/NexaTaxi/Resources/views'));

        if (! Route::has('taxi.chauffeur.handleiding')) {
            Route::middleware('web')
                ->prefix('taxi/chauffeur')
                ->name('taxi.chauffeur.')
                ->group(app_path('Modules/NexaTaxi/Routes/driver-web.php'));
        }
        if (! Route::has('taxi.chauffeur.manifest')) {
            Route::get('/taxi/chauffeur/manifest.webmanifest', fn () => response('', 200))
                ->name('taxi.chauffeur.manifest');
        }
    }

    #[Test]
    public function handleiding_page_explains_each_driver_step(): void
    {
        $this->get('/taxi/chauffeur/handleiding')
            ->assertOk()
            ->assertSee('Handleiding chauffeur', false)
            ->assertSee('Inloggen', false)
            ->assertSee('Online zetten', false)
            ->assertSee('Iconen bovenin', false)
            ->assertSee('Auto — actieve rit', false)
            ->assertSee('Inbox — open aanvragen', false)
            ->assertSee('Klok — verlopen', false)
            ->assertSee('Doos — archief', false)
            ->assertSee('Nieuwe ritaanvraag', false)
            ->assertSee('Betalen en factuur', false)
            ->assertSee('Profiel', false)
            ->assertSee('feature-chauffeur-inbox.png', false)
            ->assertSee('feature-chauffeur-active.png', false)
            ->assertSee('class="guide-zoom"', false)
            ->assertSee('id="guide-lightbox"', false)
            ->assertSee('Tik om te vergroten', false)
            ->assertSee('Terug naar de chauffeur-app', false);
    }

    #[Test]
    public function chauffeur_app_shows_banner_and_profile_link_to_handleiding(): void
    {
        $html = view('taxi::driver-app.index', [
            'apiBase' => 'http://localhost/api/taxi/v1/driver',
            'pollMs' => 2000,
            'streamEnabled' => false,
            'appUrl' => 'http://localhost/taxi/chauffeur',
            'guideUrl' => 'http://localhost/taxi/chauffeur/handleiding',
            'faviconUrl' => '/favicon.ico',
            'faviconType' => 'image/x-icon',
            'notificationIcon' => '/favicon.ico',
        ])->render();

        $this->assertStringContainsString('id="guide-hint"', $html);
        $this->assertStringContainsString('Na wegklikken vind je die altijd terug onder', $html);
        $this->assertStringContainsString('profile-guide-link', $html);
        $this->assertStringContainsString('/taxi/chauffeur/handleiding', $html);
    }
}
