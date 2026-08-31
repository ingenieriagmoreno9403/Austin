<?php

namespace App\Http\Controllers;

use App\Models\OrdenCompra;
use App\Models\Productos;
use App\Models\ReporteNoExistencia;
use App\Traits\MenuTrait;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReporteNoExistenciasController extends Controller
{
    use MenuTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        $estatus = $request->input('estatus');
        $buscar = trim((string) $request->input('buscar', ''));

        $query = ReporteNoExistencia::with(['producto', 'pedido.cliente', 'cotizacion.cliente', 'ordenCompra'])
            ->orderByDesc('id');

        if ($estatus && in_array($estatus, [
            ReporteNoExistencia::ESTATUS_PENDIENTE,
            ReporteNoExistencia::ESTATUS_EN_COMPRA,
            ReporteNoExistencia::ESTATUS_ATENDIDO,
            ReporteNoExistencia::ESTATUS_CANCELADO,
        ], true)) {
            $query->where('estatus', $estatus);
        }

        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('folio', 'like', "%{$buscar}%")
                    ->orWhere('descripcion', 'like', "%{$buscar}%")
                    ->orWhereHas('producto', function ($p) use ($buscar) {
                        $p->where('nombre', 'like', "%{$buscar}%")
                            ->orWhere('sku', 'like', "%{$buscar}%");
                    })
                    ->orWhereHas('pedido', function ($p) use ($buscar) {
                        $p->where('folio', 'like', "%{$buscar}%");
                    })
                    ->orWhereHas('cotizacion', function ($c) use ($buscar) {
                        $c->where('folio', 'like', "%{$buscar}%");
                    });
            });
        }

        $reportes = $query->get();

        $resumen = [
            'pendiente' => ReporteNoExistencia::where('estatus', ReporteNoExistencia::ESTATUS_PENDIENTE)->count(),
            'en_compra' => ReporteNoExistencia::where('estatus', ReporteNoExistencia::ESTATUS_EN_COMPRA)->count(),
            'atendido' => ReporteNoExistencia::where('estatus', ReporteNoExistencia::ESTATUS_ATENDIDO)->count(),
            'cancelado' => ReporteNoExistencia::where('estatus', ReporteNoExistencia::ESTATUS_CANCELADO)->count(),
        ];

        return view('compras.reporte_no_existencias', compact(
            'varpantallas',
            'varsubmenus',
            'reportes',
            'resumen',
            'estatus',
            'buscar'
        ));
    }

    public function cambiarEstatus(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'estatus' => ['required', Rule::in([
                ReporteNoExistencia::ESTATUS_PENDIENTE,
                ReporteNoExistencia::ESTATUS_EN_COMPRA,
                ReporteNoExistencia::ESTATUS_ATENDIDO,
                ReporteNoExistencia::ESTATUS_CANCELADO,
            ])],
        ]);

        $reporte = ReporteNoExistencia::findOrFail($id);
        $reporte->update([
            'estatus' => $validated['estatus'],
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('compras.reporte_no_existencias.index')
            ->with('success', 'Reporte ' . ($reporte->folio ?: ('#' . $reporte->id)) . ' actualizado a ' . $validated['estatus'] . '.');
    }

    /**
     * Genera un borrador de orden de compra con los reportes seleccionados y elimina
     * esas líneas del reporte de no existencias.
     */
    public function generarOrdenCompra(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reportes' => ['required', 'array', 'min:1'],
            'reportes.*' => ['integer', 'exists:tbl_reporte_no_existencias,id'],
        ], [
            'reportes.required' => 'Seleccione al menos una línea para generar la orden de compra.',
        ]);

        $reportes = ReporteNoExistencia::with(['cotizacion.cliente', 'pedido.cliente'])
            ->whereIn('id', $validated['reportes'])
            ->get();

        if ($reportes->isEmpty()) {
            return redirect()
                ->route('compras.reporte_no_existencias.index')
                ->with('warning', 'No se encontraron líneas válidas para generar la orden de compra.');
        }

        $ordenCompra = DB::transaction(function () use ($reportes) {
            $origen = $this->resolverOrigenOrdenCompra($reportes);

            $ordenCompra = OrdenCompra::create(OrdenCompra::datosBorradorAutomatico([
                'folio' => 'TMP-' . uniqid(),
                'nombre' => $origen['nombre'],
                'descripcion_detalle' => null,
                'observaciones' => null,
            ]));

            $ordenCompra->update([
                'folio' => 'OC' . $ordenCompra->id . '-' . $origen['folio_origen'],
            ]);

            foreach ($reportes as $reporte) {
                $producto = Productos::find($reporte->producto_id);
                $costo = $producto ? (float) ($producto->costo_compra ?? 0) : 0;

                $ordenCompra->detalles()->create([
                    'producto_id' => $reporte->producto_id,
                    'cantidad' => $reporte->cantidad_faltante,
                    'observaciones' => null,
                    'costo' => $costo,
                ]);
            }

            // Se quitan las líneas seleccionadas del reporte.
            ReporteNoExistencia::whereIn('id', $reportes->pluck('id'))->delete();

            return $ordenCompra->fresh();
        });

        return redirect()
            ->route('ordcompras.index')
            ->with('successgenordencompra', 'Orden de compra ' . $ordenCompra->folio . ' generada con ' . $reportes->count()
                . ' partida(s). Complétela asignando proveedor y datos faltantes');
    }

    /**
     * Nombre y folio de origen a partir de la cotización (o pedido) del reporte.
     *
     * @param  \Illuminate\Support\Collection<int, ReporteNoExistencia>  $reportes
     * @return array{nombre: string, folio_origen: string}
     */
    private function resolverOrigenOrdenCompra($reportes): array
    {
        $conCotizacion = $reportes->first(function (ReporteNoExistencia $reporte) {
            return $reporte->cotizacion !== null;
        });

        if ($conCotizacion) {
            $cotizacion = $conCotizacion->cotizacion;
            $folioCotizacion = $cotizacion->folio ?: ('#' . $cotizacion->id);
            $cliente = optional($cotizacion->cliente)->nombre;
            $nombre = trim($folioCotizacion . ($cliente ? ' - ' . $cliente : ''));

            return [
                'nombre' => $nombre !== '' ? $nombre : 'Cotización ' . $folioCotizacion,
                'folio_origen' => $folioCotizacion,
            ];
        }

        $conPedido = $reportes->first(function (ReporteNoExistencia $reporte) {
            return $reporte->pedido !== null;
        });

        if ($conPedido) {
            $pedido = $conPedido->pedido;
            $folioPedido = $pedido->folio ?: ('#' . $pedido->id);
            $cliente = optional($pedido->cliente)->nombre;
            $nombre = trim($folioPedido . ($cliente ? ' - ' . $cliente : ''));

            return [
                'nombre' => $nombre !== '' ? $nombre : 'Pedido ' . $folioPedido,
                'folio_origen' => $folioPedido,
            ];
        }

        return [
            'nombre' => 'Compra desde reporte de no existencias',
            'folio_origen' => now()->format('Ymd'),
        ];
    }
}
