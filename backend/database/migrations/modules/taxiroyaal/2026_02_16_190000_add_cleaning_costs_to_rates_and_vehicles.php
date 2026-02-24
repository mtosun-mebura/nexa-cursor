<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reinigingskosten toevoegen aan standaardtarieven en voertuigen.
     */
    public function up(): void
    {
        if (Schema::hasTable('default_rates') && !Schema::hasColumn('default_rates', 'cleaning_costs')) {
            Schema::table('default_rates', function (Blueprint $table) {
                $table->decimal('cleaning_costs', 10, 2)->nullable()->after('price_per_min');
            });
        }
        if (Schema::hasTable('vehicles') && !Schema::hasColumn('vehicles', 'cleaning_costs')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->decimal('cleaning_costs', 10, 2)->nullable()->after('price_per_min');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('default_rates') && Schema::hasColumn('default_rates', 'cleaning_costs')) {
            Schema::table('default_rates', fn (Blueprint $table) => $table->dropColumn('cleaning_costs'));
        }
        if (Schema::hasTable('vehicles') && Schema::hasColumn('vehicles', 'cleaning_costs')) {
            Schema::table('vehicles', fn (Blueprint $table) => $table->dropColumn('cleaning_costs'));
        }
    }
};
