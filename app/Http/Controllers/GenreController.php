<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Genre;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GenreController extends Controller
{
    public function Home(Request $request)
    {
        try {
            // Get pagination size from request or default to 10
            $perPage = $request->get('per_page', 10);

            // Latest 1 content
            $latestContent = Content::with('genres:id,name')
                ->select('id', 'title', 'description', 'image', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
                ->latest()
                ->take(1)
                ->get()
                ->transform(function ($content) {
                    return [
                        'id' => $content->id,
                        'title' => $content->title,
                        'description' => $content->description,
                        'image' => $content->image,
                        'publish' => $content->publish,
                        'schedule' => $content->schedule,
                        'view_count' => $content->view_count,
                        'created_at' => $content->created_at,
                        'genre_name' => optional($content->genres)->name,
                    ];
                });

            // Fetch genre names
            $genreNames = Genre::pluck('name');

            // Fetch most viewed content (popular)
            $popularContents = Content::with('genres:id,name')
                ->select('id', 'title', 'description', 'image', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
                ->orderByDesc('view_count')
                ->paginate($perPage);

            $popularContents->getCollection()->transform(function ($content) {
                return [
                    'id' => $content->id,
                    'title' => $content->title,
                    'description' => $content->description,
                    'image' => $content->image,
                    'publish' => $content->publish,
                    'schedule' => $content->schedule,
                    'view_count' => $content->view_count,
                    'created_at' => $content->created_at,
                    'genre_name' => optional($content->genres)->name,
                ];
            });

            // Fetch upcoming content (future schedule date)
            $upcomingContents = Content::with('genres:id,name')
                ->where('schedule', '>', Carbon::now())
                ->select('id', 'title', 'description', 'image', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
                ->orderBy('schedule', 'asc')
                ->paginate($perPage);

            $upcomingContents->getCollection()->transform(function ($content) {
                return [
                    'id' => $content->id,
                    'title' => $content->title,
                    'description' => $content->description,
                    'image' => $content->image,
                    'publish' => $content->publish,
                    'schedule' => $content->schedule,
                    'view_count' => $content->view_count,
                    'created_at' => $content->created_at,
                    'genre_name' => optional($content->genres)->name,
                ];
            });

            // Comedy content (genre name = 'Comedy')
            $comedyContents = Content::with('genres:id,name')
                ->whereHas('genres', function ($query) {
                    $query->where('name', 'Comedy');
                })
                ->select('id', 'title', 'description', 'image', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
                ->latest()
                ->paginate($perPage);

            $comedyContents->getCollection()->transform(function ($content) {
                return [
                    'id' => $content->id,
                    'title' => $content->title,
                    'description' => $content->description,
                    'image' => $content->image,
                    'publish' => $content->publish,
                    'schedule' => $content->schedule,
                    'view_count' => $content->view_count,
                    'created_at' => $content->created_at,
                    'genre_name' => optional($content->genres)->name,
                ];
            });

            // Family content (genre name = 'family')
            $familyContents = Content::with('genres:id,name')
                ->whereHas('genres', function ($query) {
                    $query->where('name', 'Family');
                })
                ->select('id', 'title', 'description', 'image', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
                ->latest()
                ->paginate($perPage);

            $familyContents->getCollection()->transform(function ($content) {
                return [
                    'id' => $content->id,
                    'title' => $content->title,
                    'description' => $content->description,
                    'image' => $content->image,
                    'publish' => $content->publish,
                    'schedule' => $content->schedule,
                    'view_count' => $content->view_count,
                    'created_at' => $content->created_at,
                    'genre_name' => optional($content->genres)->name,
                ];
            });

            // Dramas content (genre name = 'dramas')
            $dramasContents = Content::with('genres:id,name')
                ->whereHas('genres', function ($query) {
                    $query->where('name', 'Dramas');
                })
                ->select('id', 'title', 'description', 'image', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
                ->latest()
                ->paginate($perPage);

            $dramasContents->getCollection()->transform(function ($content) {
                return [
                    'id' => $content->id,
                    'title' => $content->title,
                    'description' => $content->description,
                    'image' => $content->image,
                    'publish' => $content->publish,
                    'schedule' => $content->schedule,
                    'view_count' => $content->view_count,
                    'created_at' => $content->created_at,
                    'genre_name' => optional($content->genres)->name,
                ];
            });

            // Tv Shows content (genre name = 'Tv shows')
            $tvshows = Content::with('genres:id,name')
                ->whereHas('genres', function ($query) {
                    $query->where('name', 'tv shows');
                })
                ->select('id', 'title', 'description', 'image', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
                ->latest()
                ->paginate($perPage);

            $tvshows->getCollection()->transform(function ($content) {
                return [
                    'id' => $content->id,
                    'title' => $content->title,
                    'description' => $content->description,
                    'image' => $content->image,
                    'publish' => $content->publish,
                    'schedule' => $content->schedule,
                    'view_count' => $content->view_count,
                    'created_at' => $content->created_at,
                    'genre_name' => optional($content->genres)->name,
                ];
            });

            // Weekly Top Content (top 10 most viewed in last 7 days)
            $weeklyTopContents = Content::with('genres:id,name')
                ->whereBetween('created_at', [Carbon::now()->subDays(7), Carbon::now()])
                ->orderByDesc('view_count')
                ->select('id', 'title', 'description', 'image', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
                ->take(10)
                ->get()
                ->transform(function ($content) {
                    return [
                        'id' => $content->id,
                        'title' => $content->title,
                        'description' => $content->description,
                        'image' => $content->image,
                        'publish' => $content->publish,
                        'schedule' => $content->schedule,
                        'view_count' => $content->view_count,
                        'created_at' => $content->created_at,
                        'genre_name' => optional($content->genres)->name,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'genre_names' => $genreNames,
                    'popular' => $popularContents,
                    'upcoming' => $upcomingContents,
                    'comedy' => $comedyContents,
                    'family' => $familyContents,
                    'dramas' => $dramasContents,
                    'tv_shows' => $tvshows,
                    'weekly_top' => $weeklyTopContents,
                     'latest' => $latestContent,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching genre names, popular or upcoming content: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch genre names, popular or upcoming content.'
            ], 500);
        }
    }

    public function index()
    {
        $genres = Genre::withCount('contents')->get();
        return response()->json($genres);

        // Optionally rename contents_count to total_content
        $genres->transform(function ($genre) {
            $genre->total_content = $genre->contents_count;
            unset($genre->contents_count);
            return $genre;
        });

        return response()->json($genres);
    }

    public function showsAllGenres()
    {
        $genres = Genre::all()->map(function ($genre) {
            return [
                'id' => $genre->id,
                'name' => $genre->name,
                'thumbnail' => $genre->thumbnail,
                'date' => $genre->created_at->format('Y-m-d'),
                'content' => $genre->content,
            ];
        });

        return response()->json($genres);
    }

    public function showsAllContents()
    {
        try {
            $contents = Content::all()->map(function ($content) {
                return [
                    'content_name' => $content->title,
                    'video1' => $content->video1,
                    'description' => $content->description,
                    'publish' => $content->publish,
                    'schedule' => $content->schedule,
                    'genre_id' => $content->genre_id,
                    'image' => $content->image,
                    'created_at' => $content->created_at?->format('Y-m-d'),
                    'updated_at' => $content->updated_at?->format('Y-m-d'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Contents fetched successfully.',
                'data' => $contents,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch contents.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $genre = Genre::findOrFail($id);
            return response()->json(['success' => true, 'data' => $genre]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Genre not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'thumbnail' => 'nullable|image',
            ]);

            if ($request->hasFile('thumbnail')) {
                $file = $request->file('thumbnail');
                $imageName = time() . '_genre_thumbnail.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/Genres'), $imageName);
                $validated['thumbnail'] = 'uploads/Genres/' . $imageName;
            }

            $genre = Genre::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Genre created successfully',
                'data' => $genre
            ], 201);
        } catch (ValidationException $e) {
            // Handle validation errors separately
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Handle other exceptions
            return response()->json([
                'success' => false,
                'message' => 'Failed to create genre',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'thumbnail' => 'nullable|image',
            ]);

            $genre = Genre::findOrFail($id);

            // Handle new thumbnail upload if present
            if ($request->hasFile('thumbnail')) {
                $file = $request->file('thumbnail');
                $imageName = time() . '_genre_thumbnail.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/Genres'), $imageName);

                // Optionally delete old file
                if ($genre->thumbnail && file_exists(public_path($genre->thumbnail))) {
                    @unlink(public_path($genre->thumbnail));
                }

                $validated['thumbnail'] = 'uploads/Genres/' . $imageName;
            }

            $genre->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Genre updated successfully',
                'data' => $genre
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update genre',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $genre = Genre::findOrFail($id);
            $genre->delete();

            return response()->json(['success' => true, 'message' => 'Genre deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete genre', 'error' => $e->getMessage()], 500);
        }
    }
}
