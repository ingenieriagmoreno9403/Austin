<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\MenuTrait;
use App\Traits\AlmacenesTraits;
use App\Traits\ProductosTraits;
use App\Traits\InventariosTraits;
use App\Models\Almacenes;
use App\Models\existencias;
use App\Models\movimientos_inventarios;
use App\Models\logerrores;
use Carbon\Carbon;
use DB;
use App\Traits\SistemasTraits;

class InventarioController extends Controller
{

    use MenuTrait;
    use AlmacenesTraits;
    use ProductosTraits;
    use InventariosTraits;
    use SistemasTraits;

    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function inventario()
    {
        $varpantallas =  $this->Traermenuenc();
        $varsubmenus =   $this->Traermenudet();
        $Listadoalmacenes = $this->Listadoalmacenes();
        $permisos1 = $this->forpermisos('crear_almacenes');
        $permisos2 = $this->forpermisos('edit_almacen');
        $permisos3 = $this->forpermisos('ver_ubicacion');
  
        return view('Inventarios.inventario',compact('varpantallas','varsubmenus',
        'Listadoalmacenes','permisos1','permisos2','permisos3'));
    }
    public function ubicaciondet(Request $request,int $id)
    {
        $varpantallas =  $this->Traermenuenc();
        $varsubmenus =   $this->Traermenudet();
        $ubicaciones =$this->Listadoubicacionesxidalmacen($id);
        $listadoalmacenxid = $this->Listadoalmacenxid($id);
        $nombre_almacen ='';

        $permisos1 = $this->forpermisos('crear_ubicacion');
        $permisos2 = $this->forpermisos('edit_ubicacion');
        $permisos3 = $this->forpermisos('ver_productosxubi');

        foreach($listadoalmacenxid as $ltsalm)
        {
            $nombre_almacen = $ltsalm->folio_interno;
        }
        return view('Inventarios.ubicaciondetalle',compact('varpantallas',
        'varsubmenus','ubicaciones','nombre_almacen','id','permisos1','permisos2','permisos3'));
    }

    public function detalleubi(Request $request,int $idalm,int $idubi,string $nomalm)
    {
        $varpantallas =  $this->Traermenuenc();
        $varsubmenus =   $this->Traermenudet();
        $Listadoproductosxubicacion=$this->Listadoproductosxubicacion($idubi);
        $Listaubixid =$this->Listaubixid($idubi);
        $listapronuevosainsert = $this->Listadoproductosnoencontradosxubicacion($idubi);
        $nombre_ubicacion = '';
 
        foreach ($Listaubixid as $ltsubixid)
        {
            $nombre_ubicacion = $ltsubixid->folio_interno;
   
        }

        $permisos1 = $this->forpermisos('ingresa_productosxubi');
        $permisos2 = $this->forpermisos('recep_productosxubi');
        $permisos3 = $this->forpermisos('trans_productosxubi');

        return view('Inventarios.productosxubicacion',compact('varpantallas','varsubmenus',
        'Listadoproductosxubicacion','nombre_ubicacion','idalm','idubi','nomalm',
        'listapronuevosainsert','permisos1','permisos2','permisos3'));
        
    }

    

    public function ptransferenciapro(int $id)
    {
        
        $varpantallas =  $this->Traermenuenc();
        $varsubmenus =   $this->Traermenudet();
        $Listadoproductosxubicacion=$this->Listadoproductosxubicacion($id);
        return view('Inventarios.transferenciaproductos',compact('varpantallas','varsubmenus','Listadoproductosxubicacion')); 
    }

