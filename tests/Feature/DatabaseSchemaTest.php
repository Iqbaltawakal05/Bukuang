<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that all expected tables exist in the database.
     */
    public function test_all_expected_tables_exist(): void
    {
        $expectedTables = [
            'users',
            'categories',
            'recurring_transactions',
            'transactions',
            'budgets',
            'financial_goals',
            'goal_contributions',
            'exports',
        ];

        foreach ($expectedTables as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Table [{$table}] does not exist in the database."
            );
        }
    }

    /**
     * Test default categories are properly seeded.
     */
    public function test_default_categories_are_seeded(): void
    {
        $this->seed(\Database\Seeders\CategorySeeder::class);

        $this->assertDatabaseHas('categories', [
            'name' => 'Food & Dining',
            'type' => 'expense',
            'user_id' => null,
        ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Salary',
            'type' => 'income',
            'user_id' => null,
        ]);
    }
}
