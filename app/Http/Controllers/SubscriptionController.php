<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class SubscriptionController extends Controller
{
    public function index()
    {
        try {
            $subscriptions = Subscription::all();
            return response()->json([
                'success' => true,
                'data' => $subscriptions,
            ]);
        } catch (Exception $e) {
            Log::error('Subscription index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve subscriptions.',
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'plan_name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'description' => 'nullable|string',
                'features' => 'nullable|array',
            ]);

            $subscription = Subscription::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Subscription created successfully.',
                'data' => $subscription,
            ], 201);
        } catch (Exception $e) {
            Log::error('Subscription store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subscription.',
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $subscription = Subscription::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $subscription,
            ]);
        } catch (Exception $e) {
            Log::error("Subscription show error (ID: $id): " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found.',
            ], 404);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            $subscription = Subscription::findOrFail($id);

            $validated = $request->validate([
                'plan_name' => 'sometimes|required|string|max:255',
                'price' => 'sometimes|required|numeric|min:0',
                'description' => 'nullable|string',
                'features' => 'nullable|array',
            ]);

            $subscription->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Subscription updated successfully.',
                'data' => $subscription,
            ]);
        } catch (Exception $e) {
            Log::error("Subscription update error (ID: $id): " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update subscription.',
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $subscription = Subscription::findOrFail($id);
            $subscription->delete();

            return response()->json([
                'success' => true,
                'message' => 'Subscription deleted successfully.',
            ]);
        } catch (Exception $e) {
            Log::error("Subscription delete error (ID: $id): " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete subscription.',
            ], 500);
        }
    }
}
