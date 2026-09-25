<?php

namespace App\Http\Controllers;

use App\Exports\PvCapturaPlantillaExport;
use App\Exports\PvPreciosPlantillaExport;
use App\Models\PvAsignacion;
use App\Models\PvAsignacionProducto;
use App\Models\PvAsignacionPermiso;
use App\Models\PvCapturaCentro;
use App\Models\PvCiclo;
use App\Models\PvPresupuesto;
use App\Models\PvProductoCosto;
use App\Models\PvProductoCostoHistorial;
use App\Models\PvVentaRealSnapshot;
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

    public function costos()
    {
        return $this->page('costos', 'ProyeccionesVentas.costos', [
            'costosUrl' => route('pv.api.costos'),
            'costosPlantillaUrl' => route('pv.api.costos.plantilla'),
            'costosImportExcelUrl' => route('pv.api.costos.import_excel'),
            'costosImportListaUrl' => route('pv.api.costos.import_lista'),
            'costosHistorialUrl' => route('pv.api.costos.historial'),
            'puedeEditarCostos' => true,
        ]);
    }

    public function listCostos(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tbl_pv_productos_costo')) {
            return response()->json(['message' => 'Falta ejecutar la migración de costos de productos.'], 422);
        }

        $empresa = strtoupper(trim((string) $request->get('empresa', '')));
        $cliente = trim((string) $request->get('cliente', ''));
        $itemcode = trim((string) $request->get('itemcode', $request->get('item_code', '')));
        $q = trim((string) $request->get('q', ''));
        $anio = $this->anioProyeccionCostos((int) $request->get('anio', 0));
        $page = max(1, (int) $request->get('page', 1));
        $sinPaginar = in_array(strtolower((string) $request->get('sin_paginar', '')), ['1', 'true', 'yes'], true);
        $perPage = (int) $request->get('per_page', 25);
        if (! $sinPaginar) {
            if ($perPage < 10) {
                $perPage = 10;
            }
            if ($perPage > 200) {
                $perPage = 200;
            }
        } elseif ($perPage < 1) {
            $perPage = 50000;
        }
        $hasCard = Schema::hasColumn('tbl_pv_productos_costo', 'card_code');
        $hasMes = Schema::hasColumn('tbl_pv_productos_costo', 'mes');
        $hasAnio = $this->hasAnioCostos();

        $map = [];

        // 1) Maestro local (Año + Empresa + CardCode + ItemCode + Mes)
        $masterQ = PvProductoCosto::query();
        if ($hasAnio) {
            $masterQ->where('anio', $anio);
        }
        if ($empresa !== '') {
            $masterQ->whereRaw('UPPER(empresa) = ?', [$empresa]);
        }
        if ($cliente !== '' && $hasCard) {
            $likeCliente = '%'.$cliente.'%';
            $masterQ->where(function ($w) use ($likeCliente) {
                $w->where('card_code', 'like', $likeCliente)
                    ->orWhere('card_name', 'like', $likeCliente);
            });
        }
        if ($itemcode !== '') {
            $likeItem = '%'.$itemcode.'%';
            $masterQ->where(function ($w) use ($likeItem) {
                $w->where('producto_codigo', 'like', $likeItem)
                    ->orWhere('producto_nombre', 'like', $likeItem);
            });
        }
        $masterQ->orderBy('empresa')->orderBy('producto_codigo')->orderBy('id')->get()
            ->each(function (PvProductoCosto $row) use (&$map, $hasCard, $hasMes, $hasAnio, $anio) {
                $emp = strtoupper(trim((string) $row->empresa));
                $card = $hasCard ? trim((string) ($row->card_code ?? '')) : '';
                $cod = trim((string) $row->producto_codigo);
                $mes = $hasMes ? (int) ($row->mes ?? 0) : 0;
                $key = $emp.'|'.$card.'|'.$cod.'|'.$mes;
                $map[$key] = [
                    'empresa' => $emp,
                    'card_code' => $card,
                    'card_name' => $hasCard ? trim((string) ($row->card_name ?? '')) : '',
                    'producto_codigo' => $cod,
                    'producto_nombre' => $row->producto_nombre,
                    'mes' => $mes > 0 ? $mes : null,
                    'anio' => $hasAnio ? (int) ($row->anio ?? $anio) : $anio,
                    'costo_unitario' => (float) $row->costo_unitario,
                    'moneda' => strtoupper((string) ($row->moneda ?: 'MXN')),
                    'tiene_maestro' => true,
                    'updated_at' => $row->updated_at ? $row->updated_at->format('Y-m-d H:i') : null,
                ];
            });

        // 2) Productos proyectados / asignados sin fila en maestro (card/mes vacíos)
        //    Solo cuando no hay filtro de cliente (los huecos no tienen CardCode).
        $agregarHueco = function (string $emp, string $cod, ?string $nombre) use (&$map, $anio) {
            $key = $emp.'||'.$cod.'|0';
            if (isset($map[$key])) {
                if ($nombre && empty($map[$key]['producto_nombre'])) {
                    $map[$key]['producto_nombre'] = $nombre;
                }

                return;
            }
            // Si ya hay filas del mismo producto con card/mes, no duplicar hueco.
            foreach ($map as $k => $it) {
                if (($it['empresa'] ?? '') === $emp && ($it['producto_codigo'] ?? '') === $cod) {
                    return;
                }
            }
            $map[$key] = [
                'empresa' => $emp,
                'card_code' => '',
                'card_name' => '',
                'producto_codigo' => $cod,
                'producto_nombre' => $nombre,
                'mes' => null,
                'anio' => $anio,
                'costo_unitario' => 0.0,
                'moneda' => 'MXN',
                'tiene_maestro' => false,
                'updated_at' => null,
            ];
        };

        if ($cliente === '') {
            if (Schema::hasTable('tbl_pv_proyecciones')) {
                $proyQ = DB::table('tbl_pv_proyecciones as p')
                    ->select('p.empresa', 'p.producto_codigo', 'p.producto_nombre', DB::raw('MAX(p.costo_unitario) as costo_snap'))
                    ->groupBy('p.empresa', 'p.producto_codigo', 'p.producto_nombre');
                if (Schema::hasTable('tbl_pv_ciclos')) {
                    $proyQ->join('tbl_pv_ciclos as c', DB::raw('UPPER(c.codigo)'), '=', DB::raw('UPPER(p.ciclo_codigo)'))
                        ->where('c.anio_presupuesto', $anio);
                }
                if ($empresa !== '') {
                    $proyQ->whereRaw('UPPER(p.empresa) = ?', [$empresa]);
                }
                if ($itemcode !== '') {
                    $likeItem = '%'.$itemcode.'%';
                    $proyQ->where(function ($w) use ($likeItem) {
                        $w->where('p.producto_codigo', 'like', $likeItem)
                            ->orWhere('p.producto_nombre', 'like', $likeItem);
                    });
                }
                foreach ($proyQ->get() as $row) {
                    $emp = strtoupper(trim((string) $row->empresa));
                    $cod = trim((string) $row->producto_codigo);
                    $agregarHueco($emp, $cod, $row->producto_nombre);
                }
            }

            if (Schema::hasTable('tbl_pv_asignacion_productos') && Schema::hasTable('tbl_pv_asignaciones')) {
                $asigQ = DB::table('tbl_pv_asignacion_productos as ap')
                    ->join('tbl_pv_asignaciones as a', 'a.id', '=', 'ap.asignacion_id')
                    ->select('a.empresa', 'ap.producto_codigo', 'ap.producto_nombre')
                    ->groupBy('a.empresa', 'ap.producto_codigo', 'ap.producto_nombre');
                if (Schema::hasTable('tbl_pv_ciclos')) {
                    $asigQ->join('tbl_pv_ciclos as c', DB::raw('UPPER(c.codigo)'), '=', DB::raw('UPPER(a.ciclo_codigo)'))
                        ->where('c.anio_presupuesto', $anio);
                }
                if ($empresa !== '') {
                    $asigQ->whereRaw('UPPER(a.empresa) = ?', [$empresa]);
                }
                if ($itemcode !== '') {
                    $likeItem = '%'.$itemcode.'%';
                    $asigQ->where(function ($w) use ($likeItem) {
                        $w->where('ap.producto_codigo', 'like', $likeItem)
                            ->orWhere('ap.producto_nombre', 'like', $likeItem);
                    });
                }
                foreach ($asigQ->get() as $row) {
                    $emp = strtoupper(trim((string) $row->empresa));
                    $cod = trim((string) $row->producto_codigo);
                    $agregarHueco($emp, $cod, $row->producto_nombre);
                }
            }
        }

        $items = array_values($map);
        $nombres = $this->mapaNombresProductosLocal();
        foreach ($items as &$it) {
            $cod = trim((string) ($it['producto_codigo'] ?? ''));
            $nom = trim((string) ($it['producto_nombre'] ?? ''));
            if ($cod !== '' && ($nom === '' || strcasecmp($nom, $cod) === 0) && ! empty($nombres[$cod])) {
                $it['producto_nombre'] = $nombres[$cod];
            }
        }
        unset($it);

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $items = array_values(array_filter($items, function ($it) use ($needle) {
                $blob = mb_strtolower(
                    ($it['empresa'] ?? '').' '.
                    ($it['card_code'] ?? '').' '.
                    ($it['card_name'] ?? '').' '.
                    ($it['producto_codigo'] ?? '').' '.
                    ($it['producto_nombre'] ?? '')
                );

                return strpos($blob, $needle) !== false;
            }));
        }

        usort($items, function ($a, $b) {
            $c = strcmp($a['empresa'], $b['empresa']);
            if ($c !== 0) {
                return $c;
            }
            $c = strcmp((string) ($a['card_code'] ?? ''), (string) ($b['card_code'] ?? ''));
            if ($c !== 0) {
                return $c;
            }
            $c = strcmp($a['producto_codigo'], $b['producto_codigo']);
            if ($c !== 0) {
                return $c;
            }

            return ((int) ($a['mes'] ?? 0)) <=> ((int) ($b['mes'] ?? 0));
        });

        // Vista agrupada: 1 fila por Empresa+CardCode+ItemCode (BD sigue por mes).
        $groupedFlag = $request->get('grouped', '1');
        $doGroup = ! in_array(strtolower((string) $groupedFlag), ['0', 'false', 'no', 'flat'], true);
        if ($doGroup) {
            $items = $this->agruparCostosPorProductoCliente($items);
        }

        $total = count($items);
        $lastPage = max(1, (int) ceil($total / $perPage));
        if ($page > $lastPage) {
            $page = $lastPage;
        }
        $offset = ($page - 1) * $perPage;
        $pageItems = array_slice($items, $offset, $perPage);

        return response()->json([
            'ok' => true,
            'anio' => $anio,
            'anios' => $this->aniosDisponiblesCostos($anio),
            'grouped' => $doGroup,
            'items' => array_values($pageItems),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
            'from' => $total === 0 ? 0 : ($offset + 1),
            'to' => $total === 0 ? 0 : min($offset + $perPage, $total),
        ]);
    }

    /**
     * Años de proyección con maestro de precios (más ciclos conocidos).
     *
     * @return array<int, int>
     */
    protected function aniosDisponiblesCostos(?int $incluir = null): array
    {
        $set = [];
        if ($incluir && $incluir >= 2000 && $incluir <= 2100) {
            $set[$incluir] = true;
        }
        $set[$this->anioProyeccionCostos()] = true;
        if (Schema::hasTable('tbl_pv_ciclos')) {
            foreach (PvCiclo::query()->pluck('anio_presupuesto') as $a) {
                $a = (int) $a;
                if ($a >= 2000 && $a <= 2100) {
                    $set[$a] = true;
                }
            }
        }
        if ($this->hasAnioCostos()) {
            foreach (PvProductoCosto::query()->distinct()->orderBy('anio')->pluck('anio') as $a) {
                $a = (int) $a;
                if ($a >= 2000 && $a <= 2100) {
                    $set[$a] = true;
                }
            }
        }
        $out = array_map('intval', array_keys($set));
        rsort($out);

        return array_values($out);
    }

    /**
     * Agrupa filas mes-a-mes en una por Empresa+CardCode+ItemCode.
     * precio_meses[0..11] = precio del mes; costo_unitario = precio global (moda / último mes).
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function agruparCostosPorProductoCliente(array $items): array
    {
        $groups = [];
        foreach ($items as $it) {
            if (! is_array($it)) {
                continue;
            }
            $emp = strtoupper(trim((string) ($it['empresa'] ?? '')));
            $card = trim((string) ($it['card_code'] ?? ''));
            $cod = trim((string) ($it['producto_codigo'] ?? ''));
            if ($emp === '' || $cod === '') {
                continue;
            }
            $key = $emp.'|'.$card.'|'.$cod;
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'empresa' => $emp,
                    'card_code' => $card,
                    'card_name' => trim((string) ($it['card_name'] ?? '')),
                    'producto_codigo' => $cod,
                    'producto_nombre' => $it['producto_nombre'] ?? null,
                    'mes' => null,
                    'costo_unitario' => 0.0,
                    'moneda' => strtoupper((string) ($it['moneda'] ?? 'MXN')) ?: 'MXN',
                    'tiene_maestro' => ! empty($it['tiene_maestro']),
                    'updated_at' => $it['updated_at'] ?? null,
                    'precio_meses' => array_fill(0, 12, null),
                    'monedas_meses' => array_fill(0, 12, null),
                    'meses_count' => 0,
                    'precios_distintos' => 0,
                    'meses_label' => '—',
                ];
            }
            $g = &$groups[$key];
            if (! empty($it['card_name']) && empty($g['card_name'])) {
                $g['card_name'] = trim((string) $it['card_name']);
            }
            if (! empty($it['producto_nombre']) && (
                empty($g['producto_nombre'])
                || strcasecmp((string) $g['producto_nombre'], $cod) === 0
            )) {
                $g['producto_nombre'] = $it['producto_nombre'];
            }
            if (! empty($it['tiene_maestro'])) {
                $g['tiene_maestro'] = true;
            }
            $upd = (string) ($it['updated_at'] ?? '');
            if ($upd !== '' && ($g['updated_at'] === null || strcmp($upd, (string) $g['updated_at']) > 0)) {
                $g['updated_at'] = $upd;
            }

            $mes = (int) ($it['mes'] ?? 0);
            $precio = (float) ($it['costo_unitario'] ?? 0);
            $moneda = strtoupper((string) ($it['moneda'] ?? 'MXN')) ?: 'MXN';
            if ($mes >= 1 && $mes <= 12 && $precio > 0) {
                $idx = $mes - 1;
                $g['precio_meses'][$idx] = round($precio, 4);
                $g['monedas_meses'][$idx] = $moneda;
            } elseif ($mes === 0 && $precio > 0) {
                // Fila global explícita (mes=0): manda sobre la moda de meses.
                if (! isset($g['global_explicito']) || $g['global_explicito'] === null) {
                    $g['global_explicito'] = round($precio, 4);
                    $g['moneda'] = $moneda;
                }
            }
            unset($g);
        }

        $out = [];
        foreach ($groups as $g) {
            $filled = [];
            $first = null;
            $last = null;
            $freq = [];
            $freqMon = [];
            for ($i = 0; $i < 12; $i++) {
                $p = $g['precio_meses'][$i];
                if ($p === null || ! ((float) $p > 0)) {
                    continue;
                }
                $filled[] = $i + 1;
                if ($first === null) {
                    $first = $i + 1;
                }
                $last = $i + 1;
                $keyP = number_format((float) $p, 4, '.', '');
                $freq[$keyP] = ($freq[$keyP] ?? 0) + 1;
                $mon = (string) ($g['monedas_meses'][$i] ?? $g['moneda']);
                $freqMon[$mon] = ($freqMon[$mon] ?? 0) + 1;
            }
            $g['meses_count'] = count($filled);
            $g['precios_distintos'] = count($freq);

            $globalExplicito = isset($g['global_explicito']) ? (float) $g['global_explicito'] : 0.0;
            unset($g['global_explicito']);

            if ($globalExplicito > 0) {
                // Precio global de Ventas/Costos (mes=0).
                $g['costo_unitario'] = $globalExplicito;
            } elseif ($freq) {
                arsort($freq);
                $modeKey = (string) array_key_first($freq);
                $g['costo_unitario'] = (float) $modeKey;
            } elseif ($last !== null) {
                $g['costo_unitario'] = (float) $g['precio_meses'][$last - 1];
            }

            if ($freqMon) {
                arsort($freqMon);
                // Si hay global explícito, conserva su moneda; si no, moda de meses.
                if ($globalExplicito <= 0) {
                    $g['moneda'] = (string) array_key_first($freqMon);
                }
            }

            if ($g['meses_count'] === 0) {
                $g['meses_label'] = 'Sin meses';
            } elseif ($g['meses_count'] === 1) {
                $mesesNom = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                $g['meses_label'] = $mesesNom[$first].' ('.$first.')';
                $g['mes'] = $first;
            } elseif ($first !== null && $last !== null && ($last - $first + 1) === $g['meses_count']) {
                $mesesNom = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                $g['meses_label'] = $mesesNom[$first].'–'.$mesesNom[$last].' ('.$g['meses_count'].')';
            } else {
                $g['meses_label'] = $g['meses_count'].' meses';
            }
            if ($g['precios_distintos'] > 1) {
                $g['meses_label'] .= ' · varía';
            }

            $out[] = $g;
        }

        usort($out, function ($a, $b) {
            $c = strcmp($a['empresa'], $b['empresa']);
            if ($c !== 0) {
                return $c;
            }
            $c = strcmp((string) ($a['card_code'] ?? ''), (string) ($b['card_code'] ?? ''));
            if ($c !== 0) {
                return $c;
            }

            return strcmp($a['producto_codigo'], $b['producto_codigo']);
        });

        return $out;
    }

    /**
     * Consulta /precios-mensuales para una fila (Empresa+CardCode+ItemCode+Mes).
     * Si confirmar=false solo compara; si confirmar=true actualiza solo precio (y moneda API).
     */
    public function actualizarCostoDesdeApi(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tbl_pv_productos_costo')) {
            return response()->json(['message' => 'Falta ejecutar la migración de costos de productos.'], 422);
        }

        $data = $request->validate([
            'empresa' => 'required|string|max:40',
            'card_code' => 'nullable|string|max:40',
            'producto_codigo' => 'required|string|max:80',
            'mes' => 'nullable|integer|min:0|max:12',
            'anio' => 'nullable|integer|min:2000|max:2100',
            'anio_proyeccion' => 'nullable|integer|min:2000|max:2100',
            'confirmar' => 'nullable|boolean',
        ]);

        $empresa = strtoupper(trim($data['empresa']));
        $card = trim((string) ($data['card_code'] ?? ''));
        $item = trim($data['producto_codigo']);
        $mes = (int) ($data['mes'] ?? 0);
        if ($mes < 0 || $mes > 12) {
            $mes = 0;
        }
        $anioApi = (int) ($data['anio'] ?? date('Y'));
        $anioProy = $this->anioProyeccionCostos((int) ($data['anio_proyeccion'] ?? 0));
        $confirmar = (bool) ($data['confirmar'] ?? false);

        $lookup = [
            'empresa' => $empresa,
            'producto_codigo' => $item,
        ];
        if ($this->hasAnioCostos()) {
            $lookup['anio'] = $anioProy;
        }
        if (Schema::hasColumn('tbl_pv_productos_costo', 'card_code')) {
            $lookup['card_code'] = $card;
        }
        if (Schema::hasColumn('tbl_pv_productos_costo', 'mes')) {
            $lookup['mes'] = $mes;
        }

        $local = PvProductoCosto::query()->where($lookup)->first();
        $precioLocal = $local ? (float) $local->costo_unitario : null;
        $monedaLocal = $local ? strtoupper((string) ($local->moneda ?: 'MXN')) : null;

        $filters = [
            'year' => $anioApi,
            'Empresa' => $empresa,
            'ItemCode' => $item,
            'per_page' => 50,
            'page' => 1,
        ];
        if ($card !== '') {
            $filters['CardCode'] = $card;
        }
        if ($mes > 0) {
            $filters['Mes'] = $mes;
        }

        try {
            $api = app(AutinApiClient::class);
            $res = $api->preciosMensuales($filters);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Error al consultar API: '.$e->getMessage()], 422);
        }
        if (empty($res['ok'])) {
            return response()->json([
                'message' => $res['message'] ?? 'Sin conexión a precios-mensuales',
            ], 422);
        }

        $body = is_array($res['body'] ?? null) ? $res['body'] : [];
        $rows = is_array($body['data'] ?? null) ? $body['data'] : [];
        $hit = null;
        foreach ($rows as $apiRow) {
            if (! is_array($apiRow)) {
                continue;
            }
            $apiItem = trim((string) ($apiRow['ItemCode'] ?? ''));
            $apiCard = trim((string) ($apiRow['CardCode'] ?? ''));
            $apiMes = (int) ($apiRow['Mes'] ?? 0);
            if (strcasecmp($apiItem, $item) !== 0) {
                continue;
            }
            if ($card !== '' && strcasecmp($apiCard, $card) !== 0) {
                continue;
            }
            if ($mes > 0 && $apiMes !== $mes) {
                continue;
            }
            $hit = $apiRow;
            break;
        }

        if (! $hit) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontró ese producto en la API (Empresa + CardCode + ItemCode + Mes).',
                'empresa' => $empresa,
                'card_code' => $card,
                'producto_codigo' => $item,
                'mes' => $mes > 0 ? $mes : null,
                'precio_local' => $precioLocal,
                'moneda_local' => $monedaLocal,
            ], 404);
        }

        $precioApi = round($this->numeroVenta($hit, ['Precio', 'Price', 'precio']), 4);
        $monedaApi = strtoupper(trim((string) (
            $hit['Moneda'] ?? $hit['Currency'] ?? $hit['DocCur'] ?? ''
        )));
        if (! in_array($monedaApi, ['MXN', 'USD'], true)) {
            $monedaApi = $monedaLocal ?: 'MXN';
        }
        $cardNameApi = trim((string) ($hit['CardName'] ?? ''));

        $mismoPrecio = $precioLocal !== null && abs($precioLocal - $precioApi) < 0.0001;
        $mismaMoneda = $monedaLocal !== null && $monedaLocal === $monedaApi;
        $igual = $mismoPrecio && $mismaMoneda;

        if (! $confirmar) {
            return response()->json([
                'ok' => true,
                'diferente' => ! $igual,
                'igual' => $igual,
                'empresa' => $empresa,
                'card_code' => $card,
                'producto_codigo' => $item,
                'mes' => $mes > 0 ? $mes : ((int) ($hit['Mes'] ?? 0) ?: null),
                'precio_local' => $precioLocal,
                'moneda_local' => $monedaLocal,
                'precio_api' => $precioApi,
                'moneda_api' => $monedaApi,
                'message' => $igual
                    ? 'El precio local ya coincide con la API.'
                    : 'El precio de la API es distinto al local.',
            ]);
        }

        if ($igual) {
            return response()->json([
                'ok' => true,
                'actualizado' => false,
                'igual' => true,
                'message' => 'No hubo cambios: el precio ya era el mismo.',
                'precio_local' => $precioLocal,
                'precio_api' => $precioApi,
                'moneda_api' => $monedaApi,
            ]);
        }

        if (! $local) {
            $local = new PvProductoCosto();
            $local->empresa = $empresa;
            $local->producto_codigo = $item;
            if ($this->hasAnioCostos()) {
                $local->anio = $anioProy;
            }
            if (Schema::hasColumn('tbl_pv_productos_costo', 'card_code')) {
                $local->card_code = $card;
            }
            if (Schema::hasColumn('tbl_pv_productos_costo', 'mes')) {
                $local->mes = $mes;
            }
            $local->producto_nombre = $item;
            $local->moneda = $monedaApi;
        }

        $precioAnterior = $local->exists ? (float) $local->costo_unitario : null;
        $monedaAnterior = $local->exists ? (string) ($local->moneda ?: 'MXN') : null;

        // Solo precio (+ moneda de la API). No toca nombre ni otros campos salvo card_name vacío.
        $local->costo_unitario = $precioApi;
        $local->moneda = $monedaApi;
        if ($this->hasAnioCostos() && ! $local->anio) {
            $local->anio = $anioProy;
        }
        if (Schema::hasColumn('tbl_pv_productos_costo', 'card_name')
            && $cardNameApi !== ''
            && trim((string) ($local->card_name ?? '')) === '') {
            $local->card_name = mb_substr($cardNameApi, 0, 180);
        }
        $local->updated_by = optional($request->user())->id;
        $local->save();

        $this->registrarHistorialPrecio(
            $empresa,
            $item,
            (string) $local->producto_nombre,
            $precioAnterior,
            $monedaAnterior,
            $precioApi,
            $monedaApi,
            'api',
            $local->updated_by,
            $card,
            (string) ($local->card_name ?? ''),
            $mes,
            $anioProy
        );

        return response()->json([
            'ok' => true,
            'actualizado' => true,
            'message' => 'Precio actualizado desde la API.',
            'precio_anterior' => $precioAnterior,
            'moneda_anterior' => $monedaAnterior,
            'precio_nuevo' => $precioApi,
            'moneda_nueva' => $monedaApi,
            'item' => [
                'empresa' => $empresa,
                'card_code' => (string) ($local->card_code ?? $card),
                'producto_codigo' => $item,
                'mes' => ((int) ($local->mes ?? 0)) > 0 ? (int) $local->mes : null,
                'costo_unitario' => (float) $local->costo_unitario,
                'moneda' => strtoupper((string) ($local->moneda ?: 'MXN')),
                'updated_at' => $local->updated_at ? $local->updated_at->format('Y-m-d H:i') : null,
            ],
        ]);
    }

    public function guardarCostoProducto(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tbl_pv_productos_costo')) {
            return response()->json(['message' => 'Falta ejecutar la migración de costos de productos.'], 422);
        }

        $data = $request->validate([
            'empresa' => 'required|string|max:40',
            'card_code' => 'nullable|string|max:40',
            'card_name' => 'nullable|string|max:180',
            'producto_codigo' => 'required|string|max:80',
            'producto_nombre' => 'nullable|string|max:180',
            'mes' => 'nullable|integer|min:0|max:12',
            'anio' => 'nullable|integer|min:2000|max:2100',
            'costo_unitario' => 'required|numeric|min:0',
            'moneda' => 'nullable|string|max:8',
        ]);

        $empresa = strtoupper(trim($data['empresa']));
        $cardCode = trim((string) ($data['card_code'] ?? ''));
        $cardName = trim((string) ($data['card_name'] ?? ''));
        $codigo = trim($data['producto_codigo']);
        $mes = (int) ($data['mes'] ?? 0);
        if ($mes < 0 || $mes > 12) {
            $mes = 0;
        }
        $anio = $this->anioProyeccionCostos((int) ($data['anio'] ?? 0));
        $costo = round((float) $data['costo_unitario'], 4);
        $moneda = strtoupper(trim((string) ($data['moneda'] ?? 'MXN'))) ?: 'MXN';
        if (! in_array($moneda, ['MXN', 'USD'], true)) {
            $moneda = 'MXN';
        }

        $lookup = [
            'empresa' => $empresa,
            'producto_codigo' => $codigo,
        ];
        if ($this->hasAnioCostos()) {
            $lookup['anio'] = $anio;
        }
        if (Schema::hasColumn('tbl_pv_productos_costo', 'card_code')) {
            $lookup['card_code'] = $cardCode;
        }
        if (Schema::hasColumn('tbl_pv_productos_costo', 'mes')) {
            $lookup['mes'] = $mes;
        }

        $row = PvProductoCosto::query()->firstOrNew($lookup);
        $precioAnterior = $row->exists ? (float) $row->costo_unitario : null;
        $monedaAnterior = $row->exists ? (string) ($row->moneda ?: 'MXN') : null;
        if (! empty($data['producto_nombre'])) {
            $row->producto_nombre = $data['producto_nombre'];
        } elseif (! $row->exists) {
            $row->producto_nombre = $codigo;
        }
        if ($this->hasAnioCostos()) {
            $row->anio = $anio;
        }
        if (Schema::hasColumn('tbl_pv_productos_costo', 'card_code')) {
            $row->card_code = $cardCode;
        }
        if (Schema::hasColumn('tbl_pv_productos_costo', 'card_name')) {
            $row->card_name = $cardName !== '' ? $cardName : $row->card_name;
        }
        if (Schema::hasColumn('tbl_pv_productos_costo', 'mes')) {
            $row->mes = $mes;
        }
        $row->costo_unitario = $costo;
        $row->moneda = $moneda;
        $row->updated_by = optional($request->user())->id;
        $row->save();
        $this->registrarHistorialPrecio(
            $empresa,
            $codigo,
            (string) $row->producto_nombre,
            $precioAnterior,
            $monedaAnterior,
            $costo,
            $moneda,
            'manual',
            $row->updated_by,
            $cardCode,
            $cardName,
            $mes,
            $anio
        );

        // Solo propagar el global (mes=0 o moda del producto), nunca el precio de un mes suelto
        // (antes el último PUT del modal dejaba Dic como costo_unitario de la proyección).
        $propagadas = 0;
        $globalProp = $this->precioGlobalMaestroProducto($empresa, $codigo, $cardCode, $anio);
        if ($globalProp !== null && $globalProp > 0) {
            $propagadas = $this->propagarCostoACiclosAbiertos($empresa, $codigo, $globalProp);
        }

        return response()->json([
            'ok' => true,
            'item' => [
                'empresa' => $row->empresa,
                'anio' => $anio,
                'card_code' => (string) ($row->card_code ?? ''),
                'card_name' => (string) ($row->card_name ?? ''),
                'producto_codigo' => $row->producto_codigo,
                'producto_nombre' => $row->producto_nombre,
                'mes' => ((int) ($row->mes ?? 0)) > 0 ? (int) $row->mes : null,
                'costo_unitario' => (float) $row->costo_unitario,
                'moneda' => $row->moneda,
                'tiene_maestro' => true,
                'updated_at' => $row->updated_at ? $row->updated_at->format('Y-m-d H:i') : null,
            ],
            'proyecciones_actualizadas' => $propagadas,
            'precio_global' => $globalProp,
            'message' => $propagadas > 0
                ? ('Precio guardado. Se actualizó el global en '.$propagadas.' proyección(es) de ciclos abiertos.')
                : 'Precio guardado en maestro local. No había proyecciones en ciclos abiertos para actualizar.',
        ]);
    }

    /**
     * Upsert de precios mensuales desde Captura → tbl_pv_productos_costo.
     * Por cada mes con valor: crea si no existe, actualiza solo si cambió.
     */
    public function guardarCostosMeses(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tbl_pv_productos_costo')) {
            return response()->json(['message' => 'Falta ejecutar la migración de costos de productos.'], 422);
        }

        $data = $request->validate([
            'empresa' => 'required|string|max:40',
            'card_code' => 'nullable|string|max:40',
            'card_name' => 'nullable|string|max:180',
            'producto_codigo' => 'required|string|max:80',
            'producto_nombre' => 'nullable|string|max:180',
            'moneda' => 'nullable|string|max:8',
            'anio' => 'nullable|integer|min:2000|max:2100',
            'meses' => 'required|array|size:12',
            'meses.*' => 'nullable|numeric|min:0',
        ]);

        $empresa = strtoupper(trim($data['empresa']));
        $cardCode = trim((string) ($data['card_code'] ?? ''));
        $cardName = trim((string) ($data['card_name'] ?? ''));
        $codigo = trim($data['producto_codigo']);
        $nombre = trim((string) ($data['producto_nombre'] ?? '')) ?: $codigo;
        $anio = $this->anioProyeccionCostos((int) ($data['anio'] ?? 0));
        $moneda = strtoupper(trim((string) ($data['moneda'] ?? 'MXN'))) ?: 'MXN';
        if (! in_array($moneda, ['MXN', 'USD'], true)) {
            $moneda = 'MXN';
        }
        $userId = optional($request->user())->id;
        $hasCard = Schema::hasColumn('tbl_pv_productos_costo', 'card_code');
        $hasCardName = Schema::hasColumn('tbl_pv_productos_costo', 'card_name');
        $hasMes = Schema::hasColumn('tbl_pv_productos_costo', 'mes');
        $hasAnio = $this->hasAnioCostos();

        $creados = 0;
        $actualizados = 0;
        $sinCambio = 0;
        $precioMesesOut = array_fill(0, 12, null);

        foreach ($data['meses'] as $idx => $raw) {
            if ($raw === null || $raw === '') {
                continue;
            }
            $precio = round((float) $raw, 4);
            if ($precio <= 0) {
                continue;
            }
            $mes = ((int) $idx) + 1;
            if ($mes < 1 || $mes > 12) {
                continue;
            }
            $precioMesesOut[$mes - 1] = $precio;

            $lookup = [
                'empresa' => $empresa,
                'producto_codigo' => $codigo,
            ];
            if ($hasAnio) {
                $lookup['anio'] = $anio;
            }
            if ($hasCard) {
                $lookup['card_code'] = $cardCode;
            }
            if ($hasMes) {
                $lookup['mes'] = $mes;
            }

            $row = PvProductoCosto::query()->firstOrNew($lookup);
            $esNuevo = ! $row->exists;
            $precioAnterior = $esNuevo ? null : (float) $row->costo_unitario;
            $monedaAnterior = $esNuevo ? null : (string) ($row->moneda ?: 'MXN');

            if (! $esNuevo
                && abs((float) $row->costo_unitario - $precio) < 0.0001
                && strtoupper((string) ($row->moneda ?: 'MXN')) === $moneda
            ) {
                $sinCambio++;
                continue;
            }

            if ($nombre !== '') {
                $row->producto_nombre = $nombre;
            } elseif (! $row->exists) {
                $row->producto_nombre = $codigo;
            }
            if ($hasAnio) {
                $row->anio = $anio;
            }
            if ($hasCard) {
                $row->card_code = $cardCode;
            }
            if ($hasCardName && $cardName !== '') {
                $row->card_name = $cardName;
            }
            if ($hasMes) {
                $row->mes = $mes;
            }
            $row->costo_unitario = $precio;
            $row->moneda = $moneda;
            $row->updated_by = $userId;
            $row->save();

            $this->registrarHistorialPrecio(
                $empresa,
                $codigo,
                (string) $row->producto_nombre,
                $precioAnterior,
                $monedaAnterior,
                $precio,
                $moneda,
                'manual',
                $userId,
                $cardCode,
                $cardName,
                $mes,
                $anio
            );

            if ($esNuevo) {
                $creados++;
            } else {
                $actualizados++;
            }
        }

        // Global explícito (mes=0) = moda de los meses enviados; propaga a proyecciones abiertas.
        $freq = [];
        foreach ($precioMesesOut as $p) {
            if ($p === null || ! ((float) $p > 0)) {
                continue;
            }
            $keyP = number_format((float) $p, 4, '.', '');
            $freq[$keyP] = ($freq[$keyP] ?? 0) + 1;
        }
        $global = null;
        if ($freq) {
            arsort($freq);
            $global = round((float) array_key_first($freq), 4);
        }
        $propagadas = 0;
        if ($global !== null && $global > 0 && $hasMes) {
            $lookupG = [
                'empresa' => $empresa,
                'producto_codigo' => $codigo,
            ];
            if ($hasAnio) {
                $lookupG['anio'] = $anio;
            }
            if ($hasCard) {
                $lookupG['card_code'] = $cardCode;
            }
            $lookupG['mes'] = 0;
            $rowG = PvProductoCosto::query()->firstOrNew($lookupG);
            $esNuevoG = ! $rowG->exists;
            $precioAntG = $esNuevoG ? null : (float) $rowG->costo_unitario;
            $monedaAntG = $esNuevoG ? null : (string) ($rowG->moneda ?: 'MXN');
            if ($nombre !== '') {
                $rowG->producto_nombre = $nombre;
            } elseif (! $rowG->exists) {
                $rowG->producto_nombre = $codigo;
            }
            if ($hasAnio) {
                $rowG->anio = $anio;
            }
            if ($hasCard) {
                $rowG->card_code = $cardCode;
            }
            if ($hasCardName && $cardName !== '') {
                $rowG->card_name = $cardName;
            }
            $rowG->mes = 0;
            $rowG->costo_unitario = $global;
            $rowG->moneda = $moneda;
            $rowG->updated_by = $userId;
            if ($esNuevoG || abs((float) ($precioAntG ?? 0) - $global) >= 0.0001
                || strtoupper((string) ($monedaAntG ?: 'MXN')) !== $moneda) {
                $rowG->save();
                $this->registrarHistorialPrecio(
                    $empresa,
                    $codigo,
                    (string) $rowG->producto_nombre,
                    $precioAntG,
                    $monedaAntG,
                    $global,
                    $moneda,
                    'manual',
                    $userId,
                    $cardCode,
                    $cardName,
                    0,
                    $anio
                );
                if ($esNuevoG) {
                    $creados++;
                } else {
                    $actualizados++;
                }
            } else {
                $sinCambio++;
            }
            $propagadas = $this->propagarCostoACiclosAbiertos($empresa, $codigo, $global);
        }

        // Refresca mapa de meses en memoria del cliente vía respuesta.
        return response()->json([
            'ok' => true,
            'anio' => $anio,
            'creados' => $creados,
            'actualizados' => $actualizados,
            'sin_cambio' => $sinCambio,
            'precio_meses' => $precioMesesOut,
            'precio_global' => $global,
            'proyecciones_actualizadas' => $propagadas,
            'moneda' => $moneda,
            'message' => trim(
                ($creados ? ($creados.' creado(s)') : '').
                ($creados && $actualizados ? ', ' : '').
                ($actualizados ? ($actualizados.' actualizado(s)') : '').
                ((! $creados && ! $actualizados) ? 'Sin cambios en maestro de precios.' : ' en Ventas/Costos.')
            ),
        ]);
    }

    public function historialCostoProducto(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tbl_pv_productos_costo_historial')) {
            return response()->json(['message' => 'Falta ejecutar la migración de historial de precios.'], 422);
        }

        $data = $request->validate([
            'empresa' => 'required|string|max:40',
            'producto_codigo' => 'required|string|max:80',
            'card_code' => 'nullable|string|max:40',
            'mes' => 'nullable|integer|min:0|max:12',
            'anio' => 'nullable|integer|min:2000|max:2100',
        ]);

        $empresa = strtoupper(trim($data['empresa']));
        $codigo = trim($data['producto_codigo']);
        $cardCode = trim((string) ($data['card_code'] ?? ''));
        $mes = (int) ($data['mes'] ?? 0);
        $anio = $this->anioProyeccionCostos((int) ($data['anio'] ?? 0));
        $hasHistCard = Schema::hasColumn('tbl_pv_productos_costo_historial', 'card_code');
        $hasHistMes = Schema::hasColumn('tbl_pv_productos_costo_historial', 'mes');
        $hasHistAnio = Schema::hasColumn('tbl_pv_productos_costo_historial', 'anio');

        $origenLabel = [
            'manual' => 'Edición manual',
            'excel' => 'Plantilla Excel',
            'api' => 'Carga desde API',
            'lista_precios' => 'Lista de precios SAP',
            'asignacion' => 'Al asignar',
        ];
        $mesesNom = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        $histQ = PvProductoCostoHistorial::query()
            ->whereRaw('UPPER(empresa) = ?', [$empresa])
            ->where('producto_codigo', $codigo);
        if ($hasHistAnio) {
            $histQ->where('anio', $anio);
        }
        // Estrictamente el cliente y mes de la fila elegida (no mezclar otros del mismo ItemCode).
        if ($hasHistCard && $cardCode !== '') {
            $histQ->where('card_code', $cardCode);
        }
        if ($hasHistMes && $mes > 0) {
            $histQ->where('mes', $mes);
        }
        $hist = $histQ->orderByDesc('created_at')->orderByDesc('id')->limit(300)->get();

        $users = User::query()
            ->whereIn('id', $hist->pluck('created_by')->filter()->unique()->all())
            ->pluck('name', 'id');

        $rows = $hist->map(function (PvProductoCostoHistorial $row) use ($origenLabel, $users, $mesesNom, $hasHistCard, $hasHistMes) {
            $antes = $row->precio_anterior;
            $despues = (float) $row->precio_nuevo;
            $delta = $antes === null ? null : round($despues - (float) $antes, 4);
            $pct = ($antes === null || (float) $antes == 0.0)
                ? null
                : round(($delta / (float) $antes) * 100, 2);
            $mesRow = $hasHistMes ? (int) ($row->mes ?? 0) : 0;

            return [
                'id' => $row->id,
                'fecha' => $row->created_at ? $row->created_at->format('Y-m-d H:i') : null,
                'card_code' => $hasHistCard ? trim((string) ($row->card_code ?? '')) : '',
                'card_name' => $hasHistCard ? trim((string) ($row->card_name ?? '')) : '',
                'producto_codigo' => (string) $row->producto_codigo,
                'producto_nombre' => $row->producto_nombre,
                'mes' => $mesRow > 0 ? $mesRow : null,
                'mes_label' => ($mesRow >= 1 && $mesRow <= 12) ? ($mesesNom[$mesRow].' ('.$mesRow.')') : '—',
                'precio_anterior' => $antes,
                'precio_nuevo' => $despues,
                'moneda_anterior' => $row->moneda_anterior,
                'moneda_nueva' => $row->moneda_nueva,
                'variacion' => $delta,
                'variacion_pct' => $pct,
                'origen' => $row->origen,
                'origen_label' => $origenLabel[$row->origen] ?? $row->origen,
                'usuario' => $row->created_by ? ($users[$row->created_by] ?? 'Usuario') : 'Sistema',
            ];
        })->values();

        $maestroQ = PvProductoCosto::query()
            ->whereRaw('UPPER(empresa) = ?', [$empresa])
            ->where('producto_codigo', $codigo);
        if ($this->hasAnioCostos()) {
            $maestroQ->where('anio', $anio);
        }
        if (Schema::hasColumn('tbl_pv_productos_costo', 'card_code') && $cardCode !== '') {
            $maestroQ->where('card_code', $cardCode);
        }
        if (Schema::hasColumn('tbl_pv_productos_costo', 'mes') && $mes > 0) {
            $maestroQ->where('mes', $mes);
        }
        $maestro = $maestroQ->orderByDesc('updated_at')->first()
            ?: PvProductoCosto::query()
                ->whereRaw('UPPER(empresa) = ?', [$empresa])
                ->where('producto_codigo', $codigo)
                ->when($this->hasAnioCostos(), function ($q) use ($anio) {
                    $q->where('anio', $anio);
                })
                ->orderByDesc('updated_at')
                ->first();

        $mesActual = $mes > 0 ? $mes : (int) ($maestro->mes ?? 0);
        $cardActual = $cardCode !== '' ? $cardCode : trim((string) ($maestro->card_code ?? ''));
        $cardNameActual = trim((string) ($maestro->card_name ?? ''));
        $nombreActual = trim((string) ($maestro->producto_nombre ?? ''));
        if ($nombreActual === '' || strcasecmp($nombreActual, $codigo) === 0) {
            $nombreActual = (string) ($rows->first()['producto_nombre'] ?? $codigo);
        }

        return response()->json([
            'ok' => true,
            'empresa' => $empresa,
            'card_code' => $cardActual,
            'card_name' => $cardNameActual,
            'producto_codigo' => $codigo,
            'producto_nombre' => $nombreActual,
            'mes' => $mesActual > 0 ? $mesActual : null,
            'mes_label' => ($mesActual >= 1 && $mesActual <= 12)
                ? ($mesesNom[$mesActual].' ('.$mesActual.')')
                : '—',
            'precio_actual' => $maestro ? (float) $maestro->costo_unitario : null,
            'moneda_actual' => $maestro ? strtoupper((string) ($maestro->moneda ?: 'MXN')) : null,
            'items' => $rows,
            'total' => $rows->count(),
        ]);
    }

    /**
     * @param  int|string|null  $userId
     */
    protected function registrarHistorialPrecio(
        string $empresa,
        string $codigo,
        ?string $nombre,
        ?float $precioAnterior,
        ?string $monedaAnterior,
        float $precioNuevo,
        string $monedaNueva,
        string $origen,
        $userId,
        string $cardCode = '',
        string $cardName = '',
        int $mes = 0,
        ?int $anio = null
    ): void {
        if (! Schema::hasTable('tbl_pv_productos_costo_historial')) {
            return;
        }

        $precioNuevo = round($precioNuevo, 4);
        $precioAnterior = $precioAnterior === null ? null : round($precioAnterior, 4);
        $monedaNueva = strtoupper(trim($monedaNueva)) ?: 'MXN';
        $monedaAnterior = $monedaAnterior !== null
            ? (strtoupper(trim($monedaAnterior)) ?: 'MXN')
            : null;

        if ($precioAnterior !== null && $precioAnterior === $precioNuevo && $monedaAnterior === $monedaNueva) {
            return;
        }

        $anio = $this->anioProyeccionCostos($anio);
        $payload = [
            'empresa' => strtoupper(trim($empresa)),
            'producto_codigo' => trim($codigo),
            'producto_nombre' => $nombre ?: $codigo,
            'precio_anterior' => $precioAnterior,
            'precio_nuevo' => $precioNuevo,
            'moneda_anterior' => $monedaAnterior,
            'moneda_nueva' => $monedaNueva,
            'origen' => $origen,
            'created_by' => $userId,
            'created_at' => now(),
        ];
        if (Schema::hasColumn('tbl_pv_productos_costo_historial', 'anio')) {
            $payload['anio'] = $anio;
        }
        if (Schema::hasColumn('tbl_pv_productos_costo_historial', 'card_code')) {
            $payload['card_code'] = trim($cardCode);
        }
        if (Schema::hasColumn('tbl_pv_productos_costo_historial', 'card_name')) {
            $payload['card_name'] = trim($cardName) !== '' ? mb_substr(trim($cardName), 0, 180) : null;
        }
        if (Schema::hasColumn('tbl_pv_productos_costo_historial', 'mes')) {
            $payload['mes'] = ($mes >= 0 && $mes <= 12) ? $mes : 0;
        }

        PvProductoCostoHistorial::query()->create($payload);
    }

    /**
     * Descarga plantilla Formato Precios (Empresa, CardCode, ItemCode, Mes, Precio).
     * Una fila por mes (1–12), con CardCode en cada fila.
     */
    public function plantillaCostos(Request $request)
    {
        if (! Schema::hasTable('tbl_pv_productos_costo')) {
            return response()->json(['message' => 'Falta ejecutar la migración de costos de productos.'], 422);
        }

        $empresa = strtoupper(trim((string) $request->get('empresa', '')));
        $anio = $this->anioProyeccionCostos((int) $request->get('anio', 0));
        $listReq = Request::create('/ProyeccionesVentas/api/costos', 'GET', [
            'empresa' => $empresa,
            'anio' => $anio,
            'grouped' => 1,
            'sin_paginar' => 1,
            'per_page' => 50000,
            'page' => 1,
        ]);
        $listJson = $this->listCostos($listReq)->getData(true);
        $items = is_array($listJson['items'] ?? null) ? $listJson['items'] : [];

        $rows = [];
        foreach ($items as $it) {
            $emp = strtoupper(trim((string) ($it['empresa'] ?? '')));
            $cod = trim((string) ($it['producto_codigo'] ?? ''));
            if ($emp === '' || $cod === '') {
                continue;
            }
            $meses = is_array($it['precio_meses'] ?? null) ? $it['precio_meses'] : array_fill(0, 12, null);
            while (count($meses) < 12) {
                $meses[] = null;
            }
            $global = (float) ($it['costo_unitario'] ?? 0);
            $mesCols = [];
            for ($m = 0; $m < 12; $m++) {
                $p = $meses[$m] ?? null;
                $mesCols[] = ($p !== null && (float) $p > 0) ? round((float) $p, 4) : '';
            }
            $rows[] = array_merge([
                $emp,
                trim((string) ($it['card_code'] ?? '')),
                trim((string) ($it['card_name'] ?? '')),
                $cod,
                trim((string) ($it['producto_nombre'] ?? $cod)),
                strtoupper((string) ($it['moneda'] ?? 'MXN')) ?: 'MXN',
                $global > 0 ? round($global, 4) : '',
            ], $mesCols);
        }

        $suffix = $empresa !== '' ? '_'.$empresa : '';
        $filename = 'Formato_Precios'.$suffix.'_'.now()->format('Ymd').'.xlsx';

        return Excel::download(new PvPreciosPlantillaExport($rows, $anio), $filename);
    }

    /**
     * Importa precios desde Excel.
     * Formato ancho: Empresa, CardCode, Cliente, ItemCode, Producto, Moneda, PrecioGlobal, Ene…Dic.
     * Formato legado: Empresa, CardCode, ItemCode, Mes, Precio.
     */
    public function importarCostosExcel(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tbl_pv_productos_costo')) {
            return response()->json(['message' => 'Falta ejecutar la migración de costos de productos.'], 422);
        }

        $request->validate([
            'archivo' => 'required|file|max:20480',
        ]);
        $ext = strtolower($request->file('archivo')->getClientOriginalExtension());
        if (! in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            return response()->json(['message' => 'El archivo debe ser Excel (.xlsx) o CSV.'], 422);
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

        $map = $this->mapearEncabezadosPrecios($sheet[0] ?? []);
        $tieneMesesAnchos = count($map['meses']) > 0;
        if ($map['empresa'] === null || $map['item'] === null) {
            return response()->json([
                'message' => 'Faltan columnas: Empresa e ItemCode (plantilla Formato Precios).',
            ], 422);
        }
        if (! $tieneMesesAnchos && $map['precio'] === null) {
            return response()->json([
                'message' => 'Faltan columnas de precio: PrecioGlobal/Ene–Dic, o Precio (formato legado).',
            ], 422);
        }

        $userId = optional($request->user())->id;
        $anio = $this->anioProyeccionCostos((int) $request->get('anio', 0));
        $hasAnio = $this->hasAnioCostos();
        $hasCard = Schema::hasColumn('tbl_pv_productos_costo', 'card_code');
        $hasCardName = Schema::hasColumn('tbl_pv_productos_costo', 'card_name');
        $hasMes = Schema::hasColumn('tbl_pv_productos_costo', 'mes');
        $actualizados = 0;
        $creados = 0;
        $omitidos = 0;
        $propagadasTot = 0;
        $errores = [];
        /** @var array<string, array<string, mixed>> $vistos */
        $vistos = [];

        DB::transaction(function () use (
            $sheet, $map, $userId, $anio, $hasAnio, $hasCard, $hasCardName, $hasMes,
            $tieneMesesAnchos, &$actualizados, &$creados, &$omitidos, &$propagadasTot, &$errores, &$vistos
        ) {
            for ($i = 1; $i < count($sheet); $i++) {
                $row = $sheet[$i];
                $fila = $i + 1;
                $empresa = strtoupper($this->celdaTexto($row[$map['empresa']] ?? ''));
                $codigo = $this->celdaTexto($row[$map['item']] ?? '');
                $card = $map['card'] !== null ? $this->celdaTexto($row[$map['card']] ?? '') : '';
                $cardName = $map['cliente'] !== null ? $this->celdaTexto($row[$map['cliente']] ?? '') : '';
                $nombre = $map['producto'] !== null ? $this->celdaTexto($row[$map['producto']] ?? '') : '';
                $moneda = $map['moneda'] !== null
                    ? strtoupper($this->celdaTexto($row[$map['moneda']] ?? '') ?: 'MXN')
                    : 'MXN';
                if (! in_array($moneda, ['MXN', 'USD'], true)) {
                    $moneda = 'MXN';
                }

                if ($empresa === '' && $codigo === '') {
                    $omitidos++;
                    continue;
                }
                if ($empresa === '' || $codigo === '') {
                    $errores[] = ['fila' => $fila, 'mensaje' => 'Empresa o ItemCode vacíos'];
                    continue;
                }

                // Formato ancho: Ene…Dic (+ PrecioGlobal opcional).
                if ($tieneMesesAnchos) {
                    $mesesVals = array_fill(0, 12, null);
                    $mesesLlenos = 0;
                    foreach ($map['meses'] as $idx0 => $colIdx) {
                        $precioMes = $this->celdaNumeroPrecio($row[$colIdx] ?? null);
                        if ($precioMes === null) {
                            continue;
                        }
                        if ($precioMes < 0) {
                            $errores[] = ['fila' => $fila, 'mensaje' => 'Precio de mes inválido'];
                            continue 2;
                        }
                        if ($precioMes <= 0) {
                            continue;
                        }
                        $mesesVals[(int) $idx0] = round($precioMes, 4);
                        $mesesLlenos++;
                    }
                    $globalRaw = $map['precio'] !== null ? ($row[$map['precio']] ?? null) : null;
                    $global = $this->celdaNumeroPrecio($globalRaw);

                    if ($mesesLlenos === 0 && ($global === null || $global <= 0)) {
                        $omitidos++;
                        continue;
                    }

                    // Solo PrecioGlobal (sin meses) → aplica a los 12 meses.
                    if ($mesesLlenos === 0 && $global !== null && $global > 0) {
                        $g = round($global, 4);
                        for ($m = 0; $m < 12; $m++) {
                            $mesesVals[$m] = $g;
                        }
                        $mesesLlenos = 12;
                    }

                    $key = $empresa.'|'.$card.'|'.$codigo;
                    $vistos[$key] = [
                        'modo' => 'ancho',
                        'empresa' => $empresa,
                        'card_code' => $card,
                        'card_name' => $cardName,
                        'codigo' => $codigo,
                        'nombre' => $nombre !== '' ? $nombre : $codigo,
                        'moneda' => $moneda,
                        'meses' => $mesesVals,
                        'global' => ($global !== null && $global > 0) ? round($global, 4) : null,
                        'fila' => $fila,
                    ];
                    continue;
                }

                // Formato legado: Mes + Precio.
                $mesRaw = $map['mes'] !== null ? ($row[$map['mes']] ?? null) : null;
                $mes = 0;
                if ($mesRaw !== null && $mesRaw !== '') {
                    $mes = (int) $mesRaw;
                    if ($mes < 0 || $mes > 12) {
                        $mes = 0;
                    }
                }
                $precioRaw = $map['precio'] !== null ? ($row[$map['precio']] ?? null) : null;
                $precio = $this->celdaNumeroPrecio($precioRaw);
                if ($precio === null) {
                    $omitidos++;
                    continue;
                }
                if ($precio < 0) {
                    $errores[] = ['fila' => $fila, 'mensaje' => 'Precio inválido'];
                    continue;
                }

                $key = $empresa.'|'.$card.'|'.$codigo.'|'.$mes;
                $vistos[$key] = [
                    'modo' => 'legado',
                    'empresa' => $empresa,
                    'card_code' => $card,
                    'card_name' => $cardName,
                    'codigo' => $codigo,
                    'nombre' => $nombre !== '' ? $nombre : $codigo,
                    'moneda' => $moneda,
                    'mes' => $mes,
                    'precio' => round($precio, 4),
                    'fila' => $fila,
                ];
            }

            foreach ($vistos as $hit) {
                if (($hit['modo'] ?? '') === 'ancho') {
                    $mesesHit = is_array($hit['meses'] ?? null) ? $hit['meses'] : array_fill(0, 12, null);
                    $globalExplicit = $hit['global'] ?? null;
                    $freq = [];
                    foreach ($mesesHit as $idx0 => $pMes) {
                        if ($pMes === null || ! ((float) $pMes > 0)) {
                            continue;
                        }
                        $mesNum = ((int) $idx0) + 1;
                        $keyP = number_format((float) $pMes, 4, '.', '');
                        $freq[$keyP] = ($freq[$keyP] ?? 0) + 1;
                        $this->upsertPrecioExcelFila(
                            $hit['empresa'],
                            $hit['card_code'],
                            $hit['card_name'],
                            $hit['codigo'],
                            $hit['nombre'],
                            $hit['moneda'],
                            $mesNum,
                            (float) $pMes,
                            $anio,
                            $userId,
                            $hasAnio,
                            $hasCard,
                            $hasCardName,
                            $hasMes,
                            $creados,
                            $actualizados
                        );
                    }
                    $global = $globalExplicit;
                    if ($global === null && $freq) {
                        arsort($freq);
                        $global = round((float) array_key_first($freq), 4);
                    }
                    if ($global !== null && $global > 0) {
                        $this->upsertPrecioExcelFila(
                            $hit['empresa'],
                            $hit['card_code'],
                            $hit['card_name'],
                            $hit['codigo'],
                            $hit['nombre'],
                            $hit['moneda'],
                            0,
                            (float) $global,
                            $anio,
                            $userId,
                            $hasAnio,
                            $hasCard,
                            $hasCardName,
                            $hasMes,
                            $creados,
                            $actualizados
                        );
                        $propagadasTot += $this->propagarCostoACiclosAbiertos(
                            $hit['empresa'],
                            $hit['codigo'],
                            (float) $global
                        );
                    }
                    continue;
                }

                $this->upsertPrecioExcelFila(
                    $hit['empresa'],
                    $hit['card_code'],
                    $hit['card_name'] ?? '',
                    $hit['codigo'],
                    $hit['nombre'] ?? $hit['codigo'],
                    $hit['moneda'] ?? 'MXN',
                    (int) ($hit['mes'] ?? 0),
                    (float) $hit['precio'],
                    $anio,
                    $userId,
                    $hasAnio,
                    $hasCard,
                    $hasCardName,
                    $hasMes,
                    $creados,
                    $actualizados
                );
                if ((int) ($hit['mes'] ?? 0) === 0) {
                    $propagadasTot += $this->propagarCostoACiclosAbiertos(
                        $hit['empresa'],
                        $hit['codigo'],
                        (float) $hit['precio']
                    );
                }
            }
        });

        $total = $actualizados + $creados;

        return response()->json([
            'ok' => true,
            'actualizados' => $actualizados,
            'creados' => $creados,
            'omitidos' => $omitidos,
            'proyecciones_actualizadas' => $propagadasTot,
            'errores' => $errores,
            'message' => $total > 0
                ? ('Se aplicaron '.$total.' precio(s) desde Excel'
                    .($actualizados ? (' · '.$actualizados.' actualizado(s)') : '')
                    .($creados ? (' · '.$creados.' nuevo(s)') : '')
                    .($propagadasTot ? (' · '.$propagadasTot.' proyección(es) abiertas') : '')
                    .'.')
                : 'No se encontró ninguna fila con precio para aplicar.',
        ]);
    }

    /**
     * Upsert de una fila de precio (mes 0 = global, 1–12 = mes) desde Excel.
     */
    protected function upsertPrecioExcelFila(
        string $empresa,
        string $cardCode,
        string $cardName,
        string $codigo,
        string $nombre,
        string $moneda,
        int $mes,
        float $precio,
        int $anio,
        $userId,
        bool $hasAnio,
        bool $hasCard,
        bool $hasCardName,
        bool $hasMes,
        int &$creados,
        int &$actualizados
    ): void {
        $lookup = [
            'empresa' => $empresa,
            'producto_codigo' => $codigo,
        ];
        if ($hasAnio) {
            $lookup['anio'] = $anio;
        }
        if ($hasCard) {
            $lookup['card_code'] = $cardCode;
        }
        if ($hasMes) {
            $lookup['mes'] = $mes;
        }
        $row = PvProductoCosto::query()->firstOrNew($lookup);
        $esNuevo = ! $row->exists;
        $precioAnterior = $esNuevo ? null : (float) $row->costo_unitario;
        $monedaAnterior = $esNuevo ? null : (string) ($row->moneda ?: 'MXN');
        if ($nombre !== '') {
            $row->producto_nombre = $nombre;
        } elseif ($esNuevo) {
            $row->producto_nombre = $codigo;
        }
        if ($hasAnio) {
            $row->anio = $anio;
        }
        if ($hasCard) {
            $row->card_code = $cardCode;
        }
        if ($hasCardName && $cardName !== '') {
            $row->card_name = $cardName;
        }
        if ($hasMes) {
            $row->mes = $mes;
        }
        $row->costo_unitario = $precio;
        $row->moneda = $moneda !== '' ? $moneda : 'MXN';
        $row->updated_by = $userId;
        $row->save();
        $this->registrarHistorialPrecio(
            $empresa,
            $codigo,
            (string) $row->producto_nombre,
            $precioAnterior,
            $monedaAnterior,
            (float) $row->costo_unitario,
            (string) ($row->moneda ?: 'MXN'),
            'excel',
            $userId,
            $cardCode,
            $cardName,
            $mes,
            $anio
        );
        if ($esNuevo) {
            $creados++;
        } else {
            $actualizados++;
        }
    }

    /**
     * @param  array<int, mixed>  $header
     * @return array{empresa: ?int, card: ?int, cliente: ?int, item: ?int, producto: ?int, moneda: ?int, mes: ?int, precio: ?int, meses: array<int, int>}
     */
    protected function mapearEncabezadosPrecios(array $header): array
    {
        $map = [
            'empresa' => null,
            'card' => null,
            'cliente' => null,
            'item' => null,
            'producto' => null,
            'moneda' => null,
            'mes' => null,
            'precio' => null,
            'meses' => [],
        ];
        $mesAlias = [
            'ene' => 0, 'enero' => 0, 'jan' => 0, 'january' => 0, 'mes_01' => 0, 'mes01' => 0, 'm01' => 0, '1' => 0,
            'feb' => 1, 'febrero' => 1, 'february' => 1, 'mes_02' => 1, 'mes02' => 1, 'm02' => 1, '2' => 1,
            'mar' => 2, 'marzo' => 2, 'march' => 2, 'mes_03' => 2, 'mes03' => 2, 'm03' => 2, '3' => 2,
            'abr' => 3, 'abril' => 3, 'apr' => 3, 'april' => 3, 'mes_04' => 3, 'mes04' => 3, 'm04' => 3, '4' => 3,
            'may' => 4, 'mayo' => 4, 'mes_05' => 4, 'mes05' => 4, 'm05' => 4, '5' => 4,
            'jun' => 5, 'junio' => 5, 'june' => 5, 'mes_06' => 5, 'mes06' => 5, 'm06' => 5, '6' => 5,
            'jul' => 6, 'julio' => 6, 'july' => 6, 'mes_07' => 6, 'mes07' => 6, 'm07' => 6, '7' => 6,
            'ago' => 7, 'agosto' => 7, 'aug' => 7, 'august' => 7, 'mes_08' => 7, 'mes08' => 7, 'm08' => 7, '8' => 7,
            'sep' => 8, 'septiembre' => 8, 'sept' => 8, 'september' => 8, 'mes_09' => 8, 'mes09' => 8, 'm09' => 8, '9' => 8,
            'oct' => 9, 'octubre' => 9, 'october' => 9, 'mes_10' => 9, 'mes10' => 9, 'm10' => 9, '10' => 9,
            'nov' => 10, 'noviembre' => 10, 'november' => 10, 'mes_11' => 10, 'mes11' => 10, 'm11' => 10, '11' => 10,
            'dic' => 11, 'diciembre' => 11, 'dec' => 11, 'december' => 11, 'mes_12' => 11, 'mes12' => 11, 'm12' => 11, '12' => 11,
        ];

        foreach ($header as $idx => $raw) {
            $h = mb_strtolower(trim((string) $raw));
            $h = str_replace([' ', '_', '-'], '', $h);
            if ($h === '') {
                continue;
            }
            if (isset($aliasMes[$h])) {
                $map['meses'][$aliasMes[$h]] = (int) $idx;
            } elseif ($map['empresa'] === null && in_array($h, ['empresa', 'company', 'empr'], true)) {
                $map['empresa'] = (int) $idx;
                continue;
            }
            if ($map['card'] === null && in_array($h, ['cardcode', 'card_code', 'centrocosto', 'centro', 'cc'], true)) {
                $map['card'] = (int) $idx;
                continue;
            }
            if ($map['cliente'] === null && in_array($h, ['cliente', 'cardname', 'nombrecliente', 'customer'], true)) {
                $map['cliente'] = (int) $idx;
                continue;
            }
            if ($map['item'] === null && in_array($h, ['itemcode', 'item_code', 'producto_codigo', 'codigoproducto', 'sku'], true)) {
                $map['item'] = (int) $idx;
                continue;
            }
            if ($map['producto'] === null && in_array($h, ['producto', 'productonombre', 'descripcion', 'articulo'], true)) {
                $map['producto'] = (int) $idx;
                continue;
            }
            if ($map['moneda'] === null && in_array($h, ['moneda', 'currency', 'curr'], true)) {
                $map['moneda'] = (int) $idx;
                continue;
            }
            if ($map['mes'] === null && in_array($h, ['mes', 'month', 'periodo'], true)) {
                $map['mes'] = (int) $idx;
                continue;
            }
            if ($map['precio'] === null && in_array($h, [
                'precioglobal', 'precio', 'price', 'preciounitario', 'costo', 'costounitario', 'costo_unitario',
            ], true)) {
                $map['precio'] = (int) $idx;
                continue;
            }
            $mesKey = preg_replace('/[^a-z0-9]+/', '', $h);
            $mesKey = preg_replace('/\d{4}$/', '', (string) $mesKey);
            if (isset($mesAlias[$mesKey]) && ! isset($map['meses'][$mesAlias[$mesKey]])) {
                $map['meses'][$mesAlias[$mesKey]] = (int) $idx;
            }
        }

        // Compat: si no hay ItemCode pero sí Producto, úsalo como código.
        if ($map['item'] === null && $map['producto'] !== null) {
            $map['item'] = $map['producto'];
            $map['producto'] = null;
        }

        ksort($map['meses']);

        return $map;
    }

    /** @param  mixed  $raw */
    protected function celdaNumeroPrecio($raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_numeric($raw)) {
            return (float) $raw;
        }
        $txt = trim((string) $raw);
        if ($txt === '') {
            return null;
        }
        $txt = str_replace(['$', 'US$', ' ', ','], ['', '', '', ''], $txt);
        if (! is_numeric($txt)) {
            return null;
        }

        return (float) $txt;
    }

    /**
     * Carga precios mensuales (Ene–Dic) desde AutinApi /precios-mensuales.
     * No actualiza el precio global (mes=0). Solo productos ya en el maestro.
     * Con preview=true solo consulta y lista afectados (no escribe BD).
     */
    public function importarCostosDesdeApi(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tbl_pv_productos_costo')) {
            return response()->json(['message' => 'Falta ejecutar la migración de costos de productos.'], 422);
        }

        $data = $request->validate([
            'empresa' => 'nullable|string|max:40',
            'anio' => 'nullable|integer|min:2000|max:2100',
            'anio_proyeccion' => 'nullable|integer|min:2000|max:2100',
            'solo_vacios' => 'nullable|boolean',
            'todas_empresas' => 'nullable|boolean',
            'preview' => 'nullable|boolean',
        ]);

        $preview = ! empty($data['preview']);
        $todasEmpresas = ! array_key_exists('todas_empresas', $data) || (bool) $data['todas_empresas'];
        $empresaFiltro = $todasEmpresas ? '' : strtoupper(trim((string) ($data['empresa'] ?? '')));
        $anioApi = (int) ($data['anio'] ?? date('Y'));
        $anioProy = $this->anioProyeccionCostos((int) ($data['anio_proyeccion'] ?? 0));
        $soloVacios = ! array_key_exists('solo_vacios', $data) || (bool) $data['solo_vacios'];

        $empresas = $empresaFiltro !== ''
            ? [$empresaFiltro]
            : ['AUSTIN', 'IMSA', 'PITIC', 'SYDNEY'];

        @set_time_limit(300);

        $api = app(AutinApiClient::class);
        $importados = 0;
        $sinDato = 0;
        $propagadasTot = 0;
        $userId = optional($request->user())->id;
        $detalle = [];
        $porEmpresaOk = [];
        $erroresEmp = [];
        $hasCard = Schema::hasColumn('tbl_pv_productos_costo', 'card_code');
        $hasMes = Schema::hasColumn('tbl_pv_productos_costo', 'mes');
        $hasAnio = $this->hasAnioCostos();
        $productosTocados = [];
        /** @var array<string, array<string, mixed>> $afectados */
        $afectados = [];
        $nuevos = 0;
        $actualizaciones = 0;
        $omitidosNoEnMaestro = 0;

        // Solo actualizar productos que ya existen en el maestro de esta proyección.
        $maestroKeys = [];
        $maestroQ = PvProductoCosto::query();
        if ($hasAnio) {
            $maestroQ->where('anio', $anioProy);
        }
        if ($empresaFiltro !== '') {
            $maestroQ->whereRaw('UPPER(empresa) = ?', [$empresaFiltro]);
        } else {
            $maestroQ->whereIn(DB::raw('UPPER(empresa)'), $empresas);
        }
        $maestroQ->orderBy('id')->chunk(1000, function ($chunk) use (&$maestroKeys, $hasCard) {
            foreach ($chunk as $r) {
                $empK = strtoupper(trim((string) $r->empresa));
                $cardK = $hasCard ? trim((string) ($r->card_code ?? '')) : '';
                $itemK = trim((string) $r->producto_codigo);
                if ($empK === '' || $itemK === '') {
                    continue;
                }
                $maestroKeys[$empK.'|'.$cardK.'|'.$itemK] = true;
                // También sin CardCode por si el API/local difieren en cliente vacío.
                $maestroKeys[$empK.'||'.$itemK] = true;
            }
        });

        foreach ($empresas as $empresa) {
            $page = 1;
            $lastPage = 1;
            $maxPages = 15;
            do {
                try {
                    $res = $api->preciosMensuales([
                        'year' => $anioApi,
                        'Empresa' => $empresa,
                        'per_page' => 500,
                        'page' => $page,
                    ]);
                } catch (Throwable $e) {
                    $erroresEmp[$empresa] = $e->getMessage();
                    break;
                }
                if (empty($res['ok'])) {
                    $erroresEmp[$empresa] = $res['message'] ?? 'Sin conexión a precios-mensuales';
                    break;
                }
                $body = is_array($res['body'] ?? null) ? $res['body'] : [];
                $rows = is_array($body['data'] ?? null) ? $body['data'] : [];
                $meta = is_array($body['meta'] ?? null) ? $body['meta'] : [];
                $lastPage = max(1, (int) ($meta['last_page'] ?? 1));

                foreach ($rows as $apiRow) {
                    if (! is_array($apiRow)) {
                        continue;
                    }
                    $emp = strtoupper(trim((string) ($apiRow['Empresa'] ?? $empresa)));
                    $card = trim((string) ($apiRow['CardCode'] ?? ''));
                    $cardName = trim((string) ($apiRow['CardName'] ?? ''));
                    $item = trim((string) ($apiRow['ItemCode'] ?? ''));
                    $itemName = trim((string) ($apiRow['ItemName'] ?? $apiRow['Dscription'] ?? $apiRow['Descripcion'] ?? ''));
                    $mes = (int) ($apiRow['Mes'] ?? 0);
                    // Solo meses 1–12: no tocar precio global (mes=0).
                    if ($mes < 1 || $mes > 12) {
                        continue;
                    }
                    $precio = $this->numeroVenta($apiRow, ['Precio', 'Price', 'precio']);
                    if ($emp === '' || $item === '' || $precio <= 0) {
                        $sinDato++;
                        continue;
                    }
                    $monedaApi = strtoupper(trim((string) (
                        $apiRow['Moneda']
                        ?? $apiRow['Currency']
                        ?? $apiRow['DocCur']
                        ?? $apiRow['CurrencyCode']
                        ?? ''
                    )));
                    if (! in_array($monedaApi, ['MXN', 'USD'], true)) {
                        $monedaApi = '';
                    }

                    $lookup = [
                        'empresa' => $emp,
                        'producto_codigo' => $item,
                    ];
                    if ($hasAnio) {
                        $lookup['anio'] = $anioProy;
                    }
                    if ($hasCard) {
                        $lookup['card_code'] = $card;
                    }
                    if ($hasMes) {
                        $lookup['mes'] = $mes;
                    }

                    // Solo productos ya presentes en el maestro de la proyección.
                    $keyProd = $emp.'|'.$card.'|'.$item;
                    $keyProdSinCard = $emp.'||'.$item;
                    if (empty($maestroKeys[$keyProd]) && empty($maestroKeys[$keyProdSinCard])) {
                        $omitidosNoEnMaestro++;
                        continue;
                    }

                    $row = PvProductoCosto::query()->firstOrNew($lookup);
                    if ($soloVacios && $row->exists && (float) $row->costo_unitario > 0) {
                        continue;
                    }

                    $akey = $emp.'|'.$card.'|'.$item;
                    if (! isset($afectados[$akey])) {
                        $afectados[$akey] = [
                            'empresa' => $emp,
                            'card_code' => $card,
                            'cliente' => $cardName !== '' ? $cardName : (string) ($row->card_name ?? ''),
                            'item_code' => $item,
                            'producto' => $itemName !== '' ? $itemName : (string) ($row->producto_nombre ?: $item),
                            'meses' => [],
                            'precio' => round($precio, 4),
                            'moneda' => $monedaApi !== '' ? $monedaApi : (string) ($row->moneda ?: 'MXN'),
                            'es_nuevo' => false,
                        ];
                    }
                    if ($mes >= 1 && $mes <= 12 && ! in_array($mes, $afectados[$akey]['meses'], true)) {
                        $afectados[$akey]['meses'][] = $mes;
                    }
                    $afectados[$akey]['precio'] = round($precio, 4);
                    if ($monedaApi !== '') {
                        $afectados[$akey]['moneda'] = $monedaApi;
                    }
                    if ($itemName !== '' && strcasecmp($itemName, $item) !== 0) {
                        $afectados[$akey]['producto'] = mb_substr($itemName, 0, 180);
                    }
                    if ($cardName !== '' && ($afectados[$akey]['cliente'] ?? '') === '') {
                        $afectados[$akey]['cliente'] = $cardName;
                    }

                    if ($preview) {
                        $importados++;
                        $detalle[] = $emp.'|'.$card.'|'.$item.'|'.$mes;
                        $porEmpresaOk[$emp] = ($porEmpresaOk[$emp] ?? 0) + 1;
                        $productosTocados[$emp.'|'.$item] = true;
                        continue;
                    }

                    $precioAnterior = $row->exists ? (float) $row->costo_unitario : null;
                    $monedaAnterior = $row->exists ? (string) ($row->moneda ?: 'MXN') : null;
                    if ($hasAnio) {
                        $row->anio = $anioProy;
                    }
                    if ($hasCard) {
                        $row->card_code = $card;
                        if ($cardName !== '') {
                            $row->card_name = $cardName;
                        }
                    }
                    if ($hasMes) {
                        $row->mes = $mes;
                    }
                    if ($itemName === '' || strcasecmp($itemName, $item) === 0) {
                        if (! isset($nombresLocales)) {
                            $nombresLocales = $this->mapaNombresProductosLocal();
                        }
                        $itemName = (string) ($nombresLocales[$item] ?? '');
                    }
                    if ($itemName !== '' && strcasecmp($itemName, $item) !== 0) {
                        $row->producto_nombre = mb_substr($itemName, 0, 180);
                    } elseif (! $row->producto_nombre) {
                        $row->producto_nombre = $item;
                    }
                    $row->costo_unitario = round($precio, 4);
                    if ($monedaApi !== '') {
                        $row->moneda = $monedaApi;
                    } elseif (! $row->moneda) {
                        $row->moneda = 'MXN';
                    }
                    $row->updated_by = $userId;
                    $row->save();
                    $this->registrarHistorialPrecio(
                        $emp,
                        $item,
                        (string) $row->producto_nombre,
                        $precioAnterior,
                        $monedaAnterior,
                        (float) $row->costo_unitario,
                        (string) ($row->moneda ?: 'MXN'),
                        'api',
                        $userId,
                        $card,
                        $cardName,
                        $mes,
                        $anioProy
                    );
                    $importados++;
                    $detalle[] = $emp.'|'.$card.'|'.$item.'|'.$mes;
                    $porEmpresaOk[$emp] = ($porEmpresaOk[$emp] ?? 0) + 1;
                    $productosTocados[$emp.'|'.$item] = true;
                }
                $page++;
            } while ($page <= $lastPage && $page <= $maxPages);
        }

        foreach ($afectados as &$hitRef) {
            if (! empty($hitRef['es_nuevo'])) {
                $nuevos++;
            } else {
                $actualizaciones++;
            }
            if (! empty($hitRef['meses']) && is_array($hitRef['meses'])) {
                sort($hitRef['meses']);
            }
        }
        unset($hitRef);

        $listaAfectados = array_values($afectados);
        usort($listaAfectados, function ($a, $b) {
            return [$a['empresa'], $a['card_code'], $a['item_code']]
                <=> [$b['empresa'], $b['card_code'], $b['item_code']];
        });

        if ($preview) {
            $errTxt = $erroresEmp
                ? (' · avisos: '.collect($erroresEmp)->map(function ($m, $e) {
                    return $e.' ('.$m.')';
                })->implode('; '))
                : '';

            return response()->json([
                'ok' => true,
                'preview' => true,
                'anio' => $anioApi,
                'anio_proyeccion' => $anioProy,
                'precios_filas' => $importados,
                'productos_unicos' => count($listaAfectados),
                'nuevos' => 0,
                'actualizaciones' => count($listaAfectados),
                'omitidos_no_en_maestro' => $omitidosNoEnMaestro,
                'solo_existentes' => true,
                'solo_meses' => true,
                'sin_dato_api' => $sinDato,
                'empresas' => $porEmpresaOk,
                'errores_empresa' => (object) $erroresEmp,
                'afectados' => array_slice($listaAfectados, 0, 150),
                'afectados_total' => count($listaAfectados),
                'message' => count($listaAfectados) > 0
                    ? ('Se actualizarían meses (Ene–Dic) de '.count($listaAfectados).' producto(s) en proyección '.$anioProy
                        .' ('.$importados.' precio(s) mensuales API '.$anioApi.'; el precio global no se toca)'
                        .($omitidosNoEnMaestro ? (' · '.$omitidosNoEnMaestro.' omitido(s) fuera de tu tabla') : '')
                        .$errTxt.'.')
                    : ('Ningún mes de tu maestro coincide con precios de la API para proyección '.$anioProy
                        .($omitidosNoEnMaestro ? (' ('.$omitidosNoEnMaestro.' en API fuera de tu tabla)') : '')
                        .$errTxt.'.'),
            ]);
        }

        // No propagar a proyecciones: este import solo escribe meses 1–12, no el global.

        $empresasTxt = $porEmpresaOk
            ? collect($porEmpresaOk)->map(function ($n, $e) {
                return $e.': '.$n;
            })->implode(', ')
            : '';
        $errTxt = $erroresEmp
            ? (' · avisos: '.collect($erroresEmp)->map(function ($m, $e) {
                return $e.' ('.$m.')';
            })->implode('; '))
            : '';

        return response()->json([
            'ok' => true,
            'preview' => false,
            'anio' => $anioApi,
            'anio_proyeccion' => $anioProy,
            'importados' => $importados,
            'sin_dato_api' => $sinDato,
            'omitidos_no_en_maestro' => $omitidosNoEnMaestro,
            'solo_existentes' => true,
            'solo_meses' => true,
            'proyecciones_actualizadas' => 0,
            'empresas' => $porEmpresaOk,
            'errores_empresa' => (object) $erroresEmp,
            'productos_unicos' => count($listaAfectados),
            'afectados' => array_slice($listaAfectados, 0, 150),
            'message' => $importados > 0
                ? ('Se actualizaron '.$importados.' precio(s) mensuales (Ene–Dic) API '.$anioApi.
                    ' → proyección '.$anioProy.' (precio global sin cambios)'.
                    ($empresasTxt ? (' · '.$empresasTxt) : '').
                    ($omitidosNoEnMaestro ? (' · '.$omitidosNoEnMaestro.' omitido(s) no estaban en tu tabla') : '').
                    $errTxt.'.')
                : ('No se actualizó ningún mes de tu maestro con la API '.$anioApi
                    .($omitidosNoEnMaestro ? (' ('.$omitidosNoEnMaestro.' en API fuera de tu tabla)') : '')
                    .$errTxt.'.'),
            'productos' => array_slice($detalle, 0, 200),
        ]);
    }

    /**
     * Actualiza solo el precio global (mes=0) desde AutinApi /listas-precios
     * (OCRD → OPLN → ITM1). Solo productos ya presentes en el maestro de la proyección.
     * Con preview=true lista afectados sin escribir.
     */
    public function importarCostosDesdeListaPrecios(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tbl_pv_productos_costo')) {
            return response()->json(['message' => 'Falta ejecutar la migración de costos de productos.'], 422);
        }

        $data = $request->validate([
            'empresa' => 'nullable|string|max:40',
            'anio_proyeccion' => 'nullable|integer|min:2000|max:2100',
            'todas_empresas' => 'nullable|boolean',
            'preview' => 'nullable|boolean',
        ]);

        $preview = ! empty($data['preview']);
        $todasEmpresas = ! array_key_exists('todas_empresas', $data) || (bool) $data['todas_empresas'];
        $empresaFiltro = $todasEmpresas ? '' : strtoupper(trim((string) ($data['empresa'] ?? '')));
        $anioProy = $this->anioProyeccionCostos((int) ($data['anio_proyeccion'] ?? 0));

        $empresas = $empresaFiltro !== ''
            ? [$empresaFiltro]
            : ['AUSTIN', 'IMSA', 'PITIC', 'SYDNEY'];

        @set_time_limit(300);

        $hasCard = Schema::hasColumn('tbl_pv_productos_costo', 'card_code');
        $hasCardName = Schema::hasColumn('tbl_pv_productos_costo', 'card_name');
        $hasMes = Schema::hasColumn('tbl_pv_productos_costo', 'mes');
        $hasAnio = $this->hasAnioCostos();
        $userId = optional($request->user())->id;

        // Productos del maestro (proyección) indexados por empresa|card|item.
        /** @var array<string, array<string, mixed>> $maestro */
        $maestro = [];
        /** @var array<string, true> $clientes */
        $clientes = [];
        $q = PvProductoCosto::query();
        if ($hasAnio) {
            $q->where('anio', $anioProy);
        }
        if ($empresaFiltro !== '') {
            $q->whereRaw('UPPER(empresa) = ?', [$empresaFiltro]);
        } else {
            $q->whereIn(DB::raw('UPPER(empresa)'), $empresas);
        }
        $q->orderBy('id')->chunk(1000, function ($chunk) use (&$maestro, &$clientes, $hasCard, $hasMes) {
            foreach ($chunk as $r) {
                $emp = strtoupper(trim((string) $r->empresa));
                $card = $hasCard ? trim((string) ($r->card_code ?? '')) : '';
                $item = trim((string) $r->producto_codigo);
                if ($emp === '' || $item === '') {
                    continue;
                }
                $key = $emp.'|'.$card.'|'.$item;
                if (! isset($maestro[$key])) {
                    $maestro[$key] = [
                        'empresa' => $emp,
                        'card_code' => $card,
                        'cliente' => (string) ($r->card_name ?? ''),
                        'item_code' => $item,
                        'producto' => (string) ($r->producto_nombre ?: $item),
                        'moneda' => (string) ($r->moneda ?: 'MXN'),
                        'precio_actual' => (float) $r->costo_unitario,
                    ];
                }
                // Preferir precio de fila global (mes=0) si existe.
                if ($hasMes && (int) ($r->mes ?? -1) === 0) {
                    $maestro[$key]['precio_actual'] = (float) $r->costo_unitario;
                    if ($r->moneda) {
                        $maestro[$key]['moneda'] = (string) $r->moneda;
                    }
                }
                $clientes[$emp.'|'.$card] = true;
            }
        });

        if (! $maestro) {
            return response()->json([
                'ok' => true,
                'preview' => $preview,
                'anio_proyeccion' => $anioProy,
                'productos_unicos' => 0,
                'afectados' => [],
                'afectados_total' => 0,
                'omitidos_sin_lista' => 0,
                'message' => 'Tu maestro de proyección '.$anioProy.' no tiene productos para actualizar.',
            ]);
        }

        $importados = 0;
        $propagadasTot = 0;
        $omitidosSinLista = 0;
        $erroresCli = [];
        /** @var array<string, array<string, mixed>> $afectados */
        $afectados = [];

        foreach (array_keys($clientes) as $ck) {
            [$emp, $card] = array_pad(explode('|', $ck, 2), 2, '');
            if ($emp === '' || $card === '') {
                // Sin CardCode no se puede consultar lista del cliente.
                continue;
            }
            try {
                $pack = $this->cargarListasPreciosCliente($emp, $card, 0);
            } catch (Throwable $e) {
                $erroresCli[$ck] = $e->getMessage();
                continue;
            }
            if (empty($pack['ok'])) {
                $erroresCli[$ck] = $pack['mensaje'] ?? 'Sin lista de precios';
                continue;
            }
            $porArticulo = is_array($pack['por_articulo'] ?? null) ? $pack['por_articulo'] : [];
            if (! $porArticulo) {
                continue;
            }

            // Productos del maestro de este cliente.
            foreach ($maestro as $mkey => $local) {
                if ($local['empresa'] !== $emp || $local['card_code'] !== $card) {
                    continue;
                }
                $item = $local['item_code'];
                $lista = $porArticulo[$item]
                    ?? $porArticulo[strtoupper($item)]
                    ?? $porArticulo[$this->codigoCuentaKey($item)]
                    ?? null;
                if (! is_array($lista)) {
                    $omitidosSinLista++;
                    continue;
                }
                $precio = round((float) ($lista['precio'] ?? 0), 4);
                if ($precio <= 0) {
                    $omitidosSinLista++;
                    continue;
                }
                $moneda = strtoupper(trim((string) ($lista['moneda'] ?? $local['moneda'] ?? 'MXN'))) ?: 'MXN';
                if (! in_array($moneda, ['MXN', 'USD'], true)) {
                    $moneda = 'MXN';
                }
                $nombreLista = trim((string) ($lista['nombre'] ?? ''));
                $listaNom = trim((string) ($lista['lista'] ?? ''));
                $noLista = trim((string) ($lista['no_lista'] ?? ''));

                $afectados[$mkey] = [
                    'empresa' => $emp,
                    'card_code' => $card,
                    'cliente' => $local['cliente'],
                    'item_code' => $item,
                    'producto' => $nombreLista !== '' ? $nombreLista : $local['producto'],
                    'precio' => $precio,
                    'precio_anterior' => round((float) $local['precio_actual'], 4),
                    'moneda' => $moneda,
                    'lista' => $listaNom,
                    'no_lista' => $noLista,
                    'meses' => [0],
                    'es_nuevo' => false,
                ];

                if ($preview) {
                    $importados++;
                    continue;
                }

                $lookup = [
                    'empresa' => $emp,
                    'producto_codigo' => $item,
                ];
                if ($hasAnio) {
                    $lookup['anio'] = $anioProy;
                }
                if ($hasCard) {
                    $lookup['card_code'] = $card;
                }
                if ($hasMes) {
                    $lookup['mes'] = 0;
                }

                $row = PvProductoCosto::query()->firstOrNew($lookup);
                $esNuevo = ! $row->exists;
                $precioAnterior = $esNuevo ? null : (float) $row->costo_unitario;
                $monedaAnterior = $esNuevo ? null : (string) ($row->moneda ?: 'MXN');

                if ($hasAnio) {
                    $row->anio = $anioProy;
                }
                if ($hasCard) {
                    $row->card_code = $card;
                }
                if ($hasCardName && $local['cliente'] !== '') {
                    $row->card_name = $local['cliente'];
                }
                if ($hasMes) {
                    $row->mes = 0;
                }
                $nombre = $nombreLista !== '' ? $nombreLista : $local['producto'];
                if ($nombre !== '') {
                    $row->producto_nombre = mb_substr($nombre, 0, 180);
                } elseif (! $row->producto_nombre) {
                    $row->producto_nombre = $item;
                }
                $row->costo_unitario = $precio;
                $row->moneda = $moneda;
                $row->updated_by = $userId;
                $row->save();

                $this->registrarHistorialPrecio(
                    $emp,
                    $item,
                    (string) $row->producto_nombre,
                    $precioAnterior,
                    $monedaAnterior,
                    $precio,
                    $moneda,
                    'lista_precios',
                    $userId,
                    $card,
                    (string) ($local['cliente'] ?? ''),
                    0,
                    $anioProy
                );
                $propagadasTot += $this->propagarCostoACiclosAbiertos($emp, $item, $precio);
                $importados++;
            }
        }

        $listaAfectados = array_values($afectados);
        usort($listaAfectados, function ($a, $b) {
            return [$a['empresa'], $a['card_code'], $a['item_code']]
                <=> [$b['empresa'], $b['card_code'], $b['item_code']];
        });

        $errTxt = $erroresCli
            ? (' · avisos: '.collect($erroresCli)->map(function ($m, $k) {
                return $k.' ('.$m.')';
            })->implode('; '))
            : '';

        return response()->json([
            'ok' => true,
            'preview' => $preview,
            'anio_proyeccion' => $anioProy,
            'origen' => 'listas-precios',
            'solo_global' => true,
            'importados' => $importados,
            'productos_unicos' => count($listaAfectados),
            'afectados' => array_slice($listaAfectados, 0, 150),
            'afectados_total' => count($listaAfectados),
            'omitidos_sin_lista' => $omitidosSinLista,
            'errores_cliente' => (object) $erroresCli,
            'proyecciones_actualizadas' => $preview ? 0 : $propagadasTot,
            'message' => count($listaAfectados) > 0
                ? (($preview ? 'Se actualizaría' : 'Se actualizó').' el precio global de '
                    .count($listaAfectados).' producto(s) desde lista de precios → proyección '.$anioProy
                    .($omitidosSinLista ? (' · '.$omitidosSinLista.' sin precio en lista') : '')
                    .(! $preview && $propagadasTot ? (' · '.$propagadasTot.' proyección(es) abiertas') : '')
                    .$errTxt.'.')
                : ('Ningún producto de tu maestro tiene precio en la lista SAP de su cliente'
                    .($omitidosSinLista ? (' ('.$omitidosSinLista.' sin match)') : '')
                    .$errTxt.'.'),
        ]);
    }

    /**
     * Precio unitario de venta por producto desde AutinApi /ventas (año).
     * Preferencia: Price de línea ponderado por uds; si no, importe÷uds (USD si hay).
     * No usa costo de inventario SAP.
     *
     * @return array<string, array{costo: float, moneda: string, nombre: string}>
     */
    protected function costosDesdeVentasApi(string $empresa, int $anio): array
    {
        $pack = $this->filasVentasEmpresa(strtolower($empresa), $anio, [
            'fecha_desde' => $anio.'/01/01',
            'fecha_hasta' => $anio.'/12/31',
        ]);
        if (empty($pack['ok']) || empty($pack['rows'])) {
            return [];
        }

        $agg = [];
        foreach ($pack['rows'] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $codigo = trim((string) ($row['ItemCode'] ?? $row['Itemcode'] ?? ''));
            if ($codigo === '') {
                continue;
            }
            $key = $this->codigoCuentaKey($codigo);
            $qty = abs($this->cantidadVenta($row));
            $importe = abs($this->importeVenta($row, ['LineTotal', 'linetotal', 'GTotal']));
            $importeUsd = abs($this->importeVenta($row, ['LineTotalUSD', 'LineTotalUsd', 'LineTotalFC', 'TotalFrgn']));
            $price = abs($this->numeroVenta($row, ['Price', 'Precio', 'UnitPrice', 'PriceBefDi']));
            $nombre = trim((string) ($row['ItemName'] ?? $row['Dscription'] ?? ''));

            if (! isset($agg[$key])) {
                $agg[$key] = [
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                    'qty' => 0.0,
                    'mxn' => 0.0,
                    'usd' => 0.0,
                    'price_w' => 0.0,
                    'price_q' => 0.0,
                    'price_usd_w' => 0.0,
                    'price_usd_q' => 0.0,
                ];
            }
            if ($nombre !== '' && $agg[$key]['nombre'] === '') {
                $agg[$key]['nombre'] = $nombre;
            }
            $agg[$key]['qty'] += $qty;
            $agg[$key]['mxn'] += $importe;
            $agg[$key]['usd'] += $importeUsd;

            // Precio de línea: si hay LineTotalUSD, el Price suele ir en moneda documento;
            // preferimos unitario en USD = LineTotalUSD/qty cuando aplica.
            if ($qty > 0.0001) {
                if ($importeUsd > 0.0001) {
                    $unitUsd = $importeUsd / $qty;
                    $agg[$key]['price_usd_w'] += $unitUsd * $qty;
                    $agg[$key]['price_usd_q'] += $qty;
                }
                $unitDoc = $price > 0 ? $price : ($importe > 0.0001 ? ($importe / $qty) : 0.0);
                if ($unitDoc > 0) {
                    $agg[$key]['price_w'] += $unitDoc * $qty;
                    $agg[$key]['price_q'] += $qty;
                }
            }
        }

        $out = [];
        foreach ($agg as $key => $a) {
            $precio = 0.0;
            $moneda = 'MXN';
            if ($a['price_usd_q'] > 0) {
                $precio = $a['price_usd_w'] / $a['price_usd_q'];
                $moneda = 'USD';
            } elseif ($a['price_q'] > 0) {
                $precio = $a['price_w'] / $a['price_q'];
                $moneda = 'MXN';
            } elseif ($a['qty'] > 0 && $a['usd'] > 0) {
                $precio = $a['usd'] / $a['qty'];
                $moneda = 'USD';
            } elseif ($a['qty'] > 0 && $a['mxn'] > 0) {
                $precio = $a['mxn'] / $a['qty'];
                $moneda = 'MXN';
            }
            if ($precio <= 0) {
                continue;
            }
            $entry = [
                'costo' => round($precio, 4),
                'moneda' => $moneda,
                'nombre' => $a['nombre'],
            ];
            $out[$key] = $entry;
            $out[strtoupper($a['codigo'])] = $entry;
            $out[$a['codigo']] = $entry;
        }

        return $out;
    }

    /**
     * Si el producto aún no está en el maestro local, lo crea una sola vez con costo y moneda.
     * No sobrescribe costos ya guardados.
     *
     * @param  array<string, array<string, mixed>>  $porCuenta
     */
    protected function asegurarCostosMaestroDesdePorCuenta(string $empresa, array $porCuenta): void
    {
        if (! Schema::hasTable('tbl_pv_productos_costo') || ! $porCuenta) {
            return;
        }

        $empresa = strtoupper(trim($empresa));
        $userId = optional(auth()->user())->id;

        foreach ($porCuenta as $item) {
            if (! is_array($item)) {
                continue;
            }
            $codigo = trim((string) ($item['codigo'] ?? ''));
            $costo = (float) ($item['costo'] ?? 0);
            if ($codigo === '' || $costo <= 0) {
                continue;
            }
            $moneda = strtoupper(trim((string) ($item['costo_moneda'] ?? 'MXN'))) ?: 'MXN';
            if (! in_array($moneda, ['MXN', 'USD'], true)) {
                $moneda = 'MXN';
            }
            $this->asegurarCostoMaestroSiNoExiste(
                $empresa,
                $codigo,
                (string) ($item['nombre'] ?? ''),
                $costo,
                $moneda,
                $userId,
                $this->anioProyeccionCostos()
            );
        }
    }

    /**
     * Mapa ItemCode => nombre/descripción desde proyecciones y asignaciones locales.
     *
     * @return array<string, string>
     */
    protected function mapaNombresProductosLocal(): array
    {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }

        $map = [];
        $add = function (string $codigo, ?string $nombre) use (&$map) {
            $codigo = trim($codigo);
            $nombre = trim((string) $nombre);
            if ($codigo === '' || $nombre === '' || strcasecmp($nombre, $codigo) === 0) {
                return;
            }
            if (! isset($map[$codigo]) || mb_strlen($nombre) > mb_strlen($map[$codigo])) {
                $map[$codigo] = mb_substr($nombre, 0, 180);
            }
        };

        if (Schema::hasTable('tbl_pv_proyecciones')) {
            foreach (DB::table('tbl_pv_proyecciones')->select('producto_codigo', 'producto_nombre')->cursor() as $row) {
                $add((string) $row->producto_codigo, (string) $row->producto_nombre);
            }
        }
        if (Schema::hasTable('tbl_pv_asignacion_productos')) {
            foreach (DB::table('tbl_pv_asignacion_productos')->select('producto_codigo', 'producto_nombre')->cursor() as $row) {
                $add((string) $row->producto_codigo, (string) $row->producto_nombre);
            }
        }

        $cache = $map;

        return $cache;
    }

    /**
     * Alta única en tbl_pv_productos_costo. Devuelve true si se creó.
     */
    protected function asegurarCostoMaestroSiNoExiste(
        string $empresa,
        string $productoCodigo,
        string $productoNombre,
        float $costo,
        string $moneda = 'MXN',
        $userId = null,
        ?int $anio = null
    ): bool {
        if (! Schema::hasTable('tbl_pv_productos_costo') || $costo <= 0) {
            return false;
        }

        $empresa = strtoupper(trim($empresa));
        $productoCodigo = trim($productoCodigo);
        if ($empresa === '' || $productoCodigo === '') {
            return false;
        }
        $anio = $this->anioProyeccionCostos($anio);

        $existsQ = PvProductoCosto::query()
            ->whereRaw('UPPER(empresa) = ?', [$empresa])
            ->where('producto_codigo', $productoCodigo);
        if ($this->hasAnioCostos()) {
            $existsQ->where('anio', $anio);
        }
        if ($existsQ->exists()) {
            return false;
        }

        $moneda = strtoupper(trim($moneda)) ?: 'MXN';
        if (! in_array($moneda, ['MXN', 'USD'], true)) {
            $moneda = 'MXN';
        }

        $payload = [
            'empresa' => $empresa,
            'producto_codigo' => $productoCodigo,
            'producto_nombre' => $productoNombre !== '' ? $productoNombre : $productoCodigo,
            'costo_unitario' => round($costo, 4),
            'moneda' => $moneda,
            'updated_by' => $userId,
        ];
        if ($this->hasAnioCostos()) {
            $payload['anio'] = $anio;
        }
        if (Schema::hasColumn('tbl_pv_productos_costo', 'card_code')) {
            $payload['card_code'] = '';
        }
        if (Schema::hasColumn('tbl_pv_productos_costo', 'mes')) {
            $payload['mes'] = 0;
        }
        PvProductoCosto::query()->create($payload);

        return true;
    }

    /**
     * Propaga costo maestro solo a proyecciones de ciclos abiertos (histórico intacto).
     */
    /**
     * Precio global del maestro para un producto: mes=0 si existe; si no, moda de meses 1–12.
     */
    protected function precioGlobalMaestroProducto(
        string $empresa,
        string $productoCodigo,
        string $cardCode = '',
        ?int $anio = null
    ): ?float {
        if (! Schema::hasTable('tbl_pv_productos_costo')) {
            return null;
        }
        $anio = $this->anioProyeccionCostos($anio);
        $q = PvProductoCosto::query()
            ->whereRaw('UPPER(empresa) = ?', [strtoupper(trim($empresa))])
            ->where('producto_codigo', trim($productoCodigo));
        if ($this->hasAnioCostos()) {
            $q->where('anio', $anio);
        }
        if (Schema::hasColumn('tbl_pv_productos_costo', 'card_code') && trim($cardCode) !== '') {
            $q->where('card_code', trim($cardCode));
        }
        $hasMes = Schema::hasColumn('tbl_pv_productos_costo', 'mes');
        $rows = $q->orderByDesc('updated_at')->orderByDesc('id')->get();
        $global = null;
        $freq = [];
        foreach ($rows as $row) {
            $precio = (float) $row->costo_unitario;
            if ($precio <= 0) {
                continue;
            }
            $mes = $hasMes ? (int) ($row->mes ?? 0) : 0;
            if ($mes === 0) {
                if ($global === null) {
                    $global = $precio;
                }

                continue;
            }
            if ($mes >= 1 && $mes <= 12) {
                $keyP = number_format($precio, 4, '.', '');
                $freq[$keyP] = ($freq[$keyP] ?? 0) + 1;
            }
        }
        if ($global !== null && $global > 0) {
            return round($global, 4);
        }
        if ($freq) {
            arsort($freq);
            $modeKey = (string) array_key_first($freq);

            return round((float) $modeKey, 4);
        }

        return null;
    }

    protected function propagarCostoACiclosAbiertos(string $empresa, string $productoCodigo, float $costo): int
    {
        if (! Schema::hasTable('tbl_pv_proyecciones') || ! Schema::hasTable('tbl_pv_ciclos')) {
            return 0;
        }
        if ($costo <= 0) {
            return 0;
        }

        $abiertos = PvCiclo::query()
            ->get(['codigo', 'estado'])
            ->filter(function (PvCiclo $c) {
                return $this->normalizeCicloEstado($c->estado) === 'abierto';
            })
            ->map(function (PvCiclo $c) {
                return strtoupper(trim((string) $c->codigo));
            })
            ->values()
            ->all();

        if (! $abiertos) {
            return 0;
        }

        return (int) PvPresupuesto::query()
            ->whereRaw('UPPER(empresa) = ?', [strtoupper($empresa)])
            ->where('producto_codigo', $productoCodigo)
            ->where(function ($q) use ($abiertos) {
                foreach ($abiertos as $cod) {
                    $q->orWhereRaw('UPPER(ciclo_codigo) = ?', [$cod]);
                }
            })
            ->update([
                'costo_unitario' => $costo,
                'updated_at' => now(),
            ]);
    }

    /**
     * Año de proyección (anio_presupuesto) para el maestro de precios.
     */
    protected function anioProyeccionCostos(?int $anio = null, ?string $cicloCodigo = null): int
    {
        $a = (int) ($anio ?? 0);
        if ($a >= 2000 && $a <= 2100) {
            return $a;
        }
        if ($cicloCodigo && Schema::hasTable('tbl_pv_ciclos')) {
            $fromCiclo = (int) (PvCiclo::query()
                ->whereRaw('UPPER(codigo) = ?', [strtoupper(trim($cicloCodigo))])
                ->value('anio_presupuesto') ?: 0);
            if ($fromCiclo >= 2000 && $fromCiclo <= 2100) {
                return $fromCiclo;
            }
        }
        if (Schema::hasTable('tbl_pv_ciclos')) {
            $fromOpen = (int) (PvCiclo::query()
                ->where('estado', 'abierto')
                ->orderByDesc('id')
                ->value('anio_presupuesto') ?: 0);
            if ($fromOpen >= 2000 && $fromOpen <= 2100) {
                return $fromOpen;
            }
            $fromAny = (int) (PvCiclo::query()->orderByDesc('anio_presupuesto')->orderByDesc('id')
                ->value('anio_presupuesto') ?: 0);
            if ($fromAny >= 2000 && $fromAny <= 2100) {
                return $fromAny;
            }
        }

        return 2027;
    }

    protected function hasAnioCostos(): bool
    {
        return Schema::hasColumn('tbl_pv_productos_costo', 'anio');
    }

    /**
     * Maestro local de precios (1 valor “global” por Empresa+CardCode+ItemCode).
     * Preferencia: fila mes=0 (global explícito) → moda de meses 1–12 → último mes.
     * También indexa empresa|ItemCode e ItemCode.
     *
     * @return array<string, array{costo: float, moneda: string, mes: int|null, card_code: string, origen: string}>
     */
    protected function costosMaestroMap(?string $empresa = null, ?int $anio = null): array
    {
        if (! Schema::hasTable('tbl_pv_productos_costo')) {
            return [];
        }
        $anio = $this->anioProyeccionCostos($anio);
        $q = PvProductoCosto::query();
        if ($this->hasAnioCostos()) {
            $q->where('anio', $anio);
        }
        if ($empresa) {
            $q->whereRaw('UPPER(empresa) = ?', [strtoupper(trim($empresa))]);
        }
        $hasCard = Schema::hasColumn('tbl_pv_productos_costo', 'card_code');
        $hasMes = Schema::hasColumn('tbl_pv_productos_costo', 'mes');

        $groups = [];
        $q->orderByDesc('updated_at')->orderByDesc('id')->get()
            ->each(function (PvProductoCosto $row) use (&$groups, $hasCard, $hasMes) {
                $emp = strtoupper(trim((string) $row->empresa));
                $cod = trim((string) $row->producto_codigo);
                if ($emp === '' || $cod === '') {
                    return;
                }
                $precio = (float) $row->costo_unitario;
                if ($precio <= 0) {
                    return;
                }
                $card = $hasCard ? trim((string) ($row->card_code ?? '')) : '';
                $mes = $hasMes ? (int) ($row->mes ?? 0) : 0;
                $moneda = strtoupper((string) ($row->moneda ?: 'MXN')) ?: 'MXN';
                $key = $emp.'|'.$card.'|'.$cod;
                if (! isset($groups[$key])) {
                    $groups[$key] = [
                        'emp' => $emp,
                        'card' => $card,
                        'cod' => $cod,
                        'global' => null,
                        'global_moneda' => $moneda,
                        'meses' => [],
                    ];
                }
                if ($mes === 0) {
                    // Primera fila global (updated_at desc).
                    if ($groups[$key]['global'] === null) {
                        $groups[$key]['global'] = $precio;
                        $groups[$key]['global_moneda'] = $moneda;
                    }

                    return;
                }
                if ($mes >= 1 && $mes <= 12) {
                    $groups[$key]['meses'][] = [
                        'mes' => $mes,
                        'precio' => $precio,
                        'moneda' => $moneda,
                    ];
                }
            });

        $out = [];
        $put = static function (string $k, array $entry) use (&$out) {
            if ($k === '' || isset($out[$k]) || (float) ($entry['costo'] ?? 0) <= 0) {
                return;
            }
            $out[$k] = $entry;
        };

        foreach ($groups as $g) {
            $costo = null;
            $moneda = 'MXN';
            $mesRef = null;
            if ($g['global'] !== null && (float) $g['global'] > 0) {
                $costo = (float) $g['global'];
                $moneda = (string) $g['global_moneda'];
                $mesRef = null;
            } elseif ($g['meses']) {
                $freq = [];
                $freqMon = [];
                $lastMes = 0;
                $lastPrecio = 0.0;
                $lastMon = 'MXN';
                foreach ($g['meses'] as $m) {
                    $keyP = number_format((float) $m['precio'], 4, '.', '');
                    $freq[$keyP] = ($freq[$keyP] ?? 0) + 1;
                    $freqMon[$m['moneda']] = ($freqMon[$m['moneda']] ?? 0) + 1;
                    if ((int) $m['mes'] >= $lastMes) {
                        $lastMes = (int) $m['mes'];
                        $lastPrecio = (float) $m['precio'];
                        $lastMon = (string) $m['moneda'];
                    }
                }
                arsort($freq);
                $modeKey = (string) array_key_first($freq);
                $costo = (float) $modeKey;
                arsort($freqMon);
                $moneda = (string) array_key_first($freqMon);
                // Mes de referencia = el más reciente que tenga la moda.
                $mesRef = 0;
                foreach ($g['meses'] as $m) {
                    if (abs((float) $m['precio'] - $costo) < 0.0001 && (int) $m['mes'] >= $mesRef) {
                        $mesRef = (int) $m['mes'];
                    }
                }
                // Si todos los precios son distintos (empate 1-1), usar último mes.
                if (count($freq) === count($g['meses']) && count($g['meses']) > 1) {
                    $costo = $lastPrecio;
                    $moneda = $lastMon;
                    $mesRef = $lastMes;
                }
                if ($mesRef <= 0) {
                    $mesRef = $lastMes;
                }
            }
            if ($costo === null || $costo <= 0) {
                continue;
            }
            $entry = [
                'costo' => $costo,
                'moneda' => $moneda,
                'mes' => $mesRef,
                'card_code' => $g['card'],
                'origen' => 'maestro_local',
            ];
            if ($g['card'] !== '') {
                $put($g['emp'].'|'.$g['card'].'|'.$g['cod'], $entry);
            }
            $put($g['emp'].'|'.$g['cod'], $entry);
            $put($g['cod'], $entry);
        }

        return $out;
    }

    /**
     * Precios del maestro local por mes (1–12) para el modal de proyección.
     * Claves: EMPRESA|CardCode|ItemCode, EMPRESA|ItemCode, ItemCode.
     * Cada valor: { meses: [12 floats|null], monedas: [12 strings|null] }.
     *
     * @return array<string, array{meses: array<int, float|null>, monedas: array<int, string|null>}>
     */
    protected function costosMaestroMesesMap(?string $empresa = null, ?int $anio = null): array
    {
        if (! Schema::hasTable('tbl_pv_productos_costo')) {
            return [];
        }
        $anio = $this->anioProyeccionCostos($anio);
        $q = PvProductoCosto::query();
        if ($this->hasAnioCostos()) {
            $q->where('anio', $anio);
        }
        if ($empresa) {
            $q->whereRaw('UPPER(empresa) = ?', [strtoupper(trim($empresa))]);
        }
        $hasCard = Schema::hasColumn('tbl_pv_productos_costo', 'card_code');
        $hasMes = Schema::hasColumn('tbl_pv_productos_costo', 'mes');
        if (! $hasMes) {
            return [];
        }

        $q->orderByDesc('updated_at')->orderByDesc('id');

        $out = [];
        $put = static function (string $key, int $mesIdx, float $precio, string $moneda) use (&$out) {
            if ($key === '' || $mesIdx < 0 || $mesIdx > 11 || $precio <= 0) {
                return;
            }
            if (! isset($out[$key])) {
                $out[$key] = [
                    'meses' => array_fill(0, 12, null),
                    'monedas' => array_fill(0, 12, null),
                ];
            }
            // Primer hit gana (updated_at desc).
            if ($out[$key]['meses'][$mesIdx] === null) {
                $out[$key]['meses'][$mesIdx] = round($precio, 4);
                $out[$key]['monedas'][$mesIdx] = $moneda;
            }
        };

        $q->get()->each(function (PvProductoCosto $row) use ($put, $hasCard) {
            $emp = strtoupper(trim((string) $row->empresa));
            $cod = trim((string) $row->producto_codigo);
            if ($emp === '' || $cod === '') {
                return;
            }
            $mes = (int) ($row->mes ?? 0);
            if ($mes < 1 || $mes > 12) {
                return;
            }
            $precio = (float) $row->costo_unitario;
            if ($precio <= 0) {
                return;
            }
            $moneda = strtoupper((string) ($row->moneda ?: 'MXN')) ?: 'MXN';
            $card = $hasCard ? trim((string) ($row->card_code ?? '')) : '';
            $idx = $mes - 1;
            if ($card !== '') {
                $put($emp.'|'.$card.'|'.$cod, $idx, $precio, $moneda);
            }
            $put($emp.'|'.$cod, $idx, $precio, $moneda);
            $put($cod, $idx, $precio, $moneda);
        });

        return $out;
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
            'tipoBudget' => 'nullable|in:BUDGET,3+9,6+6,9+3,SIOP',
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
        // Tipo de budget/forecast: al crear siempre; al editar solo si aún no estaba fijado.
        if (Schema::hasColumn('tbl_pv_ciclos', 'tipo_budget')) {
            $incoming = $this->normalizeTipoBudget($data['tipoBudget'] ?? null);
            if ($nuevo) {
                $fill['tipo_budget'] = $incoming ?: 'BUDGET';
            } elseif (($ciclo->tipo_budget === null || trim((string) $ciclo->tipo_budget) === '') && $incoming) {
                $fill['tipo_budget'] = $incoming;
            }
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
        $todas = $request->boolean('todas');
        if (function_exists('set_time_limit')) {
            @set_time_limit($todas ? 180 : 120);
        }

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
            ->whereRaw('UPPER(ciclo_codigo) = ?', [strtoupper($ciclo)]);
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
            'cuentas.*.costo' => 'nullable|numeric|min:0',
            'cuentas.*.precio' => 'nullable|numeric|min:0',
            'cuentas.*.moneda' => 'nullable|string|max:8',
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
        $creadosMaestro = $this->asegurarMaestroDesdeAsignacion($asig, $data['cuentas'] ?? []);

        $asig->load(['usuario', 'cuentas', 'permisos.tipo']);

        return response()->json([
            'ok' => true,
            'asignacion' => $this->asignacionPayload($asig),
            'maestro_creados' => $creadosMaestro,
        ]);
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
            'cuentas.*.costo' => 'nullable|numeric|min:0',
            'cuentas.*.precio' => 'nullable|numeric|min:0',
            'cuentas.*.moneda' => 'nullable|string|max:8',
            'permisos' => 'sometimes|array',
            'permisos.*' => 'string',
            'accesos' => 'sometimes|array',
            'accesos.*.user_id' => 'required|integer|exists:users,id',
            'accesos.*.permisos' => 'array',
            'accesos.*.permisos.*' => 'string',
        ]);

        $creadosMaestro = 0;
        DB::transaction(function () use ($asig, $data, &$creadosMaestro) {
            if (array_key_exists('cuentas', $data)) {
                $this->syncCuentas($asig, $data['cuentas']);
                $this->propagarCuentasAColaboradores($asig);
                $creadosMaestro = $this->asegurarMaestroDesdeAsignacion($asig, $data['cuentas']);
            }
            if (array_key_exists('permisos', $data)) {
                $this->syncPermisosClaves($asig, $data['permisos'] ?: ['revisar']);
            }
            if (array_key_exists('accesos', $data)) {
                $this->syncAccesos($asig, $data['accesos']);
            }
        });

        $asig->load(['usuario', 'cuentas', 'permisos.tipo']);

        return response()->json([
            'ok' => true,
            'asignacion' => $this->asignacionPayload($asig),
            'maestro_creados' => $creadosMaestro,
        ]);
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
        $force = filter_var($request->get('force', $request->get('refresh', false)), FILTER_VALIDATE_BOOLEAN);

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

        // 1) Snapshot local (evita ir a SAP en cada apertura de Captura).
        if (! $force && Schema::hasTable('tbl_pv_venta_real_snapshot')) {
            $snap = PvVentaRealSnapshot::query()
                ->whereRaw('UPPER(empresa) = ?', [$empresa])
                ->whereRaw('UPPER(cliente_codigo) = ?', [strtoupper($cc)])
                ->where('anio', $year)
                ->first();
            if ($snap && is_array($snap->por_cuenta) && $snap->por_cuenta !== []) {
                $payload = [
                    'ok' => true,
                    'year' => $year,
                    'por_cuenta' => $snap->por_cuenta,
                    'mensaje' => null,
                    'fuente' => 'snapshot',
                    'synced_at' => $snap->synced_at ? $snap->synced_at->format('Y-m-d H:i') : null,
                ];
                $cacheKey = 'pv.venta-real.v3.'.$empresa.'.'.$cc.'.'.$year;
                Cache::put($cacheKey, $payload, 900);
                // No reescribir maestro en cada lectura de snapshot (eso volvía lenta la recarga).

                return response()->json($payload);
            }
        }

        // 2) Cache RAM corta (útil mientras se escribe el snapshot).
        $cacheKey = 'pv.venta-real.v3.'.$empresa.'.'.$cc.'.'.$year;
        if (! $force) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached) && ! empty($cached['ok'])) {
                $cached['fuente'] = $cached['fuente'] ?? 'cache';

                return response()->json($cached);
            }
        } else {
            Cache::forget($cacheKey);
        }

        // 3) API SAP → guardar snapshot.
        try {
            $payload = $this->cargarGastoRealCentro($empresa, $cc, $year);
            if (! empty($payload['ok'])) {
                $payload['fuente'] = 'api';
                $payload['synced_at'] = now()->format('Y-m-d H:i');
                Cache::put($cacheKey, $payload, 900);
                $this->guardarVentaRealSnapshot(
                    $empresa,
                    $cc,
                    $year,
                    is_array($payload['por_cuenta'] ?? null) ? $payload['por_cuenta'] : [],
                    optional($request->user())->id
                );
            }
        } catch (Throwable $e) {
            // Si falla la API pero hay snapshot viejo, úsalo.
            if (Schema::hasTable('tbl_pv_venta_real_snapshot')) {
                $snap = PvVentaRealSnapshot::query()
                    ->whereRaw('UPPER(empresa) = ?', [$empresa])
                    ->whereRaw('UPPER(cliente_codigo) = ?', [strtoupper($cc)])
                    ->where('anio', $year)
                    ->first();
                if ($snap && is_array($snap->por_cuenta) && $snap->por_cuenta !== []) {
                    return response()->json([
                        'ok' => true,
                        'year' => $year,
                        'por_cuenta' => $snap->por_cuenta,
                        'mensaje' => 'API no disponible; se usó snapshot local ('.$e->getMessage().').',
                        'fuente' => 'snapshot_fallback',
                        'synced_at' => $snap->synced_at ? $snap->synced_at->format('Y-m-d H:i') : null,
                    ]);
                }
            }

            return response()->json([
                'ok' => false,
                'year' => $year,
                'por_cuenta' => (object) [],
                'mensaje' => $e->getMessage(),
                'fuente' => 'error',
            ], 200);
        }

        if (! empty($payload['ok']) && ! empty($payload['por_cuenta'])) {
            $this->asegurarCostosMaestroDesdePorCuenta($empresa, $payload['por_cuenta']);
        }

        return response()->json($payload);
    }

    /**
     * @param  array<string, mixed>  $porCuenta
     */
    protected function guardarVentaRealSnapshot(
        string $empresa,
        string $cc,
        int $year,
        array $porCuenta,
        $userId = null
    ): void {
        if (! Schema::hasTable('tbl_pv_venta_real_snapshot') || $porCuenta === []) {
            return;
        }

        $row = PvVentaRealSnapshot::query()->firstOrNew([
            'empresa' => strtoupper(trim($empresa)),
            'cliente_codigo' => trim($cc),
            'anio' => $year,
        ]);
        $row->por_cuenta = $porCuenta;
        $row->origen = 'api';
        $row->synced_at = now();
        $row->synced_by = $userId;
        $row->save();
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

    /**
     * Ventas budget (API /ventas-budget) agregadas por ItemCode + mes.
     * Útil en Captura tipo BUDGET para precargar los últimos 3 meses.
     */
    public function capturaVentasBudget(Request $request): JsonResponse
    {
        $empresa = strtoupper(trim((string) $request->get('empresa', '')));
        $card = trim((string) $request->get('cliente', $request->get('CardCode', $request->get('cc', ''))));
        $mesesRaw = trim((string) $request->get('meses', ''));

        $alias = ['ABSA' => 'AUSTIN'];
        if (isset($alias[$empresa])) {
            $empresa = $alias[$empresa];
        }

        if ($empresa === '' || $card === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Falta empresa o cliente.',
                'meses' => [],
                'por_articulo' => (object) [],
            ], 422);
        }

        $meses = [];
        if ($mesesRaw !== '') {
            foreach (preg_split('/[,\s]+/', $mesesRaw) as $p) {
                $m = (int) $p;
                if ($m >= 1 && $m <= 12) {
                    $meses[$m] = $m;
                }
            }
            $meses = array_values($meses);
            sort($meses);
        }
        if (! $meses) {
            // Por defecto: Oct, Nov, Dic.
            $meses = [10, 11, 12];
        }

        @set_time_limit(180);
        $api = app(AutinApiClient::class);
        $pack = $api->ventasBudgetTodasPaginas([
            'Empresa' => $empresa,
            'CardCode' => $card,
            'meses' => implode(',', $meses),
        ]);

        if (empty($pack['ok'])) {
            return response()->json([
                'ok' => false,
                'message' => $pack['message'] ?? 'Sin conexión a ventas-budget',
                'meses' => $meses,
                'por_articulo' => (object) [],
            ], 200);
        }

        /** @var array<string, array<string, mixed>> $porArticulo */
        $porArticulo = [];
        foreach ($pack['rows'] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $rowEmp = strtoupper(trim((string) ($row['Empresa'] ?? $empresa)));
            if ($rowEmp !== '' && $rowEmp !== $empresa) {
                continue;
            }
            $rowCard = trim((string) ($row['CardCode'] ?? ''));
            if ($rowCard !== '' && strcasecmp($rowCard, $card) !== 0) {
                continue;
            }
            $item = trim((string) ($row['ItemCode'] ?? ''));
            if ($item === '') {
                continue;
            }
            $fecha = (string) ($row['Fecha'] ?? $row['DocDate'] ?? $row['fecha'] ?? '');
            $mes = $this->mesDeFecha($fecha);
            // mesDeFecha returns 0..11 index — convert to 1..12
            if ($mes < 0 || $mes > 11) {
                // try explicit Mes field
                $mesNum = (int) ($row['Mes'] ?? 0);
                if ($mesNum < 1 || $mesNum > 12) {
                    continue;
                }
            } else {
                $mesNum = $mes + 1;
            }
            if (! in_array($mesNum, $meses, true)) {
                continue;
            }
            $qty = abs($this->cantidadVenta($row));
            if ($qty <= 0) {
                // Quantity may come as string
                $qty = abs((float) ($row['Quantity'] ?? $row['quantity'] ?? 0));
            }
            if ($qty <= 0) {
                continue;
            }
            $nombre = trim((string) ($row['ItemName'] ?? $row['Dscription'] ?? ''));
            $keys = array_unique(array_filter([
                $item,
                strtoupper($item),
                $this->codigoCuentaKey($item),
            ]));
            foreach ($keys as $k) {
                if (! isset($porArticulo[$k])) {
                    $porArticulo[$k] = [
                        'codigo' => $item,
                        'nombre' => $nombre,
                        'meses' => array_fill(0, 12, null),
                        'total' => 0.0,
                    ];
                }
                $idx = $mesNum - 1;
                $prev = $porArticulo[$k]['meses'][$idx];
                $porArticulo[$k]['meses'][$idx] = round(($prev === null ? 0.0 : (float) $prev) + $qty, 4);
                $porArticulo[$k]['total'] = round((float) $porArticulo[$k]['total'] + $qty, 4);
                if ($nombre !== '' && ($porArticulo[$k]['nombre'] ?? '') === '') {
                    $porArticulo[$k]['nombre'] = $nombre;
                }
            }
        }

        // Respuesta limpia: una entrada por ItemCode canónico.
        $limpias = [];
        foreach ($porArticulo as $hit) {
            $cod = (string) ($hit['codigo'] ?? '');
            if ($cod === '' || isset($limpias[$cod])) {
                continue;
            }
            $limpias[$cod] = $hit;
        }

        $mesesNom = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $mesesLabel = array_map(function ($m) use ($mesesNom) {
            return $mesesNom[$m] ?? (string) $m;
        }, $meses);

        return response()->json([
            'ok' => true,
            'empresa' => $empresa,
            'cliente' => $card,
            'meses' => $meses,
            'meses_label' => $mesesLabel,
            'productos' => count($limpias),
            'por_articulo' => $limpias,
            'message' => count($limpias) > 0
                ? ('Se encontraron '.count($limpias).' producto(s) en ventas-budget ('.implode(', ', $mesesLabel).').')
                : ('Sin datos en ventas-budget para '.$empresa.' / '.$card.' ('.implode(', ', $mesesLabel).').'),
        ]);
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

        $anioCostos = $this->anioProyeccionCostos(null, $ciclo);

        return response()->json([
            'ok' => true,
            'ciclo' => $ciclo,
            'anio_costos' => $anioCostos,
            'budgets' => (object) $budgets,
            'completados' => (object) $completados,
            'costos' => (object) $costos,
            'costosMaster' => (object) $this->costosMaestroMap(null, $anioCostos),
            'costosMeses' => (object) $this->costosMaestroMesesMap(null, $anioCostos),
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
        $anioCostos = $this->anioProyeccionCostos(null, $ciclo);

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
            $incoming = (float) $data['costo_unitario'];
            // Si el cliente manda 0, preferir maestro local (no borrar costo real).
            if ($incoming > 0) {
                $row->costo_unitario = $incoming;
            } else {
                $masterKey = $empresa.'|'.$cuenta;
                $masters = $this->costosMaestroMap($empresa, $anioCostos);
                if (! empty($masters[$masterKey]['costo'])) {
                    $row->costo_unitario = (float) $masters[$masterKey]['costo'];
                } elseif ($row->costo_unitario === null) {
                    $row->costo_unitario = 0;
                }
            }
        } else {
            $masters = $this->costosMaestroMap($empresa, $anioCostos);
            $masterKey = $empresa.'|'.$cuenta;
            if (! $row->exists && ! empty($masters[$masterKey]['costo'])) {
                $row->costo_unitario = (float) $masters[$masterKey]['costo'];
            }
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

        if ((float) ($row->costo_unitario ?? 0) > 0) {
            $this->asegurarCostoMaestroSiNoExiste(
                $empresa,
                $cuenta,
                (string) ($row->producto_nombre ?? $row->cuenta_nombre ?? ''),
                (float) $row->costo_unitario,
                strtoupper((string) ($row->moneda ?? 'MXN')) ?: 'MXN',
                auth()->id(),
                $anioCostos
            );
        }

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
        $pack = $this->filasVentasEmpresa(strtolower($empresa), $year, [
            'CardCode' => $cc,
            'fecha_desde' => $year.'/01/01',
            'fecha_hasta' => $year.'/12/31',
        ]);

        $porCuenta = [];
        $ok = ! empty($pack['ok']);
        $mensaje = $pack['mensaje'] ?? null;

        foreach ($pack['rows'] as $row) {
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
            $costoInv = $this->costoInventarioVenta($row);
            $nombre = trim((string) ($row['ItemName'] ?? $row['Dscription'] ?? ''));
            $unidad = $this->elegirUnidadDesdeVenta($row);
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
                    'costo' => $costoInv,
                    'costo_moneda' => 'MXN',
                ];
            } elseif ($nombre !== '' && $porCuenta[$key]['nombre'] === '') {
                $porCuenta[$key]['nombre'] = $nombre;
            }
            if ($unidad !== '') {
                $actual = (string) ($porCuenta[$key]['unidad'] ?? '');
                if ($actual === '' || $this->unidadEsMejor($unidad, $actual)) {
                    $porCuenta[$key]['unidad'] = $unidad;
                }
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
            if ($costoInv > 0) {
                $porCuenta[$key]['costo'] = $costoInv;
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

            // Precio/costo unitario del año: promedio ponderado de la venta (USD si hay LineTotalUSD).
            $qtyTot = array_sum($item['gasto'] ?? []);
            $usdTot = array_sum($item['importe_usd'] ?? []);
            $mxnTot = array_sum($item['importe'] ?? []);
            if ($qtyTot != 0.0 && abs($usdTot) > 0.0001) {
                $item['costo'] = round(abs($usdTot / $qtyTot), 4);
                $item['costo_moneda'] = 'USD';
            } elseif ($qtyTot != 0.0 && abs($mxnTot) > 0.0001) {
                $item['costo'] = round(abs($mxnTot / $qtyTot), 4);
                $item['costo_moneda'] = 'MXN';
            } elseif (empty($item['costo'])) {
                $item['costo'] = 0.0;
                $item['costo_moneda'] = 'MXN';
            }
            $item['unidad_nombre'] = $this->nombreUnidadMedida((string) ($item['unidad'] ?? ''));
        }
        unset($item);

        return [
            'ok' => $ok,
            'year' => $year,
            'por_cuenta' => $porCuenta,
            'mensaje' => $ok ? null : $mensaje,
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
                $codigo = trim((string) (
                    $row['CodigoArticulo']
                    ?? $row['Codigo de Articulo']
                    ?? $row['ItemCode']
                    ?? $row['Itemcode']
                    ?? ''
                ));
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
                    'lista' => trim((string) ($row['ListaPrecio'] ?? $row['Lista de Precio'] ?? $row['ListName'] ?? '')),
                    'no_lista' => trim((string) ($row['NoLista'] ?? $row['No Lista'] ?? $row['ListNum'] ?? $row['Listnum'] ?? '')),
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
        $this->adjuntarNombresUnidad($porArticulo);

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
                    $unidad = $this->elegirUnidadDesdeVenta($row);
                    if ($unidad === '') {
                        continue;
                    }
                    foreach (array_unique(array_filter([$codigo, strtoupper($codigo), $this->codigoCuentaKey($codigo)])) as $k) {
                        if (! isset($porArticulo[$k])) {
                            continue;
                        }
                        $actual = (string) ($porArticulo[$k]['unidad'] ?? '');
                        if ($actual === '' || $this->unidadEsMejor($unidad, $actual)) {
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

    /**
     * Elige la mejor unidad de una fila de ventas SAP.
     * Prefiere SalPackMsr (suele ser legible: KILOS, PZA, METROS) o la que tenga
     * nombre en tblunidadesmedida; SalUnitMsr suele ser código interno (H87, XBX).
     */
    protected function elegirUnidadDesdeVenta(array $row): string
    {
        $pack = trim((string) ($row['SalPackMsr'] ?? $row['unidad'] ?? ''));
        $unit = trim((string) ($row['SalUnitMsr'] ?? $row['UomCode'] ?? ''));
        if ($pack !== '' && $unit !== '') {
            return $this->unidadEsMejor($pack, $unit) ? $pack : $unit;
        }

        return $pack !== '' ? $pack : $unit;
    }

    /** True si $a es preferible a $b (tiene nombre en catálogo o es más legible). */
    protected function unidadEsMejor(string $a, string $b): bool
    {
        $a = trim($a);
        $b = trim($b);
        if ($a === '' || strcasecmp($a, $b) === 0) {
            return false;
        }
        if ($b === '') {
            return true;
        }
        $nombreA = $this->nombreUnidadMedida($a);
        $nombreB = $this->nombreUnidadMedida($b);
        if ($nombreA !== '' && $nombreB === '') {
            return true;
        }
        if ($nombreA === '' && $nombreB !== '') {
            return false;
        }
        // Preferir texto legible (KILOS, METROS) sobre códigos cortos alfanuméricos (H87, XBX).
        $legibleA = $this->unidadPareceLegible($a);
        $legibleB = $this->unidadPareceLegible($b);
        if ($legibleA && ! $legibleB) {
            return true;
        }
        if (! $legibleA && $legibleB) {
            return false;
        }

        return false;
    }

    protected function unidadPareceLegible(string $codigo): bool
    {
        $codigo = trim($codigo);
        if ($codigo === '') {
            return false;
        }
        // Códigos SAP típicos: letras+dígitos cortos (H87, XBX, 1I, E48).
        if (preg_match('/^[A-Z0-9]{1,4}$/i', $codigo)) {
            return false;
        }

        return (bool) preg_match('/[A-Za-zÁÉÍÓÚáéíóúñÑ]{3,}/u', $codigo);
    }

    /**
     * Mapa código UoM → nombre (tblunidadesmedida).
     *
     * @return array<string, string>
     */
    protected function mapaUnidadesMedida(): array
    {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }
        $cache = [];
        if (! Schema::hasTable('tblunidadesmedida')) {
            return $cache;
        }
        $cols = ['nombre'];
        if (Schema::hasColumn('tblunidadesmedida', 'abreviacion')) {
            $cols[] = 'abreviacion';
        }
        if (Schema::hasColumn('tblunidadesmedida', 'c_unidad_medida')) {
            $cols[] = 'c_unidad_medida';
        }
        foreach (DB::table('tblunidadesmedida')->get($cols) as $row) {
            $nombre = trim((string) ($row->nombre ?? ''));
            if ($nombre === '') {
                continue;
            }
            foreach (['abreviacion', 'c_unidad_medida', 'nombre'] as $field) {
                $code = trim((string) ($row->{$field} ?? ''));
                if ($code === '') {
                    continue;
                }
                $cache[strtoupper($code)] = $nombre;
                $cache[$code] = $nombre;
            }
        }

        return $cache;
    }

    protected function nombreUnidadMedida(string $codigo): string
    {
        $codigo = trim($codigo);
        if ($codigo === '') {
            return '';
        }
        $map = $this->mapaUnidadesMedida();

        return (string) ($map[strtoupper($codigo)] ?? $map[$codigo] ?? '');
    }

    /**
     * @param  array<string, array<string, mixed>>  $porArticulo
     */
    protected function adjuntarNombresUnidad(array &$porArticulo): void
    {
        foreach ($porArticulo as &$entry) {
            if (! is_array($entry)) {
                continue;
            }
            $code = trim((string) ($entry['unidad'] ?? ''));
            $entry['unidad_nombre'] = $this->nombreUnidadMedida($code);
        }
        unset($entry);
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
            'ventasBudgetUrl' => route('pv.api.captura.ventas_budget'),
            'costosUrl' => route('pv.api.costos'),
            'costosHistorialUrl' => route('pv.api.costos.historial'),
            'sapOk' => false,
            'sapMensaje' => null,
            'empresasSap' => [],
            'centros' => [],
            'cuentas' => [],
            'agrupaciones' => [],
            'usuarios' => $usuarios,
            'empresasLocales' => $empresasLocales,
            'unidadesMedida' => $this->mapaUnidadesMedida(),
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
    protected function filasVentasEmpresa(string $empresa, int $year, array $extra = [], int $maxPages = 30): array
    {
        $api = app(AutinApiClient::class);
        $rows = [];
        $ok = false;
        $mensaje = null;
        foreach ($this->empresasFiltroVentas($empresa) as $empFiltro) {
            $pack = $api->ventasTodasPaginas(array_merge([
                'year' => $year,
                'Empresa' => $empFiltro,
            ], $extra), $maxPages, 6);
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
     * Productos (ItemCode / ItemName) vendidos a un cliente (CardCode) en OINV + ORIN.
     * Si el año del ciclo (p. ej. Forecast 2027) aún no tiene ventas, prueba hasta 3 años atrás.
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

        $requested = $year;
        $last = null;
        for ($y = $year; $y >= $year - 3 && $y >= 2000; $y--) {
            $pack = $this->cargarProductosClienteAnio($empresa, $cliente, $y, $todas);
            $last = $pack;
            if (! empty($pack['productos'])) {
                if ($y !== $requested) {
                    $pack['mensaje'] = 'Mostrando productos con venta en '.$y.' (aún no hay en '.$requested.')';
                }

                return $pack;
            }
        }

        return $last ?? [
            'ok' => false,
            'productos' => [],
            'mensaje' => 'Sin productos SAP',
        ];
    }

    /**
     * @return array{ok: bool, productos: array<int, array<string, mixed>>, mensaje: string|null}
     */
    protected function cargarProductosClienteAnio(string $empresa, string $cliente, int $year, bool $todas = false): array
    {
        $cacheKey = $todas
            ? 'pv.productos.'.$empresa.'.'.$year.'.ALL'
            : 'pv.productos.'.$empresa.'.'.$year.'.'.md5(strtoupper($cliente));
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && ! empty($cached['ok']) && ! empty($cached['productos'])) {
            return $cached;
        }

        $pack = $this->filasVentasEmpresa($empresa, $year, $todas ? [] : ['CardCode' => $cliente], $todas ? 80 : 30);
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
     * Si el año del ciclo aún no tiene ventas (Forecast futuro), prueba hasta 3 años atrás.
     *
     * @return array{ok: bool, clientes: array<int, array<string, mixed>>, mensaje: string|null}
     */
    protected function cargarClientesEmpresa(string $empresa, int $year): array
    {
        $empresa = strtolower(trim($empresa));
        if ($year < 2000) {
            $year = (int) date('Y');
        }

        $requested = $year;
        $last = null;
        for ($y = $year; $y >= $year - 3 && $y >= 2000; $y--) {
            $pack = $this->cargarClientesEmpresaAnio($empresa, $y);
            $last = $pack;
            if (! empty($pack['clientes'])) {
                if ($y !== $requested) {
                    $pack['mensaje'] = 'Mostrando clientes con venta en '.$y.' (aún no hay en '.$requested.')';
                }

                return $pack;
            }
        }

        return $last ?? [
            'ok' => false,
            'clientes' => [],
            'mensaje' => 'Sin clientes SAP',
        ];
    }

    /**
     * @return array{ok: bool, clientes: array<int, array<string, mixed>>, mensaje: string|null}
     */
    protected function cargarClientesEmpresaAnio(string $empresa, int $year): array
    {
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
     * Costo de inventario SAP si viene en la fila (no usar Price de venta).
     *
     * @param  array<string, mixed>  $row
     */
    protected function costoInventarioVenta(array $row): float
    {
        foreach (['StockPrice', 'Costo', 'costo', 'GrossBuyPrice', 'AvgPrice', 'LastPurPrc'] as $k) {
            if (isset($row[$k]) && is_numeric($row[$k]) && (float) $row[$k] != 0.0) {
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
        $inv = $this->costoInventarioVenta($row);
        if ($inv > 0) {
            return $inv;
        }
        // Compat: si no hay costo de inventario, no inventar con Price de venta.
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
     * Al guardar una asignación a usuario: si el cliente+producto no están en el maestro
     * local (tbl_pv_productos_costo), los crea con precio de lista SAP (o el enviado).
     * No corre al solo seleccionar en la UI.
     *
     * @param  array<int, array<string, mixed>>  $cuentas
     */
    protected function asegurarMaestroDesdeAsignacion(PvAsignacion $asig, array $cuentas): int
    {
        if (! Schema::hasTable('tbl_pv_productos_costo') || ! $cuentas) {
            return 0;
        }

        $empresa = strtoupper(trim((string) $asig->empresa));
        $card = trim((string) ($asig->cliente_codigo ?? $asig->centro_codigo ?? ''));
        $cardName = trim((string) ($asig->cliente_nombre ?? $asig->centro_nombre ?? ''));
        if ($empresa === '' || $card === '' || strtoupper(str_replace(' ', '', $card)) === 'SIN_CC') {
            return 0;
        }

        $anio = $this->anioProyeccionCostos(null, (string) ($asig->ciclo_codigo ?? ''));
        $hasCard = Schema::hasColumn('tbl_pv_productos_costo', 'card_code');
        $hasCardName = Schema::hasColumn('tbl_pv_productos_costo', 'card_name');
        $hasMes = Schema::hasColumn('tbl_pv_productos_costo', 'mes');
        $hasAnio = $this->hasAnioCostos();
        $userId = auth()->id();

        $porArticulo = [];
        try {
            $listas = $this->cargarListasPreciosCliente($empresa, $card);
            $porArticulo = is_array($listas['por_articulo'] ?? null) ? $listas['por_articulo'] : [];
        } catch (Throwable $e) {
            $porArticulo = [];
        }

        $nombresLocales = $this->mapaNombresProductosLocal();
        $creados = 0;

        foreach ($cuentas as $cta) {
            if (! is_array($cta)) {
                continue;
            }
            $item = trim((string) ($cta['codigo'] ?? $cta['cuenta_codigo'] ?? ''));
            if ($item === '') {
                continue;
            }
            $nombre = trim((string) ($cta['nombre'] ?? $cta['cuenta_nombre'] ?? ''));
            $lista = $porArticulo[$item]
                ?? $porArticulo[strtoupper($item)]
                ?? $porArticulo[$this->codigoCuentaKey($item)]
                ?? null;

            $precio = 0.0;
            $moneda = 'MXN';
            if (is_array($lista)) {
                $precio = (float) ($lista['precio'] ?? 0);
                $moneda = strtoupper(trim((string) ($lista['moneda'] ?? 'MXN'))) ?: 'MXN';
                if ($nombre === '' || strcasecmp($nombre, $item) === 0) {
                    $nombre = trim((string) ($lista['nombre'] ?? $nombre));
                }
            }
            if ($precio <= 0) {
                $precio = (float) ($cta['precio'] ?? $cta['costo'] ?? 0);
                $moneda = strtoupper(trim((string) ($cta['moneda'] ?? $moneda))) ?: 'MXN';
            }
            if (! in_array($moneda, ['MXN', 'USD'], true)) {
                $moneda = 'MXN';
            }
            if ($nombre === '' || strcasecmp($nombre, $item) === 0) {
                $nombre = (string) ($nombresLocales[$item] ?? $item);
            }

            $q = PvProductoCosto::query()
                ->whereRaw('UPPER(empresa) = ?', [$empresa])
                ->where('producto_codigo', $item);
            if ($hasAnio) {
                $q->where('anio', $anio);
            }
            if ($hasCard) {
                $q->where('card_code', $card);
            }
            $existentes = $q->get(['id', 'costo_unitario', 'mes']);

            $yaExiste = false;
            foreach ($existentes as $ex) {
                // Mismo cliente + producto (+ costo similar) → no duplicar.
                if ($precio <= 0 || abs((float) $ex->costo_unitario - $precio) < 0.0001) {
                    $yaExiste = true;
                    break;
                }
                // Ya hay filas mensuales de precios-mensuales para este cliente/producto.
                if ($hasMes && (int) ($ex->mes ?? 0) > 0) {
                    $yaExiste = true;
                    break;
                }
            }
            if ($yaExiste) {
                continue;
            }

            $payload = [
                'empresa' => $empresa,
                'producto_codigo' => $item,
                'producto_nombre' => mb_substr($nombre !== '' ? $nombre : $item, 0, 180),
                'costo_unitario' => round(max(0, $precio), 4),
                'moneda' => $moneda,
                'updated_by' => $userId,
            ];
            if ($hasAnio) {
                $payload['anio'] = $anio;
            }
            if ($hasCard) {
                $payload['card_code'] = $card;
            }
            if ($hasCardName) {
                $payload['card_name'] = mb_substr($cardName, 0, 180);
            }
            if ($hasMes) {
                $payload['mes'] = 0;
            }

            $row = PvProductoCosto::query()->create($payload);
            $this->registrarHistorialPrecio(
                $empresa,
                $item,
                (string) $row->producto_nombre,
                null,
                null,
                (float) $row->costo_unitario,
                (string) $row->moneda,
                'asignacion',
                $userId,
                $card,
                $cardName,
                0,
                $anio
            );
            $creados++;
        }

        return $creados;
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
        $t = strtoupper(trim((string) $tipo));
        // Acepta códigos canónicos y alias con prefijo Forecast.
        $map = [
            'BUDGET' => 'BUDGET',
            '3+9' => '3+9',
            '6+6' => '6+6',
            '9+3' => '9+3',
            'SIOP' => 'SIOP',
            'FORECAST 3+9' => '3+9',
            'FORECAST 6+6' => '6+6',
            'FORECAST 9+3' => '9+3',
            'FORECAST3+9' => '3+9',
            'FORECAST6+6' => '6+6',
            'FORECAST9+3' => '9+3',
        ];
        // Conserva mayúsculas solo en SIOP; el resto queda como 3+9 / 6+6 / 9+3.
        $raw = trim((string) $tipo);
        if (isset($map[strtoupper($raw)])) {
            return $map[strtoupper($raw)];
        }
        if (in_array($raw, ['BUDGET', '3+9', '6+6', '9+3', 'SIOP'], true)) {
            return $raw;
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
