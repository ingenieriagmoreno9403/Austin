<?php

namespace App\Http\Controllers;

use App\Models\Empleados;
use App\Models\Empresas;
use App\Models\nominas;
use App\Models\Vistas;
use App\Models\empleados_bajas;
use App\Http\Requests\StoreEmpleadosRequest;
use App\Http\Requests\UpdateEmpleadosRequest;
use App\Traits\MenuTrait;
use App\Traits\SistemasTraits;
use App\Traits\DatosimpleTraits;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Exports\UsersExport;
use App\Imports\EmpleadosImport;
use App\Imports\NominaEmpleadosImport;
use Log;
use Maatwebsite\Excel\Facades\Excel;
use Luecano\NumeroALetras\NumeroALetras;
use DB;
use App\Models\incapacidades;
use App\Models\vacaciones;
use App\Models\EmpleadosHorarios;
use App\Services\SuaExportService;
use Illuminate\Support\Facades\File;

class EmpleadosController extends Controller
{
    use MenuTrait;
    use DatosimpleTraits;
    use SistemasTraits;

    public function __construct(){
        $this->middleware('auth');
    }

    private function empleadosErrorResponse(\Throwable $ex, string $context)
    {
        Log::error("EmpleadosController::{$context}: " . $ex->getMessage(), ['exception' => $ex]);

        return back()->with('error_msg_large', "{$context}. {$ex->getMessage()}");
    }
    
    public function index(Request $request){
        try{
            $date = Carbon::now();
            $date = $date->format('Y-m-d');
            $idusuario=auth()->user()->id;
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();
            $varpuestos = $this->obtenerpuestos();
            $varsucursales = $this->obtenersucursales();
            $varciudades =  $this->obtenerciudades();
            $varempresas = $this->obtenerempresas();
            $varbancos = $this->obtenerbancos();
            $vartipodescinfo= $this->obtenertipodescinfonavit();
            $permisos1 = $this->forpermisos('alta_empleados');
            $permisos2 = $this->forpermisos('graficar_empleados');
            $permisos3 = $this->forpermisos('exportar_empleados');
            $permisos4 = $this->forpermisos('eliminar_empleados');
            $permisos5 = $this->forpermisos('reactivar_empleados');

            $filtro = $request->get('filtro', 'A');
            if (!in_array($filtro, ['A', 'I', ''], true)) {
                $filtro = 'A';
            }
            if ($filtro === null) {
                $filtro = 'A';
            }

            $filtroSucursal = $request->get('sucursal');
            $filtroPuesto = $request->get('puesto');

            $varlistaempleados = $this->obtenerEmpleadosFiltro($filtro, $filtroSucursal, $filtroPuesto);
            $contratosPorVencer = $this->obtenerContratosPorVencer(7);
            $vardias = $this->obtenerdias();
            $varhorarios = $this->obtenerhorarios();

            // Modal SUA: incluir también bajas (inactivos) para movimiento 02.
            $varlistaempleadosSua = collect($varlistaempleados)->keyBy(fn ($e) => (int) $e->idempleado);
            if ($filtro !== 'I') {
                foreach ($this->obtenerEmpleadosFiltro('I', $filtroSucursal, $filtroPuesto) as $empBaja) {
                    $varlistaempleadosSua->put((int) $empBaja->idempleado, $empBaja);
                }
            }
            $varlistaempleadosSua = $varlistaempleadosSua->values();
            $idsEmpleadosSua = $varlistaempleadosSua->pluck('idempleado')->map(fn ($id) => (int) $id)->all();

            $suaExport = app(SuaExportService::class);
            $jornadasSuaSugeridas = $suaExport->sugerirJornadasPorEmpleados($idsEmpleadosSua);
            $jornadaSuaDefault = $suaExport->jornadaSugeridaMayoritaria($jornadasSuaSugeridas, '0');
            $jornadasSuaOpciones = SuaExportService::JORNADAS;
            $tiposTrabajadorSuaOpciones = SuaExportService::TIPOS_TRABAJADOR;
            $tiposMovimientoSuaOpciones = SuaExportService::TIPOS_MOVIMIENTO;
            $tiposMovimientoCreditoSuaOpciones = SuaExportService::TIPOS_MOVIMIENTO_CREDITO;
            $tiposDescuentoSuaOpciones = SuaExportService::TIPOS_DESCUENTO;
            $ramasIncapacidadSuaOpciones = SuaExportService::RAMAS_INCAPACIDAD;
            $tiposRiesgoSuaOpciones = SuaExportService::TIPOS_RIESGO;
            $secuelasIncapacidadSuaOpciones = SuaExportService::SECUELAS_INCAPACIDAD;
            $controlesIncapacidadSuaOpciones = SuaExportService::CONTROLES_INCAPACIDAD;
            $bajasSua = $this->obtenerUltimasBajasSua($idsEmpleadosSua);
            $incapacidadesSua = $this->obtenerUltimasIncapacidadesSua($idsEmpleadosSua);

            return view('Empleados.Index',compact('varpantallas','varsubmenus','varlistaempleados',
            'varlistaempleadosSua','varpuestos','varsucursales','varciudades','varempresas','varbancos','vartipodescinfo',
            'permisos1','permisos2','permisos3','permisos4','permisos5','filtro','filtroSucursal','filtroPuesto',
            'contratosPorVencer','vardias','varhorarios','jornadasSuaSugeridas','jornadaSuaDefault','jornadasSuaOpciones',
            'tiposTrabajadorSuaOpciones','tiposMovimientoSuaOpciones','tiposMovimientoCreditoSuaOpciones',
            'tiposDescuentoSuaOpciones','ramasIncapacidadSuaOpciones','tiposRiesgoSuaOpciones',
            'secuelasIncapacidadSuaOpciones','controlesIncapacidadSuaOpciones','bajasSua','incapacidadesSua'));

        } catch(\Throwable $ex){
            return $this->empleadosErrorResponse($ex, 'No se pudo cargar el catálogo de empleados');
        }
    }

    public function store(Request $request){
        try{
            $empleados = new empleados();
            $empleados->primer_nombre = $request->get('primer_nombre');
            if(!is_null($request->get('segundo_nombre'))){
                $empleados->segundo_nombre = $request->get('segundo_nombre');
            }else{$empleados->segundo_nombre =" ";}
            $empleados->apellido_paterno = $request->get('apellido_paterno');
            $empleados->apellido_materno = $request->get('apellido_materno');
            $empleados->telefono = $request->get('telefono');
            $empleados->correo = $request->get('correo');
            $empleados->tipo_contratacion = $request->get('tipo_contratacion');
            $empleados->fecha_determinado = $request->get('tipo_contratacion') === 'DEFINIDA'
                ? $request->get('fecha_determinado')
                : null;
            $empleados->idpuesto = $request->get('puesto');
            $empleados->grado_estudio= $request->get('grado_estudio');
            $empleados->nacionalidad = $request->get('nacionalidad');
            $empleados->idsucursal = $request->get('sucursal');
            $empleados->idciudad = $request->get('ciudad');
            $empleados->idbanco = $request->get('banco');
            $empleados->calle = $request->get('calle');
            $empleados->colonia = $request->get('colonia');
            if(!is_null($request->get('numero_interior'))){
                $empleados->numero_interior = $request->get('numero_interior');
            }else{$empleados->numero_interior ="0";}
            $empleados->numero_exterior = $request->get('numero_exterior');
            $empleados->codigo_postal = $request->get('codigo_postal');
            $empleados->sexo = $request->get('sexo');
            $empleados->fecha_nacimiento = $request->get('fecha_nacimiento');
            $empleados->foto = 1;
            $empleados->nombre_foto = "0.jpg";
            $empleados->archivo_baja = 0;
            $empleados->rfc = $request->get('rfc');
            $empleados->colonia_f = $request->get('colonia_f');
            $empleados->calle_f = $request->get('calle_f');
            $empleados->no_interior_f = $request->get('no_interior_f');
            $empleados->no_exterior_f = $request->get('no_exterior_f');
            $empleados->codigo_postal_f = $request->get('codigo_postal_f');
            
            $empleados->nss = $request->get('nss');
            $empleados->curp = $request->get('curp');
            $empleados->tipo_sangre = $request->get('tipo_sangre');
            $empleados->contacto_emergencia = $request->get('contacto_emergencias');
            $empleados->telefono_emergencia = $request->get('telefono_emergencia');
            $empleados->estado = 'A';
            $empleados->descripcion_estado = 'ALTA EMPLEADO';
            $empleados->estado_civil = $request->get('estado_civil');
            $empleados->fecha_ingreso = $request->get('fecha_alta');
            $empleados->tipo_tranferencia= $request->get('tipo_tranferencia');
            $empleados->created_by = auth()->user()->name;
            
            
            //insertamos en la tabla empleados
            if($empleados->save()){
                $ultimoemple =$this->obtenerultimoempleado();
                $nominas = new nominas();
                $nominas->idempresa=$request->get('cmbempresas');
                $nominas->idempleado=$ultimoemple->id;
                $nominas->idbancos=$request->get('banco');
                $nominas->salario_bruto=$request->get('salario_bruto');
                $nominas->salario_fijo=$request->get('salario_fijo');
                $nominas->excedente=$request->get('excedente');
                // $nominas->efectivo=$request->get('efectivo');
                $nominas->idbanca=$request->get('idbanca');
                $nominas->numero_tarjeta=$request->get('numero_tarjeta');
                $nominas->numero_cuenta=$request->get('numero_cuenta');
                $nominas->id_tipoinfonavit = $request->get('tipo_infonavit');
                $nominas->factor_sua = $request->get('factor_sua');
                // $nominas->descuento_quincenal = $request->get('descuento_quincenal');
                $nominas->numero_credito_infonavit = $request->get('numero_credito_infonavit');
                $nominas->fecha_ingreso_imss = $request->get('fecha_ingreso_imss');
                $nominas->zona = $request->get('zona');
                $nominas->created_by = auth()->user()->name;
                $nominas->save();
                $this->guardarHorariosEmpleado($request, $ultimoemple->id);
                return redirect()->route('verempleados')->with('success_msg_large', 'El empleado fue registrado correctamente en el catálogo.');
            }else{
                return redirect()->route('verempleados')->with('warning_msg_large', 'No se pudo registrar el empleado. Verifique los datos e intente nuevamente.');
            }
        } catch(\Throwable $ex){
            return $this->empleadosErrorResponse($ex, 'Error al registrar el empleado');
        }
    }

    public function getdownloadContrato(int $idemp){
        $obtenerempleado = $this->obtenerlistaempleadoid($idemp);
        $now = Carbon::now();

        $salario_diario = 0;
        $salario_hora = 0;
        $salario_semanal = 0;
        $salario_semanal_letras = '';
        $fecha_nacimiento = $now;

        foreach ($obtenerempleado as $emple) {
            $salario_aplicado = $emple->efectivo > 0 ? $emple->efectivo : $emple->salario_fijo;
            $formatter = new NumeroALetras();
            $salario_diario = (float) $salario_aplicado;
            $salario_hora = round($salario_diario / 8, 2);
            $salario_semanal = round($salario_diario * 7, 2);
            $salario_semanal_letras = strtoupper($formatter->toWords((int) round($salario_semanal)));
            $fecha_nacimiento = Carbon::parse($emple->fecha_nacimiento);
        }

        $dateNow = strtoupper($now->locale('es')->isoFormat('D \d\e MMMM \d\e\l Y'));
        $fechaNacimientoFmt = strtoupper($fecha_nacimiento->locale('es')->isoFormat('D [DE] MMMM [DE] YYYY'));
        $edad = $fecha_nacimiento->diffInYears($now);
        $diaFirma = $now->day;
        $mesFirma = strtoupper($now->locale('es')->isoFormat('MMMM'));
        $anioFirma = $now->year;

        $pdf = \PDF::setPaper('letter')->loadView('Empleados.PDF.contrato', compact(
            'obtenerempleado',
            'dateNow',
            'edad',
            'fechaNacimientoFmt',
            'salario_diario',
            'salario_hora',
            'salario_semanal',
            'salario_semanal_letras',
            'diaFirma',
            'mesFirma',
            'anioFirma'
        ));

        return $pdf->stream("CONTRATO EMPLEADO_#".$idemp.".pdf");
    }

