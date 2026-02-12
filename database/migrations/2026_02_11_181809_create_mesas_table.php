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
    Schema::create('mesas', function (Blueprint $table) {
        $table->id();
        $table->string('numero')->unique(); // Ej: 1, 2, 3 o A1, Terraza 1
        $table->integer('capacidad')->nullable();
        $table->enum('estado', ['libre', 'ocupada'])
              ->default('libre');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mesas');
    }
};
