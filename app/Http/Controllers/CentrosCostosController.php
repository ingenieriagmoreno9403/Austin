<?php

namespace App\Http\Controllers;

use App\Models\CcAsignacion;
use App\Models\CcAsignacionCuenta;
use App\Models\CcAsignacionPermiso;
use App\Models\CcCiclo;
use App\Models\CcGrupoCuenta;
use App\Models\CcGrupoCuentaItem;
use App\Models\CcTipoPermiso;
use App\Models\Empresas;
use App\Models\User;
use App\Services\AutinApiClient;
use App\Traits\MenuTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Vistas de centros de costo / presupuesto 2027.
 * Catálogo SAP vía AutinApi; la captura de presupuesto vive en la vista (sin persistencia aún).
 */
class CentrosCostosController extends Controller
{
    use MenuTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function admin()
    {
        return $this->page('admin', 'CentrosCostos.admin');
    }

    public function ciclo(string $ciclo)
    {
        return $this->page('admin-ciclo', 'CentrosCostos.ciclo', [
            'cicloCodigo' => $ciclo,
            'permisosCatalogo' => $this->permisosCatalogo(),
        ]);
    }

    public function control(Request $request)
    {
        return $this->page('control', 'CentrosCostos.control', [
            'centroInicial' => (string) $request->get('cc', ''),
            'empresaInicial' => (string) $request->get('empresa', ''),
            'cicloInicial' => (string) $request->get('ciclo', ''),
            'vistaInicial' => (string) $request->get('vista', ''),
            'detalleUrl' => route('centros.detalle'),
            'misAsignaciones' => $this->misAsignacionesList(''),
        ]);
    }

    public function detalle(Request $request)
    {
        return $this->page('detalle', 'CentrosCostos.detalle', [
            'centroInicial' => (string) $request->get('cc', ''),
            'empresaInicial' => (string) $request->get('empresa', ''),
            'cicloInicial' => (string) $request->get('ciclo', ''),
            'misAsignaciones' => $this->misAsignacionesList((string) $request->get('ciclo', '')),
        ]);
    }

    public function analisis()
    {
        return $this->page('analisis', 'CentrosCostos.analisis');
    }

    public function grupos()
    {
        return $this->page('grupos', 'CentrosCostos.grupos');
    }

    public function listCiclos(): JsonResponse
    {
        return response()->json(['ciclos' => $this->ciclosPayload()]);
    }

    public function storeCiclo(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tbl_cc_ciclos')) {
            return response()->json(['message' => 'Falta ejecutar la migración de ciclos.'], 422);
        }

        $data = $request->validate([
            'codigo' => 'required|string|max:40',
            'nombre' => 'required|string|max:180',
            'anioReferencia' => 'required|integer|min:2000|max:2100',
            'anio' => 'required|integer|min:2000|max:2100',
            'inicio' => 'nullable|date',
            'fin' => 'nullable|date',
            'capturaHasta' => 'nullable|date',
            'revisionDesde' => 'nullable|date',
            'estado' => 'nullable|in:abierto,en_proceso,en_revision,terminado,aceptado,rechazado',
            'inflacion' => 'nullable|numeric',
            'tipoCambio' => 'nullable|numeric',
            'observaciones' => 'nullable|string',
        ]);

        $codigo = strtoupper(trim($data['codigo']));
        $ciclo = CcCiclo::query()->firstOrNew(['codigo' => $codigo]);
        $nuevo = ! $ciclo->exists;
        $ciclo->fill([
            'nombre' => $data['nombre'],
            'anio_referencia' => $data['anioReferencia'],
            'anio_presupuesto' => $data['anio'],
            'fecha_inicio' => $data['inicio'] ?: null,
            'fecha_fin' => $data['fin'] ?: null,
            'captura_hasta' => $data['capturaHasta'] ?: null,
            'revision_desde' => $data['revisionDesde'] ?: null,
            'estado' => $data['estado'] ?: 'abierto',
            'inflacion' => $data['inflacion'] ?? 0,
            'tipo_cambio' => $data['tipoCambio'] ?? 0,
            'observaciones' => $data['observaciones'] ?? null,
            'updated_by' => auth()->id(),
        ]);
        if ($nuevo) {
            $ciclo->created_by = auth()->id();
        }
        $ciclo->save();