    public function getdownloadContratoDeterminado(int $idemp){
        $obtenerempleado = $this->obtenerlistaempleadoid($idemp);
        $now = Carbon::now();

        $salario_diario = 0;
        $salario_hora = 0;
        $salario_semanal = 0;
        $salario_semanal_letras = '';
        $fecha_nacimiento = $now;
        $fecha_ingreso = $now;
        $fecha_determinado = $now;

        foreach ($obtenerempleado as $emple) {
            $salario_aplicado = $emple->efectivo > 0 ? $emple->efectivo : $emple->salario_fijo;
            $formatter = new NumeroALetras();
            $salario_diario = (float) $salario_aplicado;
            $salario_hora = round($salario_diario / 8, 2);
            $salario_semanal = round($salario_diario * 7, 2);
            $salario_semanal_letras = strtoupper($formatter->toWords((int) round($salario_semanal)));
            $fecha_nacimiento = Carbon::parse($emple->fecha_nacimiento);
            $fecha_ingreso = Carbon::parse($emple->fecha_ingreso);
            $fecha_determinado = $emple->fecha_determinado
                ? Carbon::parse($emple->fecha_determinado)
                : $fecha_ingreso->copy()->addMonths(3);
        }

        $fechaNacimientoFmt = strtoupper($fecha_nacimiento->locale('es')->isoFormat('D [DE] MMMM [DE] YYYY'));
        $fechaInicioFmt = strtoupper($fecha_ingreso->locale('es')->isoFormat('D [DE] MMMM [DE] YYYY'));
        $fechaFinFmt = strtoupper($fecha_determinado->locale('es')->isoFormat('D [DE] MMMM [DE] YYYY'));
        $edad = $fecha_nacimiento->diffInYears($now);
        $diaFirma = $now->day;
        $mesFirma = strtoupper($now->locale('es')->isoFormat('MMMM'));
        $anioFirma = $now->year;

        $pdf = \PDF::setPaper('letter')->loadView('Empleados.PDF.contrato_determinado', compact(
            'obtenerempleado',
            'edad',
            'fechaNacimientoFmt',
            'fechaInicioFmt',
            'fechaFinFmt',
            'salario_diario',
            'salario_hora',
            'salario_semanal',
            'salario_semanal_letras',
            'diaFirma',
            'mesFirma',
            'anioFirma'
        ));

        return $pdf->stream("CONTRATO DETERMINADO EMPLEADO_#".$idemp.".pdf");
    }

    public function edit($id){
        try{
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();
            $varpuestos = $this->obtenerpuestos();
            $varsucursales = $this->obtenersucursales();
            $varciudades =  $this->obtenerciudades();
            $varempresas = $this->obtenerempresas();
            $varbancos = $this->obtenerbancos();
            $vartipodescinfo= $this->obtenertipodescinfonavit();
            $date = Carbon::now();
            $date = $date->format('Y-m-d');
            $obtenerempleado = $this->obtenerlistaempleadoid($id);
            $permisos = $this->forpermisos('actualizar_empleados');
            $vardias = $this->obtenerdias();
            $varhorarios = $this->obtenerhorarios();
            $horariosEmpleado = $this->obtenerHorariosEmpleado($id);

            if($permisos=="actualizar_empleados")
            {
                return view('Empleados.edit',compact('varpantallas','varsubmenus','varpuestos','varsucursales','varciudades','varempresas','varbancos','obtenerempleado','vartipodescinfo','vardias','varhorarios','horariosEmpleado'));
            }
            else{
                return redirect()->route('verempleados')->with('info_msg_large', 'No cuenta con permiso para editar empleados.');
            }
        } catch(\Throwable $ex){
            return $this->empleadosErrorResponse($ex, 'No se pudo abrir el formulario de edición del empleado');
        }
    }

    public function cambiar(request $request, $id){
        try{
            $date = Carbon::now();
            $fecha = $date->format('Y-m-d');

            $empleados = Empleados::find($id);
            $empleados->primer_nombre = $request->get('primer_nombre');
            if(!is_null($request->get('segundo_nombre'))){
                $empleados->segundo_nombre = $request->get('segundo_nombre');
                }else{$empleados->segundo_nombre =" ";}
            $empleados->apellido_paterno = $request->get('apellido_paterno');
            $empleados->apellido_materno = $request->get('apellido_materno');
            $empleados->telefono = $request->get('telefono');
            $empleados->correo = $request->get('correo');
            $empleados->idpuesto = $request->get('puesto');
            $empleados->tipo_contratacion = $request->get('tipo_contratacion');
            $empleados->fecha_determinado = $request->get('tipo_contratacion') === 'DEFINIDA'
                ? $request->get('fecha_determinado')
                : null;
            $empleados->grado_estudio= $request->get('grado_estudio');
            $empleados->nacionalidad = $request->get('nacionalidad');
            $empleados->idsucursal = $request->get('sucursal');
            $empleados->idciudad = $request->get('ciudad');
            $empleados->idbanco = $request->get('banco');
            $empleados->calle = $request->get('calle');
            $empleados->colonia = $request->get('colonia');
            if(!is_null($request->get('numero_interior'))){
                    $empleados->numero_interior = $request->get('numero_interior');
                }else{$empleados->numero_interior ="0";}
            $empleados->numero_exterior = $request->get('numero_exterior');
            $empleados->codigo_postal = $request->get('codigo_postal');
            $empleados->sexo = $request->get('sexo');
            $empleados->fecha_nacimiento = $request->get('fecha_nacimiento');
            $empleados->rfc = $request->get('rfc');
            $empleados->colonia_f = $request->get('colonia_f');
            $empleados->calle_f = $request->get('calle_f');
            $empleados->no_interior_f = $request->get('no_interior_f');
            $empleados->no_exterior_f = $request->get('no_exterior_f');
            $empleados->codigo_postal_f = $request->get('codigo_postal_f');

            $empleados->nss = $request->get('nss');
            $empleados->curp = $request->get('curp');
            $empleados->tipo_sangre = $request->get('tipo_sangre');
            $empleados->contacto_emergencia = $request->get('contacto_emergencias');
            $empleados->telefono_emergencia = $request->get('telefono_emergencia');
            $empleados->estado = 'A';
            $empleados->descripcion_estado = 'alta empleado';
            $empleados->estado_civil = $request->get('estado_civil');
               $empleados->tipo_tranferencia= $request->get('tipo_tranferencia');
            $empleados->fecha_ingreso = $request->get('fecha_alta');
            $empleados->estado_civil = $request->get('estado_civil');
            $empleados->updated_by = auth()->user()->name;
            $empleados->save();

            $idnomina = $request->get('idnomina');
            $nominas =  nominas::find($idnomina);
            $nominas->idempresa=$request->get('cmbempresas');
            $nominas->idbancos=$request->get('banco');
            $nominas->salario_bruto=$request->get('salario_bruto');
            $nominas->salario_fijo=$request->get('salario_fijo');
            $nominas->excedente=$request->get('excedente');
            // $nominas->efectivo=$request->get('efectivo');
            $nominas->idbanca=$request->get('idbanca');
            $nominas->numero_tarjeta=$request->get('numero_tarjeta');
            $nominas->numero_cuenta=$request->get('numero_cuenta');
            $nominas->id_tipoinfonavit = $request->get('tipo_infonavit');
            $nominas->factor_sua = $request->get('factor_sua');
            // $nominas->descuento_quincenal = $request->get('descuento_quincenal');
            $nominas->numero_credito_infonavit = $request->get('numero_credito_infonavit');
            $nominas->fecha_ingreso_imss = $request->get('fecha_ingreso_imss');
            $nominas->zona = $request->get('zona');
            $nominas->updated_by = auth()->user()->name;
            $nominas->save();
            $this->guardarHorariosEmpleado($request, (int) $id);

            if($request->hasFile("contrato") ){
                    $file_contrato=$request->file("contrato");

                    if($file_contrato->guessExtension()=="pdf"){
                        $Rutacarpeta = "DetallesEmpleados/contratos";
                        $nombre_contrato = "contrato_"."$id".".".$file_contrato->guessExtension();   
                        $ruta_contrato = public_path($Rutacarpeta."/".$nombre_contrato);
                        copy($file_contrato, $ruta_contrato);
            
            
                        $documento = Empleados::find($id);
                        $documento->ruta_contrato = $nombre_contrato;
                        $documento->status_contrato = 'A';
                        $documento->updated_by = auth()->user()->name;
                        $documento->save();
                    }else{
                        return back()->with('warning_msg_large', 'El contrato debe ser un archivo PDF.');
                    }
                
                }else{error_log('No tienes este archivo');}
        
            if($empleados->save() && $nominas->save()){
                return back()->with('success_msg_large', 'La información del empleado y su nómina se actualizaron correctamente.');
            }else{
                return back()->with('warning_msg_large', 'No se pudo actualizar la información del empleado.');
            }
        } catch(\Throwable $ex){
            return $this->empleadosErrorResponse($ex, 'Error al actualizar el empleado');
        }
    }

    public function contratoSubir(request $request, $id){
        try{
            if($request->hasFile("contrato") ){
                
                $file_contrato=$request->file("contrato");
                if($file_contrato->guessExtension()=="pdf"){
                        $Rutacarpeta = "DetallesEmpleados/contratos";
                        $nombre_contrato = "contrato_"."$id".".".$file_contrato->guessExtension();   
                        $ruta_contrato = public_path($Rutacarpeta."/".$nombre_contrato);
                        copy($file_contrato, $ruta_contrato);


                        $documento = Empleados::find($id);
                        $documento->ruta_contrato = $nombre_contrato;
                        $documento->status_contrato = 'A';
                        $documento->updated_by = auth()->user()->name;
                        $documento->save();
                    
                        return back()->with('success_msg_large', 'El contrato del empleado se cargó correctamente.');
                }else{
                    return back()->with('warning_msg_large', 'El archivo de contrato debe ser un documento PDF.');
                }
            }else{
                return back()->with('warning_msg_large', 'Debe seleccionar un archivo de contrato en formato PDF.');
            }
        } catch(\Throwable $ex){
            return $this->empleadosErrorResponse($ex, 'Error al subir el contrato del empleado');
        }
    }

    public function fotoPerfil(int $id, Request $request){
        try{
            if($request->hasFile("foto") ){
                $file_foto=$request->file("foto");
                $RutacarpetaFoto = "Images/Perfil";
                $nombre_foto = "$id".".".$file_foto->guessExtension();   
                $ruta_foto = public_path($RutacarpetaFoto."/".$nombre_foto);
                copy($file_foto, $ruta_foto);

                $documento = Empleados::find($id);
                $documento->nombre_foto = $nombre_foto;
                $documento->foto = '1';
                $documento->updated_by = auth()->user()->name;
                $documento->save();
                return back()->with('success_msg_large', 'La foto de perfil se actualizó correctamente.');
            
            }else{return back()->with('warning_msg_large', 'No se seleccionó ninguna imagen para cargar.');}
        } catch(\Throwable $ex){
            return $this->empleadosErrorResponse($ex, 'Error al actualizar la foto de perfil');
        }
    }

