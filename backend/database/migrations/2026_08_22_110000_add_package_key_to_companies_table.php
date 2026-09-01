<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'package_key')) {
                $table->string('package_key', 80)->nullable()->after('frontend_theme_id')->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('companies') || ! Schema::hasColumn('companies', 'package_key')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('package_key');
        });
    }
};
