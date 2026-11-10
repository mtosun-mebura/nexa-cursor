<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\PlatformBillingLineItem;
use App\Models\PlatformBillingSetting;
use App\Models\PlatformInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminLocaleEnBNexaBillingTest extends TestCase
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

    private function invoice(): PlatformInvoice
    {
        $company = Company::query()->create(['name' => 'Locale Billing Taxi', 'email' => 'billing@example.test', 'is_active' => true]);
        PlatformBillingSetting::current()->update(['tax_rate_percent' => 21]);

        return PlatformInvoice::query()->create([
            'company_id' => $company->id,
            'invoice_number' => 'NEXA-LOC-0001',
            'billing_period' => '2026-09',
            'amount' => 100,
            'tax_amount' => 21,
            'total_amount' => 121,
            'currency' => 'EUR',
            'status' => 'draft',
            'invoice_date' => '2026-09-01',
            'due_date' => '2026-09-15',
            'payment_terms_days' => 14,
            'line_items' => [[
                'description' => 'Business — september 2026',
                'quantity' => 1,
                'unit_price' => 100,
                'total' => 100,
                'type' => 'subscription',
                'billing_period' => '2026-09',
            ]],
        ]);
    }

    private function assertEnglish(string $html, array $english, array $dutch): void
    {
        foreach ($english as $text) {
            $this->assertStringContainsString($text, $html, $text);
        }
        $visible = $this->visibleHtml($html);
        foreach ($dutch as $text) {
            $this->assertStringNotContainsString($text, $visible, $text);
        }
    }

    #[Test]
    public function invoices_index_is_english_in_en_locale(): void
    {
        $this->invoice();

        $html = $this->actingAs($this->superAdmin())
            ->withSession(['locale' => 'en'])
            ->get(route('admin.platform-billing.invoices.index'))
            ->assertOk()
            ->getContent();

        $this->assertEnglish($html, [
            'NEXA invoices', 'Platform → tenant billing', 'Check payments', 'Run billing now', 'NEXA invoices overview',
            'Search number or tenant…', 'All tenants', 'All statuses', 'Invoice number', 'Draft', 'Actions', 'View', 'Send',
            'How NEXA billing works', 'Examples', 'Send invoice NEXA-LOC-0001 to the tenant by email?',
            'Billing settings', 'Monthly invoice', 'Lifting after payment', '14 days after the invoice date',
        ], [
            'NEXA-facturen', 'Platform → tenant facturatie', 'Betalingen controleren', 'Facturatie nu draaien',
            'Overzicht NEXA-facturen', 'Zoek nummer of tenant', 'Alle tenants', 'Alle statussen', 'Concept',
            'Bekijken', 'Versturen', 'Werkwijze NEXA-facturatie', 'Voorbeelden', 'Maandfactuur', 'Opheffen na betaling',
            'per e-mail naar de tenant versturen',
        ]);
    }

    #[Test]
    public function invoices_index_stays_dutch_in_nl_locale(): void
    {
        $this->invoice();

        $html = $this->actingAs($this->superAdmin())
            ->withSession(['locale' => 'nl'])
            ->get(route('admin.platform-billing.invoices.index'))
            ->assertOk()
            ->getContent();

        foreach ([
            'NEXA-facturen', 'Platform → tenant facturatie', 'Betalingen controleren', 'Facturatie nu draaien', 'Overzicht NEXA-facturen',
            'Zoek nummer of tenant…', 'Alle tenants', 'Alle statussen', 'Factuurnummer', 'Concept', 'Bekijken', 'Versturen',
            'Werkwijze NEXA-facturatie', 'Maandfactuur', 'Opheffen na betaling', 'na factuurdatum',
        ] as $dutch) {
            $this->assertStringContainsString($dutch, $html, $dutch);
        }
        $this->assertStringNotContainsString('How NEXA billing works', $html);
    }

    #[Test]
    public function invoice_show_and_edit_are_english_in_en_locale(): void
    {
        $invoice = $this->invoice();
        $admin = $this->superAdmin();

        $show = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.platform-billing.invoices.show', $invoice))
            ->assertOk()
            ->getContent();
        $this->assertEnglish($show, [
            'NEXA invoice', 'Platform → tenant invoice details', 'Invoice details', 'Period', 'Direct debit', 'Subtotal', 'VAT',
            'Payment term', 'Due date', '1st reminder', '2nd reminder', 'Block', 'Invoice lines', 'Price excl. VAT', 'Total excl. VAT',
        ], [
            'Platform → tenant factuurdetails', 'Factuurgegevens', 'Periode', 'Incasso', 'Subtotaal', 'Betaaltermijn', 'Vervaldatum',
            '1e aanmaning', 'Blokkade', 'Factuurregels', 'Prijs excl. BTW',
        ]);

        $edit = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.platform-billing.invoices.edit', $invoice))
            ->assertOk()
            ->getContent();
        $this->assertEnglish($edit, [
            'Edit invoice', 'Back', 'Invoice details', 'Draft', 'Sent', 'Paid', 'Payment term', 'days',
            'The due date is recalculated from the invoice date.', 'Optional internal note…', 'Empty line',
            'Quantity', 'Cancel', 'Save', 'Delete line', 'Business — september 2026',
        ], [
            'Factuur bewerken', 'Factuurgegevens', 'Concept', 'Verzonden', 'Betaaltermijn', 'De vervaldatum wordt herberekend',
            'Optionele interne notitie', 'Lege regel', 'Annuleren', 'Opslaan', 'Regel verwijderen',
        ]);
    }

    #[Test]
    public function invoice_show_stays_dutch_in_nl_locale(): void
    {
        $invoice = $this->invoice();

        $html = $this->actingAs($this->superAdmin())
            ->withSession(['locale' => 'nl'])
            ->get(route('admin.platform-billing.invoices.show', $invoice))
            ->assertOk()
            ->getContent();

        foreach (['Platform → tenant factuurdetails', 'Factuurgegevens', 'Betaaltermijn', '14 dagen', 'Vervaldatum', '1e aanmaning', 'Blokkade', 'Factuurregels', 'Prijs excl. BTW', 'Concept'] as $dutch) {
            $this->assertStringContainsString($dutch, $html, $dutch);
        }
    }

    #[Test]
    public function line_items_index_form_and_flash_are_english_in_en_locale(): void
    {
        $admin = $this->superAdmin();
        $item = PlatformBillingLineItem::query()->create(['name' => 'Setup fee', 'unit_price' => 50, 'is_active' => true, 'sort_order' => 1]);

        $index = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.platform-billing.line-items.index'))
            ->assertOk()
            ->getContent();
        $this->assertEnglish($index, [
            'NEXA invoice lines', 'Generic lines for NEXA invoices', 'New line', 'Search…', 'All statuses', 'Active', 'Inactive',
            'Name', 'Description', 'excl. VAT', 'Actions', 'Edit', 'Deactivate', 'Are you sure you want to delete this invoice line?', 'Setup fee',
        ], [
            'NEXA-factuurregels', 'Generieke regels', 'Nieuwe regel', 'Zoeken…', 'Alle statussen', 'Inactief', 'Naam', 'Omschrijving',
            'Deactiveren', 'Weet je zeker dat je deze factuurregel',
        ]);

        $form = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.platform-billing.line-items.edit', $item))
            ->assertOk()
            ->getContent();
        $this->assertEnglish($form, [
            'Edit invoice line', 'Generic line for NEXA invoices', 'Line details', 'Sort order', 'Price excl. VAT', 'Cancel', 'Save',
        ], [
            'Factuurregel bewerken', 'Regelgegevens', 'Sorteervolgorde', 'Prijs excl. BTW', 'Annuleren', 'Opslaan',
        ]);

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->post(route('admin.platform-billing.line-items.store'), ['name' => 'Extra', 'unit_price' => 10, 'sort_order' => 1])
            ->assertRedirect(route('admin.platform-billing.line-items.index'))
            ->assertSessionHas('success', 'Invoice line created.');
    }

    #[Test]
    public function line_items_flash_stays_dutch_in_nl_locale(): void
    {
        $this->actingAs($this->superAdmin())
            ->withSession(['locale' => 'nl'])
            ->post(route('admin.platform-billing.line-items.store'), ['name' => 'Extra', 'unit_price' => 10, 'sort_order' => 1])
            ->assertSessionHas('success', 'Factuurregel aangemaakt.');
    }

    #[Test]
    public function tenants_index_and_edit_are_english_in_en_locale(): void
    {
        $invoice = $this->invoice();
        $company = $invoice->company;
        $admin = $this->superAdmin();

        $index = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.platform-billing.tenants.index'))
            ->assertOk()
            ->getContent();
        $this->assertEnglish($index, [
            'Tenant subscriptions', 'SaaS subscriptions and direct debit per tenant', 'Invoices use the package and additional modules of the company.',
            'Search tenant…', 'All modes', 'All mandates', 'No mandate', 'Pending', 'Amount/month', 'Mandate', 'Configure',
        ], [
            'Tenant-abonnementen', 'SaaS-abonnementen en incasso per tenant', 'Facturen gebruiken het pakket', 'Zoek tenant…',
            'Alle modi', 'Alle mandaten', 'Geen mandaat', 'In afwachting', 'Bedrag/maand', 'Configureren',
        ]);

        $edit = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.platform-billing.tenants.edit', $company))
            ->assertOk()
            ->getContent();
        $this->assertEnglish($edit, [
            'SaaS subscription and direct debit', 'Subscription &amp; billing', 'Billing mode', 'Fixed package', 'Subscription discount', 'Collect automatically via mandate',
            'Block on non-payment', 'Extra invoice lines', 'Invoice preview', 'Mollie API requests', 'SEPA mandate', 'Request mandate by email',
            'Select date', 'Create customer', 'Back', 'Save', 'Cancel',
        ], [
            'SaaS-abonnement en incasso', 'Facturatiemodus', 'Korting abonnement', 'Automatisch incasseren via mandaat', 'Blokkade bij wanbetaling',
            'Extra factuurregels', 'Factuurvoorbeeld', 'Mandaat aanvragen per e-mail', 'Selecteer datum', 'Klant aanmaken', 'Annuleren',
        ]);
        // De factuurpreview zelf is een klantdocument en blijft Nederlands.
        $this->assertStringContainsString('Factuurnummer', $edit);
    }

    #[Test]
    public function tenants_index_stays_dutch_in_nl_locale(): void
    {
        $this->invoice();

        $html = $this->actingAs($this->superAdmin())
            ->withSession(['locale' => 'nl'])
            ->get(route('admin.platform-billing.tenants.index'))
            ->assertOk()
            ->getContent();

        foreach (['Tenant-abonnementen', 'SaaS-abonnementen en incasso per tenant', 'Zoek tenant…', 'Alle modi', 'Bedrag/maand', 'Mandaat', 'Configureren'] as $dutch) {
            $this->assertStringContainsString($dutch, $html, $dutch);
        }
    }

    #[Test]
    public function settings_page_and_flash_are_english_in_en_locale(): void
    {
        $admin = $this->superAdmin();

        $html = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.platform-billing.settings.edit'))
            ->assertOk()
            ->getContent();
        $this->assertEnglish($html, [
            'NEXA billing settings', 'Back', 'Automatic invoicing', 'Billing day (1–28)', 'Sender email', 'Invoice number', 'Next number',
            'Payment term, reminders &amp; VAT', 'Default payment term (days)', '1st reminder (days)', 'Company details (Nexa / platform on invoice)',
            'Postal code', 'Mollie (NEXA direct debit)', 'Not configured', 'API key', 'Webhook URL', 'Cancel', 'Save',
        ], [
            'NEXA facturatie-instellingen', 'Automatische facturatie', 'Facturatiedag', 'Afzender e-mail', 'Volgende nummer',
            'Standaard betaaltermijn', '1e aanmaning (dagen)', 'Bedrijfsgegevens', 'Niet geconfigureerd', 'API-sleutel', 'Annuleren', 'Opslaan',
        ]);
    }

    #[Test]
    public function settings_page_stays_dutch_in_nl_locale(): void
    {
        $html = $this->actingAs($this->superAdmin())
            ->withSession(['locale' => 'nl'])
            ->get(route('admin.platform-billing.settings.edit'))
            ->assertOk()
            ->getContent();

        foreach (['NEXA facturatie-instellingen', 'Automatische facturatie', 'Facturatiedag (1–28)', 'Standaard betaaltermijn (dagen)', 'Niet geconfigureerd', 'API-sleutel'] as $dutch) {
            $this->assertStringContainsString($dutch, $html, $dutch);
        }
    }

    #[Test]
    public function mandate_return_flash_is_translated(): void
    {
        $company = Company::query()->create(['name' => 'Mandate Taxi', 'is_active' => true]);
        $admin = $this->superAdmin();

        $this->actingAs($admin)->withSession(['locale' => 'en'])
            ->get(route('admin.platform-billing.mandates.return', $company))
            ->assertSessionHas('success', 'Mandate flow completed. Status will be updated after the webhook.');

        $this->actingAs($admin)->withSession(['locale' => 'nl'])
            ->get(route('admin.platform-billing.mandates.return', $company))
            ->assertSessionHas('success', 'Mandaat-flow afgerond. Status wordt bijgewerkt na webhook.');
    }
}
