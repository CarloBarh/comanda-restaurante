<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComandaDetalle extends Model
{
    protected $table = 'comanda_detalles';

    protected $fillable = [
        'comanda_id',
        'platillo_id',
        'tamano_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
        'estado',
        'notas',
          'descuento',
          'monto_descuento',
    ];

    public function comanda()
    {
        return $this->belongsTo(Comanda::class);
    }

    public function platillo()
    {
        return $this->belongsTo(Platillo::class);
    }

    public function tamano()
    {
        return $this->belongsTo(Tamano::class);
    }
}
