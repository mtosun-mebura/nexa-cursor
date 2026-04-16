<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'logo_dark_blob')) {
                $table->text('logo_dark_blob')->nullable();
            }
            if (! Schema::hasColumn('companies', 'logo_dark_mime_type')) {
                $table->string('logo_dark_mime_type', 255)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'logo_dark_mime_type')) {
                $table->dropColumn('logo_dark_mime_type');
            }
            if (Schema::hasColumn('companies', 'logo_dark_blob')) {
                $table->dropColumn('logo_dark_blob');
            }
        });
    }
};
