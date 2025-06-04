<?php

namespace App\Http\Controllers;

use App\Models\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContentController extends Controller
{
    // GET /api/contents
    public function index(Request $request)
    {
        try {
            $contents = Content::with('genres')
                ->select('id', 'video1', 'title', 'description', 'publish', 'schedule', 'genre_id', 'image', 'created_at')
                ->latest()
                ->paginate($request->get('paginate_count', 10));

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

    // POST /api/contents
    public function store(Request $request)
    {
        $validated = $request->validate([
            'video1'       => 'nullable|file|mimes:mp4,mov,avi,wmv|max:4294967296',
            'title'        => 'required|string',
            'description'  => 'required|string',
            'publish'      => 'required|in:public,private,schedule',
            'schedule'     => 'nullable|date',
            'genre_id'     => 'required|exists:genres,id',
            'image'        => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            // Handle video upload safely
            $videoName = null;
            if ($request->hasFile('video1')) {
                $videoFile = $request->file('video1');
                $videoName = time() . '_content_video.' . $videoFile->getClientOriginalExtension();
                $videoFile->move(public_path('uploads/Videos'), $videoName);
            }

            // Handle image upload safely
            $imageName = null;
            if ($request->hasFile('image')) {
                $imageFile = $request->file('image');
                $imageName = time() . '_content_image.' . $imageFile->getClientOriginalExtension();
                $imageFile->move(public_path('uploads/Contents'), $imageName);
            }

            $content = Content::create([
                'video1'      => $videoName,
                'title'       => $validated['title'],
                'description' => $validated['description'],
                'publish'     => $validated['publish'],
                'schedule'    => $validated['publish'] === 'schedule' ? $validated['schedule'] : now(),
                'genre_id'    => $validated['genre_id'],
                'image'       => $imageName,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Content created successfully.',
                'data'    => $content,
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

        return response()->json([
            'success' => true,
            'data' => $content,
        ]);
    }

    // PUT /api/contents/{id}
    public function update(Request $request, $id)
    {
        $content = Content::find($id);

        if (!$content) {
            return response()->json([
                'success' => false,
                'message' => 'Content not found.',
            ], 404);
        }

        $validated = $request->validate([
            'video1'       => 'nullable|file|mimes:mp4,mov,avi,wmv|max:204800',
            'title'        => 'sometimes|required|string',
            'description'  => 'sometimes|required|string',
            'publish'      => 'sometimes|required|in:public,private,schedule',
            'schedule'     => 'nullable|date',
            'genre_id'     => 'sometimes|required|exists:genres,id',
            'image'        => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            if ($request->hasFile('video1')) {
                if ($content->video1 && file_exists(public_path('uploads/Videos/' . $content->video1))) {
                    unlink(public_path('uploads/Videos/' . $content->video1));
                }

                $videoName = time() . '_video.' . $request->file('video1')->getClientOriginalExtension();
                $request->file('video1')->move(public_path('uploads/Videos'), $videoName);
                $content->video1 = $videoName;
            }

            if ($request->hasFile('image')) {
                if ($content->image && file_exists(public_path('uploads/Contents/' . $content->image))) {
                    unlink(public_path('uploads/Contents/' . $content->image));
                }

                $imageName = time() . '_image.' . $request->file('image')->getClientOriginalExtension();
                $request->file('image')->move(public_path('uploads/Contents'), $imageName);
                $content->image = $imageName;
            }

            $content->update(array_merge(
                $validated,
                ['schedule' => $validated['publish'] === 'schedule' ? $validated['schedule'] : $content->schedule]
            ));

            return response()->json([
                'success' => true,
                'message' => 'Content updated successfully.',
                'data'    => $content,
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
        $content = Content::find($id);

        if (!$content) {
            return response()->json([
                'success' => false,
                'message' => 'Content not found.',
            ], 404);
        }

        try {
            if ($content->video1 && file_exists(public_path('uploads/Videos/' . $content->video1))) {
                unlink(public_path('uploads/Videos/' . $content->video1));
            }

            if ($content->image && file_exists(public_path('uploads/Contents/' . $content->image))) {
                unlink(public_path('uploads/Contents/' . $content->image));
            }

            $content->delete();

            return response()->json([
                'success' => true,
                'message' => 'Content deleted successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete content: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete content.',
            ], 500);
        }
    }
}
