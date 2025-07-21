<?php

use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;

//  Route::apiResource('files', FileController::class);
 Route::post('/initiate-upload', [FileController::class, 'initiateUpload']);
 Route::post('/get-presigned-urls', [FileController::class, 'getPresignedUrls']);
 Route::post('/complete-upload', [FileController::class, 'completeUpload']);