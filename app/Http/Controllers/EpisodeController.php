<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EpisodeResource;
use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EpisodeController extends Controller
{
    /**
     * Display a listing of episodes.
     */
    public function index(Request $request)
    {
        try {
            $episodes = Episode::with('serie', 'season')->get();
            return EpisodeResource::collection($episodes);
        } catch (\Exception $e) {
            Log::error('Error fetching episodes: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch episodes'], 500);
        }
    }

    /**
     * Store a newly created episode.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'season_id' => 'required|exists:seasons,id',
            'serie_id' => 'required|exists:series,id',
            'title' => 'required|string|max:255',
            'episode_number' => 'required|integer|min:1',
            'duration' => 'required|integer|min:1',
            'release_date' => 'required|date',
        ]);

        try {
            $episode = Episode::create($validated);
            return new EpisodeResource($episode);
        } catch (\Exception $e) {
            Log::error('Error creating episode: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create episode'], 500);
        }
    }

    /**
     * Display the specified episode.
     */
    public function show(Episode $episode)
    {
        try {
            $episode->load('serie', 'season');
            return new EpisodeResource($episode);
        } catch (\Exception $e) {
            Log::error('Error fetching episode: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch episode'], 500);
        }
    }

    /**
     * Update the specified episode.
     */
    public function update(Request $request, Episode $episode)
    {
        $validated = $request->validate([
            'season_id' => 'required|exists:seasons,id',
            'serie_id' => 'required|exists:series,id',
            'title' => 'required|string|max:255',
            'episode_number' => 'required|integer|min:1',
            'duration' => 'required|integer|min:1',
            'release_date' => 'required|date',
        ]);

        try {
            $episode->update($validated);
            return new EpisodeResource($episode);
        } catch (\Exception $e) {
            Log::error('Error updating episode: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update episode'], 500);
        }
    }

    /**
     * Remove the specified episode.
     */
    public function destroy(Episode $episode)
    {
        try {
            $episode->delete();
            return response()->json(['message' => 'Episode deleted successfully'], 204);
        } catch (\Exception $e) {
            Log::error('Error deleting episode: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete episode'], 500);
        }
    }
}