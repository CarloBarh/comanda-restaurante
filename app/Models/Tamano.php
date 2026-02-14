<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tamano extends Model
{
    protected $table = 'tamanos';

    protected $fillable = ['nombre'];

    public function precios()
    {
        return $this->hasMany(PlatilloPrecio::class);
    }
}
