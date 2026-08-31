<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\Proveedorproducto;
use App\Models\facturify;
use App\Models\Vistas;
use App\Models\Acciones;
use App\Models\usuario_pantallas;
use App\Models\usuario_acciones;
use App\Traits\MenuTrait;
use Carbon\Carbon;
use App\Traits\DatosimpleTraits;
use App\Traits\SistemasTraits;
use App\Models\perfil_acciones;
use App\Models\perfiles;
use App\Models\Proveedores;
use App\Models\UserSucursal;
use Illuminate\Support\Arr;
use SimpleXMLElement;
use Illuminate\Support\Facades\Storage;
use SoapClient;
use GuzzleHttp\Client;
use App\Services\SatVerificationService;
use App\Models\Licitacion;

class ExternoPoroveedorController extends Controller
{
    use MenuTrait;
    use DatosimpleTraits;

    /*------------------------------------ */

    public function __construct()
    {
         $this->middleware('auth');
    }

    public function licitacionesExtIndex()
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $varlistausers = $this->obtenerusuarios();
        //hay que ver la fora de guardar y concatenar el usuario id con el id proveedor
        $idusuarioid = auth()->user()->id;
        $idusuario = auth()->user()->name;
        $idproveedor = DB::select("select id from tblprovedores where id_usuario = ?",[$idusuarioid]);
        $licitaciones_prov = DB::select("select * from tbllicitacion_proveedor lp
            join tbllicitacion_enc li on lp.licitacion_id = li.id
            where lp.proveedor_id = ? ",[$idproveedor[0]->id]);


        return view('proveedorext/extlicitaciones', compact('varpantallas', 'varsubmenus', 'varlistausers', 'licitaciones_prov'));
    }


    public function EnviarFacturasIndex()
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $varlistausers = $this->obtenerusuarios();
        //hay que ver la fora de guardar y concatenar el usuario id con el id proveedor
        $idusuario = auth()->user()->name;
        $proveedores = DB::select("SELECT * FROM tblprovedores where estado = 'A';");

