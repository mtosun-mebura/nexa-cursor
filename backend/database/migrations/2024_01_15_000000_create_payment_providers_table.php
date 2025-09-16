<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('provider_type'); // mollie, stripe, etc.
            $table->boolean('is_active')->default(false);
            $table->json('config')->nullable(); // API keys, tokens, settings
            $table->timestamps();
            $table->index(['provider_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_providers');
    }
};
