<?php

namespace App\Http\Controllers\API\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class MemberController extends Controller
{
    /**
     * GET: Menampilkan daftar seluruh member di gym ini
     */
    public function index(Request $request)
    {
        $owner = $request->user();

        // Isolasi data: Hanya ambil member yang terdaftar di gym_id milik Owner ini
        $members = User::where('gym_id', $owner->gym_id)
            ->where('role', 'member')
            ->select('id', 'name', 'email', 'member_code', 'membership_expires_at', 'current_streak', 'level')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $members
        ], 200);
    }

    /**
     * POST: Resepsionis/Owner mendaftarkan member baru secara manual
     */
    public function store(Request $request)
    {
        $owner = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            // Opsional: Jika saat daftar langsung bayar untuk X bulan
            'duration_months' => 'nullable|integer|min:1' 
        ]);

        // ==========================================================
        // 1. Generate Member Code Unik (Contoh: G1-M-A8F92)
        // ==========================================================
        $randomString = strtoupper(substr(uniqid(), -5));
        $memberCode = 'G' . $owner->gym_id . '-M-' . $randomString;

        // ==========================================================
        // 2. Kalkulasi Masa Aktif (Jika langsung bayar)
        // ==========================================================
        $expiresAt = null;
        if (!empty($validated['duration_months'])) {
            $expiresAt = Carbon::now()->addMonths($validated['duration_months']);
        }

        $member = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'gym_id' => $owner->gym_id,
            'role' => 'member',
            'member_code' => $memberCode,
            'membership_expires_at' => $expiresAt,
        ]);

        return response()->json([
            'message' => 'Member successfully registered!',
            'data' => $member
        ], 201);
    }

    /**
     * GET: Melihat detail profil satu member spesifik
     */
    public function show(Request $request, $id)
    {
        $owner = $request->user();

        // findOrFail yang aman (pastikan member ini benar-benar milik gym si Owner)
        $member = User::where('gym_id', $owner->gym_id)
            ->where('role', 'member')
            ->where('id', $id)
            ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => $member
        ], 200);
    }
}