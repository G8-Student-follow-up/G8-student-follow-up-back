<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/reset-password/{token}', function (string $token) {
    return response()->json([
        'token' => $token,
        'email' => request('email'),
    ]);
})->name('password.reset');
