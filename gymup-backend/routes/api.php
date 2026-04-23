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
        
        // Rute sebelumnya...
        Route::get('/member/exercises', [\App\Http\Controllers\API\ExerciseController::class, 'index']);
        
        // Rute Workout...
        Route::post('/member/workouts/start', [\App\Http\Controllers\API\WorkoutController::class, 'startSession']);
        Route::post('/member/workouts/log', [\App\Http\Controllers\API\WorkoutController::class, 'logExercise']);
        Route::post('/member/workouts', [\App\Http\Controllers\API\WorkoutController::class, 'store']); // Bulk Store
        
        // 🚀 Rute Baru untuk Profil RPG
        Route::get('/member/profile', [\App\Http\Controllers\API\ProfileController::class, 'show']);
        
    });

    // --- GRUP KHUSUS SUPER ADMIN ---
    Route::middleware('role:super_admin')->group(function () {
        // ...
    });
});