<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class aportaciones_patronales_enc extends Model
{
    use HasFactory;

    public $table = 'tblnomina_aportaciones_patronales_enc';

    protected $fillable = [
        'fecha_inicio',
        'fecha_fin',
        'nombre',
        'dias_periodo',
        'total_sbc',
        'total_riesgo_trabajo',
        'total_cuota_fija',
        'total_enf_mat_excedente',
        'total_enf_mat_dinero',
        'total_enf_mat_gastos_pensionados',
        'total_invalidez_vida',
        'total_guarderia',
        'total_retiro',
        'total_cesantia_vejez',
        'total_infonavit',
        'total_final',
        'created_by',
        'updated_by',
    ];
}
