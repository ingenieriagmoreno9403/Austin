@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<style>
    .inventario-page .vista-toggle .btn {
        min-width: 2.5rem;
    }
    .inventario-page .vista-toggle .btn.active {
        background-color: #102d49;
        border-color: #102d49;
        color: #fff;
    }
    .inventario-page .alm-card {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.1rem 1.15rem;
        height: 100%;
        display: flex;
        flex-direction: column;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .inventario-page .alm-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
    }
    .inventario-page .alm-card-link {
        text-decoration: none;
        color: inherit;
        flex: 1;
    }
    .inventario-page .alm-card-link.pointer_blocked {
        pointer-events: none;
        opacity: 0.85;
    }
    .inventario-page .alm-card-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #102d49;
        margin: 0;
        line-height: 1.3;
    }
    .inventario-page .alm-card-meta {
        font-size: 0.78rem;
        color: #6b7280;
        margin-top: 0.15rem;
    }
    .inventario-page .alm-card-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .inventario-page .alm-card-contact {
        font-size: 0.8rem;
        color: #4b5563;
        margin-top: 0.85rem;
    }
    .inventario-page .alm-card-contact .label {
        display: block;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        color: #9ca3af;
        margin-bottom: 0.1rem;
    }
    .inventario-page .alm-card-contact .row-item {
        margin-bottom: 0.55rem;
    }
    .inventario-page .alm-card-contact .row-item:last-child {
        margin-bottom: 0;
    }
    .inventario-page .alm-card-actions {
        border-top: 1px solid #e5e7eb;
        margin-top: 0.9rem;
        padding-top: 0.75rem;
        display: flex;
        justify-content: flex-end;
        gap: 0.35rem;
    }
    .inventario-page .prod-search {
        position: relative;
        overflow: hidden;
        background: linear-gradient(145deg, #ffffff 0%, #f4f7fb 100%);
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 1.25rem 1.35rem 1.15rem;
        box-shadow: 0 10px 28px rgba(16, 45, 73, 0.06);
    }
    .inventario-page .prod-search::before {
        content: '';
        position: absolute;
        inset: 0 auto 0 0;
        width: 4px;
        background: linear-gradient(180deg, #102d49 0%, #e67e22 100%);
    }
    .inventario-page .prod-search__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .inventario-page .prod-search__title-wrap {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        min-width: 0;
    }
    .inventario-page .prod-search__icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: linear-gradient(145deg, #102d49, #1a466d);
        color: #fff;
        box-shadow: 0 8px 18px rgba(16, 45, 73, 0.22);
    }
    .inventario-page .prod-search__title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        color: #102d49;
        line-height: 1.25;
    }
    .inventario-page .prod-search__subtitle {
        margin: 0.15rem 0 0;
        font-size: 0.82rem;
        color: #6b7280;
    }
    .inventario-page .prod-search__badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.7rem;
        border-radius: 999px;
        background: #eef4fa;
        color: #102d49;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .inventario-page .prod-search__field {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        background: #fff;
        border: 1px solid #d7dee8;
        border-radius: 14px;
        padding: 0.35rem 0.4rem 0.35rem 0.95rem;
        transition: border-color 0.18s ease, box-shadow 0.18s ease;
    }
    .inventario-page .prod-search__field:focus-within {
        border-color: #102d49;
        box-shadow: 0 0 0 4px rgba(16, 45, 73, 0.1);
    }
    .inventario-page .prod-search__field-icon {
        color: #94a3b8;
        font-size: 0.95rem;
        flex-shrink: 0;
    }
    .inventario-page .prod-search__input {
        border: 0;
        outline: none;
        box-shadow: none !important;
        background: transparent;
        flex: 1;
        min-width: 0;
        height: 42px;
        font-size: 0.95rem;
        color: #1f2937;
    }
    .inventario-page .prod-search__input::placeholder {
        color: #9ca3af;
    }
    .inventario-page .prod-search__btn {
        border: 0;
        border-radius: 10px;
        background: #102d49;
        color: #fff;
        font-weight: 600;
        font-size: 0.88rem;
        padding: 0.55rem 1rem;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        transition: background 0.15s ease, transform 0.15s ease;
        white-space: nowrap;
    }
    .inventario-page .prod-search__btn:hover {
        background: #163a5c;
        color: #fff;
    }
    .inventario-page .prod-search__btn:active {
        transform: translateY(1px);
    }
    .inventario-page .prod-search__meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-top: 0.75rem;
        min-height: 1.25rem;
    }
    .inventario-page .prod-search__status {
        font-size: 0.8rem;
        color: #6b7280;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }
    .inventario-page .prod-search__status.is-ok { color: #047857; }
    .inventario-page .prod-search__status.is-warn { color: #b45309; }
    .inventario-page .prod-search__status.is-error { color: #b91c1c; }
    .inventario-page .prod-search__status .spinner-border {
        width: 0.85rem;
        height: 0.85rem;
        border-width: 0.15em;
    }
    .inventario-page .prod-search__hint {
        font-size: 0.75rem;
        color: #94a3b8;
    }
    .inventario-page .prod-search__results {
        margin-top: 1rem;
        display: none;
        max-height: 420px;
        overflow: auto;
        padding-right: 0.15rem;
    }
    .inventario-page .prod-search__results.is-open {
        display: grid;
        gap: 0.65rem;
    }
    .inventario-page .prod-result {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 0.85rem;
        align-items: center;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 0.85rem 0.95rem;
        cursor: pointer;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
        animation: prodSearchIn 0.25s ease both;
    }
    .inventario-page .prod-result:hover,
    .inventario-page .prod-result:focus-visible {
        border-color: #102d49;
        box-shadow: 0 8px 20px rgba(16, 45, 73, 0.08);
        transform: translateY(-1px);
        outline: none;
    }
    .inventario-page .prod-result.is-selected {
        border-color: #102d49;
        background: #f3f7fb;
    }
    .inventario-page .prod-result__name {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: #102d49;
        line-height: 1.3;
    }
    .inventario-page .prod-result__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem 0.75rem;
        margin-top: 0.4rem;
        font-size: 0.78rem;
        color: #64748b;
    }
    .inventario-page .prod-result__chip {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.18rem 0.5rem;
        border-radius: 8px;
        background: #f1f5f9;
        color: #334155;
        font-weight: 600;
    }
    .inventario-page .prod-result__chip i {
        color: #102d49;
        font-size: 0.72rem;
    }
    .inventario-page .prod-result__side {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.45rem;
        flex-shrink: 0;
    }
    .inventario-page .prod-result__qty {
        font-size: 0.95rem;
        font-weight: 800;
        color: #047857;
        line-height: 1;
        white-space: nowrap;
    }
    .inventario-page .prod-result__qty small {
        display: block;
        margin-top: 0.15rem;
        font-size: 0.68rem;
        font-weight: 600;
        color: #64748b;
        text-align: right;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .inventario-page .prod-result__go {
        border: 0;
        border-radius: 9px;
        background: #102d49;
        color: #fff;
        font-size: 0.78rem;
        font-weight: 600;
        padding: 0.4rem 0.7rem;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        transition: background 0.15s ease;
    }
    .inventario-page .prod-result__go:hover {
        background: #163a5c;
        color: #fff;
    }
    .inventario-page .prod-search__empty {
        display: none;
        text-align: center;
        padding: 1.4rem 1rem 0.6rem;
        color: #94a3b8;
    }
    .inventario-page .prod-search__empty.is-open {
        display: block;
    }
    .inventario-page .prod-search__empty i {
        font-size: 1.5rem;
        opacity: 0.55;
        margin-bottom: 0.4rem;
    }
    @keyframes prodSearchIn {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @media (max-width: 767.98px) {
        .inventario-page .prod-search__header {
            flex-direction: column;
        }
        .inventario-page .prod-search__field {
            flex-wrap: wrap;
            padding: 0.55rem;
        }
        .inventario-page .prod-search__input {
            width: 100%;
            flex: 1 1 100%;
            padding-left: 0.35rem;
        }
        .inventario-page .prod-search__btn {
            width: 100%;
            justify-content: center;
        }
        .inventario-page .prod-result {
            grid-template-columns: 1fr;
        }
        .inventario-page .prod-result__side {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }
        .inventario-page .prod-result__qty small {
            text-align: left;
        }
    }
</style>

<div class="container-fluid format_page inventario-page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header flex-wrap gap-3">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-warehouse"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Almacenes</h2>
                        <p class="text-muted mb-0">Alta y administración de almacenes registrados</p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    <div class="btn-group vista-toggle" role="group" aria-label="Cambiar vista">
                        <button type="button" class="btn btn-baseColor-light itemcub" onclick="cubo()" title="Vista tarjetas">
                            <i class="fa-solid fa-grip"></i>
                        </button>
                        <button type="button" class="btn btn-baseColor-light itemlist active" onclick="lista()" title="Vista lista">
                            <i class="fa-solid fa-list-ul"></i>
                        </button>
                    </div>

                    @if($permisos1 == "crear_almacenes")
                        <a class="btn btn-baseColor" href="/pinsertaalm">
                            <i class="fa-solid fa-plus"></i> Nuevo
                        </a>
                    @else
                        <button class="btn btn-baseColor" disabled>
                            <i class="fa-solid fa-plus"></i> Nuevo
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <section class="prod-search" aria-label="Buscador de productos con existencia">
                <div class="prod-search__header">
                    <div class="prod-search__title-wrap">
                        <div class="prod-search__icon" aria-hidden="true">
                            <i class="fa-solid fa-magnifying-glass-location"></i>
                        </div>
                        <div>
                            <h3 class="prod-search__title">¿Dónde está mi producto?</h3>
                            <p class="prod-search__subtitle">Busca por nombre, SKU o código de barras y ve almacén + ubicación con existencia.</p>
                        </div>
                    </div>
                    <span class="prod-search__badge" id="buscarExistenciaCount">
                        <i class="fa-solid fa-boxes-stacked"></i>
                        En tiempo real
                    </span>
                </div>

                <div class="prod-search__field">
                    <i class="fa-solid fa-magnifying-glass prod-search__field-icon" aria-hidden="true"></i>
                    <input type="search"
                           id="buscarExistenciaProducto"
                           class="prod-search__input"
                           placeholder="Ej. resina, SKU-001 o código de barras..."
                           autocomplete="off">
                    <button type="button" class="prod-search__btn" id="btnBuscarExistencia">
                        <i class="fa-solid fa-search"></i>
                        Buscar
                    </button>
                </div>

                <div class="prod-search__meta">
                    <div id="buscarExistenciaEstado" class="prod-search__status">
                        Escribe al menos 2 caracteres para comenzar.
                    </div>
                    <div class="prod-search__hint d-none d-md-block">Clic en un resultado para ir a la ubicación</div>
                </div>

                <div id="buscarExistenciaEmpty" class="prod-search__empty">
                    <div><i class="fa-solid fa-box-open"></i></div>
                    <div>Sin existencia disponible para esa búsqueda.</div>
                </div>

                <div class="prod-search__results" id="tablaExistenciasWrap"></div>
            </section>
        </div>
    </div>

    <div class="row g-3" id="vista_cubo">
        @forelse($Listadoalmacenes as $listado)
            <div class="col-xl-4 col-md-6 col-12">
                <div class="alm-card">
                    @if($permisos3 == "ver_ubicacion")
                        <a href="/ubicaciondet/{{$listado->id}}" class="alm-card-link">
                    @else
                        <a href="#" class="alm-card-link pointer_blocked">
                    @endif
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="min-w-0">
                                <h3 class="alm-card-title text-truncate">{{ $listado->folio_interno }}</h3>
                                <div class="alm-card-meta text-truncate">
                                    {{ $listado->tipo_almacen ?: 'Sin tipo' }}
                                    @if(!empty($listado->ciudad))
                                        · {{ $listado->ciudad }}
                                    @endif
                                </div>
                                <div class="mt-2">
                                    @if ($listado->estado == 'A')
                                        <span class="badge badge-success-dark fs-9">Activo</span>
                                    @elseif($listado->estado == 'C')
                                        <span class="badge badge-secondary fs-9">Cancelado</span>
                                    @elseif($listado->estado == 'I')
                                        <span class="badge badge-danger-dark fs-9">Inactivo</span>
                                    @endif
                                </div>
                            </div>
                            <div class="alm-card-icon badge-orange">
                                <i class="fa-solid fa-qrcode"></i>
                            </div>
                        </div>

                        <div class="alm-card-contact">
                            <div class="row-item">
                                <span class="label">Encargado</span>
                                <span class="text-truncate d-block">{{ $listado->nombre_encargado ?: '—' }}</span>
                            </div>
                            <div class="row-item">
                                <span class="label">Correo</span>
                                <span class="text-truncate d-block">{{ $listado->correo_electronico ?: '—' }}</span>
                            </div>
                            <div class="row-item">
                                <span class="label">Teléfono</span>
                                <span>{{ $listado->telefono ?: '—' }}</span>
                            </div>
                            <div class="row-item">
                                <span class="label">Dirección</span>
                                <span class="d-block" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                    {{ $listado->direccion ?: '—' }}
                                </span>
                            </div>
                        </div>
                    </a>

                    <div class="alm-card-actions">
                        @if($permisos3 == "ver_ubicacion")
                            <a href="/ubicaciondet/{{$listado->id}}" class="btn btn-success m-0" title="Ver ubicaciones">
                                <i class="fa-solid fa-eye fs-8"></i>
                            </a>
                        @else
                            <button disabled class="btn btn-success m-0" title="Ver ubicaciones">
                                <i class="fa-solid fa-eye fs-8"></i>
                            </button>
                        @endif

                        @if($permisos2 == "edit_almacen")
                            <a href="/pactualizaalmacen/{{$listado->id}}" class="btn btn-primary border-0 m-0" title="Editar">
                                <i class="fa-solid fa-pen fs-8"></i>
                            </a>
                        @else
                            <button disabled class="btn btn-primary border-0 m-0" title="Editar">
                                <i class="fa-solid fa-pen fs-8"></i>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center text-muted py-5">
                    <i class="fa-solid fa-warehouse mb-2" style="font-size: 1.75rem; opacity: 0.45;"></i>
                    <p class="mb-0">No hay almacenes registrados.</p>
                </div>
            </div>
        @endforelse
    </div>

    <div class="row mt-3" id="vista_lista">
        <div class="table-responsive">
            <table class="table table-stripped table-hover display" id="table">
                <thead>
                    <tr>
                        <th class="text-center fw-bold text-truncate">Opciones</th>
                        <th class="text-center fw-bold text-truncate">No.</th>
                        <th class="text-center fw-bold text-truncate">Folio Interno</th>
                        <th class="text-center fw-bold text-truncate">Tipo Almacen</th>
                        <th class="text-center fw-bold text-truncate">Ciudad</th>
                        <th class="text-center fw-bold text-truncate">Encargado</th>
                        <th class="text-center fw-bold text-truncate">Estado</th>
                        <th class="text-center fw-bold text-truncate">Dirección</th>
                        <th class="text-center fw-bold text-truncate">Codigo postal</th>
                        <th class="text-center fw-bold text-truncate">Telefono</th>
                        <th class="text-center fw-bold text-truncate">Correo electronico</th>
                        <th class="text-center fw-bold text-truncate">Contacto</th>
                        <th class="text-center fw-bold text-truncate">Capacidad</th>
                        <th class="text-center fw-bold text-truncate">Comentarios</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($Listadoalmacenes as $listado)
                        <tr>
                            <td class="text-truncate">
                                @if($permisos3 == "ver_ubicacion")
                                    <a href="/ubicaciondet/{{$listado->id}}" class="btn btn-success m-0" title="Ver ubicaciones">
                                        <i class="fa-solid fa-eye fs-8"></i>
                                    </a>
                                @else
                                    <button disabled class="btn btn-success m-0" title="Ver ubicaciones">
                                        <i class="fa-solid fa-eye fs-8"></i>
                                    </button>
                                @endif

                                @if($permisos2 == "edit_almacen")
                                    <a href="/pactualizaalmacen/{{$listado->id}}" class="btn btn-primary border-0 m-0" title="Editar">
                                        <i class="fa-solid fa-pen fs-8"></i>
                                    </a>
                                @else
                                    <button disabled class="btn btn-primary border-0 m-0" title="Editar">
                                        <i class="fa-solid fa-pen fs-8"></i>
                                    </button>
                                @endif
                            </td>
                            <td class="text-center">{{ $listado->id }}</td>
                            <td class="text-start">{{ $listado->folio_interno }}</td>
                            <td class="text-center">{{ $listado->tipo_almacen }}</td>
                            <td class="text-center">{{ $listado->ciudad }}</td>
                            <td class="text-truncate">{{ $listado->nombre_encargado }}</td>
                            <td class="text-center">
                                @if ($listado->estado == 'A')
                                    <span class="badge badge-success-dark fs-9">Activo</span>
                                @elseif($listado->estado == 'C')
                                    <span class="badge badge-secondary fs-9">Cancelado</span>
                                @elseif($listado->estado == 'I')
                                    <span class="badge badge-danger-dark fs-9">Inactivo</span>
                                @endif
                            </td>
                            <td>{{ $listado->direccion }}</td>
                            <td class="text-center">{{ $listado->codigo_postal }}</td>
                            <td class="text-center">{{ $listado->telefono }}</td>
                            <td>{{ $listado->correo_electronico }}</td>
                            <td>{{ $listado->contacto }}</td>
                            <td class="text-center">{{ $listado->capacidad }}</td>
                            <td>{{ $listado->comentarios }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $("#vista_lista").show();
    $("#vista_cubo").hide();

    function setToggleActive(mode) {
        $(".itemcub, .itemlist").removeClass("active");
        if (mode === "cubo") {
            $(".itemcub").addClass("active");
        } else {
            $(".itemlist").addClass("active");
        }
    }

    function cubo(){
        $("#vista_cubo").show();
        $("#vista_lista").hide();
        setToggleActive("cubo");
    }

    function lista(){
        $("#vista_cubo").hide();
        $("#vista_lista").show();
        setToggleActive("lista");
    }
</script>
<script>
(function () {
    const input = document.getElementById('buscarExistenciaProducto');
    const btn = document.getElementById('btnBuscarExistencia');
    const estado = document.getElementById('buscarExistenciaEstado');
    const wrap = document.getElementById('tablaExistenciasWrap');
    const empty = document.getElementById('buscarExistenciaEmpty');
    const countBadge = document.getElementById('buscarExistenciaCount');
    const url = @json(route('almacenes.buscar-existencias'));
    let timer = null;
    let abortCtrl = null;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setEstado(html, type) {
        estado.className = 'prod-search__status' + (type ? ' is-' + type : '');
        estado.innerHTML = html;
    }

    function formatearUbicacion(row) {
        const partes = [];
        if (row.ubicacion_folio) partes.push(row.ubicacion_folio);
        if (row.ubicacion) partes.push(row.ubicacion);
        const detalle = [row.espacio, row.nivel].filter(Boolean).join(' / ');
        if (detalle) partes.push(detalle);
        return partes.join(' · ') || 'Sin ubicación';
    }

    function urlDetalleUbicacion(row) {
        const idAlmacen = Number(row.id_almacen || 0);
        const idUbicacion = Number(row.id_ubicacion || 0);
        const nomAlmacen = encodeURIComponent(row.almacen || 'almacen');
        return `/detalleubi/${idAlmacen}/${idUbicacion}/${nomAlmacen}`;
    }

    function confirmarIrUbicacion(row) {
        const ubicacionTxt = formatearUbicacion(row);
        const destino = urlDetalleUbicacion(row);
        const html = `
            <div class="text-start">
                <p class="mb-2"><strong>${escapeHtml(row.nombre || 'Producto')}</strong></p>
                <p class="mb-1 small text-muted">SKU: ${escapeHtml(row.sku || '-')}</p>
                <p class="mb-1">Almacén: <strong>${escapeHtml(row.almacen || '-')}</strong></p>
                <p class="mb-0">Ubicación: <strong>${escapeHtml(ubicacionTxt)}</strong></p>
            </div>
        `;

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¿Ir a esta ubicación?',
                html: html,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, ir',
                cancelButtonText: 'No, quedarme',
                confirmButtonColor: '#102d49',
                cancelButtonColor: '#6b7280',
                reverseButtons: true,
            }).then(function (result) {
                if (result.isConfirmed) {
                    window.location.href = destino;
                }
            });
            return;
        }

        if (window.confirm(`¿Ir a la ubicación "${ubicacionTxt}" del almacén "${row.almacen || ''}"?`)) {
            window.location.href = destino;
        }
    }

    function renderResultados(rows) {
        wrap.innerHTML = '';
        empty.classList.remove('is-open');

        if (!rows.length) {
            wrap.classList.remove('is-open');
            empty.classList.add('is-open');
            countBadge.innerHTML = '<i class="fa-solid fa-boxes-stacked"></i> 0 resultados';
            return;
        }

        rows.forEach(function (row, index) {
            const disponible = Number(row.disponible || 0);
            const unidad = row.unidad ? escapeHtml(row.unidad) : '';
            const card = document.createElement('article');
            card.className = 'prod-result';
            card.setAttribute('tabindex', '0');
            card.setAttribute('role', 'button');
            card.style.animationDelay = Math.min(index * 0.04, 0.28) + 's';
            card.title = 'Seleccionar para ir a la ubicación';
            card.innerHTML = `
                <div class="min-w-0">
                    <h4 class="prod-result__name text-truncate">${escapeHtml(row.nombre)}</h4>
                    <div class="prod-result__meta">
                        <span class="prod-result__chip"><i class="fa-solid fa-barcode"></i>${escapeHtml(row.sku || 'Sin SKU')}</span>
                        <span class="prod-result__chip"><i class="fa-solid fa-warehouse"></i>${escapeHtml(row.almacen || '-')}</span>
                        <span class="prod-result__chip"><i class="fa-solid fa-location-dot"></i>${escapeHtml(formatearUbicacion(row))}</span>
                        ${row.codigo_barras ? `<span class="prod-result__chip"><i class="fa-solid fa-qrcode"></i>${escapeHtml(row.codigo_barras)}</span>` : ''}
                    </div>
                </div>
                <div class="prod-result__side">
                    <div class="prod-result__qty">
                        ${disponible.toLocaleString('es-MX', { minimumFractionDigits: 0, maximumFractionDigits: 3 })}${unidad ? ' ' + unidad : ''}
                        <small>Disponible</small>
                    </div>
                    <button type="button" class="prod-result__go">
                        <i class="fa-solid fa-arrow-right"></i> Ir
                    </button>
                </div>
            `;

            card.addEventListener('click', function () {
                wrap.querySelectorAll('.prod-result.is-selected').forEach(function (el) {
                    el.classList.remove('is-selected');
                });
                card.classList.add('is-selected');
                confirmarIrUbicacion(row);
            });

            card.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    card.click();
                }
            });

            wrap.appendChild(card);
        });

        wrap.classList.add('is-open');
        countBadge.innerHTML = `<i class="fa-solid fa-boxes-stacked"></i> ${rows.length} hallazgo${rows.length === 1 ? '' : 's'}`;
    }

    function buscar() {
        const q = (input.value || '').trim();

        if (q.length < 2) {
            wrap.classList.remove('is-open');
            wrap.innerHTML = '';
            empty.classList.remove('is-open');
            countBadge.innerHTML = '<i class="fa-solid fa-boxes-stacked"></i> En tiempo real';
            setEstado('Escribe al menos 2 caracteres para comenzar.');
            return;
        }

        if (abortCtrl) abortCtrl.abort();
        abortCtrl = new AbortController();

        setEstado('<span class="spinner-border text-secondary" role="status" aria-hidden="true"></span> Buscando ubicaciones...');

        fetch(`${url}?q=${encodeURIComponent(q)}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            signal: abortCtrl.signal,
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                const rows = (data && data.resultados) ? data.resultados : [];
                renderResultados(rows);
                if (!rows.length) {
                    setEstado('No encontramos existencia para esa búsqueda.', 'warn');
                } else {
                    setEstado(`<i class="fa-solid fa-circle-check"></i> ${rows.length} ubicación(es) con existencia.`, 'ok');
                }
            })
            .catch(function (err) {
                if (err && err.name === 'AbortError') return;
                wrap.classList.remove('is-open');
                wrap.innerHTML = '';
                empty.classList.remove('is-open');
                setEstado('<i class="fa-solid fa-circle-exclamation"></i> No se pudo completar la búsqueda.', 'error');
            });
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(buscar, 350);
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(timer);
            buscar();
        }
    });

    btn.addEventListener('click', function () {
        clearTimeout(timer);
        buscar();
    });
})();
</script>
<script src="{{ asset('js/table.js') }}"></script>
@endsection
