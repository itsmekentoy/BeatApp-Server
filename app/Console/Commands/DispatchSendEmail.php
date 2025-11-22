<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Bus;

class DispatchSendEmail extends Command
{
    protected $signature = 'queue:send-email {to} {subject?} {body?}';
    protected $description = 'Dispatch a SendEmailJob to the queue (for testing)';

    public function handle()
    {
        $to = $this->argument('to');
        $subject = $this->argument('subject') ?? 'Test email from queue';
        $body = $this->argument('body') ?? 'This is a test email sent via queued job.';

        try {
            // Run job synchronously (no worker required)
            Bus::dispatchSync(new \App\Jobs\SendEmailJob($to, $subject, $body));
            $this->info("Executed SendEmailJob synchronously for: {$to}");
        } catch (\Throwable $e) {
            Log::error('Failed to dispatch SendEmailJob: ' . $e->getMessage());
            $this->error('Failed to dispatch job: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
