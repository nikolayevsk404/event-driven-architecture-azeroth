<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Guarda de idempotência. Um listener "reserva" (event_id, listener)
 * antes de aplicar o efeito colateral. Se a reserva falhar (já existe),
 * o evento já foi processado por aquele listener — não repete a ação.
 */
class ProcessedEvent extends Model
{
    protected $fillable = ['event_id', 'listener'];

    public static function markOrSkip(string $eventId, string $listener): bool
    {
        try {
            static::create(['event_id' => $eventId, 'listener' => $listener]);

            return true; // primeira vez processando: segue o fluxo.
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            return false; // já processado: listener deve ignorar (idempotência).
        }
    }
}
