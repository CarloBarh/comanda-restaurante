<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comandas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('mesa_id')
                ->constrained('mesas')
                ->cascadeOnDelete();

            $table->foreignId('mesero_id')
                ->constrained('meseros')
                ->cascadeOnDelete();

            $table->enum('estado', ['abierta', 'en_proceso', 'lista', 'cerrada'])
                ->default('abierta');

            $table->decimal('total', 10, 2)->default(0);

            $table->text('observaciones')->nullable();

            $table->timestamps();

            // Solo 1 comanda abierta por mesa (opcional pero MUY útil)
            $table->index(['mesa_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comandas');
    }
};

