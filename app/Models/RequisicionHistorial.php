<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisicionHistorial extends Model
{
    public $timestamps = false;

    protected $table = 'tbl_requisicion_historial';

    protected $fillable = [
        'requisicion_id',
        'usuario_id',
        'accion',
        'estado_anterior',
        'estado_nuevo',
        'comentario',
        'meta',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function requisicion(): BelongsTo
    {
        return $this->belongsTo(Requisicion::class, 'requisicion_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
