<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');})->name('login');
   /* return view('welcome');
});*/