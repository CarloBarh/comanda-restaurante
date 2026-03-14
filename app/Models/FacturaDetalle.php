<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacturaDetalle extends Model
{
    protected $fillable = [
        'factura_id',
        'platillo_id',
        'tamano_id',
        'platillo_nombre',
        'tamano_nombre',
        'cantidad',
        'precio_unitario',
        'subtotal',
        'notas',
         'descuento',
         'monto_descuento',
    ];

    protected $casts = [
        'cantidad'        => 'integer',
        'precio_unitario' => 'float',
        'subtotal'        => 'float',
    ];

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }

    public function platillo(): BelongsTo
    {
        return $this->belongsTo(Platillo::class);
    }

    public function tamano(): BelongsTo
    {
        return $this->belongsTo(Tamano::class);
    }
}
