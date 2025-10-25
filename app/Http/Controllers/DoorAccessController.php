<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\BeatAttendanceMonitoring;
use App\Models\BeatCustomer;

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

    public function CustomerCheckIn($keyFob)
    {

        //login via keyfob
        //open door if valid

        $customer = BeatCustomer::where('keypab', $keyFob)->first();
        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $customerEndData = $customer->membership_end;

        if(Carbon::now()->greaterThan(Carbon::parse($customerEndData))){
            return response()->json(['message' => 'Membership expired. Access denied.'], 403);
        }

        $customerAttendance = new BeatAttendanceMonitoring();
        $customerAttendance->beat_customer_id = $customer->id;
        $customerAttendance->attendance_date = Carbon::now()->toDateString();
        $customerAttendance->check_in_time = Carbon::now()->toTimeString();
        $customerAttendance->save();

        

        // Logic for customer check-in
        return response()->json(['message' => 'Customer checked in successfully']);
    }

    public function ReadKeyFob(Request $request)
    {
        //login for keyfob reading
    }
}
