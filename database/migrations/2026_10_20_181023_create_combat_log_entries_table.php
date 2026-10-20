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
        // Este é o "Combat Log" do WoW: um append-only log de fatos (eventos).
        // É daqui que reconstruímos a ficha do personagem (Event Sourcing),
        // nunca de uma coluna contadora na tabela characters.
        Schema::create('combat_log_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->unique();
            $table->string('event_type');
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->string('boss_name')->nullable();
            $table->json('payload');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combat_log_entries');
    }
};
