<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('job_configuration_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique(); // e.g., 'employment_type', 'working_hours', 'status'
            $table->string('display_name', 100); // e.g., 'Dienstverband Type', 'Werkuren', 'Status'
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('name');
            $table->index('is_active');
        });
        
        // Insert default types
        DB::table('job_configuration_types')->insert([
            [
                'name' => 'employment_type',
                'display_name' => 'Dienstverband Type',
                'description' => 'Type dienstverband zoals Fulltime, Parttime, etc.',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'working_hours',
                'display_name' => 'Werkuren',
                'description' => 'Werkuren zoals 32-40, 08:00-16:00, etc.',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'status',
                'display_name' => 'Status',
                'description' => 'Status van vacatures zoals Open, Gesloten, etc.',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('job_configuration_types');
    }
};
