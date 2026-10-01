<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ride_requests')) {
            return;
        }

        Schema::table('ride_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('ride_requests', 'settlement_status')) {
                $table->string('settlement_status', 32)->nullable()->index();
            }
            if (! Schema::hasColumn('ride_requests', 'settlement_hold_until')) {
                $table->timestamp('settlement_hold_until')->nullable()->index();
            }
            if (! Schema::hasColumn('ride_requests', 'settlement_risk_flags')) {
                $table->json('settlement_risk_flags')->nullable();
            }
            if (! Schema::hasColumn('ride_requests', 'settlement_evaluated_at')) {
                $table->timestamp('settlement_evaluated_at')->nullable();
            }
            if (! Schema::hasColumn('ride_requests', 'settlement_eligible_at')) {
                $table->timestamp('settlement_eligible_at')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ride_requests')) {
            return;
        }

        Schema::table('ride_requests', function (Blueprint $table) {
            foreach ([
                'settlement_status',
                'settlement_hold_until',
                'settlement_risk_flags',
                'settlement_evaluated_at',
                'settlement_eligible_at',
            ] as $column) {
                if (Schema::hasColumn('ride_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
