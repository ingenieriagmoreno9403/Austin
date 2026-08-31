<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class TipoEmpaque extends Model
{
    protected $table = 'tbl_tipos_empaque';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'requiere_enrollar',
        'orden',
        'estatus',
    ];

    protected $casts = [
        'requiere_enrollar' => 'boolean',
        'orden' => 'integer',
    ];

    public function scopeActivos($query)
    {
        return $query->where('estatus', 'A');
    }

    public static function activosOrdenados(): Collection
    {
        return static::activos()->orderBy('orden')->orderBy('nombre')->get();
    }

    /** @return array<string, string> codigo => nombre */
    public static function opcionesActivas(): array
    {
        return static::activosOrdenados()
            ->pluck('nombre', 'codigo')
            ->all();
    }

    public static function etiqueta(?string $codigo): string
    {
        if ($codigo === null || $codigo === '') {
            return '—';
        }

        $nombre = static::where('codigo', $codigo)->value('nombre');
        if ($nombre) {
            return (string) $nombre;
        }

        return OrdenProduccion::$tiposEmpaque[$codigo] ?? $codigo;
    }

    public static function requiereEnrollar(?string $codigo): bool
    {
        if ($codigo === null || $codigo === '') {
            return false;
        }

        $tipo = static::where('codigo', $codigo)->first();
        if ($tipo) {
            return (bool) $tipo->requiere_enrollar;
        }

        return $codigo === OrdenProduccion::TIPO_EMPAQUE_ROLLO;
    }
}
