<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\NexaTaxi\Controllers\Api\ContractPortalAuthController;
use App\Modules\NexaTaxi\Controllers\Api\DriverAuthController;
use App\Modules\NexaTaxi\Services\TaxiContractPortalAccessService;
use App\Modules\NexaTaxi\Services\TaxiDriverEarningsAccessService;
use App\Modules\NexaTaxi\Services\TaxiDriverEligibilityService;
use App\Services\ModuleDatabaseService;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiAppLoginTest extends TestCase
{
    #[Test]
    public function driver_login_returns_dutch_message_for_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'chauffeur@example.com',
            'password' => 'Geheim123!',
            'email_verified_at' => now(),
        ]);

        $request = Request::create('/api/taxi/v1/driver/login', 'POST', [
            'email' => 'chauffeur@example.com',
            'password' => 'fout',
        ]);
        $request->headers->set('Accept', 'application/json');

        $response = app(DriverAuthController::class)->login(
            $request,
            app(TaxiDriverEligibilityService::class),
            app(ModuleDatabaseService::class),
            app(TaxiDriverEarningsAccessService::class)
        );

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Onjuiste e-mail of wachtwoord.', $response->getData(true)['message'] ?? null);
    }

    #[Test]
    public function contract_login_returns_dutch_message_for_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'ouder@example.com',
            'password' => 'Geheim123!',
            'email_verified_at' => now(),
        ]);

        $request = Request::create('/api/taxi/v1/contract/login', 'POST', [
            'email' => 'ouder@example.com',
            'password' => 'fout',
        ]);
        $request->headers->set('Accept', 'application/json');

        $response = app(ContractPortalAuthController::class)->login(
            $request,
            app(ModuleDatabaseService::class),
            app(TaxiContractPortalAccessService::class)
        );

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Onjuiste e-mail of wachtwoord.', $response->getData(true)['message'] ?? null);
    }
}
