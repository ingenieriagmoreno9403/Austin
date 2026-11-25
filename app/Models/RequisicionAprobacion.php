<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisicionAprobacion extends Model
{
    protected $table = 'tbl_requisicion_aprobaciones';

    protected $fillable = [
        'requisicion_id',
        'nivel_id',
        'orden',
        'nombre_nivel',
        'responsable_id',
        'estado',
        'decision',
        'comentario',
        'fecha_decision',
        'obligatorio',
    ];

    protected $casts = [
        'orden' => 'integer',
        'obligatorio' => 'boolean',
        'fecha_decision' => 'datetime',
    ];

    public function requisicion(): BelongsTo
    {
        return $this->belongsTo(Requisicion::class, 'requisicion_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
}
