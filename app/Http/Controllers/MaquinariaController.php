<?php

namespace App\Http\Controllers;

use App\Models\CausaFalla;
use App\Models\IncidenciaMaquina;
use App\Models\InspeccionDetalle;
use App\Models\InspeccionFoto;
use App\Models\InspeccionMaquina;
use App\Models\InspeccionParametro;
use App\Models\Maquina;
use App\Models\Mantenimiento;
use App\Models\MantenimientoFoto;
use App\Models\ProgramacionMantenimiento;
use App\Models\Productos;
use App\Models\RefaccionMaquina;
use App\Models\Ubicaciones;
use Carbon\Carbon;
use App\Traits\AlmacenesTraits;
use App\Traits\MenuTrait;
use App\Traits\ProductosTraits;
use App\Traits\SistemasTraits;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MaquinariaController extends Controller
{
    use MenuTrait;
    use AlmacenesTraits;
    use ProductosTraits;
    use SistemasTraits;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $maquinas = Maquina::with(['almacen', 'ubicacionAlmacen', 'operadorResponsable'])
            ->orderBy('nombre')
            ->get();
        $empleados = $this->Listadoempleadosalmacen();
        $parametrosRevision = InspeccionParametro::query()
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        return view('Produccion.maquinas', compact(
            'varpantallas',
            'varsubmenus',
            'maquinas',
            'empleados',
            'parametrosRevision'
        ) + $this->datosAlmacenUbicacion());
    }

    public function storeParametroRevision(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:200'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $orden = $validated['orden'] ?? null;
        if ($orden === null) {
            $orden = ((int) InspeccionParametro::max('orden')) + 1;
        }

        InspeccionParametro::create([
            'nombre' => trim($validated['nombre']),
            'orden' => $orden,
            'estatus' => 'A',
        ]);

        return redirect()
            ->route('produccion.maquinas')
            ->withFragment('lista-revision')
            ->with('success', 'Parámetro de revisión agregado al catálogo.');
    }

    public function updateParametroRevision(Request $request, int $id): RedirectResponse
    {
        $parametro = InspeccionParametro::findOrFail($id);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:200'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'estatus' => ['required', 'in:A,I'],
        ]);

        $parametro->update([
            'nombre' => trim($validated['nombre']),
            'orden' => $validated['orden'] ?? $parametro->orden,
            'estatus' => $validated['estatus'],
        ]);

        return redirect()
            ->route('produccion.maquinas')
            ->withFragment('lista-revision')
            ->with('success', 'Parámetro de revisión actualizado.');
    }

    public function inactivarParametroRevision(int $id): RedirectResponse
    {
        $parametro = InspeccionParametro::findOrFail($id);
        $parametro->update(['estatus' => 'I']);

        return redirect()
            ->route('produccion.maquinas')
            ->withFragment('lista-revision')
            ->with('success', 'Parámetro inactivado. Ya no aparecerá en nuevas inspecciones.');
    }

    public function activarParametroRevision(int $id): RedirectResponse
    {
        $parametro = InspeccionParametro::findOrFail($id);
        $parametro->update(['estatus' => 'A']);

        return redirect()
            ->route('produccion.maquinas')
            ->withFragment('lista-revision')
            ->with('success', 'Parámetro reactivado en la lista de revisión.');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validarMaquina($request);
        unset($validated['manual']);
        $validated['para_reproceso'] = $request->boolean('para_reproceso');
        $this->validarUbicacionPerteneceAlmacen($validated['id_almacen'], $validated['id_ubicacion']);

        if ($request->hasFile('manual')) {
            $validated['ruta_manual'] = $this->guardarManual($request->file('manual'));
        }

        Maquina::create(array_merge($validated, ['estatus' => 'A']));

        return redirect()
            ->route('produccion.maquinas')
            ->with('success', 'Máquina registrada correctamente.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $maquina = Maquina::findOrFail($id);
        $validated = $this->validarMaquina($request);
        unset($validated['manual']);
        $validated['para_reproceso'] = $request->boolean('para_reproceso');
        $this->validarUbicacionPerteneceAlmacen($validated['id_almacen'], $validated['id_ubicacion']);

        if ($request->hasFile('manual')) {
            $validated['ruta_manual'] = $this->guardarManual($request->file('manual'), $maquina->ruta_manual);
        }

        $maquina->update($validated);

        return redirect()
            ->route('produccion.maquinas')
            ->with('success', 'Máquina actualizada correctamente.');
    }

    public function refacciones(int $id): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $maquina = Maquina::with(['almacen', 'ubicacionAlmacen'])->findOrFail($id);
        $refacciones = collect();

        if ($maquina->id_ubicacion) {
            $refacciones = $this->Listadoproductosxubicacion((int) $maquina->id_ubicacion)
                ->filter(function ($item) use ($maquina) {
                    return (int) $item->alma === (int) $maquina->id_almacen;
                })
                ->values();
        }

        return view('Produccion.refacciones', compact(
            'varpantallas',
            'varsubmenus',
            'maquina',
            'refacciones'
        ));
    }

    public function mantenimientos(int $id): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $maquina = Maquina::with('refacciones')->findOrFail($id);
        $empleados = $this->Listadoempleadosalmacen();
        $incidencias = $this->listadoIncidenciasMaquina($maquina->id);
        $causasFalla = CausaFalla::activas()->get();
        $refaccionesDisponibles = collect();
        if ($maquina->id_ubicacion) {
            $idsVinculados = $maquina->refacciones->pluck('id');
            $refaccionesDisponibles = $this->Listadoproductosxubicacion((int) $maquina->id_ubicacion)
                ->filter(function ($item) use ($maquina, $idsVinculados) {
                    if ((int) $item->alma !== (int) $maquina->id_almacen) {
                        return false;
                    }
                    if ($idsVinculados->isEmpty()) {
                        return true;
                    }

                    return $idsVinculados->contains((int) $item->id_producto);
                })
                ->values();
        }
        $historial = Mantenimiento::with(['responsable', 'causaFalla', 'refacciones.producto'])
            ->withCount('fotos')
            ->where('maquina_id', $maquina->id)
            ->orderByDesc('fecha_programada')
            ->orderByDesc('id')
            ->get();

        return view('Produccion.mantenimientos', compact(
            'varpantallas',
            'varsubmenus',
            'maquina',
            'historial',
            'empleados',
            'incidencias',
            'causasFalla',
            'refaccionesDisponibles'
        ));
    }

    public function storeMantenimiento(Request $request, int $id): RedirectResponse
    {
        $maquina = Maquina::findOrFail($id);
        $validated = $this->validarMantenimiento($request, $maquina->id);

        $horasParo = $validated['horas_paro'] ?? null;
        if ($horasParo === null && !empty($validated['fecha_inicio']) && !empty($validated['fecha_fin'])) {
            $inicio = Carbon::parse($validated['fecha_inicio']);
            $fin = Carbon::parse($validated['fecha_fin']);
            if ($fin->gte($inicio)) {
                $horasParo = round($inicio->floatDiffInHours($fin), 2);
            }
        }

        $costoRefaccion = isset($validated['costo_refacciones'])
            ? (float) $validated['costo_refacciones']
            : null;
        $cantRefaccion = isset($validated['cantidad_refaccion'])
            ? (float) $validated['cantidad_refaccion']
            : null;
        $productoRefaccionId = isset($validated['producto_refaccion_id'])
            ? (int) $validated['producto_refaccion_id']
            : null;
        $costoUnitario = null;

        if ($productoRefaccionId && $cantRefaccion !== null && $cantRefaccion > 0) {
            $producto = Productos::find($productoRefaccionId);
            $costoUnitario = $producto
                ? (float) ($producto->costo_compra ?: $producto->precio_unitario ?: 0)
                : 0.0;
            if ($costoRefaccion === null) {
                $costoRefaccion = round($costoUnitario * $cantRefaccion, 2);
            }
        }

        $validated['costo_refacciones'] = $costoRefaccion;
        $validated['costo_mano_obra'] = $validated['costo_mano_obra'] ?? null;

        $mantenimiento = null;

        DB::transaction(function () use (
            $maquina,
            $validated,
            $horasParo,
            $productoRefaccionId,
            $cantRefaccion,
            $costoUnitario,
            $costoRefaccion,
            $request,
            &$mantenimiento
        ) {
            $mantenimiento = Mantenimiento::create([
                'folio' => $this->generarFolioMantenimiento(),
                'maquina_id' => $maquina->id,
                'tipo' => $validated['tipo'],
                'fecha_reporte' => $validated['fecha_reporte'] ?? now()->toDateString(),
                'fecha_programada' => $validated['fecha_programada']
                    ?? ($validated['fecha_reporte'] ?? now()->toDateString()),
                'fecha_inicio' => $validated['fecha_inicio'] ?? null,
                'fecha_fin' => $validated['fecha_fin'] ?? null,
                'responsable_id' => $validated['responsable_id'] ?? null,
                'incidencia_id' => $validated['incidencia_id'] ?? null,
                'causa_falla_id' => $validated['causa_falla_id'] ?? null,
                'descripcion' => $validated['descripcion'],
                'horas_paro' => $horasParo,
                'costo_mano_obra' => $validated['costo_mano_obra'],
                'costo_refacciones' => $costoRefaccion,
                'costo_total' => $this->calcularCostoTotal($validated),
                'resultado' => $validated['resultado'] ?? null,
                'estatus' => $validated['estatus'],
                'created_at' => now(),
            ]);

            if ($productoRefaccionId && $cantRefaccion !== null && $cantRefaccion > 0) {
                RefaccionMaquina::create([
                    'mantenimiento_id' => $mantenimiento->id,
                    'producto_id' => $productoRefaccionId,
                    'cantidad' => $cantRefaccion,
                    'costo_unitario' => $costoUnitario,
                    'costo_total' => $costoRefaccion,
                    'observaciones' => 'Salida por mantenimiento correctivo ' . $mantenimiento->folio,
                ]);

                $this->descontarRefaccionUbicacionMaquina(
                    $maquina,
                    $productoRefaccionId,
                    $cantRefaccion,
                    $mantenimiento->folio
                );
            }

            $this->guardarFotosMantenimiento(
                $request,
                $mantenimiento,
                $request->input('descripcion_fotos')
            );
        });

        return redirect()
            ->route('produccion.maquinas.mantenimientos.detalle', [$maquina->id, $mantenimiento->id])
            ->with('success', 'Orden de mantenimiento ' . $mantenimiento->folio . ' registrada correctamente.');
    }

    public function detalleMantenimiento(int $id, int $mantenimientoId): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $maquina = Maquina::findOrFail($id);
        $mantenimiento = Mantenimiento::with(['responsable', 'fotos', 'incidencia', 'causaFalla', 'refacciones.producto'])
            ->where('maquina_id', $maquina->id)
            ->findOrFail($mantenimientoId);

        return view('Produccion.mantenimiento_detalle', compact(
            'varpantallas',
            'varsubmenus',
            'maquina',
            'mantenimiento'
        ));
    }

    public function updateMantenimiento(Request $request, int $id, int $mantenimientoId): RedirectResponse
    {
        $maquina = Maquina::findOrFail($id);
        $mantenimiento = Mantenimiento::where('maquina_id', $maquina->id)->findOrFail($mantenimientoId);

        if (in_array($mantenimiento->estatus, ['FINALIZADO', 'CANCELADO'], true)) {
            return redirect()
                ->route('produccion.maquinas.mantenimientos.detalle', [$maquina->id, $mantenimiento->id])
                ->with('warning', 'Este mantenimiento ya está cerrado y no puede modificarse.');
        }

        $validated = $request->validate([
            'estatus' => ['required', 'in:PENDIENTE,EN_PROCESO,FINALIZADO,CANCELADO'],
            'fecha_fin' => ['nullable', 'date'],
            'horas_paro' => ['nullable', 'numeric', 'min:0'],
            'costo_mano_obra' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (!empty($validated['fecha_fin']) && $mantenimiento->fecha_inicio) {
            $fechaFin = \Carbon\Carbon::parse($validated['fecha_fin']);
            if ($fechaFin->lt($mantenimiento->fecha_inicio)) {
                throw ValidationException::withMessages([
                    'fecha_fin' => 'La fecha fin no puede ser anterior a la fecha de inicio.',
                ]);
            }
        }

        $costoManoObra = $validated['costo_mano_obra'] ?? $mantenimiento->costo_mano_obra;

        $mantenimiento->update([
            'estatus' => $validated['estatus'],
            'fecha_fin' => $validated['fecha_fin'] ?? null,
            'horas_paro' => $validated['horas_paro'] ?? null,
            'costo_mano_obra' => $costoManoObra,
            'costo_total' => $this->calcularCostoTotal([
                'costo_mano_obra' => $costoManoObra,
                'costo_refacciones' => $mantenimiento->costo_refacciones,
            ]),
        ]);

        return redirect()
            ->route('produccion.maquinas.mantenimientos.detalle', [$maquina->id, $mantenimiento->id])
            ->with('success', 'Mantenimiento actualizado correctamente.');
    }

    public function mantenimientoPreventivo(int $id): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $maquina = Maquina::findOrFail($id);
        $empleados = $this->Listadoempleadosalmacen();
        $incidencias = $this->listadoIncidenciasMaquina($maquina->id);
        $actividadesSugeridas = $this->actividadesPreventivas();
        $programaciones = ProgramacionMantenimiento::with('responsable')
            ->where('maquina_id', $maquina->id)
            ->where('activo', 1)
            ->orderBy('proxima_ejecucion')
            ->orderBy('actividad')
            ->get();
        $historial = Mantenimiento::with('responsable')
            ->withCount('fotos')
            ->where('maquina_id', $maquina->id)
            ->where('tipo', 'PREVENTIVO')
            ->orderByDesc('fecha_programada')
            ->orderByDesc('id')
            ->get();

        $programacionSeleccionada = null;
        if (request()->filled('programacion_id')) {
            $programacionSeleccionada = ProgramacionMantenimiento::where('maquina_id', $maquina->id)
                ->where('activo', 1)
                ->find(request('programacion_id'));
        }

        return view('Produccion.mantenimiento_preventivo', compact(
            'varpantallas',
            'varsubmenus',
            'maquina',
            'empleados',
            'actividadesSugeridas',
            'programaciones',
            'historial',
            'programacionSeleccionada',
            'incidencias'
        ));
    }

    public function storeProgramacionMantenimiento(Request $request, int $id): RedirectResponse
    {
        $maquina = Maquina::findOrFail($id);

        $validated = $request->validate([
            'actividad' => ['required', 'string', 'max:200'],
            'frecuencia' => ['required', 'in:SEMANAL,QUINCENAL,MENSUAL,TRIMESTRAL,SEMESTRAL,ANUAL'],
            'proxima_ejecucion' => ['required', 'date'],
            'responsable_id' => ['nullable', 'integer', 'exists:tblempleados,id'],
        ]);

        ProgramacionMantenimiento::create([
            'maquina_id' => $maquina->id,
            'actividad' => $validated['actividad'],
            'frecuencia' => $validated['frecuencia'],
            'proxima_ejecucion' => $validated['proxima_ejecucion'],
            'responsable_id' => $validated['responsable_id'] ?? null,
            'activo' => 1,
        ]);

        return redirect()
            ->route('produccion.maquinas.mantenimiento_preventivo', $maquina->id)
            ->with('success', 'Actividad programada correctamente.');
    }

    public function storeEjecucionPreventiva(Request $request, int $id): RedirectResponse
    {
        $maquina = Maquina::findOrFail($id);

        $validated = $request->validate([
            'programacion_id' => ['nullable', 'integer'],
            'actividad' => ['required', 'string', 'max:200'],
            'fecha_programada' => ['required', 'date'],
            'responsable_id' => ['required', 'integer', 'exists:tblempleados,id'],
            'observaciones' => ['nullable', 'string'],
            'resultado' => ['required', 'string'],
            'fotografias' => ['nullable', 'array'],
            'fotografias.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'descripcion_fotos' => ['nullable', 'string', 'max:500'],
            'incidencia_id' => [
                'nullable',
                'integer',
                Rule::exists('tbl_incidencias_maquina', 'id')->where(fn ($query) => $query->where('maquina_id', $maquina->id)),
            ],
        ]);

        $descripcion = $validated['actividad'];
        if (!empty($validated['observaciones'])) {
            $descripcion .= "\n\nObservaciones: " . $validated['observaciones'];
        }

        $mantenimiento = Mantenimiento::create([
            'maquina_id' => $maquina->id,
            'tipo' => 'PREVENTIVO',
            'fecha_programada' => $validated['fecha_programada'],
            'fecha_inicio' => $validated['fecha_programada'],
            'fecha_fin' => now(),
            'responsable_id' => $validated['responsable_id'],
            'incidencia_id' => $validated['incidencia_id'] ?? null,
            'descripcion' => $descripcion,
            'estatus' => 'FINALIZADO',
            'resultado' => $validated['resultado'],
            'created_at' => now(),
        ]);

        $this->guardarFotosMantenimiento($request, $mantenimiento, $request->input('descripcion_fotos'));

        if (!empty($validated['programacion_id'])) {
            $programacion = ProgramacionMantenimiento::where('maquina_id', $maquina->id)
                ->where('activo', 1)
                ->find($validated['programacion_id']);

            if ($programacion) {
                $fechaEjecucion = Carbon::parse($validated['fecha_programada']);
                $programacion->update([
                    'ultima_ejecucion' => $fechaEjecucion,
                    'proxima_ejecucion' => $this->calcularProximaEjecucion($fechaEjecucion, $programacion->frecuencia),
                ]);
            }
        }

        return redirect()
            ->route('produccion.maquinas.mantenimientos.detalle', [$maquina->id, $mantenimiento->id])
            ->with('success', 'Mantenimiento preventivo registrado correctamente.');
    }

    public function inactivarProgramacionMantenimiento(int $id, int $programacionId): RedirectResponse
    {
        $maquina = Maquina::findOrFail($id);
        $programacion = ProgramacionMantenimiento::where('maquina_id', $maquina->id)->findOrFail($programacionId);
        $programacion->update(['activo' => 0]);

        return redirect()
            ->route('produccion.maquinas.mantenimiento_preventivo', $maquina->id)
            ->with('success', 'Programación inactivada correctamente.');
    }

    public function inspeccionesSemanales(int $id): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $maquina = Maquina::findOrFail($id);
        $empleados = $this->Listadoempleadosalmacen();
        $conceptos = $this->conceptosInspeccion();
        $categoriasFotos = $this->categoriasFotosInspeccion();
        $historial = InspeccionMaquina::with(['inspector', 'supervisor'])
            ->withCount('fotos')
            ->where('maquina_id', $maquina->id)
            ->orderByDesc('fecha_inspeccion')
            ->orderByDesc('id')
            ->get();

        return view('Produccion.inspecciones_semanales', compact(
            'varpantallas',
            'varsubmenus',
            'maquina',
            'empleados',
            'conceptos',
            'categoriasFotos',
            'historial'
        ));
    }

    public function storeInspeccion(Request $request, int $id): RedirectResponse
    {
        $maquina = Maquina::findOrFail($id);
        $validated = $this->validarInspeccion($request);

        $resultadosDetalle = collect($validated['detalle'])->pluck('resultado');
        $requiereMantenimiento = !empty($validated['requiere_mantenimiento'])
            || $resultadosDetalle->contains('CRITICO');

        $inspeccion = InspeccionMaquina::create([
            'maquina_id' => $maquina->id,
            'fecha_inspeccion' => $validated['fecha_inspeccion'],
            'inspector_id' => $validated['inspector_id'],
            'supervisor_id' => $validated['supervisor_id'] ?? null,
            'resultado_general' => $validated['resultado_general'],
            'observaciones_generales' => $validated['observaciones_generales'] ?? null,
            'requiere_mantenimiento' => $requiereMantenimiento ? 1 : 0,
            'estatus' => 'ABIERTA',
            'created_at' => now(),
        ]);

        foreach ($validated['detalle'] as $item) {
            InspeccionDetalle::create([
                'inspeccion_id' => $inspeccion->id,
                'concepto' => trim((string) $item['concepto']),
                'resultado' => $item['resultado'],
                'observaciones' => $item['observaciones'] ?? null,
            ]);
        }

        $this->guardarFotosInspeccion($request, $inspeccion);

        return redirect()
            ->route('produccion.maquinas.inspecciones.detalle', [$maquina->id, $inspeccion->id])
            ->with('success', 'Inspección registrada correctamente.');
    }

    public function detalleInspeccion(int $id, int $inspeccionId): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $maquina = Maquina::findOrFail($id);
        $empleados = $this->Listadoempleadosalmacen();
        $inspeccion = InspeccionMaquina::with(['inspector', 'supervisor', 'detalles', 'fotos'])
            ->where('maquina_id', $maquina->id)
            ->findOrFail($inspeccionId);

        return view('Produccion.inspeccion_detalle', compact(
            'varpantallas',
            'varsubmenus',
            'maquina',
            'inspeccion',
            'empleados'
        ));
    }

    public function cerrarInspeccion(Request $request, int $id, int $inspeccionId): RedirectResponse
    {
        $maquina = Maquina::findOrFail($id);
        $inspeccion = InspeccionMaquina::where('maquina_id', $maquina->id)->findOrFail($inspeccionId);

        if ($inspeccion->estatus === 'CERRADA') {
            return redirect()
                ->route('produccion.maquinas.inspecciones.detalle', [$maquina->id, $inspeccion->id])
                ->with('warning', 'La inspección ya estaba cerrada.');
        }

        $validated = $request->validate([
            'supervisor_id' => ['required', 'integer', 'exists:tblempleados,id'],
        ]);

        $inspeccion->update([
            'supervisor_id' => $validated['supervisor_id'],
            'estatus' => 'CERRADA',
        ]);

        return redirect()
            ->route('produccion.maquinas.inspecciones.detalle', [$maquina->id, $inspeccion->id])
            ->with('success', 'Inspección cerrada correctamente.');
    }

    public function incidencias(int $id): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $maquina = Maquina::findOrFail($id);
        $empleados = $this->Listadoempleadosalmacen();
        $inspecciones = InspeccionMaquina::where('maquina_id', $maquina->id)
            ->orderByDesc('fecha_inspeccion')
            ->orderByDesc('id')
            ->get();
        $historial = IncidenciaMaquina::with('responsable')
            ->where('maquina_id', $maquina->id)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        return view('Produccion.incidencias', compact(
            'varpantallas',
            'varsubmenus',
            'maquina',
            'empleados',
            'inspecciones',
            'historial'
        ));
    }

    public function storeIncidencia(Request $request, int $id): RedirectResponse
    {
        $maquina = Maquina::findOrFail($id);

        $validated = $request->validate([
            'fecha' => ['required', 'date'],
            'titulo' => ['required', 'string', 'max:200'],
            'descripcion' => ['required', 'string'],
            'prioridad' => ['required', 'in:BAJA,MEDIA,ALTA,CRITICA'],
            'responsable_id' => ['required', 'integer', 'exists:tblempleados,id'],
            'fecha_compromiso' => ['nullable', 'date', 'after_or_equal:fecha'],
            'inspeccion_id' => ['nullable', 'integer'],
            'estatus' => ['required', 'in:ABIERTA,EN_PROCESO,CERRADA'],
        ]);

        if (!empty($validated['inspeccion_id'])) {
            InspeccionMaquina::where('maquina_id', $maquina->id)
                ->findOrFail($validated['inspeccion_id']);
        }

        $incidencia = IncidenciaMaquina::create([
            'maquina_id' => $maquina->id,
            'inspeccion_id' => $validated['inspeccion_id'] ?? null,
            'fecha' => $validated['fecha'],
            'titulo' => $validated['titulo'],
            'descripcion' => $validated['descripcion'],
            'prioridad' => $validated['prioridad'],
            'responsable_id' => $validated['responsable_id'],
            'fecha_compromiso' => $validated['fecha_compromiso'] ?? null,
            'fecha_cierre' => $validated['estatus'] === 'CERRADA' ? now()->toDateString() : null,
            'estatus' => $validated['estatus'],
            'created_at' => now(),
        ]);

        return redirect()
            ->route('produccion.maquinas.incidencias.detalle', [$maquina->id, $incidencia->id])
            ->with('success', 'Incidencia registrada correctamente.');
    }

    public function detalleIncidencia(int $id, int $incidenciaId): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $maquina = Maquina::findOrFail($id);
        $incidencia = IncidenciaMaquina::with(['responsable', 'inspeccion'])
            ->where('maquina_id', $maquina->id)
            ->findOrFail($incidenciaId);

        return view('Produccion.incidencia_detalle', compact(
            'varpantallas',
            'varsubmenus',
            'maquina',
            'incidencia'
        ));
    }

    public function updateIncidencia(Request $request, int $id, int $incidenciaId): RedirectResponse
    {
        $maquina = Maquina::findOrFail($id);
        $incidencia = IncidenciaMaquina::where('maquina_id', $maquina->id)->findOrFail($incidenciaId);

        if ($incidencia->estatus === 'CERRADA') {
            return redirect()
                ->route('produccion.maquinas.incidencias.detalle', [$maquina->id, $incidencia->id])
                ->with('warning', 'La incidencia ya está cerrada y no puede modificarse.');
        }

        $validated = $request->validate([
            'estatus' => ['required', 'in:ABIERTA,EN_PROCESO,CERRADA'],
            'fecha_compromiso' => ['nullable', 'date'],
            'fecha_cierre' => ['nullable', 'date'],
        ]);

        if ($validated['estatus'] === 'CERRADA' && empty($validated['fecha_cierre'])) {
            $validated['fecha_cierre'] = now()->toDateString();
        }

        if ($validated['estatus'] !== 'CERRADA') {
            $validated['fecha_cierre'] = null;
        }

        $incidencia->update([
            'estatus' => $validated['estatus'],
            'fecha_compromiso' => $validated['fecha_compromiso'] ?? $incidencia->fecha_compromiso,
            'fecha_cierre' => $validated['fecha_cierre'] ?? null,
        ]);

        return redirect()
            ->route('produccion.maquinas.incidencias.detalle', [$maquina->id, $incidencia->id])
            ->with('success', 'Incidencia actualizada correctamente.');
    }

    public function inactivar(int $id): RedirectResponse
    {
        $maquina = Maquina::findOrFail($id);

        if ($maquina->estatus === 'I') {
            return redirect()
                ->route('produccion.maquinas')
                ->with('warning', 'La máquina ya estaba inactiva.');
        }

        $maquina->update(['estatus' => 'I']);

        return redirect()
            ->route('produccion.maquinas')
            ->with('success', 'Máquina inactivada correctamente.');
    }

    public function activar(int $id): RedirectResponse
    {
        $maquina = Maquina::findOrFail($id);

        if ($maquina->estatus === 'A') {
            return redirect()
                ->route('produccion.maquinas')
                ->with('warning', 'La máquina ya está activa.');
        }

        $maquina->update(['estatus' => 'A']);

        return redirect()
            ->route('produccion.maquinas')
            ->with('success', 'Máquina activada correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function datosAlmacenUbicacion(): array
    {
        $almacenes = $this->Listadoalmacenes();
        $ubicaciones = collect();

        foreach ($almacenes as $almacen) {
            $ubicaciones = $ubicaciones->merge($this->Listadoubicacionesxidalmacen($almacen->id));
        }

        return [
            'almacenes' => $almacenes,
            'ubicaciones' => $ubicaciones,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validarMaquina(Request $request): array
    {
        return $request->validate([
            'codigo' => ['required', 'string', 'max:50'],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'ubicacion' => ['nullable', 'string', 'max:150'],
            'id_almacen' => ['required', 'integer', 'exists:tblalmacenes,id'],
            'id_ubicacion' => ['required', 'integer', 'exists:tblubicaciones,id'],
            'capacidad_pr_hora' => ['nullable', 'numeric', 'min:0'],
            'potencia_kw' => ['nullable', 'numeric', 'min:0'],
            'horas_turno' => ['nullable', 'numeric', 'min:0'],
            'turnos_dia' => ['nullable', 'integer', 'min:1', 'max:4'],
            'operador_responsable_id' => ['nullable', 'integer', 'exists:tblempleados,id'],
            'para_reproceso' => ['nullable', 'boolean'],
            'manual' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:20480'],
        ]);
    }

    private function validarUbicacionPerteneceAlmacen(int $idAlmacen, int $idUbicacion): void
    {
        $ubicacion = Ubicaciones::find($idUbicacion);

        if (!$ubicacion || (int) $ubicacion->id_almacen !== $idAlmacen) {
            throw ValidationException::withMessages([
                'id_ubicacion' => 'La ubicación seleccionada no pertenece al almacén indicado.',
            ]);
        }
    }

    private function guardarManual(UploadedFile $archivo, ?string $rutaAnterior = null): string
    {
        if ($rutaAnterior) {
            $rutaFisicaAnterior = public_path($rutaAnterior);
            if (is_file($rutaFisicaAnterior)) {
                @unlink($rutaFisicaAnterior);
            }
        }

        $extension = strtolower($archivo->getClientOriginalExtension());
        $nombreBase = Str::slug(pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME), '_') ?: 'manual';
        $nombreArchivo = time() . '_' . substr(md5(uniqid('', true)), 0, 8) . '_' . $nombreBase . '.' . $extension;
        $directorio = public_path('maquinas_manuales');

        if (!is_dir($directorio)) {
            mkdir($directorio, 0775, true);
        }

        $archivo->move($directorio, $nombreArchivo);

        return 'maquinas_manuales/' . $nombreArchivo;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, IncidenciaMaquina>
     */
    private function listadoIncidenciasMaquina(int $maquinaId)
    {
        return IncidenciaMaquina::where('maquina_id', $maquinaId)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get(['id', 'titulo', 'fecha', 'estatus']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validarMantenimiento(Request $request, int $maquinaId): array
    {
        $esCorrectivo = $request->input('tipo') === 'CORRECTIVO';

        return $request->validate([
            'tipo' => ['required', 'in:PREVENTIVO,CORRECTIVO,PREDICTIVO'],
            'fecha_programada' => ['required', 'date'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'responsable_id' => ['nullable', 'integer', 'exists:tblempleados,id'],
            'incidencia_id' => [
                'nullable',
                'integer',
                Rule::exists('tbl_incidencias_maquina', 'id')->where(fn ($query) => $query->where('maquina_id', $maquinaId)),
            ],
            'causa_falla_id' => [
                $esCorrectivo ? 'required' : 'nullable',
                'integer',
                Rule::exists('tbl_causas_falla', 'id')->where(fn ($query) => $query->where('estatus', 'A')),
            ],
            'fecha_reporte' => ['required', 'date'],
            'descripcion' => ['required', 'string'],
            'horas_paro' => ['nullable', 'numeric', 'min:0'],
            'producto_refaccion_id' => [
                'nullable',
                'integer',
                'exists:tblproductos,id',
            ],
            'cantidad_refaccion' => ['nullable', 'required_with:producto_refaccion_id', 'numeric', 'min:0.001'],
            'costo_mano_obra' => ['nullable', 'numeric', 'min:0'],
            'costo_refacciones' => ['nullable', 'numeric', 'min:0'],
            'resultado' => ['nullable', 'string'],
            'estatus' => ['required', 'in:PENDIENTE,EN_PROCESO,FINALIZADO,CANCELADO'],
            'fotografias' => ['nullable', 'array'],
            'fotografias.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'descripcion_fotos' => ['nullable', 'string', 'max:500'],
        ], [
            'causa_falla_id.required' => 'Seleccione la causa de falla para el mantenimiento correctivo.',
            'cantidad_refaccion.required_with' => 'Indique la cantidad de refacción usada.',
        ]);
    }

    private function generarFolioMantenimiento(): string
    {
        $prefijo = 'MC-' . now()->format('Ymd') . '-';
        $ultimo = Mantenimiento::where('folio', 'like', $prefijo . '%')
            ->orderByDesc('id')
            ->value('folio');
        $n = 1;
        if ($ultimo && preg_match('/-(\d+)$/', $ultimo, $m)) {
            $n = ((int) $m[1]) + 1;
        }

        return $prefijo . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    private function descontarRefaccionUbicacionMaquina(
        Maquina $maquina,
        int $productoId,
        float $cantidad,
        string $folio
    ): void {
        if (!$maquina->id_almacen || !$maquina->id_ubicacion || $cantidad <= 0) {
            return;
        }

        $existencia = DB::table('tblexistencias')
            ->where('id_almacen', $maquina->id_almacen)
            ->where('id_ubicacion', $maquina->id_ubicacion)
            ->where('id_producto', $productoId)
            ->first();

        if (!$existencia) {
            throw ValidationException::withMessages([
                'producto_refaccion_id' => 'La refacción no tiene existencia en la ubicación de la máquina.',
            ]);
        }

        DB::table('tblexistencias')->where('id', $existencia->id)->update([
            'cantidad_existente' => round((float) $existencia->cantidad_existente - $cantidad, 3),
            'updated_at' => now(),
        ]);

        $tipoSalida = (int) (DB::table('tbltipos_movimientos_inventario')
            ->where('nombre_movimiento', 'like', '%Salida%')
            ->orderBy('id')
            ->value('id') ?: 0);

        if ($tipoSalida > 0 && auth()->id()) {
            DB::table('tblmovimientos_inventario')->insert([
                'id_producto' => $productoId,
                'id_almacen' => $maquina->id_almacen,
                'id_ubicacion' => $maquina->id_ubicacion,
                'id_tipo_movimiento' => $tipoSalida,
                'cantidad_producto_movimiento' => (int) max(1, (int) round($cantidad)),
                'fecha_movimiento' => now()->format('Y-m-d'),
                'documento_referencia' => Str::limit($folio, 200, ''),
                'observaciones' => 'Salida refacción por mantenimiento correctivo ' . $folio,
                'id_estado_movinv' => 2,
                'usuario_movimiento' => auth()->id(),
                'created_at' => now(),
            ]);
        }
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function calcularCostoTotal(array $validated): ?float
    {
        $manoObra = $validated['costo_mano_obra'] ?? null;
        $refacciones = $validated['costo_refacciones'] ?? null;

        if ($manoObra === null && $refacciones === null) {
            return null;
        }

        return round((float) ($manoObra ?? 0) + (float) ($refacciones ?? 0), 2);
    }

    /**
     * @return list<string>
     */
    private function actividadesPreventivas(): array
    {
        return [
            'Lubricación semanal',
            'Revisión eléctrica mensual',
            'Cambio de bandas',
            'Cambio de rodamientos',
            'Calibración',
            'Limpieza profunda',
        ];
    }

    private function calcularProximaEjecucion(Carbon $fechaBase, string $frecuencia): Carbon
    {
        return match ($frecuencia) {
            'SEMANAL' => $fechaBase->copy()->addWeek(),
            'QUINCENAL' => $fechaBase->copy()->addWeeks(2),
            'MENSUAL' => $fechaBase->copy()->addMonth(),
            'TRIMESTRAL' => $fechaBase->copy()->addMonths(3),
            'SEMESTRAL' => $fechaBase->copy()->addMonths(6),
            'ANUAL' => $fechaBase->copy()->addYear(),
            default => $fechaBase->copy()->addMonth(),
        };
    }

    private function guardarFotosMantenimiento(
        Request $request,
        Mantenimiento $mantenimiento,
        ?string $descripcion = null
    ): void {
        if (!$request->hasFile('fotografias')) {
            return;
        }

        $directorio = public_path('mantenimientos_fotos/' . $mantenimiento->maquina_id . '/' . $mantenimiento->id);

        if (!is_dir($directorio)) {
            mkdir($directorio, 0775, true);
        }

        foreach ($request->file('fotografias') as $archivo) {
            if (!$archivo instanceof UploadedFile || !$archivo->isValid()) {
                continue;
            }

            $nombreOriginal = $archivo->getClientOriginalName();
            $extension = strtolower($archivo->getClientOriginalExtension());
            $nombreBase = Str::slug(pathinfo($nombreOriginal, PATHINFO_FILENAME), '_') ?: 'foto';
            $nombreArchivo = time() . '_' . substr(md5(uniqid('', true)), 0, 8) . '_' . $nombreBase . '.' . $extension;

            $archivo->move($directorio, $nombreArchivo);

            MantenimientoFoto::create([
                'mantenimiento_id' => $mantenimiento->id,
                'nombre_archivo' => $nombreOriginal,
                'ruta' => 'mantenimientos_fotos/' . $mantenimiento->maquina_id . '/' . $mantenimiento->id . '/' . $nombreArchivo,
                'descripcion' => $descripcion,
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Conceptos activos del catálogo de lista de revisión.
     *
     * @return list<string>
     */
    private function conceptosInspeccion(): array
    {
        $desdeCatalogo = InspeccionParametro::activos()->pluck('nombre')->all();

        if (!empty($desdeCatalogo)) {
            return array_values($desdeCatalogo);
        }

        // Fallback si el catálogo aún no tiene filas activas.
        return [
            'Limpieza general',
            'Motores',
            'Bandas',
            'Tornillería',
            'Sistema eléctrico',
            'Sistema neumático',
            'Sistema hidráulico',
            'Sensores',
            'Protecciones de seguridad',
            'Fugas',
            'Vibraciones',
            'Ruidos anormales',
            'Lubricación',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function categoriasFotosInspeccion(): array
    {
        return [
            'foto_general' => 'Fotografía general',
            'foto_componentes' => 'Componentes revisados',
            'foto_danos' => 'Daños detectados',
            'foto_reparaciones' => 'Reparaciones realizadas',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validarInspeccion(Request $request): array
    {
        $reglasFotos = [];
        foreach (array_keys($this->categoriasFotosInspeccion()) as $campo) {
            $reglasFotos[$campo] = ['nullable', 'array'];
            $reglasFotos["{$campo}.*"] = ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'];
        }

        return $request->validate(array_merge([
            'fecha_inspeccion' => ['required', 'date'],
            'inspector_id' => ['required', 'integer', 'exists:tblempleados,id'],
            'supervisor_id' => ['nullable', 'integer', 'exists:tblempleados,id'],
            'resultado_general' => ['required', 'in:EXCELENTE,BUENO,REGULAR,MALO,CRITICO'],
            'observaciones_generales' => ['nullable', 'string'],
            'requiere_mantenimiento' => ['nullable', 'boolean'],
            'detalle' => ['required', 'array', 'min:1'],
            'detalle.*.concepto' => ['required', 'string', 'max:200'],
            'detalle.*.resultado' => ['required', 'in:OK,OBSERVACION,CRITICO'],
            'detalle.*.observaciones' => ['nullable', 'string', 'max:500'],
        ], $reglasFotos));
    }

    private function guardarFotosInspeccion(Request $request, InspeccionMaquina $inspeccion): void
    {
        $directorio = public_path('inspecciones_fotos/' . $inspeccion->maquina_id . '/' . $inspeccion->id);

        if (!is_dir($directorio)) {
            mkdir($directorio, 0775, true);
        }

        foreach ($this->categoriasFotosInspeccion() as $campo => $descripcion) {
            if (!$request->hasFile($campo)) {
                continue;
            }

            foreach ($request->file($campo) as $archivo) {
                if (!$archivo instanceof UploadedFile || !$archivo->isValid()) {
                    continue;
                }

                $nombreOriginal = $archivo->getClientOriginalName();
                $extension = strtolower($archivo->getClientOriginalExtension());
                $nombreBase = Str::slug(pathinfo($nombreOriginal, PATHINFO_FILENAME), '_') ?: 'foto';
                $nombreArchivo = time() . '_' . substr(md5(uniqid('', true)), 0, 8) . '_' . $nombreBase . '.' . $extension;

                $archivo->move($directorio, $nombreArchivo);

                InspeccionFoto::create([
                    'inspeccion_id' => $inspeccion->id,
                    'nombre_archivo' => $nombreOriginal,
                    'ruta' => 'inspecciones_fotos/' . $inspeccion->maquina_id . '/' . $inspeccion->id . '/' . $nombreArchivo,
                    'descripcion' => $descripcion,
                    'created_at' => now(),
                ]);
            }
        }
    }
}
