<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\TransactionSoldProduct;
use App\Models\SoldProduct;
use App\Models\ProductManagement;

class SellingProduct extends Controller
{
    public function index()
    {
       $transactions = TransactionSoldProduct::with('soldProducts.product')
        ->orderBy('created_at', 'desc')
        ->get();

        // Transform the response
        $formatted = $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'customer_name' => $transaction->customer_name,
                'email' => $transaction->email,
                'total_amount' => $transaction->total_amount,
                'created_at' => $transaction->created_at,
                'items' => $transaction->soldProducts->map(function ($sold) {
                    return [
                        'product_id' => $sold->product_id,
                        'product_name' => $sold->product->product_name ?? null,
                        'quantity' => $sold->quantity,
                        'sub_total' => $sold->sub_total,
                        'category' => $sold->product->category ?? null,
                        'price' => $sold->product->price ?? null,
                    ];
                }),
            ];
        });

        return response()->json(['transactions' => $formatted]);
    }
    public function store(Request $request)
    {
        $data = $request->all();

        Log::info('Product creation validation', ['data' => $data]);

       $grandTotal = collect($data['items'])->sum('total_price');
       
       // ✅ Save transaction
       $transaction = TransactionSoldProduct::create([
           'customer_name' => $data['customer_name'],
           'email' => $data['customer_email'] ?? null,
           'total_amount' => $grandTotal,
       ]);
         // ✅ Save sold products
        foreach ($data['items'] as $item) {
            SoldProduct::create([
                'transaction_id' => $transaction->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'sub_total' => $item['total_price'],
            ]);
            // ✅ Update stock quantity
            $product = ProductManagement::find($item['product_id']);
            if ($product) {
                $product->decrement('stock_quantity', $item['quantity']);
                $product->save();
            }
        }


        return response()->json([
            'success' => true,
            'message' => 'Product sold successfully',
        ], 201);
    }
}
