<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Cover;
use App\Models\Genre;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GenreController extends Controller
{
    // updated code 30/7/2025
    // public function Home(Request $request)
    // {
    //     try {
    //         // Get pagination size from request or default to 10
    //         $perPage = $request->get('per_page', 10);
    //         $filterGenre = $request->get('genre');

    //         // Get genre names
    //         $genreNames = Genre::pluck('name');

    //         // Fetch cover content (if any)
    //         $coverEntry = Cover::with('content.genres')->first();

    //         $coverContent = collect();  // Initialize as empty collection

    //         if ($coverEntry && $coverEntry->content) {
    //             $coverContent->push([
    //                 'id' => $coverEntry->content->id,
    //                 'title' => $coverEntry->content->title,
    //                 'description' => $coverEntry->content->description,
    //                 'director_name' => $coverEntry->content->director_name,
    //                 'profile_pic' => $coverEntry->content->profile_pic,
    //                 'image' => $coverEntry->content->image,
    //                 'video1' => $coverEntry->content->video1,
    //                 'publish' => $coverEntry->content->publish,
    //                 'schedule' => $coverEntry->content->schedule,
    //                 'view_count' => $coverEntry->content->view_count,
    //                 'created_at' => $coverEntry->content->created_at,
    //                 'genre_name' => optional($coverEntry->content->genres)->name,
    //             ]);
    //         }

    //         // Latest 1 content
    //         $latestContent = Content::with('genres:id,name')
    //             ->select('id', 'title', 'description', 'director_name', 'profile_pic', 'image', 'video1', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
    //             ->latest()
    //             ->take(1)
    //             ->get()
    //             ->transform(function ($content) {
    //                 return [
    //                     'id' => $content->id,
    //                     'title' => $content->title,
    //                     'video1' => $content->video1,
    //                     'description' => $content->description,
    //                     'image' => $content->image,
    //                     'publish' => $content->publish,
    //                     'schedule' => $content->schedule,
    //                     'view_count' => $content->view_count,
    //                     'created_at' => $content->created_at,
    //                     'genre_name' => optional($content->genres)->name,
    //                     'director_name' => $content->director_name,
    //                     'profile_pic' => $content->profile_pic,
    //                 ];
    //             });

    //         // Fetch genre names
    //         $genreNames = Genre::pluck('name');

    //         // Fetch most viewed content (popular)
    //         $popularContents = Content::with('genres:id,name')
    //             ->select('id', 'title', 'video1', 'director_name', 'profile_pic', 'description', 'image', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
    //             ->orderByDesc('view_count')
    //             ->paginate($perPage);

    //         $popularContents->getCollection()->transform(function ($content) {
    //             return [
    //                 'id' => $content->id,
    //                 'title' => $content->title,
    //                 'description' => $content->description,
    //                 'image' => $content->image,
    //                 'video1' => $content->video1,
    //                 'publish' => $content->publish,
    //                 'schedule' => $content->schedule,
    //                 'view_count' => $content->view_count,
    //                 'created_at' => $content->created_at,
    //                 'genre_name' => optional($content->genres)->name,
    //                 'director_name' => $content->director_name,
    //                 'profile_pic' => $content->profile_pic,
    //             ];
    //         });

    //         // Fetch upcoming content (future schedule date)
    //         $upcomingContents = Content::with('genres:id,name')
    //             ->where('schedule', '>', Carbon::now())
    //             ->select('id', 'title', 'description', 'director_name', 'profile_pic', 'video1', 'image', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
    //             ->orderBy('schedule', 'asc')
    //             ->paginate($perPage);

    //         $upcomingContents->getCollection()->transform(function ($content) {
    //             return [
    //                 'id' => $content->id,
    //                 'title' => $content->title,
    //                 'description' => $content->description,
    //                 'image' => $content->image,
    //                 'video1' => $content->video1,
    //                 'publish' => $content->publish,
    //                 'schedule' => $content->schedule,
    //                 'view_count' => $content->view_count,
    //                 'created_at' => $content->created_at,
    //                 'genre_name' => optional($content->genres)->name,
    //                 'director_name' => $content->director_name,
    //                 'profile_pic' => $content->profile_pic,
    //             ];
    //         });

    //         // Comedy content (genre name = 'Comedy')
    //         $comedyContents = Content::with('genres:id,name')
    //             ->whereHas('genres', function ($query) {
    //                 $query->where('name', 'Comedy');
    //             })
    //             ->select('id', 'title', 'description', 'director_name', 'profile_pic', 'image', 'video1', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
    //             ->latest()
    //             ->paginate($perPage);

    //         $comedyContents->getCollection()->transform(function ($content) {
    //             return [
    //                 'id' => $content->id,
    //                 'title' => $content->title,
    //                 'description' => $content->description,
    //                 'image' => $content->image,
    //                 'video1' => $content->video1,
    //                 'publish' => $content->publish,
    //                 'schedule' => $content->schedule,
    //                 'view_count' => $content->view_count,
    //                 'created_at' => $content->created_at,
    //                 'genre_name' => optional($content->genres)->name,
    //                 'director_name' => $content->director_name,
    //                 'profile_pic' => $content->profile_pic,
    //             ];
    //         });

    //         // Family content (genre name = 'family')
    //         $familyContents = Content::with('genres:id,name')
    //             ->whereHas('genres', function ($query) {
    //                 $query->where('name', 'Family');
    //             })
    //             ->select('id', 'title', 'description', 'director_name', 'profile_pic', 'video1', 'image', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
    //             ->latest()
    //             ->paginate($perPage);

    //         $familyContents->getCollection()->transform(function ($content) {
    //             return [
    //                 'id' => $content->id,
    //                 'title' => $content->title,
    //                 'description' => $content->description,
    //                 'image' => $content->image,
    //                 'video1' => $content->video1,
    //                 'publish' => $content->publish,
    //                 'schedule' => $content->schedule,
    //                 'view_count' => $content->view_count,
    //                 'created_at' => $content->created_at,
    //                 'genre_name' => optional($content->genres)->name,
    //                 'director_name' => $content->director_name,
    //                 'profile_pic' => $content->profile_pic,
    //             ];
    //         });

    //         // Dramas content (genre name = 'dramas')
    //         $dramasContents = Content::with('genres:id,name')
    //             ->whereHas('genres', function ($query) {
    //                 $query->where('name', 'Dramas');
    //             })
    //             ->select('id', 'title', 'description', 'director_name', 'profile_pic', 'video1', 'image', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
    //             ->latest()
    //             ->paginate($perPage);

    //         $dramasContents->getCollection()->transform(function ($content) {
    //             return [
    //                 'id' => $content->id,
    //                 'title' => $content->title,
    //                 'description' => $content->description,
    //                 'image' => $content->image,
    //                 'video1' => $content->video1,
    //                 'publish' => $content->publish,
    //                 'schedule' => $content->schedule,
    //                 'view_count' => $content->view_count,
    //                 'created_at' => $content->created_at,
    //                 'genre_name' => optional($content->genres)->name,
    //                 'director_name' => $content->director_name,
    //                 'profile_pic' => $content->profile_pic,
    //             ];
    //         });

    //         // Tv Shows content (genre name = 'Tv shows')
    //         $tvshows = Content::with('genres:id,name')
    //             ->whereHas('genres', function ($query) {
    //                 $query->where('name', 'tv shows');
    //             })
    //             ->select('id', 'title', 'description', 'director_name', 'profile_pic', 'image', 'video1', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
    //             ->latest()
    //             ->paginate($perPage);

    //         $tvshows->getCollection()->transform(function ($content) {
    //             return [
    //                 'id' => $content->id,
    //                 'title' => $content->title,
    //                 'description' => $content->description,
    //                 'image' => $content->image,
    //                 'video1' => $content->video1,
    //                 'publish' => $content->publish,
    //                 'schedule' => $content->schedule,
    //                 'view_count' => $content->view_count,
    //                 'created_at' => $content->created_at,
    //                 'genre_name' => optional($content->genres)->name,
    //                 'director_name' => $content->director_name,
    //                 'profile_pic' => $content->profile_pic,
    //             ];
    //         });

    //         // Weekly Top Content (top 10 most viewed in last 7 days)
    //         $weeklyTopContents = Content::with('genres:id,name')
    //             ->whereBetween('created_at', [Carbon::now()->subDays(7), Carbon::now()])
    //             ->orderByDesc('view_count')
    //             ->select('id', 'title', 'description', 'director_name', 'profile_pic', 'video1', 'image', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
    //             ->take(10)
    //             ->get()
    //             ->transform(function ($content) {
    //                 return [
    //                     'id' => $content->id,
    //                     'title' => $content->title,
    //                     'description' => $content->description,
    //                     'image' => $content->image,
    //                     'video1' => $content->video1,
    //                     'publish' => $content->publish,
    //                     'schedule' => $content->schedule,
    //                     'view_count' => $content->view_count,
    //                     'created_at' => $content->created_at,
    //                     'genre_name' => optional($content->genres)->name,
    //                     'director_name' => $content->director_name,
    //                     'profile_pic' => $content->profile_pic,
    //                 ];
    //             });

    //         return response()->json([
    //             'success' => true,
    //             'data' => [
    //                 'genre_names' => $genreNames,
    //                 'popular' => $popularContents,
    //                 'upcoming' => $upcomingContents,
    //                 'comedy' => $comedyContents,
    //                 'family' => $familyContents,
    //                 'dramas' => $dramasContents,
    //                 'tv_shows' => $tvshows,
    //                 'weekly_top' => $weeklyTopContents,
    //                 'latest' => $coverContent,
    //                 // 'coverContent' => $coverContent,
    //             ]
    //         ]);
    //     } catch (\Exception $e) {
    //         \Log::error('Error fetching genre names, popular or upcoming content: ' . $e->getMessage());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to fetch genre names, popular or upcoming content.'
    //         ], 500);
    //     }
    // }

    // ---------------------------------
    public function Home(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 10);

            // Fetch genre names
            $genreNames = Genre::pluck('name');

            // Cover content
            $coverEntry = Cover::with('content.genres')->first();
            $coverContent = collect();

            if ($coverEntry && $coverEntry->content) {
                $coverContent->push($this->transformContent($coverEntry->content));
            }

            // Popular contents
            $popularContents = Content::with('genres:id,name')
                ->where('publish', 'public')
                ->whereNull('schedule')
                ->select('id', 'title', 'description', 'director_name', 'profile_pic', 'image', 'video1', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
                ->orderByDesc('view_count')
                ->paginate($perPage);

            $popularData = $popularContents->getCollection()->map(fn($content) => $this->transformContent($content));

            // Upcoming contents
            $upcomingContents = Content::with('genres:id,name')
                ->where('publish', 'public')
                ->whereNotNull('schedule')  
                ->where('schedule', '>', Carbon::now())
                ->select('id', 'title', 'description', 'director_name', 'profile_pic', 'image', 'video1', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
                ->orderBy('schedule', 'asc')
                ->paginate($perPage);

            $upcomingData = $upcomingContents->getCollection()->map(fn($content) => $this->transformContent($content));

            // Weekly top contents
            $weeklyTopContents = Content::with('genres:id,name')
                ->where('publish', 'public')
                ->whereNull('schedule')
                ->whereBetween('created_at', [Carbon::now()->subDays(7), Carbon::now()])
                ->orderByDesc('view_count')
                ->select('id', 'title', 'description', 'director_name', 'profile_pic', 'image', 'video1', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
                ->take(10)
                ->get()
                ->map(fn($content) => $this->transformContent($content));

            // Base response data
            $data = [
                'genre_names' => $genreNames,
                'popular' => $popularData,
                'upcoming' => $upcomingData,
                'weekly_top' => $weeklyTopContents,
                'latest' => $coverContent,
            ];

            // Genre-wise content as top-level keys
            foreach ($genreNames as $genreName) {
                $key = strtolower($genreName);
                $genreData = $this->getContentByGenre($genreName, $perPage);

                // Optional: skip if genre has no content
                // if ($genreData->isNotEmpty()) {
                $data[$key] = $genreData;
                // }
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            \Log::error('Home error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load content.'
            ], 500);
        }
    }

    private function transformContent($content, $isUpcoming = false)
    {
        return [
            'id' => $content->id,
            'title' => $content->title,
            'description' => $content->description,
            'image' => $content->image,
            'video1' => $content->video1,  // Or use: json_decode($content->video1)
            'publish' => $isUpcoming ? $content->schedule : $content->publish,
            'schedule' => $content->schedule,
            'view_count' => $content->view_count,
            'created_at' => $content->created_at,
            'genre_name' => optional($content->genres)->name,
            'director_name' => $content->director_name,
            'profile_pic' => $content->profile_pic,
        ];
    }

    private function getContentByGenre($genreName, $perPage)
    {
        $paginated = Content::with('genres:id,name')
            ->whereHas('genres', fn($q) => $q->where('name', $genreName))
            ->where('publish', 'public')
            ->whereNull('schedule')
            ->select('id', 'title', 'description', 'director_name', 'profile_pic', 'image', 'video1', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
            ->latest()
            ->paginate($perPage);

        return $paginated->getCollection()->map(fn($content) => $this->transformContent($content));
    }

    // -----------------------------

    // public function Home(Request $request)
    // {
    //     try {
    //         // Get pagination size from request or default to 10
    //         $perPage = $request->get('per_page', 10);

    //         // Get genre names dynamically from the database
    //         $genreNames = Genre::pluck('name');

    //         // Fetch cover content (if any)
    //         $coverEntry = Cover::with('content.genres')->first();
    //         $coverContent = collect();  // Initialize as empty collection

    //         if ($coverEntry && $coverEntry->content) {
    //             $coverContent->push([
    //                 'id' => $coverEntry->content->id,
    //                 'title' => $coverEntry->content->title,
    //                 'description' => $coverEntry->content->description,
    //                 'director_name' => $coverEntry->content->director_name,
    //                 'profile_pic' => $coverEntry->content->profile_pic,
    //                 'image' => $coverEntry->content->image,
    //                 'video1' => $coverEntry->content->video1,
    //                 'publish' => $coverEntry->content->publish,
    //                 'schedule' => $coverEntry->content->schedule,
    //                 'view_count' => $coverEntry->content->view_count,
    //                 'created_at' => $coverEntry->content->created_at,
    //                 'genre_name' => optional($coverEntry->content->genres)->name,
    //             ]);
    //         }

    //         // Fetch popular content (most viewed)
    //         $popularContents = Content::with('genres:id,name')
    //             ->select('id', 'title', 'video1', 'director_name', 'profile_pic', 'description', 'image', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
    //             ->orderByDesc('view_count')
    //             ->paginate($perPage);

    //         $popularContents->getCollection()->transform(function ($content) {
    //             return [
    //                 'id' => $content->id,
    //                 'title' => $content->title,
    //                 'description' => $content->description,
    //                 'image' => $content->image,
    //                 'video1' => $content->video1,
    //                 'publish' => $content->publish,
    //                 'schedule' => $content->schedule,
    //                 'view_count' => $content->view_count,
    //                 'created_at' => $content->created_at,
    //                 'genre_name' => optional($content->genres)->name,
    //                 'director_name' => $content->director_name,
    //                 'profile_pic' => $content->profile_pic,
    //             ];
    //         });

    //         // Fetch upcoming content (future scheduled content)
    //         $upcomingContents = Content::with('genres:id,name')
    //             ->where('schedule', '>', Carbon::now())
    //             ->select('id', 'title', 'description', 'director_name', 'profile_pic', 'video1', 'image', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
    //             ->orderBy('schedule', 'asc')
    //             ->paginate($perPage);

    //         $upcomingContents->getCollection()->transform(function ($content) {
    //             return [
    //                 'id' => $content->id,
    //                 'title' => $content->title,
    //                 'description' => $content->description,
    //                 'image' => $content->image,
    //                 'video1' => $content->video1,
    //                 'publish' => $content->publish,
    //                 'schedule' => $content->schedule,
    //                 'view_count' => $content->view_count,
    //                 'created_at' => $content->created_at,
    //                 'genre_name' => optional($content->genres)->name,
    //                 'director_name' => $content->director_name,
    //                 'profile_pic' => $content->profile_pic,
    //             ];
    //         });

    //         // Dynamically fetch content by genres
    //         $categories = [];
    //         foreach ($genreNames as $genreName) {
    //             $categories[strtolower($genreName)] = $this->getContentByGenre($genreName, $perPage);
    //         }

    //         // Fetch weekly top content (top 10 most viewed in last 7 days)
    //         $weeklyTopContents = Content::with('genres:id,name')
    //             ->whereBetween('created_at', [Carbon::now()->subDays(7), Carbon::now()])
    //             ->orderByDesc('view_count')
    //             ->select('id', 'title', 'description', 'director_name', 'profile_pic', 'video1', 'image', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
    //             ->take(10)
    //             ->get()
    //             ->transform(function ($content) {
    //                 return [
    //                     'id' => $content->id,
    //                     'title' => $content->title,
    //                     'description' => $content->description,
    //                     'image' => $content->image,
    //                     'video1' => $content->video1,
    //                     'publish' => $content->publish,
    //                     'schedule' => $content->schedule,
    //                     'view_count' => $content->view_count,
    //                     'created_at' => $content->created_at,
    //                     'genre_name' => optional($content->genres)->name,
    //                     'director_name' => $content->director_name,
    //                     'profile_pic' => $content->profile_pic,
    //                 ];
    //             });

    //         return response()->json([
    //             'success' => true,
    //             'data' => [
    //                 'genre_names' => $genreNames,
    //                 'popular' => $popularContents,
    //                 'upcoming' => $upcomingContents,
    //                 'categories' => $categories,  // All genre categories with their content
    //                 'weekly_top' => $weeklyTopContents,
    //                 'latest' => $coverContent,
    //             ]
    //         ]);
    //     } catch (\Exception $e) {
    //         \Log::error('Error fetching genre names, popular or upcoming content: ' . $e->getMessage());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to fetch genre names, popular or upcoming content.'
    //         ], 500);
    //     }
    // }

    // // Helper function to get content by genre
    // private function getContentByGenre($genreName, $perPage)
    // {
    //     return Content::with('genres:id,name')
    //         ->whereHas('genres', function ($query) use ($genreName) {
    //             $query->where('name', $genreName);
    //         })
    //         ->select('id', 'title', 'description', 'director_name', 'profile_pic', 'image', 'video1', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
    //         ->latest()
    //         ->paginate($perPage)
    //         ->getCollection()
    //         ->transform(function ($content) {
    //             return [
    //                 'id' => $content->id,
    //                 'title' => $content->title,
    //                 'description' => $content->description,
    //                 'image' => $content->image,
    //                 'video1' => $content->video1,
    //                 'publish' => $content->publish,
    //                 'schedule' => $content->schedule,
    //                 'view_count' => $content->view_count,
    //                 'created_at' => $content->created_at,
    //                 'genre_name' => optional($content->genres)->name,
    //                 'director_name' => $content->director_name,
    //                 'profile_pic' => $content->profile_pic,
    //             ];
    //         });
    // }

    // public function Home(Request $request)
    // {
    //     try {
    //         // Get pagination size from request or default to 10
    //         $perPage = $request->get('per_page', 10);
    //         $filterGenre = $request->get('genre');

    //         // Get genre names
    //         $genreNames = Genre::pluck('name');

    //         // Latest 1 content
    //         $latestContentQuery = Content::with('genres:id,name')
    //             ->select('id', 'title', 'description', 'image', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
    //             ->latest()
    //             ->take(1);

    //         if ($filterGenre) {
    //             $latestContentQuery->whereHas('genres', function ($query) use ($filterGenre) {
    //                 $query->where('name', $filterGenre);
    //             });
    //         }

    //         $latestContent = $latestContentQuery->get()->transform(function ($content) {
    //             return [
    //                 'id' => $content->id,
    //                 'title' => $content->title,
    //                 'description' => $content->description,
    //                 'image' => $content->image,
    //                 'publish' => $content->publish,
    //                 'schedule' => $content->schedule,
    //                 'view_count' => $content->view_count,
    //                 'created_at' => $content->created_at,
    //                 'genre_name' => optional($content->genres)->name,
    //             ];
    //         });

    //         // Popular content
    //         $popularContentsQuery = Content::with('genres:id,name')
    //             ->select('id', 'title', 'description', 'image', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
    //             ->orderByDesc('view_count');

    //         if ($filterGenre) {
    //             $popularContentsQuery->whereHas('genres', function ($query) use ($filterGenre) {
    //                 $query->where('name', $filterGenre);
    //             });
    //         }

    //         $popularContents = $popularContentsQuery->paginate($perPage);
    //         $popularContents->getCollection()->transform(function ($content) {
    //             return [
    //                 'id' => $content->id,
    //                 'title' => $content->title,
    //                 'description' => $content->description,
    //                 'image' => $content->image,
    //                 'publish' => $content->publish,
    //                 'schedule' => $content->schedule,
    //                 'view_count' => $content->view_count,
    //                 'created_at' => $content->created_at,
    //                 'genre_name' => optional($content->genres)->name,
    //             ];
    //         });

    //         // Upcoming content
    //         $upcomingContentsQuery = Content::with('genres:id,name')
    //             ->where('schedule', '>', Carbon::now())
    //             ->select('id', 'title', 'description', 'image', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
    //             ->orderBy('schedule', 'asc');

    //         if ($filterGenre) {
    //             $upcomingContentsQuery->whereHas('genres', function ($query) use ($filterGenre) {
    //                 $query->where('name', $filterGenre);
    //             });
    //         }

    //         $upcomingContents = $upcomingContentsQuery->paginate($perPage);
    //         $upcomingContents->getCollection()->transform(function ($content) {
    //             return [
    //                 'id' => $content->id,
    //                 'title' => $content->title,
    //                 'description' => $content->description,
    //                 'image' => $content->image,
    //                 'publish' => $content->publish,
    //                 'schedule' => $content->schedule,
    //                 'view_count' => $content->view_count,
    //                 'created_at' => $content->created_at,
    //                 'genre_name' => optional($content->genres)->name,
    //             ];
    //         });

    //         // Comedy content
    //         $comedyContents = Content::with('genres:id,name')
    //             ->whereHas('genres', function ($query) {
    //                 $query->where('name', 'Comedy');
    //             })
    //             ->select('id', 'title', 'description', 'image', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
    //             ->latest()
    //             ->paginate($perPage);

    //         $comedyContents->getCollection()->transform(function ($content) {
    //             return [
    //                 'id' => $content->id,
    //                 'title' => $content->title,
    //                 'description' => $content->description,
    //                 'image' => $content->image,
    //                 'publish' => $content->publish,
    //                 'schedule' => $content->schedule,
    //                 'view_count' => $content->view_count,
    //                 'created_at' => $content->created_at,
    //                 'genre_name' => optional($content->genres)->name,
    //             ];
    //         });

    //         // Family content
    //         $familyContents = Content::with('genres:id,name')
    //             ->whereHas('genres', function ($query) {
    //                 $query->where('name', 'Family');
    //             })
    //             ->select('id', 'title', 'description', 'image', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
    //             ->latest()
    //             ->paginate($perPage);

    //         $familyContents->getCollection()->transform(function ($content) {
    //             return [
    //                 'id' => $content->id,
    //                 'title' => $content->title,
    //                 'description' => $content->description,
    //                 'image' => $content->image,
    //                 'publish' => $content->publish,
    //                 'schedule' => $content->schedule,
    //                 'view_count' => $content->view_count,
    //                 'created_at' => $content->created_at,
    //                 'genre_name' => optional($content->genres)->name,
    //             ];
    //         });

    //         // Dramas content
    //         $dramasContents = Content::with('genres:id,name')
    //             ->whereHas('genres', function ($query) {
    //                 $query->where('name', 'Dramas');
    //             })
    //             ->select('id', 'title', 'description', 'image', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
    //             ->latest()
    //             ->paginate($perPage);

    //         $dramasContents->getCollection()->transform(function ($content) {
    //             return [
    //                 'id' => $content->id,
    //                 'title' => $content->title,
    //                 'description' => $content->description,
    //                 'image' => $content->image,
    //                 'publish' => $content->publish,
    //                 'schedule' => $content->schedule,
    //                 'view_count' => $content->view_count,
    //                 'created_at' => $content->created_at,
    //                 'genre_name' => optional($content->genres)->name,
    //             ];
    //         });

    //         // Tv Shows content
    //         $tvshows = Content::with('genres:id,name')
    //             ->whereHas('genres', function ($query) {
    //                 $query->where('name', 'tv shows');
    //             })
    //             ->select('id', 'title', 'description', 'image', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at')
    //             ->latest()
    //             ->paginate($perPage);

    //         $tvshows->getCollection()->transform(function ($content) {
    //             return [
    //                 'id' => $content->id,
    //                 'title' => $content->title,
    //                 'description' => $content->description,
    //                 'image' => $content->image,
    //                 'publish' => $content->publish,
    //                 'schedule' => $content->schedule,
    //                 'view_count' => $content->view_count,
    //                 'created_at' => $content->created_at,
    //                 'genre_name' => optional($content->genres)->name,
    //             ];
    //         });

    //         // Weekly Top Content
    //         $weeklyTopQuery = Content::with('genres:id,name')
    //             ->whereBetween('created_at', [Carbon::now()->subDays(7), Carbon::now()])
    //             ->orderByDesc('view_count')
    //             ->select('id', 'title', 'description', 'image', 'publish', 'schedule', 'view_count', 'genre_id', 'created_at');

    //         if ($filterGenre) {
    //             $weeklyTopQuery->whereHas('genres', function ($query) use ($filterGenre) {
    //                 $query->where('name', $filterGenre);
    //             });
    //         }

    //         $weeklyTopContents = $weeklyTopQuery->take(10)->get()->transform(function ($content) {
    //             return [
    //                 'id' => $content->id,
    //                 'title' => $content->title,
    //                 'description' => $content->description,
    //                 'image' => $content->image,
    //                 'publish' => $content->publish,
    //                 'schedule' => $content->schedule,
    //                 'view_count' => $content->view_count,
    //                 'created_at' => $content->created_at,
    //                 'genre_name' => optional($content->genres)->name,
    //             ];
    //         });

    //         return response()->json([
    //             'success' => true,
    //             'data' => [
    //                 'genre_names' => $genreNames,
    //                 'popular' => $popularContents,
    //                 'upcoming' => $upcomingContents,
    //                 'comedy' => $comedyContents,
    //                 'family' => $familyContents,
    //                 'dramas' => $dramasContents,
    //                 'tv_shows' => $tvshows,
    //                 'weekly_top' => $weeklyTopContents,
    //                 'latest' => $latestContent,
    //             ]
    //         ]);
    //     } catch (\Exception $e) {
    //         \Log::error('Error fetching content: ' . $e->getMessage());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to fetch content.'
    //         ], 500);
    //     }
    // }

    public function index()
    {
        $genres = Genre::all();  // gets all columns and all records

        return response()->json($genres);
    }

    public function showsAllGenres()
    {
        $genres = Genre::all()->map(function ($genre) {
            return [
                'id' => $genre->id,
                'name' => $genre->name,
                'thumbnail' => $genre->thumbnail,
                'date' => $genre->created_at->format('Y-m-d'),
                'content' => $genre->content,
            ];
        });

        return response()->json($genres);
    }

    public function showsAllContents()
    {
        try {
            $contents = Content::all()->map(function ($content) {
                return [
                    'content_name' => $content->title,
                    'video1' => $content->video1,
                    'description' => $content->description,
                    'publish' => $content->publish,
                    'schedule' => $content->schedule,
                    'genre_id' => $content->genre_id,
                    'image' => $content->image,
                    'created_at' => $content->created_at?->format('Y-m-d'),
                    'updated_at' => $content->updated_at?->format('Y-m-d'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Contents fetched successfully.',
                'data' => $contents,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch contents.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $genre = Genre::findOrFail($id);
            return response()->json(['success' => true, 'data' => $genre]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Genre not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'thumbnail' => 'nullable|image',
            ]);

            if ($request->hasFile('thumbnail')) {
                $file = $request->file('thumbnail');
                $imageName = time() . '_genre_thumbnail.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/Genres'), $imageName);
                $validated['thumbnail'] = 'uploads/Genres/' . $imageName;
            }

            $genre = Genre::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Genre created successfully',
                'data' => $genre
            ], 201);
        } catch (ValidationException $e) {
            // Handle validation errors separately
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Handle other exceptions
            return response()->json([
                'success' => false,
                'message' => 'Failed to create genre',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'thumbnail' => 'nullable|image',
            ]);

            $genre = Genre::findOrFail($id);

            // Handle new thumbnail upload if present
            if ($request->hasFile('thumbnail')) {
                $file = $request->file('thumbnail');
                $imageName = time() . '_genre_thumbnail.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/Genres'), $imageName);

                // Optionally delete old file
                if ($genre->thumbnail && file_exists(public_path($genre->thumbnail))) {
                    @unlink(public_path($genre->thumbnail));
                }

                $validated['thumbnail'] = 'uploads/Genres/' . $imageName;
            }

            $genre->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Genre updated successfully',
                'data' => $genre
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update genre',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $genre = Genre::findOrFail($id);
            $genre->delete();

            return response()->json(['success' => true, 'message' => 'Genre deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete genre', 'error' => $e->getMessage()], 500);
        }
    }
}
