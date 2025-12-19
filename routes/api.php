<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminKursusController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\KaryaController;
use App\Http\Controllers\ForumPertanyaanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\LikeController;

// AUTH
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/enrollments', [EnrollmentController::class, 'index']);
    Route::post('/enroll', [EnrollmentController::class, 'store']);
    Route::get('/check-enrollment/{kursus_id}', [EnrollmentController::class, 'checkEnrollment']);
    Route::delete('/delete-enrollment/{kursus_id}', [EnrollmentController::class, 'destroy']);
    Route::post('/materi/complete', [EnrollmentController::class, 'tandaiMateriSelesai']);
    Route::get('/materi/{enrollmentId}/{materiId}/is-completed', [EnrollmentController::class, 'cekMateriSelesai']);

    // galeri auth
    Route::post('/karya/upload', [KaryaController::class, 'store']);
    Route::get('/karya/my', [KaryaController::class, 'my']);
    Route::delete('/karya/{id}', [KaryaController::class, 'destroy']);
    Route::post('/karya/update/{id}', [KaryaController::class, 'update']);

    // forum
    Route::post('/pertanyaan', [ForumPertanyaanController::class, 'store']);
    Route::get('/pertanyaan', [ForumPertanyaanController::class, 'index']);
    Route::post('/pertanyaan/{id}/jawaban', [ForumPertanyaanController::class, 'jawaban']);
    Route::post('/pertanyaan/{id}/update', [ForumPertanyaanController::class, 'update']); 
    Route::delete('/pertanyaan/{id}', [ForumPertanyaanController::class, 'destroy']);
    Route::get('/pertanyaan/{id}', [ForumPertanyaanController::class, 'show']);
    
    Route::get('/profile/{id}', [ProfileController::class, 'show']);
    Route::put('/profile/{id}', [ProfileController::class, 'update']);
    Route::get('/users', [AuthController::class, 'getAllUsers']);

    Route::get('/notifikasi', [NotifikasiController::class, 'getUserNotif']);
    Route::post('/notifikasi/{id}/read', [NotifikasiController::class, 'markRead']);
    Route::get('/users', [AuthController::class, 'getAllUsers']);
    Route::post('/notifikasi/send', [NotifikasiController::class, 'sendNotification']);
    Route::post('/users/fcm-token', [AuthController::class, 'updateFcmToken']);


    Route::post('/karya/{galeri_id}/like', [LikeController::class, 'toggleLike']);
    Route::get('/karya/{galeri_id}/check-like', [LikeController::class, 'checkLike']);

});

// KURSUS
Route::get('/courses', [AdminKursusController::class, 'apiIndex']);
Route::get('/courses/{id}', [AdminKursusController::class, 'apiShow']);
Route::get('/materi/{kursus_id}', [AdminKursusController::class, 'apiMateri']);

// galeri public
Route::get('/karya', [KaryaController::class, 'index']);
Route::post('/karya/{id}/view', [KaryaController::class, 'incrementView']);

