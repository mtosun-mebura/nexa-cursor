<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('incidents') || ! Schema::hasColumn('incidents', 'company_id')) {
            return;
        }

        $column = collect(Schema::getColumns('incidents'))->firstWhere('name', 'company_id');
        if (($column['nullable'] ?? false) === true) {
            return;
        }

        Schema::table('incidents', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->change();
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('incidents') || ! Schema::hasColumn('incidents', 'company_id')) {
            return;
        }

        Schema::table('incidents', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable(false)->change();
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });
    }
};
