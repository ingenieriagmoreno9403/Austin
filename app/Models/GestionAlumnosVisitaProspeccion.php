<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GestionAlumnosVisitaProspeccion extends Model
{
    protected $table = 'tblvisitas_prospeccion';

    protected $fillable = [
        'empresa_id',
        'fecha_visita',
        'contacto_empresa',
        'puesto_contacto',
        'presentacion_sed',
        'acepta_adoptar_sed',
        'vacantes_disponibles',
        'perfil_buscado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_visita' => 'date',
        'presentacion_sed' => 'boolean',
        'vacantes_disponibles' => 'integer',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(GestionAlumnosEmpresa::class, 'empresa_id', 'id');
    }
}
