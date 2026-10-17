<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payout_identities')) {
            return;
        }
        if (Schema::hasColumn('payout_identities', 'iban_encrypted')) {
            return;
        }

        Schema::table('payout_identities', function (Blueprint $table) {
            $table->text('iban_encrypted')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payout_identities')) {
            return;
        }
        if (! Schema::hasColumn('payout_identities', 'iban_encrypted')) {
            return;
        }

        Schema::table('payout_identities', function (Blueprint $table) {
            $table->dropColumn('iban_encrypted');
        });
    }
};
