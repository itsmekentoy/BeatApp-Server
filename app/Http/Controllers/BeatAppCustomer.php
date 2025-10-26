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
            "card_number" => $beatCustomer->keypab,
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

        // Dispatch job to save transaction (asynchronously — don't wait for it to finish)
        // This will enqueue the job and return immediately; run a queue worker to process jobs.
        SaveTransactionJob::dispatch($DataForSaving);

        // // Dispatch job to send email
        // SendEmailJob::dispatchSync($dataForEmail);






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
}
