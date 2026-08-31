<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\EntradaInventario;
use App\Models\EntradaInventarioDet;
use App\Models\Almacenes;
use App\Models\Ubicaciones;
use App\Traits\InventariosTraits;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EntradasInventario_creanuevaentr extends Component
{
    use InventariosTraits;
    
    public $entradaId;
    public $ordenCompraId;
    public $productos = [];
    public $filas = [];
    public $fechaRecepcion;
    public $observaciones;
    public $id_almacen;
    public $id_ubicacion;

    public function mount($entradaId)
    {
        $this->entradaId = $entradaId;
        
        // Cargar valores por defecto
        $this->fechaRecepcion = date('Y-m-d');
        
        // Obtener el primer almacén disponible
        $almacenInfo = DB::table('tblalmacenes')->first();
        $this->id_almacen = $almacenInfo ? $almacenInfo->id : null;
            
        // Si hay un almacén, cargar la primera ubicación
        if ($this->id_almacen) {
            $ubicacionInfo = DB::table('tblubicaciones')
                ->where('id_almacen', $this->id_almacen)
                ->first();
            $this->id_ubicacion = $ubicacionInfo ? $ubicacionInfo->id : null;
        }
        
        $entrada = EntradaInventario::findOrFail($entradaId);
        $this->ordenCompraId = $entrada->orden_compra_id;
        $this->fechaRecepcion = $entrada->fecha_recepcion;
        $this->observaciones = $entrada->observaciones;

        $detalles = DB::table('tblordencompra_det as d')
            ->join('tblproductos as p', 'p.id', '=', 'd.producto_id')
            ->where('d.orden_compra_id', $this->ordenCompraId)
            ->select('d.id as detalle_id', 'd.producto_id', 'p.nombre as nombre_producto', 'd.cantidad', 'd.cantidad_recibida')
            ->get();

        foreach ($detalles as $detalle) {
            $pendiente = $detalle->cantidad - $detalle->cantidad_recibida;

            if ($pendiente > 0) {
                $this->filas[] = [
                    'detalle_id' => $detalle->detalle_id,
                    'producto_id' => $detalle->producto_id,
                    'nombre_producto' => $detalle->nombre_producto,
                    'cantidad_pendiente' => $pendiente,
                    'cantidad_recibida' => 0,
                    'comentario' => '',
                ];
            }
        }
    }
    
    public function getAlmacenesProperty()
    {
        return Almacenes::all();
    }

    public function getUbicacionesProperty()
    {
        return Ubicaciones::where('id_almacen', $this->id_almacen)->get();
    }

    public function updatedIdAlmacen($value)
    {
        $this->id_ubicacion = null;
        $ubicacion = Ubicaciones::where('id_almacen', $value)->first();
        if ($ubicacion) {
            $this->id_ubicacion = $ubicacion->id;
        }
    }

    public function updatedFilas($value, $name) {}

    public function updatedFilasCantidadRecibida($value, $index)
    {
        $cantidadPedida = $this->filas[$index]['cantidad_pedida'];

        if ($value > $cantidadPedida) {
            $this->filas[$index]['cantidad_recibida'] = $cantidadPedida;
            $this->emit('errorEvent', 'No puedes recibir más de lo pedido.');
        } elseif ($value < 0) {
            $this->filas[$index]['cantidad_recibida'] = 0;
            $this->emit('errorEvent', 'La cantidad no puede ser negativa.');
        }
    }

    // public function guardar()
    // {
    //     DB::transaction(function () {
    //         // Actualizar cabecera
    //         $entrada = EntradaInventario::where('orden_compra_id', $this->ordenCompraId)->first();
    //         if ($entrada) {
    //             $entrada->update([
    //                 'fecha_recepcion' => $this->fechaRecepcion,
    //                 'observaciones' => $this->observaciones,
    //             ]);
    //         }

    //         // Actualizar detalles
    //         foreach ($this->filas as $fila) {
    //             $detalle = EntradaInventarioDet::find($fila['id']);
    //             if ($detalle) {
    //                 $detalle->update([
    //                     'cantidad_recibida' => $fila['cantidad_recibida'],
    //                     'comentario' => $fila['comentario'],
    //                 ]);
    //             }
    //         }
    //     });

    //     session()->flash('message', 'Recepción actualizada exitosamente.');
    //     return redirect()->route('Entradas.index');
    // }

    public function guardar()
    {
        // Validar que al menos una fila tenga cantidad_recibida
        $tieneRecepcion = false;
        foreach ($this->filas as $fila) {
            if ($fila['cantidad_recibida'] > 0) {
                $tieneRecepcion = true;
                break;
            }
        }

        if (!$tieneRecepcion) {
            $this->emit('errorEvent', 'Debe recibir al menos un producto para guardar la entrada.');
            return;
        }
        
        // Validar que se haya seleccionado un almacén y una ubicación
        if (empty($this->id_almacen)) {
            $this->emit('errorEvent', 'Debe seleccionar un almacén.');
            return;
        }
        
        if (empty($this->id_ubicacion)) {
            $this->emit('errorEvent', 'Debe seleccionar una ubicación.');
            return;
        }

        DB::transaction(function () {
            // 1. Crear nueva entrada de inventario
            $nuevaEntrada = EntradaInventario::create([
                'orden_compra_id' => $this->ordenCompraId,
                'fecha_recepcion' => $this->fechaRecepcion,
                'observaciones' => $this->observaciones,
                'id_almacen' => $this->id_almacen,
                'id_ubicacion' => $this->id_ubicacion,
            ]);

            // 2. Procesar cada fila de productos
            foreach ($this->filas as $fila) {
                // Validación de cantidad recibida
                if ($fila['cantidad_recibida'] <= 0) {
                    continue; // Ignorar si no se recibió nada
                }

                $detalleOrden = DB::table('tblordencompra_det')
                    ->where('id', $fila['detalle_id'])
                    ->first();

                if (!$detalleOrden) {
                    throw new \Exception("Detalle de orden no encontrado para el producto {$fila['nombre_producto']}.");
                }

                $nuevoAcumulado = $detalleOrden->cantidad_recibida + $fila['cantidad_recibida'];

                if ($nuevoAcumulado > $detalleOrden->cantidad) {
                    throw new \Exception("La cantidad total recibida no puede superar lo pedido para el producto {$fila['nombre_producto']}.");
                }

                // 3. Crear detalle de entrada
                EntradaInventarioDet::create([
                    'entrada_inventario_id' => $nuevaEntrada->id,
                    'producto_id' => $fila['producto_id'],
                    'cantidad_pedida' => $fila['cantidad_pendiente'],
                    'cantidad_recibida' => $fila['cantidad_recibida'],
                    'comentario' => $fila['comentario'],
                ]);

                // 4. Actualizar cantidad recibida acumulada en orden de compra
                DB::table('tblordencompra_det')
                    ->where('id', $fila['detalle_id'])
                    ->update([
                        'cantidad_recibida' => $nuevoAcumulado
                    ]);
            }

            // 5. Verificar si ya se completó la orden
            $faltan = DB::table('tblordencompra_det')
                ->where('orden_compra_id', $this->ordenCompraId)
                ->whereRaw('cantidad > cantidad_recibida')
                ->exists();

            if (!$faltan) {
                DB::table('tblordencompra_enc')
                    ->where('id', $this->ordenCompraId)
                    ->update(['estado' => 'cerrada']);
            }
        });

        session()->flash('message', '¡Nueva entrada de inventario registrada correctamente!');
        return redirect()->route('Entradas.index');
    }

    public function render()
    {
        return view('livewire.entradas-inventario');
    }
}
