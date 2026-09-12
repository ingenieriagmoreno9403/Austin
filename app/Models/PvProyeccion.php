<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PvProyeccion extends Model
{
    public $table = 'tbl_pv_proyecciones';

    protected $fillable = [
        'ciclo_codigo',
        'empresa',
        'cliente_codigo',
        'producto_codigo',
        'producto_nombre',
        'mes_01',
        'mes_02',
        'mes_03',
        'mes_04',
        'mes_05',
        'mes_06',
        'mes_07',
        'mes_08',
        'mes_09',
        'mes_10',
        'mes_11',
        'mes_12',
        'costo_unitario',
        'moneda',
        'ajuste_pct',
        'completado',
        'updated_by',
    ];

    protected $casts = [
        'completado' => 'boolean',
        'costo_unitario' => 'float',
        'ajuste_pct' => 'float',
    ];

    public function getCentroCodigoAttribute()
    {
        return $this->attributes['cliente_codigo'] ?? null;
    }

    public function setCentroCodigoAttribute($value)
    {
        $this->attributes['cliente_codigo'] = $value;
    }

    public function getCuentaCodigoAttribute()
    {
        return $this->attributes['producto_codigo'] ?? null;
    }

    public function setCuentaCodigoAttribute($value)
    {
        $this->attributes['producto_codigo'] = $value;
    }

    public function getCuentaNombreAttribute()
    {
        return $this->attributes['producto_nombre'] ?? null;
    }

    public function setCuentaNombreAttribute($value)
    {
        $this->attributes['producto_nombre'] = $value;
    }

    /**
     * @return array<int, float|null>
     */
    public function meses(): array
    {
        $out = [];
        for ($i = 1; $i <= 12; $i++) {
            $col = 'mes_'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $val = $this->{$col};
            $out[] = ($val === null || $val === '') ? null : round((float) $val, 4);
        }

        return $out;
    }

    /**
     * @param  array<int, mixed>  $meses
     */
    public function setMeses(array $meses): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $col = 'mes_'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $raw = $meses[$i - 1] ?? null;
            if ($raw === null || $raw === '') {
                $this->{$col} = null;
            } else {
                $this->{$col} = round((float) $raw, 4);
            }
        }
    }
}
