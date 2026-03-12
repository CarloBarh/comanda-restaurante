<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subcategoria extends Model
{
    protected $table = 'subcategorias';

    protected $fillable = [
        'categoria_id',
        'nombre',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }
}