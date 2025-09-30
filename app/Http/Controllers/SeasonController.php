<?php

namespace App\Http\Controllers;

use App\Models\Season;
use App\Models\Series;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;



class SeasonController extends Controller
{
    // public function index(Request $request)
    // {
    //     try {
    //         $query = Season::query();

    //         // Optional filtering
    //         if ($request->has('series_id')) {
    //             $query->where('series_id', $request->input('series_id'));
    //         }

    //         if ($request->has('status')) {
    //             $query->where('status', $request->input('status'));
    //         }

    //         // Optional sorting (default: created_at desc)
    //         $sortBy = $request->input('sort_by', 'created_at');
    //         $sortOrder = $request->input('sort_order', 'desc');
    //         $query->orderBy($sortBy, $sortOrder);

    //         // Pagination (default: 10 per page)
    //         $perPage = $request->input('per_page', 10);
    //         $seasons = $query->paginate($perPage);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Seasons retrieved successfully.',
    //             'data' => $seasons
    //         ], Response::HTTP_OK);
    //     } catch (\Throwable $e) {
    //         Log::error('Error fetching seasons: ' . $e->getMessage(), [
    //             'trace' => $e->getTraceAsString(),
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Something went wrong while retrieving the seasons.'
    //         ], Response::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }



public function index(Request $request)
{
    try {
        $query = Season::query()
            ->with(['series:id,title'])      // parent info
            ->withCount('episodes');         // episodes_count

        // ----- Optional filtering -----
        if ($request->filled('series_id')) {
            $query->where('series_id', $request->integer('series_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // ----- Optional sorting (whitelisted) -----
        $sortable = ['id', 'number', 'created_at', 'updated_at'];
        $sortBy = in_array($request->input('sort_by'), $sortable, true)
            ? $request->input('sort_by')
            : 'created_at';

        $sortOrder = $request->input('sort_order', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // ----- Pagination like the Series method -----
        $perPageParam = $request->query('per_page', 15);

        if (is_numeric($perPageParam)) {
            $perPage = max(1, min((int) $perPageParam, 100)); // safety cap
        } elseif (is_string($perPageParam) && strtolower($perPageParam) === 'all') {
            $perPage = (int) $query->count();                 // single "all" page
        } else {
            $perPage = 15;
        }

        $seasons = $query
            ->paginate($perPage)                 // honors ?page=
            ->appends($request->query());        // keep params in next/prev links

        return response()->json([
            'success' => true,
            'message' => 'Seasons list fetched successfully.',
            'data'    => $seasons,
        ], Response::HTTP_OK);

    } catch (\Throwable $e) {
        Log::error('Error fetching seasons: '.$e->getMessage(), [
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Something went wrong while retrieving the seasons.',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}


    public function show(Season $season)
    {
        return $season->load([
            'series',
            'episodes' => fn($q) => $q->orderBy('episode_number'),
        ]);
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'series_id' => ['required', 'exists:series,id'],
                'season_number' => [
                    'required',
                    'integer',
                    'min:1',
                    Rule::unique('seasons', 'season_number')
                        ->where(fn($q) => $q->where('series_id', $request->input('series_id'))),
                ],
                'title' => ['nullable', 'string', 'max:255'],
                'release_date' => ['nullable', 'date'],
                'status' => ['nullable', 'in:draft,active,archived'],
                // NEW: slug (unique per series)
                'slug' => [
                    'nullable',
                    'string',
                    'max:255',
                    // keep it URL-friendly; tweak regex as you prefer
                    'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                    Rule::unique('seasons', 'slug')
                        ->where(fn($q) => $q->where('series_id', $request->input('series_id'))),
                ],
            ]);

            // Auto-generate slug from title (or from "season {n}") if not provided
            if (empty($data['slug'])) {
                $base = $data['title'] ?? 'season-' . $data['season_number'];
                $data['slug'] = Str::slug($base);
            }

            $season = Season::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Season created successfully.',
                'data' => $season,
            ], Response::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            Log::error('Error creating season: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while creating the season.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, Season $season)
    {
        try {
            $data = $request->validate([
                'series_id' => ['sometimes', 'required', 'exists:series,id'],
                'season_number' => [
                    'sometimes',
                    'required',
                    'integer',
                    'min:1',
                    Rule::unique('seasons', 'season_number')
                        ->where(fn($q) => $q->where('series_id', $request->input('series_id', $season->series_id)))
                        ->ignore($season->id),
                ],
                'title' => ['nullable', 'string', 'max:255'],
                'release_date' => ['nullable', 'date'],
                'status' => ['nullable', 'in:draft,active,archived'],
                // NEW: slug validation (unique per series, except current season)
                'slug' => [
                    'nullable',
                    'string',
                    'max:255',
                    'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                    Rule::unique('seasons', 'slug')
                        ->where(fn($q) => $q->where('series_id', $request->input('series_id', $season->series_id)))
                        ->ignore($season->id),
                ],
            ]);

            // If slug not provided but title changed → regenerate
            if (empty($data['slug']) && array_key_exists('title', $data)) {
                $base = $data['title'] ?? 'season-' . ($data['season_number'] ?? $season->season_number);
                $data['slug'] = Str::slug($base);
            }

            $season->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Season updated successfully.',
                'data' => $season,
            ], Response::HTTP_OK);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            Log::error('Error updating season: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while updating the season.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Season $season)
    {
        try {
            $season->delete();

            return response()->json([
                'success' => true,
                'message' => 'Season deleted successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error('Error deleting season: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while deleting the season.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
