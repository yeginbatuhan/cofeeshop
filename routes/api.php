<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\carts\CartController;
use App\Http\Controllers\categories\CategoryController;
use App\Http\Controllers\products\ProductController;


/**
 * transactions that do not require login
 */
Route::group(['middleware' => ['checkCart']], function () {

    Route::controller(AuthController::class)->group(function () {
        Route::post('/login', 'login');
        Route::post('/register', 'register');
    });

    Route::controller(CartController::class)->group(function () {
        Route::post('cart/add', 'addToCart')->middleware('optional.auth');
        Route::post('cart/update/{cartDetail}', 'updateCart')->middleware('optional.auth');
        Route::delete('cart/remove/{cartDetail}', 'removeFromCart')->middleware('optional.auth');
        Route::post('cart/checkout/{cart}', 'checkoutCart')->middleware('optional.auth');
        Route::get('cart', 'getCart')->middleware('optional.auth');
    });
    Route::controller(CategoryController::class)->group(function () {
        Route::get('/categories', 'index');
        Route::get('/categories/{category}', 'selectedCategoryProduct');

    });

    Route::controller(ProductController::class)->group(function () {
        Route::get('/products', 'index');
        Route::get('/products/{product}', 'selectedProduct');
    });
    Route::controller(CartController::class)->group(function () {

    });

    /**
     * transactions requiring login
     */
    Route::group(['middleware' => ['auth:api']], function () {
        Route::get('/logout', [AuthController::class, 'logout']);
        Route::put('/user/update/{user}', [AuthController::class, 'update']);
        Route::get('/world', function () {
            return 'hi world!';
        });
        Route::controller(CartController::class)->group(function () {

        });


        /**
         * operations requiring admin login
         */
        Route::group(['middleware' => ['checkUserType']], function () {
            Route::controller(CategoryController::class)->group(function () {
                Route::post('/categories/store', 'store');
                Route::put('/categories/update/{category}', 'update');
                Route::delete('/categories/destroy/{category}', 'destroy');
            });
            Route::controller(ProductController::class)->group(function () {
                Route::post('/product/store', 'store');
                Route::put('/product/update/{product}', 'update');
                Route::delete('/product/destroy/{product}', 'destroy');
            });

        });
    });
});
