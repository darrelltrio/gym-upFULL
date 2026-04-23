<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\WorkoutSession;
use App\Models\ExerciseLog;
use App\Models\User;
use Carbon\Carbon;

class WorkoutController extends Controller
{
    /**
     * 1. LOG WORKOUT (Sistem PWA Bulk Store + AI RPE)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // 1. Validasi Input sesuai Schema Baru
        $validated = $request->validate([
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'rpe_score' => 'required|integer|min:1|max:10', // Data RPE dari PWA
            'exercises' => 'required|array|min:1',
            'exercises.*.exercise_id' => 'required|exists:exercises,id',
            'exercises.*.sets' => 'required|array|min:1',
            'exercises.*.sets.*.weight_kg' => 'required|numeric|min:0',
            'exercises.*.sets.*.reps' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            // Hitung durasi dalam menit
            $start = Carbon::parse($validated['start_time']);
            $end = Carbon::parse($validated['end_time']);
            $durationMinutes = $start->diffInMinutes($end);

            // 2. Buat Sesi Latihan
            $session = WorkoutSession::create([
                'user_id' => $user->id,
                'start_time' => $start,
                'end_time' => $end,
                'duration_minutes' => $durationMinutes,
                'rpe_score' => $validated['rpe_score'],
                'total_volume' => 0,
                'session_xp' => 0,
            ]);

            $totalSessionVolume = 0;

            // 3. Simpan Detail Set Latihan (exerciseLogs)
            foreach ($validated['exercises'] as $exerciseData) {
                foreach ($exerciseData['sets'] as $index => $set) {
                    ExerciseLog::create([
                        'workout_session_id' => $session->id,
                        'exercise_id' => $exerciseData['exercise_id'],
                        'set_number' => $index + 1,
                        'weight_kg' => $set['weight_kg'],
                        'reps' => $set['reps']
                    ]);

                    $totalSessionVolume += ($set['weight_kg'] * $set['reps']);
                }
            }

            // ======================================================
            // 4. LOGIKA STREAK HARIAN (Dipertahankan dari Legacy)
            // ======================================================
            $lastSession = WorkoutSession::where('user_id', $user->id)
                ->where('id', '!=', $session->id)
                ->orderBy('start_time', 'desc')
                ->first();

            $currentDate = $start->copy()->startOfDay();

            if ($lastSession) {
                $lastDate = Carbon::parse($lastSession->start_time)->startOfDay();
                $diffInDays = $lastDate->diffInDays($currentDate);

                if ($diffInDays == 1) {
                    $user->current_streak += 1;
                } elseif ($diffInDays > 1) {
                    $user->current_streak = 1;
                }
            } else {
                $user->current_streak = 1;
            }

            // ======================================================
            // 5. KALKULASI XP & AI RECOMMENDATION
            // ======================================================
            // Bonus XP berdasarkan durasi, volume, dan konsistensi RPE
            $baseXp = 50; 
            $volumeBonus = floor($totalSessionVolume / 100); // 1 XP tiap 100kg
            $sessionXp = $baseXp + $volumeBonus;

            $aiMessage = "Good job!";
            if ($validated['rpe_score'] <= 6) {
                $aiMessage = "RPE kamu cukup rendah ({$validated['rpe_score']}/10). Sesi berikutnya, cobalah naikkan beban (Progressive Overload)!";
            } elseif ($validated['rpe_score'] >= 9) {
                $aiMessage = "RPE sangat tinggi! Pastikan kamu mendapat istirahat yang cukup sebelum melatih otot ini lagi.";
            }

            // Update Sesi & User
            $session->update([
                'total_volume' => $totalSessionVolume,
                'session_xp' => $sessionXp
            ]);

            $user->total_volume += $totalSessionVolume;
            $user->xp += $sessionXp;
            $user->save();

            DB::commit();

            return response()->json([
                'message' => 'Workout logged successfully!',
                'session_id' => $session->id,
                'xp_earned' => $sessionXp,
                'ai_insight' => $aiMessage,
                'current_streak' => $user->current_streak
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to log workout', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * 2. HISTORY (Diperbarui relasinya)
     */
    public function history(Request $request)
    {
        $history = WorkoutSession::where('user_id', $request->user()->id)
            ->with(['exerciseLogs.exercise']) // Relasi baru
            ->orderBy('start_time', 'desc')
            ->paginate(10); 

        return response()->json(['status' => 'success', 'data' => $history], 200);
    }

    /**
     * 3. DETAIL SESSION
     */
    public function show(Request $request, $id)
    {
        $session = WorkoutSession::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with('exerciseLogs.exercise')
            ->first();

        if (!$session) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        return response()->json($session);
    }

    /**
     * 4. LEADERBOARD B2B2C (TERISOLASI PER GYM)
     */
    public function leaderboard(Request $request)
    {
        $user = $request->user();

        // HANYA ambil member yang gym_id nya sama dengan user yang request
        $leaders = User::where('gym_id', $user->gym_id)
            ->where('role', 'member') // Pastikan owner/admin tidak ikut masuk leaderboard
            ->select('id', 'name', 'level', 'total_volume', 'goal')
            ->orderBy('total_volume', 'desc')
            ->limit(20)
            ->get();

        return response()->json(['status' => 'success', 'data' => $leaders], 200);
    }
}