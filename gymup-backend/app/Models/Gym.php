<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gym extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'owner_name',
        'address',
        'logo_url',
        'status',
        'subscription_ends_at'
    ];

    // Beritahu Laravel ini adalah tipe Tanggal
    protected $casts = [
        'subscription_ends_at' => 'date',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // Relasi ke quest buatan Gym Owner (Custom Quests)
    public function quests(): HasMany
    {
        return $this->hasMany(Quest::class);
    }
}