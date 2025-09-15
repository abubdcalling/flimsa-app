<?php

use App\Http\Controllers\AdController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\CoverController;
use App\Http\Controllers\EpisodeController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\SeasonController;
use App\Http\Controllers\SeriesController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SubtitleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VideoTrackingController;
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
        // Route::get('contents/{id}', [ContentController::class, 'show']); //for movie related content

        Route::apiResource('series', SeriesController::class)->only(['show', 'store', 'update', 'destroy']);
        Route::apiResource('seasons', SeasonController::class)->only(['show', 'store', 'update', 'destroy']);
        Route::apiResource('episodes', EpisodeController::class)->only(['show', 'store', 'update', 'destroy']);

        Route::post('subtitles/{contentId}', [SubtitleController::class, 'store']);
        Route::get('subtitles', [SubtitleController::class, 'index']);
        // Route::get('subtitles/{id}', [SubtitleController::class,'show']);
        Route::delete('subtitles/{id}', [SubtitleController::class, 'destroy']);

        Route::apiResource('contents', ContentController::class)->except(['index', 'show']);
        Route::apiResource('genres', GenreController::class)->except(['Home', 'index']);
        Route::apiResource('subscriptions', SubscriptionController::class)->except(['index']);
        Route::get('dashboard', [ContentController::class, 'dashbaord']);
        // Route::get('content/{id}', [ContentController::class, 'shows']);

        Route::prefix('settings')->group(function () {
            Route::put('password', [SettingController::class, 'storeOrUpdatePassword']);
            Route::post('info', [SettingController::class, 'storeOrUpdate']);
            Route::get('info', [SettingController::class, 'index']);
        });

        Route::get('content/{id}', [ContentController::class, 'show']);
        Route::apiResource('ads', AdController::class)->except(['index', 'show']);
        Route::get('all-users', [UserController::class, 'showAllUsers']);

        Route::post('/cover', [CoverController::class, 'postOrUpdateCover']);
        Route::get('/cover', [CoverController::class, 'getCover']);  // optional
    });

    Route::middleware(['role:subscriber'])->group(function () {
        Route::post('updateInfo', [SettingController::class, 'storeOrUpdateForUser']);
        Route::get('updateInfo', [SettingController::class, 'ShowsForUser']);
        Route::put('contents/{content}/like', [ContentController::class, 'updateLike']);
        Route::get('contents/{id}', [ContentController::class, 'show']);
        Route::put('users/password', [SettingController::class, 'storeOrUpdatePasswordForUser']);

        // Route::put('update-password', [SettingController::class,'storeOrUpdateForUser']);
        // Route::get('contents/{id}', [ContentController::class,'show']);

        // Route::get('historys', [ContentController::class, 'History']);
        Route::apiResource('wishlist', WishListController::class);

        // ---------------new edition start(06/08/2025)--------------
        // Route::post('/checkout', [StripePaymentController::class, 'PaymentIntent']);
        // Route::get('success', [StripePaymentController::class, 'success']);
        // Route::get('cancel', [StripePaymentController::class, 'cancel']);

        // ----------------new edition end(06/08/2025)--------------

        Route::apiResource('video-tracking', VideoTrackingController::class);
        Route::get('video-tracking/{user_id}/{content_id}', [VideoTrackingController::class, 'show']);
        Route::delete('video-tracking/{user_id}/{content_id}', [VideoTrackingController::class, 'destroy']);
        Route::put('users/plan-type', [UserController::class, 'updateMyPlanType']);
        // Route::get('content/{id}', [ContentController::class, 'individualContent']);
        Route::get('payment-status', [StripePaymentController::class, 'paymentStatus']);

        Route::delete('/users/{id}', [UserController::class, 'destroy']);
    });

    Route::middleware(['role:admin,subscriber'])->group(function () {
        Route::get('contents/{id}', [ContentController::class, 'show']);
        Route::get('historys', [ContentController::class, 'History']);
    });

    // Route::middleware(['role:admin,subscriber'])->group(function () {
    //     Route::put('password', [SettingController::class, 'storeOrUpdatePassword']);

    // });
});

Route::get('subscriptions', [SubscriptionController::class, 'index']);
Route::get('contents', [ContentController::class, 'index']);
Route::get('allcontents', [ContentController::class, 'allcontents']);
Route::get('upcoming-content', [ContentController::class, 'upcomingContent']);
Route::get('contents/{id}/related', [ContentController::class, 'relatedContent']);  // for movie related content
Route::get('contents/{id}/related-season', [ContentController::class, 'relatedSeasonContent']);  // for season related content
Route::get('subtitles/{id}', [SubtitleController::class, 'show']);
Route::get('ads', [AdController::class, 'index']);
Route::get('ads/{id}', [AdController::class, 'show']);

// ---------------new edition start(06/08/2025)--------------

Route::post('/checkout', [StripePaymentController::class, 'PaymentIntent']);
Route::post('/success', [StripePaymentController::class, 'success']);
Route::get('cancel', [StripePaymentController::class, 'cancel']);

// ----------------new edition end(06/08/2025)--------------

Route::post('/verify-payment-and-create-user', [StripePaymentController::class, 'verifyPaymentAndCreateUser']);
Route::apiResource('people', PersonController::class);

Route::get('getByContentId/{id}', [ContentController::class, 'getByContentId']);

//
Route::get('series', [SeriesController::class, 'index']);
Route::get('seasons', [SeasonController::class, 'index']);
Route::get('episodes', [EpisodeController::class, 'index']);
