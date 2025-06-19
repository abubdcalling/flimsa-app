<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\WishListController;
use App\Models\WishList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * |--------------------------------------------------------------------------
 * | API Routes
 * |--------------------------------------------------------------------------
 * |
 * | Here is where you can register API routes for your application. These
 * | routes are loaded by the RouteServiceProvider and all of them will
 * | be assigned to the "api" middleware group. Make something great!
 * |
 */

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::post('password/email', [AuthController::class, 'sendResetOTP']);
Route::post('password/verify-otp', [AuthController::class, 'verifyResetOTP'])->name('password.verify-otp');
Route::post('password/reset', [AuthController::class, 'passwordReset'])->name('password.reset');

Route::get('home', [GenreController::class, 'Home']);
Route::get('search', [GenreController::class, 'SearchContent']);
Route::get('genres', [GenreController::class, 'index']);


 Route::get('me', [AuthController::class, 'me'])->middleware('auth:api');
 Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');


Route::middleware('auth:api')->group(function () {


    Route::middleware(['role:admin'])->group(function () {

        Route::apiResource('contents', ContentController::class)->except(['index','show']);
        Route::apiResource('genres', GenreController::class)->except(['Home','index']);
        Route::apiResource('subscriptions', SubscriptionController::class)->except(['index']);
        // Route::get('contents/history', [ContentController::class, 'History']);

        Route::prefix('settings')->group(function () {
            Route::put('password', [SettingController::class, 'storeOrUpdatePassword']);
            Route::post('info', [SettingController::class, 'storeOrUpdate']);
            Route::get('info', [SettingController::class, 'index']);
        });

    });

    Route::middleware(['role:subscriber'])->group(function () {
        Route::post('updateInfo', [SettingController::class, 'storeOrUpdateForUser']);
        Route::get('updateInfo', [SettingController::class, 'ShowsForUser']);
        Route::put('contents/{content}/like', [ContentController::class, 'updateLike']);
        Route::get('contents/{id}', [ContentController::class,'show']);

        Route::get('contents/history', [ContentController::class, 'History']);
        Route::apiResource('wishlist', WishListController::class);
        Route::post('/checkout', [StripePaymentController::class, 'PaymentIntent']);
    });


});

Route::get('contents', [ContentController::class, 'index']);
Route::get('allcontents', [ContentController::class, 'allcontents']);
Route::get('upcoming-content', [ContentController::class, 'upcomingContent']);






