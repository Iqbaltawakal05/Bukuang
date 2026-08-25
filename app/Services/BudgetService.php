<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class BudgetService
{
    /**
     * Warning threshold percentage (configurable).
     */
    protected float $warningThreshold = 80.0;

    /**
     * Get budgets for a user, optionally filtered by month/year.
     */
    public function getBudgetsForUser(User $user, array $filters = []): Collection
    {
        $query = Budget::with('category')
            ->where('user_id', $user->id);

        if (! empty($filters['month'])) {
            $query->where('month', $filters['month']);
        }

        if (! empty($filters['year'])) {
            $query->where('year', $filters['year']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        return $query->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();
    }

    /**
     * Create a new budget.
     */
    public function createBudget(User $user, array $data): Budget
    {
        return Budget::create([
            'user_id' => $user->id,
            'category_id' => $data['category_id'],
            'amount' => $data['amount'],
            'month' => $data['month'],
            'year' => $data['year'],
        ]);
    }

    /**
     * Update an existing budget.
     */
    public function updateBudget(Budget $budget, array $data): Budget
    {
        $budget->update([
            'category_id' => $data['category_id'],
            'amount' => $data['amount'],
            'month' => $data['month'],
            'year' => $data['year'],
        ]);

        return $budget->fresh('category');
    }

    /**
     * Delete a budget.
     */
    public function deleteBudget(Budget $budget): void
    {
        $budget->delete();
    }

    /**
     * Calculate total spent amount for a budget's category in the budget's month/year.
     * Only counts 'expense' transactions.
     */
    public function calculateSpent(Budget $budget): float
    {
        return (float) Transaction::where('user_id', $budget->user_id)
            ->where('category_id', $budget->category_id)
            ->where('type', 'expense')
            ->whereYear('transaction_date', $budget->year)
            ->whereMonth('transaction_date', $budget->month)
            ->sum('amount');
    }

    /**
     * Get budget summary with calculated fields: spent, remaining, percentage, status.
     */
    public function getBudgetSummary(Budget $budget): array
    {
        $spent = $this->calculateSpent($budget);
        $amount = (float) $budget->amount;
        $remaining = $amount - $spent;
        $percentage = $amount > 0 ? round(($spent / $amount) * 100, 2) : 0;

        $status = 'safe';
        if ($percentage >= 100) {
            $status = 'exceeded';
        } elseif ($percentage >= $this->warningThreshold) {
            $status = 'warning';
        }

        return [
            'spent' => $spent,
            'remaining' => $remaining,
            'percentage' => $percentage,
            'status' => $status,
        ];
    }
}
