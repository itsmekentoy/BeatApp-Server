<?php

use Illuminate\Http\Request;
use App\Http\Controllers\BeatAppCustomer;
use App\Http\Controllers\MembershipController;  
use Illuminate\Support\Facades\Route;
use App\Http\controllers\BeatAuthentication;
use App\Http\Controllers\BeatMembership as BMembership;
use App\Http\Controllers\BeatExpensesController;
use App\Http\Controllers\ProductManagementController;
use App\Http\Controllers\EmailManagementController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\SellingProduct;
use App\Http\Controllers\DoorAccessController;
use App\Http\Controllers\KeyfobController;
use App\Http\Controllers\BeatDashboardController;

    
Route::get('/hello', function (Request $request) {
    return response()->json(['message' => 'Hello, World!']);
});

Route::get('/dashboard', [BeatDashboardController::class, 'Dashboard']);

Route::get('/customers', [BeatAppCustomer::class, 'getCustomers']);
Route::get('/customer/{id}', [BeatAppCustomer::class, 'getCustomerById']);
Route::post('/customer/add', [BeatAppCustomer::class, 'store']);
Route::post('/customer/update/{id}', [BeatAppCustomer::class, 'update']);
Route::delete('/customer/delete/{id}', [BeatAppCustomer::class, 'destroy']);
Route::post('/customer/payment', [BeatAppCustomer::class, 'addPaymentTransaction']);
Route::post('/customer/freeze/{id}', [BeatAppCustomer::class, 'freezeMembership']);
Route::get('/customer/unfreeze/{id}', [BeatAppCustomer::class, 'unfreezeMembership']);
Route::get('/customer/terminate/{id}', [BeatAppCustomer::class, 'terminateMembership']);
Route::get('/customer/attendance-monitoring/{keypab}/checkin', [BeatAppCustomer::class, 'attendanceMonitoring']);


Route::get('/memberships', [MembershipController::class, 'GetMembership']);

Route::get('/CheckConnection',[BeatAuthentication::class,'CheckingConnection']);
Route::post('/LoginAccount',[BeatAuthentication::class,'LoginSystem']);

Route::controller(BMembership::class)->group(function () {
    Route::get('/MembershipPlans', 'getMembershipPlans');
    Route::post('/MembershipPlan/add', 'storeMembershipPlan');
    Route::put('/MembershipPlan/update/{id}', 'updateMembershipPlan');
    Route::delete('/MembershipPlan/delete/{id}', 'destroyMembershipPlan');
});

Route::controller(BeatExpensesController::class)->group(function () {
    Route::get('/expenses', 'index');
    Route::post('/expenses/add', 'store');
    Route::put('/expenses/{id}', 'update');
    Route::delete('/expenses/{id}', 'destroy');
});

Route::controller(ProductManagementController::class)->group(function () {
    Route::get('/products', 'index');
    Route::post('/products/add', 'create');
    Route::post('/products/update/{productID}', 'Update');
    Route::delete('/products/delete/{productID}', 'destroy');
});

Route::controller(EmailManagementController::class)->group(function () {
    Route::get('/email-management', 'index');
    Route::post('/email-management/create-or-update', 'createOrUpdate');
});

Route::controller(UserManagementController::class)->group(function () {
    Route::get('/users', 'index');
    Route::post('/users/add', 'store');
    Route::post('/users/update/{id}', 'update');
    Route::delete('/users/delete/{id}', 'destroy');
});

Route::controller(SellingProduct::class)->group(function () {
    Route::get('/sold-products', 'index');
    Route::post('/sell-product', 'store');
});

Route::controller(DoorAccessController::class)->group(function () {
    Route::post('/open-door', 'OpenDoor');
    Route::get('/search-controller', 'searchController');
    Route::get('/getID', 'listen');
    Route::get('/set-controller-time', 'SetControllerTime');
    Route::post('/SavePrivilege', 'addPrivilege');
    Route::get('/get-card-info/{cardid}', 'getCardInfo');
    Route::post('/getControllerInfo', 'getControllerInfo');
    Route::post('/refresh-privileges', 'refreshPrivileges');
    Route::post('/upload-single-privilege-block', 'uploadSinglePrivilegeBlock');
});

Route::controller(KeyfobController::class)->group(function () {
    Route::post('/add-and-activate-privilege', 'addAndActivatePrivilege');
    Route::get('/listen-swipe', 'listenSwipe');
    Route::post('/delete-privilege', 'deletePrivilege');
    Route::get('/download-all-privileges', 'downloadAllPrivileges');
});