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
use App\Models\PvClienteCatalogo;
use App\Models\PvProductoClienteCatalogo;
use App\Models\PvVentaRealSnapshot;
use App\Models\PvTipoPermiso;
use App\Models\PvUsuarioPermiso;
use App\Models\Empresas;
use App\Models\User;
use App\Services\AutinApiClient;
use App\Traits\MenuTrait;
use App\Traits\SistemasTraits;
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
    use SistemasTraits;

    /** @var array<string, bool> */
    protected $pvUserPermCache = [];

    /** @var array<string, array<string, mixed>> */
    protected $pvIndiceVentasMemo = [];

    /** @var array<string, \Illuminate\Support\Collection> */
    protected $pvFilasCostoMemo = [];

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
            'puedeEditarPreciosCaptura' => $this->puedeEditarPreciosCaptura(),
        ]);
    }

    public function detalle(Request $request)
    {
        return $this->page('detalle', 'ProyeccionesVentas.detalle', [
            'centroInicial' => (string) $request->get('cc', ''),
            'empresaInicial' => (string) $request->get('empresa', ''),
            'cicloInicial' => (string) $request->get('ciclo', ''),
            'misAsignaciones' => $this->misAsignacionesList((string) $request->get('ciclo', '')),
            'puedeEditarPreciosCaptura' => $this->puedeEditarPreciosCaptura(),
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
            'costosVaciosUrl' => route('pv.api.costos.vacios'),
            'costosRellenarVaciosLoteUrl' => route('pv.api.costos.rellenar_vacios_lote'),
            'costosSinPrecioListaUrl' => route('pv.api.costos.sin_precio_lista'),
            'costosSyncPrecioListaLoteUrl' => route('pv.api.costos.sync_precio_lista_lote'),
            'costosHistorialUrl' => route('pv.api.costos.historial'),
            'puedeEditarCostos' => $this->puedeEditarPreciosVentas(),
        ]);
    }

    /**
     * Permiso de sistema: editar precios en Maestro (/Ventas/Costos).
     */
    protected function puedeEditarPreciosVentas(): bool
    {
        return $this->forpermisos('editar_ventas_costos') === 'editar_ventas_costos';
    }

    /**
     * Permiso de sistema: editar precio por mes en Captura (/Ventas/Captura).
     */
    protected function puedeEditarPreciosCaptura(): bool
    {
        return $this->forpermisos('editar_precios_captura') === 'editar_precios_captura';
    }

    protected function denyUnlessPuedeEditarPrecios(): ?JsonResponse
    {
        if ($this->puedeEditarPreciosVentas()) {
            return null;
        }

        return response()->json([
            'message' => 'No tienes permiso para editar precios de productos (editar_ventas_costos).',
        ], 403);
    }

    protected function denyUnlessPuedeEditarPreciosCaptura(): ?JsonResponse
    {
        if ($this->puedeEditarPreciosCaptura()) {
            return null;
        }

        return response()->json([
            'message' => 'No tienes permiso para editar precios por mes en Captura (editar_precios_captura).',
        ], 403);
    }

    /** Maestro (Costos) o Captura: sincronizar precios mensuales. */
    protected function denyUnlessPuedeEditarPreciosMeses(): ?JsonResponse
    {
        if ($this->puedeEditarPreciosVentas() || $this->puedeEditarPreciosCaptura()) {
            return null;
        }

        return response()->json([
            'message' => 'No tienes permiso para editar precios por mes.',
        ], 403);
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
        $hasPrecioLista = Schema::hasColumn('tbl_pv_productos_costo', 'precio_lista');

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
            ->each(function (PvProductoCosto $row) use (&$map, $hasCard, $hasMes, $hasAnio, $hasPrecioLista, $anio) {
                $emp = strtoupper(trim((string) $row->empresa));
                $card = $hasCard ? trim((string) ($row->card_code ?? '')) : '';
                $cod = trim((string) $row->producto_codigo);
                $mes = $hasMes ? (int) ($row->mes ?? 0) : 0;
                $key = $emp.'|'.$card.'|'.$cod.'|'.$mes;
                $precioLista = null;
                if ($hasPrecioLista && $row->precio_lista !== null && (float) $row->precio_lista > 0) {
                    $precioLista = round((float) $row->precio_lista, 4);
                }
                $map[$key] = [
                    'empresa' => $emp,
                    'card_code' => $card,
                    'card_name' => $hasCard ? trim((string) ($row->card_name ?? '')) : '',
                    'producto_codigo' => $cod,
                    'producto_nombre' => $row->producto_nombre,
                    'mes' => $mes > 0 ? $mes : null,
                    'anio' => $hasAnio ? (int) ($row->anio ?? $anio) : $anio,
                    'costo_unitario' => (float) $row->costo_unitario,
                    'precio_lista' => $precioLista,
                    'moneda_lista' => $precioLista !== null ? strtoupper((string) ($row->moneda ?: 'MXN')) : null,
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
                'precio_lista' => null,
                'moneda_lista' => null,
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
                    'precio_lista' => null,
                    'moneda_lista' => null,
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
            $listaStored = isset($it['precio_lista']) ? (float) $it['precio_lista'] : 0.0;
            if ($listaStored > 0 && (empty($g['precio_lista']) || ! ((float) $g['precio_lista'] > 0))) {
                $g['precio_lista'] = round($listaStored, 4);
                $g['moneda_lista'] = strtoupper((string) ($it['moneda_lista'] ?? $it['moneda'] ?? 'MXN')) ?: 'MXN';
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
     * Consulta /listaPreciosventa (Empresa+CodigoCliente+CodigoArticulo)
     * y actualiza solo el precio global (mes=0). No toca meses 1–12.
     * Si confirmar=false solo compara; si confirmar=true escribe el global.
     */
    public function actualizarCostoDesdeApi(Request $request): JsonResponse
    {
        if ($deny = $this->denyUnlessPuedeEditarPrecios()) {
            return $deny;
        }
        if (! Schema::hasTable('tbl_pv_productos_costo')) {
            return response()->json(['message' => 'Falta ejecutar la migración de costos de productos.'], 422);
        }
        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
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
        // Siempre precio global (mes=0).
        $mes = 0;
        $anioProy = $this->anioProyeccionCostos((int) ($data['anio_proyeccion'] ?? 0));
        $confirmar = (bool) ($data['confirmar'] ?? false);

        if ($card === '') {
            return response()->json([
                'message' => 'Falta CardCode (CodigoCliente) para consultar listaPreciosventa.',
            ], 422);
        }

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
            $lookup['mes'] = 0;
        }

        $local = PvProductoCosto::query()->where($lookup)->first();
        $precioLocal = $local ? (float) $local->costo_unitario : null;
        $monedaLocal = $local ? strtoupper((string) ($local->moneda ?: 'MXN')) : null;

        try {
            // Misma ruta optimizada que Lista de precios / Asignaciones (cache + por artículo).
            $pack = $this->cargarListasPreciosCliente($empresa, $card, $anioProy, [$item], false);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Error al consultar API: '.$e->getMessage()], 422);
        }
        if (empty($pack['ok'])) {
            return response()->json([
                'message' => $pack['mensaje'] ?? 'Sin conexión a listaPreciosventa',
            ], 422);
        }

        $porArt = is_array($pack['por_articulo'] ?? null) ? $pack['por_articulo'] : [];
        $hitEntry = $porArt[$item] ?? $porArt[strtoupper($item)] ?? null;
        if (! is_array($hitEntry) || (float) ($hitEntry['precio'] ?? 0) <= 0) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontró ese producto en listaPreciosventa (Empresa + CodigoCliente + CodigoArticulo).',
                'empresa' => $empresa,
                'card_code' => $card,
                'producto_codigo' => $item,
                'mes' => 0,
                'precio_local' => $precioLocal,
                'moneda_local' => $monedaLocal,
            ], 404);
        }

        $precioApi = round((float) $hitEntry['precio'], 4);
        if ($precioApi <= 0) {
            return response()->json([
                'ok' => false,
                'message' => 'La API devolvió un precio inválido (≤ 0) para ese producto.',
                'empresa' => $empresa,
                'card_code' => $card,
                'producto_codigo' => $item,
            ], 422);
        }
        $monedaApi = strtoupper(trim((string) ($hitEntry['moneda'] ?? '')));
        if (! in_array($monedaApi, ['MXN', 'USD'], true)) {
            $monedaApi = $monedaLocal ?: 'MXN';
        }
        $cardNameApi = '';
        $nombreApi = trim((string) ($hitEntry['nombre'] ?? ''));

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
                'mes' => 0,
                'precio_local' => $precioLocal,
                'moneda_local' => $monedaLocal,
                'precio_api' => $precioApi,
                'moneda_api' => $monedaApi,
                'origen' => 'listaPreciosventa',
                'message' => $igual
                    ? 'El precio global local ya coincide con la API.'
                    : 'El precio global de la API es distinto al local.',
            ]);
        }

        if ($igual) {
            return response()->json([
                'ok' => true,
                'actualizado' => false,
                'igual' => true,
                'message' => 'No hubo cambios: el precio global ya era el mismo.',
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
                $local->mes = 0;
            }
            $local->producto_nombre = $nombreApi !== '' ? mb_substr($nombreApi, 0, 180) : $item;
            $local->moneda = $monedaApi;
        }

        $precioAnterior = $local->exists ? (float) $local->costo_unitario : null;
        $monedaAnterior = $local->exists ? (string) ($local->moneda ?: 'MXN') : null;

        $local->costo_unitario = $precioApi;
        $local->moneda = $monedaApi;
        if (Schema::hasColumn('tbl_pv_productos_costo', 'mes')) {
            $local->mes = 0;
        }
        if ($this->hasAnioCostos() && ! $local->anio) {
            $local->anio = $anioProy;
        }
        if (Schema::hasColumn('tbl_pv_productos_costo', 'card_name')
            && $cardNameApi !== ''
            && trim((string) ($local->card_name ?? '')) === '') {
            $local->card_name = mb_substr($cardNameApi, 0, 180);
        }
        if ($nombreApi !== '' && trim((string) ($local->producto_nombre ?? '')) === '') {
            $local->producto_nombre = mb_substr($nombreApi, 0, 180);
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
            'lista_precios',
            $local->updated_by,
            $card,
            (string) ($local->card_name ?? ''),
            0,
            $anioProy
        );

        $propagadas = $this->propagarCostoACiclosAbiertos($empresa, $item, $precioApi);

        return response()->json([
            'ok' => true,
            'actualizado' => true,
            'message' => 'Precio global actualizado desde listaPreciosventa.'
                .($propagadas ? (' · '.$propagadas.' proyección(es) abiertas') : ''),
            'precio_anterior' => $precioAnterior,
            'moneda_anterior' => $monedaAnterior,
            'precio_nuevo' => $precioApi,
            'moneda_nueva' => $monedaApi,
            'origen' => 'listaPreciosventa',
            'item' => [
                'empresa' => $empresa,
                'card_code' => (string) ($local->card_code ?? $card),
                'producto_codigo' => $item,
                'mes' => 0,
                'costo_unitario' => (float) $local->costo_unitario,
                'moneda' => strtoupper((string) ($local->moneda ?: 'MXN')),
                'updated_at' => $local->updated_at ? $local->updated_at->format('Y-m-d H:i') : null,
            ],
        ]);
    }

    public function guardarCostoProducto(Request $request): JsonResponse
    {
        if ($deny = $this->denyUnlessPuedeEditarPrecios()) {
            return $deny;
        }
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
        if ($deny = $this->denyUnlessPuedeEditarPreciosMeses()) {
            return $deny;
        }
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
            'origen' => mb_substr(trim($origen) !== '' ? trim($origen) : 'manual', 0, 20),
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
        if ($deny = $this->denyUnlessPuedeEditarPrecios()) {
            return $deny;
        }
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
        if (! $preview && ($deny = $this->denyUnlessPuedeEditarPrecios())) {
            return $deny;
        }
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
            'card_code' => 'nullable|string|max:40',
            'cliente' => 'nullable|string|max:120',
            'itemcode' => 'nullable|string|max:80',
            'anio_proyeccion' => 'nullable|integer|min:2000|max:2100',
            'todas_empresas' => 'nullable|boolean',
            'preview' => 'nullable|boolean',
            'solo_vacios' => 'nullable|boolean',
            'afectados' => 'nullable|array|max:3000',
            'afectados.*.empresa' => 'nullable|string|max:40',
            'afectados.*.card_code' => 'nullable|string|max:40',
            'afectados.*.cliente' => 'nullable|string|max:180',
            'afectados.*.item_code' => 'required_with:afectados|string|max:80',
            'afectados.*.producto' => 'nullable|string|max:180',
            'afectados.*.precio' => 'required_with:afectados|numeric|min:0',
            'afectados.*.moneda' => 'nullable|string|max:10',
        ]);

        $preview = ! empty($data['preview']);
        $soloVacios = ! empty($data['solo_vacios']);
        if (! $preview && ($deny = $this->denyUnlessPuedeEditarPrecios())) {
            return $deny;
        }
        $anioProy = $this->anioProyeccionCostos((int) ($data['anio_proyeccion'] ?? 0));
        $userId = optional($request->user())->id;

        // Guardar lo ya confirmado en el preview (evita reconsultar SAP y perder el guardado por timeout).
        if (! $preview && ! empty($data['afectados']) && is_array($data['afectados'])) {
            return $this->aplicarAfectadosDesdeListaPrecios($data['afectados'], $anioProy, $userId, $soloVacios);
        }

        $todasEmpresas = ! array_key_exists('todas_empresas', $data) || (bool) $data['todas_empresas'];
        $empresaFiltro = $todasEmpresas ? '' : strtoupper(trim((string) ($data['empresa'] ?? '')));
        $cardFiltro = trim((string) ($data['card_code'] ?? ''));
        $clienteFiltro = trim((string) ($data['cliente'] ?? ''));
        $anioProy = $this->anioProyeccionCostos((int) ($data['anio_proyeccion'] ?? 0));

        if ($soloVacios && $empresaFiltro === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Para rellenar precios en $0 elige una empresa (AUSTIN, IMSA, PITIC o SYDNEY).',
            ], 422);
        }

        $empresas = $empresaFiltro !== ''
            ? [$empresaFiltro]
            : ['AUSTIN', 'IMSA', 'PITIC', 'SYDNEY'];

        @set_time_limit(600);

        $hasCard = Schema::hasColumn('tbl_pv_productos_costo', 'card_code');
        $hasCardName = Schema::hasColumn('tbl_pv_productos_costo', 'card_name');
        $hasMes = Schema::hasColumn('tbl_pv_productos_costo', 'mes');
        $hasAnio = $this->hasAnioCostos();
        $userId = optional($request->user())->id;

        // Productos del maestro (proyección) indexados por empresa|card|item.
        /** @var array<string, array<string, mixed>> $maestro */
        $maestro = [];
        $q = PvProductoCosto::query();
        if ($hasAnio) {
            $q->where('anio', $anioProy);
        }
        if ($empresaFiltro !== '') {
            $q->whereRaw('UPPER(empresa) = ?', [$empresaFiltro]);
        } else {
            $q->whereIn(DB::raw('UPPER(empresa)'), $empresas);
        }
        if ($soloVacios) {
            // Solo Precio global (mes=0) vacío o 0.
            if ($hasMes) {
                $q->where('mes', 0);
            }
            $q->where(function ($w) {
                $w->whereNull('costo_unitario')->orWhere('costo_unitario', '<=', 0);
            });
        }
        if ($hasCard && ($cardFiltro !== '' || $clienteFiltro !== '')) {
            $needle = $cardFiltro !== '' ? $cardFiltro : $clienteFiltro;
            $like = '%'.$needle.'%';
            $q->where(function ($w) use ($like, $hasCardName) {
                $w->where('card_code', 'like', $like);
                if ($hasCardName) {
                    $w->orWhere('card_name', 'like', $like);
                }
            });
        }
        $itemFiltro = trim((string) ($data['itemcode'] ?? ''));
        if ($itemFiltro !== '') {
            $likeItem = '%'.$itemFiltro.'%';
            $q->where(function ($w) use ($likeItem) {
                $w->where('producto_codigo', 'like', $likeItem)
                    ->orWhere('producto_nombre', 'like', $likeItem);
            });
        }
        $q->orderBy('id')->chunk(1000, function ($chunk) use (&$maestro, $hasCard, $hasMes) {
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
            }
        });

        if ($soloVacios && $maestro) {
            $maestro = array_filter($maestro, static function ($m) {
                return (float) ($m['precio_actual'] ?? 0) <= 0;
            });
        }

        if (! $maestro) {
            return response()->json([
                'ok' => true,
                'preview' => $preview,
                'solo_vacios' => $soloVacios,
                'anio_proyeccion' => $anioProy,
                'productos_unicos' => 0,
                'afectados' => [],
                'afectados_total' => 0,
                'omitidos_sin_lista' => 0,
                'message' => $soloVacios
                    ? ('No hay productos con Precio global en $0 para '.($empresaFiltro ?: 'esa empresa').' en proyección '.$anioProy.'.')
                    : ('Tu maestro de proyección '.$anioProy.' no tiene productos para actualizar.'),
            ]);
        }

        $importados = 0;
        $propagadasTot = 0;
        $omitidosSinLista = 0;
        $omitidosSinCard = 0;
        $erroresCli = [];
        /** @var array<string, array<string, mixed>> $afectados */
        $afectados = [];

        // Maestro agrupado por cliente (1 llamada SAP por CardCode, no por artículo).
        /** @var array<string, array{empresa: string, card_code: string, locales: array<string, array<string, mixed>>}> $porCliente */
        $porCliente = [];
        foreach ($maestro as $mkey => $local) {
            if ($local['card_code'] === '') {
                $omitidosSinCard++;
                continue;
            }
            $ck = $local['empresa'].'|'.$local['card_code'];
            if (! isset($porCliente[$ck])) {
                $porCliente[$ck] = [
                    'empresa' => $local['empresa'],
                    'card_code' => $local['card_code'],
                    'locales' => [],
                ];
            }
            $porCliente[$ck]['locales'][$mkey] = $local;
        }

        if ($todasEmpresas && count($porCliente) > 90) {
            return response()->json([
                'ok' => false,
                'message' => 'Hay '.count($porCliente).' clientes con CardCode. Filtra por una empresa (AUSTIN, IMSA, PITIC o SYDNEY) antes de consultar listas SAP; “Todas las empresas” satura la API.',
                'clientes_consultados' => count($porCliente),
                'productos_unicos' => count($maestro),
                'omitidos_sin_card' => $omitidosSinCard,
            ], 422);
        }

        foreach ($porCliente as $ck => $grupo) {
            $emp = $grupo['empresa'];
            $card = $grupo['card_code'];
            $locales = $grupo['locales'];
            if ($emp === '' || $card === '' || ! $locales) {
                continue;
            }
            // Siempre pasar ítems del maestro: pocos → lookup puntual; muchos → lista con corte temprano.
            $items = array_values(array_unique(array_map(static function ($l) {
                return $l['item_code'];
            }, $locales)));
            try {
                $pack = $this->cargarListasPreciosCliente($emp, $card, 0, $items, false);
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
                $omitidosSinLista += count($locales);
                continue;
            }

            foreach ($locales as $mkey => $local) {
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
            ? (' · avisos: '.collect($erroresCli)->take(5)->map(function ($m, $k) {
                return $k.' ('.$m.')';
            })->implode('; ').((count($erroresCli) > 5) ? ('… +'.(count($erroresCli) - 5).' más') : ''))
            : '';

        $sinCardTxt = $omitidosSinCard
            ? (' · '.$omitidosSinCard.' sin CardCode (no se pueden consultar en lista SAP)')
            : '';

        return response()->json([
            'ok' => true,
            'preview' => $preview,
            'solo_vacios' => $soloVacios,
            'anio_proyeccion' => $anioProy,
            'origen' => 'listaPreciosventa',
            'productos_unicos' => count($maestro),
            'clientes_consultados' => count($porCliente),
            'afectados' => array_slice($listaAfectados, 0, 3000),
            'afectados_total' => count($listaAfectados),
            'omitidos_sin_lista' => $omitidosSinLista,
            'omitidos_sin_card' => $omitidosSinCard,
            'errores_clientes' => count($erroresCli),
            'importados' => $importados,
            'propagadas' => $propagadasTot,
            'message' => $preview
                ? ($soloVacios
                    ? ('Vista previa: '.count($listaAfectados).' producto(s) con Precio global en $0 y precio en lista SAP'
                        .$sinCardTxt.$errTxt.'.')
                    : ('Vista previa: '.count($listaAfectados).' producto(s) con precio en lista SAP'
                        .$sinCardTxt.$errTxt.'.'))
                : (($soloVacios ? 'Precio global ($0) actualizado en ' : 'Precio global actualizado en ')
                    .$importados.' producto(s)'
                    .($propagadasTot ? (' · '.$propagadasTot.' proyección(es) abiertas') : '')
                    .$sinCardTxt.$errTxt.'.'),
        ]);
    }

    /**
     * Lista local (sin SAP) de productos con Precio global = 0 para una empresa/proyección.
     * Agrupa por CardCode para procesar lotes en el front.
     */
    public function listarCostosVacios(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tbl_pv_productos_costo')) {
            return response()->json(['message' => 'Falta ejecutar la migración de costos de productos.'], 422);
        }

        $empresa = strtoupper(trim((string) $request->get('empresa', '')));
        if ($empresa === '') {
            return response()->json(['message' => 'Indica la empresa.'], 422);
        }
        $anioProy = $this->anioProyeccionCostos((int) $request->get('anio', $request->get('anio_proyeccion', 0)));
        $hasCard = Schema::hasColumn('tbl_pv_productos_costo', 'card_code');
        $hasCardName = Schema::hasColumn('tbl_pv_productos_costo', 'card_name');
        $hasMes = Schema::hasColumn('tbl_pv_productos_costo', 'mes');
        $hasAnio = $this->hasAnioCostos();

        $q = PvProductoCosto::query()->whereRaw('UPPER(empresa) = ?', [$empresa]);
        if ($hasAnio) {
            $q->where('anio', $anioProy);
        }
        if ($hasMes) {
            $q->where('mes', 0);
        }
        $q->where(function ($w) {
            $w->whereNull('costo_unitario')->orWhere('costo_unitario', '<=', 0);
        });

        $productos = [];
        $sinCard = 0;
        /** @var array<string, array{card_code: string, cliente: string, items: array<int, array<string, mixed>>}> $porCliente */
        $porCliente = [];

        $q->orderBy('id')->chunk(1000, function ($chunk) use (&$productos, &$porCliente, &$sinCard, $hasCard, $hasCardName, $empresa) {
            foreach ($chunk as $row) {
                $card = $hasCard ? trim((string) ($row->card_code ?? '')) : '';
                $item = trim((string) $row->producto_codigo);
                if ($item === '') {
                    continue;
                }
                $cliente = $hasCardName ? trim((string) ($row->card_name ?? '')) : '';
                $entry = [
                    'empresa' => $empresa,
                    'card_code' => $card,
                    'cliente' => $cliente,
                    'item_code' => $item,
                    'producto' => trim((string) ($row->producto_nombre ?: $item)),
                    'moneda' => strtoupper((string) ($row->moneda ?: 'MXN')) ?: 'MXN',
                    'precio_actual' => (float) $row->costo_unitario,
                ];
                $productos[] = $entry;
                if ($card === '') {
                    $sinCard++;
                    continue;
                }
                if (! isset($porCliente[$card])) {
                    $porCliente[$card] = [
                        'card_code' => $card,
                        'cliente' => $cliente,
                        'items' => [],
                    ];
                }
                if ($cliente !== '' && ($porCliente[$card]['cliente'] ?? '') === '') {
                    $porCliente[$card]['cliente'] = $cliente;
                }
                $porCliente[$card]['items'][] = [
                    'item_code' => $item,
                    'producto' => $entry['producto'],
                    'moneda' => $entry['moneda'],
                ];
            }
        });

        $clientes = array_values($porCliente);
        usort($clientes, static function ($a, $b) {
            return strcasecmp((string) ($a['card_code'] ?? ''), (string) ($b['card_code'] ?? ''));
        });

        return response()->json([
            'ok' => true,
            'empresa' => $empresa,
            'anio_proyeccion' => $anioProy,
            'total' => count($productos),
            'sin_card' => $sinCard,
            'clientes_total' => count($clientes),
            'clientes' => $clientes,
            'message' => count($productos)
                ? ('Hay '.count($productos).' producto(s) con Precio global en $0 en '.$empresa
                    .' · '.count($clientes).' cliente(s)'
                    .($sinCard ? (' · '.$sinCard.' sin CardCode') : '').'.')
                : ('No hay productos con Precio global en $0 para '.$empresa.' / proyección '.$anioProy.'.'),
        ]);
    }

    /**
     * Productos del maestro sin precio_lista (o todos si solo_vacios=0), agrupados por CardCode.
     */
    public function listarCostosSinPrecioLista(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tbl_pv_productos_costo')) {
            return response()->json(['message' => 'Falta ejecutar la migración de costos de productos.'], 422);
        }
        if (! Schema::hasColumn('tbl_pv_productos_costo', 'precio_lista')) {
            return response()->json(['message' => 'Falta la columna precio_lista en el maestro.'], 422);
        }

        $empresa = strtoupper(trim((string) $request->get('empresa', '')));
        if ($empresa === '' || ! in_array($empresa, ['AUSTIN', 'IMSA', 'PITIC', 'SYDNEY'], true)) {
            return response()->json(['message' => 'Indica una empresa válida (AUSTIN, IMSA, PITIC o SYDNEY).'], 422);
        }
        $anioProy = $this->anioProyeccionCostos((int) $request->get('anio', $request->get('anio_proyeccion', 0)));
        $soloVacios = ! in_array(strtolower((string) $request->get('solo_vacios', '1')), ['0', 'false', 'no'], true);
        $hasCard = Schema::hasColumn('tbl_pv_productos_costo', 'card_code');
        $hasCardName = Schema::hasColumn('tbl_pv_productos_costo', 'card_name');
        $hasMes = Schema::hasColumn('tbl_pv_productos_costo', 'mes');
        $hasAnio = $this->hasAnioCostos();

        $q = PvProductoCosto::query()->whereRaw('UPPER(empresa) = ?', [$empresa]);
        if ($hasAnio) {
            $q->where('anio', $anioProy);
        }
        if ($hasMes) {
            $q->where('mes', 0);
        }
        if ($soloVacios) {
            $q->where(function ($w) {
                $w->whereNull('precio_lista')->orWhere('precio_lista', '<=', 0);
            });
        }

        $total = 0;
        $sinCard = 0;
        /** @var array<string, array{card_code: string, cliente: string, items: array<int, array<string, mixed>>}> $porCliente */
        $porCliente = [];

        $q->orderBy('id')->chunk(1000, function ($chunk) use (&$total, &$porCliente, &$sinCard, $hasCard, $hasCardName) {
            foreach ($chunk as $row) {
                $card = $hasCard ? trim((string) ($row->card_code ?? '')) : '';
                $item = trim((string) $row->producto_codigo);
                if ($item === '') {
                    continue;
                }
                $total++;
                $cliente = $hasCardName ? trim((string) ($row->card_name ?? '')) : '';
                if ($card === '') {
                    $sinCard++;
                    continue;
                }
                if (! isset($porCliente[$card])) {
                    $porCliente[$card] = [
                        'card_code' => $card,
                        'cliente' => $cliente,
                        'items' => [],
                    ];
                }
                if ($cliente !== '' && ($porCliente[$card]['cliente'] ?? '') === '') {
                    $porCliente[$card]['cliente'] = $cliente;
                }
                $porCliente[$card]['items'][] = [
                    'item_code' => $item,
                    'producto' => trim((string) ($row->producto_nombre ?: $item)),
                    'moneda' => strtoupper((string) ($row->moneda ?: 'MXN')) ?: 'MXN',
                ];
            }
        });

        $clientes = array_values($porCliente);
        usort($clientes, static function ($a, $b) {
            return strcasecmp((string) ($a['card_code'] ?? ''), (string) ($b['card_code'] ?? ''));
        });

        return response()->json([
            'ok' => true,
            'empresa' => $empresa,
            'anio_proyeccion' => $anioProy,
            'solo_vacios' => $soloVacios,
            'endpoint' => 'listaPreciosventa/'.$empresa,
            'total' => $total,
            'sin_card' => $sinCard,
            'clientes_total' => count($clientes),
            'clientes' => $clientes,
            'message' => $total
                ? ('Hay '.$total.' producto(s) '
                    .($soloVacios ? 'sin Precio lista' : 'a sincronizar')
                    .' en '.$empresa.' · '.count($clientes).' cliente(s)'
                    .($sinCard ? (' · '.$sinCard.' sin CardCode') : '').'.')
                : ('No hay productos pendientes de Precio lista para '.$empresa.' / proyección '.$anioProy.'.'),
        ]);
    }

    /**
     * Llena solo precio_lista de UN CardCode desde /listaPreciosventa/{EMPRESA}.
     * No modifica costo_unitario (Precio global).
     */
    public function sincronizarPrecioListaLote(Request $request): JsonResponse
    {
        if ($deny = $this->denyUnlessPuedeEditarPrecios()) {
            return $deny;
        }
        if (! Schema::hasTable('tbl_pv_productos_costo')) {
            return response()->json(['message' => 'Falta ejecutar la migración de costos de productos.'], 422);
        }
        if (! Schema::hasColumn('tbl_pv_productos_costo', 'precio_lista')) {
            return response()->json(['message' => 'Falta la columna precio_lista en el maestro.'], 422);
        }
        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }

        $data = $request->validate([
            'empresa' => 'required|string|max:40',
            'card_code' => 'required|string|max:40',
            'cliente' => 'nullable|string|max:180',
            'anio_proyeccion' => 'nullable|integer|min:2000|max:2100',
            'solo_vacios' => 'nullable|boolean',
            'sync_precio_global' => 'nullable|boolean',
            'items' => 'nullable|array|max:2000',
            'items.*.item_code' => 'required_with:items|string|max:80',
            'items.*.producto' => 'nullable|string|max:180',
            'items.*.moneda' => 'nullable|string|max:10',
        ]);

        $empresa = strtoupper(trim($data['empresa']));
        if (! in_array($empresa, ['AUSTIN', 'IMSA', 'PITIC', 'SYDNEY'], true)) {
            return response()->json(['message' => 'Empresa no válida.'], 422);
        }
        $card = trim($data['card_code']);
        $anioProy = $this->anioProyeccionCostos((int) ($data['anio_proyeccion'] ?? 0));
        $soloVacios = ! array_key_exists('solo_vacios', $data) || (bool) $data['solo_vacios'];
        $syncPrecioGlobal = ! array_key_exists('sync_precio_global', $data) || (bool) $data['sync_precio_global'];
        $userId = optional($request->user())->id;
        $hasCard = Schema::hasColumn('tbl_pv_productos_costo', 'card_code');
        $hasMes = Schema::hasColumn('tbl_pv_productos_costo', 'mes');
        $hasAnio = $this->hasAnioCostos();

        /** @var array<string, array{item_code: string, producto: string, moneda: string}> $locales */
        $locales = [];
        if (! empty($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $it) {
                if (! is_array($it)) {
                    continue;
                }
                $item = trim((string) ($it['item_code'] ?? ''));
                if ($item === '') {
                    continue;
                }
                $locales[strtoupper($item)] = [
                    'item_code' => $item,
                    'producto' => trim((string) ($it['producto'] ?? $item)),
                    'moneda' => strtoupper(trim((string) ($it['moneda'] ?? 'MXN'))) ?: 'MXN',
                ];
            }
        }

        if (! $locales) {
            $q = PvProductoCosto::query()->whereRaw('UPPER(empresa) = ?', [$empresa]);
            if ($hasAnio) {
                $q->where('anio', $anioProy);
            }
            if ($hasCard) {
                $q->where('card_code', $card);
            }
            if ($hasMes) {
                $q->where('mes', 0);
            }
            if ($soloVacios) {
                $q->where(function ($w) {
                    $w->whereNull('precio_lista')->orWhere('precio_lista', '<=', 0);
                });
            }
            foreach ($q->get(['producto_codigo', 'producto_nombre', 'moneda']) as $row) {
                $item = trim((string) $row->producto_codigo);
                if ($item === '') {
                    continue;
                }
                $locales[strtoupper($item)] = [
                    'item_code' => $item,
                    'producto' => trim((string) ($row->producto_nombre ?: $item)),
                    'moneda' => strtoupper((string) ($row->moneda ?: 'MXN')) ?: 'MXN',
                ];
            }
        }

        if (! $locales) {
            return response()->json([
                'ok' => true,
                'empresa' => $empresa,
                'card_code' => $card,
                'actualizados' => 0,
                'omitidos_sin_lista' => 0,
                'message' => 'Sin productos pendientes de Precio lista para '.$card.'.',
            ]);
        }

        $api = app(AutinApiClient::class);
        $actualizados = 0;
        $actualizadosLista = 0;
        $actualizadosGlobal = 0;
        $omitidos = 0;
        $chunks = array_chunk(array_values($locales), 25);
        foreach ($chunks as $chunk) {
            $codigos = array_map(static function ($l) {
                return $l['item_code'];
            }, $chunk);
            try {
                $pack = $api->listaPreciosPorArticulosEmpresa($empresa, $card, $codigos, 5);
            } catch (Throwable $e) {
                return response()->json([
                    'ok' => false,
                    'empresa' => $empresa,
                    'card_code' => $card,
                    'message' => 'Error SAP: '.$e->getMessage(),
                ], 422);
            }
            if (empty($pack['ok'])) {
                return response()->json([
                    'ok' => false,
                    'empresa' => $empresa,
                    'card_code' => $card,
                    'message' => $pack['message'] ?? 'Sin conexión a listaPreciosventa/'.$empresa,
                ], 422);
            }
            $porItem = is_array($pack['por_item'] ?? null) ? $pack['por_item'] : [];
            foreach ($chunk as $local) {
                $item = $local['item_code'];
                $rows = $porItem[strtoupper($item)] ?? [];
                if (! is_array($rows) || ! $rows) {
                    $omitidos++;
                    continue;
                }
                $precio = 0.0;
                $moneda = $local['moneda'];
                foreach ($rows as $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    $art = trim((string) ($row['CodigoArticulo'] ?? $row['ItemCode'] ?? ''));
                    if ($art === '' || strcasecmp($art, $item) !== 0) {
                        continue;
                    }
                    $p = $this->precioPositivoLista($row);
                    if ($p <= 0) {
                        continue;
                    }
                    $precio = round($p, 4);
                    $moneda = $this->normalizarMonedaLista($row['Moneda'] ?? $row['Currency'] ?? $moneda);
                    break;
                }
                if ($precio <= 0) {
                    $omitidos++;
                    continue;
                }

                $lookup = [
                    'empresa' => $empresa,
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
                $row = PvProductoCosto::query()->where($lookup)->first();
                if (! $row) {
                    $omitidos++;
                    continue;
                }
                $listaLocal = (float) ($row->precio_lista ?? 0);
                $globalLocal = (float) ($row->costo_unitario ?? 0);
                $listaIgual = abs($listaLocal - $precio) < 0.0001;
                $globalIgual = abs($globalLocal - $precio) < 0.0001;
                $necesitaLista = ! $listaIgual;
                $necesitaGlobal = $syncPrecioGlobal && ! $globalIgual;

                if ($soloVacios && $listaLocal > 0 && $listaIgual && ! $necesitaGlobal) {
                    continue;
                }
                if (! $necesitaLista && ! $necesitaGlobal) {
                    continue;
                }

                $cambio = false;
                if ($necesitaLista) {
                    $row->precio_lista = $precio;
                    $actualizadosLista++;
                    $cambio = true;
                }
                if ($necesitaGlobal) {
                    $precioAnterior = $globalLocal;
                    $monedaAnterior = strtoupper((string) ($row->moneda ?: 'MXN')) ?: 'MXN';
                    $row->costo_unitario = $precio;
                    if ($moneda !== '') {
                        $row->moneda = $moneda;
                    }
                    $this->registrarHistorialPrecio(
                        $empresa,
                        $item,
                        (string) ($row->producto_nombre ?: ($local['producto'] ?? $item)),
                        $precioAnterior,
                        $monedaAnterior,
                        $precio,
                        $moneda,
                        'lista_precios',
                        $userId,
                        $card,
                        trim((string) ($data['cliente'] ?? '')),
                        0,
                        $anioProy
                    );
                    $actualizadosGlobal++;
                    $cambio = true;
                } elseif ($moneda !== '' && ! $row->moneda) {
                    $row->moneda = $moneda;
                }

                if (! $cambio) {
                    continue;
                }
                $row->updated_by = $userId;
                $row->save();
                $actualizados++;
            }
        }

        return response()->json([
            'ok' => true,
            'empresa' => $empresa,
            'card_code' => $card,
            'endpoint' => 'listaPreciosventa/'.$empresa,
            'actualizados' => $actualizados,
            'actualizados_lista' => $actualizadosLista,
            'actualizados_global' => $actualizadosGlobal,
            'sync_precio_global' => $syncPrecioGlobal,
            'omitidos_sin_lista' => $omitidos,
            'message' => 'Lote '.$card.': '.$actualizados.' fila(s) actualizada(s)'
                .($actualizadosLista ? (' · Precio lista: '.$actualizadosLista) : '')
                .($actualizadosGlobal ? (' · Precio global: '.$actualizadosGlobal) : '')
                .($omitidos ? (' · '.$omitidos.' sin lista SAP') : '').'.',
        ]);
    }

    /**
     * Rellena Precio global ($0) de UN CardCode: consulta lista SAP y guarda local.
     * Pensado para ejecutarse en serie desde el front (un cliente por request).
     */
    public function rellenarCostosVaciosLote(Request $request): JsonResponse
    {
        if ($deny = $this->denyUnlessPuedeEditarPrecios()) {
            return $deny;
        }
        if (! Schema::hasTable('tbl_pv_productos_costo')) {
            return response()->json(['message' => 'Falta ejecutar la migración de costos de productos.'], 422);
        }
        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }

        $data = $request->validate([
            'empresa' => 'required|string|max:40',
            'card_code' => 'required|string|max:40',
            'cliente' => 'nullable|string|max:180',
            'anio_proyeccion' => 'nullable|integer|min:2000|max:2100',
            'items' => 'nullable|array|max:2000',
            'items.*.item_code' => 'required_with:items|string|max:80',
            'items.*.producto' => 'nullable|string|max:180',
            'items.*.moneda' => 'nullable|string|max:10',
        ]);

        $empresa = strtoupper(trim($data['empresa']));
        $card = trim($data['card_code']);
        $cliente = trim((string) ($data['cliente'] ?? ''));
        $anioProy = $this->anioProyeccionCostos((int) ($data['anio_proyeccion'] ?? 0));
        $userId = optional($request->user())->id;

        $hasCard = Schema::hasColumn('tbl_pv_productos_costo', 'card_code');
        $hasCardName = Schema::hasColumn('tbl_pv_productos_costo', 'card_name');
        $hasMes = Schema::hasColumn('tbl_pv_productos_costo', 'mes');
        $hasAnio = $this->hasAnioCostos();

        /** @var array<string, array{item_code: string, producto: string, moneda: string}> $locales */
        $locales = [];
        if (! empty($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $it) {
                if (! is_array($it)) {
                    continue;
                }
                $item = trim((string) ($it['item_code'] ?? ''));
                if ($item === '') {
                    continue;
                }
                $locales[strtoupper($item)] = [
                    'item_code' => $item,
                    'producto' => trim((string) ($it['producto'] ?? $item)),
                    'moneda' => strtoupper(trim((string) ($it['moneda'] ?? 'MXN'))) ?: 'MXN',
                ];
            }
        }

        if (! $locales) {
            $q = PvProductoCosto::query()->whereRaw('UPPER(empresa) = ?', [$empresa]);
            if ($hasAnio) {
                $q->where('anio', $anioProy);
            }
            if ($hasCard) {
                $q->where('card_code', $card);
            }
            if ($hasMes) {
                $q->where('mes', 0);
            }
            $q->where(function ($w) {
                $w->whereNull('costo_unitario')->orWhere('costo_unitario', '<=', 0);
            });
            foreach ($q->get(['producto_codigo', 'producto_nombre', 'moneda', 'card_name']) as $row) {
                $item = trim((string) $row->producto_codigo);
                if ($item === '') {
                    continue;
                }
                if ($cliente === '' && $hasCardName) {
                    $cliente = trim((string) ($row->card_name ?? ''));
                }
                $locales[strtoupper($item)] = [
                    'item_code' => $item,
                    'producto' => trim((string) ($row->producto_nombre ?: $item)),
                    'moneda' => strtoupper((string) ($row->moneda ?: 'MXN')) ?: 'MXN',
                ];
            }
        }

        if (! $locales) {
            return response()->json([
                'ok' => true,
                'empresa' => $empresa,
                'card_code' => $card,
                'importados' => 0,
                'omitidos_sin_lista' => 0,
                'pendientes' => 0,
                'message' => 'Sin productos en $0 para '.$card.'.',
            ]);
        }

        $items = array_values(array_map(static function ($l) {
            return $l['item_code'];
        }, $locales));

        try {
            $pack = $this->cargarListasPreciosCliente($empresa, $card, $anioProy, $items, false);
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'empresa' => $empresa,
                'card_code' => $card,
                'message' => 'Error SAP: '.$e->getMessage(),
            ], 422);
        }
        if (empty($pack['ok'])) {
            return response()->json([
                'ok' => false,
                'empresa' => $empresa,
                'card_code' => $card,
                'message' => $pack['mensaje'] ?? 'Sin conexión a listaPreciosventa',
            ], 422);
        }

        $porArt = is_array($pack['por_articulo'] ?? null) ? $pack['por_articulo'] : [];
        $afectados = [];
        $omitidosSinLista = 0;
        foreach ($locales as $key => $local) {
            $item = $local['item_code'];
            $lista = $porArt[$item] ?? $porArt[$key] ?? $porArt[$this->codigoCuentaKey($item)] ?? null;
            if (! is_array($lista) || (float) ($lista['precio'] ?? 0) <= 0) {
                $omitidosSinLista++;
                continue;
            }
            $precio = round((float) $lista['precio'], 4);
            $moneda = strtoupper(trim((string) ($lista['moneda'] ?? $local['moneda'] ?? 'MXN'))) ?: 'MXN';
            if (! in_array($moneda, ['MXN', 'USD'], true)) {
                $moneda = 'MXN';
            }
            $nombreLista = trim((string) ($lista['nombre'] ?? ''));
            $afectados[] = [
                'empresa' => $empresa,
                'card_code' => $card,
                'cliente' => $cliente,
                'item_code' => $item,
                'producto' => $nombreLista !== '' ? $nombreLista : $local['producto'],
                'precio' => $precio,
                'moneda' => $moneda,
            ];
        }

        if (! $afectados) {
            return response()->json([
                'ok' => true,
                'empresa' => $empresa,
                'card_code' => $card,
                'cliente' => $cliente,
                'pendientes' => count($locales),
                'importados' => 0,
                'omitidos_sin_lista' => $omitidosSinLista,
                'message' => 'Ningún ítem de '.$card.' tiene precio en lista SAP.',
            ]);
        }

        $resp = $this->aplicarAfectadosDesdeListaPrecios($afectados, $anioProy, $userId, true);
        $body = $resp->getData(true);
        if (! is_array($body)) {
            $body = [];
        }
        $body['ok'] = true;
        $body['empresa'] = $empresa;
        $body['card_code'] = $card;
        $body['cliente'] = $cliente;
        $body['pendientes'] = count($locales);
        $body['omitidos_sin_lista'] = $omitidosSinLista;
        if (empty($body['message'])) {
            $body['message'] = 'Lote '.$card.': '.((int) ($body['importados'] ?? 0)).' actualizado(s).';
        }

        return response()->json($body, $resp->getStatusCode());
    }

    /**
     * Persiste precios globales ya validados en el preview de lista SAP (sin reconsultar API).
     *
     * @param  array<int, array<string, mixed>>  $afectados
     */
    protected function aplicarAfectadosDesdeListaPrecios(array $afectados, int $anioProy, $userId, bool $soloVacios = false): JsonResponse
    {
        $hasCard = Schema::hasColumn('tbl_pv_productos_costo', 'card_code');
        $hasCardName = Schema::hasColumn('tbl_pv_productos_costo', 'card_name');
        $hasMes = Schema::hasColumn('tbl_pv_productos_costo', 'mes');
        $hasAnio = $this->hasAnioCostos();
        $importados = 0;
        $omitidosConPrecio = 0;
        $propagadasTot = 0;
        $guardados = [];

        foreach ($afectados as $it) {
            if (! is_array($it)) {
                continue;
            }
            $emp = strtoupper(trim((string) ($it['empresa'] ?? '')));
            $card = trim((string) ($it['card_code'] ?? ''));
            $item = trim((string) ($it['item_code'] ?? ''));
            $precio = round((float) ($it['precio'] ?? 0), 4);
            if ($emp === '' || $item === '' || $precio <= 0) {
                continue;
            }
            $moneda = strtoupper(trim((string) ($it['moneda'] ?? 'MXN'))) ?: 'MXN';
            if (! in_array($moneda, ['MXN', 'USD'], true)) {
                $moneda = 'MXN';
            }
            $cliente = trim((string) ($it['cliente'] ?? ''));
            $nombre = trim((string) ($it['producto'] ?? $item));

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

            // No pisar precios ya capturados cuando solo se rellenan ceros.
            if ($soloVacios && ! $esNuevo && $precioAnterior !== null && $precioAnterior > 0) {
                $omitidosConPrecio++;
                continue;
            }

            if ($hasAnio) {
                $row->anio = $anioProy;
            }
            if ($hasCard) {
                $row->card_code = $card;
            }
            if ($hasCardName && $cliente !== '') {
                $row->card_name = mb_substr($cliente, 0, 180);
            }
            if ($hasMes) {
                $row->mes = 0;
            }
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
                $cliente,
                0,
                $anioProy
            );
            $propagadasTot += $this->propagarCostoACiclosAbiertos($emp, $item, $precio);
            $importados++;
            if (count($guardados) < 50) {
                $guardados[] = [
                    'empresa' => $emp,
                    'card_code' => $card,
                    'item_code' => $item,
                    'precio' => $precio,
                    'moneda' => $moneda,
                ];
            }
        }

        return response()->json([
            'ok' => true,
            'preview' => false,
            'solo_vacios' => $soloVacios,
            'anio_proyeccion' => $anioProy,
            'origen' => 'listaPreciosventa',
            'importados' => $importados,
            'omitidos_con_precio' => $omitidosConPrecio,
            'propagadas' => $propagadasTot,
            'afectados_total' => $importados,
            'afectados' => $guardados,
            'message' => ($soloVacios ? 'Precio global ($0) actualizado en ' : 'Precio global actualizado en ')
                .$importados.' producto(s)'
                .($omitidosConPrecio ? (' · '.$omitidosConPrecio.' omitido(s) ya tenían precio') : '')
                .($propagadasTot ? (' · '.$propagadasTot.' proyección(es) abiertas') : '').'.',
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
            $key = $codigo;
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
     * Una sola lectura indexada por año. Las dos vistas del maestro la reutilizan.
     *
     * @return \Illuminate\Support\Collection<int, PvProductoCosto>
     */
    protected function filasCostoMaestro(?string $empresa, ?int $anio)
    {
        $anio = $this->anioProyeccionCostos($anio);
        $emp = $empresa ? strtoupper(trim($empresa)) : '';
        $key = $emp.'|'.$anio;
        if (array_key_exists($key, $this->pvFilasCostoMemo)) {
            return $this->pvFilasCostoMemo[$key];
        }
        if (! Schema::hasTable('tbl_pv_productos_costo')) {
            return $this->pvFilasCostoMemo[$key] = collect();
        }

        $q = PvProductoCosto::query();
        if ($this->hasAnioCostos()) {
            $q->where('anio', $anio);
        }
        if ($emp !== '') {
            $q->where('empresa', $emp);
        }
        $cols = ['id', 'empresa', 'producto_codigo', 'costo_unitario', 'moneda', 'updated_at'];
        if (Schema::hasColumn('tbl_pv_productos_costo', 'card_code')) {
            $cols[] = 'card_code';
        }
        if (Schema::hasColumn('tbl_pv_productos_costo', 'mes')) {
            $cols[] = 'mes';
        }
        if (Schema::hasColumn('tbl_pv_productos_costo', 'precio_lista')) {
            $cols[] = 'precio_lista';
        }

        return $this->pvFilasCostoMemo[$key] = $q->orderByDesc('updated_at')->orderByDesc('id')->get($cols);
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
        $hasCard = Schema::hasColumn('tbl_pv_productos_costo', 'card_code');
        $hasMes = Schema::hasColumn('tbl_pv_productos_costo', 'mes');
        $hasPrecioLista = Schema::hasColumn('tbl_pv_productos_costo', 'precio_lista');

        $groups = [];
        $this->filasCostoMaestro($empresa, $anio)
            ->each(function (PvProductoCosto $row) use (&$groups, $hasCard, $hasMes, $hasPrecioLista) {
                $emp = strtoupper(trim((string) $row->empresa));
                $cod = trim((string) $row->producto_codigo);
                if ($emp === '' || $cod === '') {
                    return;
                }
                $precio = (float) $row->costo_unitario;
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
                        'global_explicito' => false,
                        'precio_lista' => null,
                        'meses' => [],
                    ];
                }
                if ($mes === 0) {
                    // Primera fila global (updated_at desc), incluso si es 0 (precio forzado a cero).
                    if ($groups[$key]['global'] === null) {
                        $groups[$key]['global'] = $precio;
                        $groups[$key]['global_moneda'] = $moneda;
                        $groups[$key]['global_explicito'] = true;
                    }
                    // Precio lista: tomar el primero > 0 aunque no sea la fila global elegida.
                    if ($groups[$key]['precio_lista'] === null
                        && $hasPrecioLista
                        && $row->precio_lista !== null
                        && (float) $row->precio_lista > 0) {
                        $groups[$key]['precio_lista'] = round((float) $row->precio_lista, 4);
                    }

                    return;
                }
                if ($precio <= 0) {
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
        $put = static function (string $k, array $entry, bool $allowZero = false) use (&$out) {
            if ($k === '' || isset($out[$k])) {
                return;
            }
            $costo = (float) ($entry['costo'] ?? 0);
            if ($costo <= 0 && ! $allowZero) {
                return;
            }
            $out[$k] = $entry;
        };

        foreach ($groups as $g) {
            $costo = null;
            $moneda = 'MXN';
            $mesRef = null;
            $ceroExplicito = false;
            if ($g['global_explicito']) {
                $costo = (float) $g['global'];
                $moneda = (string) $g['global_moneda'];
                $mesRef = null;
                $ceroExplicito = $costo <= 0;
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
            if ($costo === null) {
                continue;
            }
            if ($costo <= 0 && ! $ceroExplicito) {
                continue;
            }
            $entry = [
                'costo' => round((float) $costo, 4),
                'moneda' => $moneda,
                'mes' => $mesRef,
                'card_code' => $g['card'],
                'origen' => 'maestro_local',
                'explicito' => $ceroExplicito || ($g['global_explicito'] && (float) $g['global'] >= 0),
                'precio_lista' => isset($g['precio_lista']) && (float) $g['precio_lista'] > 0
                    ? round((float) $g['precio_lista'], 4)
                    : null,
            ];
            // Precio 0 solo se indexa por Empresa+Card+Item (no contaminar EMP|Item de otros clientes).
            if ($g['card'] !== '') {
                $put($g['emp'].'|'.$g['card'].'|'.$g['cod'], $entry, $ceroExplicito);
            }
            if (! $ceroExplicito) {
                $put($g['emp'].'|'.$g['cod'], $entry, false);
                $put($g['cod'], $entry, false);
            }
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
        $hasCard = Schema::hasColumn('tbl_pv_productos_costo', 'card_code');
        $hasMes = Schema::hasColumn('tbl_pv_productos_costo', 'mes');
        if (! $hasMes) {
            return [];
        }

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

        $this->filasCostoMaestro($empresa, $anio)->each(function (PvProductoCosto $row) use ($put, $hasCard) {
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
                // Solo clave exacta cliente+producto: no contaminar EMP|Item con otro CardCode.
                $put($emp.'|'.$card.'|'.$cod, $idx, $precio, $moneda);
            } else {
                $put($emp.'|'.$cod, $idx, $precio, $moneda);
                $put($cod, $idx, $precio, $moneda);
            }
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

    /**
     * Clientes ligeros solo para /Ventas/Asignaciones/.../asignar.
     * No descarga todas las líneas de venta: catálogo local + búsqueda SAP filtrada por empresa.
     */
    public function clientesAsignacion(Request $request): JsonResponse
    {
        $empresa = strtolower(trim((string) $request->get('empresa', '')));
        $year = (int) $request->get('year', date('Y'));
        $q = trim((string) $request->get('q', $request->get('buscar', '')));
        $force = $request->boolean('force');
        $empresasOk = ['austin', 'imsa', 'pitic', 'sydney'];
        if (! in_array($empresa, $empresasOk, true)) {
            return response()->json(['ok' => false, 'centros' => [], 'mensaje' => 'Empresa inválida'], 422);
        }
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }
        if (function_exists('set_time_limit')) {
            @set_time_limit(90);
        }

        try {
            $map = $this->clientesLocalesAsignacion($empresa, $q);
            $fuente = 'local';
            $mensaje = null;

            if ($q !== '' && mb_strlen($q) >= 2) {
                $sap = $this->buscarClientesSapAsignacion($empresa, $year, $q);
                if (! empty($sap['clientes'])) {
                    $this->guardarClientesCatalogo($empresa, $year, $sap['clientes'], 'sap_busca');
                    foreach ($sap['clientes'] as $row) {
                        $key = strtoupper((string) ($row['codigo'] ?? ''));
                        if ($key === '') {
                            continue;
                        }
                        $map[$key] = $row;
                    }
                    $fuente = 'sap_busca';
                } elseif (! empty($sap['mensaje']) && $map === []) {
                    $mensaje = $sap['mensaje'];
                }
            } elseif ($force || count($map) < 5) {
                $sap = $this->sembrarClientesSapAsignacion($empresa, $year);
                if (! empty($sap['clientes'])) {
                    $this->guardarClientesCatalogo($empresa, $year, $sap['clientes'], 'sap_semilla');
                    foreach ($sap['clientes'] as $row) {
                        $key = strtoupper((string) ($row['codigo'] ?? ''));
                        if ($key === '') {
                            continue;
                        }
                        if (! isset($map[$key])) {
                            $map[$key] = $row;
                        }
                    }
                    $fuente = count($map) > count($sap['clientes']) ? 'local+sap' : 'sap_semilla';
                    $mensaje = $sap['mensaje'];
                } elseif ($map === [] && ! empty($sap['mensaje'])) {
                    $mensaje = $sap['mensaje'];
                }
            }

            $clientes = array_values($map);
            usort($clientes, function ($a, $b) {
                return strcasecmp((string) ($a['nombre'] ?? ''), (string) ($b['nombre'] ?? ''))
                    ?: strcasecmp((string) ($a['codigo'] ?? ''), (string) ($b['codigo'] ?? ''));
            });

            if ($clientes && $mensaje === null && $fuente !== 'sap_busca') {
                $mensaje = 'Escribe código o nombre para buscar más clientes en SAP de esta empresa.';
            }
            if (! $clientes && $mensaje === null) {
                $mensaje = $q !== ''
                    ? 'Sin coincidencias. Prueba otro código o nombre.'
                    : 'Sin clientes locales. Escribe en el buscador para traer clientes de SAP.';
            }

            return response()->json([
                'ok' => $clientes !== [] || $fuente === 'local',
                'centros' => $clientes,
                'mensaje' => $mensaje,
                'fuente' => $fuente,
                'empresa' => $empresa,
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

    /**
     * Productos ligeros solo para /Ventas/Asignaciones/.../asignar.
     * Filtra por empresa + cliente; catálogo local + SAP con CardCode (sin bajar todo el año).
     */
    public function productosAsignacion(Request $request): JsonResponse
    {
        $empresa = strtolower(trim((string) $request->get('empresa', '')));
        $cliente = trim((string) $request->get('cliente', $request->get('cc', '')));
        $year = (int) $request->get('year', date('Y'));
        $q = trim((string) $request->get('q', $request->get('buscar', '')));
        $todas = $request->boolean('todas');
        $force = $request->boolean('force');
        $empresasOk = ['austin', 'imsa', 'pitic', 'sydney'];
        if (! in_array($empresa, $empresasOk, true)) {
            return response()->json(['ok' => false, 'cuentas' => [], 'mensaje' => 'Empresa inválida'], 422);
        }
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }
        if (! $todas && $cliente === '') {
            return response()->json([
                'ok' => true,
                'cuentas' => [],
                'agrupaciones' => [],
                'mensaje' => 'Elige un cliente para ver sus productos.',
            ]);
        }
        if (function_exists('set_time_limit')) {
            @set_time_limit(90);
        }

        try {
            $map = [];
            $fuente = 'sap';
            $mensaje = null;

            if ($todas) {
                // “Ver todos”: catálogo local de la empresa (no es lista de ventas del cliente).
                $map = $this->productosLocalesAsignacion($empresa, '', $q);
                $fuente = 'local';
                if ($map === []) {
                    $mensaje = 'Para ver todos los productos usa el buscador, o desactiva “Ver todos” y elige un cliente.';
                } else {
                    $mensaje = 'Catálogo local de la empresa. Usa el buscador para afinar.';
                }
            } else {
                // Cliente concreto: solo productos con venta en SAP. Sin fallback local.
                $sap = $this->cargarProductosSapAsignacion($empresa, $cliente, $year, $q);
                if (! empty($sap['productos'])) {
                    $this->guardarProductosCatalogo($empresa, $cliente, $year, $sap['productos'], $q !== '' ? 'sap_busca' : 'sap');
                    foreach ($sap['productos'] as $row) {
                        $key = strtoupper((string) ($row['codigo'] ?? ''));
                        if ($key === '') {
                            continue;
                        }
                        $map[$key] = $row;
                    }
                    $fuente = 'sap';
                    $mensaje = $sap['mensaje'];
                } else {
                    $map = [];
                    $fuente = 'sap';
                    if (! empty($sap['ok'])) {
                        $mensaje = $q !== ''
                            ? ('No se encontraron ventas para “'.$q.'” en este cliente ('.date('Y').' ene–sep).')
                            : ('No se encontraron ventas para este cliente ('.date('Y').' ene–sep).');
                    } else {
                        $mensaje = $sap['mensaje'] ?? ('No se encontraron ventas para este cliente ('.date('Y').' ene–sep).');
                    }
                }
            }

            $productos = array_values($map);
            usort($productos, function ($a, $b) {
                return strcasecmp((string) ($a['nombre'] ?? ''), (string) ($b['nombre'] ?? ''))
                    ?: strcasecmp((string) ($a['codigo'] ?? ''), (string) ($b['codigo'] ?? ''));
            });

            if ($todas && $productos && $mensaje === null) {
                $mensaje = 'Catálogo local. Si falta alguno, escribe en el buscador de productos.';
            }
            if (! $productos && $mensaje === null) {
                $mensaje = $todas
                    ? ($q !== '' ? 'Sin productos para “'.$q.'”.' : 'Sin productos en el catálogo.')
                    : 'No se encontraron ventas para este cliente.';
            }

            return response()->json([
                'ok' => true,
                'cuentas' => $productos,
                'agrupaciones' => [],
                'mensaje' => $mensaje,
                'fuente' => $fuente,
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
            ->where('ciclo_codigo', strtoupper($ciclo));
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

        if (is_array($request->input('asignaciones'))) {
            return $this->storeAsignacionesLote($request, $ciclo);
        }

        $data = $this->validarFilaAsignacion($request->all());
        $this->assertUsuariosExisten([$data]);
        $creadosMaestro = 0;
        $asig = DB::transaction(function () use ($ciclo, $data, &$creadosMaestro) {
            return $this->persistirFilaAsignacion($ciclo, $data, $creadosMaestro);
        });
        $asig->load(['usuario', 'cuentas', 'permisos.tipo']);

        return response()->json([
            'ok' => true,
            'asignacion' => $this->asignacionPayload($asig),
            'maestro_creados' => $creadosMaestro,
        ]);
    }

    protected function storeAsignacionesLote(Request $request, string $ciclo): JsonResponse
    {
        $data = $request->validate([
            'asignaciones' => 'required|array|min:1|max:300',
        ]);
        $filas = [];
        foreach ($data['asignaciones'] as $i => $fila) {
            if (! is_array($fila)) {
                return response()->json(['message' => 'La fila '.($i + 1).' no es válida.'], 422);
            }
            $filas[] = $this->validarFilaAsignacion($fila);
        }
        $this->assertUsuariosExisten($filas);

        $creadosMaestro = 0;
        $ids = [];
        DB::transaction(function () use ($ciclo, $filas, &$creadosMaestro, &$ids) {
            foreach ($filas as $fila) {
                $asig = $this->persistirFilaAsignacion($ciclo, $fila, $creadosMaestro);
                $ids[] = (int) $asig->id;
            }
        });

        $cargadas = PvAsignacion::query()->with(['usuario', 'cuentas', 'permisos.tipo'])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');
        $out = [];
        foreach ($ids as $id) {
            $row = $cargadas->get($id);
            if ($row) {
                $out[] = $this->asignacionPayload($row);
            }
        }

        return response()->json([
            'ok' => true,
            'asignaciones' => $out,
            'maestro_creados' => $creadosMaestro,
        ]);
    }

    /**
     * @param  array<string, mixed>  $fila
     * @return array<string, mixed>
     */
    protected function validarFilaAsignacion(array $fila): array
    {
        return validator($fila, [
            'empresa' => 'required|string|max:40',
            'user_id' => 'required|integer|min:1',
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
        ])->validate();
    }

    /**
     * @param  array<int, array<string, mixed>>  $filas
     */
    protected function assertUsuariosExisten(array $filas): void
    {
        $ids = [];
        foreach ($filas as $fila) {
            $ids[(int) ($fila['user_id'] ?? 0)] = true;
        }
        unset($ids[0]);
        if (! $ids) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'user_id' => 'El usuario no existe.',
            ]);
        }
        $encontrados = User::query()->whereIn('id', array_keys($ids))->count();
        if ($encontrados !== count($ids)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'user_id' => 'El usuario no existe.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function persistirFilaAsignacion(string $ciclo, array $data, int &$creadosMaestro): PvAsignacion
    {
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

        $codigoCliente = strtoupper(preg_replace('/\s+/', '', (string) ($data['centro_codigo'] ?? '')));
        $cuentas = $codigoCliente === 'EMPRESA'
            ? []
            : (is_array($data['cuentas'] ?? null) ? $data['cuentas'] : []);
        $this->syncCuentas($asig, $cuentas);
        $this->syncPermisosClaves($asig, $data['permisos'] ?? ['capturar']);
        $creadosMaestro += $this->asegurarMaestroDesdeAsignacion($asig, $cuentas);

        return $asig;
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
        $esEmpresa = strtoupper(preg_replace('/\s+/', '', (string) $asig->centro_codigo)) === 'EMPRESA';
        DB::transaction(function () use ($asig, $data, $esEmpresa, &$creadosMaestro) {
            if (array_key_exists('cuentas', $data)) {
                $cuentas = $esEmpresa ? [] : $data['cuentas'];
                $this->syncCuentas($asig, $cuentas);
                if (! $esEmpresa) {
                $this->propagarCuentasAColaboradores($asig);
                    $creadosMaestro = $this->asegurarMaestroDesdeAsignacion($asig, $cuentas);
                }
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
        $asig = PvAsignacion::query()
            ->where('id', $id)
            ->whereRaw('UPPER(ciclo_codigo) = ?', [strtoupper($ciclo)])
            ->first();
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
                ->where('empresa', $empresa)
                ->where('cliente_codigo', $cc)
                ->where('anio', $year)
                ->first();
            if ($snap && $this->ventaRealSnapshotSirve($snap->por_cuenta)) {
                if ($this->mesesPorRellenar(is_array($snap->por_cuenta) ? $snap->por_cuenta : []) !== []) {
                    $snap = $this->rellenarMesesIniciales($snap, $empresa, $cc, $year);
                }
                $payload = [
                    'ok' => true,
                    'year' => $year,
                    'por_cuenta' => $this->porCuentaSinMeta(is_array($snap->por_cuenta) ? $snap->por_cuenta : []),
                    'mensaje' => null,
                    'fuente' => 'snapshot',
                    'budget_completo' => $this->budgetCompletoDe($snap->por_cuenta),
                    'synced_at' => $snap->synced_at ? $snap->synced_at->format('Y-m-d H:i') : null,
                ];
                $cacheKey = 'pv.venta-real.v6.'.$empresa.'.'.$cc.'.'.$year;
                Cache::put($cacheKey, $payload, 900);

                return response()->json($payload);
            }
        }

        // 2) Cache RAM corta (útil mientras se escribe el snapshot).
        $cacheKey = 'pv.venta-real.v6.'.$empresa.'.'.$cc.'.'.$year;
        if (! $force) {
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && ! empty($cached['ok'])) {
                $cached['fuente'] = $cached['fuente'] ?? 'cache';

            return response()->json($cached);
            }
        } else {
            Cache::forget($cacheKey);
        }

        // 3) API SAP → guardar snapshot. Un corte no se manda al navegador:
        // la API entrega de la fecha más nueva a la más vieja y esos meses se pintarían como el año.
        @set_time_limit(300);
        try {
            $payload = $this->cargarGastoRealCentro($empresa, $cc, $year);
            if (! empty($payload['ok'])) {
                $stored = is_array($payload['por_cuenta'] ?? null) ? $payload['por_cuenta'] : [];
                $stored = $this->guardarVentaRealSnapshot(
                    $empresa,
                    $cc,
                    $year,
                    $stored,
                    optional($request->user())->id
                );
                $payload['budget_completo'] = $this->budgetCompletoDe($stored);
                $payload['por_cuenta'] = $this->porCuentaSinMeta($stored);
                $payload['fuente'] = 'api';
                $payload['synced_at'] = now()->format('Y-m-d H:i');
                Cache::put($cacheKey, $payload, 900);
            } else {
                $snapPrevio = $this->snapshotVentaRealGuardado($empresa, $cc, $year);
                if ($snapPrevio) {
                    return $this->jsonVentaRealSnapshot(
                        $snapPrevio,
                        $year,
                        $payload['mensaje'] ?? 'La bajada nueva no terminó; se dejó la venta ya guardada.',
                        'snapshot_fallback'
                    );
                }
                $payload['ok'] = false;
                $payload['por_cuenta'] = (object) [];
                $payload['fuente'] = 'incompleto';
            }
        } catch (Throwable $e) {
            $snapPrevio = $this->snapshotVentaRealGuardado($empresa, $cc, $year);
            if ($snapPrevio) {
                return $this->jsonVentaRealSnapshot(
                    $snapPrevio,
                    $year,
                    'API no disponible; se usó la venta ya guardada ('.$e->getMessage().').',
                    'snapshot_fallback'
                );
            }

            return response()->json([
                'ok' => false,
                'year' => $year,
                'por_cuenta' => (object) [],
                'mensaje' => $e->getMessage(),
                'fuente' => 'error',
            ], 200);
        }

        $ligero = filter_var($request->get('ligero', false), FILTER_VALIDATE_BOOLEAN);
        if (! $ligero && ! empty($payload['ok']) && ! empty($payload['por_cuenta'])) {
            $this->asegurarCostosMaestroDesdePorCuenta($empresa, $payload['por_cuenta']);
        }

        return response()->json($payload);
    }

    /**
     * Batch de venta real desde snapshot local (sin SAP).
     * Body JSON: { year, clientes: [{ empresa, cc }, ...] }
     * Respuesta: por_cliente["EMPRESA|CC"] = { por_cuenta, fuente, synced_at }
     */
    public function gastoRealBatch(Request $request): JsonResponse
    {
        $year = (int) $request->input('year', $request->input('anio', 0));
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }

        $raw = $request->input('clientes', []);
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($raw)) {
            $raw = [];
        }

        $alias = ['ABSA' => 'AUSTIN'];
        /** @var array<string, array{empresa: string, cc: string}> $wanted */
        $wanted = [];
        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }
            $empresa = strtoupper(trim((string) ($item['empresa'] ?? '')));
            $cc = trim((string) ($item['cc'] ?? $item['cliente'] ?? $item['codigo'] ?? ''));
            if (isset($alias[$empresa])) {
                $empresa = $alias[$empresa];
            }
            if ($empresa === '' || $cc === '') {
                continue;
            }
            $key = $empresa.'|'.$cc;
            $wanted[$key] = ['empresa' => $empresa, 'cc' => $cc];
        }

        if ($wanted === []) {
            return response()->json([
                'ok' => true,
                'year' => $year,
                'por_cliente' => (object) [],
                'faltantes' => [],
                'mensaje' => 'Sin clientes solicitados.',
            ]);
        }

        if (! Schema::hasTable('tbl_pv_venta_real_snapshot')) {
            return response()->json([
                'ok' => true,
                'year' => $year,
                'por_cliente' => (object) [],
                'faltantes' => array_keys($wanted),
                'mensaje' => 'Tabla de snapshot no disponible.',
            ]);
        }

        $rows = collect();
        foreach (array_chunk(array_values($wanted), 120) as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '(?,?)'));
            $bindings = [];
            foreach ($chunk as $w) {
                $bindings[] = $w['empresa'];
                $bindings[] = $w['cc'];
            }
            $part = PvVentaRealSnapshot::query()
                ->where('anio', $year)
                ->whereRaw('(empresa, cliente_codigo) IN ('.$placeholders.')', $bindings)
                ->get(['empresa', 'cliente_codigo', 'anio', 'por_cuenta', 'synced_at']);
            $rows = $rows->concat($part);
        }

        /** @var array<string, array<string, mixed>> $porCliente */
        $porCliente = [];
        foreach ($rows as $snap) {
            $emp = strtoupper(trim((string) $snap->empresa));
            $cc = trim((string) $snap->cliente_codigo);
            $key = $emp.'|'.$cc;
            if (! isset($wanted[$key])) {
                // Match case-insensitive cliente
                $hitKey = null;
                foreach ($wanted as $wk => $w) {
                    if ($w['empresa'] === $emp && strcasecmp($w['cc'], $cc) === 0) {
                        $hitKey = $wk;
                        break;
                    }
                }
                if ($hitKey === null) {
                    continue;
                }
                $key = $hitKey;
            }
            $por = is_array($snap->por_cuenta) ? $snap->por_cuenta : [];
            if (! $this->ventaRealSnapshotSirve($por)) {
                continue;
            }
            $porCliente[$key] = [
                'empresa' => $emp,
                'cc' => $cc,
                'por_cuenta' => $this->porCuentaSinMeta($por),
                'fuente' => 'snapshot',
                'budget_completo' => $this->budgetCompletoDe($por),
                'rellenar' => $this->mesesPorRellenar($por) !== [],
                'synced_at' => $snap->synced_at ? $snap->synced_at->format('Y-m-d H:i') : null,
            ];
        }

        $faltantes = [];
        foreach (array_keys($wanted) as $key) {
            if (! isset($porCliente[$key])) {
                $faltantes[] = $key;
            }
        }

        return response()->json([
            'ok' => true,
            'year' => $year,
            'por_cliente' => $porCliente ?: (object) [],
            'faltantes' => $faltantes,
            'mensaje' => null,
        ]);
    }

    /**
     * Lo guardado se vuelve a mostrar al recargar. No se exige la bajada nueva:
     * si esa falla, la pantalla se quedaba en ceros.
     *
     * @param  mixed  $por
     */
    protected function ventaRealSnapshotSirve($por): bool
    {
        if (! is_array($por) || $por === []) {
            return false;
        }
        foreach ($por as $key => $item) {
            if ($key === '_meta') {
                continue;
            }
            if (is_array($item)) {
                return true;
            }
        }

        return false;
    }

    protected function snapshotVentaRealGuardado(string $empresa, string $cc, int $year): ?PvVentaRealSnapshot
    {
        if (! Schema::hasTable('tbl_pv_venta_real_snapshot')) {
            return null;
        }
        $snap = PvVentaRealSnapshot::query()
            ->where('empresa', $empresa)
            ->where('cliente_codigo', $cc)
            ->where('anio', $year)
            ->first();
        if (! $snap || ! $this->ventaRealSnapshotSirve($snap->por_cuenta)) {
            return null;
        }

        return $snap;
    }

    protected function jsonVentaRealSnapshot(PvVentaRealSnapshot $snap, int $year, ?string $mensaje = null, string $fuente = 'snapshot'): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'year' => $year,
            'por_cuenta' => $this->porCuentaSinMeta(is_array($snap->por_cuenta) ? $snap->por_cuenta : []),
            'mensaje' => $mensaje,
            'fuente' => $fuente,
            'budget_completo' => $this->budgetCompletoDe($snap->por_cuenta),
            'synced_at' => $snap->synced_at ? $snap->synced_at->format('Y-m-d H:i') : null,
        ]);
    }

    /**
     * Meses anteriores al primero que sí tiene venta.
     * /ventas viene de la fecha más nueva a la más vieja: si la bajada se corta,
     * se quedan junio–diciembre y enero–mayo salen en cero.
     *
     * @param  array<string, mixed>  $por
     * @return array<int, int> meses 1–12 todavía no confirmados
     */
    protected function mesesPorRellenar(array $por): array
    {
        $tiene = array_fill(1, 12, false);
        foreach ($por as $key => $item) {
            if ($key === '_meta' || ! is_array($item)) {
                continue;
            }
            for ($i = 0; $i < 12; $i++) {
                if ((float) ($item['importe'][$i] ?? 0) != 0.0
                    || (float) ($item['gasto'][$i] ?? 0) != 0.0
                    || (float) ($item['importe_usd'][$i] ?? 0) != 0.0) {
                    $tiene[$i + 1] = true;
                }
            }
        }
        $primero = null;
        for ($m = 1; $m <= 12; $m++) {
            if ($tiene[$m]) {
                $primero = $m;
                break;
            }
        }
        if ($primero === null || $primero <= 1) {
            return [];
        }
        $meta = is_array($por['_meta'] ?? null) ? $por['_meta'] : [];
        $ya = array_map('intval', (array) ($meta['meses_ok'] ?? []));
        $huecos = [];
        for ($m = 1; $m < $primero; $m++) {
            if (! in_array($m, $ya, true)) {
                $huecos[] = $m;
            }
        }

        return $huecos;
    }

    /**
     * Baja cada mes vacío por su propio rango de fechas y lo escribe en el snapshot
     * sin tocar los meses que ya tenían importe.
     */
    protected function rellenarMesesIniciales(PvVentaRealSnapshot $snap, string $empresa, string $cc, int $year): PvVentaRealSnapshot
    {
        $por = $this->colapsarPorCuentaProductos(is_array($snap->por_cuenta) ? $snap->por_cuenta : []);
        $huecos = $this->mesesPorRellenar($por);
        if ($huecos === []) {
            return $snap;
        }

        @set_time_limit(300);
        $desdeMes = $huecos[0];
        $hastaMes = $huecos[count($huecos) - 1];
        $fin = (int) date('t', strtotime(sprintf('%04d-%02d-01', $year, $hastaMes)));
        $pack = $this->filasVentasEmpresa(strtolower($empresa), $year, [
            'CardCode' => $cc,
            'fecha_desde' => sprintf('%04d/%02d/01', $year, $desdeMes),
            'fecha_hasta' => sprintf('%04d/%02d/%02d', $year, $hastaMes, $fin),
        ], 80);
        $rows = is_array($pack['rows'] ?? null) ? $pack['rows'] : [];
        if ($rows === [] && empty($pack['ok'])) {
            return $snap;
        }

        $traido = $this->cerrarPorCuentaVentas($this->agregarPorCuentaVentas($rows, $year, $cc));
        $meta = is_array($por['_meta'] ?? null) ? $por['_meta'] : [];
        $ok = array_map('intval', (array) ($meta['meses_ok'] ?? []));
        $escrito = false;
        foreach ($huecos as $mes) {
            $idx = $mes - 1;
            $mesTraido = false;
            foreach ($traido as $hit) {
                if (! is_array($hit)) {
                    continue;
                }
                if ((float) ($hit['importe'][$idx] ?? 0) != 0.0
                    || (float) ($hit['gasto'][$idx] ?? 0) != 0.0
                    || (float) ($hit['importe_usd'][$idx] ?? 0) != 0.0) {
                    $mesTraido = true;
                    break;
                }
            }
            if (! $mesTraido) {
                continue;
            }
            foreach ($traido as $cod => $hit) {
                if (! is_array($hit)) {
                    continue;
                }
                $clave = $this->claveProductoVenta(trim((string) ($hit['codigo'] ?? $cod)));
                if ($clave === '') {
                    continue;
                }
                if (! isset($por[$clave]) || ! is_array($por[$clave])) {
                    $hit['codigo'] = trim((string) ($hit['codigo'] ?? $cod));
                    $por[$clave] = $hit;

                    continue;
                }
                foreach (['gasto', 'importe', 'importe_usd'] as $field) {
                    $serie = $por[$clave][$field] ?? [];
                    if (! is_array($serie)) {
                        $serie = [];
                    }
                    $serie = array_pad(array_slice(array_values($serie), 0, 12), 12, 0.0);
                    $decimales = $field === 'gasto' ? 4 : 2;
                    $serie[$idx] = round((float) ($hit[$field][$idx] ?? 0), $decimales);
                    $por[$clave][$field] = $serie;
                }
                if (($por[$clave]['nombre'] ?? '') === '' && ! empty($hit['nombre'])) {
                    $por[$clave]['nombre'] = (string) $hit['nombre'];
                }
            }
            $ok[] = $mes;
            $escrito = true;
        }
        if (! empty($pack['ok'])) {
            foreach ($huecos as $mes) {
                $ok[] = $mes;
            }
            $escrito = true;
        }
        if (! $escrito) {
            return $snap;
        }
        $ok = array_values(array_unique($ok));
        sort($ok);
        $meta['meses_ok'] = $ok;
        $por['_meta'] = $meta;
        $snap->por_cuenta = $por;
        $snap->synced_at = now();
        $snap->save();
        Cache::forget('pv.venta-real.v6.'.strtoupper(trim($empresa)).'.'.trim($cc).'.'.$year);

        return $snap->refresh();
    }

    /**
     * @param  mixed  $por
     */
    protected function ventaRealSnapshotTieneImportes($por): bool
    {
        if (! is_array($por)) {
            return false;
        }
        foreach ($por as $key => $item) {
            if ($key === '_meta' || ! is_array($item)) {
                continue;
            }
            foreach (['importe', 'gasto', 'importe_usd'] as $field) {
                foreach ((array) ($item[$field] ?? []) as $n) {
                    if ((float) $n != 0.0) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param  mixed  $por
     */
    protected function budgetCompletoDe($por): bool
    {
        return is_array($por)
            && is_array($por['_meta'] ?? null)
            && ! empty($por['_meta']['budget_completo']);
    }

    /**
     * Un guardado nuevo solo se persiste si la bajada de /ventas vino completa.
     *
     * @param  mixed  $por
     */
    protected function ventaRealSnapshotEsCompleto($por): bool
    {
        return is_array($por)
            && is_array($por['_meta'] ?? null)
            && ! empty($por['_meta']['completo']);
    }

    /**
     * Misma clave para ItemCode aunque cambie mayúsculas, espacios o el guion.
     */
    protected function claveProductoVenta(string $codigo): string
    {
        $codigo = str_replace(["\xc2\xa0", '–', '—'], ['', '-', '-'], $codigo);
        $codigo = preg_replace('/\s+/u', '', trim($codigo)) ?? trim($codigo);

        return strtoupper($codigo);
    }

    /**
     * Una fila por producto. La bajada de /ventas y los 3 meses de ventas-budget
     * a veces quedan con claves distintas: se rellenan los meses vacíos y no se
     * suma otra vez un mes que ya trae el mismo importe.
     *
     * @param  array<string, mixed>  $por
     * @return array<string, mixed>
     */
    protected function colapsarPorCuentaProductos(array $por): array
    {
        $meta = $por['_meta'] ?? null;
        unset($por['_meta']);
        $out = [];
        foreach ($por as $key => $item) {
            if (! is_array($item)) {
                continue;
            }
            $raw = trim((string) ($item['codigo'] ?? $key));
            $clave = $this->claveProductoVenta($raw !== '' ? $raw : (string) $key);
            if ($clave === '') {
                continue;
            }
            if (! isset($out[$clave])) {
                $item['codigo'] = $raw !== '' ? $raw : (string) $key;
                foreach (['gasto', 'importe', 'importe_usd'] as $field) {
                    $serie = $item[$field] ?? [];
                    if (! is_array($serie)) {
                        $serie = [];
                    }
                    $item[$field] = array_pad(array_slice(array_values($serie), 0, 12), 12, 0.0);
                }
                $out[$clave] = $item;

                continue;
            }
            $base = &$out[$clave];
            if (($base['nombre'] ?? '') === '' && ! empty($item['nombre'])) {
                $base['nombre'] = (string) $item['nombre'];
            }
            foreach (['unidad', 'unidad_nombre'] as $field) {
                if (($base[$field] ?? '') === '' && ! empty($item[$field])) {
                    $base[$field] = $item[$field];
                }
            }
            if ((float) ($base['costo'] ?? 0) <= 0 && (float) ($item['costo'] ?? 0) > 0) {
                $base['costo'] = $item['costo'];
                $base['costo_moneda'] = $item['costo_moneda'] ?? ($base['costo_moneda'] ?? 'MXN');
            }
            foreach (['gasto', 'importe', 'importe_usd'] as $field) {
                $dec = $field === 'gasto' ? 4 : 2;
                $a = $base[$field];
                $b = is_array($item[$field] ?? null) ? array_values($item[$field]) : [];
                for ($i = 0; $i < 12; $i++) {
                    $va = (float) ($a[$i] ?? 0);
                    $vb = (float) ($b[$i] ?? 0);
                    if (abs($va) < 0.0000001) {
                        $a[$i] = round($vb, $dec);
                    } elseif (abs($vb) >= 0.0000001 && abs($va - $vb) >= 0.0001 && abs($vb) > abs($va)) {
                        $a[$i] = round($vb, $dec);
                    }
                }
                $base[$field] = $a;
            }
            unset($base);
        }
        if (is_array($meta)) {
            $out['_meta'] = $meta;
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $por
     * @return array<string, mixed>
     */
    protected function porCuentaSinMeta(array $por): array
    {
        $por = $this->colapsarPorCuentaProductos($por);
        unset($por['_meta']);

        return $por;
    }

    /**
     * Oct–Dic de /ventas-budget se guardan en el snapshot local.
     * No pisan un mes que ya trae venta real.
     *
     * @param  array<string, array<string, mixed>>  $porArticulo
     * @param  array<int, int>  $meses
     */
    protected function fusionarVentasBudgetEnSnapshot(string $empresa, string $cc, int $year, array $porArticulo, array $meses): void
    {
        if (! Schema::hasTable('tbl_pv_venta_real_snapshot') || $porArticulo === [] || $meses === []) {
            return;
        }

        $idxs = [];
        foreach ($meses as $m) {
            $i = (int) $m - 1;
            if ($i >= 0 && $i <= 11) {
                $idxs[$i] = $i;
            }
        }
        if ($idxs === []) {
            return;
        }

        $empresa = strtoupper(trim($empresa));
        $cc = trim($cc);
        $row = PvVentaRealSnapshot::query()->firstOrNew([
            'empresa' => $empresa,
            'cliente_codigo' => $cc,
            'anio' => $year,
        ]);
        $por = $this->colapsarPorCuentaProductos(is_array($row->por_cuenta) ? $row->por_cuenta : []);

        foreach ($porArticulo as $cod => $hit) {
            if (! is_array($hit)) {
                continue;
            }
            $codigo = trim((string) ($hit['codigo'] ?? $cod));
            $clave = $this->claveProductoVenta($codigo);
            if ($clave === '') {
                continue;
            }
            if (! isset($por[$clave]) || ! is_array($por[$clave])) {
                $por[$clave] = [
                    'codigo' => $codigo,
                    'nombre' => (string) ($hit['nombre'] ?? ''),
                    'gasto' => array_fill(0, 12, 0.0),
                    'importe' => array_fill(0, 12, 0.0),
                    'importe_usd' => array_fill(0, 12, 0.0),
                ];
            }
            foreach (['gasto', 'importe', 'importe_usd'] as $field) {
                $serie = $por[$clave][$field] ?? [];
                if (! is_array($serie)) {
                    $serie = [];
                }
                $serie = array_slice(array_values($serie), 0, 12);
                $por[$clave][$field] = array_pad($serie, 12, 0.0);
            }
            if (($por[$clave]['nombre'] ?? '') === '' && ! empty($hit['nombre'])) {
                $por[$clave]['nombre'] = (string) $hit['nombre'];
            }
            $qty = is_array($hit['meses'] ?? null) ? $hit['meses'] : [];
            $mxn = is_array($hit['importe'] ?? null) ? $hit['importe'] : [];
            $usd = is_array($hit['importe_usd'] ?? null) ? $hit['importe_usd'] : [];
            foreach ($idxs as $i) {
                $q = (float) ($qty[$i] ?? 0);
                $mx = (float) ($mxn[$i] ?? 0);
                $us = (float) ($usd[$i] ?? 0);
                if ((float) $por[$clave]['gasto'][$i] == 0.0 && $q > 0) {
                    $por[$clave]['gasto'][$i] = round($q, 4);
                }
                if ((float) $por[$clave]['importe'][$i] == 0.0 && $mx > 0) {
                    $por[$clave]['importe'][$i] = round($mx, 2);
                }
                if ((float) $por[$clave]['importe_usd'][$i] == 0.0 && $us > 0) {
                    $por[$clave]['importe_usd'][$i] = round($us, 2);
                }
            }
        }

        $meta = is_array($por['_meta'] ?? null) ? $por['_meta'] : [];
        $meta['budget_meses'] = array_values($meses);
        $meta['budget_at'] = now()->toDateTimeString();
        $meta['budget_completo'] = true;
        $por['_meta'] = $meta;

        if (! $row->exists) {
            $row->origen = 'ventas-budget';
        }
        $row->por_cuenta = $por;
        $row->synced_at = now();
        $row->synced_by = optional(auth()->user())->id;
        $row->save();

        Cache::forget('pv.venta-real.v6.'.$empresa.'.'.$cc.'.'.$year);
    }

    /**
     * Si una bajada de /ventas viene sin oct–dic, conserva lo ya guardado de ventas-budget
     * solo en productos que SÍ vienen en la bajada nueva.
     * No reintroduce productos viejos (p. ej. DD814 mezclados en un snapshot de D814).
     *
     * @param  array<string, mixed>  $prev
     * @param  array<string, mixed>  $nuevo
     * @return array<string, mixed>
     */
    protected function conservarMesesBudget(array $prev, array $nuevo): array
    {
        $prev = $this->colapsarPorCuentaProductos($prev);
        $nuevo = $this->colapsarPorCuentaProductos($nuevo);
        $meta = is_array($prev['_meta'] ?? null) ? $prev['_meta'] : [];
        if (empty($meta['budget_at'])) {
            return $nuevo;
        }
        $meses = is_array($meta['budget_meses'] ?? null) ? $meta['budget_meses'] : [10, 11, 12];
        foreach ($prev as $cod => $item) {
            if ($cod === '_meta' || ! is_array($item)) {
                continue;
            }
            // Solo oct–dic de productos presentes en la bajada actual.
            if (! isset($nuevo[$cod]) || ! is_array($nuevo[$cod])) {
                continue;
            }
            foreach ($meses as $m) {
                $i = (int) $m - 1;
                if ($i < 0 || $i > 11) {
                    continue;
                }
                foreach (['gasto', 'importe', 'importe_usd'] as $field) {
                    $viejo = $item[$field][$i] ?? 0;
                    $actual = $nuevo[$cod][$field][$i] ?? 0;
                    if (! is_array($nuevo[$cod][$field] ?? null)) {
                        continue;
                    }
                    if ((float) $viejo != 0.0 && (float) $actual == 0.0) {
                        $nuevo[$cod][$field][$i] = $viejo;
                    }
                }
            }
        }
        $nuevoMeta = is_array($nuevo['_meta'] ?? null) ? $nuevo['_meta'] : [];
        $nuevoMeta['budget_meses'] = $meta['budget_meses'] ?? [10, 11, 12];
        $nuevoMeta['budget_at'] = $meta['budget_at'];
        if (! empty($meta['budget_completo'])) {
            $nuevoMeta['budget_completo'] = true;
        }
        $nuevo['_meta'] = $nuevoMeta;

        return $nuevo;
    }

    /**
     * @param  array<string, mixed>  $porCuenta
     * @return array<string, mixed>
     */
    protected function guardarVentaRealSnapshot(
        string $empresa,
        string $cc,
        int $year,
        array $porCuenta,
        $userId = null
    ): array {
        if (! Schema::hasTable('tbl_pv_venta_real_snapshot') || $porCuenta === [] || ! $this->ventaRealSnapshotEsCompleto($porCuenta)) {
            return $porCuenta;
        }

        $row = PvVentaRealSnapshot::query()->firstOrNew([
            'empresa' => strtoupper(trim($empresa)),
            'cliente_codigo' => trim($cc),
            'anio' => $year,
        ]);
        $previo = is_array($row->por_cuenta) ? $row->por_cuenta : [];
        if ($this->ventaRealSnapshotTieneImportes($previo) && ! $this->ventaRealSnapshotTieneImportes($porCuenta)) {
            return $previo;
        }
        $porCuenta = $this->conservarMesesBudget($previo, $porCuenta);
        $row->por_cuenta = $porCuenta;
        $row->origen = 'api';
        $row->synced_at = now();
        $row->synced_by = $userId;
        $row->save();

        return $porCuenta;
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

        $itemsRaw = $request->get('items', $request->get('articulos', ''));
        $soloArticulos = [];
        if (is_array($itemsRaw)) {
            $soloArticulos = $itemsRaw;
        } else {
            $soloArticulos = preg_split('/\s*,\s*/', trim((string) $itemsRaw), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }
        $soloArticulos = array_values(array_unique(array_filter(array_map(static function ($v) {
            return trim((string) $v);
        }, $soloArticulos))));

        $cacheKey = 'pv.listaPreciosventa.v2.' . $empresa . '.' . $cc;
        if ($soloArticulos) {
            $norm = array_map(static fn ($v) => strtoupper($v), $soloArticulos);
            sort($norm);
            $cacheKey .= '.' . substr(sha1(implode('|', $norm)), 0, 16);
        }
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && ! empty($cached['ok'])) {
            return response()->json($cached);
        }

        $year = (int) $request->get('year', $request->get('anio', 0));
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y') - 1;
        }

        try {
            $payload = $this->cargarListasPreciosCliente($empresa, $cc, $year, $soloArticulos);
            if (! empty($payload['ok']) && $this->listaPrecioCubreArticulos($payload['por_articulo'] ?? [], $soloArticulos)) {
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

        $year = (int) $request->get('year', $request->get('anio', 0));
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }

        @set_time_limit(300);
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
            $importe = abs($this->importeVenta($row, ['LineTotal', 'linetotal', 'GTotal']));
            $importeUsd = abs($this->importeVenta($row, ['LineTotalUSD', 'LineTotalUsd', 'LineTotalFC', 'TotalFrgn']));
            if ($qty <= 0 && $importe <= 0 && $importeUsd <= 0) {
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
                        'importe' => array_fill(0, 12, null),
                        'importe_usd' => array_fill(0, 12, null),
                        'total' => 0.0,
                    ];
                }
                $idx = $mesNum - 1;
                if ($qty > 0) {
                    $prev = $porArticulo[$k]['meses'][$idx];
                    $porArticulo[$k]['meses'][$idx] = round(($prev === null ? 0.0 : (float) $prev) + $qty, 4);
                    $porArticulo[$k]['total'] = round((float) $porArticulo[$k]['total'] + $qty, 4);
                }
                if ($importe > 0) {
                    $prevMx = $porArticulo[$k]['importe'][$idx];
                    $porArticulo[$k]['importe'][$idx] = round(($prevMx === null ? 0.0 : (float) $prevMx) + $importe, 2);
                }
                if ($importeUsd > 0) {
                    $prevUsd = $porArticulo[$k]['importe_usd'][$idx];
                    $porArticulo[$k]['importe_usd'][$idx] = round(($prevUsd === null ? 0.0 : (float) $prevUsd) + $importeUsd, 2);
                }
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

        $this->fusionarVentasBudgetEnSnapshot($empresa, $card, $year, $limpias, $meses);

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

        return response()->json($this->capturaPayload($ciclo));
    }

    /**
     * Payload local de Detalle: captura + snapshot de venta + precios maestro.
     * No consulta SAP (ni rellena meses, ni listas-precios, ni ventas-budget).
     */
    public function capturaBootstrap(Request $request): JsonResponse
    {
        $ciclo = strtoupper(trim((string) $request->get('ciclo', '')));
        $empresa = strtoupper(trim((string) $request->get('empresa', '')));
        $cc = trim((string) $request->get('cc', $request->get('cliente', '')));
        $alias = ['ABSA' => 'AUSTIN'];
        if (isset($alias[$empresa])) {
            $empresa = $alias[$empresa];
        }

        if ($ciclo === '' || $empresa === '' || $cc === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Faltan ciclo, empresa o cliente.',
            ], 422);
        }

        $year = (int) $request->get('year', $request->get('anio', 0));
        if ($year < 2000 || $year > 2100) {
            $year = (int) ($this->anioReferenciaDeCiclo($ciclo) ?: date('Y'));
        }

        $payload = $this->capturaPayload($ciclo);
        $costosMaster = json_decode(json_encode($payload['costosMaster'] ?? []), true);
        if (! is_array($costosMaster)) {
            $costosMaster = [];
        }

        $ventaReal = null;
        $budgetRef = [];
        $snap = $this->snapshotVentaRealGuardado($empresa, $cc, $year);
        if (! $snap) {
            // Reintento por CardCode case-insensitive.
            if (Schema::hasTable('tbl_pv_venta_real_snapshot')) {
                $snap = PvVentaRealSnapshot::query()
                    ->where('empresa', $empresa)
                    ->where('anio', $year)
                    ->whereRaw('LOWER(cliente_codigo) = ?', [strtolower($cc)])
                    ->first();
                if ($snap && ! $this->ventaRealSnapshotSirve($snap->por_cuenta)) {
                    $snap = null;
                }
            }
        }
        if ($snap) {
            $por = is_array($snap->por_cuenta) ? $snap->por_cuenta : [];
            $ventaReal = [
                'ok' => true,
                'year' => $year,
                'por_cuenta' => $this->porCuentaSinMeta($por),
                'fuente' => 'snapshot',
                'budget_completo' => $this->budgetCompletoDe($por),
                'synced_at' => $snap->synced_at ? $snap->synced_at->format('Y-m-d H:i') : null,
            ];
            $budgetRef = $this->budgetRefDesdePorCuenta($por);
        }

        return response()->json(array_merge($payload, [
            'ok' => true,
            'fuente' => 'local',
            'empresa' => $empresa,
            'cliente' => $cc,
            'cliente_nombre' => $this->nombreClienteLocal($empresa, $cc),
            'capturadores' => $this->capturadoresDeCliente($ciclo, $empresa, $cc),
            'year_venta' => $year,
            'venta_real' => $ventaReal,
            'ventas_budget_ref' => (object) $budgetRef,
            'precios' => [
                'ok' => true,
                'fuente' => 'maestro_local',
                'por_articulo' => (object) $this->preciosLocalesDesdeMaestro($empresa, $cc, $costosMaster),
            ],
        ]));
    }

    /**
     * Nombre del cliente distinto del código: asignación, catálogo local o maestro de precios.
     */
    protected function nombreClienteLocal(string $empresa, string $cc): string
    {
        $empresa = strtoupper(trim($empresa));
        $cc = trim($cc);
        if ($empresa === '' || $cc === '') {
            return '';
        }

        $mejor = '';
        $tomar = function ($nombre) use (&$mejor, $cc) {
            $nombre = trim((string) $nombre);
            if ($nombre === '' || strcasecmp($nombre, $cc) === 0 || $mejor !== '') {
                return;
            }
            $mejor = $nombre;
        };

        if (Schema::hasTable('tbl_pv_asignaciones') && Schema::hasColumn('tbl_pv_asignaciones', 'cliente_nombre')) {
            $tomar(DB::table('tbl_pv_asignaciones')
                ->whereRaw('UPPER(empresa) = ?', [$empresa])
                ->whereRaw('LOWER(cliente_codigo) = ?', [strtolower($cc)])
                ->whereNotNull('cliente_nombre')
                ->where('cliente_nombre', '!=', '')
                ->orderByDesc('id')
                ->value('cliente_nombre'));
        }
        if ($mejor === '' && Schema::hasTable('tbl_pv_cliente_catalogo')) {
            $tomar(DB::table('tbl_pv_cliente_catalogo')
                ->whereRaw('UPPER(empresa) = ?', [$empresa])
                ->whereRaw('LOWER(codigo) = ?', [strtolower($cc)])
                ->orderByDesc('id')
                ->value('nombre'));
        }
        if ($mejor === '' && Schema::hasTable('tbl_pv_productos_costo') && Schema::hasColumn('tbl_pv_productos_costo', 'card_name')) {
            $tomar(DB::table('tbl_pv_productos_costo')
                ->whereRaw('UPPER(empresa) = ?', [$empresa])
                ->whereRaw('LOWER(card_code) = ?', [strtolower($cc)])
                ->whereNotNull('card_name')
                ->where('card_name', '!=', '')
                ->orderByDesc('id')
                ->value('card_name'));
        }

        return $mejor;
    }

    /**
     * Usuarios con Capturar o Editar en este cliente, y los productos de cada uno.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function capturadoresDeCliente(string $ciclo, string $empresa, string $cc): array
    {
        $ciclo = strtoupper(trim($ciclo));
        $empresa = strtoupper(trim($empresa));
        $cc = trim($cc);
        if ($ciclo === '' || $empresa === '' || $cc === '' || ! Schema::hasTable('tbl_pv_asignaciones')) {
            return [];
        }

        $empresas = [$empresa];
        if ($empresa === 'AUSTIN') {
            $empresas[] = 'ABSA';
        }

        $rows = PvAsignacion::query()
            ->with(['usuario', 'cuentas', 'permisos.tipo'])
            ->where('ciclo_codigo', $ciclo)
            ->where(function ($q) use ($empresas) {
                foreach ($empresas as $e) {
                    $q->orWhereRaw('UPPER(empresa) = ?', [$e]);
                }
            })
            ->whereRaw('LOWER(cliente_codigo) = ?', [strtolower($cc)])
            ->get();

        $out = [];
        foreach ($rows as $a) {
            $permisos = $a->permisos->map(function ($p) {
                return $p->tipo->clave ?? null;
            })->filter()->values()->all();
            if (! in_array('capturar', $permisos, true) && ! in_array('editar', $permisos, true)) {
                continue;
            }
            $nombre = trim((string) ($a->usuario->name ?? ''));
            if ($nombre === '') {
                continue;
            }
            $cuentas = $a->cuentas->map(function ($c) {
                return trim((string) $c->cuenta_codigo);
            })->filter()->values()->all();
            $out[] = [
                'usuario' => $nombre,
                'es_principal' => (bool) ($a->es_principal ?? false),
                'cuentas' => $cuentas,
            ];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    protected function capturaPayload(string $ciclo): array
    {
        $budgets = [];
        $completados = [];
        $costos = [];
        $ajustes = [];
        $preciosMeses = [];
        if (Schema::hasTable('tbl_pv_proyecciones')) {
            $hasDone = Schema::hasColumn('tbl_pv_proyecciones', 'completado');
            $hasPrecioMeses = Schema::hasColumn('tbl_pv_proyecciones', 'precio_meses');
            PvPresupuesto::query()->where('ciclo_codigo', $ciclo)->get()->each(function (PvPresupuesto $row) use (&$budgets, &$completados, &$costos, &$ajustes, &$preciosMeses, $hasDone, $hasPrecioMeses) {
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
            PvCapturaCentro::query()->where('ciclo_codigo', $ciclo)->get()->each(function (PvCapturaCentro $row) use (&$overlays) {
                $key = strtoupper(trim((string) $row->empresa)).'|'.trim((string) $row->centro_codigo);
                $overlays[$key] = [
                    'estado' => $row->estado ?: 'en_proceso',
                    'fecha' => $row->updated_at ? $row->updated_at->format('d/m/Y') : null,
                ];
            });
        }

        $anioCostos = $this->anioProyeccionCostos(null, $ciclo);

        return [
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
        ];
    }

    /**
     * Oct–Dic del snapshot → formato ventas-budget ref (sin SAP).
     *
     * @param  array<string, mixed>  $por
     * @return array<string, array<string, mixed>>
     */
    protected function budgetRefDesdePorCuenta(array $por): array
    {
        $out = [];
        foreach ($por as $code => $row) {
            if ($code === '_meta' || ! is_array($row)) {
                continue;
            }
            $gasto = is_array($row['gasto'] ?? null) ? $row['gasto'] : [];
            $imp = is_array($row['importe'] ?? null) ? $row['importe'] : [];
            $usd = is_array($row['importe_usd'] ?? null) ? $row['importe_usd'] : [];
            $meses = array_fill(0, 12, null);
            $importe = array_fill(0, 12, null);
            $importeUsd = array_fill(0, 12, null);
            $any = false;
            for ($i = 9; $i <= 11; $i++) {
                $q = (float) ($gasto[$i] ?? 0);
                $mx = (float) ($imp[$i] ?? 0);
                $us = (float) ($usd[$i] ?? 0);
                if ($q > 0) {
                    $meses[$i] = round($q, 4);
                    $any = true;
                }
                if ($mx > 0) {
                    $importe[$i] = round($mx, 2);
                    $any = true;
                }
                if ($us > 0) {
                    $importeUsd[$i] = round($us, 2);
                    $any = true;
                }
            }
            if (! $any) {
                continue;
            }
            $cod = trim((string) ($row['codigo'] ?? $code));
            if ($cod === '') {
                continue;
            }
            $out[$cod] = [
                'codigo' => $cod,
                'nombre' => (string) ($row['nombre'] ?? ''),
                'meses' => $meses,
                'importe' => $importe,
                'importe_usd' => $importeUsd,
            ];
        }

        return $out;
    }

    /**
     * Precios de lista desde maestro local (tbl_pv_productos_costo) para el cliente.
     *
     * @param  array<string, mixed>  $costosMaster
     * @return array<string, array<string, mixed>>
     */
    protected function preciosLocalesDesdeMaestro(string $empresa, string $cc, array $costosMaster): array
    {
        $empresa = strtoupper(trim($empresa));
        $cc = trim($cc);
        $out = [];
        $prefixCard = $empresa.'|'.$cc.'|';
        $prefixEmp = $empresa.'|';

        foreach ($costosMaster as $key => $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $k = (string) $key;
            $cod = '';
            $card = trim((string) ($entry['card_code'] ?? ''));
            if (str_starts_with($k, $prefixCard)) {
                $cod = substr($k, strlen($prefixCard));
            } elseif ($card !== '' && strcasecmp($card, $cc) === 0) {
                $parts = explode('|', $k);
                $cod = (string) end($parts);
            } elseif ($card === '' && str_starts_with($k, $prefixEmp) && substr_count($k, '|') === 1) {
                // EMP|Item (sin card): solo si no hay precio por cliente.
                $cod = substr($k, strlen($prefixEmp));
                if (isset($out[$cod])) {
                    continue;
                }
            } else {
                continue;
            }
            $cod = trim((string) $cod);
            if ($cod === '') {
                continue;
            }
            // Captura: Precio lista = columna local precio_lista (no costo_unitario / global).
            $precioLista = isset($entry['precio_lista']) ? (float) $entry['precio_lista'] : 0.0;
            if ($precioLista <= 0) {
                continue;
            }
            if (isset($out[$cod]) && $card === '') {
                continue;
            }
            $out[$cod] = [
                'codigo' => $cod,
                'precio' => round($precioLista, 4),
                'moneda' => strtoupper((string) ($entry['moneda'] ?? 'MXN')) ?: 'MXN',
                'fuente' => 'maestro_local_precio_lista',
            ];
        }

        return $out;
    }

    protected function anioReferenciaDeCiclo(string $ciclo): ?int
    {
        $ciclo = strtoupper(trim($ciclo));
        if ($ciclo === '' || ! Schema::hasTable('tbl_pv_ciclos')) {
            return null;
        }
        $row = DB::table('tbl_pv_ciclos')->whereRaw('UPPER(codigo) = ?', [$ciclo])->first();
        if (! $row) {
            return null;
        }
        $anio = (int) ($row->anio_referencia ?? $row->anioReferencia ?? 0);
        if ($anio >= 2000 && $anio <= 2100) {
            return $anio;
        }

        return null;
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

        // La captura de cantidades no requiere editar precios. Si no hay permiso,
        // se ignoran precio_meses y se conservan los que ya están guardados.
        if (array_key_exists('precio_meses', $data) && ! $this->puedeEditarPreciosCaptura()) {
            unset($data['precio_meses']);
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
    protected function celdaMesCaptura($raw): ?int
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

        return (int) round((float) $raw);
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
     * Suma líneas de /ventas por ItemCode. $cc vacío acepta cualquier cliente.
     *
     * @param  array<int, mixed>  $rows
     * @return array<string, array<string, mixed>>
     */
    protected function agregarPorCuentaVentas(array $rows, int $year, string $cc = ''): array
    {
        $porCuenta = [];
        $ccNorm = strtoupper(trim($cc));
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $rowCc = strtoupper(trim((string) ($row['CardCode'] ?? $row['Cardcode'] ?? $row['CC'] ?? '')));
                // Ventas: CardCode exacto. No fusionar D814↔DD814 (son cuentas SAP distintas
                // aunque compartan nombre; AutinApi /ventas a veces devuelve ambas).
                if ($ccNorm !== '' && $rowCc !== $ccNorm) {
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
            $key = $this->claveProductoVenta($codigo);
            if ($key === '') {
                continue;
            }
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

        return $porCuenta;
    }

    /**
     * @param  array<string, array<string, mixed>>  $porCuenta
     * @return array<string, array<string, mixed>>
     */
    protected function cerrarPorCuentaVentas(array $porCuenta): array
    {
        foreach ($porCuenta as &$item) {
            if (! is_array($item) || ! isset($item['precio_q'])) {
                continue;
            }
            $precio = [];
            for ($m = 0; $m < 12; $m++) {
                $den = (float) ($item['precio_q'][$m] ?? 0);
                $precio[$m] = $den > 0
                    ? round(((float) $item['precio_w'][$m]) / $den, 4)
                    : 0.0;
            }
            $item['precio'] = $precio;
            unset($item['precio_w'], $item['precio_q']);

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

        return $porCuenta;
    }

    /**
     * @return array{ok: bool, year: int, por_cuenta: array<string, array<string, mixed>>, mensaje: string|null}
     */
    protected function cargarGastoRealCentro(string $empresa, string $cc, int $year): array
    {
        @set_time_limit(300);
        $cc = trim($cc);
        // Solo el CardCode de la asignación (sin variantes D/DD): evita mezclar D814 + DD814.
        $pack = $this->filasVentasEmpresa(strtolower($empresa), $year, [
            'CardCode' => $cc,
            'fecha_desde' => $year.'/01/01',
            'fecha_hasta' => $year.'/12/31',
        ], 220);

        $porCuenta = $this->cerrarPorCuentaVentas($this->agregarPorCuentaVentas($pack['rows'] ?? [], $year, $cc));
        if (! empty($pack['ok'])) {
            $porCuenta['_meta'] = [
                'completo' => true,
                'filas' => (int) ($pack['filas'] ?? count($pack['rows'] ?? [])),
                'api_total' => (int) ($pack['total'] ?? 0),
                'card_code_consulta' => $cc,
            ];

            return [
                'ok' => true,
                'year' => $year,
                'por_cuenta' => $porCuenta,
                'mensaje' => null,
            ];
        }

        return [
            'ok' => false,
            'year' => $year,
            'por_cuenta' => [],
            'mensaje' => $pack['mensaje'] ?? 'Sin conexión a ventas SAP',
        ];
    }

    protected function esItemCodeSap(string $codigo): bool
    {
        return (bool) preg_match('/[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]/u', $codigo);
    }

    /**
     * SAP B1 guarda la moneda local como "$" o "##". Eso es pesos, no dólares.
     */
    protected function normalizarMonedaLista($raw): string
    {
        $m = strtoupper(trim((string) $raw));
        $m = str_replace([' ', '.'], '', $m);
        if (in_array($m, ['USD', 'US$', 'U$S', 'U$D', 'US', 'DLLS', 'DLL', 'DOLAR', 'DOLARES', 'DOLLAR', 'DOLLARS'], true)) {
            return 'USD';
        }

        return 'MXN';
    }

    /**
     * Precio de lista > 0. Ignora un Precio en cero si otro campo trae el importe.
     */
    protected function precioPositivoLista(array $row): float
    {
        foreach (['Precio', 'Price', 'UnitPrice', 'PriceBefDi', 'PrecioLista', 'ListPrice'] as $k) {
            if (! array_key_exists($k, $row) || $row[$k] === null || $row[$k] === '') {
                continue;
            }
            $raw = $row[$k];
            if (is_string($raw)) {
                $raw = str_replace(['$', ' '], '', trim($raw));
                if (str_contains($raw, ',') && str_contains($raw, '.')) {
                    $raw = str_replace(',', '', $raw);
                } elseif (str_contains($raw, ',') && ! str_contains($raw, '.')) {
                    $raw = str_replace(',', '.', $raw);
                }
            }
            if (is_numeric($raw) && (float) $raw > 0) {
                return (float) $raw;
            }
        }

        return 0.0;
    }

    /**
     * @param  array<int, mixed>  $rows
     */
    protected function listaPrecioTraeArticulo(array $rows, string $itemCode, string $cc): bool
    {
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
            if ($codigo === '' || strcasecmp($codigo, $itemCode) !== 0) {
                continue;
            }
            $rowCard = trim((string) ($row['CodigoCliente'] ?? $row['CardCode'] ?? ''));
            if ($cc !== '' && $rowCard !== '' && ! $this->mismoCentroCodigo($rowCard, $cc)) {
                continue;
            }
            if ($this->precioPositivoLista($row) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * La API filtra CodigoArticulo por prefijo: si la página 1 no trae el ítem exacto, sigue.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function paginasListaPrecioArticulo(AutinApiClient $api, string $empresa, string $cc, string $itemCode): array
    {
        $out = [];
        for ($page = 2; $page <= 4; $page++) {
            $res = $api->listaPreciosVenta([
                'Empresa' => $empresa,
                'CodigoCliente' => $cc,
                'CodigoArticulo' => $itemCode,
                'per_page' => 100,
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
            foreach ($rows as $row) {
                if (is_array($row)) {
                    $out[] = $row;
                }
            }
            if ($this->listaPrecioTraeArticulo($rows, $itemCode, $cc)) {
                break;
            }
            $pag = $this->paginacionDe($body);
            $last = (int) ($pag['last_page'] ?? 0);
            if ($last > 0 && $page >= $last) {
                break;
            }
            if ($last < 1 && count($rows) < 100) {
                break;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $porArticulo
     * @param  array<int, string>  $articulos
     */
    protected function listaPrecioCubreArticulos(array $porArticulo, array $articulos): bool
    {
        if (! $articulos) {
            return true;
        }
        foreach ($articulos as $item) {
            $item = trim((string) $item);
            if ($item === '') {
                continue;
            }
            $hit = $porArticulo[$item] ?? $porArticulo[strtoupper($item)] ?? null;
            if (! is_array($hit) || (float) ($hit['precio'] ?? 0) <= 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, string>  $soloArticulos  Si viene, consulta por CodigoArticulo (evita paginar 15k+ filas).
     * @return array{ok: bool, empresa: string, cliente: string, por_articulo: array<string, array<string, mixed>>, mensaje: string|null, origen?: string}
     */
    protected function cargarListasPreciosCliente(string $empresa, string $cc, int $year = 0, array $soloArticulos = [], bool $enriquecerUnidades = true): array
    {
        $api = app(AutinApiClient::class);
        $porArticulo = [];
        $ok = false;
        $mensaje = null;
        $ccNorm = trim($cc);
        $soloArticulos = array_values(array_unique(array_filter(array_map(static function ($v) {
            return trim((string) $v);
        }, $soloArticulos))));

        $ingestRows = function (array $rows, ?string $requireItem = null) use (&$porArticulo, $ccNorm): void {
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $rowCard = trim((string) (
                    $row['CodigoCliente']
                    ?? $row['CardCode']
                    ?? ''
                ));
                if ($ccNorm !== '' && $rowCard !== '' && ! $this->mismoCentroCodigo($rowCard, $ccNorm)) {
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
                if ($requireItem !== null && $requireItem !== '' && strcasecmp($codigo, $requireItem) !== 0) {
                    continue;
                }
                $precio = $this->precioPositivoLista($row);
                if ($precio <= 0) {
                    continue;
                }
                $moneda = $this->normalizarMonedaLista($row['Moneda'] ?? $row['Currency'] ?? '');
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
                ]));
                if (! $this->esItemCodeSap($codigo)) {
                    $keys[] = $this->codigoCuentaKey($codigo);
                }
                foreach ($keys as $k) {
                    if (isset($porArticulo[$k]) && (float) ($porArticulo[$k]['precio'] ?? 0) > 0) {
                        continue;
                    }
                    $porArticulo[$k] = $entry;
                }
            }
        };

        if ($soloArticulos) {
            $umbralPuntual = 15;
            if (count($soloArticulos) <= $umbralPuntual) {
                $pendientes = [];
                foreach ($soloArticulos as $itemCode) {
                    $ck = 'pv.precio.row.v2.'.strtoupper($empresa).'.'.substr(sha1(strtoupper($ccNorm).'|'.strtoupper($itemCode)), 0, 20);
                    $hit = Cache::get($ck);
                    if (is_array($hit) && $this->listaPrecioTraeArticulo($hit, $itemCode, $ccNorm)) {
                        $ingestRows($hit, $itemCode);
                        $ok = true;
                        continue;
                    }
                    $pendientes[$ck] = $itemCode;
                }
                if ($pendientes) {
                    try {
                        $packPrecios = $api->listaPreciosPorArticulos(strtoupper($empresa), $ccNorm, array_values($pendientes), 5);
                    } catch (Throwable $e) {
                        $packPrecios = ['ok' => false, 'message' => $e->getMessage(), 'por_item' => []];
                    }
                    if (! empty($packPrecios['ok'])) {
                        $ok = true;
                    } elseif ($mensaje === null) {
                        $mensaje = $packPrecios['message'] ?? 'Sin conexión a listaPreciosventa';
                    }
                    $porItem = is_array($packPrecios['por_item'] ?? null) ? $packPrecios['por_item'] : [];
                    foreach ($pendientes as $ck => $itemCode) {
                        $rows = $porItem[strtoupper($itemCode)] ?? [];
                        if (! is_array($rows)) {
                            $rows = [];
                        }
                        // Página 1 a veces trae solo el prefijo del código y no el artículo exacto.
                        if ($rows && ! $this->listaPrecioTraeArticulo($rows, $itemCode, $ccNorm)) {
                            $rows = array_merge($rows, $this->paginasListaPrecioArticulo($api, strtoupper($empresa), $ccNorm, $itemCode));
                        }
                        if (! $this->listaPrecioTraeArticulo($rows, $itemCode, $ccNorm)) {
                            continue;
                        }
                        Cache::put($ck, $rows, 900);
                        $ingestRows($rows, $itemCode);
                    }
                }
            } else {
                // Muchos artículos: una lista por cliente y cortar al completar el maestro.
                $faltan = [];
                foreach ($soloArticulos as $itemCode) {
                    $faltan[strtoupper($itemCode)] = $itemCode;
                }
                $cacheLista = 'pv.precio.lista.cli.v1.'.strtoupper($empresa).'.'.substr(sha1(strtoupper($ccNorm)), 0, 24);
                $cachedRows = Cache::get($cacheLista);
                if (is_array($cachedRows) && $cachedRows) {
                    $ok = true;
                    $ingestRows($cachedRows, null);
                    foreach (array_keys($faltan) as $uk) {
                        if (isset($porArticulo[$uk]) || isset($porArticulo[$faltan[$uk]])) {
                            unset($faltan[$uk]);
                        }
                    }
                }
                if ($faltan) {
                    $allRows = is_array($cachedRows) ? $cachedRows : [];
                    $perPage = 500;
                    $maxPages = 40;
                    for ($page = 1; $page <= $maxPages && $faltan; $page++) {
                        $res = $api->listaPreciosVenta([
                            'Empresa' => strtoupper($empresa),
                            'CodigoCliente' => $ccNorm,
                            'per_page' => $perPage,
                            'page' => $page,
                        ]);
                        if (empty($res['ok'])) {
                            if ($page === 1 && ! $ok) {
                                $mensaje = $res['message'] ?? 'Sin conexión a listaPreciosventa';
                            }
                            break;
                        }
                        $ok = true;
                        $body = is_array($res['body'] ?? null) ? $res['body'] : [];
                        $rows = $body['data'] ?? [];
                        if (! is_array($rows) || ! $rows) {
                            break;
                        }
                        $allRows = array_merge($allRows, $rows);
                        $ingestRows($rows, null);
                        foreach (array_keys($faltan) as $uk) {
                            if (isset($porArticulo[$uk]) || isset($porArticulo[$faltan[$uk]])) {
                                unset($faltan[$uk]);
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
                    if ($allRows) {
                        Cache::put($cacheLista, $allRows, 1200);
                    }
                }
            }
        } else {
            $perPage = 500;
            $maxPages = 60;
            for ($page = 1; $page <= $maxPages; $page++) {
                $res = $api->listaPreciosVenta([
                    'Empresa' => strtoupper($empresa),
                    'CodigoCliente' => $ccNorm,
                    'per_page' => $perPage,
                    'page' => $page,
                ]);

                if (empty($res['ok'])) {
                    if ($page === 1) {
                        $mensaje = $res['message'] ?? 'Sin conexión a listaPreciosventa';
                    }
                    break;
                }

                $ok = true;
                $body = is_array($res['body'] ?? null) ? $res['body'] : [];
                $rows = $body['data'] ?? [];
                if (! is_array($rows) || ! $rows) {
                    break;
                }

                $ingestRows($rows, null);

                $pag = $this->paginacionDe($body);
                $lastPage = (int) ($pag['last_page'] ?? 0);
                if ($lastPage > 0 && $page >= $lastPage) {
                    break;
                }
                if ($lastPage < 1 && count($rows) < $perPage) {
                    break;
                }
            }
        }

        if ($enriquecerUnidades && $ok && $porArticulo) {
            $this->enriquecerUnidadesDesdeVentas($api, $porArticulo, $empresa, $cc, $year);
        }
        $this->adjuntarNombresUnidad($porArticulo);

        return [
            'ok' => $ok,
            'empresa' => strtoupper($empresa),
            'cliente' => $cc,
            'origen' => 'listaPreciosventa',
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
     * Elige la mejor unidad de una fila de ventas SAP (solo etiqueta; no altera qty/montos).
     * Si SalUnitMsr tiene nombre conocido (catálogo o alias, p. ej. RK→Bobina), se prefiere.
     * Si no, se mantiene SalPackMsr cuando es más legible (KILOS, METROS, PZA…).
     */
    protected function elegirUnidadDesdeVenta(array $row): string
    {
        $pack = trim((string) ($row['SalPackMsr'] ?? $row['unidad'] ?? ''));
        $unit = trim((string) ($row['SalUnitMsr'] ?? $row['UomCode'] ?? ''));

        // Bobina/carrete etc.: preferir unidad de venta si tenemos etiqueta amigable.
        if ($unit !== '' && $this->nombreUnidadMedida($unit) !== '') {
            return $unit;
        }

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
     * Alias de códigos SAP (SalUnitMsr) → nombre visible.
     * Sin cantidades de empaque (SalPackUn).
     *
     * @return array<string, string>
     */
    protected function aliasUnidadesSap(): array
    {
        return [
            // Catálogo operativo solicitado
            'XBX' => 'CAJA',
            'RK' => 'ROLLOS',
            'XSA' => 'SACOS',
            'H87' => 'PIEZAS',
            'MTS' => 'METROS',
            'E48' => 'SERVICIOS',
            'KGM' => 'KILOS',
            // Alias adicionales frecuentes
            'RL' => 'ROLLOS',
            'RO' => 'ROLLOS',
            'BO' => 'Botella',
            'PR' => 'Par',
            'SET' => 'Juego',
            'PZA' => 'PIEZAS',
            'PZ' => 'PIEZAS',
            'ACT' => 'Actividad',
        ];
    }

    /**
     * Mapa código UoM → nombre (aliases SAP + tblunidadesmedida).
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
        if (Schema::hasTable('tblunidadesmedida')) {
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
        }
        // Alias SAP / SAT ganan sobre catálogo local incompleto (p. ej. E48 → Servicio).
        foreach ($this->aliasUnidadesSap() as $code => $nombre) {
            $cache[strtoupper((string) $code)] = $nombre;
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
        if ($a === '' || $b === '') {
            return $a === $b;
        }
        if ($a === $b) {
            return true;
        }
        $na = ltrim($a, '0');
        $nb = ltrim($b, '0');
        if ($na !== '' && $na === $nb) {
            return true;
        }
        // IMSA: el mismo cliente existe como D139 y DD139 (mismo nombre).
        // En SAP listaPreciosventa el CardCode real es el DD*; al filtrar por D*
        // AutinApi devuelve filas DD*. No mezclar otros prefijos (MM, ZZ, etc.).
        if (preg_match('/^(D{1,2})(\d+)$/', $a, $ma) && preg_match('/^(D{1,2})(\d+)$/', $b, $mb)) {
            return $ma[2] === $mb[2];
        }

        return false;
    }

    /**
     * Variantes D/DD del CardCode (IMSA) para consultar ventas en SAP.
     *
     * @return array<int, string>
     */
    protected function variantesCardCodeCliente(string $cc): array
    {
        $cc = strtoupper(trim($cc));
        if ($cc === '') {
            return [];
        }
        $out = [$cc];
        if (preg_match('/^D(\d+)$/', $cc, $m)) {
            $out[] = 'DD'.$m[1];
        } elseif (preg_match('/^DD(\d+)$/', $cc, $m)) {
            $out[] = 'D'.$m[1];
        }

        return array_values(array_unique($out));
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
            'gastoBatchUrl' => route('pv.api.gasto_real_batch'),
            'listasPreciosUrl' => route('pv.api.listas_precios'),
            'capturaBootstrapUrl' => route('pv.api.captura.bootstrap'),
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
        $mensaje = null;
        $filas = 0;
        $total = 0;
        $ok = true;
        foreach ($this->empresasFiltroVentas($empresa) as $empFiltro) {
            $pack = $api->ventasTodasPaginas(array_merge([
                'year' => $year,
                'Empresa' => $empFiltro,
            ], $extra), $maxPages, 4);
            if (empty($pack['ok'])) {
                $ok = false;
                    $mensaje = $pack['message'] ?? 'Sin conexión a ventas SAP';
                }
            $filas += count($pack['rows'] ?? []);
            $total += (int) ($pack['total'] ?? 0);
            foreach ($pack['rows'] ?? [] as $row) {
                if (is_array($row)) {
                    $rows[] = $row;
                }
            }
        }

        return ['ok' => $ok, 'rows' => $rows, 'mensaje' => $mensaje, 'filas' => $filas, 'total' => $total];
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
        $resolved = Cache::get($this->claveAnioVentas($empresa, $requested));
        if (is_numeric($resolved)) {
            $pack = $this->cargarProductosClienteAnio($empresa, $cliente, (int) $resolved, $todas);
            if (! empty($pack['ok'])) {
                if ((int) $resolved !== $requested && ! empty($pack['productos'])) {
                    $pack['mensaje'] = 'Mostrando productos con venta en '.$resolved.' (aún no hay en '.$requested.')';
                }

                return $pack;
            }
        }

        $last = null;
        for ($y = $year; $y >= $year - 3 && $y >= 2000; $y--) {
            $pack = $this->cargarProductosClienteAnio($empresa, $cliente, $y, $todas);
            $last = $pack;
            if (! empty($pack['productos'])) {
                Cache::put($this->claveAnioVentas($empresa, $requested), $y, 1800);
                if ($y !== $requested) {
                    $pack['mensaje'] = 'Mostrando productos con venta en '.$y.' (aún no hay en '.$requested.')';
                }

                return $pack;
            }
            if (empty($pack['ok'])) {
                break;
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
                : ($idx['mensaje'] ?? 'Sin productos SAP'),
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
     * Catálogo local (BD) + tablas PV ya conocidas. Solo codigo/nombre.
     *
     * @return array<string, array{codigo: string, nombre: string, empresa: string, activo: bool, departamento: string}>
     */
    protected function clientesLocalesAsignacion(string $empresa, string $q = ''): array
    {
        $empresa = strtolower(trim($empresa));
        $q = trim($q);
        $map = [];

        $push = function (string $codigo, string $nombre) use (&$map, $empresa, $q) {
            $codigo = trim($codigo);
            if ($codigo === '') {
                return;
            }
            $nombre = trim($nombre) !== '' ? trim($nombre) : $codigo;
            if ($q !== '') {
                $hay = mb_strtoupper($codigo.' '.$nombre);
                if (mb_strpos($hay, mb_strtoupper($q)) === false) {
                    return;
                }
            }
            $key = strtoupper($codigo);
            if (! isset($map[$key]) || ($map[$key]['nombre'] === $map[$key]['codigo'] && $nombre !== $codigo)) {
                $map[$key] = [
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                    'empresa' => $empresa,
                    'activo' => true,
                    'departamento' => '',
                ];
            }
        };

        if (Schema::hasTable('tbl_pv_cliente_catalogo')) {
            $rows = PvClienteCatalogo::query()
                ->where('empresa', $empresa)
                ->orderBy('nombre')
                ->limit(5000)
                ->get(['codigo', 'nombre']);
            foreach ($rows as $row) {
                $push((string) $row->codigo, (string) ($row->nombre ?? ''));
            }
        }

        if (Schema::hasTable('tbl_pv_asignaciones')) {
            $colCodigo = Schema::hasColumn('tbl_pv_asignaciones', 'cliente_codigo') ? 'cliente_codigo' : 'centro_codigo';
            $colNombre = Schema::hasColumn('tbl_pv_asignaciones', 'centro_nombre') ? 'centro_nombre' : null;
            $sel = [$colCodigo];
            if ($colNombre) {
                $sel[] = $colNombre;
            }
            $rows = DB::table('tbl_pv_asignaciones')
                ->where('empresa', $empresa)
                ->select($sel)
                ->distinct()
                ->limit(3000)
                ->get();
            foreach ($rows as $row) {
                $push((string) ($row->{$colCodigo} ?? ''), (string) ($colNombre ? ($row->{$colNombre} ?? '') : ''));
            }
        }

        if (Schema::hasTable('tbl_pv_captura_clientes')) {
            $cols = ['cliente_codigo'];
            if (Schema::hasColumn('tbl_pv_captura_clientes', 'cliente_nombre')) {
                $cols[] = 'cliente_nombre';
            } elseif (Schema::hasColumn('tbl_pv_captura_clientes', 'centro_nombre')) {
                $cols[] = 'centro_nombre';
            }
            $rows = DB::table('tbl_pv_captura_clientes')
                ->where('empresa', $empresa)
                ->select($cols)
                ->distinct()
                ->limit(3000)
                ->get();
            foreach ($rows as $row) {
                $nom = (string) ($row->cliente_nombre ?? $row->centro_nombre ?? '');
                $push((string) ($row->cliente_codigo ?? ''), $nom);
            }
        }

        if (Schema::hasTable('tbl_pv_productos_costo') && Schema::hasColumn('tbl_pv_productos_costo', 'card_code')) {
            $rows = DB::table('tbl_pv_productos_costo')
                ->where('empresa', $empresa)
                ->where('card_code', '!=', '')
                ->select('card_code', 'card_name')
                ->distinct()
                ->limit(5000)
                ->get();
            foreach ($rows as $row) {
                $push((string) ($row->card_code ?? ''), (string) ($row->card_name ?? ''));
            }
        }

        if (Schema::hasTable('tbl_pv_venta_real_snapshot')) {
            $rows = PvVentaRealSnapshot::query()
                ->where('empresa', $empresa)
                ->select('cliente_codigo')
                ->distinct()
                ->limit(3000)
                ->get();
            foreach ($rows as $row) {
                $push((string) $row->cliente_codigo, (string) $row->cliente_codigo);
            }
        }

        return $map;
    }

    /**
     * Semilla rápida: 1 página /ventas por empresa-filtro (solo CardCode/CardName).
     *
     * @return array{ok: bool, clientes: array<int, array<string, mixed>>, mensaje: string|null}
     */
    protected function sembrarClientesSapAsignacion(string $empresa, int $year): array
    {
        $cacheKey = 'pv.asig.cli.seed.'.$empresa.'.'.$year;
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && ! empty($cached['clientes'])) {
            return $cached;
        }

        $api = app(AutinApiClient::class);
        $map = [];
        $ok = false;
        $mensaje = null;

        foreach ([strtoupper($empresa)] as $empFiltro) {
            $res = $api->ventas([
                'year' => $year,
                'Empresa' => $empFiltro,
                'per_page' => 80,
                'page' => 1,
            ]);
            if (empty($res['ok'])) {
                if (! $ok) {
                    $mensaje = $res['message'] ?? 'Sin conexión a ventas SAP';
                }
                continue;
            }
            $ok = true;
            $body = is_array($res['body'] ?? null) ? $res['body'] : [];
            $rows = is_array($body['data'] ?? null) ? $body['data'] : [];
            foreach ($this->extraerClientesDeFilasVentas($rows, $empresa) as $key => $row) {
                $map[$key] = $row;
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
            'mensaje' => $ok
                ? 'Muestra reciente de SAP. Escribe en el buscador para localizar cualquier cliente de la empresa.'
                : $mensaje,
        ];
        if ($ok && $clientes) {
            Cache::put($cacheKey, $payload, 900);
        }

        return $payload;
    }

    /**
     * Búsqueda SAP filtrada (CardCode o CardName) — no recorre todo el año.
     *
     * @return array{ok: bool, clientes: array<int, array<string, mixed>>, mensaje: string|null}
     */
    protected function buscarClientesSapAsignacion(string $empresa, int $year, string $q): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return ['ok' => true, 'clientes' => [], 'mensaje' => null];
        }

        $cacheKey = 'pv.asig.cli.q.'.$empresa.'.'.$year.'.'.md5(mb_strtoupper($q));
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && array_key_exists('clientes', $cached)) {
            return $cached;
        }

        $api = app(AutinApiClient::class);
        $map = [];
        $ok = false;
        $mensaje = null;
        $pareceCodigo = (bool) preg_match('/^[A-Za-z0-9._\-]{2,40}$/', $q);

        foreach ($this->empresasFiltroVentas($empresa) as $empFiltro) {
            $intentos = [];
            if ($pareceCodigo) {
                $intentos[] = ['CardCode' => $q];
            }
            $intentos[] = ['CardName' => $q];

            foreach ($intentos as $extra) {
                $res = $api->ventas(array_merge([
                    'year' => $year,
                    'Empresa' => $empFiltro,
                    'per_page' => 80,
                    'page' => 1,
                ], $extra));
                if (empty($res['ok'])) {
                    if (! $ok) {
                        $mensaje = $res['message'] ?? 'Sin conexión a ventas SAP';
                    }
                    continue;
                }
                $ok = true;
                $body = is_array($res['body'] ?? null) ? $res['body'] : [];
                $rows = is_array($body['data'] ?? null) ? $body['data'] : [];
                foreach ($this->extraerClientesDeFilasVentas($rows, $empresa) as $key => $row) {
                    $map[$key] = $row;
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
            'mensaje' => $ok
                ? ($clientes ? null : 'SAP no devolvió clientes para “'.$q.'”')
                : $mensaje,
        ];
        Cache::put($cacheKey, $payload, 600);

        return $payload;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, array{codigo: string, nombre: string, empresa: string, activo: bool, departamento: string}>
     */
    protected function extraerClientesDeFilasVentas(array $rows, string $empresa): array
    {
        $map = [];
        foreach ($rows as $row) {
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

        return $map;
    }

    /**
     * @param  array<int, array<string, mixed>>  $clientes
     */
    protected function guardarClientesCatalogo(string $empresa, int $year, array $clientes, string $origen = 'sap'): void
    {
        if (! Schema::hasTable('tbl_pv_cliente_catalogo') || $clientes === []) {
            return;
        }
        $empresa = strtolower(trim($empresa));
        $now = now();
        foreach ($clientes as $row) {
            $codigo = trim((string) ($row['codigo'] ?? ''));
            if ($codigo === '') {
                continue;
            }
            $nombre = trim((string) ($row['nombre'] ?? ''));
            PvClienteCatalogo::query()->updateOrCreate(
                ['empresa' => $empresa, 'codigo' => $codigo],
                [
                    'nombre' => $nombre !== '' ? $nombre : $codigo,
                    'anio' => $year,
                    'origen' => $origen,
                    'synced_at' => $now,
                ]
            );
        }
    }

    /**
     * Productos ya conocidos (catálogo local, costos, asignaciones y proyecciones).
     * Solo codigo/nombre/grupo/costo. Si $cliente va vacío, devuelve el catálogo de la empresa.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function productosLocalesAsignacion(string $empresa, string $cliente = '', string $q = ''): array
    {
        $empresa = strtolower(trim($empresa));
        $cliente = trim($cliente);
        $q = trim($q);
        $map = [];

        $push = function (string $codigo, string $nombre, string $grupo = '', $costo = 0, string $moneda = 'MXN') use (&$map, $empresa, $q) {
            $codigo = trim($codigo);
            if ($codigo === '') {
                return;
            }
            $nombre = trim($nombre) !== '' ? trim($nombre) : $codigo;
            $grupo = trim($grupo);
            if ($q !== '') {
                $hay = mb_strtoupper($codigo.' '.$nombre.' '.$grupo);
                if (mb_strpos($hay, mb_strtoupper($q)) === false) {
                    return;
                }
            }
            $key = strtoupper($codigo);
            $costo = is_numeric($costo) ? (float) $costo : 0.0;
            if (! isset($map[$key])) {
                $map[$key] = [
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                    'empresa' => $empresa,
                    'grupo' => $grupo,
                    'grupo_id' => $grupo,
                    'costo' => $costo,
                    'moneda' => $moneda !== '' ? $moneda : 'MXN',
                ];

                return;
            }
            if ($map[$key]['nombre'] === $map[$key]['codigo'] && $nombre !== $codigo) {
                $map[$key]['nombre'] = $nombre;
            }
            if ($map[$key]['grupo'] === '' && $grupo !== '') {
                $map[$key]['grupo'] = $grupo;
                $map[$key]['grupo_id'] = $grupo;
            }
            if ($costo > 0) {
                $map[$key]['costo'] = $costo;
            }
        };

        if (Schema::hasTable('tbl_pv_producto_cliente_catalogo')) {
            $rows = PvProductoClienteCatalogo::query()
                ->where('empresa', $empresa)
                ->when($cliente !== '', function ($query) use ($cliente) {
                    $query->where('cliente_codigo', $cliente);
                })
                ->orderBy('nombre')
                ->limit(4000)
                ->get(['codigo', 'nombre', 'grupo', 'costo']);
            foreach ($rows as $row) {
                $push((string) $row->codigo, (string) ($row->nombre ?? ''), (string) ($row->grupo ?? ''), $row->costo);
            }
        }

        if (Schema::hasTable('tbl_pv_productos_costo')) {
            $hasCard = Schema::hasColumn('tbl_pv_productos_costo', 'card_code');
            $query = DB::table('tbl_pv_productos_costo')->where('empresa', $empresa);
            if ($cliente !== '' && $hasCard) {
                $query->where('card_code', $cliente);
            }
            $cols = ['producto_codigo', 'producto_nombre'];
            if (Schema::hasColumn('tbl_pv_productos_costo', 'costo_unitario')) {
                $cols[] = 'costo_unitario';
            }
            if (Schema::hasColumn('tbl_pv_productos_costo', 'moneda')) {
                $cols[] = 'moneda';
            }
            $rows = $query->select($cols)->distinct()->limit(4000)->get();
            foreach ($rows as $row) {
                $push(
                    (string) ($row->producto_codigo ?? ''),
                    (string) ($row->producto_nombre ?? ''),
                    '',
                    $row->costo_unitario ?? 0,
                    (string) ($row->moneda ?? 'MXN')
                );
            }
        }

        if (Schema::hasTable('tbl_pv_asignacion_productos') && Schema::hasTable('tbl_pv_asignaciones')) {
            $colCliente = Schema::hasColumn('tbl_pv_asignaciones', 'cliente_codigo') ? 'cliente_codigo' : 'centro_codigo';
            $colProd = Schema::hasColumn('tbl_pv_asignacion_productos', 'producto_codigo') ? 'producto_codigo' : 'cuenta_codigo';
            $colNom = Schema::hasColumn('tbl_pv_asignacion_productos', 'producto_nombre')
                ? 'producto_nombre'
                : (Schema::hasColumn('tbl_pv_asignacion_productos', 'cuenta_nombre') ? 'cuenta_nombre' : null);
            $colLinea = Schema::hasColumn('tbl_pv_asignacion_productos', 'linea')
                ? 'linea'
                : (Schema::hasColumn('tbl_pv_asignacion_productos', 'agrupacion') ? 'agrupacion' : null);
            $query = DB::table('tbl_pv_asignacion_productos as p')
                ->join('tbl_pv_asignaciones as a', 'a.id', '=', 'p.asignacion_id')
                ->where('a.empresa', $empresa);
            if ($cliente !== '') {
                $query->where('a.'.$colCliente, $cliente);
            }
            $sel = ['p.'.$colProd.' as producto_codigo'];
            if ($colNom) {
                $sel[] = 'p.'.$colNom.' as producto_nombre';
            }
            if ($colLinea) {
                $sel[] = 'p.'.$colLinea.' as linea';
            }
            $rows = $query->select($sel)->distinct()->limit(3000)->get();
            foreach ($rows as $row) {
                $push(
                    (string) ($row->producto_codigo ?? ''),
                    (string) ($row->producto_nombre ?? ''),
                    (string) ($row->linea ?? '')
                );
            }
        }

        if (Schema::hasTable('tbl_pv_proyecciones') && Schema::hasColumn('tbl_pv_proyecciones', 'producto_codigo')) {
            $query = DB::table('tbl_pv_proyecciones')->where('empresa', $empresa);
            if ($cliente !== '' && Schema::hasColumn('tbl_pv_proyecciones', 'cliente_codigo')) {
                $query->where('cliente_codigo', $cliente);
            }
            $cols = ['producto_codigo'];
            if (Schema::hasColumn('tbl_pv_proyecciones', 'producto_nombre')) {
                $cols[] = 'producto_nombre';
            }
            $rows = $query->select($cols)->distinct()->limit(3000)->get();
            foreach ($rows as $row) {
                $push((string) ($row->producto_codigo ?? ''), (string) ($row->producto_nombre ?? ''));
            }
        }

        return $map;
    }

    /**
     * Productos del cliente en ventas SAP (CardCode).
     * Solo año en curso, enero–septiembre (sin retroceder a años anteriores).
     *
     * @return array{ok: bool, productos: array<int, array<string, mixed>>, mensaje: string|null}
     */
    protected function cargarProductosSapAsignacion(string $empresa, string $cliente, int $year, string $q = ''): array
    {
        $empresa = strtolower(trim($empresa));
        $cliente = trim($cliente);
        $q = trim($q);
        if ($cliente === '') {
            return ['ok' => true, 'productos' => [], 'mensaje' => null];
        }

        // Siempre año calendario actual, ene–sep (no el año del ciclo ni años pasados).
        $year = (int) date('Y');
        $fechaDesde = sprintf('%04d/01/01', $year);
        $fechaHasta = sprintf('%04d/09/30', $year);

        $cacheKey = 'pv.asig.prod.v5.'.$empresa.'.'.$year.'.ene-sep.'.md5(strtoupper($cliente).'|'.mb_strtoupper($q));
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && array_key_exists('productos', $cached)) {
            return $cached;
        }

        $pack = $this->cargarProductosSapAsignacionAnio($empresa, $cliente, $year, $q, $fechaDesde, $fechaHasta);
        if (! empty($pack['productos'])) {
            $pack['mensaje'] = $pack['mensaje'] ?: ('Ventas '.$year.' · ene–sep');
        } elseif (! empty($pack['ok'])) {
            $pack['mensaje'] = $q !== ''
                ? ('No se encontraron ventas para “'.$q.'” en '.$year.' (ene–sep).')
                : ('No se encontraron ventas para este cliente en '.$year.' (ene–sep).');
        }

        Cache::put($cacheKey, $pack, 900);

        return $pack;
    }

    /**
     * @return array{ok: bool, productos: array<int, array<string, mixed>>, mensaje: string|null}
     */
    protected function cargarProductosSapAsignacionAnio(
        string $empresa,
        string $cliente,
        int $year,
        string $q = '',
        ?string $fechaDesde = null,
        ?string $fechaHasta = null
    ): array {
        $api = app(AutinApiClient::class);
        $q = trim($q);
        $pareceCodigo = $q !== '' && (bool) preg_match('/^[A-Za-z0-9._\-]{2,80}$/', $q);
        $intentos = [['CardCode' => $cliente]];
        if ($q !== '') {
            $intentos = [];
            if ($pareceCodigo) {
                $intentos[] = ['CardCode' => $cliente, 'ItemCode' => $q];
            }
            $intentos[] = ['CardCode' => $cliente, 'ItemName' => $q];
        }
        $maxPages = $q === '' ? 40 : 5;
        $perPage = $q === '' ? 200 : 100;

        $base = [
            'year' => $year,
        ];
        if ($fechaDesde) {
            $base['fecha_desde'] = $fechaDesde;
        }
        if ($fechaHasta) {
            $base['fecha_hasta'] = $fechaHasta;
        }

        $map = [];
        $ok = false;
        $mensaje = null;
        foreach ($this->empresasFiltroVentas($empresa) as $empFiltro) {
            foreach ($intentos as $extra) {
                $pack = $api->ventasTodasPaginas(array_merge($base, [
                    'Empresa' => $empFiltro,
                ], $extra), $maxPages, 2, $perPage);
                if (empty($pack['ok'])) {
                    // Con filtro de fechas a veces la API marca incompleto pero sí trae filas útiles.
                    if (! empty($pack['rows'])) {
                        $ok = true;
                        foreach ($this->extraerProductosDeFilasVentas($pack['rows'] ?? [], $empresa, $cliente, $q) as $key => $row) {
                            $map[$key] = $row;
                        }
                        continue;
                    }
                    if (! $ok) {
                        $mensaje = $pack['message'] ?? 'Sin conexión a ventas SAP';
                    }
                    continue;
                }
                $ok = true;
                foreach ($this->extraerProductosDeFilasVentas($pack['rows'] ?? [], $empresa, $cliente, $q) as $key => $row) {
                    $map[$key] = $row;
                }
            }
        }

        $productos = array_values($map);
        $rango = ($fechaDesde && $fechaHasta) ? ' (ene–sep)' : '';

        return [
            'ok' => $ok,
            'productos' => $productos,
            'mensaje' => $ok
                ? ($productos ? null : ($q !== ''
                    ? 'Sin productos para “'.$q.'” en ventas '.$year.$rango.'.'
                    : 'Este cliente no tiene productos en ventas '.$year.$rango.'.'))
                : $mensaje,
        ];
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return array<string, array<string, mixed>>
     */
    protected function extraerProductosDeFilasVentas(array $rows, string $empresa, string $cliente = '', string $q = ''): array
    {
        $clienteKey = strtoupper(trim($cliente));
        $q = trim($q);
        $map = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $card = trim((string) ($row['CardCode'] ?? $row['Cardcode'] ?? ''));
            if ($clienteKey !== '' && $card !== '' && strtoupper($card) !== $clienteKey) {
                continue;
            }
            $item = trim((string) ($row['ItemCode'] ?? $row['Itemcode'] ?? ''));
            if ($item === '') {
                continue;
            }
            $itemName = trim((string) ($row['ItemName'] ?? $row['Dscription'] ?? $row['Itemname'] ?? ''));
            if ($q !== '') {
                $hay = mb_strtoupper($item.' '.$itemName);
                if (mb_strpos($hay, mb_strtoupper($q)) === false) {
                    continue;
                }
            }
            $linea = trim((string) ($row['U_LINEA_QV'] ?? $row['Linea'] ?? $row['linea'] ?? ''));
            // Total unidades del año (valor absoluto por línea para no anular NC).
            $qty = abs($this->cantidadVenta($row));
            if ($qty == 0.0) {
                $qty = abs($this->numeroVenta($row, ['Quantity', 'Cantidad', 'Qty', 'quantity', 'SalPackUn']));
            }
            $key = strtoupper($item);
            if (! isset($map[$key])) {
                $map[$key] = [
                    'codigo' => $item,
                    'nombre' => $itemName !== '' ? $itemName : $item,
                    'empresa' => $empresa,
                    'grupo' => $linea,
                    'grupo_id' => $linea,
                    'costo' => $this->costoVenta($row),
                    'moneda' => 'MXN',
                    'unidades' => round($qty, 4),
                ];
            } else {
                $map[$key]['unidades'] = round(((float) ($map[$key]['unidades'] ?? 0)) + $qty, 4);
                $costo = $this->costoVenta($row);
                if ($costo > 0) {
                    $map[$key]['costo'] = $costo;
                }
                if ($map[$key]['nombre'] === $item && $itemName !== '') {
                    $map[$key]['nombre'] = $itemName;
                }
                if ($map[$key]['grupo'] === '' && $linea !== '') {
                    $map[$key]['grupo'] = $linea;
                    $map[$key]['grupo_id'] = $linea;
                }
            }
        }

        return $map;
    }

    /**
     * @param  array<int, array<string, mixed>>  $productos
     */
    protected function guardarProductosCatalogo(string $empresa, string $cliente, int $year, array $productos, string $origen = 'sap'): void
    {
        if (! Schema::hasTable('tbl_pv_producto_cliente_catalogo') || $productos === []) {
            return;
        }
        $empresa = strtolower(trim($empresa));
        $cliente = trim($cliente);
        if ($cliente === '') {
            return;
        }
        $now = now();
        foreach ($productos as $row) {
            $codigo = trim((string) ($row['codigo'] ?? ''));
            if ($codigo === '') {
                continue;
            }
            $nombre = trim((string) ($row['nombre'] ?? ''));
            $grupo = trim((string) ($row['grupo'] ?? $row['grupo_id'] ?? ''));
            PvProductoClienteCatalogo::query()->updateOrCreate(
                [
                    'empresa' => $empresa,
                    'cliente_codigo' => $cliente,
                    'codigo' => mb_substr($codigo, 0, 80),
                ],
                [
                    'nombre' => mb_substr($nombre !== '' ? $nombre : $codigo, 0, 220),
                    'grupo' => $grupo !== '' ? mb_substr($grupo, 0, 80) : null,
                    'costo' => is_numeric($row['costo'] ?? null) ? (float) $row['costo'] : 0,
                    'anio' => $year,
                    'origen' => $origen,
                    'synced_at' => $now,
                ]
            );
        }
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
        $resolved = Cache::get($this->claveAnioVentas($empresa, $requested));
        if (is_numeric($resolved)) {
            $pack = $this->cargarClientesEmpresaAnio($empresa, (int) $resolved);
            if (! empty($pack['ok'])) {
                if ((int) $resolved !== $requested && ! empty($pack['clientes'])) {
                    $pack['mensaje'] = 'Mostrando clientes con venta en '.$resolved.' (aún no hay en '.$requested.')';
                }

                return $pack;
            }
        }

        $last = null;
        for ($y = $year; $y >= $year - 3 && $y >= 2000; $y--) {
            $pack = $this->cargarClientesEmpresaAnio($empresa, $y);
            $last = $pack;
            if (! empty($pack['clientes'])) {
                Cache::put($this->claveAnioVentas($empresa, $requested), $y, 1800);
                if ($y !== $requested) {
                    $pack['mensaje'] = 'Mostrando clientes con venta en '.$y.' (aún no hay en '.$requested.')';
                }

                return $pack;
            }
            if (empty($pack['ok'])) {
                break;
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
        $idx = $this->indiceVentasEmpresa($empresa, $year);
        $clientes = is_array($idx['clientes'] ?? null) ? $idx['clientes'] : [];

        return [
            'ok' => ! empty($idx['ok']),
            'clientes' => $clientes,
            'mensaje' => ! empty($idx['ok'])
                ? ($clientes ? null : 'No hay clientes con venta en '.$year)
                : ($idx['mensaje'] ?? 'Sin clientes SAP'),
        ];
    }

    protected function claveAnioVentas(string $empresa, int $year): string
    {
        return 'pv.ventas.anio.'.strtolower(trim($empresa)).'.'.$year;
    }

    /**
     * Una sola bajada de OINV + ORIN por empresa y año.
     * Clientes y productos de cada cliente salen de ese índice, sin volver a paginar SAP.
     *
     * @return array{ok: bool, mensaje: string|null, clientes: array<int, array<string, mixed>>, productos: array<int, array<string, mixed>>, por_cliente: array<string, array<int, array<string, mixed>>>}
     */
    protected function indiceVentasEmpresa(string $empresa, int $year): array
    {
        $empresa = strtolower(trim($empresa));
        $memoKey = $empresa.'|'.$year;
        if (isset($this->pvIndiceVentasMemo[$memoKey]) && is_array($this->pvIndiceVentasMemo[$memoKey])) {
            return $this->pvIndiceVentasMemo[$memoKey];
        }

        $cacheKey = 'pv.ventas.idx.v1.'.$empresa.'.'.$year;
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && array_key_exists('clientes', $cached)) {
            return $this->pvIndiceVentasMemo[$memoKey] = $cached;
        }

        $pack = $this->filasVentasEmpresa($empresa, $year, [], 40);
        $clientes = [];
        $productos = [];
        $porCliente = [];

        foreach ($pack['rows'] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $card = trim((string) ($row['CardCode'] ?? $row['Cardcode'] ?? ''));
            $cardKey = strtoupper($card);
            if ($cardKey !== '') {
                $name = trim((string) ($row['CardName'] ?? $row['Cardname'] ?? ''));
                if (! isset($clientes[$cardKey])) {
                    $clientes[$cardKey] = [
                        'codigo' => $card,
                        'nombre' => $name !== '' ? $name : $card,
                        'empresa' => $empresa,
                        'activo' => true,
                        'departamento' => '',
                    ];
                } elseif ($clientes[$cardKey]['nombre'] === $clientes[$cardKey]['codigo'] && $name !== '') {
                    $clientes[$cardKey]['nombre'] = $name;
                }
            }

            $item = trim((string) ($row['ItemCode'] ?? $row['Itemcode'] ?? ''));
            if ($item === '') {
                continue;
            }
            $itemName = trim((string) ($row['ItemName'] ?? $row['Dscription'] ?? $row['Itemname'] ?? ''));
            $linea = trim((string) ($row['U_LINEA_QV'] ?? $row['Linea'] ?? $row['linea'] ?? ''));
            $itemKey = strtoupper($item);
            if (! isset($productos[$itemKey])) {
                $productos[$itemKey] = [
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
                    $productos[$itemKey]['costo'] = $costo;
                }
                if ($productos[$itemKey]['nombre'] === $item && $itemName !== '') {
                    $productos[$itemKey]['nombre'] = $itemName;
                }
            }
            if ($cardKey !== '') {
                $porCliente[$cardKey][$itemKey] = true;
            }
        }

        $cmp = function ($a, $b) {
            return strcasecmp((string) ($a['nombre'] ?? ''), (string) ($b['nombre'] ?? ''))
                ?: strcasecmp((string) ($a['codigo'] ?? ''), (string) ($b['codigo'] ?? ''));
        };
        $listaProductos = array_values($productos);
        usort($listaProductos, $cmp);
        $listaClientes = array_values($clientes);
        usort($listaClientes, $cmp);

        $porOut = [];
        foreach ($porCliente as $cardKey => $items) {
            $list = [];
            foreach (array_keys($items) as $itemKey) {
                if (isset($productos[$itemKey])) {
                    $list[] = $productos[$itemKey];
                }
            }
            usort($list, $cmp);
            $porOut[$cardKey] = $list;
        }

        $payload = [
            'ok' => ! empty($pack['ok']),
            'mensaje' => $pack['mensaje'] ?? null,
            'clientes' => $listaClientes,
            'productos' => $listaProductos,
            'por_cliente' => $porOut,
        ];
        if (! empty($payload['ok'])) {
            Cache::put($cacheKey, $payload, ($listaClientes || $listaProductos) ? 1800 : 600);
        }

        return $this->pvIndiceVentasMemo[$memoKey] = $payload;
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
     * Al guardar una asignación: siembra Precio global (mes=0) en el maestro local.
     * Usa precio enviado; si falta, consulta listaPreciosventa.
     * No pisa precios ya capturados (costo_unitario > 0).
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
        $cardKey = strtoupper(str_replace(' ', '', $card));
        if ($empresa === '' || $card === '' || in_array($cardKey, ['SIN_CC', 'EMPRESA'], true)) {
            return 0;
        }

        $anio = $this->anioProyeccionCostos(null, (string) ($asig->ciclo_codigo ?? ''));
        $flags = $this->flagsMaestroCosto();
        $hasCard = $flags['card'];
        $hasCardName = $flags['card_name'];
        $hasMes = $flags['mes'];
        $hasAnio = $flags['anio'];
        $userId = auth()->id();
        $now = now();

        $desired = [];
        foreach ($cuentas as $cta) {
            if (! is_array($cta)) {
                continue;
            }
            $item = trim((string) ($cta['codigo'] ?? $cta['cuenta_codigo'] ?? ''));
            if ($item === '') {
                continue;
            }
            $precio = round(max(0, (float) ($cta['precio'] ?? $cta['costo'] ?? 0)), 4);
            $moneda = strtoupper(trim((string) ($cta['moneda'] ?? 'MXN'))) ?: 'MXN';
            if (! in_array($moneda, ['MXN', 'USD'], true)) {
                $moneda = 'MXN';
            }
            $nombre = trim((string) ($cta['nombre'] ?? $cta['cuenta_nombre'] ?? ''));
            if ($nombre === '' || strcasecmp($nombre, $item) === 0) {
                $nombre = $item;
            }
            $desired[strtoupper($item)] = [
                'codigo' => $item,
                'nombre' => mb_substr($nombre, 0, 180),
                'precio' => $precio,
                'moneda' => $moneda,
            ];
        }
        if (! $desired) {
            return 0;
        }

        $qExist = PvProductoCosto::query()
            ->where('empresa', $empresa)
            ->whereIn('producto_codigo', array_column($desired, 'codigo'));
        if ($hasAnio) {
            $qExist->where('anio', $anio);
        }
        if ($hasCard) {
            $qExist->where('card_code', $card);
        }
        if ($hasMes) {
            $qExist->where('mes', 0);
        }
        $existentes = [];
        foreach ($qExist->get(['id', 'producto_codigo', 'costo_unitario', 'moneda', 'card_name']) as $prev) {
            $existentes[strtoupper(trim((string) $prev->producto_codigo))] = $prev;
        }

        // Opción C: no tocar filas con Precio global ya capturado.
        $faltanLista = [];
        foreach ($desired as $key => $d) {
            $prev = $existentes[$key] ?? null;
            if ($prev && (float) $prev->costo_unitario > 0) {
                unset($desired[$key]);

                continue;
            }
            if ((float) ($d['precio'] ?? 0) <= 0) {
                $faltanLista[] = $d['codigo'];
            }
        }
        if (! $desired) {
            return 0;
        }

        if ($faltanLista) {
            if (function_exists('set_time_limit')) {
                @set_time_limit(120);
            }
            try {
                $pack = $this->cargarListasPreciosCliente($empresa, $card, $anio, $faltanLista, false);
            } catch (Throwable $e) {
                $pack = ['ok' => false, 'por_articulo' => [], 'mensaje' => $e->getMessage()];
            }
            $porArt = is_array($pack['por_articulo'] ?? null) ? $pack['por_articulo'] : [];
            foreach ($faltanLista as $codigo) {
                $key = strtoupper($codigo);
                if (! isset($desired[$key]) || (float) $desired[$key]['precio'] > 0) {
                    continue;
                }
                $hit = $porArt[$codigo] ?? $porArt[$key] ?? null;
                if (! is_array($hit)) {
                    continue;
                }
                $precioLista = round((float) ($hit['precio'] ?? 0), 4);
                if ($precioLista <= 0) {
                    continue;
                }
                $monedaLista = strtoupper(trim((string) ($hit['moneda'] ?? 'MXN'))) ?: 'MXN';
                if (! in_array($monedaLista, ['MXN', 'USD'], true)) {
                    $monedaLista = 'MXN';
                }
                $desired[$key]['precio'] = $precioLista;
                $desired[$key]['moneda'] = $monedaLista;
                $nombreLista = trim((string) ($hit['nombre'] ?? ''));
                if ($nombreLista !== '' && (
                    $desired[$key]['nombre'] === ''
                    || strcasecmp($desired[$key]['nombre'], $desired[$key]['codigo']) === 0
                )) {
                    $desired[$key]['nombre'] = mb_substr($nombreLista, 0, 180);
                }
            }
        }

        $inserts = [];
        $historial = [];
        $creados = 0;
        foreach ($desired as $key => $d) {
            if ((float) $d['precio'] <= 0) {
                continue;
            }
            $prev = $existentes[$key] ?? null;
            if ($prev) {
                // Solo rellena vacío; no sobrescribe capturado.
                if ((float) $prev->costo_unitario > 0) {
                    continue;
                }
                PvProductoCosto::query()->where('id', $prev->id)->update([
                    'producto_nombre' => $d['nombre'],
                    'costo_unitario' => $d['precio'],
                    'moneda' => $d['moneda'],
                    'updated_by' => $userId,
                    'updated_at' => $now,
                ]);
                $historial[] = $this->filaHistorialPrecio(
                    $empresa,
                    $d['codigo'],
                    $d['nombre'],
                    (float) $prev->costo_unitario,
                    (string) ($prev->moneda ?: 'MXN'),
                    $d['precio'],
                    $d['moneda'],
                    $userId,
                    $card,
                    $cardName,
                    $anio,
                    $now
                );
                $creados++;
                continue;
            }

            $row = [
                'empresa' => $empresa,
                'producto_codigo' => $d['codigo'],
                'producto_nombre' => $d['nombre'],
                'costo_unitario' => $d['precio'],
                'moneda' => $d['moneda'],
                'updated_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if ($hasAnio) {
                $row['anio'] = $anio;
            }
            if ($hasCard) {
                $row['card_code'] = $card;
            }
            if ($hasCardName && $cardName !== '') {
                $row['card_name'] = mb_substr($cardName, 0, 180);
            }
            if ($hasMes) {
                $row['mes'] = 0;
            }
            $inserts[] = $row;
            $historial[] = $this->filaHistorialPrecio(
                $empresa,
                $d['codigo'],
                $d['nombre'],
                null,
                null,
                $d['precio'],
                $d['moneda'],
                $userId,
                $card,
                $cardName,
                $anio,
                $now
            );
            $creados++;
        }

        foreach (array_chunk($inserts, 200) as $chunk) {
            DB::table('tbl_pv_productos_costo')->insert($chunk);
        }
        $historial = array_values(array_filter($historial));
        if ($historial && Schema::hasTable('tbl_pv_productos_costo_historial')) {
            foreach (array_chunk($historial, 200) as $chunk) {
                DB::table('tbl_pv_productos_costo_historial')->insert($chunk);
            }
        }

        return $creados;
    }

    /**
     * @return array{anio: bool, card: bool, card_name: bool, mes: bool}
     */
    protected function flagsMaestroCosto(): array
    {
        static $flags = null;
        if ($flags === null) {
            $flags = [
                'anio' => $this->hasAnioCostos(),
                'card' => Schema::hasColumn('tbl_pv_productos_costo', 'card_code'),
                'card_name' => Schema::hasColumn('tbl_pv_productos_costo', 'card_name'),
                'mes' => Schema::hasColumn('tbl_pv_productos_costo', 'mes'),
            ];
        }

        return $flags;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function filaHistorialPrecio(
        string $empresa,
        string $codigo,
        string $nombre,
        ?float $precioAnterior,
        ?string $monedaAnterior,
        float $precioNuevo,
        string $monedaNueva,
        $userId,
        string $cardCode,
        string $cardName,
        int $anio,
        $now
    ): ?array {
        if (! Schema::hasTable('tbl_pv_productos_costo_historial')) {
            return null;
        }
        $precioNuevo = round($precioNuevo, 4);
        $precioAnterior = $precioAnterior === null ? null : round($precioAnterior, 4);
        $monedaNueva = strtoupper(trim($monedaNueva)) ?: 'MXN';
        $monedaAnterior = $monedaAnterior !== null ? (strtoupper(trim($monedaAnterior)) ?: 'MXN') : null;
        if ($precioAnterior !== null && $precioAnterior === $precioNuevo && $monedaAnterior === $monedaNueva) {
            return null;
        }

        $payload = [
            'empresa' => strtoupper(trim($empresa)),
            'producto_codigo' => trim($codigo),
            'producto_nombre' => mb_substr($nombre !== '' ? $nombre : $codigo, 0, 180),
            'precio_anterior' => $precioAnterior,
            'precio_nuevo' => $precioNuevo,
            'moneda_anterior' => $monedaAnterior,
            'moneda_nueva' => $monedaNueva,
            'origen' => 'asignacion',
            'created_by' => $userId,
            'created_at' => $now,
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
            $payload['mes'] = 0;
        }

        return $payload;
    }

    /**
     * @param  array<int, array<string, mixed>>  $cuentas
     */
    protected function syncCuentas(PvAsignacion $asig, array $cuentas): void
    {
        PvAsignacionProducto::query()->where('asignacion_id', $asig->id)->delete();
        $now = now();
        $rows = [];
        $seen = [];
        foreach ($cuentas as $cta) {
            $codigo = trim((string) ($cta['codigo'] ?? $cta['cuenta_codigo'] ?? ''));
            if ($codigo === '') {
                continue;
            }
            $key = strtoupper($codigo);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $nombre = trim((string) ($cta['nombre'] ?? $cta['cuenta_nombre'] ?? ''));
            $linea = trim((string) ($cta['agrupacion'] ?? $cta['linea'] ?? ''));
            $rows[] = [
                'asignacion_id' => $asig->id,
                'producto_codigo' => mb_substr($codigo, 0, 80),
                'producto_nombre' => $nombre !== '' ? mb_substr($nombre, 0, 180) : null,
                'linea' => $linea !== '' ? mb_substr($linea, 0, 80) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('tbl_pv_asignacion_productos')->insert($chunk);
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
        $tipos = $this->tiposPermisoPorClave();
        $now = now();
        $rows = [];
        $vistos = [];
        foreach ($clavesAsig as $clave) {
            $permisoId = $tipos[$clave] ?? null;
            if (! $permisoId || isset($vistos[$permisoId])) {
                continue;
            }
            $vistos[$permisoId] = true;
            $rows[] = [
                    'asignacion_id' => $asig->id,
                'permiso_id' => $permisoId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($rows) {
            DB::table('tbl_pv_asignacion_permisos')->insert($rows);
        }
    }

    /**
     * @return array<string, int>
     */
    protected function tiposPermisoPorClave(): array
    {
        static $map = null;
        if ($map !== null) {
            return $map;
        }
        $map = [];
        if (Schema::hasTable('tbl_pv_tipos_permiso')) {
            foreach (PvTipoPermiso::query()->get(['id', 'clave']) as $tipo) {
                $clave = (string) $tipo->clave;
                if ($clave !== '') {
                    $map[$clave] = (int) $tipo->id;
                }
            }
        }

        return $map;
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
