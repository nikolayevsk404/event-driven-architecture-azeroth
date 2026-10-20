<?php

namespace App\Listeners;

use App\Events\BossKilled;
use App\Models\CombatLogEntry;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;

/**
 * Consumer síncrono: grava o fato no "Combat Log" (nosso event store).
 * Roda no mesmo processo/request do publisher — sem fila.
 */
class LogCombatEvent
{
    public function handle(BossKilled $event): void
    {
        try {
            CombatLogEntry::create([
                'event_id' => $event->eventId,
                'event_type' => BossKilled::class,
                'character_id' => $event->characterId,
                'boss_name' => $event->bossName,
                'payload' => [
                    'eventId' => $event->eventId,
                    'characterId' => $event->characterId,
                    'bossName' => $event->bossName,
                ],
            ]);

            Log::info("📜 [LogCombatEvent] Combat log gravado: {$event->bossName} derrotado.", [
                'event_id' => $event->eventId,
            ]);
        } catch (UniqueConstraintViolationException) {
            Log::warning("♻️  [LogCombatEvent] Evento {$event->eventId} já estava no combat log — reentrega ignorada.");
        }
    }
}
