<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\MenuTrait;
use App\Traits\DatosimpleTraits;
use App\Traits\SistemasTraits;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema; // Added this import
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Traits\ServiciosTrait;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Traits\InventariosTraits;

class AdminProyectoController extends Controller
{
    use MenuTrait;
    use DatosimpleTraits;
    use SistemasTraits;
    use ServiciosTrait;
    use InventariosTraits;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function proyecto()
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $EmpleadosActivos = $this->obtenerempleadosActivos();
            $PuestosActivos = $this->obtenerpuestos();
            $PuestosActivos = $this->obtenerpuestos();
            $PuestosActivos = $this->obtenerpuestos();
            $ClientesActivos = $this->obtenerClientesActivos();
            
            // Consulta SQL para obtener los proyectos, totales y avance financiero (gastado vs costeado)
            $proyectos = DB::select("
                SELECT 
                    a.id as id_servicio,
                    c.nombre as cliente, 
                    a.folio,
                    a.nombre,
                    a.fecha_inicio,
                    a.fecha_limite,
                    a.estado as estado_servicio,
                    SUM(COALESCE(b.monto_proyectado, 0)) as monto_proyectado,
                    SUM(COALESCE(b.monto_utilidad, 0)) as monto_utilidad,
                    MAX(COALESCE(a.otrosconceptos1, '')) as rfq,
                    CASE WHEN EXISTS(SELECT 1 FROM tdivisiontiemposproyecto dtp WHERE dtp.id_servicio = a.id)
                        THEN 1 ELSE 0 END as tiene_divisiones,
                    (SELECT COUNT(*) FROM tdivisiontiemposproyecto dtp WHERE dtp.id_servicio = a.id) as total_divisiones,
                    (
                        SELECT COUNT(*)
                        FROM tdivisiontiemposproyecto d
                        LEFT JOIN (
                            SELECT id_partida_proyecto, SUM(monto_ingreso) as total
                            FROM tingresosxpartidaproyecto
                            GROUP BY id_partida_proyecto
                        ) s ON s.id_partida_proyecto = d.id
                        WHERE d.id_servicio = a.id AND COALESCE(s.total, 0) >= COALESCE(d.monto, 0)
                    ) as divisiones_terminadas,
                    (
                        SELECT COALESCE(SUM(dtp2.monto),0)
                        FROM tdivisiontiemposproyecto dtp2
                        WHERE dtp2.id_servicio = a.id
                    ) as total_costeado,
                    (
                        SELECT COALESCE(SUM(g.monto),0)
                        FROM tgastosxproyecto g
                        WHERE g.id_proyecto = a.id
                    ) as total_gastado
                FROM tblservicios_enc a 
                LEFT JOIN tblservicios_det b ON a.id = b.id_servicio_enc 
                INNER JOIN tblclientes c ON c.id = a.id_cliente
                WHERE a.id_tiposervicio = 3
                GROUP BY a.id, c.nombre, a.folio, a.nombre, a.fecha_inicio, a.fecha_limite, a.estado
                ORDER BY a.fecha_limite ASC
            ");

            return view('Proyectos.index', compact('varpantallas', 'varsubmenus', 'proyectos', 'ClientesActivos', 'EmpleadosActivos'));
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "Error al cargar los proyectos");
        }
    }

    public function guardarDivisionProyecto(Request $request)
    {
        try {
            Log::info('Datos recibidos en guardarDivisionProyecto:', $request->all());
            
            $request->validate([
                'costo_total' => 'required|numeric|min:0',
                'num_partidas' => 'required|integer|min:1|max:50',
                'divisiones' => 'required|array|min:1'
            ]);

            // Obtener el ID del servicio desde la sesión o request
            $idServicio = $request->input('id_servicio');
            
            Log::info('ID del servicio:', ['id_servicio' => $idServicio]);
            
            if (!$idServicio) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID del servicio no proporcionado'
                ], 400);
            }

            // Validar que el proyecto esté EN PROCESO o BORRADOR para permitir divisiones
            $estadoProyecto = DB::table('tblservicios_enc')->where('id', $idServicio)->value('estado');
            $estadoUpper = strtoupper($estadoProyecto ?? '');
            if (!in_array($estadoUpper, ['EN PROCESO','BORRADOR'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se puede dividir el proyecto cuando su estado es EN PROCESO o BORRADOR'
                ], 400);
            }
            
            // Verificar que la tabla existe
            if (!Schema::hasTable('tdivisiontiemposproyecto')) {
                Log::error('La tabla tdivisiontiemposproyecto no existe');
                return response()->json([
                    'success' => false,
                    'message' => 'Error de configuración: tabla de divisiones no encontrada'
                ], 500);
            }
            
            // Sin tipo de plazo ni fechas: usar tipo "personalizado" por defecto
            $idTipoPlazo = 7; // personalizado

            // Calcular totales: el front envía costo_total = total con utilidad y base_sin_utilidad = monto del input
            $baseSinUtilidad = (float) ($request->input('base_sin_utilidad') ?? 0);
            $utilidadPorcentaje = (float) ($request->input('utilidad_porcentaje') ?? 0);
            $totalConUtilidad = (float) ($request->input('costo_total') ?? ($baseSinUtilidad * (1 + ($utilidadPorcentaje / 100))));
            
            // Insertar cada división en la tabla
            foreach ($request->divisiones as $division) {
                DB::table('tdivisiontiemposproyecto')->insert([
                    'id_servicio' => $idServicio,
                    'id_tipo_plazo' => $idTipoPlazo,
                    'plazo' => $division['numero_plazo'],
                    'monto' => $division['monto'],
                    // No guardar totales en campos de fecha; se usan para fechas en vistas
                    'otros_conceptos1' => null,
                    'otros_concetpos2' => null,
                    'otros_conceptos3' => $division['nombre'] ?? null, // Nombre de la división
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // Registrar/actualizar resumen del proyecto en tblservicios_det
            $nombreDet = 'División de Proyecto';
            $montoProyectado = $baseSinUtilidad;
            // Por requerimiento: guardar en monto_utilidad el monto del input + utilidad (total con utilidad)
            $montoUtilidad = (float) $totalConUtilidad;

            $existeDet = DB::table('tblservicios_det')->where('id_servicio_enc', $idServicio)->first();
            if ($existeDet) {
                DB::table('tblservicios_det')
                    ->where('id_servicio_enc', $idServicio)
                    ->update([
                        'nombre' => $nombreDet,
                        'monto_proyectado' => $montoProyectado,
                        'monto_real' => DB::raw('COALESCE(monto_real,0)'),
                        'utilidad_porcentaje' => $utilidadPorcentaje,
                        'monto_utilidad' => DB::raw($montoUtilidad),
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('tblservicios_det')->insert([
                    'nombre' => $nombreDet,
                    'id_servicio_enc' => $idServicio,
                    'id_concepto' => 22,
                    'monto_proyectado' => $montoProyectado,
                    'monto_real' => 0,
                    'utilidad_porcentaje' => $utilidadPorcentaje,
                    'monto_utilidad' => $montoUtilidad,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'División del proyecto guardada correctamente',
                'id_servicio' => $idServicio
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la división del proyecto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function listarDivisiones($idServicio = null)
    {
        // try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $EmpleadosActivos = $this->obtenerempleadosActivos();
            $PuestosActivos = $this->obtenerpuestos();
            
            // Validar que se proporcione un ID de servicio
            if (!$idServicio) {
                return redirect()->route('proyectos.index')->with('warning', 'ID del proyecto no proporcionado');
            }
            
            // Obtener las divisiones del servicio específico, incluyendo conteo de partidas
            $divisiones = DB::table('tdivisiontiemposproyecto as dtp')
                ->join('tblservicios_enc as se', 'dtp.id_servicio', '=', 'se.id')
                ->join('tblclientes as c', 'se.id_cliente', '=', 'c.id')
                ->leftJoin(DB::raw('(SELECT id_partida_proyecto, COUNT(*) as cnt FROM tingresosxpartidaproyecto GROUP BY id_partida_proyecto) as tip'), 'tip.id_partida_proyecto', '=', 'dtp.id')
                ->leftJoin(DB::raw('(SELECT id_partida_proyecto, SUM(monto_ingreso) as total_ingresos FROM tingresosxpartidaproyecto GROUP BY id_partida_proyecto) as sumi'), 'sumi.id_partida_proyecto', '=', 'dtp.id')
                ->select([
                    'dtp.id',
                    'dtp.id_servicio',
                    'dtp.id_tipo_plazo',
                    'dtp.plazo',
                    'dtp.monto',
                    'dtp.otros_conceptos1 as fecha_inicio',
                    'dtp.otros_concetpos2 as fecha_fin',
                    'dtp.otros_conceptos3 as titulo_division',
                    'dtp.created_at',
                    DB::raw('COALESCE(tip.cnt, 0) as partidas_count'),
                    DB::raw('COALESCE(sumi.total_ingresos, 0) as total_ingresos'),
                    'se.folio',
                    'se.estado as estado_servicio',
                    'c.nombre as cliente'
                ])
                ->where('dtp.id_servicio', $idServicio)
                ->orderBy('dtp.plazo', 'asc')
                ->get();
            
            // Calcular monto de divisiones extras (creadas después del lote inicial)
            $montoExtras = 0;
            $minCreatedAt = null;
            if ($divisiones->count() > 0) {
                $minCreatedAt = $divisiones->min('created_at');
                $montoExtras = $divisiones->filter(function($d) use ($minCreatedAt) {
                    return $d->created_at > $minCreatedAt;
                })->sum('monto');
            }

            // Monto base (real) antes de extras y totales de ingresos
            $montoTotalDivisiones = $divisiones->sum('monto');
            $montoBase = max(0, $montoTotalDivisiones - $montoExtras);
            // Alinear con la pantalla "Gastos del Proyecto": usar total de tgastosxproyecto
            $montoTotalIngresos = (float) DB::table('tgastosxproyecto')
                ->where('id_proyecto', $idServicio)
                ->sum('monto');
            $montoDisponible = max(0, (float)$montoTotalDivisiones - (float)$montoTotalIngresos);
            
            // Obtener información del servicio
            $servicio = DB::table('tblservicios_enc as se')
                ->join('tblclientes as c', 'se.id_cliente', '=', 'c.id')
                ->select([
                    'se.id',
                    'se.folio',
                    'se.estado as estado_servicio',
                    'c.nombre as cliente',
                    'se.fecha_inicio',
                    'se.fecha_limite'
                ])
                ->where('se.id', $idServicio)
                ->first();

            if (!$servicio) {
                return redirect()->route('proyectos.index')->with('warning', 'Proyecto no encontrado');
            }

            return view('Proyectos.divisiones-proyecto', compact('varpantallas', 'varsubmenus', 'divisiones', 'servicio', 'EmpleadosActivos', 'PuestosActivos', 'montoExtras', 'montoBase', 'minCreatedAt', 'montoTotalDivisiones', 'montoTotalIngresos', 'montoDisponible'));
        // } catch (\Exception $e) {
        //     return back()->with("warningBD", "Error al cargar las divisiones: " . $e->getMessage());
        // }
    }

    public function guardarPartida(Request $request)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'id_partida_proyecto' => 'required|integer',
                'tipo_ingreso' => 'required|string',
                'monto_ingreso' => 'required|numeric|min:0',
                'tipo_operacion' => 'required|string',
                'ruta_documento' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240', // 10MB
                'producto_suministro' => 'nullable|integer',
                'cantidad_suministro' => 'nullable|integer|min:1'
            ]);

            // Validación adicional para suministros
            if (strtoupper($request->tipo_ingreso) === 'SUMINISTRO') {
                $request->validate([
                    'producto_suministro' => 'required|integer|exists:tblproductos,id',
                    'cantidad_suministro' => 'required|integer|min:1'
                ]);
            }

            // Procesar el archivo si viene (guardar en public/documentos_ingresos)
            $filePath = null;
            if ($request->hasFile('ruta_documento')) {
                $file = $request->file('ruta_documento');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = strtolower($file->getClientOriginalExtension());
                $safeBase = Str::slug($originalName, '_');
                $unique = substr(md5(uniqid('', true)), 0, 8);
                $fileName = time() . '_' . $unique . '_' . ($safeBase ?: 'documento') . '.' . $extension;
                $destinoPublic = public_path('documentos_ingresos');
                if (!file_exists($destinoPublic)) {
                    @mkdir($destinoPublic, 0775, true);
                }
                $file->move($destinoPublic, $fileName);
                $filePath = 'documentos_ingresos/' . $fileName; // ruta relativa dentro de public
            }

            // Si es suministro, calcular monto = cantidad * precio_unitario
            $montoIngreso = $request->monto_ingreso;
            if (strtoupper($request->tipo_ingreso) === 'SUMINISTRO') {
                $producto = DB::table('tblproductos')->select('precio_unitario')->where('id', $request->producto_suministro)->first();
                if ($producto) {
                    $cantidad = max(1, (int) $request->cantidad_suministro);
                    $montoIngreso = $cantidad * (float) $producto->precio_unitario;
                    
                    // Log para debugging
                    Log::info('Cálculo de monto para suministro:', [
                        'producto_id' => $request->producto_suministro,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $producto->precio_unitario,
                        'monto_calculado' => $montoIngreso
                    ]);
                } else {
                    Log::warning('Producto no encontrado para suministro:', ['producto_id' => $request->producto_suministro]);
                }
            }

            // Insertar el ingreso en la tabla tingresosxpartidaproyecto
            $ingresoId = DB::table('tingresosxpartidaproyecto')->insertGetId([
                'id_partida_proyecto' => $request->id_partida_proyecto,
                'tipo_ingreso' => $request->tipo_ingreso,
                'monto_ingreso' => $montoIngreso,
                'tipo_operacion' => $request->tipo_operacion,
                'flagempleado' => $request->empleado_local ? intval($request->empleado_local) : null,
                'manooexterna' => $request->descripcion_foraneo ?: null,
                'cantidadsumi' => strtoupper($request->tipo_ingreso) === 'SUMINISTRO' ? ($request->cantidad_suministro ?: 1) : null,
                'id_producto' => strtoupper($request->tipo_ingreso) === 'SUMINISTRO' ? ($request->producto_suministro ?: null) : null,
                'ruta_documento' => $filePath,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Si es SUMINISTRO, decrementar existencias y registrar movimiento(s) de inventario
            if (strtoupper($request->tipo_ingreso) === 'SUMINISTRO' && $request->producto_suministro) {
                $productoId = (int) $request->producto_suministro;
                $cantidadSolicitada = max(1, (int) $request->cantidad_suministro);

                // Obtener servicio y folio para referencia de documento
                $partida = DB::table('tdivisiontiemposproyecto as d')
                    ->join('tblservicios_enc as se', 'se.id', '=', 'd.id_servicio')
                    ->select('d.id as id_partida','d.id_servicio','se.folio')
                    ->where('d.id', $request->id_partida_proyecto)
                    ->first();

                // Calcular disponible total y preparar lista de existencias por almacén/ubicación
                $existencias = DB::table('tblexistencias as e')
                    ->select('e.id','e.id_almacen','e.id_ubicacion','e.cantidad_existente','e.cantidad_reservada')
                    ->where('e.id_producto', $productoId)
                    ->orderBy('e.id_almacen')
                    ->orderBy('e.id_ubicacion')
                    ->get()
                    ->map(function($row){
                        $row->disponible = max(0, (float)($row->cantidad_existente ?? 0) - (float)($row->cantidad_reservada ?? 0));
                        return $row;
                    })
                    ->filter(function($row){ return $row->disponible > 0; })
                    ->values();

                $totalDisponible = (float) $existencias->sum('disponible');
                if ($totalDisponible < $cantidadSolicitada) {
                    // No hay stock suficiente
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Stock insuficiente para el producto seleccionado. Disponible: ' . $totalDisponible . ', solicitado: ' . $cantidadSolicitada
                    ], 422);
                }

                // Buscar tipo de movimiento "Asignación a proyecto" si existe; fallback a Transferencia (1)
                $idTipoMovimiento = DB::table('tbltipos_movimientos_inventario')
                    ->whereIn(DB::raw('UPPER(nombre_movimiento)'), [
                        'ASIGNACION A PROYECTO',
                        'SALIDA A PROYECTO',
                        'CONSUMO EN PROYECTO'
                    ])->value('id');
                if (!$idTipoMovimiento) { $idTipoMovimiento = 1; }
                $idEstadoMovimiento = 2; // Completado
                $fechaMovimiento = now()->format('Y-m-d');
                $usuarioMovimiento = auth()->id() ?? 0;
                $documentoRef = 'PARTIDA #' . $ingresoId . ' | PROYECTO ' . ($partida->folio ?? '');
                $observaciones = 'ASIGNACIÓN A PROYECTO - Servicio ' . ($partida->folio ?? '') . ' Partida ' . ($partida->id_partida ?? $request->id_partida_proyecto);

                // Descontar de múltiples ubicaciones si es necesario
                $restante = $cantidadSolicitada;
                $almacenUsadoId = null;
                $ubicacionUsadaId = null;
                foreach ($existencias as $ex) {
                    if ($restante <= 0) break;
                    $quita = min($restante, (float) $ex->disponible);
                    if ($quita <= 0) continue;

                    // Priorizar consumir reservada si existe, luego existente
                    $consumirReservada = 0;
                    $consumirExistente = $quita;
                    $reservadaActual = (float) ($ex->cantidad_reservada ?? 0);
                    if ($reservadaActual > 0) {
                        $consumirReservada = min($reservadaActual, $quita);
                        $consumirExistente = max(0, $quita - $consumirReservada);
                    }

                    DB::table('tblexistencias')
                        ->where('id', $ex->id)
                        ->update([
                            'cantidad_reservada' => DB::raw('GREATEST(0, cantidad_reservada - ' . (float)$consumirReservada . ')'),
                            'cantidad_existente' => DB::raw('GREATEST(0, cantidad_existente - ' . (float)$consumirExistente . ')'),
                            'updated_at' => now()
                        ]);

                    // Registrar movimiento de inventario
                    $this->Registramovinventario(
                        $productoId,
                        (int) $ex->id_almacen,
                        (int) $ex->id_ubicacion,
                        (int) $idTipoMovimiento,
                        (int) $idEstadoMovimiento,
                        (int) $quita,
                        $fechaMovimiento,
                        $documentoRef,
                        $observaciones,
                        now()->toDateTimeString(),
                        (int) $usuarioMovimiento,
                        0
                    );

                    if (is_null($almacenUsadoId)) { $almacenUsadoId = (int)$ex->id_almacen; }
                    if (is_null($ubicacionUsadaId)) { $ubicacionUsadaId = (int)$ex->id_ubicacion; }
                    $restante -= (int) $quita;
                }

                // Actualizar la partida con otrosconceptos: almacen, ubicacion, producto
                DB::table('tingresosxpartidaproyecto')
                    ->where('id', $ingresoId)
                    ->update([
                        'otrosconceptos1' => $almacenUsadoId,
                        'otrosconceptos2' => $ubicacionUsadaId,
                        'otrosconceptos3' => $productoId,
                        'updated_at' => now(),
                    ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Ingreso guardado correctamente',
                'ingreso_id' => $ingresoId
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el ingreso: ' . $e->getMessage()
            ], 500);
        }
    }

    public function detallePartidas($idDivision)
    {
        try {
            // Obtener las partidas de la división específica
            $partidas = DB::table('tingresosxpartidaproyecto')
                ->where('id_partida_proyecto', $idDivision)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'partidas' => $partidas,
                'total_partidas' => count($partidas),
                'monto_total' => $partidas->sum('monto_ingreso')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los detalles de partidas: ' . $e->getMessage()
            ], 500);
        }
    }

    public function detallePartidasPagina($idDivision)
    {
        // Cargar datos de la división y sus partidas
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        $division = DB::table('tdivisiontiemposproyecto')
            ->select('id','id_servicio','plazo','monto','otros_conceptos1 as fecha_inicio','otros_concetpos2 as fecha_fin','otros_conceptos3 as titulo')
            ->where('id', $idDivision)
            ->first();

        if (!$division) {
            return redirect()->back()->with('warning', 'División no encontrada');
        }

        // Partidas con joins para mostrar almacén, ubicación y producto cuando sea SUMINISTRO
        $partidas = DB::table('tingresosxpartidaproyecto as tip')
            ->leftJoin('tblalmacenes as a', 'a.id', '=', 'tip.otrosconceptos1')
            ->leftJoin('tblubicaciones as u', 'u.id', '=', 'tip.otrosconceptos2')
            ->leftJoin('tblproductos as p', 'p.id', '=', 'tip.otrosconceptos3')
            ->select([
                'tip.*',
                DB::raw('COALESCE(a.folio_interno, "") as almacen'),
                DB::raw('COALESCE(u.folio_interno, "") as ubicacion'),
                DB::raw('COALESCE(p.nombre, "") as producto')
            ])
            ->where('tip.id_partida_proyecto', $idDivision)
            ->orderBy('tip.created_at', 'desc')
            ->get();

        // Estadísticas por tipo de ingreso
        $porTipo = $partidas->groupBy('tipo_ingreso')->map(function($grupo){
            return [
                'cantidad' => $grupo->count(),
                'total' => $grupo->sum('monto_ingreso')
            ];
        });

        // Suministros por producto (para gráfica)
        $suministrosPorProducto = DB::table('tingresosxpartidaproyecto as tip')
            ->leftJoin('tblproductos as p', 'p.id', '=', 'tip.otrosconceptos3')
            ->select([
                DB::raw('COALESCE(p.nombre, "SIN PRODUCTO") as producto'),
                DB::raw('SUM(tip.monto_ingreso) as total')
            ])
            ->where('tip.id_partida_proyecto', $idDivision)
            ->where('tip.tipo_ingreso', 'SUMINISTRO')
            ->groupBy('producto')
            ->orderBy('total', 'desc')
            ->get();

        // Totales
        $totalPartidas = $partidas->count();
        $montoTotal = $partidas->sum('monto_ingreso');

        return view('Proyectos.detalle-partidas', compact(
            'varpantallas','varsubmenus','division','partidas','porTipo','totalPartidas','montoTotal','suministrosPorProducto'
        ));
    }

    public function verDocumentoPartida($id)
    {
        $partida = DB::table('tingresosxpartidaproyecto')->where('id', $id)->first();
        if (!$partida || empty($partida->ruta_documento)) {
            abort(404);
        }
        // Los documentos se guardan en public/documentos_ingresos
        $path = public_path($partida->ruta_documento);
        if (!file_exists($path)) {
            abort(404);
        }
        return response()->file($path);
    }

     public function cotizarProyecto($idServicio)
    {
        $servicio = DB::table('tblservicios_enc as se')
            ->join('tblclientes as c', 'se.id_cliente', '=', 'c.id')
            ->select('se.id','se.folio','se.fecha_inicio','se.fecha_limite','c.nombre as cliente')
            ->where('se.id', $idServicio)
            ->first();

        if (!$servicio) {
            return redirect()->route('proyectos.index')->with('warning','Proyecto no encontrado');
        }

        $divisiones = DB::table('tdivisiontiemposproyecto')
            ->select('plazo','monto','otros_conceptos1 as fecha_inicio','otros_concetpos2 as fecha_fin','otros_conceptos3 as titulo')
            ->where('id_servicio',$idServicio)
            ->orderBy('plazo','asc')
            ->get();

        $montoTotal = $divisiones->sum('monto');
        $datos_servicio = $this->informacion_enc_datos_servicio($idServicio);

        // Consulta para obtener revisor y condiciones
        $revisor_condiciones = DB::selectOne("
            SELECT 
                CONCAT(emp.primer_nombre, ' ', emp.segundo_nombre, ' ', emp.apellido_paterno, ' ', emp.apellido_materno) AS revisor,
                senc.otrosconceptos3 AS condiciones
            FROM tblservicios_enc senc
            JOIN tblempleados emp ON senc.otrosconceptos2 = emp.id
            WHERE senc.id = ?
        ", [$idServicio]);

        // Generar el PDF
        $pdf = PDF::loadView('Proyectos.pdf.cotizacion-proyecto', compact('servicio', 'divisiones','montoTotal','datos_servicio','revisor_condiciones'));
        
        
        $pdf->setPaper('letter', 'portrait');
        
        $nombreArchivo = 'COTIZACION_PROYECTO_'.$servicio->folio.'.pdf'; 
        
        return $pdf->stream($nombreArchivo);
    }

    public function cambiarEstado(Request $request)
    {
        try {
            $request->validate([
                'id_servicio' => 'required|integer',
                'estado' => 'required|string|in:EN PROCESO,EN ESPERA,BORRADOR,CIERRE,FINALIZADO,TERMINADO'
            ]);

            $estado = strtoupper(trim($request->estado));
            if ($estado === 'TERMINADO' || $estado === 'FINALIZADO') { $estado = 'CIERRE'; }

            DB::table('tblservicios_enc')
                ->where('id', $request->id_servicio)
                ->update([
                    'estado' => $estado,
                    'updated_at' => now(),
                    'updated_by' => auth()->user()->name ?? 'Sistema'
                ]);

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo cambiar el estado: ' . $e->getMessage()
            ], 500);
        }
    }

    public function indicadoresProyecto($idServicio)
    {
        // try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            
            // Obtener información del proyecto
            $proyecto = DB::table('tblservicios_enc as se')
                ->join('tblclientes as c', 'se.id_cliente', '=', 'c.id')
                ->select([
                    'se.id',
                    'se.folio',
                    'c.nombre as cliente',
                    'se.fecha_inicio',
                    'se.fecha_limite'
                ])
                ->where('se.id', $idServicio)
                ->first();

            if (!$proyecto) {
                return redirect()->route('proyectos.index')->with('warning', 'Proyecto no encontrado');
            }

            // Obtener divisiones del proyecto
            $divisiones = DB::table('tdivisiontiemposproyecto as dtp')
                ->select([
                    'dtp.id',
                    'dtp.plazo',
                    'dtp.monto',
                    'dtp.otros_conceptos1 as fecha_inicio',
                    'dtp.otros_concetpos2 as fecha_fin',
                    'dtp.otros_conceptos3'
                ])
                ->where('dtp.id_servicio', $idServicio)
                ->orderBy('dtp.plazo', 'asc')
                ->get();

            // Obtener ingresos por partidas
            $ingresos = DB::table('tingresosxpartidaproyecto as tip')
                ->join('tdivisiontiemposproyecto as dtp', 'tip.id_partida_proyecto', '=', 'dtp.id')
                ->select([
                    'tip.id',
                    'tip.id_partida_proyecto',
                    'tip.tipo_ingreso',
                    'tip.monto_ingreso',
                    'tip.tipo_operacion',
                  
                    'tip.created_at',
                    'dtp.plazo',
                    'dtp.monto as monto_division'
                ])
                ->where('dtp.id_servicio', $idServicio)
                ->orderBy('tip.created_at', 'desc')
                ->get();

            // Calcular estadísticas
            $totalDivisiones = count($divisiones);
            $totalIngresos = count($ingresos);
            $montoTotalDivisiones = $divisiones->sum('monto');
            $montoTotalIngresos = $ingresos->sum('monto_ingreso');
            $porcentajeAvance = $montoTotalDivisiones > 0 ? ($montoTotalIngresos / $montoTotalDivisiones) * 100 : 0;

            // Datos para gráficas
            // Sumar ingresos por plazo de división
            $ingresosPorPlazo = $ingresos->groupBy('plazo')->map(function($grupo){
                return $grupo->sum('monto_ingreso');
            });
            $datosGraficaDivisiones = $divisiones->map(function($division) use ($ingresosPorPlazo) {
                $plazo = (int) $division->plazo;
                $ingresado = $ingresosPorPlazo->get($plazo, 0);
                return [
                    'plazo' => 'Plazo ' . $division->plazo,
                    'monto' => (float) $division->monto,
                    'ingresado' => (float) $ingresado,
                ];
            });

            $datosGraficaIngresos = $ingresos->groupBy('tipo_ingreso')->map(function($grupo) {
                return [
                    'tipo' => $grupo->first()->tipo_ingreso,
                    'total' => $grupo->sum('monto_ingreso'),
                    'cantidad' => $grupo->count()
                ];
            });

            $datosGraficaOperaciones = $ingresos->groupBy('tipo_operacion')->map(function($grupo) {
                return [
                    'tipo' => $grupo->first()->tipo_operacion,
                    'total' => $grupo->sum('monto_ingreso'),
                    'cantidad' => $grupo->count()
                ];
            });

            return view('Proyectos.indicadores', compact(
                'varpantallas', 
                'varsubmenus', 
                'proyecto', 
                'divisiones', 
                'ingresos',
                'totalDivisiones',
                'totalIngresos',
                'montoTotalDivisiones',
                'montoTotalIngresos',
                'porcentajeAvance',
                'datosGraficaDivisiones',
                'datosGraficaIngresos',
                'datosGraficaOperaciones'
            ));

        // } catch (\Exception $e) {
        //     return back()->with("warningBD", "Error al cargar los indicadores: " . $e->getMessage());
        // }
    }

    public function gastosProyecto($idServicio)
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        $servicio = DB::table('tblservicios_enc as se')
            ->join('tblclientes as c', 'se.id_cliente', '=', 'c.id')
            ->select('se.id','se.folio','c.nombre as cliente','se.fecha_inicio','se.fecha_limite')
            ->where('se.id', $idServicio)
            ->first();
        if (!$servicio) return redirect()->route('proyectos.index')->with('warning','Proyecto no encontrado');

        $gastos = DB::table('tgastosxproyecto as g')
            ->leftJoin('ttipo_ingresoproy as t', 't.id', '=', 'g.id_tipo_ingreso')
            ->leftJoin('tblproductos as p', 'p.id', '=', 'g.otrosconceptos2')
            ->leftJoin('tblempleados as e', 'e.id', '=', 'g.otrosconceptos1')
            ->leftJoin('tblpuestos as pu', 'pu.id', '=', 'g.otrosconceptos1')
            ->select([
                'g.*',
                DB::raw('COALESCE(t.nombre_ingreso, "") as nombre_tipo'),
                DB::raw('COALESCE(p.nombre, "") as producto'),
                DB::raw("CONCAT(COALESCE(e.primer_nombre, ''), ' ', COALESCE(e.apellido_paterno, '')) as empleado"),
                DB::raw('COALESCE(pu.nombre, "") as puesto'),
                DB::raw("CASE WHEN UPPER(COALESCE(t.nombre_ingreso,'')) = 'MANO DE OBRA' AND g.comentario LIKE '%Trabajadores:%' THEN 1 ELSE 0 END as es_por_puesto")
            ])
            ->where('g.id_proyecto', $idServicio)
            ->orderBy('g.fecha','desc')
            ->get();

        $porTipo = $gastos->groupBy('nombre_tipo')->map->sum('monto');
        $conteoPorTipo = $gastos->groupBy('nombre_tipo')->map->count();
        $porMes = $gastos->groupBy(function($g){ return \Carbon\Carbon::parse($g->fecha)->format('Y-m'); })
                         ->map->sum('monto')->sortKeys();

        // Comparativa por tipo: máximo configurado vs gastado
        $maximosPorTipo = DB::table('tblcantidadxtipoingreso as c')
            ->join('ttipo_ingresoproy as t', 't.id', '=', 'c.id_tipo_ingreso')
            ->where('c.id_proyecto', $idServicio)
            ->select(['t.id as tipo_id','t.nombre_ingreso as tipo', DB::raw('COALESCE(c.monto_agregado,0) as maximo')])
            ->get();
        // Ingresado por partidas (tingresosxpartidaproyecto) por tipo para este proyecto
        // Suma por tipo desde partidas
        $ingresosPartidas = DB::table('tingresosxpartidaproyecto as tip')
            ->join('tdivisiontiemposproyecto as d', 'd.id', '=', 'tip.id_partida_proyecto')
            ->where('d.id_servicio', $idServicio)
            ->select([DB::raw('tip.tipo_ingreso as nombre'), DB::raw('SUM(tip.monto_ingreso) as total')])
            ->groupBy(DB::raw('tip.tipo_ingreso'))
            ->get();
        $normalize = function($s){
            $s = strtoupper(trim((string)$s));
            $s = strtr($s, [
                'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N',
                'Ä'=>'A','Ë'=>'E','Ï'=>'I','Ö'=>'O'
            ]);
            $s = preg_replace('/[^A-Z0-9 ]+/u','', $s);
            $s = preg_replace('/\s+/',' ', $s);
            return $s;
        };
        $ingresadoPorTipo = [];
        foreach ($ingresosPartidas as $r) {
            $clave = $normalize($r->nombre);
            $ingresadoPorTipo[$clave] = (float) $r->total;
        }

        // Fallback: suma por tipo desde gastos del proyecto (por ID de tipo)
        $gastosPorTipoIdRows = DB::table('tgastosxproyecto as g')
            ->join('ttipo_ingresoproy as t', 't.id', '=', 'g.id_tipo_ingreso')
            ->where('g.id_proyecto', $idServicio)
            ->select(['t.id as tipo_id', DB::raw('SUM(g.monto) as total')])
            ->groupBy('t.id')
            ->get();
        $gastosPorTipoId = [];
        foreach ($gastosPorTipoIdRows as $r) { $gastosPorTipoId[(int)$r->tipo_id] = (float) $r->total; }

        $comparativaTipos = $maximosPorTipo->map(function($row) use ($ingresadoPorTipo, $gastosPorTipoId, $normalize) {
            $key = $normalize($row->tipo);
            $ingresado = (float) ($ingresadoPorTipo[$key] ?? ($gastosPorTipoId[(int)$row->tipo_id] ?? 0));
            $maximo = (float) $row->maximo;
            $faltante = max(0.0, $maximo - $ingresado);
            $pct = $maximo > 0 ? round(min(100, ($ingresado / $maximo) * 100)) : 0;
            return [
                'tipo' => $row->tipo,
                'maximo' => $maximo,
                'ingresado' => $ingresado,
                'faltante' => $faltante,
                'desfasado' => max(0.0, $ingresado - $maximo),
                'porcentaje' => $pct,
            ];
        })->values();

        return view('Proyectos.gastos', compact('varpantallas','varsubmenus','servicio','gastos','porTipo','conteoPorTipo','porMes','comparativaTipos'));
    }

    public function reporteRfqs()
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        $rfqs = DB::table('tblservicios_enc as se')
            ->leftJoin('tblempleados as e', 'e.id', '=', 'se.id_vendedor')
            ->select([
                'se.id', 'se.folio', 'se.nombre', 'se.otrosconceptos1 as rfq', 'se.fecha_inicio', 'se.fecha_limite',
                DB::raw("CONCAT(e.primer_nombre,' ',e.apellido_paterno) as vendedor")
            ])
            ->whereNotNull('se.otrosconceptos1')
            ->orderBy('vendedor')
            ->orderBy('se.fecha_inicio', 'desc')
            ->get();

        // Agrupar por vendedor para gráfica y tabla
        $porVendedor = $rfqs->groupBy('vendedor')->map->count();

        return view('Proyectos.reportes.rfq', compact('varpantallas', 'varsubmenus', 'rfqs', 'porVendedor'));
    }

    // Tipos de ingreso y montos máximos por proyecto
    public function obtenerTiposIngresoMaximos(int $idServicio)
    {
        try {
            $costeo = (float) DB::table('tdivisiontiemposproyecto')->where('id_servicio', $idServicio)->sum('monto');
            // Listar tipos de ingreso del catálogo y el monto configurado (si existe) para el proyecto
            $tipos = DB::table('ttipo_ingresoproy as t')
                ->leftJoin('tblcantidadxtipoingreso as c', function ($join) use ($idServicio) {
                    $join->on('c.id_tipo_ingreso', '=', 't.id')
                         ->where('c.id_proyecto', '=', $idServicio);
                })
                ->select([
                    't.id as id_tipo',
                    't.nombre_ingreso as nombre',
                    DB::raw('COALESCE(c.monto_agregado, 0) as monto_maximo'),
                    'c.otros_conceptos1 as nota'
                ])
                ->orderBy('t.nombre_ingreso', 'asc')
                ->get();

            return response()->json(['success' => true, 'tipos' => $tipos, 'costeo' => $costeo]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function guardarTiposIngresoMaximos(Request $request, int $idServicio)
    {
        try {
            $data = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.id_tipo_ingreso' => 'required|integer',
                'items.*.monto_agregado' => 'required|numeric|min:0',
                'items.*.nota' => 'nullable|string',
            ]);

            DB::beginTransaction();

            // Estrategia simple: borrar existentes y reinsertar
            DB::table('tblcantidadxtipoingreso')->where('id_proyecto', $idServicio)->delete();

            $now = now();
            foreach ($data['items'] as $item) {
                DB::table('tblcantidadxtipoingreso')->insert([
                    'id_proyecto' => $idServicio,
                    'id_tipo_ingreso' => (int) $item['id_tipo_ingreso'],
                    'monto_agregado' => (float) $item['monto_agregado'],
                    'otros_conceptos1' => $item['nota'] ?? null,
                    'created_at' => $now,
                ]);
            }

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Datos inválidos', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function guardarIngresoProyecto(Request $request)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'id_proyecto' => 'required|integer',
                'tipo_ingreso' => 'required|string',
                'monto_ingreso' => 'required|numeric|min:0',
                'fecha' => 'required|date',
                'ruta_documento' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
                'producto_suministro' => 'nullable|integer',
                'cantidad_suministro' => 'nullable|integer|min:1',
                'empleado_local' => 'nullable|integer',
                'puesto_id' => 'nullable|integer',
                'cantidad_trab_puesto' => 'nullable|integer|min:1'
            ]);

            $filePath = null;
            if ($request->hasFile('ruta_documento')) {
                $file = $request->file('ruta_documento');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = strtolower($file->getClientOriginalExtension());
                $safeBase = Str::slug($originalName, '_');
                $unique = substr(md5(uniqid('', true)), 0, 8);
                $fileName = time() . '_' . $unique . '_' . ($safeBase ?: 'documento') . '.' . $extension;
                $destinoPublic = public_path('documentos_ingresos');
                if (!file_exists($destinoPublic)) {@mkdir($destinoPublic, 0775, true);}            
                $file->move($destinoPublic, $fileName);
                $filePath = 'documentos_ingresos/' . $fileName;
            }

            // Obtener id del tipo de ingreso desde ttipo_ingresoproy
            $idTipoIngreso = DB::table('ttipo_ingresoproy')
                ->whereRaw('UPPER(nombre_ingreso) = ?', [strtoupper((string)$request->tipo_ingreso)])
                ->value('id');
            if (!$idTipoIngreso) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de ingreso no encontrado en catálogo (ttipo_ingresoproy)'
                ], 422);
            }

            // Determinar monto: si es suministro y viene producto, calcular por precio * cantidad
            $monto = (float) ($request->monto_ingreso ?? 0);
            $piezas = null;
            if (strtoupper($request->tipo_ingreso) === 'SUMINISTRO' && $request->producto_suministro) {
                $producto = DB::table('tblproductos')->select('precio_unitario')->where('id', $request->producto_suministro)->first();
                if ($producto) {
                    $cantidad = max(1, (int) $request->cantidad_suministro);
                    $monto = $cantidad * (float) $producto->precio_unitario;
                    $piezas = $cantidad;
                }
            }

            // Determinar otrosconceptos1 y comentario dependiendo si es por empleado o por puesto
            $otros1 = null;
            $comentario = $request->input('comentario') ?: null;
            if (strtoupper($request->tipo_ingreso) === 'MANO DE OBRA') {
                $porPuesto = (bool) $request->boolean('por_puesto');
                if ($porPuesto) {
                    $otros1 = $request->input('puesto_id') ? (int)$request->input('puesto_id') : null;
                    $cantTrab = max(1, (int) ($request->input('cantidad_trab_puesto') ?: 1));
                    // Anexar cantidad al comentario para referencia
                    $comentario = trim(($comentario ? ($comentario.' | ') : '').('Trabajadores: '.$cantTrab));
                } else {
                    $otros1 = $request->input('empleado_local') ? (int)$request->input('empleado_local') : null;
                }
            }

            // Insertar en tgastosxproyecto
            $gastoId = DB::table('tgastosxproyecto')->insertGetId([
                'id_proyecto' => (int)$request->id_proyecto,
                'id_tipo_ingreso' => (int)$idTipoIngreso,
                'monto' => $monto,
                'fecha' => $request->fecha,
                'comentario' => $comentario,
                'ruta_documento' => $filePath,
                'otrosconceptos1' => $otros1,
                'otrosconceptos2' => strtoupper($request->tipo_ingreso) === 'SUMINISTRO' ? ($request->producto_suministro ?: null) : null,
                'otrosconceptos3' => $piezas,
                'created_at' => now()
            ]);

            // Si es Suministro: decrementar existencias y registrar movimiento (similar a partidas)
            if (strtoupper($request->tipo_ingreso) === 'SUMINISTRO' && $request->producto_suministro) {
                $productoId = (int) $request->producto_suministro;
                $cantidadSolicitada = max(1, (int) $request->cantidad_suministro);

                $existencias = DB::table('tblexistencias as e')
                    ->select('e.id','e.id_almacen','e.id_ubicacion','e.cantidad_existente','e.cantidad_reservada')
                    ->where('e.id_producto', $productoId)
                    ->orderBy('e.id_almacen')
                    ->orderBy('e.id_ubicacion')
                    ->get()
                    ->map(function($row){
                        $row->disponible = max(0, (float)($row->cantidad_existente ?? 0) - (float)($row->cantidad_reservada ?? 0));
                        return $row;
                    })
                    ->filter(function($row){ return $row->disponible > 0; })
                    ->values();

                $totalDisponible = (float) $existencias->sum('disponible');
                if ($totalDisponible < $cantidadSolicitada) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'Stock insuficiente para el producto seleccionado'], 422);
                }

                $idTipoMovimiento = DB::table('tbltipos_movimientos_inventario')
                    ->whereIn(DB::raw('UPPER(nombre_movimiento)'), ['ASIGNACION A PROYECTO','SALIDA A PROYECTO','CONSUMO EN PROYECTO'])
                    ->value('id') ?? 1;
                $idEstadoMovimiento = 2;
                $fechaMovimiento = $request->fecha ?: now()->format('Y-m-d');
                $usuarioMovimiento = auth()->id() ?? 0;
                $documentoRef = 'INGRESO PROYECTO #' . $gastoId;
                $observaciones = 'ASIGNACIÓN A PROYECTO (Ingreso Proyecto)';

                $restante = $cantidadSolicitada;
                foreach ($existencias as $ex) {
                    if ($restante <= 0) break;
                    $quita = min($restante, (float) $ex->disponible);
                    if ($quita <= 0) continue;

                    // Consumir reservada y luego existente
                    $consumirReservada = 0; $consumirExistente = $quita; $reservadaActual = (float) ($ex->cantidad_reservada ?? 0);
                    if ($reservadaActual > 0) { $consumirReservada = min($reservadaActual, $quita); $consumirExistente = max(0, $quita - $consumirReservada); }

                    DB::table('tblexistencias')
                        ->where('id', $ex->id)
                        ->update([
                            'cantidad_reservada' => DB::raw('GREATEST(0, cantidad_reservada - ' . (float)$consumirReservada . ')'),
                            'cantidad_existente' => DB::raw('GREATEST(0, cantidad_existente - ' . (float)$consumirExistente . ')'),
                            'updated_at' => now()
                        ]);

                    $this->Registramovinventario(
                        $productoId,
                        (int) $ex->id_almacen,
                        (int) $ex->id_ubicacion,
                        (int) $idTipoMovimiento,
                        (int) $idEstadoMovimiento,
                        (int) $quita,
                        $fechaMovimiento,
                        $documentoRef,
                        $observaciones,
                        now()->toDateTimeString(),
                        (int) $usuarioMovimiento,
                        0
                    );

                    $restante -= (int) $quita;
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Ingreso de proyecto guardado correctamente']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function agregarSiguienteDivision(Request $request, $idServicio)
    {
        try {
            $servicio = DB::table('tblservicios_enc')->select('id','estado')->where('id', $idServicio)->first();
            if (!$servicio) {
                return response()->json(['success' => false, 'message' => 'Proyecto no encontrado'], 404);
            }
            $estadoUpper = strtoupper($servicio->estado ?? '');
            if (!in_array($estadoUpper, ['EN PROCESO', 'BORRADOR'])) {
                return response()->json(['success' => false, 'message' => 'Solo se puede modificar cuando el proyecto está EN PROCESO o BORRADOR'], 400);
            }

            $ultima = DB::table('tdivisiontiemposproyecto')
                ->where('id_servicio', $idServicio)
                ->orderBy('plazo', 'desc')
                ->first();

            if (!$ultima) {
                return response()->json(['success' => false, 'message' => 'No existen divisiones previas para este proyecto'], 400);
            }

            // Validar monto opcional
            $montoNuevo = $request->input('monto');
            if (!is_null($montoNuevo)) {
                if (!is_numeric($montoNuevo)) {
                    return response()->json(['success' => false, 'message' => 'El monto proporcionado no es válido'], 422);
                }
                $montoNuevo = (float) $montoNuevo;
                if ($montoNuevo < 0) {
                    return response()->json(['success' => false, 'message' => 'El monto debe ser mayor o igual a 0'], 422);
                }
            }

            $nuevoPlazoNumero = ((int)$ultima->plazo) + 1;
            $idTipoPlazo = (int)$ultima->id_tipo_plazo;

            $mapIdToKey = [
                8 => 'semanal',
                1 => 'quincenal',
                2 => 'mensual',
                3 => 'bimestral',
                4 => 'trimestral',
                5 => 'semestral',
                6 => 'anual',
                7 => 'personalizado',
            ];
            $tipo = $mapIdToKey[$idTipoPlazo] ?? 'mensual';

            $fechaInicioUltima = new \DateTime($ultima->otros_conceptos1);
            $fechaFinUltima = new \DateTime($ultima->otros_concetpos2);

            $nuevaFechaInicio = clone $fechaFinUltima;
            $nuevaFechaInicio->modify('+1 day');
            $nuevaFechaFin = clone $nuevaFechaInicio;

            switch ($tipo) {
                case 'semanal':
                    $nuevaFechaFin->modify('+6 day');
                    break;
                case 'quincenal':
                    $nuevaFechaFin->modify('+14 day');
                    break;
                case 'mensual':
                    $nuevaFechaFin->modify('last day of this month');
                    break;
                case 'bimestral':
                    $nuevaFechaFin->modify('last day of next month');
                    break;
                case 'trimestral':
                    $nuevaFechaFin->modify('last day of +2 months');
                    break;
                case 'semestral':
                    $nuevaFechaFin->modify('last day of +5 months');
                    break;
                case 'anual':
                    $nuevaFechaFin->modify('last day of +' . 11 . ' months');
                    break;
                case 'personalizado':
                default:
                    $dias = $fechaInicioUltima->diff($fechaFinUltima)->days;
                    $nuevaFechaFin->modify('+' . max(0, $dias) . ' day');
                    break;
            }

            $idNueva = DB::table('tdivisiontiemposproyecto')->insertGetId([
                'id_servicio' => $idServicio,
                'id_tipo_plazo' => $idTipoPlazo,
                'plazo' => $nuevoPlazoNumero,
                'monto' => is_null($montoNuevo) ? $ultima->monto : $montoNuevo,
                'otros_conceptos1' => $nuevaFechaInicio->format('Y-m-d'),
                'otros_concetpos2' => $nuevaFechaFin->format('Y-m-d'),
                'otros_conceptos3' => $ultima->otros_conceptos3,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'División agregada correctamente',
                'division_id' => $idNueva,
                'plazo' => $nuevoPlazoNumero,
                'fecha_inicio' => $nuevaFechaInicio->format('Y-m-d'),
                'fecha_fin' => $nuevaFechaFin->format('Y-m-d'),
                'monto' => is_null($montoNuevo) ? (float) $ultima->monto : $montoNuevo,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar la división: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function actualizarMontoDivision(Request $request, $idDivision)
    {
        $request->validate([
            'monto' => 'required|numeric|min:0'
        ]);

        $division = DB::table('tdivisiontiemposproyecto')->where('id', $idDivision)->first();
        if (!$division) {
            return response()->json(['success' => false, 'message' => 'División no encontrada'], 404);
        }

        $partidasCount = DB::table('tingresosxpartidaproyecto')->where('id_partida_proyecto', $idDivision)->count();
        if ($partidasCount > 0) {
            return response()->json(['success' => false, 'message' => 'No se puede editar el monto: la división ya tiene partidas asignadas'], 400);
        }

        DB::table('tdivisiontiemposproyecto')
            ->where('id', $idDivision)
            ->update([
                'monto' => $request->monto,
                'updated_at' => now(),
            ]);

        return response()->json(['success' => true, 'message' => 'Monto actualizado correctamente']);
    }

    public function eliminarDivisionAutorizado(Request $request, $idDivision)
    {
        $request->validate([
            'empleado_id' => 'required|integer',
            'codigo' => 'required|string'
        ]);

        // Verificar división
        $division = DB::table('tdivisiontiemposproyecto')->where('id', $idDivision)->first();
        if (!$division) {
            return response()->json(['success' => false, 'message' => 'División no encontrada'], 404);
        }

        // Verificar que no existan partidas asociadas
        $partidasCount = DB::table('tingresosxpartidaproyecto')->where('id_partida_proyecto', $idDivision)->count();
        if ($partidasCount > 0) {
            return response()->json(['success' => false, 'message' => 'No se puede eliminar: la división tiene partidas asociadas'], 400);
        }


        // Eliminar división
        DB::table('tdivisiontiemposproyecto')->where('id', $idDivision)->delete();

        return response()->json(['success' => true, 'message' => 'División eliminada correctamente']);
    }

    public function productosConExistencia(Request $request)
    {
        try {
            $q = trim((string) $request->get('q', ''));

            $query = DB::table('tblexistencias as e')
                ->join('tblproductos as p', 'p.id', '=', 'e.id_producto')
                ->select([
                    'p.id',
                    'p.nombre',
                    'p.descripcion',
                    'p.precio_unitario',
                    DB::raw('SUM(GREATEST(0, COALESCE(e.cantidad_existente,0) - COALESCE(e.cantidad_reservada,0))) as disponible_total')
                ]);

            if ($q !== '') {
                $query->where(function ($sub) use ($q) {
                    $sub->where('p.nombre', 'like', "%$q%")
                        ->orWhere('p.descripcion', 'like', "%$q%")
                        ->orWhere('p.sku', 'like', "%$q%")
                        ->orWhere('p.codigo_barras', 'like', "%$q%");
                });
            }

            $rows = $query
                ->groupBy('p.id', 'p.nombre', 'p.descripcion', 'p.precio_unitario')
                ->havingRaw('SUM(GREATEST(0, COALESCE(e.cantidad_existente,0) - COALESCE(e.cantidad_reservada,0))) > 0')
                ->orderBy('p.nombre', 'asc')
                ->limit(100)
                ->get();

            $result = $rows->map(function ($row) {
                return [
                    'id' => $row->id,
                    'nombre' => $row->nombre,
                    'descripcion' => $row->descripcion,
                    'precio_unitario' => (float) $row->precio_unitario,
                    'disponible_total' => (float) $row->disponible_total,
                ];
            });

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    }

    public function existenciasDetalladasPorProducto(Request $request, int $productoId)
    {
        try {
            $rows = DB::table('tblexistencias as e')
                ->join('tblalmacenes as a', 'a.id', '=', 'e.id_almacen')
                ->join('tblubicaciones as u', 'u.id', '=', 'e.id_ubicacion')
                ->select([
                    'e.id as existencia_id',
                    'e.id_producto',
                    'e.id_almacen',
                    'e.id_ubicacion',
                    'e.cantidad_existente',
                    'e.cantidad_reservada',
                    DB::raw('GREATEST(0, COALESCE(e.cantidad_existente,0) - COALESCE(e.cantidad_reservada,0)) as disponible'),
                    'a.folio_interno as almacen',
                    'u.folio_interno as ubicacion'
                ])
                ->where('e.id_producto', $productoId)
                ->havingRaw('disponible > 0')
                ->orderBy('almacen', 'asc')
                ->orderBy('ubicacion', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'existencias' => $rows,
                'total_disponible' => (float) $rows->sum('disponible')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener existencias: ' . $e->getMessage()
            ], 500);
        }
    }
} 