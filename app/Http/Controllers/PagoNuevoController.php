<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vistas;
use App\Models\usuario_pantallas;
use App\Models\conyuges;
use App\Models\documentos;
use App\Models\avales;
use App\Models\referencias;
use App\Models\valeras;
use App\Models\distribuidores;
use App\Traits\MenuTrait;
use App\Models\pagosenc;
use App\Traits\DatosimpleTraits;
use App\Traits\PagoTrait;
use App\Models\tipo_distribuidor;
use App\Models\distribuidores_valeras;
use App\Models\historial;
use  App\Models\mensajes;
use \Milon\Barcode\DNS1D;
use \Milon\Barcode\DNS2D; 
use App\Models\referencias_pago;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\File;
use ZipArchive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use App\Traits\SistemasTraits;
use App\Models\historial_cuentas;
use App\Models\historial_cajas;
use App\Models\cuentas;
use App\Models\Cajas;
use App\Models\pagodet;
use App\Models\pagosclienc;
use App\Models\pagosclidet;
use App\Models\cliente_vales;
use App\Models\prestamos_valesdet;
use App\Models\prestamos_valesenc;
use App\Models\cancelacionespagos;
use App\Models\pagocontrados;
use App\Models\excedentes;
use DateTime;



class PagoNuevoController extends Controller
{

  use DatosimpleTraits;
  use PagoTrait;
  use SistemasTraits;

  public function __construct()
  {
      $this->middleware('auth');
  }

