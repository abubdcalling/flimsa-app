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
use Tymon\JWTAuth\Facades\JWTAuth;
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

    // Register user
    // public function register(Request $request)
    // {
    //     try {
    //         $validator = Validator::make($request->all(), [
    //             'name' => 'required|string|max:255',
    //             'email' => 'required|email|unique:users',
    //             'password' => 'required|string|min:6',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Validation failed.',
    //                 'errors' => $validator->errors()
    //             ], 400);
    //         }

    //         $user = User::create([
    //             'name' => $request->name,
    //             'email' => $request->email,
    //             'password' => Hash::make($request->password),
    //         ]);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'User registered successfully',
    //             'data' => $user
    //         ], 201);
    //     } catch (Exception $e) {
    //         Log::error('Error registering user: ' . $e->getMessage());
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to register user.'
    //         ], 500);
    //     }
    // }

    // public function register(Request $request)
    // {
    //     try {
    //         $validator = Validator::make($request->all(), [
    //             'first_name' => 'required|string|max:255',
    //             'last_name' => 'required|string|max:255',
    //             'phone' => 'required|string|max:20',
    //             'company_name' => 'required|string|max:255',
    //             'email' => 'required|email|unique:users,email',
    //             'password' => 'required|string|min:6|same:confirm_password',
    //             'confirm_password' => 'required|string|min:6',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Validation failed.',
    //                 'errors' => $validator->errors()
    //             ], 400);
    //         }

    //         $user = User::create([
    //             'first_name' => $request->first_name,
    //             'last_name' => $request->last_name,
    //             'phone' => $request->phone,
    //             'company_name' => $request->company_name,
    //             'email' => $request->email,
    //             'password' => Hash::make($request->password),
    //         ]);

    //         // Generate JWT token
    //         $token = JWTAuth::fromUser($user);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'User registered successfully',
    //             'token' => $token,
    //             'data' => $user
    //         ], 201);
    //     } catch (Exception $e) {
    //         Log::error('Error registering user: ' . $e->getMessage());
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to register user.',
    //             'error' => $e->getMessage()  // For debugging; remove in production
    //         ], 500);
    //     }
    // }

    // Login user and get token
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
    //                 'errors' => $validator->errors()
    //             ], 400);
    //         }

    //         $credentials = $request->only('email', 'password');

    //         if (!$token = JWTAuth::attempt($credentials)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Unauthorized. Invalid credentials.'
    //             ], 401);
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Login successful',
    //             'token' => $token
    //         ]);
    //     } catch (Exception $e) {
    //         Log::error('Login error: ' . $e->getMessage());
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Login failed.'
    //         ], 500);
    //     }
    // }



    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6|same:confirm_password',
                'confirm_password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 400);
            }

            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'company_name' => $request->company_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => $user
            ], 201);
        } catch (Exception $e) {
            Log::error('Error registering user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to register user.',
                'error' => $e->getMessage()  // Remove in production
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 400);
            }

            $credentials = $request->only('email', 'password');

            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Invalid credentials.'
                ], 401);
            }

            // Get the authenticated user
            $user = JWTAuth::user();

            // Check user role - adjust field name and values as per your User model
            $allowedRoles = ['admin', 'user', 'editor', 'author'];
            if (!in_array($user->role, $allowedRoles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Role not allowed.'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
                    // 'name' => $user->name,  // if you want to return the user name
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Error registering user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to register user.',
                'error' => $e->getMessage()  // <== This will show you the actual reason
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

    // Logout user (invalidate token)
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out'
            ]);
        } catch (Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to logout.'
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
