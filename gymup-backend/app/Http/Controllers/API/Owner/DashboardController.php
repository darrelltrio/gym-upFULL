<?php

namespace App\Http\Controllers\API\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Transaction;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * GET: Menampilkan rangkuman statistik utama untuk Gym Owner
     */
    public function index(Request $request)
    {
        $owner = $request->user();
        $gymId = $owner->gym_id;

        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        // ==========================================================
        // 1. Total Member Aktif 
        // Logika: Role 'member' dan masa aktifnya masih berlaku (di atas hari ini)
        // ==========================================================
        $activeMembers = User::where('gym_id', $gymId)
            ->where('role', 'member')
            ->whereDate('membership_expires_at', '>=', $today)
            ->count();

        // Total member keseluruhan (termasuk yang hangus) untuk melihat rasio retensi
        $totalMembers = User::where('gym_id', $gymId)
            ->where('role', 'member')
            ->count();

        // ==========================================================
        // 2. Pendapatan (Revenue)
        // ==========================================================
        $revenueToday = Transaction::where('gym_id', $gymId)
            ->whereDate('created_at', $today)
            ->sum('amount');

        $revenueMonth = Transaction::where('gym_id', $gymId)
            ->whereBetween('created_at', [$startOfMonth, Carbon::now()])
            ->sum('amount');

        // ==========================================================
        // 3. Transaksi Terakhir (Untuk Live Feed di Dashboard)
        // ==========================================================
        $recentTransactions = Transaction::where('gym_id', $gymId)
            ->with('user:id,name,member_code') // Load nama member pembayar
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'gym_name' => $owner->load('gym')->gym->name, // Menampilkan nama gym
                'members' => [
                    'active' => $activeMembers,
                    'total' => $totalMembers,
                    // Menghitung persentase member yang masih aktif
                    'retention_rate' => $totalMembers > 0 ? round(($activeMembers / $totalMembers) * 100, 1) . '%' : '0%'
                ],
                'revenue' => [
                    'today' => (int) $revenueToday,
                    'this_month' => (int) $revenueMonth,
                ],
                'recent_transactions' => $recentTransactions
            ]
        ], 200);
    }
}