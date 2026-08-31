@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid format_page factores-settings imss-catalogo">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-hospital"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="/Nominas" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Nómina
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">IMSS Factores de Integración</h2>
                        <p class="text-muted mb-0">Administración de tablas</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="settings-panel">
        <form
            action="{{ route('Nominas.actualizar_imss') }}"
            method="POST"
            class="needs-validation imss-tab-form modern-form"
            novalidate
        >
            @csrf

            <div class="settings-section__title d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>Factores de integración por antigüedad</span>
                <small class="text-muted fw-normal text-lowercase">Edita las filas y guarda todos los cambios al final</small>
            </div>

            <div class="imss-filas-container" id="imssFilas">
                @forelse ($vartarifas_integracion as $item)
                    <div class="settings-row settings-row--multi imss-fila" data-id="{{ $item->id }}">
                        <div class="catalogo-fila-eliminar">
                            <button type="button" class="btn btn-eliminar-fila-x btn-eliminar-fila-imss" title="Eliminar fila" aria-label="Eliminar fila">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <div class="imss-fila-numero" aria-label="Fila {{ $loop->iteration }}">
                            <span class="imss-fila-numero__value">{{ $loop->iteration }}</span>
                        </div>
                        <div class="settings-row__fields">
                            <input type="hidden" name="filas[{{ $loop->index }}][id]" value="{{ $item->id }}">
                            <div class="settings-field-wrap">
                                <span class="settings-field-label">Año mínimo</span>
                                <input type="number" class="form-control form-control-sm settings-row__field settings-row__field--compact" name="filas[{{ $loop->index }}][min_años]" value="{{ formatNumero($item->min_años) }}" required aria-label="Año mínimo">
                            </div>
                            <div class="settings-field-wrap">
                                <span class="settings-field-label">Año máximo</span>
                                <input type="number" class="form-control form-control-sm settings-row__field settings-row__field--compact" name="filas[{{ $loop->index }}][max_años]" value="{{ formatNumero($item->max_años) }}" required aria-label="Año máximo">
                            </div>
                            <div class="settings-field-wrap">
                                <span class="settings-field-label">Días aguinaldo</span>
                                <input type="number" class="form-control form-control-sm settings-row__field settings-row__field--compact" name="filas[{{ $loop->index }}][dias_aguinaldo]" value="{{ formatNumero($item->dias_aguinaldo) }}" required aria-label="Días de aguinaldo">
                            </div>
                            <div class="settings-field-wrap">
                                <span class="settings-field-label">Días vacaciones</span>
                                <input type="number" class="form-control form-control-sm settings-row__field settings-row__field--compact" name="filas[{{ $loop->index }}][dias_vacaciones]" value="{{ formatNumero($item->dias_vacaciones) }}" required aria-label="Días de vacaciones">
                            </div>
                            <div class="settings-field-wrap">
                                <span class="settings-field-label">Prima vacacional</span>
                                <div class="input-group input-group-sm settings-row__field settings-row__field--compact">
                                    <span class="input-group-text">%</span>
                                    <input type="number" class="form-control" name="filas[{{ $loop->index }}][prima_vacacional]" value="{{ formatNumero($item->prima_vacacional) }}" required aria-label="Prima vacacional">
                                </div>
                            </div>
                            <div class="settings-field-wrap">
                                <span class="settings-field-label">Factor integración</span>
                                <input type="number" step="any" class="form-control form-control-sm settings-row__field settings-row__field--wide" name="filas[{{ $loop->index }}][factor_integracion]" value="{{ formatNumero($item->factor_integracion) }}" required aria-label="Factor de integración">
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="settings-empty imss-empty-msg">
                        No hay factores de integración registrados. Usa "Agregar fila" para comenzar.
                    </div>
                @endforelse
            </div>

            <div class="imss-eliminar-container"></div>

            <div class="factores-panel-actions">
                <button type="button" class="btn btn-baseColor-light fs-7 btn-agregar-fila-imss" data-target="imssFilas">
                    <i class="fa-solid fa-plus"></i> Agregar fila
                </button>
                <button type="submit" class="btn btn-baseColor fs-7">
                    <i class="fa-solid fa-check"></i> Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script src="{{ asset('js/validation.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('.imss-tab-form');
        if (!form) {
            return;
        }

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

        function reindexFilas() {
            const filas = form.querySelectorAll('.imss-filas-container .imss-fila');
            filas.forEach((fila, index) => {
                const numero = fila.querySelector('.imss-fila-numero__value');
                if (numero) {
                    numero.textContent = index + 1;
                }
                fila.querySelector('.imss-fila-numero')?.setAttribute('aria-label', `Fila ${index + 1}`);

                fila.querySelectorAll('[name^="filas["]').forEach((input) => {
                    const field = input.name.replace(/^filas\[[^\]]+\]/, '');
                    input.name = `filas[${index}]${field}`;
                });
            });
        }

        function crearFilaHtml(index) {
            return `
                <div class="settings-row settings-row--multi imss-fila imss-fila-nueva">
                    <div class="catalogo-fila-eliminar">
                        <button type="button" class="btn btn-eliminar-fila-x btn-eliminar-fila-imss" title="Eliminar fila" aria-label="Eliminar fila">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="imss-fila-numero" aria-label="Fila ${index + 1}">
                        <span class="imss-fila-numero__value">${index + 1}</span>
                    </div>
                    <div class="settings-row__fields">
                        <div class="settings-field-wrap">
                            <span class="settings-field-label">Año mínimo</span>
                            <input type="number" class="form-control form-control-sm settings-row__field settings-row__field--compact" name="filas[${index}][min_años]" required aria-label="Año mínimo">
                        </div>
                        <div class="settings-field-wrap">
                            <span class="settings-field-label">Año máximo</span>
                            <input type="number" class="form-control form-control-sm settings-row__field settings-row__field--compact" name="filas[${index}][max_años]" required aria-label="Año máximo">
                        </div>
                        <div class="settings-field-wrap">
                            <span class="settings-field-label">Días aguinaldo</span>
                            <input type="number" class="form-control form-control-sm settings-row__field settings-row__field--compact" name="filas[${index}][dias_aguinaldo]" required aria-label="Días de aguinaldo">
                        </div>
                        <div class="settings-field-wrap">
                            <span class="settings-field-label">Días vacaciones</span>
                            <input type="number" class="form-control form-control-sm settings-row__field settings-row__field--compact" name="filas[${index}][dias_vacaciones]" required aria-label="Días de vacaciones">
                        </div>
                        <div class="settings-field-wrap">
                            <span class="settings-field-label">Prima vacacional</span>
                            <div class="input-group input-group-sm settings-row__field settings-row__field--compact">
                                <span class="input-group-text">%</span>
                                <input type="number" class="form-control" name="filas[${index}][prima_vacacional]" required aria-label="Prima vacacional">
                            </div>
                        </div>
                        <div class="settings-field-wrap">
                            <span class="settings-field-label">Factor integración</span>
                            <input type="number" step="any" class="form-control form-control-sm settings-row__field settings-row__field--wide" name="filas[${index}][factor_integracion]" required aria-label="Factor de integración">
                        </div>
                    </div>
                </div>
            `;
        }

        document.querySelector('.btn-agregar-fila-imss')?.addEventListener('click', function () {
            const container = document.getElementById(this.dataset.target);
            const emptyMsg = form.querySelector('.imss-empty-msg');

            if (emptyMsg) {
                emptyMsg.remove();
            }

            const index = container.querySelectorAll('.imss-fila').length;
            container.insertAdjacentHTML('beforeend', crearFilaHtml(index));
            reindexFilas();
        });

        form.addEventListener('click', function (event) {
            const btn = event.target.closest('.btn-eliminar-fila-imss');
            if (!btn) {
                return;
            }

            event.preventDefault();

            confirmarEliminarFila(() => {
                const fila = btn.closest('.imss-fila');
                const filaId = fila.dataset.id;

                if (filaId) {
                    const eliminarContainer = form.querySelector('.imss-eliminar-container');
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'eliminar[]';
                    input.value = filaId;
                    eliminarContainer.appendChild(input);
                }

                fila.remove();
                reindexFilas();

                const container = form.querySelector('.imss-filas-container');
                if (container.querySelectorAll('.imss-fila').length === 0 && !container.querySelector('.imss-empty-msg')) {
                    container.insertAdjacentHTML('beforeend', `
                        <div class="settings-empty imss-empty-msg">
                            No hay factores de integración registrados. Usa "Agregar fila" para comenzar.
                        </div>
                    `);
                }
            });
        });

        form.addEventListener('submit', function (event) {
            reindexFilas();

            const filas = form.querySelectorAll('.imss-filas-container .imss-fila');
            if (filas.length === 0) {
                event.preventDefault();
                event.stopPropagation();
                alert('Debe existir al menos un factor de integración para guardar.');
                return;
            }

            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        });
    });
</script>
@endsection
