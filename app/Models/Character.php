<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Character extends Model
{
    protected $fillable = ['name', 'class', 'xp', 'gold'];

    public function combatLogEntries(): HasMany
    {
        return $this->hasMany(CombatLogEntry::class);
    }

    public function getRouteKeyName(): string
    {
        return 'name';
    }
}
