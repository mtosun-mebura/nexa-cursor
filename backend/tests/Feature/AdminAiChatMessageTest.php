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
        Http::fake();

        config()->set('services.ai_chat.module_defaults.taxi', 'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant');

        [, $user] = $this->companyAdminWithRidesView();

        $response = $this->actingAs($user)->postJson(route('admin.ai-chat.message'), [
            'message' => 'Welke ritten staan morgen gepland?',
            'module' => 'taxi',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertNotEmpty($response->json('reply'));
        Http::assertNothingSent();
    }

    public function test_super_admin_without_selected_tenant_can_ask_platform_questions(): void
    {
        Http::fake();

        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)->postJson(route('admin.ai-chat.message'), [
            'message' => 'Welke tenants hebben deze maand niet betaald?',
            'module' => 'taxi',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $reply = mb_strtolower((string) $response->json('reply'));
        $this->assertTrue(
            str_contains($reply, 'tenant') || str_contains($reply, 'nexa-factuur') || str_contains($reply, 'betaald'),
            $reply
        );
        Http::assertNothingSent();
    }

    public function test_super_admin_without_tenant_gets_hint_for_operational_questions(): void
    {
        Http::fake();

        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)->postJson(route('admin.ai-chat.message'), [
            'message' => 'Welke ritten staan morgen gepland?',
            'module' => 'taxi',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertStringContainsString('tenant', mb_strtolower((string) $response->json('reply')));
        Http::assertNothingSent();
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
        Http::fake();

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
        $response->assertJsonPath('success', true);
        $this->assertNotEmpty($response->json('reply'));
        Http::assertNothingSent();
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
