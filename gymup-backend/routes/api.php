<?php

use App\Http\Controllers\Api\ExerciseController;
use Illuminate\Support\Facades\Route;

Route::get('/exercises', [ExerciseController::class, 'index']);
Route::post('/exercises', [ExerciseController::class, 'store']);
Route::put('/exercises/{id}', [ExerciseController::class, 'update']);     // Route Edit
Route::delete('/exercises/{id}', [ExerciseController::class, 'destroy']); // Route Hapus