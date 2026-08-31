<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Traits\MenuTrait;
use App\Traits\DatosimpleTraits;
use App\Models\pagosenc;
use App\Traits\SistemasTraits;
use DB;
use Log;
use \Milon\Barcode\DNS1D;
use \Milon\Barcode\DNS2D; 
use ZipArchive;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use App\Models\referencias_pago;
use App\Models\HistorialDosPorciento;

class RelacionController extends Controller
{
    
  use MenuTrait;
  use DatosimpleTraits;
  use SistemasTraits;

  public function __construct()
  {
      $this->middleware('auth');
  }

  public function index(){
    try{
      $obtenerlista="";
      $varpantallas = $this->Traermenuenc();
      $varsubmenus = $this->Traermenudet();
      $idusuario=auth()->user()->id;
      $idempleado=auth()->user()->idempleado;
      $permisos = $this->forpermisos('ver_todos_cordinadores'); 
      $permisos1 = $this->forpermisos('ver_todos_xsuc_cordinadores'); 
      $permisos2 = $this->forpermisos('ver_solo_tus_relaciones');  
      $obtenerultimafecharel = $this->obtenerultimafecharel();
      foreach($obtenerultimafecharel as $dato){ $fecha_rel =  $dato->fecha_relacion;}
      
         if($obtenerultimafecharel->isEmpty()){
              $date = Carbon::now();
              $fecha_rel = $date;
          }
          
       $validaDescargoRel = $this->descargaRel($fecha_rel);
       $saldoscordinador = $this->obtenersaldodistri($fecha_rel);

      if($permisos=='ver_todos_cordinadores'){ 
        $obtenerlista = $this->obtenerlistacordinadoresgeneral();
          return view('vales.Relaciones.relaciones',compact('varpantallas','varsubmenus','validaDescargoRel','obtenerlista','saldoscordinador'));
      
      }elseif($permisos1=='ver_todos_xsuc_cordinadores'){ 
        $sucursal = "";
        $varsucemp = $this->obtenersucursalxempleado($idempleado);
        foreach($varsucemp as $list){$sucursal = $list->idsucursal; }
        $obtenerlista = $this->obtenercoordinadoresxsucursal($sucursal);
        return view('vales.Relaciones.relaciones',compact('varpantallas','varsubmenus','validaDescargoRel','obtenerlista','saldoscordinador'));
      
      }elseif($permisos2=='ver_solo_tus_relaciones'){
        $obtenehistorialxcoord = $this->obtenehistorialxcoord($idempleado);
        $obtenerlista = $this->obtenercoordinadorxid($idempleado);
        return view('vales.Relaciones.relacionesxcoord',compact('varpantallas','varsubmenus','validaDescargoRel','obtenerlista','saldoscordinador','obtenehistorialxcoord'));
      
      }else{
        return back()->with("warningBD","no guardado correctamente"); 
      }
      
    } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
  }

