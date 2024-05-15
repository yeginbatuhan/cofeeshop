<?php

namespace App\Http\Controllers\categories;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Lcobucci\JWT\Exception;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $categories = Category::whereIsActive(1)->get();
            return response()->json([
                'success' => true,
                'message' => 'all categories listed',
                'categories' => $categories
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function selectedCategoryProduct(Category $category)
    {
        try {
            if ($category->is_active == 1) {
                $selectedCategoryProduct = Product::whereCategoryId($category->id)->whereIsActive(1)->get();
                return response()->json([
                    'success' => true,
                    'message' => 'all products of the selected category listed',
                    'selectedCategoryProduct' => $selectedCategoryProduct
                ], 200);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'selected category is not active'
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
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255'
        ]);
        try {
            $addedCategory = Category::create($validatedData);
            return response()->json([
                'success' => true,
                'message' => 'Category addition successful',
                'addedCategory' => $addedCategory
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        try {
            $data = $request->all();
            $updatedCategory = $category->update($data);
            return response()->json([
                'success' => true,
                'message' => 'category update successful',
                'updatedCategory' => $category
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
    public function destroy(Category $category)
    {
        try {
            $category->delete();
            return response()->json([
                'success' => true,
                'message' => 'category delete successful',
            ], 200);
        } catch (Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 400);
        }
    }
}
