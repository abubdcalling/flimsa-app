<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
public function destroy($id)
{
    $user = User::find($id);

    if (!$user) {
        return response()->json([
            'status' => 'error',
            'message' => 'User not found.'
        ], 404);
    }

    DB::beginTransaction();

    try {
        // Safely detach roles only if method exists and model uses HasRoles
        if (method_exists($user, 'syncRoles')) {
            try {
                $user->syncRoles([]); // detach roles
            } catch (\Throwable $e) {
                // Optional: log error or skip silently
            }
        }

        // Delete related records
        DB::table('devices')->where('user_id', $id)->delete();
        DB::table('histories')->where('user_id', $id)->delete();
        DB::table('likes')->where('user_id', $id)->delete();
        DB::table('user_subscriptions')->where('user_id', $id)->delete();

        // Delete user
        $user->delete();

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'User and related data deleted successfully.'
        ]);
    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to delete user. ' . $e->getMessage()
        ], 500);
    }
}


    public function showAllUsers(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);  // Default 10 users per page

            $planType = $request->input('plan_type');  // Optional plan_type filter

            $query = User::query();

            // 🔍 Filter by plan_type if provided
            if (!empty($planType)) {
                $query->where('plan_type', $planType);
            }

            $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $data = $users->getCollection()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'profile_pic' => $user->profile_pic ? url('uploads/users/' . $user->profile_pic) : null,
                    'roles' => $user->roles,
                    'phone' => $user->phone,
                    'plan_type' => $user->plan_type,
                    'gender' => $user->gender,
                    'username' => $user->username,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Users fetched successfully.',
                'data' => $data,
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'last_page' => $users->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch users: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching users.',
                'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage(),
            ], 500);
        }
    }

    public function updateMyPlanType(Request $request)
    {
        try {
            // Validate the incoming request
            $validated = $request->validate([
                'plan_type' => 'required|in:withads,withoutads,none',
            ]);

            // Get the authenticated user
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Update plan_type
            $user->plan_type = $validated['plan_type'];
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Plan type updated successfully',
                'user' => $user,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Failed to update plan type: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update plan type.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
