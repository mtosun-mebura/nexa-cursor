<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\GeneralSetting;
use App\Models\Module;
use App\Models\User;
use App\Services\NexaDemoAccountService;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminAiChatMessageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'demo', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'rides.view', 'guard_name' => 'web']);

        Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-car',
            'installed' => true,
            'active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        GeneralSetting::clearRequestCache();
        parent::tearDown();
    }

    public function test_admin_live_question_uses_admin_channel_and_tenant_scope(): void
    {
        Http::fake([
            'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant' => Http::response([
                'answer' => [],
                'count' => 0,
            ], 200),
        ]);

        config()->set('services.ai_chat.module_defaults.taxi', 'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant');

        [$company, $user] = $this->companyAdminWithRidesView();

        $response = $this->actingAs($user)->postJson(route('admin.ai-chat.message'), [
            'message' => 'Welke ritten staan morgen gepland?',
            'module' => 'taxi',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        Http::assertSent(function ($request) use ($company, $user) {
            return $request->url() === 'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant'
                && $request['company_id'] === $company->id
                && $request['channel'] === 'admin'
                && $request['isAdmin'] === true
                && $request['allowLiveData'] === true
                && $request['user_id'] === $user->id;
        });
    }

    public function test_super_admin_without_selected_tenant_gets_validation_error(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)->postJson(route('admin.ai-chat.message'), [
            'message' => 'Welke ritten staan morgen gepland?',
            'module' => 'taxi',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $this->assertStringContainsString('tenant', mb_strtolower((string) $response->json('error')));
    }

    public function test_admin_travel_intent_starts_local_quote_flow_without_n8n(): void
    {
        Http::fake();

        config()->set('services.ai_chat.module_defaults.taxi', 'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant');

        [, $user] = $this->companyAdminWithRidesView();

        $response = $this->actingAs($user)->postJson(route('admin.ai-chat.message'), [
            'message' => 'ik wil naar schiphol',
            'module' => 'taxi',
            'sessionId' => 'admin-travel-intent-test',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('input.type', 'address')
            ->assertJsonPath('input.step', 'pickup');

        $reply = mb_strtolower((string) $response->json('reply'));
        $this->assertStringContainsString('schiphol', $reply);
        $this->assertStringContainsString('vanaf welk adres', $reply);

        Http::assertNothingSent();
    }

    public function test_super_admin_uses_selected_tenant_for_admin_chat(): void
    {
        Http::fake([
            'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant' => Http::response([
                'answer' => 'Geen ritten gevonden.',
            ], 200),
        ]);

        config()->set('services.ai_chat.module_defaults.taxi', 'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant');

        $company = Company::query()->create([
            'name' => 'Gekozen Tenant',
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)
            ->withSession(['selected_tenant' => $company->id])
            ->postJson(route('admin.ai-chat.message'), [
                'message' => 'Welke ritten staan morgen gepland?',
                'module' => 'taxi',
            ]);

        $response->assertOk();

        Http::assertSent(function ($request) use ($company) {
            return $request['company_id'] === $company->id
                && $request['channel'] === 'admin';
        });
    }

    public function test_demo_user_cannot_query_admin_ai_chat(): void
    {
        Http::fake();

        $result = app(NexaDemoAccountService::class)->ensure();
        $this->assertNotNull($result['user']);

        $response = $this->actingAs($result['user'])->postJson(route('admin.ai-chat.message'), [
            'message' => 'Welke ritten staan morgen gepland?',
            'module' => 'taxi',
        ]);

        $response->assertForbidden();
        Http::assertNothingSent();
    }

    /**
     * @return array{0: Company, 1: User}
     */
    private function companyAdminWithRidesView(): array
    {
        $company = Company::query()->create([
            'name' => 'Tenant Taxi BV',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId((int) $company->id);
        try {
            $user->assignRole('company-admin');
            $user->givePermissionTo('rides.view');
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
        } finally {
            $registrar->setPermissionsTeamId($previousTeamId);
        }

        return [$company, $user];
    }
}
