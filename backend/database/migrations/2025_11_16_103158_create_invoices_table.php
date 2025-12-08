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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('job_match_id')->nullable()->constrained('matches')->onDelete('set null');
            $table->decimal('amount', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            $table->boolean('is_partial')->default(false);
            $table->string('parent_invoice_number')->nullable(); // For partial invoices
            $table->integer('partial_number')->nullable(); // 1, 2, 3, etc.
            $table->json('line_items')->nullable(); // Invoice line items
            $table->json('company_details')->nullable(); // Snapshot of company details at invoice time
            $table->text('notes')->nullable();
            $table->string('pdf_path')->nullable(); // Path to generated PDF
            $table->timestamps();
            
            $table->index(['company_id', 'status']);
            $table->index(['status', 'due_date']);
            $table->index('invoice_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
