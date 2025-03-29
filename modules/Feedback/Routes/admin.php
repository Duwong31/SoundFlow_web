<?php
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'feedback'], function () {
    Route::get('/', 'FeedbackController@index')->name('feedback.admin.index');
    Route::get('/view/{id}', 'FeedbackController@view')->name('feedback.admin.view');
    Route::post('/bulk-edit', 'FeedbackController@bulkEdit')->name('feedback.admin.bulkEdit');
    Route::post('/response/{id}', 'FeedbackController@response')->name('feedback.admin.response');
    Route::get('/settings', 'FeedbackController@settings')->name('feedback.admin.settings');
    Route::post('/settings', 'FeedbackController@settingsStore')->name('feedback.admin.settings.store');
}); 