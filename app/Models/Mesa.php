<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mesa extends Model
{
    protected $fillable = [
    'numero',
    'capacidad',
    'estado',
    'zona',
    'numero_zona',
    'pos_x',
    'pos_y',
    'ancho',
    'alto',
];
}
