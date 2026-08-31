<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\MenuTrait;
use App\Traits\SistemasTraits;
use App\Traits\DatosimpleTraits;
use App\Models\historial_cuentas;
use App\Models\Empresas;
use App\Models\Sucursales;
use App\Models\Gastos;
use Carbon\Carbon;
use App\Exports\catalogoGatos;
use App\Exports\catalogoGatosxEmpresa;
use App\Exports\SucursalesExport;
use App\Exports\EmpresasExport;
use App\Exports\CajasExport;
use Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Cajas;
use App\Models\historial_cajas;
use DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;
use App\Models\cuentas;
use App\Exports\CuentasExport;
use App\Models\Puestos;
use App\Models\estados;
use App\Models\Ciudades;
use App\Models\User;

class CatalogosController extends Controller
{

    use MenuTrait;
    use DatosimpleTraits;
    use SistemasTraits;

    public function __construct()
    {
        $this->middleware('auth');
    }

    private function manejarErrorCaja(string $metodo, \Throwable $ex)
    {
        Log::error("CatalogosController::{$metodo}", [
            'message' => $ex->getMessage(),
            'file' => $ex->getFile(),
            'line' => $ex->getLine(),
        ]);

        return back()
            ->withInput()
            ->with('error_msg', "Error en {$metodo}: " . $ex->getMessage());
    }

    private function idEmpleadoSesion(?int $idEmpleadoResponsable = null): int
    {
        $idEmpleado = (int) (auth()->user()->idempleado ?? 0);

        if ($idEmpleado > 0) {
            return $idEmpleado;
        }

        return (int) ($idEmpleadoResponsable ?? 0);
    }

    public function index()
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $permisos1 = $this->forpermisos('gestion_empresas');
            $permisos2 = $this->forpermisos('gestion_sucursales');
            $permisos3 = $this->forpermisos('gestion_gastos');
            $permisos4 = $this->forpermisos('gestion_cajas');
            $permisos5 = $this->forpermisos('gestion_permisosCuentas');

