<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultCategories = [
            // Expense Categories
            ['name' => 'Food & Dining', 'type' => 'expense', 'icon' => 'utensils', 'color' => '#FF5733'],
            ['name' => 'Transportation', 'type' => 'expense', 'icon' => 'car', 'color' => '#3357FF'],
            ['name' => 'Shopping', 'type' => 'expense', 'icon' => 'shopping-bag', 'color' => '#FF33A8'],
            ['name' => 'Entertainment', 'type' => 'expense', 'icon' => 'film', 'color' => '#9333FF'],
            ['name' => 'Bills & Utilities', 'type' => 'expense', 'icon' => 'file-text', 'color' => '#FF8C00'],
            ['name' => 'Education', 'type' => 'expense', 'icon' => 'book', 'color' => '#33FFF5'],
            ['name' => 'Health & Medical', 'type' => 'expense', 'icon' => 'activity', 'color' => '#33FF57'],
            ['name' => 'Savings & Goal Deposit', 'type' => 'expense', 'icon' => 'piggy-bank', 'color' => '#059669'],
            ['name' => 'Other Expense', 'type' => 'expense', 'icon' => 'more-horizontal', 'color' => '#808080'],

            // Income Categories
            ['name' => 'Salary', 'type' => 'income', 'icon' => 'briefcase', 'color' => '#2ECC71'],
            ['name' => 'Freelance', 'type' => 'income', 'icon' => 'laptop', 'color' => '#1ABC9C'],
            ['name' => 'Business', 'type' => 'income', 'icon' => 'trending-up', 'color' => '#3498DB'],
            ['name' => 'Investment', 'type' => 'income', 'icon' => 'pie-chart', 'color' => '#F1C40F'],
            ['name' => 'Other Income', 'type' => 'income', 'icon' => 'dollar-sign', 'color' => '#95A5A6'],
        ];

        $now = now();
        foreach ($defaultCategories as $category) {
            DB::table('categories')->updateOrInsert(
                [
                    'name' => $category['name'],
                    'type' => $category['type'],
                    'user_id' => null, // System default category
                ],
                [
                    'icon' => $category['icon'],
                    'color' => $category['color'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
