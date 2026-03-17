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
    Schema::create('caja_cierre', function (Blueprint $table) {
        $table->id();
        $table->date('fecha');
        $table->decimal('total_entradas', 10, 2)->default(0);
        $table->decimal('total_salidas',  10, 2)->default(0);
        $table->decimal('balance',        10, 2)->default(0);
        $table->decimal('efectivo',       10, 2)->default(0);
        $table->decimal('tarjeta',        10, 2)->default(0);
        $table->decimal('transferencia',  10, 2)->default(0);
        $table->decimal('apertura',       10, 2)->default(0); // monto con que se abrió
        $table->timestamp('cerrada_at');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('caja_cierre');
}
};
