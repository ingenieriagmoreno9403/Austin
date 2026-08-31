<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConvenioAlumnoEmpresa extends Model
{
    protected $table = 'tblga_convenios_alumno_empresa';

    protected $fillable = [
        'alumno_id',
        'empresa_id',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'alumno_id' => 'integer',
        'empresa_id' => 'integer',
    ];

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class, 'alumno_id', 'id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(GestionAlumnosEmpresa::class, 'empresa_id', 'id');
    }

    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }
}
