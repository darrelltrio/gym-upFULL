<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use Illuminate\Http\Request;

class ExerciseController extends Controller
{
    // GET: Ambil semua latihan
    public function index()
    {
        return response()->json(Exercise::all());
    }

    // POST: Tambah latihan baru
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:exercises',
            'muscle_group' => 'required|string|max:100',
            'equipment' => 'required|string|max:100',
        ]);

        $exercise = Exercise::create($validatedData);

        return response()->json([
            'message' => 'Exercise created successfully',
            'data' => $exercise
        ], 201);
    }

    // PUT: Update latihan (BARU)
    public function update(Request $request, $id)
    {
        $exercise = Exercise::find($id);

        if (!$exercise) {
            return response()->json(['message' => 'Exercise not found'], 404);
        }

        // Validasi: Nama unik, tapi boleh sama dengan namanya sendiri saat ini
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:exercises,name,' . $id . ',exercise_id',
            'muscle_group' => 'sometimes|required|string|max:100',
            'equipment' => 'sometimes|required|string|max:100',
        ]);

        $exercise->update($validatedData);

        return response()->json([
            'message' => 'Exercise updated successfully',
            'data' => $exercise
        ]);
    }

    // DELETE: Hapus latihan (BARU)
    public function destroy($id)
    {
        $exercise = Exercise::find($id);
        if (!$exercise) {
            return response()->json(['message' => 'Exercise not found'], 404);
        }
        $exercise->delete();
        return response()->json(['message' => 'Exercise deleted successfully']);
    }
}