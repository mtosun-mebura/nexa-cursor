<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ride_requests') && ! Schema::hasColumn('ride_requests', 'payment_method')) {
            Schema::table('ride_requests', function (Blueprint $table) {
                $table->string('payment_method', 20)->nullable()->after('quoted_price');
                $table->string('payment_status', 20)->nullable()->after('payment_method');
                $table->decimal('final_price', 10, 2)->nullable()->after('payment_status');
            });
        }

        if (! Schema::hasTable('ride_payments')) {
            Schema::create('ride_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ride_request_id')->index();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->string('channel', 20);
                $table->string('mollie_payment_id', 64)->nullable()->unique();
                $table->decimal('amount', 10, 2);
                $table->char('currency', 3)->default('EUR');
                $table->string('status', 24)->default('open')->index();
                $table->string('checkout_url', 500)->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->json('mollie_payload')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ride_payments')) {
            Schema::dropIfExists('ride_payments');
        }

        if (Schema::hasTable('ride_requests') && Schema::hasColumn('ride_requests', 'payment_method')) {
            Schema::table('ride_requests', function (Blueprint $table) {
                $table->dropColumn(['payment_method', 'payment_status', 'final_price']);
            });
        }
    }
};