    public function mtransferenciaentrealmacenes(Request $request)
    {
        $idusuario=auth()->user()->id;
        $date = Carbon::now();
        $fecha = $date->format('Y-m-d');
        $tipo_movimiento = 1; //tipo movimiento tranferencia
        $estado_movimiento = 1; //producto enviado pero no recibido en el almacen
        $documento_ref = 'N/D'; //documento de referencia de la acccion en este caso no existe
        $variablesuccess1paraexistente=0;
        $variablesuccess2paraexistente=0;
        $variablesuccess3paraexistente=0;
        $variablesuccess1paraactualiza=0;
        $variablesuccess2paraactualiza=0;
        $variablesuccess3paraactualiza=0;
        $observaciones = '';
        $nombre_almacen_viejo='';
        $nombre_almacen_nuevo='';
        $nombre_ubi_vieja='';
        $nombre_ubi_nueva='';

        $idexistencia = $request->post('idex');
        $almacen_actual = $request->post('idalmaactual');
        $ubicacion_actual = $request->post('idubiactual');
        $cantidad_actual = $request->post('cantidadexistente');
      
        $id_producto = $request->post('id_producto');
    

        $nuevacantidad = 0;
        $nuevacantidasalmacenold = 0;

        $idalmacenatranferir = $request->post('id_almacen');

        $ubicacionactualatransferir = $request->post('id_ubicacion');
        $cantidadatranferir = $request->post('cantidad');

        $infoxpro = $this->Listadoproxid($id_producto);
        $nombre_producto = '';
        $sku = '';
        $codigo_barras = '';
        foreach($infoxpro as $ltsinfp)
        {
            $nombre_producto =  $ltsinfp->nombre;
            $sku = $ltsinfp->sku;
            $codigo_barras = $ltsinfp->codigo_barras;
        }

        $obtenerinfoalmaold = $this->Listadatosalmxid($almacen_actual);
        foreach($obtenerinfoalmaold as $ltsalm)
        {
            $nombre_almacen_viejo = $ltsalm->folio_interno;
        }
        $obtenerinfoalmanuevo = $this->Listadatosalmxid($idalmacenatranferir);
        foreach($obtenerinfoalmanuevo as $ltsalm1)
        {
            $nombre_almacen_nuevo = $ltsalm1->folio_interno;
        }

        $obtenerinfoubiold = $this->Listadatosubixid($ubicacion_actual);
        foreach($obtenerinfoubiold as $ltsub)
        {
            $nombre_ubi_vieja = $ltsub->folio_interno;
        }
        $obtenerinfoubinueva = $this->Listadatosubixid($ubicacionactualatransferir);
        foreach($obtenerinfoubinueva as $ltsub1)
        {
            $nombre_ubi_nueva = $ltsub1->folio_interno;
        }

        $observaciones = 'Se realizo la transferencia del producto '.$nombre_producto." con sku ".$sku." y codigo de barras ".$codigo_barras.' del almacen '.$nombre_almacen_viejo.' al almacen '.$nombre_almacen_nuevo.' de la ubicacion '.$nombre_ubi_vieja.' a '.$nombre_ubi_nueva;
        $errorhismov = 'Error al registrar el historial de movimiento del producto'.$nombre_producto." con sku ".$sku." y codigo de barras ".$codigo_barras.' del almacen '.$nombre_almacen_viejo.' al almacen '.$nombre_almacen_nuevo.' de la ubicacion '.$nombre_ubi_vieja.' a '.$nombre_ubi_nueva;
        $erroralregistrar = 'Error al registrar el de movimiento de transferencia del producto'.$nombre_producto." con sku ".$sku." y codigo de barras ".$codigo_barras.' del almacen '.$nombre_almacen_viejo.' al almacen '.$nombre_almacen_nuevo.' de la ubicacion '.$nombre_ubi_vieja.' a '.$nombre_ubi_nueva;
        $comentariotableproductoatranferir = $cantidadatranferir .'unidades del producto'.$id_producto.'estan en proceso de entrega a la ubicacion '.$ubicacionactualatransferir.' del almacen '.$idalmacenatranferir;
        //primero checamos que no se usen los mismos parametros para evitar duplicida de productos

        if($almacen_actual == $idalmacenatranferir && $ubicacion_actual == $ubicacionactualatransferir)
        {
            return back()->with("errorduplicidad","error de duplicidad de productos");
        }
        else
        {
            if($cantidadatranferir > $cantidad_actual)
            {
                return back()->with("errorexistencia","error de existencia");
            }
            else
            {
                //checar si ya existe el producto en esa ubicacion
                
                $existeproducto =$this->Buscaexistenciaporalmacenyubi($idalmacenatranferir,$ubicacionactualatransferir,$id_producto);
                
               //si no existe se crea la linea de existencia en esa ubicacion retorna 1
                if ($existeproducto == 'inserto')
                {
                    $insertanuevostocpro = new existencias();
                    $insertanuevostocpro->id_producto = $id_producto;
                    $insertanuevostocpro->id_almacen = $idalmacenatranferir;
                    $insertanuevostocpro->id_ubicacion = $ubicacionactualatransferir;
                    $insertanuevostocpro->cantidad_existente = 0;
                    $insertanuevostocpro->productos_arecibir = $cantidadatranferir;
                    $insertanuevostocpro->id_estado_movinv = 1;
                    if($insertanuevostocpro->save())
                    {
                        //disminuimos la existencia del almacen del cual salio la tranferencia
                        $nuevacantidasalmacenold = $cantidad_actual-$cantidadatranferir;
                        $disminuyeexistencia = $this->Diminuyestcok($idexistencia,$nuevacantidasalmacenold,$cantidadatranferir);
                        //insertar movimiento en tabla de movimientos de iventario con el estado del movimiento en enviado
                        $Registramovinventario = $this->Registramovinventario($id_producto,$idalmacenatranferir,$ubicacionactualatransferir,$tipo_movimiento,$estado_movimiento,$cantidadatranferir,$fecha,$documento_ref,$observaciones,$fecha,$idusuario);
                        if($Registramovinventario == "inserto")
                        {
                            //insertamos en la tabla de productos en tranferencia para no perder detalle del movimiento y poder dar entrada mediante otra accion
                            $Insertaproenmovimiento = $this->Insertaproenmovimiento($id_producto,$ubicacionactualatransferir,$cantidadatranferir,$comentariotableproductoatranferir,$fecha);

                            if($Insertaproenmovimiento == "inserto")
                            {
                                $variablesuccess1paraexistente=1;
                                $variablesuccess2paraexistente=1;
                                $variablesuccess3paraexistente=1;
                            }
                            else
                            {

                            }
                           

                        }
                        else
                        {
                            //se quedan variables en cero
                        }

                        if($variablesuccess1paraexistente == 1 and $variablesuccess2paraexistente ==1)
                        {
                       
                            return back()->with("succeshistmov","success");
                        }
                        else
                        {
                            
                            $Registrarerrorhismov = $this->Registrarerror($errorhismov,$fecha);
                            if($Registrarerrorhismov == "inserto")
                            {
                                return back()->with("errorhistmov","error");
                            }
                            else
                            {
                                return back()->with("errorallog","error");
                            }
                           
                        }
                    }
                    else
                    {
                        $Registrarerroralreg = $this->Registrarerror($erroralregistrar,$fecha);
                        if($Registrarerroralreg == "inserto")
                        {
                            return back()->with("erroralreg","error");
                        }
                        else
                        {
                            return back()->with("errorallog","error");
                        }
                           
                    }
                    // si existe se le suma la cantidad al inventario   
                }
                else
                    {
                        $cantiparaubnueva= 0;
                        $nuevacantidadproductoenubinueva = $this->listaexisproxubi($id_producto,$ubicacionactualatransferir);
                        foreach($nuevacantidadproductoenubinueva as $ltscantubnu)
                        {
                            $cantiparaubnueva = $ltscantubnu->cantidad_existente = $ltscantubnu->cantidad_existente;
                        }

                      
                        //actualizamos stock del producto a donde va la tranferencia
                        $nuevacantidad = $cantiparaubnueva+$cantidadatranferir;
                      

                        $idexistenciarecepciontranfernecia = $this->obteneridexis($id_producto,$ubicacionactualatransferir);
                        $actualizastock = existencias::find($idexistenciarecepciontranfernecia);
                        $actualizastock->productos_arecibir = $cantidadatranferir;
                        $actualizastock->id_estado_movinv = 1;

                        if($actualizastock->save())
                        {
                            //disminuimos la existencia del del producto de donde viene la tranferencia
                            $nuevacantidasalmacenold = $cantidad_actual-$cantidadatranferir;
                            
                            $disminuyeexistencia = $this->Diminuyestcok($idexistencia,$nuevacantidasalmacenold,$cantidadatranferir);
                            //insertar movimiento en tabla de movimientos de iventario con el estado del movimiento en enviado
                            $Registramovinventario = $this->Registramovinventario($id_producto,$idalmacenatranferir,$ubicacionactualatransferir,$tipo_movimiento,$estado_movimiento,$cantidadatranferir,$fecha,$documento_ref,$observaciones,$fecha,$idusuario);
                            if($Registramovinventario =='inserto')
                            {
                                //insertamos en la tabla de productos en tranferencia para no perder detalle del movimiento y poder dar entrada mediante otra accion
                                $Insertaproenmovimiento = $this->Insertaproenmovimiento($id_producto,$ubicacionactualatransferir,$cantidadatranferir,$comentariotableproductoatranferir,$fecha);

                                if($Insertaproenmovimiento == 'inserto')
                                {
                                    $variablesuccess1paraactualiza=1;
                                    $variablesuccess2paraactualiza=1;
                                    $variablesuccess3paraactualiza=1;
                                }
                                else
                                {

                                }
                            }
                            else
                            {
                                //se quedan variables en 0
                            }

                           // return $variablesuccess1paraactualiza.''.$variablesuccess2paraactualiza;
                            if($variablesuccess1paraactualiza == 1 and $variablesuccess2paraactualiza ==1)
                            {

                                echo "entro al success";
                                return back()->with("succeshistmov","success");
                            }
                            else
                            {
                                $Registrarerrorhismov = $this->Registrarerror($errorhismov,$fecha);
                                if($Registrarerrorhismov == "inserto")
                                {
                                    echo 1;
                                    return back()->with("errorhistmov","error");
                                }
                                else
                                {
                                    echo 2;
                                    return back()->with("errorallog","error");
                                }
                              
                            }
                        }
                        else
                        {
                            $Registrarerrorhismov3 = $this->Registrarerror($errorhismov,$fecha);

                            if($Registrarerrorhismov3 == "inserto")
                            {
                                //echo '1.1';
                                return back()->with("errorhistmov","error");
                            }
                            else
                            {
                                //echo '1.2';
                                return back()->with("errorallog","error");
                            }
                        }
                }  
            }
        }
    }  
    
