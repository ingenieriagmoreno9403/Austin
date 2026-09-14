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
use App\Traits\EmpresaCatalogoTrait;
use Carbon\Carbon;
use App\Models\Empleados;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class RegisterController extends Controller
{
    use MenuTrait;
    use RegistersUsers;
    use DatosimpleTraits;
    use SistemasTraits;
    use EmpresaCatalogoTrait;

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
        $varlistaempresas = $this->obtenerEmpresasParaRegistro();
        $esMasterEmpresa = $this->esSesionMasterEmpresa();

        return view('sistemas.registro',compact('varpantallas','varsubmenus',
        'varlistaempleados','valusers','optenerproveedoresuser', 'varlistaalumnos', 'varlistasocios', 'varlistaempresas', 'esMasterEmpresa'));
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
            $idEmpresaSistema = null;
            if (in_array($tipo, ['empresa', 'master'], true)) {
                $parsed = $this->parseIdEmpresaRegistro($request->get('id_tipo_empresa'));
                $idEmpresaSistema = $parsed['id_empresa'];
                if ($tipo === 'empresa') {
                    $idTipo = $parsed['id_tipo'];
                }
            }

            if ($tipo !== 'master' && is_null($idTipo)) {
                return redirect()->route('registro')->with("warning", "Selecciona el registro correspondiente al tipo de usuario");
            }

            if ($tipo !== 'master') {
                $yaAsignado = $tipo === 'empresa' && $idEmpresaSistema && Schema::hasColumn('users', 'id_empresa')
                    ? User::where('tipo', 'empresa')->where('id_empresa', $idEmpresaSistema)->exists()
                    : User::where('tipo', $tipo)->where('id_tipo', $idTipo)->exists();

                if ($yaAsignado) {
                    return redirect()->route('registro')->with("warning", "Este registro ya tiene un usuario asignado");
                }
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
            if (in_array($tipo, ['empresa', 'master'], true) && $idEmpresaSistema && Schema::hasColumn('users', 'id_empresa')) {
                $user->id_empresa = $idEmpresaSistema;
            }

            if($user->save()){
                if ($tipo === 'proveedor') {
                    DB::select('update tblprovedores set id_usuario = ? where id = ?;', [$user->id, $idTipo]);
                }

                if (in_array($tipo, ['empresa', 'master'], true) && $idEmpresaSistema) {
                    $perfiles = $this->perfilesPermitidosEmpresa((int) $idEmpresaSistema) ?? [];
                    $this->asignarPerfilesAUsuario((int) $user->id, $perfiles);
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

    public function asignarEmpresa(Request $request)
    {
        if ($this->esSesionMasterEmpresa()) {
            return redirect()->route('registro')->with('warning', 'No puedes cambiar la empresa de un usuario.');
        }

        if (!Schema::hasColumn('users', 'id_empresa')) {
            return redirect()->route('registro')->with('warning', 'Falta el campo id_empresa en users.');
        }

        $iduser = $request->get('iduser');
        if (is_null($iduser)) {
            return redirect()->route('registro')->with('warning', 'Selecciona el usuario.');
        }

        $user = User::find($iduser);
        if (!$user) {
            return redirect()->route('registro')->with('warning', 'No se encontró el usuario seleccionado.');
        }

        $parsed = $this->parseIdEmpresaRegistro($request->get('id_tipo_empresa'));
        $idEmpresa = $parsed['id_empresa'];

        try {
            if ($idEmpresa) {
                $perfiles = $this->perfilesPermitidosEmpresa((int) $idEmpresa) ?? [];
                $data = $this->promoverSuperusuarioEmpresa((int) $user->id, (int) $idEmpresa, $perfiles);
                $mensaje = $data['name'] . ' quedó como superusuario de la empresa.';
                if (($data['perfiles_asignados'] ?? 0) > 0) {
                    $mensaje .= ' Se le asignaron los módulos vendidos.';
                }
                return redirect()->route('registro')->with('success_msg_large', $mensaje);
            }

            $this->quitarRolSuperusuarioEmpresa((int) $user->id);
            return redirect()->route('registro')->with('success_msg_large', 'Se quitó el rol de superusuario de empresa a ' . $user->name);
        } catch (\InvalidArgumentException $ex) {
            return redirect()->route('registro')->with('warning', $ex->getMessage());
        } catch (\RuntimeException $ex) {
            return redirect()->route('registro')->with('warning', $ex->getMessage());
        }
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

    private function parseIdEmpresaRegistro($valor): array
    {
        $raw = trim((string) $valor);
        if ($raw === '') {
            return ['id_tipo' => null, 'id_empresa' => null];
        }

        if (str_starts_with($raw, 'sis-')) {
            $id = (int) substr($raw, 4);
            return ['id_tipo' => $id > 0 ? $id : null, 'id_empresa' => $id > 0 ? $id : null];
        }

        if (str_starts_with($raw, 'ga-')) {
            $id = (int) substr($raw, 3);
            return ['id_tipo' => $id > 0 ? $id : null, 'id_empresa' => null];
        }

        $id = (int) $raw;
        if ($id <= 0) {
            return ['id_tipo' => null, 'id_empresa' => null];
        }

        $esSistema = Schema::hasTable('tblempresas')
            && DB::table('tblempresas')->where('id', $id)->exists();

        return [
            'id_tipo' => $id,
            'id_empresa' => $esSistema ? $id : null,
        ];
    }

    private function obtenerEmpresasParaRegistro()
    {
        $lista = collect();

        if (Schema::hasTable('tblempresas')) {
            $sql = "SELECT tblempresas.id,
                    tblempresas.nombre_empresa as nombre,
                    tblempresas.descripcion,
                    NULL as municipio,
                    'sis' as origen
                FROM tblempresas";
            if (Schema::hasColumn('tblempresas', 'estado')) {
                $sql .= " WHERE tblempresas.estado = 'A' OR tblempresas.estado IS NULL";
            }
            $sql .= " ORDER BY tblempresas.nombre_empresa asc";
            $lista = $lista->concat(DB::select($sql));
        }

        if (Schema::hasTable('tblga_empresas')) {
            $ga = DB::select("SELECT tblga_empresas.id,
                    tblga_empresas.nombre,
                    tblga_empresas.estado,
                    tblga_empresas.municipio,
                    'ga' as origen
                FROM tblga_empresas
                LEFT JOIN users on users.id_tipo = tblga_empresas.id and users.tipo = 'empresa'
                    and (users.id_empresa IS NULL OR users.id_empresa = 0)
                WHERE users.id IS NULL
                ORDER BY tblga_empresas.nombre asc;");
            $lista = $lista->concat($ga);
        }

        return $lista;
    }

}