        return view('proveedorext/enviarfacturas', compact('varpantallas', 'varsubmenus', 'varlistausers', 'proveedores'));
    }

    public function procesarXml(Request $request)
    {
        try {
            // Validar la solicitud
            $request->validate([
                'xml' => 'required|file|mimes:xml|max:104857600',
                'licitacion_id' => 'nullable|exists:tbllicitacion_enc,id'
            ]);
            
            // Obtener el archivo XML
            $xmlFile = $request->file('xml');
            $xmlContent = file_get_contents($xmlFile->getRealPath());
            
            // Procesar el XML
            $xml = new SimpleXMLElement($xmlContent);
            
            // Registrar namespaces para CFDI 4.0 México
            $namespaces = $xml->getNamespaces(true);
            $xml->registerXPathNamespace('cfdi', $namespaces['cfdi'] ?? 'http://www.sat.gob.mx/cfd/4');
            $xml->registerXPathNamespace('tfd', $namespaces['tfd'] ?? 'http://www.sat.gob.mx/TimbreFiscalDigital');
            
            // Extraer datos relevantes
            $datos = [
                'emisor' => (string)($xml->xpath('//cfdi:Emisor/@Nombre')[0] ?? ''),
                'rfc_emisor' => (string)($xml->xpath('//cfdi:Emisor/@Rfc')[0] ?? ''),
                'rfc_receptor' => (string)($xml->xpath('//cfdi:Receptor/@Rfc')[0] ?? ''),
                'sub_total' => (string)($xml->xpath('//cfdi:Comprobante/@SubTotal')[0] ?? ''),
                'total' => (string)($xml->xpath('//cfdi:Comprobante/@Total')[0] ?? ''),
                'uuid' => (string)($xml->xpath('//tfd:TimbreFiscalDigital/@UUID')[0] ?? ''),
                'fecha' => (string)($xml->xpath('//cfdi:Comprobante/@Fecha')[0] ?? date('Y-m-d')),
            ];
            
            // Validar que el XML tenga datos mínimos necesarios
            if (empty($datos['uuid']) || empty($datos['rfc_emisor'])) {
                return back()->with('warningBD', 'El archivo XML no contiene la información fiscal requerida.');
            }
            
            // Obtener datos del proveedor y licitación
            $idusuario = auth()->user()->id;
            $proveedor = DB::table('tblprovedores')
                ->where('id_usuario', $idusuario)
                ->first();
                
            if (!$proveedor) {
                return back()->with('warningBD', 'No se encontró el proveedor asociado a este usuario.');
            }
            
            // Verificar si la factura con ese UUID ya existe
            $facturaExistente = DB::table('tblfacturas_xml')
                ->where('uuid', $datos['uuid'])
                ->first();
                
            if ($facturaExistente) {
                return back()->with('warningBD', 'Esta factura ya ha sido procesada anteriormente.');
            }
            
            // Generar nombre único para el archivo
            $extension = $xmlFile->getClientOriginalExtension();
            $nombreArchivo = 'factura_' . time() . '_' . uniqid() . '.' . $extension;
            
            // Almacenar el archivo en el servidor
            $rutaArchivo = $xmlFile->storeAs('facturas/xml', $nombreArchivo, 'public');
            
            // Registrar la factura en la base de datos
            try {
                $facturaId = DB::table('tblfacturas_xml')->insertGetId([
                    'licitacion_id' => $request->input('licitacion_id'),
                    'proveedor_id' => $proveedor->id,
                    'uuid' => $datos['uuid'],
                    'rfc_emisor' => $datos['rfc_emisor'],
                    'nombre_emisor' => $datos['emisor'],
                    'rfc_receptor' => $datos['rfc_receptor'],
                    'subtotal' => floatval($datos['sub_total']),
                    'total' => floatval($datos['total']),
                    'nombre_archivo' => $nombreArchivo,
                    'ruta_archivo' => $rutaArchivo,
                    'estado' => 'Procesada',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } catch (\Exception $e) {
                // Si la tabla no existe, crear la estructura en un arreglo temporal
                Log::error('Error al guardar factura: ' . $e->getMessage());
                
                // Almacenar temporalmente en la sesión
                $request->session()->put('factura_temporal', [
                    'datos' => $datos,
                    'ruta_archivo' => $rutaArchivo,
                    'nombre_archivo' => $nombreArchivo
                ]);
                
                return redirect()->route('ext_licitaciones')->with('success', 'Factura XML recibida correctamente. Se está procesando su información.');
            }
            
            return redirect()->route('ext_licitaciones')->with('success', 'Factura XML procesada correctamente.');
            
        } catch (\Exception $e) {
            Log::error('Error al procesar XML: ' . $e->getMessage());
            return back()->with('warningBD', 'Error al procesar el archivo XML: ' . $e->getMessage());
        }
    }

    public function Anexarcosteslicitacion()
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $varlistausers = $this->obtenerusuarios();
        //hay que ver la fora de guardar y concatenar el usuario id con el id proveedor
        $idusuarioid = auth()->user()->id;
        $idusuario = auth()->user()->name;
        $idproveedor = DB::select("ect * from tbllicitacion_proveedor lp
        join tbllicitacion_enc li on lp.licitacion_idselect id from tblprovedores where id_usuario = ?",[$idusuarioid]);
        $licitaciones_prov = DB::select("sel = li.id
        where lp.proveedor_id = ? and lp.estado = 'Activa';",[$idproveedor[0]->id]);


        return view('proveedorext/extlicitaciones', compact('varpantallas', 'varsubmenus', 'varlistausers', 'licitaciones_prov'));
    }


    public function enviarLicitacion($id)
    {
        try {
            $idusuario = auth()->user()->id;

            $proveedor = DB::table('tblprovedores')
                ->where('id_usuario', $idusuario)
                ->first();

            if (!$proveedor) {
                return back()->with("warning", "Proveedor no encontrado para este usuario.");
            }

            DB::table('tbllicitacion_proveedor')
                ->where('licitacion_id', $id)
                ->where('proveedor_id', $proveedor->id)
                ->update(['estado_provext' => 'Enviada']);

            return back()->with("success", "¡Licitación enviada correctamente!");
        } catch (\Exception $e) {
            return back()->with("warningBD", "Error al enviar licitación: " . $e->getMessage());
        }
    }

    // //show externo licitacion
    // public function showexterno($id){
    //     try{
    //         // traer la informacion de los datos a enviar a edicion
    //         $varpantallas =  $this->Traermenuenc();
    //         $varsubmenus =   $this->Traermenudet();

    //         $date = Carbon::now();
    //         $date = $date->format('Y-m-d');

    //         //$obtenerempleado = $this->obtenerlistaempleadoid($id);
    //         $licitacion = Licitacion::find($id);
    //         $detalle = $licitacion->detalles->map(function ($item) {
    //             return [
    //                 'producto_id' => $item->producto_id,
    //                 'nombre' => $item->producto->nombre, // Asegúrate de tener la relación
    //                 'cantidad' => $item->cantidad,
    //                 'umed' => $item->producto->unidad->nombre, // Asegúrate de tener la relación
    //                 'observaciones' => $item->observaciones,
    //                 'costo' => $item->costo
    //             ];
    //         })->toArray();
    //         // $permisos = $this->forpermisos('actualizar_empleados');

    //         // if($permisos=="actualizar_empleados")
    //         // {
    //             return view('proveedorext.showexterno',compact('varpantallas','varsubmenus','licitacion','detalle'));
    //         // }
    //         // else{
    //         //     return redirect()->route('verempleados')->with("Errorpermisos","No se logro");
    //         // }
    //     } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    // }



    public function showexterno($id,$proveedor_id)
    {
        // try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();

            $licitacion = Licitacion::find($id);

            // Obtener proveedor_id según el usuario autenticado
            // $idusuarioid = auth()->user()->id;
            // $proveedor = DB::table('tblprovedores')->where('id_usuario', $idusuarioid)->first();

            // if (!$proveedor) {
            //     return back()->with("warningBD", "No se encontró el proveedor asociado al usuario.");
            // }

            // $proveedor_id = $proveedor->id;
            $licitacion_id = $licitacion->id;

            // Obtener comentarios del proveedor para esta licitación
            $comentarios = DB::table('tbllicitacion_comentarios')
                ->where('licitacion_id', $licitacion_id)
                ->where('proveedor_id', $proveedor_id)
                ->value('comentarios');

            // Obtener documento adjunto si existe
            $documento_adjunto = DB::table('tbllicitacion_documentos')
                ->where('licitacion_id', $licitacion_id)
                ->where('proveedor_id', $proveedor_id)
                ->value('documento');

            // Obtener productos y cantidades desde tbllicitacion_det
            $detalles_det = DB::table('tbllicitacion_det as d')
                ->where('d.licitacion_id', $licitacion_id)
                ->get()
                ->keyBy('producto_id'); // para unir después fácilmente

            // Obtener costos desde tbllicitacionproducto_proveedor
            $detalles_costo = DB::table('tbllicitacionproducto_proveedor as lpp')
                ->where('lpp.licitacion_id', $licitacion_id)
                ->where('lpp.proveedor_id', $proveedor_id)
                ->get()
                ->keyBy('producto_id');

            // Obtener info del producto y unir todo
            $producto_ids = $detalles_det->keys()->merge($detalles_costo->keys())->unique();

            $productos = DB::table('tblproductos as p')
                ->leftJoin('tblunidadesmedida as u', 'p.id_unidad_medida', '=', 'u.id')
                ->whereIn('p.id', $producto_ids)
                ->select('p.id', 'p.nombre as nombre_producto', 'u.nombre as unidad')
                ->get()
                ->keyBy('id');

            // Armar arreglo final
            $detalle = [];

            foreach ($producto_ids as $producto_id) {
                $producto = $productos[$producto_id] ?? null;
                $cantinfo = $detalles_det[$producto_id] ?? null;
                $costoinfo = $detalles_costo[$producto_id] ?? null;

                if ($producto) {
                    $cantidad = $cantinfo->cantidad ?? 1;
                    $observaciones = $cantinfo->observaciones ?? '';
                    $costo = $costoinfo->costo ?? 0;
                    $marca = $costoinfo->marca ?? '';
                    $envio = $costoinfo->envio ?? 0;
                    $margen = $costoinfo->margen ?? 0;
                    $observaciones = $costoinfo->observaciones ?? 0;

                    $detalle[] = [
                        'producto_id' => $producto_id,
                        'nombre' => $producto->nombre_producto,
                        'umed' => $producto->unidad ?? '',
                        'cantidad' => $cantidad,
                        // 'observaciones' => $observaciones,
                        'costo' => $costo,
                        'subtotal' => $cantidad * $costo,
                        'marca' => $marca ?? '',
                        'envio' => $envio,
                        'margen' => $margen,
                        'observaciones' => $observaciones,
                    ];
                }
            }

            $detalles_calculados = collect(DB::select('select tbllicitacionproducto_proveedor.producto_id as productid, tbllicitacionproducto_proveedor.*,tblproductos.nombre, 
            tblunidadesmedida.nombre as umed,
            (select cantidad from tbllicitacion_det where producto_id = productid and licitacion_id = ?) as cantidad
            FROM tbllicitacionproducto_proveedor
            inner join tblproductos on tblproductos.id = tbllicitacionproducto_proveedor.producto_id
            inner join tblunidadesmedida on tblunidadesmedida.id = tblproductos.id_unidad_medida 
            where tbllicitacionproducto_proveedor.proveedor_id = ? and tbllicitacionproducto_proveedor.licitacion_id = ?;',[$licitacion_id,$proveedor_id,$licitacion_id]));
               

            // Obtener flete_interno y flete_externo de la tabla para este proveedor y licitación
            $flete_interno = DB::table('tbllicitacionproducto_proveedor')
                ->where('licitacion_id', $licitacion_id)
                ->where('proveedor_id', $proveedor_id)
                ->value('flete_interno');
            $flete_externo = DB::table('tbllicitacionproducto_proveedor')
                ->where('licitacion_id', $licitacion_id)
                ->where('proveedor_id', $proveedor_id)
                ->value('flete_externo');
            return view('proveedorext.showexterno', compact(
                'proveedor_id',
                'varpantallas',
                'varsubmenus',
                'licitacion',
                'detalle',
                'licitacion_id',
                'proveedor_id',
                'comentarios',
                'documento_adjunto',
                'detalles_calculados',
                'flete_interno',
                'flete_externo'
            ));

        // } catch (\Illuminate\Database\QueryException $ex) {
        //     return back()->with("warningBD", "No se pudo cargar la licitación.");
        // }
    }


    // public function update(Request $request,$id){
        //     try{
        //         // traer la informacion de los datos a enviar a edicion
        //         $varpantallas =  $this->Traermenuenc();
        //         $varsubmenus =   $this->Traermenudet();

        //         $date = Carbon::now();
        //         $date = $date->format('Y-m-d');

        //         //$obtenerempleado = $this->obtenerlistaempleadoid($id);
        //         $Licitacion = Licitacion::find($id);
        //         $Licitacion->update($request->all());
        //         $detalle = json_decode($request->detalle_json, true);

        //         // Guardar detalles
        //         $Licitacion->detalles()->delete();
        //         foreach ($detalle as $item) {
        //             $Licitacion->detalles()->create([
        //                 'producto_id' => $item['producto_id'],
        //                 'cantidad' => $item['cantidad'],
        //                 'observaciones' => $item['observaciones'],
        //                 'costo' => $item['costo']
        //             ]);
        //         }

        //         if($Licitacion){
        //             return redirect()->route('ext_licitaciones')->with("success","¡Se guardaron los cambios correctamente!");
        //         }else{
        //             return redirect()->route('ext_licitaciones')->with("warning","¡No se guardaron los cambios correctamente!");}

        //     } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    // }


    public function update(Request $request, $id, $proveedor_id)
    {
        try {
        
            // Obtener el proveedor asociado al usuario actual
            $idusuarioid = auth()->user()->id;
            $proveedor = DB::table('tblprovedores')
                ->where('id', $proveedor_id)
                ->first();

            if (!$proveedor) {
                return back()->with("warningBD", "No se encontró el proveedor asociado al usuario.");
            }

            $licitacion = Licitacion::find($id);
            if (!$licitacion) {
                return back()->with("warningBD", "Licitación no encontrada.");
            }
            

            // Verificar si es un borrador o una actualización completa
            $isDraft = $request->get('is_draft');
            $estadoActual = DB::table('tbllicitacion_proveedor')
                ->where('licitacion_id', $id)
                ->where('proveedor_id', $proveedor->id)
                ->value('estado_provext');

            // Solo permitir actualizaciones si el estado no es "Enviada"
            // if ($estadoActual == 'Enviada') {
            //     return back()->with("warningBD", "No se pueden modificar licitaciones ya enviadas.");
            // }

            // Procesar el detalle JSON
            $detalle = json_decode($request->input('detalle_json'), true);
            
            // Validar que el detalle sea un array válido
            if (!is_array($detalle)) {
                return back()->with("warningBD", "El formato de los datos es incorrecto.");
            }
            

            // Obtener los IDs de productos actuales en el detalle
            $productosActuales = collect($detalle)->pluck('producto_id')->filter()->toArray();

            // Eliminar registros que ya no están en el detalle
            DB::table('tbllicitacionproducto_proveedor')
                ->where('licitacion_id', $id)
                ->where('proveedor_id', $proveedor->id)
                ->whereNotIn('producto_id', $productosActuales)
                ->delete();
                

            // Actualizar los costos y marcas en tbllicitacionproducto_proveedor
            foreach ($detalle as $item) {
                if (!isset($item['producto_id']) || empty($item['producto_id'])) {
                    continue; // Saltar productos sin ID
                }

                // Verificar si existe el registro
                $registro = DB::table('tbllicitacionproducto_proveedor')
                    ->where('licitacion_id', $id)
                    ->where('proveedor_id', $proveedor->id)
                    ->where('producto_id', $item['producto_id'])
                    ->first();
                    

                $costo = isset($item['costo']) ? floatval($item['costo']) : 0;
                $marca = isset($item['marca']) ? $item['marca'] : '';
                $margen = isset($item['margen']) ? $item['margen'] : '';
                $observaciones = isset($item['observaciones']) ? $item['observaciones'] : '';
                $envio = isset($item['envio']) ? floatval($item['envio']) : 0;
                // NUEVO: Obtener flete interno y externo del request
                $flete_interno = $request->input('flete_interno', 0);
                $flete_externo = $request->input('flete_externo', 0);
                
                // NUEVO: Obtener campos calculados
                $margencost = isset($item['margencost']) ? floatval($item['margencost']) : 0;
                $envio_producto = isset($item['enviototal']) ? floatval($item['enviototal']) : 0; // enviototal del Livewire
                $subtotal = isset($item['subtotal']) ? floatval($item['subtotal']) : 0;
                $total = isset($item['total']) ? floatval($item['total']) : 0;

                if ($registro) {
                    // Actualizar el registro existente
                    DB::table('tbllicitacionproducto_proveedor')
                        ->where('licitacion_id', $id)
                        ->where('proveedor_id', $proveedor->id)
                        ->where('producto_id', $item['producto_id'])
                        ->update([
                            'costo' => $costo,
                            'envio' => $envio,
                            'margen' => $margen,
                            'marca' => $marca,
                            'observaciones' => $observaciones,
                            'flete_interno' => $flete_interno, // Guardar flete interno
                            'flete_externo' => $flete_externo, // Guardar flete externo
                            'margencost' => $margencost, // Costo + margen calculado
                            'envio_producto' => $envio_producto, // Envío por producto
                            'subtotal' => $subtotal, // Subtotal calculado
                            'total' => $total, // Total calculado
                            'updated_by' => auth()->user()->name
                        ]);
                } else {
                    // Crear un nuevo registro
                    DB::table('tbllicitacionproducto_proveedor')->insert([
                        'licitacion_id' => $id,
                        'proveedor_id' => $proveedor->id,
                        'producto_id' => $item['producto_id'],
                        'costo' => $costo,
                        'envio' => $envio,
                        'margen' => $margen,
                        'marca' => $marca,
                        'observaciones' => $observaciones,
                        'flete_interno' => $flete_interno, // Guardar flete interno
                        'flete_externo' => $flete_externo, // Guardar flete externo
                        'margencost' => $margencost, // Costo + margen calculado
                        'envio_producto' => $envio_producto, // Envío por producto
                        'subtotal' => $subtotal, // Subtotal calculado
                        'total' => $total, // Total calculado
                        'created_by' => auth()->user()->name
                    ]);
                }
            }

            // Guardar el margen global
            $margenGlobal = $request->input('margenGlobal', 0);
            
            // Guardar el margen global en tbllicitacion_proveedor
            // Nota: Si la columna margen_global no existe, necesitarás agregarla a la tabla
            try {
                DB::table('tbllicitacion_proveedor')
                    ->where('licitacion_id', $id)
                    ->where('proveedor_id', $proveedor->id)
                    ->update(['margen_global' => $margenGlobal]);
            } catch (\Exception $e) {
                // Si la columna no existe, intentar guardar en tbllicitacionproducto_proveedor
                try {
                    // Guardar en el primer registro como referencia
                    $primerRegistro = DB::table('tbllicitacionproducto_proveedor')
                        ->where('licitacion_id', $id)
                        ->where('proveedor_id', $proveedor->id)
                        ->first();
                    
                    if ($primerRegistro) {
                        DB::table('tbllicitacionproducto_proveedor')
                            ->where('licitacion_id', $id)
                            ->where('proveedor_id', $proveedor->id)
                            ->where('producto_id', $primerRegistro->producto_id)
                            ->update(['margen_global' => $margenGlobal]);
                    }
                } catch (\Exception $e2) {
                    // Si tampoco funciona, simplemente continuar
                    // El usuario necesitará agregar la columna margen_global a una de las tablas
                }
            }

            // Procesar comentarios adicionales
            $comentarios = $request->input('comentarios');
            
            // Actualizar o crear el registro de comentarios
            DB::table('tbllicitacion_comentarios')
                ->updateOrInsert(
                    [
                        'licitacion_id' => $id,
                        'proveedor_id' => $proveedor->id
                    ],
                    [
                        'comentarios' => $comentarios,
                        'updated_at' => now()
                    ]
                );
            
            // Verificar si la licitación está adjudicada a este proveedor antes de procesar documentos
            $isAdjudicada = ($licitacion->estado == 'adjudicada');
            $isAdjudicadaToThisProvider = $isAdjudicada && ($licitacion->proveedor_adjudicado == $proveedor->id);
            
            // Procesar documentación adicional solo si la licitación está adjudicada a este proveedor
            if ($isAdjudicadaToThisProvider && $request->hasFile('documentacion')) {
                $file = $request->file('documentacion');
                
                // Validar el archivo
                $validacion = $request->validate([
                    'documentacion' => 'file|mimes:pdf,xml|max:5048', // 5MB máximo, permitir XML también
                ]);

                // Generar nombre único para el archivo
                $nombreArchivo = $id . '_' . $proveedor->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                
                // Almacenar el archivo
                $file->storeAs('public/licitaciones/docs', $nombreArchivo);
                
                // Actualizar o crear el registro de documentos
                DB::table('tbllicitacion_documentos')
                    ->updateOrInsert(
                        [
                            'licitacion_id' => $id,
                            'proveedor_id' => $proveedor->id
                        ],
                        [
                            'documento' => $nombreArchivo,
                            'updated_at' => now()
                        ]
                    );
            }

            // Actualizar estado en la relación licitacion_proveedor
            if ($isDraft == 1) {
                // Si se termino cerrar
                DB::table('tbllicitacion_proveedor')
                    ->where('licitacion_id', $id)
                    ->where('proveedor_id', $proveedor->id)
                    ->update([
                        'estado_provext' => 'Enviada',
                        'updated_at' => now()
                    ]);
                    
                return redirect()->route('licitaciones.editar',[$id])->with("success", "Borrador guardado correctamente.");
            } else {
                // marcar como En Progreso
                DB::table('tbllicitacion_proveedor')
                    ->where('licitacion_id', $id)
                    ->where('proveedor_id', $proveedor->id)
                    ->update([
                        'estado_provext' => 'Enviada', //En Progreso
                        'updated_at' => now()
                    ]);
                
                // return back()->with("success", "Licitación actualizada correctamente.");
                return redirect()->route('licitaciones.editar',[$id])->with("success", "Borrador guardado correctamente.");
            }
        } catch (\Exception $e) {
            Log::error('Error en actualización de licitación: ' . $e->getMessage());
            return back()->with("warningBD", "Error al actualizar la licitación: " . $e->getMessage());
        }
    }


}
