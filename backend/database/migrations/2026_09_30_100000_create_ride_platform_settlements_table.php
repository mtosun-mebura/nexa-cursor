<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ride_platform_settlements')) {
            return;
        }

        Schema::create('ride_platform_settlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_request_id')->index();
            $table->string('model', 32)->index(); // marketplace|network
            $table->unsignedBigInteger('owner_company_id')->nullable()->index();
            $table->unsignedBigInteger('fulfiller_company_id')->nullable()->index();
            $table->decimal('gross_amount', 12, 2);
            $table->unsignedTinyInteger('nexa_fee_percent')->default(0);
            $table->decimal('nexa_fee_amount', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->unsignedTinyInteger('owner_share_percent')->nullable();
            $table->decimal('owner_share_amount', 12, 2)->default(0);
            $table->unsignedTinyInteger('fulfiller_share_percent')->nullable();
            $table->decimal('fulfiller_share_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->string('status', 40)->index(); // pending_payout|paid_out|failed|manual_required
            $table->unsignedSmallInteger('payout_attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->json('payout_lines')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('paid_out_at')->nullable();
            $table->timestamps();

            $table->unique('ride_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ride_platform_settlements');
    }
};
