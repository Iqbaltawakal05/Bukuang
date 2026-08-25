<?php

namespace App\Services;

use App\Http\Resources\TransactionResource;
use App\Models\Budget;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Get dashboard summary metrics for a user.
     */
    public function getSummary(User $user): array
    {
        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;

        // Overall Totals
        $totalIncome = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->sum('amount');

        $totalExpense = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->sum('amount');

        $totalBalance = $totalIncome - $totalExpense;
        $totalSavings = $totalBalance;

        // Current Month Totals
        $currentMonthIncome = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->whereYear('transaction_date', $currentYear)
            ->whereMonth('transaction_date', $currentMonth)
            ->sum('amount');

        $currentMonthExpense = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereYear('transaction_date', $currentYear)
            ->whereMonth('transaction_date', $currentMonth)
            ->sum('amount');

        $currentMonthSavings = $currentMonthIncome - $currentMonthExpense;

        // Budget Usage Summary
        $totalBudgetAmount = (float) Budget::where('user_id', $user->id)
            ->where('month', $currentMonth)
            ->where('year', $currentYear)
            ->sum('amount');

        $budgetedCategoryIds = Budget::where('user_id', $user->id)
            ->where('month', $currentMonth)
            ->where('year', $currentYear)
            ->pluck('category_id');

        $totalSpentAmount = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereIn('category_id', $budgetedCategoryIds)
            ->whereYear('transaction_date', $currentYear)
            ->whereMonth('transaction_date', $currentMonth)
            ->sum('amount');

        $remainingBudgetAmount = max(0, $totalBudgetAmount - $totalSpentAmount);
        $budgetPercentage = $totalBudgetAmount > 0
            ? round(($totalSpentAmount / $totalBudgetAmount) * 100, 2)
            : 0;

        // Recent 5 Transactions
        $recentTransactions = Transaction::with('category')
            ->where('user_id', $user->id)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        return [
            'total_balance' => round($totalBalance, 2),
            'total_income' => round($totalIncome, 2),
            'total_expense' => round($totalExpense, 2),
            'total_savings' => round($totalSavings, 2),
            'current_month_income' => round($currentMonthIncome, 2),
            'current_month_expense' => round($currentMonthExpense, 2),
            'current_month_savings' => round($currentMonthSavings, 2),
            'budget_usage_summary' => [
                'total_budget' => round($totalBudgetAmount, 2),
                'total_spent' => round($totalSpentAmount, 2),
                'remaining' => round($remainingBudgetAmount, 2),
                'percentage' => $budgetPercentage,
            ],
            'recent_transactions' => TransactionResource::collection($recentTransactions),
        ];
    }

    /**
     * Get chart statistics (Monthly Income vs Expense trend & Expense by Category).
     */
    public function getCharts(User $user): array
    {
        $now = Carbon::now();
        $incomeVsExpense = [];

        // Last 6 months trend
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $month = $date->month;
            $year = $date->year;

            $income = (float) Transaction::where('user_id', $user->id)
                ->where('type', 'income')
                ->whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $month)
                ->sum('amount');

            $expense = (float) Transaction::where('user_id', $user->id)
                ->where('type', 'expense')
                ->whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $month)
                ->sum('amount');

            $incomeVsExpense[] = [
                'month' => $date->format('Y-m'),
                'label' => $date->format('M Y'),
                'income' => round($income, 2),
                'expense' => round($expense, 2),
            ];
        }

        // Current Month Expense by Category
        $currentMonthExpenseTotal = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereYear('transaction_date', $now->year)
            ->whereMonth('transaction_date', $now->month)
            ->sum('amount');

        $categoryExpensesRaw = Transaction::with('category')
            ->selectRaw('category_id, SUM(amount) as total_amount')
            ->where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereYear('transaction_date', $now->year)
            ->whereMonth('transaction_date', $now->month)
            ->groupBy('category_id')
            ->orderByDesc('total_amount')
            ->get();

        $expenseByCategory = $categoryExpensesRaw->map(function ($item) use ($currentMonthExpenseTotal) {
            $amount = (float) $item->total_amount;
            $percentage = $currentMonthExpenseTotal > 0
                ? round(($amount / $currentMonthExpenseTotal) * 100, 2)
                : 0;

            return [
                'category_id' => $item->category_id,
                'category_name' => $item->category?->name ?? 'Uncategorized',
                'color' => $item->category?->color ?? '#999999',
                'icon' => $item->category?->icon ?? 'folder',
                'amount' => round($amount, 2),
                'percentage' => $percentage,
            ];
        });

        return [
            'income_vs_expense' => $incomeVsExpense,
            'expense_by_category' => $expenseByCategory,
        ];
    }
}
