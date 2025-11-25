<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use Illuminate\Http\Request;

class ExerciseController extends Controller
{
    // GET /api/exercises
    public function index()
    {
        $exercises = Exercise::all();
        return response()->json($exercises);
    }

    // POST /api/exercises (BARU)
    public function store(Request $request)
    {
        // 1. Validasi Input
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:exercises', // Nama harus unik
            'muscle_group' => 'required|string|max:100',
            'equipment' => 'required|string|max:100',
        ]);

        // 2. Simpan ke Database
        $exercise = Exercise::create([
            'name' => $validatedData['name'],
            'muscle_group' => $validatedData['muscle_group'],
            'equipment' => $validatedData['equipment'],
        ]);

        // 3. Kembalikan data latihan baru (untuk update UI frontend)
        return response()->json([
            'message' => 'Exercise created successfully',
            'data' => $exercise
        ], 201);
    }
}