<?php

namespace App\Http\Controllers;

use App\Traits\MenuTrait;
use App\Models\OrdenCompra;
use App\Services\OrdenCompraFinanzasService;
use App\Traits\GlobalTraits;
use Illuminate\Http\Request;
use App\Traits\SistemasTraits;
use Illuminate\Support\Carbon;
use App\Traits\DatosimpleTraits;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;


class  OrdCompraController extends Controller
{
    use MenuTrait;
    use DatosimpleTraits;
    use SistemasTraits;
    use GlobalTraits;

    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index()
    {
        try {
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();


            $varlista = OrdenCompra::all();
            $detalle = OrdenCompra::with('detalles')->get();

            $date = Carbon::now();
            $date = $date->format('Y-m-d');
            $idusuario = auth()->user()->id;

            $licitacionesDisponibles = DB::table('tbllicitacion_enc')
            ->where('estado', 'adjudicada')
            ->whereNotIn('id', function ($query) {
                $query->select('referencia_licitacion_id')
                      ->from('tblordencompra_enc')
                      ->whereNotNull('referencia_licitacion_id');
            })
            // Incluir licitaciones que tienen adjudicaciones por producto o proveedor único
            ->where(function($query) {
                $query->whereExists(function($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('tbllicitacion_adjudicaciones')
                        ->whereColumn('tbllicitacion_adjudicaciones.licitacion_id', 'tbllicitacion_enc.id');
                })
                ->orWhereNotNull('proveedor_adjudicado_id');
            })
            ->get();

            $permiso1 =   $this->forpermisos("acargo_ordenesCompra");
            $permiso2 =   $this->forpermisos("revision_ordenesCompra");

            return view('OrdenCompra.index', compact('varpantallas', 'varsubmenus',
             'varlista', 'detalle','licitacionesDisponibles','date','idusuario','permiso1','permiso2'));
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function create()
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $obtenerempleados = $this->obtenerempleados();

            return view('OrdenCompra.create', compact('varpantallas', 'varsubmenus','obtenerempleados'));
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "No se pudo cargar la vista correctamente");
        }
    }

    public function store(Request $request)
    {
        try {
            if(!is_null($request->persona_atencion_id) && !is_null($request->proveedor_id)){
                // Manejar la subida del archivo PDF si existe
                $rutaArchivo = null;
                $nombreArchivo = null;
                if ($request->hasFile('archivo_orden_cliente')) {
                    $archivo = $request->file('archivo_orden_cliente');
                    
                    // Validar que sea PDF
                    if ($archivo->getClientOriginalExtension() !== 'pdf') {
                        return back()->with("error_msg", "El archivo debe ser un PDF.");
                    }
                    
                    // Eliminar archivo anterior si existe
                    if ($OrdenCompra->ruta_orden_cliente) {
                        $rutaAnterior = public_path('OC_CLIENTES/',$OrdenCompra->ruta_orden_cliente);
                        if (file_exists($rutaAnterior)) {
                            unlink($rutaAnterior);
                        }
                    }
                    
                    // Crear directorio si no existe
                    $directorio = public_path('/OC_CLIENTES'); 
                    if (!file_exists($directorio)) {
                        mkdir($directorio, 0755, true);
                    }
                    
                    
                    // Actualizar la ruta en el request
                    $nombreArchivo =  'OC_' . $OrdenCompra->folio . '_' . time() . '.' .$archivo->guessExtension();   
                    $ruta = public_path("OC_CLIENTES/".$nombreArchivo);
                    copy($archivo, $ruta);
                   
                    
                }
                
                // Crear la orden de compra
                $OrdenCompra = OrdenCompra::create([
                    'folio' => $request->folio,
                    'nombre' => $request->nombre,
                    'fecha_creacion' => $request->fecha_creacion,
                    'fecha_limite' => $request->fecha_limite,
                    'fecha_tentativa_pago' => $request->fecha_tentativa_pago,
                    'descripcion_detalle' => $request->descripcion_detalle,
                    'comprador_id' => $request->comprador_id,
                    'proveedor_id' => $request->proveedor_id,
                    'estado' => OrdenCompra::ESTADO_BORRADOR,
                    'referencia_licitacion_id' => $request->referencia_licitacion_id,
                    'persona_atencion_id' => $request->persona_atencion_id,
                    'tipo_moneda' => $request->tipo_moneda ?? 'MXN',
                    'condiciones_entrega' => $request->condiciones_entrega,
                    'observaciones' => $request->observaciones,
                    'iva_aplicado' => $request->iva_aplicado,
                    'ruta_orden_cliente' => $nombreArchivo
                ]);

                // Procesar y guardar los detalles
                if ($request->has('detalle_json')) {
                    $detalles = json_decode($request->detalle_json, true);
                    
                    foreach ($detalles as $item) {
                        // Verificar que el producto_id no esté vacío
                        if (!empty($item['producto_id'])) {
                            $OrdenCompra->detalles()->create([
                                'producto_id' => $item['producto_id'],
                                'cantidad' => $item['cantidad'],
                                'cantidad_recibida' => 0,
                                'observaciones' => $item['observaciones'] ?? '',
                                'costo' => floatval($item['costo'] ?? 0)
                            ]);
                        }
                    }
                }
            }else{
                 return back()->with("error_msg", "La información del proveedor no se acomletado correctamente, asegurese de llenar todo los campos correspondientes.");
            }

            // si existe el objeto, se realizo bien la insercion
            if ($OrdenCompra) {
                return redirect()->route('ordcompras.index')->with("success", "¡Se guardaron los cambios correctamente!");
            } else {
                return redirect()->route('ordcompras.index')->with("warning", "¡No se guardaron los cambios correctamente!");
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warning_msg", "No guardado correctamente: " . $ex->getMessage());

        } catch (\Exception $e) {
            return back()->with("warning_msg", "Error: " . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            // traer la informacion de los datos a enviar a edicion
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();

            $date = Carbon::now();
            $date = $date->format('Y-m-d');

            //$obtenerempleado = $this->obtenerlistaempleadoid($id);
            $OrdenCompra = OrdenCompra::with(['proveedor', 'personaAtencion', 'comprador'])->find($id);
            
            // Obtener lista de empleados activos para el select
            $empleados = \App\Models\Empleados::where('estado', 'A')
                ->orderBy('primer_nombre')
                ->orderBy('apellido_paterno')
                ->get();
            $detalle = $OrdenCompra->detalles->map(function ($item) {
                return [
                    'producto_id' => $item->producto_id,
                    'nombre' => $item->producto->nombre, // Asegúrate de tener la relación
                    'cantidad' => $item->cantidad,
                    'umed' => $item->producto->unidad->nombre, // Asegúrate de tener la relación
                    'observaciones' => $item->observaciones,
                    'costo' => $item->costo
                ];
            })->toArray();
            $permiso1 =   $this->forpermisos("acargo_ordenesCompra");
            $permiso2 =   $this->forpermisos("revision_ordenesCompra");

            return view('OrdenCompra.edit', compact('varpantallas', 'varsubmenus', 'OrdenCompra', 'detalle', 'empleados', 'date','permiso1','permiso2'));
            // $permisos = $this->forpermisos('actualizar_empleados');

            // if($permisos=="actualizar_empleados")
            // {
            
            // }
            // else{
            //     return redirect()->route('verempleados')->with("Errorpermisos","No se logro");
            // }
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function show($id)
    {
        try {
            // traer la informacion de los datos a enviar a edicion
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();

            $date = Carbon::now();
            $date = $date->format('Y-m-d');

            //$obtenerempleado = $this->obtenerlistaempleadoid($id);
            // buscar la orden de compra, sus detalle, el proveedor y la persona de atención
            $OrdenCompra = OrdenCompra::with(['proveedor', 'personaAtencion', 'detalles.producto.unidad', 'deudaPagar.pagos.creador'])->find($id);
            $detalle = $OrdenCompra->detalles->map(function ($item) {
                $costo = floatval($item->costo);
                $cantidad = floatval($item->cantidad);
                $subtotal = $costo * $cantidad;
                
                return [
                    'producto_id' => $item->producto_id,
                    'nombre' => $item->producto->nombre, // Asegúrate de tener la relación
                    'cantidad' => $item->cantidad,
                    'umed' => $item->producto->unidad->nombre, // Asegúrate de tener la relación
                    'observaciones' => $item->observaciones,
                    'costo' => $costo,
                    'subtotal' => $subtotal
                ];
            })->toArray();
            // $permisos = $this->forpermisos('actualizar_empleados');

            // if($permisos=="actualizar_empleados")
            // {
            $permiso1 =   $this->forpermisos("acargo_ordenesCompra");
            $permiso2 =   $this->forpermisos("revision_ordenesCompra");

            $finanzasService = new OrdenCompraFinanzasService();
            $totalesOc = $finanzasService->calcularTotales($OrdenCompra);
            $deudaPagar = $finanzasService->deudaActivaDeOrden($OrdenCompra);
            $resumenDeuda = $finanzasService->resumenDeuda($deudaPagar);
            $abonosDeuda = $deudaPagar
                ? $deudaPagar->pagos()->with('creador')->orderByDesc('fecha')->orderByDesc('id')->get()
                : collect();
            $cuentasBancarias = DB::table('tblcuentas')->select('id', 'nombre', 'saldo_actual')->orderBy('nombre')->get();

            return view('OrdenCompra.show', compact(
                'varpantallas',
                'varsubmenus',
                'OrdenCompra',
                'detalle',
                'date',
                'permiso1',
                'permiso2',
                'totalesOc',
                'deudaPagar',
                'resumenDeuda',
                'abonosDeuda',
                'cuentasBancarias'
            ));
            // }
            // else{
            //     return redirect()->route('verempleados')->with("Errorpermisos","No se logro");
            // }
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // traer la informacion de los datos a enviar a edicion
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();

            $date = Carbon::now();
            $date = $date->format('Y-m-d');
           
            //$obtenerempleado = $this->obtenerlistaempleadoid($id);
            if(!is_null($request->persona_atencion_id) && !is_null($request->proveedor_id)){
                $OrdenCompra = OrdenCompra::find($id);
                $nombreArchivo = null;
                // Manejar la subida del archivo PDF si existe
                if ($request->hasFile('archivo_orden_cliente')) {
                    $archivo = $request->file('archivo_orden_cliente');
                    
                    // Validar que sea PDF
                    if ($archivo->getClientOriginalExtension() !== 'pdf') {
                        return back()->with("error_msg", "El archivo debe ser un PDF.");
                    }
                    
                    // Eliminar archivo anterior si existe
                    if ($OrdenCompra->ruta_orden_cliente) {
                        $rutaAnterior = public_path('OC_CLIENTES/',$OrdenCompra->ruta_orden_cliente);
                        if (file_exists($rutaAnterior)) {
                            unlink($rutaAnterior);
                        }
                    }
                    
                    // Crear directorio si no existe
                    $directorio = public_path('/OC_CLIENTES'); 
                    if (!file_exists($directorio)) {
                        mkdir($directorio, 0755, true);
                    }
                    
                    
                    // Actualizar la ruta en el request
                    $nombreArchivo =  'OC_' . $OrdenCompra->folio . '_' . time() . '.' .$archivo->guessExtension();   
                    $ruta = public_path("OC_CLIENTES/".$nombreArchivo);
                    copy($archivo, $ruta);
                    
                }
                
                $OrdenCompra->update($request->all());
                $OrdenCompra->update(['ruta_orden_cliente' => $nombreArchivo]);

                // Validar que el detalle_json existe y es válido
                if (!$request->has('detalle_json') || empty($request->detalle_json)) {
                    return back()->with("warningBD", "No se recibió el detalle de productos");
                }

                $detalle = json_decode($request->detalle_json, true);
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($detalle)) {
                    return back()->with("warningBD", "El formato del detalle de productos no es válido");
                }

                // Guardar detalles
                $OrdenCompra->detalles()->delete();
                foreach ($detalle as $item) {
                    if (!empty($item['producto_id'])) {
                        $OrdenCompra->detalles()->create([
                            'producto_id' => $item['producto_id'],
                            'cantidad' => $item['cantidad'],
                            'cantidad_recibida' => 0,
                            'observaciones' => $item['observaciones'] ?? '',
                            'costo' => floatval($item['costo'] ?? 0)
                        ]);
                    }
                }
            
                if ($OrdenCompra) {
                    return back()->with("success", "¡Se guardaron los cambios correctamente!");
                } else {
                    return back()->with("warning", "¡No se guardaron los cambios correctamente!");
                }

            }else{
                 return back()->with("error_msg", "La información del proveedor no se acomletado correctamente, asegurese de llenar todo los campos correspondientes.");
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warning_msg", "No guardado correctamente: " . $ex->getMessage());

        } catch (\Exception $e) {
            return back()->with("warning_msg", "Error: " . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $OrdenCompra = OrdenCompra::find($id);

            if (!$OrdenCompra) {
                return back()->with('warning_msg', 'La orden de compra no existe.');
            }

            if ($OrdenCompra->estado === OrdenCompra::ESTADO_CERRADA) {
                return back()->with('warning_msg', 'No se puede eliminar una orden de compra cerrada.');
            }

            $OrdenCompra->delete();
            return redirect()->route('ordcompras.index')->with("success", "¡Se elimino correctamente!");
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }


    public function generarDesdeLicitacion(Request $request)
    {
        $request->validate([
            'licitacion_id' => 'required|integer|min:1',
        ]);

        $licitacion_id = $request->input('licitacion_id');

        try {
            // Obtener la licitación primero para realizar validaciones adicionales
            $licitacion = DB::table('tbllicitacion_enc')
                ->where('id', $licitacion_id)
                ->first();
                
            if (!$licitacion) {
                return redirect()->route('ordcompras.index')->with('errorgenordencompra', 'La licitación no existe');
            }
            
            if ($licitacion->estado != 'adjudicada') {
                return redirect()->route('ordcompras.index')->with('errorgenordencompra', 'Solo se pueden generar órdenes de compra desde licitaciones adjudicadas');
            }
            
            // Verificar que la licitación tenga productos
            $productosLicitacion = DB::table('tbllicitacion_det')
                ->where('licitacion_id', $licitacion_id)
                ->count();
                
            if ($productosLicitacion == 0) {
                return redirect()->route('ordcompras.index')->with('errorgenordencompra', 'La licitación no tiene productos');
            }
            
            // Verificar si ya existe una orden de compra para esta licitación
            $ordenExistente = DB::table('tblordencompra_enc')
                ->where('referencia_licitacion_id', $licitacion_id)
                ->first();
                
            if ($ordenExistente) {
                return redirect()->route('ordcompras.index')->with('errorgenordencompra', 'Ya existen órdenes de compra para esta licitación');
            }

            // Verificar si existen adjudicaciones por producto
            $adjudicaciones = DB::table('tbllicitacion_adjudicaciones')
                ->where('licitacion_id', $licitacion_id)
                ->get();

            DB::beginTransaction();
            
            try {
                $ordenesCreadas = [];
                
                if ($adjudicaciones->isNotEmpty()) {
                    // Generar órdenes de compra por proveedor basadas en adjudicaciones
                    $adjudicacionesPorProveedor = $adjudicaciones->groupBy('proveedor_id');
                    
                    foreach ($adjudicacionesPorProveedor as $proveedorId => $productos) {
                        // Verificar que el proveedor exista y esté activo
                        $proveedor = DB::table('tblprovedores')
                            ->where('id', $proveedorId)
                            ->first();
                            
                        if (!$proveedor) {
                            throw new \Exception("El proveedor ID {$proveedorId} no existe");
                        }
                        
                        if (isset($proveedor->estado) && $proveedor->estado == 'inactivo') {
                            throw new \Exception("El proveedor {$proveedor->nombre} está inactivo");
                        }
                        
                        // Crear orden de compra para este proveedor
                        $nuevaOrden = OrdenCompra::create([
                            'folio' => $licitacion->folio . '-' . $proveedorId,
                            'nombre' => $licitacion->nombre . ' - ' . $proveedor->nombre,
                            'fecha_creacion' => now(),
                            'fecha_limite' => $licitacion->fecha_limite,
                            'descripcion_detalle' => $licitacion->descripcion_detalle,
                            'proveedor_id' => $proveedorId,
                            'comprador_id' => auth()->user()->idempleado ?? 4,  //4
                            'estado' => OrdenCompra::ESTADO_BORRADOR,
                            'referencia_licitacion_id' => $licitacion_id
                        ]);
                        
                        // Agregar detalles de productos adjudicados a este proveedor
                        foreach ($productos as $adjudicacion) {
                            $nuevaOrden->detalles()->create([
                                'producto_id' => $adjudicacion->producto_id,
                                'cantidad' => $adjudicacion->cantidad_adjudicada,
                                'cantidad_recibida' => 0,
                                'costo' => $adjudicacion->precio_unitario,
                                'observaciones' => $adjudicacion->observaciones
                            ]);
                        }
                        
                        $ordenesCreadas[] = $nuevaOrden->id;
                    }
                } else {
                    // Método anterior: adjudicación a un solo proveedor
                    if (!$licitacion->proveedor_adjudicado_id) {
                        return redirect()->route('ordcompras.index')->with('errorgenordencompra', 'La licitación no tiene adjudicaciones por producto ni proveedor único');
                    }
                    
                    // Verificar que el proveedor exista y esté activo
                    $proveedor = DB::table('tblprovedores')
                        ->where('id', $licitacion->proveedor_adjudicado_id)
                        ->first();
                        
                    if (!$proveedor) {
                        return redirect()->route('ordcompras.index')->with('errorgenordencompra', 'El proveedor adjudicado no existe');
                    }
                    
                    if (isset($proveedor->estado) && $proveedor->estado == 'inactivo') {
                        return redirect()->route('ordcompras.index')->with('errorgenordencompra', 'El proveedor adjudicado está inactivo');
                    }
                    
                    // Verificar que los productos tengan precios asignados por el proveedor adjudicado
                    $productosSinPrecio = DB::table('tbllicitacion_det as ld')
                        ->leftJoin('tbllicitacionproducto_proveedor as lpp', function($join) use ($licitacion) {
                            $join->on('lpp.licitacion_id', '=', 'ld.licitacion_id')
                                ->on('lpp.producto_id', '=', 'ld.producto_id')
                                ->where('lpp.proveedor_id', '=', $licitacion->proveedor_adjudicado_id);
                        })
                        ->where('ld.licitacion_id', $licitacion_id)
                        ->where(function($query) {
                            $query->whereNull('lpp.costo')
                                  ->orWhere('lpp.costo', 0);
                        })
                        ->count();
                        
                    if ($productosSinPrecio > 0) {
                        return redirect()->route('ordcompras.index')->with('errorgenordencompra', 'Hay productos sin precio asignado por el proveedor adjudicado');
                    }
                    
                    // Usar el procedimiento almacenado original
                    DB::statement('CALL generar_orden_compra_desde_licitacion(?,?)', [$licitacion_id, $request->get("folio")]);
                    
                    // Obtener la orden de compra recién creada
                    $nuevaOrden = DB::table('tblordencompra_enc')
                        ->where('referencia_licitacion_id', $licitacion_id)
                        ->first();
                    
                    if (!$nuevaOrden) {
                        throw new \Exception('No se pudo crear la orden de compra');
                    }
                    
                    $ordenesCreadas[] = $nuevaOrden->id;
                }
                
                DB::commit();
                
                if (count($ordenesCreadas) > 1) {
                    return redirect()->route('ordcompras.index')->with('successgenordencompra', 
                        'Se generaron ' . count($ordenesCreadas) . ' órdenes de compra exitosamente desde Licitación #' . $licitacion_id);
                } else {
                    return redirect()->route('ordcompras.index')->with('successgenordencompra', 
                        'Orden de Compra #' . $ordenesCreadas[0] . ' generada exitosamente desde Licitación #' . $licitacion_id);
                }
                
            } catch (\Exception $innerEx) {
                DB::rollBack();
                throw $innerEx;
            }
            
        } catch (\Exception $e) {
            $errorInfo = $e->getMessage();
            return redirect()->route('ordcompras.index')->with('errorgenordencompra', $errorInfo);
        }
    }
    
    /**
     * Cambiar el estado de la orden de compra
     */
    public function cambiarEstado(Request $request, $id)
    {
       
        $request->validate([
            'estado' => 'required|string|in:' . implode(',', array_keys(OrdenCompra::$estados)),
        ]);
        
        
        try {

            $ordenCompra = OrdenCompra::findOrFail($id);
            $estadoAnterior = $ordenCompra->estado;
            $nuevoEstado = $request->estado;
            
            // Verificar si el cambio de estado es válido
           $cambiosPermitidos = $this->cambiosEstadoPermitidos();
            
            if (!isset($cambiosPermitidos[$estadoAnterior]) || !in_array($nuevoEstado, $cambiosPermitidos[$estadoAnterior])) {
                return back()->with('warning_msg', "No se puede cambiar de '{$estadoAnterior}' a '{$nuevoEstado}'");
            }
             
            
            // Actualizar el estado
            $ordenCompra->estado = $nuevoEstado;
            $ordenCompra->save();

            $extra = '';
            if ($nuevoEstado === OrdenCompra::ESTADO_ENVIADO_PROVEEDOR) {
                $deuda = (new OrdenCompraFinanzasService())->crearDeudaDesdeOrden($ordenCompra->fresh(['detalles', 'proveedor']));
                if ($deuda) {
                    $extra = ' Se registró la deuda por pagar #' . $deuda->id
                        . ' por $' . number_format((float) $deuda->monto, 2)
                        . ' con vencimiento ' . Carbon::parse($deuda->fecha_vencimiento)->format('d/m/Y') . '.';
                } else {
                    $extra = ' No se generó deuda por pagar: el total de la orden es $0.00. Capture costos en las partidas.';
                }
            }
            
            return back()->with(
                'success_msg', 
                "Estado actualizado correctamente a " . OrdenCompra::$estados[$nuevoEstado] . $extra
            );
            
        } catch (\Exception $e) {
            return back()->with('warning_msg', "Error al cambiar el estado: " . $e->getMessage());
        }
    }
    
    /**
     * Define los cambios de estado permitidos según el flujo de trabajo
     */
    private function cambiosEstadoPermitidos()
    {
        return [
            OrdenCompra::ESTADO_BORRADOR => [
                OrdenCompra::ESTADO_REVISION
            ],
            OrdenCompra::ESTADO_REVISION => [
                OrdenCompra::ESTADO_ACEPTADO,
                OrdenCompra::ESTADO_RECHAZADO
            ],
            OrdenCompra::ESTADO_ACEPTADO => [
                OrdenCompra::ESTADO_ENVIADO_PROVEEDOR,
                OrdenCompra::ESTADO_CERRADA
            ],
            OrdenCompra::ESTADO_RECHAZADO => [
                OrdenCompra::ESTADO_BORRADOR
            ],
            OrdenCompra::ESTADO_ENVIADO_PROVEEDOR => [
                OrdenCompra::ESTADO_CERRADA
            ],
            OrdenCompra::ESTADO_CERRADA => []
        ];
    }
    
    /**
     * Genera un PDF de la orden de compra
     */
    public function generarPDF($id)
    {
        try {
            // Obtener la orden de compra con sus relaciones
            $ordenCompra = OrdenCompra::with(['proveedor', 'personaAtencion', 'comprador', 'detalles.producto.unidad'])->findOrFail($id);

            // Calcular totales
            $subtotal = 0;
            $detalles = $ordenCompra->detalles->map(function ($detalle) use (&$subtotal) {
                $costo = floatval($detalle->costo);
                $cantidad = floatval($detalle->cantidad);
                $subtotalDetalle = $costo * $cantidad;
                $subtotal += $subtotalDetalle;

                return [
                    'sku' => $detalle->producto->sku ?? null,
                    'producto' => $detalle->producto->nombre ?? 'Producto',
                    'unidad' => $detalle->producto->unidad->nombre
                        ?? $detalle->unidad_medida
                        ?? 'Unidad',
                    'cantidad' => $cantidad,
                    'costo_unitario' => $costo,
                    'subtotal' => $subtotalDetalle,
                    'observaciones' => $detalle->observaciones,
                ];
            });

            if ($ordenCompra->iva_aplicado == 1) {
                $iva = $subtotal * 0.16;
            } else {
                $iva = 0;
            }

            $total = $subtotal + $iva;

            $monedaCodigo = strtoupper((string) ($ordenCompra->tipo_moneda ?? 'MXN'));
            $monedaNombre = match ($monedaCodigo) {
                'USD' => 'dolares americanos',
                'EUR' => 'euros',
                default => 'pesos',
            };

            $totalEnLetras = null;
            try {
                $formatter = new \Luecano\NumeroALetras\NumeroALetras();
                $entero = (int) floor((float) $total);
                $centavos = (int) round((((float) $total) - $entero) * 100);
                $palabras = $formatter->toWords($entero);
                $totalEnLetras = ucfirst(mb_strtolower($palabras)) . ' ' . $monedaNombre
                    . ' ' . str_pad((string) $centavos, 2, '0', STR_PAD_LEFT) . '/100';
            } catch (\Throwable $e) {
                $totalEnLetras = null;
            }

            $fechaCorta = \Carbon\Carbon::parse($ordenCompra->fecha_creacion)->locale('es')->translatedFormat('d/M./Y');
            $vigenciaCorta = \Carbon\Carbon::parse($ordenCompra->fecha_limite)->locale('es')->translatedFormat('d/M./Y');

            // Empresa del comprador (nómina → empresa; fallback sucursal → empresa)
            $empresaComprador = null;
            $puestoComprador = 'COMPRAS';
            if (!empty($ordenCompra->comprador_id)) {
                $empresaComprador = DB::table('tblnominas')
                    ->join('tblempresas', 'tblempresas.id', '=', 'tblnominas.idempresa')
                    ->where('tblnominas.idempleado', $ordenCompra->comprador_id)
                    ->select('tblempresas.*')
                    ->orderByDesc('tblnominas.id')
                    ->first();

                if (!$empresaComprador) {
                    $empresaComprador = DB::table('tblempleados')
                        ->join('tblsucursales', 'tblsucursales.id', '=', 'tblempleados.idsucursal')
                        ->join('tblempresas', 'tblempresas.id', '=', 'tblsucursales.idempresa')
                        ->where('tblempleados.id', $ordenCompra->comprador_id)
                        ->select('tblempresas.*')
                        ->first();
                }

                $puestoNombre = DB::table('tblempleados')
                    ->leftJoin('tblpuestos', 'tblpuestos.id', '=', 'tblempleados.idpuesto')
                    ->where('tblempleados.id', $ordenCompra->comprador_id)
                    ->value('tblpuestos.nombre');

                if (!empty($puestoNombre)) {
                    $puestoComprador = strtoupper((string) $puestoNombre);
                }
            }

            // Información adicional
            $fechaGeneracion = now()->format('d/m/Y H:i:s');
            $usuario = auth()->user()->name ?? 'Sistema';

            // Generar el PDF
            $pdf = PDF::loadView('OrdenCompra.pdf-reporte', compact(
                'ordenCompra',
                'detalles',
                'subtotal',
                'iva',
                'total',
                'totalEnLetras',
                'fechaCorta',
                'vigenciaCorta',
                'fechaGeneracion',
                'usuario',
                'empresaComprador',
                'puestoComprador'
            ));
            
            $pdf->setPaper('letter', 'portrait');
            
            $nombreArchivo = 'Orden_Compra_' . $ordenCompra->folio . '_' . date('Y-m-d') . '.pdf';
            
            return $pdf->stream($nombreArchivo);
            
        } catch (\Exception $e) {
            return back()->with('warningBD', 'Error al generar el PDF: ' . $e->getMessage());
        }
    }
    
    /**
     * Notifica a usuarios relevantes sobre el cambio de estado
     */
    private function notificarCambioEstado(OrdenCompra $ordenCompra, $estadoAnterior)
    {
        // Buscar usuarios que deben ser notificados (ej: administradores, compras, etc.)
        $usuariosANotificar = User::whereHas('roles', function($query) {
            $query->whereIn('nombre', ['admin', 'compras']);
        })->get();
        
        // Notificar internamente
        foreach ($usuariosANotificar as $usuario) {
            $usuario->notify(new \App\Notifications\OrdenCompraStatusChanged($ordenCompra, $estadoAnterior));
        }
        
        // Si el estado cambió a enviada, notificar al proveedor (si tiene email)
        if ($ordenCompra->estado == OrdenCompra::ESTADO_ENVIADA && 
            $ordenCompra->proveedor && 
            $ordenCompra->proveedor->email) {
            
            // enviar un email directo al proveedor o implementar una notificación específica para proveedores
            \Illuminate\Support\Facades\Mail::to($ordenCompra->proveedor->email)
                ->send(new \App\Mail\OrdenCompraEnviada($ordenCompra));
        }
    }
}