    public function bajas(Request $request){
        try{
            $date = Carbon::now();
            $idempleado = $request->get('ids');
            $descripcionbaja=$request->get('descripcion_baja');
            $fecha = $date->format('Y-m-d');
            $empleadosbaja = new empleados_bajas();
            $empleadosbaja->idempleado=$idempleado;
            $empleadosbaja->tipo_baja=$request->get('tipo_baja');
            $empleadosbaja->descripcion=$descripcionbaja;
            $empleadosbaja->fecha_baja=$request->get('fecha_baja');
            $empleadosbaja->dias_gratificacion=$request->get('diasgratificacion');
            $empleadosbaja->dias_aguinaldo=$request->get('dias_trabajados');
            $empleadosbaja->dias_sueldo_a_deber=$request->get('dias_trabajadosa_deber');
            $empleadosbaja->dias_vacaciones=$request->get('dias_vacaciones');
            $empleadosbaja->cantidad_gratificacion=$request->get('gratificacion');
            $empleadosbaja->cantidad_aguinaldo=$request->get('Aguinaldo_poporcional');
            $empleadosbaja->cantidad_sueldo=$request->get('sueldo_poporcional');
            $empleadosbaja->cantidad_vacaciones=$request->get('vacaciones_poporcionales');
            $empleadosbaja->cantidaddeduccion_imms=$request->get('imms');
            $empleadosbaja->cantidaddeduccion_infonavit=$request->get('infonavit');
            $empleadosbaja->cantidaddeduccion_transporte=$request->get('transporte');
            $empleadosbaja->cantidaddeduccion_prestamo=$request->get('prestamo');
            $empleadosbaja->cantidaddeduccion_otros=$request->get('otrasd');
            $empleadosbaja->cantidadtotal_entregada=$request->get('total_entregar');
            $empleadosbaja->save();


            $empleado = empleados::find($idempleado);
            $empleado->estado = 'I';
            $empleado->descripcion_estado = $descripcionbaja;
            $empleado->save();

            $pass = bcrypt("  ");

            $update1 =  DB::select('update users set estado_user = "I",password = ?  where idempleado  = ?;', [$pass,$idempleado]);

            if($empleadosbaja->save()){
                return redirect()->route('verempleados')->with('success_msg_large', 'La baja del empleado se registró correctamente.');
            }else{
                return redirect()->route('verempleados')->with('warning_msg_large', 'No se pudo completar el registro de baja del empleado.');
            }
        } catch(\Throwable $ex){
            return $this->empleadosErrorResponse($ex, 'Error al registrar la baja del empleado');
        }
    }
    
    public function empleadoBaja(int $id){
        try{
            $idusuario=auth()->user()->id;
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();
            $listaempleadoid =  $this->obtenerlistaempleadoid($id);
            $debenomina=$this-> obtenerdebeprestamo($id);

            $fecha_baja = "";
            $descripcion_baja = "";
            $tipo_baja = "";
            
            //datos siemples
            foreach($listaempleadoid as $datoemp){
                $nombre = $datoemp->Nombre;
                $puesto = $datoemp->puesto;
                $salario = $datoemp->salario_fijo + $datoemp->excedente + $datoemp->efectivo;
                $salario_mensual = $datoemp->salario_bruto;
                $fecha_ingreso = $datoemp->fecha_ingreso;
                $nombre_empresa = $datoemp->nombre_empresa;
                $tipo_infonavit = $datoemp->nombreinfonavit;
                $factor_sua = $datoemp->factor_sua;
            }

            //percepciones
            $total_dias_trabajados = 0;
            $dias_trabajados_año = 0;
            $dias_trabajados_quin = 0;
            $sueldo_proporcional = 0;
            $dias_gratificacion = 0;
            $total_gratificacion = 0;
            $dias_vacaciones_no_tomadas = 0;
            $total_vacaciones_no_tomadas = 0;
            $aguinaldo_proporcional = 0;
            $prima_vacional = 0;
            $total_percepciones = 0;
            $check = 0;
            $check2 = 0;
            $check3 = 0;

            //deducciones
            $deduccion_imms = 0;
            $total_infonavit = 0;
            $transporte = 0;
            if($debenomina->isEmpty()){ $prestamo = 0;
            }else{ foreach($debenomina as $montop){  $prestamo = $montop->monto_debe;}}  
            $otros = 0;
            $total_deducciones = 0;
            $total = 0;

            return view('Empleados.baja_emp',compact('varpantallas','varsubmenus','listaempleadoid','check','check2','check3'
                ,'id','fecha_baja','descripcion_baja','tipo_baja','dias_vacaciones_no_tomadas','total_vacaciones_no_tomadas'
                ,'nombre','puesto','salario','salario_mensual','fecha_ingreso','tipo_infonavit' ,'nombre_empresa'
                ,'total_dias_trabajados','dias_trabajados_año','dias_trabajados_quin','sueldo_proporcional','dias_gratificacion','total_gratificacion' ,'aguinaldo_proporcional','prima_vacional','total_percepciones'
                ,'deduccion_imms','factor_sua','total_infonavit','transporte','prestamo','otros','total_deducciones','total'

            ));
            
        } catch(\Throwable $ex){
            return $this->empleadosErrorResponse($ex, 'No se pudo cargar el formulario de baja del empleado');
        }
    }

    public function calcular_baja(Request $request){
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();
            $id = $request->get('id');
            $listaempleadoid =  $this->obtenerlistaempleadoid($id);
            
            
            //datos siemples
            $nombre = $request->get('nombre');
            $puesto = $request->get('puesto');
            $salario = $request->get('salario');
            $salario_mensual = $request->get('salario_mensual');
            $tipo_infonavit = $request->get('tipo_infonavit');
            $nombre_empresa = $request->get('nombre_empresa');


            //percepciones
            $total_dias_trabajados = $request->get('total_dias_trabajados');
            $dias_trabajados_año = $request->get('dias_trabajados_año');
            $dias_trabajados_quin = $request->get('dias_trabajados_quin');
            $sueldo_proporcional = $request->get('sueldo_proporcional');
            $dias_gratificacion = $request->get('dias_gratificacion');
            $total_gratificacion = $request->get('total_gratificacion');
            $dias_vacaciones_no_tomadas = $request->get('dias_vacaciones_no_tomadas');
            $total_vacaciones_no_tomadas = $request->get('total_vacaciones_no_tomadas');
            $aguinaldo_proporcional = $request->get('aguinaldo_proporcional');
            $prima_vacional = $request->get('prima_vacional');
            $total_percepciones = $request->get('total_percepciones');

            //deducciones
            $deduccion_imms = $request->get('deduccion_imms');
            $factor_sua = $request->get('factor_sua');
            $total_infonavit = $request->get('total_infonavit');
            $transporte = $request->get('transporte');
            $prestamo = $request->get('prestamo'); 
            $otros = $request->get('otros');
            $total_deducciones = $request->get('total_deducciones');
            $total = $request->get('total');


            //calculos
            $fecha_baja = Carbon::parse($request->get('fecha_baja'));
            $fecha_ingreso = Carbon::parse($request->get('fecha_ingreso'));

            $añoactual = $fecha_baja->format('Y');
            $mes = $fecha_baja->format('m');
            $diahoy =  $fecha_baja->format('d');
  
            $añoingreso = $fecha_ingreso->format('Y');
            $mesingreso = $fecha_ingreso->format('m');
            $diaingreso =  $fecha_ingreso->format('d');

  
            if ($añoingreso == $añoactual){
                $fechaIni = Carbon::parse($añoingreso.'-'.$mesingreso.'-'.$diaingreso);
            }else{
                $fechaIni = Carbon::parse($añoactual.'-01-01');
            }
            $dias_trabajados_año = $fecha_baja->diffInDays($fechaIni);

            //calculo para dias trabajados de quincena
            if($diahoy >= 16 &&  $diahoy <= 31){
                if($mes == 2 && $diahoy == 28){
                    $dias_trabajados_quin = 15;
                }
                if($diahoy == 31 || $diahoy == 30){
                    $dias_trabajados_quin = 15;
                }else{
                    $fecha_ini_sueldopropo = Carbon::parse($añoactual.'-'.$mes.'-16');
                    $dias_trabajados_quin = $fecha_baja->diffInDays($fecha_ini_sueldopropo) + 1;
                }
            }
            if($diahoy >= 1 && $diahoy <= 15){
                $fecha_ini_sueldopropo = Carbon::parse($añoactual.'-'.$mes.'-01');
                $dias_trabajados_quin = $fecha_baja->diffInDays($fecha_ini_sueldopropo) + 1;
            }

            //VACACIONES
            $total_dias_trabajados = $fecha_baja->diffInDays($fecha_ingreso);
            $diasdevacaciones = 0;

           

            if($total_dias_trabajados  >= 1 && $total_dias_trabajados < 365){
                $diasdevacaciones = number_format(((12/365)*$total_dias_trabajados), 2);
            }else{
                $diferencia = $fecha_baja->diffInYears($fecha_ingreso);
                $selecciona_tarifas_integracion = $this->selecciona_tarifas_integracion($diferencia);
                foreach($selecciona_tarifas_integracion  as $key){
                    $diasdevacaciones = $key->dias_vacaciones;
                }
            }

            //VACACIONES NO TOMADAS

            if($dias_vacaciones_no_tomadas > 0){
                $total_vacaciones_no_tomadas = $salario * $dias_vacaciones_no_tomadas;
            }else{
                $total_vacaciones_no_tomadas = 0;
            }

            //en este apartado vamos a calcular lo que debe de infonavit segun su credito
             $total_infonavit = 0;
            if($factor_sua <= 0);
            {
            $total_infonavit = 0;
            }
            if($tipo_infonavit == 'CF' && $factor_sua > 0)
            {
                $total_infonavit = (($factor_sua / 30)*$dias_trabajados_quin);
                //$total_infonavit = ($factor_sua*2)/61*($dias_trabajados_quin)+15;
            }
            if($tipo_infonavit == 'VSM' && $factor_sua > 0)
            {
                $total_infonavit = ($factor_sua*103.74*2)/61*($dias_trabajados_quin)+15;
            }
            if($tipo_infonavit == 'N/A')
            {
                $total_infonavit = 0;
            }
            $total_infonavit =  bcdiv($total_infonavit, '1', 2);

            //gratificacion total
            $total_gratificacion = bcdiv($dias_gratificacion*$salario, '1', 2);

            //calculo para dias trabajados de agunaldo
            if($request->get("no_aguinaldo") == 1){
                $dias_trabajados_año = 0;
                $check3 = 1;
            }else{
                $check3 = 0;
            }

            $aguinaldo_proporcional = bcdiv(15/365*$dias_trabajados_año*$salario, '1', 2);
            
            //esto para sacar prima vacacional
            // $diasvacaciones = bcdiv((($diasdevacaciones/365)*$dias_trabajados_año)*$salario, '1', 2);
            
            // $diasvacaciones = bcdiv((($diasdevacaciones/365)*$total_dias_trabajados)*$salario, '1', 2);
            
            if($request->get("no_diasvc") == 1){
                $diasdevacaciones = 0;
                $check2 = 1;
            }else{
                $check2 = 0;
            }

            
            $prima_vacional = bcdiv((($salario * $diasdevacaciones)*.25), '1', 2);
            // return $salario." ".$diasdevacaciones;
        
            //total sueldo proporcional
            if($request->get("no_diastr") == 1){
                $check = 1;
                $dias_trabajados_quin = 0;
            }else{
                $check = 0;
            }

            $sueldo_proporcional = bcdiv($salario*$dias_trabajados_quin, '1', 2);

            $total_percepciones = bcdiv($aguinaldo_proporcional+$total_gratificacion+$sueldo_proporcional+$prima_vacional+$total_vacaciones_no_tomadas, '1', 2);

            //falta agregar las demas deducciones
            $total_deducciones = bcdiv($deduccion_imms + $total_infonavit + $transporte + $prestamo + $otros, '1', 2);
            $total =  bcdiv($total_percepciones-$total_deducciones, '1', 2);
            //sumamos el total de dinero a otorgar al trabajador

            $descripcion_baja = $request->get('descripcion_baja');
            $fecha_baja = $request->get('fecha_baja');
            $fecha_ingreso = $request->get('fecha_ingreso');
            $tipo_baja=$request->get('tipo_baja');
            $guardar = $request->get('guardar');

            if ($guardar == 1){
                if(!is_null($descripcion_baja) && !is_null($fecha_baja) && !is_null($tipo_baja)){

                    if($tipo_baja == "no_aplica"){
                        $empleadosbaja = new empleados_bajas();
                        $empleadosbaja->idempleado=$id;
                        $empleadosbaja->tipo_baja=$tipo_baja;
                        $empleadosbaja->descripcion=$descripcion_baja;
                        $empleadosbaja->fecha_baja=$fecha_baja;
                        $empleadosbaja->dias_trabajados=$total_dias_trabajados;
                        $empleadosbaja->dias_gratificacion=0;
                        $empleadosbaja->cantidad_gratificacion=0;
                        $empleadosbaja->dias_aguinaldo=0;
                        $empleadosbaja->cantidad_aguinaldo=0;
                        $empleadosbaja->dias_sueldo_a_deber=0;
                        $empleadosbaja->dias_vacaciones=0;
                        $empleadosbaja->cantidad_vacaciones=0;
                        $empleadosbaja->dias_vacaciones_no_tomadas=0;
                        $empleadosbaja->total_vacaciones_no_tomadas =0;
                        $empleadosbaja->cantidad_sueldo=0;
                        $empleadosbaja->cantidaddeduccion_imms=0;
                        $empleadosbaja->cantidaddeduccion_infonavit=0;
                        $empleadosbaja->cantidaddeduccion_transporte=0;
                        $empleadosbaja->cantidaddeduccion_prestamo=0;
                        $empleadosbaja->cantidaddeduccion_otros=0;
                        $empleadosbaja->cantidadtotal_entregada=0;
                        $empleadosbaja->created_by=auth()->user()->name;
                        $empleadosbaja->save();
                    

                    }else{

                        $empleadosbaja = new empleados_bajas();
                        $empleadosbaja->idempleado=$id;
                        $empleadosbaja->tipo_baja=$tipo_baja;
                        $empleadosbaja->descripcion=$descripcion_baja;
                        $empleadosbaja->fecha_baja=$fecha_baja;
                        $empleadosbaja->dias_gratificacion=$request->get('dias_gratificacion');
                        $empleadosbaja->cantidad_gratificacion=$total_gratificacion;
                        $empleadosbaja->dias_trabajados=$total_dias_trabajados;
                        $empleadosbaja->dias_aguinaldo=$dias_trabajados_año;
                        $empleadosbaja->cantidad_aguinaldo=$aguinaldo_proporcional;
                        $empleadosbaja->dias_sueldo_a_deber=$dias_trabajados_quin;
                        $empleadosbaja->dias_vacaciones=$diasdevacaciones;
                        $empleadosbaja->cantidad_vacaciones=$prima_vacional;
                        $empleadosbaja->dias_vacaciones_no_tomadas=$dias_vacaciones_no_tomadas;
                        $empleadosbaja->total_vacaciones_no_tomadas =$total_vacaciones_no_tomadas;
                        $empleadosbaja->cantidad_sueldo=$sueldo_proporcional;
                        $empleadosbaja->cantidaddeduccion_imms=$deduccion_imms;
                        $empleadosbaja->cantidaddeduccion_infonavit=$total_infonavit;
                        $empleadosbaja->cantidaddeduccion_transporte=0;
                        $empleadosbaja->cantidaddeduccion_prestamo=$prestamo;
                        $empleadosbaja->cantidaddeduccion_otros=$otros;
                        $empleadosbaja->cantidadtotal_entregada=$total;
                        $empleadosbaja->created_by=auth()->user()->name;
                        $empleadosbaja->save();
                    }
                    
                    $consulta = DB::select('update users set estado_user = "I"  where idempleado = ?;', [$id]);


                    $empleado = empleados::find($id);
                    $empleado->estado = 'I';
                    $empleado->descripcion_estado = $request->get('descripcion_baja');
                    $empleado->save();

                    if($empleadosbaja->save()){
                        return redirect()->route('verempleados')->with('success_msg_large', 'La baja del empleado se procesó y registró correctamente.');
                    }else{
                        return redirect()->route('verempleados')->with('warning_msg_large', 'No se pudo guardar el finiquito de baja del empleado.');
                    }
                }else{
                    return back()->with('warning_msg_large', 'Complete la descripción, fecha y tipo de baja antes de guardar.');
                }
            }else{
                // return $diasdevacaciones." ".$total_dias_trabajados." ".$salario." ".$diasvacaciones;
                return view('Empleados.baja_emp',compact('varpantallas','varsubmenus','listaempleadoid','check','check2','check3'
                    ,'id','fecha_baja','descripcion_baja','tipo_baja','dias_vacaciones_no_tomadas','total_vacaciones_no_tomadas'
                    ,'nombre','puesto','salario','salario_mensual','fecha_ingreso','tipo_infonavit' ,'nombre_empresa'
                    ,'total_dias_trabajados','dias_trabajados_año','dias_trabajados_quin','dias_gratificacion'
                    ,'sueldo_proporcional','total_gratificacion' ,'aguinaldo_proporcional','prima_vacional','total_percepciones'
                    ,'deduccion_imms','factor_sua','total_infonavit','transporte','prestamo','otros','total_deducciones','total'

                ));
            }
    }
   
