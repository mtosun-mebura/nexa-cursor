<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stage_instances', function (Blueprint $table) {
            $table->string('type')->nullable()->after('outcome');
            $table->unsignedSmallInteger('duration')->nullable()->after('type');
            $table->string('location_type')->nullable()->after('notes');
            $table->foreignId('company_location_id')->nullable()->constrained('company_locations')->onDelete('set null')->after('location_type');
            $table->string('location')->nullable()->after('company_location_id');
            $table->string('scheduled_time')->nullable()->after('location');
            $table->foreignId('interviewer_id')->nullable()->constrained('users')->onDelete('set null')->after('scheduled_time');
            $table->string('interviewer_name')->nullable()->after('interviewer_id');
            $table->string('interviewer_email')->nullable()->after('interviewer_name');
        });
    }

    public function down(): void
    {
        Schema::table('stage_instances', function (Blueprint $table) {
            $table->dropForeign(['interviewer_id']);
            $table->dropColumn([
                'interviewer_email',
                'interviewer_name',
                'interviewer_id',
                'scheduled_time',
                'location',
                'company_location_id',
                'location_type',
                'duration',
                'type',
            ]);
        });
    }
};
