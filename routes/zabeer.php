<?php

use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;

 Route::apiResource('files', FileController::class);