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
        $user = $request->user(); // Ambil user dari token (Eksklusif)

        // Validasi
        $validated = $request->validate([
            'duration_seconds' => 'required|integer|min:1',
            'performed_at' => 'nullable|date', // Opsional, default: now()
            'exercises' => 'required|array|min:1',
            'exercises.*.exercise_id' => 'required|integer',
            'exercises.*.sets' => 'required|array|min:1',
            'exercises.*.sets.*.weight_kg' => 'required|numeric|min:0',
            'exercises.*.sets.*.reps' => 'required|integer|min:1',
        ]);

        DB::beginTransaction(); // Mulai transaksi aman

        try {
            // A. Buat Sesi Baru
            $session = WorkoutSession::create([
                'user_id' => $user->user_id,
                'session_date' => $validated['performed_at'] ?? now(),
                'duration_seconds' => $validated['duration_seconds']
            ]);

            $totalSessionVolume = 0;

            // B. Loop Simpan Detail Latihan
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

            // C. UPDATE STATS USER (PENTING UNTUK LEADERBOARD)
            // Tanpa ini, Leaderboard tidak akan berubah meskipun user latihan
            $user->total_volume += $totalSessionVolume;
            
            // Opsional: Tambah XP kecil untuk aktivitas logging (Gamifikasi)
            // XP Besar nanti dari Quest Claim
            $user->xp += 10; 
            
            $user->save(); // Simpan perubahan ke tabel users

            DB::commit();

            return response()->json([
                'message' => 'Workout logged successfully!',
                'session_id' => $session->session_id,
                'volume_added' => $totalSessionVolume,
                'new_total_volume' => $user->total_volume // Return ini agar frontend bisa update UI
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to log workout', 'error' => $e->getMessage()], 500);
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

        // Query Eksklusif: where('user_id', $user->user_id)
        // Eager Loading 'logs.exercise' agar nama latihan langsung terbawa
        $history = WorkoutSession::where('user_id', $user->user_id)
            ->with(['logs.exercise' => function($query) {
                $query->select('exercise_id', 'name'); // Ambil nama saja biar ringan
            }])
            ->orderBy('session_date', 'desc')
            ->limit(20) // Batasi 20 terakhir agar ringan (Pagination nanti)
            ->get();

        // Format Data untuk Frontend (Opsional, agar lebih rapi JSON-nya)
        $formattedHistory = $history->map(function ($session) {
            return [
                'session_id' => $session->session_id,
                'date' => $session->session_date,
                'duration' => $session->duration_seconds,
                'total_sets' => $session->logs->count(),
                // Ambil nama-nama latihan unik yang dilakukan di sesi ini
                'exercises_played' => $session->logs->pluck('exercise.name')->unique()->values()
            ];
        });

        return response()->json($formattedHistory);
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
}