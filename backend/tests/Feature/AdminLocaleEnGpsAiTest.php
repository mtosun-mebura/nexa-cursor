<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Controllers\Admin\AiChatbotSettingsController;
use App\Modules\NexaTaxi\Controllers\Admin\GpsTrackingController;
use App\Modules\NexaTaxi\Controllers\Admin\KnowledgeDocumentController;
use App\Support\TenantPackageAddon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminLocaleEnGpsAiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Module-routes worden in de testomgeving niet altijd geladen (zie GpsTrackingAccessTest).
        View::addNamespace('taxi', app_path('Modules/NexaTaxi/Resources/views'));
        $routes = [
            'admin.taxi.gps_tracking.index' => ['get', '/admin/taxi/gps-tracker', GpsTrackingController::class, 'index'],
            'admin.taxi.gps_tracking.positions' => ['get', '/admin/taxi/gps-tracker/posities', GpsTrackingController::class, 'positions'],
            'admin.taxi.gps_tracking.unlock' => ['post', '/admin/taxi/gps-tracker/offline-ontgrendelen', GpsTrackingController::class, 'unlock'],
            'admin.taxi.gps_tracking.lock' => ['post', '/admin/taxi/gps-tracker/offline-verbergen', GpsTrackingController::class, 'lock'],
            'admin.taxi.gps_tracking.demo' => ['post', '/admin/taxi/gps-tracker/demo', GpsTrackingController::class, 'demo'],
            'admin.taxi.gps_tracking.code' => ['put', '/admin/taxi/gps-tracker/veiligheidscode', GpsTrackingController::class, 'updateCode'],
            'admin.taxi.gps_tracking.settings' => ['get', '/admin/taxi/gps-tracker/configuratie', GpsTrackingController::class, 'settings'],
            'admin.taxi.gps_tracking.settings.update' => ['put', '/admin/taxi/gps-tracker/configuratie', GpsTrackingController::class, 'updateSettings'],
            'admin.taxi.ai_chatbot.settings.edit' => ['get', '/admin/taxi/ai-chatbot/instellingen', AiChatbotSettingsController::class, 'edit'],
            'admin.taxi.ai_chatbot.settings.update' => ['put', '/admin/taxi/ai-chatbot/instellingen', AiChatbotSettingsController::class, 'update'],
            'admin.taxi.knowledge_documents.index' => ['get', '/admin/taxi/ai-chatbot', KnowledgeDocumentController::class, 'index'],
            'admin.taxi.knowledge_documents.create' => ['get', '/admin/taxi/ai-chatbot/create', KnowledgeDocumentController::class, 'create'],
            'admin.taxi.knowledge_documents.store' => ['post', '/admin/taxi/ai-chatbot', KnowledgeDocumentController::class, 'store'],
            'admin.taxi.knowledge_documents.show' => ['get', '/admin/taxi/ai-chatbot/{knowledge_document}', KnowledgeDocumentController::class, 'show'],
            'admin.taxi.knowledge_documents.edit' => ['get', '/admin/taxi/ai-chatbot/{knowledge_document}/edit', KnowledgeDocumentController::class, 'edit'],
            'admin.taxi.knowledge_documents.update' => ['put', '/admin/taxi/ai-chatbot/{knowledge_document}', KnowledgeDocumentController::class, 'update'],
            'admin.taxi.knowledge_documents.destroy' => ['delete', '/admin/taxi/ai-chatbot/{knowledge_document}', KnowledgeDocumentController::class, 'destroy'],
            'admin.taxi.knowledge_documents.generate_from_website' => ['post', '/admin/taxi/ai-chatbot/genereer-van-website', KnowledgeDocumentController::class, 'generateFromWebsite'],
            'admin.taxi.knowledge_documents.format_content' => ['post', '/admin/taxi/ai-chatbot/tekst-opmaken', KnowledgeDocumentController::class, 'formatContent'],
        ];
        foreach ($routes as $name => [$method, $uri, $controller, $action]) {
            if (! Route::has($name)) {
                Route::{$method}($uri, [$controller, $action])->middleware('web')->name($name);
            }
        }
        Route::getRoutes()->refreshNameLookups();
    }

    protected function tearDown(): void
    {
        // Static scope-cache van GeneralSetting niet laten lekken naar andere tests.
        (new \ReflectionProperty(\App\Models\GeneralSetting::class, 'resolvedScopeCompanyId'))->setValue(null, null);
        (new \ReflectionProperty(\App\Models\GeneralSetting::class, 'resolvedScopeCompanyIdComputed'))->setValue(null, false);

        parent::tearDown();
    }

    private function superAdmin(): User
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }

    private function gpsCompany(): Company
    {
        return Company::query()->create([
            'name' => 'Locale GPS',
            'is_active' => true,
            'package_key' => 'pro',
            'package_addons' => [TenantPackageAddon::GPS_TRACKING => 1],
        ]);
    }

    private function page(string $route, string $locale, ?Company $company = null): string
    {
        if (! Route::has($route)) {
            $this->markTestSkipped("Route {$route} niet geregistreerd in deze testomgeving.");
        }
        $company ??= $this->gpsCompany();
        // Admin-scope wordt buiten console-context bepaald; forceer hem hier op de tenant.
        (new \ReflectionProperty(\App\Models\GeneralSetting::class, 'resolvedScopeCompanyId'))->setValue(null, $company->id);
        (new \ReflectionProperty(\App\Models\GeneralSetting::class, 'resolvedScopeCompanyIdComputed'))->setValue(null, true);
        $session = ['selected_tenant' => $company->id];
        if ($locale !== 'nl') {
            $session['locale'] = $locale;
        }

        return $this->actingAs($this->superAdmin())
            ->withSession($session)
            ->get(route($route))
            ->assertOk()
            ->getContent();
    }

    #[Test]
    public function gps_map_page_is_english_in_en(): void
    {
        $html = $this->page('admin.taxi.gps_tracking.index', 'en');

        $this->assertSame('en', app()->getLocale());
        $this->assertStringContainsString('Live map', $html);
        $this->assertStringContainsString('Follow fleet', $html);
        $this->assertStringContainsString('Security code', $html);
        $this->assertStringContainsString('Show offline vehicles', $html);
        $this->assertStringContainsString('Switch map to light mode', $html); // JS-string via @json
        $this->assertStringContainsString('Follow :plate on the map', $html);
        $this->assertStringNotContainsString('Live kaart', $html);
        $this->assertStringNotContainsString('Volg vloot', $html);
        $this->assertStringNotContainsString('Veiligheidscode', $html);
        $this->assertStringNotContainsString('Offline voertuigen tonen', $html);
    }

    #[Test]
    public function gps_settings_page_is_english_in_en(): void
    {
        $html = $this->page('admin.taxi.gps_tracking.settings', 'en');

        $this->assertStringContainsString('GPS configuration', $html);
        $this->assertStringContainsString('Changes are saved automatically.', $html);
        $this->assertStringContainsString('Back to map', $html);
        $this->assertStringContainsString('One color per car type', $html);
        $this->assertStringContainsString('Saving failed. Please try again.', $html);
        $this->assertStringContainsString('Orange', $html); // kleurpreset-label via call-site __()
        $this->assertStringNotContainsString('Terug naar kaart', $html);
        $this->assertStringNotContainsString('Eén kleur per type auto', $html);
        $this->assertStringNotContainsString('Oranje', $html);
    }

    #[Test]
    public function ai_chatbot_settings_page_is_english_in_en(): void
    {
        $html = $this->page('admin.taxi.ai_chatbot.settings.edit', 'en');

        $this->assertStringContainsString('Welcome message', $html);
        $this->assertStringContainsString('Chat window', $html);
        $this->assertStringContainsString('Knowledge base', $html);
        $this->assertStringContainsString('Save', $html);
        $this->assertStringNotContainsString('Welkomstbericht', $html);
        $this->assertStringNotContainsString('Chatvenster', $html);
        $this->assertStringNotContainsString('Kennisbank', $html);
    }

    #[Test]
    public function knowledge_document_create_page_is_english_in_en(): void
    {
        $html = $this->page('admin.taxi.knowledge_documents.create', 'en');

        $this->assertStringContainsString('New knowledge document', $html);
        $this->assertStringContainsString('Format text', $html);
        $this->assertStringContainsString('Shorten text', $html);
        $this->assertStringContainsString('Category', $html);
        $this->assertStringContainsString('>Services</option>', $html);
        $this->assertStringContainsString('Formatting text failed.', $html);
        $this->assertStringNotContainsString('Nieuw kennisdocument', $html);
        $this->assertStringNotContainsString('Tekst opmaken', $html);
        $this->assertStringNotContainsString('Tekst inkorten', $html);
    }

    #[Test]
    public function dutch_locale_keeps_the_original_dutch_text(): void
    {
        $map = $this->page('admin.taxi.gps_tracking.index', 'nl');
        $this->assertSame('nl', app()->getLocale());
        $this->assertStringContainsString('Live kaart', $map);
        $this->assertStringContainsString('Volg vloot', $map);
        $this->assertStringContainsString('Offline voertuigen tonen', $map);
        $this->assertStringContainsString('Klik op een kenteken om het voertuig te volgen.', $map);
        $this->assertStringNotContainsString('Follow fleet', $map);

        $settings = $this->page('admin.taxi.gps_tracking.settings', 'nl');
        $this->assertStringContainsString('Terug naar kaart', $settings);
        $this->assertStringContainsString('Oranje', $settings);

        $create = $this->page('admin.taxi.knowledge_documents.create', 'nl');
        $this->assertStringContainsString('Nieuw kennisdocument', $create);
        $this->assertStringContainsString('Tekst opmaken', $create);
        $this->assertStringContainsString('>Diensten</option>', $create);
    }
}
