<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Models\GymExerciseInventory; // Pastikan import model Pivot kita
use Illuminate\Http\Request;

class ExerciseController extends Controller
{
    /**
     * GET: Tampilkan Katalog Latihan Khusus untuk Member
     * Logika B2B2C: Hanya tampilkan latihan yang 'is_active' di Gym tempat member tersebut mendaftar.
     */
    public function index(Request $request)
    {
        // 1. Ambil gym_id dari user (member) yang sedang login
        $gymId = $request->user()->gym_id;

        // 2. Ambil semua ID latihan yang diaktifkan oleh Gym Owner di inventory mereka
        $activeExerciseIds = GymExerciseInventory::where('gym_id', $gymId)
                                ->where('is_active', true)
                                ->pluck('exercise_id');

        // 3. Ambil detail latihannya berdasarkan ID yang aktif tadi
        // Kita gunakan select spesifik agar payload JSON tidak terlalu berat di HP (PWA)
        $exercises = Exercise::whereIn('id', $activeExerciseIds)
                        ->select('id', 'name', 'target_muscle', 'type', 'base_xp', 'asset_url')
                        ->get();

        return response()->json([
            'message' => 'Available exercises fetched successfully',
            'data' => $exercises
        ]);
    }
    
    // 💡 FUNGSI STORE, UPDATE, DAN DESTROY SAYA HAPUS DARI SINI.
    // Nanti kita akan buatkan `GymOwner/ExerciseController` khusus untuk Owner 
    // agar keamanannya terpisah dan tidak tercampur dengan API Member.
}