<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transport_schedule_exceptions')) {
            Schema::create('transport_schedule_exceptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('transport_contract_id')->nullable()->index();
                $table->date('exception_date')->index();
                $table->string('name', 200);
                $table->boolean('active')->default(true)->index();
                $table->timestamps();

                $table->unique(
                    ['company_id', 'transport_contract_id', 'exception_date'],
                    'transport_schedule_exceptions_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_schedule_exceptions');
    }
};
