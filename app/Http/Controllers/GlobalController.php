<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\MenuTrait;
use App\Traits\GlobalTraits;
use App\Traits\SistemasTraits;
use App\Traits\DatosimpleTraits;
use App\Models\distribuidores_valeras;
use App\Models\distribuidores;
use Carbon\Carbon;
use DB;

class GlobalController extends Controller
{

    use MenuTrait;
    use DatosimpleTraits;
    use SistemasTraits;
    use GlobalTraits;
    
    public function __construct(){
        $this->middleware('auth');
    }

    public function verglobal(){
        try
        {
        $idusuario=auth()->user()->id;
        $saldoatr = 0;
        $saldoactualpagos =  $this->saldoactualpagos();
        $saldoactualceros =$this->saldoactualceros($idusuario);
        //saldo_riesgo
        $saldoenriesgo = $this->saldoRiesgoTotal($idusuario);
        $saldor = 0;
        $porsaldoenriesgo = 0;
        $saldor_incompleto = 0;
        //mora
        $saldoatra = $this->moraTotal($idusuario,$idusuario);
        $saldoatr = 0;
        $pormora = 0;
        $saldoatr_incompleto = 0;
        $pagos_total = 0;
        $total_pres = 0;
        $varpromotores =  $this->obtenerEmpleadosCordProm();
        $permiso = $this->forpermisos('reasignar');
        $permiso1 = $this->forpermisos('exportar_globalVales');
               
                //saldo actual
                if($saldoactualceros != null){
                    foreach($saldoactualceros as $sl){ 
                        $total_pres = $sl->Saldo_actual;
                    } 

                    foreach($saldoactualpagos as $sp){ 
                        $pagos_total = $sp->pagos_total;
                    } 

                    $saldoact = $total_pres - $pagos_total;
                }


                //saldo en riesgo
                if($saldoenriesgo != null){
                    $prestamo = 0; 
                    $pagado = 0;

                    foreach($saldoenriesgo as $s){
                       $prestamo = $prestamo + $s->prestamo;
                       $pagado = $pagado + $s->pagado;
                    }  
                    $saldor = $prestamo - $pagado;
                }

                //% saldo en riesgo
                if($saldor > 0){
                    $porsaldoenriesgo = ($saldor)/($saldoact);
                }

                
                //mora
                if($saldoatra != null){
                    foreach($saldoatra as $saldo)
                    {
                        $saldoatr = $saldo->mora;
                        // $saldoatr = $saldo->saldoatrasado;
                        // $saldoatr_incompleto = $saldo->incompleto;
                }  
                }

                //% mora
                // $saldoatr = $saldoatr + $saldoatr_incompleto;
                $saldoatr = $saldoatr;
                if($saldoatr > 0){
                    $pormora = ($saldoatr)/($saldoact);
                }

                $idusuario=auth()->user()->id;
                $varobtienesuc = $this->obtenersucursalxidusuario($idusuario);
                $varpantallas =  $this->Traermenuenc();
                $varsubmenus =   $this->Traermenudet();
                $global1 = $this->global1();
                $global2 = $this->global2();
                $global3 = $this->global3();
                $diasatraso =$this-> obtenerdiasatraso();
                $totalprestamo = $this->total_prestamos();
                
                $pagoalcorte = DB::select('select a.id_distribuidor, sum(a.pago)as pagorelacionadoalcorte from(
                select id_distribuidor, sum(saldo_pagar)as pago, tbldistribuidores.id_responsable
                from tblpagos_enc
                inner join tbldistribuidores on tblpagos_enc.id_distribuidor = tbldistribuidores.id
                where estado = "N"
                and fecha_relacion = (select fecha_relacion from tblpagos_enc order by fecha_relacion desc limit 1)
                group by id_distribuidor) a
                group by a.id_distribuidor; ');
                
                
                return view('Global.global',compact('varpantallas','varsubmenus','permiso1','permiso','varpromotores','global1','global2','global3','saldoact','saldor','porsaldoenriesgo','saldoatr','pormora','varobtienesuc','pagoalcorte'));
        } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    }

