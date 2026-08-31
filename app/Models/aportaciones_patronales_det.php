<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class aportaciones_patronales_det extends Model
{
    use HasFactory;

    public $table = 'tblnomina_aportaciones_patronales_det';

    protected $fillable = [
        'id_patronales_enc',
        'id_empleado',
        'sbc',
        'dias_cotizados',
        'riesgo_trabajo',
        'cuota_fija',
        'enf_mat_excedente',
        'enf_mat_dinero',
        'enf_mat_gastos_pensionados',
        'invalidez_vida',
        'guarderia',
        'retiro',
        'cesantia_vejez',
        'infonavit',
        'total',
        'created_by',
        'updated_by',
    ];
}
