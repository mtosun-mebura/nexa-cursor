<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Contractklanten / abonnementen
        if (! Schema::hasTable('transport_customers')) {
            Schema::create('transport_customers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index(); // verwijst naar core companies.id (logisch, geen FK)

                $table->string('name');
                $table->string('contact_name')->nullable();
                $table->string('contact_email')->nullable();
                $table->string('contact_phone')->nullable();

                $table->string('debtor_number')->nullable()->index();

                $table->string('billing_address')->nullable();
                $table->string('billing_city')->nullable();
                $table->string('billing_postal_code')->nullable();
                $table->string('billing_country')->nullable();

                $table->text('notes')->nullable();
                $table->boolean('active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('transport_contracts')) {
            Schema::create('transport_contracts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index(); // core companies
                $table->unsignedBigInteger('transport_customer_id')->index();

                $table->string('name');
                $table->string('status', 24)->default('active')->index();

                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();

                $table->string('billing_model', 24)->default('fixed_monthly')->index();
                $table->decimal('monthly_amount', 10, 2)->nullable();
                $table->decimal('price_per_ride', 10, 2)->nullable();
                $table->unsignedSmallInteger('invoice_day')->default(1); // 1-28 (MVP)
                $table->unsignedSmallInteger('payment_terms_days')->default(14);
                $table->decimal('tax_rate', 5, 2)->default(0);

                $table->timestamps();
            });
        }

        if (! Schema::hasTable('transport_payment_mandates')) {
            Schema::create('transport_payment_mandates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('transport_contract_id')->index();

                $table->string('mandate_reference', 64)->nullable()->index();
                $table->string('account_holder')->nullable();
                $table->string('iban', 64)->nullable()->index();
                $table->string('bic', 64)->nullable();

                $table->string('status', 24)->default('pending')->index();
                $table->timestamp('signed_at')->nullable();

                $table->timestamps();
            });
        }

        if (! Schema::hasTable('transport_passengers')) {
            Schema::create('transport_passengers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('transport_contract_id')->index();

                $table->string('first_name');
                $table->string('last_name');
                $table->string('phone')->nullable();

                $table->string('pickup_address');
                $table->decimal('pickup_lat', 10, 7)->nullable();
                $table->decimal('pickup_lng', 10, 7)->nullable();

                $table->text('notes')->nullable();
                $table->boolean('active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('transport_groups')) {
            Schema::create('transport_groups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('transport_contract_id')->index();

                $table->string('name');

                // Eindlocatie (school/ziekenhuis) voor deze groep
                $table->string('destination_address');
                $table->decimal('destination_lat', 10, 7)->nullable();
                $table->decimal('destination_lng', 10, 7)->nullable();
                $table->time('destination_arrival_time'); // HH:MM (lokale planning)

                $table->text('notes')->nullable();
                $table->boolean('active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('transport_group_members')) {
            Schema::create('transport_group_members', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('transport_group_id')->index();
                $table->unsignedBigInteger('transport_passenger_id')->index();

                $table->date('valid_from')->nullable();
                $table->date('valid_until')->nullable();
                $table->unsignedSmallInteger('sort_hint')->nullable();

                $table->timestamps();

                $table->unique(['transport_group_id', 'transport_passenger_id'], 'group_member_unique_current');
            });
        }

        // Route-template voor een groep (startpunt + volgorde) die door scheduler wordt gekopieerd naar occurrences/ride_stops.
        if (! Schema::hasTable('transport_route_templates')) {
            Schema::create('transport_route_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('transport_group_id')->index();

                $table->string('label');
                $table->json('recurrence_days')->nullable(); // bv [1,2,3,4,5]

                // startpunt kiest planner: depot of 'first_stop'
                $table->string('driver_start_mode', 24)->default('depot')->index(); // depot|first_stop
                $table->string('driver_start_address')->nullable();
                $table->decimal('driver_start_lat', 10, 7)->nullable();
                $table->decimal('driver_start_lng', 10, 7)->nullable();

                $table->unsignedInteger('buffer_seconds')->default(120);
                $table->boolean('route_locked')->default(false)->index();

                $table->boolean('active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('transport_route_stops')) {
            Schema::create('transport_route_stops', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('transport_route_template_id')->index();

                $table->unsignedSmallInteger('sequence');

                // pickup/destination
                $table->string('stop_type', 24); // pickup|destination
                $table->unsignedBigInteger('transport_passenger_id')->nullable()->index();

                $table->string('address');
                $table->decimal('lat', 10, 7)->nullable();
                $table->decimal('lng', 10, 7)->nullable();
                $table->time('planned_at_time'); // HH:MM voor pickup/destination

                $table->timestamps();

                $table->unique(['transport_route_template_id', 'sequence'], 'route_stop_unique_sequence');
            });
        }

        // Vaste chauffeur + voertuig
        if (! Schema::hasTable('transport_assignments')) {
            Schema::create('transport_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();

                // polymorf, maar MVP houdt het simpel: assignable_type + assignable_id
                $table->string('assignable_type', 64); // route_template|individual_booking
                $table->unsignedBigInteger('assignable_id')->index();

                $table->unsignedBigInteger('driver_id')->nullable()->index();
                $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();

                $table->date('valid_from')->nullable();
                $table->date('valid_until')->nullable();

                $table->boolean('active')->default(true)->index();
                $table->timestamps();

                $table->index(['assignable_type', 'assignable_id', 'active'], 'transport_assignment_lookup_idx');
            });
        }

        if (! Schema::hasTable('transport_individual_bookings')) {
            Schema::create('transport_individual_bookings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('transport_contract_id')->index();

                $table->unsignedBigInteger('transport_passenger_id')->index();

                $table->string('pickup_address');
                $table->decimal('pickup_lat', 10, 7)->nullable();
                $table->decimal('pickup_lng', 10, 7)->nullable();

                $table->string('dropoff_address');
                $table->decimal('dropoff_lat', 10, 7)->nullable();
                $table->decimal('dropoff_lng', 10, 7)->nullable();

                $table->dateTime('pickup_at');

                $table->unsignedBigInteger('driver_id')->nullable()->index();
                $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();

                $table->decimal('price_override', 10, 2)->nullable();

                $table->string('status', 24)->default('planned')->index(); // planned|cancelled
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('transport_occurrences')) {
            Schema::create('transport_occurrences', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('transport_contract_id')->index();

                $table->string('occurrence_type', 24)->index(); // group|individual
                $table->unsignedBigInteger('transport_route_template_id')->nullable()->index();
                $table->unsignedBigInteger('transport_individual_booking_id')->nullable()->index();

                $table->date('scheduled_date');
                $table->timestamp('scheduled_at')->nullable(); // vertrekmoment

                $table->string('status', 24)->default('planned')->index(); // planned|generated|cancelled|completed

                $table->foreignId('ride_request_id')->nullable()->constrained('ride_requests')->nullOnDelete();

                $table->timestamps();
            });
        }

        // Stoplijst voor multi-stop rides (wordt in MVP gevuld voor contract_group; later ook voor andere routebronnen mogelijk).
        if (! Schema::hasTable('ride_stops')) {
            Schema::create('ride_stops', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ride_request_id')->index()->constrained('ride_requests')->cascadeOnDelete();

                $table->unsignedSmallInteger('sequence');
                $table->string('stop_type', 24)->index(); // pickup|destination

                $table->unsignedBigInteger('transport_passenger_id')->nullable()->index();
                $table->string('passenger_name')->nullable()->index();

                $table->string('address');
                $table->decimal('lat', 10, 7)->nullable();
                $table->decimal('lng', 10, 7)->nullable();

                $table->timestamp('planned_at')->nullable();
                $table->string('status', 24)->default('planned')->index(); // planned|arrived|picked_up|skipped|completed
                $table->timestamp('completed_at')->nullable();

                $table->timestamps();

                $table->unique(['ride_request_id', 'sequence'], 'ride_stop_unique_sequence');
            });
        }

        // Uitbreiding van bestaande ride_requests voor contracten.
        if (Schema::hasTable('ride_requests')) {
            $cols = Schema::getColumnListing('ride_requests');

            if (! in_array('source', $cols, true)) {
                Schema::table('ride_requests', function (Blueprint $table) {
                    $table->string('source', 24)->default('booking')->index(); // booking|contract|manual
                });
            }

            if (! in_array('ride_type', $cols, true)) {
                Schema::table('ride_requests', function (Blueprint $table) {
                    $table->string('ride_type', 24)->default('standard')->index(); // standard|contract_group|contract_individual
                });
            }

            if (! in_array('transport_contract_id', $cols, true)) {
                Schema::table('ride_requests', function (Blueprint $table) {
                    $table->unsignedBigInteger('transport_contract_id')->nullable()->index();
                });
            }

            if (! in_array('transport_occurrence_id', $cols, true)) {
                Schema::table('ride_requests', function (Blueprint $table) {
                    $table->unsignedBigInteger('transport_occurrence_id')->nullable()->index();
                });
            }

            if (! in_array('transport_passenger_id', $cols, true)) {
                Schema::table('ride_requests', function (Blueprint $table) {
                    $table->unsignedBigInteger('transport_passenger_id')->nullable()->index();
                });
            }

            if (! in_array('payment_method', $cols, true)) {
                // payment_method bestaat al in een eerdere migratie, maar keep safe.
                Schema::table('ride_requests', function (Blueprint $table) {
                    $table->string('payment_method', 20)->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        // MVP down-migrations weglaten (veiligheid). Re-run deploy in production via nieuwe migrations.
    }
};

