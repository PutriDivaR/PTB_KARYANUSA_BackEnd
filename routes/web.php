<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\AdminKursusController;
use App\Http\Controllers\Api\AuthController;


// link untuk admin daftar kursus
Route::prefix('admin')->group(function () {
    Route::get('/kursus', [AdminKursusController::class, 'index']);
    Route::get('/kursus/create', [AdminKursusController::class, 'create']);
    Route::post('/kursus', [AdminKursusController::class, 'store']);
    Route::get('/kursus/{id}/edit', [AdminKursusController::class, 'edit']);
    Route::put('/kursus/{id}', [AdminKursusController::class, 'update']);
    Route::delete('/kursus/{id}', [AdminKursusController::class, 'destroy']);

});



// link untuk cek thumbnail
Route::get('/thumbnail/{filename}', function ($filename) {
    $path = storage_path('app/public/thumbnails/' . $filename);

    if (!file_exists($path)) {
        return response()->json(['error' => 'File not found'], 404);
    }

    $file = file_get_contents($path);
    $type = mime_content_type($path);

    return Response::make($file, 200)->header("Content-Type", $type);
});

// link untuk cek video
Route::get('/video/{filename}', function ($filename) {
    $path = storage_path('app/public/videos/' . $filename);

    if (!file_exists($path)) {
        abort(404, 'Video tidak ditemukan');
    }

    $mime = mime_content_type($path);
    return Response::file($path, ['Content-Type' => $mime]);
});

Route::get('/phpinfo', function () {
    phpinfo();
});