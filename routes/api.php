<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\API\ComparativaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/licitaciones/{id}/detalles', function ($id) {
    // Obtener la información de la licitación
    $licitacion = DB::table('tbllicitacion_enc')
        ->where('id', $id)
        ->first();
    
    if (!$licitacion) {
        return response()->json(['error' => 'Licitación no encontrada'], 404);
    }
    
    // Obtener información del proveedor
    $proveedor = DB::table('tblprovedores')
        ->where('id', $licitacion->proveedor_adjudicado_id)
        ->first();
    
    // Obtener cantidad de productos
    $productos = DB::table('tbllicitacion_det')
        ->where('licitacion_id', $id)
        ->get();
    
    $productosCount = $productos->count();
    
    // Calcular total aproximado
    $totalAproximado = DB::table('tbllicitacion_det as ld')
        ->join('tbllicitacionproducto_proveedor as lpp', function($join) use ($licitacion) {
            $join->on('lpp.licitacion_id', '=', 'ld.licitacion_id')
                ->on('lpp.producto_id', '=', 'ld.producto_id')
                ->where('lpp.proveedor_id', '=', $licitacion->proveedor_adjudicado_id);
        })
        ->where('ld.licitacion_id', $id)
        ->sum(DB::raw('lpp.costo * ld.cantidad'));
    
    return response()->json([
        'id' => $licitacion->id,
        'nombre' => $licitacion->nombre,
        'fecha_creacion' => $licitacion->fecha_creacion,
        'fecha_limite' => $licitacion->fecha_limite,
        'estado' => $licitacion->estado,
        'proveedor' => $proveedor ? [
            'id' => $proveedor->id,
            'nombre' => $proveedor->nombre,
            'email' => $proveedor->email ?? null
        ] : null,
        'productos_count' => $productosCount,
        'total_aproximado' => number_format($totalAproximado, 2, '.', ''),
    ]);
});

Route::get('/comparativa-licitacion-factura/{licitacionId}', [ComparativaController::class, 'comparar']);
