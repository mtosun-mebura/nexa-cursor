<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\TenantCustomerEmail;
use App\Models\User;
use App\Services\UserRoleAssignmentService;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminTenantCustomerEmailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    #[Test]
    public function company_admin_sees_email_communicatie_and_own_tenant_mails(): void
    {
        [$user, $company] = $this->companyAdmin();
        $other = Company::query()->create(['name' => 'Andere Tenant', 'is_active' => true]);

        $own = $this->email($company, 'eigen@example.test', 'Welkom bij ons');
        $this->email($other, 'ander@example.test', 'Niet zichtbaar');

        $this->actingAs($user)
            ->get(route('admin.customer-emails.index'))
            ->assertOk()
            ->assertSee('Email communicatie', false)
            ->assertSee('Welkom bij ons', false)
            ->assertSee('eigen@example.test', false)
            ->assertDontSee('Niet zichtbaar', false)
            ->assertDontSee('ander@example.test', false);

        $this->actingAs($user)
            ->get(route('admin.customer-emails.show', $own))
            ->assertOk()
            ->assertSee('Welkom bij ons', false)
            ->assertSee('Opnieuw versturen', false)
            ->assertSee(route('admin.customer-emails.preview', $own), false)
            ->assertDontSee('admin-email-preview-dark', false);
    }

    #[Test]
    public function super_admin_sees_sent_html_exactly_in_mailbox_preview(): void
    {
        $admin = User::factory()->create(['company_id' => null]);
        $admin->assignRole('super-admin');
        $company = Company::query()->create(['name' => 'SA Tenant', 'is_active' => true]);
        $email = TenantCustomerEmail::query()->create([
            'company_id' => $company->id,
            'type' => TenantCustomerEmail::TYPE_TENANT_WELCOME,
            'recipient_email' => 'klant@example.test',
            'recipient_name' => 'Test Klant',
            'subject' => 'Welkom bij NEXA',
            'body_html' => '<!DOCTYPE html><html><head></head><body><p class="welcome-copy">Welkom in uw mailbox</p><!--NEXA_COMPANY_LOGO--></body></html>',
            'body_text' => 'Welkom in uw mailbox',
            'status' => TenantCustomerEmail::STATUS_SENT,
            'sent_at' => now(),
        ]);

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => null])
            ->get(route('admin.customer-emails.show', $email))
            ->assertOk()
            ->assertSee('zoals de ontvanger dit in de mailbox zag', false)
            ->assertSee(route('admin.customer-emails.preview', $email), false);

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => null])
            ->get(route('admin.customer-emails.preview', $email))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertSee('Welkom in uw mailbox', false)
            ->assertSee('color-scheme', false)
            ->assertDontSee('NEXA_COMPANY_LOGO', false);
    }

    #[Test]
    public function super_admin_can_open_email_communicatie(): void
    {
        $admin = User::factory()->create(['company_id' => null]);
        $admin->assignRole('super-admin');
        $company = Company::query()->create(['name' => 'SA Tenant', 'is_active' => true]);
        $this->email($company, 'klant@example.test', 'Ritbevestiging');

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => null])
            ->get(route('admin.customer-emails.index'))
            ->assertOk()
            ->assertSee('Email communicatie', false)
            ->assertSee('Ritbevestiging', false)
            ->assertSee('klant@example.test', false);
    }

    #[Test]
    public function staff_cannot_open_email_communicatie(): void
    {
        $company = Company::query()->create(['name' => 'Staff Tenant', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('staff');

        $this->actingAs($user)
            ->get(route('admin.customer-emails.index'))
            ->assertForbidden();
    }

    #[Test]
    public function company_admin_can_resend_a_customer_email(): void
    {
        Mail::fake();
        [$user, $company] = $this->companyAdmin();
        $original = $this->email($company, 'klant@example.test', 'Boeking bevestigd');

        $this->actingAs($user)
            ->from(route('admin.customer-emails.show', $original))
            ->post(route('admin.customer-emails.resend', $original))
            ->assertRedirect();

        $this->assertSame(1, (int) $original->fresh()->resent_count);
        $this->assertSame(2, TenantCustomerEmail::query()->where('company_id', $company->id)->count());
        $this->assertDatabaseHas('tenant_customer_emails', [
            'company_id' => $company->id,
            'resent_from_id' => $original->id,
            'recipient_email' => 'klant@example.test',
        ]);
    }

    #[Test]
    public function company_admin_can_resend_a_customer_email_via_json(): void
    {
        Mail::fake();
        [$user, $company] = $this->companyAdmin();
        $original = $this->email($company, 'klant@example.test', 'Boeking bevestigd');

        $this->actingAs($user)
            ->postJson(route('admin.customer-emails.resend', $original))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'E-mail opnieuw verstuurd naar klant@example.test.');

        $copy = TenantCustomerEmail::query()
            ->where('company_id', $company->id)
            ->where('resent_from_id', $original->id)
            ->first();
        $this->assertNotNull($copy);
    }

    #[Test]
    public function company_admin_cannot_view_another_tenant_email(): void
    {
        [$user] = $this->companyAdmin();
        $other = Company::query()->create(['name' => 'Andere', 'is_active' => true]);
        $email = $this->email($other, 'geheim@example.test', 'Geheim');

        $this->actingAs($user)
            ->get(route('admin.customer-emails.show', $email))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.customer-emails.preview', $email))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Company}
     */
    private function companyAdmin(): array
    {
        $company = Company::query()->create([
            'name' => 'Email Admin '.uniqid(),
            'is_active' => true,
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);
        app(UserRoleAssignmentService::class)->syncWebRoles($user, ['company-admin']);

        return [$user, $company];
    }

    private function email(Company $company, string $to, string $subject): TenantCustomerEmail
    {
        return TenantCustomerEmail::query()->create([
            'company_id' => $company->id,
            'type' => TenantCustomerEmail::TYPE_BOOKING,
            'recipient_email' => $to,
            'recipient_name' => 'Test Klant',
            'subject' => $subject,
            'body_html' => '<p>'.$subject.'</p>',
            'body_text' => $subject,
            'status' => TenantCustomerEmail::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }
}
