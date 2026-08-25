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
            // Standard Default Expense Categories (Required by Tests)
            ['name' => 'Food & Dining', 'type' => 'expense', 'icon' => 'utensils', 'color' => '#FF5733'],
            ['name' => 'Transportation', 'type' => 'expense', 'icon' => 'car', 'color' => '#3357FF'],
            ['name' => 'Shopping', 'type' => 'expense', 'icon' => 'shopping-bag', 'color' => '#FF33A8'],
            ['name' => 'Entertainment', 'type' => 'expense', 'icon' => 'film', 'color' => '#9333FF'],
            ['name' => 'Bills & Utilities', 'type' => 'expense', 'icon' => 'file-text', 'color' => '#FF8C00'],
            ['name' => 'Education', 'type' => 'expense', 'icon' => 'book', 'color' => '#33FFF5'],
            ['name' => 'Health & Medical', 'type' => 'expense', 'icon' => 'activity', 'color' => '#33FF57'],
            ['name' => 'Savings & Goal Deposit', 'type' => 'expense', 'icon' => 'piggy-bank', 'color' => '#059669'],
            ['name' => 'Other Expense', 'type' => 'expense', 'icon' => 'more-horizontal', 'color' => '#808080'],

            // Kategori Pengeluaran Khusus Anak Kost & Kuliah (Indonesian)
            ['name' => 'Makan & Minum Kost', 'type' => 'expense', 'icon' => 'utensils', 'color' => '#FF5733'],
            ['name' => 'Sewa Kost & Listrik', 'type' => 'expense', 'icon' => 'home', 'color' => '#E67E22'],
            ['name' => 'Kuliah, Buku & Fotokopi', 'type' => 'expense', 'icon' => 'book', 'color' => '#9B59B6'],
            ['name' => 'Transportasi & Bensin Kost', 'type' => 'expense', 'icon' => 'car', 'color' => '#3498DB'],
            ['name' => 'Internet & Pulsa Data', 'type' => 'expense', 'icon' => 'wifi', 'color' => '#2ECC71'],
            ['name' => 'Laundry & Kebersihan', 'type' => 'expense', 'icon' => 'sparkles', 'color' => '#1ABC9C'],
            ['name' => 'Nongkrong & Jajan Cafe', 'type' => 'expense', 'icon' => 'coffee', 'color' => '#F1C40F'],
            ['name' => 'Kebutuhan Mandi & Harian', 'type' => 'expense', 'icon' => 'shopping-bag', 'color' => '#E84393'],
            ['name' => 'Hiburan & Streaming', 'type' => 'expense', 'icon' => 'film', 'color' => '#E74C3C'],

            // Standard Default Income Categories (Required by Tests)
            ['name' => 'Salary', 'type' => 'income', 'icon' => 'briefcase', 'color' => '#2ECC71'],
            ['name' => 'Freelance', 'type' => 'income', 'icon' => 'laptop', 'color' => '#1ABC9C'],
            ['name' => 'Business', 'type' => 'income', 'icon' => 'trending-up', 'color' => '#3498DB'],
            ['name' => 'Investment', 'type' => 'income', 'icon' => 'pie-chart', 'color' => '#F1C40F'],
            ['name' => 'Other Income', 'type' => 'income', 'icon' => 'dollar-sign', 'color' => '#95A5A6'],

            // Kategori Pemasukan Khusus Anak Kost & Kuliah (Indonesian)
            ['name' => 'Uang Saku Orang Tua', 'type' => 'income', 'icon' => 'heart', 'color' => '#2ECC71'],
            ['name' => 'Gaji Part-Time / Magang', 'type' => 'income', 'icon' => 'briefcase', 'color' => '#3498DB'],
            ['name' => 'Beasiswa & Bantuan Kampus', 'type' => 'income', 'icon' => 'award', 'color' => '#9B59B6'],
            ['name' => 'Bonus & Hadiah', 'type' => 'income', 'icon' => 'gift', 'color' => '#F1C40F'],
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
