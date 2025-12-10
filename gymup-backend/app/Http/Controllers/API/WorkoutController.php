<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\WorkoutSession;
use App\Models\ExerciseLog;

class WorkoutController extends Controller
{
    /**
     * 1. LOG WORKOUT (Input Data)
     * - Menyimpan Sesi & Log Latihan
     * - Mengupdate Total Volume user (Untuk Leaderboard!)
     */
    public function store(Request $request)
    {
        $user = $request->user(); // Ambil user dari token

        // 1. Validasi Input
        $validated = $request->validate([
            'duration_seconds' => 'required|integer|min:1',
            'performed_at' => 'nullable|date',
            'exercises' => 'required|array|min:1',
            'exercises.*.exercise_id' => 'required|integer',
            'exercises.*.sets' => 'required|array|min:1',
            'exercises.*.sets.*.weight_kg' => 'required|numeric|min:0',
            'exercises.*.sets.*.reps' => 'required|integer|min:1',
        ]);

        DB::beginTransaction(); // Mulai transaksi database

        try {
            // 2. Buat Sesi Latihan Baru
            $session = WorkoutSession::create([
                'user_id' => $user->user_id,
                'session_date' => $validated['performed_at'] ?? now(),
                'duration_seconds' => $validated['duration_seconds']
            ]);

            $totalSessionVolume = 0;

            // 3. Simpan Detail Set Latihan
            foreach ($validated['exercises'] as $exerciseData) {
                foreach ($exerciseData['sets'] as $index => $set) {
                    ExerciseLog::create([
                        'session_id' => $session->session_id,
                        'exercise_id' => $exerciseData['exercise_id'],
                        'set_number' => $index + 1,
                        'weight_kg' => $set['weight_kg'],
                        'reps' => $set['reps']
                    ]);

                    // Hitung Volume (Berat x Reps)
                    $totalSessionVolume += ($set['weight_kg'] * $set['reps']);
                }
            }

            // ======================================================
            // 4. LOGIKA HITUNG STREAK (Harian)
            // ======================================================
            
            // Ambil sesi terakhir user SEBELUM sesi yang baru dibuat ini
            $lastSession = WorkoutSession::where('user_id', $user->user_id)
                ->where('session_id', '!=', $session->session_id) // Exclude sesi ini
                ->orderBy('session_date', 'desc')
                ->first();

            // Normalisasi tanggal ke "Start of Day" (jam 00:00:00) agar akurat
            $currentDate = \Carbon\Carbon::parse($session->session_date)->startOfDay();

            if ($lastSession) {
                $lastDate = \Carbon\Carbon::parse($lastSession->session_date)->startOfDay();
                
                // Hitung selisih hari
                $diffInDays = $lastDate->diffInDays($currentDate);

                if ($diffInDays == 1) {
                    // Latihan kemarin (Consecutive) -> Streak Nambah
                    $user->current_streak += 1;
                } elseif ($diffInDays > 1) {
                    // Bolong lebih dari 1 hari -> Reset Streak jadi 1
                    $user->current_streak = 1;
                }
                // Jika diffInDays == 0 (Latihan di hari yang sama), Streak TETAP (tidak nambah)
            } else {
                // Tidak ada sesi sebelumnya (Latihan Pertama) -> Streak 1
                $user->current_streak = 1;
            }

            // ======================================================
            // 5. UPDATE STATS USER
            // ======================================================
            
            // Update Total Volume
            $user->total_volume += $totalSessionVolume;

            // HAPUS LOGIKA XP LAMA:
            // $user->xp += 10; <--- Dihapus, karena XP sekarang via Quest Claim

            $user->save(); // Simpan perubahan ke tabel users
            DB::commit();

            return response()->json([
                'message' => 'Workout logged successfully!',
                'session_id' => $session->session_id,
                'volume_added' => $totalSessionVolume,
                'new_total_volume' => $user->total_volume,
                'current_streak' => $user->current_streak // Return streak terbaru ke Frontend
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to log workout', 
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 2. HISTORY (Output Data)
     * - Menampilkan daftar latihan HANYA milik user yang login.
     * - Diurutkan dari yang terbaru.
     */
    public function history(Request $request)
    {
        $user = $request->user();

        // Gunakan paginate(10) menggantikan get() atau limit()
        // with(['logs.exercise']) penting untuk performa
        $history = WorkoutSession::where('user_id', $user->user_id)
            ->with(['logs.exercise']) 
            ->orderBy('session_date', 'desc')
            ->paginate(10); 

        return response()->json([
            'status' => 'success',
            'data' => $history
        ], 200);
    }

    /**
     * 4. LEADERBOARD (Baru)
     * Menampilkan ranking user berdasarkan total_volume
     */
    public function leaderboard()
    {
        // Ambil Top 20 User dengan volume tertinggi
        // Select hanya kolom publik demi keamanan
        $leaders = \App\Models\User::select('user_id', 'username', 'level', 'total_volume', 'rank_points', 'goal')
            ->orderBy('total_volume', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $leaders
        ], 200);
    }

    /**
     * 3. DETAIL SESSION (Opsional tapi berguna)
     * - Untuk melihat detail "Apa saja set saya kemarin?"
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $session = WorkoutSession::where('session_id', $id)
            ->where('user_id', $user->user_id) // Security Check: Punya dia bukan?
            ->with('logs.exercise')
            ->first();

        if (!$session) {
            return response()->json(['message' => 'Session not found or unauthorized'], 404);
        }

        return response()->json($session);
    }

    public function weeklyLeaderboard()
    {
        // Tentukan awal dan akhir minggu ini (Senin 00:00 - Minggu 23:59)
        $startOfWeek = \Carbon\Carbon::now()->startOfWeek()->format('Y-m-d H:i:s');
        $endOfWeek = \Carbon\Carbon::now()->endOfWeek()->format('Y-m-d H:i:s');

        $leaders = \App\Models\User::select('users.user_id', 'users.username', 'users.level')
            // Gabungkan dengan tabel sesi & log
            ->join('workout_sessions', 'users.user_id', '=', 'workout_sessions.user_id')
            ->join('exercise_logs', 'workout_sessions.session_id', '=', 'exercise_logs.session_id')
            // Filter HANYA sesi minggu ini
            ->whereBetween('workout_sessions.session_date', [$startOfWeek, $endOfWeek])
            // Hitung total volume (Berat x Reps)
            ->selectRaw('SUM(exercise_logs.weight_kg * exercise_logs.reps) as weekly_volume')
            ->groupBy('users.user_id', 'users.username', 'users.level')
            ->orderByDesc('weekly_volume')
            ->limit(5) // Ambil Top 5 saja untuk dashboard
            ->get();

        return response()->json([
            'status' => 'success',
            'range' => [
                'start' => $startOfWeek,
                'end' => $endOfWeek
            ],
            'data' => $leaders
        ], 200);
    }
}