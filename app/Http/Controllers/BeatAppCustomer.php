<?php

namespace App\Http\Controllers;
use App\Models\BeatCustomer;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\BeatCustomerMedical;


class BeatAppCustomer extends Controller
{
    public function getCustomers(){
        $customers = BeatCustomer::with('membershipType')->get();
        return response()->json($customers);
    }

    public function getCustomerById($id){
        $customer = BeatCustomer::with('membershipType','attendanceMonitorings')->find($id);
        if ($customer) {
            return response()->json($customer);
        } else {
            return response()->json(['message' => 'Customer not found'], 404);
        }
    }
    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string|max:100',
            'lastname' => 'required|string|max:100',
            'middlename' => 'nullable|string|max:100',
            'gender' => 'required|in:0,1,2', // 0=female,1=male,2=other
            'birthdate' => 'nullable|date',
            'age' => 'nullable|integer|min:0|max:120',
            'address' => 'nullable|string|max:255',
            'email' => 'required|email|unique:beat_customers,email',
            'phone' => 'nullable|string|max:20',
            'phone2' => 'nullable|string|max:20',
            'membership_id' => 'required|exists:beat_memberships,id',
            'membership_start' => 'nullable|date',
            'membership_end' => 'nullable|date|after_or_equal:membership_start',
            'status' => 'nullable|in:0,1',
            'medical_conditions' => 'nullable|array',
            'medical_conditions.*' => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();
            $customer = BeatCustomer::create([
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'middlename' => $request->middlename,
                'gender' => $request->gender,
                'birthdate' => $request->birthdate,
                'age' => $request->age,
                'address' => $request->address,
                'email' => $request->email,
                'phone' => $request->phone,
                'phone2' => $request->phone2,
                'keypab' => strtoupper(Str::random(8)),
                'membership_id' => $request->membership_id,
                'membership_start' => $request->membership_start,
                'membership_end' => $request->membership_end,
                'status' => $request->status ?? 1,
                'created_by' => auth()->id() ?? 1,
                'updated_by' => auth()->id() ?? 1,
            ]); 
            if (!empty($request->medical_conditions)) {
                foreach ($request->medical_conditions as $condition) {
                    BeatCustomerMedical::create([
                        'beat_customer_id' => $customer->id,
                        'medical_condition' => $condition,
                    ]);
                }
            }

            DB::commit();

            // ✅ 5. Return success response
            return response()->json([
                'success' => true,
                'message' => 'Customer successfully created.',
                'data' => $customer->load('medicalRecords')
            ], 201);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create customer',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id){
        $customer = BeatCustomer::find($id);
        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'firstname' => 'sometimes|required|string|max:100',
            'lastname' => 'sometimes|required|string|max:100',
            'middlename' => 'nullable|string|max:100',
            'gender' => 'sometimes|required|string|in:male,female',
            'birthdate' => 'sometimes|required|date',
            'age' => 'sometimes|required|integer|min:0',
            'address' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:beat_customers,email,' . $customer->id,
            'phone' => 'sometimes|nullable|string|max:20',
            'phone2' => 'sometimes|nullable|string|max:20',
            'membership_id' => 'sometimes|required|exists:beat_memberships,id',
            'membership_start' => 'sometimes|nullable|date',
            'membership_end' => 'sometimes|nullable|date|after_or_equal:membership_start',
            'status' => 'sometimes|nullable|in:0,1',
            'medical_conditions' => 'sometimes|nullable|array',
            'medical_conditions.*' => 'sometimes|nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();
            $customer->update([
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'middlename' => $request->middlename,
                'gender' => $request->gender,
                'birthdate' => $request->birthdate,
                'age' => $request->age,
                
                'address' => $request->address,
                'email' => $request->email,
                'phone' => $request->phone,
                'phone2' => $request->phone2,
                'membership_id' => $request->membership_id,
                'membership_start' => $request->membership_start,
                'membership_end' => $request->membership_end,
                'status' => $request->status ?? 1,
                'updated_by' => auth()->id() ?? 1,
            ]);
            if (!empty($request->medical_conditions)) {
                foreach ($request->medical_conditions as $condition) {
                    BeatCustomerMedical::updateOrCreate(
                        ['beat_customer_id' => $customer->id, 'medical_condition' => $condition]
                    );
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Customer successfully updated.',
                'data' => $customer->load('medicalRecords')
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update customer',
                'error' => $th->getMessage()
            ], 500);
        }
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
}
