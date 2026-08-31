<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsistenciaAlumno extends Model
{
    protected $table = 'tblga_asistencias';

    protected $fillable = [
        'alumno_id',
        'empresa_id',
        'convenio_id',
        'fecha',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'alumno_id' => 'integer',
        'empresa_id' => 'integer',
        'convenio_id' => 'integer',
    ];

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class, 'alumno_id', 'id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(GestionAlumnosEmpresa::class, 'empresa_id', 'id');
    }

    public function convenio(): BelongsTo
    {
        return $this->belongsTo(ConvenioAlumnoEmpresa::class, 'convenio_id', 'id');
    }
}
