<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->boolean('work_permission_nl')->nullable()->after('postal_code');
            $table->string('availability_type')->nullable()->after('availability'); // 'per_direct' | 'datum' | 'opzegtermijn'
            $table->date('availability_date')->nullable()->after('availability_type');
            $table->integer('notice_weeks')->nullable()->after('availability_date');
            $table->integer('hours_per_week')->nullable()->after('notice_weeks');
            $table->string('work_mode')->nullable()->after('preferred_work_type'); // 'locatie' | 'hybride' | 'remote'
            $table->json('primary_titles')->nullable()->after('desired_position'); // functietitels voorkeur
            $table->json('sectors')->nullable()->after('primary_titles'); // sector/branche
            $table->integer('travel_radius_km')->nullable()->after('preferred_location');
            $table->boolean('drivers_license')->nullable()->after('travel_radius_km');
            $table->boolean('notify_new_roles')->default(false)->after('consent_marketing');
            $table->integer('consent_retention_months')->nullable()->after('notify_new_roles');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn([
                'work_permission_nl',
                'availability_type',
                'availability_date',
                'notice_weeks',
                'hours_per_week',
                'work_mode',
                'primary_titles',
                'sectors',
                'travel_radius_km',
                'drivers_license',
                'notify_new_roles',
                'consent_retention_months'
            ]);
        });
    }
};
