<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Vistas;
use App\Models\Acciones;
use App\Models\usuario_pantallas;
use App\Models\usuario_acciones;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Traits\MenuTrait;
use Carbon\Carbon;
use App\Traits\DatosimpleTraits;
use App\Traits\SistemasTraits;
use App\Models\perfil_acciones;
use App\Models\perfiles;
use App\Models\Nominas_pagosenc;
use App\Models\Nominas_pagosdet;
use DateTime;
use SimpleXMLElement;
use Illuminate\Support\Facades\Storage;
use SoapClient;
use App\Models\ReciboNomina; 
use App\Models\NominaNotimbrados;
use App\Models\facturacionproductos;
use Illuminate\Support\Facades\Response;
use function PHPUnit\Framework\isNull;
use App\Traits\NominaTraits;
use Exception;
use App\Models\Ingreso;
use App\Models\Egreso;
use App\Models\DeudaCobrar;
use App\Models\DeudaPagar;
use App\Models\NotaCredito;
use App\Models\PagoDeudaPagar;
use App\Services\OrdenCompraFinanzasService;
use App\Models\cuentas_bitacoras;
use Illuminate\Support\Facades\Log;

class FinanzasController extends Controller
{
    use MenuTrait;
    use DatosimpleTraits;
    
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Mostrar la vista de control de cuentas financieras
     */
    public function finanzas()
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        
        // Obtener el saldo actual de la cuenta 3
        $saldoActual = DB::table('tblcuentas')
            ->where('id', 3)
            ->value('saldo_actual') ?? 0;
        
        // Obtener los servicios para el combobox
        $servicios = DB::table('tblservicios_det')
            ->select('id_servicio_enc', 'nombre', 'monto_proyectado')
            ->get();

        // Obtener los servicios para el del encabezado
        $serviciosenc = DB::table('tblservicios_enc')
            ->select('id', 'folio','nombre')
            ->where('estado', '!=', 'FINALIZADAS')
            ->get();
        
        // Obtener las cuentas bancarias para el combobox
        $cuentas = DB::table('tblcuentas')
            ->select('id', 'nombre', 'saldo_actual')
            ->get();
        
        // Obtener el total de egresos
        $totalEgresos = DB::table('egresos')
            ->sum('monto') ?? 0;
        
        // Obtener el total de deudas por pagar
        $totalDeudasPagar = DeudaPagar::query()
            ->whereNotIn('estado', ['pagada', 'anulada'])
            ->selectRaw('COALESCE(SUM(monto - COALESCE(monto_pagado, 0)), 0) as saldo')
            ->value('saldo') ?? 0;
        
        // Obtener el total de deudas por cobrar (solo las que no estén cobradas)
        $totalDeudasCobrar = DB::table('deudas_cobrar')
            ->whereNotIn('estado', ['cobrada', 'COBRADA'])
            ->sum('monto') ?? 0;
        
