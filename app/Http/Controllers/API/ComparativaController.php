<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Licitacion;
use Illuminate\Support\Facades\DB;

class ComparativaController extends Controller
{
    /**
     * Compara los datos de una licitación con la factura XML asociada
     *
     * @param int $licitacionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function comparar($licitacionId)
    {
        try {
            // Obtener datos de la licitación
            $licitacion = Licitacion::findOrFail($licitacionId);
            
            // Obtener proveedor
            $proveedor = DB::table('tblprovedores')
                ->where('id', $licitacion->proveedor_adjudicado_id)
                ->first();
                
            if (!$proveedor) {
                return response()->json([
                    'error' => 'No se encontró el proveedor adjudicado para esta licitación'
                ], 404);
            }
            
            // Obtener detalles con cantidad solicitada
            $detallesLicitacion = DB::table('tbllicitacionproducto_proveedor AS lpp')
                ->join('tbllicitacion_det AS ld', function($join) use ($licitacionId) {
                    $join->on('ld.licitacion_id', '=', 'lpp.licitacion_id')
                         ->on('ld.producto_id', '=', 'lpp.producto_id')
                         ->where('ld.licitacion_id', '=', $licitacionId);
                })
                ->join('tblproductos', 'tblproductos.id', '=', 'lpp.producto_id')
                ->where('lpp.licitacion_id', $licitacionId)
                ->where('lpp.proveedor_id', $licitacion->proveedor_adjudicado_id)
                ->select('lpp.*', 'tblproductos.nombre', 'ld.cantidad')
                ->get();
                
            // Calcular subtotal multiplicando costo por cantidad
            $subtotalLicitacion = $detallesLicitacion->sum(function ($item) {
                return $item->costo * $item->cantidad;
            });
            
            $totalLicitacion = $subtotalLicitacion * 1.16; // Asumiendo IVA del 16%
            
            // Buscar factura XML asociada a esta licitación
            $factura = DB::table('tblfacturas_xml')
                ->where('licitacion_id', $licitacionId)
                ->where('proveedor_id', $licitacion->proveedor_adjudicado_id)
                ->first();
                
            // Preparar respuesta
            $response = [
                'licitacion' => [
                    'id' => $licitacion->id,
                    'nombre' => $licitacion->nombre,
                    'fecha_creacion' => $licitacion->fecha_creacion,
                    'proveedor_id' => $licitacion->proveedor_adjudicado_id,
                    'proveedor_nombre' => $proveedor->nombre,
                    'subtotal' => $subtotalLicitacion,
                    'total' => $totalLicitacion,
                    'detalles' => $detallesLicitacion
                ]
            ];
            
            // Si hay factura, agregarla a la respuesta
            if ($factura) {
                $response['factura'] = [
                    'id' => $factura->id,
                    'uuid' => $factura->uuid,
                    'rfc_emisor' => $factura->rfc_emisor,
                    'nombre_emisor' => $factura->nombre_emisor,
                    'rfc_receptor' => $factura->rfc_receptor,
                    'fecha' => $factura->created_at,
                    'subtotal' => $factura->subtotal,
                    'total' => $factura->total
                ];
            }
            
            return response()->json($response);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener datos comparativos: ' . $e->getMessage()
            ], 500);
        }
    }
} 