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
                'subscription_ends_at' => Carbon::now()->addMonths((int)$validated['subscription_months'])
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
    /**
     * PUT: Mengedit Data Gym (Contoh: Ganti Nama)
     */
   public function update(Request $request, $id)
{
    // Ambil gym beserta owner-nya
    $gym = Gym::with(['users' => function($q) {
        $q->where('role', 'gym_owner');
    }])->findOrFail($id);
    
    $owner = $gym->users->first();

    $validated = $request->validate([
        'name' => 'sometimes|required|string|max:255',
        'address' => 'sometimes|required|string',
        'owner_name' => 'sometimes|required|string|max:255',
        'email' => 'sometimes|required|email|unique:users,email,' . ($owner ? $owner->id : 0),
        'password' => 'nullable|string|min:8',
        'status' => 'sometimes|required|in:active,inactive',
    ]);

    // 1. Update Data Gym
    $gym->update([
        'name' => $validated['name'] ?? $gym->name,
        'address' => $validated['address'] ?? $gym->address,
        'owner_name' => $validated['owner_name'] ?? $gym->owner_name,
        'status' => $validated['status'] ?? $gym->status,
    ]);

    // 2. Update Data Owner (User)
    if ($owner) {
        $userData = [
            'name' => $validated['owner_name'] ?? $owner->name,
            'email' => $validated['email'] ?? $owner->email,
        ];
        
        if (!empty($validated['password'])) {
            $userData['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        }
        
        $owner->update($userData);
    }

    return response()->json(['message' => 'Gym and Owner updated successfully!']);
}

    /**
     * DELETE: Menghapus Gym
     */
    public function destroy($id)
    {
        $gym = Gym::findOrFail($id);
        
        // Catatan: Karena kita sudah set 'onDelete cascade' di migration,
        // menghapus Gym otomatis akan menghapus User (Owner & Member) di dalamnya.
        $gym->delete();

        return response()->json(['message' => 'Gym deleted successfully!']);
    }
}