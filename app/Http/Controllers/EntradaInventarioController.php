<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EntradaInventario;
use App\Models\EntradaInventarioDet;
use App\Models\OrdenCompra;
use App\Traits\CargaMenuTrait;
use App\Traits\MenuTrait;
use App\Traits\DatosimpleTraits;
use App\Traits\SistemasTraits;

use App\Models\OrdenCompra_det; // también ajusté aquí el nombre

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;



class EntradaInventarioController extends Controller
{

    use MenuTrait;
    use DatosimpleTraits;
    use CargaMenuTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $comunesymenus = $this->cargarDatosComunes();  // carga los menus

        // Elimina recepciones "fantasma" creadas al abrir el formulario y nunca guardadas
        $borradoresVacios = EntradaInventario::whereDoesntHave('detalles', function ($q) {
            $q->where('cantidad_recibida', '>', 0);
        })->pluck('id');

        if ($borradoresVacios->isNotEmpty()) {
            EntradaInventarioDet::whereIn('entrada_inventario_id', $borradoresVacios)->delete();
            EntradaInventario::whereIn('id', $borradoresVacios)->delete();
        }

        // Órdenes pendientes de recepción (completa o parcial) — misma base del select y del offcanvas
        // COALESCE: cantidad_recibida NULL se trata como 0 (órdenes recién enviadas)
        $ordenesCompra = OrdenCompra::with(['proveedor', 'detalles', 'entradasInventario'])
            ->where('estado', 'enviado_proveedor')
            ->whereHas('detalles', function ($q) {
                $q->whereRaw('cantidad > COALESCE(cantidad_recibida, 0)');
            })
            ->orderByDesc('created_at')
            ->get();

        $entradas = EntradaInventario::with(['ordenCompra.proveedor', 'ordenCompra.detalles', 'detalles'])
            ->latest()
            ->get();

        // Pendientes = mismas órdenes disponibles para crear recepción
        $ordenesSinRecepcion = $ordenesCompra;

        $ordenesConRecepcion = $ordenesCompra->filter(function ($orden) {
            return $orden->entradasInventario->isNotEmpty();
        });

        $ordenesEnviadas = $ordenesCompra;
        
