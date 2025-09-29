<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Token;
use Exception;

class AuthController extends Controller
{
    public function sendResetOTP(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        $otp = rand(100000, 999999);

        // Store in cache
        Cache::put('reset_otp_' . $request->email, [
            'otp' => $otp,
            'expires_at' => Carbon::now()->addMinutes(10)
        ], now()->addMinutes(10));

        Mail::raw("Your password reset OTP is: $otp", function ($message) use ($request) {
            $message->to($request->email)->subject('Password Reset OTP');
        });

        return response()->json(['success' => true, 'message' => 'OTP sent to your email.']);
    }

    // protected function respondWithToken($token, $user = null)
    // {
    //     return response()->json([
    //         'success'      => true,
    //         'message'      => 'Token issued.',
    //         'token_type'   => 'bearer',
    //         'access_token' => $token,
    //         'expires_in'   => auth('api')->factory()->getTTL() * 60, // seconds
    //         'refresh_token'=>(int) config('jwt.refresh_ttl')*60,
    //         'refresh_expires_in' =>  (int) config('jwt.refresh_ttl')* 60,
    //         'user' => $user ? [
    //             'id'        => $user->id,
    //             'email'     => $user->email,
    //             'role'      => $user->roles,
    //             'plan_type' => $user->plan_type ?? null,
    //         ] : null,
    //     ]);
    // }

    // /**
    //  * Refresh using token from BODY only: { "refresh_token": "<jwt>" }
    //  * Route MUST be outside auth:api.
    //  */
    // public function refresh(Request $request)
    // {
    //     $request->validate([
    //         'refresh_token' => ['required','string'],
    //     ]);

    //     try {
    //         $raw = $request->input('refresh_token');
    //         if (stripos($raw, 'Bearer ') === 0) {
    //             $raw = trim(substr($raw, 7));
    //         }

    //         // Rotate & blacklist provided token (if blacklist enabled)
    //         $newToken = JWTAuth::setToken($raw)->refresh();

    //         // Get the user using the *JWT* guard (or via JWTAuth facade)
    //         $user = JWTAuth::setToken($newToken)->toUser();
    //         // or: $user = auth('api')->setToken($newToken)->user();

    //         return $this->respondWithToken($newToken, $user);

