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
        $tableNames = config('permission.table_names');

        // Add description and group to permissions table
        Schema::table($tableNames['permissions'], function (Blueprint $table) {
            $table->text('description')->nullable()->after('guard_name');
            $table->string('group', 100)->nullable()->after('description');
        });

        // Add description to roles table
        Schema::table($tableNames['roles'], function (Blueprint $table) {
            $table->text('description')->nullable()->after('guard_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');

        // Remove description and group from permissions table
        Schema::table($tableNames['permissions'], function (Blueprint $table) {
            $table->dropColumn(['description', 'group']);
        });

        // Remove description from roles table
        Schema::table($tableNames['roles'], function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
