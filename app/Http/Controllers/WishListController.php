<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\WishList;
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

            // Get content IDs from wishlists where isWished is true
            $contentIds = WishList::where('user_id', $userId)
                // ->where('isWished', true)
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
                        'genre_name' => optional($content->genre)->name,
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'content_id' => 'required|exists:contents,id',
            'isWished' => 'required|boolean',
        ]);

        $wishlist = WishList::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'content_id' => $validated['content_id'],
            ],
            [
                'isWished' => $validated['isWished'],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Wishlist item stored successfully.',
            'data' => $wishlist
        ], 201);
    }

    // POST /api/wishlist

    public function update(Request $request, $contentId)
    {
        $validated = $request->validate([
            'isWished' => 'required|boolean',
        ]);

        $wishlist = WishList::where('user_id', Auth::id())
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
        $wishlist = WishList::with('content')
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json($wishlist);
    }

    // DELETE /api/wishlist/{id}

    public function destroy($contentId)
    {
        $wishlist = WishList::where('user_id', Auth::id())
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
