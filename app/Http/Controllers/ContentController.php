<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Genre;
use App\Models\History;
use App\Models\Subscription;
use App\Models\User;
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
    public function dashbaord()
    {
        try {
            // Total users
            $totalUsers = User::count();

            // Total content
            $totalContents = Content::count();

            // Revenue calculation (sum of all subscription prices for users who have subscriptions)
            $totalRevenue = DB::table('users')
                ->join('subscriptions', 'users.plan_type', '=', 'subscriptions.plan_name')
                ->select(DB::raw('SUM(subscriptions.price) as revenue'))
                ->value('revenue') ?? 0;

            // Users by plan type
            $withAdsCount = User::where('plan_type', 'withads')->count();
            $withoutAdsCount = User::where('plan_type', 'withoutads')->count();

            // Genre percentage breakdown
            $genreCounts = Content::select('genre_id', DB::raw('count(*) as total'))
                ->groupBy('genre_id')
                ->pluck('total', 'genre_id');

            $totalContentCount = $genreCounts->sum();

            $genrePercentages = Genre::whereIn('id', $genreCounts->keys())
                ->get()
                ->map(function ($genre) use ($genreCounts, $totalContentCount) {
                    $percentage = $totalContentCount > 0
                        ? round(($genreCounts[$genre->id] / $totalContentCount) * 100, 2)
                        : 0;

                    return [
                        'genre_id' => $genre->id,
                        'name' => $genre->name,
                        'percentage' => $percentage,
                    ];
                });

            // Monthly revenue breakdown by plan type
            $currentYear = Carbon::now()->year;

            $monthlyRevenue = DB::table('users')
                ->join('subscriptions', 'users.plan_type', '=', 'subscriptions.plan_name')
                ->whereIn('users.plan_type', ['withads', 'withoutads'])
                ->whereYear('users.created_at', $currentYear)
                ->select(
                    DB::raw('MONTH(users.created_at) as month'),
                    'users.plan_type',
                    DB::raw('SUM(subscriptions.price) as total')
                )
                ->groupBy(DB::raw('MONTH(users.created_at)'), 'users.plan_type')
                ->orderBy(DB::raw('MONTH(users.created_at)'))
                ->get()
                ->groupBy('plan_type');

            $months = range(1, 12);
            $monthNames = [
                1 => 'Jan',
                2 => 'Feb',
                3 => 'Mar',
                4 => 'Apr',
                5 => 'May',
                6 => 'Jun',
                7 => 'Jul',
                8 => 'Aug',
                9 => 'Sep',
                10 => 'Oct',
                11 => 'Nov',
                12 => 'Dec',
            ];

            $withAdsMonthly = [];
            $withoutAdsMonthly = [];

            foreach ($months as $month) {
                $withAdsMonthly[] = [
                    'month' => $monthNames[$month],
                    'revenue' => $monthlyRevenue->get('withads')?->firstWhere('month', $month)->total ?? 0,
                ];

                $withoutAdsMonthly[] = [
                    'month' => $monthNames[$month],
                    'revenue' => $monthlyRevenue->get('withoutads')?->firstWhere('month', $month)->total ?? 0,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_user' => $totalUsers,
                    'total_content' => $totalContents,
                    'total_revenue' => $totalRevenue,
                    'monthly_revenue' => [
                        'with_ads' => $withAdsMonthly,
                        'without_ads' => $withoutAdsMonthly,
                    ],
                    'genre_distribution' => $genrePercentages,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dashboard data could not be retrieved.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function relatedContent($id)
    {
        try {
            // Find the content by ID
            $content = Content::find($id);

            if (!$content) {
                return response()->json([
                    'success' => false,
                    'message' => 'Content not found.'
                ], 404);
            }

            // Get pagination value from request or set default
            $perPage = request()->query('per_page', 10);  // default 10
            $page = request()->query('page', 1);  // default 1

            // Fetch related content with same genre, excluding the current one
            $relatedContents = Content::with('genre')
                ->where('genre_id', $content->genre_id)
                ->where('id', '!=', $id)
                ->latest()
                ->paginate($perPage, ['*'], 'page', $page);

            // Transform the items
            $relatedContents->getCollection()->transform(function ($item) {
                return [
                    'id' => $item->id,
                    'video1' => $item->video1,
                    'title' => $item->title,
                    'description' => $item->description,
                    'publish' => $item->publish,
                    'schedule' => $item->schedule,
                    'genre_id' => $item->genre_id,
                    'genre_name' => $item->genre->name ?? null,
                    'director_name' => $item->director_name,
                    'profile_pic' => $item->profile_pic,
                    'image' => $item->image,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                    'view_count' => $item->view_count,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Related content fetched successfully.',
                'data' => $relatedContents->items(),
                'meta' => [
                    'current_page' => $relatedContents->currentPage(),
                    'last_page' => $relatedContents->lastPage(),
                    'per_page' => $relatedContents->perPage(),
                    'total' => $relatedContents->total()
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch related content.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function History(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 10);
            $userId = $request->input('user_id', Auth::id());
              $device_id = $request->query('device_id');
            //   return $device_id;
        

            // Optional access rule: allow self or admin
            if ($userId != Auth::id() && !Auth::user()->hasRole('subscriber')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access.'
                ], 403);
            }

            // Get content IDs viewed by user
            $contentIds = History::where('user_id', $userId)
                ->pluck('content_id');

            // Fetch contents with optional genre relation
            $contents = Content::with('genre')
                ->whereIn('id', $contentIds)
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
                ->select('id', 'video1', 'title', 'director_name', 'profile_pic', 'description', 'publish', 'schedule', 'genre_id', 'image', 'view_count', 'created_at')
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
        try {
           

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

            $profilePicUrl = null;

            if ($request->hasFile('profile_pic')) {
                $profileFile = $request->file('profile_pic');
                $profilePath = $profileFile->store('profile_pics', 's3');

                if (!$profilePath) {
                    throw new \Exception('Failed to upload profile picture to S3');
                }

                Storage::disk('s3')->setVisibility($profilePath, 'public');
                $profilePicUrl = Storage::disk('s3')->url($profilePath);
            }

            $content = Content::create([
                'video1' => $request->input('video1'),  // S3 URL
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'publish' => $request->input('publish'),
                'schedule' => $request->input('schedule') === 'schedule' ? $request->input('schedule') : now(),
                'genre_id' => $request->input('genre_id'),
                'image' => $imageName,  // S3 URL
                'director_name' => $request->input('director_name'),
                'profile_pic' => $profilePicUrl,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Content created successfully.',
                'data' => $content,
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Failed to store content', [
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
    public function show($id , Request $request)
    {
        // return $id;
        $content = Content::with('genres')->find($id);

        if (!$content) {
            return response()->json([
                'success' => false,
                'message' => 'Content not found.',
            ], 404);
        }

        // Increment view_count
        $content->increment('view_count');

        // Initialize like/wishlist info
        $isLiked = false;
        $isWishlisted = false;

        // Log view and fetch like/wishlist if user is logged in
        if (Auth::check()) {
            $userId = Auth::id();

            // Log history
            History::updateOrCreate(
                ['user_id' => $userId, 'content_id' => $id],
                ['updated_at' => now()]
            );

            // Check if liked
            $isLiked = \App\Models\Like::where('user_id', $userId)
                ->where('content_id', $id)
                ->exists();

            // Check if wishlisted
            $isWishlisted = \App\Models\WishList::where('user_id', $userId)
                ->where('content_id', $id)
                ->exists();
        }

        // return response()->json($content);

        return response()->json([
            'success' => true,
            'data' => $content,
            'liked' => $isLiked,
            'wishlisted' => $isWishlisted,
        ]);
    }

    // DELETE /api/contents/{id}

    // public function update(Request $request, $id)
    // {
    //     try {
    //         $content = Content::findOrFail($id);

    //         // Update video if new file uploaded
    //         if ($request->hasFile('video1')) {
    //             // Optional: Delete old video from S3
    //             if ($content->video1) {
    //                 $oldVideoPath = parse_url($content->video1, PHP_URL_PATH);
    //                 $oldVideoPath = ltrim($oldVideoPath, '/');
    //                 Storage::disk('s3')->delete($oldVideoPath);
    //             }

    //             $videoFile = $request->file('video1');
    //             $videoPath = $videoFile->store('videos', 's3');

    //             if (!$videoPath) {
    //                 throw new \Exception('Failed to upload new video to S3');
    //             }

    //             Storage::disk('s3')->setVisibility($videoPath, 'public');
    //             $content->video1 = Storage::disk('s3')->url($videoPath);
    //         }

    //         // Update image if new file uploaded
    //         if ($request->hasFile('image')) {
    //             // Optional: Delete old image from S3
    //             if ($content->image) {
    //                 $oldImagePath = parse_url($content->image, PHP_URL_PATH);
    //                 $oldImagePath = ltrim($oldImagePath, '/');
    //                 Storage::disk('s3')->delete($oldImagePath);
    //             }

    //             $imageFile = $request->file('image');
    //             $imagePath = $imageFile->store('images', 's3');

    //             if (!$imagePath) {
    //                 throw new \Exception('Failed to upload new image to S3');
    //             }

    //             Storage::disk('s3')->setVisibility($imagePath, 'public');
    //             $content->image = Storage::disk('s3')->url($imagePath);
    //         }

    //         // Update profile_pic if new file uploaded
    //         if ($request->hasFile('profile_pic')) {
    //             if ($content->profile_pic) {
    //                 $oldProfilePicPath = ltrim(parse_url($content->profile_pic, PHP_URL_PATH), '/');
    //                 Storage::disk('s3')->delete($oldProfilePicPath);
    //             }

    //             $profilePicFile = $request->file('profile_pic');
    //             $profilePicPath = $profilePicFile->store('profiles', 's3');

    //             if (!$profilePicPath) {
    //                 throw new \Exception('Failed to upload new profile picture to S3');
    //             }

    //             Storage::disk('s3')->setVisibility($profilePicPath, 'public');
    //             $content->profile_pic = Storage::disk('s3')->url($profilePicPath);
    //         }

    //         // Update other fields
    //         $content->title = $request->input('title', $content->title);
    //         $content->description = $request->input('description', $content->description);
    //         $content->publish = $request->input('publish', $content->publish);
    //         $content->schedule = $request->input('publish') === 'schedule'
    //             ? $request->input('schedule')
    //             : $content->schedule;
    //         $content->genre_id = $request->input('genre_id', $content->genre_id);
    //         $content->director_name = $request->input('director_name', $content->director_name);

    //         $content->save();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Content updated successfully.',
    //             'data' => $content,
    //         ]);
    //     } catch (\Exception $e) {
    //         \Log::error('Failed to update content', [
    //             'message' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to update content.',
    //         ], 500);
    //     }
    // }

    public function update(Request $request, $id)
    {
        try {
            $content = Content::findOrFail($id);

            // Update video1 if a new video is uploaded
            if ($request->hasFile('video1')) {
                if ($content->video1) {
                    $oldVideoPath = ltrim(parse_url($content->video1, PHP_URL_PATH), '/');
                    Storage::disk('s3')->delete($oldVideoPath);
                }

                $videoFile = $request->file('video1');
                $videoPath = $videoFile->store('videos', 's3');

                if (!$videoPath) {
                    throw new \Exception('Failed to upload new video to S3');
                }

                Storage::disk('s3')->setVisibility($videoPath, 'public');
                $content->video1 = Storage::disk('s3')->url($videoPath);
            }

            // Update image if a new image is uploaded
            if ($request->hasFile('image')) {
                if ($content->image) {
                    $oldImagePath = ltrim(parse_url($content->image, PHP_URL_PATH), '/');
                    Storage::disk('s3')->delete($oldImagePath);
                }

                $imageFile = $request->file('image');
                $imagePath = $imageFile->store('images', 's3');

                if (!$imagePath) {
                    throw new \Exception('Failed to upload new image to S3');
                }

                Storage::disk('s3')->setVisibility($imagePath, 'public');
                $content->image = Storage::disk('s3')->url($imagePath);
            }

            // Update profile_pic if a new profile picture is uploaded
            if ($request->hasFile('profile_pic')) {
                if ($content->profile_pic) {
                    $oldProfilePath = ltrim(parse_url($content->profile_pic, PHP_URL_PATH), '/');
                    Storage::disk('s3')->delete($oldProfilePath);
                }

                $profileFile = $request->file('profile_pic');
                $profilePath = $profileFile->store('profile_pics', 's3');

                if (!$profilePath) {
                    throw new \Exception('Failed to upload new profile picture to S3');
                }

                Storage::disk('s3')->setVisibility($profilePath, 'public');
                $content->profile_pic = Storage::disk('s3')->url($profilePath);
            }

            // Update other fields
            $content->title = $request->input('title', $content->title);
            $content->description = $request->input('description', $content->description);
            $content->publish = $request->input('publish', $content->publish);
            $content->schedule = $request->input('publish') === 'schedule'
                ? $request->input('schedule')
                : $content->schedule;
            $content->genre_id = $request->input('genre_id', $content->genre_id);
            $content->director_name = $request->input('director_name', $content->director_name);

            $content->save();

            return response()->json([
                'success' => true,
                'message' => 'Content updated successfully.',
                'data' => $content,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to update content', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update content.',
            ], 500);
        }
    }

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