    public function getdownloadBaja(int $idemp){
        $obtenerbajas = $this->obtenerbajas_empleados($idemp);
        foreach($obtenerbajas as $baja){
            $fecha_baja = Carbon::parse($baja->fecha_baja);
            $datenow = Carbon::parse($baja->fecha_baja)->locale('es')->isoFormat('dddd D \d\e MMMM \d\e\l Y');// lunes 28 de agosto del 2023
            $idempleado= $baja->idempleado;
            $nombreemplado=$baja->NombreEmpleado;
            $nombreempresa=$baja->empresa;
            $puesto = $baja->id;
            $tipo_baja = $baja->tipo_baja;
            $fecha_baja = $baja->fecha_baja;
            $fecha_ingreso=$baja->fecha_ingreso;
            $salario = $baja->salarioDiario + $baja->excedente + $baja->efectivo;
            $puesto = $baja->puesto;
            $dias_gratificacion =  $baja->dias_gratificacion;
            $dias_vacaciones=  $baja->dias_vacaciones;
            $dias_aguinaldo =  $baja->dias_aguinaldo;
            $rptvardiasgratificacion =  $baja->cantidad_gratificacion;
            $rtpvaraguinaldoporporcional =$baja->cantidad_aguinaldo;
            $rptvarsueldoporporcional =$baja->cantidad_sueldo;
            $rptvarvacacionesporporcionales = $baja->cantidad_vacaciones;
            $dias_vacaciones_no_tomadas = $baja->dias_vacaciones_no_tomadas;
            $total_vacaciones_no_tomadas = $baja->total_vacaciones_no_tomadas;
            $rptvardeudaimms =$baja->cantidaddeduccion_imms;
            $rptvardeduedainfonavit = $baja->cantidaddeduccion_infonavit;
            $rptvardeudatransporte = $baja->cantidaddeduccion_transporte;
            $rptvardeudaprestamo =$baja->cantidaddeduccion_prestamo;
            $rptvarotrasdeudas = $baja->cantidaddeduccion_otros;
            $totalper = $rptvardiasgratificacion + $rtpvaraguinaldoporporcional + $rptvarsueldoporporcional + $rptvarvacacionesporporcionales+$total_vacaciones_no_tomadas;
            $rptotaldeducciones = $rptvardeudaimms + $rptvardeduedainfonavit + $rptvardeudatransporte + $rptvardeudaprestamo + $rptvarotrasdeudas;
            $rpttotalentregar = $baja->cantidadtotal_entregada;
            $rptfechaactual = $baja->fecha_baja;

            if($baja->dias_trabajados == 0){
                $fechaEmision = Carbon::parse($fecha_ingreso);
                $fechaExpiracion = Carbon::parse($fecha_baja);
                $diasDiferencia = $fechaExpiracion->diffInDays($fechaEmision);

                $empleado = empleados_bajas::find($baja->id);
                $empleado->dias_trabajados = $diasDiferencia;
                $empleado->save();
            }else{
                $diasDiferencia = $baja->dias_trabajados;
            }
       }

       $varempresas = $this->razonSocialxNombre($nombreempresa);

         foreach($varempresas as $empresa){
          $icono = $empresa->icono;
          $marca_agua = $empresa->marca_agua;
          $empresa = $empresa->razon_social;
        }
    
        $pdf = \PDF::setPaper('letter')->loadView('Empleados.PDF.rptfiniquito',compact('idempleado','nombreemplado','diasDiferencia','datenow',
        'empresa','fecha_baja','fecha_ingreso','salario','puesto','rpttotalentregar','totalper','rptotaldeducciones','rptvardiasgratificacion',
        'rtpvaraguinaldoporporcional','rptvarsueldoporporcional','rptvarvacacionesporporcionales','rptvardeudaimms','rptvardeduedainfonavit',
        'dias_vacaciones_no_tomadas','total_vacaciones_no_tomadas','dias_gratificacion','dias_aguinaldo','dias_vacaciones','tipo_baja',
        'rptvardeudatransporte','rptvardeudaprestamo','rptvarotrasdeudas','icono','marca_agua'));
          return $pdf->stream("BAJA EMPLEADO ".$nombreemplado.".pdf");
    }

    public function editarBaja($id){
        try{
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();
            $date = Carbon::now();
            $date = $date->format('Y-m-d');
            $obtenerbajas = $this->obtenerbajas_empleados($id);
            $permisos = $this->forpermisos('editar_baja_empleados');

            if($permisos=="editar_baja_empleados"){
              return view('Empleados.editBaja',compact('varpantallas','varsubmenus','obtenerbajas'));
            }else{
              return redirect()->route('verempleados')->with('info_msg_large', 'No cuenta con permiso para editar la baja del empleado.');
            }
        } catch(\Throwable $ex){
            return $this->empleadosErrorResponse($ex, 'No se pudo abrir la edición de la baja del empleado');
        }
    }

