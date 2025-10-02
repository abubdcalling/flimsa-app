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
            $ads = Ad::all()->map(function ($ad) {
                return [
                    'id' => $ad->id,
                    'ads' => $ad->ads,  // directly return the stored string (URL or base64)
                    'created_at' => $ad->created_at,
                    'updated_at' => $ad->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => "Ad fetched successfully.",
                'data' => $ads,
            ]);
        } catch (\Exception $e) {
            \Log::error('Ad index error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch ads',
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
            'ads' => 'required|string',
        ]);

        try {
            $ad = Ad::create([
                'ads' => $request->input('ads'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ad stored successfully.',
                'data' => [
                    'id' => $ad->id,
                    'ads' => $ad->ads,
                ],
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Ad save error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to store ad.',
            ], 500);
        }
    }

    // public function show($id)
    // {
    //     try {
    //         $ad = Ad::findOrFail($id);

    //         return response()->json([
    //             'success' => true,
    //             'data' => $ad
    //         ], 200);
    //     } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Ad not found'
    //         ], 404);
    //     } catch (\Exception $e) {
    //         \Log::error('Ad show error: ' . $e->getMessage());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to fetch ad'
    //         ], 500);
    //     }
    // }

    public function show($id)
    {
        try {
            $ad = Ad::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => "Ad fetched successfully.",
                'data' => [
                    'id' => $ad->id,
                    'ads' => $ad->ads,  // directly return the stored string
                    'created_at' => $ad->created_at,
                    'updated_at' => $ad->updated_at,
                ]
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ad not found',
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Ad show error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch ad',
            ], 500);
        }
    }

    // public function update(Request $request, $id)
    // {
    //     $request->validate([
    //         'ads' => 'nullable|mimes:mp4,avi,mpeg,qt',  // 50MB max
    //     ]);

    //     try {
    //         $ad = Ad::findOrFail($id);

    //         // Check if a new file is uploaded
    //         if ($request->hasFile('ads')) {
    //             $file = $request->file('ads');

    //             // Optionally: delete the old file from storage (if path was relative)
    //             if ($ad->ads && str_contains($ad->ads, 'storage/ads')) {
    //                 $oldPath = str_replace(asset('storage') . '/', '', $ad->ads);  // ads/file.mp4
    //                 \Storage::disk('public')->delete($oldPath);
    //             }

    //             // Upload the new file
    //             $filePath = $file->store('ads', 'public');
    //             $fullPath = asset('storage/' . $filePath);

    //             // Update with new video path
    //             $ad->ads = $fullPath;
    //         }

    //         $ad->save();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Ad updated successfully',
    //             'data' => $ad
    //         ], 200);
    //     } catch (\Exception $e) {
    //         \Log::error('Ad update error: ' . $e->getMessage());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to update ad'
    //         ], 500);
    //     }
    // }

    public function update(Request $request, $id)
    {
        $request->validate([
            'ads' => 'required|string',  // base64 string or URL
        ]);

        try {
            $ad = Ad::findOrFail($id);

            // Update the value
            $ad->ads = $request->input('ads');
            $ad->save();

            return response()->json([
                'success' => true,
                'message' => 'Ad updated successfully.',
                'data' => [
                    'id' => $ad->id,
                    'ads' => $ad->ads,
                ],
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ad not found.',
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Ad update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update ad.',
            ], 500);
        }
    }

    public function destroy(Ad $ad)
    {
        try {
            // Since you store 'ads' as a string (URL or base64),
            // if it's a local file path, you may want to delete the file.
            // But if it's a URL or base64, no local file to delete.
            // You can check if the stored string is a file path and delete accordingly.

            // Example: if it's a local path relative to public folder
            if (file_exists(public_path($ad->ads))) {
                unlink(public_path($ad->ads));
            }

            $ad->delete();

            return response()->json([
                'success' => true,
                'message' => 'Ad deleted successfully',
            ]);
        } catch (\Exception $e) {
            \Log::error('Ad delete error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete ad',
            ], 500);
        }
    }
}
