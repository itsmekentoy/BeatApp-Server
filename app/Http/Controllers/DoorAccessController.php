<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\BeatAttendanceMonitoring;
use App\Models\BeatCustomer;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class DoorAccessController extends Controller
{
    public function searchController()
    {
        $broadcastIp = '255.255.255.255'; // broadcast to all on LAN
        $port = 60000;                    // default controller port

        // Build the 64-byte UDP packet
        $packet = str_repeat("\x00", 64);
        $packet[0] = chr(0x17); // Packet Type
        $packet[1] = chr(0x94); // Function ID for "Search Controller"

        // SN can be 0x00000000 for broadcast search
        $sn = pack('V', 0);
        $packet = substr_replace($packet, $sn, 4, 4);

        // Create UDP socket
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        // Allow broadcast packets
        socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1);

        // Set receive timeout (3 seconds)
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 3, 'usec' => 0]);

        // Send the broadcast
        socket_sendto($socket, $packet, strlen($packet), 0, $broadcastIp, $port);

        $controllers = [];
        $from = '';
        $portRecv = 0;
        $buf = '';

        // Receive multiple responses
        while (@socket_recvfrom($socket, $buf, 1024, 0, $from, $portRecv)) {
            if (strlen($buf) >= 16) {
                // SN (bytes 4–7)
                $snBytes = substr($buf, 4, 4);
                $sn = unpack('V', $snBytes)[1]; // little endian

                // IP (bytes 8–11)
                $ipBytes = substr($buf, 8, 4);
                $ip = implode('.', array_map('ord', str_split($ipBytes)));

                // MAC (bytes 12–17)
                $macBytes = substr($buf, 12, 6);
                $mac = implode(':', array_map(fn($b) => sprintf('%02X', ord($b)), str_split($macBytes)));

                $controllers[] = [
                    'ip' => $ip,
                    'sn' => $sn,
                    'mac' => $mac,
                    'from' => $from,
                ];
            }
        }

        socket_close($socket);

        if (empty($controllers)) {
            return response()->json(['status' => 'no controllers found']);
        }

        return response()->json([
            'status' => 'success',
            'controllers' => $controllers
        ]);
    }
    public function OpenDoor(Request $request)
    {
        $ipAddress = $request->input('ip_address');
        $sn = $request->input('sn');

        $doorNo = $request->input('door_no', 1); // Default to door 1

        $controllerIp = $ipAddress;  // ✅ from search result
        $controllerPort = 60000;         // default UDP port
        $controllerSn = $sn;       // ✅ from search result
        $doorNo = $doorNo;                     // Door number (1–4)

        // Build 64-byte packet
        $packet = str_repeat("\x00", 64);
        $packet[0] = chr(0x17); // Type
        $packet[1] = chr(0x40); // Function ID: Remote Open Door

        // Insert SN (little-endian)
        $sn = pack('V', $controllerSn);
        $packet = substr_replace($packet, $sn, 4, 4);

        // Door number (byte 8)
        $packet[8] = chr($doorNo);

        // Sequence ID (random 4 bytes, big-endian)
        $seq = random_int(1, 0xFFFFFFFF);
        $seqBytes = pack('N', $seq);
        $packet = substr_replace($packet, $seqBytes, 40, 4);

        // Send UDP packet
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1);
        socket_sendto($socket, $packet, strlen($packet), 0, $controllerIp, $controllerPort);

        // Wait for reply (optional)
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 2, 'usec' => 0]);
        $buf = '';
        $from = '';
        $port = 0;
        $bytesReceived = @socket_recvfrom($socket, $buf, 1024, 0, $from, $port);
        socket_close($socket);

        if ($bytesReceived === false) {
            return response()->json(['status' => 'timeout or no response']);
        }

        // Check byte[8] (result)
        $result = ord($buf[8] ?? "\x00");
        if ($result === 1) {
            return response()->json(['status' => 'success', 'message' => 'Door opened successfully']);
        } else {
            return response()->json(['status' => 'failed', 'message' => 'Controller responded but door did not open']);
        }
        
    }
     public function listen()
    {
        $port = 60000;
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        if ($socket === false) {
            return response()->json(['error' => 'Failed to create socket'], 500);
        }

        if (!@socket_bind($socket, '0.0.0.0', $port)) {
            return response()->json(['error' => 'Failed to bind socket to port ' . $port], 500);
        }

        // Set a short timeout so API won’t hang forever
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);

        $buf = '';
        $from = '';
        $portOut = 0;

        $bytes = @socket_recvfrom($socket, $buf, 1024, 0, $from, $portOut);
        socket_close($socket);

        if ($bytes <= 0) {
            return response()->json(['message' => 'No data received'], 408);
        }

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

        $timestamp = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);

        $data = [
            'from' => $from,
            'controller_sn' => $sn,
            'card_number' => $cardNumber,
            'timestamp' => $timestamp,
            'raw_hex' => $rawHex,
        ];

        Log::info('🎫 Access controller card scan received', $data);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function CustomerCheckIn($keyFob)
    {

    // Ensure all date/time operations use Asia/Manila for this request
    // Prefer using Carbon with explicit timezone rather than relying on global php timezone
        //login via keyfob
        //open door if valid
        Log::info('CustomerCheckIn attempt with keyfob: ' . $keyFob);
        $customer = BeatCustomer::where('keypab', $keyFob)->first();
        if (!$customer) {
            Log::warning('No customer found with keyfob: ' . $keyFob);
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $customerEndData = $customer->membership_end;

        if(Carbon::now()->greaterThan(Carbon::parse($customerEndData))){
            Log::warning('Membership expired for customer ID ' . $customer->id);
            return response()->json(['message' => 'Membership expired. Access denied.'], 403);
        }

        $customerAttendance = new BeatAttendanceMonitoring();
        $customerAttendance->beat_customer_id = $customer->id;
        $now = Carbon::now('Asia/Manila');
        $customerAttendance->attendance_date = $now->toDateString();
        $customerAttendance->check_in_time = $now->toTimeString();
        $customerAttendance->save();
        
        // Use the same $now instance (Asia/Manila) for logging to avoid timezone mismatch
        Log::info('Customer ID ' . $customer->id . ' checked in successfully at ' . $now->toDateTimeString());
        // Logic for customer check-in
        return response()->json(['message' => 'Customer checked in successfully']);
    }

    public function ReadKeyFob(Request $request)
    {
        //login for keyfob reading
    }

    public function SetControllerTime(Request $request)
{
    $ip = '192.168.1.10';
    $sn = 222455417;
    $port = 60000;

    // Asia/Manila time
    date_default_timezone_set('Asia/Manila');
    $now = now();

    $year = $now->format('Y'); // e.g. 2025
    $month = $now->format('m');
    $day = $now->format('d');
    $hour = $now->format('H');
    $minute = $now->format('i');
    $second = $now->format('s');

    // Build 64-byte packet
    $packet = str_repeat("\x00", 64);
    $packet[0] = chr(0x17);
    $packet[1] = chr(0x30); // Adjust Time
    $packet = substr_replace($packet, pack('V', $sn), 4, 4);

    $packet[8]  = chr((int)($year / 100)); // 0x20
    $packet[9]  = chr($year % 100);        // 0x25
    $packet[10] = chr($month);
    $packet[11] = chr($day);
    $packet[12] = chr($hour);
    $packet[13] = chr($minute);
    $packet[14] = chr($second);

    $seq = random_int(1, 0xFFFFFFFF);
    $packet = substr_replace($packet, pack('N', $seq), 40, 4);

    $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    socket_sendto($sock, $packet, strlen($packet), 0, $ip, $port);

    socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 2, 'usec' => 0]);
    $response = '';
    $from = ''; $portOut = 0;
    $bytes = @socket_recvfrom($sock, $response, 1024, 0, $from, $portOut);
    socket_close($sock);

    if ($bytes === false) {
        return response()->json(['status' => 'timeout or no response']);
    }

    return response()->json(['status' => 'success', 'data' => bin2hex($response)]);
}
public function uploadSinglePrivilegeBlock(Request $request)
{
    $ip = $request->input('ip_address', '192.168.1.10');
    $sn = (int) $request->input('sn', 222455417);
    $port = 60000;
    $cardNo = (int) $request->input('card_number', 5141160);

    $activate = now('Asia/Manila')->startOfDay();
    $deactivate = now('Asia/Manila')->addYear()->endOfDay();

    // Build one 64-byte privilege record
    $record = array_fill(0, 64, 0x00);
    $record[0] = 0x17;
    $record[1] = 0x56;
    array_splice($record, 4, 4, unpack('C4', pack('V', $sn)));
    array_splice($record, 8, 4, unpack('C4', pack('V', $cardNo)));
    array_splice($record, 44, 4, unpack('C4', pack('V', $cardNo)));

    $record[12] = 0x20;
    $record[13] = 0x25;
    $record[14] = 0x10;
    $record[15] = 0x26;
    $record[16] = 0x20;
    $record[17] = 0x26;
    $record[18] = 0x10;
    $record[19] = 0x26;

    // Full 4-door privilege
    $record[20] = 0x01;
    $record[21] = 0x01;
    $record[22] = 0x01;
    $record[23] = 0x01;

    // total count = 1, index = 1
    array_splice($record, 32, 4, unpack('C4', pack('V', 1))); // total
    array_splice($record, 35, 4, unpack('C4', pack('V', 1))); // index

    // Convert to binary
    $binPacket = implode('', array_map('chr', $record));

    // --- Send UDP ---
    $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    socket_sendto($sock, $binPacket, strlen($binPacket), 0, $ip, $port);
    socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 3, 'usec' => 0]);

    $resp = '';
    $from = '';
    $portOut = 0;
    $bytes = @socket_recvfrom($sock, $resp, 1024, 0, $from, $portOut);
    socket_close($sock);

    if ($bytes === false) {
        return response()->json(['status' => 'timeout']);
    }

    $result = ord($resp[8] ?? "\x00");

    return response()->json([
        'status' => $result === 1 ? 'success' : 'failed',
        'message' => $result === 1 ? 'Privilege uploaded and activated' : 'Controller rejected privilege block',
        'response_hex' => bin2hex($resp),
    ]);
}

