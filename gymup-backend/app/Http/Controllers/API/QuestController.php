<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
// Pastikan Model Quest dan UserQuest ada, atau kita gunakan DB Facade saja biar cepat
// Asumsi tabel: 'quests' dan 'user_quests'

class QuestController extends Controller
{
    /**
     * 1. DAFTAR QUEST (Index)
     * - Otomatis assign quest harian jika belum ada.
     * - Otomatis update progress berdasarkan data latihan terbaru.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // A. SYNC QUEST HARIAN (Reset tiap hari)
        $this->assignDailyQuests($user->user_id);
        
        // B. SYNC QUEST ACHIEVEMENT (Sekali seumur hidup)
        $this->assignAchievementQuests($user->user_id);

        // C. UPDATE PROGRESS OTOMATIS
        // Kita hitung progress 'real-time' sebelum ditampilkan ke user
        $this->updateQuestProgress($user);

        // D. AMBIL DATA UNTUK DITAMPILKAN
        $myQuests = DB::table('user_quests')
            ->join('quests', 'user_quests.quest_id', '=', 'quests.quest_id')
            ->where('user_quests.user_id', $user->user_id)
            ->where('user_quests.status', '!=', 'claimed') // Yang sudah diklaim sembunyikan (opsional)
            ->select(
                'user_quests.user_quest_id',
                'user_quests.status',
                'user_quests.current_progress',
                'quests.title',
                'quests.description',
                'quests.target_value',
                'quests.reward_xp',
                'quests.reward_rank_points',
                'quests.type'
            )
            ->orderByRaw("FIELD(user_quests.status, 'completed', 'in_progress', 'claimed')")
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $myQuests
        ]);
    }

    /**
     * 2. KLAIM REWARD
     */
    public function claim(Request $request, $userQuestId)
    {
        $user = $request->user();

        // Cek kepemilikan quest
        $userQuest = DB::table('user_quests')
            ->join('quests', 'user_quests.quest_id', '=', 'quests.quest_id')
            ->where('user_quest_id', $userQuestId)
            ->where('user_id', $user->user_id)
            ->first();

        if (!$userQuest) {
            return response()->json(['message' => 'Quest not found'], 404);
        }

        // Cek status
        if ($userQuest->status === 'claimed') {
            return response()->json(['message' => 'Already claimed'], 400);
        }
        if ($userQuest->status !== 'completed' && $userQuest->current_progress < $userQuest->target_value) {
            return response()->json(['message' => 'Quest not completed yet'], 400);
        }

        DB::beginTransaction();
        try {
            // 1. Update Status jadi 'claimed'
            DB::table('user_quests')
                ->where('user_quest_id', $userQuestId)
                ->update(['status' => 'claimed', 'updated_at' => now()]);

            // 2. Tambah XP dan Rank Points ke User
            $user->xp += $userQuest->reward_xp;
            $user->rank_points += $userQuest->reward_rank_points;

            // 3. LOGIKA LEVEL UP
            // Rumus Level: Setiap 1000 XP naik 1 level (Contoh sederhana)
            // Level 1: 0-999, Level 2: 1000-1999, dst.
            $newLevel = floor($user->xp / 1000) + 1;
            $leveledUp = false;

            if ($newLevel > $user->level) {
                $user->level = $newLevel;
                $leveledUp = true;
            }

            $user->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Reward claimed!',
                'leveled_up' => $leveledUp,
                'new_level' => $user->level,
                'new_rank_points' => $user->rank_points,
                'new_xp' => $user->xp,
                'reward_xp' => $userQuest->reward_xp,
                'reward_rank_points' => $userQuest->reward_rank_points
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error claiming quest'], 500);
        }
    }

    // ==========================================
    // HELPER FUNCTIONS (Logika "Pintar")
    // ==========================================

