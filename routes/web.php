<?php

use App\Http\Livewire\Historial;
use App\Http\Livewire\PruebaError;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrdCompraController;
use App\Http\Controllers\LicitacionController;
use App\Http\Controllers\EntradaInventarioController;
use App\Http\Controllers\ExternoPoroveedorController;
use App\Http\Controllers\TimbrarNominaController;
use App\Http\Controllers\FinanzasController;
use App\Http\Controllers\NotasCreditoController;
use App\Http\Controllers\NotasDebitoController;
use App\Http\Controllers\ComplementosPagoController;
use App\Http\Controllers\ServiciosController;
use App\Http\Controllers\AdminProyectoController;
use App\Http\Controllers\MesaControlController;
use App\Http\Controllers\GestionAlumnosEscuelaController;
use App\Http\Controllers\GestionAlumnosEspecialidadController;
use App\Http\Controllers\GestionAlumnosDocumentoController;
use App\Http\Controllers\GestionAlumnosAlumnoController;
use App\Http\Controllers\GestionAlumnosPersonaDocumentoController;
use App\Http\Controllers\GestionAlumnosTutorController;
use App\Http\Controllers\GestionAlumnosViewController;
use App\Http\Controllers\GestionAlumnosEmpresaController;
use App\Http\Controllers\GestionAlumnosSocioController;
use App\Http\Controllers\GestionAlumnosCheckoutSocioController;
use App\Http\Controllers\GestionAlumnosSocioReporteDiarioController;
use App\Http\Controllers\GestionAlumnosEventoSocioController;
use App\Http\Controllers\GestionAlumnosEventoSocioDetalleController;
use App\Http\Controllers\GestionAlumnosCursoController;
use App\Http\Controllers\GestionAlumnosAlumnoCursoController;
use App\Http\Controllers\GestionAlumnosEmpresaVisitaController;
use App\Http\Controllers\GestionAlumnosDocenteController;
use App\Http\Controllers\GestionAlumnosTestPersonalidadAulaMixtaController;
use App\Http\Controllers\GestionAlumnosConvenioAsignacionController;
use App\Http\Controllers\GestionAlumnosAsistenciaController;
use App\Http\Controllers\MaquinariaController;
use App\Http\Controllers\OrdenesProduccionController;
use App\Http\Controllers\RecetasController;
use App\Http\Controllers\TuboEspecificacionesController;
use App\Http\Controllers\FlangeEspecificacionesController;
use App\Http\Controllers\ConexionEspecificacionesController;
use App\Http\Controllers\ProduccionReportesController;
use App\Http\Controllers\RegistroProduccionController;
use App\Http\Controllers\VentasController;
use App\Http\Controllers\ReporteNoExistenciasController;
use App\Http\Controllers\AutinApiController;
use App\Http\Controllers\CentrosCostosController;
use App\Http\Controllers\ProyeccionesVentasController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

  Route::get('/', function () {return view('auth.login');})->name('login');
  Route::get('/Acceso', function () {return view('auth.noDisponible');})->name('login.Acceso');

  Auth::routes();
  Route::get('/Servicios/export_cot_pdf/{id}/{tipo}', 'App\Http\Controllers\ServiciosController@export_cot_pdf')->name('servicio.export_cot_pdf');
  Route::post('/Servicios/export_cot_pdf/{id}/{tipo}', 'App\Http\Controllers\ServiciosController@export_cot_pdf')->name('servicio.export_cot_pdf.post');
   Route::get('/Servicios/Cotizacion/Exportar/{id}', 'App\Http\Controllers\ServiciosController@exportar_cotizacion')->name('servicios.exportar_cotizacion');
    
  //INICIO RUTAS DESCARGABLES------------------------------------------------------------------------------------
        Route::get('/exportarnomxqui/{id}', 'App\Http\Controllers\NominasController@exportarnomxqui')->name('Nominas.exportarnomxqui');
        Route::get('/ExportarCierres/{fecha}', 'App\Http\Controllers\ReportesController@exportarCierres')->name('exportarCierres');
        Route::get('/Formatos/VerificacionDomDist', 'App\Http\Controllers\HomeController@DescargarVerificacionDomDist')->name('DescargarVerificacionDomDist');
        Route::get('/Formatos/VerificacionDomAval', 'App\Http\Controllers\HomeController@DescargarVerificacionDomAval')->name('DescargarVerificacionDomAval');
        Route::get('/Formatos/SolicitudCredito', 'App\Http\Controllers\HomeController@DescargarSolicitud')->name('DescargarSolicitud');

        //aguinaldo
        Route::get('/Nominas/Aguinaldos/exportar_excel/{id}','App\Http\Controllers\NominasController@exportar_excel_aguinaldos')->name('exportar_excel_aguinaldos');
        Route::get('/ExportarlayoutAguinaldo/{id}', 'App\Http\Controllers\NominasController@ExportarlayoutAguinaldo')->name('ExportarlayoutAguinaldo');
        Route::get('/Nominas/Aguinaldos/exportarComprobantes/{id}', 'App\Http\Controllers\NominasController@exportarComprobantes_aguinaldos')->name('exportarComprobantes_aguinaldos');


        Route::get('/Empleados/exportar_excel', 'App\Http\Controllers\EmpleadosController@exportar_excel')->name('Empleados.exportar_excel');
        Route::post('/Nominas/importBoTrans/{id}', 'App\Http\Controllers\NominasController@importar_excel')->name('Nominas.importar_excel');
        Route::post('/Nominas/importAsistencias/{id}', 'App\Http\Controllers\NominasController@importar_asistencias')->name('Nominas.importar_asistencias');
        Route::get('/Nominas/exportarComprobantes/{id}', 'App\Http\Controllers\NominasController@exportarComprobantes')->name('Nominas.exportarComprobantes');
        Route::get('/Nominas/exportarComprobante/{idpagodet}', 'App\Http\Controllers\NominasController@exportarComprobanteEmpleado')->name('Nominas.exportarComprobante');
        Route::get('/Nominas/exportarListado/{id}', 'App\Http\Controllers\NominasController@exportarListadoNomina')->name('Nominas.exportarListado');
        Route::post('/Nominas/entrega/{idpagodet}', 'App\Http\Controllers\NominasController@actualizarEntrega')->name('Nominas.entrega');
        Route::get('/Nominas/exportarCheques/{id}', 'App\Http\Controllers\NominasController@exportarCheques')->name('Nominas.exportarCheques');
        Route::get('/Nominas/exportar_excel/{id}', 'App\Http\Controllers\NominasController@exportar_excel')->name('Nominas.exportar_excel');
        Route::get('/Nominas/exportar_retenciones/{id}', 'App\Http\Controllers\NominasController@exportar_retenciones')->name('Nominas.exportar_retenciones');
        Route::get('/Nominas/exportar_formato_nomina/{id}', 'App\Http\Controllers\NominasController@exportar_formato_nomina')->name('Nominas.exportar_formato_nomina');
        Route::get('/Nominas/exportar_formato_asistencias/{id}', 'App\Http\Controllers\NominasController@exportar_formato_asistencias')->name('Nominas.exportar_formato_asistencias');
        Route::get('/Nominas/asistencias/{id}', 'App\Http\Controllers\NominasController@verAsistenciasNomina')->name('Nominas.asistencias');
        Route::post('/Nominas/asistencias/{id}/estado', 'App\Http\Controllers\NominasController@actualizar_estado_asistencia')->name('Nominas.asistencias.estado');
        Route::post('/Nominas/asistencias/{id}/checadas', 'App\Http\Controllers\NominasController@actualizar_checadas_asistencia')->name('Nominas.asistencias.checadas');
        Route::post('/Nominas/asistencias/{id}/horario', 'App\Http\Controllers\NominasController@actualizar_horario_asistencia')->name('Nominas.asistencias.horario');
        Route::post('/Nominas/asistencias/{id}/recalcular', 'App\Http\Controllers\NominasController@recalcular_asistencias_nomina')->name('Nominas.asistencias.recalcular');
        Route::post('/Nominas/EditarEmpleado/{id}', 'App\Http\Controllers\NominasController@editar_empleado')->name('Nominas.editar_empleado');

        Route::get('/Nominas/CatalogoISR', 'App\Http\Controllers\NominasController@patalla_isr')->name('Nominas.patalla_isr');
        Route::post('/Nominas/CatalogoISR/actualizar', 'App\Http\Controllers\NominasController@actualizar_isr_masivo')->name('Nominas.actualizar_isr');
        Route::get('/Nominas/CatalogoISR/editar/{id}', 'App\Http\Controllers\NominasController@editar_isr')->name('Nominas.editar_isr');
        Route::get('/Nominas/CatalogoISR/eliminar/{id}', 'App\Http\Controllers\NominasController@eliminar_isr')->name('Nominas.eliminar_isr');

        Route::get('/Nominas/CatalogoSubsidio', 'App\Http\Controllers\NominasController@patalla_subsidio')->name('Nominas.patalla_subsidio');
        Route::get('/Nominas/CatalogoSubsidio/editar/{id}', 'App\Http\Controllers\NominasController@editar_subsidio')->name('Nominas.editar_subsidio');
        Route::get('/Nominas/CatalogoSubsidio/eliminar/{id}', 'App\Http\Controllers\NominasController@eliminar_subsidio')->name('Nominas.eliminar_subsidio');

        Route::get('/Nominas/CatalogoConceptos/editar/{id}', 'App\Http\Controllers\NominasController@editar_concepto')->name('Nominas.editar_concepto');

        Route::get('/Nominas/CatalogoIMSS', 'App\Http\Controllers\NominasController@patalla_imss')->name('Nominas.patalla_imss');
        Route::post('/Nominas/CatalogoIMSS/actualizar', 'App\Http\Controllers\NominasController@actualizar_imss_masivo')->name('Nominas.actualizar_imss');
        Route::get('/Nominas/CatalogoIMSS/editar/{id}', 'App\Http\Controllers\NominasController@editar_imss')->name('Nominas.editar_imss');
        Route::get('/Nominas/CatalogoIMSS/eliminar/{id}', 'App\Http\Controllers\NominasController@eliminar_imss')->name('Nominas.eliminar_imss');

        Route::get('/Nominas/CatalogoCesantiaVejez', 'App\Http\Controllers\NominasController@patalla_cesantia_vejez')->name('Nominas.patalla_cesantia_vejez');
        Route::post('/Nominas/CatalogoCesantiaVejez/actualizar', 'App\Http\Controllers\NominasController@actualizar_cesantia_vejez_masivo')->name('Nominas.actualizar_cesantia_vejez');

        Route::get('/Exportarlayoutnomina/{id}', 'App\Http\Controllers\NominasController@Exportarlayoutnomina')->name('Exportarlayoutnomina');
        Route::get('/ExportarlayoutBanorte/{id}', 'App\Http\Controllers\NominasController@ExportarlayoutBanorte')->name('ExportarlayoutBanorte');
        Route::get('/ExpotArchivoDispersion/{id}', 'App\Http\Controllers\NominasController@ExpotArchivoDispersion')->name('ExpotArchivoDispersion');


        //Route::get('/catalogoDistribuidores/Exportar', 'App\Http\Controllers\valesController@exportarCatalogoDis')->name('exportarCatalogoDis');
        //Route::get('/catalogoClientes/Exportar', 'App\Http\Controllers\valeraController@exportarClientes')->name('exportarClientes');
        //Route::get('/Valeras/Entregas/Exportar', 'App\Http\Controllers\valeraController@exportarDisValeras')->name('exportarDisValeras');
        //Route::get('/gestionCreditos/Cliente/Exportar', 'App\Http\Controllers\valeraController@exportarClientesPrestamos')->name('exportarClientesPrestamos');

        //Route::get('/exportarodp/{fecha}', 'App\Http\Controllers\valeraController@exportarodp')->name('exportarodp');
        //Route::get('/getdownloadTablaAmoritizacion/{cliente}/{prestamo}', 'App\Http\Controllers\valeraController@getdownloadTablaAmoritizacion')->name('getdownloadTablaAmoritizacion');
        //Route::get('/getdownloadValera/{iddis}/{val}/{fechaEntrega}', 'App\Http\Controllers\valeraController@getdownloadValera')->name('getdownloadValera');
        //Route::get('/getdownloadRelacion/{iddis}/{fechaquincena}', 'App\Http\Controllers\pagoscontroller@getdownloadRelacion')->name('getdownloadRelacion');
        //Route::get('/getdownloadFichaPago/{iddis}/{fechaquincena}', 'App\Http\Controllers\pagoscontroller@getdownloadFichaPago')->name('getdownloadFichaPago');
        //Route::get('/getdownloadReciboPago/{idpago}', 'App\Http\Controllers\pagoscontroller@getdownloadReciboPago')->name('getdownloadReciboPago');

        Route::get('/pruebaEstadoCuenta/{tipo}/{idpres}/{idemple}/{idcord}', 'App\Http\Controllers\PrestamoNomController@pruebaEstadoCuenta')->name('downloadEstadoCuenta');
        Route::get('/downloadEstadoCuenta/{tipo}/{idpres}/{idemple}/{idcord}', 'App\Http\Controllers\PrestamoNomController@downloadEstadoCuenta')->name('downloadEstadoCuenta');
        Route::get('/Empleados/getdownloadContrato/{id}','App\Http\Controllers\EmpleadosController@getdownloadContrato')->name('getdownloadContrato');
        Route::get('/Empleados/getdownloadContratoDeterminado/{id}','App\Http\Controllers\EmpleadosController@getdownloadContratoDeterminado')->name('getdownloadContratoDeterminado');
        Route::get('/exportarrptnomxsuc/{fecha}/{idn}', 'App\Http\Controllers\NominasController@exportarrptnomxsuc')->name('exportarrptnomxsuc');
        Route::get('/exporetencionesnom/{fecha}', 'App\Http\Controllers\NominasController@exporetencionesnom')->name('exporetencionesnom');
        Route::get('/exportar_excel_creditos_calculados', 'App\Http\Controllers\PrestamoNomController@exportar_excel_creditos_calculados')->name('exportar_excel_creditos_calculados');
        Route::get('/exportar_excel_creditos_empleados', 'App\Http\Controllers\PrestamoNomController@exportar_excel_creditos_empleados')->name('exportar_excel_creditos_empleados');

        Route::post('/CatalogoGeneral/Gastos/ExportarGasto', 'App\Http\Controllers\CatalogosController@exportarGasto')->name('exportarGasto');
        Route::get('/CatalogoGeneral/Gastos/ExportarSucursales', 'App\Http\Controllers\CatalogosController@exportarsucursales')->name('exportarsucursales');
        Route::get('/CatalogoGeneral/Gastos/exportarEmpresas', 'App\Http\Controllers\CatalogosController@exportarempresas')->name('exportarempresas');
        Route::get('/CatalogoGeneral/Cajas/Politicas/{tipo}', 'App\Http\Controllers\CatalogosController@getdownloadPoliticas')->name('getdownloadPoliticas');
        Route::get('/CatalogoGeneral/Exportar/Cajas', 'App\Http\Controllers\CatalogosController@exportarCajas')->name('exportarCajas');
        Route::get('/CatalogoGeneral/Cajas/Cartaresponsiva/{id}/{tipo}/{nombreResponsable}/{idEmpleado}/{nombreEmpresa}/{nombreCaja}', 'App\Http\Controllers\CatalogosController@getdownloadCartaResponsiva')->name('getdownloadCartaResponsiva');

        Route::get('/CatalogoGeneral/Empresas/Exportar', 'App\Http\Controllers\CatalogosController@exportarempresas')->name('exportarempresas');

        Route::get('/CatalogoGeneral/Cuentas/Exportar', 'App\Http\Controllers\CatalogosController@exportarcuentas')->name('exportarcuentas');
        Route::get('/Tesoreria/Cuentas/ExportarHistorialCuenta/{id}', 'App\Http\Controllers\ContabilidaController@ExportarHistorialCuenta')->name('ExportarHistorialCuenta');
        Route::get('/Tesoreria/Movimientos/ExportarMovimientos/{tipo}/{id}', 'App\Http\Controllers\MovimientosController@exportarConsultaMovimientos')->name('exportarConsultaMovimientos');

        Route::get('/Tesoreria/Movimientos/Poliza/{tipo}/{id}', 'App\Http\Controllers\MovimientosController@downloadPoliza')->name('downloadPoliza');
        Route::get('/Tesoreria/Movimientos/Exportar/ArqueoCaja/{tipo}/{empresaid}/{id}/{fecha}', 'App\Http\Controllers\MovimientosController@exportarArqueoCaja')->name('exportarArqueoCaja');
        Route::get('/HistorialRealaciondownload/{idcordi}', 'App\Http\Controllers\RelacionController@HistorialRealaciondownload')->name('HistorialRealaciondownload');

        Route::get('/ExportarIngresos/{fechaIni}/{fechaFin}', 'App\Http\Controllers\ReportesController@exportarIngresos')->name('exportarIngresos');
        Route::get('/ExportarDesembolsos/{fechaIni}/{fechaFin}', 'App\Http\Controllers\ReportesController@exportarDesembolsos')->name('exportarDesembolsos');
        Route::get('/ExportarGastos/{fechaIni}/{fechaFin}', 'App\Http\Controllers\ReportesController@exportarGastos')->name('exportarGastos');
        
        Route::get('entradas/exportar-excel', [EntradaInventarioController::class, 'exportExcel'])->name('Entradas.exportExcel');
        Route::get('/licitaciones/exportarCostos/{id}',[LicitacionController::class,'exportarCostos'])->name('licitaciones.exportarCostos');

        // Ruta para obtener factura impresa
        Route::get('/obtener-factura-impresa/{folio}', 'App\Http\Controllers\TimbrarNominaController@obtenerFacturaImpresion')->name('obtener.factura.impresion');

        //COTIZACIONES Y SERVICIOS
        Route::get('/Comparativa/Cotizacion/{id}', 'App\Http\Controllers\LicitacionController@ExportarComparativa')->name('ExportarComparativa');

  //FIN RUTAS DESCARGABLES-------------------------------------------------------------------------------
  
  Route::group(['middleware' => 'prevent-back-history'],function(){
  Auth::routes();
  Route::get('/back', function () {return back();});

  //Ruta de el modulo de empleados-------------------------------------------------------------------------------------
  Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
  Route::get('/intranet', [GestionAlumnosViewController::class, 'intranet'])->name('intranet');
  Route::get('/Gestion_alumnos/intranet', [GestionAlumnosViewController::class, 'intranet'])->name('gestion_alumnos.intranet');

  Route::prefix('/Empleados')->group(function () {
      Route::get('/',[App\Http\Controllers\EmpleadosController::class, 'index'])->name('verempleados');
      Route::post('/importar-empleados', [App\Http\Controllers\EmpleadosController::class, 'importar_empleados_excel'])->name('Empleados.importar_empleados');
      Route::post('/importar-nomina', [App\Http\Controllers\EmpleadosController::class, 'importar_nomina_excel'])->name('Empleados.importar_nomina');
      Route::post('/exportar-sua', [App\Http\Controllers\EmpleadosController::class, 'exportar_sua'])->name('Empleados.exportar_sua');
      Route::get('/create',[App\Http\Controllers\EmpleadosController::class, 'create'])->name('create');
      Route::post('/store', 'App\Http\Controllers\EmpleadosController@store')->name('Empleados.store');
      Route::post('/contratoSubir', 'App\Http\Controllers\EmpleadosController@contratoSubir')->name('Empleados.contratoSubir');
      Route::get('/{id}/edit','App\Http\Controllers\EmpleadosController@edit')->name('Empleados.edit');
      Route::put('/Update/{id}','App\Http\Controllers\EmpleadosController@cambiar')->name('cambiar');
      Route::post('/contratoSubir/{id}','App\Http\Controllers\EmpleadosController@contratoSubir')->name('contratoSubir');
      Route::post('/bajas', 'App\Http\Controllers\EmpleadosController@bajas')->name('Empleados.bajas');
      Route::get('/Baja/{id}','App\Http\Controllers\EmpleadosController@empleadoBaja')->name('empleadoBaja');
      Route::get('/cacula_baja', 'App\Http\Controllers\EmpleadosController@calcular_baja')->name('Empleados.calcular_baja');
      Route::get('/editBaja/{id}','App\Http\Controllers\EmpleadosController@editarBaja');
      Route::put('/AtualizarBaja','App\Http\Controllers\EmpleadosController@BajaEmpleadoEdit')->name('Empleados.atualizarBaja');
      Route::get('/getdownloadBaja/{id}','App\Http\Controllers\EmpleadosController@getdownloadBaja')->name('getdownloadBaja');
      Route::put('/UpdateReactivar/{id}','App\Http\Controllers\EmpleadosController@reactivar')->name('Empleados.updateReactivar');
      Route::post('/guardarBajaEmpleado/{id}','App\Http\Controllers\EmpleadosController@mguardar')->name('guardar');
      Route::get('/ReactivarEmpleado/{id}','App\Http\Controllers\EmpleadosController@traerVistaReactivar')->name('reactivar');
      Route::get('grafica', 'App\Http\Controllers\EmpleadosController@grafica_empleados')->name('Empleados.grafica_empleados');


      Route::get('/Incapacidades','App\Http\Controllers\EmpleadosController@incapacidades')->name('Empleados.incapacidades');
      Route::get('/Incapacidades/Alta',[App\Http\Controllers\EmpleadosController::class, 'incapacidades_vista_alta'])->name('incapacidades_alta');
      Route::post('/Incapacidades/Insertar','App\Http\Controllers\EmpleadosController@incapacidades_insertar')->name('incapacidades_insertar');
      Route::get('/Incapacidades/Eliminar/{id}',[App\Http\Controllers\EmpleadosController::class, 'incapacidades_eliminar'])->name('incapacidades_eliminar');

      Route::get('/Vacaciones','App\Http\Controllers\EmpleadosController@vacaciones')->name('Empleados.vacaciones');
      Route::get('/Vacaciones/Alta',[App\Http\Controllers\EmpleadosController::class, 'vacaciones_vista_alta'])->name('vacaciones_alta');
      Route::post('/Vacaciones/Insertar','App\Http\Controllers\EmpleadosController@vacaciones_insertar')->name('vacaciones_insertar');
      Route::get('/Vacaciones/Eliminar/{id}',[App\Http\Controllers\EmpleadosController::class, 'vacaciones_eliminar'])->name('vacaciones_eliminar');
      Route::post('/edit/fotoPerfil/{id}','App\Http\Controllers\EmpleadosController@fotoPerfil')->name('fotoPerfil');
  });


    //Ruta de modulo de nominas---------------------------------------------------------------------------------------------
    Route::prefix('/Nominas')->group(function () {
        Route::resource('/','App\Http\Controllers\NominasController');
        Route::get('/',[App\Http\Controllers\NominasController::class, 'index'])->name('vernominas');
        Route::get('/resultados/{id}', [App\Http\Controllers\NominasController::class, 'resultadosNomina'])->name('nominas.resultados');
        Route::get('/editarnomina/{id}/{idtiponomina}/{fecha_ini}/{fecha_fin}','App\Http\Controllers\NominasController@editarnomina')->name('Nominaseditar');
        Route::get('/preview-cierre/{id}/{idtiponomina}/{fecha_ini}/{fecha_fin}','App\Http\Controllers\NominasController@previewCierreNomina')->name('Nominas.previewCierre');
        Route::get('/{paso}/{id}/{fecha_ini}/{fecha_fin}','App\Http\Controllers\NominasController@recalcular_nomia')->name('recalcular_nomia');
        Route::post('/cerrarnomina/{id}/{dias}/{fecha_ini}/{fecha_fin}','App\Http\Controllers\NominasController@cerrarnomina')->name('Nominascerrar');

        //AGUINALDOS
        Route::get('/Aguinaldos','App\Http\Controllers\NominasController@index_aguinaldos')->name('index_aguinaldos');
        Route::post('/Aguinaldos/Crear','App\Http\Controllers\NominasController@crear_aguinaldos')->name('crear_aguinaldos');
        Route::get('/Aguinaldos/Editar/{id}','App\Http\Controllers\NominasController@editar_aguinaldos')->name('editar_aguinaldos');
        Route::get('/Aguinaldos/Calcular/{id}','App\Http\Controllers\NominasController@calcular_aguinaldos')->name('calcular_aguinaldos');
        Route::post('/Aguinaldos/EditarDias/{id}','App\Http\Controllers\NominasController@editar_dias_aguinaldo')->name('editar_dias_aguinaldo');
        Route::post('/Aguinaldos/Cerrar/{id}','App\Http\Controllers\NominasController@cerrar_aguinaldo')->name('cerrar_aguinaldo');
        Route::get('/Aguinaldos/Timbrar/{id}','App\Http\Controllers\TimbrarNominaController@TimbrarAguinaldo')->name('timbrar_aguinaldo');
        Route::get('/Aguinaldos/VerTimbrado/{id}','App\Http\Controllers\TimbrarNominaController@veraguinaldotimbrado')->name('veraguinaldotimbrado');

        //APORTACIONES OBRERO-PATRONALES
        Route::get('/AportacionesPatronales','App\Http\Controllers\NominasController@index_aportaciones_patronales')->name('index_aportaciones_patronales');
        Route::post('/AportacionesPatronales/Crear','App\Http\Controllers\NominasController@crear_aportaciones_patronales')->name('crear_aportaciones_patronales');
        Route::get('/AportacionesPatronales/Calcular/{id}','App\Http\Controllers\NominasController@recalcular_aportaciones_patronales')->name('recalcular_aportaciones_patronales');
        Route::get('/AportacionesPatronales/Eliminar/{id}','App\Http\Controllers\NominasController@eliminar_aportaciones_patronales')->name('eliminar_aportaciones_patronales');
        Route::get('/AportacionesPatronales/{id}','App\Http\Controllers\NominasController@ver_aportaciones_patronales')->name('ver_aportaciones_patronales');
    });

    Route::prefix('/Nominaseliminar')->group(function () {
      // solo de enc
      Route::get('/{id}','App\Http\Controllers\NominasController@nominaeliminar')->name('nominaeliminar');
      //solo de enc det temdet
      Route::get('/temporal/{id}','App\Http\Controllers\NominasController@nominaeliminarTemp')->name('nominaeliminarTemp');
      //solo de enc det
      Route::get('/calcular/{id}/{fecha_inicio}/{fecha_fin}','App\Http\Controllers\NominasController@nominaeliminarCalcu')->name('nominaeliminarCalcu');
    });

    Route::put('/Nominaseditaremp/actualizarEmpleado','App\Http\Controllers\NominasController@actualizarNominaEmp')->name('nominas.actualizaremp');
    Route::get('/Nominaseditaremp/editarnomemp/{id}/{idempleado}','App\Http\Controllers\NominasController@editarnomemp')->name('Nominaseditaremp');

    
    // Ruta sistemas panel de control---------------------------------------------------------------------------------------------
    Route::get('/Panel', 'App\Http\Controllers\SistemasController@index')->name('panel');
    Route::prefix('/Sistemas')->group(function () {
        Route::get('/Pantallas', 'App\Http\Controllers\SistemasController@indexPantallas')->name('pantallas');
        Route::get('/guardar_permisos', 'App\Http\Controllers\SistemasController@guardar_permisos')->name('guardar_permisos');
        Route::get('/Acciones', 'App\Http\Controllers\SistemasController@indexAcciones')->name('acciones');
        Route::post('/guardar_departamento', 'App\Http\Controllers\SistemasController@guardar_departamento')->name('guardar_departamento');
        Route::post('/guardar_vista', 'App\Http\Controllers\SistemasController@guardar_vista')->name('guardar_vista');
        Route::post('/guardar_acciones', 'App\Http\Controllers\SistemasController@guardar_acciones')->name('guardar_acciones');
        Route::get('/eliminar_acciones/{id}', 'App\Http\Controllers\SistemasController@eliminar_acciones')->name('eliminar_acciones');
        Route::get('/Perfiles', 'App\Http\Controllers\SistemasController@indexPerfiles')->name('perfiles');
        Route::get('/Empresas', [\App\Http\Controllers\EmpresaModulosController::class, 'index'])->name('sistemas.empresas');
        Route::post('/Empresas', [\App\Http\Controllers\EmpresaModulosController::class, 'store'])->name('sistemas.empresas.store');
        Route::get('/Empresas/{id}/catalogo', [\App\Http\Controllers\EmpresaModulosController::class, 'catalogo'])->name('sistemas.empresas.catalogo');
        Route::post('/Empresas/{id}/catalogo', [\App\Http\Controllers\EmpresaModulosController::class, 'guardarCatalogo'])->name('sistemas.empresas.catalogo.guardar');
        Route::post('/Empresas/{id}/superusuario', [\App\Http\Controllers\EmpresaModulosController::class, 'asignarSuperusuario'])->name('sistemas.empresas.superusuario');
        Route::post('/Empresas/{id}/superusuario/{idUsuario}/quitar', [\App\Http\Controllers\EmpresaModulosController::class, 'quitarSuperusuario'])->name('sistemas.empresas.superusuario.quitar');
        Route::get('/AccionesPerfiles', 'App\Http\Controllers\SistemasController@indexAccionesPerfiles')->name('acciones_perfiles');
        Route::get('/usuario_asignaciones/{id}', 'App\Http\Controllers\SistemasController@getUsuarioAsignaciones')->name('usuario_asignaciones');
        Route::post('/nuevo_perfil', 'App\Http\Controllers\SistemasController@nuevo_perfil')->name('nuevo_perfil');
        Route::post('/guardar_perfil', 'App\Http\Controllers\SistemasController@guardar_perfil')->name('guardar_perfil');
        Route::get('/eliminar_accion_perfil/{id}', 'App\Http\Controllers\SistemasController@eliminar_accion_perfil')->name('eliminar_accion_perfil');
        Route::post('/guardar_perfil_accion', 'App\Http\Controllers\SistemasController@guardar_perfil_accion')->name('guardar_perfil_accion');
        Route::get('/UsuarioPermisos/{id}', 'App\Http\Controllers\SistemasController@indexUserPermisos')->name('indexUserPermisos');
        Route::post('/UsuarioPermisos/{id}/acciones', 'App\Http\Controllers\SistemasController@guardarAccionesUser')->name('guardar_acciones_user');
        Route::post('/UsuarioPermisos/{id}/modulos', 'App\Http\Controllers\SistemasController@guardarPermisosModuloUser')->name('guardar_permisos_modulo_user');
        Route::get('/Usuarios', 'App\Http\Controllers\SistemasController@getUsuariosPermisos')->name('usuario_permisos');
        Route::get('/eliminar_acciones_user/{id}', 'App\Http\Controllers\SistemasController@eliminar_acciones_user')->name('eliminar_acciones_user');
        Route::get('/eliminar_perfil_user/{id}', 'App\Http\Controllers\SistemasController@eliminar_perfil_user')->name('eliminar_perfil_user');

        Route::get('/facturacion', 'App\Http\Controllers\SistemasController@facturacion')->name('sistemas.facturacion');

        Route::get('/api/datos-fiscales', [\App\Http\Controllers\DatosFiscalesEmpresaController::class, 'index'])->name('sistemas.api.datos_fiscales.index');
        Route::post('/api/datos-fiscales', [\App\Http\Controllers\DatosFiscalesEmpresaController::class, 'store'])->name('sistemas.api.datos_fiscales.store');
        Route::get('/api/datos-fiscales/{id}', [\App\Http\Controllers\DatosFiscalesEmpresaController::class, 'show'])->name('sistemas.api.datos_fiscales.show');
        Route::put('/api/datos-fiscales/{id}', [\App\Http\Controllers\DatosFiscalesEmpresaController::class, 'update'])->name('sistemas.api.datos_fiscales.update');
        Route::delete('/api/datos-fiscales/{id}', [\App\Http\Controllers\DatosFiscalesEmpresaController::class, 'destroy'])->name('sistemas.api.datos_fiscales.destroy');

        // AutinApi — catálogos SAP (proxy al servidor real)
        Route::prefix('/AutinApi')->group(function () {
            Route::get('/', [AutinApiController::class, 'index'])->name('autin-api.index');
            Route::get('/health', [AutinApiController::class, 'health'])->name('autin-api.health');

            // Globales (todas las empresas) — antes de {database}
            Route::get('/cuentas', [AutinApiController::class, 'cuentasGlobal'])->name('autin-api.cuentas-global');
            Route::get('/centros-costo', [AutinApiController::class, 'centrosCostoGlobal'])->name('autin-api.centros-global');
            Route::get('/gasto-real', [AutinApiController::class, 'gastoReal'])->name('autin-api.gasto-real');
            Route::get('/ventas', [AutinApiController::class, 'ventas'])->name('autin-api.ventas');
            Route::get('/listas-precios', [AutinApiController::class, 'listasPrecios'])->name('autin-api.listas-precios');

            Route::get('/{database}/catalogos', [AutinApiController::class, 'catalogos'])
                ->where('database', 'austin|imsa|pitic|sydney')
                ->name('autin-api.catalogos');
            Route::get('/{database}/{resource}', [AutinApiController::class, 'resource'])
                ->where('database', 'austin|imsa|pitic|sydney')
                ->where('resource', 'centros-costo|cuentas|agrupaciones-cuentas|transacciones')
                ->name('autin-api.resource');
            Route::get('/{database}/{resource}/{id}', [AutinApiController::class, 'show'])
                ->where('database', 'austin|imsa|pitic|sydney')
                ->where('resource', 'centros-costo|cuentas|agrupaciones-cuentas|transacciones')
                ->where('id', '.*')
                ->name('autin-api.show');
        });
    });

    // Ruta resgistro de usuarios--------------------------------------------------------------------------------------------------
    Route::get('/Sistemas/Registro', 'App\Http\Controllers\Auth\RegisterController@Index')->name('registro');
    Route::post('/Registro/Crear', 'App\Http\Controllers\Auth\RegisterController@create')->name('createUser');
    Route::post('/Registro/Updatepass', 'App\Http\Controllers\Auth\RegisterController@updatepass')->name('updatepass');
    Route::post('/Registro/Inactivar', 'App\Http\Controllers\Auth\RegisterController@inactivar')->name('inactivarUser');
    Route::post('/Registro/Activar', 'App\Http\Controllers\Auth\RegisterController@activar')->name('activarUser');
    Route::post('/Registro/Empresa', 'App\Http\Controllers\Auth\RegisterController@asignarEmpresa')->name('asignarEmpresaUser');

    //prestamos de nominas
    Route::get('/prestamosnominas', 'App\Http\Controllers\PrestamoNomController@creditosEmpleadosCatalogo')->name('creditosEmpleadosCatalogo');
    Route::get('/prestamosnominas/capturaPrestamos', 'App\Http\Controllers\PrestamoNomController@capturacreditonom')->name('capturacreditonom');
    Route::get('/prestamosnominas/detalle/{idpres}/{idcord}/{tipo}', 'App\Http\Controllers\PrestamoNomController@detallePrestamoTemp')->name('detallePrestamoTemp');

    Route::get('/guardarprestamonomina/{tipo}', 'App\Http\Controllers\PrestamoNomController@guardarprestamonomina')->name('guardarprestamonomina');
    Route::get('/editarprestamonomina/{tipo}', 'App\Http\Controllers\PrestamoNomController@editarprestamonomina')->name('editarprestamonomina');
    Route::get('/eliminarprestamonomina/{credito}', 'App\Http\Controllers\PrestamoNomController@eliminarprestamonomina')->name('eliminarprestamonomina');
    Route::post('/editarprestamonomina/{tipo}', 'App\Http\Controllers\PrestamoNomController@editarprestamonomina')->name('editarprestamonomina');
    Route::post('/guardarprestamonomina/{tipo}', 'App\Http\Controllers\PrestamoNomController@guardarprestamonomina')->name('guardarprestamonomina');
    Route::post('/subirCompEstadoCuenta/{id}/{idemple}', 'App\Http\Controllers\PrestamoNomController@subirCompEstadoCuenta')->name('subirCompEstadoCuenta');
    
    Route::prefix('/PrestamoEmpleados')->group(function () {
        Route::post('/Estado/{id}', 'App\Http\Controllers\PrestamoNomController@actualizaEstado')->name('actualizaEstado');
        Route::post('/Comentario/{id}/{tipo}', 'App\Http\Controllers\PrestamoNomController@ComentarioEdit')->name('ComentarioEdit');
        Route::post('/CancelarPrestamoAct/{id}', 'App\Http\Controllers\PrestamoNomController@eliminarPrestammoActivo')->name('eliminarPrestammoActivo');
        Route::post('/SolicitarCancelacion/{id}', 'App\Http\Controllers\PrestamoNomController@SolicitarCancelacion')->name('solicitarCancelacion');
        Route::post('/AplicarPago/{idpres}', 'App\Http\Controllers\PrestamoNomController@generar_pago')->name('generar_pago');
        Route::post('/AplicarPagoTodos/{idpres}', 'App\Http\Controllers\PrestamoNomController@generar_pago_todos')->name('generar_pago_todos');
    });

    Route::get('Global/Nominas',[App\Http\Controllers\GlobalController::class, 'verglobalnom'])->name('verglobalnom');


    //Catalogos Generales
    Route::prefix('/CatalogoGeneral')->group(function () {
        Route::get('/', 'App\Http\Controllers\CatalogosController@index')->name('catalogosGenerales');

        Route::get('/Empresas', 'App\Http\Controllers\CatalogosController@indexEmpresas')->name('indexEmpresas');
        Route::post('/Empresas/NuevaEmpresa', 'App\Http\Controllers\CatalogosController@nuevaEmpresa')->name('nuevaEmpresa');
        Route::post('/Empresas/EditarEmpresa/{id}', 'App\Http\Controllers\CatalogosController@editarEmpresa')->name('editarEmpresa');
        Route::post('/Empresas/EliminarEmpresa/{id}', 'App\Http\Controllers\CatalogosController@eliminarEmpresa')->name('eliminarEmpresa');

        Route::get('/Sucursales', 'App\Http\Controllers\CatalogosController@indexSucursales')->name('indexSucursales');
        Route::post('/Sucursales/NuevaSucursal', 'App\Http\Controllers\CatalogosController@nuevaSucursal')->name('nuevaSucursales');
        Route::post('/Sucursales/EditarSucursal/{id}', 'App\Http\Controllers\CatalogosController@editarSucursal')->name('editarSucursales');
        Route::post('/Sucursales/EliminarSucursal/{id}', 'App\Http\Controllers\CatalogosController@eliminarSucursal')->name('eliminarSucursales');

        Route::get('/Gastos', 'App\Http\Controllers\CatalogosController@indexGastos')->name('indexGastos');
        Route::post('/Gastos/NuevoGasto', 'App\Http\Controllers\CatalogosController@nuevoGasto')->name('nuevoGasto');
        Route::post('/Gastos/EditarGasto/{id}', 'App\Http\Controllers\CatalogosController@editarGasto')->name('editarGasto');
        Route::post('/Gastos/EliminarGasto/{id}', 'App\Http\Controllers\CatalogosController@eliminarGasto')->name('eliminarGasto');

        Route::get('/Cajas', 'App\Http\Controllers\CatalogosController@indexCaja')->name('indexCaja');
        Route::post('/Cajas/NuevaCaja', 'App\Http\Controllers\CatalogosController@nuevaCaja')->name('nuevaCaja');
        Route::post('/Cajas/EditarCaja/{id}', 'App\Http\Controllers\CatalogosController@editarCaja')->name('editarCaja');
        Route::post('/Cajas/EliminarCaja/{id}', 'App\Http\Controllers\CatalogosController@eliminarCaja')->name('eliminarCaja');
        Route::post('/Cajas/subirDocumentos/{id}/{nombreSuc}/{nombreCaja}', 'App\Http\Controllers\CatalogosController@subirDocumentos')->name('asignarResponsable');

        Route::get('/Cuentas', 'App\Http\Controllers\CatalogosController@indexCuentas')->name('indexCuentas');
        Route::post('/Cuentas/NuevaCuenta', 'App\Http\Controllers\CatalogosController@nuevaCuenta')->name('nuevaCuenta');
        Route::post('/Cuentas/EditarCuenta/{id}', 'App\Http\Controllers\CatalogosController@editarCuenta')->name('editarCuenta');
        Route::post('/Cuentas/CancelarCuenta/{id}', 'App\Http\Controllers\CatalogosController@cancelarCuenta')->name('editarCuenta');


        //Catalogos Generales - Permisos Cuentas
        Route::get('/PermisosCuentas', 'App\Http\Controllers\PermisosCuentasController@indexPermisosCuentas')->name('indexPermisosCuentas');
        Route::post('/PermisosCuentas/AñadirPermiso', 'App\Http\Controllers\PermisosCuentasController@añadirPermisoCuenta')->name('añadirPermisoCuenta');
        Route::get('/PermisosCuentas/EliminarPermiso/{id}', 'App\Http\Controllers\PermisosCuentasController@eliminarPermiso')->name('eliminarPermiso');

        //Catalogos Generales - Puestos
        Route::get('/Puestos', 'App\Http\Controllers\CatalogosController@indexPuestos')->name('indexPuestos');
        Route::post('/Puestos/NuevoPuesto', 'App\Http\Controllers\CatalogosController@nuevoPuesto')->name('nuevoPuesto');
        Route::post('/Puestos/EditarPuesto/{id}', 'App\Http\Controllers\CatalogosController@editarPuesto')->name('editarPuesto');
        Route::post('/Puestos/EliminarPuesto/{id}', 'App\Http\Controllers\CatalogosController@eliminarPuesto')->name('eliminarPuesto');

        //Catalogos Generales - Lugares
        Route::get('/Lugares', 'App\Http\Controllers\CatalogosController@indexestados_ciudades')->name('indexestados_ciudades');
        Route::post('/Estado/NuevoEstado', 'App\Http\Controllers\CatalogosController@nuevoEstado')->name('nuevoEstado');
        Route::post('/Estado/EditarEstado/{id}', 'App\Http\Controllers\CatalogosController@editarEstado')->name('editarEstado');
        Route::post('/Estado/EliminarEstado/{id}', 'App\Http\Controllers\CatalogosController@eliminarEstado')->name('eliminarEstado');

        Route::post('/Ciudad/NuevaCiudad', 'App\Http\Controllers\CatalogosController@nuevaCiudad')->name('nuevaCiudad');
        Route::post('/Ciudad/EditarCiudad/{id}', 'App\Http\Controllers\CatalogosController@editarCiudad')->name('editarCiudad');
        Route::post('/Ciudad/EliminarCiudad/{id}', 'App\Http\Controllers\CatalogosController@eliminarCiudad')->name('eliminarCiudad');
    });


    //Tesorería - Moviminetos de cuentas
    Route::prefix('/Tesoreria/Movimientos')->group(function () {
        Route::get('/', 'App\Http\Controllers\MovimientosController@indexMovimientos')->name('indexMovimientos');
        Route::get('/Reportes/PosicionFinanciera', 'App\Http\Controllers\MovimientosController@reportePosicionFinanciera')->name('reportePosicionFinanciera');
        Route::get('/Reportes/EstadoCuenta', 'App\Http\Controllers\MovimientosController@reporteEstadoCuenta')->name('reporteEstadoCuenta');
        Route::get('/Reportes/ArqueoCajas', 'App\Http\Controllers\MovimientosController@reporteArqueoCajas')->name('reporteArqueoCajas');
        Route::get('/Manejo/{tipo}', 'App\Http\Controllers\MovimientosController@indexManejo')->name('indexManejo');
        Route::post('/Traspasos/{tipo}/{id}', 'App\Http\Controllers\MovimientosController@traspasos')->name('traspasos');
        Route::post('/Ingresar/{tipo}/{id}', 'App\Http\Controllers\MovimientosController@ingreso')->name('ingreso');
        Route::post('/Retirar/{tipo}/{id}', 'App\Http\Controllers\MovimientosController@retirar')->name('retirar');
        Route::get('/ConsultarMovimientos/{tipo}/{id}', 'App\Http\Controllers\MovimientosController@consultarMovimientos')->name('consultarMovimientos');
        Route::get('/Dictamen/{tipo_caja}/{estado}/{id}/{caja}/{saldo}', 'App\Http\Controllers\MovimientosController@dictamen')->name('dictamen');
        Route::get('/Responsable/{tipo}/{empresaid}/{id}/{fecha?}', 'App\Http\Controllers\MovimientosController@indexResponsable')->name('indexResponsable');
        Route::get('/ResponsableCajaChica/{tipo}/{empresaid}/{id}', 'App\Http\Controllers\MovimientosController@indexResponsableChica')->name('indexResponsableChica');


        Route::post('/Responsable/AplicarGasto/{id}', 'App\Http\Controllers\MovimientosController@aplicarGasto')->name('aplicarGasto');
        Route::post('/EntregasEfectivo/{id}', 'App\Http\Controllers\MovimientosController@entregasEfectivo')->name('entregasEfectivo');
        Route::post('/cancelarGasto/{tipo}/{id_tipo}', 'App\Http\Controllers\MovimientosController@cancelarGasto')->name('cancelarGasto');

        //Tesorería - Arqueos
        Route::get('/ControlArqueos/{tipo}/{empresaid}/{id}', 'App\Http\Controllers\MovimientosController@controlarqueos')->name('controlarqueos');
        Route::get('/DetalleArqueo/{tipo}/{empresaid}/{fecha}/{id}', 'App\Http\Controllers\MovimientosController@detalleArqueo')->name('detalleArqueo');
        Route::get('/ControlArqueos/autorizarArqueo/{id}/{caja}/{saldo}', 'App\Http\Controllers\MovimientosController@autorizarArqueo')->name('autorizarArqueo');
        Route::get('/ControlArqueos/cancelarArqueo/{id}', 'App\Http\Controllers\MovimientosController@cancelarArqueo')->name('cancelarArqueo');
        Route::get('/ControlArqueos/editarArqueo/{id}', 'App\Http\Controllers\MovimientosController@editarArqueo')->name('editarArqueo');

        Route::get('/ArqueoCaja/{tipo}/{empresaid}/{id}/{fecha}', 'App\Http\Controllers\MovimientosController@arqueoCaja')->name('arqueoCaja');
        Route::post('/InsertArqueoCaja/{tipo}/{empresaid}/{id}/{fecha}', 'App\Http\Controllers\MovimientosController@InsertArqueoCaja')->name('InsertArqueoCaja');
        Route::post('/SolicitudArqueo/{id}', 'App\Http\Controllers\MovimientosController@solicitarArqueo')->name('solicitarArqueo');
        Route::put('/ArqueoCaja/Editarnotificaciones/{id}', 'App\Http\Controllers\MovimientosController@actualizarNotificacion')->name('actualizarNotificacion');
        Route::Post('/ArqueoCaja/Aceptarpermiso/{idempleado}/{idnotificacion}', 'App\Http\Controllers\MovimientosController@autorizarPermiso')->name('autorizarPermiso');
        Route::get('/ArqueoCaja/comentarArqueo/{id}', 'App\Http\Controllers\MovimientosController@comentarArqueo')->name('comentarArqueo');
        Route::get('/ArqueoCaja/EditarEfectivo/{id_caja}/{id_arqueo}/{id_efect}', 'App\Http\Controllers\MovimientosController@editarEfectivo')->name('editarEfectivo');
    });

    //Tesorería - Notas de Crédito
    Route::prefix('/Tesoreria/NotasCredito')->group(function () {
        Route::get('/', [NotasCreditoController::class, 'index'])->name('notasCredito.index');
        Route::get('/factura/{id}', [NotasCreditoController::class, 'obtenerFactura'])->name('notasCredito.factura');
        Route::post('/emitir', [NotasCreditoController::class, 'emitir'])->name('notasCredito.emitir');
        Route::delete('/{id}', [NotasCreditoController::class, 'destroy'])->name('notasCredito.destroy');
        Route::get('/{id}/ver', [NotasCreditoController::class, 'verFactura'])->name('notasCredito.ver');
        Route::get('/{id}/pdf', [NotasCreditoController::class, 'descargarPdf'])->name('notasCredito.pdf');
    });

    //Tesorería - Notas de Débito
    Route::prefix('/Tesoreria/NotasDebito')->group(function () {
        Route::get('/', [NotasDebitoController::class, 'index'])->name('notasDebito.index');
        Route::get('/factura/{id}', [NotasDebitoController::class, 'obtenerFactura'])->name('notasDebito.factura');
        Route::post('/emitir', [NotasDebitoController::class, 'emitir'])->name('notasDebito.emitir');
        Route::get('/{id}/ver', [NotasDebitoController::class, 'verFactura'])->name('notasDebito.ver');
        Route::get('/{id}/pdf', [NotasDebitoController::class, 'descargarPdf'])->name('notasDebito.pdf');
    });

    //Tesorería - Complementos de Pago
    Route::prefix('/Tesoreria/ComplementosPago')->group(function () {
        Route::get('/', [ComplementosPagoController::class, 'index'])->name('complementosPago.index');
        Route::get('/factura/{id}', [ComplementosPagoController::class, 'obtenerFactura'])->name('complementosPago.factura');
        Route::post('/emitir', [ComplementosPagoController::class, 'emitir'])->name('complementosPago.emitir');
        Route::get('/{id}/ver', [ComplementosPagoController::class, 'verFactura'])->name('complementosPago.ver');
        Route::get('/{id}/pdf', [ComplementosPagoController::class, 'descargarPdf'])->name('complementosPago.pdf');
    });

    //Tesorería - Facturas
    Route::prefix('/Tesoreria/Facturas')->group(function () {
        Route::get('/', [TimbrarNominaController::class, 'indexFacturas'])->name('facturas.index');
        Route::post('/{id}/cancelar', [TimbrarNominaController::class, 'cancelarFacturaEmitida'])->name('facturas.cancelar');
        Route::get('/{id}/cancelacion', [TimbrarNominaController::class, 'cancelacionHub'])->name('facturas.cancelacion');
        Route::get('/{id}/cancelacion/02', [TimbrarNominaController::class, 'cancelacionMotivo02'])->name('facturas.cancelacion.02');
        Route::post('/{id}/cancelacion/02', [TimbrarNominaController::class, 'procesarCancelacionMotivo02'])->name('facturas.cancelacion.02.procesar');
        Route::get('/{id}/cancelacion/03', [TimbrarNominaController::class, 'cancelacionMotivo03'])->name('facturas.cancelacion.03');
        Route::post('/{id}/cancelacion/03', [TimbrarNominaController::class, 'procesarCancelacionMotivo03'])->name('facturas.cancelacion.03.procesar');
        Route::get('/{id}/cancelacion/04', [TimbrarNominaController::class, 'cancelacionMotivo04'])->name('facturas.cancelacion.04');
        Route::post('/{id}/cancelacion/04', [TimbrarNominaController::class, 'procesarCancelacionMotivo04'])->name('facturas.cancelacion.04.procesar');
        Route::get('/{id}/sustitucion', [TimbrarNominaController::class, 'facturacionSustitucion'])->name('facturas.sustitucion');
        Route::post('/{id}/sustitucion', [TimbrarNominaController::class, 'procesarSustitucionCfdi'])->name('facturas.sustitucion.procesar');
        Route::get('/{id}/ver', [TimbrarNominaController::class, 'verFacturaEmitida'])->name('facturas.ver');
        Route::get('/{id}/pdf', [TimbrarNominaController::class, 'descargarPdfFacturaEmitida'])->name('facturas.pdf');
        Route::get('/{id}/representacion', [TimbrarNominaController::class, 'representacionImpresaFactura'])->name('facturas.representacion');
    });

    //Reportes Tesoreria
    Route::get('/ReportesTesoreria', 'App\Http\Controllers\ReportesController@indexReportesTesoreria')->name('reportesTesoreria');
    Route::get('/ReportesTesoreria/Desembolsos', 'App\Http\Controllers\ReportesController@reporte_desembolsos')->name('reporte_desembolsos');
    Route::get('/ReportesTesoreria/Gastos', 'App\Http\Controllers\ReportesController@reporte_gastos')->name('reporte_gastos');
    Route::get('/ReportesTesoreria/Ingresos', 'App\Http\Controllers\ReportesController@reporte_ingresos')->name('reporte_ingresos');

    //Formatos
    Route::get('/DescargarFormatos', 'App\Http\Controllers\HomeController@Formatos')->name('Formatos');
    Route::post('/AgregarFormato', 'App\Http\Controllers\HomeController@agregarFormato')->name('agregarFormato');
    Route::get('/EliminarFormato/{id}', 'App\Http\Controllers\HomeController@eliminarFormato')->name('eliminarFormato');

    //Route::get('/paginaprueba', 'App\Http\Controllers\valeraController@paginaprueba')->name('paginaprueba');
//Route::get('/vercomprobante', 'App\Http\Controllers\valeraController@vercomprobante')->name('vercomprobante');
    //Route::get('/capital','App\Http\Controllers\valesController@capital')->name('capital');

    Route::prefix('/licitaciones')->group(function () {
        Route::get('/', [LicitacionController::class,'index'])->name('licitaciones.index');
        Route::get('/enviarcorreo/{id}', [LicitacionController::class, 'enviarCorreos'])->name('licitaciones.enviarcorreo');
        Route::get('/show/{id}',[LicitacionController::class,'show'])->name('licitaciones.show');
        Route::get('/create',[LicitacionController::class,'create'])->name('licitaciones.create');
        Route::get('/edit/{id}',[LicitacionController::class,'edit'])->name('licitaciones.editar');
        Route::get('/comparar/{id}',[LicitacionController::class,'comparar'])->name('licitaciones.comparar');
        Route::put('/update/{id}',[LicitacionController::class,'update'])->name('licitaciones.update');
        Route::put('/adjudicar-producto/{id}',[LicitacionController::class,'adjudicarPorProducto'])->name('licitaciones.adjudicarPorProducto');
        Route::post('/store',[LicitacionController::class,'store'])->name('licitaciones.store');
        Route::post('/storeSolicitud',[LicitacionController::class,'storeSolicitud'])->name('licitaciones.storeSolicitud');
        Route::delete('/destroy/{id}', [LicitacionController::class,'destroy'])->name('licitaciones.destroy');
    });

    Route::prefix('/OrdenCompras')->group(function () {
        Route::get('/', [OrdCompraController::class,'index'])->name('ordcompras.index');
        Route::get('/show/{id}',[OrdCompraController::class,'show'])->name('ordcompras.show');
        Route::get('/create',[OrdCompraController::class,'create'])->name('ordcompras.create');
        Route::get('/edit/{id}',[OrdCompraController::class,'edit'])->name('ordcompras.editar');
        Route::put('/update/{id}',[OrdCompraController::class,'update'])->name('ordcompras.update');
        Route::post('/store',[OrdCompraController::class,'store'])->name('ordcompras.store');
        Route::delete('/destroy/{id}', [OrdCompraController::class,'destroy'])->name('ordcompras.destroy');
        Route::post('/generardesdelicitacion', [OrdCompraController::class, 'generarDesdeLicitacion'])->name('ordcompras.generardesdelicitacion');
        Route::put('/cambiarEstado/{id}', [OrdCompraController::class, 'cambiarEstado'])->name('ordcompras.cambiarEstado');
        Route::get('/pdf/{id}', [OrdCompraController::class, 'generarPDF'])->name('ordcompras.pdf');
    });

    //modulo Proveedores
    Route::get('/proveedores', 'App\Http\Controllers\ProveedorController@indexproveedores')->name('proveedores');
    Route::get('/historial_proveedores', 'App\Http\Controllers\ProveedorController@historialproveedores')->name('historial_proveedores');
    Route::get('/licitacion_proveedores', 'App\Http\Controllers\ProveedorController@licitacionesproveedores')->name('licitacion_proveedores');
    Route::post('/insertarproveedor', 'App\Http\Controllers\ProveedorController@insertarproveedor')->name('insertarproveedor');
    Route::get('/editar_proveedor/{id}', 'App\Http\Controllers\ProveedorController@editarproveedores')->name('editar_proveedor');
    Route::post('/updateproveedor/{id}', 'App\Http\Controllers\ProveedorController@updateproveedor')->name('updateproveedor');
    Route::get('/EliminarProveedor/{id}', 'App\Http\Controllers\ProveedorController@EliminarProveedor')->name('EliminarProveedor');
    Route::get('/ProductosProveedor/{id}', 'App\Http\Controllers\ProveedorController@ProductosProveedor')->name('ProductosProveedor');
    Route::post('/ProductosProveedor/{idprov}',  'App\Http\Controllers\ProveedorController@InsertProductoProveedor')->name('InsertProductoProveedor');
    Route::get('/EditarProductoProveedor/{id}',  'App\Http\Controllers\ProveedorController@EditarProductoProveedor')->name('EditarProductoProveedor');
    Route::post('/updateprod_prove/{id}', 'App\Http\Controllers\ProveedorController@updateprod_prove')->name('updateprod_prove');

    // Rutas para Personas de Atención
    Route::post('/insertarPersonaAtencion', 'App\Http\Controllers\ProveedorController@insertarPersonaAtencion')->name('insertarPersonaAtencion');
    Route::post('/actualizarPersonaAtencion/{id}', 'App\Http\Controllers\ProveedorController@actualizarPersonaAtencion')->name('actualizarPersonaAtencion');
    Route::get('/eliminarPersonaAtencion/{id}', 'App\Http\Controllers\ProveedorController@eliminarPersonaAtencion')->name('eliminarPersonaAtencion');
    
    // API para obtener días de crédito del proveedor
    Route::get('/api/proveedor/{id}/dias-credito', function($id) {
        try {
            $proveedor = \App\Models\Proveedor::find($id);
            if ($proveedor) {
                return response()->json([
                    'success' => true,
                    'dias_credito' => $proveedor->dias_credito ?? null
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => 'Proveedor no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información del proveedor'
            ], 500);
        }
    });
    
    // API para obtener días de crédito del cliente
    Route::get('/api/cliente/{id}/dias-credito', function($id) {
        try {
            $cliente = \App\Models\Clientes::find($id);
            if ($cliente) {
                return response()->json([
                    'success' => true,
                    'dias_credito' => $cliente->dias_credito ?? null,
                    'modo_pago' => $cliente->modo_pago ?? 'contado'
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información del cliente'
            ], 500);
        }
    });

    //Modulo de Pagos
    Route::get('/proveedores', 'App\Http\Controllers\ProveedorController@indexproveedores')->name('proveedores');

    // Modulo Externo proveedores
    Route::get('/ext_pagos', 'App\Http\Controllers\ExternoPoroveedorController@EnviarFacturasIndex')->name('ext_pagos');
    Route::post('/procesar-xml', 'App\Http\Controllers\ExternoPoroveedorController@procesarXml')->name('factura.procesar');
    // infromacion licitacionoes modulo del externo
    Route::get('/ext_licitaciones', 'App\Http\Controllers\ExternoPoroveedorController@licitacionesExtIndex')->name('ext_licitaciones');
    Route::get('/licitaciondet/{id}', 'App\Http\Controllers\ExternoPoroveedorController@Anexarcosteslicitacion')->name('licitaciondet');
    Route::get('/showlicitacion/{id}/{id_prov}','App\Http\Controllers\ExternoPoroveedorController@showexterno')->name('showlicitacion');
    Route::post('/ext_licitaciones/update/{id}/{id_prov}', 'App\Http\Controllers\ExternoPoroveedorController@update')->name('ext_licitaciones.update');
    Route::post('/ext_licitaciones/enviar/{id}', [ExternoPoroveedorController::class, 'enviarLicitacion'])->name('ext_licitaciones.enviar');

    //Rutas de almacenes
    Route::get('/almacenes', 'App\Http\Controllers\AlmacenController@almacenes')->name('almacenes');
    Route::get('/almacenes/buscar-existencias', [App\Http\Controllers\AlmacenController::class, 'buscarExistenciasProducto'])->name('almacenes.buscar-existencias');
    Route::get('/pinsertaalm',[App\Http\Controllers\AlmacenController::class, 'pinsertaalm'])->name('pinsertaalm');
    Route::post('/minsertaalm',[App\Http\Controllers\AlmacenController::class, 'minsertaalm'])->name('minsertaalm');
    Route::get('/pactualizaalmacen/{id}',[App\Http\Controllers\AlmacenController::class, 'pactualizaalmacen'])->name('pactualizaalmacen');
    Route::post('/mactualizaalm',[App\Http\Controllers\AlmacenController::class, 'mactualizaalm'])->name('mactualizaalm');


    //Rutas Ubicaciones
    Route::get('/pubicaciones/{id}',[App\Http\Controllers\UbicacionesController::class, 'pubicaciones'])->name('pubicaciones');
    Route::get('/pinsertaubi/{idalm}/{nomalm}',[App\Http\Controllers\UbicacionesController::class, 'pinsertaubi'])->name('pinsertaubi');
    Route::post('/minsertaubi',[App\Http\Controllers\UbicacionesController::class, 'minsertaubi'])->name('minsertaubi');
    Route::get('/pactualizaubi/{idalm}/{id}/{nomalm}', 'App\Http\Controllers\UbicacionesController@pactualizaubi')->name('pactualizaubi');
    Route::post('/mactualizaubi',[App\Http\Controllers\UbicacionesController::class, 'mactualizaubi'])->name('mactualizaubi');


    //Rutas de productos
    Route::get('/productos', 'App\Http\Controllers\ProductosController@productos')->name('productos');
    Route::get('/pinsertaroductos',[App\Http\Controllers\ProductosController::class, 'pinsertaroductos'])->name('pinsertaroductos');
    Route::post('/productos/categoria', [App\Http\Controllers\ProductosController::class, 'storeCategoriaProducto'])->name('productos.categoria.store');
    Route::post('/minsertapro',[App\Http\Controllers\ProductosController::class, 'minsertapro'])->name('minsertapro');
    Route::get('/pactualizaprod/{id}',[App\Http\Controllers\ProductosController::class, 'pactualizaprod'])->name('pactualizaprod');
    Route::post('/mactualizaprod',[App\Http\Controllers\ProductosController::class, 'mactualizaprod'])->name('mactualizaprod');


    Route::prefix('entradas')->group(function () {
        Route::get('/', [EntradaInventarioController::class, 'index'])->name('Entradas.index');
        //Route::post('/create', [EntradaInventarioController::class, 'store'])->name('Entradas.store');
        Route::post('/store', [EntradaInventarioController::class, 'store'])->name('Entradas.store');
        Route::get('/create/{ordenCompraId}', [EntradaInventarioController::class, 'create'])->name('Entradas.create');
        Route::get('/edit/{id}', [EntradaInventarioController::class, 'edit'])->name('Entradas.edit');
        Route::get('/show/{id}', [EntradaInventarioController::class, 'show'])->name('Entradas.show');
        Route::delete('/{id}', [EntradaInventarioController::class, 'destroy'])->name('Entradas.destroy');
    });

    //rutas de inventarios
    Route::get('/inventario', 'App\Http\Controllers\InventarioController@inventario')->name('inventario');
    Route::get('/ubicaciondet/{id}', 'App\Http\Controllers\InventarioController@ubicaciondet')->name('ubicaciondet');
    Route::get('/detalleubi/{idalm}/{idubi}/{nomalm}', 'App\Http\Controllers\InventarioController@detalleubi')->name('detalleubi');
    Route::get('/transferenciaproductos', 'App\Http\Controllers\InventarioController@mtransferenciaentrealmacenes')->name('transferenciaproductos');
    Route::get('/mrecepciontranferencia/{ide}/{catidadactual}/{cantidadtranferencia}', 'App\Http\Controllers\InventarioController@mrecepciontranferencia')->name('mrecepciontranferencia');
    Route::post('/entradamanualexistente/{idubi}/{idpro}', 'App\Http\Controllers\InventarioController@entradamanualexistente')->name('entradamanualexistente');
    Route::post('/entradamanualnuevopro', 'App\Http\Controllers\InventarioController@entradamanualnuevopro')->name('entradamanualnuevopro');
    Route::get('/inventario/buscar-productos-nuevos', 'App\\Http\\Controllers\\InventarioController@buscarProductosNuevos')->name('inventario.buscar-productos-nuevos');
    Route::get('/historial_alm', 'App\Http\Controllers\InventarioController@historial_alm')->name('historial_alm');

    //Timbrado de nominas
    Route::get('/timbrarnomina/{id}', 'App\Http\Controllers\TimbrarNominaController@indextimrado')->name('timbrarnomina');
    Route::get('/vernominatimbrada/{id}', 'App\Http\Controllers\TimbrarNominaController@vernominatimbrada')->name('vernominatimbrada');
    Route::get('/verfactura/{id}', 'App\Http\Controllers\TimbrarNominaController@verfacturanomina')->name('verfactura');
    Route::get('/verfacturaaguinaldo/{id}', 'App\Http\Controllers\TimbrarNominaController@verfacturaaguinaldo')->name('verfacturaaguinaldo');
    Route::get('/verfacturaproductos/{folio}/{uuid}', 'App\Http\Controllers\TimbrarNominaController@verfacturaproductos')->name('verfacturaproductos');
    Route::get('/TimbarEmpleado/{id_pago_det}/{emp_nomina_enc}', 'App\Http\Controllers\TimbrarNominaController@TimbarEmpleado')->name('timbarEmpleado');
    
    //Facturacoin Timbrado
    Route::get('/facturacion/{id}/{servicio}', 'App\Http\Controllers\TimbrarNominaController@facturacion')->name('facturacion');
    Route::get('/facturacion/{id}/pedido/carta-porte', [TimbrarNominaController::class, 'facturacionPedidoCartaPorte'])->name('facturacion.pedido.carta_porte');
    Route::post('/procesar-factura-carta-porte', [TimbrarNominaController::class, 'procesarFacturaCartaPorte'])->name('procesar.factura.carta_porte');
    Route::post('/Finanzas/facturar-servicio', 'App\Http\Controllers\FinanzasController@facturarServicio')->name('finanzas.facturar-servicio');

    Route::prefix('/Servicios')->group(function () {
            Route::get('/', 'App\Http\Controllers\ServiciosController@index')->name('servicios.index');
            Route::post('/Finalizar/{id}', 'App\Http\Controllers\ServiciosController@finalizarServicio')->name('servicios.finalizar');
            //vistas captura
            Route::get('/Captura/Datos/{tipo}', 'App\Http\Controllers\ServiciosController@pcaptura')->name('servicios.pcaptura');
            Route::get('/Captura/Materiales/{tipo}/{id}', 'App\Http\Controllers\ServiciosController@pmateriales')->name('servicios.pmateriales');
            Route::get('/Captura/MaterialesProyectos/{tipo}/{id}', 'App\Http\Controllers\ServiciosController@pmaterialesproyectos')->name('servicios.pmaterialesproyectos');
            Route::get('/Captura/CostosProyecto/{tipo}/{id}', 'App\Http\Controllers\ServiciosController@pcostosproyecto')->name('servicios.pcostosproyecto');
            Route::get('/Captura/Costos/{tipo}/{id}', 'App\Http\Controllers\ServiciosController@pcostos')->name('servicios.pcostos');

            // metodos captura
            Route::post('/Captura/InsertarDatos/{tipo}', 'App\Http\Controllers\ServiciosController@mcaptura')->name('servicios.mcaptura');
            Route::get('/Captura/AgregarProducto/{tipo}/{id}', 'App\Http\Controllers\ServiciosController@magregarproducto')->name('servicios.magregarproducto');
            Route::get('/Captura/EliminarProducto/{tipo}/{id}/{idproserv}/{id_prod}/{cantidad}', 'App\Http\Controllers\ServiciosController@meliminarproducto')->name('servicios.meliminarproducto');
            Route::get('/Captura/InsertarMateriales/{tipo}/{id}', 'App\Http\Controllers\ServiciosController@mmateriales')->name('servicios.mmateriales');
            Route::get('/Captura/InsertarMaterialesProyecto/{tipo}/{id}', 'App\Http\Controllers\ServiciosController@mmaterialesproyecto')->name('servicios.mmaterialesproyecto');
            Route::post('/Captura/InsertarCostos/{tipo}', 'App\Http\Controllers\ServiciosController@mcostos')->name('servicios.mcostos');
            
            // métodos para empleados en servicios
            Route::post('/Captura/GuardarEmpleado', 'App\Http\Controllers\ServiciosController@guardarEmpleadoServicio')->name('servicios.guardarEmpleado');
            Route::post('/Captura/EliminarEmpleado', 'App\Http\Controllers\ServiciosController@eliminarEmpleadoServicio')->name('servicios.eliminarEmpleado');
            
            // métodos para conceptos en servicios
            Route::post('/Captura/GuardarConcepto', 'App\Http\Controllers\ServiciosController@guardarConceptoServicio')->name('servicios.guardarConcepto');
            Route::post('/Captura/ActualizarConcepto', 'App\Http\Controllers\ServiciosController@actualizarConceptoServicio')->name('servicios.actualizarConcepto');
            Route::post('/Captura/EliminarConcepto', 'App\Http\Controllers\ServiciosController@eliminarConceptoServicio')->name('servicios.eliminarConcepto');
            
            // método para guardar información en tblservicios_det
            Route::post('/Captura/GuardarServicioDet', 'App\Http\Controllers\ServiciosController@guardarServicioDet')->name('servicios.guardarServicioDet');

            // métodos para archivos de ingeniería
            Route::post('/Captura/SubirArchivoIngenieria', 'App\Http\Controllers\ServiciosController@subirArchivoIngenieria')->name('servicios.subirArchivoIngenieria');
            Route::post('/Captura/EliminarArchivoIngenieria', 'App\Http\Controllers\ServiciosController@eliminarArchivoIngenieria')->name('servicios.eliminarArchivoIngenieria');
            Route::get('/Captura/ObtenerArchivoIngenieria/{id}', 'App\Http\Controllers\ServiciosController@obtenerArchivoIngenieria')->name('servicios.obtenerArchivoIngenieria');
            Route::get('/Captura/DescargarArchivoIngenieria/{id}', 'App\Http\Controllers\ServiciosController@descargarArchivoIngenieria')->name('servicios.descargarArchivoIngenieria');

            //validaciones
            Route::get('/ValidaProgreso/{tipo}/{nivel_progreso}/{id}', 'App\Http\Controllers\ServiciosController@mvalidaprogreso')->name('servicios.mvalidaprogreso');
            Route::post('/validar-folio', 'App\Http\Controllers\ServiciosController@validarFolio')->name('servicios.validarFolio');
            
            // métodos para personas de atención
            Route::get('/obtenerPersonasAtencion/{id_cliente}', 'App\Http\Controllers\ServiciosController@obtenerPersonasAtencion')->name('servicios.obtenerPersonasAtencion');
            Route::post('/crearPersonaAtencion', 'App\Http\Controllers\ServiciosController@crearPersonaAtencion')->name('servicios.crearPersonaAtencion');

            //vistas editar
            Route::get('/Editar/Datos/{tipo}/{id}', 'App\Http\Controllers\ServiciosController@peditcaptura')->name('servicios.peditcaptura');
            Route::get('/Editar/Materiales/{tipo}/{id}', 'App\Http\Controllers\ServiciosController@peditmateriales')->name('servicios.peditmateriales');
            Route::get('/Editar/Costos/{tipo}/{id}', 'App\Http\Controllers\ServiciosController@peditcostos')->name('servicios.peditcostos');

            
            //metodos editar
            Route::post('/Editar/InsertarDatos/{tipo}/{id}', 'App\Http\Controllers\ServiciosController@meditcaptura')->name('servicios.meditcaptura');
            Route::get('/Editar/InsertarMateriales/{tipo}/{id}', 'App\Http\Controllers\ServiciosController@meditmateriales')->name('servicios.meditmateriales');
            Route::post('/Editar/InsertarCostos/{tipo}/{id}', 'App\Http\Controllers\ServiciosController@meditcostos')->name('servicios.meditcostos');

            //metodos ver
            Route::get('/Ver/Inicio/{tipo}/{id}', 'App\Http\Controllers\ServiciosController@pverdetalle')->name('servicios.pverdetalle');
            Route::get('/GuardarObservacion/{id}', 'App\Http\Controllers\ServiciosController@mGuardarObservacion')->name('servicios.mGuardarObservacion');
            Route::get('/GuardarCotizacion/{id}', 'App\Http\Controllers\ServiciosController@mGuardarCotizacion')->name('servicios.mGuardarCotizacion');
            
            //verficar
            Route::get('/mactualizarProducto', 'App\Http\Controllers\ServiciosController@mactualizarProducto')->name('servicios.mactualizarProducto');

            //cotizaciones
            Route::get('/Cotizacion/{id}/{tipo}', 'App\Http\Controllers\ServiciosController@ver_cotizacion')->name('servicios.ver_cotizacion');
            Route::post('/CrearCotizacion/{id}', 'App\Http\Controllers\ServiciosController@crear_cotizacion')->name('servicios.crear_cotizacion');
            Route::post('/Cotizacion/CambiarEstado/{id}', 'App\Http\Controllers\ServiciosController@cambiarEstadoCotizacion')->name('servicios.cambiar_estado_cotizacion');
            Route::delete('/Cotizacion/Eliminar/{id}', 'App\Http\Controllers\ServiciosController@eliminarCotizacion')->name('servicios.eliminar_cotizacion');
    });

    // Rutas para Proyectos
    Route::get('/Proyectos', [AdminProyectoController::class, 'proyecto'])->name('proyectos.index');
    Route::post('/proyectos/guardar-division', [AdminProyectoController::class, 'guardarDivisionProyecto'])->name('proyectos.guardar-division');
    Route::get('/proyectos/divisiones/{idServicio?}', [AdminProyectoController::class, 'listarDivisiones'])->name('proyectos.divisiones');
    Route::post('/proyectos/{idServicio}/agregar-division-siguiente', [AdminProyectoController::class, 'agregarSiguienteDivision'])->name('proyectos.agregar-division-siguiente');
    Route::post('/proyectos/guardar-partida', [AdminProyectoController::class, 'guardarPartida'])->name('proyectos.guardar-partida');
    Route::get('/proyectos/detalle-partidas/{idDivision}', [AdminProyectoController::class, 'detallePartidas'])->name('proyectos.detalle-partidas');
    Route::get('/proyectos/division/{idDivision}/partidas', [AdminProyectoController::class, 'detallePartidasPagina'])->name('proyectos.division.partidas');
    Route::post('/proyectos/division/{idDivision}/actualizar-monto', [AdminProyectoController::class, 'actualizarMontoDivision'])->name('proyectos.division.actualizar-monto');
    Route::post('/proyectos/division/{idDivision}/eliminar-autorizado', [AdminProyectoController::class, 'eliminarDivisionAutorizado'])->name('proyectos.division.eliminar-autorizado');
    Route::get('/proyectos/documento/{id}', [AdminProyectoController::class, 'verDocumentoPartida'])->name('proyectos.documento');
    Route::get('/proyectos/ver-documento-partida/{id}', [AdminProyectoController::class, 'verDocumentoPartida'])->name('proyectos.ver-documento-partida');
    Route::get('/proyectos/cotizar/{idServicio}', [AdminProyectoController::class, 'cotizarProyecto'])->name('proyectos.cotizar');
    Route::post('/proyectos/cambiar-estado', [AdminProyectoController::class, 'cambiarEstado'])->name('proyectos.cambiar-estado');
    Route::get('/proyectos/productos-existencias', [AdminProyectoController::class, 'productosConExistencia'])->name('proyectos.productos-existencias');
    Route::get('/proyectos/productos-existencias/{productoId}', [AdminProyectoController::class, 'existenciasDetalladasPorProducto'])->name('proyectos.productos-existencias-detalle');
Route::get('/proyectos/debug-productos', [AdminProyectoController::class, 'debugProductos'])->name('proyectos.debug-productos');
    Route::post('/proyectos/bajar-inventario', [AdminProyectoController::class, 'bajarInventario'])->name('proyectos.bajar-inventario');
    Route::get('/proyectos/indicadores/{idServicio}', [AdminProyectoController::class, 'indicadoresProyecto'])->name('proyectos.indicadores');
    Route::get('/proyectos/reportes/rfq', [AdminProyectoController::class, 'reporteRfqs'])->name('proyectos.reportes.rfq');
    Route::post('/proyectos/ingreso/guardar', [AdminProyectoController::class, 'guardarIngresoProyecto'])->name('proyectos.guardar-ingreso-proyecto');
    Route::get('/proyectos/gastos/{idServicio}', [AdminProyectoController::class, 'gastosProyecto'])->name('proyectos.gastos');
    // Montos máximos por tipo de ingreso en proyectos
    Route::get('/proyectos/{idServicio}/tipos-ingreso-maximos', [AdminProyectoController::class, 'obtenerTiposIngresoMaximos'])->name('proyectos.tipos-ingreso-maximos');
    Route::post('/proyectos/{idServicio}/tipos-ingreso-maximos', [AdminProyectoController::class, 'guardarTiposIngresoMaximos'])->name('proyectos.guardar-tipos-ingreso-maximos');

// Ruta para facturación de proyectos
Route::get('/proyectos/facturacion/{idProyecto}/{idPartida}', [AdminProyectoController::class, 'facturacionProyecto'])->name('proyectos.facturacion');

    // Ruta para guardar conceptos de proyectos
    Route::post('/Servicios/Captura/GuardarConceptosProyectos/{id}', [ServiciosController::class, 'guardarConceptosProyectos'])->name('servicios.guardarConceptosProyectos');

    // Ruta de prueba para el buscador de clientes
    Route::get('/test-buscador', function () {
        return view('test-buscador');
    })->name('test.buscador');

    // Ruta de prueba simple para Livewire
    Route::get('/test-simple', function () {
        return view('test-simple');
    })->name('test.simple');

    // Ruta de prueba para Livewire
    Route::get('/test-livewire', function () {
        return view('test-livewire');
    })->name('test.livewire');

    // Ruta de prueba para componente simple
    Route::get('/test-component', function () {
        return view('test-component');
    })->name('test.component');

    // Ruta de prueba para BuscadorCliente
    Route::get('/test-buscador-simple', function () {
        return view('test-buscador-simple');
    })->name('test.buscador.simple');

    Route::get('/mesa_control', [MesaControlController::class, 'index'])->name('mesa_control');
    Route::get('/empaque', [MesaControlController::class, 'empaque'])->name('empaque');
    Route::get('/embarques', [MesaControlController::class, 'embarques'])->name('embarques');
    Route::get('/Embarques', [MesaControlController::class, 'embarques'])->name('Embarques');
    Route::get('/embarque', [MesaControlController::class, 'embarques'])->name('embarque');
    Route::get('/devoluciones', [MesaControlController::class, 'devoluciones'])->name('devoluciones');
    Route::get('/Devoluciones', [MesaControlController::class, 'devoluciones'])->name('Devoluciones');

    Route::get('/Gestion_alumnos/alumnos', [GestionAlumnosViewController::class, 'alumnos'])->name('gestion_alumnos.alumnos');

    Route::get('/cotizaciones', [VentasController::class, 'cotizaciones'])->name('ventas.cotizaciones');
    Route::get('/cotizaciones/historial', [VentasController::class, 'historialCotizaciones'])->name('ventas.cotizaciones.historial');
    Route::get('/pedidos', [VentasController::class, 'pedidos'])->name('ventas.pedidos');
    Route::get('/pedidos/logistica', [VentasController::class, 'logistica'])->name('ventas.pedidos.logistica');
    Route::post('/pedidos/logistica/rutas', [VentasController::class, 'crearRutaLogistica'])->name('ventas.pedidos.logistica.crear_ruta');
    Route::post('/pedidos/logistica/rutas/{rutaId}/agregar', [VentasController::class, 'agregarPedidosRutaLogistica'])->name('ventas.pedidos.logistica.agregar');
    Route::delete('/pedidos/logistica/rutas/{rutaId}', [VentasController::class, 'eliminarRutaLogistica'])->name('ventas.pedidos.logistica.eliminar');
    Route::delete('/pedidos/logistica/rutas/{rutaId}/cargas/{cargaId}', [VentasController::class, 'quitarPedidoRutaLogistica'])->name('ventas.pedidos.logistica.quitar_pedido');
    Route::post('/pedidos/{id}/cargar', [VentasController::class, 'cargarPedido'])->name('ventas.pedido.cargar');
    Route::post('/pedidos/{id}/actualizar', [VentasController::class, 'actualizarPedido'])->name('ventas.pedido.actualizar');
    Route::post('/pedidos/{id}/avisar-carga', [VentasController::class, 'avisarCarga'])->name('ventas.pedido.avisar_carga');

    // Cargas / rutas de camión (menú Almacenes)
    Route::get('/almacen/cargas', [App\Http\Controllers\CargasController::class, 'rutas'])->name('almacen.cargas');
    Route::post('/almacen/cargas', [App\Http\Controllers\CargasController::class, 'crearRuta'])->name('almacen.cargas.crear');
    Route::post('/almacen/cargas/camiones', [App\Http\Controllers\CargasController::class, 'storeCamion'])->name('almacen.cargas.camiones.store');
    Route::post('/almacen/cargas/choferes', [App\Http\Controllers\CargasController::class, 'storeChofer'])->name('almacen.cargas.choferes.store');
    Route::post('/almacen/cargas/{id}/agregar', [App\Http\Controllers\CargasController::class, 'agregarCargas'])->name('almacen.cargas.agregar');
    Route::post('/almacen/cargas/{id}/ordenes', [App\Http\Controllers\CargasController::class, 'actualizarOrdenes'])->name('almacen.cargas.ordenes');
    Route::post('/almacen/cargas/{id}/reordenar', [App\Http\Controllers\CargasController::class, 'reordenarOrdenes'])->name('almacen.cargas.reordenar');
    Route::get('/almacen/cargas/{id}/secuencia.pdf', [App\Http\Controllers\CargasController::class, 'pdfSecuencia'])->name('almacen.cargas.pdf');
    Route::post('/almacen/cargas/{id}/quitar/{cargaId}', [App\Http\Controllers\CargasController::class, 'quitarCarga'])->name('almacen.cargas.quitar');
    Route::post('/almacen/cargas/{id}/ubicar-material', [App\Http\Controllers\CargasController::class, 'ubicarMaterial'])->name('almacen.cargas.ubicar');
    Route::post('/almacen/cargas/{id}/iniciar-carga', [App\Http\Controllers\CargasController::class, 'iniciarCarga'])->name('almacen.cargas.iniciar');
    Route::post('/almacen/cargas/{id}/checklist', [App\Http\Controllers\CargasController::class, 'guardarChecklist'])->name('almacen.cargas.checklist');
    Route::post('/almacen/cargas/{id}/evidencia', [App\Http\Controllers\CargasController::class, 'subirEvidencia'])->name('almacen.cargas.evidencia');
    Route::post('/almacen/cargas/{id}/remision/{cargaId}', [App\Http\Controllers\CargasController::class, 'firmarRemision'])->name('almacen.cargas.remision');
    Route::post('/almacen/cargas/{id}/salida', [App\Http\Controllers\CargasController::class, 'darSalida'])->name('almacen.cargas.salida');
    // Alias legado desde Ventas
    Route::get('/pedidos/rutas-carga', function () {
        return redirect()->route('almacen.cargas', request()->query());
    })->name('ventas.rutas_carga');

    Route::post('/pedidos/{id}/enviar-produccion', [VentasController::class, 'enviarPedidoAProduccion'])->name('ventas.pedido.enviar_produccion');
    Route::post('/pedidos/{id}/enviar-compras', [VentasController::class, 'enviarFaltantesACompras'])->name('ventas.pedido.enviar_compras');

    Route::get('/compras/reporte-no-existencias', [ReporteNoExistenciasController::class, 'index'])->name('compras.reporte_no_existencias.index');
    Route::post('/compras/reporte-no-existencias/{id}/estatus', [ReporteNoExistenciasController::class, 'cambiarEstatus'])->name('compras.reporte_no_existencias.estatus');
    Route::post('/compras/reporte-no-existencias/generar-oc', [ReporteNoExistenciasController::class, 'generarOrdenCompra'])->name('compras.reporte_no_existencias.generar_oc');
    Route::post('/pedidos/nuevo/limpiar', [VentasController::class, 'limpiarBorradorPedido'])->name('ventas.pedido.limpiar');
    Route::post('/pedidos/nuevo/agregar-linea', [VentasController::class, 'agregarLineaPedido'])->name('ventas.pedido.agregar_linea');
    Route::post('/pedidos/nuevo/actualizar-linea', [VentasController::class, 'actualizarLineaPedido'])->name('ventas.pedido.actualizar_linea');
    Route::post('/pedidos/nuevo/recalcular', [VentasController::class, 'recalcularPedido'])->name('ventas.pedido.recalcular');
    Route::post('/pedidos/nuevo/quitar-linea', [VentasController::class, 'quitarLineaPedido'])->name('ventas.pedido.quitar_linea');
    Route::post('/pedidos/nuevo/guardar', [VentasController::class, 'guardarPedido'])->name('ventas.pedido.guardar');
    Route::post('/pedidos/cotizacion/{id}/cargar', [VentasController::class, 'cargarCotizacion'])->name('ventas.cotizacion.cargar');
    Route::get('/pedidos/cotizacion/{id}/pdf', [VentasController::class, 'descargarCotizacionPdf'])->name('ventas.cotizacion.pdf');
    Route::get('/pedidos/cotizacion/{id}/archivo-cliente', [VentasController::class, 'descargarArchivoClienteCotizacion'])->name('ventas.cotizacion.archivo_cliente');
    Route::post('/pedidos/cotizacion/{id}/convertir-pedido', [VentasController::class, 'convertirCotizacionAPedido'])->name('ventas.cotizacion.convertir_pedido');
    Route::get('/pedidos/cotizacion/productos-coincidencias', [VentasController::class, 'productosCoincidenciasCotizacion'])->name('ventas.cotizacion.productos_coincidencias');
    Route::get('/pedidos/cotizacion/producto/{productoId}', [VentasController::class, 'productoCotizacionDetalle'])->name('ventas.cotizacion.producto_detalle');
    Route::post('/pedidos/cotizacion/buscar-productos', [VentasController::class, 'buscarProductosCotizacion'])->name('ventas.cotizacion.buscar_productos');
    Route::post('/pedidos/cotizacion/agregar-linea', [VentasController::class, 'agregarLineaCotizacion'])->name('ventas.cotizacion.agregar_linea');
    Route::post('/pedidos/cotizacion/actualizar-linea', [VentasController::class, 'actualizarLineaCotizacion'])->name('ventas.cotizacion.actualizar_linea');
    Route::post('/pedidos/cotizacion/recalcular', [VentasController::class, 'recalcularCotizacion'])->name('ventas.cotizacion.recalcular');
    Route::post('/pedidos/cotizacion/quitar-linea', [VentasController::class, 'quitarLineaCotizacion'])->name('ventas.cotizacion.quitar_linea');
    Route::post('/pedidos/cotizacion/limpiar', [VentasController::class, 'limpiarBorradorCotizacion'])->name('ventas.cotizacion.limpiar');
    Route::post('/pedidos/cotizacion/guardar', [VentasController::class, 'guardarCotizacion'])->name('ventas.cotizacion.guardar');
    Route::post('/pedidos/catalogos/tipos-flete', [VentasController::class, 'storeTipoFlete'])->name('ventas.tipos_flete.store');
    Route::post('/pedidos/catalogos/tipos-iva', [VentasController::class, 'storeTipoIva'])->name('ventas.tipos_iva.store');
    // Venta de producción: tipo de venta y materiales de receta.
    Route::post('/pedidos/nuevo/tipo-venta', [VentasController::class, 'establecerTipoVentaPedido'])->name('ventas.pedido.tipo_venta');
    Route::post('/pedidos/cotizacion/tipo-venta', [VentasController::class, 'establecerTipoVentaCotizacion'])->name('ventas.cotizacion.tipo_venta');
    Route::get('/pedidos/produccion/productos-coincidencias', [VentasController::class, 'productosProduccionCoincidencias'])->name('ventas.produccion.productos_coincidencias');
    Route::get('/pedidos/produccion/producto/{productoId}/materiales', [VentasController::class, 'productoRecetaMateriales'])->name('ventas.produccion.materiales');

    Route::get('/maquinas', [MaquinariaController::class, 'index'])->name('produccion.maquinas');
    Route::post('/maquinas/nueva', [MaquinariaController::class, 'store'])->name('produccion.maquinas.store');
    Route::post('/maquinas/editar/{id}', [MaquinariaController::class, 'update'])->name('produccion.maquinas.update');
    Route::post('/maquinas/inactivar/{id}', [MaquinariaController::class, 'inactivar'])->name('produccion.maquinas.inactivar');
    Route::post('/maquinas/activar/{id}', [MaquinariaController::class, 'activar'])->name('produccion.maquinas.activar');
    Route::post('/maquinas/lista-revision', [MaquinariaController::class, 'storeParametroRevision'])->name('produccion.maquinas.lista_revision.store');
    Route::post('/maquinas/lista-revision/{id}', [MaquinariaController::class, 'updateParametroRevision'])->name('produccion.maquinas.lista_revision.update');
    Route::post('/maquinas/lista-revision/{id}/inactivar', [MaquinariaController::class, 'inactivarParametroRevision'])->name('produccion.maquinas.lista_revision.inactivar');
    Route::post('/maquinas/lista-revision/{id}/activar', [MaquinariaController::class, 'activarParametroRevision'])->name('produccion.maquinas.lista_revision.activar');
    Route::get('/maquinas/{id}/mantenimientos', [MaquinariaController::class, 'mantenimientos'])->name('produccion.maquinas.mantenimientos');
    Route::post('/maquinas/{id}/mantenimientos', [MaquinariaController::class, 'storeMantenimiento'])->name('produccion.maquinas.mantenimientos.store');
    Route::get('/maquinas/{id}/mantenimientos/{mantenimientoId}', [MaquinariaController::class, 'detalleMantenimiento'])->name('produccion.maquinas.mantenimientos.detalle');
    Route::post('/maquinas/{id}/mantenimientos/{mantenimientoId}/actualizar', [MaquinariaController::class, 'updateMantenimiento'])->name('produccion.maquinas.mantenimientos.update');
    Route::get('/maquinas/{id}/mantenimiento-preventivo', [MaquinariaController::class, 'mantenimientoPreventivo'])->name('produccion.maquinas.mantenimiento_preventivo');
    Route::post('/maquinas/{id}/mantenimiento-preventivo/programacion', [MaquinariaController::class, 'storeProgramacionMantenimiento'])->name('produccion.maquinas.mantenimiento_preventivo.programacion');
    Route::post('/maquinas/{id}/mantenimiento-preventivo/ejecutar', [MaquinariaController::class, 'storeEjecucionPreventiva'])->name('produccion.maquinas.mantenimiento_preventivo.ejecutar');
    Route::post('/maquinas/{id}/mantenimiento-preventivo/programacion/{programacionId}/inactivar', [MaquinariaController::class, 'inactivarProgramacionMantenimiento'])->name('produccion.maquinas.mantenimiento_preventivo.inactivar');
    Route::get('/maquinas/{id}/inspecciones-semanales', [MaquinariaController::class, 'inspeccionesSemanales'])->name('produccion.maquinas.inspecciones');
    Route::post('/maquinas/{id}/inspecciones-semanales', [MaquinariaController::class, 'storeInspeccion'])->name('produccion.maquinas.inspecciones.store');
    Route::get('/maquinas/{id}/inspecciones-semanales/{inspeccionId}', [MaquinariaController::class, 'detalleInspeccion'])->name('produccion.maquinas.inspecciones.detalle');
    Route::post('/maquinas/{id}/inspecciones-semanales/{inspeccionId}/cerrar', [MaquinariaController::class, 'cerrarInspeccion'])->name('produccion.maquinas.inspecciones.cerrar');
    Route::get('/maquinas/{id}/incidencias', [MaquinariaController::class, 'incidencias'])->name('produccion.maquinas.incidencias');
    Route::post('/maquinas/{id}/incidencias', [MaquinariaController::class, 'storeIncidencia'])->name('produccion.maquinas.incidencias.store');
    Route::get('/maquinas/{id}/incidencias/{incidenciaId}', [MaquinariaController::class, 'detalleIncidencia'])->name('produccion.maquinas.incidencias.detalle');
    Route::post('/maquinas/{id}/incidencias/{incidenciaId}/actualizar', [MaquinariaController::class, 'updateIncidencia'])->name('produccion.maquinas.incidencias.update');
    Route::get('/maquinas/{id}/refacciones', [MaquinariaController::class, 'refacciones'])->name('produccion.maquinas.refacciones');

    Route::get('/produccion/empaque', [App\Http\Controllers\EmpaqueController::class, 'index'])->name('produccion.empaque');
    Route::get('/produccion/tipos-empaque', [App\Http\Controllers\EmpaqueController::class, 'catalogoTipos'])->name('produccion.tipos_empaque');
    Route::post('/produccion/tipos-empaque', [App\Http\Controllers\EmpaqueController::class, 'storeTipo'])->name('produccion.tipos_empaque.store');
    Route::post('/produccion/tipos-empaque/{id}', [App\Http\Controllers\EmpaqueController::class, 'actualizarTipo'])->name('produccion.tipos_empaque.actualizar');
    Route::get('/produccion/empaque/{id}', [App\Http\Controllers\EmpaqueController::class, 'show'])->name('produccion.empaque.detalle');
    Route::post('/produccion/empaque/{id}/avanzar', [App\Http\Controllers\EmpaqueController::class, 'avanzar'])->name('produccion.empaque.avanzar');
    Route::post('/produccion/empaque/{id}/ubicacion', [App\Http\Controllers\EmpaqueController::class, 'storeUbicacion'])->name('produccion.empaque.ubicacion.store');

    Route::get('/produccion/reproceso', [App\Http\Controllers\ReprocesoController::class, 'index'])->name('produccion.reproceso');
    Route::get('/produccion/reproceso/{id}', [App\Http\Controllers\ReprocesoController::class, 'show'])->name('produccion.reproceso.detalle');
    Route::post('/produccion/reproceso/{id}/maquina', [App\Http\Controllers\ReprocesoController::class, 'asignarMaquina'])->name('produccion.reproceso.maquina');
    Route::post('/produccion/reproceso/{id}/avanzar', [App\Http\Controllers\ReprocesoController::class, 'avanzar'])->name('produccion.reproceso.avanzar');

    Route::get('/produccion/reportes', [ProduccionReportesController::class, 'index'])->name('produccion.reportes');
    Route::get('/produccion/reportes/mermas', [ProduccionReportesController::class, 'mermas'])->name('produccion.reportes.mermas');
    Route::get('/produccion/reportes/mermas/exportar', [ProduccionReportesController::class, 'exportarMermas'])->name('produccion.reportes.mermas.exportar');
    Route::get('/produccion/reportes/produccion-diaria', [ProduccionReportesController::class, 'produccionDiaria'])->name('produccion.reportes.produccion_diaria');
    Route::get('/produccion/reportes/produccion-diaria/exportar', [ProduccionReportesController::class, 'exportarProduccionDiaria'])->name('produccion.reportes.produccion_diaria.exportar');
    Route::get('/produccion/reportes/empleado-maquina', [ProduccionReportesController::class, 'empleadoMaquina'])->name('produccion.reportes.empleado_maquina');
    Route::get('/produccion/reportes/empleado-maquina/exportar', [ProduccionReportesController::class, 'exportarEmpleadoMaquina'])->name('produccion.reportes.empleado_maquina.exportar');
    Route::get('/produccion/reportes/calidad', [ProduccionReportesController::class, 'calidad'])->name('produccion.reportes.calidad');
    Route::get('/produccion/reportes/calidad/exportar', [ProduccionReportesController::class, 'exportarCalidad'])->name('produccion.reportes.calidad.exportar');
    Route::get('/produccion/reportes/reproceso', [ProduccionReportesController::class, 'reprocesoResina'])->name('produccion.reportes.reproceso');
    Route::get('/produccion/reportes/reproceso/exportar', [ProduccionReportesController::class, 'exportarReprocesoResina'])->name('produccion.reportes.reproceso.exportar');
    Route::get('/produccion/reportes/kpis-mantenimiento', [ProduccionReportesController::class, 'kpisMantenimiento'])->name('produccion.reportes.kpis_mantenimiento');
    Route::get('/produccion/reportes/kpis-mantenimiento/exportar', [ProduccionReportesController::class, 'exportarKpisMantenimiento'])->name('produccion.reportes.kpis_mantenimiento.exportar');
    Route::get('/produccion/reportes/oee', [ProduccionReportesController::class, 'oee'])->name('produccion.reportes.oee');
    Route::get('/produccion/reportes/oee/exportar', [ProduccionReportesController::class, 'exportarOee'])->name('produccion.reportes.oee.exportar');

    Route::get('/produccion/costo-pead', [RegistroProduccionController::class, 'costoPeadIndex'])->name('produccion.costo_pead');
    Route::post('/produccion/costo-pead', [RegistroProduccionController::class, 'storeCostoPead'])->name('produccion.costo_pead.store');
    Route::post('/produccion/costo-pead/{id}', [RegistroProduccionController::class, 'updateCostoPead'])->name('produccion.costo_pead.update');

    Route::get('/produccion', [OrdenesProduccionController::class, 'index'])->name('produccion.ordenes');
    Route::post('/produccion', [OrdenesProduccionController::class, 'store'])->name('produccion.ordenes.store');
    Route::post('/produccion/importar-pedidos', [OrdenesProduccionController::class, 'importarPedidosPendientes'])->name('produccion.ordenes.importar_pedidos');
    Route::get('/produccion/panel/{id}', [OrdenesProduccionController::class, 'panelDetalle'])->name('produccion.ordenes.panel');
    Route::get('/produccion/seguimiento', [OrdenesProduccionController::class, 'seguimientoJson'])->name('produccion.ordenes.seguimiento');
    Route::get('/produccion/especificaciones-tubo', [TuboEspecificacionesController::class, 'index'])->name('produccion.especificaciones.tubo');
    Route::post('/produccion/especificaciones-tubo/importar', [TuboEspecificacionesController::class, 'importar'])->name('produccion.especificaciones.tubo.importar');
    Route::post('/produccion/especificaciones-tubo/vincular-productos', [TuboEspecificacionesController::class, 'vincularProductos'])->name('produccion.especificaciones.tubo.vincular');
    Route::post('/produccion/especificaciones-tubo', [TuboEspecificacionesController::class, 'store'])->name('produccion.especificaciones.tubo.store');
    Route::post('/produccion/especificaciones-tubo/{id}', [TuboEspecificacionesController::class, 'update'])->name('produccion.especificaciones.tubo.update');
    Route::get('/produccion/especificaciones-flange', [FlangeEspecificacionesController::class, 'index'])->name('produccion.especificaciones.flange');
    Route::post('/produccion/especificaciones-flange', [FlangeEspecificacionesController::class, 'store'])->name('produccion.especificaciones.flange.store');
    Route::post('/produccion/especificaciones-flange/{id}', [FlangeEspecificacionesController::class, 'update'])->name('produccion.especificaciones.flange.update');
    Route::get('/produccion/especificaciones-conexiones', [ConexionEspecificacionesController::class, 'index'])->name('produccion.especificaciones.conexiones');
    Route::post('/produccion/especificaciones-conexiones', [ConexionEspecificacionesController::class, 'store'])->name('produccion.especificaciones.conexiones.store');
    Route::post('/produccion/especificaciones-conexiones/importar', [ConexionEspecificacionesController::class, 'importar'])->name('produccion.especificaciones.conexiones.importar');
    Route::post('/produccion/especificaciones-conexiones/{id}', [ConexionEspecificacionesController::class, 'update'])->name('produccion.especificaciones.conexiones.update');
    Route::get('/produccion/recetas', [RecetasController::class, 'index'])->name('produccion.recetas');
    Route::post('/produccion/recetas', [RecetasController::class, 'store'])->name('produccion.recetas.store');
    Route::get('/produccion/recetas/{id}', [RecetasController::class, 'detalle'])->name('produccion.recetas.detalle');
    Route::post('/produccion/recetas/{id}', [RecetasController::class, 'actualizar'])->name('produccion.recetas.actualizar');
    Route::post('/produccion/recetas/{id}/detalle', [RecetasController::class, 'storeDetalle'])->name('produccion.recetas.detalle.store');
    Route::post('/produccion/recetas/{id}/detalle/{detalleId}', [RecetasController::class, 'actualizarDetalle'])->name('produccion.recetas.detalle.actualizar');
    Route::post('/produccion/recetas/{id}/detalle/{detalleId}/eliminar', [RecetasController::class, 'eliminarDetalle'])->name('produccion.recetas.detalle.eliminar');
    Route::get('/produccion/productos/{productoId}/especificaciones-tubo', [OrdenesProduccionController::class, 'especificacionesTuboProducto'])->name('produccion.productos.especificaciones_tubo');
    Route::get('/produccion/productos/{productoId}/receta', [RecetasController::class, 'recetaProductoJson'])->name('produccion.productos.receta');
    Route::get('/produccion/{id}/materiales', [OrdenesProduccionController::class, 'materialesOrden'])->name('produccion.ordenes.materiales');
    Route::post('/produccion/{id}/ingresar-materiales', [OrdenesProduccionController::class, 'ingresarMateriales'])->name('produccion.ordenes.ingresar_materiales');
    Route::get('/produccion/{id}/orden-materiales', [OrdenesProduccionController::class, 'verOrdenMateriales'])->name('produccion.ordenes.orden_materiales');
    Route::post('/produccion/{id}/archivo-op', [OrdenesProduccionController::class, 'subirArchivoOp'])->name('produccion.ordenes.archivo_op');
    Route::get('/produccion/{id}/archivo-op', [OrdenesProduccionController::class, 'verArchivoOp'])->name('produccion.ordenes.archivo_op.ver');
    Route::get('/produccion/{id}', [OrdenesProduccionController::class, 'detalle'])->name('produccion.ordenes.detalle');
    Route::post('/produccion/{id}/cabecera', [OrdenesProduccionController::class, 'actualizarCabecera'])->name('produccion.ordenes.cabecera');
    Route::post('/produccion/{id}/registro-oee', [RegistroProduccionController::class, 'storeRegistro'])->name('produccion.ordenes.registro_oee');
    Route::post('/produccion/{id}/paros', [RegistroProduccionController::class, 'storeParo'])->name('produccion.ordenes.paros.store');
    Route::post('/produccion/{id}/paros/{paroId}/eliminar', [RegistroProduccionController::class, 'destroyParo'])->name('produccion.ordenes.paros.destroy');
    Route::post('/produccion/{id}/detalle', [OrdenesProduccionController::class, 'storeDetalle'])->name('produccion.ordenes.detalle.store');
    Route::post('/produccion/{id}/detalle/{detalleId}', [OrdenesProduccionController::class, 'actualizarDetalle'])->name('produccion.ordenes.detalle.actualizar');
    Route::post('/produccion/{id}/detalle/{detalleId}/salida', [OrdenesProduccionController::class, 'storeSalida'])->name('produccion.ordenes.detalle.salida');
    Route::post('/produccion/{id}/salidas/{salidaId}/inspeccion', [OrdenesProduccionController::class, 'inspeccionarSalida'])->name('produccion.ordenes.salida.inspeccion');
    Route::post('/produccion/{id}/empaque', [App\Http\Controllers\EmpaqueController::class, 'avanzar'])->name('produccion.ordenes.empaque');
    Route::post('/produccion/{id}/ubicacion-almacen', [App\Http\Controllers\EmpaqueController::class, 'storeUbicacion'])->name('produccion.ordenes.ubicacion.store');
    Route::post('/produccion/{id}/estatus', [OrdenesProduccionController::class, 'cambiarEstatus'])->name('produccion.ordenes.estatus');
    Route::post('/produccion/{id}/cancelar', [OrdenesProduccionController::class, 'cancelar'])->name('produccion.ordenes.cancelar');
    Route::post('/produccion/{id}/maquinas', [OrdenesProduccionController::class, 'agregarEtapaMaquina'])->name('produccion.ordenes.maquina.store');
    Route::post('/produccion/{id}/maquinas/{etapaId}/estatus', [OrdenesProduccionController::class, 'cambiarEstatusEtapa'])->name('produccion.ordenes.maquina.estatus');

    Route::redirect('/ordenes-produccion', '/produccion');
    Route::permanentRedirect('/ordenes-produccion/{id}', '/produccion/{id}');

    Route::prefix('/Gestion_alumnos/api')->group(function () {
        Route::get('/escuelas', [GestionAlumnosEscuelaController::class, 'index'])->name('gestion_alumnos.api.escuelas.index');
        Route::get('/escuelas/{id}', [GestionAlumnosEscuelaController::class, 'show'])->name('gestion_alumnos.api.escuelas.show');
        Route::post('/escuelas', [GestionAlumnosEscuelaController::class, 'store'])->name('gestion_alumnos.api.escuelas.store');
        Route::put('/escuelas/{id}', [GestionAlumnosEscuelaController::class, 'update'])->name('gestion_alumnos.api.escuelas.update');
        Route::patch('/escuelas/{id}/inactivar', [GestionAlumnosEscuelaController::class, 'inactivar'])->name('gestion_alumnos.api.escuelas.inactivar');

        Route::get('/especialidades', [GestionAlumnosEspecialidadController::class, 'index'])->name('gestion_alumnos.api.especialidades.index');
        Route::get('/especialidades/{id}', [GestionAlumnosEspecialidadController::class, 'show'])->name('gestion_alumnos.api.especialidades.show');
        Route::post('/especialidades', [GestionAlumnosEspecialidadController::class, 'store'])->name('gestion_alumnos.api.especialidades.store');
        Route::put('/especialidades/{id}', [GestionAlumnosEspecialidadController::class, 'update'])->name('gestion_alumnos.api.especialidades.update');
        Route::patch('/especialidades/{id}/inactivar', [GestionAlumnosEspecialidadController::class, 'inactivar'])->name('gestion_alumnos.api.especialidades.inactivar');

        Route::get('/documentos', [GestionAlumnosDocumentoController::class, 'index'])->name('gestion_alumnos.api.documentos.index');
        Route::get('/documentos/{id}', [GestionAlumnosDocumentoController::class, 'show'])->name('gestion_alumnos.api.documentos.show');
        Route::post('/documentos', [GestionAlumnosDocumentoController::class, 'store'])->name('gestion_alumnos.api.documentos.store');
        Route::put('/documentos/{id}', [GestionAlumnosDocumentoController::class, 'update'])->name('gestion_alumnos.api.documentos.update');
        Route::patch('/documentos/{id}/inactivar', [GestionAlumnosDocumentoController::class, 'inactivar'])->name('gestion_alumnos.api.documentos.inactivar');

        Route::get('/alumnos', [GestionAlumnosAlumnoController::class, 'index'])->name('gestion_alumnos.api.alumnos.index');
        Route::post('/alumnos', [GestionAlumnosAlumnoController::class, 'store'])->name('gestion_alumnos.api.alumnos.store');
        Route::post('/alumnos/importar-masivo', [GestionAlumnosAlumnoController::class, 'importarMasivo'])->name('gestion_alumnos.api.alumnos.importar_masivo');
        Route::get('/alumnos/requisitos-documentos', [GestionAlumnosAlumnoController::class, 'requisitosDocumentos'])->name('gestion_alumnos.api.alumnos.requisitos_documentos');
        Route::put('/alumnos/requisitos-documentos', [GestionAlumnosAlumnoController::class, 'actualizarRequisitosDocumentos'])->name('gestion_alumnos.api.alumnos.requisitos_documentos.update');
        Route::get('/alumnos/{id}/tutores', [GestionAlumnosTutorController::class, 'indexPorAlumno'])->name('gestion_alumnos.api.alumnos.tutores');
        Route::post('/alumnos/{id}/tutores', [GestionAlumnosTutorController::class, 'store'])->name('gestion_alumnos.api.alumnos.tutores.store');
        Route::get('/alumnos/{alumno}/cursos', [GestionAlumnosAlumnoCursoController::class, 'index'])->name('gestion_alumnos.api.alumnos.cursos.index');
        Route::post('/alumnos/{alumno}/cursos', [GestionAlumnosAlumnoCursoController::class, 'store'])->name('gestion_alumnos.api.alumnos.cursos.store');
        Route::patch('/alumnos/{alumno}/cursos/{curso}', [GestionAlumnosAlumnoCursoController::class, 'updateCalificacion'])->name('gestion_alumnos.api.alumnos.cursos.update_calificacion');
        Route::delete('/alumnos/{alumno}/cursos/{curso}', [GestionAlumnosAlumnoCursoController::class, 'destroy'])->name('gestion_alumnos.api.alumnos.cursos.destroy');
        Route::get('/alumnos/{id}', [GestionAlumnosAlumnoController::class, 'show'])->name('gestion_alumnos.api.alumnos.show');
        Route::put('/alumnos/{id}', [GestionAlumnosAlumnoController::class, 'update'])->name('gestion_alumnos.api.alumnos.update');
        Route::patch('/alumnos/{id}/inactivar', [GestionAlumnosAlumnoController::class, 'inactivar'])->name('gestion_alumnos.api.alumnos.inactivar');

        Route::get('/empresas', [GestionAlumnosEmpresaController::class, 'index'])->name('gestion_alumnos.api.empresas.index');
        Route::post('/empresas', [GestionAlumnosEmpresaController::class, 'store'])->name('gestion_alumnos.api.empresas.store');
        Route::post('/empresas/importar-masivo', [GestionAlumnosEmpresaController::class, 'importarMasivo'])->name('gestion_alumnos.api.empresas.importar_masivo');
        Route::get('/empresas/fed-aceptadas', [GestionAlumnosEmpresaController::class, 'empresasFedAceptadas'])->name('gestion_alumnos.api.empresas.fed_aceptadas');
        Route::get('/empresas/{id}', [GestionAlumnosEmpresaController::class, 'show'])->whereNumber('id')->name('gestion_alumnos.api.empresas.show');
        Route::put('/empresas/{id}', [GestionAlumnosEmpresaController::class, 'update'])->whereNumber('id')->name('gestion_alumnos.api.empresas.update');
        Route::delete('/empresas/{id}', [GestionAlumnosEmpresaController::class, 'destroy'])->whereNumber('id')->name('gestion_alumnos.api.empresas.destroy');
        Route::get('/empresas/{empresa}/visitas-prospeccion', [GestionAlumnosEmpresaVisitaController::class, 'index'])->whereNumber('empresa')->name('gestion_alumnos.api.empresas.visitas.index');
        Route::post('/empresas/{empresa}/visitas-prospeccion', [GestionAlumnosEmpresaVisitaController::class, 'store'])->whereNumber('empresa')->name('gestion_alumnos.api.empresas.visitas.store');

        Route::get('/tipos-socio', [GestionAlumnosSocioController::class, 'tiposSocio'])->name('gestion_alumnos.api.tipos_socio.index');
        Route::get('/grupos-socio', [GestionAlumnosSocioController::class, 'gruposSocio'])->name('gestion_alumnos.api.grupos_socio.index');
        Route::get('/descuentos', [GestionAlumnosSocioController::class, 'descuentos'])->name('gestion_alumnos.api.descuentos.index');
        Route::get('/socios/eventos', [GestionAlumnosEventoSocioController::class, 'index'])->name('gestion_alumnos.api.socios.eventos.index');
        Route::post('/socios/eventos', [GestionAlumnosEventoSocioController::class, 'store'])->name('gestion_alumnos.api.socios.eventos.store');
        Route::get('/socios/eventos/catalogo-encargados', [GestionAlumnosEventoSocioController::class, 'catalogoEncargados'])->name('gestion_alumnos.api.socios.eventos.catalogo_encargados');
        Route::get('/socios/eventos/{id}', [GestionAlumnosEventoSocioController::class, 'show'])->whereNumber('id')->name('gestion_alumnos.api.socios.eventos.show');
        Route::get('/socios/eventos/{id}/detalles', [GestionAlumnosEventoSocioDetalleController::class, 'index'])->whereNumber('id')->name('gestion_alumnos.api.socios.eventos.detalles.index');
        Route::post('/socios/eventos/{id}/detalles', [GestionAlumnosEventoSocioDetalleController::class, 'store'])->whereNumber('id')->name('gestion_alumnos.api.socios.eventos.detalles.store');
        Route::get('/socios', [GestionAlumnosSocioController::class, 'index'])->name('gestion_alumnos.api.socios.index');
        Route::post('/socios', [GestionAlumnosSocioController::class, 'store'])->name('gestion_alumnos.api.socios.store');
        Route::post('/socios/importar-masivo', [GestionAlumnosSocioController::class, 'importarMasivo'])->name('gestion_alumnos.api.socios.importar_masivo');
        Route::get('/socios/{id}', [GestionAlumnosSocioController::class, 'show'])->whereNumber('id')->name('gestion_alumnos.api.socios.show');
        Route::get('/socios/{id}/siguiente-dependiente', [GestionAlumnosSocioController::class, 'siguienteDependiente'])->whereNumber('id')->name('gestion_alumnos.api.socios.siguiente_dependiente');
        Route::put('/socios/{id}', [GestionAlumnosSocioController::class, 'update'])->whereNumber('id')->name('gestion_alumnos.api.socios.update');
        Route::get('/socios/{id}/monto-sugerido', [GestionAlumnosSocioController::class, 'montoSugerido'])->whereNumber('id')->name('gestion_alumnos.api.socios.monto_sugerido');
        Route::get('/socios/{id}/planes-pago', [GestionAlumnosSocioController::class, 'planesPagoSocio'])->whereNumber('id')->name('gestion_alumnos.api.socios.planes_pago');
        Route::post('/socios/{id}/planes-pago', [GestionAlumnosSocioController::class, 'crearPlanPagos'])->whereNumber('id')->name('gestion_alumnos.api.socios.planes_pago.store');
        Route::patch('/socios/{id}/planes-pago/detalle/{detalleId}/pagar', [GestionAlumnosSocioController::class, 'pagarDetalle'])->whereNumber('id')->whereNumber('detalleId')->name('gestion_alumnos.api.socios.planes_pago.detalle.pagar');
        Route::patch('/socios/{id}/planes-pago/detalle/{detalleId}/cancelar', [GestionAlumnosSocioController::class, 'cancelarDetalle'])->whereNumber('id')->whereNumber('detalleId')->name('gestion_alumnos.api.socios.planes_pago.detalle.cancelar');
        Route::get('/checkout-socios/buscar', [GestionAlumnosCheckoutSocioController::class, 'buscar'])->name('gestion_alumnos.api.checkout_socios.buscar');
        Route::post('/checkout-socios/entrada', [GestionAlumnosCheckoutSocioController::class, 'registrarEntrada'])->name('gestion_alumnos.api.checkout_socios.entrada');
        Route::get('/checkout-socios/historial-hoy', [GestionAlumnosCheckoutSocioController::class, 'historialHoy'])->name('gestion_alumnos.api.checkout_socios.historial_hoy');
        Route::get('/socios/reportes-diarios/resumen', [GestionAlumnosSocioReporteDiarioController::class, 'resumen'])->name('gestion_alumnos.api.socios.reportes_diarios.resumen');
        Route::post('/socios/reportes-diarios/corte', [GestionAlumnosSocioReporteDiarioController::class, 'guardarCorte'])->name('gestion_alumnos.api.socios.reportes_diarios.corte');
        Route::get('/socios/reportes-diarios/historial', [GestionAlumnosSocioReporteDiarioController::class, 'historial'])->name('gestion_alumnos.api.socios.reportes_diarios.historial');
        Route::get('/socios/reportes-diarios/trafico', [GestionAlumnosSocioReporteDiarioController::class, 'trafico'])->name('gestion_alumnos.api.socios.reportes_diarios.trafico');
        Route::get('/socios/reportes-diarios/pagos-grafica', [GestionAlumnosSocioReporteDiarioController::class, 'pagosGrafica'])->name('gestion_alumnos.api.socios.reportes_diarios.pagos_grafica');

        Route::get('/cursos', [GestionAlumnosCursoController::class, 'index'])->name('gestion_alumnos.api.cursos.index');
        Route::post('/cursos', [GestionAlumnosCursoController::class, 'store'])->name('gestion_alumnos.api.cursos.store');
        Route::get('/cursos/{id}', [GestionAlumnosCursoController::class, 'show'])->name('gestion_alumnos.api.cursos.show');
        Route::put('/cursos/{id}', [GestionAlumnosCursoController::class, 'update'])->name('gestion_alumnos.api.cursos.update');
        Route::delete('/cursos/{id}', [GestionAlumnosCursoController::class, 'destroy'])->name('gestion_alumnos.api.cursos.destroy');

        Route::get('/docentes', [GestionAlumnosDocenteController::class, 'index'])->name('gestion_alumnos.api.docentes.index');
        Route::post('/docentes', [GestionAlumnosDocenteController::class, 'store'])->name('gestion_alumnos.api.docentes.store');
        Route::get('/docentes/{id}', [GestionAlumnosDocenteController::class, 'show'])->name('gestion_alumnos.api.docentes.show');
        Route::put('/docentes/{id}', [GestionAlumnosDocenteController::class, 'update'])->name('gestion_alumnos.api.docentes.update');
        Route::delete('/docentes/{id}', [GestionAlumnosDocenteController::class, 'destroy'])->name('gestion_alumnos.api.docentes.destroy');

        Route::get('/personas-documentos', [GestionAlumnosPersonaDocumentoController::class, 'index'])->name('gestion_alumnos.api.personas_documentos.index');
        Route::post('/personas-documentos', [GestionAlumnosPersonaDocumentoController::class, 'store'])->name('gestion_alumnos.api.personas_documentos.store');
        Route::post('/personas-documentos/subir-convenio-firmado', [GestionAlumnosPersonaDocumentoController::class, 'subirConvenioFirmado'])->name('gestion_alumnos.api.personas_documentos.subir_convenio_firmado');
        Route::get('/personas-documentos/{id}/archivo', [GestionAlumnosPersonaDocumentoController::class, 'visualizar'])->name('gestion_alumnos.api.personas_documentos.archivo');
        Route::get('/personas-documentos/{id}/descargar', [GestionAlumnosPersonaDocumentoController::class, 'descargar'])->name('gestion_alumnos.api.personas_documentos.descargar');
        Route::get('/personas-documentos/{id}', [GestionAlumnosPersonaDocumentoController::class, 'show'])->name('gestion_alumnos.api.personas_documentos.show');
        Route::patch('/personas-documentos/{id}', [GestionAlumnosPersonaDocumentoController::class, 'update'])->name('gestion_alumnos.api.personas_documentos.update');
        Route::delete('/personas-documentos/{id}', [GestionAlumnosPersonaDocumentoController::class, 'destroy'])->name('gestion_alumnos.api.personas_documentos.destroy');
        Route::get('/tests-personalidad-aula-mixta/alumno/{alumnoId}', [GestionAlumnosTestPersonalidadAulaMixtaController::class, 'showByAlumno'])->name('gestion_alumnos.api.tests_personalidad_aula_mixta.show_by_alumno');
        Route::post('/tests-personalidad-aula-mixta', [GestionAlumnosTestPersonalidadAulaMixtaController::class, 'store'])->name('gestion_alumnos.api.tests_personalidad_aula_mixta.store');

        Route::get('/convenios-asignaciones', [GestionAlumnosConvenioAsignacionController::class, 'index'])->name('gestion_alumnos.api.convenios_asignaciones.index');
        Route::post('/convenios-asignaciones', [GestionAlumnosConvenioAsignacionController::class, 'store'])->name('gestion_alumnos.api.convenios_asignaciones.store');
        Route::patch('/convenios-asignaciones/{id}/terminar', [GestionAlumnosConvenioAsignacionController::class, 'terminar'])->whereNumber('id')->name('gestion_alumnos.api.convenios_asignaciones.terminar');

        Route::get('/asistencias', [GestionAlumnosAsistenciaController::class, 'index'])->name('gestion_alumnos.api.asistencias.index');
        Route::post('/asistencias', [GestionAlumnosAsistenciaController::class, 'guardarMasivo'])->name('gestion_alumnos.api.asistencias.guardar');
        Route::post('/asistencias/guardar-pagos', [GestionAlumnosAsistenciaController::class, 'guardarPagos'])->name('gestion_alumnos.api.asistencias.guardar_pagos');
    });

    Route::get('/Gestion_alumnos/empresas', [GestionAlumnosViewController::class, 'empresas'])->name('gestion_alumnos.empresas');
    Route::get('/Gestion_alumnos/escuelas', [GestionAlumnosViewController::class, 'escuelas'])->name('gestion_alumnos.escuelas');
    Route::get('/Especialidades', [GestionAlumnosViewController::class, 'especialidades'])->name('gestion_alumnos.especialidades');
    Route::get('/DocumentosSolicitados', [GestionAlumnosViewController::class, 'documentosSolicitados'])->name('gestion_alumnos.documentos_solicitados');
    Route::get('/Gestion_alumnos/socios', [GestionAlumnosViewController::class, 'socios'])->name('gestion_alumnos.socios');
    Route::get('/Gestion_alumnos/socios/checkout', [GestionAlumnosViewController::class, 'sociosCheckout'])->name('gestion_alumnos.socios.checkout');
    Route::get('/Gestion_alumnos/socios/reportes-diarios', [GestionAlumnosViewController::class, 'sociosReportesDiarios'])->name('gestion_alumnos.socios.reportes_diarios');
    Route::get('/Gestion_alumnos/socios/eventos', [GestionAlumnosViewController::class, 'sociosEventos'])->name('gestion_alumnos.socios.eventos');
    Route::get('/Gestion_alumnos/socios/eventos/{id}/detalle', [GestionAlumnosViewController::class, 'sociosEventoDetalle'])->whereNumber('id')->name('gestion_alumnos.socios.eventos.detalle');
    Route::get('/Gestion_alumnos/calendario-visitas', [GestionAlumnosViewController::class, 'calendarioVisitas'])->name('gestion_alumnos.calendario_visitas');
    Route::get('/Gestion_alumnos/cursos', [GestionAlumnosViewController::class, 'cursos'])->name('gestion_alumnos.cursos');
    Route::get('/Docentes', [GestionAlumnosViewController::class, 'docentes'])->name('gestion_alumnos.docentes');
    Route::get('/Gestion_alumnos/convenios', [GestionAlumnosViewController::class, 'convenios'])->name('gestion_alumnos.convenios');
    Route::get('/Gestion_alumnos/control-asistencias', [GestionAlumnosViewController::class, 'controlAsistencias'])->name('gestion_alumnos.control_asistencias');
    Route::get('/Gestion_alumnos/calculo-pagos', [GestionAlumnosViewController::class, 'calculoPagos'])->name('gestion_alumnos.calculo_pagos');
    Route::get('/Gestion_alumnos/facturacion-empresa', [GestionAlumnosViewController::class, 'facturacionEmpresa'])->name('gestion_alumnos.facturacion_empresa');
    Route::get('/Gestion_alumnos/convenios/asignar', [GestionAlumnosViewController::class, 'conveniosAsignar'])->name('gestion_alumnos.convenios_asignar');
    Route::get('/Gestion_alumnos/documentos_alumno', [GestionAlumnosViewController::class, 'documentosAlumno'])->name('gestion_alumnos.documentos_alumno');
    Route::get('/Gestion_alumnos/documentos_alumno/registro_aspirante_dual', [GestionAlumnosViewController::class, 'registroAspiranteDual'])->name('gestion_alumnos.documentos_alumno.registro_aspirante_dual');
    Route::get('/Gestion_alumnos/documentos_alumno/test_personalidad_aula_mixta', [GestionAlumnosViewController::class, 'testPersonalidadAulaMixta'])->name('gestion_alumnos.documentos_alumno.test_personalidad_aula_mixta');
    Route::get('/Gestion_alumnos/documentos_alumno/test_personalidad_aula_mixta_simple', [GestionAlumnosViewController::class, 'testPersonalidadAulaMixtaSimple'])->name('gestion_alumnos.documentos_alumno.test_personalidad_aula_mixta_simple');
    Route::get('/convenios', [GestionAlumnosViewController::class, 'convenios'])->name('convenios');
    Route::get('/convenios/asignar', [GestionAlumnosViewController::class, 'conveniosAsignar'])->name('convenios_asignar');
    Route::get('/documentos_alumno', [GestionAlumnosViewController::class, 'documentosAlumno'])->name('documentos_alumno');
    Route::get('/documentos_alumno/registro_aspirante_dual', [GestionAlumnosViewController::class, 'registroAspiranteDual'])->name('documentos_alumno.registro_aspirante_dual');
    Route::get('/documentos_alumno/test_personalidad_aula_mixta', [GestionAlumnosViewController::class, 'testPersonalidadAulaMixta'])->name('documentos_alumno.test_personalidad_aula_mixta');
    Route::get('/documentos_alumno/test_personalidad_aula_mixta_simple', [GestionAlumnosViewController::class, 'testPersonalidadAulaMixtaSimple'])->name('documentos_alumno.test_personalidad_aula_mixta_simple');


    Route::prefix('/Clientes')->group(function () {
        Route::get('/', 'App\Http\Controllers\ClientesController@index')->name('clientes.index');
        Route::post('/Insertar', 'App\Http\Controllers\ClientesController@insert')->name('clientes.insert');
        Route::post('/Editar/{id}', 'App\Http\Controllers\ClientesController@edit')->name('clientes.edit');
        Route::get('/Eliminar/{id}', 'App\Http\Controllers\ClientesController@delete')->name('clientes.delete');
        Route::get('/PersonasAtencion/{id_cliente}', 'App\Http\Controllers\ClientesController@getPersonasAtencion')->name('clientes.personas_atencion');
        Route::post('/InsertarPersonaAtencion', 'App\Http\Controllers\ClientesController@insertPersonaAtencion')->name('clientes.insert_persona_atencion');
        Route::post('/ActualizarPersonaAtencion/{id}', 'App\Http\Controllers\ClientesController@actualizarPersonaAtencion')->name('clientes.actualizar_persona_atencion');
        Route::get('/EliminarPersonaAtencion/{id}', 'App\Http\Controllers\ClientesController@deletePersonaAtencion')->name('clientes.delete_persona_atencion');
    });

    Route::get('/buscar-producto-servicio', [App\Http\Controllers\TimbrarNominaController::class, 'buscarProductoServicio'])->name('buscar.producto.servicio');
    Route::get('/buscar-unidad-medida', [App\Http\Controllers\TimbrarNominaController::class, 'buscarUnidadMedida'])->name('buscar.unidad.medida');
    Route::get('/buscar-uso-cfdi', [App\Http\Controllers\TimbrarNominaController::class, 'buscarUsoCFDI'])->name('buscar.uso.cfdi');
    Route::get('/buscar-forma-pago', [App\Http\Controllers\TimbrarNominaController::class, 'buscarFormaPago'])->name('buscar.forma.pago');
    Route::get('/buscar-regimen-fiscal', [App\Http\Controllers\TimbrarNominaController::class, 'buscarRegimenFiscal'])->name('buscar.regimen.fiscal');
    Route::get('/buscar-codigo-postal', [App\Http\Controllers\TimbrarNominaController::class, 'buscarCodigoPostal'])->name('buscar.codigo.postal');
    Route::get('/obtener-metodos-pago', [App\Http\Controllers\TimbrarNominaController::class, 'obtenerMetodosPago'])->name('obtener.metodos.pago');

    Route::post('/procesar-factura', [App\Http\Controllers\TimbrarNominaController::class, 'procesarFactura'])->name('procesar.factura');
    Route::post('/procesar-factura-proyecto', [App\Http\Controllers\TimbrarNominaController::class, 'procesarFacturaProyecto'])->name('procesar.factura.proyecto');
    Route::post('/procesar-factura-beca', [App\Http\Controllers\TimbrarNominaController::class, 'ProcesarFacturaBeca'])->name('procesar.factura.beca');
    Route::get('/verfacturabecas/{uuid}', [App\Http\Controllers\TimbrarNominaController::class, 'verfacturaBecas'])->name('verfactura.becas');
    Route::get('/api/facturas-beca', function (\Illuminate\Http\Request $request) {
        $query = \App\Models\FacturaBeca::orderBy('created_at', 'desc');
        if ($request->has('empresa_id')) {
            $query->where('empresa_id', $request->input('empresa_id'));
        }
        if ($request->has('mes')) {
            $query->where('mes', $request->input('mes'));
        }
        if ($request->has('anio')) {
            $query->where('anio', $request->input('anio'));
        }
        return response()->json(['data' => $query->limit(20)->get()]);
    })->name('api.facturas.beca');
    Route::post('/facturama', [App\Http\Controllers\TimbrarNominaController::class, 'facturama'])->name('facturama');

    // Ruta del módulo de Finanzas---------------------------------------------------------------------------------------------
    Route::prefix('/Finanzas')->group(function () {
            Route::get('/', [FinanzasController::class, 'finanzas'])->name('finanzas');
            Route::get('/facturas', [FinanzasController::class, 'facturas'])->name('finanzas.facturas');
            Route::post('/guardar-factura', [FinanzasController::class, 'guardarFactura'])->name('finanzas.guardar-factura');
            Route::post('/actualizar-estado-factura', [FinanzasController::class, 'actualizarEstadoFactura'])->name('finanzas.actualizar-estado-factura');
            Route::post('/cancelar-factura-facturama/{id}', [FinanzasController::class, 'cancelarFacturaFacturamaHttp'])->name('finanzas.cancelar-factura-facturama');
            Route::post('/guardar-ingreso', [FinanzasController::class, 'guardarIngreso'])->name('finanzas.guardar-ingreso');
            Route::post('/guardar-egreso', [FinanzasController::class, 'guardarEgreso'])->name('finanzas.guardar-egreso');
            Route::post('/guardar-deuda-cobrar', [FinanzasController::class, 'guardarDeudaCobrar'])->name('finanzas.guardar-deuda-cobrar');
            Route::post('/guardar-deuda-pagar', [FinanzasController::class, 'guardarDeudaPagar'])->name('finanzas.guardar-deuda-pagar');
            Route::get('/obtener-resumen', [FinanzasController::class, 'obtenerResumen'])->name('finanzas.obtener-resumen');
            Route::get('/obtener-ingresos', [FinanzasController::class, 'obtenerIngresos'])->name('finanzas.obtener-ingresos');
            Route::get('/obtener-egresos', [FinanzasController::class, 'obtenerEgresos'])->name('finanzas.obtener-egresos');
            Route::get('/obtener-deudas-cobrar', [FinanzasController::class, 'obtenerDeudasCobrar'])->name('finanzas.obtener-deudas-cobrar');
            Route::get('/obtener-deudas-pagar', [FinanzasController::class, 'obtenerDeudasPagar'])->name('finanzas.obtener-deudas-pagar');
            Route::get('/obtener-deuda-pagar/{id}', [FinanzasController::class, 'obtenerDeudaPagar'])->name('finanzas.obtener-deuda-pagar');
            Route::put('/actualizar-deuda-pagar/{id}', [FinanzasController::class, 'actualizarDeudaPagar'])->name('finanzas.actualizar-deuda-pagar');
            Route::post('/deudas-pagar/{id}/abonos', [FinanzasController::class, 'registrarAbonoDeudaPagar'])->name('finanzas.deuda-pagar.abonar');
            Route::get('/deudas-pagar/{id}/abonos', [FinanzasController::class, 'obtenerAbonosDeudaPagar'])->name('finanzas.deuda-pagar.abonos');
            Route::delete('/eliminar-deuda-pagar/{id}', [FinanzasController::class, 'eliminarDeudaPagar'])->name('finanzas.eliminar-deuda-pagar');
            Route::delete('/eliminar-deuda-cobrar/{id}', [FinanzasController::class, 'eliminarDeudaCobrar'])->name('finanzas.eliminar-deuda-cobrar');
            Route::delete('/eliminar-egreso/{id}', [FinanzasController::class, 'eliminarEgreso'])->name('finanzas.eliminar-egreso');
            Route::get('/obtener-egreso/{id}', [FinanzasController::class, 'obtenerEgreso'])->name('finanzas.obtener-egreso');
            Route::put('/actualizar-egreso/{id}', [FinanzasController::class, 'actualizarEgreso'])->name('finanzas.actualizar-egreso');
            Route::get('/obtener-deuda-cobrar/{id}', [FinanzasController::class, 'obtenerDeudaCobrar'])->name('finanzas.obtener-deuda-cobrar');
            Route::put('/actualizar-deuda-cobrar/{id}', [FinanzasController::class, 'actualizarDeudaCobrar'])->name('finanzas.actualizar-deuda-cobrar');
    });


    Route::prefix('/Seguimiento')->group(function () {
        Route::get('/', 'App\Http\Controllers\ServiciosController@seguimiento_index')->name('seguimiento.index');
        Route::post('/actualizar-comision', 'App\Http\Controllers\ServiciosController@actualizarPorcentajeComision')->name('seguimiento.actualizar-comision');
    });

    Route::get('/AdminCentros', [CentrosCostosController::class, 'admin'])->name('centros.admin');
    Route::get('/AdminCentros/grupos', [CentrosCostosController::class, 'grupos'])->name('centros.grupos');
    Route::get('/CentrosCostos/api/grupos', [CentrosCostosController::class, 'listGrupos'])->name('centros.api.grupos');
    Route::post('/CentrosCostos/api/grupos', [CentrosCostosController::class, 'storeGrupo'])->name('centros.api.grupos.store');
    Route::put('/CentrosCostos/api/grupos/{id}', [CentrosCostosController::class, 'updateGrupo'])->name('centros.api.grupos.update');
    Route::delete('/CentrosCostos/api/grupos/{id}', [CentrosCostosController::class, 'destroyGrupo'])->name('centros.api.grupos.destroy');
    Route::get('/AdminCentros/{ciclo}/asignar/{empresa?}', [CentrosCostosController::class, 'asignar'])
        ->where('empresa', 'austin|imsa|pitic|sydney')
        ->name('centros.asignar');
    Route::get('/AdminCentros/{ciclo}/asignaciones', [CentrosCostosController::class, 'listAsignaciones'])->name('centros.asignaciones.index');
    Route::post('/AdminCentros/{ciclo}/asignaciones', [CentrosCostosController::class, 'storeAsignacion'])->name('centros.asignaciones.store');
    Route::put('/AdminCentros/{ciclo}/asignaciones/{id}', [CentrosCostosController::class, 'updateAsignacion'])->name('centros.asignaciones.update');
    Route::delete('/AdminCentros/{ciclo}/asignaciones/{id}', [CentrosCostosController::class, 'destroyAsignacion'])->name('centros.asignaciones.destroy');
    Route::get('/AdminCentros/{ciclo}/importar-usuarios', [CentrosCostosController::class, 'listImportarUsuarios'])->name('centros.importar.usuarios');
    Route::put('/AdminCentros/{ciclo}/importar-usuarios', [CentrosCostosController::class, 'syncImportarUsuarios'])->name('centros.importar.usuarios.sync');
    Route::get('/AdminCentros/{ciclo}', [CentrosCostosController::class, 'ciclo'])->name('centros.ciclo');
    Route::get('/CentrosCostos/api/ciclos', [CentrosCostosController::class, 'listCiclos'])->name('centros.api.ciclos');
    Route::post('/CentrosCostos/api/ciclos', [CentrosCostosController::class, 'storeCiclo'])->name('centros.api.ciclos.store');
    Route::post('/CentrosCostos/api/ciclos/{ciclo}/estado', [CentrosCostosController::class, 'updateCicloEstado'])->name('centros.api.ciclos.estado');
    Route::delete('/CentrosCostos/api/ciclos/{ciclo}', [CentrosCostosController::class, 'destroyCiclo'])->name('centros.api.ciclos.destroy');
    Route::get('/CentrosCostos/api/empresas', [CentrosCostosController::class, 'empresasSap'])->name('centros.api.empresas');
    Route::get('/CentrosCostos/api/centros', [CentrosCostosController::class, 'centrosSap'])->name('centros.api.centros');
    Route::get('/CentrosCostos/api/departamentos', [CentrosCostosController::class, 'departamentosCentros'])->name('centros.api.departamentos');
    Route::get('/CentrosCostos/api/cuentas', [CentrosCostosController::class, 'cuentasSap'])->name('centros.api.cuentas');
    Route::get('/CentrosCostos/api/mis-asignaciones', [CentrosCostosController::class, 'misAsignaciones'])->name('centros.api.mis');
    Route::get('/ControlCentros', [CentrosCostosController::class, 'control'])->name('centros.control');
    Route::get('/ControlCentros/detalle', [CentrosCostosController::class, 'detalle'])->name('centros.detalle');
    Route::get('/AnalisisProgreso', [CentrosCostosController::class, 'analisis'])->name('centros.analisis');
    Route::get('/CentrosCostos/api/catalogo', [CentrosCostosController::class, 'catalogo'])->name('centros.catalogo');
    Route::get('/CentrosCostos/api/gasto-real', [CentrosCostosController::class, 'gastoReal'])->name('centros.api.gasto_real');
    Route::get('/CentrosCostos/api/captura', [CentrosCostosController::class, 'captura'])->name('centros.api.captura');
    Route::put('/CentrosCostos/api/captura/presupuesto', [CentrosCostosController::class, 'guardarPresupuesto'])->name('centros.api.captura.presupuesto');
    Route::put('/CentrosCostos/api/captura/centro', [CentrosCostosController::class, 'guardarCapturaCentro'])->name('centros.api.captura.centro');
    Route::get('/CentrosCostos/api/captura/plantilla', [CentrosCostosController::class, 'plantillaCaptura'])->name('centros.api.captura.plantilla');
    Route::post('/CentrosCostos/api/captura/importar', [CentrosCostosController::class, 'importarCaptura'])->name('centros.api.captura.importar');

    Route::get('/Ventas/Asignaciones', [ProyeccionesVentasController::class, 'admin'])->name('pv.admin');
    Route::get('/Ventas/Asignaciones/{ciclo}/asignar/{empresa?}', [ProyeccionesVentasController::class, 'asignar'])
        ->where('empresa', 'austin|imsa|pitic|sydney')
        ->name('pv.asignar');
    Route::get('/Ventas/Asignaciones/{ciclo}/asignaciones', [ProyeccionesVentasController::class, 'listAsignaciones'])->name('pv.asignaciones.index');
    Route::post('/Ventas/Asignaciones/{ciclo}/asignaciones', [ProyeccionesVentasController::class, 'storeAsignacion'])->name('pv.asignaciones.store');
    Route::put('/Ventas/Asignaciones/{ciclo}/asignaciones/{id}', [ProyeccionesVentasController::class, 'updateAsignacion'])->name('pv.asignaciones.update');
    Route::delete('/Ventas/Asignaciones/{ciclo}/asignaciones/{id}', [ProyeccionesVentasController::class, 'destroyAsignacion'])->name('pv.asignaciones.destroy');
    Route::get('/Ventas/Asignaciones/{ciclo}/importar-usuarios', [ProyeccionesVentasController::class, 'listImportarUsuarios'])->name('pv.importar.usuarios');
    Route::put('/Ventas/Asignaciones/{ciclo}/importar-usuarios', [ProyeccionesVentasController::class, 'syncImportarUsuarios'])->name('pv.importar.usuarios.sync');
    Route::get('/Ventas/Asignaciones/{ciclo}', [ProyeccionesVentasController::class, 'ciclo'])->name('pv.ciclo');
    Route::get('/ProyeccionesVentas/api/ciclos', [ProyeccionesVentasController::class, 'listCiclos'])->name('pv.api.ciclos');
    Route::post('/ProyeccionesVentas/api/ciclos', [ProyeccionesVentasController::class, 'storeCiclo'])->name('pv.api.ciclos.store');
    Route::post('/ProyeccionesVentas/api/ciclos/{ciclo}/estado', [ProyeccionesVentasController::class, 'updateCicloEstado'])->name('pv.api.ciclos.estado');
    Route::put('/ProyeccionesVentas/api/ciclos/{ciclo}/tipo-cambio-meses', [ProyeccionesVentasController::class, 'updateTipoCambioMeses'])->name('pv.api.ciclos.tc_meses');
    Route::delete('/ProyeccionesVentas/api/ciclos/{ciclo}', [ProyeccionesVentasController::class, 'destroyCiclo'])->name('pv.api.ciclos.destroy');
    Route::get('/ProyeccionesVentas/api/empresas', [ProyeccionesVentasController::class, 'empresasSap'])->name('pv.api.empresas');
    Route::get('/ProyeccionesVentas/api/centros', [ProyeccionesVentasController::class, 'centrosSap'])->name('pv.api.centros');
    Route::get('/ProyeccionesVentas/api/cuentas', [ProyeccionesVentasController::class, 'cuentasSap'])->name('pv.api.cuentas');
    Route::get('/ProyeccionesVentas/api/mis-asignaciones', [ProyeccionesVentasController::class, 'misAsignaciones'])->name('pv.api.mis');
    Route::get('/Ventas/Captura', [ProyeccionesVentasController::class, 'control'])->name('pv.control');
    Route::get('/Ventas/Captura/detalle', [ProyeccionesVentasController::class, 'detalle'])->name('pv.detalle');
    Route::get('/Ventas/Analisis', [ProyeccionesVentasController::class, 'analisis'])->name('pv.analisis');
    Route::get('/Ventas/Costos', [ProyeccionesVentasController::class, 'costos'])->name('pv.costos');
    Route::get('/ProyeccionesVentas/api/costos', [ProyeccionesVentasController::class, 'listCostos'])->name('pv.api.costos');
    Route::get('/ProyeccionesVentas/api/costos/historial', [ProyeccionesVentasController::class, 'historialCostoProducto'])->name('pv.api.costos.historial');
    Route::put('/ProyeccionesVentas/api/costos', [ProyeccionesVentasController::class, 'guardarCostoProducto'])->name('pv.api.costos.save');
    Route::put('/ProyeccionesVentas/api/costos/meses', [ProyeccionesVentasController::class, 'guardarCostosMeses'])->name('pv.api.costos.meses');
    Route::post('/ProyeccionesVentas/api/costos/importar-api', [ProyeccionesVentasController::class, 'importarCostosDesdeApi'])->name('pv.api.costos.import');
    Route::post('/ProyeccionesVentas/api/costos/actualizar-desde-api', [ProyeccionesVentasController::class, 'actualizarCostoDesdeApi'])->name('pv.api.costos.actualizar_api');
    Route::get('/ProyeccionesVentas/api/costos/plantilla', [ProyeccionesVentasController::class, 'plantillaCostos'])->name('pv.api.costos.plantilla');
    Route::post('/ProyeccionesVentas/api/costos/importar-excel', [ProyeccionesVentasController::class, 'importarCostosExcel'])->name('pv.api.costos.import_excel');
    Route::get('/ProyeccionesVentas/api/catalogo', [ProyeccionesVentasController::class, 'catalogo'])->name('pv.catalogo');
    Route::get('/ProyeccionesVentas/api/gasto-real', [ProyeccionesVentasController::class, 'gastoReal'])->name('pv.api.gasto_real');
    Route::get('/ProyeccionesVentas/api/listas-precios', [ProyeccionesVentasController::class, 'listasPrecios'])->name('pv.api.listas_precios');
    Route::get('/ProyeccionesVentas/api/captura', [ProyeccionesVentasController::class, 'captura'])->name('pv.api.captura');
    Route::put('/ProyeccionesVentas/api/captura/presupuesto', [ProyeccionesVentasController::class, 'guardarPresupuesto'])->name('pv.api.captura.presupuesto');
    Route::put('/ProyeccionesVentas/api/captura/centro', [ProyeccionesVentasController::class, 'guardarCapturaCentro'])->name('pv.api.captura.centro');
    Route::get('/ProyeccionesVentas/api/captura/plantilla', [ProyeccionesVentasController::class, 'plantillaCaptura'])->name('pv.api.captura.plantilla');
    Route::post('/ProyeccionesVentas/api/captura/importar', [ProyeccionesVentasController::class, 'importarCaptura'])->name('pv.api.captura.importar');

   
  });

   // Ruta para facturación de proyectos
    Route::get('/facturacion-proyecto/{id}/{servicio}/{id_division}', [TimbrarNominaController::class, 'FacturacionProyecto'])->name('facturacion.proyecto');
