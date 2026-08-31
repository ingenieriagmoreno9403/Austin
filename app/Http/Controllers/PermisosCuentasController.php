<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\MenuTrait;
use App\Traits\GlobalTraits;
use App\Traits\SistemasTraits;
use App\Traits\DatosimpleTraits;
use App\Models\historial_cuentas;
use App\Models\permisos_cuentas;
use App\Models\modulos_cuentas;
use App\Models\cuentas;
use Carbon\Carbon;
use App\Exports\cuentaHistorial;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Cajas;
use App\Models\historial_cajas;
use DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;

class PermisosCuentasController extends Controller
{
    use MenuTrait;
    use DatosimpleTraits;
    use SistemasTraits;
    use GlobalTraits;

    public function __construct(){
        $this->middleware('auth');
    }

    //CRUD GASTOS
    public function indexPermisosCuentas(){
        try{
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();
            $varCuentas =   $this->obtenercuentasActivas();
            $varCajas =   $this->obtenerCajas();
            $varusers =   $this->obtenerusuarios();
            $varModulosCuentas =   $this->obtenerModulosCuentas();
            $varModulosPermisosUser =   $this->obtenerModulosPermisosUser();
            $permisos1 = $this->forpermisos('añadir_permisoCuenta');
            $permisos2 = $this->forpermisos('eliminar_permisoCuenta');
            $permisos3 = $this->forpermisos('exportar_permisoCuenta');
            return view('Catalogos.PermisosCuentas.index',compact('varpantallas','varsubmenus','varCuentas','permisos1','permisos2','permisos3','varCajas','varModulosCuentas','varusers','varModulosPermisosUser'));
        } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    }

    public function obtenerPermisosUsuario(int $id){
        try{
            $permisos = $this->obtenerPermisosPorUsuario($id);
            return response()->json($permisos);
        } catch(\Illuminate\Database\QueryException $ex){
            return response()->json(['error' => 'No se pudieron obtener los permisos'], 500);
        }
    }

    public function añadirPermisoCuenta(Request $request){
        $id = $request->get('idusuario');

        if (!$id) {
            return back()->with("warningGuardar", true);
        }

        try {
            $permisosEnviados = $request->input('permisos', []);
            $clavePermiso = function ($modulo, $tipo, $idTipo) {
                return $modulo . '|' . $tipo . '|' . $idTipo;
            };

            $enviados = collect();
            foreach ($permisosEnviados as $permiso) {
                $partes = explode('|', $permiso);
                if (count($partes) !== 3) {
                    continue;
                }
                [$modulo, $tipo, $idTipo] = $partes;
                $enviados->put($clavePermiso($modulo, $tipo, $idTipo), [
                    'id_modulo' => (int) $modulo,
                    'tipo' => $tipo,
                    'id_tipo' => (int) $idTipo,
                ]);
            }

            $permisosActuales = permisos_cuentas::where('id_user', $id)->get();

            foreach ($permisosActuales as $permisoActual) {
                $clave = $clavePermiso($permisoActual->id_modulo, $permisoActual->tipo, $permisoActual->id_tipo);
                if (!$enviados->has($clave)) {
                    permisos_cuentas::where('id', $permisoActual->id)->delete();
                }
            }

            foreach ($enviados as $permiso) {
                $validaPermisosModulo = $this->validaPermisosModulo(
                    $id,
                    $permiso['id_modulo'],
                    $permiso['tipo'],
                    $permiso['id_tipo']
                );

                if ($validaPermisosModulo->isEmpty()) {
                    $permisos_cuentas = new permisos_cuentas();
                    $permisos_cuentas->id_modulo = $permiso['id_modulo'];
                    $permisos_cuentas->id_user = $id;
                    $permisos_cuentas->tipo = $permiso['tipo'];
                    $permisos_cuentas->id_tipo = $permiso['id_tipo'];
                    $permisos_cuentas->created_by = auth()->user()->name;
                    $permisos_cuentas->save();
                }
            }

            return back()->with("success", "guardado correctamente");
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function eliminarPermiso(int $id){
        try{
            $Borrartbl1=  DB::select('delete from tblpermisos_cuentas where id = ? ', [$id]);
            return back()->with("success","guardado correctamente");
        } catch(\Illuminate\Database\QueryException $ex){ 
            // dd($ex->getMessage()); 
            return back()->with("warningDatabase","no guardado correctamente");
            // Note any method of class PDOException can be called on $ex.
        }
    }

    public function exportarGasto(Request $request){
        try{
            $tipo  = $request->get('empresa');

            if($tipo == 0){
                return Excel::download(new catalogoGatos, 'CATALOGO DE GASTOS EXTENDIDO.xlsx');
            }else{
                $varempresa =   $this->obtenerempresaxid($request->get('empresa'));
                foreach($varempresa as $empre){$nombre  = $empre->nombre_empresa;}
                return Excel::download(new catalogoGatosxEmpresa($tipo, $nombre), 'CATALOGO DE GASTOS '.$nombre.'.xlsx');
            }
        } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    }


}
