<?php
namespace App\Traits;
use Illuminate\Support\Facades\Request;
use DB;


trait GlobalTraits{

    public function saldoactualpagos(){
        $sql=DB::select('select 
        sum(tblprestamos_valesdet.pago_total)- sum(tblprestamos_valesdet.saldo) as pagos_total
        from tblprestamos_valesenc 
        inner join tblprestamos_valesdet on tblprestamos_valesenc.id = tblprestamos_valesdet.idprestamo_vales
        inner join tblclientes_vales on tblclientes_vales.id = tblprestamos_valesenc.idcliente
        INNER JOIN tbldistribuidores on tbldistribuidores.id = tblclientes_vales.iddistribuidor
        where tblprestamos_valesenc.status = "A" and tblprestamos_valesdet.status = "P" 
        and tbldistribuidores.idsucursal in (select idsucursal from tblusuario_sucursales where idusuario = ?);',[auth()->user()->id]);
        return collect($sql);
    }
    
    public function saldoactualceros(int $idusaurio){
        $sql=DB::select('select sum(preenc.pago_totalredondeado)as Saldo_actual from tblclientes_vales cli
        inner join tblprestamos_valesenc preenc on cli.id = preenc.idcliente
        inner join tbldistribuidores dis on dis.id = cli.iddistribuidor
        where dis.idsucursal in (select idsucursal from tblusuario_sucursales where idusuario = ?)  
        and preenc.status = "A"',[$idusaurio]);
        return collect($sql);
    }

    public function saldoriesgo(){
        $sql=DB::select('select sum(pago_quincenal) as saldo_riesgo from tblprestamos_valesdet where 
        idprestamo_vales in ( select idprestamo_vales from tblprestamos_valesenc a 
        inner join tblprestamos_valesdet b on a.id = b.idprestamo_vales 
        where (b.status is null || b.status = "N")
        and b.fecha_pago < date(now())
        and a.status = "A");');
        return collect($sql);
    }

    public function saldoriesgo7dias(){
        $sql = DB::SELECT('select sum(pago_total) as saldo_riesgo from tblprestamos_valesdet where 
        idprestamo_vales in (select distinct(idprestamo_vales)  from tblprestamos_valesenc a 
        inner join tblprestamos_valesdet b on a.id = b.idprestamo_vales where  a.status = "A" and b.fecha_pago > 7) 
        and status is null || status = "N";');
        return collect($sql);
    }
    
 
    public function saldoRiesgoTotal(int $iduser){
        $sql=DB::select('select * from (select 
            tblpagos_enc.id_distribuidor iddis,
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
            tbldistribuidores.idsucursal in (select idsucursal from tblusuario_sucursales where idusuario = ?) and
            datediff(date(now()),STR_TO_DATE(tblpagos_enc.fecha_relacion, "%Y-%m-%d")) >= 12 and tblpagos_enc.estado = "N"
            || tblpagos_enc.status_atraso = "A"
            GROUP BY tblpagos_enc.id_distribuidor)a where a.diasatraso >= 12;
        ',[$iduser]);
        return collect($sql);
    }



    public function moraTotal(int $iduser){
        $sql=DB::select('select sum(a.mora) as mora from(select tblpagos_enc.id_distribuidor,
        tblpagos_enc.id_distribuidor as iddis,
        sum(tblpagos_enc.saldo_pagar) as mora,
        datediff(date(now()),STR_TO_DATE((select tblpagos_enc.fecha_relacion from tblpagos_enc where tblpagos_enc.id_distribuidor = iddis AND tblpagos_enc.estado = "N"  order by tblpagos_enc.fecha_relacion asc limit 1), "%Y-%m-%d")) as diferencia
        from tblpagos_enc 
        inner join tbldistribuidores on tbldistribuidores.id = tblpagos_enc.id_distribuidor
        WHERE tblpagos_enc.fecha_relacion < date(now())
        AND tblpagos_enc.estado = "N"
        AND tbldistribuidores.idsucursal in (select idsucursal from tblusuario_sucursales where idusuario = ?)
        group by tblpagos_enc.id_distribuidor)a where a.diferencia >= 6;',[$iduser]);
        return collect($sql);
    }


    // public function moraxdis(int $id){
    //     $sql = DB::SELECT('select sum(tblpagos_enc.monto_total) as saldoatrasado,
	// 	(SELECT sum(tblpagos_enc.otrosconceptos2) FROM tblpagos_enc 
    //      WHERE tblpagos_enc.status_atraso = "A" AND tblpagos_enc.id_distribuidor = ?
    //      AND DATEDIFF(date(now()),DATE_ADD(tblpagos_enc.fecha_relacion, INTERVAL 5 DAY)) >=1 and tblpagos_enc.fecha_relacion < date(now())) 
    //      as incompleto 
    //      from tblpagos_enc 
    //      WHERE DATEDIFF(date(now()),DATE_ADD(tblpagos_enc.fecha_relacion, INTERVAL 5 DAY)) >=1 and tblpagos_enc.fecha_relacion < date(now())
    //      and tblpagos_enc.otrosconceptos1 = 0 and tblpagos_enc.id_distribuidor = ?;',[$id,$id]);
    //      return collect($sql);
    // }

    public function moraxdis(int $id){
        $sql = DB::SELECT('select sum(tblpagos_enc.saldo_pagar) as saldoatrasado
         from tblpagos_enc 
         WHERE DATEDIFF(date(now()),DATE_ADD(tblpagos_enc.fecha_relacion, INTERVAL 5 DAY)) >=1 and tblpagos_enc.fecha_relacion < date(now())
         and tblpagos_enc.estado <> "P" and tblpagos_enc.id_distribuidor = ?;',[$id]);
         return collect($sql);
    }



    public function global1(){
        $Varglobal = DB::select("select suc.nombre AS sucursal,
            dis.id AS iddis,
            concat(dis.primer_nombre,' ',dis.segundo_nombre,' ',dis.apellido_paterno,' ',dis.apellido_materno) AS nombre_distribuidor,
            promo.id AS idcoordinador,
            concat(promo.primer_nombre,' ',promo.segundo_nombre,' ',promo.apellido_paterno,' ',promo.apellido_materno) AS nombre_coordinador,
            stadodis.nombre AS status_distribuidor,
            sum(trestamoenc.monto_vale) AS total_canjes,
            sum(trestamoenc.pago_totalredondeado) AS total_prestamos,
            sum(trestamoenc.pagototalintereses) AS total_intereses,
            sum(trestamoenc.ivainteres) AS ivainteres,tipod.nombre AS categoria,count(trestamoenc.id) AS clientes_activos,
            trestamoenc.fecha_activacionODP AS fecha_activacionodp,
            dis.capital AS capital_disponible,dis.capital_autorizado AS capital_autorizado,
            dis.capital_autorizado - dis.capital AS capital_actual,trestamoenc.fecha_canje AS fecha_inicio,dis.coord_anterior AS id_coord_anterior,
            concat(coord_ant.primer_nombre,' ',coord_ant.segundo_nombre,' ',coord_ant.apellido_paterno,' ',coord_ant.apellido_materno) AS name_coord_ant,
            (select tblhistorial.created_by from tblhistorial where tblhistorial.iddistribuidor = dis.id and tblhistorial.status = 'val' 
                order by tblhistorial.id desc limit 1) AS uservalidocdt,(select sum(tblprestamos_valesenc.numero_plazos) 
                from (tblprestamos_valesenc join tblclientes_vales on(tblprestamos_valesenc.idcliente = tblclientes_vales.id)) 
            where tblclientes_vales.iddistribuidor = dis.id group by tblclientes_vales.iddistribuidor) * 5 AS total_cobertura 
        from (((((((tbldistribuidores dis join tblempleados promo on(promo.id = dis.id_responsable)) 
            join tblclientes_vales clival on(clival.iddistribuidor = dis.id)) join tblsucursales suc on(dis.idsucursal = suc.id)) 
            join tbltipo_distribuidor tipod on(dis.tipo_distribuidor = tipod.id)) join tblprestamos_valesenc trestamoenc on(trestamoenc.idcliente = clival.id)) 
            join tblstatus_distribuidor stadodis on(stadodis.id = dis.idstatus)) left join tblempleados coord_ant on(coord_ant.id = dis.coord_anterior)) 
        where dis.idstatus = 12 and clival.status = 'A' and trestamoenc.status = 'A' group by dis.id");
            return collect($Varglobal);
    }
    
    
    public function global2(){
        $varobtenerprorrateo = DB::select('select 
            tblclientes_vales.iddistribuidor as id_distribuidor,
            tblclientes_vales.iddistribuidor as id_dis, 
            sum(tblprestamos_valesdet.pago_total)- sum(tblprestamos_valesdet.saldo) as Abonado,
            (select sum(comision) as comision from tblpagos_enc where id_distribuidor = id_dis and estado = "P") as comision,
            (select max(fecha_pago) as fecha_ultimopago from tblpagos_enc where id_distribuidor = id_dis and estado = "P") as fecha_ultimopago
            from tblprestamos_valesenc 
            inner join tblprestamos_valesdet on tblprestamos_valesenc.id = tblprestamos_valesdet.idprestamo_vales
            inner join tblclientes_vales on tblclientes_vales.id = tblprestamos_valesenc.idcliente
            inner join tbldistribuidores on tbldistribuidores.id = tblclientes_vales.iddistribuidor
            where tblprestamos_valesenc.status = "A" and tblprestamos_valesdet.status = "P"  and tbldistribuidores.idstatus = 12
            group by tblclientes_vales.iddistribuidor;');
        return collect($varobtenerprorrateo);
    }
// public function global3(){
//     $obtenercobertura =  DB::select('select tblclientes_vales.iddistribuidor as iddistribuidor,
//      tblclientes_vales.id, 
//     tblprestamos_valesdet.fecha_pago AS fecha_ultimopago,

//     (SELECT sum(tblpagos_enc.otrosconceptos2) FROM tblpagos_enc WHERE tblpagos_enc.id_distribuidor = iddistribuidor and tblpagos_enc.status_atraso ="A")+
//     (select tblpagos_enc.saldo_pagar as pago_total from tblpagos_enc
//     WHERE tblpagos_enc.id_distribuidor = iddistribuidor and tblpagos_enc.fecha_relacion < date(now()) and tblpagos_enc.otrosconceptos1 = 0)as saldoatrasado,

//     DATEDIFF(date(now()),tblprestamos_valesdet.fecha_pago) as dias_atraso
//     from tbldistribuidor_valeras 
//     INNER JOIN tblclientes_vales on tblclientes_vales.iddistribuidor = tbldistribuidor_valeras.iddistribuidor 
//     INNER JOIN tblprestamos_valesenc on tblprestamos_valesenc.idcliente = tblclientes_vales.id 
//     INNER JOIN tblprestamos_valesdet on tblprestamos_valesdet.idprestamo_vales = tblprestamos_valesenc.id 
//     WHERE tblprestamos_valesdet.fecha_pago < date(now())
//     and tblprestamos_valesdet.status ="N" or tblprestamos_valesdet.status = "C" or tblprestamos_valesdet.status = null GROUP by tblclientes_vales.iddistribuidor;');
//     return collect($obtenercobertura);
// }

public function global3(){
    $obtenercobertura =  DB::select('select * from (select tblpagos_enc.id_distribuidor as iddistribuidor, tblpagos_enc.fecha_pago AS fecha_ultimopago, 
    (SELECT sum(tblpagos_enc.otrosconceptos2) FROM tblpagos_enc WHERE tblpagos_enc.id_distribuidor = iddistribuidor and tblpagos_enc.status_atraso ="A" GROUP by tblpagos_enc.id_distribuidor) as saldoatrasado, 
    
    (select sum(tblpagos_enc.saldo_pagar) as pago_total from tblpagos_enc  
    WHERE tblpagos_enc.id_distribuidor = iddistribuidor 
    and DATE_ADD(tblpagos_enc.fecha_relacion, INTERVAL 5 DAY) < date(now()) and tblpagos_enc.otrosconceptos1 = 0
    GROUP by tblpagos_enc.id_distribuidor) as quincenasatras, 
     
     (SELECT DATEDIFF(date(now()),DATE_ADD(tblpagos_enc.fecha_relacion, INTERVAL 5 DAY)) FROM tblpagos_enc 
     WHERE id_distribuidor = iddistribuidor AND tblpagos_enc.fecha_relacion < date(now()) and tblpagos_enc.estado <> "P"  
     ORDER by tblpagos_enc.fecha_relacion ASC LIMIT 1) as dias_atraso 
     from tblpagos_enc WHERE tblpagos_enc.fecha_relacion < date(now()) and tblpagos_enc.estado <> "P"
     GROUP by tblpagos_enc.id_distribuidor) a where a.dias_atraso>= 1;');
    return collect($obtenercobertura);
}

//  DATEDIFF(date(now()),DATE_ADD(tblpagos_enc.fecha_relacion, INTERVAL 5 DAY)) as dias_atraso 

public function pagosIncompletos(){
    $var =  DB::select('select tblpagos_enc.id_distribuidor,  tblpagos_enc.otrosconceptos2 from tblpagos_enc where tblpagos_enc.status_atraso = "A";');
    return collect($var);
}


// public function global3(){
//     $obtenercobertura =  DB::select('select a1.iddistribuidor,sum(pago_quincenal)as saldoatrasado,b.fecha_pago AS fecha_ultimopago,DATEDIFF(DATE(NOW()),b.fecha_pago) as dias_atraso  from tblclientes_vales a1
//     inner join tblprestamos_valesenc a on a1.id = a.idcliente
//     inner join tblprestamos_valesdet b on a.id = b.idprestamo_vales
//     where b.fecha_pago < DATE(NOW()) and b.STATUS is null or b.STATUS ="C" OR b.STATUS ="N"
//     group by a1.iddistribuidor ;');
//     return collect($obtenercobertura);
// }

 
   public function  obtenerglocliente(int $iddis){
    $consulta = DB::select('call globlacliente(?);',[$iddis]);
    return collect($consulta);
   }


   public function obtenersaldoclientes(int $iddis)
   {
     $consulta = DB::select('select a.id as idprestamo,
        (select sum(tblprestamos_valesdet.pago_total - tblprestamos_valesdet.saldo) from  tblprestamos_valesdet where tblprestamos_valesdet.idprestamo_vales = idprestamo  and tblprestamos_valesdet.status = "P") as abonado,
        a.pago_totalredondeado as total_prestamo,
        (select count(tblprestamos_valesdet.id) from  tblprestamos_valesdet where tblprestamos_valesdet.idprestamo_vales = idprestamo  and tblprestamos_valesdet.status = "P") as plazos_pagados,
        a.numero_plazos as total_plazos,
        a.pagoxplazototalredondeado as pagoxplazo,
        (select sum(tblprestamos_valesdet.coberturax_plazo) from  tblprestamos_valesdet where tblprestamos_valesdet.idprestamo_vales = idprestamo) as total_cobertura,
        a.pagototalintereses as intereses,
        (select tblprestamos_valesdet.fecha_pago from  tblprestamos_valesdet where tblprestamos_valesdet.idprestamo_vales = idprestamo  and tblprestamos_valesdet.status = "P" ORDER BY tblprestamos_valesdet.id DESC limit 1) as fecha_pago
        from
        tblclientes_vales a1 
        inner join  tblprestamos_valesenc a on a1.id = a.idcliente
        where a1.iddistribuidor = ? 
        group by a.id;',[$iddis]);
    return collect($consulta);
   
   }

   public function obtenerinterescli(){
    $consulta = DB::select('select enc.id_cliente,
    det.idconcepto,
    case when det.idconcepto=1 then  sum(det.monto) else 0 end as capital,
    case when det.idconcepto=2 then  sum(det.monto) else 0 end as interes,
    case when det.idconcepto=3 then  sum(det.monto) else 0 end as capitalintereses
    from tblpagoscli_det det 
    inner join tblclipagos_enc enc on enc.id = det.idpagoclidet 
    where det.idconcepto = 2
    group by enc.id_cliente,det.idconcepto;');
    return collect($consulta);
   }

   public function obtenercobercli (){
    $consulta = DB::select('select cli.id,sum(coberturax_plazo) AS TOTALCOBERTURA 
    from tblprestamos_valesdet det 
    inner join tblprestamos_valesenc enc on det.idprestamo_vales = enc.id
    inner join tblclientes_vales cli on cli.id = enc.idcliente
    where det.status = "P"
    group by det.idprestamo_vales');
    return collect($consulta);
   }


   public function totalplazo(int $id){
   $sql = DB::select('select cli.id,max(det.plazos)as total_plazos  from tblprestamos_valesenc enc
   inner join tblprestamos_valesdet det on enc.id = det.idprestamo_vales
   inner join tblclientes_vales cli on cli.id = enc.idcliente
   where cli.iddistribuidor = ?
   group by cli.id',[$id]);
   return collect($sql);
}
public function obtenerdetalledistribuidor(int $iddistribuidor){
    $sql = DB::select('select id,concat(primer_nombre," ",segundo_nombre," ",apellido_paterno," ",apellido_materno) as Nombre,
    capital,
    capital_autorizado from tbldistribuidores where id = ? ;',[$iddistribuidor]);
   return collect($sql);
}

public function obtenerdiasatraso(){
    $sql = DB::select(' select tblpagos_enc.id_distribuidor,
    case 
    when DATEDIFF(now(),tblpagos_enc.fecha_relacion) >= 3 then DATEDIFF(now(),tblpagos_enc.fecha_relacion)
	when DATEDIFF(now(),tblpagos_enc.fecha_relacion) >= 1 && estado  != "P" then DATEDIFF(now(),tblpagos_enc.fecha_relacion) 
    else 0 end
     AS "dias_atraso" from tblpagos_enc   
    group by tblpagos_enc.id_distribuidor
    union
    select tblpagos_enc.id_distribuidor, sum(tblpagos_enc.otrosconceptos2)
     AS "dias_atraso" from tblpagos_enc  where otrosconceptos2 = 0 && estado  != "P"
    group by tblpagos_enc.id_distribuidor;');
    return collect($sql);
}

public function saldoalcorte(){
    $sql = DB::select('select  a.iddistribuidor,max(c.fecha_pago)as fecha_pago,sum(c.pago_quincenal) saldo_alcorte from tblclientes_vales a 
    inner join tblprestamos_valesenc b on a.id = b.idcliente
    inner join tblprestamos_valesdet c on c.idprestamo_vales = b.id
    where c.status = "n"
    group by a.iddistribuidor;');
    return collect($sql);
}

public function total_prestamos(){
    $sql = DB::select('select 
    sum(pago_totalredondeado) as totalprestamos
    from tblprestamos_valesenc where status = "A";');
    return collect($sql);
}



public function saldoatr(){
    $sql=DB::select('select sum(pago_total)as saldoatrasado from tblprestamos_valesdet 
    where DATEDIFF(now(),fecha_pago) >=1 and status IS NULL || status ="n";');
    return collect($sql);
}


public function canejados(int $id){
        $sql=DB::select('select sum(b.monto_vale) as canje from tblclientes_vales a
        inner join tblprestamos_valesenc b on a.id = b.idcliente where a.iddistribuidor = ? and b.status = "A"',[$id]);
            return collect($sql);
}


public function salriesgodis(int $id){
        $sql=DB::select('select sum(pago_quincenal) as saldo_riesgo from tblprestamos_valesdet  a
        inner join tblprestamos_valesenc  b on a.idprestamo_vales = b.id
        inner join tblclientes_vales c on c.id = b.idcliente
        where idprestamo_vales in ( select idprestamo_vales from tblprestamos_valesenc a inner join tblprestamos_valesdet b on a.id = b.idprestamo_vales where b.status is null and b.fecha_pago < date(now()) and a.status = "A") 
            and a.status is null and c.iddistribuidor = ?;',[$id]);
        return collect($sql);
}

public function saldo_riesgoxdis(int $id){
    $sql=DB::select('select sum(t99.suma) as saldo_riesgo from
     (
     select sum(tblprestamos_valesdet.pago_total)as suma from tblprestamos_valesdet
     where idprestamo_vales in (select distinct(a.id) from tblprestamos_valesenc a inner join tblprestamos_valesdet b on a.id = b.idprestamo_vales inner join tblclientes_vales c on c.id = a.idcliente where datediff(date(now()),b.fecha_pago) >= 7 and c.iddistribuidor = ?)
     and status NOT IN ("P")
    UNION
     select sum(tblprestamos_valesdet.pago_total)as suma from tblprestamos_valesdet
     where idprestamo_vales in (select distinct(a.id) from tblprestamos_valesenc a inner join tblprestamos_valesdet b on a.id = b.idprestamo_vales inner join tblclientes_vales c on c.id = a.idcliente where datediff(date(now()),b.fecha_pago) >= 7 and c.iddistribuidor = ?)
     and status IS NULL

     UNION
     select sum(tblprestamos_valesdet.pago_total)as suma from tblprestamos_valesdet
     where idprestamo_vales in (select distinct(a.id) from tblprestamos_valesenc a inner join tblprestamos_valesdet b on a.id = b.idprestamo_vales inner join tblclientes_vales c on c.id = a.idcliente where datediff(date(now()),b.fecha_pago) >= 7 and c.iddistribuidor = ?)
     and saldo > 0
     ) t99;',[$id,$id,$id]);
    return collect($sql);

}

// public function saldo_riesgoxdis(int $id){
//     $sql=DB::select('select 
//     sum(tblprestamos_valesdet.pago_total)			
//         as saldo_riesgo
//         from tblprestamos_valesdet 
//         INNER JOIN tblprestamos_valesenc on tblprestamos_valesenc.id = tblprestamos_valesdet.idprestamo_vales
//         INNER JOIN tblclientes_vales on tblclientes_vales.id = tblprestamos_valesenc.idcliente
//         where tblclientes_vales.iddistribuidor = ? and  tblprestamos_valesdet.idprestamo_vales in 
//             (select distinct(a.id)  from tblprestamos_valesenc a 
//             inner join tblprestamos_valesdet b on a.id = b.idprestamo_vales 
//             where a.status = "A"  and datediff(date(now()),b.fecha_pago) > 7  
//             and b.status is null || b.status = "N" || b.saldo > 0) 
//         and tblprestamos_valesdet.status is null || tblprestamos_valesdet.status = "N";',[$id]);
//     return collect($sql);

// }

public function saldoactualxdis(int $id){
    
     $sql=DB::select('select sum(tblprestamos_valesenc.pago_totalredondeado) - sum(tblprestamos_valesdet.pago_quincenal) as saldo_actualdis from tblclientes_vales 
    inner join tblprestamos_valesenc on tblclientes_vales.id = tblprestamos_valesenc.idcliente
    inner join tblprestamos_valesdet on tblprestamos_valesenc.id = tblprestamos_valesdet.idprestamo_vales
    where tblclientes_vales.iddistribuidor = ?',[$id]);
    return  collect($sql);
}


//GLOBAL PRESTAMOS EMPLEADOS

public function globaleprestamoenc()
{
$consulta = DB::select('select 
a.id as idprestamo,
b.id as idempleado,
concat(b.primer_nombre," ",b.segundo_nombre," ",b.apellido_paterno," ",b.apellido_materno) as Nombre_empleado,
a.fecha_inicio,
a.plazos,
a.estado,
a.monto as monto_credito,
a.interesredondeado as interes,
a.ivainteres as ivainteredes,
a.totalredondeado as total_prestamo,
a.created_by as usuario_activo,
s.nombre as "nombres"
from tblcreditos_empleado a
inner join tblempleados b on a.id_empleado = b.id
inner join tblsucursales s on s.id = b.idsucursal
where a.estado = "A"
group by a.id;');
return collect($consulta);
}

public function globalprestamosnomsaldos(){
    $consulta = DB::select(
    'select b.id as idprestamo,
    a.id_empleado,
    sum(monto_total) as abonado,
    b.totalredondeado - sum(monto_total) as saldo_actual,
    count(a.id) as plazos_pagados
    from tblpagosnomenc a
    inner join tblcreditos_empleado b on a.idprestamos = b.id
    where b.estado = "A"
    group by a.id_empleado;');
return collect($consulta);
}

public function saldosgeneralesprenom(){
    $consulta = DB::select(
'select
sum(a.monto) as capital,
sum(a.totalredondeado) as capital_Total
from tblcreditos_empleado a
where a.estado="A";');
return collect($consulta);
}
public function saldovencidoprenom(){
    $consulta = DB::select('select  sum(pago_quincenal)as saldo_vencido from tblcreditosempleado_det where estado= "A" AND fecha_pago < DATE(NOW());');
    return collect($consulta);
}

public function clientevprenom()
{
$consulta = DB::select('select count(id) as cvigentes from tblcreditos_empleado
where estado = "A";');
return collect($consulta);
}

public function prestmoabonadoglobal(){
    $consulta = DB::select('select sum(monto_total) as abonado from tblpagosnomenc;');
    return collect($consulta);
}
public function prestamosempleadosatraso(){
    $consulta = DB::select('select a.id,id_empleado from tblcreditos_empleado a
    inner join tblcreditosempleado_det b on a.id = b.id_credito
    where b.fecha_pago < DATE(NOW()) and b.estado = "A"
    group by a.id_empleado;');
    return collect($consulta);
}

public function saldosydiastraso(){
    $consulta = DB::select('Select a.id,id_empleado,b.fecha_pago AS fecha_ultimopago,DATEDIFF(DATE(NOW()),b.fecha_pago) as dias_atraso,SUM(b.pago_quincenal)as saldovencido from tblcreditos_empleado a
    inner join tblcreditosempleado_det b on a.id = b.id_credito
    where b.fecha_pago < DATE(NOW()) and b.estado = "A"
    group by a.id;');
    return collect($consulta);

}

public function obtenersucursalxidusuario(int $iduser)
{
    $obtenersuc= DB::select(" select a.idusuario,a.idsucursal,b.nombre from tblusuario_sucursales a inner join tblsucursales b on a.idsucursal = b.id where idusuario = ?;",[$iduser]);
    return collect($obtenersuc);
}
}