<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Cart;

class TransferCartMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if (Auth::check() && $request->hasHeader('X-Cart-Code')) {
            $cartCode = $request->header('X-Cart-Code');
            $cart = Cart::where('cart_code', $cartCode)->whereNull('user_id')->first();
            if ($cart) {
                $cart->user_id = Auth::id();
                $cart->save();
            }
        }
        return $response;
    }
}
