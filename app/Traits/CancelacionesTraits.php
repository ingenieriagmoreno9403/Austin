<?php
namespace App\Traits;

use Illuminate\Support\Facades\Request;
use DB;

trait CancelacionesTraits
{

   public function obtenerprestamospordis(int $id)
   {
      $mysql = DB::select('SELECT c.iddistribuidor,e.id as idprestamos 
        FROM tblclientes_vales c
        INNER JOIN tblprestamos_valesenc e on c.id = e.idcliente
        WHERE c.iddistribuidor = ? and e.status = "A";', [$id]);
      return collect($mysql);
   }

   public function obtenervalera(int $id)
   {
      $mysql = DB::select('SELECT idvalera FROM tbldistribuidor_valeras WHERE iddistribuidor = ? ;', [$id]);
      return collect($mysql);
   }

   public function obtenerplazosacancelar(int $id)
   {
      $mysql = DB::select('SELECT d.id  from tblclientes_vales c
               INNER JOIN tblprestamos_valesenc e on c.id = e.idcliente
               INNER JOIN tblprestamos_valesdet d on d.idprestamo_vales = e.id
               WHERE c.iddistribuidor = 1 and d.id NOT IN(
                  SELECT d.id  FROM tblclientes_vales c
                  INNER JOIN tblprestamos_valesenc e on c.id = e.idcliente
                  INNER JOIN tblprestamos_valesdet d on d.idprestamo_vales = e.id
                  WHERE c.iddistribuidor = 1 and d.status = "P");', [$id]);
      return collect($mysql);
   }

   public function obtenelistacancelaciones(int $id)
   {
      $mysql = DB::select('SELECT id_dis_cli,tipo_cancelacion,Comentarios 
         FROM tblcancelaciones WHERE id_dis_cli = ?', [$id]);
      return collect($mysql);
   }

   public function checarsitienpagosdis(int $id)
   {
      $mysql = DB::select('SELECT id,id_distribuidor FROM tblpagos_enc WHERE id_distribuidor = ?;', [$id]);
      return collect($mysql);
   }

   public function obtenercoordinadoresemp()
   {
      $mysql = DB::select('SELECT id,
         CONCAT(primer_nombre," ",segundo_nombre," ",apellido_paterno," ",apellido_materno)as Nombre, idpuesto 
         FROM tblempleados WHERE idpuesto  = 19;');
      return collect($mysql);
   }

   public function obteneridprestamocliente(int $id)
   {
      $mysql = DB::select('SELECT id 
         FROM tblprestamos_valesenc 
         WHERE idcliente = ?  AND status = "F" 
         ORDER BY ID DESC LIMIT 1', [$id]);
      return collect($mysql);
   }

   public function obteneridprestamoclienteActivo(int $id)
   {
      $mysql = DB::select('SELECT id FROM tblprestamos_valesenc 
         WHERE idcliente = ? AND status = "A" ORDER BY ID DESC LIMIT 1', [$id]);
      return collect($mysql);
   }

   public function validapagosclienteActivo(int $id)
   {
      $mysql = DB::select('SELECT tblprestamos_valesenc.idcliente, 
            tblprestamos_valesenc.status, 
            tblprestamos_valesdet.plazos,
            tblprestamos_valesdet.status as pago_status 
         FROM tblprestamos_valesenc 
         INNER JOIN tblprestamos_valesdet on tblprestamos_valesenc.id = tblprestamos_valesdet.idprestamo_vales
         WHERE tblprestamos_valesenc.idcliente = ? and tblprestamos_valesdet.status = "P";', [$id]);
      return collect($mysql);
   }

   public function obtenerdetalleprestamosafinar(int $idprestamo)
   {
      $mysql = DB::select('SELECT * FROM tblprestamos_valesdet WHERE idprestamo_vales = ?;', [$idprestamo]);
      return collect($mysql);
   }

   public function obtenerdatosprestamocli(int $id)
   {
      $mysql = DB::select('SELECT id, id_odp, referencia_odp,
        monto_vale, id_cuenta, folio_vale,id_caja, otrosconceptos1
        FROM tblprestamos_valesenc 
        WHERE idcliente = ? limit 1;', [$id]);
      return collect($mysql);
   }

   public function obtenersaldocuenta_2(int $idcuenta)
   {
      $mysql = DB::select('SELECT saldo_actual FROM tblcuentas WHERE id = ? ;', [$idcuenta]);
      return collect($mysql);
   }
}