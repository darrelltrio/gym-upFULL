<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    use HasFactory;

    protected $primaryKey = 'exercise_id'; // Sesuai database kita
    public $timestamps = false; // Karena tabel exercises tidak punya created_at/updated_at default

    protected $fillable = [
        'name',
        'muscle_group',
        'equipment',
        'created_by' // <--- WAJIB DITAMBAHKAN
    ];
}