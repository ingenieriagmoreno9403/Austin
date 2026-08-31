<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tutor extends Model
{
    protected $table = 'tbltutores';

    protected $fillable = [
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'direccion',
        'telefono',
        'correo',
        'alumno_id',
        'paretensco',
        'otros_conceptos1',
        'otros_conceptos2',
        'otros_conceptos34',
    ];

    protected $casts = [
        'otros_conceptos34' => 'decimal:2',
    ];

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class, 'alumno_id', 'id');
    }
}
