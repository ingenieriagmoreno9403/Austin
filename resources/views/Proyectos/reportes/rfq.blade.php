@extends('layouts.app')
@section('content')

<div class="container-fluid format_page">
    <div class="row">
        <div class="col-lg-6 col-12 start-center">
            <h3 class="mt-1">Reportes por RFQ</h3>
            <span class="fs-8 text-muted">RFQs por vendedor responsable</span>
        </div>
        <div class="col-lg-6 col-12 center-end">
            <a href="{{ route('proyectos.index') }}" class="btn btn-secondary fs-7 mb-2">
                <i class="fa-solid fa-arrow-left"></i> Volver a Proyectos
            </a>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-lg-5 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fa-solid fa-chart-pie"></i> RFQs por Vendedor</h6>
                </div>
                <div class="card-body">
                    <canvas id="chartRfqs"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-7 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fa-solid fa-table"></i> Listado de RFQs</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="tablaRfqs">
                            <thead class="table-dark">
                                <tr>
                                    <th>Vendedor</th>
                                    <th>Folio</th>
                                    <th>Proyecto</th>
                                    <th>RFQ</th>
                                    <th>Fecha Inicio</th>
                                    <th>Fecha Término</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rfqs as $r)
                                    <tr>
                                        <td>{{ $r->vendedor ?? 'SIN ASIGNAR' }}</td>
                                        <td>{{ $r->folio }}</td>
                                        <td>{{ $r->nombre }}</td>
                                        <td class="text-truncate" style="max-width:220px;" title="{{ $r->rfq }}">{{ $r->rfq }}</td>
                                        <td>{{ \Carbon\Carbon::parse($r->fecha_inicio)->format('d/m/Y') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($r->fecha_limite)->format('d/m/Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const counts = @json($porVendedor);
    const labels = Object.keys(counts);
    const data = labels.map(k => counts[k]);
    const ctx = document.getElementById('chartRfqs');
    new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets: [{ label: 'RFQs', data, backgroundColor: '#f39c12' }] },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });

    $('#tablaRfqs').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
        order: [[0, 'asc']]
    });
});
</script>
@endpush


