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
    
    // Auth Management
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']); // Ambil profil user yang sedang login

    /* Kedepannya, kita akan menaruh rute spesifik di sini:
       - Rute Gym Owner (B2B)
       - Rute Member (B2C)
       - Rute Super Admin
    */
});