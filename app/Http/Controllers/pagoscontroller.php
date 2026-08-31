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
use App\Models\pagosdet;
use App\Traits\DatosimpleTraits;
use App\Models\tipo_distribuidor;
use App\Models\excedentes;
use App\Models\distribuidores_valeras;
use App\Models\historial;
use App\Models\mensajes;
use \Milon\Barcode\DNS1D;
use \Milon\Barcode\DNS2D;
use App\Models\referencias_pago;
use Carbon\Carbon;
use DB;
use Log;
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
use App\Traits\PagoTrait;
use DateTime;

class pagoscontroller extends Controller
{
  use MenuTrait;
  use DatosimpleTraits;
  use PagoTrait;
  use SistemasTraits;

  public function __construct()
  {
    $this->middleware('auth');
  }

  public function index()
  {
    try {
      $varpantallas = $this->Traermenuenc();
      $varsubmenus = $this->Traermenudet();
      $vardistribuidores = $this->obtenerdv();
      $obtenercantidadexcedentes = $this->obtenercantidadexcedentes();
      $obtenercantidadconciliados = $this->obtenercantidadconciliados();
      $permisos = $this->forpermisos('generar_relaciones');
      $permisos1 = $this->forpermisos('pagos_dis');
      $permisos2 = $this->forpermisos('realizar_pagos');
      $permisos3 = $this->forpermisos('aplicacion_pagos_conciliados');
      $permisos4 = $this->forpermisos('ver_excedentes');
      $idusuario = auth()->user()->id;
      $varSucursalesUser = $this->obtenerSucursalesxUser($idusuario);
      $conciliados = 0;
      $excendetes = 0;

      if ($permisos == "generar_relaciones") {
        $generar_relaciones = "A";
      } else {
        $generar_relaciones = "I";
      }

      if ($permisos1 == "pagos_dis") {
        $pagos_dis = "A";
      } else {
        $pagos_dis = "I";
      }

      if ($permisos2 == "realizar_pagos") {
        $realizar_pagos = "A";
      } else {
        $realizar_pagos = "I";
      }

      // CONTEO DE LISTA DE EXCEDENTES
      if (!$obtenercantidadexcedentes->isEmpty()) {
        foreach ($obtenercantidadexcedentes as $key1) {
          $excendetes = $key1->contador;
        }
      }

      // CONTEO DE LISTA DE CONCILIADOS
      if (!$obtenercantidadconciliados->isEmpty()) {
        foreach ($obtenercantidadconciliados as $key2) {
          $conciliados = $key2->contador;
        }
      }

      return view('vales.pagos.gestionpagos', compact(
        'varpantallas',
        'varsubmenus',
        'vardistribuidores',
        'varSucursalesUser',
        'generar_relaciones',
        'pagos_dis',
        'realizar_pagos',
        'conciliados',
        'excendetes',
        'permisos3',
        'permisos4'
      ));
    } catch (\Illuminate\Database\QueryException $ex) {
      return back()->with("warningBD", "no guardado correctamente");
    }
  }

  public function pagosreferenciados()
  {
    // try{
    $idusuario = auth()->user()->id;
    $odp = $this->obtenerodp();
    $varpantallas = $this->Traermenuenc();
    $varsubmenus = $this->Traermenudet();
    $vardistribuidores = $this->obtenerdv();
    $arreglo = "";
    $startDate = "";
    $varcuenta = $this->obtenercuentasActivas();
    $varcaja = $this->obtenerCajasActivas();
    $permisos1 = $this->forpermisos('aplicacion_pagos_referidos');
    $permisos2 = $this->forpermisos('aplicacion_pagos_manuales');

    $varpagoenc = $this->obtenerpagoencALL();
    $idusuario = auth()->user()->id;
    $varSucursalesUser = $this->obtenerSucursalesxUser($idusuario);
    $consulta1 = $this->obtenerPagosconsiliados();
    $consulta2 = $this->obtenerPagoRelaciones2();

    $date = Carbon::now();
    $fecha = $date->format('Y-m-d');
    $varvalidaRel = DB::select("select fecha_relacion,fecha_corte_final from tblpagos_enc order by fecha_relacion desc limit 1;");
    foreach ($varvalidaRel as $rel) {
      $relacion_actual = $rel->fecha_relacion;
      $startDate = $rel->fecha_corte_final;
    }
    $relacion_actual = Carbon::parse($relacion_actual);
    $dia = $relacion_actual->format('d');
    $relacion_actual = $relacion_actual->format('Y-m-d');
    $startDate = Carbon::parse($startDate)->addDay(1)->format('Y-m-d');

    if ($dia == 30 || $dia == 31) {
      $dia = $date->format('d');
      $varlistacom = $this->obtenercomisondia2($dia);
    } elseif ($dia == 15) {
      $dia = $date->format('d');
      $varlistacom = $this->obtenercomisondia1($dia);
    }

    $id_user = auth()->user()->id;
    $modulo1 = "pago_dis_referenciados";
    $modulo2 = "pago_dis_manuales";

    //trait para base de calculo
    $obtprocompletoxrel = $this->obtprocompletoxrel($relacion_actual);

    //Porcentajes de datos iniciales
    $porcen_comision = 0;
    $datosini = $this->obtenerdatosiniciales();
    foreach ($datosini as $key) {
      if ($key->concepto == "COMISION") {
        $porcen_comision = $key->cantidad;
      }
    }

      $permisos3 = $this->forpermisos('fecha_pagosmanuales');
      $districon2porcientomas = DB::select("select * from tblhistorial_dosporciento where tblhistorial_dosporciento.fecha_relacion  = ?;",[$relacion_actual]);
      $districon2porcientomas =  collect($districon2porcientomas);
      $idusuario = auth()->user()->id;
      $modulo1 = "pago_dis_referenciados";
      $modulo2 = "pago_dis_manuales";
      $varManejoCuentas1 = $this->obtenerManejoCuentas($idusuario, $modulo1);
      $varManejoCajas2 = $this->obtenerManejoCajas($idusuario, $modulo2);
      $varManejoCuentas2 = $this->obtenerManejoCuentas($idusuario, $modulo2);      

      return view('vales.pagos.controlpagosrefe',compact('varpantallas','varsubmenus','fecha','startDate',
      'varSucursalesUser','vardistribuidores','odp','arreglo','varcuenta','permisos1','permisos2','permisos3',
      'varManejoCajas2','varManejoCuentas2','varManejoCuentas1','varcaja','obtprocompletoxrel',
      'varpagoenc','consulta1','consulta2','varlistacom','relacion_actual','porcen_comision','districon2porcientomas'));
    // } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
  }

  public function pagosexcedentes()
  {
    try {
      $idusuario = auth()->user()->id;
      $varpantallas = $this->Traermenuenc();
      $varsubmenus = $this->Traermenudet();
      $varsaldoexcedente = $this->saldoexcedente();
      $varSucursalesUser = $this->obtenerSucursalesxUser($idusuario);

      return view('vales.pagos.pagosexcedentes', compact('varpantallas', 'varsubmenus', 'varsaldoexcedente', 'varSucursalesUser'));
    } catch (\Illuminate\Database\QueryException $ex) {
      return back()->with("warningBD", "no guardado correctamente");
    }

  }
  
   public function pagosconciliados(){ 
    try{
        $varpantallas =  $this->Traermenuenc();
        $varsubmenus =   $this->Traermenudet();
        $id_user=auth()->user()->id;
        $varSucursalesUser = $this->obtenerSucursalesxUser($id_user);
        $vardistribuidores = $this->obtenerdv();
        $permisos3 = $this->forpermisos('aplicacion_pagos_conciliados'); 
        $permiso = $this->forpermisos('aplica_condonado'); 
        $vardispagoconcentado = $this->distribuidoresconpagoconcentrado();
        $pagosconcentrados = $this->obtenerpagosconcentrados();
      $consulta1 =  $this->obtenerPagosconsiliados();
        $consulta2 =  $this->obtenerPagoRelaciones22();
        $varpagoenc = $this->obtenerpagoencALL();
        $datoscomision = $this->obtenercomisionxprestamo2();
  
        $date = Carbon::now();
        $fecha = $date->format('Y-m-d');
        $varvalidaRel = DB::select("select fecha_relacion,fecha_corte_final from tblpagos_enc order by fecha_relacion desc limit 1;");
        foreach($varvalidaRel as $rel){
          $relacion_actual = $rel->fecha_relacion;
          $startDate = $rel->fecha_corte_final;
        }
        $relacion_actual = Carbon::parse($relacion_actual);
        $dia =  $relacion_actual->format('d');
        $relacion_actual = $relacion_actual->format('Y-m-d');
        $startDate = Carbon::parse($startDate)->addDay(1)->format('Y-m-d');
          
        if($dia == 30 || $dia == 31){
          $varlistacom2 =  $this->obtenercomisondia22();
          $varlistacom =  $this->obtenercomisondia2($dia);
        }elseif($dia == 15){
          $varlistacom2 =  $this->obtenercomisondia11();
          $varlistacom =  $this->obtenercomisondia1($dia);
        }

      $id_user = auth()->user()->id;
      $modulo1 = "pago_dis_referenciados";
      $modulo2 = "pago_dis_manuales";

      //trait para base de calculo
      $obtprocompletoxrel = $this->obtprocompletoxrel($relacion_actual);

      //Porcentajes de datos iniciales
      $porcen_comision = 0;
      $datosini = $this->obtenerdatosiniciales();
      foreach ($datosini as $key) {
        if ($key->concepto == "COMISION") {
          $porcen_comision = $key->cantidad;
        }
      }
              $pagosPorDistribuidor = [];
      foreach ($pagosconcentrados as $pago) {
          $pagosPorDistribuidor[$pago->id_distirbuidor][] = $pago;
      }

      // Agregar los pagos concentrados a cada distribuidor
      foreach ($vardispagoconcentado as &$distribuidor) {
          $idDistribuidor = $distribuidor->id_distirbuidor;
          if (isset($pagosPorDistribuidor[$idDistribuidor])) {
              $distribuidor->pagos = $pagosPorDistribuidor[$idDistribuidor];
              // Verificar la cantidad de pagos y agregar la propiedad a cada pago
              if (count($distribuidor->pagos) > 1) {
                  $distribuidor->pagos[0]->unicopago = 1;
                  for ($i = 1; $i < count($distribuidor->pagos); $i++) {
                      $distribuidor->pagos[$i]->unicopago = 0;
                  }
              } else {
                  $distribuidor->pagos[0]->unicopago = 0;
              }
          } else {
              $distribuidor->pagos = [];
          }
      }


      

      
        $districon2porcientomas = DB::select("select * from tblhistorial_dosporciento where tblhistorial_dosporciento.fecha_relacion  = ?;",[$relacion_actual]);
        $districon2porcientomas =  collect($districon2porcientomas); 
        $obtenerPagosConcentradosxrela = $this->obtenerPagosConcentradosxrela($relacion_actual);
      $date = Carbon::now();
      $fecha = $date->format('Y-m-d');

      return view('vales.pagos.pagosconciliados', compact(
        'varpantallas',
        'varsubmenus',
        'varlistacom2',
        'varSucursalesUser',
        'vardispagoconcentado',
        'permisos3',
        'datoscomision',
        'fecha',
        'permiso',
        'obtprocompletoxrel',
        'varpagoenc',
        'consulta1',
        'consulta2',
        'varlistacom',
        'relacion_actual',
        'porcen_comision',
        'districon2porcientomas',
        'obtenerPagosConcentradosxrela'
      ));
    } catch (\Illuminate\Database\QueryException $ex) {
      return back()->with("warningBD", "no guardado correctamente");
    }
  }

  public function eliminarPagosConciliados(int $id, string $tipo, int $idcuenta, Request $request)
  {
    try{
      $pagos = $request->input('pagos');
      $user = auth()->user()->idempleado;
      $date = Carbon::now();
      $fecha = $date->format('Y-m-d');
      
      $tipos_ids = [];
      foreach ($pagos as $pago) {
          $pagosConcentrados = $this->obtenerPagosConcentradosxid($pago);
          foreach ($pagosConcentrados as $pagoConcentrado) {
              $tipos_ids[] = [
                  'id' => (int)$pagoConcentrado->id,
                  'idcuenta' => (int)$pagoConcentrado->idcuenta,
                  'tipo' => $pagoConcentrado->tipocuenta,
                  'total' => (int)$pagoConcentrado->monto_recibido
              ];
          }
      }
      if (count($tipos_ids) > 1) {
        $tipos_ids[0]['unicopago'] = true;
      }
      $cuentasConsultadas = [];
      $cajasConsultadas = [];
      $cuentas = [];
      $cajas = [];
      $cuentasConsultadas = [];
      $cajasConsultadas = [];
   

      foreach ($tipos_ids as $tipo) {
          if ($tipo['tipo'] === "CUENTA" && !in_array($tipo['idcuenta'], $cuentasConsultadas)) {
              $cuentasConsulta = $this->obtenercuentasActivasxid($tipo['idcuenta']);
              $cuentasConsultadas[] = $tipo['idcuenta'];
              $cuentas[] = [
                  'id' => (int)$cuentasConsulta->id,
                  'saldototal' => (int)$cuentasConsulta->saldo_actual
              ];
          } elseif ($tipo['tipo'] === "CAJA" && !in_array($tipo['idcuenta'], $cajasConsultadas)) {
              $cajasConsulta = $this->obtenerCajasActivasxid($tipo['idcuenta']);
              $cajasConsultadas[] = $tipo['idcuenta'];
              $cajas[] = [
                  'id' => (int)$cajasConsulta->id,
                  'saldototal' => (int)$cajasConsulta->saldo_actual
              ];
          }
      }

      foreach ($cuentas as $index => $cal) {
        foreach ($tipos_ids as $tip) {
            if ($cal['id'] == $tip['idcuenta']) {
              if($cal['saldototal'] > $tip['total']){
                $cuentas[$index]['saldototal'] -= $tip['total'];
                $movimiento_cajas = new historial_cuentas();
                $movimiento_cajas->id_cuenta = $cal['id'];
                $movimiento_cajas->id_empleado = $user;
                $movimiento_cajas->fecha = $fecha;
                $movimiento_cajas->tipo_movimiento = "GASTO";
                $movimiento_cajas->estado = "A";
                $movimiento_cajas->egreso = $tip['total'];
                $movimiento_cajas->ingreso = 0.00;
                $movimiento_cajas->saldo = $cuentas[$index]['saldototal'];
                $movimiento_cajas->concepto = "CANCELACIÓN DE PAGO CONCILIADO AL DISTRIBUIDOR #" . $id;
                $movimiento_cajas->descripcion = "SE CANCELO EL PAGO DE LA LISTA DE CONCILIADOS";
                $movimiento_cajas->responsable = "DISTRIBUIDOR #" . $id;
                $movimiento_cajas->numero_referencia = 51;
                $movimiento_cajas->tipo_referencia = "gastos";
                $movimiento_cajas->save();

                $pagosconcen = pagocontrados::find($tip['id']);
                $pagosconcen->delete();
              }else{
                return redirect()->back()->with('errorsaldo', 'No se puede eliminar el pago conciliado, el saldo de la cuenta es insuficiente.');
              }
            }
        }
    }
    
    foreach ($cajas as $index => $cal) {
        foreach ($tipos_ids as $tip) {
            if ($cal['id'] == $tip['idcuenta']) {
              if($cal['saldototal'] > $tip['total']){
                $cajas[$index]['saldototal'] -= $tip['total'];
                $movimiento_cajas = new historial_cajas();
                $movimiento_cajas->id_caja = $cal['id'];
                $movimiento_cajas->id_empleado = $user;
                $movimiento_cajas->fecha = $fecha;
                $movimiento_cajas->tipo_movimiento = "GASTO";
                $movimiento_cajas->estado = "A";
                $movimiento_cajas->egreso = $tip['total'];
                $movimiento_cajas->ingreso = 0.00;
                $movimiento_cajas->saldo = $cajas[$index]['saldototal'];
                $movimiento_cajas->concepto = "CANCELACIÓN DE PAGO CONCILIADO AL DISTRIBUIDOR #" . $id;
                $movimiento_cajas->descripcion = "SE CANCELO EL PAGO DE LA LISTA DE CONCILIADOS";
                $movimiento_cajas->responsable = "DISTRIBUIDOR #" . $id;
                $movimiento_cajas->numero_referencia = 47;
                $movimiento_cajas->tipo_referencia = "gastos";
                $movimiento_cajas->save();
                $pagosconcen = pagocontrados::find($tip['id']);
                $pagosconcen->delete();
            }else{
              return redirect()->back()->with('errorsaldo', 'No se puede eliminar el pago conciliado, el saldo de la caja es insuficiente.');
            }
          }
        }
    }
    foreach($cuentas as $index => $cal){
      $cuentas = cuentas::find($cal['id']);
      $cuentas->saldo_actual = $cal['saldototal'];
      $cuentas->save();
    }
    foreach($cajas as $index => $cal){
      $cajas = Cajas::find($cal['id']);
      $cajas->saldo_actual = $cal['saldototal'];
      $cajas->save();
    }

      return redirect()->back()->with('success', 'Pagos eliminados correctamente.');

      }catch(\Illuminate\Database\QueryException $ex){
        return back()->with("warningBD","no guardado correctamente");
      }
  }
  

