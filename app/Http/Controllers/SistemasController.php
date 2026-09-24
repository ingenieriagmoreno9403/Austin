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
use App\Models\CcCiclo;
use App\Models\PvCiclo;
use App\Traits\EmpresaCatalogoTrait;
use Illuminate\Support\Facades\Schema;

class SistemasController extends Controller
{
    use MenuTrait;
    use DatosimpleTraits;
    use SistemasTraits;
    use EmpresaCatalogoTrait;

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
            $esMasterEmpresa = $this->esSesionMasterEmpresa();
            return view('sistemas.index', compact('varpantallas', 'varsubmenus', 'varlistausers', 'permisos1', 'permisos2', 'permisos3', 'permisos4', 'permisos5', 'esMasterEmpresa'));
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
            $varlistausers = $this->obtenerusuariosAlcance();
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
            $varlistausers = $this->obtenerusuariosAlcance();

            $idEmpresaSesion = $this->empresaIdSesion();
            $perfilesPermitidos = $this->esSesionMasterEmpresa()
                ? $this->perfilesPermitidosEmpresa($idEmpresaSesion)
                : null;
            if (is_array($perfilesPermitidos)) {
                $varperfiles = collect($varperfiles)->filter(fn ($perfil) => in_array((int) $perfil->id, $perfilesPermitidos, true))->values();
                $varaccionesdePerfiles = collect($varaccionesdePerfiles)->filter(fn ($accion) => in_array((int) $accion->id, $perfilesPermitidos, true))->values();
            }

            return view('sistemas.perfiles', compact('varpantallas', 'varsubmenus', 'varlistausers', 'varperfiles', 'varaccionesdePerfiles'));
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
            $idEmpresa = $this->empresaIdDeUsuario((int) $id);
            $vistasPermitidas = $this->vistasPermitidasEmpresa($idEmpresa);

