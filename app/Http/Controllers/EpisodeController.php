<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEpisodeWithContentRequest;
use App\Models\Content;
use App\Models\Episode;
use App\Models\Season;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;



class EpisodeController extends Controller
{

    public function index(Request $request)
{
    // 1) Validate query params
    $v = Validator::make($request->all(), [
        'page' => ['nullable', 'integer', 'min:1'],

        // allow either an integer (1..100) or the literal string "all"
        'per_page' => ['nullable', 'regex:/^(all|[1-9]\d{0,2})$/'],

        'series_id' => ['nullable', 'integer', 'exists:series,id'],
        'season_id' => ['nullable', 'integer', 'exists:seasons,id'],
        'status' => ['nullable', 'in:draft,scheduled,published,archived'],
        'publish' => ['nullable', 'in:public,private,schedule'],  // Content.publish
        'search' => ['nullable', 'string', 'max:255'],            // title/slug/synopsis, content.title/description
        // Optional schedule window (filters content.schedule)
        'scheduled_from' => ['nullable', 'date'],
        'scheduled_to' => ['nullable', 'date'],
        // Sorting (episode columns only to avoid joins)
        'order_by' => ['nullable', 'in:created_at,updated_at,release_date,episode_number,title'],
        'order_dir' => ['nullable', 'in:asc,desc'],
    ]);

    if ($v->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $v->errors(),
        ], \Illuminate\Http\Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    $data = $v->validated();

    // 2) If both series_id and season_id provided, ensure season belongs to series
    if (!empty($data['series_id']) && !empty($data['season_id'])) {
        $season = Season::find($data['season_id']);
        if ($season && (int) $season->series_id !== (int) $data['series_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Selected season does not belong to the given series.',
            ], \Illuminate\Http\Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    // 3) Build query (+ eager load)
    $q = Episode::query()->with(['season', 'series', 'content']);

    // Filters
    if (!empty($data['series_id'])) {
        $q->where('series_id', (int) $data['series_id']);
    }
    if (!empty($data['season_id'])) {
        $q->where('season_id', (int) $data['season_id']);
    }
    if (!empty($data['status'])) {
        $q->where('status', $data['status']);
    }
    if (!empty($data['publish'])) {
        $q->whereHas('content', fn($c) => $c->where('publish', $data['publish']));
    }

    // Scheduled window (on related content.schedule)
    if (!empty($data['scheduled_from']) || !empty($data['scheduled_to'])) {
        $from = $data['scheduled_from'] ?? null;
        $to = $data['scheduled_to'] ?? null;

        $q->whereHas('content', function ($c) use ($from, $to) {
            if ($from) {
                $c->where('schedule', '>=', $from);
            }
            if ($to) {
                $c->where('schedule', '<=', $to);
            }
        });
    }

    // Search (episode + content fields)
    if (!empty($data['search'])) {
        $term = trim($data['search']);
        $q->where(function ($qq) use ($term) {
            $qq->where('title', 'like', "%{$term}%")
               ->orWhere('slug', 'like', "%{$term}%")
               ->orWhere('synopsis', 'like', "%{$term}%")
               ->orWhereHas('content', function ($c) use ($term) {
                   $c->where('title', 'like', "%{$term}%")
                     ->orWhere('description', 'like', "%{$term}%");
               });
        });
    }

    // Sorting (defaults already give "latest" by created_at desc)
    $orderBy = $data['order_by'] ?? 'created_at';
    $orderDir = $data['order_dir'] ?? 'desc';
    $q->orderBy($orderBy, $orderDir);

    // Pagination (Series-style per_page handling; preserves your response)
    $perPageRaw = $request->query('per_page', 15);
    if (is_numeric($perPageRaw)) {
        $perPage = max(1, min((int) $perPageRaw, 100));
    } elseif (is_string($perPageRaw) && strtolower($perPageRaw) === 'all') {
        $perPage = max(1, Episode::count()); // single page with all items
    } else {
        $perPage = 15;
    }

    $episodes = $q->paginate($perPage)->appends($request->query());

    // 4) Hide schedule unless actually scheduled
    $episodes->getCollection()->each(function ($ep) {
        if ($ep->relationLoaded('content') && $ep->content && $ep->content->publish !== 'schedule') {
            $ep->content->makeHidden(['schedule']);
        }
    });

    // 5) Response (unchanged)
    return response()->json([
        'success' => true,
        'message' => 'Episodes list.',
        'data' => $episodes->items(),
        'meta' => [
            'current_page' => $episodes->currentPage(),
            'per_page' => $episodes->perPage(),
            'last_page' => $episodes->lastPage(),
            'total' => $episodes->total(),
        ],
    ], \Illuminate\Http\Response::HTTP_OK);
}






    // public function index(Request $request)
    // {
    //     // 1) Validate query params
    //     $v = Validator::make($request->all(), [
    //         'page' => ['nullable', 'integer', 'min:1'],
    //         'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
    //         'series_id' => ['nullable', 'integer', 'exists:series,id'],
    //         'season_id' => ['nullable', 'integer', 'exists:seasons,id'],
    //         'status' => ['nullable', 'in:draft,scheduled,published,archived'],
    //         'publish' => ['nullable', 'in:public,private,schedule'],  // Content.publish
    //         'search' => ['nullable', 'string', 'max:255'],  // title/slug/synopsis, content.title/description
    //         // Optional schedule window (filters content.schedule)
    //         'scheduled_from' => ['nullable', 'date'],
    //         'scheduled_to' => ['nullable', 'date'],
    //         // Sorting (episode columns only to avoid joins)
    //         'order_by' => ['nullable', 'in:created_at,updated_at,release_date,episode_number,title'],
    //         'order_dir' => ['nullable', 'in:asc,desc'],
    //     ]);

    //     if ($v->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation failed.',
    //             'errors' => $v->errors(),
    //         ], \Illuminate\Http\Response::HTTP_UNPROCESSABLE_ENTITY);
    //     }

    //     $data = $v->validated();

    //     // 2) If both series_id and season_id provided, ensure season belongs to series
    //     if (!empty($data['series_id']) && !empty($data['season_id'])) {
    //         $season = Season::find($data['season_id']);
    //         if ($season && (int) $season->series_id !== (int) $data['series_id']) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Selected season does not belong to the given series.',
    //             ], \Illuminate\Http\Response::HTTP_UNPROCESSABLE_ENTITY);
    //         }
    //     }

    //     // 3) Build query (+ eager load)
    //     $q = Episode::query()
    //         ->with(['season', 'series', 'content']);

    //     // Filters
    //     if (!empty($data['series_id'])) {
    //         $q->where('series_id', (int) $data['series_id']);
    //     }
    //     if (!empty($data['season_id'])) {
    //         $q->where('season_id', (int) $data['season_id']);
    //     }
    //     if (!empty($data['status'])) {
    //         $q->where('status', $data['status']);
    //     }
    //     if (!empty($data['publish'])) {
    //         $q->whereHas('content', fn($c) => $c->where('publish', $data['publish']));
    //     }

    //     // Scheduled window (on related content.schedule)
    //     if (!empty($data['scheduled_from']) || !empty($data['scheduled_to'])) {
    //         $from = $data['scheduled_from'] ?? null;
    //         $to = $data['scheduled_to'] ?? null;

    //         $q->whereHas('content', function ($c) use ($from, $to) {
    //             if ($from) {
    //                 $c->where('schedule', '>=', $from);
    //             }
    //             if ($to) {
    //                 $c->where('schedule', '<=', $to);
    //             }
    //         });
    //     }

    //     // Search (episode + content fields)
    //     if (!empty($data['search'])) {
    //         $term = trim($data['search']);
    //         $q->where(function ($qq) use ($term) {
    //             $qq
    //                 ->where('title', 'like', "%{$term}%")
    //                 ->orWhere('slug', 'like', "%{$term}%")
    //                 ->orWhere('synopsis', 'like', "%{$term}%")
    //                 ->orWhereHas('content', function ($c) use ($term) {
    //                     $c
    //                         ->where('title', 'like', "%{$term}%")
    //                         ->orWhere('description', 'like', "%{$term}%");
    //                 });
    //         });
    //     }

    //     // Sorting
    //     $orderBy = $data['order_by'] ?? 'created_at';
    //     $orderDir = $data['order_dir'] ?? 'desc';
    //     $q->orderBy($orderBy, $orderDir);

    //     // Pagination
    //     $perPage = (int) ($data['per_page'] ?? 15);
    //     $episodes = $q->paginate($perPage)->appends($request->query());

    //     // 4) Hide schedule unless actually scheduled
    //     $episodes->getCollection()->each(function ($ep) {
    //         if ($ep->relationLoaded('content') && $ep->content && $ep->content->publish !== 'schedule') {
    //             $ep->content->makeHidden(['schedule']);
    //         }
    //     });

    //     // 5) Response
    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Episodes list.',
    //         'data' => $episodes->items(),  // models will serialize with loaded relations
    //         'meta' => [
    //             'current_page' => $episodes->currentPage(),
    //             'per_page' => $episodes->perPage(),
    //             'last_page' => $episodes->lastPage(),
    //             'total' => $episodes->total(),
    //         ],
    //     ], \Illuminate\Http\Response::HTTP_OK);
    // }

    public function show(Request $request, Episode $episode)
    {
        try {
            // Eager-load relations
            $episode->load(['season', 'series', 'content']);

            // Hide schedule unless publish === 'schedule'
            if ($episode->relationLoaded('content') && $episode->content) {
                if ($episode->content->publish !== 'schedule' || empty($episode->content->schedule)) {
                    $episode->content->makeHidden(['schedule']);
                } else {
                    // Optional local-time echo for clients: ?tz=America/Los_Angeles
                    if ($tz = $request->query('tz')) {
                        try {
                            $episode->content->schedule_local = optional($episode->content->schedule)
                                ->timezone($tz)
                                ->toDateTimeString();
                        } catch (\Throwable $t) {
                            // Silently ignore bad tz values; you can log if you want
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Episode details.',
                'data' => $episode,
            ], \Illuminate\Http\Response::HTTP_OK);
        } catch (\Throwable $e) {
            \Log::error('Error fetching episode: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'episode_id' => $episode->id ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => app()->environment('local') ? $e->getMessage() : 'Failed to fetch episode.',
            ], \Illuminate\Http\Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // app/Http/Controllers/EpisodeController.php

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            // Episode
            'series_id' => ['required', 'exists:series,id'],
            'season_id' => ['required', 'exists:seasons,id'],
            'episode_number' => [
                'required', 'integer', 'min:1',
                Rule::unique('episodes', 'episode_number')
                    ->where(fn($q) => $q->where('season_id', $request->input('season_id')))
            ],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('episodes', 'slug')
                    ->where(fn($q) => $q->where('season_id', $request->input('season_id')))
            ],
            'synopsis' => ['nullable', 'string'],
            'status' => ['nullable', 'in:draft,scheduled,published,archived'],
            'release_date' => ['nullable', 'date'],
            'runtime_minutes' => ['nullable', 'integer', 'min:1'],
            // Content
            'description' => ['nullable', 'string'],
            'publish' => ['nullable', Rule::in(['public', 'private', 'schedule'])],
            'schedule_date' => ['nullable', 'date'],
            'schedule_time' => ['nullable', 'date_format:H:i'],
            'schedule_tz' => ['nullable', 'timezone'],
            'genre_id' => ['nullable', 'exists:genres,id'],
            'director_name' => ['nullable', 'string', 'max:255'],
            'duration' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            // Files
            'video1' => ['required', 'json'],
            'profile_pic' => [$request->hasFile('profile_pic') ? 'image|max:5120' : 'nullable'],
            'image' => [$request->hasFile('image') ? 'image|max:5120' : 'nullable'],
        ], [
            'episode_number.unique' => 'This season already has that episode number.',
            'slug.unique' => 'Slug must be unique within the selected season.',
        ]);

        if ($v->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $v->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $v->validated();

        // Season must belong to series
        $season = Season::findOrFail($data['season_id']);
        if ((int) $season->series_id !== (int) $data['series_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Selected season does not belong to the given series.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Store files (outside tx)
        $videoPath = $request->input('video1');
        $profilePath = $request->file('profile_pic')?->store('images', 'public');
        $imagePath = $request->file('image')?->store('images', 'public');

        try {
            // Compose schedule (UTC)
            $schedule = null;
            if (($data['publish'] ?? null) === 'schedule' &&
                    !empty($data['schedule_date']) &&
                    !empty($data['schedule_time'])) {
                $tz = $data['schedule_tz'] ?? 'UTC';
                $schedule = Carbon::parse($data['schedule_date'] . ' ' . $data['schedule_time'], $tz)->utc();
            }

            $episode = DB::transaction(function () use ($data, $schedule, $videoPath, $profilePath, $imagePath) {
                $content = Content::create([
                    'title' => $data['title'],
                    'description' => $data['description'] ?? '',
                    'video1' => $videoPath,
                    'publish' => $data['publish'] ?? 'private',
                    'schedule' => $schedule,  // ensure column exists (or rename key)
                    'genre_id' => $data['genre_id'] ?? null,
                    'director_name' => $data['director_name'] ?? null,
                    'duration' => !empty($data['runtime_minutes'])
                        ? gmdate('H:i:s', $data['runtime_minutes'] * 60)
                        : ($data['duration'] ?? null),
                    'profile_pic' => $profilePath,
                    'image' => $imagePath,
                    'type' => $data['type'] ?? 'episode',  // ensure in $fillable and column exists
                ]);

                $slug = $data['slug'] ?? Str::slug($data['title']);
                $slug = $this->uniqueEpisodeSlug($slug, (int) $data['season_id']);

                return Episode::create([
                    'series_id' => $data['series_id'],
                    'season_id' => $data['season_id'],
                    'episode_number' => $data['episode_number'],
                    'title' => $data['title'],
                    'slug' => $slug,
                    'synopsis' => $data['synopsis'] ?? null,
                    'status' => $data['status'] ?? 'draft',
                    'content_id' => $content->id,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Episode and Content created.',
                'data' => $episode->load(['season', 'series', 'content']),
            ], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            foreach (array_filter([$videoPath, $profilePath, $imagePath]) as $p) {
                try {
                    Storage::disk('public')->delete($p);
                } catch (\Throwable $t) {
                }
            }
            Log::error('Error creating episode+content: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => app()->environment('local') ? $e->getMessage() : 'Something went wrong while creating the episode and content.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Ensure slug is unique within a season by suffixing -2, -3, ...
     */
    protected function uniqueEpisodeSlug(string $baseSlug, int $seasonId): string
    {
        $slug = $baseSlug;
        $n = 2;
        while (Episode::where('season_id', $seasonId)->where('slug', $slug)->exists()) {
            $slug = Str::limit($baseSlug, 245, '') . '-' . $n;
            $n++;
        }
        return $slug;
    }

    public function update(Request $request, Episode $episode)
    {
        // 1) Validate (mirror of store, but with unique rules that ignore the current episode)
        $v = Validator::make($request->all(), [
            // Episode
            'series_id' => ['required', 'exists:series,id'],
            'season_id' => ['required', 'exists:seasons,id'],
            'episode_number' => [
                'required', 'integer', 'min:1',
                Rule::unique('episodes', 'episode_number')
                    ->where(fn($q) => $q->where('season_id', $request->input('season_id')))
                    ->ignore($episode->id)
            ],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('episodes', 'slug')
                    ->where(fn($q) => $q->where('season_id', $request->input('season_id')))
                    ->ignore($episode->id)
            ],
            'synopsis' => ['nullable', 'string'],
            'status' => ['nullable', 'in:draft,scheduled,published,archived'],
            'release_date' => ['nullable', 'date'],
            'runtime_minutes' => ['nullable', 'integer', 'min:1'],
            // Content
            'description' => ['nullable', 'string'],
            'publish' => ['nullable', Rule::in(['public', 'private', 'schedule'])],
            'schedule_date' => ['nullable', 'date'],
            'schedule_time' => ['nullable', 'date_format:H:i'],
            'schedule_tz' => ['nullable', 'timezone'],
            'genre_id' => ['nullable', 'exists:genres,id'],
            'director_name' => ['nullable', 'string', 'max:255'],
            'duration' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            // Files / payload
            'video1' => ['nullable', 'json'],  // not required on update
            'profile_pic' => [$request->hasFile('profile_pic') ? 'image|max:5120' : 'nullable'],
            'image' => [$request->hasFile('image') ? 'image|max:5120' : 'nullable'],
        ], [
            'episode_number.unique' => 'This season already has that episode number.',
            'slug.unique' => 'Slug must be unique within the selected season.',
        ]);

        if ($v->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $v->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $v->validated();

        // 2) Ensure the selected season belongs to the provided series
        $season = Season::findOrFail($data['season_id']);
        if ((int) $season->series_id !== (int) $data['series_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Selected season does not belong to the given series.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // 3) Pre-store any newly uploaded files (outside tx)
        $newProfilePath = $request->file('profile_pic')?->store('images', 'public');
        $newImagePath = $request->file('image')?->store('images', 'public');
        $newVideoJson = $request->input('video1');  // JSON string, optional

        try {
            // Compute schedule (keep current if still scheduled and no new fields provided)
            $currentContent = $episode->content;  // may be null in weird cases
            $effectivePublish = $data['publish'] ?? ($currentContent->publish ?? 'private');
            $schedule = $currentContent->schedule ?? null;

            if ($effectivePublish === 'schedule') {
                if (!empty($data['schedule_date']) && !empty($data['schedule_time'])) {
                    $tz = $data['schedule_tz'] ?? 'UTC';
                    $schedule = Carbon::parse($data['schedule_date'] . ' ' . $data['schedule_time'], $tz)->utc();
                }
                // else: leave existing schedule as-is
            } else {
                $schedule = null;  // switching away from schedule clears it
            }

            $pathsToDeleteAfterCommit = [];

            $updated = DB::transaction(function () use (
                $episode, $data, $newProfilePath, $newImagePath, $newVideoJson,
                $schedule, &$pathsToDeleteAfterCommit
            ) {
                // 4) Upsert/Update Content
                $content = $episode->content ?: Content::create([
                    'title' => $episode->title,
                    'description' => $episode->synopsis ?? '',
                    'publish' => 'private',
                    'type' => 'episode',
                ]);

                $contentUpdates = [
                    'title' => $data['title'],
                    'description' => $data['description'] ?? ($content->description ?? ''),
                    'video1' => $newVideoJson ?? $content->video1,
                    'publish' => $data['publish'] ?? $content->publish,
                    'schedule' => $schedule,
                    'genre_id' => $data['genre_id'] ?? null,
                    'director_name' => $data['director_name'] ?? null,
                    'duration' => !empty($data['runtime_minutes'])
                        ? gmdate('H:i:s', $data['runtime_minutes'] * 60)
                        : ($data['duration'] ?? $content->duration),
                    'type' => $data['type'] ?? ($content->type ?? 'episode'),
                ];

                if ($newProfilePath) {
                    if (!empty($content->profile_pic)) {
                        $pathsToDeleteAfterCommit[] = $content->profile_pic;
                    }
                    $contentUpdates['profile_pic'] = $newProfilePath;
                }

                if ($newImagePath) {
                    if (!empty($content->image)) {
                        $pathsToDeleteAfterCommit[] = $content->image;
                    }
                    $contentUpdates['image'] = $newImagePath;
                }

                $content->update($contentUpdates);

                // 5) Episode updates
                // Only regenerate slug if it changed or the season changed
                $finalSlug = $episode->slug;
                $seasonChanged = (int) $data['season_id'] !== (int) $episode->season_id;
                if (($data['slug'] ?? null) && $data['slug'] !== $episode->slug) {
                    $finalSlug = $data['slug'];
                }
                if ($seasonChanged || $finalSlug !== $episode->slug) {
                    // Ensure uniqueness in the (possibly new) season
                    $finalSlug = $this->uniqueEpisodeSlug($finalSlug, (int) $data['season_id']);
                }

                $episode->update([
                    'series_id' => $data['series_id'],
                    'season_id' => $data['season_id'],
                    'episode_number' => $data['episode_number'],
                    'title' => $data['title'],
                    'slug' => $finalSlug,
                    'synopsis' => $data['synopsis'] ?? $episode->synopsis,
                    'status' => $data['status'] ?? $episode->status,
                    'content_id' => $content->id,
                ]);

                return $episode->load(['season', 'series', 'content']);
            });

            // 6) After successful commit, delete old replaced files
            foreach ($pathsToDeleteAfterCommit as $p) {
                try {
                    Storage::disk('public')->delete($p);
                } catch (\Throwable $t) {
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Episode and Content updated.',
                'data' => $updated,
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            // Rollback-time cleanup of *newly* stored files (since we didn't switch pointers)
            foreach (array_filter([$newProfilePath, $newImagePath]) as $p) {
                try {
                    Storage::disk('public')->delete($p);
                } catch (\Throwable $t) {
                }
            }

            Log::error('Error updating episode+content: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => app()->environment('local')
                    ? $e->getMessage()
                    : 'Something went wrong while updating the episode and content.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Episode $episode)
    {
        // Collect any files we may need to delete AFTER a successful commit
        $pathsToDeleteAfterCommit = [];

        // Snapshot current content/files before we touch the DB
        $content = $episode->content;  // may be null

        if ($content) {
            // profile_pic & image are stored on the "public" disk in your store()
            foreach (['profile_pic', 'image'] as $key) {
                if (!empty($content->{$key})) {
                    $pathsToDeleteAfterCommit[] = $content->{$key};
                }
            }

            // Handle video1: could be JSON, a raw path, or a remote URL
            $video1 = $content->video1 ?? null;
            if (!empty($video1)) {
                $potentialPath = null;

                $decoded = json_decode($video1, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    // Look for common keys that might store a local path
                    foreach (['path', 'file', 'local_path', 'storage_path'] as $k) {
                        if (!empty($decoded[$k])) {
                            $potentialPath = $decoded[$k];
                            break;
                        }
                    }
                    // If JSON has only 'url', we treat it as remote and don't delete.
                } else {
                    // Not JSON: treat raw string as potential path
                    $potentialPath = $video1;
                }

                // Only delete if it looks like a local file (not an http/https URL)
                if ($potentialPath && !preg_match('~^https?://~i', $potentialPath)) {
                    $pathsToDeleteAfterCommit[] = $potentialPath;
                }
            }
        }

        try {
            DB::transaction(function () use ($episode) {
                // Delete Episode first to satisfy FK on episodes.content_id (if not cascading)
                $episode->delete();

                // Delete related Content if present (and not cascaded automatically)
                if ($episode->content) {
                    $episode->content->delete();
                }
            });

            // Files are deleted only after the transaction succeeds
            foreach ($pathsToDeleteAfterCommit as $p) {
                try {
                    Storage::disk('public')->delete($p);
                } catch (\Throwable $t) {
                    // swallow; log if you want
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Episode and related content deleted.',
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error('Error deleting episode+content: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'episode_id' => $episode->id ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => app()->environment('local')
                    ? $e->getMessage()
                    : 'Failed to delete the episode.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
