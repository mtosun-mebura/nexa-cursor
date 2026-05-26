<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Zet branch-namen naar Nederlands (zelfde labels als CategorySeeder) en voegt Taxi toe.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('branches')) {
            return;
        }

        $slugToName = [
            'it' => 'IT',
            'marketing' => 'Marketing',
            'finance' => 'Financiën',
            'hr' => 'HR',
            'sales' => 'Verkoop',
            'engineering' => 'Techniek',
            'healthcare' => 'Gezondheidszorg',
            'education' => 'Onderwijs',
            'construction' => 'Bouw',
            'hospitality' => 'Horeca',
            'legal' => 'Juridisch',
            'real-estate' => 'Vastgoed',
            'retail' => 'Detailhandel',
            'travel-tourism' => 'Reizen & toerisme',
            'transportation' => 'Transport',
            'manufacturing' => 'Productie',
            'arts' => 'Kunst & cultuur',
            'science' => 'Wetenschap',
            'government' => 'Overheid',
            'non-profit' => 'Non-profit',
            'accounting' => 'Boekhouding',
            'advertising' => 'Reclame',
            'agriculture' => 'Landbouw',
            'automotive' => 'Automotive',
            'biotechnology' => 'Biotechnologie',
            'consulting' => 'Consultancy',
            'sports' => 'Sport',
            'energy' => 'Energie',
            'entertainment' => 'Entertainment',
            'environmental' => 'Milieu & duurzaamheid',
            'fashion' => 'Mode',
            'food-beverage' => 'Food & beverage',
            'gaming' => 'Gaming',
            'insurance' => 'Verzekeringen',
            'media' => 'Media',
            'pharmaceuticals' => 'Farmacie',
            'public-relations' => 'Public relations',
            'research-development' => 'Research & development',
            'security' => 'Beveiliging',
            'telecommunications' => 'Telecommunicatie',
        ];

        foreach ($slugToName as $slug => $name) {
            DB::table('branches')->where('slug', $slug)->update(['name' => $name]);
        }

        $englishToDutch = [
            'IT' => 'IT',
            'Marketing' => 'Marketing',
            'Finance' => 'Financiën',
            'HR' => 'HR',
            'Sales' => 'Verkoop',
            'Engineering' => 'Techniek',
            'Healthcare' => 'Gezondheidszorg',
            'Education' => 'Onderwijs',
            'Construction' => 'Bouw',
            'Hospitality' => 'Horeca',
            'Legal' => 'Juridisch',
            'Real Estate' => 'Vastgoed',
            'Retail' => 'Detailhandel',
            'Travel & Tourism' => 'Reizen & toerisme',
            'Transportation' => 'Transport',
            'Manufacturing' => 'Productie',
            'Arts' => 'Kunst & cultuur',
            'Science' => 'Wetenschap',
            'Government' => 'Overheid',
            'Non-Profit' => 'Non-profit',
            'Accounting' => 'Boekhouding',
            'Advertising' => 'Reclame',
            'Agriculture' => 'Landbouw',
            'Automotive' => 'Automotive',
            'Biotechnology' => 'Biotechnologie',
            'Consulting' => 'Consultancy',
            'Sports' => 'Sport',
            'Energy' => 'Energie',
            'Entertainment' => 'Entertainment',
            'Environmental' => 'Milieu & duurzaamheid',
            'Fashion' => 'Mode',
            'Food & Beverage' => 'Food & beverage',
            'Gaming' => 'Gaming',
            'Insurance' => 'Verzekeringen',
            'Media' => 'Media',
            'Pharmaceuticals' => 'Farmacie',
            'Public Relations' => 'Public relations',
            'Research & Development' => 'Research & development',
            'Security' => 'Beveiliging',
            'Telecommunications' => 'Telecommunicatie',
        ];

        if (Schema::hasTable('companies')) {
            foreach ($englishToDutch as $en => $nl) {
                if ($en === $nl) {
                    continue;
                }
                DB::table('companies')->where('industry', $en)->update(['industry' => $nl]);
            }
        }

        $exists = DB::table('branches')->where('slug', 'taxi')->exists();
        if (! $exists) {
            $maxOrder = (int) DB::table('branches')->max('sort_order');
            DB::table('branches')->insert([
                'name' => 'Taxi',
                'slug' => 'taxi',
                'description' => 'Vacatures in de sector: Taxi',
                'icon' => 'fas fa-taxi',
                'color' => '#FFC107',
                'is_active' => true,
                'sort_order' => $maxOrder + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Geen terugdraaiing: bestaande data kan gemengd Engels/Nederlands zijn.
    }
};
