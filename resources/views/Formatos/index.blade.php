@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

@if($mensaje = Session::get('warningNoexiste'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "question",title: "Oops...!", text: "¡No se ha encontrado este formato!"});';
            echo '</script>';
    @endphp
@endif

@php
    $conteoPorDepto = [];
    foreach ($formatos as $f) {
        $conteoPorDepto[$f->id_departamento] = ($conteoPorDepto[$f->id_departamento] ?? 0) + 1;
    }
    $totalFormatos = count($formatos);
@endphp

<style>
    .formatos-page {
        --fmt-bg: #f4f5f7;
        --fmt-surface: #ffffff;
        --fmt-border: rgba(17, 24, 39, 0.09);
        --fmt-text: #111827;
        --fmt-muted: #6b7280;
        --fmt-accent: #111827;
        --fmt-soft: #f3f4f6;
        --fmt-radius: 14px;
    }

    .formatos-page .fmt-layout {
        display: grid;
        grid-template-columns: 240px 1fr;
        gap: 1.25rem;
        align-items: start;
    }

    @media (max-width: 991.98px) {
        .formatos-page .fmt-layout {
            grid-template-columns: 1fr;
        }
    }

    /* Sidebar filtros */
    .formatos-page .fmt-sidebar {
        position: sticky;
        top: 1rem;
        background: var(--fmt-surface);
        border: 1px solid var(--fmt-border);
        border-radius: var(--fmt-radius);
        padding: 1rem;
        box-shadow: 0 2px 12px rgba(17, 24, 39, 0.04);
    }

    .formatos-page .fmt-sidebar-title {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--fmt-muted);
        margin-bottom: 0.75rem;
    }

    .formatos-page .fmt-filter-list {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        list-style: none;
        padding: 0;
        margin: 0;
    }

    @media (max-width: 991.98px) {
        .formatos-page .fmt-sidebar {
            position: static;
        }
        .formatos-page .fmt-filter-list {
            flex-direction: row;
            flex-wrap: wrap;
            gap: 0.4rem;
        }
    }

    .formatos-page .fmt-filter-btn {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        width: 100%;
        text-align: left;
        border: 1px solid transparent;
        background: transparent;
        color: var(--fmt-text);
        border-radius: 10px;
        padding: 0.55rem 0.7rem;
        font-size: 0.875rem;
        font-weight: 500;
        transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }

    @media (max-width: 991.98px) {
        .formatos-page .fmt-filter-btn {
            width: auto;
            background: var(--fmt-soft);
            border-color: var(--fmt-border);
        }
    }

    .formatos-page .fmt-filter-btn:hover {
        background: var(--fmt-soft);
    }

    .formatos-page .fmt-filter-btn.is-active {
        background: var(--fmt-accent);
        color: #fff;
        border-color: var(--fmt-accent);
    }

    .formatos-page .fmt-filter-count {
        min-width: 1.5rem;
        height: 1.5rem;
        padding: 0 0.4rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.72rem;
        font-weight: 700;
        background: var(--fmt-soft);
        color: var(--fmt-muted);
    }

    .formatos-page .fmt-filter-btn.is-active .fmt-filter-count {
        background: rgba(255, 255, 255, 0.18);
        color: #fff;
    }

    .formatos-page .fmt-search {
        position: relative;
        margin-bottom: 1rem;
    }

    .formatos-page .fmt-search i {
        position: absolute;
        left: 0.85rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--fmt-muted);
        font-size: 0.85rem;
        pointer-events: none;
    }

    .formatos-page .fmt-search input {
        width: 100%;
        border: 1px solid var(--fmt-border);
        border-radius: 10px;
        padding: 0.55rem 0.85rem 0.55rem 2.25rem;
        font-size: 0.875rem;
        background: var(--fmt-surface);
        color: var(--fmt-text);
    }

    .formatos-page .fmt-search input:focus {
        outline: none;
        border-color: #9ca3af;
        box-shadow: 0 0 0 3px rgba(17, 24, 39, 0.08);
    }

    .formatos-page .fmt-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .formatos-page .fmt-section-label {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--fmt-text);
        margin: 0;
    }

    .formatos-page .fmt-section-label span {
        font-weight: 500;
        color: var(--fmt-muted);
    }

    /* Galería de documentos */
    .formatos-page .fmt-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1rem;
    }

    .formatos-page .fmt-card {
        background: var(--fmt-surface);
        border: 1px solid var(--fmt-border);
        border-radius: var(--fmt-radius);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
        box-shadow: 0 2px 10px rgba(17, 24, 39, 0.04);
    }

    .formatos-page .fmt-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 28px rgba(17, 24, 39, 0.1);
        border-color: rgba(17, 24, 39, 0.16);
    }

    .formatos-page .fmt-preview {
        position: relative;
        background: #e8eaed;
        height: 320px;
        overflow: hidden;
        border-bottom: 1px solid var(--fmt-border);
    }

    .formatos-page .fmt-preview embed,
    .formatos-page .fmt-preview iframe {
        width: 100%;
        height: 100%;
        border: 0;
        pointer-events: none;
    }

    .formatos-page .fmt-preview-overlay {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: flex-end;
        justify-content: center;
        padding: 0.85rem;
        background: linear-gradient(to top, rgba(17, 24, 39, 0.55) 0%, transparent 45%);
        opacity: 0;
        transition: opacity 0.2s ease;
    }

    .formatos-page .fmt-card:hover .fmt-preview-overlay {
        opacity: 1;
    }

    .formatos-page .fmt-preview-actions {
        display: flex;
        gap: 0.4rem;
        width: 100%;
        justify-content: center;
    }

    .formatos-page .fmt-preview-actions .btn {
        font-size: 0.78rem;
        padding: 0.4rem 0.75rem;
        border-radius: 8px;
        font-weight: 600;
    }

    .formatos-page .fmt-badge-pdf {
        position: absolute;
        top: 0.65rem;
        left: 0.65rem;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        background: #fff;
        color: #b91c1c;
        border: 1px solid rgba(185, 28, 28, 0.2);
        border-radius: 8px;
        padding: 0.2rem 0.5rem;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        z-index: 1;
    }

    .formatos-page .fmt-card-body {
        padding: 0.9rem 1rem 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
        flex: 1;
    }

    .formatos-page .fmt-card-title {
        font-size: 0.95rem;
        font-weight: 650;
        color: var(--fmt-text);
        margin: 0;
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .formatos-page .fmt-card-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.4rem;
        margin-top: auto;
    }

    .formatos-page .fmt-dept-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        background: var(--fmt-soft);
        color: var(--fmt-muted);
        border-radius: 999px;
        padding: 0.2rem 0.6rem;
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .formatos-page .fmt-card-date {
        font-size: 0.72rem;
        color: var(--fmt-muted);
        margin-left: auto;
    }

    .formatos-page .fmt-card-footer {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0 1rem 1rem;
    }

    .formatos-page .fmt-card-footer .btn {
        flex: 1;
        font-size: 0.78rem;
        padding: 0.4rem 0.5rem;
        border-radius: 8px;
        font-weight: 600;
    }

    .formatos-page .fmt-card-footer .btn-delete {
        flex: 0 0 auto;
        width: 2.25rem;
        padding: 0.4rem;
    }

    /* Empty / no results */
    .formatos-page .fmt-empty {
        grid-column: 1 / -1;
        text-align: center;
        padding: 3.5rem 1.5rem;
        background: var(--fmt-surface);
        border: 1px dashed var(--fmt-border);
        border-radius: var(--fmt-radius);
        color: var(--fmt-muted);
    }

    .formatos-page .fmt-empty i {
        font-size: 2.25rem;
        margin-bottom: 0.75rem;
        color: #9ca3af;
        display: block;
    }

    .formatos-page .fmt-empty h5 {
        color: var(--fmt-text);
        font-weight: 650;
        margin-bottom: 0.35rem;
    }

    .formatos-page .fmt-empty p {
        margin: 0;
        font-size: 0.875rem;
    }

    .formatos-page .fmt-card.is-hidden,
    .formatos-page .fmt-empty.is-hidden {
        display: none !important;
    }

    @media (max-width: 575.98px) {
        .formatos-page .fmt-preview {
            height: 260px;
        }
        .formatos-page .fmt-preview-overlay {
            opacity: 1;
            background: linear-gradient(to top, rgba(17, 24, 39, 0.45) 0%, transparent 40%);
        }
    }
