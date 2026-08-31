@extends('layouts.app')
@section('content')

@if($mensaje = Session::get('successExcel'))
    @php
        echo '<script language="JavaScript">';
        echo 'const Toast = Swal.mixin({toast: true,position: "top-end",showConfirmButton: false,timer: 8000,timerProgressBar: true,';
        echo 'didOpen: (toast) => { toast.onmouseenter = Swal.stopTimer; toast.onmouseleave = Swal.resumeTimer;}});';
        echo 'Toast.fire({ icon: "success", title: "¡Acción exitosa!", text: "' . e(Session::get('successExcel')) . '"});';
        echo '</script>';
    @endphp
@endif

<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">
<link href="{{ asset('css/inputfile.css') }}" rel="stylesheet">

<style>
  .asist-page {
    color: #1f2937;
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
    box-sizing: border-box;
  }
  .asist-page .row {
    margin-left: 0;
    margin-right: 0;
    max-width: 100%;
  }
  .asist-page .asist-table-scroll,
  .asist-page .asist-table-scroll:has(.dataTables_wrapper),
  .asist-page .asist-table-scroll:has(.dt-container) {
    display: block;
    overflow-x: auto !important;
    overflow-y: hidden !important;
    max-width: 100%;
    width: 100%;
    box-sizing: border-box;
    -webkit-overflow-scrolling: touch;
  }
  .asist-page .asist-table-scroll .dt-container,
  .asist-page .asist-table-scroll .dataTables_wrapper {
    width: 100% !important;
    max-width: 100%;
    box-sizing: border-box;
  }
  .asist-page .asist-table-scroll .dt-layout-row {
    display: flex !important;
    flex-wrap: wrap !important;
    align-items: center !important;
    max-width: 100% !important;
    gap: 0.35rem;
  }
  .asist-page .asist-table-scroll .dt-layout-cell,
  .asist-page .asist-table-scroll .dt-buttons {
    max-width: 100%;
    flex-wrap: wrap;
  }
  .asist-page .asist-table-scroll table.dataTable {
    width: 100% !important;
    margin: 0 !important;
  }
  .asist-hero {
    padding: 1.1rem 1.25rem;
    background: linear-gradient(135deg, #ffffff 0%, #faf7ff 48%, #f8fafc 100%);
    border: 1px solid rgba(100, 116, 139, 0.16);
    border-radius: 18px;
    box-shadow: 0 8px 22px rgba(51, 65, 85, 0.08);
  }
  .asist-summary-card {
    padding: 0.9rem 1rem;
    background: #fff;
    border: 1px solid rgba(100, 116, 139, 0.12);
    border-radius: 14px;
    box-shadow: 0 4px 14px rgba(51, 65, 85, 0.05);
    height: 100%;
  }
  .asist-summary-label {
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 0.25rem;
  }
  .asist-summary-value {
    margin: 0;
    font-size: 1.35rem;
    font-weight: 800;
    color: #1f2937;
  }
  .asist-panel {
    padding: 1rem;
    background: #fff;
    border: 1px solid rgba(100, 116, 139, 0.12);
    border-radius: 16px;
    box-shadow: 0 4px 14px rgba(51, 65, 85, 0.05);
    overflow: hidden;
    max-width: 100%;
    box-sizing: border-box;
  }
  .asist-page .celda-comentario {
    max-width: 220px;
    white-space: normal;
    word-break: break-word;
  }
  .asist-panel-title {
    font-size: 0.95rem;
    font-weight: 800;
    color: #1f2937;
    margin-bottom: 0.85rem;
  }
  .badge-estado-a { background: #166534; color: #ecfdf5; }
  .badge-estado-f { background: #991b1b; color: #fef2f2; }
  .badge-horario-descanso { background: #f1f5f9; color: #64748b; }
  .badge-horario-laboral { background: #e0f2fe; color: #0369a1; }
  .celda-estado {
    min-width: 72px;
    white-space: nowrap;
  }
  .estado-asist-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    border: 1px solid transparent;
    padding: 0;
    line-height: 1;
  }
  .estado-asist-pill-a {
    background-color: #166534;
    border-color: #14532d;
  }
  .estado-asist-pill-f {
    background-color: #991b1b;
    border-color: #7f1d1d;
  }
  .select-estado-asistencia {
    min-width: 52px;
    width: 52px;
    height: 30px;
    margin: 0;
    padding: 0 1.15rem 0 0.15rem;
    font-weight: 800;
    font-size: 0.9rem;
    text-align: center;
    text-align-last: center;
    border: 0;
    border-radius: 8px;
    cursor: pointer;
    background-color: transparent !important;
    background-repeat: no-repeat;
    background-position: right 0.2rem center;
    background-size: 10px 7px;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    box-shadow: none !important;
  }
  .estado-asist-pill-a .select-estado-asistencia {
    color: #ecfdf5 !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ecfdf5'%3E%3Cpath d='M4.5 6L8 9.5 11.5 6'/%3E%3C/svg%3E");
  }
  .estado-asist-pill-f .select-estado-asistencia {
    color: #fef2f2 !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23fef2f2'%3E%3Cpath d='M4.5 6L8 9.5 11.5 6'/%3E%3C/svg%3E");
  }
  .select-estado-asistencia:focus {
    outline: none;
  }
  .select-estado-asistencia option {
    color: #1f2937;
    background: #fff;
    font-weight: 700;
    text-align: center;
  }
  .asist-filtro-estado {
    min-width: 180px;
    max-width: 220px;
  }
  .input-hora-asistencia {
    min-width: 96px;
    max-width: 110px;
    font-size: 0.78rem;
    padding: 0.15rem 0.35rem;
    margin: 0 auto;
  }
  .select-horario-asistencia {
    min-width: 160px;
    max-width: 220px;
    font-size: 0.72rem;
    padding: 0.2rem 1.5rem 0.2rem 0.35rem;
  }
  .asist-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
    margin-bottom: 1rem;
  }
  .asist-filtro-empleado {
    min-width: 280px;
    max-width: 380px;
    flex: 1 1 280px;
  }
  .asist-empty {
    text-align: center;
    padding: 3rem 1rem;
    color: #64748b;
  }
  .asist-empty i { font-size: 2.5rem; opacity: 0.35; margin-bottom: 0.75rem; }
</style>

<div class="asist-page asist-page-root px-2 px-md-3">
  <div class="asist-hero mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <div class="mb-1">
          <a href="/Nominas/editarnomina/{{ $nominaEnc->id }}/{{ $nominaEnc->idtiponomina }}/{{ $fechaInicio }}/{{ $fechaFin }}" class="text-muted text-decoration-none fs-8">
            <i class="fa-solid fa-chevron-left me-1"></i>Edición de nómina
          </a>
        </div>
        <h2 class="h5 fw-bold mb-1">
          <i class="fa-solid fa-clock me-2"></i>Asistencias de nómina
        </h2>
        <p class="mb-0 text-muted fs-8">
          <b>{{ $nominaEnc->nombre_nomina }}</b> &middot;
          {{ date('d/m/Y', strtotime($fechaInicio)) }} al {{ date('d/m/Y', strtotime($fechaFin)) }}
          <span class="badge {{ $nominaEnc->estado_nomina == 'Edicion' ? 'bg-primary' : 'bg-danger' }} ms-1">
            {{ $nominaEnc->estado_nomina }}
          </span>
        </p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        @if($permisosImportar == 'importar_bonos_nominas' && $nominaEnc->estado_nomina == 'Edicion')
          <button type="button" class="btn btn-baseColor btn-sm" data-bs-toggle="modal" data-bs-target="#modalImportAsistencias">
            <i class="fas fa-arrow-up-from-bracket me-1"></i>Importar
          </button>
          <a class="btn btn-outline-primary btn-sm" href="/Nominas/exportar_formato_asistencias/{{ $nominaEnc->id }}">
            <i class="fa-sharp fa-solid fa-file-arrow-down me-1"></i>Formato
          </a>
        @endif
      </div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-lg-3 col-md-6">
      <div class="asist-summary-card">
        <div class="asist-summary-label">Empleados con registro</div>
        <p class="asist-summary-value">{{ $totales['empleados_con_datos'] }}</p>
      </div>
    </div>
    <div class="col-lg-3 col-md-6">
      <div class="asist-summary-card">
        <div class="asist-summary-label">Días trabajados (suma)</div>
        <p class="asist-summary-value">{{ number_format($totales['dias_trabajados'], 2) }}</p>
      </div>
    </div>
    <div class="col-lg-3 col-md-6">
      <div class="asist-summary-card">
        <div class="asist-summary-label">Asistencias (A)</div>
        <p class="asist-summary-value text-success">{{ $totales['total_asistencias'] }}</p>
      </div>
    </div>
    <div class="col-lg-3 col-md-6">
      <div class="asist-summary-card">
        <div class="asist-summary-label">Faltas (F)</div>
        <p class="asist-summary-value text-danger">{{ $totales['total_faltas'] }}</p>
      </div>
    </div>
  </div>

  @if($detalleAsistencias->isEmpty())
    <div class="asist-panel">
      <div class="asist-empty">
        <div><i class="fa-solid fa-calendar-xmark d-block"></i></div>
        <h6 class="fw-bold">Sin asistencias importadas</h6>
        <p class="mb-3 fs-8">Importe un archivo para ver el detalle por empleado y día.</p>
        @if($permisosImportar == 'importar_bonos_nominas' && $nominaEnc->estado_nomina == 'Edicion')
          <button type="button" class="btn btn-baseColor btn-sm" data-bs-toggle="modal" data-bs-target="#modalImportAsistencias">
            <i class="fas fa-arrow-up-from-bracket me-1"></i>Importar asistencias
          </button>
        @endif
      </div>
    </div>
  @else
    @php
      $puedeEditarAsistencias = $permisosImportar == 'importar_bonos_nominas' && $nominaEnc->estado_nomina == 'Edicion';
    @endphp
    <div class="row g-3 mb-3">
      <div class="col-12">
        <div class="asist-panel">
          <div class="asist-panel-title"><i class="fa-solid fa-users me-2"></i>Resumen por empleado</div>
          <div class="asist-table-scroll">
            <table class="table table-sm table-striped table-hover align-middle display" id="tablaResumenAsistencias">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Empleado</th>
                  <th class="text-center">Registros</th>
                  <th class="text-center">Días trabajados</th>
                  <th class="text-center">Asistencias</th>
                  <th class="text-center">Faltas</th>
                </tr>
              </thead>
              <tbody>
                @foreach($resumenEmpleados as $resumen)
                  <tr>
                    <td>{{ $resumen->id_empleado }}</td>
                    <td>{{ $resumen->nombre_empleado }}</td>
                    <td class="text-center">{{ $resumen->total_registros }}</td>
                    <td class="text-center fw-bold">{{ number_format($resumen->dias_trabajados, 2) }}</td>
                    <td class="text-center text-success">{{ $resumen->asistencias }}</td>
                    <td class="text-center text-danger">{{ $resumen->faltas }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="asist-panel">
      <div class="asist-panel-title"><i class="fa-solid fa-list-check me-2"></i>Detalle por día</div>
      @if($puedeEditarAsistencias)
        <p class="text-muted fs-8 mb-2">Puede editar horario, entrada, salida y estado (A/F). Los cambios recalculan día trabajado y la nómina automáticamente.</p>
      @endif

      <div class="asist-toolbar modern-form">
        <div class="asist-filtro-empleado">
          <select id="filtroEmpleado" class="form-select form-select-sm">
            <option value=""></option>
            @foreach($empleadosFiltro as $emp)
              <option value="{{ $emp->id }}">{{ $emp->id }} - {{ $emp->nombre }}</option>
            @endforeach
          </select>
        </div>
        <select id="filtroEstado" class="form-select form-select-sm asist-filtro-estado">
          <option value="">Todos los estados</option>
          <option value="A">Asistencia (A)</option>
          <option value="F">Falta (F)</option>
        </select>
        @if($puedeEditarAsistencias)
          <button type="button" class="btn btn-outline-secondary btn-sm ms-md-auto" id="btnRecalcularAsistencias">
            <i class="fa-solid fa-arrows-rotate me-1"></i>Recalcular días
          </button>
        @endif
      </div>

      <div class="asist-table-scroll">
        <table class="table table-sm table-striped table-hover align-middle display" id="tablaDetalleAsistencias">
          <thead>
            <tr>
              <th>ID</th>
              <th>Empleado</th>
              <th>Fecha</th>
              <th>Día</th>
              <th>Horario</th>
              <th>Entrada</th>
              <th>Salida</th>
              <th class="text-center" style="min-width: 72px;">Estado</th>
              <th class="text-center">Día trabajado</th>
              <th>Comentario</th>
            </tr>
          </thead>
          <tbody>
            @php
              $diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
            @endphp
            @foreach($detalleAsistencias as $row)
              @php
                $diaSemana = $diasSemana[\Carbon\Carbon::parse($row->fecha)->dayOfWeek];
                $esDescanso = $row->horario_tipo && str_contains(strtoupper($row->horario_tipo), 'DESCANSO');
                if ($row->horario_tipo) {
                  $horarioTexto = $row->horario_tipo;
                  if (!$esDescanso && $row->horario_entrada && $row->horario_salida) {
                    $horarioTexto .= ' (' . substr($row->horario_entrada, 0, 5) . ' - ' . substr($row->horario_salida, 0, 5) . ')';
                  }
                } else {
                  $horarioTexto = 'Sin horario';
                }
                $horarioSeleccionado = $row->id_horario ?? $row->horario_empleado_id ?? '';
              @endphp
              <tr data-registro="{{ $row->id }}">
                <td>{{ $row->id_empleado }}</td>
                <td>{{ $row->nombre_empleado }}</td>
                <td data-order="{{ $row->fecha }}">{{ date('d/m/Y', strtotime($row->fecha)) }}</td>
                <td>{{ $diaSemana }}</td>
                <td class="celda-horario" data-search="{{ $row->horario_tipo ?? 'Sin horario' }}">
                  @if($puedeEditarAsistencias)
                    <select class="form-select form-select-sm select-horario-asistencia" data-id="{{ $row->id }}">
                      <option value="">Sin horario</option>
                      @foreach($varhorarios as $horario)
                        <option value="{{ $horario->id }}" {{ (string) $horarioSeleccionado === (string) $horario->id ? 'selected' : '' }}>
                          {{ $horario->tipo }}
                          @if(!str_contains(strtoupper($horario->tipo), 'DESCANSO'))
                            ({{ substr((string) $horario->entrada, 0, 5) }} - {{ substr((string) $horario->salida, 0, 5) }})
                          @endif
                        </option>
                      @endforeach
                    </select>
                  @elseif($row->horario_tipo)
                    <span class="badge {{ $esDescanso ? 'badge-horario-descanso' : 'badge-horario-laboral' }}">{{ $horarioTexto }}</span>
                  @else
                    <span class="text-muted fs-8">{{ $horarioTexto }}</span>
                  @endif
                </td>
                <td class="text-center celda-entrada">
                  @if($puedeEditarAsistencias)
                    <input type="time" class="form-control form-control-sm input-hora-asistencia" data-campo="entrada" value="{{ $row->entrada ? substr($row->entrada, 0, 5) : '' }}">
                  @else
                    {{ $row->entrada ? substr($row->entrada, 0, 5) : '—' }}
                  @endif
                </td>
                <td class="text-center celda-salida">
                  @if($puedeEditarAsistencias)
                    <input type="time" class="form-control form-control-sm input-hora-asistencia" data-campo="salida" value="{{ $row->salida ? substr($row->salida, 0, 5) : '' }}">
                  @else
                    {{ $row->salida ? substr($row->salida, 0, 5) : '—' }}
                  @endif
                </td>
                <td class="text-center celda-estado" data-search="{{ $row->estado }}">
                  @if($puedeEditarAsistencias)
                    <span class="estado-asist-pill estado-asist-pill-{{ strtolower($row->estado) }}">
                      <select class="select-estado-asistencia" data-id="{{ $row->id }}">
                        <option value="A" {{ $row->estado === 'A' ? 'selected' : '' }}>A</option>
                        <option value="F" {{ $row->estado === 'F' ? 'selected' : '' }}>F</option>
                      </select>
                    </span>
                  @elseif($row->estado === 'A')
                    <span class="badge badge-estado-a">A</span>
                  @else
                    <span class="badge badge-estado-f">F</span>
                  @endif
                </td>
                <td class="text-center fw-bold celda-dia-trabajo" data-order="{{ $row->dia_trabajo }}">{{ number_format((float) $row->dia_trabajo, 2) }}</td>
                <td class="fs-8 text-muted celda-comentario">{{ $row->comentario }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif
</div>

@if($permisosImportar == 'importar_bonos_nominas' && $nominaEnc->estado_nomina == 'Edicion' && !$detalleAsistencias->isEmpty())
<form id="formRecalcularAsistencias" action="{{ route('Nominas.asistencias.recalcular', $nominaEnc->id) }}" method="POST" class="d-none">
  @csrf
</form>
@endif

@if($permisosImportar == 'importar_bonos_nominas' && $nominaEnc->estado_nomina == 'Edicion')
<div class="modal fade" id="modalImportAsistencias" tabindex="-1" aria-labelledby="modalImportAsistenciasLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalImportAsistenciasLabel">
          <i class="fa-solid fa-clock me-2"></i>Importar asistencias
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/Nominas/importAsistencias/{{ $nominaEnc->id }}" method="POST" enctype="multipart/form-data" class="msform needs-validation" novalidate>
        @csrf
        <div class="modal-body">
          <p class="text-muted fs-8 mb-3">
            Suba la plantilla IOHISA o el reporte <b>Total de Asistencia</b> del reloj checador.
            Se calcularán estado y día trabajado según el horario del empleado (incluyendo días de descanso) y las checadas registradas.
          </p>
          <div class="modern-file-input" data-input-id="import-asistencias">
            <div class="file-input-wrapper" id="wrapper-import-asistencias">
              <div class="file-input-content">
                <div class="file-input-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                <p class="file-input-text">Arrastra tu archivo aquí</p>
                <p class="file-input-subtext">o haz clic para seleccionar</p>
              </div>
              <input type="file" name="urlxlsx_asistencias" id="import-asistencias" class="hidden-file-input" required accept=".xlsx"/>
            </div>
            <div class="file-preview" id="preview-import-asistencias">
              <div class="file-preview-item">
                <div class="file-preview-info">
                  <div class="file-preview-icon"><i class="fas fa-file-excel"></i></div>
                  <div class="file-preview-details">
                    <h6 id="filename-import-asistencias"></h6>
                    <small id="filesize-import-asistencias"></small>
                  </div>
                </div>
                <button type="button" class="file-preview-remove" onclick="removeFile('import-asistencias')">
                  <i class="fas fa-times"></i>
                </button>
              </div>
              <div class="progress-bar"><div class="progress-fill" id="progress-import-asistencias"></div></div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-baseColor">
            <i class="fas fa-arrow-up-from-bracket me-2"></i>Subir asistencias
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif

<script src="{{ asset('js/files.js') }}"></script>

@endsection

@section('js')
<script>
$(function() {
  $('#btnRecalcularAsistencias').on('click', function() {
    Swal.fire({
      title: '¿Recalcular días de asistencias?',
      text: 'Se volverán a calcular estado y día trabajado de todos los registros según horario y checadas, y se actualizará la nómina.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#047857',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Sí, recalcular',
      cancelButtonText: 'Cancelar',
      reverseButtons: true
    }).then(function(result) {
      if (result.isConfirmed) {
        $('#formRecalcularAsistencias').trigger('submit');
      }
    });
  });

  const asistDtButtons = [
    { extend: 'copy', text: '<i class="fa-regular fa-copy"></i>', titleAttr: 'Copiar', className: 'btn btn-tool-copy push' },
    { extend: 'excel', text: '<i class="fa-regular fa-file-excel"></i>', titleAttr: 'Excel', className: 'btn btn-tool-excel push' },
    { extend: 'pdf', text: '<i class="fa-regular fa-file-pdf"></i>', titleAttr: 'PDF', className: 'btn btn-tool-pdf push' },
    { extend: 'print', text: '<i class="fa-solid fa-print"></i>', titleAttr: 'Imprimir', className: 'btn btn-tool-print push' },
    { extend: 'colvis', text: '<i class="fa-solid fa-filter"></i>', titleAttr: 'Filtrar', className: 'btn btn-tool-colvis push' }
  ];

  const asistDtLang = {
    url: 'https://cdn.datatables.net/plug-ins/1.13.5/i18n/es-MX.json'
  };

  function initAsistDataTable(selector, order) {
    const $table = $(selector);
    if (!$table.length || !$.fn.DataTable) {
      return null;
    }
    if ($.fn.DataTable.isDataTable(selector)) {
      return $table.DataTable();
    }
    return $table.DataTable({
      responsive: false,
      scrollX: false,
      autoWidth: false,
      select: false,
      order: order,
      layout: { topStart: { buttons: asistDtButtons } },
      oLanguage: { sSearch: '<i class="fa-solid fa-magnifying-glass"></i>' },
      language: asistDtLang
    });
  }

  initAsistDataTable('#tablaResumenAsistencias', [[0, 'asc']]);
  const tablaDetalle = initAsistDataTable('#tablaDetalleAsistencias', [[0, 'asc'], [2, 'asc']]);

  const modal = document.getElementById('modalImportAsistencias');
  if (modal && typeof bindModernFileInputs === 'function') {
    modal.addEventListener('shown.bs.modal', function() {
      bindModernFileInputs(modal);
    });
  }

  const $filtroEmpleado = $('#filtroEmpleado');

  function aplicarColorEstadoSelect(select) {
    const $select = $(select);
    const $pill = $select.closest('.estado-asist-pill');
    $pill.removeClass('estado-asist-pill-a estado-asist-pill-f');
    if ($select.val() === 'A') {
      $pill.addClass('estado-asist-pill-a');
    } else if ($select.val() === 'F') {
      $pill.addClass('estado-asist-pill-f');
    }
  }

  $('.select-estado-asistencia').each(function() {
    aplicarColorEstadoSelect(this);
  });

  if ($filtroEmpleado.length && typeof $.fn.select2 !== 'undefined') {
    if ($filtroEmpleado.hasClass('select2-hidden-accessible')) {
      $filtroEmpleado.select2('destroy');
    }

    $filtroEmpleado.select2({
      theme: 'bootstrap-5',
      width: '100%',
      allowClear: true,
      placeholder: 'Todos los empleados',
      dropdownParent: $filtroEmpleado.parent(),
      language: {
        noResults: function() { return 'Sin resultados'; },
        searching: function() { return 'Buscando...'; }
      }
    });
  }

  if (!tablaDetalle) {
    return;
  }

  function aplicarFiltrosDetalle() {
    const emp = $filtroEmpleado.val();
    const est = $('#filtroEstado').val();
    tablaDetalle.column(0).search(emp ? '^' + emp + '$' : '', true, false);
    tablaDetalle.column(7).search(est || '');
    tablaDetalle.draw();
  }

  $filtroEmpleado.on('change', aplicarFiltrosDetalle);
  $('#filtroEstado').on('change', aplicarFiltrosDetalle);

  function actualizarFilaAsistencia($fila, data) {
    if (data.entrada !== undefined) {
      $fila.find('[data-campo="entrada"]').val(data.entrada || '');
    }
    if (data.salida !== undefined) {
      $fila.find('[data-campo="salida"]').val(data.salida || '');
    }
    if (data.horario_id !== undefined) {
      $fila.find('.select-horario-asistencia').val(data.horario_id || '');
    }
    if (data.horario_texto !== undefined) {
      $fila.find('.celda-horario').attr('data-search', data.horario_texto);
    }
    $fila.find('.celda-dia-trabajo').text(parseFloat(data.dia_trabajo).toFixed(2));
    $fila.find('.celda-comentario').text(data.comentario);
    const $estadoSelect = $fila.find('.select-estado-asistencia');
    if ($estadoSelect.length && data.estado !== undefined) {
      $estadoSelect.val(data.estado);
      aplicarColorEstadoSelect($estadoSelect[0]);
      $fila.find('.celda-estado').attr('data-search', data.estado);
    }
  }

  let guardandoHorario = false;
  let horarioAnteriorAsistencia = null;

  $(document).on('focus', '.select-horario-asistencia', function() {
    horarioAnteriorAsistencia = this.value;
  });

  $(document).on('change', '.select-horario-asistencia', function() {
    if (guardandoHorario) {
      return;
    }

    const select = this;
    const nuevoHorario = select.value;
    const $fila = $(select).closest('tr');
    const idRegistro = select.dataset.id;

    if (nuevoHorario === horarioAnteriorAsistencia) {
      return;
    }

    guardandoHorario = true;

    $.ajax({
      url: '{{ route('Nominas.asistencias.horario', $nominaEnc->id) }}',
      method: 'POST',
      data: {
        _token: $('meta[name="csrf-token"]').attr('content'),
        id_registro: idRegistro,
        id_horario: nuevoHorario || ''
      },
      success: function(res) {
        guardandoHorario = false;
        if (res.ok) {
          actualizarFilaAsistencia($fila, res.data);
          horarioAnteriorAsistencia = nuevoHorario;
          Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: res.message,
            showConfirmButton: false,
            timer: 3000
          });
        } else {
          select.value = horarioAnteriorAsistencia;
          Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo actualizar el horario.' });
        }
      },
      error: function(xhr) {
        guardandoHorario = false;
        select.value = horarioAnteriorAsistencia;
        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'No se pudo actualizar el horario.';
        Swal.fire({ icon: 'error', title: 'Error', text: msg });
      }
    });
  });

  let guardandoChecadas = false;

  $(document).on('change', '.input-hora-asistencia', function() {
    if (guardandoChecadas) {
      return;
    }

    const $fila = $(this).closest('tr');
    const idRegistro = $fila.data('registro');
    const entrada = $fila.find('[data-campo="entrada"]').val() || '';
    const salida = $fila.find('[data-campo="salida"]').val() || '';

    guardandoChecadas = true;

    $.ajax({
      url: '{{ route('Nominas.asistencias.checadas', $nominaEnc->id) }}',
      method: 'POST',
      data: {
        _token: $('meta[name="csrf-token"]').attr('content'),
        id_registro: idRegistro,
        entrada: entrada,
        salida: salida
      },
      success: function(res) {
        guardandoChecadas = false;
        if (res.ok) {
          actualizarFilaAsistencia($fila, res.data);
          Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: res.message,
            showConfirmButton: false,
            timer: 3000
          });
        } else {
          Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo actualizar.' });
        }
      },
      error: function(xhr) {
        guardandoChecadas = false;
        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'No se pudo actualizar el horario.';
        Swal.fire({ icon: 'error', title: 'Error', text: msg });
      }
    });
  });

  let estadoAnteriorAsistencia = null;

  $(document).on('focus', '.select-estado-asistencia', function() {
    estadoAnteriorAsistencia = this.value;
  });

  $(document).on('change', '.select-estado-asistencia', function() {
    const select = this;
    const nuevoEstado = select.value;
    const idRegistro = select.dataset.id;
    const $fila = $(select).closest('tr');

    if (nuevoEstado === estadoAnteriorAsistencia) {
      return;
    }

    aplicarColorEstadoSelect(select);

    Swal.fire({
      title: '¿Cambiar estado?',
      text: 'Se actualizará el registro y se recalculará la nómina.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, cambiar',
      cancelButtonText: 'Cancelar'
    }).then(function(result) {
      if (!result.isConfirmed) {
        select.value = estadoAnteriorAsistencia;
        aplicarColorEstadoSelect(select);
        return;
      }

      $.ajax({
        url: '{{ route('Nominas.asistencias.estado', $nominaEnc->id) }}',
        method: 'POST',
        data: {
          _token: $('meta[name="csrf-token"]').attr('content'),
          id_registro: idRegistro,
          estado: nuevoEstado
        },
        success: function(res) {
          if (res.ok) {
            actualizarFilaAsistencia($fila, res.data);
            estadoAnteriorAsistencia = nuevoEstado;
            Swal.fire({
              toast: true,
              position: 'top-end',
              icon: 'success',
              title: res.message,
              showConfirmButton: false,
              timer: 3500
            });
          } else {
            select.value = estadoAnteriorAsistencia;
            aplicarColorEstadoSelect(select);
            Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo actualizar.' });
          }
        },
        error: function(xhr) {
          select.value = estadoAnteriorAsistencia;
          aplicarColorEstadoSelect(select);
          const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'No se pudo actualizar el estado.';
          Swal.fire({ icon: 'error', title: 'Error', text: msg });
        }
      });
    });
  });
});
</script>
@endsection
