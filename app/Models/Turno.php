<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Turno extends Model
{
    protected $table = 'tbl_turnos';

    protected $fillable = [
        'nombre',
        'hora_inicio',
        'hora_fin',
        'horas_turno',
        'estatus',
    ];

    protected $casts = [
        'horas_turno' => 'decimal:2',
    ];

    public function ordenes()
    {
        return $this->hasMany(OrdenProduccion::class, 'turno_id');
    }

    public function getEstatusTextoAttribute(): string
    {
        return match ($this->estatus) {
            'ACTIVO' => 'Activo',
            'INACTIVO' => 'Inactivo',
            default => $this->estatus,
        };
    }
}
