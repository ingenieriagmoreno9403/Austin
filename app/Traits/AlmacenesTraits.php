<?php
namespace App\Traits;
use Illuminate\Support\Facades\Request;
use DB;
use Carbon\Carbon;
use App\Models\Almacenes;



trait AlmacenesTraits
{

    public function Listadoalmacenes()
    {
        $listadoalmacenes = DB::select("
            select
            a.id,
            a.folio_interno,
            c.tipo_almacen,
            b.nombre as ciudad,
            case
            when d.segundo_nombre is null then concat(d.primer_nombre,' ',d.segundo_nombre,' ',d.apellido_materno)
            else concat(d.primer_nombre,' ',d.segundo_nombre,' ',d.apellido_materno,' ',d.apellido_materno) end as nombre_encargado,
            a.direccion,
            a.codigo_postal,
            a.telefono,
            a.correo_electronico,
            a.contacto,
            a.capacidad,
            a.comentarios,
            a.estado
            from tblalmacenes a
            inner join tblciudades b on a.id_municipio = b.id
            inner join tbltipos_almacenes c on c.id = a.id_tipo_almacen
            inner join tblempleados d on d.id = a.id_encargado;");
        return collect($listadoalmacenes);
    }

    public function Listadoalmacenxid(int $id)
    {
        $listadoalmacenes = DB::select("
            select 
            a.id as idalmacen,
            a.folio_interno,
            c.id as idtipoa,
            c.tipo_almacen,
            b.id as idciudad,
            b.nombre as ciudad,
            d.id as idencargado,
            case
            when d.segundo_nombre is null then concat(d.primer_nombre,' ',d.segundo_nombre,' ',d.apellido_materno)
            else concat(d.primer_nombre,' ',d.segundo_nombre,' ',d.apellido_materno,' ',d.apellido_materno) end as nombre_encargado,
            a.direccion,
            a.codigo_postal,
            a.telefono,
            a.correo_electronico,
            a.contacto,
            a.capacidad,
            a.comentarios,
            a.estado
            from tblalmacenes a
            inner join tblciudades b on a.id_municipio = b.id
            inner join tbltipos_almacenes c on c.id = a.id_tipo_almacen
            inner join tblempleados d on d.id = a.id_encargado where a.id = ?;",[$id]);
        return collect($listadoalmacenes);
    }

    public function InsertaroActualizaalmacen(string $tipo, int $id, string $folio_interno, int $id_tipo_almacen, int $id_municipio, int $id_encargado, string $direccion,  string $codigo_postal, string $telefono, string $correo_electronico, string $contacto, float $capacidad, string $comentarios)
    {
        $date = Carbon::now();
        $fecha = $date->format('Y-m-d');
        $boloenadosuccess = 0;

      if($tipo == 'Inserta')
      {
        $insetaalmacen = new Almacenes();
      }
      if ($tipo == 'Actualiza')
      {
        $insetaalmacen =  Almacenes::find($id);
      }
      
    
      $insetaalmacen->folio_interno = $folio_interno;
      $insetaalmacen->id_tipo_almacen = $id_tipo_almacen;
      $insetaalmacen->id_municipio  = $id_municipio;
      $insetaalmacen->id_encargado = $id_encargado;
      $insetaalmacen->direccion = $direccion;
      $insetaalmacen->codigo_postal = $codigo_postal;
      $insetaalmacen->telefono = $telefono;
      $insetaalmacen->correo_electronico =  $correo_electronico;
      $insetaalmacen->contacto = $contacto;
      $insetaalmacen->capacidad = $capacidad;
      $insetaalmacen->comentarios = $comentarios;
      $insetaalmacen->estado = 'A';
      $insetaalmacen->created_at = $fecha;
      if($insetaalmacen->save())
      {
        $boloenadosuccess = 1;
      }
      else
      {

      }

      return $boloenadosuccess;
    }

    public function Eliminaralmacen()
    {

    }

    public function Listadoproductosa()
    {
        $listaprodcutos = DB::select("select a.id, a.sku, a.precio_unitario, a.nombre, a.id_categoria, a.id_unidad_medida, a.id_proveedor, a.costo_compra, 
        a.costo_venta, a.minima_existencia, a.maxima_existencia, a.fecha_ultima_entrada, 
        a.fecha_ultima_salida, a.ruta_img1, a.ruta_img2, a.ruta_img3, a.codigo_barras,  a.descripcion,
        b.descripcion,c.nombre as unidad_medida, d.nombre
        from tproductos a
        inner join tblcategorias_productos b on a.id_categoria = b.id
        inner join tblunidades_medida c on c.id = a.id_unidad_medida
        inner join tblprovedores d on d.id = a.id_proveedor;");

        return collect($listaprodcutos);
    }

    public function Listadoproductoxid(int $id)
    {
        $listaprodcutos = DB::select("select a.id, a.sku, a.precio_unitario, a.nombre, a.id_categoria, a.id_unidad_medida, a.id_proveedor, a.costo_compra, 
        a.costo_venta, a.minima_existencia, a.maxima_existencia, a.fecha_ultima_entrada, 
        a.fecha_ultima_salida, a.ruta_img1, a.ruta_img2, a.ruta_img3, a.codigo_barras,  a.descripcion,
        b.descripcion,c.nombre as unidad_medida, d.nombre
        from tproductos a
        inner join tblcategorias_productos b on a.id_categoria = b.id
        inner join tblunidades_medida c on c.id = a.id_unidad_medida
        inner join tblprovedores d on d.id = a.id_proveedor where a.id;",[$id]);

        return collect($listaprodcutos);
    }   

    public function Listadotiposalmacen()
    {
        $listatiposalmacen = DB::select("select * from tbltipos_almacenes");
        return collect($listatiposalmacen);
    }

    public function Listadomunicipios()
    {
        $listado = DB::select("select * from tblciudades");
        return collect($listado);
    }

    public function Listadoempleadosalmacen()
    {
        $listaempleados =DB::select("select * from tblempleados");
        return collect($listaempleados);
    }

    public function Listadoubicacionesxidalmacen(int $idalmacen)
    {
      $lidatosubicaciones = DB::select("select 
                                        b.id as id_almacen,
                                        a.id as id_ubicacion,
                                        a.folio_interno,
                                        a.descripcion,
                                        a.espacio,
                                        a.nivel,
                                        a.ubicacion
                                        from tblubicaciones a
                                        inner join tblalmacenes b on a.id_almacen = b.id
                                        inner join tbltipos_ubicaciones c on c.id = a.id_tipo_ubicacion
                                        where a.id_almacen = ?;",[$idalmacen]);
      return collect($lidatosubicaciones);
    }

    public function Listadotiposubi()
    {
      $listadoubi = DB::select("select * from tbltipos_ubicaciones");
      return collect($listadoubi);
    }

    public function Listaubixid(int $id)
    {
      $listaubixid = DB::select("select * from tblubicaciones where id = ?",[$id]);
      return collect($listaubixid);
    }

    

}