            return view('Catalogos.Index', compact('varpantallas', 'varsubmenus', 'permisos1', 'permisos2', 'permisos3', 'permisos4', 'permisos5'));
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function indexEmpresas()
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $varempresas = $this->obtenerempresasAll();
            $permisos1 = $this->forpermisos('nueva_empresa');
            $permisos2 = $this->forpermisos('editar_empresa');
            $permisos3 = $this->forpermisos('eliminar_empresa');
            $permisos4 = $this->forpermisos('exportar_empresa');
            return view('Catalogos.Empresas.index', compact('varpantallas', 'varsubmenus', 'varempresas', 'permisos1', 'permisos2', 'permisos3', 'permisos4'));
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    private function normalizarRegistroPatronalImss($valor): ?string
    {
        // Forzar string para no perder ceros (ej. "0", "0111...")
        // Debe guardarse en VARCHAR, nunca en INT.
        $registro = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($valor ?? '')) ?? '');
        $registro = substr($registro, 0, 15);

        // No usar empty(): empty("0") === true en PHP
        return $registro === '' ? null : $registro;
    }

    private function normalizarRfc($valor): ?string
    {
        $rfc = strtoupper(preg_replace('/[^A-Za-z0-9Ñ&]/', '', (string) ($valor ?? '')) ?? '');
        $rfc = substr($rfc, 0, 12);

        return $rfc === '' ? null : $rfc;
    }

    public function nuevaEmpresa(Request $request)
    {
        try {
            if ($request->get("efectivo") == 1) {
                $efectivo = 1;
            } else {
                $efectivo = 0;
            }

            $empresa = new Empresas();
            $empresa->nombre_empresa = $request->get("nombre_empresa");
            $empresa->representada = $request->get("representada");
            $empresa->efectivo = $efectivo;
            $empresa->descripcion = $request->get("descripcion");
            $empresa->rfc = $this->normalizarRfc($request->input('rfc'));
            $empresa->registro_patronal_imss = $this->normalizarRegistroPatronalImss($request->input('registro_patronal_imss'));
            $empresa->direccion_fiscal = $request->get("direccion_fiscal");
            $empresa->created_by = auth()->user()->name;
            $empresa->save();

            return back()->with("success", "guardado correctamente");
        } catch (\Illuminate\Database\QueryException $ex) {
            report($ex);
            if (str_contains(strtolower($ex->getMessage()), 'registro_patronal_imss')) {
                return back()->with(
                    'error_msg_large',
                    'registro_patronal_imss debe ser VARCHAR(15), no INT. Ejecute en MySQL: ALTER TABLE tblempresas MODIFY registro_patronal_imss VARCHAR(15) NULL DEFAULT NULL;'
                );
            }
            return back()->with("errorBD", "no guardado correctamente");
        } catch (\Throwable $ex) {
            report($ex);
            return back()->with("error_msg", "No se pudo guardar la empresa.");
        }
    }

    public function editarEmpresa(Request $request, int $id)
    {
        try {

            if ($request->get("estado") == 1) {
                $estado = "A";
            } else {
                $estado = "I";
            }

            if ($request->get("efectivo") == 1) {
                $efectivo = 1;
            } else {
                $efectivo = 0;
            }

            $empresa = Empresas::findOrFail($id);
            $empresa->nombre_empresa = $request->get("nombre_empresa");
            $empresa->representada = $request->get("representada");
            $empresa->descripcion = $request->get("descripcion");
            $empresa->efectivo = $efectivo;
            $empresa->estado = $estado;
            $empresa->rfc = $this->normalizarRfc($request->input('rfc'));
            $empresa->registro_patronal_imss = $this->normalizarRegistroPatronalImss($request->input('registro_patronal_imss'));
            $empresa->direccion_fiscal = $request->get("direccion_fiscal");
            $empresa->updated_by = auth()->user()->name;
            $empresa->save();

            return back()->with("success", "guardado correctamente");

        } catch (\Illuminate\Database\QueryException $ex) {
            report($ex);
            if (str_contains(strtolower($ex->getMessage()), 'registro_patronal_imss')) {
                return back()->with(
                    'error_msg_large',
                    'registro_patronal_imss debe ser VARCHAR(15), no INT. Ejecute en MySQL: ALTER TABLE tblempresas MODIFY registro_patronal_imss VARCHAR(15) NULL DEFAULT NULL;'
                );
            }
            return back()->with("errorBD", "no guardado correctamente");
        } catch (\Throwable $ex) {
            report($ex);
            return back()->with("error_msg", "No se pudo actualizar la empresa.");
        }
    }

    public function eliminarEmpresa(Request $request, int $id)
    {
        try {
            $accion = $request->get("eliminaraccion");

            if ($accion == "borrar") {
                try {
                    $Borrartbl1 = DB::select('delete from tblempresas where id = ? ', [$id]);
                    return back()->with("success", "guardado correctamente");

                } catch (\Illuminate\Database\QueryException $ex) {
                    return back()->with("warningDatabase", "no guardado correctamente");
                }
            } else {
                $empresa = Empresas::find($id);
                $empresa->estado = "I";
                $empresa->updated_by = auth()->user()->name;
                $empresa->save();
                return back()->with("success", "guardado correctamente");
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningDatabase", "no guardado correctamente");
        }
    }

    public function exportarempresas()
    {
        return Excel::download(new EmpresasExport, 'CATALOGO GENERAL DE EMPRESAS.xlsx');
    }


    //CRUD SUCURSAL
    public function indexSucursales()
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $varsucursales = $this->obtenersucursalesAll();
            $obtenerCiudades = $this->obtenerciudades();
            $obtenerempresas = $this->obtenerempresas();
            $permisos1 = $this->forpermisos('nueva_sucursal');
            $permisos2 = $this->forpermisos('editar_sucursal');
            $permisos3 = $this->forpermisos('eliminar_sucursal');
            $permisos4 = $this->forpermisos('exportar_sucursal');
            return view('Catalogos.Sucursales.index', compact('varpantallas', 'varsubmenus', 'varsucursales', 'obtenerCiudades', 'obtenerempresas', 'permisos1', 'permisos2', 'permisos3', 'permisos4'));
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function nuevaSucursal(Request $request)
    {
        try {
            $var = $this->obtenerultimasucusal();
            if ($var->isEmpty()) {
                $idconcecutivo = 1;
            } else {
                foreach ($var as $row) {
                    $idconcecutivo = $row->consecutivo;
                }
            }

            $idconcecutivo = $idconcecutivo + 1;

            $Sucursal = new Sucursales();
            $Sucursal->consecutivo = $idconcecutivo;
            $Sucursal->nombre = $request->get("nombre");
            $Sucursal->telefono = $request->get("telefono");
            $Sucursal->idciudad = $request->get("ciudad");
            $Sucursal->colonia = $request->get("colonia");
            $Sucursal->calle = $request->get("calle");
            $Sucursal->idempresa = $request->get("empresa");
            if (!is_null($request->get('noInt'))) {
                $Sucursal->numero_interior = $request->get('noInt');
            } else {
                $Sucursal->numero_interior = "0";
            }
            $Sucursal->numero_exterior = $request->get("noExt");
            $Sucursal->codigo_postal = $request->get("codigo_postal");
            $Sucursal->created_by = auth()->user()->name;
            $Sucursal->save();

            if ($Sucursal->save()) {
                return back()->with("success", "guardado correctamente");
            } else {
                return back()->with("warning", "no guardado correctamente");
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function editarSucursal(Request $request, int $id)
    {
        try {
            $Sucursal = Sucursales::find($id);
            $Sucursal->nombre = $request->get("nombre");
            $Sucursal->telefono = $request->get("telefono");
            $Sucursal->idciudad = $request->get("ciudad");
            $Sucursal->colonia = $request->get("colonia");
            $Sucursal->calle = $request->get("calle");
            if (!is_null($request->get('noInt'))) {
                $Sucursal->numero_interior = $request->get('noInt');
            } else {
                $Sucursal->numero_interior = "0";
            }
            $Sucursal->numero_exterior = $request->get("noExt");
            $Sucursal->codigo_postal = $request->get("codigo_postal");
            $Sucursal->idempresa = $request->get("empresa");
            $Sucursal->estado = $request->get("activo") == 1 ? "A" : "I";
            $Sucursal->updated_by = auth()->user()->name;
            $Sucursal->save();

            if ($Sucursal->save()) {
                return back()->with("success", "guardado correctamente");
            } else {
                return back()->with("warning", "no guardado correctamente");
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function eliminarSucursal(int $id)
    {
        try {
            $Sucursal = Sucursales::find($id);
            $Sucursal->estado = "I";
            $Sucursal->updated_by = auth()->user()->name;
            $Sucursal->save();

            if ($Sucursal->save()) {
                return back()->with("success", "guardado correctamente");
            } else {
                return back()->with("warning", "no guardado correctamente");
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function exportarsucursales()
    {
        return Excel::download(new SucursalesExport, 'CATALOGO GENERAL DE SUCURSALES.xlsx');
    }

    //CRUD GASTOS
    public function indexGastos()
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $varGastos = $this->obtenerGastos();
            $varempresa = $this->obtenerempresasconid0();
            $permisos1 = $this->forpermisos('nuevo_gasto');
            $permisos2 = $this->forpermisos('editar_gasto');
            $permisos3 = $this->forpermisos('eliminar_gasto');
            $permisos4 = $this->forpermisos('exportar_gasto');
            return view('Catalogos.Gastos.index', compact('varpantallas', 'varsubmenus', 'varGastos', 'permisos1', 'permisos2', 'permisos3', 'permisos4', 'varempresa'));
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function nuevoGasto(Request $request)
    {
        try {
            $varempresa = $this->obtenerGastosNumxEmpresa($request->get("empresa"));

            if ($varempresa->isEmpty()) {
                $id_consecutivo = 1;
            } else {
                foreach ($varempresa as $empresa) {
                    $id_consecutivo = $empresa->id_consecutivo + 1;
                }
            }
            if ($request->get("tipo") == 1) {
                $tipo = "E";
            } else {
                $tipo = "B";
            }

            $Gasto = new Gastos();
            $Gasto->id_consecutivo = $id_consecutivo;
            $Gasto->nombre = $request->get("nombre");

            if(!is_null($request->get("iva"))){
                $Gasto->iva = $request->get("iva");
            }else{
                $Gasto->iva =0;
            }

            if(!is_null($request->get("ret_iva"))){
                $Gasto->ret_iva = $request->get("ret_iva");
            }else{
                $Gasto->ret_iva =0;
            }

            if(!is_null($request->get("ret_isr"))){
                $Gasto->ret_isr = $request->get("ret_isr");
            }else{
                $Gasto->ret_isr =0;
            }

            if(!is_null($request->get("ret_isr_resico"))){
                $Gasto->ret_isr_resico = $request->get("ret_isr_resico");
            }else{
                $Gasto->ret_isr_resico =0;
            }
    
            $Gasto->estado = "A";
            $Gasto->id_empresa = $request->get("empresa");
            $Gasto->rubro = $request->get("rubro");
            $Gasto->tipo = $tipo;
            $Gasto->created_by = auth()->user()->name;
            $Gasto->save();

            if ($Gasto->save()) {
                return back()->with("success", "guardado correctamente");
            } else {
                return back()->with("warning", "no guardado correctamente");
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function editarGasto(Request $request, int $id)
    {
        try {
            if ($request->get("estado") == 1) {
                $estado = "A";
            } else {
                $estado = "I";
            }

            if ($request->get("tipo") == 1) {
                $tipo = "E";
            } else {
                $tipo = "B";
            }

            $Gasto = Gastos::find($id);
            $Gasto->nombre = $request->get("nombre");

            if(!is_null($request->get("iva"))){
                $Gasto->iva = $request->get("iva");
            }else{
                $Gasto->iva =0;
            }

            if(!is_null($request->get("ret_iva"))){
                $Gasto->ret_iva = $request->get("ret_iva");
            }else{
                $Gasto->ret_iva =0;
            }

            if(!is_null($request->get("ret_isr"))){
                $Gasto->ret_isr = $request->get("ret_isr");
            }else{
                $Gasto->ret_isr =0;
            }

            if(!is_null($request->get("ret_isr_resico"))){
                $Gasto->ret_isr_resico = $request->get("ret_isr_resico");
            }else{
                $Gasto->ret_isr_resico =0;
            }

            $Gasto->estado = $estado;
            $Gasto->tipo = $tipo;
            $Gasto->id_empresa = $request->get("empresa");
            $Gasto->rubro = $request->get("rubro");
            $Gasto->updated_by = auth()->user()->name;
            $Gasto->save();

            if ($Gasto->save()) {
                return back()->with("success", "guardado correctamente");
            } else {
                return back()->with("warning", "no guardado correctamente");
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function eliminarGasto(Request $request, int $id)
    {
        try {
            $accion = $request->get("eliminaraccion");

            if ($accion == "borrar") {
                try {
                    $Borrartbl1 = DB::select('delete from tblgastos where id = ? ', [$id]);
                    return back()->with("success", "guardado correctamente");
                } catch (\Illuminate\Database\QueryException $ex) {
                    // dd($ex->getMessage()); 
                    return back()->with("warningDatabase", "no guardado correctamente");
                    // Note any method of class PDOException can be called on $ex.
                }
            } else {
                $Gasto = Gastos::find($id);
                $Gasto->estado = "I";
                $Gasto->updated_by = auth()->user()->name;
                $Gasto->save();
                return back()->with("success", "guardado correctamente");
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningDatabase", "no guardado correctamente");
        }
    }

    public function exportarGasto(Request $request)
    {
        try {
            $tipo = $request->get('empresa');

            if ($tipo == 0) {
                return Excel::download(new catalogoGatos, 'CATALOGO DE GASTOS EXTENDIDO.xlsx');
            } else {
                $varempresa = $this->obtenerempresaxid($request->get('empresa'));
                foreach ($varempresa as $empre) {
                    $nombre = $empre->nombre_empresa;
                }
                return Excel::download(new catalogoGatosxEmpresa($tipo, $nombre), 'CATALOGO DE GASTOS ' . $nombre . '.xlsx');
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    //CRUD CAJAS
    public function indexCaja()
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $varCajas = $this->obtenerCajasAll();
            $varsucursales = $this->obtenersucursales();
            $varusers = $this->obtenerUsuariosCaja();
            $permisos1 = $this->forpermisos('nueva_caja');
            $permisos2 = $this->forpermisos('editar_caja');
            $permisos3 = $this->forpermisos('eliminar_caja');
            $permisos4 = $this->forpermisos('exportar_caja');
            $permisos5 = $this->forpermisos('asignar_responsable_caja');
            return view('Catalogos.Cajas.index', compact('varpantallas', 'varsubmenus', 'varCajas', 'permisos1', 'permisos2', 'permisos3', 'permisos4', 'varsucursales', 'permisos5', 'varusers'));
        } catch (\Illuminate\Database\QueryException $ex) {
            Log::error('CatalogosController::indexCaja', ['message' => $ex->getMessage()]);
            return back()->with('error_msg', 'Error al cargar cajas: ' . $ex->getMessage());
        } catch (\Throwable $ex) {
            Log::error('CatalogosController::indexCaja', ['message' => $ex->getMessage()]);
            return back()->with('error_msg', 'Error al cargar cajas: ' . $ex->getMessage());
        }
    }

    public function nuevaCaja(Request $request)
    {
        try {
            $date = Carbon::now();
            $fecha = $date->format('Y-m-d');
            if ($request->get("tipoCaja") == "caja_chica") {
                $tipo = "caja_chica";
            } else {
                $tipo = "caja";
            }

            $idEmpleado = (int) $request->get("responsable");
            if ($idEmpleado <= 0) {
                return back()
                    ->withInput()
                    ->with('warning_msg', 'Debe seleccionar un responsable para la caja.');
            }

            $userResponsable = $this->resolverUsuarioResponsableCaja($idEmpleado);
            $idEmpleadoSesion = $this->idEmpleadoSesion($idEmpleado);

            $Cajas = new Cajas();
            $Cajas->nombre = $request->get("nombre");
            $Cajas->status = "A";
            $Cajas->fecha_alta = $request->get("fecha_alta");
            $Cajas->id_usuario = $userResponsable->id;
            $Cajas->responsable_status = "N";
            $Cajas->id_sucursal = $request->get("sucursal");
            $Cajas->saldo_inicial = $request->get("saldo_inicial");
            $Cajas->saldo_actual = $request->get("saldo_inicial");
            $Cajas->tipo = $tipo;
            $Cajas->created_by = auth()->user()->name;
            $Cajas->save();

            $ultimacaja = $this->obtenerultimacaja();
            if ($ultimacaja->isEmpty()) {
                return back()->withInput()->with('warning_msg', 'La caja se guardó pero no se pudo registrar el historial de apertura.');
            }

            $caja = $ultimacaja->first()->id;

            $historialcuent = new historial_cajas();
            $historialcuent->id_caja = $caja;
            $historialcuent->id_empleado = $idEmpleadoSesion;
            $historialcuent->estado = "A";
            $historialcuent->tipo_movimiento = "APERTURA";
            $historialcuent->concepto = "APERTURA DE CAJA";
            $historialcuent->responsable = "EMPLEADO #" . $idEmpleadoSesion;
            $historialcuent->ingreso = $request->get('saldo_inicial');
            $historialcuent->egreso = 0;
            $historialcuent->saldo = $request->get('saldo_inicial');
            $historialcuent->numero_referencia = $caja;
            $historialcuent->tipo_referencia = "caja";
            $historialcuent->numero_poliza = 0;
            $historialcuent->fecha = $fecha;
            $historialcuent->created_by = auth()->user()->name;
            $historialcuent->save();

            return back()->with('success_msg', '¡Caja creada correctamente!');
        } catch (\Illuminate\Database\QueryException $ex) {
            return $this->manejarErrorCaja('nuevaCaja', $ex);
        } catch (\Throwable $ex) {
            return $this->manejarErrorCaja('nuevaCaja', $ex);
        }
    }

    public function editarCaja(Request $request, int $id)
    {
        try {
            if ($request->get("tipoCaja") == "caja_chica") {
                $tipo = "caja_chica";
            } else {
                $tipo = "caja";
            }

            $Cajas = Cajas::find($id);
            if (!$Cajas) {
                return back()->with('warning_msg', 'No se encontró la caja a editar.');
            }

            $Cajas->nombre = $request->get("nombre");
            $Cajas->status = "A";
            $Cajas->fecha_alta = $request->get("fecha_alta");
            $Cajas->id_sucursal = $request->get("sucursal");
            $Cajas->tipo = $tipo;
            $Cajas->updated_by = auth()->user()->name;
            $Cajas->save();

            return back()->with('success_msg', '¡Caja actualizada correctamente!');
        } catch (\Illuminate\Database\QueryException $ex) {
            return $this->manejarErrorCaja('editarCaja', $ex);
        } catch (\Throwable $ex) {
            return $this->manejarErrorCaja('editarCaja', $ex);
        }
    }

    public function eliminarCaja(Request $request, int $id)
    {
        try {
            $date = Carbon::now();
            $fecha = $date->format('Y-m-d');

            $Cajas = Cajas::find($id);
            if (!$Cajas) {
                return back()->with('warning_msg', 'No se encontró la caja a cancelar.');
            }

            $varcajas = $this->obtenerCajasxId($id);
            $saldo = 0;
            $nombreCaja = $Cajas->nombre;
            foreach ($varcajas as $caja) {
                $saldo = $caja->saldo_actual;
                $nombreCaja = $caja->nombre;
            }

            $Cajas->status = "C";
            $Cajas->updated_by = auth()->user()->name;
            $Cajas->save();

            $idEmpleadoSesion = $this->idEmpleadoSesion();

            $historialcuent = new historial_cajas();
            $historialcuent->id_caja = $id;
            $historialcuent->id_empleado = $idEmpleadoSesion;
            $historialcuent->estado = "A";
            $historialcuent->tipo_movimiento = "CANCELACION";
            $historialcuent->concepto = "CANCELACION DE CAJA" . $nombreCaja;
            $historialcuent->descripcion = $request->get('descripcion');
            $historialcuent->responsable = "EMPLEADO #" . $idEmpleadoSesion;
            $historialcuent->ingreso = 0;
            $historialcuent->egreso = 0;
            $historialcuent->saldo = $saldo;
            $historialcuent->numero_referencia = $id;
            $historialcuent->tipo_referencia = "caja";
            $historialcuent->numero_poliza = 0;
            $historialcuent->fecha = $fecha;
            $historialcuent->created_by = auth()->user()->name;
            $historialcuent->save();

            return back()->with('success_msg', '¡Caja cancelada correctamente!');
        } catch (\Illuminate\Database\QueryException $ex) {
            return $this->manejarErrorCaja('eliminarCaja', $ex);
        } catch (\Throwable $ex) {
            return $this->manejarErrorCaja('eliminarCaja', $ex);
        }
    }

    public function getdownloadPoliticas(string $tipo)
    {
        try {
            if ($tipo == "caja_chica") {
                $file = public_path() . "/Politicas/Responsable_CajaChica.pdf";
            } else {
                $file = public_path() . "/Politicas/Responsable_CajaSucursal.pdf";
            }

            if (file_exists($file)) {
                $headers = array(
                    'Content-Type: application/pdf',
                );
                return Response::download($file, 'POLITICAS CAJA.pdf', $headers);

            } else {
                return back()->with("errorArchivo", "No se genero el archivo");
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function getdownloadCartaResponsiva(int $id, string $tipo, string $nombreResponsable, string $idEmpleado, string $nombreEmpresa, string $nombreCaja)
    {
        $date = Carbon::now()->locale('es')->isoFormat('D \d\e MMMM \d\e\l Y');//28 de agosto del 2023
        $fechaEntrega = strtoupper($date); //convertir a mayuscula

        $info_caja = DB::select(' select tblcajas.nombre, tblcajas.status, tblcajas.saldo_actual, tblcajas.saldo_inicial,  tblsucursales. nombre as sucursal, tblcajas.fecha_alta from tblcajas
        inner join  tblsucursales on tblcajas.id_sucursal = tblsucursales.id where tblcajas.id = ?;', [$id]);

        $pdf = \PDF::setPaper('letter')->loadView('Catalogos.PDF.cartaResponsiva', compact('id', 'tipo', 'nombreResponsable', 'idEmpleado', 'nombreEmpresa', 'nombreCaja', 'fechaEntrega', 'info_caja'));
        return $pdf->download("CARTA RESPONSIVA_#" . $id . " - " . $nombreCaja . ".pdf");
    }

    public function subirDocumentos(Request $request, int $id, string $nombreSuc, string $nombreCaja)
    {
        try {
            if (!$request->hasFile("cartaResponsiva") || !$request->hasFile("pagare")) {
                return back()->with('warning_msg', 'Debe subir la carta responsiva y el pagaré.');
            }

            $Rutacarpeta = "Tesoreria/Responsivas";
            File::makeDirectory($Rutacarpeta, 0777, true, true);

            $file_carta = $request->file("cartaResponsiva");
            $nombre_carta = "carta_resposiva_" . "$id" . "." . $file_carta->guessExtension();
            $ruta_carta = public_path($Rutacarpeta . "/" . $nombre_carta);
            copy($file_carta, $ruta_carta);

            $file_pagare = $request->file("cartaResponsiva");
            $nombre_pagare = "pagare_" . "$id" . "." . $file_pagare->guessExtension();
            $ruta_pagare = public_path($Rutacarpeta . "/" . $nombre_pagare);
            copy($file_pagare, $ruta_pagare);

            $Cajas = Cajas::find($id);
            if (!$Cajas) {
                return back()->with('warning_msg', 'No se encontró la caja para subir documentos.');
            }

            $Cajas->responsable_status = "A";
            $Cajas->ruta_comprobante_carta = $nombre_carta;
            $Cajas->status_carta = 'A';
            $Cajas->ruta_comprobante_pagare = $nombre_pagare;
            $Cajas->status_pagare = 'A';
            $Cajas->created_by = auth()->user()->name;
            $Cajas->save();

            return back()->with('success_msg', 'Documentación guardada correctamente.');
        } catch (\Illuminate\Database\QueryException $ex) {
            return $this->manejarErrorCaja('subirDocumentos', $ex);
        } catch (\Throwable $ex) {
            return $this->manejarErrorCaja('subirDocumentos', $ex);
        }
    }

    public function exportarCajas(Request $request)
    {
        return Excel::download(new CajasExport, 'CATALOGO GENERAL DE CAJAS.xlsx');
    }

    public function indexCuentas(){
        try{
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();
            $obtenerCuentas =   $this->obtenerCuentasBanco();
            $obtenerempresas =   $this->obtenerempresas();
            $permisos1 = $this->forpermisos('nueva_cuenta');
            $permisos2 = $this->forpermisos('cancelar_cuenta');
            // $permisos3 = $this->forpermisos('tranferencia_cuenta');
            $permisos4 = $this->forpermisos('editar_cuenta');
            $permisos5 = $this->forpermisos('detalle_cuenta');
            $permisos6 = $this->forpermisos('exportar_cuenta');
            return view('Catalogos.Cuentas.index',compact('varpantallas','varsubmenus','obtenerCuentas','obtenerempresas',
        'permisos1','permisos2','permisos4','permisos5','permisos6'));
        } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    }

    public function nuevaCuenta(Request $request){
        try{
            $date = Carbon::now();
            $fecha = $date->format('Y-m-d');
    
            $cuentas = new cuentas();
            $cuentas->id_cuenta = $request->get('no_cuenta');
            $cuentas->nombre= $request->get('nombre');
            $cuentas->status= "A";
            $cuentas->saldo_inicial = $request->get('saldo_inicial');
            $cuentas->saldo_actual = $request->get('saldo_inicial');
            $cuentas->fecha_alta= $request->get('fecha_alta');
            $cuentas->tipo= $request->get('tipo');
            $cuentas->id_empresa  = $request->get('empresa');
            $cuentas->created_by=auth()->user()->name;

            if($cuentas->save()){
                $ultimacuenta =$this->obtenerultimacuenta();
                foreach($ultimacuenta as $cuent){$cuenta = $cuent->id;}

                $historialcuent = new  historial_cuentas();
                $historialcuent->id_cuenta = $cuenta;
                $historialcuent->id_empleado = auth()->user()->idempleado;
                $historialcuent->estado = "A";
                $historialcuent->tipo_movimiento = "APERTURA";
                $historialcuent->concepto = "APERTURA DE CUENTA";
                $historialcuent->responsable = "EMPLEADO #".auth()->user()->idempleado;
                $historialcuent->ingreso =  $request->get('saldo_inicial');
                $historialcuent->egreso = 0;
                $historialcuent->saldo =  $request->get('saldo_inicial');
                $historialcuent->numero_referencia = $cuenta;
                $historialcuent->tipo_referencia = "cuenta";
                $historialcuent->numero_poliza = 0;
                $historialcuent->fecha = $fecha;
                $historialcuent->created_by=auth()->user()->name;
                $historialcuent->save();
    
                return back()->with("success","¡Se guardaron los cambios correctamente!");
            }else{
                return back()->with("warning","No se logro");
            }
         } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    }

    public function editarCuenta(int $cuenta, Request $request){
        try{
            $date = Carbon::now();
            $fecha = $date->format('Y-m-d');
            $varobtenercuentas =$this->obtenercuentasPrincipales($cuenta);
            foreach($varobtenercuentas as $varobtenercuenta){
                $saldo_actual = $varobtenercuenta->saldo_actual;
                $saldo_inicial = $varobtenercuenta->saldo_inicial;
            }

            $cuentas = cuentas::find($cuenta);
            if(!is_null($request->get('no_cuenta'))){
                $cuentas->id_cuenta = $request->get('no_cuenta');
            }else{$cuentas->id_cuenta ="0";}
            $cuentas->nombre= $request->get('nombre');
            $cuentas->status= "A";
            $cuentas->fecha_alta= $request->get('fecha_alta');
            $cuentas->tipo= $request->get('tipo');
            $cuentas->id_empresa  = $request->get('empresa');
            $cuentas->updated_by=auth()->user()->name;
            $cuentas->save();
            
            if($cuentas->save()){
                return back()->with("success","¡Se guardaron los cambios correctamente!");
            }else{
                return rback()->with("warning","No se logro");
            }
        } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    }

    public function cancelarCuenta(int $cuenta, Request $request){
        try{
            $date = Carbon::now();
            $fecha = $date->format('Y-m-d');

            $varobtenercuentas =$this->obtenercuentasPrincipales($cuenta);
            foreach($varobtenercuentas as $varobtenercuenta){
                $saldoCuenta = $varobtenercuenta->saldo_actual;
                $nombreCuenta = $varobtenercuenta->descripcion;
            }

            $cuentas = cuentas::find($cuenta);
            $cuentas->status= "C";
            $cuentas->updated_by=auth()->user()->name;
            $cuentas->save();

            if($cuentas->save()){

                $historialcuent = new  historial_cuentas();
                $historialcuent->id_cuenta = $cuenta;
                $historialcuent->id_empleado = auth()->user()->idempleado;
                $historialcuent->estado = "A";
                $historialcuent->tipo_movimiento = "CANCELACION";
                $historialcuent->concepto = "CANCELACION DE CUENTA".$nombreCuenta;
                $historialcuent->descripcion = $request->get('descripcion');
                $historialcuent->responsable = "EMPLEADO #".auth()->user()->idempleado;
                $historialcuent->ingreso =  0;
                $historialcuent->egreso = 0;
                $historialcuent->saldo =  $saldoCuenta;
                $historialcuent->numero_referencia = $cuenta;
                $historialcuent->tipo_referencia = "cuenta";
                $historialcuent->numero_poliza = 0;
                $historialcuent->fecha = $fecha;
                $historialcuent->created_by=auth()->user()->name;
                $historialcuent->save();

                return back()->with("success","¡Se guardaron los cambios correctamente!");
            }else{
                return back()->with("warning","No se logro");
            }
        } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    }

    public function exportarcuentas(){
        return Excel::download(new CuentasExport, 'CATALOGO GENERAL DE CUENTAS.xlsx');
    }

    //PUESTOS
    public function indexPuestos(){
        try{
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();
            $obtenerpuestosAll =   $this->obtenerpuestosAll();
            $permisos1 = $this->forpermisos('nuevo_puesto');
            $permisos2 = $this->forpermisos('editar_puesto');
            $permisos3 = $this->forpermisos('eliminar_puesto');

            return view('Catalogos.Puestos.index',compact('varpantallas','varsubmenus','obtenerpuestosAll',
        'permisos1','permisos2','permisos3'));
        } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    }

    public function nuevoPuesto(Request $request){
        try{
            $date = Carbon::now();
            $fecha = $date->format('Y-m-d');

            $Puestos = new Puestos();
            $Puestos->nombre = $request->get('nombre');
            $Puestos->descripcion = $request->get('descripcion');
            $Puestos->estado= "A";
            $Puestos->created_by=auth()->user()->name;
            $Puestos->save();

            if($Puestos->save()){
                return back()->with("success","¡Se guardaron los cambios correctamente!");
            }else{
                return rback()->with("warning","No se logro");
            }
         } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    }

    public function editarPuesto(int $id, Request $request){
        try{
            $date = Carbon::now();
            $fecha = $date->format('Y-m-d');

            $Puestos = Puestos::find($id);
            $Puestos->nombre = $request->get('nombre');
            $Puestos->descripcion = $request->get('descripcion');
            $Puestos->estado= "A";
            $Puestos->updated_by = auth()->user()->name;
            $Puestos->save();

            if($Puestos->save()){
                return back()->with("success","¡Se guardaron los cambios correctamente!");
            }else{
                return rback()->with("warning","No se logro");
            }
         } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    }

    public function eliminarPuesto(int $id, Request $request){
        try{
            $date = Carbon::now();
            $fecha = $date->format('Y-m-d');

            if($request->get('eliminar') == "desactivar"){

                $Puestos = Puestos::find($id);
                $Puestos->estado = "I";
                $Puestos->updated_by=auth()->user()->name;

                if($Puestos->save()){
                    return back()->with("success","exito");
                }else{
                    return back()->with("error","no uardadoexito");
                }

            }else{
                try{
                    $Borrartbl =  DB::select('delete from tblpuestos where id = ? ', [$id]);
                    return back()->with("success","exito");
                } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningEliminar","no guardado correctamente"); }
            }
        } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    }

     //ESTADOS
    public function indexestados_ciudades(Request $request){
        try{
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();
            $permisos1 = $this->forpermisos('nuevo_lugar');
            $permisos2 = $this->forpermisos('editar_lugar');
            $permisos3 = $this->forpermisos('eliminar_lugar');

            if(!is_null($request->get('filtro'))){
                $filtro = $request->get('filtro');
                if($filtro == "Estado"){
                    $filtro = "Estado";
                    $obtenerlugar =   $this->obtenerestadosAll();
                }else{
                    $filtro = "Ciudad";
                    $obtenerlugar =   $this->obtenerciudadesAll();
                }


            }else{
                $filtro = "Estado";
                $obtenerlugar =   $this->obtenerestadosAll();
            }

            $listEstados =   $this->obtenerestadosAll();

            return view('Catalogos.Lugares.index',compact('varpantallas','varsubmenus','obtenerlugar','listEstados',
            'permisos1','permisos2','permisos3','filtro'));

        } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    }

    public function nuevoEstado(Request $request){
        try{
            $date = Carbon::now();
            $fecha = $date->format('Y-m-d');

            $Puestos = new estados();
            $Puestos->nombre = $request->get('nombre');
            $Puestos->created_by=auth()->user()->name;
            $Puestos->save();

            if($Puestos->save()){
                return back()->with("success","¡Se guardaron los cambios correctamente!");
            }else{
                return rback()->with("warning","No se logro");
            }
         } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    }

    public function editarEstado(int $id, Request $request){
        try{
            $date = Carbon::now();
            $fecha = $date->format('Y-m-d');

            $Puestos = estados::find($id);
            $Puestos->nombre = $request->get('nombre');
            $Puestos->updated_by = auth()->user()->name;
            $Puestos->save();

            if($Puestos->save()){
                return back()->with("success","¡Se guardaron los cambios correctamente!");
            }else{
                return rback()->with("warning","No se logro");
            }
         } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    }

    public function eliminarEstado(int $id, Request $request){
        try{
            $date = Carbon::now();
            $fecha = $date->format('Y-m-d');

            $Borrartbl =  DB::select('delete from tblestados where id = ? ', [$id]);
            return back()->with("success","exito");

        } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningEliminarE","depende de otro dato"); }
    }

    //CIUDADES
    public function nuevaCiudad(Request $request){
        try{
            $date = Carbon::now();
            $fecha = $date->format('Y-m-d');

            $Puestos = new Ciudades();
            $Puestos->nombre = $request->get('nombre');
            $Puestos->idestado  = $request->get('idestado');
            $Puestos->created_by=auth()->user()->name;
            $Puestos->save();

            if($Puestos->save()){
                return back()->with("success","¡Se guardaron los cambios correctamente!");
            }else{
                return rback()->with("warning","No se logro");
            }
         } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    }

    public function editarCiudad(int $id, Request $request){
        try{
            $date = Carbon::now();
            $fecha = $date->format('Y-m-d');

            $Puestos = Ciudades::find($id);
            $Puestos->nombre = $request->get('nombre');
            $Puestos->idestado  = $request->get('idestado');
            $Puestos->updated_by = auth()->user()->name;
            $Puestos->save();

            if($Puestos->save()){
                return back()->with("success","¡Se guardaron los cambios correctamente!");
            }else{
                return rback()->with("warning","No se logro");
            }
         } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningBD","no guardado correctamente"); }
    }

    public function eliminarCiudad(int $id, Request $request){
        try{
            $date = Carbon::now();
            $fecha = $date->format('Y-m-d');

            $Borrartbl =  DB::select('delete from tblciudades where id = ? ', [$id]);
            return back()->with("success","exito");

        } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warningEliminarC","depende de otro dato"); }
    }
    
}
