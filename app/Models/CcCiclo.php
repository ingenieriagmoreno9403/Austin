<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CcCiclo extends Model
{
    public $table = 'tbl_cc_ciclos';

    protected $fillable = [
        'codigo',
        'nombre',
        'anio_referencia',
        'anio_presupuesto',
        'fecha_inicio',
        'fecha_fin',
        'captura_hasta',
        'revision_desde',
        'estado',
        'inflacion',
        'tipo_cambio',
        'observaciones',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'anio_referencia' => 'integer',
        'anio_presupuesto' => 'integer',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'captura_hasta' => 'date',
        'revision_desde' => 'date',
        'inflacion' => 'float',
        'tipo_cambio' => 'float',
    ];

    public function asignaciones()
    {
        return $this->hasMany(CcAsignacion::class, 'ciclo_codigo', 'codigo');
    }
}
