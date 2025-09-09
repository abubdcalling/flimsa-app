<?php 

namespace App\Http\Controllers;

use App\Models\Cover;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CoverController extends Controller
{
    /**
     * Create or update the single cover content.
     */
    public function postOrUpdateCover(Request $request)
    {
        $request->validate([
            'content_id' => 'required|exists:contents,id',
        ]);

        try {
            // Ensure only one cover exists
            Cover::truncate();

            // Create new cover
            $cover = Cover::create([
                'content_id' => $request->content_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cover content updated successfully.',
                'cover' => $cover->load('content'),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to update cover content: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update cover content.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Optional: Get the current cover content.
     */
    public function getCover()
    {
        try {
            $cover = Cover::with('content')->first();

            if (!$cover) {
                return response()->json([
                    'success' => false,
                    'message' => 'No cover content found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'cover' => $cover,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve cover content: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving cover content.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
