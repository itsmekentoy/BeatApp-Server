<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
class BeatAuthentication extends Controller
{
    public function CheckingConnection(){
        return response()->json([
            'success' => true,
            'message' => "Successfully connected to server"
        ],200);
    }

    public function LoginSystem(Request $request){
        $validatedData = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        if ($validatedData->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validatedData->errors()
            ], 422);
        }

        $email = $request->input('email');
        $password = $request->input('password');

        $user = User::where('email', $email)->first();

        if ($user && password_verify($password, $user->password)) {
            return response()->json([
                'success' => true,
                'message' => 'Login successful',        
                'user' => $user,
                'permissions' => $user->permissions
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password'
            ], 401);
        }


    }
}
