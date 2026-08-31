@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid acciones-config-page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="{{ route('produccion.maquinas') }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Catálogo de máquinas
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Mantenimientos</h2>
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
                <i class="fa-solid fa-circle-plus me-2"></i>Registrar mantenimiento
            </h5>
            <p class="text-muted fs-8 mb-0">Programe el mantenimiento y adjunte fotografías de evidencia.</p>
        </div>
        <div class="card-body pt-0">
            <form action="{{ route('produccion.maquinas.mantenimientos.store', $maquina->id) }}" method="POST"
                enctype="multipart/form-data" class="modern-form needs-validation" novalidate>
                @csrf
                <div class="row">
                    <div class="col-md-3 col-12 mt-2">
                        <label class="form-label">Tipo <span class="text-danger">*</span></label>
                        <select class="form-select" name="tipo" required> 
                            <option value="CORRECTIVO" {{ old('tipo') === 'CORRECTIVO' ? 'selected' : '' }}>Correctivo</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-12 mt-2">
                        <label class="form-label">Fecha reporte <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="fecha_reporte"
                            value="{{ old('fecha_reporte', date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-3 col-12 mt-2">
                        <label class="form-label">Fecha programada <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="fecha_programada"
                            value="{{ old('fecha_programada', date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-3 col-12 mt-2">
                        <label class="form-label">Técnico</label>
                        <select class="form-select" name="responsable_id">
                            <option value="">— Seleccione —</option>
                            @foreach ($empleados as $empleado)
                                <option value="{{ $empleado->id }}" {{ (int) old('responsable_id') === (int) $empleado->id ? 'selected' : '' }}>
                                    {{ $empleado->primer_nombre }} {{ $empleado->segundo_nombre }}
                                    {{ $empleado->apellido_paterno }} {{ $empleado->apellido_materno }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 col-12 mt-2">
                        <label class="form-label">Máquina (ID)</label>
                        <input type="text" class="form-control" value="{{ $maquina->codigo }} — {{ $maquina->nombre }}" readonly>
                    </div>
                    <div class="col-md-3 col-12 mt-2">
                        <label class="form-label">Estatus <span class="text-danger">*</span></label>
                        <select class="form-select" name="estatus" required>
                            <option value="PENDIENTE" {{ old('estatus', 'PENDIENTE') === 'PENDIENTE' ? 'selected' : '' }}>Pendiente</option>
                            <option value="EN_PROCESO" {{ old('estatus') === 'EN_PROCESO' ? 'selected' : '' }}>En proceso</option>
                            <option value="FINALIZADO" {{ old('estatus') === 'FINALIZADO' ? 'selected' : '' }}>Finalizado</option>
                            <option value="CANCELADO" {{ old('estatus') === 'CANCELADO' ? 'selected' : '' }}>Cancelado</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-12 mt-2" id="wrap-causa-falla">
                        <label class="form-label">Causa (CF-xx) <span class="text-danger">*</span></label>
                        <select class="form-select" name="causa_falla_id" id="causa_falla_id" required>
                            <option value="">— Seleccione causa —</option>
                            @foreach ($causasFalla as $causa)
                                <option value="{{ $causa->id }}" {{ (int) old('causa_falla_id') === (int) $causa->id ? 'selected' : '' }}>
                                    {{ $causa->clave }} — {{ $causa->nombre }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text fs-9">Obligatoria en mantenimiento correctivo.</div>
                    </div>
                </div>
                <div class="row">
                    @include('Produccion.partials.select_incidencia_maquina', ['cols' => 12])
                </div>
                <div class="row">
                    <div class="col-md-3 col-12 mt-2">
                        <label class="form-label">Hora paro</label>
                        <input type="datetime-local" class="form-control" name="fecha_inicio" id="fecha_inicio" value="{{ old('fecha_inicio') }}">
                    </div>
                    <div class="col-md-3 col-12 mt-2">
                        <label class="form-label">Hora reinicio</label>
                        <input type="datetime-local" class="form-control" name="fecha_fin" id="fecha_fin" value="{{ old('fecha_fin') }}">
                    </div>
                    <div class="col-md-2 col-12 mt-2">
                        <label class="form-label">Duración paro (h)</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="horas_paro" id="horas_paro" value="{{ old('horas_paro') }}">
                    </div>
                    <div class="col-md-2 col-12 mt-2">
                        <label class="form-label">Costo mano obra ($)</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="costo_mano_obra" id="costo_mano_obra" value="{{ old('costo_mano_obra') }}">
                    </div>
                    <div class="col-md-2 col-12 mt-2">
                        <label class="form-label">Costo total ($)</label>
                        <input type="text" class="form-control" id="costo_total_preview" value="—" readonly>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-5 col-12 mt-2">
                        <label class="form-label">Refacción usada (REF-)</label>
                        <select class="form-select" name="producto_refaccion_id" id="producto_refaccion_id">
                            <option value="">— Sin refacción —</option>
                            @foreach ($refaccionesDisponibles as $ref)
                                <option value="{{ $ref->id_producto }}"
                                    data-costo="{{ (float) ($ref->costo_compra ?: $ref->precio_unitario ?: 0) }}"
                                    data-stock="{{ (float) $ref->cantidad_existente }}"
                                    {{ (int) old('producto_refaccion_id') === (int) $ref->id_producto ? 'selected' : '' }}>
                                    {{ $ref->sku ?: $ref->nombre }} — {{ $ref->nombre }}
                                    (stock: {{ number_format((float) $ref->cantidad_existente, 2) }})
                                </option>
                            @endforeach
                        </select>
                        @if ($refaccionesDisponibles->isEmpty())
                            <div class="form-text text-warning fs-9">No hay refacciones en la ubicación de esta máquina.</div>
                        @endif
                    </div>
                    <div class="col-md-2 col-12 mt-2">
                        <label class="form-label">Cant. refacción</label>
                        <input type="number" step="0.001" min="0" class="form-control" name="cantidad_refaccion" id="cantidad_refaccion"
                            value="{{ old('cantidad_refaccion') }}">
                    </div>
                    <div class="col-md-2 col-12 mt-2">
                        <label class="form-label">Costo refacción ($)</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="costo_refacciones" id="costo_refacciones"
                            value="{{ old('costo_refacciones') }}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 mt-2">
                        <label class="form-label">Descripción falla <span class="text-danger">*</span></label>
                        <textarea class="form-control text" name="descripcion" rows="3" required>{{ old('descripcion') }}</textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 mt-2">
                        <label class="form-label">Resultado</label>
                        <textarea class="form-control text" name="resultado" rows="2">{{ old('resultado') }}</textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 col-12 mt-2">
                        <label class="form-label">Fotografías de evidencia</label>
                        <input type="file" class="form-control" name="fotografias[]" multiple
                            accept=".jpg,.jpeg,.png,.webp,image/*">
                        <div class="form-text">JPG, PNG o WEBP. Máximo 10 MB por imagen.</div>
                    </div>
                    <div class="col-md-6 col-12 mt-2">
                        <label class="form-label">Descripción de las fotografías</label>
                        <input type="text" class="form-control text" name="descripcion_fotos" maxlength="500"
                            value="{{ old('descripcion_fotos') }}" placeholder="Texto común para las fotos subidas">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-baseColor">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar mantenimiento
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow p-3 bg-body rounded-5">
        <div class="card-header bg-body border-0">
            <h5 class="text-secondary mb-1">
                <i class="fa-solid fa-clock-rotate-left me-2"></i>Historial de mantenimientos
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-stripped table-hover mb-0">
                <thead>
                    <tr>
                        <th>Orden Nº</th>
                        <th>Fecha reporte</th>
                        <th>Causa (CF-xx)</th>
                        <th>Descripción falla</th>
                        <th>Hora paro</th>
                        <th>Hora reinicio</th>
                        <th>Duración paro (h)</th>
                        <th>Técnico</th>
                        <th>Refacción usada</th>
                        <th>Cant.</th>
                        <th>Costo refacción</th>
                        <th>Costo MO</th>
                        <th>Costo total</th>
                        <th>Estado</th>
                        <th>Fotos</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($historial as $item)
                        @php
                            $refLinea = $item->refacciones->first();
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $item->folio ?: ('#' . $item->id) }}</td>
                            <td>{{ ($item->fecha_reporte ?? $item->fecha_programada)?->format('d/m/Y') }}</td>
                            <td>
                                @if ($item->causaFalla)
                                    <span class="fw-semibold">{{ $item->causaFalla->clave }}</span>
                                    <div class="text-muted fs-9">{{ $item->causaFalla->nombre }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <span class="d-inline-block text-truncate" style="max-width: 180px;" title="{{ $item->descripcion }}">
                                    {{ $item->descripcion }}
                                </span>
                            </td>
                            <td>{{ $item->fecha_inicio ? $item->fecha_inicio->format('d/m H:i') : '—' }}</td>
                            <td>{{ $item->fecha_fin ? $item->fecha_fin->format('d/m H:i') : '—' }}</td>
                            <td>{{ $item->horas_paro !== null ? number_format($item->horas_paro, 2) : '—' }}</td>
                            <td>
                                @if ($item->responsable)
                                    {{ $item->responsable->primer_nombre }} {{ $item->responsable->apellido_paterno }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($refLinea && $refLinea->producto)
                                    {{ $refLinea->producto->sku ?: $refLinea->producto->nombre }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $refLinea ? number_format((float) $refLinea->cantidad, 2) : '—' }}</td>
                            <td>{{ $item->costo_refacciones !== null ? '$' . number_format($item->costo_refacciones, 2) : '—' }}</td>
                            <td>{{ $item->costo_mano_obra !== null ? '$' . number_format($item->costo_mano_obra, 2) : '—' }}</td>
                            <td>{{ $item->costo_total !== null ? '$' . number_format($item->costo_total, 2) : '—' }}</td>
                            <td>
                                @if ($item->estatus === 'FINALIZADO')
                                    <span class="badge badge-success-dark fs-9">Finalizado</span>
                                @elseif ($item->estatus === 'EN_PROCESO')
                                    <span class="badge badge-secondary fs-9">En proceso</span>
                                @elseif ($item->estatus === 'CANCELADO')
                                    <span class="badge badge-danger-dark fs-9">Cancelado</span>
                                @else
                                    <span class="badge badge-warning-dark fs-9">Pendiente</span>
                                @endif
                            </td>
                            <td>{{ $item->fotos_count }}</td>
                            <td>
                                <a href="{{ route('produccion.maquinas.mantenimientos.detalle', [$maquina->id, $item->id]) }}"
                                    class="btn btn-primary btn-sm">
                                    <i class="fa-solid fa-eye"></i> Ver
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="16" class="text-muted text-center py-4">
                                No hay mantenimientos registrados para esta máquina.
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
    const inicio = document.getElementById('fecha_inicio');
    const fin = document.getElementById('fecha_fin');
    const horas = document.getElementById('horas_paro');
    const refSelect = document.getElementById('producto_refaccion_id');
    const cant = document.getElementById('cantidad_refaccion');
    const costoRef = document.getElementById('costo_refacciones');
    const costoMo = document.getElementById('costo_mano_obra');
    const totalPreview = document.getElementById('costo_total_preview');

    function calcHoras() {
        if (!inicio.value || !fin.value) return;
        const a = new Date(inicio.value);
        const b = new Date(fin.value);
        if (isNaN(a) || isNaN(b) || b < a) return;
        const h = (b - a) / 36e5;
        horas.value = h.toFixed(2);
    }

    function calcCostoRef() {
        const opt = refSelect.options[refSelect.selectedIndex];
        if (!opt || !opt.value || !cant.value) return;
        const unit = parseFloat(opt.dataset.costo || '0');
        const qty = parseFloat(cant.value || '0');
        if (!isNaN(unit) && !isNaN(qty) && qty > 0) {
            costoRef.value = (unit * qty).toFixed(2);
        }
        calcTotal();
    }

    function calcTotal() {
        const mo = parseFloat(costoMo.value || '0') || 0;
        const rf = parseFloat(costoRef.value || '0') || 0;
        if (!costoMo.value && !costoRef.value) {
            totalPreview.value = '—';
            return;
        }
        totalPreview.value = '$' + (mo + rf).toFixed(2);
    }

    inicio.addEventListener('change', calcHoras);
    fin.addEventListener('change', calcHoras);
    refSelect.addEventListener('change', calcCostoRef);
    cant.addEventListener('input', calcCostoRef);
    costoRef.addEventListener('input', calcTotal);
    costoMo.addEventListener('input', calcTotal);
    calcTotal();
})();
</script>
@endsection
