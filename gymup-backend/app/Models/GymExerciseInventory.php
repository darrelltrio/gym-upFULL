<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

// KUNCI PENTING: Extends Pivot, BUKAN Model.
class GymExerciseInventory extends Pivot
{
    // Beritahu Laravel secara eksplisit nama tabel pivot ini
    protected $table = 'gym_exercise_inventory';

    // Kolom apa saja yang boleh diisi
    protected $fillable = [
        'gym_id',
        'exercise_id',
        'is_active',
    ];

    // Pastikan 'is_active' dibaca sebagai boolean oleh PHP
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}