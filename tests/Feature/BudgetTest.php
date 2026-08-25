<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CategorySeeder::class);
    }

    public function test_user_can_create_budget(): void
    {
        $user = User::factory()->create();
        $category = Category::where('type', 'expense')->first();

        $payload = [
            'category_id' => $category->id,
            'amount' => 1500000,
            'month' => 8,
            'year' => 2026,
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/budgets', $payload);

        $response->assertCreated()
            ->assertJson([
                'message' => 'Budget berhasil dibuat.',
                'data' => [
                    'amount' => 1500000,
                    'month' => 8,
                    'year' => 2026,
                    'spent' => 0,
                    'remaining' => 1500000,
                    'percentage' => 0,
                    'status' => 'safe',
                ],
            ]);

        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1500000,
        ]);
    }

    public function test_user_cannot_create_duplicate_budget(): void
    {
        $user = User::factory()->create();
        $category = Category::where('type', 'expense')->first();

        Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1500000,
            'month' => 8,
            'year' => 2026,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/budgets', [
                'category_id' => $category->id,
                'amount' => 2000000,
                'month' => 8,
                'year' => 2026,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');
    }


    public function test_user_can_list_and_filter_own_budgets(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::where('type', 'expense')->first();

        Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1500000,
            'month' => 8,
            'year' => 2026,
        ]);

        Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 2000000,
            'month' => 9,
            'year' => 2026,
        ]);

        Budget::create([
            'user_id' => $otherUser->id,
            'category_id' => $category->id,
            'amount' => 500000,
            'month' => 8,
            'year' => 2026,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/budgets');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/budgets?month=8&year=2026');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(1500000, $response->json('data.0.amount'));
    }

    public function test_user_cannot_view_or_update_or_delete_other_users_budget(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::first();

        $budget = Budget::create([
            'user_id' => $otherUser->id,
            'category_id' => $category->id,
            'amount' => 1000000,
            'month' => 8,
            'year' => 2026,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/budgets/{$budget->id}")
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/budgets/{$budget->id}", [
                'category_id' => $category->id,
                'amount' => 9999999,
                'month' => 8,
                'year' => 2026,
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/budgets/{$budget->id}")
            ->assertForbidden();
    }

    public function test_user_can_update_and_delete_own_budget(): void
    {
        $user = User::factory()->create();
        $category = Category::first();

        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1000000,
            'month' => 8,
            'year' => 2026,
        ]);

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/budgets/{$budget->id}", [
                'category_id' => $category->id,
                'amount' => 2000000,
                'month' => 8,
                'year' => 2026,
            ])
            ->assertOk()
            ->assertJsonPath('data.amount', 2000000);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/budgets/{$budget->id}")
            ->assertOk();

        $this->assertSoftDeleted('budgets', ['id' => $budget->id]);
    }

    public function test_budget_calculates_spent_remaining_percentage_and_status(): void
    {
        $user = User::factory()->create();
        $category = Category::where('type', 'expense')->first();

        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1000000,
            'month' => 8,
            'year' => 2026,
        ]);

        // Expense transactions in same category & month
        Transaction::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 400000,
            'transaction_date' => '2026-08-10',
            'description' => 'Makan siang',
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 450000,
            'transaction_date' => '2026-08-20',
            'description' => 'Makan malam',
        ]);

        // Income should NOT count toward spent
        Transaction::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'type' => 'income',
            'amount' => 500000,
            'transaction_date' => '2026-08-15',
            'description' => 'Refund',
        ]);

        // Different month should NOT count
        Transaction::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 100000,
            'transaction_date' => '2026-09-01',
            'description' => 'Bulan berikutnya',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/budgets/{$budget->id}");

        $response->assertOk()
            ->assertJsonPath('data.amount', 1000000)
            ->assertJsonPath('data.spent', 850000)
            ->assertJsonPath('data.remaining', 150000)
            ->assertJsonPath('data.percentage', 85)
            ->assertJsonPath('data.status', 'warning');
    }

    public function test_budget_status_exceeded_when_over_budget(): void
    {
        $user = User::factory()->create();
        $category = Category::where('type', 'expense')->first();

        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 500000,
            'month' => 8,
            'year' => 2026,
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 600000,
            'transaction_date' => '2026-08-15',
            'description' => 'Belanja besar',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/budgets/{$budget->id}");

        $response->assertOk()
            ->assertJsonPath('data.spent', 600000)
            ->assertJsonPath('data.remaining', -100000)
            ->assertJsonPath('data.percentage', 120)
            ->assertJsonPath('data.status', 'exceeded');
    }
}

