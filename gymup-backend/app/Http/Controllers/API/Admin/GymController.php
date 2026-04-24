<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Gym;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class GymController extends Controller
{
    /**
     * GET: Melihat semua Gym yang berlangganan aplikasi kita
     */
    public function index()
    {
        // Load data gym beserta data owner-nya (user yang role-nya gym_owner di gym tersebut)
        $gyms = Gym::with(['users' => function($query) {
            $query->where('role', 'gym_owner');
        }])->get();

        return response()->json([
            'status' => 'success',
            'data' => $gyms
        ]);
    }

    /**
     * POST: Mendaftarkan Gym Baru & Membuat Akun Gym Owner
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Data Gym
            'gym_name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'address' => 'required|string',
            'subscription_months' => 'required|integer|min:1',
            
            // Data Akun Login Owner
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:8',
        ]);

        DB::beginTransaction();

        try {
            // 1. Buat Data Gym
            $gym = Gym::create([
                'name' => $validated['gym_name'],
                'owner_name' => $validated['owner_name'],
                'address' => $validated['address'],
                'status' => 'active',
                'subscription_ends_at' => Carbon::now()->addMonths($validated['subscription_months'])
            ]);

            // 2. Buat Akun User untuk si Owner
            $owner = User::create([
                'name' => $validated['owner_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'gym_id' => $gym->id, // Ikat owner ini ke gym yang baru dibuat
                'role' => 'gym_owner', // KUNCI UTAMA: Role-nya adalah Owner
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Gym and Owner account created successfully!',
                'data' => [
                    'gym' => $gym,
                    'owner_account' => $owner
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to onboard Gym', 'error' => $e->getMessage()], 500);
        }
    }
}