<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // 1. REGISTER USER BARU
    public function register(Request $request)
    {
        // 1. Validasi input diperlengkap
        $validatedData = $request->validate([
            'username' => 'required|string|max:100',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            
            // Tambahan Data Fisik (Wajib diisi saat register)
            'goal' => 'required|in:bulk,cut,maintain',
            'gender' => 'required|in:male,female',
            'age' => 'required|integer|min:10|max:100',
            'height_cm' => 'required|integer|min:100|max:250',
            'weight_kg' => 'required|numeric|min:30|max:300',
            'activity_level' => 'required|in:sedentary,light,moderate,active,very_active',
        ]);

        // 2. Simpan User dengan data lengkap
        $user = User::create([
            'username' => $validatedData['username'],
            'email' => $validatedData['email'],
            'password' => $validatedData['password'], // Biarkan Model yang bekerja,
            
            // Masukkan data fisik ke kolom tabel users
            'goal' => $validatedData['goal'],
            'gender' => $validatedData['gender'],
            'age' => $validatedData['age'],
            'height_cm' => $validatedData['height_cm'],
            'weight_kg' => $validatedData['weight_kg'],
            'activity_level' => $validatedData['activity_level'],
            
            // Default nilai awal gamifikasi
            'level' => 1,
            'xp' => 0,
            'rank_points' => 0,
            'current_streak' => 0,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    // 2. LOGIN USER
    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login details'
            ], 401);
        }

        $user = User::where('email', $request['email'])->firstOrFail();

        // Buat Token Baru
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    // 3. LOGOUT (Hapus Token)
    public function logout(Request $request)
    {
        // Hapus token yang sedang dipakai saja
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    // 4. CEK USER PROFILE (Siapa saya?)
    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}