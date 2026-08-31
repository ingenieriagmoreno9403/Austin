<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\MenuTrait;
use App\Traits\GlobalTraits;
use App\Traits\SistemasTraits;
use App\Traits\DatosimpleTraits;
use App\Models\historial_cuentas;
use App\Models\cuentas;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\cuentaHistorial;
use App\Exports\CuentasExport;



class ContabilidaController extends Controller
{
    use MenuTrait;
    use DatosimpleTraits;
    use SistemasTraits;
    use GlobalTraits;

    public function __construct(){
        $this->middleware('auth');
    }

    public function index(){
        try{
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();
            $permisos1 = $this->forpermisos('gestion_cuentas');
            return view('Tesoreria.Index',compact('varpantallas','varsubmenus','permisos1'));
        } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    }

    public function HistorialCuentas(Request $request, int $id){
        try{
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();
            $fecha_inicio = $request->get('fecha_inicio');
            $fecha_fin  = $request->get('fecha_fin');
            $obtenerHistorialCuentas =   $this->obtenerHistorialCuentas($id, $fecha_inicio, $fecha_fin);
            $obtenerCuenta =  $this->obtenercuentasPrincipales($id);
            $permisos1 = $this->forpermisos('exportar_cuenta');

            if($obtenerHistorialCuentas->isEmpty()){
                return back()->with("warningFecha","no se encontro");
            }else{
                return view('Tesoreria.Cuentas.movimientos_cuentas',compact('varpantallas','varsubmenus','obtenerHistorialCuentas','id', 'fecha_inicio', 'fecha_fin','obtenerCuenta','permisos1'));
            }
        } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    }

    public function ExportarHistorialCuenta(Request $request, int $id){
        try{
            $nombre = $request->get('nombre_cuenta');
            $fecha_inicio = $request->get('fecha_inicio');
            $fecha_fin  = $request->get('fecha_fin');
            $empresa  = $request->get('empresa');

            return Excel::download(new cuentaHistorial($id, $fecha_inicio, $fecha_fin, $nombre,$empresa), 'HISTORIAL DE CUENTA '.$nombre.' '.$fecha_inicio.'-'.$fecha_fin.'.xlsx');
        } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    }
        
    // public function transferenciaSaldo(int $cuenta, Request $request){
    //     $date = Carbon::now();
    //     $fecha = $date->format('Y-m-d');
        
    //     $varobtenercuentas =$this->obtenercuentasPrincipales($cuenta);
    //     foreach($varobtenercuentas as $varobtenercuenta){
    //         $saldoCuenta1 = $varobtenercuenta->saldo_actual;
    //         $nomCuenta1 = $varobtenercuenta->descripcion;
    //     }

    //     if($saldoCuenta1 >= $request->get('saldo_trasferir')){

    //         $saldoFinal = $saldoCuenta1 - $request->get('saldo_trasferir');
    //         $cuenta1 = cuentas::find($cuenta);
    //         $cuenta1->saldo_actual = $saldoFinal;
    //         $cuenta1->updated_by=auth()->user()->name;
    //         $cuenta1->save();

    //         $varobtenercuentas =$this->obtenercuentasPrincipales($request->get('cuenta_tranferencia'));
    //         foreach($varobtenercuentas as $varobtenercuenta){
    //             $saldoCuenta2 = $varobtenercuenta->saldo_actual;
    //             $nomCuenta2 = $varobtenercuenta->descripcion;
    //         }

    //         $saldoTransferido = $saldoCuenta2 + $request->get('saldo_trasferir');
    //         $cuenta2 = cuentas::find($request->get('cuenta_tranferencia'));
    //         $cuenta2->saldo_actual = $saldoTransferido;
    //         $cuenta2->updated_by=auth()->user()->name;
    //         $cuenta2->save();

    //         if($cuenta1->save() && $cuenta2->save()){

    //             $historialcuent = new  historial_cuentas();
    //             $historialcuent->id_cuenta = $cuenta;
    //             $historialcuent->id_empleado =auth()->user()->idempleado;
    //             $historialcuent->estado = "A";
    //             $historialcuent->tipo_movimiento = "TRANSFERENCIA";
    //             $historialcuent->concepto = "TRANSFERENCIA DESDE TESORERIA A CUENTA".$nomCuenta2;
    //             $historialcuent->responsable = "EMPLEADO #".auth()->user()->idempleado;
    //             $historialcuent->ingreso = 0;
    //             $historialcuent->egreso = $request->get('saldo_trasferir');
    //             $historialcuent->saldo =  $saldoFinal;
    //             $historialcuent->numero_referencia = $cuenta;
    //             $historialcuent->tipo_referencia = "cuenta";
    //             $historialcuent->numero_poliza = 0;
    //             $historialcuent->fecha = $fecha;
    //             $historialcuent->created_by=auth()->user()->name;
    //             $historialcuent->save();

    //             $historialcuent = new  historial_cuentas();
    //             $historialcuent->id_cuenta = $request->get('cuenta_tranferencia');
    //             $historialcuent->id_empleado =auth()->user()->idempleado;
    //             $historialcuent->estado = "A";
    //             $historialcuent->tipo_movimiento = "INGRESO";
    //             $historialcuent->concepto = "DEPOSITO POR TRANSFERENCIA DE CUENTA ".$nomCuenta1;
    //             $historialcuent->responsable = "EMPLEADO #".auth()->user()->idempleado;
    //             $historialcuent->ingreso = $request->get('saldo_trasferir');
    //             $historialcuent->egreso = 0;
    //             $historialcuent->saldo =  $saldoTransferido;
    //             $historialcuent->numero_referencia = $request->get('cuenta_tranferencia');
    //             $historialcuent->tipo_referencia = "cuenta";
    //             $historialcuent->numero_poliza = 0;
    //             $historialcuent->fecha = $fecha;
    //             $historialcuent->created_by=auth()->user()->name;
    //             $historialcuent->save();

    //             return back()->with("success","¡Se guardaron los cambios correctamente!");
    //         }else{
    //             return rback()->with("warning","No se logro");
    //         }
    //     }else{
    //         return back()->with("warningSaldo","No se logro");
    //     }
    // }
   
    
}
