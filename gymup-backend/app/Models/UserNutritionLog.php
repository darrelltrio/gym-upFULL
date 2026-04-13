<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNutritionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'food_id',
        'date',
        'meal_type',
        'quantity'
    ];

    // Ubah kolom date menjadi format tanggal Carbon agar mudah di-filter
    protected $casts = [
        'date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class);
    }
}