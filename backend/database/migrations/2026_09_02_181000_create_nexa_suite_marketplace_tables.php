<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nexa_suite_marketplace_settings')) {
            Schema::create('nexa_suite_marketplace_settings', function (Blueprint $table) {
                $table->id();
                $table->decimal('fee_percent', 5, 2)->default(10);
                $table->boolean('auto_generate')->default(true);
                $table->boolean('auto_send')->default(true);
                $table->unsignedTinyInteger('billing_day')->default(1);
                $table->string('billing_time', 5)->default('06:30');
                $table->decimal('tax_rate_percent', 5, 2)->default(21);
                $table->unsignedSmallInteger('payment_terms_days')->default(14);
                $table->unsignedSmallInteger('dunning_first_interval_days')->default(1);
                $table->unsignedSmallInteger('dunning_interval_days')->default(14);
                $table->string('invoice_number_prefix', 20)->default('NSB');
                $table->string('invoice_number_format', 80)->default('{prefix}-{year}-{number}');
                $table->unsignedInteger('next_invoice_number')->default(1);
                $table->unsignedSmallInteger('current_year')->nullable();
                $table->string('invoice_title')->nullable();
                $table->string('sender_name')->nullable();
                $table->string('sender_email')->nullable();
                $table->text('invoice_footer')->nullable();
                $table->text('invoice_payment_terms_text')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('nexa_suite_booking_invoices')) {
            Schema::create('nexa_suite_booking_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('invoice_number')->unique();
                $table->string('billing_period', 7)->index();
                $table->unsignedInteger('ride_count')->default(0);
                $table->decimal('rides_subtotal', 10, 2)->default(0);
                $table->decimal('fee_percent', 5, 2)->default(10);
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('tax_amount', 10, 2)->default(0);
                $table->decimal('total_amount', 10, 2)->default(0);
                $table->string('currency', 3)->default('EUR');
                $table->string('status', 24)->default('draft')->index();
                $table->date('invoice_date')->nullable();
                $table->date('due_date')->nullable();
                $table->unsignedSmallInteger('payment_terms_days')->default(14);
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('first_reminder_sent_at')->nullable();
                $table->timestamp('second_reminder_sent_at')->nullable();
                $table->json('line_items')->nullable();
                $table->json('issuer_details')->nullable();
                $table->json('recipient_details')->nullable();
                $table->text('notes')->nullable();
                $table->string('pdf_path')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'billing_period']);
            });
        }

        if (! Schema::hasTable('nexa_suite_booking_invoice_rides')) {
            Schema::create('nexa_suite_booking_invoice_rides', function (Blueprint $table) {
                $table->id();
                $table->foreignId('nexa_suite_booking_invoice_id')
                    ->constrained('nexa_suite_booking_invoices')
                    ->cascadeOnDelete();
                $table->unsignedBigInteger('ride_request_id');
                $table->decimal('ride_price', 10, 2)->default(0);
                $table->decimal('fee_amount', 10, 2)->default(0);
                $table->timestamps();

                $table->unique('ride_request_id', 'nexa_suite_invoice_rides_ride_unique');
                $table->index(['nexa_suite_booking_invoice_id', 'ride_request_id'], 'nexa_suite_invoice_rides_lookup');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nexa_suite_booking_invoice_rides');
        Schema::dropIfExists('nexa_suite_booking_invoices');
        Schema::dropIfExists('nexa_suite_marketplace_settings');
    }
};
