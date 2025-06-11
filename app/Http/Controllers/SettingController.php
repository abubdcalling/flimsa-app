<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class SettingController extends Controller
{
    public function storeOrUpdatePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:6',
                'confirm_new_password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 400);
            }

            if ($request->new_password !== $request->confirm_new_password) {
                return response()->json([
                    'success' => false,
                    'message' => 'New password and confirmation do not match.',
                ], 400);
            }

            $user = auth('api')->user();  // ✅ Explicitly use 'api' guard for JWT

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The current password is incorrect.',
                ], 403);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully.',
            ]);
        } catch (Exception $e) {
            Log::error('Error updating password: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the password.',
            ], 500);
        }
    }

    public function storeOrUpdateForUser(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login first.'
            ], 401);
        }

        try {
            $validated = $request->validate([
                'username' => 'nullable|string|max:255|unique:users,username,' . Auth::id(),
                'first_name' => 'nullable|string|max:255',
                'profile_pic' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10248',
            ]);

            $user = Auth::user();

            $user->username = $validated['username'] ?? $user->username;
            $user->first_name = $validated['first_name'] ?? $user->first_name;

            if ($request->hasFile('profile_pic')) {
                $file = $request->file('profile_pic');
                $imageName = time() . '_profile.' . $file->getClientOriginalExtension();
                $destinationPath = public_path('uploads/profiles');

                // Delete old profile pic if it exists
                if ($user->profile_pic && file_exists(public_path($user->profile_pic))) {
                    unlink(public_path($user->profile_pic));
                }

                // Move and save the new file
                $file->move($destinationPath, $imageName);
                $user->profile_pic = 'uploads/profiles/' . $imageName;
            }

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully.',
                'data' => [
                    'username' => $user->username,
                    'first_name' => $user->first_name,
                    'profile_pic' => $user->profile_pic,
                ]
            ]);
        } catch (Exception $e) {
            \Log::error('Error updating user profile: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storeOrUpdate(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login first.'
            ], 401);
        }

        try {
            $validated = $request->validate([
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255|unique:users,email,' . Auth::id(),
                'country' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
            ]);

            $user = Auth::user();

            // Update fields
            $user->first_name = $validated['first_name'] ?? $user->first_name;
            $user->last_name = $validated['last_name'] ?? $user->last_name;
            $user->phone = $validated['phone'] ?? $user->phone;
            $user->email = $validated['email'] ?? $user->email;
            $user->country = $validated['country'] ?? $user->country;
            $user->city = $validated['city'] ?? $user->city;

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully.',
                'data' => [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'country' => $user->country,
                    'city' => $user->city,
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Error updating user profile: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
                'email' => $user->email,
                'country' => $user->country,
                'city' => $user->city,
            ],
        ]);
    }
}
