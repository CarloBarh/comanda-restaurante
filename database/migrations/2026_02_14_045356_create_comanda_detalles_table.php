<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comanda_detalles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('comanda_id')
                ->constrained('comandas')
                ->cascadeOnDelete();

            $table->foreignId('platillo_id')
                ->constrained('platillos')
                ->restrictOnDelete(); // evita borrar platillos usados en ventas

            $table->foreignId('tamano_id')
                ->nullable()
                ->constrained('tamanos')
                ->nullOnDelete();

            $table->unsignedInteger('cantidad')->default(1);

            // Guardamos el precio del momento (histórico)
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);

            // Estado por ítem (para monitores de áreas)
            $table->enum('estado', ['pendiente', 'preparando', 'listo'])
                ->default('pendiente');

            $table->text('notas')->nullable();

            $table->timestamps();

            $table->index(['comanda_id', 'estado']);
            $table->index(['platillo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comanda_detalles');
    }
};

