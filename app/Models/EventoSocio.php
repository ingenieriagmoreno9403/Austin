<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventoSocio extends Model
{
    protected $table = 'tbleventos_socios';

    protected $fillable = [
        'nombre_evento',
        'descripcion',
        'fecha_evento',
        'tipo_evento',
        'lugar_evento',
        'hora_inicio',
        'hora_fin',
        'monto_estimado',
        'monto_comprometido',
        'status',
        'created_by',
        'updated_by',
    ];

    public function encargados(): HasMany
    {
        return $this->hasMany(EventoSocioEncargado::class, 'id_evento');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(EventoSocioDetalle::class, 'id_evento');
    }
}
