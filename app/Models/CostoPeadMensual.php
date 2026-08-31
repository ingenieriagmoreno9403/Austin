<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostoPeadMensual extends Model
{
    protected $table = 'tbl_costo_pead_mensual';

    protected $fillable = [
        'mes',
        'costo_kg',
        'proveedor',
        'observaciones',
    ];

    protected $casts = [
        'costo_kg' => 'decimal:4',
    ];

    public static function costoParaFecha($fecha): ?float
    {
        if (!$fecha) {
            return null;
        }

        $mes = \Carbon\Carbon::parse($fecha)->format('Y-m');
        $row = static::query()->where('mes', $mes)->first();

        return $row ? (float) $row->costo_kg : null;
    }
}
