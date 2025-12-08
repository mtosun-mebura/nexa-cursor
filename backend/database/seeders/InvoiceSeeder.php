<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\Company;
use App\Models\InvoiceSetting;
use Carbon\Carbon;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::where('is_active', true)->get();
        
        if ($companies->isEmpty()) {
            $this->command->warn('Geen actieve bedrijven gevonden. Maak eerst bedrijven aan.');
            return;
        }

        $settings = InvoiceSetting::getSettings();
        $statuses = ['draft', 'sent', 'paid', 'overdue'];
        $amounts = [5000, 7500, 10000, 12500, 15000, 20000, 25000];
        
        // Maak 20 dummy facturen aan
        for ($i = 0; $i < 20; $i++) {
            $company = $companies->random();
            $amount = $amounts[array_rand($amounts)];
            $taxRate = $settings->default_tax_rate;
            $taxAmount = ($amount * $taxRate) / 100;
            $totalAmount = $amount + $taxAmount;
            
            $invoiceDate = Carbon::now()->subDays(rand(0, 90));
            $dueDate = $invoiceDate->copy()->addDays($settings->payment_terms_days);
            $status = $statuses[array_rand($statuses)];
            
            // Genereer factuurnummer
            $year = $invoiceDate->format('Y');
            $number = str_pad($i + 1, 4, '0', STR_PAD_LEFT);
            $invoiceNumber = str_replace(
                ['{prefix}', '{year}', '{number}'],
                [$settings->invoice_number_prefix, $year, $number],
                $settings->invoice_number_format
            );
            
            Invoice::create([
                'invoice_number' => $invoiceNumber,
                'company_id' => $company->id,
                'job_match_id' => null,
                'amount' => $amount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'currency' => 'EUR',
                'status' => $status,
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'paid_date' => $status === 'paid' ? $invoiceDate->copy()->addDays(rand(1, 30)) : null,
                'is_partial' => false,
                'notes' => $i % 3 === 0 ? 'Factuur voor diensten geleverd in ' . $invoiceDate->format('F Y') : null,
            ]);
        }
        
        $this->command->info('20 dummy facturen succesvol aangemaakt!');
    }
}
