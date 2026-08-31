<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\MenuTrait;
use App\Traits\GlobalTraits;
use App\Traits\SistemasTraits;
use App\Traits\DatosimpleTraits;
use Carbon\Carbon;
use App\Models\creditoempleado;
use App\Models\creditosempleadodet;
use App\Models\creditoempleadotemp;
use App\Models\creditosempleadodettemp;
use Illuminate\Support\Facades\File;
use App\Models\solitarCancelacionPresEmp;
use App\Exports\CreditosEmpPend;
use App\Exports\CreditosEmp;
use Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\pagonominaenc;
use App\Models\pagonominadet;
use Illuminate\Support\Number;
use App\Models\cuentas;
use App\Models\historial_cuentas;
use App\Models\Cajas;
use App\Models\historial_cajas;
use Illuminate\Http\UploadedFile;
use DB;

class PrestamoNomController extends Controller
{

  use MenuTrait;
  use DatosimpleTraits;
  use SistemasTraits;
  use GlobalTraits;

  public function __construct()
  {
    $this->middleware('auth');
  }

  public function creditosEmpleados()
  {
    try {
      $varpantallas = $this->Traermenuenc();
      $varsubmenus = $this->Traermenudet();
      $permisos1 = $this->forpermisos('captura_credito_emp');
      $permisos2 = $this->forpermisos('cat_prestamo_emp');
      $permisos3 = $this->forpermisos('global_empleados');
      if ($permisos1 == "captura_credito_emp") {
        $captura_credito_emp = "A";
      } else {
        $captura_credito_emp = "I";
      }
      if ($permisos2 == "cat_prestamo_emp") {
        $cat_prestamo_emp = "A";
      } else {
        $cat_prestamo_emp = "I";
      }
      return view('Prestamosnomina.index', compact('varpantallas', 'varsubmenus', 'captura_credito_emp', 'cat_prestamo_emp', 'permisos3'));
    } catch (\Illuminate\Database\QueryException $ex) {
      return back()->with("warningBD", "no guardado correctamente");
    }
  }

  public function capturacreditonom()
  {
      $idusuario = auth()->user()->id;
      $varpantallas = $this->Traermenuenc();
      $varsubmenus = $this->Traermenudet();
      $varempleados = $this->obtenerempleadosActivos();
      $varcoordinadores = [];//$this->obtenercoordinadores()
      $varPresEnc = $this->obtenerTempPrestEmpEnc();
      $listatas = $this->obtenertasasnomina();
      $cuentas = $this->obtenercuentasActivas();
      $cajas = $this->obtenerCajasActivas();
      $modulo = "prestamos_nominas";
      $varManejoCuentas = $this->obtenerManejoCuentas($idusuario, $modulo);
      $permisos1 = $this->forpermisos("insertartasamanual");
      $permisos2 = $this->forpermisos("insertarplazomanuales");
      $permisos3 = $this->forpermisos('calcular_prestamo_emp');
      $permisos4 = $this->forpermisos('aut_pres_emp');
      $permisos5 = $this->forpermisos('eliminar_captura_cred_emp');
      $permisos6 = $this->forpermisos('editar_captura_cred_emp');
      $permisos7 = $this->forpermisos('descargar_estado_cuenta');
      $permisos8 = $this->forpermisos('exportar_presnom');
      return view('Prestamosnomina.creditos', compact(
        'varpantallas',
        'varsubmenus',
        'varempleados',
        'varcoordinadores',
        'listatas',
        'cuentas',
        'varManejoCuentas',
        'cajas',
        'permisos1',
        'permisos2',
        'permisos3',
        'permisos4',
        'permisos5',
        'permisos6',
        'permisos7',
        'permisos8',
        'varPresEnc'
      ));
    // } catch (\Illuminate\Database\QueryException $ex) {
    //   Log::error("Error en capturacreditonom: " . $ex->getMessage());
    //   return back()->with("warningBD", "no guardado correctamente");
    // }
  }

  public function creditosEmpleadosCatalogo(Request $request)
  {
    try {
      $estado = $request->get('filtro');
      $idusuario = auth()->user()->id;
      $varpantallas = $this->Traermenuenc();
      $varsubmenus = $this->Traermenudet();
      $varempleados = $this->obtenerempleados();
      if ($request->get('filtro') == null) {
        $estado = "A";
      }
      $varPresEnc = $this->obtenerPrestEmpEnc($estado);
      $varCancelSoli = $this->obtener_cancelaciones_solicitadas();
      $varPrestEmpEncCompleto = $this->obtenerPrestEmpEncCompleto();
      $permisos1 = $this->forpermisos('cancel_pres_emp');
      $permisos2 = $this->forpermisos('descargar_estado_cuenta');
      $permisos3 = $this->forpermisos('documentos_cred_emp');
      $permisos4 = $this->forpermisos('exportar_presnom');
      return view('Prestamosnomina.creditosCatalogo', compact('varpantallas', 'varsubmenus', 'varempleados', 'varPresEnc', 'permisos1', 'permisos2', 'permisos3', 'permisos4', 'varPrestEmpEncCompleto', 'varCancelSoli', 'estado'));
    } catch (\Illuminate\Database\QueryException $ex) {
      return back()->with("warningBD", "no guardado correctamente");
    }
  }

