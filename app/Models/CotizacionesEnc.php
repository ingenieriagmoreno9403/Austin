<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CotizacionesEnc extends Model
{
    use HasFactory;
    
    public $table = 'tblcotizaciones_enc';
    
    protected $fillable = [
        'id',
        'id_servicio',
        'estado',
        'fecha',
        'nota',
        'reviso',
        'subtotal',
        'iva',
        'total',
        'otros1',
        'otros2',
        'otros3',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'fecha' => 'date',
        'subtotal' => 'decimal:2',
        'iva' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Relación con el servicio
     */
    public function servicio()
    {
        return $this->belongsTo(ServiciosEnc::class, 'id_servicio');
    }

    /**
     * Relación con el empleado que revisó
     */
    public function empleadoReviso()
    {
        return $this->belongsTo(Empleados::class, 'reviso');
    }

    /**
     * Relación con los detalles de la cotización
     */
    public function detalles()
    {
        return $this->hasMany(CotizacionesDet::class, 'id_cotizacion');
    }
}

