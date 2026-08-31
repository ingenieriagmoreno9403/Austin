<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DeudaCobrar extends Model
{
    use HasFactory;
    
    protected $table = 'deudas_cobrar';
    
    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'deudor',
        'monto',
        'fecha_vencimiento',
        'estado',
        'descripcion',
        'archivo_ruta',
        'archivo_nombre',
        'fecha_cobro',
        'monto_cobrado',
        'id_venta',
        'id_factura',
        'created_by',
        'updated_by'
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'fecha_vencimiento' => 'date:Y-m-d',
        'fecha_cobro' => 'date:Y-m-d',
        'monto' => 'decimal:2',
        'monto_cobrado' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Obtener el usuario que creó la deuda
     */
    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Obtener el usuario que actualizó la deuda
     */
    public function actualizador()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope para filtrar por deudor
     */
    public function scopePorDeudor($query, $deudor)
    {
        return $query->where('deudor', 'like', '%' . $deudor . '%');
    }

    /**
     * Scope para filtrar por fecha de vencimiento
     */
    public function scopePorFechaVencimiento($query, $fechaInicio, $fechaFin = null)
    {
        if ($fechaFin) {
            return $query->whereBetween('fecha_vencimiento', [$fechaInicio, $fechaFin]);
        }
        return $query->where('fecha_vencimiento', $fechaInicio);
    }

    /**
     * Obtener el total de deudas por cobrar
     */
    public static function obtenerTotal($estado = null)
    {
        $query = self::query();
        
        if ($estado) {
            $query->porEstado($estado);
        }
        
        return $query->sum('monto');
    }

    /**
     * Formatear el monto para mostrar
     */
    public function getMontoFormateadoAttribute()
    {
        return '$' . number_format($this->monto, 2);
    }

    /**
     * Verificar si tiene archivo adjunto
     */
    public function tieneArchivo()
    {
        return !empty($this->archivo_ruta);
    }

    /**
     * Obtener la URL del archivo
     */
    public function getUrlArchivoAttribute()
    {
        if ($this->tieneArchivo()) {
            return Storage::url($this->archivo_ruta);
        }
        return null;
    }

    /**
     * Verificar si la deuda está vencida
     */
    public function getEstaVencidaAttribute()
    {
        return $this->fecha_vencimiento < now()->toDateString();
    }

    /**
     * Obtener el estado formateado
     */
    public function getEstadoFormateadoAttribute()
    {
        $estados = [
            'pendiente' => 'Pendiente',
            'vencida' => 'Vencida',
            'cobrada' => 'Cobrada'
        ];
        
        return $estados[$this->estado] ?? $this->estado;
    }
} 