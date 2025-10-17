<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BeatCustomer;
use App\Models\CustomerPaymentTransaction;
use App\Models\BeatExpesense;

class BeatDashboardController extends Controller
{
    public function Dashboard()
    {
        $BeatCustomers = BeatCustomer::with('attendanceMonitorings')->get();
        $totalSales = CustomerPaymentTransaction::where('status', 'completed')
            ->whereMonth('payment_date', now()->month)
            ->sum('amount');

        $totalExpenses = BeatExpesense::whereMonth('expense_date', now()->month)
            ->sum('amount');

        // get the expiring memberships in the next 7 days by membership_end
        $expiringMemberships = BeatCustomer::whereBetween('membership_end', [now(), now()->addDays(7)])->get();

        return response()->json([
            'total_customers' => $BeatCustomers->count(),
            'total_sales' => $totalSales,
            'total_expenses' => $totalExpenses,
            'expiring_memberships' => $expiringMemberships,
        ]);

    }
}
    