<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transport_customers')) {
            return;
        }

        if (Schema::hasColumn('transport_customers', 'organization_type')) {
            return;
        }

        Schema::table('transport_customers', function (Blueprint $table) {
            $table->string('organization_type', 32)->default('overig')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('transport_customers')) {
            return;
        }

        if (! Schema::hasColumn('transport_customers', 'organization_type')) {
            return;
        }

        Schema::table('transport_customers', function (Blueprint $table) {
            $table->dropColumn('organization_type');
        });
    }
};
