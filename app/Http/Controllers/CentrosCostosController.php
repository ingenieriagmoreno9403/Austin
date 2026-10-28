<?php

namespace App\Http\Controllers;

use App\Exports\CcBudgetXlsxStreamer;
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
use App\Services\CcCatalogoLocal;
use App\Services\CcGastoRealSnapshot;
use App\Traits\MenuTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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

    /** @var array<string, string> */
    protected $formatCodePlantillaCache = [];

    /** @var array<string, bool> */
    protected $codigosSapGuardados = [];

    /** @var array<string, array<string, array<int, string>>> */
    protected $catalogoCuentasPorNombreCache = [];

    /** @var array<string, bool> */
    protected $gastoCopiaEmpresas = [];

    /** @var array<int, array<string, mixed>> */
    protected $ultimasFilasGasto = [];

    protected $forzarGastoRemoto = false;

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
        $localCat = app(CcCatalogoLocal::class);

        return $this->page('analisis', 'CentrosCostos.analisis', [
            'detalleUrl' => route('centros.detalle'),
            // Filtros al instante: solo lo asignado al usuario para revisión/captura.
            'misAsignaciones' => $this->misAsignacionesList(''),
            // No incrustar catálogo entero en el HTML (se hidrata por API local).
            'centros' => [],
            'cuentas' => [],
            'usuarios' => [],
            'catalogoLocal' => $localCat->disponible() && $localCat->tieneDatos(),
        ]);
    }

    public function exportarInformacion()
    {
        return $this->page('exportar', 'CentrosCostos.exportar', [
            'exportExcelUrl' => route('centros.exportar.excel'),
        ]);
    }

    /**
     * Descarga Excel sincrónica (compatibilidad). Preferir start/status/file para evitar 504.
     */
    public function exportarInformacionExcel(Request $request)
    {
        $prepared = $this->prepararExportBudgetGastosParams($request);
        if ($prepared instanceof JsonResponse || $prepared instanceof \Illuminate\Http\RedirectResponse) {
            return $prepared;
        }

        try {
            $this->boostExportBudgetGastosLimits();
            $pack = $this->generarArchivoExportBudgetGastos(
                $prepared['ciclo'],
                $prepared['empresa'],
                null,
                (int) (auth()->id() ?? 0),
                $prepared['formato'],
                $prepared['moneda']
            );

            return response()->download($pack['absolute'], $pack['filename'], [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (Throwable $e) {
            Log::error('Export budget gastos Excel falló', [
                'ciclo' => $prepared['ciclo'],
                'empresa' => $prepared['empresa'],
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->exportBudgetGastosErrorResponse($request, $this->motivoErrorExportBudgetGastos($e), 500);
        }
    }

    /**
     * Deja listo el token. El Excel lo arma el navegador pidiendo tramos cortos
     * (en hosting no corre un proceso en segundo plano ni artisan).
     */
    public function exportarInformacionExcelStart(Request $request): JsonResponse
    {
        $prepared = $this->prepararExportBudgetGastosParams($request);
        if ($prepared instanceof JsonResponse) {
            return $prepared;
        }
        if ($prepared instanceof \Illuminate\Http\RedirectResponse) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al descargar',
                'motivo' => 'Selecciona un ciclo para exportar.',
            ], 422);
        }

        $token = (string) Str::uuid();
        $userId = (int) (auth()->id() ?? 0);
        $suffix = $prepared['empresa'] !== '' ? '_'.$prepared['empresa'] : '';
        if ($prepared['formato'] === 'estructurado') {
            $suffix .= '_estructurado_'.$prepared['moneda'];
        }
        $workDir = storage_path('app'.DIRECTORY_SEPARATOR.'cc_exports'.DIRECTORY_SEPARATOR.$token);
        if (! is_dir($workDir) && ! @mkdir($workDir, 0755, true) && ! is_dir($workDir)) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al descargar',
                'motivo' => 'No se pudo crear la carpeta temporal del Excel.',
            ], 500);
        }

        $meta = [
            'status' => 'pending',
            'pct' => 2,
            'motivo' => null,
            'filename' => 'budget_gastos_'.$prepared['ciclo'].$suffix.'_'.now()->format('Ymd_His').'.xlsx',
            'path' => 'cc_exports/'.$token.'.xlsx',
            'work_dir' => $workDir,
            'offset' => 0,
            'total' => null,
            'user_id' => $userId,
            'ciclo' => $prepared['ciclo'],
            'empresa' => $prepared['empresa'],
            'formato' => $prepared['formato'],
            'moneda' => $prepared['moneda'],
            'created_at' => now()->toIso8601String(),
        ];
        Cache::put($this->exportBudgetGastosCacheKey($token), $meta, now()->addHours(2));

        return response()->json([
            'ok' => true,
            'token' => $token,
            'status' => 'pending',
            'pct' => 2,
            'message' => 'Listo para generar el Excel por partes.',
        ]);
    }

    /**
     * Un tramo del Excel. El navegador lo llama hasta que status = ready.
     */
    public function exportarInformacionExcelStep(string $token): JsonResponse
    {
        $meta = $this->metaExportBudgetGastos($token);
        if ($meta instanceof JsonResponse) {
            return $meta;
        }

        $key = $this->exportBudgetGastosCacheKey($token);
        if (in_array(($meta['status'] ?? ''), ['ready', 'error', 'cancelled'], true)) {
            return response()->json([
                'ok' => ($meta['status'] ?? '') !== 'error',
                'status' => (string) ($meta['status'] ?? 'error'),
                'pct' => (int) ($meta['pct'] ?? 0),
                'offset' => (int) ($meta['offset'] ?? 0),
                'total' => $meta['total'],
                'filename' => $meta['filename'] ?? null,
                'motivo' => $meta['motivo'] ?? null,
            ], ($meta['status'] ?? '') === 'error' ? 500 : 200);
        }

        $workDir = (string) ($meta['work_dir'] ?? '');
        if ($workDir === '' || ! is_dir($workDir)) {
            return response()->json([
                'ok' => false,
                'status' => 'error',
                'motivo' => 'No se encontró la carpeta temporal del Excel. Vuelve a intentarlo.',
            ], 404);
        }

        $lockPath = $workDir.DIRECTORY_SEPARATOR.'lock';
        $lock = fopen($lockPath, 'c');
        if ($lock === false || ! flock($lock, LOCK_EX | LOCK_NB)) {
            if (is_resource($lock)) {
                fclose($lock);
            }

            return response()->json([
                'ok' => true,
                'status' => 'running',
                'pct' => (int) ($meta['pct'] ?? 5),
                'offset' => (int) ($meta['offset'] ?? 0),
                'total' => $meta['total'],
                'motivo' => null,
            ]);
        }

        try {
            @set_time_limit(90);
            $meta = Cache::get($key);
            if (! is_array($meta) || ($meta['status'] ?? '') === 'cancelled') {
                return response()->json([
                    'ok' => true,
                    'status' => 'cancelled',
                    'pct' => 0,
                    'motivo' => 'Descarga cancelada.',
                ]);
            }

            $streamer = new \App\Exports\CcBudgetXlsxStreamer();
            $cuentasPath = $workDir.DIRECTORY_SEPARATOR.'cuentas.json';
            if (! is_file($cuentasPath)) {
                $asignados = $this->listaCuentasAsignadasExportGastos(
                    (string) $meta['ciclo'],
                    ($meta['empresa'] ?? '') !== '' ? (string) $meta['empresa'] : null
                );
                if ($asignados === []) {
                    $meta['status'] = 'error';
                    $meta['pct'] = 0;
                    $meta['motivo'] = 'No hay cuentas asignadas para exportar en este ciclo'
                        .(($meta['empresa'] ?? '') !== '' ? ' / empresa '.$meta['empresa'] : '')
                        .'. Revisa asignaciones o el filtro de empresa.';
                    Cache::put($key, $meta, now()->addHours(2));

                    return response()->json([
                        'ok' => false,
                        'status' => 'error',
                        'pct' => 0,
                        'motivo' => $meta['motivo'],
                    ], 422);
                }

                $json = json_encode($asignados, JSON_UNESCAPED_UNICODE);
                if ($json === false || file_put_contents($cuentasPath, $json) === false) {
                    throw new \RuntimeException('No se pudo guardar la lista de cuentas a exportar.');
                }

                $contexto = $this->contextoExportBudgetGastos((string) $meta['ciclo']);
                $formato = (string) ($meta['formato'] ?? 'normal');
                $estructurado = $formato === 'estructurado';
                if ($estructurado) {
                    $headings = [
                        'COMPANY', 'CC', 'NAME CC', 'CUENTA', 'NAME CUENTA',
                        'ORIGIN', 'TC', 'YEAR',
                        'ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN',
                        'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC',
                        'IMPORTE TOTAL',
                    ];
                    $widths = [12, 12, 28, 14, 36, 10, 10, 8, 12, 12, 12, 12, 12, 12, 12, 12, 12, 12, 12, 12, 16];
                    $textColumns = [1 => true, 2 => true, 3 => true, 4 => true, 5 => true, 6 => true];
                } else {
                    $headings = [
                        'COMPANY', 'CC', 'NAME CC', 'CUENTA', 'NAME CUENTA',
                        'YEAR', 'MONTH', 'ORIGIN', 'IMPORTEUSD', 'IMPORTEMXN', 'TC',
                    ];
                    $widths = [12, 12, 28, 14, 36, 8, 8, 10, 14, 14, 10];
                    $textColumns = [1 => true, 2 => true, 3 => true, 4 => true, 5 => true, 8 => true];
                }
                $streamer->begin($workDir, 'Budget '.$meta['ciclo'], $headings, [
                    'widths' => $widths,
                    'text_columns' => $textColumns,
                ]);
                $meta['anio'] = $contexto['anio'];
                $meta['tc'] = $contexto['tc'];
                $meta['total'] = count($asignados);
                $meta['offset'] = 0;
                $meta['status'] = 'running';
                $meta['pct'] = 8;
                Cache::put($key, $meta, now()->addHours(2));
                $asignados = null;
            }

            $raw = file_get_contents($cuentasPath);
            $asignados = is_string($raw) ? json_decode($raw, true) : null;
            if (! is_array($asignados)) {
                throw new \RuntimeException('No se pudo leer la lista de cuentas a exportar.');
            }

            $total = count($asignados);
            $offset = (int) ($meta['offset'] ?? 0);
            $slice = $this->sliceCuentasExport($asignados, $offset);
            $asignados = null;

            if ($slice !== []) {
                if ($this->exportBudgetGastosFueCancelado($token)) {
                    $streamer->discard($workDir);

                    return response()->json([
                        'ok' => true,
                        'status' => 'cancelled',
                        'pct' => 0,
                        'motivo' => 'Descarga cancelada.',
                    ]);
                }
                $filas = $this->filasExportBudgetGastosSlice(
                    (string) $meta['ciclo'],
                    $slice,
                    (int) ($meta['anio'] ?? 0),
                    (float) ($meta['tc'] ?? 20),
                    (string) ($meta['formato'] ?? 'normal'),
                    (string) ($meta['moneda'] ?? 'MXN'),
                    $workDir
                );
                $streamer->append($workDir, $filas);
                $offset += count($slice);
                $meta['offset'] = $offset;
                $meta['total'] = $total;
                $meta['status'] = 'running';
                $meta['pct'] = $total > 0 ? min(96, 8 + (int) floor(($offset / $total) * 88)) : 96;
                Cache::put($key, $meta, now()->addHours(2));
            }

            if ($offset >= $total) {
                $relative = (string) ($meta['path'] ?? '');
                $absolute = Storage::disk('local')->path($relative);
                $rows = $streamer->finish($workDir, $absolute);
                $streamer->discard($workDir);
                if ($rows <= 0 || ! is_file($absolute)) {
                    throw new \RuntimeException('No se pudo guardar el archivo Excel en disco.');
                }
                $meta['status'] = 'ready';
                $meta['pct'] = 100;
                $meta['rows'] = $rows;
                $meta['motivo'] = null;
                $meta['offset'] = $total;
                Cache::put($key, $meta, now()->addHours(2));
            }

            return response()->json([
                'ok' => true,
                'status' => (string) $meta['status'],
                'pct' => (int) $meta['pct'],
                'offset' => (int) ($meta['offset'] ?? 0),
                'total' => (int) ($meta['total'] ?? 0),
                'filename' => $meta['filename'] ?? null,
                'motivo' => null,
            ]);
        } catch (Throwable $e) {
            if ($this->esCancelacionExportBudgetGastos($e) || $this->exportBudgetGastosFueCancelado($token)) {
                return response()->json([
                    'ok' => true,
                    'status' => 'cancelled',
                    'pct' => 0,
                    'motivo' => 'Descarga cancelada.',
                ]);
            }
            Log::error('Export budget gastos Excel (tramo) falló', [
                'token' => $token,
                'message' => $e->getMessage(),
            ]);
            $meta = Cache::get($key);
            if (is_array($meta) && ($meta['status'] ?? '') !== 'cancelled') {
                $meta['status'] = 'error';
                $meta['pct'] = 0;
                $meta['motivo'] = $this->motivoErrorExportBudgetGastos($e);
                Cache::put($key, $meta, now()->addHours(2));
            }

            return response()->json([
                'ok' => false,
                'status' => 'error',
                'pct' => 0,
                'motivo' => $this->motivoErrorExportBudgetGastos($e),
            ], 500);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function exportarInformacionExcelStatus(string $token): JsonResponse
    {
        $token = trim($token);
        if ($token === '' || ! preg_match('/^[a-f0-9\-]{36}$/i', $token)) {
            return response()->json([
                'ok' => false,
                'status' => 'error',
                'motivo' => 'Token de exportación inválido.',
            ], 422);
        }

        $meta = Cache::get($this->exportBudgetGastosCacheKey($token));
        if (! is_array($meta)) {
            return response()->json([
                'ok' => false,
                'status' => 'error',
                'motivo' => 'La exportación expiró o no existe. Vuelve a intentarlo.',
            ], 404);
        }

        $userId = (int) (auth()->id() ?? 0);
        if (! empty($meta['user_id']) && (int) $meta['user_id'] !== $userId) {
            return response()->json([
                'ok' => false,
                'status' => 'error',
                'motivo' => 'No tienes acceso a esta exportación.',
            ], 403);
        }

        return response()->json([
            'ok' => true,
            'status' => (string) ($meta['status'] ?? 'pending'),
            'pct' => (int) ($meta['pct'] ?? 0),
            'motivo' => $meta['motivo'] ?? null,
            'filename' => $meta['filename'] ?? null,
        ]);
    }

    public function exportarInformacionExcelFile(string $token)
    {
        $token = trim($token);
        if ($token === '' || ! preg_match('/^[a-f0-9\-]{36}$/i', $token)) {
            return $this->exportBudgetGastosErrorResponse(request(), 'Token de exportación inválido.', 422);
        }

        $meta = Cache::get($this->exportBudgetGastosCacheKey($token));
        if (! is_array($meta) || ($meta['status'] ?? '') !== 'ready') {
            return $this->exportBudgetGastosErrorResponse(
                request(),
                is_array($meta) ? (string) ($meta['motivo'] ?: 'El archivo aún no está listo.') : 'La exportación expiró o no existe.',
                409
            );
        }

        $userId = (int) (auth()->id() ?? 0);
        if (! empty($meta['user_id']) && (int) $meta['user_id'] !== $userId) {
            return $this->exportBudgetGastosErrorResponse(request(), 'No tienes acceso a esta exportación.', 403);
        }

        $relative = (string) ($meta['path'] ?? '');
        if ($relative === '' || ! Storage::disk('local')->exists($relative)) {
            return $this->exportBudgetGastosErrorResponse(request(), 'No se encontró el archivo generado.', 404);
        }

        $filename = (string) ($meta['filename'] ?: basename($relative));
        $absolute = Storage::disk('local')->path($relative);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $contentType = $ext === 'csv'
            ? 'text/csv; charset=UTF-8'
            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return response()->download($absolute, $filename, [
            'Content-Type' => $contentType,
        ])->deleteFileAfterSend(true);
    }

    public function exportarInformacionExcelCancel(string $token): JsonResponse
    {
        $token = trim($token);
        if ($token === '' || ! preg_match('/^[a-f0-9\-]{36}$/i', $token)) {
            return response()->json([
                'ok' => false,
                'status' => 'error',
                'motivo' => 'Token de exportación inválido.',
            ], 422);
        }

        $key = $this->exportBudgetGastosCacheKey($token);
        $meta = Cache::get($key);
        if (! is_array($meta)) {
            return response()->json([
                'ok' => true,
                'status' => 'cancelled',
                'message' => 'La exportación ya no existe o expiró.',
            ]);
        }

        $userId = (int) (auth()->id() ?? 0);
        if (! empty($meta['user_id']) && (int) $meta['user_id'] !== $userId) {
            return response()->json([
                'ok' => false,
                'status' => 'error',
                'motivo' => 'No tienes acceso a esta exportación.',
            ], 403);
        }

        $this->borrarArchivoExportBudgetGastos($meta['path'] ?? null);
        if (! empty($meta['work_dir'])) {
            (new \App\Exports\CcBudgetXlsxStreamer())->discard((string) $meta['work_dir']);
        }
        $meta['status'] = 'cancelled';
        $meta['pct'] = 0;
        $meta['motivo'] = 'Descarga cancelada.';
        $meta['filename'] = null;
        $meta['path'] = null;
        Cache::put($key, $meta, now()->addHours(2));

        return response()->json([
            'ok' => true,
            'status' => 'cancelled',
            'message' => 'Descarga cancelada.',
        ]);
    }

    /**
     * @return array<string, mixed>|JsonResponse
     */
    protected function metaExportBudgetGastos(string $token)
    {
        $token = trim($token);
        if ($token === '' || ! preg_match('/^[a-f0-9\-]{36}$/i', $token)) {
            return response()->json([
                'ok' => false,
                'status' => 'error',
                'motivo' => 'Token de exportación inválido.',
            ], 422);
        }

        $meta = Cache::get($this->exportBudgetGastosCacheKey($token));
        if (! is_array($meta)) {
            return response()->json([
                'ok' => false,
                'status' => 'error',
                'motivo' => 'La exportación expiró o no existe. Vuelve a intentarlo.',
            ], 404);
        }

        $userId = (int) (auth()->id() ?? 0);
        if (! empty($meta['user_id']) && (int) $meta['user_id'] !== $userId) {
            return response()->json([
                'ok' => false,
                'status' => 'error',
                'motivo' => 'No tienes acceso a esta exportación.',
            ], 403);
        }

        return $meta;
    }

    /**
     * @return array{anio: int, tc: float}
     */
    protected function contextoExportBudgetGastos(string $ciclo): array
    {
        $cicloCodigo = strtoupper(trim($ciclo));
        $cicloRow = Schema::hasTable('tbl_cc_ciclos')
            ? CcCiclo::query()->whereRaw('UPPER(codigo) = ?', [$cicloCodigo])->first()
            : null;

        $anioBudget = (int) ($cicloRow->anio_presupuesto ?? 0);
        if ($anioBudget < 2000 || $anioBudget > 2100) {
            if (preg_match('/(20\d{2}|19\d{2})/', $cicloCodigo, $m)) {
                $anioBudget = (int) $m[1];
            } else {
                $anioBudget = (int) date('Y');
            }
        }

        $tc = (float) ($cicloRow->tipo_cambio ?? 0);
        if ($tc <= 0) {
            $tc = 20.0;
        }

        return [
            'anio' => $anioBudget,
            'tc' => round($tc, 4),
        ];
    }

    /**
     * Consecutivo viejo (4 dígitos) → FormatCode SAP de 12.
     */
    protected function codigoSapParaExport(string $empresa, string $codigo, string $nombre): string
    {
        $codigo = trim($codigo);
        if ($codigo === '' || ! $this->esCodigoCuentaViejo($codigo)) {
            return $codigo;
        }
        $fmt = $this->formatCodeDeCuentaVieja($empresa, $codigo, $nombre);

        return $fmt !== '' ? $fmt : $codigo;
    }

    /**
     * Deja el FormatCode en asignaciones, presupuestos y grupos. Si el código SAP ya existe, fusiona y quita el consecutivo.
     */
    protected function guardarCodigoSapEnBase(string $empresa, string $viejo, string $sap): void
    {
        $viejo = trim($viejo);
        $sap = trim($sap);
        $empresa = strtoupper(trim($empresa));
        if ($viejo === '' || $empresa === '') {
            return;
        }

        if (in_array($viejo, ['879', '945'], true)) {
            if (isset($this->codigosSapGuardados['INDEMNIZACIONES']) || Cache::get('cc_sap_guardado_INDEMNIZACIONES')) {
                $this->codigosSapGuardados['INDEMNIZACIONES'] = true;

                return;
            }
            $this->codigosSapGuardados['INDEMNIZACIONES'] = true;
            try {
                DB::transaction(function () {
                    $this->guardarParIndemnizacionesSap();
                });
                Cache::put('cc_sap_guardado_INDEMNIZACIONES', 1, now()->addHours(6));
            } catch (Throwable $e) {
                unset($this->codigosSapGuardados['INDEMNIZACIONES']);
                Log::warning('No se pudo guardar el par SAP de indemnizaciones: '.$e->getMessage());
            }

            return;
        }

        if ($sap === '' || $sap === $viejo || $this->esCodigoCuentaViejo($sap)) {
            return;
        }
        $marca = $empresa.'|'.$viejo;
        if (isset($this->codigosSapGuardados[$marca]) || Cache::get('cc_sap_guardado_'.$marca) === $sap) {
            $this->codigosSapGuardados[$marca] = true;

            return;
        }
        $this->codigosSapGuardados[$marca] = true;

        $empresas = ($empresa === 'AUSTIN' || $empresa === 'ABSA') ? ['AUSTIN', 'ABSA'] : [$empresa];

        try {
            DB::transaction(function () use ($empresas, $viejo, $sap) {
                $this->reescribirAsignacionesCodigoSap($empresas, $viejo, $sap);
                $this->reescribirPresupuestosCodigoSap($empresas, $viejo, $sap);
                $this->reescribirGruposCodigoSap($empresas, $viejo, $sap);
            });
            Cache::put('cc_sap_guardado_'.$marca, $sap, now()->addHours(6));
        } catch (Throwable $e) {
            unset($this->codigosSapGuardados[$marca]);
            Log::warning('No se pudo guardar el código SAP '.$sap.' (antes '.$viejo.'): '.$e->getMessage());
        }
    }

    /**
     * @param  array<int, string>  $empresas
     */
    protected function reescribirAsignacionesCodigoSap(array $empresas, string $viejo, string $sap): void
    {
        if (! Schema::hasTable('tbl_cc_asignacion_cuentas') || ! Schema::hasTable('tbl_cc_asignaciones')) {
            return;
        }
        $rows = DB::table('tbl_cc_asignacion_cuentas as c')
            ->join('tbl_cc_asignaciones as a', 'a.id', '=', 'c.asignacion_id')
            ->where('c.cuenta_codigo', $viejo)
            ->where(function ($w) use ($empresas) {
                foreach ($empresas as $e) {
                    $w->orWhereRaw('UPPER(a.empresa) = ?', [$e]);
                }
            })
            ->get(['c.id', 'c.asignacion_id']);

        foreach ($rows as $row) {
            $existe = DB::table('tbl_cc_asignacion_cuentas')
                ->where('asignacion_id', $row->asignacion_id)
                ->where('cuenta_codigo', $sap)
                ->where('id', '!=', $row->id)
                ->exists();
            if ($existe) {
                DB::table('tbl_cc_asignacion_cuentas')->where('id', $row->id)->delete();
            } else {
                DB::table('tbl_cc_asignacion_cuentas')->where('id', $row->id)->update([
                    'cuenta_codigo' => $sap,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * @param  array<int, string>  $empresas
     */
    protected function reescribirPresupuestosCodigoSap(array $empresas, string $viejo, string $sap): void
    {
        if (! Schema::hasTable('tbl_cc_presupuestos')) {
            return;
        }
        $meses = [];
        for ($i = 1; $i <= 12; $i++) {
            $meses[] = 'mes_'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
        }
        $rows = DB::table('tbl_cc_presupuestos')
            ->where('cuenta_codigo', $viejo)
            ->where(function ($w) use ($empresas) {
                foreach ($empresas as $e) {
                    $w->orWhereRaw('UPPER(empresa) = ?', [$e]);
                }
            })
            ->get(array_merge(['id', 'ciclo_codigo', 'empresa', 'centro_codigo'], $meses));

        foreach ($rows as $row) {
            $otro = DB::table('tbl_cc_presupuestos')
                ->where('ciclo_codigo', $row->ciclo_codigo)
                ->where('empresa', $row->empresa)
                ->where('centro_codigo', $row->centro_codigo)
                ->where('cuenta_codigo', $sap)
                ->where('id', '!=', $row->id)
                ->first(array_merge(['id'], $meses));
            if ($otro) {
                $patch = [];
                foreach ($meses as $col) {
                    $dest = $otro->{$col} ?? null;
                    $src = $row->{$col} ?? null;
                    $destVacio = $dest === null || $dest === '' || (float) $dest == 0.0;
                    $srcTiene = $src !== null && $src !== '' && (float) $src != 0.0;
                    if ($destVacio && $srcTiene) {
                        $patch[$col] = $src;
                    }
                }
                if ($patch !== []) {
                    $patch['updated_at'] = now();
                    DB::table('tbl_cc_presupuestos')->where('id', $otro->id)->update($patch);
                }
                DB::table('tbl_cc_presupuestos')->where('id', $row->id)->delete();
            } else {
                DB::table('tbl_cc_presupuestos')->where('id', $row->id)->update([
                    'cuenta_codigo' => $sap,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * @param  array<int, string>  $empresas
     */
    protected function reescribirGruposCodigoSap(array $empresas, string $viejo, string $sap): void
    {
        if (! Schema::hasTable('tbl_cc_grupo_cuentas') || ! Schema::hasTable('tbl_cc_grupos_cuenta')) {
            return;
        }
        $rows = DB::table('tbl_cc_grupo_cuentas as i')
            ->join('tbl_cc_grupos_cuenta as g', 'g.id', '=', 'i.grupo_id')
            ->where('i.cuenta_codigo', $viejo)
            ->where(function ($w) use ($empresas) {
                foreach ($empresas as $e) {
                    $w->orWhereRaw('UPPER(g.empresa) = ?', [$e]);
                }
            })
            ->get(['i.id', 'i.grupo_id']);

        foreach ($rows as $row) {
            $existe = DB::table('tbl_cc_grupo_cuentas')
                ->where('grupo_id', $row->grupo_id)
                ->where('cuenta_codigo', $sap)
                ->where('id', '!=', $row->id)
                ->exists();
            if ($existe) {
                DB::table('tbl_cc_grupo_cuentas')->where('id', $row->id)->delete();
            } else {
                DB::table('tbl_cc_grupo_cuentas')->where('id', $row->id)->update([
                    'cuenta_codigo' => $sap,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * 879 y 945 son el mismo par. Se guardan las dos cuentas SAP y se quitan los consecutivos.
     */
    protected function guardarParIndemnizacionesSap(): void
    {
        $viejos = ['879', '945'];
        $sap = ['600060001013', '600060001024'];
        $now = now();

        $escribir = function () use ($viejos, $sap, $now) {
            if (Schema::hasTable('tbl_cc_asignacion_cuentas')) {
                $asigIds = DB::table('tbl_cc_asignacion_cuentas')
                    ->whereIn('cuenta_codigo', $viejos)
                    ->distinct()
                    ->pluck('asignacion_id');
                foreach ($asigIds as $asigId) {
                    foreach ($sap as $codigo) {
                        $existe = DB::table('tbl_cc_asignacion_cuentas')
                            ->where('asignacion_id', $asigId)
                            ->where('cuenta_codigo', $codigo)
                            ->exists();
                        if ($existe) {
                            continue;
                        }
                        DB::table('tbl_cc_asignacion_cuentas')->insert([
                            'asignacion_id' => $asigId,
                            'cuenta_codigo' => $codigo,
                            'cuenta_nombre' => 'INDEMNIZACIONES',
                            'agrupacion' => 'Gastos',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
                DB::table('tbl_cc_asignacion_cuentas')->whereIn('cuenta_codigo', $viejos)->delete();
            }

            if (Schema::hasTable('tbl_cc_grupo_cuentas')) {
                $grupoIds = DB::table('tbl_cc_grupo_cuentas')
                    ->whereIn('cuenta_codigo', $viejos)
                    ->distinct()
                    ->pluck('grupo_id');
                foreach ($grupoIds as $grupoId) {
                    foreach ($sap as $codigo) {
                        $existe = DB::table('tbl_cc_grupo_cuentas')
                            ->where('grupo_id', $grupoId)
                            ->where('cuenta_codigo', $codigo)
                            ->exists();
                        if ($existe) {
                            continue;
                        }
                        DB::table('tbl_cc_grupo_cuentas')->insert([
                            'grupo_id' => $grupoId,
                            'cuenta_codigo' => $codigo,
                            'cuenta_nombre' => 'INDEMNIZACIONES',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
                DB::table('tbl_cc_grupo_cuentas')->whereIn('cuenta_codigo', $viejos)->delete();
            }
        };
        $escribir();
    }

    /**
     * @param  array{empresa?: string, cuenta?: string, cuenta_nombre?: string}  $item
     */
    protected function exportCuentaNecesitaSap(array $item): bool
    {
        $codigo = trim((string) ($item['cuenta'] ?? ''));
        $nombre = trim((string) ($item['cuenta_nombre'] ?? ''));
        $empresa = strtoupper(trim((string) ($item['empresa'] ?? '')));
        if ($empresa === '' || $nombre === '' || ! $this->esCodigoCuentaViejo($codigo)) {
            return false;
        }
        if ($this->formatCodeDesdeCatalogoLocal($empresa, $nombre) !== '') {
            return false;
        }
        $cacheKey = $empresa.'|'.$codigo.'|'.mb_strtoupper($nombre);
        if (array_key_exists($cacheKey, $this->formatCodePlantillaCache)) {
            return false;
        }
        $guardado = Cache::get('cc_fmt_v1_'.$cacheKey);

        return ! (is_string($guardado) && $guardado !== '');
    }

    /**
     * @param  array<int, array{empresa?: string, cuenta?: string, cuenta_nombre?: string}>  $asignados
     * @return array<int, array<string, mixed>>
     */
    protected function sliceCuentasExport(array $asignados, int $offset): array
    {
        $total = count($asignados);
        $max = 80;
        $apiTope = 1;
        $api = 0;
        $slice = [];
        for ($i = $offset; $i < $total && count($slice) < $max; $i++) {
            $item = $asignados[$i];
            $necesita = $this->exportCuentaNecesitaSap($item);
            if ($necesita && $api >= $apiTope && $slice !== []) {
                break;
            }
            if ($necesita) {
                $api++;
            }
            $slice[] = $item;
        }

        return $slice;
    }

    /**
     * @param  array<string, array{meses: array<int, float|null>, cuenta_nombre: string}>  $pptoByKey
     * @return array{meses: array<int, float|null>, cuenta_nombre: string}|null
     */
    protected function presupuestoExportDeCuenta(array $pptoByKey, string $empresa, string $centro, string $cuenta, string $nombre): ?array
    {
        foreach ($this->clavesCuentaCaptura($empresa, $centro, $cuenta, $nombre) as $key) {
            if (isset($pptoByKey[$key])) {
                return $pptoByKey[$key];
            }
        }

        return null;
    }

    /**
     * @param  array<int, array{empresa: string, centro: string, centro_nombre: string, cuenta: string, cuenta_nombre: string}>  $slice
     * @return array<int, array<int, mixed>>
     */
    /**
     * @return array{usados: array<string, bool>, lineas: array<string, bool>}
     */
    protected function estadoImportesExport(?string $workDir): array
    {
        $estado = ['usados' => [], 'lineas' => []];
        if ($workDir === null || $workDir === '') {
            return $estado;
        }
        $path = $workDir.DIRECTORY_SEPARATOR.'importes.json';
        if (! is_file($path)) {
            return $estado;
        }
        $raw = json_decode((string) file_get_contents($path), true);
        if (! is_array($raw)) {
            return $estado;
        }
        foreach ((array) ($raw['usados'] ?? []) as $k) {
            $estado['usados'][(string) $k] = true;
        }
        foreach ((array) ($raw['lineas'] ?? []) as $k) {
            $estado['lineas'][(string) $k] = true;
        }

        return $estado;
    }

    /**
     * @param  array{usados: array<string, bool>, lineas: array<string, bool>}  $estado
     */
    protected function guardarEstadoImportesExport(?string $workDir, array $estado): void
    {
        if ($workDir === null || $workDir === '' || ! is_dir($workDir)) {
            return;
        }
        file_put_contents(
            $workDir.DIRECTORY_SEPARATOR.'importes.json',
            json_encode([
                'usados' => array_keys($estado['usados']),
                'lineas' => array_keys($estado['lineas']),
            ])
        );
    }

    /**
     * El mismo presupuesto (cuenta corta y su código SAP) solo suma una vez.
     *
     * @param  array<string, bool>  $usados
     * @param  array{fila?: string, meses?: array<int, float|null>, cuenta_nombre?: string}|null  $info
     * @return array{fila?: string, meses?: array<int, float|null>, cuenta_nombre?: string}|null
     */
    protected function reclamarPresupuestoExport(array &$usados, ?array $info): ?array
    {
        if ($info === null) {
            return null;
        }
        $fila = (string) ($info['fila'] ?? '');
        if ($fila !== '') {
            if (isset($usados[$fila])) {
                return null;
            }
            $usados[$fila] = true;
        }

        return $info;
    }

    protected function filasExportBudgetGastosSlice(
        string $ciclo,
        array $slice,
        int $anioBudget,
        float $tc,
        string $formato,
        string $moneda,
        ?string $workDir = null
    ): array {
        foreach ($slice as $i => $item) {
            $slice[$i]['cuenta_sap'] = $this->codigoSapParaExport(
                (string) ($item['empresa'] ?? ''),
                (string) ($item['cuenta'] ?? ''),
                (string) ($item['cuenta_nombre'] ?? '')
            );
        }
        $pptoByKey = $this->mapaPresupuestosDeSlice($ciclo, $slice);
        $estructurado = $formato === 'estructurado';
        $enUsd = $estructurado && $moneda === 'USD';
        $estado = $this->estadoImportesExport($workDir);
        $filas = [];
        foreach ($slice as $item) {
            $origen = (string) ($item['cuenta'] ?? '');
            $sap = (string) ($item['cuenta_sap'] ?? $origen);
            $nombre = (string) ($item['cuenta_nombre'] ?? '');
            $empresa = (string) ($item['empresa'] ?? '');
            $centro = (string) ($item['centro'] ?? '');
            $linea = strtoupper($empresa).'|'.trim($centro).'|'.$sap;
            $info = $this->presupuestoExportDeCuenta($pptoByKey, $empresa, $centro, $origen, $nombre);
            if ($info === null && $sap !== $origen) {
                $info = $this->presupuestoExportDeCuenta($pptoByKey, $empresa, $centro, $sap, $nombre);
            }
            $info = $this->reclamarPresupuestoExport($estado['usados'], $info);
            if (isset($estado['lineas'][$linea]) && $info === null) {
                continue;
            }
            $estado['lineas'][$linea] = true;
            $this->guardarCodigoSapEnBase($empresa, $origen, $sap);
            $item['cuenta'] = $sap;
            foreach ($this->filasExportCuenta($item, $info, $anioBudget, $tc, $estructurado, $enUsd) as $fila) {
                $filas[] = $fila;
            }
        }
        $this->guardarEstadoImportesExport($workDir, $estado);

        return $filas;
    }

    /**
     * @param  array{empresa: string, centro: string, centro_nombre?: string, cuenta: string, cuenta_nombre?: string}  $item
     * @param  array{meses: array<int, float|null>, cuenta_nombre: string}|null  $info
     * @return array<int, array<int, mixed>>
     */
    protected function filasExportCuenta(array $item, ?array $info, int $anioBudget, float $tc, bool $estructurado, bool $enUsd): array
    {
        $emp = $item['empresa'];
        $centro = $item['centro'];
        $centroNombre = $item['centro_nombre'] ?? '';
        $cuenta = $item['cuenta'];
        $cuentaNombre = $item['cuenta_nombre'] ?? '';
        $meses = $info['meses'] ?? array_fill(0, 12, null);
        if ($cuentaNombre === '' && ! empty($info['cuenta_nombre'])) {
            $cuentaNombre = $info['cuenta_nombre'];
        }

        if ($estructurado) {
            $row = [$emp, $centro, $centroNombre, $cuenta, $cuentaNombre, 'BUDGET', $tc, $anioBudget];
            $totalMxn = 0.0;
            for ($i = 0; $i < 12; $i++) {
                $raw = $meses[$i] ?? null;
                $importeMxn = ($raw === null || $raw === '') ? 0.0 : $this->ccRound2Money((float) $raw);
                $totalMxn += $importeMxn;
                $row[] = $enUsd ? ($tc > 0 ? ($importeMxn / $tc) : 0.0) : $importeMxn;
            }
            $row[] = $enUsd ? ($tc > 0 ? ($totalMxn / $tc) : 0.0) : $this->ccRound2Money($totalMxn);

            return [$row];
        }

        $rows = [];
        for ($i = 0; $i < 12; $i++) {
            $raw = $meses[$i] ?? null;
            $importeMxn = ($raw === null || $raw === '') ? 0.0 : $this->ccRound2Money((float) $raw);
            $importeUsd = $tc > 0 ? ($importeMxn / $tc) : 0.0;
            $rows[] = [
                $emp,
                $centro,
                $centroNombre,
                $cuenta,
                $cuentaNombre,
                $anioBudget,
                $i + 1,
                'BUDGET',
                $importeUsd,
                $importeMxn,
                $tc,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array{empresa: string, centro: string, cuenta: string, cuenta_nombre?: string}>  $slice
     * @return array<string, array{meses: array<int, float|null>, cuenta_nombre: string}>
     */
    protected function mapaPresupuestosDeSlice(string $cicloCodigo, array $slice): array
    {
        $pptoByKey = [];
        if ($slice === [] || ! Schema::hasTable('tbl_cc_presupuestos')) {
            return $pptoByKey;
        }

        $centros = [];
        $cuentas = [];
        foreach ($slice as $item) {
            $centro = trim((string) ($item['centro'] ?? ''));
            if ($centro !== '') {
                $centros[$centro] = true;
                $alias = ltrim($centro, '0');
                if ($alias !== '' && $alias !== $centro) {
                    $centros[$alias] = true;
                }
            }
            foreach (['cuenta', 'cuenta_sap'] as $campo) {
                $cuenta = trim((string) ($item[$campo] ?? ''));
                if ($cuenta === '') {
                    continue;
                }
                $cuentas[$cuenta] = true;
                $sinCeros = ltrim($cuenta, '0');
                if ($sinCeros !== '' && $sinCeros !== $cuenta) {
                    $cuentas[$sinCeros] = true;
                }
            }
        }
        if ($centros === [] || $cuentas === []) {
            return $pptoByKey;
        }

        $cols = ['empresa', 'centro_codigo', 'cuenta_codigo', 'cuenta_nombre'];
        for ($i = 1; $i <= 12; $i++) {
            $cols[] = 'mes_'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
        }

        $q = DB::table('tbl_cc_presupuestos')
            ->whereRaw('UPPER(ciclo_codigo) = ?', [strtoupper(trim($cicloCodigo))])
            ->whereIn('centro_codigo', array_keys($centros))
            ->whereIn('cuenta_codigo', array_keys($cuentas));

        foreach ($q->select($cols)->cursor() as $row) {
            $emp = strtoupper(trim((string) $row->empresa));
            if ($emp === 'ABSA') {
                $emp = 'AUSTIN';
            }
            $centro = trim((string) $row->centro_codigo);
            $cuenta = trim((string) $row->cuenta_codigo);
            if ($emp === '' || $centro === '' || $cuenta === '') {
                continue;
            }
            $meses = [];
            for ($i = 1; $i <= 12; $i++) {
                $col = 'mes_'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                $val = $row->{$col} ?? null;
                $meses[] = ($val === null || $val === '') ? null : round((float) $val, 2);
            }
            $info = [
                'fila' => $emp.'|'.$centro.'|'.$cuenta,
                'meses' => $meses,
                'cuenta_nombre' => trim((string) ($row->cuenta_nombre ?? '')),
            ];
            foreach ($this->clavesCuentaCaptura($emp, $centro, $cuenta, $info['cuenta_nombre']) as $key) {
                if (! isset($pptoByKey[$key])) {
                    $pptoByKey[$key] = $info;
                }
            }
        }

        return $pptoByKey;
    }

    protected function exportBudgetGastosCacheKey(string $token): string
    {
        return 'cc_export_budget_'.$token;
    }

    protected function exportBudgetGastosFueCancelado(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        $meta = Cache::get($this->exportBudgetGastosCacheKey($token));

        return is_array($meta) && ($meta['status'] ?? '') === 'cancelled';
    }

    protected function esCancelacionExportBudgetGastos(Throwable $e): bool
    {
        return trim($e->getMessage()) === 'EXPORT_CANCELLED';
    }

    protected function borrarArchivoExportBudgetGastos(?string $relative = null, ?string $absolute = null): void
    {
        if ($relative) {
            try {
                if (Storage::disk('local')->exists($relative)) {
                    Storage::disk('local')->delete($relative);
                }
            } catch (Throwable $e) {
                // ignore
            }
        }
        if ($absolute && is_file($absolute)) {
            @unlink($absolute);
        }
    }

    protected function boostExportBudgetGastosLimits(): void
    {
        @set_time_limit(900);
        @ini_set('max_execution_time', '900');
        @ini_set('memory_limit', '512M');
        ignore_user_abort(true);
    }

    /**
     * @return array{ciclo: string, empresa: string, formato: string, moneda: string}|JsonResponse|\Illuminate\Http\RedirectResponse
     */
    protected function prepararExportBudgetGastosParams(Request $request)
    {
        $ciclo = strtoupper(trim((string) $request->get('ciclo', '')));
        if ($ciclo === '') {
            return $this->exportBudgetGastosErrorResponse($request, 'Selecciona un ciclo para exportar.', 422);
        }

        if (! Schema::hasTable('tbl_cc_asignaciones')) {
            return $this->exportBudgetGastosErrorResponse(
                $request,
                'Falta ejecutar la migración de asignaciones (tbl_cc_asignaciones).',
                422
            );
        }

        $empresa = strtoupper(trim((string) $request->get('empresa', '')));
        $alias = ['ABSA' => 'AUSTIN'];
        if (isset($alias[$empresa])) {
            $empresa = $alias[$empresa];
        }

        $formato = strtolower(trim((string) $request->get('formato', 'normal')));
        if (! in_array($formato, ['normal', 'estructurado'], true)) {
            $formato = 'normal';
        }

        $moneda = strtoupper(trim((string) $request->get('moneda', 'MXN')));
        if (! in_array($moneda, ['MXN', 'USD'], true)) {
            $moneda = 'MXN';
        }

        return [
            'ciclo' => $ciclo,
            'empresa' => $empresa,
            'formato' => $formato,
            'moneda' => $moneda,
        ];
    }

    /**
     * Genera .xlsx en streaming (evita OOM de PhpSpreadsheet con cientos de miles de filas).
     *
     * @return array{absolute: string, relative: string, filename: string, rows: int}
     */
    protected function generarArchivoExportBudgetGastos(
        string $ciclo,
        string $empresa,
        ?string $token = null,
        int $userId = 0,
        string $formato = 'normal',
        string $moneda = 'MXN'
    ): array {
        $suffix = $empresa !== '' ? '_'.$empresa : '';
        if ($formato === 'estructurado') {
            $suffix .= '_estructurado_'.$moneda;
        }
        $filename = 'budget_gastos_'.$ciclo.$suffix.'_'.now()->format('Ymd_His').'.xlsx';
        $dir = 'cc_exports';
        if (! Storage::disk('local')->exists($dir)) {
            Storage::disk('local')->makeDirectory($dir);
        }
        $relative = $dir.'/'.($token ?: (string) Str::uuid()).'.xlsx';
        $absolute = Storage::disk('local')->path($relative);

        try {
            $rowCount = $this->escribirXlsxExportBudgetGastos(
                $absolute,
                $ciclo,
                $empresa !== '' ? $empresa : null,
                $userId,
                $formato,
                $moneda,
                $token
            );
        } catch (Throwable $e) {
            $this->borrarArchivoExportBudgetGastos($relative, $absolute);
            throw $e;
        }

        if ($rowCount <= 0) {
            $this->borrarArchivoExportBudgetGastos($relative, $absolute);
            throw new \RuntimeException(
                'No hay cuentas asignadas para exportar en este ciclo'
                .($empresa !== '' ? ' / empresa '.$empresa : '')
                .'. Revisa asignaciones o el filtro de empresa.'
            );
        }

        if (! is_file($absolute)) {
            throw new \RuntimeException('No se pudo guardar el archivo Excel en disco.');
        }

        Log::info('Export budget gastos listo', [
            'ciclo' => $ciclo,
            'empresa' => $empresa,
            'formato' => $formato,
            'moneda' => $moneda,
            'user_id' => $userId,
            'rows' => $rowCount,
            'file' => $relative,
        ]);

        return [
            'absolute' => $absolute,
            'relative' => $relative,
            'filename' => $filename,
            'rows' => $rowCount,
        ];
    }

    /**
     * Escribe el export a XLSX fila a fila (bajo consumo de memoria).
     */
    protected function escribirXlsxExportBudgetGastos(
        string $absolute,
        string $ciclo,
        ?string $empresa,
        int $userId,
        string $formato = 'normal',
        string $moneda = 'MXN',
        ?string $token = null
    ): int {
        $cicloCodigo = strtoupper(trim($ciclo));
        $cicloRow = Schema::hasTable('tbl_cc_ciclos')
            ? CcCiclo::query()->whereRaw('UPPER(codigo) = ?', [$cicloCodigo])->first()
            : null;

        $anioBudget = (int) ($cicloRow->anio_presupuesto ?? 0);
        if ($anioBudget < 2000 || $anioBudget > 2100) {
            if (preg_match('/(20\d{2}|19\d{2})/', $cicloCodigo, $m)) {
                $anioBudget = (int) $m[1];
            } else {
                $anioBudget = (int) date('Y');
            }
        }

        $tc = (float) ($cicloRow->tipo_cambio ?? 0);
        if ($tc <= 0) {
            $tc = 20.0;
        }
        $tc = round($tc, 4);

        if ($userId <= 0) {
            $userId = (int) (auth()->id() ?? 0);
        }

        $asignados = $this->listaCuentasAsignadasExportGastos(
            $cicloCodigo,
            ($empresa !== null && $empresa !== '') ? $empresa : null
        );
        if ($asignados === []) {
            return 0;
        }

        $pptoByKey = $this->mapaPresupuestosExportGastos(
            $cicloCodigo,
            ($empresa !== null && $empresa !== '') ? $empresa : null
        );

        $estructurado = $formato === 'estructurado';
        $enUsd = $estructurado && $moneda === 'USD';

        if ($estructurado) {
            $headings = [
                'COMPANY', 'CC', 'NAME CC', 'CUENTA', 'NAME CUENTA',
                'ORIGIN', 'TC', 'YEAR',
                'ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN',
                'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC',
                'IMPORTE TOTAL',
            ];
            $widths = [12, 12, 28, 14, 36, 10, 10, 8, 12, 12, 12, 12, 12, 12, 12, 12, 12, 12, 12, 12, 16];
            $textColumns = [1 => true, 2 => true, 3 => true, 4 => true, 5 => true, 6 => true];
        } else {
            $headings = [
                'COMPANY', 'CC', 'NAME CC', 'CUENTA', 'NAME CUENTA',
                'YEAR', 'MONTH', 'ORIGIN', 'IMPORTEUSD', 'IMPORTEMXN', 'TC',
            ];
            $widths = [12, 12, 28, 14, 36, 8, 8, 10, 14, 14, 10];
            $textColumns = [1 => true, 2 => true, 3 => true, 4 => true, 5 => true, 8 => true];
        }

        $rowGenerator = function () use ($asignados, $pptoByKey, $anioBudget, $tc, $estructurado, $enUsd) {
            $usados = [];
            $lineas = [];
            foreach ($asignados as $item) {
                $emp = $item['empresa'];
                $centro = $item['centro'];
                $centroNombre = $item['centro_nombre'];
                $origen = $item['cuenta'];
                $cuentaNombre = $item['cuenta_nombre'];

                $info = $this->presupuestoExportDeCuenta($pptoByKey, $emp, $centro, $origen, $cuentaNombre);
                $cuenta = $this->codigoSapParaExport($emp, $origen, $cuentaNombre);
                if ($info === null && $cuenta !== $origen) {
                    $info = $this->presupuestoExportDeCuenta($pptoByKey, $emp, $centro, $cuenta, $cuentaNombre);
                }
                $info = $this->reclamarPresupuestoExport($usados, $info);
                $linea = strtoupper($emp).'|'.trim($centro).'|'.$cuenta;
                if (isset($lineas[$linea]) && $info === null) {
                    continue;
                }
                $lineas[$linea] = true;
                $this->guardarCodigoSapEnBase($emp, $origen, $cuenta);

                $meses = $info['meses'] ?? array_fill(0, 12, null);
                if ($cuentaNombre === '' && ! empty($info['cuenta_nombre'])) {
                    $cuentaNombre = $info['cuenta_nombre'];
                }

                if ($estructurado) {
                    $row = [
                        $emp,
                        $centro,
                        $centroNombre,
                        $cuenta,
                        $cuentaNombre,
                        'BUDGET',
                        $tc,
                        $anioBudget,
                    ];
                    $totalMxn = 0.0;
                    for ($i = 0; $i < 12; $i++) {
                        $raw = $meses[$i] ?? null;
                        $importeMxn = ($raw === null || $raw === '') ? 0.0 : $this->ccRound2Money((float) $raw);
                        $totalMxn += $importeMxn;
                        $row[] = $enUsd
                            ? ($tc > 0 ? ($importeMxn / $tc) : 0.0)
                            : $importeMxn;
                    }
                    $row[] = $enUsd
                        ? ($tc > 0 ? ($totalMxn / $tc) : 0.0)
                        : $this->ccRound2Money($totalMxn);
                    yield $row;
                    continue;
                }

                for ($i = 0; $i < 12; $i++) {
                    $raw = $meses[$i] ?? null;
                    // MXN como en captura (2 decimales).
                    $importeMxn = ($raw === null || $raw === '') ? 0.0 : $this->ccRound2Money((float) $raw);
                    // USD = MXN/TC sin redondear a centavos por fila.
                    // Si se redondea cada mes a 2 decimales, la SUMA del Excel no coincide
                    // con el total del sistema (que hace money(totMxn) = round2(totMxn/TC) una vez).
                    $importeUsd = $tc > 0 ? ($importeMxn / $tc) : 0.0;

                    yield [
                        $emp,
                        $centro,
                        $centroNombre,
                        $cuenta,
                        $cuentaNombre,
                        $anioBudget,
                        $i + 1,
                        'BUDGET',
                        $importeUsd,
                        $importeMxn,
                        $tc,
                    ];
                }
            }
        };

        $streamer = new CcBudgetXlsxStreamer();

        return $streamer->write(
            $absolute,
            'Budget '.$cicloCodigo,
            $headings,
            $rowGenerator(),
            [
                'widths' => $widths,
                'text_columns' => $textColumns,
                'should_abort' => function () use ($token) {
                    return $this->exportBudgetGastosFueCancelado($token);
                },
            ]
        );
    }

    /**
     * @return array<string, array{meses: array<int, float|null>, cuenta_nombre: string}>
     */
    protected function mapaPresupuestosExportGastos(string $cicloCodigo, ?string $empresa = null): array
    {
        $pptoByKey = [];
        if (! Schema::hasTable('tbl_cc_presupuestos')) {
            return $pptoByKey;
        }

        $cols = ['empresa', 'centro_codigo', 'cuenta_codigo', 'cuenta_nombre'];
        for ($i = 1; $i <= 12; $i++) {
            $cols[] = 'mes_'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
        }

        $q = DB::table('tbl_cc_presupuestos')->whereRaw('UPPER(ciclo_codigo) = ?', [$cicloCodigo]);
        if ($empresa !== null && $empresa !== '') {
            $emp = strtoupper(trim($empresa));
            $empresas = [$emp];
            if ($emp === 'AUSTIN') {
                $empresas[] = 'ABSA';
            } elseif ($emp === 'ABSA') {
                $empresas[] = 'AUSTIN';
            }
            $q->where(function ($w) use ($empresas) {
                foreach ($empresas as $e) {
                    $w->orWhereRaw('UPPER(empresa) = ?', [$e]);
                }
            });
        }

        foreach ($q->select($cols)->cursor() as $row) {
            $emp = strtoupper(trim((string) $row->empresa));
            if ($emp === 'ABSA') {
                $emp = 'AUSTIN';
            }
            $centro = trim((string) $row->centro_codigo);
            $cuenta = trim((string) $row->cuenta_codigo);
            if ($emp === '' || $centro === '' || $cuenta === '') {
                continue;
            }
            $meses = [];
            for ($i = 1; $i <= 12; $i++) {
                $col = 'mes_'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                $val = $row->{$col} ?? null;
                $meses[] = ($val === null || $val === '') ? null : round((float) $val, 2);
            }
            $nombre = trim((string) ($row->cuenta_nombre ?? ''));
            $info = [
                'fila' => $emp.'|'.$centro.'|'.$cuenta,
                'meses' => $meses,
                'cuenta_nombre' => $nombre,
            ];
            foreach ($this->clavesCuentaCaptura($emp, $centro, $cuenta, $nombre) as $key) {
                if (! isset($pptoByKey[$key])) {
                    $pptoByKey[$key] = $info;
                }
            }
        }

        return $pptoByKey;
    }

    /**
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    protected function exportBudgetGastosErrorResponse(Request $request, string $motivo, int $status = 500)
    {
        $wantsJson = $request->expectsJson()
            || $request->ajax()
            || str_contains(strtolower((string) $request->header('Accept', '')), 'application/json')
            || $request->header('X-Requested-With') === 'XMLHttpRequest';

        if ($wantsJson) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al descargar',
                'motivo' => $motivo,
            ], $status);
        }

        return redirect()
            ->route('centros.exportar')
            ->with('error_msg', 'Error al descargar: '.$motivo);
    }

    protected function motivoErrorExportBudgetGastos(Throwable $e): string
    {
        if ($this->esCancelacionExportBudgetGastos($e)) {
            return 'Descarga cancelada.';
        }

        $msg = trim((string) $e->getMessage());
        $lower = strtolower($msg);

        if (
            str_contains($lower, 'maximum execution time')
            || str_contains($lower, 'max_execution_time')
            || str_contains($lower, 'execution time')
            || str_contains($lower, 'timed out')
            || str_contains($lower, 'timeout')
        ) {
            return 'Se agotó el tiempo de ejecución al generar el Excel. Vuelve a intentar; si persiste, exporta por empresa.';
        }

        if (
            str_contains($lower, 'allowed memory size')
            || str_contains($lower, 'out of memory')
            || str_contains($lower, 'memory')
        ) {
            return 'Se agotó la memoria del servidor al generar el Excel. Intenta exportar filtrando por una empresa.';
        }

        if ($msg === '') {
            return 'Error interno al generar el archivo ('.class_basename($e).').';
        }

        if (mb_strlen($msg) > 280) {
            $msg = mb_substr($msg, 0, 277).'…';
        }

        return $msg;
    }

    /**
     * Misma regla que round2() en public/js/centros-costos.js (money / captura).
     */
    protected function ccRound2Money($n): float
    {
        $v = (float) $n;
        if (! is_finite($v)) {
            return 0.0;
        }
        $abs = abs($v);
        $s = rtrim(rtrim(sprintf('%.8F', $abs), '0'), '.');
        $dot = strpos($s, '.');
        $decimals = $dot === false ? 0 : (strlen($s) - $dot - 1);
        $sign = $v < 0 ? -1.0 : 1.0;
        if ($decimals > 3) {
            return $sign * floor($abs * 100 + 1e-8) / 100;
        }

        return round($v, 2);
    }

    /**
     * Cuentas únicas asignadas para export: todas las del ciclo (cualquier usuario).
     *
     * @return array<int, array{empresa: string, centro: string, centro_nombre: string, cuenta: string, cuenta_nombre: string}>
     */
    protected function listaCuentasAsignadasExportGastos(string $ciclo, ?string $empresa = null): array
    {
        if (! Schema::hasTable('tbl_cc_asignaciones')) {
            return [];
        }

        return $this->cargarCuentasAsignadasExportGastos(strtoupper(trim($ciclo)), $empresa, null);
    }

    /**
     * @return array<int, array{empresa: string, centro: string, centro_nombre: string, cuenta: string, cuenta_nombre: string}>
     */
    protected function cargarCuentasAsignadasExportGastos(string $ciclo, ?string $empresa = null, ?int $userId = null): array
    {
        if (! Schema::hasTable('tbl_cc_asignacion_cuentas')) {
            return [];
        }

        $q = DB::table('tbl_cc_asignaciones as a')
            ->join('tbl_cc_asignacion_cuentas as c', 'c.asignacion_id', '=', 'a.id')
            ->whereRaw('UPPER(a.ciclo_codigo) = ?', [$ciclo])
            ->whereNotNull('c.cuenta_codigo')
            ->where('c.cuenta_codigo', '!=', '');

        if ($userId !== null && $userId > 0) {
            $q->where('a.user_id', $userId);
        }

        if ($empresa !== null && $empresa !== '') {
            $emp = strtoupper(trim($empresa));
            $empresas = [$emp];
            if ($emp === 'AUSTIN') {
                $empresas[] = 'ABSA';
            } elseif ($emp === 'ABSA') {
                $empresas[] = 'AUSTIN';
            }
            $q->where(function ($w) use ($empresas) {
                foreach ($empresas as $e) {
                    $w->orWhereRaw('UPPER(a.empresa) = ?', [$e]);
                }
            });
        }

        $seen = [];
        $out = [];
        foreach ($q->orderBy('a.empresa')->orderBy('a.centro_codigo')->orderBy('c.cuenta_codigo')
            ->select([
                'a.empresa',
                'a.centro_codigo',
                'a.centro_nombre',
                'c.cuenta_codigo',
                'c.cuenta_nombre',
            ])
            ->cursor() as $row) {
            $emp = strtoupper(trim((string) $row->empresa));
            if ($emp === 'ABSA') {
                $emp = 'AUSTIN';
            }
            $centro = trim((string) $row->centro_codigo);
            $cuenta = trim((string) $row->cuenta_codigo);
            $cuentaNombre = trim((string) ($row->cuenta_nombre ?? ''));
            if ($emp === '' || $centro === '' || $cuenta === '') {
                continue;
            }
            $cuenta = $this->codigoCuentaCatalogo($emp, $cuenta, $cuentaNombre);
            $canon = $this->capturaBudgetKey($emp, $centro, $cuenta);
            if (isset($seen[$canon])) {
                continue;
            }
            $seen[$canon] = true;
            $out[] = [
                'empresa' => $emp,
                'centro' => $centro,
                'centro_nombre' => trim((string) ($row->centro_nombre ?? '')),
                'cuenta' => $cuenta,
                'cuenta_nombre' => $cuentaNombre !== '' ? $cuentaNombre : trim((string) ($row->cuenta_nombre ?? '')),
            ];
        }

        return $out;
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
        $empresa = strtolower(trim((string) $request->get('empresa', 'austin')));
        $alias = ['absa' => 'austin'];
        $empresa = $alias[$empresa] ?? $empresa;
        $perPage = min(500, max(50, (int) $request->get('per_page', 200)));
        $refresh = $request->boolean('refresh');
        $local = app(CcCatalogoLocal::class);
        $empUp = strtoupper($empresa);

        if (! $refresh && $local->disponible() && $local->tieneDatos($empUp)) {
            $data = $local->leer($empUp);

            return response()->json([
                'ok' => true,
                'centros' => $data['centros'],
                'mensaje' => null,
                'fuente' => 'local',
                'synced_at' => $data['synced_at'],
            ]);
        }

        try {
            $api = app(AutinApiClient::class);
            $centros = [];
            $ok = false;
            $mensaje = null;
            for ($page = 1; $page <= 15; $page++) {
                $res = $api->index('centros-costo', ['per_page' => $perPage, 'page' => $page], $empresa);
                if (empty($res['ok'])) {
                    if ($page === 1) {
                        $mensaje = $res['message'] ?? 'Sin conexión a catálogo SAP';
                    }
                    break;
                }
                $ok = true;
                $body = is_array($res['body'] ?? null) ? $res['body'] : [];
                $batch = $this->normalizarCentros($body['data'] ?? []);
                if (! $batch) {
                    break;
                }
                $centros = array_merge($centros, $batch);
                $pag = $this->paginacionDe($body);
                if ($pag['last_page'] > 0 && $page >= $pag['last_page']) {
                    break;
                }
                if ($pag['last_page'] < 1 && count($batch) < $perPage) {
                    break;
                }
            }

            if ($ok && $local->disponible()) {
                // Solo centros de esta empresa; conserva cuentas locales existentes.
                $prev = $local->leer($empUp);
                $local->reemplazarEmpresa($empUp, $centros, $prev['cuentas'], auth()->id());
            }

            return response()->json([
                'ok' => $ok,
                'centros' => $centros,
                'mensaje' => $mensaje,
                'fuente' => $ok ? 'sap' : 'vacio',
                'synced_at' => $local->disponible() ? $local->ultimaSync($empUp) : null,
            ]);
        } catch (Throwable $e) {
            if ($local->disponible() && $local->tieneDatos($empUp)) {
                $data = $local->leer($empUp);

                return response()->json([
                    'ok' => true,
                    'centros' => $data['centros'],
                    'mensaje' => $e->getMessage(),
                    'fuente' => 'local',
                    'synced_at' => $data['synced_at'],
                ]);
            }

            return response()->json(['ok' => false, 'centros' => [], 'mensaje' => $e->getMessage()], 200);
        }
    }

    public function departamentosCentros(Request $request): JsonResponse
    {
        $empresa = strtoupper(trim((string) $request->get('empresa', '')));
        $alias = ['ABSA' => 'AUSTIN'];
        if (isset($alias[$empresa])) {
            $empresa = $alias[$empresa];
        }
        $ccs = $this->listaRequest($request, 'ccs');
        $year = (int) $request->get('year', $request->get('anio', date('Y')));
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }
        if ($empresa === '' || ! $ccs) {
            return response()->json(['ok' => false, 'por_cc' => (object) [], 'mensaje' => 'Falta empresa o centros.'], 422);
        }

        $porCc = [];
        $faltan = [];
        foreach ($ccs as $cc) {
            $ck = ltrim($cc, '0') ?: $cc;
            $cacheKey = 'cc.depto.v1.' . $empresa . '.' . $ck;
            $hit = Cache::get($cacheKey);
            if (is_string($hit) && $hit !== '') {
                $porCc[$cc] = $hit;
                $porCc[$ck] = $hit;
            } else {
                $faltan[] = $cc;
            }
        }

        if ($faltan) {
            try {
                $api = app(AutinApiClient::class);
                $res = $api->gastoRealDeptoPorCentros($empresa, $year, $faltan, 8);
                foreach ($res['por_cc'] ?? [] as $cc => $depto) {
                    $depto = trim((string) $depto);
                    if ($depto === '') {
                        continue;
                    }
                    $ck = ltrim((string) $cc, '0') ?: (string) $cc;
                    $porCc[(string) $cc] = $depto;
                    $porCc[$ck] = $depto;
                    Cache::put('cc.depto.v1.' . $empresa . '.' . $ck, $depto, 1800);
                }
            } catch (Throwable $e) {
                return response()->json([
                    'ok' => ! empty($porCc),
                    'por_cc' => $porCc ?: (object) [],
                    'mensaje' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'ok' => true,
            'por_cc' => $porCc ?: (object) [],
        ]);
    }

    public function cuentasSap(Request $request): JsonResponse
    {
        $empresa = strtolower((string) $request->get('empresa', 'austin'));
        $todas = $request->boolean('todas');
        $groupMask = trim((string) $request->get('group_mask', $request->get('GroupMask', '')));
        $refresh = $request->boolean('refresh');
        $local = app(CcCatalogoLocal::class);
        $empUp = strtoupper($empresa);

        if (! $refresh && $local->disponible() && $local->tieneCuentas($empUp)) {
            $data = $local->leer($empUp);
            $cuentas = $data['cuentas'];
            if ($groupMask !== '') {
                $cuentas = array_values(array_filter($cuentas, function ($c) use ($groupMask) {
                    return (string) ($c['grupo_id'] ?? '') === $groupMask;
                }));
            }
            if ($cuentas || $groupMask === '') {
                return response()->json([
                    'ok' => true,
                    'cuentas' => $cuentas,
                    'agrupaciones' => [],
                    'mensaje' => null,
                    'fuente' => 'local',
                    'synced_at' => $data['synced_at'],
                ]);
            }
        }

        try {
            $cargadas = $this->cargarCuentasEmpresa($empresa, $todas, $groupMask);
            if (! empty($cargadas['ok']) && $local->disponible() && $groupMask === '') {
                $prev = $local->leer($empUp);
                // Reemplaza cuentas del catálogo completo; conserva centros locales.
                $local->reemplazarEmpresa($empUp, $prev['centros'], $cargadas['cuentas'] ?? [], auth()->id());
            }

            return response()->json([
                'ok' => $cargadas['ok'],
                'cuentas' => $cargadas['cuentas'],
                'agrupaciones' => $cargadas['agrupaciones'],
                'mensaje' => $cargadas['mensaje'],
                'fuente' => ! empty($cargadas['ok']) ? 'sap' : 'vacio',
                'synced_at' => $local->disponible() ? $local->ultimaSync($empUp) : null,
            ]);
        } catch (Throwable $e) {
            if ($local->disponible() && $local->tieneDatos($empUp)) {
                $data = $local->leer($empUp);

                return response()->json([
                    'ok' => true,
                    'cuentas' => $data['cuentas'],
                    'agrupaciones' => [],
                    'mensaje' => $e->getMessage(),
                    'fuente' => 'local',
                    'synced_at' => $data['synced_at'],
                ]);
            }

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
        $asignacionesActualizadas = 0;
        DB::transaction(function () use ($grupo, $data, &$asignacionesActualizadas) {
            $grupo->updated_by = auth()->id();
            $grupo->save();

            if (array_key_exists('cuentas', $data)) {
                $asignacionesActualizadas = $this->syncGrupoCuentas($grupo, $data['cuentas']);
            }
            $grupo->load('cuentas');
        });

        return response()->json([
            'ok' => true,
            'grupo' => $this->grupoPayload($grupo),
            'asignaciones_actualizadas' => $asignacionesActualizadas,
        ]);
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
            return response()->json(['asignaciones' => [], 'resumen' => false]);
        }

        $empresa = request('empresa');
        $userId = (int) request('user_id', 0);
        $resumen = request()->boolean('resumen');
        $with = ['usuario', 'permisos.tipo'];
        if (! $resumen) {
            $with[] = 'cuentas';
        }
        $q = CcAsignacion::query()->with($with)
            ->where('ciclo_codigo', $ciclo);
        if ($empresa) {
            $q->where('empresa', strtolower($empresa));
        }
        if ($userId > 0) {
            $q->where('user_id', $userId);
        }
        $ccs = $this->listaRequest(request(), 'cc');
        if ($ccs === []) {
            $ccs = $this->listaRequest(request(), 'centros');
        }
        if ($ccs !== []) {
            $codigos = [];
            foreach ($ccs as $cc) {
                $cc = trim((string) $cc);
                if ($cc === '') {
                    continue;
                }
                $codigos[$cc] = $cc;
                $alt = ltrim($cc, '0');
                if ($alt !== '' && $alt !== $cc) {
                    $codigos[$alt] = $alt;
                }
            }
            if ($codigos) {
                $q->whereIn('centro_codigo', array_values($codigos));
            }
        }

        if (! $resumen) {
            $this->promoverCapturasIndependientes($ciclo);
        }

        $rows = $q->orderByDesc('id')->get()->map(function (CcAsignacion $a) use ($resumen) {
            return $this->asignacionPayload($a, $resumen);
        })->values()->all();

        return response()->json(['asignaciones' => $rows, 'resumen' => $resumen]);
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
        $permisos = $data['permisos'] ?? ['capturar'];
        $revisionCentro = $this->esRevisionDeCentroCompleto($data['centro_codigo'], $permisos, $data['cuentas'] ?? []);
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
            }
        if ($this->asigHasRolColumns() && ($nuevo || $this->clavesSonCaptura($permisos) || $revisionCentro)) {
            $asig->es_principal = true;
            $asig->parent_id = null;
        }
        $asig->save();

        $this->syncCuentas($asig, $data['cuentas'] ?? []);
        $this->syncPermisosClaves($asig, $permisos);

        $asig->load(['usuario', 'cuentas', 'permisos.tipo']);
        $snapshot = $this->clavesSonCaptura($permisos)
            && $this->datosSnapshotAsignacion($asig->empresa, $asig->centro_codigo, $data['cuentas'] ?? []) !== null;

        return response()->json([
            'ok' => true,
            'asignacion' => $this->asignacionPayload($asig),
            'snapshot' => $snapshot,
        ]);
    }

    /**
     * Escribe ya la copia local de los centros que se acaban de asignar.
     * Lo usan Guardar y Guardar y salir, para que los dos dejen el snapshot.
     */
    public function snapshotAsignaciones(Request $request, string $ciclo): JsonResponse
    {
        $data = $request->validate([
            'items' => 'required|array|min:1|max:40',
            'items.*.empresa' => 'required|string|max:40',
            'items.*.centro_codigo' => 'required|string|max:40',
            'items.*.cuentas' => 'array',
            'items.*.permisos' => 'array',
            'items.*.permisos.*' => 'string',
        ]);

        @set_time_limit(180);
        $n = 0;
        $userId = auth()->id();
        foreach ($data['items'] as $item) {
            $permisos = is_array($item['permisos'] ?? null) ? $item['permisos'] : [];
            if (! $this->clavesSonCaptura($permisos)) {
                continue;
            }
            $prep = $this->datosSnapshotAsignacion(
                (string) $item['empresa'],
                (string) $item['centro_codigo'],
                is_array($item['cuentas'] ?? null) ? $item['cuentas'] : []
            );
            if ($prep === null) {
                continue;
            }
            try {
                $this->asegurarSnapshotAsignacion($ciclo, $prep['empresa'], $prep['centro'], $prep['codigos'], $userId);
            } catch (Throwable $e) {
                report($e);

                return response()->json(['ok' => false, 'message' => 'No se pudo guardar la copia local.'], 500);
            }
            $n++;
        }

        return response()->json(['ok' => true, 'snapshot' => $n > 0, 'centros' => $n]);
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
                $permisos = $data['permisos'] ?: ['revisar'];
                $this->syncPermisosClaves($asig, $permisos);
                if ($this->asigHasRolColumns() && $this->clavesSonCaptura($permisos)) {
                    $asig->es_principal = true;
                    $asig->parent_id = null;
                    $asig->save();
                }
            }
            if (array_key_exists('accesos', $data)) {
                $this->syncAccesos($asig, $data['accesos']);
            }
        });

        $asig->load(['usuario', 'cuentas', 'permisos.tipo']);
        $claves = $asig->permisos->map(function ($p) {
            return (string) ($p->tipo->clave ?? '');
        })->all();
        if (array_key_exists('cuentas', $data) && $this->clavesSonCaptura($claves)) {
            $this->programarSnapshotAsignacion($ciclo, $asig->empresa, $asig->centro_codigo, $data['cuentas']);
        }

        return response()->json(['ok' => true, 'asignacion' => $this->asignacionPayload($asig), 'snapshot' => array_key_exists('cuentas', $data)]);
    }

    public function destroyAsignacion(string $ciclo, int $id): JsonResponse
    {
        $asig = CcAsignacion::query()
            ->where('id', $id)
            ->whereRaw('UPPER(ciclo_codigo) = ?', [strtoupper($ciclo)])
            ->first();
        if (! $asig) {
            return response()->json(['message' => 'Asignación no encontrada'], 404);
        }

        DB::transaction(function () use ($asig) {
            if ($this->esRevisionCentroCompletoGuardada($asig)) {
                $this->borrarRevisionesDelMismoCentro($asig);
                return;
            }
            if ($this->asigHasRolColumns() && $asig->es_principal) {
                foreach ($this->queryColaboradoresDe($asig)->get() as $extra) {
                    $this->borrarAsignacion($extra);
                }
            }
            $this->borrarAsignacion($asig);
        });

        return response()->json(['ok' => true]);
    }

    /**
     * Al asignar, agrega a la copia local las cuentas nuevas y actualiza las que ya estaban.
     * Corre después de responder, para no frenar la pantalla.
     *
     * @param  array<int, mixed>  $cuentas
     */
    /**
     * @param  array<int, mixed>  $cuentas
     * @return array{empresa: string, centro: string, codigos: array<int, string>}|null
     */
    protected function datosSnapshotAsignacion(string $empresa, string $centro, array $cuentas): ?array
    {
        $centro = strtoupper(trim($centro));
        if ($centro === '' || $centro === 'SIN_CC' || $centro === 'EMPRESA') {
            return null;
        }
        $empresa = strtoupper(trim($empresa));
        $alias = ['ABSA' => 'AUSTIN'];
        if (isset($alias[$empresa])) {
            $empresa = $alias[$empresa];
        }
        $codigos = [];
        foreach ($cuentas as $cuenta) {
            if (is_array($cuenta)) {
                $codigos[] = trim((string) ($cuenta['codigo'] ?? ''));
            } else {
                $codigos[] = trim((string) $cuenta);
            }
        }
        $codigos = array_values(array_filter($codigos));
        if ($codigos === []) {
            return null;
        }

        return ['empresa' => $empresa, 'centro' => $centro, 'codigos' => $codigos];
    }

    protected function programarSnapshotAsignacion(string $ciclo, string $empresa, string $centro, array $cuentas): void
    {
        $prep = $this->datosSnapshotAsignacion($empresa, $centro, $cuentas);
        if ($prep === null) {
            return;
        }
        $userId = auth()->id();
        app()->terminating(function () use ($ciclo, $prep, $userId) {
            try {
                @set_time_limit(180);
                $this->asegurarSnapshotAsignacion($ciclo, $prep['empresa'], $prep['centro'], $prep['codigos'], $userId);
            } catch (Throwable $e) {
                report($e);
            }
        });
    }

    /**
     * Baja el gasto del centro y lo escribe en la copia local:
     * las cuentas que no estaban se agregan y las que ya estaban se actualizan.
     *
     * @param  array<int, string>  $cuentas
     */
    protected function asegurarSnapshotAsignacion(string $ciclo, string $empresa, string $centro, array $cuentas, ?int $userId): void
    {
        $snap = app(CcGastoRealSnapshot::class);
        if (! $snap->disponible() || $empresa === '' || $centro === '') {
            return;
        }
        $year = $this->anioGastoCiclo($ciclo);
        $asignado = strtoupper(trim($centro));
        $destinos = $this->centrosSnapshotDe($asignado, $snap->centrosPresentes($empresa, $year));
        if ($destinos === []) {
            $destinos = [$asignado];
        }

        $codigos = [];
        foreach ($cuentas as $cuenta) {
            $cuenta = trim((string) $cuenta);
            if ($cuenta === '') {
                continue;
            }
            $clave = $this->codigoCuentaKey($cuenta);
            $codigos[$clave !== '' ? $clave : $cuenta] = $cuenta;
        }
        $codigos = array_values($codigos);

        $pedido = array_values(array_unique(array_merge([$asignado], $destinos)));
        $remoto = $this->mapaGastoPorCentros($empresa, $year, $pedido, true);
        $ok = ! empty($remoto['ok']);
        $por = [];
        $filas = [];
        if ($ok) {
            foreach ($pedido as $codigoCc) {
                foreach ($this->porCuentaDeIndiceEmpresa($remoto['mapa'] ?? [], $codigoCc) as $k => $item) {
                    if (! isset($por[$k]) && is_array($item)) {
                        $por[$k] = $item;
                    }
                }
            }
            $filas = $this->filasGastoSapDesdeRegistros(is_array($remoto['rows'] ?? null) ? $remoto['rows'] : [], $empresa, $year);
        }

        if ($codigos === []) {
            return;
        }

        foreach ($destinos as $destino) {
            $lista = $ok ? $codigos : $this->cuentasFueraDeSnapshot($empresa, $year, $destino, $codigos);
            if ($lista === []) {
                continue;
            }
            $paquetes = [];
            foreach ($lista as $cuenta) {
                $paquetes[] = [
                    'cuenta' => $cuenta,
                    'item' => $ok ? $this->itemGastoAsignado($por, $cuenta) : $this->itemGastoVacio($cuenta),
                    'filas' => $ok ? $this->filasGastoDeCuenta($filas, $cuenta, $pedido) : [],
                ];
            }
            $snap->aplicarCuentas($empresa, $year, $destino, $paquetes, $userId);
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $por
     * @return array<string, mixed>
     */
    protected function itemGastoAsignado(array $por, string $cuenta): array
    {
        $ck = $this->codigoCuentaKey($cuenta);
        $hit = $ck !== '' ? $this->gastoDesdeIndice($por, $ck) : null;
        if (! is_array($hit)) {
            return $this->itemGastoVacio($cuenta);
        }
        $hit['codigo'] = $cuenta;
        if (! isset($hit['gasto']) || ! is_array($hit['gasto'])) {
            $hit['gasto'] = array_fill(0, 12, 0.0);
        }
        if (! isset($hit['gasto_usd']) || ! is_array($hit['gasto_usd'])) {
            $hit['gasto_usd'] = array_fill(0, 12, 0.0);
        }

        return $hit;
    }

    /**
     * @return array<string, mixed>
     */
    protected function itemGastoVacio(string $cuenta): array
    {
        return [
            'codigo' => $cuenta,
            'nombre' => '',
            'gasto' => array_fill(0, 12, 0.0),
            'gasto_usd' => array_fill(0, 12, 0.0),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $filas
     * @param  array<int, string>  $centros
     * @return array<int, array<string, mixed>>
     */
    protected function filasGastoDeCuenta(array $filas, string $cuenta, array $centros): array
    {
        $ck = $this->codigoCuentaKey($cuenta);
        if ($ck === '') {
            return [];
        }
        $out = [];
        foreach ($filas as $fila) {
            if (! is_array($fila) || $this->codigoCuentaKey((string) ($fila['cuenta'] ?? '')) !== $ck) {
                continue;
            }
            $centroFila = trim((string) ($fila['centro'] ?? ''));
            if ($centroFila !== '') {
                $coincide = false;
                foreach ($centros as $centro) {
                    if ($this->mismoCentroSap($centroFila, (string) $centro)) {
                        $coincide = true;
                        break;
                    }
                }
                if (! $coincide) {
                    continue;
                }
            }
            $out[] = $fila;
        }

        return $out;
    }

    /**
     * @param  array<int, string>  $presentes
     * @return array<int, string>
     */
    protected function centrosSnapshotDe(string $centro, array $presentes): array
    {
        $out = [];
        foreach ($presentes as $item) {
            $item = strtoupper(trim((string) $item));
            if ($item !== '' && $this->mismoCentroSap($centro, $item)) {
                $out[$item] = $item;
            }
        }

        return array_values($out);
    }

    protected function anioGastoCiclo(string $ciclo): int
    {
        $year = (int) CcCiclo::query()
            ->whereRaw('UPPER(codigo) = ?', [strtoupper($ciclo)])
            ->value('anio_referencia');
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }

        return $year;
    }

    /**
     * @param  array<int, string>  $cuentas
     * @return array<int, string>
     */
    protected function cuentasFueraDeSnapshot(string $empresa, int $year, string $centro, array $cuentas): array
    {
        $guardadas = DB::table('tbl_cc_gasto_real_snap')
            ->where('empresa', $empresa)
            ->where('anio', $year)
            ->where('centro_codigo', strtoupper(trim($centro)))
            ->pluck('cuenta_codigo');
        $tienen = [];
        foreach ($guardadas as $codigo) {
            $key = trim((string) $codigo);
            if ($key !== '') {
                $tienen[$key] = true;
            }
        }
        $faltan = [];
        foreach ($cuentas as $cuenta) {
            $cuenta = trim((string) $cuenta);
            if ($cuenta === '' || isset($tienen[$cuenta])) {
                continue;
            }
            $faltan[] = $cuenta;
        }

        return $faltan;
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
        $refresh = $request->boolean('refresh');
        $local = app(CcCatalogoLocal::class);

        // Por defecto: catálogo local (rápido). refresh=1 fuerza SAP → local.
        if (! $refresh && $local->disponible() && $local->tieneDatos($empresa ? (string) $empresa : null)) {
            $data = $local->leer($empresa ? (string) $empresa : null);

            return response()->json([
                'centros' => $data['centros'],
                'cuentas' => $data['cuentas'],
                'agrupaciones' => [],
                'empresas' => $data['empresas'],
                'sapOk' => false,
                'sapMensaje' => null,
                'fuente' => 'local',
                'catalogo_local' => true,
                'synced_at' => $data['synced_at'],
            ]);
        }

        try {
            $api = app(AutinApiClient::class);
        } catch (Throwable $e) {
            if ($local->disponible() && $local->tieneDatos($empresa ? (string) $empresa : null)) {
                $data = $local->leer($empresa ? (string) $empresa : null);

                return response()->json([
                    'centros' => $data['centros'],
                    'cuentas' => $data['cuentas'],
                    'agrupaciones' => [],
                    'empresas' => $data['empresas'],
                    'sapOk' => false,
                    'sapMensaje' => $e->getMessage(),
                    'fuente' => 'local',
                    'catalogo_local' => true,
                    'synced_at' => $data['synced_at'],
                ]);
            }

            return response()->json([
                'centros' => [],
                'cuentas' => [],
                'agrupaciones' => [],
                'empresas' => [],
                'sapOk' => false,
                'sapMensaje' => $e->getMessage(),
                'fuente' => 'sap',
                'catalogo_local' => false,
                'synced_at' => null,
            ]);
        }

        $payload = $this->cargarCatalogo($api, $perPage, $empresa);
        // Si vino de SAP y hay tablas locales, persistir (sin tocar captura).
        if (! empty($payload['sapOk']) && $local->disponible()) {
            $this->persistirCatalogoSapEnLocal($payload, $empresa ? (string) $empresa : null);
            $payload['fuente'] = 'sap';
            $payload['catalogo_local'] = true;
            $payload['synced_at'] = $local->ultimaSync($empresa ? strtoupper((string) $empresa) : null);
        } else {
            $payload['fuente'] = ! empty($payload['sapOk']) ? 'sap' : 'vacio';
            $payload['catalogo_local'] = $local->disponible();
            $payload['synced_at'] = $local->disponible()
                ? $local->ultimaSync($empresa ? strtoupper((string) $empresa) : null)
                : null;
        }

        return response()->json($payload);
    }

    /**
     * Actualiza catálogo local desde SAP (centros/cuentas/nombres/depto).
     * No modifica tbl_cc_presupuestos ni asignaciones.
     */
    public function sincronizarCatalogo(Request $request): JsonResponse
    {
        @set_time_limit(300);
        $local = app(CcCatalogoLocal::class);
        if (! $local->disponible()) {
            return response()->json([
                'ok' => false,
                'message' => 'Faltan tablas de catálogo local. Ejecuta database/sql/cc_catalogo_local.sql',
            ], 422);
        }

        $empresaReq = strtoupper(trim((string) $request->get('empresa', '')));
        try {
            $api = app(AutinApiClient::class);
            $dbs = $api->allowedDatabases();
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 200);
        }

        $empresas = $empresaReq !== ''
            ? [strtolower($empresaReq)]
            : array_values($dbs);
        $alias = ['absa' => 'austin'];
        $out = [];
        $okAll = true;
        $userId = auth()->id();

        foreach ($empresas as $db) {
            $db = strtolower((string) ($alias[$db] ?? $db));
            $empUp = strtoupper($db);
            try {
                $payload = $this->cargarCatalogo($api, 200, $empUp);
                if (empty($payload['sapOk'])) {
                    $okAll = false;
                    $msg = (string) ($payload['sapMensaje'] ?? 'Sin respuesta SAP');
                    $local->marcarFallo($empUp, $msg, $userId);
                    $out[] = [
                        'empresa' => $empUp,
                        'ok' => false,
                        'centros' => 0,
                        'cuentas' => 0,
                        'mensaje' => $msg,
                    ];
                    continue;
                }
                $stats = $local->reemplazarEmpresa(
                    $empUp,
                    $payload['centros'] ?? [],
                    $payload['cuentas'] ?? [],
                    $userId
                );
                $out[] = [
                    'empresa' => $empUp,
                    'ok' => true,
                    'centros' => $stats['centros'],
                    'cuentas' => $stats['cuentas'],
                    'mensaje' => null,
                ];
            } catch (Throwable $e) {
                $okAll = false;
                $local->marcarFallo($empUp, $e->getMessage(), $userId);
                $out[] = [
                    'empresa' => $empUp,
                    'ok' => false,
                    'centros' => 0,
                    'cuentas' => 0,
                    'mensaje' => $e->getMessage(),
                ];
            }
        }

        $data = $local->leer($empresaReq !== '' ? $empresaReq : null);

        return response()->json([
            'ok' => $okAll,
            'empresas' => $out,
            'centros' => $data['centros'],
            'cuentas' => $data['cuentas'],
            'empresas_catalogo' => $data['empresas'],
            'fuente' => 'local',
            'catalogo_local' => true,
            'synced_at' => $data['synced_at'],
            'mensaje' => $okAll
                ? 'Catálogo local actualizado desde SAP (sin tocar captura).'
                : 'Algunas empresas fallaron; revisa el detalle.',
        ]);
    }

    public function gastoReal(Request $request): JsonResponse
    {
        @set_time_limit(180);
        $empresa = strtoupper(trim((string) $request->get('empresa', '')));
        $cc = trim((string) $request->get('cc', $request->get('CC', '')));
        $year = (int) $request->get('year', $request->get('anio', 0));
        $cuentas = $this->listaRequest($request, 'cuentas');

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

        if (! $cuentas) {
            $this->completarCuentasGastoDesdeAsignaciones($empresa, $cc, $cuentas);
        }
        $cuentas = array_values(array_unique($cuentas));
        sort($cuentas);

        if ($cc !== '' && ! $request->boolean('refresh')) {
            $desdeCopia = $this->gastoRealDesdeCopia($empresa, $cc, $year, $cuentas);
            if ($desdeCopia !== null) {
                return response()->json($desdeCopia);
            }
            // Con copia local disponible: no ir a SAP (Detalle/Captura deben ser rápidos).
            // Para forzar SAP usar refresh=1 o sincronizar gasto del centro.
            $snap = app(CcGastoRealSnapshot::class);
            if ($snap->disponible() && ! $request->boolean('sap')) {
                return response()->json([
                    'ok' => true,
                    'completo' => true,
                    'year' => $year,
                    'por_cuenta' => (object) [],
                    'origen' => 'copia',
                    'mensaje' => null,
                ]);
            }
        }

        $cacheSuffix = $empresa . '.' . $cc . '.' . $year . '.' . md5(json_encode($cuentas));
        $cacheKey = 'cc.gasto-real.v14.' . $cacheSuffix;
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && ! empty($cached['ok']) && ! empty($cached['por_cuenta'])) {
            return response()->json($this->conMesesRecientes($cached, $empresa, $cc, $year));
        }

        try {
            $payload = $this->cargarGastoRealCentro($empresa, $cc, $year, $cuentas, $request->boolean('refresh'));
            if (! empty($payload['ok']) && ! empty($payload['completo']) && ! empty($payload['por_cuenta'])) {
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

        return response()->json($this->conMesesRecientes($payload, $empresa, $cc, $year));
    }

    /**
     * Gasto real de varios centros en una sola petición (análisis, sobre todo «Todas»).
     * Una consulta por empresa si el año cabe en pocas páginas; si no, una por centro en paralelo.
     */
    /**
     * Gasto real de varios centros: solo copia local (BD).
     * Si la empresa no tiene nada guardado, el front siembra con /sincronizar
     * (Buscar lo hace una vez; el botón «Actualizar gasto SAP» fuerza siempre).
     */
    public function gastoRealAnalisis(Request $request): JsonResponse
    {
        @set_time_limit(180);
        $year = (int) $request->input('year', $request->input('anio', 0));
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }

        $centros = $request->input('centros', []);
        if (! is_array($centros)) {
            $centros = [];
        }

        $alias = ['ABSA' => 'AUSTIN'];
        $grupos = [];
        $vistos = [];
        foreach (array_slice($centros, 0, 500) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $raw = strtoupper(trim((string) ($item['empresa'] ?? '')));
            $cc = trim((string) ($item['cc'] ?? ''));
            if ($raw === '' || $cc === '') {
                continue;
            }
            $clave = $raw.'|'.$cc;
            if (isset($vistos[$clave])) {
                continue;
            }
            $vistos[$clave] = true;
            $tieneCuentas = array_key_exists('cuentas', $item) && $item['cuentas'] !== null;
            $cuentas = $tieneCuentas ? ($item['cuentas'] ?? []) : [];
            if (is_string($cuentas)) {
                $cuentas = preg_split('/\s*,\s*/', $cuentas) ?: [];
            }
            if (! is_array($cuentas)) {
                $cuentas = [];
            }
            $sap = $alias[$raw] ?? $raw;
            $grupos[$sap][] = [
                'raw' => $raw,
                'cc' => $cc,
                'clave' => $clave,
                'filtrar_cuentas' => $tieneCuentas,
                'cuentas' => array_values(array_filter(array_map(function ($c) {
                    return trim((string) $c);
                }, $cuentas))),
            ];
        }

        // Modo ligero: totales + serie mensual por centro (sin mapa de cuentas).
        if ($request->boolean('totales')) {
            return $this->gastoRealAnalisisTotales($request, $year, $grupos);
        }

        $this->gastoCopiaEmpresas = [];
        $porCentro = [];
        $sinCopia = [];
        foreach ($grupos as $empresa => $lista) {
            try {
                $ccs = [];
                foreach ($lista as $item) {
                    $ccs[] = (string) ($item['cc'] ?? '');
                }
                $mapa = $this->mapaGastoCopia($empresa, $year, $ccs);
            } catch (Throwable $e) {
                $mapa = [];
            }
            $tieneCopia = ! empty($this->gastoCopiaEmpresas[$empresa]);
            if (! $tieneCopia) {
                $sinCopia[$empresa] = $empresa;
            }
            foreach ($lista as $item) {
                $todo = $this->porCuentaDeIndiceEmpresa($mapa, $item['cc']);
                $pedido = is_array($item['cuentas'] ?? null) ? $item['cuentas'] : [];
                // Sin cuentas pedidas (o sin match de códigos): devolver el snap completo del centro.
                $por = $pedido === []
                    ? $todo
                    : $this->recortarGastoPedido($todo, $pedido);
                if ($por === [] && $todo !== []) {
                    $por = $todo;
                }
                $porCentro[$item['clave']] = (object) $por;
                if ($tieneCopia) {
                    $raw = explode('|', (string) $item['clave'], 2)[0];
                    if ($raw !== '') {
                        $this->gastoCopiaEmpresas[$raw] = true;
                    }
                }
            }
        }

        return response()->json([
            'ok' => true,
            'year' => $year,
            'origen' => 'copia',
            'por_centro' => (object) $porCentro,
            'copias' => (object) $this->gastoCopiaEmpresas,
            'sin_copia' => array_values($sinCopia),
        ]);
    }

    /**
     * Totales reales del snap local por centro (+ ppto/captura del ciclo si viene).
     *
     * @param  array<string, array<int, array{raw: string, cc: string, clave: string, cuentas: array<int, string>}>>  $grupos
     */
    protected function gastoRealAnalisisTotales(Request $request, int $year, array $grupos): JsonResponse
    {
        @set_time_limit(60);
        $ciclo = strtoupper(trim((string) $request->input('ciclo', '')));
        $snap = app(CcGastoRealSnapshot::class);
        $porCentro = [];
        $copias = [];
        $sinCopia = [];

        foreach ($grupos as $empresa => $lista) {
            $tiene = false;
            try {
                $tiene = $snap->disponible() && $snap->tieneCopia($empresa, $year);
            } catch (Throwable $e) {
                $tiene = false;
            }
            if ($tiene) {
                $copias[$empresa] = true;
                foreach ($lista as $item) {
                    $raw = (string) ($item['raw'] ?? '');
                    if ($raw !== '') {
                        $copias[$raw] = true;
                    }
                }
            } else {
                $sinCopia[$empresa] = $empresa;
            }

            $ccs = [];
            $cuentasPorCc = [];
            foreach ($lista as $item) {
                $ccs[] = $item['cc'];
                // null = todas las cuentas del centro; [] = nada asignado; lista = filtro.
                $cuentasPorCc[$item['cc']] = ! empty($item['filtrar_cuentas'])
                    ? $item['cuentas']
                    : null;
                $porCentro[$item['clave']] = [
                    'tot_gasto' => 0.0,
                    'tot_gasto_usd' => 0.0,
                    'tot_ppto' => 0.0,
                    'total' => ! empty($item['filtrar_cuentas']) ? count($item['cuentas']) : 0,
                    'capturadas' => 0,
                    'depto' => '',
                    'months' => array_fill(0, 12, 0.0),
                    'months_usd' => array_fill(0, 12, 0.0),
                ];
            }

            $totales = [];
            try {
                if ($tiene) {
                    $totales = $snap->totalesVisorPorCentro($empresa, $year, $ccs, $cuentasPorCc, 9);
                }
            } catch (Throwable $e) {
                $totales = [];
            }

            foreach ($lista as $item) {
                // Sin cuentas asignadas (filtro vacío): dejar en ceros.
                if (! empty($item['filtrar_cuentas']) && $item['cuentas'] === []) {
                    continue;
                }
                $hit = $totales[$item['cc']] ?? null;
                if (! $hit) {
                    foreach ($totales as $ccKey => $vals) {
                        if ($this->mismoCentroCodigo((string) $ccKey, $item['cc'])) {
                            $hit = $vals;
                            break;
                        }
                    }
                }
                if (! $hit) {
                    continue;
                }
                $porCentro[$item['clave']]['tot_gasto'] = (float) ($hit['tot_gasto'] ?? 0);
                $porCentro[$item['clave']]['tot_gasto_usd'] = (float) ($hit['tot_gasto_usd'] ?? 0);
                $porCentro[$item['clave']]['months'] = array_values($hit['months'] ?? array_fill(0, 12, 0.0));
                $porCentro[$item['clave']]['months_usd'] = array_values($hit['months_usd'] ?? array_fill(0, 12, 0.0));
                if (! empty($hit['depto'])) {
                    $porCentro[$item['clave']]['depto'] = (string) $hit['depto'];
                }
            }
        }

        if ($ciclo !== '' && Schema::hasTable('tbl_cc_presupuestos') && $porCentro !== []) {
            $hasDone = Schema::hasColumn('tbl_cc_presupuestos', 'completado');
            $cols = ['empresa', 'centro_codigo', 'cuenta_codigo'];
            for ($i = 1; $i <= 12; $i++) {
                $cols[] = 'mes_'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            }
            if ($hasDone) {
                $cols[] = 'completado';
            }

            $pptoByClave = [];
            foreach (DB::table('tbl_cc_presupuestos')->where('ciclo_codigo', $ciclo)->get($cols) as $row) {
                $emp = strtoupper(trim((string) $row->empresa));
                $cc = trim((string) $row->centro_codigo);
                $clave = $emp.'|'.$cc;
                if (! isset($porCentro[$clave])) {
                    foreach (array_keys($porCentro) as $k) {
                        $parts = explode('|', $k, 2);
                        if (count($parts) === 2
                            && strtoupper($parts[0]) === $emp
                            && $this->mismoCentroCodigo($parts[1], $cc)) {
                            $clave = $k;
                            break;
                        }
                    }
                }
                if (! isset($porCentro[$clave])) {
                    continue;
                }
                $ck = $this->codigoCuentaKey((string) $row->cuenta_codigo);
                if ($ck === '') {
                    continue;
                }
                $tot = 0.0;
                $meses = [];
                for ($i = 1; $i <= 12; $i++) {
                    $col = 'mes_'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                    $val = $row->{$col} ?? null;
                    $meses[] = ($val === null || $val === '') ? null : round((float) $val, 2);
                    if ($val !== null && $val !== '') {
                        $tot += (float) $val;
                    }
                }
                $done = ($hasDone && ! empty($row->completado)) || $this->mesesTodosLlenos($meses);
                if (! isset($pptoByClave[$clave])) {
                    $pptoByClave[$clave] = [];
                }
                $pptoByClave[$clave][$ck] = ['tot' => round($tot, 2), 'done' => $done];
            }

            foreach ($grupos as $lista) {
                foreach ($lista as $item) {
                    $clave = $item['clave'];
                    $map = $pptoByClave[$clave] ?? [];
                    if ($map === []) {
                        continue;
                    }
                    $filtrar = ! empty($item['filtrar_cuentas']);
                    if ($filtrar && $item['cuentas'] === []) {
                        $porCentro[$clave]['tot_ppto'] = 0.0;
                        $porCentro[$clave]['capturadas'] = 0;
                        $porCentro[$clave]['total'] = 0;

                        continue;
                    }
                    $filtro = [];
                    if ($filtrar) {
                        foreach ($item['cuentas'] as $cta) {
                            $ck = $this->codigoCuentaKey($cta);
                            if ($ck !== '') {
                                $filtro[$ck] = true;
                            }
                        }
                    }
                    $sumP = 0.0;
                    $capt = 0;
                    $total = 0;
                    foreach ($map as $ck => $hit) {
                        if ($filtrar && ! isset($filtro[$ck])) {
                            continue;
                        }
                        $total++;
                        $sumP += (float) ($hit['tot'] ?? 0);
                        if (! empty($hit['done'])) {
                            $capt++;
                        }
                    }
                    $porCentro[$clave]['tot_ppto'] = round($sumP, 2);
                    $porCentro[$clave]['capturadas'] = $capt;
                    if ($total > 0) {
                        $porCentro[$clave]['total'] = $total;
                    } elseif ($filtrar) {
                        $porCentro[$clave]['total'] = count($item['cuentas']);
                    }
                }
            }
        }

        return response()->json([
            'ok' => true,
            'year' => $year,
            'modo' => 'totales',
            'origen' => 'copia',
            'por_centro' => (object) $porCentro,
            'copias' => (object) $copias,
            'sin_copia' => array_values($sinCopia),
        ]);
    }

    /**
     * Encabezados del Visor: 1 fila por centro (totales), sin mapa de cuentas.
     */
    public function visorResumen(Request $request): JsonResponse
    {
        @set_time_limit(60);
        $year = (int) $request->input('year', $request->input('anio', 0));
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }
        $ciclo = strtoupper(trim((string) $request->input('ciclo', '')));
        $conPpto = filter_var($request->input('con_ppto', '1'), FILTER_VALIDATE_BOOLEAN);
        $sinGasto = filter_var($request->input('sin_gasto', '0'), FILTER_VALIDATE_BOOLEAN);
        $centros = $request->input('centros', []);
        if (! is_array($centros)) {
            $centros = [];
        }

        $alias = ['ABSA' => 'AUSTIN'];
        $grupos = [];
        $vistos = [];
        $clavesOrden = [];
        foreach (array_slice($centros, 0, 500) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $raw = strtoupper(trim((string) ($item['empresa'] ?? '')));
            $cc = trim((string) ($item['cc'] ?? ''));
            if ($raw === '' || $cc === '') {
                continue;
            }
            $clave = $raw.'|'.$cc;
            if (isset($vistos[$clave])) {
                continue;
            }
            $vistos[$clave] = true;
            $clavesOrden[] = $clave;
            $cuentas = $item['cuentas'] ?? [];
            if (is_string($cuentas)) {
                $cuentas = preg_split('/\s*,\s*/', $cuentas) ?: [];
            }
            if (! is_array($cuentas)) {
                $cuentas = [];
            }
            $sap = $alias[$raw] ?? $raw;
            $grupos[$sap][] = [
                'raw' => $raw,
                'cc' => $cc,
                'clave' => $clave,
                'cuentas' => array_values(array_filter(array_map(function ($c) {
                    return trim((string) $c);
                }, $cuentas))),
            ];
        }

        $snap = app(CcGastoRealSnapshot::class);
        $porCentro = [];
        foreach ($clavesOrden as $clave) {
            $porCentro[$clave] = [
                'tot_gasto' => 0.0,
                'tot_gasto_usd' => 0.0,
                'tot_ppto' => 0.0,
                'total' => 0,
                'capturadas' => 0,
                'depto' => '',
                'estado' => null,
                'fecha' => null,
            ];
        }

        foreach ($grupos as $empresa => $lista) {
            $ccs = [];
            $cuentasPorCc = [];
            foreach ($lista as $item) {
                $ccs[] = $item['cc'];
                // Vacío en Visor = todas las cuentas del centro (aún no hay desglose).
                $cuentasPorCc[$item['cc']] = $item['cuentas'] === [] ? null : $item['cuentas'];
            }
            $totales = [];
            if (! $sinGasto) {
                try {
                    if ($snap->disponible()) {
                        $totales = $snap->totalesVisorPorCentro($empresa, $year, $ccs, $cuentasPorCc, 9);
                    }
                } catch (Throwable $e) {
                    $totales = [];
                }
            }
            foreach ($lista as $item) {
                $hit = $totales[$item['cc']] ?? null;
                if (! $hit) {
                    foreach ($totales as $ccKey => $vals) {
                        if ($this->mismoCentroCodigo((string) $ccKey, $item['cc'])) {
                            $hit = $vals;
                            break;
                        }
                    }
                }
                if ($hit) {
                    $porCentro[$item['clave']]['tot_gasto'] = (float) ($hit['tot_gasto'] ?? 0);
                    $porCentro[$item['clave']]['tot_gasto_usd'] = (float) ($hit['tot_gasto_usd'] ?? 0);
                    if (! empty($hit['depto'])) {
                        $porCentro[$item['clave']]['depto'] = (string) $hit['depto'];
                    }
                }
                $porCentro[$item['clave']]['total'] = count($item['cuentas']);
            }
        }

        if ($conPpto && $ciclo !== '' && Schema::hasTable('tbl_cc_presupuestos') && $clavesOrden) {
            $hasDone = Schema::hasColumn('tbl_cc_presupuestos', 'completado');
            $cols = ['empresa', 'centro_codigo', 'cuenta_codigo'];
            for ($i = 1; $i <= 12; $i++) {
                $cols[] = 'mes_'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            }
            if ($hasDone) {
                $cols[] = 'completado';
            }
            $pptoByClave = [];
            foreach (DB::table('tbl_cc_presupuestos')->where('ciclo_codigo', $ciclo)->get($cols) as $row) {
                $emp = strtoupper(trim((string) $row->empresa));
                $cc = trim((string) $row->centro_codigo);
                $clave = $emp.'|'.$cc;
                if (! isset($porCentro[$clave])) {
                    foreach ($porCentro as $k => $_) {
                        $parts = explode('|', $k, 2);
                        if (count($parts) === 2
                            && strtoupper($parts[0]) === $emp
                            && $this->mismoCentroCodigo($parts[1], $cc)) {
                            $clave = $k;
                            break;
                        }
                    }
                }
                if (! isset($porCentro[$clave])) {
                    continue;
                }
                $ck = $this->codigoCuentaKey((string) $row->cuenta_codigo);
                $tot = 0.0;
                $meses = [];
                for ($i = 1; $i <= 12; $i++) {
                    $col = 'mes_'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                    $val = $row->{$col} ?? null;
                    $meses[] = ($val === null || $val === '') ? null : round((float) $val, 2);
                    if ($val !== null && $val !== '') {
                        $tot += (float) $val;
                    }
                }
                $done = ($hasDone && ! empty($row->completado)) || $this->mesesTodosLlenos($meses);
                if (! isset($pptoByClave[$clave])) {
                    $pptoByClave[$clave] = [];
                }
                $pptoByClave[$clave][$ck] = ['tot' => round($tot, 2), 'done' => $done];
            }

            foreach ($grupos as $lista) {
                foreach ($lista as $item) {
                    $clave = $item['clave'];
                    $map = $pptoByClave[$clave] ?? [];
                    $sumP = 0.0;
                    $capt = 0;
                    $seen = [];
                    foreach ($item['cuentas'] as $cta) {
                        $ck = $this->codigoCuentaKey($cta);
                        if ($ck === '' || isset($seen[$ck])) {
                            continue;
                        }
                        $seen[$ck] = true;
                        $hit = $map[$ck] ?? null;
                        if ($hit) {
                            $sumP += (float) $hit['tot'];
                            if (! empty($hit['done'])) {
                                $capt++;
                            }
                        }
                    }
                    $porCentro[$clave]['tot_ppto'] = round($sumP, 2);
                    $porCentro[$clave]['capturadas'] = $capt;
                    if ($porCentro[$clave]['total'] <= 0) {
                        $porCentro[$clave]['total'] = count($seen) ?: count($item['cuentas']);
                    }
                }
            }
        }

        if ($ciclo !== '' && Schema::hasTable('tbl_cc_captura_centros') && $clavesOrden) {
            foreach (DB::table('tbl_cc_captura_centros')->where('ciclo_codigo', $ciclo)->get(['empresa', 'centro_codigo', 'estado', 'updated_at']) as $row) {
                $emp = strtoupper(trim((string) $row->empresa));
                $cc = trim((string) $row->centro_codigo);
                $clave = $emp.'|'.$cc;
                if (! isset($porCentro[$clave])) {
                    foreach (array_keys($porCentro) as $k) {
                        $parts = explode('|', $k, 2);
                        if (count($parts) === 2
                            && strtoupper($parts[0]) === $emp
                            && $this->mismoCentroCodigo($parts[1], $cc)) {
                            $clave = $k;
                            break;
                        }
                    }
                }
                if (! isset($porCentro[$clave])) {
                    continue;
                }
                $fecha = null;
                if (! empty($row->updated_at)) {
                    $ts = strtotime((string) $row->updated_at);
                    $fecha = $ts ? date('d/m/Y', $ts) : null;
                }
                $porCentro[$clave]['estado'] = $row->estado ?: 'en_proceso';
                $porCentro[$clave]['fecha'] = $fecha;
            }
        }

        $local = app(CcCatalogoLocal::class);
        if ($local->disponible() && Schema::hasTable('tbl_cc_catalogo_centros')) {
            foreach ($grupos as $lista) {
                foreach ($lista as $item) {
                    if ($porCentro[$item['clave']]['depto'] !== '') {
                        continue;
                    }
                    $emps = array_values(array_unique(array_filter([
                        $item['raw'],
                        $alias[$item['raw']] ?? null,
                    ])));
                    $row = DB::table('tbl_cc_catalogo_centros')
                        ->where(function ($q) use ($emps) {
                            foreach ($emps as $emp) {
                                $q->orWhereRaw('UPPER(empresa) = ?', [strtoupper($emp)]);
                            }
                        })
                        ->where(function ($q) use ($item) {
                            $cc = $item['cc'];
                            $alt = ltrim($cc, '0');
                            $q->where('codigo', $cc);
                            if ($alt !== '' && $alt !== $cc) {
                                $q->orWhere('codigo', $alt);
                            }
                        })
                        ->value('departamento');
                    $d = trim((string) $row);
                    if ($d !== '' && ! preg_match('/^\d+$/', $d)) {
                        $porCentro[$item['clave']]['depto'] = $d;
                    }
                }
            }
        }

        return response()->json([
            'ok' => true,
            'year' => $year,
            'ciclo' => $ciclo,
            'por_centro' => (object) $porCentro,
        ]);
    }

    public function sincronizarGastoSap(Request $request): JsonResponse
    {
        @set_time_limit(300);
        $year = (int) $request->input('year', $request->input('anio', 0));
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }
        $centros = $request->input('centros', []);
        if (! is_array($centros)) {
            $centros = [];
        }
        $alias = ['ABSA' => 'AUSTIN'];
        $grupos = [];
        foreach (array_slice($centros, 0, 500) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $raw = strtoupper(trim((string) ($item['empresa'] ?? '')));
            $cc = trim((string) ($item['cc'] ?? ''));
            if ($raw === '' || $cc === '') {
                continue;
            }
            $sap = $alias[$raw] ?? $raw;
            $grupos[$sap][$cc] = $cc;
        }

        $snap = app(CcGastoRealSnapshot::class);
        $changed = [];
        foreach ($grupos as $empresa => $ccs) {
            try {
                $wrote = $this->refrescarGastoEmpresa($empresa, $year, array_values($ccs), $snap);
            } catch (Throwable $e) {
                continue;
            }
            if ($wrote) {
                $changed[] = $empresa;
            }
        }

        return response()->json([
            'ok' => true,
            'year' => $year,
            'changed' => $changed,
            'cargas' => $snap->resumen($year),
        ]);
    }

    public function sincronizarGastoCentro(Request $request): JsonResponse
    {
        @ignore_user_abort(true);
        @set_time_limit(0);
        $pedido = $this->pedidoGastoCentro($request);
        if ($pedido instanceof JsonResponse) {
            return $pedido;
        }
        [$empresa, $cc, $year, $corrida] = $pedido;

        $snap = app(CcGastoRealSnapshot::class);
        if (! $snap->disponible()) {
            return response()->json(['ok' => false, 'estado' => 'fallo', 'mensaje' => 'La copia local no está disponible.'], 200);
        }

        if ($request->boolean('desde_json')) {
            return $this->insertarGastoCentroDesdeJson($empresa, $year, $cc, $corrida, $snap);
        }

        $this->escribirEstadoJsonGasto($empresa, $year, $cc, [
            'corrida' => $corrida,
            'listo' => false,
            'insertado' => false,
            'estado' => 'descargando',
            'cuentas' => 0,
            'mensaje' => null,
        ]);

        try {
            $remoto = $this->mapaGastoPorCentros($empresa, $year, [$cc], true);
        } catch (Throwable $e) {
            return $this->recuperarGastoCentroDesdeJson($empresa, $year, $cc, $corrida, $snap, 'SAP no respondió para este centro.');
        }

        $mapa = is_array($remoto['mapa'] ?? null) ? $remoto['mapa'] : [];
        if (! isset($mapa[$cc]) || empty($remoto['ok'])) {
            return $this->recuperarGastoCentroDesdeJson($empresa, $year, $cc, $corrida, $snap, 'SAP no devolvió este centro.');
        }

        $filas = array_values(array_filter(
            $this->filasGastoSapDesdeRegistros(is_array($remoto['rows'] ?? null) ? $remoto['rows'] : [], $empresa, $year),
            function ($fila) use ($cc) {
                return is_array($fila)
                    && strtoupper(trim((string) ($fila['centro'] ?? ''))) === $cc;
            }
        ));
        if (! $this->guardarJsonGastoCentro($empresa, $year, $cc, [$cc => $mapa[$cc]], $filas, $corrida)) {
            return response()->json([
                'ok' => false,
                'estado' => 'fallo',
                'empresa' => $empresa,
                'cc' => $cc,
                'mensaje' => 'No se pudo escribir el archivo de este centro.',
            ], 200);
        }

        return $this->insertarGastoCentroDesdeJson($empresa, $year, $cc, $corrida, $snap);
    }

    public function estadoJsonGastoCentro(Request $request): JsonResponse
    {
        $pedido = $this->pedidoGastoCentro($request);
        if ($pedido instanceof JsonResponse) {
            return $pedido;
        }
        [$empresa, $cc, $year] = $pedido;
        $estado = $this->leerEstadoJsonGasto($empresa, $year, $cc);

        return response()->json(array_merge([
            'ok' => true,
            'empresa' => $empresa,
            'cc' => $cc,
            'year' => $year,
            'corrida' => '',
            'listo' => false,
            'insertado' => false,
            'estado' => '',
            'cuentas' => 0,
            'mensaje' => null,
        ], $estado));
    }

    /**
     * Baja una sola cuenta (empresa + centro + cuenta de la asignación),
     * la guarda en archivo y luego la inserta. Si se corta la red, se reanuda desde el JSON.
     */
    public function sincronizarGastoCuentaArchivo(Request $request): JsonResponse
    {
        @ignore_user_abort(true);
        @set_time_limit(0);
        $pedido = $this->pedidoGastoCuenta($request);
        if ($pedido instanceof JsonResponse) {
            return $pedido;
        }
        [$empresa, $cc, $cuenta, $year, $corrida] = $pedido;

        $snap = app(CcGastoRealSnapshot::class);
        if (! $snap->disponible()) {
            return response()->json(['ok' => false, 'estado' => 'fallo', 'mensaje' => 'La copia local no está disponible.'], 200);
        }

        if ($request->boolean('desde_json')) {
            return $this->insertarGastoCuentaDesdeJson($empresa, $year, $cc, $cuenta, $corrida, $snap);
        }

        $this->escribirEstadoJsonGastoCuenta($empresa, $year, $cc, $cuenta, [
            'corrida' => $corrida,
            'listo' => false,
            'insertado' => false,
            'estado' => 'descargando',
            'mensaje' => null,
        ]);

        try {
            $api = app(AutinApiClient::class);
            $res = $api->gastoRealPorCuentas($empresa, $year, [$cuenta], 40, 2, $cc, '6');
        } catch (Throwable $e) {
            return $this->recuperarGastoCuentaDesdeJson($empresa, $year, $cc, $cuenta, $corrida, $snap, 'SAP no respondió para esta cuenta.');
        }

        if (empty($res['ok']) && empty($res['rows'])) {
            return $this->recuperarGastoCuentaDesdeJson(
                $empresa,
                $year,
                $cc,
                $cuenta,
                $corrida,
                $snap,
                $res['message'] ?? 'No se pudo leer esa cuenta en SAP.'
            );
        }

        $todo = [];
        foreach ($res['rows'] ?? [] as $row) {
            if (is_array($row)) {
                $this->acumularGastoRealFila($todo, $row, $empresa, $year, [$cuenta], $cc);
            }
        }
        $hit = $this->gastoDesdeIndice($todo, $cuenta);
        if (! is_array($hit)) {
            $hit = $this->itemGastoVacio($cuenta);
        }
        $hit['codigo'] = $cuenta;

        $filas = array_values(array_filter(
            $this->filasGastoSapDesdeRegistros(is_array($res['rows'] ?? null) ? $res['rows'] : [], $empresa, $year),
            function ($fila) use ($cc, $cuenta) {
                return is_array($fila)
                    && strtoupper(trim((string) ($fila['centro'] ?? ''))) === $cc
                    && trim((string) ($fila['cuenta'] ?? '')) === $cuenta;
            }
        ));

        if (! $this->guardarJsonGastoCuenta($empresa, $year, $cc, $cuenta, $hit, $filas, $corrida)) {
            return response()->json([
                'ok' => false,
                'estado' => 'fallo',
                'empresa' => $empresa,
                'cc' => $cc,
                'cuenta' => $cuenta,
                'mensaje' => 'No se pudo escribir el archivo de esta cuenta.',
            ], 200);
        }

        return $this->insertarGastoCuentaDesdeJson($empresa, $year, $cc, $cuenta, $corrida, $snap);
    }

    public function estadoJsonGastoCuenta(Request $request): JsonResponse
    {
        $pedido = $this->pedidoGastoCuenta($request);
        if ($pedido instanceof JsonResponse) {
            return $pedido;
        }
        [$empresa, $cc, $cuenta, $year] = $pedido;
        $estado = $this->leerEstadoJsonGastoCuenta($empresa, $year, $cc, $cuenta);

        return response()->json(array_merge([
            'ok' => true,
            'empresa' => $empresa,
            'cc' => $cc,
            'cuenta' => $cuenta,
            'year' => $year,
            'corrida' => '',
            'listo' => false,
            'insertado' => false,
            'estado' => '',
            'mensaje' => null,
        ], $estado));
    }

    /**
     * @return array{0: string, 1: string, 2: int, 3: string}|JsonResponse
     */
    protected function pedidoGastoCentro(Request $request)
    {
        $empresa = strtoupper(trim((string) $request->input('empresa', '')));
        $cc = strtoupper(trim((string) $request->input('cc', '')));
        $year = (int) $request->input('year', $request->input('anio', 0));
        $corrida = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $request->input('corrida', ''));
        $alias = ['ABSA' => 'AUSTIN'];
        if (isset($alias[$empresa])) {
            $empresa = $alias[$empresa];
        }
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }
        if ($empresa === '' || $cc === '' || $cc === 'SIN_CC' || $cc === 'EMPRESA') {
            return response()->json(['ok' => false, 'estado' => 'fallo', 'mensaje' => 'Falta la empresa o el centro.'], 422);
        }
        if (! preg_match('/^[A-Z0-9._-]{1,40}$/', $empresa) || ! preg_match('/^[A-Z0-9._-]{1,40}$/', $cc)) {
            return response()->json(['ok' => false, 'estado' => 'fallo', 'mensaje' => 'La empresa o el centro no se pueden guardar en archivo.'], 422);
        }

        return [$empresa, $cc, $year, $corrida];
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: int, 4: string}|JsonResponse
     */
    protected function pedidoGastoCuenta(Request $request)
    {
        $base = $this->pedidoGastoCentro($request);
        if ($base instanceof JsonResponse) {
            return $base;
        }
        [$empresa, $cc, $year, $corrida] = $base;
        $cuenta = trim((string) $request->input('cuenta', ''));
        if ($cuenta === '' || strlen($cuenta) > 40) {
            return response()->json(['ok' => false, 'estado' => 'fallo', 'mensaje' => 'Falta la cuenta.'], 422);
        }

        return [$empresa, $cc, $cuenta, $year, $corrida];
    }

    protected function carpetaJsonGasto(string $empresa, int $year): string
    {
        $dir = storage_path('app/cc-gasto-sap/'.$empresa.'/'.$year);
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir;
    }

    protected function carpetaJsonGastoCuenta(string $empresa, int $year, string $cc): string
    {
        $dir = $this->carpetaJsonGasto($empresa, $year).'/'.$cc;
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir;
    }

    protected function archivoSeguroCuenta(string $cuenta): string
    {
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', trim($cuenta)) ?? '';
        $safe = trim($safe, '._-');
        if ($safe === '') {
            $safe = 'cuenta';
        }

        return substr($safe, 0, 80);
    }

    protected function rutaJsonGastoCentro(string $empresa, int $year, string $cc, bool $estado = false): string
    {
        return $this->carpetaJsonGasto($empresa, $year).'/'.$cc.($estado ? '.estado.json' : '.json');
    }

    protected function rutaJsonGastoCuenta(string $empresa, int $year, string $cc, string $cuenta, bool $estado = false): string
    {
        return $this->carpetaJsonGastoCuenta($empresa, $year, $cc).'/'.$this->archivoSeguroCuenta($cuenta).($estado ? '.estado.json' : '.json');
    }

    /**
     * @param  array<string, mixed>  $estado
     */
    protected function escribirEstadoJsonGastoCuenta(string $empresa, int $year, string $cc, string $cuenta, array $estado): void
    {
        $path = $this->rutaJsonGastoCuenta($empresa, $year, $cc, $cuenta, true);
        $estado['empresa'] = $empresa;
        $estado['cc'] = $cc;
        $estado['cuenta'] = $cuenta;
        $estado['year'] = $year;
        $estado['actualizado_en'] = now()->toIso8601String();
        $json = json_encode($estado, JSON_UNESCAPED_UNICODE);
        if ($json !== false) {
            @file_put_contents($path, $json, LOCK_EX);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function leerEstadoJsonGastoCuenta(string $empresa, int $year, string $cc, string $cuenta): array
    {
        $path = $this->rutaJsonGastoCuenta($empresa, $year, $cc, $cuenta, true);
        if (! is_file($path)) {
            return [];
        }
        $data = json_decode((string) @file_get_contents($path), true);

        return is_array($data) ? $data : [];
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<int, array<string, mixed>>  $filas
     */
    protected function guardarJsonGastoCuenta(string $empresa, int $year, string $cc, string $cuenta, array $item, array $filas, string $corrida): bool
    {
        $path = $this->rutaJsonGastoCuenta($empresa, $year, $cc, $cuenta);
        $json = json_encode([
            'empresa' => $empresa,
            'anio' => $year,
            'cc' => $cc,
            'cuenta' => $cuenta,
            'corrida' => $corrida,
            'item' => $item,
            'filas' => $filas,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            return false;
        }
        $tmp = $path.'.tmp';
        if (@file_put_contents($tmp, $json, LOCK_EX) === false) {
            return false;
        }
        if (is_file($path)) {
            @unlink($path);
        }
        if (! @rename($tmp, $path)) {
            $ok = @file_put_contents($path, $json, LOCK_EX) !== false;
            @unlink($tmp);
            if (! $ok) {
                return false;
            }
        }
        $this->escribirEstadoJsonGastoCuenta($empresa, $year, $cc, $cuenta, [
            'corrida' => $corrida,
            'listo' => true,
            'insertado' => false,
            'estado' => 'archivo',
            'mensaje' => null,
        ]);

        return true;
    }

    protected function insertarGastoCuentaDesdeJson(string $empresa, int $year, string $cc, string $cuenta, string $corrida, CcGastoRealSnapshot $snap): JsonResponse
    {
        $estado = $this->leerEstadoJsonGastoCuenta($empresa, $year, $cc, $cuenta);
        if ($corrida !== '' && (string) ($estado['corrida'] ?? '') !== $corrida) {
            return response()->json([
                'ok' => false,
                'estado' => 'fallo',
                'empresa' => $empresa,
                'cc' => $cc,
                'cuenta' => $cuenta,
                'mensaje' => 'El archivo de esta cuenta todavía no está listo.',
            ], 200);
        }
        if (empty($estado['listo'])) {
            return response()->json([
                'ok' => false,
                'estado' => 'fallo',
                'empresa' => $empresa,
                'cc' => $cc,
                'cuenta' => $cuenta,
                'mensaje' => 'Todavía no hay archivo de esta cuenta.',
            ], 200);
        }
        if (! empty($estado['insertado']) && in_array((string) ($estado['estado'] ?? ''), ['registrado', 'actualizado', 'sin_cambio'], true)) {
            return response()->json([
                'ok' => true,
                'estado' => (string) $estado['estado'],
                'empresa' => $empresa,
                'cc' => $cc,
                'cuenta' => $cuenta,
                'year' => $year,
                'origen' => 'json',
                'mensaje' => null,
            ]);
        }

        $path = $this->rutaJsonGastoCuenta($empresa, $year, $cc, $cuenta);
        $data = json_decode((string) @file_get_contents($path), true);
        $item = is_array($data['item'] ?? null) ? $data['item'] : null;
        $filas = is_array($data['filas'] ?? null) ? $data['filas'] : [];
        if (! is_array($item)) {
            return response()->json([
                'ok' => false,
                'estado' => 'fallo',
                'empresa' => $empresa,
                'cc' => $cc,
                'cuenta' => $cuenta,
                'mensaje' => 'El archivo de esta cuenta no se puede leer.',
            ], 200);
        }

        $existia = DB::table('tbl_cc_gasto_real_snap')
            ->where('empresa', $empresa)
            ->where('anio', $year)
            ->where('centro_codigo', $cc)
            ->where('cuenta_codigo', $cuenta)
            ->exists();

        if ($existia && $this->cuentaGastoSnapIgual($empresa, $year, $cc, $cuenta, $item)) {
            $this->escribirEstadoJsonGastoCuenta($empresa, $year, $cc, $cuenta, [
                'corrida' => $corrida !== '' ? $corrida : (string) ($estado['corrida'] ?? ''),
                'listo' => true,
                'insertado' => true,
                'estado' => 'sin_cambio',
                'mensaje' => null,
            ]);

            return response()->json([
                'ok' => true,
                'estado' => 'sin_cambio',
                'empresa' => $empresa,
                'cc' => $cc,
                'cuenta' => $cuenta,
                'year' => $year,
                'origen' => 'json',
                'mensaje' => null,
            ]);
        }

        try {
            $snap->reemplazarCuenta($empresa, $year, $cc, $cuenta, $item, $filas, auth()->id());
        } catch (Throwable $e) {
            $this->escribirEstadoJsonGastoCuenta($empresa, $year, $cc, $cuenta, [
                'corrida' => $corrida !== '' ? $corrida : (string) ($estado['corrida'] ?? ''),
                'listo' => true,
                'insertado' => false,
                'estado' => 'archivo',
                'mensaje' => 'No se pudo insertar desde el archivo.',
            ]);

            return response()->json([
                'ok' => false,
                'estado' => 'fallo',
                'empresa' => $empresa,
                'cc' => $cc,
                'cuenta' => $cuenta,
                'mensaje' => 'No se pudo insertar desde el archivo.',
            ], 200);
        }

        $marca = $existia ? 'actualizado' : 'registrado';
        $this->escribirEstadoJsonGastoCuenta($empresa, $year, $cc, $cuenta, [
            'corrida' => $corrida !== '' ? $corrida : (string) ($estado['corrida'] ?? ''),
            'listo' => true,
            'insertado' => true,
            'estado' => $marca,
            'mensaje' => null,
        ]);

        return response()->json([
            'ok' => true,
            'estado' => $marca,
            'empresa' => $empresa,
            'cc' => $cc,
            'cuenta' => $cuenta,
            'year' => $year,
            'origen' => 'json',
            'mensaje' => null,
        ]);
    }

    protected function recuperarGastoCuentaDesdeJson(string $empresa, int $year, string $cc, string $cuenta, string $corrida, CcGastoRealSnapshot $snap, string $mensaje): JsonResponse
    {
        $estado = $this->leerEstadoJsonGastoCuenta($empresa, $year, $cc, $cuenta);
        if (! empty($estado['listo']) && (string) ($estado['corrida'] ?? '') === $corrida) {
            return $this->insertarGastoCuentaDesdeJson($empresa, $year, $cc, $cuenta, $corrida, $snap);
        }
        $this->escribirEstadoJsonGastoCuenta($empresa, $year, $cc, $cuenta, [
            'corrida' => $corrida,
            'listo' => false,
            'insertado' => false,
            'estado' => 'fallo',
            'mensaje' => $mensaje,
        ]);

        return response()->json([
            'ok' => false,
            'estado' => 'fallo',
            'empresa' => $empresa,
            'cc' => $cc,
            'cuenta' => $cuenta,
            'mensaje' => $mensaje,
        ], 200);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function cuentaGastoSnapIgual(string $empresa, int $year, string $cc, string $cuenta, array $item): bool
    {
        $row = DB::table('tbl_cc_gasto_real_snap')
            ->where('empresa', $empresa)
            ->where('anio', $year)
            ->where('centro_codigo', $cc)
            ->where('cuenta_codigo', $cuenta)
            ->first();
        if (! $row) {
            return false;
        }
        $gasto = array_values(is_array($item['gasto'] ?? null) ? $item['gasto'] : []);
        $usd = array_values(is_array($item['gasto_usd'] ?? null) ? $item['gasto_usd'] : []);
        for ($i = 1; $i <= 9; $i++) {
            $suf = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $g = round((float) ($gasto[$i - 1] ?? 0), 2);
            $u = round((float) ($usd[$i - 1] ?? 0), 2);
            if (round((float) ($row->{'mes_'.$suf} ?? 0), 2) !== $g) {
                return false;
            }
            if (round((float) ($row->{'usd_'.$suf} ?? 0), 2) !== $u) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $estado
     */
    protected function escribirEstadoJsonGasto(string $empresa, int $year, string $cc, array $estado): void
    {
        $path = $this->rutaJsonGastoCentro($empresa, $year, $cc, true);
        $estado['empresa'] = $empresa;
        $estado['cc'] = $cc;
        $estado['year'] = $year;
        $estado['actualizado_en'] = now()->toIso8601String();
        $json = json_encode($estado, JSON_UNESCAPED_UNICODE);
        if ($json !== false) {
            @file_put_contents($path, $json, LOCK_EX);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function leerEstadoJsonGasto(string $empresa, int $year, string $cc): array
    {
        $path = $this->rutaJsonGastoCentro($empresa, $year, $cc, true);
        if (! is_file($path)) {
            return [];
        }
        $data = json_decode((string) @file_get_contents($path), true);

        return is_array($data) ? $data : [];
    }

    /**
     * @param  array<string, array<string, mixed>>  $mapa
     * @param  array<int, array<string, mixed>>  $filas
     */
    protected function guardarJsonGastoCentro(string $empresa, int $year, string $cc, array $mapa, array $filas, string $corrida): bool
    {
        $path = $this->rutaJsonGastoCentro($empresa, $year, $cc);
        $json = json_encode([
            'empresa' => $empresa,
            'anio' => $year,
            'cc' => $cc,
            'corrida' => $corrida,
            'mapa' => $mapa,
            'filas' => $filas,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            return false;
        }
        $tmp = $path.'.tmp';
        if (@file_put_contents($tmp, $json, LOCK_EX) === false) {
            return false;
        }
        if (is_file($path)) {
            @unlink($path);
        }
        if (! @rename($tmp, $path)) {
            $ok = @file_put_contents($path, $json, LOCK_EX) !== false;
            @unlink($tmp);
            if (! $ok) {
                return false;
            }
        }
        $this->escribirEstadoJsonGasto($empresa, $year, $cc, [
            'corrida' => $corrida,
            'listo' => true,
            'insertado' => false,
            'estado' => 'archivo',
            'cuentas' => 0,
            'mensaje' => null,
        ]);

        return true;
    }

    protected function insertarGastoCentroDesdeJson(string $empresa, int $year, string $cc, string $corrida, CcGastoRealSnapshot $snap): JsonResponse
    {
        $estado = $this->leerEstadoJsonGasto($empresa, $year, $cc);
        if ($corrida !== '' && (string) ($estado['corrida'] ?? '') !== $corrida) {
            return response()->json([
                'ok' => false,
                'estado' => 'fallo',
                'empresa' => $empresa,
                'cc' => $cc,
                'mensaje' => 'El archivo de este centro todavía no está listo.',
            ], 200);
        }
        if (empty($estado['listo'])) {
            return response()->json([
                'ok' => false,
                'estado' => 'fallo',
                'empresa' => $empresa,
                'cc' => $cc,
                'mensaje' => 'Todavía no hay archivo de este centro.',
            ], 200);
        }
        if (! empty($estado['insertado']) && in_array((string) ($estado['estado'] ?? ''), ['registrado', 'actualizado', 'sin_cambio'], true)) {
            return response()->json([
                'ok' => true,
                'estado' => (string) $estado['estado'],
                'empresa' => $empresa,
                'cc' => $cc,
                'year' => $year,
                'cuentas' => (int) ($estado['cuentas'] ?? 0),
                'origen' => 'json',
                'mensaje' => null,
            ]);
        }

        $path = $this->rutaJsonGastoCentro($empresa, $year, $cc);
        $data = json_decode((string) @file_get_contents($path), true);
        $mapa = is_array($data['mapa'] ?? null) ? $data['mapa'] : [];
        $filas = is_array($data['filas'] ?? null) ? $data['filas'] : [];
        if (! isset($mapa[$cc]) || ! is_array($mapa[$cc])) {
            return response()->json([
                'ok' => false,
                'estado' => 'fallo',
                'empresa' => $empresa,
                'cc' => $cc,
                'mensaje' => 'El archivo de este centro no se puede leer.',
            ], 200);
        }

        $existia = $snap->centroExiste($empresa, $year, $cc);
        if ($existia && $snap->centroIgual($empresa, $year, $cc, $mapa[$cc], $filas)) {
            $cuentas = (int) DB::table('tbl_cc_gasto_real_snap')
                ->where('empresa', $empresa)
                ->where('anio', $year)
                ->where('centro_codigo', $cc)
                ->count();
            $this->escribirEstadoJsonGasto($empresa, $year, $cc, [
                'corrida' => $corrida !== '' ? $corrida : (string) ($estado['corrida'] ?? ''),
                'listo' => true,
                'insertado' => true,
                'estado' => 'sin_cambio',
                'cuentas' => $cuentas,
                'mensaje' => null,
            ]);

            return response()->json([
                'ok' => true,
                'estado' => 'sin_cambio',
                'empresa' => $empresa,
                'cc' => $cc,
                'year' => $year,
                'cuentas' => $cuentas,
                'origen' => 'json',
                'mensaje' => null,
            ]);
        }
        try {
            $snap->fusionarCentro($empresa, $year, [$cc => $mapa[$cc]], $filas, auth()->id());
        } catch (Throwable $e) {
            $this->escribirEstadoJsonGasto($empresa, $year, $cc, [
                'corrida' => $corrida !== '' ? $corrida : (string) ($estado['corrida'] ?? ''),
                'listo' => true,
                'insertado' => false,
                'estado' => 'archivo',
                'cuentas' => 0,
                'mensaje' => 'No se pudo insertar desde el archivo.',
            ]);

            return response()->json([
                'ok' => false,
                'estado' => 'fallo',
                'empresa' => $empresa,
                'cc' => $cc,
                'mensaje' => 'No se pudo insertar desde el archivo.',
            ], 200);
        }

        $marca = $existia ? 'actualizado' : 'registrado';
        $cuentas = (int) DB::table('tbl_cc_gasto_real_snap')
            ->where('empresa', $empresa)
            ->where('anio', $year)
            ->where('centro_codigo', $cc)
            ->count();
        $this->escribirEstadoJsonGasto($empresa, $year, $cc, [
            'corrida' => $corrida !== '' ? $corrida : (string) ($estado['corrida'] ?? ''),
            'listo' => true,
            'insertado' => true,
            'estado' => $marca,
            'cuentas' => $cuentas,
            'mensaje' => null,
        ]);

        return response()->json([
            'ok' => true,
            'estado' => $marca,
            'empresa' => $empresa,
            'cc' => $cc,
            'year' => $year,
            'cuentas' => $cuentas,
            'origen' => 'json',
            'mensaje' => null,
        ]);
    }

    protected function recuperarGastoCentroDesdeJson(string $empresa, int $year, string $cc, string $corrida, CcGastoRealSnapshot $snap, string $mensaje): JsonResponse
    {
        $estado = $this->leerEstadoJsonGasto($empresa, $year, $cc);
        if (! empty($estado['listo']) && (string) ($estado['corrida'] ?? '') === $corrida) {
            return $this->insertarGastoCentroDesdeJson($empresa, $year, $cc, $corrida, $snap);
        }
        $this->escribirEstadoJsonGasto($empresa, $year, $cc, [
            'corrida' => $corrida,
            'listo' => false,
            'insertado' => false,
            'estado' => 'fallo',
            'cuentas' => 0,
            'mensaje' => $mensaje,
        ]);

        return response()->json([
            'ok' => false,
            'estado' => 'fallo',
            'empresa' => $empresa,
            'cc' => $cc,
            'mensaje' => $mensaje,
        ], 200);
    }

    public function actualizarGastoCuenta(Request $request): JsonResponse
    {
        @set_time_limit(120);
        $empresa = strtoupper(trim((string) $request->input('empresa', '')));
        $cc = trim((string) $request->input('cc', ''));
        $cuenta = trim((string) $request->input('cuenta', ''));
        $year = (int) $request->input('year', $request->input('anio', 0));
        $alias = ['ABSA' => 'AUSTIN'];
        if (isset($alias[$empresa])) {
            $empresa = $alias[$empresa];
        }
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }
        if ($empresa === '' || $cc === '' || $cuenta === '') {
            return response()->json(['ok' => false, 'mensaje' => 'Falta empresa, centro o cuenta.'], 422);
        }

        $api = app(AutinApiClient::class);
        $res = $api->gastoRealPorCuentas($empresa, $year, [$cuenta], 40, 2, $cc, '6');
        if (empty($res['ok']) && empty($res['rows'])) {
            return response()->json([
                'ok' => false,
                'mensaje' => $res['message'] ?? 'No se pudo leer esa cuenta en SAP.',
            ], 200);
        }

        $ck = $this->codigoCuentaKey($cuenta);
        $todo = [];
        foreach ($res['rows'] ?? [] as $row) {
            if (is_array($row)) {
                $this->acumularGastoRealFila($todo, $row, $empresa, $year, $ck !== '' ? [$ck] : [], $cc);
            }
        }
        $hit = $ck !== '' ? $this->gastoDesdeIndice($todo, $ck) : null;
        if (! is_array($hit)) {
            $hit = [
                'codigo' => $cuenta,
                'nombre' => '',
                'gasto' => array_fill(0, 12, 0.0),
                'gasto_usd' => array_fill(0, 12, 0.0),
            ];
        }
        $hit['codigo'] = $cuenta;
        $filas = array_values(array_filter(
            $this->filasGastoSapDesdeRegistros(is_array($res['rows'] ?? null) ? $res['rows'] : [], $empresa, $year),
            function ($fila) use ($cc, $ck) {
                if (! is_array($fila) || $ck === '') {
                    return false;
                }

                return $this->mismoCentroSap((string) ($fila['centro'] ?? ''), $cc)
                    && $this->codigoCuentaKey((string) ($fila['cuenta'] ?? '')) === $ck;
            }
        ));

        app(CcGastoRealSnapshot::class)->reemplazarCuenta($empresa, $year, $cc, $cuenta, $hit, $filas, auth()->id());

        return response()->json([
            'ok' => true,
            'year' => $year,
            'origen' => 'sap',
            'por_cuenta' => (object) [$ck !== '' ? $ck : $cuenta => $hit],
            'mensaje' => null,
        ]);
    }

    public function estadoGastoSap(Request $request): JsonResponse
    {
        $year = (int) $request->get('year', $request->get('anio', 0));
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }
        $snap = app(CcGastoRealSnapshot::class);

        return response()->json([
            'ok' => true,
            'year' => $year,
            'cargas' => $snap->resumen($year),
        ]);
    }

    public function plantillaGastoSap()
    {
        $headings = [
            'Empresa', 'CC', 'PrcCode', 'Cuenta', 'FormatCode', 'AcctCode',
            'DescCuenta', 'AcctName', 'DEPTO', 'Fecha', 'DocDate', 'RefDate', 'TaxDate',
            'Importe', 'LineTotal', 'Debit', 'Credit', 'ImporteDlls', 'GroupMask', 'year',
        ];

        return Excel::download(
            new CcCapturaPlantillaExport($headings, [], []),
            'plantilla_gasto_sap_centros.xlsx'
        );
    }

    public function importarGastoSap(Request $request): JsonResponse
    {
        @set_time_limit(300);
        $request->validate([
            'archivo' => 'required|file|max:51200',
            'anio' => 'nullable|integer|min:2000|max:2100',
        ]);
        $ext = strtolower($request->file('archivo')->getClientOriginalExtension());
        if (! in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            return response()->json(['message' => 'El archivo debe ser Excel (.xlsx, .xls) o CSV.'], 422);
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

        $anioForzado = (int) $request->input('anio', 0);
        $filas = $this->filasGastoSapDesdeHoja($sheet, $anioForzado > 2000 ? $anioForzado : null);
        if ($filas === []) {
            return response()->json(['message' => 'No encontré movimientos. Revisa Empresa, CC, Cuenta y Fecha (o year).'], 422);
        }

        try {
            $stats = app(CcGastoRealSnapshot::class)->reemplazarDesdeArchivo($filas, auth()->id());
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if ($stats['filas'] < 1) {
            return response()->json([
                'message' => 'Ninguna fila se pudo guardar. Cada renglón necesita empresa, centro, cuenta y una fecha del año.',
                'omitidas' => $stats['omitidas'],
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'filas' => $stats['filas'],
            'cuentas' => $stats['cuentas'],
            'empresas' => $stats['empresas'],
            'omitidas' => $stats['omitidas'],
            'message' => 'Se guardaron '.$stats['filas'].' movimientos de SAP'
                .($stats['empresas'] ? ' ('.implode(', ', $stats['empresas']).')' : '')
                .'. El análisis usa esa copia y ya no baja ese año a SAP. Lo que no venga en el archivo queda en cero.',
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    protected function filasGastoSapDesdeRegistros(array $rows, string $empresa, int $year): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            if ($this->campoFila($row, ['Empresa', 'EMPRESA', 'empresa', 'DB']) === '') {
                $row['Empresa'] = $empresa;
            }
            $headers = array_keys($row);
            $built = $this->filasGastoSapDesdeHoja([$headers, array_values($row)], $year);
            foreach ($built as $item) {
                $out[] = $item;
            }
        }

        return $out;
    }

    /**
     * @param  array<int, array<int, mixed>>  $sheet
     * @return array<int, array<string, mixed>>
     */
    protected function filasGastoSapDesdeHoja(array $sheet, ?int $anioForzado): array
    {
        $headers = $sheet[0] ?? [];
        $alias = ['ABSA' => 'AUSTIN'];
        $out = [];
        foreach (array_slice($sheet, 1) as $cells) {
            if (! is_array($cells)) {
                continue;
            }
            $row = [];
            foreach ($headers as $i => $header) {
                $name = trim((string) $header);
                if ($name === '') {
                    continue;
                }
                $row[$name] = $cells[$i] ?? null;
            }
            if ($row === [] || $this->filaGastoSapVacia($row)) {
                continue;
            }
            $empresa = strtoupper($this->campoFila($row, ['Empresa', 'EMPRESA', 'empresa', 'DB']));
            $empresa = $alias[$empresa] ?? $empresa;
            $centro = $this->campoFila($row, ['CC', 'PrcCode', 'OcrCode', 'ProfitCode', 'centro', 'Centro']);
            $cuenta = $this->campoFila($row, ['Cuenta', 'FormatCode', 'AcctCode', 'CUENTA', 'codigo']);
            $nombre = $this->campoFila($row, ['DescCuenta', 'AcctName', 'NOMBRE', 'AccountName', 'nombre', 'cuenta_nombre']);
            $depto = $this->campoFila($row, ['DEPTO', 'Depto', 'depto', 'departamento']);
            $mask = $this->campoFila($row, ['GroupMask', 'group_mask', 'Group']);
            $fechaRaw = $this->valorFilaGasto($row, ['Fecha', 'fecha', 'FECHA', 'DocDate', 'RefDate', 'TaxDate', 'DueDate']);
            $fecha = $this->fechaGastoSap($fechaRaw);
            $anio = $this->anioDeFecha($fecha);
            if ($anio < 2000) {
                $anioTxt = $this->campoFila($row, ['year', 'anio', 'Año', 'Anio']);
                $anio = (int) $anioTxt;
            }
            if ($anio < 2000 && $anioForzado) {
                $anio = $anioForzado;
            }
            $mes = $this->mesDeFecha($fecha);
            if ($mes < 0) {
                $mesTxt = $this->campoFila($row, ['mes', 'Mes', 'month']);
                $mesNum = (int) $mesTxt;
                $mes = ($mesNum >= 1 && $mesNum <= 12) ? $mesNum - 1 : -1;
            }
            $importe = $this->importeGastoFila($row);
            $importeUsd = $this->importeGastoFilaUsd($row);
            $out[] = [
                'empresa' => $empresa,
                'centro' => $centro,
                'cuenta' => $cuenta,
                'cuenta_nombre' => $nombre,
                'depto' => $depto,
                'group_mask' => $mask,
                'fecha' => $fecha !== '' ? substr($fecha, 0, 10) : null,
                'anio' => $anio,
                'mes' => $mes >= 0 ? $mes + 1 : 0,
                'importe' => $importe,
                'importe_usd' => $importeUsd,
                'fila' => $this->filaGastoSapPlana($row),
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     * @return mixed
     */
    protected function valorFilaGasto(array $row, array $keys)
    {
        $index = [];
        foreach ($row as $key => $value) {
            $index[strtoupper(trim((string) $key))] = $value;
        }
        foreach ($keys as $key) {
            $up = strtoupper($key);
            if (! array_key_exists($up, $index) || $index[$up] === null || $index[$up] === '') {
                continue;
            }

            return $index[$up];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function filaGastoSapPlana(array $row): array
    {
        $out = [];
        foreach ($row as $key => $value) {
            if ($value instanceof \DateTimeInterface) {
                $out[$key] = $value->format('Y-m-d');
            } elseif (is_scalar($value) || $value === null) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function filaGastoSapVacia(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  mixed  $raw
     */
    protected function fechaGastoSap($raw): string
    {
        if ($raw instanceof \DateTimeInterface) {
            return $raw->format('Y-m-d');
        }
        if (is_numeric($raw)) {
            $serial = (float) $raw;
            if ($serial > 20000 && $serial < 80000) {
                $ts = (int) round(($serial - 25569) * 86400);

                return gmdate('Y-m-d', $ts);
            }
        }

        return trim((string) $raw);
    }

    /**
     * Oct–Dic aparte, desactivado.
     * Captura y análisis se quedan con el gasto del año completo (ene–dic).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function conMesesRecientes(array $payload, string $empresa, string $cc, int $year): array
    {
        return $payload;

        /*
        $por = $payload['por_cuenta'] ?? null;
        if (! is_array($por) || $por === [] || $cc === '') {
            return $payload;
        }

        $fuente = $year;
        if ($year === (int) date('Y') && (int) date('n') < 10) {
            $fuente = $year - 1;
        }
        $cacheKey = 'cc.gasto-ond.v1.'.$empresa.'.'.$cc.'.'.$fuente;
        $reciente = Cache::get($cacheKey);
        if (! is_array($reciente)) {
            $reciente = [];
            try {
                $res = app(AutinApiClient::class)->gastoRealTodasPaginas([
                    'Empresa' => $empresa,
                    'CC' => $cc,
                    'year' => $fuente,
                    'fecha_desde' => $fuente.'-10-01',
                    'fecha_hasta' => $fuente.'-12-31',
                    'GroupMask' => '6',
                ], 12, 4);
                if (! empty($res['ok'])) {
                    foreach ($res['rows'] as $row) {
                        if (is_array($row)) {
                            $this->acumularGastoRealFila($reciente, $row, $empresa, $fuente, [], $cc);
                        }
                    }
                }
            } catch (Throwable $e) {
                $reciente = [];
            }
            Cache::put($cacheKey, $reciente, 600);
        }

        foreach ($reciente as $key => $item) {
            if (! is_array($item) || ! isset($item['gasto']) || ! is_array($item['gasto'])) {
                continue;
            }
            if (! isset($por[$key]) || ! is_array($por[$key])) {
                $por[$key] = [
                    'codigo' => (string) ($item['codigo'] ?? $key),
                    'nombre' => (string) ($item['nombre'] ?? ''),
                    'gasto' => array_fill(0, 12, 0.0),
                    'gasto_usd' => array_fill(0, 12, 0.0),
                ];
            }
            if (! isset($por[$key]['gasto_usd']) || ! is_array($por[$key]['gasto_usd'])) {
                $por[$key]['gasto_usd'] = array_fill(0, 12, 0.0);
            }
            for ($m = 9; $m <= 11; $m++) {
                $por[$key]['gasto'][$m] = round((float) ($item['gasto'][$m] ?? 0), 2);
                $por[$key]['gasto_usd'][$m] = round((float) ($item['gasto_usd'][$m] ?? 0), 2);
            }
        }

        $payload['por_cuenta'] = $por;

        return $payload;
        */
    }

    /**
     * Conteos de la barra de avance. No trae los 12 meses ni el gasto real.
     */
    public function avanceCaptura(Request $request): JsonResponse
    {
        $ciclo = strtoupper(trim((string) $request->get('ciclo', '')));
        if ($ciclo === '') {
            return response()->json(['message' => 'Falta el ciclo.'], 422);
        }

        $vacio = [
            'ok' => true,
            'ciclo' => $ciclo,
            'centros' => 0,
            'centros_listos' => 0,
            'cuentas' => 0,
            'capturadas' => 0,
        ];
        $uid = (int) auth()->id();
        if ($uid <= 0
            || ! Schema::hasTable('tbl_cc_asignaciones')
            || ! Schema::hasTable('tbl_cc_asignacion_cuentas')
            || ! Schema::hasTable('tbl_cc_asignacion_permisos')
            || ! Schema::hasTable('tbl_cc_tipos_permiso')) {
            return response()->json($vacio);
        }

        $cuentas = DB::table('tbl_cc_asignaciones as a')
            ->join('tbl_cc_asignacion_permisos as ap', 'ap.asignacion_id', '=', 'a.id')
            ->join('tbl_cc_tipos_permiso as t', 't.id', '=', 'ap.permiso_id')
            ->leftJoin('tbl_cc_asignacion_cuentas as c', 'c.asignacion_id', '=', 'a.id')
            ->where('a.user_id', $uid)
            ->whereRaw('UPPER(a.ciclo_codigo) = ?', [$ciclo])
            ->whereIn('t.clave', ['capturar', 'editar'])
            ->whereRaw("UPPER(REPLACE(TRIM(a.centro_codigo), ' ', '')) <> 'EMPRESA'")
            ->get(['a.empresa', 'a.centro_codigo', 'c.cuenta_codigo']);

        $porCentro = [];
        foreach ($cuentas as $row) {
            $empresa = strtoupper(trim((string) $row->empresa));
            $centro = trim((string) $row->centro_codigo);
            if ($empresa === '' || $centro === '') {
                continue;
            }
            $ck = $empresa.'|'.$centro;
            if (! isset($porCentro[$ck])) {
                $porCentro[$ck] = ['empresa' => $empresa, 'centro' => $centro, 'cuentas' => []];
            }
            $cuenta = trim((string) ($row->cuenta_codigo ?? ''));
            if ($cuenta !== '') {
                $porCentro[$ck]['cuentas'][$cuenta] = true;
            }
        }

        $listas = [];
        if ($porCentro !== [] && Schema::hasTable('tbl_cc_presupuestos')) {
            $hasDone = Schema::hasColumn('tbl_cc_presupuestos', 'completado');
            $lleno = [];
            for ($i = 1; $i <= 12; $i++) {
                $lleno[] = 'mes_'.str_pad((string) $i, 2, '0', STR_PAD_LEFT).' IS NOT NULL';
            }
            $expr = '('.implode(' AND ', $lleno).')';
            if ($hasDone) {
                $expr = '(COALESCE(completado, 0) = 1 OR '.$expr.')';
            }
            $empresas = array_values(array_unique(array_column($porCentro, 'empresa')));
            $centros = array_values(array_unique(array_column($porCentro, 'centro')));
            $pres = DB::table('tbl_cc_presupuestos')
                ->where('ciclo_codigo', $ciclo)
                ->whereIn(DB::raw('UPPER(empresa)'), $empresas)
                ->whereIn('centro_codigo', $centros)
                ->selectRaw('empresa, centro_codigo, cuenta_codigo, CASE WHEN '.$expr.' THEN 1 ELSE 0 END as listo')
                ->get();
            foreach ($pres as $row) {
                if ((int) $row->listo !== 1) {
                    continue;
                }
                $key = strtoupper(trim((string) $row->empresa)).'|'.trim((string) $row->centro_codigo).'|'.trim((string) $row->cuenta_codigo);
                $listas[$key] = true;
            }
        }

        $totalCuentas = 0;
        $capturadas = 0;
        $centrosListos = 0;
        foreach ($porCentro as $centro) {
            $n = count($centro['cuentas']);
            $ok = 0;
            foreach (array_keys($centro['cuentas']) as $cuenta) {
                $key = $centro['empresa'].'|'.$centro['centro'].'|'.$cuenta;
                if (isset($listas[$key])) {
                    $ok++;
                }
            }
            $totalCuentas += $n;
            $capturadas += $ok;
            if ($n === 0 || $ok === $n) {
                $centrosListos++;
            }
        }

        return response()->json([
            'ok' => true,
            'ciclo' => $ciclo,
            'centros' => count($porCentro),
            'centros_listos' => $centrosListos,
            'cuentas' => $totalCuentas,
            'capturadas' => $capturadas,
        ]);
    }

    public function captura(Request $request): JsonResponse
    {
        $ciclo = strtoupper(trim((string) $request->get('ciclo', '')));
        if ($ciclo === '') {
            return response()->json(['message' => 'Falta el ciclo.'], 422);
        }
        $empresaFiltro = strtoupper(trim((string) $request->get('empresa', '')));
        $centroFiltro = trim((string) $request->get('centro', ''));

        $budgets = [];
        $completados = [];
        if (Schema::hasTable('tbl_cc_presupuestos')) {
            $hasDone = Schema::hasColumn('tbl_cc_presupuestos', 'completado');
            $cols = ['empresa', 'centro_codigo', 'cuenta_codigo'];
            if (Schema::hasColumn('tbl_cc_presupuestos', 'cuenta_nombre')) {
                $cols[] = 'cuenta_nombre';
            }
            for ($i = 1; $i <= 12; $i++) {
                $cols[] = 'mes_'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            }
            if ($hasDone) {
                $cols[] = 'completado';
            }
            $presQ = DB::table('tbl_cc_presupuestos')->where('ciclo_codigo', $ciclo);
            if ($empresaFiltro !== '') {
                $presQ->whereRaw('UPPER(empresa) = ?', [$empresaFiltro]);
            }
            if ($centroFiltro !== '') {
                $aliasCentro = ltrim($centroFiltro, '0');
                $presQ->where(function ($w) use ($centroFiltro, $aliasCentro) {
                    $w->where('centro_codigo', $centroFiltro);
                    if ($aliasCentro !== '' && $aliasCentro !== $centroFiltro) {
                        $w->orWhere('centro_codigo', $aliasCentro);
                    }
                });
            }
            foreach ($presQ->get($cols) as $row) {
                $meses = [];
                for ($i = 1; $i <= 12; $i++) {
                    $col = 'mes_'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                    $val = $row->{$col} ?? null;
                    $meses[] = ($val === null || $val === '') ? null : round((float) $val, 2);
                }
                $done = ($hasDone && ! empty($row->completado)) || $this->mesesTodosLlenos($meses);
                $empRow = (string) $row->empresa;
                $centroRow = (string) $row->centro_codigo;
                $cuentaRow = (string) $row->cuenta_codigo;
                $nombreRow = trim((string) ($row->cuenta_nombre ?? ''));
                foreach ($this->clavesCuentaCaptura($empRow, $centroRow, $cuentaRow, $nombreRow) as $key) {
                    if (! isset($budgets[$key])) {
                        $budgets[$key] = $meses;
                    }
                    if ($done) {
                        $completados[$key] = true;
                    }
                }
            }
        }

        $overlays = [];
        if (Schema::hasTable('tbl_cc_captura_centros')) {
            $ovQ = DB::table('tbl_cc_captura_centros')->where('ciclo_codigo', $ciclo);
            if ($empresaFiltro !== '') {
                $ovQ->whereRaw('UPPER(empresa) = ?', [$empresaFiltro]);
            }
            if ($centroFiltro !== '') {
                $aliasCentro = ltrim($centroFiltro, '0');
                $ovQ->where(function ($w) use ($centroFiltro, $aliasCentro) {
                    $w->where('centro_codigo', $centroFiltro);
                    if ($aliasCentro !== '' && $aliasCentro !== $centroFiltro) {
                        $w->orWhere('centro_codigo', $aliasCentro);
                    }
                });
            }
            foreach ($ovQ->get(['empresa', 'centro_codigo', 'estado', 'updated_at']) as $row) {
                $key = strtoupper(trim((string) $row->empresa)).'|'.trim((string) $row->centro_codigo);
                $fecha = null;
                if (! empty($row->updated_at)) {
                    $ts = strtotime((string) $row->updated_at);
                    $fecha = $ts ? date('d/m/Y', $ts) : null;
                }
                $overlays[$key] = [
                    'estado' => $row->estado ?: 'en_proceso',
                    'fecha' => $fecha,
                ];
            }
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
        $cuentaNombre = trim((string) ($data['cuenta_nombre'] ?? ''));

        $motivo = $this->motivoBloqueoCaptura($ciclo, $empresa, $centro);
        if ($motivo) {
            return response()->json(['message' => $motivo], 403);
        }

        $row = $this->presupuestoPorAlias($ciclo, $empresa, $centro, $cuenta, $cuentaNombre);
        if (! $row) {
            $row = new CcPresupuesto([
                'ciclo_codigo' => $ciclo,
                'empresa' => $empresa,
                'centro_codigo' => $centro,
                'cuenta_codigo' => $this->codigoCuentaCatalogo($empresa, $cuenta, $cuentaNombre) ?: $cuenta,
            ]);
        } else {
            $canon = $this->codigoCuentaCatalogo($empresa, $cuenta, $cuentaNombre);
            if ($canon !== '' && $canon !== (string) $row->cuenta_codigo) {
                $row->cuenta_codigo = $canon;
            }
        }
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
                    'centro_codigo' => (string) ($info['centro'] ?? $centro),
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
                    $this->codigoCuentaParaPlantilla($empresa, $cuenta, (string) ($cta->cuenta_nombre ?: '')),
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
                    'centro' => $centro,
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
                $format = $this->codigoCuentaParaPlantilla($empresa, $cuenta, (string) ($cta->cuenta_nombre ?: ''));
                if ($format !== '' && $format !== $cuenta) {
                    $alias = $this->capturaBudgetKey($empresa, $centro, $format);
                    if (! isset($out[$alias])) {
                        $out[$alias] = $payload;
                    } else {
                        $out[$alias]['capturar'] = $out[$alias]['capturar'] || $capturar;
                    }
                }
            }
        }

        return $this->aliasCentrosNumericosCaptura($out);
    }

    /**
     * Excel convierte 34.100 en 34.1. Si un solo centro asignado cae en ese número,
     * la fila importada se reconoce y se guarda con el código original.
     *
     * @param  array<string, array{cuenta: string, centro?: string, nombre: string, capturar: bool}>  $out
     * @return array<string, array{cuenta: string, centro?: string, nombre: string, capturar: bool}>
     */
    protected function aliasCentrosNumericosCaptura(array $out): array
    {
        $aliases = [];
        foreach ($out as $key => $payload) {
            $cc = trim((string) ($payload['centro'] ?? ''));
            $leido = $this->centroComoLoLeeExcel($cc);
            if ($leido === null || $leido === $cc) {
                continue;
            }
            $parts = explode('|', $key, 3);
            if (count($parts) < 3) {
                continue;
            }
            $alias = $parts[0].'|'.$leido.'|'.$parts[2];
            if (isset($out[$alias])) {
                continue;
            }
            if (! array_key_exists($alias, $aliases)) {
                $aliases[$alias] = $payload;
                continue;
            }
            if (! is_array($aliases[$alias]) || (string) ($aliases[$alias]['centro'] ?? '') !== $cc) {
                $aliases[$alias] = null;
            }
        }
        foreach ($aliases as $alias => $payload) {
            if (! is_array($payload) || isset($out[$alias])) {
                continue;
            }
            $out[$alias] = $payload;
        }

        return $out;
    }

    /**
     * Misma lectura que celdaTexto cuando Excel guardó el centro como número.
     */
    protected function centroComoLoLeeExcel(string $centro): ?string
    {
        $centro = trim($centro);
        if ($centro === '' || ! is_numeric($centro)) {
            return null;
        }
        $leido = $this->celdaTexto((float) $centro);

        return $leido === '' ? null : $leido;
    }

    /**
     * @param  array<int, string>  $cuentas
     * @return array{ok: bool, year: int, por_cuenta: array<string, array<string, mixed>>|\stdClass, mensaje: string|null}
     */
    /**
     * Misma cifra que Análisis: las cuentas pedidas, leídas de la copia local.
     *
     * @param  array<int, string>  $cuentas
     * @return array<string, mixed>|null
     */
    protected function gastoRealDesdeCopia(string $empresa, string $cc, int $year, array $cuentas): ?array
    {
        $mapa = $this->mapaGastoCopia($empresa, $year, [$cc]);
        $todo = $this->porCuentaDeIndiceEmpresa($mapa, $cc);
        if ($todo === []) {
            return null;
        }
        $por = $cuentas === [] ? [] : $this->recortarGastoPedido($todo, $cuentas);

        return [
            'ok' => true,
            'completo' => true,
            'year' => $year,
            'por_cuenta' => (object) $por,
            'origen' => 'copia',
            'mensaje' => null,
        ];
    }

    protected function cargarGastoRealCentro(string $empresa, string $cc, int $year, array $cuentas = [], bool $refrescar = false): array
    {
        $porCuenta = [];
        $ok = false;
        $mensaje = null;
        $pedido = [];
        foreach ($cuentas as $cuenta) {
            $cuenta = trim((string) $cuenta);
            $ck = $this->codigoCuentaKey($cuenta);
            if ($ck !== '') {
                $pedido[$ck] = $cuenta;
            }
        }

        if ($cc !== '' && $pedido) {
            $api = app(AutinApiClient::class);
            $res = $api->gastoRealPorCuentas($empresa, $year, array_values($pedido), 12, 4, $cc, '6');
            if (empty($res['ok']) && empty($res['rows'])) {
                $mensaje = $res['message'] ?? 'Sin conexión a gasto real SAP';
            } else {
                $ok = true;
                $todo = [];
                foreach ($res['rows'] ?? [] as $row) {
                    if (is_array($row)) {
                        $this->acumularGastoRealFila($todo, $row, $empresa, $year, array_keys($pedido), $cc);
                    }
                }
                foreach ($pedido as $ck => $cuenta) {
                    $hit = $this->gastoDesdeIndice($todo, $ck);
                    $porCuenta[$ck] = $hit ?: [
                        'codigo' => $cuenta,
                        'nombre' => '',
                        'gasto' => array_fill(0, 12, 0.0),
                        'gasto_usd' => array_fill(0, 12, 0.0),
                    ];
                }
            }
        } elseif ($cc !== '') {
            $indice = $this->indiceGastoPorCentro($empresa, $cc, $year, ! $refrescar);
            if (empty($indice['ok'])) {
                $mensaje = $indice['mensaje'] ?? 'Sin conexión a gasto real SAP';
            } else {
                $ok = true;
                $todo = $indice['por_cuenta'];
                if ($pedido) {
                    foreach ($pedido as $ck => $cuenta) {
                        $hit = $this->gastoDesdeIndice($todo, $ck);
                        $porCuenta[$ck] = $hit ?: [
                            'codigo' => $cuenta,
                            'nombre' => '',
                            'gasto' => array_fill(0, 12, 0.0),
                            'gasto_usd' => array_fill(0, 12, 0.0),
                        ];
                    }
                } else {
                    $porCuenta = $todo;
                }
            }
        }

        return [
            'ok' => $ok,
            'completo' => $ok && ($pedido === [] || count($porCuenta) >= count($pedido)),
            'year' => $year,
            'por_cuenta' => $porCuenta ?: (object) [],
            'mensaje' => $mensaje,
        ];
    }

    /**
     * Mapa CC => por_cuenta para los centros pedidos.
     * Con varios centros intenta el año completo de la empresa (una sola bajada).
     * Si ese libro no cabe en pocas páginas, pide cada centro en paralelo.
     *
     * @param  array<int, array{cc: string, clave: string, cuentas: array<int, string>}>  $lista
     * @return array<string, array<string, array<string, mixed>>>
     */
    /**
     * Lectura del análisis/control: solo la copia local, sin volver a SAP.
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    protected function mapaGastoCopia(string $empresa, int $year, array $centros = []): array
    {
        $snap = app(CcGastoRealSnapshot::class);
        if (! $snap->disponible()) {
            return [];
        }
        $mapa = $centros === []
            ? $snap->mapa($empresa, $year)
            : $snap->mapaDeCentros($empresa, $year, $centros);
        // Cualquier dato local de la empresa cuenta como copia (no resembrar SAP
        // solo porque el centro filtrado aún no tiene filas).
        if ($mapa !== [] || $snap->tieneCopia($empresa, $year)) {
            $this->gastoCopiaEmpresas[$empresa] = true;
        }

        return $mapa;
    }

    protected function mapaGastoEmpresa(string $empresa, int $year, array $lista): array
    {
        $ccs = [];
        foreach ($lista as $item) {
            $cc = trim((string) ($item['cc'] ?? ''));
            if ($cc !== '') {
                $ccs[$cc] = $cc;
            }
        }
        if (! $ccs) {
            return [];
        }

        $snap = app(CcGastoRealSnapshot::class);
        if ($snap->disponible() && $snap->cubreEmpresa($empresa, $year)) {
            $this->gastoCopiaEmpresas[$empresa] = true;

            return $snap->mapa($empresa, $year);
        }

        $pedidos = array_values($ccs);
        $faltan = $pedidos;
        $local = [];
        if ($snap->disponible()) {
            $presentes = $snap->centrosPresentes($empresa, $year);
            $faltan = [];
            foreach ($pedidos as $cc) {
                if (! $this->ccEstaEnLista($cc, $presentes)) {
                    $faltan[] = $cc;
                }
            }
            if (count($faltan) < count($pedidos)) {
                $local = $snap->mapa($empresa, $year);
            }
            if ($faltan === []) {
                $this->gastoCopiaEmpresas[$empresa] = true;

                return $local;
            }
        }

        $remoto = $this->mapaGastoRemoto($empresa, $year, $faltan, false);
        if ($snap->disponible() && ! empty($remoto['ok'])) {
            $snap->guardarSiCambio(
                $empresa,
                $year,
                $remoto['mapa'],
                $this->filasGastoSapDesdeRegistros($remoto['rows'], $empresa, $year),
                $remoto['completa'],
                'api',
                auth()->id()
            );
        }
        $this->gastoCopiaEmpresas[$empresa] = false;

        return $this->unirMapasGasto($local, $remoto['mapa']);
    }

    /**
     * @param  array<int, string>  $ccs
     * @return array{mapa: array<string, array<string, array<string, mixed>>>, completa: bool, rows: array<int, array<string, mixed>>, ok: bool}
     */
    protected function mapaGastoRemoto(string $empresa, int $year, array $ccs, bool $forzar = false): array
    {
        $ccs = array_values(array_unique(array_filter(array_map('trim', $ccs))));
        $vacio = ['mapa' => [], 'completa' => false, 'rows' => [], 'ok' => false];
        if (! $ccs) {
            return $vacio;
        }

        $cacheKey = 'cc.gasto-emp.v4.'.$empresa.'.'.$year;
        if (count($ccs) >= 6) {
            if (! $forzar) {
                $cached = Cache::get($cacheKey);
                if ($cached === 'wide') {
                    $porCentro = $this->mapaGastoPorCentros($empresa, $year, $ccs, false);

                    return ['mapa' => $porCentro['mapa'], 'completa' => false, 'rows' => $porCentro['rows'], 'ok' => $porCentro['ok']];
                }
                if (is_array($cached)) {
                    return ['mapa' => $cached, 'completa' => true, 'rows' => [], 'ok' => true];
                }
            }
            $api = app(AutinApiClient::class);
            $res = $api->gastoRealEmpresaSiCabe($empresa, $year, 12, 8);
            if (! empty($res['ok']) && empty($res['truncated'])) {
                $rows = is_array($res['rows'] ?? null) ? $res['rows'] : [];
                $mapa = $this->mapaPorCentroDesdeFilas($rows, $empresa, $year);
                Cache::put($cacheKey, $mapa, 1800);

                return ['mapa' => $mapa, 'completa' => true, 'rows' => $rows, 'ok' => true];
            }
                if (empty($res['ok'])) {
                return $vacio;
            }
            Cache::put($cacheKey, 'wide', 1800);
        }

        $porCentro = $this->mapaGastoPorCentros($empresa, $year, $ccs, $forzar);

        return ['mapa' => $porCentro['mapa'], 'completa' => false, 'rows' => $porCentro['rows'], 'ok' => $porCentro['ok']];
    }

    /**
     * Vuelve a bajar el gasto de la API y solo reescribe la copia local si cambió.
     *
     * @param  array<int, string>  $ccs
     */
    protected function refrescarGastoEmpresa(string $empresa, int $year, array $ccs, CcGastoRealSnapshot $snap): bool
    {
        if (! $snap->disponible()) {
            return false;
        }
        $remoto = $this->mapaGastoRemoto($empresa, $year, $ccs, true);
        if (empty($remoto['ok'])) {
            return false;
        }

        return $snap->guardarSiCambio(
            $empresa,
            $year,
            $remoto['mapa'],
            $this->filasGastoSapDesdeRegistros($remoto['rows'], $empresa, $year),
            $remoto['completa'],
            'api',
            auth()->id()
        );
    }

    /**
     * @param  array<int, string>  $lista
     */
    protected function ccEstaEnLista(string $cc, array $lista): bool
    {
        foreach ($lista as $item) {
            if ($this->mismoCentroSap($cc, (string) $item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, array<string, array<string, mixed>>>  $base
     * @param  array<string, array<string, array<string, mixed>>>  $extra
     * @return array<string, array<string, array<string, mixed>>>
     */
    protected function unirMapasGasto(array $base, array $extra): array
    {
        foreach ($extra as $cc => $por) {
            $base[$cc] = $por;
        }

        return $base;
    }

    /**
     * @param  array<int, string>  $ccs
     * @return array{mapa: array<string, array<string, array<string, mixed>>>, rows: array<int, array<string, mixed>>, ok: bool}
     */
    protected function mapaGastoPorCentros(string $empresa, int $year, array $ccs, bool $forzar = false): array
    {
        $out = [];
        $filas = [];
        $missing = [];
        foreach ($ccs as $cc) {
            $cc = trim((string) $cc);
            if ($cc === '') {
                continue;
            }
            $cached = $forzar ? null : Cache::get('cc.gasto-indice.v4.'.$empresa.'.'.$cc.'.'.$year);
            if (is_array($cached)) {
                $out[strtoupper($cc)] = $cached;
                if ($cc !== strtoupper($cc)) {
                    $out[$cc] = $cached;
                    }
                } else {
                $missing[] = $cc;
            }
        }
        if (! $missing) {
            return ['mapa' => $out, 'rows' => [], 'ok' => $out !== []];
        }

        $api = app(AutinApiClient::class);
        $res = $api->gastoRealPorCentros($empresa, $year, $missing, 40, 8);
        $failed = array_fill_keys($res['failed'] ?? [], true);
        $ok = false;
        foreach ($missing as $cc) {
            $rows = $res['por_cc'][$cc] ?? [];
            if (isset($failed[$cc])) {
                continue;
            }
                    $ok = true;
            foreach ($rows as $row) {
                        if (is_array($row)) {
                    $filas[] = $row;
                }
            }
            $por = $this->porCuentaDesdeFilas(is_array($rows) ? $rows : [], $empresa, $cc, $year);
            Cache::put('cc.gasto-indice.v4.'.$empresa.'.'.$cc.'.'.$year, $por, 1800);
            $out[strtoupper($cc)] = $por;
            $out[$cc] = $por;
        }

        return ['mapa' => $out, 'rows' => $filas, 'ok' => $ok || $out !== []];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, array<string, array<string, mixed>>>
     */
    protected function mapaPorCentroDesdeFilas(array $rows, string $empresa, int $year): array
    {
        $porCc = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $rowCc = strtoupper($this->campoFila($row, ['CC', 'PrcCode', 'OcrCode', 'ProfitCode', 'centro', 'Centro']));
            if ($rowCc === '') {
                continue;
            }
            if (! isset($porCc[$rowCc])) {
                $porCc[$rowCc] = [];
            }
            $this->acumularGastoRealFila($porCc[$rowCc], $row, $empresa, $year, [], $rowCc);
        }

        return $porCc;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, array<string, mixed>>
     */
    protected function porCuentaDesdeFilas(array $rows, string $empresa, string $cc, int $year): array
    {
        $por = [];
        foreach ($rows as $row) {
                    if (is_array($row)) {
                $this->acumularGastoRealFila($por, $row, $empresa, $year, [], $cc);
            }
        }

        return $this->aliasCuentasIndice($por);
    }

    /**
     * @param  array<string, array<string, mixed>>  $por
     * @return array<string, array<string, mixed>>
     */
    protected function aliasCuentasIndice(array $por): array
    {
        return $por;
    }

    /**
     * @param  array<string, array<string, array<string, mixed>>>  $porCc
     * @return array<string, array<string, mixed>>
     */
    protected function porCuentaDeIndiceEmpresa(array $porCc, string $cc): array
    {
        $cc = trim($cc);
        if ($cc !== '' && isset($porCc[$cc]) && is_array($porCc[$cc])) {
            return $porCc[$cc];
        }
        $up = strtoupper($cc);
        if ($up !== '' && isset($porCc[$up]) && is_array($porCc[$up])) {
            return $porCc[$up];
        }
        $alt = ltrim($up, '0');
        if ($alt !== '' && isset($porCc[$alt]) && is_array($porCc[$alt])) {
            return $porCc[$alt];
        }
        foreach ($porCc as $key => $por) {
            if (is_array($por) && $this->mismoCentroSap((string) $key, $cc)) {
                return $por;
            }
        }

        return [];
    }

    /**
     * @param  array<string, array<string, mixed>>  $todo
     * @param  array<int, string>  $cuentas
     * @return array<string, array<string, mixed>>
     */
    protected function recortarGastoPedido(array $todo, array $cuentas): array
    {
        $pedido = [];
        foreach ($cuentas as $cuenta) {
            $cuenta = trim((string) $cuenta);
            $ck = $this->codigoCuentaKey($cuenta);
            if ($ck !== '') {
                $pedido[$ck] = $cuenta;
            }
        }
        if (! $pedido) {
            return [];
        }
        $por = [];
        foreach ($pedido as $ck => $cuenta) {
            $hit = $this->gastoDesdeIndice($todo, $ck);
            if (! $hit) {
                continue;
            }
            $propia = trim((string) ($hit['codigo'] ?? ''));
            if ($propia !== '' && $propia !== trim((string) $cuenta) && $propia !== trim((string) $ck)) {
                continue;
            }
            $hit['codigo'] = $cuenta;
            $por[$ck] = $hit;
        }

        return $por;
    }

    /**
     * Una sola consulta a gasto-real, igual que en /Sistemas/AutinApi:
     * Empresa + centro + año + GroupMask 6. El resultado queda indexado por número de cuenta.
     *
     * @return array{ok: bool, por_cuenta: array<string, array<string, mixed>>, mensaje: string|null}
     */
    protected function indiceGastoPorCentro(string $empresa, string $cc, int $year, bool $permitirLegacy = true): array
    {
        $cacheKey = 'cc.gasto-indice.v4.' . $empresa . '.' . $cc . '.' . $year;
        $cached = $permitirLegacy ? Cache::get($cacheKey) : null;
        if (is_array($cached)) {
            return ['ok' => true, 'por_cuenta' => $cached, 'mensaje' => null];
        }

        $api = app(AutinApiClient::class);
        $res = $api->gastoRealTodasPaginas([
            'Empresa' => $empresa,
            'CC' => $cc,
            'year' => $year,
            'fecha_desde' => $year . '-01-01',
            'fecha_hasta' => $year . '-12-31',
            'GroupMask' => '6',
        ], 40, 6);

        if (empty($res['ok'])) {
            return [
                'ok' => false,
                'por_cuenta' => [],
                'mensaje' => $res['message'] ?? 'Sin conexión a gasto real SAP',
            ];
        }

        $por = [];
        foreach ($res['rows'] as $row) {
            if (is_array($row)) {
                $this->acumularGastoRealFila($por, $row, $empresa, $year, [], $cc);
            }
        }
        Cache::put($cacheKey, $por, 1800);

        return ['ok' => true, 'por_cuenta' => $por, 'mensaje' => null];
    }

    /**
     * @param  array<string, array<string, mixed>>  $por
     * @return array<string, mixed>|null
     */
    protected function gastoDesdeIndice(array $por, string $ck): ?array
    {
        $ck = trim($ck);
        if ($ck !== '' && isset($por[$ck]) && is_array($por[$ck])) {
            return $por[$ck];
        }

        return null;
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
     * @param  array<int, string>  $cuentas
     */
    protected function completarCuentasGastoDesdeAsignaciones(string $empresa, string $cc, array &$cuentas): void
    {
        if (! Schema::hasTable('tbl_cc_asignaciones') || ! Schema::hasTable('tbl_cc_asignacion_cuentas')) {
            return;
        }
        $q = CcAsignacion::query()->with('cuentas')->whereRaw('UPPER(empresa) = ?', [$empresa]);
        if ($cc !== '') {
            $q->where('centro_codigo', $cc);
        }
        foreach ($q->get() as $asig) {
            foreach ($asig->cuentas as $cta) {
                $codigo = trim((string) $cta->cuenta_codigo);
                if ($codigo !== '') {
                    $cuentas[] = $codigo;
                }
            }
        }
        $cuentas = array_values(array_unique($cuentas));
    }

    /**
     * Suma importes de la misma cuenta en el mismo mes.
     * Cuenta y CC de la API son coincidencias parciales: aquí se exige el código y el centro exactos.
     *
     * @param  array<string, array{codigo: string, nombre: string, gasto: array<int, float>}>  $porCuenta
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $cuentasPedido
     */
    protected function acumularGastoRealFila(array &$porCuenta, array $row, string $empresa, int $year, array $cuentasPedido = [], string $cc = ''): void
    {
        $rowEmp = $this->campoFila($row, ['EMPRESA', 'Empresa', 'empresa', 'DB']);
        if ($rowEmp !== '' && ! $this->mismaEmpresaSap($rowEmp, $empresa)) {
            return;
        }

        $rowCc = $this->campoFila($row, ['CC', 'PrcCode', 'OcrCode', 'ProfitCode', 'centro', 'Centro']);
        if ($cc !== '' && ! $this->mismoCentroSap($rowCc, $cc)) {
            return;
        }

        $codigo = trim($this->campoFila($row, ['Cuenta', 'FormatCode', 'AcctCode', 'CUENTA', 'codigo']));
        $key = $codigo;
        if ($key === '' || ($cuentasPedido && ! $this->cuentaGastoPedida($key, $cuentasPedido))) {
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

        $nombre = $this->campoFila($row, ['DescCuenta', 'AcctName', 'NOMBRE', 'AccountName', 'nombre']);
        $importe = $this->importeGastoFila($row);
        $importeUsd = $this->importeGastoFilaUsd($row);
        if (! isset($porCuenta[$key])) {
            $porCuenta[$key] = [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'gasto' => array_fill(0, 12, 0.0),
                'gasto_usd' => array_fill(0, 12, 0.0),
            ];
        } elseif ($nombre !== '' && ($porCuenta[$key]['nombre'] ?? '') === '') {
            $porCuenta[$key]['nombre'] = $nombre;
        }
        if (! isset($porCuenta[$key]['gasto_usd']) || ! is_array($porCuenta[$key]['gasto_usd'])) {
            $porCuenta[$key]['gasto_usd'] = array_fill(0, 12, 0.0);
        }
        $porCuenta[$key]['gasto'][$mes] = round($porCuenta[$key]['gasto'][$mes] + $importe, 2);
        $porCuenta[$key]['gasto_usd'][$mes] = round($porCuenta[$key]['gasto_usd'][$mes] + $importeUsd, 2);
    }

    /**
     * @param  array<int, string>  $cuentas
     */
    protected function cuentaGastoPedida(string $key, array $cuentas): bool
    {
        $key = trim($key);
        if ($key === '') {
            return false;
        }
        foreach ($cuentas as $req) {
            if (trim((string) $req) === $key) {
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

    /**
     * Dólares de /gasto-real (ImporteDlls). No se convierte con el tipo de cambio.
     *
     * @param  array<string, mixed>  $row
     */
    protected function importeGastoFilaUsd(array $row): float
    {
        foreach (['ImporteDlls', 'ImporteDLLS', 'ImporteUSD', 'LineTotalUSD'] as $key) {
            if (isset($row[$key]) && $row[$key] !== '' && $row[$key] !== null) {
                return round((float) $row[$key], 2);
            }
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

        return $a !== '' && $a === $b;
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

    protected function mismoCentroSap(string $a, string $b): bool
    {
        $a = strtoupper(trim($a));
        $b = strtoupper(trim($b));

        return $a !== '' && $a === $b;
    }

    protected function codigoCuentaKey(string $codigo): string
    {
        return trim($codigo);
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

        $localCat = app(CcCatalogoLocal::class);
        $centrosBoot = [];
        $cuentasBoot = [];
        $empresasSapBoot = [];
        $catalogoSyncedAt = null;
        $catalogoLocalOk = false;
        if ($localCat->disponible() && $localCat->tieneDatos()) {
            $cat = $localCat->leer();
            $centrosBoot = $cat['centros'];
            $cuentasBoot = $cat['cuentas'];
            $empresasSapBoot = $cat['empresas'];
            $catalogoSyncedAt = $cat['synced_at'];
            $catalogoLocalOk = true;
        }

        return [
            'usuarioActual' => auth()->user()->name ?? '',
            'usuarioActualId' => auth()->id(),
            'anioGasto' => 2026,
            'anioPresupuesto' => 2027,
            'sapBase' => url('/Sistemas/AutinApi'),
            'catalogoUrl' => route('centros.catalogo'),
            'catalogoSyncUrl' => route('centros.api.catalogo_sync'),
            'gastoUrl' => route('centros.api.gasto_real'),
            'gastoBatchUrl' => route('centros.api.gasto_real_analisis'),
            'visorResumenUrl' => route('centros.api.visor_resumen'),
            'sapOk' => false,
            'sapMensaje' => null,
            'catalogoLocal' => $catalogoLocalOk,
            'catalogoSyncedAt' => $catalogoSyncedAt,
            'empresasSap' => $empresasSapBoot,
            'centros' => $centrosBoot,
            'cuentas' => $cuentasBoot,
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
     * Persiste respuesta SAP en catálogo local, agrupando por empresa.
     * No toca presupuestos ni asignaciones.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function persistirCatalogoSapEnLocal(array $payload, ?string $empresaFiltro = null): void
    {
        $local = app(CcCatalogoLocal::class);
        if (! $local->disponible()) {
            return;
        }
        $centros = is_array($payload['centros'] ?? null) ? $payload['centros'] : [];
        $cuentas = is_array($payload['cuentas'] ?? null) ? $payload['cuentas'] : [];
        $byEmp = [];
        foreach ($centros as $c) {
            $emp = strtoupper(trim((string) ($c['empresa'] ?? $empresaFiltro ?? '')));
            if ($emp === '') {
                continue;
            }
            if (! isset($byEmp[$emp])) {
                $byEmp[$emp] = ['centros' => [], 'cuentas' => []];
            }
            $byEmp[$emp]['centros'][] = $c;
        }
        foreach ($cuentas as $c) {
            $emp = strtoupper(trim((string) ($c['empresa'] ?? $empresaFiltro ?? '')));
            if ($emp === '') {
                continue;
            }
            if (! isset($byEmp[$emp])) {
                $byEmp[$emp] = ['centros' => [], 'cuentas' => []];
            }
            $byEmp[$emp]['cuentas'][] = $c;
        }
        if ($empresaFiltro && ! isset($byEmp[strtoupper($empresaFiltro)])) {
            $byEmp[strtoupper($empresaFiltro)] = ['centros' => $centros, 'cuentas' => $cuentas];
        }
        $uid = auth()->id();
        foreach ($byEmp as $emp => $pack) {
            // Si solo trajimos centros o solo cuentas, conserva el otro lado local.
            $prev = $local->leer($emp);
            $centrosSave = $pack['centros'] ?: $prev['centros'];
            $cuentasSave = $pack['cuentas'] ?: $prev['cuentas'];
            $local->reemplazarEmpresa($emp, $centrosSave, $cuentasSave, $uid);
        }
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
            $perCentro = min(500, max(80, $perPage));
            $filtrosCentro = ['per_page' => $perCentro];
            $filtrosCuenta = ['per_page' => min($perPage, 120), 'page' => 1];
            if ($empresa) {
                $filtrosCentro['Empresa'] = strtoupper($empresa);
                $filtrosCuenta['Empresa'] = strtoupper($empresa);
            }

            $centros = [];
            for ($page = 1; $page <= 15; $page++) {
                $resCentros = $api->centrosCostoGlobal(array_merge($filtrosCentro, ['page' => $page]));
                if (empty($resCentros['ok'])) {
                    if ($page === 1) {
                        $sapMensaje = $resCentros['message'] ?? 'Sin conexión a catálogo SAP';
                    }
                    break;
                }
                $sapOk = true;
                $body = is_array($resCentros['body'] ?? null) ? $resCentros['body'] : [];
                $batch = $this->normalizarCentros($body['data'] ?? []);
                if (! $batch) {
                    break;
                }
                $centros = array_merge($centros, $batch);
                $pag = $this->paginacionDe($body);
                if ($pag['last_page'] > 0 && $page >= $pag['last_page']) {
                    break;
                }
                if ($pag['last_page'] < 1 && count($batch) < $perCentro) {
                    break;
                }
            }

            $resCuentas = $api->cuentasGlobal($filtrosCuenta);

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
        $ok = false;
        $mensaje = null;
        $empresaApi = strtoupper(trim($empresa));

        $fetch = function (string $mask) use ($api, $empresaApi, $todas, &$ok, &$mensaje): array {
            $cuentas = [];
        $maxPages = $todas ? 40 : 1;
        $perPage = 200;
        for ($page = 1; $page <= $maxPages; $page++) {
                // Endpoint global: trae FormatCode (número de cuenta). El /{db}/cuentas solo trae CUENTA=_SYS… (consecutivo).
                $filtros = [
                    'per_page' => $perPage,
                    'page' => $page,
                    'Empresa' => $empresaApi,
                ];
                if ($mask !== '') {
                    $filtros['GroupMask'] = $mask;
                }
                $res = $api->cuentasGlobal($filtros);
            if (empty($res['ok'])) {
                    if ($page === 1 && $mensaje === null) {
                    $mensaje = $res['message'] ?? 'Sin conexión a catálogo SAP';
                }
                break;
            }
            $ok = true;
            $body = is_array($res['body'] ?? null) ? $res['body'] : [];
                $raw = is_array($body['data'] ?? null) ? $body['data'] : [];
                $batch = $this->normalizarCuentas($raw);
                if ($batch) {
            $cuentas = array_merge($cuentas, $batch);
                }
            $pag = $this->paginacionDe($body);
            $lastPage = $pag['last_page'];
            if ($lastPage > 0 && $page >= $lastPage) {
                break;
            }
                if ($lastPage < 1 && (count($raw) < $perPage || ! $batch)) {
                break;
                }
            }

            return $cuentas;
        };

        $cuentas = $fetch($groupMask);
        if ($todas && $groupMask === '' && $ok && ! $cuentas) {
            @set_time_limit(180);
            $seen = [];
            foreach (['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'] as $mask) {
                foreach ($fetch($mask) as $c) {
                    $code = trim((string) ($c['codigo'] ?? ''));
                    if ($code === '' || isset($seen[$code])) {
                        continue;
                    }
                    $seen[$code] = true;
                    if (trim((string) ($c['grupo_id'] ?? '')) === '') {
                        $c['grupo_id'] = $mask;
                    }
                    $cuentas[] = $c;
                }
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
     * Número que va en la plantilla: FormatCode (cuenta contable).
     * El consecutivo viejo (_SYS… → 667) se cambia por ese número.
     */
    protected function codigoCuentaParaPlantilla(string $empresa, string $codigo, string $nombre): string
    {
        $codigo = trim($codigo);
        if ($codigo === '' || ! $this->esCodigoCuentaViejo($codigo)) {
            return $codigo;
        }

        $format = $this->formatCodeDeCuentaVieja($empresa, $codigo, $nombre);

        return $format !== '' ? $format : $this->codigoCuentaVisible($codigo);
    }

    /**
     * FormatCode del catálogo local para consecutivos viejos (&lt; 8 dígitos o _SYS).
     * Si el nombre coincide con varias cuentas, se deja el código original.
     */
    protected function codigoCuentaCatalogo(string $empresa, string $codigo, string $nombre): string
    {
        $codigo = trim($codigo);
        if ($codigo === '') {
            return '';
        }
        if (! $this->esCodigoCuentaViejo($codigo)) {
            return $codigo;
        }
        $fmt = $this->formatCodeDesdeCatalogoLocal($empresa, $nombre);

        return $fmt !== '' ? $fmt : $codigo;
    }

    protected function normalizarNombreCuenta(string $nombre): string
    {
        $s = trim($nombre);
        if ($s === '') {
            return '';
        }
        if (class_exists(\Normalizer::class)) {
            $n = \Normalizer::normalize($s, \Normalizer::FORM_D);
            if (is_string($n) && $n !== '') {
                $s = $n;
            }
        }
        $s = preg_replace('/\p{Mn}+/u', '', $s) ?? $s;
        $s = mb_strtoupper($s);
        $s = str_replace('Ñ', 'N', $s);
        $s = preg_replace('/[^A-Z0-9]+/', ' ', $s) ?? $s;

        return trim(preg_replace('/\s+/', ' ', $s) ?? $s);
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function catalogoCuentasPorNombre(string $empresa): array
    {
        $empresa = strtoupper(trim($empresa));
        if ($empresa === '') {
            return [];
        }
        if (isset($this->catalogoCuentasPorNombreCache[$empresa])) {
            return $this->catalogoCuentasPorNombreCache[$empresa];
        }

        $map = [];
        if (Schema::hasTable('tbl_cc_catalogo_cuentas')) {
            $empresas = [$empresa];
            if ($empresa === 'AUSTIN') {
                $empresas[] = 'ABSA';
            } elseif ($empresa === 'ABSA') {
                $empresas[] = 'AUSTIN';
            }
            $rows = DB::table('tbl_cc_catalogo_cuentas')
                ->where(function ($q) use ($empresas) {
                    foreach ($empresas as $e) {
                        $q->orWhereRaw('UPPER(empresa) = ?', [$e]);
                    }
                })
                ->get(['codigo', 'nombre']);
            foreach ($rows as $row) {
                $norm = $this->normalizarNombreCuenta((string) ($row->nombre ?? ''));
                $codigo = trim((string) ($row->codigo ?? ''));
                if ($norm === '' || $codigo === '') {
                    continue;
                }
                if (! isset($map[$norm])) {
                    $map[$norm] = [];
                }
                if (! in_array($codigo, $map[$norm], true)) {
                    $map[$norm][] = $codigo;
                }
            }
        }

        return $this->catalogoCuentasPorNombreCache[$empresa] = $map;
    }

    protected function formatCodeDesdeCatalogoLocal(string $empresa, string $nombre): string
    {
        $norm = $this->normalizarNombreCuenta($nombre);
        if ($norm === '') {
            return '';
        }
        $hits = $this->catalogoCuentasPorNombre($empresa)[$norm] ?? [];

        return $this->elegirCodigoSapUnico($hits);
    }

    /**
     * Una sola coincidencia, o una sola cuenta de gasto (6000…) si el nombre se repite en balance.
     *
     * @param  array<int, string>  $hits
     */
    protected function elegirCodigoSapUnico(array $hits): string
    {
        $hits = array_values(array_unique(array_filter(array_map('trim', $hits))));
        if (count($hits) === 1) {
            return $hits[0];
        }
        $gastos = array_values(array_filter($hits, function ($codigo) {
            return strpos($codigo, '6000') === 0;
        }));

        return count($gastos) === 1 ? $gastos[0] : '';
    }

    protected function presupuestoPorAlias(string $ciclo, string $empresa, string $centro, string $cuenta, string $nombre = ''): ?CcPresupuesto
    {
        $aliases = array_values(array_unique(array_filter([
            $cuenta,
            ltrim($cuenta, '0'),
            $this->codigoCuentaVisible($cuenta),
            $this->codigoCuentaPlantilla($cuenta),
            $this->codigoCuentaCatalogo($empresa, $cuenta, $nombre),
        ])));
        if ($aliases === []) {
            return null;
        }

        return CcPresupuesto::query()
            ->whereRaw('UPPER(ciclo_codigo) = ?', [strtoupper($ciclo)])
            ->whereRaw('UPPER(empresa) = ?', [strtoupper($empresa)])
            ->where('centro_codigo', $centro)
            ->whereIn('cuenta_codigo', $aliases)
            ->first();
    }

    protected function esCodigoCuentaViejo(string $codigo): bool
    {
        $codigo = trim($codigo);
        if ($codigo === '') {
            return false;
        }
        if (preg_match('/SYS/i', $codigo)) {
            return true;
        }
        $digits = preg_replace('/\D+/', '', $codigo) ?? '';

        return $digits !== '' && $digits === $codigo && strlen($digits) < 8;
    }

    protected function formatCodeDeCuentaVieja(string $empresa, string $codigo, string $nombre): string
    {
        $empresa = strtoupper(trim($empresa));
        $nombre = trim($nombre);
        $cacheKey = $empresa.'|'.trim($codigo).'|'.mb_strtoupper($nombre);
        if (array_key_exists($cacheKey, $this->formatCodePlantillaCache)) {
            return $this->formatCodePlantillaCache[$cacheKey];
        }
        $guardado = Cache::get('cc_fmt_v1_'.$cacheKey);
        if (is_string($guardado) && $guardado !== '') {
            return $this->formatCodePlantillaCache[$cacheKey] = $guardado;
        }

        $format = '';
        if ($empresa !== '' && $nombre !== '') {
            $format = $this->formatCodeDesdeCatalogoLocal($empresa, $nombre);
            if ($format === '') {
                try {
                    $api = app(AutinApiClient::class);
                    $codes = $this->formatCodesPorNombre($api, $empresa, $nombre);
                    if (count($codes) === 1) {
                        $format = $codes[0];
                    } elseif (count($codes) > 1) {
                        $format = $this->emparejarCodigoViejo($api, $empresa, $codigo, $nombre, $codes);
                    }
                } catch (Throwable $e) {
                    $format = '';
                }
            }
        }

        if ($format !== '') {
            Cache::put('cc_fmt_v1_'.$cacheKey, $format, 3600);
        }

        return $this->formatCodePlantillaCache[$cacheKey] = $format;
    }

    /**
     * @return array<int, string>
     */
    protected function formatCodesPorNombre(AutinApiClient $api, string $empresa, string $nombre): array
    {
        $res = $api->cuentasGlobal([
            'Empresa' => $empresa,
            'AcctName' => $nombre,
            'per_page' => 40,
            'page' => 1,
        ]);
        $hits = [];
        $needle = mb_strtoupper($nombre);
        foreach (($res['body']['data'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            if (mb_strtoupper(trim((string) ($row['Empresa'] ?? ''))) !== $empresa) {
                continue;
            }
            if (mb_strtoupper(trim((string) ($row['AcctName'] ?? ''))) !== $needle) {
                continue;
            }
            $code = trim((string) ($row['FormatCode'] ?? ''));
            if ($code === '') {
                continue;
            }
            $hits[$code] = trim((string) ($row['GroupMask'] ?? ''));
        }
        $gastos = [];
        foreach ($hits as $code => $mask) {
            if ($mask === '6') {
                $gastos[] = $code;
            }
        }
        $pool = count($gastos) >= 1 ? $gastos : array_keys($hits);
        sort($pool, SORT_STRING);

        return array_values($pool);
    }

    /**
     * @param  array<int, string>  $formatCodes
     */
    protected function emparejarCodigoViejo(AutinApiClient $api, string $empresa, string $codigo, string $nombre, array $formatCodes): string
    {
        $digits = ltrim(preg_replace('/\D+/', '', $codigo) ?? '', '0');
        if ($digits === '') {
            return '';
        }
        $db = strtolower($empresa);
        if (! in_array($db, $api->allowedDatabases(), true)) {
            return '';
        }

        $res = $api->index('cuentas', [
            'NOMBRE' => $nombre,
            'GroupMask' => 6,
            'per_page' => 50,
            'page' => 1,
        ], $db);
        $needle = mb_strtoupper($nombre);
        $orden = [];
        foreach (($res['body']['data'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            if (mb_strtoupper(trim((string) ($row['NOMBRE'] ?? ''))) !== $needle) {
                continue;
            }
            $num = ltrim(preg_replace('/\D+/', '', (string) ($row['CUENTA'] ?? '')) ?? '', '0');
            if ($num !== '' && ! in_array($num, $orden, true)) {
                $orden[] = $num;
            }
        }
        usort($orden, function ($a, $b) {
            return ((int) $a) <=> ((int) $b);
        });
        $idx = array_search($digits, $orden, true);
        if ($idx === false || count($orden) !== count($formatCodes) || ! isset($formatCodes[$idx])) {
            return '';
        }

        return $formatCodes[$idx];
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
    protected function clavesCuentaCaptura(string $empresa, string $centro, string $cuenta, string $nombre = ''): array
    {
        $cuenta = trim($cuenta);
        $keys = [
            $this->capturaBudgetKey($empresa, $centro, $cuenta),
            $this->capturaBudgetKey($empresa, $centro, ltrim($cuenta, '0')),
            $this->capturaBudgetKey($empresa, $centro, $this->codigoCuentaVisible($cuenta)),
            $this->capturaBudgetKey($empresa, $centro, $this->codigoCuentaKey($cuenta)),
            $this->capturaBudgetKey($empresa, $centro, $this->codigoCuentaPlantilla($cuenta)),
        ];
        $canon = $this->codigoCuentaCatalogo($empresa, $cuenta, $nombre);
        if ($canon !== '' && $canon !== $cuenta) {
            $keys[] = $this->capturaBudgetKey($empresa, $centro, $canon);
        }

        return array_values(array_unique(array_filter($keys)));
    }

    /**
     * @param  array<int, array<string, mixed>>  $cuentas
     */
    protected function syncGrupoCuentas(CcGrupoCuenta $grupo, array $cuentas): int
    {
        $anteriores = $grupo->cuentas->pluck('cuenta_codigo')->map(function ($c) {
            return trim((string) $c);
        })->filter()->values()->all();
        if ($anteriores === []) {
            $anteriores = CcGrupoCuentaItem::query()
                ->where('grupo_id', $grupo->id)
                ->pluck('cuenta_codigo')
                ->map(function ($c) {
                    return trim((string) $c);
                })
                ->filter()
                ->values()
                ->all();
        }

        CcGrupoCuentaItem::query()->where('grupo_id', $grupo->id)->delete();
        $seen = [];
        $nuevas = [];
        foreach ($cuentas as $cta) {
            $codigo = trim((string) ($cta['codigo'] ?? $cta['cuenta_codigo'] ?? ''));
            if ($codigo === '' || isset($seen[$codigo])) {
                continue;
            }
            $seen[$codigo] = true;
            $item = [
                'codigo' => $codigo,
                'nombre' => $cta['nombre'] ?? $cta['cuenta_nombre'] ?? null,
            ];
            $nuevas[] = $item;
            CcGrupoCuentaItem::query()->create([
                'grupo_id' => $grupo->id,
                'cuenta_codigo' => $codigo,
                'cuenta_nombre' => $item['nombre'],
            ]);
        }

        $agregadas = [];
        foreach ($nuevas as $cta) {
            if (! $this->cuentaCodigoEstaEnLista($cta['codigo'], $anteriores)) {
                $agregadas[] = $cta;
            }
        }

        if ($agregadas === [] || $anteriores === []) {
            return 0;
        }

        return $this->propagarCuentasGrupoAAsignaciones($grupo, $anteriores, $agregadas);
    }

    /**
     * @param  array<int, string>  $codigos
     */
    protected function cuentaCodigoEstaEnLista(string $codigo, array $codigos): bool
    {
        $keys = $this->clavesCodigoCuenta($codigo);
        foreach ($codigos as $otro) {
            foreach ($this->clavesCodigoCuenta((string) $otro) as $k => $_on) {
                if (isset($keys[$k])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array<string, true>
     */
    protected function clavesCodigoCuenta(string $codigo): array
    {
        $codigo = trim($codigo);
        $keys = [
            $codigo,
            ltrim($codigo, '0'),
            $this->codigoCuentaKey($codigo),
            $this->codigoCuentaVisible($codigo),
            $this->codigoCuentaPlantilla($codigo),
        ];
        $out = [];
        foreach ($keys as $k) {
            $k = trim((string) $k);
            if ($k !== '') {
                $out[$k] = true;
            }
        }

        return $out;
    }

    /**
     * Si un usuario ya tenía todas las cuentas previas del grupo, se le agregan las nuevas.
     *
     * @param  array<int, string>  $cuentasPrevias
     * @param  array<int, array{codigo: string, nombre: mixed}>  $cuentasAgregadas
     */
    protected function propagarCuentasGrupoAAsignaciones(CcGrupoCuenta $grupo, array $cuentasPrevias, array $cuentasAgregadas): int
    {
        if (! Schema::hasTable('tbl_cc_asignaciones') || ! Schema::hasTable('tbl_cc_asignacion_cuentas')) {
            return 0;
        }

        $asigs = CcAsignacion::query()
            ->with('cuentas')
            ->whereRaw('LOWER(empresa) = ?', [strtolower((string) $grupo->empresa)])
            ->get();

        $actualizadas = 0;
        $usuarios = [];
        foreach ($asigs as $asig) {
            $codigosAsig = $asig->cuentas->pluck('cuenta_codigo')->map(function ($c) {
                return trim((string) $c);
            })->filter()->values()->all();
            if ($codigosAsig === []) {
                continue;
            }

            $tieneGrupo = true;
            foreach ($cuentasPrevias as $codigo) {
                if (! $this->cuentaCodigoEstaEnLista((string) $codigo, $codigosAsig)) {
                    $tieneGrupo = false;
                    break;
                }
            }
            if (! $tieneGrupo) {
                continue;
            }

            $agrego = false;
            foreach ($cuentasAgregadas as $cta) {
                $codigo = trim((string) ($cta['codigo'] ?? ''));
                if ($codigo === '' || $this->cuentaCodigoEstaEnLista($codigo, $codigosAsig)) {
                    continue;
                }
                CcAsignacionCuenta::query()->create([
                    'asignacion_id' => $asig->id,
                    'cuenta_codigo' => $codigo,
                    'cuenta_nombre' => $cta['nombre'] ?? null,
                    'agrupacion' => null,
                ]);
                $codigosAsig[] = $codigo;
                $agrego = true;
            }
            if ($agrego) {
                $actualizadas++;
                $usuarios[(int) $asig->user_id] = true;
            }
        }

        return $actualizadas > 0 ? count($usuarios) : 0;
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
            $nombre = (string) ($row['PrcName'] ?? $row['NOMBRE'] ?? $row['nombre'] ?? $row['DescripcionCC'] ?? '');
            if ($codigo === '' && $nombre === '') {
                continue;
            }
            $out[] = [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'empresa' => (string) ($row['Empresa'] ?? $row['empresa'] ?? $row['DB'] ?? ''),
                'activo' => ! in_array(strtoupper((string) ($row['ESTATUS'] ?? $row['Active'] ?? 'Y')), ['N', '0', 'I', 'INACTIVE'], true),
                'departamento' => $this->departamentoDeCentroSap($row),
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function departamentoDeCentroSap(array $row): string
    {
        foreach ([
            'DEPTO', 'Depto', 'depto', 'departamento', 'Departamento',
            'DimName', 'DimensionName', 'DimDesc',
            'GroupName', 'GrpName', 'PrcGroup', 'Grupo',
            'U_DEPTO', 'U_Depto', 'U_Departamento', 'U_DEPARTAMENTO',
        ] as $key) {
            if (! array_key_exists($key, $row) || $row[$key] === null) {
                continue;
            }
            $val = trim((string) $row[$key]);
            if ($val === '' || preg_match('/^\d+$/', $val)) {
                continue;
            }

            return $val;
        }

        return '';
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
            // Preferir FormatCode (número de cuenta contable). CUENTA/_SYS… es el consecutivo interno SAP.
            $format = trim((string) ($row['FormatCode'] ?? ''));
            $cuentaSys = trim((string) ($row['CUENTA'] ?? $row['Cuenta'] ?? $row['AcctCode'] ?? $row['codigo'] ?? ''));
            if ($format !== '') {
                $codigo = $format;
            } elseif ($cuentaSys !== '' && stripos($cuentaSys, 'SYS') === false) {
                $codigo = $cuentaSys;
            } elseif (array_key_exists('FormatCode', $row) && $format === '') {
                // Endpoint global sin FormatCode → encabezado / sin número de cuenta; omitir.
                continue;
            } else {
                $codigo = $this->codigoCuentaVisible($cuentaSys);
            }
            $nombre = (string) ($row['AcctName'] ?? $row['NOMBRE'] ?? $row['nombre'] ?? '');
            if ($codigo === '' && $nombre === '') {
                continue;
            }
            $out[] = [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'empresa' => (string) ($row['Empresa'] ?? $row['EMPRESA'] ?? $row['empresa'] ?? ''),
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

    /**
     * @param  array<int, string>  $claves
     */
    protected function clavesSonCaptura(array $claves): bool
    {
        foreach ($claves as $clave) {
            if ((string) $clave === 'capturar') {
                return true;
            }
        }

        return false;
    }

    /**
     * Revisar un centro, sin cuentas y sin ligarlo a una persona: cubre a quien ya lo tiene.
     *
     * @param  array<int, string>  $permisos
     * @param  array<int, mixed>  $cuentas
     */
    protected function esRevisionDeCentroCompleto(string $centro, array $permisos, array $cuentas): bool
    {
        $centro = strtoupper(trim($centro));
        if ($centro === '' || $centro === 'SIN_CC' || $centro === 'EMPRESA' || $cuentas !== []) {
            return false;
        }
        $claves = array_map('strval', $permisos);

        return in_array('revisar', $claves, true) && ! $this->clavesSonCaptura($claves);
    }

    protected function esRevisionCentroCompletoGuardada(CcAsignacion $asig): bool
    {
        if ($this->asigHasRolColumns() && $asig->parent_id) {
            return false;
        }
        $cuentas = CcAsignacionCuenta::query()->where('asignacion_id', $asig->id)->count();
        $claves = CcAsignacionPermiso::query()
            ->where('asignacion_id', $asig->id)
            ->with('tipo')
            ->get()
            ->map(function ($p) {
                return (string) ($p->tipo->clave ?? '');
            })->all();

        return $this->esRevisionDeCentroCompleto((string) $asig->centro_codigo, $claves, $cuentas > 0 ? ['x'] : []);
    }

    /**
     * Quita la revisión de ese centro, también si el código está guardado con o sin ceros.
     */
    protected function borrarRevisionesDelMismoCentro(CcAsignacion $asig): void
    {
        $hermanas = CcAsignacion::query()
            ->where('ciclo_codigo', $asig->ciclo_codigo)
            ->where('empresa', $asig->empresa)
            ->where('user_id', $asig->user_id)
            ->get()
            ->filter(function (CcAsignacion $row) use ($asig) {
                return $this->mismoCentroSap((string) $row->centro_codigo, (string) $asig->centro_codigo)
                    && $this->esRevisionCentroCompletoGuardada($row);
            });
        foreach ($hermanas as $row) {
            $this->borrarAsignacion($row);
        }
    }

    protected function idPermisoCapturar(): ?int
    {
        if (! Schema::hasTable('tbl_cc_tipos_permiso')) {
            return null;
        }
        $id = CcTipoPermiso::query()->where('clave', 'capturar')->value('id');

        return $id ? (int) $id : null;
    }

    /**
     * Quien captura el mismo centro es una asignación propia, no un revisor del primero.
     */
    protected function promoverCapturasIndependientes(string $ciclo): void
    {
        if (! $this->asigHasRolColumns()) {
            return;
        }
        $tipoId = $this->idPermisoCapturar();
        if (! $tipoId || ! Schema::hasTable('tbl_cc_asignacion_permisos')) {
            return;
        }
        $ids = CcAsignacionPermiso::query()->where('permiso_id', $tipoId)->pluck('asignacion_id');
        if ($ids->isEmpty()) {
            return;
        }
        CcAsignacion::query()
            ->where('ciclo_codigo', $ciclo)
            ->whereIn('id', $ids)
            ->where(function ($q) {
                $q->where('es_principal', false)->orWhereNotNull('parent_id');
            })
            ->update([
                'es_principal' => true,
                'parent_id' => null,
            ]);
    }

    protected function queryColaboradoresDe(CcAsignacion $principal)
    {
        $q = CcAsignacion::query()
            ->where('ciclo_codigo', $principal->ciclo_codigo)
            ->where('empresa', $principal->empresa)
            ->where('centro_codigo', $principal->centro_codigo)
            ->where('id', '!=', $principal->id);
        if (! $this->asigHasRolColumns()) {
            return $q;
        }
        $q->where('es_principal', false)
            ->where('parent_id', $principal->id);
        $tipoId = $this->idPermisoCapturar();
        if ($tipoId) {
            $q->whereDoesntHave('permisos', function ($p) use ($tipoId) {
                $p->where('permiso_id', $tipoId);
            });
        }

        return $q;
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
        $empresa = strtoupper(trim((string) $asig->empresa));

        return $asig->cuentas()->get()->map(function ($c) use ($empresa) {
            $orig = trim((string) $c->cuenta_codigo);

            return [
                'codigo' => $this->codigoCuentaCatalogo($empresa, $orig, (string) $c->cuenta_nombre) ?: $orig,
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
        foreach ($this->queryColaboradoresDe($asig)->get() as $extra) {
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

        $extras = $this->queryColaboradoresDe($principal)
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
    protected function asignacionPayload(CcAsignacion $a, bool $resumen = false): array
    {
        $permisos = $a->permisos->map(function ($p) {
            return $p->tipo->clave ?? null;
        })->filter(function ($c) {
            return $c && $c !== 'importar';
        })->values()->all();

        $esPrincipal = $this->asigHasRolColumns() ? (bool) $a->es_principal : true;

        $cuentas = [];
        if (! $resumen && $a->relationLoaded('cuentas')) {
            $empresa = strtoupper(trim((string) $a->empresa));
            $cuentas = $a->cuentas->map(function ($c) use ($empresa) {
                $orig = trim((string) $c->cuenta_codigo);

                return [
                    'codigo' => $this->codigoCuentaCatalogo($empresa, $orig, (string) $c->cuenta_nombre) ?: $orig,
                    'nombre' => $c->cuenta_nombre,
                    'agrupacion' => $c->agrupacion,
                ];
            })->values()->all();
        }

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
            'cuentas' => $cuentas,
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
