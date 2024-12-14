<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::view('/example-page', 'template.example-page');
Route::view('/example-auth', 'example-auth');
