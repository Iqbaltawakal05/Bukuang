<?php

namespace App\Services;

use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class ReportService
{
    /**
     * Get summary report for a user based on period and filters.
     */
    public function getSummaryReport(User $user, array $filters = []): array
    {
        $period = $filters['period'] ?? 'monthly';
        $now = Carbon::now();

        switch ($period) {
            case 'daily':
                $startDate = !empty($filters['start_date'])
                    ? Carbon::parse($filters['start_date'])->startOfDay()
                    : $now->copy()->startOfDay();
                $endDate = !empty($filters['end_date'])
                    ? Carbon::parse($filters['end_date'])->endOfDay()
                    : $now->copy()->endOfDay();
                break;

            case 'weekly':
                $startDate = !empty($filters['start_date'])
                    ? Carbon::parse($filters['start_date'])->startOfWeek()
                    : $now->copy()->startOfWeek();
                $endDate = !empty($filters['end_date'])
                    ? Carbon::parse($filters['end_date'])->endOfWeek()
                    : $now->copy()->endOfWeek();
                break;

            case 'yearly':
                $startDate = !empty($filters['start_date'])
                    ? Carbon::parse($filters['start_date'])->startOfYear()
                    : $now->copy()->startOfYear();
                $endDate = !empty($filters['end_date'])
                    ? Carbon::parse($filters['end_date'])->endOfYear()
                    : $now->copy()->endOfYear();
                break;

            case 'custom':
                $startDate = !empty($filters['start_date'])
                    ? Carbon::parse($filters['start_date'])->startOfDay()
                    : $now->copy()->startOfMonth();
                $endDate = !empty($filters['end_date'])
                    ? Carbon::parse($filters['end_date'])->endOfDay()
                    : $now->copy()->endOfMonth();
                break;

            case 'monthly':
            default:
                $startDate = !empty($filters['start_date'])
                    ? Carbon::parse($filters['start_date'])->startOfMonth()
                    : $now->copy()->startOfMonth();
                $endDate = !empty($filters['end_date'])
                    ? Carbon::parse($filters['end_date'])->endOfMonth()
                    : $now->copy()->endOfMonth();
                break;
        }

        // Base Query
        $baseQuery = Transaction::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        if (!empty($filters['category_id'])) {
            $baseQuery->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['type'])) {
            $baseQuery->where('type', $filters['type']);
        }

        // Totals
        $totalIncome = (float) (clone $baseQuery)->where('type', 'income')->sum('amount');
        $totalExpense = (float) (clone $baseQuery)->where('type', 'expense')->sum('amount');
        $netBalance = $totalIncome - $totalExpense;
        $transactionCount = (clone $baseQuery)->count();

        // Category Breakdown
        $categorySummaryRaw = (clone $baseQuery)
            ->with('category')
            ->selectRaw('category_id, type, COUNT(id) as count, SUM(amount) as total_amount')
            ->groupBy('category_id', 'type')
            ->orderByDesc('total_amount')
            ->get();

        $categoryBreakdown = $categorySummaryRaw->map(function ($item) use ($totalIncome, $totalExpense) {
            $amount = (float) $item->total_amount;
            $typeTotal = $item->type === 'income' ? $totalIncome : $totalExpense;
            $percentage = $typeTotal > 0 ? round(($amount / $typeTotal) * 100, 2) : 0;

            return [
                'category_id' => $item->category_id,
                'category_name' => $item->category?->name ?? 'Uncategorized',
                'type' => $item->type,
                'icon' => $item->category?->icon ?? 'folder',
                'color' => $item->category?->color ?? '#999999',
                'count' => (int) $item->count,
                'total_amount' => round($amount, 2),
                'percentage' => $percentage,
            ];
        });

        // Transactions list
        $transactions = (clone $baseQuery)
            ->with('category')
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return [
            'period_info' => [
                'period' => $period,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'total_income' => round($totalIncome, 2),
            'total_expense' => round($totalExpense, 2),
            'net_balance' => round($netBalance, 2),
            'transaction_count' => $transactionCount,
            'category_breakdown' => $categoryBreakdown,
            'transactions' => TransactionResource::collection($transactions),
        ];
    }
}
