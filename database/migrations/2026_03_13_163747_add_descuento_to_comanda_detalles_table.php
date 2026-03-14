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
    Schema::table('comanda_detalles', function (Blueprint $table) {
        $table->unsignedTinyInteger('descuento')->default(0)->after('subtotal');
    });
}

public function down(): void
{
    Schema::table('comanda_detalles', function (Blueprint $table) {
        $table->dropColumn('descuento');
    });
}
};
