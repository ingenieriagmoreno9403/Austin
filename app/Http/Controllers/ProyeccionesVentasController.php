<?php

namespace App\Http\Controllers;

use App\Exports\PvCapturaPlantillaExport;
use App\Models\PvAsignacion;
use App\Models\PvAsignacionProducto;
use App\Models\PvAsignacionPermiso;
use App\Models\PvCapturaCentro;
use App\Models\PvCiclo;
use App\Models\PvPresupuesto;
use App\Models\PvTipoPermiso;
use App\Models\PvUsuarioPermiso;
use App\Models\Empresas;
use App\Models\User;
use App\Services\AutinApiClient;
use App\Traits\MenuTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Proyecciones de ventas: empresas > clientes > productos.
 * Captura de cantidades en tbl_pv_proyecciones.
 */
class ProyeccionesVentasController extends Controller
{
    use MenuTrait;

    /** @var array<string, bool> */
    protected $pvUserPermCache = [];

    /** @var array<string, string> */
    protected $pvCicloEstadoCache = [];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function admin()
    {
        return $this->page('admin', 'ProyeccionesVentas.admin');
    }

    public function ciclo(string $ciclo)
    {
        return $this->page('admin-ciclo', 'ProyeccionesVentas.ciclo', [
            'cicloCodigo' => $ciclo,
            'permisosCatalogo' => $this->permisosCatalogoAsignacion(),
        ]);
    }

    public function control(Request $request)
    {
        return $this->page('control', 'ProyeccionesVentas.control', [
            'centroInicial' => (string) $request->get('cc', ''),
            'empresaInicial' => (string) $request->get('empresa', ''),
            'cicloInicial' => (string) $request->get('ciclo', ''),
            'vistaInicial' => (string) $request->get('vista', ''),
            'detalleUrl' => route('pv.detalle'),
            'misAsignaciones' => $this->misAsignacionesList(''),
        ]);
    }

    public function detalle(Request $request)
    {
        return $this->page('detalle', 'ProyeccionesVentas.detalle', [
            'centroInicial' => (string) $request->get('cc', ''),
            'empresaInicial' => (string) $request->get('empresa', ''),
            'cicloInicial' => (string) $request->get('ciclo', ''),
            'misAsignaciones' => $this->misAsignacionesList((string) $request->get('ciclo', '')),
        ]);
    }

    public function analisis()
    {
        return $this->page('analisis', 'ProyeccionesVentas.analisis', [
            'detalleUrl' => route('pv.detalle'),
        ]);
    }

    public function listCiclos(): JsonResponse
    {
        return response()->json(['ciclos' => $this->ciclosPayload()]);
    }

