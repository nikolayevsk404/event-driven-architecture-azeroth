<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento = semântica de negócio: um fato que já aconteceu.
 * "BossKilled" não pergunta nada a ninguém; apenas anuncia o fato.
 * Quem quiser reagir (dar XP, soltar loot, liberar conquista...), reage.
 *
 * Note o $eventId: é o que permite os listeners detectarem reentrega
 * duplicada do evento (ver ProcessedEvent::markOrSkip).
 */
class BossKilled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $eventId,
        public readonly int $characterId,
        public readonly string $bossName,
    ) {
    }
}