    public function verglobalcli(int $id){
        try{
           $permiso1 = $this->forpermisos('exportar_globalDetalleCli'); 
            $idusuario=auth()->user()->id;
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();
            $globalcli = $this->obtenerglocliente($id);
            $globalcli2 = $this->obtenerinterescli();
            $globalcli3 = $this->obtenercobercli();
            $detalledis = $this->obtenerdetalledistribuidor($id);
            $total = $this->totalplazo($id);
            $canejados= $this->canejados($id);
            $saldo_riesgoxdis=$this->saldo_riesgoxdis($id);
            $obtenersaldoclientes=$this->obtenersaldoclientes($id);
            // $saldoactualxdis=$this->saldoactualxdis($id);
            $moraxdis = $this->moraxdis($id);
            $saldoactualxdistri = 0;
            $abono = 0;
            $toral_p = 0;
            $saldor = 0;
            $mora = 0;

            //saldo actual
            $total_prest=DB::select('SELECT tblprestamos_valesenc.id, tblprestamos_valesenc.pago_totalredondeado from tblprestamos_valesenc 
            INNER JOIN tblprestamos_valesdet ON tblprestamos_valesenc.id=tblprestamos_valesdet.idprestamo_vales 
            INNER JOIN tblclientes_vales ON tblclientes_vales.id=tblprestamos_valesenc.idcliente 
            WHERE tblclientes_vales.iddistribuidor = ? and tblprestamos_valesenc.status = "A" 
            GROUP by tblprestamos_valesenc.id;',[$id]);

            $total_abonado=DB::select('SELECT sum(tblpagos_enc.monto_total) as abonado from tblpagos_enc 
            WHERE tblpagos_enc.id_distribuidor = ?;',[$id]);

            foreach($total_prest as $pres){
                $toral_p = $toral_p + $pres->pago_totalredondeado;
            }

            foreach($total_abonado as $abo){
                $abono = $abono + $abo->abonado;
            }

            $saldoactualxdistri= $toral_p-$abono;

       
            //saldo_riesgo
            $saldo_riesgo=DB::select('select 
            tblpagos_enc.id_distribuidor iddis,
            datediff(date(now()), DATE_ADD(tblpagos_enc.fecha_relacion, INTERVAL 5 DAY)) as dias,
            (select SUM(tblprestamos_valesenc.pago_totalredondeado)
            from tblprestamos_valesenc 
            INNER JOIN tblclientes_vales on tblclientes_vales.id = tblprestamos_valesenc.idcliente
            where tblprestamos_valesenc.status = "A" AND tblclientes_vales.iddistribuidor = iddis
            and datediff(date(now()), DATE_ADD(tblpagos_enc.fecha_relacion, INTERVAL 5 DAY)) >= 7) AS prestamo,
            (select sum(tblpagos_enc.monto_total) from tblpagos_enc where tblpagos_enc.id_distribuidor = iddis) as pagado 
            from tblpagos_enc
            where 
            datediff(date(now()), DATE_ADD(tblpagos_enc.fecha_relacion, INTERVAL 5 DAY)) >= 7 AND tblpagos_enc.id_distribuidor = ?
            AND tblpagos_enc.status_atraso = "A" || tblpagos_enc.otrosconceptos1 = 0  GROUP BY tblpagos_enc.id_distribuidor;',[$id]);

            
            if($saldo_riesgo != null){
                $prestamo = 0; 
                $pagado = 0;

                foreach($saldo_riesgo as $s){
                    if($s->iddis == $id && $s->prestamo > 0 && $s->dias >= 7){
                        $prestamo = $prestamo + $s->prestamo;
                        $pagado = $pagado + $s->pagado;
                    }
                }  
                $saldor = $prestamo - $pagado;
            }

            //mora
            if($moraxdis != null){
                foreach($moraxdis as $saldo){$saldoatr = $saldo->saldoatrasado;}  
            }
            $mora = $saldoatr;
    
            return view('Global.globlaxcli',compact('varpantallas','varsubmenus','permiso1','globalcli','globalcli2','globalcli3','total','detalledis','obtenersaldoclientes','canejados','saldo_riesgoxdis','saldoactualxdistri','saldor','mora'));
        } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    }

    public function verglobalnom(){
        $capital=0;
        $abona=0;
        $saldo_actual = 0;
            $idusuario=auth()->user()->id;
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();
            $globlaprestamosenc = $this->globaleprestamoenc();
            $globlaprestamossaldos = $this->globalprestamosnomsaldos();
            $saldosgenericos = $this->saldosgeneralesprenom();
            $sadovencidoprenom =$this->saldovencidoprenom();
            $abonado=$this->prestmoabonadoglobal();
            $clientevprenom =$this->clientevprenom();
            $empleadosatraso = $this->prestamosempleadosatraso();
            $saldotdiastraso = $this->saldosydiastraso();

           foreach($saldosgenericos as $capitales)
           {
            $capital=$capitales->capital_Total;
           }
           foreach($abonado as $abn)
           {
            $abona=$abn->abonado;
           }
           number_format($saldo_actual = $capital-$abona,2,',', '.');
           $permiso1 = $this->forpermisos('exportar_globalPresNom'); 
        return view('Global.Nominas',compact('varpantallas','varsubmenus','permiso1','globlaprestamosenc','globlaprestamossaldos','saldosgenericos','sadovencidoprenom','clientevprenom','abonado','saldo_actual','empleadosatraso','saldotdiastraso'));
    }
    
    public function reasignacion(Request $request, int $iddis){
        try{
            $date = Carbon::now();
            $fecha = $date->format('Y-m-d');


            $distribuidorupdate = distribuidores::find($iddis);
            $distribuidorupdate->id_responsable = $request->get('id_responsable');
            $distribuidorupdate->coord_anterior = $request->get('id_anterior');
            $distribuidorupdate->updated_at = $date;
            $distribuidorupdate->updated_by= auth()->user()->name;

            if($distribuidorupdate->save()){
          
                $consulta = DB::select('update tbldistribuidor_valeras set id_coordinador = ? where tbldistribuidor_valeras.iddistribuidor = ? and status = "A";', 
                [$request->get('id_responsable'),$iddis]);

                return back()->with("success","no guardado correctamente");

            }else{
                return back()->with("warningBD","no guardado correctamente");
            }

        } catch(\Illuminate\Database\QueryException $ex){ return back()->with("warningBD","no guardado correctamente");}
    }
}
