<?php

namespace App\Http\Controllers;
use App\Models\facturify;
use App\Models\Vistas;
use App\Models\Acciones;
use App\Models\Departamentos;
use App\Models\usuario_pantallas;
use App\Models\usuario_acciones;
use Illuminate\Http\Request;
use App\Traits\MenuTrait;
use Carbon\Carbon;
use App\Traits\DatosimpleTraits;
use App\Traits\SistemasTraits;
use App\Models\perfil_acciones;
use App\Models\perfiles;
use App\Models\UserSucursal;
use Illuminate\Support\Arr;
use DB;
use App\Models\usuario_perfiles;

class SistemasController extends Controller
{
    use MenuTrait;
    use DatosimpleTraits;
    use SistemasTraits;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $varlistausers = $this->obtenerusuarios();
            $permisos1 = $this->forpermisos('registrar_permisos');
            $permisos2 = $this->forpermisos('registrar_acciones');
            $permisos3 = $this->forpermisos('registrar_perfiles');
            $permisos4 = $this->forpermisos('registrar_usuarios');
            $permisos5 = $this->forpermisos('editar_permisos');
            return view('sistemas.index', compact('varpantallas', 'varsubmenus', 'varlistausers', 'permisos1', 'permisos2', 'permisos3', 'permisos4', 'permisos5'));
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function indexPantallas()
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $varlistavistas = $this->obtenervistas();
            $varlistapuestos = $this->obtenerpuestos();
            $varlistausers = $this->obtenerusuarios();
            $varlistadepas = $this->obtenerdepartamentos();
            $varpermiso = $this->obtenerpermisos();
            return view('sistemas.pantallas', compact('varpantallas', 'varsubmenus', 'varlistavistas', 'varlistausers', 'varlistadepas', 'varpermiso'));
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function indexAcciones()
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $varlistavistas = $this->obtenervistas();
            $varlistapuestos = $this->obtenerpuestos();
            $varlistausers = $this->obtenerusuarios();
            $varlistadepas = $this->obtenerdepartamentos();
            $varacciones = $this->obteneracciones();
            $varlistaempresas = collect(DB::select('SELECT id, nombre_empresa FROM tblempresas ORDER BY nombre_empresa asc;'));
            return view('sistemas.acciones', compact('varpantallas', 'varsubmenus', 'varlistavistas', 'varlistausers', 'varlistadepas', 'varacciones', 'varlistaempresas'));
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function indexfacturacion()
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $varlistavistas = $this->obtenervistas();
            $datosfacturify = $this->ObtenerDatosFacturify();
            return view('sistemas.facturaconfig', compact('varpantallas', 'varsubmenus', 'varlistavistas','datosfacturify'));
        } catch (\Illuminate\Database\QueryException $ex) {
            Log::info($ex->getMessage());
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function indexPerfiles()
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $varperfiles = $this->obtenerPerfiles();
            $varaccionesdePerfiles = $this->obtenerAccionesdePerfiles();
            $varlistausers = $this->obtenerusuarios();
            $varsucursales = $this->obtenersucursales();
            return view('sistemas.perfiles', compact('varpantallas', 'varsubmenus', 'varlistausers', 'varperfiles', 'varaccionesdePerfiles', 'varsucursales'));
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function indexAccionesPerfiles()
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            return view('sistemas.acciones_perfiles', compact('varpantallas', 'varsubmenus'));
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function guardar_acciones(Request $request)
    {
        try {
            $varacci = new Acciones();
            $varacci->nombre_accion = $request->get('nombre');
            $varacci->descripcion_accion = $request->get('descripcion');
            $varacci->idvista = $request->get('idvista');
            $varacci->created_by = auth()->user()->name;
            $varacci->save();

            if ($varacci->save()) {
                return back()->with("success_msg_large", "¡Se guardaron los cambios correctamente!");
            } else {
                return rback()->with("warning", "No se logro");
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function guardar_departamento(Request $request)
    {
        try {
            $departamento = new Departamentos();
            $departamento->nombre = $request->get('nombre');
            $departamento->descripcion = $request->get('descripcion');
            $departamento->orden = $request->get('orden');
            $departamento->id_empresa = $request->get('id_empresa');
            $departamento->icon = $request->get('icon');
            $departamento->save();

            if ($departamento->save()) {
                return back()->with("success_msg_large", "¡Se guardaron los cambios correctamente!");
            } else {
                return back()->with("warning", "No se logro");
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function guardar_vista(Request $request)
    {
        try {
            $vista = new Vistas();
            $vista->nombre = $request->get('nombre');
            $vista->descripcion = $request->get('descripcion');
            $vista->orden = $request->get('orden');
            $vista->iddepartamento = $request->get('iddepartamento');
            $vista->estado = 1;
            $vista->save();

            if ($vista->save()) {
                return back()->with("success_msg_large", "¡Se guardaron los cambios correctamente!");
            } else {
                return back()->with("warning", "No se logro");
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function eliminar_acciones(int $id)
    {
        try {
            $Borrartbl1 = DB::select('delete from tblacciones where id = ? ', [$id]);
            return back()->with("success_msg_large", "guardado correctamente");

        } catch (\Illuminate\Database\QueryException $ex) {
            // dd($ex->getMessage()); 
            return back()->with("warningBD", "no guardado correctamente");
            // Note any method of class PDOException can be called on $ex.
        }

    }

    public function guardar_permisos(Request $request)
    {
        try {
            $date = Carbon::now();
            $fecha = $date->format('Y-m-d');
            $id = $request->get('idusuario');

            if ($request->has('vistas')) {
                foreach ($request->get('vistas') as $idvista) {
                    $vardepa = $this->obtenerdepartamentoXvista($idvista);
                    foreach ($vardepa as $depa)
                        $usuario_pantalas = new usuario_pantallas();
                    $usuario_pantalas->idusuario = $id;
                    $usuario_pantalas->idvista = $idvista;
                    $usuario_pantalas->iddepartamento = $depa->iddepartamento;
                    $usuario_pantalas->estado = 'A';
                    $usuario_pantalas->created_at = $fecha;
                    $usuario_pantalas->save();
                }
            }

            if ($request->has('caja')) {
                foreach ($request->get('caja') as $peso) {
                    $varpermiso = new usuario_acciones();
                    $varpermiso->idacciones = $peso;
                    $varpermiso->idusuario = $id;
                    $varpermiso->created_at = $fecha;
                    $varpermiso->save();

                }
            }
            if ($varpermiso->save()) {
                return back()->with("success_msg_large", "¡Se guardaron los cambios correctamente!");
            } else {
                return rback()->with("warning", "No se logro");
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function nuevo_perfil(Request $request)
    {
        try {
            $perfil = new perfiles();
            $perfil->nombre = $request->get('nombre');
            $perfil->descripcion = $request->get('descripcion');
            $perfil->created_by = auth()->user()->name;
            $perfil->save();

            if ($perfil->save()) {
                return back()->with("success_msg_large", "¡Se guardaron los cambios correctamente!");
            } else {
                return rback()->with("warning", "No se logro");
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function getUsuarioAsignaciones(int $id)
    {
        try {
            $perfiles = $this->obtenerPerfilesUsuario($id)
                ->pluck('id_perfil')
                ->map(fn ($idPerfil) => (int) $idPerfil)
                ->values();

            $sucursales = collect($this->obtenerSucursalesxUser($id))
                ->pluck('idsucursal')
                ->map(fn ($idSucursal) => (int) $idSucursal)
                ->values();

            return response()->json([
                'perfiles' => $perfiles,
                'sucursales' => $sucursales,
            ]);
        } catch (\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'perfiles' => [],
                'sucursales' => [],
                'error' => 'No se pudieron cargar las asignaciones',
            ], 500);
        }
    }

    public function guardar_perfil(Request $request)
    {
        $transactionStarted = false;

        try {
            $perfiles = collect(Arr::wrap($request->get('perfil', [])))
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();
            $usuario = $request->get('idusuario');
            $guardado = false;
            $varsucursales = $this->obtenersucursales();

            DB::beginTransaction();
            $transactionStarted = true;

            // Quitar perfiles desmarcados
            $perfilesActuales = usuario_perfiles::where('id_usuario', $usuario)->get();
            foreach ($perfilesActuales as $perfilActual) {
                if (!$perfiles->contains((int) $perfilActual->id_perfil)) {
                    $perfilActual->delete();
                    $guardado = true;
                }
            }

            // Agregar perfiles nuevos
            foreach ($perfiles as $perfil) {
                $checarSiPerfil = usuario_perfiles::where('id_perfil', $perfil)
                    ->where('id_usuario', $usuario)
                    ->exists();

                if (!$checarSiPerfil) {
                    $usuario_perfiles = new usuario_perfiles();
                    $usuario_perfiles->id_perfil = $perfil;
                    $usuario_perfiles->id_usuario = $usuario;
                    $usuario_perfiles->created_by = auth()->user()->name;
                    $usuario_perfiles->save();
                    $guardado = true;
                }
            }

            // Sucursales marcadas en el formulario
            $sucursalesSeleccionadas = collect($varsucursales)
                ->filter(fn ($dato) => (int) $request->input((string) $dato->id) === 1)
                ->map(fn ($dato) => (int) $dato->id)
                ->values();

            // Quitar sucursales desmarcadas
            $sucursalesActuales = UserSucursal::where('idusuario', $usuario)->get();
            foreach ($sucursalesActuales as $sucursalActual) {
                if (!$sucursalesSeleccionadas->contains((int) $sucursalActual->idsucursal)) {
                    $sucursalActual->delete();
                    $guardado = true;
                }
            }

            // Agregar sucursales nuevas
            foreach ($sucursalesSeleccionadas as $idSucursal) {
                $yaAsignada = UserSucursal::where('idusuario', $usuario)
                    ->where('idsucursal', $idSucursal)
                    ->exists();

                if (!$yaAsignada) {
                    $UserSucursal = new UserSucursal();
                    $UserSucursal->idusuario = $usuario;
                    $UserSucursal->idsucursal = $idSucursal;
                    $UserSucursal->created_by = auth()->user()->name;
                    $UserSucursal->save();
                    $guardado = true;
                }
            }

            DB::commit();

            if ($guardado) {
                return back()->with("success_msg_large", "¡Se guardaron los cambios correctamente!");
            } else {
                return back()->with("info_msg_large", "No hubo cambios: las asignaciones ya coincidían con lo seleccionado");
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            if ($transactionStarted) {
                DB::rollBack();
            }

            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function eliminar_accion_perfil(int $id)
    {
        try {
            $Borrartbl1 = DB::select('delete from tblperfil_acciones where id = ? ', [$id]);
            return back()->with("success_msg_large", "guardado correctamente");

        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function guardar_perfil_accion(Request $request)
    {
        try {
            $idaccion = $request->get('accion');
            $idperfil = $request->get('perfil');
            $userP = 0;
            $checarSiPerfilAcc = $this->obteneraccionesxPerfiles($idaccion, $idperfil);

            if ($checarSiPerfilAcc->isEmpty()) {
                $perfil_acciones = new perfil_acciones();
                $perfil_acciones->idperfil = $idperfil;
                $perfil_acciones->idaccion = $idaccion;
                $perfil_acciones->created_by = auth()->user()->name;
                $perfil_acciones->save();
                $userP = 1;
            } else {
                error_log('Perfil ya esta creado');
            }



            if ($userP != 0) {
                return back()->with("success_msg_large", "¡Se guardaron los cambios correctamente!");
            } else {
                return back()->with("info_msg_large", "Este perfil ya tiene estas cualidades, no es posible repetir");
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function indexUserPermisos(Request $request, int $id)
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $varlistausers = $this->obtenerusuarios();
            $varacciones = $this->obteneracciones();
            $varlistadepas = $this->obtenerdepartamentos();
            $varlistuseracc = $this->obtenerAccionesUser($id);
            $varperfilesUser = $this->obtenerPerfilesUsuario($id);
            $varusuario = $varlistausers->firstWhere('id', $id);

            return view('sistemas.permisos_user', compact('varpantallas', 'varsubmenus', 'varlistausers', 'varlistadepas', 'varacciones', 'varlistuseracc', 'varperfilesUser', 'varusuario'));
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function getUsuariosPermisos()
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $varlistausers = $this->obtenerusuarios();
        $perfilesPorUsuario = $this->obtenerPerfilesUsuarios()->groupBy('id_usuario');

        return view('sistemas.usuarios_permisos', compact('varpantallas', 'varsubmenus', 'varlistausers', 'perfilesPorUsuario'));
    }

    public function eliminar_acciones_user(int $id)
    {
        try {
            $Borrartbl1 = DB::select('delete from tblusuario_acciones where id = ? ', [$id]);
            return back()->with("success_msg_large", "guardado correctamente");

        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }

    }

    public function eliminar_perfil_user(int $id)
    {
        try {
            DB::select('delete from tblusuario_perfiles where id = ? ', [$id]);
            return back()->with("success_msg_large", "guardado correctamente");
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    private function obtenerPerfilesUsuario(int $idusuario)
    {
        $perfiles = DB::select('SELECT
                tblusuario_perfiles.id,
                tblusuario_perfiles.id_usuario,
                tblusuario_perfiles.id_perfil,
                tblusuario_perfiles.created_at,
                tblusuario_perfiles.created_by,
                tblperfiles.nombre,
                tblperfiles.descripcion,
                COUNT(tblperfil_acciones.id) as total_acciones
            FROM tblusuario_perfiles
            INNER JOIN tblperfiles on tblusuario_perfiles.id_perfil = tblperfiles.id
            LEFT JOIN tblperfil_acciones on tblperfil_acciones.idperfil = tblperfiles.id
            WHERE tblusuario_perfiles.id_usuario = ?
            GROUP BY
                tblusuario_perfiles.id,
                tblusuario_perfiles.id_usuario,
                tblusuario_perfiles.id_perfil,
                tblusuario_perfiles.created_at,
                tblusuario_perfiles.created_by,
                tblperfiles.nombre,
                tblperfiles.descripcion
            ORDER BY tblperfiles.nombre asc;', [$idusuario]);

        return collect($perfiles);
    }

    private function obtenerPerfilesUsuarios()
    {
        $perfiles = DB::select('SELECT
                tblusuario_perfiles.id,
                tblusuario_perfiles.id_usuario,
                tblusuario_perfiles.id_perfil,
                tblperfiles.nombre,
                tblperfiles.descripcion
            FROM tblusuario_perfiles
            INNER JOIN tblperfiles on tblusuario_perfiles.id_perfil = tblperfiles.id
            ORDER BY tblperfiles.nombre asc;');

        return collect($perfiles);
    }

    public function guardar_config(Request $request)
    {
        try {
           
            $urlapi = $request->post("url_api");
            $webadmin = $request->post("web_admin");
            $apisecret = $request->post("api_secret");
            $apikey = $request->post("api_key");
            $fecha = $request->post("fecha");
            $idemisor = $this->ObtenerIdEmisor();
            $datosfacturify = $this->ObtenerDatosFacturify();
            $uuid="";
    
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $varlistavistas = $this->obtenervistas();
    
            //insertar los datos en la tabla facturify
            if($idemisor->isEmpty())
            {
                $tblfacturify = new facturify();
                $tblfacturify->url_api = $urlapi;
                $tblfacturify->web_admin = $webadmin;
                $tblfacturify->api_secret = $apisecret;
                $tblfacturify->api_key = $apikey;
                $tblfacturify->fecha_registro = $fecha;
                $tblfacturify->id_usuario = auth()->user()->id;
                $tblfacturify->save();
                
                if($tblfacturify->save())
                {
                return view('sistemas.facturaconfig', compact('varpantallas', 'varsubmenus', 'varlistavistas','datosfacturify'));
                }
            }
            else
            {

                /*rectornamos la ruta normal con la infromacion de facturify*/
                return view('sistemas.facturaconfig', compact('varpantallas', 'varsubmenus', 'varlistavistas','datosfacturify'));
            }
           
            
        } catch (\Illuminate\Database\QueryException $ex) 
        {
            Log::info($ex->getMessage());
            return back()->with("warningBD", "no guardado correctamente");
        }
    }


    public function facturacion()
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        return view('sistemas.facturacion', compact('varpantallas', 'varsubmenus'));
    }
}
