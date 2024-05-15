<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Lcobucci\JWT\Exception;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    protected $prefix;
    protected $year;

    protected function couponExists($code)
    {
        return Coupon::whereCode($code)->exists();
    }

    public function __construct()
    {
        $this->prefix = 'TTN';
        $this->year = now()->year;
    }

    public function register(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
        ];

        $messages = [
            'name.required' => 'The name field is mandatory.',
            'email.required' => 'Email field is mandatory.',
            'email.email' => 'Enter a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'password.required' => 'Password field is mandatory.',
            'password.min' => 'Password must be at least 6 characters.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()
            ], 422);
        }
        try {

            $data = $request->all();
            if (empty($data['user_type']))
            {
                $user = User::create($data);
                return response()->json([
                    'success' => true,
                    'message' => 'Coffee Shop Admin Register Done!',
                    'user' => $user
                ], 200);
            }
            $user = User::create($data);
            do {
                $ts = str_repeat('T', rand(3, 6));
                $number = rand(1, 999);
                $couponCode = $this->prefix . $this->year . $ts . sprintf('%03d', $number);
            } while ($this->couponExists($couponCode));
            $coupon['code'] = $couponCode;
            $coupon['user_id'] = $user->id;
            $coupon['percent'] = 25;
            Coupon::create($coupon);
            return response()->json([
                'success' => true,
                'message' => 'Coffee Shop Register Done!',
                'user' => $user,
                'coupon' => $couponCode
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function login(Request $request)
    {
        try {
            $credentials = $request->only('email', 'password');
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email|max:255',
                'password' => 'required|string',
            ]);
            if ($validator->fails()) {
                return response(['success' => false, 'message' => $validator->errors()->all()], 422);
            }
            if (Auth::attempt($credentials)) {
                $user = Auth::user();
                $user['coupons'] = Coupon::whereUserId(\auth()->id())->get();
                return response()->json([
                    "success" => true,
                    "token" => $user->createToken('Login')->accessToken,
                    "user" => $user,
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function logout()
    {
        try {
            $token = auth()->user()->token();
            $token->revoke();
            $response = ['success' => true, 'message' => 'Logout Successful'];
            return response($response, 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function update(Request $request, User $user)
    {
        try {
            $data = $request->all();
            if ($user->id === auth()->id()) {
                if (empty($request->password)) {
                    $data['password'] = Hash::make($request->password);
                }
                $user->update($data);
                return response()->json([
                    'success' => true,
                    'message' => 'user updated succesfull!',
                    'user' => $user
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'unauthorized!'
                ], 401);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
