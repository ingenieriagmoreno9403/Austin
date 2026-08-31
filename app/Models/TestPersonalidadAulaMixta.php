<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestPersonalidadAulaMixta extends Model
{
    protected $table = 'tbltest_personalidad_aula_mixta';

    protected $fillable = [
        'alumno_id',
        'numero_matricula',
        'alumno_nombre',
        'grupo',
        'fecha_aplicacion',
        'puntaje_total',
        'interpretacion',
        'resultados_columnas_json',
        'rasgos_positivos_total',
        'rango_bajo',
        'rango_medio',
        'rango_alto',
    ];
}
