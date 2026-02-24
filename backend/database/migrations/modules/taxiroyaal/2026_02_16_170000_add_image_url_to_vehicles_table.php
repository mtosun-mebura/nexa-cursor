<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vehicles') || Schema::hasColumn('vehicles', 'image_url')) {
            return;
        }
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('image_url', 500)->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('vehicles', 'image_url')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->dropColumn('image_url');
            });
        }
    }
};
