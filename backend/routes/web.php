<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

// Google OAuth routes for API (with /api prefix for frontend compatibility)
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// User routes
Route::get('/user', [AuthController::class, 'user']);
Route::get('/logout', [AuthController::class, 'logout']);
