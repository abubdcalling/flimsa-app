<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Subscription::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'plan_name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
        ]);

        $subscription = Subscription::create($validated);
        return response()->json($subscription, 201);
    }

    public function show(string $id)
    {
        $subscription = Subscription::findOrFail($id);
        return response()->json($subscription);
    }

    public function update(Request $request, string $id)
    {
        $subscription = Subscription::findOrFail($id);

        $validated = $request->validate([
            'plan_name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
        ]);

        $subscription->update($validated);
        return response()->json($subscription);
    }

    public function destroy(string $id)
    {
        $subscription = Subscription::findOrFail($id);
        $subscription->delete();

        return response()->json(['message' => 'Subscription deleted']);
    }
}
