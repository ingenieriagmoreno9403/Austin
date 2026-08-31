<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\CancelacionesTraits;
use App\Traits\MenuTrait;
use App\Traits\SistemasTraits;
use App\Traits\DatosimpleTraits;
use Carbon\Carbon;
use App\Models\historial_cuentas;
use App\Models\historial_cajas;
use App\Models\Cajas;
use App\Models\cuentas;
use App\Models\cancelaciones;
use DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;

class CancelacionesController extends Controller
{
    use CancelacionesTraits;
    use MenuTrait;
    use DatosimpleTraits;
    use SistemasTraits;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function cancelardis(string $tipo,int $id){
        
        $idusuario = auth()->user()->id;
        $varpantallas =  $this->Traermenuenc();
        $varsubmenus =   $this->Traermenudet();
        $distribuidor =  $this->obtenerdis($id);
        $coordinadores = $this->obtenercoordinadoresemp();
        $varTipoCancelacion = $this->obtenerTipoCancelacion();
        return view('vales.Cancelaciones.cancelacion',compact('varpantallas','varsubmenus','tipo','id','distribuidor','coordinadores','varTipoCancelacion'));
    }

    public function realizarcancelaciondis(int $id, Request $request){

        $idusuario=auth()->user()->id;
        $checarcancelacion = $this->obtenelistacancelaciones($id);
        $name=auth()->user()->name;

        if($checarcancelacion->isEmpty()){
            $date = Carbon::now();
            $fecha = $date->format('Y-m-d');
            $valera =  $this->obtenervalera($id);
            $cordi =$request->get("cordinador");
            $pagos = $this->checarsitienpagosdis($id);
            $tipo = "Distribuidor"; // == Dis

            try{
                if($request->get("motivo") ==1){

                    $Rutacarpeta = "Expedientes/Cancelaciones/Distribuidores/".$id;

                    if(!file_exists(public_path($Rutacarpeta))){
                        File::makeDirectory($Rutacarpeta,0777,true,true);
                    }
        
                    $file_defuncion = $request->file("defuncion");
                    $Nombre_defuncion = "defuncion_".$id.".".$file_defuncion->guessExtension();
                    $ruta_defuncion = public_path($Rutacarpeta."/".$Nombre_defuncion);  
                    copy($file_defuncion, $ruta_defuncion);
        
                    $file_identificacion = $request->file("identificacion");
                    $Nombre_identificacion = "identificacion_".$id.".".$file_identificacion->guessExtension();
                    $ruta_identificacion = public_path($Rutacarpeta."/".$Nombre_identificacion);  
                    copy($file_identificacion, $ruta_identificacion);
                    
                    $insertarcancelacion = new cancelaciones();
                    $insertarcancelacion->id_dis_cli=$request->get("id");
                    $insertarcancelacion->tipo_cancelacion=$tipo;
                    $insertarcancelacion->Comentarios=$request->get("comentario");
                    $insertarcancelacion->Rutadoc1=$Nombre_identificacion;
                    $insertarcancelacion->Rutadoc2=$Nombre_defuncion;
                    $insertarcancelacion->Rutadoc3="";
                    $insertarcancelacion->created_at=$idusuario;
                    $insertarcancelacion->created_by=$fecha;
                    $insertarcancelacion->updated_by="";
                    $insertarcancelacion->Encargado=$cordi;
                    $insertarcancelacion->Tipo=$request->get("motivo");
                    $insertarcancelacion->created_by=$name;
                    $insertarcancelacion->save();
        
                    if($insertarcancelacion->save()){
                        //Primero cambiamos el estatus del distribuidor
                        $Actutalizaestadodis = DB::update('update tbldistribuidores set idstatus = 15, id_responsable = ?, updated_by = ? where id = ?;', [$cordi,$name,$id]);
                    
                        //cancelamos la realacion valera distribuidor
                        $Actutalizaestadodisval = DB::update('update tbldistribuidor_valeras set status = "F", id_coordinador = ?, updated_by = ? WHERE iddistribuidor = ?;', [$cordi,$name,$id]);
        
                        //cancelamos la valera 
                        foreach($valera as $listav)
                        {$Actutalizaestadodisval = DB::update('update tblvaleras set status_valera = "C", updated_by = ? WHERE id = ?;', [$name,$listav->idvalera]);}
        
                        return back()->with("success","se fino al distribuidor");
                    }else{
                        return back()->with("warning","Ocurrio un Error");
                    }
                }else{
                    $insertarcancelacion = new cancelaciones();
                    $insertarcancelacion->id_dis_cli=$request->get("id");
                    $insertarcancelacion->tipo_cancelacion=$tipo;
                    $insertarcancelacion->Comentarios=$request->get("comentario");
                    $insertarcancelacion->Rutadoc1="";
                    $insertarcancelacion->Rutadoc2="";
                    $insertarcancelacion->Rutadoc3="";
                    $insertarcancelacion->created_at=$idusuario;
                    $insertarcancelacion->created_by=$fecha;
                    $insertarcancelacion->updated_by="";
                    $insertarcancelacion->Encargado=$cordi;
                    $insertarcancelacion->Tipo=$request->get("motivo");
                    $insertarcancelacion->created_by=$name;
                    $insertarcancelacion->save();

                    //Primero cambiamos el estatus del distribuidor
                    $Actutalizaestadodis = DB::update('update tbldistribuidores set idstatus = 13,id_responsable = ?, updated_by = ? WHERE id = ?;', [$cordi,$name,$id]);

                    //cancelamos la realacion valera distribuidor
                    $Actutalizaestadodisval = DB::update('update tbldistribuidor_valeras set status = "S", id_coordinador = ?, updated_by = ? WHERE iddistribuidor = ?;', [$cordi,$name,$id]);
        
                    //cancelamos la valera 
                    foreach($valera as $listav)
                    {$Actutalizaestadovalera = DB::update('update tblvaleras set status_valera = "S", updated_by = ? WHERE id = ?;', [$name,$listav->idvalera]);}

                    return back()->with("success","cancelacion efectuada correctamente");
                }
            } catch(\Illuminate\Database\QueryException $ex){return back()->with("warningBD","No se logro"); }   
        
        }else{
            return back()->with("errorExiste","Ocurrio un Error, ya hay una cancelación");
        }
    }