  public function guardarprestamonomina(Request $request, string $tipo)
  {
    try {
      $date = Carbon::now();
      $fecha = $date->format('Y-m-d');
      $idusuario = auth()->user()->id;
      $dias = 0;
      $varplazo = 0;
      $totalmeses = 0;
      $intereses = 0;
      $porcentajetasa = 0;
      $total = 0;
      $totalredondeado = 0;
      $ivainteres = 0;
      $ivainteresredondeado = 0;
      $interesredondeado = 0;
      $totalredondeado = 0;
      $pagoquincenal = 0;
      $pagoquincenalred = 0;
      $idultimop = 0;
      $saldo_nuevo = 0;
      $estado = "Null";
      $contadorencabezado = 1;
      $contadordetalle = 0;
      $tipoMessge = "";
      $tipocredito = $request->get('tipocredito');
      $Empleado = $request->get('empleado');
      $Coordinador = $request->get('coordinador');
      $varprimeraquincena = $request->get('fechai');
      $tasa = $request->get('tasa');
      $Monto = $request->get('monto');
      $Tipoplazo = $request->get('tipop');
      $cuenta = $request->get('cuenta');
      $Numeroplazo = $request->get('numplazo');
      $permisos = $this->forpermisos('doble_prestamo_nom');
      $estadoCuenta = $request->file('estadoCuenta');
      $pagare = $request->file('pagare');
      $factura = $request->file('factura');
      $estatusEntregadoFactura = "no_entregado";

      if ($permisos == "doble_prestamo_nom") {
        $permiso = "si";
      } else {
        $permiso = "no";
      }
      $permisos1 = $this->forpermisos('doble_prestamo_nom');

      $tipocuenta = $request->get('tipo');
      if ($tipocuenta == "CAJA") {
        $cuenta = $request->get('caja');
      } else {
        $cuenta = $request->get('cuenta');
      }

      if ($tipocredito != "" && $Empleado != "" && $Coordinador != "" && $varprimeraquincena != "" && $tasa != "" && $Monto != "" && $Tipoplazo != "" && $Numeroplazo != "" && $cuenta != "" && $tipocuenta != "") {
        $varPresEmp = $this->validaPrestEmp($Empleado, $permiso, $tipocredito);
        $varPresEmpTemp = $this->validaPrestEmpTemp($Empleado, $permiso, $tipocredito);
        $fechaFormat = Carbon::createFromFormat('Y-m-d', $varprimeraquincena);
        $diaquin = $fechaFormat->format('d');
        $añoprimeraquincena = substr($varprimeraquincena, 0, 4);
        $mesprimeraquincena = substr($varprimeraquincena, 5, -3);
        $diaprimeraquincena = substr($varprimeraquincena, 8, 10);
        if ($tipo == "autorizar") {
          $estado = "A";
        } else {
          $estado = "P";
        }

        if ($estado == "A") {

          if ($estadoCuenta == null || $pagare == null) {
            Log::error("No estan llenos todos los campos estado cuenta o pagaré");
            return back()->with("required", "No estan llenos todos los campos");
          }

          if ($tipocredito == "automovil") {
            if ($factura == null) {
              Log::error("No estan llenos todos los campos factura");
              return back()->with("required", "Falta subir la factura");
            }
            $estatusEntregadoFactura = "entregado";
          }
        }

        if ($tipocuenta == "CAJA") {
          $varcajas = $this->obtenerCajasxId($cuenta);
          foreach ($varcajas as $cajas) {
            $saldoCuenta = $cajas->saldo_actual;
          }
        } else {
          $varobtenercuentas = $this->obtenercuentasPrincipales($cuenta);
          foreach ($varobtenercuentas as $varobtenercuenta) {
            $saldoCuenta = $varobtenercuenta->saldo_actual;
          }
        }

        if ($varPresEmp->isEmpty() && $permisos1 == "doble_prestamo_nom") {
          if ($varPresEmpTemp->isEmpty() && $permisos1 == "doble_prestamo_nom") {
            if ($Monto <= $saldoCuenta) {
              //if ($diaquin == 15 || $diaquin == 28 || $diaquin == 29 || $diaquin == 30 || $diaquin == 31) {
              if (true) {
                $listatas = $this->obtenertasaxid($tasa);
                foreach ($listatas as $dt1) {
                  $porcentajetasa = $dt1->porcentaje;
                }

                //obtenemos los intereses del credito como sera pago quincenal puede ser asi ejemplo los 25,000*2.5/100*meses
                //obtenemos el total de los meses del credito
                $totalmeses = $Numeroplazo / 2;
                $intereses = (($Monto * $porcentajetasa) / 100);
                //interes y iva de interes
                $intereses = $intereses * $totalmeses;
                $ivainteres = $intereses * .16;
                //  //interesredondeado
                $interesredondeado = ceil($intereses);
                $ivainteresredondeado = ceil($intereses * .16);

                //Monto total
                $total = $Monto + $intereses + $ivainteres;
                $totalredondeado = ceil($Monto + $interesredondeado + $ivainteresredondeado);

                $pagoquincenal = $total / $Numeroplazo;
                $pagoquincenalred = ceil($pagoquincenal);
                $pagototal = $pagoquincenalred * $Numeroplazo;
                $otrosExtra = $pagototal - ($Monto + $intereses + $ivainteres);
                $saldo_nuevo = $pagototal;


                if ($estado == "A") {
                  $insercredito = new creditoempleado();
                  $insercredito->id_empleado = $Empleado;
                  $insercredito->idcoordinador = $Coordinador;
                  $insercredito->idtasa = $tasa;
                  $insercredito->tipo_credito = $tipocredito;
                  $insercredito->estado = $estado;
                  $insercredito->monto = $Monto;
                  $insercredito->interes = $intereses;
                  $insercredito->ivainteres = $ivainteres;
                  $insercredito->otros = $otrosExtra;
                  $insercredito->total = $pagototal;
                  $insercredito->fecha_inicio = $varprimeraquincena;
                  if (!is_null($request->get('comentario'))) {
                    $insercredito->comentario = $request->get('comentario');
                    $insercredito->status_comentario = "1";
                  } else {
                    $insercredito->status_comentario = "0";
                  }

                  $insercredito->tipo_cuenta = $tipocuenta;
                  if ($tipocuenta == "CAJA") {
                    $insercredito->id_caja = $cuenta;
                  } else {
                    $insercredito->id_cuenta = $cuenta;
                  }

                  $insercredito->fecha_fin = "";
                  $insercredito->tipo_plazo = $Tipoplazo;
                  $insercredito->plazos = $Numeroplazo;
                  $insercredito->interesredondeado = $interesredondeado;
                  $insercredito->ivainteresredondeado = $ivainteresredondeado;
                  $insercredito->totalredondeado = $totalredondeado;
                  $insercredito->created_by = auth()->user()->name;
                  $insercredito->save();

                  $ultomocre = $this->obtenerultimpresnom();
                  foreach ($ultomocre as $idultimopr) {
                    $idultimop = $idultimopr->id;
                  }

                  $this->subirComprobantes($idultimop, $Empleado, $tipocredito, $estadoCuenta, $pagare, $factura, "entregado", "entregado", $estatusEntregadoFactura);

                } else {

                  $insercredito = new creditoempleadotemp();
                  $insercredito->id_empleado = $Empleado;
                  $insercredito->idcoordinador = $Coordinador;
                  $insercredito->idtasa = $tasa;
                  $insercredito->tipo_credito = $tipocredito;
                  $insercredito->estado = $estado;
                  $insercredito->monto = $Monto;
                  $insercredito->interes = $intereses;
                  $insercredito->ivainteres = $ivainteres;
                  $insercredito->otros = $otrosExtra;
                  $insercredito->total = $pagototal;
                  $insercredito->fecha_inicio = $varprimeraquincena;
                  $insercredito->fecha_fin = "";
                  if (!is_null($request->get('comentario'))) {
                    $insercredito->comentario = $request->get('comentario');
                    $insercredito->status_comentario = "1";
                  } else {
                    $insercredito->status_comentario = "0";
                  }

                  $insercredito->tipo_cuenta = $tipocuenta;
                  if ($tipocuenta == "CAJA") {
                    $insercredito->id_caja = $cuenta;
                  } else {
                    $insercredito->id_cuenta = $cuenta;
                  }

                  $insercredito->tipo_plazo = $Tipoplazo;
                  $insercredito->plazos = $Numeroplazo;
                  $insercredito->interesredondeado = $interesredondeado;
                  $insercredito->ivainteresredondeado = $ivainteresredondeado;
                  $insercredito->totalredondeado = $totalredondeado;
                  $insercredito->created_by = auth()->user()->name;
                  $insercredito->save();

                  $ultomocre = $this->obtenerultimpresnomtemp();
                  foreach ($ultomocre as $idultimopr) {
                    $idultimop = $idultimopr->id;
                  }

                  if ($tipocredito == "automovil" && $factura == null) {
                    $tipocredito = "no aplica";  // para que el metodo subirComprobantes no requiera la factura cuando es un calculo en vez de autorizado
                  }

                  $this->subirComprobantes($idultimop, $Empleado, $tipocredito, $estadoCuenta, $pagare, $factura, "entregado", "entregado", $estatusEntregadoFactura, "temp");
                }


                for ($i = 0; $i < $Numeroplazo; $i++) {
                  $saldo_nuevo = $saldo_nuevo - $pagoquincenalred;
                  if ($diaprimeraquincena == 15) {
                    $varplazo = $varplazo + 1;
                    $varprimeraquincena = $añoprimeraquincena . '-' . $mesprimeraquincena . '-15';
                    $varprimeraquincena = substr(Carbon::createFromFormat('Y-m-d', $varprimeraquincena), 0, 10);
                    $diaprimeraquincena = 30;
                    $contadordetalle = $contadorencabezado + 1;

                    if ($estado == "A") {
                      $inserpresdet = new creditosempleadodet();
                      $inserpresdet->id_credito = $idultimop;
                      $inserpresdet->plazo = $varplazo;
                      $inserpresdet->pago_quincenal = $pagoquincenalred;
                      $inserpresdet->pago_total = $pagototal;
                      $inserpresdet->fecha_pago = $varprimeraquincena;
                      $inserpresdet->otrosconceptos1 = $Monto / $Numeroplazo;
                      $inserpresdet->otrosconceptos2 = $intereses / $Numeroplazo;
                      $inserpresdet->otrosconceptos3 = $ivainteres / $Numeroplazo;
                      $inserpresdet->monto = $Monto;
                      $inserpresdet->saldo_nuevo = $saldo_nuevo;
                      $inserpresdet->estado = $estado;
                      $inserpresdet->fecha_saldado = "null";
                      $inserpresdet->created_by = auth()->user()->name;
                      $inserpresdet->save();
                      $tipoMessge = "successAutorizado";
                    } else {

                      $inserpresdet = new creditosempleadodettemp();
                      $inserpresdet->id_credito = $idultimop;
                      $inserpresdet->plazo = $varplazo;
                      $inserpresdet->pago_quincenal = $pagoquincenalred;
                      $inserpresdet->pago_total = $pagototal;
                      $inserpresdet->fecha_pago = $varprimeraquincena;
                      $inserpresdet->otrosconceptos1 = $Monto / $Numeroplazo;
                      $inserpresdet->otrosconceptos2 = $intereses / $Numeroplazo;
                      $inserpresdet->otrosconceptos3 = $ivainteres / $Numeroplazo;
                      $inserpresdet->monto = $Monto;
                      $inserpresdet->saldo_nuevo = $saldo_nuevo;
                      $inserpresdet->estado = $estado;
                      $inserpresdet->fecha_saldado = "null";
                      $inserpresdet->created_by = auth()->user()->name;
                      $inserpresdet->save();
                      $tipoMessge = "successPendiente";
                    }

                    $diaprimeraquincena == 31;
                  } else {
                    // if($mesprimeraquincena==13)
                    // {
                    //   if($mesprimeraquincena==1){
                    //     $mesprimeraquincena =  "0".$mesprimeraquincena;
                    //   }
                    //   else
                    //   {
                    //     $mesprimeraquincena=  $mesprimeraquincena;
                    //   }
                    //   $añoprimeraquincena=$añoprimeraquincena+1;

                    // }

                    if ($mesprimeraquincena == 13) {
                      $mesprimeraquincena = 01;
                      $añoprimeraquincena = $añoprimeraquincena + 1;
                    }

                    $dias = cal_days_in_month(CAL_GREGORIAN, $mesprimeraquincena, $añoprimeraquincena);
                    if ($dias == 28) {
                      $dias = 28;
                    }
                    if ($dias == 29) {
                      $dias = 29;
                    }
                    if ($dias == 30) {
                      $dias = 30;
                    }
                    if ($dias == 31) {
                      $dias = 31;
                    }

                    $varprimeraquincena = $añoprimeraquincena . '-' . $mesprimeraquincena . '-' . $dias;
                    $varprimeraquincena = substr(Carbon::createFromFormat('Y-m-d', $varprimeraquincena), 0, 10);
                    $mesprimeraquincena = $mesprimeraquincena + 1;
                    $diaprimeraquincena = 15;
                    $varplazo = $varplazo + 1;

                    if ($estado == "A") {
                      $ultomocre = $this->obtenerultimpresnom();
                      foreach ($ultomocre as $idultimopr) {
                        $idultimop = $idultimopr->id;
                      }

                      $inserpresdet = new creditosempleadodet();
                      $inserpresdet->id_credito = $idultimop;
                      $inserpresdet->plazo = $varplazo;
                      $inserpresdet->pago_quincenal = $pagoquincenalred;
                      $inserpresdet->pago_total = $pagototal;
                      $inserpresdet->fecha_pago = $varprimeraquincena;
                      $inserpresdet->otrosconceptos1 = $Monto / $Numeroplazo;
                      $inserpresdet->otrosconceptos2 = $intereses / $Numeroplazo;
                      $inserpresdet->otrosconceptos3 = $ivainteres / $Numeroplazo;
                      $inserpresdet->monto = $Monto;
                      $inserpresdet->saldo_nuevo = $saldo_nuevo;
                      $inserpresdet->estado = $estado;
                      $inserpresdet->fecha_saldado = "null";
                      $inserpresdet->save();
                      $tipoMessge = "successAutorizado";
                    } else {
                      $ultomocre = $this->obtenerultimpresnomtemp();
                      foreach ($ultomocre as $idultimopr) {
                        $idultimop = $idultimopr->id;
                      }
                      $inserpresdet = new creditosempleadodettemp();
                      $inserpresdet->id_credito = $idultimop;
                      $inserpresdet->plazo = $varplazo;
                      $inserpresdet->pago_quincenal = $pagoquincenalred;
                      $inserpresdet->pago_total = $pagototal;
                      $inserpresdet->fecha_pago = $varprimeraquincena;
                      $inserpresdet->otrosconceptos1 = $Monto / $Numeroplazo;
                      $inserpresdet->otrosconceptos2 = $intereses / $Numeroplazo;
                      $inserpresdet->otrosconceptos3 = $ivainteres / $Numeroplazo;
                      $inserpresdet->monto = $Monto;
                      $inserpresdet->saldo_nuevo = $saldo_nuevo;
                      $inserpresdet->estado = $estado;
                      $inserpresdet->fecha_saldado = "null";
                      $inserpresdet->save();
                      $tipoMessge = "successPendiente";
                    }

                    // $diaprimeraquincena == 15;
                  }
                }

                if ($estado == "A") {
                  //DESCUENTO DE CUENTAS

                  if ($tipocuenta == "CAJA") {
                    $cuentas = Cajas::find($cuenta);
                    $cuentas->saldo_actual = $saldoCuenta - $Monto;
                    $cuentas->updated_at = $fecha;
                    $cuentas->updated_by = auth()->user()->name;
                    $cuentas->save();

                    $historialcuent = new historial_cajas();
                    $historialcuent->id_caja = $cuenta;
                    $historialcuent->id_empleado = auth()->user()->idempleado;
                    $historialcuent->estado = "A";
                    $historialcuent->tipo_movimiento = "DESEMBOLSO";
                    $historialcuent->concepto = "DESEMBOLSO DE CREDITO A EMPLEADO - TIPO " . strtoupper($tipocredito) . " CREDITO #" . $idultimop;
                    $historialcuent->responsable = "EMPLEADO #" . $Empleado;
                    $historialcuent->ingreso = 0;
                    $historialcuent->egreso = $Monto;
                    $historialcuent->saldo = $saldoCuenta - $Monto;
                    $historialcuent->numero_referencia = $idultimop;
                    $historialcuent->tipo_referencia = "prestamo_emp";
                    $historialcuent->numero_poliza = 0;
                    $historialcuent->fecha = $fecha;
                    $historialcuent->created_by = auth()->user()->name;
                    $historialcuent->save();
                    $tipoMessge = "success";
                  } else {
                    $cuentas = cuentas::find($cuenta);
                    $cuentas->saldo_actual = $saldoCuenta - $Monto;
                    $cuentas->updated_at = $date;
                    $cuentas->updated_by = auth()->user()->name;
                    $cuentas->save();

                    $historialcuent = new historial_cuentas();
                    $historialcuent->id_cuenta = $cuenta;
                    $historialcuent->id_empleado = auth()->user()->idempleado;
                    $historialcuent->estado = "A";
                    $historialcuent->tipo_movimiento = "DESEMBOLSO";
                    $historialcuent->concepto = "DESEMBOLSO DE CREDITO A EMPLEADO - TIPO " . strtoupper($tipocredito) . " CREDITO #" . $idultimop;
                    $historialcuent->responsable = "EMPLEADO #" . $Empleado;
                    $historialcuent->ingreso = 0;
                    $historialcuent->egreso = $Monto;
                    $historialcuent->saldo = $saldoCuenta - $Monto;
                    $historialcuent->numero_referencia = $idultimop;
                    $historialcuent->tipo_referencia = "prestamo_emp";
                    $historialcuent->numero_poliza = 0;
                    $historialcuent->fecha = $fecha;
                    $historialcuent->created_by = auth()->user()->name;
                    $historialcuent->save();

                    $tipoMessge = "success";
                  }
                }

                return back()->with($tipoMessge, "MensajeInsertExitoso");

              } else {
                return back()->with("warningDia", "Día no permitido");
              }
            } else {
              return back()->with("saldoInsuficiente", "no valido");
            }
          } else {
            return back()->with("ExistePres", "Ya existe un prestamo calculado");
          }
        } else {
          return back()->with("ExistePres", "Ya existe un prestamo calculado");
        }
      } else {
        Log::error("No estan llenos todos los campos ultimo");
        return back()->with("required", "No estan llenos todos los campos");
      }
    } catch (\Illuminate\Database\QueryException $ex) {
      return back()->with("warningBD", "no guardado correctamente");
    }
  }


