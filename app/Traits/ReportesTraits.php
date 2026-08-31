<?php
namespace App\Traits;

use Illuminate\Support\Facades\Request;
use App\Models\prestamos_valesenc;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use DB;

trait ReportesTraits
{

   public function obtenerReporte_desembolsos(string $fecha_inicio, string $fecha_fin)
   {
      $mysql = DB::select('select a.fecha_desemboso,
         a.cuenta,
         a.nombre_sucursal,
         a.nombre_cliente,
         a.numero_vale,
         a.referencia_odp,
         a.estado,
         a.numero_plazos,
         a.interesmensual,
         a.capital,
         a.intereses,
         a.iva_intereses,
         a.otros,
         a.cobertura,
         a.iva_cobertura,
         a.total_importe,
         a.total_cartera,
         a.Nombre_distribuidor,
         a.totalintereses_cobertura,
         a.total_iva_deintereses_cober
         from
         (select 
         DATE_FORMAT(enc.fecha_canje, "%d/%m/%Y") as fecha_desemboso,
         CASE 
            when enc.id_odp = 6 THEN (select nombre from tblcajas where id = enc.id_caja)
            else (select nombre from tblcuentas where id = enc.id_cuenta)
         END AS cuenta,
         suc.nombre nombre_sucursal,
         concat(cli.primer_nombre," ",cli.segundo_nombre," ",cli.apellido_paterno," ",cli.apellido_materno) as nombre_cliente,
		 enc.folio_vale as numero_vale,
		  CASE 
			  WHEN enc.referencia_odp > 0 THEN enc.referencia_odp
			  ELSE "No aplica"
		 END AS referencia_odp,
         CASE 
         WHEN enc.status = "A" then "ACTIVO"
         WHEN enc.status = "F" then "FINADO"
         WHEN enc.status = "P" then "PAGADO"
         WHEN enc.status = "C" OR  enc.status = "CC"  then "CANCELADO"
         END AS estado,
         enc.numero_plazos,
         CONCAT("%", cast(enc.interesmensual * 100 as decimal(10,2))) AS interesmensual,
         enc.monto_vale as capital,
         enc.pagototalintereses as intereses,
         enc.ivainteres as iva_intereses,
         enc.redondeototalcentavos as otros,
         enc.id as numero_prestamo,
         (enc.numero_plazos* (select tblprestamos_valesdet.coberturax_plazo from tblprestamos_valesdet where idprestamo_vales = numero_prestamo limit 1))/(1.16) as cobertura,
         ((enc.numero_plazos*  (select tblprestamos_valesdet.coberturax_plazo from tblprestamos_valesdet where idprestamo_vales = numero_prestamo limit 1))/(1.16))*.16 AS iva_cobertura,
		  enc.monto_vale AS total_importe,
		  enc.pago_totalredondeado as total_cartera,
		 concat(dis.primer_nombre," ",dis.segundo_nombre," ",dis.apellido_paterno," ",dis.apellido_materno) as Nombre_distribuidor,
         enc.pagototalintereses + (enc.numero_plazos* (select tblprestamos_valesdet.coberturax_plazo from tblprestamos_valesdet where idprestamo_vales = numero_prestamo limit 1))/(1.16) as totalintereses_cobertura,
		 enc.ivainteres + (((enc.numero_plazos* (select tblprestamos_valesdet.coberturax_plazo from tblprestamos_valesdet where idprestamo_vales = numero_prestamo limit 1))/(1.16))*.16) as total_iva_deintereses_cober
         from tblclientes_vales cli
         inner join tblprestamos_valesenc enc on cli.id = enc.idcliente
         inner join tbldistribuidores dis on dis.id = cli.iddistribuidor
         inner join tblsucursales suc on suc.id = dis.idsucursal
         LEFT JOIN tblcuentas cuen on cuen.id = enc.id_cuenta
            AND enc.id_odp = 4 OR enc.id_odp = 5 OR enc.id_odp = 7
         LEFT JOIN tblcajas caj on caj.id = enc.id_cuenta
            AND enc.id_odp = 6
         where enc.fecha_canje >= ? and enc.fecha_canje <= ? and enc.otrosconceptos1 > 0  group by enc.id) a;', [$fecha_inicio, $fecha_fin]);
      return collect($mysql);
   }

   public function obtenerReporteDesembolsosFiltro(string $fecha_inicio, string $fecha_fin, string $sucursal, string $estatus)
   {

      // Define una base para la consulta
      $query = 'select a.fecha_desemboso,
           a.cuenta,
           a.nombre_sucursal,
           a.nombre_cliente,
           a.numero_vale,
           a.referencia_odp,
           a.estado,
           a.numero_plazos,
           a.interesmensual,
           a.capital,
           a.intereses,
           a.iva_intereses,
           a.otros,
           a.cobertura,
           a.iva_cobertura,
           a.total_importe,
           a.total_cartera,
           a.Nombre_distribuidor,
           a.totalintereses_cobertura,
           a.total_iva_deintereses_cober
           from
           (select 
           DATE_FORMAT(enc.fecha_canje, "%d/%m/%Y") as fecha_desemboso,
           CASE 
               when enc.id_odp = 6 THEN (select nombre from tblcajas where id = enc.id_caja)
               else (select nombre from tblcuentas where id = enc.id_cuenta)
           END AS cuenta,
           suc.nombre nombre_sucursal,
           concat(cli.primer_nombre," ",cli.segundo_nombre," ",cli.apellido_paterno," ",cli.apellido_materno) as nombre_cliente,
           enc.folio_vale as numero_vale,
           CASE 
               WHEN enc.referencia_odp > 0 THEN enc.referencia_odp
               ELSE "No aplica"
           END AS referencia_odp,
           CASE 
           WHEN enc.status = "A" then "ACTIVO"
           WHEN enc.status = "F" then "FINADO"
           WHEN enc.status = "P" then "PAGADO"
           WHEN enc.status = "C" OR  enc.status = "CC"  then "CANCELADO"
           END AS estado,
           enc.numero_plazos,
           CONCAT("%", cast(enc.interesmensual * 100 as decimal(10,2))) AS interesmensual,
           enc.monto_vale as capital,
           enc.pagototalintereses as intereses,
           enc.ivainteres as iva_intereses,
           enc.redondeototalcentavos as otros,
           enc.id as numero_prestamo,
           (enc.numero_plazos* (select tblprestamos_valesdet.coberturax_plazo from tblprestamos_valesdet where idprestamo_vales = numero_prestamo limit 1))/(1.16) as cobertura,
           ((enc.numero_plazos*  (select tblprestamos_valesdet.coberturax_plazo from tblprestamos_valesdet where idprestamo_vales = numero_prestamo limit 1))/(1.16))*.16 AS iva_cobertura,
           enc.monto_vale AS total_importe,
           enc.pago_totalredondeado as total_cartera,
           concat(dis.primer_nombre," ",dis.segundo_nombre," ",dis.apellido_paterno," ",dis.apellido_materno) as Nombre_distribuidor,
           enc.pagototalintereses + (enc.numero_plazos* (select tblprestamos_valesdet.coberturax_plazo from tblprestamos_valesdet where idprestamo_vales = numero_prestamo limit 1))/(1.16) as totalintereses_cobertura,
           enc.ivainteres + (((enc.numero_plazos* (select tblprestamos_valesdet.coberturax_plazo from tblprestamos_valesdet where idprestamo_vales = numero_prestamo limit 1))/(1.16))*.16) as total_iva_deintereses_cober
           from tblclientes_vales cli
           inner join tblprestamos_valesenc enc on cli.id = enc.idcliente
           inner join tbldistribuidores dis on dis.id = cli.iddistribuidor
           inner join tblsucursales suc on suc.id = dis.idsucursal
           LEFT JOIN tblcuentas cuen on cuen.id = enc.id_cuenta
               AND enc.id_odp = 4 OR enc.id_odp = 5 OR enc.id_odp = 7
           LEFT JOIN tblcajas caj on caj.id = enc.id_cuenta
               AND enc.id_odp = 6
           where enc.fecha_canje >= ? and enc.fecha_canje <= ? and enc.status = ?';

      // Agrega el filtro de sucursal solo si no es 0
      $bindings = [$fecha_inicio, $fecha_fin, $estatus];
      if ($sucursal != '0') {
         $query .= ' and suc.id = ?';
         $bindings[] = $sucursal;
      }

      // Completa el resto de la consulta
      $query .= ' and enc.otrosconceptos1 > 0 group by enc.id) a;';

      // Ejecuta la consulta
      $mysql = DB::select($query, $bindings);

      return collect($mysql);
   }


   public function exportarReporte_desembolsos(string $fecha_inicio, string $fecha_fin)
   {
      $mysql = DB::select(' select a.fecha_desemboso,
            a.cuenta,
            a.nombre_sucursal,
            a.nombre_cliente,
            a.numero_vale,
            a.referencia_odp,
            a.estado,
            a.numero_plazos,
            a.interesmensual,
            a.capital,
            a.intereses,
            a.iva_intereses,
            a.otros,
            a.cobertura,
            a.iva_cobertura,
            a.total_importe,
            a.total_cartera,
            a.Nombre_distribuidor,
            a.totalintereses_cobertura,
            a.total_iva_deintereses_cober
            from
            (select 
               DATE_FORMAT(enc.fecha_canje, "%d/%m/%Y") as fecha_desemboso,
               CASE 
                  when enc.id_odp = 6 THEN (select nombre from tblcajas where id = enc.id_caja)
                  else (select nombre from tblcuentas where id = enc.id_cuenta)
               END AS cuenta,
               suc.nombre nombre_sucursal,
               concat(cli.primer_nombre," ",cli.segundo_nombre," ",cli.apellido_paterno," ",cli.apellido_materno) as nombre_cliente,
            enc.folio_vale as numero_vale,
            CASE 
               WHEN enc.referencia_odp > 0 THEN enc.referencia_odp
               ELSE "No aplica"
            END AS referencia_odp,
               CASE 
               WHEN enc.status = "A" then "ACTIVO"
               WHEN enc.status = "F" then "FINADO"
               WHEN enc.status = "P" then "PAGADO"
               WHEN enc.status = "C" OR  enc.status = "CC"  then "CANCELADO"
               END AS estado,
               enc.numero_plazos,
               CONCAT("%", cast(enc.interesmensual * 100 as decimal(10,2))) AS interesmensual,
               enc.monto_vale as capital,
               enc.pagototalintereses as intereses,
               enc.ivainteres as iva_intereses,
               enc.redondeototalcentavos as otros,
               enc.id as numero_prestamo,
               (enc.numero_plazos* (select tblprestamos_valesdet.coberturax_plazo from tblprestamos_valesdet where idprestamo_vales = numero_prestamo limit 1))/(1.16) as cobertura,
               ((enc.numero_plazos*  (select tblprestamos_valesdet.coberturax_plazo from tblprestamos_valesdet where idprestamo_vales = numero_prestamo limit 1))/(1.16))*.16 AS iva_cobertura,
            enc.monto_vale AS total_importe,
            enc.pago_totalredondeado as total_cartera,
            concat(dis.primer_nombre," ",dis.segundo_nombre," ",dis.apellido_paterno," ",dis.apellido_materno) as Nombre_distribuidor,
               enc.pagototalintereses + (enc.numero_plazos* (select tblprestamos_valesdet.coberturax_plazo from tblprestamos_valesdet where idprestamo_vales = numero_prestamo limit 1))/(1.16) as totalintereses_cobertura,
            enc.ivainteres + (((enc.numero_plazos* (select tblprestamos_valesdet.coberturax_plazo from tblprestamos_valesdet where idprestamo_vales = numero_prestamo limit 1))/(1.16))*.16) as total_iva_deintereses_cober
               from tblclientes_vales cli
               inner join tblprestamos_valesenc enc on cli.id = enc.idcliente
               inner join tbldistribuidores dis on dis.id = cli.iddistribuidor
               inner join tblsucursales suc on suc.id = dis.idsucursal
               LEFT JOIN tblcuentas cuen on cuen.id = enc.id_cuenta
                  AND enc.id_odp = 4 OR enc.id_odp = 5 OR enc.id_odp = 7
               LEFT JOIN tblcajas caj on caj.id = enc.id_cuenta
                  AND enc.id_odp = 6
               where enc.fecha_canje >= ? and enc.fecha_canje <= ? and enc.otrosconceptos1 > 0  group by enc.id
               union 
            select b.fecha_desemboso,
            b.cuenta,
            b.nombre_sucursal,
            b.nombre_cliente,
            b.numero_vale,
            b.referencia_odp,
            b.estado,
            b.numero_plazos,
            b.interesmensual,
            SUM(b.capital) as capital,
            SUM(b.intereses) as intereses,
            SUM( b.iva_intereses) as iva_intereses,
            SUM(b.otros) as otros,
            b.numero_prestamo,
            SUM(b.cobertura) as cobertura,
            SUM(b.iva_cobertura) as iva_cobertura,
            SUM(b.total_importe) as total_importe,
            SUM(b.total_cartera) as total_cartera,
            b.Nombre_distribuidor,
            SUM(b.totalintereses_cobertura) as totalintereses_cobertura,
            SUM(b.total_iva_deintereses_cober) as total_iva_deintereses_cober
            from (select 
            "TOTALES" as fecha_desemboso,
            "" as cuenta,
            "" as nombre_sucursal,
            "" as nombre_cliente,
            "" as numero_vale,
            "" as referencia_odp,
            "" as estado,
            "" as numero_plazos,
            "" as interesmensual,
            enc.monto_vale as capital,
            enc.pagototalintereses as intereses,
            enc.ivainteres as iva_intereses,
            enc.redondeototalcentavos as otros,
            enc.id as numero_prestamo,
            (enc.numero_plazos* (select tblprestamos_valesdet.coberturax_plazo from tblprestamos_valesdet where idprestamo_vales = numero_prestamo limit 1))/(1.16) as cobertura,
            ((enc.numero_plazos*  (select tblprestamos_valesdet.coberturax_plazo from tblprestamos_valesdet where idprestamo_vales = numero_prestamo limit 1))/(1.16))*.16 AS iva_cobertura,
            enc.monto_vale AS total_importe,
            enc.pago_totalredondeado as total_cartera,
            "" as Nombre_distribuidor,
            enc.pagototalintereses + (enc.numero_plazos* (select tblprestamos_valesdet.coberturax_plazo from tblprestamos_valesdet where idprestamo_vales = numero_prestamo limit 1))/(1.16) as totalintereses_cobertura,
            enc.ivainteres + (((enc.numero_plazos* (select tblprestamos_valesdet.coberturax_plazo from tblprestamos_valesdet where idprestamo_vales = numero_prestamo limit 1))/(1.16))*.16) as total_iva_deintereses_cober
               from tblclientes_vales cli
               inner join tblprestamos_valesenc enc on cli.id = enc.idcliente
               inner join tbldistribuidores dis on dis.id = cli.iddistribuidor
               inner join tblsucursales suc on suc.id = dis.idsucursal
               LEFT JOIN tblcuentas cuen on cuen.id = enc.id_cuenta
                  AND enc.id_odp = 4 OR enc.id_odp = 5 OR enc.id_odp = 7
               LEFT JOIN tblcajas caj on caj.id = enc.id_cuenta
                  AND enc.id_odp = 6
               where enc.fecha_canje >= ? and enc.fecha_canje <= ? and enc.otrosconceptos1 > 0  group by enc.id) b
               ) a;', [$fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin]);
      return collect($mysql);
   }

   // public function obtenerReporte_desembolsos(string $fecha_inicio, string $fecha_fin)
   // {
   //    $mysql = DB::select(' select 
   //    enc.fecha_canje as fecha_desemboso,
   //    CASE 
   //       when enc.id_odp = 6 THEN "CAJA" 
   //       else "CUENTA" 
   //    END AS tipo,
   //    CASE 
   //       when enc.id_odp = 6 THEN enc.id_caja
   //       else enc.id_cuenta
   //    END AS id_cuenta,
   //    CASE 
   //       when enc.id_odp = 6 THEN (select nombre from tblcajas where id = enc.id_caja)
   //       else (select nombre from tblcuentas where id = enc.id_cuenta)
   //    END AS cuenta,
   //    suc.id as sucursal,
   //    suc.nombre nombre_sucursal,
   //    enc.id as numero_prestamo,
   //    cli.id as idcliente,
   //    concat(cli.primer_nombre," ",cli.segundo_nombre," ",cli.apellido_paterno," ",cli.apellido_materno) as nombre_cliente,
   //    enc.status,
   //    enc.folio_vale as numero_vale,
   //    enc.monto_vale as capital,
   //    enc.interesmensual,
   //    enc.numero_plazos,
   //    enc.referencia_odp,
   //    enc.pagototalintereses as intereses,
   //    enc.ivainteres as iva_intereses,
   //    enc.redondeototalcentavos as otros,
   //    otrosconceptos1,
   //    (enc.numero_plazos* (select tblprestamos_valesdet.coberturax_plazo from tblprestamos_valesdet where idprestamo_vales = numero_prestamo limit 1)) as cobertura_total,
   //    (enc.numero_plazos* (select tblprestamos_valesdet.coberturax_plazo from tblprestamos_valesdet where idprestamo_vales = numero_prestamo limit 1))/(1.16) as cobertura,
   //    ((enc.numero_plazos*  (select tblprestamos_valesdet.coberturax_plazo from tblprestamos_valesdet where idprestamo_vales = numero_prestamo limit 1))/(1.16))*.16 AS iva_cobertura,
   //    enc.pagototalintereses + (enc.numero_plazos* (select tblprestamos_valesdet.coberturax_plazo from tblprestamos_valesdet where idprestamo_vales = numero_prestamo limit 1))/(1.16) as totalintereses_cobertura,
   //    enc.ivainteres + (((enc.numero_plazos* (select tblprestamos_valesdet.coberturax_plazo from tblprestamos_valesdet where idprestamo_vales = numero_prestamo limit 1))/(1.16))*.16) as total_iva_deintereses_cober,
   //    enc.pago_totalredondeado as total_cartera,
   //    cli.iddistribuidor as id_distribuidor,
   //    concat(dis.primer_nombre," ",dis.segundo_nombre," ",dis.apellido_paterno," ",dis.apellido_materno) as Nombre_distribuidor
   //    from tblclientes_vales cli
   //    inner join tblprestamos_valesenc enc on cli.id = enc.idcliente
   //    inner join tbldistribuidores dis on dis.id = cli.iddistribuidor
   //    inner join tblsucursales suc on suc.id = dis.idsucursal
   //    LEFT JOIN tblcuentas cuen on cuen.id = enc.id_cuenta
   //       AND enc.id_odp = 4 OR enc.id_odp = 5 OR enc.id_odp = 7
   //    LEFT JOIN tblcajas caj on caj.id = enc.id_cuenta
   //       AND enc.id_odp = 6
   //    where enc.fecha_canje >= ? and enc.fecha_canje <= ? and enc.otrosconceptos1 > 0  group by enc.id;',[$fecha_inicio,$fecha_fin]);
   //    return collect($mysql);
   // }

   public function obtenerReporte_gastos(string $fecha_inicio, string $fecha_fin)
   {
      $mysql = DB::select('(select 
		   DATE_FORMAT(tblmovimientos_cuentas.fecha, "%d/%m/%Y") as fecha,
		   suc.nombre as nombre_sucursal,
         tblmovimientos_cuentas.id as movimiento_id,
         tblmovimientos_cuentas.id_cuenta as id_tipo,
         tblmovimientos_cuentas.estado,
         tblcuentas.nombre,
         tblcuentas.tipo,
         tblmovimientos_cuentas.responsable,
         tblmovimientos_cuentas.tipo_movimiento,
         tblmovimientos_cuentas.concepto,
         tblmovimientos_cuentas.descripcion,
         tblmovimientos_cuentas.total_iva ,
         tblmovimientos_cuentas.total_ret_iva ,
         tblmovimientos_cuentas.total_ret_isr ,
         tblmovimientos_cuentas.total_ret_isr_resico ,
         tblmovimientos_cuentas.egreso,
         tblmovimientos_cuentas.ingreso,
         tblgastos.iva,
         tblgastos.ret_iva,
         tblgastos.ret_isr,
         tblgastos.ret_isr_resico,
         tblmovimientos_cuentas.numero_poliza,
         tblmovimientos_cuentas.numero_referencia,
         tblmovimientos_cuentas.created_by,
         tblmovimientos_cuentas.created_at,
         tblcuentas.saldo_inicial,
         tblcuentas.saldo_actual,
         tblcuentas.id_empresa as id_prestenencia,
         tblempresas.nombre_empresa as nombre_prestenencia,
         tblcuentas.id_empresa as id_empresa,
         tblgastos.id as id_gasto,
         tblgastos.nombre as nombre_gasto,
         concat(tblmovimientos_cuentas.created_by," - ", tblmovimientos_cuentas.created_at) AS usuario_registrado,
         concat(tblempleados.primer_nombre," ",tblempleados.segundo_nombre," ",tblempleados.apellido_paterno," ",tblempleados.apellido_materno) as nombre_usuario
         
         from tblmovimientos_cuentas
         inner join tblcuentas on tblcuentas.id = tblmovimientos_cuentas.id_cuenta
         inner join tblempresas on tblempresas.id = tblcuentas.id_empresa
         inner join tblempleados on tblempleados.id = tblmovimientos_cuentas.id_empleado
		   inner join tblsucursales suc on suc.id = tblempleados.idsucursal
         inner join tblgastos on tblgastos.id = tblmovimientos_cuentas.id_gasto
         WHERE tblmovimientos_cuentas.tipo_movimiento = "GASTO"  and tblmovimientos_cuentas.estado <> "C"  
         and tblmovimientos_cuentas.fecha >= ? 
         and tblmovimientos_cuentas.fecha <= ? )
         
         UNION ALL
         (select 
         DATE_FORMAT(tblmovimientos_cajas.fecha, "%d/%m/%Y") as fecha,
         suc.nombre as nombre_sucursal,
         tblmovimientos_cajas.id as movimiento_id,
         tblmovimientos_cajas.id_caja as id_tipo,
         tblmovimientos_cajas.estado,
         tblcajas.nombre,
         tblcajas.tipo,
         tblmovimientos_cajas.responsable,
         tblmovimientos_cajas.tipo_movimiento,
         tblmovimientos_cajas.concepto,
         tblmovimientos_cajas.descripcion,
         tblmovimientos_cajas.total_iva ,
         tblmovimientos_cajas.total_ret_iva ,
         tblmovimientos_cajas.total_ret_isr ,
         tblmovimientos_cajas.total_ret_isr_resico ,
         tblmovimientos_cajas.egreso,
         tblmovimientos_cajas.ingreso,
         tblgastos.iva,
         tblgastos.ret_iva,
         tblgastos.ret_isr,
         tblgastos.ret_isr_resico,
         tblmovimientos_cajas.numero_poliza,
         tblmovimientos_cajas.numero_referencia,
         tblmovimientos_cajas.created_by,
         tblmovimientos_cajas.created_at,
         tblcajas.saldo_inicial,
         tblcajas.saldo_actual,
         tblsucursales.id as id_prestenencia,
         tblsucursales.nombre as nombre_prestenencia,
         tblsucursales.idempresa as id_empresa,
         tblgastos.id as id_gasto,
         tblgastos.nombre as nombre_gasto,
		   concat(tblmovimientos_cajas.created_by," - ", tblmovimientos_cajas.created_at) AS usuario_registrado,
		   concat(tblempleados.primer_nombre," ",tblempleados.segundo_nombre," ",tblempleados.apellido_paterno," ",tblempleados.apellido_materno) as nombre_usuario

         from tblmovimientos_cajas
         inner join tblcajas on tblcajas.id = tblmovimientos_cajas.id_caja
         inner join tblsucursales on tblsucursales.id = tblcajas.id_sucursal
         inner join tblempresas on tblempresas.id = tblsucursales.idempresa
         inner join tblempleados on tblempleados.id = tblmovimientos_cajas.id_empleado
		   inner join tblsucursales  suc on suc.id = tblempleados.idsucursal
		   inner join tblgastos on tblgastos.id = tblmovimientos_cajas.id_gasto
         WHERE tblmovimientos_cajas.tipo_movimiento = "GASTO"  and tblmovimientos_cajas.estado <> "C"  
         and tblmovimientos_cajas.fecha >= ?  
         and tblmovimientos_cajas.fecha <= ? );', [$fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin]);
      return collect($mysql);
   }


   public function obtenerReporteGastosFiltros(string $fecha_inicio, string $fecha_fin, string $estado, string $sucursal)
   {
      $filtroSucursal = '';
      $bindings = [$fecha_inicio, $fecha_fin, $estado, $fecha_inicio, $fecha_fin, $estado];

      if ($sucursal !== '0' && $sucursal != 0) {
         $filtroSucursal = 'AND suc.id = ?';

         // Inserta la sucursal en el índice 3
         array_splice($bindings, 3, 0, $sucursal);

         // Agrega la sucursal al final del arreglo
         $bindings[] = $sucursal;
      }

      $query = '
           (SELECT 
               DATE_FORMAT(tblmovimientos_cuentas.fecha, "%d/%m/%Y") AS fecha,
               suc.nombre AS nombre_sucursal,
               tblmovimientos_cuentas.id AS movimiento_id,
               tblmovimientos_cuentas.id_cuenta AS id_tipo,
               tblmovimientos_cuentas.estado,
               tblcuentas.nombre,
               tblcuentas.tipo,
               tblmovimientos_cuentas.responsable,
               tblmovimientos_cuentas.tipo_movimiento,
               tblmovimientos_cuentas.concepto,
               tblmovimientos_cuentas.descripcion,
               tblmovimientos_cuentas.total_iva,
               tblmovimientos_cuentas.total_ret_iva,
               tblmovimientos_cuentas.total_ret_isr,
               tblmovimientos_cuentas.total_ret_isr_resico,
               tblmovimientos_cuentas.egreso,
               tblmovimientos_cuentas.ingreso,
               tblgastos.iva,
               tblgastos.ret_iva,
               tblgastos.ret_isr,
               tblgastos.ret_isr_resico,
               tblmovimientos_cuentas.numero_poliza,
               tblmovimientos_cuentas.numero_referencia,
               tblmovimientos_cuentas.created_by,
               tblmovimientos_cuentas.created_at,
               tblcuentas.saldo_inicial,
               tblcuentas.saldo_actual,
               tblcuentas.id_empresa AS id_prestenencia,
               tblempresas.nombre_empresa AS nombre_prestenencia,
               tblcuentas.id_empresa AS id_empresa,
               tblgastos.nombre AS nombre_gasto,
               CONCAT(tblmovimientos_cuentas.created_by, " - ", tblmovimientos_cuentas.created_at) AS usuario_registrado,
               CONCAT(tblempleados.primer_nombre, " ", tblempleados.segundo_nombre, " ", tblempleados.apellido_paterno, " ", tblempleados.apellido_materno) AS nombre_usuario
           FROM tblmovimientos_cuentas
           INNER JOIN tblcuentas ON tblcuentas.id = tblmovimientos_cuentas.id_cuenta
           INNER JOIN tblempresas ON tblempresas.id = tblcuentas.id_empresa
           INNER JOIN tblempleados ON tblempleados.id = tblmovimientos_cuentas.id_empleado
           INNER JOIN tblsucursales suc ON suc.id = tblempleados.idsucursal
           INNER JOIN tblgastos ON tblgastos.id = tblmovimientos_cuentas.numero_referencia
           WHERE tblmovimientos_cuentas.tipo_movimiento = "GASTO"  
           AND tblmovimientos_cuentas.fecha >= ? 
           AND tblmovimientos_cuentas.fecha <= ? 
           AND tblmovimientos_cuentas.estado = ? 
           ' . $filtroSucursal . ')
           UNION ALL
           (SELECT 
               DATE_FORMAT(tblmovimientos_cajas.fecha, "%d/%m/%Y") AS fecha,
               suc.nombre AS nombre_sucursal,
               tblmovimientos_cajas.id AS movimiento_id,
               tblmovimientos_cajas.id_caja AS id_tipo,
               tblmovimientos_cajas.estado,
               tblcajas.nombre,
               tblcajas.tipo,
               tblmovimientos_cajas.responsable,
               tblmovimientos_cajas.tipo_movimiento,
               tblmovimientos_cajas.concepto,
               tblmovimientos_cajas.descripcion,
               tblmovimientos_cajas.total_iva,
               tblmovimientos_cajas.total_ret_iva,
               tblmovimientos_cajas.total_ret_isr,
               tblmovimientos_cajas.total_ret_isr_resico,
               tblmovimientos_cajas.egreso,
               tblmovimientos_cajas.ingreso,
               tblgastos.iva,
               tblgastos.ret_iva,
               tblgastos.ret_isr,
               tblgastos.ret_isr_resico,
               tblmovimientos_cajas.numero_poliza,
               tblmovimientos_cajas.numero_referencia,
               tblmovimientos_cajas.created_by,
               tblmovimientos_cajas.created_at,
               tblcajas.saldo_inicial,
               tblcajas.saldo_actual,
               tblsucursales.id AS id_prestenencia,
               tblsucursales.nombre AS nombre_prestenencia,
               tblsucursales.idempresa AS id_empresa,
               tblgastos.nombre AS nombre_gasto,
               CONCAT(tblmovimientos_cajas.created_by, " - ", tblmovimientos_cajas.created_at) AS usuario_registrado,
               CONCAT(tblempleados.primer_nombre, " ", tblempleados.segundo_nombre, " ", tblempleados.apellido_paterno, " ", tblempleados.apellido_materno) AS nombre_usuario
           FROM tblmovimientos_cajas
           INNER JOIN tblcajas ON tblcajas.id = tblmovimientos_cajas.id_caja
           INNER JOIN tblsucursales ON tblsucursales.id = tblcajas.id_sucursal
           INNER JOIN tblempresas ON tblempresas.id = tblsucursales.idempresa
           INNER JOIN tblempleados ON tblempleados.id = tblmovimientos_cajas.id_empleado
           INNER JOIN tblsucursales suc ON suc.id = tblempleados.idsucursal
           INNER JOIN tblgastos ON tblgastos.id = tblmovimientos_cajas.numero_referencia
           WHERE tblmovimientos_cajas.tipo_movimiento = "GASTO"  
           AND tblmovimientos_cajas.fecha >= ?  
           AND tblmovimientos_cajas.fecha <= ?
           AND tblmovimientos_cajas.estado = ? 
           ' . $filtroSucursal . ');';

      $mysql = DB::select($query, $bindings);

      return collect($mysql);
   }


   public function exportarReporte_gastos(string $fecha_inicio, string $fecha_fin)
   {
      $mysql = DB::select('(select 
            DATE_FORMAT(tblmovimientos_cuentas.fecha, "%d/%m/%Y") as fecha,
            tblcuentas.nombre,
            tblmovimientos_cuentas.descripcion,
            tblsucursales.nombre as nombre_sucursal,
            ((tblmovimientos_cuentas.egreso + (tblmovimientos_cuentas.total_ret_iva + tblmovimientos_cuentas.total_ret_isr + tblmovimientos_cuentas.total_ret_isr_resico))
            - (tblmovimientos_cuentas.total_iva)) AS importe,
            tblmovimientos_cuentas.total_iva ,
            tblmovimientos_cuentas.total_ret_iva ,
            tblmovimientos_cuentas.total_ret_isr ,
            tblmovimientos_cuentas.egreso,
            tblmovimientos_cuentas.numero_poliza,
            tblgastos.id as id_gasto,
            tblgastos.nombre as nombre_gasto,
            concat(tblmovimientos_cuentas.created_by," - ", tblmovimientos_cuentas.created_at) AS usuario_registrado,
            concat(tblempleados.primer_nombre," ",tblempleados.segundo_nombre," ",tblempleados.apellido_paterno," ",tblempleados.apellido_materno) as nombre_usuario
            from tblmovimientos_cuentas
            inner join tblcuentas on tblcuentas.id = tblmovimientos_cuentas.id_cuenta
            inner join tblempleados on tblempleados.id = tblmovimientos_cuentas.id_empleado
            inner join tblsucursales on tblsucursales.id = tblempleados.idsucursal
            inner join tblgastos on tblgastos.id = tblmovimientos_cuentas.numero_referencia
            WHERE tblmovimientos_cuentas.tipo_movimiento = "GASTO"  
            and tblmovimientos_cuentas.fecha >=  ?
            and tblmovimientos_cuentas.fecha <=  ?)
            
            UNION ALL

            (select 
            DATE_FORMAT(tblmovimientos_cajas.fecha, "%d/%m/%Y") as fecha,
            tblcajas.nombre,
            tblmovimientos_cajas.descripcion,
            tblsucursales.nombre as nombre_sucursal,
            ((tblmovimientos_cajas.egreso + (tblmovimientos_cajas.total_ret_iva + tblmovimientos_cajas.total_ret_isr + tblmovimientos_cajas.total_ret_isr_resico))
            - (tblmovimientos_cajas.total_iva)) AS importe,
            tblmovimientos_cajas.total_iva ,
            tblmovimientos_cajas.total_ret_iva ,
            tblmovimientos_cajas.total_ret_isr ,
            tblmovimientos_cajas.egreso,
            tblmovimientos_cajas.numero_poliza,
            tblgastos.id as id_gasto,
            tblgastos.nombre as nombre_gasto,
            concat(tblmovimientos_cajas.created_by," - ", tblmovimientos_cajas.created_at) AS usuario_registrado,
            concat(tblempleados.primer_nombre," ",tblempleados.segundo_nombre," ",tblempleados.apellido_paterno," ",tblempleados.apellido_materno) as nombre_usuario
            from tblmovimientos_cajas
            inner join tblcajas on tblcajas.id = tblmovimientos_cajas.id_caja
            inner join tblempleados on tblempleados.id = tblmovimientos_cajas.id_empleado
            inner join tblsucursales on tblsucursales.id = tblempleados.idsucursal
            inner join tblgastos on tblgastos.id = tblmovimientos_cajas.numero_referencia
            WHERE tblmovimientos_cajas.tipo_movimiento = "GASTO"  
            and tblmovimientos_cajas.fecha >= ?
            and tblmovimientos_cajas.fecha <= ?);', [$fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin]);
      return collect($mysql);
   }

   // public function obtenerReporte_gastos(string $fecha_inicio ,string $fecha_fin){
   //    $mysql = DB::select('
   //    (select 
   //    tblmovimientos_cuentas.id as movimiento_id,
   //    tblmovimientos_cuentas.fecha,
   //    tblmovimientos_cuentas.id_cuenta as id_tipo,
   //    tblmovimientos_cuentas.estado,
   //    tblcuentas.nombre,
   //    tblcuentas.tipo,
   //    tblmovimientos_cuentas.responsable,
   //    tblmovimientos_cuentas.tipo_movimiento,
   //    tblmovimientos_cuentas.concepto,
   //    tblmovimientos_cuentas.descripcion,
   //    tblmovimientos_cuentas.total_iva ,
   //    tblmovimientos_cuentas.total_ret_iva ,
   //    tblmovimientos_cuentas.total_ret_isr ,
   //    tblmovimientos_cuentas.total_ret_isr_resico ,
   //    tblmovimientos_cuentas.egreso,
   //    tblmovimientos_cuentas.ingreso,
   //    tblgastos.iva,
   //    tblgastos.ret_iva,
   //    tblgastos.ret_isr,
   //    tblgastos.ret_isr_resico,
   //    tblmovimientos_cuentas.numero_poliza,
   //    tblmovimientos_cuentas.numero_referencia,
   //    tblmovimientos_cuentas.created_by,
   //    tblmovimientos_cuentas.created_at,
   //    tblcuentas.saldo_inicial,
   //    tblcuentas.saldo_actual,
   //    tblcuentas.id_empresa as id_prestenencia,
   //    tblempresas.nombre_empresa as nombre_prestenencia,
   //    tblcuentas.id_empresa as id_empresa

   //    from tblmovimientos_cuentas
   //    inner join tblcuentas on tblcuentas.id = tblmovimientos_cuentas.id_cuenta
   //    inner join tblempresas on tblempresas.id = tblcuentas.id_empresa
   //    left join tblgastos on tblgastos.id = tblmovimientos_cuentas.numero_referencia
   //    WHERE tblmovimientos_cuentas.tipo_movimiento = "GASTO"  
   //    and tblmovimientos_cuentas.fecha >= ? 
   //    and tblmovimientos_cuentas.fecha <= ?)

   //    UNION ALL
   //    (select 
   //    tblmovimientos_cajas.id as movimiento_id,
   //    tblmovimientos_cajas.fecha,
   //    tblmovimientos_cajas.id_caja as id_tipo,
   //    tblmovimientos_cajas.estado,
   //    tblcajas.nombre,
   //    tblcajas.tipo,
   //    tblmovimientos_cajas.responsable,
   //    tblmovimientos_cajas.tipo_movimiento,
   //    tblmovimientos_cajas.concepto,
   //    tblmovimientos_cajas.descripcion,
   //    tblmovimientos_cajas.total_iva ,
   //    tblmovimientos_cajas.total_ret_iva ,
   //    tblmovimientos_cajas.total_ret_isr ,
   //    tblmovimientos_cajas.total_ret_isr_resico ,
   //    tblmovimientos_cajas.egreso,
   //    tblmovimientos_cajas.ingreso,
   //    tblgastos.iva,
   //    tblgastos.ret_iva,
   //    tblgastos.ret_isr,
   //    tblgastos.ret_isr_resico,
   //    tblmovimientos_cajas.numero_poliza,
   //    tblmovimientos_cajas.numero_referencia,
   //    tblmovimientos_cajas.created_by,
   //    tblmovimientos_cajas.created_at,
   //    tblcajas.saldo_inicial,
   //    tblcajas.saldo_actual,
   //    tblsucursales.id as id_prestenencia,
   //    tblsucursales.nombre as nombre_prestenencia,
   //    tblsucursales.idempresa as id_empresa

   //    from tblmovimientos_cajas
   //    inner join tblcajas on tblcajas.id = tblmovimientos_cajas.id_caja
   //    inner join tblsucursales on tblsucursales.id = tblcajas.id_sucursal
   //    inner join tblempresas on tblempresas.id = tblsucursales.idempresa
   //    left join tblgastos on tblgastos.id = tblmovimientos_cajas.numero_referencia
   //    WHERE tblmovimientos_cajas.tipo_movimiento = "GASTO"  
   //    and tblmovimientos_cajas.fecha >= ? 
   //    and tblmovimientos_cajas.fecha <= ?);',[$fecha_inicio,$fecha_fin,$fecha_inicio,$fecha_fin]);
   //    return collect($mysql);
   // }

   public function obtenerReporte_ingresos(string $fecha_inicio, string $fecha_fin)
   {
      $mysql = DB::select('select 
            DATE_FORMAT(a.fecha_pago, "%d/%m/%Y") as fecha_pago,
            a.cuenta_ingreso,
            a.numero_sucursal,
            a.sucursal,
            a.folio_vale,
            a.idenc,
            a.referencia_pago,
            a.capital,
            a.interes,
            a.ivainteres,
            a.cobertura,
            a.ivacobertura,
            a.otros,
            a.costo_transaccion,
            a.proteccion_saldo,
            a.pago_total,
            a.comision,
            a.Importe_entrada_Cuenta,
            a.Importe_Condonado,
            a.tipo_pago,
            a.interes_cobertura,
            a.ivainteres_ivacobertura,        
            a.fecha_desembolso,
            a.cuenta_desembolso,
            a.iddistribuidor,
            a.nombres_dis
            from (select
                        enc.fecha_relacion as rela,
                        dis.id as iddis,
                        enc.fecha_pago,
                        case 
                           when enc.tipo_cuenta = "CAJA" then concat(caj.nombre)
                           else concat(cuent.nombre) 
                        end as cuenta_ingreso,
                        suc.id as numero_sucursal,
                        suc.nombre as sucursal,
                        pe.folio_vale,
                        enc.id as idenc,
                        
                        CASE
                        WHEN (select count(id) as tipo_pago from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela ) > 0 THEN
							(select GROUP_CONCAT( tblpagosconcentrado.referencia_pago ORDER BY  tblpagosconcentrado.id ASC SEPARATOR ", ")  as referencia_pago from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela)
                        WHEN enc.referencia_pago = 0 and enc.tipo_cuenta = "CAJA" THEN 
							"CAJA"
                        ELSE 
							enc.referencia_pago 
                        END AS referencia_pago,
                        
                        cast((select sum(monto) from tblpagos_det where tblpagos_det.idconcepto = 1 and tblpagos_det.idpagoenc = idenc) as decimal (10,2)) as capital,
                        cast((select sum(monto) from tblpagos_det where tblpagos_det.idconcepto = 2 and tblpagos_det.idpagoenc = idenc) as decimal (10,2)) as interes,
                        cast((select sum(monto) from tblpagos_det where tblpagos_det.idconcepto = 3 and tblpagos_det.idpagoenc = idenc) as decimal (10,2)) as ivainteres,
                        cast(((select sum(monto) from tblpagos_det where tblpagos_det.idconcepto = 4 and tblpagos_det.idpagoenc = idenc)/1.16) as decimal (10,2)) as cobertura,
                        cast((((select sum(monto) from tblpagos_det where tblpagos_det.idconcepto = 4 and tblpagos_det.idpagoenc = idenc)/1.16)*.16) as decimal (10,2)) as ivacobertura,
                        cast((select sum(monto) from tblpagos_det where tblpagos_det.idconcepto = 5 and tblpagos_det.idpagoenc = idenc) as decimal (10,2)) as otros,
                        CASE 
                           WHEN (select count(id) as transacciones from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela and descripcion is null) > 0 
                           THEN (select count(id) as transacciones from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela and descripcion is null) * enc.costo_transaccion
                           ELSE enc.costo_transaccion
                        END AS costo_transaccion,
                        enc.proteccion_saldo,

                        CASE 
                           WHEN (select count(id) as transacciones from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela) > 0 
                           THEN cast((enc.monto_total + ((select count(id) as transacciones from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela and descripcion is null) * enc.costo_transaccion) + enc.proteccion_saldo) as decimal (10,2))
                           ELSE cast((enc.monto_total + enc.costo_transaccion + enc.proteccion_saldo) as decimal (10,2))
                        END AS pago_total,
                  
                        enc.comision,

                        CASE
                           WHEN enc.otrosconceptos3 > 0 THEN
							         cast((enc.otrosconceptos1  + enc.costo_transaccion + enc.proteccion_saldo) as decimal (10,2))
                           WHEN enc.monto_recibido > 0 THEN enc.monto_recibido
                           WHEN (select sum(monto_recibido) as transacciones from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela and descripcion is null) > 0 THEN 
                              cast(((select sum(monto_recibido) as transacciones from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela and descripcion is null) +  enc.monto_recibido)as decimal (10,2))
                           WHEN (select count(id) as transacciones from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela and descripcion is null) > 0 THEN
                              cast((enc.otrosconceptos1  + ((select count(id) as transacciones from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela and descripcion is null)*enc.costo_transaccion) + enc.proteccion_saldo) as decimal (10,2))
                           ELSE 
                              cast((enc.otrosconceptos1  + enc.costo_transaccion + enc.proteccion_saldo) as decimal (10,2))
                        END AS Importe_entrada_Cuenta,

                        CASE
                        WHEN (select count(id) as tipo_pago from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela and descripcion = "CONDONADO") > 0 THEN
                        (select SUM(intento_pago) as pago from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela and descripcion = "CONDONADO")
                        ELSE 0
                        END as Importe_Condonado,

                        cast((select sum(monto) from tblpagos_det where tblpagos_det.idconcepto = 2 and tblpagos_det.idpagoenc = idenc) +
                        ((select sum(monto) from tblpagos_det where tblpagos_det.idconcepto = 4 and tblpagos_det.idpagoenc = idenc)/1.16) as decimal (10,2)) as interes_cobertura,

                        cast((select sum(monto) from tblpagos_det where tblpagos_det.idconcepto = 3 and tblpagos_det.idpagoenc = idenc) +
                        (((select sum(monto) from tblpagos_det where tblpagos_det.idconcepto = 4 and tblpagos_det.idpagoenc = idenc)/1.16)*.16) as decimal (10,2)) as ivainteres_ivacobertura,

                        pe.fecha_canje as fecha_desembolso,
                        
                        CASE 
                     WHEN (select count(id) as tipo_pago from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela and descripcion = "CONDONADO") > 0 THEN "CONDONADO"
                        WHEN (select count(id) as transacciones from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela ) > 0 AND enc.saldo_pagar = enc.monto_total THEN "PAGO CONCILIADO"
                        WHEN (select count(id) as transacciones from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela) > 0 AND enc.saldo_pagar <> enc.monto_total THEN "PAGO CONCILIADO INCOMPLETO"
                        WHEN enc.fecha_pago >= enc.fecha_corte_final and enc.fecha_pago <= DATE_ADD(enc.fecha_relacion, INTERVAL 5 DAY) AND enc.saldo_pagar = enc.monto_total THEN "COMPLETO"
                        WHEN enc.fecha_pago >= enc.fecha_corte_final and enc.fecha_pago <= DATE_ADD(enc.fecha_relacion, INTERVAL 5 DAY) AND enc.saldo_pagar <> enc.monto_total THEN "INCOMPLETO"
                        WHEN enc.saldo_pagar = enc.monto_total THEN "ATRASADO"
                        WHEN enc.saldo_pagar <> enc.monto_total THEN "INCOMPLETO ATRASADO"
                        END AS tipo_pago,
                        
                        CASE 
                           when pe.id_odp = 6 THEN (select CONCAT(nombre) from tblcajas where id = pe.id_caja)
                           else (select CONCAT(nombre) from tblcuentas where id = pe.id_cuenta)
                        END AS cuenta_desembolso,
                        
                        dis.id as iddistribuidor,
                        concat(dis.primer_nombre," ",dis.segundo_nombre," ",dis.apellido_paterno," ",dis.apellido_materno) as nombres_dis
                        
                     from tblpagos_enc enc
                           inner join tbldistribuidores dis on dis.id = enc.id_distribuidor
                           inner join tblsucursales suc on suc.id = dis.idsucursal
                              LEFT JOIN tblcuentas cuent on cuent.id = enc.cuenta
                                 AND enc.tipo_cuenta = "CUENTA"
                              LEFT JOIN tblcajas caj on caj.id = enc.caja
                                 AND enc.tipo_cuenta = "CAJA"
                           inner join tblclientes_vales cli on cli.iddistribuidor = enc.id_distribuidor
                           inner join tblprestamos_valesenc pe on pe.idcliente = cli.id
                     where enc.estado = "P" 
                     and enc.fecha_pago >= ?
            and enc.fecha_pago <= ? GROUP by enc.id) as a;', [$fecha_inicio, $fecha_fin]);
      return collect($mysql);
   }

   public function obtenerReporteIngresosFiltros(string $fecha_inicio, string $fecha_fin, int $id_sucursal = null)
   {
      $query = 'select 
            DATE_FORMAT(a.fecha_pago, "%d/%m/%Y") as fecha_pago,
            a.cuenta_ingreso,
            a.numero_sucursal,
            a.sucursal,
            a.folio_vale,
            a.idenc,
            a.referencia_pago,
            a.capital,
            a.interes,
            a.ivainteres,
            a.cobertura,
            a.ivacobertura,
            a.otros,
            a.costo_transaccion,
            a.proteccion_saldo,
            a.pago_total,
            a.comision,
            a.Importe_entrada_Cuenta,
            a.Importe_Condonado,
            a.tipo_pago,
            a.interes_cobertura,
            a.ivainteres_ivacobertura,        
            DATE_FORMAT(a.fecha_desembolso, "%d/%m/%Y") as fecha_desembolso,
            a.cuenta_desembolso,
            a.iddistribuidor,
            a.nombres_dis
            from (select
                        enc.fecha_relacion as rela,
                        dis.id as iddis,
                        enc.fecha_pago,
                        case 
                           when enc.tipo_cuenta = "CAJA" then concat(caj.nombre)
                           else concat(cuent.nombre) 
                        end as cuenta_ingreso,
                        suc.id as numero_sucursal,
                        suc.nombre as sucursal,
                        pe.folio_vale,
                        enc.id as idenc,
                        
                        CASE
                           WHEN (SELECT COUNT(id) 
                                 FROM tblpagosconcentrado 
                                 WHERE id_distirbuidor = iddis AND fecha_relacion = rela) > 0 THEN
                              (SELECT GROUP_CONCAT(
                                          CASE 
                                                WHEN LOCATE("3", referencia_pago) > 0 THEN 
                                                   CONCAT("0", SUBSTRING(referencia_pago, LOCATE("3", referencia_pago)))
                                                ELSE 
                                                   referencia_pago
                                          END
                                          ORDER BY id ASC SEPARATOR ", ") 
                                 FROM tblpagosconcentrado 
                                 WHERE id_distirbuidor = iddis AND fecha_relacion = rela)
                           WHEN enc.referencia_pago = 0 AND enc.tipo_cuenta = "CAJA" THEN 
                              "CAJA"
                           ELSE 
                              CASE 
                                    WHEN LOCATE("3", enc.referencia_pago) > 0 THEN 
                                       CONCAT("0", SUBSTRING(enc.referencia_pago, LOCATE("3", enc.referencia_pago)))
                                    ELSE 
                                       enc.referencia_pago
                              END
                        END AS referencia_pago,
                        
                        cast((select sum(monto) from tblpagos_det where tblpagos_det.idconcepto = 1 and tblpagos_det.idpagoenc = idenc) as decimal (10,2)) as capital,
                        cast((select sum(monto) from tblpagos_det where tblpagos_det.idconcepto = 2 and tblpagos_det.idpagoenc = idenc) as decimal (10,2)) as interes,
                        cast((select sum(monto) from tblpagos_det where tblpagos_det.idconcepto = 3 and tblpagos_det.idpagoenc = idenc) as decimal (10,2)) as ivainteres,
                        cast(((select sum(monto) from tblpagos_det where tblpagos_det.idconcepto = 4 and tblpagos_det.idpagoenc = idenc)/1.16) as decimal (10,2)) as cobertura,
                        cast((((select sum(monto) from tblpagos_det where tblpagos_det.idconcepto = 4 and tblpagos_det.idpagoenc = idenc)/1.16)*.16) as decimal (10,2)) as ivacobertura,
                        cast((select sum(monto) from tblpagos_det where tblpagos_det.idconcepto = 5 and tblpagos_det.idpagoenc = idenc) as decimal (10,2)) as otros,
                        CASE 
                           WHEN (select count(id) as transacciones from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela and descripcion is null) > 0 
                           THEN (select count(id) as transacciones from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela and descripcion is null) * enc.costo_transaccion
                           ELSE enc.costo_transaccion
                        END AS costo_transaccion,
                        enc.proteccion_saldo,

                        CASE 
                           WHEN (select count(id) as transacciones from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela) > 0 
                           THEN cast((enc.monto_total + ((select count(id) as transacciones from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela and descripcion is null) * enc.costo_transaccion) + enc.proteccion_saldo) as decimal (10,2))
                           ELSE cast((enc.monto_total + enc.costo_transaccion + enc.proteccion_saldo) as decimal (10,2))
                        END AS pago_total,
                  
                        enc.comision,

                        CASE
                           WHEN enc.otrosconceptos3 > 0 THEN
							         cast((enc.otrosconceptos1  + enc.costo_transaccion + enc.proteccion_saldo) as decimal (10,2))
                           WHEN enc.monto_recibido > 0 THEN enc.monto_recibido
                           WHEN (select sum(monto_recibido) as transacciones from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela and descripcion is null) > 0 THEN 
                              cast(((select sum(monto_recibido) as transacciones from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela and descripcion is null) +  enc.monto_recibido)as decimal (10,2))
                           WHEN (select count(id) as transacciones from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela and descripcion is null) > 0 THEN
                              cast((enc.otrosconceptos1  + ((select count(id) as transacciones from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela and descripcion is null)*enc.costo_transaccion) + enc.proteccion_saldo) as decimal (10,2))
                           ELSE 
                              cast((enc.otrosconceptos1  + enc.costo_transaccion + enc.proteccion_saldo) as decimal (10,2))
                        END AS Importe_entrada_Cuenta,

                        CASE
                        WHEN (select count(id) as tipo_pago from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela and descripcion = "CONDONADO") > 0 THEN
                        (select SUM(intento_pago) as pago from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela and descripcion = "CONDONADO")
                        ELSE 0
                        END as Importe_Condonado,

                        cast((select sum(monto) from tblpagos_det where tblpagos_det.idconcepto = 2 and tblpagos_det.idpagoenc = idenc) +
                        ((select sum(monto) from tblpagos_det where tblpagos_det.idconcepto = 4 and tblpagos_det.idpagoenc = idenc)/1.16) as decimal (10,2)) as interes_cobertura,

                        cast((select sum(monto) from tblpagos_det where tblpagos_det.idconcepto = 3 and tblpagos_det.idpagoenc = idenc) +
                        (((select sum(monto) from tblpagos_det where tblpagos_det.idconcepto = 4 and tblpagos_det.idpagoenc = idenc)/1.16)*.16) as decimal (10,2)) as ivainteres_ivacobertura,

                        pe.fecha_canje as fecha_desembolso,
                        
                        CASE 
                     WHEN (select count(id) as tipo_pago from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela and descripcion = "CONDONADO") > 0 THEN "CONDONADO"
                        WHEN (select count(id) as transacciones from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela ) > 0 AND enc.saldo_pagar = enc.monto_total THEN "PAGO CONCILIADO"
                        WHEN (select count(id) as transacciones from tblpagosconcentrado where id_distirbuidor = iddis and fecha_relacion = rela) > 0 AND enc.saldo_pagar <> enc.monto_total THEN "PAGO CONCILIADO INCOMPLETO"
                        WHEN enc.fecha_pago >= enc.fecha_corte_final and enc.fecha_pago <= DATE_ADD(enc.fecha_relacion, INTERVAL 5 DAY) AND enc.saldo_pagar = enc.monto_total THEN "COMPLETO"
                        WHEN enc.fecha_pago >= enc.fecha_corte_final and enc.fecha_pago <= DATE_ADD(enc.fecha_relacion, INTERVAL 5 DAY) AND enc.saldo_pagar <> enc.monto_total THEN "INCOMPLETO"
                        WHEN enc.saldo_pagar = enc.monto_total THEN "ATRASADO"
                        WHEN enc.saldo_pagar <> enc.monto_total THEN "INCOMPLETO ATRASADO"
                        END AS tipo_pago,
                        
                        CASE 
                           when pe.id_odp = 6 THEN (select CONCAT(nombre) from tblcajas where id = pe.id_caja)
                           else (select CONCAT(nombre) from tblcuentas where id = pe.id_cuenta)
                        END AS cuenta_desembolso,
                        
                        dis.id as iddistribuidor,
                        concat(dis.primer_nombre," ",dis.segundo_nombre," ",dis.apellido_paterno," ",dis.apellido_materno) as nombres_dis
                        
                     from tblpagos_enc enc
                           inner join tbldistribuidores dis on dis.id = enc.id_distribuidor
                           inner join tblsucursales suc on suc.id = dis.idsucursal
                              LEFT JOIN tblcuentas cuent on cuent.id = enc.cuenta
                                 AND enc.tipo_cuenta = "CUENTA"
                              LEFT JOIN tblcajas caj on caj.id = enc.caja
                                 AND enc.tipo_cuenta = "CAJA"
                           inner join tblclientes_vales cli on cli.iddistribuidor = enc.id_distribuidor
                           inner join tblprestamos_valesenc pe on pe.idcliente = cli.id
                     where enc.estado = "P"
                     and enc.fecha_pago >= ?
                     and enc.fecha_pago <= ? ';

      if ($id_sucursal != null) {
         $query .= ' and suc.id = ? ';
      }

      $query .= ' GROUP by enc.id) as a; ';

      $bindings = [$fecha_inicio, $fecha_fin];

      if ($id_sucursal != null) {
         $bindings[] = $id_sucursal;
      }

      $mysql = DB::select($query, $bindings);
      return collect($mysql);
   }

   public function obtenerinteresxingreso(string $fecha_inicio, string $fecha_fin)
   {
      $mysql = DB::select('select * from tblpagos_det det
         where det.fecha_pago >= ?
         and det.fecha_pago <= ?;', [$fecha_inicio, $fecha_fin]);
      return collect($mysql);
   }


   public function obtenerReporte_canjes(string $fecha_inicio, string $fecha_fin)
   {
      $mysql = DB::select(
         'SELECT tblsucursales.nombre as sucursal,tblempleados.id as id_coordinador , CONCAT(tblempleados.primer_nombre, " " ,tblempleados.segundo_nombre, " " ,tblempleados.apellido_paterno, " " ,tblempleados.apellido_materno)  as coordinador, 
            tbldistribuidores.id as id_distribuidor, CONCAT(tbldistribuidores.primer_nombre, " " ,tbldistribuidores.segundo_nombre, " " ,tbldistribuidores.apellido_paterno, " " ,tbldistribuidores.apellido_materno) as distribuidor, 
            tblclientes_vales.id as id_cliente, CONCAT(tblclientes_vales.primer_nombre," ", tblclientes_vales.segundo_nombre," ", tblclientes_vales.apellido_paterno," ", tblclientes_vales.apellido_materno) as cliente,
            tblprestamos_valesenc.monto_vale as monto,
            DATE_FORMAT(tblprestamos_valesenc.fecha_canje, "%d/%m/%Y") as fecha_canje 
            FROM tblprestamos_valesenc 
            INNER JOIN tblclientes_vales on tblprestamos_valesenc.idcliente = tblclientes_vales.id
            INNER JOIN tbldistribuidores on tblclientes_vales.iddistribuidor = tbldistribuidores.id 
            INNER JOIN tblempleados on tbldistribuidores.id_responsable = tblempleados.id 
            INNER JOIN tblsucursales on tblempleados.idsucursal = tblsucursales.id
            INNER JOIN tblusuario_sucursales on tblusuario_sucursales.idsucursal = tblsucursales.id
            WHERE tblprestamos_valesenc.fecha_canje >= ?
            AND tblprestamos_valesenc.fecha_canje <= ?
            AND tblusuario_sucursales.idusuario = ?;',
         [$fecha_inicio, $fecha_fin, auth()->user()->id]
      );
      return collect($mysql);
   }


   public function obtenerReporteCajasFiltros($fecha_inicio, $fecha_fin, $id_sucursal, $id_distribuidor, $id_empleado)
   {

      $mysql = prestamos_valesenc::select(
         'tblsucursales.nombre as sucursal',
         'tblempleados.id as id_coordinador',
         DB::raw('CONCAT(tblempleados.primer_nombre, " ", tblempleados.segundo_nombre, " ", tblempleados.apellido_paterno, " ", tblempleados.apellido_materno) as coordinador'),
         'tbldistribuidores.id as id_distribuidor',
         DB::raw('CONCAT(tbldistribuidores.primer_nombre, " ", tbldistribuidores.segundo_nombre, " ", tbldistribuidores.apellido_paterno, " ", tbldistribuidores.apellido_materno) as distribuidor'),
         'tblclientes_vales.id as id_cliente',
         DB::raw('CONCAT(tblclientes_vales.primer_nombre, " ", tblclientes_vales.segundo_nombre, " ", tblclientes_vales.apellido_paterno, " ", tblclientes_vales.apellido_materno) as cliente'),
         'tblprestamos_valesenc.monto_vale as monto',
         DB::raw('DATE_FORMAT(tblprestamos_valesenc.fecha_canje, "%d/%m/%Y") as fecha_canje')
      )
         ->join('tblclientes_vales', 'tblprestamos_valesenc.idcliente', '=', 'tblclientes_vales.id')
         ->join('tbldistribuidores', 'tblclientes_vales.iddistribuidor', '=', 'tbldistribuidores.id')
         ->join('tblempleados', 'tbldistribuidores.id_responsable', '=', 'tblempleados.id')
         ->join('tblsucursales', 'tblempleados.idsucursal', '=', 'tblsucursales.id')
         ->join('tblusuario_sucursales', 'tblusuario_sucursales.idsucursal', '=', 'tblsucursales.id')
         ->where('tblprestamos_valesenc.fecha_canje', '<=', $fecha_inicio)
         ->where('tblprestamos_valesenc.fecha_canje', '>=', $fecha_fin)
         //->where('tblusuario_sucursales.idusuario', '=', auth()->user()->id)
         ->when($id_sucursal && $id_sucursal !== 0, function ($query) use ($id_sucursal) {
            $query->where('tblsucursales.id', '=', $id_sucursal);
         })
         ->when($id_distribuidor && $id_distribuidor !== 0, function ($query) use ($id_distribuidor) {
            $query->where('tbldistribuidores.id', '=', $id_distribuidor);
         })
         ->when($id_empleado && $id_empleado !== 0, function ($query) use ($id_empleado) {
            $query->where('tblempleados.id', '=', $id_empleado);
         })
         ->get();

      return collect($mysql);
   }

   public function obtenerReporte_distribuidores()
   {
      $mysql = DB::select(
         'SELECT tbldistribuidores.id as id_distribuidor, 
               CONCAT(tbldistribuidores.primer_nombre, " " ,tbldistribuidores.segundo_nombre, " " ,tbldistribuidores.apellido_paterno, " " ,tbldistribuidores.apellido_materno) as distribuidor, 
               tblsucursales.nombre as sucursal,cuenta.referencia, tblreferencias_pago.referencia as referencia2 
            FROM 
               (SELECT tblreferencias_pago.referencia, tblreferencias_pago.id_dis 
               FROM tblreferencias_pago WHERE tblreferencias_pago.id_fichapago=1 ) cuenta,   
            tbldistribuidores 
            INNER JOIN tblreferencias_pago on tbldistribuidores.id=tblreferencias_pago.id_dis 
            INNER JOIN tblsucursales on tblsucursales.id=tbldistribuidores.idsucursal
            WHERE tblreferencias_pago.id_fichapago=2 
               AND cuenta.id_dis=tblreferencias_pago.id_dis;'
      );
      return collect($mysql);
   }

}