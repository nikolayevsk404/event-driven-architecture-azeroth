<?php

namespace App\Console\Commands\Wow;

use App\Events\BossKilled;
use App\Models\CombatLogEntry;
use Illuminate\Console\Command;

/**
 * Simula o slide "E se o evento chegar duas vezes?". Em sistemas
 * distribuídos, reentrega (redelivery) faz parte do design: um broker
 * pode reenviar a mesma mensagem (timeout de ack, retry, etc).
 * Redisparamos o MESMO eventId e mostramos que os listeners ficam
 * quietos na segunda vez (idempotência), em vez de duplicar XP/loot/conquista.
 */
class ReplayBossKillCommand extends Command
{
    protected $signature = 'wow:replay-boss-kill {event_id : eventId impresso pelo wow:boss-kill}';

    protected $description = 'Redispara um BossKilled já existente, simulando entrega duplicada';

    public function handle(): int
    {
        $eventId = $this->argument('event_id');

        $entry = CombatLogEntry::where('event_id', $eventId)->first();

        if (! $entry) {
            $this->error("Nenhum combat log encontrado para o eventId {$eventId}.");
            $this->comment('Rode `php artisan wow:boss-kill` primeiro e copie o eventId impresso.');

            return self::FAILURE;
        }

        $this->warn("🔁 Reentregando BossKilled (eventId: {$eventId}) — igualzinho ao original.");

        BossKilled::dispatch($entry->event_id, $entry->character_id, $entry->boss_name);

        $this->newLine();
        $this->comment('Veja no `php artisan pail`: os listeners vão logar "já processado — ignorando".');

        return self::SUCCESS;
    }
}
