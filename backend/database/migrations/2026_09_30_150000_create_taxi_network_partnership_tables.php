<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxi_network_invite_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('code', 32)->unique();
            $table->timestamp('expires_at')->nullable()->index();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('use_count')->default(0);
            $table->boolean('auto_accept')->default(false);
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'revoked_at']);
        });

        Schema::create('taxi_network_partnerships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_company_id')->index();
            $table->unsignedBigInteger('partner_company_id')->index();
            $table->string('status', 20)->default('pending')->index(); // pending|accepted|declined|revoked
            $table->unsignedBigInteger('invite_code_id')->nullable()->index();
            $table->unsignedBigInteger('requested_by_company_id')->nullable();
            $table->unsignedBigInteger('acted_by_user_id')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['owner_company_id', 'partner_company_id'], 'taxi_network_partnerships_pair_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_network_partnerships');
        Schema::dropIfExists('taxi_network_invite_codes');
    }
};
