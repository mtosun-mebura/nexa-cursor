<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customer_login_codes')) {
            return;
        }

        Schema::table('customer_login_codes', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_login_codes', 'purpose')) {
                $table->string('purpose', 32)->default('customer')->after('user_id');
                $table->index(['user_id', 'purpose']);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('customer_login_codes') || ! Schema::hasColumn('customer_login_codes', 'purpose')) {
            return;
        }

        Schema::table('customer_login_codes', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'purpose']);
            $table->dropColumn('purpose');
        });
    }
};
