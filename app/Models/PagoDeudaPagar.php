<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoDeudaPagar extends Model
{
    protected $table = 'pagos_deuda_pagar';

    protected $fillable = [
        'deuda_pagar_id',
        'orden_compra_id',
        'monto',
        'fecha',
        'metodo_pago',
        'referencia',
        'cuenta_id',
        'egreso_id',
        'notas',
        'created_by',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha' => 'date:Y-m-d',
    ];

    public function deuda(): BelongsTo
    {
        return $this->belongsTo(DeudaPagar::class, 'deuda_pagar_id');
    }

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function egreso(): BelongsTo
    {
        return $this->belongsTo(Egreso::class, 'egreso_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
