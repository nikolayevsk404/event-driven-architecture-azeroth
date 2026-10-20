<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Desliga o auto-discovery de listeners por convenção: o registro
    // explícito em AppServiceProvider::boot() (estilo antigo $listen do
    // EventServiceProvider) é a única fonte de verdade — sem isso, cada
    // listener era registrado 2x (auto-discovery + explícito) e reagia
    // em dobro ao mesmo evento.
    ->withEvents(discover: false)
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
