<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CajaActiva extends Model
{
    protected $table = 'caja_activa';

    protected $fillable = ['activa'];

    protected $casts = ['activa' => 'boolean'];

    // Helper estático para leer el estado
    public static function estaAbierta(): bool
    {
        return (bool) self::first()?->activa;
    }

    // Helper estático para cambiar el estado
    public static function setEstado(bool $estado): void
    {
        self::query()->update(['activa' => $estado]);
    }
}