    //     } catch (TokenBlacklistedException $e) {
    //         return response()->json(['success' => false, 'message' => 'Token blacklisted.'], 401);
    //     } catch (TokenExpiredException $e) {
    //         return response()->json(['success' => false, 'message' => 'Refresh window expired.'], 401);
    //     } catch (TokenInvalidException $e) {
    //         return response()->json(['success' => false, 'message' => 'Token invalid.'], 401);
    //     } catch (JWTException $e) {
    //         return response()->json(['success' => false, 'message' => 'Token missing or cannot be parsed.'], 400);
    //     } catch (Exception $e) {
    //         Log::error('Refresh error ['.get_class($e).']: '.$e->getMessage());
    //         if (!app()->environment('production')) {
    //             return response()->json(['success'=>false,'message'=>'Failed to refresh token.','error'=>get_class($e).': '.$e->getMessage()], 500);
    //         }
    //         return response()->json(['success'=>false,'message'=>'Failed to refresh token.'], 500);
    //     }
    // }


protected function respondWithToken($token, $user = null, $refreshToken = null)
{
    $accessTtl  = (int) config('jwt.ttl');          // minutes
    $refreshTtl = (int) config('jwt.refresh_ttl');  // minutes

    // If no refresh token was passed in, mint one (when we have a user)
    if ($refreshToken === null && $user) {
        $refreshToken = auth('api')
            ->claims(['typ' => 'refresh'])
            ->setTTL($refreshTtl)
            ->fromUser($user);

        // reset guard TTL so later calls don't inherit the refresh TTL
        auth('api')->setTTL($accessTtl);
    }

    return response()->json([
        'success'             => true,
        'message'             => 'Token issued.',
        'token_type'          => 'bearer',
        'access_token'        => $token,
        'expires_in'          => $accessTtl * 60,          // seconds
        'refresh_token'       => $refreshToken,            // <-- actual JWT string
        'refresh_expires_in'  => $refreshTtl * 60,         // seconds
        'user' => $user ? [
            'id'        => $user->id,
            'email'     => $user->email,
            'role'      => $user->roles,
            'plan_type' => $user->plan_type ?? null,
        ] : null,
    ]);
}



//     public function refresh(Request $request)
// {
//     $request->validate([
//         'refresh_token' => ['required','string'],
//     ]);

//     try {
//         $raw = $request->input('refresh_token');
//         if (stripos($raw, 'Bearer ') === 0) {
//             $raw = trim(substr($raw, 7));
//         }

//         // Rotate & blacklist provided token (if blacklist enabled)
//         $newToken = \Tymon\JWTAuth\Facades\JWTAuth::setToken($raw)->refresh();

//         // Get the user using the *JWT* guard (or via JWTAuth facade)
//         $user = \Tymon\JWTAuth\Facades\JWTAuth::setToken($newToken)->toUser();

//         // respondWithToken will also generate and return a new refresh_token
//         return $this->respondWithToken($newToken, $user);

//     } catch (\Tymon\JWTAuth\Exceptions\TokenBlacklistedException $e) {
//         return response()->json(['success' => false, 'message' => 'Token blacklisted.'], 401);
//     } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
//         return response()->json(['success' => false, 'message' => 'Refresh window expired.'], 401);
//     } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
//         return response()->json(['success' => false, 'message' => 'Token invalid.'], 401);
//     } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
//         return response()->json(['success' => false, 'message' => 'Token missing or cannot be parsed.'], 400);
//     } catch (\Exception $e) {
//         \Illuminate\Support\Facades\Log::error('Refresh error ['.get_class($e).']: '.$e->getMessage());
//         if (!app()->environment('production')) {
//             return response()->json(['success'=>false,'message'=>'Failed to refresh token.','error'=>get_class($e).': '.$e->getMessage()], 500);
//         }
//         return response()->json(['success'=>false,'message'=>'Failed to refresh token.'], 500);
//     }
// }


    public function refresh(Request $request)
{
    $request->validate(['refresh_token' => ['required','string']]);

    $raw = $request->input('refresh_token');
    if (stripos($raw, 'Bearer ') === 0) {
        $raw = trim(substr($raw, 7));
    }

    try {
        // Parse & validate refresh token
        $payload = JWTAuth::setToken($raw)->getPayload();

        // Enforce refresh token type
        if (($payload['typ'] ?? null) !== 'refresh') {
            return response()->json(['success' => false, 'message' => 'Not a refresh token'], 400);
        }

        // Optionally blacklist the old refresh token (requires blacklist enabled)
        try { JWTAuth::invalidate(new \Tymon\JWTAuth\Token($raw)); } catch (\Exception $e) {}

        // Mint new access token & new refresh token
        $uid   = $payload['sub'];
        $user  = User::findOrFail($uid);
        $access = auth('api')->fromUser($user); // uses access ttl

        return $this->respondWithToken($access, $user); // will mint a new refresh token too

    } catch (TokenExpiredException $e) {
        return response()->json(['success' => false, 'message' => 'Refresh token expired'], 401);
    } catch (TokenInvalidException $e) {
        return response()->json(['success' => false, 'message' => 'Refresh token invalid'], 401);
    } catch (JWTException $e) {
        return response()->json(['success' => false, 'message' => 'Token missing or cannot be parsed'], 400);
    }
}


