<?php
namespace App\Traits;
use App\Models\promotores;
use App\Models\estados;
use App\Models\distribuidores;
use App\Models\avales;
use App\Models\documentos;
use App\Models\historial;
use App\Models\mensajes;
use App\Models\tipo_distribuidor;
use App\Models\usuario_acciones;
use App\Models\Acciones;
use App\Models\Vistas;
use Illuminate\Support\Facades\Request;
use DB;
use Log;


trait SistemasTraits{


    public function  obtenerpermisos(){
    $varpermisos = acciones::join('tblvistas','tblacciones.idvista','tblvistas.id')
    ->join('tbldepartamentos','tblvistas.iddepartamento','tbldepartamentos.id')
    ->select('tbldepartamentos.id as iddepartamento','tblvistas.id as idvista','tblacciones.id as idacciones','tbldepartamentos.nombre as nombre_departamento','tblvistas.nombre as nombre_vista', 'tblacciones.nombre_accion','tblacciones.descripcion_accion')
    ->get();
    return $varpermisos;
    }
    public function  obtenerpermisosporNombre(string $nombrePermiso){
        $varpermisos = acciones::join('tblvistas','tblacciones.idvista','tblvistas.id')
        ->join('tbldepartamentos','tblvistas.iddepartamento','tbldepartamentos.id')
        ->select('tbldepartamentos.id as iddepartamento','tblvistas.id as idvista','tblacciones.id as idacciones','tbldepartamentos.nombre as nombre_departamento','tblvistas.nombre as nombre_vista', 'tblacciones.nombre_accion','tblacciones.descripcion_accion')
        ->where('tblacciones.nombre_accion',$nombrePermiso)
        ->first();
        return $varpermisos;
        }



    public function obtenerdepartamentoXvista(int $iddistribuidor){
    $vardeparatamento = vistas::select('tblvistas.iddepartamento')
    ->where('tblvistas.id','=',$iddistribuidor)
    ->get();
    return $vardeparatamento;
    }

    public function obtener_permisosxusuario($idusuario, $permisobuscado){
        $varpermisos = DB::select('select 
            tblacciones.nombre_accion,
                tblacciones.id,
                users.name 
                FROM tblusuario_perfiles
                inner join tblperfiles on tblperfiles.id = tblusuario_perfiles.id_perfil
                inner join tblperfil_acciones on tblperfil_acciones.idperfil = tblperfiles.id
                inner join tblacciones on tblacciones.id = tblperfil_acciones.idaccion
                INNER JOIN users ON users.id = tblusuario_perfiles.id_usuario
                where 
                tblusuario_perfiles.id_usuario = ?
                and tblacciones.nombre_accion = ?
                and users.estado_user = "A" 
            order by tblacciones.id asc;',[$idusuario,$permisobuscado]);
        return collect($varpermisos)->first();

        // select b.nombre_accion,b.id,c.name 
        // from tblusuario_acciones a inner join tblacciones b on a.idacciones = b.id 
        // inner join users c on a.idusuario = c.id where a.idusuario = ? and c.estado_user = "A" order by id asc;
    }

    public function forpermisos($permisobuscado){
        $idusuario=auth()->user()->id;
        $permisos = $this->obtener_permisosxusuario($idusuario,$permisobuscado)->nombre_accion ?? "null";  
        return $permisos;
    }

    public function forpermisoconid(string $permisobuscado){
        $idusuario=auth()->user()->id;
        $permisos = $this->obtener_permisosxusuario($idusuario);  
        $permisoa = 0;
        foreach($permisos as $permiso)
        {
            if($permiso->nombre_accion == $permisobuscado){
                $permisoa = $permiso ->id;
                break;
            }
            else{
                $permisoa = "null";
            }

        }
        return $permisoa;
    
    }
}