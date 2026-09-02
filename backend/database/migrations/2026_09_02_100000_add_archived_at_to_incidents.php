<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('incidents') || Schema::hasColumn('incidents', 'archived_at')) {
            return;
        }

        Schema::table('incidents', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('resolved_at');
            $table->index('archived_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('incidents') || ! Schema::hasColumn('incidents', 'archived_at')) {
            return;
        }

        Schema::table('incidents', function (Blueprint $table) {
            $table->dropIndex(['archived_at']);
            $table->dropColumn('archived_at');
        });
    }
};