    public function mrecepciontranferencia(int $idexistencia,float $cantidadacutal,float $cantidadtranferencia)
    {
            $nombreusuario=auth()->user()->name;
            $idusuario = auth()->user()->id;
            $date = Carbon::now();
            $fecha = $date->format('Y-m-d');
            
            $Insertarececpionproducto = $this->Insertarececpionproducto($idexistencia,$cantidadacutal,$cantidadtranferencia);

            if($Insertarececpionproducto == 'inserto')
            {
                    $obtenerlistaxexistencia = DB::select("select id, id_producto, id_almacen, id_ubicacion, cantidad_existente, cantidad_reservada, id_estado_movinv, productos_arecibir from tblexistencias where id = ?",[$idexistencia]);
                    foreach($obtenerlistaxexistencia as $ltsexi)
                    {
                        $infoxpro = $this->Listadoproxid($ltsexi->id_producto);
                        $nombre_producto = '';
                        foreach($infoxpro as $ltsinfp)
                        {
                            $nombre_producto =  $ltsinfp->nombre;
                           
                        }
                        $Registramovinventario = DB::insert("insert into tblmovimientos_inventario (id_producto, id_almacen, id_ubicacion, id_tipo_movimiento, cantidad_producto_movimiento, fecha_movimiento, documento_referencia, observaciones,id_estado_movinv, usuario_movimiento, created_at) values (?,?,?,?,?,?,?,?,?,?,?)",
                        [
                            $ltsexi->id_producto,
                            $ltsexi->id_almacen,
                            $ltsexi->id_ubicacion,
                            3,
                            $cantidadtranferencia,
                            $fecha,
                            'N/d',
                            'Se recepciono la cantidad de '.$cantidadtranferencia.' del producto #'.$nombre_producto. ' en la ubicacion '.$ltsexi->id_ubicacion.' al almacen'.'por el usuario '.$nombreusuario,
                            2,
                            $idusuario,
                            $fecha
                        ]);
                    }
                               
                return back()->with("succeshistmov","success");
            }
            else
            {
                 return back()->with("error","error");
            }
    } 

