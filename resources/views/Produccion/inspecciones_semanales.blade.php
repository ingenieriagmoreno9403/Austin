@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid acciones-config-page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="{{ route('produccion.maquinas') }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Catálogo de máquinas
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Inspecciones semanales</h2>
                        <p class="text-muted mb-0">{{ $maquina->codigo }} — {{ $maquina->nombre }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-4">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow p-3 mb-3 bg-body rounded-5">
        <div class="card-header bg-body border-0">
            <h5 class="text-secondary mb-1">
                <i class="fa-solid fa-circle-plus me-2"></i>Nueva inspección semanal
            </h5>
            <p class="text-muted fs-8 mb-0">Complete la lista de revisión y adjunte evidencia fotográfica.</p>
        </div>
        <div class="card-body pt-0">
            <form action="{{ route('produccion.maquinas.inspecciones.store', $maquina->id) }}" method="POST"
                enctype="multipart/form-data" class="modern-form needs-validation" novalidate>
                @csrf

                <h6 class="text-marino mt-2 mb-2">Datos de inspección</h6>
                <div class="row">
                    <div class="col-md-3 col-12 mt-2">
                        <label class="form-label">Fecha de inspección <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="fecha_inspeccion"
                            value="{{ old('fecha_inspeccion', date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-3 col-12 mt-2">
                        <label class="form-label">Responsable de inspección <span class="text-danger">*</span></label>
                        <select class="form-select" name="inspector_id" required>
                            <option value="">— Seleccione —</option>
                            @foreach ($empleados as $empleado)
                                <option value="{{ $empleado->id }}" {{ (int) old('inspector_id') === (int) $empleado->id ? 'selected' : '' }}>
                                    {{ $empleado->primer_nombre }} {{ $empleado->segundo_nombre }}
                                    {{ $empleado->apellido_paterno }} {{ $empleado->apellido_materno }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-12 mt-2">
                        <label class="form-label">Supervisor que valida</label>
                        <select class="form-select" name="supervisor_id">
                            <option value="">— Seleccione —</option>
                            @foreach ($empleados as $empleado)
                                <option value="{{ $empleado->id }}" {{ (int) old('supervisor_id') === (int) $empleado->id ? 'selected' : '' }}>
                                    {{ $empleado->primer_nombre }} {{ $empleado->segundo_nombre }}
                                    {{ $empleado->apellido_paterno }} {{ $empleado->apellido_materno }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-12 mt-2">
                        <label class="form-label">Resultado de la inspección <span class="text-danger">*</span></label>
                        <select class="form-select" name="resultado_general" required>
                            <option value="">— Seleccione —</option>
                            <option value="EXCELENTE" {{ old('resultado_general') === 'EXCELENTE' ? 'selected' : '' }}>Excelente</option>
                            <option value="BUENO" {{ old('resultado_general') === 'BUENO' ? 'selected' : '' }}>Bueno</option>
                            <option value="REGULAR" {{ old('resultado_general') === 'REGULAR' ? 'selected' : '' }}>Regular</option>
                            <option value="MALO" {{ old('resultado_general') === 'MALO' ? 'selected' : '' }}>Malo</option>
                            <option value="CRITICO" {{ old('resultado_general') === 'CRITICO' ? 'selected' : '' }}>Crítico</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 mt-2">
                        <label class="form-label">Observaciones generales</label>
                        <textarea class="form-control text" name="observaciones_generales" rows="2">{{ old('observaciones_generales') }}</textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 mt-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="requiere_mantenimiento" value="1"
                                id="requiere_mantenimiento" {{ old('requiere_mantenimiento') ? 'checked' : '' }}>
                            <label class="form-check-label" for="requiere_mantenimiento">
                                Requiere mantenimiento
                            </label>
                        </div>
                        <div class="form-text">Si algún concepto es crítico, se marcará automáticamente al guardar.</div>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 mb-2">
                    <h6 class="text-marino mb-0">Lista de revisión</h6>
                    <button type="button" class="btn btn-blue-light btn-sm" id="btn-agregar-concepto-revision">
                        <i class="fa-solid fa-plus"></i> Agregar concepto
                    </button>
                </div>
                <p class="text-muted fs-8 mb-2">Puede quitar filas o agregar conceptos solo para esta inspección.</p>
                <div class="table-responsive">
                    <table class="table table-stripped table-hover mb-0" id="tabla-lista-revision">
                        <thead>
                            <tr>
                                <th>Concepto</th>
                                <th class="text-center">OK</th>
                                <th class="text-center">Observación</th>
                                <th class="text-center">Crítico</th>
                                <th>Observaciones</th>
                                <th class="text-center" style="width: 3.5rem;"></th>
                            </tr>
                        </thead>
                        <tbody id="tbody-lista-revision">
                            @php
                                $detalleOld = old('detalle');
                                $filasRevision = [];
                                if (is_array($detalleOld) && count($detalleOld) > 0) {
                                    foreach ($detalleOld as $item) {
                                        $filasRevision[] = [
                                            'concepto' => $item['concepto'] ?? '',
                                            'resultado' => $item['resultado'] ?? 'OK',
                                            'observaciones' => $item['observaciones'] ?? '',
                                        ];
                                    }
                                } else {
                                    foreach ($conceptos as $concepto) {
                                        $filasRevision[] = [
                                            'concepto' => $concepto,
                                            'resultado' => 'OK',
                                            'observaciones' => '',
                                        ];
                                    }
                                }
                            @endphp
                            @foreach ($filasRevision as $indice => $fila)
                                <tr class="fila-concepto-revision" data-idx="{{ $indice }}">
                                    <td>
                                        <input type="text" class="form-control form-control-sm"
                                            name="detalle[{{ $indice }}][concepto]"
                                            value="{{ $fila['concepto'] }}" maxlength="200" required>
                                    </td>
                                    <td class="text-center">
                                        <input type="radio" name="detalle[{{ $indice }}][resultado]" value="OK" required
                                            {{ ($fila['resultado'] ?? 'OK') === 'OK' ? 'checked' : '' }}>
                                    </td>
                                    <td class="text-center">
                                        <input type="radio" name="detalle[{{ $indice }}][resultado]" value="OBSERVACION"
                                            {{ ($fila['resultado'] ?? '') === 'OBSERVACION' ? 'checked' : '' }}>
                                    </td>
                                    <td class="text-center">
                                        <input type="radio" name="detalle[{{ $indice }}][resultado]" value="CRITICO"
                                            {{ ($fila['resultado'] ?? '') === 'CRITICO' ? 'checked' : '' }}>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control text"
                                            name="detalle[{{ $indice }}][observaciones]"
                                            value="{{ $fila['observaciones'] }}" maxlength="500">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm btn-quitar-concepto"
                                            title="Quitar de la lista">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div id="lista-revision-vacia" class="alert alert-warning border-0 mt-2 mb-0 fs-8 d-none">
                    Agregue al menos un concepto a la lista de revisión.
                </div>

                <h6 class="text-marino mt-4 mb-2">Evidencia fotográfica</h6>
                <div class="row">
                    @foreach ($categoriasFotos as $campo => $etiqueta)
                        <div class="col-md-6 col-12 mt-2">
                            <label class="form-label">{{ $etiqueta }}</label>
                            <input type="file" class="form-control" name="{{ $campo }}[]" multiple
                                accept=".jpg,.jpeg,.png,.webp,image/*">
                        </div>
                    @endforeach
                </div>

                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-baseColor">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar inspección
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow p-3 bg-body rounded-5">
        <div class="card-header bg-body border-0">
            <h5 class="text-secondary mb-1">
                <i class="fa-solid fa-clock-rotate-left me-2"></i>Historial de inspecciones
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-stripped table-hover mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Inspector</th>
                        <th>Resultado</th>
                        <th>Mantenimiento</th>
                        <th>Estatus</th>
                        <th>Fotos</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($historial as $item)
                        <tr>
                            <td>{{ $item->fecha_inspeccion->format('d/m/Y') }}</td>
                            <td>
                                @if ($item->inspector)
                                    {{ $item->inspector->primer_nombre }} {{ $item->inspector->apellido_paterno }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $item->resultado_general_texto }}</td>
                            <td>{{ $item->requiere_mantenimiento ? 'Sí' : 'No' }}</td>
                            <td>
                                @if ($item->estatus === 'CERRADA')
                                    <span class="badge badge-success-dark fs-9">Cerrada</span>
                                @else
                                    <span class="badge badge-warning-dark fs-9">Abierta</span>
                                @endif
                            </td>
                            <td>{{ $item->fotos_count }}</td>
                            <td>
                                <a href="{{ route('produccion.maquinas.inspecciones.detalle', [$maquina->id, $item->id]) }}"
                                    class="btn btn-primary btn-sm">
                                    <i class="fa-solid fa-eye"></i> Ver
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-muted text-center py-4">
                                No hay inspecciones registradas para esta máquina.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="{{ asset('js/validation.js') }}"></script>
<script>
(function () {
    const tbody = document.getElementById('tbody-lista-revision');
    const btnAdd = document.getElementById('btn-agregar-concepto-revision');
    const vacia = document.getElementById('lista-revision-vacia');
    if (!tbody || !btnAdd) return;

    let nextIdx = tbody.querySelectorAll('tr.fila-concepto-revision').length;

    function actualizarVacia() {
        const n = tbody.querySelectorAll('tr.fila-concepto-revision').length;
        if (vacia) vacia.classList.toggle('d-none', n > 0);
    }

    function filaHtml(idx) {
        return `
            <tr class="fila-concepto-revision" data-idx="${idx}">
                <td>
                    <input type="text" class="form-control form-control-sm"
                        name="detalle[${idx}][concepto]" value="" maxlength="200" required
                        placeholder="Nuevo concepto…">
                </td>
                <td class="text-center">
                    <input type="radio" name="detalle[${idx}][resultado]" value="OK" checked required>
                </td>
                <td class="text-center">
                    <input type="radio" name="detalle[${idx}][resultado]" value="OBSERVACION">
                </td>
                <td class="text-center">
                    <input type="radio" name="detalle[${idx}][resultado]" value="CRITICO">
                </td>
                <td>
                    <input type="text" class="form-control text"
                        name="detalle[${idx}][observaciones]" value="" maxlength="500">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm btn-quitar-concepto" title="Quitar de la lista">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>`;
    }

    btnAdd.addEventListener('click', function () {
        tbody.insertAdjacentHTML('beforeend', filaHtml(nextIdx++));
        const input = tbody.querySelector('tr.fila-concepto-revision:last-child input[name*="[concepto]"]');
        if (input) input.focus();
        actualizarVacia();
    });

    tbody.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-quitar-concepto');
        if (!btn) return;
        const tr = btn.closest('tr.fila-concepto-revision');
        if (tr) tr.remove();
        actualizarVacia();
    });

    actualizarVacia();
})();
</script>
@endsection
