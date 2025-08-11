<?php 

// app/Http/Controllers/PersonController.php
namespace App\Http\Controllers;

use App\Models\Person;
use Illuminate\Http\Request;

class PersonController extends Controller
{
    // List all people with search + latest first
    public function index(Request $request)
    {
        try {
            $search = $request->query('search');

            $query = Person::orderBy('created_at', 'desc');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('stateOrCountry', 'like', "%{$search}%")
                      ->orWhere('gender', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('shows', 'like', "%{$search}%");
                });
            }

            return response()->json([
                'status' => 'success',
                'data' => $query->paginate(10)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Store new record
    public function store(Request $request)
    {
        try {
            $person = Person::create($request->only([
                'name', 'stateOrCountry', 'gender', 'email', 'shows'
            ]));

            return response()->json([
                'status' => 'success',
                'data' => $person
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Show specific record
    public function show($id)
    {
        try {
            $person = Person::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $person
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Record not found'
            ], 404);
        }
    }

    // Update specific record
    public function update(Request $request, $id)
    {
        try {
            $person = Person::findOrFail($id);
            $person->update($request->only([
                'name', 'stateOrCountry', 'gender', 'email', 'shows'
            ]));

            return response()->json([
                'status' => 'success',
                'data' => $person
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Delete specific record
    public function destroy($id)
    {
        try {
            $person = Person::findOrFail($id);
            $person->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Record deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Record not found or could not be deleted'
            ], 404);
        }
    }
}
