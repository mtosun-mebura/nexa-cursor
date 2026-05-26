<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('host', 255)->comment('Normalized hostname, lowercase, no port');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique('host');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_domains');
    }
};
