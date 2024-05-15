<?php

namespace App\Http\Controllers\carts;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Cart;
use App\Models\CartDetail;

class CartController extends Controller
{
    public function generateCartCode()
    {
        return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(8/strlen($x)) )),1,8);
    }

    public function addToCart(Request $request)
    {
        $productId = $request->input('product_id');
        $count = $request->input('count');
        $cartCode = $request->header('X-Cart-Code') ?: $this->generateCartCode();
        $product = Product::findOrFail($productId);
        if ($product->stock_quantity == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Product is out of stock'
            ], 400);
        }
        $cart = Auth::check() ? Cart::where('user_id', Auth::id())->where('check_card', 0)->first() : Cart::where('cart_code', $cartCode)->where('check_card', 0)->first();

        if (!$cart) {
            $cart = new Cart();
            if (Auth::check()) {
                $cart->user_id = Auth::id();
                $cart->cart_code = $cartCode;
            } else {
                $cart->cart_code = $cartCode;
            }
            $cart->save();
        }

        $cartDetail = $cart->cartDetails()->where('product_id', $productId)->first();
        if ($cartDetail) {
            if ($product->stock_quantity < $cartDetail->count + $count) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not enough stock available'
                ], 400);
            }
            $cartDetail->count += $count;
        } else {
            if ($product->stock_quantity < $count) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not enough stock available'
                ], 400);
            }
            $cartDetail = new CartDetail([
                'product_id' => $productId,
                'count' => $count,
                'price' => $product->price,
            ]);
        }
        $product->stock_quantity -= $count;
        $product->save();
        $cart->cartDetails()->save($cartDetail);

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart',
            'cart_code' => $cart->cart_code ?: $cartCode,
            'cart' => $cartDetail,
            'total_price' => $cart->total_price
        ], 201);
    }


    public function updateCart(Request $request, CartDetail $cartDetail)
    {
        $newCount  = $request->input('count');
        $cartCode = $request->header('X-Cart-Code');

        if ($cartDetail->cart->check_card == 1) {
            return response()->json([
                'success' => false,
                'message' => 'the cart has been approved, you cannot make a transaction'
            ], 403);
        }

        if (Auth::check()) {
            if ($cartDetail->cart->user_id !== Auth::id()) {
                return response()->json(['success' => false,'message' => 'Unauthorized'], 403);
            }
        } else if (!$cartCode || $cartDetail->cart->cart_code !== $cartCode) {
            return response()->json(['success' => false,'message' => 'Unauthorized'], 403);
        }

        $product = $cartDetail->products;
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }
        $currentCount = $cartDetail->count;
        $countDifference = $newCount - $currentCount;
        if ($countDifference > 0) {
            if ($product->stock_quantity < $countDifference) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not enough stock available'
                ], 400);
            }
            $product->stock_quantity -= $countDifference;
        } else {
            $product->stock_quantity += abs($countDifference);
        }
        $product->save();
        $cartDetail->count = $newCount;
        $cartDetail->save();

        return response()->json([
            'success' => true,
            'message' => 'Cart updated',
            'cart' => $cartDetail,
            'total_price' => $cartDetail->cart->total_price
        ], 200);
    }

    public function removeFromCart(Request $request, CartDetail $cartDetail)
    {
        $cartCode = $request->header('X-Cart-Code');

        if ($cartDetail->cart->check_card == 1) {
            return response()->json([
                'success' => false,
                'message' => 'the cart has been approved, you cannot make a transaction'
            ], 403);
        }
        if (Auth::check()) {
            if ($cartDetail->cart->user_id !== Auth::id()) {
                return response()->json(['success' => false,'message' => 'Unauthorized'], 403);
            }
        } else if (!$cartCode || $cartDetail->cart->cart_code !== $cartCode) {
            return response()->json(['success' => false,'message' => 'Unauthorized'], 403);
        }
        $product = $cartDetail->products;
        $count = $cartDetail->count;
        $product->stock_quantity += $count;
        $product->save();
        $cart = $cartDetail->cart;
        $cartDetail->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product removed from cart',
            'total_price' => $cart->total_price
        ], 200);
    }
    public function checkoutCart(Request $request , Cart $cart)
    {
        $cartCode = $request->header('X-Cart-Code');
        if (Auth::check()) {
            if ($cart->user_id !== \auth()->id()) {
                return response()->json(['success' => false,'message' => 'Unauthorized'], 403);
            }
        } else if (!$cartCode || $cart->cart_code !== $cartCode) {
            return response()->json(['success' => false,'message' => 'Unauthorized'], 403);
        }

        $totalPrice = $cart->total_price;
        if (Auth::check())
        {

            $user=User::whereId(auth()->id())->first();
            $firstCoupon = $user->coupons->first();
            if ($request->coupon)
            {

                if ($firstCoupon->code === $request->coupon)
                {

                    if ($firstCoupon)
                    {

                        $totalPrice=($totalPrice * $firstCoupon->percent)/100;
                        $cart->check_card = 1;
                        $cart->check_card_price=$totalPrice;
                        $cart->save();
                        $firstCoupon->delete();
                    }else
                    {
                        $cart->check_card = 1;
                        $cart->check_card_price=$totalPrice;
                        $cart->save();
                    }
                }else{
                    return response()->json([
                        'success' => false,
                        'message' => 'invalid code',
                    ],403);
                }

            }
        }else{
            $cart->check_card = 1;
            $cart->check_card_price=$totalPrice;
            $cart->save();
        }
        return response()->json([
            'success' => true,
            'message' => 'Cart checked out',
            'referanceCode' =>$cart->cart_code,
            'total_price' => $totalPrice
        ],200);
    }

    public function getCart(Request $request)
    {
        $cartCode = $request->header('X-Cart-Code');

        if (Auth::check()) {
            $cart = Cart::where('user_id', Auth::id())->where('check_card',0)->first();
        } else if ($cartCode) {
            $cart = Cart::where('cart_code', $cartCode)->where('check_card',0)->whereNull('user_id')->first();
        } else {
            return response()->json(['error' => 'No cart found'], 404);
        }

        if ($cart) {
            $cartDetails = $cart->cartDetails;
            $totalPrice = $cart->total_price;
            return response()->json(['cart_details' => $cartDetails, 'total_price' => $totalPrice]);
        } else {
            return response()->json(['error' => 'No cart found'], 404);
        }
    }
}
