<?php

namespace App\Console\Commands\Wow;

use App\Events\BossKilled;
use App\Models\Character;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Producer (Publisher) da talk. Dispara o fato "BossKilled" e NÃO sabe
 * (nem precisa saber) quem vai reagir a ele. Compare a saída deste comando
 * com o `php artisan pail` rodando em outro terminal + `php artisan queue:work`
 * em um terceiro: você verá 3 listeners reagindo na hora e 1 reagindo
 * quando a fila processar.
 */
class BossKillCommand extends Command
{
    protected $signature = 'wow:boss-kill
        {character=Nikolayevsk : nome do personagem}
        {boss=Lich King : nome do boss derrotado}';

    protected $description = 'Dispara o evento BossKilled (Publish-Subscribe ao vivo)';

    public function handle(): int
    {
        $character = Character::firstOrCreate(
            ['name' => $this->argument('character')],
            ['class' => 'Warrior'],
        );

        $eventId = (string) Str::uuid();
        $boss = $this->argument('boss');

        $this->info("📣 Publicando BossKilled: {$character->name} derrotou {$boss}");
        $this->line("   eventId: {$eventId}");

        BossKilled::dispatch($eventId, $character->id, $boss);

        $this->newLine();
        $this->comment('Dica: abra `php artisan pail` em outro terminal para ver os listeners reagindo.');
        $this->comment('Dica: rode `php artisan queue:work` para processar o AwardLoot (assíncrono).');
        $this->comment("Para simular reentrega duplicada: php artisan wow:replay-boss-kill {$eventId}");

        return self::SUCCESS;
    }
}
