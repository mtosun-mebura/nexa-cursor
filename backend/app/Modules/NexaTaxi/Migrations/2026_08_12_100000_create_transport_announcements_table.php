<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contractportaal: verstoringen / aankondigingen per contractklant.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transport_announcements')) {
            Schema::create('transport_announcements', function (Blueprint $table) {
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

    public function down(): void
    {
        Schema::dropIfExists('transport_announcements');
    }
};
