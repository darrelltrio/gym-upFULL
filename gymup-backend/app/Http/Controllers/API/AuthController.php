<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register Member (B2C)
     * Mengapa ini penting? Karena member harus terikat pada satu Gym (Multi-tenancy).
     */
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'gym_id'   => 'required|exists:gyms,id', // Harus memilih gym yang valid
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'gym_id'   => $request->gym_id,
            'role'     => 'member', // Default pendaftaran lewat API adalah member
        ]);

        // Berikan token dengan ability 'role:member'
        $token = $user->createToken('auth_token', ['role:member'])->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user
        ], 201);
    }

    /**
     * Login Universal
     * Mengapa pakai 'abilities'? Agar satu token punya label role yang tidak bisa dipalsukan.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        // Pengecekan password aman (anti-SQL Injection karena Eloquent)
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial yang diberikan salah.'],
            ]);
        }

        // Generate token dengan label role (misal: 'role:gym_owner')
        $token = $user->createToken('auth_token', ["role:{$user->role}"])->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'role'         => $user->role,
            'user'         => $user->load('gym') // Muat data gym untuk branding di frontend
        ]);
    }

    public function logout(Request $request)
    {
        // Hapus token yang sedang digunakan saat ini saja
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('gym'));
    }
}