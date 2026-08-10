<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ride_dispatch_offers')) {
            return;
        }

        if (! Schema::hasColumn('ride_dispatch_offers', 'decline_reason')) {
            Schema::table('ride_dispatch_offers', function (Blueprint $table) {
                $table->string('decline_reason', 500)->nullable()->after('responded_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ride_dispatch_offers') && Schema::hasColumn('ride_dispatch_offers', 'decline_reason')) {
            Schema::table('ride_dispatch_offers', function (Blueprint $table) {
                $table->dropColumn('decline_reason');
            });
        }
    }
};
