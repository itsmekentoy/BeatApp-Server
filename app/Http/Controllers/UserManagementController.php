<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::with('permissions')->get();
        return response()->json($users);
    }
    public function store(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
           
        ]);

        if ($validatedData->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validatedData->errors()
            ], 422);
        }
        $permissionList = [
            "1" => "View Financial Overview",
            "2" => "Membership Plan",
            "3" => "Add Customer",
            "4" => "Edit Customer",
            "5" => "Delete Customer",
            "6" => "Add Transaction",
            "7" => "Add Expense",
            "8" => "Edit Expense",
            "9" => "Delete Expense",
            "10" => "Product Management",
            "11" => "Email Management",
        ];
        Log::info('User creation validation', ['data' => $request->all()]);

        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password')),
        ]);

        if ($request->has('permissions')) {
            foreach ($request->input('permissions') as $perm) {
                UserPermission::create([
                    'user_id' => $user->id,
                    'permission' => $perm['permission'], // e.g. "1"
                    'permission_name' => $permissionList[$perm['permission']] ?? null,
                    'is_granted' => $perm['is_granted'], // include if you have this column
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'User created successfully'
            // 'user' => $user->load('permissions')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $validatedData = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
        ]);

        if ($validatedData->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validatedData->errors()
            ], 422);
        }

        $permissionList = [
            "1" => "View Financial Overview",
            "2" => "Membership Plan",
            "3" => "Add Customer",
            "4" => "Edit Customer",
            "5" => "Delete Customer",
            "6" => "Add Transaction",
            "7" => "Add Expense",
            "8" => "Edit Expense",
            "9" => "Delete Expense",
            "10" => "Product Management",
            "11" => "Email Management",
        ];

        // ✅ Update user details
        $user->update([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            // only update password if provided
            'password' => $request->filled('password') ? bcrypt($request->input('password')) : $user->password,
        ]);
        
        $permissions = $request->input('permission');
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true);
        }
        // ✅ Update permissions
        if ($request->has('permissions')) {
            foreach ($request->input('permissions') as $perm) {
                UserPermission::updateOrCreate(
                    ['user_id' => $user->id, 'permission' => $perm['permission']],
                    [
                        'permission_name' => $permissionList[$perm['permission']] ?? null,
                        'is_granted' => $perm['is_granted'],
                    ]
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            // 'user' => $user->load('permissions') // optional
        ]);
    }


    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        // Delete associated permissions first
        UserPermission::where('user_id', $user->id)->delete();

        // Then delete the user
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);
    }
}
