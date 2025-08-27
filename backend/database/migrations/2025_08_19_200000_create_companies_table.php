<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('department', 255)->nullable();
            // Address
            $table->string('street', 255)->nullable();
            $table->string('house_number', 10)->nullable();
            $table->string('house_number_extension', 10)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->nullable();
            // Contact
            $table->string('website', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('contact_first_name', 100)->nullable();
            $table->string('contact_middle_name', 100)->nullable();
            $table->string('contact_last_name', 100)->nullable();
            $table->string('contact_email', 255)->nullable();
            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};


