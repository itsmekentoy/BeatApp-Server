<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BeatMembership as BeatMembershipplan;
use Illuminate\Support\Facades\Validator;


class BeatMembership extends Controller
{
    public function getMembershipPlans(){
        $memberships = BeatMembershipplan::all();
        return response()->json($memberships);
    }

    public function storeMembershipPlan(Request $request){
        $validatedData = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'duration_days' => 'required|integer',
        ]);
        if ($validatedData->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validatedData->errors()
            ], 422);
        }
        $membership = BeatMembershipplan::create([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'price' => $request->input('price'),
            'duration_days' => $request->input('duration_days'),
            'status' => '1',
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Membership plan created successfully',
            'membership' => $membership
        ], 201);
    }

    public function updateMembershipPlan(Request $request, $id){
        $membership = BeatMembershipplan::find($id);
        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'Membership plan not found'
            ], 404);
        }
        $validatedData = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'price' => 'sometimes|required|numeric',
            'duration_days' => 'sometimes|required|integer',
        ]);
        if ($validatedData->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validatedData->errors()
            ], 422);
        }
        $membership->update($request->only(['name', 'description', 'price', 'duration_days']));
        return response()->json([
            'success' => true,
            'message' => 'Membership plan updated successfully',
            'membership' => $membership
        ], 200);
    }

    public function destroyMembershipPlan($id){
        $membership = BeatMembershipplan::find($id);
        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'Membership plan not found'
            ], 404);
        }
        $membership->delete();
        return response()->json([
            'success' => true,
            'message' => 'Membership plan deleted successfully'
        ], 200);
    }
}
