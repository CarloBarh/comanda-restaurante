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
    Schema::create('caja', function (Blueprint $table) {
        $table->id();

        // Tipo de movimiento
        $table->enum('tipo', ['entrada', 'salida']);

        // Concepto del movimiento
        $table->string('concepto', 150);

        // Monto
        $table->decimal('monto', 10, 2);

        // Referencia opcional a factura (solo para entradas por venta)
        $table->foreignId('factura_id')
              ->nullable()
              ->constrained('facturas')
              ->nullOnDelete();

        // Quién registró el movimiento
        $table->foreignId('mesero_id')
              ->nullable()
              ->constrained('meseros')
              ->nullOnDelete();

        // Método de pago (efectivo, tarjeta, transferencia — null para salidas manuales)
        $table->string('metodo_pago', 30)->nullable();

        // Estado del movimiento
        $table->enum('estado', ['activo', 'anulado'])->default('activo');

        // Nota opcional
        $table->text('notas')->nullable();

        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('caja');
}
};
