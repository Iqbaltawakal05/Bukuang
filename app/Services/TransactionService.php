<?php

namespace App\Services;

use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TransactionService
{
    /**
     * Get paginated transactions for a user with filters.
     */
    public function getTransactionsForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Transaction::with('category')
            ->where('user_id', $user->id);

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('transaction_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('transaction_date', '<=', $filters['end_date']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Create a new transaction.
     */
    public function createTransaction(User $user, array $data): Transaction
    {
        return Transaction::create([
            'user_id' => $user->id,
            'category_id' => $data['category_id'],
            'type' => $data['type'],
            'amount' => $data['amount'],
            'transaction_date' => $data['transaction_date'],
            'description' => $data['description'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }
    /**
     * Calculate next run date for recurring transactions based on frequency.
     */
    public function calculateNextRunDate(string $startDate, string $frequency, ?string $currentRunDate = null): string
    {
        $baseDate = $currentRunDate ? Carbon::parse($currentRunDate) : Carbon::parse($startDate);

        return match ($frequency) {
            'daily' => $baseDate->addDay()->format('Y-m-d'),
            'weekly' => $baseDate->addWeek()->format('Y-m-d'),
            'monthly' => $baseDate->addMonth()->format('Y-m-d'),
            'yearly' => $baseDate->addYear()->format('Y-m-d'),
            default => $baseDate->format('Y-m-d'),
        };
    }

    /**
     * Create a new recurring transaction schedule.
     */
    public function createRecurringTransaction(User $user, array $data): RecurringTransaction
    {
        return RecurringTransaction::create([
            'user_id' => $user->id,
            'category_id' => $data['category_id'],
            'type' => $data['type'],
            'amount' => $data['amount'],
            'frequency' => $data['frequency'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'next_run_date' => $data['start_date'],
            'description' => $data['description'] ?? null,
            'is_active' => true,
        ]);
    }

    /**
     * Process due recurring transactions and generate actual Transaction entries.
     */
    public function processDueRecurringTransactions(?string $currentDate = null): int
    {
        $today = $currentDate ? Carbon::parse($currentDate) : Carbon::today();

        $dueRecurring = RecurringTransaction::where('is_active', true)
            ->whereDate('next_run_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            })->get();

        $processedCount = 0;

        foreach ($dueRecurring as $recurring) {
            Transaction::create([
                'user_id' => $recurring->user_id,
                'category_id' => $recurring->category_id,
                'recurring_transaction_id' => $recurring->id,
                'type' => $recurring->type,
                'amount' => $recurring->amount,
                'transaction_date' => $recurring->next_run_date,
                'description' => $recurring->description ?? 'Recurring Transaction',
                'notes' => 'Generated automatically by system schedule',
            ]);

            $nextRun = $this->calculateNextRunDate(
                $recurring->start_date->format('Y-m-d'),
                $recurring->frequency,
                $recurring->next_run_date->format('Y-m-d')
            );

            $updateData = [
                'last_processed_at' => now(),
                'next_run_date' => $nextRun,
            ];

            if ($recurring->end_date && Carbon::parse($nextRun)->gt($recurring->end_date)) {
                $updateData['is_active'] = false;
            }

            $recurring->update($updateData);
            $processedCount++;
        }

        return $processedCount;
    }

    /**
     * Update an existing transaction.
     */
    public function updateTransaction(Transaction $transaction, array $data): Transaction
    {
        $transaction->update([
            'category_id' => $data['category_id'],
            'type' => $data['type'],
            'amount' => $data['amount'],
            'transaction_date' => $data['transaction_date'],
            'description' => $data['description'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return $transaction->fresh('category');
    }

    /**
     * Delete a transaction.
     */
    public function deleteTransaction(Transaction $transaction): void
    {
        $transaction->delete();
    }
}
