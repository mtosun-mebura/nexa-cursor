<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'IT', 'icon' => 'fas fa-laptop-code', 'color' => '#2196F3'],
            ['name' => 'Marketing', 'icon' => 'fas fa-bullhorn', 'color' => '#FF9800'],
            ['name' => 'Finance', 'icon' => 'fas fa-chart-line', 'color' => '#4CAF50'],
            ['name' => 'HR', 'icon' => 'fas fa-users', 'color' => '#9C27B0'],
            ['name' => 'Sales', 'icon' => 'fas fa-handshake', 'color' => '#F44336'],
            ['name' => 'Engineering', 'icon' => 'fas fa-cogs', 'color' => '#607D8B'],
            ['name' => 'Healthcare', 'icon' => 'fas fa-heartbeat', 'color' => '#E91E63'],
            ['name' => 'Education', 'icon' => 'fas fa-graduation-cap', 'color' => '#3F51B5'],
            ['name' => 'Construction', 'icon' => 'fas fa-hard-hat', 'color' => '#795548'],
            ['name' => 'Hospitality', 'icon' => 'fas fa-hotel', 'color' => '#FF5722'],
            ['name' => 'Legal', 'icon' => 'fas fa-balance-scale', 'color' => '#673AB7'],
            ['name' => 'Real Estate', 'icon' => 'fas fa-home', 'color' => '#8BC34A'],
            ['name' => 'Retail', 'icon' => 'fas fa-shopping-cart', 'color' => '#00BCD4'],
            ['name' => 'Travel & Tourism', 'icon' => 'fas fa-plane', 'color' => '#009688'],
            ['name' => 'Transportation', 'icon' => 'fas fa-truck', 'color' => '#FFC107'],
            ['name' => 'Manufacturing', 'icon' => 'fas fa-industry', 'color' => '#795548'],
            ['name' => 'Arts', 'icon' => 'fas fa-palette', 'color' => '#E91E63'],
            ['name' => 'Science', 'icon' => 'fas fa-flask', 'color' => '#673AB7'],
            ['name' => 'Government', 'icon' => 'fas fa-landmark', 'color' => '#607D8B'],
            ['name' => 'Non-Profit', 'icon' => 'fas fa-heart', 'color' => '#F44336'],
            ['name' => 'Accounting', 'icon' => 'fas fa-calculator', 'color' => '#4CAF50'],
            ['name' => 'Advertising', 'icon' => 'fas fa-ad', 'color' => '#FF9800'],
            ['name' => 'Agriculture', 'icon' => 'fas fa-seedling', 'color' => '#8BC34A'],
            ['name' => 'Automotive', 'icon' => 'fas fa-car', 'color' => '#FF5722'],
            ['name' => 'Biotechnology', 'icon' => 'fas fa-dna', 'color' => '#9C27B0'],
            ['name' => 'Consulting', 'icon' => 'fas fa-lightbulb', 'color' => '#FFC107'],
            ['name' => 'Sports', 'icon' => 'fas fa-futbol', 'color' => '#4CAF50'],
            ['name' => 'Energy', 'icon' => 'fas fa-bolt', 'color' => '#FFC107'],
            ['name' => 'Entertainment', 'icon' => 'fas fa-film', 'color' => '#E91E63'],
            ['name' => 'Environmental', 'icon' => 'fas fa-leaf', 'color' => '#4CAF50'],
            ['name' => 'Fashion', 'icon' => 'fas fa-tshirt', 'color' => '#E91E63'],
            ['name' => 'Food & Beverage', 'icon' => 'fas fa-utensils', 'color' => '#FF9800'],
            ['name' => 'Gaming', 'icon' => 'fas fa-gamepad', 'color' => '#9C27B0'],
            ['name' => 'Insurance', 'icon' => 'fas fa-shield-alt', 'color' => '#2196F3'],
            ['name' => 'Media', 'icon' => 'fas fa-newspaper', 'color' => '#607D8B'],
            ['name' => 'Pharmaceuticals', 'icon' => 'fas fa-pills', 'color' => '#E91E63'],
            ['name' => 'Public Relations', 'icon' => 'fas fa-comments', 'color' => '#FF9800'],
            ['name' => 'Research & Development', 'icon' => 'fas fa-microscope', 'color' => '#673AB7'],
            ['name' => 'Security', 'icon' => 'fas fa-lock', 'color' => '#795548'],
            ['name' => 'Telecommunications', 'icon' => 'fas fa-phone', 'color' => '#2196F3'],
        ];

        foreach ($categories as $index => $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                [
                    'name' => $category['name'],
                    'slug' => Str::slug($category['name']),
                    'description' => 'Vacatures in de ' . $category['name'] . ' sector',
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
