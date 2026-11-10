<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\PayoutIdentity;
use App\Models\PayoutIdentityAuditLog;
use App\Models\User;
use App\Services\Payout\PayoutIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PayoutIdentityPhase2Test extends TestCase
{
    use RefreshDatabase;

    private PayoutIdentityService $payouts;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super-admin', 'company-admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->payouts = app(PayoutIdentityService::class);
    }

    #[Test]
    public function company_payout_identity_stores_metadata_only_and_rejects_card_fields(): void
    {
        $company = Company::query()->create(['name' => 'Taxi Payout BV', 'is_active' => true]);
        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('company-admin');

        try {
            $this->payouts->ensureCompanyIdentity($company, $admin, [
                'provider_account_id' => 'org_abc',
                'card_number' => '4111111111111111',
                'cvc' => '123',
            ]);
            $this->fail('Expected ValidationException for card_number');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('card_number', $e->errors());
        }

        $identity = $this->payouts->ensureCompanyIdentity($company, $admin, [
            'provider_organization_id' => 'org_123',
            'provider_account_id' => 'acc_456',
            'masked_destination' => 'NL** **** 4242',
        ]);

        $this->assertSame(PayoutIdentity::PARTY_COMPANY, $identity->settlement_party);
        $this->assertSame('acc_456', $identity->provider_account_id);
        $this->assertSame('NL** **** 4242', $identity->masked_destination);
        $this->assertNull($identity->user_id);
        $this->assertFalse($identity->isEnabled()); // pending until enabled status

        $this->assertTrue(
            PayoutIdentityAuditLog::query()->where('action', 'company_identity_ensured')->exists()
        );
    }

    #[Test]
    public function independent_driver_payout_is_blocked_by_default(): void
    {
        config(['nexa_payout.allow_independent_driver_payouts' => false]);

        $company = Company::query()->create(['name' => 'Taxi Company', 'is_active' => true]);
        $admin = User::factory()->create(['company_id' => $company->id]);
        $driver = User::factory()->create(['company_id' => $company->id]);

        try {
            $this->payouts->ensureIndependentDriverIdentity($driver, $company, $admin, [
                'iban' => 'NL91ABNA0417164300',
            ]);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('settlement_party', $e->errors());
        }

        $this->assertSame(0, PayoutIdentity::query()->where('settlement_party', PayoutIdentity::PARTY_INDEPENDENT_DRIVER)->count());
        $this->assertTrue(
            PayoutIdentityAuditLog::query()->where('action', 'independent_driver_payout_blocked')->exists()
        );
    }

    #[Test]
    public function destination_change_requires_password_and_cooling_off(): void
    {
        config(['nexa_payout.destination_change_cooling_off_hours' => 48]);

        $company = Company::query()->create(['name' => 'Cooling Taxi', 'is_active' => true]);
        $admin = User::factory()->create([
            'company_id' => $company->id,
            'password' => bcrypt('SecurePass1'),
        ]);

        $identity = $this->payouts->ensureCompanyIdentity($company, $admin, [
            'provider_account_id' => 'acc_old',
            'masked_destination' => 'NL** **** 0001',
        ]);
        $identity->forceFill([
            'capability_status' => PayoutIdentity::STATUS_ENABLED,
            'verified_at' => now(),
        ])->save();

        try {
            $this->payouts->requestDestinationChange($identity, $admin, [
                'provider_account_id' => 'acc_new',
                'password' => 'wrong',
            ]);
            $this->fail('Expected password failure');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('password', $e->errors());
        }

        $pending = $this->payouts->requestDestinationChange($identity->fresh(), $admin, [
            'provider_account_id' => 'acc_new',
            'masked_destination' => 'NL** **** 9999',
            'password' => 'SecurePass1',
        ]);

        $this->assertTrue($pending->destinationChangeIsCooling());
        $this->assertSame('acc_old', $pending->provider_account_id);
        $this->assertSame('acc_new', $pending->pending_provider_account_id);

        try {
            $this->payouts->applyPendingDestinationChange($pending, $admin);
            $this->fail('Expected cooling-off block');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('destination', $e->errors());
        }

        $pending->forceFill([
            'destination_change_eligible_at' => now()->subMinute(),
        ])->save();

        $applied = $this->payouts->applyPendingDestinationChange($pending->fresh(), $admin);
        $this->assertSame('acc_new', $applied->provider_account_id);
        $this->assertNull($applied->pending_provider_account_id);
        $this->assertFalse($applied->isEnabled()); // re-verify required after change
    }

    #[Test]
    public function full_iban_like_masked_destination_is_rejected(): void
    {
        $company = Company::query()->create(['name' => 'Mask Taxi', 'is_active' => true]);
        $admin = User::factory()->create(['company_id' => $company->id]);

        $this->expectException(ValidationException::class);

        $this->payouts->ensureCompanyIdentity($company, $admin, [
            'provider_account_id' => 'acc_1',
            'masked_destination' => 'NL91ABNA0417164300',
        ]);
    }

    #[Test]
    public function admin_page_requires_company_admin_or_super_admin(): void
    {
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        $company = Company::query()->create(['name' => 'UI Taxi', 'is_active' => true]);
        $staff = User::factory()->create(['company_id' => $company->id]);
        $staff->assignRole('staff');

        $this->actingAs($staff)
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.payout-identities.index'))
            ->assertForbidden();

        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('company-admin');

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.payout-identities.index'))
            ->assertOk()
            ->assertSee('Payout onboarding', false);
    }

    #[Test]
    public function company_create_bootstraps_payout_identity_automatically(): void
    {
        $company = Company::query()->create(['name' => 'Auto Payout Taxi', 'is_active' => true]);

        $identity = PayoutIdentity::query()->where('company_id', $company->id)->first();
        $this->assertNotNull($identity);
        $this->assertSame(PayoutIdentity::PARTY_COMPANY, $identity->settlement_party);
        $this->assertSame(PayoutIdentity::STATUS_NOT_STARTED, $identity->capability_status);
        $this->assertNull($identity->masked_destination);
    }

    #[Test]
    public function app_sets_bank_account_and_stores_only_masked_last_four(): void
    {
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'api']);

        $company = Company::query()->create(['name' => 'App Bank Taxi', 'is_active' => true]);
        $admin = User::factory()->create([
            'company_id' => $company->id,
            'password' => bcrypt('SecurePass1'),
        ]);

        $registrar = app(\Spatie\Permission\PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($company->id);
        $admin->assignRole('company-admin');

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/tenant/payout-identity/bank-account', [
                'iban' => 'NL91 ABNA 0417 1643 00',
            ])
            ->assertOk()
            ->assertJsonPath('data.masked_bank_account', '***4300')
            ->assertJsonMissing(['iban' => 'NL91ABNA0417164300']);

        $identity = PayoutIdentity::query()->where('company_id', $company->id)->first();
        $this->assertSame('***4300', $identity->masked_destination);
        $this->assertStringNotContainsString('NL91', (string) $identity->masked_destination);
        $this->assertNull(data_get($identity->provider_meta, 'iban'));

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/tenant/payout-identity/bank-account', [
                'iban' => 'NL20INGB0001234567',
                'password' => 'SecurePass1',
            ])
            ->assertOk()
            ->assertJsonPath('data.masked_bank_account', '***4300')
            ->assertJsonPath('data.pending_masked_bank_account', '***4567');

        $identity->refresh();
        $this->assertSame('***4300', $identity->masked_destination);
        $this->assertSame('***4567', $identity->pending_masked_destination);
    }

    #[Test]
    public function bank_account_change_accepts_email_confirmation_code_instead_of_password(): void
    {
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'api']);

        $company = Company::query()->create(['name' => 'Code Bank Taxi', 'is_active' => true]);
        $admin = User::factory()->create([
            'company_id' => $company->id,
            'password' => bcrypt('UnknownToUser1'),
        ]);

        $registrar = app(\Spatie\Permission\PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($company->id);
        $admin->assignRole('company-admin');

        $this->payouts->setCompanyBankAccount($company, $admin, 'NL91ABNA0417164300', null, null);

        $plainCode = '654321';
        \App\Models\CustomerLoginCode::query()->create([
            'user_id' => $admin->id,
            'purpose' => \App\Models\CustomerLoginCode::PURPOSE_ADMIN_STEP_UP,
            'code_hash' => bcrypt($plainCode),
            'expires_at' => now()->addMinutes(15),
        ]);

        $request = \Illuminate\Http\Request::create('/api/tenant/payout-identity/bank-account', 'PUT', [
            'iban' => 'NL20INGB0001234567',
            'confirmation_code' => $plainCode,
        ]);
        $request->setUserResolver(static fn () => $admin);

        $identity = $this->payouts->setCompanyBankAccount(
            $company,
            $admin,
            'NL20INGB0001234567',
            null,
            $request
        );

        $this->assertSame('***4300', $identity->masked_destination);
        $this->assertSame('***4567', $identity->pending_masked_destination);
        $this->assertTrue($identity->hasPendingDestinationChange());
    }
}