  public function editarprestamonomina(Request $request, string $tipo)
  {
    try {
      $date = Carbon::now();
      $fecha = $date->format('Y-m-d');
      $idusuario = auth()->user()->id;
      $dias = 0;
      $varplazo = 0;
      $totalmeses = 0;
      $intereses = 0;
      $porcentajetasa = 0;
      $total = 0;
      $totalredondeado = 0;
      $ivainteres = 0;
      $ivainteresredondeado = 0;
      $interesredondeado = 0;
      $totalredondeado = 0;
      $pagoquincenal = 0;
      $pagoquincenalred = 0;
      $idultimop = 0;
      $saldo_nuevo = 0;
      $estado = "Null";
      $contadorencabezado = 1;
      $contadordetalle = 0;
      $tipoMessge = "";
      $credito = $request->get('id_credito');
      $tipocredito = $request->get('tipocredito');
      $Empleado = $request->get('empleado');
      $Coordinador = $request->get('coordinador');
      $varprimeraquincena = $request->get('fechai');
      $tasa = $request->get('tasa');
      $Monto = $request->get('monto');
      $Tipoplazo = $request->get('tipop');
      $Numeroplazo = $request->get('numplazo');
      $tipocuenta = $request->get('tipo2');
      $estadoCuenta = $request->file('estadoCuentaEdit');
      $pagare = $request->file('pagareEdit');
      $factura = $request->file('facturaEdit');
      $estatusEntregadoFactura = "no_entregado";

      if ($tipocuenta == "CAJA") {
        $cuenta = $request->get('caja');
      } else {
        $cuenta = $request->get('cuenta');
      }

      if ($credito != "" && $tipocredito != "" && $Empleado != "" && $Coordinador != "" && $varprimeraquincena != "" && $tasa != "" && $Monto != "" && $Tipoplazo != "" && $cuenta != "" && $Numeroplazo != "" && $tipocuenta != "") {
        $varPresEmp = $this->obtenerPrestEmp($Empleado);
        $fechaFormat = Carbon::createFromFormat('Y-m-d', $varprimeraquincena);
        $añoprimeraquincena = substr($varprimeraquincena, 0, 4);
        $mesprimeraquincena = substr($varprimeraquincena, 5, -3);
        $diaprimeraquincena = substr($varprimeraquincena, 8, 10);
        if ($tipo == "autorizar") {
          $estado = "A";
        } else {
          $estado = "P";
        }

        if ($tipocuenta == "CAJA") {
          $varcajas = $this->obtenerCajasxId($cuenta);
          foreach ($varcajas as $cajas) {
            $saldoCuenta = $cajas->saldo_actual;
          }
        } else {
          $varobtenercuentas = $this->obtenercuentasPrincipales($cuenta);
          foreach ($varobtenercuentas as $varobtenercuenta) {
            $saldoCuenta = $varobtenercuenta->saldo_actual;
          }
        }

        $prestamoOld = creditoempleadotemp::find($credito);

        if ($estado == "A") {
          if ($prestamoOld->estado_cuenta == null && $estadoCuenta == null) {
            return back()->with("required", "No estan llenos todos los campos");
          }

          if ($prestamoOld->pagare == null && $pagare == null) {
            return back()->with("required", "No estan llenos todos los campos");
          }

          if ($prestamoOld->factura == null && $factura == null && $tipocredito == "automovil") {
            return back()->with("required", "No estan llenos todos los campos");
          }
        }

        if ($Monto <= $saldoCuenta) {
          //if ($diaquin == 15 || $diaquin == 28 || $diaquin == 29 || $diaquin == 30 || $diaquin == 31) {
          if (true) {
            $listatas = $this->obtenertasaxid($tasa);
            foreach ($listatas as $dt1) {
              $porcentajetasa = $dt1->porcentaje;
            }

            //obtenemos los intereses del credito como sera pago quincenal puede ser asi ejemplo los 25,000*2.5/100*meses
            //obtenemos el total de los meses del credito
            $totalmeses = $Numeroplazo / 2;
            $intereses = (($Monto * $porcentajetasa) / 100);
            //interes y iva de interes
            $intereses = $intereses * $totalmeses;
            $ivainteres = $intereses * .16;
            //  interesredondeado
            $interesredondeado = ceil($intereses);
            $ivainteresredondeado = ceil($intereses * .16);

            //Monto total
            $total = $Monto + $intereses + $ivainteres;
            $totalredondeado = ceil($Monto + $interesredondeado + $ivainteresredondeado);
            //DB::select('delete from temptblcreditos_empleado_det where id_credito = ? ', [$credito]);
            //DB::select('delete from temptblcreditos_empleado where id = ? ', [$credito]);

            $pagoquincenal = $total / $Numeroplazo;
            $pagoquincenalred = ceil($pagoquincenal);
            $pagototal = $pagoquincenalred * $Numeroplazo;
            $otrosExtra = $pagototal - ($Monto + $intereses + $ivainteres);
            $saldo_nuevo = $pagototal;

            if ($estado == "A") {
              $insercredito = new creditoempleado();
              $insercredito->id_empleado = $Empleado;
              $insercredito->idcoordinador = $Coordinador;
              $insercredito->idtasa = $tasa;
              $insercredito->tipo_credito = $tipocredito;
              $insercredito->estado = $estado;
              if (!is_null($request->get('comentario'))) {
                $insercredito->comentario = $request->get('comentario');
                $insercredito->status_comentario = "1";
              } else {
                $insercredito->status_comentario = "0";
              }

              $insercredito->tipo_cuenta = $tipocuenta;
              if ($tipocuenta == "CAJA") {
                $insercredito->id_caja = $cuenta;
                $insercredito->id_cuenta = NULL;
              } else {
                $insercredito->id_cuenta = $cuenta;
                $insercredito->id_caja = NULL;
              }
              $insercredito->monto = $Monto;
              $insercredito->interes = $intereses;
              $insercredito->ivainteres = $ivainteres;
              $insercredito->otros = $otrosExtra;
              $insercredito->total = $pagototal;
              $insercredito->fecha_inicio = $varprimeraquincena;
              $insercredito->fecha_fin = "";
              $insercredito->tipo_plazo = $Tipoplazo;
              $insercredito->plazos = $Numeroplazo;
              $insercredito->interesredondeado = $interesredondeado;
              $insercredito->ivainteresredondeado = $ivainteresredondeado;
              $insercredito->totalredondeado = $totalredondeado;
              $insercredito->created_by = auth()->user()->name;
              $insercredito->save();

              $ultomocre = $this->obtenerultimpresnom();
              foreach ($ultomocre as $idultimopr) {
                $idultimop = $idultimopr->id;
              }

              $prestamoNew = creditoempleado::find($idultimop);

              $this->moverComprobantesTemp($prestamoNew, $prestamoOld);

              $this->subirComprobantes($idultimop, $Empleado, $tipocredito, $estadoCuenta, $pagare, $factura, "entregado", "entregado", $estatusEntregadoFactura);


            } else {
              $insercredito = new creditoempleadotemp();
              $insercredito->id_empleado = $Empleado;
              $insercredito->idcoordinador = $Coordinador;
              $insercredito->idtasa = $tasa;
              $insercredito->tipo_credito = $tipocredito;
              $insercredito->estado = $estado;
              if (!is_null($request->get('comentario'))) {
                $insercredito->comentario = $request->get('comentario');
                $insercredito->status_comentario = "1";
              } else {
                $insercredito->status_comentario = "0";
              }

              $insercredito->tipo_cuenta = $tipocuenta;
              if ($tipocuenta == "CAJA") {
                $insercredito->id_caja = $cuenta;
                $insercredito->id_cuenta = NULL;
              } else {
                $insercredito->id_cuenta = $cuenta;
                $insercredito->id_caja = NULL;
              }

              $insercredito->monto = $Monto;
              $insercredito->interes = $intereses;
              $insercredito->ivainteres = $ivainteres;
              $insercredito->otros = $otrosExtra;
              $insercredito->total = $pagototal;
              $insercredito->fecha_inicio = $varprimeraquincena;
              $insercredito->fecha_fin = "";
              $insercredito->tipo_plazo = $Tipoplazo;
              $insercredito->plazos = $Numeroplazo;
              $insercredito->interesredondeado = $interesredondeado;
              $insercredito->ivainteresredondeado = $ivainteresredondeado;
              $insercredito->totalredondeado = $totalredondeado;
              $insercredito->created_by = auth()->user()->name;
              $insercredito->save();

              $ultomocre = $this->obtenerultimpresnomtemp();
              foreach ($ultomocre as $idultimopr) {
                $idultimop = $idultimopr->id;
              }

              if ($tipocredito == "automovil" && $factura == null) {
                $tipocredito = "no aplica";  // para que el metodo subirComprobantes no requiera la factura cuando es un calculo en vez de autorizado
              }

              $prestamoNew = creditoempleadotemp::find($idultimop);

              $this->moverComprobantesTemp($prestamoNew, $prestamoOld, "temp");

              $this->subirComprobantes($idultimop, $Empleado, $tipocredito, $estadoCuenta, $pagare, $factura, "entregado", "entregado", $estatusEntregadoFactura, "temp");

            }

            DB::select('delete from 	temptblcreditos_empleado_det where id_credito = ? ', [$credito]);
            DB::select('delete from temptblcreditos_empleado where id = ? ', [$credito]);

            for ($i = 0; $i < $Numeroplazo; $i++) {
              $saldo_nuevo = $saldo_nuevo - $pagoquincenalred;
              if ($diaprimeraquincena == 15) {
                $varplazo = $varplazo + 1;
                $varprimeraquincena = $añoprimeraquincena . '-' . $mesprimeraquincena . '-15';
                $varprimeraquincena = substr(Carbon::createFromFormat('Y-m-d', $varprimeraquincena), 0, 10);
                $diaprimeraquincena = 30;
                $contadordetalle = $contadorencabezado + 1;

                if ($estado == "A") {

                  $inserpresdet = new creditosempleadodet();
                  $inserpresdet->id_credito = $idultimop;
                  $inserpresdet->plazo = $varplazo;
                  $inserpresdet->pago_quincenal = $pagoquincenalred;
                  $inserpresdet->pago_total = $pagototal;
                  $inserpresdet->fecha_pago = $varprimeraquincena;
                  $inserpresdet->otrosconceptos1 = $Monto / $Numeroplazo;
                  $inserpresdet->otrosconceptos2 = $intereses / $Numeroplazo;
                  $inserpresdet->otrosconceptos3 = $ivainteres / $Numeroplazo;
                  $inserpresdet->monto = $Monto;
                  $inserpresdet->saldo_nuevo = $saldo_nuevo;
                  $inserpresdet->estado = $estado;
                  $inserpresdet->fecha_saldado = "null";
                  $inserpresdet->created_by = auth()->user()->name;
                  $inserpresdet->save();
                  $tipoMessge = "successAutorizado";
                } else {
                  $inserpresdet = new creditosempleadodettemp();
                  $inserpresdet->id_credito = $idultimop;
                  $inserpresdet->plazo = $varplazo;
                  $inserpresdet->pago_quincenal = $pagoquincenalred;
                  $inserpresdet->pago_total = $pagototal;
                  $inserpresdet->fecha_pago = $varprimeraquincena;
                  $inserpresdet->otrosconceptos1 = $Monto / $Numeroplazo;
                  $inserpresdet->otrosconceptos2 = $intereses / $Numeroplazo;
                  $inserpresdet->otrosconceptos3 = $ivainteres / $Numeroplazo;
                  $inserpresdet->monto = $Monto;
                  $inserpresdet->saldo_nuevo = $saldo_nuevo;
                  $inserpresdet->estado = $estado;
                  $inserpresdet->fecha_saldado = "null";
                  $inserpresdet->created_by = auth()->user()->name;
                  $inserpresdet->save();
                }

                $diaprimeraquincena == 31;
              } else {
                if ($mesprimeraquincena == 13) {
                  $mesprimeraquincena = 01;
                  $añoprimeraquincena = $añoprimeraquincena + 1;
                }

                $dias = cal_days_in_month(CAL_GREGORIAN, $mesprimeraquincena, $añoprimeraquincena);
                if ($dias == 28) {
                  $dias = 28;
                }
                if ($dias == 29) {
                  $dias = 29;
                }
                if ($dias == 30) {
                  $dias = 30;
                }
                if ($dias == 31) {
                  $dias = 31;
                }

                $varprimeraquincena = $añoprimeraquincena . '-' . $mesprimeraquincena . '-' . $dias;
                $varprimeraquincena = substr(Carbon::createFromFormat('Y-m-d', $varprimeraquincena), 0, 10);
                $mesprimeraquincena = $mesprimeraquincena + 1;
                $diaprimeraquincena = 15;
                $varplazo = $varplazo + 1;

                if ($estado == "A") {
                  $ultomocre = $this->obtenerultimpresnom();
                  foreach ($ultomocre as $idultimopr) {
                    $idultimop = $idultimopr->id;
                  }

                  $inserpresdet = new creditosempleadodet();
                  $inserpresdet->id_credito = $idultimop;
                  $inserpresdet->plazo = $varplazo;
                  $inserpresdet->pago_quincenal = $pagoquincenalred;
                  $inserpresdet->pago_total = $pagototal;
                  $inserpresdet->fecha_pago = $varprimeraquincena;
                  $inserpresdet->otrosconceptos1 = $Monto / $Numeroplazo;
                  $inserpresdet->otrosconceptos2 = $intereses / $Numeroplazo;
                  $inserpresdet->otrosconceptos3 = $ivainteres / $Numeroplazo;
                  $inserpresdet->monto = $Monto;
                  $inserpresdet->saldo_nuevo = $saldo_nuevo;
                  $inserpresdet->estado = $estado;
                  $inserpresdet->fecha_saldado = "null";
                  $inserpresdet->save();
                  $tipoMessge = "successAutorizado";
                } else {
                  $ultomocre = $this->obtenerultimpresnomtemp();
                  foreach ($ultomocre as $idultimopr) {
                    $idultimop = $idultimopr->id;
                  }
                  $inserpresdet = new creditosempleadodettemp();
                  $inserpresdet->id_credito = $idultimop;
                  $inserpresdet->plazo = $varplazo;
                  $inserpresdet->pago_quincenal = $pagoquincenalred;
                  $inserpresdet->pago_total = $pagototal;
                  $inserpresdet->fecha_pago = $varprimeraquincena;
                  $inserpresdet->otrosconceptos1 = $Monto / $Numeroplazo;
                  $inserpresdet->otrosconceptos2 = $intereses / $Numeroplazo;
                  $inserpresdet->otrosconceptos3 = $ivainteres / $Numeroplazo;
                  $inserpresdet->monto = $Monto;
                  $inserpresdet->saldo_nuevo = $saldo_nuevo;
                  $inserpresdet->estado = $estado;
                  $inserpresdet->fecha_saldado = "null";
                  $inserpresdet->save();
                  $tipoMessge = "successPendiente";
                }
              }
            }

            if ($estado == "A") {
              if ($tipocuenta == "CAJA") {
                $cuentas = Cajas::find($cuenta);
                $cuentas->saldo_actual = $saldoCuenta - $Monto;
                $cuentas->updated_at = $fecha;
                $cuentas->updated_by = auth()->user()->name;
                $cuentas->save();

                $historialcuent = new historial_cajas();
                $historialcuent->id_caja = $cuenta;
                $historialcuent->id_empleado = auth()->user()->idempleado;
                $historialcuent->estado = "A";
                $historialcuent->tipo_movimiento = "DESEMBOLSO";
                $historialcuent->concepto = "DESEMBOLSO DE CREDITO A EMPLEADO - TIPO " . strtoupper($tipocredito) . " CREDITO #" . $idultimop;
                $historialcuent->responsable = "EMPLEADO #" . $Empleado;
                $historialcuent->ingreso = 0;
                $historialcuent->egreso = $Monto;
                $historialcuent->saldo = $saldoCuenta - $Monto;
                $historialcuent->numero_referencia = $idultimop;
                $historialcuent->tipo_referencia = "prestamo_emp";
                $historialcuent->numero_poliza = 0;
                $historialcuent->fecha = $fecha;
                $historialcuent->created_by = auth()->user()->name;
                $historialcuent->save();
              } else {
                $cuentas = cuentas::find($cuenta);
                $cuentas->saldo_actual = $saldoCuenta - $Monto;
                $cuentas->updated_at = $fecha;
                $cuentas->updated_by = auth()->user()->name;
                $cuentas->save();

                $historialcuent = new historial_cuentas();
                $historialcuent->id_cuenta = $cuenta;
                $historialcuent->id_empleado = auth()->user()->idempleado;
                $historialcuent->estado = "A";
                $historialcuent->tipo_movimiento = "DESEMBOLSO";
                $historialcuent->concepto = "DESEMBOLSO DE CREDITO A EMPLEADO - TIPO " . strtoupper($tipocredito) . " CREDITO #" . $idultimop;
                $historialcuent->responsable = "EMPLEADO #" . $Empleado;
                $historialcuent->ingreso = 0;
                $historialcuent->egreso = $Monto;
                $historialcuent->saldo = $saldoCuenta - $Monto;
                $historialcuent->numero_referencia = $idultimop;
                $historialcuent->tipo_referencia = "prestamo_emp";
                $historialcuent->numero_poliza = 0;
                $historialcuent->fecha = $fecha;
                $historialcuent->created_by = auth()->user()->name;
                $historialcuent->save();
              }
            }

            File::deleteDirectory("Expedientes/prestamosEmpleados/EMP_" . $prestamoOld->id_empleado . "/PRES_" . $prestamoOld->id);

            return back()->with("success", "MensajeInsertExitoso");
          } else {
            return back()->with("warningDia", "Día no permitido");
          }
        } else {
          return back()->with("saldoInsuficiente", "no valido");
        }
      } else {
        return back()->with("required", "No estan llenos todos los campos");
      }
    } catch (\Illuminate\Database\QueryException $ex) {
      return back()->with("warningBD", "no guardado correctamente");
    }

  }

