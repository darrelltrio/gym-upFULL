<?php

use App\Http\Controllers\API\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes (Bisa diakses tanpa login)
|--------------------------------------------------------------------------
*/
// Untuk pendaftaran member baru (B2C)
Route::post('/register', [AuthController::class, 'register']);
// Login universal untuk semua Role
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Wajib membawa Bearer Token)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // --- GRUP KHUSUS GYM OWNER (B2B) ---
    Route::middleware('role:gym_owner')->group(function () {
        // Contoh: Route::get('/owner/stats', [GymOwnerController::class, 'stats']);
    });

    // --- GRUP KHUSUS MEMBER (B2C) ---
    Route::middleware('role:member')->group(function () {
        // Member hanya butuh GET untuk melihat daftar gerakan yang tersedia di gym-nya
        Route::get('/member/exercises', [\App\Http\Controllers\API\ExerciseController::class, 'index']);
    });

    // --- GRUP KHUSUS SUPER ADMIN ---
    Route::middleware('role:super_admin')->group(function () {
        // ...
    });
});