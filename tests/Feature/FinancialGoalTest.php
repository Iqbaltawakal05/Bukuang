<?php

namespace Tests\Feature;

use App\Models\FinancialGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialGoalTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_financial_goal(): void
    {
        $user = User::factory()->create();

        $payload = [
            'name' => 'Buy New Laptop',
            'target_amount' => 10000000,
            'current_amount' => 6500000,
            'target_date' => '2027-12-31',
            'description' => 'Saving for a new MacBook Pro',
            'status' => 'active',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/financial-goals', $payload);

        $response->assertCreated()
            ->assertJson([
                'message' => 'Target keuangan berhasil dibuat.',
                'data' => [
                    'name' => 'Buy New Laptop',
                    'target_amount' => 10000000,
                    'current_amount' => 6500000,
                    'remaining' => 3500000,
                    'percentage' => 65,
                    'status' => 'active',
                ],
            ]);

        $this->assertDatabaseHas('financial_goals', [
            'user_id' => $user->id,
            'name' => 'Buy New Laptop',
            'target_amount' => 10000000,
        ]);
    }

    public function test_user_can_create_goal_without_current_amount(): void
    {
        $user = User::factory()->create();

        $payload = [
            'name' => 'Emergency Fund',
            'target_amount' => 50000000,
            'target_date' => '2028-06-30',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/financial-goals', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.current_amount', 0)
            ->assertJsonPath('data.remaining', 50000000)
            ->assertJsonPath('data.percentage', 0);
    }

    public function test_user_can_list_and_filter_own_financial_goals(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        FinancialGoal::create([
            'user_id' => $user->id,
            'name' => 'Active Goal',
            'target_amount' => 5000000,
            'current_amount' => 2000000,
            'target_date' => '2027-12-31',
            'status' => 'active',
        ]);

        FinancialGoal::create([
            'user_id' => $user->id,
            'name' => 'Completed Goal',
            'target_amount' => 3000000,
            'current_amount' => 3000000,
            'target_date' => '2026-06-30',
            'status' => 'completed',
        ]);

        FinancialGoal::create([
            'user_id' => $otherUser->id,
            'name' => 'Other User Goal',
            'target_amount' => 1000000,
            'current_amount' => 500000,
            'target_date' => '2027-01-01',
            'status' => 'active',
        ]);

        // List all goals
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/financial-goals');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));

        // Filter by status
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/financial-goals?status=active');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Active Goal', $response->json('data.0.name'));
    }


    public function test_user_cannot_view_or_update_or_delete_other_users_goal(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $goal = FinancialGoal::create([
            'user_id' => $otherUser->id,
            'name' => 'Other Goal',
            'target_amount' => 5000000,
            'current_amount' => 1000000,
            'target_date' => '2027-12-31',
            'status' => 'active',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/financial-goals/{$goal->id}")
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/financial-goals/{$goal->id}", [
                'name' => 'Hacked Goal',
                'target_amount' => 9999999,
                'current_amount' => 0,
                'target_date' => '2027-12-31',
                'status' => 'active',
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/financial-goals/{$goal->id}")
            ->assertForbidden();
    }

    public function test_user_can_update_and_delete_own_goal(): void
    {
        $user = User::factory()->create();

        $goal = FinancialGoal::create([
            'user_id' => $user->id,
            'name' => 'Old Goal Name',
            'target_amount' => 5000000,
            'current_amount' => 1000000,
            'target_date' => '2027-12-31',
            'status' => 'active',
        ]);

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/financial-goals/{$goal->id}", [
                'name' => 'Updated Goal Name',
                'target_amount' => 8000000,
                'current_amount' => 3000000,
                'target_date' => '2028-06-30',
                'status' => 'active',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Goal Name')
            ->assertJsonPath('data.target_amount', 8000000)
            ->assertJsonPath('data.current_amount', 3000000);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/financial-goals/{$goal->id}")
            ->assertOk();

        $this->assertSoftDeleted('financial_goals', ['id' => $goal->id]);
    }

    public function test_goal_calculates_remaining_and_percentage(): void
    {
        $user = User::factory()->create();

        $goal = FinancialGoal::create([
            'user_id' => $user->id,
            'name' => 'Vacation Fund',
            'target_amount' => 20000000,
            'current_amount' => 15000000,
            'target_date' => '2027-07-01',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/financial-goals/{$goal->id}");

        $response->assertOk()
            ->assertJsonPath('data.target_amount', 20000000)
            ->assertJsonPath('data.current_amount', 15000000)
            ->assertJsonPath('data.remaining', 5000000)
            ->assertJsonPath('data.percentage', 75);
    }

    public function test_goal_percentage_is_100_when_target_reached(): void
    {
        $user = User::factory()->create();

        $goal = FinancialGoal::create([
            'user_id' => $user->id,
            'name' => 'Completed Goal',
            'target_amount' => 10000000,
            'current_amount' => 10000000,
            'target_date' => '2027-01-01',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/financial-goals/{$goal->id}");

        $response->assertOk()
            ->assertJsonPath('data.remaining', 0)
            ->assertJsonPath('data.percentage', 100)
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_user_can_update_goal_status_to_cancelled(): void
    {
        $user = User::factory()->create();

        $goal = FinancialGoal::create([
            'user_id' => $user->id,
            'name' => 'Cancelled Goal',
            'target_amount' => 5000000,
            'current_amount' => 2000000,
            'target_date' => '2027-12-31',
            'status' => 'active',
        ]);

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/financial-goals/{$goal->id}", [
                'name' => 'Cancelled Goal',
                'target_amount' => 5000000,
                'current_amount' => 2000000,
                'target_date' => '2027-12-31',
                'status' => 'cancelled',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }
}
