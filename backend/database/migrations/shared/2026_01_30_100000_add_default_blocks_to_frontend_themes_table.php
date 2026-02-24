<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('frontend_themes', function (Blueprint $table) {
            $table->json('default_blocks')->nullable()->after('settings');
        });
    }

    public function down(): void
    {
        Schema::table('frontend_themes', function (Blueprint $table) {
            $table->dropColumn('default_blocks');
        });
    }
};
