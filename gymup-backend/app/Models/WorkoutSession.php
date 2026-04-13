<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'start_time',
        'end_time',
        'duration_minutes',
        'total_volume',
        'session_xp',
        'rpe_score' // Fitur baru kita!
    ];

    // Karena kolom kita namanya start_time dan end_time (bukan default Laravel),
    // kita beri tahu Laravel untuk memperlakukannya sebagai objek Carbon (Tanggal/Waktu)
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Relasi: Sesi latihan ini MILIK satu User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi: Satu Sesi Latihan MEMILIKI BANYAK Log (Set dan Repetisi)
     */
    public function exerciseLogs(): HasMany
    {
        return $this->hasMany(ExerciseLog::class);
    }
}