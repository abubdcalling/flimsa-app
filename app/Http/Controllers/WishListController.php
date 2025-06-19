<?php 

namespace App\Http\Controllers;

use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishListController extends Controller
{
    // GET /api/wishlist
    public function index()
    {
        $wishlists = Wishlist::with('content')
            ->where('user_id', Auth::id())
            ->get();

        return response()->json($wishlists);
    }

    // POST /api/wishlist
    public function store(Request $request)
    {
        $validated = $request->validate([
            'content_id' => 'required|exists:contents,id',
            'isWished' => 'boolean',
        ]);

        $wishlist = Wishlist::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'content_id' => $validated['content_id'],
            ],
            [
                'isWished' => $validated['isWished'] ?? true,
            ]
        );

        return response()->json($wishlist, 201);
    }

    // GET /api/wishlist/{id}
    public function show($id)
    {
        $wishlist = Wishlist::with('content')
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json($wishlist);
    }

    // PUT /api/wishlist/{id}
    public function update(Request $request, $id)
    {
        $wishlist = Wishlist::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'isWished' => 'boolean',
        ]);

        $wishlist->update($validated);

        return response()->json($wishlist);
    }

    // DELETE /api/wishlist/{id}
    public function destroy($id)
    {
        $wishlist = Wishlist::where('user_id', Auth::id())->findOrFail($id);
        $wishlist->delete();

        return response()->json(['message' => 'Wishlist item removed.']);
    }
}
