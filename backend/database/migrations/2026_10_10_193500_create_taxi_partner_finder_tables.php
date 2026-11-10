<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('taxi_prospects')) {
            Schema::create('taxi_prospects', function (Blueprint $table) {
                $table->id();
                $table->string('company_name');
                $table->string('trade_name')->nullable();
                $table->string('kvk_number', 20)->nullable();
                $table->string('street')->nullable();
                $table->string('house_number', 20)->nullable();
                $table->string('postal_code', 16)->nullable();
                $table->string('city', 80)->nullable();
                $table->string('municipality', 80)->nullable();
                $table->string('province', 40)->nullable();
                $table->string('country', 2)->default('NL');
                $table->string('phone', 40)->nullable();
                $table->string('email')->nullable();
                $table->string('website')->nullable();
                $table->string('website_domain', 120)->nullable();
                $table->string('google_place_id', 128)->nullable();
                $table->string('knv_member_status', 40)->default('unknown');
                $table->string('verification_status', 40)->default('unverified');
                $table->string('lead_status', 40)->default('discovered');
                $table->unsignedTinyInteger('confidence_score')->default(40);
                $table->string('normalized_name', 180)->nullable();
                $table->string('normalized_postal_code', 16)->nullable();
                $table->timestamp('discovered_at')->nullable();
                $table->timestamp('last_verified_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique('kvk_number');
                $table->unique('google_place_id');
                $table->index(['lead_status', 'province']);
                $table->index(['knv_member_status', 'province']);
                $table->index(['normalized_name', 'normalized_postal_code']);
                $table->index('website_domain');
                $table->index('email');
            });
        }

        if (! Schema::hasTable('taxi_prospect_sources')) {
            Schema::create('taxi_prospect_sources', function (Blueprint $table) {
                $table->id();
                $table->foreignId('taxi_prospect_id')->constrained('taxi_prospects')->cascadeOnDelete();
                $table->string('source_type', 40);
                $table->string('source_url')->nullable();
                $table->string('external_reference', 160)->nullable();
                $table->timestamp('discovered_at')->nullable();
                $table->timestamp('last_checked_at')->nullable();
                $table->json('raw_metadata')->nullable();
                $table->timestamps();

                $table->unique(['source_type', 'external_reference']);
                $table->index(['taxi_prospect_id', 'source_type']);
            });
        }

        if (! Schema::hasTable('taxi_prospect_service_areas')) {
            Schema::create('taxi_prospect_service_areas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('taxi_prospect_id')->constrained('taxi_prospects')->cascadeOnDelete();
                $table->string('municipality_code', 20)->nullable();
                $table->string('municipality_name', 80)->nullable();
                $table->string('service_type', 60)->default('general');
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();

                $table->index(['taxi_prospect_id', 'service_type']);
            });
        }

        if (! Schema::hasTable('taxi_discovery_runs')) {
            Schema::create('taxi_discovery_runs', function (Blueprint $table) {
                $table->id();
                $table->string('source_type', 40);
                $table->string('status', 40)->default('queued');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->unsignedInteger('discovered_count')->default(0);
                $table->unsignedInteger('updated_count')->default(0);
                $table->unsignedInteger('duplicate_count')->default(0);
                $table->unsignedInteger('failed_count')->default(0);
                $table->text('error_summary')->nullable();
                $table->json('meta')->nullable();
                $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['source_type', 'status']);
            });
        }

        if (! Schema::hasTable('taxi_prospect_opt_outs')) {
            Schema::create('taxi_prospect_opt_outs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('taxi_prospect_id')->nullable()->constrained('taxi_prospects')->nullOnDelete();
                $table->string('email')->nullable();
                $table->string('phone', 40)->nullable();
                $table->string('reason')->nullable();
                $table->timestamp('opted_out_at');
                $table->timestamps();

                $table->index('email');
                $table->index('phone');
            });
        }

        if (! Schema::hasTable('taxi_prospect_invitations')) {
            Schema::create('taxi_prospect_invitations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('taxi_prospect_id')->constrained('taxi_prospects')->cascadeOnDelete();
                $table->string('status', 40)->default('draft');
                $table->string('channel', 40)->default('newsletter');
                $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('prepared_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['status', 'channel']);
            });
        }

        if (! Schema::hasTable('taxi_prospect_contacts')) {
            Schema::create('taxi_prospect_contacts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('taxi_prospect_id')->constrained('taxi_prospects')->cascadeOnDelete();
                $table->string('channel', 40)->default('note');
                $table->string('direction', 20)->default('outbound');
                $table->string('subject')->nullable();
                $table->text('body')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('contacted_at')->nullable();
                $table->timestamps();

                $table->index(['taxi_prospect_id', 'contacted_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_prospect_contacts');
        Schema::dropIfExists('taxi_prospect_invitations');
        Schema::dropIfExists('taxi_prospect_opt_outs');
        Schema::dropIfExists('taxi_discovery_runs');
        Schema::dropIfExists('taxi_prospect_service_areas');
        Schema::dropIfExists('taxi_prospect_sources');
        Schema::dropIfExists('taxi_prospects');
    }
};
