<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payout_identities')) {
            Schema::create('payout_identities', function (Blueprint $table) {
                $table->id();
                $table->string('owner_key', 64)->unique();
                $table->unsignedBigInteger('company_id')->index();
                // Null for company settlement party; only set if independent-driver payouts are legally enabled.
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('settlement_party', 32)->default('company')->index(); // company | independent_driver
                $table->string('provider', 32)->default('mollie')->index(); // mollie | …
                $table->string('provider_account_id', 128)->nullable()->index();
                $table->string('provider_organization_id', 128)->nullable()->index();
                $table->string('capability_status', 32)->default('not_started')->index();
                // pending | restricted | enabled | disabled | rejected
                $table->string('masked_destination')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('disabled_at')->nullable();
                // High-risk destination change hold
                $table->string('pending_provider_account_id', 128)->nullable();
                $table->string('pending_masked_destination')->nullable();
                $table->timestamp('destination_change_requested_at')->nullable();
                $table->timestamp('destination_change_eligible_at')->nullable();
                $table->timestamp('last_synced_at')->nullable();
                $table->json('provider_meta')->nullable(); // non-secret status payload only
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('payout_identity_audit_logs')) {
            Schema::create('payout_identity_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payout_identity_id')->nullable()->index();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('actor_user_id')->nullable()->index();
                $table->string('action', 64)->index();
                $table->json('payload')->nullable();
                $table->string('ip', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_identity_audit_logs');
        Schema::dropIfExists('payout_identities');
    }
};
