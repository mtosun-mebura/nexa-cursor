<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Controllers\Admin\DispatchSettingsController;
use App\Services\WhatsAppBookingMessageComposer;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DispatchSettingsWhatsappStatusEventsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        View::addNamespace('taxi', app_path('Modules/NexaTaxi/Resources/views'));

        $namedRoutes = [
            'admin.taxi.dispatch_settings.edit' => ['get', '/admin/taxi/dispatch-instellingen', [DispatchSettingsController::class, 'edit']],
            'admin.taxi.dispatch_settings.update' => ['put', '/admin/taxi/dispatch-instellingen', [DispatchSettingsController::class, 'update']],
            'admin.taxi.dispatch_settings.customer_accept_email.edit' => ['get', '/admin/taxi/dispatch-instellingen/klant-e-mail', fn () => 'ok'],
            'admin.taxi.ride_requests.index' => ['get', '/admin/taxi/ritten', fn () => 'ok'],
            'admin.email-templates.index' => ['get', '/admin/email-templates', fn () => 'ok'],
            'admin.settings.general.index' => ['get', '/admin/settings/general', fn () => 'ok'],
        ];
        foreach ($namedRoutes as $name => [$method, $uri, $action]) {
            if (! Route::has($name)) {
                Route::{$method}($uri, $action)->middleware('web')->name($name);
            }
        }
        Route::getRoutes()->refreshNameLookups();
    }

    #[Test]
    public function dispatch_settings_default_status_events_exclude_completed(): void
    {
        $company = Company::query()->create(['name' => 'Dispatch WA Co', 'is_active' => true]);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin, 'web');
        session(['selected_tenant' => $company->id]);

        $view = app(DispatchSettingsController::class)->edit();
        $data = $view->getData();

        $this->assertSame('taxi::admin.dispatch-settings.edit', $view->name());
        $this->assertArrayHasKey('whatsappStatusEventLabels', $data);
        $this->assertSame(
            WhatsAppBookingMessageComposer::statusEventLabels(),
            $data['whatsappStatusEventLabels']
        );
        $this->assertContains(WhatsAppBookingMessageComposer::EVENT_ACCEPTED, $data['customerWhatsappStatusEvents']);
        $this->assertContains(WhatsAppBookingMessageComposer::EVENT_STARTED, $data['customerWhatsappStatusEvents']);
        $this->assertNotContains(WhatsAppBookingMessageComposer::EVENT_COMPLETED, $data['customerWhatsappStatusEvents']);
    }
}