        return response()->json([
            'ok' => true,
            'nuevo' => $nuevo,
            'ciclo' => $this->cicloPayload($ciclo),
        ]);
    }

    public function updateCicloEstado(Request $request, string $ciclo): JsonResponse
    {
        if (! Schema::hasTable('tbl_cc_ciclos')) {
            return response()->json(['message' => 'Falta ejecutar la migración de ciclos.'], 422);
        }

        $data = $request->validate([
            'estado' => 'required|in:abierto,en_proceso,en_revision,terminado,aceptado,rechazado',
        ]);

        $row = CcCiclo::query()->where('codigo', $ciclo)->first();
        if (! $row) {
            return response()->json(['message' => 'Ciclo no encontrado.'], 404);
        }

        $row->estado = $data['estado'];
        $row->updated_by = auth()->id();
        $row->save();

        return response()->json([
            'ok' => true,
            'ciclo' => $this->cicloPayload($row),
        ]);
    }

    public function destroyCiclo(string $ciclo): JsonResponse
    {
        if (! Schema::hasTable('tbl_cc_ciclos')) {
            return response()->json(['message' => 'Falta ejecutar la migración de ciclos.'], 422);
        }

        $row = CcCiclo::query()->where('codigo', $ciclo)->first();
        if (! $row) {
            return response()->json(['message' => 'Ciclo no encontrado.'], 404);
        }

        $stats = $this->cicloStats($row->codigo);

        DB::transaction(function () use ($row) {
            $ids = CcAsignacion::query()->where('ciclo_codigo', $row->codigo)->pluck('id');
            if ($ids->isNotEmpty()) {
                if (Schema::hasTable('tbl_cc_asignacion_cuentas')) {
                    CcAsignacionCuenta::query()->whereIn('asignacion_id', $ids)->delete();
                }
                if (Schema::hasTable('tbl_cc_asignacion_permisos')) {
                    CcAsignacionPermiso::query()->whereIn('asignacion_id', $ids)->delete();
                }
                CcAsignacion::query()->whereIn('id', $ids)->delete();
            }
            $row->delete();
        });

        return response()->json([
            'ok' => true,
            'codigo' => $ciclo,
            'eliminado' => $stats,
        ]);
    }

    public function asignar(string $ciclo, ?string $empresa = null)
    {
        $empresa = strtolower(trim((string) $empresa));
        $empresasOk = ['austin', 'imsa', 'pitic', 'sydney'];
        if ($empresa !== '' && ! in_array($empresa, $empresasOk, true)) {
            return redirect()->route('centros.asignar', $ciclo);
        }

        return $this->page('asignacion', 'CentrosCostos.asignacion', [
            'cicloCodigo' => $ciclo,
            'empresaCodigo' => $empresa,
            'empresaNombre' => $empresa !== '' ? strtoupper($empresa) : '',
            'permisosCatalogo' => $this->permisosCatalogo(),
        ]);
    }

    public function empresasSap(Request $request): JsonResponse
    {
        try {
            $api = app(AutinApiClient::class);
            $dbs = $api->allowedDatabases();
        } catch (Throwable $e) {
            $dbs = ['austin', 'imsa', 'pitic', 'sydney'];
        }

        $ciclo = (string) $request->get('ciclo', '');
        $counts = [];
        if (Schema::hasTable('tbl_cc_asignaciones')) {
            $q = CcAsignacion::query()
                ->selectRaw('empresa, count(*) as total')
                ->groupBy('empresa');
            if ($ciclo !== '') {
                $q->where('ciclo_codigo', $ciclo);
            }
            $counts = $q->pluck('total', 'empresa')->all();
        }

        $gruposCounts = [];
        if (Schema::hasTable('tbl_cc_grupos_cuenta')) {
            $gruposCounts = CcGrupoCuenta::query()
                ->selectRaw('empresa, count(*) as total')
                ->groupBy('empresa')
                ->pluck('total', 'empresa')
                ->all();
        }

        $empresas = collect($dbs)->map(function ($db) use ($counts, $gruposCounts) {
            return [
                'codigo' => $db,
                'nombre' => strtoupper($db),
                'asignaciones' => (int) ($counts[$db] ?? 0),
                'grupos' => (int) ($gruposCounts[$db] ?? 0),
            ];
        })->values()->all();

        return response()->json(['empresas' => $empresas]);
    }

    public function centrosSap(Request $request): JsonResponse
    {
        $empresa = strtolower((string) $request->get('empresa', 'austin'));

        try {
            $api = app(AutinApiClient::class);
            $res = $api->index('centros-costo', ['per_page' => 200, 'page' => 1], $empresa);
            $centros = ! empty($res['ok']) ? $this->normalizarCentros($res['body']['data'] ?? []) : [];

            return response()->json([
                'ok' => ! empty($res['ok']),
                'centros' => $centros,
                'mensaje' => $res['message'] ?? null,
            ]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'centros' => [], 'mensaje' => $e->getMessage()], 200);
        }
    }

    public function cuentasSap(Request $request): JsonResponse
    {
        $empresa = strtolower((string) $request->get('empresa', 'austin'));
        $todas = $request->boolean('todas');
        $groupMask = trim((string) $request->get('group_mask', $request->get('GroupMask', '')));

        try {
            $cargadas = $this->cargarCuentasEmpresa($empresa, $todas, $groupMask);

            return response()->json([
                'ok' => $cargadas['ok'],
                'cuentas' => $cargadas['cuentas'],
                'agrupaciones' => $cargadas['agrupaciones'],
                'mensaje' => $cargadas['mensaje'],
            ]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'cuentas' => [], 'agrupaciones' => [], 'mensaje' => $e->getMessage()], 200);
        }
    }

    public function listGrupos(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tbl_cc_grupos_cuenta')) {
            return response()->json(['grupos' => []]);
        }

        $empresa = strtolower(trim((string) $request->get('empresa', '')));
        $q = CcGrupoCuenta::query()->with('cuentas')->orderBy('clave');
        if ($empresa !== '') {
            $q->where('empresa', $empresa);
        }

        $rows = $q->get()->map(function (CcGrupoCuenta $g) {
            return $this->grupoPayload($g);
        })->values()->all();

        return response()->json(['grupos' => $rows]);
    }

    public function storeGrupo(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tbl_cc_grupos_cuenta')) {
            return response()->json(['message' => 'Falta ejecutar la migración de grupos de cuentas.'], 422);
        }

        $data = $request->validate([
            'empresa' => 'required|string|max:40',
            'clave' => 'required|string|max:40',
            'nombre' => 'required|string|max:180',
            'cuentas' => 'array',
            'cuentas.*.codigo' => 'required|string|max:40',
            'cuentas.*.nombre' => 'nullable|string|max:180',
        ]);

        $empresa = strtolower(trim($data['empresa']));
        $clave = strtoupper(trim($data['clave']));
        $empresasOk = ['austin', 'imsa', 'pitic', 'sydney'];
        if (! in_array($empresa, $empresasOk, true)) {
            return response()->json(['message' => 'Empresa no válida.'], 422);
        }

        $existe = CcGrupoCuenta::query()
            ->where('empresa', $empresa)
            ->where('clave', $clave)
            ->exists();
        if ($existe) {
            return response()->json(['message' => 'Ya existe un grupo con esa clave en esta empresa.'], 422);
        }

        $grupo = CcGrupoCuenta::query()->create([
            'empresa' => $empresa,
            'clave' => $clave,
            'nombre' => trim($data['nombre']),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);
        $this->syncGrupoCuentas($grupo, $data['cuentas'] ?? []);
        $grupo->load('cuentas');

        return response()->json(['ok' => true, 'grupo' => $this->grupoPayload($grupo)]);
    }

    public function updateGrupo(Request $request, int $id): JsonResponse
    {
        $grupo = CcGrupoCuenta::query()->with('cuentas')->find($id);
        if (! $grupo) {
            return response()->json(['message' => 'Grupo no encontrado.'], 404);
        }

        $data = $request->validate([
            'clave' => 'sometimes|required|string|max:40',
            'nombre' => 'sometimes|required|string|max:180',
            'cuentas' => 'sometimes|array',
            'cuentas.*.codigo' => 'required_with:cuentas|string|max:40',
            'cuentas.*.nombre' => 'nullable|string|max:180',
        ]);

        if (isset($data['clave'])) {
            $clave = strtoupper(trim($data['clave']));
            $dup = CcGrupoCuenta::query()
                ->where('empresa', $grupo->empresa)
                ->where('clave', $clave)
                ->where('id', '!=', $grupo->id)
                ->exists();
            if ($dup) {
                return response()->json(['message' => 'Ya existe un grupo con esa clave en esta empresa.'], 422);
            }
            $grupo->clave = $clave;
        }
        if (isset($data['nombre'])) {
            $grupo->nombre = trim($data['nombre']);
        }
        $grupo->updated_by = auth()->id();
        $grupo->save();

        if (array_key_exists('cuentas', $data)) {
            $this->syncGrupoCuentas($grupo, $data['cuentas']);
        }
        $grupo->load('cuentas');

        return response()->json(['ok' => true, 'grupo' => $this->grupoPayload($grupo)]);
    }

    public function destroyGrupo(int $id): JsonResponse
    {
        $grupo = CcGrupoCuenta::query()->find($id);
        if (! $grupo) {
            return response()->json(['message' => 'Grupo no encontrado.'], 404);
        }

        DB::transaction(function () use ($grupo) {
            CcGrupoCuentaItem::query()->where('grupo_id', $grupo->id)->delete();
            $grupo->delete();
        });

        return response()->json(['ok' => true]);
    }

    public function listAsignaciones(string $ciclo): JsonResponse
    {
        if (! Schema::hasTable('tbl_cc_asignaciones')) {
            return response()->json(['asignaciones' => []]);
        }

        $empresa = request('empresa');
        $userId = (int) request('user_id', 0);
        $q = CcAsignacion::query()->with(['usuario', 'cuentas', 'permisos.tipo'])
            ->where('ciclo_codigo', $ciclo);
        if ($empresa) {
            $q->where('empresa', strtolower($empresa));
        }
        if ($userId > 0) {
            $q->where('user_id', $userId);
        }

        $rows = $q->orderByDesc('id')->get()->map(function (CcAsignacion $a) {
            return $this->asignacionPayload($a);
        })->values()->all();

        return response()->json(['asignaciones' => $rows]);
    }

    public function storeAsignacion(Request $request, string $ciclo): JsonResponse
    {
        if (! Schema::hasTable('tbl_cc_asignaciones')) {
            return response()->json(['message' => 'Falta ejecutar migraciones de asignaciones.'], 422);
        }

        $data = $request->validate([
            'empresa' => 'required|string|max:40',
            'user_id' => 'required|integer|exists:users,id',
            'centro_codigo' => 'required|string|max:40',
            'centro_nombre' => 'nullable|string|max:180',
            'cuentas' => 'array',
            'cuentas.*.codigo' => 'required|string|max:40',
            'cuentas.*.nombre' => 'nullable|string|max:180',
            'cuentas.*.agrupacion' => 'nullable|string|max:80',
            'permisos' => 'array',
            'permisos.*' => 'string',
        ]);

        $empresa = strtolower($data['empresa']);
        $asig = CcAsignacion::query()->firstOrNew([
            'ciclo_codigo' => $ciclo,
            'empresa' => $empresa,
            'user_id' => $data['user_id'],
            'centro_codigo' => $data['centro_codigo'],
        ]);
        $asig->centro_nombre = $data['centro_nombre'] ?? $asig->centro_nombre;
        $nuevo = ! $asig->exists;
        if ($nuevo) {
            $asig->created_by = auth()->id();
            if ($this->asigHasRolColumns()) {
                $principal = $this->buscarPrincipal($ciclo, $empresa, $data['centro_codigo']);
                $asig->es_principal = $principal ? false : true;
                $asig->parent_id = $principal ? $principal->id : null;
            }
        }
        $asig->save();

        $this->syncCuentas($asig, $data['cuentas'] ?? []);
        $this->syncPermisosClaves($asig, $data['permisos'] ?? ['capturar']);

        $asig->load(['usuario', 'cuentas', 'permisos.tipo']);

        return response()->json(['ok' => true, 'asignacion' => $this->asignacionPayload($asig)]);
    }

    public function updateAsignacion(Request $request, string $ciclo, int $id): JsonResponse
    {
        $asig = CcAsignacion::query()->with(['usuario', 'cuentas', 'permisos.tipo'])
            ->where('ciclo_codigo', $ciclo)
            ->where('id', $id)
            ->first();
        if (! $asig) {
            return response()->json(['message' => 'Asignación no encontrada'], 404);
        }

        $data = $request->validate([
            'cuentas' => 'sometimes|array',
            'cuentas.*.codigo' => 'required_with:cuentas|string|max:40',
            'cuentas.*.nombre' => 'nullable|string|max:180',
            'cuentas.*.agrupacion' => 'nullable|string|max:80',
            'permisos' => 'sometimes|array',
            'permisos.*' => 'string',
            'accesos' => 'sometimes|array',
            'accesos.*.user_id' => 'required|integer|exists:users,id',
            'accesos.*.permisos' => 'array',
            'accesos.*.permisos.*' => 'string',
        ]);

        DB::transaction(function () use ($asig, $data) {
            if (array_key_exists('cuentas', $data)) {
                $this->syncCuentas($asig, $data['cuentas']);
                $this->propagarCuentasAColaboradores($asig);
            }
            if (array_key_exists('permisos', $data)) {
                $this->syncPermisosClaves($asig, $data['permisos'] ?: ['revisar']);
            }
            if (array_key_exists('accesos', $data)) {
                $this->syncAccesos($asig, $data['accesos']);
            }
        });

        $asig->load(['usuario', 'cuentas', 'permisos.tipo']);

        return response()->json(['ok' => true, 'asignacion' => $this->asignacionPayload($asig)]);
    }

    public function destroyAsignacion(string $ciclo, int $id): JsonResponse
    {
        $asig = CcAsignacion::query()->where('ciclo_codigo', $ciclo)->where('id', $id)->first();
        if (! $asig) {
            return response()->json(['message' => 'Asignación no encontrada'], 404);
        }

        DB::transaction(function () use ($asig) {
            if ($this->asigHasRolColumns() && $asig->es_principal) {
                $extras = CcAsignacion::query()
                    ->where('ciclo_codigo', $asig->ciclo_codigo)
                    ->where('empresa', $asig->empresa)
                    ->where('centro_codigo', $asig->centro_codigo)
                    ->where('id', '!=', $asig->id)
                    ->get();
                foreach ($extras as $extra) {
                    $this->borrarAsignacion($extra);
                }
            }
            $this->borrarAsignacion($asig);
        });

        return response()->json(['ok' => true]);
    }

    public function misAsignaciones(Request $request): JsonResponse
    {
        return response()->json([
            'asignaciones' => $this->misAsignacionesList((string) $request->get('ciclo', '')),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function misAsignacionesList(string $ciclo = ''): array
    {
        if (! Schema::hasTable('tbl_cc_asignaciones')) {
            return [];
        }

        $q = CcAsignacion::query()->with(['usuario', 'cuentas', 'permisos.tipo'])
            ->where('user_id', auth()->id());
        if ($ciclo !== '') {
            $q->where('ciclo_codigo', $ciclo);
        }

        return $q->orderBy('ciclo_codigo')->orderBy('empresa')->orderBy('centro_codigo')
            ->get()
            ->map(function (CcAsignacion $a) {
                return $this->asignacionPayload($a);
            })->values()->all();
    }

    public function catalogo(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 150);
        $empresa = $request->get('empresa');

        try {
            $api = app(AutinApiClient::class);
        } catch (Throwable $e) {
            return response()->json([
                'centros' => [],
                'cuentas' => [],
                'agrupaciones' => [],
                'empresas' => [],
                'sapOk' => false,
                'sapMensaje' => $e->getMessage(),
            ]);
        }

        return response()->json($this->cargarCatalogo($api, $perPage, $empresa));
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    protected function page(string $ccPage, string $view, array $extra = [])
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $bootstrap = array_merge($this->bootstrap(), $extra);

        return view($view, compact('varpantallas', 'varsubmenus', 'ccPage', 'bootstrap'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function bootstrap(): array
    {
        $usuarios = User::query()
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->limit(800)
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'nombre' => $u->name,
                    'email' => $u->email,
                ];
            })
            ->values()
            ->all();

        $empresasLocales = [];
        if (Schema::hasTable('tblempresas')) {
            $empresasLocales = Empresas::query()
                ->when(Schema::hasColumn('tblempresas', 'estado'), function ($q) {
                    $q->where('estado', 'A');
                })
                ->orderBy('nombre_empresa')
                ->limit(20)
                ->get(['id', 'nombre_empresa'])
                ->map(function ($e) {
                    return [
                        'id' => (string) $e->id,
                        'nombre' => $e->nombre_empresa,
                    ];
                })
                ->values()
                ->all();
        }

        return [
            'usuarioActual' => auth()->user()->name ?? '',
            'usuarioActualId' => auth()->id(),
            'anioGasto' => 2026,
            'anioPresupuesto' => 2027,
            'sapBase' => url('/Sistemas/AutinApi'),
            'catalogoUrl' => route('centros.catalogo'),
            'sapOk' => false,
            'sapMensaje' => null,
            'empresasSap' => [],
            'centros' => [],
            'cuentas' => [],
            'agrupaciones' => [],
            'usuarios' => $usuarios,
            'empresasLocales' => $empresasLocales,
            'ciclos' => $this->ciclosPayload(),
            'periodoDefault' => [
                'codigo' => 'BGT-2027',
                'nombre' => 'Presupuesto 2027',
                'anioReferencia' => 2026,
                'anio' => 2027,
                'inicio' => '2026-10-01',
                'fin' => '2026-10-31',
                'capturaHasta' => '2026-10-31',
                'revisionDesde' => '2026-11-01',
                'estado' => 'abierto',
                'inflacion' => 4.0,
                'tipoCambio' => 20.0,
                'observaciones' => '',
                'fuente' => 'Informe Banxico',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function cargarCatalogo(AutinApiClient $api, int $perPage = 80, ?string $empresa = null): array
    {
        $centros = [];
        $cuentas = [];
        $agrupaciones = [];
        $empresas = [];
        $sapOk = false;
        $sapMensaje = null;

        try {
            $filtrosCentro = ['per_page' => $perPage, 'page' => 1];
            $filtrosCuenta = ['per_page' => min($perPage, 120), 'page' => 1];
            if ($empresa) {
                $filtrosCentro['Empresa'] = strtoupper($empresa);
                $filtrosCuenta['Empresa'] = strtoupper($empresa);
            }

            $resCentros = $api->centrosCostoGlobal($filtrosCentro);
            $resCuentas = $api->cuentasGlobal($filtrosCuenta);

            if (! empty($resCentros['ok'])) {
                $sapOk = true;
                $centros = $this->normalizarCentros($resCentros['body']['data'] ?? []);
            } else {
                $sapMensaje = $resCentros['message'] ?? 'Sin conexión a catálogo SAP';
            }

            if (! empty($resCuentas['ok'])) {
                $sapOk = true;
                $cuentas = $this->normalizarCuentas($resCuentas['body']['data'] ?? []);
            } elseif (! $sapMensaje) {
                $sapMensaje = $resCuentas['message'] ?? null;
            }

            $empresas = collect(array_merge(
                array_column($centros, 'empresa'),
                array_column($cuentas, 'empresa')
            ))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $db = strtolower((string) ($empresa ?: $api->defaultDatabase()));
            $resGrupos = $api->index('agrupaciones-cuentas', ['per_page' => 50], $db);
            if (! empty($resGrupos['ok'])) {
                $agrupaciones = $this->normalizarAgrupaciones($resGrupos['body']['data'] ?? []);
            }
        } catch (Throwable $e) {
            $sapMensaje = $e->getMessage();
        }

        return compact('centros', 'cuentas', 'agrupaciones', 'empresas', 'sapOk', 'sapMensaje');
    }

    /**
     * @return array{ok: bool, cuentas: array<int, array<string, mixed>>, agrupaciones: array<int, array<string, mixed>>, mensaje: string|null}
     */
    protected function cargarCuentasEmpresa(string $empresa, bool $todas = false, string $groupMask = ''): array
    {
        $api = app(AutinApiClient::class);
        $cuentas = [];
        $ok = false;
        $mensaje = null;
        $maxPages = $todas ? 40 : 1;
        $perPage = 200;

        for ($page = 1; $page <= $maxPages; $page++) {
            $filtros = ['per_page' => $perPage, 'page' => $page];
            if ($groupMask !== '') {
                $filtros['GroupMask'] = $groupMask;
            }
            $res = $api->index('cuentas', $filtros, $empresa);
            if (empty($res['ok'])) {
                if ($page === 1) {
                    $mensaje = $res['message'] ?? 'Sin conexión a catálogo SAP';
                }
                break;
            }
            $ok = true;
            $body = is_array($res['body'] ?? null) ? $res['body'] : [];
            $batch = $this->normalizarCuentas($body['data'] ?? []);
            if (! $batch) {
                break;
            }
            $cuentas = array_merge($cuentas, $batch);
            $pag = $this->paginacionDe($body);
            $lastPage = $pag['last_page'];
            if ($lastPage > 0 && $page >= $lastPage) {
                break;
            }
            if ($lastPage < 1 && count($batch) < $perPage) {
                break;
            }
        }

        $agrupaciones = $this->cargarAgrupacionesEmpresa($api, $empresa);
        $cuentas = $this->resolverNombresGrupoCuentas($cuentas, $agrupaciones);
        if ($groupMask !== '') {
            foreach ($cuentas as &$cta) {
                if (trim((string) ($cta['grupo_id'] ?? '')) === '') {
                    $cta['grupo_id'] = $groupMask;
                }
            }
            unset($cta);
        }

        return [
            'ok' => $ok,
            'cuentas' => $cuentas,
            'agrupaciones' => $agrupaciones,
            'mensaje' => $mensaje,
        ];
    }

    /**
     * @return array{last_page: int, total: int, per_page: int, current_page: int}
     */
    protected function paginacionDe(array $body): array
    {
        $meta = is_array($body['meta'] ?? null) ? $body['meta'] : [];

        return [
            'last_page' => (int) ($meta['last_page'] ?? $body['last_page'] ?? $meta['lastPage'] ?? 0),
            'total' => (int) ($meta['total'] ?? $body['total'] ?? 0),
            'per_page' => (int) ($meta['per_page'] ?? $body['per_page'] ?? 0),
            'current_page' => (int) ($meta['current_page'] ?? $body['current_page'] ?? 0),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function cargarAgrupacionesEmpresa(AutinApiClient $api, string $empresa): array
    {
        $rows = [];
        for ($page = 1; $page <= 5; $page++) {
            $res = $api->index('agrupaciones-cuentas', ['per_page' => 100, 'page' => $page], $empresa);
            if (empty($res['ok'])) {
                break;
            }
            $body = is_array($res['body'] ?? null) ? $res['body'] : [];
            $batch = $this->normalizarAgrupaciones($body['data'] ?? []);
            if (! $batch) {
                break;
            }
            $rows = array_merge($rows, $batch);
            $pag = $this->paginacionDe($body);
            if ($pag['last_page'] > 0 && $page >= $pag['last_page']) {
                break;
            }
            if ($pag['last_page'] < 1 && count($batch) < 100) {
                break;
            }
        }

        return $this->completarAgrupaciones($rows);
    }

    /**
     * @param  array<int, array<string, mixed>>  $agrupaciones
     * @return array<int, array<string, mixed>>
     */
    protected function completarAgrupaciones(array $agrupaciones): array
    {
        $clases = [
            '1' => 'Activo',
            '2' => 'Pasivo',
            '3' => 'Capital',
            '4' => 'Ingresos',
            '5' => 'Costo de ventas',
            '6' => 'Gastos',
            '7' => 'Otros ingresos y gastos',
            '8' => 'Otros',
            '9' => 'GroupMask 9',
            '10' => 'GroupMask 10',
        ];
        $byId = [];
        foreach ($agrupaciones as $g) {
            $id = trim((string) ($g['id'] ?? ''));
            if ($id === '') {
                continue;
            }
            $nom = trim((string) ($g['nombre'] ?? ''));
            $byId[$id] = [
                'id' => $id,
                'nombre' => ($nom !== '' && $nom !== $id) ? $nom : ($clases[$id] ?? $id),
            ];
        }
        for ($i = 1; $i <= 10; $i++) {
            $id = (string) $i;
            if (! isset($byId[$id])) {
                $byId[$id] = ['id' => $id, 'nombre' => $clases[$id]];
            }
        }
        uksort($byId, function ($a, $b) {
            $na = is_numeric($a) ? (int) $a : PHP_INT_MAX;
            $nb = is_numeric($b) ? (int) $b : PHP_INT_MAX;
            if ($na !== $nb && ($na < PHP_INT_MAX || $nb < PHP_INT_MAX)) {
                return $na <=> $nb;
            }

            return strcasecmp((string) $a, (string) $b);
        });

        return array_values($byId);
    }

    /**
     * @param  array<int, array<string, mixed>>  $cuentas
     */
    protected function syncGrupoCuentas(CcGrupoCuenta $grupo, array $cuentas): void
    {
        CcGrupoCuentaItem::query()->where('grupo_id', $grupo->id)->delete();
        $seen = [];
        foreach ($cuentas as $cta) {
            $codigo = trim((string) ($cta['codigo'] ?? $cta['cuenta_codigo'] ?? ''));
            if ($codigo === '' || isset($seen[$codigo])) {
                continue;
            }
            $seen[$codigo] = true;
            CcGrupoCuentaItem::query()->create([
                'grupo_id' => $grupo->id,
                'cuenta_codigo' => $codigo,
                'cuenta_nombre' => $cta['nombre'] ?? $cta['cuenta_nombre'] ?? null,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function grupoPayload(CcGrupoCuenta $g): array
    {
        return [
            'id' => $g->id,
            'empresa' => $g->empresa,
            'clave' => $g->clave,
            'nombre' => $g->nombre,
            'cuentas' => $g->cuentas->map(function (CcGrupoCuentaItem $c) {
                return [
                    'codigo' => $c->cuenta_codigo,
                    'nombre' => $c->cuenta_nombre,
                ];
            })->values()->all(),
        ];
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return array<int, array<string, mixed>>
     */
    protected function normalizarCentros(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $codigo = (string) ($row['PrcCode'] ?? $row['CC'] ?? $row['OcrCode'] ?? $row['codigo'] ?? '');
            $nombre = (string) ($row['PrcName'] ?? $row['NOMBRE'] ?? $row['nombre'] ?? '');
            if ($codigo === '' && $nombre === '') {
                continue;
            }
            $out[] = [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'empresa' => (string) ($row['Empresa'] ?? $row['empresa'] ?? $row['DB'] ?? ''),
                'activo' => ! in_array(strtoupper((string) ($row['ESTATUS'] ?? $row['Active'] ?? 'Y')), ['N', '0', 'I', 'INACTIVE'], true),
                'departamento' => (string) ($row['DimCode'] ?? $row['departamento'] ?? $row['GroupName'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return array<int, array<string, mixed>>
     */
    protected function normalizarCuentas(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $codigo = (string) ($row['FormatCode'] ?? $row['CUENTA'] ?? $row['AcctCode'] ?? $row['codigo'] ?? '');
            $nombre = (string) ($row['AcctName'] ?? $row['NOMBRE'] ?? $row['nombre'] ?? '');
            if ($codigo === '' && $nombre === '') {
                continue;
            }
            $out[] = [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'empresa' => (string) ($row['Empresa'] ?? $row['empresa'] ?? ''),
                'grupo' => (string) ($row['GroupName'] ?? $row['agrupacion'] ?? $row['grupo'] ?? ''),
                'grupo_id' => (string) ($row['GroupMask'] ?? $row['grupo_id'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @param  array<int, array<string, mixed>>  $cuentas
     * @param  array<int, array<string, mixed>>  $agrupaciones
     * @return array<int, array<string, mixed>>
     */
    protected function resolverNombresGrupoCuentas(array $cuentas, array $agrupaciones): array
    {
        $porId = [];
        foreach ($agrupaciones as $g) {
            $id = (string) ($g['id'] ?? '');
            $nom = trim((string) ($g['nombre'] ?? ''));
            if ($id !== '' && $nom !== '' && $nom !== $id) {
                $porId[$id] = $nom;
            }
        }

        $clasesSap = [
            '1' => 'Activo',
            '2' => 'Pasivo',
            '3' => 'Capital',
            '4' => 'Ingresos',
            '5' => 'Costo de ventas',
            '6' => 'Gastos',
            '7' => 'Otros ingresos y gastos',
            '8' => 'Otros',
            '9' => 'GroupMask 9',
            '10' => 'GroupMask 10',
        ];

        foreach ($cuentas as &$c) {
            $nombre = trim((string) ($c['grupo'] ?? ''));
            $id = trim((string) ($c['grupo_id'] ?? ''));
            if ($nombre === '' || ctype_digit($nombre)) {
                if ($id !== '' && isset($porId[$id])) {
                    $nombre = $porId[$id];
                } elseif (isset($porId[$nombre])) {
                    $nombre = $porId[$nombre];
                } elseif (isset($clasesSap[$nombre])) {
                    $nombre = $clasesSap[$nombre];
                } elseif (isset($clasesSap[$id])) {
                    $nombre = $clasesSap[$id];
                } else {
                    $nombre = '';
                }
            }
            $c['grupo'] = $nombre;
        }
        unset($c);

        return $cuentas;
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return array<int, array<string, mixed>>
     */
    protected function normalizarAgrupaciones(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $out[] = [
                'id' => (string) ($row['GroupMask'] ?? $row['id'] ?? $row['codigo'] ?? ''),
                'nombre' => (string) ($row['GroupName'] ?? $row['NOMBRE'] ?? $row['nombre'] ?? $row['GroupMask'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function permisosCatalogo(): array
    {
        if (! Schema::hasTable('tbl_cc_tipos_permiso')) {
            return [
                ['clave' => 'capturar', 'nombre' => 'Capturar', 'descripcion' => 'Puede capturar presupuesto mensual'],
                ['clave' => 'editar', 'nombre' => 'Editar', 'descripcion' => 'Puede modificar montos ya capturados'],
                ['clave' => 'revisar', 'nombre' => 'Revisar', 'descripcion' => 'Puede consultar y revisar sin editar'],
            ];
        }

        return CcTipoPermiso::query()->orderBy('orden')->get(['id', 'clave', 'nombre', 'descripcion'])->toArray();
    }

    protected function asigHasRolColumns(): bool
    {
        static $has = null;
        if ($has === null) {
            $has = Schema::hasTable('tbl_cc_asignaciones')
                && Schema::hasColumn('tbl_cc_asignaciones', 'es_principal');
        }

        return $has;
    }

    protected function buscarPrincipal(string $ciclo, string $empresa, string $centro): ?CcAsignacion
    {
        $q = CcAsignacion::query()
            ->where('ciclo_codigo', $ciclo)
            ->where('empresa', $empresa)
            ->where('centro_codigo', $centro);
        if ($this->asigHasRolColumns()) {
            $q->where('es_principal', true);
        }

        return $q->orderBy('id')->first();
    }

    protected function principalDeAsignacion(CcAsignacion $asig): CcAsignacion
    {
        if ($this->asigHasRolColumns()) {
            if ($asig->es_principal) {
                return $asig;
            }
            if ($asig->parent_id) {
                $p = CcAsignacion::query()->find($asig->parent_id);
                if ($p) {
                    return $p;
                }
            }
        }
        $found = $this->buscarPrincipal($asig->ciclo_codigo, $asig->empresa, $asig->centro_codigo);

        return $found ?: $asig;
    }

    /**
     * @param  array<int, array<string, mixed>>  $cuentas
     */
    protected function syncCuentas(CcAsignacion $asig, array $cuentas): void
    {
        CcAsignacionCuenta::query()->where('asignacion_id', $asig->id)->delete();
        foreach ($cuentas as $cta) {
            CcAsignacionCuenta::query()->create([
                'asignacion_id' => $asig->id,
                'cuenta_codigo' => $cta['codigo'] ?? $cta['cuenta_codigo'] ?? '',
                'cuenta_nombre' => $cta['nombre'] ?? $cta['cuenta_nombre'] ?? null,
                'agrupacion' => $cta['agrupacion'] ?? null,
            ]);
        }
    }

    /**
     * @param  array<int, string>  $claves
     */
    protected function syncPermisosClaves(CcAsignacion $asig, array $claves): void
    {
        CcAsignacionPermiso::query()->where('asignacion_id', $asig->id)->delete();
        if (! Schema::hasTable('tbl_cc_tipos_permiso')) {
            return;
        }
        $tipos = CcTipoPermiso::query()->whereIn('clave', $claves)->get();
        foreach ($tipos as $tipo) {
            CcAsignacionPermiso::query()->create([
                'asignacion_id' => $asig->id,
                'permiso_id' => $tipo->id,
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function cuentasDe(CcAsignacion $asig): array
    {
        return $asig->cuentas()->get()->map(function ($c) {
            return [
                'codigo' => $c->cuenta_codigo,
                'nombre' => $c->cuenta_nombre,
                'agrupacion' => $c->agrupacion,
            ];
        })->values()->all();
    }

    protected function propagarCuentasAColaboradores(CcAsignacion $asig): void
    {
        $principal = $this->principalDeAsignacion($asig);
        if ((int) $principal->id !== (int) $asig->id && ! ($this->asigHasRolColumns() && $asig->es_principal)) {
            return;
        }
        $cuentas = $this->cuentasDe($asig);
        $extras = CcAsignacion::query()
            ->where('ciclo_codigo', $asig->ciclo_codigo)
            ->where('empresa', $asig->empresa)
            ->where('centro_codigo', $asig->centro_codigo)
            ->where('id', '!=', $asig->id)
            ->get();
        foreach ($extras as $extra) {
            $this->syncCuentas($extra, $cuentas);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $accesos
     */
    protected function syncAccesos(CcAsignacion $asig, array $accesos): void
    {
        $principal = $this->principalDeAsignacion($asig);
        $keepIds = [(int) $principal->user_id];
        $cuentas = $this->cuentasDe($principal);

        foreach ($accesos as $acc) {
            $uid = (int) ($acc['user_id'] ?? 0);
            if ($uid <= 0 || $uid === (int) $principal->user_id) {
                continue;
            }
            $keepIds[] = $uid;
            $col = CcAsignacion::query()->firstOrNew([
                'ciclo_codigo' => $principal->ciclo_codigo,
                'empresa' => $principal->empresa,
                'user_id' => $uid,
                'centro_codigo' => $principal->centro_codigo,
            ]);
            $col->centro_nombre = $principal->centro_nombre;
            if (! $col->exists) {
                $col->created_by = auth()->id();
            }
            if ($this->asigHasRolColumns()) {
                $col->es_principal = false;
                $col->parent_id = $principal->id;
            }
            $col->save();
            $this->syncCuentas($col, $cuentas);
            $this->syncPermisosClaves($col, $acc['permisos'] ?? ['revisar']);
        }

        $extras = CcAsignacion::query()
            ->where('ciclo_codigo', $principal->ciclo_codigo)
            ->where('empresa', $principal->empresa)
            ->where('centro_codigo', $principal->centro_codigo)
            ->whereNotIn('user_id', $keepIds)
            ->get();
        foreach ($extras as $extra) {
            $this->borrarAsignacion($extra);
        }
    }

    protected function borrarAsignacion(CcAsignacion $asig): void
    {
        CcAsignacionCuenta::query()->where('asignacion_id', $asig->id)->delete();
        CcAsignacionPermiso::query()->where('asignacion_id', $asig->id)->delete();
        $asig->delete();
    }

    /**
     * @return array<string, mixed>
     */
    protected function asignacionPayload(CcAsignacion $a): array
    {
        $permisos = $a->permisos->map(function ($p) {
            return $p->tipo->clave ?? null;
        })->filter()->values()->all();

        $esPrincipal = $this->asigHasRolColumns() ? (bool) $a->es_principal : true;

        return [
            'id' => $a->id,
            'ciclo' => $a->ciclo_codigo,
            'empresa' => $a->empresa,
            'user_id' => $a->user_id,
            'usuario' => $a->usuario->name ?? '',
            'email' => $a->usuario->email ?? '',
            'centro_codigo' => $a->centro_codigo,
            'centro_nombre' => $a->centro_nombre,
            'es_principal' => $esPrincipal,
            'parent_id' => $this->asigHasRolColumns() ? $a->parent_id : null,
            'cuentas' => $a->cuentas->map(function ($c) {
                return [
                    'codigo' => $c->cuenta_codigo,
                    'nombre' => $c->cuenta_nombre,
                    'agrupacion' => $c->agrupacion,
                ];
            })->values()->all(),
            'permisos' => $permisos,
            'capturar' => in_array('capturar', $permisos, true),
            'editar' => in_array('editar', $permisos, true),
            'revisar' => in_array('revisar', $permisos, true),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function ciclosPayload(): array
    {
        if (! Schema::hasTable('tbl_cc_ciclos')) {
            return [];
        }

        $statsMap = $this->cicloStatsByCodigo();

        return CcCiclo::query()->orderByDesc('anio_presupuesto')->orderByDesc('id')->get()
            ->map(function (CcCiclo $c) use ($statsMap) {
                return $this->cicloPayload($c, $statsMap[$c->codigo] ?? $this->emptyCicloStats());
            })->values()->all();
    }

    /**
     * @param  array<string, int>|null  $stats
     * @return array<string, mixed>
     */
    protected function cicloPayload(CcCiclo $c, ?array $stats = null): array
    {
        $stats = $stats ?: $this->cicloStats($c->codigo);

        return [
            'id' => $c->id,
            'codigo' => $c->codigo,
            'nombre' => $c->nombre,
            'anioReferencia' => (int) $c->anio_referencia,
            'anio' => (int) $c->anio_presupuesto,
            'inicio' => $c->fecha_inicio ? substr((string) $c->fecha_inicio, 0, 10) : '',
            'fin' => $c->fecha_fin ? substr((string) $c->fecha_fin, 0, 10) : '',
            'capturaHasta' => $c->captura_hasta ? substr((string) $c->captura_hasta, 0, 10) : '',
            'revisionDesde' => $c->revision_desde ? substr((string) $c->revision_desde, 0, 10) : '',
            'estado' => $c->estado,
            'inflacion' => (float) $c->inflacion,
            'tipoCambio' => (float) $c->tipo_cambio,
            'observaciones' => $c->observaciones,
            'asignaciones' => (int) ($stats['asignaciones'] ?? 0),
            'cuentas' => (int) ($stats['cuentas'] ?? 0),
            'usuarios' => (int) ($stats['usuarios'] ?? 0),
            'centros' => (int) ($stats['centros'] ?? 0),
        ];
    }

    /**
     * @return array<string, int>
     */
    protected function emptyCicloStats(): array
    {
        return [
            'asignaciones' => 0,
            'cuentas' => 0,
            'usuarios' => 0,
            'centros' => 0,
        ];
    }

    /**
     * @return array<string, int>
     */
    protected function cicloStats(string $codigo): array
    {
        $map = $this->cicloStatsByCodigo([$codigo]);

        return $map[$codigo] ?? $this->emptyCicloStats();
    }

    /**
     * @param  array<int, string>|null  $codigos
     * @return array<string, array<string, int>>
     */
    protected function cicloStatsByCodigo(?array $codigos = null): array
    {
        if (! Schema::hasTable('tbl_cc_asignaciones')) {
            return [];
        }

        $q = CcAsignacion::query()->select(['id', 'ciclo_codigo', 'user_id', 'empresa', 'centro_codigo']);
        if ($codigos !== null) {
            $q->whereIn('ciclo_codigo', $codigos);
        }
        $asigs = $q->get();
        if ($asigs->isEmpty()) {
            return [];
        }

        $ctaByAsig = [];
        if (Schema::hasTable('tbl_cc_asignacion_cuentas')) {
            $ctaByAsig = CcAsignacionCuenta::query()
                ->selectRaw('asignacion_id, count(*) as total')
                ->whereIn('asignacion_id', $asigs->pluck('id'))
                ->groupBy('asignacion_id')
                ->pluck('total', 'asignacion_id')
                ->all();
        }

        $out = [];
        foreach ($asigs->groupBy('ciclo_codigo') as $codigo => $rows) {
            $out[$codigo] = [
                'asignaciones' => $rows->count(),
                'cuentas' => (int) $rows->sum(function ($a) use ($ctaByAsig) {
                    return (int) ($ctaByAsig[$a->id] ?? 0);
                }),
                'usuarios' => $rows->pluck('user_id')->unique()->count(),
                'centros' => $rows->unique(function ($a) {
                    return strtolower((string) $a->empresa).'|'.$a->centro_codigo;
                })->count(),
            ];
        }

        return $out;
    }
}
