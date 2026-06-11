<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiCustomerLoginCodeService;
use App\Modules\NexaTaxi\Services\TaxiRideCustomerLinkService;
use App\Services\ModuleDatabaseService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaxiGuestLoginCodeRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'klant', 'guard_name' => 'web']);

        config(['database.connections.module_taxi' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        $this->mock(ModuleDatabaseService::class, function ($mock): void {
            $mock->shouldReceive('ensureModuleStorageReady')->with('taxi');
            $mock->shouldReceive('getModuleConnectionName')->with('taxi')->andReturn('module_taxi');
        });

        Schema::connection('module_taxi')->create('ride_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('customer_user_id')->nullable();
            $table->string('pickup_address');
            $table->string('dropoff_address');
            $table->dateTime('pickup_at');
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->timestamps();
        });
    }

    #[Test]
    public function guest_booking_email_can_request_login_code_and_get_account(): void
    {
        $company = Company::query()->create(['name' => 'Taxi BV', 'is_active' => true]);
        app()->instance('resolved_tenant_id', $company->id);

        RideRequest::on('module_taxi')->create([
            'company_id' => $company->id,
            'customer_user_id' => null,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Gast Klant',
            'customer_email' => 'gast@example.com',
        ]);

        $this->mock(TaxiCustomerLoginCodeService::class, function ($mock): void {
            $mock->shouldReceive('issueAndSend')
                ->once()
                ->andReturn(true);
        });

        $response = $this->post(route('login.code.request'), [
            'email' => 'gast@example.com',
            'intended' => '/mijn-taxi',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $user = User::query()->whereRaw('LOWER(email) = ?', ['gast@example.com'])->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->password_must_be_set);
        $ride = RideRequest::on('module_taxi')->where('customer_email', 'gast@example.com')->first();
        $this->assertSame((int) $user->id, (int) $ride->customer_user_id);
        $this->assertSame((int) $company->id, (int) $user->company_id);
    }

    #[Test]
    public function login_code_request_without_email_shows_dutch_validation_message(): void
    {
        $response = $this->from(route('login', ['code_login' => 1]))
            ->post(route('login.code.request'), [
                'email' => '',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'email' => 'Vul uw e-mailadres in om een nieuwe code aan te vragen.',
        ]);
    }

    #[Test]
    public function unknown_email_without_guest_booking_gets_generic_response(): void
    {
        $this->mock(TaxiCustomerLoginCodeService::class, function ($mock): void {
            $mock->shouldReceive('issueAndSend')->never();
        });

        $response = $this->post(route('login.code.request'), [
            'email' => 'onbekend@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('warning');
        $this->assertNull(User::query()->where('email', 'onbekend@example.com')->first());
    }
}
