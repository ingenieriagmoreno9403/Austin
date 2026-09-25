<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PvCiclo extends Model
{
    public $table = 'tbl_pv_ciclos';

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
        'tipo_cambio',
        'tipo_cambio_meses',
        'tipo_budget',
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
        'tipo_cambio' => 'float',
        'tipo_cambio_meses' => 'array',
    ];

    public function asignaciones()
    {
        return $this->hasMany(PvAsignacion::class, 'ciclo_codigo', 'codigo');
    }

    public function getInflacionAttribute()
    {
        return 0;
    }
}
