<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only "Combat Log". É o event store do demo:
 * a ficha do personagem pode ser recalculada 100% a partir daqui.
 */
class CombatLogEntry extends Model
{
    protected $fillable = ['event_id', 'event_type', 'character_id', 'boss_name', 'payload'];

    protected $casts = [
        'payload' => 'array',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
