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
            if (! Schema::hasColumn('companies', 'favicon_blob')) {
                $table->longText('favicon_blob')->nullable()->after('logo_dark_mime_type');
            }
            if (! Schema::hasColumn('companies', 'favicon_mime_type')) {
                $table->string('favicon_mime_type', 64)->nullable()->after('favicon_blob');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'favicon_mime_type')) {
                $table->dropColumn('favicon_mime_type');
            }
            if (Schema::hasColumn('companies', 'favicon_blob')) {
                $table->dropColumn('favicon_blob');
            }
        });
    }
};
