<?php

namespace App\Console\Commands;

use App\Services\TransactionService;
use Illuminate\Console\Command;

class ProcessRecurringTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:process-recurring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process due recurring transactions and insert actual transaction records.';

    /**
     * Execute the console command.
     */
    public function handle(TransactionService $transactionService): int
    {
        $this->info('Processing due recurring transactions...');

        $processedCount = $transactionService->processDueRecurringTransactions();

        $this->info("Successfully processed {$processedCount} recurring transaction(s).");

        return Command::SUCCESS;
    }
}
