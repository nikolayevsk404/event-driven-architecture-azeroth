<?php

namespace App\Http\Controllers\Api;

use App\Events\BossKilled;
use App\Http\Controllers\Controller;
use App\Models\Character;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Producer via HTTP. Mesma mecânica do BossKillCommand, só que entrando
 * pela borda web — útil pra demonstrar com `curl` ao vivo na talk.
 */
class BossKillController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'character' => 'required|string|max:255',
            'boss' => 'sometimes|string|max:255',
        ]);

        $character = Character::firstOrCreate(
            ['name' => $data['character']],
            ['class' => 'Warrior'],
        );

        $eventId = (string) Str::uuid();
        $boss = $data['boss'] ?? 'Lich King';

        BossKilled::dispatch($eventId, $character->id, $boss);

        return response()->json([
            'message' => 'BossKilled publicado. Síncronos já reagiram; AwardLoot está na fila.',
            'event_id' => $eventId,
            'character' => $character->name,
            'boss' => $boss,
            'replay_hint' => "php artisan wow:replay-boss-kill {$eventId}",
        ], 202);
    }

    public function sheet(Character $character)
    {
        $kills = $character->combatLogEntries()->orderBy('created_at')->get([
            'event_id', 'boss_name', 'created_at',
        ]);

        return response()->json([
            'projection' => [
                'name' => $character->name,
                'xp' => $character->xp,
                'gold' => $character->gold,
            ],
            'rebuilt_from_event_log' => [
                'kills_count' => $kills->count(),
                'xp_rebuilt' => $kills->count() * 250,
                'kills' => $kills,
            ],
        ]);
    }
}
