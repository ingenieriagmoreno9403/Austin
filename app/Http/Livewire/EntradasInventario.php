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
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EntradasInventario extends Component
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
    public $hasChanges = false;
    public $modoSoloVista = false;
    /** true cuando aún no existe registro en BD (formulario nuevo) */
    public $esNueva = false;

    protected $listeners = ['confirmarSalida', 'destroy'];

    public function mount($entradaId = null, $ordenCompraId = null, $modoSoloVista = false)
    {
        $this->modoSoloVista = (bool) $modoSoloVista;
        $this->fechaRecepcion = date('Y-m-d');

        if (!empty($entradaId)) {
            $this->cargarEntradaExistente((int) $entradaId);
            return;
        }

        if (!empty($ordenCompraId)) {
            $this->cargarDesdeOrden((int) $ordenCompraId);
            return;
        }

        abort(404, 'No se indicó la recepción ni la orden de compra.');
    }

    protected function asignarAlmacenUbicacionPorDefecto(): void
    {
        if (!empty($this->id_almacen)) {
            return;
        }

        $almacenInfo = DB::table('tblalmacenes')->where('estado', 'A')->orderBy('id')->first();
        $this->id_almacen = $almacenInfo ? $almacenInfo->id : null;

        if ($this->id_almacen) {
            $ubicacionInfo = DB::table('tblubicaciones')
                ->where('id_almacen', $this->id_almacen)
                ->orderBy('id')
                ->first();
            $this->id_ubicacion = $ubicacionInfo ? $ubicacionInfo->id : null;
        }
    }

    protected function cargarDesdeOrden(int $ordenCompraId): void
    {
        $orden = OrdenCompra::findOrFail($ordenCompraId);
        $this->ordenCompraId = $orden->id;
        $this->entradaId = null;
        $this->esNueva = true;
        $this->hasChanges = false;
        $this->observaciones = '';

        $this->asignarAlmacenUbicacionPorDefecto();

        $detalles = DB::table('tblordencompra_det as d')
            ->join('tblproductos as p', 'p.id', '=', 'd.producto_id')
            ->where('d.orden_compra_id', $this->ordenCompraId)
            ->select(
                'd.id as detalle_id',
                'd.producto_id',
                'p.nombre as nombre_producto',
                'd.cantidad',
                'd.cantidad_recibida'
            )
            ->get();

        $this->filas = [];
        foreach ($detalles as $detalle) {
            $pendiente = (float) $detalle->cantidad - (float) ($detalle->cantidad_recibida ?? 0);
            if ($pendiente > 0) {
                $this->filas[] = [
                    'detalle_id' => $detalle->detalle_id,
                    'producto_id' => $detalle->producto_id,
                    'nombre_producto' => $detalle->nombre_producto,
                    'cantidad_pedida' => $detalle->cantidad,
                    'cantidad_pendiente' => $pendiente,
                    'cantidad_recibida' => 0,
                    'comentario' => '',
                ];
            }
        }
    }

    protected function cargarEntradaExistente(int $entradaId): void
    {
        $this->entradaId = $entradaId;
        $this->esNueva = false;

        $entrada = EntradaInventario::findOrFail($entradaId);
        $this->ordenCompraId = $entrada->orden_compra_id;
        $this->fechaRecepcion = $entrada->fecha_recepcion;
        $this->observaciones = $entrada->observaciones;

        $this->id_almacen = $entrada->id_almacen ?? null;
        $this->id_ubicacion = $entrada->id_ubicacion ?? null;
        $this->asignarAlmacenUbicacionPorDefecto();

        $hasDetailsWithAmounts = EntradaInventarioDet::where('entrada_inventario_id', $entradaId)
            ->where('cantidad_recibida', '>', 0)
            ->exists();

        // Si ya tiene cantidades guardadas, no tratarla como borrador descartable
        $this->hasChanges = false;

        $detalles = DB::table('tblordencompra_det as d')
            ->join('tblproductos as p', 'p.id', '=', 'd.producto_id')
            ->where('d.orden_compra_id', $this->ordenCompraId)
            ->select(
                'd.id as detalle_id',
                'd.producto_id',
                'p.nombre as nombre_producto',
                'd.cantidad',
                'd.cantidad_recibida'
            )
            ->get();

        $detallesEntrada = [];
        if ($this->modoSoloVista || $hasDetailsWithAmounts) {
            $detallesEntrada = EntradaInventarioDet::where('entrada_inventario_id', $entradaId)
                ->get()
                ->keyBy('producto_id');
        }

        $this->filas = [];
        foreach ($detalles as $detalle) {
            $pendiente = (float) $detalle->cantidad - (float) ($detalle->cantidad_recibida ?? 0);

            $cantidadRecibida = 0;
            $comentario = '';

            if (isset($detallesEntrada[$detalle->producto_id])) {
                $cantidadRecibida = $detallesEntrada[$detalle->producto_id]->cantidad_recibida;
                $comentario = $detallesEntrada[$detalle->producto_id]->comentario;
            }

            if ($pendiente > 0 || ($this->modoSoloVista && $cantidadRecibida > 0)) {
                $this->filas[] = [
                    'detalle_id' => $detalle->detalle_id,
                    'producto_id' => $detalle->producto_id,
                    'nombre_producto' => $detalle->nombre_producto,
                    'cantidad_pedida' => $detalle->cantidad,
                    'cantidad_pendiente' => $pendiente,
                    'cantidad_recibida' => $cantidadRecibida,
                    'comentario' => $comentario,
                ];
            }
        }
    }

    public function getAlmacenesProperty()
    {
        return Almacenes::query()
            ->where(function ($query) {
                $query->where('estado', 'A');
                if (!empty($this->id_almacen)) {
                    $query->orWhere('id', $this->id_almacen);
                }
            })
            ->orderBy('folio_interno')
            ->get();
    }

    public function getUbicacionesProperty()
    {
        return Ubicaciones::where('id_almacen', $this->id_almacen)
            ->orderBy('folio_interno')
            ->get();
    }

    public function updatedIdAlmacen($value)
    {
        $this->id_ubicacion = null;
        $ubicacion = Ubicaciones::where('id_almacen', $value)->first();
        if ($ubicacion) {
            $this->id_ubicacion = $ubicacion->id;
        }
        
        $this->hasChanges = true;
        $this->emit('propertyChanged');
    }

    public function updatedIdUbicacion()
    {
        $this->hasChanges = true;
        $this->emit('propertyChanged');
    }

    public function updatedFilas($value, $name) 
    {
        $this->hasChanges = true;
        $this->emit('propertyChanged');
    }

    public function updatedFechaRecepcion() 
    {
        $this->hasChanges = true;
        $this->emit('propertyChanged');
    }

    public function updatedObservaciones() 
    {
        $this->hasChanges = true;
        $this->emit('propertyChanged');
    }

    public function updatedFilasCantidadRecibida($value, $index)
    {
        $cantidadPedida = $this->filas[$index]['cantidad_pendiente'];
        $this->hasChanges = true;
        $this->emit('propertyChanged');

        if ($value > $cantidadPedida) {
            $this->filas[$index]['cantidad_recibida'] = $cantidadPedida;
            $this->emit('errorEvent', 'No puedes recibir más de lo pedido.');
        } elseif ($value < 0) {
            $this->filas[$index]['cantidad_recibida'] = 0;
            $this->emit('errorEvent', 'La cantidad no puede ser negativa.');
        }
    }

    public function confirmarSalida($ruta)
    {
        $destino = $ruta ?: route('Entradas.index');

        // Solo limpia borradores vacíos ya persistidos (recepciones antiguas sin cantidades).
        // Las recepciones nuevas ya no se crean hasta Guardar.
        if ($this->entradaId && !$this->modoSoloVista && !$this->esNueva) {
            $tieneRecepcion = EntradaInventarioDet::where('entrada_inventario_id', $this->entradaId)
                ->where('cantidad_recibida', '>', 0)
                ->exists();

            if (!$tieneRecepcion) {
                DB::transaction(function () {
                    EntradaInventarioDet::where('entrada_inventario_id', $this->entradaId)->delete();
                    EntradaInventario::where('id', $this->entradaId)->delete();
                });
            }
        }

        return redirect()->to($destino);
    }

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

        try {
            DB::transaction(function () {
            if (empty($this->entradaId)) {
                $entrada = EntradaInventario::create([
                    'orden_compra_id' => $this->ordenCompraId,
                    'fecha_recepcion' => $this->fechaRecepcion ?: now(),
                    'observaciones' => $this->observaciones ?? '',
                    'status' => 'pendiente',
                    'id_almacen' => $this->id_almacen,
                    'id_ubicacion' => $this->id_ubicacion,
                ]);

                $this->entradaId = $entrada->id;
                $this->esNueva = false;

                foreach ($this->filas as $fila) {
                    EntradaInventarioDet::create([
                        'entrada_inventario_id' => $this->entradaId,
                        'producto_id' => $fila['producto_id'],
                        'cantidad_pedida' => $fila['cantidad_pedida'] ?? $fila['cantidad_pendiente'],
                        'cantidad_recibida' => 0,
                        'comentario' => $fila['comentario'] ?? '',
                    ]);
                }
            }

            $entrada = EntradaInventario::findOrFail($this->entradaId);
            $entrada->update([
                'fecha_recepcion' => $this->fechaRecepcion,
                'observaciones' => $this->observaciones,
                'id_almacen' => $this->id_almacen,
                'id_ubicacion' => $this->id_ubicacion,
            ]);

            foreach ($this->filas as $fila) {
                // Omitir filas donde la cantidad recibida es cero
                if ($fila['cantidad_recibida'] <= 0) {
                    continue;
                }

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
                        'cantidad_pedida' => $fila['cantidad_pedida'] ?? $fila['cantidad_pendiente'],
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
            // Obtener todos los detalles de la orden para validar y debugging
            $detallesOrden = DB::table('tblordencompra_det')
                ->where('orden_compra_id', $this->ordenCompraId)
                ->select('id', 'producto_id', 'cantidad', 'cantidad_recibida')
                ->get();
                
            Log::info("Verificación de cierre para orden #{$this->ordenCompraId}");
            
            $tieneProductosFaltantes = false;
            foreach ($detallesOrden as $detOrden) {
                $recibido = (float) ($detOrden->cantidad_recibida ?? 0);
                $faltante = (float) $detOrden->cantidad - $recibido;
                Log::info("Producto ID: {$detOrden->producto_id}, Cantidad: {$detOrden->cantidad}, Recibido: {$recibido}, Faltante: {$faltante}");
                
                if ($faltante > 0) {
                    $tieneProductosFaltantes = true;
                }
            }
            
            $faltan = DB::table('tblordencompra_det')
                ->where('orden_compra_id', $this->ordenCompraId)
                ->whereRaw('cantidad > COALESCE(cantidad_recibida, 0)')
                ->exists();
                
            Log::info("¿Faltan productos por recibir (consulta)? " . ($faltan ? 'SI' : 'NO'));
            Log::info("¿Faltan productos por recibir (foreach)? " . ($tieneProductosFaltantes ? 'SI' : 'NO'));

            // Usar la validación manual en lugar de la consulta para mayor seguridad
            if (!$tieneProductosFaltantes) {
                Log::info("Cerrando orden de compra #{$this->ordenCompraId}");
                DB::table('tblordencompra_enc')
                    ->where('id', $this->ordenCompraId)
                    ->update(['estado' => 'cerrada']);
            } else {
                Log::info("No se cierra la orden #{$this->ordenCompraId} porque aún hay productos pendientes");
            }
            
            // Registrar movimientos de inventario para cada producto recibido
            $this->registrarMovimientosInventario($entrada);
            });
        } catch (\Exception $e) {
            Log::error('Error al guardar recepción: ' . $e->getMessage());
            $mensaje = 'No se pudo guardar la recepción. Intenta de nuevo.';
            if (config('app.debug')) {
                $mensaje .= ' ' . $e->getMessage();
            }
            $this->emit('errorEvent', $mensaje);
            return;
        }

        // Reiniciar el indicador de cambios después de guardar correctamente
        $this->hasChanges = false;
        $this->esNueva = false;
        
        session()->flash('success_msg', 'Recepción guardada correctamente.');
        return redirect()->route('Entradas.index');
    }
    
    /**
     * Registra los movimientos de inventario para cada producto en la entrada
     * 
     * @param EntradaInventario $entrada
     * @return void
     */
    protected function registrarMovimientosInventario($entrada)
    {
        // try {
            // Obtener detalles de la entrada que tienen cantidades recibidas
            $detalles = EntradaInventarioDet::where('entrada_inventario_id', $this->entradaId)
                ->where('cantidad_recibida', '>', 0)
                ->get();
                
            if ($detalles->isEmpty()) {
                return; // No hay detalles para procesar
            }
                
            // Datos comunes para todos los movimientos
            $fecha = Carbon::now()->format('Y-m-d');
            $id_tipo_movimiento = 4; // Tipo: Entrada por recepción
            $id_estado_movinv = 2;   // Estado: Completado/Recibido
            $documento_referencia = 'Entrada #' . $this->entradaId;
            
            // Usar el almacén y ubicación seleccionados
            $id_almacen = $this->id_almacen;
            $id_ubicacion = $this->id_ubicacion;
            
            foreach ($detalles as $detalle) {
                // Buscar si ya existe el producto en esa ubicación
                $existencia = $this->Buscaexistenciaporalmacenyubi(
                    $id_almacen, 
                    $id_ubicacion, 
                    $detalle->producto_id
                );
                
                // Registrar el movimiento de inventario
                $observaciones = "Entrada de inventario #{$this->entradaId} - Producto recibido por orden de compra #{$entrada->orden_compra_id}";
                
                $this->Registramovinventario(
                    $detalle->producto_id,
                    $id_almacen,
                    $id_ubicacion,
                    $id_tipo_movimiento,
                    $id_estado_movinv,
                    $detalle->cantidad_recibida,
                    $fecha,
                    $documento_referencia,
                    $observaciones,
                    $fecha,
                    auth()->id(),
                    $this->entradaId
                );
                
                // Si la existencia no existe, crear una nueva
                if ($existencia === 'inserto') {
                    // Crear nueva existencia usando DB en lugar del modelo
                    DB::table('tblexistencias')->insert([
                        'id_producto' => $detalle->producto_id,
                        'id_almacen' => $id_almacen,
                        'id_ubicacion' => $id_ubicacion,
                        'cantidad_existente' => $detalle->cantidad_recibida,
                        'productos_arecibir' => 0,
                        'id_estado_movinv' => $id_estado_movinv,
                        'created_at' => $fecha
                    ]);
                } else {
                    // Actualizar existencia existente
                    $idExistencia = $existencia[0]->id;
                    $cantidadActual = $existencia[0]->cantidad_existente;
                    $nuevaCantidad = $cantidadActual + $detalle->cantidad_recibida;
                    
                    $this->Actualizastock(
                        $idExistencia,
                        $nuevaCantidad,
                        0 // No hay productos en tránsito
                    );
                }
            }
            
            // Actualizar estatus de la entrada a completada
            DB::table('tblentradas_inventario')
                ->where('id', $this->entradaId)
                ->update(['status' => 'cerrada']);
                
        // } catch (\Exception $e) {
        //     Log::error("Error al registrar movimientos de inventario: " . $e->getMessage());
        // }
    }

    // Método de limpieza para cuando el componente es destruido
    public function destroy()
    {
        // Las recepciones nuevas ya no se crean hasta Guardar; no hay borrador que limpiar aquí.
        return;
    }

    public function render()
    {
        $entrada = null;
        if ($this->entradaId) {
            $entrada = EntradaInventario::with(['almacen', 'ubicacion'])->find($this->entradaId);
        }
        
        return view('livewire.entradas-inventario', [
            'entrada' => $entrada
        ]);
    }
}
