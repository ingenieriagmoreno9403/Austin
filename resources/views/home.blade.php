@extends('layouts.app')
@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap');

    .home {
        --ink: #1a1a1a;
        --muted: #6b6b6b;
        --line: #ececec;
        --red: #e30613;
        --soft: #f7f7f8;
        --card: #ffffff;
        --shadow: 0 1px 2px rgba(0, 0, 0, 0.04), 0 8px 24px rgba(0, 0, 0, 0.04);
        width: 100%;
        max-width: none;
        margin: 0;
        padding: 1.35rem 1.5rem 2.5rem;
        box-sizing: border-box;
        color: var(--ink);
        font-family: 'DM Sans', system-ui, sans-serif;
    }

    .home-panel {
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 16px;
        box-shadow: var(--shadow);
    }

    .home-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.25rem;
        padding: 1.35rem 1.5rem;
        overflow: hidden;
        position: relative;
    }
    .home-top::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: var(--red);
    }
    .home-brand {
        margin: 0 0 0.35rem;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: var(--red);
    }
    .home h1 {
        margin: 0;
        font-size: clamp(1.55rem, 2.6vw, 1.95rem);
        font-weight: 650;
        letter-spacing: -0.03em;
        line-height: 1.15;
    }
    .home-meta {
        margin: 0.4rem 0 0;
        font-size: 0.88rem;
        color: var(--muted);
    }
    .home-logo-wrap {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        flex: 0 1 auto;
        min-width: 0;
        background: transparent;
    }
    .home-logo {
        height: 84px;
        width: auto;
        max-width: min(520px, 46vw);
        object-fit: contain;
        display: block;
    }

    .home-block {
        margin-top: 1rem;
        padding: 1.35rem 1.5rem 1.4rem;
    }
    .home-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1.15rem;
    }
    .home h2 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 650;
        letter-spacing: -0.02em;
    }
    .home-status {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.28rem 0.7rem;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        background: var(--soft);
        color: var(--muted);
        border: 1px solid var(--line);
    }
    .home-status.is-on {
        background: #fff4f4;
        color: var(--red);
        border-color: rgba(227, 6, 19, 0.16);
    }
    .home-status.is-on::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--red);
    }

    .home-metrics {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 0.7rem;
        margin: 0 0 1.15rem;
    }
    .home-metrics > div {
        background: var(--soft);
        border-radius: 12px;
        padding: 0.9rem 0.95rem 0.95rem;
    }
    .home-metrics dt {
        margin: 0 0 0.35rem;
        font-size: 0.68rem;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--muted);
    }
    .home-metrics dd {
        margin: 0;
        font-size: 1.4rem;
        font-weight: 700;
        letter-spacing: -0.03em;
        line-height: 1;
    }

    .home-nav {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 0.95rem;
    }
    .home-nav a {
        color: var(--ink);
        text-decoration: none;
        font-size: 0.86rem;
        font-weight: 600;
        padding: 0.48rem 0.85rem;
        border-radius: 999px;
        border: 1px solid var(--line);
        background: #fff;
    }
    .home-nav a:first-child {
        background: var(--red);
        border-color: var(--red);
        color: #fff;
    }
    .home-nav a:hover {
        border-color: #d4d4d4;
        background: var(--soft);
        color: var(--ink);
    }
    .home-nav a:first-child:hover {
        background: #c70510;
        border-color: #c70510;
        color: #fff;
    }

    .home-note {
        margin: 0;
        font-size: 0.86rem;
        color: var(--muted);
        line-height: 1.5;
    }

    .home-lines {
        margin-top: 1rem;
        padding: 0.35rem 0 0.5rem;
    }
    .home-lines-head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.35rem 0.35rem;
    }
    .home-lines-head h2 { margin: 0; }
    .home-lines-empty { padding: 0.4rem 1.35rem 1rem; }
    .home-lines-list {
        list-style: none;
        margin: 0;
        padding: 0.35rem 0.6rem 0.4rem;
    }
    .home-line {
        display: grid;
        grid-template-columns: 92px minmax(180px, 1.4fr) minmax(180px, 0.9fr) minmax(190px, 1fr);
        gap: 0.85rem 1.1rem;
        align-items: center;
        text-decoration: none;
        color: var(--ink);
        padding: 0.85rem 0.75rem;
        border-bottom: 1px solid var(--line);
    }
    .home-lines-list li:last-child .home-line { border-bottom: none; }
    .home-line:hover { background: var(--soft); }
    .home-kind {
        justify-self: start;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        padding: 0.28rem 0.55rem;
        border-radius: 999px;
        border: 1px solid var(--line);
        background: var(--soft);
        color: var(--muted);
    }
    .home-kind.is-gasto {
        background: #1a1a1a;
        color: #fff;
        border-color: #1a1a1a;
    }
    .home-kind.is-venta {
        background: #fff;
        color: #1a1a1a;
        border-color: #c8c8c8;
    }
    .home-line-name strong {
        display: block;
        font-size: 0.95rem;
        font-weight: 650;
        letter-spacing: -0.02em;
        line-height: 1.25;
    }
    .home-line-name em,
    .home-line-date em,
    .home-line-progress em {
        display: block;
        margin-top: 0.12rem;
        font-style: normal;
        font-size: 0.75rem;
        color: var(--muted);
    }
    .home-line-date strong {
        display: block;
        font-size: 0.86rem;
        font-weight: 650;
    }
    .home-line-date .is-soon { color: var(--red); }
    .home-line-progress { min-width: 0; }
    .home-meter {
        display: grid;
        grid-template-columns: 3.1rem minmax(72px, 1fr);
        align-items: center;
        gap: 0.6rem;
    }
    .home-meter strong {
        font-size: 0.95rem;
        font-weight: 700;
        letter-spacing: -0.03em;
        line-height: 1;
    }
    .home-line-progress > em {
        margin-top: 0.28rem;
    }
    .home-bar {
        display: block;
        height: 12px;
        border-radius: 999px;
        background: #e7e7e7;
        border: 1px solid #dedede;
        overflow: hidden;
    }
    .home-bar i {
        display: block;
        height: 100%;
        min-width: 0;
        border-radius: inherit;
        background: #dc2626;
    }
    .home-line-progress.is-stop .home-meter strong { color: #dc2626; }
    .home-line-progress.is-stop .home-bar i { background: #dc2626; }
    .home-line-progress.is-warn .home-meter strong { color: #d97706; }
    .home-line-progress.is-warn .home-bar i { background: #d97706; }
    .home-line-progress.is-go .home-meter strong { color: #16a34a; }
    .home-line-progress.is-go .home-bar i { background: #16a34a; }
    .home-lines-nav {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: center;
        padding: 0.9rem 1.15rem 1.05rem;
        border-top: 1px solid var(--line);
    }
    .home-lines-group {
        display: inline-flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.4rem;
        padding: 0.4rem 0.45rem 0.4rem 0.55rem;
        border-radius: 14px;
        border: 1px solid var(--line);
        background: var(--soft);
    }
    .home-lines-group span {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        padding-right: 0.25rem;
        color: #1a1a1a;
    }
    .home-lines-group a {
        text-decoration: none;
        font-size: 0.8rem;
        font-weight: 650;
        padding: 0.38rem 0.75rem;
        border-radius: 999px;
        border: 1px solid #1a1a1a;
        background: #1a1a1a;
        color: #fff;
    }
    .home-lines-group a:hover {
        background: #fff;
        color: #1a1a1a;
    }
    .home-lines-group.is-gasto {
        background: #f3f3f3;
        border-color: #e0e0e0;
    }
    .home-lines-group.is-venta {
        background: #fff;
        border-color: #d4d4d4;
    }
    .home-lines-group.is-venta a {
        background: #fff;
        color: #1a1a1a;
        border-color: #bdbdbd;
    }
    .home-lines-group.is-venta a:hover {
        background: #1a1a1a;
        border-color: #1a1a1a;
        color: #fff;
    }

    .home-cal {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(260px, 340px);
        gap: 0;
        margin-top: 1rem;
        overflow: hidden;
        align-items: stretch;
    }
    .home-cal-main {
        padding: 1.25rem 1.35rem 1.35rem;
        border-right: 1px solid var(--line);
        min-width: 0;
    }
    .home-cal-head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .home-cal-month {
        font-size: 0.82rem;
        color: var(--muted);
        text-transform: capitalize;
        font-weight: 500;
    }
    .home-cal-week {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 0.3rem;
        margin-bottom: 0.4rem;
        font-size: 0.66rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--muted);
        text-align: center;
    }
    .home-cal-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 0.3rem;
    }
    .home-cal-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 0.7rem 1rem;
        margin-top: 0.85rem;
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--muted);
    }
    .home-cal-legend span {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .home-cal-legend i {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        display: block;
    }
    .home-cal-legend .is-ok { background: #16a34a; }
    .home-cal-legend .is-warn { background: #d97706; }
    .home-cal-legend .is-late { background: var(--red); }

    .home-cal-day {
        height: 46px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border: 1px solid transparent;
        background: var(--soft);
        border-radius: 10px;
        font-size: 0.84rem;
        font-weight: 600;
        color: var(--ink);
        cursor: pointer;
        padding: 0;
    }
    .home-cal-day.is-empty {
        visibility: hidden;
        pointer-events: none;
    }
    .home-cal-day:hover {
        background: #fff;
        border-color: #e4e4e4;
    }
    .home-cal-day.is-selected {
        background: var(--red);
        border-color: var(--red);
        color: #fff;
    }
    .home-cal-day .dot {
        width: 4px;
        height: 4px;
        border-radius: 50%;
        background: var(--red);
        margin-top: 3px;
    }
    .home-cal-day.is-selected .dot { background: #fff; }
    .home-cal-day.is-ok .dot { background: #16a34a; }
    .home-cal-day.is-warn .dot { background: #d97706; }
    .home-cal-day.is-late .dot { background: var(--red); }
    .home-cal-day.is-selected.is-ok .dot,
    .home-cal-day.is-selected.is-warn .dot,
    .home-cal-day.is-selected.is-late .dot { background: #fff; }

    .home-cal-side {
        padding: 1.25rem 1.2rem 1.35rem;
        background: var(--soft);
        min-width: 0;
        display: flex;
        flex-direction: column;
    }
    .home-cal-side h3 {
        margin: 0 0 0.3rem;
        font-size: 1rem;
        font-weight: 650;
    }
    .home-cal-side > p {
        margin: 0 0 1rem;
        font-size: 0.8rem;
        color: var(--muted);
    }
    .home-cal-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        max-height: 360px;
        overflow: auto;
    }
    .home-cal-list li {
        font-size: 0.84rem;
        line-height: 1.35;
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 10px;
        padding: 0.65rem 0.75rem;
    }
    .home-cal-list strong {
        display: block;
        font-weight: 650;
        margin-bottom: 0.1rem;
    }
    .home-cal-list span {
        color: var(--muted);
        font-size: 0.78rem;
    }
    .home-cal-empty {
        margin: 0;
        font-size: 0.84rem;
        color: var(--muted);
        background: transparent !important;
        border: none !important;
        padding: 0 !important;
    }

    .home-quick {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
        margin-top: 1rem;
        padding: 0.95rem 1.15rem;
    }
    .home-quick a {
        color: var(--ink);
        text-decoration: none;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 0.4rem 0.75rem;
        border-radius: 999px;
        background: var(--soft);
        border: 1px solid transparent;
    }
    .home-quick a:hover {
        border-color: #e4e4e4;
        background: #fff;
    }

    @media (max-width: 860px) {
        .home-metrics { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .home-line {
            grid-template-columns: 92px minmax(0, 1fr);
            grid-template-areas: "kind name" "date progress";
        }
        .home-kind { grid-area: kind; }
        .home-line-name { grid-area: name; }
        .home-line-date { grid-area: date; }
        .home-line-progress { grid-area: progress; }
        .home-cal { grid-template-columns: 1fr; }
        .home-cal-main { border-right: none; border-bottom: 1px solid var(--line); }
        .home-logo { height: 64px; }
    }
    @media (max-width: 520px) {
        .home-metrics { grid-template-columns: 1fr 1fr; }
        .home-top { align-items: flex-start; }
        .home-logo { height: 56px; max-width: 100%; }
    }
</style>

@php
    $date = intval($date);
    $saludo = 'Bienvenido';
    if ($formato == 'am') {
        if ($date >= 5 && $date <= 11) {
            $saludo = 'Buenos días';
        } elseif ($date == 12 || ($date >= 1 && $date <= 4)) {
            $saludo = 'Buenas noches';
        }
    } else {
        if ($date == 12 || ($date >= 1 && $date <= 8)) {
            $saludo = 'Buenas tardes';
        } elseif ($date >= 9 && $date <= 11) {
            $saludo = 'Buenas noches';
        }
    }
    $nombreCorto = auth()->user()->name ? explode(' ', auth()->user()->name)[0] : '';
    $cc = $resumenCentros ?? [];
    $pv = $resumenPv ?? [];
    $mm = $datosMantenimientoMaquinas ?? [];
@endphp

<div class="home">
    <header class="home-top home-panel">
        <div>
            <p class="home-brand">Austin Powder</p>
            <h1>Hola{{ $nombreCorto ? ', '.$nombreCorto : '' }}</h1>
            <p class="home-meta">{{ $saludo }} · {{ now()->format('h:i') }} {{ $formato == 'am' ? 'AM' : 'PM' }} · {{ now()->format('d/m/Y') }}</p>
        </div>
        <div class="home-logo-wrap">
            <img class="home-logo" src="{{ asset('Images/logo_horizontal.png') }}" alt="Austin Powder">
        </div>
    </header>

    @php
        $filasAsignadas = collect($cc['asignados'] ?? [])->concat($pv['asignados'] ?? [])->values();
    @endphp
    @if(auth()->user()->tipo != 'alumno' && auth()->user()->tipo != 'empresa')
    <section class="home-lines home-panel" aria-label="Asignaciones">
        <div class="home-lines-head">
            <h2>Ciclos</h2>
            <p class="home-note">{{ $filasAsignadas->count() }} {{ $filasAsignadas->count() === 1 ? 'ciclo' : 'ciclos' }}</p>
        </div>
        @if($filasAsignadas->isEmpty())
        <p class="home-note home-lines-empty">No tienes ciclos de gastos ni de ventas.</p>
        @else
        <ul class="home-lines-list">
            @foreach($filasAsignadas as $item)
            <li>
                <a class="home-line" href="{{ $item['url'] }}">
                    <span class="home-kind is-{{ $item['tipo'] }}">{{ $item['tipoLabel'] }}</span>
                    <span class="home-line-name">
                        <strong>{{ $item['nombre'] }}</strong>
                        <em>{{ $item['detalle'] ?? '' }}</em>
                    </span>
                    <span class="home-line-date">
                        <strong>
                            @if(!empty($item['capturaDesde']) && !empty($item['capturaHasta']))
                                {{ $item['capturaDesde'] }} – {{ $item['capturaHasta'] }}
                            @elseif(!empty($item['capturaHasta']))
                                Hasta {{ $item['capturaHasta'] }}
                            @else
                                Sin fecha de captura
                            @endif
                        </strong>
                        <em class="{{ ($item['diasRestantes'] !== null && $item['diasRestantes'] >= 0 && $item['diasRestantes'] <= 7) ? 'is-soon' : '' }}">
                            @if($item['diasRestantes'] === null)
                                Captura
                            @elseif($item['diasRestantes'] < 0)
                                Captura cerrada
                            @elseif($item['diasRestantes'] === 0)
                                La captura cierra hoy
                            @else
                                {{ $item['diasRestantes'] }} {{ $item['diasRestantes'] === 1 ? 'día' : 'días' }} para capturar
                            @endif
                        </em>
                    </span>
                    <span class="home-line-progress {{ (float) $item['progreso'] >= 80 ? 'is-go' : ((float) $item['progreso'] >= 40 ? 'is-warn' : 'is-stop') }}">
                        <span class="home-meter">
                            <strong>{{ rtrim(rtrim(number_format((float) $item['progreso'], 1, '.', ''), '0'), '.') }}%</strong>
                            <span class="home-bar" role="progressbar" aria-valuenow="{{ (float) $item['progreso'] }}" aria-valuemin="0" aria-valuemax="100">
                                <i style="width: {{ min(100, (float) $item['progreso']) }}%"></i>
                            </span>
                        </span>
                        <em>{{ $item['capturadas'] }} de {{ $item['cuentas'] ?? $item['productos'] ?? 0 }} {{ $item['unidad'] }}</em>
                    </span>
                </a>
            </li>
            @endforeach
        </ul>
        @endif
        <div class="home-lines-nav">
            <div class="home-lines-group is-gasto">
                <span>Gastos</span>
                <a href="{{ route('centros.control') }}">Captura</a>
                <a href="{{ route('centros.control', ['vista' => 'visor']) }}">Visor</a>
                <a href="{{ route('centros.analisis') }}">Análisis</a>
            </div>
            <div class="home-lines-group is-venta">
                <span>Ventas</span>
                <a href="{{ route('pv.control') }}">Captura</a>
                <a href="{{ route('pv.control', ['vista' => 'visor']) }}">Visor</a>
                <a href="{{ route('pv.analisis') }}">Análisis</a>
            </div>
        </div>
    </section>

    <section class="home-cal home-panel" aria-label="Mantenimiento">
        <div class="home-cal-main">
            <div class="home-cal-head">
                <h2>Mantenimiento</h2>
                <span class="home-cal-month" id="calendarMantenimientoMesTitulo"></span>
            </div>
            <div class="home-cal-week">
                <span>Lu</span><span>Ma</span><span>Mi</span><span>Ju</span><span>Vi</span><span>Sa</span><span>Do</span>
            </div>
            <div class="home-cal-grid" id="calendarGridMantenimiento"></div>
            <div class="home-cal-legend">
                <span><i class="is-ok"></i> Realizado</span>
                <span><i class="is-warn"></i> Pendiente</span>
                <span><i class="is-late"></i> Vencido</span>
            </div>
        </div>
        <aside class="home-cal-side">
            <h3 id="calendarMantenimientoFechaLabel">Hoy</h3>
            <p>{{ number_format($mm['cumplimientoSemanal'] ?? 0, 1) }}% de cumplimiento esta semana · {{ $mm['realizadosSemana'] ?? 0 }}/{{ $mm['totalSemanal'] ?? 0 }}</p>
            <ul class="home-cal-list" id="calendarMantenimientoDiaLista"></ul>
        </aside>
    </section>

    @php
        $modulosPermitidos = collect($varpantallas ?? [])
            ->pluck('nombre')
            ->map(fn ($n) => mb_strtolower(trim($n)))
            ->filter()
            ->values();
        $rutasPermitidas = collect($varsubmenus ?? [])
            ->pluck('descripcion')
            ->map(fn ($d) => mb_strtolower(trim($d, '/')))
            ->filter()
            ->values();

        $tieneModulo = function (...$nombres) use ($modulosPermitidos) {
            foreach ($nombres as $nombre) {
                if ($modulosPermitidos->contains(mb_strtolower(trim($nombre)))) {
                    return true;
                }
            }
            return false;
        };

        $tieneRuta = function (...$rutas) use ($rutasPermitidas) {
            foreach ($rutas as $ruta) {
                $ruta = mb_strtolower(trim($ruta, '/'));
                if ($rutasPermitidas->contains($ruta)) {
                    return true;
                }
                if ($rutasPermitidas->contains(fn ($r) => str_starts_with($r, $ruta . '/') || str_starts_with($ruta, $r . '/'))) {
                    return true;
                }
            }
            return false;
        };

        $accesosRapidos = collect([
            [
                'label' => 'Recursos Humanos',
                'route' => 'verempleados',
                'visible' => $tieneModulo('Recursos Humanos') || $tieneRuta('Empleados'),
            ],
            [
                'label' => 'Ventas',
                'route' => 'ventas.pedidos',
                'visible' => $tieneModulo('Gestion de Pedidos') || $tieneRuta('pedidos'),
            ],
            [
                'label' => 'Compras',
                'route' => 'ordcompras.index',
                'visible' => $tieneModulo('Ordenes de Compra') || $tieneRuta('OrdenCompras'),
            ],
            [
                'label' => 'Maquinaria',
                'route' => 'produccion.maquinas',
                'visible' => $tieneModulo('Gestion Maquinaria') || $tieneRuta('maquinas'),
            ],
            [
                'label' => 'Reportes',
                'route' => 'reportePosicionFinanciera',
                'visible' => $tieneModulo('Reportes')
                    || $tieneRuta('Reportes/PosicionFinanciera', 'ReportesTesoreria/Ingresos', 'ReportesTesoreria/Gastos'),
            ],
            [
                'label' => 'Producción',
                'route' => 'produccion.ordenes',
                'visible' => $tieneModulo('Gestion de Produccion') || $tieneRuta('produccion'),
            ],
        ])->filter(fn ($acceso) => $acceso['visible'])->values();
    @endphp
    @if($accesosRapidos->isNotEmpty())
    <nav class="home-quick home-panel" aria-label="Otros módulos">
        @foreach($accesosRapidos as $acceso)
            <a href="{{ route($acceso['route']) }}">{{ $acceso['label'] }}</a>
        @endforeach
    </nav>
    @endif
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const mantenimientosCalendario = @json($mm['mantenimientosCalendario'] ?? []);
    const grid = document.getElementById('calendarGridMantenimiento');
    const fechaLabel = document.getElementById('calendarMantenimientoFechaLabel');
    const diaLista = document.getElementById('calendarMantenimientoDiaLista');
    const mesTitulo = document.getElementById('calendarMantenimientoMesTitulo');
    if (!grid) {
        return;
    }

    const esc = function (value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    };
    const pad2 = function (n) { return String(n).padStart(2, '0'); };
    const hoy = new Date();
    const year = hoy.getFullYear();
    const month = hoy.getMonth();

    const porFecha = mantenimientosCalendario.reduce(function (acc, item) {
        const key = item && item.fecha ? String(item.fecha) : '';
        if (!key) return acc;
        if (!acc[key]) acc[key] = [];
        acc[key].push(item);
        return acc;
    }, {});

    const claseDia = function (lista) {
        if (!lista || !lista.length) return '';
        if (lista.some(function (i) { return String(i.estatus).toUpperCase() === 'VENCIDO'; })) return ' is-late';
        if (lista.some(function (i) { return String(i.estatus).toUpperCase() === 'PENDIENTE'; })) return ' is-warn';
        if (lista.some(function (i) { return String(i.estatus).toUpperCase() === 'REALIZADO'; })) return ' is-ok';
        return '';
    };

    const pintarDia = function (iso) {
        if (!fechaLabel || !diaLista) return;
        const fecha = new Date(iso + 'T00:00:00');
        fechaLabel.textContent = fecha.toLocaleDateString('es-MX', { day: 'numeric', month: 'long' });
        const lista = porFecha[iso] || [];
        if (!lista.length) {
            diaLista.innerHTML = '<li class="home-cal-empty">Sin mantenimientos este día.</li>';
            return;
        }
        diaLista.innerHTML = lista.map(function (item) {
            return '<li><strong>' + esc(item.maquina || 'Máquina') + '</strong><span>' + esc(item.actividad || item.estatus || '') + '</span></li>';
        }).join('');
    };

    const first = new Date(year, month, 1);
    const offset = (first.getDay() + 6) % 7;
    const total = new Date(year, month + 1, 0).getDate();
    if (mesTitulo) {
        mesTitulo.textContent = first.toLocaleDateString('es-MX', { month: 'long', year: 'numeric' });
    }

    grid.innerHTML = '';
    for (let i = 0; i < offset; i++) {
        const empty = document.createElement('button');
        empty.type = 'button';
        empty.className = 'home-cal-day is-empty';
        empty.tabIndex = -1;
        grid.appendChild(empty);
    }

    for (let day = 1; day <= total; day++) {
        const iso = year + '-' + pad2(month + 1) + '-' + pad2(day);
        const eventos = porFecha[iso] || [];
        const cell = document.createElement('button');
        cell.type = 'button';
        cell.className = 'home-cal-day' + claseDia(eventos);
        cell.dataset.dateIso = iso;
        cell.innerHTML = String(day) + (eventos.length ? '<span class="dot"></span>' : '');
        cell.addEventListener('click', function () {
            grid.querySelectorAll('.home-cal-day.is-selected').forEach(function (el) {
                el.classList.remove('is-selected');
            });
            cell.classList.add('is-selected');
            pintarDia(iso);
        });
        grid.appendChild(cell);
    }

    const hoyIso = year + '-' + pad2(month + 1) + '-' + pad2(hoy.getDate());
    const hoyCell = grid.querySelector('[data-date-iso="' + hoyIso + '"]');
    if (hoyCell) {
        hoyCell.classList.add('is-selected');
        pintarDia(hoyIso);
    }
});
</script>

@endsection