  public function leerpagostxt(Request $request)
  {
    try {
      $varpantallas = $this->Traermenuenc();
      $varsubmenus = $this->Traermenudet();
      $tippago = $request->get('odp');
      $status = "N";
      $idusuario = auth()->user()->id;
      $odp = $this->obtenerodpxusuariologueado($idusuario);
      $ruta = 'pagos';
      $nombre = 'pago.txt';
      $fecha = "";        //6
      $tipo = "";         //2
      $cadena1 = "";        //6
      $formapago = "";      //3
      $cadena2 = "";        //11
      $referencia = "";     //20
      $comentarios = "";    //30
      $monto = 0;           //11
      $contadorceros = 0;
      $contadorcerosmonto = 0;
      $caracteresmonto = 0;
      $montototal = 0;
      $caracteres = 0;
      $arreglo = array();
      $distribuidorsimple = '';
      $boelanodis = 0;
      $boleanomon = 0;
      $disante = 0;
      $vardistribuidores = $this->obtenerdistribuidoresactivos();
      

      if ($request->hasFile("archivo")) {
        $archivo = $request->file('archivo');
        Storage::putFileAs($ruta, $archivo, $nombre);
        $errorMsg = "";
        $ruta = storage_path() . "/app/pagos/pago.txt";
        $archivos = fopen($ruta, 'r');
        $countEnc=0;
        $countEnc2=0;
        $lineaError="";
        $listaDatosProcesados = [];
        while (!feof($archivos)) {
          if ($tippago == 5) {
            if($countEnc===0)
            {
              $lineaError .= "<table border='1' cellpadding='5' cellspacing='0'>
              <tr>
                  <th>FECHA</th>
                  <th>TIPO</th>
                  <th>CADENA_1</th>
                  <th>FORMA DE PAGO</th>
                  <th>CADENA_2</th>
                  <th>REFERENCIA</th>
                  <th>MONTO</th>
                  <th>COMENTARIOS</th>
                  <th>ERROR</th>
              </tr>"; // Tabla inicial
              $countEnc = $countEnc + 1;
            }
            
            $caracteres = 0;
            $contadorceros = 0;
            $nombreD = '';
            //   $linea = fgets($archivos)."<br/>";
            //   $fecha = substr($linea,0,6);
            //   $tipo = substr($linea,6,2);
            //   $cadena1 =substr($linea,8,6);
            //   $formapago =substr($linea,14,3);
            //   $cadena2 =substr($linea,17,11);
            //   $referencia =substr($linea,28,20);
            //   $cadena = substr($referencia,10,20);
            //   $cadena1 =substr($cadena,0,9);
            //   $comentarios =substr($linea,48,30);
            //   $monto =substr($linea,78,11);
            //   $registro = '';
            //   $distribuidor = substr($referencia,10,-2);
            $linea = fgets($archivos) . "<br/>";                 
            $fecha = substr($linea, 0, 6);
            $tipo = substr($linea, 6, 2);
            $cadena1 = substr($linea, 8, 6);
            $formapago = substr($linea, 14, 3);
            $cadena2 = substr($linea, 17, 11);
            $referencia = substr($linea, 28, 20);
            $cadena = substr($referencia, 10, 20);
            $cadena1 = substr($cadena, 0, 9);
            $comentarios = substr($linea, 48, 30);
            $monto = substr($linea, 78, 8);
            $registro = '';
            $distribuidor = substr($referencia, 10, -1);

            //$linea2 = trim($linea); 
            // Validar si la referencia contiene "03"
            $linea2 = trim(str_replace(["<br/>", "\n", "\r"], '', $linea));
            
            // Verifica si la línea tiene al menos 28 caracteres
            if (!empty($linea2) && strlen($linea2) >= 28) {
                $referencia2 = substr($linea2, 28, 20);
                //echo "<br/>Mensaje antes del referencia3333: " . $referencia2 . PHP_EOL;
                if (!str_contains($referencia, "03")) {
                    // Si no contiene "03", arrojar un mensaje de error
                    $errorMsg = "La referencia no contiene '03'. Verifica los datos.";
                    $lineaError .= "<tr>
                                        <td>" . $fecha . "</td>
                                        <td>" . $tipo . "</td>
                                        <td>" . $cadena1 . "</td>
                                        <td>" . $formapago . "</td>
                                        <td>" . $cadena2 . "</td>
                                        <td>" . $referencia . "</td>
                                        <td>" . $monto . "</td>
                                        <td>" . $comentarios . "</td>
                                        <td>" . $errorMsg . "</td>
                                    </tr>";
                        continue; // Continuar con la siguiente línea
                }
            }

            $primerEspacio = strpos($linea2, ' ');
            $lineaAntesDeEspacio = ($primerEspacio !== -1) ? substr($linea2, 0, $primerEspacio) : '';
            $lineaDespuesDelEspacio = ($primerEspacio !== false) ? substr(str_replace("<br/>", "", $linea2), $primerEspacio + 1) : '';
            $numeroDeDigitos = preg_match_all('/\d/', $lineaDespuesDelEspacio);
                
            if ($primerEspacio !== 60 && $primerEspacio!='')
            {
              $errorMsg = "La longitud de la cadena no coincide, verifica los datos de la cadena por favor.";
              $lineaError .= "<tr>
                                <td>" . $fecha . "</td>
                                <td>" . $tipo . "</td>
                                <td>" . $cadena1 . "</td>
                                <td>" . $formapago . "</td>
                                <td>" . $cadena2 . "</td>
                                <td>" . $referencia . "</td>
                                <td>" . $monto . "</td>
                                <td>" . $comentarios . "</td>
                                <td>" . $errorMsg . "</td>
                             </tr>";
              continue; // Pasar a la siguiente línea
            }
            else
            {
              if($numeroDeDigitos > 10 && $numeroDeDigitos !='')
              {
                $errorMsg = "Error en el monto";
                $lineaError = "Error en monto: " . $lineaDespuesDelEspacio;
              }
            }
            if ($linea == "") {
            } else {
              $contadorcerosmonto = 0;
              $caracteresmonto = 0;
              for ($i = 0; $i <= strlen($distribuidor) - 1; $i++) {
                if ($distribuidor[$i] == 0) {
                  $contadorceros = $contadorceros + 1;
                  $boelanodis = 1;
                } else {

                  $montototal = substr($monto, 0, 8);
                  $caracteres = $contadorceros - 1;

                  $distribuidorsimple = substr($distribuidor, $contadorceros, $caracteres);
                  $listadis = $this->obtenerdatospago($distribuidorsimple);
                  $checarpago = $this->checarsiyapagaron($distribuidorsimple);
                  foreach ($checarpago as $statu) {
                    $status = $statu->estado;
                  }
                  //  echo $status;
                  foreach ($listadis as $list) {

                    $dia = substr($fecha, 0, 2);
                    $mes = substr($fecha, 2, 2);
                    $ano = substr($fecha, 4, 2);
                    $fecha = "20" . $ano . $mes . $dia;
                    $fecha = Carbon::parse($fecha)->format('Y-m-d');
                    $nombreD = $list->nombre_dis;
                    array_push($arreglo, array(
                      'referencia' => $referencia,
                      'distribuidor' => $distribuidorsimple,
                      'cliente' => $distribuidorsimple,
                      'nombre' => $nombreD,
                      'montopago' => $montototal,
                      'fechapago' => $fecha,
                      'estado' => $status
                    ));
                    $listaDatosProcesados[] = [
                      'fecha' => $fecha,
                      'distribuidor' => $distribuidorsimple,
                      'montototal' => $montototal
                  ];
                  }
                  break;
                }
              }
            }
          }

          if ($tippago == 4 || $tippago == 7) {
            $caracteres = 0;
            $contadorceros = 0;
            $nombreD = '';
            $arreglo = [];
            if($countEnc2===0)
            {
                $lineaError .= "<table border='1' cellpadding='5' cellspacing='0'>
                    <tr>
                        <th>INICIAL CADENA</th>
                        <th>CADENA_1</th>
                        <th>CADENA_2</th>
                        <th>CADENA_3</th>
                        <th>ERROR</th>
                    </tr>"; // Tabla inicial
                    $countEnc2 = $countEnc2 + 1;
            }
            while (!feof($archivos)) {
                $linea = fgets($archivos);
                $linea2 = trim($linea); 
                // Validar si la línea es de tipo H
                $letraEmpieza = substr($linea2, 0, 1);
                if ($letraEmpieza === "H") {
                    // Encontrar el primer espacio
                    $posEspacio = strpos($linea, " ");
                    if ($posEspacio === false) {
                        $errorMsg .= "<tr><td>Error: No se encontró un espacio en la línea 'H'.</td><td>$linea2</td></tr>";
                        //echo "errorMsg: " . $errorMsg . PHP_EOL;
                        continue;
                    }
                    
                    // Contar caracteres hacia atrás (11) y hacia adelante (8)
                    $haciaAtras = substr($linea2, 0, $posEspacio);
                    $haciaAdelante = substr($linea2, $posEspacio + 1);
                    
                    if (strlen($haciaAtras) !== 11 || strlen($haciaAdelante) !== 12) {
                      $errorMsg .= "Los caracteres no cumplen con las longitudes requeridas</tr>";
                        $lineaError .= "<tr>
                                <td>" . $letraEmpieza . "</td>        
                                <td>" . $haciaAtras . "</td>
                                <td>" . $haciaAdelante . "</td>
                                <td>" . '' . "</td>
                                <td>" . $errorMsg . "</td>
                             </tr>";
                        continue;
                    }
        
                    // Si pasa las validaciones de línea H, continuar con la siguiente línea
                    continue;
                }
        
                if ($letraEmpieza === "T") {
                  // Verificamos la longitud de la línea
                  $haciaAdelanteT = substr($linea2, 0);
                  // Comprobamos que la línea tenga exactamente 29 caracteres
                  if (strlen($haciaAdelanteT) !== 29) {
                      // Si no tiene 29 caracteres, mostramos un mensaje de error
                      $errorMsg .= "Los caracteres de la linea T no cumplen con las longitudes requeridas</tr>";
                        $lineaError .= "<tr>
                                <td>" . $letraEmpieza . "</td> 
                                <td>" . $haciaAdelanteT . "</td>
                                <td>" . '' . "</td>
                                <td>" . '' . "</td>
                                <td>" . $errorMsg . "</td>
                             </tr>";
                      continue;
                  }
                }
        
                // Procesar la línea D
                if ($linea != "" && $letraEmpieza === "D") {
                    $haciaAdelanteD = substr($linea2, 0);
                    // Comprobamos que la línea tenga exactamente 81 caracteres
                    if (strlen($haciaAdelanteD) !== 81) {
                        // Si no tiene 81 caracteres, mostramos un mensaje de error
                        $errorMsg .= "Los caracteres de la linea D no cumplen con las longitudes requeridas</tr>";
                          $lineaError .= "<tr>
                                  <td>" . $letraEmpieza . "</td> 
                                  <td>" . $haciaAdelanteD . "</td>
                                  <td>" . '' . "</td>
                                  <td>" . '' . "</td>
                                  <td>" . $errorMsg . "</td>
                              </tr>";
                          //echo "errorMsg: " . $errorMsg . PHP_EOL;
                        continue;
                    }


                    // Obtener los datos de la línea
                    $fecha = substr($linea, 1, 8); // Fecha a partir del índice 1
                    $referencia = substr($linea, 9, 13); // Referencia a partir del índice 9
                    $monto = substr($linea, 73, 8); // Monto desde el índice 73
                    $distribuidor = substr($referencia, 2, 9); // Extraer distribuidor
                    $distribuidorsimple = ltrim($distribuidor, "0"); // Quitar ceros iniciales
                    $montototal = substr($monto, 0, 6); // Monto total simplificado

                    if (!str_contains($referencia, "03")) {
                      // Si no contiene "03", arrojar un mensaje de error
                      $errorMsg = "La referencia no contiene '03'. Verifica los datos.";
                      $lineaError .= "<tr>
                                          <td>" . $letraEmpieza . "</td> 
                                          <td>" . $referencia . "</td>
                                          <td>" . $monto . "</td>
                                          <td>" . $fecha . "</td>
                                          <td>" . $errorMsg . "</td>
                                      </tr>";
                      continue; // Continuar con la siguiente línea
                    }
                    
                    if ($disante == $distribuidorsimple) {
                        // Si el distribuidor es el mismo que el anterior, no se hace nada
                    } else {
                        $disante = $distribuidorsimple;
        
                        // Obtener información del distribuidor
                        $listadis = $this->obtenerdatospago(intval($disante));
                        $checarpago = $this->checarsiyapagaron(intval($disante));
                        $status = '';
        
                        foreach ($checarpago as $statu) {
                            $status = $statu->estado;
                        }
        
                        $fecha = Carbon::parse($fecha)->format('Y-m-d'); // Formatear fecha
                        $nombreD = '';
        
                        foreach ($listadis as $list) {
                            $nombreD = $list->nombre_dis;
                        }
        
                        // Agregar los datos al arreglo
                        array_push($arreglo, array(
                            'referencia' => $referencia,
                            'distribuidor' => $distribuidorsimple,
                            'cliente' => $distribuidorsimple,
                            'nombre' => $nombreD,
                            'montopago' => $montototal,
                            'fechapago' => $fecha,
                            'estado' => $status
                        ));
                        $listaDatosProcesados[] = [
                          'fecha' => $fecha,
                          'distribuidor' => $distribuidorsimple,
                          'montototal' => $montototal
                      ];
                    }
                }
            }
        }
        
        }
        $lineaError .= "</table>";
        $mensajeResultado = "";
        if (strpos($lineaError, "<tr>") !== false) {
            // Si hubo errores, retornarlos
            if (!empty($errorMsg)) {
              return back()->with("warning", "Se encontraron errores en las siguientes líneas:<br/>" . $lineaError);
            }
        }

        $varcuenta = $this->obtenercuentasActivas();
        $varcaja = $this->obtenerCajasActivas();
        $permisos1 = $this->forpermisos('aplicacion_pagos_referidos');
        $permisos2 = $this->forpermisos('aplicacion_pagos_manuales');
        $permisos3 = $this->forpermisos('fecha_pagosmanuales');
        $idusuario = auth()->user()->id;
        $varSucursalesUser = $this->obtenerSucursalesxUser($idusuario);
        $modulo1 = "pago_dis_referenciados";
        $modulo2 = "pago_dis_manuales";
        $varManejoCuentas1 = $this->obtenerManejoCuentas($idusuario, $modulo1);
        $vardispagoconcentado = $this->distribuidoresconpagoconcentrado();
        $varManejoCajas2 = $this->obtenerManejoCajas($idusuario, $modulo2);
        $varManejoCuentas2 = $this->obtenerManejoCuentas($idusuario, $modulo2);
        $varpagoenc = $this->obtenerpagoencALL();
        $consulta1 = $this->obtenerPagosconsiliados();
        $consulta2 = $this->obtenerPagoRelaciones2();
        $date = Carbon::now()->subMonth();


        $fecha = $date->format('Y-m-d');
        $varvalidaRel = DB::select("select fecha_relacion,fecha_corte_final from tblpagos_enc order by fecha_relacion desc limit 1;");
        foreach($varvalidaRel as $rel){
          $relacion_actual = $rel->fecha_relacion;
          $startDate = $rel->fecha_corte_final;
        }

        $relacion_actual = Carbon::parse($relacion_actual);
        $dia =  $relacion_actual->format('d');
        $relacion_actual = $relacion_actual->format('Y-m-d');
        $startDate = Carbon::parse($startDate)->addDay()->format('Y-m-d');

        $districon2porcientomas = DB::select("select * from tblhistorial_dosporciento where tblhistorial_dosporciento.fecha_relacion  = ?;",[$relacion_actual]);
        $districon2porcientomas =  collect($districon2porcientomas);
          
        if($dia == 30 || $dia == 31){
          $dia =  $date->format('d');
          $varlistacom =  $this->obtenercomisondia2($dia);
        }elseif($dia == 15){
          $dia =  $date->format('d');
          $varlistacom =  $this->obtenercomisondia1($dia);
        }

        //trait para base de calculo
        $obtprocompletoxrel = $this->obtprocompletoxrel($relacion_actual);

        //Porcentajes de datos iniciales
        $porcen_comision = 0;
        $datosini = $this->obtenerdatosiniciales();
        foreach ($datosini as $key) {
          if ($key->concepto == "COMISION") {
            $porcen_comision = $key->cantidad;
          }
        }

        if (!empty($listaDatosProcesados)) {
          $totalLineasProcesadas = count($arreglo); // Si $arreglo contiene todas las líneas válidas procesadas
          
          $mensajeResultado .= "Archivo procesado correctamente.<br/>";
          $mensajeResultado .= "Líneas válidas procesadas: <strong>" . $totalLineasProcesadas . "</strong><br/>";

              $mensajeResultado .= "<br/><strong>Datos procesados:</strong><br/>";
              $mensajeResultado .= "<table border='1' cellpadding='5' cellspacing='0'>";
              $mensajeResultado .= "<tr><th>Fecha</th><th>Distribuidor</th><th>Monto Total</th></tr>";
  
              foreach ($listaDatosProcesados as $datos) {
                  $mensajeResultado .= "<tr>";
                  $mensajeResultado .= "<td>" . $datos['fecha'] . "</td>";
                  $mensajeResultado .= "<td>" . $datos['distribuidor'] . "</td>";
                  $mensajeResultado .= "<td>" . $datos['montototal'] . "</td>";
                  $mensajeResultado .= "</tr>";
              }
  
              $mensajeResultado .= "</table>";
          
          //return back()->with("resultadoProcesamiento", "Se encontraron errores en las siguientes líneas:<br/>" . $lineaError);
        }
        session()->flash('resultadoProcesamiento', $mensajeResultado);
        return view('vales.pagos.controlpagosrefe', compact('varpantallas',
         'districon2porcientomas', 'varsubmenus', 'arreglo', 'odp', 'varcuenta', 'vardistribuidores', 'obtprocompletoxrel', 'porcen_comision','fecha','startDate',
          'varSucursalesUser', 'permisos3', 'varcuenta', 'permisos1', 'permisos2', 'varManejoCajas2', 'varManejoCuentas2', 'varManejoCuentas1',
           'varcaja', 'vardispagoconcentado', 'varpagoenc', 'consulta1', 'consulta2', 'varlistacom', 'relacion_actual'))->with('resultadoProcesamiento', $mensajeResultado);
        // fclose($archivo);
      } else {
        return back()->with("warning", "No se subio el archivo");
      }
    } catch (\Illuminate\Database\QueryException $ex) {
      return back()->with("warningBD", "no guardado correctamente");
    }
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
    $referencia = $request->get('referencia');
    $distribuidor = $request->get('distribuidor');
    $nombre = $request->get('nombre');
    $total = $request->get('total');
    $fecha_pago = $request->get('fecha_pago');
    $estado = $request->get('estado');

    if ($referencia > 0) {
      for ($i = 0; $i <= count($referencia) - 1; $i++) {
        $result[$i] = array(
          'referencia' => $referencia[$i],
          'nombre' => $nombre[$i],
          'iddistribuidor' => $distribuidor[$i],
          'total' => $total[$i],
          'fecha_pago' => $fecha_pago[$i]
        );

        // LEER LAS LINEAS DE LA TABLA
        $varnombre = $result[$i]['nombre'];
        $vardistribuidor = $result[$i]['iddistribuidor'];
        $varreferencia = $result[$i]['referencia'];
        $montopagado = $result[$i]['total'];
        $varfecha_pago = $result[$i]['fecha_pago'];
        $montosinproteccion_saldo = 0;
        $montomascomision = 0;
        $montoexcedente = 0;
        $pagos_incpletos = 0;
        $obtenerlineaspagoenc = $this->obtener_relaciones_a_pagar($vardistribuidor);
        $relaciones_a_pagar = count($obtenerlineaspagoenc);

        // BUSCAMOS SI EXISTE UNA RELACION POR PAGAR
        if (!$obtenerlineaspagoenc->isEmpty()) {
          foreach ($obtenerlineaspagoenc as $linea) {
            $salex = 0;
            $montosincomision = 0;
            $saldo_pagar = $linea->saldo_pagar;
            $estado = $linea->estado;
            $idpagoencabezado = $linea->id;
            $capital_regresado = 0;
            $fecha_relacion = $linea->fecha_relacion;
            $abonado = $linea->otrosconceptos1;
            $saldo_atrasado = $linea->otrosconceptos2;
            $proteccion_saldo = $linea->proteccion_saldo;
            $montoxtransaccion = $linea->costo_transaccion;
            $fechacorteini = $linea->fecha_corte_inicio;
            $fechacortefin = $linea->fecha_corte_final;
            $monto_total = $linea->monto_total;
            $statuspresdet = "";
            $saldo_atrasadocli = 0;
            $saldorestantecliente = 0;
            $comisioncalcul = 0;
            $montoreal = 0;
            $merece_comision = 0;
            $saldodis = 0;
            $clientesxdis = $this->obtenerdetallepagoClientes($vardistribuidor, $fecha_relacion);
            $totalcliente = count($clientesxdis);

            // REVISAR SI CUENTAS CON SALDO EXCEDENTE
            $saldoexcedente = $this->saldoexdistribuidor($vardistribuidor);
            if ($saldoexcedente->isEmpty()) {
              echo "<br> No hay excedente | ";
              $salex = 0;
              // QUTAMOS LOS 2 CONCEPTOS QUE SE INCLUYEN EN EL PAGO
              $montosinproteccion_saldo = (($montopagado) - $montoxtransaccion - $proteccion_saldo);
            } else {
              foreach ($saldoexcedente as $s) {
                echo "Si hay excedente | ";
                // SUMAMOS EXDENTE E INCATIVAMOS PARA USARLO
                $salex = $s->monto;
                if ($salex > 0) {
                  $actualizastatus = DB::update("update tblexcedentedistribuidor set estado = 'I' where id = ?", [$s->id]);
                }
              }
              // QUTAMOS LOS 2 CONCEPTOS QUE SE INCLUYEN EN EL PAGO
              $montosinproteccion_saldo = (($montopagado) - $montoxtransaccion - $proteccion_saldo) + $salex;
            }

            //APLICAMOS LOS VALORES DEL DINERO QUE MANEJAREMOS
            $abonado = $monto_total + $montosinproteccion_saldo;
            $saldo = $montosinproteccion_saldo;

            // VALIDACION DE MERECER COMISION
            $comisiones = $this->validacomision($linea->fecha_relacion, $varfecha_pago, $montosinproteccion_saldo, $saldo_pagar, $vardistribuidor, $fechacortefin, $tipo_pago);
            if ($comisiones->isEmpty()) {
              echo "No tiene bonificación | ";
              // AÑADIMOS A IMPORTE DE DINERO EL PAGO DE LA COMISION
              $comisioncalcul = 0;
              $montoreal = $montosinproteccion_saldo;
              $merece_comision = 'no';
            } else {
              echo "Si tiene bonificación | ";
              // AÑADIMOS A IMPORTE DE DINERO EL PAGO DE LA COMISION
              $comisioncalcul = $comisiones["comision"];
              $montoreal = $comisiones["montototal"];
              $merece_comision = $comisiones["merececomision"];
              $capital_regresado = $comisiones["capital_regresado"];
              echo "Capital Regresado :" . $capital_regresado . " | ";

              $actualizabandera = DB::update("update tblpagos_enc set capital_regresado = ? where id = ?;", [$capital_regresado, $idpagoencabezado]);
            }
            echo "<br>" . "Monto Real : " . $montoreal . " Monto Sin PS : " . $montosinproteccion_saldo . " Calculo de Comision : " . $comisioncalcul . "<br>";

            // PAGO COMPLETO
            if ($montoreal == $saldo_pagar) {
              echo "<br>" . "Pago Completo" . "<br>";
              //ACTUALIZAMOS QUE ATRASO DE DV QUEDE EN 0 Y EL COSTO_TRANSACCION QUEDARA EN 0
              $UpdAt = DB::select('update tblpagos_enc set otrosconceptos2 = "0", costo_transaccion = 16  WHERE id = ?;', [$idpagoencabezado]);

              //ACTUALIZAMOS QUE ATRASO DE CLIENTES QUEDE EN 0 
              // foreach($clientesxdis as $clientes)
              // {
              //   $UpdAtr = DB::select('update tblprestamos_valesdet set saldo = "0" WHERE id = ?;', [$clientes->id]);
              // }

              // FUNCION PARA APLICAR EL PAGO
              $funcion = $this->insertarPagos(
                $vardistribuidor,
                $idpagoencabezado,
                $montosinproteccion_saldo,
                $saldo_pagar,
                0,
                $varfecha_pago,
                $fecha_relacion,
                $montoreal,
                $comisioncalcul,
                0,
                0,
                "I",
                "I",
                0,
                $cuenta,
                $tipo,
                $totalcliente,
                $fecha,
                $user,
                0,
                $tipos,
                $proteccion_saldo,
                $montoxtransaccion,
                $varreferencia,
              );

              //ACTUALIZAMOS QUE ATRASO DE CLIENTES QUEDE EN 0 
              foreach ($clientesxdis as $clientes) {
                echo "<br> SI ACTUALIZO: " . $clientes->idcliente;
                $UpdAtr = DB::select('update tblprestamos_valesdet set saldo = "0" WHERE id = ?;', [$clientes->id]);
              }

              // CERRAR FUNCION SI YA NO TIENES SALDO PARA APLICAR
              $montosinproteccion_saldo = $montosinproteccion_saldo - $saldo_pagar;
              if ($montosinproteccion_saldo < 0) {
                $montosinproteccion_saldo = 0;
              }

              $pCompleto = 1;
            }

            // PAGO COMPLETO CON EXCEDENTE
            if ($montoreal > $saldo_pagar) {
              echo "<br>" . "Pago Completo más Excedente" . "<br>";

              //CHECAMOS CUANTO EXCEDENTE TENEMOS PARA GUARDALO 
              $montoexcedente = $montoreal - $saldo_pagar;
              //MONTO REAL = LO QUE RECIBIMOS, MAS EL EXCEDENTE, MAS COMISION
              $montoreal = $montoreal - $montoexcedente;
              $montosinproteccion_saldo = $montosinproteccion_saldo - $montoexcedente;
              echo "<br> | " . "Excedente : " . $montoexcedente . " Monto Real: " . $montoreal . " Monto Sin PS: " . $montosinproteccion_saldo . "<br>";

              //APLICAR PAGO COMPLETO
              //NO GENERAMOS ATRASOS, ASÍ QUE ACTUALIZAMOS EL CAMPO
              $UpdAtr = DB::select('update tblpagos_enc set otrosconceptos2 = "0" WHERE id = ?;', [$idpagoencabezado]);

              //MANDAMOS TODOS LOS CALCULOS PARA INSERTAR EL PAGO DE LA RELACION
              $funcion = $this->insertarPagos(
                $vardistribuidor,
                $idpagoencabezado,
                $montosinproteccion_saldo,
                $saldo_pagar,
                0,
                $varfecha_pago,
                $fecha_relacion,
                $montoreal,
                $comisioncalcul,
                0,
                0,
                "I",
                "I",
                $montoexcedente - $salex,
                $cuenta,
                $tipo,
                $totalcliente,
                $fecha,
                $user,
                0,
                $tipos,
                $proteccion_saldo,
                $montoxtransaccion,
                $varreferencia
              );

              //ACTUALIZAMOS QUE ATRASO DE CLIENTES QUEDE EN 0 
              foreach ($clientesxdis as $clientes) {
                $UpdAtr = DB::select('update tblprestamos_valesdet set saldo = "0" WHERE id = ?;', [$clientes->id]);
              }

              //SALIMOS DE FUNCION
              $montosinproteccion_saldo = $montosinproteccion_saldo - $saldo_pagar;
              if ($montosinproteccion_saldo < 0) {
                $montosinproteccion_saldo = 0;
              }

              $relacionesfaltantes = $this->relacionesfaltantes($vardistribuidor);

              //SALIMOS DE FUNCION
              if ($montoexcedente < 50 || $relacionesfaltantes->isEmpty()) {
                //GUARDAR EXCEDENTE
                $insertaexcedente = new excedentes();
                $insertaexcedente->id_distribuidor = $vardistribuidor;
                $insertaexcedente->fecha_pago = $varfecha_pago;
                $insertaexcedente->monto = $montoexcedente;
                $insertaexcedente->estado = "A";
                $insertaexcedente->created_at = $fecha;
                $insertaexcedente->save();

                $montosinproteccion_saldo = $montosinproteccion_saldo - $saldo_pagar;
                $montoexcedente = 0;
                echo "<br> no aplico para seguir pagondo con excedente";
              } else {
              }

              $pExcedente = 1;
            }

            // PAGO INCOMPLETO
            if ($montoreal < $saldo_pagar && $montoreal > 0 && $montosinproteccion_saldo > 0) {
              $finalcomision = Carbon::parse($fecha_relacion)->addDays(5);
              $fechaf = $finalcomision->format('Y-m-d');
              $monto_conciliado = 0;

              $ultima_relacion = "";
              $ultimarela = DB::select("select tblpagos_enc.fecha_relacion from tblpagos_enc  where id_distribuidor = ? order by fecha_relacion DESC limit 1;", [$vardistribuidor]);
              foreach ($ultimarela as $key) {
                $ultima_relacion = $key->fecha_relacion;
              }
              $ultima_relacion = Carbon::parse($ultima_relacion);
              $ultima_relacion = $ultima_relacion->format('Y-m-d');

              //si esta entre fecha de comisiones se le da la opcion de un pago conciliado
              //y juntarlos al final para completar el pago
              if ($varfecha_pago >= $fechacorteini && $varfecha_pago <= $fechaf && $ultima_relacion == $fecha_relacion) {
                $pagosyacontrados = $this->obtenerpagosconcetrandosporquincenaydis($vardistribuidor, $fecha_relacion, $montoreal);
                $validaconcentrados = $this->obtenerpagosconcixdisrela($vardistribuidor, $fecha_relacion);

                // if($pagosyacontrados->isEmpty())
                // {
                echo "<br>" . "Pago Conciliado" . "<br>";
                $insertapagoconcentrado = new pagocontrados();
                $insertapagoconcentrado->id_distirbuidor = $vardistribuidor;
                $insertapagoconcentrado->saldo_pagar_real = $saldo_pagar;
                if ($montoexcedente > 0) {
                  // echo "entro";
                  $monto_conciliado = $montoexcedente;
                } else {
                  // echo "entro al 2";
                  $monto_conciliado = $montoreal;
                }

                if (!$validaconcentrados->isEmpty()) {
                  $monto_conciliado = $montoreal + $proteccion_saldo;
                }

                $insertapagoconcentrado->intento_pago = $monto_conciliado;
                $insertapagoconcentrado->fecha_relacion = $fecha_relacion;
                $insertapagoconcentrado->fecha_intento_pago = $varfecha_pago;
                $insertapagoconcentrado->status = "A";
                $insertapagoconcentrado->referencia_pago = $varreferencia;
                $insertapagoconcentrado->idcuenta = $cuenta;
                $insertapagoconcentrado->tipocuenta = $tipo;
                $insertapagoconcentrado->created_at = $fecha;

                if ($insertapagoconcentrado->save()) {
                  $pagos_incpletos = $pagos_incpletos + 1;
                }
                //afectamos las cuentas
                $tipomov = "PAGO";
                $concepto = "FECHA DE CAPTURA DEL PAGO " . $varfecha_pago;
                $descripcion = "PAGO CONCILIADO APLICADO DE DISTRIBUIDOR #" . $vardistribuidor . " A LA RELACION DEL " . $fecha_relacion;
                $afectarhistorialcuentas = $this->afectarhistorialcuentaspago($tipo, $cuenta, $montopagado, $comisioncalcul, $varfecha_pago, $vardistribuidor, $idpagoencabezado, $tipomov, $concepto, $descripcion);
                $pConciliado = 1;
                // }
              }
              // se aplica el pago normal sin comision 
              else {
                echo "<br>" . "Pago Incompleto sin comision" . "<br>";
                $pagorealizado = $montoreal;
                $saldo_atrasado = $saldo_pagar - $montoreal;
                echo "<br> Atraso Generado:" . $saldo_atrasado;

                if ($tipo == "CAJA") {
                  $updateenc = DB::update('update tblpagos_enc set estado = "P", status_atraso = "I", fecha_pago = ?, monto_total = ?, otrosconceptos1 = ?, otrosconceptos2 = ?, tipo_cuenta = ?, caja = ?  where id = ?;', [$varfecha_pago, $pagorealizado, $montoreal, $saldo_atrasado, $tipo, $cuenta, $idpagoencabezado]);
                } else {
                  $updateenc = DB::update('update tblpagos_enc set estado = "P", status_atraso = "I", fecha_pago = ?, monto_total = ?, otrosconceptos1 = ?, otrosconceptos2 = ?, tipo_cuenta = ?, cuenta = ?  where id = ?;', [$varfecha_pago, $pagorealizado, $montoreal, $saldo_atrasado, $tipo, $cuenta, $idpagoencabezado]);
                }

                if ($updateenc > 0) {
                  $UpdAtr = DB::select('update tblpagos_enc set otrosconceptos2 = "0" WHERE id = ?;', [$idpagoencabezado]);

                  //return $montosinproteccion_saldo;
                  $funcion = $this->insertarPagos(
                    $vardistribuidor,
                    $idpagoencabezado,
                    $montosinproteccion_saldo,
                    $saldo_pagar,
                    0,
                    $varfecha_pago,
                    $fecha_relacion,
                    $montoreal,
                    $comisioncalcul,
                    $saldo_atrasado,
                    0,
                    "I",
                    "I",
                    0,
                    $cuenta,
                    $tipo,
                    $totalcliente,
                    $fecha,
                    $user,
                    0,
                    $tipos,
                    $proteccion_saldo,
                    $montoxtransaccion,
                    $varreferencia
                  );

                  $pagocli = 0;
                  //ACTUALIZAR ESTADOS DE ABONOS COMPLETOS CLIENTES
                  foreach ($clientesxdis as $clientes) {

                    if ($clientes->status == "P" && $clientes->saldo == 0) {
                      $UpdAtr1 = DB::select('update tblprestamos_valesdet  pres_det set  pres_det.status = ?, pres_det.saldo = ? WHERE pres_det.id = ? ;', ["P", 0, $clientes->id]);
                    } else {
                      $abonadocliente = 0;
                      $obtenerabonado = DB::select("select pago_total - saldo as abonado from tblprestamos_valesdet where id = ?;", [$clientes->id]);

                      foreach ($obtenerabonado as $abn) {
                        $abonadocliente = $abn->abonado;
                      }

                      if ($clientes->saldo == $saldo) {
                        $saldo = $clientes->pago_total;
                      }

                      if ($clientes->status == "N" || $clientes->saldo > 0) {

                        if ($clientes->saldo > 0) {
                          $pagocli = $clientes->saldo;
                        } else {
                          $pagocli = $clientes->pago_total;
                        }

                        //actualizamos el pago por cliente
                        if ($saldo >= $clientes->pago_total) {
                          $saldo_atrasadocli = 0;
                          $statuspresdet = "P";
                          $saldo = 0;
                        }
                        //pago incompleto cliente
                        else {

                          $saldo_atrasadocli = $pagocli - $saldo;
                          $statuspresdet = "P";
                          $saldo = 0;

                        }
                      }

                      $UpdAtr1 = DB::select('update tblprestamos_valesdet  pres_det set  pres_det.status = ?, pres_det.saldo = ? WHERE pres_det.id = ? ;', [$statuspresdet, $saldo_atrasadocli, $clientes->id]);

                      if ($saldo <= 0) {
                        if ($montoexcedente > 0) {
                          $saldofaltanteendet = 0;
                          $obtenersaldopendiente = DB::SELECT("select c.id,c.saldo from tblclientes_vales a
                                inner join tblprestamos_valesenc b on  a.id =b.idcliente
                                inner join tblprestamos_valesdet c on c.idprestamo_vales = b.id
                                where c.fecha_pago = ? and a.iddistribuidor= ? and c.saldo > 0 and b.status = 'A';", [$fecha_relacion, $vardistribuidor]);
                          foreach ($obtenersaldopendiente as $s) {
                            $saldofaltanteendet = $s->saldo;
                            $idspre = $s->id;
                          }

                          if ($saldofaltanteendet == $montoexcedente) {
                            $UpdAtr2 = DB::select('update tblprestamos_valesdet pres_det set pres_det.saldo = 0 WHERE pres_det.id = ? ;', [$s->id]);
                            return $UpdAtr2;
                            if ($UpdAtr2 > 0) {
                              echo "se actualizo la linea" . $s->id;

                            }
                          }
                        }
                        break;
                      }
                    }
                  }

                  if ($montosinproteccion_saldo < 0) {
                    $montosinproteccion_saldo = 0;
                  }
                } else {
                  echo "error no actualizo";
                }

                if ($montosinproteccion_saldo = 0) {
                } else {
                  $ultimop = "";
                  //nueva liena a crear con su atraso
                  $pagosenc = new pagosenc();
                  $pagosenc->id_distribuidor = $vardistribuidor;
                  $pagosenc->saldo_pagar = $saldo_atrasado;
                  $pagosenc->comision = 0;
                  $pagosenc->interes = 0;
                  $pagosenc->monto_total = 0;
                  $pagosenc->estado_generado = "u";
                  $pagosenc->estado = "N";
                  $pagosenc->fecha_pago = "Null";
                  $pagosenc->fecha_relacion = $fecha_relacion;
                  $pagosenc->fecha_corte_inicio = $fechacorteini;
                  $pagosenc->fecha_corte_final = $fechacortefin;
                  $pagosenc->otrosconceptos1 = 0;
                  $pagosenc->otrosconceptos2 = 0;
                  $pagosenc->status_atraso = "A";
                  $pagosenc->otrosconceptos3 = 0;

                  // if($fecha_relacion >= "2024-11-30"){
                  $pagosenc->costo_transaccion = 16;
                  // }else{
                  //   $pagosenc->costo_transaccion=0;
                  // }

                  $pagosenc->created_at = $fecha;
                  $pagosenc->created_by = auth()->user()->name;
                  if ($pagosenc->save()) {
                    $ultimopagoenc = $this->obtenerultimopagoenc($vardistribuidor);
                    foreach ($ultimopagoenc as $ul) {
                      $ultimop = $ul->id;
                    }
                  }
                }
                //si aplica el pago bien o lo actualiza bien creamos la nueva linea   
                $pIncompleto = 1;
                echo "<br>  CON 1";
                $actualizaPorce = DB::update("update tblpagos_enc set flag2porciento = 1 where id_distribuidor = ? and fecha_relacion <= ?;", [$vardistribuidor, $fecha_relacion]);
              }
            }

            //AJUSTE DE ESTADOO A PRESTAMOS PAGADOS
            $estadosxplazos = $this->obtenerestadosclientes($vardistribuidor, $fecha_relacion);
            if (!$estadosxplazos->isEmpty()) {
              foreach ($estadosxplazos as $item) {
                $UpdAt = DB::select('update tblprestamos_valesenc set status = "P" WHERE id = ?;', [$item->id_pres]);
              }
            }

            // CERRAR FUNCION SI YA NO TIENES SALDO PARA APLICAR
            $montopagado = intval($montoreal + $montoexcedente) - intval($saldo_pagar);
            echo "<br> |||||| MONTO PAGADO = " . $montopagado . " ||||| <br>";
            if ($montopagado <= 0) {
              $montosinproteccion_saldo = 0;
              $montoreal = 0;
              $montopagado = 0;

              if ($montopagado == 0) {
                break;
              }
            } else {
            }
          }
        } else {
          if ($montopagado > 0) {
            // REVISAR SI CUENTAS CON SALDO EXCEDENTE
            $saldoexcedente = $this->saldoexdistribuidor($vardistribuidor);
            if ($saldoexcedente->isEmpty()) {
              echo "<br> No hay excedente | ";
              $salex = 0;
            } else {
              foreach ($saldoexcedente as $s) {
                echo "Si hay excedente | ";
                // SUMAMOS EXDENTE E INCATIVAMOS PARA USARLO
                $salex = $s->monto;
                if ($salex > 0) {
                  $actualizastatus = DB::update("update tblexcedentedistribuidor set estado = 'I' where id = ?", [$s->id]);
                }
              }
            }

            $insertaexcedente = new excedentes();
            $insertaexcedente->id_distribuidor = $vardistribuidor;
            $insertaexcedente->fecha_pago = $varfecha_pago;
            $insertaexcedente->monto = $montopagado + $salex;
            $insertaexcedente->estado = "A";
            $insertaexcedente->created_at = $fecha;
            $insertaexcedente->save();

            $tipomov = "INGRESO";
            $concepto = "EXCEDENTE APLICADO CON FECHA DEL  " . $varfecha_pago;
            $descripcion = "EXCENDENTE APLICADO DESPUÉS DE HABER COMPLETADO PAGO, DISTRIBUIDOR #" . $vardistribuidor . ", PAGO REALIZADO EL " . $varfecha_pago;
            $afectarhistorialcuentas = $this->afectarhistorialcuentaspago($tipo, $cuenta, $montopagado + $salex, 0, $varfecha_pago, $vardistribuidor, 0, $tipomov, $concepto, $descripcion);
            $pExcedenteextra = 1;
          }
        }
      }

      //ALERTA
      if ($pCompleto >= 1) {
        return redirect()->route('pagosreferenciados')->with("successPagoEfectivo", "¡Se guardaron los cambios correctamente!");
      } elseif ($pExcedente >= 1) {
        return redirect()->route('pagosreferenciados')->with("successPagoExcedente", "¡Se guardaron los cambios correctamente!");
      } elseif ($pIncompleto >= 1) {
        return redirect()->route('pagosreferenciados')->with("pagoIncompleto", "¡Se guardaron los cambios correctamente!");
      } elseif ($pConciliado >= 1) {
        return redirect()->route('pagosreferenciados')->with("envioConcilia", "¡Se guardaron los cambios correctamente!");
      } elseif ($pExcedenteextra >= 1) {
        return redirect()->route('pagosreferenciados')->with("successExcedente", "¡Se guardaron los cambios correctamente!");
      } else {
        return redirect()->route('pagosreferenciados')->with("warningnPago", "¡No se guardaron los cambios correctamente!");
      }
    }
  }

  public function aplicapagoefectivo(Request $request)
  {
    try {
      $tipo = $request->get('tipo');
      if ($tipo == "CAJA") {
        $cuenta = $request->get('caja');
      } else {
        $cuenta = $request->get('cuenta');
      }
      $date = Carbon::now();
      $fecha = $date->format('Y-m-d');
      $varfecha_pago = $request->get('fecha_pago');
      $fecha_pago = Carbon::createFromFormat('Y-m-d', $varfecha_pago);
      $diaactual = $fecha_pago->format('d');
      $vardistribuidor = $request->get('iddistribuidor');
      $monto = $request->get('monto');
      $user = auth()->user()->name;

      //Porcentajes de datos iniciales
      $porcen_comision = 0;
      $porcen_cashBack = 0;

      $datosini = $this->obtenerdatosiniciales();
      foreach ($datosini as $key) {
        if ($key->concepto == "COMISION") {
          $porcen_comision = $key->cantidad;
        }

        if ($key->concepto == "CAPITAL_REGRESADO") {
          $porcen_cashBack = $key->cantidad;
        }
      }

      //traemos la informacion de las relaciones
      $obtenerlineaspagoenc = $this->obtenerlienaprestamo($vardistribuidor);
      $comisiones = $this->obtenercomisionxprestamo($diaactual);

      if ($cuenta != 0) {
        if (!$obtenerlineaspagoenc->isEmpty()) {
          foreach ($obtenerlineaspagoenc as $linea) {
            $obtener_pagosIncompletos = $this->obtenerAtrasos($vardistribuidor);
            $obtenerSaldoAcumulado = $this->obtenerSaldoAcumulado($vardistribuidor);
            $saldo_pagar = $linea->saldo_pagar;
            $estado = $linea->estado;
            $idpagoenc = $linea->id;
            $fecha_relacion = $linea->fecha_relacion;
            $costo_transaccion = 0;

            $otrosconceptos1 = $linea->otrosconceptos1;
            $otrosconceptos2 = $linea->otrosconceptos2;
            $monto_t = 0;
            $monto_total = $linea->monto_total;

            //PAGO DE TRANSACCION
            if ($fecha_relacion > "2024-10-31") {
              $costo_transaccion = $linea->costo_transaccion;
              $monto = $monto - $costo_transaccion;
            } else {
              $costo_transaccion = 0;
            }

            $consulta = DB::select('update tblpagos_enc set costo_transaccion = 0  where id = ?;', [$idpagoenc]);
            $saldosc = 0;
            $varulticlipenc = 0;
            $saldoatraso = 0;
            $saldoatrasoclientes = 0;
            $incompleto = 0;
            $pagos_incpletos = 0;
            $excedente = 0;
            $acumulado = 0;
            $comisioncalcul = 0;
            $cashBack = 0;
            $montoreal = 0;
            $incompletocli_pago = 0;
            $incompletocli = 0;
            $funcion = 0;
            $totalcliente = 0;
            $status_atraso = "I";
            $status_excedente = "I";
            $clientesxdis = $this->obtenerdetallepagoClientes($vardistribuidor, $fecha_relacion);
            $totalcliente = count($clientesxdis);

            if ($monto > 0) {
              if ($otrosconceptos2 > 0) {
                // return "entro aqui";
                $consulta = DB::select('update tblpagos_enc set estado = "P", status_atraso = "I"  where id = ?;', [$idpagoenc]);
                $ultimop = "";
                $saldopagado = $saldo_pagar - $monto_total;
                $statusa = "A";
                $statusprincipal = "N";
                $saldonuevoapagar = $saldopagado - $monto;
                $abonado = $monto_total + $monto;
                $totalabonados = 0;
                $saldoapagar = $otrosconceptos2;
                //pago completo
                if ($otrosconceptos2 == $monto) {

                  $saldonuevoapagar = 0;
                  $statusa = "I";
                  $statusprincipal = "P";
                }
                //incompletos
                else {
                  $statusa = "A";
                  $saldonuevoapagar = $otrosconceptos2 - $monto;
                  $statusprincipal = "N";
                }

                $totalabonado = $this->obtenerabonadoxdis($vardistribuidor, $fecha_relacion);
                foreach ($totalabonado as $tab) {
                  $totalabonados = $tab->saldopagado + $monto;
                }

                $clientesconpago = DB::select("select pres_det.id, tblclientes_vales.iddistribuidor, pres_det.pago_total,tblprestamos_valesenc.idcliente,pres_det.plazos  from tblprestamos_valesdet  pres_det
                      inner join tblprestamos_valesenc on pres_det.idprestamo_vales = tblprestamos_valesenc.id  
                      inner join tblclientes_vales on tblprestamos_valesenc.idcliente = tblclientes_vales.id   
                      WHERE tblclientes_vales.iddistribuidor = ? and  pres_det.fecha_pago = ?;", [$vardistribuidor, $fecha_relacion]);

                //afectamos la columna saldo
                foreach ($clientesconpago as $pagocli) {

                  if ($totalabonados >= $pagocli->pago_total) {

                    $saldorestante = 0;
                    $statuspresdet = "P";
                    $totalabonados = $totalabonados - $pagocli->pago_total;

                  } else {
                    $statuspresdet = "N";
                    $saldorestante = $pagocli->pago_total - $totalabonados;
                  }
                }

                $pagosenc = new pagosenc();
                $pagosenc->id_distribuidor = $vardistribuidor;
                $pagosenc->saldo_pagar = $saldoapagar;
                $pagosenc->comision = 0;
                $pagosenc->interes = 0;
                $pagosenc->monto_total = $monto;
                $pagosenc->estado_generado = "u";
                $pagosenc->estado = "N";
                $pagosenc->fecha_pago = "Null";
                $pagosenc->fecha_relacion = $fecha_relacion;
                $pagosenc->otrosconceptos1 = $monto;
                $pagosenc->otrosconceptos2 = $saldonuevoapagar;
                $pagosenc->status_atraso = $statusa;
                $pagosenc->otrosconceptos3 = 0;
                $pagosenc->created_at = $fecha;
                $pagosenc->created_by = auth()->user()->name;
                if ($pagosenc->save()) {
                  $ultimopagoenc = $this->obtenerultimopagoenc($vardistribuidor);
                  foreach ($ultimopagoenc as $ul) {
                    $ultimop = $ul->id;
                  }
                }

                $actualizanuevalinea = DB::select('update tblpagos_enc set estado = ?, status_atraso = ?  where id = ?;', [$statusprincipal, $statusa, $ultimop]);
                $clientesxdis = $this->obtenerdetallepagoClientes($vardistribuidor, $fecha_relacion);
                $obtprocompletodisxrel = $this->obtprocompletodisxrel($vardistribuidor, $fecha_relacion);
                $obtenerconceptospagos = $this->obtenerprorrateo();
                //variables para asignales prorrateo al distribuidor
                $capital = 0;
                $interes = 0;
                $ivainteres = 0;
                $cobertura = 0;
                $otros = 0;
                $monto_total = 0;
                $correcto = 0;

                //variables para asignarles al distribuidor sus porcentajes de pago de esa quincena
                $parcialidad_relaciondis = 0;
                $porcentajecapitaldis = 0;
                $porcentajeinteresdis = 0;
                $porcentajeivainteresdis = 0;
                $porcentajecoberturadis = 0;
                $porcentajeotrodis = 0;

                //variables para asignarle al cliente sus porcentajes de pago
                $parcialidad_relacioncli = 0;
                $porcentajecapitalcli = 0;
                $porcentajeinterescli = 0;
                $porcentajeivainterescli = 0;
                $porcentajecoberturacli = 0;
                $porcentajeotroscli = 0;
                $boolinsertpagoclidet = 0;
                $statupagoenc = "";
                $bolhistorialcuent = 0;
                $bolpoliza = 0;
                $boolcliplazo = 0;
                $boolpagoclienc = 0;
                $pagodetalledis = 0;
                $saldo_atrasadocli = 0;
                $pago_totalc = 0;
                $saldoadebercli = 0;
                $statuscliente = "";
                $montoxconceptocliente = 0;

                foreach ($obtprocompletodisxrel as $ltsobtdisxrel) {
                  $capital = $ltsobtdisxrel->capital;
                  $interes = $ltsobtdisxrel->interes;
                  $ivainteres = $ltsobtdisxrel->ivainteres;
                  $cobertura = $ltsobtdisxrel->cobertura;
                  $otros = $ltsobtdisxrel->otros_redondeo;
                  $parcialidad_relaciondis = $ltsobtdisxrel->paarcialidatotal;
                }

                $montoparaporcentaje = 0;
                $monto_total = $monto;
                $statupagoenc = "N";

                $saldocompleto = $this->obtenersaldoxprorrateo($vardistribuidor, $fecha_relacion);
                foreach ($saldocompleto as $sc) {
                  $montoparaporcentaje = $sc->saldo_pagar;
                }

                $porcentajecapitaldis = $capital / $montoparaporcentaje;
                $porcentajeinteresdis = $interes / $montoparaporcentaje;
                $porcentajeivainteresdis = $ivainteres / $montoparaporcentaje;
                $porcentajecoberturadis = $cobertura / $montoparaporcentaje;
                $porcentajeotrodis = $otros / $montoparaporcentaje;
                $status_excedente = "I";
                $status_atraso = "I";
                $montoreal = $monto;
                $comisioncalcul = 0;
                $excedente = 0;

                //echo "el montoparaporcentaje es ".$montoparaporcentaje."<br>";
                //echo "el %capital es ".$porcentajecapitaldis."<br>";
                //echo "el %interes es ".$porcentajeinteresdis."<br>";
                //echo "el %ivainteres es ".$porcentajeivainteresdis."<br>";
                //echo "el %cobertura es ".$porcentajecoberturadis."<br>";
                //echo "el %otros es ".$porcentajeotrodis."<br>";

                if ($tipo == "CAJA") {
                  $consulta = DB::select(
                    'update tblpagos_enc set estado = ?, fecha_pago = ?,monto_total = ?,status_excedente = ?,status_atraso = ?, otrosconceptos1 = ?, comision = ?, otrosconceptos2 = ? ,otrosconceptos3 = ? ,caja = ?, tipo_cuenta = ?,updated_at = ?, updated_by = ? where id = ?;',
                    [$statusprincipal, $varfecha_pago, $monto_total, $status_excedente, $status_atraso, $monto, $comisioncalcul, $saldonuevoapagar, $excedente, $cuenta, $tipo, $fecha, $user, $ultimop]
                  );
                } else
                //esta linea es en caso de que el pago se haga por una cuenta
                {
                  $consulta = DB::select(
                    'update tblpagos_enc set estado = ?, fecha_pago = ?,monto_total = ?,status_excedente = ?,status_atraso = ?, otrosconceptos1 = ?, comision = ?, otrosconceptos2 = ? ,otrosconceptos3 = ? ,cuenta = ?, tipo_cuenta = ?,updated_at = ?, updated_by = ? where id = ?;',
                    [$statusprincipal, $varfecha_pago, $monto_total, $status_excedente, $status_atraso, $monto, $comisioncalcul, $saldonuevoapagar, $excedente, $cuenta, $tipo, $fecha, $user, $ultimop]
                  );
                }

                if ($tipo == "CAJA") {
                  $varcajas = $this->obtenerCajasxId($cuenta);
                  foreach ($varcajas as $cajas) {
                    $saldo_actual = $cajas->saldo_actual;
                  }

                  $saldoCuenta = $saldo_actual + $monto;
                  $actualizacuenta = DB::select('update tblcajas set saldo_actual = ?,updated_at = ?,updated_by = ? where id = ?', [$saldoCuenta, $fecha, $user, $cuenta]);

                  $historialcuent = new historial_cajas();
                  $historialcuent->id_caja = $cuenta;
                  $historialcuent->id_empleado = auth()->user()->idempleado;
                  $historialcuent->estado = "A";
                  $historialcuent->tipo_movimiento = "PAGO";
                  $historialcuent->concepto = "PAGO DE EFECTIVO DE DISTRIBUIDOR #" . $vardistribuidor . " CON FECHA DEL " . $varfecha_pago;
                  $historialcuent->responsable = "DISTRIBUIDOR #" . $vardistribuidor;
                  $historialcuent->ingreso = $monto;
                  $historialcuent->egreso = 0;
                  $historialcuent->saldo = $saldoCuenta;
                  $historialcuent->numero_referencia = $ultimop;
                  $historialcuent->tipo_referencia = "tblpagos_enc";
                  $historialcuent->numero_poliza = 0;
                  $historialcuent->fecha = $fecha;//como es caja tiene que ser del dia del movimiento
                  $historialcuent->created_by = auth()->user()->name;
                  if ($historialcuent->save()) {
                    $bolhistorialcuent = 1;
                  }

                  $movcaja = $this->obtenerultimomovcaja();
                  foreach ($movcaja as $caj) {
                    $movId = $caj->id;
                  }
                  $poliza = historial_cajas::find($movId);
                  $poliza->numero_poliza = "CJ00" . $movId;
                  $poliza->updated_by = auth()->user()->name;
                  if ($poliza->save()) {
                    $bolpoliza = 1;
                  }
                } else {
                  $saldoactual = $this->obtenersaldocuenta($cuenta);
                  foreach ($saldoactual as $saldoactualcuenta) {
                    $saldo_actual = $saldoactualcuenta->saldo_actual;
                  }

                  $saldoCuenta = $saldo_actual + $monto;
                  $actualizacuenta = DB::select('update tblcuentas set saldo_actual = ?,updated_at = ?,updated_by = ? where id = ?', [$saldoCuenta, $fecha, $user, $cuenta]);

                  $historialcuent = new historial_cuentas();
                  $historialcuent->id_cuenta = $cuenta;
                  $historialcuent->id_empleado = auth()->user()->idempleado;
                  $historialcuent->estado = "A";
                  $historialcuent->tipo_movimiento = "PAGO";
                  $historialcuent->concepto = "PAGO DE EFECTIVO DE DISTRIBUIDOR #" . $vardistribuidor . "CON FECHA DEL " . $varfecha_pago;
                  $historialcuent->responsable = "DISTRIBUIDOR #" . $vardistribuidor;
                  $historialcuent->ingreso = $monto;
                  $historialcuent->egreso = 0;
                  $historialcuent->saldo = $saldoCuenta;
                  $historialcuent->numero_referencia = $ultimop;
                  $historialcuent->tipo_referencia = "tblpagos_enc";
                  $historialcuent->numero_poliza = 0;
                  $historialcuent->fecha = $fecha; //fecha actual por movimiento actual
                  $historialcuent->created_by = auth()->user()->name;
                  if ($historialcuent->save()) {
                    $bolhistorialcuent = 1;
                  }

                  $movcuenta = $this->obtenerultimomovcuenta();
                  foreach ($movcuenta as $cue) {
                    $movId = $cue->id;
                  }
                  $poliza = historial_cuentas::find($movId);
                  $poliza->numero_poliza = "CU00" . $movId;
                  $poliza->updated_by = auth()->user()->name;
                  if ($poliza->save()) {
                    $bolpoliza = 1;
                  }
                }

                //Obtenemos los conceptos de los pagos los cuales son capital,interes,iva interes,cobetura y otros
                foreach ($obtenerconceptospagos as $conceptoporciento) {
                  // si el pago sera completo 

                  if ($monto_total >= $saldo_pagar) { //pago completo
                    if ($conceptoporciento->id == 1)//pago capital
                    {
                      $pagoxconcepto = $capital;
                    } elseif ($conceptoporciento->id == 2)//pago a interes
                    {
                      $pagoxconcepto = $interes;
                    } elseif ($conceptoporciento->id == 3)//pago a iva de interes
                    {
                      $pagoxconcepto = $ivainteres;
                    } elseif ($conceptoporciento->id == 4)//pago a cobertura
                    {
                      $pagoxconcepto = $cobertura;
                    } elseif ($conceptoporciento->id == 5)//pago a otros redondeo etc
                    {
                      $pagoxconcepto = $otros;
                    }
                    echo $pagoxconcepto . "<br>";
                  } else {
                    if ($conceptoporciento->id == 1)//pago capital
                    {
                      $pagoxconcepto = $monto * $porcentajecapitaldis;
                      echo "el monto es " . $monto . "<br>";
                      echo "el porcentaje es " . $porcentajecapitaldis;
                      echo "el porcentaje capital es " . $porcentajecapitaldis;
                      echo "el pagoconcepto es  es " . $pagoxconcepto;
                      echo "<br>";
                      echo "<br>";
                    } elseif ($conceptoporciento->id == 2)//pago a interes
                    {
                      $pagoxconcepto = $monto * $porcentajeinteresdis;

                      echo "el porcentaje interes es " . $porcentajeinteresdis;
                      echo "el pagoconcepto es  es " . $pagoxconcepto;
                      echo "<br>";
                      echo "<br>";
                    } elseif ($conceptoporciento->id == 3)//pago a iva de interes
                    {
                      $pagoxconcepto = $monto * $porcentajeivainteresdis;

                      echo "el porcentaje iva interes es " . $porcentajeivainteresdis;
                      echo "el pagoconcepto es  es " . $pagoxconcepto;
                      echo "<br>";
                      echo "<br>";
                    } elseif ($conceptoporciento->id == 4)//pago a cobertura
                    {
                      $pagoxconcepto = $monto * $porcentajecoberturadis;

                      echo "el porcentaje cobertura es " . $porcentajecoberturadis;
                      echo "el pagoconcepto es  es " . $pagoxconcepto;
                      echo "<br>";
                      echo "<br>";
                    } elseif ($conceptoporciento->id == 5)//pago a otros redondeo etc
                    {
                      $pagoxconcepto = $monto * $porcentajeotrodis;
                      echo "el porcentaje otros es " . $porcentajeotrodis;
                      echo "el pagoconcepto es  es " . $pagoxconcepto;
                      echo "<br>";
                      echo "<br>";
                    }
                  }

                  // en teoria el pago mas vieja ya viene desde e controlador como parametro para este trait
                  $inserdetalle = new pagodet();
                  $inserdetalle->idpagoenc = $ultimop;
                  $inserdetalle->idconcepto = $conceptoporciento->id;
                  $inserdetalle->monto = $pagoxconcepto;
                  $inserdetalle->estado = "P";
                  $inserdetalle->fecha_pago = $varfecha_pago;
                  $inserdetalle->otrosconceptos1 = "";
                  $inserdetalle->otrosconceptos2 = "";
                  $inserdetalle->otrosconceptos3 = "";
                  $inserdetalle->created_at = $fecha;
                  $inserdetalle->created_by = auth()->user()->name;
                  if ($inserdetalle->save()) {
                    $pagodetalledis = $conceptoporciento->id;
                  }
                }

                foreach ($clientesxdis as $plazocli) {
                  if ($plazocli->status == "N" || $plazocli->saldo > 0) {
                    $boolinsertpagoclidet = 0;
                    //pago completo
                    if ($monto_total >= $plazocli->pago_total) {
                      $pago_totalc = $plazocli->pago_total;
                      $saldo_atrasadocli = 0;
                      $statuscliente = "P";
                      if ($plazocli->saldo > 0) {
                        $pago_totalc = $plazocli->saldo;
                      }
                    }
                    //pago incompleto cliente
                    else {
                      $saldo_atrasadocli = $plazocli->pago_total - $monto_total;
                      $pago_totalc = $monto_total;
                      $statuscliente = "P";
                    }

                    if ($monto_total <= 0) {
                      break;
                    }
                    $pagoclienc = new pagosclienc();
                    $pagoclienc->id_cliente = $plazocli->idcliente;
                    $pagoclienc->saldo_pagar = $pago_totalc;
                    $pagoclienc->comision = 0;
                    $pagoclienc->interes = 0;
                    $pagoclienc->monto_total = $pago_totalc;
                    $pagoclienc->estado_generado = "A";
                    $pagoclienc->estado = $statuscliente;
                    $pagoclienc->fecha_pago = $varfecha_pago;
                    $pagoclienc->fecha_relacion = $fecha_relacion;
                    $pagoclienc->otrosconceptos1 = $pago_totalc;
                    $pagoclienc->otrosconceptos2 = $saldo_atrasadocli;
                    $pagoclienc->otrosconceptos3 = 0;
                    $pagoclienc->created_at = $fecha;
                    $pagoclienc->created_by = auth()->user()->name;
                    if ($pagoclienc->save()) {
                      $boolpagoclienc = 1;
                    }

                    $UpdAtr1 = DB::select('update tblprestamos_valesdet  pres_det set  pres_det.status = ?  WHERE pres_det.id = ?;', [$statuspresdet, $plazocli->id]);
                    $UpdAtr2 = DB::select('update tblprestamos_valesdet  pres_det
                                      inner join tblprestamos_valesenc on pres_det.idprestamo_vales = tblprestamos_valesenc.id  
                                      inner join tblclientes_vales on tblprestamos_valesenc.idcliente = tblclientes_vales.id   set pres_det.saldo = ? 
                                      WHERE tblclientes_vales.iddistribuidor = ? and  pres_det.fecha_pago = ? and pres_det.id = ?;', [$saldorestante, $vardistribuidor, $fecha_relacion, $plazocli->id]);

                    //checamos que todo se haya hecho correcto
                    if ($pagodetalledis > 0 && $boolpagoclienc > 0) {
                      //obtenermos el id del ultimo pago del cliente
                      $varulticlipenc = 0;
                      $pagoclienc = $this->obtenerultimopagoclienc($plazocli->idcliente, $varfecha_pago);
                      foreach ($pagoclienc as $ultimopclienc) {
                        $varulticlipenc = $ultimopclienc->id;

                        //inserto pagoporconcepto en clientepagode
                        foreach ($obtenerconceptospagos as $cptp) {
                          $insertpagoclidet = new pagosclidet();
                          $insertpagoclidet->idpagoclidet = $varulticlipenc;
                          $insertpagoclidet->idconcepto = $cptp->id;

                          if ($monto_total >= $plazocli->pago_total) { //pago completo
                            if ($cptp->id == 1) {
                              $montoxconceptocliente = $plazocli->capitalxplazo;
                            } elseif ($cptp->id == 2) {
                              $montoxconceptocliente = $plazocli->interesxquincenasiniva;
                            } elseif ($cptp->id == 3) {
                              $montoxconceptocliente = $plazocli->ivainteresxplazo;
                            } elseif ($cptp->id == 4) {
                              $montoxconceptocliente = $plazocli->coberturax_plazo;
                            } elseif ($cptp->id == 5) {
                              $montoxconceptocliente = $plazocli->redondeocentavosxplazo;
                            }
                          } else {
                            //obtenemos los porcentajes por pago
                            if ($monto_total <= 0) {
                              break;
                            }
                            $porcentajecapitalcli = $plazocli->capitalxplazo / $plazocli->pago_total;
                            $porcentajeinterescli = $plazocli->interesxquincenasiniva / $plazocli->pago_total;
                            $porcentajeivainterescli = $plazocli->ivainteresxplazo / $plazocli->pago_total;
                            $porcentajecoberturacli = $plazocli->coberturax_plazo / $plazocli->pago_total;
                            if ($plazocli->redondeocentavosxplazo == 0) {
                              $porcentajeotroscli = 0;
                            } else {
                              $porcentajeotroscli = $plazocli->redondeocentavosxplazo / $plazocli->pago_total;
                            }

                            if ($cptp->id == 1) {
                              $montoxconceptocliente = $monto_total * $porcentajecapitalcli;
                              // echo "EL MONTO ES ".$montoxconceptocliente."<br>";
                            } elseif ($cptp->id == 2) {
                              $montoxconceptocliente = $monto_total * $porcentajeinterescli;
                              // echo "EL MONTO ES ".$montoxconceptocliente."<br>";
                            } elseif ($cptp->id == 3) {
                              $montoxconceptocliente = $monto_total * $porcentajeivainterescli;
                              // echo "EL MONTO ES ".$montoxconceptocliente."<br>";
                            } elseif ($cptp->id == 4) {
                              $montoxconceptocliente = $monto_total * $porcentajecoberturacli;
                              // echo "EL MONTO ES ".$montoxconceptocliente."<br>";
                            } elseif ($cptp->id == 5) {
                              $montoxconceptocliente = $monto_total * $porcentajeotroscli;
                            }
                          }
                          $insertpagoclidet->monto = $montoxconceptocliente;
                          $insertpagoclidet->estado = "P";
                          $insertpagoclidet->fecha_pago = $varfecha_pago;
                          $insertpagoclidet->otrosconceptos1 = "";
                          $insertpagoclidet->otrosconceptos2 = "";
                          $insertpagoclidet->otrosconceptos3 = "";
                          $insertpagoclidet->created_at = $fecha;
                          $insertpagoclidet->created_by = auth()->user()->name;
                          if ($insertpagoclidet->save()) {
                            $boolinsertpagoclidet = 1;
                          }
                        }
                        if ($monto_total <= 0) {
                          break;
                        }
                        $monto_total = $monto_total - $pago_totalc;
                      }
                    } else {
                      log("Error parte 2");
                    }
                  }
                }
              } //termina foreach de conceptos
              else {
                //Checamos el saldo Atrasado
                if (!$obtener_pagosIncompletos->isEmpty()) {
                  foreach ($obtener_pagosIncompletos as $atr) {
                    $incompleto = $incompleto + $atr->atraso;
                  }
                }

                //Checamos el saldo Atrasado
                if (!$obtenerSaldoAcumulado->isEmpty()) {
                  foreach ($obtenerSaldoAcumulado as $acumu) {
                    $acumulado = $acumulado + $acumu->acumulado;
                  }
                }

                //   $varfecha_pago = "2024-11-20";
                //Toma de fechas
                $fecha_rela = Carbon::parse($linea->fecha_relacion);
                $fecha_relacion1 = Carbon::parse($varfecha_pago);
                $fecha_relacion2 = Carbon::parse($varfecha_pago);
                $startDate = $fecha_relacion1->subDay(5);
                $endDate = $fecha_relacion2->addDay(7);
                $captial_actual = 0;

                //Checamos si merece comicion depende el dia de pago y el rango de fechas
                // return $fecha_rela." | ".$startDate." | ".$fecha_rela."  | ".$endDate;
                if ($fecha_rela >= $startDate && $fecha_rela <= $endDate) {
                  echo "entro a fechas";
                  foreach ($comisiones as $comision) {
                    if ($comision->dia_depago == $diaactual) {
                      echo "entro al dia de hoy";
                      $porcientocomision = $comision->comision;

                      //COMISION SACADA DEL CAPITAL
                      $obtprocompletodisxrel = $this->obtprocompletodisxrel($vardistribuidor, $fecha_relacion);
                      foreach ($obtprocompletodisxrel as $ltsobtdisxrel) {
                        $captial_actual = ($ltsobtdisxrel->capital * $porcen_comision) + $ltsobtdisxrel->interes;
                      }

                      //CALCULO DE COMSION MAS MONTO RECIBIDO
                      $comisioncalcul = floor(($captial_actual * $porcientocomision));

                      // $comisioncalcul = floor($saldo_pagar*$porcientocomision);
                      $montoreal = $monto + $comisioncalcul;

                      if ($montoreal == $saldo_pagar) {
                      } else {
                        $montoreal = $monto;
                        $comisioncalcul = 0;
                      }
                    } else {
                      $montoreal = $monto;
                    }
                  }

                  //CHECAR BIEN SI MERECE COMISION
                  if ($monto > 0 and $montoreal >= $saldo_pagar || $acumulado > 0) {
                    $merece_comision = "si";
                  } else {
                    $merece_comision = "no";
                  }
                } else {
                  $montoreal = $monto;
                  $merece_comision = "no";
                }

                if ($montoreal >= $saldo_pagar) {
                  //si existe algun acumilado se pasa todo al pago actual para sumar cantidades
                  if ($acumulado > 0) {
                    $UpdAtr = DB::select('update tblpagos_enc set status_excedente = "I" WHERE id_distribuidor = ? and estado = "P";', [$vardistribuidor]);
                  }

                  // aplicar pagos incompletos
                  if ($saldo_pagar != $montoreal) {
                    $consulta = DB::select('update tblpagos_enc set estado = "P"  where id = ?;', [$idpagoenc]);
                    if ($montoreal < $saldo_pagar) {
                      //genera atraso de la relacion a pagar
                      $cashBack = 0;
                      $excedente = 0;
                      $pago_deincompleto = 0;
                      $saldoatraso = $saldo_pagar - $montoreal;
                      $saldoatrasoclientes = $saldoatraso / $totalcliente;
                      $status_atraso = "A";
                    } else {
                      //$cashBack = floor($saldo_pagar * .70);redondeamos el 30% de pago devuelto hacía abajo
                      //no tenemos atrasos en este pago así que guardamos esa informacion 
                      $saldoatraso = 0;
                      $saldoatrasoclientes = 0;
                      $UpdAtr = DB::select('update tblpagos_enc set otrosconceptos2 = "0" WHERE id = ?;', [$idpagoenc]);
                      foreach ($clientesxdis as $clientes) {
                        $UpdAtr = DB::select('update tblprestamos_valesdet set saldo = "0" WHERE id = ?;', [$clientes->id]);
                      }

                      //si hay atrasos por pagar vermos si podemos pagar con lo que resta
                      $pago_deincompleto = $montoreal - $saldo_pagar;

                      //pago de atraso y saldo acomulado
                      //compramos si el atraso registrado es igual al saldo extra para pago de atraso 
                      if ($pago_deincompleto == $incompleto) {
                        if ($incompleto > 0) {
                          foreach ($obtener_pagosIncompletos as $atr) {
                            $UpdAtr = DB::select('update tblpagos_enc set  monto_total = ?, status_atraso = "I" WHERE id = ?;', [$saldo_pagar, $atr->id]);
                          }
                          $UpdAtr = DB::select('update tblprestamos_valesdet  pres_det
                                      inner join tblprestamos_valesenc on pres_det.idprestamo_vales = tblprestamos_valesenc.id  
                                      inner join tblclientes_vales on tblprestamos_valesenc.idcliente = tblclientes_vales.id   set pres_det.saldo = 0 
                                      WHERE tblclientes_vales.iddistribuidor = ? and  pres_det.fecha_pago = ?;', [$vardistribuidor, $atr->fecha_relacion]);
                        }

                        $excedente = $pago_deincompleto;

                        if ($excedente > 0) {
                          $status_excedente = "I";
                        }
                      } else {
                        if ($incompleto > 0) {
                          foreach ($obtener_pagosIncompletos as $atr) {
                            if ($pago_deincompleto >= $atr->atraso) {
                              $UpdAtr = DB::select('update tblpagos_enc set  monto_total = ?, status_atraso = "I", monto_total = ? WHERE id = ?;', [$saldo_pagar, $atr->saldo_pagar, $atr->id]);
                              $UpdAtr = DB::select('update tblprestamos_valesdet  pres_det
                                      inner join tblprestamos_valesenc on pres_det.idprestamo_vales = tblprestamos_valesenc.id  
                                      inner join tblclientes_vales on tblprestamos_valesenc.idcliente = tblclientes_vales.id set pres_det.saldo = 0 
                                      WHERE tblclientes_vales.iddistribuidor = ? and  pres_det.fecha_pago = ?;', [$atr->iddis, $atr->fecha_relacion]);
                              $pago_deincompleto = $pago_deincompleto - $atr->atraso;
                            } else {
                              $pay = $pago_deincompleto - $atr->atraso;
                              if ($pay < 0) {
                                $pay = $atr->atraso - $pago_deincompleto;
                                $pago_deincompleto = 0;
                              }

                              $monto_t = $atr->monto_total + $pago_deincompleto;
                              $UpdAtr = DB::select('update tblpagos_enc set monto_total = ?, otrosconceptos2 = ?,status_atraso = "A" WHERE id = ?;', [$monto_t, $pay, $atr->id]);

                              $incompletocli = DB::select('select count(tblclientes_vales.id) from tblprestamos_valesdet  pres_det
                                      inner join tblprestamos_valesenc on pres_det.idprestamo_vales = tblprestamos_valesenc.id  
                                      inner join tblclientes_vales on tblprestamos_valesenc.idcliente = tblclientes_vales.id   
                                      WHERE tblclientes_vales.iddistribuidor = ? and  pres_det.fecha_pago = ?;', [$vardistribuidor, $atr->fecha_relacion]);
                              $incompletocli_pago = $pay / $incompletocli;

                              $UpdAtr = DB::select('update tblprestamos_valesdet  pres_det
                                      inner join tblprestamos_valesenc on pres_det.idprestamo_vales = tblprestamos_valesenc.id  
                                      inner join tblclientes_vales on tblprestamos_valesenc.idcliente = tblclientes_vales.id   set pres_det.saldo = ? 
                                      WHERE tblclientes_vales.iddistribuidor = ? and  pres_det.fecha_pago = ?;', [$incompletocli_pago, $vardistribuidor, $atr->fecha_relacion]);

                              if ($pago_deincompleto == 0) {
                                break;
                              }
                            }
                          }
                        }

                        $excedente = $pago_deincompleto;
                        if ($excedente > 0) {
                          $status_excedente = "A";
                        }
                      }
                    }
                  } else {
                    //$cashBack = floor($saldo_pagar * .70); redondeamos el 30% de pago devuelto hacía abajo
                    $saldoatraso = 0;
                    $saldoatrasoclientes = 0;
                  }

                  //PROCESO DE INSERT
                  $pagoconcilidado = 0;
                  $funcion = $this->insertarPagos(
                    $vardistribuidor,
                    $idpagoenc,
                    $monto,
                    $saldo_pagar,
                    $cashBack,
                    $varfecha_pago,
                    $fecha_relacion,
                    $montoreal,
                    $comisioncalcul,
                    $saldoatraso,
                    $saldoatrasoclientes,
                    $status_atraso,
                    $status_excedente,
                    $excedente,
                    $cuenta,
                    $tipo,
                    $totalcliente,
                    $fecha,
                    $user,
                    $pagoconcilidado,
                    $costo_transaccion
                  );
                  $monto = 0;
                } else {
                  if ($diaactual >= 23 && $diaactual <= 28 || $diaactual >= 7 && $diaactual <= 14) {
                    return back()->with("warningNoaplica", "no guardado correctamente");
                  }
                  if ($diaactual >= 21 && $diaactual <= 28 || $diaactual >= 6 && $diaactual <= 13 and $montoreal < $saldo_pagar) {
                    $consulta = DB::select('update tblpagos_enc set estado = "P", status_atraso = "I"  where id = ?;', [$idpagoenc]);
                    $ultimop = "";
                    $saldopagado = $saldo_pagar - $monto_total;
                    $saldonuevoapagar = $saldopagado - $monto;
                    $abonado = $monto_total + $monto;

                    $pagosenc = new pagosenc();
                    $pagosenc->id_distribuidor = $vardistribuidor;
                    $pagosenc->saldo_pagar = $saldonuevoapagar;
                    $pagosenc->comision = 0;
                    $pagosenc->interes = 0;
                    $pagosenc->monto_total = $saldonuevoapagar;
                    $pagosenc->estado_generado = "u";
                    $pagosenc->estado = "N";
                    $pagosenc->fecha_pago = "Null";
                    $pagosenc->fecha_relacion = $fecha_relacion;
                    $pagosenc->otrosconceptos1 = $monto;
                    $pagosenc->otrosconceptos2 = $saldonuevoapagar;
                    $pagosenc->status_atraso = "A";
                    $pagosenc->otrosconceptos3 = 0;
                    $pagosenc->created_at = $fecha;
                    $pagosenc->created_by = auth()->user()->name;
                    if ($pagosenc->save()) {
                      $ultimopagoenc = $this->obtenerultimopagoenc($vardistribuidor);
                      foreach ($ultimopagoenc as $ul) {
                        $ultimop = $ul->id;
                      }
                    }

                    $actualizanuevalinea = DB::select('update tblpagos_enc set estado = "N", status_atraso = "A"  where id = ?;', [$ultimop]);
                    $clientesxdis = $this->obtenerdetallepagoClientes($vardistribuidor, $fecha_relacion);
                    $obtprocompletodisxrel = $this->obtprocompletodisxrel($vardistribuidor, $fecha_relacion);

                    $obtenerconceptospagos = $this->obtenerprorrateo();
                    //variables para asignales prorrateo al distribuidor
                    $capital = 0;
                    $interes = 0;
                    $ivainteres = 0;
                    $cobertura = 0;
                    $otros = 0;
                    $monto_total = 0;
                    $correcto = 0;

                    //variables para asignarles al distribuidor sus porcentajes de pago de esa quincena
                    $parcialidad_relaciondis = 0;
                    $porcentajecapitaldis = 0;
                    $porcentajeinteresdis = 0;
                    $porcentajeivainteresdis = 0;
                    $porcentajecoberturadis = 0;
                    $porcentajeotrodis = 0;

                    //variables para asignarle al cliente sus porcentajes de pago
                    $parcialidad_relacioncli = 0;
                    $porcentajecapitalcli = 0;
                    $porcentajeinterescli = 0;
                    $porcentajeivainterescli = 0;
                    $porcentajecoberturacli = 0;
                    $porcentajeotroscli = 0;
                    $boolinsertpagoclidet = 0;

                    $statupagoenc = "";
                    $bolhistorialcuent = 0;
                    $bolpoliza = 0;
                    $boolcliplazo = 0;
                    $boolpagoclienc = 0;
                    $pagodetalledis = 0;
                    $saldo_atrasadocli = 0;
                    $pago_totalc = 0;
                    $saldoadebercli = 0;
                    $statuscliente = "";
                    $montoxconceptocliente = 0;

                    foreach ($obtprocompletodisxrel as $ltsobtdisxrel) {
                      $capital = $ltsobtdisxrel->capital;
                      $interes = $ltsobtdisxrel->interes;
                      $ivainteres = $ltsobtdisxrel->ivainteres;
                      $cobertura = $ltsobtdisxrel->cobertura;
                      $otros = $ltsobtdisxrel->otros_redondeo;
                      $parcialidad_relaciondis = $ltsobtdisxrel->paarcialidatotal;
                    }

                    $montoparaporcentaje = 0;
                    $monto_total = $monto;
                    $statupagoenc = "N";

                    $saldocompleto = $this->obtenersaldoxprorrateo($vardistribuidor, $fecha_relacion);
                    foreach ($saldocompleto as $sc) {
                      $montoparaporcentaje = $sc->saldo_pagar;
                    }

                    $porcentajecapitaldis = $capital / $montoparaporcentaje;
                    $porcentajeinteresdis = $interes / $montoparaporcentaje;
                    $porcentajeivainteresdis = $ivainteres / $montoparaporcentaje;
                    $porcentajecoberturadis = $cobertura / $montoparaporcentaje;
                    $porcentajeotrodis = $otros / $montoparaporcentaje;
                    $status_excedente = 0;
                    $status_atraso = "I";
                    $montoreal = $monto;
                    $comisioncalcul = 0;
                    $excedente = 0;

                    if ($tipo == "CAJA") {
                      $consulta = DB::select(
                        'update tblpagos_enc set estado = ?, fecha_pago = ?,monto_total = ?,status_excedente = ?,status_atraso = ?, otrosconceptos1 = ?, comision = ?, otrosconceptos2 = ? ,otrosconceptos3 = ? ,caja = ?, tipo_cuenta = ?,updated_at = ?, updated_by = ? where id = ?;',
                        [$statupagoenc, $varfecha_pago, $monto_total, $status_excedente, $status_atraso, $monto, $comisioncalcul, $saldonuevoapagar, $excedente, $cuenta, $tipo, $fecha, $user, $ultimop]
                      );
                    } else
                    //esta linea es en caso de que el pago se haga por una cuenta
                    {
                      $consulta = DB::select(
                        'update tblpagos_enc set estado = ?, fecha_pago = ?,monto_total = ?,status_excedente = ?,status_atraso = ?, otrosconceptos1 = ?, comision = ?, otrosconceptos2 = ? ,otrosconceptos3 = ? ,cuenta = ?, tipo_cuenta = ?,updated_at = ?, updated_by = ? where id = ?;',
                        [$statupagoenc, $varfecha_pago, $monto_total, $status_excedente, $status_atraso, $monto, $comisioncalcul, $saldonuevoapagar, $excedente, $cuenta, $tipo, $fecha, $user, $ultimop]
                      );
                    }

                    if ($tipo == "CAJA") {
                      $varcajas = $this->obtenerCajasxId($cuenta);
                      foreach ($varcajas as $cajas) {
                        $saldo_actual = $cajas->saldo_actual;
                      }
                      $saldoCuenta = $saldo_actual + $monto + $costo_transaccion;
                      $actualizacuenta = DB::select('update tblcajas set saldo_actual = ?,updated_at = ?,updated_by = ? where id = ?', [$saldoCuenta, $fecha, $user, $cuenta]);

                      $historialcuent = new historial_cajas();
                      $historialcuent->id_caja = $cuenta;
                      $historialcuent->id_empleado = auth()->user()->idempleado;
                      $historialcuent->estado = "A";
                      $historialcuent->tipo_movimiento = "PAGO";
                      $historialcuent->concepto = "PAGO DE EFECTIVO DE DISTRIBUIDOR #" . $vardistribuidor . " CON FECHA DEL " . $varfecha_pago;
                      $historialcuent->responsable = "DISTRIBUIDOR #" . $vardistribuidor;
                      $historialcuent->ingreso = $monto + $costo_transaccion;
                      $historialcuent->egreso = 0;
                      $historialcuent->saldo = $saldoCuenta;
                      $historialcuent->numero_referencia = $ultimop;
                      $historialcuent->tipo_referencia = "tblpagos_enc";
                      $historialcuent->numero_poliza = 0;
                      $historialcuent->fecha = $fecha;//como es caja tiene que ser del dia del movimiento
                      $historialcuent->created_by = auth()->user()->name;
                      if ($historialcuent->save()) {
                        $bolhistorialcuent = 1;
                      }

                      $movcaja = $this->obtenerultimomovcaja();
                      foreach ($movcaja as $caj) {
                        $movId = $caj->id;
                      }
                      $poliza = historial_cajas::find($movId);
                      $poliza->numero_poliza = "CJ00" . $movId;
                      $poliza->updated_by = auth()->user()->name;
                      if ($poliza->save()) {
                        $bolpoliza = 1;
                      }
                    } else {
                      $saldoactual = $this->obtenersaldocuenta($cuenta);
                      foreach ($saldoactual as $saldoactualcuenta) {
                        $saldo_actual = $saldoactualcuenta->saldo_actual;
                      }

                      $saldoCuenta = $saldo_actual + $monto + $costo_transaccion;
                      $actualizacuenta = DB::select('update tblcuentas set saldo_actual = ?,updated_at = ?,updated_by = ? where id = ?', [$saldoCuenta, $fecha, $user, $cuenta]);

                      $historialcuent = new historial_cuentas();
                      $historialcuent->id_cuenta = $cuenta;
                      $historialcuent->id_empleado = auth()->user()->idempleado;
                      $historialcuent->estado = "A";
                      $historialcuent->tipo_movimiento = "PAGO";
                      $historialcuent->concepto = "PAGO DE EFECTIVO DE DISTRIBUIDOR #" . $vardistribuidor . "CON FECHA DEL " . $varfecha_pago;
                      $historialcuent->responsable = "DISTRIBUIDOR #" . $vardistribuidor;
                      $historialcuent->ingreso = $monto + $costo_transaccion;
                      $historialcuent->egreso = 0;
                      $historialcuent->saldo = $saldoCuenta;
                      $historialcuent->numero_referencia = $ultimop;
                      $historialcuent->tipo_referencia = "tblpagos_enc";
                      $historialcuent->numero_poliza = 0;
                      $historialcuent->fecha = $fecha; //fecha actual por movimiento actual
                      $historialcuent->created_by = auth()->user()->name;
                      if ($historialcuent->save()) {
                        $bolhistorialcuent = 1;
                      }

                      $movcuenta = $this->obtenerultimomovcuenta();
                      foreach ($movcuenta as $cue) {
                        $movId = $cue->id;
                      }
                      $poliza = historial_cuentas::find($movId);
                      $poliza->numero_poliza = "CU00" . $movId;
                      $poliza->updated_by = auth()->user()->name;
                      if ($poliza->save()) {
                        $bolpoliza = 1;
                      }
                    }

                    //Obtenemos los conceptos de los pagos los cuales son capital,interes,iva interes,cobetura y otros
                    foreach ($obtenerconceptospagos as $conceptoporciento) {
                      // si el pago sera completo 
                      if ($monto_total >= $saldo_pagar) { //pago completo
                        if ($conceptoporciento->id == 1)//pago capital
                        {
                          $pagoxconcepto = $capital;
                        } elseif ($conceptoporciento->id == 2)//pago a interes
                        {
                          $pagoxconcepto = $interes;
                        } elseif ($conceptoporciento->id == 3)//pago a iva de interes
                        {
                          $pagoxconcepto = $ivainteres;
                        } elseif ($conceptoporciento->id == 4)//pago a cobertura
                        {
                          $pagoxconcepto = $cobertura;
                        } elseif ($conceptoporciento->id == 5)//pago a otros redondeo etc
                        {
                          $pagoxconcepto = $otros;
                        }
                      } else {
                        if ($conceptoporciento->id == 1)//pago capital
                        {
                          $pagoxconcepto = $monto * $porcentajecapitaldis;
                          echo "el monto es " . $monto . "<br>";
                          echo "el porcentaje es " . $porcentajecapitaldis;
                          echo "el porcentaje capital es " . $porcentajecapitaldis;
                          echo "el pagoconcepto es  es " . $pagoxconcepto;
                          echo "<br>";
                          echo "<br>";
                        } elseif ($conceptoporciento->id == 2)//pago a interes
                        {
                          $pagoxconcepto = $monto * $porcentajeinteresdis;
                          echo "el porcentaje interes es " . $porcentajeinteresdis;
                          echo "el pagoconcepto es  es " . $pagoxconcepto;
                          echo "<br>";
                          echo "<br>";
                        } elseif ($conceptoporciento->id == 3)//pago a iva de interes
                        {
                          $pagoxconcepto = $monto * $porcentajeivainteresdis;
                          echo "el porcentaje iva interes es " . $porcentajeivainteresdis;
                          echo "el pagoconcepto es  es " . $pagoxconcepto;
                          echo "<br>";
                          echo "<br>";
                        } elseif ($conceptoporciento->id == 4)//pago a cobertura
                        {
                          $pagoxconcepto = $monto * $porcentajecoberturadis;
                          echo "el porcentaje cobertura es " . $porcentajecoberturadis;
                          echo "el pagoconcepto es  es " . $pagoxconcepto;
                          echo "<br>";
                          echo "<br>";
                        } elseif ($conceptoporciento->id == 5)//pago a otros redondeo etc
                        {
                          $pagoxconcepto = $monto * $porcentajeotrodis;
                          echo "el porcentaje otros es " . $porcentajeotrodis;
                          echo "el pagoconcepto es  es " . $pagoxconcepto;
                          echo "<br>";
                          echo "<br>";
                        }
                      }

                      // en teoria el pago mas vieja ya viene desde e controlador como parametro para este trait
                      $inserdetalle = new pagodet();
                      $inserdetalle->idpagoenc = $ultimop;
                      $inserdetalle->idconcepto = $conceptoporciento->id;
                      $inserdetalle->monto = $pagoxconcepto;
                      $inserdetalle->estado = "P";
                      $inserdetalle->fecha_pago = $varfecha_pago;
                      $inserdetalle->otrosconceptos1 = "";
                      $inserdetalle->otrosconceptos2 = "";
                      $inserdetalle->otrosconceptos3 = "";
                      $inserdetalle->created_at = $fecha;
                      $inserdetalle->created_by = auth()->user()->name;
                      if ($inserdetalle->save()) {
                        $pagodetalledis = $conceptoporciento->id;
                      }
                    }

                    foreach ($clientesxdis as $plazocli) {
                      $boolinsertpagoclidet = 0;
                      //pago completo
                      if ($monto_total >= $plazocli->pago_total) {
                        $pago_totalc = $plazocli->pago_total;
                        $saldo_atrasadocli = 0;
                        $statuscliente = "P";
                      }
                      //pago incompleto cliente
                      else {
                        $saldo_atrasadocli = $plazocli->pago_total - $monto_total;
                        $pago_totalc = $monto_total;
                        $statuscliente = "P";
                      }
                      echo $pago_totalc;
                      if ($monto_total <= 0) {
                        break;
                      }
                      $pagoclienc = new pagosclienc();
                      $pagoclienc->id_cliente = $plazocli->idcliente;
                      $pagoclienc->saldo_pagar = $pago_totalc;
                      $pagoclienc->comision = 0;
                      $pagoclienc->interes = 0;
                      $pagoclienc->monto_total = $pago_totalc;
                      $pagoclienc->estado_generado = "A";
                      $pagoclienc->estado = $statuscliente;
                      $pagoclienc->fecha_pago = $varfecha_pago;
                      $pagoclienc->fecha_relacion = $fecha_relacion;
                      $pagoclienc->otrosconceptos1 = $pago_totalc;
                      $pagoclienc->otrosconceptos2 = $saldo_atrasadocli;
                      $pagoclienc->otrosconceptos3 = 0;
                      $pagoclienc->created_at = $fecha;
                      $pagoclienc->created_by = auth()->user()->name;
                      if ($pagoclienc->save()) {
                        $boolpagoclienc = 1;
                      }

                      //checamos que todo se haya hecho correcto
                      if ($pagodetalledis > 0 && $boolpagoclienc > 0) {
                        //obtenermos el id del ultimo pago del cliente
                        $varulticlipenc = 0;
                        $pagoclienc = $this->obtenerultimopagoclienc($plazocli->idcliente, $varfecha_pago);
                        foreach ($pagoclienc as $ultimopclienc) {
                          $varulticlipenc = $ultimopclienc->id;

                          //inserto pagoporconcepto en clientepagode
                          foreach ($obtenerconceptospagos as $cptp) {
                            $insertpagoclidet = new pagosclidet();
                            $insertpagoclidet->idpagoclidet = $varulticlipenc;
                            $insertpagoclidet->idconcepto = $cptp->id;

                            if ($monto_total >= $plazocli->pago_total) { //pago completo
                              if ($cptp->id == 1) {
                                $montoxconceptocliente = $plazocli->capitalxplazo;
                              } elseif ($cptp->id == 2) {
                                $montoxconceptocliente = $plazocli->interesxquincenasiniva;
                              } elseif ($cptp->id == 3) {
                                $montoxconceptocliente = $plazocli->ivainteresxplazo;
                              } elseif ($cptp->id == 4) {
                                $montoxconceptocliente = $plazocli->coberturax_plazo;
                              } elseif ($cptp->id == 5) {
                                $montoxconceptocliente = $plazocli->redondeocentavosxplazo;
                              }
                            } else {
                              //obtenemos los porcentajes por pago
                              if ($monto_total <= 0) {
                                break;
                              }
                              $porcentajecapitalcli = $plazocli->capitalxplazo / $plazocli->pago_total;
                              $porcentajeinterescli = $plazocli->interesxquincenasiniva / $plazocli->pago_total;
                              $porcentajeivainterescli = $plazocli->ivainteresxplazo / $plazocli->pago_total;
                              $porcentajecoberturacli = $plazocli->coberturax_plazo / $plazocli->pago_total;
                              if ($plazocli->redondeocentavosxplazo == 0) {
                                $porcentajeotroscli = 0;
                              } else {
                                $porcentajeotroscli = $plazocli->redondeocentavosxplazo / $plazocli->pago_total;
                              }

                              if ($cptp->id == 1) {
                                $montoxconceptocliente = $monto_total * $porcentajecapitalcli;
                                // echo "EL MONTO ES ".$montoxconceptocliente."<br>";
                              } elseif ($cptp->id == 2) {
                                $montoxconceptocliente = $monto_total * $porcentajeinterescli;
                                // echo "EL MONTO ES ".$montoxconceptocliente."<br>";
                              } elseif ($cptp->id == 3) {
                                $montoxconceptocliente = $monto_total * $porcentajeivainterescli;
                                // echo "EL MONTO ES ".$montoxconceptocliente."<br>";
                              } elseif ($cptp->id == 4) {
                                $montoxconceptocliente = $monto_total * $porcentajecoberturacli;
                                // echo "EL MONTO ES ".$montoxconceptocliente."<br>";
                              } elseif ($cptp->id == 5) {
                                $montoxconceptocliente = $monto_total * $porcentajeotroscli;
                              }
                            }

                            $insertpagoclidet->monto = $montoxconceptocliente;
                            $insertpagoclidet->estado = "P";
                            $insertpagoclidet->fecha_pago = $varfecha_pago;
                            $insertpagoclidet->otrosconceptos1 = "";
                            $insertpagoclidet->otrosconceptos2 = "";
                            $insertpagoclidet->otrosconceptos3 = "";
                            $insertpagoclidet->created_at = $fecha;
                            $insertpagoclidet->created_by = auth()->user()->name;
                            if ($insertpagoclidet->save()) {
                              $boolinsertpagoclidet = 1;
                            }
                          }
                          if ($monto_total <= 0) {
                            break;
                          }
                          $monto_total = $monto_total - $plazocli->pago_total;
                        }
                      } else {
                        log("Error parte 2");
                      }
                    }

                    //INSERTAMOS PRIMERO EN EL ENCABEZADO SI ES INCOMPLETO
                    $montoreal = $monto;
                    echo "el saldo a pagar es de" . $saldo_pagar . "<br>";
                    echo "el monto total es de" . $monto_total . "<br>";
                    echo "el monto es de" . $monto . "<br>";
                    echo "el monto de ese pago es" . $montoreal . "<br>";
                    echo "el saldo a deber es " . $saldopagado . "<br>";
                    echo "el saldo abonado es " . $abonado . "<br>";
                    echo "el saldo a deber nuevo es de" . $saldonuevoapagar . "<br>";

                    //obtenemos los clientes a afectar el saldo de prestamos det
                    $clientesconpago = DB::select("select pres_det.id, tblclientes_vales.iddistribuidor, pres_det.pago_total,tblprestamos_valesenc.idcliente,pres_det.plazos  from tblprestamos_valesdet  pres_det
                              inner join tblprestamos_valesenc on pres_det.idprestamo_vales = tblprestamos_valesenc.id  
                              inner join tblclientes_vales on tblprestamos_valesenc.idcliente = tblclientes_vales.id   
                              WHERE tblclientes_vales.iddistribuidor = ? and  pres_det.fecha_pago = ?;", [$vardistribuidor, $fecha_relacion]);

                    //afectamos la columna saldo
                    foreach ($clientesconpago as $pagocli) {
                      if ($abonado >= $pagocli->pago_total) {
                        $saldorestante = 0;
                        $abonado = $abonado - $pagocli->pago_total;
                      } else {
                        $saldorestante = $pagocli->pago_total - $abonado;
                      }

                      $UpdAtr = DB::select('update tblprestamos_valesdet  pres_det
                                inner join tblprestamos_valesenc on pres_det.idprestamo_vales = tblprestamos_valesenc.id  
                                inner join tblclientes_vales on tblprestamos_valesenc.idcliente = tblclientes_vales.id   set pres_det.saldo = ? 
                                WHERE tblclientes_vales.iddistribuidor = ? and  pres_det.fecha_pago = ? and pres_det.id = ?;', [$saldorestante, $vardistribuidor, $fecha_relacion, $pagocli->id]);
                    }
                  } else {
                    $pagosyacontrados = $this->obtenerpagosconcetrandosporquincenaydis($vardistribuidor, $fecha_relacion, $montoreal);
                    if ($pagosyacontrados->isEmpty()) {
                      echo "entro a concilidados";
                      $insertapagoconcentrado = new pagocontrados();
                      $insertapagoconcentrado->id_distirbuidor = $vardistribuidor;
                      $insertapagoconcentrado->saldo_pagar_real = $saldo_pagar;
                      $insertapagoconcentrado->intento_pago = $monto;
                      $insertapagoconcentrado->fecha_relacion = $fecha_relacion;
                      $insertapagoconcentrado->fecha_intento_pago = $varfecha_pago;
                      $insertapagoconcentrado->status = "A";
                      $insertapagoconcentrado->idcuenta = $cuenta;
                      $insertapagoconcentrado->tipocuenta = $tipo;
                      $insertapagoconcentrado->created_at = $fecha;
                      if ($insertapagoconcentrado->save()) {
                        $pagos_incpletos = $pagos_incpletos + 1;
                        $ultimopagoenc = $this->obtenerultimopagoenc($vardistribuidor);
                        foreach ($ultimopagoenc as $ul) {
                          $ultimop = $ul->id;
                        }

                        if ($tipo == "CAJA") {
                          $varcajas = $this->obtenerCajasxId($cuenta);
                          foreach ($varcajas as $cajas) {
                            $saldo_actual = $cajas->saldo_actual;
                          }

                          $saldoCuenta = $saldo_actual + $monto + $costo_transaccion;
                          $actualizacuenta = DB::select('update tblcajas set saldo_actual = ?,updated_at = ?,updated_by = ? where id = ?', [$saldoCuenta, $fecha, $user, $cuenta]);

                          $historialcuent = new historial_cajas();
                          $historialcuent->id_caja = $cuenta;
                          $historialcuent->id_empleado = auth()->user()->idempleado;
                          $historialcuent->estado = "A";
                          $historialcuent->tipo_movimiento = "PAGO";
                          $historialcuent->concepto = "FECHA DE CAPTURA DEL INGRESO  " . $varfecha_pago;
                          $historialcuent->descripcion = "PAGO REFERENCIADO DE DISTRIBUIDOR #" . $vardistribuidor;
                          $historialcuent->responsable = "DISTRIBUIDOR #" . $vardistribuidor;
                          $historialcuent->ingreso = $monto + $costo_transaccion;
                          $historialcuent->egreso = 0;
                          $historialcuent->saldo = $saldoCuenta;
                          $historialcuent->numero_referencia = $idpagoenc;
                          $historialcuent->tipo_referencia = "tblpagos_enc";
                          $historialcuent->numero_poliza = 0;
                          $historialcuent->fecha = $fecha;//como es caja tiene que ser del dia del movimiento
                          $historialcuent->created_by = auth()->user()->name;

                          if ($historialcuent->save()) {
                            $bolhistorialcuent = 1;
                          }

                          $movcaja = $this->obtenerultimomovcaja();
                          foreach ($movcaja as $caj) {
                            $movId = $caj->id;
                          }
                          $poliza = historial_cajas::find($movId);
                          $poliza->numero_poliza = "CJ00" . $movId;
                          $poliza->updated_by = auth()->user()->name;

                          if ($poliza->save()) {
                            $bolpoliza = 1;
                          }
                        } else {
                          $saldoactual = $this->obtenersaldocuenta($cuenta);
                          foreach ($saldoactual as $saldoactualcuenta) {
                            $saldo_actual = $saldoactualcuenta->saldo_actual;
                          }

                          $saldoCuenta = $saldo_actual + $monto + $costo_transaccion;
                          $actualizacuenta = DB::select('update tblcuentas set saldo_actual = ?,updated_at = ?,updated_by = ? where id = ?', [$saldoCuenta, $fecha, $user, $cuenta]);

                          $historialcuent = new historial_cuentas();
                          $historialcuent->id_cuenta = $cuenta;
                          $historialcuent->id_empleado = auth()->user()->idempleado;
                          $historialcuent->estado = "A";
                          $historialcuent->tipo_movimiento = "PAGO";
                          $historialcuent->concepto = "FECHA DE CAPTURA DEL INGRESO  " . $varfecha_pago;
                          $historialcuent->descripcion = "PAGO REFERENCIADO DE DISTRIBUIDOR #" . $vardistribuidor;
                          $historialcuent->responsable = "DISTRIBUIDOR #" . $vardistribuidor;
                          $historialcuent->ingreso = $monto + $costo_transaccion;
                          $historialcuent->egreso = 0;
                          $historialcuent->saldo = $saldoCuenta;
                          $historialcuent->numero_referencia = $idpagoenc;
                          $historialcuent->tipo_referencia = "tblpagos_enc";
                          $historialcuent->numero_poliza = 0;
                          $historialcuent->fecha = $fecha; //fecha actual por movimiento actual
                          $historialcuent->created_by = auth()->user()->name;

                          if ($historialcuent->save()) {
                            $bolhistorialcuent = 1;
                          }

                          $movcuenta = $this->obtenerultimomovcuenta();
                          foreach ($movcuenta as $cue) {
                            $movId = $cue->id;
                          }
                          $poliza = historial_cuentas::find($movId);
                          $poliza->numero_poliza = "CU00" . $movId;
                          $poliza->updated_by = auth()->user()->name;

                          if ($poliza->save()) {
                            $bolpoliza = 1;
                          }
                        }
                        break;
                      }
                    }
                  }
                }
              }
            }
          }

          // return back()->with("successPagoEfectivo","Error");
          if ($pagos_incpletos > 0) {
            echo "se mandaron " . $pagos_incpletos . " a Conciliacion";
            //  return back()->with("envioConcilia","success");
            return redirect()->route('pagosreferenciados')->with("envioConcilia", "¡Se guardaron los cambios correctamente!");
          } else {
            // return back()->with("successPago","success");
            return redirect()->route('pagosreferenciados')->with("successPagoEfectivo", "¡Se guardaron los cambios correctamente!");
          }
        } else {
          return back()->with("warningRelacion", "Error");
        }
      } else {
        return back()->with("warningCuenta", "Error");
      }
    } catch (\Illuminate\Database\QueryException $ex) {
      return back()->with("warningBD", "no guardado correctamente");
    }
  }

  public function cancelarPagosDis(int $pagoenc, Request $request)
  {
    try {
      $date = Carbon::now();
      $fecha = $date->format('Y-m-d');
      $user = auth()->user()->name;
      $saldosc = 0;
      $excedente = 0;
      $capital_regresado = 0;
      $monto_recibido = 0;
      $varfecha_relacion = $request->get('fecha_relacion');
      $varfecha_pago = $request->get('fecha_pago');


      //VALIDA PAGOS ENC
      $pagosEnc = $this->pagosEnc($pagoenc);
      foreach ($pagosEnc as $pago) {
        $idDis = $pago->iddis;
        $comision = $pago->comision;
        $saldo_pagar = $pago->saldo_pagar;
        $otrosconceptos1 = $pago->otrosconceptos1;

        $capital_regresado = $pago->capital_regresado;
        $monto_recibido = $pago->monto_recibido;
        $excedente = $pago->otrosconceptos3;
      }

      //VALIDA CUENTA
      $tipo_cuenta = $request->get('tipo_odp');
      if ($tipo_cuenta == "CAJA") {
        $cuenta = $request->get('idcaja');
        $varcajas = $this->obtenerCajasxId($cuenta);
        foreach ($varcajas as $cajas) {
          $saldosc = $cajas->saldo_actual;
          $nomCuenta = $cajas->nombre;
        }

      } else {
        $cuenta = $request->get('idcuenta');
        $varobtenercuentas = $this->obtenercuentasPrincipales($cuenta);
        foreach ($varobtenercuentas as $varobtenercuenta) {
          $saldosc = $varobtenercuenta->saldo_actual;
          $nomCuenta = $varobtenercuenta->descripcion;
        }
      }
      $saldoCuenta = $saldosc - $monto_recibido;


      //VALIDA CAPITAL
      $solicitudDis = $this->obtenersolicitud($idDis);
      foreach ($solicitudDis as $disSol) {
        $capital = $disSol->capital;
      }
      if ($comision > 0) {
        $NuevoCapital = $capital - $capital_regresado;
        if ($NuevoCapital < 0) {
          return back()->with("warningCapitaRegresado", "No es posible cancelar por que el capital regresado ya se utilizo");
        } else {
          $NuevoCapital = $capital - $capital_regresado;
        }
      }

      // return $varfecha_relacion ." ".$pagoenc." ".$varfecha_pago." ".$idDis." ".$tipo_cuenta." ".$cuenta;

      //VALIDA CUENTA
      if ($saldoCuenta >= 0 && !is_null($tipo_cuenta) && !is_null($cuenta)) {
        foreach ($pagosEnc as $pago) {
          $insertcancelacionpago = new cancelacionespagos();
          $insertcancelacionpago->id_distribuidor = $pago->iddis;
          $insertcancelacionpago->fecha_relacion = $pago->fecha_relacion;
          $insertcancelacionpago->fecha_pago = $pago->fecha_pago;
          $insertcancelacionpago->estado = "C";
          $insertcancelacionpago->saldo_pagar = $pago->saldo_pagar;
          $insertcancelacionpago->comision = $pago->comision;
          $insertcancelacionpago->interes = $pago->interes;
          $insertcancelacionpago->monto_total = $pago->monto_total;
          $insertcancelacionpago->capital_regresado = $pago->capital_regresado;
          $insertcancelacionpago->estado_generado = $pago->estado_generado;
          $insertcancelacionpago->otrosconceptos1 = $pago->otrosconceptos1;
          $insertcancelacionpago->otrosconceptos2 = $pago->otrosconceptos2;
          $insertcancelacionpago->otrosconceptos3 = $pago->otrosconceptos3;
          $insertcancelacionpago->tipo_cuenta = $tipo_cuenta;
          if ($tipo_cuenta == "CAJA") {
            $insertcancelacionpago->caja = $cuenta;
          } else {
            $insertcancelacionpago->cuenta = $cuenta;
          }

          $insertcancelacionpago->created_by = auth()->user()->name;
          $insertcancelacionpago->monto_recibido = $pago->monto_recibido;
          $insertcancelacionpago->save();
        }

        //APLICAR CANCELACION EN PAGO_ENC Y PAGO_DET
        $obtenerpagoDet = $this->obtenerpagoDet($pagoenc, $varfecha_pago);
        foreach ($obtenerpagoDet as $pagoDet) {
          $Borrartbl1 = DB::select('delete from tblpagos_det where id = ?;', [$pagoDet->id]);
        }

        $consulta = DB::select('update tblpagos_enc set estado = "N", fecha_pago = "Null", monto_total = 0,
         otrosconceptos1 = 0, comision = 0, otrosconceptos2 = 0 ,otrosconceptos3 = 0, cuenta = NULL ,referencia_pago = 0,
         caja = NULL, tipo_cuenta = NULL, monto_recibido = NULL, capital_regresado = 0, updated_by = ? where id = ?;', 
        [$user,$pagoenc]);


        //MARCAR MOVIMIENTO EN CUENTA
        if (!is_null($tipo_cuenta)) {
          if ($tipo_cuenta == "CUENTA") {
            $cuentas = cuentas::find($cuenta);
            $cuentas->saldo_actual = $saldoCuenta;
            $cuentas->updated_by = auth()->user()->name;

            $historialcuent = new historial_cuentas();
            $historialcuent->id_cuenta = $cuenta;
            $historialcuent->id_empleado = auth()->user()->idempleado;
            $historialcuent->estado = "A";
            $historialcuent->tipo_movimiento = "CANCELACION";
            $historialcuent->concepto = "PAGO CANCELADO DE DISTRIBUIDOR #" . $idDis;
            $historialcuent->responsable = "DISTRIBUIDOR #" . $idDis;
            $historialcuent->ingreso = 0;
            $historialcuent->egreso = $monto_recibido;
            $historialcuent->saldo = $saldoCuenta;
            $historialcuent->numero_referencia = $pagoenc;
            $historialcuent->tipo_referencia = "tblpagos_enc";
            $historialcuent->numero_poliza = 0;
            $historialcuent->fecha = $fecha;
            $historialcuent->created_by = auth()->user()->name;
            $historialcuent->save();
          } else {
            $cuentas = Cajas::find($cuenta);
            $cuentas->saldo_actual = $saldoCuenta;
            $cuentas->updated_by = auth()->user()->name;

            $historialcuent = new historial_cajas();
            $historialcuent->id_caja = $cuenta;
            $historialcuent->id_empleado = auth()->user()->idempleado;
            $historialcuent->estado = "A";
            $historialcuent->tipo_movimiento = "CANCELACION";
            $historialcuent->concepto = "PAGO CANCELADO DE DISTRIBUIDOR #" . $idDis;
            $historialcuent->responsable = "DISTRIBUIDOR #" . $idDis;
            $historialcuent->ingreso = 0;
            $historialcuent->egreso = $monto_recibido;
            $historialcuent->saldo = $saldoCuenta;
            $historialcuent->numero_referencia = $pagoenc;
            $historialcuent->tipo_referencia = "tblpagos_enc";
            $historialcuent->numero_poliza = 0;
            $historialcuent->fecha = $fecha;
            $historialcuent->created_by = auth()->user()->name;
            $historialcuent->save();
          }
        }

        //DEVOLUCION DE CAPITAL
        if ($comision != 0) {
          $dis = distribuidores::find($idDis);
          $dis->capital = $NuevoCapital;
          $dis->updated_at = $date;
          $dis->updated_by = auth()->user()->name;
          $dis->save();
        }
        //ELIMINAR EXCEDENTE
        if ($excedente > 0) {
          $editable = DB::select('update tblexcedentedistribuidor set estado = "C" where id_distribuidor = ? and fecha_pago = ?;', [$idDis, $varfecha_pago]);
        }

        //CANCELACION A CLIENTES DEL PAGO
        $obtenerlistaclientesxdis = $this->obtenerdetallepagoClientes($idDis, $varfecha_relacion);
        foreach ($obtenerlistaclientesxdis as $clientes) {
          if ($clientes->statuspresenc == "P" || $clientes->statuspresenc == "A") {
            $dis = prestamos_valesdet::find($clientes->id);
            $dis->status = 'N';
            $dis->saldo = 0;
            $dis->updated_at = $fecha;
            $dis->updated_by = auth()->user()->name;
            $dis->save();

            $Borrartbl3 = DB::select('update tblprestamos_valesenc set  status = "A"  where id = ?;', [$clientes->idpresenc]);
            $obtenerclieEnc = $this->pagoscliEnc($clientes->idcliente, $varfecha_pago);
            foreach ($obtenerclieEnc as $cliEnc) {
              $cliEnc_id = $cliEnc->id;
            }
            $Borrartbl3 = DB::select('delete from tblpagoscli_det where idpagoclidet = ?;', [$cliEnc_id]);
            $Borrartbl2 = DB::select('delete from tblclipagos_enc where id_cliente = ? and fecha_relacion = ?;', [$clientes->idcliente, $varfecha_relacion]);
          }
        }

        if ($cuentas->save() && $dis->save()) {
          return back()->with("success", "se inserto correctamente");
        } else {
          return back()->with("warning", "no se inserto correctamente");
        }

        if ($cuentas->save() && $dis->save()) {
          return back()->with("success", "se inserto correctamente");
        } else {
          return back()->with("warning", "no se inserto correctamente");
        }
      } else {
        return back()->with("warnignCuenta", "saldo en la cuenta insuficiente");
      }
    } catch (\Illuminate\Database\QueryException $ex) {
      \Log::error('Error al guardar en la base de datos: ' . $ex->getMessage());
      return back()->with("warningBD", "no guardado correctamente");
    }
  }


  public function verpagos(int $distribuidorid)
  {
    $varpantallas = $this->Traermenuenc();
    $varsubmenus = $this->Traermenudet();
    $date = Carbon::now();
    $fecha = $date->format('Y-m-d');
    $ultimarela = 0;
    $excedente = 0;
    $obtenerultimarelacion = $this->obtenerultimarelacion($distribuidorid);
    foreach ($obtenerultimarelacion as $dato) {
      $ultimarela = $dato->id;
    }
    $detallepagoenc = $this->detallepagoenc($distribuidorid);
    $varpagoenc = $this->obtenerpagoenc($distribuidorid);
    $detallepagodet = $this->detallepagodet($distribuidorid);
    $detallexcleinte = $this->detallepagocliente($distribuidorid);
    $obtenerPagoTotal = $this->obtenerPagoTotal($distribuidorid);
    $obtenerAtrasoPagos = $this->obtenerAtrasoPagos($distribuidorid, $fecha);
    $saldoexcedente = $this->saldoexdistribuidor($distribuidorid);
    $varcuenta = $this->obtenercuentasActivas();
    $varcaja = $this->obtenerCajasActivas();
    $PagoTotal = 0;
    $Atraso = 0;
    $incompletoxCli = 0;

    foreach ($obtenerPagoTotal as $pago) {
      $PagoTotal = $pago->Pagos + $PagoTotal;
    }

    foreach ($obtenerAtrasoPagos as $atra) {
      if ($atra->status != "P") {
        $Atraso = $atra->pago_total + $Atraso;
      }
    }

    $Atraso = $Atraso;

    foreach ($saldoexcedente as $key) {
      $excedente = $key->monto;
    }

    if ($detallepagoenc->isEmpty()) {
      return back()->with("warningPagos", "No existen pagos aun");
    } else {

      $permisos = $this->forpermisos('cancelar_pagos');
      if ($permisos == "cancelar_pagos") {
        $permisosCancelacion = "A";
      } else {
        $permisosCancelacion = "I";
      }

      $permisos = $this->forpermisos('ver_pago_cli');
      if ($permisos == "ver_pago_cli") {
        $ver_pago_cli = "A";
      } else {
        $ver_pago_cli = "I";
      }

      return view('vales.pagos.verpagos', compact(
        'varpantallas',
        'varsubmenus',
        'detallepagoenc',
        'varpagoenc',
        'detallepagodet',
        'detallexcleinte',
        'PagoTotal',
        'Atraso',
        'permisosCancelacion',
        'ver_pago_cli',
        'excedente',
        'fecha',
        'ultimarela',
        'obtenerAtrasoPagos',
        'varcaja',
        'varcuenta'
      ));
    }
  }

  public function verClientesPagos(int $prestamoenc)
  {
    try {
      $varpantallas = $this->Traermenuenc();
      $varsubmenus = $this->Traermenudet();

      $detallepagodet = $this->obtenerdetallecliente($prestamoenc);
      return view('vales.pagos.detalleClientes', compact('varpantallas', 'varsubmenus', 'detallepagodet'));
    } catch (\Illuminate\Database\QueryException $ex) {
      return back()->with("warningBD", "no guardado correctamente");
    }
  }

  public function getdownloadReciboPago(int $idpago)
  {
    try {
      $date = Carbon::now();
      $fecha = $date->format('Y-m-d');
      $varempresas = $this->razonSocial("Vales");

      foreach ($varempresas as $empresa) {
        $razon_social = $empresa->razon_social;
        $nombre_empresa = $empresa->empresa;
        $marca_agua = $empresa->marca_agua;
        $icono = $empresa->icono;
      }

      $varinfo = $this->obtenerInformacionRecibo($idpago);
      $varinfoGASTO = $this->obtenerInformacionReciboGASTO($idpago);
      $varinfoCANCEL = $this->obtenerInformacionReciboCALNCEL($idpago);
      if (!$varinfo->isEmpty()) {
        $item = 0;
        foreach ($varinfo as $info) {
          $iddis = $info->iddis;
          $nombre_dis = $info->nombreDis;
          $relacion_fecha = $info->relacion;
          $nombre_cajera = $info->nombreEmple;
          $monto = $info->monto;
          $monto_total_abonado = $info->monto_total_abonado;
          $item = $item + 1;
        }

        $datos_iniciales = DB::select('select tblpagos_enc.proteccion_saldo, 
            (select tbldatos_iniciales.cantidad from tbldatos_iniciales where tbldatos_iniciales.concepto = "COSTO_TRANSACCION" LIMIT 1) AS costo_transaccion 
            from tblpagos_enc where id = ?;', [$idpago]);
        foreach ($datos_iniciales as $key) {
          $proteccion_saldo = $key->proteccion_saldo;
          $costo_transaccion = $key->costo_transaccion;
        }

        $varinfocli = $this->obtenerInformacionCliente($relacion_fecha, $iddis);
        $pdf = \PDF::setPaper('letter')->loadView('vales.PDF.reciboPago', compact(
          'razon_social',
          'nombre_empresa',
          'icono',
          'marca_agua',
          'varinfo',
          'varinfocli',
          'date',
          'nombre_cajera',
          'costo_transaccion',
          'proteccion_saldo',
          'monto',
          'iddis',
          'nombre_dis',
          'item',
          'monto_total_abonado',
          'varinfoGASTO',
          'varinfoCANCEL'
        ));
        return $pdf->download("RECIBO DE PAGO #" . $idpago . "_" . $fecha . ".pdf");
      } else {
        return back()->with("warningNohayrecibo", "no guardado correctamente");
      }
    } catch (\Illuminate\Database\QueryException $ex) {
      return back()->with("warningBD", "no guardado correctamente");
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

    if ($diadehoy) {
      $vardispagoconcentado = $this->distribuidoresconpagoconcentrado();
      foreach ($vardispagoconcentado as $ltspagxdiscon) {
        $tipo = $ltspagxdiscon->tipocuenta;
        if ($tipo == "CAJA") {
          $cuenta = $ltspagxdiscon->idcuenta;
        } else {
          $cuenta = $ltspagxdiscon->idcuenta;
        }

        $varfecha_pago = $ltspagxdiscon->ultimopagofecha;
        $fecha_pago = Carbon::createFromFormat('Y-m-d', $varfecha_pago);
        $diaactual = $fecha_pago->format('d');
        $vardistribuidor = $ltspagxdiscon->id_distirbuidor;
        $monto = $ltspagxdiscon->pago_total;
        $user = auth()->user()->name;
        $obtenerlineaspagoenc = $this->obtenerlienaprestamo($vardistribuidor);
        $comisiones = $this->obtenercomisionxprestamo($diaactual);

        if ($cuenta != 0) {
          if (!$obtenerlineaspagoenc->isEmpty()) {
            foreach ($obtenerlineaspagoenc as $linea) {
              $obtener_pagosIncompletos = $this->obtenerAtrasos($vardistribuidor);
              $obtenerSaldoAcumulado = $this->obtenerSaldoAcumulado($vardistribuidor);
              $saldo_pagar = $linea->saldo_pagar;
              $estado = $linea->estado;
              $idpagoenc = $linea->id;
              $fecha_relacion = $linea->fecha_relacion;
              $monto_t = 0;
              $saldosc = 0;
              $porcientocomision = 0;

              //PAGO DE TRANSACCION
              $costo_transaccion = 0;
              $varulticlipenc = 0;
              $saldoatraso = 0;
              $saldoatrasoclientes = 0;
              $incompleto = 0;
              $excedente = 0;
              $acumulado = 0;
              $comisioncalcul = 0;
              $cashBack = 0;
              $comi = 0;
              $montoreal = 0;
              $incompletocli_pago = 0;
              $incompletocli = 0;
              $funcion = 0;
              $totalcliente = 0;
              $status_atraso = "I";
              $status_excedente = "I";
              $clientesxdis = $this->obtenerdetallepagoClientes($vardistribuidor, $fecha_relacion);
              $totalcliente = count($clientesxdis);
              //Porcentajes de datos iniciales
              $porcen_comision = 0;
              $porcen_cashBack = 0;

              $datosini = $this->obtenerdatosiniciales();
              foreach ($datosini as $key) {
                if ($key->concepto == "COMISION") {
                  $porcen_comision = $key->cantidad;
                }

                if ($key->concepto == "CAPITAL_REGRESADO") {
                  $porcen_cashBack = $key->cantidad;
                }
              }

              //Checamos el saldo Atrasado
              if (!$obtener_pagosIncompletos->isEmpty()) {
                foreach ($obtener_pagosIncompletos as $atr) {
                  $incompleto = $incompleto + $atr->atraso;
                }
              }

              //Checamos el saldo Atrasado
              if (!$obtenerSaldoAcumulado->isEmpty()) {
                foreach ($obtenerSaldoAcumulado as $acumu) {
                  $acumulado = $acumulado + $acumu->acumulado;
                }
              }

              //Toma de fechas
              $fecha_rela = Carbon::parse($linea->fecha_relacion);
              $fecha_relacion1 = Carbon::parse($varfecha_pago);
              $fecha_relacion2 = Carbon::parse($varfecha_pago);
              $startDate = $fecha_relacion1->subDay(5);
              $endDate = $fecha_relacion2->addDay(6);
              $captial_actual = 0;

              //Checamos si merece comicion depende el dia de pago y el rango de fechas
              if ($fecha_rela >= $startDate && $fecha_rela <= $endDate) {

                echo "entro a fechas";
                foreach ($comisiones as $comision) {
                  if ($comision->dia_depago == $diaactual) {
                    echo "entro al dia de hoy";
                    $porcientocomision = $comision->comision;

                    //COMISION SACADA DEL CAPITAL
                    $obtprocompletodisxrel = $this->obtprocompletodisxrel($vardistribuidor, $fecha_relacion);
                    foreach ($obtprocompletodisxrel as $ltsobtdisxrel) {
                      $captial_actual = ($ltsobtdisxrel->capital * $porcen_comision) + $ltsobtdisxrel->interes;
                    }

                    //CALCULO DE COMSION MAS MONTO RECIBIDO
                    $comisioncalcul = floor(($captial_actual * $porcientocomision));
                    // $comisioncalcul = floor($saldo_pagar*$porcientocomision);
                    $montoreal = $monto + $comisioncalcul;
                    if ($montoreal == $saldo_pagar) {
                    } else {
                      $montoreal = $monto;
                      $comisioncalcul = 0;
                    }
                  } else {
                    $montoreal = $monto;
                  }
                }

                //CHECAR BIEN SI MERECE COMISION
                if ($monto > 0 and $montoreal >= $saldo_pagar || $acumulado > 0) {
                  $merece_comision = "si";
                } else {
                  $merece_comision = "no";
                }
              } else {
                $merece_comision = "no";
                $montoreal = $monto;
              }

              if ($montoreal > 0) {
                echo "enta a motoreal mayo";
                //si existe algun acumilado se pasa todo al pago actual para sumar cantidades
                if ($acumulado > 0) {
                  echo "entro a acumulado" . $acumulado;
                  $UpdAtr = DB::select('update tblpagos_enc set status_excedente = "I" WHERE id_distribuidor = ? and estado = "N";', [$vardistribuidor]);
                }

                // aplicar pagos incompletos
                if ($montoreal < $saldo_pagar) {
                  $statusdis = "N";
                  $consulta = DB::select('update tblpagos_enc set estado = ?  where id = ?;', [$statusdis, $idpagoenc]);

                  if ($montoreal < $saldo_pagar) {
                    //genera atraso de la relacion a pagar
                    $cashBack = 0;
                    $excedente = 0;
                    $pago_deincompleto = 0;
                    $saldoatraso = $saldo_pagar - $montoreal;
                    $saldoatrasoclientes = $saldoatraso / $totalcliente;
                    $status_atraso = "A";
                  } else {
                    //$cashBack = floor($saldo_pagar * .70); redondeamos el 30% de pago devuelto hacía abajo
                    //no tenemos atrasos en este pago así que guardamos esa informacion 
                    $saldoatraso = 0;
                    $saldoatrasoclientes = 0;
                    $UpdAtr = DB::select('update tblpagos_enc set otrosconceptos2 = "0" WHERE id = ?;', [$idpagoenc]);
                    foreach ($clientesxdis as $clientes) {
                      $UpdAtr = DB::select('update tblprestamos_valesdet set saldo = "0" WHERE id = ?;', [$clientes->id]);
                    }

                    //si hay atrasos por pagar vermos si podemos pagar con lo que resta
                    $pago_deincompleto = $montoreal - $saldo_pagar;

                    //pago de atraso y saldo acomulado
                    //compramos si el atraso registrado es igual al saldo extra para pago de atraso 
                    if ($pago_deincompleto == $incompleto) {
                      if ($incompleto > 0) {
                        foreach ($obtener_pagosIncompletos as $atr) {
                          $UpdAtr = DB::select('update tblpagos_enc set  monto_total = ?, status_atraso = "I" WHERE id = ?;', [$saldo_pagar, $atr->id]);
                        }
                        $UpdAtr = DB::select('update tblprestamos_valesdet  pres_det
                                      inner join tblprestamos_valesenc on pres_det.idprestamo_vales = tblprestamos_valesenc.id  
                                      inner join tblclientes_vales on tblprestamos_valesenc.idcliente = tblclientes_vales.id   set pres_det.saldo = 0 
                                      WHERE tblclientes_vales.iddistribuidor = ? and  pres_det.fecha_pago = ?;', [$vardistribuidor, $atr->fecha_relacion]);
                      }

                      $excedente = $pago_deincompleto;

                      if ($excedente > 0) {
                        $status_excedente = "I";
                      }
                    } else {
                      if ($incompleto > 0) {
                        foreach ($obtener_pagosIncompletos as $atr) {
                          if ($pago_deincompleto >= $atr->atraso) {
                            $UpdAtr = DB::select('update tblpagos_enc set  monto_total = ?, status_atraso = "I", monto_total = ? WHERE id = ?;', [$saldo_pagar, $atr->saldo_pagar, $atr->id]);
                            $UpdAtr = DB::select('update tblprestamos_valesdet  pres_det
                                      inner join tblprestamos_valesenc on pres_det.idprestamo_vales = tblprestamos_valesenc.id  
                                      inner join tblclientes_vales on tblprestamos_valesenc.idcliente = tblclientes_vales.id set pres_det.saldo = 0 
                                      WHERE tblclientes_vales.iddistribuidor = ? and  pres_det.fecha_pago = ?;', [$atr->iddis, $atr->fecha_relacion]);
                            $pago_deincompleto = $pago_deincompleto - $atr->atraso;
                          } else {
                            $pay = $pago_deincompleto - $atr->atraso;
                            if ($pay < 0) {
                              $pay = $atr->atraso - $pago_deincompleto;
                              $pago_deincompleto = 0;
                            }

                            $monto_t = $atr->monto_total + $pago_deincompleto;
                            $UpdAtr = DB::select('update tblpagos_enc set monto_total = ?, otrosconceptos2 = ?,status_atraso = "A" WHERE id = ?;', [$monto_t, $pay, $atr->id]);

                            $incompletocli = DB::select('select count(tblclientes_vales.id) from tblprestamos_valesdet  pres_det
                                      inner join tblprestamos_valesenc on pres_det.idprestamo_vales = tblprestamos_valesenc.id  
                                      inner join tblclientes_vales on tblprestamos_valesenc.idcliente = tblclientes_vales.id   
                                      WHERE tblclientes_vales.iddistribuidor = ? and  pres_det.fecha_pago = ?;', [$vardistribuidor, $atr->fecha_relacion]);
                            $incompletocli_pago = $pay / $incompletocli;

                            $UpdAtr = DB::select('update tblprestamos_valesdet  pres_det
                                      inner join tblprestamos_valesenc on pres_det.idprestamo_vales = tblprestamos_valesenc.id  
                                      inner join tblclientes_vales on tblprestamos_valesenc.idcliente = tblclientes_vales.id   set pres_det.saldo = ? 
                                      WHERE tblclientes_vales.iddistribuidor = ? and  pres_det.fecha_pago = ?;', [$incompletocli_pago, $vardistribuidor, $atr->fecha_relacion]);

                            if ($pago_deincompleto == 0) {
                              break;
                            }
                          }
                        }
                      }
                      $excedente = $pago_deincompleto;

                      if ($excedente > 0) {
                        $status_excedente = "A";
                      }
                    }
                  }
                } else {
                  $statusdis = "P";
                  //$cashBack = floor($saldo_pagar * .70); redondeamos el 30% de pago devuelto hacía abajo
                  $saldoatraso = 0;
                  $saldoatrasoclientes = 0;
                }

                //PROCESO DE INSERT
                $pagoconcilidado = 1;
                $funcion = $this->insertarPagos(
                  $vardistribuidor,
                  $idpagoenc,
                  $monto,
                  $saldo_pagar,
                  $cashBack,
                  $varfecha_pago,
                  $fecha_relacion,
                  $montoreal,
                  $comisioncalcul,
                  $saldoatraso,
                  $saldoatrasoclientes,
                  $status_atraso,
                  $status_excedente,
                  $excedente,
                  $cuenta,
                  $tipo,
                  $totalcliente,
                  $fecha,
                  $user,
                  $pagoconcilidado,
                  $costo_transaccion
                );
                $monto = 0;

                $UpdAtr = DB::select('update tblpagosconcentrado set  status = "P" WHERE id_distirbuidor = ? AND fecha_relacion = ?;', [$vardistribuidor, $fecha_relacion]);
              } else {
                if ($diadehoy >= 21 and $diadehoy <= 28 || $diadehoy >= 6 and $diadehoy <= 13) {
                  echo "entro aqui";
                  $consulta = DB::select('update tblpagos_enc set estado = "P", status_atraso = "I"  where id = ?;', [$idpagoenc]);

                  $totalPag = $saldo_pagar - $monto;
                  $pagosenc = new pagosenc();
                  $pagosenc->id_distribuidor = $vardistribuidor;
                  $pagosenc->saldo_pagar = $totalPag;
                  $pagosenc->comision = 0;
                  $pagosenc->interes = 0;
                  $pagosenc->monto_total = $totalPag;
                  ;
                  $pagosenc->estado_generado = "u";
                  $pagosenc->estado = "N";
                  $pagosenc->fecha_pago = "Null";
                  $pagosenc->fecha_pago = "A";
                  $pagosenc->fecha_relacion = $fecha_relacion;
                  $pagosenc->otrosconceptos1 = 0;
                  $pagosenc->otrosconceptos2 = 0;
                  $pagosenc->otrosconceptos3 = 0;
                  $pagosenc->created_at = $fecha;
                  $pagosenc->created_by = auth()->user()->name;
                  $pagosenc->save();
                } else {
                  echo "entro else de else";
                }
              }
            }
          } else {
            return back()->with("warningRelacion", "Error");
          }
        } else {
          return back()->with("warningCuenta", "Error");
        }
      }

      return back()->with("success", "aplicado");
    } else {
      return back()->with("warningConcilia", "Error");
      // echo "No es dia de aplicar pagos concentrados";
    }
  }

}

