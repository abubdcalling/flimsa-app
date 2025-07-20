<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdController extends Controller
{
    public function index()
    {
        try {
            $ads = Ad::all();
            return response()->json([
                'success' => true,
                'data' => $ads
            ]);
        } catch (\Exception $e) {
            Log::error('Ad index error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch ads'
            ], 500);
        }
    }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'ads' => 'required|mimes:mp4,avi,mpeg,qt|max:51200',  // 50MB max
    //     ]);

    //     try {
    //         // Upload the file
    //         if ($request->hasFile('ads')) {
    //             $file = $request->file('ads');

    //             // Store in public disk (storage/app/public/ads)
    //             $filePath = $file->store('ads', 'public');

    //             // Get full public path (e.g., http://yourdomain.com/storage/ads/filename.mp4)
    //             $fullPath = asset('storage/' . $filePath);
    //         } else {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'No video file uploaded.',
    //             ], 400);
    //         }

    //         // Save to DB
    //         $ad = Ad::create([
    //             'ads' => $fullPath,  // Save the full URL or path
    //         ]);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Ad created successfully',
    //             'data' => $ad
    //         ], 201);
    //     } catch (\Exception $e) {
    //         \Log::error('Ad store error: ' . $e->getMessage());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to create ad'
    //         ], 500);
    //     }
    // }

    public function store(Request $request)
    {
        $request->validate([
            'ads' => 'required|mimes:mp4,avi,mpeg,qt|max:51200',  // max 50MB
        ]);

        try {
            if ($request->hasFile('ads')) {
                $file = $request->file('ads');
                $fileName = time() . '_' . $file->getClientOriginalName();

                // Move the file to public/ads/
                $file->move(public_path('ads'), $fileName);

                // Save the file name or full path in DB
                $ad = Ad::create([
                    'ads' => 'ads/' . $fileName,  // or just $fileName if you prefer
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Ad uploaded successfully.',
                    'data' => [
                        'id' => $ad->id,
                        'ads_url' => asset('ads/' . $fileName),
                    ],
                ], 201);
            }

            return response()->json([
                'success' => false,
                'message' => 'No file was uploaded.',
            ], 400);
        } catch (\Exception $e) {
            \Log::error('Ad upload error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload ad.',
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $ad = Ad::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $ad
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ad not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Ad show error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch ad'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'ads' => 'nullable|mimes:mp4,avi,mpeg,qt',  // 50MB max
        ]);

        try {
            $ad = Ad::findOrFail($id);

            // Check if a new file is uploaded
            if ($request->hasFile('ads')) {
                $file = $request->file('ads');

                // Optionally: delete the old file from storage (if path was relative)
                if ($ad->ads && str_contains($ad->ads, 'storage/ads')) {
                    $oldPath = str_replace(asset('storage') . '/', '', $ad->ads);  // ads/file.mp4
                    \Storage::disk('public')->delete($oldPath);
                }

                // Upload the new file
                $filePath = $file->store('ads', 'public');
                $fullPath = asset('storage/' . $filePath);

                // Update with new video path
                $ad->ads = $fullPath;
            }

            $ad->save();

            return response()->json([
                'success' => true,
                'message' => 'Ad updated successfully',
                'data' => $ad
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Ad update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update ad'
            ], 500);
        }
    }

    public function destroy(Ad $ad)
    {
        try {
            $ad->delete();

            return response()->json([
                'success' => true,
                'message' => 'Ad deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Ad delete error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete ad'
            ], 500);
        }
    }
}
