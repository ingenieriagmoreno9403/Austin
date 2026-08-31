<?php

namespace App\Http\Controllers;

use App\Models\Camion;
use App\Models\Carga;
use App\Models\Chofer;
use App\Models\Empleados;
use App\Models\OrdenProduccion;
use App\Models\ProcesoProduccionResponsable;
use App\Models\RutaCarga;
use App\Models\RutaCargaEvidencia;
use App\Models\VentaPedido;
use App\Traits\MenuTrait;
use App\Traits\SistemasTraits;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CargasController extends Controller
{
    use MenuTrait;
    use SistemasTraits;

    public function __construct()
    {
        $this->middleware('auth');
    }

    private function assertPuedeVerCargas(): void
    {
        $ok = $this->forpermisos('ver_cargas_almacen') === 'ver_cargas_almacen';
        if (!$ok) {
            abort(403, 'No tiene permiso para ver cargas de almacén.');
        }
    }

    public function rutas(Request $request): View
    {
        $this->assertPuedeVerCargas();

        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        $rutas = RutaCarga::with(['cargas.pedido.cliente', 'camion', 'chofer'])
            ->withCount('cargas')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $rutaId = (int) $request->input('ruta_id', 0);
        $rutaSeleccionada = null;
        if ($rutaId > 0) {
            $rutaSeleccionada = RutaCarga::with([
                'cargas' => fn ($q) => $q->orderBy('orden_entrega')->orderBy('id'),
                'cargas.pedido.cliente.ciudad',
                'cargas.pedido.detalles',
                'cargas.ordenProduccion.detalles.producto',
                'evidencias',
                'creadoPor',
                'camion',
                'chofer',
            ])->find($rutaId);
        }

        $cargasDisponibles = Carga::with([
                'pedido.cliente.ciudad',
                'ordenProduccion.detalles',
            ])
            ->whereNull('ruta_id')
            ->where('estatus', Carga::ESTATUS_PROGRAMADA)
            ->orderByDesc('id')
            ->get();

        // OP terminadas / pedidos listos aún sin carga avisada: para acomodar en camión.
        $pedidosListos = $this->obtenerPedidosListosParaCarga();
        // OP en empaque (aún no TERMINADA): visibles para anticipar, no asignables.
        $pedidosEnEmpaque = $this->obtenerPedidosEnEmpaqueHaciaAlmacen();

        $camiones = Camion::activos()->orderBy('placas')->get();
        $choferes = Chofer::activos()->with('empleado')->orderBy('nombre')->get();
        $empleados = Empleados::query()
            ->where('estado', 'A')
            ->orderBy('apellido_paterno')
            ->orderBy('primer_nombre')
            ->get(['id', 'primer_nombre', 'segundo_nombre', 'apellido_paterno', 'apellido_materno', 'telefono']);

        $responsableAlmacen = 'AUXILIAR DE ALMACEN';
        $responsableEvidencia = 'SUPERVISOR DE ALMACEN';
        try {
            $mapa = ProcesoProduccionResponsable::mapaResponsables();
            $responsableAlmacen = $mapa['EN_ESPERA_MATERIALES'] ?? $responsableAlmacen;
        } catch (\Throwable $e) {
            // catálogo opcional
        }

        return view('Ventas.rutas_carga', compact(
            'varpantallas',
            'varsubmenus',
            'rutas',
            'rutaSeleccionada',
            'cargasDisponibles',
            'pedidosListos',
            'pedidosEnEmpaque',
            'camiones',
            'choferes',
            'empleados',
            'responsableAlmacen',
            'responsableEvidencia'
        ));
    }

    private function redirectRuta(int $rutaId, string $mensaje, string $tipo = 'success'): RedirectResponse
    {
        return redirect()
            ->route('almacen.cargas', ['ruta_id' => $rutaId])
            ->with($tipo, $mensaje);
    }

    public function crearRuta(Request $request): RedirectResponse
    {
        $this->assertPuedeVerCargas();

        $validated = $request->validate([
            'fecha' => ['required', 'date'],
            'camion_id' => ['nullable', 'integer', 'exists:tbl_camiones,id'],
            'chofer_id' => ['nullable', 'integer', 'exists:tbl_choferes,id'],
            'unidad' => ['nullable', 'string', 'max:80'],
            'chofer_nombre' => ['nullable', 'string', 'max:120'],
            'chofer_tipo' => ['nullable', Rule::in([RutaCarga::CHOFER_INTERNO, RutaCarga::CHOFER_EXTERNO])],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'carga_ids' => ['nullable', 'array'],
            'carga_ids.*' => ['integer', 'exists:tbl_cargas,id'],
            'pedido_ids' => ['nullable', 'array'],
            'pedido_ids.*' => ['integer', 'exists:tbl_pedidos,id'],
        ]);

        $cargaIds = array_values(array_unique(array_map('intval', $validated['carga_ids'] ?? [])));
        $pedidoIds = array_values(array_unique(array_map('intval', $validated['pedido_ids'] ?? [])));

        if ($cargaIds === [] && $pedidoIds === []) {
            throw ValidationException::withMessages([
                'pedido_ids' => 'Debe seleccionar al menos una orden de producción (OP terminada) o una carga avisada para crear la ruta.',
            ]);
        }

        $ruta = DB::transaction(function () use ($validated, $cargaIds, $pedidoIds) {
            $camion = !empty($validated['camion_id'])
                ? Camion::find((int) $validated['camion_id'])
                : null;
            $chofer = !empty($validated['chofer_id'])
                ? Chofer::find((int) $validated['chofer_id'])
                : null;

            $unidad = $validated['unidad'] ?? null;
            if ($camion) {
                $unidad = $camion->placas . ($camion->nombre ? ' — ' . $camion->nombre : '');
            }

            $choferNombre = $validated['chofer_nombre'] ?? null;
            $choferTipo = $validated['chofer_tipo'] ?? null;
            if ($chofer) {
                $choferNombre = $chofer->nombre;
                $choferTipo = $chofer->tipo;
            }

            if (!empty($pedidoIds)) {
                $creadas = $this->crearCargasDesdePedidosListos($pedidoIds);
                $cargaIds = array_values(array_unique(array_merge($cargaIds, $creadas)));
            }

            if ($cargaIds === []) {
                throw ValidationException::withMessages([
                    'pedido_ids' => 'No se pudo asignar ninguna orden de producción a la ruta. Verifique que las OP estén terminadas y listos para carga.',
                ]);
            }

            $ruta = RutaCarga::create([
                'folio' => $this->generarFolioRuta(),
                'fecha' => $validated['fecha'],
                'estatus' => RutaCarga::ESTATUS_ARMADA,
                'camion_id' => $camion?->id,
                'unidad' => $unidad,
                'chofer_id' => $chofer?->id,
                'chofer_nombre' => $choferNombre,
                'chofer_tipo' => $choferTipo,
                'observaciones' => $validated['observaciones'] ?? null,
                'creado_por' => auth()->id(),
            ]);

            $this->asignarCargasARuta($ruta, $cargaIds);

            $ruta->recalcularOrdenes(false);

            return $ruta;
        });

        $ruta->load(['cargas.ordenProduccion.detalles', 'cargas.pedido.detalles', 'camion']);
        $cap = $ruta->resumenCapacidad();
        $mensaje = 'Ruta ' . $ruta->folio . ' creada. Acomode el orden: entrega cerca→lejos; carga lejos→cerca (fondo del camión).';
        $tipoFlash = 'success';
        if ($cap['cabe'] === false) {
            $mensaje .= ' Atención: ' . $cap['mensaje'];
            $tipoFlash = 'warning';
        } elseif ($cap['cabe'] === true) {
            $mensaje .= ' ' . $cap['mensaje'];
        }

        return redirect()
            ->route('almacen.cargas', ['ruta_id' => $ruta->id])
            ->with($tipoFlash, $mensaje);
    }

    public function storeCamion(Request $request): JsonResponse|RedirectResponse
    {
        $this->assertPuedeVerCargas();

        $validated = $request->validate([
            'placas' => ['required', 'string', 'min:3', 'max:40', 'unique:tbl_camiones,placas'],
            'nombre' => ['nullable', 'string', 'max:120'],
            'tipo' => ['nullable', 'string', 'max:60'],
            'capacidad_ton' => ['nullable', 'numeric', 'min:0'],
            'capacidad_metros' => ['nullable', 'numeric', 'min:0'],
            'perm_sct' => ['nullable', 'string', 'max:20'],
            'num_permiso_sct' => ['nullable', 'string', 'max:80'],
            'config_vehicular' => ['nullable', 'string', 'max:20'],
            'anio_modelo' => ['nullable', 'string', 'max:4'],
            'asegura_resp_civil' => ['nullable', 'string', 'max:120'],
            'poliza_resp_civil' => ['nullable', 'string', 'max:80'],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ]);

        $camion = Camion::create([
            'placas' => strtoupper(trim($validated['placas'])),
            'nombre' => $validated['nombre'] ?? null,
            'tipo' => $validated['tipo'] ?? null,
            'capacidad_ton' => $validated['capacidad_ton'] ?? null,
            'capacidad_metros' => $validated['capacidad_metros'] ?? null,
            'perm_sct' => $validated['perm_sct'] ?? null,
            'num_permiso_sct' => $validated['num_permiso_sct'] ?? null,
            'config_vehicular' => $validated['config_vehicular'] ?? null,
            'anio_modelo' => $validated['anio_modelo'] ?? null,
            'asegura_resp_civil' => $validated['asegura_resp_civil'] ?? null,
            'poliza_resp_civil' => $validated['poliza_resp_civil'] ?? null,
            'observaciones' => $validated['observaciones'] ?? null,
            'activo' => true,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'camion' => [
                    'id' => $camion->id,
                    'placas' => $camion->placas,
                    'nombre' => $camion->nombre,
                    'tipo' => $camion->tipo,
                    'capacidad_ton' => $camion->capacidad_ton,
                    'capacidad_metros' => $camion->capacidad_metros,
                    'etiqueta' => $camion->etiqueta,
                    'etiqueta_capacidad' => $camion->etiqueta_con_capacidad,
                ],
            ]);
        }

        return redirect()
            ->route('almacen.cargas')
            ->with('success', 'Camión ' . $camion->etiqueta . ' registrado.');
    }

    public function storeChofer(Request $request): JsonResponse|RedirectResponse
    {
        $this->assertPuedeVerCargas();

        $validated = $request->validate([
            'tipo' => ['required', Rule::in([Chofer::TIPO_INTERNO, Chofer::TIPO_EXTERNO])],
            'empleado_id' => ['nullable', 'integer', 'exists:tblempleados,id'],
            'nombre' => ['nullable', 'string', 'min:2', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:40'],
            'licencia' => ['nullable', 'string', 'max:60'],
            'rfc' => ['nullable', 'string', 'max:13'],
            'empresa_externa' => ['nullable', 'string', 'max:120'],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ]);

        $tipo = $validated['tipo'];
        $empleadoId = null;
        $nombre = trim((string) ($validated['nombre'] ?? ''));
        $telefono = $validated['telefono'] ?? null;

        if ($tipo === Chofer::TIPO_INTERNO) {
            $empleadoId = (int) ($validated['empleado_id'] ?? 0);
            if ($empleadoId <= 0) {
                throw ValidationException::withMessages([
                    'empleado_id' => 'Seleccione el empleado dado de alta que fungirá como chofer interno.',
                ]);
            }

            $ya = Chofer::where('empleado_id', $empleadoId)->where('activo', true)->first();
            if ($ya) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'ok' => true,
                        'chofer' => [
                            'id' => $ya->id,
                            'nombre' => $ya->nombre,
                            'tipo' => $ya->tipo,
                            'etiqueta' => $ya->etiqueta,
                        ],
                        'message' => 'El empleado ya estaba registrado como chofer.',
                    ]);
                }

                return redirect()
                    ->route('almacen.cargas')
                    ->with('warning', 'El empleado ya estaba registrado como chofer: ' . $ya->etiqueta);
            }

            $empleado = Empleados::findOrFail($empleadoId);
            $nombre = $this->nombreEmpleado($empleado);
            $telefono = $telefono ?: ($empleado->telefono ?: null);
        } else {
            if ($nombre === '') {
                throw ValidationException::withMessages([
                    'nombre' => 'Capture el nombre del chofer externo.',
                ]);
            }
            if (empty($validated['empresa_externa'])) {
                throw ValidationException::withMessages([
                    'empresa_externa' => 'Indique la empresa del chofer externo.',
                ]);
            }
        }

        $chofer = Chofer::create([
            'nombre' => $nombre,
            'tipo' => $tipo,
            'telefono' => $telefono,
            'licencia' => $validated['licencia'] ?? null,
            'rfc' => isset($validated['rfc']) ? strtoupper(trim($validated['rfc'])) : null,
            'empresa_externa' => $tipo === Chofer::TIPO_EXTERNO ? ($validated['empresa_externa'] ?? null) : null,
            'empleado_id' => $empleadoId,
            'observaciones' => $validated['observaciones'] ?? null,
            'activo' => true,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'chofer' => [
                    'id' => $chofer->id,
                    'nombre' => $chofer->nombre,
                    'tipo' => $chofer->tipo,
                    'etiqueta' => $chofer->etiqueta,
                ],
            ]);
        }

        return redirect()
            ->route('almacen.cargas')
            ->with('success', 'Chofer ' . $chofer->etiqueta . ' registrado.');
    }

    private function nombreEmpleado(Empleados $empleado): string
    {
        return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
            $empleado->primer_nombre ?? null,
            $empleado->segundo_nombre ?? null,
            $empleado->apellido_paterno ?? null,
            $empleado->apellido_materno ?? null,
        ]))));
    }

    public function agregarCargas(Request $request, int $id): RedirectResponse
    {
        $this->assertPuedeVerCargas();

        $validated = $request->validate([
            'carga_ids' => ['nullable', 'array'],
            'carga_ids.*' => ['integer', 'exists:tbl_cargas,id'],
            'pedido_ids' => ['nullable', 'array'],
            'pedido_ids.*' => ['integer', 'exists:tbl_pedidos,id'],
        ]);

        $cargaIds = $validated['carga_ids'] ?? [];
        $pedidoIds = $validated['pedido_ids'] ?? [];

        if (empty($cargaIds) && empty($pedidoIds)) {
            throw ValidationException::withMessages([
                'carga_ids' => 'Seleccione al menos una carga avisada o una orden terminada.',
            ]);
        }

        $ruta = RutaCarga::findOrFail($id);
        if ($ruta->estaCerrada()) {
            throw ValidationException::withMessages([
                'carga_ids' => 'La ruta ya está cerrada.',
            ]);
        }

        DB::transaction(function () use ($ruta, &$cargaIds, $pedidoIds) {
            if (!empty($pedidoIds)) {
                $creadas = $this->crearCargasDesdePedidosListos($pedidoIds);
                $cargaIds = array_values(array_unique(array_merge($cargaIds, $creadas)));
            }
            $this->asignarCargasARuta($ruta, $cargaIds);
        });

        $ruta->load(['cargas.ordenProduccion.detalles', 'cargas.pedido.detalles', 'camion']);
        $cap = $ruta->resumenCapacidad();
        $mensaje = 'Órdenes agregadas a la ruta ' . $ruta->folio . '. Ajuste el orden de carga si hace falta.';
        $tipo = 'success';
        if ($cap['cabe'] === false) {
            $mensaje .= ' Atención: ' . $cap['mensaje'];
            $tipo = 'warning';
        } elseif ($cap['cabe'] === true) {
            $mensaje .= ' ' . $cap['mensaje'];
        }

        return redirect()
            ->route('almacen.cargas', ['ruta_id' => $ruta->id])
            ->with($tipo, $mensaje);
    }

    public function actualizarOrdenes(Request $request, int $id): RedirectResponse
    {
        $this->assertPuedeVerCargas();

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.carga_id' => ['required', 'integer', 'exists:tbl_cargas,id'],
            'items.*.orden_entrega' => ['nullable', 'integer', 'min:1', 'max:999'],
            'items.*.distancia_km' => ['nullable', 'numeric', 'min:0'],
            'ordenar_por_distancia' => ['nullable', 'boolean'],
        ]);

        $ruta = RutaCarga::findOrFail($id);
        if ($ruta->estaCerrada()) {
            throw ValidationException::withMessages([
                'items' => 'La ruta ya está cerrada.',
            ]);
        }

        DB::transaction(function () use ($ruta, $validated) {
            foreach ($validated['items'] as $item) {
                $carga = Carga::where('id', $item['carga_id'])
                    ->where('ruta_id', $ruta->id)
                    ->first();
                if (!$carga) {
                    continue;
                }
                $carga->orden_entrega = $item['orden_entrega'] ?? $carga->orden_entrega;
                $carga->distancia_km = array_key_exists('distancia_km', $item)
                    ? $item['distancia_km']
                    : $carga->distancia_km;
                $carga->save();
            }

            $ruta->recalcularOrdenes((bool) ($validated['ordenar_por_distancia'] ?? false));
        });

        $msg = !empty($validated['ordenar_por_distancia'])
            ? 'Ruta ordenada por distancia (más cerca se entrega primero; más lejos se carga primero).'
            : 'Órdenes de entrega/carga actualizadas.';

        return redirect()
            ->route('almacen.cargas', ['ruta_id' => $ruta->id])
            ->with('success', $msg);
    }

    /**
     * Reordena por arrastre: el array carga_ids define el orden de entrega (1 = primero).
     * orden_carga se recalcula al inverso (lo más lejos / última parada va al fondo).
     */
    public function reordenarOrdenes(Request $request, int $id): JsonResponse
    {
        $this->assertPuedeVerCargas();

        $validated = $request->validate([
            'carga_ids' => ['required', 'array', 'min:1'],
            'carga_ids.*' => ['integer', 'exists:tbl_cargas,id'],
        ]);

        $ruta = RutaCarga::findOrFail($id);
        if ($ruta->estaCerrada()) {
            return response()->json(['ok' => false, 'message' => 'La ruta ya está cerrada.'], 422);
        }

        $resultado = DB::transaction(function () use ($ruta, $validated) {
            foreach ($validated['carga_ids'] as $i => $cargaId) {
                $carga = Carga::where('id', $cargaId)
                    ->where('ruta_id', $ruta->id)
                    ->first();
                if (!$carga) {
                    continue;
                }
                $carga->orden_entrega = $i + 1;
                $carga->save();
            }

            $ruta->recalcularOrdenes(false);

            return $ruta->cargas()
                ->with(['pedido.cliente', 'ordenProduccion.detalles'])
                ->orderBy('orden_entrega')
                ->get()
                ->map(function (Carga $c) {
                    $op = $c->ordenProduccion;
                    return [
                        'id' => $c->id,
                        'folio' => $c->folio,
                        'pedido_folio' => $c->pedido->folio ?? ('#' . $c->pedido_id),
                        'cliente' => $c->pedido->cliente->nombre ?? '—',
                        'destino' => $c->destino_texto,
                        'distancia_km' => $c->distancia_km,
                        'orden_entrega' => $c->orden_entrega,
                        'orden_carga' => $c->orden_carga,
                        'op_folio' => $op?->folio,
                        'metros' => $op ? round((float) $op->total_metros_producidos, 1) : null,
                        'piezas' => $op ? (int) $op->total_piezas_producidas : null,
                    ];
                });
        });

        return response()->json(['ok' => true, 'cargas' => $resultado]);
    }

    public function pdfSecuencia(int $id): Response
    {
        $this->assertPuedeVerCargas();

        $ruta = RutaCarga::with([
            'cargas.pedido.cliente.ciudad',
            'cargas.pedido.detalles',
            'cargas.ordenProduccion.detalles',
            'camion',
            'creadoPor',
        ])->findOrFail($id);

        if ($ruta->cargas->isEmpty()) {
            abort(422, 'La ruta no tiene órdenes para imprimir.');
        }

        $porCarga = $ruta->cargas->sortBy('orden_carga')->values();
        $porEntrega = $ruta->cargas->sortBy('orden_entrega')->values();

        $pdf = Pdf::loadView('Ventas.pdf.secuencia_carga', [
            'ruta' => $ruta,
            'porCarga' => $porCarga,
            'porEntrega' => $porEntrega,
        ])->setPaper('letter', 'portrait');

        $nombre = 'secuencia-carga-' . preg_replace('/[^A-Za-z0-9\-]/', '', $ruta->folio) . '.pdf';

        return $pdf->stream($nombre);
    }

    public function quitarCarga(Request $request, int $id, int $cargaId): RedirectResponse
    {
        $this->assertPuedeVerCargas();

        $ruta = RutaCarga::findOrFail($id);
        if ($ruta->estaCerrada() || !in_array($ruta->estatus, [RutaCarga::ESTATUS_ARMADA, RutaCarga::ESTATUS_MATERIAL_UBICADO], true)) {
            throw ValidationException::withMessages([
                'carga' => 'Solo se pueden quitar cargas al inicio de la operación.',
            ]);
        }

        $carga = Carga::where('id', $cargaId)->where('ruta_id', $ruta->id)->firstOrFail();

        DB::transaction(function () use ($ruta, $carga) {
            $carga->update([
                'ruta_id' => null,
                'orden_entrega' => null,
                'orden_carga' => null,
                'estatus' => Carga::ESTATUS_PROGRAMADA,
            ]);
            $ruta->recalcularOrdenes(false);
        });

        return redirect()
            ->route('almacen.cargas', ['ruta_id' => $ruta->id])
            ->with('success', 'Carga ' . $carga->folio . ' quitada de la ruta.');
    }

    public function ubicarMaterial(int $id): RedirectResponse
    {
        $this->assertPuedeVerCargas();

        $ruta = RutaCarga::withCount('cargas')->findOrFail($id);
        $this->assertRutaEditable($ruta);
        if ($ruta->cargas_count < 1) {
            throw ValidationException::withMessages(['ruta' => 'Agregue al menos una carga antes de continuar.']);
        }
        if ($ruta->estatus !== RutaCarga::ESTATUS_ARMADA) {
            throw ValidationException::withMessages(['ruta' => 'El material ya fue marcado como ubicado.']);
        }

        $ruta->update(['estatus' => RutaCarga::ESTATUS_MATERIAL_UBICADO]);

        return redirect()
            ->route('almacen.cargas', ['ruta_id' => $ruta->id])
            ->with('success', 'Material ubicado. Siguiente: llegada del chofer y carga.');
    }

    public function iniciarCarga(Request $request, int $id): RedirectResponse
    {
        $this->assertPuedeVerCargas();

        $validated = $request->validate([
            'camion_id' => ['nullable', 'integer', 'exists:tbl_camiones,id'],
            'chofer_id' => ['nullable', 'integer', 'exists:tbl_choferes,id'],
            'chofer_nombre' => ['nullable', 'string', 'max:120'],
            'chofer_tipo' => ['nullable', Rule::in([RutaCarga::CHOFER_INTERNO, RutaCarga::CHOFER_EXTERNO])],
            'unidad' => ['nullable', 'string', 'max:80'],
        ]);

        $ruta = RutaCarga::findOrFail($id);
        $this->assertRutaEditable($ruta);
        if (!in_array($ruta->estatus, [RutaCarga::ESTATUS_ARMADA, RutaCarga::ESTATUS_MATERIAL_UBICADO], true)) {
            throw ValidationException::withMessages(['ruta' => 'La ruta no está en etapa de llegada de chofer.']);
        }

        $camion = !empty($validated['camion_id'])
            ? Camion::find((int) $validated['camion_id'])
            : ($ruta->camion_id ? Camion::find($ruta->camion_id) : null);
        $chofer = !empty($validated['chofer_id'])
            ? Chofer::find((int) $validated['chofer_id'])
            : ($ruta->chofer_id ? Chofer::find($ruta->chofer_id) : null);

        $unidad = $validated['unidad'] ?? $ruta->unidad;
        if ($camion) {
            $unidad = $camion->placas . ($camion->nombre ? ' — ' . $camion->nombre : '');
        }

        $choferNombre = $validated['chofer_nombre'] ?? $ruta->chofer_nombre;
        $choferTipo = $validated['chofer_tipo'] ?? $ruta->chofer_tipo;
        if ($chofer) {
            $choferNombre = $chofer->nombre;
            $choferTipo = $chofer->tipo;
        }

        if (empty($choferNombre) || empty($choferTipo)) {
            throw ValidationException::withMessages([
                'chofer_id' => 'Seleccione o capture el chofer (interno o externo).',
            ]);
        }

        $ruta->update([
            'camion_id' => $camion?->id ?? $ruta->camion_id,
            'chofer_id' => $chofer?->id ?? $ruta->chofer_id,
            'chofer_nombre' => $choferNombre,
            'chofer_tipo' => $choferTipo,
            'unidad' => $unidad,
            'chofer_llegada_at' => now(),
            'estatus' => RutaCarga::ESTATUS_CARGANDO,
        ]);

        return redirect()
            ->route('almacen.cargas', ['ruta_id' => $ruta->id])
            ->with('success', 'Chofer registrado. Complete el checklist de seguridad (EPP / documentos).');
    }

    public function guardarChecklist(Request $request, int $id): RedirectResponse
    {
        $this->assertPuedeVerCargas();

        $ruta = RutaCarga::findOrFail($id);
        $this->assertRutaEditable($ruta);
        if ($ruta->estatus !== RutaCarga::ESTATUS_CARGANDO) {
            throw ValidationException::withMessages(['ruta' => 'Primero registre la llegada del chofer.']);
        }

        $rules = ['chofer_tipo' => ['required', Rule::in([RutaCarga::CHOFER_INTERNO, RutaCarga::CHOFER_EXTERNO])]];
        if ($request->input('chofer_tipo') === RutaCarga::CHOFER_EXTERNO) {
            $rules += [
                'check_seguro_vehiculo' => ['accepted'],
                'check_seguro_imss' => ['accepted'],
                'check_botas' => ['accepted'],
                'check_casco' => ['accepted'],
                'check_chaleco' => ['accepted'],
            ];
        } else {
            $rules['check_epp'] = ['accepted'];
        }

        $validated = $request->validate($rules, [
            'accepted' => 'Debe marcar :attribute para continuar.',
        ]);

        $ruta->update([
            'chofer_tipo' => $validated['chofer_tipo'],
            'check_seguro_vehiculo' => $request->boolean('check_seguro_vehiculo'),
            'check_seguro_imss' => $request->boolean('check_seguro_imss'),
            'check_botas' => $request->boolean('check_botas'),
            'check_casco' => $request->boolean('check_casco'),
            'check_chaleco' => $request->boolean('check_chaleco'),
            'check_epp' => $request->boolean('check_epp'),
            'checklist_ok_at' => now(),
            'checklist_por' => auth()->id(),
            'estatus' => RutaCarga::ESTATUS_CHECKLIST,
        ]);

        return redirect()
            ->route('almacen.cargas', ['ruta_id' => $ruta->id])
            ->with('success', 'Checklist OK. Siguiente: subir evidencia fotográfica de la carga.');
    }

    public function subirEvidencia(Request $request, int $id): RedirectResponse
    {
        $this->assertPuedeVerCargas();

        $ruta = RutaCarga::with('evidencias')->findOrFail($id);
        $this->assertRutaEditable($ruta);
        if (!in_array($ruta->estatus, [RutaCarga::ESTATUS_CHECKLIST, RutaCarga::ESTATUS_EVIDENCIA], true)) {
            throw ValidationException::withMessages(['ruta' => 'Complete el checklist antes de evidenciar.']);
        }

        $validated = $request->validate([
            'evidencias' => ['required', 'array', 'min:1'],
            'evidencias.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        foreach ($validated['evidencias'] as $archivo) {
            $path = $archivo->store('cargas/evidencias/' . $ruta->id, 'public');
            RutaCargaEvidencia::create([
                'ruta_id' => $ruta->id,
                'archivo_path' => $path,
                'archivo_nombre' => $archivo->getClientOriginalName(),
                'subido_por' => auth()->id(),
            ]);
        }

        $ruta->update([
            'estatus' => RutaCarga::ESTATUS_EVIDENCIA,
            'evidencia_ok_at' => now(),
            'evidencia_por' => auth()->id(),
        ]);

        return redirect()
            ->route('almacen.cargas', ['ruta_id' => $ruta->id])
            ->with('success', 'Evidencia guardada. Siguiente: firma de remisiones por el chofer.');
    }

    public function firmarRemision(Request $request, int $id, int $cargaId): RedirectResponse
    {
        $this->assertPuedeVerCargas();

        $ruta = RutaCarga::with('cargas')->findOrFail($id);
        $this->assertRutaEditable($ruta);
        if (!in_array($ruta->estatus, [RutaCarga::ESTATUS_EVIDENCIA, RutaCarga::ESTATUS_REMISION], true)) {
            throw ValidationException::withMessages(['ruta' => 'Primero registre la evidencia de carga.']);
        }

        $carga = Carga::where('id', $cargaId)->where('ruta_id', $ruta->id)->firstOrFail();

        $request->validate([
            'remision_archivo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $archivoPath = $carga->remision_archivo;
        if ($request->hasFile('remision_archivo')) {
            $archivoPath = $request->file('remision_archivo')->store('cargas/remisiones/' . $ruta->id, 'public');
        }

        $carga->update([
            'remision_firmada' => true,
            'remision_archivo' => $archivoPath,
            'remision_firmada_at' => now(),
            'remision_firmada_por' => auth()->id(),
        ]);

        $ruta->refresh()->load('cargas');
        $ruta->update([
            'estatus' => RutaCarga::ESTATUS_REMISION,
            'remision_ok_at' => $ruta->remisionesCompletas() ? now() : $ruta->remision_ok_at,
        ]);

        return redirect()
            ->route('almacen.cargas', ['ruta_id' => $ruta->id])
            ->with('success', 'Remisión firmada para ' . $carga->folio . '.');
    }

    public function darSalida(int $id): RedirectResponse
    {
        $this->assertPuedeVerCargas();

        $ruta = RutaCarga::with('cargas')->findOrFail($id);
        $this->assertRutaEditable($ruta);

        if ($ruta->estatus !== RutaCarga::ESTATUS_REMISION || !$ruta->remisionesCompletas()) {
            throw ValidationException::withMessages([
                'ruta' => 'Deben estar firmadas todas las remisiones antes de dar salida.',
            ]);
        }
        if ($ruta->evidencias()->count() < 1) {
            throw ValidationException::withMessages([
                'ruta' => 'Debe existir al menos una evidencia fotográfica.',
            ]);
        }

        DB::transaction(function () use ($ruta) {
            $ruta->update([
                'estatus' => RutaCarga::ESTATUS_CERRADA,
                'salida_at' => now(),
                'salida_por' => auth()->id(),
            ]);

            foreach ($ruta->cargas as $carga) {
                $carga->update(['estatus' => Carga::ESTATUS_DESPACHADA]);
                if ($carga->pedido_id) {
                    VentaPedido::where('id', $carga->pedido_id)
                        ->whereIn('estatus', ['CARGA_AVISADA', 'LISTO_PARA_CARGA'])
                        ->update(['estatus' => 'DESPACHADO', 'updated_at' => now()]);
                }
            }
        });

        return redirect()
            ->route('almacen.cargas', ['ruta_id' => $ruta->id])
            ->with('success', 'Salida registrada. Ruta ' . $ruta->folio . ' cerrada y pedidos despachados.');
    }

    private function assertRutaEditable(RutaCarga $ruta): void
    {
        if ($ruta->estaCerrada()) {
            throw ValidationException::withMessages([
                'ruta' => 'La ruta ya tiene salida y está cerrada.',
            ]);
        }
    }

    private function asignarCargasARuta(RutaCarga $ruta, array $cargaIds): void
    {
        $cargas = Carga::whereIn('id', $cargaIds)
            ->whereNull('ruta_id')
            ->get();

        if ($cargas->isEmpty()) {
            throw ValidationException::withMessages([
                'carga_ids' => 'Seleccione cargas avisadas que aún no estén en otra ruta.',
            ]);
        }

        $siguiente = ((int) $ruta->cargas()->max('orden_entrega')) + 1;

        foreach ($cargas as $carga) {
            $carga->loadMissing('pedido.cliente.ciudad');
            $destino = Carga::destinoDesdeCliente($carga->pedido?->cliente);
            if ($destino) {
                $carga->destino_texto = $destino;
            }
            $carga->ruta_id = $ruta->id;
            $carga->estatus = Carga::ESTATUS_EN_RUTA;
            $carga->orden_entrega = $siguiente++;
            $carga->save();
        }

        $ruta->recalcularOrdenes(false);
    }

    private function generarFolioRuta(): string
    {
        $prefijo = 'RT-' . now()->format('Ymd') . '-';
        $ultimo = RutaCarga::where('folio', 'like', $prefijo . '%')
            ->orderByDesc('id')
            ->value('folio');

        $consecutivo = 1;
        if ($ultimo && preg_match('/-(\d+)$/', $ultimo, $m)) {
            $consecutivo = ((int) $m[1]) + 1;
        }

        return $prefijo . str_pad((string) $consecutivo, 4, '0', STR_PAD_LEFT);
    }

    private function generarFolioCarga(): string
    {
        $prefijo = 'CG-' . now()->format('Ymd') . '-';
        $ultimo = Carga::where('folio', 'like', $prefijo . '%')
            ->orderByDesc('id')
            ->value('folio');

        $consecutivo = 1;
        if ($ultimo && preg_match('/-(\d+)$/', $ultimo, $m)) {
            $consecutivo = ((int) $m[1]) + 1;
        }

        return $prefijo . str_pad((string) $consecutivo, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Pedidos listos para planificar carga (OP terminada o LISTO_PARA_CARGA) sin carga avisada.
     *
     * @return \Illuminate\Support\Collection<int, VentaPedido>
     */
    private function obtenerPedidosListosParaCarga()
    {
        return VentaPedido::with([
                'cliente.ciudad',
                'detalles.producto',
                'ordenProduccion.detalles.producto',
            ])
            ->whereDoesntHave('carga')
            ->where('estatus', '!=', 'DESPACHADO')
            ->where(function ($q) {
                $q->where('estatus', 'LISTO_PARA_CARGA')
                    ->orWhereHas('ordenProduccion', function ($op) {
                        $op->where('estatus', OrdenProduccion::ESTATUS_TERMINADA);
                    });
            })
            ->orderBy('fecha_entrega')
            ->orderByDesc('id')
            ->limit(80)
            ->get();
    }

    /**
     * Pedidos cuya OP está en empaque (aún no TERMINADA): anticipación para Almacén.
     *
     * @return \Illuminate\Support\Collection<int, VentaPedido>
     */
    private function obtenerPedidosEnEmpaqueHaciaAlmacen()
    {
        $estatusEmpaque = OrdenProduccion::ESTATUS_EMPAQUE;

        return VentaPedido::with([
                'cliente.ciudad',
                'ordenProduccion.detalles.producto',
            ])
            ->whereDoesntHave('carga')
            ->whereHas('ordenProduccion', function ($op) use ($estatusEmpaque) {
                $op->whereIn('estatus', $estatusEmpaque);
            })
            ->orderByDesc('id')
            ->limit(40)
            ->get();
    }

    /**
     * Crea registros de carga (como “avisar”) desde pedidos listos y los deja PROGRAMADA.
     *
     * @param  array<int, int>  $pedidoIds
     * @return array<int, int> IDs de cargas creadas o ya existentes sin ruta
     */
    private function crearCargasDesdePedidosListos(array $pedidoIds): array
    {
        $ids = [];
        $pedidos = VentaPedido::with(['cliente.ciudad', 'ordenProduccion', 'carga'])
            ->whereIn('id', $pedidoIds)
            ->get();

        foreach ($pedidos as $pedido) {
            if ($pedido->carga) {
                if ($pedido->carga->ruta_id === null) {
                    $ids[] = (int) $pedido->carga->id;
                }
                continue;
            }

            $orden = $pedido->ordenProduccion;
            $opTerminada = $orden && $orden->estatus === OrdenProduccion::ESTATUS_TERMINADA;
            $listo = $pedido->estatus === 'LISTO_PARA_CARGA' || $opTerminada;

            if (!$listo) {
                continue;
            }

            if (!in_array($pedido->estatus, ['LISTO_PARA_CARGA', 'CARGA_AVISADA', 'DESPACHADO'], true)) {
                $pedido->update(['estatus' => 'LISTO_PARA_CARGA', 'updated_at' => now()]);
            }

            $carga = Carga::create([
                'folio' => $this->generarFolioCarga(),
                'pedido_id' => $pedido->id,
                'orden_produccion_id' => $orden?->id,
                'estatus' => Carga::ESTATUS_PROGRAMADA,
                'destino_texto' => Carga::destinoDesdeCliente($pedido->cliente),
                'fecha_programada' => now()->toDateString(),
                'avisado_por' => auth()->id(),
                'avisado_at' => now(),
                'observaciones' => 'Incluida desde planeación de cargas (Almacén).',
            ]);

            $pedido->update(['estatus' => 'CARGA_AVISADA', 'updated_at' => now()]);
            $ids[] = (int) $carga->id;
        }

        return $ids;
    }
}
