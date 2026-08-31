<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoSocioEnc extends Model
{
    protected $table = 'tblpagos_socios_enc';

    protected $fillable = [
        'id_socio',
        'periodicidad',
        'fecha_inicio',
        'plazos',
        'monto_base',
        'porcentaje_descuento',
        'monto_final_periodo',
        'monto_total_plan',
        'status',
        'created_by',
        'updated_by',
    ];

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class, 'id_socio');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(PagoSocioDet::class, 'id_pago_enc');
    }
}
