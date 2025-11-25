<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    // Beritahu Laravel nama tabel kita (karena tidak pakai standar plural 's')
    protected $table = 'exercises';
    
    // Beritahu Laravel nama primary key kita
    protected $primaryKey = 'exercise_id';

    // Matikan timestamps (karena di tabel exercises kita tidak buat kolom created_at/updated_at)
    public $timestamps = false;

    // Kolom yang boleh diisi (untuk fitur Create nanti)
    protected $fillable = [
        'name',
        'muscle_group',
        'equipment'
    ];
}
