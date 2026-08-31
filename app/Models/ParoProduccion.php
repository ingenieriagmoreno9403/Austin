<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParoProduccion extends Model
{
    protected $table = 'tbl_paros_produccion';

    protected $fillable = [
        'orden_id',
        'maquina_id',
        'causa_paro_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'duracion_h',
        'comentario',
        'created_by',
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora_inicio' => 'datetime',
        'hora_fin' => 'datetime',
        'duracion_h' => 'decimal:3',
    ];

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenProduccion::class, 'orden_id');
    }

    public function maquina(): BelongsTo
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
    }

    public function causaParo(): BelongsTo
    {
        return $this->belongsTo(CausaParo::class, 'causa_paro_id');
    }
}
