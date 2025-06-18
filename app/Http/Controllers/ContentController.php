<?php

namespace App\Http\Controllers;

use App\Models\Content;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContentController extends Controller
{
    // GET /api/contents

   public function index(Request $request)
    { 
        try {
            $paginateCount = $request->get('paginate_count', 10);
            $userId = $request->user()->id ?? null;

            $genreName = $request->get('genre_name');     // e.g., "Action"
            $releaseYear = $request->get('release_year'); // e.g., 2023

            // Group total likes by content_id where is_liked is true
            $likesGrouped = DB::table('likes')
                ->select('content_id', DB::raw('COUNT(*) as total_likes'))
                ->where('is_liked', true)
                ->groupBy('content_id')
                ->pluck('total_likes', 'content_id');

            // Build the base query with genre relationship
            $query = Content::with('genres')
                ->select('id', 'video1', 'title', 'description', 'publish', 'schedule', 'genre_id', 'image', 'view_count', 'created_at');

            // Filter by genre name (relationship)
            if ($genreName) {
                $query->whereHas('genres', function ($q) use ($genreName) {
                    $q->where('name', 'like', "%{$genreName}%");
                });
            }

            // Filter by release year (publish date)
            if ($releaseYear) {
                $query->whereYear('publish', $releaseYear);
            }

            // Paginate and transform
            $contents = $query->latest()->paginate($paginateCount);

            $contents->getCollection()->transform(function ($content) use ($userId, $likesGrouped) {
                $content->total_view = $content->view_count;
                unset($content->view_count);

                $content->total_likes = (int) ($likesGrouped[$content->id] ?? 0);
                $content->genre_name = optional($content->genres)->name;
                unset($content->genres);

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

    // POST /api/contents
    public function store(Request $request)
    {
        $validated = $request->validate([
            'video1' => 'nullable|file|mimes:mp4,mov,avi,wmv|max:4294967296',
            'title' => 'required|string',
            'description' => 'required|string',
            'publish' => 'required|in:public,private,schedule',
            'schedule' => 'nullable|date',
            'genre_id' => 'required|exists:genres,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            // Upload video to Cloudinary
            $videoName = null;
            if ($request->hasFile('video1')) {
                $videoFile = $request->file('video1');
                // dd($videoFile);
                // $uploadedVideo = Cloudinary::uploadVideo(
                //     $videoFile->getRealPath(),
                //     [
                //         'folder' => 'Contents/Videos',
                //         'resource_type' => 'video'
                //     ]
                // );
                // $videoName = $uploadedVideo->getSecurePath();
                $videoName = time() . '_content_video.' . $videoFile->getClientOriginalExtension();
                $videoFile->move(public_path('uploads/Videos'), $videoName);
            }

            // Upload image to local storage
            $imageName = null;
            if ($request->hasFile('image')) {
                $imageFile = $request->file('image');
                $imageName = time() . '_content_image.' . $imageFile->getClientOriginalExtension();
                $imageFile->move(public_path('uploads/Contents'), $imageName);
            }

            // Store content
            $content = Content::create([
                'video1' => $videoName,
                'title' => $validated['title'],
                'description' => $validated['description'],
                'publish' => $validated['publish'],
                'schedule' => $validated['schedule'] ,//=== 'schedule' ? $validated['schedule'] : now(),
                'genre_id' => $validated['genre_id'],
                'image' => $imageName,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Content created successfully.',
                'data' => $content,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to store content: ' . $e->getMessage());
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

        return response()->json([
            'success' => true,
            'data' => $content,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'video1' => 'nullable|file|mimes:mp4,mov,avi,wmv|max:4294967296',
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
}
