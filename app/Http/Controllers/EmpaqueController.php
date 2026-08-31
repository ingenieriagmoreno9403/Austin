<?php

namespace App\Http\Controllers;

use App\Models\OrdenProduccion;
use App\Models\ProcesoProduccionResponsable;
use App\Models\TipoEmpaque;
use App\Models\Ubicaciones;
use App\Models\VentaPedido;
use App\Traits\AlmacenesTraits;
use App\Traits\MenuTrait;
use App\Traits\SistemasTraits;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmpaqueController extends Controller
{
    use MenuTrait;
    use AlmacenesTraits;
    use SistemasTraits;

    public function __construct()
    {
        $this->middleware('auth');
    }

    private function assertPuedeVerEmpaque(): void
    {
        $ok = $this->forpermisos('ver_empaque_produccion') === 'ver_empaque_produccion'
            || $this->forpermisos('ver_produccion') === 'ver_produccion';
        if (!$ok) {
            abort(403, 'No tiene permiso para el módulo de Empaque.');
        }
    }

    public function index(Request $request): View
    {
        $this->assertPuedeVerEmpaque();

        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        $filtro = $request->input('estatus', 'pendientes');
        $estatusEmpaque = OrdenProduccion::ESTATUS_EMPAQUE;
        $estatusCola = array_merge(
            [OrdenProduccion::ESTATUS_REVISION_CALIDAD, OrdenProduccion::ESTATUS_EMPACANDO],
            $estatusEmpaque
        );

        $query = OrdenProduccion::with([
                'pedido.cliente',
                'maquina',
                'ubicacionDestino',
                'detalles',
            ])
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if ($filtro === 'terminadas') {
            $query->where('estatus', OrdenProduccion::ESTATUS_TERMINADA)
                ->where('updated_at', '>=', now()->subDays(14));
        } elseif ($filtro === 'todas') {
            $query->whereIn('estatus', array_merge($estatusCola, [OrdenProduccion::ESTATUS_TERMINADA]));
        } else {
            $query->whereIn('estatus', $estatusCola)
                ->where(function ($w) {
                    $w->whereNull('tipo_proceso')
                        ->orWhere('tipo_proceso', OrdenProduccion::TIPO_PROCESO_TUBO);
                });
            $filtro = 'pendientes';
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $query->where(function ($w) use ($q) {
                $w->where('folio', 'like', '%' . $q . '%')
                    ->orWhereHas('pedido', function ($p) use ($q) {
                        $p->where('folio', 'like', '%' . $q . '%');
                    })
                    ->orWhereHas('pedido.cliente', function ($c) use ($q) {
                        $c->where('nombre', 'like', '%' . $q . '%');
                    });
            });
        }

        $ordenes = $query->limit(100)->get();

        $conteos = [
            'pendientes' => OrdenProduccion::whereIn('estatus', $estatusCola)
                ->where(function ($w) {
                    $w->whereNull('tipo_proceso')
                        ->orWhere('tipo_proceso', OrdenProduccion::TIPO_PROCESO_TUBO);
                })
                ->count(),
            'terminadas' => OrdenProduccion::where('estatus', OrdenProduccion::ESTATUS_TERMINADA)
                ->where('updated_at', '>=', now()->subDays(14))
                ->count(),
        ];

        $responsablesProceso = [];
        try {
            $responsablesProceso = ProcesoProduccionResponsable::mapaResponsables();
        } catch (\Throwable $e) {
            // opcional
        }

        return view('Produccion.empaque_index', compact(
            'varpantallas',
            'varsubmenus',
            'ordenes',
            'filtro',
            'conteos',
            'responsablesProceso'
        ));
    }

    public function show(int $id): View
    {
        $this->assertPuedeVerEmpaque();

        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        $orden = OrdenProduccion::with([
            'pedido.cliente',
            'maquina',
            'ubicacionDestino',
            'detalles.producto',
            'detalles.salidas.inspeccion',
        ])->findOrFail($id);

        $almacenes = $this->Listadoalmacenes();
        $ubicaciones = collect();
        foreach ($almacenes as $almacen) {
            $ubicaciones = $ubicaciones->merge($this->Listadoubicacionesxidalmacen($almacen->id));
        }
        $tiposUbicacion = $this->Listadotiposubi();

        $responsablesProceso = [];
        try {
            $responsablesProceso = ProcesoProduccionResponsable::mapaResponsables();
        } catch (\Throwable $e) {
            // opcional
        }

        $editable = !in_array($orden->estatus, OrdenProduccion::ESTATUS_FINALES, true);
        $tiposEmpaque = TipoEmpaque::activosOrdenados();

        return view('Produccion.empaque_detalle', compact(
            'varpantallas',
            'varsubmenus',
            'orden',
            'almacenes',
            'ubicaciones',
            'tiposUbicacion',
            'tiposEmpaque',
            'responsablesProceso',
            'editable'
        ));
    }

    public function catalogoTipos(Request $request): View
    {
        $this->assertPuedeVerEmpaque();

        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $tipos = TipoEmpaque::orderBy('orden')->orderBy('nombre')->get();

        return view('Produccion.tipos_empaque', compact(
            'varpantallas',
            'varsubmenus',
            'tipos'
        ));
    }

    public function storeTipo(Request $request): JsonResponse|RedirectResponse
    {
        $this->assertPuedeVerEmpaque();

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'codigo' => ['nullable', 'string', 'max:30', 'unique:tbl_tipos_empaque,codigo'],
            'requiere_enrollar' => ['nullable', 'boolean'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:999'],
            'estatus' => ['nullable', Rule::in(['A', 'I'])],
        ]);

        $nombre = trim($validated['nombre']);
        $codigo = strtoupper(trim((string) ($validated['codigo'] ?? '')));

        if ($codigo === '') {
            $base = Str::upper(Str::slug($nombre, '_'));
            $base = Str::limit(preg_replace('/[^A-Z0-9_]/', '', $base) ?: 'EMPAQUE', 24, '');
            $codigo = $base;
            $n = 1;
            while (TipoEmpaque::where('codigo', $codigo)->exists()) {
                $codigo = Str::limit($base, 24, '') . '_' . $n;
                $n++;
            }
        }

        $tipo = TipoEmpaque::create([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'descripcion' => $validated['descripcion'] ?? null,
            'requiere_enrollar' => $request->boolean('requiere_enrollar'),
            'orden' => (int) ($validated['orden'] ?? ((int) TipoEmpaque::max('orden') + 1)),
            'estatus' => $validated['estatus'] ?? 'A',
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Tipo de empaque agregado.',
                'tipo' => [
                    'id' => $tipo->id,
                    'codigo' => $tipo->codigo,
                    'nombre' => $tipo->nombre,
                    'requiere_enrollar' => (bool) $tipo->requiere_enrollar,
                ],
            ]);
        }

        return redirect()
            ->route('produccion.tipos_empaque')
            ->with('success', 'Tipo de empaque "' . $tipo->nombre . '" creado.');
    }

    public function actualizarTipo(Request $request, int $id): RedirectResponse
    {
        $this->assertPuedeVerEmpaque();

        $tipo = TipoEmpaque::findOrFail($id);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'requiere_enrollar' => ['nullable', 'boolean'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:999'],
            'estatus' => ['required', Rule::in(['A', 'I'])],
        ]);

        $tipo->update([
            'nombre' => trim($validated['nombre']),
            'descripcion' => $validated['descripcion'] ?? null,
            'requiere_enrollar' => $request->boolean('requiere_enrollar'),
            'orden' => (int) ($validated['orden'] ?? $tipo->orden),
            'estatus' => $validated['estatus'],
        ]);

        return redirect()
            ->route('produccion.tipos_empaque')
            ->with('success', 'Tipo de empaque actualizado.');
    }

    public function avanzar(Request $request, int $id): RedirectResponse
    {
        $this->assertPuedeVerEmpaque();

        $orden = OrdenProduccion::findOrFail($id);
        if (in_array($orden->estatus, OrdenProduccion::ESTATUS_FINALES, true)) {
            throw ValidationException::withMessages([
                'estatus' => 'La orden ya está cerrada.',
            ]);
        }

        $codigosActivos = array_keys(TipoEmpaque::opcionesActivas());
        if (empty($codigosActivos)) {
            $codigosActivos = array_keys(OrdenProduccion::$tiposEmpaque);
        }
        // Permitir el valor ya guardado en la OP aunque esté inactivo.
        if ($orden->tipo_empaque) {
            $codigosActivos[] = $orden->tipo_empaque;
            $codigosActivos = array_values(array_unique($codigosActivos));
        }

        $validated = $request->validate([
            'accion' => ['required', 'string', Rule::in([
                'preparar',
                'enrollar',
                'longitud',
                'cortar_flejar',
                'terminar',
            ])],
            'tipo_empaque' => ['nullable', 'string', Rule::in($codigosActivos)],
            'longitud_objetivo_m' => ['nullable', 'numeric', 'min:0.001'],
            'ubicacion_destino_id' => ['nullable', 'integer', 'exists:tblubicaciones,id'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $accion = $validated['accion'];
        $datos = ['updated_at' => now()];

        if (!empty($validated['tipo_empaque'])) {
            $datos['tipo_empaque'] = $validated['tipo_empaque'];
        }
        if (isset($validated['longitud_objetivo_m'])) {
            $datos['longitud_objetivo_m'] = $validated['longitud_objetivo_m'];
        }
        if (!empty($validated['ubicacion_destino_id'])) {
            $ubi = Ubicaciones::find((int) $validated['ubicacion_destino_id']);
            if (!$ubi) {
                throw ValidationException::withMessages([
                    'ubicacion_destino_id' => 'La ubicación de almacén no existe.',
                ]);
            }
            $datos['ubicacion_destino_id'] = (int) $ubi->id;
            $datos['nave_destino'] = trim(($ubi->folio_interno ?? '') . ($ubi->descripcion ? ' — ' . $ubi->descripcion : ''));
        }
        if (!empty($validated['observaciones'])) {
            $datos['observaciones'] = trim(
                ($orden->observaciones ? $orden->observaciones . "\n" : '')
                . '[' . now()->format('d/m H:i') . '] ' . $validated['observaciones']
            );
        }

        $tipo = $datos['tipo_empaque'] ?? $orden->tipo_empaque;
        $requiereEnrollar = TipoEmpaque::requiereEnrollar($tipo);
        $etiquetaTipo = TipoEmpaque::etiqueta($tipo);

        $mensaje = match ($accion) {
            'preparar' => (function () use (&$datos, $tipo, $requiereEnrollar, $etiquetaTipo) {
                if (empty($tipo)) {
                    throw ValidationException::withMessages([
                        'tipo_empaque' => 'Seleccione el tipo de empaque.',
                    ]);
                }
                $datos['estatus'] = $requiereEnrollar
                    ? OrdenProduccion::ESTATUS_ENROLLANDO
                    : OrdenProduccion::ESTATUS_A_LONGITUD;

                return $requiereEnrollar
                    ? 'Empaque preparado (' . $etiquetaTipo . '). Siguiente: sujetar tubería al enrollador.'
                    : 'Empaque preparado (' . $etiquetaTipo . '). Siguiente: esperar longitud requerida.';
            })(),
            'enrollar' => (function () use (&$datos, $requiereEnrollar) {
                if (!$requiereEnrollar) {
                    throw ValidationException::withMessages([
                        'tipo_empaque' => 'Enrollar solo aplica cuando el tipo de empaque requiere enrollador.',
                    ]);
                }
                $datos['estatus'] = OrdenProduccion::ESTATUS_A_LONGITUD;

                return 'Tubería sujeta al enrollador. Espere la longitud requerida.';
            })(),
            'longitud' => (function () use (&$datos, $validated, $orden) {
                $largo = $validated['longitud_objetivo_m'] ?? $orden->longitud_objetivo_m;
                if (empty($largo) || (float) $largo <= 0) {
                    throw ValidationException::withMessages([
                        'longitud_objetivo_m' => 'Indique la longitud requerida (metros).',
                    ]);
                }
                $datos['longitud_objetivo_m'] = $largo;
                $datos['estatus'] = OrdenProduccion::ESTATUS_CORTAR_FLEJAR;

                return 'Longitud alcanzada (' . number_format((float) $largo, 2) . ' m). Siguiente: cortar y flejar.';
            })(),
            'cortar_flejar' => (function () use (&$datos) {
                $datos['estatus'] = OrdenProduccion::ESTATUS_CORTAR_FLEJAR;

                return 'Corte y flejado listos. Seleccione ubicación de almacén e indique «Almacenar / Terminar».';
            })(),
            'terminar' => (function () use (&$datos, $validated, $orden) {
                $ubicacionId = (int) ($validated['ubicacion_destino_id'] ?? $orden->ubicacion_destino_id ?? 0);
                if ($ubicacionId <= 0) {
                    throw ValidationException::withMessages([
                        'ubicacion_destino_id' => 'Seleccione la ubicación de almacén (nave) donde se almacenará el producto.',
                    ]);
                }

                $ubi = Ubicaciones::find($ubicacionId);
                if (!$ubi) {
                    throw ValidationException::withMessages([
                        'ubicacion_destino_id' => 'La ubicación de almacén no existe.',
                    ]);
                }

                $etiqueta = trim(($ubi->folio_interno ?? '') . ($ubi->descripcion ? ' — ' . $ubi->descripcion : ''));
                $datos['ubicacion_destino_id'] = $ubicacionId;
                $datos['nave_destino'] = $etiqueta !== '' ? $etiqueta : ('Ubicación #' . $ubicacionId);
                $datos['estatus'] = OrdenProduccion::ESTATUS_TERMINADA;

                if ($orden->pedido_id) {
                    VentaPedido::where('id', $orden->pedido_id)
                        ->whereNotIn('estatus', ['LISTO_PARA_CARGA', 'CARGA_AVISADA', 'DESPACHADO'])
                        ->update(['estatus' => 'LISTO_PARA_CARGA', 'updated_at' => now()]);
                }

                return 'Orden terminada. Producto enviado a ' . $datos['nave_destino']
                    . '. Ya aparece en Cargas (Almacén).';
            })(),
            default => 'Estatus de empaque actualizado.',
        };

        if ($accion === 'preparar' && !in_array($orden->estatus, array_merge(
            OrdenProduccion::ESTATUS_EMPAQUE,
            [OrdenProduccion::ESTATUS_REVISION_CALIDAD, OrdenProduccion::ESTATUS_EMPACANDO, OrdenProduccion::ESTATUS_EN_PRODUCCION]
        ), true)) {
            throw ValidationException::withMessages([
                'estatus' => 'La orden aún no está en etapa de empaque/calidad.',
            ]);
        }

        $orden->update($datos);

        return redirect()
            ->route('produccion.empaque.detalle', $orden->id)
            ->with('success', $mensaje);
    }

    public function storeUbicacion(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $this->assertPuedeVerEmpaque();
        OrdenProduccion::findOrFail($id);

        $validated = $request->validate([
            'id_almacen' => ['required', 'integer', 'exists:tblalmacenes,id'],
            'folio_interno' => ['required', 'string', 'min:2', 'max:100'],
            'descripcion' => ['required', 'string', 'min:2', 'max:100'],
            'id_tipo_ubicacion' => ['required', 'integer', 'exists:tbltipos_ubicaciones,id'],
            'capacidad' => ['nullable', 'numeric', 'min:0'],
            'nivel' => ['nullable', 'string', 'max:10'],
            'observaciones' => ['nullable', 'string', 'max:100'],
        ]);

        $ubi = new Ubicaciones();
        $ubi->id_almacen = (int) $validated['id_almacen'];
        $ubi->folio_interno = trim($validated['folio_interno']);
        $ubi->descripcion = trim($validated['descripcion']);
        $ubi->id_tipo_ubicacion = (int) $validated['id_tipo_ubicacion'];
        $ubi->capacidad = $validated['capacidad'] ?? null;
        $ubi->nivel = $validated['nivel'] ?? null;
        $ubi->observaciones = $validated['observaciones'] ?? null;
        $ubi->espacio = 0;
        $ubi->ubicacion = 0;
        $ubi->created_at = now();
        $ubi->save();

        $etiqueta = trim($ubi->folio_interno . ' — ' . $ubi->descripcion);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'ubicacion' => [
                    'id' => (int) $ubi->id,
                    'id_almacen' => (int) $ubi->id_almacen,
                    'folio_interno' => $ubi->folio_interno,
                    'descripcion' => $ubi->descripcion,
                    'etiqueta' => $etiqueta,
                ],
            ]);
        }

        return redirect()
            ->route('produccion.empaque.detalle', $id)
            ->with('success', 'Ubicación ' . $etiqueta . ' creada.');
    }
}
