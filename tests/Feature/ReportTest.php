<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ReportTest extends TestCase
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

    public function test_user_can_get_monthly_summary_report(): void
    {
        $today = Carbon::today()->format('Y-m-d');

        Transaction::create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryIncome->id,
            'type' => 'income',
            'amount' => 15000000,
            'transaction_date' => $today,
            'description' => 'Gaji Pokok',
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryExpense->id,
            'type' => 'expense',
            'amount' => 3000000,
            'transaction_date' => $today,
            'description' => 'Sewa APARTEMEN & Makan',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/reports/summary?period=monthly');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.period_info.period', 'monthly')
            ->assertJsonPath('data.total_income', 15000000)
            ->assertJsonPath('data.total_expense', 3000000)
            ->assertJsonPath('data.net_balance', 12000000)
            ->assertJsonPath('data.transaction_count', 2)
            ->assertJsonCount(2, 'data.category_breakdown')
            ->assertJsonCount(2, 'data.transactions');
    }

    public function test_user_can_filter_report_by_custom_date_range_and_type(): void
    {
        $startDate = '2026-08-01';
        $endDate = '2026-08-15';

        Transaction::create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryExpense->id,
            'type' => 'expense',
            'amount' => 500000,
            'transaction_date' => '2026-08-05',
            'description' => 'Makan Siang',
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryExpense->id,
            'type' => 'expense',
            'amount' => 700000,
            'transaction_date' => '2026-08-25', // Outside range
            'description' => 'Makan Malam Akhir Bulan',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/reports/summary?period=custom&start_date={$startDate}&end_date={$endDate}&type=expense");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.period_info.period', 'custom')
            ->assertJsonPath('data.total_expense', 500000)
            ->assertJsonPath('data.transaction_count', 1);
    }
}
