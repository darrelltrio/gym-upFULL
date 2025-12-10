<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // API token

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable; // <--- PENTING: Pasang trait HasApiTokens di sini

    // 1. Definisikan Primary Key (Karena di database kita pakai 'user_id')
    protected $primaryKey = 'user_id';

    protected $appends = [
        'workouts_completed',
    ];

    // 2. Daftar kolom yang boleh diisi saat Register (Mass Assignment)
    protected $fillable = [
        'username', // Kita pakai username, bukan name
        'email',
        'password',
        
        // Data Fisik & Nutrisi (Wajib diisi saat register)
        'goal',
        'age',
        'gender',
        'height_cm',
        'weight_kg',
        'activity_level',

        // Data Gamifikasi (Opsional, tapi baik didaftarkan)
        'level',
        'xp',
        'rank_points',
        'current_streak'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            // Pastikan data angka dibaca sebagai integer/float, bukan string
            'age' => 'integer',
            'height_cm' => 'integer',
            'weight_kg' => 'float',
            'level' => 'integer',
            'xp' => 'integer',
        ];
    }

    public function workoutSessions()
    {
        // Parameter: Model, Foreign Key, Local Key
        return $this->hasMany(WorkoutSession::class, 'user_id', 'user_id');
    }

    public function getWorkoutsCompletedAttribute()
    {
        // Mengembalikan jumlah baris di tabel workout_sessions milik user ini
        return $this->workoutSessions()->count();
    }
}