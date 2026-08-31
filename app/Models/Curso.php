<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Curso extends Model
{
    protected $table = 'cursos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo_curso',
        'duracion',
        'instructor',
        'link',
        'aula_lugar',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function alumnos(): BelongsToMany
    {
        return $this->belongsToMany(Alumno::class, 'tblalumno_cursos', 'curso_id', 'alumno_id')
            ->withPivot('calificacion')
            ->withTimestamps();
    }
}
