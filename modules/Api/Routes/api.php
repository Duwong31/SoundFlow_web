<?php

use Illuminate\Http\Request;
use \Illuminate\Support\Facades\Route;
use Modules\Api\Controllers\LifestyleController;
use Modules\Api\Controllers\CarBrandController;
use Modules\Api\Controllers\CarLocationController;
use Modules\Api\Controllers\FeeController;
use Modules\Api\Controllers\PlaylistController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('forgot-password', 'AuthController@forgotPassword')->name('api.forgot-password');
Route::post('reset-password', 'AuthController@resetPassword')->name('api.reset-password');

/* Register - Login */
Route::group(['middleware' => 'api', 'prefix' => 'auth'], function ($router) {
    // Public routes
    Route::post('login', 'AuthController@login')->middleware(['throttle:login']);
    Route::post('register', 'AuthController@register');
    Route::post('verify-phone-otp', 'AuthController@verifyPhoneOTP');
    Route::post('resend-otp', 'AuthController@resendOTP');
    // Route::post('forgot-password-email', [AuthController::class, 'forgotPasswordEmail'])->name('api.auth.forgot_password_email'); 
    Route::post('reset-password-email', 'AuthController@resetPasswordEmail')->name('api.auth.reset_password_email');
    Route::post('forgot-password-email', 'AuthController@forgotPasswordEmail')->name('api.auth.forgot_password_email'); 

    // Protected routes
    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::get('me', 'AuthController@me');
        Route::post('logout', 'AuthController@logout');
        Route::post('refresh', 'AuthController@refresh');
        Route::post('me', 'AuthController@updateUser');
        Route::post('change-password', 'AuthController@changePassword');
    });
    Route::post('set-password', 'AuthController@setPassword');
});



/* User */
Route::group(['prefix' => 'user', 'middleware' => ['api']], function ($router) {
    Route::post('/wishlist', 'UserController@handleWishList')->name("api.user.wishList.handle");
    Route::get('/wishlist', 'UserController@indexWishlist')->name("api.user.wishList.index");
    Route::post('/profile', 'UserController@updateProfile')->name('api.user.profile.update');
    Route::post('/permanently_delete', 'UserController@permanentlyDelete')->name("user.permanently.delete");
});


/*Playlist*/ 
Route::group(['middleware' => 'auth:sanctum'], function() {

    Route::get('playlists', 'PlaylistController@index')->name('api.playlists.index');
    Route::post('playlists', 'PlaylistController@store')->name('api.playlists.store');

    Route::post('playlists/{playlist}/tracks', [PlaylistController::class, 'addTrack'])
        ->name('api.playlists.tracks.add')
        ->where('playlist', '[0-9]+');
    Route::delete('playlists/{playlist}/tracks/{trackId}', [PlaylistController::class, 'removeTrack'])
        ->name('api.playlists.tracks.remove')
        ->where('trackId', '[0-9]+');
    Route::delete('playlists/{playlist}', [PlaylistController::class, 'destroy'])
    ->name('api.playlists.destroy')
    ->where('playlist', '[0-9]+');
});



