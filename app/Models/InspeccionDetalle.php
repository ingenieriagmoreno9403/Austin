<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspeccionDetalle extends Model
{
    use HasFactory;

    protected $table = 'tbl_inspecciones_detalle';

    public $timestamps = false;

    protected $fillable = [
        'inspeccion_id',
        'concepto',
        'resultado',
        'observaciones',
    ];

    public function inspeccion()
    {
        return $this->belongsTo(InspeccionMaquina::class, 'inspeccion_id');
    }

    public function getResultadoTextoAttribute(): string
    {
        return match ($this->resultado) {
            'OK' => 'OK',
            'OBSERVACION' => 'Observación',
            'CRITICO' => 'Crítico',
            default => $this->resultado,
        };
    }
}
