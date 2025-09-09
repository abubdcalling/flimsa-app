<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Subtitle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubtitleController extends Controller
{
    public function index()
    {
        try {
            $subtitles = Subtitle::all();  // Fetch only subtitles without relationships

            return response()->json([
                'success' => true,
                'message' => 'All subtitles fetched successfully.',
                'data' => $subtitles
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subtitles.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $subtitle = Subtitle::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Subtitle fetched successfully.',
                'data' => $subtitle
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Subtitle not found.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subtitle.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // public function store(Request $request, $contentId)
    // {
    //     try {
    //         // Validate input
    //         $request->validate([
    //             'language' => 'required|string|max:50',
    //             'file_path' => 'required|file',  // 2MB max
    //         ]);

    //         // Handle file upload
    //         $file = $request->file('file_path');
    //         $path = $file->store('subtitles', 'public');  // store in /storage/app/public/subtitles

    //         // Create subtitle
    //         $subtitle = Subtitle::create([
    //             'content_id' => $contentId,
    //             'language' => $request->language,
    //             'file_path' => $path,
    //         ]);

    //         return response()->json([
    //             'message' => 'Subtitle uploaded successfully.',
    //             'data' => $subtitle
    //         ], 201);
    //     } catch (\Exception $e) {
    //         Log::error('Subtitle upload failed: ' . $e->getMessage());

    //         return response()->json([
    //             'message' => 'Failed to upload subtitle.',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function store(Request $request, $contentId)
    {
        try {
            $subtitles = $request->file('subtitles');

            if (!$subtitles || !is_array($subtitles)) {
                return response()->json(['message' => 'No subtitles uploaded.'], 400);
            }

            $savedSubtitles = [];

            foreach ($subtitles as $language => $file) {
                // Validate each file and language key
                if (!$file || !$file->isValid()) {
                    continue;
                }

                if (!preg_match('/^[a-z]{2}$/i', $language)) {
                    continue;  // skip invalid language keys
                }

                // Store the file
                $path = $file->store('subtitles', 'public');

                // Save to database
                $subtitle = Subtitle::create([
                    'content_id' => $contentId,
                    'language' => $language,
                    'file_path' => $path,
                ]);

                $savedSubtitles[] = $subtitle;
            }

            return response()->json([
                'message' => 'Subtitles uploaded successfully.',
                'data' => $savedSubtitles
            ], 201);
        } catch (\Exception $e) {
            Log::error('Subtitle upload failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to upload subtitles.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $subtitle = Subtitle::findOrFail($id);
        $subtitle->delete();

        return response()->json(['message' => 'Subtitle deleted successfully.']);
    }
}
