<?php

namespace App\Http\Controllers;

use App\Models\CcAsignacion;
use App\Models\LicParamTipo;
use App\Models\Producto;
use App\Models\ReqNivel;
use App\Models\ReqRuta;
use App\Models\ReqSuplente;
use App\Models\Requisicion;
use App\Models\RequisicionDetalle;
use App\Models\User;
use App\Services\RequisicionWorkflowService;
use App\Traits\MenuTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class RequisicionController extends Controller
{
    use MenuTrait;

    protected RequisicionWorkflowService $workflow;

    public function __construct(RequisicionWorkflowService $workflow)
    {
        $this->middleware('auth');
        $this->workflow = $workflow;
    }

    protected function menuData(): array
    {
        return [
            'varpantallas' => $this->Traermenuenc(),
            'varsubmenus' => $this->Traermenudet(),
        ];
    }

    public function index(Request $request)
    {
        $userId = (int) auth()->id();
        $q = Requisicion::query()->with(['solicitante', 'responsableActual', 'aprobaciones', 'licitacion']);

        $puedeVerTodas = $this->puedeVerTodas();
        // Master/empresa: por defecto ve todas (incluye pendientes de autorizar)
        $vista = $request->get('vista', $puedeVerTodas ? 'todas' : 'activas');

        if ($vista === 'autorizar') {
            $q->where('responsable_actual_id', $userId)
                ->whereIn('estado', ['pendiente_autorizacion', 'reenviada']);
        } elseif ($vista === 'autorizadas') {
            // Compat: las autorizadas viven en historial (ya pasaron a compras/licitación)
            $vista = 'historial';
            $q->whereIn('estado', Requisicion::ESTADOS_HISTORIAL);
            if (! $puedeVerTodas) {
                $q->where(function ($w) use ($userId) {
                    $w->where('solicitante_id', $userId)
                        ->orWhere('responsable_actual_id', $userId)
                        ->orWhereExists(function ($sub) use ($userId) {
                            $sub->selectRaw('1')
                                ->from('tbl_requisicion_aprobaciones as a')
                                ->whereColumn('a.requisicion_id', 'tbl_requisiciones.id')
                                ->where('a.responsable_id', $userId);
                        });
                });
            }
        } elseif ($vista === 'historial') {
            // Historial: desde autorizada (pasa a licitaciones) + cerradas/rechazadas
            $q->whereIn('estado', Requisicion::ESTADOS_HISTORIAL);
            if (! $puedeVerTodas) {
                $q->where(function ($w) use ($userId) {
                    $w->where('solicitante_id', $userId)
                        ->orWhere('responsable_actual_id', $userId)
                        ->orWhereExists(function ($sub) use ($userId) {
                            $sub->selectRaw('1')
                                ->from('tbl_requisicion_aprobaciones as a')
                                ->whereColumn('a.requisicion_id', 'tbl_requisiciones.id')
                                ->where('a.responsable_id', $userId);
                        });
                });
            }
        } elseif ($vista === 'todas' && $puedeVerTodas) {
            // Master: solo en curso (no historial)
            $q->whereIn('estado', Requisicion::ESTADOS_ACTIVAS);
        } elseif ($vista === 'activas') {
            $q->whereIn('estado', Requisicion::ESTADOS_ACTIVAS);
            if (! $puedeVerTodas) {
                $q->where('solicitante_id', $userId);
            }
        } else {
            if ($puedeVerTodas) {
                $vista = 'todas';
                $q->whereIn('estado', Requisicion::ESTADOS_ACTIVAS);
            } else {
                $q->where('solicitante_id', $userId)
                    ->whereIn('estado', Requisicion::ESTADOS_ACTIVAS);
                $vista = 'activas';
            }
        }

        if ($folio = trim((string) $request->get('folio', ''))) {
            $q->where('folio', 'like', '%'.$folio.'%');
        }
        if ($estado = trim((string) $request->get('estado', ''))) {
            $q->where('estado', $estado);
        }
        if ($depto = trim((string) $request->get('departamento', ''))) {
            $q->where('departamento', 'like', '%'.$depto.'%');
        }
        if ($desde = $request->get('desde')) {
            $q->whereDate('fecha_solicitud', '>=', $desde);
        }
        if ($hasta = $request->get('hasta')) {
            $q->whereDate('fecha_solicitud', '<=', $hasta);
        }

        $lista = $q->orderByDesc('id')->paginate(20)->withQueryString();
        $lista->getCollection()->transform(function (Requisicion $r) use ($userId) {
            $r->puede_editar = $r->esEditablePor($userId);
            $r->puede_enviar = $r->esEditablePor($userId);
            $r->puede_decidir = $this->workflow->puedeDecidir($r, $userId);
            $r->puede_cancelar = in_array($r->estado, ['borrador', 'devuelta', 'pendiente_autorizacion', 'reenviada', 'autorizada'], true)
                && ((int) $r->solicitante_id === $userId || $this->puedeVerTodas());

            return $r;
        });

        $kpis = [
            'mias' => Requisicion::query()
                ->when(! $puedeVerTodas, fn ($q) => $q->where('solicitante_id', $userId))
                ->whereIn('estado', Requisicion::ESTADOS_ACTIVAS)
                ->count(),
            'autorizar' => Requisicion::query()->where('responsable_actual_id', $userId)->whereIn('estado', ['pendiente_autorizacion', 'reenviada'])->count(),
            'historial' => Requisicion::query()->whereIn('estado', Requisicion::ESTADOS_HISTORIAL)
                ->when(! $puedeVerTodas, function ($q) use ($userId) {
                    $q->where(function ($w) use ($userId) {
                        $w->where('solicitante_id', $userId)
                            ->orWhere('responsable_actual_id', $userId)
                            ->orWhereExists(function ($sub) use ($userId) {
                                $sub->selectRaw('1')
                                    ->from('tbl_requisicion_aprobaciones as a')
                                    ->whereColumn('a.requisicion_id', 'tbl_requisiciones.id')
                                    ->where('a.responsable_id', $userId);
                            });
                    });
                })->count(),
        ];

        return view('requisiciones.index', array_merge($this->menuData(), [
            'lista' => $lista,
            'vista' => $vista,
            'estados' => Requisicion::ESTADOS,
            'prioridades' => Requisicion::PRIORIDADES,
            'filtros' => $request->only(['folio', 'estado', 'departamento', 'desde', 'hasta', 'vista']),
            'puedeVerTodas' => $puedeVerTodas,
            'puedeConfigurar' => $this->puedeConfigurar(),
            'kpis' => $kpis,
            'userId' => $userId,
        ]));
    }

    public function create()
    {
        return view('requisiciones.form', array_merge($this->menuData(), $this->formCatalogos(), [
            'requisicion' => null,
            'detalles' => [],
            'prioridades' => Requisicion::PRIORIDADES,
        ]));
    }

    public function store(Request $request)
    {
        $data = $this->validarEncabezado($request);
        $partidas = $this->validarPartidas($request);

        $req = DB::transaction(function () use ($data, $partidas, $request) {
            $userId = (int) auth()->id();
            $tipoMeta = $this->resolverTipoParam($data['tipo_param_id'] ?? null);
            $req = Requisicion::query()->create([
                'folio' => $this->workflow->siguienteFolio(),
                'solicitante_id' => $userId,
                'departamento' => $tipoMeta['nombre'],
                'tipo_param_id' => $tipoMeta['id'],
                'centro_costo' => $data['centro_costo'] ?? null,
                'almacen_destino' => null,
                'prioridad' => $data['prioridad'] ?? 'normal',
                'fecha_solicitud' => $data['fecha_solicitud'] ?? now()->toDateString(),
                'fecha_requerida' => $data['fecha_requerida'] ?? null,
                'justificacion' => $data['justificacion'] ?? null,
                'estado' => 'borrador',
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
            $this->syncPartidas($req, $partidas);
            $this->workflow->registrarHistorial($req, 'crear', null, 'borrador', 'Requisición creada', null, $userId);

            if ($request->boolean('enviar')) {
                $this->workflow->enviar($req, $userId);
            }

            return $req;
        });

        return redirect()
            ->route('requisiciones.show', $req->id)
            ->with('success', $request->boolean('enviar') ? 'Requisición enviada a autorización.' : 'Borrador guardado.');
    }

    public function show(int $id)
    {
        $req = Requisicion::query()
            ->with(['detalles', 'aprobaciones.responsable', 'historial.usuario', 'solicitante', 'responsableActual', 'ruta.niveles', 'tipoParam', 'licitacion'])
            ->findOrFail($id);

        $userId = (int) auth()->id();
        if (! $this->puedeVer($req, $userId)) {
            abort(403, 'No tienes acceso a esta requisición.');
        }

        return view('requisiciones.show', array_merge($this->menuData(), [
            'requisicion' => $req,
            'puedeEditar' => $req->esEditablePor($userId),
            'puedeDecidir' => $this->workflow->puedeDecidir($req, $userId),
            'puedeEnviar' => $req->esEditablePor($userId) && $req->detalles->count() > 0,
            'puedeAjustarNiveles' => $this->puedeConfigurar()
                && in_array($req->estado, ['pendiente_autorizacion', 'reenviada', 'autorizada'], true),
            'users' => $this->puedeConfigurar()
                ? User::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
            'estados' => Requisicion::ESTADOS,
        ]));
    }

    /**
     * Ajusta obligatoriedad/responsable de niveles de ESTA requisición (no la plantilla global).
     */
    public function actualizarNiveles(Request $request, int $id)
    {
        if (! $this->puedeConfigurar()) {
            abort(403, 'Solo master/empresa puede ajustar niveles de la requisición.');
        }

        $req = Requisicion::query()->with('aprobaciones')->findOrFail($id);
        $data = $request->validate([
            'niveles' => 'required|array|min:1',
            'niveles.*.id' => 'required|integer',
            'niveles.*.obligatorio' => 'nullable|boolean',
            'niveles.*.responsable_id' => 'nullable|integer|exists:users,id',
        ]);

        $items = [];
        foreach ($data['niveles'] as $n) {
            $items[] = [
                'id' => (int) $n['id'],
                'obligatorio' => ! empty($n['obligatorio']),
                'responsable_id' => $n['responsable_id'] ?? null,
            ];
        }

        try {
            $result = $this->workflow->actualizarNivelesDeRequisicion($req, $items, (int) auth()->id());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('warning', collect($e->errors())->flatten()->first());
        }

        $reqFresh = $result['requisicion'];
        if ($result['autorizada_ahora'] && $reqFresh->estado === 'autorizada') {
            $lic = \App\Models\Licitacion::query()->where('requisicion_id', $reqFresh->id)->first();
            if ($lic) {
                return redirect()
                    ->route('licitaciones.editar', $lic->id)
                    ->with('success', 'Niveles actualizados. Requisición autorizada; abriendo su licitación.');
            }

            // Crear licitación vía el mismo flujo POST (request sintético)
            $sub = Request::create(
                route('licitaciones.desdeRequisicion', $reqFresh->id),
                'POST',
                ['tipo_param_id' => $reqFresh->tipo_param_id]
            );
            $sub->setUserResolver(fn () => auth()->user());

            return app(\App\Http\Controllers\LicitacionController::class)
                ->storeDesdeRequisicion($sub, $reqFresh->id);
        }

        return redirect()
            ->route('requisiciones.show', $reqFresh->id)
            ->with('success', 'Niveles de esta requisición actualizados. Siguiente: '.$reqFresh->estadoLabel());
    }

    public function edit(int $id)
    {
        $req = Requisicion::query()->with('detalles')->findOrFail($id);
        $userId = (int) auth()->id();
        if (! $req->esEditablePor($userId)) {
            return redirect()->route('requisiciones.show', $id)
                ->with('warning', 'Solo puedes editar borradores o requisiciones devueltas.');
        }

        return view('requisiciones.form', array_merge($this->menuData(), $this->formCatalogos(), [
            'requisicion' => $req,
            'detalles' => $req->detalles,
            'prioridades' => Requisicion::PRIORIDADES,
        ]));
    }

    public function update(Request $request, int $id)
    {
        $req = Requisicion::query()->with('detalles')->findOrFail($id);
        $userId = (int) auth()->id();
        if (! $req->esEditablePor($userId)) {
            return back()->with('warning', 'No puedes editar esta requisición.');
        }

        $data = $this->validarEncabezado($request);
        $partidas = $this->validarPartidas($request);

        DB::transaction(function () use ($req, $data, $partidas, $request, $userId) {
            $antes = $req->estado;
            $tipoMeta = $this->resolverTipoParam($data['tipo_param_id'] ?? null);
            $req->fill([
                'departamento' => $tipoMeta['nombre'],
                'tipo_param_id' => $tipoMeta['id'],
                'centro_costo' => $data['centro_costo'] ?? null,
                'almacen_destino' => null,
                'prioridad' => $data['prioridad'] ?? 'normal',
                'fecha_solicitud' => $data['fecha_solicitud'] ?? $req->fecha_solicitud,
                'fecha_requerida' => $data['fecha_requerida'] ?? null,
                'justificacion' => $data['justificacion'] ?? null,
                'updated_by' => $userId,
            ]);
            $req->save();
            $this->syncPartidas($req, $partidas);
            $this->workflow->registrarHistorial($req, 'editar', $antes, $req->estado, 'Requisición actualizada', null, $userId);

            if ($request->boolean('enviar')) {
                $this->workflow->enviar($req, $userId);
            }
        });

        return redirect()
            ->route('requisiciones.show', $id)
            ->with('success', $request->boolean('enviar') ? 'Requisición enviada a autorización.' : 'Cambios guardados.');
    }

    public function enviar(int $id)
    {
        $req = Requisicion::query()->findOrFail($id);
        try {
            $this->workflow->enviar($req, (int) auth()->id());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('warning', collect($e->errors())->flatten()->first());
        }

        return back()->with('success', 'Requisición enviada a autorización.');
    }

    public function autorizar(Request $request, int $id)
    {
        $req = Requisicion::query()->findOrFail($id);
        try {
            $this->workflow->autorizar($req, (int) auth()->id(), $request->input('comentario'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('warning', collect($e->errors())->flatten()->first());
        }

        return back()->with('success', 'Autorización registrada.');
    }

    public function rechazar(Request $request, int $id)
    {
        $request->validate(['comentario' => 'required|string|min:3|max:2000']);
        $req = Requisicion::query()->findOrFail($id);
        try {
            $this->workflow->rechazar($req, (int) auth()->id(), (string) $request->input('comentario'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('warning', collect($e->errors())->flatten()->first());
        }

        return back()->with('success', 'Requisición rechazada.');
    }

    public function devolver(Request $request, int $id)
    {
        $request->validate(['comentario' => 'required|string|min:3|max:2000']);
        $req = Requisicion::query()->findOrFail($id);
        try {
            $this->workflow->devolver($req, (int) auth()->id(), (string) $request->input('comentario'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('warning', collect($e->errors())->flatten()->first());
        }

        return back()->with('success', 'Requisición devuelta para corrección.');
    }

    public function cancelar(Request $request, int $id)
    {
        $request->validate(['comentario' => 'required|string|min:3|max:2000']);
        $req = Requisicion::query()->findOrFail($id);
        try {
            $this->workflow->cancelar($req, (int) auth()->id(), (string) $request->input('comentario'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('warning', collect($e->errors())->flatten()->first());
        }

        return back()->with('success', 'Requisición cancelada.');
    }

    public function configRutas()
    {
        if (! $this->puedeConfigurar()) {
            abort(403, 'No tienes permiso para configurar rutas.');
        }

        $rutas = ReqRuta::query()->with(['niveles.responsable', 'niveles.suplentes.usuario'])->orderByDesc('es_default')->orderBy('nombre')->get();
        $users = User::query()->orderBy('name')->get(['id', 'name', 'email']);

        return view('requisiciones.config', array_merge($this->menuData(), [
            'rutas' => $rutas,
            'users' => $users,
        ]));
    }

    public function storeRuta(Request $request)
    {
        if (! $this->puedeConfigurar()) {
            abort(403);
        }
        $data = $request->validate([
            'nombre' => 'required|string|max:120',
            'departamento' => 'nullable|string|max:120',
            'activo' => 'nullable|boolean',
            'es_default' => 'nullable|boolean',
        ]);

        $activo = $request->boolean('activo', true);
        $esDefault = $request->boolean('es_default');
        DB::transaction(function () use ($data, $activo, $esDefault) {
            if ($esDefault) {
                ReqRuta::query()->update(['es_default' => false]);
            }
            $ruta = ReqRuta::query()->create([
                'nombre' => $data['nombre'],
                'departamento' => $data['departamento'] ?? null,
                'activo' => $activo,
                'es_default' => $esDefault,
            ]);
            foreach ([
                [1, 'Jefe de departamento', true],
                [2, 'Gerencia', true],
                [3, 'Dirección', false],
            ] as [$orden, $nombre, $obligatorio]) {
                ReqNivel::query()->create([
                    'ruta_id' => $ruta->id,
                    'orden' => $orden,
                    'nombre_nivel' => $nombre,
                    'obligatorio' => $obligatorio,
                ]);
            }
        });

        return back()->with('success', 'Ruta creada. Asigna responsables a cada nivel.');
    }

    public function updateNivel(Request $request, int $nivelId)
    {
        if (! $this->puedeConfigurar()) {
            abort(403);
        }
        $data = $request->validate([
            'nombre_nivel' => 'required|string|max:120',
            'responsable_user_id' => 'nullable|integer|exists:users,id',
            'obligatorio' => 'nullable|boolean',
            'suplente_user_id' => 'nullable|integer|exists:users,id',
            'vigencia_desde' => 'nullable|date',
            'vigencia_hasta' => 'nullable|date|after_or_equal:vigencia_desde',
        ]);

        $nivel = ReqNivel::query()->findOrFail($nivelId);
        $nivel->nombre_nivel = $data['nombre_nivel'];
        $nivel->responsable_user_id = $data['responsable_user_id'] ?? null;
        $nivel->obligatorio = $request->boolean('obligatorio', true);
        $nivel->save();

        if (! empty($data['suplente_user_id'])) {
            ReqSuplente::query()->updateOrCreate(
                [
                    'nivel_id' => $nivel->id,
                    'user_id' => $data['suplente_user_id'],
                ],
                [
                    'vigencia_desde' => $data['vigencia_desde'] ?? null,
                    'vigencia_hasta' => $data['vigencia_hasta'] ?? null,
                    'activo' => true,
                ]
            );
        }

        return back()->with('success', 'Nivel actualizado.');
    }

    public function buscarProductos(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['ok' => true, 'items' => []]);
        }

        $cols = ['id', 'nombre', 'id_unidad_medida'];
        if (Schema::hasColumn('tblproductos', 'sku')) {
            $cols[] = 'sku';
        } elseif (Schema::hasColumn('tblproductos', 'codigo')) {
            $cols[] = 'codigo';
        }

        $items = Producto::query()
            ->with('unidad')
            ->when(Schema::hasColumn('tblproductos', 'estado'), function ($query) {
                $query->where(function ($w) {
                    $w->whereNull('estado')->orWhere('estado', 1)->orWhere('estado', '1')->orWhere('estado', 'A');
                });
            })
            ->where(function ($w) use ($q) {
                $w->where('nombre', 'like', '%'.$q.'%');
                if (Schema::hasColumn('tblproductos', 'sku')) {
                    $w->orWhere('sku', 'like', '%'.$q.'%');
                }
                if (Schema::hasColumn('tblproductos', 'codigo')) {
                    $w->orWhere('codigo', 'like', '%'.$q.'%');
                }
                if (Schema::hasColumn('tblproductos', 'descripcion')) {
                    $w->orWhere('descripcion', 'like', '%'.$q.'%');
                }
            })
            ->orderBy('nombre')
            ->limit(20)
            ->get($cols);

        $out = $items->map(function ($p) {
            $um = '';
            try {
                if ($p->relationLoaded('unidad') && $p->unidad) {
                    $um = (string) ($p->unidad->abreviacion ?? $p->unidad->nombre ?? $p->unidad->descripcion ?? '');
                }
            } catch (\Throwable $e) {
                $um = '';
            }
            $codigo = (string) ($p->sku ?? $p->codigo ?? '');

            return [
                'id' => $p->id,
                'codigo' => $codigo,
                'nombre' => $p->nombre,
                'unidad' => $um,
                'etiqueta' => trim(($codigo !== '' ? $codigo.' — ' : '').$p->nombre),
            ];
        });

        return response()->json(['ok' => true, 'items' => $out]);
    }

    /**
     * Alta rápida en tblproductos desde la captura de requisición.
     */
    public function crearProductoRapido(Request $request)
    {
        $data = $request->validate([
            'sku' => 'required|string|min:3|max:100',
            'nombre' => 'required|string|min:2|max:255',
            'id_unidad_medida' => 'required|integer',
            'id_categoria' => 'nullable|integer',
            'descripcion' => 'nullable|string|max:2000',
            'codigo_barras' => 'nullable|string|max:100',
        ]);

        $sku = trim($data['sku']);
        if (Producto::query()->where('sku', $sku)->exists()) {
            return response()->json([
                'ok' => false,
                'message' => 'Ya existe un producto con ese SKU.',
            ], 422);
        }

        $unidadId = (int) $data['id_unidad_medida'];
        if (Schema::hasTable('tblunidadesmedida') && ! DB::table('tblunidadesmedida')->where('id', $unidadId)->exists()) {
            return response()->json(['ok' => false, 'message' => 'Unidad de medida inválida.'], 422);
        }

        $categoriaId = (int) ($data['id_categoria'] ?? 0);
        if ($categoriaId < 1 && Schema::hasTable('tblcategoria_producto')) {
            $categoriaId = (int) (DB::table('tblcategoria_producto')->orderBy('id')->value('id') ?? 0);
        }

        $codigoBarras = trim((string) ($data['codigo_barras'] ?? ''));
        if ($codigoBarras === '') {
            $codigoBarras = $sku;
        }

        $payload = [
            'sku' => $sku,
            'nombre' => trim($data['nombre']),
            'descripcion' => trim((string) ($data['descripcion'] ?? '')) ?: trim($data['nombre']),
            'codigo_barras' => $codigoBarras,
            'precio_unitario' => 0,
            'id_categoria' => $categoriaId ?: null,
            'id_unidad_medida' => $unidadId,
            'id_proveedor' => 0,
            'costo_compra' => 0,
            'costo_venta' => 0,
            'minima_existencia' => 0,
            'maxima_existencia' => 0,
            'fecha_ultima_entrada' => now()->toDateString(),
            'piezasxunidmedida' => 0,
        ];

        if (Schema::hasColumn('tblproductos', 'tipo_proceso')) {
            $payload['tipo_proceso'] = 'OTRO';
        }
        if (Schema::hasColumn('tblproductos', 'created_at')) {
            $payload['created_at'] = now();
            $payload['updated_at'] = now();
        }

        $id = (int) DB::table('tblproductos')->insertGetId($payload);

        $um = '';
        if (Schema::hasTable('tblunidadesmedida')) {
            $u = DB::table('tblunidadesmedida')->where('id', $unidadId)->first();
            if ($u) {
                $um = (string) ($u->abreviacion ?? $u->nombre ?? '');
            }
        }

        return response()->json([
            'ok' => true,
            'message' => 'Producto registrado en el catálogo.',
            'item' => [
                'id' => $id,
                'codigo' => $sku,
                'nombre' => $payload['nombre'],
                'unidad' => $um ?: 'PZA',
                'etiqueta' => $sku.' — '.$payload['nombre'],
            ],
        ], 201);
    }

    protected function validarEncabezado(Request $request): array
    {
        $data = $request->validate([
            'tipo_param_id' => 'required|integer|exists:tbl_lic_param_tipos,id',
            'centro_costo' => 'required|string|max:120',
            'prioridad' => 'required|in:baja,normal,alta,urgente',
            'fecha_solicitud' => 'nullable|date',
            'fecha_requerida' => 'nullable|date',
            'justificacion' => 'nullable|string|max:5000',
        ]);

        $centrosPermitidos = $this->centrosCostoUsuario((int) auth()->id())
            ->pluck('value')
            ->all();
        if ($centrosPermitidos && ! in_array($data['centro_costo'], $centrosPermitidos, true)) {
            throw ValidationException::withMessages([
                'centro_costo' => 'El centro de costos no está asignado a tu usuario.',
            ]);
        }

        return $data;
    }

    /**
     * @return array{id:?int,nombre:?string}
     */
    protected function resolverTipoParam($tipoId): array
    {
        $tipoId = (int) $tipoId;
        if ($tipoId < 1) {
            return ['id' => null, 'nombre' => null];
        }
        $tipo = LicParamTipo::query()->where('activo', true)->find($tipoId);

        return [
            'id' => $tipo?->id,
            'nombre' => $tipo?->nombre,
        ];
    }

    protected function formCatalogos(): array
    {
        return [
            'productos' => $this->productosSelect(),
            'unidades' => $this->unidadesSelect(),
            'unidadesMedida' => $this->unidadesMedidaCatalogo(),
            'categoriasProducto' => $this->categoriasProductoCatalogo(),
            'tiposParam' => LicParamTipo::query()->where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'descripcion']),
            'centrosCosto' => $this->centrosCostoUsuario((int) auth()->id()),
        ];
    }

    protected function centrosCostoUsuario(int $userId)
    {
        if ($userId < 1 || ! Schema::hasTable('tbl_cc_asignaciones')) {
            return collect();
        }

        return CcAsignacion::query()
            ->where('user_id', $userId)
            ->whereNotNull('centro_codigo')
            ->orderByDesc('es_principal')
            ->orderBy('empresa')
            ->orderBy('centro_nombre')
            ->get(['empresa', 'centro_codigo', 'centro_nombre', 'es_principal'])
            ->unique(fn ($r) => strtolower($r->empresa.'|'.$r->centro_codigo))
            ->values()
            ->map(function ($r) {
                $label = trim(($r->centro_nombre ?: $r->centro_codigo).' · '.strtoupper((string) $r->empresa));
                $value = $r->centro_codigo.(($r->empresa) ? ' ('.$r->empresa.')' : '');

                return (object) [
                    'value' => $value,
                    'label' => $label,
                    'codigo' => $r->centro_codigo,
                    'empresa' => $r->empresa,
                ];
            });
    }

    protected function validarPartidas(Request $request): array
    {
        $data = $request->validate([
            'partidas' => 'required|array|min:1',
            'partidas.*.producto_id' => 'nullable|integer',
            'partidas.*.codigo' => 'nullable|string|max:80',
            'partidas.*.descripcion' => 'required|string|max:255',
            'partidas.*.unidad_medida' => 'required|string|max:40',
            'partidas.*.cantidad' => 'required|numeric|gt:0',
            'partidas.*.especificaciones' => 'nullable|string|max:2000',
            'partidas.*.motivo' => 'nullable|string|max:255',
            'partidas.*.fecha_requerida' => 'nullable|date',
            'partidas.*.observaciones' => 'nullable|string|max:2000',
            'partidas.*.es_no_catalogado' => 'nullable|boolean',
            'partidas.*.registrar_catalogo' => 'nullable|boolean',
            'partidas.*.sku_nuevo' => 'nullable|string|max:100',
        ]);

        return $data['partidas'];
    }

    protected function syncPartidas(Requisicion $req, array $partidas): void
    {
        $req->detalles()->delete();
        $orden = 1;
        foreach ($partidas as $p) {
            $desc = trim((string) ($p['descripcion'] ?? ''));
            if ($desc === '') {
                continue;
            }

            $productoId = ! empty($p['producto_id']) ? (int) $p['producto_id'] : null;
            $codigo = $p['codigo'] ?? null;

            // Si marcó registrar en catálogo y aún no tiene producto, lo crea en tblproductos
            if (! $productoId && ! empty($p['registrar_catalogo'])) {
                $creado = $this->crearProductoDesdePartida($p);
                if ($creado) {
                    $productoId = (int) $creado['id'];
                    $codigo = $creado['codigo'];
                }
            }

            RequisicionDetalle::query()->create([
                'requisicion_id' => $req->id,
                'producto_id' => $productoId,
                'codigo' => $codigo,
                'descripcion' => $desc,
                'unidad_medida' => $p['unidad_medida'] ?? null,
                'cantidad' => $p['cantidad'],
                'especificaciones' => $p['especificaciones'] ?? null,
                'motivo' => $p['motivo'] ?? null,
                'fecha_requerida' => $p['fecha_requerida'] ?? null,
                'observaciones' => $p['observaciones'] ?? null,
                'es_no_catalogado' => empty($productoId),
                'orden' => $orden++,
            ]);
        }
    }

    /**
     * Crea producto en catálogo a partir de una partida (fallback al guardar).
     *
     * @return array{id:int,codigo:string}|null
     */
    protected function crearProductoDesdePartida(array $p): ?array
    {
        $nombre = trim((string) ($p['descripcion'] ?? ''));
        if ($nombre === '') {
            return null;
        }

        $sku = trim((string) ($p['sku_nuevo'] ?? $p['codigo'] ?? ''));
        if ($sku === '') {
            $sku = 'REQ-'.strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $nombre) ?: 'PROD', 0, 8)).'-'.now()->format('ymdHis');
        }
        $sku = mb_substr($sku, 0, 100);

        $existente = Producto::query()->where('sku', $sku)->first();
        if ($existente) {
            return ['id' => (int) $existente->id, 'codigo' => (string) $existente->sku];
        }

        $unidadTexto = trim((string) ($p['unidad_medida'] ?? 'PZA'));
        $unidadId = $this->resolverUnidadId($unidadTexto);
        $categoriaId = Schema::hasTable('tblcategoria_producto')
            ? (int) (DB::table('tblcategoria_producto')->orderBy('id')->value('id') ?? 0)
            : 0;

        $payload = [
            'sku' => $sku,
            'nombre' => mb_substr($nombre, 0, 255),
            'descripcion' => mb_substr(trim((string) ($p['especificaciones'] ?? $nombre)), 0, 2000),
            'codigo_barras' => $sku,
            'precio_unitario' => 0,
            'id_categoria' => $categoriaId ?: null,
            'id_unidad_medida' => $unidadId,
            'id_proveedor' => 0,
            'costo_compra' => 0,
            'costo_venta' => 0,
            'minima_existencia' => 0,
            'maxima_existencia' => 0,
            'fecha_ultima_entrada' => now()->toDateString(),
            'piezasxunidmedida' => 0,
        ];
        if (Schema::hasColumn('tblproductos', 'tipo_proceso')) {
            $payload['tipo_proceso'] = 'OTRO';
        }
        if (Schema::hasColumn('tblproductos', 'created_at')) {
            $payload['created_at'] = now();
            $payload['updated_at'] = now();
        }

        $id = (int) DB::table('tblproductos')->insertGetId($payload);

        return ['id' => $id, 'codigo' => $sku];
    }

    protected function resolverUnidadId(string $texto): int
    {
        if (! Schema::hasTable('tblunidadesmedida')) {
            return 0;
        }
        $texto = trim($texto);
        $row = DB::table('tblunidadesmedida')
            ->where('abreviacion', $texto)
            ->orWhere('nombre', $texto)
            ->orWhere('nombre', 'like', $texto)
            ->first();
        if ($row) {
            return (int) $row->id;
        }

        return (int) (DB::table('tblunidadesmedida')->where('abreviacion', 'PZA')->value('id')
            ?? DB::table('tblunidadesmedida')->orderBy('id')->value('id')
            ?? 0);
    }

    protected function unidadesMedidaCatalogo()
    {
        if (! Schema::hasTable('tblunidadesmedida')) {
            return collect();
        }

        return DB::table('tblunidadesmedida')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'abreviacion']);
    }

    protected function categoriasProductoCatalogo()
    {
        if (! Schema::hasTable('tblcategoria_producto')) {
            return collect();
        }

        return DB::table('tblcategoria_producto')
            ->orderBy('nombre')
            ->limit(300)
            ->get(['id', 'nombre']);
    }

    protected function puedeVer(Requisicion $req, int $userId): bool
    {
        if ($this->puedeVerTodas()) {
            return true;
        }
        if ((int) $req->solicitante_id === $userId) {
            return true;
        }
        if ((int) $req->responsable_actual_id === $userId) {
            return true;
        }

        return $req->aprobaciones()->where('responsable_id', $userId)->exists();
    }

    protected function puedeVerTodas(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        $tipo = strtolower((string) ($user->tipo ?? ''));

        return in_array($tipo, ['master', 'empresa'], true);
    }

    protected function puedeConfigurar(): bool
    {
        return $this->puedeVerTodas();
    }

    protected function productosSelect()
    {
        if (! Schema::hasTable('tblproductos')) {
            return collect();
        }

        $cols = ['id', 'nombre'];
        if (Schema::hasColumn('tblproductos', 'sku')) {
            $cols[] = 'sku';
        } elseif (Schema::hasColumn('tblproductos', 'codigo')) {
            $cols[] = 'codigo';
        }

        return Producto::query()
            ->orderBy('nombre')
            ->limit(500)
            ->get($cols)
            ->map(function ($p) {
                $p->codigo = $p->sku ?? $p->codigo ?? '';

                return $p;
            });
    }

    protected function unidadesSelect(): array
    {
        if (! Schema::hasTable('tblunidadesmedida')) {
            return ['PZA', 'KG', 'LT', 'M', 'SERV'];
        }
        $col = Schema::hasColumn('tblunidadesmedida', 'nombre')
            ? 'nombre'
            : (Schema::hasColumn('tblunidadesmedida', 'descripcion') ? 'descripcion' : null);
        if (! $col) {
            return ['PZA', 'KG', 'LT', 'M', 'SERV'];
        }

        return DB::table('tblunidadesmedida')->orderBy($col)->limit(200)->pluck($col)->filter()->values()->all();
    }
}