    public function verifyResetOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
        ]);

        $otpData = Cache::get('reset_otp_' . $request->email);

        if (!$otpData || $otpData['otp'] != $request->otp) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired OTP.'], 400);
        }

        // Store verification status in cache
        Cache::put('reset_verified_' . $request->email, true, now()->addMinutes(10));

        return response()->json(['success' => true, 'message' => 'OTP verified. You may now reset your password.']);
    }

    public function passwordReset(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Check OTP verification flag
        if (!Cache::get('reset_verified_' . $request->email)) {
            return response()->json(['success' => false, 'message' => 'OTP not verified or expired.'], 403);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        $user->password = bcrypt($request->password);
        $user->save();

        // Clear cache
        Cache::forget('reset_otp_' . $request->email);
        Cache::forget('reset_verified_' . $request->email);

        return response()->json(['success' => true, 'message' => 'Password reset successful.']);
    }

    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6|same:confirm_password',
                'confirm_password' => 'required|string|min:6',
                'gender' => 'required|in:male,female,others',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 400);
            }

            $validated = $validator->validated();

            $user = User::create([
                'first_name' => $validated['first_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'gender' => $validated['gender'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'email' => $user->email,
                    'gender' => $user->gender,
                    // 'roles' => $user->role, // Assuming you have a 'role' field in your User model
                ]
            ], 201);
        } catch (Exception $e) {
            Log::error('Error registering user: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to register user.',
                'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage()
            ], 500);
        }
    }

    // public function login(Request $request)
    // {
    //     try {
    //         $validator = Validator::make($request->all(), [
    //             'email' => 'required|email',
    //             'password' => 'required|string',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Validation failed.',
    //                 'errors' => $validator->errors(),
    //             ], 400);
    //         }

    //         $credentials = $request->only('email', 'password');

    //         if (!$token = JWTAuth::attempt($credentials)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Unauthorized. Invalid credentials.',
    //             ], 401);
    //         }

    //         $user = JWTAuth::user();

    //         $allowedRoles = ['admin', 'subscriber'];
    //         if (!in_array($user->roles, $allowedRoles)) {
    //             JWTAuth::invalidate($token);  // Optional: Invalidate token if role is not allowed
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Unauthorized. Role not allowed.',
    //             ], 403);
    //         }

    //         // Proper refresh with token set
    //         $refreshToken = JWTAuth::setToken($token)->refresh();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Login successful.',
    //             'token' => $token,
    //             'refresh_token' => $refreshToken,
    //             'user' => [
    //                 'id' => $user->id,
    //                 'email' => $user->email,
    //                 'role' => $user->roles,
    //                 'plan_type' => $user->plan_type ?? null,  // Assuming plan_type is a field in your User model
    //             ],
    //         ]);

    //         // return $this->respondWithToken($refreshToken, $user);
    //     } catch (Exception $e) {
    //         Log::error('Login error: ' . $e->getMessage());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Login failed.',
    //             'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage(),
    //         ], 500);
    //     }
    // }

  
  public function login(Request $request)
{
    $data = $request->validate([
        'email'    => 'required|email',
        'password' => 'required|string',
    ]);

    if (! $access = auth('api')->attempt($data)) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    $user = auth('api')->user();

    // roles check...
    if (! in_array($user->roles, ['admin','subscriber'])) {
        auth('api')->logout(); // invalidate access token
        return response()->json(['success' => false, 'message' => 'Role not allowed'], 403);
    }

    return $this->respondWithToken($access, $user); // this will also mint refresh
}

  
    public function logout()
    {
        try {
            if (!$token = JWTAuth::getToken()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token not provided.',
                ], 401);  // 401 is more accurate for missing token
            }

            JWTAuth::invalidate($token);

            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out.'
            ]);
        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token has already expired.',
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token is invalid.',
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token could not be parsed.',
            ], 400);
        } catch (Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to logout.',
                'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage(),
            ], 500);
        }
    }

    // Get authenticated user
    public function me()
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'User details fetched successfully.',
                'data' => auth()->user()
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user details.'
            ], 500);
        }
    }

    // Send password reset link to email
    public function sendResetEmailLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['success' => true, 'message' => __($status)])
            : response()->json(['success' => false, 'message' => __($status)], 400);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],  // Laravel expects a `new_password_confirmation` field for confirmation
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }
}
