<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishListController extends Controller
{
    // GET /api/wishlist

    public function index(Request $request)
    {
        try {
            $userId = $request->input('user_id', Auth::id());

            // Only allow self or subscriber role
            if ($userId != Auth::id() && !Auth::user()->hasRole('subscriber')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access.'
                ], 403);
            }

            // Get all content IDs from wishlists for this user
            $contentIds = Wishlist::where('user_id', $userId)
                ->pluck('content_id');

            if ($contentIds->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No wishlisted content found for this user.'
                ], 404);
            }

            // Fetch content records with genre relation
            $contents = Content::with('genre')
                ->whereIn('id', $contentIds)
                ->where('publish', 'public')
                ->get()
                ->map(function ($content) {
                    return [
                        'id' => $content->id,
                        'title' => $content->title,
                        'publish' => $content->publish,
                        'genre_name' => $content->genre ? $content->genre->name : null,
                        'video1' => $content->video1,
                        'description' => $content->description,
                        'schedule' => $content->schedule,
                        'image' => $content->image,
                        'view_count' => $content->view_count,
                        'created_at' => $content->created_at,
                        'updated_at' => $content->updated_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Wishlisted content fetched successfully.',
                'data' => $contents
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch wishlisted content.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // POST /api/wishlist

    public function update(Request $request, $contentId)
    {
        $validated = $request->validate([
            'isWished' => 'required|boolean',
        ]);

        $wishlist = Wishlist::where('user_id', Auth::id())
            ->where('content_id', $contentId)
            ->first();

        if (!$wishlist) {
            return response()->json([
                'success' => false,
                'message' => 'Wishlist entry not found.'
            ], 404);
        }

        $wishlist->isWished = $validated['isWished'];
        $wishlist->save();

        return response()->json([
            'success' => true,
            'message' => 'Wishlist updated successfully.',
            'data' => $wishlist
        ], 200);
    }

    // GET /api/wishlist/{id}
    public function show($id)
    {
        $wishlist = Wishlist::with('content')
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json($wishlist);
    }



    // DELETE /api/wishlist/{id}

    public function destroy($contentId)
    {
        $wishlist = Wishlist::where('user_id', Auth::id())
            ->where('content_id', $contentId)
            ->first();

        if (!$wishlist) {
            return response()->json([
                'success' => false,
                'message' => 'Wishlist item not found.'
            ], 404);
        }

        $wishlist->delete();

        return response()->json([
            'success' => true,
            'message' => 'Wishlist item removed.'
        ]);
    }
}
