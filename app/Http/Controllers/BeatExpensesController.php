<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BeatExpesense;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BeatExpensesController extends Controller
{
    public function index()
    {
        $expenses = BeatExpesense::orderBy('expense_date', 'desc')->get();
        return response()->json($expenses);
    }

    public function store(Request $request)
    {
        $data = $request->all();

        // ✅ Convert expense_date to date only (YYYY-MM-DD)
        if (isset($data['expense_date'])) {
            try {
                $data['expense_date'] = Carbon::parse($data['expense_date'])->format('Y-m-d');
            } catch (\Exception $e) {
                return response()->json(['expense_date' => ['Invalid date format']], 422);
            }
        }

        Log::info('Storing new expense', $data);

        $validator = Validator::make($data, [
            'expense_type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'expense_date' => 'required|date',
            'amount' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $expense = BeatExpesense::create([
            'expense_type' => $data['expense_type'],
            'description' => $data['description'] ?? null,
            'expense_date' => $data['expense_date'], // ✅ Only date (e.g. 2025-10-21)
            'amount' => $data['amount'],
            'added_by' => $request->user()->id ?? null,
            'updated_by' => $request->user()->id ?? null,
        ]);

        return response()->json($expense, 201);
    }
   

    public function show($id)
    {
        $expense = BeatExpesense::find($id);
        if (!$expense) {
            return response()->json(['message' => 'Expense not found'], 404);
        }
        return response()->json($expense);
    }

    public function update(Request $request, $id)
    {
        $expense = BeatExpesense::find($id);
        if (!$expense) {
            return response()->json(['message' => 'Expense not found'], 404);
        }
        $validator = Validator::make($request->all(), [
            'expense_type' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'expense_date' => 'sometimes|required|date',
            'amount' => 'sometimes|required|numeric',
            'added_by' => 'nullable|integer',
            'updated_by' => 'nullable|integer',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $expense->update(
            $request->only([
                'expense_type',
                'description',
                'expense_date',
                'amount',
                'added_by',
                'updated_by',
            ])
        );
        return response()->json($expense);
    }

    public function destroy($id)
    {
        $expense = BeatExpesense::find($id);
        if (!$expense) {
            return response()->json(['message' => 'Expense not found'], 404);
        }
        $expense->delete();
        return response()->json(['message' => 'Expense deleted successfully']);
    }
}
