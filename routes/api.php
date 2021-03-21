<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::prefix('v1')->group(function () {
    Route::get('order', 'OrderController@index');
    Route::get('unfinish', 'OrderController@unfinish');
    Route::get('finish', 'OrderController@finish');
    Route::get('payment-error', 'OrderController@error');
    Route::post('order', 'OrderController@create');

    Route::post('webhook', 'WebhookController@midtransHandler');
});
