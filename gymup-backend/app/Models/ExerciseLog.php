<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExerciseLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'workout_session_id',
        'exercise_id',
        'set_number',
        'weight_kg',
        'reps'
    ];

    /**
     * Relasi: Log ini berada di dalam satu Sesi Latihan tertentu
     */
    public function workoutSession(): BelongsTo
    {
        return $this->belongsTo(WorkoutSession::class);
    }

    /**
     * Relasi: Log ini merujuk pada satu jenis latihan di Katalog Global
     * (Misal: Log ini adalah log untuk gerakan 'Squat')
     */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}