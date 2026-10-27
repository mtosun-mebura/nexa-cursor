<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FrontendTheme;
use App\Models\TenantCustomerEmail;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminLocaleEnBPackagesEmailTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();


        config([
            'app.url' => 'http://localhost:8085',
            'tenancy.central_domains' => ['localhost'],
        ]);

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create(['company_id' => null]);
        $admin->assignRole('super-admin');

        return $admin;
    }

    private function visibleHtml(string $html): string
    {
        return (string) preg_replace('/<!--.*?-->/s', '', $html);
    }

    private function mail(Company $company): TenantCustomerEmail
    {
        return TenantCustomerEmail::query()->create([
            'company_id' => $company->id,
            'type' => TenantCustomerEmail::TYPE_BOOKING,
            'recipient_email' => 'klant@example.test',
            'recipient_name' => 'Test Klant',
            'subject' => 'Ritbevestiging',
            'body_html' => '<p>Ritbevestiging</p>',
            'body_text' => 'Ritbevestiging',
            'status' => TenantCustomerEmail::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    #[Test]
    public function packages_edit_is_english_in_en_locale(): void
    {
        $html = $this->actingAs($this->superAdmin())
            ->withSession(['locale' => 'en'])
            ->get(route('admin.nexa-pricing.edit'))
            ->assertOk()
            ->getContent();

        foreach (['Price overview', 'Monthly price', 'Offer price', 'Free months', 'Contract &amp; trial period', 'Cancellable monthly', 'Website (one-time)', 'Features (for the software)', 'Maximum drivers', 'Maximum contract customers', 'Additional modules', '+ Add package', '+ Add feature', 'Delete package', 'Texts above the packages'] as $english) {
            $this->assertStringContainsString($english, $html, $english);
        }
        foreach (['Overzicht prijzen', 'Maandprijs', 'Aanbiedingsprijs', 'Gratis maanden', 'Maandelijks opzegbaar', 'Website (eenmalig)', 'Functies (voor de software)', 'Maximum chauffeurs', 'Maximum contractklanten', '+ Pakket toevoegen', '+ Kenmerk toevoegen', 'Teksten boven de pakketten'] as $dutch) {
            $this->assertStringNotContainsString($dutch, $this->visibleHtml($html), $dutch);
        }
    }

    #[Test]
    public function packages_edit_stays_dutch_in_nl_locale(): void
    {
        $html = $this->actingAs($this->superAdmin())
            ->withSession(['locale' => 'nl'])
            ->get(route('admin.nexa-pricing.edit'))
            ->assertOk()
            ->getContent();

        foreach (['Overzicht prijzen', 'Maandprijs', 'Aanbiedingsprijs', 'Gratis maanden', 'Contract &amp; proeftijd', 'Maandelijks opzegbaar', 'Website (eenmalig)', 'Functies (voor de software)', 'Maximum chauffeurs', 'Maximum contractklanten', '+ Pakket toevoegen', '+ Kenmerk toevoegen'] as $dutch) {
            $this->assertStringContainsString($dutch, $html, $dutch);
        }
    }

    #[Test]
    public function packages_update_validation_and_access_messages_are_english_in_en_locale(): void
    {
        $this->actingAs($this->superAdmin())
            ->withSession(['locale' => 'en'])
            ->from(route('admin.nexa-pricing.edit'))
            ->put(route('admin.nexa-pricing.update'), [
                'commitment_months' => 0,
                'trial_notice_days' => 5,
            ])
            ->assertRedirect(route('admin.nexa-pricing.edit'))
            ->assertSessionHasErrors(['packages' => 'Add at least one monthly package.']);
    }

    #[Test]
    public function customer_emails_index_and_show_are_english_in_en_locale(): void
    {
        $admin = $this->superAdmin();
        $company = Company::query()->create(['name' => 'Locale Mail Taxi', 'is_active' => true]);
        $email = $this->mail($company);
        $session = ['locale' => 'en', 'selected_tenant' => $company->id];

        $html = $this->actingAs($admin)
            ->withSession($session)
            ->get(route('admin.customer-emails.index'))
            ->assertOk()
            ->getContent();

        foreach (['Showing 1 to 1 of 1 emails', 'Search by email, name, subject…', 'All types', 'All statuses', 'Booking confirmation', 'Sent', 'Resend', 'Subject', 'Actions'] as $english) {
            $this->assertStringContainsString($english, $html, $english);
        }
        foreach (['Toon 1 tot', 'Zoek op e-mail', 'Alle types', 'Alle statussen', 'Boekingsbevestiging', 'Verzonden', 'Opnieuw versturen', 'Onderwerp', 'Acties'] as $dutch) {
            $this->assertStringNotContainsString($dutch, $this->visibleHtml($html), $dutch);
        }

        $show = $this->actingAs($admin)
            ->withSession($session)
            ->get(route('admin.customer-emails.show', $email))
            ->assertOk()
            ->getContent();

        foreach (['Resend', 'Booking confirmation', 'Shown as the recipient saw it in their mailbox.', 'Content', 'To:', 'Resend email?', 'Resend email to klant@example.test?'] as $english) {
            $this->assertStringContainsString($english, $show, $english);
        }
        foreach (['Opnieuw versturen', 'Boekingsbevestiging', 'Weergave zoals de ontvanger', 'Inhoud', 'Aan:', 'E-mail opnieuw versturen'] as $dutch) {
            $this->assertStringNotContainsString($dutch, $this->visibleHtml($show), $dutch);
        }
    }

    #[Test]
    public function customer_emails_stay_dutch_in_nl_locale(): void
    {
        $admin = $this->superAdmin();
        $company = Company::query()->create(['name' => 'Locale Mail Taxi NL', 'is_active' => true]);
        $email = $this->mail($company);
        $session = ['locale' => 'nl', 'selected_tenant' => $company->id];

        $html = $this->actingAs($admin)
            ->withSession($session)
            ->get(route('admin.customer-emails.index'))
            ->assertOk()
            ->getContent();

        foreach (['Toon 1 tot 1 van 1 e-mails', 'Zoek op e-mail, naam, onderwerp…', 'Alle types', 'Boekingsbevestiging', 'Verzonden', 'Opnieuw versturen', "confirm('E-mail opnieuw versturen naar klant@example.test?')"] as $dutch) {
            $this->assertStringContainsString($dutch, $html, $dutch);
        }

        $show = $this->actingAs($admin)
            ->withSession($session)
            ->get(route('admin.customer-emails.show', $email))
            ->assertOk()
            ->getContent();

        foreach (['Weergave zoals de ontvanger dit in de mailbox zag.', '<strong>Aan:</strong>', '>Aan</span>', 'E-mail opnieuw versturen naar klant@example.test?'] as $dutch) {
            $this->assertStringContainsString($dutch, $show, $dutch);
        }
    }

    #[Test]
    public function customer_email_resend_message_is_english_in_en_locale(): void
    {
        $admin = $this->superAdmin();
        $company = Company::query()->create(['name' => 'Locale Resend Taxi', 'is_active' => true]);
        $email = $this->mail($company);

        \Illuminate\Support\Facades\Mail::fake();

        $this->actingAs($admin)
            ->withSession(['locale' => 'en', 'selected_tenant' => $company->id])
            ->postJson(route('admin.customer-emails.resend', $email))
            ->assertOk()
            ->assertJsonPath('message', 'Email resent to klant@example.test.');
    }
}
