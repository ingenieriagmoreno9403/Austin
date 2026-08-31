<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspeccionMaquina extends Model
{
    use HasFactory;

    protected $table = 'tbl_inspecciones_maquina';

    public $timestamps = false;

    protected $fillable = [
        'maquina_id',
        'fecha_inspeccion',
        'inspector_id',
        'supervisor_id',
        'resultado_general',
        'observaciones_generales',
        'requiere_mantenimiento',
        'estatus',
        'created_at',
    ];

    protected $casts = [
        'fecha_inspeccion' => 'date',
        'requiere_mantenimiento' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function maquina()
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
    }

    public function inspector()
    {
        return $this->belongsTo(Empleados::class, 'inspector_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(Empleados::class, 'supervisor_id');
    }

    public function detalles()
    {
        return $this->hasMany(InspeccionDetalle::class, 'inspeccion_id');
    }

    public function fotos()
    {
        return $this->hasMany(InspeccionFoto::class, 'inspeccion_id');
    }

    public function getResultadoGeneralTextoAttribute(): string
    {
        return match ($this->resultado_general) {
            'EXCELENTE' => 'Excelente',
            'BUENO' => 'Bueno',
            'REGULAR' => 'Regular',
            'MALO' => 'Malo',
            'CRITICO' => 'Crítico',
            default => $this->resultado_general,
        };
    }

    public function getEstatusTextoAttribute(): string
    {
        return $this->estatus === 'CERRADA' ? 'Cerrada' : 'Abierta';
    }
}
