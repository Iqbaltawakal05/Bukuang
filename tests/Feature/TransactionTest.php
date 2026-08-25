<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CategorySeeder::class);
    }

    public function test_user_can_create_transaction(): void
    {
        $user = User::factory()->create();
        $category = Category::where('type', 'expense')->first();

        $payload = [
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 150000.50,
            'transaction_date' => '2026-08-25',
            'description' => 'Makan malam',
            'notes' => 'Restoran Sunda',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/transactions', $payload);

        $response->assertCreated()
            ->assertJson([
                'message' => 'Transaksi berhasil dicatat.',
                'data' => [
                    'amount' => 150000.50,
                    'type' => 'expense',
                    'description' => 'Makan malam',
                ],
            ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'amount' => 150000.50,
            'description' => 'Makan malam',
        ]);
    }

    public function test_user_can_list_and_filter_own_transactions(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::first();

        Transaction::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 50000,
            'transaction_date' => '2026-08-01',
            'description' => 'Beli buku',
        ]);

        Transaction::create([
            'user_id' => $otherUser->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 100000,
            'transaction_date' => '2026-08-02',
            'description' => 'Transaksi user lain',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions?search=buku');

        $response->assertOk()
            ->assertJsonFragment(['description' => 'Beli buku'])
            ->assertJsonMissing(['description' => 'Transaksi user lain']);
    }

    public function test_user_cannot_view_or_update_or_delete_other_users_transaction(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::first();

        $transaction = Transaction::create([
            'user_id' => $otherUser->id,
            'category_id' => $category->id,
            'type' => 'income',
            'amount' => 5000000,
            'transaction_date' => '2026-08-01',
            'description' => 'Gaji user lain',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/transactions/{$transaction->id}")
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/transactions/{$transaction->id}", [
                'category_id' => $category->id,
                'type' => 'income',
                'amount' => 1000,
                'transaction_date' => '2026-08-01',
            ])->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/transactions/{$transaction->id}")
            ->assertForbidden();
    }

    public function test_user_can_update_and_delete_own_transaction(): void
    {
        $user = User::factory()->create();
        $category = Category::first();

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 50000,
            'transaction_date' => '2026-08-01',
            'description' => 'Deskripsi lama',
        ]);

        $updatePayload = [
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 75000,
            'transaction_date' => '2026-08-01',
            'description' => 'Deskripsi baru',
        ];

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/transactions/{$transaction->id}", $updatePayload)
            ->assertOk()
            ->assertJsonPath('data.description', 'Deskripsi baru');

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/transactions/{$transaction->id}")
            ->assertOk();

        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
    }

    public function test_user_can_create_recurring_transaction(): void
    {
        $user = User::factory()->create();
        $category = Category::first();

        $payload = [
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 186000,
            'frequency' => 'monthly',
            'start_date' => '2026-08-10',
            'description' => 'Langganan Netflix',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/recurring-transactions', $payload);

        $response->assertCreated()
            ->assertJson([
                'message' => 'Transaksi berulang berhasil ditambahkan.',
                'data' => [
                    'amount' => 186000,
                    'frequency' => 'monthly',
                    'next_run_date' => '2026-08-10',
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('recurring_transactions', [
            'user_id' => $user->id,
            'description' => 'Langganan Netflix',
        ]);
    }

    public function test_scheduler_command_processes_due_recurring_transactions(): void
    {
        $user = User::factory()->create();
        $category = Category::first();

        $recurring = RecurringTransaction::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'type' => 'income',
            'amount' => 5000000,
            'frequency' => 'monthly',
            'start_date' => '2026-08-01',
            'next_run_date' => '2026-08-01',
            'description' => 'Gaji Bulanan',
            'is_active' => true,
        ]);

        $this->artisan('transactions:process-recurring')
            ->assertExitCode(0);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'recurring_transaction_id' => $recurring->id,
            'amount' => 5000000,
            'transaction_date' => '2026-08-01',
        ]);

        $this->assertDatabaseHas('recurring_transactions', [
            'id' => $recurring->id,
            'next_run_date' => '2026-09-01',
        ]);
    }
}
