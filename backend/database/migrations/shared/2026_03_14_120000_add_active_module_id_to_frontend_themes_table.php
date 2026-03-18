<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('frontend_themes', function (Blueprint $table) {
            $table->foreignId('active_module_id')->nullable()->after('is_active')->constrained('modules')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('frontend_themes', function (Blueprint $table) {
            $table->dropForeign(['active_module_id']);
        });
    }
};
