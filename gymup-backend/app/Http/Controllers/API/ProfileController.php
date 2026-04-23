<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WorkoutSession;
use App\Models\ExerciseLog;

class ProfileController extends Controller
{
    /**
     * Mengambil profil member beserta kalkulasi RPG Stats untuk Radar Chart
     */
    public function show(Request $request)
    {
        $user = $request->user();

        // ==========================================================
        // 1. Kalkulasi STR (Strength)
        // Logika: Total Volume (Beban x Reps) HANYA dari latihan bertipe 'compound'
        // ==========================================================
        $strScore = ExerciseLog::whereHas('workoutSession', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->whereHas('exercise', function ($q) {
                $q->where('type', 'compound'); // Isolasi data hanya gerakan Compound
            })
            ->selectRaw('SUM(weight_kg * reps) as total_str')
            ->value('total_str') ?? 0;

        // ==========================================================
        // 2. Kalkulasi AGI (Agility)
        // Logika: Total durasi menit dari sesi latihan yang mengandung gerakan 'cardio'
        // ==========================================================
        $agiScore = WorkoutSession::where('user_id', $user->id)
            ->whereHas('exerciseLogs.exercise', function ($q) {
                $q->where('type', 'cardio');
            })
            ->sum('duration_minutes') ?? 0;

        // ==========================================================
        // 3. Kalkulasi INT (Intelligence / Consistency)
        // Logika: Kombinasi antara kedisiplinan (streak) dan total sesi
        // ==========================================================
        $totalSessions = WorkoutSession::where('user_id', $user->id)->count();
        $intScore = ($user->current_streak * 10) + ($totalSessions * 5);

        // ==========================================================
        // 4. Penentuan "Class" Karakter (Logika Dinamis)
        // ==========================================================
        $rpgClass = 'Novice';
        // Angka pengali di bawah ini bisa kamu sesuaikan (balancing) nantinya
        if ($strScore > ($agiScore * 100) && $strScore > ($intScore * 50)) {
            $rpgClass = 'Barbarian'; // Paling sering angkat beban berat
        } elseif ($agiScore > 30 && ($agiScore * 100) > $strScore) {
            $rpgClass = 'Rogue'; // Sering banget Cardio / lari
        } elseif ($intScore > 100 && $intScore > ($strScore / 50)) {
            $rpgClass = 'Monk'; // Beban biasa saja, tapi konsistensi/streak luar biasa
        }

        // ==========================================================
        // 5. Normalisasi Skor untuk Radar Chart (Skala 0 - 100)
        // Chart.js butuh data dengan skala yang setara agar bentuknya rapi
        // ==========================================================
        $chartStr = min(100, ceil($strScore / 500)); // Misal 50.000 kg volume = 100
        $chartAgi = min(100, ceil($agiScore / 2));   // Misal 200 menit cardio = 100
        $chartInt = min(100, ceil($intScore));       // Skor murni max 100

        // Menghitung Level dari XP (Setiap 1000 XP = Naik 1 Level)
        $level = floor($user->xp / 1000) + 1;

        return response()->json([
            'status' => 'success',
            'data' => [
                'profile' => $user->load('gym'), // Data standar user & info gym
                'rpg_profile' => [
                    'class' => $rpgClass,
                    'level' => $level,
                    'raw_stats' => [
                        'STR' => (int) $strScore,
                        'AGI' => (int) $agiScore,
                        'INT' => (int) $intScore,
                    ],
                    'chart_data' => [
                        'STR' => $chartStr,
                        'AGI' => $chartAgi,
                        'INT' => $chartInt,
                    ]
                ]
            ]
        ], 200);
    }
}