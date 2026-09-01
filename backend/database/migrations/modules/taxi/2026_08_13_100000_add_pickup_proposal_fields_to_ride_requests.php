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
            if (! Schema::hasColumn('ride_requests', 'pickup_proposal_at')) {
                $table->dateTime('pickup_proposal_at')->nullable()->after('pickup_at');
            }
            if (! Schema::hasColumn('ride_requests', 'pickup_proposal_status')) {
                $table->string('pickup_proposal_status', 32)->nullable()->after('pickup_proposal_at');
            }
            if (! Schema::hasColumn('ride_requests', 'pickup_proposal_customer_remark')) {
                $table->text('pickup_proposal_customer_remark')->nullable()->after('pickup_proposal_status');
            }
            if (! Schema::hasColumn('ride_requests', 'pickup_proposal_sent_at')) {
                $table->timestamp('pickup_proposal_sent_at')->nullable()->after('pickup_proposal_customer_remark');
            }
            if (! Schema::hasColumn('ride_requests', 'pickup_proposal_responded_at')) {
                $table->timestamp('pickup_proposal_responded_at')->nullable()->after('pickup_proposal_sent_at');
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
                'pickup_proposal_at',
                'pickup_proposal_status',
                'pickup_proposal_customer_remark',
                'pickup_proposal_sent_at',
                'pickup_proposal_responded_at',
            ] as $column) {
                if (Schema::hasColumn('ride_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