        return view('Entradas.index', array_merge( compact('comunesymenus','entradas','ordenesCompra','varpantallas','varsubmenus','ordenesEnviadas','ordenesConRecepcion','ordenesSinRecepcion')));
    }

    // public function store(Request $request)
        // {
        //     try {
        //         $entrada = EntradaInventario::create($request->all());
        //         // Verificación final
        //         if ($entrada) {
        //             return redirect()->route('Entradas.index')->with("success", "¡Se guardaron los cambios correctamente!");
        //         } else {
        //             return redirect()->route('Entradas.index')->with("warning", "¡No se guardaron los cambios correctamente!");
        //         }
        //     } catch (\Exception $e) {
        //         return response()->json(['success' => false, 'message' => 'Error al crear la entrada: ' . $e->getMessage()]);
        //     }
    // }

    public function store(Request $request)
    {
        $request->validate([
            'ordenCompraId' => 'required|integer|exists:tblordencompra_enc,id',
        ]);

        $orden = OrdenCompra::findOrFail($request->ordenCompraId);

        if ($orden->estado !== 'enviado_proveedor') {
            return redirect()
                ->route('Entradas.index')
                ->with('error_msg', 'Solo se pueden crear recepciones de órdenes enviadas al proveedor.');
        }

        // PRG: evita el aviso nativo del navegador al refrescar/volver tras un POST
        return redirect()->route('Entradas.create', ['ordenCompraId' => (int) $request->ordenCompraId]);
    }

    public function create($ordenCompraId)
    {
        $orden = OrdenCompra::findOrFail($ordenCompraId);

        if ($orden->estado !== 'enviado_proveedor') {
            return redirect()
                ->route('Entradas.index')
                ->with('error_msg', 'Solo se pueden crear recepciones de órdenes enviadas al proveedor.');
        }

        $comunesymenus = $this->cargarDatosComunes();

        return view('Entradas.create', array_merge($comunesymenus, [
            'ordenCompraId' => (int) $ordenCompraId,
        ]));
    }

    public function show($id)
    {
        $comunesymenus = $this->cargarDatosComunes();  // carga los menus
        $entrada = EntradaInventario::findOrFail($id);
        return view('Entradas.show', array_merge($comunesymenus, compact('entrada')));
    }

    public function edit($id)
    {
        $comunesymenus = $this->cargarDatosComunes();  // carga los menus
        $entrada = EntradaInventario::with('detalles.producto')->findOrFail($id);
        return view('Entradas.edit', array_merge($comunesymenus, [
            'entradaId' => $entrada->id,
        ]));
    }


    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $entrada = EntradaInventario::with('detalles')->findOrFail($id);

            // Solo se permite eliminar dentro del primer día desde su creación
            if ($entrada->created_at->lt(now()->subDay())) {
                DB::rollBack();
                return redirect()
                    ->route('Entradas.index')
                    ->with('error_msg', 'No se puede eliminar la recepción: ya pasó más de un día desde su creación.');
            }

            // Eliminar movimientos de inventario ligados a esta recepción
            DB::table('tblmovimientos_inventario')
                ->where(function ($q) use ($id) {
                    $q->where('entrada_inventario_id', $id)
                        ->orWhere('documento_referencia', 'Entrada #' . $id)
                        ->orWhere('documento_referencia', 'entrada #' . $id);
                })
                ->delete();

            // Revertir existencias y cantidades de la orden de compra
            foreach ($entrada->detalles as $detalle) {
                $cantidadRecibida = (float) ($detalle->cantidad_recibida ?? 0);
                if ($cantidadRecibida <= 0) {
                    continue;
                }

                if (!empty($entrada->id_almacen) && !empty($entrada->id_ubicacion)) {
                    $existencia = DB::table('tblexistencias')
                        ->where('id_producto', $detalle->producto_id)
                        ->where('id_almacen', $entrada->id_almacen)
                        ->where('id_ubicacion', $entrada->id_ubicacion)
                        ->first();

                    if ($existencia) {
                        $nuevaCantidad = max(0, ((float) $existencia->cantidad_existente) - $cantidadRecibida);

                        DB::table('tblexistencias')
                            ->where('id', $existencia->id)
                            ->update([
                                'cantidad_existente' => $nuevaCantidad,
                                'updated_at' => now(),
                            ]);
                    }
                }

                // Ajustar cantidad recibida en la orden de compra (sin quedar negativa)
                $detalleOrden = DB::table('tblordencompra_det')
                    ->where('orden_compra_id', $entrada->orden_compra_id)
                    ->where('producto_id', $detalle->producto_id)
                    ->first();

                if ($detalleOrden) {
                    $nuevaRecibidaOrden = max(0, ((float) $detalleOrden->cantidad_recibida) - $cantidadRecibida);
                    DB::table('tblordencompra_det')
                        ->where('id', $detalleOrden->id)
                        ->update([
                            'cantidad_recibida' => $nuevaRecibidaOrden,
                        ]);
                }
            }

            // Si la orden estaba cerrada, reabrirla a enviada al proveedor si quedan pendientes
            $ordenCompra = OrdenCompra::find($entrada->orden_compra_id);
            if ($ordenCompra && in_array($ordenCompra->estado, ['cerrada', 'cerrado'], true)) {
                $tienePendientes = DB::table('tblordencompra_det')
                    ->where('orden_compra_id', $entrada->orden_compra_id)
                    ->whereRaw('cantidad > COALESCE(cantidad_recibida, 0)')
                    ->exists();

                if ($tienePendientes) {
                    $ordenCompra->update(['estado' => 'enviado_proveedor']);
                }
            }

            // Eliminar detalles y la entrada
            EntradaInventarioDet::where('entrada_inventario_id', $entrada->id)->delete();
            $entrada->delete();

            DB::commit();

            return redirect()
                ->route('Entradas.index')
                ->with('success_msg', 'Recepción eliminada. Se revirtieron las existencias y la orden de compra.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->route('Entradas.index')
                ->with('error_msg', 'Error al eliminar la entrada: ' . $e->getMessage());
        }
    }

    public function exportExcel(Request $request)
    {
        try {
            // Consultar datos con filtros si están presentes
            $query = EntradaInventario::with(['ordenCompra.proveedor', 'detalles']);
            
            // Aplicar filtros si se proporcionan
            if ($request->has('desde') && $request->desde) {
                $query->whereDate('fecha_recepcion', '>=', $request->desde);
            }
            
            if ($request->has('hasta') && $request->hasta) {
                $query->whereDate('fecha_recepcion', '<=', $request->hasta);
            }
            
            if ($request->has('proveedor') && $request->proveedor) {
                $query->whereHas('ordenCompra.proveedor', function($q) use ($request) {
                    $q->where('nombre', 'like', '%' . $request->proveedor . '%');
                });
            }
            
            if ($request->has('estado') && $request->estado) {
                $query->where('status', $request->estado);
            }
            
            $entradas = $query->latest()->get();
            
            // Crear un nuevo archivo de Excel
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Recepciones de Inventario');
            
            // Establecer encabezados
            $sheet->setCellValue('A1', 'REPORTE DE RECEPCIONES DE INVENTARIO');
            $sheet->mergeCells('A1:H1');
            
            // Dar formato al título
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Establecer encabezados de columnas
            $sheet->setCellValue('A3', 'ID');
            $sheet->setCellValue('B3', 'Orden de Compra');
            $sheet->setCellValue('C3', 'Proveedor');
            $sheet->setCellValue('D3', 'Fecha de Recepción');
            $sheet->setCellValue('E3', 'Estado');
            $sheet->setCellValue('F3', 'Productos Recibidos');
            $sheet->setCellValue('G3', 'Total Productos');
            $sheet->setCellValue('H3', 'Progreso (%)');
            
            // Dar formato a los encabezados
            $headersStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4e73df'], // Color primario de Bootstrap
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ];
            
            $sheet->getStyle('A3:H3')->applyFromArray($headersStyle);
            $sheet->getRowDimension(3)->setRowHeight(20);
            
            // Llenar datos
            $row = 4;
            foreach ($entradas as $entrada) {
                // Calcular progreso
                $totalPedido = 0;
                $totalRecibido = 0;
                
                foreach ($entrada->detalles as $detalle) {
                    $totalPedido += $detalle->cantidad_pedida;
                    $totalRecibido += $detalle->cantidad_recibida;
                }
                
                $porcentaje = $totalPedido > 0 ? round(($totalRecibido / $totalPedido) * 100) : 0;
                
                $sheet->setCellValue('A' . $row, $entrada->id);
                $sheet->setCellValue('B' . $row, $entrada->orden_compra_id);
                $sheet->setCellValue('C' . $row, $entrada->ordenCompra->proveedor->nombre ?? 'Sin proveedor');
                $sheet->setCellValue('D' . $row, \Carbon\Carbon::parse($entrada->fecha_recepcion)->format('d/m/Y'));
                $sheet->setCellValue('E' . $row, ucfirst($entrada->status ?? 'pendiente'));
                $sheet->setCellValue('F' . $row, $totalRecibido);
                $sheet->setCellValue('G' . $row, $totalPedido);
                $sheet->setCellValue('H' . $row, $porcentaje . '%');
                
                $row++;
            }
            
            // Autoajustar anchos de columnas
            foreach (range('A', 'H') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Definir estilo para los datos
            $dataStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ];
            
            if ($row > 4) {
                $sheet->getStyle('A4:H' . ($row - 1))->applyFromArray($dataStyle);
            }
            
            // Generar el archivo
            $writer = new Xlsx($spreadsheet);
            $fileName = 'Recepciones_Inventario_' . date('Y-m-d_H-i-s') . '.xlsx';
            $path = storage_path('app/public/exports/' . $fileName);
            
            // Asegurar que el directorio exista
            if (!file_exists(storage_path('app/public/exports'))) {
                mkdir(storage_path('app/public/exports'), 0755, true);
            }
            
            $writer->save($path);
            
            // Devolver el archivo
            return response()->download($path)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            return redirect()->route('Entradas.index')->with('error_msg', 'Error al exportar a Excel: ' . $e->getMessage());
        }
    }
}
