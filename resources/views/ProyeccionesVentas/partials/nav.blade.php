@php
    $ccPage = $ccPage ?? '';
@endphp
<nav class="cc-subnav" aria-label="Proyecciones de ventas">
    <a href="{{ route('pv.admin') }}" class="{{ in_array($ccPage, ['admin', 'admin-ciclo', 'asignacion'], true) ? 'is-active' : '' }}">
        <i class="fa-solid fa-sliders me-1"></i> Proyecciones de ventas
    </a>
    <a href="{{ route('pv.control') }}" class="{{ $ccPage === 'control' ? 'is-active' : '' }}">
        <i class="fa-solid fa-table me-1"></i> Captura
    </a>
    <a href="{{ route('pv.analisis') }}" class="{{ $ccPage === 'analisis' ? 'is-active' : '' }}">
        <i class="fa-solid fa-chart-line me-1"></i> Análisis de Proyecciones
    </a>
    <a href="{{ route('pv.costos') }}" class="{{ $ccPage === 'costos' ? 'is-active' : '' }}">
        <i class="fa-solid fa-tags me-1"></i> Precios de productos
    </a>
</nav>
