<?php

namespace App\Http\Controllers;
use App\Models\BeatCustomer;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\BeatCustomerMedical;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\CustomerPaymentTransaction;
use App\Jobs\SaveTransactionJob;
use App\Jobs\SendEmailJob;
use App\Models\BeatMembership;
use App\Jobs\FreezeMembershipJob;
use App\Models\BeatAttendanceMonitoring;
use App\Jobs\DeletePrevillage;

class BeatAppCustomer extends Controller
{
    public function getCustomers(){
        $customers = BeatCustomer::with('membershipType', 'attendanceMonitorings', 'CustomerPaymentTransactions')->get();
        return response()->json($customers);
    }

    public function getCustomerById($id){
        $customer = BeatCustomer::with('membershipType', 'attendanceMonitorings', 'CustomerPaymentTransactions')->find($id);
        if ($customer) {
            return response()->json($customer);
        } else {
            return response()->json(['message' => 'Customer not found'], 404);
        }
    }
    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'gender' => 'required', // 0=female,1=male,2=other
            'age' => 'nullable|integer|min:0|max:120',
            'address' => 'nullable|string|max:255',
            'email' => 'required|email|unique:beat_customers,email',
            'phone_number' => 'nullable|string|max:20',
            'rfid_number' => 'nullable|string|max:50|unique:beat_customers,keypab',
            'membership_type' => 'required|exists:beat_memberships,id',
            'medical_conditions' => 'nullable|string',
            'profile' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
        ]);
        Log::info('Creating new customer', $request->all()); 
        if ($validator->fails()) {
            Log::error('Validation failed for creating customer', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        if($request->gender == 'Male'){
            $genderValue = 1;
        } elseif($request->gender == 'Female'){
            $genderValue = 0;
        } else {
            $genderValue = 2;
        }

        if($request->hasFile('profile')){
            $file = $request->file('profile');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('profiles', $file, $filename);
        } else {
            $filename = null;
        }
        $customer = BeatCustomer::create([
            'firstname' => $request->first_name,
            'lastname' => $request->last_name,
            'middlename' => $request->middle_name,
            'gender' => $genderValue,
            'birthdate' => $request->dob,
            'age' => $request->age,
            'address' => $request->address,
            'email' => $request->email,
            'phone' => $request->phone_number,
            'keypab' => $request->rfid_number,
            'membership_id' => $request->membership_type,
            'membership_start' => $request->start_membership_date,
            'profile_picture' => $filename,
            'medical_condition' => $request->medical_conditions,
            'status' => 1,
        ]);
           

        return response()->json([
            'success' => true,
            'message' => 'Customer successfully created.'
        ], 201);

        
    }

    public function update(Request $request, $id){
        Log::info('Updating customer with ID: ' . $id, $request->all());
        $customer = BeatCustomer::find($id);
        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'gender' => 'required', // 0=female,1=male,2=other
            'age' => 'nullable|integer|min:0|max:120',
            'address' => 'nullable|string|max:255',
            'email' => 'required|email|unique:beat_customers,email,'.$id,
            'phone_number' => 'nullable|string|max:20',
            'rfid_number' => 'nullable|string|max:50|unique:beat_customers,keypab,'.$id,
            'membership_type' => 'required|exists:beat_memberships,id',
            'medical_conditions' => 'nullable|string',
        ]); 

        if ($validator->fails()) {
            Log::error('Validation failed for updating customer ID ' . $id, $validator->errors()->toArray());   
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        if($request->gender == 'Male'){
            $genderValue = 1;
        } elseif($request->gender == 'Female'){
            $genderValue = 0;
        } else {
            $genderValue = 2;
        }

       
        $customer->firstname = $request->first_name;
        $customer->lastname = $request->last_name;
        $customer->middlename = $request->middle_name;
        $customer->gender = $genderValue;
        $customer->birthdate = $request->dob;
        $customer->age = $request->age;
        $customer->address = $request->address;
        $customer->email = $request->email;
        $customer->phone = $request->phone_number;
        $customer->keypab = $request->rfid_number;
        $customer->membership_id = $request->membership_type;
        $customer->membership_start = $request->start_membership_date;
        $customer->medical_condition = $request->medical_conditions;
        $customer->save();

        return response()->json([
            'success' => true,
            'message' => 'Customer successfully updated.'
        ], 200);
        
    }
    public function destroy($id){
        $customer = BeatCustomer::find($id);
        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }
        try {
            $customer->delete();
            return response()->json(['message' => 'Customer successfully deleted'], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete customer',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function addPaymentTransaction(Request $request){

        Log::info('Adding payment transaction', $request->all());
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:beat_customers,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|max:50',
            'remarks' => 'nullable|string|max:255',
            'new_expiration_date' => 'required|date',
        ]);
        $customer = BeatCustomer::find($request->customer_id);
        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        // /generate transaction id
        $transactionId = 'BEAT-' . $request->customer_id . '-' . Str::upper(Str::random(8));

        $transaction = CustomerPaymentTransaction::create([
            'transaction_id' => $transactionId,
            'customer_id' => $request->customer_id,
            'amount' => $request->amount,
            'payment_date' => $request->payment_date,
            'payment_method' => $request->payment_method,
            'status' => 'completed',
            'reference' => null,
            'notes' => $request->remarks,
            'added_by' => 1,
        ]);


        $beatCustomer = BeatCustomer::find($request->customer_id);
        $beatCustomer->membership_end = $request->new_expiration_date;
        $beatCustomer->save();

        $membershipType = $beatCustomer->membership_id;
        $membershipTypeDetails = BeatMembership::find($membershipType)->name;
        // kent
        $DataForSaving =[
            "card_number" => (int)$beatCustomer->keypab,
            "start_date" => $beatCustomer->membership_end,
            "end_date" => $request->new_expiration_date,
        ];


        $dataForEmail = [
            'email' => $beatCustomer->email,
            'subject' => 'Payment Receipt',
            'amount' => $request->amount,
            "start_date" => $beatCustomer->membership_end,
            "new_expiration_date" => $request->new_expiration_date,
            "transaction_id" => $transactionId,
            "Plan Type" => $membershipTypeDetails,
            "payment_method" => $request->payment_method,
        ];

        $this->addAndActivatePrivilege($beatCustomer->keypab, $beatCustomer->membership_start, $beatCustomer->membership_end);

        // // Dispatch job to save transaction (asynchronously — don't wait for it to finish)
        // // This will enqueue the job and return immediately; run a queue worker to process jobs.
        // SaveTransactionJob::dispatch($DataForSaving);

        // // // Dispatch job to send email
        // // SendEmailJob::dispatchSync($dataForEmail);






        return response()->json([
            'success' => true,
            'message' => 'Transaction successfully added.'
        ], 201);
    }

    public function freezeMembership($id, Request $request){
        Log::info('Freezing membership for customer ID: ' . $id, $request->all());
        $customer = BeatCustomer::find($id);
        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }
        $validator = Validator::make($request->all(), [
            'months' => 'required|integer|min:1|max:12',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed for freezing membership of customer ID ' . $id, $validator->errors()->toArray());   
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        

        $customer->is_frozen = true;
        // Cast months to int to avoid Carbon type errors when a string is passed.
        $monthsToAdd = (int) $request->months;
        // If membership_end exists, extend it; otherwise set to now + months.
        if ($customer->membership_end) {
            $customer->membership_end = Carbon::parse($customer->membership_end)->addMonths($monthsToAdd);
        } else {
            $customer->membership_end = Carbon::now()->addMonths($monthsToAdd);
        }
        $customer->save();
        $keypab = $customer->keypab;
        FreezeMembershipJob::dispatch($keypab);

        return response()->json([
            'success' => true,
            'message' => 'Membership successfully frozen for ' . $request->months . ' months.'
        ], 200);
    }

    public function unfreezeMembership($id){
        Log::info('Unfreezing membership for customer ID: ' . $id);
        $customer = BeatCustomer::find($id);
        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $customer->is_frozen = false;
        $customer->save();

        return response()->json([
            'success' => true,
            'message' => 'Membership successfully unfrozen.'
        ], 200);
    }

    public function terminateMembership($id){
        Log::info('Terminating membership for customer ID: ' . $id);
        $customer = BeatCustomer::find($id);
        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $customer->is_terminated = true;
        $customer->status = 0; // Set status to inactive
        $customer->save();

        return response()->json([
            'success' => true,
            'message' => 'Membership successfully terminated.'
        ], 200);
    }

    public function attendanceMonitoring($keypab){
        $customerID = BeatCustomer::where('keypab', $keypab)->first();


        // use asiamanila time zone then save to ATtdance
        Log::info('Starting attendance monitoring for keyfob: ' . $keypab);
        $attendance = BeatAttendanceMonitoring::create([
            'beat_customer_id' => $customerID->id,
            'attendance_date' => Carbon::now('Asia/Manila')->toDateString(),
            'check_in_time' => Carbon::now('Asia/Manila')->toTimeString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attendance monitoring started for keyfob: ' . $keypab
        ], 200);
    }
    private function toBcdByte(int $v): int
    {
        // integer division and remainder; produce single byte value
        $tens = intdiv($v, 10);
        $ones = $v % 10;
        return ($tens << 4) | $ones;
    }
    private function addAndActivatePrivilege($cardNumber, $startDate, $endDate)
    {
        $ip = env('IP_DOOR_CONTROLLER', '192.168.1.10');
        $controllerIp = $ip;
        $port = env('DATASERVERPORT', 6000);
        $sn = env('SN_DOOR_CONTROLLER', 12345678);
        $cardNo = $cardNumber;

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();
        Log::info('Adding and activating privilege for card number: ' . $cardNo . ' from ' . $start->toDateString() . ' to ' . $end->toDateString());

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
        socket_sendto($sock, $packet, strlen($packet), 0, $ip, $port);

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
                Log::warning('Controller ignored 0x56 activation but card is active', [
                    'card_number' => $cardNo,
                    'response_hex_0x50' => $respHex,
                    'response_hex_0x56' => $resp56Hex,
                ]);
                return true;
                // return response()->json([
                //     'status' => 'success',
                //     'message' => 'Privilege added. Controller ignored 0x56 but card is active (normal for some firmwares).',
                //     'card_number' => $cardNo,
                //     'response_hex_0x50' => $respHex,
                //     'response_hex_0x56' => $resp56Hex,
                // ]);
            }else{
                Log::info('Privilege added and activated successfully', [
                    'card_number' => $cardNo,
                    'response_hex_0x50' => $respHex,
                    'response_hex_0x56' => $resp56Hex,
                ]);
                return true;
                // return response()->json([
                //     'status' => 'success',
                //     'message' => 'Privilege added and activated successfully.',
                //     'card_number' => $cardNo,
                //     'response_hex_0x50' => $respHex,
                //     'response_hex_0x56' => $resp56Hex,
                // ]);
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
}
