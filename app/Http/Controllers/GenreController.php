<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GenreController extends Controller
{
    public function index()
    {
        $genres = Genre::all();

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
            $genre = Genre::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'thumbnail' => 'nullable|url',
            ]);

            $genre->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Genre updated successfully',
                'data' => $genre,
            ]);
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