  public function recalcularRelaciones(int $id_coord){
      $totalPag = 0;
      $varempresas = $this->razonSocial("Vales");
      foreach ($varempresas as $empresa) {
          $razon_social = $empresa->razon_social;
          $empresa = $empresa->empresa;
      }

      $dist = $this->obtenerdisporcoord($id_coord);

      $totaldistribuidor = 0;
      $date = Carbon::now();
      $fecha = $date;
      $añoactual = $date->year;
      $mesactual = $date->month;
      $diaactual = $date->day;

      if ($diaactual >= 8 && $diaactual <= 22) {
          $fechaAjustada = Carbon::create($añoactual, $mesactual, 8);
      } else {
          if ($diaactual >= 23) {
              $fechaAjustada = Carbon::create($añoactual, $mesactual, 23);
          } else {
              $fechaAjustada = Carbon::create($añoactual, $mesactual - 1, 23);
          }
      }
      $diaactual = $fechaAjustada->day;
      Log::info("Fecha ajustada: " . $diaactual);

      if ($diaactual == 8) {
        $varlistacom = $this->obtenercomison1();
        $mesanterior = $mesactual - 1;
        $varfechacorteini = Carbon::create($añoactual, $mesanterior, 23)->startOfDay();
        $varfechacortefin = Carbon::create($añoactual, $mesactual, 7)->startOfDay();
        $varfechaquincenaprox = Carbon::create($añoactual, $mesactual, 15)->startOfDay();

    } elseif ($diaactual == 23) {
        $varlistacom = $this->obtenercomison2();
        $varfechacorteini = Carbon::create($añoactual, $mesactual, 8)->startOfDay();
        $varfechacortefin = Carbon::create($añoactual, $mesactual, 22)->startOfDay();
        $cantidadDias = cal_days_in_month(CAL_GREGORIAN, $mesactual, $añoactual);
        $diasdelmes = $cantidadDias;
        $varfechaquincenaprox = Carbon::create($añoactual, $mesactual, $diasdelmes)->startOfDay();
    }
    Log::info("Fecha de corte inicial: " . $varfechacorteini->toDateString() . " Fecha quincena próxima: " . $varfechaquincenaprox->toDateString());
      // Eliminar registros de la tabla tblpagos_enc
      foreach ($dist as $distribuidor) {
        DB::table('tblpagos_enc')
            ->where('fecha_corte_inicio', $varfechacorteini->toDateString())
            ->where('fecha_corte_final', $varfechacortefin->toDateString())
            ->where('estado', 'N')
            ->where('id_distribuidor', $distribuidor->id)
            ->delete();
      }

      $pattern = public_path("Expedientes/Relaciones/{$id_coord}-{$varfechaquincenaprox->toDateString()}.zip");
      $files = glob($pattern);
      foreach ($files as $file) {
          if (File::exists($file)) {
              File::delete($file);
              Log::info("Archivo eliminado: " . $file);
          }
      }
      
      $distribuidores = $this->obtenerdisparadesglose($varfechaquincenaprox->toDateString(), $id_coord);
      Log::info("Distribuidores: " . $distribuidores);
      foreach ($distribuidores as $listadis) {
          $fechacorteinicio = substr(Carbon::createFromFormat('Y-m-d', $varfechacorteini->toDateString()), 0, 10);
          $fechacortefinal = substr(Carbon::createFromFormat('Y-m-d', $varfechacortefin->toDateString()), 0, 10);
          $fechaquincena = substr(Carbon::createFromFormat('Y-m-d', $varfechaquincenaprox->toDateString()), 0, 10);
          $pagosnom = DB::select('CALL relacionpagosdistribuidor(?,?,?,?)', [$listadis->iddistribuidor, $fechacorteinicio, $fechacortefinal, $fechaquincena]);
          $resultadoproc = collect($pagosnom);
          $dis = $listadis->iddistribuidor;
          $varchecarelacion = $this->checarrelaciongenerada($dis, $fechaquincena);

          foreach ($varchecarelacion as $relacion) {

              $estado = $relacion->estado;
          }

          if ($varchecarelacion->isEmpty() || $estado == "C") {
              $presdet = $this->presdetdisFec($fechaquincena, $dis);
              foreach ($presdet as $det) {
                  if ($det->status == "F" || $det->status == "CC") {
                  } else {
                      $actualizadet = DB::select('update tblprestamos_valesdet set status = "N" ,updated_by = ? WHERE id = ?;', ['mgarcia', $det->id]);
                  }
              }
              $totalPag = $this->obtenersaldodis($dis, $fechaquincena);
              foreach ($totalPag as $totdist) {
                  $totalPag = $totdist->totaldis;
                  $proteccion = floor($totalPag / 1000);
              }

              if(!is_null($totalPag) || $totalPag > 0){
                if($totalPag == null){
                $totalPag = 0;
              }
              $pagosenc = new pagosenc();
                $pagosenc->id_distribuidor = $dis;
                $pagosenc->saldo_pagar = $totalPag;
                $pagosenc->comision = 0;
                $pagosenc->interes = 0;
                $pagosenc->monto_total = 0;
                $pagosenc->estado_generado = "u";
                $pagosenc->estado = "N";
                $pagosenc->fecha_pago = "Null";
                $pagosenc->fecha_relacion = $fechaquincena;
                $pagosenc->fecha_corte_inicio = $fechacorteinicio;
                $pagosenc->fecha_corte_final = $fechacortefinal;
                $pagosenc->otrosconceptos1 = 0;
                $pagosenc->otrosconceptos2 = 0;
                $pagosenc->otrosconceptos3 = 0;
                $pagosenc->proteccion_saldo = $proteccion;
                $pagosenc->costo_transaccion = 16;
                $pagosenc->created_at = $fecha;
                $pagosenc->created_by = 'mgarcia';
                $pagosenc->save();
              }
          } else {
          }
          $id = $dis;
          $boelanosficha = $this->checarsiexistefpago($dis);
          if($boelanosficha->isEmpty()){
            //fichas de pago bvva bancomer
            //obtenemos la referencia origen
            $ceroscomplementarios = '00000000';
            $caracteres = mb_strlen($id);
            $numerosdeceros =9-$caracteres;
            $cero = '';
            $cadenasteca="";
            $tipoprestamo='03';
            $cadenaorigen = '';
            $multiplicador = 2;
            $totalnumerosmul=0;
            $var1 =0;
            $cadenamul=0;
            $cadenamultiplicadaf=0;
            $sumacadena=0;
            $divisionbbva1=0;
            $residuo = 0;
            $referenciaoldbbva='';
            $datoverificador=0;
            $arraypound = array(11,23,19,17,13,11,23,19,17,13,11); 
            $referenciacondatoverificador = 0;
              for ($i = 0; $i<=$numerosdeceros-1; $i++){
                $cero=$cero.'0';
                $totalceros = $cero;
              }
        
              $cadenaorigen = $tipoprestamo.$totalceros.$id;   
              for ($j = 0; $j < strlen($cadenaorigen); $j++) {
                $digito = intval($cadenaorigen[$j]);
                $resultado = $digito * $multiplicador;
            
                // Si el resultado es de dos dígitos, sumar los dígitos del resultado
                if ($resultado >= 10) {
                    $resultado = array_sum(str_split($resultado));
                }
            
                // Concatenar el resultado a la cadena multiplicada
                $cadenamul .= $resultado;
            
                // Alternar el multiplicador entre 2 y 1
                $multiplicador = ($multiplicador == 2) ? 1 : 2;
              }
              // Sumar todos los dígitos de la cadena multiplicada
              for ($k = 0; $k < strlen($cadenamul); $k++) {
                $sumacadena += intval($cadenamul[$k]);
              }

              // Dividir la suma entre 10 y obtener el residuo
              $residuo = $sumacadena % 10;

              // Restar el residuo a 10 para obtener el dato verificador
              $datoverificador = (10 - $residuo) % 10;

              // Concatenar el dato verificador a la cadena original
              $referenciaoldbbva = $ceroscomplementarios . $cadenaorigen;
              $referenciacondatoverificador = $referenciaoldbbva . $datoverificador;

            
              //armamos la referencia de pago banco azteca
              $basebasica = 97;
              $Valorcontante = 234;
              $obtenerresiduo = 0;
              $residuo = 0;
              $dtvbancoazteca=0;
              $cadenamulbancoazteza=0;
              $suma =0;
      
              for($a = 0; $a<=strlen($cadenaorigen);  $a++){
                if($a==11){break;}
                else{
                  $cadenamulbancoazteza = $cadenamulbancoazteza + $cadenaorigen[$a]* $arraypound[$a];
                }
              }
              $suma= $cadenamulbancoazteza + $Valorcontante;
              for($b = 0; $b<=$suma;  $b++){
                $obtenerresiduo = $obtenerresiduo + $basebasica;
                if($obtenerresiduo <= $suma){
                  $dtvbancoazteca = $obtenerresiduo;
                }
                else{$residuo = $suma - $dtvbancoazteca;
                  break;
                }
              }
              $residuo= $residuo+1;


              //conteo de caracteres en residio azteca
              $conteo_caracazt= strlen($residuo);
              if($conteo_caracazt == 1){
                  $residuo= "0".$residuo;
              }
              $cadenasteca = $cadenaorigen.$residuo;

              //Inserta las referencias de pago
              for($i =0; $i<=1; $i++){
                if($i ==0){
                  $referencia = new referencias_pago();
                  $referencia->idempresa = 1;
                  $referencia->id_dis = $dis;
                  $referencia->id_fichapago = 1;
                  $referencia->referencia = $cadenasteca;
                  $referencia->estatus = 1;
                  $referencia->referencia_origen = $cadenaorigen;
                  $referencia->created_at = $fecha;
                  $referencia->created_by = 'mgarcia';
                  $referencia->save();
    
                }
                if($i ==1){
                  $referencia2 = new referencias_pago();
                  $referencia2->idempresa = 1;
                  $referencia2->id_dis = $dis;
                  $referencia2->id_fichapago = 2;
                  $referencia2->referencia = $referenciacondatoverificador;
                  $referencia2->estatus = 1;
                  $referencia2->referencia_origen = $referenciaoldbbva;
                  $referencia2->created_at = $fecha;
                  $referencia2->created_by = 'mgarcia';
                  $referencia2->save();
                }
              }
              
          }
          else{
            foreach($boelanosficha as $lista){
              if($lista->id_fichapago==2){
                $referenciacondatoverificador = $lista->referencia;
              }
              if($lista->id_fichapago==1){
                $cadenasteca = $lista->referencia;
              }
            }
          }
        }

        $districon2porcientomas = DB::select("SELECT * FROM(SELECT tblpagos_enc.id_distribuidor,COUNT(tblpagos_enc.id) as pagos 
          from tblpagos_enc WHERE tblpagos_enc.fecha_pago >= tblpagos_enc.fecha_corte_final 
          and tblpagos_enc.fecha_pago <= tblpagos_enc.fecha_relacion and tblpagos_enc.flag2porciento = 0 
          and tblpagos_enc.fecha_relacion < ?  GROUP BY tblpagos_enc.id_distribuidor)a WHERE a.pagos >= 4;",[$varfechaquincenaprox->toDateString()]);
          $districon2porcientomas = collect($districon2porcientomas);
          foreach($districon2porcientomas as $dosporce){
              $pagosenc = new HistorialDosPorciento();
              $pagosenc->id_distribuidor = $dosporce->id_distribuidor;
              $pagosenc->fecha_relacion = $varfechaquincenaprox->toDateString();
              $pagosenc->estado = "A";
              $pagosenc->created_by = 'mgarcia';
              $pagosenc->save();
          }
          return back()->with("success","Relaciones recalculadas correctamente");
  }



