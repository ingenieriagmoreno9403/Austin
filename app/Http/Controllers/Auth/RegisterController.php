<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Vistas;
use App\Models\usuario_pantallas;
use App\Traits\MenuTrait;
use App\Traits\SistemasTraits;
use App\Traits\DatosimpleTraits;
use Carbon\Carbon;
use App\Models\Empleados;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Arr;

class RegisterController extends Controller
{
    use MenuTrait;
    use RegistersUsers;
    use DatosimpleTraits;
    use SistemasTraits;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    // protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('auth');
    }


    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function Index(){
        $varpantallas =  $this->Traermenuenc();
        $varsubmenus =   $this->Traermenudet();
        $varlistaempleados =  $this-> obtenerlistaempleados();
        $valusers =  $this-> obtenerusuarios();
        $date = Carbon::now();
        $date = $date->format('Y-m-d');
        $optenerproveedoresuser =  $this->optenerproveedoresuser();
        $varlistaalumnos = $this->obtenerAlumnosSinUsuario();
        $varlistasocios = $this->obtenerSociosSinUsuario();
        $varlistaempresas = $this->obtenerEmpresasSinUsuario();

        return view('sistemas.registro',compact('varpantallas','varsubmenus',
        'varlistaempleados','valusers','optenerproveedoresuser', 'varlistaalumnos', 'varlistasocios', 'varlistaempresas'));
    }

    // public function __construct()
    // {
    //     $this->middleware('auth');
    // }

    // esta funcion corrobora que no estes logeado ya
    // public function __construct()
    // {
    //     $this->middleware('guest');
    // }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    // protected function validator(array $data)
    // {
    //     return Validator::make($data, [
    //         'name' => ['required', 'string', 'max:255'],
    //         'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
    //         'password' => ['required', 'string', 'min:8', 'confirmed'],
    //     ]);
    // }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    public function create(Request $request)
    {

        $date = Carbon::now();
        $fecha = $date->format('Y-m-d');

        if ($request->get('contrasena') !== $request->get('recontrasena')) {
            return redirect()->route('registro')->with('warning_msg', 'Las contraseñas no coinciden. Verifica e inténtalo de nuevo.');
        }

        if(!is_null($request->get('tipo')) && !is_null($request->get('contrasena')) && !is_null($request->get('email')) && !is_null($request->get('name')))
        {
            $pass = bcrypt($request->get('contrasena'));
            $valusers =  $this-> obtenerusuarios();
            $varlistaempleados =  $this-> obtenerlistaempleados();
            $tipo = $request->get('tipo');
            $tiposPermitidos = ['proveedor', 'empleado', 'alumno', 'socio', 'empresa', 'master'];

            if (!in_array($tipo, $tiposPermitidos, true)) {
                return redirect()->route('registro')->with("warning", "Tipo de usuario no válido");
            }

            $idTipo = $this->obtenerIdTipoRegistro($request, $tipo);
            if ($tipo !== 'master' && is_null($idTipo)) {
                return redirect()->route('registro')->with("warning", "Selecciona el registro correspondiente al tipo de usuario");
            }

            if ($tipo !== 'master' && User::where('tipo', $tipo)->where('id_tipo', $idTipo)->exists()) {
                return redirect()->route('registro')->with("warning", "Este registro ya tiene un usuario asignado");
            }

            $idempleado = null;
            $nombre = "0.png";

            if ($tipo === 'empleado') {
                $idempleado = $idTipo;

                foreach($varlistaempleados as $emple){
                    if($emple->idempleado == $idempleado){
                        $nombre = $emple->nombre_foto ?: "0.png";
                        break;
                    }
                }

                foreach($valusers as $userExistente){
                    if($userExistente->idempleado == $idempleado){
                        return redirect()->route('registro')->with("warning","No se logro");
                    }
                }
            }

            $user = new User();
            $user->name = $request->get('name');
            $user->email = $request->get('email');
            $user->password = $pass;
            $user->id_tipo = $idTipo;
            $user->tipo = $tipo;
            $user->idempleado = $idempleado;
            $user->nombre_foto = $nombre;
            $user->estado_user = "A";
            $user->created_at = $fecha;
            $user->created_by = auth()->user()->name;

            if($user->save()){
                if ($tipo === 'proveedor') {
                    DB::select('update tblprovedores set id_usuario = ? where id = ?;', [$user->id, $idTipo]);
                }

                return redirect()->route('registro')->with("success_msg_large","¡Se guardaron los cambios correctamente!");
            }else{
                return redirect()->route('registro')->with("error_msg_large","No se logro, existe un conflicto con los datos ingresados, verifica e intenta de nuevo");
            }
              
           
        }else{
            return back()->with("error_msg_large","No se logro, existe un conflicto con los datos ingresados, verifica e intenta de nuevo");
        }
    }


    public function updatepass(Request $request)
    {

        if ($request->get('contrasena') !== $request->get('recontrasena')) {
            return redirect()->route('registro')->with('warning_msg', 'Las contraseñas no coinciden. Verifica e inténtalo de nuevo.');
        }

        $date = Carbon::now();
        $fecha = $date->format('Y-m-d');
        $pass = bcrypt($request->get('contrasena'));
        $iduser = $request->get('iduser');

        
            $user =  User::find($iduser);
            $user->password = $pass;
            $user->updated_at=$fecha;
            $user->updated_by = auth()->user()->name;

            if($user->save()){

                return redirect()->route('registro')->with("success_msg_large","¡Se guardaron los cambios correctamente!");
            }else{
                return redirect()->route('registro')->with("error_msg_large","No se logro agregar la contraseña");
            }
    }

    public function inactivar(Request $request)
    {
        $iduser = $request->get('iduser');

        if (is_null($iduser)) {
            return redirect()->route('registro')->with("warning", "Selecciona un usuario para inactivar");
        }

        $user = User::find($iduser);

        if (!$user) {
            return redirect()->route('registro')->with("warning", "No se encontró el usuario seleccionado");
        }

        $user->estado_user = "I";
        $user->updated_at = Carbon::now()->format('Y-m-d');
        $user->updated_by = auth()->user()->name;

        if ($user->save()) {
            return redirect()->route('registro')->with("success_msg_large", "Usuario inactivado correctamente");
        }

        return redirect()->route('registro')->with("warning", "No se logro");
    }

    public function activar(Request $request)
    {
        $iduser = $request->get('iduser');

        if (is_null($iduser)) {
            return redirect()->route('registro')->with("error_msg_large", "Selecciona un usuario para activar");
        }

        $user = User::find($iduser);

        if (!$user) {
            return redirect()->route('registro')->with("error_msg_large", "No se encontró el usuario seleccionado");
        }

        $user->estado_user = "A";
        $user->updated_at = Carbon::now()->format('Y-m-d');
        $user->updated_by = auth()->user()->name;

        if ($user->save()) {
            return redirect()->route('registro')->with("success_msg_large", "Usuario activado correctamente");
        }

        return redirect()->route('registro')->with("error_msg_large", "No se logro reactivar el usuario");
    }

    private function obtenerIdTipoRegistro(Request $request, string $tipo)
    {
        return Arr::get([
            'empleado' => $request->get('id_tipo_empleado'),
            'proveedor' => $request->get('id_tipo_proveedor'),
            'alumno' => $request->get('id_tipo_alumno'),
            'socio' => $request->get('id_tipo_socio'),
            'empresa' => $request->get('id_tipo_empresa'),
            'master' => null,
        ], $tipo);
    }

    private function obtenerAlumnosSinUsuario()
    {
        return collect(DB::select("SELECT tblalumnos.id,
                tblalumnos.nombres,
                tblalumnos.apellido_paterno,
                tblalumnos.apellido_materno
            FROM tblalumnos
            LEFT JOIN users on users.id_tipo = tblalumnos.id and users.tipo = 'alumno'
            WHERE users.id IS NULL
            ORDER BY tblalumnos.nombres asc;"));
    }

    private function obtenerSociosSinUsuario()
    {
        return collect(DB::select("SELECT tblsocios.id,
                tblsocios.numero_socio,
                tblsocios.nombre,
                tblsocios.ap_paterno,
                tblsocios.ap_materno
            FROM tblsocios
            LEFT JOIN users on users.id_tipo = tblsocios.id and users.tipo = 'socio'
            WHERE users.id IS NULL
            ORDER BY tblsocios.nombre asc;"));
    }

    private function obtenerEmpresasSinUsuario()
    {
        return collect(DB::select("SELECT tblga_empresas.id,
                tblga_empresas.nombre,
                tblga_empresas.estado,
                tblga_empresas.municipio
            FROM tblga_empresas
            LEFT JOIN users on users.id_tipo = tblga_empresas.id and users.tipo = 'empresa'
            WHERE users.id IS NULL
            ORDER BY tblga_empresas.nombre asc;"));
    }

}
