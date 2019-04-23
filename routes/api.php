<?php

use Illuminate\Http\Request;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
 

Route::group(['prefix' => 'nas'], function() {
	Route::get('/listnas', 'Api\NasController@index')->name('listnas');
});

Route::group(['prefix' => 'raduser'], function() {
	Route::get('/listraduser', 'Api\RadCheckController@index');
	Route::post('/adduser', 'Api\RadCheckController@createUser');
	Route::put('/updateuser/{id}', 'Api\RadCheckController@updateUser');
	Route::delete('/delete/{username}', 'Api\RadCheckController@destroy');
});

Route::group(['prefix' => 'acct'], function() {
	Route::get('/trafficusagemikrotik', 'Api\RadAccountingController@trafficusagemikrotik');
});

Route::group(['prefix' => 'bandwidth'], function() {
	Route::get('/usage/{mac}', 'Api\RadAccountingController@bandwidthClientUsage');
	Route::get('/total_usage', 'Api\RadAccountingController@total_bandwidth_usage');
});