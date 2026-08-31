@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid format_page factores-settings isr-catalogo">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-money-bill-trend-up"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="/Nominas" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Nómina
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">ISR Retenciones</h2>
                        <p class="text-muted mb-0">Administración de tablas de retenciones por tipo de nómina</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card factores-tabs p-3 pb-2 mb-0">
        <ul class="nav nav-pills flex-wrap gap-2 mb-0" id="isrTabs" role="tablist">
            @foreach ($tiposNomina as $tipo)
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link {{ $tipoActivo == $tipo->id ? 'active' : '' }}"
                        id="tab-isr-{{ $tipo->id }}"
                        data-bs-toggle="tab"
                        data-bs-target="#panel-isr-{{ $tipo->id }}"
                        type="button"
                        role="tab"
                        aria-controls="panel-isr-{{ $tipo->id }}"
                        aria-selected="{{ $tipoActivo == $tipo->id ? 'true' : 'false' }}"
                    >
                        {{ $tipo->tipo }}
                    </button>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="tab-content settings-panel mt-0" id="isrTabsContent" style="border-top-left-radius: 0; border-top-right-radius: 0; border-top: 0;">
        @foreach ($tiposNomina as $tipo)
            @php($filas = $isrPorTipo[$tipo->id] ?? collect())
            <div
                class="tab-pane fade {{ $tipoActivo == $tipo->id ? 'show active' : '' }}"
                id="panel-isr-{{ $tipo->id }}"
                role="tabpanel"
                aria-labelledby="tab-isr-{{ $tipo->id }}"
            >
                <form
                    action="{{ route('Nominas.actualizar_isr') }}"
                    method="POST"
                    class="needs-validation isr-tab-form modern-form"
                    data-tipo="{{ $tipo->id }}"
                    novalidate
                >
                    @csrf
                    <input type="hidden" name="id_tipo_nomina" value="{{ $tipo->id }}">

                    <div class="settings-section__title d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span>Tarifas {{ $tipo->tipo }}</span>
                        <small class="text-muted fw-normal text-lowercase">Edita las filas y guarda todos los cambios al final</small>
                    </div>

                    <div class="isr-filas-container" id="isrFilas{{ $tipo->id }}">
                        @forelse ($filas as $item)
                            <div class="settings-row settings-row--multi isr-fila" data-id="{{ $item->id }}">
                                <div class="catalogo-fila-eliminar">
                                    <button type="button" class="btn btn-eliminar-fila-x btn-eliminar-fila-isr" title="Eliminar fila" aria-label="Eliminar fila">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                                <div class="isr-fila-numero" aria-label="Fila {{ $loop->iteration }}">
                                    <span class="isr-fila-numero__value">{{ $loop->iteration }}</span>
                                </div>
                                <div class="settings-row__fields">
                                    <input type="hidden" class="isr-fila-id" name="filas[{{ $loop->index }}][id]" value="{{ $item->id }}">
                                    <div class="settings-field-wrap">
                                        <span class="settings-field-label">Lím. inferior</span>
                                        <div class="input-group input-group-sm settings-row__field settings-row__field--wide">
                                            <span class="input-group-text">$</span>
                                            <input type="number" step="any" class="form-control isr-input-limite-inf" name="filas[{{ $loop->index }}][limite_inferior]" value="{{ formatNumero($item->limite_inferior) }}" required aria-label="Límite inferior">
                                        </div>
                                    </div>
                                    <div class="settings-field-wrap">
                                        <span class="settings-field-label">Lím. superior</span>
                                        <div class="input-group input-group-sm settings-row__field settings-row__field--wide">
                                            <span class="input-group-text">$</span>
                                            <input type="number" step="any" class="form-control isr-input-limite-sup" name="filas[{{ $loop->index }}][limite_superior]" value="{{ formatNumero($item->limite_superior) }}" required aria-label="Límite superior">
                                        </div>
                                    </div>
                                    <div class="settings-field-wrap">
                                        <span class="settings-field-label">Cuota fija</span>
                                        <div class="input-group input-group-sm settings-row__field settings-row__field--wide">
                                            <span class="input-group-text">$</span>
                                            <input type="number" step="any" class="form-control" name="filas[{{ $loop->index }}][cuota_fija]" value="{{ formatNumero($item->cuota_fija) }}" required aria-label="Cuota fija">
                                        </div>
                                    </div>
                                    <div class="settings-field-wrap">
                                        <span class="settings-field-label">% excedente</span>
                                        <div class="input-group input-group-sm settings-row__field settings-row__field--compact">
                                            <span class="input-group-text">%</span>
                                            <input type="number" step="any" class="form-control" name="filas[{{ $loop->index }}][porcentaje]" value="{{ formatNumero($item->porcentaje) }}" required aria-label="Porcentaje sobre excedente">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="settings-empty isr-empty-msg">
                                No hay tarifas registradas para este tipo. Usa "Agregar fila" para comenzar.
                            </div>
                        @endforelse
                    </div>

                    <div class="isr-eliminar-container"></div>

                    <div class="factores-panel-actions">
                        <button
                            type="button"
                            class="btn btn-baseColor-light fs-7 btn-agregar-fila-isr"
                            data-target="isrFilas{{ $tipo->id }}"
                        >
                            <i class="fa-solid fa-plus"></i> Agregar fila
                        </button>
                        <button type="submit" class="btn btn-baseColor fs-7">
                            <i class="fa-solid fa-check"></i> Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        @endforeach
    </div>
