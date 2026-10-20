<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Guarda de idempotência: "e se o evento chegar duas vezes?"
        // Cada listener tenta reservar (event_id, listener) antes de agir.
        // Se já existir, o efeito colateral já foi aplicado — não repete.
        Schema::create('processed_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id');
            $table->string('listener');
            $table->timestamps();

            $table->unique(['event_id', 'listener']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processed_events');
    }
};
