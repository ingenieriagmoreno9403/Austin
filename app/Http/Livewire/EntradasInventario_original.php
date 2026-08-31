<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\EntradaInventario;
use App\Models\EntradaInventarioDet;
use Illuminate\Support\Facades\DB;

class EntradasInventario_original extends Component
{
    public $entradaId;
    public $ordenCompraId;
    public $productos = [];
    public $filas = [];
    public $fechaRecepcion;
    public $observaciones;

    public function mount($entradaId)
    {
        $this->entradaId = $entradaId;

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
        DB::transaction(function () {
            $entrada = EntradaInventario::findOrFail($this->entradaId);
            $entrada->update([
                'fecha_recepcion' => $this->fechaRecepcion,
                'observaciones' => $this->observaciones,
            ]);

            foreach ($this->filas as $fila) {
                $detalle = EntradaInventarioDet::where('entrada_inventario_id', $this->entradaId)
                    ->where('producto_id', $fila['producto_id'])
                    ->first();

                if ($detalle) {
                    $nuevaCantidad = $fila['cantidad_recibida'];
                    $anteriorCantidad = $detalle->cantidad_recibida;
                    $diferencia = $nuevaCantidad - $anteriorCantidad;

                    // Validar que la cantidad acumulada no quede negativa
                    $detalleOrden = DB::table('tblordencompra_det')
                        ->where('id', $fila['detalle_id'])
                        ->first();

                    if (!$detalleOrden) {
                        throw new \Exception('Detalle de orden no encontrado.');
                    }

                    $nuevoAcumulado = $detalleOrden->cantidad_recibida + $diferencia;

                    if ($nuevoAcumulado < 0) {
                        throw new \Exception("No puedes reducir la cantidad recibida por debajo de cero para el producto {$fila['nombre_producto']}.");
                    }

                    // Actualizar detalle
                    $detalle->update([
                        'cantidad_recibida' => $nuevaCantidad,
                        'comentario' => $fila['comentario'],
                    ]);

                    // Ajustar en orden de compra
                    DB::table('tblordencompra_det')
                        ->where('id', $fila['detalle_id'])
                        ->update([
                            'cantidad_recibida' => $nuevoAcumulado
                        ]);
                } else {
                    // Si no existía, crear nuevo
                    EntradaInventarioDet::create([
                        'entrada_inventario_id' => $this->entradaId,
                        'producto_id' => $fila['producto_id'],
                        'cantidad_pedida' => $fila['cantidad_pendiente'],
                        'cantidad_recibida' => $fila['cantidad_recibida'],
                        'comentario' => $fila['comentario'],
                    ]);

                    // Sumar a la orden
                    DB::table('tblordencompra_det')
                        ->where('id', $fila['detalle_id'])
                        ->increment('cantidad_recibida', $fila['cantidad_recibida']);
                }
            }

            // Verificar si la orden se debe cerrar
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

        session()->flash('message', 'Recepción actualizada correctamente.');
        return redirect()->route('Entradas.index');
    }


    public function render()
    {
        return view('livewire.entradas-inventario');
    }
}
