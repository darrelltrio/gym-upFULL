<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Food; // Pastikan Model Food ada (akan kita bahas di bawah)

class NutritionController extends Controller
{
    public function getRecommendations(Request $request)
    {
        $user = $request->user();

        // 1. Hitung BMR (Basal Metabolic Rate) - Rumus Mifflin-St Jeor
        // Pria: (10 × weight) + (6.25 × height) - (5 × age) + 5
        // Wanita: (10 × weight) + (6.25 × height) - (5 × age) - 161
        
        $weight = $user->weight_kg;
        $height = $user->height_cm;
        $age = $user->age;
        
        $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age);
        
        if ($user->gender === 'male') {
            $bmr += 5;
        } else {
            $bmr -= 161;
        }

        // 2. Hitung TDEE berdasarkan Activity Level
        $activityMultipliers = [
            'sedentary' => 1.2,
            'light' => 1.375,
            'moderate' => 1.55,
            'active' => 1.725,
            'very_active' => 1.9,
        ];
        
        $tdee = $bmr * ($activityMultipliers[$user->activity_level] ?? 1.2);

        // 3. Sesuaikan dengan GOAL (Bulk/Cut/Maintain)
        $targetCalories = $tdee;
        $phase = "MAINTENANCE PHASE";
        
        if ($user->goal === 'bulk') {
            $targetCalories += 500; // Surplus
            $phase = "BULKING PHASE";
        } elseif ($user->goal === 'cut') {
            $targetCalories -= 500; // Defisit
            $phase = "CUTTING PHASE";
        }

        // 4. Hitung Macros (Sederhana)
        // Protein: 2g per kg berat badan (cukup standar untuk gym)
        // Lemak: 1g per kg berat badan
        // Karbo: Sisa kalori
        
        $proteinGrams = $weight * 2.0; 
        $fatGrams = $weight * 1.0;
        
        // 1g Protein = 4 cal, 1g Fat = 9 cal
        $caloriesFromProtein = $proteinGrams * 4;
        $caloriesFromFat = $fatGrams * 9;
        
        $remainingCalories = $targetCalories - ($caloriesFromProtein + $caloriesFromFat);
        
        // 1g Carbs = 4 cal
        $carbsGrams = max(0, $remainingCalories / 4); // Pastikan tidak negatif

        return response()->json([
            'goal_status' => "You're on",
            'phase' => $phase,
            'calories' => round($targetCalories),
            'macros' => [
                'protein' => round($proteinGrams),
                'fat' => round($fatGrams),
                'carbs' => round($carbsGrams),
            ]
        ]);
    }

    public function getMealIdeas(Request $request)
{
    $user = $request->user(); // Ambil user yang sedang login
    $goal = $user->goal;      // bulk, cut, atau maintain

    // Base query
    $query = \App\Models\Food::query();

    // Logika Pemilihan Makanan Berdasarkan Goal
    switch ($goal) {
        case 'bulk':
            // BULKING: Cari makanan yang tinggi kalori & karbo untuk surplus energi
            // Urutkan dari kalori terbesar ke terkecil
            $query->orderBy('calories', 'desc');
            break;

        case 'cut':
            // CUTTING: Cari makanan yang mengenyangkan tapi rendah kalori (High Protein, Low Cal)
            // Urutkan dari kalori terkecil ke terbesar
            // Opsional: Bisa tambahkan where('calories', '<', 500)
            $query->orderBy('calories', 'asc')
                  ->orderBy('protein_g', 'desc'); 
            break;

        case 'maintain':
        default:
            // MAINTAIN: Campuran seimbang
            // Kita acak agar tidak bosan
            $query->inRandomOrder();
            break;
    }

    // Ambil misal 6 rekomendasi teratas agar tidak terlalu banyak
    $meals = $query->limit(6)->get();

    return response()->json($meals);
}
}