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

        // 🚀 Rute Baru: Dashboard Utama
        Route::get('/owner/dashboard', [\App\Http\Controllers\API\Owner\DashboardController::class, 'index']);
        
        // Manajemen Inventory & Custom Exercises (yang sudah kita buat)
        Route::get('/owner/exercises', [\App\Http\Controllers\API\Owner\ExerciseController::class, 'index']);
        Route::post('/owner/exercises/toggle', [\App\Http\Controllers\API\Owner\ExerciseController::class, 'toggleInventory']);
        Route::post('/owner/exercises', [\App\Http\Controllers\API\Owner\ExerciseController::class, 'store']);
        
        // 🚀 Rute Baru: Kasir & Keuangan
        Route::get('/owner/transactions', [\App\Http\Controllers\API\Owner\TransactionController::class, 'index']);
        Route::post('/owner/transactions', [\App\Http\Controllers\API\Owner\TransactionController::class, 'store']);

        // 🚀 Rute Baru: Manajemen Member
        Route::get('/owner/members', [\App\Http\Controllers\API\Owner\MemberController::class, 'index']);
        Route::post('/owner/members', [\App\Http\Controllers\API\Owner\MemberController::class, 'store']);
        Route::get('/owner/members/{id}', [\App\Http\Controllers\API\Owner\MemberController::class, 'show']);
        
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

        // 🚀 Rute Baru untuk Quests
        Route::get('/member/quests', [\App\Http\Controllers\API\QuestController::class, 'index']);
        Route::post('/member/quests/{id}/claim', [\App\Http\Controllers\API\QuestController::class, 'claim']);

        // 🚀 Rute Baru untuk Nutrisi
        Route::get('/member/foods', [\App\Http\Controllers\API\NutritionController::class, 'getFoods']);
        Route::get('/member/nutrition', [\App\Http\Controllers\API\NutritionController::class, 'index']);
        Route::post('/member/nutrition', [\App\Http\Controllers\API\NutritionController::class, 'store']);
        Route::delete('/member/nutrition/{id}', [\App\Http\Controllers\API\NutritionController::class, 'destroy']);
        
    });

    // --- GRUP KHUSUS SUPER ADMIN ---
    Route::middleware('role:super_admin')->group(function () {
        
        // B2B Onboarding (Daftarin Gym Baru)
        Route::get('/admin/gyms', [\App\Http\Controllers\API\Admin\GymController::class, 'index']);
        Route::post('/admin/gyms', [\App\Http\Controllers\API\Admin\GymController::class, 'store']);
        
        // 🚀 Rute Baru: Master Data Exercises (Global)
        Route::get('/admin/master/exercises', [\App\Http\Controllers\API\Admin\MasterDataController::class, 'getExercises']);
        Route::post('/admin/master/exercises', [\App\Http\Controllers\API\Admin\MasterDataController::class, 'storeExercise']);
        Route::put('/admin/master/exercises/{id}', [\App\Http\Controllers\API\Admin\MasterDataController::class, 'updateExercise']);
        Route::delete('/admin/master/exercises/{id}', [\App\Http\Controllers\API\Admin\MasterDataController::class, 'destroyExercise']);

        // 🚀 Rute Baru: Master Data Foods
        Route::get('/admin/master/foods', [\App\Http\Controllers\API\Admin\MasterDataController::class, 'getFoods']);
        Route::post('/admin/master/foods', [\App\Http\Controllers\API\Admin\MasterDataController::class, 'storeFood']);
        Route::put('/admin/master/foods/{id}', [\App\Http\Controllers\API\Admin\MasterDataController::class, 'updateFood']);
        Route::delete('/admin/master/foods/{id}', [\App\Http\Controllers\API\Admin\MasterDataController::class, 'destroyFood']);
        
    });
});