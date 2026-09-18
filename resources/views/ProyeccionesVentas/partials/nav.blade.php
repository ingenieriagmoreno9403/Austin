@php
    $ccPage = $ccPage ?? '';
    $modPermisos = $modPermisos ?? [];
@endphp
<nav class="cc-subnav" aria-label="Proyecciones de ventas">
    @if(!empty($modPermisos['admin']))
    <a href="{{ route('pv.admin') }}" class="{{ in_array($ccPage, ['admin', 'admin-ciclo', 'asignacion'], true) ? 'is-active' : '' }}">
        <i class="fa-solid fa-sliders me-1"></i> Proyecciones de ventas
    </a>
    @endif
    @if(!empty($modPermisos['captura']) || !empty($modPermisos['visor']))
    <a href="{{ route('pv.control') }}" class="{{ $ccPage === 'control' ? 'is-active' : '' }}">
        <i class="fa-solid fa-table me-1"></i> Captura
    </a>
    @endif
    @if(!empty($modPermisos['analisis']))
    <a href="{{ route('pv.analisis') }}" class="{{ $ccPage === 'analisis' ? 'is-active' : '' }}">
        <i class="fa-solid fa-chart-line me-1"></i> Análisis de Proyecciones
    </a>
    @endif
</nav>
