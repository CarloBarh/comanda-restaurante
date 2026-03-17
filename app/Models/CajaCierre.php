<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CajaCierre extends Model
{
    protected $table = 'caja_cierre';

    protected $fillable = [
        'fecha',
        'total_entradas',
        'total_salidas',
        'balance',
        'efectivo',
        'tarjeta',
        'transferencia',
        'apertura',
        'cerrada_at',
    ];

    protected $casts = [
        'fecha'          => 'date',
        'cerrada_at'     => 'datetime',
        'total_entradas' => 'float',
        'total_salidas'  => 'float',
        'balance'        => 'float',
        'efectivo'       => 'float',
        'tarjeta'        => 'float',
        'transferencia'  => 'float',
        'apertura'       => 'float',
    ];
}