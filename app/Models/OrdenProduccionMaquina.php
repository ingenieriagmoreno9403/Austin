<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenProduccionMaquina extends Model
{
    protected $table = 'tbl_ordenes_maquinas';

    public $timestamps = true;

    protected $fillable = [
        'orden_id',
        'maquina_id',
        'secuencia',
        'estatus',
        'operador_id',
        'fecha_inicio',
        'fecha_fin',
        'observaciones',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenProduccion::class, 'orden_id');
    }

    public function maquina(): BelongsTo
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
    }

    public function operador(): BelongsTo
    {
        return $this->belongsTo(Empleados::class, 'operador_id');
    }

    public function esActiva(): bool
    {
        return ! in_array($this->estatus, OrdenProduccion::ESTATUS_FINALES, true);
    }

    public function getEstatusTextoAttribute(): string
    {
        return OrdenProduccion::$estatusFlujo[$this->estatus] ?? $this->estatus;
    }
}
