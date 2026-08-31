<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidenciaMaquina extends Model
{
    use HasFactory;

    protected $table = 'tbl_incidencias_maquina';

    public $timestamps = false;

    protected $fillable = [
        'maquina_id',
        'inspeccion_id',
        'fecha',
        'titulo',
        'descripcion',
        'prioridad',
        'responsable_id',
        'fecha_compromiso',
        'fecha_cierre',
        'estatus',
        'created_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_compromiso' => 'date',
        'fecha_cierre' => 'date',
        'created_at' => 'datetime',
    ];

    public function maquina()
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
    }

    public function inspeccion()
    {
        return $this->belongsTo(InspeccionMaquina::class, 'inspeccion_id');
    }

    public function responsable()
    {
        return $this->belongsTo(Empleados::class, 'responsable_id');
    }

    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class, 'incidencia_id');
    }

    public function getPrioridadTextoAttribute(): string
    {
        return match ($this->prioridad) {
            'BAJA' => 'Baja',
            'MEDIA' => 'Media',
            'ALTA' => 'Alta',
            'CRITICA' => 'Crítica',
            default => $this->prioridad,
        };
    }

    public function getEstatusTextoAttribute(): string
    {
        return match ($this->estatus) {
            'ABIERTA' => 'Abierta',
            'EN_PROCESO' => 'En proceso',
            'CERRADA' => 'Cerrada',
            default => $this->estatus,
        };
    }
}
