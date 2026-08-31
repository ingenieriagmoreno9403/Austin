<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcesoProduccionResponsable extends Model
{
    protected $table = 'tbl_procesos_produccion_responsables';

    protected $fillable = [
        'estatus',
        'puesto_id',
    ];

    public function puesto()
    {
        return $this->belongsTo(Puestos::class, 'puesto_id');
    }

    /**
     * Mapa estatus => nombre del puesto responsable (desde RH).
     *
     * @return array<string, string|null>
     */
    public static function mapaResponsables(): array
    {
        return static::query()
            ->with('puesto')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->estatus => $r->puesto?->nombre])
            ->all();
    }
}
