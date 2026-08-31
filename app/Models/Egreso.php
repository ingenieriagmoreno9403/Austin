<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Egreso extends Model
{
    use HasFactory;
    
    protected $table = 'egresos';
    
    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'concepto',
        'monto',
        'fecha',
        'categoria',
        'metodo_pago',
        'descripcion',
        'archivo_ruta',
        'archivo_nombre',
        'cuenta_id',
        'created_by',
        'updated_by'
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'fecha' => 'date:Y-m-d',
        'monto' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Obtener el usuario que creó el egreso
     */
    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Obtener el usuario que actualizó el egreso
     */
    public function actualizador()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope para filtrar por fecha
     */
    public function scopePorFecha($query, $fechaInicio, $fechaFin = null)
    {
        if ($fechaFin) {
            return $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
        }
        return $query->where('fecha', $fechaInicio);
    }

    /**
     * Scope para filtrar por categoría
     */
    public function scopePorCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    /**
     * Scope para filtrar por método de pago
     */
    public function scopePorMetodoPago($query, $metodoPago)
    {
        return $query->where('metodo_pago', $metodoPago);
    }

    /**
     * Obtener el total de egresos
     */
    public static function obtenerTotal($fechaInicio = null, $fechaFin = null)
    {
        $query = self::query();
        
        if ($fechaInicio && $fechaFin) {
            $query->porFecha($fechaInicio, $fechaFin);
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
     * Obtener la cuenta asociada al egreso
     */
    public function cuenta()
    {
        return $this->belongsTo(\App\Models\Cuenta::class, 'cuenta_id');
    }
}
