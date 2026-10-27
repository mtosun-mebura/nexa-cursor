<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\RidePlatformSettlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminLocaleEnBPaymentsATest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    private function superAdmin(): User
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }

    private function visibleHtml(string $html): string
    {
        return (string) preg_replace('/<!--.*?-->/s', '', $html);
    }

    private function tenantSession(string $locale, ?Company $tenant = null): array
    {
        $tenant ??= Company::query()->create(['name' => 'Locale Pay Taxi', 'is_active' => true]);

        return ['selected_tenant' => $tenant->id, 'locale' => $locale];
    }

    private function invoice(Company $company, string $status = 'sent'): Invoice
    {
        return Invoice::query()->create([
            'invoice_number' => 'LP2026-0001',
            'company_id' => $company->id,
            'amount' => 10,
            'tax_amount' => 2.1,
            'total_amount' => 12.1,
            'currency' => 'EUR',
            'status' => $status,
            'invoice_date' => now(),
            'due_date' => now()->addDays(14),
            'line_items' => [],
        ]);
    }

    /**
     * @param  list<string>  $english
     * @param  list<string>  $dutch
     */
    private function assertPage(User $admin, array $session, string $url, array $english, array $dutch): void
    {
        $html = $this->actingAs($admin)->withSession($session)->get($url)->assertOk()->getContent();
        $visible = $this->visibleHtml($html);
        foreach ($english as $text) {
            $this->assertStringContainsString($text, $html, $text);
        }
        foreach ($dutch as $text) {
            $this->assertStringNotContainsString($text, $visible, $text);
        }
    }

    #[Test]
    public function payments_pages_are_english_in_en_locale(): void
    {
        $admin = $this->superAdmin();
        $session = $this->tenantSession('en');

        $this->assertPage($admin, $session, route('admin.payments.index'),
            ['Payments overview', 'Outstanding payments', 'Completed payments', 'Payments per tenant', 'Actions'],
            ['Betalingen Overzicht', 'Openstaande Betalingen', 'Voldane Betalingen', 'Betalingen per tenant', 'Acties']);

        $this->assertPage($admin, $session, route('admin.payments.openstaand'),
            ['Back to overview', 'Search…', 'Amount', 'Reference', 'No outstanding payments for these tenants.'],
            ['Terug naar Overzicht', 'Zoeken…', 'Bedrag', 'Referentie', 'Geen openstaande betalingen voor deze tenants.']);

        $this->assertPage($admin, $session, route('admin.payments.voldaan'),
            ['Revenue on this page', 'Revenue (last 12 months)', 'Paid on', 'No paid payments for these tenants.'],
            ['Omzet op deze pagina', 'Inkomsten (laatste 12 maanden)', 'Betaald op', 'Geen voldane betalingen voor deze tenants.']);
    }

    #[Test]
    public function financial_overview_page_and_messages_are_english_in_en_locale(): void
    {
        $admin = $this->superAdmin();
        $session = $this->tenantSession('en');

        $this->assertPage($admin, $session, route('admin.payments.overzichten'),
            ['Overview type', 'Layout', 'Show period', 'Download', 'VAT overview', 'Invoice register', '1st quarter (January–March)', 'January'],
            ['Type overzicht', 'Indeling', 'Periode tonen', 'BTW-overzicht', 'Factuurregister', '1e kwartaal', 'januari']);

        $this->actingAs($admin)
            ->withSession($session)
            ->getJson(route('admin.payments.overzichten.preview', ['period_type' => 'bogus']))
            ->assertStatus(422)
            ->assertJson(['message' => 'Invalid period.']);
    }

    #[Test]
    public function ride_invoices_pages_are_english_in_en_locale(): void
    {
        $admin = $this->superAdmin();
        $session = $this->tenantSession('en');
        $invoice = $this->invoice(Company::query()->findOrFail($session['selected_tenant']));

        $this->assertPage($admin, $session, route('admin.invoices.index'),
            ['New invoice', 'Overview of all invoices', 'Search invoices…', 'Invoice number', 'Due date', 'Sent', 'Showing 1 to 1 of 1 invoices'],
            ['Nieuwe Factuur', 'Overzicht van alle facturen', 'Zoek facturen…', 'Factuurnummer', 'Vervaldatum', 'Verzonden', 'Toon 1 tot']);

        $this->assertPage($admin, $session, route('admin.invoices.create'),
            ['Basic Information', 'Create invoice', 'Amount information', 'VAT percentage', 'Partial invoice information', 'Select date'],
            ['Basis Informatie', 'Factuur Aanmaken', 'Bedrag Informatie', 'BTW Percentage', 'Deelfactuur Informatie', 'Selecteer datum']);

        $this->assertPage($admin, $session, route('admin.invoices.edit', $invoice->id),
            ['Edit invoice:', 'Edit invoice details', 'Parent invoice number', 'Default: 21%'],
            ['Factuur Bewerken', 'Bewerk factuurdetails', 'Ouder Factuurnummer', 'Standaard:']);

        $this->assertPage($admin, $session, route('admin.invoices.show', $invoice->id),
            ['Invoice details', 'Generate PDF', 'Send reminder', 'Payment links', 'Subtotal:'],
            ['Factuur Details', 'PDF genereren', 'Verstuur Aanmaning', 'Betaallinks', 'Subtotaal:']);

        $this->assertPage($admin, $session, route('admin.invoices.settings'),
            ['Invoice settings', 'Invoice number settings', 'Next invoice number', 'Payment terms text', 'Footer text'],
            ['Factuurinstellingen', 'Factuurnummer Instellingen', 'Volgende Factuurnummer', 'Betaaltermijntekst', 'Footer Tekst']);
    }

    #[Test]
    public function payment_links_json_is_english_in_en_locale_and_dutch_in_nl_locale(): void
    {
        $admin = $this->superAdmin();
        $session = $this->tenantSession('en');
        $invoice = $this->invoice(Company::query()->findOrFail($session['selected_tenant']));

        $this->actingAs($admin)->withSession($session)
            ->getJson(route('admin.invoices.payment-links', $invoice->id))
            ->assertOk()
            ->assertJsonFragment(['label' => 'Bank transfer'])
            ->assertJsonFragment(['hint' => 'Bank payment instruction page']);

        $this->actingAs($admin)->withSession(['locale' => 'nl'] + $session)
            ->getJson(route('admin.invoices.payment-links', $invoice->id))
            ->assertOk()
            ->assertJsonFragment(['label' => 'Bankoverschrijving']);
    }

    #[Test]
    public function customer_invoices_pages_and_flash_are_english_in_en_locale(): void
    {
        $admin = $this->superAdmin();
        $session = $this->tenantSession('en');

        $this->assertPage($admin, $session, route('admin.tenant-customer-invoices.index'),
            ['Customer invoices', 'New invoice', 'No customer invoices yet.'],
            ['Klantfacturen', 'Nieuwe factuur', 'Nog geen klantfacturen.']);

        $this->assertPage($admin, $session, route('admin.tenant-customer-invoices.create'),
            ['New customer invoice', 'Customer name', 'Amount excl. VAT', 'Create invoice'],
            ['Nieuwe klantfactuur', 'Klantnaam', 'Bedrag excl. BTW', 'Factuur aanmaken']);

        $response = $this->actingAs($admin)->withSession($session)->post(route('admin.tenant-customer-invoices.store'), [
            'customer_name' => 'Jan Klant',
            'description' => 'Rit',
            'amount' => 10,
        ]);
        $response->assertRedirect();
        $this->assertSame('Invoice created.', session('success'));

        // De show-route bindt het model niet (resource-parameter heet anders dan $invoice), dus de view wordt direct gerenderd.
        $invoice = Invoice::query()->where('module', Invoice::MODULE_CUSTOMER)->firstOrFail();
        $this->actingAs($admin)->withSession($session);
        $html = view('admin.tenant-customer-invoices.show', ['invoice' => $invoice])->render();
        $visible = $this->visibleHtml($html);
        foreach (['Send with Mollie payment link', 'Customer:', 'Send'] as $text) {
            $this->assertStringContainsString($text, $html, $text);
        }
        foreach (['Verstuur met Mollie-betaallink', 'Klant:', 'Versturen'] as $text) {
            $this->assertStringNotContainsString($text, $visible, $text);
        }
    }

    #[Test]
    public function payout_onboarding_page_is_english_in_en_locale(): void
    {
        $admin = $this->superAdmin();
        $session = $this->tenantSession('en');

        $this->assertPage($admin, $session, route('admin.payout-identities.index'),
            ['Settlement party: taxi company (legal entity)', 'Bank account (display)', 'Not filled in yet', 'Enter bank account', 'Advanced: provider metadata (super-admin)', 'Disable payout', 'To payment providers'],
            ['Bankrekening (weergave)', 'Nog niet ingevuld', 'Bankrekening invullen', 'Geavanceerd', 'Payout uitschakelen', 'Naar betalingsproviders']);
    }

    #[Test]
    public function payment_flows_pages_are_english_in_en_locale(): void
    {
        $admin = $this->superAdmin();
        $session = $this->tenantSession('en');
        $company = Company::query()->findOrFail($session['selected_tenant']);

        RidePlatformSettlement::query()->create([
            'ride_request_id' => 42,
            'model' => RidePlatformSettlement::MODEL_MARKETPLACE,
            'owner_company_id' => $company->id,
            'fulfiller_company_id' => $company->id,
            'gross_amount' => 100,
            'nexa_fee_percent' => 10,
            'nexa_fee_amount' => 10,
            'net_amount' => 90,
            'owner_share_percent' => 100,
            'owner_share_amount' => 90,
            'fulfiller_share_percent' => 0,
            'fulfiller_share_amount' => 0,
            'currency' => 'EUR',
            'status' => RidePlatformSettlement::STATUS_PENDING_PAYOUT,
        ]);

        $this->assertPage($admin, $session, route('admin.payment-flows.guide'),
            ['Payments explained', 'Super-admin only', 'Settlement queue', 'Core principle: platform collect', 'Customer pays NEXA', 'Automatic and manual', 'Own tenant rides (no platform fee)'],
            ['Uitleg betalingen', 'Alleen super-admin', 'Settlement-wachtrij', 'Kernprincipe', 'Klant betaalt NEXA', 'Automatisch én handmatig', 'Eigen tenant-ritten']);

        $this->assertPage($admin, $session, route('admin.payment-flows.settlements'),
            ['Process queue now', 'Awaiting payout', 'All statuses', 'Net / split', 'Force paid'],
            ['Verwerk wachtrij nu', 'Wacht op uitbetaling', 'Alle statussen', 'Netto / split', 'Forceer betaald']);

        $this->actingAs($admin)->withSession($session)
            ->post(route('admin.payment-flows.settlements.process'))
            ->assertRedirect();
        $this->assertStringStartsWith('New ledgers:', (string) session('success'));
    }

    #[Test]
    public function pages_stay_dutch_in_nl_locale(): void
    {
        $admin = $this->superAdmin();
        $session = $this->tenantSession('nl');
        $invoice = $this->invoice(Company::query()->findOrFail($session['selected_tenant']));

        $this->assertPage($admin, $session, route('admin.payments.index'),
            ['Betalingen Overzicht', 'Openstaande Betalingen', 'Voldane Betalingen', 'Betalingen per tenant'], ['Payments overview']);
        $this->assertPage($admin, $session, route('admin.payments.overzichten'),
            ['Type overzicht', 'Indeling', 'Periode tonen', 'BTW-overzicht', 'Factuurregister', '1e kwartaal (januari–maart)'], ['Overview type']);
        $this->assertPage($admin, $session, route('admin.invoices.index'),
            ['Nieuwe Factuur', 'Overzicht van alle facturen', 'Zoek facturen…', 'Toon 1 tot 1 van 1 facturen'], ['New invoice']);
        $this->assertPage($admin, $session, route('admin.invoices.create'),
            ['Basis Informatie', 'Factuur Aanmaken', 'Deelfactuur Informatie'], ['Basic Information']);
        $this->assertPage($admin, $session, route('admin.invoices.show', $invoice->id),
            ['Factuur Details', 'PDF genereren', 'Verstuur Aanmaning', 'Subtotaal:'], ['Generate PDF']);
        $this->assertPage($admin, $session, route('admin.invoices.settings'),
            ['Factuurinstellingen', 'Footer Tekst'], ['Invoice settings']);
        $this->assertPage($admin, $session, route('admin.tenant-customer-invoices.index'),
            ['Klantfacturen', 'Nog geen klantfacturen.'], ['Customer invoices']);
        $this->assertPage($admin, $session, route('admin.payout-identities.index'),
            ['Bankrekening invullen', 'Payout uitschakelen'], ['Enter bank account']);
        $this->assertPage($admin, $session, route('admin.payment-flows.guide'),
            ['Uitleg betalingen', 'Kernprincipe: platform collect', 'Automatisch én handmatig'], ['Payments explained']);

        $this->actingAs($admin)->withSession($session)
            ->getJson(route('admin.payments.overzichten.preview', ['period_type' => 'bogus']))
            ->assertStatus(422)
            ->assertJson(['message' => 'Ongeldige periode.']);
    }
}
