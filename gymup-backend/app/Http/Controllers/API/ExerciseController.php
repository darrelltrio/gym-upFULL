<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use Illuminate\Http\Request;

class ExerciseController extends Controller
{
    // GET: Tampilkan Katalog (Global + Punya Saya)
    public function index(Request $request)
    {
        // Ambil User ID dari Token yang login
        $userId = $request->user()->user_id;

        // Logika: Ambil yang Global (NULL) ATAU punya user ini
        $exercises = Exercise::whereNull('created_by')
                    ->orWhere('created_by', $userId)
                    ->get();

        return response()->json($exercises);
    }

    // POST: Tambah Latihan Baru (Pasti punya Saya)
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            // Validasi nama unik hanya untuk latihan milik user ini (agar tidak bentrok dengan global)
            'name' => 'required|string|max:255', 
            'muscle_group' => 'required|string|max:100',
            'equipment' => 'required|string|max:100',
        ]);

        // Simpan dengan ID pemilik
        $exercise = Exercise::create([
            'name' => $validatedData['name'],
            'muscle_group' => $validatedData['muscle_group'],
            'equipment' => $validatedData['equipment'],
            'created_by' => $request->user()->user_id // <--- KUNCI PRIVASI
        ]);

        return response()->json([
            'message' => 'Exercise created successfully',
            'data' => $exercise
        ], 201);
    }

    // PUT: Edit Latihan (Hanya milik sendiri)
    public function update(Request $request, $id)
    {
        $exercise = Exercise::find($id);

        if (!$exercise) {
            return response()->json(['message' => 'Exercise not found'], 404);
        }

        // CEK KEPEMILIKAN: Jangan izinkan edit jika Global atau punya orang lain
        if ($exercise->created_by !== $request->user()->user_id) {
            return response()->json(['message' => 'Unauthorized: You cannot edit global exercises.'], 403);
        }

        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'muscle_group' => 'sometimes|required|string|max:100',
            'equipment' => 'sometimes|required|string|max:100',
        ]);

        $exercise->update($validatedData);

        return response()->json([
            'message' => 'Exercise updated successfully',
            'data' => $exercise
        ]);
    }

    // DELETE: Hapus Latihan (Hanya milik sendiri)
    public function destroy(Request $request, $id)
    {
        $exercise = Exercise::find($id);

        if (!$exercise) {
            return response()->json(['message' => 'Exercise not found'], 404);
        }

        // CEK KEPEMILIKAN: Jangan izinkan hapus jika Global
        if ($exercise->created_by !== $request->user()->user_id) {
            return response()->json(['message' => 'Unauthorized: You cannot delete global exercises.'], 403);
        }

        $exercise->delete();

        return response()->json(['message' => 'Exercise deleted successfully']);
    }
}