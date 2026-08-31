<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VentaCotizacion extends Model
{
    public $table = 'tbl_cotizaciones';

    public $timestamps = false;

    protected $fillable = [
        'folio',
        'cliente_id',
        'persona_atencion',
        'usuario_id',
        'fecha',
        'fecha_vencimiento',
        'subtotal',
        'descuento',
        'iva',
        'total',
        'estatus',
        'observaciones',
        'tiempo_entrega',
        'moneda_id',
        'tipo_flete_id',
        'condicion_pago_id',
        'importe_flete',
        'flete_en_precios',
        'iva_en_precios',
        'tipo_iva_id',
        'porcentaje_iva',
        'archivo_cliente_ruta',
        'archivo_cliente_nombre',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'fecha_vencimiento' => 'date',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'iva' => 'decimal:2',
        'total' => 'decimal:2',
        'importe_flete' => 'decimal:2',
        'porcentaje_iva' => 'decimal:2',
        'flete_en_precios' => 'boolean',
        'iva_en_precios' => 'boolean',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Clientes::class, 'cliente_id');
    }

    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Moneda::class, 'moneda_id');
    }

    public function tipoFlete(): BelongsTo
    {
        return $this->belongsTo(TipoFlete::class, 'tipo_flete_id');
    }

    public function tipoIva(): BelongsTo
    {
        return $this->belongsTo(TipoIva::class, 'tipo_iva_id');
    }

    public function condicionPago(): BelongsTo
    {
        return $this->belongsTo(CondicionPago::class, 'condicion_pago_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(VentaCotizacionDetalle::class, 'cotizacion_id');
    }
}
