<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use App\Traits\MenuTrait;
use App\Models\Licitacion;
use App\Mail\LicitacionMail;
use App\Traits\GlobalTraits;
use Illuminate\Http\Request;
use App\Traits\SistemasTraits;
use Illuminate\Support\Carbon;
use App\Traits\DatosimpleTraits;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Exports\CostosLicitacionExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\ServiciosTrait;
use App\Exports\ComparativaExportar;
use App\Models\LicParamTipo;
use App\Models\LicParamPlantilla;
use App\Models\Requisicion;
use App\Services\LicitacionParametroService;


class LicitacionController extends Controller
{
    use MenuTrait;
    use DatosimpleTraits;
    use SistemasTraits;
    use GlobalTraits;
    use ServiciosTrait;

    protected LicitacionParametroService $paramService;

    public function __construct(LicitacionParametroService $paramService)
    {
        $this->middleware('auth');
        $this->paramService = $paramService;
    }

    public function index(\Illuminate\Http\Request $request)
    {
        try{
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();

            $vista = $request->get('vista', 'activas') === 'historial' ? 'historial' : 'activas';

            $q = Licitacion::query()->orderByDesc('id');
            if ($vista === 'historial') {
                $q->whereIn('estado', Licitacion::ESTADOS_HISTORIAL);
            } else {
                $q->where(function ($w) {
                    $w->whereNotIn('estado', Licitacion::ESTADOS_HISTORIAL)
                        ->orWhereNull('estado');
                });
            }
            $varlista = $q->get();

            $ids = $varlista->pluck('id');
            $conOrden = collect();
            $conteoProveedores = collect();
            if ($ids->isNotEmpty()) {
                $conOrden = DB::table('tblordencompra_enc')
                    ->whereIn('referencia_licitacion_id', $ids)
                    ->pluck('referencia_licitacion_id')
                    ->flip();
                $conteoProveedores = DB::table('tbllicitacion_proveedor')
                    ->select('licitacion_id', DB::raw('COUNT(*) as total'))
                    ->whereIn('licitacion_id', $ids)
                    ->groupBy('licitacion_id')
                    ->pluck('total', 'licitacion_id');
            }
            $solicitudes_pendientes =   $this->solicitudes_pendientes_licitaciones();

            $date = Carbon::now();
            $date = $date->format('Y-m-d');
            $idusuario=auth()->user()->id;

            $tiposParam = LicParamTipo::query()
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre']);

            $kpis = [
                'activas' => Licitacion::query()
                    ->where(function ($w) {
                        $w->whereNotIn('estado', Licitacion::ESTADOS_HISTORIAL)
                            ->orWhereNull('estado');
                    })->count(),
                'historial' => Licitacion::query()
                    ->whereIn('estado', Licitacion::ESTADOS_HISTORIAL)
                    ->count(),
            ];

            return view('Licitacion.index', compact(
                'varpantallas',
                'varsubmenus',
                'varlista',
                'solicitudes_pendientes',
                'conOrden',
                'conteoProveedores',
                'tiposParam',
                'vista',
                'kpis'
            ));
        } catch(\Illuminate\Database\QueryException $ex)
        {
          return back()->with("warningBD","no guardado correctamente");
        }
    }

    public function costos($id)
    {
        $costos = DB::table('tbllicitacionproducto_proveedor as lpp')
            ->join('tblprovedores as p', 'p.id', '=', 'lpp.proveedor_id')
            ->join('tblproductos as pr', 'pr.id', '=', 'lpp.producto_id')
            ->select('p.nombre as proveedor', 'pr.nombre as producto', 'lpp.costo', 'lpp.marca')
            ->where('lpp.licitacion_id', $id)
            ->orderBy('proveedor')
            ->orderBy('producto')
            ->get();

        return view('Licitacion.partials.costos', [
            'costos' => $costos,
            'id' => $id,
        ]);
    }

    public function create()
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();

