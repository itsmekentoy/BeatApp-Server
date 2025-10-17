<?php

use Illuminate\Http\Request;
use App\Http\Controllers\BeatAppCustomer;
use App\Http\Controllers\MembershipController;  
use Illuminate\Support\Facades\Route;




    
Route::get('/hello', function (Request $request) {
    return response()->json(['message' => 'Hello, World!']);
});

Route::get('/customers', [BeatAppCustomer::class, 'getCustomers']);
Route::get('/customer/{id}', [BeatAppCustomer::class, 'getCustomerById']);
Route::post('/customer/add', [BeatAppCustomer::class, 'store']);
Route::put('/customer/update/{id}', [BeatAppCustomer::class, 'update']);
Route::delete('/customer/delete/{id}', [BeatAppCustomer::class, 'destroy']);


Route::get('/memberships', [MembershipController::class, 'GetMembership']);
