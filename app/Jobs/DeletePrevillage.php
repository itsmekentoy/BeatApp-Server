<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class DeletePrevillage implements ShouldQueue
{
     use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // the customer's keypab/card id that we send to the external service
    public $keypab;

    /**
     * Create a new job instance.
     *
     * @param string $keypab
     */
    public function __construct($keypab)
    {
        // store the provided keypab (no strict int requirement because key may be string)
        $this->keypab = $keypab;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Log::info('FreezeMembershipJob started', ['keypab' => $this->keypab]);
            // use configured base URL if available
            $base = env('MYLINK', env('APP_URL', 'http://localhost'));
            $endpoint = rtrim($base, '/') . '/api/Beat/delete-privilege';
            $response = Http::timeout(50)->post('http://localhost/api/Beat/delete-privileg', ['card_number' => $this->keypab]);
            Log::info('FreezeMembershipJob completed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'response' => $response->json()
            ]);
        } catch (\Throwable $e) {
            Log::error('FreezeMembershipJob failed: ' . $e->getMessage(), ['keypab' => $this->keypab]);
            throw $e;
        }
    }
}