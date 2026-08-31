<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Traits\DatosimpleTraits;
use App\Traits\SistemasTraits;
use App\Traits\MenuTrait;
use Illuminate\Support\Arr;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\ServiciosTrait;
use App\Models\ServiciosEnc;
use App\Models\Clientes;
use App\Traits\ProductosTraits;
use App\Models\ServiciosProductos;
use App\Models\ServiciosDet;
use App\Models\CotizacionesEnc;
use App\Models\CotizacionesDet;
use Barryvdh\DomPDF\Facade\Pdf;

class ServiciosController extends Controller
{

    use MenuTrait;
    use DatosimpleTraits;
    use SistemasTraits;
    use ServiciosTrait;
    use ProductosTraits;


     public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $listaservicio_enc = $this->listaservicio_enc(); 
            $totales_estados = $this->totales_estados();   
            
            //INICIO -- REVISION DE STOCK AL ENTRAR

             $id_producto  = 0;
             $cantidad =  0;
             $cantidad_disponible = 0;
             $cantidad_faltante = 0;
             $stockPendiete = $this->stockPendiete();

            if($stockPendiete->isNotEmpty()){
                foreach($stockPendiete as $list){

                    //VALIDAR LA EXISTENCIA DEL PRODUCTO EN EL STOCK
                    $cantidad_encontrada = $this->aparta_existencias($list->id_producto, $list->cantidad_faltante);
                    $cantidad_reservada = $this->cantidad_reservadaxid($list->id_producto);

                    if($cantidad_encontrada == 0){
                        $cantidad_disponible = $list->cantidad_disponible;
                        $cantidad_faltante = $list->cantidad_faltante;
                        $estado = $list->estado;
                        
                    }elseif($list->cantidad_faltante <= $cantidad_encontrada){

                        $cantidad_disponible = $list->cantidad_disponible + $list->cantidad_faltante;
                        $cantidad_faltante = 0;
                        $estado = "Con Stock";

                        DB::select('update tblexistencias set cantidad_reservada = ? + ?  where id_producto = ?;',[$cantidad_encontrada,$cantidad_reservada,$list->id_producto]);
                        DB::select('update tblservicios_productos set estado = ?,cantidad_disponible = ?,cantidad_faltante = ?  where id = ?;',[$estado,$cantidad_disponible,$cantidad_faltante,$list->id]);

                    }else{

                        $cantidad_disponible = $list->cantidad_disponible + $cantidad_encontrada;
                        $cantidad_faltante = $list->cantidad_faltante - $cantidad_encontrada;
                        $estado = "Diferencia";

                        DB::select('update tblexistencias set cantidad_reservada = ? + ?  where id_producto = ?;',[$cantidad_encontrada,$cantidad_reservada,$list->id_producto]);
                        DB::select('update tblservicios_productos set estado = ?,cantidad_disponible = ?,cantidad_faltante = ?  where id = ?;',[$estado,$cantidad_disponible,$cantidad_faltante,$list->id]);

                    }

                    // echo 
                    // "SERVICO ".$list->id_servicio_enc."<br>"
                    // ."PRODUCTO ".$list->id_producto."<br>"
                    // ."CANTIDAD ".$list->cantidad_total."<br>"
                    // ."DISPONIBLE ".$cantidad_disponible."<br>"
                    // ."FALTANTE ".$cantidad_faltante."<br>"
                    // ."ESTADO ".$estado."<br><br><br>";
                }
            }

            //FIN -- REVISION DE STOCK AL ENTRAR

