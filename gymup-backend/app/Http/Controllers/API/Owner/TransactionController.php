<?php

namespace App\Http\Controllers\API\Owner;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransactionController extends Controller
{
    /**
     * GET: Melihat riwayat transaksi (Kasir) di Gym ini
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Hanya ambil transaksi milik gym ini, urutkan dari yang terbaru
        $transactions = Transaction::where('gym_id', $user->gym_id)
            ->with('user:id,name,member_code') // Load nama member jika ada
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $transactions
        ]);
    }

    /**
     * POST: Mencatat transaksi baru & Otomatis perpanjang membership
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // user_id bisa null jika yang beli bukan member (misal tamu beli air minum)
            'user_id' => 'nullable|exists:users,id', 
            'type' => 'required|in:membership,drop_in,product,other',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
            // Jika type-nya membership, wajib kirim berapa bulan perpanjangannya
            'duration_months' => 'required_if:type,membership|integer|min:1', 
        ]);

        $owner = $request->user();

        DB::beginTransaction();

        try {
            // 1. Buat Kode Referensi Unik (Contoh: TRX-GYM1-1701234567)
            $referenceNumber = 'TRX-GYM' . $owner->gym_id . '-' . time();

            // 2. Catat Uang Masuk ke tabel transactions
            $transaction = Transaction::create([
                'gym_id' => $owner->gym_id,
                'user_id' => $validated['user_id'] ?? null,
                'reference_number' => $referenceNumber,
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? 'Pembayaran ' . $validated['type'],
            ]);

            // ======================================================
            // 3. LOGIKA PERPANJANGAN MEMBERSHIP OTOMATIS
            // ======================================================
            if ($validated['type'] === 'membership' && !empty($validated['user_id'])) {
                // Pastikan member yang diperpanjang benar-benar terdaftar di gym ini
                $member = User::where('id', $validated['user_id'])
                    ->where('gym_id', $owner->gym_id)
                    ->where('role', 'member')
                    ->firstOrFail();

                // Cek masa aktif saat ini
                $currentExpiry = $member->membership_expires_at ? Carbon::parse($member->membership_expires_at) : Carbon::now();

                // Jika sudah hangus (past), mulai hitung dari hari ini.
                // Jika masih aktif (future), tambahkan dari sisa harinya.
                if ($currentExpiry->isPast()) {
                    $currentExpiry = Carbon::now();
                }

                // Tambahkan bulan
                $member->membership_expires_at = $currentExpiry->addMonths($validated['duration_months']);
                $member->save();

                $transaction->description .= " (" . $validated['duration_months'] . " Bulan)";
                $transaction->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Transaction recorded successfully!',
                'data' => $transaction->load('user:id,name,membership_expires_at')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Transaction failed', 'error' => $e->getMessage()], 500);
        }
    }
}