<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Food;
use App\Models\UserNutritionLog;
use Carbon\Carbon;

class NutritionController extends Controller
{
    /**
     * Menampilkan daftar makanan (Master Data Global)
     */
    public function getFoods()
    {
        // Nantinya Super Admin yang mengisi tabel ini.
        // Kita select data yang penting saja untuk dropdown PWA.
        $foods = Food::select('id', 'name', 'calories', 'protein', 'carbs', 'fats', 'serving_size')->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $foods
        ], 200);
    }

    /**
     * Menampilkan log nutrisi member pada hari tertentu beserta total makro-nya.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Frontend bisa kirim tanggal spesifik via query parameter (?date=2025-10-10)
        // Jika tidak ada, gunakan hari ini.
        $targetDate = $request->query('date', Carbon::today()->toDateString());

        $logs = UserNutritionLog::where('user_id', $user->id)
            ->whereDate('date', $targetDate)
            ->with('food') // Load relasi detail makanannya
            ->orderBy('created_at', 'desc')
            ->get();

        // Kalkulasi Total Harian
        $dailyTotals = [
            'calories' => 0,
            'protein' => 0,
            'carbs' => 0,
            'fats' => 0,
        ];

        foreach ($logs as $log) {
            $dailyTotals['calories'] += ($log->food->calories * $log->quantity);
            $dailyTotals['protein']  += ($log->food->protein * $log->quantity);
            $dailyTotals['carbs']    += ($log->food->carbs * $log->quantity);
            $dailyTotals['fats']     += ($log->food->fats * $log->quantity);
        }

        return response()->json([
            'status' => 'success',
            'date' => $targetDate,
            'daily_totals' => $dailyTotals,
            'logs' => $logs
        ], 200);
    }

    /**
     * Member mencatat apa yang mereka makan
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'food_id' => 'required|exists:foods,id',
            'date' => 'required|date',
            'meal_type' => 'required|in:breakfast,lunch,dinner,snack',
            'quantity' => 'required|numeric|min:0.1', // Bisa input 0.5 porsi
        ]);

        $log = UserNutritionLog::create([
            'user_id' => $request->user()->id,
            'food_id' => $validated['food_id'],
            'date' => $validated['date'],
            'meal_type' => $validated['meal_type'],
            'quantity' => $validated['quantity'],
        ]);

        return response()->json([
            'message' => 'Nutrition log added successfully!',
            'data' => $log->load('food')
        ], 201);
    }

    /**
     * Menghapus log jika member salah input
     */
    public function destroy(Request $request, $id)
    {
        $log = UserNutritionLog::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$log) {
            return response()->json(['message' => 'Log not found or unauthorized.'], 404);
        }

        $log->delete();

        return response()->json(['message' => 'Log deleted successfully.'], 200);
    }
}