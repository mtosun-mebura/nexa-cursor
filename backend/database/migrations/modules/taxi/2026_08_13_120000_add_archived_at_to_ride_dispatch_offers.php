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

        Schema::table('ride_dispatch_offers', function (Blueprint $table) {
            if (! Schema::hasColumn('ride_dispatch_offers', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('responded_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ride_dispatch_offers')) {
            return;
        }

        Schema::table('ride_dispatch_offers', function (Blueprint $table) {
            if (Schema::hasColumn('ride_dispatch_offers', 'archived_at')) {
                $table->dropColumn('archived_at');
            }
        });
    }
};
