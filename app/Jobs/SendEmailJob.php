<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Mail\Message;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $to;
    public $subject;
    public $body;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($to, $subject, $body)
    {
        $this->to = $to;
        $this->subject = $subject;
        $this->body = $body;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Mail::raw($this->body, function (Message $message) {
                $message->to($this->to)
                    ->subject($this->subject);
            });

            Log::info('SendEmailJob: email queued/sent to ' . $this->to, [
                'subject' => $this->subject,
            ]);
        } catch (\Throwable $e) {
            Log::error('SendEmailJob failed: ' . $e->getMessage(), [
                'to' => $this->to,
                'subject' => $this->subject,
            ]);
            // rethrow to allow retry/backoff per queue config
            throw $e;
        }
    }
}
