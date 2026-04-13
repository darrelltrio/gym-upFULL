<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercise extends Model
{
    use HasFactory;

    // $fillable adalah daftar kolom yang Boleh diisi secara massal (mass-assignment)
    protected $fillable = [
        'name',
        'target_muscle',
        'type',
        'base_xp',
        'asset_url'
    ];

    /**
     * Relasi: Satu jenis latihan (misal: Bench Press) bisa memiliki banyak Log dari berbagai User
     * Mengapa ini penting? Nanti kita bisa query: "Siapa saja user yang pernah Bench Press?"
     */
    public function exerciseLogs(): HasMany
    {
        return $this->hasMany(ExerciseLog::class);
    }
}