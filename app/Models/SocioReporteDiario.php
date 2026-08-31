<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocioReporteDiario extends Model
{
    protected $table = 'tblsocios_reportes_diarios';

    protected $fillable = [
        'fecha_reporte',
        'nuevos_socios',
        'socios_entradas',
        'monto_cobrado_dia',
        'monto_cancelado_dia',
        'corte_caja_esperado',
        'caja_fisica_reportada',
        'diferencia_corte',
        'observaciones',
        'created_by',
        'updated_by',
    ];
}
