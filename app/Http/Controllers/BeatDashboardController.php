<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BeatCustomer;
use App\Models\CustomerPaymentTransaction;
use App\Models\BeatExpesense;
use App\Models\BeatAttendanceMonitoring;
use App\Models\SoldProduct;

class BeatDashboardController extends Controller
{
    public function Dashboard()
    {
        $BeatCustomers = BeatCustomer::with('attendanceMonitorings')->get();
        $totalSales = CustomerPaymentTransaction::where('status', 'completed')
            ->whereMonth('payment_date', now()->month)
            ->sum('amount');
        $sumProductSales = SoldProduct::whereMonth('created_at', now()->month)
            ->sum('sub_total');
        $totalSales += $sumProductSales;

        $totalExpenses = BeatExpesense::whereMonth('expense_date', now()->month)
            ->sum('amount');

        $netIncome = $totalSales - $totalExpenses;

        $countofActiveMembers = BeatCustomer::where('status', 1)
            ->where('is_terminated', '!=', 1)
            ->where('is_frozen', '!=', 1)
            ->count();
        $todayCheckin = BeatAttendanceMonitoring::whereDate('attendance_date', now()->toDateString())
            ->count();

        // get the expiring memberships in the next 7 days by membership_end
        $expiringMemberships = BeatCustomer::whereBetween('membership_end', [now(), now()->addDays(7)])->get();
        $latestCheckins = BeatAttendanceMonitoring::with('beatCustomer')
            ->whereDate('attendance_date', now()->toDateString())
            ->orderBy('check_in_time', 'desc')
            ->take(10)
            ->get();


        return response()->json([
            'total_customers' => $BeatCustomers->count(),
            'total_sales' => $totalSales,
            'total_expenses' => $totalExpenses,
            'net_income' => $netIncome,
            'active_members_count' => $countofActiveMembers,
            'today_checkin_count' => $todayCheckin,
            'expiring_memberships' => $expiringMemberships,
            'latest_checkins' => $latestCheckins,
            
        ]);

    }
}
    