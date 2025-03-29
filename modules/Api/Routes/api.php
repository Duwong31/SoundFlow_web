<?php

use Illuminate\Http\Request;
use \Illuminate\Support\Facades\Route;
use Modules\Api\Controllers\LifestyleController;
use Modules\Api\Controllers\CarBrandController;
use Modules\Api\Controllers\CarLocationController;
use Modules\Api\Controllers\FeeController;

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
/* Config */

Route::get('configs', 'BookingController@getConfigs')->name('api.get_configs');
/* Service */
Route::get('services', 'SearchController@searchServices')->name('api.service-search');
Route::get('{type}/search', 'SearchController@search')->name('api.search2');
Route::get('{type}/detail/{id}', 'SearchController@detail')->name('api.detail');
Route::get('{type}/availability/{id}', 'SearchController@checkAvailability')->name('api.service.check_availability');
Route::get('boat/availability-booking/{id}', 'SearchController@checkBoatAvailability')->name('api.service.checkBoatAvailability');

Route::get('{type}/filters', 'SearchController@getFilters')->name('api.service.filter');
Route::get('{type}/form-search', 'SearchController@getFormSearch')->name('api.service.form');

Route::group(['middleware' => 'api'], function () {
    Route::post('{type}/write-review/{id}', 'ReviewController@writeReview')->name('api.service.write_review');
});


/* Layout HomePage */
Route::get('home-page', 'BookingController@getHomeLayout')->name('api.get_home_layout');

Route::post('forgot-password', 'AuthController@forgotPassword')->name('api.forgot-password');
Route::post('reset-password', 'AuthController@resetPassword')->name('api.reset-password');

/* Register - Login */
Route::group(['middleware' => 'api', 'prefix' => 'auth'], function ($router) {
    // Public routes
    Route::post('login', 'AuthController@login')->middleware(['throttle:login']);
    Route::post('register', 'AuthController@register');
    Route::post('verify-phone-otp', 'AuthController@verifyPhoneOTP');
    Route::post('resend-otp', 'AuthController@resendOTP');

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
    Route::get('booking-history', 'UserController@getBookingHistory')->name("api.user.booking_history");
    Route::post('/wishlist', 'UserController@handleWishList')->name("api.user.wishList.handle");
    Route::get('/wishlist', 'UserController@indexWishlist')->name("api.user.wishList.index");
    Route::post('/permanently_delete', 'UserController@permanentlyDelete')->name("user.permanently.delete");
    Route::post('/cancel-booking', 'UserController@cancelBooking');
});

/* Location */
Route::get('locations', 'LocationController@search')->name('api.location.search');
Route::get('location/{id}', 'LocationController@detail')->name('api.location.detail');

// Booking
Route::group(['prefix' => config('booking.booking_route_prefix')], function () {
    // Add comment to explain direct booking option
    // Use direct_booking=1 parameter to complete booking in a single API call for logged-in users
    Route::post('/addToCart', 'BookingController@addToCart')->name("api.booking.add_to_cart");
    Route::post('/addEnquiry', 'BookingController@addEnquiry')->name("api.booking.add_enquiry");
    Route::post('/doCheckout', 'BookingController@doCheckout')->name('api.booking.doCheckout');
    Route::get('/confirm/{gateway}', 'BookingController@confirmPayment');
    Route::get('/cancel/{gateway}', 'BookingController@cancelPayment');
    Route::get('/{code}', 'BookingController@detail');
    Route::get('/{code}/thankyou', 'BookingController@thankyou')->name('booking.thankyou');
    Route::get('/{code}/checkout', 'BookingController@checkout');
    Route::get('/{code}/check-status', 'BookingController@checkStatusCheckout');
});

// Gateways
Route::get('/gateways', 'BookingController@getGatewaysForApi');

// News
Route::get('news', 'NewsController@search')->name('api.news.search');
Route::get('news/category', 'NewsController@category')->name('api.news.category');
Route::get('news/{id}', 'NewsController@detail')->name('api.news.detail');

/* Media */
Route::group(['prefix' => 'media', 'middleware' => 'auth:sanctum'], function () {
    Route::post('/store', 'MediaController@store')->name("api.media.store");
});

Route::get('cars', 'CarController@listCars')->name('api.cars.list');
Route::get('car-brands', [CarBrandController::class, 'listBrands']);
Route::get('car-brands/{brand_id}', [CarBrandController::class, 'getCarsByBrand']);

Route::group(['prefix' => 'lifestyle'], function() {
    Route::get('/category', [LifestyleController::class, 'getCategories']);
    Route::get('/', [LifestyleController::class, 'getList']);
    Route::get('/{id}', [LifestyleController::class, 'getDetail']);
});

Route::get('car-locations', [CarLocationController::class, 'getPopularLocations']);
Route::get('car-locations/{location_id}/cars', [CarLocationController::class, 'getCarsByLocation']);

Route::group(['middleware' => 'auth:sanctum'], function() {
    Route::get('user/coupons', 'CouponController@getUserCoupons');
    Route::post('user/coupons/verify', 'CouponController@verifyCode');
    Route::post('user/coupons/apply', 'CouponController@applyCoupon');
    Route::post('user/coupons/remove', 'CouponController@removeCoupon');

    // Notification routes
    Route::prefix('notifications')->group(function() {
        Route::get('/', 'NotificationController@getNotifications');
        Route::get('/unread-count', 'NotificationController@getUnreadCount'); 
        Route::post('/mark-as-read', 'NotificationController@markAsRead');
        Route::post('/mark-all-as-read', 'NotificationController@markAllAsRead');
        Route::delete('/delete', 'NotificationController@deleteNotification');
        Route::delete('/delete-all', 'NotificationController@deleteAllNotifications');
    });
});

// review
Route::post('/car/write-review/{id}', 'ReviewController@writeReview');
Route::get('/car/reviews/{id}', 'ReviewController@getCarReviews');

Route::post('/car/check-availability', 'CarController@checkAvailability');


// feedback
Route::middleware(['auth:sanctum'])->namespace('Modules\Api\Controllers')->group(function() {
    Route::post('/feedback', 'FeedbackController@submit');
});

Route::group(['middleware' => 'api'], function () {
    Route::post('/calculate-fee', [FeeController::class, 'calculateFee'])->name('api.calculate_fee');
});
