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
        Schema::create('pipeline_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->string('name'); // e.g., "Standaard sollicitatieflow"
            $table->string('key')->nullable(); // e.g., "default_general"
            $table->integer('version')->default(1);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('stages'); // Array of stage definitions
            $table->json('terminal_stages')->nullable(); // ["REJECTION", "WITHDRAWN"]
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pipeline_templates');
    }
};