    public function historial_alm()
    {
        $varpantallas =  $this->Traermenuenc();
        $varsubmenus =   $this->Traermenudet();
        $minv = $this->Reporte_movimientos_inventarios();
  
        return view('Inventarios.historial_alm',compact('varpantallas','varsubmenus','minv'));
    }

    public function entradamanualexistente(int $idubi, int $idpro,Request $request)
    {
        $date = Carbon::now();
        $fecha = $date->format('Y-m-d');
        $nombreusuario=auth()->user()->name;
        $idusuario =auth()->user()->id;
        $cantidadaactual = 0;
        $cantidadnueva =  $request->post('cantidad');
        $nombreimpiutdoc = 'entrada'.$idpro;
        $rutadoc =  $request->post($nombreimpiutdoc);
        $datosactuales = $this->Buscaexistenciaporubipro($idubi,$idpro);
        $idalmacen = 0;
        $nombre_ubicacion="";
        $nombre_almacen = "";
        $nombre_producto = "";
        foreach($datosactuales as $ltsact)
        {
            $cantidadacutal = $ltsact->cantidad_existente;
            $idalmacen = $ltsact->id_almacen;
            $nombre_almacen = $ltsact->nombre_almacen;
            $nombre_ubicacion = $ltsact->nombreubi;
            $nombre_producto = $ltsact->nombre;
        }
        $cantidadnueva = $cantidadacutal+$cantidadnueva;
        $tipoarchivo = "entrada_".$nombre_producto;

       

        $ingresamercancia = DB::update("update tblexistencias set cantidad_existente = ? where id_ubicacion = ? and id_producto = ?",[$cantidadnueva,$idubi,$idpro]);


        if($ingresamercancia > 0)
        {

        

            $GuardarArchivopdfgeneral=$this->GuardarArchivopdfgeneral($nombreimpiutdoc,$nombreusuario, $tipoarchivo, $request,$fecha);

            
            $Registramovinventario = DB::insert("insert into tblmovimientos_inventario (id_producto, id_almacen, id_ubicacion, id_tipo_movimiento, cantidad_producto_movimiento, fecha_movimiento, documento_referencia, observaciones,id_estado_movinv, usuario_movimiento, created_at) values (?,?,?,?,?,?,?,?,?,?,?)",
            [
                $idpro,
                $idalmacen,
                $idubi,
                4,
                $cantidadnueva,
                $fecha,
                $GuardarArchivopdfgeneral,
                'Se Ingreso manualmente la cantidad de '.$cantidadnueva.' del producto #'.$nombre_producto. ' en la ubicacion '.$nombre_ubicacion.' al almacen '.$nombre_almacen.' por el usuario '.$idusuario,
                2,
                $idusuario,
                $fecha
            ]);
            return back()->with("success","bien");
        }
        else
        {
            $errorhismov = "Se registro un error al insertar el hostorial de movimiento del ingreso de ".$cantidadnueva. "a la ubicación ".$nombre_ubicacion;
            $Registrarerrorhismov3 = $this->Registrarerror($errorhismov,$fecha);

            if($Registrarerrorhismov3 == "inserto")
            {
                //echo '1.1';
                return back()->with("errorhistmov","error");
            }
            else
            {
                //echo '1.2';
                return back()->with("errorallog","error");
            }
        }

    }

