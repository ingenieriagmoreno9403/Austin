<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\DatosimpleTraits;
use App\Traits\ReportesTraits;
use App\Traits\MenuTrait;
use App\Traits\SistemasTraits;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;
use App\Exports\CierresExport;
use App\Models\cortes;
use App\Models\Sucursales;
use App\Models\coordinador;
use App\Models\distribuidores;
use App\Models\Empleados;
use DB;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\catalogoGatosxEmpresa;
use App\Exports\ExportarIngresos;
use App\Exports\ExportarDesembolsos;
use App\Exports\ExportarGastos;
use App\Traits\GlobalTraits;
use Log;

class ReportesController extends Controller
{
    use MenuTrait;
    use DatosimpleTraits;
    use ReportesTraits;
    use SistemasTraits;
    use GlobalTraits;

    public function __construct()
    {
        $this->middleware('auth');
    }

    //TESORERIA
    public function indexReportesTesoreria()
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            return view('Reportes.Tesoreria.index', compact('varpantallas', 'varsubmenus'));
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function reporte_desembolsos(Request $request)
    {
        try {

            if (is_null($request->get('fecha_inicio')) || is_null($request->get('fecha_fin'))) {
                $date = Carbon::now();
                $año = $date->format('Y');
                $mes = $date->format('m');
                $diaIncio = "01";
                $diaFin = cal_days_in_month(CAL_GREGORIAN, $mes, $año);
                $fecha_inicio = Carbon::parse($año . "-" . $mes . "-" . $diaIncio)->format('Y-m-d');
                $fecha_fin = Carbon::parse($año . "-" . $mes . "-" . $diaFin)->format('Y-m-d');

            } else {
                $fecha_inicio = $request->get('fecha_inicio');
                $fecha_fin = $request->get('fecha_fin');
            }

            $estatus = $request->get('estatus');
            if (is_null($request->get('estatus'))) {
                $estatus = "A";
            }

            $sucursalId = $request->get('sucursal');
            if (is_null($request->get('sucursal'))) {
                $sucursalId = 0;
            }

            $reporte_desembolsos = $this->obtenerReporteDesembolsosFiltro($fecha_inicio, $fecha_fin, $sucursalId, $estatus);

            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $permiso1 = $this->forpermisos('exportar_reporteDesem');
            $sucursales = Sucursales::get();

            if ($reporte_desembolsos->isEmpty()) {
                return back()->with("warningFecha", "fecha no detectada");
            } else {
                return view('Reportes.Tesoreria.reporteDesembolsos', compact('varpantallas', 'varsubmenus', 'permiso1', 'reporte_desembolsos', 'fecha_inicio', 'fecha_fin', 'sucursales', 'sucursalId', 'estatus'));
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function exportarDesembolsos(string $fechaIni, string $fechaFin)
    {
        try {
            return Excel::download(new ExportarDesembolsos($fechaIni, $fechaFin), 'RESPORTE DE DESEMBOLSOS ' . $fechaIni . ' A ' . $fechaFin . '.xlsx');
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }


    public function reporte_gastos(Request $request)
    {
        $fecha_inicio = $request->get('fecha_inicio');
        $fecha_fin = $request->get('fecha_fin');
        $sucursalesId = $request->get('sucursal');
        $estado = $request->get('estado');

        if (is_null($fecha_inicio) || is_null($fecha_fin)) {
            $date = Carbon::now();
            $año = $date->format('Y');
            $mes = $date->format('m');
            $diaIncio = "01";
            $diaFin = cal_days_in_month(CAL_GREGORIAN, $mes, $año);
            $fecha_inicio = Carbon::parse($año . "-" . $mes . "-" . $diaIncio)->format('Y-m-d');
            $fecha_fin = Carbon::parse($año . "-" . $mes . "-" . $diaFin)->format('Y-m-d');
        }

        if (is_null($sucursalesId)) {
            $sucursalesId = 0;
        }

        if (is_null($estado)) {
            $estado = "A";
        }

        $reporte_gastos = $this->obtenerReporteGastosFiltros($fecha_inicio, $fecha_fin, $estado, $sucursalesId);
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $permiso1 = $this->forpermisos('exportar_reporteGast');
        $sucursales = Sucursales::get();


        return view('Reportes.Tesoreria.reporteGastos', compact('varpantallas', 'varsubmenus', 'permiso1', 'reporte_gastos', 'fecha_inicio', 'fecha_fin', 'sucursales', 'sucursalesId', 'estado'));

    }

    public function exportarGastos(string $fechaIni, string $fechaFin)
    {
        // try {
            return Excel::download(new ExportarGastos($fechaIni, $fechaFin), 'RESPORTE DE GASTOS ' . $fechaIni . ' A ' . $fechaFin . '.xlsx');
        // } catch (\Illuminate\Database\QueryException $ex) {
        //     return back()->with("warning", "no guardado correctamente");
        // }
    }


    public function reporte_ingresos(Request $request)
    {

        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $sucursales = Sucursales::get();
        $fecha_inicio = $request->get('fecha_inicio');
        $fecha_fin = $request->get('fecha_fin');
        $sucursalId = $request->get('sucursal');

        if (is_null($fecha_inicio) || is_null($fecha_fin)) {
            $date = Carbon::now();
            $año = $date->format('Y');
            $mes = $date->format('m');
            $diaIncio = "01";
            $diaFin = cal_days_in_month(CAL_GREGORIAN, $mes, $año);
            $fecha_inicio = Carbon::parse($año . "-" . $mes . "-" . $diaIncio)->format('Y-m-d');
            $fecha_fin = Carbon::parse($año . "-" . $mes . "-" . $diaFin)->format('Y-m-d');
        }

        if ($sucursalId == 0) {
            $sucursalId = null;
        }

        $reporte_ingresos = $this->obtenerReporteIngresosFiltros($fecha_inicio, $fecha_fin, $sucursalId);
        $permiso1 = $this->forpermisos('exportar_reporteIngr');
        // $obtenerinteresxingreso =$this->obtenerinteresxingreso($fecha_inicio, $fecha_fin);

        /*if ($reporte_ingresos->isEmpty()) {
            return back()->with("warningFecha", "fecha no detectada");
        } else {*/
        return view('Reportes.Tesoreria.reporteIngresos', compact('varpantallas', 'varsubmenus', 'permiso1', 'reporte_ingresos', 'fecha_inicio', 'fecha_fin', 'sucursales', 'sucursalId'));
        //}
    }

    public function exportarIngresos(string $fechaIni, string $fechaFin)
    {
        try {
            return Excel::download(new ExportarIngresos($fechaIni, $fechaFin), 'RESPORTE DE INGRESOS ' . $fechaIni . ' A ' . $fechaFin . '.xlsx');
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    //ADMINISTRACION
    public function indexAdministracion()
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();

            $permiso1 = $this->forpermisos('ver_reporteCierres');
            $permiso2 = $this->forpermisos('ver_reporteCoord');
            $permiso3 = $this->forpermisos('ver_reporteSuc');
            $permiso4 = $this->forpermisos('ver_reporteCanjes');


            return view('Reportes.Administracion.index', compact('varpantallas', 'varsubmenus', 'permiso1', 'permiso2', 'permiso3', 'permiso4'));
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function reporte_cierres()
    {

        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $permiso1 = $this->forpermisos('exportar_reporteCierres');
        $date = Carbon::now();//->addDays(-2)
        $fecha = $date;
        $añoactual = $fecha->year;
        $mesactual = $fecha->month;
        $diaactual = $fecha->day;
        $varfechacorteini = "";
        $varfechacortefin = "";
        $fechainicorteabonado = "";
        $fechafincorteabonado = "";
        $mesanterior = 0;
        $vista = "no";
        $global3 = $this->global3();

        if ($diaactual == 15 || $diaactual == 16) {
            $mesanterior = $mesactual - 1;
            $caractermesanterior = strlen($mesanterior);
            $caractermesactual = strlen($mesactual);
            $vista = "si";


            if (strlen($caractermesanterior) == 1) {
                $mesanterior = "0" . $mesanterior;
            }

            if (strlen($caractermesactual) == 1) {
                $mesactual = "0" . $mesactual;
            }


            $varfechacorteini = $añoactual . "-" . $mesanterior . "-23";
            $varfechacortefin = $añoactual . "-" . $mesactual . "-07";

            $fechainicorteabonado = $añoactual . "-" . $mesanterior . "-29";
            $fechafincorteabonado = $añoactual . "-" . $mesactual . "-15";

        } elseif ($diaactual == 30 || $diaactual == 31 || $diaactual == 01) {
            $caractermesanterior = strlen($mesanterior);
            $caractermesactual = strlen($mesactual);
            $vista = "si";

            if (strlen($caractermesanterior) == 1) {
                $mesanterior = "0" . $mesanterior;
            }

            if (strlen($caractermesactual) == 1) {
                $mesactual = "0" . $mesactual;
            }

            $varfechacorteini = $añoactual . "-" . $mesactual . "-08";
            $varfechacortefin = $añoactual . "-" . $mesactual . "-22";

            $fechainicorteabonado = $añoactual . "-" . $mesactual . "-14";
            $fechafincorteabonado = $añoactual . "-" . $mesactual . "-" . $diaactual;

            $caractermesactual = 0;
        }


        $consulta_1 = DB::select('select 
                su.id,
                su.nombre as sucursal,
                em.id as id_coordinador,
                concat(em.primer_nombre," ",em.segundo_nombre," ",em.apellido_materno," ",em.apellido_paterno) as coordinador,
                dis.id as n_contrato,
                dis.id as id_distribuidor,
                concat(dis.primer_nombre," ", dis.segundo_nombre," ",dis.apellido_paterno," ",dis.apellido_materno) as distribuidor,
                (SELECT tblprestamos_valesenc.fecha_canje  FROM tblprestamos_valesenc 
                    INNER JOIN tblclientes_vales on tblprestamos_valesenc.idcliente = tblclientes_vales.id 
                    WHERE tblclientes_vales.iddistribuidor = n_contrato ORDER by tblclientes_vales.id ASC LIMIT 1
                ) as fecha_activacion,
                edis.nombre,
                dis.capital_autorizado ,
                (SELECT sum(tblprestamos_valesenc.monto_vale)  FROM tblprestamos_valesenc 
                    INNER JOIN tblclientes_vales on tblprestamos_valesenc.idcliente = tblclientes_vales.id 
                    WHERE tblclientes_vales.iddistribuidor = n_contrato 
                ) as capital_activo,
                dis.capital,
                sum(tenc.pagototalintereses) as intereses,
                sum(tenc.ivainteres) as iva_intereses,
                sum(tenc.numero_plazos) as total,
                sum(tenc.numero_plazos*4.2) as cobertura,
                sum(tenc.numero_plazos*0.8) as iva_cobertura,
                ROUND(sum(tenc.redondeototalcentavos/100),2)as otros,
                count(cliv.id) as clientes_vigentes,
                sum(tenc.monto_vale) as capital_desembolsado
                from tblempleados em
                inner join tblpuestos pu on  pu.id = em.idpuesto
                inner join tbldistribuidores dis on dis.id_responsable = em.id
                inner join tblclientes_vales cliv on cliv.iddistribuidor = dis.id
                inner join tblprestamos_valesenc tenc on tenc.idcliente = cliv.id
                inner join tblstatus_distribuidor edis on edis.id = dis.idstatus
                inner join tblsucursales su on su.id = dis.idsucursal
                where cliv.status = "A" and tenc.status = "A"
            group by dis.id;');

        $consulta_2 = DB::select('select cliv.iddistribuidor,
                case when datediff(date(now()),pdet.fecha_pago) <= 5 then 0 else
                sum(pdet.pago_total) end  as saldo_riesgo
                from tblclientes_vales cliv
                inner join tblprestamos_valesenc penc on cliv.id = penc.idcliente
                inner join tblprestamos_valesdet pdet on pdet.idprestamo_vales = penc.id
                where pdet.status = "N" || pdet.status is null
            group by cliv.iddistribuidor;');

        $consulta_4 = DB::select('select tblclientes_vales.iddistribuidor, 
                tblprestamos_valesdet.fecha_pago AS fecha_ultimopago,
                sum(tblprestamos_valesdet.pago_total) as saldoatrasado,
                DATEDIFF(DATE(NOW()),tblprestamos_valesdet.fecha_pago) as dias_atraso
                from tbldistribuidor_valeras 
                INNER JOIN tblclientes_vales on tblclientes_vales.iddistribuidor = tbldistribuidor_valeras.iddistribuidor 
                INNER JOIN tblprestamos_valesenc on tblprestamos_valesenc.idcliente = tblclientes_vales.id 
                INNER JOIN tblprestamos_valesdet on tblprestamos_valesdet.idprestamo_vales = tblprestamos_valesenc.id 
                WHERE tblprestamos_valesdet.fecha_pago < DATE(NOW()) >=6
                and tblprestamos_valesdet.status ="N" or tblprestamos_valesdet.status = "C" or tblprestamos_valesdet.status = null GROUP by tblclientes_vales.iddistribuidor
                union
                select  dis.id,
                enc.fecha_pago,
                sum(enc.otrosconceptos2) as saldoatrasado,
                case when  sum(enc.otrosconceptos2) > 0 THEN  DATEDIFF(DATE(NOW()),enc.fecha_pago) else 0 end as dias_atraso
                from tblpagos_enc enc
                inner join tbldistribuidores dis on dis.id = enc.id_distribuidor
                where enc.estado = "P" and dis.id not in (select tblclientes_vales.iddistribuidor
                from tbldistribuidor_valeras 
                INNER JOIN tblclientes_vales on tblclientes_vales.iddistribuidor = tbldistribuidor_valeras.iddistribuidor 
                INNER JOIN tblprestamos_valesenc on tblprestamos_valesenc.idcliente = tblclientes_vales.id 
                INNER JOIN tblprestamos_valesdet on tblprestamos_valesdet.idprestamo_vales = tblprestamos_valesenc.id 
                WHERE tblprestamos_valesdet.fecha_pago < DATE(NOW())
                and tblprestamos_valesdet.status ="N" or tblprestamos_valesdet.status = "C" or tblprestamos_valesdet.status = null GROUP by tblclientes_vales.iddistribuidor
            ) group by dis.id;');

        $consulta_5 = DB::select('select tblclientes_vales.iddistribuidor as iddis,  
                (select sum(tblprestamos_valesenc.pago_totalredondeado) - sum(tblpagos_enc.monto_total) from tblpagos_enc where tblpagos_enc.id_distribuidor = iddis)  as saldo
                from tblclientes_vales 
                inner join tblprestamos_valesenc on tblprestamos_valesenc.idcliente = tblclientes_vales.id
            group by tblclientes_vales.iddistribuidor;');

        $consulta_6 = DB::select('select dis.id as dis,sum(pdet.monto) as capital_pagado from tbldistribuidores dis 
                inner join tblpagos_enc pecn on pecn.id_distribuidor = dis.id
                inner join tblpagos_det pdet on pdet.idpagoenc = pecn.id
                where pdet.idconcepto = 1
                group by dis.id
                union 
                select id, 0 as capital_pagado from tbldistribuidores where id not in(select dis.id from tbldistribuidores dis 
                inner join tblpagos_enc pecn on pecn.id_distribuidor = dis.id
                inner join tblpagos_det pdet on pdet.idpagoenc = pecn.id
                where pdet.idconcepto = 1
            group by dis.id);');

        $consulta_7 = DB::select('select tblpagos_enc.id_distribuidor, sum(tblpagos_enc.saldo_pagar)as pagorelacionadoalcorte 
                from tblpagos_enc
                inner join tbldistribuidores on tblpagos_enc.id_distribuidor = tbldistribuidores.id
                where  tblpagos_enc.estado = "N" and tblpagos_enc.fecha_corte_inicio = ? and tblpagos_enc.fecha_corte_final = ?
            group by tblpagos_enc.id_distribuidor;', [$varfechacorteini, $varfechacortefin]);


        //  abonado
        // $consulta_3 = DB::select('select dis.id, sum(enc.monto_total) as abonado from 
        //     tblpagos_enc enc
        //     inner join tbldistribuidores dis on dis.id = enc.id_distribuidor
        //     group by dis.id
        //     union 
        //     SELECT id, 0 as abonado FROM tbldistribuidores WHERE idstatus = 12 and id not in (select dis.id as abonado from 
        //     tblpagos_enc enc
        //     inner join tbldistribuidores dis on dis.id = enc.id_distribuidor
        // group by dis.id);');

        $consulta_3 = db::select("select id_distribuidor as id, sum(monto_total) abonado from tblpagos_enc 
            where fecha_pago between ? and ? group by id_distribuidor;", [$fechainicorteabonado, $fechafincorteabonado]);

        //  EL MAXIMO DE DIAS EN ATRASO REGISTRADO EN SU HISTORIAL DE PAGOS
        $diasatrasomaximo = DB::select("select iddistribuidor, max(diasatraso)maximosdiasatraso from tblcierres group by iddistribuidor;");

        //  CAPITAL DESEMBOLSADO DURANTE EL PERIODO DE CORTE 23 AL 7 Y 8 AL 22
        $capitaldesembolsadoalcorte = DB::select("select a.iddistribuidor, sum(b.monto_vale) as capitaldesembolsado from tblclientes_vales a inner join tblprestamos_valesenc b on a.id = b.idcliente where b.fecha_canje between ? and ? group by a.iddistribuidor;", [$varfechacorteini, $varfechacortefin]);


        $cierreshis = DB::select("select fecha_corte from tblcierres group by fecha_corte;");
        return view('Reportes.Administracion.reporteCierres', compact(
            'varpantallas',
            'varsubmenus',
            'permiso1',
            'vista',
            'global3',
            'consulta_1',
            'consulta_2',
            'consulta_3',
            'consulta_4',
            'consulta_5',
            'consulta_6',
            'consulta_7',
            'cierreshis',
            'diasatrasomaximo',
            'capitaldesembolsadoalcorte'
        ));
    }

    public function datoscierre(Request $request)
    {
        $date = Carbon::now();
        //$date->format('Y-m-d')
        $fecha = $request->get('fechacorte');
        $fechaformateada = Carbon::createFromFormat('Y-m-d', $fecha);
        $diadehoy = $fechaformateada->format('d');

        if ($diadehoy == 15 || $diadehoy == 30 || $diadehoy == 31) {
            $var = DB::SELECT("select fecha_corte from tblcierres where fecha_corte = ? group by fecha_corte;", [$request->get('fechacorte')]);
            $validaexistecierre = collect($var);

            if (!$validaexistecierre->isEmpty()) {
                return back()->with("ya_existe", "accion no completada");
            } else {
                $numerolineas = count($request->get('id_dis'));
                for ($i = 0; $i < $numerolineas; $i++) {
                    $numero_suc = $request->get('numero_suc')[$i];
                    $sucursal = $request->get('sucursal')[$i];
                    $numero_cor = $request->get('numero_cor')[$i];
                    $nombre_cor = $request->get('nombre_cor')[$i];
                    $numero_contrato = $request->get('numero_contrato')[$i];
                    $id_distribuidor = $request->get('id_dis')[$i];
                    $distribuidor = $request->get('distribuidor')[$i];
                    $fecha_act = $request->get('fecha_activacion')[$i];
                    $estado = $request->get('estado')[$i];
                    $capital_aut = $request->get('capital_autorizado')[$i];
                    $capital_activo = $request->get('capital_activo')[$i];
                    $intereses = $request->get('intereses')[$i];
                    $ivaintereses = $request->get('iva_intereses')[$i];
                    $cobertura = $request->get('cobertura')[$i];
                    $ivacobertura = $request->get('iva_cobertura')[$i];
                    $otros = $request->get('otros')[$i];
                    $diasatraso = $request->get('diasatraso')[$i];
                    $saldo_riesgo = $request->get('saldo_riesgo')[$i];
                    $saldoatrasado = $request->get('saldoatrasado')[$i];
                    $saldo = $request->get('saldo')[$i];
                    $capitalpagado = $request->get('capitalpagado')[$i];
                    $clientes_vigentes = $request->get('clientes_vigentes')[$i];
                    $capitaldesembolsado = $request->get('capital_desembolsado')[$i];
                    $pago_alcorte = $request->get('pago_alcorte')[$i];
                    $abonado = $request->get('abonado')[$i];
                    $maximosdiasatraso = $request->get('maximosdiasatraso')[$i];
                    //    $fechaultimopago=$request->get('fechaultimopago')[$i];
                    //    $total =$request->get('total')[$i];

                    //insertamos si no existe la ya un insert con la fecha de corte       
                    $insertacorte = new cortes();
                    $insertacorte->numero = $numero_suc;
                    $insertacorte->sucursal = $sucursal;
                    $insertacorte->numero_cor = $numero_cor;
                    $insertacorte->nombre_cor = $nombre_cor;
                    $insertacorte->numero_contrato = $numero_contrato;
                    $insertacorte->iddistribuidor = $id_distribuidor;
                    $insertacorte->distribuidor = $distribuidor;
                    $insertacorte->fecha_activacion = $fecha_act;
                    $insertacorte->estado = $estado;
                    $insertacorte->capitalautorizado = $capital_aut;
                    $insertacorte->capital = $capital_activo;
                    $insertacorte->intereses = $intereses;
                    $insertacorte->ivaintereses = $ivaintereses;
                    $insertacorte->cobertura = $cobertura;
                    $insertacorte->ivacobertura = $ivacobertura;
                    $insertacorte->otros = $otros;
                    $insertacorte->diasatraso = $diasatraso;
                    $insertacorte->saldo_riesgo = $saldo_riesgo;
                    $insertacorte->saldo_atrasado = $saldoatrasado;
                    $insertacorte->saldo = $saldo;
                    $insertacorte->capitalpagado = $capitalpagado;
                    $insertacorte->clientes_vigentes = $clientes_vigentes;
                    $insertacorte->capital_desembolsado = $capitaldesembolsado;
                    $insertacorte->pago_alcorte = $pago_alcorte;
                    $insertacorte->abonado = $abonado;
                    $insertacorte->maximosdiasatraso = $maximosdiasatraso;
                    $insertacorte->fecha_corte = $request->get('fechacorte');
                    //$insertacorte->total=$total;
                    //$insertacorte->fechaultimopago=$fechaultimopago;

                    if ($insertacorte->save()) {

                    } else {
                        echo "Ocrrio algun Error";
                    }
                }

                return back()->with("success", "accion completada");
            }

        } else {
            return back()->with("no_es_dia", "accion no completada");
        }
        //checamos que el dia sea los dias de cierre que se puedan generar


        //checamos que no exista cierre de esa fecha


    }

    public function vercierreant(string $fechac)
    {
        $var = DB::select("select * from tblcierres where fecha_corte = ?", [$fechac]);
        $datoscierre = collect($var);

        if (!$datoscierre->isEmpty()) {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            return view('Reportes.Administracion.historialcierres', compact('varpantallas', 'varsubmenus', 'datoscierre'));
        }
    }

    public function exportarCierres(string $fecha)
    {
        try {
            return Excel::download(new CierresExport($fecha), 'RESPORTE DE CIERRE ' . $fecha . '.xlsx');
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function reporte_gestionCoord()
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $permiso1 = $this->forpermisos('exportar_reporteCoord');

            // encabezasdo del reporte de cordinadores
            $consulta_1 = DB::select('select a.id_responsable,
            a.nombre,
            sum(a.dvactivas) as distriuidoras_activas,
            sum(a.linea_credito) as linea_credito,
            a.nombre_cordinador,
            a.fecha_ingreso
            from (select d.id, d.id_responsable, s.nombre, 1 as dvactivas, d.capital_autorizado as linea_credito,e.fecha_ingreso,
                  case 
                  when e.segundo_nombre = "" then concat(e.primer_nombre," ",e.apellido_paterno," ",e.apellido_materno) 
                  else concat(e.primer_nombre," ",e.segundo_nombre," ",e.apellido_paterno," ",e.apellido_materno)
                  end as nombre_cordinador
                  from tblempleados e 
                  inner join tbldistribuidores d on d.id_responsable = e.id
                  inner join tblclientes_vales c on c.iddistribuidor = d.id 
                  inner join tblprestamos_valesenc en on en.idcliente = c.id 
                  inner join tblsucursales s on s.id = e.idsucursal 
                  inner join tblusuario_sucursales us on us.idsucursal = s.id
                  where e.idpuesto = 19 and d.idstatus = 12  and us.idusuario = ? 
                  group by d.id) as a 
                GROUP BY a.id_responsable;', [auth()->user()->id]);

            // saldo activo por cordinador
            $consulta_2 = DB::select('
                SELECT tblempleados.id as id_responsable, sum(tblprestamos_valesenc.monto_vale) as saldo_actiboxcor 
                FROM tblprestamos_valesenc INNER JOIN tblclientes_vales on tblclientes_vales.id = tblprestamos_valesenc.idcliente 
                INNER JOIN tbldistribuidores on tbldistribuidores.id = tblclientes_vales.iddistribuidor 
                INNER JOIN tblempleados on tblempleados.id = tbldistribuidores.id_responsable
                WHERE tblprestamos_valesenc.status = "A" and tbldistribuidores.idstatus = 12
                GROUP by tbldistribuidores.id_responsable
            ');


            // saldo por coordinador lo que debe
            $consulta_3 = DB::select('select tbldistribuidores.id_responsable, 
                tbldistribuidores.id as dis,
                sum(tblprestamos_valesenc.pago_totalredondeado) as prestamo, 
                (select 
                sum(tblprestamos_valesdet.pago_total)- sum(tblprestamos_valesdet.saldo) as pagos_total
                from tblprestamos_valesenc 
                inner join tblprestamos_valesdet on tblprestamos_valesenc.id = tblprestamos_valesdet.idprestamo_vales
                inner join tblclientes_vales on tblclientes_vales.id = tblprestamos_valesenc.idcliente
                INNER JOIN tbldistribuidores on tbldistribuidores.id = tblclientes_vales.iddistribuidor
                where tblprestamos_valesenc.status = "A" and tblprestamos_valesdet.status = "P" and tblclientes_vales.iddistribuidor = dis) as abono
                from
                tblprestamos_valesenc 
                inner join tblclientes_vales on tblclientes_vales.id = tblprestamos_valesenc.idcliente
                inner join tbldistribuidores on tbldistribuidores.id = tblclientes_vales.iddistribuidor 
                where tblprestamos_valesenc.status = "A"
                group by tbldistribuidores.id;
            ');

            // saldo atrasado de 1 a 7 dias
            // $consulta_4 = DB::select('select a.id_responsable, sum(saldoatrasado) as saldo_atrasado from 
            //     (select a.iddistribuidor,  c.saldo_pagar - c.monto_total as saldoatrasado, d.id_responsable
            //     from tblclientes_vales a 
            //     inner join tblprestamos_valesenc b on a.id = b.idcliente 
            //     inner join tblpagos_enc c on c.id_distribuidor = a.iddistribuidor
            //     join tbldistribuidores d on d.id = c.id_distribuidor
            //     where b.status = "A" and  datediff(date(now()),c.fecha_relacion) > 6 and datediff(date(now()),c.fecha_relacion) < 14
            //     group by a.iddistribuidor
            //     union 
            //     select a.id as iddistribuidor, 0 as saldoatrasado, a.id_responsable
            //     from tbldistribuidores a where a.idstatus = 12 and a.id not in (select a.iddistribuidor
            //     from tblclientes_vales a 
            //     inner join tblprestamos_valesenc b on a.id = b.idcliente 
            //     inner join tblpagos_enc c on c.id_distribuidor = a.iddistribuidor
            //     join tbldistribuidores d on d.id = c.id_distribuidor
            //     where b.status = "A" and  datediff(date(now()),c.fecha_relacion) > 6 and datediff(date(now()),c.fecha_relacion) < 14
            //     group by a.iddistribuidor)) a
            //     group by a.id_responsable;
            // ');

            $consulta_4 = DB::select('select * from (select 
                tblpagos_enc.id_distribuidor iddis,
                tbldistribuidores.id_responsable,
                (select SUM(tblprestamos_valesenc.pago_totalredondeado)
                from tblprestamos_valesenc 
                INNER JOIN tblclientes_vales on tblclientes_vales.id = tblprestamos_valesenc.idcliente
                where tblprestamos_valesenc.status = "A"
                AND tblclientes_vales.iddistribuidor = iddis) AS prestamo,
                (select sum(tblprestamos_valesdet.pago_total)- sum(tblprestamos_valesdet.saldo) as pagado from tblprestamos_valesenc inner join tblprestamos_valesdet on tblprestamos_valesenc.id = tblprestamos_valesdet.idprestamo_vales inner join tblclientes_vales on tblclientes_vales.id = tblprestamos_valesenc.idcliente where tblprestamos_valesenc.status = "A" and tblprestamos_valesdet.status = "P"  and tblclientes_vales.iddistribuidor = iddis) as pagado,
                datediff(date(now()),STR_TO_DATE((select tblpagos_enc.fecha_relacion from tblpagos_enc where tblpagos_enc.id_distribuidor = iddis AND tblpagos_enc.estado = "N"  order by tblpagos_enc.fecha_relacion asc limit 1), "%Y-%m-%d")) as diasatraso,
                tblpagos_enc.fecha_relacion
                from tblpagos_enc
                inner join tbldistribuidores on tbldistribuidores.id = tblpagos_enc.id_distribuidor
                where 
                tbldistribuidores.idsucursal in (select idsucursal from tblusuario_sucursales where idusuario = 524) and
                datediff(date(now()),STR_TO_DATE(tblpagos_enc.fecha_relacion, "%Y-%m-%d")) >= 6 and tblpagos_enc.estado = "N"
                GROUP BY tblpagos_enc.id_distribuidor)a where a.diasatraso >= 6 and a.diasatraso <= 12;
            ');


            // Lo que no ha pagado
            $consulta_5 = DB::select('
                select a.id_responsable, sum(a.pago)as Saldoquincenal from(
                    select id_distribuidor, sum(saldo_pagar)as pago, tbldistribuidores.id_responsable
                    from tblpagos_enc
                    inner join tbldistribuidores on tblpagos_enc.id_distribuidor = tbldistribuidores.id
                     where  fecha_relacion = (select fecha_relacion from tblpagos_enc order by fecha_relacion desc limit 1)
                    group by id_distribuidor) a
                group by a.id_responsable;
            ');


            // $consulta_6 = DB::select('select b.id_responsable, b.saldoriesgo, b.dias_atraso from 
            //     (select a.iddistribuidor, a.saldo_riesgo as saldoriesgo, datediff(date(now()),a.fecha_pago) dias_atraso, a.id_responsable from 
            //     (select cliv.iddistribuidor,SUM(predet.pago_total)as saldo_riesgo, predet.fecha_pago, d.id_responsable from tblclientes_vales cliv
            //     inner join tblprestamos_valesenc preenc on cliv.id = preenc.idcliente
            //     inner join tblprestamos_valesdet predet on predet.idprestamo_vales = preenc.id
            //     inner join tbldistribuidores d on d.id = cliv.iddistribuidor
            //     where predet.status = "N" OR predet.status is null and d.idstatus = 12
            //     group by cliv.iddistribuidor) a
            //     where datediff(date(now()),a.fecha_pago) >= 12) b
            //     group by b.id_responsable;
            // ');
            $consulta_6 = DB::select('select * from (select 
                tblpagos_enc.id_distribuidor iddis,
                tbldistribuidores.id_responsable,
                (select SUM(tblprestamos_valesenc.pago_totalredondeado)
                from tblprestamos_valesenc 
                INNER JOIN tblclientes_vales on tblclientes_vales.id = tblprestamos_valesenc.idcliente
                where tblprestamos_valesenc.status = "A"
                AND tblclientes_vales.iddistribuidor = iddis) AS prestamo,
                (select sum(tblprestamos_valesdet.pago_total)- sum(tblprestamos_valesdet.saldo) as pagado from tblprestamos_valesenc inner join tblprestamos_valesdet on tblprestamos_valesenc.id = tblprestamos_valesdet.idprestamo_vales inner join tblclientes_vales on tblclientes_vales.id = tblprestamos_valesenc.idcliente where tblprestamos_valesenc.status = "A" and tblprestamos_valesdet.status = "P"  and tblclientes_vales.iddistribuidor = iddis) as pagado,
                datediff(date(now()),STR_TO_DATE(tblpagos_enc.fecha_relacion, "%Y-%m-%d")) as diasatraso,
                tblpagos_enc.fecha_relacion
                from tblpagos_enc
                inner join tbldistribuidores on tbldistribuidores.id = tblpagos_enc.id_distribuidor
                where 
                tbldistribuidores.idsucursal in (select idsucursal from tblusuario_sucursales where idusuario = 524) and
                datediff(date(now()),STR_TO_DATE(tblpagos_enc.fecha_relacion, "%Y-%m-%d")) >= 12 and tblpagos_enc.estado = "N"
                GROUP BY tblpagos_enc.id_distribuidor)a where a.diasatraso >= 12;
             ');

            // saldo de ultima relacion
            $consulta_7 = DB::select('
                select a.id_responsable, sum(a.pago)as pagadoquincena from(
                    select id_distribuidor, sum(monto_total)as pago, tbldistribuidores.id_responsable
                    from tblpagos_enc
                    inner join tbldistribuidores on tblpagos_enc.id_distribuidor = tbldistribuidores.id
                     where estado = "P"
                    and fecha_relacion = (select fecha_relacion from tblpagos_enc order by fecha_relacion desc limit 1)
                    group by id_distribuidor) a
                group by a.id_responsable;
            ');


            return view('Reportes.Administracion.reporteGestionCoord', compact(
                'varpantallas',
                'varsubmenus',
                'permiso1',
                'consulta_1',
                'consulta_2',
                'consulta_3',
                'consulta_4',
                'consulta_5',
                'consulta_6',
                'consulta_7'
            ));
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }


    public function reporte_gestionSuc()
    {

        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $permiso1 = $this->forpermisos('exportar_reporteSuc');
        $date = Carbon::now();
        $fecha = $date->format('Y-m-d');


        // encabezasdo del reporte de sucursales
        $consulta_1 = DB::select('select a.idsucursal, a.nombre,  sum(a.dvactivas) as dvactivas, sum(a.linea_credito) as linea_credito 
                from (select d.id, d.idsucursal, s.nombre, 1 as dvactivas, d.capital_autorizado as linea_credito from tblempleados e 
                inner join tbldistribuidores d on d.id_responsable = e.id
                inner join tblsucursales s on s.id = e.idsucursal 
                inner join tblclientes_vales c on c.iddistribuidor = d.id 
                inner join tblprestamos_valesenc en on en.idcliente = c.id 
                where e.idpuesto = 19 and d.idstatus = 12 
                group by d.id)a GROUP by a.idsucursal;
            ');

        $consulta = DB::select('select tbldistribuidores.idsucursal, tbldistribuidores.id_responsable 
                from tbldistribuidores where tbldistribuidores.idstatus = 12 
                group by tbldistribuidores.id_responsable;
            ');

        // saldo activo por sucursal
        $consulta_2 = DB::select('
                SELECT tbldistribuidores.idsucursal, sum(tblprestamos_valesenc.monto_vale) as credito_activosuc FROM tblprestamos_valesenc 
                INNER JOIN tblclientes_vales on tblclientes_vales.id = tblprestamos_valesenc.idcliente 
                INNER JOIN tbldistribuidores on tbldistribuidores.id = tblclientes_vales.iddistribuidor 
                INNER JOIN tblsucursales on tblsucursales.id = tbldistribuidores.idsucursal
                WHERE tblprestamos_valesenc.status = "A" and tbldistribuidores.idstatus = 12
                GROUP by tbldistribuidores.idsucursal ORDER by tblsucursales.nombre asc;
            ');


        // saldo por coordinador lo que debe
        $consulta_3 = DB::select('select tbldistribuidores.idsucursal as idsuc, 
                tbldistribuidores.id as dis,
                sum(tblprestamos_valesenc.pago_totalredondeado) as prestamos, 
                (select 
                sum(tblprestamos_valesdet.pago_total)- sum(tblprestamos_valesdet.saldo) as pagos_total
                from tblprestamos_valesenc 
                inner join tblprestamos_valesdet on tblprestamos_valesenc.id = tblprestamos_valesdet.idprestamo_vales
                inner join tblclientes_vales on tblclientes_vales.id = tblprestamos_valesenc.idcliente
                INNER JOIN tbldistribuidores on tbldistribuidores.id = tblclientes_vales.iddistribuidor
                where tblprestamos_valesenc.status = "A" and tblprestamos_valesdet.status = "P" and tblclientes_vales.iddistribuidor = dis) as abonados
                from
                tblprestamos_valesenc 
                inner join tblclientes_vales on tblclientes_vales.id = tblprestamos_valesenc.idcliente
                inner join tbldistribuidores on tbldistribuidores.id = tblclientes_vales.iddistribuidor 
                where tblprestamos_valesenc.status = "A"
                group by tbldistribuidores.id;
            ');

        // canjes diarios por sucursal checar como ponerle la fecha del dia sin hora/

        $consulta_4 = DB::select('select d.idsucursal, sum(b.monto_vale) as totalcanjes from tblclientes_vales a
                inner join tblprestamos_valesenc b on a.id = b.idcliente
                inner join tbldistribuidores d on d.id = a.iddistribuidor
                where b.fecha_canje like ?
                group by d.idsucursal
                union 
                select id as idsucursal, 0 as totalcanjes from tblsucursales where id not in 
                (select d.idsucursal from tblclientes_vales a
                inner join tblprestamos_valesenc b on a.id = b.idcliente
                inner join tbldistribuidores d on d.id = a.iddistribuidor
                where b.fecha_canje like ? and estado = "A"
                group by d.idsucursal);', [$fecha, $fecha]);





        // saldo atrasado de 1 a 7 dias sucursal
        $consulta_5 = DB::select('select * from (select 
                tblpagos_enc.id_distribuidor iddis,
                tbldistribuidores.idsucursal,
                (select SUM(tblprestamos_valesenc.pago_totalredondeado)
                from tblprestamos_valesenc 
                INNER JOIN tblclientes_vales on tblclientes_vales.id = tblprestamos_valesenc.idcliente
                where tblprestamos_valesenc.status = "A"
                AND tblclientes_vales.iddistribuidor = iddis) AS prestamo,
                (select sum(tblprestamos_valesdet.pago_total)- sum(tblprestamos_valesdet.saldo) as pagado from tblprestamos_valesenc inner join tblprestamos_valesdet on tblprestamos_valesenc.id = tblprestamos_valesdet.idprestamo_vales inner join tblclientes_vales on tblclientes_vales.id = tblprestamos_valesenc.idcliente where tblprestamos_valesenc.status = "A" and tblprestamos_valesdet.status = "P"  and tblclientes_vales.iddistribuidor = iddis) as pagado,
                datediff(date(now()),STR_TO_DATE((select tblpagos_enc.fecha_relacion from tblpagos_enc where tblpagos_enc.id_distribuidor = iddis AND tblpagos_enc.estado = "N"  order by tblpagos_enc.fecha_relacion asc limit 1), "%Y-%m-%d")) as diasatraso,
                tblpagos_enc.fecha_relacion
                from tblpagos_enc
                inner join tbldistribuidores on tbldistribuidores.id = tblpagos_enc.id_distribuidor
                where 
                tbldistribuidores.idsucursal in (select idsucursal from tblusuario_sucursales where idusuario = 524) and
                datediff(date(now()),STR_TO_DATE(tblpagos_enc.fecha_relacion, "%Y-%m-%d")) >= 6 and tblpagos_enc.estado = "N"
                GROUP BY tblpagos_enc.id_distribuidor)a where a.diasatraso >= 6 and a.diasatraso <= 12;
            ');


        // saldo en riesgo por sucursal
        $consulta_6 = DB::select('select * from (select 
                tblpagos_enc.id_distribuidor iddis,
                tbldistribuidores.idsucursal,
                (select SUM(tblprestamos_valesenc.pago_totalredondeado)
                from tblprestamos_valesenc 
                INNER JOIN tblclientes_vales on tblclientes_vales.id = tblprestamos_valesenc.idcliente
                where tblprestamos_valesenc.status = "A"
                AND tblclientes_vales.iddistribuidor = iddis) AS prestamo,
                (select sum(tblprestamos_valesdet.pago_total)- sum(tblprestamos_valesdet.saldo) as pagado from tblprestamos_valesenc inner join tblprestamos_valesdet on tblprestamos_valesenc.id = tblprestamos_valesdet.idprestamo_vales inner join tblclientes_vales on tblclientes_vales.id = tblprestamos_valesenc.idcliente where tblprestamos_valesenc.status = "A" and tblprestamos_valesdet.status = "P"  and tblclientes_vales.iddistribuidor = iddis) as pagado,
                datediff(date(now()),STR_TO_DATE(tblpagos_enc.fecha_relacion, "%Y-%m-%d")) as diasatraso,
                tblpagos_enc.fecha_relacion
                from tblpagos_enc
                inner join tbldistribuidores on tbldistribuidores.id = tblpagos_enc.id_distribuidor
                where 
                tbldistribuidores.idsucursal in (select idsucursal from tblusuario_sucursales where idusuario = 524) and
                datediff(date(now()),STR_TO_DATE(tblpagos_enc.fecha_relacion, "%Y-%m-%d")) >= 12 and tblpagos_enc.estado = "N"
                GROUP BY tblpagos_enc.id_distribuidor)a where a.diasatraso >= 12;
            ');


        //  saldo al corte
        $consulta_7 = DB::select('
                select tbldistribuidores.idsucursal, sum(saldo_pagar)as saldocorte
                from tblpagos_enc
                inner join tbldistribuidores on tblpagos_enc.id_distribuidor = tbldistribuidores.id
                where fecha_relacion = (select fecha_relacion from tblpagos_enc order by fecha_relacion desc limit 1)
                group by tbldistribuidores.idsucursal;
            ');


        // Comisiones pagadas al cierre
        $consulta_8 = DB::select('select a.idsucursal, sum(a.pagocomisiones) as pagocomisiones from (
                select a.id_distribuidor, sum(a.comision) as pagocomisiones , b.id_responsable, b.idsucursal
                from tblpagos_enc a
                inner join tbldistribuidores  b on a.id_distribuidor = b.id
                where a.estado = "P" and a.fecha_relacion = (select fecha_relacion from tblpagos_enc order by fecha_relacion  desc limit 1)
                group by a.id_distribuidor  ) a
                group by a.idsucursal;
            ');

        //  pago al corte
        $consulta_9 = DB::select('
                select a.idsucursal, sum(a.pago)as pagoalcorte from(
                    select id_distribuidor, sum(monto_total)as pago, tbldistribuidores.idsucursal
                    from tblpagos_enc
                    inner join tbldistribuidores on tblpagos_enc.id_distribuidor = tbldistribuidores.id
                     where estado = "P"
                    and fecha_relacion = (select fecha_relacion from tblpagos_enc order by fecha_relacion desc limit 1)
                    group by id_distribuidor) a
                group by a.idsucursal;
            ');

        return view('Reportes.Administracion.reporteGestionSuc', compact(
            'varpantallas',
            'varsubmenus',
            'permiso1',
            'consulta',
            'consulta_1',
            'consulta_2',
            'consulta_3',
            'consulta_4',
            'consulta_5',
            'consulta_6',
            'consulta_7',
            'consulta_8',
            'consulta_9'
        ));
    }


    public function reporte_canjes(Request $request)
    {
        try {
            $fecha_inicio = $request->get('fecha_inicio');
            $fecha_fin = $request->get('fecha_fin');
            $sucursal = $request->get('sucursal');
            $coordinador = $request->get('coordinador');
            $distribuidor = $request->get('distribuidor');

            $coordinadores = Empleados::select('*')->where('idpuesto', 19)->orderBy('primer_nombre', 'ASC')->get();
            $distribuidores = distribuidores::select('*')->orderBy('primer_nombre', 'ASC')->get();

            if ($fecha_inicio == null)
                $fecha_inicio = Carbon::now()->format('Y-m-d');

            if ($fecha_fin == null) {
                $fecha_fin = strtotime($fecha_inicio . "- 30 days");
                $fecha_fin = date("Y-m-d", $fecha_fin);
            }

            $sucursales = Sucursales::select('tblsucursales.id as id', 'tblsucursales.nombre as nombre')
                ->join('tblempleados', 'tblempleados.idsucursal', '=', 'tblsucursales.id')
                ->join('tbldistribuidores', 'tbldistribuidores.id_responsable', '=', 'tblempleados.id')
                ->join('tblclientes_vales', 'tblclientes_vales.iddistribuidor', '=', 'tbldistribuidores.id')
                ->join('tblprestamos_valesenc', 'tblprestamos_valesenc.idcliente', '=', 'tblclientes_vales.id')
                ->join('tblusuario_sucursales', 'tblusuario_sucursales.idsucursal', '=', 'tblsucursales.id')
                ->where('tblprestamos_valesenc.fecha_canje', '<=', $fecha_inicio)
                ->where('tblprestamos_valesenc.fecha_canje', '>=', $fecha_fin)
                ->groupBy('tblsucursales.id')
                ->get();

            $reporte_canjes = $this->obtenerReporteCajasFiltros($fecha_inicio, $fecha_fin, $sucursal, $distribuidor, $coordinador);

            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $permiso1 = $this->forpermisos('exportar_reporteCanjes');

            /*if ($reporte_canjes->isEmpty()) {
                return back()->with("warningFecha", "fecha no detectada");
            } else {*/
            return view('Reportes.Administracion.reporteCanjes', compact('varpantallas', 'varsubmenus', 'sucursales', 'coordinadores', 'distribuidores', 'permiso1', 'reporte_canjes', 'fecha_inicio', 'fecha_fin', 'sucursal', 'coordinador', 'distribuidor'));
            //}
        } catch (\Illuminate\Database\QueryException $ex) {
            Log::error($ex->getMessage());
            return back()->with("warningBD", "no guardado correctamente");
        }
    }


    public function cuentas_distribuidores()
    {
        try {
            $cuentas_distribuidores = $this->obtenerReporte_distribuidores();

            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $permiso1 = $this->forpermisos('exportar_reporteDesem');
            return view('Reportes.Administracion.cuentasDistribuidores', compact('varpantallas', 'varsubmenus', 'permiso1', 'cuentas_distribuidores'));

        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }
}
