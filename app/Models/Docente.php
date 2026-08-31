<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Docente extends Model
{
    protected $table = 'docentes';

    protected $fillable = [
        'primer_nombre',
        'segundo_nombre',
        'apellido_paterno',
        'apellido_materno',
        'email',
        'telefono',
        'especialidad',
        'grado_academico',
        'estatus',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function cursos(): BelongsToMany
    {
        return $this->belongsToMany(Curso::class, 'docente_cursos', 'docente_id', 'curso_id')
            ->withPivot('rol', 'fecha_inicio', 'fecha_fin', 'estatus')
            ->withTimestamps();
    }
}
