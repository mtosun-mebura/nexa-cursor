<?php

use App\Services\NexaSuitePublicHost;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nexa_suite_marketplace_settings')) {
            return;
        }
        if (! Schema::hasColumn('nexa_suite_marketplace_settings', 'public_hosts')) {
            Schema::table('nexa_suite_marketplace_settings', function (Blueprint $table) {
                $table->json('public_hosts')->nullable();
            });
        }

        app(NexaSuitePublicHost::class)->backfillFromLegacyColumn();
    }

    public function down(): void
    {
        if (! Schema::hasTable('nexa_suite_marketplace_settings')) {
            return;
        }
        if (! Schema::hasColumn('nexa_suite_marketplace_settings', 'public_hosts')) {
            return;
        }

        Schema::table('nexa_suite_marketplace_settings', function (Blueprint $table) {
            $table->dropColumn('public_hosts');
        });
    }
};
