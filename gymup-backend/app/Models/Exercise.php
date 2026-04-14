<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'target_muscle',
        'type',
        'base_xp',
        'asset_url',
        'created_by'
    ];

    /**
     * Relasi ke User (Gym Owner) yang membuat latihan ini.
     * Menggunakan nama fungsi 'creator' agar lebih semantik, 
     * lalu kita beri tahu Laravel bahwa foreign key-nya adalah 'created_by'.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function exerciseLogs(): HasMany
    {
        return $this->hasMany(ExerciseLog::class);
    }
}