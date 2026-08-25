<?php

namespace Tests\Feature;

use App\Models\FinancialGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class GoalContributionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected FinancialGoal $goal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $this->goal = FinancialGoal::create([
            'user_id' => $this->user->id,
            'name' => 'Beli Laptop',
            'target_amount' => 10000000,
            'current_amount' => 2000000,
            'target_date' => '2026-12-31',
            'description' => 'Target laptop baru',
            'status' => 'active',
        ]);
    }

    public function test_user_can_add_contribution_to_own_financial_goal(): void
    {
        $payload = [
            'amount' => 3000000,
            'contribution_date' => '2026-08-25',
            'notes' => 'Setoran bonus bulanan',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/financial-goals/{$this->goal->id}/contributions", $payload);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonPath('message', 'Setoran target keuangan berhasil ditambahkan.')
            ->assertJsonPath('data.contribution.amount', 3000000)
            ->assertJsonPath('data.financial_goal.current_amount', 5000000)
            ->assertJsonPath('data.financial_goal.remaining', 5000000)
            ->assertJsonPath('data.financial_goal.percentage', 50)
            ->assertJsonPath('data.financial_goal.status', 'active');

        $this->assertDatabaseHas('goal_contributions', [
            'financial_goal_id' => $this->goal->id,
            'user_id' => $this->user->id,
            'amount' => 3000000,
            'notes' => 'Setoran bonus bulanan',
        ]);

        $this->assertDatabaseHas('financial_goals', [
            'id' => $this->goal->id,
            'current_amount' => 5000000,
        ]);
    }

    public function test_adding_contribution_automatically_completes_goal_when_target_reached(): void
    {
        $payload = [
            'amount' => 8000000,
            'contribution_date' => '2026-08-25',
            'notes' => 'Pelunasan setoran akhir',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/financial-goals/{$this->goal->id}/contributions", $payload);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonPath('data.financial_goal.current_amount', 10000000)
            ->assertJsonPath('data.financial_goal.remaining', 0)
            ->assertJsonPath('data.financial_goal.percentage', 100)
            ->assertJsonPath('data.financial_goal.status', 'completed');

        $this->assertDatabaseHas('financial_goals', [
            'id' => $this->goal->id,
            'status' => 'completed',
        ]);
    }

    public function test_user_cannot_add_contribution_to_other_users_financial_goal(): void
    {
        $payload = [
            'amount' => 1000000,
            'contribution_date' => '2026-08-25',
        ];

        $response = $this->actingAs($this->otherUser)
            ->postJson("/api/v1/financial-goals/{$this->goal->id}/contributions", $payload);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_adding_contribution_fails_with_invalid_amount(): void
    {
        $payload = [
            'amount' => -50000,
            'contribution_date' => '2026-08-25',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/financial-goals/{$this->goal->id}/contributions", $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['amount']);
    }
}
