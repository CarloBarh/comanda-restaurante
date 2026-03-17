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
    Schema::table('facturas', function (Blueprint $table) {
        $table->string('cliente_nombre', 120)->nullable()->after('tipo_pago');
        $table->string('cliente_rtn', 20)->nullable()->after('cliente_nombre');
    });
}

public function down(): void
{
    Schema::table('facturas', function (Blueprint $table) {
        $table->dropColumn(['cliente_nombre', 'cliente_rtn']);
    });
}
};
