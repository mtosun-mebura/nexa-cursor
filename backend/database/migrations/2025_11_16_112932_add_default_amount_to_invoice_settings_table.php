<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if column already exists before adding it
        if (!Schema::hasColumn('invoice_settings', 'default_amount')) {
            Schema::table('invoice_settings', function (Blueprint $table) {
                $table->decimal('default_amount', 10, 2)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if column exists before dropping it
        if (Schema::hasColumn('invoice_settings', 'default_amount')) {
            Schema::table('invoice_settings', function (Blueprint $table) {
                $table->dropColumn('default_amount');
            });
        }
    }
};