    public function BajaEmpleadoEdit(Request $request){
        try{
            $date = Carbon::now();
            $idempleado = $request->get('ids');
            $id = $request->get('id');
            $descripcionbaja=$request->get('descripcion_baja');
            $fecha = $date->format('Y-m-d');
            $empleadosbaja = empleados_bajas::find($id);
            $empleadosbaja->idempleado=$idempleado;
            $empleadosbaja->tipo_baja=$request->get('tipo_baja');
            $empleadosbaja->descripcion=$descripcionbaja;
            $empleadosbaja->fecha_baja=$request->get('fecha_baja');
            $empleadosbaja->dias_gratificacion=$request->get('diasgratificacion');
            $empleadosbaja->dias_aguinaldo=$request->get('dias_trabajados');
            $empleadosbaja->dias_sueldo_a_deber=$request->get('dias_trabajadosa_deber');
            $empleadosbaja->dias_vacaciones=$request->get('dias_vacaciones');
            $empleadosbaja->cantidad_gratificacion=$request->get('gratificacion');
            $empleadosbaja->cantidad_aguinaldo=$request->get('Aguinaldo_poporcional');
            $empleadosbaja->cantidad_sueldo=$request->get('sueldo_poporcional');
            $empleadosbaja->cantidad_vacaciones=$request->get('vacaciones_poporcionales');

            $empleadosbaja->cantidaddeduccion_imms=$request->get('imms');
            $empleadosbaja->cantidaddeduccion_infonavit=$request->get('infonavit');
            $empleadosbaja->cantidaddeduccion_transporte=$request->get('transporte');
            $empleadosbaja->cantidaddeduccion_prestamo=$request->get('prestamo');
            $empleadosbaja->cantidaddeduccion_otros=$request->get('otras');
            $empleadosbaja->cantidadtotal_entregada=$request->get('total_entregar');
            $empleadosbaja->updated_at = $fecha;
            $empleadosbaja->save();

            $empleado = empleados::find($idempleado);
            $empleado->descripcion_estado = $descripcionbaja;
            $empleado->save();
        
            $datenow = $date->format('l jS \\of F Y');
            $idempleado = $request->get('ids');
            $nombreemplado=$request->get('nombre');
            $nombreempresa=$request->get('empresa');
            $puesto=$request->get('ids');
            $fecha_baja=$request->get('fecha_baja');
            $fecha_ingreso=$request->get('fecha_ingresoe');
            $salario=$request->get('salario');
            $puesto=$request->get('t_puesto');
            $rptvardiasgratificacion=  $request->get('gratificacion');
            $rtpvaraguinaldoporporcional =$request->get('Aguinaldo_poporcional');
            $rptvarsueldoporporcional =$request->get('sueldo_poporcional');
            $rptvarvacacionesporporcionales = $request->get('vacaciones_poporcionales');
            $rptvardeudaimms =$request->get('imms');
            $rptvardeduedainfonavit = $request->get('infonavit');
            $rptvardeudatransporte = $request->get('transporte');
            $rptvardeudaprestamo =$request->get('prestamo');
            $rptvarotrasdeudas = $request->get('otras');
            $totalper =$request->get('total_p');
            $rptotaldeducciones =$request->get('total_d');
            $rpttotalentregar =$request->get('total_entregar');
            $rptfechaactual=$request->get('fecha_baja');


            $fechaEmision = Carbon::parse($fecha_ingreso);
            $fechaExpiracion = Carbon::parse($fecha_baja);
            $diasDiferencia = $fechaExpiracion->diffInDays($fechaEmision);


            $pdf = \PDF::loadView('Empleados.rptfiniquito',compact('idempleado','nombreemplado','diasDiferencia','datenow','nombreempresa','fecha_baja','fecha_ingreso','salario','puesto','rpttotalentregar','totalper','rptotaldeducciones','rptvardiasgratificacion','rtpvaraguinaldoporporcional','rptvarsueldoporporcional','rptvarvacacionesporporcionales','rptvardeudaimms','rptvardeduedainfonavit','rptvardeudatransporte','rptvardeudaprestamo','rptvarotrasdeudas',));
            return $pdf->download("FINIQUITO EMPLEADO_#".$idempleado.".pdf");
        } catch(\Throwable $ex){
            return $this->empleadosErrorResponse($ex, 'Error al generar el finiquito de baja');
        }
    }

    public function vistaReactivar($id){
        try{
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();
            $varpuestos = $this->obtenerpuestos();
            $varsucursales = $this->obtenersucursales();
            $varciudades =  $this->obtenerciudades();
            $varempresas = $this->obtenerempresas();
            $varbancos = $this->obtenerbancos();
            $varlistaempleados=  $this-> obtenerlistaempleados();
            $vartipodescinfo= $this->obtenertipodescinfonavit();
            $date = Carbon::now();
            $date = $date->format('Y-m-d');
            $obtenerempleado = $this->obtenerlistaempleadoid($id);

            return view('Empleados.reactivar',compact('varpantallas','varsubmenus','varlistaempleados','varpuestos','varsucursales','varciudades','varempresas','varbancos','obtenerempleado','vartipodescinfo'));
        } catch(\Throwable $ex){
            return $this->empleadosErrorResponse($ex, 'No se pudo cargar el formulario de reactivación del empleado');
        }
    }

    public function traerVistaReactivar($id){
        try{
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();
            $varpuestos = $this->obtenerpuestos();
            $varsucursales = $this->obtenersucursales();
            $varciudades =  $this->obtenerciudades();
            $varempresas = $this->obtenerempresas();
            $varbancos = $this->obtenerbancos();
            $varlistaempleados=  $this-> obtenerlistaempleados();
            $vartipodescinfo= $this->obtenertipodescinfonavit();
            $date = Carbon::now();
            $date = $date->format('Y-m-d');
            $obtenerempleado = $this->obtenerlistaempleadoid($id);

            return view('Empleados.reactivar',compact('varpantallas','varsubmenus',
            'varlistaempleados','varpuestos','varsucursales','varciudades',
            'varempresas','varbancos','obtenerempleado','vartipodescinfo')); 

        } catch(\Throwable $ex){
            return $this->empleadosErrorResponse($ex, 'No se pudo cargar la vista de reactivación del empleado');
        }
    }
    
    public function reactivar($id,request $request){
        try{
            $date = Carbon::now();
            $fecha = $date->format('Y-m-d');
            
            $empleados = Empleados::find($id);
            $empleados->estado = 'A';
            $empleados->descripcion_estado = 'Reingreso de empleado';
            $empleados->updated_at=$fecha;
            $empleados->fecha_ingreso = $request->get('fecha_alta');
            $empleados->archivo_baja = 0;
            $empleados->save();
            
            $consulta = DB::select('update users set estado_user = "A"  where idempleado = ?;', [$id]);

            $idnomina = $request->get('idnomina');
            $nominas =  nominas::find($idnomina);
            $nominas->idempresa=$request->get('cmbempresas');
            $nominas->salario_bruto=$request->get('salario_bruto');
            $nominas->salario_fijo=$request->get('salario_fijo');
            $nominas->fecha_ingreso_imss = $request->get('fecha_ingreso_imss');
            $nominas->excedente=$request->get('excedente');
            $nominas->id_tipoinfonavit = $request->get('tipo_infonavit');
            $nominas->factor_sua = $request->get('factor_sua');
            $nominas->descuento_quincenal = $request->get('descuento_quincenal');
            $nominas->numero_credito_infonavit = $request->get('numero_credito_infonavit');
            $nominas->save();
           
            if($empleados->save() && $nominas->save()){
                return redirect()->route('verempleados')->with('success_msg_large', 'El empleado fue reactivado correctamente.');
            }else{
                return redirect()->route('verempleados')->with('warning_msg_large', 'No se pudo reactivar al empleado. Intente nuevamente.');
            }
        } catch(\Throwable $ex){
            return $this->empleadosErrorResponse($ex, 'Error al reactivar el empleado');
        }
    
    }

    public function exportar_excel(){
        return Excel::download(new UsersExport, 'CATALOGO EMPLEADOS.xlsx');
    }

    /**
     * Última incapacidad por empleado para prellenar exportación SUA movimientos.
     *
     * @param  array<int|string>  $idsEmpleados
     * @return array<int, object>
     */
    /**
     * Arma una colección de registros SUA (una línea por incapacidad).
     * Por defecto exporta todas las incapacidades de los empleados seleccionados;
     * los campos del modal sobrescriben la incapacidad más reciente de cada empleado.
     */
    private function construirRegistrosIncapacidadesSua($empleados, array $validated)
    {
        $ids = $empleados->pluck('idempleado')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $porEmpleado = $empleados->keyBy(fn ($e) => (int) $e->idempleado);

        $exportarTodas = (bool) ($validated['exportar_todas_incapacidades'] ?? true);

        $query = DB::table('tblincapacidades')
            ->whereIn('id_empleado', $ids)
            ->orderBy('fecha_inicio')
            ->orderBy('id');

        $rows = $query->get([
            'id',
            'id_empleado',
            'folio',
            'fecha_incidencia',
            'fecha_inicio',
            'fecha_fin',
            'tipo',
            'ramo_seguro',
        ]);

        if ($rows->isEmpty()) {
            return collect();
        }

        $latestIdPorEmpleado = [];
        foreach ($rows->sortByDesc('id') as $row) {
            $idEmp = (int) $row->id_empleado;
            if (!isset($latestIdPorEmpleado[$idEmp])) {
                $latestIdPorEmpleado[$idEmp] = (int) $row->id;
            }
        }

        $fechasIni = $validated['fecha_inicio_incapacidad'] ?? [];
        $fechasFin = $validated['fecha_termino_incapacidad'] ?? [];
        $folios = $validated['folio_incapacidad_datos'] ?? [];
        $dias = $validated['dias_subsidiados'] ?? [];
        $porcentajes = $validated['porcentaje_incapacidad'] ?? [];
        $ramas = $validated['rama_incapacidad'] ?? [];
        $riesgos = $validated['tipo_riesgo'] ?? [];
        $secuelas = $validated['secuela_incapacidad'] ?? [];
        $controles = $validated['control_incapacidad'] ?? [];

        $registros = collect();
        foreach ($rows as $row) {
            $idEmp = (int) $row->id_empleado;
            $empleado = $porEmpleado->get($idEmp);
            if (!$empleado) {
                continue;
            }

            $esLatest = ((int) $row->id) === ($latestIdPorEmpleado[$idEmp] ?? null);
            if (!$exportarTodas && !$esLatest) {
                continue;
            }

            $diasCalc = 0;
            try {
                if (!empty($row->fecha_inicio) && !empty($row->fecha_fin)) {
                    $diasCalc = Carbon::parse($row->fecha_inicio)->diffInDays(Carbon::parse($row->fecha_fin)) + 1;
                }
            } catch (\Throwable $e) {
                $diasCalc = 0;
            }

            $ramaDefault = SuaExportService::mapRamaIncapacidad((string) ($row->ramo_seguro ?? ''));
            $controlDefault = SuaExportService::mapControlIncapacidad((string) ($row->tipo ?? ''), $ramaDefault);

            $registro = (object) [
                'idempleado' => $idEmp,
                'nss' => $empleado->nss,
                'registro_patronal_imss' => $empleado->registro_patronal_imss,
                'nombre_empresa' => $empleado->nombre_empresa ?? '',
                'folio' => $row->folio,
                'fecha_inicio' => $row->fecha_inicio,
                'fecha_fin' => $row->fecha_fin,
                'tipo' => $row->tipo,
                'ramo_seguro' => $row->ramo_seguro,
                'dias_calculados' => max(0, min(999, (int) $diasCalc)),
                'tipo_incidencia_sua' => '1',
                'fecha_inicio_sua' => $row->fecha_inicio,
                'fecha_termino_sua' => $row->fecha_fin,
                'folio_incapacidad_sua' => $row->folio,
                'dias_subsidiados_sua' => max(0, min(999, (int) $diasCalc)),
                'porcentaje_incapacidad_sua' => 0,
                'rama_incapacidad_sua' => $ramaDefault,
                'tipo_riesgo_sua' => $ramaDefault === '1' ? '1' : '0',
                'secuela_sua' => $ramaDefault === '1' ? '1' : '0',
                'control_incapacidad_sua' => $controlDefault,
            ];

            if ($esLatest) {
                $registro->fecha_inicio_sua = $fechasIni[$idEmp] ?? $fechasIni[(string) $idEmp] ?? $registro->fecha_inicio_sua;
                $registro->fecha_termino_sua = $fechasFin[$idEmp] ?? $fechasFin[(string) $idEmp] ?? $registro->fecha_termino_sua;
                $registro->folio_incapacidad_sua = $folios[$idEmp] ?? $folios[(string) $idEmp] ?? $registro->folio_incapacidad_sua;
                $registro->dias_subsidiados_sua = $dias[$idEmp] ?? $dias[(string) $idEmp] ?? $registro->dias_subsidiados_sua;
                $registro->porcentaje_incapacidad_sua = $porcentajes[$idEmp] ?? $porcentajes[(string) $idEmp] ?? $registro->porcentaje_incapacidad_sua;
                $registro->rama_incapacidad_sua = $ramas[$idEmp] ?? $ramas[(string) $idEmp] ?? $registro->rama_incapacidad_sua;
                $registro->tipo_riesgo_sua = $riesgos[$idEmp] ?? $riesgos[(string) $idEmp] ?? $registro->tipo_riesgo_sua;
                $registro->secuela_sua = $secuelas[$idEmp] ?? $secuelas[(string) $idEmp] ?? $registro->secuela_sua;
                $registro->control_incapacidad_sua = $controles[$idEmp] ?? $controles[(string) $idEmp] ?? $registro->control_incapacidad_sua;
            }

            $registros->push($registro);
        }

        return $registros;
    }

