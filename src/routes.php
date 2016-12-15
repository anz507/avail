<?php

/*
|--------------------------------------------------------------------------
| Avail Calendar Routes
|--------------------------------------------------------------------------
|
*/

Route::get('/avail/api/get-calendar', ['as' => 'avc-get-calendar', 'uses' => 'Anzware\Avail\AvailAPIController@getCalendar']);
