<?php

namespace App\Modules\NexaTaxi\Services;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Maakt contractvervoer-tabellen aan als ze ontbreken (ook als een oudere migratie-run al geregistreerd staat).
 */
final class TaxiContractvervoerSchemaService
{
    public function ensureTablesExist(?string $connection = null): void
    {
        $schema = $this->schema($connection);

        if (! $schema->hasTable('transport_customers')) {
            $schema->create('transport_customers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
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

        if (! $schema->hasTable('transport_contracts')) {
            $schema->create('transport_contracts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('transport_customer_id')->index();
                $table->string('name');
                $table->string('status', 24)->default('active')->index();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('billing_model', 24)->default('fixed_monthly')->index();
                $table->decimal('monthly_amount', 10, 2)->nullable();
                $table->decimal('price_per_ride', 10, 2)->nullable();
                $table->unsignedSmallInteger('invoice_day')->default(1);
                $table->unsignedSmallInteger('payment_terms_days')->default(14);
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('transport_payment_mandates')) {
            $schema->create('transport_payment_mandates', function (Blueprint $table) {
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

        if (! $schema->hasTable('transport_passengers')) {
            $schema->create('transport_passengers', function (Blueprint $table) {
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

        if (! $schema->hasTable('transport_groups')) {
            $schema->create('transport_groups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('transport_contract_id')->index();
                $table->string('name');
                $table->string('destination_address');
                $table->decimal('destination_lat', 10, 7)->nullable();
                $table->decimal('destination_lng', 10, 7)->nullable();
                $table->time('destination_arrival_time');
                $table->text('notes')->nullable();
                $table->boolean('active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('transport_group_members')) {
            $schema->create('transport_group_members', function (Blueprint $table) {
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

        if (! $schema->hasTable('transport_route_templates')) {
            $schema->create('transport_route_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('transport_group_id')->index();
                $table->string('label');
                $table->json('recurrence_days')->nullable();
                $table->string('driver_start_mode', 24)->default('depot')->index();
                $table->string('driver_start_address')->nullable();
                $table->decimal('driver_start_lat', 10, 7)->nullable();
                $table->decimal('driver_start_lng', 10, 7)->nullable();
                $table->unsignedInteger('buffer_seconds')->default(120);
                $table->boolean('route_locked')->default(false)->index();
                $table->boolean('active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('transport_route_stops')) {
            $schema->create('transport_route_stops', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('transport_route_template_id')->index();
                $table->unsignedSmallInteger('sequence');
                $table->string('stop_type', 24);
                $table->unsignedBigInteger('transport_passenger_id')->nullable()->index();
                $table->string('address');
                $table->decimal('lat', 10, 7)->nullable();
                $table->decimal('lng', 10, 7)->nullable();
                $table->time('planned_at_time');
                $table->timestamps();
                $table->unique(['transport_route_template_id', 'sequence'], 'route_stop_unique_sequence');
            });
        }

        if (! $schema->hasTable('transport_assignments')) {
            $schema->create('transport_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->string('assignable_type', 64);
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

        if (! $schema->hasTable('transport_individual_bookings')) {
            $schema->create('transport_individual_bookings', function (Blueprint $table) {
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
                $table->string('status', 24)->default('planned')->index();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('transport_occurrences')) {
            $schema->create('transport_occurrences', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('transport_contract_id')->index();
                $table->string('occurrence_type', 24)->index();
                $table->unsignedBigInteger('transport_route_template_id')->nullable()->index();
                $table->unsignedBigInteger('transport_individual_booking_id')->nullable()->index();
                $table->date('scheduled_date');
                $table->timestamp('scheduled_at')->nullable();
                $table->string('status', 24)->default('planned')->index();
                $table->foreignId('ride_request_id')->nullable()->constrained('ride_requests')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('ride_stops')) {
            $schema->create('ride_stops', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ride_request_id')->index()->constrained('ride_requests')->cascadeOnDelete();
                $table->unsignedSmallInteger('sequence');
                $table->string('stop_type', 24)->index();
                $table->unsignedBigInteger('transport_passenger_id')->nullable()->index();
                $table->string('passenger_name')->nullable()->index();
                $table->string('address');
                $table->decimal('lat', 10, 7)->nullable();
                $table->decimal('lng', 10, 7)->nullable();
                $table->timestamp('planned_at')->nullable();
                $table->string('status', 24)->default('planned')->index();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->unique(['ride_request_id', 'sequence'], 'ride_stop_unique_sequence');
            });
        }

        $this->ensureContractPortalTables($connection);
        $this->ensureRideRequestContractColumns($connection);
    }

    public function ensureContractPortalTables(?string $connection = null): void
    {
        $schema = $this->schema($connection);

        if (! $schema->hasTable('transport_customer_portal_users')) {
            $schema->create('transport_customer_portal_users', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('transport_customer_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('portal_role', 32);
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->unique(['transport_customer_id', 'user_id'], 'tcp_users_customer_user_unique');
            });
        }

        if (! $schema->hasTable('transport_passenger_guardians')) {
            $schema->create('transport_passenger_guardians', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('transport_passenger_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->timestamps();
                $table->unique(['transport_passenger_id', 'user_id'], 'tp_guardians_passenger_user_unique');
            });
        }

        if (! $schema->hasTable('transport_passenger_absences')) {
            $schema->create('transport_passenger_absences', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('transport_passenger_id')->index();
                $table->date('absence_date')->index();
                $table->string('reason', 500)->nullable();
                $table->unsignedBigInteger('created_by_user_id')->nullable()->index();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();
                $table->unique(['transport_passenger_id', 'absence_date'], 'tp_absences_passenger_date_unique');
            });
        }

        if (! $schema->hasTable('transport_announcements')) {
            $schema->create('transport_announcements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('transport_customer_id')->index();
                $table->string('title', 200);
                $table->text('body')->nullable();
                $table->string('severity', 16)->default('info');
                $table->timestamp('starts_at')->nullable()->index();
                $table->timestamp('ends_at')->nullable()->index();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }
    }

    public function ensureRideRequestContractColumns(?string $connection = null): void
    {
        $schema = $this->schema($connection);

        if (! $schema->hasTable('ride_requests')) {
            return;
        }

        $cols = $schema->getColumnListing('ride_requests');

        if (! in_array('source', $cols, true)) {
            $schema->table('ride_requests', function (Blueprint $table) {
                $table->string('source', 24)->default('booking')->index();
            });
        }

        if (! in_array('ride_type', $cols, true)) {
            $schema->table('ride_requests', function (Blueprint $table) {
                $table->string('ride_type', 24)->default('standard')->index();
            });
        }

        if (! in_array('transport_contract_id', $cols, true)) {
            $schema->table('ride_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('transport_contract_id')->nullable()->index();
            });
        }

        if (! in_array('transport_occurrence_id', $cols, true)) {
            $schema->table('ride_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('transport_occurrence_id')->nullable()->index();
            });
        }

        if (! in_array('transport_passenger_id', $cols, true)) {
            $schema->table('ride_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('transport_passenger_id')->nullable()->index();
            });
        }

        if (! in_array('payment_method', $cols, true)) {
            $schema->table('ride_requests', function (Blueprint $table) {
                $table->string('payment_method', 20)->nullable();
            });
        }

        $cols = $schema->getColumnListing('ride_requests');

        if (! in_array('return_at', $cols, true)) {
            $schema->table('ride_requests', function (Blueprint $table) {
                $table->dateTime('return_at')->nullable();
            });
        }

        $cols = $schema->getColumnListing('ride_requests');

        if (! in_array('outbound_completed_at', $cols, true)) {
            $schema->table('ride_requests', function (Blueprint $table) {
                $table->dateTime('outbound_completed_at')->nullable();
            });
        }

        $cols = $schema->getColumnListing('ride_requests');

        if (! in_array('outbound_driver_id', $cols, true)) {
            $schema->table('ride_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('outbound_driver_id')->nullable();
            });
        }

        $cols = $schema->getColumnListing('ride_requests');

        if (! in_array('return_started_at', $cols, true)) {
            $schema->table('ride_requests', function (Blueprint $table) {
                $table->dateTime('return_started_at')->nullable();
            });
        }
    }

    private function schema(?string $connection): \Illuminate\Database\Schema\Builder
    {
        return Schema::connection($connection ?? (string) config('database.default'));
    }
}
