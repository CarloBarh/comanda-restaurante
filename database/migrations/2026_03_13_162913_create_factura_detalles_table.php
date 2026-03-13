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
        Schema::create('factura_detalles', function (Blueprint $table) {
    $table->id();
    $table->foreignId('factura_id')->constrained('facturas')->cascadeOnDelete();
    $table->foreignId('platillo_id')->nullable()->constrained('platillos')->nullOnDelete();
    $table->foreignId('tamano_id')->nullable()->constrained('tamanos')->nullOnDelete();
    $table->string('platillo_nombre', 120);
    $table->string('tamano_nombre', 60)->nullable();
    $table->unsignedInteger('cantidad');
    $table->decimal('precio_unitario', 10, 2);
    $table->decimal('subtotal', 10, 2);
    $table->text('notas')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factura_detalles');
    }
};
