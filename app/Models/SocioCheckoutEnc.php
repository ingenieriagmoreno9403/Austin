<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocioCheckoutEnc extends Model
{
    protected $table = 'tblsocios_checkout_enc';

    protected $fillable = [
        'id_socio',
        'fecha_hora_entrada',
        'pago_al_corriente',
        'resultado',
        'motivo',
        'created_by',
        'updated_by',
    ];

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class, 'id_socio');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(SocioCheckoutDet::class, 'id_checkout_enc');
    }
}
