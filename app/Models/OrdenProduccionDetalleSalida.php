<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenProduccionDetalleSalida extends Model
{
    protected $table = 'tbl_ordenes_detalle_salidas';

    public $timestamps = false;

    protected $fillable = [
        'orden_detalle_id',
        'diametro_real',
        'rd_real',
        'espesor_real',
        'metros',
        'largo_tramo',
        'piezas',
        'piezas_buenas',
        'piezas_malas',
        'kg_real',
        'kg_teorico',
        'kg_merma',
        'kg_retrabajo',
        'porcentaje_merma',
        'ubicacion_destino_id',
        'observaciones',
        'created_at',
    ];

    protected $casts = [
        'espesor_real' => 'decimal:3',
        'metros' => 'decimal:3',
        'largo_tramo' => 'decimal:3',
        'kg_real' => 'decimal:3',
        'kg_teorico' => 'decimal:3',
        'kg_merma' => 'decimal:3',
        'kg_retrabajo' => 'decimal:3',
        'porcentaje_merma' => 'decimal:3',
        'created_at' => 'datetime',
    ];

    public function detalle(): BelongsTo
    {
        return $this->belongsTo(OrdenProduccionDetalle::class, 'orden_detalle_id');
    }

    public function ubicacionDestino(): BelongsTo
    {
        return $this->belongsTo(Ubicaciones::class, 'ubicacion_destino_id');
    }

    public function inspeccion()
    {
        return $this->hasOne(InspeccionCalidadOrdenTrabajo::class, 'salida_id');
    }

    public function reproceso()
    {
        return $this->hasOne(Reproceso::class, 'salida_id');
    }
}
