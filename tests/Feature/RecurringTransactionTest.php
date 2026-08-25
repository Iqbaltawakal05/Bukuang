<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class RecurringTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $this->category = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Langganan',
            'type' => 'expense',
            'icon' => 'subscription',
            'color' => '#FF0000',
        ]);
    }

    public function test_user_can_create_recurring_transaction(): void
    {
        $payload = [
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 186000,
            'frequency' => 'monthly',
            'start_date' => '2026-08-01',
            'description' => 'Langganan Netflix Bulanan',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/recurring-transactions', $payload);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonPath('message', 'Transaksi berulang berhasil ditambahkan.')
            ->assertJsonPath('data.amount', 186000)
            ->assertJsonPath('data.frequency', 'monthly')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('recurring_transactions', [
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'amount' => 186000,
            'frequency' => 'monthly',
            'is_active' => true,
        ]);
    }

    public function test_user_can_list_own_recurring_transactions(): void
    {
        RecurringTransaction::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 100000,
            'frequency' => 'monthly',
            'start_date' => '2026-08-01',
            'next_run_date' => '2026-08-01',
            'description' => 'Spotify',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/recurring-transactions');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'Spotify');
    }

    public function test_user_can_update_own_recurring_transaction(): void
    {
        $recurring = RecurringTransaction::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 100000,
            'frequency' => 'monthly',
            'start_date' => '2026-08-01',
            'next_run_date' => '2026-08-01',
            'description' => 'Spotify',
            'is_active' => true,
        ]);

        $payload = [
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 150000,
            'frequency' => 'monthly',
            'start_date' => '2026-08-01',
            'description' => 'Spotify Family',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/recurring-transactions/{$recurring->id}", $payload);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.amount', 150000)
            ->assertJsonPath('data.description', 'Spotify Family');

        $this->assertDatabaseHas('recurring_transactions', [
            'id' => $recurring->id,
            'amount' => 150000,
            'description' => 'Spotify Family',
        ]);
    }

    public function test_user_can_delete_recurring_transaction(): void
    {
        $recurring = RecurringTransaction::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 100000,
            'frequency' => 'monthly',
            'start_date' => '2026-08-01',
            'next_run_date' => '2026-08-01',
            'description' => 'Spotify',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/recurring-transactions/{$recurring->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertSoftDeleted('recurring_transactions', ['id' => $recurring->id]);
    }

    public function test_user_cannot_access_other_users_recurring_transaction(): void
    {
        $recurring = RecurringTransaction::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 100000,
            'frequency' => 'monthly',
            'start_date' => '2026-08-01',
            'next_run_date' => '2026-08-01',
            'description' => 'Private Sub',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->otherUser)
            ->getJson("/api/v1/recurring-transactions/{$recurring->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_artisan_scheduler_command_processes_due_recurring_transactions(): void
    {
        $recurring = RecurringTransaction::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 500000,
            'frequency' => 'monthly',
            'start_date' => '2026-08-01',
            'next_run_date' => Carbon::yesterday()->format('Y-m-d'),
            'description' => 'Tagihan Listrik',
            'is_active' => true,
        ]);

        $this->artisan('transactions:process-recurring')
            ->expectsOutput('Processing due recurring transactions...')
            ->expectsOutput('Successfully processed 1 recurring transaction(s).')
            ->assertExitCode(0);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'recurring_transaction_id' => $recurring->id,
            'amount' => 500000,
            'description' => 'Tagihan Listrik',
        ]);

        $nextRunExpected = Carbon::yesterday()->addMonth()->format('Y-m-d');
        $this->assertDatabaseHas('recurring_transactions', [
            'id' => $recurring->id,
            'next_run_date' => $nextRunExpected,
        ]);
    }
}
