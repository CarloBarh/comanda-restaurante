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
    Schema::table('caja', function (Blueprint $table) {
        // Reemplazar enum activo/anulado con uno que incluya 'cerrado'
        DB::statement("ALTER TABLE caja MODIFY COLUMN estado ENUM('activo','anulado','cerrado') DEFAULT 'activo'");
    });
}

public function down(): void
{
    Schema::table('caja', function (Blueprint $table) {
        DB::statement("ALTER TABLE caja MODIFY COLUMN estado ENUM('activo','anulado') DEFAULT 'activo'");
    });
}
};
