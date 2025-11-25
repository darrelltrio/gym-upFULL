<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ExerciseController;

// GET: Ambil semua latihan (Katalog)
Route::get('/exercises', [ExerciseController::class, 'index']);

// POST: Tambah latihan baru (BARU)
Route::post('/exercises', [ExerciseController::class, 'store']);