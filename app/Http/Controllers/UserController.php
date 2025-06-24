<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
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
