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

@endsection
