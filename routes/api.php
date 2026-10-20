<?php

use App\Http\Controllers\Api\BossKillController;
use Illuminate\Support\Facades\Route;

// Producer HTTP: mesma ideia do comando artisan, só que disparado por
// uma requisição (ex: um client WoW enviando "matei o boss" pro servidor).
Route::post('/boss-kill', [BossKillController::class, 'store']);
Route::get('/characters/{character}/sheet', [BossKillController::class, 'sheet']);