  public function  generar_reporte_relacionesautomaticas(){

    // try{

    //PARA COMODAR 1 POR CADA 1000
    // $actualizadet = DB::select('select * from tblpagos_enc where fecha_relacion = "2024-11-15";');
    // foreach($actualizadet as $totdist)
    // {
    //   $totalPag = $totdist->saldo_pagar;
    //   $proteccion = floor($totalPag/1000);
    //   $actualizadet = DB::select('update tblpagos_enc set proteccion_saldo = ? where id = ?;',[$proteccion,$totdist->id]);
    // }

    // return "ya";
      $totalPag = 0;
      $varempresas =$this->razonSocial("Vales");
      foreach($varempresas as $empresa)
      {
        $razon_social = $empresa->razon_social;$empresa = $empresa->empresa;
          
      }
      $totaldistribuidor = 0;
      $varpantallas =  $this->Traermenuenc();
      $varsubmenus =   $this->Traermenudet();
      $date = Carbon::now();
      $fecha = $date;
      $añoactual = $fecha->year;
      $mesactual = $fecha->month;
      $diaactual = $fecha->day;
      $varfechacorteini = "";
      $varfechacortefin="";
      $varfechaquincenaprox="";
      $varfechapago = "";
      $mesanterior = 0;
      $messuiguiente=0;
      $diasdelmes =0;
      $resultadoproc=0;
      $sum = 0;
      $añoactual = 2025;
      $mesactual = "01";
      $diaactual =  9;
      // $añoactual = $fecha->format('Y');
      // $mesactual = $fecha->format('m');
      // $diaactual =  $fecha->format('d');
    
      if($diaactual == 9 ){
         $varlistacom =  $this->obtenercomison1();
         $mesanterior = $mesactual-1;
         $varfechacorteini = $añoactual."-".$mesanterior."-23";
         $varfechacortefin = $añoactual."-".$mesactual."-07";
         $varfechaquincenaprox=$añoactual."-".$mesactual."-15";
      }
      elseif($diaactual == 23)
      {
        $varlistacom =  $this->obtenercomison2();
        $varfechacorteini = $añoactual."-".$mesactual."-08";
        $varfechacortefin = $añoactual."-".$mesactual."-22";
        $cantidadDias = cal_days_in_month(CAL_GREGORIAN, $mesactual, $añoactual);
        if($cantidadDias == 31){
          $diasdelmes =31;
        }
        if ($cantidadDias == 30) {
          $diasdelmes =30;
        }
        if ($cantidadDias == 28) {
          $diasdelmes =28;
        }
        if ($cantidadDias == 29) {
          $diasdelmes =29;
        } 
        $varfechaquincenaprox= $añoactual."-".$mesactual."-".$diasdelmes;

      }else{
        return back()->with("PagosDiawarning","no exitoso");
      }

      $distribuidores = $this->obtenerdisparadesglose($varfechaquincenaprox);
  
      foreach ($distribuidores as $listadis) {
         $fechacorteinicio = substr(Carbon::createFromFormat('Y-m-d', $varfechacorteini),0,10);
         $fechacortefinal = substr(Carbon::createFromFormat('Y-m-d', $varfechacortefin),0,10);
         $fechaquincena = substr(Carbon::createFromFormat('Y-m-d', $varfechaquincenaprox),0,10);
         $pagosnom = DB::select('CALL relacionpagosdistribuidor(?,?,?,?)', [$listadis->iddistribuidor,$fechacorteinicio,$fechacortefinal,$fechaquincena]);  
         $resultadoproc = collect($pagosnom);
         $dis = $listadis->iddistribuidor;
         $varchecarelacion = $this->checarrelaciongenerada($dis,$fechaquincena);
         foreach($varchecarelacion as $relacion)
         {
           $estado = $relacion->estado;
         }
  
  
         if($varchecarelacion->isEmpty() || $estado == "C"){
              $presdet = $this->presdetdisFec($fechaquincena,$dis);
              foreach($presdet as $det){
                if($det->status == "F" || $det->status == "CC"){
                  error_log('No aplica');
                }else{
                  $actualizadet = DB::select('update tblprestamos_valesdet set status = "N" ,updated_by = ? WHERE id = ?;', [auth()->user()->name,$det->id]);
                }
              }
              $totalPag = $this->obtenersaldodis($dis, $fechaquincena);
              foreach($totalPag as $totdist)
              {
                $totalPag = $totdist->totaldis;
                $proteccion = floor($totalPag/1000);
              }
              if(!is_null($totalPag) || $totalPag > 0){

                $pagosenc = new pagosenc();
                $pagosenc->id_distribuidor=$dis;
                $pagosenc->saldo_pagar=$totalPag;
                $pagosenc->comision=0;
                $pagosenc->interes=0;
                $pagosenc->monto_total=0;
                $pagosenc->estado_generado="u";
                $pagosenc->estado="N";
                $pagosenc->fecha_pago="Null";
                $pagosenc->fecha_relacion= $fechaquincena;
                $pagosenc->fecha_corte_inicio = $fechacorteinicio;
                $pagosenc->fecha_corte_final = $fechacortefinal;
                $pagosenc->otrosconceptos1=0;
                $pagosenc->otrosconceptos2=0;
                $pagosenc->otrosconceptos3=0;
                $pagosenc->proteccion_saldo=$proteccion;
                $pagosenc->costo_transaccion = 16;
                $pagosenc->created_at=$fecha;
                $pagosenc->created_by = auth()->user()->name;
                $pagosenc->save();
              }
          }
         else{
           error_log('Ya existe una relacion');
         }
  
         $id = $dis;
         $boelanosficha = $this->checarsiexistefpago($dis);
         if($boelanosficha->isEmpty()){
           //fichas de pago bvva bancomer
           //obtenemos la referencia origen
           $ceroscomplementarios = '00000000';
           $caracteres = mb_strlen($id);
           $numerosdeceros =9-$caracteres;
           $cero = '';
           $cadenasteca="";
           $tipoprestamo='03';
           $cadenaorigen = '';
           $multiplicador = 2;
           $totalnumerosmul=0;
           $var1 =0;
           $cadenamul=0;
           $cadenamultiplicadaf=0;
           $sumacadena=0;
           $divisionbbva1=0;
           $residuo = 0;
           $referenciaoldbbva='';
           $datoverificador=0;
           $arraypound = array(11,23,19,17,13,11,23,19,17,13,11); 
           $referenciacondatoverificador = 0;
             for ($i = 0; $i<=$numerosdeceros-1; $i++){
               $cero=$cero.'0';
               $totalceros = $cero;
             }
       
             $cadenaorigen = $tipoprestamo.$totalceros.$id;   
             for ($j = 0; $j < strlen($cadenaorigen); $j++) {
              $digito = intval($cadenaorigen[$j]);
              $resultado = $digito * $multiplicador;
          
              // Si el resultado es de dos dígitos, sumar los dígitos del resultado
              if ($resultado >= 10) {
                  $resultado = array_sum(str_split($resultado));
              }
          
              // Concatenar el resultado a la cadena multiplicada
              $cadenamul .= $resultado;
          
              // Alternar el multiplicador entre 2 y 1
              $multiplicador = ($multiplicador == 2) ? 1 : 2;
            }
            // Sumar todos los dígitos de la cadena multiplicada
            for ($k = 0; $k < strlen($cadenamul); $k++) {
              $sumacadena += intval($cadenamul[$k]);
            }

            // Dividir la suma entre 10 y obtener el residuo
            $residuo = $sumacadena % 10;

            // Restar el residuo a 10 para obtener el dato verificador
            $datoverificador = (10 - $residuo) % 10;

            // Concatenar el dato verificador a la cadena original
            $referenciaoldbbva = $ceroscomplementarios . $cadenaorigen;
            $referenciacondatoverificador = $referenciaoldbbva . $datoverificador;

            //calculamos el datos verificador
            // multiplicamos por dos y por 1 comenzando desde la 
       
            //  for($j = 0; $j<=strlen($cadenaorigen);  $j++){
            //    if($j==11){break;}
            //    if($cadenaorigen[$j]*$multiplicador==10){
            //      $cadenamul=$cadenamul.'1';
            //    }
            //    if($cadenaorigen[$j]*$multiplicador==12 ||$cadenaorigen[$j]*$multiplicador==14 ||$cadenaorigen[$j]*$multiplicador==16||$cadenaorigen[$j]*$multiplicador==18){
            //      $cadenamul=$cadenamul.$cadenaorigen[$j];
            //    }
            //    else{
            //      $cadenamul=$cadenamul.$cadenaorigen[$j]*$multiplicador;
            //    }
               
            //    if($multiplicador==2){$multiplicador =1;}
            //    else{$multiplicador=2;}
            //  }
       
            //  $cadenamultiplicadaf = substr($cadenamul,1,11);
            //  // sumamos los numero de la cadenayamultiplcada
            //  for($k = 0; $k<=$cadenamultiplicadaf; $k++){
            //    if($k==11){break;}
            //    $sumacadena = $sumacadena+$cadenamultiplicadaf[$k];
            //  }
             
            //  $divisionbbva1 = $sumacadena/10;

            //  //conteo de caracteres
            //  $conteo_carac= strlen($divisionbbva1);
            //  if($conteo_carac <= 1){
            //   $divisionbbva1 = number_format($divisionbbva1,1);
            //  }
           
            //  $residuo  = explode(".", $divisionbbva1);
             
            //  if($residuo[1]==0){
            //    $datoverificador=1;
            //  }else{
            //    $datoverificador =10-$residuo[1];
            //  }
            //  $referenciaoldbbva  =$ceroscomplementarios.$cadenaorigen;
            //  $referenciacondatoverificador=$referenciaoldbbva.$datoverificador;
           
             //armamos la referencia de pago banco azteca
             $basebasica = 97;
             $Valorcontante = 234;
             $obtenerresiduo = 0;
             $residuo = 0;
             $dtvbancoazteca=0;
             $cadenamulbancoazteza=0;
             $suma =0;
     
             for($a = 0; $a<=strlen($cadenaorigen);  $a++){
               if($a==11){break;}
               else{
                 $cadenamulbancoazteza = $cadenamulbancoazteza + $cadenaorigen[$a]* $arraypound[$a];
               }
             }
             $suma= $cadenamulbancoazteza + $Valorcontante;
             for($b = 0; $b<=$suma;  $b++){
               $obtenerresiduo = $obtenerresiduo + $basebasica;
               if($obtenerresiduo <= $suma){
                 $dtvbancoazteca = $obtenerresiduo;
               }
               else{$residuo = $suma - $dtvbancoazteca;
                 break;
               }
             }
             $residuo= $residuo+1;


             //conteo de caracteres en residio azteca
             $conteo_caracazt= strlen($residuo);
             if($conteo_caracazt == 1){
                $residuo= "0".$residuo;
             }
             $cadenasteca = $cadenaorigen.$residuo;

             //Inserta las referencias de pago
             for($i =0; $i<=1; $i++){
               if($i ==0){
                 $referencia = new referencias_pago();
                 $referencia->idempresa = 1;
                 $referencia->id_dis = $dis;
                 $referencia->id_fichapago = 1;
                 $referencia->referencia = $cadenasteca;
                 $referencia->estatus = 1;
                 $referencia->referencia_origen = $cadenaorigen;
                 $referencia->created_at = $fecha;
                 $referencia->created_by = auth()->user()->name;
                 $referencia->save();
  
               }
               if($i ==1){
                 $referencia2 = new referencias_pago();
                 $referencia2->idempresa = 1;
                 $referencia2->id_dis = $dis;
                 $referencia2->id_fichapago = 2;
                 $referencia2->referencia = $referenciacondatoverificador;
                 $referencia2->estatus = 1;
                 $referencia2->referencia_origen = $referenciaoldbbva;
                 $referencia2->created_at = $fecha;
                 $referencia2->created_by = auth()->user()->name;
                 $referencia2->save();
               }
             }
             
         }
         else{
           foreach($boelanosficha as $lista){
             if($lista->id_fichapago==2){
               $referenciacondatoverificador = $lista->referencia;
             }
             if($lista->id_fichapago==1){
               $cadenasteca = $lista->referencia;
             }
           }
         }
      }


      //checar 2%
      $districon2porcientomas = DB::select("SELECT * FROM(SELECT tblpagos_enc.id_distribuidor,COUNT(tblpagos_enc.id) as pagos 
        from tblpagos_enc WHERE tblpagos_enc.fecha_pago >= tblpagos_enc.fecha_corte_final 
        and tblpagos_enc.fecha_pago <= tblpagos_enc.fecha_relacion and tblpagos_enc.flag2porciento = 0 
        and tblpagos_enc.fecha_relacion < ?  GROUP BY tblpagos_enc.id_distribuidor)a WHERE a.pagos >= 4;",[$varfechaquincenaprox]);
        $districon2porcientomas = collect($districon2porcientomas);

        foreach($districon2porcientomas as $dosporce){
            $pagosenc = new HistorialDosPorciento();
            $pagosenc->id_distribuidor = $dosporce->id_distribuidor;
            $pagosenc->fecha_relacion = $varfechaquincenaprox;
            $pagosenc->estado = "A";
            $pagosenc->created_by = auth()->user()->name;
            $pagosenc->save();
        }
      return back()->with("success","se logro");
    // } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
  }
  
