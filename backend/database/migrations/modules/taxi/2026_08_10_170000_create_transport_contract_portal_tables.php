<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contractportaal: portalgebruikers, ouder↔passagier, afmeldingen.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transport_customer_portal_users')) {
            Schema::create('transport_customer_portal_users', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('transport_customer_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('portal_role', 32); // contractant | contractouder
                $table->boolean('active')->default(true);
                $table->timestamps();

                $table->unique(
                    ['transport_customer_id', 'user_id'],
                    'tcp_users_customer_user_unique'
                );
            });
        }

        if (! Schema::hasTable('transport_passenger_guardians')) {
            Schema::create('transport_passenger_guardians', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('transport_passenger_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->timestamps();

                $table->unique(
                    ['transport_passenger_id', 'user_id'],
                    'tp_guardians_passenger_user_unique'
                );
            });
        }

        if (! Schema::hasTable('transport_passenger_absences')) {
            Schema::create('transport_passenger_absences', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('transport_passenger_id')->index();
                $table->date('absence_date')->index();
                $table->string('reason', 500)->nullable();
                $table->unsignedBigInteger('created_by_user_id')->nullable()->index();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();

                $table->unique(
                    ['transport_passenger_id', 'absence_date'],
                    'tp_absences_passenger_date_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_passenger_absences');
        Schema::dropIfExists('transport_passenger_guardians');
        Schema::dropIfExists('transport_customer_portal_users');
    }
};
