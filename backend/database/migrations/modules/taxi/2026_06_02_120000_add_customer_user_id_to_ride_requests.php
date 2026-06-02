<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ride_requests') && ! Schema::hasColumn('ride_requests', 'customer_user_id')) {
            Schema::table('ride_requests', function (Blueprint $table) {
                $table->foreignId('customer_user_id')
                    ->nullable()
                    ->after('customer_email')
                    ->constrained('users')
                    ->nullOnDelete()
                    ->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ride_requests') && Schema::hasColumn('ride_requests', 'customer_user_id')) {
            Schema::table('ride_requests', function (Blueprint $table) {
                $table->dropConstrainedForeignId('customer_user_id');
            });
        }
    }
};

