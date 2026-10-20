<?php

namespace App\Console\Commands\Wow;

use App\Models\Character;
use Illuminate\Console\Command;

/**
 * Bônus: Event Sourcing. A coluna `characters.xp` é só uma "projeção"
 * (cache de leitura). Aqui provamos que dá pra reconstruir o mesmo dado
 * — e mais: o histórico de quais bosses, em que ordem — usando SOMENTE
 * o combat_log_entries (o event store), sem tocar em xp/gold.
 */
class CharacterSheetCommand extends Command
{
    protected $signature = 'wow:character-sheet {character=Nikolayevsk}';

    protected $description = 'Reconstrói a ficha do personagem a partir do Combat Log (Event Sourcing)';

    public function handle(): int
    {
        $character = Character::where('name', $this->argument('character'))->first();

        if (! $character) {
            $this->error('Personagem não encontrado. Rode `php artisan wow:boss-kill` primeiro.');

            return self::FAILURE;
        }

        $this->info("=== Ficha de {$character->name} (projeção / tabela characters) ===");
        $this->line("XP: {$character->xp}  |  Gold: {$character->gold}");

        $this->newLine();
        $this->info('=== Ficha reconstruída via replay do Combat Log (Event Sourcing) ===');

        $kills = $character->combatLogEntries()->orderBy('created_at')->get();

        $rebuiltXp = $kills->count() * 250; // mesma regra do GrantExperience, aplicada em replay.

        $this->line("Bosses derrotados ({$kills->count()}):");
        foreach ($kills as $i => $entry) {
            $this->line('  '.($i + 1).". {$entry->boss_name} (eventId: {$entry->event_id}) em {$entry->created_at}");
        }

        $this->newLine();
        $this->line("XP recalculado a partir do histórico: {$rebuiltXp}");
        $this->comment($rebuiltXp === $character->xp
            ? '✅ Bate com a projeção — o estado É derivado do histórico de eventos.'
            : '⚠️  Divergiu da projeção — sinal de bug na projeção, não no event store (o log é a fonte da verdade).');

        return self::SUCCESS;
    }
}