</style>

<div class="container-fluid format_page formatos-page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-file-pdf"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Formatos</h2>
                        <p class="text-muted mb-0">Documentos oficiales listos para consultar y descargar.</p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    @if ($permisos1 == 'subir_formatos')
                        <button class="btn btn-baseColor fs-7" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight">
                            <i class="fa-solid fa-plus"></i> Nuevo Formato
                        </button>
                    @else
                        <button class="btn btn-baseColor fs-7" disabled type="button">
                            <i class="fa-solid fa-plus"></i> Nuevo Formato
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="fmt-layout">
        <aside class="fmt-sidebar">
            <div class="fmt-sidebar-title">Departamentos</div>
            <ul class="fmt-filter-list" id="fmtFilterList">
                <li>
                    <button type="button" class="fmt-filter-btn is-active" data-dept="all">
                        <span>Todos</span>
                        <span class="fmt-filter-count">{{ $totalFormatos }}</span>
                    </button>
                </li>
                @foreach ($departamentos as $dep)
                    @if ($dep->id)
                        <li>
                            <button type="button" class="fmt-filter-btn" data-dept="{{ $dep->id }}">
                                <span>{{ $dep->nombre }}</span>
                                <span class="fmt-filter-count">{{ $conteoPorDepto[$dep->id] ?? 0 }}</span>
                            </button>
                        </li>
                    @endif
                @endforeach
            </ul>
        </aside>

        <section>
            <div class="fmt-toolbar">
                <p class="fmt-section-label">
                    Mostrando: <span id="fmtActiveLabel">Todos</span>
                    <span class="text-muted fw-normal"> · <strong id="fmtVisibleCount" class="text-dark">{{ $totalFormatos }}</strong> formato(s)</span>
                </p>
                <div class="fmt-search" style="min-width: min(100%, 280px); margin-bottom: 0;">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" id="fmtSearch" placeholder="Buscar por nombre..." autocomplete="off">
                </div>
            </div>

            <div class="fmt-grid" id="fmtGrid">
                @forelse ($formatos as $dato)
                    @php
                        $urlArchivo = asset('Formatos/' . $dato->departamento . '/' . $dato->ruta_archivo);
                        $fecha = !empty($dato->created_at) ? \Carbon\Carbon::parse($dato->created_at)->format('d/m/Y') : null;
                    @endphp
                    <article
                        class="fmt-card"
                        data-dept="{{ $dato->id_departamento }}"
                        data-name="{{ strtolower($dato->nombre) }}"
                    >
                        <div class="fmt-preview">
                            <span class="fmt-badge-pdf"><i class="fa-solid fa-file-pdf"></i> PDF</span>
                            <embed src="{{ $urlArchivo }}#toolbar=0&navpanes=0&scrollbar=0" type="application/pdf">
                            <div class="fmt-preview-overlay">
                                <div class="fmt-preview-actions">
                                    <a class="btn btn-light" href="{{ $urlArchivo }}" target="_blank" rel="noopener">
                                        <i class="fa-solid fa-eye"></i> Ver
                                    </a>
                                    <a class="btn btn-baseColor" href="{{ $urlArchivo }}" download>
                                        <i class="fa-solid fa-download"></i> Descargar
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="fmt-card-body">
                            <h4 class="fmt-card-title" title="{{ $dato->nombre }}">{{ $dato->nombre }}</h4>
                            <div class="fmt-card-meta">
                                <span class="fmt-dept-chip">
                                    <i class="fa-solid fa-building"></i>
                                    {{ $dato->departamento }}
                                </span>
                                @if ($fecha)
                                    <span class="fmt-card-date">{{ $fecha }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="fmt-card-footer">
                            <a class="btn btn-baseColor-light" href="{{ $urlArchivo }}" target="_blank" rel="noopener">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir
                            </a>
                            @if ($permisos2 == 'eliminar_formatos')
                                <button
                                    class="btn btn-outline-danger btn-delete"
                                    type="button"
                                    data-bs-toggle="modal"
                                    data-bs-target="#eliminarModal{{ $dato->id }}"
                                    title="Eliminar formato"
                                >
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="fmt-empty" id="fmtEmptyInitial">
                        <i class="fa-regular fa-folder-open"></i>
                        <h5>Sin formatos cargados</h5>
                        <p>Aún no hay documentos. Sube el primero con «Nuevo Formato».</p>
                    </div>
                @endforelse

                @if ($totalFormatos > 0)
                    <div class="fmt-empty is-hidden" id="fmtEmptyFilter">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <h5>Sin resultados</h5>
                        <p>No hay formatos que coincidan con el filtro o la búsqueda.</p>
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>

@foreach ($formatos as $dato)
    <div class="modal fade" id="eliminarModal{{ $dato->id }}" tabindex="-1"
        aria-labelledby="eliminarModalLabel{{ $dato->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="eliminarModalLabel{{ $dato->id }}">Eliminar formato</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body border-0 pt-0">
                    <p class="mb-1 text-muted">¿Seguro que quieres eliminar este archivo?</p>
                    <p class="fw-semibold mb-0">{{ $dato->nombre }}</p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary fs-8" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark"></i> Cerrar
                    </button>
                    <a href="/EliminarFormato/{{ $dato->id }}" class="btn btn-baseColor fs-8">
                        <i class="fa-solid fa-check"></i> Aceptar
                    </a>
                </div>
            </div>
        </div>
    </div>
@endforeach

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRight" aria-labelledby="offcanvasRightLabel">
    <div class="offcanvas-header">
        <h5 id="offcanvasRightLabel">Subir un Nuevo Formato</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body p-4 pt-0">
        <form action="/AgregarFormato" method="POST" enctype="multipart/form-data" class="g-3 form needs-validation" novalidate>
            @csrf
            <div class="modal-body container">
                <div class="row mb-3 p-2 pb-0 pt-0">
                    <label class="form-label">Nombre de Archivo</label>
                    <input type="text" class="form-control text" name="nombre" maxlength="30" required />
                    <div class="valid-feedback">  ¡Se ve bien!</div>
                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                </div>

                <div class="row mb-3 p-2 pb-0 pt-0">
                    <label class="form-label">Departamento</label>
                    <select class="form-select" name="departamento" id="" required>
                        <option value="">Selecciona...</option>
                        @foreach ($vardepartamentos as $dep)
                            <option value="{{ $dep->id }}">{{ $dep->nombre }}</option>
                        @endforeach
                    </select>
                    <div class="valid-feedback">  ¡Se ve bien!</div>
                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                </div>

                <div class="row mb-3 p-2 pb-0 pt-0">
                    <label for="file1" class="drop-container2">
                        <span class="drop-title2">Subir Nuevo Formato</span>
                        <small>Archivo importado PDF</small>
                        <label class="btn" for="formato">
                            Seleccionar archivo
                        </label>
                        <input type="file" id="formato" class="form-control d-none" name="formato" onchange="this.nextElementSibling.textContent = this.files[0]?.name || 'No se ha seleccionado ningún archivo';">
                        <small class="text-muted mt-2">No se ha seleccionado ningún archivo</small>

                        <div class="valid-feedback"> ¡Se ve bien!</div>
                        <div class="invalid-feedback"> Por favor, completa la información  requerida.</div>
                    </label>
                </div>

                <button type="submit" class="btn btn-baseColor fs-8 col-12 m-0 mb-2">
                    <i class="fas fa-arrow-up-from-bracket"></i>&nbsp;&nbsp;
                    Subir
                </button>
            </div>
        </form>
    </div>
</div>

<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/validaPDF.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const filterBtns = document.querySelectorAll('.fmt-filter-btn');
        const cards = document.querySelectorAll('.fmt-card');
        const searchInput = document.getElementById('fmtSearch');
        const activeLabel = document.getElementById('fmtActiveLabel');
        const visibleCount = document.getElementById('fmtVisibleCount');
        const emptyFilter = document.getElementById('fmtEmptyFilter');

        let activeDept = 'all';

        function applyFilters() {
            const query = (searchInput?.value || '').trim().toLowerCase();
            let shown = 0;

            cards.forEach(card => {
                const deptMatch = activeDept === 'all' || card.dataset.dept === activeDept;
                const nameMatch = !query || (card.dataset.name || '').includes(query);
                const visible = deptMatch && nameMatch;
                card.classList.toggle('is-hidden', !visible);
                if (visible) shown += 1;
            });

            if (visibleCount) visibleCount.textContent = shown;
            if (emptyFilter) emptyFilter.classList.toggle('is-hidden', shown > 0 || cards.length === 0);
        }

        filterBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                filterBtns.forEach(b => b.classList.remove('is-active'));
                this.classList.add('is-active');
                activeDept = this.dataset.dept;
                if (activeLabel) {
                    activeLabel.textContent = this.querySelector('span')?.textContent?.trim() || 'Todos';
                }
                applyFilters();
            });
        });

        searchInput?.addEventListener('input', applyFilters);
        applyFilters();
    });
</script>
@endsection