</div>

<script src="{{ asset('js/validation.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        function confirmarEliminarFila(callback) {
            Swal.fire({
                title: '¿Está seguro?',
                text: '¿Desea eliminar este registro?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    callback();
                }
            });
        }

        function reindexFilas(form) {
            const filas = form.querySelectorAll('.isr-filas-container .isr-fila');
            filas.forEach((fila, index) => {
                const numero = fila.querySelector('.isr-fila-numero__value');
                if (numero) {
                    numero.textContent = index + 1;
                }
                fila.querySelector('.isr-fila-numero')?.setAttribute('aria-label', `Fila ${index + 1}`);

                fila.querySelectorAll('[name^="filas["]').forEach((input) => {
                    const field = input.name.replace(/^filas\[[^\]]+\]/, '');
                    input.name = `filas[${index}]${field}`;
                });
            });
        }

        function crearFilaHtml(index) {
            return `
                <div class="settings-row settings-row--multi isr-fila isr-fila-nueva">
                    <div class="catalogo-fila-eliminar">
                        <button type="button" class="btn btn-eliminar-fila-x btn-eliminar-fila-isr" title="Eliminar fila" aria-label="Eliminar fila">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="isr-fila-numero" aria-label="Fila ${index + 1}">
                        <span class="isr-fila-numero__value">${index + 1}</span>
                    </div>
                    <div class="settings-row__fields">
                        <div class="settings-field-wrap">
                            <span class="settings-field-label">Lím. inferior</span>
                            <div class="input-group input-group-sm settings-row__field settings-row__field--wide">
                                <span class="input-group-text">$</span>
                                <input type="number" step="any" class="form-control isr-input-limite-inf" name="filas[${index}][limite_inferior]" required>
                            </div>
                        </div>
                        <div class="settings-field-wrap">
                            <span class="settings-field-label">Lím. superior</span>
                            <div class="input-group input-group-sm settings-row__field settings-row__field--wide">
                                <span class="input-group-text">$</span>
                                <input type="number" step="any" class="form-control isr-input-limite-sup" name="filas[${index}][limite_superior]" required>
                            </div>
                        </div>
                        <div class="settings-field-wrap">
                            <span class="settings-field-label">Cuota fija</span>
                            <div class="input-group input-group-sm settings-row__field settings-row__field--wide">
                                <span class="input-group-text">$</span>
                                <input type="number" step="any" class="form-control" name="filas[${index}][cuota_fija]" required>
                            </div>
                        </div>
                        <div class="settings-field-wrap">
                            <span class="settings-field-label">% excedente</span>
                            <div class="input-group input-group-sm settings-row__field settings-row__field--compact">
                                <span class="input-group-text">%</span>
                                <input type="number" step="any" class="form-control" name="filas[${index}][porcentaje]" required>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        document.querySelectorAll('.btn-agregar-fila-isr').forEach((btn) => {
            btn.addEventListener('click', function () {
                const container = document.getElementById(this.dataset.target);
                const form = container.closest('form');
                const emptyMsg = form.querySelector('.isr-empty-msg');

                if (emptyMsg) {
                    emptyMsg.remove();
                }

                const index = container.querySelectorAll('.isr-fila').length;
                container.insertAdjacentHTML('beforeend', crearFilaHtml(index));
                reindexFilas(form);
            });
        });

        document.querySelectorAll('.isr-tab-form').forEach((form) => {
            form.addEventListener('click', function (event) {
                const btn = event.target.closest('.btn-eliminar-fila-isr');
                if (!btn) {
                    return;
                }

                event.preventDefault();

                confirmarEliminarFila(() => {
                    const fila = btn.closest('.isr-fila');
                    const filaId = fila.dataset.id;

                    if (filaId) {
                        const eliminarContainer = form.querySelector('.isr-eliminar-container');
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'eliminar[]';
                        input.value = filaId;
                        eliminarContainer.appendChild(input);
                    }

                    fila.remove();
                    reindexFilas(form);

                    const container = form.querySelector('.isr-filas-container');
                    if (container.querySelectorAll('.isr-fila').length === 0 && !container.querySelector('.isr-empty-msg')) {
                        container.insertAdjacentHTML('beforeend', `
                            <div class="settings-empty isr-empty-msg">
                                No hay tarifas registradas para este tipo. Usa "Agregar fila" para comenzar.
                            </div>
                        `);
                    }
                });
            });

            form.addEventListener('submit', function (event) {
                reindexFilas(form);

                const filas = form.querySelectorAll('.isr-filas-container .isr-fila');
                if (filas.length === 0) {
                    event.preventDefault();
                    event.stopPropagation();
                    alert('Debe existir al menos una tarifa para guardar.');
                    return;
                }

                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }

                form.classList.add('was-validated');
            });
        });
    });
</script>
@endsection