        // Obtener el total de facturas timbradas
        $totalfacturas = DB::table('tblfacturacionproductos')
        ->whereNotIn('cancelada', ['C', 'c'])
        ->sum('total') ?? 0;
        return view('Finanzas.cuentas', compact('varpantallas', 'varsubmenus', 'saldoActual', 'servicios', 'totalEgresos', 'totalDeudasPagar', 'totalDeudasCobrar', 'cuentas', 'serviciosenc', 'totalfacturas'));
    }

    /**
     * Mostrar la vista de gestión de facturas
     */
    public function facturas(Request $request)
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        
        // Construir la consulta base con JOIN para obtener el nombre del servicio
        $query = DB::table('tblfacturacionproductos')
            ->leftJoin('tblservicios_enc', 'tblfacturacionproductos.id_serv_enc', '=', 'tblservicios_enc.id')
            ->select(
                'tblfacturacionproductos.id', 
                'tblfacturacionproductos.folio', 
                'tblfacturacionproductos.date', 
                'tblfacturacionproductos.reciver_nombre', 
                'tblfacturacionproductos.reciver_rfc', 
                'tblfacturacionproductos.id_serv_enc', 
                'tblfacturacionproductos.tipo_serv', 
                'tblfacturacionproductos.subtotal', 
                'tblfacturacionproductos.total', 
                'tblfacturacionproductos.estado', 
                'tblfacturacionproductos.cancelada',
                'tblfacturacionproductos.Uuid',
                'tblfacturacionproductos.metodo_pago',
                'tblfacturacionproductos.facturama_id',
                'tblservicios_enc.nombre as nombre_servicio'
            );
        
        // Aplicar filtros si se proporcionan
        if ($request->filled('cliente')) {
            $query->where('reciver_nombre', 'like', '%' . $request->cliente . '%');
        }
        
        if ($request->filled('estado')) {
            $query->where('tblfacturacionproductos.estado', $request->estado);
        }
        
        if ($request->filled('fecha_desde')) {
            $query->where('date', '>=', $request->fecha_desde);
        }
        
        if ($request->filled('fecha_hasta')) {
            $query->where('date', '<=', $request->fecha_hasta);
        }
        
        if ($request->filled('nombre_servicio')) {
            $query->where('tblservicios_enc.nombre', 'like', '%' . $request->nombre_servicio . '%');
        }
        
        // Obtener datos de facturas filtrados
        $facturas = $query->orderBy('date', 'desc')->get();
        
        // Obtener totales por estado (sin filtros para mostrar totales generales)
        $totalPendientes = DB::table('tblfacturacionproductos')
            ->where('estado', 'Pendiente')
            ->where('cancelada', 'A')
            ->sum('total') ?? 0;
            
        $totalPagadas = DB::table('tblfacturacionproductos')
            ->where('estado', 'Pagado')
            ->where('cancelada', 'A')
            ->sum('total') ?? 0;
            
        $totalCobrando = DB::table('tblfacturacionproductos')
            ->where('estado', 'Cobrando')
            ->where('cancelada', 'A')
            ->sum('total') ?? 0;
            
        $totalFacturas = DB::table('tblfacturacionproductos')
            ->where('cancelada', 'A')
            ->sum('total') ?? 0;
        
                 // Obtener lista de clientes únicos para el filtro
         $clientes = DB::table('tblfacturacionproductos')
             ->select('reciver_nombre')
             ->distinct()
             ->whereNotNull('reciver_nombre')
             ->where('reciver_nombre', '!=', '')
             ->orderBy('reciver_nombre')
             ->pluck('reciver_nombre');
         
                   // Obtener servicios activos para el modal de nueva factura
          $servicios = DB::table('tblservicios_enc')
              ->select('id', 'folio', 'nombre')
              ->where('estado', '!=', 'FINALIZADAS')
              ->orderBy('folio')
              ->get();
        
                 return view('Finanzas.facturas', compact(
             'varpantallas', 
             'varsubmenus', 
             'facturas', 
             'totalPendientes', 
             'totalPagadas', 
             'totalCobrando', 
             'totalFacturas',
             'clientes',
             'servicios'
         ));
    }

    /**
     * Guardar un nuevo ingreso
     */
    public function guardarIngreso(Request $request)
    {
        try {
            $request->validate([
                'concepto' => 'required|string|max:255',
                'monto' => 'required|numeric|min:0',
                'fecha' => 'required|date',
                'categoria' => 'nullable|string|max:100',
                'metodo_pago' => 'required|string|max:50',
                'descripcion' => 'nullable|string|max:500',
                'servicio_id' => 'nullable|integer',
                'cuenta_id' => 'nullable|integer',
                'archivo' => 'nullable|file|mimes:pdf,xml|max:5120' // 5MB máximo
            ]);
            $id_servicio = $request->servicio_id;
           
            // Crear nueva instancia del modelo Ingreso
            $ingreso = new Ingreso();
            $ingreso->concepto = $request->concepto;
            $ingreso->monto = $request->monto;
            $ingreso->fecha = date('Y-m-d', strtotime($request->fecha)); // Formatear como DATE
            $ingreso->categoria = $request->categoria;
            $ingreso->metodo_pago = $request->metodo_pago;
            $ingreso->descripcion = $request->descripcion;
            $ingreso->id_venta = $request->servicio_id; // Guardar el id_servicio_enc en id_venta
            $ingreso->cuenta_id = $request->cuenta_id; // Guardar el id_cuenta en id_cuenta
            $ingreso->created_by = auth()->id(); // ID del usuario autenticado
           
            // Manejar la subida del archivo si se proporciona
            if ($request->hasFile('archivo')) {
                $archivo = $request->file('archivo');
                $nombreArchivo = time() . '_' . $archivo->getClientOriginalName();
                
                // Guardar el archivo en storage/app/public/finanzas/ingresos
                $rutaArchivo = $archivo->storeAs('finanzas/ingresos', $nombreArchivo, 'public');
                
                // Guardar la ruta y nombre del archivo en la base de datos
                $ingreso->archivo_ruta = $rutaArchivo;
                $ingreso->archivo_nombre = $archivo->getClientOriginalName();
            }

            // Guardar el ingreso en la base de datos
            $ingreso->save();

            // Actualizar saldo de la cuenta y registrar en bitácora
            $this->actualizarSaldoCuenta($ingreso, 'ingreso', $id_servicio);

            // Verificar si es una petición AJAX
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ingreso registrado correctamente' . ($request->hasFile('archivo') ? ' con archivo adjunto' : ''),
                    'data' => $ingreso
                ]);
            } else {
                // Si es una petición POST normal, redirigir con mensaje de éxito
                return redirect()->back()->with('success', 'Ingreso registrado correctamente' . ($request->hasFile('archivo') ? ' con archivo adjunto' : ''));
            }

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al registrar el ingreso: ' . $e->getMessage()
                ], 500);
            } else {
                return redirect()->back()->with('error', 'Error al registrar el ingreso: ' . $e->getMessage())->withInput();
            }
        }
    }

    /**
     * Guardar un nuevo egreso
     */
    public function guardarEgreso(Request $request)
    {
        try {
            $request->validate([
                'concepto' => 'required|string|max:255',
                'monto' => 'required|numeric|min:0',
                'fecha' => 'required|date',
                'categoria' => 'nullable|string|max:100',
                'metodo_pago' => 'required|string|max:50',
                'descripcion' => 'nullable|string|max:500',
                'cuenta_id' => 'nullable|integer',
                'archivo' => 'nullable|file|mimes:pdf,xml|max:5120' // 5MB máximo
            ]);
            //dd($request->cuenta_id);
            // Crear nueva instancia del modelo Egreso
            $egreso = new Egreso();
            $egreso->concepto = $request->concepto;
            $egreso->monto = $request->monto;
            $egreso->fecha = date('Y-m-d', strtotime($request->fecha)); // Formatear como DATE
            $egreso->categoria = $request->categoria;
            $egreso->metodo_pago = $request->metodo_pago;
            $egreso->descripcion = $request->descripcion;
            $egreso->cuenta_id = $request->cuenta_id; // Guardar el id_cuenta
            $egreso->created_by = auth()->id(); // ID del usuario autenticado
            
            // Manejar la subida del archivo si se proporciona
            if ($request->hasFile('archivo')) {
                $archivo = $request->file('archivo');
                $nombreArchivo = time() . '_' . $archivo->getClientOriginalName();
                
                // Guardar el archivo en storage/app/public/finanzas/egresos
                $rutaArchivo = $archivo->storeAs('finanzas/egresos', $nombreArchivo, 'public');
                
                // Guardar la ruta y nombre del archivo en la base de datos
                $egreso->archivo_ruta = $rutaArchivo;
                $egreso->archivo_nombre = $archivo->getClientOriginalName();
            }

            // Guardar el egreso en la base de datos
            $egreso->save();

            // Actualizar saldo de la cuenta y registrar en bitácora
            $this->actualizarSaldoCuenta($egreso, 'egreso', $request->cuenta_id);

            // Verificar si es una petición AJAX
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Egreso registrado correctamente' . ($request->hasFile('archivo') ? ' con archivo adjunto' : ''),
                    'data' => $egreso
                ]);
            } else {
                // Si es una petición POST normal, redirigir con mensaje de éxito
                return redirect()->back()->with('success', 'Egreso registrado correctamente' . ($request->hasFile('archivo') ? ' con archivo adjunto' : ''));
            }

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al registrar el egreso: ' . $e->getMessage()
                ], 500);
            } else {
                return redirect()->back()->with('error', 'Error al registrar el egreso: ' . $e->getMessage())->withInput();
            }
        }
    }

    /**
     * Guardar una nueva deuda por cobrar
     */
    public function guardarDeudaCobrar(Request $request)
    {
        try {
            $request->validate([
                'deudor' => 'required|string|max:255',
                'monto' => 'required|numeric|min:0',
                'fecha_vencimiento' => 'required|date',
                'estado' => 'required|in:pendiente,vencida,cobrada',
                'descripcion' => 'nullable|string|max:500',
                'servicio_id' => 'nullable|integer',
                'archivo' => 'nullable|file|mimes:pdf,xml|max:5120' // 5MB máximo
            ]);

            // Crear nueva instancia del modelo DeudaCobrar
            $deudaCobrar = new DeudaCobrar();
            $deudaCobrar->deudor = $request->deudor;
            $deudaCobrar->monto = $request->monto;
            $deudaCobrar->fecha_vencimiento = date('Y-m-d', strtotime($request->fecha_vencimiento)); // Formatear como DATE
            $deudaCobrar->estado = $request->estado;
            $deudaCobrar->descripcion = $request->descripcion;
            $deudaCobrar->id_venta = $request->servicio_id; // Guardar el id_servicio_enc en id_venta
            $deudaCobrar->created_by = auth()->id(); // ID del usuario autenticado
            
            // Manejar la subida del archivo si se proporciona
            if ($request->hasFile('archivo')) {
                $archivo = $request->file('archivo');
                $nombreArchivo = time() . '_' . $archivo->getClientOriginalName();
                
                // Guardar el archivo en storage/app/public/finanzas/deudas_cobrar
                $rutaArchivo = $archivo->storeAs('finanzas/deudas_cobrar', $nombreArchivo, 'public');
                
                // Guardar la ruta y nombre del archivo en la base de datos
                $deudaCobrar->archivo_ruta = $rutaArchivo;
                $deudaCobrar->archivo_nombre = $archivo->getClientOriginalName();
            }

            // Guardar la deuda por cobrar en la base de datos
            $deudaCobrar->save();

            return response()->json([
                'success' => true,
                'message' => 'Deuda por cobrar registrada correctamente' . ($request->hasFile('archivo') ? ' con archivo adjunto' : ''),
                'data' => $deudaCobrar
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la deuda por cobrar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar una nueva deuda por pagar
     */
    public function guardarDeudaPagar(Request $request)
    {
        try {
            $request->validate([
                'acreedor' => 'required|string|max:255',
                'monto' => 'required|numeric|min:0',
                'fecha_vencimiento' => 'required|date',
                'estado' => 'required|in:pendiente,vencida,pagada',
                'descripcion' => 'nullable|string|max:500',
                'archivo' => 'nullable|file|mimes:pdf,xml|max:5120' // 5MB máximo
            ]);

            // Crear nueva instancia del modelo DeudaPagar
            $deudaPagar = new DeudaPagar();
            $deudaPagar->acreedor = $request->acreedor;
            $deudaPagar->monto = $request->monto;
            $deudaPagar->fecha_vencimiento = date('Y-m-d', strtotime($request->fecha_vencimiento)); // Formatear como DATE
            $deudaPagar->estado = $request->estado;
            $deudaPagar->descripcion = $request->descripcion;
            $deudaPagar->created_by = auth()->id(); // ID del usuario autenticado
            
            // Manejar la subida del archivo si se proporciona
            if ($request->hasFile('archivo')) {
                $archivo = $request->file('archivo');
                $nombreArchivo = time() . '_' . $archivo->getClientOriginalName();
                
                // Guardar el archivo en storage/app/public/finanzas/deudas_pagar
                $rutaArchivo = $archivo->storeAs('finanzas/deudas_pagar', $nombreArchivo, 'public');
                
                // Guardar la ruta y nombre del archivo en la base de datos
                $deudaPagar->archivo_ruta = $rutaArchivo;
                $deudaPagar->archivo_nombre = $archivo->getClientOriginalName();
            }

            // Guardar la deuda por pagar en la base de datos
            $deudaPagar->save();

            // Validar que el insert se realizó correctamente
            if (!$deudaPagar->id) {
                throw new \Exception('No se pudo insertar la deuda por pagar en la base de datos');
            }

            return response()->json([
                'success' => true,
                'message' => 'Deuda por pagar registrada correctamente' . ($request->hasFile('archivo') ? ' con archivo adjunto' : ''),
                'data' => $deudaPagar
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la deuda por pagar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener resumen financiero
     */
    public function obtenerResumen()
    {
        try {
            // Obtener el saldo actual de la cuenta 3
            $saldoActual = DB::table('tblcuentas')
                ->where('id', 3)
                ->value('saldo_actual') ?? 0;

            // Obtener el total de egresos
            $totalEgresos = DB::table('egresos')
                ->sum('monto') ?? 0;

            // Obtener el total de deudas por pagar
            $totalDeudasPagar = DeudaPagar::query()
                ->whereNotIn('estado', ['pagada', 'anulada'])
                ->selectRaw('COALESCE(SUM(monto - COALESCE(monto_pagado, 0)), 0) as saldo')
                ->value('saldo') ?? 0;

            // Obtener el total de deudas por cobrar (solo las que no estén cobradas)
            $totalDeudasCobrar = DB::table('deudas_cobrar')
                ->whereNotIn('estado', ['cobrada', 'COBRADA'])
                ->sum('monto') ?? 0;

            // Calcular el balance neto
            $balanceNeto = $saldoActual - $totalEgresos;

            $resumen = [
                'ingresos' => $saldoActual, // Usar el saldo actual como total de ingresos
                'egresos' => $totalEgresos, // Usar el total real de egresos
                'deudas_cobrar' => $totalDeudasCobrar, // Usar el total real de deudas por cobrar
                'deudas_pagar' => $totalDeudasPagar, // Usar el total real de deudas por pagar
                'balance_neto' => $balanceNeto,
                'flujo_caja' => $balanceNeto,
                'patrimonio_neto' => $balanceNeto,
                'ratio_deuda' => $totalDeudasPagar > 0 ? ($totalDeudasPagar / $balanceNeto) * 100 : 0
            ];

            return response()->json([
                'success' => true,
                'data' => $resumen
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el resumen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener lista de ingresos ligados a facturas CFDI
     */
    public function obtenerIngresos(Request $request)
    {
        try {
            $rows = collect();

            $queryIngresos = Ingreso::with('creador')->whereNotNull('id_factura');

            if ($request->filled('fecha_desde')) {
                $queryIngresos->where('fecha', '>=', $request->fecha_desde);
            }

            if ($request->filled('fecha_hasta')) {
                $queryIngresos->where('fecha', '<=', $request->fecha_hasta);
            }

            if ($request->filled('categoria')) {
                $queryIngresos->where('categoria', $request->categoria);
            }

            $facturasPorId = facturacionproductos::whereIn(
                'id',
                $queryIngresos->pluck('id_factura')->filter()->unique()
            )->get()->keyBy('id');

            foreach ($queryIngresos->orderByDesc('fecha')->get() as $ingreso) {
                $factura = $facturasPorId->get($ingreso->id_factura);
                $rows->push([
                    'id' => $ingreso->id,
                    'fecha' => $ingreso->fecha?->format('Y-m-d') ?? $ingreso->fecha,
                    'concepto' => $ingreso->concepto,
                    'monto' => number_format((float) $ingreso->monto, 2),
                    'categoria' => $ingreso->categoria,
                    'metodo_pago' => $ingreso->metodo_pago,
                    'servicio' => $ingreso->id_venta ? 'Servicio #' . $ingreso->id_venta : 'N/A',
                    'descripcion' => $ingreso->descripcion,
                    'factura' => $this->enlaceFacturaHtml($factura),
                    'factura_folio' => $factura->folio ?? null,
                    'archivo' => $ingreso->tieneArchivo()
                        ? '<a href="' . $ingreso->url_archivo . '" target="_blank" class="btn btn-sm btn-info"><i class="fas fa-download"></i></a>'
                        : 'Sin archivo',
                    'registrado_por' => $ingreso->creador ? $ingreso->creador->name : 'N/A',
                    'acciones' => $this->accionesIngresoFacturaHtml($ingreso, $factura),
                    'orden_fecha' => $ingreso->fecha,
                ]);
            }

            $incluirVirtuales = !$request->filled('categoria')
                || $request->categoria === 'Facturación CFDI';

            if ($incluirVirtuales) {
                $idsConIngreso = Ingreso::whereNotNull('id_factura')->pluck('id_factura');

                $queryFacturas = facturacionproductos::query()
                    ->whereNotNull('Uuid')
                    ->where('Uuid', '!=', '')
                    ->where('cancelada', 'A')
                    ->where(function ($q) {
                        $q->where('metodo_pago', 'PUE')
                            ->orWhere(function ($q2) {
                                $q2->where('estado', 'Pagado')
                                    ->where(function ($q3) {
                                        $q3->whereNull('metodo_pago')
                                            ->orWhere('metodo_pago', '!=', 'PPD');
                                    });
                            });
                    })
                    ->whereNotIn('id', $idsConIngreso);

                if ($request->filled('fecha_desde')) {
                    $queryFacturas->where('date', '>=', $request->fecha_desde);
                }

                if ($request->filled('fecha_hasta')) {
                    $queryFacturas->where('date', '<=', $request->fecha_hasta);
                }

                foreach ($queryFacturas->orderByDesc('date')->get() as $factura) {
                    $rows->push([
                        'id' => 'F-' . $factura->id,
                        'fecha' => $factura->date,
                        'concepto' => 'Factura timbrada PUE folio ' . ($factura->folio ?? $factura->id),
                        'monto' => number_format((float) $factura->total, 2),
                        'categoria' => 'Facturación CFDI',
                        'metodo_pago' => $this->labelFormaPagoFactura($factura->forma_pago),
                        'servicio' => $factura->id_serv_enc ? 'Servicio #' . $factura->id_serv_enc : 'N/A',
                        'descripcion' => 'Cliente: ' . ($factura->reciver_nombre ?? '-')
                            . ' · RFC: ' . ($factura->reciver_rfc ?? '-'),
                        'factura' => $this->enlaceFacturaHtml($factura),
                        'factura_folio' => $factura->folio ?? null,
                        'archivo' => 'Sin archivo',
                        'registrado_por' => 'CFDI',
                        'acciones' => $this->accionesFacturaHtml($factura),
                        'orden_fecha' => $factura->date,
                    ]);
                }
            }

            $ingresos = $rows->sortByDesc('orden_fecha')->values()->map(function ($row) {
                unset($row['orden_fecha']);
                return $row;
            });

            return response()->json([
                'success' => true,
                'data' => $ingresos,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ingresos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener egresos: notas de crédito timbradas ligadas a facturas
     */
    public function obtenerEgresos(Request $request)
    {
        try {
            $rows = collect();

            $queryNotas = NotaCredito::query()
                ->whereNotNull('id_factura_original')
                ->where('estado', 'Timbrado');

            if ($request->filled('fecha_desde')) {
                $queryNotas->whereDate('created_at', '>=', $request->fecha_desde);
            }

            if ($request->filled('fecha_hasta')) {
                $queryNotas->whereDate('created_at', '<=', $request->fecha_hasta);
            }

            $facturasPorId = facturacionproductos::whereIn(
                'id',
                $queryNotas->pluck('id_factura_original')->filter()->unique()
            )->get()->keyBy('id');

            foreach ($queryNotas->orderByDesc('created_at')->get() as $nota) {
                if ($request->filled('categoria') && $request->categoria !== 'Facturación CFDI') {
                    continue;
                }

                $factura = $facturasPorId->get($nota->id_factura_original);
                $rows->push([
                    'id' => 'NC-' . $nota->id,
                    'fecha' => $nota->created_at?->format('Y-m-d'),
                    'concepto' => 'Nota de crédito folio ' . ($nota->folio_nota ?? $nota->id),
                    'monto' => number_format((float) $nota->total, 2),
                    'categoria' => 'Facturación CFDI',
                    'metodo_pago' => 'Nota de crédito',
                    'descripcion' => ($nota->motivo_descripcion ?? $nota->motivo ?? '')
                        . ' · Factura origen folio ' . ($nota->folio_factura ?? '-'),
                    'factura' => $this->enlaceFacturaHtml($factura),
                    'archivo' => 'Sin archivo',
                    'registrado_por' => $nota->created_by ?? 'N/A',
                    'acciones' => $this->accionesFacturaHtml($factura),
                    'orden_fecha' => $nota->created_at,
                ]);
            }

            $incluirManuales = !$request->filled('categoria')
                || $request->categoria !== 'Facturación CFDI';

            if ($incluirManuales) {
                $queryEgresos = Egreso::with('creador');

                if ($request->filled('fecha_desde')) {
                    $queryEgresos->where('fecha', '>=', $request->fecha_desde);
                }

                if ($request->filled('fecha_hasta')) {
                    $queryEgresos->where('fecha', '<=', $request->fecha_hasta);
                }

                if ($request->filled('categoria')) {
                    $queryEgresos->where('categoria', $request->categoria);
                }

                foreach ($queryEgresos->orderByDesc('fecha')->get() as $egreso) {
                    $rows->push([
                        'id' => $egreso->id,
                        'fecha' => $egreso->fecha?->format('Y-m-d') ?? $egreso->fecha,
                        'concepto' => $egreso->concepto,
                        'monto' => number_format((float) $egreso->monto, 2),
                        'categoria' => $egreso->categoria,
                        'metodo_pago' => $egreso->metodo_pago,
                        'descripcion' => $egreso->descripcion,
                        'factura' => '<span class="text-muted">Sin factura</span>',
                        'archivo' => $egreso->tieneArchivo()
                            ? '<a href="' . $egreso->url_archivo . '" target="_blank" class="btn btn-sm btn-info"><i class="fas fa-download"></i></a>'
                            : 'Sin archivo',
                        'registrado_por' => $egreso->creador ? $egreso->creador->name : 'N/A',
                        'acciones' => '<button class="btn btn-sm btn-warning" onclick="editarEgreso(' . $egreso->id . ')"><i class="fas fa-edit"></i></button> '
                            . '<button class="btn btn-sm btn-danger" onclick="eliminarEgreso(' . $egreso->id . ')"><i class="fas fa-trash"></i></button>',
                        'orden_fecha' => $egreso->fecha,
                    ]);
                }
            }

            $egresos = $rows->sortByDesc('orden_fecha')->values()->map(function ($row) {
                unset($row['orden_fecha']);
                return $row;
            });

            return response()->json([
                'success' => true,
                'data' => $egresos,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener egresos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener deudas por cobrar ligadas a facturas PPD
     */
    public function obtenerDeudasCobrar(Request $request)
    {
        try {
            $rows = collect();

            $queryDeudas = DeudaCobrar::with('creador')->whereNotNull('id_factura');

            if ($request->filled('fecha_desde')) {
                $queryDeudas->where('fecha_vencimiento', '>=', $request->fecha_desde);
            }

            if ($request->filled('fecha_hasta')) {
                $queryDeudas->where('fecha_vencimiento', '<=', $request->fecha_hasta);
            }

            if ($request->filled('estado')) {
                $queryDeudas->where('estado', $request->estado);
            }

            $facturasPorId = facturacionproductos::whereIn(
                'id',
                $queryDeudas->pluck('id_factura')->filter()->unique()
            )->get()->keyBy('id');

            foreach ($queryDeudas->orderByDesc('created_at')->get() as $deuda) {
                $factura = $facturasPorId->get($deuda->id_factura);
                $rows->push([
                    'id' => $deuda->id,
                    'deudor' => $deuda->deudor,
                    'monto' => number_format((float) $deuda->monto, 2),
                    'fecha_vencimiento' => $deuda->fecha_vencimiento?->format('Y-m-d') ?? $deuda->fecha_vencimiento,
                    'estado' => '<span class="badge bg-' . ($deuda->estado == 'pendiente' ? 'warning' : ($deuda->estado == 'vencida' ? 'danger' : 'success')) . '">'
                        . ucfirst($deuda->estado) . '</span>',
                    'servicio' => $deuda->id_venta ? 'Servicio #' . $deuda->id_venta : 'N/A',
                    'descripcion' => $deuda->descripcion,
                    'factura' => $this->enlaceFacturaHtml($factura),
                    'archivo' => $deuda->tieneArchivo()
                        ? '<a href="' . $deuda->url_archivo . '" target="_blank" class="btn btn-sm btn-info"><i class="fas fa-download"></i></a>'
                        : 'Sin archivo',
                    'fecha_cobro' => $deuda->fecha_cobro ? ($deuda->fecha_cobro instanceof \Carbon\Carbon ? $deuda->fecha_cobro->format('Y-m-d') : $deuda->fecha_cobro) : 'N/A',
                    'monto_cobrado' => number_format((float) ($deuda->monto_cobrado ?? 0), 2),
                    'registrado_por' => $deuda->creador ? $deuda->creador->name : 'N/A',
                    'acciones' => '<button class="btn btn-sm btn-warning" onclick="editarDeudaCobrar(' . $deuda->id . ')"><i class="fas fa-edit"></i></button> '
                        . '<button class="btn btn-sm btn-danger" onclick="eliminarDeudaCobrar(' . $deuda->id . ')"><i class="fas fa-trash"></i></button>'
                        . ($factura ? ' ' . $this->accionesFacturaHtml($factura) : ''),
                    'orden_fecha' => $deuda->fecha_vencimiento,
                ]);
            }

            $idsConDeuda = DeudaCobrar::whereNotNull('id_factura')->pluck('id_factura');

            $queryFacturas = facturacionproductos::query()
                ->whereNotNull('Uuid')
                ->where('Uuid', '!=', '')
                ->where('cancelada', 'A')
                ->where('metodo_pago', 'PPD')
                ->whereIn('estado', ['Pendiente', 'Cobrando'])
                ->whereNotIn('id', $idsConDeuda);

            if ($request->filled('fecha_desde')) {
                $queryFacturas->where('date', '>=', $request->fecha_desde);
            }

            if ($request->filled('fecha_hasta')) {
                $queryFacturas->where('date', '<=', $request->fecha_hasta);
            }

            if ($request->filled('estado')) {
                $mapEstado = [
                    'pendiente' => 'Pendiente',
                    'vencida' => 'Pendiente',
                    'cobrada' => 'Pagado',
                ];
                $estadoFactura = $mapEstado[$request->estado] ?? null;
                if ($estadoFactura) {
                    $queryFacturas->where('estado', $estadoFactura);
                }
            }

            foreach ($queryFacturas->orderByDesc('date')->get() as $factura) {
                $fechaVenc = Carbon::parse($factura->date)->addDays(30)->format('Y-m-d');
                $estadoFactura = $factura->estado ?? 'Pendiente';
                $badge = $estadoFactura === 'Cobrando' ? 'info' : 'warning';

                $rows->push([
                    'id' => 'F-' . $factura->id,
                    'deudor' => $factura->reciver_nombre ?? 'Cliente sin nombre',
                    'monto' => number_format((float) $factura->total, 2),
                    'fecha_vencimiento' => $fechaVenc,
                    'estado' => '<span class="badge bg-' . $badge . '">' . e($estadoFactura) . '</span>',
                    'servicio' => $factura->id_serv_enc ? 'Servicio #' . $factura->id_serv_enc : 'N/A',
                    'descripcion' => 'CFDI PPD folio ' . ($factura->folio ?? $factura->id),
                    'factura' => $this->enlaceFacturaHtml($factura),
                    'archivo' => 'Sin archivo',
                    'fecha_cobro' => 'N/A',
                    'monto_cobrado' => '0.00',
                    'registrado_por' => 'CFDI',
                    'acciones' => $this->accionesFacturaHtml($factura),
                    'orden_fecha' => $fechaVenc,
                ]);
            }

            $deudasCobrar = $rows->sortByDesc('orden_fecha')->values()->map(function ($row) {
                unset($row['orden_fecha']);
                return $row;
            });

            return response()->json([
                'success' => true,
                'data' => $deudasCobrar,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener deudas por cobrar: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener lista de deudas por pagar
     */
    public function obtenerDeudasPagar(Request $request)
    {
        try {
            $query = DeudaPagar::with(['creador', 'ordenCompra']);
            
            // Aplicar filtros si se proporcionan
            if ($request->filled('fecha_desde')) {
                $query->where('fecha_vencimiento', '>=', $request->fecha_desde);
            }
            
            if ($request->filled('fecha_hasta')) {
                $query->where('fecha_vencimiento', '<=', $request->fecha_hasta);
            }
            
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }
            
            $deudasPagar = $query->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($deuda) {
                    $saldo = $deuda->saldo_pendiente;
                    $estadoKey = $deuda->estado;
                    if ($estadoKey === 'pendiente' && $deuda->fecha_vencimiento && $deuda->fecha_vencimiento->lt(now()->startOfDay())) {
                        $estadoKey = 'vencida';
                    }
                    $badge = 'warning';
                    if ($estadoKey === 'pagada') {
                        $badge = 'success';
                    } elseif ($estadoKey === 'vencida') {
                        $badge = 'danger';
                    } elseif ($estadoKey === 'parcial') {
                        $badge = 'info';
                    } elseif ($estadoKey === 'anulada') {
                        $badge = 'secondary';
                    }

                    $ocHtml = '—';
                    if ($deuda->orden_compra_id) {
                        $folio = optional($deuda->ordenCompra)->folio ?: ('#' . $deuda->orden_compra_id);
                        $url = route('ordcompras.show', $deuda->orden_compra_id);
                        $ocHtml = '<a href="' . $url . '">OC ' . e($folio) . '</a>';
                    }

                    $acciones = '<button class="btn btn-sm btn-success" onclick="abrirAbonoDeudaPagar(' . $deuda->id . ')" title="Registrar abono"><i class="fas fa-dollar-sign"></i></button> '
                        . '<button class="btn btn-sm btn-warning" onclick="editarDeudaPagar(' . $deuda->id . ')"><i class="fas fa-edit"></i></button> '
                        . '<button class="btn btn-sm btn-danger" onclick="eliminarDeudaPagar(' . $deuda->id . ')"><i class="fas fa-trash"></i></button>';

                    return [
                        'id' => $deuda->id,
                        'acreedor' => $deuda->acreedor,
                        'oc' => $ocHtml,
                        'monto' => number_format((float) $deuda->monto, 2),
                        'fecha_vencimiento' => optional($deuda->fecha_vencimiento)->format('Y-m-d'),
                        'estado' => '<span class="badge bg-' . $badge . '">' . ucfirst($estadoKey) . '</span>',
                        'descripcion' => $deuda->descripcion,
                        'archivo' => $deuda->tieneArchivo() ?
                            '<a href="' . $deuda->url_archivo . '" target="_blank" class="btn btn-sm btn-info"><i class="fas fa-download"></i></a>' :
                            'Sin archivo',
                        'fecha_pago' => $deuda->fecha_pago ? optional($deuda->fecha_pago)->format('Y-m-d') : 'N/A',
                        'monto_pagado' => number_format((float) ($deuda->monto_pagado ?? 0), 2),
                        'saldo' => number_format($saldo, 2),
                        'registrado_por' => $deuda->creador ? $deuda->creador->name : 'N/A',
                        'acciones' => $acciones,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $deudasPagar
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener deudas por pagar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener una deuda por pagar específica para editar
     */
    public function obtenerDeudaPagar($id)
    {
        try {
            $deudaPagar = DeudaPagar::find($id);
            
            if (!$deudaPagar) {
                return response()->json([
                    'success' => false,
                    'message' => 'Deuda por pagar no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $deudaPagar->id,
                    'acreedor' => $deudaPagar->acreedor,
                    'monto' => $deudaPagar->monto,
                    'monto_pagado' => $deudaPagar->monto_pagado,
                    'fecha_vencimiento' => $deudaPagar->fecha_vencimiento,
                    'fecha_pago' => $deudaPagar->fecha_pago,
                    'estado' => $deudaPagar->estado,
                    'descripcion' => $deudaPagar->descripcion,
                    'saldo' => $deudaPagar->saldo_pendiente,
                    'orden_compra_id' => $deudaPagar->orden_compra_id,
                    'tipo_moneda' => $deudaPagar->tipo_moneda,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la deuda por pagar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar una deuda por pagar
     */
    public function actualizarDeudaPagar(Request $request, $id)
    {
        try {
            $request->validate([
                'monto' => 'required|numeric|min:0',
                'fecha_vencimiento' => 'required|date',
                'estado' => 'required|in:pendiente,parcial,vencida,pagada,anulada',
                'monto_pagado' => 'nullable|numeric|min:0',
                'fecha_pago' => 'nullable|date'
            ]);

            $deudaPagar = DeudaPagar::find($id);
            
            if (!$deudaPagar) {
                return response()->json([
                    'success' => false,
                    'message' => 'Deuda por pagar no encontrada'
                ], 404);
            }

            // Actualizar monto, fecha de vencimiento y estado
            $deudaPagar->monto = $request->monto;
            $deudaPagar->fecha_vencimiento = date('Y-m-d', strtotime($request->fecha_vencimiento));
            $deudaPagar->estado = $request->estado;
            
            // Si se proporciona monto pagado, actualizarlo
            if ($request->has('monto_pagado') && $request->monto_pagado !== null) {
                $deudaPagar->monto_pagado = $request->monto_pagado;
            }
            
            // Si se proporciona fecha de pago, actualizarla
            if ($request->has('fecha_pago') && $request->fecha_pago !== null) {
                $deudaPagar->fecha_pago = date('Y-m-d', strtotime($request->fecha_pago));
            }
            
            $deudaPagar->updated_by = auth()->id();
            $deudaPagar->save();

            return response()->json([
                'success' => true,
                'message' => 'Deuda por pagar actualizada correctamente',
                'data' => $deudaPagar
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la deuda por pagar: ' . $e->getMessage()
            ], 500);
        }
    }

    public function registrarAbonoDeudaPagar(Request $request, $id)
    {
        $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'fecha' => 'required|date',
            'metodo_pago' => 'nullable|string|max:80',
            'referencia' => 'nullable|string|max:120',
            'cuenta_id' => 'nullable|integer',
            'notas' => 'nullable|string|max:500',
        ]);

        try {
            $deuda = DeudaPagar::findOrFail($id);
            $pago = (new OrdenCompraFinanzasService())->registrarAbono($deuda, $request->only([
                'monto', 'fecha', 'metodo_pago', 'referencia', 'cuenta_id', 'notas',
            ]));

            $mensaje = 'Abono de $' . number_format((float) $pago->monto, 2) . ' registrado correctamente.';
            if ($pago->egreso_id) {
                $mensaje .= ' Se generó el egreso #' . $pago->egreso_id . '.';
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $mensaje,
                    'data' => $pago,
                ]);
            }

            return back()->with('success_msg', $mensaje);
        } catch (\InvalidArgumentException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('warning_msg', $e->getMessage());
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->with('warning_msg', 'Error al registrar el abono: ' . $e->getMessage());
        }
    }

    public function obtenerAbonosDeudaPagar($id)
    {
        $deuda = DeudaPagar::findOrFail($id);
        $abonos = PagoDeudaPagar::with('creador')
            ->where('deuda_pagar_id', $deuda->id)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get()
            ->map(function ($pago) {
                return [
                    'id' => $pago->id,
                    'fecha' => optional($pago->fecha)->format('Y-m-d'),
                    'monto' => number_format((float) $pago->monto, 2),
                    'metodo_pago' => $pago->metodo_pago,
                    'referencia' => $pago->referencia,
                    'notas' => $pago->notas,
                    'egreso_id' => $pago->egreso_id,
                    'usuario' => optional($pago->creador)->name,
                ];
            });

        return response()->json([
            'success' => true,
            'saldo' => $deuda->saldo_pendiente,
            'estado' => $deuda->estado,
            'data' => $abonos,
        ]);
    }

    /**
     * Eliminar una deuda por pagar
     */
    public function eliminarDeudaPagar($id)
    {
        try {
            $deudaPagar = DeudaPagar::find($id);
            
            if (!$deudaPagar) {
                return response()->json([
                    'success' => false,
                    'message' => 'Deuda por pagar no encontrada'
                ], 404);
            }

            // Eliminar archivo asociado si existe
            if ($deudaPagar->tieneArchivo()) {
                Storage::disk('public')->delete($deudaPagar->archivo_ruta);
            }

            // Eliminar la deuda de la base de datos
            $deudaPagar->delete();

            return response()->json([
                'success' => true,
                'message' => 'Deuda por pagar eliminada correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la deuda por pagar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una deuda por cobrar
     */
    public function eliminarDeudaCobrar($id)
    {
        try {
            $deudaCobrar = DeudaCobrar::find($id);
            
            if (!$deudaCobrar) {
                return response()->json([
                    'success' => false,
                    'message' => 'Deuda por cobrar no encontrada'
                ], 404);
            }

            // Eliminar archivo asociado si existe
            if ($deudaCobrar->tieneArchivo()) {
                Storage::disk('public')->delete($deudaCobrar->archivo_ruta);
            }

            // Eliminar la deuda de la base de datos
            $deudaCobrar->delete();

            return response()->json([
                'success' => true,
                'message' => 'Deuda por cobrar eliminada correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la deuda por cobrar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un egreso
     */
    public function eliminarEgreso($id)
    {
        try {
            $egreso = Egreso::find($id);
            
            if (!$egreso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Egreso no encontrado'
                ], 404);
            }

            // Eliminar archivo asociado si existe
            if ($egreso->tieneArchivo()) {
                Storage::disk('public')->delete($egreso->archivo_ruta);
            }

            // Eliminar el egreso de la base de datos
            $egreso->delete();

            return response()->json([
                'success' => true,
                'message' => 'Egreso eliminado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el egreso: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un egreso específico para editar
     */
    public function obtenerEgreso($id)
    {
        try {
            $egreso = Egreso::find($id);
            
            if (!$egreso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Egreso no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $egreso->id,
                    'fecha' => $egreso->fecha,
                    'monto' => $egreso->monto,
                    'concepto' => $egreso->concepto,
                    'categoria' => $egreso->categoria,
                    'metodo_pago' => $egreso->metodo_pago,
                    'descripcion' => $egreso->descripcion
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el egreso: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un egreso
     */
    public function actualizarEgreso(Request $request, $id)
    {
        try {
            $request->validate([
                'fecha' => 'required|date',
                'monto' => 'required|numeric|min:0',
                'concepto' => 'required|string|max:255',
                'metodo_pago' => 'required|string|max:50'
            ]);

            $egreso = Egreso::find($id);
            
            if (!$egreso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Egreso no encontrado'
                ], 404);
            }

            // Actualizar los campos permitidos
            $egreso->fecha = date('Y-m-d', strtotime($request->fecha));
            $egreso->monto = $request->monto;
            $egreso->concepto = $request->concepto;
            $egreso->metodo_pago = $request->metodo_pago;
            $egreso->updated_by = auth()->id();
            $egreso->save();

            return response()->json([
                'success' => true,
                'message' => 'Egreso actualizado correctamente',
                'data' => $egreso
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el egreso: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener una deuda por cobrar específica para editar
     */
    public function obtenerDeudaCobrar($id)
    {
        try {
            $deudaCobrar = DeudaCobrar::find($id);
            
            if (!$deudaCobrar) {
                return response()->json([
                    'success' => false,
                    'message' => 'Deuda por cobrar no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $deudaCobrar->id,
                    'deudor' => $deudaCobrar->deudor,
                    'monto' => $deudaCobrar->monto,
                    'monto_cobrado' => $deudaCobrar->monto_cobrado,
                    'fecha_vencimiento' => $deudaCobrar->fecha_vencimiento,
                    'fecha_cobro' => $deudaCobrar->fecha_cobro,
                    'estado' => $deudaCobrar->estado,
                    'descripcion' => $deudaCobrar->descripcion,
                    'id_venta' => $deudaCobrar->id_venta
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la deuda por cobrar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar una deuda por cobrar
     */
    public function actualizarDeudaCobrar(Request $request, $id)
    {
        try {
            $request->validate([
                'monto' => 'required|numeric|min:0',
                'fecha_vencimiento' => 'required|date',
                'estado' => 'required|in:pendiente,vencida,cobrada',
                'monto_cobrado' => 'nullable|numeric|min:0',
                'fecha_cobro' => 'nullable|date'
            ]);

            $deudaCobrar = DeudaCobrar::find($id);
            
            if (!$deudaCobrar) {
                return response()->json([
                    'success' => false,
                    'message' => 'Deuda por cobrar no encontrada'
                ], 404);
            }

            // Actualizar monto, fecha de vencimiento, estado, monto cobrado y fecha de cobro
            $deudaCobrar->monto = $request->monto;
            $deudaCobrar->fecha_vencimiento = date('Y-m-d', strtotime($request->fecha_vencimiento));
            $deudaCobrar->estado = $request->estado;
            
            // Actualizar monto cobrado y fecha de cobro si se proporcionan
            if ($request->filled('monto_cobrado')) {
                $deudaCobrar->monto_cobrado = $request->monto_cobrado;
            }
            
            if ($request->filled('fecha_cobro')) {
                $deudaCobrar->fecha_cobro = date('Y-m-d', strtotime($request->fecha_cobro));
            }
            
            $deudaCobrar->updated_by = auth()->id();
            $deudaCobrar->save();

            return response()->json([
                'success' => true,
                'message' => 'Deuda por cobrar actualizada correctamente',
                'data' => $deudaCobrar
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la deuda por cobrar: ' . $e->getMessage()
            ], 500);
        }
    }

         /**
      * Actualizar estado de una factura
      */
     public function actualizarEstadoFactura(Request $request)
     {
         try {
             $request->validate([
                 'factura_id' => 'required|integer|exists:tblfacturacionproductos,id',
                 'nuevo_estado' => 'required|in:Pendiente,Pagado,Cobrando',
                 'nuevo_cancelada' => 'required|in:A,C',
                 'comentario_estado' => 'nullable|string|max:500',
                 'comentario_cancelacion' => 'nullable|string|max:500'
             ]);
             
             // Buscar la factura
             $factura = DB::table('tblfacturacionproductos')->where('id', $request->factura_id)->first();
             
             if (!$factura) {
                 return redirect()->back()->with('error', 'Factura no encontrada');
             }
             
             // Si se está cancelando la factura, intentar cancelar en Facturama primero
             if ($request->nuevo_cancelada == 'C' && $factura->cancelada == 'A') {
                 // Obtener el motivo de cancelación del formulario
                 $motivoCancelacion = $request->comentario_cancelacion;
                 $codigoMotivo = '01'; // Código por defecto
                 
                 if ($motivoCancelacion) {
                     // Extraer el código del motivo (formato: "01 - Descripción")
                     if (preg_match('/^(\d{2})/', $motivoCancelacion, $matches)) {
                         $codigoMotivo = $matches[1];
                     }
                 }
                 
                 $resultadoCancelacion = $this->cancelarFacturaFacturama($request->factura_id, $codigoMotivo);
                 
                 if (!$resultadoCancelacion['success']) {
                     return redirect()->back()->with('error', 'Error al cancelar la factura en Facturama: ' . $resultadoCancelacion['message']);
                 }
             }
             
             // Preparar los datos de actualización
             $updateData = [
                 'estado' => $request->nuevo_estado,
                 'cancelada' => $request->nuevo_cancelada,
                 'updated_at' => now()
             ];
             
             // Si se está cancelando la factura, agregar fecha de cancelación
             if ($request->nuevo_cancelada == 'C') {
                 $updateData['dia_cancelacion'] = now();
             } else {
                 $updateData['dia_cancelacion'] = null;
             }
             
             // Actualizar la factura
             DB::table('tblfacturacionproductos')
                 ->where('id', $request->factura_id)
                 ->update($updateData);
             
             // Aquí podrías agregar lógica para registrar el cambio en una tabla de bitácora
             // si es necesario para auditoría
             
             $mensaje = 'Factura actualizada correctamente';
             if ($request->nuevo_cancelada == 'C') {
                 $mensaje = 'Factura cancelada correctamente';
             } elseif ($request->nuevo_cancelada == 'A' && $factura->cancelada == 'C') {
                 $mensaje = 'Factura reactivada correctamente';
             }
             
             return redirect()->back()->with('success', $mensaje);
             
         } catch (\Exception $e) {
             return redirect()->back()->with('error', 'Error al actualizar la factura: ' . $e->getMessage());
         }
     }

     /**
      * Cancelar factura en Facturama API (método público para HTTP)
      */
     public function cancelarFacturaFacturamaHttp($id, Request $request = null)
     {
         try {
             // Obtener el motivo de cancelación del request
             if (!$request || !$request->has('motive')) {
                 return redirect()->back()->with('error', 'Se requiere especificar el motivo de cancelación');
             }
             
             $motive = $request->motive;
             $resultado = $this->cancelarFacturaFacturama($id, $motive);
             
             if ($resultado['success']) {
                 // Si la cancelación en Facturama fue exitosa, actualizar el estado en la base de datos
                 DB::table('tblfacturacionproductos')
                     ->where('id', $id)
                     ->update([
                         'cancelada' => 'C',
                         'dia_cancelacion' => now(),
                         'updated_at' => now()
                     ]);
                 
                 return redirect()->back()->with('success', 'Factura cancelada exitosamente en Facturama y en el sistema local');
             } else {
                 return redirect()->back()->with('error', 'Error al cancelar la factura: ' . $resultado['message']);
             }
             
         } catch (\Exception $e) {
             Log::error('Error al cancelar factura desde HTTP', [
                 'factura_id' => $id,
                 'error' => $e->getMessage()
             ]);
             
             return redirect()->back()->with('error', 'Error inesperado al cancelar la factura: ' . $e->getMessage());
         }
     }

     /**
      * Cancelar factura en Facturama API (método privado)
      */
     private function cancelarFacturaFacturama($facturaId, $motive)
     {
       
         try {
             // Buscar la factura en la base de datos
             $factura = DB::table('tblfacturacionproductos')->where('id', $facturaId)->first();
             
             if (!$factura) {
                 return [
                     'success' => false,
                     'message' => 'Factura no encontrada en la base de datos'
                 ];
             }
             
             // Verificar que la factura tenga UUID
             if (empty($factura->Uuid)) {
                 return [
                     'success' => false,
                     'message' => 'La factura no tiene UUID asignado, no se puede cancelar en Facturama'
                 ];
             }
             
             // Configurar credenciales de Facturama
             $usuario = env('USER_FAC'); // Tu usuario Facturama Sandbox
             $password = env('PWD'); // Tu contraseña Facturama Sandbox
             
             if (!$usuario || !$password) {
                 return [
                     'success' => false,
                     'message' => 'Credenciales de Facturama no configuradas'
                 ];
             }
             
             // Crear cliente HTTP
             $client = new \GuzzleHttp\Client([
                 'base_uri' => env('FACTURAMA_BASE_URI', 'https://apisandbox.facturama.mx') . '/',
                 'auth' => [$usuario, $password],
                 'timeout' => 30,
                 'verify' => false,
             ]);
             
             // Parámetros para la cancelación
             $cfdiId = $factura->Uuid; // Usar el UUID como cfdiId
             $type = 'issued'; // Tipo de factura emitida
             $uuidReplacement = ''; // UUID de reemplazo (vacío por defecto)
             
             // Construir la URL de cancelación
             $url = "cfdi/{$cfdiId}?type={$type}&motive={$motive}";
             
             // Realizar la petición DELETE a Facturama
             $response = $client->request('DELETE', $url, [
                 'headers' => [
                     'Accept' => 'application/json',
                     'Content-Type' => 'application/json'
                 ]
             ]);
             
             // Verificar la respuesta
             if ($response->getStatusCode() == 200) {
                 $responseData = json_decode($response->getBody()->getContents(), true);
                 
                 // Log de la cancelación exitosa
                 Log::info('Factura cancelada exitosamente en Facturama', [
                     'factura_id' => $facturaId,
                     'uuid' => $cfdiId,
                     'response' => $responseData
                 ]);
                 
                 return [
                     'success' => true,
                     'message' => 'Factura cancelada exitosamente en Facturama',
                     'data' => $responseData
                 ];
             } else {
                 return [
                     'success' => false,
                     'message' => 'Error en la respuesta de Facturama: ' . $response->getStatusCode()
                 ];
             }
             
         } catch (\GuzzleHttp\Exception\ClientException $e) {
             $response = $e->getResponse();
             $errorBody = $response ? $response->getBody()->getContents() : 'Sin respuesta del servidor';
             
             Log::error('Error de cliente al cancelar factura en Facturama', [
                 'factura_id' => $facturaId,
                 'status_code' => $response ? $response->getStatusCode() : 'N/A',
                 'error' => $e->getMessage(),
                 'response_body' => $errorBody
             ]);
             
             return [
                 'success' => false,
                 'message' => 'Error de cliente al cancelar factura: ' . $e->getMessage() . ' - ' . $errorBody
             ];
             
         } catch (\GuzzleHttp\Exception\ServerException $e) {
             Log::error('Error del servidor al cancelar factura en Facturama', [
                 'factura_id' => $facturaId,
                 'error' => $e->getMessage()
             ]);
             
             return [
                 'success' => false,
                 'message' => 'Error del servidor de Facturama: ' . $e->getMessage()
             ];
             
         } catch (\Exception $e) {
             Log::error('Error inesperado al cancelar factura en Facturama', [
                 'factura_id' => $facturaId,
                 'error' => $e->getMessage()
             ]);
             
             return [
                 'success' => false,
                 'message' => 'Error inesperado: ' . $e->getMessage()
             ];
         }
     }

     /**
      * Guardar una nueva factura
      */
     public function guardarFactura(Request $request)
    {
        try {
                         $request->validate([
                 'folio' => 'required|string|max:25',
                 'reciver_rfc' => 'required|string|max:60',
                 'reciver_nombre' => 'required|string|max:80',
                 'fecha_emision' => 'required|date',
                 'subtotal' => 'required|numeric|min:0',
                 'total' => 'required|numeric|min:0',
                 'estado' => 'required|in:Pendiente,Pagado,Cobrando',
                 'servicio_id' => 'nullable|integer',
                 'otros_conceptos' => 'nullable|string',
                 'monto_proyectado' => 'nullable|numeric|min:0',
                 'monto_real' => 'nullable|numeric|min:0',
                 'referencia_factura' => 'nullable|string'
             ]);

                         // Preparar datos para la tabla tblfacturacionproductos
             $facturaData = [
                 'facturama_id' => 'FAC-' . time(), // Generar ID único
                 'folio' => $request->folio,
                 'date' => date('Y-m-d', strtotime($request->fecha_emision)),
                 'reciver_rfc' => $request->reciver_rfc,
                 'reciver_nombre' => $request->reciver_nombre,
                 'subtotal' => $request->subtotal,
                 'total' => $request->total,
                 'estado' => $request->estado,
                 'cancelada' => 'A', // Activa por defecto
                 'id_serv_enc' => $request->servicio_id,
                 'tipo_serv' => $request->otros_conceptos,
                 'referencia_factura' => $request->referencia_factura,
                 'created_at' => now(),
                 'updated_at' => now()
             ];

            // Insertar en la base de datos usando DB::insert
            $facturaId = DB::table('tblfacturacionproductos')->insertGetId($facturaData);

            // Validar que el insert se realizó correctamente
            if (!$facturaId) {
                throw new \Exception('No se pudo insertar la factura en la base de datos');
            }

            // Verificar si es una petición AJAX
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Factura registrada correctamente',
                    'data' => array_merge($facturaData, ['id' => $facturaId])
                ]);
            } else {
                // Si es una petición POST normal, redirigir con mensaje de éxito
                return redirect()->back()->with('success', 'Factura registrada correctamente');
            }

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al registrar la factura: ' . $e->getMessage()
                ], 500);
            } else {
                return redirect()->back()->with('error', 'Error al registrar la factura: ' . $e->getMessage())->withInput();
            }
        }
    }

         /**
      * Redirigir a la facturación de un servicio
      */
      public function facturarServicio(Request $request)
      {
          try {
              $request->validate([
                  'servicio_id' => 'required|integer|exists:tblservicios_enc,id'
              ]);
              
              $servicioId = $request->servicio_id;
              
              // Obtener información del servicio para determinar el tipo
              $servicio = DB::table('tblservicios_enc')
                  ->select('id', 'folio', 'nombre', 'id_tiposervicio')
                  ->where('id', $servicioId)
                  ->first();
              
              if (!$servicio) {
                  return redirect()->back()->with('error', 'Servicio no encontrado');
              }
              
              $tipo_serv = $servicio->id_tiposervicio;
              $nombre_serv = DB::table('tbltipos_servicios')
                  ->select('nombre')
                  ->where('id', $tipo_serv)
                  ->first();
                  
              $tipoServicio = $nombre_serv->nombre;
              
              // Redirigir a la ruta de facturación
              return redirect()->route('facturacion', ['id' => $servicioId, 'servicio' => $tipoServicio]);
              
          } catch (\Exception $e) {
              return redirect()->back()->with('error', 'Error al procesar la facturación: ' . $e->getMessage());
          }
      }
 
 
     /**
      * Actualizar saldo de cuenta y registrar en bitácora
      */
     private function actualizarSaldoCuenta($transaccion, $tipo, $servicio_id)
    {
        try {
            // Determinar la cuenta a usar (por defecto cuenta_id = 3 si no se especifica)
            $cuentaId = $transaccion->cuenta_id;
            $id_servicio = $servicio_id;
            
            // Obtener el saldo actual de la cuenta
            $cuenta = DB::table('tblcuentas')->where('id', $cuentaId)->first();
            
            if (!$cuenta) {
                Log::error('Cuenta no encontrada con ID: ' . $cuentaId);
                return;
            }
            
            $saldoAnterior = $cuenta->saldo_actual;
            
            // Calcular el nuevo saldo
            if ($tipo === 'ingreso') {
                $saldoNuevo = $saldoAnterior + $transaccion->monto;
            } else {
                $saldoNuevo = $saldoAnterior - $transaccion->monto;
            }
            
            // Actualizar el saldo en la cuenta
            DB::table('tblcuentas')
                ->where('id', $cuentaId)
                ->update(['saldo_actual' => $saldoNuevo]);
                
            // Registrar en bitácora usando DB::insert
            $bitacoraData = [
                'id_cuenta' => (string) $cuentaId, // Convertir a string ya que el campo es varchar(18)
                'id_ent_salida' => $transaccion->id, // ID del ingreso/egreso relacionado
                'saldo_anterior' => $saldoAnterior,
                'saldo_nuevo' => $saldoNuevo,
                'fecha' => now(),
                'usuario' => auth()->user()->name ?? auth()->id(), // Usar nombre del usuario o ID como fallback
                'servicio_id' => $servicio_id ?? 0
            ];
            
            // Insertar en la bitácora
            $bitacoraId = DB::table('tblcuentas_bitacora')->insertGetId($bitacoraData);
            
            // Validar que el insert se realizó correctamente
            if (!$bitacoraId) {
                Log::error('No se pudo insertar el registro en tblcuentas_bitacora');
            } else {
                Log::info('Bitácora registrada correctamente con ID: ' . $bitacoraId);
            }
          
        } catch (\Exception $e) {
            // Log del error pero no fallar la transacción principal
            Log::error('Error al actualizar saldo de cuenta: ' . $e->getMessage());
        }
    }

    private function enlaceFacturaHtml(?facturacionproductos $factura): string
    {
        if (!$factura) {
            return '<span class="text-muted">Sin factura</span>';
        }

        $folio = e($factura->folio ?? $factura->id);
        $cliente = e($factura->reciver_nombre ?? '');

        return '<div><strong>Folio ' . $folio . '</strong>'
            . ($cliente ? '<br><small class="text-muted">' . $cliente . '</small>' : '')
            . '</div>';
    }

    private function accionesFacturaHtml(?facturacionproductos $factura): string
    {
        if (!$factura || empty($factura->Uuid)) {
            return '';
        }

        $ver = route('facturas.ver', $factura->id);
        $cobranza = route('finanzas.facturas') . '?cliente=' . urlencode($factura->reciver_nombre ?? '');

        return '<a href="' . $ver . '" target="_blank" class="btn btn-sm btn-outline-primary" title="Ver CFDI">'
            . '<i class="fas fa-file-invoice"></i></a> '
            . '<a href="' . $cobranza . '" class="btn btn-sm btn-outline-success" title="Cobranza">'
            . '<i class="fas fa-coins"></i></a>';
    }

    private function accionesIngresoFacturaHtml(Ingreso $ingreso, ?facturacionproductos $factura): string
    {
        $acciones = $this->accionesFacturaHtml($factura);

        if (is_numeric($ingreso->id)) {
            $acciones .= ' <button class="btn btn-sm btn-danger" onclick="eliminarIngreso(' . $ingreso->id . ')"><i class="fas fa-trash"></i></button>';
        }

        return trim($acciones);
    }

    private function labelFormaPagoFactura(?string $formaPago): string
    {
        $codigo = str_pad(trim((string) $formaPago), 2, '0', STR_PAD_LEFT);

        $map = [
            '01' => 'Efectivo',
            '02' => 'Cheque nominativo',
            '03' => 'Transferencia electrónica',
            '04' => 'Tarjeta de crédito',
            '28' => 'Tarjeta de débito',
            '99' => 'Por definir',
        ];

        return $map[$codigo] ?? 'Transferencia electrónica';
    }
}
