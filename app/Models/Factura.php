<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Factura extends Model
{
    protected $fillable = [
        'comanda_id',
        'mesa_id',
        'mesero_id',
        'tipo_pago',
        'total',
        'base_gravada',
        'isv_15',
        'importe_exento',
        'importe_exonerado',
        'numero_factura',
        'cliente_nombre',
'cliente_rtn',
    ];

    protected $casts = [
        'total'               => 'float',
        'base_gravada'        => 'float',
        'isv_15'              => 'float',
        'importe_exento'      => 'float',
        'importe_exonerado'   => 'float',
    ];

    public function comanda(): BelongsTo
    {
        return $this->belongsTo(Comanda::class);
    }

    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class);
    }

    public function mesero(): BelongsTo
    {
        return $this->belongsTo(Mesero::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(FacturaDetalle::class);
    }

    // Genera el número correlativo basado en el id de la factura
    // Formato: 002-001-01-XXXXXXXX
    public function generarNumero(): string
    {
        $correlativo = str_pad($this->id, 8, '0', STR_PAD_LEFT);
        return "002-001-01-{$correlativo}";
    }
}
