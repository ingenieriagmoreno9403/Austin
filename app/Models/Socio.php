<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Socio extends Model
{
    protected $table = 'tblsocios';

    protected $fillable = [
        'id_titular',
        'numero_socio',
        'numero_dependiente',
        'id_tipo_socio',
        'nombre',
        'segund_nom',
        'ap_paterno',
        'ap_materno',
        'telefono',
        'correo',
        'domicilio',
        'cuota_periodicidad',
        'fecha_facturacion',
        'edad',
        'empresa',
        'id_grupo',
        'id_descuento',
        'id_porcentaje_des',
        'foto_path',
        'status',
        'created_by',
        'updated_by',
    ];

    public function tipoSocio(): BelongsTo
    {
        return $this->belongsTo(TipoSocio::class, 'id_tipo_socio');
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(GrupoSocio::class, 'id_grupo');
    }

    public function descuento(): BelongsTo
    {
        return $this->belongsTo(Descuento::class, 'id_descuento');
    }

    public function titular(): BelongsTo
    {
        return $this->belongsTo(self::class, 'id_titular');
    }
}
