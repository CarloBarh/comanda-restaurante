<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mesas', function (Blueprint $table) {
            $table->string('zona')->nullable()->after('numero');
            $table->unsignedInteger('numero_zona')->nullable()->after('zona');

            $table->integer('pos_x')->nullable()->after('estado');
            $table->integer('pos_y')->nullable()->after('pos_x');

            $table->integer('ancho')->default(80)->after('pos_y');
            $table->integer('alto')->default(80)->after('ancho');
        });
    }

    public function down(): void
    {
        Schema::table('mesas', function (Blueprint $table) {
            $table->dropColumn([
                'zona',
                'numero_zona',
                'pos_x',
                'pos_y',
                'ancho',
                'alto',
            ]);
        });
    }
};
