<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventoSocioEncargado extends Model
{
    protected $table = 'tbleventos_socios_encargados';

    protected $fillable = [
        'id_evento',
        'id_socio',
        'monto_aportacion',
        'rol_encargado',
        'created_by',
        'updated_by',
    ];

    public function evento(): BelongsTo
    {
        return $this->belongsTo(EventoSocio::class, 'id_evento');
    }

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class, 'id_socio');
    }
}