  public function eliminarprestamonomina(int $credito)
  {
    try {
      $Borrartbl1 = DB::select('delete from 	temptblcreditos_empleado_det where id_credito = ? ', [$credito]);
      $Borrartbl2 = DB::select('delete from temptblcreditos_empleado where id = ? ', [$credito]);
      return back()->with("success", "Prestamo eliminado");
    } catch (\Illuminate\Database\QueryException $ex) {
      return back()->with("warningBD", "no guardado correctamente");
    }
  }

  public function detallePrestamoTemp(string $tipo, int $idpres, int $idcord)
  {
    try {
      $idusuario = auth()->user()->id;
      $varpantallas = $this->Traermenuenc();
      $varsubmenus = $this->Traermenudet();
      $varempleados = $this->obtenerempleados();
      $listatas = $this->obtenertasasnomina();
      $modulo = "prestamos_nominas";
      $varManejoCuentas = $this->obtenerManejoCuentas($idusuario, $modulo);
      $permisos1 = $this->forpermisos('aplicar_pago_prestamosEmp');
      $pago_total = 0;

      if ($tipo == "pendientes") {
        $varPresDet = $this->obtenerTempPrestEmpDet($idpres);
        $cuentas = 0;
      } else {
        $varPresDet = $this->obtenerPrestEmpDet($idpres);
        $cuentas = $this->obtenercuentasActivas();
      }

      foreach ($varPresDet as $pres) {
        $nombre = $pres->Nombre;
        $id_empleado = $pres->id_empleado;
      }

      foreach ($varPresDet as $pr) {
        $coordinador = $pr->coordinador;
        $coordinadorTel = $pr->coordinadorTel;
        $monto = $pr->monto;
        $pago_total = $pr->pago_total;
        break;
      }

      $pagos = 0.00;
      foreach ($varPresDet as $item) {
        if ($item->estado == "S") {
          $pagos = doubleval($pagos + $item->pago_quincenal);
        }
      }

      $pagos = doubleval($pago_total - $pagos);
      $cuentas = $this->obtenercuentasActivas();
      $cajas = $this->obtenerCajasActivas();

      return view('Prestamosnomina.creditosDetalle', compact(
        'varpantallas',
        'varsubmenus',
        'varempleados',
        'listatas',
        'coordinador',
        'coordinadorTel',
        'monto',
        'pago_total',
        'pagos',
        'varPresDet',
        'nombre',
        'id_empleado',
        'tipo',
        'cuentas',
        'varManejoCuentas',
        'cajas',
        'idpres',
        'permisos1'
      ));
    } catch (\Illuminate\Database\QueryException $ex) {
      return back()->with("warningBD", "no guardado correctamente");
    }
  }

