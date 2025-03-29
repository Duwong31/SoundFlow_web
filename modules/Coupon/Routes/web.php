<?php
use \Illuminate\Support\Facades\Route;

Route::group(['prefix'=>config('booking.booking_route_prefix')],function(){
    Route::post('/{code}/apply-coupon','CouponController@applyCoupon')->name('coupon.apply');
    Route::post('/{code}/remove-coupon','CouponController@removeCoupon')->name('coupon.remove');
});


Route::group(['prefix'=>'user/coupon','middleware' => ['auth','verified']],function(){
    Route::get('/','CouponController@index')->name('coupon.vendor.index');
    Route::get('/create','CouponController@create')->name('coupon.vendor.create');
    Route::get('/edit/{id}','CouponController@edit')->name('coupon.vendor.edit');
    Route::get('/del/{id}', 'CouponController@delete')->name('coupon.vendor.delete');
    Route::post('/store/{id}','CouponController@store')->name('coupon.vendor.store');
    Route::get('/get_services', 'CouponController@getServiceForSelect2')->name('coupon.vendor.getServices');
});
