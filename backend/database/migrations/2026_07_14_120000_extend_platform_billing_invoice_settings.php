<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('platform_billing_settings')) {
            Schema::table('platform_billing_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('platform_billing_settings', 'invoice_number_prefix')) {
                    $table->string('invoice_number_prefix', 10)->default('SAAS')->after('invoice_footer');
                }
                if (! Schema::hasColumn('platform_billing_settings', 'invoice_number_format')) {
                    $table->string('invoice_number_format', 100)->default('{prefix}-{year}-{number}')->after('invoice_number_prefix');
                }
                if (! Schema::hasColumn('platform_billing_settings', 'next_invoice_number')) {
                    $table->unsignedInteger('next_invoice_number')->default(1)->after('invoice_number_format');
                }
                if (! Schema::hasColumn('platform_billing_settings', 'current_year')) {
                    $table->unsignedSmallInteger('current_year')->nullable()->after('next_invoice_number');
                }
                if (! Schema::hasColumn('platform_billing_settings', 'invoice_title')) {
                    $table->string('invoice_title')->default('SaaS-factuur')->after('current_year');
                }
                if (! Schema::hasColumn('platform_billing_settings', 'company_name')) {
                    $table->string('company_name')->nullable()->after('invoice_title');
                }
                if (! Schema::hasColumn('platform_billing_settings', 'company_address')) {
                    $table->string('company_address')->nullable()->after('company_name');
                }
                if (! Schema::hasColumn('platform_billing_settings', 'company_city')) {
                    $table->string('company_city', 100)->nullable()->after('company_address');
                }
                if (! Schema::hasColumn('platform_billing_settings', 'company_postal_code')) {
                    $table->string('company_postal_code', 20)->nullable()->after('company_city');
                }
                if (! Schema::hasColumn('platform_billing_settings', 'company_country')) {
                    $table->string('company_country', 100)->nullable()->after('company_postal_code');
                }
                if (! Schema::hasColumn('platform_billing_settings', 'company_vat_number')) {
                    $table->string('company_vat_number', 50)->nullable()->after('company_country');
                }
                if (! Schema::hasColumn('platform_billing_settings', 'company_email')) {
                    $table->string('company_email')->nullable()->after('company_vat_number');
                }
                if (! Schema::hasColumn('platform_billing_settings', 'company_phone')) {
                    $table->string('company_phone', 50)->nullable()->after('company_email');
                }
                if (! Schema::hasColumn('platform_billing_settings', 'bank_account')) {
                    $table->string('bank_account', 50)->nullable()->after('company_phone');
                }
                if (! Schema::hasColumn('platform_billing_settings', 'invoice_payment_terms_text')) {
                    $table->text('invoice_payment_terms_text')->nullable()->after('bank_account');
                }
            });
        }

        if (Schema::hasTable('platform_invoices')) {
            Schema::table('platform_invoices', function (Blueprint $table) {
                if (! Schema::hasColumn('platform_invoices', 'issuer_details')) {
                    $table->json('issuer_details')->nullable()->after('line_items');
                }
                if (! Schema::hasColumn('platform_invoices', 'recipient_details')) {
                    $table->json('recipient_details')->nullable()->after('issuer_details');
                }
                if (! Schema::hasColumn('platform_invoices', 'payment_terms_days')) {
                    $table->unsignedSmallInteger('payment_terms_days')->nullable()->after('due_date');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('platform_invoices')) {
            Schema::table('platform_invoices', function (Blueprint $table) {
                foreach (['payment_terms_days', 'recipient_details', 'issuer_details'] as $column) {
                    if (Schema::hasColumn('platform_invoices', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('platform_billing_settings')) {
            Schema::table('platform_billing_settings', function (Blueprint $table) {
                foreach ([
                    'invoice_payment_terms_text',
                    'bank_account',
                    'company_phone',
                    'company_email',
                    'company_vat_number',
                    'company_country',
                    'company_postal_code',
                    'company_city',
                    'company_address',
                    'company_name',
                    'invoice_title',
                    'current_year',
                    'next_invoice_number',
                    'invoice_number_format',
                    'invoice_number_prefix',
                ] as $column) {
                    if (Schema::hasColumn('platform_billing_settings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
