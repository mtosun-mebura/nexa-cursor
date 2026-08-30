<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('platform_billing_packages') && ! Schema::hasColumn('platform_billing_packages', 'package_key')) {
            Schema::table('platform_billing_packages', function (Blueprint $table) {
                $table->string('package_key', 80)->nullable()->after('name')->unique();
            });
        }

        if (Schema::hasTable('company_billing_profiles')) {
            Schema::table('company_billing_profiles', function (Blueprint $table) {
                if (! Schema::hasColumn('company_billing_profiles', 'pending_change_type')) {
                    $table->string('pending_change_type', 32)->nullable()->after('subscription_end_date');
                }
                if (! Schema::hasColumn('company_billing_profiles', 'pending_package_key')) {
                    $table->string('pending_package_key', 80)->nullable()->after('pending_change_type');
                }
                if (! Schema::hasColumn('company_billing_profiles', 'pending_change_effective_on')) {
                    $table->date('pending_change_effective_on')->nullable()->after('pending_package_key');
                }
                if (! Schema::hasColumn('company_billing_profiles', 'pending_proration_amount')) {
                    $table->decimal('pending_proration_amount', 10, 2)->nullable()->after('pending_change_effective_on');
                }
                if (! Schema::hasColumn('company_billing_profiles', 'pending_proration_label')) {
                    $table->string('pending_proration_label', 255)->nullable()->after('pending_proration_amount');
                }
                if (! Schema::hasColumn('company_billing_profiles', 'pending_proration_applied_at')) {
                    $table->timestamp('pending_proration_applied_at')->nullable()->after('pending_proration_label');
                }
            });
        }

        if (! Schema::hasTable('company_subscription_changes')) {
            Schema::create('company_subscription_changes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('company_billing_profile_id')->nullable()->constrained('company_billing_profiles')->nullOnDelete();
                $table->string('change_type', 32);
                $table->string('status', 32)->default('scheduled');
                $table->string('from_package_key', 80)->nullable();
                $table->string('to_package_key', 80)->nullable();
                $table->decimal('from_monthly_amount', 10, 2)->nullable();
                $table->decimal('to_monthly_amount', 10, 2)->nullable();
                $table->date('effective_on');
                $table->timestamp('requested_at')->nullable();
                $table->timestamp('applied_at')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'status']);
                $table->index(['status', 'effective_on']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_subscription_changes');

        if (Schema::hasTable('company_billing_profiles')) {
            Schema::table('company_billing_profiles', function (Blueprint $table) {
                foreach ([
                    'pending_proration_applied_at',
                    'pending_proration_label',
                    'pending_proration_amount',
                    'pending_change_effective_on',
                    'pending_package_key',
                    'pending_change_type',
                ] as $column) {
                    if (Schema::hasColumn('company_billing_profiles', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('platform_billing_packages') && Schema::hasColumn('platform_billing_packages', 'package_key')) {
            Schema::table('platform_billing_packages', function (Blueprint $table) {
                $table->dropUnique(['package_key']);
                $table->dropColumn('package_key');
            });
        }
    }
};
