<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ExerciseController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NutritionController;
use App\Http\Controllers\API\WorkoutController;

// ==========================
// PUBLIC ROUTES (Register & Login)
// ==========================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// ==========================
// PROTECTED ROUTES (Harus Punya Token)
// ==========================
Route::middleware('auth:sanctum')->group(function () {
    
    // Fitur User
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Fitur Katalog (CRUD)
    Route::get('/exercises', [ExerciseController::class, 'index']); // Pindahkan ke sini jika ingin katalog privat
    Route::post('/exercises', [ExerciseController::class, 'store']);
    Route::put('/exercises/{id}', [ExerciseController::class, 'update']);
    Route::delete('/exercises/{id}', [ExerciseController::class, 'destroy']);

    //Fitur Profile
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    
    //Nutrition
    Route::get('/nutrition/recommendations', [NutritionController::class, 'getRecommendations']);
    Route::get('/nutrition/meal-ideas', [NutritionController::class, 'getMealIdeas']);

    // --- WORKOUT ROUTES ---
    
    // 1. Log Workout (POST)
    // Frontend mengirim JSON data latihan ke sini
    Route::post('/workouts', [WorkoutController::class, 'store']);

    // 2. Workout History (GET)
    // Frontend mengambil daftar riwayat untuk halaman 'History'
    Route::get('/workouts/history', [WorkoutController::class, 'history']);

    // 3. Session Detail (GET) - Opsional
    Route::get('/workouts/{id}', [WorkoutController::class, 'show']);

    Route::get('/workouts/history', [WorkoutController::class, 'history']);
    Route::get('/leaderboard', [WorkoutController::class, 'leaderboard']);

    // Route Weekly Leaderboard
    Route::get('/leaderboard/weekly', [WorkoutController::class, 'weeklyLeaderboard']);
});