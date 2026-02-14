<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meseros', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('pin', 4)->unique();   // 4 dígitos
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meseros');
    }
};
