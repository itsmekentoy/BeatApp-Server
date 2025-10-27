<?php

namespace App\Http\Controllers;

use App\Models\EmailManagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class EmailManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //get the first email management record'
        $emailManagement = EmailManagement::first();
        return response()->json($emailManagement);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function createOrUpdate(Request $request)
    {
        //check if there is an existing record if not create one if exists update it
        $validatedData = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|max:255',
            
        ]);
        if ($validatedData->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validatedData->errors()
            ], 422);
        }

       



        $emailManagement = EmailManagement::first();
        if ($emailManagement) {
            //update
            $emailManagement->update([
                'email' => $request->input('email'),
                'password' => $request->input('password'),
                
                
            ]);
        } else {
            //create
            $emailManagement = EmailManagement::create([
                'email' => $request->input('email'),
                'password' => $request->input('password'),
            ]);
        }
    }

    
}
