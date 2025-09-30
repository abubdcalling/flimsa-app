<?php

namespace App\Http\Controllers;

use App\Models\Series;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SeriesController extends Controller
{
    // public function index()
    // {
    //     try {
    //         $series = Series::withCount(['seasons', 'episodes'])->paginate();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Series list fetched successfully.',
    //             'data'    => $series
    //         ], 200);
    //     } catch (\Exception $e) {
    //         Log::error('Error fetching series list: ' . $e->getMessage());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Something went wrong while fetching the series list.'
    //         ], 500);
    //     }
    // }

   

    public function index(Request $request)
    {
        try {
            $perPageParam = $request->query('per_page', 15);

            // Decide per-page size
            if (is_numeric($perPageParam)) {
                // guardrail: cap at e.g. 100
                $perPage = max(1, min((int) $perPageParam, 100));
            } elseif (is_string($perPageParam) && strtolower($perPageParam) === 'all') {
                // put all results on a single page (still returns paginator shape)
                $perPage = Series::count();
            } else {
                $perPage = 15;
            }

            $series = Series::withCount(['seasons', 'episodes'])
                ->paginate($perPage)  // honors ?page=
                
                ->appends($request->query());  // keeps params in next/prev links

            return response()->json([
                'success' => true,
                'message' => 'Series list fetched successfully.',
                'data' => $series,
            ], 200);
        } catch (\Throwable $e) {
            \Log::error('Error fetching series list: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while fetching the series list.',
            ], 500);
        }
    }

    public function show(Series $series)
    {
        return $series->load([
            'seasons' => fn($q) => $q->orderBy('season_number'),
        ]);
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'slug' => ['nullable', 'string', 'max:255', 'unique:series,slug'],
                'description' => ['nullable', 'string'],
                'release_date' => ['nullable', 'date'],
                'status' => ['nullable', 'in:draft,active,archived'],
            ]);

            // Auto-generate slug if not provided
            if (empty($data['slug'])) {
                $baseSlug = Str::slug($data['title']);
                $slug = $baseSlug;
                $counter = 2;

                // Ensure slug uniqueness
                while (Series::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter++;
                }

                $data['slug'] = $slug;
            }

            $series = Series::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Series created successfully.',
                'data' => $series
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating series: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while creating the series.'
            ], 500);
        }
    }

    public function update(Request $request, Series $series)
    {
        try {
            $data = $request->validate([
                'title' => ['sometimes', 'required', 'string', 'max:255'],
                'slug' => ['nullable', 'string', 'max:255', 'unique:series,slug,' . $series->id],
                'description' => ['nullable', 'string'],
                'release_date' => ['nullable', 'date'],
                'status' => ['nullable', 'in:draft,active,archived'],
            ]);

            // Auto-generate slug if not provided but title is updated
            if (empty($data['slug']) && array_key_exists('title', $data)) {
                $baseSlug = Str::slug($data['title']);
                $slug = $baseSlug;
                $counter = 2;

                // Ensure slug is unique (ignoring current series record)
                while (
                    Series::where('slug', $slug)
                        ->where('id', '!=', $series->id)
                        ->exists()
                ) {
                    $slug = $baseSlug . '-' . $counter++;
                }

                $data['slug'] = $slug;
            }

            $series->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Series updated successfully.',
                'data' => $series
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating series: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while updating the series.'
            ], 500);
        }
    }

    public function destroy(Series $series)
    {
        try {
            $series->delete();

            return response()->json([
                'success' => true,
                'message' => 'Series deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting series: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while deleting the series.'
            ], 500);
        }
    }
}
