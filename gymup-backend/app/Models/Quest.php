<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quest extends Model
{
    use HasFactory;

    protected $fillable = [
        'gym_id',
        'title',
        'description',
        'requirement_type',
        'target_value',
        'xp_reward',
        'is_daily'
    ];

    // Cast is_daily menjadi boolean agar mudah diolah di Frontend JS
    protected $casts = [
        'is_daily' => 'boolean',
    ];

    /**
     * Relasi: Quest BISA SAJA milik sebuah Gym (Custom Gym Quest)
     * Jika null, berarti Global Quest dari Super Admin.
     */
    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    /**
     * Relasi: Satu Quest dikerjakan oleh banyak User
     */
    public function userQuests(): HasMany
    {
        return $this->hasMany(UserQuest::class);
    }
}