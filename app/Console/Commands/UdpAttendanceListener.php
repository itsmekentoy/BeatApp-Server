<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UdpAttendanceListener extends Command
{
    protected $signature = 'attendance:listen';
    protected $description = 'Listen to attendance device on UDP port 6000.';

    public function handle()
    {
        $port = 60000;
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        if ($socket === false) {
            Log::error('❌ Failed to create UDP socket.');
            return 1;
        }

        if (!@socket_bind($socket, '0.0.0.0', $port)) {
            Log::error("❌ Cannot bind to port $port. Is another app listening?");
            return 1;
        }

        // No hang — receive every 1 second
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 1, 'usec' => 0]);

        Log::info("🔊 Attendance listener started on port $port");

        while (true) {
            $buf = '';
            $from = '';
            $portOut = 0;

            $bytes = @socket_recvfrom($socket, $buf, 1024, 0, $from, $portOut);

            if ($bytes > 0) {
                $rawHex = bin2hex($buf);

                // Decode packet
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

                $data = [
                    'source_ip'     => $from,
                    'controller_sn' => $sn,
                    'card_number'   => $cardNumber,
                    'timestamp'     => $timestamp,
                    'raw_hex'       => $rawHex,
                ];

                Log::info("🎫 Attendance card scan received", $data);

                // OPTIONAL: Save to database
                // Attendance::create($data);
            }

            // Continue looping every 100ms
            usleep(100000);
        }

        socket_close($socket);
        return 0;
    }
}