    public function entradamanualnuevopro(Request $request)
    {
        $date = Carbon::now();
        $fecha = $date->format('Y-m-d');
        $idusuario =auth()->user()->id;
        $id_producto =  $request->post('id_producto');
        $id_almacen =  $request->post('idalmaactual');
        $id_ubicacion =  $request->post('idubiactual');
        $cantidad =  $request->post('cantidad');
        $nombreimpiutdoc = 'entrada';
        $nombreusuario=auth()->user()->name;
        $tipoarchivo = "entrada_".$id_producto;
        
    
        $GuardarArchivopdfgeneral=$this->GuardarArchivopdfgeneral($nombreimpiutdoc,$nombreusuario, $tipoarchivo, $request,$fecha);

        if($GuardarArchivopdfgeneral != "error"){

            $insertanewproaubi = new existencias();
            $insertanewproaubi->id_producto = $id_producto;
            $insertanewproaubi->id_almacen = $id_almacen;
            $insertanewproaubi->id_ubicacion = $id_ubicacion;
            $insertanewproaubi->cantidad_existente = $cantidad;
            $insertanewproaubi->id_estado_movinv = 2;

            if($insertanewproaubi->save())
            {
                $Registramovinventario = DB::insert("insert into tblmovimientos_inventario (id_producto, id_almacen, id_ubicacion, id_tipo_movimiento, cantidad_producto_movimiento, fecha_movimiento, documento_referencia, observaciones,id_estado_movinv, usuario_movimiento, created_at) values (?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $id_producto,
                    $id_almacen,
                    $id_ubicacion,
                    4,
                    $cantidad,
                    $fecha,
                    $GuardarArchivopdfgeneral,
                    'Se Ingreso manualmente la cantidad de '.$cantidad.' del producto #'.$id_producto. ' en la ubicacion '.$id_ubicacion.' al almacen '.$id_almacen.' por el usuario '.$idusuario,
                    2,
                    $idusuario,
                    $fecha
                ]);

                return back()->with("success","bien");
            }else{
                return back()->with("error","mal");
            }

        }else{
            return back()->with("error","mal");
        }
       
    }

    public function buscarProductosNuevos(Request $request)
    {
        $idubi = (int) $request->get('idubi', 0);
        $q = trim((string) $request->get('q', ''));

        if ($idubi <= 0) {
            return response()->json([]);
        }

        $sub = DB::table('tblexistencias')->select('id_producto')->where('id_ubicacion', $idubi);

        $query = DB::table('tblproductos as p')
            ->select('p.id', 'p.nombre', 'p.sku', 'p.codigo_barras')
            ->whereNotIn('p.id', $sub);

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('p.nombre', 'like', "%$q%")
                  ->orWhere('p.sku', 'like', "%$q%")
                  ->orWhere('p.codigo_barras', 'like', "%$q%")
                  ->orWhere('p.descripcion', 'like', "%$q%");
            });
        }

        $rows = $query->orderBy('p.nombre', 'asc')->limit(50)->get();

        return response()->json($rows);
    }
}