  // ESTA FUNCION SE USA PARA LAS RALACIONES
  public function generarfichasautomaticas(int $idcordinadior, string $fechaquincena){
    try{
      $bbva = 0;
      $bancoAzteca = 0;$varempresas =$this->razonSocial("Vales");
      $date = Carbon::now();
      $fecha = $date;
      $barra = new DNS1D(); 
      $idusuario=auth()->user()->id;
      $fechaquincena = Carbon::parse($fechaquincena);
      $añoactual = $fechaquincena->format('Y');
      $mesactual = $fechaquincena->format('m');
      $diaactual =  $fechaquincena->format('d');
      $varfechaquincenaprox = $añoactual."-".$mesactual."-".$diaactual;
      $listadistirbuidores = $this->oobtenerrelacioneshistorial($idcordinadior,$varfechaquincenaprox);
      // $añoactual = 2024;
      // $mesactual = "04";
      // $diaactual =  21;
      foreach($varempresas as $empresa){
        $razon_social = $empresa->razon_social;
        $icono = $empresa->icono;
        $marca_agua = $empresa->marca_agua;
        $empresa= $empresa->empresa;
      }

      if($diaactual >= 14 && $diaactual <= 20)
      {
        $varlistacom =  $this->obtenercomison1A();
      }elseif($diaactual >= 29  || $diaactual <= 5 )
      {
        $varlistacom =  $this->obtenercomison2B();
      }else{
        return back()->with("NO_APLICA_FECHA", "No");
      }
      

      if($listadistirbuidores->isEmpty()){
        return back()->with("No_Se_Encontro","Errror");
      }
      else{
        foreach($listadistirbuidores as $listadis)
        {
          $distri = $listadis->id_distribuidor;
          $distribuidores = $this->obtenerdis($distri);
          $id = $distri;
          $resultadoproc = $this->encabezadorelacionesHistorial($distri,$varfechaquincenaprox);
          $saldosrelacion = $this->obtnersaldosxrelacion($distri);
          $captial_actual = 0;
          //Porcentajes de datos iniciales
          $porcen_comision = 0;
          $datosini = $this->obtenerdatosiniciales();
          foreach($datosini as $key){
            if($key->concepto == "COMISION"){ $porcen_comision = $key->cantidad; }
          }
          
          //COMISION EN BASE A CAPITAL
          $obtprocompletodisxrel=$this->obtprocompletodisxrel($distri,$varfechaquincenaprox);
          foreach($obtprocompletodisxrel as $ltsobtdisxrel)
          {
            // $capp = $ltsobtdisxrel->capital*$porcen_comision;
            // $inter = $ltsobtdisxrel->interes;
            $captial_actual = ($ltsobtdisxrel->capital*$porcen_comision) + $ltsobtdisxrel->interes;
          }
          // return $capp." + ".$inter;

          $refe = $this->obtenerreferencias($distri);
          foreach($refe as $listarefe){
            if($listarefe->id_fichapago == 2){
              $bbva = $listarefe->referencia;
            }
            if($listarefe->id_fichapago == 1){
              $bancoAzteca = $listarefe->referencia;
            }
          }

          $nombrearchivo ="RELACION_DV".$distri;
          $rutaacomprimir = "Expedientes/Relaciones/".$idcordinadior."-".$varfechaquincenaprox;
          $Rutacarpeta = "Expedientes/Relaciones/".$idcordinadior."-".$varfechaquincenaprox."/".$nombrearchivo;

          
          if(file_exists(public_path($Rutacarpeta)))
          {error_log('Directorio Creado');
          }else{File::makeDirectory($Rutacarpeta,0777,true,true); }

          $obtener_pagosIncompletos = $this->atrasosxdis($varfechaquincenaprox,$distri);
          $atraso = 0;
          foreach ($obtener_pagosIncompletos as $atr){
            $atraso = $atraso + $atr->atraso_proteccion;
          }
      
        
        $districon2porcientomas = DB::select("select * from tblhistorial_dosporciento where tblhistorial_dosporciento.fecha_relacion  = ?;",[$varfechaquincenaprox]);
        $districon2porcientomas =  collect($districon2porcientomas);
          
        $Upstatus = DB::select('update tblpagos_enc set descargo = "1" WHERE id_distribuidor = ? and fecha_relacion = ?;', [$distri,$varfechaquincenaprox]);
          
          set_time_limit(600); 
          
          $pdf = \PDF::setPaper('letter')->loadView('vales.PDF.fichaPago',compact('varfechaquincenaprox','bbva',
          'bancoAzteca','id','distribuidores','resultadoproc','varlistacom','barra','razon_social','captial_actual',
          'obtprocompletodisxrel','atraso','icono','marca_agua','saldosrelacion','districon2porcientomas'))
          ->save(public_path($Rutacarpeta) . "/RELACION_DV".$distri."_".$varfechaquincenaprox.".pdf");
        }
      }
        $zip = new \ZipArchive();
        //abrimos el archivo y lo preparamos para agregarle archivos
        $zip->open(public_path("Expedientes/Relaciones/".$idcordinadior."-".$varfechaquincenaprox.".zip"), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        
        //indicamos cual es la carpeta que se quiere comprimir
        $origen = realpath($rutaacomprimir);
        
        //Ahora usando funciones de recursividad vamos a explorar todo el directorio y a enlistar todos los archivos contenidos en la carpeta
        $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($origen),
                    \RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        //Ahora recorremos el arreglo con los nombres los archivos y carpetas y se adjuntan en el zip
        foreach ($files as $name => $file)
        {
            if (!$file->isDir())
            {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($origen) + 1);
        
                $zip->addFile($filePath, $relativePath);
            }
        }
        
        //Se cierra el Zip
        $zip->close();
        $fileName = "RELACIONESPAGO_CORD".$idcordinadior." FECHA ".$varfechaquincenaprox.".zip";
        $filePath = public_path()."/".$rutaacomprimir.".zip";

            File::deleteDirectory(public_path($rutaacomprimir.".zip",0777,true,true));
            File::deleteDirectory(public_path($rutaacomprimir,0777,true,true));

        if(!empty($fileName) && file_exists($filePath)){

            // Define headers
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header("Content-Disposition: attachment; filename=$fileName");
            header("Content-Type: application/zip");
            header("Content-Transfer-Encoding: binary");
                  
            
          // Read the file
          readfile($filePath);
          exit;
          
          
        }else{
            echo 'The file does not exist.';
        }
    } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
  }

