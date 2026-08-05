<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Herstelt ontbrekende platform-billing tabellen/kolommen als een eerdere migratie
 * halverwege is mislukt (bijv. door ontbrekende after()-kolom op oudere DB's).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platform_billing_settings')) {
            Schema::create('platform_billing_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedTinyInteger('billing_day')->default(1);
                $table->string('billing_time', 5)->default('05:00');
                $table->string('sender_name')->nullable();
                $table->string('sender_email')->nullable();
                $table->decimal('tax_rate_percent', 5, 2)->default(21);
                $table->unsignedSmallInteger('payment_terms_days')->default(14);
                $table->text('invoice_footer')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('platform_billing_packages')) {
            Schema::create('platform_billing_packages', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('monthly_amount', 10, 2);
                $table->string('currency', 3)->default('EUR');
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('company_billing_profiles')) {
            Schema::create('company_billing_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('billing_mode', 20)->default('package');
                $table->foreignId('platform_billing_package_id')->nullable()->constrained('platform_billing_packages')->nullOnDelete();
                $table->decimal('custom_monthly_amount', 10, 2)->nullable();
                $table->decimal('discount_percent', 5, 2)->default(0);
                $table->string('billing_email')->nullable();
                $table->string('billing_contact_name')->nullable();
                $table->boolean('auto_collect_enabled')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('platform_invoices')) {
            Schema::create('platform_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('invoice_number')->unique();
                $table->string('billing_period', 7);
                $table->decimal('amount', 10, 2);
                $table->decimal('tax_amount', 10, 2)->default(0);
                $table->decimal('total_amount', 10, 2);
                $table->string('currency', 3)->default('EUR');
                $table->string('status', 20)->default('draft');
                $table->date('invoice_date');
                $table->date('due_date')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->json('line_items')->nullable();
                $table->text('notes')->nullable();
                $table->string('pdf_path')->nullable();
                $table->string('mollie_payment_id')->nullable();
                $table->string('collection_method', 30)->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'billing_period']);
                $table->index('status');
            });
        }

        if (! Schema::hasTable('platform_payment_mandates')) {
            Schema::create('platform_payment_mandates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('mollie_customer_id')->nullable();
                $table->string('mollie_mandate_id')->nullable();
                $table->string('status', 20)->default('pending');
                $table->string('verification_mollie_payment_id')->nullable();
                $table->timestamp('signed_at')->nullable();
                $table->timestamp('last_requested_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('platform_payments')) {
            Schema::create('platform_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('platform_invoice_id')->nullable()->constrained('platform_invoices')->nullOnDelete();
                $table->string('type', 30);
                $table->string('mollie_payment_id')->unique();
                $table->decimal('amount', 10, 2);
                $table->string('currency', 3)->default('EUR');
                $table->string('status', 30);
                $table->json('mollie_payload')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (! Schema::hasColumn('invoices', 'mollie_payment_id')) {
                    $table->string('mollie_payment_id')->nullable();
                }
                if (! Schema::hasColumn('invoices', 'mollie_checkout_url')) {
                    $table->text('mollie_checkout_url')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // Geen down: dit is een herstelmigratie.
    }
};
