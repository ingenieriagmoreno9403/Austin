<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LicitacionAdjudicacion extends Model
{
    use HasFactory;

    protected $table = 'tbllicitacion_adjudicaciones';
    protected $fillable = [
        'licitacion_id',
        'producto_id',
        'proveedor_id',
        'cantidad_adjudicada',
        'precio_unitario',
        'subtotal',
        'observaciones',
        'fecha_adjudicacion',
        'usuario_adjudicador'
    ];

    public $timestamps = false;

    /**
     * Relación con la licitación
     */
    public function licitacion()
    {
        return $this->belongsTo(Licitacion::class, 'licitacion_id');
    }

    /**
     * Relación con el producto
     */
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    /**
     * Relación con el proveedor
     */
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    /**
     * Scope para obtener adjudicaciones por licitación
     */
    public function scopePorLicitacion($query, $licitacionId)
    {
        return $query->where('licitacion_id', $licitacionId);
    }

    /**
     * Scope para obtener adjudicaciones por proveedor
     */
    public function scopePorProveedor($query, $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }

    /**
     * Scope para obtener adjudicaciones por producto
     */
    public function scopePorProducto($query, $productoId)
    {
        return $query->where('producto_id', $productoId);
    }

    /**
     * Método para obtener todas las adjudicaciones agrupadas por proveedor
     */
    public static function obtenerAdjudicacionesPorProveedor($licitacionId)
    {
        return static::with(['producto', 'proveedor'])
            ->where('licitacion_id', $licitacionId)
            ->get()
            ->groupBy('proveedor_id');
    }

    /**
     * Método para calcular el total adjudicado por proveedor
     */
    public static function calcularTotalPorProveedor($licitacionId, $proveedorId)
    {
        return static::where('licitacion_id', $licitacionId)
            ->where('proveedor_id', $proveedorId)
            ->sum('subtotal');
    }

    /**
     * Método para verificar si una licitación está completamente adjudicada
     */
    public static function licitacionCompleta($licitacionId)
    {
        // Obtener productos de la licitación
        $productosLicitacion = DB::table('tbllicitacion_det')
            ->where('licitacion_id', $licitacionId)
            ->get();

        // Verificar si todos los productos tienen adjudicaciones
        foreach ($productosLicitacion as $producto) {
            $cantidadAdjudicada = static::where('licitacion_id', $licitacionId)
                ->where('producto_id', $producto->producto_id)
                ->sum('cantidad_adjudicada');

            if ($cantidadAdjudicada < $producto->cantidad) {
                return false;
            }
        }

        return true;
    }
} 