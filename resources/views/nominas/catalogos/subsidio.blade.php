@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid format_page factores-settings">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-hand-holding-dollar"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="/Nominas" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Nómina
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Factores Generales</h2>
                        <p class="text-muted mb-0">Administración y gestión de factores</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="settings-row settings-row--toggle">
        <div class="settings-row__label">
            Visor Fiscal
            <span class="settings-row__hint">Muestra solo información fiscal en nóminas y empleados</span>
        </div>
        <form
            action="/Nominas/CatalogoConceptos/editar/{{ $visorFiscal->id }}"
            method="GET"
            class="settings-row__actions mb-0"
            id="formVisorFiscal"
        >
            <span class="badge {{ (int) $visorFiscal->valor === 1 ? 'bg-success' : 'bg-secondary' }} fs-9" id="visorFiscalEstado">
                {{ (int) $visorFiscal->valor === 1 ? 'Encendido' : 'Apagado' }}
            </span>
            <div class="form-check form-switch mb-0">
                <input type="hidden" name="valor" value="{{ (int) $visorFiscal->valor === 1 ? 1 : 0 }}" id="visorFiscalValor">
                <input
                    class="form-check-input"
                    type="checkbox"
                    role="switch"
                    id="visorFiscalSwitch"
                    {{ (int) $visorFiscal->valor === 1 ? 'checked' : '' }}
                    onchange="actualizarVisorFiscal(this)"
                >
            </div>
        </form>
    </div>

    <div class="card factores-tabs p-3 pb-2 mb-0">
        <ul class="nav nav-pills flex-wrap gap-2 mb-0" id="factoresTabs" role="tablist">
            @foreach ($conceptos_nomina as $grupo => $secciones)
                @php($tabId = 'tab-grupo-' . \Illuminate\Support\Str::slug($grupo ?: 'sin-grupo'))
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link {{ $loop->first ? 'active' : '' }}"
                        id="{{ $tabId }}-btn"
                        data-bs-toggle="tab"
                        data-bs-target="#{{ $tabId }}"
                        type="button"
                        role="tab"
                        aria-controls="{{ $tabId }}"
                        aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                    >
                        {{ $grupo ?: 'Sin grupo' }}
                    </button>
                </li>
            @endforeach
            <li class="nav-item" role="presentation">
                <button
                    class="nav-link"
                    id="tab-subsidio-btn"
                    data-bs-toggle="tab"
                    data-bs-target="#tab-subsidio"
                    type="button"
                    role="tab"
                    aria-controls="tab-subsidio"
                    aria-selected="false"
                >
                    SUBSIDIO
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content settings-panel mt-0" id="factoresTabsContent" style="border-top-left-radius: 0; border-top-right-radius: 0; border-top: 0;">
        @foreach ($conceptos_nomina as $grupo => $secciones)
            @php($tabId = 'tab-grupo-' . \Illuminate\Support\Str::slug($grupo ?: 'sin-grupo'))
            <div
                class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                id="{{ $tabId }}"
                role="tabpanel"
                aria-labelledby="{{ $tabId }}-btn"
            >
                @foreach ($secciones as $seccion => $items)
                    <div class="settings-section__title">{{ $seccion ?: 'Sin sección' }}</div>

                    @foreach ($items as $item)
                        @php($prefijo = (str_contains($item->descripcion, '%') || $item->nombre === 'prima_riesgo') ? '%' : '$')
                        <form
                            action="/Nominas/CatalogoConceptos/editar/{{ $item->id }}"
                            class="settings-row needs-validation msform modern-form"
                            novalidate
                        >
                            <div class="settings-row__label">{{ $item->descripcion }}</div>
                            <div class="settings-row__actions">
                                <div class="input-group input-group-sm settings-row__field">
                                    <span class="input-group-text">{{ $prefijo }}</span>
                                    <input
                                        type="number"
                                        step="any"
                                        class="form-control"
                                        name="valor"
                                        value="{{ formatNumero($item->valor) }}"
                                        required
                                        aria-label="Valor de {{ $item->descripcion }}"
                                    >
                                </div>
                                @if((float) $item->valor2 != 0)
                                    <div class="input-group input-group-sm settings-row__field">
                                        <span class="input-group-text">{{ $prefijo }}</span>
                                        <input
                                            type="number"
                                            step="any"
                                            class="form-control"
                                            name="valor2"
                                            value="{{ formatNumero($item->valor2) }}"
                                            required
                                            aria-label="Valor 2 de {{ $item->descripcion }}"
                                        >
                                    </div>
                                @endif
                                <button class="btn btn-baseColor btn-save" type="submit" title="Guardar">
                                    <i class="fa-solid fa-check fs-9"></i> Guardar
                                </button>
                            </div>
                        </form>
                    @endforeach
                @endforeach
            </div>
        @endforeach

        <div class="tab-pane fade" id="tab-subsidio" role="tabpanel" aria-labelledby="tab-subsidio-btn">
             <div class="settings-section__title">PARAMETROS DE SUBSIDIO</div>
            @foreach ($varsubsidio as $item)
                <form
                    action="/Nominas/CatalogoSubsidio/editar/{{ $item->id }}"
                    class="settings-row settings-row--multi needs-validation msform modern-form"
                    novalidate
                >
                    <div class="catalogo-fila-eliminar">
                        <button
                            type="button"
                            class="btn btn-eliminar-fila-x"
                            data-delete-url="/Nominas/CatalogoSubsidio/eliminar/{{ $item->id }}"
                            onclick="confirmarEliminarSubsidio(this)"
                            title="Eliminar"
                            aria-label="Eliminar"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="settings-row__label fw-semibold">{{ $item->tipo }}</div>
                    <div class="settings-row__fields">
                        <div class="settings-field-wrap">
                            <span class="settings-field-label">UMA</span>
                            <div class="input-group input-group-sm settings-row__field">
                                <span class="input-group-text">%</span>
                                <input type="number" step="any" class="form-control" name="uma" value="{{ formatNumero($item->uma) }}" required aria-label="UMA">
                            </div>
                        </div>
                        <div class="settings-field-wrap">
                            <span class="settings-field-label">Cuota fija</span>
                            <div class="input-group input-group-sm settings-row__field">
                                <span class="input-group-text">$</span>
                                <input type="number" step="any" class="form-control" name="cuota_fija" value="{{ formatNumero($item->cuota_fija) }}" required aria-label="Cuota fija">
                            </div>
                        </div>
                        <div class="settings-field-wrap">
                            <span class="settings-field-label">Límite</span>
                            <input type="number" step="any" class="form-control form-control-sm settings-row__field" name="limite_ingresos" value="{{ formatNumero($item->limite_ingresos) }}" required aria-label="Límite de ingresos">
                        </div>
                        <div class="settings-field-wrap justify-content-end">
                            <span class="settings-field-label">&nbsp;</span>
                            <button class="btn btn-baseColor btn-save" type="submit" title="Guardar">
                                <i class="fa-solid fa-check fs-9"></i> Guardar
                            </button>
                        </div>
                    </div>
                </form>
            @endforeach
        </div>
    </div>
</div>

<script src="{{ asset('js/validation.js') }}"></script>
<script>
    function actualizarVisorFiscal(checkbox) {
        const valorInput = document.getElementById('visorFiscalValor');
        const estadoBadge = document.getElementById('visorFiscalEstado');
        const valor = checkbox.checked ? 1 : 0;

        valorInput.value = valor;
        estadoBadge.textContent = valor === 1 ? 'Encendido' : 'Apagado';
        estadoBadge.classList.toggle('bg-success', valor === 1);
        estadoBadge.classList.toggle('bg-secondary', valor !== 1);
        document.getElementById('formVisorFiscal').submit();
    }

    function confirmarEliminarSubsidio(btn) {
        const url = btn.getAttribute('data-delete-url');
        Swal.fire({
            title: '¿Está seguro?',
            text: '¿Desea eliminar este registro de subsidio?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
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
