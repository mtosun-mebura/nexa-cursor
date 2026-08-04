<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PlatformBillingSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_billing_tables_exist_after_migrations(): void
    {
        $tables = [
            'platform_billing_settings',
            'platform_billing_packages',
            'company_billing_profiles',
            'platform_invoices',
            'platform_payment_mandates',
            'platform_payments',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Tabel {$table} ontbreekt.");
        }
    }

    public function test_invoices_table_has_mollie_columns(): void
    {
        if (! Schema::hasTable('invoices')) {
            $this->markTestSkipped('invoices table not present');
        }

        $this->assertTrue(Schema::hasColumn('invoices', 'mollie_payment_id'));
        $this->assertTrue(Schema::hasColumn('invoices', 'mollie_checkout_url'));
    }

    public function test_platform_billing_settings_have_mollie_credential_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('platform_billing_settings', 'mollie_api_key'));
        $this->assertTrue(Schema::hasColumn('platform_billing_settings', 'mollie_webhook_url'));
    }
}