  public function exportarZip(int $id_coordinador,string $fecha_relacion){
    try{

      $zip_file = "Expedientes/Relaciones/".$id_coordinador."-".$fecha_relacion.".zip";
      return response()->download(public_path($zip_file));
    
      
    } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
  }

  // ESTA FUNCION SE USA PARA EL HISTORIAL RALACIONES
  public function Historialfichasdownload(int $idcordinadior, Request $request){
    try{
      $bbva = 0;
      $bancoAzteca = 0;
      $varempresas =$this->razonSocial("Vales");
      foreach($varempresas as $empresa){
        $razon_social = $empresa->razon_social;
        $icono = $empresa->icono;
        $marca_agua = $empresa->marca_agua;
        $empresa= $empresa->empresa;
      }
      $fecha = Carbon::parse($request->get("fecha"));
      $varfechaquincenaprox = $fecha;
      $añoactual = $fecha->format('Y');
      $mesactual = $fecha->format('m');
      $diaactual =  $fecha->format('d');
      $districon2porcientomas = "NULL";
      if($diaactual == 15)
      {
        $varlistacom =  $this->obtenercomison1A();
      }
        
      if($diaactual == 30 || $diaactual == 31 || $diaactual == 28)
      {
        $varlistacom =  $this->obtenercomison2B();
      }

      $fecha = $request->get("fecha");
    
      $barra = new DNS1D(); 
      $idusuario=auth()->user()->id;
      $listadistirbuidores = $this->oobtenerrelacioneshistorial($idcordinadior,$fecha);
      foreach($listadistirbuidores as $listadis)
      {
        $captial_actual = 0;
        $distri = $listadis->id_distribuidor;
        $distribuidores = $this->obtenerdis($distri);
        $id = $distri;
        $resultadoproc = $this->encabezadorelacionesHistorial2($distri,$fecha);
        $saldosrelacion = $this->obtnersaldosxrelacion($distri);
        $captial_actual = 0;
        //Porcentajes de datos iniciales
        $porcen_comision = 0;
        $datosini = $this->obtenerdatosiniciales();
        foreach($datosini as $key){
          if($key->concepto == "COMISION"){ $porcen_comision = $key->cantidad; }
        }

        $refe = $this->obtenerreferencias($distri);
        foreach($refe as $listarefe){
          if($listarefe->id_fichapago == 2){
            $bbva = $listarefe->referencia;
          }
          if($listarefe->id_fichapago == 1){
            $bancoAzteca = $listarefe->referencia;
          }
        }

        $obtprocompletodisxrel=$this->obtprocompletodisxrel($distri,$fecha);
        foreach($obtprocompletodisxrel as $ltsobtdisxrel)
        {
       
            $captial_actual = ($ltsobtdisxrel->capital* $porcen_comision ) + $ltsobtdisxrel->interes;
        }


        $nombrearchivo ="RELACION_DV".$distri;
        $rutaacomprimir = "Expedientes/Relaciones/".$idcordinadior."-".$fecha;
        $Rutacarpeta = "Expedientes/Relaciones/".$idcordinadior."-".$fecha."/".$nombrearchivo;

        
        if(file_exists(public_path($Rutacarpeta)))
        {
          error_log('Directorio Creado');
        }else
        {
          //creamos la carpeta
          File::makeDirectory($Rutacarpeta,0777,true,true);
        }
        $obtener_pagosIncompletos = $this->obtenerAtrasos($distri);
        $atraso = 0;
        foreach ($obtener_pagosIncompletos as $atr){
          if($atr->fecha_relacion != $fecha){$atraso = $atraso + $atr->atraso;}
        }
        set_time_limit(600);
        $pdf = \PDF::setPaper('letter')->loadView('vales.PDF.fichaPago',compact('varfechaquincenaprox','bbva','bancoAzteca','id',
        'distribuidores','resultadoproc','varlistacom','barra','razon_social','atraso','icono','marca_agua','captial_actual',
        'saldosrelacion','districon2porcientomas'))
        ->save(public_path($Rutacarpeta) . "/RELACION_DV".$distri."_".$fecha.".pdf");
      }

        $zip = new \ZipArchive();
        //abrimos el archivo y lo preparamos para agregarle archivos
        $zip->open(public_path("Expedientes/Relaciones/".$idcordinadior."-".$fecha.".zip"), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        
        //indicamos cual es la carpeta que se quiere comprimir
        $origen = realpath($rutaacomprimir);
        
        //Ahora usando funciones de recursividad vamos a explorar todo el directorio y a enlistar todos los archivos contenidos en la carpeta
        $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($origen),
                    \RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        //Ahora recorremos el arreglo con los nombres los archivos y carpetas y se adjuntan en el zip
        foreach ($files as $name => $file)
        {
            if (!$file->isDir())
            {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($origen) + 1);
        
                $zip->addFile($filePath, $relativePath);
            }
        }
        
        //Se cierra el Zip
        $zip->close();
        $fileName = "RELACIONESPAGO_CORD".$idcordinadior." FECHA ".$fecha.".zip";
        $filePath = public_path()."/".$rutaacomprimir.".zip";

            File::deleteDirectory(public_path($rutaacomprimir.".zip",0777,true,true));
            File::deleteDirectory(public_path($rutaacomprimir,0777,true,true));

        if(!empty($fileName) && file_exists($filePath)){

            // Define headers
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header("Content-Disposition: attachment; filename=$fileName");
            header("Content-Type: application/zip");
            header("Content-Transfer-Encoding: binary");
                  
            
          // Read the file
          readfile($filePath);
          exit;
          
          
        }else{
            echo 'The file does not exist.';
        }
    } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
  }

  public function descargarrel(int $idcordinadior, string $fechaquincena){
    try{
      $varempresas =$this->razonSocial("Vales");
      foreach($varempresas as $empresa){
        $razon_social = $empresa->razon_social;
        $icono = $empresa->icono;
        $marca_agua = $empresa->marca_agua;
        $empresa= $empresa->empresa;
      }

        $date = Carbon::now();
        $fecha = $date;
        $idusuario=auth()->user()->id;

        // $varsucemp = $this->obtenersucursalxempleado($idusuario);
        $sum=0;
        $sucursal = "";
        $fecha = Carbon::parse($fechaquincena);
        $añoactual = $fecha->format('Y');
        $mesactual = $fecha->format('m');
        $diaactual =  $fecha->format('d');
        // $añoactual = 2024;
        // $mesactual = "04";
        // $diaactual =  9;

        if($diaactual >= 14 && $diaactual <= 18)
        {
          $varlistacom =  $this->obtenercomison1A();
        }elseif($diaactual >= 28  || $diaactual <= 3 )
        {
          $varlistacom =  $this->obtenercomison2B();
          
        }else{
          return back()->with("NO_APLICA_FECHA", "No");
        }
  
        $varfechaquincenaprox=$añoactual."-".$mesactual."-".$diaactual;
        $listadistirbuidores = $this->oobtenerrelacioneshistorial($idcordinadior,$varfechaquincenaprox);

        if($listadistirbuidores->isEmpty()){
          return back()->with("No_Se_Encontro","Errror");
        }
        else{
          
          foreach($listadistirbuidores as $listadis)
          {
            //aqui entraran todos los distribuidores
            $distri = $listadis->id_distribuidor;
            $distribuidores = $this->obtenerdis($distri);
            $resultadoproc=$this->encabezadorelacionesHistorial($distri,$varfechaquincenaprox);
            $rutacordinador = "Expedientes/Relaciones/".$idcordinadior."-".$varfechaquincenaprox;
            if(file_exists(public_path($rutacordinador)))
            {
              error_log('Ya Existe');
            }
            else{
              File::makeDirectory($rutacordinador,0777,true,true);
            }
            $nombrearchivo ="Relacion_DIS".$distri;
            $Rutacarpeta = "Expedientes/Relaciones/".$idcordinadior."-".$varfechaquincenaprox."/".$nombrearchivo;
            $rutaacomprimir = "Expedientes/Relaciones/".$idcordinadior."-".$varfechaquincenaprox;

      
            //$Rutacarpeta = "Expedientes/Relaciones/DIS_".$distri;
            if(file_exists(public_path($Rutacarpeta)))
            {
              error_log('Directorio Creado');
            }else
            {
              //creamos la carpeta
              File::makeDirectory($Rutacarpeta,0777,true,true);
            }
          
            $varPrestamos_valesdetDis = $this->obtenerPrestamos_detDis($distri);
            $Upstatus = DB::select('update tblpagos_enc set descargo = "1" WHERE id_distribuidor = ? and fecha_relacion = ?;', [$distri,$varfechaquincenaprox]);

            set_time_limit(600);
            $pdf = \PDF::setPaper('letter')->loadView('vales.PDF.relacionpagos',compact('resultadoproc','distribuidores','razon_social','icono','marca_agua','varPrestamos_valesdetDis'))->save(public_path($Rutacarpeta) . "/RELACIONPAGOS_DIS".$distri."_".$varfechaquincenaprox.".pdf");
          }
        }


        if(file_exists(public_path($rutaacomprimir."zip")))
        {
          $fileName =$rutaacomprimir."zip";
          $filePath =public_path()."/".$rutaacomprimir.".zip";
          if(!empty($fileName) && file_exists($filePath)){
              // Define headers
              header("Cache-Control: public");
              header("Content-Description: File Transfer");
              header("Content-Disposition: attachment; filename=$fileName");
              header("Content-Type: application/zip");
              header("Content-Transfer-Encoding: binary");
    
              // Read the file
              readfile($filePath);
              exit;
            }
        }
        else
        {
              $zip = new \ZipArchive();
              //abrimos el archivo y lo preparamos para agregarle archivos
              $zip->open(public_path("Expedientes/Relaciones/".$idcordinadior."-".$varfechaquincenaprox.".zip"), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
              
              //indicamos cual es la carpeta que se quiere comprimir
              $origen = realpath($rutaacomprimir);
              
              //Ahora usando funciones de recursividad vamos a explorar todo el directorio y a enlistar todos los archivos contenidos en la carpeta
              $files = new \RecursiveIteratorIterator(
                          new \RecursiveDirectoryIterator($origen),
                          \RecursiveIteratorIterator::LEAVES_ONLY
              );
              
              //Ahora recorremos el arreglo con los nombres los archivos y carpetas y se adjuntan en el zip
              foreach ($files as $name => $file)
              {
                if (!$file->isDir())
                {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($origen) + 1);
              
                    $zip->addFile($filePath, $relativePath);
                }
              }
              
              //Se cierra el Zip
              $zip->close();
              $fileName =  "RELACIONPAGOS-CORD".$idcordinadior."-FECHA".$varfechaquincenaprox.".zip";
              $filePath =public_path()."/".$rutaacomprimir.".zip";

              File::deleteDirectory(public_path($rutaacomprimir));
              File::deleteDirectory(public_path($rutaacomprimir.".zip"));

            if(!empty($fileName) && file_exists($filePath))
            {
                // Define headers
                header("Cache-Control: public");
                header("Content-Description: File Transfer");
                header("Content-Disposition: attachment; filename=$fileName");
                header("Content-Type: application/zip");
                header("Content-Transfer-Encoding: binary");
      
                // Read the file
                readfile($filePath);
                exit;
            }
            else
            {
                echo 'El Archivo no existe';
            }
        }        
    } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }    
  }

  public function indexHistorialRel(int $idcord){
    try{
      $varpantallas = $this->Traermenuenc();
      $varsubmenus = $this->Traermenudet();
      $idusuario=auth()->user()->id;
      $obtenehistorialxcoord = $this->obtenehistorialxcoord($idcord);
      return view('vales.Relaciones.historial',compact('varpantallas','varsubmenus','obtenehistorialxcoord'));
     
      
    } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
  }

  public function HistorialRealaciondownload(int $idcordinadior, Request $request){
    try{
      $varempresas =$this->razonSocial("Vales");
      foreach($varempresas as $empresa){
        $razon_social = $empresa->razon_social;
        $icono = $empresa->icono;
        $marca_agua = $empresa->marca_agua;
        $empresa= $empresa->empresa;
      }

            $date = Carbon::now();
            $fecha = $date;
            $idusuario=auth()->user()->id;
            $fecha = Carbon::parse($request->get("fecha"));
            $añoactual = $fecha->format('Y');
            $mesactual = $fecha->format('m');
            $diaactual =  $fecha->format('d');
            $sum=0;
            $sucursal = "";
       
            if($diaactual == 15)
            {
              $varlistacom =  $this->obtenercomison1();
            }
              
            if($diaactual == 30)
            {
              $varlistacom =  $this->obtenercomison2();
            }

            $varfechaquincenaprox = $request->get("fecha");

                
                    $listadistirbuidores = $this->oobtenerrelacioneshistorial($idcordinadior,$varfechaquincenaprox);
                    foreach($listadistirbuidores as $listadis)
                    {
                        //aqui entraran todos los distribuidores
                        $distri = $listadis->id_distribuidor;
                        $distribuidores = $this->obtenerdis($distri);
                        $resultadoproc=$this->encabezadorelacionesHistorial($distri,$varfechaquincenaprox);
                        $rutacordinador = "Expedientes/Relaciones/".$idcordinadior."-".$varfechaquincenaprox;
                        
                        if(file_exists(public_path($rutacordinador)))
                        {error_log('Ya Existe');}
                        else{File::makeDirectory($rutacordinador,0777,true,true); }

                        $nombrearchivo ="Relacion_DIS".$distri;
                        $Rutacarpeta = "Expedientes/Relaciones/".$idcordinadior."-".$varfechaquincenaprox."/".$nombrearchivo;
                        $rutaacomprimir = "Expedientes/Relaciones/".$idcordinadior."-".$varfechaquincenaprox;

                
                        //$Rutacarpeta = "Expedientes/Relaciones/DIS_".$distri;
                        if(file_exists(public_path($Rutacarpeta)))
                        { error_log('Directorio Creado');
                        }else{File::makeDirectory($Rutacarpeta,0777,true,true);}
                  
                        $varPrestamos_valesdetDis = $this->obtenerPrestamos_detDis($distri);
                        set_time_limit(600);
                        $pdf = \PDF::setPaper('letter')->loadView('vales.PDF.relacionpagos',compact('resultadoproc','distribuidores','razon_social','icono','marca_agua','varPrestamos_valesdetDis'))->save(public_path($Rutacarpeta) . "/RELACIONPAGOS_DIS".$distri."_".$varfechaquincenaprox.".pdf");
                    }

                    if(file_exists(public_path($rutaacomprimir."zip"))) {
                        $fileName =$rutaacomprimir."zip";
                        $filePath =public_path()."/".$rutaacomprimir.".zip";
                        if(!empty($fileName) && file_exists($filePath)){
                          // Define headers
                          header("Cache-Control: public");
                          header("Content-Description: File Transfer");
                          header("Content-Disposition: attachment; filename=$fileName");
                          header("Content-Type: application/zip");
                          header("Content-Transfer-Encoding: binary");
                
                          // Read the file
                          readfile($filePath);
                          exit;
                        }
                    }
                    else {
                        $zip = new \ZipArchive();
                        //abrimos el archivo y lo preparamos para agregarle archivos
                        $zip->open(public_path("Expedientes/Relaciones/".$idcordinadior."-".$varfechaquincenaprox.".zip"), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
                        
                        //indicamos cual es la carpeta que se quiere comprimir
                        $origen = realpath($rutaacomprimir);
                        
                        //Ahora usando funciones de recursividad vamos a explorar todo el directorio y a enlistar todos los archivos contenidos en la carpeta
                        $files = new \RecursiveIteratorIterator(
                                  new \RecursiveDirectoryIterator($origen),
                                  \RecursiveIteratorIterator::LEAVES_ONLY
                        );
                        
                        //Ahora recorremos el arreglo con los nombres los archivos y carpetas y se adjuntan en el zip
                        foreach ($files as $name => $file){
                          if (!$file->isDir()){
                              $filePath = $file->getRealPath();
                              $relativePath = substr($filePath, strlen($origen) + 1);
                        
                              $zip->addFile($filePath, $relativePath);
                          }
                        }
                      
                        //Se cierra el Zip
                        $zip->close();
                        $fileName =  "RELACIONPAGOS-CORD".$idcordinadior."-FECHA".$varfechaquincenaprox.".zip";
                        $filePath =public_path()."/".$rutaacomprimir.".zip";

                        $Rutacarpetas = "Expedientes/Relaciones/".$idcordinadior."-".$varfechaquincenaprox;
                        File::deleteDirectory(public_path($Rutacarpetas));
                        File::deleteDirectory(public_path($Rutacarpetas.".zip"));

                        if(!empty($fileName) && file_exists($filePath)){
                            // Define headers
                            header("Cache-Control: public");
                            header("Content-Description: File Transfer");
                            header("Content-Disposition: attachment; filename=$fileName");
                            header("Content-Type: application/zip");
                            header("Content-Transfer-Encoding: binary");
                  
                            // Read the file
                            readfile($filePath);
                            exit;
                        }else{
                            echo 'El Archivo no existe';
                        }
                    }        
    } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }    
  }


  // public function HistorialRealaciondownload(int $idcordinadior, Request $request){
  //   try{
  //     $varempresas =$this->razonSocial("Vales");
  //     foreach($varempresas as $empresa){
  //       $razon_social = $empresa->razon_social;
  //       $icono = $empresa->icono;
  //       $marca_agua = $empresa->marca_agua;
  //       $empresa= $empresa->empresa;
  //     }

  //           $date = Carbon::now();
  //           $fecha = $date;
  //           $idusuario=auth()->user()->id;
  //           $fecha = Carbon::parse($request->get("fecha"));
  //           $añoactual = $fecha->format('Y');
  //           $mesactual = $fecha->format('m');
  //           $diaactual =  $fecha->format('d');
  //           $sum=0;
  //           $sucursal = "";
       
  //           if($diaactual == 15)
  //           {
  //             $varlistacom =  $this->obtenercomison1();
  //           }
              
  //           if($diaactual == 30)
  //           {
  //             $varlistacom =  $this->obtenercomison2();
  //           }

  //           $varfechaquincenaprox = $request->get("fecha");
                    
  //           $rutaacomprimir = "Expedientes/Relaciones/".$idcordinadior."-".$varfechaquincenaprox;
  //           $rutaacomprimir =  public_path($rutaacomprimir.".zip");

  //                   if(file_exists($rutaacomprimir)) {
  //                       return response()->download($rutaacomprimir );
  //                   }
  //                   else {
  //                       $listadistirbuidores = $this->oobtenerrelacioneshistorial($idcordinadior,$varfechaquincenaprox);
  //                       foreach($listadistirbuidores as $listadis)
  //                       {
  //                           //aqui entraran todos los distribuidores
  //                           $distri = $listadis->id_distribuidor;
  //                           $distribuidores = $this->obtenerdis($distri);
  //                           $resultadoproc=$this->encabezadorelacionesHistorial($distri,$varfechaquincenaprox);
  //                           $rutacordinador = "Expedientes/Relaciones/".$idcordinadior."-".$varfechaquincenaprox;
                            
  //                           if(file_exists(public_path($rutacordinador)))
  //                           {error_log('Ya Existe');}
  //                           else{File::makeDirectory($rutacordinador,0777,true,true); }
    
  //                           $nombrearchivo ="Relacion_DIS".$distri;
  //                           $Rutacarpeta = "Expedientes/Relaciones/".$idcordinadior."-".$varfechaquincenaprox."/".$nombrearchivo;
  //                           $rutaacomprimir = "Expedientes/Relacionexs/".$idcordinadior."-".$varfechaquincenaprox."/".$nombrearchivo;
    
                    
  //                           //$Rutacarpeta = "Expedientes/Relaciones/DIS_".$distri;
  //                           if(file_exists(public_path($Rutacarpeta)))
  //                           { error_log('Directorio Creado');
  //                           }else{File::makeDirectory($Rutacarpeta,0777,true,true);}
                      
  //                           $varPrestamos_valesdetDis = $this->obtenerPrestamos_detDis($distri);
  //                           set_time_limit(600);
  //                           $pdf = \PDF::setPaper('letter')->loadView('vales.PDF.relacionpagos',compact('resultadoproc','distribuidores','razon_social','icono','marca_agua','varPrestamos_valesdetDis'))->save(public_path($Rutacarpeta) . "/RELACIONPAGOS_DIS".$distri."_".$varfechaquincenaprox.".pdf");
  //                       }
                        
  //                       $zip = new \ZipArchive();
  //                       //abrimos el archivo y lo preparamos para agregarle archivos
  //                       $zip->open(public_path("Expedientes/Relaciones/".$idcordinadior."-".$varfechaquincenaprox.".zip"), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
                        
  //                       //indicamos cual es la carpeta que se quiere comprimir
  //                       $origen = realpath($rutaacomprimir);
                        
  //                       //Ahora usando funciones de recursividad vamos a explorar todo el directorio y a enlistar todos los archivos contenidos en la carpeta
  //                       $files = new \RecursiveIteratorIterator(
  //                                 new \RecursiveDirectoryIterator($origen),
  //                                 \RecursiveIteratorIterator::LEAVES_ONLY
  //                       );
                        
  //                       //Ahora recorremos el arreglo con los nombres los archivos y carpetas y se adjuntan en el zip
  //                       foreach ($files as $name => $file){
  //                         if (!$file->isDir()){
  //                             $filePath = $file->getRealPath();
  //                             $relativePath = substr($filePath, strlen($origen) + 1);
                        
  //                             $zip->addFile($filePath, $relativePath);
  //                         }
  //                       }
                      
  //                       //Se cierra el Zip
  //                       $zip->close();
  //                       $fileName =  "RELACIONPAGOS-CORD".$idcordinadior."-FECHA".$varfechaquincenaprox.".zip";
  //                       $filePath =public_path()."/".$rutaacomprimir.".zip";

  //                       // File::deleteDirectory(public_path($rutaacomprimir));
  //                       // File::deleteDirectory(public_path($rutaacomprimir.".zip"));

  //                       if(!empty($fileName) && file_exists($filePath)){
  //                           // Define headers
  //                           header("Cache-Control: public");
  //                           header("Content-Description: File Transfer");
  //                           header("Content-Disposition: attachment; filename=$fileName");
  //                           header("Content-Type: application/zip");
  //                           header("Content-Transfer-Encoding: binary");
                  
  //                           // Read the file
  //                           readfile($filePath);
  //                           exit;
  //                       }else{
  //                           echo 'El Archivo no existe';
  //                       }
  //                   }        
  //   } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }    
  // }
}