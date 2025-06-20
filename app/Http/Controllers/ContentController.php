<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\History;
use Carbon\Carbon;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ContentController extends Controller
{

    public function History(Request $request)
    {
        try {
            // Get per_page value from query, default to 10
            $perPage = $request->query('per_page', 10);

            $contents = Content::whereIn('id', function ($query) {
                $query
                    ->select('content_id')
                    ->from('histories')
                    ->where('user_id', Auth::id());
            })
                ->orderBy('updated_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'User viewed contents fetched successfully.',
                'data' => $contents
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch user viewed contents.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function upcomingContent(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 10);  // default to 10 if not provided

            $upcoming = Content::whereDate('schedule', '>', Carbon::today())
                ->orderBy('schedule', 'asc')
                ->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Upcoming content retrieved successfully.',
                'data' => $upcoming
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve upcoming content.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // GET /api/contents

    public function index(Request $request)
    {
        try {
            $paginateCount = $request->get('paginate_count', 10);
            $userId = $request->user()->id ?? null;

            // Group total likes by content_id where is_liked is true
            $likesGrouped = DB::table('likes')
                ->select('content_id', DB::raw('COUNT(*) as total_likes'))
                ->where('is_liked', true)
                ->groupBy('content_id')
                ->pluck('total_likes', 'content_id');

            // [content_id => total_likes]

            // Fetch paginated contents with genre relationship
            $contents = Content::with('genres')  // genres contains genre name
                ->select('id', 'video1', 'title', 'description', 'publish', 'schedule', 'genre_id', 'image', 'view_count', 'created_at')
                ->latest()
                ->paginate($paginateCount);

            $contents->getCollection()->transform(function ($content) use ($userId, $likesGrouped) {
                // Rename view_count to total_view
                $content->total_view = $content->view_count;
                unset($content->view_count);

                // Assign total_likes
                $content->total_likes = (int) ($likesGrouped[$content->id] ?? 0);

                // Pull genre_name from related genre table
                $content->genre_name = optional($content->genres)->name;

                // Remove the genres object if only genre_name is needed
                unset($content->genres);

                // Add is_liked only if user is logged in
                if ($userId) {
                    $content->is_liked = $content
                        ->likes()
                        ->where('user_id', $userId)
                        ->where('is_liked', true)
                        ->exists();
                }

                return $content;
            });

            return response()->json([
                'success' => true,
                'message' => 'Content list retrieved successfully',
                'data' => $contents,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching content list: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch contents.',
            ], 500);
        }
    }

    public function updateLike(Request $request, Content $content)
    {
        $user = $request->user();

        // Check if user already liked the content
        $existingLike = $content->likes()->where('user_id', $user->id)->first();

        if ($existingLike) {
            // Unlike
            $existingLike->delete();
            $isLiked = false;
        } else {
            // Like
            $content->likes()->create([
                'user_id' => $user->id,
                // 'is_liked' => 1,
            ]);
            $isLiked = true;
            $content->likes()->where('user_id', $user->id)->update(['is_liked' => true]);
        }

        return response()->json([
            'success' => true,
            'is_liked' => $isLiked,
            'total_likes' => $content->likes()->count(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'video1' => 'nullable|file',
            'title' => 'required|string',
            'description' => 'required|string',
            'publish' => 'required|in:public,private,schedule',
            'schedule' => 'nullable|date',
            'genre_id' => 'required|exists:genres,id',
            'image' => 'nullable|image',
        ]);

        try {
            $videoName = null;
            if ($request->hasFile('video1')) {
                $videoFile = $request->file('video1');
                $videoPath = $videoFile->store('videos', 's3');

                if (!$videoPath) {
                    throw new \Exception('Failed to upload video to S3');
                }

                Storage::disk('s3')->setVisibility($videoPath, 'public');
                $videoName = Storage::disk('s3')->url($videoPath);
            }

            $imageName = null;
            if ($request->hasFile('image')) {
                $imageFile = $request->file('image');
                $imagePath = $imageFile->store('images', 's3');

                if (!$imagePath) {
                    throw new \Exception('Failed to upload image to S3');
                }

                Storage::disk('s3')->setVisibility($imagePath, 'public');
                $imageName = Storage::disk('s3')->url($imagePath);
            }

            $content = Content::create([
                'video1' => $videoName,
                'title' => $validated['title'],
                'description' => $validated['description'],
                'publish' => $validated['publish'],
                'schedule' => $validated['publish'],  // === 'schedule' ? $validated['schedule'] : null,
                'genre_id' => $validated['genre_id'],
                'image' => $imageName,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Content created successfully.',
                'data' => $content,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to store content', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create content.',
            ], 500);
        }
    }

    // GET /api/contents/{id}
    public function show($id)
    {
        $content = Content::with('genres')->find($id);

        if (!$content) {
            return response()->json([
                'success' => false,
                'message' => 'Content not found.',
            ], 404);
        }

        // Increment view_count
        $content->increment('view_count');

        // Log user view if logged in
        if (Auth::check()) {
            History::updateOrCreate(
                ['user_id' => Auth::id(), 'content_id' => $id],
                ['updated_at' => now()]  // update timestamp if already exists
            );
        }

        // return response()->json($content);

        return response()->json([
            'success' => true,
            'data' => $content,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'video1' => 'nullable|file',
            'title' => 'required|string',
            'description' => 'required|string',
            'publish' => 'required|in:public,private,schedule',
            'schedule' => 'nullable|date',
            'genre_id' => 'required|exists:genres,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            $content = Content::findOrFail($id);

            // Handle video upload
            if ($request->hasFile('video1')) {
                $videoFile = $request->file('video1');
                $videoName = time() . '_content_video.' . $videoFile->getClientOriginalExtension();
                $videoFile->move(public_path('uploads/Videos'), $videoName);

                // Optionally delete old video file from local (if needed)
                // @unlink(public_path('uploads/Videos/' . $content->video1));

                $content->video1 = $videoName;
            }

            // Handle image upload
            if ($request->hasFile('image')) {
                $imageFile = $request->file('image');
                $imageName = time() . '_content_image.' . $imageFile->getClientOriginalExtension();
                $imageFile->move(public_path('uploads/Contents'), $imageName);

                // Optionally delete old image file from local (if needed)
                // @unlink(public_path('uploads/Contents/' . $content->image));

                $content->image = $imageName;
            }

            // Update remaining fields
            $content->title = $validated['title'];
            $content->description = $validated['description'];
            $content->publish = $validated['publish'];
            $content->schedule = $validated['publish'] === 'schedule' ? $validated['schedule'] : now();
            $content->genre_id = $validated['genre_id'];
            $content->save();

            return response()->json([
                'success' => true,
                'message' => 'Content updated successfully.',
                'data' => $content,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update content: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update content.',
            ], 500);
        }
    }

    // DELETE /api/contents/{id}

    public function destroy($id)
    {
        try {
            $content = Content::findOrFail($id);

            // Delete video file if exists
            if (!empty($content->video1)) {
                $videoPath = public_path('uploads/Videos/' . $content->video1);
                if (file_exists($videoPath)) {
                    unlink($videoPath);
                }
            }

            // Delete image file if exists
            if (!empty($content->image)) {
                $imagePath = public_path('uploads/Contents/' . $content->image);
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            // Delete content from DB
            $content->delete();

            return response()->json([
                'success' => true,
                'message' => 'Content deleted successfully.',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Content not found.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete content: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete content.',
            ], 500);
        }
    }

    public function allcontents(Request $request)
    {
        try {
            $paginateCount = $request->get('paginate_count', 10);
            $searchGenreName = $request->get('genre');
            $searchTitle = $request->get('title');
            $sortBy = $request->get('sort_by');  // options: 'popularity', 'latest'

            $query = Content::with('genres');

            if ($searchGenreName) {
                $query->whereHas('genres', function ($q) use ($searchGenreName) {
                    $q->where('name', 'like', '%' . $searchGenreName . '%');
                });
            }

            if ($searchTitle) {
                $query->where('title', 'like', '%' . $searchTitle . '%');
            }

            // Sorting logic
            if ($sortBy === 'popularity') {
                $query->orderByDesc('view_count');
            } elseif ($sortBy === 'latest') {
                $query->orderByDesc('created_at');
            }

            $contents = $query->orderBy('view_count', 'desc')->paginate($paginateCount);

            return response()->json([
                'success' => true,
                'message' => 'Content list retrieved successfully',
                'data' => $contents,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve content list',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
