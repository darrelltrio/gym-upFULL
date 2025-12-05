<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ExerciseController;
use App\Http\Controllers\Api\AuthController;

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
    
});