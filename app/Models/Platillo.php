<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Platillo extends Model
{
    protected $table = 'platillos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'categoria_id',
        'subcategoria_id',
        'precio',
        'imagen',
        'ingredientes',
        'area_id',
    ];

    public function precios()
    {
        return $this->hasMany(PlatilloPrecio::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
}
