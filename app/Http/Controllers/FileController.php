<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Aws\S3\S3Client;
use Illuminate\Support\Str;

class FileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function initiateUpload(Request $request)
    {
        $request->validate([
            'fileName' => 'required|string',
            'fileType' => 'required|string',
            'fileSize' => 'required|integer',
        ]);

        $fileName = pathinfo($request->fileName, PATHINFO_FILENAME);
        $extension = pathinfo($request->fileName, PATHINFO_EXTENSION);
        $uniqueFileName = $fileName . '_' . Str::uuid() . '.' . $extension;

        // You can optionally use Auth::id() if the user is logged in
        $key = "uploads/anonymous/{$uniqueFileName}";

        $s3Client = new S3Client([
            'version' => 'latest',
            'region' => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ]);

        $bucket = env('AWS_BUCKET');

        $result = $s3Client->createMultipartUpload([
            'Bucket' => $bucket,
            'Key' => $key,
            'ContentType' => $request->fileType,
        ]);

        return response()->json([
            'uploadId' => $result['UploadId'],
            'key' => $key,
            'bucket' => $bucket,
            'region' => env('AWS_DEFAULT_REGION'),
            'fileName' => $uniqueFileName,
            'partSize' => 5 * 1024 * 1024, // 5MB part size
            'message' => 'Multipart upload initiated',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function getPresignedUrls(Request $request)
    {
        $request->validate([
            'uploadId' => 'required|string',
            'key' => 'required|string',
            'parts' => 'required|array',
            'parts.*' => 'integer|min:1'
        ]);

        $uploadId = $request->uploadId;
        $key = $request->key;
        $parts = $request->parts;
        $bucket = env('AWS_BUCKET');

        // Initialize AWS S3 Client
        $s3 = new S3Client([
            'version' => 'latest',
            'region' => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ]);

        $urls = [];

        // Generate pre-signed URL for each part
        foreach ($parts as $partNumber) {
            $command = $s3->getCommand('UploadPart', [
                'Bucket' => $bucket,
                'Key' => $key,
                'UploadId' => $uploadId,
                'PartNumber' => $partNumber,
            ]);

            $presignedRequest = $s3->createPresignedRequest($command, '+20 minutes');

            $urls[] = [
                'partNumber' => $partNumber,
                'url' => (string) $presignedRequest->getUri(),
            ];
        }

        return response()->json(['urls' => $urls]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $awsPath = 'https://flimsabucket.s3.us-east-2.amazonaws.com/';
        $path = $request->file('file')->store('public/files');

        return response()->json([
            'path' => $awsPath . $path,
            'msg' => 'success'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function completeUpload(Request $request)
    {
        $request->validate([
            'uploadId' => 'required|string',
            'key' => 'required|string',
            'parts' => 'required|array',
            'parts.*.PartNumber' => 'required|integer',
            'parts.*.ETag' => 'required|string',
        ]);

        $s3Client = new S3Client([
            'version' => 'latest',
            'region' => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ]);

        // Complete multipart upload
        $result = $s3Client->completeMultipartUpload([
            'Bucket' => env('AWS_BUCKET'),
            'Key' => $request->key,
            'UploadId' => $request->uploadId,
            'MultipartUpload' => [
                'Parts' => $request->parts,
            ],
        ]);

        // Fetch full object metadata
        $object = $s3Client->headObject([
            'Bucket' => env('AWS_BUCKET'),
            'Key' => $request->key,
        ]);

        return response()->json([
            'message' => 'Upload completed successfully',
            'location' => $result['Location'] ?? null,
            'key' => $request->key,
            'object' => $object->toArray(), // full metadata here
        ]);
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
