<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ride_requests') && ! Schema::hasColumn('ride_requests', 'invoice_id')) {
            Schema::table('ride_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('invoice_id')->nullable()->after('final_price')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ride_requests') && Schema::hasColumn('ride_requests', 'invoice_id')) {
            Schema::table('ride_requests', function (Blueprint $table) {
                $table->dropColumn('invoice_id');
            });
        }
    }
};
