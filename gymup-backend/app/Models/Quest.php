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

    protected function casts(): array
    {
        return [
            'is_daily' => 'boolean',
        ];
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function userQuests(): HasMany
    {
        return $this->hasMany(UserQuest::class);
    }
}