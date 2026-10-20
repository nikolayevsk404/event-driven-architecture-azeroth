<?php

namespace App\Listeners;

use App\Events\BossKilled;
use App\Models\Character;
use App\Models\ProcessedEvent;
use Illuminate\Support\Facades\Log;

/**
 * Este é o exemplo exato do slide "E se o evento chegar duas vezes?":
 * o AchievementService não pode liberar a mesma conquista duas vezes.
 * processar 1x ou 2x deve produzir o mesmo resultado final.
 */
class UnlockAchievement
{
    public function handle(BossKilled $event): void
    {
        if (! ProcessedEvent::markOrSkip($event->eventId, self::class)) {
            Log::warning("♻️  [UnlockAchievement] Evento {$event->eventId} já processado — conquista NÃO duplicada.");

            return;
        }

        $character = Character::findOrFail($event->characterId);

        Log::info("🏆 [UnlockAchievement] Conquista liberada para {$character->name}: \"Matador(a) de {$event->bossName}\".");
    }
}
