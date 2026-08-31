<?php
namespace App\Traits;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\movimientos_inventarios;
use App\Models\existencias;
use App\Models\logerrores;
use App\Models\productos_en_movimiento;


trait InventariosTraits
{
    
    public function Buscaexistenciaporalmacenyubi(int $idalm,int $idu,int $idpro)
    {
        $buscaproducto = DB::select("select id,id_almacen,id_ubicacion,cantidad_existente from tblexistencias where id_almacen = ? and id_ubicacion = ? and id_producto = ?;",[$idalm,$idu,$idpro]);

        if(collect($buscaproducto)->isempty())
        {
            return 'inserto';
        }
        else
        {
            return collect($buscaproducto);
        }
    }

    public function Registramovinventario(int $id_producto, int $id_almacen, int $id_ubicacion, int $id_tipo_movimiento, int $id_estado_movinv, int $cantidad_producto_movimiento, string $fecha_movimiento, string $documento_referencia, string $observaciones, string $created_at, int $usuario_movimiento, int $entrada_inventario_id =0)
    {
        $insertamovtransferencia = new movimientos_inventarios();
        $insertamovtransferencia->id_producto = $id_producto;
        $insertamovtransferencia->id_almacen = $id_almacen;
        $insertamovtransferencia->id_ubicacion = $id_ubicacion;
        $insertamovtransferencia->id_tipo_movimiento = $id_tipo_movimiento;
        $insertamovtransferencia->id_estado_movinv = $id_estado_movinv;
        $insertamovtransferencia->cantidad_producto_movimiento = $cantidad_producto_movimiento;
        $insertamovtransferencia->fecha_movimiento = $fecha_movimiento;
        $insertamovtransferencia->documento_referencia = $documento_referencia;
        $insertamovtransferencia->observaciones = $observaciones;
        $insertamovtransferencia->created_at = $created_at;
        $insertamovtransferencia->usuario_movimiento = $usuario_movimiento;
        $insertamovtransferencia->entrada_inventario_id = $entrada_inventario_id;

        if($insertamovtransferencia->save())
        {
            return "inserto";
        }
        else
        {
            return "error";
        }
    }

    public function Registrarerror(string $descripcionerror,string $fecha)
    {
        $insertaerrortranferencia = new logerrores();
        $insertaerrortranferencia->descripcion_error =$descripcionerror;
        $insertaerrortranferencia->created_at = $fecha;

        if($insertaerrortranferencia->save())
        {
            return "inserto";
        }
        else
        {
            return "error";
        }
    }

    public function Actualizastock(int $idexistencia, float $cantidadnuevaproducto,float $productotranferido)
    {
        $actualizaexistencia = existencias::find($idexistencia);
        $actualizaexistencia->cantidad_existente = $cantidadnuevaproducto;
        $actualizaexistencia->productos_arecibir =$productotranferido;

        if($actualizaexistencia->save())
        {
            return "inserto";
        }
        else
        {
            return "error";
        }
    }


    public function Diminuyestcok(int $idexistencia, float $cantidadnuevaproducto,float $productotranferido)
    {

       
        $actualizaexistencia = existencias::find($idexistencia);
        $actualizaexistencia->cantidad_existente = $cantidadnuevaproducto;


        if($actualizaexistencia->save())
        {
            return "inserto";
        }
        else
        {
            return "error";
        }
    }


    public function Insertaproenmovimiento(int $id_producto, int $ubicacion_actual,float $cantidad, string $comentario, string $fecha)
    {
      $insertapro = new productos_en_movimiento();
      $insertapro->id_producto = $id_producto;
      $insertapro->ubicacion_actual = $ubicacion_actual;
      $insertapro->cantidad = $cantidad;
      $insertapro->comentario = $comentario;
      $insertapro->fecha_tranferencia = $fecha;
      $insertapro->created_at = $fecha;

      if($insertapro->save())
      {
        return "inserto";
      }
      else
      {
        return "error";
      }
    }

    public function Insertarececpionproducto(int $idexistencias,float $cantidadacutal,float $cantidadtranferencia)
    {
        

        $cantidadfinal = 0;
        $cantidadfinal = $cantidadacutal+$cantidadtranferencia;
        $actualizaexistencia = existencias::find($idexistencias);
        $actualizaexistencia->cantidad_existente = $cantidadfinal;
        $actualizaexistencia->productos_arecibir = 0;
        $actualizaexistencia->id_estado_movinv = 2;

        if($actualizaexistencia->save())
        {
            return "inserto";
        }
        else
        {
            return "error";
        }
    }

    public function Reporte_movimientos_inventarios()
    {
        $Reporte = DB::select("SELECT 
            mv.id as numero_movimineto,
            al.id as idalmacen,al.folio_interno as almacen,
            ub.id as idubicacion, ub.folio_interno as ubicacion,
            mv.observaciones,
            tmv.nombre_movimiento,
            p.id as id_producto,
            p.sku,
            p.codigo_barras,
            p.nombre,
            mv.cantidad_producto_movimiento as cantidadp_movida,
            mv.documento_referencia
            from tblmovimientos_inventario mv
            inner join tblalmacenes al on al.id=mv.id_almacen
            inner join tblubicaciones ub on ub.id_almacen = al.id
            inner join tbltipos_movimientos_inventario tmv on tmv.id = mv.id_tipo_movimiento
            inner join tblproductos p on p.id = mv.id_producto 
            GROUP BY mv.id ORDER BY mv.id DESC;
        ");
        return collect($Reporte);
    }

    public function Listadoproductosnoencontradosxubicacion(int $idubicacion)
    {
        $listado = DB::select("select id,nombre from tblproductos where id not in (select id_producto from tblexistencias where id_ubicacion =  ?)",[$idubicacion]);
        return collect($listado);
    }
}