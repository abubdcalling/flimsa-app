<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return 'hyi';
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $awsPath = 'https://flimsabucket.s3.us-east-2.amazonaws.com/';
        $path = $request->file('file')->store('public/files');
        
        return response()->json([
            'path' => $awsPath.$path,
            'msg' =>'success'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(File $file)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(File $file)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file',
        ]);

        $file = File::findOrFail($id);

        // Delete the old file if it exists
        if ($file->path && file_exists(public_path($file->path))) {
            unlink(public_path($file->path));
        }

        // Store the new file manually
        $uploadedFile = $request->file('file');
        $destinationPath = 'files';
        $fileName = uniqid() . '_' . $uploadedFile->getClientOriginalName();
        $uploadedFile->move(public_path($destinationPath), $fileName);

        $path = $destinationPath . '/' . $fileName;
        $awsPath = 'https://flimsabucket.s3.us-east-2.amazonaws.com/';
        $file->path = $path;
        $file->save();

        return response()->json([
            'path' => $awsPath . $path,
            'msg' => 'File updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(File $file)
    {
        //
    }
}
