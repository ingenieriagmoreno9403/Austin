<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonaDocumento extends Model
{
    protected $table = 'tblpersonas_documentos';

    protected $fillable = [
        'id_documento',
        'id_persona',
        'tipos_persona',
        'fecha_alta',
        'estado_documento',
        'otros_conceptos2',
        'otros_conceptos34',
        'ruta_documento',
    ];

    protected $casts = [
        'id_documento' => 'integer',
        'id_persona' => 'integer',
        'otros_conceptos34' => 'decimal:2',
    ];

    public function tipoDocumento()
    {
        return $this->belongsTo(Documento::class, 'id_documento');
    }
}
