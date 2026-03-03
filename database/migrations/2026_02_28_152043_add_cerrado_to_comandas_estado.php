<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    DB::statement("ALTER TABLE comandas MODIFY COLUMN estado 
        ENUM('en_proceso','entregado','finalizado','cerrado') DEFAULT 'en_proceso'");
}

public function down(): void
{
    DB::statement("ALTER TABLE comandas MODIFY COLUMN estado 
        ENUM('en_proceso','entregado','finalizado') DEFAULT 'en_proceso'");
}
};
