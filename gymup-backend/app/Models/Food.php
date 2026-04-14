<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Food extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'calories', 'protein', 'carbs', 'fats', 'serving_size'
    ];

    public function nutritionLogs(): HasMany
    {
        return $this->hasMany(UserNutritionLog::class, 'food_id');
    }
}