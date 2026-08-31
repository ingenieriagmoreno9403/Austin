<?php
namespace App\Traits;
use Illuminate\Support\Facades\Request;
use DB;
use Log;


trait ServiciosTrait{

    public function obtener_servicios(){
        $var = DB::select('select * from tbltipos_servicios;');
        return collect($var);
   }

   public function obtener_conceptos_servicios(){
        $var = DB::select('select * from tblconceptos_servicios;');
        return collect($var);
   }

    public function identifica_servicio(string $nombre)
    {
          $lista = DB::select("select * FROM tbltipos_servicios where nombre = ? limit 1;",[$nombre]);
          $lista = collect($lista);

          foreach($lista as $key){
               $id = $key->id;
          }

          return $id;
     }

     public function listaservicio_enc()
    {
          $lista = DB::select("select tbltipos_servicios.nombre as servicio, tblservicios_enc.*,
          CONCAT(tblempleados.primer_nombre,' ',tblempleados.segundo_nombre,' ',tblempleados.apellido_paterno,' ',tblempleados.apellido_materno) as nombre_vendedor,
          tblclientes.nombre as nombre_cliente,  tblclientes.razon_social,
          tbllicitacion_enc.id as licitacion,
          tblordencompra_enc.id as order_compra,
          case
               when tblordencompra_enc.id is not null then  (select sum(cantidad_recibida) from tblordencompra_det where tblordencompra_det.orden_compra_id = order_compra)
               else 0
          end as recepcion,
          case 
               when tblclientes_atencion.segundo_nombre is null then CONCAT (tblclientes_atencion.primer_nombre,' ',tblclientes_atencion.apellido_paterno,' ',tblclientes_atencion.apellido_materno ) 
          else
               CONCAT (tblclientes_atencion.primer_nombre,' ',tblclientes_atencion.segundo_nombre,' ',tblclientes_atencion.apellido_paterno,' ',tblclientes_atencion.apellido_materno ) 
          end as nombre_atencion,
          tblclientes_atencion.telefono as telefono_atencion,
          tblclientes_atencion.correo as correo_atencio

          FROM tblservicios_enc 
          inner join tblempleados on tblempleados.id = tblservicios_enc.id_vendedor 
          inner join tblclientes on tblclientes.id = tblservicios_enc.id_cliente
          inner join tbltipos_servicios on tbltipos_servicios.id = tblservicios_enc.id_tiposervicio
          left join tbllicitacion_enc on tblservicios_enc.id = tbllicitacion_enc.id_servicio
          inner join tblclientes_atencion on tblclientes_atencion.id = tblservicios_enc.id_atencion
          left join tblordencompra_enc on tbllicitacion_enc.id = tblordencompra_enc.referencia_licitacion_id group by tblservicios_enc.id;");
          return collect($lista);
    }

   public function obtnerultimoservicio_enc()
   {
          $lista = DB::select("select * FROM tblservicios_enc where tblservicios_enc.estado <> 'FINALIZADO' order by id DESC limit 1;");
          $lista = collect($lista);

          foreach($lista as $key){
               $id = $key->id;
          }

          return $id;
    }

    public function obtnerservicio_encxid(int $id)
    {
          $lista = DB::select("select * FROM tblservicios_enc where id = ? limit 1;",[$id]);
          return collect($lista);
    }

    public function obtnerproductosxservicio(int $id)
    {
          $lista = DB::select("select tblservicios_productos.*, 
          tblproductos.nombre, 
          tblproductos.descripcion, 
          tblproductos.sku, 
          tblproductos.precio_unitario, 
          tblunidadesmedida.abreviacion as unidad_medida 
          from tblservicios_productos 
          inner join tblproductos on tblproductos.id = tblservicios_productos.id_producto
          inner join tblunidadesmedida on tblunidadesmedida.id = tblproductos.id_unidad_medida
          where tblservicios_productos.id_servicio_enc = ?;",[$id]);
          return collect($lista);
    }


    public function valida_existencias_prod(int $id)
   {
          $lista = DB::select("select sum(cantidad_existente) as cantidad_encontrada from tblexistencias where id_producto = ?;",[$id]);
          $lista = collect($lista);

          foreach($lista as $key){
               $cantidad_encontrada = $key->cantidad_encontrada;
          }

          if(is_null($cantidad_encontrada)){
               $cantidad_encontrada = 0;
          }else{
               $cantidad_encontrada = $cantidad_encontrada;
          }

          return $cantidad_encontrada;
    }


      public function valida_norepetidos_servprod(int $id,int $idprod)
     {
          $lista = DB::select("select * from tblservicios_productos where id_servicio_enc = ? and id_producto = ?;",[$id,$idprod]);
          $lista = collect($lista);

         

          if($lista->isempty()){
               $cantidad_total = 0;
          }else{
               foreach($lista as $key){
                    $cantidad_total = $key->cantidad_total;
               }
          }

          return $cantidad_total;
    }

    public function totales_estados()
    {
          $lista = DB::select("select estado, count(id) as totales from tblservicios_enc where id_tiposervicio <> 3 group by estado;");
          return collect($lista);
    }


    public function solicitudes_pendientes_licitaciones()
    {
          $lista = DB::select('select * from tblservicios_enc where solicita_licitacion = "si" and otrosconceptos1 is null and estado = "EN ESPERA";');
          return collect($lista);
    }

    public function productos_faltantes(int $id)
    {
          $lista = DB::select('select * from tblservicios_productos where id_servicio_enc = ? and cantidad_faltante != 0;',[$id]);
          return collect($lista);
    }

    public function folio_servicio(int $id)
    {
          $lista =  collect(DB::select('select * from tblservicios_enc where id = ?;',[$id]));

          foreach($lista as $key){
               $folio = $key->folio;
          }
          
          return $folio;
    }


    public function aparta_existencias(int $id, int $cantidad)
    {
          $lista = collect( DB::select('select cantidad_existente ,cantidad_reservada FROM tblexistencias where id_producto = ?;',[$id]));
          $cantidad_entra = 0;

          if($lista->isNotEmpty()){
               foreach($lista as $key){
                    $cantidad_existente = $key->cantidad_existente;
                    $cantidad_reservada = $key->cantidad_reservada;
               }

               $cantidad_real =  $cantidad_existente  - $cantidad_reservada;

               if($cantidad_real == 0){
                   $cantidad_entra = 0;

               }
               elseif($cantidad <= $cantidad_real){
                   $cantidad_entra = $cantidad;

               }else{
                    $cantidad_entra = $cantidad_real;
               }
               
          }else{
               $cantidad_entra = 0;
          }

          return $cantidad_entra;
    }

    public function cantidad_reservadaxid(int $id)
    {
          $lista = collect( DB::select('select cantidad_existente ,cantidad_reservada FROM tblexistencias where id_producto = ?;',[$id]));

          if($lista->isNotEmpty()){
               foreach($lista as $key){
                    $cantidad_reservada = $key->cantidad_reservada;

                    if(is_null($cantidad_reservada)){
                         $cantidad_reservada = 0;
                    }
               }

              
          }else{
               $cantidad_reservada = 0;
          }

          return $cantidad_reservada;
         
    }

    public function stockPendiete()
    {
          $lista = DB::select('select * FROM tblservicios_productos where estado = "Sin Stock" or estado = "Diferencia" order by id_servicio_enc asc;');
          return collect($lista);
    }

    public function ComparativaPreciosExport(int $id)
    {
         
            $item = collect(DB::select('
               select a.proveedor, a.producto, a.cantidad,a.umed,
               a.costo,
               a.margen,
               CASE 
               WHEN a.envio > 0 THEN
                    ROUND((a.envio / a.partidas) / a.cantidad, 2)
               ELSE 
                    0
               END AS envio_total,

               CASE 
               WHEN a.envio > 0 THEN
                    ROUND(a.costo / (1 - (a.margen/100)) + (ROUND((a.envio / a.partidas) / a.cantidad, 2)), 2)
               ELSE 
                    0
               END AS margen_total,

               CASE 
               WHEN a.envio > 0 THEN
                    ROUND(a.costo +
                    ROUND((a.envio / a.partidas) / a.cantidad, 2),2) 
               ELSE 
                    ROUND(a.costo, 2)
               END AS sub_total,

               CASE 
               WHEN a.envio > 0 THEN
                    ROUND((a.costo +
                    ROUND((a.envio / a.partidas) / a.cantidad, 2)) * a.cantidad,2)
               ELSE 
                    ROUND((a.costo) * a.cantidad,2)
               END AS total,
               a.observaciones
               
               from (select tbllicitacionproducto_proveedor.producto_id as productid,
               tblprovedores.nombre as proveedor,
               tblproductos.nombre as producto, 
               tbllicitacionproducto_proveedor.*,
               tblunidadesmedida.nombre as umed,
               (select cantidad from tbllicitacion_det where producto_id = productid and licitacion_id = ?) as cantidad,
               (select count(id) from tbllicitacion_det where licitacion_id = ?) as partidas
               FROM tbllicitacionproducto_proveedor
               inner join tblproductos on tblproductos.id = tbllicitacionproducto_proveedor.producto_id
               inner join tblunidadesmedida on tblunidadesmedida.id = tblproductos.id_unidad_medida 
               inner join tblprovedores on tblprovedores.id = tbllicitacionproducto_proveedor.proveedor_id
               where tbllicitacionproducto_proveedor.licitacion_id = ?) a
               ;',[$id,$id,$id]));
            return $item;    

    }


    public function HisotrialServicio(int $id)
    {
         
            $item = collect(DB::select('
               select tbltipos_servicios.nombre as servicio, 
               tblservicios_enc.*,

               tbllicitacion_enc.id as licitacion,
               tbllicitacion_enc.nombre as nombre_licitacion,
               tbllicitacion_enc.folio as folio_licitacion,
               tbllicitacion_enc.fecha_creacion as fechaini_licitacion,
               tbllicitacion_enc.fecha_limite as fechafin_licitacion,
               tbllicitacion_enc.estado as estado_licitacion,
               tbllicitacion_enc.descripcion_detalle as descripcion_detalle_licitacion,

               tblordencompra_enc.id as order_compra,
               tblordencompra_enc.folio as folio_oc,
               tblordencompra_enc.nombre as nombre_oc,
               tblordencompra_enc.fecha_creacion as fechaini_oc,
               tblordencompra_enc.fecha_limite as fechafin_oc,
               tblordencompra_enc.estado as estado_oc,
               tblordencompra_enc.descripcion_detalle as descripcion_detalle_oc,

               case
               when tblordencompra_enc.id is not null then 
                    (select sum(cantidad_recibida) from tblordencompra_det where tblordencompra_det.orden_compra_id = order_compra)
               else
               0
               end as recepcion
               FROM tblservicios_enc 
               inner join tblempleados on tblempleados.id = tblservicios_enc.id_vendedor 
               inner join tblclientes on tblclientes.id = tblservicios_enc.id_cliente
               inner join tbltipos_servicios on tbltipos_servicios.id = tblservicios_enc.id_tiposervicio
               left join tbllicitacion_enc on tblservicios_enc.id = tbllicitacion_enc.id_servicio
               left join tblordencompra_enc on tbllicitacion_enc.id = tblordencompra_enc.referencia_licitacion_id
               where tblservicios_enc.id = ? and tblservicios_enc.solicita_licitacion = "si";',[$id]));
            return $item;    

    }

     public function informacion_enc_datos_servicio(int $id)
    {
         
            $item = collect(DB::select("select 
               tblservicios_enc.folio,
        
               CONCAT_WS (' ',tblempleados.primer_nombre,tblempleados.segundo_nombre,tblempleados.apellido_paterno,tblempleados.apellido_materno ) AS nombre_vendedor,
               tblclientes.nombre AS nombre_cliente,
               tblpuestos.nombre as puesto_vendedor,
               tblempresas.nombre_empresa as nombre_empresa_vendedor,
               tblempresas.rfc as rfc_empresa_vendedor,
               tblempresas.direccion_fiscal as direccion_empresa_vendedor,
               tblempleados.telefono as telefono_vendedor,
               tblempleados.correo as correo_vendedor,

               tblclientes.razon_social as nombre_empresa_cliente,
               tblclientes.rfc as rfc_cliente,
               CONCAT('COL. ',tblclientes.colonia,', CALLE ',tblclientes.calle,' NO. EXT ',tblclientes.numero_ext,' NO. INT ',tblclientes.numero_int,', C.P. ',tblclientes.cp) AS direccion_cliente,
               tblclientes.telefono as clientes_telefono,
               tblclientes.correo_electronico as clientes_correo,
               tblservicios_enc.otrosconceptos2 as reviso,

             
               CONCAT_WS (' ',tblclientes_atencion.primer_nombre,tblclientes_atencion.segundo_nombre,tblclientes_atencion.apellido_paterno,tblclientes_atencion.apellido_materno ) as nombre_atencion,
               tblclientes_atencion.telefono as telefono_atencion,
               tblclientes_atencion.correo as correo_atencion
               from tblservicios_enc
               inner join tblempleados on tblempleados.id = tblservicios_enc.id_vendedor
               inner join tblclientes on tblclientes.id = tblservicios_enc.id_cliente
               inner join tblsucursales on tblsucursales.id = tblempleados.idsucursal
               inner join tblempresas on tblempresas.id = tblsucursales.idempresa
               inner join tblclientes_atencion on tblclientes_atencion.id = tblservicios_enc.id_atencion
               inner join tblpuestos on tblpuestos.id = tblempleados.idpuesto where tblservicios_enc.id = ?
               ;",[$id]));
            return $item;    

    }


    public function total_productos(int $id)
    {
         
     $estado = "fayo";
          $total = collect(DB::select("select 
               tblservicios_productos.cantidad_total,
               tblproductos.precio_unitario,
               sum(tblservicios_productos.cantidad_total * tblproductos.precio_unitario) as precio
               from tblservicios_productos 
               inner join tblproductos on tblproductos.id = tblservicios_productos.id_producto
               where tblservicios_productos.id_servicio_enc = ?
               ;",[$id]))->first();

          $servicios_det = collect(DB::select("select *
               from tblservicios_det 
               where id_servicio_enc = ?
               ;",[$id]))->first();

          if(is_null($servicios_det)){
               $servicios_det =DB::select("INSERT 
               INTO tblservicios_det (id, nombre, id_servicio_enc, id_concepto, monto_proyectado, monto_real) 
               VALUES (NULL, 'ND', ? , '14', ?, '0.00');",[$id, $total->precio]);
               $estado = "exito";
          }else{
               $servicios_det =DB::select("UPDATE 
               tblservicios_det SET monto_proyectado = ? WHERE id_servicio_enc = ?;",[$total->precio, $id]);
               $estado = "exito";
          }

            return $estado;    

    }

}