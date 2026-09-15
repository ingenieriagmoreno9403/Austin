<?php

namespace App\Http\Controllers;

use App\Exports\CcCapturaPlantillaExport;
use App\Models\CcAsignacion;
use App\Models\CcAsignacionCuenta;
use App\Models\CcAsignacionPermiso;
use App\Models\CcCapturaCentro;
use App\Models\CcCiclo;
use App\Models\CcGrupoCuenta;
use App\Models\CcGrupoCuentaItem;
use App\Models\CcPresupuesto;
use App\Models\CcTipoPermiso;
use App\Models\CcUsuarioPermiso;
use App\Models\Empresas;
use App\Models\User;
use App\Services\AutinApiClient;
use App\Traits\MenuTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Vistas de centros de costo / presupuesto.
 * Catálogo SAP vía AutinApi; la captura mensual se guarda en tbl_cc_presupuestos.
 */
class CentrosCostosController extends Controller
{
    use MenuTrait;

    /** @var array<string, bool> */
    protected $ccUserPermCache = [];

    /** @var array<string, string> */
    protected $ccCicloEstadoCache = [];

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
            'permisosCatalogo' => $this->permisosCatalogoAsignacion(),
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
        return $this->page('analisis', 'CentrosCostos.analisis', [
            'detalleUrl' => route('centros.detalle'),
        ]);
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
            'estado' => 'nullable|in:abierto,en_revision,cerrado,en_proceso,terminado',
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
            'estado' => $this->normalizeCicloEstado($data['estado'] ?? 'abierto'),
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
            'estado' => 'required|in:abierto,en_revision,cerrado,en_proceso,terminado',
        ]);

        $row = CcCiclo::query()->where('codigo', $ciclo)->first();
        if (! $row) {
            return response()->json(['message' => 'Ciclo no encontrado.'], 404);
        }

        $row->estado = $this->normalizeCicloEstado($data['estado']);
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
            if (Schema::hasTable('tbl_cc_presupuestos')) {
                CcPresupuesto::query()->where('ciclo_codigo', $row->codigo)->delete();
            }
            if (Schema::hasTable('tbl_cc_captura_centros')) {
                CcCapturaCentro::query()->where('ciclo_codigo', $row->codigo)->delete();
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
            'permisosCatalogo' => $this->permisosCatalogoAsignacion(),
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
        $q = CcGrupoCuenta::query()->with('cuentas')->orderBy('nombre')->orderBy('clave');
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
            'clave' => 'nullable|string|max:40',
            'nombre' => 'required|string|max:180',
            'cuentas' => 'required|array|min:1',
            'cuentas.*.codigo' => 'required|string|max:40',
            'cuentas.*.nombre' => 'nullable|string|max:180',
        ]);

        $empresa = strtolower(trim($data['empresa']));
        $empresasOk = ['austin', 'imsa', 'pitic', 'sydney'];
        if (! in_array($empresa, $empresasOk, true)) {
            return response()->json(['message' => 'Empresa no válida.'], 422);
        }

        $clave = strtoupper(trim((string) ($data['clave'] ?? '')));
        if ($clave === '') {
            $clave = $this->claveDesdeNombre($data['nombre'], $empresa);
        } else {
            $existe = CcGrupoCuenta::query()
                ->where('empresa', $empresa)
                ->where('clave', $clave)
                ->exists();
            if ($existe) {
                return response()->json(['message' => 'Ya existe un grupo con esa clave en esta empresa.'], 422);
            }
        }

        $grupo = DB::transaction(function () use ($empresa, $clave, $data) {
            $grupo = CcGrupoCuenta::query()->create([
                'empresa' => $empresa,
                'clave' => $clave,
                'nombre' => trim($data['nombre']),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
            $this->syncGrupoCuentas($grupo, $data['cuentas'] ?? []);
            $grupo->load('cuentas');

            return $grupo;
        });

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
            'cuentas' => 'sometimes|array|min:1',
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

    public function listImportarUsuarios(string $ciclo): JsonResponse
    {
        return response()->json(['usuarios' => $this->importarUsuariosPayload($ciclo)]);
    }

    public function syncImportarUsuarios(Request $request, string $ciclo): JsonResponse
    {
        $data = $request->validate([
            'user_ids' => 'array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);
        $wanted = array_values(array_unique(array_map('intval', $data['user_ids'] ?? [])));
        $current = $this->userIdsConImportar($ciclo);

        foreach ($wanted as $uid) {
            $this->syncPermisoUsuario($ciclo, $uid, 'importar', true);
        }
        foreach ($current as $uid) {
            if (! in_array($uid, $wanted, true)) {
                $this->syncPermisoUsuario($ciclo, $uid, 'importar', false);
            }
        }
        $this->ccUserPermCache = [];

        return response()->json([
            'ok' => true,
            'usuarios' => $this->importarUsuariosPayload($ciclo),
        ]);
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

    public function gastoReal(Request $request): JsonResponse
    {
        @set_time_limit(120);
        $empresa = strtoupper(trim((string) $request->get('empresa', '')));
        $cc = trim((string) $request->get('cc', $request->get('CC', '')));
        $year = (int) $request->get('year', $request->get('anio', 0));
        $nombres = $this->listaRequest($request, 'nombres');

        $alias = [
            'ABSA' => 'AUSTIN',
        ];
        if (isset($alias[$empresa])) {
            $empresa = $alias[$empresa];
        }

        if ($empresa === '') {
            return response()->json(['ok' => false, 'por_cuenta' => (object) [], 'mensaje' => 'Falta empresa.'], 422);
        }
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }

        if (! $nombres) {
            $this->completarNombresGastoDesdeAsignaciones($empresa, $cc, $nombres);
        }

        $cacheKey = 'cc.gasto-real.v6.' . $empresa . '.' . $year . '.' . md5(json_encode($nombres));
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && ! empty($cached['ok']) && ! empty($cached['por_cuenta'])) {
            return response()->json($cached);
        }

        try {
            $payload = $this->cargarGastoRealCentro($empresa, $cc, $year, $nombres);
            if (! empty($payload['ok']) && ! empty($payload['por_cuenta'])) {
                Cache::put($cacheKey, $payload, 900);
            }
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'year' => $year,
                'por_cuenta' => (object) [],
                'mensaje' => $e->getMessage(),
            ], 200);
        }

        return response()->json($payload);
    }

    public function captura(Request $request): JsonResponse
    {
        $ciclo = strtoupper(trim((string) $request->get('ciclo', '')));
        if ($ciclo === '') {
            return response()->json(['message' => 'Falta el ciclo.'], 422);
        }

        $budgets = [];
        $completados = [];
        if (Schema::hasTable('tbl_cc_presupuestos')) {
            $hasDone = Schema::hasColumn('tbl_cc_presupuestos', 'completado');
            CcPresupuesto::query()->whereRaw('UPPER(ciclo_codigo) = ?', [$ciclo])->get()->each(function (CcPresupuesto $row) use (&$budgets, &$completados, $hasDone) {
                $meses = $row->meses();
                $done = ($hasDone && ! empty($row->completado)) || $this->mesesTodosLlenos($meses);
                foreach ($this->clavesCuentaCaptura($row->empresa, $row->centro_codigo, $row->cuenta_codigo) as $key) {
                    $budgets[$key] = $meses;
                    if ($done) {
                        $completados[$key] = true;
                    }
                }
            });
        }

        $overlays = [];
        if (Schema::hasTable('tbl_cc_captura_centros')) {
            CcCapturaCentro::query()->whereRaw('UPPER(ciclo_codigo) = ?', [$ciclo])->get()->each(function (CcCapturaCentro $row) use (&$overlays) {
                $key = strtoupper(trim((string) $row->empresa)).'|'.trim((string) $row->centro_codigo);
                $overlays[$key] = [
                    'estado' => $row->estado ?: 'en_proceso',
                    'fecha' => $row->updated_at ? $row->updated_at->format('d/m/Y') : null,
                ];
            });
        }

        return response()->json([
            'ok' => true,
            'ciclo' => $ciclo,
            'budgets' => (object) $budgets,
            'completados' => (object) $completados,
            'overlays' => (object) $overlays,
        ]);
    }

    public function guardarPresupuesto(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tbl_cc_presupuestos')) {
            return response()->json(['message' => 'Falta ejecutar la migración de presupuestos.'], 422);
        }

        $data = $request->validate([
            'ciclo' => 'required|string|max:40',
            'empresa' => 'required|string|max:40',
            'centro' => 'required|string|max:40',
            'cuenta' => 'required|string|max:40',
            'cuenta_nombre' => 'nullable|string|max:180',
            'meses' => 'required|array|size:12',
            'meses.*' => 'nullable|numeric',
            'completado' => 'nullable|boolean',
        ]);

        $ciclo = strtoupper(trim($data['ciclo']));
        $empresa = strtoupper(trim($data['empresa']));
        $centro = trim($data['centro']);
        $cuenta = trim($data['cuenta']);

        $motivo = $this->motivoBloqueoCaptura($ciclo, $empresa, $centro);
        if ($motivo) {
            return response()->json(['message' => $motivo], 403);
        }

        $row = CcPresupuesto::query()->firstOrNew([
            'ciclo_codigo' => $ciclo,
            'empresa' => $empresa,
            'centro_codigo' => $centro,
            'cuenta_codigo' => $cuenta,
        ]);
        $row->setMeses($data['meses']);
        if (! empty($data['cuenta_nombre'])) {
            $row->cuenta_nombre = $data['cuenta_nombre'];
        }
        if (Schema::hasColumn('tbl_cc_presupuestos', 'completado')) {
            $filled = count(array_filter($row->meses(), function ($v) {
                return $v !== null;
            })) === 12;
            if (array_key_exists('completado', $data) && $data['completado'] !== null) {
                $row->completado = ((bool) $data['completado']) || $filled;
            } else {
                $row->completado = $filled;
            }
        }
        $row->updated_by = auth()->id();
        $row->save();

        return response()->json([
            'ok' => true,
            'key' => $this->capturaBudgetKey($empresa, $centro, $cuenta),
            'meses' => $row->meses(),
            'completado' => (bool) ($row->completado ?? false),
        ]);
    }

    public function guardarCapturaCentro(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tbl_cc_captura_centros')) {
            return response()->json(['message' => 'Falta ejecutar la migración de captura.'], 422);
        }

        $data = $request->validate([
            'ciclo' => 'required|string|max:40',
            'empresa' => 'required|string|max:40',
            'centro' => 'required|string|max:40',
            'estado' => 'required|in:abierto,en_proceso,terminado,en_revision',
        ]);

        $ciclo = strtoupper(trim($data['ciclo']));
        $empresa = strtoupper(trim($data['empresa']));
        $centro = trim($data['centro']);

        $motivo = $this->motivoBloqueoCaptura($ciclo, $empresa, $centro);
        if ($motivo) {
            return response()->json(['message' => $motivo], 403);
        }

        $row = CcCapturaCentro::query()->firstOrNew([
            'ciclo_codigo' => $ciclo,
            'empresa' => $empresa,
            'centro_codigo' => $centro,
        ]);
        $row->estado = $data['estado'];
        $row->updated_by = auth()->id();
        $row->save();

        return response()->json([
            'ok' => true,
            'estado' => $row->estado,
            'fecha' => $row->updated_at ? $row->updated_at->format('d/m/Y') : now()->format('d/m/Y'),
        ]);
    }

    protected function capturaBudgetKey(string $empresa, string $centro, string $cuenta): string
    {
        return strtoupper(trim($empresa)).'|'.trim($centro).'|'.trim($cuenta);
    }

    protected function puedeCapturarCentro(string $ciclo, string $empresa, string $centro): bool
    {
        return $this->motivoBloqueoCaptura($ciclo, $empresa, $centro) === null;
    }

    protected function motivoBloqueoCaptura(string $ciclo, string $empresa, string $centro): ?string
    {
        if (! Schema::hasTable('tbl_cc_asignaciones')) {
            return 'No tienes permiso de captura en este centro.';
        }

        $asigs = CcAsignacion::query()
            ->with('permisos.tipo')
            ->whereRaw('UPPER(ciclo_codigo) = ?', [strtoupper($ciclo)])
            ->where('user_id', auth()->id())
            ->whereRaw('UPPER(empresa) = ?', [strtoupper($empresa)])
            ->where('centro_codigo', $centro)
            ->get();

        if ($asigs->isEmpty()) {
            return 'No tienes este centro asignado.';
        }

        $estado = $this->cicloEstado($ciclo);
        $claves = [];
        foreach ($asigs as $a) {
            foreach ($a->permisos as $p) {
                $clave = $p->tipo->clave ?? null;
                if ($clave) {
                    $claves[] = $clave;
                }
            }
        }

        if ($this->asignacionPuedeEscribir($claves, $estado)) {
            return null;
        }

        if ($estado === 'abierto') {
            return 'No tienes permiso de captura en este centro.';
        }

        return 'El budget no está Abierto. Solo puedes modificar con el permiso Editar en la asignación.';
    }

    /**
     * @param  array<int, string|null>  $claves
     */
    protected function asignacionPuedeEscribir(array $claves, string $estado): bool
    {
        $estado = $this->normalizeCicloEstado($estado);
        if ($estado === 'abierto') {
            return in_array('capturar', $claves, true) || in_array('editar', $claves, true);
        }

        return in_array('editar', $claves, true);
    }

    protected function cicloEstado(string $ciclo): string
    {
        $key = strtoupper(trim($ciclo));
        if ($key === '') {
            return 'abierto';
        }
        if (! isset($this->ccCicloEstadoCache[$key])) {
            $raw = Schema::hasTable('tbl_cc_ciclos')
                ? CcCiclo::query()->whereRaw('UPPER(codigo) = ?', [$key])->value('estado')
                : 'abierto';
            $this->ccCicloEstadoCache[$key] = $this->normalizeCicloEstado($raw);
        }

        return $this->ccCicloEstadoCache[$key];
    }

    public function plantillaCaptura(Request $request)
    {
        $ciclo = strtoupper(trim((string) $request->get('ciclo', '')));
        if ($ciclo === '') {
            return response()->json(['message' => 'Falta el ciclo.'], 422);
        }
        if (! $this->puedeImportarMasivo($ciclo)) {
            return response()->json(['message' => 'No tienes permiso de importación masiva en este ciclo.'], 403);
        }
        $bloqueo = $this->motivoBloqueoPlantilla($ciclo);
        if ($bloqueo) {
            return response()->json(['message' => $bloqueo], 403);
        }

        $built = $this->filasPlantillaCaptura($ciclo);
        $filename = 'plantilla_captura_'.$ciclo.'_'.now()->format('Ymd').'.xlsx';

        return Excel::download(new CcCapturaPlantillaExport($built['headings'], $built['rows'], $built['captured'] ?? []), $filename);
    }

    public function importarCaptura(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tbl_cc_presupuestos')) {
            return response()->json(['message' => 'Falta ejecutar la migración de presupuestos.'], 422);
        }

        $data = $request->validate([
            'ciclo' => 'required|string|max:40',
            'archivo' => 'required|file|max:20480',
        ]);
        $ext = strtolower($request->file('archivo')->getClientOriginalExtension());
        if (! in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            return response()->json(['message' => 'El archivo debe ser Excel (.xlsx) o CSV.'], 422);
        }
        $ciclo = strtoupper(trim($data['ciclo']));
        if (! $this->puedeImportarMasivo($ciclo)) {
            return response()->json(['message' => 'No tienes permiso de importación masiva en este ciclo.'], 403);
        }
        $bloqueo = $this->motivoBloqueoPlantilla($ciclo);
        if ($bloqueo) {
            return response()->json(['message' => $bloqueo], 403);
        }

        try {
            $sheets = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray
            {
                public function array(array $array)
                {
                }
            }, $request->file('archivo'));
        } catch (Throwable $e) {
            return response()->json(['message' => 'No se pudo leer el archivo: '.$e->getMessage()], 422);
        }

        $sheet = $sheets[0] ?? [];
        if (count($sheet) < 2) {
            return response()->json(['message' => 'El archivo no tiene filas para importar.'], 422);
        }

        $map = $this->mapearEncabezadosCaptura($sheet[0] ?? []);
        if ($map['empresa'] === null || $map['centro'] === null || $map['cuenta'] === null) {
            return response()->json(['message' => 'Faltan columnas: empresa, centro de costo y cuenta.'], 422);
        }
        if (count($map['meses']) < 12) {
            return response()->json(['message' => 'Faltan las 12 columnas de meses del periodo.'], 422);
        }

        $permitidas = $this->mapaCuentasCaptura($ciclo);
        $guardadas = 0;
        $omitidas = 0;
        $errores = [];

        DB::transaction(function () use ($sheet, $map, $ciclo, $permitidas, &$guardadas, &$omitidas, &$errores) {
            for ($i = 1; $i < count($sheet); $i++) {
                $row = $sheet[$i];
                $fila = $i + 1;
                $empresa = strtoupper($this->celdaTexto($row[$map['empresa']] ?? ''));
                $centro = $this->celdaTexto($row[$map['centro']] ?? '');
                $cuenta = $this->celdaTexto($row[$map['cuenta']] ?? '');
                if ($empresa === '' && $centro === '' && $cuenta === '') {
                    $omitidas++;
                    continue;
                }
                if ($empresa === '' || $centro === '' || $cuenta === '') {
                    $errores[] = ['fila' => $fila, 'mensaje' => 'Empresa, centro o cuenta vacíos'];
                    continue;
                }
                $info = $this->buscarCuentaCaptura($permitidas, $empresa, $centro, $cuenta);
                if (! $info) {
                    $errores[] = ['fila' => $fila, 'mensaje' => 'No tienes esa cuenta asignada ('.$empresa.' / '.$centro.' / '.$cuenta.')'];
                    continue;
                }
                if (empty($info['capturar'])) {
                    $errores[] = ['fila' => $fila, 'mensaje' => $this->cicloEstado($ciclo) === 'abierto'
                        ? 'Sin permiso de captura en '.$empresa.' / '.$centro
                        : 'El budget no está Abierto. Se requiere permiso de Editar en '.$empresa.' / '.$centro];
                    continue;
                }

                $meses = [];
                $hayValor = false;
                foreach ($map['meses'] as $idx) {
                    $num = $this->celdaMesCaptura($row[$idx] ?? null);
                    $meses[] = $num;
                    if ($num !== null) {
                        $hayValor = true;
                    }
                }
                if (! $hayValor) {
                    $omitidas++;
                    continue;
                }
                while (count($meses) < 12) {
                    $meses[] = null;
                }
                $meses = array_slice($meses, 0, 12);

                $rec = CcPresupuesto::query()->firstOrNew([
                    'ciclo_codigo' => $ciclo,
                    'empresa' => $empresa,
                    'centro_codigo' => $centro,
                    'cuenta_codigo' => $info['cuenta'],
                ]);
                $rec->setMeses($meses);
                if (! empty($info['nombre'])) {
                    $rec->cuenta_nombre = $info['nombre'];
                }
                if (Schema::hasColumn('tbl_cc_presupuestos', 'completado')) {
                    $rec->completado = $this->mesesTodosLlenos($meses);
                }
                $rec->updated_by = auth()->id();
                $rec->save();
                $guardadas++;
            }
        });

        return response()->json([
            'ok' => true,
            'guardadas' => $guardadas,
            'omitidas' => $omitidas,
            'errores' => array_slice($errores, 0, 80),
            'errores_total' => count($errores),
        ]);
    }

    protected function puedeImportarMasivo(string $ciclo): bool
    {
        $uid = (int) auth()->id();
        if ($uid <= 0) {
            return false;
        }

        return $this->usuarioTienePermiso($ciclo, $uid, 'importar');
    }

    protected function motivoBloqueoPlantilla(string $ciclo): ?string
    {
        if ($this->cicloEstado($ciclo) === 'abierto') {
            return null;
        }
        if ($this->usuarioTienePermiso($ciclo, (int) auth()->id(), 'editar')) {
            return null;
        }

        return 'El budget no está Abierto. Para bajar o subir plantilla necesitas permiso de Editar en algún centro.';
    }

    /**
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>, captured: array<int, bool>}
     */
    protected function filasPlantillaCaptura(string $ciclo): array
    {
        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $headings = array_merge(['empresa', 'centro_de_costo', 'centro_nombre', 'cuenta', 'cuenta_nombre'], $meses);
        $rows = [];
        $captured = [];

        $asigs = CcAsignacion::query()->with(['cuentas', 'permisos.tipo'])
            ->whereRaw('UPPER(ciclo_codigo) = ?', [strtoupper($ciclo)])
            ->where('user_id', auth()->id())
            ->orderBy('empresa')
            ->orderBy('centro_codigo')
            ->get();

        $pptoMap = [];
        $hasDone = Schema::hasTable('tbl_cc_presupuestos') && Schema::hasColumn('tbl_cc_presupuestos', 'completado');
        if (Schema::hasTable('tbl_cc_presupuestos')) {
            CcPresupuesto::query()->whereRaw('UPPER(ciclo_codigo) = ?', [strtoupper($ciclo)])->get()
                ->each(function (CcPresupuesto $row) use (&$pptoMap, $hasDone) {
                    $info = [
                        'meses' => $row->meses(),
                        'completado' => $hasDone && ! empty($row->completado),
                    ];
                    foreach ($this->clavesCuentaCaptura($row->empresa, $row->centro_codigo, $row->cuenta_codigo) as $k) {
                        $pptoMap[$k] = $info;
                    }
                });
        }

        $seen = [];
        $estado = $this->cicloEstado($ciclo);
        foreach ($asigs as $a) {
            $claves = $a->permisos->map(function ($p) {
                return $p->tipo->clave ?? null;
            })->filter()->all();
            if (! $this->asignacionPuedeEscribir($claves, $estado)) {
                continue;
            }
            $empresa = strtoupper(trim((string) $a->empresa));
            $centro = trim((string) $a->centro_codigo);
            foreach ($a->cuentas as $cta) {
                $cuenta = trim((string) $cta->cuenta_codigo);
                $key = $this->capturaBudgetKey($empresa, $centro, $cuenta);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $info = null;
                foreach ($this->clavesCuentaCaptura($empresa, $centro, $cuenta) as $k) {
                    if (isset($pptoMap[$k])) {
                        $info = $pptoMap[$k];
                        break;
                    }
                }
                $vals = $info['meses'] ?? array_fill(0, 12, null);
                $vals = array_map(function ($v) {
                    return $v === null || $v === '' ? null : round((float) $v, 2);
                }, $vals);
                while (count($vals) < 12) {
                    $vals[] = null;
                }
                $vals = array_slice($vals, 0, 12);
                $rows[] = array_merge([
                    $empresa,
                    $centro,
                    (string) ($a->centro_nombre ?: ''),
                    $this->codigoCuentaPlantilla($cuenta),
                    (string) ($cta->cuenta_nombre ?: ''),
                ], $vals);
                $captured[] = ! empty($info['completado']) || $this->mesesTodosLlenos($vals);
            }
        }

        return ['headings' => $headings, 'rows' => $rows, 'captured' => $captured];
    }

    /**
     * @param  array<int, mixed>  $header
     * @return array{empresa: int|null, centro: int|null, cuenta: int|null, meses: array<int, int>}
     */
    protected function mapearEncabezadosCaptura(array $header): array
    {
        $empresa = null;
        $centro = null;
        $cuenta = null;
        $meses = [];
        $mesAlias = [
            'ene' => 0, 'enero' => 0, 'jan' => 0, 'january' => 0, 'mes_01' => 0, 'mes01' => 0, 'm01' => 0,
            'feb' => 1, 'febrero' => 1, 'february' => 1, 'mes_02' => 1, 'mes02' => 1, 'm02' => 1,
            'mar' => 2, 'marzo' => 2, 'march' => 2, 'mes_03' => 2, 'mes03' => 2, 'm03' => 2,
            'abr' => 3, 'abril' => 3, 'apr' => 3, 'april' => 3, 'mes_04' => 3, 'mes04' => 3, 'm04' => 3,
            'may' => 4, 'mayo' => 4, 'mes_05' => 4, 'mes05' => 4, 'm05' => 4,
            'jun' => 5, 'junio' => 5, 'june' => 5, 'mes_06' => 5, 'mes06' => 5, 'm06' => 5,
            'jul' => 6, 'julio' => 6, 'july' => 6, 'mes_07' => 6, 'mes07' => 6, 'm07' => 6,
            'ago' => 7, 'agosto' => 7, 'aug' => 7, 'august' => 7, 'mes_08' => 7, 'mes08' => 7, 'm08' => 7,
            'sep' => 8, 'septiembre' => 8, 'sept' => 8, 'september' => 8, 'mes_09' => 8, 'mes09' => 8, 'm09' => 8,
            'oct' => 9, 'octubre' => 9, 'october' => 9, 'mes_10' => 9, 'mes10' => 9, 'm10' => 9,
            'nov' => 10, 'noviembre' => 10, 'november' => 10, 'mes_11' => 10, 'mes11' => 10, 'm11' => 10,
            'dic' => 11, 'diciembre' => 11, 'dec' => 11, 'december' => 11, 'mes_12' => 11, 'mes12' => 11, 'm12' => 11,
        ];

        foreach ($header as $idx => $raw) {
            $norm = $this->normHeader($raw);
            if ($norm === '') {
                continue;
            }
            if (in_array($norm, ['empresa', 'emp', 'company'], true)) {
                $empresa = (int) $idx;
                continue;
            }
            if (in_array($norm, ['centro_de_costo', 'centro', 'centro_costo', 'centrocosto', 'cc', 'costcenter'], true)) {
                $centro = (int) $idx;
                continue;
            }
            if (in_array($norm, ['cuenta', 'cta', 'cuenta_codigo', 'account'], true)) {
                $cuenta = (int) $idx;
                continue;
            }
            $mesKey = preg_replace('/[^a-z0-9_]+/', '', $norm);
            $mesKey = preg_replace('/_?\d{4}$/', '', $mesKey);
            if (isset($mesAlias[$mesKey]) && ! isset($meses[$mesAlias[$mesKey]])) {
                $meses[$mesAlias[$mesKey]] = (int) $idx;
            }
        }

        ksort($meses);

        return [
            'empresa' => $empresa,
            'centro' => $centro,
            'cuenta' => $cuenta,
            'meses' => array_values($meses),
        ];
    }

    protected function normHeader($raw): string
    {
        $s = strtolower(trim((string) $raw));
        $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
        $s = preg_replace('/[^a-z0-9]+/', '_', $s);

        return trim((string) $s, '_');
    }

    protected function celdaTexto($raw): string
    {
        if ($raw === null) {
            return '';
        }
        if (is_float($raw) || is_int($raw)) {
            if ((float) $raw == floor((float) $raw)) {
                return (string) (int) $raw;
            }

            return rtrim(rtrim(sprintf('%.8F', $raw), '0'), '.');
        }

        return trim((string) $raw);
    }

    /**
     * Celda de mes: vacía = pendiente (null). Un 0 escrito cuenta como capturado.
     */
    protected function celdaMesCaptura($raw): ?float
    {
        if ($raw === null) {
            return null;
        }
        if (is_string($raw)) {
            $raw = trim(str_replace(['$', ',', ' '], '', $raw));
            if ($raw === '') {
                return null;
            }
        }
        if (! is_numeric($raw)) {
            return null;
        }

        return round((float) $raw, 2);
    }

    /**
     * @param  array<int, mixed>  $meses
     */
    protected function mesesTodosLlenos(array $meses): bool
    {
        if (count($meses) < 12) {
            return false;
        }
        for ($i = 0; $i < 12; $i++) {
            if ($meses[$i] === null || $meses[$i] === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, array{cuenta: string, nombre: string, capturar: bool}>
     */
    protected function mapaCuentasCaptura(string $ciclo): array
    {
        $out = [];
        $estado = $this->cicloEstado($ciclo);
        $asigs = CcAsignacion::query()->with(['cuentas', 'permisos.tipo'])
            ->whereRaw('UPPER(ciclo_codigo) = ?', [strtoupper($ciclo)])
            ->where('user_id', auth()->id())
            ->get();
        foreach ($asigs as $a) {
            $claves = $a->permisos->map(function ($p) {
                return $p->tipo->clave ?? null;
            })->filter()->all();
            $capturar = $this->asignacionPuedeEscribir($claves, $estado);
            $empresa = strtoupper(trim((string) $a->empresa));
            $centro = trim((string) $a->centro_codigo);
            foreach ($a->cuentas as $cta) {
                $cuenta = trim((string) $cta->cuenta_codigo);
                $payload = [
                    'cuenta' => $cuenta,
                    'nombre' => (string) ($cta->cuenta_nombre ?: ''),
                    'capturar' => $capturar,
                ];
                foreach ($this->clavesCuentaCaptura($empresa, $centro, $cuenta) as $key) {
                    if (! isset($out[$key])) {
                        $out[$key] = $payload;
                    } else {
                        $out[$key]['capturar'] = $out[$key]['capturar'] || $capturar;
                    }
                }
            }
        }

        return $out;
    }

    /**
     * @param  array<int, string>  $nombres
     * @return array{ok: bool, year: int, por_cuenta: array<string, array<string, mixed>>|\stdClass, mensaje: string|null}
     */
    protected function cargarGastoRealCentro(string $empresa, string $cc, int $year, array $nombres = []): array
    {
        $api = app(AutinApiClient::class);
        $porCuenta = [];
        $ok = false;
        $mensaje = null;
        $base = [
            'Empresa' => $empresa,
            'year' => $year,
            'fecha_desde' => $year . '-01-01',
            'fecha_hasta' => $year . '-12-31',
        ];

        $consultas = [];
        foreach ($nombres as $nombre) {
            $nombre = trim($nombre);
            if ($nombre !== '') {
                $consultas[] = $nombre;
            }
        }

        if ($consultas) {
            $cachedNombres = [];
            $faltan = [];
            foreach ($consultas as $nombre) {
                $nk = $this->nombreCuentaKey($nombre);
                $nomCache = 'cc.gasto-nom.v1.' . $empresa . '.' . $year . '.' . md5($nk);
                $hit = Cache::get($nomCache);
                if (is_array($hit) && isset($hit['gasto']) && is_array($hit['gasto'])) {
                    $porCuenta[$nk] = [
                        'nombre' => $hit['nombre'] ?? $nombre,
                        'gasto' => $hit['gasto'],
                    ];
                    $cachedNombres[$nk] = true;
                } else {
                    $faltan[] = $nombre;
                }
            }
            if ($faltan) {
                $res = $api->gastoRealPorNombres($empresa, $year, $faltan, 12, 8);
                if (empty($res['ok'])) {
                    if (! $cachedNombres) {
                        $mensaje = $res['message'] ?? 'Sin conexión a gasto real SAP';
                    }
                } else {
                    $ok = true;
                    $mensaje = null;
                    foreach ($res['rows'] as $row) {
                        if (is_array($row)) {
                            $this->acumularGastoRealFila($porCuenta, $row, $empresa, $year, $faltan);
                        }
                    }
                    foreach ($faltan as $nombre) {
                        $nk = $this->nombreCuentaKey($nombre);
                        if (! isset($porCuenta[$nk])) {
                            $porCuenta[$nk] = [
                                'nombre' => $nombre,
                                'gasto' => array_fill(0, 12, 0.0),
                            ];
                        }
                        Cache::put('cc.gasto-nom.v1.' . $empresa . '.' . $year . '.' . md5($nk), $porCuenta[$nk], 1800);
                    }
                }
            }
            if ($cachedNombres) {
                $ok = true;
            }
        } elseif ($cc !== '') {
            $res = $api->gastoRealTodasPaginas(array_merge($base, ['CC' => $cc, 'GroupMask' => '6']), 8, 6);
            if (empty($res['ok'])) {
                $mensaje = $res['message'] ?? 'Sin conexión a gasto real SAP';
            } else {
                $ok = true;
                foreach ($res['rows'] as $row) {
                    if (is_array($row)) {
                        $this->acumularGastoRealFila($porCuenta, $row, $empresa, $year, $nombres);
                    }
                }
            }
        }

        return [
            'ok' => $ok,
            'year' => $year,
            'por_cuenta' => $porCuenta ?: (object) [],
            'mensaje' => $mensaje,
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function listaRequest(Request $request, string $key): array
    {
        $raw = $request->input($key, $request->input($key . '[]', []));
        if (is_string($raw)) {
            $raw = preg_split('/\s*,\s*/', $raw) ?: [];
        }
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $item) {
            $item = trim((string) $item);
            if ($item !== '') {
                $out[$item] = $item;
            }
        }

        return array_values($out);
    }

    /**
     * @param  array<int, string>  $nombres
     */
    protected function completarNombresGastoDesdeAsignaciones(string $empresa, string $cc, array &$nombres): void
    {
        if (! Schema::hasTable('tbl_cc_asignaciones') || ! Schema::hasTable('tbl_cc_asignacion_cuentas')) {
            return;
        }
        $q = CcAsignacion::query()->with('cuentas')->whereRaw('UPPER(empresa) = ?', [$empresa]);
        if ($cc !== '') {
            $alt = ltrim($cc, '0');
            $q->where(function ($w) use ($cc, $alt) {
                $w->where('centro_codigo', $cc);
                if ($alt !== '' && $alt !== $cc) {
                    $w->orWhere('centro_codigo', $alt)->orWhere('centro_codigo', str_pad($alt, strlen($cc), '0', STR_PAD_LEFT));
                }
            });
        }
        foreach ($q->get() as $asig) {
            foreach ($asig->cuentas as $cta) {
                $nom = trim((string) $cta->cuenta_nombre);
                if ($nom !== '') {
                    $nombres[] = $nom;
                }
            }
        }
        $nombres = array_values(array_unique($nombres));
    }

    /**
     * Suma importes de la misma cuenta / DescCuenta en el mismo mes.
     *
     * @param  array<string, array{codigo: string, nombre: string, gasto: array<int, float>}>  $porCuenta
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $nombresPedido
     */
    protected function acumularGastoRealFila(array &$porCuenta, array $row, string $empresa, int $year, array $nombresPedido = []): void
    {
        $rowEmp = $this->campoFila($row, ['EMPRESA', 'Empresa', 'empresa', 'DB']);
        if ($rowEmp !== '' && ! $this->mismaEmpresaSap($rowEmp, $empresa)) {
            return;
        }

        $nombre = $this->campoFila($row, ['DescCuenta', 'AcctName', 'NOMBRE', 'AccountName', 'nombre']);
        if ($nombre === '' || ($nombresPedido && ! $this->nombreGastoPedido($nombre, $nombresPedido))) {
            return;
        }

        $fecha = $this->campoFila($row, ['Fecha', 'fecha', 'FECHA', 'RefDate', 'TaxDate', 'DocDate', 'DueDate', 'CreateDate']);
        $anioFila = $this->anioDeFecha($fecha);
        if ($anioFila > 0 && $anioFila !== $year) {
            return;
        }
        $mes = $this->mesDeFecha($fecha);
        if ($mes < 0) {
            return;
        }

        $importe = $this->importeGastoFila($row);
        $key = $this->nombreCuentaKey($nombre);
        if ($key === '') {
            return;
        }
        if (! isset($porCuenta[$key])) {
            $porCuenta[$key] = [
                'nombre' => $nombre,
                'gasto' => array_fill(0, 12, 0.0),
            ];
        }
        $porCuenta[$key]['gasto'][$mes] = round($porCuenta[$key]['gasto'][$mes] + $importe, 2);
    }

    /**
     * @param  array<int, string>  $nombres
     */
    protected function nombreGastoPedido(string $nombre, array $nombres): bool
    {
        $nk = $this->nombreCuentaKey($nombre);
        if ($nk === '') {
            return false;
        }
        foreach ($nombres as $req) {
            if ($this->nombreCuentaKey((string) $req) === $nk) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    protected function campoFila(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $row) || $row[$key] === null) {
                continue;
            }
            $val = trim((string) $row[$key]);
            if ($val !== '') {
                return $val;
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function importeGastoFila(array $row): float
    {
        foreach (['Importe', 'importe', 'LineTotal', 'DebitSys', 'Amount'] as $key) {
            if (isset($row[$key]) && $row[$key] !== '' && $row[$key] !== null) {
                return round((float) $row[$key], 2);
            }
        }
        $debit = (float) ($row['Debit'] ?? $row['DebitLC'] ?? 0);
        $credit = (float) ($row['Credit'] ?? $row['CreditLC'] ?? 0);
        if ($debit != 0.0 || $credit != 0.0) {
            return round($debit - $credit, 2);
        }

        return 0.0;
    }

    protected function nombreCuentaKey(string $nombre): string
    {
        $s = strtoupper(trim($nombre));
        if ($s === '') {
            return '';
        }
        $s = strtr($s, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'Ä' => 'A', 'Ë' => 'E', 'Ï' => 'I', 'Ö' => 'O', 'Ü' => 'U',
            'Ñ' => 'N',
        ]);
        $s = preg_replace('/[^A-Z0-9]+/', ' ', $s) ?? $s;

        return trim(preg_replace('/\s+/', ' ', $s) ?? $s);
    }

    protected function anioDeFecha(string $fecha): int
    {
        if ($fecha === '') {
            return 0;
        }
        if (preg_match('/(20\d{2}|19\d{2})/', $fecha, $m)) {
            return (int) $m[1];
        }

        return 0;
    }

    protected function mismoCentroCodigo(string $a, string $b): bool
    {
        $a = strtoupper(trim($a));
        $b = strtoupper(trim($b));
        if ($a === $b) {
            return true;
        }
        $na = ltrim($a, '0');
        $nb = ltrim($b, '0');

        return $na !== '' && $na === $nb;
    }

    protected function mismaEmpresaSap(string $a, string $b): bool
    {
        $alias = ['ABSA' => 'AUSTIN'];
        $a = strtoupper(trim($a));
        $b = strtoupper(trim($b));
        $a = $alias[$a] ?? $a;
        $b = $alias[$b] ?? $b;

        return $a === $b;
    }

    protected function codigoCuentaKey(string $codigo): string
    {
        $digits = preg_replace('/\D+/', '', $codigo);

        return $digits !== '' ? $digits : $codigo;
    }

    protected function mesDeFecha(string $fecha): int
    {
        if ($fecha === '') {
            return -1;
        }
        $fecha = trim($fecha);
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $fecha, $m)) {
            $month = (int) substr($m[0], 5, 2);

            return $month >= 1 && $month <= 12 ? $month - 1 : -1;
        }
        if (preg_match('/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})/', $fecha, $m)) {
            $month = (int) $m[2];
            if ($month > 12) {
                $month = (int) $m[1];
            }

            return $month >= 1 && $month <= 12 ? $month - 1 : -1;
        }
        $ts = strtotime(substr($fecha, 0, 19));
        if (! $ts) {
            return -1;
        }

        return ((int) date('n', $ts)) - 1;
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
            'gastoUrl' => route('centros.api.gasto_real'),
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

    protected function claveDesdeNombre(string $nombre, string $empresa): string
    {
        $base = strtoupper((string) Str::slug($nombre, '_'));
        $base = trim($base, '_');
        if ($base === '') {
            $base = 'GRP';
        }
        $base = substr($base, 0, 36);
        $clave = $base;
        $n = 2;
        while (CcGrupoCuenta::query()->where('empresa', $empresa)->where('clave', $clave)->exists()) {
            $suffix = '_' . $n;
            $clave = substr($base, 0, 40 - strlen($suffix)) . $suffix;
            $n++;
        }

        return $clave;
    }

    protected function codigoCuentaVisible(string $codigo): string
    {
        $s = trim($codigo);
        if ($s === '') {
            return '';
        }
        if (! preg_match('/SYS/i', $s)) {
            return $s;
        }
        $s = preg_replace('/_?SYS/i', '', $s) ?? $s;
        $s = preg_replace('/^0+/', '', $s) ?? $s;
        $s = trim($s, " \t-_");

        return $s !== '' ? $s : $this->codigoCuentaKey($codigo);
    }

    protected function codigoCuentaPlantilla(string $codigo): string
    {
        $digits = $this->codigoCuentaKey($codigo);
        if ($digits !== '' && preg_match('/^\d+$/', $digits)) {
            $trimmed = ltrim($digits, '0');

            return $trimmed !== '' ? $trimmed : $digits;
        }

        return $this->codigoCuentaVisible($codigo);
    }

    /**
     * @param  array<string, array{cuenta: string, nombre: string, capturar: bool}>  $permitidas
     * @return array{cuenta: string, nombre: string, capturar: bool}|null
     */
    protected function buscarCuentaCaptura(array $permitidas, string $empresa, string $centro, string $cuenta): ?array
    {
        foreach ($this->clavesCuentaCaptura($empresa, $centro, $cuenta) as $key) {
            if (isset($permitidas[$key])) {
                return $permitidas[$key];
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    protected function clavesCuentaCaptura(string $empresa, string $centro, string $cuenta): array
    {
        $cuenta = trim($cuenta);
        $keys = [
            $this->capturaBudgetKey($empresa, $centro, $cuenta),
            $this->capturaBudgetKey($empresa, $centro, ltrim($cuenta, '0')),
            $this->capturaBudgetKey($empresa, $centro, $this->codigoCuentaVisible($cuenta)),
            $this->capturaBudgetKey($empresa, $centro, $this->codigoCuentaKey($cuenta)),
            $this->capturaBudgetKey($empresa, $centro, $this->codigoCuentaPlantilla($cuenta)),
        ];

        return array_values(array_unique(array_filter($keys)));
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
            $format = trim((string) ($row['FormatCode'] ?? $row['CUENTA'] ?? $row['Cuenta'] ?? ''));
            $acct = trim((string) ($row['AcctCode'] ?? $row['codigo'] ?? ''));
            if ($format !== '' && stripos($format, 'SYS') === false) {
                $codigo = $format;
            } else {
                $codigo = $this->codigoCuentaVisible($format !== '' ? $format : $acct);
            }
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
        $copy = $this->permisosCatalogoCopy();
        if (! Schema::hasTable('tbl_cc_tipos_permiso')) {
            return [
                ['clave' => 'capturar', 'nombre' => 'Capturar', 'descripcion' => $copy['capturar']],
                ['clave' => 'editar', 'nombre' => 'Editar', 'descripcion' => $copy['editar']],
                ['clave' => 'revisar', 'nombre' => 'Revisar', 'descripcion' => $copy['revisar']],
                ['clave' => 'importar', 'nombre' => 'Importar masivo', 'descripcion' => $copy['importar']],
            ];
        }

        return CcTipoPermiso::query()->orderBy('orden')->get(['id', 'clave', 'nombre', 'descripcion'])
            ->map(function ($p) use ($copy) {
                $row = $p->toArray();
                $clave = (string) ($row['clave'] ?? '');
                if (isset($copy[$clave])) {
                    $row['descripcion'] = $copy[$clave];
                }

                return $row;
            })->all();
    }

    /**
     * @return array<string, string>
     */
    protected function permisosCatalogoCopy(): array
    {
        return [
            'capturar' => 'Puede capturar presupuesto mientras el budget esté Abierto',
            'editar' => 'Puede modificar montos cuando el ciclo está En revisión o Cerrado',
            'revisar' => 'Puede consultar y revisar sin editar',
            'importar' => 'Puede descargar plantilla e importar presupuestos de todos sus centros. Aplica al usuario en todo el ciclo.',
        ];
    }

    /**
     * Permisos que se eligen por centro (sin importar masivo, que es por usuario).
     *
     * @return array<int, array<string, mixed>>
     */
    protected function permisosCatalogoAsignacion(): array
    {
        return array_values(array_filter($this->permisosCatalogo(), function ($p) {
            return ($p['clave'] ?? '') !== 'importar';
        }));
    }

    /**
     * @return array<int, int>
     */
    protected function userIdsConImportar(string $ciclo): array
    {
        $ids = [];
        if (Schema::hasTable('tbl_cc_usuario_permisos') && Schema::hasTable('tbl_cc_tipos_permiso')) {
            $tipo = CcTipoPermiso::query()->where('clave', 'importar')->first();
            if ($tipo) {
                $ids = CcUsuarioPermiso::query()
                    ->whereRaw('UPPER(ciclo_codigo) = ?', [strtoupper($ciclo)])
                    ->where('permiso_id', $tipo->id)
                    ->pluck('user_id')
                    ->map(function ($id) {
                        return (int) $id;
                    })
                    ->all();
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function importarUsuariosPayload(string $ciclo): array
    {
        $ids = $this->userIdsConImportar($ciclo);
        if (! $ids) {
            return [];
        }

        $centros = [];
        if (Schema::hasTable('tbl_cc_asignaciones')) {
            $centros = CcAsignacion::query()
                ->whereRaw('UPPER(ciclo_codigo) = ?', [strtoupper($ciclo)])
                ->whereIn('user_id', $ids)
                ->selectRaw('user_id, count(*) as total')
                ->groupBy('user_id')
                ->pluck('total', 'user_id')
                ->all();
        }

        return User::query()->whereIn('id', $ids)->orderBy('name')->get(['id', 'name', 'email'])
            ->map(function ($u) use ($centros) {
                return [
                    'id' => (int) $u->id,
                    'nombre' => $u->name,
                    'email' => $u->email,
                    'asignaciones' => (int) ($centros[$u->id] ?? 0),
                ];
            })->values()->all();
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
        $claves = array_values(array_unique(array_filter(array_map('strval', $claves))));
        $clavesAsig = array_values(array_filter($claves, function ($c) {
            return $c !== 'importar';
        }));

        CcAsignacionPermiso::query()->where('asignacion_id', $asig->id)->delete();
        if (Schema::hasTable('tbl_cc_tipos_permiso')) {
            $tipos = CcTipoPermiso::query()->whereIn('clave', $clavesAsig)->get();
            foreach ($tipos as $tipo) {
                CcAsignacionPermiso::query()->create([
                    'asignacion_id' => $asig->id,
                    'permiso_id' => $tipo->id,
                ]);
            }
        }
    }

    protected function syncPermisoUsuario(string $ciclo, int $userId, string $clave, bool $enabled): void
    {
        if ($userId <= 0 || $clave === '') {
            return;
        }

        if (Schema::hasTable('tbl_cc_usuario_permisos') && Schema::hasTable('tbl_cc_tipos_permiso')) {
            $tipo = CcTipoPermiso::query()->where('clave', $clave)->first();
            if (! $tipo) {
                return;
            }
            $q = CcUsuarioPermiso::query()
                ->where('ciclo_codigo', $ciclo)
                ->where('user_id', $userId)
                ->where('permiso_id', $tipo->id);
            if ($enabled) {
                if (! $q->exists()) {
                    CcUsuarioPermiso::query()->create([
                        'ciclo_codigo' => $ciclo,
                        'user_id' => $userId,
                        'permiso_id' => $tipo->id,
                    ]);
                }
            } else {
                $q->delete();
            }

            return;
        }

        if (! $enabled || ! Schema::hasTable('tbl_cc_tipos_permiso')) {
            return;
        }
        $tipo = CcTipoPermiso::query()->where('clave', $clave)->first();
        if (! $tipo) {
            return;
        }
        $asigs = CcAsignacion::query()
            ->where('ciclo_codigo', $ciclo)
            ->where('user_id', $userId)
            ->get();
        foreach ($asigs as $a) {
            CcAsignacionPermiso::query()->firstOrCreate([
                'asignacion_id' => $a->id,
                'permiso_id' => $tipo->id,
            ]);
        }
    }

    protected function usuarioTienePermiso(string $ciclo, int $userId, string $clave): bool
    {
        if ($userId <= 0) {
            return false;
        }
        $cacheKey = strtoupper($ciclo).'|'.$userId.'|'.$clave;
        if (array_key_exists($cacheKey, $this->ccUserPermCache)) {
            return $this->ccUserPermCache[$cacheKey];
        }

        $found = false;
        if (Schema::hasTable('tbl_cc_usuario_permisos') && Schema::hasTable('tbl_cc_tipos_permiso')) {
            $tipo = CcTipoPermiso::query()->where('clave', $clave)->first();
            if ($tipo && CcUsuarioPermiso::query()
                ->whereRaw('UPPER(ciclo_codigo) = ?', [strtoupper($ciclo)])
                ->where('user_id', $userId)
                ->where('permiso_id', $tipo->id)
                ->exists()) {
                $found = true;
            }
        }

        if (! $found && Schema::hasTable('tbl_cc_asignaciones')) {
            $asigs = CcAsignacion::query()
                ->with('permisos.tipo')
                ->whereRaw('UPPER(ciclo_codigo) = ?', [strtoupper($ciclo)])
                ->where('user_id', $userId)
                ->get();
            foreach ($asigs as $a) {
                foreach ($a->permisos as $p) {
                    if (($p->tipo->clave ?? null) === $clave) {
                        $found = true;
                        break 2;
                    }
                }
            }
        }

        $this->ccUserPermCache[$cacheKey] = $found;

        return $found;
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
        })->filter(function ($c) {
            return $c && $c !== 'importar';
        })->values()->all();

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
            'importar' => $this->usuarioTienePermiso((string) $a->ciclo_codigo, (int) $a->user_id, 'importar'),
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
            'estado' => $this->normalizeCicloEstado($c->estado),
            'inflacion' => (float) $c->inflacion,
            'tipoCambio' => (float) $c->tipo_cambio,
            'observaciones' => $c->observaciones,
            'asignaciones' => (int) ($stats['asignaciones'] ?? 0),
            'cuentas' => (int) ($stats['cuentas'] ?? 0),
            'usuarios' => (int) ($stats['usuarios'] ?? 0),
            'centros' => (int) ($stats['centros'] ?? 0),
        ];
    }

    protected function normalizeCicloEstado(?string $estado): string
    {
        $e = strtolower(trim((string) $estado));
        if (in_array($e, ['cerrado', 'terminado', 'aceptado', 'rechazado'], true)) {
            return 'cerrado';
        }
        if ($e === 'en_revision') {
            return 'en_revision';
        }

        return 'abierto';
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
