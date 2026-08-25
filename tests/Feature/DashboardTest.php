<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Category $categoryIncome;
    protected Category $categoryExpense;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $this->categoryIncome = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Salary',
            'type' => 'income',
            'icon' => 'money',
            'color' => '#00FF00',
        ]);

        $this->categoryExpense = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Food',
            'type' => 'expense',
            'icon' => 'food',
            'color' => '#FF0000',
        ]);
    }

    public function test_user_can_get_dashboard_summary(): void
    {
        $now = Carbon::now();

        // Income transaction current month
        Transaction::create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryIncome->id,
            'type' => 'income',
            'amount' => 10000000,
            'transaction_date' => $now->format('Y-m-d'),
            'description' => 'Gaji Bulan Ini',
        ]);

        // Expense transaction current month
        Transaction::create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryExpense->id,
            'type' => 'expense',
            'amount' => 2000000,
            'transaction_date' => $now->format('Y-m-d'),
            'description' => 'Makan Resto',
        ]);

        // Budget for current month
        Budget::create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryExpense->id,
            'amount' => 5000000,
            'month' => $now->month,
            'year' => $now->year,
        ]);

        // Other user's transaction (should be ignored)
        Transaction::create([
            'user_id' => $this->otherUser->id,
            'category_id' => $this->categoryIncome->id,
            'type' => 'income',
            'amount' => 50000000,
            'transaction_date' => $now->format('Y-m-d'),
            'description' => 'Gaji Other User',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/dashboard/summary');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.total_balance', 8000000)
            ->assertJsonPath('data.total_income', 10000000)
            ->assertJsonPath('data.total_expense', 2000000)
            ->assertJsonPath('data.current_month_income', 10000000)
            ->assertJsonPath('data.current_month_expense', 2000000)
            ->assertJsonPath('data.budget_usage_summary.total_budget', 5000000)
            ->assertJsonPath('data.budget_usage_summary.total_spent', 2000000)
            ->assertJsonPath('data.budget_usage_summary.remaining', 3000000)
            ->assertJsonPath('data.budget_usage_summary.percentage', 40)
            ->assertJsonCount(2, 'data.recent_transactions');
    }

    public function test_user_can_get_dashboard_charts(): void
    {
        $now = Carbon::now();

        Transaction::create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryIncome->id,
            'type' => 'income',
            'amount' => 5000000,
            'transaction_date' => $now->format('Y-m-d'),
            'description' => 'Bonus',
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryExpense->id,
            'type' => 'expense',
            'amount' => 1000000,
            'transaction_date' => $now->format('Y-m-d'),
            'description' => 'Belanja Supermarket',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/dashboard/charts');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(6, 'data.income_vs_expense')
            ->assertJsonCount(1, 'data.expense_by_category')
            ->assertJsonPath('data.expense_by_category.0.category_name', 'Food')
            ->assertJsonPath('data.expense_by_category.0.amount', 1000000)
            ->assertJsonPath('data.expense_by_category.0.percentage', 100);
    }
}
