<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\NexaSuiteBookingInvoice;
use App\Models\PaymentProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminLocaleEnBPaymentsBTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole('super-admin');

        return $user;
    }

    private function visibleHtml(string $html): string
    {
        return (string) preg_replace('/<!--.*?-->/s', '', $html);
    }

    /** @return array{0: Company, 1: array<string, mixed>} */
    private function tenant(string $locale): array
    {
        $tenant = Company::query()->create(['name' => 'Locale Pay Taxi', 'is_active' => true]);

        return [$tenant, ['selected_tenant' => $tenant->id, 'locale' => $locale]];
    }

    private function provider(Company $tenant): PaymentProvider
    {
        return PaymentProvider::query()->create([
            'company_id' => $tenant->id,
            'name' => 'Mollie Locale',
            'provider_type' => 'mollie',
            'is_active' => true,
            'config' => ['test_mode' => true, 'description' => 'Locale test', 'webhook_url' => 'https://example.com/hook'],
        ]);
    }

    private function invoice(Company $tenant): NexaSuiteBookingInvoice
    {
        return NexaSuiteBookingInvoice::query()->create([
            'company_id' => $tenant->id,
            'invoice_number' => 'NSB-2026-0001',
            'billing_period' => '2026-09',
            'ride_count' => 3,
            'rides_subtotal' => 100,
            'fee_percent' => 10,
            'amount' => 10,
            'tax_amount' => 2.1,
            'total_amount' => 12.1,
            'currency' => 'EUR',
            'status' => 'sent',
            'invoice_date' => '2026-10-01',
            'due_date' => '2026-10-15',
            'sent_at' => now(),
        ]);
    }

    #[Test]
    public function payment_providers_index_is_english_in_en_locale(): void
    {
        [$tenant, $session] = $this->tenant('en');
        $this->provider($tenant);

        $html = $this->actingAs($this->superAdmin())->withSession($session)
            ->get(route('admin.payment-providers.index'))
            ->assertOk()->getContent();

        foreach (['Payment providers', 'New payment provider', 'Test mode', 'Search payment providers...', 'All modes', 'Name &amp; description', 'Provider type', 'Test connection', 'Showing 1 to 1 of 1 payment providers', 'Active'] as $english) {
            $this->assertStringContainsString($english, $html, $english);
        }
        foreach (['Betalingsproviders', 'Nieuwe Betalingsprovider', 'Test Modus', 'Zoek betalingsproviders', 'Alle modi', 'Naam &amp; Beschrijving', 'Test Verbinding', 'Toon 1 tot', 'Actief', 'Acties'] as $dutch) {
            $this->assertStringNotContainsString($dutch, $this->visibleHtml($html), $dutch);
        }
    }

    #[Test]
    public function payment_providers_create_edit_and_show_are_english_in_en_locale(): void
    {
        [$tenant, $session] = $this->tenant('en');
        $provider = $this->provider($tenant);
        $admin = $this->superAdmin();

        $create = $this->actingAs($admin)->withSession($session)->get(route('admin.payment-providers.create'))->assertOk()->getContent();
        foreach (['New payment provider', 'Basics &amp; API', 'Name *', 'API key *', 'Stored securely encrypted.', 'Provider-specific information', 'Cancel', 'Create'] as $english) {
            $this->assertStringContainsString($english, $create, $english);
        }
        foreach (['Basis &amp; API', 'Naam *', 'Wordt veilig versleuteld', 'Provider-specifieke Informatie', 'Annuleren', 'Aanmaken'] as $dutch) {
            $this->assertStringNotContainsString($dutch, $this->visibleHtml($create), $dutch);
        }

        $edit = $this->actingAs($admin)->withSession($session)->get(route('admin.payment-providers.edit', $provider))->assertOk()->getContent();
        foreach (['Edit payment provider', 'Company (tenant)', 'Use test API keys instead of live keys.', 'Update'] as $english) {
            $this->assertStringContainsString($english, $edit, $english);
        }
        foreach (['Betalingsprovider Bewerken', 'Gebruik test API keys in plaats van', 'Bijwerken'] as $dutch) {
            $this->assertStringNotContainsString($dutch, $this->visibleHtml($edit), $dutch);
        }

        $show = $this->actingAs($admin)->withSession($session)->get(route('admin.payment-providers.show', $provider))->assertOk()->getContent();
        foreach (['Payment provider details - Mollie Locale', 'Test mode', 'Configuration', 'Basic information', 'System information', 'Connection test result', 'Stored securely encrypted. Change via Edit.'] as $english) {
            $this->assertStringContainsString($english, $show, $english);
        }
        foreach (['Betalingsprovider Details', 'Testmodus', 'Configuratie', 'Basisinformatie', 'Systeeminformatie', 'Testresultaat Verbinding', 'Veilig versleuteld opgeslagen'] as $dutch) {
            $this->assertStringNotContainsString($dutch, $this->visibleHtml($show), $dutch);
        }
    }

    #[Test]
    public function payment_provider_flash_messages_follow_the_locale(): void
    {
        [$tenant, $session] = $this->tenant('en');
        $provider = $this->provider($tenant);
        $admin = $this->superAdmin();

        $this->actingAs($admin)->withSession($session)
            ->post(route('admin.payment-providers.toggle-status', $provider))
            ->assertSessionHas('success', 'Payment provider deactivated successfully.');

        $this->actingAs($admin)->withSession($session)
            ->postJson(route('admin.payment-providers.toggle-status', $provider))
            ->assertJson(['success' => true, 'message' => 'Payment provider activated successfully.']);

        $this->actingAs($admin)->withSession($session)
            ->delete(route('admin.payment-providers.destroy', $provider))
            ->assertSessionHas('success', 'Payment provider deleted successfully.');
    }

    #[Test]
    public function nexa_suite_settings_and_procedure_are_english_in_en_locale(): void
    {
        [, $session] = $this->tenant('en');
        $admin = $this->superAdmin();

        $settings = $this->actingAs($admin)->withSession($session)
            ->get(route('admin.nexa-suite-bookings.settings'))->assertOk()->getContent();
        foreach (['NEXA Suite booking settings', 'How this is used', 'Platform collect is on', 'Mollie (customer app / marketplace)', 'API key', 'Webhook URL', 'Commission', 'Minimum fee (Mollie costs)', 'Fetch prices from Mollie', 'Automatic specification (PDF)', 'VAT &amp; reminders', 'Invoice header (PDF)', 'Overview of fee and what you keep', 'Driver receives', 'You keep', 'Save', 'Public host', 'Use for payments', 'The marked environment is the public host for Mollie return URLs and webhooks. In the app you choose which server to call.', 'Environment', 'Delete environment', 'Are you sure you want to delete the environment “Productie”? This cannot be undone.'] as $english) {
            $this->assertStringContainsString($english, $settings, $english);
        }
        foreach (['NEXA Suite boekingsinstellingen', 'Hoe dit gebruikt wordt', 'Platform collect staat aan', 'Provisie', 'Minimale fee', 'Prijzen bij Mollie ophalen', 'Automatische specificatie', 'BTW &amp; aanmaningen', 'Factuurkop', 'Chauffeur krijgt', 'Jij houdt over', 'Opslaan', 'Publieke host', 'Gebruik voor betalingen', 'De gemarkeerde omgeving', 'Toevoegen', 'aria-label="Verwijderen"', 'Omgeving verwijderen'] as $dutch) {
            $this->assertStringNotContainsString($dutch, $this->visibleHtml($settings), $dutch);
        }
        $this->assertStringContainsString('aria-label="Delete"', $settings);
        $this->assertStringContainsString('aria-label="Save"', $settings);

        $this->actingAs($admin)->withSession($session)
            ->post(route('admin.nexa-suite-bookings.settings.public-hosts.store'), [
                'name' => 'Locale host',
                'host' => 'https://locale.example.nl',
            ])
            ->assertRedirect(route('admin.nexa-suite-bookings.settings'))
            ->assertSessionHas('success', 'Environment saved.');

        $procedure = $this->actingAs($admin)->withSession($session)
            ->get(route('admin.nexa-suite-bookings.procedure'))->assertOk()->getContent();
        foreach (['Marketplace procedure', 'Pickup code (PIN)', '4-digit pickup code', 'Customer picked up — enter code', 'Payout', 'Completing far from the destination'] as $english) {
            $this->assertStringContainsString($english, $procedure, $english);
        }
        foreach (['Werkwijze marktplaats', 'Ophaalcode', '4-cijferige', 'Uitbetaling', 'Afronden ver van de bestemming'] as $dutch) {
            $this->assertStringNotContainsString($dutch, $this->visibleHtml($procedure), $dutch);
        }
    }

    #[Test]
    public function nexa_suite_invoices_and_invoice_detail_are_english_in_en_locale(): void
    {
        [$tenant, $session] = $this->tenant('en');
        $invoice = $this->invoice($tenant);
        $admin = $this->superAdmin();

        $list = $this->actingAs($admin)->withSession($session)
            ->get(route('admin.nexa-suite-bookings.invoices'))->assertOk()->getContent();
        foreach (['NEXA Suite invoices', 'Create invoices', 'Send immediately', 'All tenants', 'All statuses', 'Reminder', 'Sent'] as $english) {
            $this->assertStringContainsString($english, $list, $english);
        }
        foreach (['NEXA Suite facturen', 'Facturen maken', 'Direct versturen', 'Alle tenants', 'Alle statussen', 'Aanmaning', 'Verzonden'] as $dutch) {
            $this->assertStringNotContainsString($dutch, $this->visibleHtml($list), $dutch);
        }

        $show = $this->actingAs($admin)->withSession($session)
            ->get(route('admin.nexa-suite-bookings.invoices.show', $invoice))->assertOk()->getContent();
        foreach (['Invoice NSB-2026-0001', 'Locale Pay Taxi · period 2026-09', 'Invoice details', '3 completed rides from NEXA Suite', 'Ride turnover', 'Excl. VAT', 'Due date', 'Send reminder', 'Mark as paid', 'Change status'] as $english) {
            $this->assertStringContainsString($english, $show, $english);
        }
        foreach (['Factuurgegevens', 'gereden ritten vanuit', 'Ritomzet', 'Excl. BTW', 'Vervaldatum', 'Aanmaning sturen', 'Markeer betaald', 'Status aanpassen'] as $dutch) {
            $this->assertStringNotContainsString($dutch, $this->visibleHtml($show), $dutch);
        }
    }

    #[Test]
    public function nexa_suite_flash_messages_follow_the_locale(): void
    {
        [$tenant, $session] = $this->tenant('en');
        $invoice = $this->invoice($tenant);
        $admin = $this->superAdmin();

        $this->actingAs($admin)->withSession($session)
            ->post(route('admin.nexa-suite-bookings.invoices.mark-paid', $invoice))
            ->assertSessionHas('success', 'Invoice marked as paid.');

        $this->actingAs($admin)->withSession($session)
            ->post(route('admin.nexa-suite-bookings.invoices.send', $invoice->fresh()))
            ->assertSessionHas('error', 'This invoice has already been paid.');

        $this->actingAs($admin)->withSession($session)
            ->post(route('admin.nexa-suite-bookings.invoices.status', $invoice), ['status' => 'cancelled'])
            ->assertSessionHas('success', 'Status updated to Cancelled.');

        $this->actingAs($admin)->withSession($session)
            ->put(route('admin.nexa-suite-bookings.settings.update'), ['fee_percent' => 'abc'])
            ->assertSessionHasErrors('fee_percent');
    }

    #[Test]
    public function dutch_locale_output_is_unchanged(): void
    {
        [$tenant, $session] = $this->tenant('nl');
        $provider = $this->provider($tenant);
        $invoice = $this->invoice($tenant);
        $admin = $this->superAdmin();

        $index = $this->actingAs($admin)->withSession($session)->get(route('admin.payment-providers.index'))->assertOk()->getContent();
        foreach (['Betalingsproviders', 'Nieuwe Betalingsprovider', 'Test Modus', 'Zoek betalingsproviders...', 'Toon 1 tot 1 van 1 betalingsproviders', 'Acties'] as $dutch) {
            $this->assertStringContainsString($dutch, $index, $dutch);
        }

        $show = $this->actingAs($admin)->withSession($session)->get(route('admin.payment-providers.show', $provider))->assertOk()->getContent();
        foreach (['Betalingsprovider Details - Mollie Locale', 'Testmodus', 'Basisinformatie', 'Veilig versleuteld opgeslagen. Wijzigen via Bewerken.'] as $dutch) {
            $this->assertStringContainsString($dutch, $show, $dutch);
        }

        $settings = $this->actingAs($admin)->withSession($session)->get(route('admin.nexa-suite-bookings.settings'))->assertOk()->getContent();
        foreach (['NEXA Suite boekingsinstellingen', 'Hoe dit gebruikt wordt', 'Platform collect staat aan', 'Minimale fee (Mollie-kosten)', 'Jij houdt over', 'Opslaan', 'Publieke host', 'Gebruik voor betalingen', 'Toevoegen', 'De gemarkeerde omgeving is de publieke host voor Mollie-terugkeer en webhooks. In de app kies je zelf welke server je aanroept.', 'Weet je zeker dat je de omgeving “Productie” wilt verwijderen? Dit kan niet ongedaan worden gemaakt.'] as $dutch) {
            $this->assertStringContainsString($dutch, $settings, $dutch);
        }

        $invoiceShow = $this->actingAs($admin)->withSession($session)->get(route('admin.nexa-suite-bookings.invoices.show', $invoice))->assertOk()->getContent();
        foreach (['Factuur NSB-2026-0001', 'Locale Pay Taxi · periode 2026-09', '3 gereden ritten vanuit NEXA Suite', 'Aanmaning sturen', 'Verzonden'] as $dutch) {
            $this->assertStringContainsString($dutch, $invoiceShow, $dutch);
        }

        $this->actingAs($admin)->withSession($session)
            ->post(route('admin.nexa-suite-bookings.invoices.mark-paid', $invoice))
            ->assertSessionHas('success', 'Factuur gemarkeerd als betaald.');
    }

    #[Test]
    public function validation_and_connection_messages_follow_the_locale(): void
    {
        $admin = $this->superAdmin();

        [$tenant, $session] = $this->tenant('en');
        $provider = $this->provider($tenant);

        $settings = $this->actingAs($admin)->withSession($session)
            ->get(route('admin.nexa-suite-bookings.settings', ['bedrag' => '0']))->assertOk()->getContent();
        $this->assertStringContainsString('Enter an amount between €0.01 and €5,000.', $settings);
        $this->assertStringNotContainsString('Vul een bedrag in', $this->visibleHtml($settings));

        $this->actingAs($admin)->withSession($session)
            ->postJson(route('admin.payment-providers.test-connection', $provider))
            ->assertJson(['success' => false, 'message' => 'No Mollie API key set.']);

        [$tenantNl, $sessionNl] = $this->tenant('nl');
        $providerNl = $this->provider($tenantNl);

        $settingsNl = $this->actingAs($admin)->withSession($sessionNl)
            ->get(route('admin.nexa-suite-bookings.settings', ['bedrag' => '0']))->assertOk()->getContent();
        $this->assertStringContainsString('Vul een bedrag in tussen €0,01 en €5.000.', $settingsNl);

        $this->actingAs($admin)->withSession($sessionNl)
            ->postJson(route('admin.payment-providers.test-connection', $providerNl))
            ->assertJson(['success' => false, 'message' => 'Geen Mollie API-sleutel ingesteld.']);
    }
}
