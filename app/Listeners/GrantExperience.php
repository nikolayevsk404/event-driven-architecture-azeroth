<?php

namespace App\Listeners;

use App\Events\BossKilled;
use App\Models\Character;
use App\Models\ProcessedEvent;
use Illuminate\Support\Facades\Log;

/**
 * Consumer síncrono: concede XP imediatamente.
 * Mesmo publisher, mesmo processo — reage rápido, sem fila.
 */
class GrantExperience
{
    private const XP_PER_BOSS = 250;

    public function handle(BossKilled $event): void
    {
        if (! ProcessedEvent::markOrSkip($event->eventId, self::class)) {
            Log::warning("♻️  [GrantExperience] Evento {$event->eventId} já processado — ignorando (idempotência).");

            return;
        }

        $character = Character::findOrFail($event->characterId);
        $character->increment('xp', self::XP_PER_BOSS);

        Log::info("⚔️  [GrantExperience] +".self::XP_PER_BOSS." XP para {$character->name} por derrotar {$event->bossName}.");
    }
}