            return view('Servicios.index', compact('varpantallas', 'varsubmenus','listaservicio_enc','totales_estados'));
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }


    //METODOS CAPTURA NORMAL
    public function pcaptura(string $tipo)
    {
        try {
            
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();  
            $ClientesActivos = $this->obtenerClientesActivos();
            $Ciudades = $this->obtenerciudadesAll();
            $EmpleadosActivos = $this->obtenerempleadosActivos();

            return view('Servicios.Captura.datosinformativos', 
            compact('varpantallas', 'varsubmenus','tipo','ClientesActivos','Ciudades','EmpleadosActivos'));

        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function obtenerPersonasAtencion($id_cliente)
    {
        try {
            $personas = \App\Models\ClientesAtencion::where('id_cliente', $id_cliente)
                ->where('estado', 'A')
                ->get();
            return response()->json($personas);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Validar si un folio ya existe en el sistema
     */
    public function validarFolio(Request $request)
    {
        try {
            $folio = $request->get('folio');
            $existe = ServiciosEnc::where('folio', $folio)->exists();
            
            return response()->json([
                'existe' => $existe,
                'folio' => $folio
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function crearPersonaAtencion(Request $request)
    {
        try {
            // Construir el nombre completo concatenando los campos
            $personaAtencion = new \App\Models\ClientesAtencion();
            $personaAtencion->id_cliente = $request->id_cliente;
            $personaAtencion->primer_nombre = $request->primer_nombre;
            $personaAtencion->segundo_nombre = $request->segundo_nombre;
            $personaAtencion->apellido_paterno = $request->apellido_paterno;
            $personaAtencion->apellido_materno = $request->apellido_materno;
            $personaAtencion->telefono = $request->telefono;
            $personaAtencion->correo = $request->correo;
            $personaAtencion->save();

            return response()->json([
                'success' => true,
                'persona' => $personaAtencion,
                'message' => 'Persona de atención creada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function mcaptura(string $tipo, Request $request)
    {
        try {
            // Validar que el folio sea único
            $folio = $request->get("folio");
            $folioExistente = ServiciosEnc::where('folio', $folio)->first();
            
            if ($folioExistente) {
                return back()->with("errorFolio", "El folio '$folio' ya existe en el sistema. Por favor, ingrese un folio diferente.")->withInput();
            }
            
            $id_tiposervicio = $this->identifica_servicio($tipo);


            //CHECAMOS CLIENTE
            $tipo_cliente = $request->get("tipo_cliente");

            if($tipo_cliente == "nuevo"){
            
                $Clientes = new Clientes();
                $Clientes->nombre = $request->get("nombreatencion");
                $Clientes->alias = $request->get("alias");
                $Clientes->estado = "A";
                $Clientes->tipo = $request->get("tipo");
                $Clientes->razon_social = $request->get("razon_social");
                $Clientes->rfc= $request->get("rfc");
                $Clientes->telefono = $request->get("telefono");
                $Clientes->correo_electronico = $request->get("correo_electronico");
                $Clientes->id_ciudad  = $request->get("id_ciudad");
                $Clientes->colonia = $request->get("colonia");
                $Clientes->calle = $request->get("calle");
                $Clientes->numero_int = $request->get("numero_int");
                $Clientes->numero_ext = $request->get("numero_ext");
                $Clientes->cp = $request->get("cp");
                $Clientes->created_by = auth()->user()->name;

                if ($Clientes->save()) {

                    if(is_null($this->obtnerultimocliente())){
                            return back()->with("errorCli", "no guardado correctamente");
                    }else{
                         $cliente = $this->obtnerultimocliente();
                    }
                    
                } else {
                    return back()->with("warning", "no guardado correctamente");
                }

            }else{
                $cliente = $request->get("cliente");
            }
            
                //INSERTA SERVICIO ENC
                $ServiciosEnc = new ServiciosEnc();
                $ServiciosEnc->nombre = $request->get("nombre");
                $ServiciosEnc->folio = $request->get("folio");
                $ServiciosEnc->id_tiposervicio = $id_tiposervicio;
                $ServiciosEnc->estado = "BORRADOR";
                $ServiciosEnc->nivel_progreso = 1;
                $ServiciosEnc->id_vendedor  = $request->get("vendedor");
                $ServiciosEnc->id_cliente  = $cliente;
                $ServiciosEnc->id_atencion = $request->get("id_atencion");
                $ServiciosEnc->fecha_inicio = $request->get("fecha_ini");
                $ServiciosEnc->fecha_limite  = $request->get("fecha_fin");
                $ServiciosEnc->fecha_tentativa_pago = $request->get("fecha_tentativa_pago");
                $ServiciosEnc->created_by = auth()->user()->name;
                $ServiciosEnc->tipo_servicio = $request->get("tipo_servicio");
                $ServiciosEnc->save();

                // Guardar RFQ en tblservicios_enc.otrosconceptos1 si viene
                $rfq = trim((string) $request->get('rfq', ''));
                if ($rfq !== '') {
                    DB::table('tblservicios_enc')
                        ->where('id', $ServiciosEnc->id)
                        ->update([
                            'otrosconceptos1' => $rfq,
                            'updated_at' => now(),
                            'updated_by' => auth()->user()->name ?? 'Sistema'
                        ]);
                }

                if ($ServiciosEnc->save()) {
                    if(is_null($this->obtnerultimoservicio_enc())){
                        return back()->with("errorServ", "no guardado correctamente");
                    }
                    else
                    {
                      
                        $id = $this->obtnerultimoservicio_enc();
                        // Redirección condicional según tipo_servicio

                        $tipo_servicio = $request->get("tipo_servicio");
                        if($tipo == "Proyecto")
                        {
                            return redirect()->route('proyectos.index')->with("success","¡Se guardaron los cambios correctamente!");
                        }
                        elseif($tipo == "Suministro" || $tipo == "Integración")
                        { 
                                return redirect()->route('servicios.pmateriales',[$tipo,$id])->with("success","¡Se guardaron los cambios correctamente!");
                        }
                    }
                    
                } else {
                    return back()->with("warning", "no guardado correctamente");
                }
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function pmateriales(string $tipo, int $id)
    {
        try {
            
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();  
            $servicio_encxid = $this->obtnerservicio_encxid($id);  
            $Listadoproductos = $this->Listadoproductos();  
            $obtnerproductosxservicio = $this->obtnerproductosxservicio($id); 

            return view('Servicios.Captura.materiales',
            compact('varpantallas', 'varsubmenus','tipo','servicio_encxid','Listadoproductos','id','obtnerproductosxservicio'));

        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function pmaterialesproyectos(string $tipo,int $id)
    {
        try {
            
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();  
            $servicio_encxid = $this->obtnerservicio_encxid($id);  
            $Listadoproductos = $this->Listadoproductos();  
            $obtnerproductosxservicio = $this->obtnerproductosxservicio($id); 

            return view('Servicios.Captura.pmaterialesproyectos',
            compact('varpantallas', 'varsubmenus','tipo','servicio_encxid','Listadoproductos','id','obtnerproductosxservicio'));

        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function pcostosproyecto(string $tipo, int $id)
    {
        try {
            
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();  
            $servicio_encxid = $this->obtnerservicio_encxid($id);  
            $costo_sumisnitros = DB::select('select SUM(p.precio_unitario * sp.cantidad_total) as costo_suministros from tblservicios_productos sp
            join tblproductos p on sp.id_producto = p.id where sp.id_servicio_enc = ?;', [$id]);
            $costo_sumisnitros = $costo_sumisnitros[0]->costo_suministros ?? 0;
            $utilidad_porcentaje = DB::table('tblservicios_det')->where('id_servicio_enc', $id)->value('utilidad_porcentaje');
            
            // Variables para el tipo de formulario y gestión de puestos
            $empleados_puestos = $this->obtenerEmpleadosPuestos();
            $empleados_servicio = $this->obtenerEmpleadosServicio($id);
            $tipo_formulario = DB::table('tblservicios_enc')->where('id', $id)->value('tipo_formulario');

            return view('Servicios.Captura.costosproyecto',
            compact('varpantallas', 'varsubmenus','tipo','servicio_encxid','costo_sumisnitros','utilidad_porcentaje','empleados_puestos','empleados_servicio','tipo_formulario','id'));

        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function magregarproducto(string $tipo, int $id, Request $request)
    {
        try {
             $id_producto  = $request->get("producto");
             $cantidad =  $request->get("cantidad");
             $cantidad_disponible = 0;
             $cantidad_faltante = 0;

            if($cantidad > 0){
               //SUMAMOS CANTIDAD NUEVAS Y PASADAS
                $valida_norepetidos_servprod = $this->valida_norepetidos_servprod($id, $id_producto);
                $cantidad_total = $cantidad + $valida_norepetidos_servprod;

                //VALIDAR QUE NO EXISTAN REPETIDOS PRODUCTOS Y COMPACTAR EN UNA SOLA LINEA
                if($valida_norepetidos_servprod == 0){
                        
                }else{
                    //LOCALIZAMOS EL REGISTRO DEL SERV_PROD PARA ACTULIZAR DEDE AHÍ 
                    // $Borrartbl1 = DB::select('delete from tblservicios_productos where id_servicio_enc = ? and id_producto = ?;', [$id, $id_producto]);
                    $select = DB::select('select * from tblservicios_productos where id_servicio_enc = ? and id_producto = ?;', [$id, $id_producto]);
                    foreach($select as $key){
                        $idprodserv = $key->id;
                        $cantidad_disponible = $cantidad_disponible + $key->cantidad_disponible;
                        $cantidad_faltante = $cantidad_faltante + $key->cantidad_faltante;
                    }
                }

           
                //VALIDAR LA EXISTENCIA DEL PRODUCTO EN EL STOCK
                $cantidad_encontrada = $this->aparta_existencias($id_producto, $cantidad);
                $cantidad_reservada = $this->cantidad_reservadaxid($id_producto);

                if($cantidad_encontrada == 0){
                    $cantidad_disponible = $cantidad_disponible;
                    $cantidad_faltante = $cantidad;
                    $estado = "Sin Stock";
                    
                }elseif($cantidad <= $cantidad_encontrada){

                    $cantidad_disponible = $cantidad + $cantidad_disponible;
                    $cantidad_faltante = $cantidad_faltante + 0;
                    $estado = "Con Stock";

                    DB::select('update tblexistencias set cantidad_reservada = ? + ?  where id_producto = ?;',[$cantidad_encontrada,$cantidad_reservada,$id_producto]);

                }else{

                    $cantidad_disponible = $cantidad_encontrada + $cantidad_disponible;
                    $cantidad_faltante = $cantidad_faltante + ($cantidad - $cantidad_encontrada) ;
                    $estado = "Diferencia";

                    DB::select('update tblexistencias set cantidad_reservada = ? + ?  where id_producto = ?;',[$cantidad_encontrada,$cantidad_reservada,$id_producto]);

                }

                // return "CANTIDAD ENCONTRDA = ".$cantidad_encontrada." CANTIDAD DISPONIBLE = ".$cantidad_disponible." CANTIDAD FALTANTE = ".$cantidad_faltante." ESTADO = ".$estado;

                if($valida_norepetidos_servprod == 0){
                    //CREAMOS DE 0
                    $ServiciosProductos = new ServiciosProductos();
                    $ServiciosProductos->id_servicio_enc = $id;
                    $ServiciosProductos->id_producto  = $id_producto;
                    $ServiciosProductos->created_by = auth()->user()->name;

                }else{
                    //ACTUALIZAMOS LA LICITACION SI ES QUE EXISTE
                     $select2 = collect(DB::select('select * from tbllicitacion_enc where id_servicio = ?;', [$id]));

                     if($select2->isNotEmpty()){
                        foreach($select2  as $key){
                            $id_licitacion = $key->id;
                        }
                        $select3 = collect(DB::select('update tbllicitacion_det set cantidad = ? where licitacion_id = ? and producto_id = ?;', [$cantidad_total,$id_licitacion, $id_producto]));
                     }

                    //ACTUALIZAMOS REGISTRO
                    $ServiciosProductos = ServiciosProductos::find($idprodserv);
                    $ServiciosProductos->updated_by = auth()->user()->name;
                }
                
                    $ServiciosProductos->estado = $estado;
                    $ServiciosProductos->cantidad_disponible = $cantidad_disponible;
                    $ServiciosProductos->cantidad_faltante = $cantidad_faltante;
                    $ServiciosProductos->cantidad_total = $cantidad_total;
                    
                if ($ServiciosProductos->save()) {
                        if($tipo == "Suministro"){
                                $guardar_total_proyectado = $this->total_productos($id);

                                if($guardar_total_proyectado == "exito"){
                                    return back()->with("success","¡Se guardaron los cambios correctamente!");
                                }else {
                                    return back()->with("error_msg", "Error al guardar el total proyectado, revise o intente despues.");
                                }
                                
                        }else{
                            return back()->with("success","¡Se guardaron los cambios correctamente!");
                        }
                } else {
                    return back()->with("warningProducto", "no guardado correctamente");
                }
            }else{
                 return back()->with("errorcero", "no guardado correctamente");
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function meliminarproducto(string $tipo, int $id, int $idproserv, int $idprod, int $cantidad)
    {
        try {
            //ACTUALIZAMOS LA LICITACION SI ES QUE EXISTE
            $select2 = collect(DB::select('select * from tbllicitacion_enc where id_servicio = ?;', [$id]));

            if($select2->isNotEmpty()){
                foreach($select2  as $key){
                    $id_licitacion = $key->id;
                }
                $select3 = collect(DB::select('delete from tbllicitacion_det  where licitacion_id = ? and producto_id = ?;', [$id_licitacion, $idprod]));
                $select4 = collect(DB::select('delete from tbllicitacionproducto_proveedor where licitacion_id = ? and producto_id = ?;', [$id_licitacion, $idprod]));
            }


            //QUITAMOS SI SE RESERVO CANTIDAD
            $cantidad_reservada = $this->cantidad_reservadaxid($idprod);
            DB::select('update tblexistencias set cantidad_reservada = ? - ?  where id_producto = ?;',[$cantidad_reservada,$cantidad,$idprod]);
            $Borrartbl1 = DB::select('delete from tblservicios_productos where id = ? ', [$idproserv]);


            if($tipo == "Suministro"){
                $guardar_total_proyectado = $this->total_productos($id);
            
                if($guardar_total_proyectado == "exito"){
                    return back()->with("success","¡Se guardaron los cambios correctamente!");
                }else {
                    return back()->with("error_msg", "Error al guardar el total proyectado, revise o intente despues.");
                }
            }else{
                return back()->with("success","¡Se guardaron los cambios correctamente!");
            }
        

        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

     public function mactualizarProducto()
    {
        try {
             $id_producto  = 0;
             $cantidad =  0;
             $cantidad_disponible = 0;
             $cantidad_faltante = 0;
             $stockPendiete = $this->stockPendiete();

            if($stockPendiete->isNotEmpty()){
                foreach($stockPendiete as $list){

                    //VALIDAR LA EXISTENCIA DEL PRODUCTO EN EL STOCK
                    $cantidad_encontrada = $this->aparta_existencias($list->id_producto, $list->cantidad_faltante);
                    $cantidad_reservada = $this->cantidad_reservadaxid($list->id_producto);

                    if($cantidad_encontrada == 0){
                        $cantidad_disponible = $list->cantidad_disponible;
                        $cantidad_faltante = $list->cantidad_faltante;
                        $estado = $list->estado;
                        
                    }elseif($list->cantidad_faltante <= $cantidad_encontrada){

                        $cantidad_disponible = $list->cantidad_disponible + $list->cantidad_faltante;
                        $cantidad_faltante = 0;
                        $estado = "Con Stock";

                        DB::select('update tblexistencias set cantidad_reservada = ? + ?  where id_producto = ?;',[$cantidad_encontrada,$cantidad_reservada,$list->id_producto]);
                        DB::select('update tblservicios_productos set estado = ?,cantidad_disponible = ?,cantidad_faltante = ?  where id = ?;',[$estado,$cantidad_disponible,$cantidad_faltante,$list->id]);

                    }else{

                        $cantidad_disponible = $list->cantidad_disponible + $cantidad_encontrada;
                        $cantidad_faltante = $list->cantidad_faltante - $cantidad_encontrada;
                        $estado = "Diferencia";

                        DB::select('update tblexistencias set cantidad_reservada = ? + ?  where id_producto = ?;',[$cantidad_encontrada,$cantidad_reservada,$list->id_producto]);
                        DB::select('update tblservicios_productos set estado = ?,cantidad_disponible = ?,cantidad_faltante = ?  where id = ?;',[$estado,$cantidad_disponible,$cantidad_faltante,$list->id]);

                    }

                    echo 
                    "SERVICO ".$list->id_servicio_enc."<br>"
                    ."PRODUCTO ".$list->id_producto."<br>"
                    ."CANTIDAD ".$list->cantidad_total."<br>"
                    ."DISPONIBLE ".$cantidad_disponible."<br>"
                    ."FALTANTE ".$cantidad_faltante."<br>"
                    ."ESTADO ".$estado."<br><br><br>";
                }
            }

            return 1;
            // if($cantidad > 0){
            //    //SUMAMOS CANTIDAD NUEVAS Y PASADAS
            //     $valida_norepetidos_servprod = $this->valida_norepetidos_servprod($id, $id_producto);
            //     $cantidad_total = $cantidad + $valida_norepetidos_servprod;

            //     //VALIDAR QUE NO EXISTAN REPETIDOS PRODUCTOS Y COMPACTAR EN UNA SOLA LINEA
            //     if($valida_norepetidos_servprod == 0){
                        
            //     }else{
            //         //LOCALIZAMOS EL REGISTRO DEL SERV_PROD PARA ACTULIZAR DEDE AHÍ 
            //         // $Borrartbl1 = DB::select('delete from tblservicios_productos where id_servicio_enc = ? and id_producto = ?;', [$id, $id_producto]);
            //         $select = DB::select('select * from tblservicios_productos where id_servicio_enc = ? and id_producto = ?;', [$id, $id_producto]);
            //         foreach($select as $key){
            //             $idprodserv = $key->id;
            //             $cantidad_disponible = $cantidad_disponible + $key->cantidad_disponible;
            //             $cantidad_faltante = $cantidad_faltante + $key->cantidad_faltante;
            //         }
            //     }

           
            //     //VALIDAR LA EXISTENCIA DEL PRODUCTO EN EL STOCK
            //     $cantidad_encontrada = $this->aparta_existencias($id_producto, $cantidad);
            //     $cantidad_reservada = $this->cantidad_reservadaxid($id_producto);

            //     if($cantidad_encontrada == 0){
            //         $cantidad_disponible = $cantidad_disponible;
            //         $cantidad_faltante = $cantidad;
            //         $estado = "Sin Stock";
                    
            //     }elseif($cantidad <= $cantidad_encontrada){

            //         $cantidad_disponible = $cantidad + $cantidad_disponible;
            //         $cantidad_faltante = $cantidad_faltante + 0;
            //         $estado = "Con Stock";

            //         DB::select('update tblexistencias set cantidad_reservada = ? + ?  where id_producto = ?;',[$cantidad_encontrada,$cantidad_reservada,$id_producto]);

            //     }else{

            //         $cantidad_disponible = $cantidad_encontrada + $cantidad_disponible;
            //         $cantidad_faltante = $cantidad_faltante + ($cantidad - $cantidad_encontrada) ;
            //         $estado = "Diferencia";

            //         DB::select('update tblexistencias set cantidad_reservada = ? + ?  where id_producto = ?;',[$cantidad_encontrada,$cantidad_reservada,$id_producto]);

            //     }

            //     // return "CANTIDAD ENCONTRDA = ".$cantidad_encontrada." CANTIDAD DISPONIBLE = ".$cantidad_disponible." CANTIDAD FALTANTE = ".$cantidad_faltante." ESTADO = ".$estado;

            //     if($valida_norepetidos_servprod == 0){
            //         //CREAMOS DE 0
            //         $ServiciosProductos = new ServiciosProductos();
            //         $ServiciosProductos->id_servicio_enc = $id;
            //         $ServiciosProductos->id_producto  = $id_producto;
            //         $ServiciosProductos->created_by = auth()->user()->name;

            //     }else{
            //         //ACTUALIZAMOS LA LICITACION SI ES QUE EXISTE
            //          $select2 = collect(DB::select('select * from tbllicitacion_enc where id_servicio = ?;', [$id]));

            //          if($select2->isNotEmpty()){
            //             foreach($select2  as $key){
            //                 $id_licitacion = $key->id;
            //             }
            //             $select3 = collect(DB::select('update tbllicitacion_det set cantidad = ? where licitacion_id = ? and producto_id = ?;', [$cantidad_total,$id_licitacion, $id_producto]));
            //          }

            //         //ACTUALIZAMOS REGISTRO
            //         $ServiciosProductos = ServiciosProductos::find($idprodserv);
            //         $ServiciosProductos->updated_by = auth()->user()->name;
            //     }
                
            //         $ServiciosProductos->estado = $estado;
            //         $ServiciosProductos->cantidad_disponible = $cantidad_disponible;
            //         $ServiciosProductos->cantidad_faltante = $cantidad_faltante;
            //         $ServiciosProductos->cantidad_total = $cantidad_total;
                    

            //     if ($ServiciosProductos->save()) {

            //         return back()->with("success","¡Se guardaron los cambios correctamente!");

            //     } else {
            //         return back()->with("warningProducto", "no guardado correctamente");
            //     }
            // }else{
            //      return back()->with("errorcero", "no guardado correctamente");
            // }
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function mmateriales(string $tipo, int $id,Request $request)
    {
        try {
            
                $ServiciosEnc = ServiciosEnc::find($id);
                // $ServiciosEnc->estado = "BORRADOR";
                // $ServiciosEnc->nivel_progreso = 2;
                if(is_null($request->get("licitaciones"))){
                        $ServiciosEnc->estado = "EN PROCESO";
                }else{
                        $ServiciosEnc->estado = "EN ESPERA";
                }
                

                if($tipo == "Suministro"){
                    
                    $ServiciosEnc->nivel_progreso = 3;
                    
                }elseif($tipo == "Integración"){
                
                    $ServiciosEnc->nivel_progreso = 2;
                }

                $ServiciosEnc->solicita_licitacion = $request->get("licitaciones");
                $ServiciosEnc->updated_by = auth()->user()->name;
                $ServiciosEnc->save();

                if($ServiciosEnc->save()){

                    //  return redirect()->route('servicios.pcostos',[$tipo,$id])->with("success","¡Se guardaron los cambios correctamente!");

                    if($tipo == "Suministro"){
                        return redirect()->route('servicios.index')->with("success","¡Se guardaron los cambios correctamente!");
                    }elseif($tipo == "Integración"){
                        return redirect()->route('servicios.pcostos',[$tipo,$id])->with("success","¡Se guardaron los cambios correctamente!");
                    }

                }else{
                      return back()->with("error_pasar", "no guardado correctamente");
                }


        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function mmaterialesproyecto(string $tipo, int $id,Request $request)
    {
        try {
            
                $ServiciosEnc = ServiciosEnc::find($id);
                // $ServiciosEnc->estado = "BORRADOR";
                // $ServiciosEnc->nivel_progreso = 2;
                if(is_null($request->get("licitaciones"))){
                        $ServiciosEnc->estado = "EN PROCESO";
                }else{
                        $ServiciosEnc->estado = "EN ESPERA";
                }
                
                $ServiciosEnc->nivel_progreso = 2;
                $ServiciosEnc->solicita_licitacion = $request->get("licitaciones");
                $ServiciosEnc->updated_by = auth()->user()->name;
                $ServiciosEnc->save();

                if($ServiciosEnc->save()){
                    return redirect()->route('servicios.pcostosproyecto',[$tipo,$id])->with("success","¡Se guardaron los cambios correctamente!");
                }else{
                      return back()->with("error_pasar", "no guardado correctamente");
                }


        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }


    public function pcostos(string $tipo, int $id)
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();        
            $conceptos_servicios = $this->obtener_conceptos_servicios();          
            $servicio_encxid = $this->obtnerservicio_encxid($id);  
            $empleados_puestos = $this->obtenerEmpleadosPuestos();
            $empleados_servicio = $this->obtenerEmpleadosServicio($id);
            $conceptos_servicio = $this->obtenerConceptosServicio($id);
            $costo_sumisnitros = DB::select('select SUM(p.precio_unitario * sp.cantidad_total) as costo_suministros from tblservicios_productos sp
            join tblproductos p on sp.id_producto = p.id where sp.id_servicio_enc = ?;', [$id]);
            $costo_sumisnitros = $costo_sumisnitros[0]->costo_suministros;

            // Obtener el tipo de formulario guardado
            $tipo_formulario = DB::table('tblservicios_enc')->where('id', $id)->value('tipo_formulario');
            // Obtener el costo por proveedor externo guardado
            $costo_externo = DB::table('tblservicios_enc')->where('id', $id)->value('costo_externo');
            // Obtener el porcentaje de utilidad guardado
            $utilidad_porcentaje = DB::table('tblservicios_det')->where('id_servicio_enc', $id)->value('utilidad_porcentaje');

            // Si es tipo proyecto, obtener conceptos guardados
            $conceptos_proyecto = collect([]);
            if($tipo == "Proyecto") {
                $conceptos_proyecto = DB::select('
                    SELECT 
                        nombre_concepto,
                        descripcion_concepto,
                        costo_concepto,
                        otrosconceptos1 as cantidad,
                        otrosconceptos2 as precio_unitario
                    FROM tconceptos_proyectos 
                    WHERE id_proyecto = ? 
                    ORDER BY id ASC
                ', [$id]);
                $conceptos_proyecto = collect($conceptos_proyecto);
                dd($conceptos_proyecto);
            }

            $existe_cotizacion_integracion = \DB::table('tblcotizacion_borrador_integracion')
                ->where('id_serv_enc', $id)
                ->exists();

            return view('Servicios.Captura.costos', 
            compact('varpantallas', 'varsubmenus','tipo','conceptos_servicios','servicio_encxid','id','empleados_puestos','empleados_servicio','conceptos_servicio','costo_sumisnitros','conceptos_proyecto','tipo_formulario','costo_externo','utilidad_porcentaje', 'existe_cotizacion_integracion'));
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function obtenerEmpleadosPuestos()
    {
        try {
            $empleados = DB::select('
                SELECT 
                    nom.idempleado, 
                    emp.primer_nombre, 
                    emp.segundo_nombre, 
                    emp.apellido_paterno, 
                    emp.apellido_materno, 
                    nom.salario_fijo
                FROM tblnominas nom
                JOIN tblempleados emp ON emp.id = nom.idempleado 
                WHERE emp.idpuesto >= 10
                ORDER BY emp.primer_nombre, emp.apellido_paterno
            ');
            
            return collect($empleados);
        } catch (\Exception $ex) {
            return collect([]);
        }
    }


    public function mcostos(string $tipo, int $id,Request $request)
    {
        try {
            
          //INSERTRN SERVICIOS_DET
          //CALCULO DE DESGLOSE DE TIEMPOS PARA PRESUPUESTO CON INSERT
          //ACTUALIZAR PROGRESO EN SERVICIOS_ENC
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    //VALIDACION PARA CONTINUAR CAPTURA
    public function mvalidaprogreso(string $tipo, int $nivel_progreso, int $id)
    {
        try {


            if($tipo == "Suministro"){
                if($nivel_progreso == 1){
                    return redirect()->route('servicios.pmateriales',[$tipo,$id]);
                }
            }elseif($tipo == "Integración"){
                if($nivel_progreso == 1){
                    return redirect()->route('servicios.pmateriales',[$tipo,$id]);
                }elseif($nivel_progreso == 2){
                     return redirect()->route('servicios.pcostos',[$tipo,$id]);
                }
            }elseif($tipo == "Proyecto"){
                if($nivel_progreso == 2){
                    return redirect()->route('servicios.pmateriales',[$tipo,$id]);
                }elseif($nivel_progreso == 1){
                    return redirect()->route('servicios.pcostos',[$tipo,$id]);
                }
            }
         
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }


    //METODOS DE EDICION
    public function peditcaptura(string $tipo, int $id)
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();  
            $ClientesActivos = $this->obtenerClientesActivos();
            $Ciudades = $this->obtenerciudadesAll();
            $EmpleadosActivos = $this->obtenerempleadosActivos();
            $servicio_encxid = $this->obtnerservicio_encxid($id);  

            return view('Servicios.Editar.datosinformativos', 
            compact('varpantallas', 'varsubmenus','tipo','ClientesActivos','Ciudades','EmpleadosActivos','servicio_encxid','id'));

         
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function meditcaptura(string $tipo, int $id, Request $request)
    {
        try {
            $id_tiposervicio = $this->identifica_servicio($tipo);

            //CHECAMOS CLIENTE
            $tipo_cliente = $request->get("tipo_cliente");

            if($tipo_cliente == "nuevo"){
            
                $Clientes = new Clientes();
                $Clientes->nombre = $request->get("nombreatencion");
                $Clientes->alias = $request->get("alias");
                $Clientes->estado = "A";
                $Clientes->tipo = $request->get("tipo");
                $Clientes->razon_social = $request->get("razon_social");
                $Clientes->rfc= $request->get("rfc");
                $Clientes->telefono = $request->get("telefono");
                $Clientes->correo_electronico = $request->get("correo_electronico");
                $Clientes->id_ciudad  = $request->get("id_ciudad");
                $Clientes->colonia = $request->get("colonia");
                $Clientes->calle = $request->get("calle");
                $Clientes->numero_int = $request->get("numero_int");
                $Clientes->numero_ext = $request->get("numero_ext");
                $Clientes->cp = $request->get("cp");
                $Clientes->created_by = auth()->user()->name;

                if ($Clientes->save()) {

                    if(is_null($this->obtnerultimocliente())){
                            return back()->with("errorCli", "no guardado correctamente");
                    }else{
                         $cliente = $this->obtnerultimocliente();
                    }
                    
                } else {
                    return back()->with("warning", "no guardado correctamente");
                }

            }else{
                $cliente = $request->get("cliente");
            }
            
                //INSERTA SERVICIO ENC
                $ServiciosEnc =  ServiciosEnc::find($id);
                $ServiciosEnc->nombre = $request->get("nombre");
                $ServiciosEnc->folio = $request->get("folio");
                $ServiciosEnc->id_vendedor  = $request->get("vendedor");
                $ServiciosEnc->id_cliente  = $cliente;
                $ServiciosEnc->id_atencion = $request->get("id_atencion");
                $ServiciosEnc->fecha_inicio = $request->get("fecha_ini");
                $ServiciosEnc->fecha_limite  = $request->get("fecha_fin");
                $ServiciosEnc->fecha_tentativa_pago = $request->get("fecha_tentativa_pago");
                $ServiciosEnc->updated_by = auth()->user()->name;
                $ServiciosEnc->save();

                if ($ServiciosEnc->save()) {
                    return redirect()->route('servicios.peditmateriales',[$tipo,$id])->with("success","¡Se guardaron los cambios correctamente!");
                } else {
                    return back()->with("warning", "no guardado correctamente");
                }


        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warning_msg", "No guardado correctamente");
        }
    }


     public function peditmateriales(string $tipo, int $id)
    {
        try {

            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();  
            $servicio_encxid = $this->obtnerservicio_encxid($id);  
            $Listadoproductos = $this->Listadoproductos();  
            $obtnerproductosxservicio = $this->obtnerproductosxservicio($id); 

            return view('Servicios.Editar.materiales',
            compact('varpantallas', 'varsubmenus','tipo','servicio_encxid','Listadoproductos','id','obtnerproductosxservicio'));

         
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function meditmateriales(string $tipo, int $id,Request $request)
    {
        try {
            //valida la lista de productos_servicos y envia los que no se esten cotizando

            if($tipo == "Suministro"){
                return redirect()->route('servicios.index')->with("success","¡Se guardaron los cambios correctamente!");
            }elseif($tipo == "Integración"){
                return redirect()->route('servicios.peditcostos',[$tipo,$id])->with("success","¡Se guardaron los cambios correctamente!");
            }

        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }


    public function peditcostos(string $tipo, int $id)
    {
        try {

            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();        
            $conceptos_servicios = $this->obtener_conceptos_servicios();          
            $servicio_encxid = $this->obtnerservicio_encxid($id);  

            return view('Servicios.Editar.costos', 
            compact('varpantallas', 'varsubmenus','tipo','conceptos_servicios','servicio_encxid','id'));
         
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }


     public function meditcostos(string $tipo, int $id,Request $request)
    {
        try {

           
         
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function guardarEmpleadoServicio(Request $request)
    {
        try {
            $id_servicio_enc = $request->get('id_servicio_enc');
            $id_empleado = $request->get('id_empleado');
            $nombre_completo = $request->get('nombre_completo');
            $sueldo_semanal = $request->get('sueldo_semanal');
            
            // Verificar si ya existe el empleado en este servicio
            $existe = DB::select('SELECT id FROM tblservicios_personal WHERE id_servicio_enc = ? AND id_empleado = ?', [$id_servicio_enc, $id_empleado]);
            
            if (empty($existe)) {
                // Insertar nuevo empleado
                DB::insert('INSERT INTO tblservicios_personal (id_servicio_enc, id_empleado, nombre_completo, sueldo_semanal, create_at) VALUES (?, ?, ?, ?, NOW())', 
                    [$id_servicio_enc, $id_empleado, $nombre_completo, $sueldo_semanal]);
                
                return response()->json(['success' => true, 'message' => 'Empleado agregado correctamente']);
            } else {
                return response()->json(['success' => false, 'message' => 'El empleado ya existe en este servicio']);
            }
            
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'message' => 'Error al guardar empleado: ' . $ex->getMessage()]);
        }
    }

    public function eliminarEmpleadoServicio(Request $request)
    {
        try {
            $id_servicio_enc = $request->get('id_servicio_enc');
            $id_empleado = $request->get('id_empleado');
            
            DB::delete('DELETE FROM tblservicios_personal WHERE id_servicio_enc = ? AND id_empleado = ?', [$id_servicio_enc, $id_empleado]);
            
            return response()->json(['success' => true, 'message' => 'Empleado eliminado correctamente']);
            
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'message' => 'Error al eliminar empleado: ' . $ex->getMessage()]);
        }
    }

    public function obtenerEmpleadosServicio(int $id_servicio_enc)
    {
        try {
            $empleados = DB::select('
                SELECT id_empleado, nombre_completo, sueldo_semanal 
                FROM tblservicios_personal 
                WHERE id_servicio_enc = ?
                ORDER BY nombre_completo
            ', [$id_servicio_enc]);
            
            return collect($empleados);
        } catch (\Exception $ex) {
            return collect([]);
        }
    }

    public function guardarConceptoServicio(Request $request)
    {
        try {
            $id_servicio_enc = $request->get('id_servicio_enc');
            $id_concepto = $request->get('id_concepto');
            $monto = $request->get('monto');
            $estado = $request->get('estado', 1); // Por defecto activo
            
            // Validar que los datos requeridos estén presentes
            if (!$id_servicio_enc || !$id_concepto) {
                return response()->json(['success' => false, 'message' => 'Datos incompletos para guardar el concepto']);
            }
            
            // Verificar si ya existe el concepto en este servicio
            $existe = DB::select('SELECT id FROM tblservicios_integracion_conceptos WHERE id_servicio_enc = ? AND id_concepto = ?', [$id_servicio_enc, $id_concepto]);
            
            if (empty($existe)) {
                // Verificar que el concepto existe en la tabla de conceptos
                $conceptoValido = DB::select('SELECT id, nombre FROM tblconceptos_servicios WHERE id = ?', [$id_concepto]);
                if (empty($conceptoValido)) {
                    return response()->json(['success' => false, 'message' => 'El concepto especificado no existe']);
                }
                
                // Insertar nuevo concepto
                DB::insert('INSERT INTO tblservicios_integracion_conceptos (id_servicio_enc, id_concepto, monto, estado, createat) VALUES (?, ?, ?, ?, NOW())', 
                    [$id_servicio_enc, $id_concepto, $monto, $estado]);
                
                return response()->json(['success' => true, 'message' => 'Concepto agregado correctamente']);
            } else {
                return response()->json(['success' => false, 'message' => 'El concepto ya existe en este servicio. No se pueden duplicar conceptos.']);
            }
            
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'message' => 'Error al guardar concepto: ' . $ex->getMessage()]);
        }
    }

    public function actualizarConceptoServicio(Request $request)
    {
        try {
            $id_servicio_enc = $request->get('id_servicio_enc');
            $id_concepto = $request->get('id_concepto');
            $monto = $request->get('monto');
            $estado = $request->has('estado') ? $request->get('estado') : null;
            
            // Actualizar el concepto existente
            $updateData = [];
            if (!is_null($monto)) {
                $updateData['monto'] = $monto;
            }
            if (!is_null($estado)) {
                $updateData['estado'] = $estado;
            }
            if (!empty($updateData)) {
                $updateData['updateat'] = now();
                DB::table('tblservicios_integracion_conceptos')
                    ->where('id_servicio_enc', $id_servicio_enc)
                    ->where('id_concepto', $id_concepto)
                    ->update($updateData);
            }
            return response()->json(['success' => true, 'message' => 'Concepto actualizado correctamente']);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'message' => 'Error al actualizar concepto: ' . $ex->getMessage()]);
        }
    }

    public function eliminarConceptoServicio(Request $request)
    {
        try {
            $id_servicio_enc = $request->get('id_servicio_enc');
            $id_concepto = $request->get('id_concepto');
            
            DB::delete('DELETE FROM tblservicios_integracion_conceptos WHERE id_servicio_enc = ? AND id_concepto = ?', [$id_servicio_enc, $id_concepto]);
            
            return response()->json(['success' => true, 'message' => 'Concepto eliminado correctamente']);
            
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'message' => 'Error al eliminar concepto: ' . $ex->getMessage()]);
        }
    }

    public function obtenerConceptosServicio(int $id_servicio_enc)
    {
        try {
            $conceptos = DB::select('
                SELECT sic.id_concepto, sic.monto, cs.nombre, cs.descripcion, sic.estado
                FROM tblservicios_integracion_conceptos sic
                JOIN tblconceptos_servicios cs ON cs.id = sic.id_concepto
                WHERE sic.id_servicio_enc = ?
                ORDER BY cs.nombre
            ', [$id_servicio_enc]);
            
            return collect($conceptos);
        } catch (\Exception $ex) {
            return collect([]);
        }
    }

    public function guardarServicioDet(Request $request)
    {    
        try {
            $id_servicio_enc = $request->get('id_servicio_enc');
            $nombre = $request->get('nombre');
            $total_facturacion = $request->get('total_facturacion');
            $total_costos = $request->get('total_costos');
            $total_utilidad = $request->get('total_utilidad');
            $iva_integrado = $request->get('iva_integrado');
            $utilidad_porcentaje = $request->get('utilidad_porcentaje');
            $tipo_formulario = $request->get('tipo_formulario');
            $costo_proveedor_externo = $request->get('costo_proveedor_externo');
            $monto_utilidad = $request->get('monto_utilidad'); // Nuevo campo
            //dd($monto_utilidad);
            
            // Verificar si ya existe un registro para este servicio
            $servicioDet = ServiciosDet::where('id_servicio_enc', $id_servicio_enc)->first();
            
            if (!$servicioDet) {
                // Crear nuevo registro
                $servicioDet = new ServiciosDet();
                $servicioDet->nombre = $nombre;
                $servicioDet->id_servicio_enc = $id_servicio_enc;
                $servicioDet->id_concepto = 14;
                $servicioDet->monto_proyectado = $total_facturacion;
                $servicioDet->monto_real = $total_costos;
                $servicioDet->otrosconceptos1 = $total_utilidad;
                $servicioDet->otrosconceptos2 = $iva_integrado;
                $servicioDet->utilidad_porcentaje = $utilidad_porcentaje;
                $servicioDet->created_by = auth()->user()->name;
                if ($monto_utilidad !== null) {
                    $servicioDet->monto_utilidad = $monto_utilidad;
                }
                $servicioDet->save();
            } else {
                // Actualizar registro existente
                $servicioDet->monto_proyectado = $total_facturacion;
                $servicioDet->monto_real = $total_costos;
                $servicioDet->otrosconceptos1 = $total_utilidad;
                $servicioDet->otrosconceptos2 = $iva_integrado;
                $servicioDet->utilidad_porcentaje = $utilidad_porcentaje;
                $servicioDet->updated_by = auth()->user()->name;
                if ($monto_utilidad !== null) {
                    $servicioDet->monto_utilidad = $monto_utilidad;
                }
                $servicioDet->save();
            }
            
            // Actualizar el campo tipo_formulario y costo_externo en tblservicios_enc
            $updateData = [];
            if ($tipo_formulario) {
                $updateData['tipo_formulario'] = $tipo_formulario;
            }
            if ($costo_proveedor_externo !== null) {
                $updateData['costo_externo'] = $costo_proveedor_externo;
            }
            if (!empty($updateData)) {
                DB::table('tblservicios_enc')
                    ->where('id', $id_servicio_enc)
                    ->update($updateData);
            }
            
            return response()->json(['success' => true, 'message' => 'Información guardada correctamente']);
            
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'message' => 'Error al guardar información: ' . $ex->getMessage()]);
            //dd($ex);
        }
    }

    // ===== MÉTODOS PARA ARCHIVOS DE INGENIERÍA =====
    
    public function subirArchivoIngenieria(Request $request)
    {
        try {
            $request->validate([
                'archivo' => 'required|file|mimes:pdf,jpg,jpeg,png|max:512000', // 500MB
                'id_servicio_enc' => 'required|integer'
            ]);
            
            $archivo = $request->file('archivo');
            $id_servicio_enc = $request->get('id_servicio_enc');
            
            // Crear directorio si no existe
            $directorio = public_path('ingenieria');
            if (!file_exists($directorio)) {
                mkdir($directorio, 0755, true);
            }
            
            // Generar nombre único para el archivo
            $extension = $archivo->getClientOriginalExtension();
            $nombreArchivo = 'ingenieria_' . $id_servicio_enc . '_' . time() . '.' . $extension;
            
            // Mover archivo al directorio
            $archivo->move($directorio, $nombreArchivo);
            
            // Guardar la URL en tblservicios_det en la columna otrosconceptos3
            $rutaArchivo = 'ingenieria/' . $nombreArchivo;
            
            // Verificar si ya existe un registro en tblservicios_det para este servicio
            $servicioDet = DB::table('tblservicios_det')
                ->where('id_servicio_enc', $id_servicio_enc)
                ->first();
            
            if ($servicioDet) {
                // Actualizar registro existente
                DB::table('tblservicios_det')
                    ->where('id_servicio_enc', $id_servicio_enc)
                    ->update([
                        'otrosconceptos3' => $rutaArchivo,
                        'updated_at' => now(),
                        'updated_by' => auth()->user()->name
                    ]);
            } else {
                // Crear nuevo registro
                DB::table('tblservicios_det')->insert([
                    'id_servicio_enc' => $id_servicio_enc,
                    'nombre' => 'Archivo de Ingeniería',
                    'id_concepto' => 14, // Concepto por defecto
                    'monto_proyectado' => 0.00,
                    'monto_real' => 0.00,
                    'otrosconceptos3' => $rutaArchivo,
                    'created_at' => now(),
                    'created_by' => auth()->user()->name
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Archivo subido correctamente',
                'ruta' => $rutaArchivo
            ]);
            
        } catch (\Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir archivo: ' . $ex->getMessage()
            ]);
        }
    }
    
    public function eliminarArchivoIngenieria(Request $request)
    {
        try {
            $id_servicio_enc = $request->get('id_servicio_enc');
            
            // Obtener información del archivo desde tblservicios_det
            $servicioDet = DB::table('tblservicios_det')
                ->where('id_servicio_enc', $id_servicio_enc)
                ->first();
            
            if (!$servicioDet || !$servicioDet->otrosconceptos3) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró archivo de ingeniería para este servicio'
                ]);
            }
            
            // Eliminar archivo físico
            $rutaArchivo = public_path($servicioDet->otrosconceptos3);
            if (file_exists($rutaArchivo)) {
                unlink($rutaArchivo);
            }
            
            // Limpiar la columna otrosconceptos3 en la base de datos
            DB::table('tblservicios_det')
                ->where('id_servicio_enc', $id_servicio_enc)
                ->update([
                    'otrosconceptos3' => null,
                    'updated_at' => now(),
                    'updated_by' => auth()->user()->name
                ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Archivo eliminado correctamente'
            ]);
            
        } catch (\Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar archivo: ' . $ex->getMessage()
            ]);
        }
    }

    public function obtenerArchivoIngenieria(int $id_servicio_enc)
    {
        try {
            // Obtener información del archivo desde tblservicios_det
            $servicioDet = DB::table('tblservicios_det')
                ->where('id_servicio_enc', $id_servicio_enc)
                ->first();
            
            if (!$servicioDet || !$servicioDet->otrosconceptos3) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró archivo de ingeniería para este servicio'
                ]);
            }
            
            // Extraer nombre del archivo de la ruta
            $rutaArchivo = $servicioDet->otrosconceptos3;
            $nombreArchivo = basename($rutaArchivo);
            
            return response()->json([
                'success' => true,
                'archivo' => [
                    'ruta' => $rutaArchivo,
                    'nombre_original' => $nombreArchivo,
                    'fecha' => $servicioDet->created_at ? date('d/m/Y H:i', strtotime($servicioDet->created_at)) : 'Fecha no disponible'
                ]
            ]);
            
        } catch (\Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener archivo: ' . $ex->getMessage()
            ]);
        }
    }

    public function descargarArchivoIngenieria(int $id_servicio_enc)
    {
        try {
            \Log::info("Iniciando descarga de archivo para servicio: {$id_servicio_enc}");
            
            // Obtener información del archivo desde tblservicios_det
            $servicioDet = DB::table('tblservicios_det')
                ->where('id_servicio_enc', $id_servicio_enc)
                ->first();
            
            \Log::info("Registro encontrado en tblservicios_det:", ['servicio_det' => $servicioDet]);
            
            if (!$servicioDet) {
                \Log::error("No se encontró registro en tblservicios_det para el servicio: {$id_servicio_enc}");
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró información del archivo para este servicio'
                ], 404);
            }
            
            if (!$servicioDet->otrosconceptos3) {
                \Log::error("Campo otrosconceptos3 está vacío para el servicio: {$id_servicio_enc}");
                return response()->json([
                    'success' => false,
                    'message' => 'No hay archivo de ingeniería asociado a este servicio'
                ], 404);
            }
            
            // Construir la ruta completa del archivo
            $rutaArchivo = public_path($servicioDet->otrosconceptos3);
            
            \Log::info("Ruta del archivo:", [
                'ruta_relativa' => $servicioDet->otrosconceptos3,
                'ruta_completa' => $rutaArchivo
            ]);
            
            // Verificar que el archivo existe
            if (!file_exists($rutaArchivo)) {
                \Log::error("El archivo no existe en el servidor: {$rutaArchivo}");
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo no existe en el servidor'
                ], 404);
            }
            
            // Verificar que el archivo es legible
            if (!is_readable($rutaArchivo)) {
                \Log::error("El archivo no es legible: {$rutaArchivo}");
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo no es accesible'
                ], 403);
            }
            
            \Log::info("Archivo encontrado, redirigiendo a descarga");
            
            // Redirigir directamente al archivo
            return redirect('/' . $servicioDet->otrosconceptos3);
            
        } catch (\Exception $ex) {
            \Log::error("Error al descargar archivo: " . $ex->getMessage(), [
                'servicio_id' => $id_servicio_enc,
                'exception' => $ex
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar archivo: ' . $ex->getMessage()
            ], 500);
        }
    }


    public function obtenerProductosSuministro($id_servicio)
    {
        // Obtener el id de la licitación relacionado a este servicio
        $id_licitacion = DB::table('tbllicitacion_enc')->where('id_servicio', $id_servicio)->value('id');
        
        if (!$id_licitacion) {
            return collect([]);
        }
        $productos = DB::select('
            SELECT 
                p.id AS producto_id,
                p.nombre AS nombre_producto,
                p.descripcion AS descripcion_producto,
                um.nombre AS unidad_medida,
                ld.cantidad AS cantidad_solicitada,
                ld.observaciones AS observaciones_solicitud,
                pr.id AS proveedor_id,
                pr.nombre AS proveedor,
                COALESCE(lpp.costo, 0) AS precio_unitario,
                COALESCE(lpp.envio_producto, 0) AS costo_envio,
                COALESCE(lpp.margencost, 0) AS margencost,
                COALESCE(lpp.subtotal, 0) AS subtotal,
                COALESCE(lpp.total, 0) AS total,
                COALESCE(lpp.marca, \'\') AS marca,
                COALESCE(lpp.observaciones, \'\') AS t_entrega
            FROM tbllicitacion_det ld  
            INNER JOIN tblproductos p ON p.id = ld.producto_id
            INNER JOIN tblunidadesmedida um ON um.id = p.id_unidad_medida
            INNER JOIN tbllicitacion_proveedor lp ON lp.licitacion_id = ld.licitacion_id
            INNER JOIN tblprovedores pr ON pr.id = lp.proveedor_id
            LEFT JOIN tbllicitacionproducto_proveedor lpp ON (
                lpp.licitacion_id = ld.licitacion_id 
                AND lpp.producto_id = ld.producto_id 
                AND lpp.proveedor_id = lp.proveedor_id
            )
            WHERE ld.licitacion_id = ? and lpp.costo > 0
            ORDER BY p.nombre, pr.nombre;
        ', [$id_licitacion]);
        return collect($productos);
    }

    public function pverdetalle(int $id,string $tipo)
    {
        try {

            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();        
            $conceptos_servicios = $this->obtener_conceptos_servicios();          
            $conceptos_servicio = $this->obtenerConceptosServicio($id); // <-- Agregado
            $servicio_encxid = $this->obtnerservicio_encxid($id);  
            $servicio = ServiciosEnc::find($id);

            $ClientesActivos = $this->obtenerClientesActivos();
            $Ciudades = $this->obtenerciudadesAll();
            $EmpleadosActivos = $this->obtenerempleadosActivos();

            $Listadoproductos = $this->Listadoproductos();  
            $obtnerproductosxservicio = $this->obtnerproductosxservicio($id); 
            $HisotrialServicio = $this->HisotrialServicio($id);  
            $datos_servicio = $this->informacion_enc_datos_servicio($id);  

            // Si es suministro, obtener los productos con la consulta especial
            $productos_suministro = collect([]);
            if (strtolower($tipo) == 'suministro') {
                $productos_suministro = $this->obtenerProductosSuministro($id);
            }

            // Si es proyecto, obtener los conceptos de tconceptos_proyectos
            $conceptos_proyecto = collect([]);
            if (strtolower($tipo) == 'proyecto') {
                $conceptos_proyecto = DB::table('tconceptos_proyectos')
                    ->where('id_proyecto', $id)
                    ->orderBy('id', 'asc')
                    ->get();
            }

            // Obtener el monto_proyectado de tblservicios_det
            $monto_proyectado = DB::table('tblservicios_det')->where('id_servicio_enc', $id)->value('monto_proyectado');

            if (strtolower($tipo) == 'integracion' || strtolower($tipo) == 'integración') {
                $cotizacion_borrador = \DB::table('tblcotizacion_borrador_integracion')
                    ->where('id_serv_enc', $id)
                    ->orderBy('id', 'desc')
                    ->first();

                $cotizacion_integracion = null;
                if ($cotizacion_borrador && $cotizacion_borrador->cotizacion) {
                    $cotizacion_integracion = json_decode($cotizacion_borrador->cotizacion, true);
                }
            } else {
                $cotizacion_integracion = null;
            }

            if (strtolower($tipo) == 'suministro') {
                $cotizacion_borrador_suministro = \DB::table('tbl_cotizacion_borrador_suministros')
                    ->where('id_serv_enc', $id)
                    ->orderBy('id', 'desc')
                    ->first();
                $cotizacion_borrador_suministro_json = null;
                if ($cotizacion_borrador_suministro && $cotizacion_borrador_suministro->cotizacion) {
                    $cotizacion_borrador_suministro_json = json_decode($cotizacion_borrador_suministro->cotizacion, true);
                }
            } else {
                $cotizacion_borrador_suministro_json = null;
            }
            

        
            $totalespro = DB::select("select nombre,monto_proyectado,monto_real from tblservicios_det WHERE id_servicio_enc = ?  ;",[$id]);
            $totalespro = collect($totalespro);

            return view('Servicios.Ver.inicio', 
            compact('varpantallas', 'varsubmenus','tipo','id','conceptos_servicios','conceptos_servicio','servicio_encxid',
            'ClientesActivos','Ciudades','EmpleadosActivos','Listadoproductos','obtnerproductosxservicio',
            'HisotrialServicio','servicio','datos_servicio','productos_suministro','monto_proyectado',
            'cotizacion_integracion', 'cotizacion_borrador_suministro_json','conceptos_proyecto','totalespro'));
         
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function mGuardarCotizacion(int $id, Request $request)
    {
        try {
            $reviso = $request->get("reviso");
            $observaciones = $request->get("observaciones");
            $tipo = $request->get("tipo"); // Obtener el tipo de servicio
            
            // Guardar el JSON de productos si viene en la petición
            $productos_data_json = $request->input('productos_data');

            // Validar que productos_data_json no esté vacío, nulo o sea un array vacío
            if ($productos_data_json && !empty($productos_data_json)) {
                // Decodificar el JSON para verificar si es un array vacío
                $productos_data = json_decode($productos_data_json, true);
                //dd($productos_data);
                // Verificar que el JSON sea válido y no sea un array vacío
                if (json_last_error() === JSON_ERROR_NONE && is_array($productos_data) && !empty($productos_data)) {

                    // Determinar la tabla según el tipo de servicio
                    switch (strtolower($tipo)) {
                        case 'suministro':
                            \App\Models\CotizacionBorradorSuministro::create([
                                'id_serv_enc' => $id,
                                'cotizacion' => $productos_data_json,
                                'created_by' => auth()->user()->id,
                            ]);
                            break;
                            
                        case 'integracion':
                        case 'integración':
                            // Insertar en la tabla de integración
                            DB::table('tblcotizacion_borrador_integracion')->insert([
                                'id_serv_enc' => $id,
                                'cotizacion' => $productos_data_json,
                                'created_by' => auth()->user()->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            break;
                            
                        case 'proyecto':
                            // Para proyecto, usar la misma tabla que suministro o crear una específica
                            \App\Models\CotizacionBorradorSuministro::create([
                                'id_serv_enc' => $id,
                                'cotizacion' => $productos_data_json,
                                'created_by' => auth()->user()->id,
                            ]);
                            break;
                            
                        default:
                            // Tipo no reconocido, no insertar
                            \Log::warning("Tipo de servicio no reconocido para guardar cotización", [
                                'id_servicio' => $id,
                                'tipo' => $tipo,
                                'productos_data_json' => $productos_data_json
                            ]);
                            break;
                    }
                } else {
                    // Si el JSON está vacío, nulo o es un array vacío, no insertar
                    \Log::warning("No se insertó cotización - productos_data_json vacío o inválido", [
                        'id_servicio' => $id,
                        'tipo' => $tipo,
                        'productos_data_json' => $productos_data_json,
                        'decoded_data' => $productos_data ?? 'null'
                    ]);
                }
            } else {
                // Si no hay productos_data_json, no insertar
                \Log::warning("No se insertó cotización - productos_data_json no proporcionado", [
                    'id_servicio' => $id,
                    'tipo' => $tipo,
                    'productos_data_json' => $productos_data_json
                ]);
            }
            
            $lista = DB::select('update tblservicios_enc set otrosconceptos2 = ?, otrosconceptos3 = ? where id = ?;',[$reviso, $observaciones, $id]);
            if($lista > 0){
                return back()->with("success","¡Se guardaron los cambios correctamente!");
            }
         
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
           // dd($ex);
        }
    }

    public function mGuardarObservacion(int $id, Request $request)
    {
        try {

            $lista = DB::select('update tblservicios_productos set otrosconceptos1 = ? where id = ?;',[$request->get("observacion"),$id]);

            if($lista > 0){
                return back()->with("success","¡Se guardaron los cambios correctamente!");
            }
         
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function export_cot_pdf(int $id, string $tipo, Request $request)
    {
        try {

            $date = Carbon::now()->locale('es')->isoFormat('D \d\e MMMM \d\e\l Y');//28 de agosto del 2023
            $fecha = strtoupper($date); //convertir a mayuscula
            $nom_imprime=auth()->user()->name;

            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();        
            $conceptos_servicios = $this->obtener_conceptos_servicios();          
            $servicio_encxid = $this->obtnerservicio_encxid($id);  
            $servicio = ServiciosEnc::find($id);

            $ClientesActivos = $this->obtenerClientesActivos();
            $Ciudades = $this->obtenerciudadesAll();
            $EmpleadosActivos = $this->obtenerempleadosActivos();

            $Listadoproductos = $this->Listadoproductos();  
            $obtnerproductosxservicio = $this->obtnerproductosxservicio($id); 
            $HisotrialServicio = $this->HisotrialServicio($id);  
            $datos_servicio = $this->informacion_enc_datos_servicio($id); 

            // Para servicios de suministro, usar los datos enviados desde el frontend
            if (strtolower($tipo) == 'suministro') {
                $productos_data_json = $request->input('productos_data');
                
                \Log::info('Datos de productos recibidos:', ['productos_data' => $productos_data_json]);
                
                if ($productos_data_json) {
                    $productos_data = json_decode($productos_data_json, true);
                    \Log::info('Productos decodificados:', ['productos' => $productos_data]);
                    
                    // Convertir los datos a la estructura esperada por la vista
                    $productos_suministro = collect($productos_data)->map(function($producto) {
                        return (object) [
                            'producto_id' => $producto['id'],
                            'descripcion_producto' => $producto['descripcion'],
                            'proveedor' => $producto['proveedor'],
                            'total' => $producto['precio'],
                            'nombre_producto' => $producto['descripcion'],
                            'proveedor_id' => $producto['id'], // Usar el ID como proveedor_id temporal
                            'cantidad' => 1, // Cantidad por defecto
                            'unidad_medida' => 'PZA', // Unidad por defecto
                            'precio_unitario' => $producto['precio'],
                            'otrosconceptos1' => '' // Observación vacía por defecto
                        ];
                    });
                } else {
                    // Si no hay datos enviados, usar array vacío
                    $productos_suministro = collect([]);
                }
                
                \Log::info('Productos procesados para PDF:', ['count' => $productos_suministro->count()]);
            } elseif (strtolower($tipo) == 'integracion' || strtolower($tipo) == 'integración') {
                // Para servicios de integración, procesar los datos enviados desde el frontend
                $productos_data_json = $request->input('productos_data');
                
                \Log::info('Datos de integración recibidos:', ['productos_data' => $productos_data_json]);
                
                if ($productos_data_json) {
                    $productos_data = json_decode($productos_data_json, true);
                    \Log::info('Datos de integración decodificados:', ['productos' => $productos_data]);
                    //dd($productos_data);
                    // Convertir los datos a la estructura esperada por la vista
                    $productos_suministro = collect($productos_data)->map(function($producto) {
                        //dd($producto);
                        return (object) [
                            'partida' => $producto['partida'] ?? '',
                            'cantidad' => $producto['cantidad'] ?? 1,
                            'descripcion' => $producto['descripcion'] ?? '',
                            'unidad' => $producto['unidad'] ?? 'PZA',
                            'Precio u.' => $producto['Precio u.'] ?? 0,
                            'observacion' => $producto['observacion'] ?? '',
                            'total' => $producto['total'] ?? 0,
                            'unidad_medida' => $producto['unidad_medida'] ?? 'UNIDAD'
                        ];
                    });
                } else {
                    // Si no hay datos enviados, usar array vacío
                    $productos_suministro = collect([]);
                }
                
                \Log::info('Conceptos de integración procesados para PDF:', ['count' => $productos_suministro->count()]);
            } else {
                // Para otros tipos de servicio, mantener la lógica original
                $productos_suministro = collect([]);
            }
            
            // Consulta para obtener revisor y condiciones
            $revisor_condiciones = DB::selectOne("
                SELECT 
                    CONCAT(emp.primer_nombre, ' ', emp.segundo_nombre, ' ', emp.apellido_paterno, ' ', emp.apellido_materno) AS revisor,
                    senc.otrosconceptos3 AS condiciones
                FROM tblservicios_enc senc
                JOIN tblempleados emp ON senc.otrosconceptos2 = emp.id
                WHERE senc.id = ?
            ", [$id]);

            // Generar el PDF
            $pdf = PDF::loadView('Servicios.PDF.cotizacionNuevo', compact('varpantallas', 'varsubmenus','tipo',
            'id','conceptos_servicios','servicio_encxid',
            'ClientesActivos','Ciudades','EmpleadosActivos','Listadoproductos','obtnerproductosxservicio',
            'HisotrialServicio','servicio','datos_servicio','fecha','date','nom_imprime',
            'productos_suministro','revisor_condiciones'));
          
            
            $pdf->setPaper('letter', 'portrait');
            
            $nombreArchivo = 'COTIZACION_SERVICIO #' . $servicio->folio.'.pdf'; 
            
            return $pdf->stream($nombreArchivo);

        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function guardarConceptosProyectos(Request $request, $id)
    {
        try {
            $id_proyecto = $id;
            $conceptos = $request->get('concepto');
            $descripciones = $request->get('descripcion');
            $cantidades = $request->get('cantidad');
            $precios_unitarios = $request->get('precio_unitario');
            $totales = $request->get('total');
            $partidas = $request->get('partida');
            
            // Validar que todos los arrays tengan la misma longitud
            if (count($conceptos) !== count($descripciones) || 
                count($conceptos) !== count($cantidades) || 
                count($conceptos) !== count($precios_unitarios) || 
                count($conceptos) !== count($totales)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: Los datos de conceptos no están completos'
                ]);
            }
            
            // Calcular totales para servicios_det
            $total_conceptos = 0;
            $total_utilidad = 0;
            
            // Obtener datos del servicio
            $servicio = DB::table('tblservicios_enc')->where('id', $id)->first();
            if (!$servicio) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: Servicio no encontrado'
                ]);
            }
            
            // Calcular total de conceptos marcados para utilidad
            for ($i = 0; $i < count($conceptos); $i++) {
                if (!empty($conceptos[$i]) && !empty($totales[$i])) {
                    $total_conceptos += floatval($totales[$i]);
                    
                    // Solo incluir en utilidad si está marcado
                    if (isset($partidas[$i]) && $partidas[$i] == '1') {
                        $total_utilidad += floatval($totales[$i]);
                    }
                }
            }
            
            // Obtener datos adicionales para servicios_det
            $costo_suministros = DB::select('select SUM(p.precio_unitario * sp.cantidad_total) as costo_suministros from tblservicios_productos sp
                join tblproductos p on sp.id_producto = p.id where sp.id_servicio_enc = ?;', [$id]);
            $costo_suministros = $costo_suministros[0]->costo_suministros ?? 0;
            
            // Obtener total de salarios
            $total_salarios = DB::table('tblservicios_personal')
                ->where('id_servicio_enc', $id)
                ->sum('sueldo_semanal');
            
            // Calcular total de costos
            $total_costos = $total_conceptos + $total_salarios + $costo_suministros;
            
            // Calcular utilidad
            $porcentaje_utilidad = $servicio->utilidad_porcentaje ?? 0;
            $utilidad_calculada = $total_utilidad * ($porcentaje_utilidad / 100);
            
            // Actualizar o insertar en tblservicios_det
            $servicio_det_existe = DB::table('tblservicios_det')->where('id_servicio_enc', $id)->first();
            
            if ($servicio_det_existe) {
                // Actualizar registro existente
                DB::table('tblservicios_det')->where('id_servicio_enc', $id)->update([
                    'nombre' => 'Creación de Proyecto - Costos',
                    'monto_proyectado' => $total_costos + $utilidad_calculada,
                    'monto_real' => $total_costos,
                    'otrosconceptos1' => $utilidad_calculada,
                    'otrosconceptos2' => $costo_suministros,
                    'otrosconceptos3' => null, // Los archivos de ingeniería se guardan aquí
                    'utilidad_porcentaje' => $porcentaje_utilidad,
                    'monto_utilidad' => $utilidad_calculada,
                    'updated_at' => now(),
                    'updated_by' => auth()->user()->name ?? 'Sistema'
                ]);
            } else {
                // Insertar nuevo registro
                DB::table('tblservicios_det')->insert([
                    'id_servicio_enc' => $id,
                    'id_concepto' => 22, // Concepto por defecto para proyectos
                    'nombre' => 'Creación de Proyecto - Costos',
                    'monto_proyectado' => $total_costos + $utilidad_calculada,
                    'monto_real' => $total_costos,
                    'otrosconceptos1' => $utilidad_calculada,
                    'otrosconceptos2' => $costo_suministros,
                    'otrosconceptos3' => null, // Los archivos de ingeniería se guardan aquí
                    'utilidad_porcentaje' => $porcentaje_utilidad,
                    'monto_utilidad' => $utilidad_calculada,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'created_by' => auth()->user()->name ?? 'Sistema',
                    'updated_by' => auth()->user()->name ?? 'Sistema'
                ]);
            }
            
            // Eliminar conceptos existentes para este proyecto
            DB::table('tconceptos_proyectos')->where('id_proyecto', $id_proyecto)->delete();
            
            // Insertar nuevos conceptos en tconceptos_proyectos
            for ($i = 0; $i < count($conceptos); $i++) {
                if (!empty($conceptos[$i]) && !empty($totales[$i])) {
                    DB::table('tconceptos_proyectos')->insert([
                        'id_proyecto' => $id_proyecto,
                        'nombre_concepto' => $conceptos[$i],
                        'descripcion_concepto' => $descripciones[$i] ?? '',
                        'costo_concepto' => $totales[$i],
                        'otrosconceptos1' => $cantidades[$i] ?? null,
                        'otrosconceptos2' => $precios_unitarios[$i] ?? null,
                        'otrsoconceptos3' => isset($partidas[$i]) ? $partidas[$i] : '1',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
            
            // Actualizar nivel de progreso del servicio
            DB::table('tblservicios_enc')->where('id', $id)->update([
                'nivel_progreso' => 3,
                'updated_at' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Conceptos de proyecto guardados correctamente'
            ]);
            
        } catch (\Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar conceptos de proyecto: ' . $ex->getMessage()
            ]);
        }
    }

    public function finalizarServicio(Request $request, $id)
    {
        try {
            $servicio = ServiciosEnc::find($id);
            
            if (!$servicio) {
                return back()->with("warningBD", "Servicio no encontrado.");
            }
            
            // Validar que se haya proporcionado la fecha de entrega
            $request->validate([
                'fecha_entrega_realizada' => 'required|date',
            ], [
                'fecha_entrega_realizada.required' => 'La fecha de entrega realizada es obligatoria.',
                'fecha_entrega_realizada.date' => 'La fecha de entrega realizada debe ser una fecha válida.',
            ]);
            
            // Verificar que el servicio tenga licitación, orden de compra y recepción
            $tieneLicitacion = DB::table('tbllicitacion_enc')
                ->where('id_servicio', $id)
                ->exists();
            
            $tieneOrdenCompra = DB::table('tblordencompra_enc')
                ->where('id_servicio', $id)
                ->exists();
            
            $tieneRecepcion = DB::table('tblordencompra_enc as oc')
                ->join('tblordencompra_det as od', 'oc.id', '=', 'od.orden_compra_id')
                ->where('oc.id_servicio', $id)
                ->whereRaw('od.cantidad_recibida > 0')
                ->exists();
            
            if (!$tieneLicitacion || !$tieneOrdenCompra || !$tieneRecepcion) {
                return back()->with("warningBD", "No se puede marcar como entregado el servicio. Debe tener licitación, orden de compra y recepción completada.");
            }
            
            // Actualizar el estado a FINALIZADO y guardar la fecha de entrega realizada
            $servicio->estado = "FINALIZADO";
            $servicio->fecha_entrega_realizada = $request->fecha_entrega_realizada;
            $servicio->updated_by = auth()->user()->name;
            
            if ($servicio->save()) {
                return redirect()->route('servicios.index')->with("success", "¡Servicio marcado como entregado correctamente!");
            } else {
                return back()->with("warningBD", "Error al marcar el servicio como entregado.");
            }
            
        } catch (\Illuminate\Validation\ValidationException $ex) {
            return back()->withErrors($ex->errors())->withInput();
        } catch (\Exception $ex) {
            Log::error('Error al finalizar servicio: ' . $ex->getMessage());
            return back()->with("warningBD", "Error al marcar el servicio como entregado: " . $ex->getMessage());
        }
    }


    // COTIZACIONES
    public function ver_cotizacion(int $id,string $tipo)
    {
        try {

            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();        
            $servicio_encxid = $this->obtnerservicio_encxid($id)->first();  
            $servicio = ServiciosEnc::find($id);
            $EmpleadosActivos = $this->obtenerempleadosActivos();

            $HisotrialServicio = $this->HisotrialServicio($id);  
            $datos_servicio = $this->informacion_enc_datos_servicio($id)->first();  

            // Si es suministro, obtener los productos con la consulta especial
            $productos_suministro = collect([]);
            $productos_suministro = $this->obtenerProductosSuministro($id);

            $cotizacion =  collect(DB::select('select * from tblcotizaciones_enc where id_servicio = ?', [$id]));
            $cotizacion_det =  collect(DB::select('select tblcotizaciones_det.*,tblproductos.nombre as nombre_producto from 
            tblcotizaciones_det 
            inner join tblproductos on  tblproductos.id = tblcotizaciones_det.producto_id
            inner join tblcotizaciones_enc on  tblcotizaciones_enc.id = tblcotizaciones_det.id_cotizacion 
            where tblcotizaciones_enc.id_servicio  = ?', [$id]));
            

            return view('Servicios.Ver.cotizacion', 
            compact('varpantallas', 'varsubmenus','tipo','id','servicio_encxid',
            'EmpleadosActivos',
            'HisotrialServicio','servicio','datos_servicio','productos_suministro',
            'cotizacion','cotizacion_det'));
         
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function crear_cotizacion(int $id, Request $request)
    {
        try {
            $reviso = $request->get("reviso");
            $observaciones = $request->get("observaciones");
            $tipo = $request->get("tipo"); // Obtener el tipo de servicio
            
            // Validar datos requeridos
            if (!$reviso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe seleccionar un revisor.'
                ], 400);
            }

            // Obtener productos desde el request
            $productos_json = $request->input('productos');
            
            // Validar que el JSON sea válido
            if (is_string($productos_json)) {
                $productos = json_decode($productos_json, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error al decodificar el JSON de productos: ' . json_last_error_msg()
                    ], 400);
                }
            } else {
                $productos = $productos_json;
            }

            if (!$productos || empty($productos) || !is_array($productos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe agregar al menos un producto a la cotización.'
                ], 400);
            }

            // Obtener totales
            $subtotal = $request->input('subtotal', 0);
            $iva = $request->input('iva', 0);
            $total = $request->input('total', 0);

            DB::beginTransaction();

            try {
                // Siempre crear una nueva cotización
                $cotizacion = CotizacionesEnc::create([
                    'id_servicio' => $id,
                    'estado' => 'BORRADOR',
                    'fecha' => now(),
                    'nota' => $observaciones,
                    'reviso' => $reviso,
                    'subtotal' => $subtotal,
                    'iva' => $iva,
                    'total' => $total,
                    'created_by' => auth()->user()->name,
                    'updated_by' => auth()->user()->name,
                ]);

                $cotizacion_id = $cotizacion->id;

                // Guardar detalles de la cotización
                foreach ($productos as $producto) {
                    CotizacionesDet::create([
                        'id_cotizacion' => $cotizacion_id,
                        'pda' => $producto['pda'] ?? 1,
                        'cantidad' => $producto['cantidad'] ?? 1,
                        'producto_id' => $producto['producto_id'] ?? null,
                        'marca' => $producto['marca'] ?? null,
                        't_entrega' => $producto['t_entrega'] ?? null,
                        'p_unitario' => $producto['p_unitario'] ?? 0,
                        'total' => $producto['total'] ?? 0,
                    ]);
                }

                // Confirmar la transacción
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => '¡Cotización guardada correctamente!',
                    'cotizacion_id' => $cotizacion_id
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error("Error al guardar cotización: " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Error al guardar la cotización: ' . $e->getMessage()
                ], 500);
            }
         
        } catch (\Exception $ex) {
            \Log::error("Error en mGuardarCotizacion: " . $ex->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud: ' . $ex->getMessage()
            ], 500);
        }
    }

    public function exportar_cotizacion(int $id)
    {
        try {

            $date = Carbon::now()->locale('es')->isoFormat('D \d\e MMMM \d\e\l Y');//28 de agosto del 2023
            $fecha = strtoupper($date); //convertir a mayuscula
            $nom_imprime=auth()->user()->name;
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet(); 

            $CotizacionesEnc = CotizacionesEnc::find($id);
            $servicio = ServiciosEnc::find($CotizacionesEnc->id_servicio);

            $datos_servicio = $this->informacion_enc_datos_servicio($CotizacionesEnc->id_servicio)->first();

            $cotizacion =  collect(DB::select('select tblcotizaciones_enc.*, 
            CONCAT_WS (" ",tblempleados.primer_nombre,tblempleados.segundo_nombre,tblempleados.apellido_paterno,tblempleados.apellido_materno) as nombre_reviso 
            from tblcotizaciones_enc 
            inner join tblempleados on tblempleados.id = tblcotizaciones_enc.reviso
            where tblcotizaciones_enc.id = ?', [$id]))->first();

            $cotizacion_det =  collect(DB::select('select tblcotizaciones_det.*,tblproductos.nombre as nombre_producto from 
            tblcotizaciones_det 
            inner join tblproductos on  tblproductos.id = tblcotizaciones_det.producto_id
            inner join tblcotizaciones_enc on  tblcotizaciones_enc.id = tblcotizaciones_det.id_cotizacion 
            where tblcotizaciones_det.id_cotizacion  = ?', [$id]));
           

            // Generar el PDF
            $pdf = PDF::loadView('Servicios.PDF.cotizacionSum', compact('varpantallas', 'varsubmenus','datos_servicio','fecha',
            'date','nom_imprime','servicio',
            'cotizacion','cotizacion_det'));
          
            
            $pdf->setPaper('letter', 'portrait');
            
            $nombreArchivo = 'COTIZACION_SERVICIO #' . $servicio->folio.'.pdf'; 
            
            return $pdf->stream($nombreArchivo);

        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function cambiarEstadoCotizacion(int $id, Request $request)
    {
        try {
            $nuevoEstado = $request->input('nuevo_estado');
            $servicioId = $request->input('servicio_id');

            // Validar que el estado sea válido
            $estadosValidos = ['BORRADOR', 'REALIZADA', 'AUTORIZADA', 'CERRADA'];
            if (!in_array($nuevoEstado, $estadosValidos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estado no válido.'
                ], 400);
            }

            // Obtener la cotización
            $cotizacion = CotizacionesEnc::find($id);
            if (!$cotizacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cotización no encontrada.'
                ], 404);
            }

            // Validar que el servicio_id coincida
            if ($cotizacion->id_servicio != $servicioId) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cotización no pertenece a este servicio.'
                ], 400);
            }

            // Validación: Si el nuevo estado NO es BORRADOR, verificar que no exista otra cotización con ese estado
            if ($nuevoEstado != 'BORRADOR') {
                $cotizacionExistente = CotizacionesEnc::where('id_servicio', $servicioId)
                    ->where('estado', $nuevoEstado)
                    ->where('id', '!=', $id)
                    ->first();

                if ($cotizacionExistente) {
                    return response()->json([
                        'success' => false,
                        'message' => "Ya existe una cotización con estado '{$nuevoEstado}' para este servicio. Solo puede haber una cotización por estado (excepto BORRADOR)."
                    ], 400);
                }
            }

            // Si todo está bien, actualizar el estado
            $cotizacion->estado = $nuevoEstado;
            $cotizacion->updated_by = auth()->user()->name;
            $cotizacion->save();

            return response()->json([
                'success' => true,
                'message' => "Estado cambiado a '{$nuevoEstado}' correctamente."
            ]);

        } catch (\Exception $ex) {
            \Log::error("Error al cambiar estado de cotización: " . $ex->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado: ' . $ex->getMessage()
            ], 500);
        }
    }

    public function eliminarCotizacion(int $id, Request $request)
    {
        try {
            $servicioId = $request->input('servicio_id');

            // Obtener la cotización
            $cotizacion = CotizacionesEnc::find($id);
            if (!$cotizacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cotización no encontrada.'
                ], 404);
            }

            // Validar que el servicio_id coincida
            if ($cotizacion->id_servicio != $servicioId) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cotización no pertenece a este servicio.'
                ], 400);
            }

            // Validar que solo se puedan eliminar borradores
            if ($cotizacion->estado != 'BORRADOR') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden eliminar cotizaciones en estado BORRADOR.'
                ], 400);
            }

            DB::beginTransaction();

            try {
                // Eliminar los detalles primero (por la foreign key)
                CotizacionesDet::where('id_cotizacion', $id)->delete();

                // Eliminar la cotización
                $cotizacion->delete();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Borrador eliminado correctamente.'
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error("Error al eliminar cotización: " . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar la cotización: ' . $e->getMessage()
                ], 500);
            }

        } catch (\Exception $ex) {
            \Log::error("Error en eliminarCotizacion: " . $ex->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud: ' . $ex->getMessage()
            ], 500);
        }
    }

    //SEGUIMIENTO
    public function seguimiento_index()
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();        
            
            $pedidos =  collect(DB::select("select 
                    se.id AS id_serv_enc,
                    se.estado,
                    c.razon_social AS cliente,
                    CONCAT_WS(' ', ca.primer_nombre, ca.segundo_nombre, ca.apellido_paterno, ca.apellido_materno) AS comprador,
                    se.folio AS numero_pedido,
                    oc.tipo_moneda AS moneda,
                    ts.nombre as tipo,
                    null AS ref_sap,
                    se.fecha_inicio AS fecha_recepcion,
                    se.fecha_limite AS fecha_entrega,
                    se.nombre AS descripcion,
                    ce.total AS pedido,
                    GROUP_CONCAT(DISTINCT p.nombre ORDER BY p.nombre SEPARATOR ', ') AS proveedores,
                    GROUP_CONCAT(DISTINCT oc.folio ORDER BY oc.folio SEPARATOR ', ') AS oc_prov,
                    GROUP_CONCAT(DISTINCT oc.fecha_limite ORDER BY oc.fecha_limite SEPARATOR ', ') AS f_entrega_prov,
                    IFNULL(SUM(DISTINCT oc_det.cantidad * oc_det.costo), 0) AS pago_proveedor,
                    oc.fecha_tentativa_pago as fecha_tent_pago_prov,
                    null as fecha_pago_prov,
                    null AS monto_por_facturar,
                    null AS folio_factura,
                    null AS monto_facturado,
                    se.fecha_entrega_realizada AS fecha_entrega_realizada,
                    'ok' AS acepto_factura,
                    se.fecha_tentativa_pago as fecha_lim_pago_cli,
                    null as fecha_pago_cli,
                    ce.total as monto_pagado,
                    null as extras,
                    CONCAT_WS(' ',em.primer_nombre,em.segundo_nombre, em.apellido_paterno,em.apellido_materno) as vendedor,
                    null as comision,
                    se.porc_utilidad_vendedor
                FROM tblservicios_enc se
                INNER JOIN tblempleados em 
                    ON se.id_vendedor = em.id
                INNER JOIN tblclientes c 
                    ON se.id_cliente = c.id
                INNER JOIN tblclientes_atencion ca 
                    ON se.id_atencion = ca.id
                INNER JOIN tbltipos_servicios ts 
                    ON se.id_tiposervicio = ts.id
                LEFT JOIN tblcotizaciones_enc ce 
                    ON ce.id_servicio = se.id 
                    AND ce.estado = 'AUTORIZADA'
                LEFT JOIN tbllicitacion_enc le
                    ON le.id_servicio = se.id
                LEFT JOIN tbllicitacion_adjudicaciones la
                    ON la.licitacion_id = le.id
                LEFT JOIN tblprovedores p
                    ON p.id = la.proveedor_id
                LEFT JOIN tblordencompra_enc oc
                ON le.id = oc.referencia_licitacion_id AND oc.proveedor_id = p.id
                LEFT JOIN tblordencompra_det oc_det
                ON oc.id = oc_det.orden_compra_id
                GROUP BY 
                    se.id, 
                    c.razon_social,
                    comprador,
                    se.folio,
                    moneda,
                    ref_sap,
                    fecha_recepcion,
                    fecha_entrega,
                    descripcion,
                    pedido;
            "));
            

            return view('Seguimiento.index', 
            compact('varpantallas', 'varsubmenus', 'pedidos'));
         
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function actualizarPorcentajeComision(Request $request)
    {
        try {
            $request->validate([
                'id_serv_enc' => 'required|integer|exists:tblservicios_enc,id',
                'porc_utilidad_vendedor' => 'required|numeric|min:0|max:100',
                'comision' => 'nullable|numeric|min:0'
            ]);

            $id = $request->input('id_serv_enc');
            $porcentaje = $request->input('porc_utilidad_vendedor');
            $comision = $request->input('comision', 0);

            // Actualizar el porcentaje en la tabla tblservicios_enc
            DB::table('tblservicios_enc')
                ->where('id', $id)
                ->update([
                    'porc_utilidad_vendedor' => $porcentaje,
                    'updated_at' => now()
                ]);

            Log::info("Porcentaje de utilidad actualizado", [
                'id_serv_enc' => $id,
                'porc_utilidad_vendedor' => $porcentaje,
                'comision' => $comision
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Porcentaje de comisión actualizado correctamente',
                'data' => [
                    'id_serv_enc' => $id,
                    'porc_utilidad_vendedor' => $porcentaje,
                    'comision' => $comision
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $ex) {
            Log::error("Error al actualizar porcentaje de comisión: " . $ex->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el porcentaje: ' . $ex->getMessage()
            ], 500);
        }
    }
}
