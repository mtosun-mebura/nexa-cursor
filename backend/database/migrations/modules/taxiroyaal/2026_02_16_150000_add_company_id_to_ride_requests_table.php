<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ride_requests')) {
            return;
        }
        if (Schema::hasColumn('ride_requests', 'company_id')) {
            return;
        }
        Schema::table('ride_requests', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ride_requests', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });
    }
};
