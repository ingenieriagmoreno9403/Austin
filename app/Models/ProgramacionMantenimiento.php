<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramacionMantenimiento extends Model
{
    use HasFactory;

    protected $table = 'tbl_programacion_mantenimiento';

    public $timestamps = false;

    protected $fillable = [
        'maquina_id',
        'actividad',
        'frecuencia',
        'ultima_ejecucion',
        'proxima_ejecucion',
        'responsable_id',
        'activo',
    ];

    protected $casts = [
        'ultima_ejecucion' => 'date',
        'proxima_ejecucion' => 'date',
        'activo' => 'boolean',
    ];

    public function maquina()
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
    }

    public function responsable()
    {
        return $this->belongsTo(Empleados::class, 'responsable_id');
    }

    public function getFrecuenciaTextoAttribute(): string
    {
        return match ($this->frecuencia) {
            'SEMANAL' => 'Semanal',
            'QUINCENAL' => 'Quincenal',
            'MENSUAL' => 'Mensual',
            'TRIMESTRAL' => 'Trimestral',
            'SEMESTRAL' => 'Semestral',
            'ANUAL' => 'Anual',
            default => $this->frecuencia,
        };
    }
}
