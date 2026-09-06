<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('incidents') || Schema::hasColumn('incidents', 'page_url')) {
            return;
        }

        Schema::table('incidents', function (Blueprint $table) {
            $table->string('page_url', 2048)->nullable()->after('title');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('incidents') || ! Schema::hasColumn('incidents', 'page_url')) {
            return;
        }

        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn('page_url');
        });
    }
};
