<?php

namespace App\Providers;

use App\Events\BossKilled;
use App\Listeners\AwardLoot;
use App\Listeners\GrantExperience;
use App\Listeners\LogCombatEvent;
use App\Listeners\UnlockAchievement;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Este bloco é o "Dispatch / Broker" da talk: um fato (BossKilled) tem
     * vários interessados reagindo, sem que o publisher conheça nenhum deles
     * (Publish-Subscribe). Três reagem na hora (síncrono); um reage quando a
     * fila liberar (assíncrono) — fica explícito em `implements ShouldQueue`
     * dentro do próprio listener, não aqui.
     */
    public function boot(): void
    {
        Event::listen(BossKilled::class, LogCombatEvent::class);
        Event::listen(BossKilled::class, GrantExperience::class);
        Event::listen(BossKilled::class, UnlockAchievement::class);
        Event::listen(BossKilled::class, AwardLoot::class);
    }
}
