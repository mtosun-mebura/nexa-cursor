<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiPickupProposalService;
use App\Modules\NexaTaxi\Services\WhatsAppPickupProposalMockService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminWhatsAppPickupProposalMockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        config(['database.connections.module_taxi' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        Schema::connection('module_taxi')->create('ride_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('status', 32)->default('offered');
            $table->string('source', 32)->nullable();
            $table->string('pickup_address');
            $table->string('dropoff_address');
            $table->unsignedSmallInteger('passengers')->default(1);
            $table->dateTime('pickup_at');
            $table->dateTime('pickup_proposal_at')->nullable();
            $table->string('pickup_proposal_status', 32)->nullable();
            $table->text('pickup_proposal_customer_remark')->nullable();
            $table->timestamp('pickup_proposal_sent_at')->nullable();
            $table->timestamp('pickup_proposal_responded_at')->nullable();
            $table->string('pickup_proposal_whatsapp_wamid', 191)->nullable();
            $table->decimal('quoted_price', 10, 2)->nullable();
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->text('customer_note')->nullable();
            $table->json('booking_payload')->nullable();
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('ride_dispatch_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_request_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('driver_id');
            $table->string('status', 24)->default('pending');
            $table->unsignedSmallInteger('wave')->default(1);
            $table->timestamp('offered_at');
            $table->timestamp('expires_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_super_admin_can_open_mock_page(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin, 'web')
            ->get(route('admin.whatsapp-pickup-proposal-mock.index'))
            ->assertOk()
            ->assertSee('WhatsApp ophaalvoorstel test', false)
            ->assertSee('Testdata aanmaken', false);
    }

    public function test_staff_cannot_open_mock_page(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $this->actingAs($staff, 'web')
            ->get(route('admin.whatsapp-pickup-proposal-mock.index'))
            ->assertRedirect();
    }

    public function test_seed_and_accept_changes_only_the_targeted_ride(): void
    {
        $company = Company::query()->create(['name' => 'Mock Taxi', 'is_active' => true]);
        $admin = $this->superAdmin();

        $this->actingAs($admin, 'web')
            ->withSession(['selected_tenant' => $company->id])
            ->post(route('admin.whatsapp-pickup-proposal-mock.seed'))
            ->assertRedirect(route('admin.whatsapp-pickup-proposal-mock.index', ['saved' => 1]));

        $rides = RideRequest::on('module_taxi')
            ->where('customer_note', 'like', '%'.WhatsAppPickupProposalMockService::NOTE_MARKER.'%')
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $rides);
        $this->assertSame($rides[0]->customer_phone, $rides[1]->customer_phone);
        $this->assertNotSame($rides[0]->pickup_proposal_whatsapp_wamid, $rides[1]->pickup_proposal_whatsapp_wamid);
        $this->assertSame(RideRequest::PICKUP_PROPOSAL_PENDING, $rides[0]->pickup_proposal_status);
        $this->assertSame(RideRequest::PICKUP_PROPOSAL_PENDING, $rides[1]->pickup_proposal_status);

        $this->actingAs($admin, 'web')
            ->post(route('admin.whatsapp-pickup-proposal-mock.simulate'), [
                'ride_id' => $rides[0]->id,
                'action' => 'accept',
            ])
            ->assertRedirect(route('admin.whatsapp-pickup-proposal-mock.index', ['saved' => 1]));

        $freshA = RideRequest::on('module_taxi')->find($rides[0]->id);
        $freshB = RideRequest::on('module_taxi')->find($rides[1]->id);
        $this->assertSame(RideRequest::PICKUP_PROPOSAL_ACCEPTED, $freshA->pickup_proposal_status);
        $this->assertSame(RideRequest::PICKUP_PROPOSAL_PENDING, $freshB->pickup_proposal_status);
    }

    public function test_mocked_remark_is_stored_without_accepting(): void
    {
        $company = Company::query()->create(['name' => 'Mock Taxi', 'is_active' => true]);
        $admin = $this->superAdmin();

        $this->actingAs($admin, 'web')
            ->withSession(['selected_tenant' => $company->id])
            ->post(route('admin.whatsapp-pickup-proposal-mock.seed'));

        $ride = RideRequest::on('module_taxi')
            ->where('customer_note', 'like', '%'.WhatsAppPickupProposalMockService::NOTE_MARKER.'%')
            ->orderBy('id')
            ->first();

        $this->actingAs($admin, 'web')
            ->post(route('admin.whatsapp-pickup-proposal-mock.simulate'), [
                'ride_id' => $ride->id,
                'action' => 'remark',
            ])
            ->assertRedirect();

        $fresh = RideRequest::on('module_taxi')->find($ride->id);
        $this->assertSame(RideRequest::PICKUP_PROPOSAL_PENDING, $fresh->pickup_proposal_status);
        $this->assertSame('Graag 10 minuten later', $fresh->pickup_proposal_customer_remark);
    }

    public function test_mock_is_blocked_when_disabled(): void
    {
        config(['whatsapp.inbound_mock_enabled' => false]);
        $company = Company::query()->create(['name' => 'Mock Taxi', 'is_active' => true]);
        $admin = $this->superAdmin();

        $this->actingAs($admin, 'web')
            ->withSession(['selected_tenant' => $company->id])
            ->post(route('admin.whatsapp-pickup-proposal-mock.seed'))
            ->assertSessionHasErrors('environment');
    }

    public function test_driver_app_proposal_appears_and_can_be_accepted_via_mock_webhook(): void
    {
        $company = Company::query()->create(['name' => 'Mock Taxi', 'is_active' => true]);
        $admin = $this->superAdmin();
        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'web']);
        $driver = User::factory()->create(['company_id' => $company->id]);
        $driver->assignRole('chauffeur');

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'source' => RideRequest::SOURCE_MANUAL,
            'pickup_address' => 'Damrak 1, Amsterdam',
            'dropoff_address' => 'Utrecht CS',
            'passengers' => 1,
            'pickup_at' => now('UTC')->subHour()->format('Y-m-d H:i:s'),
            'quoted_price' => 25.00,
            'customer_name' => 'Chauffeur-app klant',
            'customer_phone' => '0622222222',
        ]);

        app(TaxiPickupProposalService::class)->proposeNewPickup(
            'module_taxi',
            $driver,
            (int) $ride->id,
            now()->addHours(3)->toIso8601String()
        );

        $fresh = RideRequest::on('module_taxi')->find($ride->id);
        $this->assertSame(RideRequest::PICKUP_PROPOSAL_PENDING, $fresh->pickup_proposal_status);
        $this->assertNotEmpty($fresh->pickup_proposal_whatsapp_wamid);
        $this->assertStringStartsWith('wamid.NEXA-MOCK-', $fresh->pickup_proposal_whatsapp_wamid);

        $this->actingAs($admin, 'web')
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.whatsapp-pickup-proposal-mock.index'))
            ->assertOk()
            ->assertSee('Chauffeur-app klant', false)
            ->assertSee('Chauffeur-app', false);

        $this->actingAs($admin, 'web')
            ->post(route('admin.whatsapp-pickup-proposal-mock.simulate'), [
                'ride_id' => $fresh->id,
                'action' => 'accept',
            ])
            ->assertRedirect(route('admin.whatsapp-pickup-proposal-mock.index', ['saved' => 1]));

        $accepted = RideRequest::on('module_taxi')->find($ride->id);
        $this->assertSame(RideRequest::PICKUP_PROPOSAL_ACCEPTED, $accepted->pickup_proposal_status);
        $this->assertSame(
            $fresh->pickup_proposal_at->format('Y-m-d H:i:s'),
            $accepted->pickup_at->format('Y-m-d H:i:s')
        );
    }

    public function test_clear_seeded_data_keeps_driver_app_proposals(): void
    {
        $company = Company::query()->create(['name' => 'Mock Taxi', 'is_active' => true]);
        $admin = $this->superAdmin();
        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'web']);
        $driver = User::factory()->create(['company_id' => $company->id]);
        $driver->assignRole('chauffeur');

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now('UTC')->subHour()->format('Y-m-d H:i:s'),
            'customer_name' => 'Chauffeur-app klant',
            'customer_phone' => '0622222222',
        ]);

        app(TaxiPickupProposalService::class)->proposeNewPickup(
            'module_taxi',
            $driver,
            (int) $ride->id,
            now()->addHours(3)->toIso8601String()
        );

        $this->actingAs($admin, 'web')
            ->withSession(['selected_tenant' => $company->id])
            ->post(route('admin.whatsapp-pickup-proposal-mock.seed'))
            ->assertRedirect();

        $this->assertGreaterThanOrEqual(3, RideRequest::on('module_taxi')->count());

        $this->actingAs($admin, 'web')
            ->post(route('admin.whatsapp-pickup-proposal-mock.clear'))
            ->assertRedirect();

        $this->assertTrue(RideRequest::on('module_taxi')->whereKey($ride->id)->exists());
        $this->assertSame(
            0,
            RideRequest::on('module_taxi')
                ->where('customer_note', 'like', '%'.WhatsAppPickupProposalMockService::NOTE_MARKER.'%')
                ->count()
        );
    }

    public function test_feed_lists_newest_proposal_first(): void
    {
        $company = Company::query()->create(['name' => 'Mock Taxi', 'is_active' => true]);
        $admin = $this->superAdmin();
        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'web']);
        $driver = User::factory()->create(['company_id' => $company->id]);
        $driver->assignRole('chauffeur');

        $older = RideRequest::on('module_taxi')->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'Oud',
            'dropoff_address' => 'B',
            'pickup_at' => now('UTC')->subHour()->format('Y-m-d H:i:s'),
            'pickup_proposal_at' => now('UTC')->addHour()->format('Y-m-d H:i:s'),
            'pickup_proposal_status' => RideRequest::PICKUP_PROPOSAL_PENDING,
            'pickup_proposal_sent_at' => now()->subMinutes(10),
            'customer_name' => 'Oudere klant',
            'customer_phone' => '0611111111',
        ]);
        $newer = RideRequest::on('module_taxi')->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'Nieuw',
            'dropoff_address' => 'B',
            'pickup_at' => now('UTC')->subHour()->format('Y-m-d H:i:s'),
            'pickup_proposal_at' => now('UTC')->addHours(2)->format('Y-m-d H:i:s'),
            'pickup_proposal_status' => RideRequest::PICKUP_PROPOSAL_PENDING,
            'pickup_proposal_sent_at' => now(),
            'customer_name' => 'Nieuwere klant',
            'customer_phone' => '0622222222',
        ]);

        $feed = $this->actingAs($admin, 'web')
            ->getJson(route('admin.whatsapp-pickup-proposal-mock.feed'))
            ->assertOk()
            ->assertJsonPath('rides.0.id', $newer->id)
            ->assertJsonPath('rides.1.id', $older->id)
            ->json();

        $this->assertNotEmpty($feed['rides'][0]['message_preview'] ?? '');
        $this->assertStringContainsString('Nieuwere klant', $feed['rides'][0]['message_preview']);
        $this->assertStringContainsString('Voorgesteld ophaalmoment', $feed['rides'][0]['message_preview']);
    }

    public function test_selected_seeded_rides_are_deleted_and_driver_rides_are_hidden(): void
    {
        $company = Company::query()->create(['name' => 'Mock Taxi', 'is_active' => true]);
        $admin = $this->superAdmin();
        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'web']);
        $driver = User::factory()->create(['company_id' => $company->id]);
        $driver->assignRole('chauffeur');

        $this->actingAs($admin, 'web')
            ->withSession(['selected_tenant' => $company->id])
            ->post(route('admin.whatsapp-pickup-proposal-mock.seed'));

        $seeded = RideRequest::on('module_taxi')
            ->where('customer_note', 'like', '%'.WhatsAppPickupProposalMockService::NOTE_MARKER.'%')
            ->first();

        $driverRide = RideRequest::on('module_taxi')->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now('UTC')->subHour()->format('Y-m-d H:i:s'),
            'customer_name' => 'Chauffeur-app klant',
            'customer_phone' => '0622222222',
        ]);
        app(TaxiPickupProposalService::class)->proposeNewPickup(
            'module_taxi',
            $driver,
            (int) $driverRide->id,
            now()->addHours(3)->toIso8601String()
        );

        $this->actingAs($admin, 'web')
            ->postJson(route('admin.whatsapp-pickup-proposal-mock.destroy'), [
                'ride_ids' => [$seeded->id, $driverRide->id],
            ])
            ->assertOk()
            ->assertJsonPath('deleted', 1)
            ->assertJsonPath('hidden', 1);

        $this->assertNull(RideRequest::on('module_taxi')->find($seeded->id));
        $this->assertTrue(RideRequest::on('module_taxi')->whereKey($driverRide->id)->exists());

        $feed = $this->actingAs($admin, 'web')
            ->getJson(route('admin.whatsapp-pickup-proposal-mock.feed'))
            ->assertOk()
            ->json('rides');
        $ids = collect($feed)->pluck('id')->all();
        $this->assertNotContains($driverRide->id, $ids);

        app(TaxiPickupProposalService::class)->proposeNewPickup(
            'module_taxi',
            $driver,
            (int) $driverRide->id,
            now()->addHours(4)->toIso8601String()
        );

        $feedAfter = $this->actingAs($admin, 'web')
            ->getJson(route('admin.whatsapp-pickup-proposal-mock.feed'))
            ->json('rides');
        $this->assertContains($driverRide->id, collect($feedAfter)->pluck('id')->all());
    }

    public function test_inbound_mock_allowed_on_test_host_even_in_production_env(): void
    {
        $this->app['env'] = 'production';
        config([
            'app.env' => 'production',
            'app.url' => 'https://nexa-test.example',
            'whatsapp.inbound_mock_enabled' => null,
            'whatsapp.live_webhook_hosts' => ['nexasuite.nl', 'www.nexasuite.nl'],
        ]);

        $this->assertTrue(app(WhatsAppPickupProposalMockService::class)->inboundMockAllowed());

        config(['app.url' => 'https://nexasuite.nl']);
        $this->assertFalse(app(WhatsAppPickupProposalMockService::class)->inboundMockAllowed());
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        return $admin;
    }
}
