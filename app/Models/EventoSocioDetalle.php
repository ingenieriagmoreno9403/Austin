<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventoSocioDetalle extends Model
{
    protected $table = 'tbleventos_socios_det';

    protected $fillable = [
        'id_evento',
        'concepto',
        'categoria',
        'cantidad',
        'costo_unitario',
        'costo_total',
        'empresa_proveedor',
        'lugar_entrega',
        'fecha_recibido',
        'hora_recibido',
        'fecha_recogida',
        'hora_recogida',
        'observaciones',
        'created_by',
        'updated_by',
    ];

    public function evento(): BelongsTo
    {
        return $this->belongsTo(EventoSocio::class, 'id_evento');
    }
}
