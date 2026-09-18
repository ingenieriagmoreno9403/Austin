@php
    $ccPage = $ccPage ?? '';
    $modPermisos = $modPermisos ?? [];
@endphp
<nav class="cc-subnav" aria-label="Centros de costo">
    @if(!empty($modPermisos['admin']))
    <a href="{{ route('centros.admin') }}" class="{{ in_array($ccPage, ['admin', 'admin-ciclo', 'asignacion'], true) ? 'is-active' : '' }}">
        <i class="fa-solid fa-sliders me-1"></i> Budgets y Asignaciones
    </a>
    @endif
    @if(!empty($modPermisos['captura']) || !empty($modPermisos['visor']))
    <a href="{{ route('centros.control') }}" class="{{ $ccPage === 'control' ? 'is-active' : '' }}">
        <i class="fa-solid fa-table me-1"></i> Captura e Indicadores
    </a>
    @endif
    @if(!empty($modPermisos['analisis']))
    <a href="{{ route('centros.analisis') }}" class="{{ $ccPage === 'analisis' ? 'is-active' : '' }}">
        <i class="fa-solid fa-chart-line me-1"></i> Análisis de Progreso
    </a>
    @endif
</nav>