    public function realizaractivaciondis(int $id, Request $request){
        $idusuario=auth()->user()->id;
        $name=auth()->user()->name;
        $date = Carbon::now();
        $fecha = $date->format('Y-m-d');
        $valera =  $this->obtenervalera($id);
        $cordi =$request->get("cordinador");
        $tipo = "Distribuidor";

        //eliminamos la cancelacionn aplicada a ese distribuidor
        $Borrartbl3 =  DB::select('delete from tblcancelaciones where id_dis_cli = ? and tipo_cancelacion = ?', [$id,$tipo]);

        //cambiamos el status del distribuidor
        $Actutalizaestadodis = DB::update('update tbldistribuidores set idstatus = 12, id_responsable = ?, updated_by = ? where id = ?;', [$cordi,$name,$id]);
                    
        //cambiamos el estado de la realacion valera_distribuidor
        $Actutalizaestadodisval = DB::update('update tbldistribuidor_valeras set status = "A", id_coordinador = ?, updated_by = ? WHERE iddistribuidor = ?;', [$cordi,$name,$id]);

        //cambiamos el estado de la valera 
        foreach($valera as $listav)
        {$Actutalizaestadodisval = DB::update('update tblvaleras set status_valera = "A", updated_by = ? WHERE id = ?;', [$name,$listav->idvalera]);}

        return back()->with("success","Se activo exitosamente");
    }

