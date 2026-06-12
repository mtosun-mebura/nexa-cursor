<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\GeneralSetting;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAiChatMessageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
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

        $company = Company::query()->create([
            'name' => 'Tenant Taxi BV',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);
        $user->assignRole('company-admin');
        $user->givePermissionTo('rides.view');

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
}
