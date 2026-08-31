@extends('layouts.app')
@section('content')
@php
    $apiAlumnos = url('/Gestion_alumnos/api/alumnos');
    $usuarioActual = auth()->user();
    $esUsuarioEmpresa = strtolower((string) optional($usuarioActual)->tipo) === 'empresa';
    $fotoPerfilEmpresa = optional($usuarioActual)->nombre_foto && file_exists(public_path('Images/Perfil/' . optional($usuarioActual)->nombre_foto))
        ? asset('Images/Perfil/' . optional($usuarioActual)->nombre_foto)
        : asset('Images/Perfil/0.png');
    $fotoPerfilDefault = asset('Images/Perfil/0.png');
@endphp

<style>
    .intranet-portal {
        --in-primary: #1e40af;
        --in-primary-light: #3b82f6;
        --in-accent: #0ea5e9;
        --in-surface: #ffffff;
        --in-muted: #64748b;
        --in-border: rgba(30, 64, 175, 0.12);
        --in-shadow: 0 18px 45px rgba(30, 64, 175, 0.12);
        --in-radius: 18px;
        font-family: "Poppins", sans-serif;
    }

    .intranet-hero {
        background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 45%, #0ea5e9 100%);
        border-radius: var(--in-radius);
        padding: 2rem 2.25rem;
        color: #fff;
        position: relative;
        overflow: hidden;
        box-shadow: var(--in-shadow);
    }

    .intranet-hero::before,
    .intranet-hero::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
    }

    .intranet-hero::before {
        width: 280px;
        height: 280px;
        top: -90px;
        right: -40px;
    }

    .intranet-hero::after {
        width: 160px;
        height: 160px;
        bottom: -50px;
        left: 12%;
    }

    .intranet-hero-content {
        position: relative;
        z-index: 1;
    }

    .intranet-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: rgba(255, 255, 255, 0.16);
        border: 1px solid rgba(255, 255, 255, 0.25);
        border-radius: 999px;
        padding: 0.35rem 0.85rem;
        font-size: 0.78rem;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }

    .intranet-stat {
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 14px;
        padding: 0.85rem 1rem;
        backdrop-filter: blur(6px);
    }

    .intranet-stat strong {
        display: block;
        font-size: 1.45rem;
        line-height: 1.1;
    }

    .intranet-stat span {
        font-size: 0.78rem;
        opacity: 0.9;
    }

    .empresa-header-profile {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .empresa-profile-photo {
        width: 82px;
        height: 82px;
        object-fit: cover;
        border-radius: 18px;
        border: 3px solid rgba(255, 255, 255, 0.35);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.18);
        background: rgba(255, 255, 255, 0.18);
        flex-shrink: 0;
    }

    .empresa-profile {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
    }

    .empresa-profile-item {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-radius: 999px;
        padding: 0.35rem 0.7rem;
        background: rgba(255, 255, 255, 0.13);
        font-size: 0.82rem;
    }

    .intranet-panel {
        background: var(--in-surface);
        border: 1px solid var(--in-border);
        border-radius: var(--in-radius);
        box-shadow: 0 8px 28px rgba(15, 23, 42, 0.06);
    }

    .intranet-panel-header {
        padding: 1.1rem 1.35rem;
        border-bottom: 1px solid var(--in-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .intranet-panel-body {
        padding: 1.25rem 1.35rem 1.4rem;
    }

    .aviso-card {
        border: 1px solid var(--in-border);
        border-radius: 14px;
        padding: 1rem 1.1rem;
        height: 100%;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        background: linear-gradient(180deg, #fff 0%, #f8fbff 100%);
    }

    .aviso-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 28px rgba(37, 99, 235, 0.14);
        border-color: rgba(37, 99, 235, 0.35);
    }

    .aviso-card.aviso-importante {
        border-left: 4px solid #f59e0b;
    }

    .aviso-tipo {
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        border-radius: 999px;
        padding: 0.2rem 0.55rem;
    }

    .aviso-tipo.info { background: #dbeafe; color: #1d4ed8; }
    .aviso-tipo.success { background: #dcfce7; color: #15803d; }
    .aviso-tipo.warning { background: #fef3c7; color: #b45309; }
    .aviso-tipo.event { background: #ede9fe; color: #6d28d9; }

    .intranet-quick-btn {
        border: 1px solid var(--in-border);
        border-radius: 14px;
        padding: 1rem;
        background: #fff;
        width: 100%;
        text-align: left;
        transition: all 0.2s ease;
    }

    .intranet-quick-btn:hover {
        border-color: var(--in-primary-light);
        box-shadow: 0 10px 24px rgba(37, 99, 235, 0.12);
        transform: translateY(-2px);
    }

    .intranet-quick-btn .icon-wrap {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        color: #fff;
    }

    .intranet-quick-btn.trabajadores .icon-wrap { background: linear-gradient(135deg, #7c3aed, #a855f7); }
    .intranet-quick-btn.conductores .icon-wrap { background: linear-gradient(135deg, #059669, #10b981); }

    .alumno-card {
        border: 1px solid var(--in-border);
        border-radius: 16px;
        padding: 1.1rem;
        background: #fff;
        height: 100%;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        cursor: pointer;
    }

    .alumno-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 16px 32px rgba(30, 64, 175, 0.12);
    }

    .alumnos-list {
        display: grid;
        gap: 0.75rem;
        max-height: 640px;
        overflow-y: auto;
        padding-right: 0.35rem;
    }

    .alumno-list-card {
        border: 1px solid var(--in-border);
        border-radius: 16px;
        padding: 0.95rem;
        background: #fff;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .alumno-list-card:hover,
    .alumno-list-card.is-active {
        border-color: rgba(37, 99, 235, 0.4);
        box-shadow: 0 12px 28px rgba(30, 64, 175, 0.12);
        transform: translateY(-2px);
    }

    .alumno-list-card.is-active {
        background: linear-gradient(180deg, #ffffff 0%, #eff6ff 100%);
    }

    .alumno-list-photo {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        object-fit: cover;
        border: 2px solid #dbeafe;
        flex-shrink: 0;
    }

    .alumno-avatar {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: #fff;
        background: linear-gradient(135deg, #2563eb, #0ea5e9);
        font-size: 1rem;
        flex-shrink: 0;
    }

    .alumno-meta {
        font-size: 0.82rem;
        color: var(--in-muted);
    }

    .alumno-detail-card {
        position: sticky;
        top: 1rem;
        border: 1px solid var(--in-border);
        border-radius: 18px;
        padding: 1.35rem;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        box-shadow: 0 14px 32px rgba(30, 64, 175, 0.08);
    }

    .alumno-detail-photo {
        width: 96px;
        height: 96px;
        border-radius: 22px;
        object-fit: cover;
        border: 3px solid #dbeafe;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
        flex-shrink: 0;
    }

    .chip {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        border-radius: 999px;
        padding: 0.2rem 0.55rem;
        font-size: 0.72rem;
        font-weight: 600;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .chip.semestre { background: #f0fdf4; color: #166534; }
    .chip.practica { background: #fff7ed; color: #c2410c; }

    .intranet-search {
        border-radius: 12px;
        border: 1px solid var(--in-border);
        padding: 0.65rem 0.9rem 0.65rem 2.4rem;
        background: #f8fafc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85zm-5.242 1.1a5 5 0 1 1 0-10 5 5 0 0 1 0 10z'/%3E%3C/svg%3E") no-repeat 0.75rem center / 1rem;
    }

    .intranet-search:focus {
        border-color: var(--in-primary-light);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }

    .intranet-empty {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--in-muted);
    }

    .intranet-empty i {
        font-size: 2.5rem;
        color: #cbd5e1;
        margin-bottom: 0.75rem;
    }

    .modal-intranet .modal-content {
        border: none;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.2);
    }

    .modal-intranet .modal-header {
        background: linear-gradient(135deg, #1e40af, #2563eb);
        color: #fff;
        border: none;
        padding: 1.25rem 1.5rem;
    }

    .modal-intranet .btn-close {
        filter: brightness(0) invert(1);
        opacity: 0.85;
    }

    .oferta-row,
    .conductor-row {
        border: 1px solid var(--in-border);
        border-radius: 12px;
        padding: 0.9rem 1rem;
        margin-bottom: 0.75rem;
        transition: background 0.15s ease;
    }

    .oferta-row:hover,
    .conductor-row:hover {
        background: #f8fafc;
    }

    .trabajador-disponibilidad {
        display: inline-flex;
        align-items: center;
        align-self: flex-start;
        border-radius: 999px;
        padding: 0.25rem 0.6rem;
        line-height: 1;
        font-size: 0.72rem;
        white-space: nowrap;
        background: #dcfce7;
        color: #15803d;
    }

    .detalle-grid dt {
        font-size: 0.78rem;
        color: var(--in-muted);
        margin-bottom: 0.15rem;
    }

    .detalle-grid dd {
        font-weight: 600;
        margin-bottom: 0.85rem;
    }

    @media (max-width: 767.98px) {
        .intranet-hero { padding: 1.35rem; }
    }
</style>

<div class="container-fluid format_page intranet-portal py-2">

    <div class="intranet-hero mb-4">
        <div class="intranet-hero-content">
            <div class="row align-items-center g-3">
                <div class="col-lg-7">
                    <span class="intranet-badge mb-3">
                        <i class="fa-solid fa-building-shield"></i> Portal empresarial COPARMEX
                    </span>
                    <h2 class="fw-bold mb-2">Intranet de Vinculación</h2>
                    @if ($esUsuarioEmpresa)
                        <div class="empresa-header-profile mb-3">
                            <img src="{{ $fotoPerfilEmpresa }}" alt="Imagen de perfil de la empresa" class="empresa-profile-photo" onerror="this.onerror=null;this.src='{{ $fotoPerfilDefault }}';">
                            <div>
                                <h5 class="fw-semibold mb-2">{{ optional($empresaPerfil)->nombre ?: 'Empresa sin perfil vinculado' }}</h5>
                                <div class="empresa-profile">
                                    @if (optional($empresaPerfil)->razon_social)
                                        <span class="empresa-profile-item"><i class="fa-solid fa-id-card"></i>{{ $empresaPerfil->razon_social }}</span>
                                    @endif
                                    @if (optional($empresaPerfil)->rfc)
                                        <span class="empresa-profile-item"><i class="fa-solid fa-file-invoice"></i>RFC: {{ $empresaPerfil->rfc }}</span>
                                    @endif
                                    @if (optional($empresaPerfil)->giro_empresarial)
                                        <span class="empresa-profile-item"><i class="fa-solid fa-industry"></i>{{ $empresaPerfil->giro_empresarial }}</span>
                                    @endif
                                    @if (optional($empresaPerfil)->municipio)
                                        <span class="empresa-profile-item"><i class="fa-solid fa-location-dot"></i>{{ $empresaPerfil->municipio }}</span>
                                    @endif
                                    @if (optional($empresaPerfil)->telefonos)
                                        <span class="empresa-profile-item"><i class="fa-solid fa-phone"></i>{{ $empresaPerfil->telefonos }}</span>
                                    @endif
                                    @if (optional($empresaPerfil)->correos)
                                        <span class="empresa-profile-item"><i class="fa-solid fa-envelope"></i>{{ $empresaPerfil->correos }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <h5 class="fw-semibold mb-2">Visor General</h5>
                    @endif
                    <p class="mb-0 opacity-90 pe-lg-4">
                        Consulta avisos, alumnos disponibles para prácticas profesionales, ofertas de trabajadores y conductores vinculados a tu empresa.
                    </p>
                </div>
                <div class="col-lg-5">
                    <div class="row g-2">
                        <div class="col-4">
                            <div class="intranet-stat text-center">
                                <strong id="statAlumnos">—</strong>
                                <span>Alumnos</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="intranet-stat text-center">
                                <strong id="statPracticas">—</strong>
                                <span>Prácticas</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="intranet-stat text-center">
                                <strong id="statAvisos">5</strong>
                                <span>Avisos</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="intranet-panel h-100">
                <div class="intranet-panel-header">
                    <div>
                        <h5 class="mb-0 fw-semibold"><i class="fa-solid fa-bullhorn text-primary me-2"></i>Avisos recientes</h5>
                        <small class="text-muted">Comunicados oficiales para empresas aliadas</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalTodosAvisos">
                        Ver todos
                    </button>
                </div>
                <div class="intranet-panel-body">
                    <div class="row g-3" id="avisosDestacados"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="intranet-panel h-100">
                <div class="intranet-panel-header">
                    <div>
                        <h5 class="mb-0 fw-semibold"><i class="fa-solid fa-layer-group text-primary me-2"></i>Accesos rápidos</h5>
                        <small class="text-muted">Otras listas de tu portal</small>
                    </div>
                </div>
                <div class="intranet-panel-body d-grid gap-3">
                    <button type="button" class="intranet-quick-btn trabajadores" data-bs-toggle="modal" data-bs-target="#modalTrabajadores">
                        <div class="d-flex align-items-center gap-3">
                            <span class="icon-wrap"><i class="fa-solid fa-briefcase"></i></span>
                            <div>
                                <div class="fw-semibold">Bolsa de trabajadores</div>
                                <small class="text-muted d-block">Perfiles disponibles para contratación</small>
                            </div>
                        </div>
                    </button>
                    <button type="button" class="intranet-quick-btn conductores" data-bs-toggle="modal" data-bs-target="#modalConductores">
                        <div class="d-flex align-items-center gap-3">
                            <span class="icon-wrap"><i class="fa-solid fa-truck-fast"></i></span>
                            <div>
                                <div class="fw-semibold">Conductores</div>
                                <small class="text-muted d-block">Personal certificado para logística</small>
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="intranet-panel">
        <div class="intranet-panel-header">
            <div>
                <h5 class="mb-0 fw-semibold"><i class="fa-solid fa-user-graduate text-primary me-2"></i>Alumnos para prácticas profesionales</h5>
                <small class="text-muted">Vista principal · Datos desde el catálogo de alumnos</small>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <input type="search" class="form-control intranet-search" id="buscadorAlumnosIntranet" placeholder="Buscar por nombre, matrícula o especialidad…" style="min-width: 260px;">
                <select class="form-select form-select-sm" id="filtroSemestreIntranet" style="width: auto; border-radius: 10px;">
                    <option value="">Todos los semestres</option>
                </select>
            </div>
        </div>
        <div class="intranet-panel-body">
            <div id="alumnosLoading" class="text-center py-5 text-muted">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 mb-0">Cargando alumnos…</p>
            </div>
            <div id="alumnosError" class="alert alert-danger d-none" role="alert"></div>
            <div class="row g-3 d-none" id="alumnosListadoLayout">
                <div class="col-xl-5 col-lg-6">
                    <div class="alumnos-list" id="listaAlumnosIntranet"></div>
                </div>
                <div class="col-xl-7 col-lg-6">
                    <article class="alumno-detail-card" id="panelDetalleAlumno">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <img src="{{ asset('Images/Perfil/0.png') }}" alt="Foto del alumno" class="alumno-detail-photo" id="panelAlumnoFoto" onerror="this.onerror=null;this.src='{{ asset('Images/Perfil/0.png') }}';">
                            <div>
                                <h4 class="mb-1" id="panelAlumnoNombre">Selecciona un alumno</h4>
                                <span class="chip practica" id="panelAlumnoEstado">Disponible para prácticas</span>
                            </div>
                        </div>
                        <dl class="row detalle-grid mb-0">
                            <div class="col-md-6">
                                <dt>Matrícula</dt>
                                <dd id="panelAlumnoMatricula">—</dd>
                            </div>
                            <div class="col-md-6">
                                <dt>Semestre</dt>
                                <dd id="panelAlumnoSemestre">—</dd>
                            </div>
                            <div class="col-md-6">
                                <dt>Especialidad</dt>
                                <dd id="panelAlumnoEspecialidad">—</dd>
                            </div>
                            <div class="col-md-6">
                                <dt>Escuela</dt>
                                <dd id="panelAlumnoEscuela">—</dd>
                            </div>
                            <div class="col-md-6">
                                <dt>Correo</dt>
                                <dd id="panelAlumnoCorreo">—</dd>
                            </div>
                            <div class="col-md-6">
                                <dt>Teléfono</dt>
                                <dd id="panelAlumnoTelefono">—</dd>
                            </div>
                            <div class="col-12">
                                <dt>Empresa vinculada</dt>
                                <dd id="panelAlumnoEmpresa">—</dd>
                            </div>
                        </dl>
                    </article>
                </div>
            </div>
            <div id="alumnosVacio" class="intranet-empty d-none">
                <i class="fa-solid fa-user-slash d-block"></i>
                <p class="mb-0">No hay alumnos que coincidan con tu búsqueda.</p>
            </div>
        </div>
    </div>
</div>

{{-- Modal: detalle alumno --}}
<div class="modal fade modal-intranet" id="modalDetalleAlumno" tabindex="-1" aria-labelledby="modalDetalleAlumnoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetalleAlumnoLabel"><i class="fa-solid fa-id-card me-2"></i>Perfil del alumno</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <span class="alumno-avatar" id="detalleAlumnoAvatar">—</span>
                    <div>
                        <h4 class="mb-1" id="detalleAlumnoNombre">—</h4>
                        <span class="chip practica" id="detalleAlumnoEstado">Disponible para prácticas</span>
                    </div>
                </div>
                <dl class="row detalle-grid mb-0">
                    <div class="col-md-6">
                        <dt>Matrícula</dt>
                        <dd id="detalleAlumnoMatricula">—</dd>
                    </div>
                    <div class="col-md-6">
                        <dt>Semestre</dt>
                        <dd id="detalleAlumnoSemestre">—</dd>
                    </div>
                    <div class="col-md-6">
                        <dt>Especialidad</dt>
                        <dd id="detalleAlumnoEspecialidad">—</dd>
                    </div>
                    <div class="col-md-6">
                        <dt>Escuela</dt>
                        <dd id="detalleAlumnoEscuela">—</dd>
                    </div>
                    <div class="col-md-6">
                        <dt>Correo</dt>
                        <dd id="detalleAlumnoCorreo">—</dd>
                    </div>
                    <div class="col-md-6">
                        <dt>Teléfono</dt>
                        <dd id="detalleAlumnoTelefono">—</dd>
                    </div>
                    <div class="col-12">
                        <dt>Empresa vinculada</dt>
                        <dd id="detalleAlumnoEmpresa">—</dd>
                    </div>
                </dl>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-baseColor" disabled title="Próximamente">
                    <i class="fa-solid fa-handshake"></i> Solicitar práctica
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: todos los avisos --}}
<div class="modal fade modal-intranet" id="modalTodosAvisos" tabindex="-1" aria-labelledby="modalTodosAvisosLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTodosAvisosLabel"><i class="fa-solid fa-bullhorn me-2"></i>Centro de avisos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4" id="listaTodosAvisos"></div>
        </div>
    </div>
</div>

{{-- Modal: trabajadores --}}
<div class="modal fade modal-intranet" id="modalTrabajadores" tabindex="-1" aria-labelledby="modalTrabajadoresLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTrabajadoresLabel"><i class="fa-solid fa-briefcase me-2"></i>Bolsa de trabajadores</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">Perfiles referenciados para cubrir vacantes en empresas aliadas. Datos de demostración.</p>
                <div id="listaTrabajadores"></div>
            </div>
        </div>
    </div>
</div>

{{-- Modal: conductores --}}
<div class="modal fade modal-intranet" id="modalConductores" tabindex="-1" aria-labelledby="modalConductoresLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConductoresLabel"><i class="fa-solid fa-truck-fast me-2"></i>Conductores disponibles</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">Personal con licencia vigente para rutas y logística. Datos de demostración.</p>
                <div id="listaConductores"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const apiAlumnos = @json($apiAlumnos);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const fotoAlumnoDefault = @json(asset('Images/Perfil/0.png'));
    const fotoPerfilBase = @json(asset('Images/Perfil'));

    const avisos = [
        {
            id: 1,
            titulo: 'Apertura de periodo de prácticas 2026-B',
            fecha: '15 may 2026',
            tipo: 'info',
            importante: true,
            resumen: 'Del 20 de mayo al 10 de junio podrás revisar perfiles de alumnos certificados para iniciar prácticas en tu planta.',
            contenido: 'Se habilita el módulo de consulta de alumnos. Recuerda validar documentación con tu asesor COPARMEX antes de formalizar la carta compromiso.'
        },
        {
            id: 2,
            titulo: 'Jornada de vinculación empresarial',
            fecha: '10 may 2026',
            tipo: 'event',
            importante: false,
            resumen: 'Invitación al evento presencial el 28 de mayo en sede COPARMEX. Cupo limitado para empresas socias.',
            contenido: 'Confirma asistencia con tu representante. Incluye mesas de trabajo con escuelas participantes y muestra de perfiles técnicos.'
        },
        {
            id: 3,
            titulo: 'Actualización de requisitos documentales',
            fecha: '05 may 2026',
            tipo: 'warning',
            importante: true,
            resumen: 'A partir del 1 de junio será obligatorio el comprobante de seguro escolar vigente para altas de prácticas.',
            contenido: 'Los expedientes incompletos no podrán avanzar a convenio. Revisa el checklist en tu panel de documentación.'
        },
        {
            id: 4,
            titulo: 'Nuevos perfiles en bolsa de trabajadores',
            fecha: '28 abr 2026',
            tipo: 'success',
            importante: false,
            resumen: 'Se incorporaron 12 candidatos en áreas de producción, mantenimiento y administración.',
            contenido: 'Consulta la bolsa desde accesos rápidos. Los perfiles incluyen años de experiencia y disponibilidad inmediata.'
        },
        {
            id: 5,
            titulo: 'Recordatorio: evaluación de desempeño',
            fecha: '20 abr 2026',
            tipo: 'info',
            importante: false,
            resumen: 'Empresas con alumnos en práctica activa deben capturar la evaluación mensual antes del día 25.',
            contenido: 'El formato estará disponible en el módulo de seguimiento. El incumplimiento puede retrasar la facturación del servicio dual.'
        }
    ];

    const trabajadores = [
        { nombre: 'Roberto Méndez López', puesto: 'Técnico en mantenimiento industrial', exp: '4 años', zona: 'Zona metropolitana', disponibilidad: 'Inmediata' },
        { nombre: 'Laura Patricia Soto', puesto: 'Auxiliar administrativo bilingüe', exp: '2 años', zona: 'Querétaro', disponibilidad: '15 días' },
        { nombre: 'Miguel Ángel Ríos', puesto: 'Operador de producción', exp: '6 años', zona: 'León, Gto.', disponibilidad: 'Inmediata' },
        { nombre: 'Diana Guadalupe Núñez', puesto: 'Contadora junior', exp: '1 año', zona: 'CDMX', disponibilidad: 'A convenir' },
        { nombre: 'Fernando Isaac Delgado', puesto: 'Soldador certificado', exp: '8 años', zona: 'Monterrey', disponibilidad: 'Inmediata' },
        { nombre: 'Verónica Estrella Camacho', puesto: 'Recursos humanos', exp: '5 años', zona: 'Puebla', disponibilidad: '30 días' }
    ];

    const conductores = [
        { nombre: 'Jorge Luis Paredes', licencia: 'Tipo E vigente', vehiculo: 'Tractocamión', antiguedad: '9 años', disponibilidad: 'Tiempo completo' },
        { nombre: 'Héctor Manuel Villar', licencia: 'Tipo C vigente', vehiculo: 'Camión 3.5 ton', antiguedad: '5 años', disponibilidad: 'Matutino' },
        { nombre: 'Ricardo Omar Salinas', licencia: 'Tipo E + hazmat', vehiculo: 'Pipa', antiguedad: '12 años', disponibilidad: 'Rotativo' },
        { nombre: 'Oswaldo Martínez Cruz', licencia: 'Tipo B vigente', vehiculo: 'Van ejecutiva', antiguedad: '3 años', disponibilidad: 'Medio tiempo' },
        { nombre: 'Arturo Enrique Fuentes', licencia: 'Tipo E vigente', vehiculo: 'Caja seca 53 pies', antiguedad: '7 años', disponibilidad: 'Inmediata' }
    ];

    let alumnosData = [];
    let alumnoSeleccionadoId = null;

    const escapeHtml = function (text) {
        if (text === null || text === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(text);
        return d.innerHTML;
    };

    const nombreCompleto = function (a) {
        return [a.nombres, a.apellido_paterno, a.apellido_materno].filter(Boolean).join(' ').replace(/\s+/g, ' ').trim();
    };

    const iniciales = function (nombre) {
        const partes = String(nombre || '').trim().split(/\s+/).filter(Boolean);
        if (!partes.length) return '?';
        if (partes.length === 1) return partes[0].substring(0, 2).toUpperCase();
        return (partes[0][0] + partes[partes.length - 1][0]).toUpperCase();
    };

    const resolverFotoAlumno = function (a) {
        const raw = a?.foto_url || a?.foto_perfil || a?.imagen || a?.avatar || a?.foto || a?.nombre_foto || '';
        const foto = String(raw || '').trim();
        if (!foto) return fotoAlumnoDefault;
        if (/^(https?:)?\/\//.test(foto) || foto.startsWith('/')) return foto;
        if (!foto.includes('/')) return fotoPerfilBase.replace(/\/$/, '') + '/' + encodeURIComponent(foto);
        return '/' + foto.replace(/^\/+/, '');
    };

    const renderAvisosDestacados = function () {
        const cont = document.getElementById('avisosDestacados');
        if (!cont) return;
        cont.innerHTML = '';
        avisos.slice(0, 3).forEach(function (av) {
            const col = document.createElement('div');
            col.className = 'col-md-4';
            col.innerHTML =
                '<article class="aviso-card' + (av.importante ? ' aviso-importante' : '') + '" data-aviso-id="' + av.id + '">' +
                    '<div class="d-flex justify-content-between align-items-start mb-2">' +
                        '<span class="aviso-tipo ' + escapeHtml(av.tipo) + '">' + escapeHtml(av.tipo) + '</span>' +
                        (av.importante ? '<i class="fa-solid fa-star text-warning" title="Importante"></i>' : '') +
                    '</div>' +
                    '<h6 class="fw-semibold mb-1">' + escapeHtml(av.titulo) + '</h6>' +
                    '<small class="text-muted d-block mb-2"><i class="fa-regular fa-calendar me-1"></i>' + escapeHtml(av.fecha) + '</small>' +
                    '<p class="small text-muted mb-0">' + escapeHtml(av.resumen) + '</p>' +
                '</article>';
            col.querySelector('.aviso-card').addEventListener('click', function () {
                const modal = new bootstrap.Modal(document.getElementById('modalTodosAvisos'));
                modal.show();
            });
            cont.appendChild(col);
        });
    };

    const renderTodosAvisos = function () {
        const cont = document.getElementById('listaTodosAvisos');
        if (!cont) return;
        cont.innerHTML = avisos.map(function (av) {
            return '<div class="border rounded-3 p-3 mb-3' + (av.importante ? ' border-warning border-2' : '') + '">' +
                '<div class="d-flex justify-content-between flex-wrap gap-2 mb-2">' +
                    '<span class="aviso-tipo ' + escapeHtml(av.tipo) + '">' + escapeHtml(av.tipo) + '</span>' +
                    '<small class="text-muted">' + escapeHtml(av.fecha) + '</small>' +
                '</div>' +
                '<h6 class="fw-semibold">' + escapeHtml(av.titulo) + '</h6>' +
                '<p class="text-muted small mb-0">' + escapeHtml(av.contenido) + '</p>' +
            '</div>';
        }).join('');
    };

    const renderTrabajadores = function () {
        const cont = document.getElementById('listaTrabajadores');
        if (!cont) return;
        cont.innerHTML = trabajadores.map(function (t) {
            return '<div class="oferta-row">' +
                '<div class="d-flex justify-content-between align-items-start flex-wrap gap-2">' +
                    '<div><strong>' + escapeHtml(t.nombre) + '</strong><br><span class="text-primary small">' + escapeHtml(t.puesto) + '</span></div>' +
                    '<span class="badge badge-success trabajador-disponibilidad">' + escapeHtml(t.disponibilidad) + '</span>' +
                '</div>' +
                '<div class="small text-muted mt-2"><i class="fa-solid fa-clock me-1"></i>' + escapeHtml(t.exp) +
                ' · <i class="fa-solid fa-location-dot me-1"></i>' + escapeHtml(t.zona) + '</div>' +
            '</div>';
        }).join('');
    };

    const renderConductores = function () {
        const cont = document.getElementById('listaConductores');
        if (!cont) return;
        cont.innerHTML = conductores.map(function (c) {
            return '<div class="conductor-row">' +
                '<div class="d-flex justify-content-between flex-wrap gap-2">' +
                    '<strong>' + escapeHtml(c.nombre) + '</strong>' +
                    '<span class="chip">' + escapeHtml(c.disponibilidad) + '</span>' +
                '</div>' +
                '<div class="small text-muted mt-2">' +
                    '<i class="fa-solid fa-id-card me-1"></i>' + escapeHtml(c.licencia) +
                    ' · <i class="fa-solid fa-truck me-1"></i>' + escapeHtml(c.vehiculo) +
                    ' · <i class="fa-solid fa-road me-1"></i>' + escapeHtml(c.antiguedad) +
                '</div>' +
            '</div>';
        }).join('');
    };

    const actualizarStats = function (lista) {
        const total = lista.length;
        const practicas = lista.filter(function (a) {
            const est = String(a.estado || '').toLowerCase();
            return !est || est.includes('activ') || est.includes('dispon') || est.includes('pract');
        }).length;
        document.getElementById('statAlumnos').textContent = total;
        document.getElementById('statPracticas').textContent = practicas || total;
    };

    const llenarFiltroSemestre = function (lista) {
        const sel = document.getElementById('filtroSemestreIntranet');
        if (!sel) return;
        const semestres = [...new Set(lista.map(function (a) { return a.semestre; }).filter(function (s) { return s !== null && s !== undefined && s !== ''; }))].sort(function (a, b) { return Number(a) - Number(b); });
        semestres.forEach(function (s) {
            const opt = document.createElement('option');
            opt.value = String(s);
            opt.textContent = 'Semestre ' + s;
            sel.appendChild(opt);
        });
    };

    const renderDetalleAlumno = function (a) {
        const fotoEl = document.getElementById('panelAlumnoFoto');
        if (!a) {
            if (fotoEl) fotoEl.src = fotoAlumnoDefault;
            document.getElementById('panelAlumnoNombre').textContent = 'Selecciona un alumno';
            document.getElementById('panelAlumnoMatricula').textContent = '—';
            document.getElementById('panelAlumnoSemestre').textContent = '—';
            document.getElementById('panelAlumnoEspecialidad').textContent = '—';
            document.getElementById('panelAlumnoEscuela').textContent = '—';
            document.getElementById('panelAlumnoCorreo').textContent = '—';
            document.getElementById('panelAlumnoTelefono').textContent = '—';
            document.getElementById('panelAlumnoEmpresa').textContent = '—';
            document.getElementById('panelAlumnoEstado').textContent = 'Disponible para prácticas';
            return;
        }

        const nombre = nombreCompleto(a);
        if (fotoEl) fotoEl.src = resolverFotoAlumno(a);
        document.getElementById('panelAlumnoNombre').textContent = nombre || '—';
        document.getElementById('panelAlumnoMatricula').textContent = a.numero_matricula ?? '—';
        document.getElementById('panelAlumnoSemestre').textContent = a.semestre != null ? ('Semestre ' + a.semestre) : '—';
        document.getElementById('panelAlumnoEspecialidad').textContent = (a.especialidad && a.especialidad.nombre_especialidad) ? a.especialidad.nombre_especialidad : '—';
        document.getElementById('panelAlumnoEscuela').textContent = (a.escuela && a.escuela.nombre) ? a.escuela.nombre : '—';
        document.getElementById('panelAlumnoCorreo').textContent = a.correo || '—';
        document.getElementById('panelAlumnoTelefono').textContent = a.telefono || '—';
        document.getElementById('panelAlumnoEmpresa').textContent = (a.empresa && a.empresa.nombre) ? a.empresa.nombre : 'Sin asignar';
        const est = String(a.estado || 'Disponible para prácticas').trim();
        document.getElementById('panelAlumnoEstado').textContent = est || 'Disponible para prácticas';
    };

    const marcarAlumnoActivo = function () {
        document.querySelectorAll('.alumno-list-card').forEach(function (card) {
            card.classList.toggle('is-active', Number(card.dataset.alumnoId) === Number(alumnoSeleccionadoId));
        });
    };

    const seleccionarAlumno = function (a) {
        alumnoSeleccionadoId = Number(a.id);
        renderDetalleAlumno(a);
        marcarAlumnoActivo();
    };

    const renderListaAlumnos = function (lista) {
        const layout = document.getElementById('alumnosListadoLayout');
        const cont = document.getElementById('listaAlumnosIntranet');
        const vacio = document.getElementById('alumnosVacio');
        if (!layout || !cont || !vacio) return;

        cont.innerHTML = '';
        if (!lista.length) {
            layout.classList.add('d-none');
            vacio.classList.remove('d-none');
            alumnoSeleccionadoId = null;
            renderDetalleAlumno(null);
            return;
        }

        vacio.classList.add('d-none');
        layout.classList.remove('d-none');

        const ids = lista.map(function (a) { return Number(a.id); });
        if (!alumnoSeleccionadoId || !ids.includes(Number(alumnoSeleccionadoId))) {
            alumnoSeleccionadoId = Number(lista[0].id);
        }

        lista.forEach(function (a) {
            const nombre = nombreCompleto(a);
            const esp = (a.especialidad && a.especialidad.nombre_especialidad) ? a.especialidad.nombre_especialidad : 'Sin especialidad';
            const esc = (a.escuela && a.escuela.nombre) ? a.escuela.nombre : 'Escuela no registrada';
            const item = document.createElement('article');
            item.className = 'alumno-list-card' + (Number(a.id) === Number(alumnoSeleccionadoId) ? ' is-active' : '');
            item.tabIndex = 0;
            item.setAttribute('role', 'button');
            item.dataset.alumnoId = String(a.id);
            item.innerHTML =
                '<div class="d-flex gap-3 align-items-start">' +
                    '<img class="alumno-list-photo" src="' + escapeHtml(resolverFotoAlumno(a)) + '" alt="Foto de ' + escapeHtml(nombre || 'alumno') + '" onerror="this.onerror=null;this.src=\'' + escapeHtml(fotoAlumnoDefault) + '\';">' +
                    '<div class="min-w-0 flex-grow-1">' +
                        '<div class="d-flex justify-content-between gap-2 flex-wrap">' +
                            '<h6 class="fw-semibold mb-1 text-truncate" title="' + escapeHtml(nombre) + '">' + escapeHtml(nombre || '—') + '</h6>' +
                            (a.semestre != null ? '<span class="chip semestre">Sem. ' + escapeHtml(a.semestre) + '</span>' : '') +
                        '</div>' +
                        '<p class="alumno-meta mb-2 text-truncate" title="' + escapeHtml(esp) + '">' + escapeHtml(esp) + '</p>' +
                        '<div class="d-flex flex-wrap gap-1 mb-2">' +
                            '<span class="chip"><i class="fa-solid fa-hashtag"></i> ' + escapeHtml(a.numero_matricula ?? '—') + '</span>' +
                            '<span class="chip practica"><i class="fa-solid fa-briefcase"></i> Prácticas</span>' +
                        '</div>' +
                        '<p class="small text-muted mb-0 text-truncate" title="' + escapeHtml(esc) + '"><i class="fa-solid fa-school me-1"></i>' + escapeHtml(esc) + '</p>' +
                    '</div>' +
                '</div>';
            item.addEventListener('click', function () { seleccionarAlumno(a); });
            item.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    seleccionarAlumno(a);
                }
            });
            cont.appendChild(item);
        });

        const seleccionado = lista.find(function (a) { return Number(a.id) === Number(alumnoSeleccionadoId); }) || lista[0];
        renderDetalleAlumno(seleccionado);
        marcarAlumnoActivo();
    };

    const filtrarAlumnos = function () {
        const q = (document.getElementById('buscadorAlumnosIntranet')?.value || '').trim().toLowerCase();
        const sem = document.getElementById('filtroSemestreIntranet')?.value || '';
        const filtrados = alumnosData.filter(function (a) {
            const nombre = nombreCompleto(a).toLowerCase();
            const esp = ((a.especialidad && a.especialidad.nombre_especialidad) || '').toLowerCase();
            const mat = String(a.numero_matricula || '');
            const matchQ = !q || nombre.includes(q) || esp.includes(q) || mat.includes(q);
            const matchSem = !sem || String(a.semestre) === sem;
            return matchQ && matchSem;
        });
        renderListaAlumnos(filtrados);
    };

    const cargarAlumnos = async function () {
        const loading = document.getElementById('alumnosLoading');
        const errBox = document.getElementById('alumnosError');
        try {
            const r = await fetch(apiAlumnos, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {})
                },
                credentials: 'same-origin'
            });
            const j = await r.json();
            if (!r.ok) {
                throw new Error((j && j.message) ? j.message : 'No se pudieron cargar los alumnos.');
            }
            alumnosData = Array.isArray(j.data) ? j.data : [];
            actualizarStats(alumnosData);
            llenarFiltroSemestre(alumnosData);
            renderListaAlumnos(alumnosData);
        } catch (e) {
            if (errBox) {
                errBox.textContent = e.message || 'Error al cargar alumnos.';
                errBox.classList.remove('d-none');
            }
        } finally {
            if (loading) loading.classList.add('d-none');
        }
    };

    renderAvisosDestacados();
    renderTodosAvisos();
    renderTrabajadores();
    renderConductores();
    cargarAlumnos();

    document.getElementById('buscadorAlumnosIntranet')?.addEventListener('input', filtrarAlumnos);
    document.getElementById('filtroSemestreIntranet')?.addEventListener('change', filtrarAlumnos);
});
</script>
@endsection
