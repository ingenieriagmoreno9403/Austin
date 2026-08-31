<?php

namespace App\Http\Controllers;

use App\Models\Maquina;
use App\Models\Productos;
use App\Models\Reproceso;
use App\Traits\MenuTrait;
use App\Traits\SistemasTraits;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReprocesoController extends Controller
{
    use MenuTrait;
    use SistemasTraits;

    public function __construct()
    {
        $this->middleware('auth');
    }

    private function assertPuedeVer(): void
    {
        $ok = $this->forpermisos('ver_reproceso_produccion') === 'ver_reproceso_produccion'
            || $this->forpermisos('ver_produccion') === 'ver_produccion';
        if (!$ok) {
            abort(403, 'No tiene permiso para el módulo de Reproceso.');
        }
    }

    private function maquinasReproceso()
    {
        return Maquina::paraReproceso()
            ->orderBy('nombre')
            ->get(['id', 'codigo', 'nombre']);
    }

    /** Peletizadoras (por nombre/código); si no hay coincidencia, todas las de reproceso. */
    private function maquinasPeletizado()
    {
        $todas = $this->maquinasReproceso();
        $filtradas = $todas->filter(function ($m) {
            $txt = Str::upper(trim(($m->codigo ?? '') . ' ' . ($m->nombre ?? '')));

            return Str::contains($txt, ['PELET', 'PEL-']);
        })->values();

        return $filtradas->isNotEmpty() ? $filtradas : $todas;
    }

    /** IDs de máquinas ya tomadas (triturado o peletizado) por otro lote activo. */
    private function idsMaquinasOcupadas(?int $exceptoLoteId = null): array
    {
        $q = Reproceso::query()
            ->whereNotIn('estatus', [
                Reproceso::ESTATUS_STOCK_RESINA,
                Reproceso::ESTATUS_CANCELADA,
            ])
            ->where(function ($w) {
                $w->whereNotNull('maquina_id')
                    ->orWhereNotNull('maquina_peletizado_id');
            });

        if ($exceptoLoteId) {
            $q->where('id', '!=', $exceptoLoteId);
        }

        $ids = [];
        foreach ($q->get(['maquina_id', 'maquina_peletizado_id']) as $lote) {
            if (!empty($lote->maquina_id)) {
                $ids[] = (int) $lote->maquina_id;
            }
            if (!empty($lote->maquina_peletizado_id)) {
                $ids[] = (int) $lote->maquina_peletizado_id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function assertMaquinaDisponible(int $maquinaId, ?int $exceptoLoteId = null, string $campo = 'maquina_id'): void
    {
        $q = Reproceso::query()
            ->where(function ($w) use ($maquinaId) {
                $w->where('maquina_id', $maquinaId)
                    ->orWhere('maquina_peletizado_id', $maquinaId);
            })
            ->whereNotIn('estatus', [
                Reproceso::ESTATUS_STOCK_RESINA,
                Reproceso::ESTATUS_CANCELADA,
            ]);

        if ($exceptoLoteId) {
            $q->where('id', '!=', $exceptoLoteId);
        }

        $otro = $q->lockForUpdate()->first(['id', 'folio']);
        if ($otro) {
            throw ValidationException::withMessages([
                $campo => 'La máquina ya está en uso en el lote ' . ($otro->folio ?: '#' . $otro->id) . ' y no puede seleccionarse hasta que termine ese flujo.',
            ]);
        }
    }

    private function productosResina()
    {
        try {
            // Categoría 44 = RESINA en tblcategoria_producto
            $porCategoria = Productos::query()
                ->where('id_categoria', 44)
                ->orderBy('nombre')
                ->get(['id', 'sku', 'codigo_barras', 'nombre']);

            if ($porCategoria->isNotEmpty()) {
                return $porCategoria;
            }

            return Productos::query()
                ->where(function ($q) {
                    $q->whereRaw('UPPER(nombre) LIKE ?', ['RESINA%'])
                        ->orWhereRaw('UPPER(nombre) LIKE ?', ['% RESINA %']);
                })
                ->orderBy('nombre')
                ->limit(200)
                ->get(['id', 'sku', 'codigo_barras', 'nombre']);
        } catch (\Throwable $e) {
            return collect();
        }
    }

    private function assertLoteDesdeOpRechazada(Reproceso $lote): void
    {
        if (empty($lote->orden_id) || empty($lote->salida_id)) {
            throw ValidationException::withMessages([
                'orden_id' => 'Todo reproceso debe provenir de una salida rechazada en una orden de producción.',
            ]);
        }
    }

    public function index(Request $request): View
    {
        $this->assertPuedeVer();

        try {
            Reproceso::sincronizarRechazosPendientes();
        } catch (\Throwable $e) {
            // no bloquear
        }

        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        $filtro = $request->input('estatus', 'activos');
        $activos = array_values(array_diff(Reproceso::$flujo, [Reproceso::ESTATUS_STOCK_RESINA]));

        $query = Reproceso::with(['orden.pedido.cliente', 'ubicacion', 'salida', 'maquina'])
            ->whereNotNull('orden_id')
            ->whereNotNull('salida_id')
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if ($filtro === 'triturado') {
            $query->whereIn('estatus', [
                Reproceso::ESTATUS_TRITURADA,
                Reproceso::ESTATUS_SACA_PESADA,
                Reproceso::ESTATUS_IDENTIFICADA,
                Reproceso::ESTATUS_LISTO_PELETIZADO,
            ]);
        } elseif ($filtro === 'peletizado') {
            $query->whereIn('estatus', [
                Reproceso::ESTATUS_EN_PELETIZADO,
                Reproceso::ESTATUS_PELETIZADO,
                Reproceso::ESTATUS_SACA_RESINA_DESMONTADA,
                Reproceso::ESTATUS_RESINA_IDENTIFICADA,
                Reproceso::ESTATUS_SACA_RESINA_PESADA,
            ]);
        } elseif ($filtro === 'almacen') {
            $query->whereIn('estatus', [
                Reproceso::ESTATUS_RESINA_ALMACENADA,
                Reproceso::ESTATUS_PRODUCCION_REPORTADA,
                Reproceso::ESTATUS_KILOS_RESINA_SISTEMA,
                Reproceso::ESTATUS_STOCK_RESINA,
            ])->where('updated_at', '>=', now()->subDays(45));
        } elseif ($filtro === 'todas') {
            //
        } elseif (in_array($filtro, Reproceso::$flujo, true) || $filtro === Reproceso::ESTATUS_CANCELADA) {
            $query->where('estatus', $filtro);
        } else {
            $query->whereIn('estatus', $activos);
            $filtro = 'activos';
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $query->where(function ($w) use ($q) {
                $w->where('folio', 'like', '%' . $q . '%')
                    ->orWhere('etiqueta_triturado', 'like', '%' . $q . '%')
                    ->orWhere('etiqueta_resina', 'like', '%' . $q . '%')
                    ->orWhereHas('orden', function ($o) use ($q) {
                        $o->where('folio', 'like', '%' . $q . '%');
                    });
            });
        }

        $lotes = $query->limit(150)->get();
        $ubicacion = Reproceso::ubicacionRechazados();

        $conteos = [
            'activos' => Reproceso::whereIn('estatus', $activos)->count(),
            'disponible' => Reproceso::where('estatus', Reproceso::ESTATUS_DISPONIBLE)->count(),
            'triturado' => Reproceso::whereIn('estatus', [
                Reproceso::ESTATUS_TRITURADA,
                Reproceso::ESTATUS_SACA_PESADA,
                Reproceso::ESTATUS_IDENTIFICADA,
                Reproceso::ESTATUS_LISTO_PELETIZADO,
            ])->count(),
            'peletizado' => Reproceso::whereIn('estatus', [
                Reproceso::ESTATUS_EN_PELETIZADO,
                Reproceso::ESTATUS_PELETIZADO,
                Reproceso::ESTATUS_SACA_RESINA_DESMONTADA,
                Reproceso::ESTATUS_RESINA_IDENTIFICADA,
                Reproceso::ESTATUS_SACA_RESINA_PESADA,
            ])->count(),
            'almacen' => Reproceso::whereIn('estatus', [
                Reproceso::ESTATUS_RESINA_ALMACENADA,
                Reproceso::ESTATUS_PRODUCCION_REPORTADA,
                Reproceso::ESTATUS_KILOS_RESINA_SISTEMA,
                Reproceso::ESTATUS_STOCK_RESINA,
            ])->where('updated_at', '>=', now()->subDays(45))->count(),
        ];

        return view('Produccion.reproceso_index', compact(
            'varpantallas',
            'varsubmenus',
            'lotes',
            'filtro',
            'conteos',
            'ubicacion'
        ));
    }

    public function show(int $id): View
    {
        $this->assertPuedeVer();

        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        $lote = Reproceso::with([
            'orden.pedido.cliente',
            'orden.maquina',
            'detalle',
            'salida',
            'inspeccion',
            'ubicacion',
            'maquina',
            'maquinaPeletizado',
            'productoResina',
            'creadoPor',
            'reportadoPor',
        ])->findOrFail($id);

        $ubicacionesResina = \App\Models\Ubicaciones::query()
            ->where(function ($q) {
                $q->whereRaw('UPPER(folio_interno) LIKE ?', ['%NAVE 2%'])
                    ->orWhereRaw('UPPER(folio_interno) LIKE ?', ['%RESINA%'])
                    ->orWhereRaw('UPPER(folio_interno) LIKE ?', ['%PELET%']);
            })
            ->orderBy('folio_interno')
            ->get(['id', 'folio_interno', 'descripcion']);

        $maquinas = $this->maquinasReproceso();
        $maquinasPeletizado = $this->maquinasPeletizado();
        $maquinasOcupadas = $this->idsMaquinasOcupadas($lote->id);
        $productosResina = $this->productosResina();

        return view('Produccion.reproceso_detalle', compact(
            'varpantallas',
            'varsubmenus',
            'lote',
            'ubicacionesResina',
            'maquinas',
            'maquinasPeletizado',
            'maquinasOcupadas',
            'productosResina'
        ));
    }

    public function asignarMaquina(Request $request, int $id): RedirectResponse
    {
        $this->assertPuedeVer();

        $lote = Reproceso::findOrFail($id);
        $this->assertLoteDesdeOpRechazada($lote);
        if ($lote->estaCerrada()) {
            throw ValidationException::withMessages(['maquina_id' => 'El lote ya está cerrado.']);
        }

        $validated = $request->validate([
            'maquina_id' => ['required', 'integer', 'exists:tbl_maquinas,id'],
        ]);

        $maquina = Maquina::paraReproceso()->where('id', $validated['maquina_id'])->first();
        if (!$maquina) {
            throw ValidationException::withMessages([
                'maquina_id' => 'Solo se permiten máquinas marcadas para reproceso (triturado / peletizado) y activas.',
            ]);
        }

        DB::transaction(function () use ($lote, $maquina) {
            $this->assertMaquinaDisponible((int) $maquina->id, (int) $lote->id);
            $lote->update(['maquina_id' => $maquina->id]);
        });

        return redirect()
            ->route('produccion.reproceso.detalle', $lote->id)
            ->with('success', 'Máquina de reproceso asignada: ' . ($maquina->codigo ? $maquina->codigo . ' — ' : '') . $maquina->nombre);
    }

    public function avanzar(Request $request, int $id): RedirectResponse
    {
        $this->assertPuedeVer();

        $lote = Reproceso::findOrFail($id);
        $this->assertLoteDesdeOpRechazada($lote);
        if ($lote->estaCerrada()) {
            throw ValidationException::withMessages(['estatus' => 'Este lote ya está cerrado.']);
        }

        $siguiente = $lote->siguiente_estatus;
        if (!$siguiente) {
            throw ValidationException::withMessages(['estatus' => 'No hay siguiente paso para este lote.']);
        }

        // En triturado se puede elegir la máquina en el mismo paso; en el resto debe existir antes.
        if (!$lote->tieneMaquina() && $siguiente !== Reproceso::ESTATUS_TRITURADA) {
            throw ValidationException::withMessages([
                'maquina_id' => 'Antes de avanzar debe seleccionar la máquina de reproceso (debe estar dada de alta).',
            ]);
        }

        $rules = ['observaciones' => ['nullable', 'string', 'max:1000']];

        if ($siguiente === Reproceso::ESTATUS_TRITURADA) {
            $rules['maquina_id'] = ['required', 'integer', 'exists:tbl_maquinas,id'];
        }
        if ($siguiente === Reproceso::ESTATUS_LISTO_PELETIZADO
            || ($siguiente === Reproceso::ESTATUS_EN_PELETIZADO && !$lote->tieneMaquinaPeletizado())) {
            $rules['maquina_peletizado_id'] = ['required', 'integer', 'exists:tbl_maquinas,id'];
        }
        if ($siguiente === Reproceso::ESTATUS_EN_PELETIZADO && $lote->tieneMaquinaPeletizado()) {
            $rules['maquina_peletizado_id'] = ['nullable', 'integer', 'exists:tbl_maquinas,id'];
        }
        if ($siguiente === Reproceso::ESTATUS_PESADA) {
            $rules['kg_pesado'] = ['required', 'numeric', 'min:0.001'];
        }
        if ($siguiente === Reproceso::ESTATUS_REPORTADA) {
            $rules['kg_reportado'] = ['required', 'numeric', 'min:0.001'];
        }
        if ($siguiente === Reproceso::ESTATUS_SACA_PESADA) {
            $rules['kg_saca_triturada'] = ['required', 'numeric', 'min:0.001'];
            $rules['etiqueta_triturado'] = ['required', 'string', 'max:80'];
        }
        if ($siguiente === Reproceso::ESTATUS_IDENTIFICADA) {
            $rules['identificacion_material'] = ['required', 'string', 'max:255'];
            $rules['reporte_triturado'] = ['nullable', 'string', 'max:80'];
        }
        if ($siguiente === Reproceso::ESTATUS_EN_PELETIZADO) {
            $rules['reporte_peletizado'] = ['nullable', 'string', 'max:80'];
            $rules['peletizado_inicio_at'] = ['nullable', 'date'];
        }
        if ($siguiente === Reproceso::ESTATUS_SACA_RESINA_DESMONTADA) {
            $rules['etiqueta_resina'] = ['required', 'string', 'max:80'];
        }
        if ($siguiente === Reproceso::ESTATUS_RESINA_IDENTIFICADA) {
            $manual = $request->boolean('resina_manual');
            $rules['resina_manual'] = ['nullable', 'boolean'];
            if ($manual) {
                $rules['identificacion_resina'] = ['required', 'string', 'max:255'];
                $rules['producto_resina_id'] = ['nullable', 'integer', 'exists:tblproductos,id'];
            } else {
                $rules['producto_resina_id'] = ['required', 'integer', 'exists:tblproductos,id'];
                $rules['identificacion_resina'] = ['nullable', 'string', 'max:255'];
            }
        }
        if ($siguiente === Reproceso::ESTATUS_SACA_RESINA_PESADA) {
            $rules['kg_saca_resina'] = ['required', 'numeric', 'min:0.001'];
        }
        if ($siguiente === Reproceso::ESTATUS_RESINA_ALMACENADA) {
            $rules['ubicacion_id'] = ['nullable', 'integer', 'exists:tblubicaciones,id'];
        }
        if ($siguiente === Reproceso::ESTATUS_PRODUCCION_REPORTADA) {
            $rules['kg_produccion_reportado'] = ['required', 'numeric', 'min:0.001'];
        }
        if ($siguiente === Reproceso::ESTATUS_KILOS_RESINA_SISTEMA) {
            $rules['producto_resina_id'] = ['required', 'integer', 'exists:tblproductos,id'];
            $rules['kg_resina_sistema'] = ['required', 'numeric', 'min:0.001'];
        }

        $validated = $request->validate($rules);

        $maquinaTrituradoId = null;
        if ($siguiente === Reproceso::ESTATUS_TRITURADA) {
            $maquina = Maquina::paraReproceso()->where('id', $validated['maquina_id'])->first();
            if (!$maquina) {
                throw ValidationException::withMessages([
                    'maquina_id' => 'Solo se permiten máquinas marcadas para reproceso (triturado / peletizado) y activas.',
                ]);
            }
            $maquinaTrituradoId = $maquina->id;
        }

        $maquinaPeletizadoId = null;
        if (!empty($validated['maquina_peletizado_id'])) {
            $maquinaPel = Maquina::paraReproceso()->where('id', $validated['maquina_peletizado_id'])->first();
            if (!$maquinaPel) {
                throw ValidationException::withMessages([
                    'maquina_peletizado_id' => 'Solo se permiten máquinas marcadas para reproceso (triturado / peletizado) y activas.',
                ]);
            }
            $maquinaPeletizadoId = $maquinaPel->id;
        } elseif (in_array($siguiente, [Reproceso::ESTATUS_LISTO_PELETIZADO, Reproceso::ESTATUS_EN_PELETIZADO], true)
            && $lote->tieneMaquinaPeletizado()) {
            $maquinaPeletizadoId = (int) $lote->maquina_peletizado_id;
        }

        $productoResinaIdentificado = null;
        $identificacionResinaManual = null;
        if ($siguiente === Reproceso::ESTATUS_RESINA_IDENTIFICADA) {
            if ($request->boolean('resina_manual')) {
                $identificacionResinaManual = trim((string) ($validated['identificacion_resina'] ?? ''));
                if ($identificacionResinaManual === '') {
                    throw ValidationException::withMessages([
                        'identificacion_resina' => 'Capture el tipo de resina.',
                    ]);
                }
            } else {
                $productoResinaIdentificado = Productos::query()
                    ->where('id', (int) $validated['producto_resina_id'])
                    ->first(['id', 'nombre']);
                if (!$productoResinaIdentificado) {
                    throw ValidationException::withMessages([
                        'producto_resina_id' => 'Seleccione una resina válida del catálogo de productos.',
                    ]);
                }
            }
        }

        DB::transaction(function () use ($lote, $siguiente, $validated, $maquinaTrituradoId, $maquinaPeletizadoId, $productoResinaIdentificado, $identificacionResinaManual) {
            if ($maquinaTrituradoId) {
                $this->assertMaquinaDisponible((int) $maquinaTrituradoId, (int) $lote->id, 'maquina_id');
            }
            if ($maquinaPeletizadoId) {
                $this->assertMaquinaDisponible((int) $maquinaPeletizadoId, (int) $lote->id, 'maquina_peletizado_id');
            }

            $data = [
                'estatus' => $siguiente,
                'observaciones' => $validated['observaciones'] ?? $lote->observaciones,
            ];

            if ($siguiente === Reproceso::ESTATUS_PESADA) {
                $data['kg_pesado'] = $validated['kg_pesado'];
            }
            if ($siguiente === Reproceso::ESTATUS_REPORTADA) {
                $data['kg_reportado'] = $validated['kg_reportado'];
                $data['reportado_por'] = auth()->id();
                $data['reportado_at'] = now();
            }
            if ($siguiente === Reproceso::ESTATUS_TRITURADA) {
                $data['maquina_id'] = $maquinaTrituradoId;
                $data['triturado_por'] = auth()->id();
                $data['triturado_at'] = now();
            }
            if ($siguiente === Reproceso::ESTATUS_SACA_PESADA) {
                $data['kg_saca_triturada'] = $validated['kg_saca_triturada'];
                $data['etiqueta_triturado'] = trim($validated['etiqueta_triturado']);
            }
            if ($siguiente === Reproceso::ESTATUS_IDENTIFICADA) {
                $data['identificacion_material'] = trim($validated['identificacion_material']);
                $data['reporte_triturado'] = !empty($validated['reporte_triturado'])
                    ? trim($validated['reporte_triturado'])
                    : Reproceso::DOC_REPORTE_TRIT;
            }
            if ($siguiente === Reproceso::ESTATUS_LISTO_PELETIZADO) {
                $ubicacion = Reproceso::ubicacionMaterialTriturado();
                if ($ubicacion) {
                    $data['ubicacion_id'] = $ubicacion->id;
                }
                if ($maquinaPeletizadoId) {
                    $data['maquina_peletizado_id'] = $maquinaPeletizadoId;
                }
            }
            if ($siguiente === Reproceso::ESTATUS_EN_PELETIZADO) {
                $ubicacion = Reproceso::ubicacionPeletizado();
                if ($ubicacion) {
                    $data['ubicacion_id'] = $ubicacion->id;
                }
                if ($maquinaPeletizadoId) {
                    $data['maquina_peletizado_id'] = $maquinaPeletizadoId;
                }
                $data['reporte_peletizado'] = !empty($validated['reporte_peletizado'])
                    ? trim($validated['reporte_peletizado'])
                    : Reproceso::DOC_REPORTE_PELLET;
                $data['peletizado_inicio_at'] = !empty($validated['peletizado_inicio_at'])
                    ? $validated['peletizado_inicio_at']
                    : now();
                $data['peletizado_por'] = auth()->id();
            }
            if ($siguiente === Reproceso::ESTATUS_PELETIZADO) {
                $data['peletizado_fin_at'] = now();
                $data['peletizado_por'] = $lote->peletizado_por ?: auth()->id();
            }
            if ($siguiente === Reproceso::ESTATUS_SACA_RESINA_DESMONTADA) {
                $data['etiqueta_resina'] = trim($validated['etiqueta_resina']);
            }
            if ($siguiente === Reproceso::ESTATUS_RESINA_IDENTIFICADA) {
                if ($identificacionResinaManual !== null) {
                    $data['producto_resina_id'] = null;
                    $data['identificacion_resina'] = $identificacionResinaManual;
                } else {
                    $data['producto_resina_id'] = (int) $productoResinaIdentificado->id;
                    $data['identificacion_resina'] = $productoResinaIdentificado->nombre;
                }
            }
            if ($siguiente === Reproceso::ESTATUS_SACA_RESINA_PESADA) {
                $data['kg_saca_resina'] = $validated['kg_saca_resina'];
                if (empty($lote->reporte_peletizado)) {
                    $data['reporte_peletizado'] = Reproceso::DOC_REPORTE_PELLET;
                }
            }
            if ($siguiente === Reproceso::ESTATUS_RESINA_ALMACENADA) {
                if (!empty($validated['ubicacion_id'])) {
                    $data['ubicacion_id'] = (int) $validated['ubicacion_id'];
                } else {
                    $ubicacion = Reproceso::ubicacionNave2Resina();
                    if ($ubicacion) {
                        $data['ubicacion_id'] = $ubicacion->id;
                    }
                }
            }
            if ($siguiente === Reproceso::ESTATUS_PRODUCCION_REPORTADA) {
                $data['kg_produccion_reportado'] = $validated['kg_produccion_reportado'];
                $data['produccion_reportado_por'] = auth()->id();
                $data['produccion_reportado_at'] = now();
            }
            if ($siguiente === Reproceso::ESTATUS_KILOS_RESINA_SISTEMA) {
                $data['producto_resina_id'] = (int) $validated['producto_resina_id'];
                $data['kg_resina_sistema'] = $validated['kg_resina_sistema'];
                $data['kilos_sistema_por'] = auth()->id();
                $data['kilos_sistema_at'] = now();
            }
            if ($siguiente === Reproceso::ESTATUS_STOCK_RESINA) {
                $this->generarStockResina($lote->fresh());
                $data['stock_por'] = auth()->id();
                $data['stock_at'] = now();
            }

            $lote->update($data);
        });

        $fresh = $lote->fresh(['ubicacion', 'productoResina', 'maquina']);
        $msg = match ($siguiente) {
            Reproceso::ESTATUS_CORTADA => 'Tubería marcada como cortada.',
            Reproceso::ESTATUS_PESADA => 'Peso registrado: ' . number_format((float) $fresh->kg_pesado, 2) . ' kg.',
            Reproceso::ESTATUS_REPORTADA => 'Reproceso reportado.',
            Reproceso::ESTATUS_TRITURADA => 'Material triturado y embolsado.',
            Reproceso::ESTATUS_SACA_PESADA => 'Saca triturado pesada.',
            Reproceso::ESTATUS_IDENTIFICADA => 'Material identificado.',
            Reproceso::ESTATUS_LISTO_PELETIZADO => 'Saca en MATERIAL TRITURADO.',
            Reproceso::ESTATUS_EN_PELETIZADO => 'Inicio de peletizado registrado.',
            Reproceso::ESTATUS_PELETIZADO => 'Peletizado concluido.',
            Reproceso::ESTATUS_SACA_RESINA_DESMONTADA => 'Saca de resina desmontada · ' . $fresh->etiqueta_resina,
            Reproceso::ESTATUS_RESINA_IDENTIFICADA => 'Resina: ' . $fresh->identificacion_resina,
            Reproceso::ESTATUS_SACA_RESINA_PESADA => 'Saca resina: ' . number_format((float) $fresh->kg_saca_resina, 2) . ' kg.',
            Reproceso::ESTATUS_RESINA_ALMACENADA => 'En ' . ($fresh->ubicacion->folio_interno ?? 'Nave 2') . '.',
            Reproceso::ESTATUS_PRODUCCION_REPORTADA => 'Kilos en reporte de producción.',
            Reproceso::ESTATUS_KILOS_RESINA_SISTEMA => 'Kilos de resina en sistema: ' . number_format((float) $fresh->kg_resina_sistema, 2) . ' kg'
                . ($fresh->productoResina ? ' · ' . $fresh->productoResina->nombre : '') . '.',
            Reproceso::ESTATUS_STOCK_RESINA => 'Stock de resina generado en almacén IOHISA.',
            default => 'Estatus actualizado.',
        };

        return redirect()
            ->route('produccion.reproceso.detalle', $lote->id)
            ->with('success', $msg);
    }

    /**
     * Auxiliar de almacén: genera existencia de resina en almacén IOHISA / Nave 2.
     */
    private function generarStockResina(Reproceso $lote): void
    {
        $productoId = (int) ($lote->producto_resina_id ?: 0);
        $cantidad = round((float) ($lote->kg_resina_sistema ?: $lote->kg_produccion_reportado ?: $lote->kg_saca_resina ?: 0), 3);

        if ($productoId <= 0 || $cantidad <= 0) {
            throw ValidationException::withMessages([
                'producto_resina_id' => 'Faltan kilos de resina en sistema o producto. Complete el paso del auxiliar administrativo primero.',
            ]);
        }

        $ubicacion = $lote->ubicacion_id
            ? \App\Models\Ubicaciones::find($lote->ubicacion_id)
            : Reproceso::ubicacionNave2Resina();

        if (!$ubicacion) {
            throw ValidationException::withMessages([
                'ubicacion_id' => 'No hay ubicación de Nave 2 / resina para generar stock.',
            ]);
        }

        $almacenId = (int) ($ubicacion->id_almacen ?? 0);
        if ($almacenId <= 0) {
            $almacenId = (int) (DB::table('tblalmacenes')->where('estado', 'A')->orderBy('id')->value('id') ?: 0);
        }
        if ($almacenId <= 0) {
            throw ValidationException::withMessages([
                'ubicacion_id' => 'No se encontró almacén IOHISA para el stock.',
            ]);
        }

        $tipoId = (int) (DB::table('tbltipos_movimientos_inventario')
            ->where('nombre_movimiento', 'Entrada por reproceso')
            ->value('id') ?: 0);

        $existencia = DB::table('tblexistencias')
            ->where('id_almacen', $almacenId)
            ->where('id_producto', $productoId)
            ->where('id_ubicacion', $ubicacion->id)
            ->first();

        if ($existencia) {
            DB::table('tblexistencias')->where('id', $existencia->id)->update([
                'cantidad_existente' => round((float) $existencia->cantidad_existente + $cantidad, 3),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('tblexistencias')->insert([
                'id_producto' => $productoId,
                'id_almacen' => $almacenId,
                'id_ubicacion' => $ubicacion->id,
                'cantidad_existente' => $cantidad,
                'cantidad_reservada' => 0,
                'id_estado_movinv' => 2,
                'productos_arecibir' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($tipoId > 0 && auth()->id()) {
            DB::table('tblmovimientos_inventario')->insert([
                'id_producto' => $productoId,
                'id_almacen' => $almacenId,
                'id_ubicacion' => $ubicacion->id,
                'id_tipo_movimiento' => $tipoId,
                'cantidad_producto_movimiento' => (int) max(1, (int) round($cantidad)),
                'fecha_movimiento' => now()->format('Y-m-d'),
                'documento_referencia' => Str::limit((string) $lote->folio, 200, ''),
                'observaciones' => 'Stock por reproceso ' . $lote->folio
                    . ' · máquina #' . ($lote->maquina_id ?: '—')
                    . ' · ' . number_format($cantidad, 3) . ' kg resina',
                'id_estado_movinv' => 2,
                'usuario_movimiento' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
