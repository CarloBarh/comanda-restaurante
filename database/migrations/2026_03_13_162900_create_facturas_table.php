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
       Schema::create('facturas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('comanda_id')->constrained('comandas')->cascadeOnDelete();
    $table->foreignId('mesa_id')->constrained('mesas')->cascadeOnDelete();
    $table->foreignId('mesero_id')->nullable()->constrained('meseros')->nullOnDelete();
    $table->string('tipo_pago', 30);
    $table->string('numero_factura', 30)->nullable();
    $table->decimal('total', 10, 2)->default(0);
    $table->decimal('base_gravada', 10, 2)->default(0);
    $table->decimal('isv_15', 10, 2)->default(0);
    $table->decimal('importe_exento', 10, 2)->default(0);
    $table->decimal('importe_exonerado', 10, 2)->default(0);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