    private function obtenerUltimasBajasSua(array $idsEmpleados): array
    {
        $ids = array_values(array_unique(array_map('intval', $idsEmpleados)));
        if ($ids === []) {
            return [];
        }

        $rows = DB::table('tblempleado_bajas')
            ->whereIn('idempleado', $ids)
            ->orderByDesc('fecha_baja')
            ->orderByDesc('id')
            ->get(['id', 'idempleado', 'fecha_baja', 'tipo_baja', 'descripcion']);

        $mapa = [];
        foreach ($rows as $row) {
            $id = (int) $row->idempleado;
            if (isset($mapa[$id])) {
                continue;
            }
            $fechaIso = '';
            try {
                if (!empty($row->fecha_baja) && $row->fecha_baja !== '0000-00-00') {
                    $fechaIso = Carbon::parse($row->fecha_baja)->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                $fechaIso = '';
            }
            $row->fecha_baja_iso = $fechaIso;
            $mapa[$id] = $row;
        }

        return $mapa;
    }

    private function obtenerUltimasIncapacidadesSua(array $idsEmpleados): array
    {
        $ids = array_values(array_unique(array_map('intval', $idsEmpleados)));
        if ($ids === []) {
            return [];
        }

        $rows = DB::table('tblincapacidades')
            ->whereIn('id_empleado', $ids)
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id')
            ->get([
                'id',
                'id_empleado',
                'folio',
                'fecha_incidencia',
                'fecha_inicio',
                'fecha_fin',
                'tipo',
                'ramo_seguro',
            ]);

        $mapa = [];
        foreach ($rows as $row) {
            $id = (int) $row->id_empleado;
            if (isset($mapa[$id])) {
                continue;
            }
            $dias = 0;
            $fechaInicioIso = '';
            try {
                if (!empty($row->fecha_inicio) && !empty($row->fecha_fin)) {
                    $dias = Carbon::parse($row->fecha_inicio)->diffInDays(Carbon::parse($row->fecha_fin)) + 1;
                }
                if (!empty($row->fecha_inicio) && $row->fecha_inicio !== '0000-00-00') {
                    $fechaInicioIso = Carbon::parse($row->fecha_inicio)->format('Y-m-d');
                } elseif (!empty($row->fecha_incidencia) && $row->fecha_incidencia !== '0000-00-00') {
                    $fechaInicioIso = Carbon::parse($row->fecha_incidencia)->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                $dias = 0;
            }
            $row->dias_calculados = max(0, min(999, (int) $dias));
            $row->fecha_inicio_iso = $fechaInicioIso;
            $row->rama_sua = SuaExportService::mapRamaIncapacidad((string) ($row->ramo_seguro ?? ''));
            $row->control_sua = SuaExportService::mapControlIncapacidad(
                (string) ($row->tipo ?? ''),
                $row->rama_sua
            );
            $mapa[$id] = $row;
        }

        return $mapa;
    }

    public function exportar_sua(Request $request, SuaExportService $suaExport)
    {
        $suaError = function (string $mensaje, int $status = 422) use ($request) {
            if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['message' => $mensaje], $status);
            }

            return back()->withInput()->with('error_msg_large', $mensaje);
        };

        try {
            $validated = $request->validate([
                'tipo_exportacion' => 'required|string|in:' . implode(',', array_keys(SuaExportService::TIPOS_DISPONIBLES)),
                'empleados' => 'required|array|min:1',
                'empleados.*' => 'integer|exists:tblempleados,id',
                'tipo_trabajador' => 'nullable|array',
                'tipo_trabajador.*' => 'nullable|string|in:1,2,3,4',
                'jornada' => 'nullable|array',
                'jornada.*' => 'nullable|string|in:0,1,2,3,4,5,6',
                'tipo_movimiento' => 'nullable|string|in:02,07,08,11,12',
                'tipo_movimiento_credito' => 'nullable|string|in:15,16,17,18,19,20',
                'fecha_movimiento' => 'nullable|array',
                'fecha_movimiento.*' => 'nullable|date',
                'fecha_movimiento_credito' => 'nullable|array',
                'fecha_movimiento_credito.*' => 'nullable|date',
                'folio_incapacidad' => 'nullable|array',
                'folio_incapacidad.*' => 'nullable|string|max:8',
                'dias_incidencia' => 'nullable|array',
                'dias_incidencia.*' => 'nullable|integer|min:0|max:99',
                'sdi_movimiento' => 'nullable|array',
                'sdi_movimiento.*' => 'nullable|numeric|min:0',
                'numero_credito' => 'nullable|array',
                'numero_credito.*' => 'nullable|string|max:10',
                'tipo_descuento' => 'nullable|array',
                'tipo_descuento.*' => 'nullable|string|in:1,2,3',
                'valor_descuento' => 'nullable|array',
                'valor_descuento.*' => 'nullable|numeric|min:0',
                'aplica_tabla' => 'nullable|array',
                'aplica_tabla.*' => 'nullable|string|in:S,N',
                'fecha_inicio_incapacidad' => 'nullable|array',
                'fecha_inicio_incapacidad.*' => 'nullable|date',
                'fecha_termino_incapacidad' => 'nullable|array',
                'fecha_termino_incapacidad.*' => 'nullable|date',
                'folio_incapacidad_datos' => 'nullable|array',
                'folio_incapacidad_datos.*' => 'nullable|string|max:8',
                'dias_subsidiados' => 'nullable|array',
                'dias_subsidiados.*' => 'nullable|integer|min:0|max:999',
                'porcentaje_incapacidad' => 'nullable|array',
                'porcentaje_incapacidad.*' => 'nullable|integer|min:0|max:100',
                'rama_incapacidad' => 'nullable|array',
                'rama_incapacidad.*' => 'nullable|string|in:1,2,3,4',
                'tipo_riesgo' => 'nullable|array',
                'tipo_riesgo.*' => 'nullable|string|in:0,1,2,3',
                'secuela_incapacidad' => 'nullable|array',
                'secuela_incapacidad.*' => 'nullable|string|in:0,1,2,3,4,5,6,7,8,9',
                'control_incapacidad' => 'nullable|array',
                'control_incapacidad.*' => 'nullable|string|in:0,1,2,3,4,5,6,7,8',
                'exportar_todas_incapacidades' => 'nullable|boolean',
            ], [
                'empleados.required' => 'Seleccione al menos un empleado para exportar.',
                'empleados.min' => 'Seleccione al menos un empleado para exportar.',
            ]);

            $tipo = $validated['tipo_exportacion'];
            if (!in_array($tipo, SuaExportService::TIPOS_IMPLEMENTADOS, true)) {
                return $suaError(
                    'El tipo de exportación "' . (SuaExportService::TIPOS_DISPONIBLES[$tipo] ?? $tipo) . '" aún no está disponible.'
                );
            }

            $empleados = Empleados::query()
                ->join('tblnominas', 'tblempleados.id', '=', 'tblnominas.idempleado')
                ->join('tblpuestos', 'tblempleados.idpuesto', '=', 'tblpuestos.id')
                ->join('tblempresas', 'tblempresas.id', '=', 'tblnominas.idempresa')
                ->join('tbltipoinfonavit', 'tbltipoinfonavit.id', '=', 'tblnominas.id_tipoinfonavit')
                ->leftJoin('tblciudades', 'tblciudades.id', '=', 'tblempleados.idciudad')
                ->leftJoin('tblestados', 'tblestados.id', '=', 'tblciudades.idestado')
                ->whereIn('tblempleados.id', $validated['empleados'])
                ->orderBy('tblempleados.apellido_paterno')
                ->orderBy('tblempleados.apellido_materno')
                ->orderBy('tblempleados.primer_nombre')
                ->select(
                    'tblempleados.id as idempleado',
                    'tblempleados.primer_nombre',
                    'tblempleados.segundo_nombre',
                    'tblempleados.apellido_paterno',
                    'tblempleados.apellido_materno',
                    'tblempleados.rfc',
                    'tblempleados.nss',
                    'tblempleados.curp',
                    'tblempleados.sexo',
                    'tblempleados.codigo_postal',
                    'tblempleados.fecha_nacimiento',
                    'tblempleados.fecha_ingreso',
                    'tblempleados.tipo_contratacion',
                    'tblnominas.fecha_ingreso_imss',
                    'tblnominas.salario_fijo',
                    'tblnominas.excedente',
                    'tblnominas.efectivo',
                    'tblnominas.factor_sua',
                    'tblnominas.descuento_quincenal',
                    'tblnominas.numero_credito_infonavit',
                    'tblnominas.id_tipoinfonavit',
                    'tbltipoinfonavit.Nombre as nombreinfonavit',
                    'tblpuestos.nombre as puesto',
                    'tblempresas.registro_patronal_imss',
                    'tblempresas.nombre_empresa',
                    'tblciudades.nombre as ciudad',
                    'tblestados.nombre as estado'
                )
                ->get();

            if ($empleados->isEmpty()) {
                return $suaError('No se encontraron empleados para exportar.');
            }

            $sinRegistro = $empleados->first(function ($empleado) {
                $rp = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) ($empleado->registro_patronal_imss ?? '')) ?? '');
                return strlen($rp) !== 11;
            });
            if ($sinRegistro) {
                $empresaNombre = trim((string) ($sinRegistro->nombre_empresa ?? 'la empresa'));
                return $suaError(
                    "Falta el Registro Patronal IMSS (11 caracteres) en la empresa {$empresaNombre}. "
                    . 'Ve a Catálogo → Empresas, edítala y guarda el registro (ejemplo: B2827500102).'
                );
            }

            if ($tipo === SuaExportService::TIPO_TRABAJADORES) {
                $tiposPorEmpleado = $validated['tipo_trabajador'] ?? [];
                $jornadasPorEmpleado = $validated['jornada'] ?? [];
                $jornadasSugeridas = $suaExport->sugerirJornadasPorEmpleados(
                    $empleados->pluck('idempleado')->all()
                );

                $empleados->transform(function ($empleado) use ($tiposPorEmpleado, $jornadasPorEmpleado, $jornadasSugeridas) {
                    $id = (int) $empleado->idempleado;
                    $empleado->tipo_trabajador_sua = (string) ($tiposPorEmpleado[$id] ?? $tiposPorEmpleado[(string) $id] ?? '2');
                    $empleado->jornada_sua = (string) (
                        $jornadasPorEmpleado[$id]
                        ?? $jornadasPorEmpleado[(string) $id]
                        ?? $jornadasSugeridas[$id]
                        ?? '0'
                    );
                    return $empleado;
                });
            }

            if ($tipo === SuaExportService::TIPO_MOVIMIENTOS) {
                $tipoMov = (string) ($validated['tipo_movimiento'] ?? '02');
                if (!array_key_exists($tipoMov, SuaExportService::TIPOS_MOVIMIENTO)) {
                    return $suaError('Seleccione un tipo de movimiento SUA válido.');
                }

                $fechas = $validated['fecha_movimiento'] ?? [];
                $folios = $validated['folio_incapacidad'] ?? [];
                $dias = $validated['dias_incidencia'] ?? [];
                $sdis = $validated['sdi_movimiento'] ?? [];

                $empleados->transform(function ($empleado) use ($tipoMov, $fechas, $folios, $dias, $sdis) {
                    $id = (int) $empleado->idempleado;
                    $empleado->tipo_movimiento_sua = $tipoMov;
                    $empleado->fecha_movimiento_sua = $fechas[$id] ?? $fechas[(string) $id] ?? null;
                    $empleado->folio_incapacidad_sua = $folios[$id] ?? $folios[(string) $id] ?? '';
                    $empleado->dias_incidencia_sua = $dias[$id] ?? $dias[(string) $id] ?? 0;
                    $empleado->sdi_sua = $sdis[$id] ?? $sdis[(string) $id] ?? $empleado->salario_fijo ?? 0;
                    return $empleado;
                });
            }

            if ($tipo === SuaExportService::TIPO_CREDITO) {
                $tipoMovCred = (string) ($validated['tipo_movimiento_credito'] ?? '15');
                if (!array_key_exists($tipoMovCred, SuaExportService::TIPOS_MOVIMIENTO_CREDITO)) {
                    return $suaError('Seleccione un tipo de movimiento de crédito válido.');
                }

                $fechasCred = $validated['fecha_movimiento_credito'] ?? [];
                $numerosCredito = $validated['numero_credito'] ?? [];
                $tiposDesc = $validated['tipo_descuento'] ?? [];
                $valoresDesc = $validated['valor_descuento'] ?? [];
                $aplicaTabla = $validated['aplica_tabla'] ?? [];

                $empleados->transform(function ($empleado) use ($tipoMovCred, $fechasCred, $numerosCredito, $tiposDesc, $valoresDesc, $aplicaTabla) {
                    $id = (int) $empleado->idempleado;
                    $creditoNomina = preg_replace('/\D+/', '', (string) ($empleado->numero_credito_infonavit ?? '')) ?? '';
                    $creditoForm = preg_replace('/\D+/', '', (string) ($numerosCredito[$id] ?? $numerosCredito[(string) $id] ?? '')) ?? '';
                    if ($creditoForm === '' || (int) $creditoForm <= 0) {
                        $creditoForm = $creditoNomina;
                    }

                    $tipoDescForm = (string) ($tiposDesc[$id] ?? $tiposDesc[(string) $id] ?? '');
                    $tipoDescNomina = SuaExportService::tipoDescuentoDesdeCatalogo(
                        (string) ($empleado->nombreinfonavit ?? ''),
                        $empleado->id_tipoinfonavit ?? null
                    );
                    if ($tipoDescForm === '' || !array_key_exists($tipoDescForm, SuaExportService::TIPOS_DESCUENTO)) {
                        $tipoDescForm = $tipoDescNomina;
                    }

                    $valorForm = $valoresDesc[$id] ?? $valoresDesc[(string) $id] ?? null;
                    $valorNomina = (float) ($empleado->descuento_quincenal ?? 0);
                    if ($valorNomina <= 0) {
                        $valorNomina = (float) ($empleado->factor_sua ?? 0);
                    }
                    if ($valorForm === null || $valorForm === '' || (float) $valorForm <= 0) {
                        $valorForm = $valorNomina;
                    }

                    $empleado->tipo_movimiento_credito_sua = $tipoMovCred;
                    $empleado->fecha_movimiento_sua = $fechasCred[$id] ?? $fechasCred[(string) $id] ?? null;
                    $empleado->numero_credito_sua = $creditoForm;
                    $empleado->tipo_descuento_sua = $tipoDescForm;
                    $empleado->valor_descuento_sua = $valorForm;
                    $empleado->aplica_tabla_sua = $aplicaTabla[$id] ?? $aplicaTabla[(string) $id] ?? 'N';
                    return $empleado;
                });
            }

            $registrosExport = $empleados;
            if ($tipo === SuaExportService::TIPO_INCAPACITADOS) {
                $validated['exportar_todas_incapacidades'] = $request->boolean('exportar_todas_incapacidades');
                $registrosExport = $this->construirRegistrosIncapacidadesSua($empleados, $validated);
                if ($registrosExport->isEmpty()) {
                    return $suaError('Los empleados seleccionados no tienen incapacidades registradas para exportar.');
                }
            }

            try {
                $contenido = $suaExport->generar($tipo, $registrosExport, [
                    'tipo_movimiento' => $validated['tipo_movimiento'] ?? '02',
                    'tipo_movimiento_credito' => $validated['tipo_movimiento_credito'] ?? '15',
                ]);
            } catch (\InvalidArgumentException $ex) {
                return $suaError($ex->getMessage());
            }

            $filename = $suaExport->nombreArchivo($tipo);

            if ($contenido === '' || trim(str_replace(["\r", "\n"], '', $contenido)) === '') {
                return $suaError('No se generó contenido para el archivo SUA. Verifique los datos de los empleados seleccionados.');
            }

            return response($contenido, 200, [
                'Content-Type' => 'text/plain; charset=ISO-8859-1',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'X-SUA-Filename' => $filename,
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
                'Pragma' => 'no-cache',
            ]);
        } catch (\Illuminate\Validation\ValidationException $ex) {
            $mensaje = collect($ex->errors())->flatten()->first() ?: 'Datos inválidos para exportar a SUA.';
            return $suaError($mensaje);
        } catch (\Throwable $ex) {
            Log::error('EmpleadosController::exportar_sua: ' . $ex->getMessage(), ['exception' => $ex]);
            return $suaError('No se pudo exportar el archivo SUA. ' . $ex->getMessage(), 500);
        }
    }

    public function grafica_empleados(){
        try{
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();
            $varpuestos = $this->obtenerpuestos();
            $varsucursales = $this->obtenersucursales();
            $varciudades =  $this->obtenerciudades();
            $varempresas = $this->obtenerempresas();
            $varbancos = $this->obtenerbancos();
            $varlistaempleados=  $this-> obtenerlistaempleados();
            $empleados = collect($varlistaempleados);
            $puntos = [];

            foreach($varlistaempleados as $articulo){
                $puntos[] = [
                    'name' => $articulo['primer_nombre'] ?? 'Empleado',
                    'y' => floatval($articulo['idpuesto'] ?? 0)
                ];
            }

            $graficaPuestos = $empleados->groupBy('puesto')->map(function ($grupo, $puesto) {
                return ['name' => $puesto ?: 'Sin puesto', 'y' => $grupo->count()];
            })->values()->all();

            $graficaSucursales = $empleados->groupBy('sucursal')->map(function ($grupo, $sucursal) {
                return ['name' => $sucursal ?: 'Sin sucursal', 'y' => $grupo->count()];
            })->values()->all();

            $graficaEstados = $empleados->groupBy('estado')->map(function ($grupo, $estado) {
                $nombre = $estado === 'A' ? 'Activo' : 'Inactivo';
                return ['name' => $nombre, 'y' => $grupo->count()];
            })->values()->all();

            $graficaAntiguedad = $empleados->filter(function ($empleado) {
                return !empty($empleado->fecha_alta);
            })->map(function ($empleado) {
                return Carbon::parse($empleado->fecha_alta);
            })->map(function ($fecha) {
                return $fecha->diffInYears(Carbon::now());
            })->reduce(function ($carry, $years) {
                if ($years <= 1) {
                    $carry['0-1']++;
                } elseif ($years <= 3) {
                    $carry['1-3']++;
                } elseif ($years <= 5) {
                    $carry['3-5']++;
                } else {
                    $carry['5+']++;
                }
                return $carry;
            }, ['0-1' => 0, '1-3' => 0, '3-5' => 0, '5+' => 0]);

            $graficaAntiguedad = collect($graficaAntiguedad)->map(function ($total, $rango) {
                return ['name' => $rango . ' años', 'y' => $total];
            })->values()->all();

            $salarios = $empleados->map(function ($empleado) {
                return floatval($empleado->salario_bruto ?? 0);
            });

            $graficaSalarios = [
                ['name' => '0 - 8,000', 'y' => $salarios->filter(fn ($s) => $s > 0 && $s <= 8000)->count()],
                ['name' => '8,001 - 12,000', 'y' => $salarios->filter(fn ($s) => $s > 8000 && $s <= 12000)->count()],
                ['name' => '12,001 - 20,000', 'y' => $salarios->filter(fn ($s) => $s > 12000 && $s <= 20000)->count()],
                ['name' => '20,001 +', 'y' => $salarios->filter(fn ($s) => $s > 20000)->count()],
            ];

            $salarioPorPuesto = $empleados->filter(function ($empleado) {
                return !empty($empleado->puesto);
            })->groupBy('puesto')->map(function ($grupo) {
                $promedio = $grupo->average(function ($empleado) {
                    return floatval($empleado->salario_bruto ?? 0);
                });
                return round($promedio, 2);
            });

            $graficaSalarioPuesto = [
                'categorias' => $salarioPorPuesto->keys()->values()->all(),
                'series' => $salarioPorPuesto->values()->all(),
            ];

            $inicio = Carbon::now()->startOfMonth()->subMonths(11);
            $meses = collect(range(0, 11))->map(function ($offset) use ($inicio) {
                return $inicio->copy()->addMonths($offset);
            });

            $contrataciones = $empleados->filter(function ($empleado) {
                return !empty($empleado->fecha_alta);
            })->map(function ($empleado) {
                return Carbon::parse($empleado->fecha_alta);
            });

            $contratacionesPorMes = $contrataciones->groupBy(function ($fecha) {
                return $fecha->format('Y-m');
            })->map->count();

            $graficaContrataciones = $meses->map(function ($mes) use ($contratacionesPorMes) {
                $key = $mes->format('Y-m');
                return [
                    'name' => $mes->format('M Y'),
                    'y' => $contratacionesPorMes[$key] ?? 0
                ];
            })->values()->all();

            return view('Empleados.graficaempleados', [
                'data' => json_encode($puntos),
                'graficaPuestos' => $graficaPuestos,
                'graficaSucursales' => $graficaSucursales,
                'graficaEstados' => $graficaEstados,
                'graficaAntiguedad' => $graficaAntiguedad,
                'graficaSalarios' => $graficaSalarios,
                'graficaSalarioPuesto' => $graficaSalarioPuesto,
                'graficaContrataciones' => $graficaContrataciones
            ], compact('varpantallas','varsubmenus','varlistaempleados','varpuestos','varsucursales','varciudades','varempresas','varbancos',));
        } catch(\Throwable $ex){
            return $this->empleadosErrorResponse($ex, 'No se pudo cargar la gráfica de empleados');
        }
    }

    public function mguardar(Request $request, int $id){
        try{
            if($request->hasFile("bajaFirmada")){
                $file=$request->file("bajaFirmada");

                $empleados = Empleados::find($id);
                $empleados->archivo_baja = 1;
                $empleados->save();

                $nombre = "baja_"."$id".".".$file->guessExtension();
                $ruta = public_path("DetallesEmpleados/bajas/".$nombre);
                copy($file, $ruta);
                
                return redirect()->route('verempleados')->with('success_msg_large', 'El documento de baja firmada se cargó correctamente.');
            }else{
                return redirect()->route('verempleados')->with('warning_msg_large', 'Debe seleccionar el PDF de baja firmada para subir.');
            }

        } catch(\Throwable $ex){
            return $this->empleadosErrorResponse($ex, 'Error al subir el documento de baja firmada');
        }
    }

    public function incapacidades(){
        try{
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();
            $varincapacidades =   $this->TraerIncapacidades();
            $varlistaempleados =   $this->obtenerempleadosActivos();
            $contratosPorVencer = $this->obtenerContratosPorVencer(7);

            return view('Empleados.Incapacidades.index',compact('varpantallas','varsubmenus','varincapacidades','varlistaempleados','contratosPorVencer'));
          
        } catch(\Throwable $ex){
            return $this->empleadosErrorResponse($ex, 'No se pudo cargar el listado de incapacidades');
        }
    }

    public function incapacidades_vista_alta(){
        try{
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();
            $varincapacidades =   $this->TraerIncapacidades();
            $varlistaempleados =   $this->obtenerempleadosActivos();

            return view('Empleados.Incapacidades.insertar',compact('varpantallas','varsubmenus','varincapacidades','varlistaempleados'));
          
        } catch(\Throwable $ex){
            return $this->empleadosErrorResponse($ex, 'No se pudo abrir el formulario de alta de incapacidad');
        }
    }


    public function incapacidades_insertar(Request $request){
        try{

            $file_evidencia=$request->file("ruta_evidencia");

            if($file_evidencia->guessExtension()=="pdf"){

                $Rutacarpeta = "DetallesEmpleados/incapacidades/".$request->get('id_empleado');
                if(!file_exists(public_path($Rutacarpeta)))
                {File::makeDirectory($Rutacarpeta,0777,true,true);}

                $nombre_evidencia = "incapacidad_".$request->get('fecha_incidencia').".".$file_evidencia->guessExtension();   
                $ruta_evidencia = public_path($Rutacarpeta."/".$nombre_evidencia);
                copy($file_evidencia, $ruta_evidencia);
               
                $empleados = new incapacidades();
                $empleados->id_empleado  = $request->get('id_empleado');
                $empleados->id_autorizo  = auth()->user()->idempleado;
                $empleados->descripcion = $request->get('descripcion');
                $empleados->fecha_incidencia = $request->get('fecha_incidencia');
                $empleados->fecha_inicio = $request->get('fecha_inicio');
                $empleados->fecha_fin = $request->get('fecha_fin');
                $empleados->folio = $request->get('folio');
                $empleados->tipo = $request->get('tipo');
                $empleados->ramo_seguro = $request->get('ramo_seguro');
                $empleados->ruta_evidencia = $nombre_evidencia;
                $empleados->created_by = auth()->user()->name;

                if($empleados->save()){
                   
                    return redirect()->route('Empleados.incapacidades')->with('success_msg_large', 'La incapacidad se registró correctamente.');

                }else{

                    return back()->with('warning_msg_large', 'No se pudo guardar el registro de incapacidad.');
                }

            }else{
                return back()->with('warning_msg_large', 'La evidencia de incapacidad debe ser un archivo PDF.');
            }

        } catch(\Throwable $ex){
            return $this->empleadosErrorResponse($ex, 'Error al registrar la incapacidad');
        }
    }


    public function incapacidades_eliminar($id,Request $request){
        try{

            $Borrartbl3 =  DB::select('delete from tblincapacidades where id = ? ', [$id]);

            $ruta = "DetallesEmpleados/incapacidades/".$request->get('id_empleado')."/".$request->get('ruta_evidencia');

            if(file_exists(public_path($ruta))){
                File::delete(public_path($ruta));
              }else{error_log('No tienes este archivo');}

            return redirect()->route('Empleados.incapacidades')->with('success_msg_large', 'La incapacidad se eliminó correctamente.');

               

        } catch(\Throwable $ex){
            return $this->empleadosErrorResponse($ex, 'Error al eliminar la incapacidad');
        }
    }

    public function vacaciones(){
        try{
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();
            $varvacaciones =   $this->TraerVacaciones();
            $varlistaempleados =   $this->obtenerempleadosActivos();
            $contratosPorVencer = $this->obtenerContratosPorVencer(7);

            return view('Empleados.Vacaciones.index',compact('varpantallas','varsubmenus','varvacaciones','varlistaempleados','contratosPorVencer'));
          
        } catch(\Throwable $ex){
            return $this->empleadosErrorResponse($ex, 'No se pudo cargar el listado de vacaciones');
        }
    }

    public function vacaciones_vista_alta(){
        try{
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();
            $varvacaciones =   $this->TraerVacaciones();
            $varlistaempleados =   $this->obtenerempleadosActivos();

            return view('Empleados.Vacaciones.insertar',compact('varpantallas','varsubmenus','varvacaciones','varlistaempleados'));
          
        } catch(\Throwable $ex){
            return $this->empleadosErrorResponse($ex, 'No se pudo abrir el formulario de alta de vacaciones');
        }
    }

    public function vacaciones_insertar(Request $request){
        try{
            $request->validate([
                'id_empleado' => 'required|integer',
                'fecha_solicitante' => 'required|date',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
                'descripcion' => 'required|string|max:300',
                'ruta_evidencia' => 'required|file|mimes:pdf|max:10240',
            ]);

            $file_evidencia = $request->file('ruta_evidencia');
            $extension = strtolower($file_evidencia->getClientOriginalExtension() ?: $file_evidencia->guessExtension() ?: '');

            if ($extension !== 'pdf' && $file_evidencia->getMimeType() !== 'application/pdf') {
                return back()->with('info_msg_large', 'El archivo de evidencia debe ser un documento PDF.')->withInput();
            }

            $fechaInicio = Carbon::parse($request->get('fecha_inicio'));
            $fechaFin = Carbon::parse($request->get('fecha_fin'));
            $diasSolicitados = $fechaInicio->diffInDays($fechaFin) + 1;

            $Rutacarpeta = 'DetallesEmpleados/vacaciones/'.$request->get('id_empleado');
            if (!file_exists(public_path($Rutacarpeta))) {
                File::makeDirectory(public_path($Rutacarpeta), 0777, true, true);
            }

            $nombre_evidencia = 'vacacion_'.$request->get('fecha_inicio').'.pdf';
            $file_evidencia->move(public_path($Rutacarpeta), $nombre_evidencia);

            $registro = new vacaciones();
            $registro->id_empleado = $request->get('id_empleado');
            $registro->id_autorizo = auth()->user()->idempleado ?? null;
            $registro->descripcion = $request->get('descripcion');
            $registro->fecha_solicitante = $request->get('fecha_solicitante');
            $registro->fecha_inicio = $request->get('fecha_inicio');
            $registro->fecha_fin = $request->get('fecha_fin');
            $registro->dias_solicitados = $diasSolicitados;
            $registro->ruta_evidencia = $nombre_evidencia;
            $registro->created_by = auth()->user()->name;

            if ($registro->save()) {
                return redirect()->route('Empleados.vacaciones')->with('success_msg_large', 'El periodo de vacaciones se registró correctamente.');
            }

            return back()->with('warning_msg_large', 'No se pudo guardar el registro de vacaciones.')->withInput();
        } catch(\Illuminate\Validation\ValidationException $ex){
            throw $ex;
        } catch(\Throwable $ex){
            return $this->empleadosErrorResponse($ex, 'Error al registrar las vacaciones')->withInput();
        }
    }

    public function vacaciones_eliminar($id, Request $request){
        try{
            DB::select('delete from tblvacaciones where id = ? ', [$id]);

            $ruta_evidencia = $request->get('ruta_evidencia');
            if($ruta_evidencia){
                $ruta = "DetallesEmpleados/vacaciones/".$request->get('id_empleado')."/".$ruta_evidencia;

                if(file_exists(public_path($ruta))){
                    File::delete(public_path($ruta));
                }
            }

            return redirect()->route('Empleados.vacaciones')->with('success_msg_large', 'El periodo de vacaciones se eliminó correctamente.');
        } catch(\Throwable $ex){
            return $this->empleadosErrorResponse($ex, 'Error al eliminar el periodo de vacaciones');
        }
    }

    public function importar_empleados_excel(Request $request)
    {
        try {
            if ($this->forpermisos('alta_empleados') !== 'alta_empleados') {
                return back()->with('info_msg_large', 'No cuenta con permiso para importar empleados.');
            }

            $request->validate([
                'archivo_empleados' => ['required', 'file', 'mimes:xlsx,xls'],
            ]);

            $import = new EmpleadosImport();
            Excel::import($import, $request->file('archivo_empleados'));
            $resumen = $import->getResumen();

            $mensaje = "Importación de empleados: {$resumen['creados']} creados, {$resumen['actualizados']} actualizados.";
            if (!empty($resumen['errores'])) {
                $mensaje .= ' Errores: ' . implode(' | ', array_slice($resumen['errores'], 0, 5));
                if (count($resumen['errores']) > 5) {
                    $mensaje .= ' ...';
                }

                return back()->with('warning_msg_large', $mensaje);
            }

            return back()->with('success_msg_large', $mensaje);
        } catch (\Throwable $ex) {
            Log::error('EmpleadosController::importar_empleados_excel: ' . $ex->getMessage(), ['exception' => $ex]);

            return back()->with('error_msg_large', 'Error al importar empleados. ' . $ex->getMessage());
        }
    }

    public function importar_nomina_excel(Request $request)
    {
        try {
            if ($this->forpermisos('alta_empleados') !== 'alta_empleados') {
                return back()->with('info_msg_large', 'No cuenta con permiso para importar datos de nómina.');
            }

            $request->validate([
                'archivo_nomina' => ['required', 'file', 'mimes:xlsx,xls'],
            ]);

            $import = new NominaEmpleadosImport();
            Excel::import($import, $request->file('archivo_nomina'));
            $resumen = $import->getResumen();

            $mensaje = "Importación de nómina: {$resumen['creados']} creados, {$resumen['actualizados']} actualizados.";
            if (!empty($resumen['errores'])) {
                $mensaje .= ' Errores: ' . implode(' | ', array_slice($resumen['errores'], 0, 5));
                if (count($resumen['errores']) > 5) {
                    $mensaje .= ' ...';
                }

                return back()->with('warning_msg_large', $mensaje);
            }

            return back()->with('success_msg_large', $mensaje);
        } catch (\Throwable $ex) {
            Log::error('EmpleadosController::importar_nomina_excel: ' . $ex->getMessage(), ['exception' => $ex]);

            return back()->with('error_msg_large', 'Error al importar nómina. ' . $ex->getMessage());
        }
    }

     private function guardarHorariosEmpleado(Request $request, int $idEmpleado): void
    {
        $horarios = $request->get('horarios', []);
        $horariosRegistro = $request->get('horarios_registro', []);
        $userName = auth()->user()->name;

        foreach ($horarios as $idDia => $idHorario) {
            if ($idHorario === null || $idHorario === '') {
                continue;
            }

            $registroId = $horariosRegistro[$idDia] ?? null;
            $registro = $registroId
                ? EmpleadosHorarios::find($registroId)
                : EmpleadosHorarios::where('id_empleado', $idEmpleado)
                    ->where('id_dia', (string) $idDia)
                    ->first();

            if ($registro) {
                $registro->id_horario = $idHorario;
                $registro->updated_by = $userName;
                $registro->save();
            } else {
                $nuevo = new EmpleadosHorarios();
                $nuevo->id_empleado = $idEmpleado;
                $nuevo->id_dia = (string) $idDia;
                $nuevo->id_horario = $idHorario;
                $nuevo->created_by = $userName;
                $nuevo->save();
            }
        }
    }

}