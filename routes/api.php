<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminKursusController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\NotifikasiController;

// AUTH
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/enrollments', [EnrollmentController::class, 'index']);
    Route::post('/enroll', [EnrollmentController::class, 'store']);
    Route::get('/check-enrollment/{kursus_id}', [EnrollmentController::class, 'checkEnrollment']);
    Route::post('/materi/complete', [EnrollmentController::class, 'tandaiMateriSelesai']);
    Route::get('/materi/{enrollmentId}/{materiId}/is-completed', [EnrollmentController::class, 'cekMateriSelesai']);


    Route::get('/notifikasi', [NotifikasiController::class, 'getUserNotif']);
    Route::post('/notifikasi/read/{id}', [NotifikasiController::class, 'markRead']);
    Route::get('/users', [AuthController::class, 'getAllUsers']);
    Route::post('/notifikasi/send', [NotifikasiController::class, 'sendNotification']);
    Route::post('/users/fcm-token', [AuthController::class, 'updateFcmToken']);
});

// KURSUS
Route::get('/courses', [AdminKursusController::class, 'apiIndex']);
Route::get('/courses/{id}', [AdminKursusController::class, 'apiShow']);
Route::get('/materi/{kursus_id}', [AdminKursusController::class, 'apiMateri']);

// notif share kursus
Route::get('/users/search', [AuthController::class, 'searchUser']);



