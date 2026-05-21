<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_providers')) {
            return;
        }

        if (! Schema::hasColumn('payment_providers', 'company_id')) {
            Schema::table('payment_providers', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id')->index();
            });
        }

        try {
            Schema::table('payment_providers', function (Blueprint $table) {
                $table->dropIndex(['provider_type']);
            });
        } catch (\Throwable) {
            // index kan al ontbreken of andere naam hebben
        }

        try {
            Schema::table('payment_providers', function (Blueprint $table) {
                $table->unique(['company_id', 'provider_type'], 'payment_providers_company_provider_unique');
            });
        } catch (\Throwable) {
            // unieke index bestaat mogelijk al
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('payment_providers')) {
            return;
        }

        Schema::table('payment_providers', function (Blueprint $table) {
            $table->dropUnique('payment_providers_company_provider_unique');
            $table->index('provider_type');
        });

        if (Schema::hasColumn('payment_providers', 'company_id')) {
            Schema::table('payment_providers', function (Blueprint $table) {
                $table->dropColumn('company_id');
            });
        }
    }
};
