<?php

/*
|--------------------------------------------------------------------------
| Avail Calendar Routes
|--------------------------------------------------------------------------
|
*/

// Get the calendar
Route::get('/avail/api/get-calendar', ['as' => 'avc-get-calendar', 'uses' => 'Anzware\Avail\AvailAPIController@getCalendar']);

// Get calendar with data
Route::get('/avail/api/get-calendar-with-data', ['as' => 'avc-get-calendar-with-data', 'uses' => 'Anzware\Avail\AvailAPIController@getCalendarWithData']);

// Get calendar states
Route::get('/avail/api/get-state', ['as' => 'avc-get-state', 'uses' => 'Anzware\Avail\AvailAPIController@getState']);


/*
|--------------------------------------------------------------------------
| Avail Calendar Filtered Routes
|--------------------------------------------------------------------------
|
| You should add 'avail-auth' filter in laravel filters.php to protect
| this route from unauthorized access, eg:
| Route::filter('avail-auth', function()
| {
|     // your auth filter
| });
*/

Route::group(array('before' => 'avail-auth'), function()
{
    // create new calendar item
    Route::post('/avail/api/post-new-calendar', ['as' => 'avc-post-new-calendar', 'uses' => 'Anzware\Avail\AvailAPIController@postNewCalendar']);

    // update existing calendar item
    Route::post('/avail/api/post-update-calendar', ['as' => 'avc-post-update-calendar', 'uses' => 'Anzware\Avail\AvailAPIController@postUpdateCalendar']);

    // create new state item
    Route::post('/avail/api/post-new-state', ['as' => 'avc-post-new-state', 'uses' => 'Anzware\Avail\AvailAPIController@postNewState']);

    // update existing state item
    Route::post('/avail/api/post-update-state', ['as' => 'avc-post-update-state', 'uses' => 'Anzware\Avail\AvailAPIController@postUpdateState']);

    // create new booking item
    Route::post('/avail/api/post-new-booking', ['as' => 'avc-post-new-booking', 'uses' => 'Anzware\Avail\AvailAPIController@postNewBooking']);

    // update existing booking item
    Route::post('/avail/api/post-release-booking', ['as' => 'avc-post-release-booking', 'uses' => 'Anzware\Avail\AvailAPIController@postReleaseBooking']);
});