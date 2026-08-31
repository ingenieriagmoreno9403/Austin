<?php
namespace App\Traits;
use App\Models\Vistas;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use DB;
trait MenuTrait{
 

public  function Traermenuenc(){
    $var = DB::select('select DISTINCT tbldepartamentos.nombre, tbldepartamentos.icon
        FROM tblusuario_perfiles
        inner join tblperfiles on tblperfiles.id = tblusuario_perfiles.id_perfil
        inner join tblperfil_acciones on tblperfil_acciones.idperfil = tblperfiles.id
        inner join tblacciones on tblacciones.id = tblperfil_acciones.idaccion
        INNER JOIN tblvistas ON tblvistas.id = tblacciones.idvista
        INNER JOIN users ON users.id = tblusuario_perfiles.id_usuario
        INNER JOIN tbldepartamentos ON tbldepartamentos.id = tblvistas.iddepartamento
        WHERE users.id = ? AND tblvistas.estado = 1
    ORDER BY tbldepartamentos.orden ASC;',[auth() ->user()->id]);
    return collect($var);

    // SELECT DISTINCT tbldepartamentos.nombre, tbldepartamentos.icon
    // FROM tblvistas
    // INNER JOIN tblusuario_pantallas ON tblusuario_pantallas.idvista = tblvistas.id
    // INNER JOIN users ON users.id = tblusuario_pantallas.idusuario
    // INNER JOIN tbldepartamentos ON tbldepartamentos.id = tblusuario_pantallas.iddepartamento
    // WHERE users.name = "master"
    // ORDER BY tbldepartamentos.nombre;
}


public function Traermenudet(){
    $varsubmenu = DB::select('select DISTINCT tbldepartamentos.nombre, tblvistas.nombre AS nom, tblvistas.descripcion AS descripcion  
        FROM tblusuario_perfiles
        inner join tblperfiles on tblperfiles.id = tblusuario_perfiles.id_perfil
        inner join tblperfil_acciones on tblperfil_acciones.idperfil = tblperfiles.id
        inner join tblacciones on tblacciones.id = tblperfil_acciones.idaccion
        INNER JOIN tblvistas ON tblvistas.id = tblacciones.idvista
        INNER JOIN users ON users.id = tblusuario_perfiles.id_usuario
        INNER JOIN tbldepartamentos ON tbldepartamentos.id = tblvistas.iddepartamento
    where users.id = ? AND tblvistas.estado = 1
    ORDER BY tblvistas.orden ASC;',[auth() ->user()->id]);
    return collect($varsubmenu);

    // select tbldepartamentos.nombre, tblvistas.nombre AS nom, tblvistas.descripcion AS descripcion  from tblvistas 
    // inner join tblusuario_pantallas  on tblusuario_pantallas.idvista = tblvistas.id 
    // inner join users on users.id = tblusuario_pantallas.idusuario 
    // inner join tbldepartamentos on tbldepartamentos.id = tblusuario_pantallas.iddepartamento 
    //  where users.id = ? order by tblvistas.nombre asc;
}
}


