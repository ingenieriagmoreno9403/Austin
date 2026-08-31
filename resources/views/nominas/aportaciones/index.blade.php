@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">
<link href="{{ asset('css/tables.css') }}" rel="stylesheet">

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-building-columns"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="/Nominas" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Nómina
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Aportaciones Obrero-Patronales</h2>
                        <p class="text-muted mb-0">Cálculo bimestral de cuotas patronales IMSS e INFONAVIT</p>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="btn btn-baseColor fs-7 mb-2" type="button" onclick="abrirNuevoPeriodoAportaciones()">
                        <i class="fa-solid fa-plus me-1"></i> Nuevo periodo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="table-responsive">
            <table class="table table-stripped table-hover display" id="tablenomina">
                <thead>
                    <tr>
                        <th class="text-truncate">Acciones</th>
                        <th class="text-truncate">Identificador</th>
                        <th class="text-truncate">Fecha inicio</th>
                        <th class="text-truncate">Fecha fin</th>
                        <th class="text-truncate">Días periodo</th>
                        <th class="text-truncate">Creado</th>
                        <th class="text-truncate">Actualizado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($varaportaciones as $item)
                        <tr>
                            <td class="text-truncate">
                                <a class="btn btn-primary m-0" href="{{ route('ver_aportaciones_patronales', $item->id) }}" title="Ver detalle">
                                    <i class="fa-solid fa-eye fs-8 me-1"></i> Detalle
                                </a>
                                <button class="btn btn-danger m-0" type="button" title="Eliminar" onclick="confirmarEliminarAportacion({{ $item->id }})">
                                    <i class="fa-solid fa-trash fs-8"></i> Eliminar
                                </button>
                            </td>
                            <td class="text-truncate fw-semibold">{{ $item->nombre }}</td>
                            <td class="text-truncate">{{ $item->fecha_inicio ? date('d/m/Y', strtotime($item->fecha_inicio)) : '-' }}</td>
                            <td class="text-truncate">{{ $item->fecha_fin ? date('d/m/Y', strtotime($item->fecha_fin)) : '-' }}</td>
                            <td class="text-truncate">{{ $item->dias_periodo }}</td>
                            <td class="text-truncate">
                                @if($item->created_at)
                                    {{ date('d/m/Y h:i A', strtotime($item->created_at)) }} - {{ $item->created_by }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-truncate">
                                @if($item->updated_at)
                                    {{ date('d/m/Y h:i A', strtotime($item->updated_at)) }} - {{ $item->updated_by }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No hay periodos registrados. Cree uno con el rango de fechas del bimestre.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function abrirNuevoPeriodoAportaciones() {
    Swal.fire({
        title: 'Nuevo periodo de aportaciones',
        html: `
            <div class="text-start">
                <label class="form-label fw-semibold" for="swal_nombre_periodo">Identificador del periodo</label>
                <input type="text" class="form-control mb-3" id="swal_nombre_periodo" maxlength="150" placeholder="Ej. Bimestre 1 - 2026">
                <label class="form-label fw-semibold" for="swal_fecha_inicio">Fecha inicio</label>
                <input type="date" class="form-control mb-3" id="swal_fecha_inicio">
                <label class="form-label fw-semibold" for="swal_fecha_fin">Fecha fin</label>
                <input type="date" class="form-control" id="swal_fecha_fin">
            </div>
        `,
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#475569',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Calcular y guardar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        focusConfirm: false,
        preConfirm: () => {
            const nombre = document.getElementById('swal_nombre_periodo')?.value.trim();
            const fechaInicio = document.getElementById('swal_fecha_inicio')?.value;
            const fechaFin = document.getElementById('swal_fecha_fin')?.value;

            if (!nombre) {
                Swal.showValidationMessage('Ingrese el identificador del periodo.');
                return false;
            }
            if (!fechaInicio) {
                Swal.showValidationMessage('Seleccione la fecha de inicio.');
                return false;
            }
            if (!fechaFin) {
                Swal.showValidationMessage('Seleccione la fecha de fin.');
                return false;
            }
            if (fechaFin < fechaInicio) {
                Swal.showValidationMessage('La fecha fin debe ser igual o posterior a la fecha inicio.');
                return false;
            }

            return { nombre, fechaInicio, fechaFin };
        }
    }).then((result) => {
        if (!result.isConfirmed) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('crear_aportaciones_patronales') }}';
        form.innerHTML = `
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="nombre" value="${result.value.nombre.replace(/"/g, '&quot;')}">
            <input type="hidden" name="fecha_inicio" value="${result.value.fechaInicio}">
            <input type="hidden" name="fecha_fin" value="${result.value.fechaFin}">
        `;
        document.body.appendChild(form);
        form.submit();
    });
}

function confirmarEliminarAportacion(id) {
    Swal.fire({
        title: '¿Está seguro?',
        text: 'Se eliminará el periodo y todos sus cálculos.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#475569',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `/Nominas/AportacionesPatronales/Eliminar/${id}`;
        }
    });
}
</script>

<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/table.js') }}"></script>
@endsection
