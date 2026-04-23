<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Quest;
use App\Models\UserQuest;
use App\Models\WorkoutSession;
use Illuminate\Support\Facades\DB;

class QuestController extends Controller
{
    /**
     * Menampilkan daftar Quest (Global + Khusus Gym ini) beserta progres member.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // 1. Ambil semua quest yang valid untuk member ini (Global/NULL ATAU milik Gym-nya)
        $availableQuests = Quest::whereNull('gym_id')
            ->orWhere('gym_id', $user->gym_id)
            ->get();

        $responseQuests = [];

        foreach ($availableQuests as $quest) {
            // Cek apakah user sudah mulai mengerjakan quest ini
            $userQuest = UserQuest::firstOrCreate(
                ['user_id' => $user->id, 'quest_id' => $quest->id],
                ['current_progress' => 0, 'status' => 'in_progress']
            );

            // ======================================================
            // LOGIKA AUTO-UPDATE PROGRESS (Penting!)
            // Di sini kita cek database secara real-time untuk memastikan
            // progress bar quest selalu up-to-date saat member membuka halaman Quests.
            // ======================================================
            if ($userQuest->status === 'in_progress') {
                $progressLama = $userQuest->current_progress;
                $progressBaru = 0;

                if ($quest->requirement_type === 'volume') {
                    // Cek total volume terbaru
                    $progressBaru = $user->total_volume;
                } elseif ($quest->requirement_type === 'streak') {
                    // Cek streak harian terbaru
                    $progressBaru = $user->current_streak;
                } elseif ($quest->requirement_type === 'workouts_count') {
                    // Cek total sesi latihan terbaru
                    $progressBaru = WorkoutSession::where('user_id', $user->id)->count();
                }

                // Jika ada peningkatan, update database
                if ($progressBaru > $progressLama) {
                    $userQuest->current_progress = $progressBaru;
                    
                    // Cek apakah quest sudah selesai
                    if ($progressBaru >= $quest->target_value) {
                        $userQuest->status = 'completed';
                    }
                    $userQuest->save();
                }
            }

            $responseQuests[] = [
                'id' => $userQuest->id, // Kirim ID pivot untuk mempermudah API Claim nanti
                'quest_id' => $quest->id,
                'title' => $quest->title,
                'description' => $quest->description,
                'reward_xp' => $quest->xp_reward,
                'target_value' => $quest->target_value,
                'current_progress' => $userQuest->current_progress,
                'status' => $userQuest->status, // in_progress, completed, atau claimed
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $responseQuests
        ], 200);
    }

    /**
     * Mengklaim XP dari Quest yang sudah selesai ('completed').
     */
    public function claim(Request $request, $userQuestId)
    {
        $user = $request->user();

        DB::beginTransaction();

        try {
            $userQuest = UserQuest::where('id', $userQuestId)
                ->where('user_id', $user->id)
                ->with('quest')
                ->first();

            if (!$userQuest) {
                return response()->json(['message' => 'Quest not found.'], 404);
            }

            if ($userQuest->status === 'in_progress') {
                return response()->json(['message' => 'Quest not completed yet!'], 400);
            }

            if ($userQuest->status === 'claimed') {
                return response()->json(['message' => 'Reward already claimed!'], 400);
            }

            // Tambahkan XP ke User
            $user->xp += $userQuest->quest->xp_reward;
            $user->save();

            // Ubah status quest menjadi claimed
            $userQuest->status = 'claimed';
            $userQuest->save();

            DB::commit();

            return response()->json([
                'message' => 'Reward claimed successfully!',
                'xp_earned' => $userQuest->quest->xp_reward,
                'new_total_xp' => $user->xp
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to claim reward', 'error' => $e->getMessage()], 500);
        }
    }
}