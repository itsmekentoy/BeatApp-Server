<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SaveTransactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;

    /**
     * Create a new job instance.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Normalize keys to lower-case for predictable access
            $payload = array_change_key_case($this->data, CASE_LOWER);

            Log::info('SaveTransactionJob received payload', $payload);

            // --- Send payload to privilege service endpoint /add-and-activate-privilege ---
            // Use MYLINK env var if set, otherwise fall back to app URL
            // NOTE: don't use rtrim with a substring as the second argument —
            // rtrim($str, '...') treats the second arg as a character list and
            // will strip any matching characters from the end (this caused
            // 'localhost' -> 'localhos'). Use rtrim($base, '/') instead.
            $base = env('MYLINK', config('app.url'));
            $endpoint = rtrim($base, '/') . '/add-and-activate-privilege';

            Log::info('SaveTransactionJob: sending payload to privilege endpoint', ['endpoint' => $endpoint]);
            $response = Http::timeout(50)->post('http://localhost/api/Beat/add-and-activate-privilege', $payload);

            Log::info('SaveTransactionJob: privilege endpoint response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'endpoint' => $endpoint,
            ]);

            Log::info($response);
        } catch (\Throwable $e) {
            // Don't rethrow — this job should not break the calling flow if the external service fails.
            Log::error('SaveTransactionJob failed to send payload: ' . $e->getMessage(), ['data' => $this->data]);
        }
    }
}
