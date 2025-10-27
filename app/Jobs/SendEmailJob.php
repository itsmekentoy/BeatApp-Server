<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Helpers\MailHelper;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $to;
    public $subject;
    public $body;
    public $attachments;

    /**
     * Create a new job instance.
     */
    public function __construct($to, $subject, $body)
    {
        $this->to = $to;
        $this->subject = $subject;
        $this->body = $body;
       
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $sent = MailHelper::sendMail(
                $this->to,
                $this->subject,
                $this->body,
                
            );

            if ($sent) {
                Log::info("SendEmailJob: Email successfully sent to {$this->to}");
            } else {
                Log::warning("SendEmailJob: Failed to send email to {$this->to}");
            }

        } catch (\Throwable $e) {
            Log::error('SendEmailJob Error: ' . $e->getMessage());
            throw $e; // allows Laravel queue retry/backoff to handle failure
        }
    }
}
