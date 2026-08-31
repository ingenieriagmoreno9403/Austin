<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestPersonalidadAulaMixtaRespuesta extends Model
{
    protected $table = 'tbltest_personalidad_aula_mixta_respuestas';

    protected $fillable = [
        'test_id',
        'alumno_id',
        'reactivo_numero',
        'respuesta_valor',
    ];
}
