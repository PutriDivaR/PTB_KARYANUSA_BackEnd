<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminKursusController;

// AUTH
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

// KURSUS
Route::get('/courses', [AdminKursusController::class, 'apiIndex']);
Route::get('/courses/{id}', [AdminKursusController::class, 'apiShow']);
Route::get('/materi/{kursus_id}', [AdminKursusController::class, 'apiMateri']);