  public function downloadEstadoCuenta(string $tipo, int $idpres, int $idemple, int $idcoord)
  {
    try {
      $varpantallas = $this->Traermenuenc();
      $varsubmenus = $this->Traermenudet();
      $varempleados = $this->obtenerempleados();
      $varempresas = $this->razonSocial("Recursos Humanos");
      if ($tipo == "pendientes") {
        $varPresDet = $this->obtenerTempPrestEmpDet($idpres);
      } else {
        $varPresDet = $this->obtenerPrestEmpDet($idpres);
      }

      foreach ($varempresas as $empresa) {
        $razon_social = $empresa->razon_social;
        $icono = $empresa->icono;
        $marca_agua = $empresa->marca_agua;
        $empresa = $empresa->empresa;
      }

      foreach ($varempleados as $cord) {
        if ($cord->id == $idcoord) {
          $coordinador = $cord->Nombre;
          $coordinadorTel = $cord->telefono;
        }
      }
      $pdf = \PDF::setPaper('letter')->loadView('Prestamosnomina.PDF.estadoCuenta', compact('razon_social', 'varPresDet', 'empresa', 'coordinador', 'coordinadorTel', 'icono', 'marca_agua'));
      return $pdf->download("ESTADO DE CUENTA EMPLEADO_#" . $idemple . ".pdf");
    } catch (\Illuminate\Database\QueryException $ex) {
      return back()->with("warningBD", "no guardado correctamente");
    }
  }

  public function pruebaEstadoCuenta(string $tipo, int $idpres, int $idemple, int $idcoord)
  {
    try {
      $varpantallas = $this->Traermenuenc();
      $varsubmenus = $this->Traermenudet();
      $varempleados = $this->obtenerempleados();
      $varempresas = $this->razonSocial("Recursos Humanos");
      if ($tipo == "pendientes") {
        $varPresDet = $this->obtenerTempPrestEmpDet($idpres);
      } else {
        $varPresDet = $this->obtenerPrestEmpDet($idpres);
      }

      foreach ($varempresas as $empresa) {
        $razon_social = $empresa->razon_social;
        $icono = $empresa->icono;
        $marca_agua = $empresa->marca_agua;
        $empresa = $empresa->empresa;
      }

      foreach ($varempleados as $cord) {
        if ($cord->id == $idcoord) {
          $coordinador = $cord->Nombre;
          $coordinadorTel = $cord->telefono;
        }
      }
      return view('Prestamosnomina.PDF.estadoCuenta', compact('razon_social', 'varPresDet', 'empresa', 'coordinador', 'coordinadorTel', 'icono', 'marca_agua'));
    } catch (\Illuminate\Database\QueryException $ex) {
      return back()->with("warningBD", "no guardado correctamente");
    }
  }


  public function subirCompEstadoCuenta(Request $request, int $idpres, int $idemple)
  {
    try {
      $tipo_credito = $request->get('tipo_credito');

      if ($request->hasFile("estadoCuenta") && $request->hasFile("pagare")) {
        $Rutacarpeta = "Expedientes/prestamosEmpleados/EMP_" . $idemple . "/PRES_" . $idpres;
        File::makeDirectory($Rutacarpeta, 0777, true, true);

        $file_estado_cuenta = $request->file("estadoCuenta");
        $Nombre_estado_cuenta = "estado_cuenta_emp_" . "$idemple" . "_pres_" . $idpres . "." . $file_estado_cuenta->guessExtension();
        $ruta_estado_cuenta = public_path($Rutacarpeta . "/" . $Nombre_estado_cuenta);
        copy($file_estado_cuenta, $ruta_estado_cuenta);

        $file_pagare = $request->file("pagare");
        $Nombre_pagare = "pagare_" . "$idemple" . "_pres_" . $idpres . "." . $file_pagare->guessExtension();
        $ruta_pagare = public_path($Rutacarpeta . "/" . $Nombre_pagare);
        copy($file_pagare, $ruta_pagare);

        $varcli = creditoempleado::find($idpres);
        $varcli->estado_cuenta = $Nombre_estado_cuenta;
        $varcli->estado_cuenta_status = "A";
        $varcli->pagare = $Nombre_pagare;
        $varcli->pagare_status = "A";


        if ($tipo_credito == "automovil") {

          $file_factura = $request->file("factura");
          $Nombre_factura = "factura_" . "$idemple" . "_pres_" . $idpres . "." . $file_factura->guessExtension();
          $ruta_factura = public_path($Rutacarpeta . "/" . $Nombre_factura);
          copy($file_factura, $ruta_factura);

          $varcli->factura = $Nombre_factura;
          $varcli->factura_status = "A";

          if ($request->get('status_entrega_factura') == "entregado") {
            $varcli->status_entrega_factura = "entregado";
          } else {
            $varcli->status_entrega_factura = "no_entregado";
          }

        } else {
          error_log('No tienes este archivo');
        }

        if ($request->get('status_entrega_estado_cuenta') == "entregado") {
          $varcli->status_entrega_estado_cuenta = "entregado";
        } else {
          $varcli->status_entrega_estado_cuenta = "no_entregado";
        }

        if ($request->get('status_entrega_pagare') == "entregado") {
          $varcli->status_entrega_pagare = "entregado";
        } else {
          $varcli->status_entrega_pagare = "no_entregado";
        }

        $varcli->updated_by = auth()->user()->name;
        $varcli->save();

        return back()->with("success", "ok");

      } else {
        back()->with("warning", "warning");
      }
    } catch (\Illuminate\Database\QueryException $ex) {
      return back()->with("warningBD", "no guardado correctamente");
    }
  }