    public function cancelacionesclientes(int $id, Request $request){

        $idempleado = auth()->user()->idempleado;
        $date = Carbon::now();
        $fecha = $date->format('Y-m-d');
        $motivocancelacion = $request->get("motivo");
        $Rutadoc1 = $request->get("defuncion");
        $Rutadoc2 = $request->get("ine");
        $idprestamocliente = "";
        $activacionODP = 0;

       // try{
            //cancelacion por defuncion
            if($motivocancelacion == 1){

                //OBTENER ID DEL ULTIMO PRESTAMO FINADO DEL CLIENTE
                $IDprestamo = $this->obteneridprestamoclienteActivo($id);
                foreach($IDprestamo as $presta){
                    $idprestamocliente = $presta->id;
                }

                $Rutacarpeta = "Expedientes/Cancelaciones/Clientes/".$id;

                if(!file_exists(public_path($Rutacarpeta))){
                    File::makeDirectory($Rutacarpeta,0777,true,true);
                }

                $file_defuncion = $request->file("defuncion");
                $Nombre_defuncion = "defuncion_".$id.".".$file_defuncion->guessExtension();
                $ruta_defuncion = public_path($Rutacarpeta."/".$Nombre_defuncion);  
                copy($file_defuncion, $ruta_defuncion);

                $file_identificacion = $request->file("ine");
                $Nombre_identificacion = "identificacion_".$id.".".$file_identificacion->guessExtension();
                $ruta_identificacion = public_path($Rutacarpeta."/".$Nombre_identificacion);  
                copy($file_identificacion, $ruta_identificacion);
            
                $insertarcancelacion = new cancelaciones();
                $insertarcancelacion->id_dis_cli=$id;
                $insertarcancelacion->tipo_cancelacion="Cliente";
                $insertarcancelacion->Comentarios=$request->get("comentario");
                $insertarcancelacion->Rutadoc1=$Nombre_defuncion; //$request->get("acta_defuncion");
                $insertarcancelacion->Rutadoc2=$Nombre_identificacion;//$request->get("identificacion_finado");
                $insertarcancelacion->Rutadoc3="";
                $insertarcancelacion->Encargado=0;
                $insertarcancelacion->Tipo=2;
                $insertarcancelacion->created_by = auth()->user()->name;
                $insertarcancelacion->save();


                //actualizamos el estado del cliente
                $Actutalizaestadocliente = DB::update('update tblclientes_vales set status = "F" WHERE id =  ?;', [$id]);
                
                //Actualizamos status prestamo  del cliente a finado
                $Actualizaprestamocliente = DB::update('update tblprestamos_valesenc set status = "F" WHERE id = ?;',[$idprestamocliente]);

                
                
                //OBTENER PRESTAMOS CON ESE IDPRESTAMO
                $idprestamosdet = $this->obtenerdetalleprestamosafinar($idprestamocliente);
                foreach($idprestamosdet as $idpre){
                    if($idpre->status != "P"){
                       $Actualizadetpetcli = DB::update('update tblprestamos_valesdet set status = "CC", saldo = 0, updated_by = ? where id = ?;',[$idempleado,$idpre->id]);
                    }
                }
                // echo $idprestamocliente;
                // echo "El cliente se Dio de baja por el motivo de defuncion";
                return back()->with("success","se fino al cliente");
            }
            
            //cancelacion sin caje
            elseif($motivocancelacion == 2){
                $si_pago = $this->validapagosclienteActivo($id);
                if($si_pago->isEmpty()){
                    //obtenemos datos del prestamo
                    $datoscliente = $this->obtenerdatosprestamocli($id);
                    foreach($datoscliente as $listacli){
                        $id_odp = $listacli->id_odp;
                        $referencia_odp = $listacli->referencia_odp;
                        $activacionODP = $listacli->otrosconceptos1;
                        $monto_vale = $listacli->monto_vale;
                    }
                    //OBTENER ID DEL ULTIMO PRESTAMO FINADO DEL CLIENTE
                    $IDprestamo = $this->obteneridprestamoclienteActivo($id);
                    foreach($IDprestamo as $presta){
                        $idprestamocliente = $presta->id;
                    }

                    if($activacionODP > 0){
                        //si esta activada la ODP no se puede editar el archivo de txt en datos generales
                        return back()->with("ODPActivada","No es posible cambiar datos para corrección de txt");
                    }else{
                        return redirect()->route('getEditarCliente',['idcli'=>"{$id}",'idpre'=>"{$idprestamocliente}"])->with("successPrestamo","¡Se puede actualizar!");
                    }
                }else{
                    return back()->with("existePago","Ya existe un pago y no se puede cancelar");
                }
            }
           
            //cancelacion error de captura
            elseif($motivocancelacion == 3){
                $si_pago = $this->validapagosclienteActivo($id);
                if($si_pago->isEmpty()){
                    $referencia_odp="";
                    $monto_vale=0;
                    $id_cuenta="";
                    $saldo_actual=0;
                    $saldo_nuevo =0;
                    $comisionxcancel = 0;
                    $id_odp = 0;
                    
                    $insertarcancelacion = new cancelaciones();
                    $insertarcancelacion->id_dis_cli = $id;
                    $insertarcancelacion->tipo_cancelacion="Cliente";
                    $insertarcancelacion->Comentarios=$request->get("comentario");
                    $insertarcancelacion->Rutadoc1="Null";
                    $insertarcancelacion->Rutadoc2="Null";
                    $insertarcancelacion->Rutadoc3="Null";
                    $insertarcancelacion->created_by = auth()->user()->name;
                    $insertarcancelacion->Encargado=0;
                    $insertarcancelacion->Tipo=2;
                    $insertarcancelacion->save();

                
                    // esto es cuando ya se subio el archivo al banco
                    //cancelamos el cliente
                    $Actutalizaestadocliente = DB::update('update tblclientes_vales set status = "C" WHERE id =  ?;', [$id]);

                    //cancelamos el prestamo
                    $Actualizaprestamocliente = DB::update('update tblprestamos_valesenc set status = "CC" WHERE idcliente = ?;',[$id]);

                    //no entro a esta parte
                    $IDprestamo = $this->obteneridprestamocliente($id);
                    foreach($IDprestamo as $presta){
                        $idprestamocliente = $presta->id;
                    }
                    
                    //no entro a esta parte
                    $Actualizadetpetcli = DB::update('update tblprestamos_valesdet set status = "CC", saldo = 0, updated_by = ? where idprestamo_vales = ? AND STATUS <> "P";',[$idempleado,$idprestamocliente]);
                    
                    $folio = 0;
                    //obtenemos datos del prestamo
                    $datoscliente = $this->obtenerdatosprestamocli($id);
                    foreach($datoscliente as $listacli){
                        $id_odp = $listacli->id_odp;
                        $referencia_odp = $listacli->referencia_odp;
                        $activacionODP = $listacli->otrosconceptos1;
                        $monto_vale = $listacli->monto_vale;
                        $folio = $listacli->folio_vale;
                        if($id_odp == 6){$id_cuenta = $listacli->id_caja;
                        }else{$id_cuenta = $listacli->id_cuenta;}
                    }

                    if($id_odp == 6){
                        //obtenersaldo_caja
                        $varcajas = $this->obtenerCajasxId($id_cuenta);
                        foreach ($varcajas as $cajas){$saldo_actual = $cajas->saldo_actual;$nombreCaja = $cajas->nombre;}
                        $saldo_nuevo = $monto_vale + $saldo_actual;

                        //insertamos movimiento en las cajas
                        $historialcuent = new  historial_cajas();
                        $historialcuent->id_caja = $id_cuenta;
                        $historialcuent->id_empleado = $idempleado;
                        $historialcuent->estado = "A";
                        $historialcuent->tipo_movimiento = "INGRESO";
                        $historialcuent->concepto = "INGRESO POR CANCELACION ODP DEL PRESTAMO #".$idprestamocliente." AL CLIENTE #".$id." EN VALES, CON FOLIO DE VALE #".$folio;
                        $historialcuent->descripcion = $request->get("comentario");
                        $historialcuent->responsable = "CLIENTE #".$id;
                        $historialcuent->ingreso = $monto_vale;
                        $historialcuent->egreso = 0;
                        $historialcuent->saldo =  $saldo_nuevo;
                        $historialcuent->numero_referencia = $folio;
                        $historialcuent->tipo_referencia = "folio vale";
                        $historialcuent->numero_poliza = 0;
                        $historialcuent->fecha = $fecha;
                        $historialcuent->created_by = auth()->user()->name;
                        $historialcuent->save();

                
                        if($historialcuent->save()){
                            //regresamos el dinero a la caja y se completa la primera accion
                            $Actualizacuentapaso1 = DB::update('update tblcajas set saldo_actual = ? where id = ?;',[$saldo_nuevo,$id_cuenta]);

                            //Asigamos numero de poliza al primer movimiento
                            $movcaja1 =$this->obtenerultimomovcaja();
                            foreach($movcaja1 as $caj1){$movId1 = $caj1->id;}

                            $poliza1 = historial_cajas::find($movId1);
                            $poliza1->numero_poliza = "CJ00".$movId1;
                            $poliza1->updated_by = auth()->user()->name;
                            $poliza1->save();
        
                            return back()->with("success","se Dio de baja por el motivo de no canje");
                            // echo "El cliente se Dio de baja por el motivo de no canje";
                        }else{
                            return back()->with("wairning","Error al insertar");
                        }
                    }else{
                        //obtenersaldo_cuenta
                        $varobtenercuentas =$this->obtenercuentasPrincipales($id_cuenta);
                        foreach($varobtenercuentas as $varobtenercuenta){$saldo_actual = $varobtenercuenta->saldo_actual ;$nomCuenta = $varobtenercuenta->descripcion;}
                        $saldo_nuevo = $monto_vale + $saldo_actual;

                        //insertamos movimiento en las cuentas
                        $historialcuent = new  historial_cuentas();
                        $historialcuent->id_cuenta = $id_cuenta;
                        $historialcuent->id_empleado = $idempleado;
                        $historialcuent->estado = "A";
                        $historialcuent->tipo_movimiento = "INGRESO";
                        $historialcuent->concepto =  "INGRESO POR CANCELACION ODP DEL PRESTAMO #".$idprestamocliente." AL CLIENTE #".$id." EN VALES, CON FOLIO DE VALE #".$folio;
                        $historialcuent->descripcion = $request->get("comentario");
                        $historialcuent->responsable = "CLIENTE #".$id;
                        $historialcuent->ingreso = $monto_vale;
                        $historialcuent->egreso = 0;
                        $historialcuent->saldo =  $saldo_nuevo;
                        $historialcuent->numero_referencia = $folio;
                        $historialcuent->tipo_referencia = "folio vale";
                        $historialcuent->numero_poliza = 0;
                        $historialcuent->fecha = $fecha;
                        $historialcuent->created_by = auth()->user()->name;
                        $historialcuent->save();

                        //regresamos el dinero a la cuenta
                        $Actualizacuentapaso1 = DB::update('update tblcuentas set saldo_actual = ? where id = ?;',[$saldo_nuevo,$id_cuenta]);

                        //Asigamos numero de poliza al primer movimiento
                        $movcuenta1 = $this->obtenerultimomovcuenta();
                        foreach($movcuenta1 as $cue1){$movId1 = $cue1->id;}

                        $poliza1 = historial_cuentas::find($movId1);
                        $poliza1->numero_poliza = "CU00".$movId1;
                        $poliza1->updated_by = auth()->user()->name;
                        $poliza1->save();

                        //verificamos si la ODP fue activada, si es así se aplican los gastos de cancelacion
                        if($activacionODP > 0){
                            //sacamos el 15 mas iva de la cuenta
                            $comisionxcancel = 15+(15*.16);
                            $saldomenoscomision = ($saldo_nuevo - $comisionxcancel);

                            //se aplica el gasto del movimiento de cancelacion
                            $historialcuent = new  historial_cuentas();
                            $historialcuent->id_cuenta= $id_cuenta;
                            $historialcuent->id_empleado = $idempleado;
                            $historialcuent->estado = "A";
                            $historialcuent->tipo_movimiento = "GASTO";
                            $historialcuent->concepto = " COMISION ODP"; 
                            $historialcuent->descripcion = "GASTO COBRADO POR CANCELACION ODP, DEL PRESTAMO #".$idprestamocliente." AL CLIENTE #".$id." EN VALES, CON FOLIO DE VALE".$folio;
                            $historialcuent->responsable = "CLIENTE #".$id;
                            $historialcuent->total_iva = 15*.16;
                            $historialcuent->ingreso = 0;
                            $historialcuent->egreso = $comisionxcancel;
                            $historialcuent->saldo = $saldomenoscomision;
                            $historialcuent->numero_referencia = 28;//ID DE HONORARIOS POR CANCELACION
                            $historialcuent->tipo_referencia = "gastos";
                            $historialcuent->numero_poliza = 0;
                            $historialcuent->fecha = $fecha;
                            $historialcuent->created_by = auth()->user()->name;
                            $historialcuent->save();

                            if($historialcuent->save()){
                                //Restamos a la cuenta la comision por cancelacion
                                $Actualizacuentapaso1 = DB::update('update tblcuentas set saldo_actual = ? where id = ?;',[$saldomenoscomision,$id_cuenta]);

                                //Asigamos numero de poliza al segunado movimiento
                                $movcuenta2 =$this->obtenerultimomovcuenta();
                                foreach($movcuenta2 as $cue2){$movId2 = $cue2->id;}
            
                                $poliza2 = historial_cuentas::find($movId2);
                                $poliza2->numero_poliza = "CU00".$movId2;
                                $poliza2->updated_by = auth()->user()->name;
                                $poliza2->save();

                                return back()->with("successODPsi","cancelacion sin canje, ODP activa cancelada");
                            }else{
                                return back()->with("wairning","Error al insertar");
                            }
                        }else{
                            return back()->with("successODPno","cancelacion sin canje exitosa, ODP no activa cancelada");
                        }
                    }
                }else{
                    return back()->with("existePago","Ya existe un pago y no se puede cancelar");
                }
            }
            else{
                return back()->with("warningMotivo","No existe aun ese motivo");
            }

        //} catch(\Illuminate\Database\QueryException $ex){return back()->with("warningBD","No se logro"); }   
    }
    
}