            if ($request->has('vistas')) {
                $vistas = collect($request->get('vistas'))
                    ->map(fn ($idvista) => (int) $idvista)
                    ->filter()
                    ->unique()
                    ->values();

                if (is_array($vistasPermitidas)) {
                    $vistas = $vistas->filter(fn ($idvista) => in_array($idvista, $vistasPermitidas, true))->values();
                }

                foreach ($vistas as $idvista) {
                    $vardepa = $this->obtenerdepartamentoXvista($idvista);
                    foreach ($vardepa as $depa) {
                        $usuario_pantalas = new usuario_pantallas();
                        $usuario_pantalas->idusuario = $id;
                        $usuario_pantalas->idvista = $idvista;
                        $usuario_pantalas->iddepartamento = $depa->iddepartamento;
                        $usuario_pantalas->estado = 'A';
                        $usuario_pantalas->created_at = $fecha;
                        $usuario_pantalas->save();
                    }
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

            $idEmpresa = $this->empresaIdDeUsuario($id);
            $catalogoActivo = $this->catalogoAccesosActivo($idEmpresa);
            $perfilesPermitidos = $this->perfilesPermitidosEmpresa($idEmpresa);
            $vistasPermitidas = $this->vistasPermitidasEmpresa($idEmpresa);

            $empresaNombre = null;
            if ($idEmpresa) {
                $empresaNombre = DB::table('tblempresas')->where('id', $idEmpresa)->value('nombre_empresa');
            }

            return response()->json([
                'perfiles' => $perfiles,
                'sucursales' => $sucursales,
                'id_empresa' => $idEmpresa,
                'empresa' => $empresaNombre,
                'catalogo_activo' => $catalogoActivo,
                'perfiles_permitidos' => $perfilesPermitidos,
                'vistas_permitidas' => $vistasPermitidas,
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
            if ($this->esSesionMasterEmpresa()) {
                $permitido = $this->obtenerusuariosAlcance()->firstWhere('id', (int) $usuario);
                if (!$permitido) {
                    return back()->with('warning', 'No puedes asignar perfiles a usuarios de otra empresa.');
                }
            }
            $guardado = false;

            $idEmpresa = $this->empresaIdDeUsuario((int) $usuario);
            $perfilesPermitidos = $this->perfilesPermitidosEmpresa($idEmpresa);
            if (is_array($perfilesPermitidos)) {
                $perfilesActualesIds = usuario_perfiles::where('id_usuario', $usuario)
                    ->pluck('id_perfil')
                    ->map(fn ($id) => (int) $id)
                    ->all();
                $permitidos = array_values(array_unique(array_merge($perfilesPermitidos, $perfilesActualesIds)));
                $perfiles = $perfiles->filter(fn ($id) => in_array((int) $id, $permitidos, true))->values();
            }

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
            if ($this->esSesionMasterEmpresa()) {
                $row = DB::selectOne(
                    'SELECT tblperfil_acciones.idperfil, tblacciones.idvista
                    FROM tblperfil_acciones
                    INNER JOIN tblacciones ON tblacciones.id = tblperfil_acciones.idaccion
                    WHERE tblperfil_acciones.id = ?
                    LIMIT 1',
                    [$id]
                );
                $perfilesPermitidos = $this->perfilesPermitidosEmpresa($this->empresaIdSesion()) ?? [];
                $vistasPermitidas = $this->vistasPermitidasEmpresa($this->empresaIdSesion()) ?? [];
                if (
                    !$row
                    || !in_array((int) $row->idperfil, $perfilesPermitidos, true)
                    || !in_array((int) $row->idvista, $vistasPermitidas, true)
                ) {
                    return back()->with('warning', 'No puedes quitar acciones de módulos que no compró tu empresa.');
                }
            }

            DB::select('delete from tblperfil_acciones where id = ? ', [$id]);
            return back()->with("success_msg_large", "guardado correctamente");

        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function guardar_perfil_accion(Request $request)
    {
        try {
            $idaccion = (int) $request->get('accion');
            $idperfil = (int) $request->get('perfil');

            if ($this->esSesionMasterEmpresa()) {
                $perfilesPermitidos = $this->perfilesPermitidosEmpresa($this->empresaIdSesion()) ?? [];
                $vistasPermitidas = $this->vistasPermitidasEmpresa($this->empresaIdSesion()) ?? [];
                if (!in_array($idperfil, $perfilesPermitidos, true)) {
                    return back()->with('warning', 'Ese perfil no está habilitado para tu empresa.');
                }
                $idVista = (int) (DB::table('tblacciones')->where('id', $idaccion)->value('idvista') ?? 0);
                if (!$idVista || !in_array($idVista, $vistasPermitidas, true)) {
                    return back()->with('warning', 'Esa acción no pertenece a los módulos comprados.');
                }
            }

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
            $varlistausers = $this->obtenerusuariosAlcance();
            $varacciones = $this->obteneracciones();
            $varlistadepas = $this->obtenerdepartamentos();
            $varusuario = $varlistausers->firstWhere('id', $id);
            if ($this->esSesionMasterEmpresa() && !$varusuario) {
                return back()->with('warning', 'No puedes editar usuarios de otra empresa.');
            }
            $varlistuseracc = $this->obtenerAccionesUser($id);
            $varperfilesUser = $this->obtenerPerfilesUsuario($id);
            $accionesAgrupadas = $this->accionesDisponiblesParaUsuario($id, $varlistuseracc);
            $permisosModulo = $this->permisosModuloParaUsuario($id);

            return view('sistemas.permisos_user', compact(
                'varpantallas',
                'varsubmenus',
                'varlistausers',
                'varlistadepas',
                'varacciones',
                'accionesAgrupadas',
                'permisosModulo',
                'varlistuseracc',
                'varperfilesUser',
                'varusuario'
            ));
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function getUsuariosPermisos()
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $varlistausers = $this->obtenerusuariosAlcance();
        $perfilesPorUsuario = $this->obtenerPerfilesUsuarios()->groupBy('id_usuario');

        return view('sistemas.usuarios_permisos', compact('varpantallas', 'varsubmenus', 'varlistausers', 'perfilesPorUsuario'));
    }

    public function guardarAccionesUser(Request $request, int $id)
    {
        $varlistausers = $this->obtenerusuariosAlcance();
        $varusuario = $varlistausers->firstWhere('id', $id);
        if (!$varusuario) {
            return back()->with('warning', 'No puedes editar este usuario.');
        }

        $acciones = collect($request->get('acciones', []))
            ->map(fn ($idAccion) => (int) $idAccion)
            ->filter()
            ->unique()
            ->values();

        if ($acciones->isEmpty()) {
            return back()->with('warning', 'Selecciona al menos una acción puntual.');
        }

        if ($this->esSesionMasterEmpresa()) {
            $vistasPermitidas = $this->vistasPermitidasEmpresa($this->empresaIdSesion()) ?? [];
            $idsPermitidos = DB::table('tblacciones')
                ->whereIn('idvista', $vistasPermitidas ?: [0])
                ->pluck('id')
                ->map(fn ($idAccion) => (int) $idAccion)
                ->all();
            $acciones = $acciones->filter(fn ($idAccion) => in_array($idAccion, $idsPermitidos, true))->values();
            if ($acciones->isEmpty()) {
                return back()->with('warning', 'Esas acciones no pertenecen a los módulos comprados.');
            }
        }

        $agregadas = 0;
        foreach ($acciones as $idAccion) {
            $existe = usuario_acciones::where('idusuario', $id)
                ->where('idacciones', $idAccion)
                ->exists();
            if ($existe) {
                continue;
            }

            $row = new usuario_acciones();
            $row->idusuario = $id;
            $row->idacciones = $idAccion;
            $row->created_by = auth()->user()->name;
            $row->save();
            $agregadas++;
        }

        if ($agregadas === 0) {
            return back()->with('info_msg_large', 'Esas acciones ya estaban asignadas al usuario.');
        }

        return back()->with('success_msg_large', 'Se agregaron ' . $agregadas . ' acciones puntuales, sin cambiar el perfil.');
    }

    public function guardarPermisosModuloUser(Request $request, int $id)
    {
        $varusuario = $this->obtenerusuariosAlcance()->firstWhere('id', $id);
        if (!$varusuario) {
            return back()->with('warning', 'No puedes editar este usuario.');
        }

        $modulos = $this->permisosModuloParaUsuario($id);
        if ($modulos->isEmpty()) {
            return back()->with('warning', 'Este usuario no tiene módulos con importe masivo.');
        }

        foreach ($modulos as $modulo) {
            $wanted = collect($request->get($modulo['campo'], []))
                ->map(fn ($codigo) => strtoupper(trim((string) $codigo)))
                ->filter()
                ->unique()
                ->all();
            $this->sincronizarImportarMasivoUsuario($id, $modulo['clave'], $wanted);
        }

        return back()->with('success_msg_large', 'Se actualizó el importe masivo de Excel, sin cambiar el perfil.');
    }

    public function eliminar_acciones_user(int $id)
    {
        try {
            $row = DB::selectOne(
                'SELECT id, idusuario FROM tblusuario_acciones WHERE id = ? LIMIT 1',
                [$id]
            );
            if (!$row) {
                return back()->with('warning', 'No se encontró la acción.');
            }

            if ($this->esSesionMasterEmpresa()) {
                $permitido = $this->obtenerusuariosAlcance()->firstWhere('id', (int) $row->idusuario);
                if (!$permitido) {
                    return back()->with('warning', 'No puedes quitar acciones de usuarios de otra empresa.');
                }
            }

            DB::select('delete from tblusuario_acciones where id = ? ', [$id]);
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

    private function accionesDisponiblesParaUsuario(int $idUsuario, $accionesAsignadas)
    {
        $idsAsignadas = collect($accionesAsignadas)
            ->pluck('id_accion')
            ->map(fn ($idAccion) => (int) $idAccion)
            ->filter()
            ->all();

        $query = Acciones::join('tblvistas', 'tblacciones.idvista', '=', 'tblvistas.id')
            ->join('tbldepartamentos', 'tblvistas.iddepartamento', '=', 'tbldepartamentos.id')
            ->select(
                'tblacciones.id',
                'tblacciones.nombre_accion',
                'tblacciones.descripcion_accion',
                'tblvistas.nombre as vista',
                'tbldepartamentos.nombre as departamento'
            )
            ->when($idsAsignadas, fn ($q) => $q->whereNotIn('tblacciones.id', $idsAsignadas));

        if ($this->esSesionMasterEmpresa()) {
            $vistasPermitidas = $this->vistasPermitidasEmpresa($this->empresaIdSesion()) ?? [];
            $query->whereIn('tblvistas.id', $vistasPermitidas ?: [0]);
        }

        return $query
            ->orderBy('tbldepartamentos.nombre')
            ->orderBy('tblvistas.nombre')
            ->orderBy('tblacciones.descripcion_accion')
            ->get()
            ->groupBy(['departamento', 'vista']);
    }

    private function permisosModuloParaUsuario(int $idUsuario)
    {
        $modulos = collect();
        if ($this->empresaSesionTieneModulo('cc') && Schema::hasTable('tbl_cc_ciclos') && Schema::hasTable('tbl_cc_usuario_permisos')) {
            $tipoId = (int) (DB::table('tbl_cc_tipos_permiso')->where('clave', 'importar')->value('id') ?? 0);
            $activos = $tipoId
                ? DB::table('tbl_cc_usuario_permisos')
                    ->where('user_id', $idUsuario)
                    ->where('permiso_id', $tipoId)
                    ->pluck('ciclo_codigo')
                    ->map(fn ($c) => strtoupper((string) $c))
                    ->all()
                : [];
            $ciclos = CcCiclo::query()->orderByDesc('anio_presupuesto')->orderByDesc('id')->get()
                ->map(function ($ciclo) use ($activos) {
                    return [
                        'codigo' => $ciclo->codigo,
                        'nombre' => $ciclo->nombre ?: $ciclo->codigo,
                        'estado' => $ciclo->estado,
                        'activo' => in_array(strtoupper((string) $ciclo->codigo), $activos, true),
                    ];
                })->values();
            if ($ciclos->isNotEmpty()) {
                $modulos->push([
                    'clave' => 'cc',
                    'campo' => 'cc_importar',
                    'titulo' => 'Gestor Presupuestos',
                    'ciclos' => $ciclos,
                ]);
            }
        }

        if ($this->empresaSesionTieneModulo('pv') && Schema::hasTable('tbl_pv_ciclos') && Schema::hasTable('tbl_pv_usuario_permisos')) {
            $tipoId = (int) (DB::table('tbl_pv_tipos_permiso')->where('clave', 'importar')->value('id') ?? 0);
            $activos = $tipoId
                ? DB::table('tbl_pv_usuario_permisos')
                    ->where('user_id', $idUsuario)
                    ->where('permiso_id', $tipoId)
                    ->pluck('ciclo_codigo')
                    ->map(fn ($c) => strtoupper((string) $c))
                    ->all()
                : [];
            $ciclos = PvCiclo::query()->orderByDesc('anio_presupuesto')->orderByDesc('id')->get()
                ->map(function ($ciclo) use ($activos) {
                    return [
                        'codigo' => $ciclo->codigo,
                        'nombre' => $ciclo->nombre ?: $ciclo->codigo,
                        'estado' => $ciclo->estado,
                        'activo' => in_array(strtoupper((string) $ciclo->codigo), $activos, true),
                    ];
                })->values();
            if ($ciclos->isNotEmpty()) {
                $modulos->push([
                    'clave' => 'pv',
                    'campo' => 'pv_importar',
                    'titulo' => 'Proyecciones de Ventas',
                    'ciclos' => $ciclos,
                ]);
            }
        }

        return $modulos;
    }

    private function empresaSesionTieneModulo(string $grupo): bool
    {
        $rutas = [
            'cc' => ['admincentros', 'controlcentros', 'analisisprogreso'],
            'pv' => ['ventas/asignaciones', 'ventas/captura', 'ventas/analisis', 'ventas/costos'],
        ];
        $buscar = $rutas[$grupo] ?? [];
        if (!$buscar) {
            return false;
        }

        if (!$this->esSesionMasterEmpresa()) {
            return true;
        }

        $ids = $this->vistasPermitidasEmpresa($this->empresaIdSesion());
        if (!is_array($ids)) {
            return true;
        }

        $filas = DB::table('tblvistas')
            ->join('tbldepartamentos', 'tbldepartamentos.id', '=', 'tblvistas.iddepartamento')
            ->whereIn('tblvistas.id', $ids ?: [0])
            ->get(['tblvistas.descripcion', 'tblvistas.nombre', 'tbldepartamentos.nombre as departamento']);

        foreach ($filas as $fila) {
            $ruta = strtolower(trim((string) $fila->descripcion));
            $nombre = strtolower(trim((string) $fila->nombre));
            $depto = strtolower(trim((string) $fila->departamento));
            if (in_array($ruta, $buscar, true)) {
                return true;
            }
            if ($grupo === 'cc' && (str_contains($depto, 'centro') || str_contains($nombre, 'presupuesto') || str_contains($nombre, 'gestor'))) {
                return true;
            }
            if ($grupo === 'pv' && (str_contains($depto, 'venta') || str_contains($nombre, 'proyeccion') || str_contains($nombre, 'proyección'))) {
                return true;
            }
        }

        return false;
    }

    private function sincronizarImportarMasivoUsuario(int $idUsuario, string $modulo, array $ciclosWanted): void
    {
        $esCc = $modulo === 'cc';
        $tablaTipos = $esCc ? 'tbl_cc_tipos_permiso' : 'tbl_pv_tipos_permiso';
        $tablaPermisos = $esCc ? 'tbl_cc_usuario_permisos' : 'tbl_pv_usuario_permisos';
        $tablaCiclos = $esCc ? 'tbl_cc_ciclos' : 'tbl_pv_ciclos';
        if (!Schema::hasTable($tablaTipos) || !Schema::hasTable($tablaPermisos) || !Schema::hasTable($tablaCiclos)) {
            return;
        }

        $tipoId = (int) (DB::table($tablaTipos)->where('clave', 'importar')->value('id') ?? 0);
        if ($tipoId <= 0) {
            return;
        }

        $codigos = DB::table($tablaCiclos)->pluck('codigo')->map(fn ($c) => strtoupper((string) $c))->all();
        $wanted = array_values(array_intersect($ciclosWanted, $codigos));
        $actuales = DB::table($tablaPermisos)
            ->where('user_id', $idUsuario)
            ->where('permiso_id', $tipoId)
            ->pluck('ciclo_codigo')
            ->map(fn ($c) => strtoupper((string) $c))
            ->all();

        foreach ($wanted as $ciclo) {
            if (in_array($ciclo, $actuales, true)) {
                continue;
            }
            DB::table($tablaPermisos)->insert([
                'ciclo_codigo' => $ciclo,
                'user_id' => $idUsuario,
                'permiso_id' => $tipoId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($actuales as $ciclo) {
            if (in_array($ciclo, $wanted, true)) {
                continue;
            }
            DB::table($tablaPermisos)
                ->where('user_id', $idUsuario)
                ->where('permiso_id', $tipoId)
                ->whereRaw('UPPER(ciclo_codigo) = ?', [$ciclo])
                ->delete();
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

    private function obtenerusuariosAlcance()
    {
        $usuarios = $this->obtenerusuarios();
        if (!$this->esSesionMasterEmpresa()) {
            return $usuarios;
        }

        return $this->filtrarUsuariosDeEmpresa($usuarios, $this->empresaIdSesion());
    }

    private function obtenersucursalesAlcance()
    {
        $sucursales = $this->obtenersucursales();
        if (!$this->esSesionMasterEmpresa()) {
            return $sucursales;
        }

        $idEmpresa = $this->empresaIdSesion();
        $ids = collect(DB::table('tblsucursales')->where('idempresa', $idEmpresa)->pluck('id'))
            ->map(fn ($id) => (int) $id);

        return collect($sucursales)->filter(function ($sucursal) use ($ids) {
            return $ids->contains((int) $sucursal->id);
        })->values();
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
