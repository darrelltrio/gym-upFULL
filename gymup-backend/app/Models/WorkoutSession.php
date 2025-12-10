<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkoutSession extends Model
{
    use HasFactory;

    protected $table = 'workout_sessions';
    protected $primaryKey = 'session_id';

    public $timestamps = false;
    // Kolom yang boleh diisi
    protected $fillable = [
        'user_id',
        'session_date',
        'duration_seconds'
    ];

    // Relasi: Session -> Logs (One to Many)
    // Untuk mengambil detail set & reps saat menampilkan history
    public function logs()
    {
        return $this->hasMany(ExerciseLog::class, 'session_id', 'session_id');
    }
}