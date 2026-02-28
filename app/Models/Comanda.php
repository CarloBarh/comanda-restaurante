<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comanda extends Model
{
    protected $fillable = [
        'mesa_id',
        'mesero_id',
        'estado',
        'total',
        'observaciones',
    ];

    public function detalles()
    {
        return $this->hasMany(ComandaDetalle::class);
    }

    public function mesa()
    {
        return $this->belongsTo(Mesa::class);
    }

    public function mesero()
    {
        return $this->belongsTo(Mesero::class);
    }
}