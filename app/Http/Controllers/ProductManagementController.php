<?php

namespace App\Http\Controllers;

use App\Models\ProductManagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;


class ProductManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
     
    public function index()
    {
        $product = ProductManagement::all();
        return response()->json($product);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $ValidatedData = Validator::make($request->all(), [
            'product_name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|string',
            'stock_quantity' => 'required|string',
        ]);
        Log::info('Product creation validation', ['data' => $ValidatedData->validated()]);
        if ($ValidatedData->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $ValidatedData->errors()
            ], 422);
        }

        $product = ProductManagement::create([
            'product_name' => $request->input('product_name'),
            'category' => $request->input('category'),
            'description' => $request->input('description'),
            'price' => $request->input('price'),
            'stock_quantity' => $request->input('stock_quantity'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'product' => $product
        ], 201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function Update(Request $request, $productID)
    {
        $ValidatedData = Validator::make($request->all(), [
            'product_name' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'price' => 'sometimes|required|numeric',
            'stock_quantity' => 'sometimes|required|integer',
        ]);
        if ($ValidatedData->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $ValidatedData->errors()
            ], 422);
        }

        $product = ProductManagement::find($productID);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $product->update($request->only([
            'product_name',
            'category',
            'description',
            'price',
            'stock_quantity'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'product' => $product
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($productID)
    {
        $product = ProductManagement::find($productID);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }
}
