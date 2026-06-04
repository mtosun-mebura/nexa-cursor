<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'password_must_be_set')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('password_must_be_set')->default(false)->after('password');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'password_must_be_set')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('password_must_be_set');
            });
        }
    }
};
