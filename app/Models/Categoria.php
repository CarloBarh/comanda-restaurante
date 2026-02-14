<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';
    protected $fillable = ['nombre'];

    public function platillos()
    {
        return $this->hasMany(Platillo::class, 'categoria_id');
    }
}