    public function storeCiclo(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tbl_pv_ciclos')) {
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
            'tipoCambio' => 'nullable|numeric',
            'tipoCambioMeses' => 'nullable|array|size:12',
            'tipoCambioMeses.*' => 'nullable|numeric|min:0',
            'tipoBudget' => 'nullable|in:3+9,6+6,9+3',
            'observaciones' => 'nullable|string',
        ]);

        $codigo = strtoupper(trim($data['codigo']));
        $ciclo = PvCiclo::query()->firstOrNew(['codigo' => $codigo]);
        $nuevo = ! $ciclo->exists;
        $fill = [
            'nombre' => $data['nombre'],
            'anio_referencia' => $data['anioReferencia'],
            'anio_presupuesto' => $data['anio'],
            'fecha_inicio' => $data['inicio'] ?: null,
            'fecha_fin' => $data['fin'] ?: null,
            'captura_hasta' => $data['capturaHasta'] ?: null,
            'revision_desde' => $data['revisionDesde'] ?: null,
            'estado' => $this->normalizeCicloEstado($data['estado'] ?? 'abierto'),
            'tipo_cambio' => $data['tipoCambio'] ?? 0,
            'observaciones' => $data['observaciones'] ?? null,
            'updated_by' => auth()->id(),
        ];
        if (array_key_exists('tipoCambioMeses', $data) && Schema::hasColumn('tbl_pv_ciclos', 'tipo_cambio_meses')) {
            $fill['tipo_cambio_meses'] = $this->normalizeTipoCambioMeses(
                $data['tipoCambioMeses'] ?? null,
                (float) ($fill['tipo_cambio'] ?: 20)
            );
        }
        // Tipo de budget: solo se fija al crear el ciclo.
        if ($nuevo && Schema::hasColumn('tbl_pv_ciclos', 'tipo_budget')) {
            $fill['tipo_budget'] = $this->normalizeTipoBudget($data['tipoBudget'] ?? '3+9');
        }
        $ciclo->fill($fill);
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

    public function updateTipoCambioMeses(Request $request, string $ciclo): JsonResponse
    {
        if (! Schema::hasTable('tbl_pv_ciclos')) {
            return response()->json(['message' => 'Falta la tabla de ciclos.'], 422);
        }
        $row = PvCiclo::query()->where('codigo', strtoupper(trim($ciclo)))->first();
        if (! $row) {
            return response()->json(['message' => 'Ciclo no encontrado.'], 404);
        }

        $data = $request->validate([
            'tipoCambio' => 'nullable|numeric|min:0',
            'tipoCambioMeses' => 'nullable|array|size:12',
            'tipoCambioMeses.*' => 'nullable|numeric|min:0',
        ]);

        if (array_key_exists('tipoCambio', $data) && $data['tipoCambio'] !== null) {
            $row->tipo_cambio = (float) $data['tipoCambio'];
        }
        if (Schema::hasColumn('tbl_pv_ciclos', 'tipo_cambio_meses')) {
            $base = (float) ($row->tipo_cambio ?: 20);
            $row->tipo_cambio_meses = $this->normalizeTipoCambioMeses(
                $data['tipoCambioMeses'] ?? $row->tipo_cambio_meses,
                $base
            );
        }
        $row->updated_by = auth()->id();
        $row->save();

        return response()->json([
            'ok' => true,
            'ciclo' => $this->cicloPayload($row),
        ]);
    }

    public function updateCicloEstado(Request $request, string $ciclo): JsonResponse
    {
        if (! Schema::hasTable('tbl_pv_ciclos')) {
            return response()->json(['message' => 'Falta ejecutar la migración de ciclos.'], 422);
        }

        $data = $request->validate([
            'estado' => 'required|in:abierto,en_revision,cerrado,en_proceso,terminado',
        ]);

        $row = PvCiclo::query()->where('codigo', $ciclo)->first();
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
        if (! Schema::hasTable('tbl_pv_ciclos')) {
            return response()->json(['message' => 'Falta ejecutar la migración de ciclos.'], 422);
        }

        $row = PvCiclo::query()->where('codigo', $ciclo)->first();
        if (! $row) {
            return response()->json(['message' => 'Ciclo no encontrado.'], 404);
        }

        $stats = $this->cicloStats($row->codigo);

        DB::transaction(function () use ($row) {
            $ids = PvAsignacion::query()->where('ciclo_codigo', $row->codigo)->pluck('id');
            if ($ids->isNotEmpty()) {
                if (Schema::hasTable('tbl_pv_asignacion_productos')) {
                    PvAsignacionProducto::query()->whereIn('asignacion_id', $ids)->delete();
                }
                if (Schema::hasTable('tbl_pv_asignacion_permisos')) {
                    PvAsignacionPermiso::query()->whereIn('asignacion_id', $ids)->delete();
                }
                PvAsignacion::query()->whereIn('id', $ids)->delete();
            }
            if (Schema::hasTable('tbl_pv_proyecciones')) {
                PvPresupuesto::query()->where('ciclo_codigo', $row->codigo)->delete();
            }
            if (Schema::hasTable('tbl_pv_captura_clientes')) {
                PvCapturaCentro::query()->where('ciclo_codigo', $row->codigo)->delete();
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
            return redirect()->route('pv.asignar', $ciclo);
        }

        $cicloRow = Schema::hasTable('tbl_pv_ciclos')
            ? PvCiclo::query()->where('codigo', $ciclo)->first()
            : null;

        return $this->page('asignacion', 'ProyeccionesVentas.asignacion', [
            'cicloCodigo' => $ciclo,
            'empresaCodigo' => $empresa,
            'empresaNombre' => $empresa !== '' ? strtoupper($empresa) : '',
            'permisosCatalogo' => $this->permisosCatalogoAsignacion(),
            'anioGasto' => $cicloRow ? (int) $cicloRow->anio_referencia : null,
            'anioPresupuesto' => $cicloRow ? (int) $cicloRow->anio_presupuesto : null,
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
        if (Schema::hasTable('tbl_pv_asignaciones')) {
            $q = PvAsignacion::query()
                ->selectRaw('empresa, count(*) as total')
                ->groupBy('empresa');
            if ($ciclo !== '') {
                $q->where('ciclo_codigo', $ciclo);
            }
            $counts = $q->pluck('total', 'empresa')->all();
        }

        $empresas = collect($dbs)->map(function ($db) use ($counts) {
            return [
                'codigo' => $db,
                'nombre' => strtoupper($db),
                'asignaciones' => (int) ($counts[$db] ?? 0),
            ];
        })->values()->all();

        return response()->json(['empresas' => $empresas]);
    }

    public function centrosSap(Request $request): JsonResponse
    {
        $empresa = strtolower((string) $request->get('empresa', 'austin'));
        $year = (int) $request->get('year', date('Y'));
        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }

        try {
            $pack = $this->cargarClientesEmpresa($empresa, $year);

            return response()->json([
                'ok' => $pack['ok'],
                'centros' => $pack['clientes'],
                'mensaje' => $pack['mensaje'],
            ]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'centros' => [], 'mensaje' => $e->getMessage()], 200);
        }
    }

    public function cuentasSap(Request $request): JsonResponse
    {
        $empresa = strtolower((string) $request->get('empresa', 'austin'));
        $cliente = trim((string) $request->get('cliente', $request->get('cc', '')));
        $year = (int) $request->get('year', date('Y'));
        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }

        $todas = $request->boolean('todas');

        try {
            $cargadas = $this->cargarProductosCliente($empresa, $cliente, $year, $todas);

            return response()->json([
                'ok' => $cargadas['ok'],
                'cuentas' => $cargadas['productos'],
                'agrupaciones' => [],
                'mensaje' => $cargadas['mensaje'],
            ]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'cuentas' => [], 'agrupaciones' => [], 'mensaje' => $e->getMessage()], 200);
        }
    }

    public function listAsignaciones(string $ciclo): JsonResponse
    {
        if (! Schema::hasTable('tbl_pv_asignaciones')) {
            return response()->json(['asignaciones' => []]);
        }

        $empresa = request('empresa');
        $userId = (int) request('user_id', 0);
        $q = PvAsignacion::query()->with(['usuario', 'cuentas', 'permisos.tipo'])
            ->where('ciclo_codigo', $ciclo);
        if ($empresa) {
            $q->where('empresa', strtolower($empresa));
        }
        if ($userId > 0) {
            $q->where('user_id', $userId);
        }

        $rows = $q->orderByDesc('id')->get()->map(function (PvAsignacion $a) {
            return $this->asignacionPayload($a);
        })->values()->all();

        return response()->json(['asignaciones' => $rows]);
    }

    public function storeAsignacion(Request $request, string $ciclo): JsonResponse
    {
        if (! Schema::hasTable('tbl_pv_asignaciones')) {
            return response()->json(['message' => 'Falta ejecutar migraciones de asignaciones.'], 422);
        }

        $data = $request->validate([
            'empresa' => 'required|string|max:40',
            'user_id' => 'required|integer|exists:users,id',
            'centro_codigo' => 'required|string|max:40',
            'centro_nombre' => 'nullable|string|max:180',
            'cuentas' => 'array',
            'cuentas.*.codigo' => 'required|string|max:80',
            'cuentas.*.nombre' => 'nullable|string|max:180',
            'cuentas.*.agrupacion' => 'nullable|string|max:80',
            'permisos' => 'array',
            'permisos.*' => 'string',
        ]);

        $empresa = strtolower($data['empresa']);
        $asig = PvAsignacion::query()->firstOrNew([
            'ciclo_codigo' => $ciclo,
            'empresa' => $empresa,
            'user_id' => $data['user_id'],
            'cliente_codigo' => $data['centro_codigo'],
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
        $asig = PvAsignacion::query()->with(['usuario', 'cuentas', 'permisos.tipo'])
            ->where('ciclo_codigo', $ciclo)
            ->where('id', $id)
            ->first();
        if (! $asig) {
            return response()->json(['message' => 'Asignación no encontrada'], 404);
        }

        $data = $request->validate([
            'cuentas' => 'sometimes|array',
            'cuentas.*.codigo' => 'required_with:cuentas|string|max:80',
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
        $asig = PvAsignacion::query()->where('ciclo_codigo', $ciclo)->where('id', $id)->first();
        if (! $asig) {
            return response()->json(['message' => 'Asignación no encontrada'], 404);
        }

        DB::transaction(function () use ($asig) {
            if ($this->asigHasRolColumns() && $asig->es_principal) {
                $extras = PvAsignacion::query()
                    ->where('ciclo_codigo', $asig->ciclo_codigo)
                    ->where('empresa', $asig->empresa)
                    ->where('cliente_codigo', $asig->centro_codigo)
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
        $this->pvUserPermCache = [];

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
        if (! Schema::hasTable('tbl_pv_asignaciones')) {
            return [];
        }

        $q = PvAsignacion::query()->with(['usuario', 'cuentas', 'permisos.tipo'])
            ->where('user_id', auth()->id());
        if ($ciclo !== '') {
            $q->where('ciclo_codigo', $ciclo);
        }

        return $q->orderBy('ciclo_codigo')->orderBy('empresa')->orderBy('cliente_codigo')
            ->get()
            ->map(function (PvAsignacion $a) {
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
        $empresa = strtoupper(trim((string) $request->get('empresa', '')));
        $cc = trim((string) $request->get('cc', $request->get('CC', '')));
        $year = (int) $request->get('year', $request->get('anio', 0));

        $alias = [
            'ABSA' => 'AUSTIN',
        ];
        if (isset($alias[$empresa])) {
            $empresa = $alias[$empresa];
        }

        if ($empresa === '' || $cc === '') {
            return response()->json(['ok' => false, 'por_cuenta' => (object) [], 'mensaje' => 'Falta empresa o cliente.'], 422);
        }
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }
        $refYear = (int) ($request->get('ref') ?: ($request->get('anio_referencia') ?: date('Y')));
        if ($refYear < 2000 || $refYear > 2100) {
            $refYear = (int) date('Y');
        }
        if ($year > $refYear) {
            $year = $refYear;
        }
        if ($year < ($refYear - 3)) {
            $year = $refYear - 3;
        }

        $cacheKey = 'pv.venta-real.' . $empresa . '.' . $cc . '.' . $year;
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && ! empty($cached['ok'])) {
            return response()->json($cached);
        }

        try {
            $payload = $this->cargarGastoRealCentro($empresa, $cc, $year);
            if (! empty($payload['ok'])) {
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

    /**
     * Listas de precios SAP por empresa + cliente (CardCode).
     * Respuesta: por_articulo[codigo] => { codigo, nombre, precio, moneda, unidad, lista }.
     */
    public function listasPrecios(Request $request): JsonResponse
    {
        $empresa = strtoupper(trim((string) $request->get('empresa', '')));
        $cc = trim((string) $request->get('cliente', $request->get('cc', $request->get('CodigoCliente', ''))));

        $alias = [
            'ABSA' => 'AUSTIN',
        ];
        if (isset($alias[$empresa])) {
            $empresa = $alias[$empresa];
        }

        if ($empresa === '' || $cc === '') {
            return response()->json([
                'ok' => false,
                'por_articulo' => (object) [],
                'mensaje' => 'Falta empresa o cliente.',
            ], 422);
        }

        $cacheKey = 'pv.listas-precios.' . $empresa . '.' . $cc;
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && ! empty($cached['ok'])) {
            return response()->json($cached);
        }

        $year = (int) $request->get('year', $request->get('anio', 0));
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y') - 1;
        }

        try {
            $payload = $this->cargarListasPreciosCliente($empresa, $cc, $year);
            if (! empty($payload['ok'])) {
                Cache::put($cacheKey, $payload, 900);
            }
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'por_articulo' => (object) [],
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
        $costos = [];
        $ajustes = [];
        $preciosMeses = [];
        if (Schema::hasTable('tbl_pv_proyecciones')) {
            $hasDone = Schema::hasColumn('tbl_pv_proyecciones', 'completado');
            $hasPrecioMeses = Schema::hasColumn('tbl_pv_proyecciones', 'precio_meses');
            PvPresupuesto::query()->whereRaw('UPPER(ciclo_codigo) = ?', [$ciclo])->get()->each(function (PvPresupuesto $row) use (&$budgets, &$completados, &$costos, &$ajustes, &$preciosMeses, $hasDone, $hasPrecioMeses) {
                $meses = $row->meses();
                $done = ($hasDone && ! empty($row->completado)) || $this->mesesTodosLlenos($meses);
                $pm = $hasPrecioMeses ? $row->precioMeses() : array_fill(0, 12, null);
                foreach ($this->clavesCuentaCaptura($row->empresa, $row->centro_codigo, $row->cuenta_codigo) as $key) {
                    $budgets[$key] = $meses;
                    $costos[$key] = (float) ($row->costo_unitario ?? 0);
                    $ajustes[$key] = (float) ($row->ajuste_pct ?? 0);
                    $preciosMeses[$key] = $pm;
                    if ($done) {
                        $completados[$key] = true;
                    }
                }
            });
        }

        $overlays = [];
        if (Schema::hasTable('tbl_pv_captura_clientes')) {
            PvCapturaCentro::query()->whereRaw('UPPER(ciclo_codigo) = ?', [$ciclo])->get()->each(function (PvCapturaCentro $row) use (&$overlays) {
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
            'costos' => (object) $costos,
            'ajustes' => (object) $ajustes,
            'preciosMeses' => (object) $preciosMeses,
            'overlays' => (object) $overlays,
        ]);
    }

    public function guardarPresupuesto(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tbl_pv_proyecciones')) {
            return response()->json(['message' => 'Falta ejecutar la migración de presupuestos.'], 422);
        }

        $data = $request->validate([
            'ciclo' => 'required|string|max:40',
            'empresa' => 'required|string|max:40',
            'centro' => 'required|string|max:40',
            'cuenta' => 'required|string|max:80',
            'cuenta_nombre' => 'nullable|string|max:180',
            'meses' => 'required|array|size:12',
            'meses.*' => 'nullable|numeric',
            'completado' => 'nullable|boolean',
            'ajuste_pct' => 'nullable|numeric',
            'costo_unitario' => 'nullable|numeric',
            'moneda' => 'nullable|string|max:8',
            'precio_meses' => 'nullable|array|size:12',
            'precio_meses.*' => 'nullable|numeric|min:0',
        ]);

        $ciclo = strtoupper(trim($data['ciclo']));
        $empresa = strtoupper(trim($data['empresa']));
        $centro = trim($data['centro']);
        $cuenta = trim($data['cuenta']);

        $motivo = $this->motivoBloqueoCaptura($ciclo, $empresa, $centro);
        if ($motivo) {
            return response()->json(['message' => $motivo], 403);
        }

        $row = PvPresupuesto::query()->firstOrNew([
            'ciclo_codigo' => $ciclo,
            'empresa' => $empresa,
            'cliente_codigo' => $centro,
            'producto_codigo' => $cuenta,
        ]);
        $row->setMeses($data['meses']);
        if (! empty($data['cuenta_nombre'])) {
            $row->cuenta_nombre = $data['cuenta_nombre'];
        }
        if (array_key_exists('ajuste_pct', $data) && $data['ajuste_pct'] !== null) {
            $row->ajuste_pct = (float) $data['ajuste_pct'];
        }
        if (array_key_exists('costo_unitario', $data) && $data['costo_unitario'] !== null) {
            $row->costo_unitario = (float) $data['costo_unitario'];
        }
        if (! empty($data['moneda'])) {
            $row->moneda = strtoupper(trim((string) $data['moneda']));
        }
        if (array_key_exists('precio_meses', $data) && Schema::hasColumn('tbl_pv_proyecciones', 'precio_meses')) {
            $row->setPrecioMeses($data['precio_meses']);
        }
        if (Schema::hasColumn('tbl_pv_proyecciones', 'completado')) {
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
            'precio_meses' => Schema::hasColumn('tbl_pv_proyecciones', 'precio_meses') ? $row->precioMeses() : array_fill(0, 12, null),
            'completado' => (bool) ($row->completado ?? false),
            'ajuste_pct' => (float) ($row->ajuste_pct ?? 0),
            'costo_unitario' => (float) ($row->costo_unitario ?? 0),
        ]);
    }

    public function guardarCapturaCentro(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tbl_pv_captura_clientes')) {
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

        $row = PvCapturaCentro::query()->firstOrNew([
            'ciclo_codigo' => $ciclo,
            'empresa' => $empresa,
            'cliente_codigo' => $centro,
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
        if (! Schema::hasTable('tbl_pv_asignaciones')) {
            return 'No tienes permiso de captura en este centro.';
        }

        $asigs = PvAsignacion::query()
            ->with('permisos.tipo')
            ->whereRaw('UPPER(ciclo_codigo) = ?', [strtoupper($ciclo)])
            ->where('user_id', auth()->id())
            ->whereRaw('UPPER(empresa) = ?', [strtoupper($empresa)])
            ->where('cliente_codigo', $centro)
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
        if (! isset($this->pvCicloEstadoCache[$key])) {
            $raw = Schema::hasTable('tbl_pv_ciclos')
                ? PvCiclo::query()->whereRaw('UPPER(codigo) = ?', [$key])->value('estado')
                : 'abierto';
            $this->pvCicloEstadoCache[$key] = $this->normalizeCicloEstado($raw);
        }

        return $this->pvCicloEstadoCache[$key];
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

        return Excel::download(new PvCapturaPlantillaExport($built['headings'], $built['rows'], $built['captured'] ?? []), $filename);
    }

    public function importarCaptura(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tbl_pv_proyecciones')) {
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
            return response()->json(['message' => 'Faltan columnas: empresa, cliente y producto.'], 422);
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
                    $errores[] = ['fila' => $fila, 'mensaje' => 'Empresa, cliente o producto vacíos'];
                    continue;
                }
                $info = $this->buscarCuentaCaptura($permitidas, $empresa, $centro, $cuenta);
                if (! $info) {
                    $errores[] = ['fila' => $fila, 'mensaje' => 'No tienes ese producto asignado ('.$empresa.' / '.$centro.' / '.$cuenta.')'];
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

                $rec = PvPresupuesto::query()->firstOrNew([
                    'ciclo_codigo' => $ciclo,
                    'empresa' => $empresa,
                    'cliente_codigo' => $centro,
                    'producto_codigo' => $info['cuenta'],
                ]);
                $rec->setMeses($meses);
                if (! empty($info['nombre'])) {
                    $rec->cuenta_nombre = $info['nombre'];
                }
                if (Schema::hasColumn('tbl_pv_proyecciones', 'completado')) {
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
        $headings = array_merge(['empresa', 'cliente', 'cliente_nombre', 'producto', 'producto_nombre'], $meses);
        $rows = [];
        $captured = [];

        $asigs = PvAsignacion::query()->with(['cuentas', 'permisos.tipo'])
            ->whereRaw('UPPER(ciclo_codigo) = ?', [strtoupper($ciclo)])
            ->where('user_id', auth()->id())
            ->orderBy('empresa')
            ->orderBy('cliente_codigo')
            ->get();

        $pptoMap = [];
        $hasDone = Schema::hasTable('tbl_pv_proyecciones') && Schema::hasColumn('tbl_pv_proyecciones', 'completado');
        if (Schema::hasTable('tbl_pv_proyecciones')) {
            PvPresupuesto::query()->whereRaw('UPPER(ciclo_codigo) = ?', [strtoupper($ciclo)])->get()
                ->each(function (PvPresupuesto $row) use (&$pptoMap, $hasDone) {
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
            // Plantilla PV: cliente. Compatibilidad con plantillas CC: centro_de_costo.
            if (in_array($norm, [
                'cliente', 'cliente_codigo', 'cardcode', 'card_code', 'socio', 'customer',
                'centro_de_costo', 'centro', 'centro_costo', 'centrocosto', 'cc', 'costcenter',
            ], true)) {
                $centro = (int) $idx;
                continue;
            }
            // Plantilla PV: producto. Compatibilidad con plantillas CC: cuenta.
            if (in_array($norm, [
                'producto', 'producto_codigo', 'itemcode', 'item_code', 'articulo',
                'cuenta', 'cta', 'cuenta_codigo', 'account',
            ], true)) {
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
        $asigs = PvAsignacion::query()->with(['cuentas', 'permisos.tipo'])
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
     * @return array{ok: bool, year: int, por_cuenta: array<string, array<string, mixed>>, mensaje: string|null}
     */
    protected function cargarGastoRealCentro(string $empresa, string $cc, int $year): array
    {
        $api = app(AutinApiClient::class);
        $porCuenta = [];
        $ok = false;
        $mensaje = null;
        $perPage = 200;
        $maxPages = 20;

        for ($page = 1; $page <= $maxPages; $page++) {
            $res = $api->ventas([
                'Empresa' => strtoupper($empresa),
                'CardCode' => $cc,
                'year' => $year,
                'fecha_desde' => $year . '/01/01',
                'fecha_hasta' => $year . '/12/31',
                'per_page' => $perPage,
                'page' => $page,
            ]);

            if (empty($res['ok'])) {
                if ($page === 1) {
                    $mensaje = $res['message'] ?? 'Sin conexión a ventas SAP';
                }
                break;
            }

            $ok = true;
            $body = is_array($res['body'] ?? null) ? $res['body'] : [];
            $rows = $body['data'] ?? [];
            if (! is_array($rows) || ! $rows) {
                break;
            }

            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $rowCc = (string) ($row['CardCode'] ?? $row['Cardcode'] ?? $row['CC'] ?? '');
                if ($cc !== '' && ! $this->mismoCentroCodigo($rowCc, $cc)) {
                    continue;
                }
                $codigo = trim((string) ($row['ItemCode'] ?? $row['Itemcode'] ?? ''));
                if ($codigo === '') {
                    continue;
                }
                $fecha = (string) ($row['DocDate'] ?? $row['Fecha'] ?? $row['fecha'] ?? $row['TaxDate'] ?? '');
                $ts = strtotime(substr($fecha, 0, 19));
                if ($ts && (int) date('Y', $ts) !== $year) {
                    continue;
                }
                $mes = $this->mesDeFecha($fecha);
                if ($mes < 0) {
                    continue;
                }
                $qty = $this->cantidadVenta($row);
                $importe = $this->importeVenta($row, ['LineTotal', 'linetotal', 'GTotal']);
                $importeUsd = $this->importeVenta($row, ['LineTotalUSD', 'LineTotalUsd', 'LineTotalFC', 'TotalFrgn']);
                $price = $this->numeroVenta($row, ['Price', 'Precio', 'UnitPrice', 'PriceBefDi']);
                $costo = $this->costoVenta($row);
                $nombre = trim((string) ($row['ItemName'] ?? $row['Dscription'] ?? ''));
                $unidad = trim((string) ($row['SalPackMsr'] ?? $row['SalUnitMsr'] ?? $row['unidad'] ?? ''));
                $key = $this->codigoCuentaKey($codigo);
                if (! isset($porCuenta[$key])) {
                    $porCuenta[$key] = [
                        'codigo' => $codigo,
                        'nombre' => $nombre,
                        'unidad' => $unidad,
                        'gasto' => array_fill(0, 12, 0.0),
                        'importe' => array_fill(0, 12, 0.0),
                        'importe_usd' => array_fill(0, 12, 0.0),
                        'precio_w' => array_fill(0, 12, 0.0),
                        'precio_q' => array_fill(0, 12, 0.0),
                        'costo' => $costo,
                    ];
                } elseif ($nombre !== '' && $porCuenta[$key]['nombre'] === '') {
                    $porCuenta[$key]['nombre'] = $nombre;
                }
                if ($unidad !== '' && ($porCuenta[$key]['unidad'] ?? '') === '') {
                    $porCuenta[$key]['unidad'] = $unidad;
                }
                $porCuenta[$key]['gasto'][$mes] = round($porCuenta[$key]['gasto'][$mes] + $qty, 4);
                $porCuenta[$key]['importe'][$mes] = round($porCuenta[$key]['importe'][$mes] + $importe, 2);
                $porCuenta[$key]['importe_usd'][$mes] = round($porCuenta[$key]['importe_usd'][$mes] + $importeUsd, 2);
                $peso = abs($qty);
                if ($price == 0.0 && abs($qty) > 0.0001) {
                    $price = $importe / $qty;
                }
                if ($peso > 0 && $price != 0.0) {
                    $porCuenta[$key]['precio_w'][$mes] += $peso * $price;
                    $porCuenta[$key]['precio_q'][$mes] += $peso;
                }
                if ($costo > 0) {
                    $porCuenta[$key]['costo'] = $costo;
                }
            }

            $pag = $this->paginacionDe($body);
            $lastPage = (int) ($pag['last_page'] ?? 0);
            if ($lastPage > 0 && $page >= $lastPage) {
                break;
            }
            if ($lastPage < 1 && count($rows) < $perPage) {
                break;
            }
        }

        foreach ($porCuenta as &$item) {
            $precio = [];
            for ($m = 0; $m < 12; $m++) {
                $den = (float) ($item['precio_q'][$m] ?? 0);
                $precio[$m] = $den > 0
                    ? round(((float) $item['precio_w'][$m]) / $den, 4)
                    : 0.0;
            }
            $item['precio'] = $precio;
            unset($item['precio_w'], $item['precio_q']);
        }
        unset($item);

        return [
            'ok' => $ok,
            'year' => $year,
            'por_cuenta' => $porCuenta,
            'mensaje' => $mensaje,
        ];
    }

    /**
     * @return array{ok: bool, empresa: string, cliente: string, por_articulo: array<string, array<string, mixed>>, mensaje: string|null}
     */
    protected function cargarListasPreciosCliente(string $empresa, string $cc, int $year = 0): array
    {
        $api = app(AutinApiClient::class);
        $porArticulo = [];
        $ok = false;
        $mensaje = null;
        $perPage = 500;
        $maxPages = 20;

        for ($page = 1; $page <= $maxPages; $page++) {
            $res = $api->listasPrecios([
                'Empresa' => strtoupper($empresa),
                'CodigoCliente' => $cc,
                'per_page' => $perPage,
                'page' => $page,
            ]);

            if (empty($res['ok'])) {
                if ($page === 1) {
                    $mensaje = $res['message'] ?? 'Sin conexión a listas de precios SAP';
                }
                break;
            }

            $ok = true;
            $body = is_array($res['body'] ?? null) ? $res['body'] : [];
            $rows = $body['data'] ?? [];
            if (! is_array($rows) || ! $rows) {
                break;
            }

            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $codigo = trim((string) ($row['CodigoArticulo'] ?? $row['ItemCode'] ?? $row['Itemcode'] ?? ''));
                if ($codigo === '') {
                    continue;
                }
                $precio = $this->numeroVenta($row, ['Precio', 'Price', 'UnitPrice', 'PriceBefDi']);
                $moneda = strtoupper(trim((string) ($row['Moneda'] ?? $row['Currency'] ?? 'MXN')));
                if ($moneda === '') {
                    $moneda = 'MXN';
                }
                $unidad = trim((string) (
                    $row['Unidad']
                    ?? $row['SalPackMsr']
                    ?? $row['SalUnitMsr']
                    ?? $row['UomCode']
                    ?? $row['unidad']
                    ?? ''
                ));
                $nombre = trim((string) ($row['Descripcion'] ?? $row['ItemName'] ?? ''));
                $entry = [
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                    'precio' => $precio,
                    'moneda' => $moneda,
                    'unidad' => $unidad,
                    'lista' => trim((string) ($row['ListaPrecio'] ?? '')),
                    'no_lista' => trim((string) ($row['NoLista'] ?? '')),
                ];

                $keys = array_unique(array_filter([
                    $codigo,
                    strtoupper($codigo),
                    $this->codigoCuentaKey($codigo),
                ]));
                foreach ($keys as $k) {
                    $porArticulo[$k] = $entry;
                }
            }

            $pag = $this->paginacionDe($body);
            $lastPage = (int) ($pag['last_page'] ?? 0);
            if ($lastPage > 0 && $page >= $lastPage) {
                break;
            }
            if ($lastPage < 1 && count($rows) < $perPage) {
                break;
            }
        }

        if ($ok && $porArticulo) {
            $this->enriquecerUnidadesDesdeVentas($api, $porArticulo, $empresa, $cc, $year);
        }

        return [
            'ok' => $ok,
            'empresa' => strtoupper($empresa),
            'cliente' => $cc,
            'por_articulo' => $porArticulo,
            'mensaje' => $mensaje,
        ];
    }

    /**
     * Completa unidad de medida (SalPackMsr) desde ventas SAP cuando la lista de precios no la trae.
     *
     * @param  array<string, array<string, mixed>>  $porArticulo
     */
    protected function enriquecerUnidadesDesdeVentas(AutinApiClient $api, array &$porArticulo, string $empresa, string $cc, int $year): void
    {
        $years = [];
        if ($year >= 2000) {
            $years[] = $year;
        }
        $prev = ((int) date('Y')) - 1;
        if (! in_array($prev, $years, true)) {
            $years[] = $prev;
        }
        $curr = (int) date('Y');
        if (! in_array($curr, $years, true)) {
            $years[] = $curr;
        }

        $faltan = 0;
        foreach ($porArticulo as $entry) {
            if (($entry['unidad'] ?? '') === '') {
                $faltan++;
            }
        }
        if ($faltan < 1) {
            return;
        }

        foreach ($years as $y) {
            $res = $api->ventas([
                'Empresa' => strtoupper($empresa),
                'CardCode' => $cc,
                'year' => $y,
                'per_page' => 500,
                'page' => 1,
            ]);
            if (empty($res['ok'])) {
                continue;
            }
            $body = is_array($res['body'] ?? null) ? $res['body'] : [];
            $rows = $body['data'] ?? [];
            if (! is_array($rows)) {
                continue;
            }
            $lastPage = (int) (($body['meta']['last_page'] ?? 1) ?: 1);
            $maxPages = min(3, max(1, $lastPage));

            for ($page = 1; $page <= $maxPages; $page++) {
                if ($page > 1) {
                    $res = $api->ventas([
                        'Empresa' => strtoupper($empresa),
                        'CardCode' => $cc,
                        'year' => $y,
                        'per_page' => 500,
                        'page' => $page,
                    ]);
                    if (empty($res['ok'])) {
                        break;
                    }
                    $body = is_array($res['body'] ?? null) ? $res['body'] : [];
                    $rows = $body['data'] ?? [];
                    if (! is_array($rows) || ! $rows) {
                        break;
                    }
                }

                foreach ($rows as $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    $codigo = trim((string) ($row['ItemCode'] ?? $row['Itemcode'] ?? ''));
                    if ($codigo === '') {
                        continue;
                    }
                    $unidad = trim((string) ($row['SalPackMsr'] ?? $row['SalUnitMsr'] ?? ''));
                    if ($unidad === '') {
                        continue;
                    }
                    foreach (array_unique(array_filter([$codigo, strtoupper($codigo), $this->codigoCuentaKey($codigo)])) as $k) {
                        if (! isset($porArticulo[$k])) {
                            continue;
                        }
                        if (($porArticulo[$k]['unidad'] ?? '') === '') {
                            $porArticulo[$k]['unidad'] = $unidad;
                        }
                    }
                }
            }

            $faltan = 0;
            foreach ($porArticulo as $entry) {
                if (($entry['unidad'] ?? '') === '') {
                    $faltan++;
                }
            }
            if ($faltan < 1) {
                return;
            }
        }
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
        $ts = strtotime(substr($fecha, 0, 10));
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
            'catalogoUrl' => route('pv.catalogo'),
            'gastoUrl' => route('pv.api.gasto_real'),
            'listasPreciosUrl' => route('pv.api.listas_precios'),
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
                'codigo' => 'PRY-2027',
                'nombre' => 'Proyección de ventas 2027',
                'anioReferencia' => 2026,
                'anio' => 2027,
                'inicio' => '2026-10-01',
                'fin' => '2026-10-31',
                'capturaHasta' => '2026-10-31',
                'revisionDesde' => '2026-11-01',
                'estado' => 'abierto',
                'inflacion' => 0,
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
        $year = (int) date('Y');
        $emp = $empresa ? strtolower($empresa) : null;
        $pack = $this->cargarVentasCatalogo($emp, $year, max(8, (int) ceil($perPage / 25)));

        return [
            'centros' => $pack['clientes'],
            'cuentas' => $pack['productos'],
            'agrupaciones' => [],
            'empresas' => $pack['empresas'],
            'sapOk' => $pack['ok'],
            'sapMensaje' => $pack['mensaje'],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function empresasFiltroVentas(string $empresa): array
    {
        $empresa = strtolower(trim($empresa));
        $out = [strtoupper($empresa)];
        if ($empresa === 'imsa') {
            $out[] = 'BACHIMBA';
        }

        return $out;
    }

    /**
     * Líneas de OINV + ORIN (AutinApi /ventas).
     *
     * @param  array<string, mixed>  $extra
     * @return array{ok: bool, rows: array<int, array<string, mixed>>, mensaje: string|null}
     */
    protected function filasVentasEmpresa(string $empresa, int $year, array $extra = []): array
    {
        $api = app(AutinApiClient::class);
        $rows = [];
        $ok = false;
        $mensaje = null;
        foreach ($this->empresasFiltroVentas($empresa) as $empFiltro) {
            $pack = $api->ventasTodasPaginas(array_merge([
                'year' => $year,
                'Empresa' => $empFiltro,
            ], $extra), 30, 6);
            if (empty($pack['ok'])) {
                if (! $ok) {
                    $mensaje = $pack['message'] ?? 'Sin conexión a ventas SAP';
                }
                continue;
            }
            $ok = true;
            foreach ($pack['rows'] as $row) {
                if (is_array($row)) {
                    $rows[] = $row;
                }
            }
        }

        return ['ok' => $ok, 'rows' => $rows, 'mensaje' => $mensaje];
    }

    /**
     * Productos (ItemCode / ItemName) en OINV + ORIN.
     * Por cliente (CardCode) o, con $todas, todo el catálogo vendido de la empresa.
     *
     * @return array{ok: bool, productos: array<int, array<string, mixed>>, mensaje: string|null}
     */
    protected function cargarProductosCliente(string $empresa, string $cliente, int $year, bool $todas = false): array
    {
        $empresa = strtolower(trim($empresa));
        $cliente = trim($cliente);
        if ($year < 2000) {
            $year = (int) date('Y');
        }
        if (! $todas && $cliente === '') {
            return [
                'ok' => true,
                'productos' => [],
                'mensaje' => 'Elige un cliente para ver sus productos (ItemName).',
            ];
        }

        $cacheKey = $todas
            ? 'pv.productos.'.$empresa.'.'.$year.'.ALL'
            : 'pv.productos.'.$empresa.'.'.$year.'.'.md5(strtoupper($cliente));
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && ! empty($cached['ok']) && ! empty($cached['productos'])) {
            return $cached;
        }

        $pack = $this->filasVentasEmpresa($empresa, $year, $todas ? [] : ['CardCode' => $cliente]);
        $map = [];
        foreach ($pack['rows'] as $row) {
            $item = trim((string) ($row['ItemCode'] ?? $row['Itemcode'] ?? ''));
            if ($item === '') {
                continue;
            }
            $itemName = trim((string) ($row['ItemName'] ?? $row['Dscription'] ?? $row['Itemname'] ?? ''));
            $linea = trim((string) ($row['U_LINEA_QV'] ?? $row['Linea'] ?? $row['linea'] ?? ''));
            $key = strtoupper($item);
            if (! isset($map[$key])) {
                $map[$key] = [
                    'codigo' => $item,
                    'nombre' => $itemName !== '' ? $itemName : $item,
                    'empresa' => $empresa,
                    'grupo' => $linea,
                    'grupo_id' => $linea,
                    'costo' => $this->costoVenta($row),
                ];
            } else {
                $costo = $this->costoVenta($row);
                if ($costo > 0) {
                    $map[$key]['costo'] = $costo;
                }
                if ($map[$key]['nombre'] === $item && $itemName !== '') {
                    $map[$key]['nombre'] = $itemName;
                }
            }
        }

        $productos = array_values($map);
        usort($productos, function ($a, $b) {
            return strcasecmp((string) ($a['nombre'] ?? ''), (string) ($b['nombre'] ?? ''))
                ?: strcasecmp((string) ($a['codigo'] ?? ''), (string) ($b['codigo'] ?? ''));
        });

        $payload = [
            'ok' => ! empty($pack['ok']),
            'productos' => $productos,
            'mensaje' => ! empty($pack['ok'])
                ? ($productos ? null : ($todas
                    ? 'Esta empresa no tiene productos en ventas '.$year
                    : 'Este cliente no tiene productos en ventas '.$year))
                : ($pack['mensaje'] ?? 'Sin productos SAP'),
        ];
        if (! empty($payload['ok']) && $productos) {
            Cache::put($cacheKey, $payload, 1800);
        }

        return $payload;
    }

    /**
     * @return array{ok: bool, cuentas: array<int, array<string, mixed>>, agrupaciones: array<int, array<string, mixed>>, mensaje: string|null}
     */
    protected function cargarCuentasEmpresa(string $empresa, bool $todas = false, string $groupMask = '', string $cliente = '', int $year = 0): array
    {
        if ($year < 2000) {
            $year = (int) date('Y');
        }
        $pack = $this->cargarProductosCliente($empresa, $cliente, $year, $todas);

        return [
            'ok' => $pack['ok'],
            'cuentas' => $pack['productos'],
            'agrupaciones' => [],
            'mensaje' => $pack['mensaje'],
        ];
    }

    /**
     * Clientes SAP de la empresa (CardCode únicos en ventas del año de referencia).
     *
     * @return array{ok: bool, clientes: array<int, array<string, mixed>>, mensaje: string|null}
     */
    protected function cargarClientesEmpresa(string $empresa, int $year): array
    {
        $empresa = strtolower(trim($empresa));
        if ($year < 2000) {
            $year = (int) date('Y');
        }
        $cacheKey = 'pv.clientes.'.$empresa.'.'.$year;
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && ! empty($cached['ok']) && ! empty($cached['clientes'])) {
            return $cached;
        }

        $empresasFiltro = $this->empresasFiltroVentas($empresa);
        $api = app(AutinApiClient::class);
        $map = [];
        $ok = false;
        $mensaje = null;

        foreach ($empresasFiltro as $empFiltro) {
            $pack = $api->ventasTodasPaginas([
                'year' => $year,
                'Empresa' => $empFiltro,
            ], 30, 6);
            if (empty($pack['ok'])) {
                if (! $ok) {
                    $mensaje = $pack['message'] ?? 'Sin clientes SAP';
                }
                continue;
            }
            $ok = true;
            foreach ($pack['rows'] as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $card = trim((string) ($row['CardCode'] ?? $row['Cardcode'] ?? ''));
                if ($card === '') {
                    continue;
                }
                $name = trim((string) ($row['CardName'] ?? $row['Cardname'] ?? ''));
                $key = strtoupper($card);
                if (! isset($map[$key])) {
                    $map[$key] = [
                        'codigo' => $card,
                        'nombre' => $name !== '' ? $name : $card,
                        'empresa' => $empresa,
                        'activo' => true,
                        'departamento' => '',
                    ];
                } elseif (($map[$key]['nombre'] === $map[$key]['codigo']) && $name !== '') {
                    $map[$key]['nombre'] = $name;
                }
            }
        }

        $clientes = array_values($map);
        usort($clientes, function ($a, $b) {
            return strcasecmp((string) ($a['nombre'] ?? ''), (string) ($b['nombre'] ?? ''))
                ?: strcasecmp((string) ($a['codigo'] ?? ''), (string) ($b['codigo'] ?? ''));
        });

        $payload = [
            'ok' => $ok,
            'clientes' => $clientes,
            'mensaje' => $ok ? ($clientes ? null : 'No hay clientes con venta en '.$year) : $mensaje,
        ];
        if ($ok && $clientes) {
            Cache::put($cacheKey, $payload, 1800);
        }

        return $payload;
    }

    /**
     * @return array{ok: bool, clientes: array<int, array<string, mixed>>, productos: array<int, array<string, mixed>>, agrupaciones: array<int, array<string, mixed>>, empresas: array<int, string>, mensaje: string|null}
     */
    protected function cargarVentasCatalogo(?string $empresa, int $year, int $maxPages = 12, string $cliente = ''): array
    {
        $cacheKey = 'pv.cat.'.$year.'.'.strtolower((string) $empresa).'.'.md5($cliente).'.'.$maxPages;
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && ! empty($cached['ok'])) {
            return $cached;
        }

        $api = app(AutinApiClient::class);
        $clientes = [];
        $productos = [];
        $empresas = [];
        $ok = false;
        $mensaje = null;
        $perPage = 200;

        for ($page = 1; $page <= $maxPages; $page++) {
            $filtros = [
                'year' => $year,
                'per_page' => $perPage,
                'page' => $page,
            ];
            if ($empresa) {
                $filtros['Empresa'] = strtoupper($empresa);
            }
            if ($cliente !== '') {
                $filtros['CardCode'] = $cliente;
            }
            $res = $api->ventas($filtros);
            if (empty($res['ok'])) {
                if ($page === 1) {
                    $mensaje = $res['message'] ?? 'Sin conexión a ventas SAP';
                }
                break;
            }
            $ok = true;
            $body = is_array($res['body'] ?? null) ? $res['body'] : [];
            $rows = $body['data'] ?? [];
            if (! is_array($rows) || ! $rows) {
                break;
            }
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $emp = strtolower(trim((string) ($row['Empresa'] ?? $row['empresa'] ?? $empresa ?? '')));
                if ($emp !== '') {
                    $empresas[$emp] = $emp;
                }
                $card = trim((string) ($row['CardCode'] ?? $row['Cardcode'] ?? ''));
                $cardName = trim((string) ($row['CardName'] ?? $row['Cardname'] ?? ''));
                if ($card !== '') {
                    $ck = strtoupper($emp).'|'.strtoupper($card);
                    if (! isset($clientes[$ck])) {
                        $clientes[$ck] = [
                            'codigo' => $card,
                            'nombre' => $cardName !== '' ? $cardName : $card,
                            'empresa' => $emp,
                            'activo' => true,
                            'departamento' => '',
                        ];
                    }
                }
                $item = trim((string) ($row['ItemCode'] ?? $row['Itemcode'] ?? ''));
                if ($item === '') {
                    continue;
                }
                $itemName = trim((string) ($row['ItemName'] ?? $row['Dscription'] ?? $row['Itemname'] ?? ''));
                $linea = trim((string) ($row['U_LINEA_QV'] ?? $row['Linea'] ?? $row['linea'] ?? ''));
                $pk = strtoupper($emp).'|'.strtoupper($item);
                if (! isset($productos[$pk])) {
                    $productos[$pk] = [
                        'codigo' => $item,
                        'nombre' => $itemName !== '' ? $itemName : $item,
                        'empresa' => $emp,
                        'grupo' => $linea,
                        'grupo_id' => $linea,
                        'costo' => $this->costoVenta($row),
                    ];
                } else {
                    $costo = $this->costoVenta($row);
                    if ($costo > 0) {
                        $productos[$pk]['costo'] = $costo;
                    }
                    if ($productos[$pk]['nombre'] === $item && $itemName !== '') {
                        $productos[$pk]['nombre'] = $itemName;
                    }
                }
            }
            $pag = $this->paginacionDe($body);
            if ($pag['last_page'] > 0 && $page >= $pag['last_page']) {
                break;
            }
            if ($pag['last_page'] < 1 && count($rows) < $perPage) {
                break;
            }
        }

        $payload = [
            'ok' => $ok,
            'clientes' => array_values($clientes),
            'productos' => array_values($productos),
            'agrupaciones' => [],
            'empresas' => array_values($empresas),
            'mensaje' => $mensaje,
        ];
        if ($ok) {
            Cache::put($cacheKey, $payload, 600);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function cantidadVenta(array $row): float
    {
        $qty = $this->numeroVenta($row, ['Quantity', 'Cantidad', 'Qty', 'quantity']);
        if ($this->signoDocVenta($row) < 0) {
            return -1 * abs($qty);
        }

        return $qty;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    protected function importeVenta(array $row, array $keys): float
    {
        $v = $this->numeroVenta($row, $keys);
        if ($this->signoDocVenta($row) < 0) {
            return -1 * abs($v);
        }

        return $v;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function signoDocVenta(array $row): float
    {
        $tipo = strtoupper(trim((string) ($row['Tipo_Doc'] ?? $row['DocType'] ?? $row['tipo'] ?? 'VENTA')));
        if (in_array($tipo, ['NC', 'NOTA', 'CREDIT', 'C'], true)) {
            return -1.0;
        }

        return 1.0;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    protected function numeroVenta(array $row, array $keys): float
    {
        foreach ($keys as $k) {
            if (isset($row[$k]) && is_numeric($row[$k])) {
                return (float) $row[$k];
            }
        }

        return 0.0;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function costoVenta(array $row): float
    {
        foreach (['StockPrice', 'Costo', 'costo', 'GrossBuyPrice', 'Price', 'Precio', 'UnitPrice'] as $k) {
            if (isset($row[$k]) && is_numeric($row[$k]) && (float) $row[$k] != 0.0) {
                return (float) $row[$k];
            }
        }

        return 0.0;
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
            $codigo = (string) ($row['CardCode'] ?? $row['PrcCode'] ?? $row['CC'] ?? $row['OcrCode'] ?? $row['codigo'] ?? '');
            $nombre = (string) ($row['CardName'] ?? $row['PrcName'] ?? $row['NOMBRE'] ?? $row['nombre'] ?? '');
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
            $codigo = trim((string) ($row['ItemCode'] ?? $row['FormatCode'] ?? $row['CUENTA'] ?? $row['Cuenta'] ?? $row['AcctCode'] ?? $row['codigo'] ?? ''));
            $nombre = (string) ($row['ItemName'] ?? $row['Dscription'] ?? $row['AcctName'] ?? $row['NOMBRE'] ?? $row['nombre'] ?? '');
            if ($codigo === '' && $nombre === '') {
                continue;
            }
            $linea = (string) ($row['U_LINEA_QV'] ?? $row['GroupName'] ?? $row['agrupacion'] ?? $row['grupo'] ?? '');
            $out[] = [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'empresa' => (string) ($row['Empresa'] ?? $row['empresa'] ?? ''),
                'grupo' => $linea,
                'grupo_id' => (string) ($row['GroupMask'] ?? $row['grupo_id'] ?? $linea),
                'costo' => $this->costoVenta($row),
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
        if (! Schema::hasTable('tbl_pv_tipos_permiso')) {
            return [
                ['clave' => 'capturar', 'nombre' => 'Capturar', 'descripcion' => $copy['capturar']],
                ['clave' => 'editar', 'nombre' => 'Editar', 'descripcion' => $copy['editar']],
                ['clave' => 'revisar', 'nombre' => 'Revisar', 'descripcion' => $copy['revisar']],
                ['clave' => 'importar', 'nombre' => 'Importar masivo', 'descripcion' => $copy['importar']],
            ];
        }

        return PvTipoPermiso::query()->orderBy('orden')->get(['id', 'clave', 'nombre', 'descripcion'])
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
            'capturar' => 'Puede capturar proyección mientras el ciclo esté Abierto',
            'editar' => 'Puede modificar cantidades cuando el ciclo está En revisión o Cerrado',
            'revisar' => 'Puede consultar y revisar sin editar',
            'importar' => 'Puede descargar plantilla e importar proyecciones de todos sus clientes. Aplica al usuario en todo el ciclo.',
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
        if (Schema::hasTable('tbl_pv_usuario_permisos') && Schema::hasTable('tbl_pv_tipos_permiso')) {
            $tipo = PvTipoPermiso::query()->where('clave', 'importar')->first();
            if ($tipo) {
                $ids = PvUsuarioPermiso::query()
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
        if (Schema::hasTable('tbl_pv_asignaciones')) {
            $centros = PvAsignacion::query()
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
            $has = Schema::hasTable('tbl_pv_asignaciones')
                && Schema::hasColumn('tbl_pv_asignaciones', 'es_principal');
        }

        return $has;
    }

    protected function buscarPrincipal(string $ciclo, string $empresa, string $centro): ?PvAsignacion
    {
        $q = PvAsignacion::query()
            ->where('ciclo_codigo', $ciclo)
            ->where('empresa', $empresa)
            ->where('cliente_codigo', $centro);
        if ($this->asigHasRolColumns()) {
            $q->where('es_principal', true);
        }

        return $q->orderBy('id')->first();
    }

    protected function principalDeAsignacion(PvAsignacion $asig): PvAsignacion
    {
        if ($this->asigHasRolColumns()) {
            if ($asig->es_principal) {
                return $asig;
            }
            if ($asig->parent_id) {
                $p = PvAsignacion::query()->find($asig->parent_id);
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
    protected function syncCuentas(PvAsignacion $asig, array $cuentas): void
    {
        PvAsignacionProducto::query()->where('asignacion_id', $asig->id)->delete();
        foreach ($cuentas as $cta) {
            PvAsignacionProducto::query()->create([
                'asignacion_id' => $asig->id,
                'producto_codigo' => $cta['codigo'] ?? $cta['cuenta_codigo'] ?? '',
                'producto_nombre' => $cta['nombre'] ?? $cta['cuenta_nombre'] ?? null,
                'linea' => $cta['agrupacion'] ?? null,
            ]);
        }
    }

    /**
     * @param  array<int, string>  $claves
     */
    protected function syncPermisosClaves(PvAsignacion $asig, array $claves): void
    {
        $claves = array_values(array_unique(array_filter(array_map('strval', $claves))));
        $clavesAsig = array_values(array_filter($claves, function ($c) {
            return $c !== 'importar';
        }));

        PvAsignacionPermiso::query()->where('asignacion_id', $asig->id)->delete();
        if (Schema::hasTable('tbl_pv_tipos_permiso')) {
            $tipos = PvTipoPermiso::query()->whereIn('clave', $clavesAsig)->get();
            foreach ($tipos as $tipo) {
                PvAsignacionPermiso::query()->create([
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

        if (Schema::hasTable('tbl_pv_usuario_permisos') && Schema::hasTable('tbl_pv_tipos_permiso')) {
            $tipo = PvTipoPermiso::query()->where('clave', $clave)->first();
            if (! $tipo) {
                return;
            }
            $q = PvUsuarioPermiso::query()
                ->where('ciclo_codigo', $ciclo)
                ->where('user_id', $userId)
                ->where('permiso_id', $tipo->id);
            if ($enabled) {
                if (! $q->exists()) {
                    PvUsuarioPermiso::query()->create([
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

        if (! $enabled || ! Schema::hasTable('tbl_pv_tipos_permiso')) {
            return;
        }
        $tipo = PvTipoPermiso::query()->where('clave', $clave)->first();
        if (! $tipo) {
            return;
        }
        $asigs = PvAsignacion::query()
            ->where('ciclo_codigo', $ciclo)
            ->where('user_id', $userId)
            ->get();
        foreach ($asigs as $a) {
            PvAsignacionPermiso::query()->firstOrCreate([
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
        if (array_key_exists($cacheKey, $this->pvUserPermCache)) {
            return $this->pvUserPermCache[$cacheKey];
        }

        $found = false;
        if (Schema::hasTable('tbl_pv_usuario_permisos') && Schema::hasTable('tbl_pv_tipos_permiso')) {
            $tipo = PvTipoPermiso::query()->where('clave', $clave)->first();
            if ($tipo && PvUsuarioPermiso::query()
                ->whereRaw('UPPER(ciclo_codigo) = ?', [strtoupper($ciclo)])
                ->where('user_id', $userId)
                ->where('permiso_id', $tipo->id)
                ->exists()) {
                $found = true;
            }
        }

        if (! $found && Schema::hasTable('tbl_pv_asignaciones')) {
            $asigs = PvAsignacion::query()
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

        $this->pvUserPermCache[$cacheKey] = $found;

        return $found;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function cuentasDe(PvAsignacion $asig): array
    {
        return $asig->cuentas()->get()->map(function ($c) {
            return [
                'codigo' => $c->cuenta_codigo,
                'nombre' => $c->cuenta_nombre,
                'agrupacion' => $c->agrupacion,
            ];
        })->values()->all();
    }

    protected function propagarCuentasAColaboradores(PvAsignacion $asig): void
    {
        $principal = $this->principalDeAsignacion($asig);
        if ((int) $principal->id !== (int) $asig->id && ! ($this->asigHasRolColumns() && $asig->es_principal)) {
            return;
        }
        $cuentas = $this->cuentasDe($asig);
        $extras = PvAsignacion::query()
            ->where('ciclo_codigo', $asig->ciclo_codigo)
            ->where('empresa', $asig->empresa)
            ->where('cliente_codigo', $asig->centro_codigo)
            ->where('id', '!=', $asig->id)
            ->get();
        foreach ($extras as $extra) {
            $this->syncCuentas($extra, $cuentas);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $accesos
     */
    protected function syncAccesos(PvAsignacion $asig, array $accesos): void
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
            $col = PvAsignacion::query()->firstOrNew([
                'ciclo_codigo' => $principal->ciclo_codigo,
                'empresa' => $principal->empresa,
                'user_id' => $uid,
                'cliente_codigo' => $principal->centro_codigo,
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

        $extras = PvAsignacion::query()
            ->where('ciclo_codigo', $principal->ciclo_codigo)
            ->where('empresa', $principal->empresa)
            ->where('cliente_codigo', $principal->centro_codigo)
            ->whereNotIn('user_id', $keepIds)
            ->get();
        foreach ($extras as $extra) {
            $this->borrarAsignacion($extra);
        }
    }

    protected function borrarAsignacion(PvAsignacion $asig): void
    {
        PvAsignacionProducto::query()->where('asignacion_id', $asig->id)->delete();
        PvAsignacionPermiso::query()->where('asignacion_id', $asig->id)->delete();
        $asig->delete();
    }

    /**
     * @return array<string, mixed>
     */
    protected function asignacionPayload(PvAsignacion $a): array
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
        if (! Schema::hasTable('tbl_pv_ciclos')) {
            return [];
        }

        $statsMap = $this->cicloStatsByCodigo();

        return PvCiclo::query()->orderByDesc('anio_presupuesto')->orderByDesc('id')->get()
            ->map(function (PvCiclo $c) use ($statsMap) {
                return $this->cicloPayload($c, $statsMap[$c->codigo] ?? $this->emptyCicloStats());
            })->values()->all();
    }

    /**
     * @param  array<string, int>|null  $stats
     * @return array<string, mixed>
     */
    protected function cicloPayload(PvCiclo $c, ?array $stats = null): array
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
            'tipoCambioMeses' => $this->normalizeTipoCambioMeses(
                Schema::hasColumn('tbl_pv_ciclos', 'tipo_cambio_meses') ? $c->tipo_cambio_meses : null,
                (float) ($c->tipo_cambio ?: 20)
            ),
            'tipoBudget' => $this->normalizeTipoBudget(
                Schema::hasColumn('tbl_pv_ciclos', 'tipo_budget') ? $c->tipo_budget : null
            ),
            'observaciones' => $c->observaciones,
            'asignaciones' => (int) ($stats['asignaciones'] ?? 0),
            'cuentas' => (int) ($stats['cuentas'] ?? 0),
            'usuarios' => (int) ($stats['usuarios'] ?? 0),
            'centros' => (int) ($stats['centros'] ?? 0),
        ];
    }

    /**
     * @param  mixed  $meses
     * @return array<int, float>
     */
    protected function normalizeTipoCambioMeses($meses, float $fallback = 20.0): array
    {
        $base = $fallback > 0 ? $fallback : 20.0;
        $out = [];
        $src = is_array($meses) ? array_values($meses) : [];
        for ($i = 0; $i < 12; $i++) {
            $v = isset($src[$i]) ? (float) $src[$i] : $base;
            $out[] = $v > 0 ? round($v, 4) : $base;
        }

        return $out;
    }

    protected function normalizeTipoBudget($tipo): ?string
    {
        $t = trim((string) $tipo);
        if (in_array($t, ['3+9', '6+6', '9+3'], true)) {
            return $t;
        }

        return null;
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
        if (! Schema::hasTable('tbl_pv_asignaciones')) {
            return [];
        }

        $q = PvAsignacion::query()->select(['id', 'ciclo_codigo', 'user_id', 'empresa', 'cliente_codigo']);
        if ($codigos !== null) {
            $q->whereIn('ciclo_codigo', $codigos);
        }
        $asigs = $q->get();
        if ($asigs->isEmpty()) {
            return [];
        }

        $ctaByAsig = [];
        if (Schema::hasTable('tbl_pv_asignacion_productos')) {
            $ctaByAsig = PvAsignacionProducto::query()
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
