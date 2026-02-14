<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatilloPrecio extends Model
{
    protected $table = 'platillo_precios';

    protected $fillable = [
        'platillo_id',
        'tamano_id',
        'precio',
    ];

    public function platillo()
    {
        return $this->belongsTo(Platillo::class);
    }

    public function tamano()
    {
        return $this->belongsTo(Tamano::class);
    }
}
