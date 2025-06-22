<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VideoTrackingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validate input
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'device_id' => 'required|integer',
                'content_id' => 'required|exists:contents,id',
                'status' => 'required|in:completed,not completed',
                'elapsed_time' => 'required|string',  // Assuming it's seconds as string (e.g., "360")
            ]);

            // Store the video tracking
            $video = Video::create($validated);

            // Fetch the user
            $user = User::find($validated['user_id']);

            // Calculate unique device count for this user
            $deviceCount = Video::where('user_id', $user->id)
                ->distinct('device_id')
                ->count('device_id');

            // Find max elapsed_time for the given user_id, device_id, content_id
            $maxElapsedTime = Video::where('user_id', $validated['user_id'])
                ->where('device_id', $validated['device_id'])
                ->where('content_id', $validated['content_id'])
                ->max('elapsed_time');

            // Get the max elapsed_time for this user
            // $maxDuration = Video::where('user_id', $user->id)
            //     ->max('elapsed_time');

            // Update user's device_count and final_duration (only using max elapsed_time)
            $user->update([
                'device_count' => $deviceCount,
                // 'final_duration' => $maxDuration,
            ]);

            \DB::table('devices')->updateOrInsert(
                [
                    'user_id' => $validated['user_id'],
                    // 'device_id' => $validated['device_id'],
                    'content_id' => $validated['content_id'],
                ],
                [
                    'duration' => $maxElapsedTime,
                    // You can add timestamps or other columns if needed here
                    'updated_at' => now(),
                    // 'created_at' => now(), // updateOrInsert won't set created_at automatically, you can add if you want
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Video tracking stored and user stats updated successfully.',
                'data' => $video,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Video store failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to store video tracking.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($user_id, $content_id)
    {
        try {
            // Validate input manually since route parameters bypass automatic validation
            $user = User::findOrFail($user_id);
            $content = Content::findOrFail($content_id);

            // Fetch duration from devices table
            $record = \DB::table('devices')
                ->where('user_id', $user_id)
                ->where('content_id', $content_id)
                ->first();

            if ($record) {
                return response()->json([
                    'success' => true,
                    'message' => 'Duration fetched successfully.',
                    'duration' => $record->duration,
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'No duration record found yet for this user and content.',
                    'duration' => 0,
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Fetching duration failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch duration.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try {
            // Validate input
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'content_id' => 'required|exists:contents,id',
            ]);

            // Get max elapsed_time for the given user_id and content_id (across all devices)
            $maxElapsedTime = Video::where('user_id', $validated['user_id'])
                ->where('content_id', $validated['content_id'])
                ->max('elapsed_time');

            // Update or insert duration in the devices table (grouped by user + content)
            \DB::table('devices')->updateOrInsert(
                [
                    'user_id' => $validated['user_id'],
                    'content_id' => $validated['content_id'],
                ],
                [
                    'duration' => $maxElapsedTime,
                    'updated_at' => now(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Duration updated successfully.',
                'duration' => $maxElapsedTime,
            ]);
        } catch (\Exception $e) {
            Log::error('Duration update failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update duration.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($user_id, $content_id)
    {
        try {
            // Validate that user and content exist
            $user = User::findOrFail($user_id);
            $content =Content::findOrFail($content_id);

            // Attempt to delete the record
            $deleted = \DB::table('devices')
                ->where('user_id', $user_id)
                ->where('content_id', $content_id)
                ->delete();

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Duration record deleted successfully.',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No duration record found for the given user and content.',
                ], 404);
            }
        } catch (\Exception $e) {
            \Log::error('Duration delete failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete duration.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
