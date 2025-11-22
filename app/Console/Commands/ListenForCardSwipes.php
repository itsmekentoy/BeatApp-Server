<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\DoorAccessController;

class ListenForCardSwipes extends Command
{
    /**
     * Command name and description
     */
    protected $signature = 'door:listen';
    protected $description = 'Listen for UDP card swipes from door access controller (one trigger per tap)';

    public function handle()
    {
        $port = 60000;
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        if ($socket === false) {
            $this->error('❌ Failed to create socket');
            return 1;
        }

        if (!@socket_bind($socket, '0.0.0.0', $port)) {
            $this->error('❌ Failed to bind socket to port ' . $port);
            return 1;
        }

        $this->info("🎧 Listening for card swipes on UDP port {$port} (one tap mode)...");

        // ✅ Track last card scanned
        $lastCardNumber = null;

        while (true) {
            $buf = '';
            $from = '';
            $portOut = 0;

            $bytes = @socket_recvfrom($socket, $buf, 1024, 0, $from, $portOut);

            if ($bytes > 0) {
                $rawHex = bin2hex($buf);

                // --- Decode packet ---
                $snHex = substr($rawHex, 8, 8);
                $sn = hexdec(implode('', array_reverse(str_split($snHex, 2))));

                $cardHex = substr($rawHex, 32, 8);
                $cardNumber = hexdec(implode('', array_reverse(str_split($cardHex, 2))));

                $dateHex = substr($rawHex, 40, 8);
                $year = hexdec(substr($dateHex, 0, 2)) + 2000;
                $month = hexdec(substr($dateHex, 2, 2));
                $day = hexdec(substr($dateHex, 4, 2));

                $timeHex = substr($rawHex, 48, 6);
                $hour = hexdec(substr($timeHex, 0, 2));
                $minute = hexdec(substr($timeHex, 2, 2));
                $second = hexdec(substr($timeHex, 4, 2));

                $timestamp = sprintf('%04d-%02d-%02d %02d:%02d:%02d', 
                    $year, $month, $day, $hour, $minute, $second
                );

                // ✅ Ignore if same card as last tap
                if ($cardNumber === $lastCardNumber) {
                    continue;
                }

                // New card detected → process
                $lastCardNumber = $cardNumber;

                $data = [
                    'from' => $from,
                    'controller_sn' => $sn,
                    'card_number' => $cardNumber,
                    'timestamp' => $timestamp,
                ];

                Log::info('🎫 New card tap detected', $data);
                $this->info("✅ Card tapped: {$cardNumber} at {$timestamp}");
                    $controller = app(DoorAccessController::class);
                    $controller->CustomerCheckIn($cardNumber);
                // try {
                    
                // } catch (\Throwable $e) {
                //     Log::error('❌ Failed to handle card check-in: ' . $e->getMessage());
                //     $this->error('❌ Error: ' . $e->getMessage());
                // }

                // 💤 Wait briefly before listening again to prevent immediate repeat
                usleep(500000); // 0.5 second
            }

            usleep(100000); // prevent CPU overload
        }

        socket_close($socket);
        return 0;
    }
}
