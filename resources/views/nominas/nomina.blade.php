@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">
<style>
    .nomina-table-wrapper {
        overflow-x: auto;
        overflow-y: visible;
    }
    .nomina-table-wrapper .dropdown-menu {
        z-index: 1056;
    }
    .nomina-charts-toggle {
        width: 100%;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #f8fafc;
        color: #1e40af;
        font-weight: 600;
        padding: 12px 16px;
        text-align: left;
        transition: background 0.2s ease;
    }
    .nomina-charts-toggle:hover,
    .nomina-charts-toggle:focus {
        background: #eff6ff;
        color: #1e40af;
    }
    .nomina-charts-toggle .toggle-icon {
        transition: transform 0.2s ease;
    }
    .nomina-charts-toggle:not(.collapsed) .toggle-icon {
        transform: rotate(180deg);
    }
    .nomina-charts-card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
        padding: 18px;
        height: 100%;
    }
    .nomina-chart-title {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 4px;
    }
    .nomina-chart-subtitle {
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 12px;
    }
    .nomina-chart-box {
        height: 280px;
    }
    @media (max-width: 768px) {
        .nomina-chart-box { height: 220px; }
    }
    .nomina-filters {
        background: var(--table-soft, #f9fafb);
        border: 1px solid var(--table-border, #e5e7eb);
        margin-bottom: 0.85rem;
        padding: 0.55rem 0.75rem !important;
        border-radius: 14px !important;
        box-shadow: none;
    }
    .nomina-filters form {
        gap: 0.35rem 0.65rem;
    }
    .nomina-filters .filter-field {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        flex: 0 1 auto;
        max-width: none;
    }
    .nomina-filters .form-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--table-muted, #6b7280);
        margin-bottom: 0;
        white-space: nowrap;
    }
    .nomina-filters .form-control-sm,
    .nomina-filters .form-select-sm {
        min-height: 28px;
        height: 28px;
        font-size: 0.78rem;
        padding: 0.15rem 0.4rem;
        width: auto;
        background: #ffffff !important;
        border: 1px solid var(--table-border, #e5e7eb) !important;
        border-radius: 10px !important;
        color: var(--table-text, #111827) !important;
    }
    .nomina-filters input[type="date"] {
        min-width: 128px;
        max-width: 138px;
    }
    .nomina-filters .form-select-sm {
        min-width: 120px;
        max-width: 160px;
    }
    .nomina-filters .btn-sm {
        min-height: 28px;
        padding: 0.15rem 0.55rem;
        font-size: 0.78rem;
    }
    @media (max-width: 768px) {
        .nomina-filters .filter-field {
            flex: 1 1 calc(50% - 0.5rem);
        }
        .nomina-filters input[type="date"],
        .nomina-filters .form-select-sm {
            flex: 1;
            max-width: none;
        }
    }
</style>

    <div class="container-fluid format_page">
        <!-- Header Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Gestión de Nóminas</h2>
                            <p class="text-muted mb-0">Administración de nómina en empleados</p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-baseColor fs-7 mb-2" type="button" data-bs-toggle="modal"
                            data-bs-target="#modalNuevaNomina" aria-controls="modalNuevaNomina">
                            <i class="fa-solid fa-plus"></i> Nueva Nómina
                        </button> 

                        <a class="btn btn-baseColor-light fs-7 mb-2" href="/Nominas/Aguinaldos">
                            <i class="fa-solid fa-gift"></i> Aguinaldos
                        </a>

                        <a class="btn btn-baseColor-light fs-7 mb-2" href="{{ route('index_aportaciones_patronales') }}">
                            <i class="fa-solid fa-building-columns"></i> Aportaciones Patronales
                        </a> 

                        <button class="btn btn-baseColor-light fs-7 mb-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight">
                            <i class="fa-solid fa-address-book"></i> Configuraciones
                        </button> 
                    </div>
                </div>
            </div>
        </div>


        @if ($permisos2 == 'ver_nominas')
            <div class="row mt-3">
                <div class="table-responsive nomina-table-wrapper">
                    <div class="filters nomina-filters">
                        <form method="GET" action="{{ route('vernominas') }}" class="d-flex flex-wrap align-items-center">
                            <div class="filter-field">
                                <label class="form-label" for="fecha_inicio">Inicio</label>
                                <input type="date" class="form-control form-control-sm" id="fecha_inicio" name="fecha_inicio" value="{{ $fechaInicio }}" required />
                            </div>
                            <div class="filter-field">
                                <label class="form-label" for="fecha_fin">Fin</label>
                                <input type="date" class="form-control form-control-sm" id="fecha_fin" name="fecha_fin" value="{{ $fechaFin }}" required />
                            </div>
                            <div class="filter-field">
                                <label class="form-label" for="filtro_tipo_nomina">Tipo</label>
                                <select class="form-select form-select-sm" id="filtro_tipo_nomina" name="tipo_nomina">
                                    <option value="">Todos</option>
                                    @foreach ($vartiponominas as $tipoNominaOption)
                                        <option value="{{ $tipoNominaOption->id }}" {{ (string) $tipoNomina === (string) $tipoNominaOption->id ? 'selected' : '' }}>
                                            {{ $tipoNominaOption->tipo }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-baseColor-light btn-sm flex-shrink-0">
                                <i class="fa-solid fa-filter"></i> Filtrar
                            </button>
                        </form>
                    </div>

                    <table class="table table-stripped table-hover display" id="tablenomina">
                        <thead>
                            <tr>
                                <th class="text-truncate">Acciones</th>
                                <th class="text-truncate">Nombre de Nómina</th>
                                <th class="text-truncate">Fecha inicio</th>
                                <th class="text-truncate">Fecha Fin</th>
                                <th class="text-truncate">Estado</th>
                                <th class="text-truncate">Tipo Nómina</th>
                                {{-- <th class="text-truncate">Reporte por Sucursal</th>
                                <th class="text-truncate">Retenciones por Sucursal</th> --}}
                                <th class="text-truncate">Descargables</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($varnominas as $nomina)
                                <tr>
                                    <td class="text-truncate">
                                        {{-- calcular --}}
                                        @if ($nomina->estado_nomina === 'Iniciada')
                                            <button class="btn btn-primary m-0" disabled title="Editar">
                                                <i class="fa-solid fa-pen fs-8"></i>
                                            </button>

                                            <button class="btn btn-info m-0" disabled title="Resultados">
                                                <i class="fa-solid fa-chart-column fs-8"></i>
                                            </button>

                                            <button class="btn btn-danger m-0" type="button" title="Eliminar" onclick="confirmarEliminarNomina(this)"
                                                data-delete-url="/Nominaseliminar/{{ $nomina->id }}">
                                                <i class="fa-solid fa-trash fs-8"></i>
                                            </button>

                                            {{-- <button class="btn btn-success m-0" disabled data-bs-toggle="tooltip" data-bs-placement="right" title="Exportar">
                                                <i class="fa-solid fa-download fs-8"></i>
                                            </button> --}}

                                        {{-- editar --}}
                                        @elseif($nomina->estado_nomina === 'Edicion')
                                            @if ($permisos4 == 'actualizar_nominas')
                                                <a class="btn btn-primary m-0" href="/Nominas/editarnomina/{{ $nomina->id }}/{{ $nomina->idtiponomina }}/{{ $nomina->fecha_inicio_f }}/{{ $nomina->fecha_fin_f }}" title="Editar">
                                                    <i class="fa-solid fa-pen fs-8"></i>
                                                </a>
                                            @else
                                                 <button class="btn btn-primary m-0" disabled title="Editar">
                                                    <i class="fa-solid fa-pen fs-8"></i>
                                                </button>
                                            @endif

                                            <a class="btn btn-info m-0" href="{{ route('nominas.resultados', $nomina->id) }}" title="Resultados">
                                                <i class="fa-solid fa-chart-column fs-8"></i>
                                            </a>

                                            <button class="btn btn-danger m-0" type="button" title="Eliminar" onclick="confirmarEliminarNomina(this)"
                                                data-delete-url="/Nominaseliminar/temporal/{{ $nomina->id }}">
                                                <i class="fa-solid fa-trash fs-8"></i>
                                            </button>

                                            {{-- <button class="btn btn-success m-0" disabled data-bs-toggle="tooltip" data-bs-placement="right" title="Exportar">
                                                <i class="fa-solid fa-download fs-8"></i>
                                            </button> --}}

                                        {{-- terminada --}}
                                        @elseif($nomina->estado_nomina === 'Cerrada')
                                                @if ($permisos4 == 'actualizar_nominas')
                                                    <a class="btn btn-primary m-0" href="/Nominas/editarnomina/{{ $nomina->id }}/{{ $nomina->idtiponomina }}/{{ $nomina->fecha_inicio_f }}/{{ $nomina->fecha_fin_f }}" title="Editar">
                                                        <i class="fa-solid fa-pen fs-8"></i>
                                                    </a>
                                                @else
                                                    <button class="btn btn-primary m-0" disabled title="Editar">
                                                        <i class="fa-solid fa-pen fs-8"></i>
                                                    </button>
                                                @endif

                                                <a class="btn btn-info m-0" href="{{ route('nominas.resultados', $nomina->id) }}" title="Resultados">
                                                    <i class="fa-solid fa-chart-column fs-8"></i>
                                                </a>

                                                <button class="btn btn-danger m-0" type="button" title="Eliminar" onclick="confirmarEliminarNomina(this)"
                                                    data-delete-url="/Nominaseliminar/calcular/{{ $nomina->id }}/{{ \Carbon\Carbon::createFromFormat('d/m/Y', $nomina->fecha_inicio)->format('Y-m-d') }}/{{ \Carbon\Carbon::createFromFormat('d/m/Y', $nomina->fecha_fin)->format('Y-m-d') }}">
                                                    <i class="fa-solid fa-trash fs-8"></i>
                                                </button>

                                            {{-- @if ($permisos5 = 'exportar_nominas')
                                                <a class="btn btn-success m-0" href="/Nominas/exportar_excel/{{ $nomina->id }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Exportar">
                                                    <i class="fa-solid fa-download fs-8"></i>
                                                </a>
                                            @else
                                                <button class="btn btn-baseColor m-0" disabled data-bs-toggle="tooltip" data-bs-placement="right" title="Exportar">
                                                    <i class="fa-success fa-download fs-8"></i>
                                                </button>
                                            @endif --}}
                                        @endif
                                    </td>

                                    <td class="text-truncate">{{ $nomina->nombre_nomina }}</td>
                                    <td class="text-truncate">{{ $nomina->fecha_inicio }}</td>
                                    <td class="text-truncate">{{ $nomina->fecha_fin }}</td>

                                    <td class="text-truncate">
                                        @if ($nomina->estado_nomina == 'Iniciada')
                                            <span class="badge badge-success-dark fs-9"> {{ $nomina->estado_nomina }}</span>
                                        @elseif($nomina->estado_nomina == 'Edicion')
                                            <span class="badge badge-primary-dark fs-9"> Edición</span>
                                        @elseif($nomina->estado_nomina == 'Cerrada')
                                            <span class="badge badge-danger-dark fs-9"> {{ $nomina->estado_nomina }}</span>
                                        @endif
                                    </td>

                                    <td name="dtiponomina" class="text-truncate">
                                        @if ($nomina->idtiponomina == 1)
                                            Semanal
                                        @elseif($nomina->idtiponomina == 2)
                                            Quincenal
                                        @elseif($nomina->idtiponomina == 3)
                                            Mensual
                                        @endif
                                    </td>

                                    <td>
                                         @if($permisos5 == "exportar_nominas")
                                            <div class="dropdown">
                                                <a class="btn btn-success dropdown-toggle fs-8" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fa-solid fa-download "></i> Exportar 
                                                </a>

                                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                                                    <li><a class="dropdown-item" href="/Nominas/exportar_excel/{{$nomina->id}}">Excel Nomina</a></li>
                                                    <li><a class="dropdown-item" href="/Nominas/exportar_retenciones/{{$nomina->id}}">Excel Retenciones</a></li>
                                                    @if($nomina->estado_nomina == "Cerrada")
                                                        <!--<li><a class="dropdown-item" href="/ExpotArchivoDispersion/{{ $nomina->id }}">Excel Dispersión</a></li>
                                                        <li><a class="dropdown-item" href="/Exportarlayoutnomina/{{ $nomina->id}}">Layout Dispersión</a></li>-->
                                                        <li><a class="dropdown-item" href="/ExportarlayoutBanorte/{{ $nomina->id}}">Layout BANORTE</a></li>
                                                        <li><a class="dropdown-item" href="/Nominas/exportarComprobantes/{{ $nomina->id}}">Recibos de Pago</a></li>
                                                        <li><a class="dropdown-item" target="_blank" href="/Nominas/exportarCheques/{{ $nomina->id}}">Cheques de Pago</a></li>
                                                    @else
                                                        <!--<li><a class="dropdown-item disabled" href="/ExpotArchivoDispersion/{{ $nomina->id }}">Excel Dispersión</a></li>
                                                        <li><a class="dropdown-item disabled" href="/Exportarlayoutnomina/{{ $nomina->id}}">Layout Dispersión</a></li>-->
                                                        <li><a class="dropdown-item disabled" href="/ExportarlayoutBanorte/{{ $nomina->id}}">Layout BANORTE</a></li>
                                                        <li><a class="dropdown-item disabled" href="/Nominas/exportarComprobantes/{{ $nomina->id}}">Recibos de Pago</a></li>
                                                        <li><a class="dropdown-item disabled" href="/Nominas/exportarCheques/{{ $nomina->id}}">Cheques de Pago</a></li>
                                                    @endif
                                                </ul>
                                            </div>
                                            @else
                                                <div class="dropdown">
                                                    <button class="btn btn-success dropdown-toggle fs-8" style="width: 100%" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false" disabled>
                                                        <i class="fa-solid fa-download "></i> Exportar 
                                                    </button>
                                                </div>
                                            @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <button class="nomina-charts-toggle collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#nominaChartsCollapse" aria-expanded="false" aria-controls="nominaChartsCollapse">
                        <i class="fa-solid fa-chart-column me-2"></i>
                        Ver gráficas del periodo filtrado
                        <i class="fa-solid fa-chevron-down float-end toggle-icon"></i>
                    </button>
                </div>
            </div>

            <div class="collapse mt-3" id="nominaChartsCollapse">
                <div class="row g-3">
                    <div class="col-lg-6 col-12">
                        <div class="nomina-charts-card">
                            <div class="nomina-chart-title">Retenciones por periodo</div>
                            <div class="nomina-chart-subtitle">ISR, IMSS e INFONAVIT por nómina</div>
                            <div id="chartRetenciones" class="nomina-chart-box"></div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-12">
                        <div class="nomina-charts-card">
                            <div class="nomina-chart-title">Bonos y horas extras</div>
                            <div class="nomina-chart-subtitle">Comparativo por nómina filtrada</div>
                            <div id="chartBonosHoras" class="nomina-chart-box"></div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-12">
                        <div class="nomina-charts-card">
                            <div class="nomina-chart-title">Incapacidades y faltas</div>
                            <div class="nomina-chart-subtitle">Eventos registrados por nómina</div>
                            <div id="chartIncapacidades" class="nomina-chart-box"></div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-12">
                        <div class="nomina-charts-card">
                            <div class="nomina-chart-title">Vacaciones y días no laborados</div>
                            <div class="nomina-chart-subtitle">Totales por nómina</div>
                            <div id="chartVacaciones" class="nomina-chart-box"></div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-12">
                        <div class="nomina-charts-card">
                            <div class="nomina-chart-title">ISR retenido</div>
                            <div class="nomina-chart-subtitle">Monto acumulado por nómina</div>
                            <div id="chartIsr" class="nomina-chart-box"></div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-12">
                        <div class="nomina-charts-card">
                            <div class="nomina-chart-title">Impuestos y deducciones</div>
                            <div class="nomina-chart-subtitle">Distribución total del filtro</div>
                            <div id="chartImpuestos" class="nomina-chart-box"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Nueva Nómina -->
    <div class="modal fade" id="modalNuevaNomina" tabindex="-1" aria-labelledby="modalNuevaNominaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content nomina-nueva-modal">
                <div class="modal-header nomina-nueva-header border-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="nomina-nueva-header-icon">
                            <i class="fa-solid fa-file-invoice-dollar"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0 fw-bold" id="modalNuevaNominaLabel">Nueva nómina</h5>
                            <p class="mb-0 text-muted fs-8">Configure el periodo y tipo de nómina</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="{{ url('/Nominas') }}" method="POST" class="nomina-nueva-form g-3 needs-validation modern-form" novalidate id="formNuevaNomina">
                    @csrf

                    <div class="modal-body nomina-nueva-body">
                        <div class="card nomina-nueva-card border-0 shadow-sm rounded-4">
                            <div class="nomina-nueva-section-title">
                                <span class="nomina-nueva-step-badge">1</span>
                                <span>Datos generales</span>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="nombre_nomina">Nombre de nómina</label>
                                    <input type="text" class="form-control" name="nombre_nomina" id="nombre_nomina" placeholder="Ej. Quincena 1 - Junio 2026" required />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="tipo_nomina">Tipo de nómina</label>
                                    <select class="form-select" name="tipo_nomina" id="tipo_nomina" required>
                                        <option value="">Seleccionar...</option>
                                        @foreach ($vartiponominas as $tipoNomina)
                                            <option value="{{ $tipoNomina->id }}" {{ $tipoNomina->id == 1 ? 'selected' : '' }}>
                                                {{ $tipoNomina->tipo }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Por favor, selecciona el tipo de nómina.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="fecha_ini_nom">Fecha de inicio</label>
                                    <input type="date" id="fecha_ini_nom" name="fecha_ini_nom" class="form-control" required />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="fecha_ter_no">Fecha fin</label>
                                    <input type="date" id="fecha_ter_no" name="fecha_ter_no" class="form-control" required />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="tipo_empresa">Alcance de nómina</label>
                                    <select class="form-select" name="tipo_empresa" id="tipo_empresa" required>
                                        <option value="GENERAL" selected>General (todos los empleados)</option>
                                        <option value="EMPRESA">Por empresa</option>
                                    </select>
                                    <div class="invalid-feedback">Por favor, selecciona el alcance de la nómina.</div>
                                </div>

                                <div class="col-md-6 d-none" id="empresaNominaWrapper">
                                    <label class="form-label" for="id_empresa">Empresa</label>
                                    <select class="form-select" name="id_empresa" id="id_empresa">
                                        <option value="">Seleccionar...</option>
                                        @foreach ($varempresas as $empresa)
                                            <option value="{{ $empresa->id }}">{{ $empresa->nombre_empresa }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Por favor, selecciona la empresa.</div>
                                </div>
                            </div>

                            <div class="nomina-nueva-tip mt-3">
                                <i class="fa-solid fa-circle-info"></i>
                                <span id="tipoNominaHint">Quincenal: seleccione un periodo de 15 días.</span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer nomina-nueva-footer d-flex flex-wrap justify-content-end align-items-center gap-2">
                        <button type="button" class="btn btn-light fs-8" data-bs-dismiss="modal">
                            <i class="fa-solid fa-xmark"></i> Cancelar
                        </button>
                        <button class="btn btn-baseColor fs-8 px-4" type="submit">
                            <i class="fa-solid fa-check"></i> Crear nómina
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Catálogos -->
    <div class="offcanvas offcanvas-end nomina-offcanvas" tabindex="-1" id="offcanvasRight" aria-labelledby="offcanvasRightLabel">
        <div class="offcanvas-header nomina-offcanvas-header">
            <div class="d-flex align-items-center gap-3">
                <div class="nomina-offcanvas-header-icon">
                    <i class="fa-solid fa-sliders"></i>
                </div>
                <div>
                    <h5 class="offcanvas-title mb-0" id="offcanvasRightLabel">Configuraciones</h5>
                    <p class="text-muted mb-0 fs-8">Catálogos y factores de nómina</p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
        </div>
        <div class="offcanvas-body nomina-offcanvas-body">
            <div class="nomina-config-section">
                <div class="nomina-config-section__title">Catálogos fiscales</div>
                <div class="nomina-config-list">
                    <a class="nomina-config-item" href="/Nominas/CatalogoSubsidio">
                        <span class="nomina-config-item__icon">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                        </span>
                        <span class="nomina-config-item__content">
                            <span class="nomina-config-item__title">Factores Generales</span>
                            <span class="nomina-config-item__desc">UMA, subsidio y conceptos base</span>
                        </span>
                        <i class="fa-solid fa-chevron-right nomina-config-item__arrow"></i>
                    </a>

                    <a class="nomina-config-item" href="/Nominas/CatalogoISR">
                        <span class="nomina-config-item__icon">
                            <i class="fa-solid fa-money-bill-trend-up"></i>
                        </span>
                        <span class="nomina-config-item__content">
                            <span class="nomina-config-item__title">ISR Retenciones</span>
                            <span class="nomina-config-item__desc">Tablas de retención por tipo de nómina</span>
                        </span>
                        <i class="fa-solid fa-chevron-right nomina-config-item__arrow"></i>
                    </a>

                    <a class="nomina-config-item" href="/Nominas/CatalogoIMSS">
                        <span class="nomina-config-item__icon">
                            <i class="fa-solid fa-hospital"></i>
                        </span>
                        <span class="nomina-config-item__content">
                            <span class="nomina-config-item__title">IMSS Factores de Integración</span>
                            <span class="nomina-config-item__desc">Antigüedad, aguinaldo y vacaciones</span>
                        </span>
                        <i class="fa-solid fa-chevron-right nomina-config-item__arrow"></i>
                    </a>

                    <a class="nomina-config-item" href="/Nominas/CatalogoCesantiaVejez">
                        <span class="nomina-config-item__icon">
                            <i class="fa-solid fa-user-clock"></i>
                        </span>
                        <span class="nomina-config-item__content">
                            <span class="nomina-config-item__title">Cesantía y Vejez</span>
                            <span class="nomina-config-item__desc">Cuotas patronales por rango de SBC</span>
                        </span>
                        <i class="fa-solid fa-chevron-right nomina-config-item__arrow"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/validation.js') }}"></script>
    <script src="{{ asset('js/table.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('modalNuevaNomina');
            if (!modal) {
                return;
            }

            const tipoSelect = modal.querySelector('#tipo_nomina');
            const fechaInicio = modal.querySelector('#fecha_ini_nom');
            const fechaFin = modal.querySelector('#fecha_ter_no');
            const tipoEmpresaSelect = modal.querySelector('#tipo_empresa');
            const empresaWrapper = document.getElementById('empresaNominaWrapper');
            const empresaSelect = modal.querySelector('#id_empresa');
            const hint = document.getElementById('tipoNominaHint');

            if (!tipoSelect || !fechaInicio || !fechaFin) {
                return;
            }

            function actualizarCampoEmpresa() {
                if (!tipoEmpresaSelect || !empresaWrapper || !empresaSelect) {
                    return;
                }

                const esPorEmpresa = tipoEmpresaSelect.value === 'EMPRESA';
                empresaWrapper.classList.toggle('d-none', !esPorEmpresa);
                empresaSelect.required = esPorEmpresa;

                if (!esPorEmpresa) {
                    empresaSelect.value = '';
                }
            }

            if (tipoEmpresaSelect) {
                tipoEmpresaSelect.addEventListener('change', actualizarCampoEmpresa);
                actualizarCampoEmpresa();
            }

            const hints = {
                '1': 'Semanal: seleccione un periodo de 7 días.',
                '2': 'Quincenal: seleccione un periodo de 15 días.',
                '3': 'Mensual: seleccione un periodo de 28 a 31 días.'
            };

            const diasPorTipo = { '1': 6, '2': 14, '3': null };

            function formatDate(date) {
                return date.toISOString().split('T')[0];
            }

            function finDeMes(date) {
                return new Date(date.getFullYear(), date.getMonth() + 1, 0);
            }

            function actualizarFechaFin() {
                if (!fechaInicio.value || !tipoSelect.value) {
                    return;
                }

                const inicio = new Date(fechaInicio.value + 'T00:00:00');
                let fin;

                if (tipoSelect.value === '3') {
                    fin = finDeMes(inicio);
                } else {
                    const offset = diasPorTipo[tipoSelect.value] ?? 14;
                    fin = new Date(inicio);
                    fin.setDate(fin.getDate() + offset);
                }

                fechaFin.value = formatDate(fin);
            }

            tipoSelect.addEventListener('change', function () {
                if (hint) {
                    hint.textContent = hints[this.value] || 'Seleccione el tipo de nómina.';
                }
                actualizarFechaFin();
            });

            fechaInicio.addEventListener('change', actualizarFechaFin);

            if (tipoSelect.value) {
                if (hint) {
                    hint.textContent = hints[tipoSelect.value] || hint.textContent;
                }
            }
        });
    </script>

    @if ($permisos2 == 'ver_nominas')
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chartData = @json($chartData);
            const labels = chartData.labels.length ? chartData.labels : ['Sin datos'];
            const hasData = chartData.labels.length > 0;
            let chartsRendered = false;
            let charts = [];

            function renderNominaCharts() {
                if (chartsRendered) {
                    charts.forEach(chart => chart.reflow());
                    return;
                }

                chartsRendered = true;

                charts.push(Highcharts.chart('chartRetenciones', {
                    chart: { type: 'column' },
                    title: { text: null },
                    xAxis: { categories: labels },
                    yAxis: { title: { text: 'Monto ($)' } },
                    colors: ['#2563eb', '#10b981', '#8b5cf6'],
                    tooltip: { shared: true, valuePrefix: '$' },
                    series: [
                        { name: 'ISR', data: hasData ? chartData.isr : [0] },
                        { name: 'IMSS', data: hasData ? chartData.imss : [0] },
                        { name: 'INFONAVIT', data: hasData ? chartData.infonavit : [0] }
                    ]
                }));

                charts.push(Highcharts.chart('chartBonosHoras', {
                    chart: { type: 'line' },
                    title: { text: null },
                    xAxis: { categories: labels },
                    yAxis: { title: { text: 'Monto ($)' } },
                    colors: ['#10b981', '#2563eb'],
                    tooltip: { valuePrefix: '$' },
                    series: [
                        { name: 'Bonos', data: hasData ? chartData.bonos : [0] },
                        { name: 'Horas extra', data: hasData ? chartData.horasExtras : [0] }
                    ]
                }));

                charts.push(Highcharts.chart('chartIncapacidades', {
                    chart: { type: 'bar' },
                    title: { text: null },
                    xAxis: { categories: labels },
                    yAxis: { title: { text: 'Eventos' }, allowDecimals: false },
                    colors: ['#8b5cf6', '#a78bfa'],
                    series: [
                        { name: 'Incapacidades', data: hasData ? chartData.incapacidades : [0] },
                        { name: 'Faltas', data: hasData ? chartData.faltas : [0] }
                    ]
                }));

                charts.push(Highcharts.chart('chartVacaciones', {
                    chart: { type: 'area' },
                    title: { text: null },
                    xAxis: { categories: labels },
                    yAxis: { title: { text: 'Días' }, allowDecimals: false },
                    colors: ['#38bdf8', '#0ea5e9'],
                    series: [
                        { name: 'Vacaciones', data: hasData ? chartData.vacaciones : [0] },
                        { name: 'Días no laborados', data: hasData ? chartData.diasNoLaborados : [0] }
                    ]
                }));

                charts.push(Highcharts.chart('chartIsr', {
                    chart: { type: 'line' },
                    title: { text: null },
                    xAxis: { categories: labels },
                    yAxis: { title: { text: 'Monto ($)' } },
                    colors: ['#1e40af'],
                    tooltip: { valuePrefix: '$' },
                    series: [{
                        name: 'ISR retenido',
                        data: hasData ? chartData.isr : [0]
                    }]
                }));

                const impuestosPie = [
                    { name: 'ISR', y: chartData.totales.isr },
                    { name: 'IMSS', y: chartData.totales.imss },
                    { name: 'INFONAVIT', y: chartData.totales.infonavit },
                    { name: 'FONACOT', y: chartData.totales.fonacot },
                    { name: 'Deudores', y: chartData.totales.deudores }
                ].filter(item => item.y > 0);

                charts.push(Highcharts.chart('chartImpuestos', {
                    chart: { type: 'pie' },
                    title: { text: null },
                    tooltip: { pointFormat: '<b>${point.y:,.2f}</b> ({point.percentage:.1f}%)' },
                    colors: ['#2563eb', '#10b981', '#8b5cf6', '#38bdf8', '#f59e0b'],
                    series: [{
                        name: 'Monto',
                        colorByPoint: true,
                        data: impuestosPie.length ? impuestosPie : [{ name: 'Sin deducciones', y: 1 }]
                    }]
                }));
            }

            const collapseEl = document.getElementById('nominaChartsCollapse');
            if (collapseEl) {
                collapseEl.addEventListener('shown.bs.collapse', renderNominaCharts);
            }
        });
    </script>
    @endif

    <script>
    function confirmarEliminarNomina(btn) {
        const url = btn.getAttribute('data-delete-url');
        Swal.fire({
            title: '¿Está seguro?',
            text: '¿Desea eliminar esta nómina?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#475569',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    }
    </script>
@endsection
