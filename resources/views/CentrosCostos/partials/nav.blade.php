@php
    $ccPage = $ccPage ?? '';
    $ccNavOmitAdmin = ! empty($ccNavOmitAdmin);
@endphp
<!-- <nav class="cc-subnav" aria-label="Centros de costo">
    @unless($ccNavOmitAdmin)
    <a href="{{ route('centros.admin') }}" class="{{ in_array($ccPage, ['admin', 'admin-ciclo', 'asignacion'], true) ? 'is-active' : '' }}">
        <i class="fa-solid fa-sliders me-1"></i> Budgets y Asignaciones
    </a>
    @endunless
    <a href="{{ route('centros.control') }}" class="{{ $ccPage === 'control' ? 'is-active' : '' }}">
        <i class="fa-solid fa-table me-1"></i> Captura e Indicadores
    </a>
    <a href="{{ route('centros.analisis') }}" class="{{ $ccPage === 'analisis' ? 'is-active' : '' }}">
        <i class="fa-solid fa-chart-line me-1"></i> Análisis de Progreso
    </a>
</nav> -->
