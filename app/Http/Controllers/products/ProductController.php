<?php

namespace App\Http\Controllers\products;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Lcobucci\JWT\Exception;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $products = Product::whereIsActive(1)->get();
            return response()->json([
                'success' => true,
                'message' => 'all products listed',
                'products' => $products
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validatedData = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|between:0,9999999.99',
            'stock_quantity' => 'required|integer'
        ]);
        try {
            $data= $request->all();
            $data['category_title'] = Category::whereId($request->category_id)->value('title');
            $addedProduct = Product::create($data);
            return response()->json([
                'success' => true,
                'message' => 'Product addition successful',
                'addedProduct' => $addedProduct
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function selectedProduct(Product $product)
    {
        try {
            if ($product->is_active === 1) {
                return response()->json([
                    'success' => true,
                    'message' => 'selected product listed',
                    'selectedProduct' => $product
                ], 200);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'selected product not active'
                ], 200);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        try {
            $data = $request->all();

            if ($request->has('category_id')) {
                $data['category_title'] = Category::whereId($request->category_id)->value('title');
            }
           $product->update($data);
            return response()->json([
                'success' => true,
                'message' => 'product update successful',
                'updatedProduct' => $product
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => true,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        try {
            $product->delete();
            return response()->json([
                'success' => true,
                'message' => 'product delete successful',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => true,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
