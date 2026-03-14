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
        $table->decimal('monto_descuento', 10, 2)->default(0)->after('descuento');
    });

    Schema::table('factura_detalles', function (Blueprint $table) {
        $table->decimal('monto_descuento', 10, 2)->default(0)->after('descuento');
    });
}

public function down(): void
{
    Schema::table('comanda_detalles', function (Blueprint $table) {
        $table->dropColumn('monto_descuento');
    });

    Schema::table('factura_detalles', function (Blueprint $table) {
        $table->dropColumn('monto_descuento');
    });
}
};
