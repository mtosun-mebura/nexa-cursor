<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoice_settings', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number_prefix')->default('NX'); // NX
            $table->string('invoice_number_format')->default('{prefix}{year}-{number}'); // NX2025-0001
            $table->integer('next_invoice_number')->default(1);
            $table->integer('current_year')->default(date('Y'));
            $table->string('company_name')->nullable();
            $table->string('company_address')->nullable();
            $table->string('company_city')->nullable();
            $table->string('company_postal_code')->nullable();
            $table->string('company_country')->nullable();
            $table->string('company_vat_number')->nullable();
            $table->string('company_email')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('bank_account')->nullable();
            $table->decimal('default_tax_rate', 5, 2)->default(21.00); // 21% VAT
            $table->integer('payment_terms_days')->default(30); // 30 days payment terms
            $table->text('invoice_footer_text')->nullable();
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });
        
        // Insert default settings
        DB::table('invoice_settings')->insert([
            'invoice_number_prefix' => 'NX',
            'invoice_number_format' => '{prefix}{year}-{number}',
            'next_invoice_number' => 1,
            'current_year' => date('Y'),
            'default_tax_rate' => 21.00,
            'payment_terms_days' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_settings');
    }
};
