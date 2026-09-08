<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CcPresupuesto extends Model
{
    public $table = 'tbl_cc_presupuestos';

    protected $fillable = [
        'ciclo_codigo',
        'empresa',
        'centro_codigo',
        'cuenta_codigo',
        'cuenta_nombre',
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
        'updated_by',
    ];

    protected $casts = [
        'mes_01' => 'float',
        'mes_02' => 'float',
        'mes_03' => 'float',
        'mes_04' => 'float',
        'mes_05' => 'float',
        'mes_06' => 'float',
        'mes_07' => 'float',
        'mes_08' => 'float',
        'mes_09' => 'float',
        'mes_10' => 'float',
        'mes_11' => 'float',
        'mes_12' => 'float',
    ];

    /**
     * @return array<int, float>
     */
    public function meses(): array
    {
        $out = [];
        for ($i = 1; $i <= 12; $i++) {
            $col = 'mes_'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $out[] = round((float) $this->{$col}, 2);
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
            $this->{$col} = round((float) ($meses[$i - 1] ?? 0), 2);
        }
    }
}
