<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BeatExpense;
use Illuminate\Support\Facades\Validator;


class BeatExpensesController extends Controller
{
    public function index()
    {
        $expenses = BeatExpense::all();
        return response()->json($expenses);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'expense_type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'expense_date' => 'required|date',
            'amount' => 'required|numeric',
            'added_by' => 'nullable|integer',
            'updated_by' => 'nullable|integer',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $expense = BeatExpense::create(
            $request->only([
                'expense_type',
                'description',
                'expense_date',
                'amount',
                'added_by',
                'updated_by',
            ])
        );
        return response()->json($expense, 201);
    }

    public function show($id)
    {
        $expense = BeatExpense::find($id);
        if (!$expense) {
            return response()->json(['message' => 'Expense not found'], 404);
        }
        return response()->json($expense);
    }

    public function update(Request $request, $id)
    {
        $expense = BeatExpense::find($id);
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
        $expense = BeatExpense::find($id);
        if (!$expense) {
            return response()->json(['message' => 'Expense not found'], 404);
        }
        $expense->delete();
        return response()->json(['message' => 'Expense deleted successfully']);
    }
}
