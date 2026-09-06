<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyBillingProfile;
use App\Models\User;
use App\Services\NexaPricingService;
use App\Services\PlatformBilling\SaasTrialNoticeService;
use App\Services\PlatformBilling\TenantSubscriptionService;
use App\Services\UserRoleAssignmentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SaasTrialNoticeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        Carbon::setTestNow('2026-04-10 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function company_admin_sees_stop_trial_button_during_free_months(): void
    {
        [$user] = $this->trialCompanyAdmin();

        $this->actingAs($user)
            ->get(route('admin.subscriptions.show'))
            ->assertOk()
            ->assertSee('Proefperiode stoppen', false)
            ->assertSee('id="subscription-end-trial-modal"', false)
            ->assertSee('data-end-trial-open', false)
            ->assertSee('Direct stoppen kan alleen tijdens de', false)
            ->assertDontSee('Opzeggen per', false);
    }

    #[Test]
    public function notice_is_sent_once_when_trial_is_almost_over(): void
    {
        $this->trialCompanyAdmin();
        $profile = CompanyBillingProfile::query()->first();
        $this->assertNotNull($profile);

        $sent = app(SaasTrialNoticeService::class)->run(Carbon::parse('2026-04-10'));
        $this->assertSame(1, $sent);

        $messages = Mail::mailer('array')->getSymfonyTransport()->messages();
        $this->assertNotEmpty($messages);
        $html = (string) $messages->last()->getOriginalMessage()->getHtmlBody();
        $this->assertStringContainsString('aparte mail', $html);
        $this->assertStringContainsString('eerste betaling', $html);
        $this->assertStringContainsString('Proefperiode beëindigen', $html);
        $this->assertStringNotContainsString('#ea580c', $html);
        $this->assertStringNotContainsString('eerste incasso plaats', $html);
        $this->assertStringNotContainsString('Na die betaling wordt de automatische incasso', $html);
        $this->assertStringNotContainsString('dagen af (', $html);
        $this->assertStringNotContainsString('€', $html);
        $this->assertStringNotContainsString('incl. btw', $html);
        $this->assertStringNotContainsString('FIRST_AMOUNT', $html);

        $profile->refresh();
        $this->assertNotNull($profile->trial_notice_sent_at);
        $this->assertSame(0, app(SaasTrialNoticeService::class)->run(Carbon::parse('2026-04-10')));
    }

    #[Test]
    public function old_announcement_template_is_rewritten_without_amounts(): void
    {
        \App\Models\EmailTemplate::query()->create([
            'type' => \App\Services\SaasTrialEndingEmailTemplateService::TYPE,
            'company_id' => null,
            'name' => 'oud',
            'subject' => 'oud',
            'html_content' => '<p>Op {{ START_DATE }} gaat het abonnement in. Je ontvangt dan een aparte e-mail ({{ FIRST_AMOUNT }} incl. btw, voor {{ COVERAGE_LABEL }}).</p>',
            'text_content' => 'oud',
            'is_active' => true,
            'recipient_type' => 'email',
        ]);

        $html = (string) app(\App\Services\SaasTrialEndingEmailTemplateService::class)->ensureExists()->html_content;
        $this->assertStringNotContainsString('FIRST_AMOUNT', $html);
        $this->assertStringNotContainsString('COVERAGE_LABEL', $html);
        $this->assertStringContainsString('aparte mail worden verstuurd', $html);
        $this->assertStringNotContainsString('({{ TRIAL_ENDS_AT }})', $html);
        $this->assertStringNotContainsString('Na die betaling wordt de automatische incasso', $html);
    }

    #[Test]
    public function signed_stop_link_keeps_access_until_trial_end(): void
    {
        [, $company] = $this->trialCompanyAdmin();
        $url = URL::temporarySignedRoute(
            'saas.trial.stop.show',
            now()->addDays(7),
            ['company' => $company->id]
        );

        $this->get($url)
            ->assertOk()
            ->assertSee('Proefperiode stoppen', false);

        $this->post(route('saas.trial.stop'))
            ->assertRedirect(route('saas.trial.stopped'));

        $company->refresh();
        $this->assertTrue((bool) $company->is_active);
        $this->assertSame('trial_end', $company->billingProfile?->pending_change_type);
        $this->assertNull($company->billingProfile?->subscription_end_date);
    }

    #[Test]
    public function company_admin_stays_in_admin_after_stopping_the_trial(): void
    {
        [$user, $company] = $this->trialCompanyAdmin();

        $this->actingAs($user)
            ->followingRedirects()
            ->post(route('admin.subscriptions.end-trial'))
            ->assertOk()
            ->assertSee('id="admin-trial-declined-modal"', false)
            ->assertSee('Proefperiode beëindigd', false)
            ->assertSee('Abonnement activeren', false)
            ->assertDontSee('Je tenant wordt inactief gezet en er volgt geen incasso.', false);

        $company->refresh();
        $this->assertTrue((bool) $company->is_active);
        $this->assertSame('trial_end', $company->billingProfile?->pending_change_type);

        $this->actingAs($user)
            ->get(route('admin.subscriptions.show'))
            ->assertOk()
            ->assertDontSee('id="admin-trial-declined-modal"', false);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('id="admin-trial-declined-modal"', false)
            ->assertSee('Stopt per', false)
            ->assertSee('15 april 2026', false);
    }

    /**
     * @return array{0: User, 1: Company}
     */
    private function trialCompanyAdmin(): array
    {
        $pricing = app(NexaPricingService::class)->get();
        $pricing['packages'][0]['free_months'] = 1;
        $pricing['trial_notice_days'] = 5;
        app(NexaPricingService::class)->save($pricing);

        $company = Company::query()->create([
            'name' => 'Trial Mail '.uniqid(),
            'is_active' => true,
            'package_key' => 'start',
            'email' => 'klant@example.com',
        ]);
        $company->forceFill([
            'created_at' => '2026-03-15 09:00:00',
            'updated_at' => '2026-03-15 09:00:00',
        ])->save();
        $company->refresh();

        app(TenantSubscriptionService::class)->ensureProfile($company);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'admin-'.uniqid().'@example.com',
        ]);
        app(UserRoleAssignmentService::class)->syncWebRoles($user, ['company-admin']);

        return [$user, $company];
    }
}
