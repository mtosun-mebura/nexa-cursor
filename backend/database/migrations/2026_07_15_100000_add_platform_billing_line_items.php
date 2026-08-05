<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platform_billing_line_items')) {
            Schema::create('platform_billing_line_items', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('unit_price', 12, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('company_billing_profiles')) {
            Schema::table('company_billing_profiles', function (Blueprint $table) {
                if (! Schema::hasColumn('company_billing_profiles', 'extra_lines_one_time')) {
                    $table->boolean('extra_lines_one_time')->default(true)->after('notes');
                }
                if (! Schema::hasColumn('company_billing_profiles', 'extra_lines_applied_at')) {
                    $table->timestamp('extra_lines_applied_at')->nullable()->after('extra_lines_one_time');
                }
            });
        }

        if (! Schema::hasTable('company_billing_profile_line_item')) {
            Schema::create('company_billing_profile_line_item', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_billing_profile_id')->constrained('company_billing_profiles')->cascadeOnDelete();
                $table->foreignId('platform_billing_line_item_id')->constrained('platform_billing_line_items')->cascadeOnDelete();
                $table->unique(['company_billing_profile_id', 'platform_billing_line_item_id'], 'cbp_line_item_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_billing_profile_line_item');

        if (Schema::hasTable('company_billing_profiles')) {
            Schema::table('company_billing_profiles', function (Blueprint $table) {
                foreach (['extra_lines_applied_at', 'extra_lines_one_time'] as $column) {
                    if (Schema::hasColumn('company_billing_profiles', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('platform_billing_line_items');
    }
};