public function refreshPrivileges(Request $request)
{
    $ip = $request->input('ip_address', '192.168.1.10');
    $sn = (int) $request->input('sn', 222455417);
    $port = 60000;

    // 64-byte packet for command 0x56 (Upload/Activate Privileges)
    $packet = str_repeat("\x00", 64);
    $packet[0] = chr(0x17);
    $packet[1] = chr(0x56);
    $packet = substr_replace($packet, pack('V', $sn), 4, 4);

    $seq = random_int(1, 0xFFFFFFFF);
    $packet = substr_replace($packet, pack('N', $seq), 40, 4);

    $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    socket_sendto($sock, $packet, strlen($packet), 0, $ip, $port);
    socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 3, 'usec' => 0]);

    $resp = '';
    $from = '';
    $portOut = 0;
    $bytes = @socket_recvfrom($sock, $resp, 1024, 0, $from, $portOut);
    socket_close($sock);

    if ($bytes === false) {
        return response()->json(['status' => 'timeout', 'message' => 'No response from controller']);
    }

    $result = ord($resp[8] ?? "\x00");

    return response()->json([
        'status' => $result === 1 ? 'success' : 'failed',
        'message' => $result === 1
            ? 'Controller privilege table refreshed successfully'
            : 'Failed to refresh privileges',
        'response_hex' => bin2hex($resp)
    ]);
}

}