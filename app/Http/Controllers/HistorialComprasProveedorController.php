<?php

namespace App\Http\Controllers;

use App\Models\OrdenCompra;
use App\Traits\MenuTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HistorialComprasProveedorController extends Controller
{
    use MenuTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        $proveedorId = (int) $request->input('proveedor_id', 0);
        $producto = trim((string) $request->input('producto', ''));
        $desde = trim((string) $request->input('desde', ''));
        $hasta = trim((string) $request->input('hasta', ''));
        $estado = trim((string) $request->input('estado', ''));

        $filtrado = $this->consulta($proveedorId, $producto, $desde, $hasta, $estado);
        $grafica = $this->graficaCalidad(clone $filtrado);
        $topProveedores = $this->topProveedores(clone $filtrado);

        $resumen = (clone $filtrado)
            ->selectRaw('COUNT(DISTINCT e.id) as ordenes')
            ->selectRaw('COUNT(DISTINCT d.producto_id) as productos')
            ->selectRaw('COALESCE(SUM(IFNULL(d.cantidad, 0) * IFNULL(d.costo, 0)), 0) as importe')
            ->first();

        $lineas = $filtrado
            ->select([
                'e.id as orden_id',
                'e.folio',
                'e.nombre as orden_nombre',
                'e.fecha_creacion',
                'e.estado',
                'e.tipo_moneda',
                'e.proveedor_id',
                'p.nombre as proveedor',
                'd.id as detalle_id',
                'd.calidad_proveedor',
                'd.producto_id',
                'pr.nombre as producto',
                'd.marca',
                'd.cantidad',
                'd.unidad_medida',
                'd.costo',
                'd.cantidad_recibida',
            ])
            ->selectRaw('(IFNULL(d.cantidad, 0) * IFNULL(d.costo, 0)) as importe')
            ->orderByDesc('e.fecha_creacion')
            ->orderByDesc('e.id')
            ->paginate(40)
            ->withQueryString();

        $proveedores = DB::table('tblprovedores')
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $estados = OrdenCompra::$estados;

        return view('compras.historial_proveedores', compact(
            'varpantallas',
            'varsubmenus',
            'lineas',
            'proveedores',
            'estados',
            'resumen',
            'proveedorId',
            'producto',
            'desde',
            'hasta',
            'estado',
            'grafica',
            'topProveedores'
        ));
    }

    public function actualizarCalidad(Request $request): JsonResponse
    {
        $calidades = ['EXCELENTE', 'BUENO', 'REGULAR', 'MALO'];
        $detalleId = (int) $request->input('detalle_id', 0);
        $calidad = strtoupper(trim((string) $request->input('calidad', '')));

        if ($detalleId <= 0 || ! in_array($calidad, $calidades, true)) {
            return response()->json(['ok' => false, 'message' => 'Calidad no válida.'], 422);
        }

        $existe = DB::table('tblordencompra_det')->where('id', $detalleId)->exists();
        if (! $existe) {
            return response()->json(['ok' => false, 'message' => 'Línea no encontrada.'], 404);
        }

        DB::table('tblordencompra_det')->where('id', $detalleId)->update([
            'calidad_proveedor' => $calidad,
        ]);

        return response()->json([
            'ok' => true,
            'detalle_id' => $detalleId,
            'calidad' => $calidad,
        ]);
    }

    private function consulta(int $proveedorId, string $producto, string $desde, string $hasta, string $estado)
    {
        $query = DB::table('tblordencompra_det as d')
            ->join('tblordencompra_enc as e', 'e.id', '=', 'd.orden_compra_id')
            ->leftJoin('tblprovedores as p', 'p.id', '=', 'e.proveedor_id')
            ->leftJoin('tblproductos as pr', 'pr.id', '=', 'd.producto_id');

        if ($proveedorId > 0) {
            $query->where('e.proveedor_id', $proveedorId);
        }
        if ($producto !== '') {
            $query->where('pr.nombre', 'like', '%'.$producto.'%');
        }
        if ($desde !== '') {
            $query->whereDate('e.fecha_creacion', '>=', $desde);
        }
        if ($hasta !== '') {
            $query->whereDate('e.fecha_creacion', '<=', $hasta);
        }
        if ($estado !== '' && array_key_exists($estado, OrdenCompra::$estados)) {
            $query->where('e.estado', $estado);
        }

        return $query;
    }

    /**
     * @return array<string, int>
     */
    private function graficaCalidad($query): array
    {
        $orden = ['EXCELENTE', 'BUENO', 'REGULAR', 'MALO', 'SIN CALIFICAR'];
        $totales = array_fill_keys($orden, 0);
        $caso = "CASE WHEN UPPER(TRIM(COALESCE(d.calidad_proveedor,''))) IN ('EXCELENTE','BUENO','REGULAR','MALO') THEN UPPER(TRIM(d.calidad_proveedor)) ELSE 'SIN CALIFICAR' END";

        $filas = $query
            ->selectRaw($caso.' as calidad')
            ->selectRaw('COUNT(*) as total')
            ->groupBy(DB::raw($caso))
            ->get();

        foreach ($filas as $fila) {
            $clave = (string) $fila->calidad;
            if (array_key_exists($clave, $totales)) {
                $totales[$clave] = (int) $fila->total;
            }
        }

        return $totales;
    }

    /**
     * @return list<array{proveedor: string, importe: float}>
     */
    private function topProveedores($query): array
    {
        $filas = $query
            ->selectRaw('e.proveedor_id')
            ->selectRaw("COALESCE(NULLIF(TRIM(MAX(p.nombre)), ''), 'Sin proveedor') as proveedor")
            ->selectRaw('COALESCE(SUM(IFNULL(d.cantidad, 0) * IFNULL(d.costo, 0)), 0) as importe')
            ->groupBy('e.proveedor_id')
            ->orderByDesc('importe')
            ->limit(10)
            ->get();

        return $filas->map(function ($fila) {
            return [
                'proveedor' => (string) $fila->proveedor,
                'importe' => round((float) $fila->importe, 2),
            ];
        })->all();
    }
}
