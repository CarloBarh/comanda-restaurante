<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE comandas MODIFY COLUMN estado ENUM('en_proceso','entregado','finalizado') DEFAULT 'en_proceso'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE comandas MODIFY COLUMN estado ENUM('en_proceso','finalizado') DEFAULT 'en_proceso'");
    }
};