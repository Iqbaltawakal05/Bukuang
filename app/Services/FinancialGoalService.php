<?php

namespace App\Services;

use App\Models\FinancialGoal;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class FinancialGoalService
{
    /**
     * Get financial goals for a user, optionally filtered by status.
     */
    public function getFinancialGoalsForUser(User $user, array $filters = []): Collection
    {
        $query = FinancialGoal::where('user_id', $user->id);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('target_date', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Create a new financial goal.
     */
    public function createFinancialGoal(User $user, array $data): FinancialGoal
    {
        return FinancialGoal::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'target_amount' => $data['target_amount'],
            'current_amount' => $data['current_amount'] ?? 0,
            'target_date' => $data['target_date'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);
    }

    /**
     * Update an existing financial goal.
     */
    public function updateFinancialGoal(FinancialGoal $financialGoal, array $data): FinancialGoal
    {
        $financialGoal->update([
            'name' => $data['name'],
            'target_amount' => $data['target_amount'],
            'current_amount' => $data['current_amount'],
            'target_date' => $data['target_date'],
            'description' => $data['description'] ?? $financialGoal->description,
            'status' => $data['status'],
        ]);

        return $financialGoal->fresh();
    }

    /**
     * Delete a financial goal.
     */
    public function deleteFinancialGoal(FinancialGoal $financialGoal): void
    {
        $financialGoal->delete();
    }

    /**
     * Get financial goal summary with calculated fields: remaining, percentage.
     */
    public function getFinancialGoalSummary(FinancialGoal $financialGoal): array
    {
        $targetAmount = (float) $financialGoal->target_amount;
        $currentAmount = (float) $financialGoal->current_amount;
        $remaining = $targetAmount - $currentAmount;
        $percentage = $targetAmount > 0 ? round(($currentAmount / $targetAmount) * 100, 2) : 0;

        return [
            'remaining' => $remaining,
            'percentage' => $percentage,
        ];
    }

    /**
     * Add a contribution/setoran to a financial goal.
     */
    public function addContribution(FinancialGoal $financialGoal, User $user, array $data): \App\Models\GoalContribution
    {
        $contribution = \App\Models\GoalContribution::create([
            'financial_goal_id' => $financialGoal->id,
            'user_id' => $user->id,
            'amount' => $data['amount'],
            'contribution_date' => $data['contribution_date'],
            'notes' => $data['notes'] ?? null,
        ]);

        $newCurrentAmount = (float) $financialGoal->current_amount + (float) $data['amount'];
        $updateData = ['current_amount' => $newCurrentAmount];

        if ($newCurrentAmount >= (float) $financialGoal->target_amount && $financialGoal->status === 'active') {
            $updateData['status'] = 'completed';
        }

        $financialGoal->update($updateData);

        return $contribution;
    }
}