  public function subirComprobantes(int $idpres, int $idemple, string $tipo_credito, ?UploadedFile $estadoCuenta = null, ?UploadedFile $pagare = null, ?UploadedFile $factura = null, ?string $status_entrega_factura = null, ?string $status_entrega_estado_cuenta = null, ?string $status_entrega_pagare = null, string $temp = null)
  {
    try {
      $Rutacarpeta = "Expedientes/prestamosEmpleados/EMP_" . $idemple . "/PRES_" . $idpres;
      $varcli = creditoempleado::find($idpres);

      if ($temp == "temp") {
        $varcli = creditoempleadotemp::find($idpres);
        $Rutacarpeta = "Expedientes/TEMP/prestamosEmpleados/EMP_" . $idemple . "/PRES_" . $idpres;
      }

      File::makeDirectory($Rutacarpeta, 0777, true, true);

      if ($estadoCuenta) {
        $Nombre_estado_cuenta = "estado_cuenta_emp_" . "$idemple" . "_pres_" . $idpres . "." . $estadoCuenta->guessExtension();
        $ruta_estado_cuenta = public_path($Rutacarpeta . "/" . $Nombre_estado_cuenta);
        copy($estadoCuenta, $ruta_estado_cuenta);

        $varcli->estado_cuenta = $Nombre_estado_cuenta;
        $varcli->estado_cuenta_status = "A";
        $varcli->status_entrega_estado_cuenta = $status_entrega_estado_cuenta === "entregado" ? "entregado" : "no_entregado";
      }

      if ($pagare) {
        $Nombre_pagare = "pagare_" . "$idemple" . "_pres_" . $idpres . "." . $pagare->guessExtension();
        $ruta_pagare = public_path($Rutacarpeta . "/" . $Nombre_pagare);
        copy($pagare, $ruta_pagare);

        $varcli->pagare = $Nombre_pagare;
        $varcli->pagare_status = "A";
        $varcli->status_entrega_pagare = $status_entrega_pagare === "entregado" ? "entregado" : "no_entregado";
      }

      if ($tipo_credito === "automovil" && $factura) {
        $Nombre_factura = "factura_" . "$idemple" . "_pres_" . $idpres . "." . $factura->guessExtension();
        $ruta_factura = public_path($Rutacarpeta . "/" . $Nombre_factura);
        copy($factura, $ruta_factura);

        $varcli->factura = $Nombre_factura;
        $varcli->factura_status = "A";
        $varcli->status_entrega_factura = $status_entrega_factura === "entregado" ? "entregado" : "no_entregado";
      }

      $varcli->updated_by = auth()->user()->name;
      $varcli->save();

    } catch (\Illuminate\Database\QueryException $ex) {
      Log::error($ex->getMessage());
    }
  }

  public function moverComprobantesTemp(object $nuevoObject, object $eliminarObject, string $temp = null)
  {
    try {
      $RutacarpetaOld = "Expedientes/TEMP/prestamosEmpleados/EMP_" . $eliminarObject->id_empleado . "/PRES_" . $eliminarObject->id;
      $RutacarpetaNueva = "Expedientes/prestamosEmpleados/EMP_" . $nuevoObject->id_empleado . "/PRES_" . $nuevoObject->id;

      if ($temp == "temp") {
        $RutacarpetaNueva = "Expedientes/TEMP/prestamosEmpleados/EMP_" . $nuevoObject->id_empleado . "/PRES_" . $nuevoObject->id;
      }

      File::makeDirectory($RutacarpetaNueva, 0777, true, true);

      if ($eliminarObject->estado_cuenta != null) {
        $ruta_estado_cuenta = public_path($RutacarpetaOld . "/" . $eliminarObject->estado_cuenta);
        if (file_exists($ruta_estado_cuenta)) {
          $nombreEstadoCuentaNuevo = "estado_cuenta_emp_" . $nuevoObject->id_empleado . "_pres_" . $nuevoObject->id . "." . pathinfo($eliminarObject->estado_cuenta, PATHINFO_EXTENSION);
          $ruta_destino = public_path($RutacarpetaNueva . "/" . $nombreEstadoCuentaNuevo);
          copy($ruta_estado_cuenta, $ruta_destino);

          $nuevoObject->estado_cuenta = $nombreEstadoCuentaNuevo;
        }
      }

      if ($eliminarObject->pagare != null) {
        $ruta_pagare = public_path($RutacarpetaOld . "/" . $eliminarObject->pagare);
        if (file_exists($ruta_pagare)) {
          $nombrePagareNuevo = "pagare_" . $nuevoObject->id_empleado . "_pres_" . $nuevoObject->id . "." . pathinfo($eliminarObject->pagare, PATHINFO_EXTENSION);
          $ruta_destino = public_path($RutacarpetaNueva . "/" . $nombrePagareNuevo);

          copy($ruta_pagare, $ruta_destino);

          $nuevoObject->pagare = $nombrePagareNuevo;
        }
      }

      if ($eliminarObject->factura != null) {
        $ruta_factura = public_path($RutacarpetaOld . "/" . $eliminarObject->factura);
        if (file_exists($ruta_factura)) {
          $nombreFacturaNuevo = "factura_" . $nuevoObject->id_empleado . "_pres_" . $nuevoObject->id . "." . pathinfo($eliminarObject->factura, PATHINFO_EXTENSION);
          $ruta_destino = public_path($RutacarpetaNueva . "/" . $nombreFacturaNuevo);

          copy($ruta_factura, $ruta_destino);

          $nuevoObject->factura = $nombreFacturaNuevo;
        }
      }

      $nuevoObject->estado_cuenta_status = $eliminarObject->estado_cuenta_status;
      $nuevoObject->status_entrega_estado_cuenta = $eliminarObject->status_entrega_estado_cuenta;
      $nuevoObject->pagare_status = $eliminarObject->pagare_status;
      $nuevoObject->status_entrega_pagare = $eliminarObject->status_entrega_pagare;
      $nuevoObject->factura_status = $eliminarObject->factura_status;
      $nuevoObject->status_entrega_factura = $eliminarObject->status_entrega_factura;
      $nuevoObject->updated_by = auth()->user()->name;
      $nuevoObject->save();

      File::deleteDirectory($RutacarpetaOld);
      return true;
    } catch (\Illuminate\Database\QueryException $ex) {
      Log::error($ex->getMessage());
      return false;
    } catch (\Exception $ex) {
      Log::error($ex->getMessage());
      return false;
    }
  }


  public function actualizaEstado(Request $request, int $idpres)
  {
    try {
      $pres = creditoempleado::find($idpres);
      if ($request->get('status_entrega_estado_cuenta') == "entregado") {
        $pres->status_entrega_estado_cuenta = "entregado";
      } else {
        $pres->status_entrega_estado_cuenta = "no_entregado";
      }

      if ($request->get('status_entrega_pagare') == "entregado") {
        $pres->status_entrega_pagare = "entregado";
      } else {
        $pres->status_entrega_pagare = "no_entregado";
      }
      $pres->updated_by = auth()->user()->name;
      $pres->save();

      return back()->with("success", "guardado");
    } catch (\Illuminate\Database\QueryException $ex) {
      return back()->with("warningBD", "no guardado correctamente");
    }
  }

  public function ComentarioEdit(Request $request, int $idpres, string $tipopres)
  {
    try {
      if ($tipopres == "activo") {
        $pres = creditoempleado::find($idpres);
        if (!is_null($request->get('comentario'))) {
          $pres->comentario = $request->get('comentario');
          $pres->status_comentario = "1";
        } else {
          $pres->status_comentario = "0";
        }
        $pres->save();
      } else {
        $presdet = creditoempleadotemp::find($idpres);
        if (!is_null($request->get('comentario'))) {
          $presdet->comentario = $request->get('comentario');
          $presdet->status_comentario = "1";
        } else {
          $presdet->status_comentario = "0";
        }
        $presdet->save();
      }
      return back()->with("success", "guardado");
    } catch (\Illuminate\Database\QueryException $ex) {
      return back()->with("warningBD", "no guardado correctamente");
    }
  }

  public function SolicitarCancelacion(Request $request, int $idpres)
  {
    try {
      $date = Carbon::now();
      $fecha = $date->format('Y-m-d');

      $canelcred = new solitarCancelacionPresEmp();
      $canelcred->id_credito = $idpres;
      $canelcred->estado = "P";
      $canelcred->fecha = $fecha;
      $canelcred->descripcion = $request->get("descripcion");
      $canelcred->created_by = auth()->user()->name;
      $canelcred->save();

      return back()->with("success", "MensajeInsertExitoso");
    } catch (\Illuminate\Database\QueryException $ex) {
      return back()->with("warningBD", "no guardado correctamente");
    }
  }