            return view('Licitacion.create', compact('varpantallas', 'varsubmenus'));
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "No se pudo cargar la vista correctamente");
        }
    }

    // public function store(Request $request){
        //     try{
        //         $Licitacion = Licitacion::create($request->all());

        //         // Guardar detalles
        //         if($Licitacion->detalles()->exists()) {
        //             foreach ($request->detalle as $item) {  // por cada renglon (td) en la tabla detalle
        //                 $Licitacion->detalles()->create([
        //                     'producto' => $item['producto_id'],
        //                     'cantidad' => $item['cantidad'],
        //                     'observaciones' => $item['observaciones']

        //                 ]);
        //             }
        //         }

        //         // si existe el objeto, se realizo bien la insercion
        //         if($Licitacion){
        //             return redirect()->route('licitaciones.index')->with("success","¡Se guardaron los cambios correctamente!");
        //         }else{
        //             return redirect()->route('licitaciones.index')->with("warning","¡No se guardaron los cambios correctamente!");}
        //     } catch(\Illuminate\Database\QueryException $ex)
        //     {  return back()->with("warningBD","no guardado correctamente"); }
    // }

    public function store(Request $request){
        try {
            $payload = $request->only([
                'nombre', 'folio', 'fecha_creacion', 'fecha_limite',
                'descripcion_detalle', 'proveedor_adjudicado_id', 'estado', 'id_servicio', 'tipo_param_id',
            ]);
            if (empty($payload['tipo_param_id'])) {
                $payload['tipo_param_id'] = null;
            }

            $Licitacion = Licitacion::create($payload);

            if (! empty($payload['tipo_param_id'])) {
                $this->paramService->clonarPlantillaALicitacion($Licitacion, (int) $payload['tipo_param_id']);
            }

            // Guardar detalles
            if ($request->has('detalle_json')) {
                $detalle = json_decode($request->detalle_json, true);
                if (is_array($detalle)) {
                    foreach ($detalle as $item) {
                        $Licitacion->detalles()->create([
                            'producto_id' => $item['producto_id'],
                            'cantidad' => $item['cantidad'],
                            'observaciones' => $item['observaciones'] ?? null
                        ]);
                    }
                }
            }

            // Obtener todos los proveedores registrados para esta licitación
            $proveedores = DB::table('tbllicitacion_proveedor')
                ->where('licitacion_id', $Licitacion->id)
                ->pluck('proveedor_id');

            // Insertar productos por cada proveedor en tbllicitacionproducto_proveedor
            if ($request->has('detalle_json') && $proveedores->isNotEmpty()) {
                $detalle = json_decode($request->detalle_json, true);
                if (is_array($detalle)) {
                    foreach ($proveedores as $proveedor_id) {
                        foreach ($detalle as $item) {
                            DB::table('tbllicitacionproducto_proveedor')->insert([
                                'licitacion_id' => $Licitacion->id,
                                'proveedor_id' => $proveedor_id,
                                'producto_id' => $item['producto_id'],
                                'costo' => $item['costo'] ?? 0  // puede venir vacío
                            ]);
                        }
                    }
                }
            }

            // Verificación final
            if ($Licitacion) {
                return redirect()->route('licitaciones.editar', $Licitacion->id)->with("success", "¡Licitación creada! Completa productos, proveedores y parámetros.");
            } else {
                return redirect()->route('licitaciones.index')->with("warning", "¡No se guardaron los cambios correctamente!");
            }

        } catch(\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "No guardado correctamente: " . $ex->getMessage());
        }
    }


    /**
     * Crea una licitación a partir de una requisición autorizada.
     */
    public function storeDesdeRequisicion(Request $request, int $requisicionId)
    {
        $req = Requisicion::query()->with('detalles')->findOrFail($requisicionId);

        if (! in_array($req->estado, ['autorizada', 'en_proceso_compra', 'atendida_parcial'], true)) {
            return back()->with('warning', 'Solo se puede generar licitación desde una requisición autorizada.');
        }

        $yaExiste = Licitacion::query()->where('requisicion_id', $req->id)->first();
        if ($yaExiste) {
            return redirect()
                ->route('licitaciones.editar', $yaExiste->id)
                ->with('warning', 'Ya existe la licitación '.$yaExiste->folio.' para esta requisición.');
        }

        $tipoId = (int) ($request->input('tipo_param_id')
            ?: ($req->tipo_param_id
                ?: (LicParamTipo::query()->where('nombre', 'Tecnologías')->value('id') ?? 0)));

        try {
            $lic = DB::transaction(function () use ($req, $tipoId) {
                $folio = 'LIC-REQ-'.$req->folio;
                if (Licitacion::query()->where('folio', $folio)->exists()) {
                    $folio .= '-'.now()->format('His');
                }

                $tipoNombre = $req->departamento
                    ?: optional(LicParamTipo::query()->find($tipoId))->nombre;

                $lic = Licitacion::query()->create([
                    'folio' => mb_substr($folio, 0, 50),
                    'nombre' => mb_substr('Licitación desde '.$req->folio.($tipoNombre ? ' · '.$tipoNombre : ''), 0, 120),
                    'descripcion_detalle' => $req->justificacion ?: ('Generada desde requisición '.$req->folio),
                    'fecha_creacion' => now()->toDateString(),
                    'fecha_limite' => optional($req->fecha_requerida)->format('Y-m-d') ?: now()->addDays(7)->toDateString(),
                    'estado' => 'borrador',
                    'requisicion_id' => $req->id,
                    'tipo_param_id' => $tipoId ?: null,
                ]);

                foreach ($req->detalles as $d) {
                    if (empty($d->producto_id)) {
                        continue; // solo partidas catalogadas entran a licitación
                    }
                    $lic->detalles()->create([
                        'producto_id' => $d->producto_id,
                        'cantidad' => $d->cantidad,
                        'observaciones' => trim(implode(' · ', array_filter([
                            $d->codigo,
                            $d->especificaciones,
                            $d->motivo,
                        ]))) ?: null,
                    ]);
                }

                if ($tipoId) {
                    $this->paramService->clonarPlantillaALicitacion($lic, $tipoId);
                }

                if ($req->estado === 'autorizada') {
                    $req->estado = 'en_proceso_compra';
                    $req->updated_by = (int) auth()->id();
                    $req->save();
                }

                return $lic;
            });

            return redirect()
                ->route('licitaciones.editar', $lic->id)
                ->with('success', 'Licitación creada desde requisición '.$req->folio.'. Invita proveedores y continúa el proceso.');
        } catch (\Throwable $e) {
            return back()->with('warningBD', 'No se pudo crear la licitación: '.$e->getMessage());
        }
    }

    public function storeSolicitud(Request $request){
        try {
            $Licitacion = Licitacion::create($request->all());
            $id_serv = $request->get("id_servicio");
            $detalle = $this->productos_faltantes($id_serv);

            $Licitacion->update(['folio' => $this->folio_servicio($id_serv)]);

            // Guardar detalles
            if ($detalle->isNotEmpty()) {
                foreach ($detalle as $item) {
                    $Licitacion->detalles()->create([
                        'producto_id' => $item->id_producto,
                        'cantidad' => $item->cantidad_faltante,
                        'observaciones' => null
                    ]);
                }
            }

            $update1 =  DB::select('update tblservicios_enc set otrosconceptos1 = ? where id  = ?;', [$Licitacion->id ,$id_serv]);

            // Obtener todos los proveedores registrados para esta licitación
            $proveedores = DB::table('tbllicitacion_proveedor')
                ->where('licitacion_id', $Licitacion->id)
                ->pluck('proveedor_id');

            // Insertar productos por cada proveedor en tbllicitacionproducto_proveedor
            if ($detalle->isNotEmpty() && $proveedores->isNotEmpty()) {
                    foreach ($proveedores as $proveedor_id) {
                        foreach ($detalle as $item) {
                            DB::table('tbllicitacionproducto_proveedor')->insert([
                                'licitacion_id' => $Licitacion->id,
                                'proveedor_id' => $proveedor_id,
                                'producto_id' => $item->id_producto,
                                'costo' => 0  // puede venir vacío
                            ]);
                        }
                    }
            }

            // Verificación final
            if ($Licitacion) {
                return redirect()->route('licitaciones.index')->with("success", "¡Se guardaron los cambios correctamente!");
            } else {
                return redirect()->route('licitaciones.index')->with("warning", "¡No se guardaron los cambios correctamente!");
            }

        } catch(\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "No guardado correctamente: " . $ex->getMessage());
        }
    }


    public function edit($id){
        try{
            // traer la informacion de los datos a enviar a edicion
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();

            $date = Carbon::now();
            $date = $date->format('Y-m-d');

            $licitacion = Licitacion::find($id);
            
            if (!$licitacion) {
                return back()->with("warningBD", "Licitación no encontrada");
            }

            $detalle = $licitacion->detalles->map(function ($item) {
                // Verificar que el producto exista
                $producto = $item->producto;
                if (!$producto) {
                    return [
                        'producto_id' => $item->producto_id,
                        'nombre' => 'Producto no encontrado',
                        'cantidad' => $item->cantidad,
                        'umed' => 'N/A',
                        'observaciones' => $item->observaciones
                    ];
                }
                
                // Verificar que la unidad de medida exista
                $unidadNombre = $producto->unidad->nombre ?? 'N/A';
                
                return [
                    'producto_id' => $item->producto_id,
                    'nombre' => $producto->nombre, 
                    'cantidad' => $item->cantidad,
                    'umed' => $unidadNombre,
                    'observaciones' => $item->observaciones
                ];
            })->toArray();

            $proveedoresInvitados = DB::table('tbllicitacion_proveedor')
                ->join('tblprovedores', 'tbllicitacion_proveedor.proveedor_id', '=', 'tblprovedores.id')
                ->where('tbllicitacion_proveedor.licitacion_id', $id)
                ->select('tblprovedores.id', 'tblprovedores.nombre')
                ->get();

            $licitacion->load(['parametrosEval', 'tipoParam']);
            $tiposParam = LicParamTipo::query()->where('activo', true)->orderBy('nombre')->get();
            $sentidos = LicParamPlantilla::SENTIDOS;
            $tiposDato = LicParamPlantilla::TIPOS_DATO;

            return view('Licitacion.edit', compact(
                'varpantallas',
                'varsubmenus',
                'licitacion',
                'detalle',
                'proveedoresInvitados',
                'tiposParam',
                'sentidos',
                'tiposDato'
            ));
        } catch(\Exception $ex){  
            return back()->with("warningBD", "Error al cargar licitación: " . $ex->getMessage()); 
        }
    }

    public function show($id){
        try{
            // traer la informacion de los datos a enviar a edicion
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();

            $date = Carbon::now();
            $date = $date->format('Y-m-d');

            $licitacion = Licitacion::find($id);
            
            if (!$licitacion) {
                return back()->with("warningBD", "Licitación no encontrada");
            }

            $detalle = $licitacion->detalles->map(function ($item) {
                // Verificar que el producto exista
                $producto = $item->producto;
                if (!$producto) {
                    return [
                        'producto_id' => $item->producto_id,
                        'nombre' => 'Producto no encontrado',
                        'cantidad' => $item->cantidad,
                        'umed' => 'N/A',
                        'observaciones' => $item->observaciones,
                        'costo' => $item->costo
                    ];
                }
                
                // Verificar que la unidad de medida exista
                $unidadNombre = $producto->unidad->nombre ?? 'N/A';
                
                return [
                    'producto_id' => $item->producto_id,
                    'nombre' => $producto->nombre,
                    'cantidad' => $item->cantidad,
                    'umed' => $unidadNombre,
                    'observaciones' => $item->observaciones,
                    'costo' => $item->costo
                ];
            })->toArray();

            return view('Licitacion.show',compact('varpantallas','varsubmenus','licitacion','detalle'));
        } catch(\Exception $ex){
            return back()->with("warningBD", "Error al cargar licitación: " . $ex->getMessage());
        }
    }


    // public function update(Request $request,$id){
        //     try{
        //         // traer la informacion de los datos a enviar a edicion
        //         $varpantallas =  $this->Traermenuenc();
        //         $varsubmenus =   $this->Traermenudet();

        //         $date = Carbon::now();
        //         $date = $date->format('Y-m-d');

        //         //$obtenerempleado = $this->obtenerlistaempleadoid($id);
        //         $Licitacion = Licitacion::find($id);
        //         $Licitacion->update($request->all());
        //         $detalle = json_decode($request->detalle_json, true);

        //         // Guardar detalles
        //         $Licitacion->detalles()->delete();
        //         foreach ($detalle as $item) {
        //             $Licitacion->detalles()->create([
        //                 'producto_id' => $item['producto_id'],
        //                 'cantidad' => $item['cantidad'],
        //                 'observaciones' => $item['observaciones']
        //             ]);
        //         }

        //         if($Licitacion){
        //             return redirect()->route('licitaciones.index')->with("success","¡Se guardaron los cambios correctamente!");
        //         }else{
        //             return redirect()->route('licitaciones.index')->with("warning","¡No se guardaron los cambios correctamente!");}

        //     } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
        // }


        // public function update(Request $request, $id)
        // {
        //     // try {
        //         $varpantallas = $this->Traermenuenc();
        //         $varsubmenus = $this->Traermenudet();

        //         $date = Carbon::now()->format('Y-m-d');

        //         // si en el request viene un campo proveedor_adjudicado_id, se actualiza el estado  del request con el valor 'Adjudicado'
        //         // esto es para cambiar el estado de la licitacion a adjudicado
        //         // si no viene el campo, no se actualiza el estado
        //         $proveedor_adjudicado_id = $request->input('proveedor_adjudicado_id');
        //         if ($proveedor_adjudicado_id) {
        //             $Licitacion = Licitacion::find($id);
        //             $Licitacion->update(['estado' => 'adjudicada']);
        //         }

        //         $Licitacion = Licitacion::find($id);
        //         $Licitacion->update($request->all());

        //             // Cambiar estado solo si se adjudicó
        //         if ($request->filled('proveedor_adjudicado_id')) {
        //             $Licitacion->estado = 'adjudicada';
        //             $Licitacion->save(); // Solo se guarda si se cambió el estado
        //         }

        //         $detalle = json_decode($request->detalle_json, true);


        //         // Guardar detalles
        //         $Licitacion->detalles()->delete();
        //         foreach ($detalle as $item) {
        //             $Licitacion->detalles()->create([
        //                 'producto_id' => $item['producto_id'],
        //                 'cantidad' => $item['cantidad'],
        //                 'observaciones' => $item['observaciones']
        //             ]);
        //         }

        //         // Obtener todos los proveedores registrados en esta licitación
        //         $proveedores = DB::table('tbllicitacion_proveedor')
        //             ->where('licitacion_id', $Licitacion->id)
        //             ->pluck('proveedor_id');

        //         if ($proveedores->isEmpty()) {
        //             return back()->with("warningBD", "No hay proveedores registrados para esta licitación.");
        //         }


        //         // Limpiar registros anteriores de todos los proveedores para esta licitación
        //         DB::table('tbllicitacionproducto_proveedor')
        //             ->where('licitacion_id', $Licitacion->id)
        //             ->whereIn('proveedor_id', $proveedores)
        //             ->delete();

        //         // Insertar los mismos productos para cada proveedor
        //         foreach ($proveedores as $proveedor_id) {
        //             foreach ($detalle as $item) {
        //                 DB::table('tbllicitacionproducto_proveedor')->insert([
        //                     'licitacion_id' => $Licitacion->id,
        //                     'proveedor_id' => $proveedor_id,
        //                     'producto_id' => $item['producto_id'],
        //                     'costo' => $item['costo'] ?? 0
        //                 ]);
        //             }
        //         }

        //         return redirect()->route('licitaciones.index')->with("success", "¡Se guardaron los cambios correctamente!");
        //     // } catch (\Illuminate\Database\QueryException $ex) {
        //     //     return back()->with("warningBD", "No se guardó correctamente: " . $ex->getMessage());
        //     // }
    // }


    public function update(Request $request, $id)
    {
        try
        {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();

            $date = Carbon::now()->format('Y-m-d');

            $proveedor_adjudicado_id = $request->input('proveedor_adjudicado_id');
            $Licitacion = Licitacion::find($id);

            // Verificar si se está intentando adjudicar
            if($proveedor_adjudicado_id != null && $proveedor_adjudicado_id != 0) {
                // Verificar que el proveedor haya enviado su propuesta
                $estadoProveedor = DB::table('tbllicitacion_proveedor')
                    ->where('licitacion_id', $id)
                    ->where('proveedor_id', $proveedor_adjudicado_id)
                    ->value('estado_provext');

                if ($estadoProveedor !== 'Enviada') {
                    return back()->with("warningBD", "No se puede adjudicar la licitación. El proveedor seleccionado aún no ha enviado su propuesta.");
                }

                $Licitacion->update(['estado' => 'adjudicada']);
            }

            $Licitacion->update($request->all());

            // Parsear detalle_json solo si existe y no está vacío
            if ($request->has('detalle_json') && !empty($request->detalle_json)) {
                $detalle = json_decode($request->detalle_json, true);

                // Verificar que el detalle se haya decodificado correctamente como un array
                if (is_array($detalle)) {
                    // Obtener los IDs de productos actuales en el detalle
                    $productosActuales = collect($detalle)->pluck('producto_id')->filter()->toArray();

                    // Obtener todos los costos actuales de tbllicitacionproducto_proveedor
                    $costosActuales = DB::table('tbllicitacionproducto_proveedor')
                        ->where('licitacion_id', $Licitacion->id)
                        ->get()
                        ->groupBy(function ($item) {
                            return $item->proveedor_id . '-' . $item->producto_id;
                        });

                    // Eliminar registros que ya no están en el detalle
                    DB::table('tbllicitacionproducto_proveedor')
                        ->where('licitacion_id', $Licitacion->id)
                        ->whereNotIn('producto_id', $productosActuales)
                        ->delete();

                    //  Borrar detalles de productos, pero NO tocar costos
                    $Licitacion->detalles()->delete();

                    // Insertar nuevos productos en tbllicitacion_det
                    foreach ($detalle as $item) {
                        $Licitacion->detalles()->create([
                            'producto_id' => $item['producto_id'],
                            'cantidad' => $item['cantidad'],
                            'unidad_medida' => $item['unidad_medida'] ?? null,
                            'observaciones' => $item['observaciones'] ?? null,
                        ]);
                    }

                    // Obtener proveedores activos para esta licitación
                    $proveedores = DB::table('tbllicitacion_proveedor')
                        ->where('licitacion_id', $Licitacion->id)
                        ->pluck('proveedor_id');

                    if ($proveedores->isEmpty()) {
                        return back()->with("warningBD", "No hay proveedores registrados para esta licitación.");
                    }

                    //  Insertar productos para cada proveedor, respetando costos antiguos si existen
                    foreach ($proveedores as $proveedor_id) {
                        foreach ($detalle as $item) {
                            $key = $proveedor_id . '-' . $item['producto_id'];

                            $costoAnterior = isset($costosActuales[$key]) ? $costosActuales[$key][0]->costo : 0;
                            $marcaAnterior = isset($costosActuales[$key]) ? $costosActuales[$key][0]->marca : null;

                            // Insertar el registro respetando costo anterior si existía
                            DB::table('tbllicitacionproducto_proveedor')->updateOrInsert(
                                [
                                    'licitacion_id' => $Licitacion->id,
                                    'proveedor_id' => $proveedor_id,
                                    'producto_id' => $item['producto_id'],
                                ],
                                [
                                    'costo' => $costoAnterior,
                                    'marca' => $marcaAnterior
                                ]
                            );
                        }
                    }
                } else {
                    return back()->with("warningBD", "El formato de los datos de productos no es válido.");
                }
            }

             return back()->with("success", "¡Se guardaron los cambios correctamente!");
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "No se guardó correctamente: " . $ex->getMessage());
        }
    }



    public function destroy($id){
        try{
            $Licitacion = Licitacion::find($id);
            $Licitacion->delete();
            return redirect()->route('licitaciones.index')->with("success","¡Se elimino correctamente!");
        } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    }


    public function enviarCorreos($licitacionId)
    {
        // Obtener los proveedores asociados con la licitación
        $proveedores = Proveedor::whereHas('licitaciones', function($query) use ($licitacionId) {
            $query->where('licitacion_id', $licitacionId);
        })->get();

        // Obtener los correos electrónicos de los proveedores
        $correos = $proveedores->pluck('email');

        // Enviar el correo a cada proveedor
        foreach ($correos as $correo) {
            // Enviar el correo
            Mail::to($correo)->send(new LicitacionMail($licitacionId));
        }

        // Redirigir o devolver una respuesta indicando que los correos se enviaron
        return redirect()->route('licitaciones.index')->with('success', 'Correos enviados a los proveedores.');
    }

    /**
     * Muestra una comparativa de precios por proveedor para una licitación.
     */
    public function comparar($id)
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();

            $licitacion = Licitacion::find($id);
            
            if (!$licitacion) {
                return back()->with("warningBD", "Licitación no encontrada");
            }

            // Obtener todos los proveedores asociados a esta licitación
            $proveedores = DB::table('tbllicitacion_proveedor as lp')
                ->join('tblprovedores as p', 'p.id', '=', 'lp.proveedor_id')
                ->where('lp.licitacion_id', $id)
                ->select('p.id', 'p.nombre')
                ->get();

            // Obtener todos los productos de la licitación
            $productos = DB::table('tbllicitacion_det as ld')
                ->join('tblproductos as p', 'p.id', '=', 'ld.producto_id')
                ->leftJoin('tblunidadesmedida as u', 'u.id', '=', 'p.id_unidad_medida')
                ->where('ld.licitacion_id', $id)
                ->select('p.id', 'p.nombre', 'ld.cantidad', 'u.nombre as unidad')
                ->get();

            // Obtener todas las cotizaciones por proveedor y producto
            $cotizaciones = DB::table('tbllicitacionproducto_proveedor')
                ->where('licitacion_id', $id)
                ->get()
                ->groupBy(function($item) {
                    // Agrupar por producto_id-proveedor_id para acceso fácil
                    return $item->producto_id . '-' . $item->proveedor_id;
                });

            $licitacion->load(['parametrosEval', 'tipoParam']);
            $parametrosEval = $licitacion->parametrosEval;
            $scoresMap = [];
            $ranking = [];
            $paramPrecioId = null;
            $precioDesdeCotizacion = false;

            $totalesPorProveedor = [];
            foreach ($proveedores as $p) {
                $total = 0.0;
                foreach ($productos as $producto) {
                    $key = $producto->id.'-'.$p->id;
                    if (isset($cotizaciones[$key])) {
                        $total += ((float) $cotizaciones[$key][0]->costo) * ((float) $producto->cantidad);
                    }
                }
                $totalesPorProveedor[(int) $p->id] = round($total, 2);
            }

            if ($parametrosEval->isNotEmpty()) {
                $precioDesdeCotizacion = $this->paramService->sincronizarPrecioDesdeCotizaciones($licitacion);
                $paramPrecio = $this->paramService->parametroPrecio($licitacion);
                $paramPrecioId = $paramPrecio?->id;

                $result = $this->paramService->recalcularNormalizacion($licitacion);
                $ranking = $result['ranking'];
                foreach ($result['scores'] as $s) {
                    $scoresMap[$s->parametro_id.'-'.$s->proveedor_id] = $s;
                }
            }

            $sugerencia = null;
            if (! empty($ranking)) {
                $bestId = (int) array_key_first($ranking);
                $bestProv = $proveedores->firstWhere('id', $bestId);
                $sugerencia = [
                    'proveedor_id' => $bestId,
                    'nombre' => $bestProv->nombre ?? ('#'.$bestId),
                    'score' => (float) $ranking[$bestId],
                    'total' => (float) ($totalesPorProveedor[$bestId] ?? 0),
                    'ranking' => $ranking,
                ];
            }

            return view('Licitacion.comparar', compact(
                'varpantallas',
                'varsubmenus',
                'licitacion',
                'proveedores',
                'productos',
                'cotizaciones',
                'parametrosEval',
                'scoresMap',
                'ranking',
                'totalesPorProveedor',
                'sugerencia',
                'paramPrecioId',
                'precioDesdeCotizacion'
            ));
        } catch(\Exception $ex) {
            return back()->with("warningBD", "Error al cargar la licitación: " . $ex->getMessage());
        }
    }

    /**
     * Exportar costos de la licitación a Excel
     *
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportarCostos($id)
    {
        try {
            $licitacion = Licitacion::findOrFail($id);
            
            // Obtener los costos
            $costos = DB::table('tbllicitacionproducto_proveedor as lpp')
                ->join('tblprovedores as p', 'p.id', '=', 'lpp.proveedor_id')
                ->join('tblproductos as pr', 'pr.id', '=', 'lpp.producto_id')
                ->select('lpp.producto_id', 'p.nombre as proveedor', 'pr.nombre as producto', 'lpp.costo', 'lpp.marca')
                ->where('lpp.licitacion_id', $id)
                ->orderBy('proveedor')
                ->orderBy('producto')
                ->get();
                
            if ($costos->isEmpty()) {
                return back()->with('warning', 'No hay costos para exportar en esta licitación.');
            }
            
            $filename = 'costos_licitacion_' . $id . '_' . date('Y-m-d') . '.xlsx';
            
            return Excel::download(
                new CostosLicitacionExport($costos, $licitacion), 
                $filename
            );
            
        } catch (\Exception $e) {
            return back()->with('warningBD', 'Error al exportar: ' . $e->getMessage());
        }
    }

    public function ExportarComparativa(int $id)
    {
        return Excel::download(new ComparativaExportar($id), 'COMPARATIVA DE PRECIOS.xlsx');
    }

    /**
     * Adjudicar productos individualmente a diferentes proveedores
     */
    public function adjudicarPorProducto(Request $request, $id)
    {
        try {
            $licitacion = Licitacion::findOrFail($id);
            
            // Validar que la licitación no esté ya adjudicada
            if ($licitacion->estado == 'adjudicada') {
                return back()->with('warningBD', 'La licitación ya ha sido adjudicada.');
            }

            $adjudicaciones = $request->input('adjudicaciones', []);
            
            if (empty($adjudicaciones)) {
                return back()->with('warningBD', 'Debe adjudicar al menos un producto.');
            }

            DB::beginTransaction();

            // Limpiar adjudicaciones anteriores
            DB::table('tbllicitacion_adjudicaciones')
                ->where('licitacion_id', $id)
                ->delete();

            $usuarioAdjudicador = auth()->user()->name;
            $fechaAdjudicacion = now();

            // Procesar cada adjudicación (ahora puede haber múltiples por producto)
            foreach ($adjudicaciones as $productoId => $adjudicacionesProducto) {
                // Obtener información del producto
                $producto = DB::table('tbllicitacion_det')
                    ->where('licitacion_id', $id)
                    ->where('producto_id', $productoId)
                    ->first();

                if (!$producto) {
                    continue;
                }

                $cantidadTotalAdjudicada = 0;

                // Procesar cada división del producto
                foreach ($adjudicacionesProducto as $index => $adjudicacion) {
                    if (empty($adjudicacion['proveedor_id']) || empty($adjudicacion['cantidad'])) {
                        continue;
                    }

                    $cantidad = floatval($adjudicacion['cantidad']);
                    $cantidadTotalAdjudicada += $cantidad;

                    // Obtener el precio del proveedor
                    $cotizacion = DB::table('tbllicitacionproducto_proveedor')
                        ->where('licitacion_id', $id)
                        ->where('producto_id', $productoId)
                        ->where('proveedor_id', $adjudicacion['proveedor_id'])
                        ->first();

                    if (!$cotizacion || $cotizacion->costo == 0) {
                        return back()->with('warningBD', 
                            'El proveedor seleccionado no tiene precio para el producto ID ' . $productoId
                        );
                    }

                    $precioUnitario = floatval($cotizacion->costo);
                    $subtotal = $cantidad * $precioUnitario;

                    // Insertar la adjudicación
                    DB::table('tbllicitacion_adjudicaciones')->insert([
                        'licitacion_id' => $id,
                        'producto_id' => $productoId,
                        'proveedor_id' => $adjudicacion['proveedor_id'],
                        'cantidad_adjudicada' => $cantidad,
                        'precio_unitario' => $precioUnitario,
                        'subtotal' => $subtotal,
                        'observaciones' => $adjudicacion['observaciones'] ?? null,
                        'fecha_adjudicacion' => $fechaAdjudicacion,
                        'usuario_adjudicador' => $usuarioAdjudicador
                    ]);
                }

                // Validar que la cantidad total no exceda lo solicitado
                if ($cantidadTotalAdjudicada > $producto->cantidad) {
                    return back()->with('warningBD', 
                        'La cantidad total adjudicada (' . $cantidadTotalAdjudicada . ') para el producto "' . 
                        $producto->nombre . '" excede la cantidad solicitada (' . $producto->cantidad . ').'
                    );
                }
            }

            // Cambiar el estado de la licitación a adjudicada
            $licitacion->update(['estado' => 'adjudicada']);

            DB::commit();

            return redirect()->route('licitaciones.comparar', $id)
                ->with('success', '¡Productos adjudicados correctamente!');

        } catch (\Exception $ex) {
            DB::rollback();
            return back()->with('warningBD', 'Error al adjudicar productos: ' . $ex->getMessage());
        }
    }
}
