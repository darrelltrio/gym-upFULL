<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Food extends Model
{
    use HasFactory;

    // Menghubungkan ke tabel yang benar
    protected $table = 'foods';

    // PENTING: Mendefinisikan Primary Key kustom sesuai database Anda
    protected $primaryKey = 'food_id';

    // Kolom yang boleh diisi (Mass Assignment)
    protected $fillable = [
        'name',
        'serving_size_g',
        'calories',
        'protein_g',
        'carbs_g',
        'fat_g',
        'category',
    ];

    // Karena di tabel foods tidak ada kolom created_at/updated_at di skema,
    // kita perlu mematikan timestamps agar tidak error saat insert/update via Laravel.
    // Namun, jika Anda menambahkannya manual nanti, set ini ke true.
    public $timestamps = false; 
}