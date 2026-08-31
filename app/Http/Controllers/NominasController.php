<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Traits\MenuTrait;
use App\Traits\DatosimpleTraits;
use App\Traits\SistemasTraits;
use App\Models\Nominas_pagosenc;
use App\Models\pagonominaenc;
use App\Models\pagonominadet;
use App\Models\Nominas_pagosdet;
use App\Models\NominaAsistencias;
use App\Models\cuentas;
use App\Models\rptnomx;
use DB;
use Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\BoTranImport;
use App\Imports\AsistenciasNominaImport;
use App\Imports\AsistenciasRelojImport;
use App\Services\AsistenciasFormatoDetector;
use App\Exports\NominasExport;
use App\Exports\AsistenciasNominaExportFormat;
use App\Exports\rptnomxsuc;
use App\Exports\rpttetnom;
use App\Exports\NominasExportFormat;
use App\Exports\RetencionesNominaExport;
use App\Exports\importarnominafiscal;
use App\Models\historial_cuentas;
use App\Models\tarifa_isr;
use App\Models\tarifa_subsidio;
use App\Models\tarifas_integracion;
use App\Models\cesantia_vejez;
use App\Models\tipo_nominas;
use App\Traits\NominaTraits;
use Illuminate\Support\Facades\File;
use App\Exports\ExpotArchivoDispersion;
use App\Models\aguinaldos_enc;
use App\Models\aguinaldos_det;
use App\Models\aportaciones_patronales_enc;
use App\Models\aportaciones_patronales_det;
use App\Models\conceptos_nomina;
use App\Exports\AguinaldosExport;


class NominasController extends Controller
{
    use MenuTrait;
    use DatosimpleTraits;
    use SistemasTraits;
    use NominaTraits;

    public function __construct()
    {
        $this->middleware('auth');
    }

