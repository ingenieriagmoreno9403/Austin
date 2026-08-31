<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoFlangeEspecificacion extends Model
{
    protected $table = 'tbl_producto_flange_especificaciones';

    protected $fillable = [
        'producto_id',
        'diametro_nominal',
        'rd',
        'peso_kg_pieza',
        'descripcion',
        'estatus',
    ];

    protected $casts = [
        'rd' => 'decimal:3',
        'peso_kg_pieza' => 'decimal:3',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Productos::class, 'producto_id');
    }

    /**
     * Determina la ruta post-inyección según diámetro y RD.
     * 4" + RD 6 / 7 / 7.3 → proveedor externo; 4" → torno; 6" → corte.
     */
    public static function resolverRuta(?string $diametro, $rd): string
    {
        $d = strtoupper(trim((string) $diametro));
        $rdVal = $rd !== null && $rd !== '' ? (float) $rd : null;

        $es4 = str_contains($d, '4');
        $es6 = str_contains($d, '6');

        $rdsExternos = [6.0, 7.0, 7.3];
        if ($es4 && $rdVal !== null) {
            foreach ($rdsExternos as $objetivo) {
                if (abs($rdVal - $objetivo) < 0.001) {
                    return OrdenProduccion::RUTA_FLANGE_TORNO_EXTERNO;
                }
            }
        }
        if ($es6) {
            return OrdenProduccion::RUTA_FLANGE_CORTE;
        }

        return OrdenProduccion::RUTA_FLANGE_TORNO;
    }
}
