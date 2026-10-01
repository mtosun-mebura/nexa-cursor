<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ride_requests')) {
            return;
        }

        if (! Schema::hasColumn('ride_requests', 'fulfilling_company_id')) {
            Schema::table('ride_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('fulfilling_company_id')->nullable()->after('company_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ride_requests') && Schema::hasColumn('ride_requests', 'fulfilling_company_id')) {
            Schema::table('ride_requests', function (Blueprint $table) {
                $table->dropColumn('fulfilling_company_id');
            });
        }
    }
};
