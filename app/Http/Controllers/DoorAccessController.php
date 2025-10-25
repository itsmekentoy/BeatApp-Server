<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\BeatAttendanceMonitoring;
use App\Models\BeatCustomer;

class DoorAccessController extends Controller
{
    public function OpenDoor($DoorID)
    {
        // Logic to open the door
        return response()->json(['message' => 'Door opened successfully']);
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
