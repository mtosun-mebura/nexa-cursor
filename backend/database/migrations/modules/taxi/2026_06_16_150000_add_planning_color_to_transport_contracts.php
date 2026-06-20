<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transport_contracts')) {
            return;
        }

        Schema::table('transport_contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('transport_contracts', 'planning_color')) {
                $table->string('planning_color', 7)->default('#3b82f6')->after('name');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('transport_contracts')) {
            return;
        }

        Schema::table('transport_contracts', function (Blueprint $table) {
            if (Schema::hasColumn('transport_contracts', 'planning_color')) {
                $table->dropColumn('planning_color');
            }
        });
    }
};