    private function assignDailyQuests($userId)
    {
        $today = Carbon::today();
        $dailyLimit = 3; // LIMIT: Hanya munculkan 3 quest per hari

        // 1. Cek dulu: Apakah user SUDAH punya quest daily HARI INI?
        // Kita join ke tabel quests untuk memastikan type-nya 'daily'
        $existingDailyCount = DB::table('user_quests')
            ->join('quests', 'user_quests.quest_id', '=', 'quests.quest_id')
            ->where('user_quests.user_id', $userId)
            ->where('quests.type', 'daily')
            ->whereDate('user_quests.created_at', $today)
            ->count();

        // 2. Jika belum ada (atau kurang dari limit), baru kita ambil dari "Bank Soal"
        if ($existingDailyCount < $dailyLimit) {
            
            // Logika Rolling: Ambil quest daily secara ACAK dari database
            $randomQuests = DB::table('quests')
                ->where('type', 'daily')
                ->inRandomOrder() // <--- INI KUNCINYA: Mengacak urutan
                ->take($dailyLimit - $existingDailyCount) // Ambil 3 (atau kekurangannya)
                ->get();

            foreach ($randomQuests as $quest) {
                // Pastikan tidak duplikat (safety check)
                $alreadyAssigned = DB::table('user_quests')
                    ->where('user_id', $userId)
                    ->where('quest_id', $quest->quest_id)
                    ->whereDate('created_at', $today)
                    ->exists();

                if (!$alreadyAssigned) {
                    DB::table('user_quests')->insert([
                        'user_id' => $userId,
                        'quest_id' => $quest->quest_id,
                        'current_progress' => 0,
                        'status' => 'in_progress',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }
    }

    private function assignAchievementQuests($userId)
    {
        $achievements = DB::table('quests')->where('type', 'achievement')->get();
        foreach ($achievements as $quest) {
            $exists = DB::table('user_quests')
                ->where('user_id', $userId)
                ->where('quest_id', $quest->quest_id)
                ->exists();

            if (!$exists) {
                DB::table('user_quests')->insert([
                    'user_id' => $userId,
                    'quest_id' => $quest->quest_id,
                    'current_progress' => 0,
                    'status' => 'in_progress',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }

    private function updateQuestProgress($user)
    {
        // Ambil semua quest user yang masih 'in_progress'
        $activeQuests = DB::table('user_quests')
            ->join('quests', 'user_quests.quest_id', '=', 'quests.quest_id')
            ->where('user_quests.user_id', $user->user_id)
            ->where('user_quests.status', 'in_progress')
            ->select('user_quests.*', 'quests.metric', 'quests.target_value', 'quests.type')
            ->get();

        foreach ($activeQuests as $uq) {
            $progress = 0;

            // HITUNG PROGRESS BERDASARKAN METRIC
            if ($uq->type === 'daily') {
                // Filter data HARI INI
                if ($uq->metric === 'workout_sessions') {
                    $progress = DB::table('workout_sessions')
                        ->where('user_id', $user->user_id)
                        ->whereDate('session_date', Carbon::today())
                        ->count();
                } elseif ($uq->metric === 'total_volume') {
                    // Query join yang agak kompleks untuk hitung volume hari ini
                    $progress = DB::table('workout_sessions')
                        ->join('exercise_logs', 'workout_sessions.session_id', '=', 'exercise_logs.session_id')
                        ->where('workout_sessions.user_id', $user->user_id)
                        ->whereDate('workout_sessions.session_date', Carbon::today())
                        ->sum(DB::raw('exercise_logs.weight_kg * exercise_logs.reps'));
                }
            } elseif ($uq->type === 'achievement') {
                // Filter data SEUMUR HIDUP
                if ($uq->metric === 'workout_sessions') {
                    $progress = $user->workouts_completed; // Pakai Accessor User yang tadi kita buat!
                } elseif ($uq->metric === 'streak_days') {
                    $progress = $user->current_streak;
                }
            }

            // UPDATE DB JIKA PROGRESS BERUBAH
            if ($progress != $uq->current_progress) {
                $status = ($progress >= $uq->target_value) ? 'completed' : 'in_progress';
                
                DB::table('user_quests')
                    ->where('user_quest_id', $uq->user_quest_id)
                    ->update([
                        'current_progress' => $progress,
                        'status' => $status,
                        'updated_at' => now()
                    ]);
            }
        }
    }
}