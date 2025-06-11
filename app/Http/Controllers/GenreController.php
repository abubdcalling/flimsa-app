<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GenreController extends Controller
{

    public function index()
    {
        try {
            $genres = Genre::withCount('contents')->get();

            return response()->json([
                'success' => true,
                'data' => $genres,
                'message' => 'Genres retrieved successfully',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching genres: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again later.',
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
                'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
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