  public function eliminarPrestammoActivo(Request $request)
  {
    try {
      $date = Carbon::now();
      $fecha = $date->format('Y-m-d');
      $dias = 0;
      $varplazo = 0;
      $totalmeses = 0;
      $intereses = 0;
      $porcentajetasa = 0;
      $total = 0;
      $totalredondeado = 0;
      $ivainteres = 0;
      $ivainteresredondeado = 0;
      $interesredondeado = 0;
      $totalredondeado = 0;
      $pagoquincenal = 0;
      $pagoquincenalred = 0;
      $idultimop = 0;
      $saldo_nuevo = 0;
      $estado = "C";
      $contadorencabezado = 1;
      $credito = $request->get('id_credito');
      $tipocredito = $request->get('tipocredito');
      $Empleado = $request->get('empleado');
      $Coordinador = $request->get('coordinador');
      $varprimeraquincena = $request->get('fechai');
      $tasa = $request->get('tasa');
      $Monto = $request->get('monto');
      $Tipoplazo = $request->get('tipop');
      $Numeroplazo = $request->get('numplazo');
      $cuenta = $request->get('cuenta');
      $comentario = $request->get('comentario');
      $varobtenercuentas = $this->obtenercuentasPrincipales($cuenta);

      foreach ($varobtenercuentas as $varobtenercuenta) {
        $saldoCuenta = $varobtenercuenta->saldo_actual;
      }

      $varprimeraquincena = strtotime($varprimeraquincena);
      $varprimeraquincena = date('Y-m-d', $varprimeraquincena);
      $añoprimeraquincena = substr($varprimeraquincena, 0, 4);
      $mesprimeraquincena = substr($varprimeraquincena, 5, -3);
      $diaprimeraquincena = substr($varprimeraquincena, 8, 10);

      $listatas = $this->obtenertasaxid($tasa);
      foreach ($listatas as $dt1) {
        $porcentajetasa = $dt1->porcentaje;
      }

      //obtenemos los intereses del credito como sera pago quincenal puede ser asi ejemplo los 25,000*2.5/100*meses
      //obtenemos el total de los meses del credito
      $totalmeses = $Numeroplazo / 2;
      $intereses = (($Monto * $porcentajetasa) / 100);
      //interes y iva de interes
      $intereses = $intereses * $totalmeses;
      $ivainteres = $intereses * .16;
      //  //interesredondeado
      $interesredondeado = ceil($intereses);
      $ivainteresredondeado = ceil($intereses * .16);

      //Monto total
      $total = $Monto + $intereses + $ivainteres;
      $totalredondeado = ceil($Monto + $interesredondeado + $ivainteresredondeado);
      DB::select('delete from tblsolicitar_cancelacion_credemp where id_credito = ? ', [$credito]);
      DB::select('delete from tblcreditosempleado_det where id_credito = ? ', [$credito]);
      DB::select('delete from tblcreditos_empleado where id = ? ', [$credito]);

      $pagoquincenal = $total / $Numeroplazo;
      $pagoquincenalred = ceil($pagoquincenal);
      $pagototal = $pagoquincenalred * $Numeroplazo;
      $otrosExtra = $pagototal - ($Monto + $intereses + $ivainteres);
      $saldo_nuevo = $pagototal;

      $insercredito = new creditoempleadotemp();
      $insercredito->id_empleado = $Empleado;
      $insercredito->idcoordinador = $Coordinador;
      $insercredito->idtasa = $tasa;
      $insercredito->tipo_credito = $tipocredito;
      $insercredito->estado = $estado;
      $insercredito->comentario = $comentario;
      $insercredito->status_comentario = $comentario ? 1 : 0;
      $insercredito->monto = $Monto;
      $insercredito->interes = $intereses;
      $insercredito->ivainteres = $ivainteres;
      $insercredito->otros = $otrosExtra;
      $insercredito->total = $pagototal;
      $insercredito->fecha_inicio = $varprimeraquincena;
      $insercredito->fecha_fin = "";
      $insercredito->tipo_plazo = $Tipoplazo;
      $insercredito->plazos = $Numeroplazo;
      $insercredito->interesredondeado = $interesredondeado;
      $insercredito->ivainteresredondeado = $ivainteresredondeado;
      $insercredito->totalredondeado = $totalredondeado;
      $insercredito->created_by = auth()->user()->name;
      $insercredito->save();

      $ultomocre = $this->obtenerultimpresnomtemp();
      foreach ($ultomocre as $idultimopr) {
        $idultimop = $idultimopr->id;
      }

      $historialcuent = new historial_cuentas();
      $historialcuent->id_cuenta = $cuenta;
      $historialcuent->id_empleado = auth()->user()->idempleado;
      $historialcuent->estado = "A";
      $historialcuent->tipo_movimiento = "INGRESO";
      $historialcuent->concepto = "CANCELACION DE CREDITO A EMPLEADO - TIPO " . strtoupper($tipocredito) . " CREDITO #" . $credito;
      $historialcuent->responsable = "EMPLEADO #" . $Empleado;
      $historialcuent->ingreso = $Monto;
      $historialcuent->egreso = 0;
      $historialcuent->saldo = $saldoCuenta + $Monto;
      $historialcuent->numero_referencia = $credito;
      $historialcuent->tipo_referencia = "prestamo_emp";
      $historialcuent->numero_poliza = 0;
      $historialcuent->fecha = $fecha;
      $historialcuent->created_by = auth()->user()->name;
      $historialcuent->save();

      $cuentas = cuentas::find($cuenta);
      $cuentas->saldo_actual = $saldoCuenta + $Monto;
      $cuentas->updated_by = auth()->user()->name;
      $cuentas->save();

      for ($i = 0; $i < $Numeroplazo; $i++) {
        $saldo_nuevo = $saldo_nuevo - $pagoquincenalred;
        if ($diaprimeraquincena == 15) {
          $varplazo = $varplazo + 1;
          $varprimeraquincena = $añoprimeraquincena . '-' . $mesprimeraquincena . '-15';
          $diaprimeraquincena = 30;
          $contadordetalle = $contadorencabezado + 1;

          $inserpresdet = new creditosempleadodettemp();
          $inserpresdet->id_credito = $idultimop;
          $inserpresdet->plazo = $varplazo;
          $inserpresdet->pago_quincenal = $pagoquincenalred;
          $inserpresdet->pago_total = $pagototal;
          $inserpresdet->fecha_pago = $varprimeraquincena;
          $inserpresdet->otrosconceptos1 = $Monto / $Numeroplazo;
          $inserpresdet->otrosconceptos2 = $intereses / $Numeroplazo;
          $inserpresdet->otrosconceptos3 = $ivainteres / $Numeroplazo;
          $inserpresdet->monto = $Monto;
          $inserpresdet->saldo_nuevo = $saldo_nuevo;
          $inserpresdet->estado = $estado;
          $inserpresdet->fecha_saldado = "null";
          $inserpresdet->created_by = auth()->user()->name;
          $inserpresdet->save();

          $diaprimeraquincena == 31;
        } else {
          if ($mesprimeraquincena == 13) {
            if ($mesprimeraquincena == 1) {
              $mesprimeraquincena = "0" . $mesprimeraquincena;
            } else {
              $mesprimeraquincena = $mesprimeraquincena;
            }
            $añoprimeraquincena = $añoprimeraquincena + 1;

          }

          if ($mesprimeraquincena == 13) {
            $mesprimeraquincena = 01;
            $añoprimeraquincena = $añoprimeraquincena + 1;
          }

          $dias = cal_days_in_month(CAL_GREGORIAN, $mesprimeraquincena, $añoprimeraquincena);
          if ($dias == 28) {
            $dias = 28;
          }
          if ($dias == 29) {
            $dias = 29;
          }
          if ($dias == 30) {
            $dias = 30;
          }
          if ($dias == 31) {
            $dias = 31;
          }

          $varprimeraquincena = $añoprimeraquincena . '-' . $mesprimeraquincena . '-' . $dias;
          $mesprimeraquincena = $mesprimeraquincena + 1;
          $diaprimeraquincena = 15;
          $varplazo = $varplazo + 1;

          $inserpresdet = new creditosempleadodettemp();
          $inserpresdet->id_credito = $idultimop;
          $inserpresdet->plazo = $varplazo;
          $inserpresdet->pago_quincenal = $pagoquincenalred;
          $inserpresdet->pago_total = $pagototal;
          $inserpresdet->fecha_pago = $varprimeraquincena;
          $inserpresdet->otrosconceptos1 = $Monto / $Numeroplazo;
          $inserpresdet->otrosconceptos2 = $intereses / $Numeroplazo;
          $inserpresdet->otrosconceptos3 = $ivainteres / $Numeroplazo;
          $inserpresdet->monto = $Monto;
          $inserpresdet->saldo_nuevo = $saldo_nuevo;
          $inserpresdet->estado = $estado;
          $inserpresdet->fecha_saldado = "null";
          $inserpresdet->save();
          $diaprimeraquincena == 15;
        }
      }

      return back()->with("success", "MensajeInsertExitoso");
    } catch (\Illuminate\Database\QueryException $ex) {
      return back()->with("warningBD", "no guardado correctamente");
    }
  }

  public function exportar_excel_creditos_calculados()
  {
    try {
      return Excel::download(new CreditosEmpPend, 'CREDITOS CALCULADOS.xlsx');
    } catch (\Illuminate\Database\QueryException $ex) {
      return back()->with("warningBD", "no guardado correctamente");
    }
  }

  public function exportar_excel_creditos_empleados()
  {
    try {
      return Excel::download(new CreditosEmp, 'CREDITOS EMPLEADOS.xlsx');
    } catch (\Illuminate\Database\QueryException $ex) {
      return back()->with("warningBD", "no guardado correctamente");
    }
  }

