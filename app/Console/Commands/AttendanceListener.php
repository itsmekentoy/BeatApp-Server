<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\lastTappKeyPob;
use App\Models\BeatCustomer;
use App\Models\BeatAttendanceMonitoring;

class AttendanceListener extends Command
{
    protected $signature = 'attendance:listen';
    protected $description = 'Listen for UDP packets for attendance system';

    public function handle()
    {
        $port = 60000; // your port here
        
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        if ($socket === false) {
            $this->error("❌ Failed to create socket");
            return;
        }

        if (!@socket_bind($socket, '0.0.0.0', $port)) {
            $this->error("❌ Failed to bind port {$port}");
            return;
        }

        $this->info("✅ Attendance Listener Running on Port {$port}...");

        $lastCard = null;

        while (true) {
            $buf = '';
            $from = '';
            $portOut = 0;

            $bytes = @socket_recvfrom($socket, $buf, 1024, 0, $from, $portOut);

            if ($bytes > 0) {
                $rawHex = bin2hex($buf);

                // Extract SN
                $snHex = substr($rawHex, 8, 8);
                $sn = hexdec(implode('', array_reverse(str_split($snHex, 2))));

                // Extract Card Number
                $cardHex = substr($rawHex, 32, 8);
                $cardNumber = hexdec(implode('', array_reverse(str_split($cardHex, 2))));

                if ($cardNumber === $lastCard) {
                    continue;
                }

                $lastCard = $cardNumber;

                // Extract Date
                $dateHex = substr($rawHex, 40, 8);
                $year = hexdec(substr($dateHex, 0, 2)) + 2000;
                $month = hexdec(substr($dateHex, 2, 2));
                $day = hexdec(substr($dateHex, 4, 2));

                // Extract Time
                $timeHex = substr($rawHex, 48, 6);
                $hour = hexdec(substr($timeHex, 0, 2));
                $minute = hexdec(substr($timeHex, 2, 2));
                $second = hexdec(substr($timeHex, 4, 2));

                $timestamp = sprintf(
                    '%04d-%02d-%02d %02d:%02d:%02d',
                    $year, $month, $day, $hour, $minute, $second
                );

                $data = [
                    'source_ip'     => $from,
                    'controller_sn' => $sn,
                    'card_number'   => $cardNumber,
                    'timestamp'     => $timestamp,
                    'raw_hex'       => $rawHex,
                ];
                $dateNow = date('Y-m-d H:i:s');
                $last = lastTappKeyPob::create([
                    'keyfob_number' => $cardNumber,
                ]);
                $customer = BeatCustomer::where('keypab', $cardNumber)->first();
                if (!$customer) {
                    Log::warning("⚠️ Unrecognized Card #{$cardNumber} at {$dateNow} from Controller SN: {$sn} (IP: {$from})");
                    continue;
                }
                Log::info('Starting attendance monitoring for keyfob: ' . $keypab);
                $attendance = BeatAttendanceMonitoring::create([
                    'beat_customer_id' => $customerID->id,
                    'attendance_date' => Carbon::now('Asia/Manila')->toDateString(),
                    'check_in_time' => Carbon::now('Asia/Manila')->toTimeString(),
                ]);


                Log::info("🎫 Attendance Scan Received", $data);



                $this->info("Card #{$cardNumber} at {$dateNow} from Controller SN: {$sn} (IP: {$from})");
            }

            usleep(100000); // 100ms
        }
    }
}
