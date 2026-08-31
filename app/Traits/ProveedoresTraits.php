<?php
namespace App\Traits;
use Illuminate\Support\Facades\Request;
use DB;
use App\Models\provedores;
use Carbon\Carbon;
trait ProveedoresTraits
{
    public function Listadoproveedores()
    {
        $listaproveedores = DB::select("select id, nombre, telefono, direccion, estado, nit, giro, rfc, calificacion_proveedor,otrosconceptos1 from tblprovedores");
        return collect($listaproveedores);
    }

    public function Listadoproveedoresxid( $idprov)
    {
        $listaproveedoresxid = DB::select("select id, nombre, telefono, direccion, estado, nit, giro, rfc, otrosconceptos1, calificacion_proveedor from tblprovedores 
        where id = ?",[$idprov]);
        return collect($listaproveedoresxid);
    }


    public function Insertarooactuproveedor(string $tipo,int $id, string $nombre, string $telefono, string $direccion, string $nit, string $giro, string $rfc, string $calificacion_proveedor, string $correo)
    {
        $date = Carbon::now();
        $fecha = $date->format('Y-m-d');
        $boloenadosuccess = 0;

      if($tipo == 'Inserta')
      {
        $insertaprov = new provedores();
      }
      if ($tipo == 'Actualiza')
      {
        $insertaprov =  provedores::find($id);
      }
      


      $insertaprov->nombre = $nombre;
      $insertaprov->telefono = $telefono;
      $insertaprov->direccion  = $direccion;
      $insertaprov->estado = 'A';
      $insertaprov->nit = $nit;
      $insertaprov->giro = $giro;
      $insertaprov->rfc = $rfc;
      $insertaprov->calificacion_proveedor =  $calificacion_proveedor;
      $insertaprov->otrosconceptos1 = $correo;
      $insertaprov->created_at = $fecha;
      if($insertaprov->save())
      {
        $boloenadosuccess = 1;
      }
      else
      {

      }

      return $boloenadosuccess;
    }


    public function Eliminarproveedor(int $idprov)
    {
        $boloenadosuccess = 0;
        $eliminaprovedor = DB::delete("delete from tprovedores where id = ?",[$idprov]);

        if($eliminaprovedor > 0 )
        {
            $boloenadosuccess =1;
        }
        else
        {

        }
        return $boloenadosuccess;
    }

    public function obtenerprovxfoliointerno(int $nit)
    {
      $boleanossuccess = 0;
      $lista = DB::select("select id,nit,nombre from tprovedores where nit = ?",[$nit]);

      if(collect($lista)->isEmpty())
      {
      
      }
      else
      {
        //encontro algo
        $boleanossuccess = 1;
      }

      return $boleanossuccess;
    }

}