    //NOMINAS
        public function index(Request $request){
            try{
                $varpantallas =  $this->Traermenuenc();
                $varsubmenus =   $this->Traermenudet();

                $fechaInicio = $request->get('fecha_inicio', Carbon::now()->startOfMonth()->format('Y-m-d'));
                $fechaFin = $request->get('fecha_fin', Carbon::now()->endOfMonth()->format('Y-m-d'));
                $tipoNomina = $request->get('tipo_nomina');

                $varnominas = $this->obtenernominas($fechaInicio, $fechaFin, $tipoNomina ?: null);
                $chartData = $this->obtenerDatosGraficasNominas($varnominas->pluck('id')->all());

                $permisos1 = $this->forpermisos('alta_nominas');
                $permisos2 = $this->forpermisos('ver_nominas');
                $permisos3 = $this->forpermisos('calcular_nominas');
                $permisos4 = $this->forpermisos('actualizar_nominas');
                $permisos5 = $this->forpermisos('exportar_nominas');
                $permisos6 = $this->forpermisos('eliminar_nominas');
                $vartiponominas = tipo_nominas::orderBy('id')->get();
                $varempresas = $this->obtenerempresas();

                return view('nominas.nomina', compact(
                    'varpantallas',
                    'varsubmenus',
                    'varnominas',
                    'vartiponominas',
                    'varempresas',
                    'permisos1',
                    'permisos2',
                    'permisos3',
                    'permisos4',
                    'permisos5',
                    'permisos6',
                    'fechaInicio',
                    'fechaFin',
                    'tipoNomina',
                    'chartData'
                ));
            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg_large","Error al cargar datos, intente despues o más tarde"); }
        }

        public function resultadosNomina($id){
            try{
                $varpantallas = $this->Traermenuenc();
                $varsubmenus = $this->Traermenudet();
                $permisos2 = $this->forpermisos('ver_nominas');

                if ($permisos2 !== 'ver_nominas') {
                    return back()->with('info_msg_large', 'No tiene permisos para ver resultados de nómina.');
                }

                $nominaEnc = Nominas_pagosenc::findOrFail($id);
                $detalle = $this->obtenernominasporid($id);

                $chartData = [
                    'isr' => round($detalle->sum('pago_isr'), 2),
                    'imss' => round($detalle->sum('pago_imss'), 2),
                    'infonavit' => round($detalle->sum('pago_infonavit'), 2),
                    'bonos' => round($detalle->sum('bono'), 2),
                    'horasExtras' => round($detalle->sum('total_horas_extras'), 2),
                    'incapacidades' => (int) $detalle->sum('dias_incapacidad'),
                    'faltas' => (int) $detalle->sum('faltas_reta_aus'),
                    'vacaciones' => (int) $detalle->sum('dias_vaciones'),
                    'diasNoLaborados' => (int) $detalle->sum('dias_pendiente'),
                    'fonacot' => round($detalle->sum('fonacot'), 2),
                    'deudores' => round($detalle->sum('total_deudores'), 2),
                ];

                return view('nominas.resultados', compact(
                    'varpantallas',
                    'varsubmenus',
                    'nominaEnc',
                    'chartData',
                    'permisos2'
                ));
            } catch(\Illuminate\Database\QueryException $ex){
                return back()->with('info_msg_large', 'Error al cargar resultados, intente después o más tarde');
            }
        }
    
        public function store(Request $request){
            try{
                $date = Carbon::now();
                $fecha = $date->format('Y-m-d');
                $fecha_inicio=$request->get('fecha_ini_nom');
                $fecha_fin=$request->get('fecha_ter_no');
                $idTipoNomina = (int) $request->get('tipo_nomina', 2);
                $tipoEmpresa = strtoupper((string) $request->get('tipo_empresa', 'GENERAL'));
                $idEmpresa = $request->get('id_empresa');
                $obtenernoinasEnc = $this->obtenernoinasEnc(); 
                $fechaAprobada = "A";
                $permisos = $this->forpermisos('alta_nominas');  
    
                if($permisos=="alta_nominas"){ 

                    if (!in_array($tipoEmpresa, ['GENERAL', 'EMPRESA'], true)) {
                        return redirect()->route('vernominas')->with("info_msg_large", "Seleccione un alcance de nómina válido.");
                    }

                    if ($tipoEmpresa === 'EMPRESA' && empty($idEmpresa)) {
                        return redirect()->route('vernominas')->with("info_msg_large", "Seleccione la empresa para la nómina.");
                    }

                    if (!tipo_nominas::where('id', $idTipoNomina)->exists()) {
                        return redirect()->route('vernominas')->with("info_msg_large", "¡Estas fechas no son validas,Verifique el rango de fechas según el tipo de nómina seleccionado (Semanal: 7 días, Quincenal: 15 días, Mensual: mes completo)");
                    }

                    if (!$this->validarRangoFechasTipoNomina($idTipoNomina, $fecha_inicio, $fecha_fin)) {
                        return redirect()->route('vernominas')->with("info_msg_large", "¡Estas fechas no son validas,Verifique el rango de fechas según el tipo de nómina seleccionado (Semanal: 7 días, Quincenal: 15 días, Mensual: mes completo)");
                    }
    
                    $fecha1 = "0000-00-00";
                    $fecha2 = "0000-00-00";
        
                    foreach($obtenernoinasEnc as $fechas){
                        $dateToCheck1 = Carbon::parse($fecha_inicio);
                        $dateToCheck2 = Carbon::parse($fecha_fin);
    
                        $fecha1 = $fechas->fecha_inicio;
                        $fecha2 = $fechas->fecha_fin;
                        $startDate = Carbon::parse($fecha1);
                        $endDate = Carbon::parse($fecha2);
                        
                        if($dateToCheck1->between($startDate, $endDate) || $dateToCheck2->between($startDate, $endDate)){$fechaAprobada = "N";break;
                        }else{ $fechaAprobada = "A";}
                    }
            
                    if ($fechaAprobada == "A") {
                        //insertamos en la tabla pagosnominasencabezado
                        $nominas_pagoenc = new Nominas_pagosenc();
                        $nominas_pagoenc->nombre_nomina = $request->get('nombre_nomina');
                        $nominas_pagoenc->fecha_inicio = $fecha_inicio;
                        $nominas_pagoenc->fecha_fin = $fecha_fin;
                        $nominas_pagoenc->estado_nomina = 'Edicion';
                        $nominas_pagoenc->comentarios = 'comentario';
                        $nominas_pagoenc->idtiponomina = $idTipoNomina;
                        $nominas_pagoenc->tipo_empresa = $tipoEmpresa;
                        $nominas_pagoenc->id_empresa = $tipoEmpresa === 'EMPRESA' ? (int) $idEmpresa : null;
                        $nominas_pagoenc->created_by=auth()->user()->name;
    
                        //CALCULAR NOMINA EN SEGUIDA
                        if ($nominas_pagoenc->save()) {
                            $id_nom = $this->ultima_nomina_insertada(); 
                            $funcion = $this->calcular_nomia("Calcular",$id_nom,$fecha_inicio,$fecha_fin);

                            if($funcion == "exito"){;
                                return back()->with("success_msg_large","Felicidades, Nómina creada correctamente");
                            }else{
                                return back()->with("error_msg_large","Conflicto al guardar, intente de nuevo o más tarde");
                            }
                        }else{
                            return redirect()->route('vernominas')->with("error_msg_large","Error al guardar, intente de nuevo o más tarde");
                        }
    
                    }else{return redirect()->route('vernominas')->with("info_msg_large","¡Estas fechas no son validas,Verifique el rango de fechas según el tipo de nómina seleccionado (Semanal: 7 días, Quincenal: 15 días, Mensual: mes completo)");}
                
                }else{
                    return redirect()->route('vernominas')->with("info_msg_large","No tiene permisos para crear nóminas.");
                }
            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg_large","Error al cargar datos, intente despues o más tarde"); }
        }
    
        public function editarnomina(int $id, int $idtiponomina, string $fecha_inicio, string $fecha_fin){
            try{
                $varnominas =  $this->obtenernominasporid($id);
                $varpantallas =  $this->Traermenuenc();
                $varsubmenus =   $this->Traermenudet();
                $listaprestamos =$this->obtenerprestmoempxfecha($fecha_inicio,$fecha_fin);
                $varcuentas  =$this->obtenercuentasActivas();
                $permisos1 = $this->forpermisos('importar_bonos_nominas');
                $permisos2 = $this->forpermisos('calcular_nominas');
                $permisos3 = $this->forpermisos('exportar_nominas');
                $validaTimbrado =  $this->validaTimbrado($id);
                $validaNominasenCero =  $this->validaNominasenCero($id);
    
                return view('nominas.editarnomina',compact('varpantallas','varsubmenus','varnominas','listaprestamos','permisos1',
                'permisos2','permisos3','varcuentas','fecha_inicio','fecha_fin','validaTimbrado','validaNominasenCero'));
            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg","Error al cargar datos, intente despues o más tarde"); }
        }

        public function previewCierreNomina(int $id, int $idtiponomina, string $fecha_inicio, string $fecha_fin){
            try{
                $varnominas = $this->obtenernominasporid($id);

                if (empty($varnominas)) {
                    return redirect()->route('vernominas')->with('info_msg_large', 'Nómina no encontrada.');
                }

                $estadoNomina = $varnominas[0]->estado_nomina ?? '';
                if ($estadoNomina !== 'Edicion') {
                    return redirect()->route('Nominaseditar', [
                        'id' => $id,
                        'idtiponomina' => $idtiponomina,
                        'fecha_ini' => $fecha_inicio,
                        'fecha_fin' => $fecha_fin,
                    ])->with('info_msg_large', 'La nómina ya no está en edición.');
                }

                $validaNominasenCero = $this->validaNominasenCero($id);
                if ($validaNominasenCero > 0) {
                    return redirect()->route('Nominaseditar', [
                        'id' => $id,
                        'idtiponomina' => $idtiponomina,
                        'fecha_ini' => $fecha_inicio,
                        'fecha_fin' => $fecha_fin,
                    ])->with('info_msg_large', 'No se puede cerrar: hay empleados con sueldo fiscal en $0.');
                }

                $totalFiscal = 0;
                $totalExcedente = 0;
                foreach ($varnominas as $pago) {
                    $totalFiscal += $pago->total_nomina_fiscal;
                    $totalExcedente += $pago->total_apagar_excedente;
                }
                $saldosVisor = conceptos_nomina::visorFiscalActivo()
                    ? $totalFiscal
                    : ($totalFiscal + $totalExcedente);

                if ($saldosVisor <= 0) {
                    return redirect()->route('Nominaseditar', [
                        'id' => $id,
                        'idtiponomina' => $idtiponomina,
                        'fecha_ini' => $fecha_inicio,
                        'fecha_fin' => $fecha_fin,
                    ])->with('info_msg_large', 'El total a dispersar debe ser mayor a 0.');
                }

                $varpantallas = $this->Traermenuenc();
                $varsubmenus = $this->Traermenudet();
                $varcuentas = $this->obtenercuentasActivas();

                return view('nominas.previewcierre', compact(
                    'varpantallas',
                    'varsubmenus',
                    'varnominas',
                    'varcuentas',
                    'fecha_inicio',
                    'fecha_fin',
                    'idtiponomina'
                ));
            } catch (\Illuminate\Database\QueryException $ex) {
                return back()->with('info_msg_large', 'Error al cargar datos, intente después o más tarde.');
            }
        }

        public function verAsistenciasNomina(int $id){
            try{
                $nominaEnc = Nominas_pagosenc::find($id);

                if(!$nominaEnc){
                    return back()->with('error_msg_large', "No se encontró la nómina con ID {$id}.");
                }

                $varpantallas = $this->Traermenuenc();
                $varsubmenus = $this->Traermenudet();
                $permisosImportar = $this->forpermisos('importar_bonos_nominas');

                $fechaInicio = $nominaEnc->fecha_inicio;
                $fechaFin = $nominaEnc->fecha_fin;

                $detalleAsistencias = NominaAsistencias::obtenerDetalleNomina($id, $fechaInicio, $fechaFin);
                $resumenEmpleados = NominaAsistencias::obtenerResumenPorEmpleado($id, $fechaInicio, $fechaFin);
                $totales = NominaAsistencias::obtenerTotalesNomina($id, $fechaInicio, $fechaFin);

                $empleadosFiltro = NominaAsistencias::obtenerEmpleadosFiltroNomina($id, $fechaInicio, $fechaFin);
                $varhorarios = $this->obtenerhorarios();

                return view('nominas.asistenciasnomina', compact(
                    'varpantallas',
                    'varsubmenus',
                    'nominaEnc',
                    'detalleAsistencias',
                    'resumenEmpleados',
                    'totales',
                    'empleadosFiltro',
                    'permisosImportar',
                    'fechaInicio',
                    'fechaFin',
                    'varhorarios'
                ));
            } catch(\Illuminate\Database\QueryException $ex){
                return back()->with('error_msg_large', 'Error al cargar asistencias: ' . $ex->getMessage());
            } catch(\Throwable $e){
                return back()->with('error_msg_large', 'Error al cargar asistencias: ' . $e->getMessage());
            }
        }

        public function actualizar_estado_asistencia(Request $request, int $id){
            try{
                $request->validate([
                    'id_registro' => 'required|integer|min:1',
                    'estado' => 'required|in:A,F',
                ]);

                $nominaEnc = Nominas_pagosenc::find($id);

                if(!$nominaEnc){
                    return response()->json(['ok' => false, 'message' => "No se encontró la nómina con ID {$id}."], 404);
                }

                if($nominaEnc->estado_nomina !== 'Edicion'){
                    return response()->json(['ok' => false, 'message' => 'La nómina no está en edición.'], 403);
                }

                if($this->forpermisos('importar_bonos_nominas') !== 'importar_bonos_nominas'){
                    return response()->json(['ok' => false, 'message' => 'No tiene permisos para modificar asistencias.'], 403);
                }

                $resultado = NominaAsistencias::actualizarEstadoManual(
                    (int) $request->input('id_registro'),
                    $id,
                    $nominaEnc->fecha_inicio,
                    $nominaEnc->fecha_fin,
                    $request->input('estado'),
                    auth()->user()->name
                );

                $recalculo = $this->calcular_nomia('Recalcular', $id, ' ', ' ');

                return response()->json([
                    'ok' => true,
                    'message' => $recalculo === 'exito'
                        ? 'Estado actualizado y nómina recalculada.'
                        : 'Estado actualizado. Revise el recálculo de la nómina.',
                    'data' => $resultado,
                ]);
            } catch(\InvalidArgumentException $ex){
                return response()->json(['ok' => false, 'message' => $ex->getMessage()], 422);
            } catch(\Throwable $e){
                \Log::error('Error actualizar estado asistencia', ['id' => $id, 'message' => $e->getMessage()]);
                return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
            }
        }

        public function actualizar_checadas_asistencia(Request $request, int $id){
            try{
                $request->validate([
                    'id_registro' => 'required|integer|min:1',
                    'entrada' => 'nullable|string|max:8',
                    'salida' => 'nullable|string|max:8',
                ]);

                $nominaEnc = Nominas_pagosenc::find($id);

                if(!$nominaEnc){
                    return response()->json(['ok' => false, 'message' => "No se encontró la nómina con ID {$id}."], 404);
                }

                if($nominaEnc->estado_nomina !== 'Edicion'){
                    return response()->json(['ok' => false, 'message' => 'La nómina no está en edición.'], 403);
                }

                if($this->forpermisos('importar_bonos_nominas') !== 'importar_bonos_nominas'){
                    return response()->json(['ok' => false, 'message' => 'No tiene permisos para modificar asistencias.'], 403);
                }

                $resultado = NominaAsistencias::actualizarChecadasManual(
                    (int) $request->input('id_registro'),
                    $id,
                    $nominaEnc->fecha_inicio,
                    $nominaEnc->fecha_fin,
                    $request->input('entrada'),
                    $request->input('salida'),
                    auth()->user()->name
                );

                $recalculo = $this->calcular_nomia('Recalcular', $id, ' ', ' ');

                return response()->json([
                    'ok' => true,
                    'message' => $recalculo === 'exito'
                        ? 'Horario actualizado y nómina recalculada.'
                        : 'Horario actualizado. Revise el recálculo de la nómina.',
                    'data' => $resultado,
                ]);
            } catch(\InvalidArgumentException $ex){
                return response()->json(['ok' => false, 'message' => $ex->getMessage()], 422);
            } catch(\Throwable $e){
                \Log::error('Error actualizar checadas asistencia', ['id' => $id, 'message' => $e->getMessage()]);
                return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
            }
        }

        public function actualizar_horario_asistencia(Request $request, int $id){
            try{
                $request->validate([
                    'id_registro' => 'required|integer|min:1',
                    'id_horario' => 'nullable|integer|min:1',
                ]);

                $nominaEnc = Nominas_pagosenc::find($id);

                if(!$nominaEnc){
                    return response()->json(['ok' => false, 'message' => "No se encontró la nómina con ID {$id}."], 404);
                }

                if($nominaEnc->estado_nomina !== 'Edicion'){
                    return response()->json(['ok' => false, 'message' => 'La nómina no está en edición.'], 403);
                }

                if($this->forpermisos('importar_bonos_nominas') !== 'importar_bonos_nominas'){
                    return response()->json(['ok' => false, 'message' => 'No tiene permisos para modificar asistencias.'], 403);
                }

                $idHorario = $request->input('id_horario');
                $idHorario = ($idHorario === null || $idHorario === '') ? null : (int) $idHorario;

                $resultado = NominaAsistencias::actualizarHorarioManual(
                    (int) $request->input('id_registro'),
                    $id,
                    $nominaEnc->fecha_inicio,
                    $nominaEnc->fecha_fin,
                    $idHorario,
                    auth()->user()->name
                );

                $recalculo = $this->calcular_nomia('Recalcular', $id, ' ', ' ');

                return response()->json([
                    'ok' => true,
                    'message' => $recalculo === 'exito'
                        ? 'Horario actualizado y nómina recalculada.'
                        : 'Horario actualizado. Revise el recálculo de la nómina.',
                    'data' => $resultado,
                ]);
            } catch(\InvalidArgumentException $ex){
                return response()->json(['ok' => false, 'message' => $ex->getMessage()], 422);
            } catch(\Throwable $e){
                \Log::error('Error actualizar horario asistencia', ['id' => $id, 'message' => $e->getMessage()]);
                return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
            }
        }

        public function recalcular_asistencias_nomina(Request $request, int $id){
            try{
                $nominaEnc = Nominas_pagosenc::find($id);

                if(!$nominaEnc){
                    return back()->with('error_msg_large', "No se encontró la nómina con ID {$id}.");
                }

                if($nominaEnc->estado_nomina !== 'Edicion'){
                    return back()->with('warning_msg_large', 'La nómina no está en edición.');
                }

                if($this->forpermisos('importar_bonos_nominas') !== 'importar_bonos_nominas'){
                    return back()->with('warning_msg_large', 'No tiene permisos para recalcular asistencias.');
                }

                $total = NominaAsistencias::recalcularTodos(
                    $id,
                    $nominaEnc->fecha_inicio,
                    $nominaEnc->fecha_fin,
                    auth()->user()->name
                );

                if($total === 0){
                    return redirect()
                        ->route('Nominas.asistencias', $id)
                        ->with('warning_msg_large', 'No hay registros de asistencias para recalcular.');
                }

                $recalculo = $this->calcular_nomia('Recalcular', $id, ' ', ' ');

                $mensaje = $recalculo === 'exito'
                    ? "Se recalcularon {$total} registros de asistencias y la nómina fue actualizada."
                    : "Se recalcularon {$total} registros de asistencias. Revise el recálculo de la nómina.";

                return redirect()
                    ->route('Nominas.asistencias', $id)
                    ->with('successExcel', $mensaje);
            } catch(\Throwable $e){
                \Log::error('Error recalcular asistencias', ['id' => $id, 'message' => $e->getMessage()]);
                return back()->with('error_msg_large', 'Error al recalcular asistencias: ' . $e->getMessage());
            }
        }
    
        function obtenerSaldoCuentaExistente(&$saldos_cuentas, $cuentaId) {
            foreach ($saldos_cuentas as &$cuenta) {
                if ($cuenta['id'] == $cuentaId) {
                    return $cuenta['saldo'];
                }
            }
            return null;
        }
        
        function actualizarSaldoCuenta(&$saldos_cuentas, $cuentaId, $nuevoSaldo) {
            foreach ($saldos_cuentas as &$cuenta) {
                if ($cuenta['id'] == $cuentaId) {
                    $cuenta['saldo'] = $nuevoSaldo;
                    return;
                }
            }
            $saldos_cuentas[] = ['id' => $cuentaId, 'saldo' => $nuevoSaldo];
        }
    
        public function cerrarnomina(int $id, int $idtipodias, Request $request, string $fecha_inicio, string $fecha_fin){
            try{
                $date = Carbon::now();
                $fecha = $date->format('Y-m-d');
                $idusuario=auth()->user()->id;
                $listacreditoniom =$this->obtenerprestmoempxfecha($fecha_inicio,$fecha_fin);
                $obtenerconceptospagos = $this->obtenerprorrateo();
                $saldogenerado=0;
                $ultimop = 0;
                $fiscalValidaCampos = 0;
                $fiscalValidaSaldo = 0;
                $excedenteValidaCampos = 0;
                $excedenteValidaSaldo = 0;
                $efectivoValidaCampos = 0;
                $efectivoValidaSaldo = 0;
                $total_fiscal = 0;
                $total_excedente = 0;
                $total_efectivo = 0;
                $total_fiscal_vales = 0;
                $total_excedente_vales = 0;
                $total_efectivo_vales = 0;
    
                $total_fiscal = $total_fiscal+ $request->get('total_fiscal');
                $total_excedente = $total_excedente+  $request->get('total_excedente');
                $varnominas =  $this->obtenernominasporid($id);
              
                //GRUPAL
                $saldos_cuentas = [];
                
                function obtenerSaldoCuentaExistente(&$saldos_cuentas, $cuentaId) {
                    foreach ($saldos_cuentas as &$cuenta) {
                        if ($cuenta['id'] == $cuentaId) {
                            return $cuenta['saldo'];
                        }
                    }
                    return null;
                }
                
                function actualizarSaldoCuenta(&$saldos_cuentas, $cuentaId, $nuevoSaldo) {
                    foreach ($saldos_cuentas as &$cuenta) {
                        if ($cuenta['id'] == $cuentaId) {
                            $cuenta['saldo'] = $nuevoSaldo;
                            return;
                        }
                    }
                    $saldos_cuentas[] = ['id' => $cuentaId, 'saldo' => $nuevoSaldo];
                }
                
                if ($total_fiscal > 0 && !is_null($request->get('cuentaFiscal'))) {
                    $fiscalValidaCampos = 1;
                    $cuentaFiscal = $request->get('cuentaFiscal');
                    if (!is_null($cuentaFiscal)) {
                        $saldo_a = obtenerSaldoCuentaExistente($saldos_cuentas, $cuentaFiscal);
                        if (is_null($saldo_a)) {
                            $saldoactual = $this->obtenersaldocuenta($cuentaFiscal);
                            foreach ($saldoactual as $saldoactualcuenta) {
                                $saldo_a = $saldoactualcuenta->saldo_actual;
                            }
                        }
                        if ($saldo_a >= $total_fiscal) {
                            $fiscalValidaSaldo = 1;
                            $saldo_a -= $total_fiscal;
                        } else {
                            $fiscalValidaSaldo = 0;
                        }
                        actualizarSaldoCuenta($saldos_cuentas, $cuentaFiscal, $saldo_a);
                    }
                } elseif ($total_fiscal == 0) {
                    $fiscalValidaCampos = 1;
                    $fiscalValidaSaldo = 1;
                } else {
                    $fiscalValidaCampos = 0;
                }
                
                if ($total_excedente > 0 && !is_null($request->get('cuentaExcedente'))) {
                    $excedenteValidaCampos = 1;
                    $cuentaExcedente = $request->get('cuentaExcedente');
                    if (!is_null($cuentaExcedente)) {
                        $saldo_a = obtenerSaldoCuentaExistente($saldos_cuentas, $cuentaExcedente);
                        if (is_null($saldo_a)) {
                            $saldoactual = $this->obtenersaldocuenta($cuentaExcedente);
                            foreach ($saldoactual as $saldoactualcuenta) {
                                $saldo_a = $saldoactualcuenta->saldo_actual;
                            }
                        }
                        if ($saldo_a >= $total_excedente) {
                            $excedenteValidaSaldo = 1;
                            $saldo_a -= $total_excedente;
                        } else {
                            $excedenteValidaSaldo = 0;
                        }
                        actualizarSaldoCuenta($saldos_cuentas, $cuentaExcedente, $saldo_a);
                    }
                } elseif ($total_excedente == 0) {
                    $excedenteValidaCampos = 1;
                    $excedenteValidaSaldo = 1;
                } else {
                    $excedenteValidaCampos = 0;
                }
              
    
                if($fiscalValidaSaldo > 0){
                    if($excedenteValidaSaldo > 0){
                                //PAGO DE PRESTAMOS
                                if($listacreditoniom->isEmpty()){
                                    error_log('No tienes prestamo');
                                }else{
                                    foreach($listacreditoniom as $lista)
                                    {
                                        if($lista->estado != "S"){
                                            $saldo = 0;
                                            
                                            //actualizamos el pago a saldado
                                            $update1 =  DB::select('update tblcreditosempleado_det set estado = ? where id = ?;', ["S",$lista->id]);
                                            
                                            $validasaldado = $this->validasaldado($lista->id_credito);
                                            if($validasaldado->isEmpty()){
                                                $update2 =  DB::select('update tblcreditos_empleado set estado = ? where id = ?;', ["S",$lista->id_credito]);
                                            }else{error_log('Aun faltan pagos para saldar');}
                                            $cuentaPrestamos = $lista->id_cuenta;
                                            $saldoactual = $this->obtenersaldocuenta($cuentaPrestamos);
                                            foreach($saldoactual as $saldoactualcuenta){$saldo = $saldoactualcuenta->saldo_actual;}
                                            $saldogenerado = $saldo + $lista->pago_quincenal;
                                            
                                           
                                            // afectar cuenta de prestamos
                                            $historialcuent = new  historial_cuentas();
                                            $historialcuent->id_cuenta = $cuentaPrestamos;
                                            $historialcuent->id_empleado = auth()->user()->idempleado;
                                            $historialcuent->estado = "A";
                                            $historialcuent->tipo_movimiento = "PAGO";
                                            $historialcuent->concepto = "PAGO DE PRESTAMO # ".$lista->id_credito." A EMPLEADO #".$lista->id_empleado." POR NOMINA #".$id;
                                            $historialcuent->responsable =  "EMPLEADO #".$lista->id_empleado;
                                            $historialcuent->ingreso = $lista->pago_quincenal;
                                            $historialcuent->egreso = 0;
                                            $historialcuent->saldo =  $saldogenerado; 
                                            $historialcuent->numero_referencia = $lista->id_credito;
                                            $historialcuent->tipo_referencia = "prestamo_emp";
                                            $historialcuent->numero_poliza = 0;
                                            $historialcuent->fecha = $fecha;
                                            $historialcuent->created_by=auth()->user()->name;
                                            $historialcuent->save();
                                            
                                            
                                            $cuenta = cuentas::find($cuentaPrestamos);
                                            $cuenta-> saldo_actual = $saldogenerado;
                                            $cuenta-> updated_by = auth()->user()->name;
                                            $cuenta->save();
    
    
                                            //crear tabla de pagos enc
                                            $insertarenc = new pagonominaenc();
                                            $insertarenc->id_empleado = $lista->id_empleado;
                                            $insertarenc->idprestamos = $lista->id_credito;
                                            $insertarenc->saldo_pagar = $lista->pago_quincenal;
                                            $insertarenc->monto_total = $lista->pago_quincenal;
                                            $insertarenc->fecha_pago = $fecha;
                                            $insertarenc->otrosconceptos1 = "NULL";
                                            $insertarenc->otrosconceptos2 = "NULL";
                                            $insertarenc->otrosconceptos3 = "NULL";
                                            $insertarenc->created_by = auth()->user()->name;
    
                                            if($insertarenc->save()){
    
                                                $ultimopagoshecho = $this->obtenerultimopagnom();
                                                foreach($ultimopagoshecho as $ultimo){ $ultimop =  $ultimo->id;}
    
                                                //crear tabla de det segun el concepto
                                                foreach($obtenerconceptospagos as $conceptoporciento){
                                                $totalcomision = 0;
                                                $inserdetalle = new pagonominadet();
                                                $inserdetalle->idpagonomenc = $ultimop;
                                                $inserdetalle->idconcepto = $conceptoporciento->id;
                                                $inserdetalle->usuarioregistro = $idusuario;
                                                $totalcomision =$lista->pago_quincenal * $conceptoporciento->porcentaje;
                                                $inserdetalle->monto = $totalcomision;
                                                $inserdetalle->fecha_pago = $fecha;
                                                $inserdetalle->otrosconceptos1 = "null";
                                                $inserdetalle->otrosconceptos2 = "null";
                                                $inserdetalle->otrosconceptos3 = "null";
                                                $inserdetalle->created_by = auth()->user()->name;
                                                $inserdetalle->save();
                                                }
                                            }
    
                                            
                                        }else{
                                            error_log('No tienes pagos pendientes');
                                        }
                                    }
                                }
                                $Borrartablatemp =  DB::select('truncate table temptblnominas_pagodet');
    
    
                                //APLICACION DE MOVIMEINTO DE NOMINAS POR EMPLEADO
                                foreach ($varnominas as $item) {
                                    $saldoFinal = 0;
                                    if($item->total_nomina_fiscal > 0){
                                        $varobtenercuentas =$this->obtenercuentasPrincipales($cuentaFiscal);
                                        foreach($varobtenercuentas as $varobtenercuenta){$saldo = $varobtenercuenta->saldo_actual;$nombreCaja = $varobtenercuenta->descripcion;}
    
                                        $saldoFinal = $saldo - $item->total_nomina_fiscal;
                                        $historialcuent = new  historial_cuentas();
                                        $historialcuent->id_cuenta = $cuentaFiscal;
                                        $historialcuent->id_empleado =auth()->user()->idempleado;
                                        $historialcuent->estado = "A";
                                        $historialcuent->tipo_movimiento = "GASTO";
                                        $historialcuent->concepto = "SUELDO FISCAL";
                                        $historialcuent->descripcion ="PAGO FISCAL #".$item->id;
                                        $historialcuent->responsable = "EMPLEADO #".$item->idempleado;
                                        $historialcuent->ingreso = 0;
                                        $historialcuent->egreso = $item->total_nomina_fiscal;
                                        $historialcuent->saldo =  $saldoFinal;
                                        $historialcuent->numero_referencia = 3;
                                        $historialcuent->tipo_referencia = "gastos";
                                        $historialcuent->numero_poliza = 0;
                                        $historialcuent->fecha = $fecha;
                                        $historialcuent->created_by = auth()->user()->name;
                                        $historialcuent->save();
    
                                        if($historialcuent->save()){
                                            $movcuent =$this->obtenerultimomovcuenta();
                                            foreach($movcuent as $cue){$movId = $cue->id;}
                                            $poliza = historial_cuentas::find($movId);
                                            $poliza->numero_poliza = "CU00".$movId;
                                            $poliza->updated_by = auth()->user()->name;
                                            $poliza->save();
    
                                            $cuentas = cuentas::find($cuentaFiscal);
                                            $cuentas->saldo_actual = $saldoFinal;
                                            $cuentas->updated_by = auth()->user()->name;
                                            $cuentas->save();
                                        }else{}
                                    } 
    
                                    $saldoFinal = 0;
                                    if($item->total_apagar_excedente > 0){
                                        $varobtenercuentas =$this->obtenercuentasPrincipales($cuentaExcedente);
                                        foreach($varobtenercuentas as $varobtenercuenta){$saldo = $varobtenercuenta->saldo_actual;$nombreCaja = $varobtenercuenta->descripcion;}
    
                                        $saldoFinal = $saldo - $item->total_apagar_excedente;
                                        $historialcuent = new  historial_cuentas();
                                        $historialcuent->id_cuenta = $cuentaExcedente;
                                        $historialcuent->id_empleado =auth()->user()->idempleado;
                                        $historialcuent->estado = "A";
                                        $historialcuent->tipo_movimiento = "GASTO";
                                        $historialcuent->concepto = "SUELDO EXCEDENTE";
                                        $historialcuent->descripcion ="PAGO EXCEDENTE #".$item->id;
                                        $historialcuent->responsable = "EMPLEADO #".$item->idempleado;
                                        $historialcuent->ingreso = 0;
                                        $historialcuent->egreso = $item->total_apagar_excedente;
                                        $historialcuent->saldo =  $saldoFinal;
                                        $historialcuent->numero_referencia = 12;
                                        $historialcuent->tipo_referencia = "gastos";
                                        $historialcuent->numero_poliza = 0;
                                        $historialcuent->fecha = $fecha;
                                        $historialcuent->created_by = auth()->user()->name;
                                        $historialcuent->save();
    
                                        if($historialcuent->save()){
                                            $movcuent =$this->obtenerultimomovcuenta();
                                            foreach($movcuent as $cue){$movId = $cue->id;}
                                            $poliza = historial_cuentas::find($movId);
                                            $poliza->numero_poliza = "CU00".$movId;
                                            $poliza->updated_by = auth()->user()->name;
                                            $poliza->save();
    
                                            $cuentas = cuentas::find($cuentaExcedente);
                                            $cuentas->saldo_actual = $saldoFinal;
                                            $cuentas->updated_by = auth()->user()->name;
                                            $cuentas->save();
                                        }else{}
                                    }                                     
                                }
    
                                //CERRAMOS NOMINA
                                $pagonomenc = Nominas_pagosenc::find($id);
                                $pagonomenc->estado_nomina = 'Cerrada';
                                $pagonomenc->updated_by=auth()->user()->name;
                                $pagonomenc->save();
    
                                if($pagonomenc->save()){
                                    return redirect()->route('Nominaseditar', [
                                        'id' => $id,
                                        'idtiponomina' => $idtipodias,
                                        'fecha_ini' => $fecha_inicio,
                                        'fecha_fin' => $fecha_fin,
                                    ])->with('success_msg_large', 'Nómina cerrada y desembolsada correctamente.');
                                }
                                else{
                                    return back()->with('error_msg_large', 'No se logró guardar los cambios, intente de nuevo o más tarde');
                                }
                       
                    }else{
                        return back()->with("error_msg_large","No se logro guardar los cambios, ya que no tiene el saldo suficiente, intente de nuevo o más tarde");
                    }
                }else{
                    return back()->with("error_msg_large","No se logro guardar los cambios, ya que no tiene el saldo suficiente, intente de nuevo o más tarde");
                }
            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg_large","Error al cargar datos, intente despues o más tarde"); }
        }
    
        public function editar_empleado($id, Request $request){
            try{
                $pagonomenc = Nominas_pagosdet::find($id);
                $pagonomenc->sueldo_fiscal = $request->get('sueldo_fiscal');
                $pagonomenc->sueldo_excedente = $request->get('sueldo_excedente');
                $pagonomenc->total_sueldo = $request->get('total_sueldo');
                $pagonomenc->dias_laborados = $request->get('dias_laborados');

                // Excepciones (mismos conceptos del import Excel)
                $pagonomenc->bono = $request->get('bono', 0);
                $pagonomenc->viaticos = $request->get('viaticos', 0);
                $pagonomenc->horas_extras = $request->get('horas_extras');
                $pagonomenc->horas_extras_pago_f = $request->get('horas_extras_pago_f');
                $pagonomenc->horas_extras_pago_e = $request->get('horas_extras_pago_e');
                $pagonomenc->total_horas_extras = $request->get('total_horas_extras');
                $pagonomenc->dias_descanso = $request->get('dias_descanso');
                $pagonomenc->pago_dias_descanso = $request->get('pago_dias_descanso');
                $pagonomenc->dias_prima_dominical = $request->get('dias_prima_dominical');
                $pagonomenc->pago_prima_dominical = $request->get('pago_prima_dominical');
                $pagonomenc->percepcion_extraordinaria = $request->get('percepcion_extraordinaria');
                $pagonomenc->deudores_fiscal = $request->get('deudores_fiscal');
                $pagonomenc->total_deudores = $request->get('deudores_fiscal');
                $pagonomenc->fonacot = $request->get('fonacot');
                $pagonomenc->dias_prima_vacacional = $request->get('dias_prima_vacacional', 0);

                $pagonomenc->pago_infonavit = $request->get('pago_infonavit');
                $pagonomenc->pago_imss = $request->get('pago_imss');
                $pagonomenc->pago_isr = $request->get('pago_isr');
                $pagonomenc->pago_subsidio = $request->get('pago_subsidio');

                $pagonomenc->despensa = $request->get('despensa');
                $pagonomenc->otros = $request->get('otros');

                $pagonomenc->total_nomina_fiscal = $request->get('total_nomina_fiscal');
                $pagonomenc->total_apagar_excedente = $request->get('total_apagar_excedente');

                $pagonomenc->pago_nomina_fiscal_global = $request->get('total_nomina_fiscal');
                $pagonomenc->pago_nomina_excedente_global = $request->get('total_apagar_excedente');

                $pagonomenc->total_apagar = $request->get('total_apagar');

                $pagonomenc->updated_by=auth()->user()->name;
                if($pagonomenc->save()){
                    return back()->with("success_msg_large","¡Se guardaron los cambios correctamente!");
                }return back()->with("error_msg_large","No se logro guardar los cambios, intente de nuevo o más tarde");
            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg_large","Error al cargar datos, intente despues o más tarde"); }
        }
    
        public function nominaeliminar($id){
                NominaAsistencias::eliminarPorNomina((int) $id);
                $Borrartbl =  DB::select('delete from tblnominas_pagoenc where id = ? ', [$id]);
                return back()->with("success_msg_large","¡Se guardaron los cambios correctamente!");
        }
    
        public function nominaeliminarTemp($id){
            try{
                // solo de enc det temdet
                NominaAsistencias::eliminarPorNomina((int) $id);
                $Borrartbl =  DB::select('delete from temptblnominas_pagodet where idpagonomina = ? ', [$id]);
                $Borrartbl =  DB::select('delete from tblnominas_pagodet where idpagonomina = ? ', [$id]);
                $Borrartbl =  DB::select('delete from tblnominas_pagoenc where id = ? ', [$id]);
                return back()->with("success_msg_large","¡Se guardaron los cambios correctamente!");
            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg_large","Error al cargar datos, intente despues o más tarde"); }
        }
    
        public function nominaeliminarCalcu($id, string $fecha_inicio, string $fecha_fin){
            try{
                $date = Carbon::now();
                $fecha = $date->format('Y-m-d');
                // Log de inicio de la función
                error_log("Inicio de la función nominaeliminarCalcu con ID: $id, Fecha Inicio: $fecha_inicio, Fecha Fin: $fecha_fin");
        
                $listacreditoniom = $this->obtenerprestmoempxfechaRevertirPago($fecha_inicio, $fecha_fin);
    
                    // PAGO DE PRESTAMOS
                    if ($listacreditoniom->isEmpty()) {
                        error_log('No tienes prestamos');
                    } else {
                        foreach ($listacreditoniom as $lista) {
                            if ($lista->estado != "A") {
                                $saldo = 0;
            
                                // Actualizamos el pago a saldado
                                $update1 = DB::select('update tblcreditosempleado_det set estado = ? where id = ?;', ["A", $lista->id]);
                                $update2 = DB::select('update tblcreditos_empleado set estado = ? where id = ?;', ["A", $lista->id_credito]);
            
                                $cuentaPrestamos = $lista->id_cuenta;
                            } else {
                                //error_log('No tienes pagos pendientes para el préstamo con ID: ' . $lista->id);
                            }
                        }
                    }
    
                    $varnominas = $this->obtenernominasporid($id);
    
                    foreach ($varnominas as $item) {
                        $fechaFormateada = date('Y-m-d', strtotime($item->created));
    
                        // Inicializar acumuladores por cada nómina procesada
                        $saldosPorCuenta = []; // Array para acumular saldo por cuenta
    
                        // Obtener los movimientos relacionados
                        $movimientos = DB::table('tblmovimientos_cuentas')
                            ->where('fecha', $fechaFormateada)
                            ->where(function ($query) {
                                $query->where('descripcion', 'like', "%PAGO%")
                                      ->orWhere('descripcion', '')
                                      ->orWhereNull('descripcion'); // Incluye valores nulos
                            })
                            ->where('responsable', 'like', "%EMPLEADO%")
                            ->where(function ($query) {
                                $query->where('tipo_referencia', 'gastos')
                                    ->orWhere('tipo_referencia', 'prestamo_emp'); // Agregamos la referencia adicional
                            })
                            ->where(function ($query) {
                                $query->where('tipo_movimiento', 'GASTO')
                                    ->orWhere('tipo_movimiento', 'PAGO'); // Agregamos el movimiento adicional
                            })
                            ->get(['id','id_cuenta', 'egreso', 'ingreso', 'tipo_movimiento']);
    
                        // Procesar movimientos y acumular saldos por cuenta
                        foreach ($movimientos as $mov) {
                            if (!isset($saldosPorCuenta[$mov->id_cuenta])) {
                                $saldosPorCuenta[$mov->id_cuenta] = ['ingreso' => 0, 'egreso' => 0];
                            }
    
                            // Dependiendo del tipo de movimiento, acumula los valores
                            if ($mov->tipo_movimiento === 'PAGO') {
                                $saldosPorCuenta[$mov->id_cuenta]['ingreso'] += $mov->ingreso;
                            } elseif ($mov->tipo_movimiento === 'GASTO') {
                                $saldosPorCuenta[$mov->id_cuenta]['egreso'] += $mov->egreso;
                            } else {
                                error_log("Tipo de movimiento desconocido: {$mov->tipo_movimiento}");
                            }
    
                            $nuevoIngreso = $mov->egreso;
                            $nuevoEgreso = $mov->ingreso;
    
                            // Actualizar los valores en la tabla tblmovimientos_cuentas
                            DB::table('tblmovimientos_cuentas')
                                ->where('id', $mov->id) // Filtrar por el id único del movimiento
                                ->update([
                                    //'ingreso' => $nuevoIngreso,
                                    //'egreso' => $nuevoEgreso,
                                    'descripcion' => 'movimiento revertido por cancelacion de nomina',
                                    //'tipo_movimiento' => $mov->tipo_movimiento === 'PAGO' ? 'GASTO' : 'PAGO', // Cambiar el tipo de movimiento
                                    'updated_at' => now(), // Actualizar la fecha de modificación
                                ]);
                        }
    
                        // Actualizar saldo de cada cuenta una vez
                        foreach ($saldosPorCuenta as $id_cuenta => $saldos) {
                            $cuenta = DB::table('tblcuentas')->where('id', $id_cuenta)->first();
                            if (!$cuenta) {
                                error_log("Cuenta no encontrada: $id_cuenta");
                                continue; // Si no encuentra la cuenta, salta
                            }
    
                            $saldoActual = $cuenta->saldo_actual;
    
                            // Calcular el nuevo saldo
                            $nuevoSaldo = $saldoActual - $saldos['ingreso'] + $saldos['egreso'];
                            $nuevoSaldoEngreso = $saldos['egreso'];
                            $nuevoSaldoIngreso = $saldos['ingreso'];
                            $nuevoSaldoConEgreso = $saldoActual - $saldos['ingreso'] + $saldos['egreso'];
                            $nuevoSaldoConIngreso = $saldoActual + $saldos['egreso'];
    
                            // Actualizar el saldo en la base de datos
                            DB::table('tblcuentas')
                                ->where('id', $id_cuenta)
                                ->update([
                                    'saldo_actual' => $nuevoSaldo,
                                    'updated_by' => auth()->user()->name,
                                ]);
                            
                                if($nuevoSaldoEngreso > 0)
                                {
                                    $historialcuent = new  historial_cuentas();
                                    $historialcuent->id_cuenta = $id_cuenta;
                                    $historialcuent->id_empleado =auth()->user()->idempleado;
                                    $historialcuent->estado = "A";
                                    $historialcuent->tipo_movimiento = "INGRESO";
                                    $historialcuent->concepto = "CANCELACION DE NOMINA";
                                    $historialcuent->descripcion ="INGRESO POR CANCELACION DE NOMINA #".$item->id;
                                    $historialcuent->responsable = "EMPLEADO #".$item->idempleado;
                                    $historialcuent->ingreso = $nuevoSaldoEngreso;
                                    $historialcuent->egreso = 0;
                                    $historialcuent->saldo =  $nuevoSaldoConIngreso;
                                    $historialcuent->numero_referencia = $item->id;
                                    $historialcuent->tipo_referencia = "tblnomina";
                                    $historialcuent->numero_poliza = 0;
                                    $historialcuent->fecha = $fecha;
                                    $historialcuent->created_by = auth()->user()->name;
                                    $historialcuent->save();
        
                                    if($historialcuent->save()){
                                        $movcuent =$this->obtenerultimomovcuenta();
                                        foreach($movcuent as $cue){$movId = $cue->id;}
                                        $poliza = historial_cuentas::find($movId);
                                        $poliza->numero_poliza = "CU00".$movId;
                                        $poliza->updated_by = auth()->user()->name;
                                        $poliza->save();
                                    }
                                }
                                if($nuevoSaldoIngreso > 0)
                                {
                                    $historialcuent = new  historial_cuentas();
                                    $historialcuent->id_cuenta = $id_cuenta;
                                    $historialcuent->id_empleado =auth()->user()->idempleado;
                                    $historialcuent->estado = "A";
                                    $historialcuent->tipo_movimiento = "GASTO";
                                    $historialcuent->concepto = "CANCELACION DE PAGO DE NOMINA";
                                    $historialcuent->descripcion ="EGRESO POR CANCELACION DE NOMINA #".$item->id." EN PAGOS DE PRESTAMOS";
                                    $historialcuent->responsable = "EMPLEADO #".$item->idempleado;
                                    $historialcuent->ingreso = 0;
                                    $historialcuent->egreso = $nuevoSaldoIngreso;
                                    $historialcuent->saldo =  $nuevoSaldoConEgreso;
                                    $historialcuent->numero_referencia = $item->id;
                                    $historialcuent->tipo_referencia = "tblnomina";
                                    $historialcuent->numero_poliza = 0;
                                    $historialcuent->fecha = $fecha;
                                    $historialcuent->created_by = auth()->user()->name;
                                    $historialcuent->save();
        
                                    if($historialcuent->save()){
                                        $movcuent =$this->obtenerultimomovcuenta();
                                        foreach($movcuent as $cue){$movId = $cue->id;}
                                        $poliza = historial_cuentas::find($movId);
                                        $poliza->numero_poliza = "CU00".$movId;
                                        $poliza->updated_by = auth()->user()->name;
                                        $poliza->save();
                                    }
                                }
                        }
                    }
    
                    // Eliminar registros en tblnomina_asistencias, tblnominas_pagodet y tblnominas_pagoenc
                    NominaAsistencias::eliminarPorNomina((int) $id);
                    $Borrartbl = DB::select('delete from tblnominas_pagodet where idpagonomina = ? ', [$id]);
                    $Borrartbl = DB::select('delete from tblnominas_pagoenc where id = ? ', [$id]);
            
                    return back()->with("success_msg_large", "¡Se guardaron los cambios correctamente!");
               
                
            } catch(\Illuminate\Database\QueryException $ex){
                // Log de error en caso de excepción
                error_log("Error en la función nominaeliminarCalcu: " . $ex->getMessage());
                return back()->with("error_msg_large", "No se logro guardar los cambios, intente de nuevo o más tarde");
            }
        }
    
        public function importar_excel(request $request, int $id){
            try{
                if($request->hasFile("urlxlsx")){
                    $file=$request->file("urlxlsx");
    
                    if($file->guessExtension()=="xlsx"){
                        Excel::import(new BoTranImport($id), request()->file('urlxlsx'));
    
                        //RECALCULAR EN BASE A LO IMPORTADO
                        $funcion = $this->calcular_nomia("Recalcular",$id," "," ");
              
                        if($funcion == "exito"){;
                            return back()->with("success_msg","Felicidades, Importado Correctamente");
                        }else{
                            return back()->with("warning_msg","Conflicto al guardar, intente de nuevo o más tarde");
                        }
                        
                    }else{return back()->with("warningExcel","¡Se guardaron los cambios correctamente!"); }            
                }
            } catch (\Illuminate\Database\QueryException $ex) {
                \Log::error('Error SQL: ' . $ex->getMessage());
                return back()->with('error_msg2', 'Error SQL: ' . $ex->getMessage());
            } catch (\Exception $e) {
                \Log::error('Error general: ' . $e->getMessage());
                return back()->with('error_msg2', 'Error: ' . $e->getMessage());
            }
        }
    
        public function recalcular_nomia(string $paso,int $id, string $fecha_inicio, string $fecha_fin){
            try{
              
                //RECALCULAR
                $funcion = $this->calcular_nomia($paso,$id,$fecha_inicio,$fecha_fin);
                
                if($funcion == "exito"){;
                    return back()->with("success_msg_large","Felicidades, Recalculado Correctamente");
                }else{
                    return back()->with("error_msg_large","Conflicto al guardar, intente de nuevo o más tarde");
                }
                        
            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg","Error al cargar datos, intente despues o más tarde"); }
        }
    
        public function exportarrptnomxsuc(string $fecha,int $idnomina){
           
                $date = Carbon::now();
                $fechas = $date->format('Y-m-d');
                $varnom =  $this-> rptenomporsucursal($idnomina);
    
    
                $Borrartbl =  DB::select('delete from temprptnomxsuc where idnomina = ? ', [$idnomina]);
                foreach($varnom as $listvarnom){
                    $inser = new rptnomx();
                    $inser->id_sucursal=$listvarnom->idsucursal;
                    $inser->nombre=$listvarnom->sucursal;
                    $inser->nomina_fiscal=$listvarnom->nomina_fiscal;
                    $inser->pagonomina_excedente=$listvarnom->pagonomina_excedente;
                    $inser->efectivo_cajas=$listvarnom->efectivo_cajas;
                    $inser->total=$listvarnom->Total;
                    $inser->idnomina=$listvarnom->idpagonomina;
                    $inser->created_by = auth()->user()->name;
                    $inser->save();    
                }
    
                return Excel::download(new rptnomxsuc($idnomina), 'NOMINA QUINCENAL_'.$fecha.'.xlsx');
    
                
        }
        
        public function exporetencionesnom(string $fecha){
            try{
                return Excel::download(new rpttetnom($fecha), 'RETENCIONES QUINCELAES_'.$fecha.'.xlsx');
            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg","Error al cargar datos, intente despues o más tarde"); }
        }
    
        public function exportar_excel(int $id){
            try{
                return Excel::download(new NominasExport($id), 'NOMINA.xlsx');
            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg","Error al cargar datos, intente despues o más tarde"); }
        }

        public function exportar_retenciones(int $id){
            try{
                $nominaEnc = Nominas_pagosenc::find($id);

                if(!$nominaEnc){
                    return back()->with('error_msg_large', "Error al descargar reporte de retenciones: No se encontró la nómina con ID {$id}.");
                }

                if(Nominas_pagosdet::where('idpagonomina', $id)->count() === 0){
                    return back()->with('error_msg_large', "Error al descargar reporte de retenciones: La nómina {$id} no tiene empleados registrados.");
                }

                $nombreArchivo = 'RETENCIONES_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $nominaEnc->nombre_nomina ?? (string) $id) . '.xlsx';

                return Excel::download(new RetencionesNominaExport($id), $nombreArchivo);
            } catch(\Illuminate\Database\QueryException $ex){
                \Log::error('Error SQL exportar retenciones', ['id' => $id, 'message' => $ex->getMessage()]);
                return back()->with('error_msg_large', 'Error SQL al descargar reporte de retenciones: ' . $ex->getMessage());
            } catch(\Throwable $e){
                \Log::error('Error exportar retenciones', ['id' => $id, 'message' => $e->getMessage()]);
                return back()->with('error_msg_large', $e->getMessage());
            }
        }
    
        public function exportar_formato_nomina(int $id){
            try{
           
                return Excel::download(new NominasExportFormat($id), 'FORMATO_IMPORTACIONES_'.$id.'.xlsx');
            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg","Error al cargar datos, intente despues o más tarde"); }
        }

        public function exportar_formato_asistencias(int $id){
            try{
                $nominaEnc = Nominas_pagosenc::find($id);

                if(!$nominaEnc){
                    return back()->with('error_msg_large', "Error al descargar formato de asistencias: No se encontró la nómina con ID {$id}.");
                }

                if(Nominas_pagosdet::where('idpagonomina', $id)->count() === 0){
                    return back()->with('error_msg_large', "Error al descargar formato de asistencias: La nómina {$id} no tiene empleados registrados.");
                }

                return Excel::download(new AsistenciasNominaExportFormat($id), 'FORMATO_ASISTENCIAS_'.$id.'.xlsx');
            } catch(\Illuminate\Database\QueryException $ex){
                \Log::error('Error SQL exportar asistencias', ['id' => $id, 'message' => $ex->getMessage()]);
                return back()->with('error_msg_large', 'Error SQL al descargar formato de asistencias: ' . $ex->getMessage());
            } catch(\Throwable $e){
                \Log::error('Error exportar asistencias', ['id' => $id, 'message' => $e->getMessage()]);
                return back()->with('error_msg_large', $e->getMessage());
            }
        }

        public function importar_asistencias(Request $request, int $id){
            try{
                if(!$request->hasFile('urlxlsx_asistencias')){
                    return back()->with('warning_msg_large', 'Error al importar asistencias: No se recibió ningún archivo. Seleccione un archivo .xlsx.');
                }

                $file = $request->file('urlxlsx_asistencias');

                if(!$file->isValid()){
                    return back()->with('warning_msg_large', 'Error al importar asistencias: El archivo no se cargó correctamente.');
                }

                $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: '');

                if($extension !== 'xlsx'){
                    return back()->with('warning_msg_large', 'Error al importar asistencias: Solo se admite formato .xlsx. Archivo recibido: ' . $file->getClientOriginalName());
                }

                $nominaEnc = Nominas_pagosenc::find($id);

                if(!$nominaEnc){
                    return back()->with('error_msg_large', "Error al importar asistencias: No se encontró la nómina con ID {$id}.");
                }

                $formato = AsistenciasFormatoDetector::detectarDesdeArchivo($file->getPathname());

                if($formato === 'reloj'){
                    Excel::import(new AsistenciasRelojImport($id), $file);
                } else {
                    Excel::import(new AsistenciasNominaImport($id), $file);
                }

                $funcion = $this->calcular_nomia('Recalcular', $id, ' ', ' ');

                if($funcion === 'exito'){
                    return redirect()
                        ->route('Nominas.asistencias', $id)
                        ->with('successExcel', 'Asistencias importadas y nómina recalculada correctamente.');
                }

                return redirect()
                    ->route('Nominas.asistencias', $id)
                    ->with('warning_msg_large', 'Las asistencias se importaron, pero hubo un problema al recalcular la nómina.');
            } catch(\Illuminate\Database\QueryException $ex){
                \Log::error('Error SQL importar asistencias', ['id' => $id, 'message' => $ex->getMessage()]);
                return back()->with('error_msg_large', 'Error SQL al importar asistencias: ' . $ex->getMessage());
            } catch(\Throwable $e){
                \Log::error('Error importar asistencias', ['id' => $id, 'message' => $e->getMessage()]);
                return back()->with('error_msg_large', $e->getMessage());
            }
        }
    
        public function exportarComprobantes(int $id){
            try{
                $varpantallas =  $this->Traermenuenc();
                $varsubmenus =   $this->Traermenudet();
                $varempleados = $this->obtenerempleados();
                // $varempresas =$this->razonSocial("Recursos Humanos");
                $varlistanomina=  $this-> recibonomina($id);
            
                $empresa =  collect(DB::select("select* from tblnominas_pagoenc
                left join tblempresas on tblempresas.id = tblnominas_pagoenc.id_empresa
                where tblnominas_pagoenc.id = ?;", [$id]))->first(); 
                
                
                $razon_social = $empresa->nombre_empresa;
                $empresa= $empresa->descripcion;
                
                foreach($varlistanomina as $nomina){
                    $fecha_nomina = $nomina->fecha_fin;
                    break;
                }
    
                set_time_limit(300);
                $pdf = \PDF::setPaper('letter')->loadView('nominas.PDF.recibos',compact('varlistanomina','razon_social','empresa'));
                return $pdf->stream("RECIBOS NOMINA_".$fecha_nomina.".pdf");

            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg","Error al cargar datos, intente despues o más tarde"); }
        }

        public function exportarListadoNomina(Request $request, int $id){
            try{
                $modo = $request->query('modo', 'completo');
                $orden = $request->query('orden', 'apellido');

                $listado = $this->buildListadoNominaData($id, $modo, $orden);

                $empresa = collect(DB::select("select * from tblnominas_pagoenc
                    left join tblempresas on tblempresas.id = tblnominas_pagoenc.id_empresa
                    where tblnominas_pagoenc.id = ?;", [$id]))->first();

                if (!$empresa) {
                    return back()->with('info_msg', 'No se encontró la nómina.');
                }

                $razon_social = $empresa->nombre_empresa ?? 'EMPRESA';
                $fechaRef = $listado['fecha_fin'] ?? $listado['fecha_inicio'] ?? date('Y-m-d');

                set_time_limit(300);
                $pdf = \PDF::setPaper('letter')->loadView('nominas.PDF.listado', compact('listado', 'razon_social'));
                return $pdf->stream('LISTADO NOMINA_'.$fechaRef.'.pdf');
            } catch(\Illuminate\Database\QueryException $ex){
                return back()->with('info_msg', 'Error al cargar datos, intente despues o más tarde');
            } catch(\Throwable $ex){
                \Log::error('Error exportar listado nómina', ['id' => $id, 'message' => $ex->getMessage()]);
                return back()->with('info_msg', 'No se pudo generar el listado de nómina.');
            }
        }

        public function exportarComprobanteEmpleado(int $idpagodet){
            try{
                $varlistanomina = $this->recibonominaEmpleado($idpagodet);

                if ($varlistanomina->isEmpty()) {
                    return back()->with('info_msg', 'No se encontró el recibo del empleado.');
                }

                $pagoencId = $varlistanomina->first()->idpagonomina;
                $empresa = collect(DB::select("select * from tblnominas_pagoenc
                    left join tblempresas on tblempresas.id = tblnominas_pagoenc.id_empresa
                    where tblnominas_pagoenc.id = ?;", [$pagoencId]))->first();

                if (!$empresa) {
                    return back()->with('info_msg', 'No se encontró la nómina del recibo.');
                }

                $razon_social = $empresa->nombre_empresa;
                $empresaDesc = $empresa->descripcion;
                $fecha_nomina = $varlistanomina->first()->fecha_fin;
                $idEmpleado = $varlistanomina->first()->id_empleado;

                set_time_limit(300);
                $pdf = \PDF::setPaper('letter')->loadView('nominas.PDF.recibos', [
                    'varlistanomina' => $varlistanomina,
                    'razon_social' => $razon_social,
                    'empresa' => $empresaDesc,
                ]);
                return $pdf->stream("RECIBO NOMINA_{$idEmpleado}_{$fecha_nomina}.pdf");
            } catch(\Illuminate\Database\QueryException $ex){
                return back()->with('info_msg', 'Error al cargar datos, intente despues o más tarde');
            }
        }

        public function actualizarEntrega(Request $request, int $idpagodet){
            try{
                $request->validate([
                    'entrega' => 'required|in:0,1',
                ]);

                $pagoDet = Nominas_pagosdet::find($idpagodet);
                if (!$pagoDet) {
                    return response()->json(['ok' => false, 'message' => 'No se encontró el detalle de nómina.'], 404);
                }

                $nominaEnc = Nominas_pagosenc::find($pagoDet->idpagonomina);
                if (!$nominaEnc) {
                    return response()->json(['ok' => false, 'message' => 'No se encontró la nómina.'], 404);
                }

                if (!in_array($nominaEnc->estado_nomina, ['Edicion', 'Cerrada'], true)) {
                    return response()->json(['ok' => false, 'message' => 'No se puede marcar entrega en este estado de nómina.'], 403);
                }

                $pagoDet->entrega = (int) $request->input('entrega');
                $pagoDet->updated_at = Carbon::now()->format('Y-m-d');
                $pagoDet->updated_by = auth()->user()->name ?? null;
                $pagoDet->save();

                return response()->json([
                    'ok' => true,
                    'message' => $pagoDet->entrega == 1 ? 'Entrega marcada.' : 'Entrega desmarcada.',
                    'entrega' => (int) $pagoDet->entrega,
                ]);
            } catch(\Illuminate\Validation\ValidationException $ex){
                return response()->json(['ok' => false, 'message' => 'Valor de entrega inválido.'], 422);
            } catch(\Throwable $ex){
                return response()->json(['ok' => false, 'message' => 'No se pudo actualizar la entrega.'], 500);
            }
        }

        public function exportarCheques(int $id){
            try{
                $varlistanomina = $this->recibonomina($id);

                $nominaEnc = collect(DB::select("
                    SELECT tblnominas_pagoenc.id,
                           tblnominas_pagoenc.id_empresa,
                           tblnominas_pagoenc.tipo_empresa,
                           tblnominas_pagoenc.fecha_fin,
                           tblempresas.cheque_ancho,
                           tblempresas.cheque_alto
                    FROM tblnominas_pagoenc
                    LEFT JOIN tblempresas ON tblempresas.id = tblnominas_pagoenc.id_empresa
                    WHERE tblnominas_pagoenc.id = ?
                ", [$id]))->first();

                if(!$nominaEnc){
                    return back()->with('error_msg_large', "No se encontró la nómina con ID {$id}.");
                }

                $anchoCm = 21.59;
                $altoCm = 9.50;

                if(!empty($nominaEnc->id_empresa)
                    && !empty($nominaEnc->cheque_ancho)
                    && !empty($nominaEnc->cheque_alto)){
                    $anchoCm = (float) $nominaEnc->cheque_ancho;
                    $altoCm = (float) $nominaEnc->cheque_alto;
                }

                $anchoPt = round($anchoCm * 72 / 2.54, 2);
                $altoPt = round($altoCm * 72 / 2.54, 2);

                $fechaNomina = $nominaEnc->fecha_fin ?? now()->format('Y-m-d');

                set_time_limit(300);
                $pdf = \PDF::loadView('nominas.PDF.cheques', compact('varlistanomina', 'anchoCm', 'altoCm'))
                    ->setPaper([0, 0, $anchoPt, $altoPt])
                    ->setOption('dpi', 96)
                    ->setOption('isHtml5ParserEnabled', true)
                    ->setOption('isRemoteEnabled', true);

                return $pdf->stream('CHEQUES_NOMINA_' . $fechaNomina . '.pdf');
            } catch(\Illuminate\Database\QueryException $ex){
                return back()->with('info_msg', 'Error al cargar datos, intente despues o más tarde');
            } catch(\Throwable $e){
                \Log::error('Error exportar cheques', ['id' => $id, 'message' => $e->getMessage()]);
                return back()->with('error_msg_large', $e->getMessage());
            }
        }
    
        public function editarnomemp($id,$idemple){
            try{
                $varpantallas =  $this->Traermenuenc();
                $varsubmenus =   $this->Traermenudet();
                $varnomem= $this->obteneempleadonomaeditar($id,$idemple);
                return view('nominas.editarnominaempleado',compact('varpantallas','varsubmenus','varnomem'));
            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg","Error al cargar datos, intente despues o más tarde"); }
        }
    
        public function actualizarNominaEmp(request $request){
            try{
                $permisos = $this->forpermisos('actualizar_nominas_empleado'); 
                if($permisos=="actualizar_nominas_empleado"){
                    $date = Carbon::now();
                    $fecha = $date->format('Y-m-d');
                    $id = $request->get('idpadodet');
                    
                    $nomina = Nominas_pagosdet::find($id);
                    $nomina->dias_laborados = $request->post('dias_laborados');
                    $nomina->deudores_fiscal = $request->post('deudores_fiscal');
                    $nomina->deudores_no_fiscal = $request->post('deudores_no_fiscal');
                    $nomina->updated_at=$fecha;
                    $nomina->updated_by=auth()->user()->name;
                    $nomina->save();
    
                    return back()->with("success_msg_large","¡Se guardaron los cambios correctamente!");
                }else{
                    return redirect()->route('vernominas')->with("error_msg_large","No se logro");  
                }
            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg_large","Error al cargar datos, intente despues o más tarde"); }
        }
        
        public function exportarnomxqui(int $id){
            try
            {
                return Excel::download(new importarnominafiscal($id), 'Depositos'.'.xlsx');
        
            }catch(\Illuminate\Database\QueryException $ex){
                 return back()->with("info_msg_large","Error al cargar datos, intente despues o más tarde"); 
            }
        
       }

    //ISR
        public function patalla_isr(Request $request){
            try{
                $varpantallas =  $this->Traermenuenc();
                $varsubmenus =   $this->Traermenudet();
                $tiposNomina = tipo_nominas::orderBy('id')->get();
                $tipoSemanal = (int) (tipo_nominas::where('tipo', 'Semanal')->value('id') ?? 1);
                $tipoActivo = (int) $request->get('tipo', $tipoSemanal);

                $isrPorTipo = [];
                foreach ($tiposNomina as $tipo) {
                    $isrPorTipo[$tipo->id] = tarifa_isr::where('id_tipo_nomina', $tipo->id)
                        ->orderBy('limite_inferior')
                        ->get();
                }

                return view('nominas.catalogos.isr', compact(
                    'varpantallas',
                    'varsubmenus',
                    'tiposNomina',
                    'isrPorTipo',
                    'tipoActivo'
                ));
            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg","Error al cargar datos, intente despues o más tarde"); }
        }

        public function actualizar_isr_masivo(Request $request){
            try{
                $validated = $request->validate([
                    'id_tipo_nomina' => ['required', 'integer', 'exists:tbltipo_nominas,id'],
                    'filas' => ['required', 'array', 'min:1'],
                    'filas.*.limite_inferior' => ['required', 'numeric'],
                    'filas.*.limite_superior' => ['required', 'numeric'],
                    'filas.*.cuota_fija' => ['required', 'numeric'],
                    'filas.*.porcentaje' => ['required', 'numeric'],
                    'eliminar' => ['nullable', 'array'],
                    'eliminar.*' => ['integer'],
                ]);

                $idTipo = (int) $validated['id_tipo_nomina'];
                $usuario = auth()->user()->name;

                DB::transaction(function () use ($validated, $idTipo, $usuario) {
                    if (!empty($validated['eliminar'])) {
                        tarifa_isr::query()
                            ->where('id_tipo_nomina', $idTipo)
                            ->whereIn('id', $validated['eliminar'])
                            ->delete();
                    }

                    foreach ($validated['filas'] as $fila) {
                        $payload = [
                            'limite_inferior' => $fila['limite_inferior'],
                            'limite_superior' => $fila['limite_superior'],
                            'cuota_fija' => $fila['cuota_fija'],
                            'porcentaje' => $fila['porcentaje'],
                            'updated_by' => $usuario,
                        ];

                        if (!empty($fila['id']) && is_numeric($fila['id'])) {
                            tarifa_isr::query()
                                ->where('id', (int) $fila['id'])
                                ->where('id_tipo_nomina', $idTipo)
                                ->update($payload);
                        } else {
                            tarifa_isr::create(array_merge($payload, [
                                'id_tipo_nomina' => $idTipo,
                                'created_by' => $usuario,
                            ]));
                        }
                    }
                });

                return redirect()
                    ->route('Nominas.patalla_isr', ['tipo' => $idTipo])
                    ->with('success_msg_large', 'Tarifas ISR actualizadas correctamente.');
            } catch(\Illuminate\Database\QueryException $ex){
                return back()->with("info_msg_large","Error al guardar las tarifas ISR, intente de nuevo.");
            }
        }

        public function editar_isr(Request $request, $id){
            try{
                $tarifa_isr = tarifa_isr::find($id);
                $tarifa_isr->limite_inferior = $request->get('limite_inferior');
                $tarifa_isr->limite_superior = $request->get('limite_superior');
                $tarifa_isr->cuota_fija = $request->get('cuota_fija');
                $tarifa_isr->porcentaje = $request->get('porcentaje');
                $tarifa_isr->updated_by=auth()->user()->name;

                if( $tarifa_isr->save()){
                    return back()->with("success_msg_large","exito");
                }else{
                    return back()->with("error_msg_large","no uardadoexito");
                }

            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg_large","Error al cargar datos, intente despues o más tarde"); }
        }

        public function eliminar_isr(Request $request, $id){
            try{

                $Borrartbl =  DB::select('delete from tbltarifas_isr where id = ? ', [$id]);
                return back()->with("success_msg_large","exito");
                
            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg_large","Error al cargar datos, intente despues o más tarde"); }
        }

    //SUBSIDIO
        public function patalla_subsidio(){
            try{
                $varpantallas =  $this->Traermenuenc();
                $varsubmenus =   $this->Traermenudet();
                $varsubsidio =  $this->seleciona_subsidio();

                $visorFiscal = conceptos_nomina::obtenerVisorFiscal();
                $conceptos_nomina = conceptos_nomina::where('nombre', '!=', conceptos_nomina::VISOR_FISCAL)
                    ->orderBy('grupo')
                    ->orderBy('seccion')
                    ->orderBy('descripcion')
                    ->get()
                    ->groupBy(['grupo', 'seccion']);
            
                return view('nominas.catalogos.subsidio',compact('varpantallas','varsubmenus','varsubsidio','conceptos_nomina','visorFiscal'));
            } catch(\Illuminate\Database\QueryException $ex){
                \Log::error('Error al cargar factores generales', ['error' => $ex->getMessage()]);
                return back()->with('error_msg_large', 'No se pudieron cargar los factores generales: ' . $ex->getMessage());
            } catch (\Throwable $ex) {
                \Log::error('Error inesperado al cargar factores generales', ['error' => $ex->getMessage()]);
                return back()->with('error_msg_large', 'Error inesperado al cargar factores generales: ' . $ex->getMessage());
            }
        }

        public function editar_concepto(Request $request, $id){
            try{
                $concepto = conceptos_nomina::find($id);

                if (!$concepto) {
                    return back()->with('error_msg_large', 'No se encontró el concepto de nómina solicitado.');
                }

                $valor = $request->get('valor');
                if ($valor === null || $valor === '') {
                    return back()->with('error_msg_large', 'Debe capturar un valor para el concepto.');
                }

                if ($concepto->nombre === conceptos_nomina::VISOR_FISCAL) {
                    $concepto->valor = (int) ((float) $valor >= 1);
                } else {
                    $concepto->valor = $valor;

                    if ($request->has('valor2')) {
                        $concepto->valor2 = $request->get('valor2');
                    }
                }

                if ($concepto->save()) {
                    $mensaje = $concepto->nombre === conceptos_nomina::VISOR_FISCAL
                        ? 'Visor Fiscal ' . ((int) $concepto->valor === 1 ? 'activado' : 'desactivado') . ' correctamente.'
                        : 'Concepto editado correctamente.';

                    return back()->with('success_msg_large', $mensaje);
                }

                return back()->with('error_msg_large', 'No se pudo guardar el concepto de nómina.');
            } catch(\Illuminate\Database\QueryException $ex){
                \Log::error('Error al editar concepto de nómina', ['id' => $id, 'error' => $ex->getMessage()]);
                return back()->with('error_msg_large', 'Error al guardar el concepto: ' . $ex->getMessage());
            } catch (\Throwable $ex) {
                \Log::error('Error inesperado al editar concepto de nómina', ['id' => $id, 'error' => $ex->getMessage()]);
                return back()->with('error_msg_large', 'Error inesperado al guardar el concepto: ' . $ex->getMessage());
            }
        }
    
        public function editar_subsidio(Request $request, $id){
            try{
                $tarifa_subsidio = tarifa_subsidio::find($id);
                $tarifa_subsidio->uma = $request->get('uma');
                $tarifa_subsidio->cuota_fija = $request->get('cuota_fija');
                $tarifa_subsidio->limite_ingresos = $request->get('limite_ingresos');
                $tarifa_subsidio->updated_by=auth()->user()->name;
    
                if( $tarifa_subsidio->save()){
                    return back()->with("success_msg_large","exito");
                }else{
                    return back()->with("error_msg_large","no uardadoexito");
                }
            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg_large","Error al cargar datos, intente despues o más tarde"); }
        }
    
        public function eliminar_subsidio(Request $request, $id){
            try{
    
                $Borrartbl =  DB::select('delete from tbltarifas_subsidio where id = ? ', [$id]);
                return back()->with("success_msg_large","exito");
                
            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg_large","Error al cargar datos, intente despues o más tarde"); }
        }

    //IMSS
        public function patalla_imss(){
            try{
                $varpantallas =  $this->Traermenuenc();
                $varsubmenus =   $this->Traermenudet();
                $vartarifas_integracion = tarifas_integracion::orderBy('min_años')->get();
            
                return view('nominas.catalogos.imss',compact('varpantallas','varsubmenus','vartarifas_integracion'));
            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg_large","Error al cargar datos, intente despues o más tarde"); }
        }

        public function actualizar_imss_masivo(Request $request){
            try{
                $validated = $request->validate([
                    'filas' => ['required', 'array', 'min:1'],
                    'filas.*.min_años' => ['required', 'numeric'],
                    'filas.*.max_años' => ['required', 'numeric'],
                    'filas.*.dias_aguinaldo' => ['required', 'numeric'],
                    'filas.*.dias_vacaciones' => ['required', 'numeric'],
                    'filas.*.prima_vacacional' => ['required', 'numeric'],
                    'filas.*.factor_integracion' => ['required', 'numeric'],
                    'eliminar' => ['nullable', 'array'],
                    'eliminar.*' => ['integer'],
                ]);

                $usuario = auth()->user()->name;

                DB::transaction(function () use ($validated, $usuario) {
                    if (!empty($validated['eliminar'])) {
                        tarifas_integracion::query()
                            ->whereIn('id', $validated['eliminar'])
                            ->delete();
                    }

                    foreach ($validated['filas'] as $fila) {
                        $payload = [
                            'min_años' => $fila['min_años'],
                            'max_años' => $fila['max_años'],
                            'dias_aguinaldo' => $fila['dias_aguinaldo'],
                            'dias_vacaciones' => $fila['dias_vacaciones'],
                            'prima_vacacional' => $fila['prima_vacacional'],
                            'factor_integracion' => $fila['factor_integracion'],
                            'updated_by' => $usuario,
                        ];

                        if (!empty($fila['id']) && is_numeric($fila['id'])) {
                            tarifas_integracion::query()
                                ->where('id', (int) $fila['id'])
                                ->update($payload);
                        } else {
                            tarifas_integracion::create(array_merge($payload, [
                                'created_by' => $usuario,
                            ]));
                        }
                    }
                });

                return redirect()
                    ->route('Nominas.patalla_imss')
                    ->with('success_msg_large', 'Factores de integración actualizados correctamente.');
            } catch(\Illuminate\Database\QueryException $ex){
                return back()->with('info_msg_large', 'Error al guardar los factores IMSS, intente de nuevo.');
            }
        }
    
        public function editar_imss(Request $request, $id){
            try{
    
                $tarifas_integracion = tarifas_integracion::find($id);
                $tarifas_integracion->min_años = $request->get('min_años');
                $tarifas_integracion->max_años = $request->get('max_años');
                $tarifas_integracion->dias_aguinaldo = $request->get('dias_aguinaldo');
                $tarifas_integracion->dias_vacaciones = $request->get('dias_vacaciones');
                $tarifas_integracion->prima_vacacional = $request->get('prima_vacacional');
                $tarifas_integracion->factor_integracion = $request->get('factor_integracion');
                $tarifas_integracion->updated_by=auth()->user()->name;
    
                if($tarifas_integracion->save()){
                    return back()->with("success_msg_large","exito");
                }else{
                    return back()->with("error_msg_large","no uardadoexito");
                }
    
            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg_large","Error al cargar datos, intente despues o más tarde"); }
        }
    
        public function eliminar_imss(Request $request, $id){
            try{
    
                $Borrartbl =  DB::select('delete from tbltarifas_integracion where id = ? ', [$id]);
                return back()->with("success_msg_large","Eliminado correctamente");
                
            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg_large","Error al cargar datos, intente despues o más tarde"); }
        }

    //CESANTIA Y VEJEZ
        public function patalla_cesantia_vejez(){
            try{
                $varpantallas =  $this->Traermenuenc();
                $varsubmenus =   $this->Traermenudet();
                $varcesantia_vejez = cesantia_vejez::orderBy('sbc_minimo')->get();

                return view('nominas.catalogos.cesantia_vejez', compact(
                    'varpantallas',
                    'varsubmenus',
                    'varcesantia_vejez'
                ));
            } catch(\Illuminate\Database\QueryException $ex){
                return back()->with("info_msg_large","Error al cargar datos, intente despues o más tarde");
            }
        }

        public function actualizar_cesantia_vejez_masivo(Request $request){
            try{
                $validated = $request->validate([
                    'filas' => ['required', 'array', 'min:1'],
                    'filas.*.sbc_minimo' => ['required', 'numeric'],
                    'filas.*.sbc_maximo' => ['required', 'numeric'],
                    'filas.*.sbc_pesos_minimo' => ['required', 'numeric'],
                    'filas.*.sbc_pesos_maximo' => ['required', 'numeric'],
                    'filas.*.cuota_patronal' => ['required', 'numeric'],
                    'eliminar' => ['nullable', 'array'],
                    'eliminar.*' => ['integer'],
                ]);

                DB::transaction(function () use ($validated) {
                    if (!empty($validated['eliminar'])) {
                        cesantia_vejez::query()
                            ->whereIn('id', $validated['eliminar'])
                            ->delete();
                    }

                    foreach ($validated['filas'] as $fila) {
                        $payload = [
                            'sbc_minimo' => $fila['sbc_minimo'],
                            'sbc_maximo' => $fila['sbc_maximo'],
                            'sbc_pesos_minimo' => $fila['sbc_pesos_minimo'],
                            'sbc_pesos_maximo' => $fila['sbc_pesos_maximo'],
                            'cuota_patronal' => $fila['cuota_patronal'],
                        ];

                        if (!empty($fila['id']) && is_numeric($fila['id'])) {
                            cesantia_vejez::query()
                                ->where('id', (int) $fila['id'])
                                ->update($payload);
                        } else {
                            cesantia_vejez::create($payload);
                        }
                    }
                });

                return redirect()
                    ->route('Nominas.patalla_cesantia_vejez')
                    ->with('success_msg_large', 'Tarifas de cesantía y vejez actualizadas correctamente.');
            } catch(\Illuminate\Database\QueryException $ex){
                return back()->with('info_msg_large', 'Error al guardar las tarifas de cesantía y vejez, intente de nuevo.');
            }
        }
    
        public function Exportarlayoutnomina(int $id)
        {
            $enter = "\n";
            $fechai = Carbon::now();
            $date = substr($fechai,0,10);
            $fechaHoy = Carbon::now();
            $fechaActual = $fechaHoy->format('Y-m-d');
            $fechaActual = str_replace('-', '', $fechaActual);
        
            $fechaHoy1 = Carbon::now();
            $fechaAñadida1 = $fechaHoy1->addDay(8);
            $fechaAñadida = $fechaAñadida1->format('Y-m-d');
            $now = Carbon::now();
            $format1 = $now->format('d_m_Y h_i_s a');
            $format2 = $now->format('d.m.Y h.i A');
            $EncabezadoTotales = $this->EncabezadoTotales($id);
    
            //ENCABEZADO
                $tiporegistrovlave = 'HNE';
                $emisora = '07278';
                $consecutivo = '01';
                foreach($EncabezadoTotales as $item){
                    $totalRegistros = $item->registros;
                    $totalPagos = str_replace('.', '', $item->total);
                }
    
                $c_resgistros = 6 - strlen($totalRegistros);
                $c_total = 15 - strlen($totalPagos);
                $totalRegistros = substr("000000", 0, $c_resgistros).$totalRegistros;
                $totalPagos = substr("000000000000000", 0, $c_total).$totalPagos;
    
                $numeroaltas = '000000';
                $importetotalatas = '000000000000000';
                $numerobajas = '000000';
                $importetotalbajas = '000000000000000';
                $numerocuentasanoti = '000000';
                $accion = '000000000000000000000000000000000000000000000000000000000000000000000000000000';
                $filter = '';
                
                $ENCABEZADO = $tiporegistrovlave.$emisora.$fechaActual.$consecutivo.$totalRegistros.$totalPagos.$numeroaltas.$importetotalatas.$numerobajas.$importetotalbajas.$numerocuentasanoti.$accion.$filter;
            
            //LINEAS POR EMPLEADO A DEPOSITAR
                $infocuerpo = $this->Listadoinfolayout($id);
                $ceroscomnumeroemp = '';
                $variablecerosn_e = '';
                $cerosimporte = '';
                $variablecerosimporte = '';
                $cerosdecuenta = '00000000';
                $CUERPO = '';
    
                foreach($infocuerpo as $infc)
                {
                    //ID DE LA BANCA
                    $c_idbanca = 10 - strlen($infc->idbanca);
                    $idbanca = substr("0000000000", 0, $c_idbanca).$infc->idbanca;
    
                    //IMPORTE A PAGAR
                    $importe = str_replace('.', '', $infc->importe);
                    $c_importe = 15 - strlen($importe);
                    $importe = substr("000000000000000", 0, $c_importe).$importe;
    
                    //NUMERO DE CUENTA
                    $c_numero_cuenta = 18 - strlen($infc->numero_cuenta);
                    $numero_cuenta = substr("000000000000000000", 0, $c_numero_cuenta).$infc->numero_cuenta;
    
                    
                    $CUERPO = $CUERPO.$enter.
                    $infc->tipo_registro.
                    $fechaActual.
                    $idbanca.
                    $infc->referencia_servicioyOrdenante.
                    $importe.
                    $infc->numero_banco_receptor.
                    $infc->tipo_cuenta.
                    $numero_cuenta.
                    $infc->tipo_movimiento.
                    $infc->accion.
                    $infc->importe_ivadelaoperacion.
                    $infc->filtro;
                    
                    $variablecerosn_e = '';
                    $variablecerosimporte = '';
                }
                
            //DESCARGA PAG
                $nombrearchivobbva = 'NI0727801.PAG';
                //le informamos que será un archivo txt
                header('Content-type: application/txt');
                //también le damos un nombre
                header('Content-Disposition: attachment; filename='.$nombrearchivobbva);
                //generamos el contenido del archivo
                return $ENCABEZADO.$CUERPO; 
    
              
        }

        /**
         * Layout Banorte (pago a proveedores/terceros).
         * Archivo de texto delimitado por tabulaciones, sin encabezado.
         */
        public function ExportarlayoutBanorte(int $id)
        {
            $tab = "\t";
            $enter = "\r\n";
            $lineas = [];

            $cuentaOrigen = $this->obtenerCuentaOrigenBanorte();
            $fechaAplicacion = Carbon::now()->format('dmY');
            $infocuerpo = $this->ListadoinfolayoutBanorte($id);

            foreach ($infocuerpo as $infc) {
                $cuentaDestino = preg_replace('/\D+/', '', (string) ($infc->numero_cuenta ?: $infc->idbanca));
                if ($cuentaDestino === '') {
                    continue;
                }

                $lenDestino = strlen($cuentaDestino);
                if ($lenDestino === 18) {
                    $operacion = '04'; // SPEI / CLABE
                } else {
                    // Cuentas Banorte: 10 dígitos (respetar ceros a la izquierda)
                    $cuentaDestino = str_pad(substr($cuentaDestino, 0, 10), 10, '0', STR_PAD_LEFT);
                    $operacion = '02'; // Transferencia a terceros Banorte
                }

                $claveId = substr((string) $infc->idempleado, 0, 13);
                $importe = number_format((float) $infc->importe, 2, '.', '');

                $referencia = (string) $infc->idempleado;
                if (in_array($operacion, ['04', '05'], true)) {
                    $referencia = str_pad(substr($referencia, 0, 7), 7, '0', STR_PAD_LEFT);
                } else {
                    $referencia = substr($referencia, 0, 10);
                }

                $nombre = trim(preg_replace(
                    '/\s+/',
                    ' ',
                    ($infc->primer_nombre ?? '') . ' ' . ($infc->segundo_nombre ?? '') . ' ' .
                    ($infc->apellido_paterno ?? '') . ' ' . ($infc->apellido_materno ?? '')
                ));
                $descripcionBase = 'PAGO NOMINA ' . ($infc->nombre_nomina ?: $nombre);
                $descripcion = $this->sanitizarTextoBanorte($descripcionBase, 30);

                $cuentaOrigenPad = str_pad(substr(preg_replace('/\D+/', '', (string) $cuentaOrigen), 0, 10), 10, '0', STR_PAD_LEFT);
                $rfcOrdenante = strtoupper(substr(preg_replace('/\s+/', '', (string) ($infc->rfc_ordenante ?? '')), 0, 13));
                $iva = '0';
                $instruccionPago = in_array($operacion, ['01', '02', '05', '07'], true) ? 'X' : '';
                $claveTipoCambio = '0';

                // Apostrofe en campos numéricos con posible cero a la izquierda
                $lineas[] = implode($tab, [
                    "'" . $operacion,
                    $claveId,
                    "'" . $cuentaOrigenPad,
                    "'" . $cuentaDestino,
                    $importe,
                    $referencia,
                    $descripcion,
                    $rfcOrdenante,
                    $iva,
                    $fechaAplicacion,
                    $instruccionPago,
                    $claveTipoCambio,
                ]);
            }

            $contenido = implode($enter, $lineas);
            $nombreArchivo = 'LAYOUT_BANORTE_' . $id . '_' . Carbon::now()->format('Ymd_His') . '.txt';

            return response($contenido, 200, [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $nombreArchivo . '"',
            ]);
        }

        private function sanitizarTextoBanorte(string $texto, int $maxLen): string
        {
            $mapa = [
                'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
                'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
                'ñ' => 'n', 'Ñ' => 'N', 'ü' => 'u', 'Ü' => 'U', '&' => 'Y',
            ];
            $texto = strtr($texto, $mapa);
            $texto = preg_replace('/[^A-Za-z0-9 .\\-\/]/', '', $texto) ?? '';
            $texto = trim(preg_replace('/\s+/', ' ', $texto) ?? '');

            return substr($texto, 0, $maxLen);
        }
    
        public function ExpotArchivoDispersion(int $id){
            try{
           
                return Excel::download(new ExpotArchivoDispersion($id), 'FORMATO_DISPERSION_'.$id.'.xlsx');
            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg_large","Error al cargar datos, intente despues o más tarde"); }
        }

        public function Actualizanom()
        {
            return 1;
        }
    
    //AGUINALDO
        public function index_aguinaldos(){
            try{
                $varpantallas =  $this->Traermenuenc();
                $varsubmenus =   $this->Traermenudet();
                $varaguinaldos =  $this->obtener_aguinaldos_enc();
                $date = Carbon::now();
                $date = $date->format('Y-m-d');
                $idusuario=auth()->user()->id;
            

                return view('nominas.aguinaldo.index',compact('varpantallas','varsubmenus','varaguinaldos'));
            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg_large","Error al cargar datos, intente despues o más tarde"); }
        }

        public function crear_aguinaldos(Request $request){
            try{
                $aguinaldos_enc = new aguinaldos_enc();
                $aguinaldos_enc->nombre = $request->get('nombre');
                $aguinaldos_enc->estado = "EDICION";
                $aguinaldos_enc->fecha_pago = $request->get('fecha_pago');
                $aguinaldos_enc->created_by = auth()->user()->name;   

                if($aguinaldos_enc->save()){
                    $save = 0;
                    $registros = 0;
                    $datos =  $this->datos_empleados_activos();

                    //contador
                    $registros = count($datos);

                    //RECORRER EMPLEADOS 
                    foreach($datos as $item){

                        $idempleado = $item->idempleado;
                        $idnomina = $item->id_nomina;
                        $fecha_ingreso = $item->fecha_ingreso;
                        $salario_diario = $item->salario_fijo;
                        $salario_excedente = $item->excedente;
                        $sueldo_mensual = $item->salario_bruto;

                        $funcion = $this->calcular_aguinaldo("CALCULAR", $aguinaldos_enc->id, $idempleado ,$idnomina,$fecha_ingreso,$salario_diario,$salario_excedente,$sueldo_mensual,0);

                        if($funcion == "exito"){
                                $save++;
                        }
                    }
            
                    if($save >= $registros){
                        return back()->with("success_msg_large","Recalculado con exito, generado correctamente");
                    }else{
                        return back()->with("error_msg_large","Conflicto al generar el calculo de aguinaldo, pruebe de nuevo o consulte al administrador");
                    } 
                }else{
                    return back()->with("error_msg_large","Conflicto al generar el calculo de aguinaldo, pruebe de nuevo o consulte al administrador");
                } 
            
            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg_large","Error al cargar datos, intente despues o más tarde"); }
        }

        public function editar_aguinaldos($id){
            try{
                $varpantallas =  $this->Traermenuenc();
                $varsubmenus =   $this->Traermenudet();
                $aguinaldos_enc = aguinaldos_enc::find($id);
                $aguinaldos_det =  $this->obtener_aguinaldos_det($id);
                $validaAguinaldoCero =  $this->validaAguinaldoCero($id);
                $varcuentas  = $this->obtenercuentasActivas();
                $dias_aguinaldo = conceptos_nomina::where('nombre', 'dias_aguinaldo')->first();
                $validaTimbrado = 0;
                $validaTimbrado = collect(DB::select('select ifnull(count(recibos_aguinaldo.id),0) as timbres from recibos_aguinaldo 
                inner join tblaguinaldos_det on tblaguinaldos_det.id = recibos_aguinaldo.id_tblnominas_pagodet
                inner join tblaguinaldos_enc on tblaguinaldos_det.id_aguinaldo = tblaguinaldos_enc.id
                where  tblaguinaldos_enc.id = ? ', [$id]))->first()->timbres;

                return view('nominas.aguinaldo.editar',compact('varpantallas','varsubmenus','varcuentas',
                'aguinaldos_enc','aguinaldos_det','validaAguinaldoCero','dias_aguinaldo','validaTimbrado'));
            
            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg","Error al cargar datos, intente despues o más tarde"); }
        }

        public function calcular_aguinaldos(int $id){
            // try{
                $save = 0;
                $registros = 0;
                $datos =  $this->obtener_aguinaldos_det($id);

                //contador
                $registros = count($datos);


                //RECORRER EMPLEADOS 
                foreach($datos as $item){

                    $idempleado = $item->idempleado;
                    $idnomina = $item->id_nomina;
                    $fecha_ingreso = $item->fecha_ingreso;
                    $salario_diario = $item->salario_fijo;
                    $salario_excedente = $item->excedente;
                    $sueldo_mensual = $item->salario_bruto;

                    $funcion = $this->calcular_aguinaldo("RECALCULAR", $id, $idempleado ,$idnomina,$fecha_ingreso,$salario_diario,$salario_excedente,$sueldo_mensual,0);

                    if($funcion == "exito"){
                            $save++;
                    }
                }
        
                if($save >= $registros){
                    $aguinaldos_enc = aguinaldos_enc::find($id);
                    $aguinaldos_enc->estado = 'EDICION';
                    $aguinaldos_enc->updated_by=auth()->user()->name;
                    $aguinaldos_enc->save();

                    return back()->with("success_msg_large","Recalculado con exito, generado correctamente");
                }else{
                    return back()->with("error_msg_large","Conflicto al generar el calculo de aguinaldo, pruebe de nuevo o consulte al administrador");
                } 
                      
            // } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warning_msg","no guardado correctamente"); }
        }

        public function editar_dias_aguinaldo(int $id, Request $request){
            // try{
                $idempleado = $request->get('idempleado');
                $idnomina = $request->get('idnomina');
                $fecha_ingreso = $request->get('fecha_ingreso');
                $salario_diario = $request->get('salario_diario');
                $salario_excedente = $request->get('salario_excedente');
                $dias_aguinaldo_pagados = $request->get('dias_aguinaldo_pagados');
                $sueldo_mensual = $request->get('sueldo_mensual');

                if(!is_null($dias_aguinaldo_pagados) && $dias_aguinaldo_pagados >= 0){
                    $funcion = $this->calcular_aguinaldo("RECALCULAR", $id, $idempleado ,$idnomina,$fecha_ingreso,$salario_diario,$salario_excedente,$sueldo_mensual,$dias_aguinaldo_pagados);

                    if($funcion == "exito"){
                        return back()->with("success_msg_large","Recalculado con exito, generado correctamente");
                    }else{
                        return back()->with("error_msg_large","Conflicto al generar el calculo de aguinaldo, pruebe de nuevo o consulte al administrador");
                    } 
                }else{
                    return back()->with("error_msg_large","El campo dias aguinaldo pagados no puede estar vacio o ser menor a 0");
                }
                
                      
            // } catch(\Illuminate\Database\QueryException $ex){  return back()->with("warning_msg","no guardado correctamente"); }
        }

        public function eliminar_aguinaldos(){
            try{
                $varpantallas =  $this->Traermenuenc();
                $varsubmenus =   $this->Traermenudet();
                $varaguinaldos =  $this->obtener_aguinaldos_enc();
                $date = Carbon::now();
                $date = $date->format('Y-m-d');
                $idusuario=auth()->user()->id;
            

                return view('nominas.aguinaldo.index',compact('varpantallas','varsubmenus','varaguinaldos'));
            
            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg","Error al cargar datos, intente despues o más tarde"); }
        }

        public function exportar_excel_aguinaldos(int $id){
            try{
                return Excel::download(new AguinaldosExport($id), 'AGUINALDOS_'.$id.'.xlsx');
            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg_large","Error al cargar datos, intente despues o más tarde"); }
        }


        public function cerrar_aguinaldo(int $id, Request $request){
            try{
                $date = Carbon::now();
                $fecha = $date->format('Y-m-d');
                $idusuario=auth()->user()->id;
                $fiscalValidaCampos = 0;
                $fiscalValidaSaldo = 0;
                $excedenteValidaCampos = 0;
                $excedenteValidaSaldo = 0;
                $total_fiscal = 0;
                $total_excedente = 0;

                $total_fiscal = $total_fiscal + $request->get('total_fiscal');
                $total_excedente = $total_excedente + $request->get('total_excedente');
                $varaguinaldos = $this->obtener_aguinaldos_det($id);
            
                //GRUPAL
                $saldos_cuentas = [];
                
                function obtenerSaldoCuentaExistente(&$saldos_cuentas, $cuentaId) {
                    foreach ($saldos_cuentas as &$cuenta) {
                        if ($cuenta['id'] == $cuentaId) {
                            return $cuenta['saldo'];
                        }
                    }
                    return null;
                }
                
                function actualizarSaldoCuenta(&$saldos_cuentas, $cuentaId, $nuevoSaldo) {
                    foreach ($saldos_cuentas as &$cuenta) {
                        if ($cuenta['id'] == $cuentaId) {
                            $cuenta['saldo'] = $nuevoSaldo;
                            return;
                        }
                    }
                    $saldos_cuentas[] = ['id' => $cuentaId, 'saldo' => $nuevoSaldo];
                }
                
                if ($total_fiscal > 0 && !is_null($request->get('cuentaFiscal'))) {
                    $fiscalValidaCampos = 1;
                    $cuentaFiscal = $request->get('cuentaFiscal');
                    if (!is_null($cuentaFiscal)) {
                        $saldo_a = obtenerSaldoCuentaExistente($saldos_cuentas, $cuentaFiscal);
                        if (is_null($saldo_a)) {
                            $saldoactual = $this->obtenersaldocuenta($cuentaFiscal);
                            foreach ($saldoactual as $saldoactualcuenta) {
                                $saldo_a = $saldoactualcuenta->saldo_actual;
                            }
                        }
                        if ($saldo_a >= $total_fiscal) {
                            $fiscalValidaSaldo = 1;
                            $saldo_a -= $total_fiscal;
                        } else {
                            $fiscalValidaSaldo = 0;
                        }
                        actualizarSaldoCuenta($saldos_cuentas, $cuentaFiscal, $saldo_a);
                    }
                } elseif ($total_fiscal == 0) {
                    $fiscalValidaCampos = 1;
                    $fiscalValidaSaldo = 1;
                } else {
                    $fiscalValidaCampos = 0;
                }
                
                if ($total_excedente > 0 && !is_null($request->get('cuentaExcedente'))) {
                    $excedenteValidaCampos = 1;
                    $cuentaExcedente = $request->get('cuentaExcedente');
                    if (!is_null($cuentaExcedente)) {
                        $saldo_a = obtenerSaldoCuentaExistente($saldos_cuentas, $cuentaExcedente);
                        if (is_null($saldo_a)) {
                            $saldoactual = $this->obtenersaldocuenta($cuentaExcedente);
                            foreach ($saldoactual as $saldoactualcuenta) {
                                $saldo_a = $saldoactualcuenta->saldo_actual;
                            }
                        }
                        if ($saldo_a >= $total_excedente) {
                            $excedenteValidaSaldo = 1;
                            $saldo_a -= $total_excedente;
                        } else {
                            $excedenteValidaSaldo = 0;
                        }
                        actualizarSaldoCuenta($saldos_cuentas, $cuentaExcedente, $saldo_a);
                    }
                } elseif ($total_excedente == 0) {
                    $excedenteValidaCampos = 1;
                    $excedenteValidaSaldo = 1;
                } else {
                    $excedenteValidaCampos = 0;
                }
            

                if($fiscalValidaSaldo > 0){
                    if($excedenteValidaSaldo > 0){
                                //APLICACION DE MOVIMIENTO DE AGUINALDOS POR EMPLEADO
                                foreach ($varaguinaldos as $item) {
                                    $saldoFinal = 0;
                                    if($item->total_pagar_f > 0){
                                        $varobtenercuentas =$this->obtenercuentasPrincipales($cuentaFiscal);
                                        foreach($varobtenercuentas as $varobtenercuenta){$saldo = $varobtenercuenta->saldo_actual;$nombreCaja = $varobtenercuenta->descripcion;}

                                        $saldoFinal = $saldo - $item->total_pagar_f;
                                        $historialcuent = new  historial_cuentas();
                                        $historialcuent->id_cuenta = $cuentaFiscal;
                                        $historialcuent->id_empleado =auth()->user()->idempleado;
                                        $historialcuent->estado = "A";
                                        $historialcuent->tipo_movimiento = "GASTO";
                                        $historialcuent->concepto = "AGUINALDO FISCAL";
                                        $historialcuent->descripcion ="PAGO AGUINALDO FISCAL #".$item->id_aguinaldo_det." - EMPLEADO #".$item->idempleado." ".$item->nombre_empleado;
                                        $historialcuent->responsable = "EMPLEADO #".$item->idempleado;
                                        $historialcuent->ingreso = 0;
                                        $historialcuent->egreso = $item->total_pagar_f;
                                        $historialcuent->saldo =  $saldoFinal;
                                        $historialcuent->numero_referencia = $item->id_aguinaldo_det;
                                        $historialcuent->tipo_referencia = "aguinaldos_det";
                                        $historialcuent->numero_poliza = 0;
                                        $historialcuent->fecha = $fecha;
                                        $historialcuent->created_by = auth()->user()->name;
                                        $historialcuent->save();

                                        if($historialcuent->save()){
                                            $movcuent =$this->obtenerultimomovcuenta();
                                            foreach($movcuent as $cue){$movId = $cue->id;}
                                            $poliza = historial_cuentas::find($movId);
                                            $poliza->numero_poliza = "CU00".$movId;
                                            $poliza->updated_by = auth()->user()->name;
                                            $poliza->save();

                                            $cuentas = cuentas::find($cuentaFiscal);
                                            $cuentas->saldo_actual = $saldoFinal;
                                            $cuentas->updated_by = auth()->user()->name;
                                            $cuentas->save();
                                        }else{}
                                    } 

                                    $saldoFinal = 0;
                                    if($item->total_pagar_e > 0){
                                        $varobtenercuentas =$this->obtenercuentasPrincipales($cuentaExcedente);
                                        foreach($varobtenercuentas as $varobtenercuenta){$saldo = $varobtenercuenta->saldo_actual;$nombreCaja = $varobtenercuenta->descripcion;}

                                        $saldoFinal = $saldo - $item->total_pagar_e;
                                        $historialcuent = new  historial_cuentas();
                                        $historialcuent->id_cuenta = $cuentaExcedente;
                                        $historialcuent->id_empleado =auth()->user()->idempleado;
                                        $historialcuent->estado = "A";
                                        $historialcuent->tipo_movimiento = "GASTO";
                                        $historialcuent->concepto = "AGUINALDO EXCEDENTE";
                                        $historialcuent->descripcion ="PAGO AGUINALDO EXCEDENTE #".$item->id_aguinaldo_det." - EMPLEADO #".$item->idempleado." ".$item->nombre_empleado;
                                        $historialcuent->responsable = "EMPLEADO #".$item->idempleado;
                                        $historialcuent->ingreso = 0;
                                        $historialcuent->egreso = $item->total_pagar_e;
                                        $historialcuent->saldo =  $saldoFinal;
                                        $historialcuent->numero_referencia = $item->id_aguinaldo_det;
                                        $historialcuent->tipo_referencia = "aguinaldos_det";
                                        $historialcuent->numero_poliza = 0;
                                        $historialcuent->fecha = $fecha;
                                        $historialcuent->created_by = auth()->user()->name;
                                        $historialcuent->save();

                                        if($historialcuent->save()){
                                            $movcuent =$this->obtenerultimomovcuenta();
                                            foreach($movcuent as $cue){$movId = $cue->id;}
                                            $poliza = historial_cuentas::find($movId);
                                            $poliza->numero_poliza = "CU00".$movId;
                                            $poliza->updated_by = auth()->user()->name;
                                            $poliza->save();

                                            $cuentas = cuentas::find($cuentaExcedente);
                                            $cuentas->saldo_actual = $saldoFinal;
                                            $cuentas->updated_by = auth()->user()->name;
                                            $cuentas->save();
                                        }else{}
                                    }                                     
                                }

                                //CERRAMOS AGUINALDO
                                $aguinaldos_enc = aguinaldos_enc::find($id);
                                $aguinaldos_enc->estado = 'CERRADO';
                                $aguinaldos_enc->fecha_cierre = $fecha;
                                $aguinaldos_enc->updated_by=auth()->user()->name;
                                $aguinaldos_enc->save();

                                if($aguinaldos_enc->save()){
                                    return back()->with("success_msg_large","¡Se guardaron los cambios correctamente!");
                                }
                                else{
                                    return back()->with("error_msg_large","No se logro guardar los cambios, intente de nuevo o más tarde");
                                }
                    
                    }else{
                        return back()->with("error_msg_large","No se logro guardar los cambios, ya que no tiene el saldo suficiente, intente de nuevo o más tarde");
                    }
                }else{
                    return back()->with("error_msg_large","No se logro guardar los cambios, ya que no tiene el saldo suficiente, intente de nuevo o más tarde");
                }
            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg","Error al cargar datos, intente despues o más tarde"); }
        }

        public function ExportarlayoutAguinaldo(int $id)
        {
            $enter = "\n";
            $fechai = Carbon::now();
            $date = substr($fechai,0,10);
            $fechaHoy = Carbon::now();
            $fechaActual = $fechaHoy->format('Y-m-d');
            $fechaActual = str_replace('-', '', $fechaActual);
        
            $fechaHoy1 = Carbon::now();
            $fechaAñadida1 = $fechaHoy1->addDay(8);
            $fechaAñadida = $fechaAñadida1->format('Y-m-d');
            $now = Carbon::now();
            $format1 = $now->format('d_m_Y h_i_s a');
            $format2 = $now->format('d.m.Y h.i A');
            $EncabezadoTotales = $this->EncabezadoTotalesAguinaldo($id);

            //ENCABEZADO
                $tiporegistrovlave = 'HNE';
                $emisora = '07278';
                $consecutivo = '01';
                foreach($EncabezadoTotales as $item){
                    $totalRegistros = $item->registros;
                    $totalPagos = str_replace('.', '', $item->total);
                }

                $c_resgistros = 6 - strlen($totalRegistros);
                $c_total = 15 - strlen($totalPagos);
                $totalRegistros = substr("000000", 0, $c_resgistros).$totalRegistros;
                $totalPagos = substr("000000000000000", 0, $c_total).$totalPagos;

                $numeroaltas = '000000';
                $importetotalatas = '000000000000000';
                $numerobajas = '000000';
                $importetotalbajas = '000000000000000';
                $numerocuentasanoti = '000000';
                $accion = '000000000000000000000000000000000000000000000000000000000000000000000000000000';
                $filter = '';
                
                $ENCABEZADO = $tiporegistrovlave.$emisora.$fechaActual.$consecutivo.$totalRegistros.$totalPagos.$numeroaltas.$importetotalatas.$numerobajas.$importetotalbajas.$numerocuentasanoti.$accion.$filter;
            
            //LINEAS POR EMPLEADO A DEPOSITAR
                $infocuerpo = $this->ListadoinfolayoutAguinaldo($id);
                $ceroscomnumeroemp = '';
                $variablecerosn_e = '';
                $cerosimporte = '';
                $variablecerosimporte = '';
                $cerosdecuenta = '00000000';
                $CUERPO = '';

                foreach($infocuerpo as $infc)
                {
                    if($infc->idbanca > 0){
                        //ID DE LA BANCA
                        $c_idbanca = 10 - strlen($infc->idbanca);
                        $idbanca = substr("0000000000", 0, $c_idbanca).$infc->idbanca;

                        //IMPORTE A PAGAR
                        $importe = str_replace('.', '', $infc->importe);
                        $c_importe = 15 - strlen($importe);
                        $importe = substr("000000000000000", 0, $c_importe).$importe;

                        //NUMERO DE CUENTA
                        $c_numero_cuenta = 18 - strlen($infc->numero_cuenta);
                        $numero_cuenta = substr("000000000000000000", 0, $c_numero_cuenta).$infc->numero_cuenta;

                        
                        $CUERPO = $CUERPO.$enter.
                        $infc->tipo_registro.
                        $fechaActual.
                        $idbanca.
                        $infc->referencia_servicioyOrdenante.
                        $importe.
                        $infc->numero_banco_receptor.
                        $infc->tipo_cuenta.
                        $numero_cuenta.
                        $infc->tipo_movimiento.
                        $infc->accion.
                        $infc->importe_ivadelaoperacion.
                        $infc->filtro;
                        
                        $variablecerosn_e = '';
                        $variablecerosimporte = '';
                    }
                }
                
            //DESCARGA PAG
                $nombrearchivobbva = 'NI0727801.PAG';
                //le informamos que será un archivo txt
                header('Content-type: application/txt');
                //también le damos un nombre
                header('Content-Disposition: attachment; filename='.$nombrearchivobbva);
                //generamos el contenido del archivo
                return $ENCABEZADO.$CUERPO; 

            
        }

        public function exportarComprobantes_aguinaldos(int $id){
            try{
                $varpantallas =  $this->Traermenuenc();
                $varsubmenus =   $this->Traermenudet();
                $varempleados = $this->obtenerempleados();
                $varlistanomina = $this->reciboaguinaldo($id);
            
                // Obtener información de la empresa
                $empresas = $this->obtenerempresas();
                $empresa = $empresas->first();
                $razon_social = $empresa ? $empresa->nombre_empresa : '';

                // Obtener fecha del aguinaldo
                $aguinaldos_enc = aguinaldos_enc::find($id);
                $fecha_aguinaldo = $aguinaldos_enc ? date('Y', strtotime($aguinaldos_enc->fecha_pago)) : date('Y');

                set_time_limit(300);
                $pdf = \PDF::setPaper('letter')->loadView('nominas.PDF.recibos_aguinaldo',compact('razon_social','varlistanomina'));
                return $pdf->download("RECIBOS AGUINALDOS_".$fecha_aguinaldo.".pdf");
            } catch(\Illuminate\Database\QueryException $ex){  return back()->with("info_msg","Error al cargar datos, intente despues o más tarde"); }
        }

    //APORTACIONES OBRERO-PATRONALES
        public function index_aportaciones_patronales()
        {
            try {
                $varpantallas = $this->Traermenuenc();
                $varsubmenus = $this->Traermenudet();
                $varaportaciones = $this->obtener_aportaciones_patronales_enc();

                return view('nominas.aportaciones.index', compact(
                    'varpantallas',
                    'varsubmenus',
                    'varaportaciones'
                ));
            } catch (\Illuminate\Database\QueryException $ex) {
                return back()->with('info_msg_large', 'Error al cargar datos, intente despues o más tarde');
            }
        }

        public function crear_aportaciones_patronales(Request $request)
        {
            try {
                $validated = $request->validate([
                    'nombre' => ['required', 'string', 'max:150'],
                    'fecha_inicio' => ['required', 'date'],
                    'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
                ]);

                $inicio = Carbon::parse($validated['fecha_inicio']);
                $fin = Carbon::parse($validated['fecha_fin']);
                $diasPeriodo = $inicio->diffInDays($fin) + 1;

                $enc = aportaciones_patronales_enc::create([
                    'nombre' => $validated['nombre'],
                    'fecha_inicio' => $validated['fecha_inicio'],
                    'fecha_fin' => $validated['fecha_fin'],
                    'dias_periodo' => $diasPeriodo,
                    'created_by' => auth()->user()->name,
                    'updated_by' => auth()->user()->name,
                ]);

                $insertados = $this->calcular_aportaciones_patronales(
                    $enc->id,
                    $validated['fecha_inicio'],
                    $validated['fecha_fin']
                );

                if ($insertados > 0) {
                    return redirect()
                        ->route('ver_aportaciones_patronales', $enc->id)
                        ->with('success_msg_large', "Periodo generado correctamente con {$insertados} empleado(s).");
                }

                return redirect()
                    ->route('ver_aportaciones_patronales', $enc->id)
                    ->with('info_msg_large', 'Periodo creado, pero no se encontraron nóminas cerradas en el rango de fechas.');
            } catch (\Illuminate\Database\QueryException $ex) {
                return back()->with('info_msg_large', 'Error al crear el periodo de aportaciones, intente de nuevo.');
            }
        }

        public function ver_aportaciones_patronales(int $id)
        {
            try {
                $varpantallas = $this->Traermenuenc();
                $varsubmenus = $this->Traermenudet();
                $aportacionEnc = aportaciones_patronales_enc::findOrFail($id);
                $aportacionDet = collect($this->obtener_aportaciones_patronales_det($id));

                $totales = [
                    'sbc' => (float) ($aportacionEnc->total_sbc ?? 0),
                    'dias_cotizados' => round($aportacionDet->sum('dias_cotizados'), 2),
                    'riesgo_trabajo' => (float) ($aportacionEnc->total_riesgo_trabajo ?? 0),
                    'cuota_fija' => (float) ($aportacionEnc->total_cuota_fija ?? 0),
                    'enf_mat_excedente' => (float) ($aportacionEnc->total_enf_mat_excedente ?? 0),
                    'enf_mat_dinero' => (float) ($aportacionEnc->total_enf_mat_dinero ?? 0),
                    'enf_mat_gastos_pensionados' => (float) ($aportacionEnc->total_enf_mat_gastos_pensionados ?? 0),
                    'invalidez_vida' => (float) ($aportacionEnc->total_invalidez_vida ?? 0),
                    'guarderia' => (float) ($aportacionEnc->total_guarderia ?? 0),
                    'retiro' => (float) ($aportacionEnc->total_retiro ?? 0),
                    'cesantia_vejez' => (float) ($aportacionEnc->total_cesantia_vejez ?? 0),
                    'infonavit' => (float) ($aportacionEnc->total_infonavit ?? 0),
                    'total' => (float) ($aportacionEnc->total_final ?? 0),
                ];

                return view('nominas.aportaciones.detalle', compact(
                    'varpantallas',
                    'varsubmenus',
                    'aportacionEnc',
                    'aportacionDet',
                    'totales'
                ));
            } catch (\Illuminate\Database\QueryException $ex) {
                return back()->with('info_msg_large', 'Error al cargar datos, intente despues o más tarde');
            }
        }

        public function recalcular_aportaciones_patronales(int $id)
        {
            try {
                $enc = aportaciones_patronales_enc::findOrFail($id);

                $insertados = $this->calcular_aportaciones_patronales(
                    $enc->id,
                    $enc->fecha_inicio,
                    $enc->fecha_fin
                );

                if ($insertados > 0) {
                    return back()->with('success_msg_large', "Cálculo actualizado para {$insertados} empleado(s).");
                }

                return back()->with('info_msg_large', 'No se encontraron nóminas cerradas en el periodo para recalcular.');
            } catch (\Illuminate\Database\QueryException $ex) {
                return back()->with('info_msg_large', 'Error al recalcular aportaciones, intente de nuevo.');
            }
        }

        public function eliminar_aportaciones_patronales(int $id)
        {
            try {
                aportaciones_patronales_det::where('id_patronales_enc', $id)->delete();
                aportaciones_patronales_enc::where('id', $id)->delete();

                return redirect()
                    ->route('index_aportaciones_patronales')
                    ->with('success_msg_large', 'Periodo de aportaciones eliminado correctamente.');
            } catch (\Illuminate\Database\QueryException $ex) {
                return back()->with('info_msg_large', 'Error al eliminar el periodo, intente de nuevo.');
            }
        }

}