  public function generar_pago(int $idpres, Request $request)
  {
    try {
      $date = Carbon::now();
      $fecha = $date->format('Y-m-d');
      $idusuario = auth()->user()->id;
      $idempleado = $request->get('idempleado');
      $pago_quincenal = $request->get('pago_quincenal');
      $fecha_quincenal = $request->get('fecha_quincenal');
      $plazo = $request->get('plazo');
      $cuenta = $request->get('cuenta');
      $descripcion = $request->get('descripcion');
      $fecha_pago = $request->get('fecha_pago');

      $saldo = 0;
      $saldoactual = 0;
      $saldogenerado = 0;

      $tipocuenta = $request->get('tipo');

      if ($tipocuenta == "CAJA") {
        $cuenta = $request->get('caja');
    
        $saldoactual = $this->obtenerCajasxId($cuenta);
        foreach ($saldoactual as $saldoactualcajas) {
            $saldo = $saldoactualcajas->saldo_actual;
        }
        $saldogenerado = $saldo + $pago_quincenal;
    } else {
        $cuenta = $request->get('cuenta');
    
        $saldoactual = $this->obtenersaldocuenta($cuenta);
        foreach ($saldoactual as $saldoactualcuenta) {
            $saldo = $saldoactualcuenta->saldo_actual;
        }
        $saldogenerado = $saldo + $pago_quincenal;
    }
    

      $obtenerconceptospagos = $this->obtenerprorrateo();

      //actualizamos el pago a saldado
      DB::table('tblcreditosempleado_det')
        ->where('id_credito', $idpres)
        ->where('plazo', $plazo)
        ->update([
          'estado' => 'S',
          'fecha_saldado' => $fecha_pago,
        ]);


      //saldamos pretamo_enc si ya el detalle tiene todos los pagos
      $validasaldado = $this->validasaldado($idpres);
      if ($validasaldado->isEmpty()) {
        DB::table('tblcreditos_empleado')
          ->where('id', $idpres)
          ->update(['estado' => 'S']);
      } else {
        error_log('Aun faltan pagos para saldar');
      }

      //crear tabla de pagos enc
      $insertarenc = new pagonominaenc();
      $insertarenc->id_empleado = $idempleado;
      $insertarenc->idprestamos = $idpres;
      $insertarenc->saldo_pagar = $pago_quincenal;
      $insertarenc->monto_total = $pago_quincenal;
      $insertarenc->fecha_pago = $fecha_pago;
      $insertarenc->otrosconceptos1 = "NULL";
      $insertarenc->otrosconceptos2 = "NULL";
      $insertarenc->otrosconceptos3 = "NULL";
      $insertarenc->created_by = auth()->user()->name;

      if ($insertarenc->save()) {
        $ultimopagoshecho = $this->obtenerultimopagnom();
        foreach ($ultimopagoshecho as $ultimo) {
          $ultimop = $ultimo->id;
        }

        //crear tabla de det segun el concepto
        foreach ($obtenerconceptospagos as $conceptoporciento) {
          $totalcomision = 0;
          $inserdetalle = new pagonominadet();
          $inserdetalle->idpagonomenc = $ultimop;
          $inserdetalle->idconcepto = $conceptoporciento->id;
          $inserdetalle->usuarioregistro = $idusuario;
          $totalcomision = $pago_quincenal * $conceptoporciento->porcentaje;
          $inserdetalle->monto = $totalcomision;
          $inserdetalle->fecha_pago = $fecha_pago;
          $inserdetalle->otrosconceptos1 = "null";
          $inserdetalle->otrosconceptos2 = "null";
          $inserdetalle->otrosconceptos3 = "null";
          $inserdetalle->created_by = auth()->user()->name;
          $inserdetalle->save();
        }
      }

      // afectar cuenta de prestamos
      if ($tipocuenta == "CAJA") {
        $cuentas = Cajas::find($cuenta);
        $cuentas->saldo_actual = $saldogenerado;
        $cuentas->updated_at = $fecha;
        $cuentas->updated_by = auth()->user()->name;
        $cuentas->save();

        $historialcuent = new  historial_cajas();
        $historialcuent->id_caja = $cuenta;
        $historialcuent->id_empleado = auth()->user()->idempleado;
        $historialcuent->estado = "A";
        $historialcuent->tipo_movimiento = "PAGO";
        $historialcuent->concepto = "PAGO DE PRESTAMO # " . $idpres . " DEL EMPLEADO #" . $idempleado . " POR PAGO DIRECTO, A QUINCENA " . $fecha_quincenal;
        $historialcuent->descripcion = $descripcion;
        $historialcuent->responsable = "EMPLEADO #" . $idempleado;
        $historialcuent->ingreso = $pago_quincenal;
        $historialcuent->egreso = 0;
        $historialcuent->saldo = $saldogenerado;
        $historialcuent->numero_referencia = $idpres;
        $historialcuent->tipo_referencia = "prestamo_emp";
        $historialcuent->numero_poliza = 0;
        $historialcuent->fecha = $fecha;
        $historialcuent->created_by = auth()->user()->name;
        $historialcuent->save();
      }
      else 
      {
        $insertcuenta = cuentas::find($cuenta);
        $insertcuenta->saldo_actual = $saldogenerado;
        $insertcuenta->updated_by = auth()->user()->name;
        $insertcuenta->save();

        $historialcuent = new historial_cuentas();
        $historialcuent->id_cuenta = $cuenta;
        $historialcuent->id_empleado = auth()->user()->idempleado;
        $historialcuent->estado = "A";
        $historialcuent->tipo_movimiento = "PAGO";
        $historialcuent->concepto = "PAGO DE PRESTAMO # " . $idpres . " DEL EMPLEADO #" . $idempleado . " POR PAGO DIRECTO, A QUINCENA " . $fecha_quincenal;
        $historialcuent->descripcion = $descripcion;
        $historialcuent->responsable = "EMPLEADO #" . $idempleado;
        $historialcuent->ingreso = $pago_quincenal;
        $historialcuent->egreso = 0;
        $historialcuent->saldo = $saldogenerado;
        $historialcuent->numero_referencia = $idpres;
        $historialcuent->tipo_referencia = "prestamo_emp";
        $historialcuent->numero_poliza = 0;
        $historialcuent->fecha = $fecha;
        $historialcuent->created_by = auth()->user()->name;
        $historialcuent->save();
      }

      return back()->with("successpagoAplicado", "pagoAplicado");
    } catch (\Illuminate\Database\QueryException $ex) {
      Log::error($ex->getMessage());
      return back()->with("warningBD", "no guardado correctamente");
    }
  }

  public function generar_pago_todos(int $idpres, Request $request)
  {
    try {
      $date = Carbon::now();
      $fecha = $date->format('Y-m-d');
      $idusuario = auth()->user()->id;
      $idempleado = $request->get('idempleado');
      $cuenta = $request->get('cuenta');
      $descripcion = $request->get('descripcion');
      $fecha_pago = $request->get('fecha_pago');
      $tipocuenta = $request->get('tipo');
      // Obtener pagos pendientes del empleado
      $pagosPendientes = DB::table('tblcreditosempleado_det')
        ->where('id_credito', $idpres)
        ->where('estado', 'A') // Pendientes
        ->get();

      if ($pagosPendientes->isEmpty()) {
        return back()->with("info", "No hay pagos pendientes para este préstamo.");
      }

      $saldo = 0;
      $saldoactual = 0;
      $saldogenerado = 0;

      if ($tipocuenta == "CAJA") {
        $cuenta = $request->get('caja');
    
        $saldoactual = $this->obtenerCajasxId($cuenta);
        $saldo = $saldoactual[0]->saldo_actual ?? 0;
        // $saldogenerado = $saldo;
      } else {
        $saldoactual = $this->obtenersaldocuenta($cuenta);
        $saldo = $saldoactual[0]->saldo_actual ?? 0;
        // $saldogenerado = $saldo;
      }

      foreach ($pagosPendientes as $pago) {
        $pago_quincenal = $pago->pago_quincenal;
        $fecha_quincenal = $pago->fecha_pago;

        // Actualizar el estado del detalle a saldado
        DB::table('tblcreditosempleado_det')
          ->where('id', $pago->id)
          ->update(['estado' => 'S', 'fecha_saldado' => $fecha_pago]);

        // Crear el registro de pago en pagonominaenc
        $insertarenc = new pagonominaenc();
        $insertarenc->id_empleado = $idempleado;
        $insertarenc->idprestamos = $idpres;
        $insertarenc->saldo_pagar = $pago_quincenal;
        $insertarenc->monto_total = $pago_quincenal;
        $insertarenc->fecha_pago = $fecha_pago;
        $insertarenc->otrosconceptos1 = "NULL";
        $insertarenc->otrosconceptos2 = "NULL";
        $insertarenc->otrosconceptos3 = "NULL";
        $insertarenc->created_by = auth()->user()->name;

        if ($insertarenc->save()) {
          $ultimop = $insertarenc->id;

          // Crear los detalles del pago
          $obtenerconceptospagos = $this->obtenerprorrateo();
          foreach ($obtenerconceptospagos as $conceptoporciento) {
            $totalcomision = $pago_quincenal * $conceptoporciento->porcentaje;

            $inserdetalle = new pagonominadet();
            $inserdetalle->idpagonomenc = $ultimop;
            $inserdetalle->idconcepto = $conceptoporciento->id;
            $inserdetalle->usuarioregistro = $idusuario;
            $inserdetalle->monto = $totalcomision;
            $inserdetalle->fecha_pago = $fecha_pago;
            $inserdetalle->otrosconceptos1 = "null";
            $inserdetalle->otrosconceptos2 = "null";
            $inserdetalle->otrosconceptos3 = "null";
            $inserdetalle->created_by = auth()->user()->name;
            $inserdetalle->save();
          }
        }

        // Actualizar saldo acumulado
        $saldogenerado += $pago_quincenal;
      }

      // Actualizar saldo de la cuenta
      if ($tipocuenta == "CAJA") {
        $cuentas = Cajas::find($cuenta);
        $cuentas->saldo_actual = $saldo + $saldogenerado;
        $cuentas->updated_at = $fecha;
        $cuentas->updated_by = auth()->user()->name;
        $cuentas->save();
        echo  "SALDO FINAL".$saldo + $saldogenerado."<br>";

        $historialcuent = new  historial_cajas();
        $historialcuent->id_caja = $cuenta;
        $historialcuent->id_empleado = auth()->user()->idempleado;
        $historialcuent->estado = "A";
        $historialcuent->tipo_movimiento = "PAGO";
        $historialcuent->concepto = "PAGO DE PRESTAMO # " . $idpres . " DEL EMPLEADO #" . $idempleado . " POR PAGO DIRECTO, LIQUIDACIÓN DE PRESTAMO";
        $historialcuent->descripcion = $descripcion;
        $historialcuent->responsable = "EMPLEADO #" . $idempleado;
        $historialcuent->ingreso = $saldogenerado;
        $historialcuent->egreso = 0;
        $historialcuent->saldo = $saldo + $saldogenerado;
        $historialcuent->numero_referencia = $idpres;
        $historialcuent->tipo_referencia = "prestamo_emp";
        $historialcuent->numero_poliza = 0;
        $historialcuent->fecha = $fecha;
        $historialcuent->created_by = auth()->user()->name;
        $historialcuent->save();
      }
      else 
      {
        $insertcuenta = cuentas::find($cuenta);
        $insertcuenta->saldo_actual = $saldo + $saldogenerado;
        $insertcuenta->updated_by = auth()->user()->name;
        $insertcuenta->save();

        $historialcuent = new historial_cuentas();
        $historialcuent->id_cuenta = $cuenta;
        $historialcuent->id_empleado = auth()->user()->idempleado;
        $historialcuent->estado = "A";
        $historialcuent->tipo_movimiento = "PAGO";
        $historialcuent->concepto = "PAGO DE PRESTAMO # " . $idpres . " DEL EMPLEADO #" . $idempleado . " POR PAGO DIRECTO, LIQUIDACIÓN DE PRESTAMO";
        $historialcuent->descripcion = $descripcion;
        $historialcuent->responsable = "EMPLEADO #" . $idempleado;
        $historialcuent->ingreso = $saldogenerado;
        $historialcuent->egreso = 0;
        $historialcuent->saldo = $saldo + $saldogenerado;
        $historialcuent->numero_referencia = $idpres;
        $historialcuent->tipo_referencia = "prestamo_emp";
        $historialcuent->numero_poliza = 0;
        $historialcuent->fecha = $fecha;
        $historialcuent->created_by = auth()->user()->name;
        $historialcuent->save();
      }
      // Marcar el préstamo como saldado si ya no hay pendientes
      $validasaldado = $this->validasaldado($idpres);
      if ($validasaldado->isEmpty()) {
        DB::table('tblcreditos_empleado')
          ->where('id', $idpres)
          ->update(['estado' => 'S']);
      }

      return back()->with("successpagoAplicado", "Todos los pagos se aplicaron correctamente.");
    } catch (\Illuminate\Database\QueryException $ex) {
      Log::error($ex->getMessage());
      return back()->with("warningBD", "Ocurrió un error al procesar los pagos.");
    }
  }

}
