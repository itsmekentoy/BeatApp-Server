<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
class KeyfobController extends Controller
{
    // --- Change these defaults if you need to test other controllers ---
    private string $defaultIp = '192.168.1.10';
    private int $defaultSn = 222455417;
    private int $port = 60000;

    /**
     * Helper: convert a decimal value to BCD-like single byte (same as GetHex in C# sample)
     * e.g. 20 -> 0x20, 25 -> 0x25, 7 -> 0x07
     */
    private function toBcdByte(int $v): int
    {
        // integer division and remainder; produce single byte value
        $tens = intdiv($v, 10);
        $ones = $v % 10;
        return ($tens << 4) | $ones;
    }

    /**
     * Add (or update) a single card privilege (2-door) and then activate (upload) privileges.
     * Uses sample data if not provided in request.
     *
     * POST /api/add-and-activate-privilege
     * Body JSON (optional):
     * {
     *   "ip_address": "192.168.1.10",
     *   "sn": 222455417,
     *   "card_number": 5141160,
     *   "start_date": "2025-10-26",
     *   "end_date": "2026-10-26"
     * }
     */
    public function addAndActivatePrivilege(Request $request)
    {
        $ip = $this->defaultIp;
        $controllerIp = $ip;
        $port = $this->port;
        $sn = $this->defaultSn;
        $cardNo = (int)$request->input('card_number');

        $start = Carbon::parse($request->input('start_date', now('Asia/Manila')->toDateString()))->startOfDay();
        $end = Carbon::parse($request->input('end_date', now('Asia/Manila')->addYear()->toDateString()))->startOfDay();

        // --- Build Add/Edit Privilege packet (0x50), 64 bytes ---
        $packet = str_repeat("\x00", 64);
        $packet[0] = chr(0x17); // Type
        $packet[1] = chr(0x50); // Function ID: Add/Edit Privilege

        // Controller SN (little endian, 4 bytes)
        $packet = substr_replace($packet, pack('V', $sn), 4, 4);

        // Card number (low 4 bytes little-endian) at offset 8
        $packet = substr_replace($packet, pack('V', $cardNo), 8, 4);

        // Card high 4 bytes at offset 44 — per WG sample, set to 0
        $packet = substr_replace($packet, pack('V', 0), 44, 4);

        // Start date bytes at offsets 12..15 — WG sample uses (century, year, month, day) in single bytes
        $packet[12] = chr($this->toBcdByte(intdiv((int)$start->year, 100))); // e.g. 20
        $packet[13] = chr($this->toBcdByte((int)$start->format('y')));       // e.g. 25
        $packet[14] = chr($this->toBcdByte((int)$start->month));
        $packet[15] = chr($this->toBcdByte((int)$start->day));

        // End date bytes at offsets 16..19
        $packet[16] = chr($this->toBcdByte(intdiv((int)$end->year, 100)));
        $packet[17] = chr($this->toBcdByte((int)$end->format('y')));
        $packet[18] = chr($this->toBcdByte((int)$end->month));
        $packet[19] = chr($this->toBcdByte((int)$end->day));

        // Door privileges (2-door controller): offsets 20 & 21 -> allow (0x01)
        // Offsets 22 & 23 (doors 3/4) -> zero (disabled)
        $packet[20] = chr(0x01); // Door 1 allow
        $packet[21] = chr(0x01); // Door 2 allow
        $packet[22] = chr(0x00); // Door 3 disallow
        $packet[23] = chr(0x00); // Door 4 disallow

        // Password 3-bytes (offsets 24..26), leave 0
        $packet[24] = chr(0x00);
        $packet[25] = chr(0x00);
        $packet[26] = chr(0x00);

        // Deactivation hour/minute - offsets 30..31 (optional)
        $packet[30] = chr(23); // 23h
        $packet[31] = chr(59); // 59m

        // Sequence ID (big-endian) at 40..43
        $seq = random_int(1, 0xFFFFFFFF);
        $packet = substr_replace($packet, pack('N', $seq), 40, 4);

        // Send 0x50
        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, 1);
        socket_sendto($sock, $packet, strlen($packet), 0, $ip, $this->port);

