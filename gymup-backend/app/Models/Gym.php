<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gym extends Model
{
    // Hindari mass-assignment vulnerability
    protected $fillable = [
        'name',
        'status',
        'subscription_ends_at'
    ];

    // Satu Gym memiliki banyak Users (Owner & Members)
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}