<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\NexaSuiteMarketplaceSetting;
use App\Models\PaymentProvider;
use App\Models\User;
use App\Modules\NexaTaxi\Controllers\Api\AppBootstrapController;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use App\Modules\NexaTaxi\Services\TaxiMolliePaymentService;
use App\Modules\NexaTaxi\Services\TaxiRidePaymentService;
use App\Services\NexaSuitePublicHost;
use App\Services\PaymentProviderService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NexaSuiteNativeAppHostAndPaymentTest extends TestCase
{
    private const SUITE_KEY = 'test_suitekey1234567890';

    private const TENANT_KEY = 'test_tenantkey1234567';

    protected function tearDown(): void
    {
        Mockery::close();
        DB::disconnect('module_taxi');
        parent::tearDown();
    }

    #[Test]
    public function default_public_host_is_production(): void
    {
        $settings = NexaSuiteMarketplaceSetting::current();
        $settings->public_base_url = null;
        $settings->public_hosts = null;
        $settings->save();

        $this->assertSame('https://nexasuite.nl', app(NexaSuitePublicHost::class)->current());

        app()->setLocale('en');
        $user = $this->superAdmin();
        $html = $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.nexa-suite-bookings.settings'))
            ->assertOk()
            ->assertSee('value="https://nexasuite.nl"', false)
            ->assertSee('value="https://nexasuite.online"', false)
            ->assertSee('Public host', false)
            ->assertSee('Environment', false)
            ->assertSee('Host', false)
            ->assertSee('Productie', false)
            ->assertSee('Test', false)
            ->assertSee('Use for payments', false)
            ->assertSee('Add', false)
            ->assertSee('Save', false)
            ->assertSee('Delete', false)
            ->assertSee('id="nexa-public-host-draft-discard"', false)
            ->assertSee('id="nexa-public-host-draft"', false)
            ->getContent();
        $this->assertStringNotContainsString('Publieke host', $html);
        $this->assertStringNotContainsString('Gebruik voor betalingen', $html);
        $this->assertStringNotContainsString('Modified', $html);
        $this->assertMatchesRegularExpression(
            '/id="nexa-public-host-draft"[\\s\\S]*id="nexa-host-url-new"[\\s\\S]*type="button"[^>]*id="nexa-public-host-draft-discard"/',
            $html
        );

        $this->ensureEnvironmentsRoute();
        $this->getJson('/api/taxi/v1/app/environments')
            ->assertOk()
            ->assertJsonPath('default_host', 'https://nexasuite.nl')
            ->assertJsonFragment(['name' => 'Productie', 'host' => 'https://nexasuite.nl'])
            ->assertJsonFragment(['name' => 'Test', 'host' => 'https://nexasuite.online']);
    }

    #[Test]
    public function public_hosts_can_be_added_edited_and_deleted(): void
    {
        $user = $this->superAdmin();

        $this->actingAs($user)
            ->post(route('admin.nexa-suite-bookings.settings.public-hosts.store'), [
                'name' => 'Beta',
                'host' => 'https://beta.example.nl/',
            ])
            ->assertRedirect(route('admin.nexa-suite-bookings.settings'))
            ->assertSessionHas('success', 'Omgeving opgeslagen.')
            ->assertSessionHasNoErrors();

        $beta = collect(app(NexaSuitePublicHost::class)->entries())->firstWhere('name', 'Beta');
        $this->assertNotNull($beta);
        $this->assertSame('https://beta.example.nl', $beta['host']);
        $this->assertFalse($beta['payments']);
        $this->assertSame('https://nexasuite.nl', app(NexaSuitePublicHost::class)->current());

        $this->actingAs($user)
            ->get(route('admin.nexa-suite-bookings.settings'))
            ->assertOk()
            ->assertSee('Beta', false)
            ->assertSee('https://beta.example.nl', false);

        $this->actingAs($user)
            ->put(route('admin.nexa-suite-bookings.settings.public-hosts.update', $beta['id']), [
                'name' => 'Staging',
                'host' => 'https://staging.example.nl/',
            ])
            ->assertRedirect(route('admin.nexa-suite-bookings.settings'))
            ->assertSessionHas('success', 'Omgeving opgeslagen.')
            ->assertSessionHasNoErrors();

        $staging = collect(app(NexaSuitePublicHost::class)->entries())->firstWhere('name', 'Staging');
        $this->assertNotNull($staging);
        $this->assertSame('https://staging.example.nl', $staging['host']);
        $this->assertNull(collect(app(NexaSuitePublicHost::class)->entries())->firstWhere('name', 'Beta'));

        $this->actingAs($user)
            ->post(route('admin.nexa-suite-bookings.settings.public-hosts.payments', $staging['id']))
            ->assertRedirect(route('admin.nexa-suite-bookings.settings'))
            ->assertSessionHas('success', 'Deze omgeving wordt gebruikt voor betalingen.');
        $this->assertSame('https://staging.example.nl', app(NexaSuitePublicHost::class)->current());

        $this->actingAs($user)
            ->put(route('admin.nexa-suite-bookings.settings.update'), $this->settingsPayload())
            ->assertRedirect(route('admin.nexa-suite-bookings.settings'));
        $this->assertNotNull(collect(app(NexaSuitePublicHost::class)->entries())->firstWhere('name', 'Staging'));

        $this->actingAs($user)
            ->delete(route('admin.nexa-suite-bookings.settings.public-hosts.destroy', $staging['id']))
            ->assertRedirect(route('admin.nexa-suite-bookings.settings'))
            ->assertSessionHas('success', 'Omgeving verwijderd.');
        $this->assertNull(collect(app(NexaSuitePublicHost::class)->entries())->firstWhere('name', 'Staging'));
        $this->assertSame('https://nexasuite.nl', app(NexaSuitePublicHost::class)->current());

        $this->actingAs($user)
            ->delete(route('admin.nexa-suite-bookings.settings.public-hosts.destroy', 'test'))
            ->assertRedirect(route('admin.nexa-suite-bookings.settings'));
        $this->actingAs($user)
            ->delete(route('admin.nexa-suite-bookings.settings.public-hosts.destroy', 'productie'))
            ->assertRedirect(route('admin.nexa-suite-bookings.settings'))
            ->assertSessionHasErrors('public_hosts');
        $this->assertSame('https://nexasuite.nl', app(NexaSuitePublicHost::class)->current());
    }

    #[Test]
    public function invalid_public_host_is_rejected(): void
    {
        $user = $this->superAdmin();
        $settings = NexaSuiteMarketplaceSetting::current();
        $settings->public_base_url = null;
        $settings->public_hosts = null;
        $settings->save();

        $this->actingAs($user)
            ->put(route('admin.nexa-suite-bookings.settings.public-hosts.update', 'productie'), [
                'name' => 'Productie',
                'host' => 'http://evil.example',
            ])
            ->assertRedirect(route('admin.nexa-suite-bookings.settings'))
            ->assertSessionHasErrors('host');

        $this->assertSame('https://nexasuite.nl', app(NexaSuitePublicHost::class)->current());

        $this->actingAs($user)
            ->get(route('admin.nexa-suite-bookings.settings'))
            ->assertOk()
            ->assertSee('Vul een geldige https-URL in. http is alleen toegestaan voor localhost.', false)
            ->assertSee('border-destructive', false)
            ->assertSee('http://evil.example', false);

        $this->actingAs($user)
            ->post(route('admin.nexa-suite-bookings.settings.public-hosts.store'), [
                'name' => 'Productie',
                'host' => 'https://other.example.nl',
            ])
            ->assertRedirect(route('admin.nexa-suite-bookings.settings'))
            ->assertSessionHasErrors('name');
        $this->assertSame(2, count(app(NexaSuitePublicHost::class)->entries()));
    }

    #[Test]
    public function localhost_http_host_is_allowed(): void
    {
        $user = $this->superAdmin();

        $this->actingAs($user)
            ->post(route('admin.nexa-suite-bookings.settings.public-hosts.store'), [
                'name' => 'Lokaal',
                'host' => 'http://localhost:8080/',
            ])
            ->assertRedirect(route('admin.nexa-suite-bookings.settings'))
            ->assertSessionHasNoErrors();

        $local = collect(app(NexaSuitePublicHost::class)->entries())->firstWhere('name', 'Lokaal');
        $this->assertNotNull($local);
        $this->assertSame('http://localhost:8080', $local['host']);
    }

    #[Test]
    public function legacy_public_base_url_is_carried_into_the_list(): void
    {
        $settings = NexaSuiteMarketplaceSetting::current();
        $settings->public_base_url = 'https://beta.example.nl/';
        $settings->public_hosts = null;
        $settings->save();

        $active = collect(app(NexaSuitePublicHost::class)->entries())->firstWhere('payments', true);
        $this->assertSame('https://beta.example.nl', $active['host']);
        $this->assertSame('Aangepast', $active['name']);
        $this->assertNotNull(collect(app(NexaSuitePublicHost::class)->entries())->firstWhere('name', 'Productie'));

        app(NexaSuitePublicHost::class)->backfillFromLegacyColumn();
        $stored = NexaSuiteMarketplaceSetting::current()->fresh()->public_hosts;
        $this->assertIsArray($stored);
        $this->assertSame('https://beta.example.nl', collect($stored)->firstWhere('payments', true)['host']);

        app(NexaSuitePublicHost::class)->backfillFromLegacyColumn();
        $this->assertCount(1, collect(NexaSuiteMarketplaceSetting::current()->fresh()->public_hosts)->where('name', 'Aangepast'));
    }

    #[Test]
    public function native_client_header_is_recognized(): void
    {
        $request = Request::create('/api/taxi/v1/driver/dispatch/rides/1/payment', 'POST');
        $this->assertFalse(TaxiRidePaymentService::usesNativeAppClient($request));

        $request->headers->set('X-Nexa-Client', 'native');
        $this->assertTrue(TaxiRidePaymentService::usesNativeAppClient($request));
    }

    #[Test]
    public function native_app_payment_uses_suite_mollie_key_and_public_host(): void
    {
        $this->bootTaxiPayments();
        URL::forceRootUrl('https://nexasuite.online');

        $captured = [];
        $service = $this->paymentService($captured);
        $ride = $this->tenantRide();

        $result = $service->createDriverPayment('module_taxi', $ride, 18.50, true);

        $this->assertSame(self::SUITE_KEY, $captured['key']);
        $this->assertStringStartsWith('https://pay.example.test/', $captured['redirect']);
        $this->assertStringStartsWith('https://pay.example.test/api/taxi/webhooks/mollie', (string) $captured['webhook']);
        $this->assertStringNotContainsString('nexasuite.online', $captured['redirect']);
        $this->assertStringNotContainsString('nexasuite.online', (string) $captured['webhook']);
        $this->assertSame('suite', $result['payment']->mollie_payload['nexa_mollie_account']);
        $this->assertSame('https://www.mollie.com/checkout/native-suite', $result['checkout_url']);
    }

    #[Test]
    public function native_payment_uses_default_productie_host_for_redirect_and_webhook(): void
    {
        $this->bootTaxiPayments();
        $settings = NexaSuiteMarketplaceSetting::current()->fresh();
        $settings->public_hosts = null;
        $settings->public_base_url = null;
        $settings->mollie_webhook_url = null;
        $settings->save();

        URL::forceRootUrl('https://nexasuite.online');

        $captured = [];
        $service = $this->paymentService($captured);
        $ride = $this->tenantRide();

        $service->createDriverPayment('module_taxi', $ride, 18.50, true);

        $this->assertSame(self::SUITE_KEY, $captured['key']);
        $this->assertStringStartsWith('https://nexasuite.nl/', $captured['redirect']);
        $this->assertStringStartsWith('https://nexasuite.nl/api/taxi/webhooks/mollie', (string) $captured['webhook']);
        $this->assertStringNotContainsString('nexasuite.online', $captured['redirect']);
        $this->assertStringNotContainsString('nexasuite.online', (string) $captured['webhook']);
    }

    #[Test]
    public function tenant_website_booking_payment_keeps_tenant_mollie_key(): void
    {
        $this->bootTaxiPayments();
        URL::forceRootUrl('https://nexasuite.online');

        $captured = [];
        $service = $this->paymentService($captured);
        $ride = $this->tenantRide();

        $result = $service->createBookingPayment('module_taxi', $ride, 18.50);

        $this->assertSame(self::TENANT_KEY, $captured['key']);
        $this->assertStringContainsString('nexasuite.online', $captured['redirect']);
        $this->assertStringNotContainsString('pay.example.test', $captured['redirect']);
        $this->assertSame('tenant', $result['payment']->mollie_payload['nexa_mollie_account']);
    }

    private function superAdmin(): User
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole('super-admin');

        return $user;
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function settingsPayload(array $extra = []): array
    {
        return $extra + [
            'fee_percent' => 10,
            'auto_generate' => 1,
            'auto_send' => 1,
            'billing_day' => 1,
            'billing_time' => '06:30',
            'tax_rate_percent' => 21,
            'payment_terms_days' => 14,
            'dunning_first_interval_days' => 1,
            'dunning_interval_days' => 14,
            'invoice_number_prefix' => 'NSB',
            'invoice_title' => 'NEXA Suite boekingsfactuur',
        ];
    }

    private function ensureEnvironmentsRoute(): void
    {
        foreach (Route::getRoutes() as $route) {
            if ($route->uri() === 'api/taxi/v1/app/environments' && in_array('GET', $route->methods(), true)) {
                return;
            }
        }

        Route::middleware('api')->prefix('api/taxi')->group(function () {
            Route::get('v1/app/environments', [AppBootstrapController::class, 'environments']);
        });
    }

    private function ensureNamedRoute(string $name, string $uri): void
    {
        if (Route::has($name)) {
            return;
        }

        $route = app('router')->newRoute(['GET'], $uri, [
            'uses' => fn () => 'ok',
            'as' => $name,
        ]);
        app('router')->getRoutes()->add($route);
    }

    private function bootTaxiPayments(): void
    {
        config(['database.connections.module_taxi' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);
        DB::purge('module_taxi');

        // Route-cache negeert een naam die ná add() fluent wordt gezet.
        $this->ensureNamedRoute('taxi.chauffeur.payment.return', '/taxi/chauffeur/betaling/terug');
        $this->ensureNamedRoute('nexataxi.booking.payment.return', '/boeking/betaling/terug');

        Schema::connection('module_taxi')->create('ride_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('status', 32)->nullable();
            $table->string('source', 32)->nullable();
            $table->string('payment_method', 20)->nullable();
            $table->string('payment_status', 32)->nullable();
            $table->string('pickup_address')->nullable();
            $table->string('dropoff_address')->nullable();
            $table->string('customer_name')->nullable();
            $table->decimal('final_price', 10, 2)->nullable();
            $table->json('booking_payload')->nullable();
            $table->timestamps();
        });
        Schema::connection('module_taxi')->create('ride_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_request_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('channel', 20)->nullable();
            $table->string('mollie_payment_id', 64)->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->char('currency', 3)->default('EUR');
            $table->string('status', 24)->nullable();
            $table->string('checkout_url', 500)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('mollie_payload')->nullable();
            $table->timestamps();
        });

        $company = Company::create(['name' => 'Tenant Taxi', 'is_active' => true]);
        PaymentProvider::create([
            'company_id' => $company->id,
            'name' => 'Mollie tenant',
            'provider_type' => 'mollie',
            'is_active' => true,
            'config' => [
                'api_key' => Crypt::encryptString(self::TENANT_KEY),
                'test_mode' => true,
            ],
        ]);

        $settings = NexaSuiteMarketplaceSetting::current();
        $settings->setEncryptedMollieApiKey(self::SUITE_KEY);
        $settings->public_base_url = null;
        $settings->public_hosts = null;
        $settings->mollie_webhook_url = null;
        $settings->save();

        $hosts = app(NexaSuitePublicHost::class);
        $hosts->add('Betaling', 'https://pay.example.test');
        $paymentHost = collect($hosts->entries())->firstWhere('name', 'Betaling');
        $hosts->useForPayments((string) $paymentHost['id']);
        $settings = NexaSuiteMarketplaceSetting::current()->fresh();
        $settings->public_base_url = null;
        $settings->save();

        $this->companyId = (int) $company->id;
    }

    private int $companyId = 0;

    private function tenantRide(): RideRequest
    {
        return RideRequest::on('module_taxi')->create([
            'company_id' => $this->companyId,
            'driver_id' => 7,
            'status' => RideRequest::STATUS_ACCEPTED,
            'source' => RideRequest::SOURCE_BOOKING,
            'payment_method' => RideRequest::PAYMENT_METHOD_DRIVER,
            'payment_status' => RideRequest::PAYMENT_STATUS_PENDING,
            'pickup_address' => 'Station',
            'dropoff_address' => 'Centrum',
            'customer_name' => 'Klant',
            'booking_payload' => ['channel' => 'website'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $captured
     */
    private function paymentService(array &$captured): TaxiRidePaymentService
    {
        $settings = Mockery::mock(TaxiDispatchSettingsService::class);
        $settings->shouldReceive('paymentDriverEnabled')->andReturn(true);

        $mollie = Mockery::mock(TaxiMolliePaymentService::class);
        $mollie->shouldReceive('createPayment')->once()->andReturnUsing(
            function ($key, $amount, $description, $redirect, $webhook) use (&$captured) {
                $captured = [
                    'key' => $key,
                    'amount' => $amount,
                    'redirect' => $redirect,
                    'webhook' => $webhook,
                ];

                return [
                    'id' => 'tr_test_native',
                    'status' => 'open',
                    '_links' => ['checkout' => ['href' => 'https://www.mollie.com/checkout/native-suite']],
                ];
            }
        );
        $mollie->shouldReceive('checkoutUrl')->once()->andReturn('https://www.mollie.com/checkout/native-suite');

        return new TaxiRidePaymentService($settings, $mollie, app(PaymentProviderService::class));
    }
}
