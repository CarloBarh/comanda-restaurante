<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Caja extends Model
{
    protected $table = 'caja';

    protected $fillable = [
        'tipo',
        'concepto',
        'monto',
        'factura_id',
        'mesero_id',
        'metodo_pago',
        'estado',
        'notas',
    ];

    protected $casts = [
        'monto' => 'float',
    ];

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }

    public function mesero(): BelongsTo
    {
        return $this->belongsTo(Mesero::class);
    }
}