  public function Aplicarpagos_referenciados(Request $request)
  {
    //DATOS PRINCIPALES DEL FORMULARIO
    $date = Carbon::now();
    $fecha = $date->format('Y-m-d');
    $user = auth()->user()->name;
    $numerodedistribuidores = 0;
    $pago_incompleto = 0;
    $merece_comision = "";
    $saldo_distribuidor = 0;
    $tipo = "CUENTA";
    $cuenta = $request->get('cuenta');
    $pCompleto = 0;
    $pExcedente = 0;
    $pIncompleto = 0;
    $pConciliado = 0;
    $pExcedenteextra = 0;
    $tipos = "";
    $tipo_pago = "";

    //ARREGLOS DE LA TABLA
    $referencia=$request->get('referencia');
    $distribuidor = $request->get('distribuidor');
    $nombre = $request->get('nombre');
    $total = $request->get('total');
    $fecha_pago =$request->get('fecha_pago');
    $estado = $request->get('estado');

    if($referencia > 0)
    {
      for($i=0; $i <= count($referencia)-1; $i++)
      {
        $result[$i] = array(
          'referencia' => $referencia[$i],
          'nombre' => $nombre[$i],
          'iddistribuidor' => $distribuidor[$i],
          'total'  => $total[$i],
          'fecha_pago'=>$fecha_pago[$i]
        );

        // LEER LAS LINEAS DE LA TABLA
        $varnombre = $result[$i]['nombre'];
        $vardistribuidor = $result[$i]['iddistribuidor'];
        $varreferencia =$result[$i]['referencia'];
        $montopagado =$result[$i]['total'];
        $varfecha_pago =$result[$i]['fecha_pago'];
        $montosinproteccion_saldo =0;
        $montomascomision = 0;
        $montoexcedente = 0; 
        $pagos_incpletos=0;
        $obtenerlineaspagoenc = $this->obtener_relaciones_a_pagar($vardistribuidor);
        $relaciones_a_pagar = count($obtenerlineaspagoenc);

        // BUSCAMOS SI EXISTE UNA RELACION POR PAGAR
        if(!$obtenerlineaspagoenc->isEmpty())
        {
          foreach($obtenerlineaspagoenc as $linea)
          {     
            $salex = 0;
            $montosincomision = 0;
            $saldo_pagar = $linea->saldo_pagar;
            $estado = $linea->estado;
            $idpagoencabezado = $linea->id;
            $capital_regresado = 0;
            $fecha_relacion = $linea->fecha_relacion;
            $abonado = $linea->otrosconceptos1;
            $saldo_atrasado = $linea->otrosconceptos2;
            $proteccion_saldo =$linea->proteccion_saldo;
            $montoxtransaccion  = $linea->costo_transaccion;
            $fechacorteini = $linea->fecha_corte_inicio;
            $fechacortefin = $linea->fecha_corte_final;
            $monto_total = $linea->monto_total;
            $statuspresdet="";
            $saldo_atrasadocli = 0;
            $saldorestantecliente = 0;
            $comisioncalcul = 0;
            $montoreal = 0;
            $merece_comision = 0;
            $saldodis = 0;
            $clientesxdis = $this->obtenerdetallepagoClientes($vardistribuidor,$fecha_relacion);
            $totalcliente = count($clientesxdis);

            // REVISAR SI CUENTAS CON SALDO EXCEDENTE
            $saldoexcedente = $this->saldoexdistribuidor($vardistribuidor);
            if($saldoexcedente->isEmpty())
            {
              echo "<br> No hay excedente | ";
              $salex = 0;
              // QUTAMOS LOS 2 CONCEPTOS QUE SE INCLUYEN EN EL PAGO
              $montosinproteccion_saldo = (($montopagado)-$montoxtransaccion-$proteccion_saldo);
            }
            else
            {
              foreach($saldoexcedente as $s)
              {
                echo "Si hay excedente | ";
                // SUMAMOS EXDENTE E INCATIVAMOS PARA USARLO
                $salex = $s->monto;
                if($salex > 0)
                {$actualizastatus = DB::update("update tblexcedentedistribuidor set estado = 'I' where id = ?",[$s->id]);}
              }
              // QUTAMOS LOS 2 CONCEPTOS QUE SE INCLUYEN EN EL PAGO
              $montosinproteccion_saldo = (($montopagado)-$montoxtransaccion-$proteccion_saldo)+$salex;
            }

            //APLICAMOS LOS VALORES DEL DINERO QUE MANEJAREMOS
            $abonado = $monto_total+$montosinproteccion_saldo;
            $saldo = $montosinproteccion_saldo;
  
            // VALIDACION DE MERECER COMISION
            $comisiones = $this->validacomision($linea->fecha_relacion,$varfecha_pago,$montosinproteccion_saldo,$saldo_pagar,$vardistribuidor,$fechacortefin,$tipo_pago);    
            if($comisiones->isEmpty())
            {
              echo "No tiene bonificación | ";
              // AÑADIMOS A IMPORTE DE DINERO EL PAGO DE LA COMISION
              $comisioncalcul = 0;
              $montoreal = $montosinproteccion_saldo;
              $merece_comision ='no';
            }
            else
            {  
              echo "Si tiene bonificación | ";
              // AÑADIMOS A IMPORTE DE DINERO EL PAGO DE LA COMISION
              $comisioncalcul =$comisiones["comision"];
              $montoreal = $comisiones["montototal"];
              $merece_comision = $comisiones["merececomision"];
              $capital_regresado = $comisiones["capital_regresado"];
              echo "Capital Regresado :".$capital_regresado." | ";

              $actualizabandera = DB::update("update tblpagos_enc set capital_regresado = ? where id = ?;",[$capital_regresado, $idpagoencabezado]);
            }
            echo "<br>"."Monto Real : ".$montoreal." Monto Sin PS : ".$montosinproteccion_saldo." Calculo de Comision : ".$comisioncalcul."<br>";
        
            // PAGO COMPLETO
            if($montoreal == $saldo_pagar )
            {
                echo "<br>"."Pago Completo"."<br>";
                //ACTUALIZAMOS QUE ATRASO DE DV QUEDE EN 0 Y EL COSTO_TRANSACCION QUEDARA EN 0
                $UpdAt = DB::select('update tblpagos_enc set otrosconceptos2 = "0", costo_transaccion = 16  WHERE id = ?;', [$idpagoencabezado]);
          
                //ACTUALIZAMOS QUE ATRASO DE CLIENTES QUEDE EN 0 
                // foreach($clientesxdis as $clientes)
                // {
                //   $UpdAtr = DB::select('update tblprestamos_valesdet set saldo = "0" WHERE id = ?;', [$clientes->id]);
                // }

                // FUNCION PARA APLICAR EL PAGO
                $funcion = $this->insertarPagos($vardistribuidor, $idpagoencabezado, $montosinproteccion_saldo, $saldo_pagar,
                0, $varfecha_pago, $fecha_relacion, $montoreal, $comisioncalcul, 0, 
                0,"I","I", 0, $cuenta, $tipo,$totalcliente, $fecha, $user,0,$tipos,$proteccion_saldo,$montoxtransaccion,$varreferencia);

                //ACTUALIZAMOS QUE ATRASO DE CLIENTES QUEDE EN 0 
                foreach($clientesxdis as $clientes)
                {
                  echo "<br> SI ACTUALIZO: ".$clientes->idcliente;
                  $UpdAtr = DB::select('update tblprestamos_valesdet set saldo = "0" WHERE id = ?;', [$clientes->id]);
                }

                // CERRAR FUNCION SI YA NO TIENES SALDO PARA APLICAR
                $montosinproteccion_saldo = $montosinproteccion_saldo-$saldo_pagar;
                if($montosinproteccion_saldo < 0)
                {
                  $montosinproteccion_saldo = 0;
                }

                $pCompleto = 1;
            }

            // PAGO COMPLETO CON EXCEDENTE
            if($montoreal > $saldo_pagar )
            {
                echo "<br>"."Pago Completo más Excedente"."<br>";
                
                //CHECAMOS CUANTO EXCEDENTE TENEMOS PARA GUARDALO 
                $montoexcedente = $montoreal-$saldo_pagar;
                //MONTO REAL = LO QUE RECIBIMOS, MAS EL EXCEDENTE, MAS COMISION
                $montoreal = $montoreal-$montoexcedente;
                $montosinproteccion_saldo = $montosinproteccion_saldo-$montoexcedente;
                echo "<br> | "."Excedente : ".$montoexcedente." Monto Real: ".$montoreal." Monto Sin PS: ".$montosinproteccion_saldo."<br>";

                
                //APLICAR PAGO COMPLETO
                //NO GENERAMOS ATRASOS, ASÍ QUE ACTUALIZAMOS EL CAMPO
                $UpdAtr = DB::select('update tblpagos_enc set otrosconceptos2 = "0" WHERE id = ?;', [$idpagoencabezado]);

                //MANDAMOS TODOS LOS CALCULOS PARA INSERTAR EL PAGO DE LA RELACION
                $funcion = $this->insertarPagos($vardistribuidor, $idpagoencabezado, $montosinproteccion_saldo, $saldo_pagar,
                0, $varfecha_pago,$fecha_relacion,$montoreal,  $comisioncalcul, 0, 
                0,"I","I", $montoexcedente-$salex, $cuenta, $tipo,$totalcliente, $fecha, $user,0, $tipos,$proteccion_saldo,$montoxtransaccion,$varreferencia,$montopagado);

                //ACTUALIZAMOS QUE ATRASO DE CLIENTES QUEDE EN 0 
                foreach($clientesxdis as $clientes)
                {
                  $UpdAtr = DB::select('update tblprestamos_valesdet set saldo = "0" WHERE id = ?;', [$clientes->id]);
                }

                //SALIMOS DE FUNCION
                $montosinproteccion_saldo = $montosinproteccion_saldo-$saldo_pagar;
                if($montosinproteccion_saldo < 0)
                {
                  $montosinproteccion_saldo = 0;
                }

                $relacionesfaltantes = $this->relacionesfaltantes($vardistribuidor);

                //SALIMOS DE FUNCION
                if($montoexcedente < 50 || $relacionesfaltantes->isEmpty()){
                    //GUARDAR EXCEDENTE
                    $insertaexcedente = new excedentes();
                    $insertaexcedente->id_distribuidor = $vardistribuidor;
                    $insertaexcedente->fecha_pago =$varfecha_pago;
                    $insertaexcedente->monto=$montoexcedente;
                    $insertaexcedente->estado="A";
                    $insertaexcedente->created_at=$fecha;
                    $insertaexcedente->save();
                    
                    $montosinproteccion_saldo = $montosinproteccion_saldo-$saldo_pagar;
                    $montoexcedente = 0;
                  echo "<br> no aplico para seguir pagondo con excedente";
                }else{
                  $montoexcedente = $montoexcedente;
                  echo "<br> si aplico para seguir pagondo con excedente";
                }
                
                $pExcedente = 1;
            }

            // PAGO INCOMPLETO
            if($montoreal < $saldo_pagar && $montoreal > 0 && $montosinproteccion_saldo > 0)
            {
              $finalcomision = Carbon::parse($fecha_relacion)->addDays(5);
              $fechaf = $finalcomision->format('Y-m-d');
              $monto_conciliado = 0;
              
              $ultima_relacion = "";
              $ultimarela = DB::select("select tblpagos_enc.fecha_relacion from tblpagos_enc  where id_distribuidor = ? order by fecha_relacion DESC limit 1;",[$vardistribuidor]);
              foreach($ultimarela as $key){
                  $ultima_relacion = $key->fecha_relacion;
              }
              $ultima_relacion = Carbon::parse($ultima_relacion);
              $ultima_relacion = $ultima_relacion->format('Y-m-d');
          
              //si esta entre fecha de comisiones se le da la opcion de un pago conciliado
              //y juntarlos al final para completar el pago
              if($varfecha_pago >= $fechacorteini && $varfecha_pago <= $fechaf  &&  $ultima_relacion == $fecha_relacion)
              {
                $pagosyacontrados = $this->obtenerpagosconcetrandosporquincenaydis($vardistribuidor,$fecha_relacion,$montoreal);
                $validaconcentrados = $this->obtenerpagosconcixdisrela($vardistribuidor,$fecha_relacion);
               
                // if($pagosyacontrados->isEmpty())
                // {
                      echo "<br>"."Pago Conciliado"."<br>";
                      $insertapagoconcentrado = new pagocontrados();
                      $insertapagoconcentrado->id_distirbuidor = $vardistribuidor;
                      $insertapagoconcentrado->saldo_pagar_real = $saldo_pagar;
                      // if($montoexcedente > 0)
                      // {
                      //   // echo "entro";
                      //   $monto_conciliado = $montoexcedente;
                      // }
                      // else
                      // {
                      //   // echo "entro al 2";
                      //   $monto_conciliado = $montoreal;
                      // }     

                      $monto_conciliado = $montoreal;

                      if(!$validaconcentrados->isEmpty()){
                        $monto_conciliado = $montoreal + $proteccion_saldo;
                      }

                      $insertapagoconcentrado->intento_pago = $monto_conciliado;
                      $insertapagoconcentrado->fecha_relacion = $fecha_relacion;
                      $insertapagoconcentrado->fecha_intento_pago = $varfecha_pago;
                      $insertapagoconcentrado->status = "A";
                      $insertapagoconcentrado->referencia_pago = $varreferencia;
                      $insertapagoconcentrado->idcuenta=$cuenta;
                      $insertapagoconcentrado->tipocuenta = $tipo;
                      $insertapagoconcentrado->created_at = $fecha;
                      $insertapagoconcentrado->monto_recibido = $montopagado;
                      
                      if($insertapagoconcentrado->save())
                      {
                        $pagos_incpletos = $pagos_incpletos+1;
                      }
                      //afectamos las cuentas
                      $tipomov="PAGO";
                      $concepto = "FECHA DE CAPTURA DEL PAGO ".$varfecha_pago;
                      $descripcion= "PAGO CONCILIADO APLICADO DE DISTRIBUIDOR #".$vardistribuidor." A LA RELACION DEL ".$fecha_relacion;
                      $afectarhistorialcuentas = $this->afectarhistorialcuentaspago($tipo,$cuenta,$montopagado,$comisioncalcul,$varfecha_pago,$vardistribuidor,$idpagoencabezado,$tipomov,$concepto,$descripcion);
                      $pConciliado = 1;
                // }
              }
              // se aplica el pago normal sin comision 
              else
              {
                echo "<br>"."Pago Incompleto sin comision"."<br>";
                $pagorealizado = $montoreal;
                $saldo_atrasado = $saldo_pagar - $montoreal;
                echo "<br> Atraso Generado:".$saldo_atrasado;

                if($tipo == "CAJA"){
                  $updateenc = DB::update('update tblpagos_enc set estado = "P", status_atraso = "I", fecha_pago = ?, monto_total = ?, otrosconceptos1 = ?, otrosconceptos2 = ?, tipo_cuenta = ?, caja = ?  where id = ?;', [$varfecha_pago,$pagorealizado,$montoreal,$saldo_atrasado,$tipo,$cuenta,$idpagoencabezado]);
                }else{
                  $updateenc = DB::update('update tblpagos_enc set estado = "P", status_atraso = "I", fecha_pago = ?, monto_total = ?, otrosconceptos1 = ?, otrosconceptos2 = ?, tipo_cuenta = ?, cuenta = ?  where id = ?;', [$varfecha_pago,$pagorealizado,$montoreal,$saldo_atrasado,$tipo,$cuenta,$idpagoencabezado]);
                }
                
                if($updateenc > 0)
                {
                  
                    $UpdAtr = DB::select('update tblpagos_enc set otrosconceptos2 = "0" WHERE id = ?;', [$idpagoencabezado]);

                    //return $montosinproteccion_saldo;
                    $funcion = $this->insertarPagos($vardistribuidor, $idpagoencabezado, $montosinproteccion_saldo, $saldo_pagar,
                    0, $varfecha_pago,$fecha_relacion,$montoreal,  $comisioncalcul, $saldo_atrasado, 
                    0,"I","I", 0, $cuenta, $tipo,$totalcliente, $fecha, $user,0,$tipos,$proteccion_saldo,$montoxtransaccion,$varreferencia,$montopagado);

                    $pagocli = 0;
                    //ACTUALIZAR ESTADOS DE ABONOS COMPLETOS CLIENTES
                    foreach($clientesxdis as $clientes)
                    {

                      if($clientes->status == "P" && $clientes->saldo == 0)
                      {
                        $UpdAtr1 = DB::select('update tblprestamos_valesdet  pres_det set  pres_det.status = ?, pres_det.saldo = ? WHERE pres_det.id = ? ;', ["P",0,$clientes->id]);
                      }
                      else
                      {
                        $abonadocliente = 0;
                        $obtenerabonado =DB::select("select pago_total - saldo as abonado from tblprestamos_valesdet where id = ?;",[$clientes->id]);

                        foreach($obtenerabonado as $abn)
                        {
                          $abonadocliente = $abn->abonado;
                        }

                        if($clientes->saldo == $saldo)
                        {
                          $saldo=$clientes->pago_total;
                        }
                      
                        if($clientes->status == "N" || $clientes->saldo > 0 )
                        {

                          if ($clientes->saldo > 0){
                              $pagocli = $clientes->saldo;
                          }else{
                              $pagocli = $clientes->pago_total;
                          }
                          
                          //actualizamos el pago por cliente
                          if($saldo >= $clientes->pago_total)
                            {
                                $saldo_atrasadocli = 0;
                                $statuspresdet = "P";    
                                $saldo = 0;
                            }
                            //pago incompleto cliente
                      
                          else
                          { 
                              
                                $saldo_atrasadocli = $pagocli - $saldo;
                                $statuspresdet = "P"; 
                                $saldo = 0;

                          }
                        }
                  
                
                          $UpdAtr1 = DB::select('update tblprestamos_valesdet  pres_det set  pres_det.status = ?, pres_det.saldo = ? WHERE pres_det.id = ? ;', [$statuspresdet,$saldo_atrasadocli,$clientes->id]);

                          if($saldo <=0)
                          {
                            if($montoexcedente > 0)
                            {
                              $saldofaltanteendet=0;
                              $obtenersaldopendiente=DB::SELECT("select c.id,c.saldo from tblclientes_vales a
                                inner join tblprestamos_valesenc b on  a.id =b.idcliente
                                inner join tblprestamos_valesdet c on c.idprestamo_vales = b.id
                                where c.fecha_pago = ? and a.iddistribuidor= ? and c.saldo > 0 and b.status = 'A';",[$fecha_relacion,$vardistribuidor]);
                                foreach($obtenersaldopendiente as $s)
                                {
                                $saldofaltanteendet = $s->saldo;
                                $idspre = $s->id;
                                }

                                if($saldofaltanteendet == $montoexcedente)
                                {
                                $UpdAtr2 = DB::select('update tblprestamos_valesdet pres_det set pres_det.saldo = 0 WHERE pres_det.id = ? ;',[$s->id]);
                                return $UpdAtr2;
                                if($UpdAtr2 > 0)
                                {
                                  echo "se actualizo la linea".$s->id;

                                }
                                }

                            }
                            break;
                            
                          }
                      }
            
                    }
                  
                    if($montosinproteccion_saldo < 0)
                    {
                      $montosinproteccion_saldo = 0;
                    }
                }
                else 
                {
                  echo "error no actualizo";
                }

                if($montosinproteccion_saldo = 0)
                {

                }
                else
                {
                  $ultimop = "";
                  //nueva liena a crear con su atraso
                  $pagosenc = new pagosenc();
                  $pagosenc->id_distribuidor=$vardistribuidor;
                  $pagosenc->saldo_pagar=$saldo_atrasado;
                  $pagosenc->comision=0;
                  $pagosenc->interes=0;
                  $pagosenc->monto_total=0;
                  $pagosenc->estado_generado="u";
                  $pagosenc->estado="N";
                  $pagosenc->fecha_pago="Null";
                  $pagosenc->fecha_relacion= $fecha_relacion;
                  $pagosenc->fecha_corte_inicio=$fechacorteini;
                  $pagosenc->fecha_corte_final=$fechacortefin;
                  $pagosenc->otrosconceptos1=0;
                  $pagosenc->otrosconceptos2=0;
                  $pagosenc->status_atraso="A";
                  $pagosenc->otrosconceptos3=0;

                  // if($fecha_relacion >= "2024-11-30"){
                    $pagosenc->costo_transaccion=16;
                  // }else{
                  //   $pagosenc->costo_transaccion=0;
                  // }

                  $pagosenc->created_at=$fecha;
                  $pagosenc->created_by = auth()->user()->name;
                  if($pagosenc->save())
                  {
                    $ultimopagoenc = $this->obtenerultimopagoenc($vardistribuidor);
                    foreach($ultimopagoenc as $ul)
                    {
                      $ultimop = $ul->id;
                    }
                  }
                }
                //si aplica el pago bien o lo actualiza bien creamos la nueva linea   
                $pIncompleto = 1;
                echo "<br>  CON 1";
                $actualizaPorce = DB::update("update tblpagos_enc set flag2porciento = 1 where id_distribuidor = ? and fecha_relacion <= ?;",[$vardistribuidor,$fecha_relacion]);
              } 
            }


            //AJUSTE DE ESTADOO A PRESTAMOS PAGADOS
            $estadosxplazos = $this->obtenerestadosclientes($vardistribuidor,$fecha_relacion);
            if(!$estadosxplazos->isEmpty()){
              foreach($estadosxplazos as $item){
                $UpdAt = DB::select('update tblprestamos_valesenc set status = "P" WHERE id = ?;', [$item->id_pres]);
              }
            }

        
            // CERRAR FUNCION SI YA NO TIENES SALDO PARA APLICAR
            $montopagado = intval($montoreal + $montoexcedente)-intval($saldo_pagar);
            echo "<br> |||||| MONTO PAGADO = ".$montopagado." ||||| <br>";
            if($montopagado <= 0)
            {
                $montosinproteccion_saldo = 0;
                $montoreal = 0;
                $montopagado = 0;

                if($montopagado == 0)
                {
                  break;
                }
            }else{
              $montopagado = $montopagado;
            }
          }
        }else{
          if($montopagado > 0){
            // REVISAR SI CUENTAS CON SALDO EXCEDENTE
            $saldoexcedente = $this->saldoexdistribuidor($vardistribuidor);
            if($saldoexcedente->isEmpty())
            {
              echo "<br> No hay excedente | ";
              $salex = 0;
            }
            else
            {
              foreach($saldoexcedente as $s)
              {
                echo "Si hay excedente | ";
                // SUMAMOS EXDENTE E INCATIVAMOS PARA USARLO
                $salex = $s->monto;
                if($salex > 0)
                {$actualizastatus = DB::update("update tblexcedentedistribuidor set estado = 'I' where id = ?",[$s->id]);}
              }
            }

            $insertaexcedente = new excedentes();
            $insertaexcedente->id_distribuidor = $vardistribuidor;
            $insertaexcedente->fecha_pago =$varfecha_pago;
            $insertaexcedente->monto=$montopagado+$salex;
            $insertaexcedente->estado="A";
            $insertaexcedente->created_at=$fecha;
            $insertaexcedente->save();

            $tipomov="INGRESO";
            $concepto = "EXCEDENTE APLICADO CON FECHA DEL  ".$varfecha_pago;
            $descripcion= "EXCENDENTE APLICADO DESPUÉS DE HABER COMPLETADO PAGO, DISTRIBUIDOR #".$vardistribuidor.", PAGO REALIZADO EL ".$varfecha_pago;
            $afectarhistorialcuentas = $this->afectarhistorialcuentaspago($tipo,$cuenta,$montopagado+$salex,0,$varfecha_pago,$vardistribuidor,0,$tipomov,$concepto,$descripcion);
            $pExcedenteextra = 1;
          }
        }
      }


      //ALERTA
      if($pCompleto >= 1){
        return redirect()->route('pagosreferenciados')->with("successPagoEfectivo","¡Se guardaron los cambios correctamente!");
      }elseif($pExcedente >= 1){
        return redirect()->route('pagosreferenciados')->with("successPagoExcedente","¡Se guardaron los cambios correctamente!");
      }elseif($pIncompleto >= 1){
        return redirect()->route('pagosreferenciados')->with("pagoIncompleto","¡Se guardaron los cambios correctamente!");
      }elseif($pConciliado >= 1){
        return redirect()->route('pagosreferenciados')->with("envioConcilia","¡Se guardaron los cambios correctamente!");
      }elseif($pExcedenteextra >= 1){
        return redirect()->route('pagosreferenciados')->with("successExcedente","¡Se guardaron los cambios correctamente!");
      }else{
        return redirect()->route('pagosreferenciados')->with("warningnPago","¡No se guardaron los cambios correctamente!");
      }
    }
  }

  public function aplicapagoefectivo(Request $request)
  {
        $date = Carbon::now();
        $fecha = $date->format('Y-m-d');

        //PERMISO DE FECHAS
        $permisos3 = $this->forpermisos('fecha_pagosmanuales'); 
        if($permisos3 == "fecha_pagosmanuales"){
          $varfecha_pago = $request->get('fecha_pago');
        }else{
          $varfecha_pago = $date->format('Y-m-d');
        }

        $fecha_pago = Carbon::createFromFormat('Y-m-d', $varfecha_pago);

        
        $diaactual =  $fecha_pago->format('d');
        $vardistribuidor = $request->get('iddistribuidor');
        $varreferencia = "CAJA";
        $monto = $request->get('monto');
        $user = auth()->user()->name;
        $montopagado = $monto;
        $montosinproteccion_saldo =0;
        $montomascomision = 0;
        $montoexcedente = 0; 
        $pagos_incpletos = 0;
        $pCompleto = 0;
        $tipos = "";
        $tipo_pago = "";
        $pExcedente = 0;
        $pIncompleto = 0;
        $pConciliado = 0;

        $tipo = $request->get('tipo');

        if($tipo == "CAJA")
        {
          $cuenta = $request->get('caja');
        }
        else
        {
          $cuenta = $request->get('cuenta');
        }

        if($cuenta != 0)
        {
          $obtenerlineaspagoenc = $this->obtener_relaciones_a_pagar($vardistribuidor);
          $relaciones_a_pagar = count($obtenerlineaspagoenc);
  
          // BUSCAMOS SI EXISTE UNA RELACION POR PAGAR
          if(!$obtenerlineaspagoenc->isEmpty())
          {
            foreach($obtenerlineaspagoenc as $linea)
            {     
              $salex=0;
              $montosincomision = 0;
              $saldo_pagar = $linea->saldo_pagar;
              $estado = $linea->estado;
              $idpagoencabezado = $linea->id;
              $capital_regresado = 0;
              $fecha_relacion = $linea->fecha_relacion;
              $abonado = $linea->otrosconceptos1;
              $saldo_atrasado = $linea->otrosconceptos2;
              $proteccion_saldo =$linea->proteccion_saldo;
              $montoxtransaccion  = $linea->costo_transaccion;
              $fechacorteini = $linea->fecha_corte_inicio;
              $fechacortefin = $linea->fecha_corte_final;
              $monto_total = $linea->monto_total;
              $statuspresdet="";
              $saldo_atrasadocli = 0;
              $saldorestantecliente = 0;
              $comisioncalcul = 0;
              $montoreal = 0;
              $merece_comision = 0;
              $saldodis = 0;
              $clientesxdis = $this->obtenerdetallepagoClientes($vardistribuidor,$fecha_relacion);
              $totalcliente = count($clientesxdis);
              
  
              // REVISAR SI CUENTAS CON SALDO EXCEDENTE
              $saldoexcedente = $this->saldoexdistribuidor($vardistribuidor);
              if($saldoexcedente->isEmpty())
              {
                echo "<br> No hay excedente | ";
                $salex = 0;
                // QUTAMOS LOS 2 CONCEPTOS QUE SE INCLUYEN EN EL PAGO
                $montosinproteccion_saldo = (($montopagado)-$montoxtransaccion-$proteccion_saldo);
              }
              else
              {
                foreach($saldoexcedente as $s)
                {
                  echo "Si hay excedente | ";
                  // SUMAMOS EXDENTE E INCATIVAMOS PARA USARLO
                  $salex = $s->monto;
                  if($salex > 0)
                  {$actualizastatus = DB::update("update tblexcedentedistribuidor set estado = 'I' where id = ?",[$s->id]);}
                }
                // QUTAMOS LOS 2 CONCEPTOS QUE SE INCLUYEN EN EL PAGO
                $montosinproteccion_saldo = (($montopagado)-$montoxtransaccion-$proteccion_saldo)+$salex;
              }
  
              //APLICAMOS LOS VALORES DEL DINERO QUE MANEJAREMOS
              $abonado = $monto_total+$montosinproteccion_saldo;
              $saldo = $montosinproteccion_saldo;
    
              // VALIDACION DE MERECER COMISION
              $comisiones = $this->validacomision($linea->fecha_relacion,$varfecha_pago,$montosinproteccion_saldo,$saldo_pagar,$vardistribuidor,$fechacortefin,$tipo_pago);    
              if($comisiones->isEmpty())
              {
                echo "No tiene bonificación | ";
                // AÑADIMOS A IMPORTE DE DINERO EL PAGO DE LA COMISION
                $comisioncalcul = 0;
                $montoreal = $montosinproteccion_saldo;
                $merece_comision ='no';
              }
              else
              {  
                echo "Si tiene bonificación | ";
                // AÑADIMOS A IMPORTE DE DINERO EL PAGO DE LA COMISION
                $comisioncalcul = $comisiones["comision"];
                $montoreal = $comisiones["montototal"];
                $merece_comision =$comisiones["merececomision"];
                $capital_regresado = $comisiones["capital_regresado"];
                echo "Capital Regresado :".$capital_regresado." | ";

                $actualizabandera = DB::update("update tblpagos_enc set capital_regresado = ? where id = ?;",[$capital_regresado, $idpagoencabezado]);
              }

              echo "<BR> Monto Pagado = ".$montopagado." Monto sin PS =".$montosinproteccion_saldo." Excedente Guardado =".$salex;
              
              echo "<br>"."Saldo Pagar : ".$saldo_pagar." Monto Real : ".$montoreal." Monto Sin PS : ".$montosinproteccion_saldo." Calculo de Comision : ".$comisioncalcul."<br>";
        
              // return "ALTO";
              // PAGO COMPLETO
              if($montoreal == $saldo_pagar )
              {
                  echo "<br>"."Pago Completo"."<br>";
                  //ACTUALIZAMOS QUE ATRASO DE DV QUEDE EN 0 Y EL COSTO_TRANSACCION QUEDARA EN 0
                  $UpdAt = DB::select('update tblpagos_enc set otrosconceptos2 = "0", costo_transaccion = 16  WHERE id = ?;', [$idpagoencabezado]);
            
                  //ACTUALIZAMOS QUE ATRASO DE CLIENTES QUEDE EN 0 
                  // foreach($clientesxdis as $clientes)
                  // {
                  //   $UpdAtr = DB::select('update tblprestamos_valesdet set saldo = "0" WHERE id = ?;', [$clientes->id]);
                  // }
  
                  // FUNCION PARA APLICAR EL PAGO
                  $funcion = $this->insertarPagos($vardistribuidor, $idpagoencabezado, $montosinproteccion_saldo, $saldo_pagar,
                  0, $varfecha_pago, $fecha_relacion, $montoreal, $comisioncalcul, 0, 
                  0,"I","I", 0, $cuenta, $tipo,$totalcliente, $fecha, $user,0,$tipo,$proteccion_saldo,$montoxtransaccion,$varreferencia,$montopagado);
  
                  //ACTUALIZAMOS QUE ATRASO DE CLIENTES QUEDE EN 0 
                  foreach($clientesxdis as $clientes)
                  {
                    echo "<br> SI ACTUALIZO: ".$clientes->idcliente;
                    $UpdAtr = DB::select('update tblprestamos_valesdet set saldo = "0" WHERE id = ?;', [$clientes->id]);
                  }
  
                  // CERRAR FUNCION SI YA NO TIENES SALDO PARA APLICAR
                  $montosinproteccion_saldo = $montosinproteccion_saldo-$saldo_pagar;
                  if($montosinproteccion_saldo < 0)
                  {
                    $montosinproteccion_saldo = 0;
                  }

                  $pCompleto = 1;
              }

              // PAGO COMPLETO CON EXCEDENTE
              if($montoreal > $saldo_pagar )
              {
                  echo "<br>"."Pago Completo más Excedente"."<br>";
                  
                  //CHECAMOS CUANTO EXCEDENTE TENEMOS PARA GUARDALO 
                  $montoexcedente = $montoreal-$saldo_pagar;
                  //MONTO REAL = LO QUE RECIBIMOS, MAS EL EXCEDENTE, MAS COMISION
                  $montoreal = $montoreal-$montoexcedente;
                  $montosinproteccion_saldo = $montosinproteccion_saldo-$montoexcedente;
                  echo "<br> | "."Excedente : ".$montoexcedente." Monto Real: ".$montoreal." Monto Sin PS: ".$montosinproteccion_saldo."<br>";

                  
                  //APLICAR PAGO COMPLETO
                  //NO GENERAMOS ATRASOS, ASÍ QUE ACTUALIZAMOS EL CAMPO
                  $UpdAtr = DB::select('update tblpagos_enc set otrosconceptos2 = "0" WHERE id = ?;', [$idpagoencabezado]);

                  //MANDAMOS TODOS LOS CALCULOS PARA INSERTAR EL PAGO DE LA RELACION
                  $funcion = $this->insertarPagos($vardistribuidor, $idpagoencabezado, $montosinproteccion_saldo, $saldo_pagar,
                  0, $varfecha_pago,$fecha_relacion,$montoreal,  $comisioncalcul, 0, 
                  0,"I","I", $montoexcedente, $cuenta, $tipo,$totalcliente, $fecha, $user,0, $tipo,$proteccion_saldo,$montoxtransaccion,$varreferencia,$montopagado);

                  //ACTUALIZAMOS QUE ATRASO DE CLIENTES QUEDE EN 0 
                  foreach($clientesxdis as $clientes)
                  {
                    $UpdAtr = DB::select('update tblprestamos_valesdet set saldo = "0" WHERE id = ?;', [$clientes->id]);
                  }

                  //SALIMOS DE FUNCION
                  $montosinproteccion_saldo = $montosinproteccion_saldo-$saldo_pagar;
                  if($montosinproteccion_saldo < 0)
                  {
                    $montosinproteccion_saldo = 0;
                  }

                  $relacionesfaltantes = $this->relacionesfaltantes($vardistribuidor);
  
                  //SALIMOS DE FUNCION
                  if($montoexcedente < 50 || $relacionesfaltantes->isEmpty()){
                      //GUARDAR EXCEDENTE
                      $insertaexcedente = new excedentes();
                      $insertaexcedente->id_distribuidor = $vardistribuidor;
                      $insertaexcedente->fecha_pago =$varfecha_pago;
                      $insertaexcedente->monto=$montoexcedente;
                      $insertaexcedente->estado="A";
                      $insertaexcedente->created_at=$fecha;
                      $insertaexcedente->save();
                      
                      $montosinproteccion_saldo = $montosinproteccion_saldo-$saldo_pagar;
                      $montoexcedente = 0;
                    echo "<br> no aplico para seguir pagondo con excedente";
                  }else{
                    $montoexcedente = $montoexcedente;
                    echo "<br> si aplico para seguir pagondo con excedente";
                  }
                  
                  $pExcedente = 1;
              }

              // PAGO INCOMPLETO
              if($montoreal < $saldo_pagar && $montoreal > 0 && $montosinproteccion_saldo > 0)
              {
                $finalcomision = Carbon::parse($fecha_relacion)->addDays(5);
                $fechaf = $finalcomision->format('Y-m-d');
                $monto_conciliado = 0;
                
                $ultima_relacion = "";
                $ultimarela = DB::select("select tblpagos_enc.fecha_relacion from tblpagos_enc  where id_distribuidor = ? order by fecha_relacion DESC limit 1;",[$vardistribuidor]);
                foreach($ultimarela as $key){
                  $ultima_relacion = $key->fecha_relacion;
                }
                $ultima_relacion = Carbon::parse($ultima_relacion);
                $ultima_relacion = $ultima_relacion->format('Y-m-d');
            
                //si esta entre fecha de comisiones se le da la opcion de un pago conciliado
                //y juntarlos al final para completar el pago
                if($varfecha_pago >= $fechacorteini && $varfecha_pago <= $fechaf && $ultima_relacion == $fecha_relacion)
                {
                
                  $pagosyacontrados = $this->obtenerpagosconcetrandosporquincenaydis($vardistribuidor,$fecha_relacion,$montoreal);
                  $validaconcentrados = $this->obtenerpagosconcixdisrela($vardistribuidor,$fecha_relacion);
                 
                  // if($pagosyacontrados->isEmpty())
                  // {
                        echo "<br>"."Pago Conciliado"."<br>";
                        $insertapagoconcentrado = new pagocontrados();
                        $insertapagoconcentrado->id_distirbuidor = $vardistribuidor;
                        $insertapagoconcentrado->saldo_pagar_real = $saldo_pagar;
                        // if($montoexcedente > 0)
                        // {
                        //   // echo "entro";
                        //   $monto_conciliado = $montoexcedente;
                        // }
                        // else
                        // {
                        //   // echo "entro al 2";
                        //   $monto_conciliado = $montoreal;
                        // }     

                        $monto_conciliado = $montoreal;

                        if(!$validaconcentrados->isEmpty()){
                          $monto_conciliado = $montoreal + $proteccion_saldo;
                        }

                        $insertapagoconcentrado->intento_pago = $monto_conciliado;
                        $insertapagoconcentrado->fecha_relacion = $fecha_relacion;
                        $insertapagoconcentrado->fecha_intento_pago = $varfecha_pago;
                        $insertapagoconcentrado->status = "A";
                        $insertapagoconcentrado->idcuenta=$cuenta;
                        $insertapagoconcentrado->tipocuenta = $tipo;
                        $insertapagoconcentrado->created_at = $fecha;
                        $insertapagoconcentrado->monto_recibido = $montopagado;
                        $insertapagoconcentrado->referencia_pago = "CAJA";
                        
                        if($insertapagoconcentrado->save())
                        {
                          $pagos_incpletos = $pagos_incpletos+1;
                        }
                        //afectamos las cuentas
                        $tipomov="PAGO";
                        $concepto = "FECHA DE CAPTURA DEL PAGO ".$varfecha_pago;
                        $descripcion= "PAGO CONCILIADO APLICADO DE DISTRIBUIDOR #".$vardistribuidor." A LA RELACION DEL ".$fecha_relacion;
                        $afectarhistorialcuentas = $this->afectarhistorialcuentaspago($tipo,$cuenta,$montopagado,$comisioncalcul,$varfecha_pago,$vardistribuidor,$idpagoencabezado,$tipomov,$concepto,$descripcion);
                        $pConciliado = 1;
                  // }
                }
                // se aplica el pago normal sin comision 
                else
                {
             
                  echo "<br>"."Pago Incompleto sin comision"."<br>";
                  $pagorealizado = $montoreal;
                  $saldo_atrasado = $saldo_pagar - $montoreal;
                  echo "<br> Atraso Generado:".$saldo_atrasado;

                  if($tipo == "CAJA"){
                    $updateenc = DB::update('update tblpagos_enc set estado = "P", status_atraso = "I", fecha_pago = ?, monto_total = ?, otrosconceptos1 = ?, otrosconceptos2 = ?, tipo_cuenta = ?, caja = ?  where id = ?;', [$varfecha_pago,$pagorealizado,$montoreal,$saldo_atrasado,$tipo,$cuenta,$idpagoencabezado]);
                  }else{
                    $updateenc = DB::update('update tblpagos_enc set estado = "P", status_atraso = "I", fecha_pago = ?, monto_total = ?, otrosconceptos1 = ?, otrosconceptos2 = ?, tipo_cuenta = ?, cuenta = ?  where id = ?;', [$varfecha_pago,$pagorealizado,$montoreal,$saldo_atrasado,$tipo,$cuenta,$idpagoencabezado]);
                  }
                  
                  
                  if($updateenc > 0)
                  {
                
                      $UpdAtr = DB::select('update tblpagos_enc set otrosconceptos2 = "0" WHERE id = ?;', [$idpagoencabezado]);

                      //return $montosinproteccion_saldo;
                      $funcion = $this->insertarPagos($vardistribuidor, $idpagoencabezado, $montosinproteccion_saldo, $saldo_pagar,
                      0, $varfecha_pago,$fecha_relacion,$montoreal,  $comisioncalcul, $saldo_atrasado, 
                      0,"I","I", 0, $cuenta, $tipo,$totalcliente, $fecha, $user,0,$tipo,$proteccion_saldo,$montoxtransaccion,$varreferencia,$montopagado);

                      $pagocli = 0;
                      //ACTUALIZAR ESTADOS DE ABONOS COMPLETOS CLIENTES
                      foreach($clientesxdis as $clientes)
                      {

                        if($clientes->status == "P" && $clientes->saldo == 0)
                        {
                          $UpdAtr1 = DB::select('update tblprestamos_valesdet  pres_det set  pres_det.status = ?, pres_det.saldo = ? WHERE pres_det.id = ? ;', ["P",0,$clientes->id]);
                        }
                        else
                        {
                          $abonadocliente = 0;
                          $obtenerabonado =DB::select("select pago_total - saldo as abonado from tblprestamos_valesdet where id = ?;",[$clientes->id]);

                          foreach($obtenerabonado as $abn)
                          {
                            $abonadocliente = $abn->abonado;
                          }

                          if($clientes->saldo == $saldo)
                          {
                            $saldo=$clientes->pago_total;
                          }
                        
                          if($clientes->status == "N" || $clientes->saldo > 0 )
                          {

                            if ($clientes->saldo > 0){
                                $pagocli = $clientes->saldo;
                            }else{
                                $pagocli = $clientes->pago_total;
                            }
                            
                            //actualizamos el pago por cliente
                            if($saldo >= $clientes->pago_total)
                              {
                                  $saldo_atrasadocli = 0;
                                  $statuspresdet = "P";    
                                  $saldo = 0;
                              }
                              //pago incompleto cliente
                        
                            else
                            { 
                                
                                  $saldo_atrasadocli = $pagocli - $saldo;
                                  $statuspresdet = "P"; 
                                  $saldo = 0;

                            }
                          }
                    
                  
                            $UpdAtr1 = DB::select('update tblprestamos_valesdet  pres_det set  pres_det.status = ?, pres_det.saldo = ? WHERE pres_det.id = ? ;', [$statuspresdet,$saldo_atrasadocli,$clientes->id]);

                            if($saldo <=0)
                            {
                              if($montoexcedente > 0)
                              {
                                $saldofaltanteendet=0;
                                $obtenersaldopendiente=DB::SELECT("select c.id,c.saldo from tblclientes_vales a
                                  inner join tblprestamos_valesenc b on  a.id =b.idcliente
                                  inner join tblprestamos_valesdet c on c.idprestamo_vales = b.id
                                  where c.fecha_pago = ? and a.iddistribuidor= ? and c.saldo > 0 and b.status = 'A';",[$fecha_relacion,$vardistribuidor]);
                                  foreach($obtenersaldopendiente as $s)
                                  {
                                  $saldofaltanteendet = $s->saldo;
                                  $idspre = $s->id;
                                  }

                                  if($saldofaltanteendet == $montoexcedente)
                                  {
                                  $UpdAtr2 = DB::select('update tblprestamos_valesdet pres_det set pres_det.saldo = 0 WHERE pres_det.id = ? ;',[$s->id]);
                                  return $UpdAtr2;
                                  if($UpdAtr2 > 0)
                                  {
                                    echo "se actualizo la linea".$s->id;

                                  }
                                  }

                              }
                              break;
                              
                            }
                        }
              
                      }
                    
                      if($montosinproteccion_saldo < 0)
                      {
                        $montosinproteccion_saldo = 0;
                      }
                  }
                  else 
                  {
                    echo "error no actualizo";
                  }
  
                  if($montosinproteccion_saldo = 0)
                  {
  
                  }
                  else
                  {
                    $ultimop = "";
                    //nueva liena a crear con su atraso
                    $pagosenc = new pagosenc();
                    $pagosenc->id_distribuidor=$vardistribuidor;
                    $pagosenc->saldo_pagar=$saldo_atrasado;
                    $pagosenc->comision=0;
                    $pagosenc->interes=0;
                    $pagosenc->monto_total=0;
                    $pagosenc->estado_generado="u";
                    $pagosenc->estado="N";
                    $pagosenc->fecha_pago="Null";
                    $pagosenc->fecha_relacion= $fecha_relacion;
                    $pagosenc->fecha_corte_inicio=$fechacorteini;
                    $pagosenc->fecha_corte_final=$fechacortefin;
                    $pagosenc->otrosconceptos1=0;
                    $pagosenc->otrosconceptos2=0;
                    $pagosenc->status_atraso="A";
                    $pagosenc->otrosconceptos3=0;
                    $pagosenc->costo_transaccion=16;
                    $pagosenc->created_at=$fecha;
                    $pagosenc->created_by = auth()->user()->name;
                    if($pagosenc->save())
                    {
                      $ultimopagoenc = $this->obtenerultimopagoenc($vardistribuidor);
                      foreach($ultimopagoenc as $ul)
                      {
                        $ultimop = $ul->id;
                      }
                    }
                  }
                  //si aplica el pago bien o lo actualiza bien creamos la nueva linea   
                  $pIncompleto = 1;
                  echo "<br>  CON 1";
                  $actualizaPorce = DB::update("update tblpagos_enc set flag2porciento = 1 where id_distribuidor = ? and fecha_relacion <= ?;",[$vardistribuidor,$fecha_relacion]);
                } 
              }


              //AJUSTE DE ESTADOO A PRESTAMOS PAGADOS
              $estadosxplazos = $this->obtenerestadosclientes($vardistribuidor,$fecha_relacion);
              if(!$estadosxplazos->isEmpty()){
                foreach($estadosxplazos as $item){
                  $UpdAt = DB::select('update tblprestamos_valesenc set status = "P" WHERE id = ?;', [$item->id_pres]);
                }
              }
          
              // CERRAR FUNCION SI YA NO TIENES SALDO PARA APLICAR
              $montopagado = intval($montoreal + $montoexcedente)-intval($saldo_pagar);
              echo "<br> |||||| MONTO PAGADO = ".$montopagado." ||||| <br>";
              if($montopagado <= 0)
              {
                $montosinproteccion_saldo = 0;
                $montoreal = 0;
                $montopagado = 0;

                if($montopagado == 0)
                {
                  break;
                }

              }else{
                $montopagado = $montopagado;
              }
              
            }

            //ALERTA
            if($pCompleto >= 1){
              return redirect()->route('pagosreferenciados')->with("successPagoEfectivo","¡Se guardaron los cambios correctamente!");
            }elseif($pExcedente >= 1){
              return redirect()->route('pagosreferenciados')->with("successPagoExcedente","¡Se guardaron los cambios correctamente!");
            }elseif($pIncompleto >= 1){
              return redirect()->route('pagosreferenciados')->with("pagoIncompleto","¡Se guardaron los cambios correctamente!");
            }elseif($pConciliado >= 1){
              return redirect()->route('pagosreferenciados')->with("envioConcilia","¡Se guardaron los cambios correctamente!");
            }else{
              return redirect()->route('pagosreferenciados')->with("warningnPago","¡No se guardaron los cambios correctamente!");
            }
          }else{
            return redirect()->route('pagosreferenciados')->with("warningRelacion","¡No se guardaron los cambios correctamente!");
          }
        }else{
          return redirect()->route('pagosreferenciados')->with("warningCuenta","¡No se guardaron los cambios correctamente!");
        }
  }

  public function aplicarpagosconcentrados()
  {
    $date = Carbon::now();
    $fecha = $date->format('Y-m-d');
    $fechaformateada = Carbon::createFromFormat('Y-m-d', $fecha);
    $diadehoy = $fechaformateada->format('d');
    $tipo = "";
    $statusdis = "";
    $pagoconcilidado = 0;
    $pCompleto = 0;
    $pExcedente = 0;
    $pIncompleto = 0;
    $pConciliado = 0;

    if($diadehoy == 6 || $diadehoy == 21)
    {
      $vardispagoconcentado = $this->distribuidoresconpagoconcentrado();
      foreach($vardispagoconcentado as $ltspagxdiscon)
      {
        $tipo =  $ltspagxdiscon->tipocuenta;
        if($tipo == "CAJA")
        {
        $cuenta =   $ltspagxdiscon->idcuenta;
        }
        else
        {
          $cuenta =   $ltspagxdiscon->idcuenta;
        }

        $varfecha_pago = $ltspagxdiscon->ultimopagofecha;
        $fecha_pago = Carbon::createFromFormat('Y-m-d', $varfecha_pago);
        $diaactual =  $fecha_pago->format('d');
        $vardistribuidor = $ltspagxdiscon->id_distirbuidor;
        $monto = $ltspagxdiscon->pago_total;
        $varreferencia = $ltspagxdiscon->referencia_pago;
        $user = auth()->user()->name;
        $montopagado = $monto;

        $obtenerlineaspagoenc = $this->obtener_relaciones_a_pagar($vardistribuidor);
        $relaciones_a_pagar = count($obtenerlineaspagoenc);

        if(!$obtenerlineaspagoenc->isEmpty())
        {
          foreach($obtenerlineaspagoenc as $linea)
          {
            $salex = 0;
            $montosincomision = 0;
            $saldo_pagar = $linea->saldo_pagar;
            $estado = $linea->estado;
            $idpagoencabezado = $linea->id;
            $capital_regresado = 0;
            $fecha_relacion = $linea->fecha_relacion;
            $abonado = $linea->otrosconceptos1;
            $saldo_atrasado = $linea->otrosconceptos2;
            $proteccion_saldo =$linea->proteccion_saldo;
            $montoxtransaccion  = $linea->costo_transaccion;
            $fechacorteini = $linea->fecha_corte_inicio;
            $fechacortefin = $linea->fecha_corte_final;
            $monto_total = $linea->monto_total;
            $statuspresdet="";
            $tipo_pago = "";
            $tipos = "CONCILIADOS";
            $saldo_atrasadocli = 0;
            $saldorestantecliente = 0;
            $comisioncalcul = 0;
            $montoreal = 0;
            $merece_comision = 0;
            $saldodis = 0;
            $clientesxdis = $this->obtenerdetallepagoClientes($vardistribuidor,$fecha_relacion);
            $totalcliente = count($clientesxdis);
              
            // REVISAR SI CUENTAS CON SALDO EXCEDENTE
            $saldoexcedente = $this->saldoexdistribuidor($vardistribuidor);
            if($saldoexcedente->isEmpty())
            {
              echo "<br> No hay excedente | ";
              $salex = 0;
              // QUTAMOS LOS 2 CONCEPTOS QUE SE INCLUYEN EN EL PAGO
              $montosinproteccion_saldo = (($montopagado));
            }
            else
            {
              foreach($saldoexcedente as $s)
              {
                echo "Si hay excedente | ";
                // SUMAMOS EXDENTE E INCATIVAMOS PARA USARLO
                $salex = $s->monto;
                if($salex > 0)
                {$actualizastatus = DB::update("update tblexcedentedistribuidor set estado = 'I' where id = ?",[$s->id]);}
              }
              // QUTAMOS LOS 2 CONCEPTOS QUE SE INCLUYEN EN EL PAGO
              $montosinproteccion_saldo = (($montopagado))+$salex;
            }

            //APLICAMOS LOS VALORES DEL DINERO QUE MANEJAREMOS
            $abonado = $monto_total+$montosinproteccion_saldo;
            $saldo = $montosinproteccion_saldo;
  
            // VALIDACION DE MERECER COMISION
            $comisiones = $this->validacomision($linea->fecha_relacion,$varfecha_pago,$montosinproteccion_saldo,$saldo_pagar,$vardistribuidor,$fechacortefin,$tipo_pago);    
            if($comisiones->isEmpty())
            {
              echo "No tiene bonificación | ";
              // AÑADIMOS A IMPORTE DE DINERO EL PAGO DE LA COMISION
              $comisioncalcul = 0;
              $montoreal = $montosinproteccion_saldo;
              $merece_comision ='no';
            }
            else
            {  
              echo "Si tiene bonificación | ";
              // AÑADIMOS A IMPORTE DE DINERO EL PAGO DE LA COMISION
              $comisioncalcul =$comisiones["comision"];
              $montoreal = $comisiones["montototal"];
              $merece_comision = $comisiones["merececomision"];
              $capital_regresado = $comisiones["capital_regresado"];
              echo "Capital Regresado :".$capital_regresado." | ";

              $actualizabandera = DB::update("update tblpagos_enc set capital_regresado = ? where id = ?;",[$capital_regresado, $idpagoencabezado]);
            }
            echo "<br>"."Monto Real : ".$montoreal." Monto Sin PS : ".$montosinproteccion_saldo." Calculo de Comision : ".$comisioncalcul."<br>";

            // PAGO COMPLETO
            if($montoreal == $saldo_pagar )
            {
                echo "<br>"."Pago Completo"."<br>";
                //ACTUALIZAMOS QUE ATRASO DE DV QUEDE EN 0 Y EL COSTO_TRANSACCION QUEDARA EN 0
                $UpdAt = DB::select('update tblpagos_enc set otrosconceptos2 = "0", costo_transaccion = 16  WHERE id = ?;', [$idpagoencabezado]);
          
                //ACTUALIZAMOS QUE ATRASO DE CLIENTES QUEDE EN 0 
                // foreach($clientesxdis as $clientes)
                // {
                //   $UpdAtr = DB::select('update tblprestamos_valesdet set saldo = "0" WHERE id = ?;', [$clientes->id]);
                // }

                // FUNCION PARA APLICAR EL PAGO
                $funcion = $this->insertarPagos($vardistribuidor, $idpagoencabezado, $montosinproteccion_saldo, $saldo_pagar,
                0, $varfecha_pago, $fecha_relacion, $montoreal, $comisioncalcul, 0, 
                0,"I","I", 0, $cuenta, $tipo,$totalcliente, $fecha, $user,0,$tipos,$proteccion_saldo,$montoxtransaccion,$varreferencia,0);

                //ACTUALIZAMOS QUE ATRASO DE CLIENTES QUEDE EN 0 
                foreach($clientesxdis as $clientes)
                {
                  echo "<br> SI ACTUALIZO: ".$clientes->idcliente;
                  $UpdAtr = DB::select('update tblprestamos_valesdet set saldo = "0" WHERE id = ?;', [$clientes->id]);
                }

                // CERRAR FUNCION SI YA NO TIENES SALDO PARA APLICAR
                $montosinproteccion_saldo = $montosinproteccion_saldo-$saldo_pagar;
                if($montosinproteccion_saldo < 0)
                {
                  $montosinproteccion_saldo = 0;
                }

                $pCompleto = 1;
            }

            // PAGO COMPLETO CON EXCEDENTE
            if($montoreal > $saldo_pagar )
            {
                echo "<br>"."Pago Completo más Excedente"."<br>";
                
                //CHECAMOS CUANTO EXCEDENTE TENEMOS PARA GUARDALO 
                $montoexcedente = $montoreal-$saldo_pagar;
                //MONTO REAL = LO QUE RECIBIMOS, MAS EL EXCEDENTE, MAS COMISION
                $montoreal = $montoreal-$montoexcedente;
                $montosinproteccion_saldo = $montosinproteccion_saldo-$montoexcedente;
                echo "<br> | "."Excedente : ".$montoexcedente." Monto Real: ".$montoreal." Monto Sin PS: ".$montosinproteccion_saldo."<br>";

                
                //APLICAR PAGO COMPLETO
                //NO GENERAMOS ATRASOS, ASÍ QUE ACTUALIZAMOS EL CAMPO
                $UpdAtr = DB::select('update tblpagos_enc set otrosconceptos2 = "0" WHERE id = ?;', [$idpagoencabezado]);

                //MANDAMOS TODOS LOS CALCULOS PARA INSERTAR EL PAGO DE LA RELACION
                $funcion = $this->insertarPagos($vardistribuidor, $idpagoencabezado, $montosinproteccion_saldo, $saldo_pagar,
                0, $varfecha_pago,$fecha_relacion,$montoreal,  $comisioncalcul, 0, 
                0,"I","I", $montoexcedente, $cuenta, $tipo,$totalcliente, $fecha, $user,0, $tipos,$proteccion_saldo,$montoxtransaccion,$varreferencia,0);

                //ACTUALIZAMOS QUE ATRASO DE CLIENTES QUEDE EN 0 
                foreach($clientesxdis as $clientes)
                {
                  $UpdAtr = DB::select('update tblprestamos_valesdet set saldo = "0" WHERE id = ?;', [$clientes->id]);
                }

                //SALIMOS DE FUNCION
                $montosinproteccion_saldo = $montosinproteccion_saldo-$saldo_pagar;
                if($montosinproteccion_saldo < 0)
                {
                  $montosinproteccion_saldo = 0;
                }

                $relacionesfaltantes = $this->relacionesfaltantes($vardistribuidor);

                //SALIMOS DE FUNCION
                if($montoexcedente < 50 || $relacionesfaltantes->isEmpty())
                {
                    //GUARDAR EXCEDENTE
                    $insertaexcedente = new excedentes();
                    $insertaexcedente->id_distribuidor = $vardistribuidor;
                    $insertaexcedente->fecha_pago =$varfecha_pago;
                    $insertaexcedente->monto=$montoexcedente;
                    $insertaexcedente->estado="A";
                    $insertaexcedente->created_at=$fecha;
                    $insertaexcedente->save();
                    
                    $montosinproteccion_saldo = $montosinproteccion_saldo-$saldo_pagar;
                    $montoexcedente = 0;
                  echo "<br> no aplico para seguir pagondo con excedente";
                }else{
                  $montoexcedente = $montoexcedente;
                  echo "<br> si aplico para seguir pagondo con excedente";
                }
                
                $pExcedente = 1;
            }

            // PAGO INCOMPLETO
            if($montoreal < $saldo_pagar && $montoreal > 0 && $montosinproteccion_saldo > 0)
            {
                $finalcomision = Carbon::parse($fecha_relacion)->addDays(5);
                $fechaf = $finalcomision->format('Y-m-d');
                $monto_conciliado = 0;
                $montoexcedente = 0;

                echo "<br>"."Pago Incompleto sin comision"."<br>";
                $pagorealizado = $montoreal;
                $saldo_atrasado = $saldo_pagar - $montoreal;
                echo "<br> Atraso Generado:".$saldo_atrasado;

                if($tipo == "CAJA"){
                  $updateenc = DB::update('update tblpagos_enc set estado = "P", status_atraso = "I", fecha_pago = ?, monto_total = ?, otrosconceptos1 = ?, otrosconceptos2 = ?, tipo_cuenta = ?, caja = ?  where id = ?;', [$varfecha_pago,$pagorealizado,$montoreal,$saldo_atrasado,$tipo,$cuenta,$idpagoencabezado]);
                }else{
                  $updateenc = DB::update('update tblpagos_enc set estado = "P", status_atraso = "I", fecha_pago = ?, monto_total = ?, otrosconceptos1 = ?, otrosconceptos2 = ?, tipo_cuenta = ?, cuenta = ?  where id = ?;', [$varfecha_pago,$pagorealizado,$montoreal,$saldo_atrasado,$tipo,$cuenta,$idpagoencabezado]);
                }
                
                if($updateenc > 0)
                {
               
                    $UpdAtr = DB::select('update tblpagos_enc set otrosconceptos2 = "0" WHERE id = ?;', [$idpagoencabezado]);

                    //return $montosinproteccion_saldo;
                    $funcion = $this->insertarPagos($vardistribuidor, $idpagoencabezado, $montosinproteccion_saldo, $saldo_pagar,
                    0, $varfecha_pago,$fecha_relacion,$montoreal,  $comisioncalcul, $saldo_atrasado, 
                    0,"I","I", 0, $cuenta, $tipo,$totalcliente, $fecha, $user,0,$tipos,$proteccion_saldo,$montoxtransaccion,$varreferencia,0);

                    $pagocli = 0;
                    //ACTUALIZAR ESTADOS DE ABONOS COMPLETOS CLIENTES
                    foreach($clientesxdis as $clientes)
                    {

                      if($clientes->status == "P" && $clientes->saldo == 0)
                      {
                        $UpdAtr1 = DB::select('update tblprestamos_valesdet  pres_det set  pres_det.status = ?, pres_det.saldo = ? WHERE pres_det.id = ? ;', ["P",0,$clientes->id]);
                      }
                      else
                      {
                        $abonadocliente = 0;
                        $obtenerabonado =DB::select("select pago_total - saldo as abonado from tblprestamos_valesdet where id = ?;",[$clientes->id]);

                        foreach($obtenerabonado as $abn)
                        {
                          $abonadocliente = $abn->abonado;
                        }

                        if($clientes->saldo == $saldo)
                        {
                          $saldo=$clientes->pago_total;
                        }
                      
                        if($clientes->status == "N" || $clientes->saldo > 0 )
                        {

                          if ($clientes->saldo > 0){
                              $pagocli = $clientes->saldo;
                          }else{
                              $pagocli = $clientes->pago_total;
                          }
                          
                          //actualizamos el pago por cliente
                          if($saldo >= $clientes->pago_total)
                            {
                                $saldo_atrasadocli = 0;
                                $statuspresdet = "P";    
                                $saldo = 0;
                            }
                            //pago incompleto cliente
                      
                          else
                          { 
                              
                                $saldo_atrasadocli = $pagocli - $saldo;
                                $statuspresdet = "P"; 
                                $saldo = 0;

                          }
                        }
                  
                
                          $UpdAtr1 = DB::select('update tblprestamos_valesdet  pres_det set  pres_det.status = ?, pres_det.saldo = ? WHERE pres_det.id = ? ;', [$statuspresdet,$saldo_atrasadocli,$clientes->id]);

                          if($saldo <=0)
                          {
                            if($montoexcedente > 0)
                            {
                              $saldofaltanteendet=0;
                              $obtenersaldopendiente=DB::SELECT("select c.id,c.saldo from tblclientes_vales a
                                inner join tblprestamos_valesenc b on  a.id =b.idcliente
                                inner join tblprestamos_valesdet c on c.idprestamo_vales = b.id
                                where c.fecha_pago = ? and a.iddistribuidor= ? and c.saldo > 0 and b.status = 'A';",[$fecha_relacion,$vardistribuidor]);
                                foreach($obtenersaldopendiente as $s)
                                {
                                $saldofaltanteendet = $s->saldo;
                                $idspre = $s->id;
                                }

                                if($saldofaltanteendet == $montoexcedente)
                                {
                                $UpdAtr2 = DB::select('update tblprestamos_valesdet pres_det set pres_det.saldo = 0 WHERE pres_det.id = ? ;',[$s->id]);
                                return $UpdAtr2;
                                if($UpdAtr2 > 0)
                                {
                                  echo "se actualizo la linea".$s->id;

                                }
                                }

                            }
                            break;
                            
                          }
                      }
            
                    }
                  
                    if($montosinproteccion_saldo < 0)
                    {
                      $montosinproteccion_saldo = 0;
                    }
                    echo "<br> |termino primera parte, MONTO SP:".$montosinproteccion_saldo;
                }
                else 
                {
                  echo "error no actualizo";
                }

                if($montosinproteccion_saldo = 0)
                {

                }
                else
                {
                  echo "<br> |INSERTA NUEVA LINEA CON EL ATRASO GENERADO";
                  $ultimop = "";
                  //nueva liena a crear con su atraso
                  $pagosenc = new pagosenc();
                  $pagosenc->id_distribuidor=$vardistribuidor;
                  $pagosenc->saldo_pagar=$saldo_atrasado;
                  $pagosenc->comision=0;
                  $pagosenc->interes=0;
                  $pagosenc->monto_total=0;
                  $pagosenc->estado_generado="u";
                  $pagosenc->estado="N";
                  $pagosenc->fecha_pago="Null";
                  $pagosenc->fecha_relacion= $fecha_relacion;
                  $pagosenc->fecha_corte_inicio=$fechacorteini;
                  $pagosenc->fecha_corte_final=$fechacortefin;
                  $pagosenc->otrosconceptos1=0;
                  $pagosenc->otrosconceptos2=0;
                  $pagosenc->status_atraso="A";
                  $pagosenc->otrosconceptos3=0;
                  $pagosenc->costo_transaccion=16;
                  $pagosenc->created_at=$fecha;
                  $pagosenc->created_by = auth()->user()->name;
                  if($pagosenc->save())
                  {
                    $ultimopagoenc = $this->obtenerultimopagoenc($vardistribuidor);
                    foreach($ultimopagoenc as $ul)
                    {
                      $ultimop = $ul->id;
                    }
                  }
                }
                //si aplica el pago bien o lo actualiza bien creamos la nueva linea   
                $pIncompleto = 1;
                echo "<br>  CON 1";
                $actualizaPorce = DB::update("update tblpagos_enc set flag2porciento = 1 where id_distribuidor = ? and fecha_relacion <= ?;",[$vardistribuidor,$fecha_relacion]);
            }
  
  
            //QUITAMOS DE CONCLIADOS
            $updateenc = DB::update('update tblpagosconcentrado set status = "P" where id_distirbuidor = ? and fecha_relacion = ?;', [$vardistribuidor,$fecha_relacion]);

            //AJUSTE DE ESTADOO A PRESTAMOS PAGADOS
            $estadosxplazos = $this->obtenerestadosclientes($vardistribuidor,$fecha_relacion);
            if(!$estadosxplazos->isEmpty()){
              foreach($estadosxplazos as $item){
                $UpdAt = DB::select('update tblprestamos_valesenc set status = "P" WHERE id = ?;', [$item->id_pres]);
              }
            }


            // CERRAR FUNCION SI YA NO TIENES SALDO PARA APLICAR
            $montopagado = intval($montoreal + $montoexcedente)-intval($saldo_pagar);
            echo "<br> |||||| MONTO PAGADO = ".$montopagado." ||||| <br>";
            if($montopagado <= 0)
            {
              $montosinproteccion_saldo = 0;
              $montoreal = 0;
              $montopagado = 0;

              if($montopagado == 0)
              {
                break;
              }

            }else{
              $montopagado = $montopagado;
            }
          }
        }
      }

      //ALERTA
      if($pCompleto >= 1){
        return redirect()->route('pagosconciliados')->with("successPagoEfectivo","¡Se guardaron los cambios correctamente!");
      }elseif($pExcedente >= 1){
        return redirect()->route('pagosconciliados')->with("successPagoExcedente","¡Se guardaron los cambios correctamente!");
      }elseif($pIncompleto >= 1){
        return redirect()->route('pagosconciliados')->with("pagoIncompleto","¡Se guardaron los cambios correctamente!");
      }elseif($pConciliado >= 1){
        return redirect()->route('pagosconciliados')->with("envioConcilia","¡Se guardaron los cambios correctamente!");
      }else{
        return redirect()->route('pagosconciliados')->with("warningnPago","¡No se guardaron los cambios correctamente!");
      }

    }else{
      return redirect()->route('pagosconciliados')->with("warningfechasconcili","¡No aplica!");
    }
  }

  public function aplicarpagoconciliadoxdistribuidor(int $dis, string $fechapago, float $montopagado)
  {
    $date = Carbon::now();
    $fecha = $date->format('Y-m-d');
    $infopagocontrado = $this->pagoconcetradoxdis($dis);
    foreach($infopagocontrado as $ltspagxdiscon)
    {
        $tipo =  $ltspagxdiscon->tipocuenta;
        if($tipo == "CAJA")
        {
          $cuenta =   $ltspagxdiscon->idcuenta;
        }
        else
        {
          $cuenta =   $ltspagxdiscon->idcuenta;
        }

        $varfecha_pago = $fechapago;
        $fecha_pago = Carbon::createFromFormat('Y-m-d', $varfecha_pago);
        $diaactual =  $fecha_pago->format('d');
        $vardistribuidor = $dis;
        $varreferencia = $ltspagxdiscon->referencia_pago;
        $montoentante = $montopagado;
        $user = auth()->user()->name;
        $montopagado = $montoentante;

        $obtenerlineaspagoenc = $this->obtener_relaciones_a_pagar($vardistribuidor);
        $relaciones_a_pagar = count($obtenerlineaspagoenc);


      if(!$obtenerlineaspagoenc->isEmpty())
      {
      
        foreach($obtenerlineaspagoenc as $linea)
        {
              $saldo_pagar = $linea->saldo_pagar;
              $estado = $linea->estado;
              $idpagoencabezado = $linea->id;
              $fecha_relacion = $linea->fecha_relacion;
              $abonado = $linea->otrosconceptos1;
              $saldo_atrasado = $linea->otrosconceptos2;
              $proteccion_saldo =$linea->proteccion_saldo;
              $montosincomision = 0;
              $fechacorteini = $linea->fecha_corte_inicio;
              $fechacortefin = $linea->fecha_corte_final;
              $monto_total = $linea->monto_total;
              $proteccion_saldo = $linea->proteccion_saldo;
              $montoxtransaccion  = $linea->costo_transaccion;
              $montoexcedente = 0;
              $montosinproteccion_saldo = $montopagado;
              $saldo_atrasadocli = 0;
              $abonado = $monto_total+$montosinproteccion_saldo;
              $statuspresdet="";
              $saldo = $montosinproteccion_saldo;
              $saldorestantecliente = 0;
              $comisioncalcul = 0;
              $montoreal = 0;
              $merece_comision = 0;
              $saldodis = 0;
              $pago_esperado = 0;
              $tipos = "CONCILIADOS";
              $tipo_pago = "CONDONADO";

              $clientesxdis = $this->obtenerdetallepagoClientes($vardistribuidor,$fecha_relacion);
              $totalcliente = count($clientesxdis);
              
              // Afectamos las cuentas con  la proteccion de saldo
              $comisiones = $this->validacomision($linea->fecha_relacion,$varfecha_pago,$montosinproteccion_saldo,$saldo_pagar,$vardistribuidor,$fechacortefin,$tipo_pago);    
              
              
              $comisioncalcul = $comisiones["comision"];
              $montoreal = $comisiones["montototal"];
              $merece_comision = $comisiones["merececomision"];
              $pago_esperado = $comisiones["pago_esperado"];

              echo "<br> Comision: ".$comisioncalcul." Monto Real:". $montoreal." Pago Esperado:".$pago_esperado."<br>";

              $montoreal = $montoreal;
              $montofaltante = $pago_esperado - $montosinproteccion_saldo;

              //CAPITAL REGRESADO GUARDARLO EN PAGOS ENCA
              if($comisioncalcul > 0){
                $capital_regresado = $comisiones["capital_regresado"];
                echo "Capital Regresado :".$capital_regresado." | ";
  
                $actualizabandera = DB::update("update tblpagos_enc set capital_regresado = ? where id = ?;",[$capital_regresado, $idpagoencabezado]);
              }

              if($montofaltante < 0)
              {
                $montofaltante = 0;
              }

              // PAGO COMPLETO CON EXCEDENTE
              if($montoreal > $saldo_pagar )
              {
                  echo "<br>"."Pago Completo más Excedente"."<br>";
                  //CHECAMOS CUANTO EXCEDENTE TENEMOS PARA GUARDALO 
                  $montoexcedente = $montoreal-$saldo_pagar;
                  //MONTO REAL = LO QUE RECIBIMOS, MAS EL EXCEDENTE, MAS COMISION
                  $montoreal = $montoreal-$montoexcedente;
                  $montosinproteccion_saldo = $montosinproteccion_saldo-$montoexcedente;
                  echo "<br> | "."Excedente : ".$montoexcedente." Monto Real: ".$montoreal." Monto Sin PS: ".$montosinproteccion_saldo."<br>";
            
                  //GUARDAR EXCEDENTE
                  $insertaexcedente = new excedentes();
                  $insertaexcedente->id_distribuidor = $vardistribuidor;
                  $insertaexcedente->fecha_pago =$varfecha_pago;
                  $insertaexcedente->monto=$montoexcedente;
                  $insertaexcedente->estado="A";
                  $insertaexcedente->created_at=$fecha;
                  $insertaexcedente->save();
                  
                  $pExcedente = 1;
              }
              
              $montoreal = $saldo_pagar;
              if($montoreal == $saldo_pagar)
              {
                //Pago completo normal en fecha de comisiones
                $UpdAt = DB::select('update tblpagos_enc set otrosconceptos2 = "0", costo_transaccion = 16  WHERE id = ?;', [$idpagoencabezado]);
            
                foreach($clientesxdis as $clientes)
                {
                  $UpdAtr = DB::select('update tblprestamos_valesdet set saldo = "0" WHERE id = ?;', [$clientes->id]);
                }

                if($montofaltante > 0){
                  $tipomov="PAGO";
                  $concepto = "CONDONACIÓN DE PAGO CAPTURADO EL ".$varfecha_pago;
                  $descripcion= "PAGO CONDONADO APLICADO AL DISTRIBUIDOR #".$vardistribuidor.", A LA RELACION DEL ".$fecha_relacion;
                  $afectarhistorialcuentas = $this->afectarhistorialcuentaspago($tipo,$cuenta,$montofaltante,$montofaltante,$varfecha_pago,$vardistribuidor,$idpagoencabezado,$tipomov,$concepto,$descripcion); 
                  echo "ENTRO";
                }

                $funcion = $this->insertarPagos($vardistribuidor, $idpagoencabezado, $montosinproteccion_saldo, $saldo_pagar,
                0, $varfecha_pago,$fecha_relacion,$montoreal,  $comisioncalcul, 0, 
                0,"I","I", $montoexcedente , $cuenta, $tipo,$totalcliente, $fecha, $user,0,$tipos, $proteccion_saldo, $montoxtransaccion,$varreferencia,0);
                
                foreach($clientesxdis as $clientes)
                {
                  $UpdAtr = DB::select('update tblprestamos_valesdet set saldo = "0" WHERE id = ?;', [$clientes->id]);
                }

                $montosinproteccion_saldo = $montosinproteccion_saldo-$saldo_pagar;
              
                if($montosinproteccion_saldo < 0)
                {
                  $montosinproteccion_saldo = 0;
                }

                //QUITAMOS DE CONCLIADOS
                $updateenc = DB::update('update tblpagosconcentrado set status = "P" where id_distirbuidor = ? and fecha_relacion = ?;', [$vardistribuidor,$fecha_relacion]);


                if($montofaltante > 0){
                  echo "ENTRO";
                  $afectarhistorialgasto = $this->afectarhistorialgasto($tipo,0,$cuenta,$montofaltante,$varfecha_pago,$vardistribuidor,$idpagoencabezado,"CONDONACIÓN EN EL PAGO");
                  
                  echo "<br> | APLICA REGISTRO DE CONDONACION EN PAGOS CONCENTRADOS";
                  $insertapagoconcentrado = new pagocontrados();
                  $insertapagoconcentrado->id_distirbuidor = $vardistribuidor;
                  $insertapagoconcentrado->saldo_pagar_real = $saldo_pagar;
                  $insertapagoconcentrado->intento_pago = $montofaltante;
                  $insertapagoconcentrado->fecha_relacion = $fecha_relacion;
                  $insertapagoconcentrado->fecha_intento_pago = $varfecha_pago;
                  $insertapagoconcentrado->status = "P";
                  $insertapagoconcentrado->idcuenta = $cuenta;
                  $insertapagoconcentrado->descripcion="CONDONADO";
                  $insertapagoconcentrado->tipocuenta = $tipo;
                  $insertapagoconcentrado->created_at = $fecha;
                  $insertapagoconcentrado->monto_recibido = 0;
                  $insertapagoconcentrado->referencia_pago = "CONDONADO";
                  $insertapagoconcentrado->save();

                }
  
                echo "<br> | MONTO FALTANTE:".$montofaltante;
                $pCompleto = 1;
        
              }

              //AJUSTE DE ESTADOO A PRESTAMOS PAGADOS
              $estadosxplazos = $this->obtenerestadosclientes($vardistribuidor,$fecha_relacion);
              if(!$estadosxplazos->isEmpty()){
                foreach($estadosxplazos as $item){
                  $UpdAt = DB::select('update tblprestamos_valesenc set status = "P" WHERE id = ?;', [$item->id_pres]);
                }
              }

              $montosinproteccion_saldo = intval($montosinproteccion_saldo)-intval($saldo_pagar);
              if($montosinproteccion_saldo <= 0)
              {
                $montosinproteccion_saldo=0;
                $montosinproteccion_saldo= intval($montoexcedente);
                if($montosinproteccion_saldo==0)
                {
                  break;
                }
              }
        }
      }
    }

     if($pCompleto >= 1){
        return redirect()->route('pagosconciliados')->with("successPagoEfectivo","¡Se guardaron los cambios correctamente!");
      }elseif($pExcedente >= 1){
        return redirect()->route('pagosconciliados')->with("successPagoExcedente","¡Se guardaron los cambios correctamente!");
      }elseif($pIncompleto >= 1){
        return redirect()->route('pagosconciliados')->with("pagoIncompleto","¡Se guardaron los cambios correctamente!");
      }elseif($pConciliado >= 1){
        return redirect()->route('pagosconciliados')->with("envioConcilia","¡Se guardaron los cambios correctamente!");
      }else{
        return redirect()->route('pagosconciliados')->with("warningnPago","¡No se guardaron los cambios correctamente!");
      }

  }

}
