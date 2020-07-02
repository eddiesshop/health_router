<?php
use App\Classes\Route;

Route::get('/something/here/there', 'SomeController@someMethod');
//Route::post('/something', 'SomeController@someMethod');

Route::resource('patients');
Route::resource('patients.metrics');