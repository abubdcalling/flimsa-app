<?php

namespace App\Http\Controllers;

use App\Models\Serie;  // Singular model name

use Illuminate\Http\Request;

class SerieController extends Controller
{
    // Show all series
    public function index()
    {
        $series = Serie::all();   // Use Serie here
        return view('series.index', compact('series'));
    }

    // Show create form
    public function create()
    {
        return view('series.create');
    }

    // Store new series
    public function store(Request $request)
    {
        $this->validateSeries($request);

        Serie::create($request->all());  // Use Serie here

        return redirect()->route('series.index')->with('success', 'Series created successfully.');
    }

    // Show a single series by $id
    public function show($id)
    {
        $series = Serie::findOrFail($id);   // Use Serie here
        return view('series.show', compact('series'));
    }

    // Show edit form by $id
    public function edit($id)
    {
        $series = Serie::findOrFail($id);  // Use Serie here
        return view('series.edit', compact('series'));
    }

    // Update a series by $id
    public function update(Request $request, $id)
    {
        $this->validateSeries($request);

        $series = Serie::findOrFail($id);  // Use Serie here
        $series->update($request->all());

        return redirect()->route('series.index')->with('success', 'Series updated successfully.');
    }

    // Delete a series by $id
    public function destroy($id)
    {
        $series = Serie::findOrFail($id);  // Use Serie here
        $series->delete();

        return redirect()->route('series.index')->with('success', 'Series deleted successfully.');
    }

    // Validation rules in a separate method
    protected function validateSeries(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'release_date' => 'nullable|date',
        ]);
    }
}