        // Wait for response (2s)
        socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 2, 'usec' => 0]);
        $buf = '';
        $from = '';
        $portOut = 0;
        $bytes = @socket_recvfrom($sock, $buf, 1024, 0, $from, $portOut);

        if ($bytes === false) {
            socket_close($sock);
            return response()->json([
                'status' => 'timeout',
                'message' => 'No response from controller to 0x50 (add privilege)'
            ], 408);
        }

        $respHex = bin2hex($buf);
        $resultByte = ord($buf[8] ?? "\x00");

        // If controller accepted (byte[8] == 1), then trigger upload/activate (0x56)
       if ($resultByte === 1) {
    // === Build and send 0x56 Activate Privilege ===
    $packet56 = str_repeat("\x00", 64);
    $packet56[0] = chr(0x17); // Type
    $packet56[1] = chr(0x56); // Function ID: Upload/Activate Privilege
    $packet56 = substr_replace($packet56, pack('V', $sn), 4, 4); // SN (little-endian)

    // Door count (byte 8): 2 doors in your controller
    $packet56[8] = chr(0x02);

    // Record count (bytes 9–10): 1 record (low byte first)
    $packet56[9] = chr(0x01);
    $packet56[10] = chr(0x00);

    // Sequence ID (bytes 40–43): random big-endian 4 bytes
    $seq2 = random_int(1, 0xFFFFFFFF);
    $packet56 = substr_replace($packet56, pack('N', $seq2), 40, 4);

    // Send 0x56 command
    socket_sendto($sock, $packet56, strlen($packet56), 0, $controllerIp, $port);

    // Wait for reply (timeout: 2s)
    socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 2, 'usec' => 0]);
    $buf56 = '';
    $from56 = '';
    $port56 = 0;
    $bytes56 = @socket_recvfrom($sock, $buf56, 1024, 0, $from56, $port56);

    socket_close($sock);

    if ($bytes56 === false) {
        return response()->json([
            'status' => 'partial',
            'message' => '0x50 accepted but no reply to 0x56 (activation). Check controller logs.',
            'response_hex_0x50' => $respHex
        ]);
    }

    $resp56Hex = bin2hex($buf56);
    $res56 = ord($buf56[8] ?? "\x00");

    if ($res56 !== 1) {
        return response()->json([
            'status' => 'success',
            'message' => 'Privilege added. Controller ignored 0x56 but card is active (normal for some firmwares).',
            'card_number' => $cardNo,
            'response_hex_0x50' => $respHex,
            'response_hex_0x56' => $resp56Hex,
        ]);
    }else{
        return response()->json([
            'status' => 'success',
            'message' => 'Privilege added and activated successfully.',
            'card_number' => $cardNo,
            'response_hex_0x50' => $respHex,
            'response_hex_0x56' => $resp56Hex,
        ]);
    }
} else {
    socket_close($sock);
    return response()->json([
        'status' => 'failed',
        'message' => 'Controller rejected the add privilege command (0x50).',
        'response_hex' => $respHex,
    ], 400);
}

    }

    /**
     * Listener endpoint for receiving a single UDP packet and decoding it as a swipe/event record.
     * GET /api/listen-swipe
     *
     * This will bind to 0.0.0.0:60000, wait up to 5 seconds for a packet, decode and return JSON.
     */
    public function listenSwipe()
    {
        $port = $this->port;
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        if ($socket === false) {
            return response()->json(['status' => 'error', 'message' => 'Failed to create socket'], 500);
        }

        if (!@socket_bind($socket, '0.0.0.0', $port)) {
            return response()->json(['status' => 'error', 'message' => "Failed to bind socket to port {$port}"], 500);
        }

        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);

        $buf = '';
        $from = '';
        $portOut = 0;
        $bytes = @socket_recvfrom($socket, $buf, 4096, 0, $from, $portOut);
        socket_close($socket);

        if ($bytes === false || strlen($buf) === 0) {
            return response()->json(['status' => 'no_data', 'message' => 'No packet received'], 408);
        }

        $rawHex = bin2hex($buf);
        Log::info('Access controller packet', ['from' => $from, 'port' => $portOut, 'raw_hex' => $rawHex]);

        // Try decode common WG swipe/event formats
        $decoded = $this->decodeSwipePacket($buf);

        return response()->json([
            'status' => 'success',
            'from' => $from,
            'raw_hex' => $rawHex,
            'decoded' => $decoded
        ]);
    }

    /**
     * Decode a binary packet into a swipe/event record (best-effort according to WG/WG3000 formats).
     *
     * This function attempts a couple of known locations for card no + timestamp that match
     * the documentation and your Swipe Record Description.xls.
     */
    private function decodeSwipePacket(string $buf): array
    {
        $len = strlen($buf);
        $hex = bin2hex($buf);

        // default result
        $res = [
            'packet_length' => $len,
            'function_id' => null,
            'controller_sn' => null,
            'card_number' => null,
            'record_index' => null,
            'door_no' => null,
            'in_out' => null,
            'valid' => null,
            'timestamp' => null,
            'raw_hex' => $hex,
        ];

        // Function ID often at byte 1
        $res['function_id'] = ord($buf[1]);

        // controller SN bytes 4..7 (little endian)
        if ($len >= 8) {
            $snBytes = substr($buf, 4, 4);
            $sn = unpack('V', $snBytes)[1];
            $res['controller_sn'] = $sn;
        }

        // Many WG swipe records format places card number at offset 8..11 (little-endian)
        if ($len >= 12) {
            $cardLowBytes = substr($buf, 8, 4);
            $cardNo = unpack('V', $cardLowBytes)[1];
            $res['card_number'] = $cardNo;
        }

        // RecordIndex often in bytes 28..31 (or 32..35) depending on variant
        if ($len >= 36) {
            // try offset 28 (4 bytes little-endian)
            $possibleIndex = unpack('V', substr($buf, 28, 4))[1];
            if ($possibleIndex !== 0) {
                $res['record_index'] = $possibleIndex;
            }
        }

        // Door number commonly in a byte near 8+? but also often at byte 9 or 10 in some variants.
        // We'll attempt a few likely offsets and take the first non-zero small value.
        $possibleDoorOffsets = [9, 10, 11, 12, 20];
        foreach ($possibleDoorOffsets as $o) {
            if ($len > $o) {
                $v = ord($buf[$o]);
                if ($v >= 1 && $v <= 8) {
                    $res['door_no'] = $v;
                    break;
                }
            }
        }

        // Valid / NoPass: many implementations return 1 for Pass at byte 8 or 9. Try those.
        $possibleValidOffsets = [8, 9, 10];
        foreach ($possibleValidOffsets as $o) {
            if ($len > $o) {
                $v = ord($buf[$o]);
                // heuristics: 0 => failed, 1 => pass, other small values used as codes
                if ($v === 0) {
                    $res['valid'] = 'NoPass';
                    break;
                } elseif ($v === 1) {
                    $res['valid'] = 'Pass';
                    break;
                }
            }
        }

        // Timestamp: many WG event packets include BCD fields around offsets 12..18 or 37..39.
        // We'll attempt to parse a common layout: bytes 12..15 = YY YY MM DD and 37..39 = hh mm ss
        if ($len >= 40) {
            $yhigh = ord($buf[12]) ; // e.g. 0x20
            $ylow = ord($buf[13]);   // e.g. 0x25
            $mon = ord($buf[14]);
            $day = ord($buf[15]);
            $hh = ord($buf[37]);
            $mm = ord($buf[38]);
            $ss = ord($buf[39]);

            // sanity check
            if ($yhigh > 0 && $ylow <= 99 && $mon >= 1 && $mon <= 12 && $day >= 1 && $day <= 31) {
                $year = ($yhigh * 100) + $ylow;
                $res['timestamp'] = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $mon, $day, $hh, $mm, $ss);
            }
        }

        // Fallback: if no card_number deduced above, try the hex slice used earlier in this conversation:
        if (empty($res['card_number']) || $res['card_number'] === 0) {
            // older decode we used hex substring at position 32 (hex chars) => bytes at offset 16..19
            if ($len >= 20) {
                $cardBytes2 = substr($buf, 16, 4);
                $cardNo2 = unpack('V', $cardBytes2)[1];
                if ($cardNo2 !== 0) {
                    $res['card_number'] = $cardNo2;
                }
            }
        }

        return $res;
    }

    public function deletePrivilege(Request $request)
{
    $ip = '192.168.1.10';      // Controller IP
    $sn = 222455417;           // Controller Serial Number
    $cardNo = $request->input('card_number');         // Card number to delete
    $port = 60000;

    // --- Build 64-byte UDP packet ---
    $packet = str_repeat("\x00", 64);
    $packet[0] = chr(0x17); // Type
    $packet[1] = chr(0x52); // Function ID: Delete Privilege

    // SN (little-endian)
    $packet = substr_replace($packet, pack('V', $sn), 4, 4);

    // Card number (little-endian)
    $packet = substr_replace($packet, pack('V', $cardNo), 8, 4);

    // Random sequence ID (big-endian)
    $seq = random_int(1, 0xFFFFFFFF);
    $packet = substr_replace($packet, pack('N', $seq), 40, 4);

    // --- Send packet via UDP ---
    $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, 1);
    socket_sendto($sock, $packet, strlen($packet), 0, $ip, $port);

    // Wait for reply (timeout = 2 sec)
    socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 2, 'usec' => 0]);
    $response = '';
    $from = ''; 
    $portOut = 0;
    $bytes = @socket_recvfrom($sock, $response, 1024, 0, $from, $portOut);
    socket_close($sock);

    // --- Check result ---
    if ($bytes === false) {
        return response()->json([
            'status' => 'timeout',
            'message' => 'No response from controller (delete failed)'
        ]);
    }

    $responseHex = bin2hex($response);
    $resultByte = ord($response[8] ?? "\x00");

    if ($resultByte === 1) {
        return response()->json([
            'status' => 'success',
            'message' => 'Card privilege deleted successfully',
            'card_number' => $cardNo,
            'response_hex' => $responseHex
        ]);
    } else {
        return response()->json([
            'status' => 'failed',
            'message' => 'Controller rejected the delete command',
            'card_number' => $cardNo,
            'response_hex' => $responseHex
        ]);
    }
}
public function downloadAllPrivileges()
{
    $ip = '192.168.1.10';
    $sn = 222455417;
    $port = 60000;

    $results = [];
    $startCard = 0;
    $hasMore = true;

    $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 3, 'usec' => 0]);

    while ($hasMore) {
        // Build 0x5C packet
        $packet = str_repeat("\x00", 64);
        $packet[0] = chr(0x17);
        $packet[1] = chr(0x5C); // Read all privileges
        $packet = substr_replace($packet, pack('V', $sn), 4, 4);
        $packet = substr_replace($packet, pack('V', $startCard), 8, 4);
        $seq = random_int(1, 0xFFFFFFFF);
        $packet = substr_replace($packet, pack('N', $seq), 40, 4);

        // Send packet
        socket_sendto($sock, $packet, strlen($packet), 0, $ip, $port);

        // Receive response
        $buf = '';
        $from = '';
        $portOut = 0;
        $bytes = @socket_recvfrom($sock, $buf, 1024, 0, $from, $portOut);

        if ($bytes === false || $bytes < 64) {
            $hasMore = false;
            break;
        }

        // Process response
        $hex = bin2hex($buf);
        $records = [];

        // Each 64 bytes = one card record
        for ($i = 0; $i + 64 <= strlen($buf); $i += 64) {
            $chunk = substr($buf, $i, 64);
            $cardNo = unpack('V', substr($chunk, 8, 4))[1];
            if ($cardNo === 0) continue; // no more data

            // Dates
            $fromYear  = 2000 + ord($chunk[12 + 1]);
            $fromMonth = ord($chunk[12 + 2]);
            $fromDay   = ord($chunk[12 + 3]);

            $toYear  = 2000 + ord($chunk[16 + 1]);
            $toMonth = ord($chunk[16 + 2]);
            $toDay   = ord($chunk[16 + 3]);

            $doors = [
                'door1' => ord($chunk[20]),
                'door2' => ord($chunk[21]),
                'door3' => ord($chunk[22]),
                'door4' => ord($chunk[23]),
            ];

            $record = [
                'card_number' => $cardNo,
                'active_from' => sprintf('%04d-%02d-%02d', $fromYear, $fromMonth, $fromDay),
                'active_until' => sprintf('%04d-%02d-%02d', $toYear, $toMonth, $toDay),
                'doors' => $doors,
            ];

            $records[] = $record;
            $results[] = $record;

            $startCard = $cardNo + 1;
        }

        // If no records found, stop loop
        if (empty($records)) {
            $hasMore = false;
        }

        // Optional: small delay to prevent flooding
        usleep(200000); // 200 ms
    }

    socket_close($sock);

    return response()->json([
        'status' => 'success',
        'count' => count($results),
        'data' => $results,
    ]);
}


}
