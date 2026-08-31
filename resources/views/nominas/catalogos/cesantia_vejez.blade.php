@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid format_page factores-settings cesantia-catalogo">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="/Nominas" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Nómina
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Cesantía y Vejez</h2>
                        <p class="text-muted mb-0">Administración de cuotas patronales por rango de SBC</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="settings-panel">
        <form
            action="{{ route('Nominas.actualizar_cesantia_vejez') }}"
            method="POST"
            class="needs-validation cesantia-tab-form modern-form"
            novalidate
        >
            @csrf

            <div class="settings-section__title d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>Tarifas de cesantía y vejez</span>
                <small class="text-muted fw-normal text-lowercase">Edita las filas y guarda todos los cambios al final</small>
            </div>

            <div class="cesantia-filas-container" id="cesantiaFilas">
                @forelse ($varcesantia_vejez as $item)
                    <div class="settings-row settings-row--multi cesantia-fila" data-id="{{ $item->id }}">
                        <div class="catalogo-fila-eliminar">
                            <button type="button" class="btn btn-eliminar-fila-x btn-eliminar-fila-cesantia" title="Eliminar fila" aria-label="Eliminar fila">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <div class="cesantia-fila-numero" aria-label="Fila {{ $loop->iteration }}">
                            <span class="cesantia-fila-numero__value">{{ $loop->iteration }}</span>
                        </div>
                        <div class="settings-row__fields">
                            <input type="hidden" name="filas[{{ $loop->index }}][id]" value="{{ $item->id }}">
                            <div class="settings-field-wrap">
                                <span class="settings-field-label">SBC mín. (UMA)</span>
                                <input type="number" step="any" class="form-control form-control-sm settings-row__field settings-row__field--compact" name="filas[{{ $loop->index }}][sbc_minimo]" value="{{ formatNumero($item->sbc_minimo) }}" required aria-label="SBC mínimo en UMA">
                            </div>
                            <div class="settings-field-wrap">
                                <span class="settings-field-label">SBC máx. (UMA)</span>
                                <input type="number" step="any" class="form-control form-control-sm settings-row__field settings-row__field--compact" name="filas[{{ $loop->index }}][sbc_maximo]" value="{{ formatNumero($item->sbc_maximo) }}" required aria-label="SBC máximo en UMA">
                            </div>
                            <div class="settings-field-wrap">
                                <span class="settings-field-label">SBC mín. ($)</span>
                                <div class="input-group input-group-sm settings-row__field settings-row__field--wide">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="any" class="form-control" name="filas[{{ $loop->index }}][sbc_pesos_minimo]" value="{{ formatNumero($item->sbc_pesos_minimo) }}" required aria-label="SBC mínimo en pesos">
                                </div>
                            </div>
                            <div class="settings-field-wrap">
                                <span class="settings-field-label">SBC máx. ($)</span>
                                <div class="input-group input-group-sm settings-row__field settings-row__field--wide">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="any" class="form-control" name="filas[{{ $loop->index }}][sbc_pesos_maximo]" value="{{ formatNumero($item->sbc_pesos_maximo) }}" required aria-label="SBC máximo en pesos">
                                </div>
                            </div>
                            <div class="settings-field-wrap">
                                <span class="settings-field-label">Cuota patronal</span>
                                <div class="input-group input-group-sm settings-row__field settings-row__field--compact">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="any" class="form-control" name="filas[{{ $loop->index }}][cuota_patronal]" value="{{ formatNumero($item->cuota_patronal) }}" required aria-label="Cuota patronal">
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="settings-empty cesantia-empty-msg">
                        No hay tarifas registradas. Usa "Agregar fila" para comenzar.
                    </div>
                @endforelse
            </div>

            <div class="cesantia-eliminar-container"></div>

            <div class="factores-panel-actions">
                <button type="button" class="btn btn-baseColor-light fs-7 btn-agregar-fila-cesantia" data-target="cesantiaFilas">
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
        const form = document.querySelector('.cesantia-tab-form');
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
            const filas = form.querySelectorAll('.cesantia-filas-container .cesantia-fila');
            filas.forEach((fila, index) => {
                const numero = fila.querySelector('.cesantia-fila-numero__value');
                if (numero) {
                    numero.textContent = index + 1;
                }
                fila.querySelector('.cesantia-fila-numero')?.setAttribute('aria-label', `Fila ${index + 1}`);

                fila.querySelectorAll('[name^="filas["]').forEach((input) => {
                    const field = input.name.replace(/^filas\[[^\]]+\]/, '');
                    input.name = `filas[${index}]${field}`;
                });
            });
        }

        function crearFilaHtml(index) {
            return `
                <div class="settings-row settings-row--multi cesantia-fila cesantia-fila-nueva">
                    <div class="catalogo-fila-eliminar">
                        <button type="button" class="btn btn-eliminar-fila-x btn-eliminar-fila-cesantia" title="Eliminar fila" aria-label="Eliminar fila">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="cesantia-fila-numero" aria-label="Fila ${index + 1}">
                        <span class="cesantia-fila-numero__value">${index + 1}</span>
                    </div>
                    <div class="settings-row__fields">
                        <div class="settings-field-wrap">
                            <span class="settings-field-label">SBC mín. (UMA)</span>
                            <input type="number" step="any" class="form-control form-control-sm settings-row__field settings-row__field--compact" name="filas[${index}][sbc_minimo]" required aria-label="SBC mínimo en UMA">
                        </div>
                        <div class="settings-field-wrap">
                            <span class="settings-field-label">SBC máx. (UMA)</span>
                            <input type="number" step="any" class="form-control form-control-sm settings-row__field settings-row__field--compact" name="filas[${index}][sbc_maximo]" required aria-label="SBC máximo en UMA">
                        </div>
                        <div class="settings-field-wrap">
                            <span class="settings-field-label">SBC mín. ($)</span>
                            <div class="input-group input-group-sm settings-row__field settings-row__field--wide">
                                <span class="input-group-text">$</span>
                                <input type="number" step="any" class="form-control" name="filas[${index}][sbc_pesos_minimo]" required aria-label="SBC mínimo en pesos">
                            </div>
                        </div>
                        <div class="settings-field-wrap">
                            <span class="settings-field-label">SBC máx. ($)</span>
                            <div class="input-group input-group-sm settings-row__field settings-row__field--wide">
                                <span class="input-group-text">$</span>
                                <input type="number" step="any" class="form-control" name="filas[${index}][sbc_pesos_maximo]" required aria-label="SBC máximo en pesos">
                            </div>
                        </div>
                        <div class="settings-field-wrap">
                            <span class="settings-field-label">Cuota patronal</span>
                            <div class="input-group input-group-sm settings-row__field settings-row__field--compact">
                                <span class="input-group-text">$</span>
                                <input type="number" step="any" class="form-control" name="filas[${index}][cuota_patronal]" required aria-label="Cuota patronal">
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        document.querySelector('.btn-agregar-fila-cesantia')?.addEventListener('click', function () {
            const container = document.getElementById(this.dataset.target);
            const emptyMsg = form.querySelector('.cesantia-empty-msg');

            if (emptyMsg) {
                emptyMsg.remove();
            }

            const index = container.querySelectorAll('.cesantia-fila').length;
            container.insertAdjacentHTML('beforeend', crearFilaHtml(index));
            reindexFilas();
        });

        form.addEventListener('click', function (event) {
            const btn = event.target.closest('.btn-eliminar-fila-cesantia');
            if (!btn) {
                return;
            }

            event.preventDefault();

            confirmarEliminarFila(() => {
                const fila = btn.closest('.cesantia-fila');
                const filaId = fila.dataset.id;

                if (filaId) {
                    const eliminarContainer = form.querySelector('.cesantia-eliminar-container');
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'eliminar[]';
                    input.value = filaId;
                    eliminarContainer.appendChild(input);
                }

                fila.remove();
                reindexFilas();

                const container = form.querySelector('.cesantia-filas-container');
                if (container.querySelectorAll('.cesantia-fila').length === 0 && !container.querySelector('.cesantia-empty-msg')) {
                    container.insertAdjacentHTML('beforeend', `
                        <div class="settings-empty cesantia-empty-msg">
                            No hay tarifas registradas. Usa "Agregar fila" para comenzar.
                        </div>
                    `);
                }
            });
        });

        form.addEventListener('submit', function (event) {
            reindexFilas();

            const filas = form.querySelectorAll('.cesantia-filas-container .cesantia-fila');
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
</script>
@endsection
