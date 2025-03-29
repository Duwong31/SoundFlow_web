<?php
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth:sanctum'], function() {
    Route::post('/feedback', 'Controllers\FeedbackController@submit');
});