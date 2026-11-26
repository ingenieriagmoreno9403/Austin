@extends('layouts.app')
@section('content')
 @php 
    $partidas = 0;
 @endphp

 @foreach($productos as $producto)
    @php 
        $partidas = $partidas + 1;
    @endphp
 @endforeach

<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-chart-column"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">
                            Resultados de Licitación
                            <span class="badge bg-orange fs-6">Folio: {{ $licitacion->folio }}</span>
                        </h2>
                        <p class="text-muted mb-0">Análisis y resultados de la licitación de precios</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header bg-light text-white">
                    <h6 class="text-dark mb-0">Información de la Licitación</h6>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <p class="fs-8">
                                    <b>Nombre de la Licitación </b><br>
                                    {{ $licitacion->nombre }}
                                </p>
                            </div>
                            <div class="mb-3">
                                <p class="fs-8">
                                    <b>Descripción</b><br>
                                    {{ $licitacion->descripcion_detalle }}
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <p class="fs-8">
                                            <b>Fecha de Creación</b><br>
                                            {{ \Carbon\Carbon::parse($licitacion->fecha_creacion)->format('d/m/Y') }}
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3 fs-8">
                                        <b>Fecha Límite</b><br>
                                        <p class="{{ \Carbon\Carbon::parse($licitacion->fecha_limite)->isPast() ? 'text-danger' : '' }}">
                                            {{ \Carbon\Carbon::parse($licitacion->fecha_limite)->format('d/m/Y') }}
                                            @php
                                                $fechaLimite = \Carbon\Carbon::parse($licitacion->fecha_limite);
                                                $diasRestantes = now()->diffInDays($fechaLimite, false);
                                            @endphp
                                            
                                            @if($diasRestantes < 0)
                                                <span class="badge bg-danger">Vencida ({{ abs($diasRestantes) }} días)</span>
                                            @elseif($diasRestantes <= 3)
                                                <span class="badge bg-warning text-dark">{{ $diasRestantes }} días restantes</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3 fs-8">
                                <b class="fw-bold">Estado</b>
                                
                                <p class="fs-8">
                                    @if($licitacion->estado == 'cerrada')
                                        <span class="badge bg-danger">Cerrada</span>
                                    @elseif($licitacion->estado == 'borrador')
                                        <span class="badge bg-success">Activa</span>
                                    @elseif($licitacion->estado == 'adjudicada')
                                        <span class="badge bg-info">Adjudicada</span>
                                    @else
                                        <span class="badge bg-primary">{{ucfirst($licitacion->estado)}}</span>
                                    @endif
                                
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif

    @php
        $totalesPorProveedor = $totalesPorProveedor ?? [];
        $paramPrecioId = $paramPrecioId ?? null;
        $precioDesdeCotizacion = !empty($precioDesdeCotizacion);
        $rankingPos = [];
        $posR = 1;
        foreach (($ranking ?? []) as $rid => $rscore) {
            $rankingPos[(int) $rid] = $posR++;
        }
        $normClass = function ($norm) {
            if ($norm === null) {
                return 'cmp-cell-empty';
            }
            $n = (float) $norm;
            if ($n >= 80) {
                return 'cmp-cell-best';
            }
            if ($n >= 45) {
                return 'cmp-cell-mid';
            }

            return 'cmp-cell-worst';
        };
        $maxScore = ! empty($ranking) ? (float) max($ranking) : 0;
        $minTotal = null;
        foreach ($totalesPorProveedor as $t) {
            if ($t > 0 && ($minTotal === null || $t < $minTotal)) {
                $minTotal = $t;
            }
        }
        $hayCotizaciones = false;
        foreach ($totalesPorProveedor as $t) {
            if ((float) $t > 0) {
                $hayCotizaciones = true;
                break;
            }
        }
    @endphp

    <style>
        .cmp-suggest {
            border: 1px solid #bbf7d0;
            background: linear-gradient(120deg, #ecfdf5 0%, #f0fdf4 55%, #fff 100%);
            border-radius: 14px;
            padding: 1rem 1.15rem;
        }
        .cmp-suggest__title { font-size: .78rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: #047857; margin: 0 0 .35rem; }
        .cmp-suggest__name { font-size: 1.35rem; font-weight: 800; color: #064e3b; margin: 0; }
        .cmp-suggest__meta { color: #065f46; font-size: .9rem; }
        .cmp-card-prov {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: .85rem 1rem;
            background: #fff;
            height: 100%;
            position: relative;
        }
        .cmp-card-prov.is-best { border-color: #86efac; box-shadow: 0 0 0 2px rgba(34,197,94,.15); }
        .cmp-card-prov.is-worst { border-color: #fecaca; }
        .cmp-card-prov .cmp-pos {
            position: absolute; top: .55rem; right: .7rem;
            font-size: .72rem; font-weight: 800; padding: .15rem .45rem; border-radius: 999px;
        }
        .cmp-bar { height: 8px; border-radius: 999px; background: #e5e7eb; overflow: hidden; margin-top: .45rem; }
        .cmp-bar > span { display: block; height: 100%; border-radius: 999px; background: #0f766e; }
        .cmp-cell-best { background: #dcfce7 !important; }
        .cmp-cell-mid { background: #fef9c3 !important; }
        .cmp-cell-worst { background: #fee2e2 !important; }
        .cmp-cell-empty { background: #f9fafb !important; }
        .cmp-norm {
            display: inline-block; margin-top: .25rem; font-size: .68rem; font-weight: 700;
            padding: .1rem .4rem; border-radius: 999px; background: rgba(15,23,42,.06);
        }
        .cmp-col-best { box-shadow: inset 0 0 0 2px #22c55e; }
        .cmp-section-label {
            font-size: .75rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
            color: #64748b; margin: 0 0 .65rem;
        }
    </style>

    {{-- Sugerencia del sistema --}}
    @if(!empty($sugerencia))
        <div class="cmp-suggest mb-3">
            <div class="row align-items-center g-3">
                <div class="col-lg-8">
                    <p class="cmp-suggest__title"><i class="fa-solid fa-lightbulb me-1"></i> Sugerencia del sistema (ponderación)</p>
                    <p class="cmp-suggest__name">{{ $sugerencia['nombre'] }}</p>
                    <p class="cmp-suggest__meta mb-0">
                        Mejor score ponderado:
                        <strong>{{ number_format($sugerencia['score'], 2) }} pts</strong>
                        @if(($sugerencia['total'] ?? 0) > 0)
                            · Total cotizado: <strong>${{ number_format($sugerencia['total'], 2) }}</strong>
                        @endif
                    </p>
                        </div>
                <div class="col-lg-4 text-lg-end">
                    <span class="badge bg-success fs-6 px-3 py-2">#1 recomendado</span>
                    </div>
                </div>
        </div>
    @endif

    {{-- Tarjetas resumen por proveedor --}}
    @if($proveedores->isNotEmpty())
        <div class="row g-3 mb-3">
            @foreach($proveedores as $prov)
                @php
                    $pid = (int) $prov->id;
                    $scoreP = $ranking[$pid] ?? null;
                    $posP = $rankingPos[$pid] ?? null;
                    $totalP = (float) ($totalesPorProveedor[$pid] ?? 0);
                    $isBest = $posP === 1;
                    $isWorst = !empty($rankingPos) && $posP === count($rankingPos);
                    $pctBar = ($maxScore > 0 && $scoreP !== null) ? max(4, min(100, ($scoreP / $maxScore) * 100)) : 0;
                    $totalCls = '';
                    if ($totalP > 0 && $minTotal !== null) {
                        if ($totalP == $minTotal) $totalCls = 'text-success fw-bold';
                        elseif ($totalP > $minTotal * 1.10) $totalCls = 'text-danger';
                        else $totalCls = 'text-warning';
                    }
                @endphp
                <div class="col-md-6 col-xl-4">
                    <div class="cmp-card-prov {{ $isBest ? 'is-best' : '' }} {{ $isWorst && !$isBest ? 'is-worst' : '' }}">
                        @if($posP)
                            <span class="cmp-pos {{ $isBest ? 'bg-success text-white' : ($isWorst ? 'bg-danger text-white' : 'bg-secondary text-white') }}">
                                #{{ $posP }}
                            </span>
                        @endif
                        <div class="fw-bold pe-5">{{ $prov->nombre }}</div>
                        <div class="small text-muted mt-1">
                            Score:
                            <strong>{{ $scoreP !== null ? number_format($scoreP, 2).' pts' : '—' }}</strong>
                            · Cotización:
                            <span class="{{ $totalCls }}">{{ $totalP > 0 ? '$'.number_format($totalP, 2) : 'Sin precios' }}</span>
                        </div>
                        <div class="cmp-bar"><span style="width: {{ $pctBar }}%"></span></div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Comparativa integrada: parámetros + precios --}}
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="text-dark mb-0">
                Comparativa integrada
                @if($licitacion->tipoParam)
                    <span class="badge bg-secondary ms-1">{{ $licitacion->tipoParam->nombre }}</span>
                @endif
            </h6>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('licitaciones.editar', $licitacion->id) }}#seccion-parametros" class="btn btn-sm btn-outline-secondary">
                    Configurar parámetros
                </a>
                <a href="/Comparativa/Cotizacion/{{ $licitacion->id }}" class="btn btn-sm btn-outline-success">
                    <i class="fa-solid fa-file-excel"></i> Descargar
                </a>
            </div>
        </div>
        <div class="card-body">
            @if(($parametrosEval ?? collect())->isEmpty())
                <div class="alert alert-info">
                    Esta licitación aún no tiene parámetros de evaluación.
                    <a href="{{ route('licitaciones.editar', $licitacion->id) }}#seccion-parametros">Aplica un tipo/familia</a>
                    para ponderar distribuidores.
                </div>
            @elseif($proveedores->isEmpty())
                <div class="alert alert-warning">Invita proveedores para comparar.</div>
            @else
                <div class="alert alert-light border py-2 small mb-3">
                    <i class="fa-solid fa-arrow-down me-1"></i>
                    Captura los valores <strong>arriba</strong> (precio, entrega, garantía, soporte…).
                    La colorimetría y la sugerencia de abajo se calculan <strong>con esos datos y su ponderación %</strong>.
                    @if($precioDesdeCotizacion)
                        · Precio se sincroniza también desde cotizaciones de producto.
                    @endif
                </div>

                <p class="cmp-section-label">1. Captura (base de la comparación)</p>
                <form method="post" action="{{ route('licitaciones.parametros.scores', $licitacion->id) }}">
                    @csrf
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead>
                                <tr class="table-light">
                                    <th>Parámetro</th>
                                    <th class="text-center">Peso</th>
                                    <th class="text-center">Sentido</th>
                                    @foreach($proveedores as $prov)
                                        @php $isBestCol = ($rankingPos[(int)$prov->id] ?? null) === 1; @endphp
                                        <th class="text-center {{ $isBestCol ? 'cmp-col-best' : '' }}">
                                            {{ $prov->nombre }}
                                            @if($isBestCol)<div class="small text-success">Mejor</div>@endif
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($parametrosEval as $param)
                                    @php $esPrecioAuto = $paramPrecioId && (int)$param->id === (int)$paramPrecioId && $precioDesdeCotizacion; @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $param->nombre }}</strong>
                                            <div class="text-muted" style="font-size:.72rem">
                                                {{ $param->tipo_dato }}
                                                @if($esPrecioAuto) · desde cotizaciones @endif
                                            </div>
                                        </td>
                                        <td class="text-center">{{ rtrim(rtrim(number_format($param->peso_pct, 2), '0'), '.') }}%</td>
                                        <td class="text-center" style="font-size:.75rem">
                                            {{ $param->sentido === 'menor_mejor' ? 'Menor mejor' : 'Mayor mejor' }}
                                        </td>
                                        @foreach($proveedores as $prov)
                                            @php
                                                $key = $param->id.'-'.$prov->id;
                                                $scoreRow = $scoresMap[$key] ?? null;
                                                $norm = $scoreRow->score_normalizado ?? null;
                                                $cellCls = $normClass($norm);
                                            @endphp
                                            <td class="{{ $cellCls }}">
                                                <input type="hidden" name="scores[{{ $param->id }}_{{ $prov->id }}][parametro_id]" value="{{ $param->id }}">
                                                <input type="hidden" name="scores[{{ $param->id }}_{{ $prov->id }}][proveedor_id]" value="{{ $prov->id }}">
                                                <input type="number" step="0.0001"
                                                       class="form-control form-control-sm"
                                                       name="scores[{{ $param->id }}_{{ $prov->id }}][valor]"
                                                       value="{{ $scoreRow->valor ?? '' }}"
                                                       placeholder="Valor"
                                                       @if($esPrecioAuto) readonly @endif>
                                                @if($norm !== null)
                                                    <div class="text-center">
                                                        <span class="cmp-norm">{{ number_format($norm, 0) }}/100</span>
                                                    </div>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                @if(!empty($ranking))
                                    <tr class="table-light fw-bold">
                                        <td colspan="3" class="text-end">Score ponderado</td>
                                        @foreach($proveedores as $prov)
                                            @php
                                                $pid = (int) $prov->id;
                                                $sc = $ranking[$pid] ?? null;
                                                $pos = $rankingPos[$pid] ?? null;
                                            @endphp
                                            <td class="text-center {{ $pos === 1 ? 'cmp-cell-best' : ($pos === count($rankingPos) ? 'cmp-cell-worst' : 'cmp-cell-mid') }}">
                                                {{ $sc !== null ? number_format($sc, 2).' pts' : '—' }}
                                                @if($pos)<div class="small">#{{ $pos }}</div>@endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mb-4">
                        <button type="submit" class="btn btn-baseColor">
                            <i class="fa-solid fa-calculator me-1"></i> Guardar y recalcular sugerencia
                        </button>
                    </div>
                </form>
            @endif

            {{-- 2. Resultado visual SOLO a partir de lo capturado arriba --}}
            @if(($parametrosEval ?? collect())->isNotEmpty() && $proveedores->isNotEmpty())
                <p class="cmp-section-label">2. Resultado (en base a lo de arriba)</p>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead>
                            <tr class="table-light">
                                <th>Criterio</th>
                                <th class="text-center">Peso</th>
                                @foreach($proveedores as $prov)
                                    @php $isBestCol = ($rankingPos[(int)$prov->id] ?? null) === 1; @endphp
                                    <th class="text-center {{ $isBestCol ? 'cmp-col-best' : '' }}">{{ $prov->nombre }}</th>
                                @endforeach
                                <th class="text-center">Mejor en este criterio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($parametrosEval as $param)
                                @php
                                    $mejoresIds = [];
                                    $mejorNorm = null;
                                    foreach ($proveedores as $prov) {
                                        $sr = $scoresMap[$param->id.'-'.$prov->id] ?? null;
                                        if ($sr && $sr->score_normalizado !== null) {
                                            $n = (float) $sr->score_normalizado;
                                            if ($mejorNorm === null || $n > $mejorNorm) {
                                                $mejorNorm = $n;
                                                $mejoresIds = [(int) $prov->id];
                                            } elseif ($n === $mejorNorm) {
                                                $mejoresIds[] = (int) $prov->id;
                                            }
                                        }
                                    }
                                    $nombresMejor = $proveedores->whereIn('id', $mejoresIds)->pluck('nombre')->implode(', ');
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $param->nombre }}</strong>
                                        <div class="text-muted" style="font-size:.7rem">
                                            {{ $param->sentido === 'menor_mejor' ? 'Menor mejor' : 'Mayor mejor' }}
                                        </div>
                                    </td>
                                    <td class="text-center">{{ rtrim(rtrim(number_format($param->peso_pct, 2), '0'), '.') }}%</td>
                                    @foreach($proveedores as $prov)
                                        @php
                                            $sr = $scoresMap[$param->id.'-'.$prov->id] ?? null;
                                            $norm = $sr->score_normalizado ?? null;
                                            $val = $sr->valor ?? null;
                                            $cls = $normClass($norm);
                                            if ($norm !== null && $mejorNorm !== null && (float)$norm === (float)$mejorNorm) {
                                                $cls = 'cmp-cell-best';
                                            } elseif ($norm !== null && (float)$norm < 45) {
                                                $cls = 'cmp-cell-worst';
                                            }
                                        @endphp
                                        <td class="text-center {{ $cls }}">
                                            @if($val !== null && $val !== '')
                                                <div class="fw-bold">{{ is_numeric($val) ? number_format((float)$val, 2) : $val }}</div>
                                                <span class="cmp-norm">{{ number_format((float)$norm, 0) }}/100</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="text-center small">
                                        @if($nombresMejor !== '')
                                            <span class="badge bg-success">{{ $nombresMejor }}</span>
                                        @else
                                            <span class="text-muted">Sin datos</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            @if(!empty($ranking))
                                <tr class="fw-bold">
                                    <td colspan="2" class="text-end">Resultado ponderado</td>
                                    @foreach($proveedores as $prov)
                                        @php
                                            $pid = (int) $prov->id;
                                            $sc = $ranking[$pid] ?? null;
                                            $pos = $rankingPos[$pid] ?? null;
                                        @endphp
                                        <td class="text-center {{ $pos === 1 ? 'cmp-cell-best' : ($pos === count($rankingPos) ? 'cmp-cell-worst' : 'cmp-cell-mid') }}">
                                            {{ $sc !== null ? number_format($sc, 2).' pts' : '—' }}
                                            @if($pos)<div class="small">#{{ $pos }}</div>@endif
                                        </td>
                                    @endforeach
                                    <td class="text-center">
                                        @if(!empty($sugerencia))
                                            <span class="badge bg-success">Sugerido: {{ $sugerencia['nombre'] }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                <div class="small mb-3">
                    <strong>Colorimetría (según lo capturado arriba):</strong>
                    <span class="badge cmp-cell-best text-dark border">Mejor</span>
                    <span class="badge cmp-cell-mid text-dark border">Intermedio</span>
                    <span class="badge cmp-cell-worst text-dark border">Peor</span>
                </div>
            @endif

            {{-- Detalle de cotizaciones solo si ya hay precios capturados por producto --}}
            @if($hayCotizaciones)
                <p class="cmp-section-label">3. Detalle de cotizaciones por producto</p>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-sm mb-0">
                        <thead>
                            <tr class="table-warning">
                                <th class="align-middle">Producto</th>
                                <th class="align-middle text-center">Cantidad</th>
                                <th class="align-middle text-center">Unidad</th>
                                @foreach($proveedores as $proveedor)
                                    <th class="text-center" colspan="4">{{ $proveedor->nombre }}</th>
                                @endforeach
                            </tr>
                            <tr>
                                <th colspan="3"></th>
                                @foreach($proveedores as $proveedor)
                                    <th class="text-center">Precio</th>
                                    <th class="text-center">Envío</th>
                                    <th class="text-center">Cost + Marg</th>
                                    <th class="text-center">Marca</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($productos as $producto)
                                <tr>
                                    <td>{{ $producto->nombre }}</td>
                                    <td class="text-center">{{ intval($producto->cantidad) }}</td>
                                    <td class="text-center">{{ $producto->unidad }}</td>
                                    @foreach($proveedores as $proveedor)
                                        @php
                                            $key = $producto->id . '-' . $proveedor->id;
                                            $cotizacion = $cotizaciones[$key] ?? null;
                                            $costo = $cotizacion ? (float) $cotizacion[0]->costo : 0;
                                            $marca = $cotizacion ? ($cotizacion[0]->marca ?: '—') : '—';
                                            $envio = $cotizacion ? (float) ($cotizacion[0]->envio ?? 0) : 0;
                                            $margen = $cotizacion ? (float) ($cotizacion[0]->margen ?? 0) : 0;
                                            $precioMasBajo = PHP_FLOAT_MAX;
                                            foreach ($proveedores as $p) {
                                                $k = $producto->id . '-' . $p->id;
                                                if (isset($cotizaciones[$k])) {
                                                    $c = (float) $cotizaciones[$k][0]->costo;
                                                    if ($c > 0 && $c < $precioMasBajo) {
                                                        $precioMasBajo = $c;
                                                    }
                                                }
                                            }
                                            $claseColumna = '';
                                            if ($costo > 0 && $precioMasBajo < PHP_FLOAT_MAX) {
                                                if ($costo == $precioMasBajo) $claseColumna = 'cmp-cell-best';
                                                elseif ($costo > $precioMasBajo * 1.10) $claseColumna = 'cmp-cell-worst';
                                                elseif ($costo > $precioMasBajo) $claseColumna = 'cmp-cell-mid';
                                            }
                                        @endphp
                                        <td class="text-end {{ $claseColumna }}">{{ $costo > 0 ? '$'.number_format($costo, 2) : '—' }}</td>
                                        <td class="text-end {{ $claseColumna }}">
                                            @if($envio > 0 && $partidas > 0 && $producto->cantidad > 0)
                                                ${{ number_format(($envio / $partidas) / $producto->cantidad, 2) }}
                                            @else — @endif
                                        </td>
                                         <td class="text-end {{ $claseColumna }}">
                                            @if($margen > 0 && $margen < 100)
                                                ${{ number_format(round($costo * round((100 / (100 - $margen)), 2), 2), 2) }}
                                            @else — @endif
                                        </td>
                                        <td class="text-center">{{ $marca }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <th colspan="3" class="text-end">TOTAL POR PROVEEDOR</th>
                                @foreach($proveedores as $proveedor)
                                    @php
                                        $total = (float) ($totalesPorProveedor[(int)$proveedor->id] ?? 0);
                                        $tCls = 'cmp-cell-empty';
                                        if ($total > 0 && $minTotal !== null) {
                                            if ($total == $minTotal) $tCls = 'cmp-cell-best';
                                            elseif ($total > $minTotal * 1.10) $tCls = 'cmp-cell-worst';
                                            else $tCls = 'cmp-cell-mid';
                                        }
                                    @endphp
                                    <th class="text-end {{ $tCls }}" colspan="4">
                                        {{ $total > 0 ? '$'.number_format($total, 2) : '—' }}
                                    </th>
                                @endforeach
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="alert alert-secondary py-2 small mb-0">
                    Aún no hay precios por producto capturados. La comparación actual usa solo los valores de la sección 1
                    (incluye el campo <strong>Precio</strong> que capturaste arriba).
            </div>
            @endif
        </div>
            </div>

    <div class="mb-4">
                <a href="{{ route('licitaciones.index') }}" class="btn btn-baseColor-light btn-mode fs-8">
                    <i class="fa-solid fa-house"></i> Inicio
                </a>
                <a href="{{ route('licitaciones.editar', $licitacion->id) }}" class="btn btn-baseColor btn-mode fs-8 me-2">
                    <i class="fa-solid fa-pen"></i> Editar
                </a>
                
                @if($licitacion->estado != 'adjudicada')
                    <div class="float-end">
                        <button type="button" class="btn btn-success btn-mode fs-8" data-bs-toggle="modal" data-bs-target="#modalAdjudicarPorProducto">
                            <i class="fa-solid fa-check"></i> Adjudicar por Producto
                        </button>
                    </div>
                @else
                    <div class="float-end">
                        <div class="alert alert-success p-2 mb-0">
                            <i class="fa-solid fa-check-circle"></i> Licitación adjudicada
                        </div>
                    </div>
                    
                    @php
                        $adjudicaciones = DB::table('tbllicitacion_adjudicaciones as la')
                            ->join('tblprovedores as p', 'p.id', '=', 'la.proveedor_id')
                            ->join('tblproductos as pr', 'pr.id', '=', 'la.producto_id')
                            ->where('la.licitacion_id', $licitacion->id)
                            ->select('la.*', 'p.nombre as proveedor_nombre', 'pr.nombre as producto_nombre')
                            ->get();
                    @endphp
                    
                    @if($adjudicaciones->isNotEmpty())
                        <div class="mt-3">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">Resumen de Adjudicaciones</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Producto</th>
                                                    <th>Proveedor</th>
                                                    <th>Cantidad</th>
                                                    <th>Precio Unitario</th>
                                                    <th>Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($adjudicaciones as $adj)
                                                    <tr>
                                                        <td>{{ $adj->producto_nombre }}</td>
                                                        <td>{{ $adj->proveedor_nombre }}</td>
                                                        <td>{{ $adj->cantidad_adjudicada }}</td>
                                                        <td>${{ number_format($adj->precio_unitario, 2) }}</td>
                                                        <td>${{ number_format($adj->subtotal, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-info">
                                                    <th colspan="4">Total Adjudicado:</th>
                                                    <th>${{ number_format($adjudicaciones->sum('subtotal'), 2) }}</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fa-solid fa-info-circle"></i>
                                            Adjudicado el {{ \Carbon\Carbon::parse($adjudicaciones->first()->fecha_adjudicacion)->format('d/m/Y H:i') }}
                                            por {{ $adjudicaciones->first()->usuario_adjudicador }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif
    </div>
</div>

<!-- Modal para adjudicar por producto -->
@if($licitacion->estado != 'adjudicada')
<div class="modal fade" id="modalAdjudicarPorProducto" tabindex="-1" aria-labelledby="modalAdjudicarPorProductoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <form action="{{ route('licitaciones.adjudicarPorProducto', $licitacion->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAdjudicarPorProductoLabel">Adjudicar por Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @php
                        $sugeridoId = !empty($sugerencia) ? (int) $sugerencia['proveedor_id'] : null;
                        $sugeridoNombre = $sugerencia['nombre'] ?? null;
                        $sugeridoScore = $sugerencia['score'] ?? null;
                    @endphp

                    @if($sugeridoId)
                        <div class="alert alert-success py-2">
                            <i class="fa-solid fa-trophy me-1"></i>
                            <strong>Mejor según ponderación:</strong> {{ $sugeridoNombre }}
                            ({{ number_format((float) $sugeridoScore, 2) }} pts).
                            Queda preseleccionado en cada producto; puedes cambiarlo si no lo quieres.
                        </div>
                    @else
                        <div class="alert alert-warning py-2">
                            Aún no hay ranking. Captura y guarda la evaluación ponderada para ver el proveedor sugerido.
                        </div>
                    @endif

                    <div class="alert alert-info py-2">
                        <i class="fa-solid fa-info-circle"></i> 
                        <strong>Instrucciones:</strong> 
                        <ul class="mb-0 mt-2">
                            <li>Seleccione el proveedor y la cantidad para cada producto</li>
                            <li>Use el botón <i class="fa-solid fa-plus text-success"></i> para <strong>dividir un producto</strong> entre varios proveedores</li>
                            <li>Use el botón <i class="fa-solid fa-trash text-danger"></i> para eliminar divisiones adicionales</li>
                            <li>La cantidad total por producto no puede exceder lo solicitado</li>
                        </ul>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad Solicitada</th>
                                    <th>Proveedor</th>
                                    <th>Cantidad a Adjudicar</th>
                                    <th>Precio Unitario</th>
                                    <th>Subtotal / Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="adjudicacionesTable">
                                                                                @foreach($productos as $producto)
                                                    <tr class="producto-row" data-producto-id="{{ $producto->id }}" data-producto-nombre="{{ $producto->nombre }}" data-cantidad-original="{{ $producto->cantidad }}">
                                                        <td class="producto-nombre">{{ $producto->nombre }}</td>
                                                        <td class="cantidad-solicitada">{{ $producto->cantidad }}</td>
                                                        <td>
                                                            <select class="form-select proveedor-select {{ $sugeridoId ? 'border-success' : '' }}" name="adjudicaciones[{{ $producto->id }}][0][proveedor_id]" data-producto-id="{{ $producto->id }}" data-fila-index="0">
                                                                <option value="">-- Seleccionar Proveedor --</option>
                                                                @foreach($proveedores as $proveedor)
                                                                    @php
                                                                        $pidOpt = (int) $proveedor->id;
                                                                        $esSugerido = $sugeridoId && $pidOpt === $sugeridoId;
                                                                        $scoreOpt = $ranking[$pidOpt] ?? null;
                                                                        $labelOpt = $proveedor->nombre;
                                                                        if ($esSugerido) {
                                                                            $labelOpt .= ' ★ #1 sugerido';
                                                                        }
                                                                        if ($scoreOpt !== null) {
                                                                            $labelOpt .= ' ('.number_format((float) $scoreOpt, 1).' pts)';
                                                                        }
                                                                    @endphp
                                                                    <option value="{{ $proveedor->id }}"
                                                                            @selected($esSugerido)
                                                                            @if($esSugerido) data-sugerido="1" @endif>
                                                                        {{ $labelOpt }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            @if($sugeridoId)
                                                                <div class="form-text text-success" style="font-size:.7rem">Sugerido: {{ $sugeridoNombre }}</div>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control cantidad-input" 
                                                                   name="adjudicaciones[{{ $producto->id }}][0][cantidad]" 
                                                                   min="1" max="{{ $producto->cantidad }}" 
                                                                   value="{{ $producto->cantidad }}"
                                                                   data-producto-id="{{ $producto->id }}" data-fila-index="0">
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control precio-input" 
                                                                   name="adjudicaciones[{{ $producto->id }}][0][precio_unitario]" 
                                                                   min="0" step="0.01" readonly
                                                                   data-producto-id="{{ $producto->id }}" data-fila-index="0">
                                                        </td>
                                                        <td class="subtotal-cell">
                                                            <span class="subtotal-display" data-producto-id="{{ $producto->id }}" data-fila-index="0">$0.00</span>
                                                            <button type="button" class="btn btn-sm btn-outline-success ms-2 btn-dividir" data-producto-id="{{ $producto->id }}" title="Dividir entre proveedores">
                                                                <i class="fa-solid fa-plus"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="alert alert-warning alert-sm" style="display: none;" id="alertCantidades">
                                <i class="fa-solid fa-exclamation-triangle"></i>
                                <span id="mensajeAlerta"></span>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <h5>Total: <span id="totalAdjudicacion">$0.00</span></h5>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Adjudicar Productos</button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
.producto-row.table-secondary {
    background-color: #f8f9fa !important;
    border-left: 3px solid #6c757d;
}

.alert-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.btn-dividir:hover {
    background-color: #198754;
    border-color: #198754;
    color: white;
}

.btn-eliminar-fila:hover {
    background-color: #dc3545;
    border-color: #dc3545;
    color: white;
}

.table-danger {
    background-color: #f8d7da !important;
    border-color: #f5c2c7 !important;
}

.subtotal-cell {
    min-width: 150px;
}

.producto-nombre small {
    font-size: 0.75rem;
}
</style>

<script>
// Datos de cotizaciones para JavaScript
const cotizacionesData = @json($cotizaciones);
const proveedoresData = @json($proveedores);
const rankingData = @json($ranking ?? new \stdClass());
const sugeridoProveedorId = @json($sugeridoId ?? null);

function etiquetaProveedor(p) {
    const sid = Number(p.id);
    const score = rankingData[sid] ?? rankingData[String(sid)];
    const esSug = sugeridoProveedorId && sid === Number(sugeridoProveedorId);
    let label = p.nombre || '';
    if (esSug) label += ' ★ #1 sugerido';
    if (score != null && score !== '') label += ' (' + Number(score).toFixed(1) + ' pts)';
    return label;
}

function opcionesProveedoresHtml(selectedId) {
    return proveedoresData.map(function (p) {
        const sid = Number(p.id);
        const esSug = sugeridoProveedorId && sid === Number(sugeridoProveedorId);
        const sel = selectedId && sid === Number(selectedId) ? ' selected' : '';
        const sugAttr = esSug ? ' data-sugerido="1"' : '';
        return '<option value="' + p.id + '"' + sel + sugAttr + '>' + etiquetaProveedor(p) + '</option>';
    }).join('');
}

// Función para obtener el precio del producto por proveedor
function obtenerPrecioProducto(productoId, proveedorId) {
    const key = productoId + '-' + proveedorId;
    return cotizacionesData[key] ? cotizacionesData[key][0].costo : 0;
}

function aplicarPrecioYEstilo(selectEl) {
    const productoId = selectEl.getAttribute('data-producto-id');
    const filaIndex = selectEl.getAttribute('data-fila-index');
    const proveedorId = selectEl.value;
        const precioInput = document.querySelector(`input[data-producto-id="${productoId}"][data-fila-index="${filaIndex}"].precio-input`);
    const opt = selectEl.options[selectEl.selectedIndex];
    const esSugerido = opt && opt.getAttribute('data-sugerido') === '1';

    selectEl.classList.toggle('border-success', !!esSugerido);
    selectEl.classList.toggle('border-warning', !!(proveedorId && !esSugerido));
        
        if (proveedorId) {
            const precio = obtenerPrecioProducto(productoId, proveedorId);
        if (precio > 0) {
            precioInput.value = Number(precio).toFixed(2);
            precioInput.readOnly = true;
        } else {
            // Sin cotización por producto: permitir captura manual
            if (!precioInput.value) precioInput.value = '';
            precioInput.readOnly = false;
            precioInput.placeholder = 'Capturar precio';
        }
        } else {
            precioInput.value = '';
        precioInput.readOnly = true;
        }
        
        calcularSubtotal(productoId, filaIndex);
        validarCantidadesProducto(productoId);
}

// Evento cuando cambia el proveedor
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('proveedor-select')) {
        aplicarPrecioYEstilo(e.target);
    }
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('#adjudicacionesTable .proveedor-select').forEach(function (sel) {
        if (sel.value) aplicarPrecioYEstilo(sel);
    });
});

// Evento cuando cambia la cantidad o el precio manual
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('cantidad-input') || e.target.classList.contains('precio-input')) {
        const productoId = e.target.getAttribute('data-producto-id');
        const filaIndex = e.target.getAttribute('data-fila-index');
        calcularSubtotal(productoId, filaIndex);
        if (e.target.classList.contains('cantidad-input')) {
        validarCantidadesProducto(productoId);
        }
    }
});

// Evento para botones de dividir producto
document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-dividir')) {
        const productoId = e.target.closest('.btn-dividir').getAttribute('data-producto-id');
        agregarFilaDivision(productoId);
    }
    
    if (e.target.closest('.btn-eliminar-fila')) {
        const fila = e.target.closest('tr');
        const productoId = fila.getAttribute('data-producto-id');
        fila.remove();
        validarCantidadesProducto(productoId);
        calcularTotal();
    }
});

// Función para calcular subtotal de una fila específica
function calcularSubtotal(productoId, filaIndex) {
    const cantidadInput = document.querySelector(`input[data-producto-id="${productoId}"][data-fila-index="${filaIndex}"].cantidad-input`);
    const precioInput = document.querySelector(`input[data-producto-id="${productoId}"][data-fila-index="${filaIndex}"].precio-input`);
    const subtotalDisplay = document.querySelector(`span[data-producto-id="${productoId}"][data-fila-index="${filaIndex}"].subtotal-display`);
    
    const cantidad = parseFloat(cantidadInput.value) || 0;
    const precio = parseFloat(precioInput.value) || 0;
    const subtotal = cantidad * precio;
    
    subtotalDisplay.textContent = '$' + subtotal.toFixed(2);
    
    calcularTotal();
}

// Función para calcular el total
function calcularTotal() {
    let total = 0;
    document.querySelectorAll('.subtotal-display').forEach(function(element) {
        const subtotal = parseFloat(element.textContent.replace('$', '')) || 0;
        total += subtotal;
    });
    
    document.getElementById('totalAdjudicacion').textContent = '$' + total.toFixed(2);
}

// Función para agregar una fila de división
function agregarFilaDivision(productoId) {
    const filaOriginal = document.querySelector(`tr[data-producto-id="${productoId}"]`);
    const productoNombre = filaOriginal.getAttribute('data-producto-nombre');
    const cantidadOriginal = filaOriginal.getAttribute('data-cantidad-original');
    
    // Encontrar el índice más alto para este producto
    const filasExistentes = document.querySelectorAll(`tr[data-producto-id="${productoId}"]`);
    let maxIndex = 0;
    filasExistentes.forEach(function(fila) {
        const inputs = fila.querySelectorAll('input, select');
        inputs.forEach(function(input) {
            const filaIndex = parseInt(input.getAttribute('data-fila-index') || 0);
            if (filaIndex > maxIndex) maxIndex = filaIndex;
        });
    });
    
    const nuevoIndex = maxIndex + 1;
    
    // Crear nueva fila
    const nuevaFila = document.createElement('tr');
    nuevaFila.className = 'producto-row table-secondary';
    nuevaFila.setAttribute('data-producto-id', productoId);
    nuevaFila.setAttribute('data-producto-nombre', productoNombre);
    nuevaFila.setAttribute('data-cantidad-original', cantidadOriginal);
    
    // Calcular cantidad restante
    let cantidadAsignada = 0;
    filasExistentes.forEach(function(fila) {
        const cantidadInput = fila.querySelector('.cantidad-input');
        if (cantidadInput) {
            cantidadAsignada += parseFloat(cantidadInput.value) || 0;
        }
    });
    
    const cantidadRestante = Math.max(1, cantidadOriginal - cantidadAsignada);
    
    nuevaFila.innerHTML = `
        <td class="producto-nombre">
            <small class="text-muted">↳ División de:</small><br>
            ${productoNombre}
        </td>
        <td class="cantidad-solicitada">${cantidadOriginal}</td>
        <td>
            <select class="form-select proveedor-select" name="adjudicaciones[${productoId}][${nuevoIndex}][proveedor_id]" data-producto-id="${productoId}" data-fila-index="${nuevoIndex}">
                <option value="">-- Seleccionar Proveedor --</option>
                ${opcionesProveedoresHtml(null)}
            </select>
        </td>
        <td>
            <input type="number" class="form-control cantidad-input" 
                   name="adjudicaciones[${productoId}][${nuevoIndex}][cantidad]" 
                   min="1" max="${cantidadRestante}" 
                   value="${cantidadRestante}"
                   data-producto-id="${productoId}" data-fila-index="${nuevoIndex}">
        </td>
        <td>
            <input type="number" class="form-control precio-input" 
                   name="adjudicaciones[${productoId}][${nuevoIndex}][precio_unitario]" 
                   min="0" step="0.01" readonly
                   data-producto-id="${productoId}" data-fila-index="${nuevoIndex}">
        </td>
        <td class="subtotal-cell">
            <span class="subtotal-display" data-producto-id="${productoId}" data-fila-index="${nuevoIndex}">$0.00</span>
            <button type="button" class="btn btn-sm btn-outline-danger ms-2 btn-eliminar-fila" title="Eliminar división">
                <i class="fa-solid fa-trash"></i>
            </button>
        </td>
    `;
    
    // Insertar después de la última fila de este producto
    let ultimaFila = filaOriginal;
    filasExistentes.forEach(function(fila) {
        if (fila.compareDocumentPosition(ultimaFila) & Node.DOCUMENT_POSITION_FOLLOWING) {
            ultimaFila = fila;
        }
    });
    
    ultimaFila.parentNode.insertBefore(nuevaFila, ultimaFila.nextSibling);
    
    // Actualizar la cantidad de la fila original si es necesario
    const cantidadInputOriginal = filaOriginal.querySelector('.cantidad-input');
    if (cantidadAsignada >= cantidadOriginal) {
        cantidadInputOriginal.value = Math.max(1, cantidadOriginal - cantidadRestante);
    }
    
    validarCantidadesProducto(productoId);
}

// Función para validar que las cantidades no excedan lo solicitado
function validarCantidadesProducto(productoId) {
    const filasProducto = document.querySelectorAll(`tr[data-producto-id="${productoId}"]`);
    const cantidadOriginal = parseInt(filasProducto[0].getAttribute('data-cantidad-original'));
    
    let cantidadTotal = 0;
    filasProducto.forEach(function(fila) {
        const cantidadInput = fila.querySelector('.cantidad-input');
        if (cantidadInput) {
            cantidadTotal += parseFloat(cantidadInput.value) || 0;
        }
    });
    
    const alertDiv = document.getElementById('alertCantidades');
    const mensajeSpan = document.getElementById('mensajeAlerta');
    
    if (cantidadTotal > cantidadOriginal) {
        alertDiv.style.display = 'block';
        mensajeSpan.textContent = `La cantidad total adjudicada (${cantidadTotal}) excede la solicitada (${cantidadOriginal}) para el producto.`;
        
        // Resaltar filas con error
        filasProducto.forEach(function(fila) {
            fila.classList.add('table-danger');
        });
    } else {
        // Verificar si hay otros productos con problemas
        let hayOtrosErrores = false;
        document.querySelectorAll('.producto-row').forEach(function(fila) {
            const prodId = fila.getAttribute('data-producto-id');
            if (prodId !== productoId) {
                const filasOtroProducto = document.querySelectorAll(`tr[data-producto-id="${prodId}"]`);
                const cantidadOriginalOtro = parseInt(filasOtroProducto[0].getAttribute('data-cantidad-original'));
                
                let cantidadTotalOtro = 0;
                filasOtroProducto.forEach(function(filaOtra) {
                    const cantidadInputOtro = filaOtra.querySelector('.cantidad-input');
                    if (cantidadInputOtro) {
                        cantidadTotalOtro += parseFloat(cantidadInputOtro.value) || 0;
                    }
                });
                
                if (cantidadTotalOtro > cantidadOriginalOtro) {
                    hayOtrosErrores = true;
                }
            }
        });
        
        if (!hayOtrosErrores) {
            alertDiv.style.display = 'none';
        }
        
        // Quitar resaltado de error
        filasProducto.forEach(function(fila) {
            fila.classList.remove('table-danger');
        });
    }
}

// Validación antes de enviar el formulario
document.querySelector('form').addEventListener('submit', function(e) {
    let hayErrores = false;
    
    // Validar todas las cantidades
    const productosUnicos = [...new Set(Array.from(document.querySelectorAll('.producto-row')).map(fila => fila.getAttribute('data-producto-id')))];
    
    productosUnicos.forEach(function(productoId) {
        const filasProducto = document.querySelectorAll(`tr[data-producto-id="${productoId}"]`);
        const cantidadOriginal = parseInt(filasProducto[0].getAttribute('data-cantidad-original'));
        
        let cantidadTotal = 0;
        let tieneProveedorSeleccionado = false;
        
        filasProducto.forEach(function(fila) {
            const cantidadInput = fila.querySelector('.cantidad-input');
            const proveedorSelect = fila.querySelector('.proveedor-select');
            
            if (cantidadInput && proveedorSelect.value) {
                cantidadTotal += parseFloat(cantidadInput.value) || 0;
                tieneProveedorSeleccionado = true;
            }
        });
        
        if (cantidadTotal > cantidadOriginal) {
            hayErrores = true;
            alert(`Error: La cantidad total adjudicada (${cantidadTotal}) excede la solicitada (${cantidadOriginal}) para un producto.`);
        }
        
        if (tieneProveedorSeleccionado && cantidadTotal === 0) {
            hayErrores = true;
            alert('Error: Hay productos con proveedor seleccionado pero sin cantidad.');
        }
    });
    
    if (hayErrores) {
        e.preventDefault();
    }
});
</script>
@endif

@endsection 