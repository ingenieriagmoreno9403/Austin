<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CotizacionesDet extends Model
{
    use HasFactory;
    
    public $table = 'tblcotizaciones_det';
    
    protected $fillable = [
        'id',
        'id_cotizacion',
        'pda',
        'cantidad',
        'producto_id',
        'marca',
        't_entrega',
        'p_unitario',
        'total',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'pda' => 'integer',
        'cantidad' => 'integer',
        'p_unitario' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Relación con la cotización encabezado
     */
    public function cotizacion()
    {
        return $this->belongsTo(CotizacionesEnc::class, 'id_cotizacion');
    }

    /**
     * Relación con el producto
     */
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}

