<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExerciseLog extends Model
{
    use HasFactory;

    protected $table = 'exercise_logs';
    protected $primaryKey = 'log_id';
    public $timestamps = false; // Tidak ada created_at/updated_at di tabel ini

    protected $fillable = [
        'session_id',
        'exercise_id',
        'set_number',
        'weight_kg',
        'reps'
    ];

    // Relasi: Log -> Exercise Catalog (Belongs To)
    // Untuk menampilkan nama latihan (misal: "Bench Press") di history
    public function exercise()
    {
        return $this->belongsTo(Exercise::class, 'exercise_id', 'exercise_id');
    }
}