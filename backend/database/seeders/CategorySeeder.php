<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Branches (tabel `branches`): vaste slug (stabiel), weergavenaam Nederlands.
     */
    public function run(): void
    {
        $categories = [
            ['slug' => 'it', 'name' => 'IT', 'icon' => 'fas fa-laptop-code', 'color' => '#2196F3'],
            ['slug' => 'marketing', 'name' => 'Marketing', 'icon' => 'fas fa-bullhorn', 'color' => '#FF9800'],
            ['slug' => 'finance', 'name' => 'Financiën', 'icon' => 'fas fa-chart-line', 'color' => '#4CAF50'],
            ['slug' => 'hr', 'name' => 'HR', 'icon' => 'fas fa-users', 'color' => '#9C27B0'],
            ['slug' => 'sales', 'name' => 'Verkoop', 'icon' => 'fas fa-handshake', 'color' => '#F44336'],
            ['slug' => 'engineering', 'name' => 'Techniek', 'icon' => 'fas fa-cogs', 'color' => '#607D8B'],
            ['slug' => 'healthcare', 'name' => 'Gezondheidszorg', 'icon' => 'fas fa-heartbeat', 'color' => '#E91E63'],
            ['slug' => 'education', 'name' => 'Onderwijs', 'icon' => 'fas fa-graduation-cap', 'color' => '#3F51B5'],
            ['slug' => 'construction', 'name' => 'Bouw', 'icon' => 'fas fa-hard-hat', 'color' => '#795548'],
            ['slug' => 'hospitality', 'name' => 'Horeca', 'icon' => 'fas fa-hotel', 'color' => '#FF5722'],
            ['slug' => 'legal', 'name' => 'Juridisch', 'icon' => 'fas fa-balance-scale', 'color' => '#673AB7'],
            ['slug' => 'real-estate', 'name' => 'Vastgoed', 'icon' => 'fas fa-home', 'color' => '#8BC34A'],
            ['slug' => 'retail', 'name' => 'Detailhandel', 'icon' => 'fas fa-shopping-cart', 'color' => '#00BCD4'],
            ['slug' => 'travel-tourism', 'name' => 'Reizen & toerisme', 'icon' => 'fas fa-plane', 'color' => '#009688'],
            ['slug' => 'transportation', 'name' => 'Transport', 'icon' => 'fas fa-truck', 'color' => '#FFC107'],
            ['slug' => 'manufacturing', 'name' => 'Productie', 'icon' => 'fas fa-industry', 'color' => '#795548'],
            ['slug' => 'arts', 'name' => 'Kunst & cultuur', 'icon' => 'fas fa-palette', 'color' => '#E91E63'],
            ['slug' => 'science', 'name' => 'Wetenschap', 'icon' => 'fas fa-flask', 'color' => '#673AB7'],
            ['slug' => 'government', 'name' => 'Overheid', 'icon' => 'fas fa-landmark', 'color' => '#607D8B'],
            ['slug' => 'non-profit', 'name' => 'Non-profit', 'icon' => 'fas fa-heart', 'color' => '#F44336'],
            ['slug' => 'accounting', 'name' => 'Boekhouding', 'icon' => 'fas fa-calculator', 'color' => '#4CAF50'],
            ['slug' => 'advertising', 'name' => 'Reclame', 'icon' => 'fas fa-ad', 'color' => '#FF9800'],
            ['slug' => 'agriculture', 'name' => 'Landbouw', 'icon' => 'fas fa-seedling', 'color' => '#8BC34A'],
            ['slug' => 'automotive', 'name' => 'Automotive', 'icon' => 'fas fa-car', 'color' => '#FF5722'],
            ['slug' => 'biotechnology', 'name' => 'Biotechnologie', 'icon' => 'fas fa-dna', 'color' => '#9C27B0'],
            ['slug' => 'consulting', 'name' => 'Consultancy', 'icon' => 'fas fa-lightbulb', 'color' => '#FFC107'],
            ['slug' => 'sports', 'name' => 'Sport', 'icon' => 'fas fa-futbol', 'color' => '#4CAF50'],
            ['slug' => 'energy', 'name' => 'Energie', 'icon' => 'fas fa-bolt', 'color' => '#FFC107'],
            ['slug' => 'entertainment', 'name' => 'Entertainment', 'icon' => 'fas fa-film', 'color' => '#E91E63'],
            ['slug' => 'environmental', 'name' => 'Milieu & duurzaamheid', 'icon' => 'fas fa-leaf', 'color' => '#4CAF50'],
            ['slug' => 'fashion', 'name' => 'Mode', 'icon' => 'fas fa-tshirt', 'color' => '#E91E63'],
            ['slug' => 'food-beverage', 'name' => 'Food & beverage', 'icon' => 'fas fa-utensils', 'color' => '#FF9800'],
            ['slug' => 'gaming', 'name' => 'Gaming', 'icon' => 'fas fa-gamepad', 'color' => '#9C27B0'],
            ['slug' => 'insurance', 'name' => 'Verzekeringen', 'icon' => 'fas fa-shield-alt', 'color' => '#2196F3'],
            ['slug' => 'media', 'name' => 'Media', 'icon' => 'fas fa-newspaper', 'color' => '#607D8B'],
            ['slug' => 'pharmaceuticals', 'name' => 'Farmacie', 'icon' => 'fas fa-pills', 'color' => '#E91E63'],
            ['slug' => 'public-relations', 'name' => 'Public relations', 'icon' => 'fas fa-comments', 'color' => '#FF9800'],
            ['slug' => 'research-development', 'name' => 'Research & development', 'icon' => 'fas fa-microscope', 'color' => '#673AB7'],
            ['slug' => 'security', 'name' => 'Beveiliging', 'icon' => 'fas fa-lock', 'color' => '#795548'],
            ['slug' => 'telecommunications', 'name' => 'Telecommunicatie', 'icon' => 'fas fa-phone', 'color' => '#2196F3'],
            ['slug' => 'taxi', 'name' => 'Taxi', 'icon' => 'fas fa-taxi', 'color' => '#FFC107'],
        ];

        foreach ($categories as $index => $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'slug' => $category['slug'],
                    'description' => 'Vacatures in de sector: '.$category['name'],
                    'icon' => $category['icon'],
                    'color' => $category['color'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }

        $this->command->info('Categories seeded successfully!');
    }
}
