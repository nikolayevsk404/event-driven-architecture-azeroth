<?php

namespace App\Listeners;

use App\Events\BossKilled;
use App\Models\Character;
use App\Models\ProcessedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Consumer ASSÍNCRONO: implements ShouldQueue.
 *
 * Isto é o que separa Fase 4 (Comunicação) em dois mundos: o mesmo evento
 * BossKilled que disparou GrantExperience/UnlockAchievement em sincrono,
 * aqui vira uma MENSAGEM na tabela `jobs` (QUEUE_CONNECTION=database) e só
 * é processada quando o `php artisan queue:work` pegar o worker livre.
 *
 * "Calcular loot" é deliberadamente lento (sleep) para simular uma
 * dependência mais pesada (ex: serviço externo de itens) — o publisher
 * (BossKillCommand/Controller) NÃO fica esperando isso terminar.
 */
class AwardLoot implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public function handle(BossKilled $event): void
    {
        if (! ProcessedEvent::markOrSkip($event->eventId, self::class)) {
            Log::warning("♻️  [AwardLoot] Evento {$event->eventId} já processado — loot NÃO duplicado.");

            return;
        }

        sleep(2); // simula latência de um serviço externo de itens.

        $character = Character::findOrFail($event->characterId);
        $gold = random_int(10, 50);
        $character->increment('gold', $gold);

        Log::info("💰 [AwardLoot] (assíncrono, via fila) {$character->name} saqueou {$gold} de ouro de {$event->bossName}.");
    }
}
