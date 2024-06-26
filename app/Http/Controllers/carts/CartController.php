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

        $originalTotalPrice = $cart->total_price;
        $totalPrice = $originalTotalPrice;
        $shippingCost = 54.99;
        $discount = 0;
        $discountPercentage = 0;
        $reward = '';
        if ($totalPrice >= 3000) {
            $discountPercentage = 25;
            $reward = 'You have earned 1 KG of coffee!';
        } elseif ($totalPrice >= 2000) {
            $discountPercentage = 20;
        } elseif ($totalPrice >= 1500) {
            $discountPercentage = 15;
        } elseif ($totalPrice >= 1000) {
            $discountPercentage = 10;
        }

        if ($discountPercentage > 0) {
            $discount = ($totalPrice * $discountPercentage) / 100;
            $totalPrice -= $discount;
        }
        if ($originalTotalPrice > 500) {
            $shippingCost = 0;
        }

        $totalPrice += $shippingCost;
        $couponDiscount = 0;
        $couponCode = null;
        if (Auth::check()) {
            $user = User::whereId(auth()->id())->first();
            $firstCoupon = $user->coupons->first();
            if ($request->coupon) {
                if ($firstCoupon && $firstCoupon->code === $request->coupon) {
                    $couponCode = $firstCoupon->code;
                    $couponDiscount = ($totalPrice * $firstCoupon->percent) / 100;
                    $totalPrice -= $couponDiscount;
                    $firstCoupon->delete();
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid code',
                    ], 403);
                }
            }
        }
        $cart->check_card = 1;
        $cart->check_card_price = $totalPrice;
        $cart->save();
        return response()->json([
            'success' => true,
            'message' => 'Cart checked out',
            'referenceCode' => $cart->cart_code,
            'original_total_price' => $originalTotalPrice,
            'discount_applied' => $discountPercentage . '%',
            'discount_amount' => $discount,
            'shipping_cost' => $shippingCost,
            'coupon_code' => $couponCode,
            'coupon_discount' => $couponDiscount,
            'total_price_after_discounts' => $totalPrice,
            'reward' => $reward
        ], 200);
    }

    public function getCart(Request $request)
    {
        $cartCode = $request->header('X-Cart-Code');

        if (Auth::check()) {
            $cart = Cart::where('user_id', Auth::id())->where('check_card', 0)->first();
        } else if ($cartCode) {
            $cart = Cart::where('cart_code', $cartCode)->where('check_card', 0)->whereNull('user_id')->first();
        } else {
            return response()->json(['error' => 'No cart found'], 404);
        }

        if ($cart) {
            $cartDetails = $cart->cartDetails;
            $originalTotalPrice = $cart->total_price;
            $totalPrice = $originalTotalPrice;
            $shippingCost = 54.99;
            $discount = 0;
            $discountPercentage = 0;
            $reward = '';

            if ($totalPrice >= 3000) {
                $discountPercentage = 25;
                $reward = 'You have earned 1 KG of coffee!';
            } elseif ($totalPrice >= 2000) {
                $discountPercentage = 20;
            } elseif ($totalPrice >= 1500) {
                $discountPercentage = 15;
            } elseif ($totalPrice >= 1000) {
                $discountPercentage = 10;
            }
            if ($discountPercentage > 0) {
                $discount = ($totalPrice * $discountPercentage) / 100;
                $totalPrice -= $discount;
            }
            if ($originalTotalPrice > 500) {
                $shippingCost = 0;
            }

            $totalPrice += $shippingCost;

            return response()->json([
                'cart_details' => $cartDetails,
                'original_total_price' => $originalTotalPrice,
                'discount_applied' => $discountPercentage . '%',
                'discount_amount' => $discount,
                'shipping_cost' => $shippingCost,
                'total_price_after_discounts' => $totalPrice,
                'reward' => $reward
            ]);
        } else {
            return response()->json(['error' => 'No cart found'], 404);
        }
    }

}
