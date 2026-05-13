<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Exercise;
use App\Models\Food;

class MasterDataController extends Controller
{
    // =========================================================================
    // BAGIAN 1: MASTER EXERCISES (Global, created_by = null)
    // =========================================================================

    public function getExercises()
    {
        // Hanya ambil yang sifatnya Global (Bukan custom buatan Gym Owner)
        $exercises = Exercise::whereNull('created_by')->get();
        return response()->json(['status' => 'success', 'data' => $exercises]);
    }

    public function storeExercise(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_muscle' => 'required|in:chest,back,legs,shoulders,arms,core,full_body',
            'type' => 'required|in:compound,isolation,cardio',
            'base_xp' => 'required|integer|min:5',
            'asset_url' => 'nullable|string' // Opsional: Link gambar/gif gerakan
        ]);

        // Paksa created_by menjadi null agar menjadi data Global
        $validated['created_by'] = null; 

        $exercise = Exercise::create($validated);

        return response()->json(['message' => 'Global exercise created!', 'data' => $exercise], 201);
    }

    public function updateExercise(Request $request, $id)
    {
        // Pastikan Super Admin hanya mengedit data Global, BUKAN data custom Gym Owner
        $exercise = Exercise::whereNull('created_by')->where('id', $id)->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'target_muscle' => 'sometimes|required|in:chest,back,legs,shoulders,arms,core,full_body',
            'type' => 'sometimes|required|in:compound,isolation,cardio',
            'base_xp' => 'sometimes|required|integer|min:5',
            'asset_url' => 'nullable|string'
        ]);

        $exercise->update($validated);

        return response()->json(['message' => 'Global exercise updated!', 'data' => $exercise]);
    }

    public function destroyExercise($id)
    {
        $exercise = Exercise::whereNull('created_by')->where('id', $id)->firstOrFail();
        $exercise->delete();

        return response()->json(['message' => 'Global exercise deleted!']);
    }

    // =========================================================================
    // BAGIAN 2: MASTER FOODS (Database Nutrisi)
    // =========================================================================

    public function getFoods()
    {
        $foods = Food::all();
        return response()->json(['status' => 'success', 'data' => $foods]);
    }

    public function storeFood(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'calories' => 'required|numeric|min:0',
            'protein' => 'required|numeric|min:0',
            'carbs' => 'required|numeric|min:0',
            'fats' => 'required|numeric|min:0',
            'serving_size' => 'required|string|max:100', // Contoh: "100 gram", "1 Piring"
        ]);

        // Mapping manual agar 'fats' masuk ke kolom 'fat'
        $food = Food::create([
            'name' => $validated['name'],
            'calories' => $validated['calories'],
            'protein' => $validated['protein'],
            'carbs' => $validated['carbs'],
            'fat' => $validated['fats'], // <--- INI KUNCI PERBAIKANNYA
            'serving_size' => $validated['serving_size'],
        ]);

        $food = Food::create($validated);

        return response()->json(['message' => 'Food added to database!', 'data' => $food], 201);
    }

    public function updateFood(Request $request, $id)
    {
        $food = Food::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'calories' => 'sometimes|required|numeric|min:0',
            'protein' => 'sometimes|required|numeric|min:0',
            'carbs' => 'sometimes|required|numeric|min:0',
            'fats' => 'sometimes|required|numeric|min:0',
            'serving_size' => 'sometimes|required|string|max:100',
        ]);

        $food->update([
            'name' => $validated['name'] ?? $food->name,
            'calories' => $validated['calories'] ?? $food->calories,
            'protein' => $validated['protein'] ?? $food->protein,
            'carbs' => $validated['carbs'] ?? $food->carbs,
            'fat' => $validated['fats'] ?? $food->fat,
            'serving_size' => $validated['serving_size'] ?? $food->serving_size,
        ]);

        

        $food->update($validated);

        return response()->json(['message' => 'Food updated!', 'data' => $food]);
    }

    public function destroyFood($id)
    {
        $food = Food::findOrFail($id);
        $food->delete();

        return response()->json(['message' => 'Food deleted!']);
    }
}