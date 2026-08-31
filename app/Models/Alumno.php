<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alumno extends Model
{
    protected $table = 'tblalumnos';

    protected $fillable = [
        'nunero_matricula',
        'id_especialidad',
        'semestre',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'id_escuela',
        'id_empresa',
        'fecha_nacimiento',
        'telefono',
        'correo',
        'estado',
        'id_beca',
        'otros_conceptos1',
        'otros_conceptos2',
        'otros_conceptos34',
    ];

    /**
     * En BD la columna es `nunero_matricula`. La API y el front usan `numero_matricula`.
     */
    protected $hidden = [
        'nunero_matricula',
    ];

    protected $appends = [
        'numero_matricula',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'semestre' => 'integer',
        'nunero_matricula' => 'integer',
        'id_especialidad' => 'integer',
        'id_empresa' => 'integer',
        'id_beca' => 'integer',
        'otros_conceptos34' => 'decimal:2',
    ];

    public function getNumeroMatriculaAttribute(): ?int
    {
        if (! array_key_exists('nunero_matricula', $this->attributes)) {
            return null;
        }

        return (int) $this->attributes['nunero_matricula'];
    }

    public function escuela(): BelongsTo
    {
        return $this->belongsTo(Escuela::class, 'id_escuela', 'id');
    }

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class, 'id_especialidad');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(GestionAlumnosEmpresa::class, 'id_empresa');
    }

    public function tutores(): HasMany
    {
        return $this->hasMany(Tutor::class, 'alumno_id', 'id');
    }

    public function cursos(): BelongsToMany
    {
        return $this->belongsToMany(Curso::class, 'tblalumno_cursos', 'alumno_id', 'curso_id')
            ->withPivot('calificacion')
            ->withTimestamps();
    }
}
