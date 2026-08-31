<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class conceptos_nomina extends Model
{
    use HasFactory;

    public const VISOR_FISCAL = 'visor_fiscal';

    public $table = 'tblconceptos_nomina';

    public $timestamps = false;

    protected $fillable = [
        'grupo',
        'seccion',
        'nombre',
        'descripcion',
        'valor',
        'valor2',
        'otros1',
        'otros2',
        'otros3',
    ];

    public static function obtenerVisorFiscal(): self
    {
        return static::firstOrCreate(
            ['nombre' => self::VISOR_FISCAL],
            [
                'descripcion' => 'Visor Fiscal',
                'valor' => 0,
            ]
        );
    }

    public static function visorFiscalActivo(): bool
    {
        try {
            $concepto = static::where('nombre', self::VISOR_FISCAL)->first();

            return $concepto && (int) $concepto->valor === 1;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
