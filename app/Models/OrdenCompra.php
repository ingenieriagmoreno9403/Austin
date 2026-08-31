<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenCompra extends Model
{
    use HasFactory;
    protected $table = 'tblordencompra_enc';
    protected $fillable=['id','folio','nombre','fecha_creacion','fecha_limite','fecha_tentativa_pago','descripcion_detalle','comprador_id','proveedor_id','persona_atencion_id','tipo_moneda',
    'condiciones_entrega','observaciones','estado','referencia_licitacion_id','iva_aplicado','ruta_orden_cliente'];
    public $timestamps=true;

    // Constantes para los estados de la orden de compra
    const ESTADO_BORRADOR = 'borrador';
    const ESTADO_REVISION = 'revision';
    const ESTADO_ACEPTADO = 'aceptado';
    const ESTADO_RECHAZADO = 'rechazado';
    const ESTADO_ENVIADO_PROVEEDOR = 'enviado_proveedor';
    const ESTADO_CERRADA = 'cerrada';
    
    // Lista de estados disponibles
    public static $estados = [
        self::ESTADO_BORRADOR => 'Borrador',
        self::ESTADO_REVISION => 'Revisión',
        self::ESTADO_ACEPTADO => 'Aceptado',
        self::ESTADO_RECHAZADO => 'Rechazado',
        self::ESTADO_ENVIADO_PROVEEDOR => 'Enviado al Proveedor',
        self::ESTADO_CERRADA => 'Cerrada'
    ];

    /**
     * Atributos base para órdenes de compra generadas automáticamente (borrador).
     *
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    public static function datosBorradorAutomatico(array $datos): array
    {
        return array_merge([
            'fecha_creacion' => now(),
            'fecha_limite' => now()->addDays(7),
            'estado' => self::ESTADO_BORRADOR,
            'tipo_moneda' => 'MXN',
            'comprador_id' => auth()->user()->idempleado ?? 4,
        ], $datos);
    }

    public function detalles()
    {
        return $this->hasMany(OrdenCompra_det::class);
    }

    // relacion con el modelo proveedor, una orden de compra pertenece a un proveedor
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }
    
    // Relación con la persona de atención del proveedor
    public function personaAtencion()
    {
        return $this->belongsTo(ProveedoresAtencion::class, 'persona_atencion_id');
    }
    
    // Relación con el empleado comprador
    public function comprador()
    {
        return $this->belongsTo(Empleados::class, 'comprador_id');
    }
    
    // Relación con la licitación original
    public function licitacion()
    {
        return $this->belongsTo(Licitacion::class, 'referencia_licitacion_id');
    }
    
    // Relación con las entradas de inventario
    public function entradasInventario()
    {
        return $this->hasMany(EntradaInventario::class, 'orden_compra_id');
    }

    public function deudaPagar()
    {
        return $this->hasOne(DeudaPagar::class, 'orden_compra_id')->where('estado', '!=', 'anulada');
    }
    
    // Estado legible
    public function getEstadoLegibleAttribute()
    {
        return self::$estados[$this->estado] ?? 'Desconocido';
    }
    
    // Porcentaje de productos recibidos
    public function getPorcentajeRecibidoAttribute()
    {
        $detalles = $this->detalles;
        
        if ($detalles->isEmpty()) {
            return 0;
        }
        
        $totalPedido = $detalles->sum('cantidad');
        $totalRecibido = $detalles->sum('cantidad_recibida');
        
        return $totalPedido > 0 ? round(($totalRecibido / $totalPedido) * 100) : 0;
    }
}
