@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<style>
    .reportes-prod-page .reporte-card {
        display: flex;
        flex-direction: column;
        height: 100%;
        min-height: 168px;
        text-decoration: none;
        color: inherit;
        border: 1px solid var(--card-border, rgba(17, 24, 39, 0.09));
        border-radius: var(--card-radius, 12px);
        background: #fff;
        box-shadow: var(--card-shadow, 0 2px 12px rgba(17, 24, 39, 0.06));
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }
    .reportes-prod-page a.reporte-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 20px rgba(17, 24, 39, 0.1);
        border-color: rgba(17, 24, 39, 0.1);
        color: inherit;
    }
    .reportes-prod-page .reporte-card.is-disabled {
        opacity: .6;
        cursor: not-allowed;
        filter: grayscale(0.15);
    }
    .reportes-prod-page .reporte-card-body {
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        gap: .85rem;
    }
    .reportes-prod-page .reporte-card-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .reportes-prod-page .reporte-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.35rem;
        flex-shrink: 0;
        background: linear-gradient(145deg, var(--primary-dark, #030712), var(--primary-color, #111827));
    }
    .reportes-prod-page .reporte-arrow {
        color: var(--primary-color, #111827);
        font-size: 1rem;
        opacity: 0;
        transform: translateX(-4px);
        transition: opacity .2s ease, transform .2s ease;
    }
    .reportes-prod-page a.reporte-card:hover .reporte-arrow {
        opacity: 1;
        transform: translateX(0);
    }
    .reportes-prod-page .reporte-title {
        font-size: 1.05rem;
        font-weight: 600;
        color: var(--dark-color, #111827);
        margin: 0;
        line-height: 1.3;
    }
    .reportes-prod-page .reporte-desc {
        color: var(--text-muted, #6b7280);
        font-size: .82rem;
        margin: 0;
        line-height: 1.45;
        flex-grow: 1;
    }
    .reportes-prod-page .reporte-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        font-size: .78rem;
        font-weight: 600;
        padding-top: .15rem;
    }
    .reportes-prod-page .reporte-footer .entrar {
        color: #1e3a5f;
    }
    .reportes-prod-page .reporte-footer .prox {
        color: #94a3b8;
    }
</style>

@php
    $totalReportes = count($reportes);
    $disponibles = collect($reportes)->where('activo', true)->count();
@endphp

<div class="container-fluid format_page reportes-prod-page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Reportes de producción</h2>
                        <p class="text-muted mb-0">
                            Detalle, gráficas y exportación — {{ $disponibles }} de {{ $totalReportes }} disponibles
                        </p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    <a class="btn btn-baseColor-light" href="{{ route('produccion.ordenes') }}">
                        <i class="fa-solid fa-industry"></i> Producción
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 g-lg-4">
        @foreach ($reportes as $reporte)
            <div class="col-md-6 col-xl-4">
                @if (!empty($reporte['activo']) && !empty($reporte['ruta']))
                    <a href="{{ $reporte['ruta'] }}" class="reporte-card">
                @else
                    <div class="reporte-card is-disabled" title="Próximamente">
                @endif
                        <div class="reporte-card-body">
                            <div class="reporte-card-top">
                                <span class="reporte-icon" style="background: {{ $reporte['color'] }};">
                                    <i class="fa-solid {{ $reporte['icono'] }}"></i>
                                </span>
                                @if (!empty($reporte['activo']))
                                    <span class="reporte-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                                @endif
                            </div>
                            <h3 class="reporte-title">{{ $reporte['titulo'] }}</h3>
                            <p class="reporte-desc">{{ $reporte['descripcion'] }}</p>
                            <div class="reporte-footer">
                                @if (!empty($reporte['activo']))
                                    <span class="entrar">
                                        Abrir detalle <i class="fa-solid fa-arrow-right ms-1"></i>
                                    </span>
                                    <span class="badge badge-success-dark fs-9">Disponible</span>
                                @else
                                    <span class="prox">Próximamente</span>
                                    <span class="badge bg-secondary fs-9">En construcción</span>
                                @endif
                            </div>
                        </div>
                @if (!empty($reporte['activo']) && !empty($reporte['ruta']))
                    </a>
                @else
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection
