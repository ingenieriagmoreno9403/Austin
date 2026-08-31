<?php

namespace App\Http\Controllers;

use App\Models\Empresas;
use App\Models\Sucursales;
use App\Models\usuario_acciones;
use App\Traits\NotificacionesTrait;
use Illuminate\Http\Request;
use App\Traits\MenuTrait;
use App\Traits\GlobalTraits;
use App\Traits\SistemasTraits;
use App\Traits\DatosimpleTraits;
use App\Models\historial_cuentas;
use App\Models\historial_cajas;
use App\Models\cuentas;
use App\Models\Cajas;
use Carbon\Carbon;
use App\Models\Notificacion;
use App\Exports\MovimientosHistorial;
use Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;
use DB;
use App\Models\arqueocajas;
use App\Models\arqueorelacion_efect;


class MovimientosController extends Controller
{
    use MenuTrait;
    use DatosimpleTraits;
    use SistemasTraits;
    use GlobalTraits;
    use NotificacionesTrait;

    public function __construct(){
        $this->middleware('auth');
    }

    public function indexMovimientos(){
        try{
            $date = Carbon::now();
            $fechaHoy = $date->format('Y-m-d');
            $id_user = auth()->user()->id;
            $modulo = "movimientos_dinero";
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();
            $varValidaPermisoCuentas =  $this->validaPermisoCuentas($id_user,$modulo);
            $varValidaResponsableCaja =  $this->validaResponsableCaja($id_user);

            if($varValidaPermisoCuentas->isEmpty()){$varValidaPermisoCuentas = "no";
            }else{$varValidaPermisoCuentas =  $this->validaPermisoCuentas($id_user,$modulo);}

            if($varValidaResponsableCaja->isEmpty()){$varValidaResponsableCaja = "no";
            }else{$varValidaResponsableCaja =  $this->validaResponsableCaja($id_user);}

            $total_ingresos =  $this->total_ingresos();
            $total_egresos =  $this->total_egresos();
            
            $cuentas = DB::table('tblcuentas')
            ->where('status', 'A')
            ->count();

            $cajas = DB::table('tblcajas')
            ->where('status', 'A')
            ->where('tipo', 'caja')
            ->count();

            $cajas_chicas = DB::table('tblcajas')
            ->where('status', 'A')
            ->where('tipo', '<>', 'caja')
            ->count();

            $movmientos_cuenta = DB::table('tblmovimientos_cuentas')
            ->where('fecha', $fechaHoy)
            ->count();

            $movmientos_cajas = DB::table('tblmovimientos_cajas')
            ->where('fecha', $fechaHoy)
            ->count();

            $pendientes = collect(DB::select('select tblcajas.nombre, tblarqueocajas.* from tblarqueocajas 
            inner join tblcajas on tblarqueocajas.id_caja = tblcajas.id
            where estado = "Creado";'));
            $solicitudes = collect(DB::select('select tblcajas.nombre, tblarqueocajas.*  from tblarqueocajas
            inner join tblcajas on tblarqueocajas.id_caja = tblcajas.id 
            where estado = "En Espera" and comentario is not null;'));

            // Consulta para obtener ingresos por día de los últimos 7 días
            $ingresosPorDia = DB::select("
                SELECT fecha, SUM(ingreso) AS total_ingresos
                FROM (
                    SELECT ingreso, fecha
                    FROM tblmovimientos_cuentas 
                    WHERE ingreso > 0
                      AND fecha BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND CURDATE()

                    UNION ALL

                    SELECT ingreso, fecha
                    FROM tblmovimientos_cajas 
                    WHERE ingreso > 0
                      AND fecha BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND CURDATE()
                ) AS ingresos_unidos
                GROUP BY fecha
                ORDER BY fecha
            ");

            // Consulta para obtener egresos por día de los últimos 7 días
            $egresosPorDia = DB::select("
                SELECT fecha, SUM(egreso) AS total_egresos
                FROM (
                    SELECT egreso, fecha
                    FROM tblmovimientos_cuentas 
                    WHERE egreso > 0
                      AND fecha BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND CURDATE()

                    UNION ALL

                    SELECT egreso, fecha
                    FROM tblmovimientos_cajas 
                    WHERE egreso > 0
                      AND fecha BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND CURDATE()
                ) AS egresos_unidos
                GROUP BY fecha
                ORDER BY fecha
            ");

            // Preparar datos para la gráfica
            $datosGrafica = [];
            $fechas = [];
            $ingresos = [];
            $egresos = [];

            // Generar array de los últimos 7 días
            $nombresDias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
            
            for ($i = 6; $i >= 0; $i--) {
                $fecha = Carbon::now()->subDays($i)->format('Y-m-d');
                $diaSemana = Carbon::now()->subDays($i)->dayOfWeek;
                $fechas[] = $nombresDias[$diaSemana];
                
                // Buscar ingresos para esta fecha
                $ingresoDia = collect($ingresosPorDia)->firstWhere('fecha', $fecha);
                $ingresos[] = $ingresoDia ? (float)$ingresoDia->total_ingresos : 0;
                
                // Buscar egresos para esta fecha
                $egresoDia = collect($egresosPorDia)->firstWhere('fecha', $fecha);
                $egresos[] = $egresoDia ? (float)$egresoDia->total_egresos : 0;
            }

            // Calcular totales de la semana
            $totalIngresosSemana = array_sum($ingresos);
            $totalEgresosSemana = array_sum($egresos);
            $balanceSemana = $totalIngresosSemana - $totalEgresosSemana;
            
            $datosGrafica = [
                'fechas' => $fechas,
                'ingresos' => $ingresos,
                'egresos' => $egresos,
                'totalIngresos' => $totalIngresosSemana,
                'totalEgresos' => $totalEgresosSemana,
                'balance' => $balanceSemana
            ];

             $permisos1 = $this->forpermisos('ver_detalles_movcuentas');
            
            return view('Tesoreria.Movimientos.index',compact('varpantallas','varsubmenus',
            'varValidaPermisoCuentas','varValidaResponsableCaja',
            'cuentas','cajas','cajas_chicas','movmientos_cuenta','movmientos_cajas','datosGrafica',
            'solicitudes','pendientes','permisos1'));
        } catch(\Illuminate\Database\QueryException $ex){  return back()->with("errorBD","no guardado correctamente"); }
    }

    //MANEJO

    public function indexManejo(string $tipo){
        try{
            $id_user = auth()->user()->id;
            
            $modulo = "movimientos_dinero";
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();
            $obtenerCuentas =   $this->obtenercuentasActivas();
            $obtenerCajas =   $this->obtenerCajas();
            $permisos1 = $this->forpermisos('movimientos_transferir');
            $permisos2 = $this->forpermisos('movimientos_consultar');
            $permisos3 = $this->forpermisos('movimientos_exportar');
            
            $permisos4 = $this->forpermisos('movimientos_ingresar');
            $permisos5 = $this->forpermisos('movimientos_retirar');
            $date = Carbon::now();
            $fechaHoy = $date->format('Y-m-d');
            $fechaHace7Dias = $date->subDays(7)->format('Y-m-d');
            $fecha = $date->format('Y-m-d');
            $mes = $date->format('m');
            $año = $date->format('Y');
            $mes = $date->format('m');
            $notificaciones = $this->obtenerNotificacionesporFecha($fechaHoy, $fechaHace7Dias);
            $totalnotis = $notificaciones->count();
            $varGastos =  $this->obtenerGastos();
    
            if($tipo == "Cuentas"){$varManejo =  $this->obtenerManejoCuentas($id_user, $modulo);
            }elseif($tipo == "Cajas"){$varManejo =  $this->obtenerManejoCaja($id_user, $modulo);}
            else{$varManejo =  $this->obtenerManejoCajaChica($id_user, $modulo);}

            return view('Tesoreria.Movimientos.Manejo.manejo',compact('varpantallas','varsubmenus','obtenerCuentas','obtenerCajas','varManejo','tipo',
        'permisos1','permisos2','permisos3','permisos4','permisos5','mes','varGastos', 'notificaciones', 'totalnotis'));
        } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    }

    public function traspasos(string $tipo, int $id, Request $request){
        $date = Carbon::now();
        $fecha = $date->format('Y-m-d');
        $tipo1 = $tipo;
        $tipo2 = $request->get('tipo_transferencia');
        $id1 = $id;

        if($tipo2 == "cuenta"){
            $id2 = $request->get('transferenciaCuenta');
        }elseif($tipo2 == "caja"){
            $id2 = $request->get('transferenciaCaja');
        }elseif($tipo2 == "caja_chica"){
            $id2 = $request->get('transferenciaCajaChica');
        }else{
            $id2 = 0;
        }

        try{
            if($id2 != 0){
                    if($tipo1 == "Cuentas"){

                        $varobtenercuentas = $this->obtenercuentasPrincipales($id1);
                        foreach($varobtenercuentas as $varobtenercuenta){
                            $saldo1 = $varobtenercuenta->saldo_actual;
                            $nombre1 = $varobtenercuenta->descripcion;
                        }

                    }else{
                        $varcajas = $this->obtenerCajasxId($id1);
                        foreach ($varcajas as $cajas){
                            $saldo1 = $cajas->saldo_actual;
                            $nombre1 = $cajas->nombre;
                        }
                    }

                    if($tipo2 == "cuenta"){

                        $varobtenercuentas = $this->obtenercuentasPrincipales($id2);
                        foreach($varobtenercuentas as $varobtenercuenta){
                            $saldo2 = $varobtenercuenta->saldo_actual;
                            $nombre2 = $varobtenercuenta->descripcion;
                        }

                    }else{
                        $varcajas = $this->obtenerCajasxId($id2);
                        foreach ($varcajas as $cajas){
                            $saldo2 = $cajas->saldo_actual;
                            $nombre2 = $cajas->nombre;
                        }
                    }
                    

                    if($saldo1 >= $request->get('saldo_trasferir')){

                        $saldoEgresa = $saldo1 - $request->get('saldo_trasferir');

                        if($tipo1 == "Cuentas"){$cuenta1 = cuentas::find($id1);}else{$cuenta1 = Cajas::find($id1);}
                        $cuenta1->saldo_actual = $saldoEgresa;
                        $cuenta1->updated_by=auth()->user()->name;
                        $cuenta1->save();


                        $saldoIngresa = $saldo2 + $request->get('saldo_trasferir');
                        if($tipo2 == "cuenta"){$cuenta2 = cuentas::find($id2);}else{ $cuenta2 = Cajas::find($id2);}
                        $cuenta2->saldo_actual = $saldoIngresa;
                        $cuenta2->updated_by=auth()->user()->name;
                        $cuenta2->save();

                        if($cuenta1->save() && $cuenta2->save()){

                            if($tipo1 == "Cuentas"){
                            $historial1 = new historial_cuentas();
                            $historial1->id_cuenta = $id1;
                            }else{
                            $historial1 = new historial_cajas();
                            $historial1->id_caja = $id1;
                            }
                            $historial1->id_empleado =auth()->user()->idempleado;
                            $historial1->estado = "A";
                            $historial1->tipo_movimiento = "TRANSFERENCIA";
                            $historial1->concepto = "TRANSFERENCIA DESDE TESORERIA A CUENTA".$nombre2;
                            $historial1->descripcion = $request->get('descripcion');
                            $historial1->responsable = "EMPLEADO #".auth()->user()->idempleado;
                            $historial1->ingreso = 0;
                            $historial1->egreso = $request->get('saldo_trasferir');
                            $historial1->saldo =  $saldoEgresa;
                            $historial1->numero_referencia = $id2;
                            if($tipo2 == "cuenta"){
                                $historial1->tipo_referencia = "cuenta";
                            }else{
                                $historial1->tipo_referencia = "caja";
                            }
                            $historial1->numero_poliza = 0;
                            $historial1->fecha = $fecha;
                            $historial1->created_by = auth()->user()->name;
                            $historial1->save();

                            if($tipo1 == "Cuentas"){
                                $movcuenta =$this->obtenerultimomovcuenta();
                                foreach($movcuenta as $cue){$movId = $cue->id;}
                                $poliza = historial_cuentas::find($movId);
                                $poliza->numero_poliza = "CU00".$movId;
                                $poliza->updated_by = auth()->user()->name;
                                $poliza->save();
                            }else{
                                $movcaja =$this->obtenerultimomovcaja();
                                foreach($movcaja as $caj){$movId = $caj->id;}
                                $poliza = historial_cajas::find($movId);
                                $poliza->numero_poliza = "CJ00".$movId;
                                $poliza->updated_by = auth()->user()->name;
                                $poliza->save();
                            }

                            if($tipo2 == "cuenta"){
                            $historial2 = new historial_cuentas(); 
                            $historial2->id_cuenta = $id2;
                            }else{
                            $historial2 = new historial_cajas();
                            $historial2->id_caja = $id2;
                            }
                            $historial2->id_empleado =auth()->user()->idempleado;
                            $historial2->estado = "A";
                            $historial2->tipo_movimiento = "INGRESO";
                            $historial2->concepto = "INGRESO DE SALDO POR TRANSFERENCIA DE CUENTA ".$nombre1;
                            $historial2->descripcion = $request->get('descripcion');
                            $historial2->responsable = "EMPLEADO #".auth()->user()->idempleado;
                            $historial2->ingreso = $request->get('saldo_trasferir');
                            $historial2->egreso = 0;
                            $historial2->saldo =  $saldoIngresa;
                            $historial2->numero_referencia = $id1;
                            if($tipo1 == "Cuentas"){
                                $historial2->tipo_referencia = "cuenta";
                            }else{
                                $historial2->tipo_referencia = "caja";
                            }
                            $historial2->numero_poliza = 0;
                            $historial2->fecha = $fecha;
                            $historial2->created_by=auth()->user()->name;
                            $historial2->save();

                            if($tipo2 == "cuenta"){
                                $movcuenta =$this->obtenerultimomovcuenta();
                                foreach($movcuenta as $cue){$movId = $cue->id;}
                                $poliza = historial_cuentas::find($movId);
                                $poliza->numero_poliza = "CU00".$movId;
                                $poliza->updated_by = auth()->user()->name;
                                $poliza->save();
                            }else{
                                $movcaja =$this->obtenerultimomovcaja();
                                foreach($movcaja as $caj){$movId = $caj->id;}
                                $poliza = historial_cajas::find($movId);
                                $poliza->numero_poliza = "CJ00".$movId;
                                $poliza->updated_by = auth()->user()->name;
                                $poliza->save();
                            }

                            return back()->with("success","¡Se guardaron los cambios correctamente!");
                        }else{
                            return back()->with("warning","No se logro");
                        }
                    }else{
                        return back()->with("warningSaldo","No se logro");
                    }
            }else{
                return back()->with("warningCuenta","No se logro");
            }
       } catch(\Illuminate\Database\QueryException $ex){return back()->with("warningBD","No se logro"); }
    }

    public function ingreso(string $tipo, int $id, Request $request){
        $date = Carbon::now();
        $fecha = $date->format('Y-m-d');
        $checararqueo = $this->checararqueoaut();
        if($checararqueo->isEmpty()){
            $arqueo = 0;
        }else{
            foreach($checararqueo as $item){$arqueo = $item->arqueo;}
        }

        if($arqueo == 0){
            try{
                if($tipo == "Cuentas"){
                    $cuenta = $id;
                    $varobtenercuentas =$this->obtenercuentasPrincipales($cuenta);
                    foreach($varobtenercuentas as $varobtenercuenta){$saldoCuenta = $varobtenercuenta->saldo_actual;$nomCuenta = $varobtenercuenta->descripcion;}

                        $saldoFinal = $saldoCuenta + $request->get('saldo_ingresar');
                        $historialcuent = new  historial_cuentas();
                        $historialcuent->id_cuenta = $cuenta;
                        $historialcuent->id_empleado =auth()->user()->idempleado;
                        $historialcuent->estado = "A";
                        $historialcuent->tipo_movimiento = "INGRESO";
                        $historialcuent->concepto = "INGRESO DESDE MODULO MOVIMIENTOS";
                        $historialcuent->descripcion = $request->get('descripcion');
                        $historialcuent->responsable = "EMPLEADO #".auth()->user()->idempleado;
                        $historialcuent->ingreso = $request->get('saldo_ingresar');
                        $historialcuent->egreso = 0;
                        $historialcuent->saldo =  $saldoFinal;
                        $historialcuent->numero_referencia = $cuenta;
                        $historialcuent->tipo_referencia = "cuenta";
                        $historialcuent->numero_poliza = 0;
                        $historialcuent->fecha = $fecha;
                        $historialcuent->created_by = auth()->user()->name;
                        $historialcuent->save();
                    
                    if($historialcuent->save()){

                        $movcuenta =$this->obtenerultimomovcuenta();
                        foreach($movcuenta as $cue){$movId = $cue->id;}

                        $poliza = historial_cuentas::find($movId);
                        $poliza->numero_poliza = "CU00".$movId;
                        $poliza->updated_by = auth()->user()->name;
                        $poliza->save();

                        $cuenta = cuentas::find($cuenta);
                        $cuenta->saldo_actual = $saldoFinal;
                        $cuenta->updated_by = auth()->user()->name;
                        $cuenta->save();

                        return back()->with("success","¡Se guardaron los cambios correctamente!");
                    }else{ return back()->with("warning","No se logro");} 

                }else{
                    $caja = $id;
                    $varcajas = $this->obtenerCajasxId($caja);
                    foreach ($varcajas as $cajas){$saldoCaja = $cajas->saldo_actual;$nombreCaja = $cajas->nombre;}

                        $saldoFinal = $saldoCaja + $request->get('saldo_ingresar');
                        $historialcuent = new  historial_cajas();
                        $historialcuent->id_caja = $caja;
                        $historialcuent->id_empleado =auth()->user()->idempleado;
                        $historialcuent->estado = "A";
                        $historialcuent->tipo_movimiento = "INGRESO";
                        $historialcuent->concepto = "INGRESO DESDE MODULO MOVIMIENTOS";
                        $historialcuent->descripcion = $request->get('descripcion');
                        $historialcuent->responsable = "EMPLEADO #".auth()->user()->idempleado;
                        $historialcuent->ingreso = $request->get('saldo_ingresar');
                        $historialcuent->egreso = 0;
                        $historialcuent->saldo =  $saldoFinal;
                        $historialcuent->numero_referencia = $caja;
                        $historialcuent->tipo_referencia = "caja";
                        $historialcuent->numero_poliza = 0;
                        $historialcuent->fecha = $fecha;
                        $historialcuent->created_by = auth()->user()->name;
                        $historialcuent->save();
                    
                    if($historialcuent->save()){

                        $movcaja =$this->obtenerultimomovcaja();
                        foreach($movcaja as $caj){$movId = $caj->id;}

                        $poliza = historial_cajas::find($movId);
                        $poliza->numero_poliza = "CJ00".$movId;
                        $poliza->updated_by = auth()->user()->name;
                        $poliza->save();

                        $Cajas = Cajas::find($caja);
                        $Cajas->saldo_actual = $saldoFinal;
                        $Cajas->updated_by = auth()->user()->name;
                        $Cajas->save();

                        return back()->with("success","¡Se guardaron los cambios correctamente!");
                    }else{ return back()->with("warning","No se logro");}  
                }
            } catch(\Illuminate\Database\QueryException $ex){return back()->with("warningBD","No se logro"); }
        }else{
            return back()->with("arqueoNoAut","No se logro");
        }
    }

    public function retirar(string $tipo, int $id, Request $request){
        $date = Carbon::now();
        $fecha = $date->format('Y-m-d');

        try{
            if($tipo == "Cuentas"){
                $cuenta = $id;
                $varobtenercuentas =$this->obtenercuentasPrincipales($cuenta);
                foreach($varobtenercuentas as $varobtenercuenta){$saldoCuenta = $varobtenercuenta->saldo_actual;$nomCuenta = $varobtenercuenta->descripcion;}
        
                $saldoFinal = $saldoCuenta - $request->get('saldo_retirar');
                if($saldoFinal > 0){
                    $historialcuent = new  historial_cuentas();
                    $historialcuent->id_cuenta = $cuenta;
                    $historialcuent->id_empleado =auth()->user()->idempleado;
                    $historialcuent->estado = "A";
                    $historialcuent->tipo_movimiento = "RETIRO";
                    $historialcuent->concepto = "RETIRO DESDE MODULO MOVIMIENTOS";
                    $historialcuent->descripcion = $request->get('descripcion');
                    $historialcuent->responsable = "EMPLEADO #".auth()->user()->idempleado;
                    $historialcuent->ingreso = 0;
                    $historialcuent->egreso = $request->get('saldo_retirar');
                    $historialcuent->saldo =  $saldoFinal;
                    $historialcuent->numero_referencia = $cuenta;
                    $historialcuent->tipo_referencia = "cuenta";
                    $historialcuent->numero_poliza = 0;
                    $historialcuent->fecha = $fecha;
                    $historialcuent->created_by = auth()->user()->name;
                    $historialcuent->save();
                    
                    if($historialcuent->save()){
                        $movcuenta =$this->obtenerultimomovcuenta();
                        foreach($movcuenta as $cue){$movId = $cue->id;}

                        $poliza = historial_cuentas::find($movId);
                        $poliza->numero_poliza = "CU00".$movId;
                        $poliza->updated_by = auth()->user()->name;
                        $poliza->save();

                        $cuenta = cuentas::find($cuenta);
                        $cuenta->saldo_actual = $saldoFinal;
                        $cuenta->updated_by = auth()->user()->name;
                        $cuenta->save();
                        return back()->with("success","¡Se guardaron los cambios correctamente!");

                    }else{ return back()->with("warning","No se logro");}    

                }else{return back()->with("warningCantidadEgreso","No se logro");}

            }else{
                $caja = $id;
                $varcajas = $this->obtenerCajasxId($caja);
                foreach ($varcajas as $cajas){$saldoCaja = $cajas->saldo_actual;$nombreCaja = $cajas->nombre;}

                $saldoFinal = $saldoCaja - $request->get('saldo_retirar');
                if($saldoFinal > 0){
                    $historialcuent = new  historial_cajas();
                    $historialcuent->id_caja = $caja;
                    $historialcuent->id_empleado =auth()->user()->idempleado;
                    $historialcuent->estado = "A";
                    $historialcuent->tipo_movimiento = "RETIRO";
                    $historialcuent->concepto = "RETIRO DESDE MODULO MOVIMIENTOS";
                    $historialcuent->descripcion = $request->get('descripcion');
                    $historialcuent->responsable = "EMPLEADO #".auth()->user()->idempleado;
                    $historialcuent->ingreso = 0;
                    $historialcuent->egreso = $request->get('saldo_retirar');
                    $historialcuent->saldo =  $saldoFinal;
                    $historialcuent->numero_referencia = $caja;
                    $historialcuent->tipo_referencia = "caja";
                    $historialcuent->numero_poliza = 0;
                    $historialcuent->fecha = $fecha;
                    $historialcuent->created_by = auth()->user()->name;
                    $historialcuent->save();
                    
                    if($historialcuent->save()){
                        $movcaja =$this->obtenerultimomovcaja();
                        foreach($movcaja as $caj){$movId = $caj->id;}

                        $poliza = historial_cajas::find($movId);
                        $poliza->numero_poliza = "CJ00".$movId;
                        $poliza->updated_by = auth()->user()->name;
                        $poliza->save();

                        $Cajas = Cajas::find($caja);
                        $Cajas->saldo_actual = $saldoFinal;
                        $Cajas->updated_by = auth()->user()->name;
                        $Cajas->save();
                        return back()->with("success","¡Se guardaron los cambios correctamente!");

                    }else{ return back()->with("warning","No se logro");}    

                }else{return back()->with("warningCantidadEgreso","No se logro");}
            }
        } catch(\Illuminate\Database\QueryException $ex){return back()->with("warningBD","No se logro"); }
    }

    public function consultarMovimientos(Request $request, string $tipo, int $id)
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
    
        $tipo_fecha = $request->get('tipo_fecha', 'aplicacion');
        $fecha_inicio_request = $request->get('fecha_inicio');
        $fecha_fin_request = $request->get('fecha_fin');
        $fecha_aplicacion_inicio = $request->get('fecha_aplicacion_inicio');
        $fecha_aplicacion_fin = $request->get('fecha_aplicacion_fin');
        $fecha_movimiento_inicio = $request->get('fecha_movimiento_inicio');
        $fecha_movimiento_fin = $request->get('fecha_movimiento_fin');

        if ($tipo_fecha === 'movimiento') {
            $fecha_inicio = $fecha_movimiento_inicio ?? $fecha_inicio_request;
            $fecha_fin = $fecha_movimiento_fin ?? $fecha_fin_request;
        } else {
            $fecha_inicio = $fecha_aplicacion_inicio ?? $fecha_inicio_request;
            $fecha_fin = $fecha_aplicacion_fin ?? $fecha_fin_request;
        }

        if (is_null($fecha_inicio) || is_null($fecha_fin)) {
            $fecha_inicio = Carbon::now()->startOfMonth()->format('Y-m-d');
            $fecha_fin = Carbon::now()->endOfMonth()->format('Y-m-d');
        }

        $fecha_aplicacion_inicio = $fecha_aplicacion_inicio ?: $fecha_inicio;
        $fecha_aplicacion_fin = $fecha_aplicacion_fin ?: $fecha_fin;
        $fecha_movimiento_inicio = $fecha_movimiento_inicio ?: $fecha_inicio;
        $fecha_movimiento_fin = $fecha_movimiento_fin ?: $fecha_fin;
    
        $id_sucursal = $request->get('sucursal');
        $id_empresa = $request->get('empresa');
        $tipo_movimiento = $request->get('tipo_movimiento', 'TODOS');

        if ($id_sucursal == 0) {
            $id_sucursal = null;
        }
        if ($id_empresa == 0) {
            $id_empresa = null;
        }
    
        $permisos1 = $this->forpermisos('movimientos_exportar');
        $tipos_movimiento = [
            'APERTURA',
            'INGRESO',
            'PAGO',
            'DEVOLUCION',
            'CANCELACION',
            'TRANSFERENCIA',
            'ENTREGA',
            'CARGO',
            'GASTO',
            'DESEMBOLSO',
            'RETIRO'
        ];
        $sucursales = Sucursales::get();
        $empresas = Empresas::where('id', '!=', 0)->get();
    
        try {
            if ($tipo == "Cuentas") {
                $obtenerHistorial = $this->obtenerHistorialCuentas($id, $fecha_inicio, $fecha_fin, $tipo_fecha, $tipo_movimiento);
                $obtenerInfo = $this->obtenercuentasPrincipales($id);
            } else {
                $obtenerHistorial = $this->obtenerHistorialCajas($id, $fecha_inicio, $fecha_fin, $tipo_fecha, $tipo_movimiento);
                $obtenerInfo = $this->obtenerCajasxId($id);
            }

            return view('Tesoreria.Movimientos.Manejo.consultar_movimientos', compact(
                'varpantallas', 'varsubmenus', 'obtenerHistorial',
                'obtenerInfo', 'id', 'tipo', 'fecha_inicio', 'fecha_fin', 'tipo_fecha',
                'fecha_aplicacion_inicio', 'fecha_aplicacion_fin', 'fecha_movimiento_inicio', 'fecha_movimiento_fin',
                'permisos1', 'sucursales', 'empresas', 'tipo_movimiento', 'tipos_movimiento'
            ));
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "No se logro");
        }
    }

    public function exportarConsultaMovimientos(Request $request, string $tipo, int $id){
        $nombre = $request->get('nombre');
        $fecha_inicio = $request->get('fecha_inicio');
        $fecha_fin  = $request->get('fecha_fin');
        $empresa  = $request->get('empresa');
        $tipo_fecha = $request->get('tipo_fecha', 'aplicacion');
        $tipo_movimiento = $request->get('tipo_movimiento', 'TODOS');

        try{
            return Excel::download(new MovimientosHistorial($id, $fecha_inicio, $fecha_fin, $nombre ,$tipo, $empresa, $tipo_fecha, $tipo_movimiento ), 'HISTORIAL DE MOVIMIENTOS '.$nombre.' '.$fecha_inicio.'-'.$fecha_fin.'.xlsx');
        } catch(\Illuminate\Database\QueryException $ex){return back()->with("warningBD","No se logro"); }

    }

    public function downloadPoliza(Request $request, string $tipo, string $id){
        $date = Carbon::now();
        $fecha = $date->format('d-m-Y');
        $tipoMovimiento = $tipo;
        $no_poliza = $id;
        $id1 = $request->get('id1');
        $nombre1 = $request->get('nombre1');
        $id2 = $request->get('id2');
        $tipo1 = $request->get('tipo1');
        $tipo2 = $request->get('tipo2');
        $saldo = $request->get('saldo');
        $saldo_inicial = $request->get('saldo_inicial');
        $saldo_actual = $request->get('saldo_actual');
        $concepto = $request->get('concepto');
        $descripcion = $request->get('descripcion');
        $usuario = $request->get('usuario');
        $id_empleado = $request->get('id_empleado');
        $hecho_por = $usuario;
        $idempresa = $request->get('empresa');
        $pertenece = $request->get('pertenece');
        $idmov = $request->get('idmov');
        $varporcent = "NULL";
        $nombre2 = "";
        try{
            if (!empty($id_empleado) && (int) $id_empleado > 0) {
                foreach ($this->obtenerempleadoxid((int) $id_empleado) as $empleado) {
                    $nombreEmpleado = trim(preg_replace('/\s+/', ' ', $empleado->Nombre ?? ''));
                    if ($nombreEmpleado !== '') {
                        $hecho_por = $nombreEmpleado;
                        break;
                    }
                }
            }

            $obtenerempresa =  $this->obtenerempresaxid($idempresa);
            foreach($obtenerempresa as $varemp){
                $nombre_empresa = $varemp->nombre_empresa;
                $icono = $varemp->icono;
                $marca_agua = $varemp->marca_agua;
                $rfc = $varemp->rfc;
            }           

            if($concepto == "SUELDO" || $concepto == "S EFECTIVO" || $concepto == "S EXCEDENTE" || $concepto == "GRUPAL"){
                
                if($tipo1 == "Cajas" || $tipo1 == "Cajas Chicas"  || $tipo1 == "caja" || $tipo1 == "caja_chica"){
                    $varporcent = $this->obtenerMovCajas($idmov);                    
                }else{
                    $varporcent = $this->obtenerMovCuentas($idmov);
                }
                

                $motivo = substr($request->get('descripcion'),5,6);
                if($motivo == "FISCAL"){
                    $nomIdmov = substr($request->get('descripcion'),13,100);
                    $varpagonom =  $this->obtenerempleadoxnomina($nomIdmov);

                    $pdf = \PDF::setPaper('letter')->loadView('Tesoreria.Movimientos.PDF.polizaFis',compact('fecha','tipoMovimiento',
                    'no_poliza','id1','nombre1','nombre2','tipo1','tipo2','saldo','saldo_inicial','saldo_actual','concepto',
                    'descripcion','usuario','hecho_por','nombre_empresa','icono','marca_agua','pertenece','varporcent','varpagonom','rfc'));
                    return $pdf->stream("POLIZA_#".$no_poliza.".pdf");
                }
                
                $motivo = substr($request->get('descripcion'),5,9);
                if($motivo == "EXCEDENTE"){
                    $nomIdmov = substr( $request->get('descripcion'),16,100);
                    $varpagonom =  $this->obtenerempleadoxnomina($nomIdmov);

                    $pdf = \PDF::setPaper('letter')->loadView('Tesoreria.Movimientos.PDF.polizaExc',compact('fecha','tipoMovimiento',
                    'no_poliza','id1','nombre1','nombre2','tipo1','tipo2','saldo','saldo_inicial','saldo_actual','concepto',
                    'descripcion','usuario','hecho_por','nombre_empresa','icono','marca_agua','pertenece','varporcent','varpagonom','rfc'));
                    return $pdf->stream("POLIZA_#".$no_poliza.".pdf");
                }
                
                $motivo = substr($request->get('descripcion'),5,8);
                if($motivo == "EFECTIVO"){
                    $nomIdmov = substr( $request->get('descripcion'),15,100);
                    $varpagonom =  $this->obtenerempleadoxnomina($nomIdmov);
                    
                    $pdf = \PDF::setPaper('letter')->loadView('Tesoreria.Movimientos.PDF.polizaEfec',compact('fecha','tipoMovimiento',
                    'no_poliza','id1','nombre1','nombre2','tipo1','tipo2','saldo','saldo_inicial','saldo_actual','concepto',
                    'descripcion','usuario','hecho_por','nombre_empresa','icono','marca_agua','pertenece','varporcent','varpagonom','rfc'));
                    return $pdf->stream("POLIZA_#".$no_poliza.".pdf");
                }

            }else{
                if($tipoMovimiento == "TRANSFERENCIA" || $tipoMovimiento == "ENTREGA"){
                    
                    if($tipo2 == "cuenta"){
                        $varcu = $this->obtenercuentasPrincipales($id2);
                        foreach($varcu as $var){$nombre2 = $var->descripcion;}
                        $varcu = $this->obtenercuentasPrincipales($id2);
                    }
                    else{
                        $varcu = $this->obtenerCajasxId($id2);
                        foreach($varcu as $var){$nombre2 = $var->nombre;}
                        $varcu = $this->obtenercuentasPrincipales($id2);
                    }
                    
                }elseif($tipoMovimiento == "GASTO"){
                    if($tipo1 == "Cajas" || $tipo1 == "Cajas Chicas"  || $tipo1 == "caja" || $tipo1 == "caja_chica"){
                        $varporcent = $this->obtenerMovCajas($idmov);                    
                    }else{
                        $varporcent = $this->obtenerMovCuentas($idmov);
                    }
                }

                $pdf = \PDF::setPaper('letter')->loadView('Tesoreria.Movimientos.PDF.poliza',compact('fecha','tipoMovimiento','no_poliza','id1',
                'nombre1','nombre2','tipo1','tipo2','saldo','saldo_inicial','saldo_actual','concepto','descripcion','usuario','hecho_por',
                'nombre_empresa','icono','marca_agua','pertenece','varporcent','rfc'));
                return $pdf->stream("POLIZA_#".$no_poliza.".pdf");
            }
            
        } catch(\Illuminate\Database\QueryException $ex){return back()->with("warningBD","No se logro"); }
    }

    public function dictamen(string $tipo_caja,string $estado,int $id, int $caja, string $saldo){
        $date = Carbon::now();
        $fecha = $date->format('Y-m-d');
     try{

        if($estado == "Autorizar"){
            $estado = historial_cajas::find($id);
            $estado->estado = "A";
            $estado->updated_by = auth()->user()->name;
            $estado->save();

            if($tipo_caja == "Cajas Chicas"){
                // $varcajas = $this->obtenerCajasxId($caja);
                // foreach ($varcajas as $cajas){$saldoCaja = $cajas->saldo_actual;$nombreCaja = $cajas->nombre;}
                // $saldoFinal = $saldoCaja + $saldo;

                // $Cajas = Cajas::find($caja);
                // $Cajas->saldo_actual = $saldoFinal;
                // $Cajas->updated_by = auth()->user()->name;
                // $Cajas->save();

                // $historialcuent = new  historial_cajas();
                // $historialcuent->id_caja = $caja;
                // $historialcuent->id_empleado =auth()->user()->idempleado;
                // $historialcuent->estado = "A";
                // $historialcuent->tipo_movimiento = "INGRESO";
                // $historialcuent->concepto = "GASTO AUTORIZADO Y SALDO LIBERADO";
                // $historialcuent->descripcion = "SE DICTAMINO EL GASTO, FUE RECONOCIDO Y SE APLICO UN NUEVO INGRESO POR EL MISMO TOTAL DE GASTO ASUMIDO";
                // $historialcuent->responsable = "EMPLEADO #".auth()->user()->idempleado;
                // $historialcuent->ingreso = $saldo;
                // $historialcuent->egreso = 0;
                // $historialcuent->saldo =  $saldoFinal;
                // $historialcuent->numero_referencia = $caja;
                // $historialcuent->tipo_referencia = "caja";
                // $historialcuent->numero_poliza = 0;
                // $historialcuent->fecha = $fecha;
                // $historialcuent->created_by = auth()->user()->name;
                // $historialcuent->save();

                // $movcaja =$this->obtenerultimomovcaja();
                // foreach($movcaja as $caj){$movId = $caj->id;}

                // $poliza = historial_cajas::find($movId);
                // $poliza->numero_poliza = "CJ00".$movId;
                // $poliza->updated_by = auth()->user()->name;
                // $poliza->save();
                return back()->with("success","¡Se guardaron los cambios correctamente!");
            }else{
                return back()->with("success","¡Se guardaron los cambios correctamente!");
            }

            
        }else{

            $estado = historial_cajas::find($id);
            $estado->estado = "D";
            $estado->updated_by = auth()->user()->name;
            $estado->save();
            return back()->with("success","¡Se guardaron los cambios correctamente!");
        }
        return back()->with("warning","¡No se aplica!");
        } catch(\Illuminate\Database\QueryException $ex){return back()->with("warningBD","No se logro"); }  

    }

    //RESPONSABLE

    public function indexResponsable(Request $request,string $tipo, int $empresaid, int $id, string $fecha = null){
        try{
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();
            
            $date = Carbon::now();
            if($fecha == "null"){ $fecha = $date->format('Y-m-d');}
            $fecha = $date->format('Y-m-d');
            $mes = $date->format('m');
            $año = $date->format('Y');
            $fecha_inicio = $año."-".$mes."-01";
            $fecha_fin = $año."-".$mes."-31";
            $permisos1 = $this->forpermisos('movimientos_exportar');
            $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
            $mesNombre = $meses[$mes-1];
            $varGastos =  $this->obtenerGastosxEmpresa($empresaid);
            $obtenerCuentas =   $this->obtenercuentasActivas();
            $obtenerInfo = $this->obtenerCajasxId($id);
            
            if($tipo == "Caja"){
                // $fecha = "2024-09-14";
                $obtenerHistorial = $this->obtenerResponsableCajaDiario($id, $fecha);
                $varaqueo = $this->obtenerArqueoCajas($fecha,$id);
                if($varaqueo->isEmpty()){ $varaqueo = "null";
                }else{$varaqueo = $this->obtenerArqueoCajas($fecha,$id);}
                $varaqueos = $this->obtenerArqueosCajas($id);
                $validaAutorizar = $this->validarArqueoRealizado($id);

                return view('Tesoreria.Movimientos.Responsable.Caja.index',compact('varpantallas','varsubmenus','obtenerHistorial','obtenerInfo',
                'id','tipo','fecha_inicio', 'fecha_fin','permisos1','mesNombre','varGastos','obtenerCuentas','empresaid','varaqueo','varaqueos','validaAutorizar', 'fecha'));
            }else{
                
                $obtenerHistorial =   $this->obtenerResponsableCaja($id, $fecha_inicio, $fecha_fin);
                if (is_null($request->get('fecha_inicio')) || is_null($request->get('fecha_fin'))) {
                    $fecha_inicio = Carbon::now()->startOfMonth()->format('Y-m-d');
                    $fecha_fin = Carbon::now()->endOfMonth()->format('Y-m-d');
                } else {
                    $fecha_inicio = $request->get('fecha_inicio');
                    $fecha_fin = $request->get('fecha_fin');
                }

                $obtenerHistorial = $this->obtenerHistorialCajas($id, $fecha_inicio, $fecha_fin);


                return view('Tesoreria.Movimientos.Responsable.CajaChica.index',compact('varpantallas','varsubmenus','obtenerHistorial','obtenerInfo','id','tipo','fecha_inicio', 'fecha_fin','permisos1','mesNombre','varGastos', 'fecha'));
            }
        } catch(\Illuminate\Database\QueryException $ex){return back()->with("warningBD","No se logro"); }    
    }

    public function indexResponsableChica(Request $request, string $tipo, int $empresaid, int $id){
        try{
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();
            
            $date = Carbon::now();
            $fecha = $date->format('Y-m-d');
            $mes = $date->format('m');
            $año = $date->format('Y');
            $fecha_inicio = $año."-".$mes."-01";
            $fecha_fin = $año."-".$mes."-31";
            $permisos1 = $this->forpermisos('movimientos_exportar');
            $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto",
            "Septiembre","Octubre","Noviembre","Diciembre");
            $mesNombre = $meses[$mes-1];
            $varGastos =  $this->obtenerGastosxEmpresa($empresaid);
            $obtenerCuentas =   $this->obtenercuentasActivas();
            $obtenerInfo = $this->obtenerCajasxId($id);
            // $obtenerHistorial =   $this->obtenerResponsableCaja($id, $fecha_inicio, $fecha_fin);

            if (is_null($request->get('fecha_inicio')) || is_null($request->get('fecha_fin'))) {
                $fecha_inicio = Carbon::now()->startOfMonth()->format('Y-m-d');
                $fecha_fin = Carbon::now()->endOfMonth()->format('Y-m-d');
            } else {
                $fecha_inicio = $request->get('fecha_inicio');
                $fecha_fin = $request->get('fecha_fin');
            }

            $obtenerHistorial = $this->obtenerHistorialCajas($id, $fecha_inicio, $fecha_fin);
            return view('Tesoreria.Movimientos.Responsable.CajaChica.index',compact('varpantallas','varsubmenus',
            'obtenerHistorial','obtenerInfo','id','tipo','fecha_inicio', 'fecha_fin','permisos1',
            'mesNombre','varGastos', 'fecha','empresaid'));
            
        } catch(\Illuminate\Database\QueryException $ex){return back()->with("warningBD","No se logro"); }    
    }

    public function solicitarArqueo(int $id, Request $request)
    {
        try {
            $date = Carbon::now();
            $fecha = $date->format('Y-m-d'); 
            $idempleado = auth()->user()->idempleado;
            $idcaja = $id;
            $descripcion = $request->get('descripcion');
            $estado = "P";
            
            $notificacion = new Notificacion();
            $notificacion->id_empleado = $idempleado;
            $notificacion->id_caja = $idcaja;
            $notificacion->descripcion = $descripcion;
            $notificacion->estado = $estado;
            $notificacion->razon = "Solicitud de arqueo dias pasados";
            $notificacion->save();

            return back()->with("success", "¡Se guardaron los cambios correctamente!");
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "No se logró guardar la solicitud.");
        }
    }

    public function actualizarNotificacion(int $id){
        try {
            $notificacion = Notificacion::find($id);
            $notificacion->estado = "R";
            $notificacion->save();
            return back()->with("success", "¡Se guardaron los cambios correctamente!");
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "No se logró actualizar la notificación.");
        }

    }

    public function autorizarPermiso(int $id_empleado, int $id_notificacion){
        try {
            $permiso = $this->obtenerpermisosporNombre('filtrar_arqueos');
            $permisos = $this->forpermisos('filtrar_arqueos');
            
            $user = $this->obtenerUsuarioporIdempleado($id_empleado);
            if($permisos == "null")
            {
                $usuario_acc = new usuario_acciones();
                $usuario_acc->idusuario = $user->id;
                $usuario_acc->idacciones = $permiso->idacciones;
                $usuario_acc->save();
                $notificacion = Notificacion::find($id_notificacion);
                $notificacion->estado = "A";
                $notificacion->save();
                return back()->with("success", "¡Se guardaron los cambios correctamente!");
            }
            else{
                return back()->with("warningPermiso", "¡El usuario ya cuenta con el permiso!");
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "No se logró actualizar el permiso.");
        }
        
    }
    
    

    public function aplicarGasto(Request $request, int $id){
        $date = Carbon::now();
        $fecha = $date->format('Y-m-d');
        $nombreSuc = $request->get('sucursal');
        $nombreCaja = $request->get('nombre');
        $caja = $id;
        $tipo = $request->get('tipo');
        $mesNombre = $request->get('mes');
        $id_gasto = $request->get('gasto');
        $vargastos = $this->obtenerGastosxId($id_gasto);
        $importe = $request->get('saldo');
        foreach ($vargastos as $gat){
            $nombre_gasto = $gat->nombre;
            $iva = $gat->iva;
            $ret_iva = $gat->ret_iva;
            $ret_isr = $gat->ret_isr;
            $ret_isr_resico = $gat->ret_isr_resico;
        }

        //calculo de iva

        if($iva !=0){ $total_iva = ($importe * $iva)/100;
        }else{$total_iva = 0;}

        if($ret_iva !=0){ $total_ret_iva = ($importe * $ret_iva)/100;
        }else{ $total_ret_iva = 0;}

        if($ret_isr !=0){ $total_ret_isr = ($importe * $ret_isr)/100;
        }else{ $total_ret_isr = 0;}

        if($ret_isr_resico !=0){ $total_ret_isr_resico = ($importe * $ret_isr_resico)/100;
        }else{ $total_ret_isr_resico = 0;}

        $suma = $total_iva;
        $resta = $total_ret_iva + $total_ret_isr + $total_ret_isr_resico;
        $total_neto = ($importe + $suma) - $resta;


        try{

            if($tipo == "Cuentas"){
                $varobtenercuentas =$this->obtenercuentasPrincipales($caja);
                foreach($varobtenercuentas as $varobtenercuenta){$saldoCaja = $varobtenercuenta->saldo_actual;$nombreCaja = $varobtenercuenta->descripcion;}

                $saldoFinal = $saldoCaja - $total_neto;

                if($total_neto <= $saldoCaja){
                    $historialcuent = new  historial_cuentas();
                    $historialcuent->id_cuenta = $caja;
                    $historialcuent->id_empleado =auth()->user()->idempleado;
                    $historialcuent->estado = "A";
                    $historialcuent->tipo_movimiento = "GASTO";
                    $historialcuent->concepto = $nombre_gasto;
                    $historialcuent->descripcion = $request->get('descripcion');
                    $historialcuent->responsable = "EMPLEADO #".auth()->user()->idempleado;
                    $historialcuent->total_iva = $total_iva;
                    $historialcuent->total_ret_iva = $total_ret_iva;
                    $historialcuent->total_ret_isr = $total_ret_isr;
                    $historialcuent->total_ret_isr_resico = $total_ret_isr_resico;
                    $historialcuent->ingreso = 0;
                    $historialcuent->egreso = $total_neto;
                    $historialcuent->saldo =  $saldoFinal;
                    $historialcuent->numero_referencia = $id_gasto;
                    $historialcuent->tipo_referencia = "gastos";
                    $historialcuent->numero_poliza = 0;
                    $historialcuent->fecha = $fecha;
                    $historialcuent->created_by = auth()->user()->name;
    
                    if($historialcuent->save()){
                        $Rutacarpeta = "Tesoreria/Gastos/".$tipo;
    
                        $movcuenta =$this->obtenerultimomovcuenta();
                        foreach($movcuenta as $cue){$movId = $cue->id;}

                        if(!file_exists(public_path($Rutacarpeta))){
                            File::makeDirectory($Rutacarpeta,0777,true,true);
                        }
    
                        $file_comp = $request->file("evidencia");
                        $Nombre_comp = "comprobante_".$movId.".".$file_comp->guessExtension();
                        $ruta_comp = public_path($Rutacarpeta."/".$Nombre_comp);  
                        copy($file_comp, $ruta_comp);

                        $poliza = historial_cuentas::find($movId);
                        $poliza->numero_poliza = "CU00".$movId;
                        $poliza->ruta_evidencia = $Nombre_comp;
                        $poliza->updated_by = auth()->user()->name;
                        $poliza->save();
    
                        $Cajas = cuentas::find($caja);
                        $Cajas->saldo_actual = $saldoFinal;
                        $Cajas->updated_by = auth()->user()->name;
                        $Cajas->save();
    
                        return back()->with("success","¡Se guardaron los cambios correctamente!");
                    }
                    return back()->with("warning","¡No se aplica!");
                }else{
                    return back()->with("info_msg","¡El saldo no es suficiente para realizar esta acción!");
                }

            }else{
                $varcajas = $this->obtenerCajasxId($caja);
                foreach ($varcajas as $cajas){$saldoCaja = $cajas->saldo_actual;$nombreCaja = $cajas->nombre;}
                $saldoFinal = $saldoCaja - $total_neto;

                if($total_neto <= $saldoCaja){
                    $historialcuent = new  historial_cajas();
                    $historialcuent->id_caja = $caja;
                    $historialcuent->id_empleado =auth()->user()->idempleado;
                    $historialcuent->estado = "E";
                    $historialcuent->tipo_movimiento = "GASTO";
                    $historialcuent->concepto = $nombre_gasto;
                    $historialcuent->descripcion = $request->get('descripcion');
                    $historialcuent->responsable = "EMPLEADO #".auth()->user()->idempleado;
                    $historialcuent->total_iva = $total_iva;
                    $historialcuent->total_ret_iva = $total_ret_iva;
                    $historialcuent->total_ret_isr = $total_ret_isr;
                    $historialcuent->total_ret_isr_resico = $total_ret_isr_resico;
                    $historialcuent->ingreso = 0;
                    $historialcuent->egreso = $total_neto;
                    $historialcuent->saldo =  $saldoFinal;
                    $historialcuent->numero_referencia = $id_gasto;
                    $historialcuent->tipo_referencia = "gastos";
                    $historialcuent->numero_poliza = 0;
                    $historialcuent->fecha = $fecha;
                    $historialcuent->created_by = auth()->user()->name;
    
                    if($historialcuent->save()){
                        $movcaja =$this->obtenerultimomovcaja();
                        foreach($movcaja as $caj){$movId = $caj->id;}
    
                        $Rutacarpeta = "Tesoreria/Gastos/".$tipo;
    
                        if(!file_exists(public_path($Rutacarpeta))){
                            File::makeDirectory($Rutacarpeta,0777,true,true);
                        }
    
                        $file_comp = $request->file("evidencia");
                        $Nombre_comp = "comprobante_".$movId.".".$file_comp->guessExtension();
                        $ruta_comp = public_path($Rutacarpeta."/".$Nombre_comp);  
                        copy($file_comp, $ruta_comp);
    
                        $poliza = historial_cajas::find($movId);
                        $poliza->numero_poliza = "CJ00".$movId;
                        $poliza->ruta_evidencia = $Nombre_comp;
                        $poliza->updated_by = auth()->user()->name;
                        $poliza->save();
    
                        $Cajas = Cajas::find($caja);
                        $Cajas->saldo_actual = $saldoFinal;
                        $Cajas->updated_by = auth()->user()->name;
                        $Cajas->save();
    
                        return back()->with("success","¡Se guardaron los cambios correctamente!");
                    }
                    return back()->with("warning","¡No se aplica!");
                }else{
                    return back()->with("info_msg","¡El saldo no es suficiente para realizar esta acción!");
                }
            }
          
        } catch(\Illuminate\Database\QueryException $ex){return back()->with("warningBD","No se logro"); }       

    }

    public function entregasEfectivo(int $id, Request $request){
        $date = Carbon::now();
        $fecha = $date->format('Y-m-d');
        $id1 = $id;
        $id2 = $request->get('cuenta');

        try{
            if($id2 != 0){
                    
                    $varcajas = $this->obtenerCajasxId($id1);
                    foreach ($varcajas as $cajas){
                        $saldo1 = $cajas->saldo_actual;
                        $nombre1 = $cajas->nombre;
                    }
                   
                    $varobtenercuentas = $this->obtenercuentasPrincipales($id2);
                    foreach($varobtenercuentas as $varobtenercuenta){
                        $saldo2 = $varobtenercuenta->saldo_actual;
                        $nombre2 = $varobtenercuenta->descripcion;
                    }

                  

                    if($request->get('saldo_entregar') <= $saldo1 ){

                        $saldoEgresa = $saldo1 - $request->get('saldo_entregar');

                        $cuenta1 = Cajas::find($id1);
                        $cuenta1->saldo_actual = $saldoEgresa;
                        $cuenta1->updated_by=auth()->user()->name;
                        $cuenta1->save();


                        $saldoIngresa = $saldo2 + $request->get('saldo_entregar');

                        $cuenta2 = cuentas::find($id2);
                        $cuenta2->saldo_actual = $saldoIngresa;
                        $cuenta2->updated_by=auth()->user()->name;
                        $cuenta2->save();

                        if($cuenta1->save() && $cuenta2->save()){

                            $historial1 = new historial_cajas();
                            $historial1->id_caja = $id1;
                            $historial1->id_empleado =auth()->user()->idempleado;
                            $historial1->estado = "A";
                            $historial1->tipo_movimiento = "ENTREGA";
                            $historial1->concepto = "ENTREGA DE EFECTIVO DESDE CAJA A CUENTA".$nombre2;
                            $historial1->descripcion = $request->get('descripcion');
                            $historial1->responsable = "EMPLEADO #".auth()->user()->idempleado;
                            $historial1->ingreso = 0;
                            $historial1->egreso = $request->get('saldo_entregar');
                            $historial1->saldo =  $saldoEgresa;
                            $historial1->numero_referencia = $id2;
                            $historial1->tipo_referencia = "cuenta";
                            $historial1->numero_poliza = 0;
                            $historial1->fecha = $fecha;
                            $historial1->created_by = auth()->user()->name;
                            $historial1->save();

                            $movcaja =$this->obtenerultimomovcaja();
                            foreach($movcaja as $caj){$movId = $caj->id;}
                            $poliza = historial_cajas::find($movId);
                            $poliza->numero_poliza = "CJ00".$movId;
                            $poliza->updated_by = auth()->user()->name;
                            $poliza->save();
                            

                            $historial2 = new historial_cuentas(); 
                            $historial2->id_cuenta = $id2;
                            $historial2->id_empleado =auth()->user()->idempleado;
                            $historial2->estado = "A";
                            $historial2->tipo_movimiento = "INGRESO";
                            $historial2->concepto = "INGRESO DE SALDO POR ENTREGA DE EFECTIVO DE ".$nombre1;
                            $historial2->descripcion = $request->get('descripcion');
                            $historial2->responsable = "EMPLEADO #".auth()->user()->idempleado;
                            $historial2->ingreso = $request->get('saldo_entregar');
                            $historial2->egreso = 0;
                            $historial2->saldo =  $saldoIngresa;
                            $historial2->numero_referencia = $id1;
                            $historial2->tipo_referencia = "caja";
                            $historial2->numero_poliza = 0;
                            $historial2->fecha = $fecha;
                            $historial2->created_by=auth()->user()->name;
                            $historial2->save();

                            $movcuenta =$this->obtenerultimomovcuenta();
                            foreach($movcuenta as $cue){$movId = $cue->id;}
                            $poliza = historial_cuentas::find($movId);
                            $poliza->numero_poliza = "CU00".$movId;
                            $poliza->updated_by = auth()->user()->name;
                            $poliza->save();
                        

                            return back()->with("success","¡Se guardaron los cambios correctamente!");
                        }else{
                            return back()->with("warning","No se logro");
                        }
                    }else{
                        return back()->with("warningSaldo","No se logro");
                    }
            }else{
                return back()->with("warningCuenta","No se logro");
            }
       } catch(\Illuminate\Database\QueryException $ex){return back()->with("warningBD","No se logro"); }
    }
  
    //ARQUEO

    public function controlarqueos(string $tipo,int $empresaid, int $id, Request $request){
        try{
            if (is_null($request->get('fecha_inicio')) || is_null($request->get('fecha_fin'))) {
                $fecha_inicio = Carbon::now()->startOfMonth()->format('Y-m-d');
                $fecha_fin = Carbon::now()->format('Y-m-d');
            } else {
                $fecha_inicio = $request->get('fecha_inicio');
                $fecha_fin = $request->get('fecha_fin');
            }
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();
            $varaqueos = $this->obtenerArqueosCajas($id, $fecha_inicio, $fecha_fin);
            Log::info(json_encode($varaqueos));
            return view('Tesoreria.Movimientos.ControlArqueo.index',compact('varpantallas','varsubmenus','varaqueos','empresaid','id','tipo', 'fecha_inicio', 'fecha_fin'));
        } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    }

    public function InsertArqueoCaja(string $tipo,int $empresaid, int $id, string $fecha, Request $request){
        try{
                $varpantallas =  $this->Traermenuenc();
                $varsubmenus =   $this->Traermenudet();
                $date = Carbon::now();
                // $fecha = "2024-09-14";
                $varcajas = $this->obtenerCajasxId($id); 
                $vartraspasos = $this->obtenerTraspasosDiarios($id,$fecha);
                $varcobranza = $this->obtenerPagosDiarios($id,$fecha);
                $vardesembolsos = $this->obtenerDesembolsosDiarios($id,$fecha);
                $vargastos = $this->obtenerGastosDiarios($id,$fecha);
                $varentregas = $this->obtenerEntregasDiarias($id,$fecha);
                $varcancelaciones = $this->obtenerCancelacioneDiarias($id,$fecha);
                $checararqueo = $this->checararqueo($fecha,$id);
                $permiso = $this->forpermisoconid('filtrar_arqueos');
                

                $usuario_acc = usuario_acciones::where('idusuario', auth()->user()->id)
                                ->where('idacciones', $permiso)
                                ->first();

                if ($usuario_acc) {
                    $usuario_acc->delete();
                }               

                foreach ($varcajas as $cajas){$saldo_actual = $cajas->saldo_actual;$saldo_inicial = $cajas->saldo_inicial;$nombre = $cajas->nombre;}
               
        
                //variables a calcular
                $traspasos = 0; $cobranza = 0; $desembolsos = 0; $gastos = 0; $entregas = 0;$cancelaciones = 0;
                //monedas
                $diez = 10; $cinco = 5; $dos = 2; $uno = 1; $cincuentacentavos = 0.50;
                //billetes
                $mil = 1000;  $quinientos = 500; $doscientos = 200; $cien = 100; $cincuenta = 50; $veinte = 20;

                foreach ($vartraspasos as $tras){$traspasos = $traspasos + $tras->ingreso;}
                foreach ($varcobranza as $pagos){$cobranza = $cobranza + $pagos->ingreso; }
                foreach ($vardesembolsos as $desem){$desembolsos = $desembolsos + $desem->egreso;}
                foreach ($vargastos as $gast){$gastos = $gastos + $gast->egreso; }
                foreach ($varentregas as $entr){ $entregas = $entregas + $entr->egreso;}
                foreach ($varcancelaciones as $can){ $cancelaciones = $cancelaciones + $can->egreso;}

                //calculo de cantidades del sistema - siempre esta calculando segun movimientos del día
                $total_ingresos = $saldo_inicial + $cobranza +  $traspasos; 
                $total_egresos = $desembolsos + $gastos + $entregas + $cancelaciones;
                $total_calculado =  $total_ingresos - $total_egresos;


                if($checararqueo->isEmpty()){
                    //si no se ha hecho el arqueo del día
                    //calculo de efectivo ingresado 
                    $catidad_diez = $request->get('diez');
                    $catidad_cinco = $request->get('cinco');
                    $catidad_dos = $request->get('dos');
                    $catidad_uno = $request->get('uno');
                    $catidad_cincuentacentavos = $request->get('cincuentacentavos');
                    
                    $total_diez = $diez * $catidad_diez;
                    $total_cinco = $cinco * $catidad_cinco;
                    $total_dos = $dos * $catidad_dos;
                    $total_uno = $uno * $catidad_uno;
                    $total_cincuentacentavos = $cincuentacentavos * $catidad_cincuentacentavos;

                    $catidad_mil = $request->get('mil');
                    $catidad_quinientos = $request->get('quinientos');
                    $catidad_doscientos = $request->get('doscientos');
                    $catidad_cien = $request->get('cien');
                    $catidad_cincuenta = $request->get('cincuenta');
                    $catidad_veinte = $request->get('veinte');

                    $total_mil = $mil * $catidad_mil;
                    $total_quinientos = $quinientos * $catidad_quinientos;
                    $total_doscientos = $doscientos * $catidad_doscientos;
                    $total_cien =  $cien * $catidad_cien;
                    $total_cincuenta = $cincuenta * $catidad_cincuenta;
                    $total_veinte = $veinte * $catidad_veinte;

                    //calculamos todo lo ingresado
                    $total_ingresado = ($total_diez + $total_cinco + $total_dos + $total_uno + $total_cincuentacentavos) + ($total_mil + $total_quinientos + $total_doscientos + $total_cien + $total_cincuenta + $total_veinte);

                    //calculo de diferencias
                    if($total_calculado > $total_ingresado) {
                        $diferencia = $total_calculado - $total_ingresado;
                    }elseif($total_calculado < $total_ingresado) {
                        $diferencia = $total_ingresado - $total_calculado;
                    }else {
                        $diferencia = 0;
                    }

                    //insertar arqueo y detalle de efectivo
                    $estado = "Creado";
                    $arqueo = new arqueocajas();
                    $arqueo->id_caja = $id;
                    $arqueo->estado = $estado;
                    $arqueo->realizado  = auth()->user()->idempleado;
                    $arqueo->fecha = $fecha;
                    $arqueo->saldo_inicial = $saldo_inicial;
                    $arqueo->saldo_actual = $saldo_actual;
                    $arqueo->total_traspasos = $traspasos;
                    $arqueo->total_cobranza = $cobranza;
                    $arqueo->total_ingresos = $total_ingresos;
                    $arqueo->total_desembolsos =  $desembolsos;
                    $arqueo->total_gastos =  $gastos;
                    $arqueo->total_entregas =  $entregas;
                    $arqueo->total_cancelaciones =  $cancelaciones;
                    $arqueo->total_egresos =  $total_egresos;
                    $arqueo->total_calculado =  $total_calculado;
                    $arqueo->diferencia =  $diferencia;
                    $arqueo->total_ingresado =  $total_ingresado;
                    $arqueo->created_by = auth()->user()->name;
                    $arqueo->save();

                    $ultimoarqueo = $this->obtenerultimoarqueocaja();
                    foreach($ultimoarqueo as $arq){$id_arqueo = $arq->id;}

                    if(!$ultimoarqueo->isEmpty()){
                        $arqueorelacion = new  arqueorelacion_efect();
                        $arqueorelacion->id_arqueo  = $id_arqueo;
                        $arqueorelacion->mil = $request->get('mil');
                        $arqueorelacion->quinientos = $request->get('quinientos');
                        $arqueorelacion->doscientos = $request->get('doscientos');
                        $arqueorelacion->cien = $request->get('cien');
                        $arqueorelacion->cincuenta = $request->get('cincuenta');
                        $arqueorelacion->veinte =  $request->get('veinte');
                        $arqueorelacion->diez = $request->get('diez');
                        $arqueorelacion->cinco =  $request->get('cinco');
                        $arqueorelacion->dos = $request->get('dos');
                        $arqueorelacion->uno = $request->get('uno');
                        $arqueorelacion->cincuentacentavos = $request->get('cincuentacentavos');
                        $arqueorelacion->created_by = auth()->user()->name;
                        $arqueorelacion->save();
                        return  redirect()->route('arqueoCaja', [$tipo,$empresaid,$id,$fecha])->with("success","ya realizado");
                    }else{
                        return back()->with("warningBD","No se logro");
                    }
                
                    
                }else{
                    return  redirect()->route('indexResponsable', [$tipo,$empresaid,$id,$fecha])->with("arqueroExistente","Arqueo ya realizado con esta fecha");
                }
    
                // return view('Tesoreria.Movimientos.Responsable.Caja.arqueo',compact('varpantallas','varsubmenus','vartraspasos','varcobranza','vardesembolsos','vargastos', 'varentregas','varcancelaciones', 'id', 'tipo','empresaid', 'fecha','saldo_actual', 'saldo_inicial', 'nombre',  'traspasos', 'cobranza',  'total_ingresos', 'desembolsos','gastos', 'entregas','cancelaciones','total_egresos','total_calculado','total_ingresado','diferencia',
                //     'catidad_veinte','catidad_cincuenta','catidad_cien','catidad_doscientos','catidad_quinientos','catidad_mil','catidad_cincuentacentavos','catidad_uno','catidad_dos','catidad_cinco','catidad_diez','estado','id_arqueo','idarqueo_efect'));

            } catch(\Illuminate\Database\QueryException $ex){return back()->with("warningBD","No se logro"); } 
    }

    public function arqueoCaja(string $tipo,int $empresaid, int $id, string $fecha){ {
        try{
                $varpantallas =  $this->Traermenuenc();
                $varsubmenus =   $this->Traermenudet();
                $date = Carbon::now();

                if($fecha == "null"){ $fecha = $date->format('Y-m-d'); }
                $varcajas = $this->obtenerCajasxId($id); 
                $varaqueo = $this->obtenerArqueoCajas($fecha,$id);
                $vartraspasos = $this->obtenerTraspasosDiarios($id,$fecha);
                $varcobranza = $this->obtenerPagosDiarios($id,$fecha);
                $vardesembolsos = $this->obtenerDesembolsosDiarios($id,$fecha);
                $vargastos = $this->obtenerGastosDiarios($id,$fecha);
                $varentregas = $this->obtenerEntregasDiarias($id,$fecha);
                $varcancelaciones = $this->obtenerCancelacioneDiarias($id,$fecha);
                $checararqueo = $this->checararqueo($fecha,$id);
            
                 if(!$varaqueo->isEmpty()){
                    foreach ($varcajas as $cajas){$saldo_actual = $cajas->saldo_actual;$nombre = $cajas->nombre;}
                    foreach ($varaqueo as $arq){$saldo_inicial = $arq->saldo_inicial;}
            
                    //variables a calcular
                    $traspasos = 0; $cobranza = 0; $desembolsos = 0; $gastos = 0; $entregas = 0;$cancelaciones = 0;
                    //monedas
                    $diez = 10; $cinco = 5; $dos = 2; $uno = 1; $cincuentacentavos = 0.50;
                    //billetes
                    $mil = 1000;  $quinientos = 500; $doscientos = 200; $cien = 100; $cincuenta = 50; $veinte = 20;

                    foreach ($vartraspasos as $tras){$traspasos = $traspasos + $tras->ingreso;}
                    foreach ($varcobranza as $pagos){$cobranza = $cobranza + $pagos->ingreso; }
                    foreach ($vardesembolsos as $desem){$desembolsos = $desembolsos + $desem->egreso;}
                    foreach ($vargastos as $gast){$gastos = $gastos + $gast->egreso; }
                    foreach ($varentregas as $entr){ $entregas = $entregas + $entr->egreso;}
                    foreach ($varcancelaciones as $can){ $cancelaciones = $cancelaciones + $can->egreso;}

                    //calculo de cantidades del sistema - siempre esta calculando segun movimientos del día
                    $total_ingresos = $saldo_inicial + $cobranza +  $traspasos; 
                    $total_egresos = $desembolsos + $gastos + $entregas + $cancelaciones;
                    $total_calculado =  $total_ingresos - $total_egresos;
                
                    
                    foreach($varaqueo as $arqueocaja){
                        $estado = $arqueocaja->estado;
                        $id_arqueo = $arqueocaja->id;
                        $idarqueo_efect = $arqueocaja->idarqueo_efect;

                        //Traemos todo lo ingresado
                        $catidad_diez = $arqueocaja->diez;
                        $catidad_cinco = $arqueocaja->cinco;
                        $catidad_dos = $arqueocaja->dos;
                        $catidad_uno = $arqueocaja->uno;
                        $catidad_cincuentacentavos = $arqueocaja->cincuentacentavos;

                        $total_diez = $diez * $catidad_diez;
                        $total_cinco = $cinco * $catidad_cinco;
                        $total_dos = $dos * $catidad_dos;
                        $total_uno = $uno * $catidad_uno;
                        $total_cincuentacentavos = $cincuentacentavos * $catidad_cincuentacentavos;
                
                        $catidad_mil = $arqueocaja->mil;
                        $catidad_quinientos = $arqueocaja->quinientos;
                        $catidad_doscientos = $arqueocaja->doscientos;
                        $catidad_cien = $arqueocaja->cien;
                        $catidad_cincuenta = $arqueocaja->cincuenta;
                        $catidad_veinte = $arqueocaja->veinte;

                        $total_mil = $mil * $catidad_mil;
                        $total_quinientos = $quinientos * $catidad_quinientos;
                        $total_doscientos = $doscientos * $catidad_doscientos;
                        $total_cien =  $cien * $catidad_cien;
                        $total_cincuenta = $cincuenta * $catidad_cincuenta;
                        $total_veinte = $veinte * $catidad_veinte;

                        //calculamos lo que traemos de efectivo insertado con los datos actuales de movimientos
                        $total_ingresado = ($total_diez + $total_cinco + $total_dos + $total_uno + $total_cincuentacentavos) + ($total_mil + $total_quinientos + $total_doscientos + $total_cien + $total_cincuenta + $total_veinte);

                        //calculo de diferencias
                        if($total_calculado > $total_ingresado) {
                            $diferencia = $total_calculado - $total_ingresado;
                        }elseif($total_calculado < $total_ingresado) {
                            $diferencia = $total_ingresado - $total_calculado;
                        }else {
                            $diferencia = 0;
                        }
                    }

                    return view('Tesoreria.Movimientos.Responsable.Caja.arqueo',compact('varpantallas','varsubmenus','vartraspasos','varcobranza',
                    'vardesembolsos','vargastos', 'varentregas','varcancelaciones', 'id', 'tipo','empresaid', 'fecha','saldo_actual', 'saldo_inicial', 'nombre',  'traspasos', 'cobranza',  'total_ingresos', 'desembolsos','gastos', 'entregas','cancelaciones','total_egresos','total_calculado','total_ingresado','diferencia',
                    'catidad_veinte','catidad_cincuenta','catidad_cien','catidad_doscientos','catidad_quinientos','catidad_mil',
                    'catidad_cincuentacentavos','catidad_uno','catidad_dos','catidad_cinco','catidad_diez','estado','id_arqueo','idarqueo_efect'));

                }else{
                    return  redirect()->route('indexResponsable', [$tipo,$empresaid,$id])->with("warningBD","Arqueo ya realizado con esta fecha");
                }
            } catch(\Illuminate\Database\QueryException $ex){
                Log::error($ex->getMessage());
                return back()->with("warningBD","No se logro"); } 
    }
    }

    public function exportarArqueoCaja(string $tipo,int $empresaid, int $id, string $fecha, Request $request){
        try{
            $varcajas = $this->obtenerCajasxId($id); 
            foreach ($varcajas as $cajas){$nombre = $cajas->nombre;$nombre_sucursal = $cajas->nombre_sucursal;}
            $obtenerempresa =  $this->obtenerempresaxid($empresaid);
            foreach($obtenerempresa as $varemp){$nombre_empresa = $varemp->nombre_empresa;$icono = $varemp->icono;$marca_agua = $varemp->marca_agua;}
            
            $varaqueo = $this->obtenerArqueoCajas($fecha,$id);
            $vartraspasos = $this->obtenerTraspasosDiarios($id,$fecha);
            $varcobranza = $this->obtenerPagosDiarios($id,$fecha);
            $vardesembolsos = $this->obtenerDesembolsosDiarios($id,$fecha);
            $vargastos = $this->obtenerGastosDiarios($id,$fecha);
            $varentregas = $this->obtenerEntregasDiarias($id,$fecha);
            $varcancelaciones = $this->obtenerCancelacioneDiarias($id,$fecha);
            //variables a calcular
            $traspasos = 0; $cobranza = 0; $desembolsos = 0; $gastos = 0; $entregas = 0;$cancelaciones = 0;
            //monedas
            $diez = 10; $cinco = 5;  $dos = 2; $uno = 1; $cincuentacentavos = 0.50;
            //billetes
            $mil = 1000;  $quinientos = 500; $doscientos = 200; $cien = 100;  $cincuenta = 50; $veinte = 20;

            foreach ($vartraspasos as $tras){$traspasos = $traspasos + $tras->ingreso;}
            foreach ($varcobranza as $pagos){$cobranza = $cobranza + $pagos->ingreso; }
            foreach ($vardesembolsos as $desem){$desembolsos = $desembolsos + $desem->egreso;}
            foreach ($vargastos as $gast){$gastos = $gastos + $gast->egreso; }
            foreach ($varentregas as $entr){ $entregas = $entregas + $entr->egreso;}
            foreach ($varcancelaciones as $can){ $cancelaciones = $cancelaciones + $can->egreso;}

            //calculo de efectivo ingresado 
            
            foreach($varaqueo as $arqueocaja){
                $estado = $arqueocaja->estado;
                $NombreAutoriza = $arqueocaja->NombreAutoriza;
                $NombreRealizado = $arqueocaja->NombreRealizado;
                $saldo_actual = $arqueocaja->saldo_actual;
                $saldo_inicial = $arqueocaja->saldo_inicial;
                $total_ingresado = $arqueocaja->total_ingresado;

                $catidad_diez = $arqueocaja->diez;
                $catidad_cinco = $arqueocaja->cinco;
                $catidad_dos = $arqueocaja->dos;
                $catidad_uno = $arqueocaja->uno;
                $catidad_cincuentacentavos = $arqueocaja->cincuentacentavos;

                $catidad_mil = $arqueocaja->mil;
                $catidad_quinientos = $arqueocaja->quinientos;
                $catidad_doscientos = $arqueocaja->doscientos;
                $catidad_cien = $arqueocaja->cien;
                $catidad_cincuenta = $arqueocaja->cincuenta;
                $catidad_veinte = $arqueocaja->veinte;
            }
            

            //calculo de cantidades del sistema dinamicas para que marque alguna diferencia aun que ya se cierre el arqueo
            //si el arqueo marca diferencia es por que se hizo un movimeinto (desmbolso cobranza, ingreso o egreso) que no se contemplo en el arqueo diario.
            $total_ingresos = $saldo_inicial + $cobranza +  $traspasos;
            $total_egresos = $desembolsos + $gastos + $entregas + $cancelaciones;
            $total_calculado = $total_ingresos - $total_egresos;
            

            //calculo de diferencias
            if($total_calculado > $total_ingresado) {
                $diferencia = $total_calculado - $total_ingresado;
            }elseif($total_calculado < $total_ingresado) {
                $diferencia = $total_ingresado - $total_calculado;
            }else {
                $diferencia = 0;
            }


            $pdf = \PDF::setPaper('letter')->loadView('Tesoreria.Movimientos.PDF.arqueo',compact('nombre_empresa','marca_agua','icono','NombreRealizado','nombre_sucursal','vartraspasos','varcobranza','vardesembolsos','vargastos', 'varentregas','varcancelaciones', 'id', 'tipo','empresaid', 'fecha','saldo_actual', 'saldo_inicial', 'nombre',  'traspasos', 'cobranza',  'total_ingresos', 'desembolsos','gastos', 'entregas','cancelaciones','total_egresos','total_calculado','total_ingresado','diferencia',
            'catidad_veinte','catidad_cincuenta','catidad_cien','catidad_doscientos','catidad_quinientos','catidad_mil','catidad_cincuentacentavos','catidad_uno','catidad_dos','catidad_cinco','catidad_diez','NombreAutoriza','estado'));
                return $pdf->stream("ARQUEO _".$nombre."_".$fecha.".pdf");
                
        } catch(\Illuminate\Database\QueryException $ex){return back()->with("warningBD","No se logro"); } 


    }
    
    public function comentarArqueo(int $id, Request $request){
        try{
                $arqueo = arqueocajas::find($id);
                $arqueo->estado = "En Espera";
                $arqueo->concepto = $request->get("accion");
                $arqueo->comentario = $request->get("comentario");
                $arqueo->updated_by = auth()->user()->name;
                $arqueo->save();

                return back()->with("success", "Realizado Accion");
        } catch(\Illuminate\Database\QueryException $ex){return back()->with("warningBD","No se logro"); } 
    }

    public function autorizarArqueo(int $id, int $caja,string $saldo){
        try{
                $cuenta1 = Cajas::find($caja);
                $cuenta1->saldo_inicial = $saldo;
                $cuenta1->saldo_actual = $saldo;
                $cuenta1->updated_by=auth()->user()->name;

                if($cuenta1->save()){
                    $arqueo = arqueocajas::find($id);
                    $arqueo->estado = "Autorizado";
                    $arqueo->autoriza = auth()->user()->idempleado;
                    $arqueo->updated_by = auth()->user()->name;
                    $arqueo->save();
                    return back()->with("success", "Realizado Accion");
                }else{
                    return back()->with("warningBD","No se logro"); 
                }
                
                
        } catch(\Illuminate\Database\QueryException $ex){return back()->with("warningBD","No se logro"); } 
    }

    public function cancelarArqueo(int $id){
        try{
                $arqueo = arqueocajas::find($id);
                $arqueo->estado = "Cancelado";
                $arqueo->updated_by = auth()->user()->name;
                $arqueo->save();

                return back()->with("success", "Realizado Accion");
        } catch(\Illuminate\Database\QueryException $ex){return back()->with("warningBD","No se logro"); } 
    }

    public function editarArqueo(int $id){
        try{
                $arqueo = arqueocajas::find($id);
                $arqueo->estado = "Editando";
                $arqueo->updated_by = auth()->user()->name;
                $arqueo->save();

                return back()->with("success", "Realizado Accion");
        } catch(\Illuminate\Database\QueryException $ex){return back()->with("warningBD","No se logro"); } 
    }

    public function detalleArqueo(string $tipo,int $empresaid,string $fecha, int $id, Request $request){
        try{
                $varpantallas =  $this->Traermenuenc();
                $varsubmenus =   $this->Traermenudet();
                $varcajas = $this->obtenerCajasxId($id); 
                foreach ($varcajas as $cajas){$nombre = $cajas->nombre;}
                $varaqueo = $this->obtenerArqueoCajas($fecha,$id);
                $vartraspasos = $this->obtenerTraspasosDiarios($id,$fecha);
                $varcobranza = $this->obtenerPagosDiarios($id,$fecha);
                $vardesembolsos = $this->obtenerDesembolsosDiarios($id,$fecha);
                $vargastos = $this->obtenerGastosDiarios($id,$fecha);
                $varentregas = $this->obtenerEntregasDiarias($id,$fecha);
                $varcancelaciones = $this->obtenerCancelacioneDiarias($id,$fecha);

                //variables a calcular
                $traspasos = 0; $cobranza = 0; $desembolsos = 0; $gastos = 0; $entregas = 0;$cancelaciones = 0;
                //monedas
                $diez = 10; $cinco = 5; $dos = 2; $uno = 1; $cincuentacentavos = 0.50;
                //billetes
                $mil = 1000;  $quinientos = 500; $doscientos = 200; $cien = 100; $cincuenta = 50; $veinte = 20;

                foreach ($vartraspasos as $tras){$traspasos = $traspasos + $tras->ingreso;}
                foreach ($varcobranza as $pagos){$cobranza = $cobranza + $pagos->ingreso; }
                foreach ($vardesembolsos as $desem){$desembolsos = $desembolsos + $desem->egreso;}
                foreach ($vargastos as $gast){$gastos = $gastos + $gast->egreso; }
                foreach ($varentregas as $entr){ $entregas = $entregas + $entr->egreso;}
                foreach ($varcancelaciones as $can){ $cancelaciones = $cancelaciones + $can->egreso;}
                
                
                //calculo de efectivo ingresado 
                    foreach($varaqueo as $arqueocaja){
                        $estado = $arqueocaja->estado;
                        $id_arqueo = $arqueocaja->id;
                        $saldo_actual = $arqueocaja->saldo_actual;
                        $saldo_inicial = $arqueocaja->saldo_inicial;

                        $catidad_diez = $arqueocaja->diez;
                        $catidad_cinco = $arqueocaja->cinco;
                        $catidad_dos = $arqueocaja->dos;
                        $catidad_uno = $arqueocaja->uno;
                        $catidad_cincuentacentavos = $arqueocaja->cincuentacentavos;

                        $total_diez = $diez * $catidad_diez;
                        $total_cinco = $cinco * $catidad_cinco;
                        $total_dos = $dos * $catidad_dos;
                        $total_uno = $uno * $catidad_uno;
                        $total_cincuentacentavos = $cincuentacentavos * $catidad_cincuentacentavos;
                
                        $catidad_mil = $arqueocaja->mil;
                        $catidad_quinientos = $arqueocaja->quinientos;
                        $catidad_doscientos = $arqueocaja->doscientos;
                        $catidad_cien = $arqueocaja->cien;
                        $catidad_cincuenta = $arqueocaja->cincuenta;
                        $catidad_veinte = $arqueocaja->veinte;

                        $total_mil = $mil * $catidad_mil;
                        $total_quinientos = $quinientos * $catidad_quinientos;
                        $total_doscientos = $doscientos * $catidad_doscientos;
                        $total_cien =  $cien * $catidad_cien;
                        $total_cincuenta = $cincuenta * $catidad_cincuenta;
                        $total_veinte = $veinte * $catidad_veinte;
                    }

                //calculo de cantidades del sistema
                $total_ingresos = $saldo_inicial + $cobranza +  $traspasos;
                $total_egresos = $desembolsos + $gastos + $entregas + $cancelaciones;
                $total_calculado = $total_ingresos - $total_egresos;
                $total_ingresado = ($total_diez + $total_cinco + $total_dos + $total_uno + $total_cincuentacentavos) + ($total_mil + $total_quinientos + $total_doscientos + $total_cien + $total_cincuenta + $total_veinte);

                //calculo de diferencias
                if($total_calculado > $total_ingresado) {
                    $diferencia = $total_calculado - $total_ingresado;
                }elseif($total_calculado < $total_ingresado) {
                    $diferencia = $total_ingresado - $total_calculado;
                }else {
                    $diferencia = 0;
                }

                return view('Tesoreria.Movimientos.ControlArqueo.arqueo',compact('varpantallas','varsubmenus','vartraspasos','varcobranza','vardesembolsos','vargastos', 'varentregas','varcancelaciones', 'id', 'tipo','empresaid', 'fecha','saldo_actual', 'saldo_inicial', 'nombre',  'traspasos', 'cobranza',  'total_ingresos', 'desembolsos','gastos', 'entregas','cancelaciones','total_egresos','total_calculado','total_ingresado','diferencia',
                'catidad_veinte','catidad_cincuenta','catidad_cien','catidad_doscientos','catidad_quinientos','catidad_mil','catidad_cincuentacentavos','catidad_uno','catidad_dos','catidad_cinco','catidad_diez','estado','id_arqueo'));
              
                
        } catch(\Illuminate\Database\QueryException $ex){return back()->with("warningBD","No se logro"); } 
    }

    public function EditarEfectivo(int $id_caja, int $id_arqueo,int $id_efect, Request $request){
        try{
            $varcajas = $this->obtenerCajasxId($id_caja); 
            foreach ($varcajas as $cajas){$saldo_actual = $cajas->saldo_actual;$saldo_inicial = $cajas->saldo_inicial;$nombre = $cajas->nombre;}
            $date = Carbon::now();
            $fecha = $date->format('Y-m-d');
            // $fecha = "2023-12-07";
            $varaqueo = $this->obtenerArqueoCajas($fecha,$id_caja);
            $vartraspasos = $this->obtenerTraspasosDiarios($id_caja,$fecha);
            $varcobranza = $this->obtenerPagosDiarios($id_caja,$fecha);
            $vardesembolsos = $this->obtenerDesembolsosDiarios($id_caja,$fecha);
            $vargastos = $this->obtenerGastosDiarios($id_caja,$fecha);
            $varentregas = $this->obtenerEntregasDiarias($id_caja,$fecha);
            $varcancelaciones = $this->obtenerCancelacioneDiarias($id_caja,$fecha);

            //variables a calcular
            $traspasos = 0; $cobranza = 0; $desembolsos = 0; $gastos = 0; $entregas = 0;$cancelaciones = 0;
            //monedas
            $diez = 10; $cinco = 5; $dos = 2; $uno = 1; $cincuentacentavos = 0.50;
            //billetes
            $mil = 1000;  $quinientos = 500; $doscientos = 200; $cien = 100; $cincuenta = 50; $veinte = 20;

            foreach ($vartraspasos as $tras){$traspasos = $traspasos + $tras->ingreso;}
            foreach ($varcobranza as $pagos){$cobranza = $cobranza + $pagos->ingreso; }
            foreach ($vardesembolsos as $desem){$desembolsos = $desembolsos + $desem->egreso;}
            foreach ($vargastos as $gast){$gastos = $gastos + $gast->egreso; }
            foreach ($varentregas as $entr){ $entregas = $entregas + $entr->egreso;}
            foreach ($varcancelaciones as $can){ $cancelaciones = $cancelaciones + $can->egreso;}

            //calculo de cantidades del sistema
            $total_ingresos = $saldo_inicial + $cobranza +  $traspasos;
            $total_egresos = $desembolsos + $gastos + $entregas + $cancelaciones;
            $total_calculado = $total_ingresos - $total_egresos;


            //calculo de efectivo ingresado 
        
            $catidad_diez = $request->get('diez');
            $catidad_cinco = $request->get('cinco');
            $catidad_dos = $request->get('dos');
            $catidad_uno = $request->get('uno');
            $catidad_cincuentacentavos = $request->get('cincuentacentavos');
            
            $total_diez = $diez * $catidad_diez;
            $total_cinco = $cinco * $catidad_cinco;
            $total_dos = $dos * $catidad_dos;
            $total_uno = $uno * $catidad_uno;
            $total_cincuentacentavos = $cincuentacentavos * $catidad_cincuentacentavos;

            $catidad_mil = $request->get('mil');
            $catidad_quinientos = $request->get('quinientos');
            $catidad_doscientos = $request->get('doscientos');
            $catidad_cien = $request->get('cien');
            $catidad_cincuenta = $request->get('cincuenta');
            $catidad_veinte = $request->get('veinte');

            $total_mil = $mil * $catidad_mil;
            $total_quinientos = $quinientos * $catidad_quinientos;
            $total_doscientos = $doscientos * $catidad_doscientos;
            $total_cien =  $cien * $catidad_cien;
            $total_cincuenta = $cincuenta * $catidad_cincuenta;
            $total_veinte = $veinte * $catidad_veinte;
    

            $total_ingresado = ($total_diez + $total_cinco + $total_dos + $total_uno + $total_cincuentacentavos) + ($total_mil + $total_quinientos + $total_doscientos + $total_cien + $total_cincuenta + $total_veinte);

            //calculo de diferencias
            if($total_calculado > $total_ingresado) {
                $diferencia = $total_calculado - $total_ingresado;
            }elseif($total_calculado < $total_ingresado) {
                $diferencia = $total_ingresado - $total_calculado;
            }else {
                $diferencia = 0;
            }

            //si no se ha hecho el arqueo de ese  día se insertaran todos los detalles
            $estado = "Creado";
            $arqueo = arqueocajas::find($id_arqueo);
            $arqueo->id_caja = $id_caja;
            $arqueo->estado = $estado;
            $arqueo->realizado  = auth()->user()->idempleado;
            $arqueo->fecha = $fecha;
            $arqueo->saldo_inicial = $saldo_inicial;
            $arqueo->saldo_actual = $saldo_actual;
            $arqueo->total_traspasos = $traspasos;
            $arqueo->total_cobranza = $cobranza;
            $arqueo->total_ingresos = $total_ingresos;
            $arqueo->total_desembolsos =  $desembolsos;
            $arqueo->total_gastos =  $gastos;
            $arqueo->total_entregas =  $entregas;
            $arqueo->total_cancelaciones =  $cancelaciones;
            $arqueo->total_egresos =  $total_egresos;
            $arqueo->total_calculado =  $total_calculado;
            $arqueo->diferencia =  $diferencia;
            $arqueo->total_ingresado =  $total_ingresado;
            $arqueo->created_by = auth()->user()->name;
            $arqueo->save();

            
            $arqueorelacion = arqueorelacion_efect::find($id_efect);
            $arqueorelacion->id_arqueo  = $id_arqueo;
            $arqueorelacion->mil = $request->get('mil');
            $arqueorelacion->quinientos = $request->get('quinientos');
            $arqueorelacion->doscientos = $request->get('doscientos');
            $arqueorelacion->cien = $request->get('cien');
            $arqueorelacion->cincuenta = $request->get('cincuenta');
            $arqueorelacion->veinte =  $request->get('veinte');
            $arqueorelacion->diez = $request->get('diez');
            $arqueorelacion->cinco =  $request->get('cinco');
            $arqueorelacion->dos = $request->get('dos');
            $arqueorelacion->uno = $request->get('uno');
            $arqueorelacion->cincuentacentavos = $request->get('cincuentacentavos');
            $arqueorelacion->created_by = auth()->user()->name;
            $arqueorelacion->save();

            return back()->with("seccesso", "arqueo actualizado");
        } catch(\Illuminate\Database\QueryException $ex){return back()->with("warningBD","No se logro"); }     
    }

    public function cancelarGasto(string $tipo, int $id_tipo, Request $request){
        try{
                $id_movmiento = $request->get("id_mov");
                $monto = $request->get("monto");
                $cuenta = "";
                $caja = "";
                $concepto = $request->get("concepto");
                $fecha = $request->get("fecha");

                if($tipo == "Cajas" || $tipo == "Cajas Chicas" ){
                    $arqueo = historial_cajas::find($id_movmiento);
                    $arqueo->estado = "C";
                    $arqueo->updated_by = auth()->user()->name;
                    $arqueo->save();
                    $caja = $id_tipo;

                }else{
                    $arqueo = historial_cuentas::find($id_movmiento);
                    $arqueo->estado = "C";
                    $arqueo->updated_by = auth()->user()->name;
                    $arqueo->save();
                    $cuenta = $id_tipo;
                }


                if($tipo == "Cuentas"){
                    $varobtenercuentas =$this->obtenercuentasPrincipales($cuenta);
                    foreach($varobtenercuentas as $varobtenercuenta){$saldoCuenta = $varobtenercuenta->saldo_actual;$nomCuenta = $varobtenercuenta->descripcion;}

                        $saldoFinal = $saldoCuenta + $monto;
                        $historialcuent = new  historial_cuentas();
                        $historialcuent->id_cuenta = $cuenta;
                        $historialcuent->id_empleado =auth()->user()->idempleado;
                        $historialcuent->estado = "A";
                        $historialcuent->tipo_movimiento = "INGRESO";
                        $historialcuent->concepto = "INGRESO POR CANCELACIÓN DE GASTO";
                        $historialcuent->descripcion = $request->get('descripcion');
                        $historialcuent->responsable = "EMPLEADO #".auth()->user()->idempleado;
                        $historialcuent->ingreso = $monto;
                        $historialcuent->egreso = 0;
                        $historialcuent->saldo =  $saldoFinal;
                        $historialcuent->numero_referencia = $id_movmiento;
                        $historialcuent->tipo_referencia = "tbl_movmiento";
                        $historialcuent->numero_poliza = 0;
                        $historialcuent->fecha = $fecha;
                        $historialcuent->created_by = auth()->user()->name;
                    
                    if($historialcuent->save()){

                        $movcuenta =$this->obtenerultimomovcuenta();
                        foreach($movcuenta as $cue){$movId = $cue->id;}

                        $poliza = historial_cuentas::find($movId);
                        $poliza->numero_poliza = "CU00".$movId;
                        $poliza->updated_by = auth()->user()->name;
                        $poliza->save();

                        $cuenta = cuentas::find($cuenta);
                        $cuenta->saldo_actual = $saldoFinal;
                        $cuenta->updated_by = auth()->user()->name;
                        $cuenta->save();

                        return back()->with("success","¡Se guardaron los cambios correctamente!");
                    }else{ return back()->with("warning","No se logro");} 

                }else{
                    $varcajas = $this->obtenerCajasxId($caja);
                    foreach ($varcajas as $cajas){$saldoCaja = $cajas->saldo_actual;$nombreCaja = $cajas->nombre;}

                        $saldoFinal = $saldoCaja + $monto;
                        $historialcuent = new  historial_cajas();
                        $historialcuent->id_caja = $caja;
                        $historialcuent->id_empleado =auth()->user()->idempleado;
                        $historialcuent->estado = "A";
                        $historialcuent->tipo_movimiento = "INGRESO";
                        $historialcuent->concepto = "INGRESO POR CANCELACIÓN DE GASTO";
                        $historialcuent->descripcion = $request->get('descripcion');
                        $historialcuent->responsable = "EMPLEADO #".auth()->user()->idempleado;
                        $historialcuent->ingreso = $monto;
                        $historialcuent->egreso = 0;
                        $historialcuent->saldo =  $saldoFinal;
                        $historialcuent->numero_referencia = $id_movmiento;
                        $historialcuent->tipo_referencia = "tbl_movmiento";
                        $historialcuent->numero_poliza = 0;
                        $historialcuent->fecha = $fecha;
                        $historialcuent->created_by = auth()->user()->name;
                    
                    if($historialcuent->save()){

                        $movcaja =$this->obtenerultimomovcaja();
                        foreach($movcaja as $caj){$movId = $caj->id;}

                        $poliza = historial_cajas::find($movId);
                        $poliza->numero_poliza = "CJ00".$movId;
                        $poliza->updated_by = auth()->user()->name;
                        $poliza->save();

                        $Cajas = Cajas::find($caja);
                        $Cajas->saldo_actual = $saldoFinal;
                        $Cajas->updated_by = auth()->user()->name;
                        $Cajas->save();

                        return back()->with("success","¡Se guardaron los cambios correctamente!");
                    }else{ return back()->with("warning","No se logro");}  
                }
               


        } catch(\Illuminate\Database\QueryException $ex){return back()->with("warningBD","No se logro"); }
    }

    public function reportePosicionFinanciera()
    {
        $id_user = auth()->user()->id;
        $modulo = "movimientos_dinero";
        $varpantallas =  $this->Traermenuenc();
        $varsubmenus =   $this->Traermenudet();
        $varValidaPermisoCuentas =  $this->validaPermisoCuentas($id_user, $modulo);

        if ($varValidaPermisoCuentas->isEmpty()) {
            $varValidaPermisoCuentas = "no";
        }

        $cuentasActivas = DB::table('tblcuentas')
            ->where('status', 'A')
            ->sum('saldo_actual');

        $cajasActivas = DB::table('tblcajas')
            ->where('status', 'A')
            ->sum('saldo_actual');

        $activos = $cuentasActivas + $cajasActivas;
        $pasivos = 0;
        $capital = $activos - $pasivos;

        return view('Tesoreria.Movimientos.Reportes.posicion-financiera', compact(
            'varpantallas',
            'varsubmenus',
            'varValidaPermisoCuentas',
            'cuentasActivas',
            'cajasActivas',
            'activos',
            'pasivos',
            'capital'
        ));
    }

    public function reporteEstadoCuenta()
    {
        $id_user = auth()->user()->id;
        $modulo = "movimientos_dinero";
        $varpantallas =  $this->Traermenuenc();
        $varsubmenus =   $this->Traermenudet();
        $varValidaPermisoCuentas =  $this->validaPermisoCuentas($id_user, $modulo);

        if ($varValidaPermisoCuentas->isEmpty()) {
            $varValidaPermisoCuentas = "no";
        }

        return view('Tesoreria.Movimientos.Reportes.estado-cuenta', compact(
            'varpantallas',
            'varsubmenus',
            'varValidaPermisoCuentas'
        ));
    }

    public function reporteArqueoCajas()
    {
        $id_user = auth()->user()->id;
        $modulo = "movimientos_dinero";
        $varpantallas =  $this->Traermenuenc();
        $varsubmenus =   $this->Traermenudet();
        $varValidaPermisoCuentas =  $this->validaPermisoCuentas($id_user, $modulo);

        if ($varValidaPermisoCuentas->isEmpty()) {
            $varValidaPermisoCuentas = "no";
        }

        return view('Tesoreria.Movimientos.Reportes.arqueo-cajas', compact(
            'varpantallas',
            'varsubmenus',
            'varValidaPermisoCuentas'
        ));
    }
}
