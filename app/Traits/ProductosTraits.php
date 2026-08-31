<?php
namespace App\Traits;
use Illuminate\Support\Facades\Request;
use DB;
use Carbon\Carbon;
use App\Models\Productos;
use Illuminate\Http\Request as peticion;
use File;

trait ProductosTraits
{
    public function Listadoproductos()
    {
        $listado = DB::select("select 
            a.id as idproducto,
            a.sku,
            a.descripcion,
            a. precio_unitario,
            a.nombre , 
            a.id_categoria,
            b.nombre as nombrecate,
            b.subcategoria as categoria_producto,
            a.id_unidad_medida,
            d.nombre as unidadmedida,
            a.id_proveedor,
            c.nombre as provedor,
            a.costo_compra,
            a.costo_venta,
            a.minima_existencia,
            a.maxima_existencia,
            a.fecha_ultima_entrada,
            a.fecha_ultima_salida,
            a.ruta_img1,
            a.ruta_img2,
            a.ruta_img3, 
            a.codigo_barras
            from tblproductos a
            inner join tblcategoria_producto b on a.id_categoria = b.id
            left join tblprovedores c on c.id = a.id_proveedor
            inner join tblunidadesmedida d on d.id = a.id_unidad_medida;");
        return collect($listado);
    }

    public function Listadoproductosxid(int $idproducto)
    {
        $listaxid = DB::select("select a.id as idproducto,
                            a.sku,
                            a.descripcion,
                            a. precio_unitario,
                            a.nombre, 
                            a.id_categoria,
                            b.subcategoria as categoria_producto,
                            a.id_unidad_medida,
                            d.nombre as unidadmedida,
                            a.id_proveedor,
                            b.nombre as provedor,
                            a.costo_compra,
                            a.costo_venta,
                            a.minima_existencia,
                            a.maxima_existencia,
                            a.fecha_ultima_entrada,
                            a.fecha_ultima_salida,
                            a.ruta_img1,
                            a.ruta_img2,
                            a.ruta_img3, 
                            a.piezasxunidmedida,
                            a.codigo_barras
                            from tblproductos a
                            inner join tblcategoria_producto b on a.id_categoria = b.id
                            left join tblprovedores c on c.id = a.id_proveedor
                            inner join tblunidadesmedida d on d.id = a.id_unidad_medida where a.id = ?;",[$idproducto]);
        return collect($listaxid);
    }

    public function Listadoproductosxubicacion(int $idubicacion)
    {
        $listado = DB::select("select 
        a.id as idex,
        b.id as id_producto,
        b.nombre,
        b.descripcion,
        a.id_ubicacion as idubi,
        a.id_almacen as alma,
        ubi.folio_interno as ubi,
        al.folio_interno as nombreal,
        b.sku,
        b.codigo_barras,
        b.nombre as nombrepro,
        c.subcategoria,
        p.nombre as provedor,
        b.precio_unitario,
        b.costo_compra,
        b.costo_venta,
        b.minima_existencia,
        b.maxima_existencia,
        b.fecha_ultima_entrada,
        b.fecha_ultima_salida,
        b.ruta_img1,
        b.ruta_img2,
        b.ruta_img3,
        u.nombre as unidad,
        a.cantidad_existente,
        a.cantidad_reservada,
        ubi.espacio,
        ubi.nivel,
        ubi.ubicacion,
        a.id_estado_movinv,
        es.nombre_estado,
        a.productos_arecibir
        from tblexistencias a 
        inner join tblproductos b on a.id_producto = b.id 
        inner join tblcategoria_producto c on c.id = b.id_categoria
        left join tblprovedores p on p.id = b.id_proveedor
        inner join tblunidadesmedida u on u.id = b.id_unidad_medida 
        inner join tblalmacenes al on al.id = a.id_almacen
        inner join tblubicaciones ubi on ubi.id = a.id_ubicacion
        inner join tblestadosinventarios es on es.id = a.id_estado_movinv
        where a.id_ubicacion = ?",[$idubicacion]);
        return collect($listado);
    }

    public function  Listacategoriaproductos()
    {
        $listacategorias = DB::select("select id,nombre,subcategoria as descripcion from tblcategoria_producto;");
        return collect($listacategorias);
    }

    /**
     * Inserta una categoría en tblcategoria_producto y devuelve el id autogenerado.
     */
    /**
     * @return array{id: int, creado: bool}
     */
    public function insertarCategoriaProducto(string $nombre): array
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            return ['id' => 0, 'creado' => false];
        }
        $existe = DB::table('tblcategoria_producto')->where('nombre', $nombre)->first();
        if ($existe) {
            return ['id' => (int) $existe->id, 'creado' => false];
        }
        $id = (int) DB::table('tblcategoria_producto')->insertGetId([
            'nombre' => $nombre,
            'subcategoria' => $nombre,
        ]);

        return ['id' => $id, 'creado' => true];
    }

    public function Listadounidadesmedida()
    {
        $listaunidadesmedida = DB::select("select id,nombre,descripcion from tblunidadesmedida;");
        return collect($listaunidadesmedida);
    }

    public function Insertaprodcuto(string $tipo, int $id, string $descripcion, string $sku, string $codigo_barras, float $precio_unitario, string $nombre, int $id_categoria, int $id_unidad_medida, int $id_proveedor,
            float $costo_compra, float $costo_venta, float $minima_existencia, float $maxima_existencia, string $fecha_entrada, string $ruta_img1, string $ruta_img2,string $ruta_img3 ,int $piezasxuni)
    {
        $boloenadosuccess = 0;
        $date = Carbon::now();
        $fecha = $date->format('Y-m-d');


        if($tipo == 'Inserta')
        {
          $costo_compraInsertapro = new Productos();
        }
        if ($tipo == 'Actualiza')
        {
          $costo_compraInsertapro =  Productos::find($id);
          
        }

        $costo_compraInsertapro->descripcion = $descripcion;
        $costo_compraInsertapro->sku =$sku;
        $costo_compraInsertapro->codigo_barras = $codigo_barras;
        $costo_compraInsertapro->precio_unitario = $precio_unitario;
        $costo_compraInsertapro->nombre = $nombre;
        $costo_compraInsertapro->id_categoria = $id_categoria;
        $costo_compraInsertapro->id_unidad_medida = $id_unidad_medida;
        $costo_compraInsertapro->id_proveedor = $id_proveedor;
        $costo_compraInsertapro->costo_compra = $costo_compra;
        $costo_compraInsertapro->costo_venta = $costo_venta;
        $costo_compraInsertapro->minima_existencia = $minima_existencia;
        $costo_compraInsertapro->maxima_existencia = $maxima_existencia;
        $costo_compraInsertapro->fecha_ultima_entrada = $fecha_entrada;
        if($ruta_img1 != "null"){
            $costo_compraInsertapro->ruta_img1 = $ruta_img1;
        }
        if($ruta_img2 != "null"){
            $costo_compraInsertapro->ruta_img2 = $ruta_img2;
        }
        if($ruta_img3 != "null"){
            $costo_compraInsertapro->ruta_img3 = $ruta_img3;
        }
        
        
        if($piezasxuni=="")
        {
            $costo_compraInsertapro->piezasxunidmedida =$piezasxuni;
        }
        else
        {
            $costo_compraInsertapro->piezasxunidmedida=0;
        }

        if($costo_compraInsertapro->save())
        {
          $boloenadosuccess = 1;
        }
        else
        {
  
        }
        return $boloenadosuccess;
    }

    public function validasku(string $sku)
    {
        $varvalidasku = 0;
        $bucasku = DB::select("select sku from tblproductos where sku = ?",[$sku]);
      

        if(collect($bucasku)->isempty())
        {
            $varvalidasku = 0;
        }
        else
        {
            $varvalidasku = 1;
        }

        return $varvalidasku;
    }


    public function Guardarimgproductos(String $nombreinput, string $carpeta, string $nombrearchivo, peticion $request )
    {

        $nombreusuario = '';
        if($request->hasFile($nombreinput))
        { 
        $path = public_path('images'.'/Productos'.'/'.$carpeta);

        if (file_exists($path))
        {
        }
        else
        {
            File::makeDirectory($path, 0777, true);
        }
        
         $rutadestino ='images/Productos/'.$carpeta.'';
         $file_foto = $request->file($nombreinput);
         $nombreusuario = $nombrearchivo.'.'.$file_foto->guessExtension();

         $ruta_foto = public_path($rutadestino."/".$nombreusuario);
         copy($file_foto, $ruta_foto);
     
        }
         else
         {
             error_log('No tienes este archivo');
         }
         return $nombreusuario;
    }


    public function GuardarArchivopdf(String $nombreinput, string $carpeta, string $usuario, string $tipoarchivo, peticion $request )
    {
        $archivos = "";
        if($request->hasFile($nombreinput))
        {
            if($tipoarchivo == 'Distribuidor' || $tipoarchivo == 'Aval')
            {
                $archivo = $request->file($nombreinput);
                $Rutacarpeta = "archivos"."/Documentosdistribuidores"."/".$carpeta;
                foreach ($archivo as $A ) 
                {
                    $archivos = $A;
                }
                $nombre_archivo = $tipoarchivo.'_'.$usuario.'.'.$archivos->guessExtension();
            }
            else
            {
                $archivos = $request->file($nombreinput);
                $Rutacarpeta = "archivos"."/".$carpeta;
                $nombre_archivo = $tipoarchivo.'_'.$usuario.'.'.$archivos->guessExtension();
            }    
            $ruta_contrato = public_path($Rutacarpeta."/".$nombre_archivo);
            copy($archivos, $ruta_contrato);

            return $nombre_archivo;
        }
        else
        {
           
        }
    }

    public function Listadoproxid(int $idproducto)
    {
        $datospro = DB::select("select id,sku,nombre,codigo_barras from tblproductos where id = ?",[$idproducto]);
        return collect($datospro);
    }

    public function Listadatosalmxid(int $idalmacen)
    {
        $datosalm = DB::select("select folio_interno from tblalmacenes where id = ?",[$idalmacen]);
        return collect($datosalm);
    }

    public function Listadatosubixid(int $idubi)
    {
        $datosubi =DB::select("select folio_interno from tblubicaciones where id = ?",[$idubi]);
        return collect($datosubi);
    }

    public function listaexisproxubi(int $idpro, int $idub)
    {
        $lista = DB::select("select cantidad_existente from tblexistencias where id_producto = ? and id_ubicacion = ?",[$idpro,$idub]);
        return collect($lista);
    }

    public function obteneridexis(int $id_producto, int $id_ubicacion)
    {
        $ide = 0;
       $idexistencia = DB::select("select id from tblexistencias where id_producto = ? and id_ubicacion = ?;",[$id_producto,$id_ubicacion]);
       foreach($idexistencia as $id)
       {
        $ide = $id->id;
       }
       return $ide;;
    }

    public function GuardarArchivopdfgeneral(string $nombreinput, string $usuario, string $tipoarchivo, peticion $request,string $fecha)
    {
        $archivos = "";
        if($request->hasFile($nombreinput))
        {
            echo "entra";
        
                $archivos = $request->file($nombreinput);
                $Rutacarpeta = "archivos/";
                $nombre_archivo = $tipoarchivo.'fecha'.$fecha.'_usuario'.$usuario.'.'.$archivos->guessExtension();
               
                $ruta_contrato = public_path($Rutacarpeta."/".$nombre_archivo);
                echo  $ruta_contrato;
            copy($archivos, $ruta_contrato);

            return $nombre_archivo;
        }
        else
        {
           return "error";
        }
    }

    public function Buscaexistenciaporubipro(int $idu,int $idpro)
    {
        $buscaproducto = DB::select("select a.id,a.id_almacen,al.folio_interno as nombre_almacen, a.id_ubicacion,ubi.folio_interno as nombreubi ,a.cantidad_existente, pro.nombre from tblexistencias a
                                    inner join tblalmacenes al on al.id = a.id_almacen
                                    inner join tblubicaciones ubi on ubi.id = a.id_ubicacion
                                    inner join tblproductos pro on pro.id = a.id_producto
                                    where id_ubicacion = ? and id_producto = ?;",[$idu,$idpro]);

        if(collect($buscaproducto)->isempty())
        {
            return 'inserto';
        }
        else
        {
            return collect($buscaproducto);
        }
    }

    public function productosEnviados(int $idubicacion)
    {
        $listado = DB::select("select 
        a.id as idex,
        b.id as id_producto,
        b.nombre,
        b.descripcion,
        a.id_ubicacion as idubi,
        a.id_almacen as alma,
        ubi.folio_interno as ubi,
        al.folio_interno as nombreal,
        b.sku,
        b.codigo_barras,
        b.nombre as nombrepro,
        c.subcategoria,
        p.nombre as provedor,
        b.precio_unitario,
        b.costo_compra,
        b.costo_venta,
        b.minima_existencia,
        b.maxima_existencia,
        b.fecha_ultima_entrada,
        b.fecha_ultima_salida,
        b.ruta_img1,
        b.ruta_img2,
        b.ruta_img3,
        u.nombre as unidad,
        a.cantidad_existente,
        a.cantidad_reservada,
        ubi.espacio,
        ubi.nivel,
        ubi.ubicacion,
        a.id_estado_movinv,
        es.nombre_estado,
        a.productos_arecibir
        from tblexistencias a 
        inner join tblproductos b on a.id_producto = b.id 
        inner join tblcategoria_producto c on c.id = b.id_categoria
        left join tblprovedores p on p.id = b.id_proveedor
        inner join tblunidadesmedida u on u.id = b.id_unidad_medida 
        inner join tblalmacenes al on al.id = a.id_almacen
        inner join tblubicaciones ubi on ubi.id = a.id_ubicacion
        inner join tblestadosinventarios es on es.id = a.id_estado_movinv
        where a.id_ubicacion = ?",[$idubicacion]);
        return collect($listado);
    }
}