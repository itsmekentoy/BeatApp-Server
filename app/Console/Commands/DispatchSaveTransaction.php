<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Bus;

class DispatchSaveTransaction extends Command
{
    protected $signature = 'queue:save-transaction {customer_id} {amount} {payment_method?} {--reference=} {--notes=}';
    protected $description = 'Dispatch a SaveTransactionJob to the queue (for testing)';

    public function handle()
    {
        $data = [
            'customer_id' => $this->argument('customer_id'),
            'amount' => $this->argument('amount'),
            'payment_method' => $this->argument('payment_method') ?? 'unknown',
            'reference' => $this->option('reference'),
            'notes' => $this->option('notes'),
            'payment_date' => now(),
        ];

        try {
            // Run job synchronously so no queue worker is required during testing
            Bus::dispatchSync(new \App\Jobs\SaveTransactionJob($data));
            $this->info('Executed SaveTransactionJob synchronously');
            
        } catch (\Throwable $e) {
            Log::error('Failed to dispatch SaveTransactionJob: ' . $e->getMessage());
            $this->error('Failed to dispatch job: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
