<?php

namespace App\Http\Controllers\API\Owner;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Models\GymExerciseInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExerciseController extends Controller
{
    /**
     * GET: Menampilkan Katalog Master (Global + Custom milik Gym ini)
     * Sekaligus mengecek apakah latihan tersebut 'aktif' di inventory gym ini.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $gymId = $user->gym_id;

        // Ambil latihan Global (NULL) ATAU yang dibuat oleh Owner ini
        $exercises = Exercise::whereNull('created_by')
            ->orWhere('created_by', $user->id)
            ->get();

        // Ambil status inventory khusus gym ini
        $inventory = GymExerciseInventory::where('gym_id', $gymId)
            ->pluck('is_active', 'exercise_id');

        // Gabungkan data agar Frontend mudah merender tombol "Toggle On/Off"
        $data = $exercises->map(function ($exercise) use ($inventory) {
            return [
                'id' => $exercise->id,
                'name' => $exercise->name,
                'target_muscle' => $exercise->target_muscle,
                'type' => $exercise->type,
                'base_xp' => $exercise->base_xp,
                'is_custom' => $exercise->created_by !== null, // True jika buatan Owner
                // Jika ada di inventory ambil nilainya, jika tidak default false
                'is_active' => $inventory->get($exercise->id, false), 
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    /**
     * POST: Toggle On/Off Alat di Gym (Inventory Management)
     * Ini dipanggil saat Owner menekan tombol "Aktifkan" pada sebuah gerakan.
     */
    public function toggleInventory(Request $request)
    {
        $validated = $request->validate([
            'exercise_id' => 'required|exists:exercises,id',
            'is_active' => 'required|boolean',
        ]);

        $user = $request->user();

        // Update atau Create data di tabel Pivot
        $inventory = GymExerciseInventory::updateOrCreate(
            ['gym_id' => $user->gym_id, 'exercise_id' => $validated['exercise_id']],
            ['is_active' => $validated['is_active']]
        );

        $statusText = $validated['is_active'] ? 'activated' : 'deactivated';

        return response()->json([
            'message' => "Exercise successfully {$statusText} for your gym.",
            'data' => $inventory
        ]);
    }

    /**
     * POST: Membuat Custom Exercise (Hanya untuk Gym ini)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_muscle' => 'required|in:chest,back,legs,shoulders,arms,core,full_body',
            'type' => 'required|in:compound,isolation,cardio',
            'base_xp' => 'required|integer|min:5',
        ]);

        $user = $request->user();

        DB::beginTransaction();

        try {
            // 1. Buat Latihannya (Tandai dengan ID Owner agar tidak bocor ke gym lain)
            $exercise = Exercise::create([
                'name' => $validated['name'],
                'target_muscle' => $validated['target_muscle'],
                'type' => $validated['type'],
                'base_xp' => $validated['base_xp'],
                'created_by' => $user->id, 
            ]);

            // 2. Otomatis aktifkan di inventory gym ini
            GymExerciseInventory::create([
                'gym_id' => $user->gym_id,
                'exercise_id' => $exercise->id,
                'is_active' => true,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Custom exercise created and activated successfully!',
                'data' => $exercise
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create exercise', 'error' => $e->getMessage()], 500);
        }
    }
}