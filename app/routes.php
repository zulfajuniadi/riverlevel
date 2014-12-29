<?php

App::after(function($request, $response)
{
   $response->headers->set('Cache-Control','max-age=1800');
});

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::get('/', function()
{
    return 'Malaysian River Levels API';
});

Route::group(['prefix' => 'rivers', 'after' => 'cors'], function()
{
    Route::get('state/{state_name}', 'RiverController@states');
    Route::get('alerts', 'RiverController@alerts');
});

Route::group(['prefix' => 'cron', 'after' => 'cors'], function()
{
    Route::get('rivers', 'CronController@rivers');
});

