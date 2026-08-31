@extends('layouts.app')
@section('content')

<style>
    .mining-dashboard {
        min-height: 100vh;
        color: #e5e7eb;
        background: radial-gradient(circle at top left, rgba(192, 38, 211, 0.22), transparent 32%),
                    linear-gradient(135deg, #07040d 0%, #151022 48%, #260b2f 100%);
    }
    
    .hero-section {
        margin-bottom: 1rem;
        position: relative;
    }
    
    .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(217,70,239,0.18)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
        opacity: 0.45;
    }
    
    .greeting-card {
        background: rgba(17, 12, 28, 0.92);
        backdrop-filter: blur(15px);
        border: 1px solid rgba(217, 70, 239, 0.25);
        border-radius: 15px;
        padding: 1.5rem;
        transition: all 0.3s ease;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
        height: 200px;
        vertical-align: middle;
    }
    
    .greeting-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(217, 70, 239, 0.28);
    }
    
    .greeting-text {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        background: linear-gradient(45deg, #ffffff, #d946ef, #f472b6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .time-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, #7e22ce, #db2777);
        border-radius: 25px;
        font-size: 12px;
        color: white;
        font-weight: 600;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: rgba(17, 12, 28, 0.94);
        border: 1px solid rgba(217, 70, 239, 0.22);
        border-radius: 15px;
        padding: 1.5rem;
        text-align: center;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        box-shadow: 0 5px 18px rgba(0, 0, 0, 0.32);
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(217, 70, 239, 0.14), transparent);
        transition: left 0.5s;
    }
    
    .stat-card:hover::before {
        left: 100%;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(217, 70, 239, 0.28);
        border-color: #d946ef;
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        margin: 0 auto 1rem;
        background: linear-gradient(135deg, #7e22ce, #db2777);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: #d946ef;
    }
    
    .stat-label {
        font-size: 0.9rem;
        color: #a1a1aa;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .quick-actions {
        padding: 10px;
        margin-bottom: 1rem;
    }
    
    .action-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
    
    .action-btn {
        background: linear-gradient(135deg, #6d28d9, #c026d3);
        border: none;
        border-radius: 8px;
        padding: 1rem;
        padding-top: 2.5rem;
        padding-bottom: 2.5rem;
        color: white;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        font-weight: 600;
        text-align: center;
    }
    
    .action-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(217, 70, 239, 0.38);
        color: white;
        text-decoration: none;
        background: linear-gradient(135deg, #c026d3, #db2777);
    }
    
    .charts-section {
        background: rgba(17, 12, 28, 0.94);
        border: 1px solid rgba(217, 70, 239, 0.22);
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 5px 18px rgba(0, 0, 0, 0.32);
    }
    
    .chart-container {
        height: 300px;
        margin: 1rem 0;
        position: relative;
    }
    
    .chart-title {
        color: #f5f3ff;
        font-weight: 600;
        margin-bottom: 1rem;
        text-align: center;
    }
    
    .mining-visualization {
        background: rgba(17, 12, 28, 0.94);
        border: 1px solid rgba(217, 70, 239, 0.22);
        border-radius: 15px;
        padding: 1.5rem;
        text-align: center;
        position: relative;
        overflow: hidden;
        box-shadow: 0 5px 18px rgba(0, 0, 0, 0.32);
    }
    
    .mining-animation {
        width: 100%;
        height: 200px;
        background: linear-gradient(45deg, #12091d, #2a1138);
        border-radius: 10px;
        position: relative;
        margin-bottom: 1rem;
        overflow: hidden;
        border: 1px solid rgba(217, 70, 239, 0.22);
    }
    
    .mining-truck {
        position: absolute;
        bottom: 20px;
        left: -100px;
        width: 80px;
        height: 40px;
        background: #d946ef;
        border-radius: 20px;
        animation: drive 8s infinite linear;
    }
    
    .mining-truck::before {
        content: '';
        position: absolute;
        top: -15px;
        left: 15px;
        width: 50px;
        height: 30px;
        background: #d946ef;
        border-radius: 15px 15px 0 0;
    }
    
    .mining-truck::after {
        content: '';
        position: absolute;
        bottom: -5px;
        left: 10px;
        width: 20px;
        height: 10px;
        background: #18181b;
        border-radius: 50%;
        box-shadow: 30px 0 0 #18181b;
    }
    
    @keyframes drive {
        0% { left: -100px; }
        100% { left: 100%; }
    }
    
    .mining-particles {
        position: absolute;
        width: 100%;
        height: 100%;
    }
    
    .particle {
        position: absolute;
        width: 4px;
        height: 4px;
        background: #d946ef;
        border-radius: 50%;
        animation: float 3s infinite ease-in-out;
    }
    
    .particle:nth-child(1) { top: 20%; left: 10%; animation-delay: 0s; }
    .particle:nth-child(2) { top: 40%; left: 30%; animation-delay: 1s; }
    .particle:nth-child(3) { top: 60%; left: 50%; animation-delay: 2s; }
    .particle:nth-child(4) { top: 80%; left: 70%; animation-delay: 0.5s; }
    .particle:nth-child(5) { top: 30%; left: 90%; animation-delay: 1.5s; }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px); opacity: 0.7; }
        50% { transform: translateY(-20px); opacity: 1; }
    }
    
    .weather-widget {
        background: rgba(17, 12, 28, 0.92);
        backdrop-filter: blur(15px);
        border: 1px solid rgba(217, 70, 239, 0.25);
        border-radius: 15px;
        padding: 1.5rem;
        transition: all 0.3s ease;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
        height: 200px;
        text-align: center;
        vertical-align: middle;
    }

    .weather-widget:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(217, 70, 239, 0.28);
    }
    
    .weather-icon {
        font-size: 3rem;
        margin-bottom: .3rem;
        color: #d946ef;
    }
    
    .weather-temp {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: #f5f3ff;
    }
    
    .weather-desc {
        color: #a1a1aa;
        font-size: 0.9rem;
    }
    
    .suggestion-box {
        background: rgba(17, 12, 28, 0.94);
        border: 1px solid rgba(217, 70, 239, 0.22);
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 18px rgba(0, 0, 0, 0.32);
    }
    
    .suggestion-textarea {
        background: rgba(10, 7, 18, 0.85);
        border: 1px solid rgba(217, 70, 239, 0.22);
        border-radius: 10px;
        color: #f5f3ff;
        padding: 1rem;
        width: 100%;
        min-height: 80px;
        resize: vertical;
    }
    
    .suggestion-textarea::placeholder {
        color: #a1a1aa;
    }
    
    .suggestion-textarea:focus {
        outline: none;
        border-color: #d946ef;
        box-shadow: 0 0 0 3px rgba(217, 70, 239, 0.22);
        background: #12091d;
    }
    
    .send-btn {
        background: linear-gradient(135deg, #6d28d9, #db2777);
        border: none;
        border-radius: 8px;
        padding: 0.75rem 2rem;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
        font-size: 15px;
    }
    
    .send-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(217, 70, 239, 0.38);
        background: linear-gradient(135deg, #c026d3, #db2777);
    }
    
    .mission-statement {
        background: rgba(17, 12, 28, 0.94);
        border: 1px solid rgba(217, 70, 239, 0.22);
        border-radius: 15px;
        padding: 1.5rem;
        text-align: center;
        font-style: italic;
        position: relative;
        box-shadow: 0 5px 18px rgba(0, 0, 0, 0.32);
        font-size: 16px;
    }
    
    .mission-statement::before {
        content: '"';
        font-size: 4rem;
        color: rgba(217, 70, 239, 0.32);
        position: absolute;
        top: -10px;
        left: 20px;
        font-family: serif;
    }
    
    .mission-statement::after {
        content: '"';
        font-size: 4rem;
        color: rgba(217, 70, 239, 0.32);
        position: absolute;
        bottom: -30px;
        right: 20px;
        font-family: serif;
    }
    
    .progress-bar {
        background: rgba(82, 82, 91, 0.45);
        border-radius: 10px;
        height: 8px;
        margin: 0.5rem 0;
        overflow: hidden;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #7e22ce, #db2777);
        border-radius: 10px;
        transition: width 2s ease;
    }
    
    .metric-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding: 0.5rem 0;
    }
    
    .metric-label {
        font-weight: 600;
        color: #f5f3ff;
    }
    
    .metric-value {
        font-weight: 700;
        color: #d946ef;
    }

    .mining-dashboard .btn-baseColor {
        background: linear-gradient(135deg, #6d28d9, #db2777);
        border: none;
        color: #ffffff;
    }

    .mining-dashboard .btn-baseColor:hover {
        background: linear-gradient(135deg, #c026d3, #db2777);
        color: #ffffff;
    }

    .mining-dashboard .text-orange {
        color: #f472b6 !important;
    }

    .mining-dashboard .card {
        background: rgba(10, 7, 18, 0.9);
        border: 1px solid rgba(217, 70, 239, 0.18);
        color: #f5f3ff;
    }
    
    @media (max-width: 768px) {
        .greeting-text {
            font-size: 2rem;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .action-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="mining-dashboard">
    <div class="container-fluid">
        <!-- hero section -->
        <div class="hero-section">
            <div class="row align-items-center">
                <div class="col-lg-8 mb-4">
                    <div class="greeting-card">
                        @php
                        $date = intval($date);
                        if($formato == "am"){
                            if($date >= 5 && $date <= 11){
                                $saludo = "Buenos días";
                            } elseif($date == 12){ 
                                $saludo = "Buenas Noches";
                            }elseif($date >= 1 && $date <= 4) {
                                $saludo = "Buenas Noches";
                            }
                        }else{
                            if($date == 12){ 
                                $saludo = "Buenas Tardes";
                            }elseif($date >= 1 && $date <= 8){ 
                                $saludo = "Buenas Tardes";
                            }elseif($date >= 9 && $date <= 11){
                                $saludo = "Buenas Noches";
                            }
                        }
                        @endphp
                        
                        <h3 class="greeting-text mb-2 mt-2">Hola,</BR> {{$saludo}}</h3>
                        <div class="time-indicator">
                            <i class="fas fa-clock"></i>
                            <span>{{ now()->format('H:i') }} - {{ now()->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 mb-4">
                    <div class="weather-widget">
                        <div class="weather-icon">
                            @if($formato == "am")
                                @if($date >= 5 && $date <= 11) 
                                   <i class="fas fa-sun"></i>
                                @elseif($date == 12) 
                                    <i class="fas fa-moon"></i>
                                @elseif($date >= 1 && $date <= 4) 
                                    <i class="fas fa-moon"></i>
                                @endif
                            @else
                                @if($date == 12) 
                                    <i class="fas fa-sun"></i>
                                @elseif($date >= 1 && $date <= 8) 
                                    <i class="fas fa-sun"></i>
                                @elseif($date >= 9 && $date <= 11) 
                                    <i class="fas fa-moon"></i>
                                @endif
                            @endif
                        </div>
                        <div class="weather-temp">{{ now()->format('g:i') }}</div>
                        <div class="weather-desc">{{$formato == "am" ? "AM" : "PM"}}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <div class="row text-center">
                <div class="col-lg-2 col-6 p-1">
                    <a href="/Servicios" class="action-btn">
                        <i class="fa-solid fa-chart-simple"></i>
                        <span>Ventas</span>
                    </a>
                </div>

                <div class="col-lg-2 col-6 p-1">
                    <a href="/licitaciones" class="action-btn">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                        <span>Compras</span>
                    </a>
                </div>

                <div class="col-lg-2 col-6 p-1">
                    <a href="/OrdenCompras" class="action-btn">
                        <i class="fa-solid fa-boxes-stacked"></i>
                        <span>Inventario</span>
                    </a>
                </div>

                <div class="col-lg-2 col-6 p-1">
                    <a href="/inventario" class="action-btn">
                        <i class="fa-solid fa-wallet"></i>
                        <span>Finanzas</span>
                    </a>
                </div>

                <div class="col-lg-2 col-6 p-1">
                    <a href="/Empleados" class="action-btn">
                        <i class="fa-solid fa-chart-line"></i>
                        <span>Reportes</span>
                    </a>
                </div>

                <div class="col-lg-2 col-6 p-1">
                    <a href="/Nominas" class="action-btn">
                        <i class="fa-solid fa-clipboard-list"></i>
                        <span>Administración</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Statistics Dashboard -->
            <div class="col">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Operación Continua</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div class="stat-number">98%</div>
                        <div class="stat-label">Cumplimiento</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-number">200</div>
                        <div class="stat-label">Procesos Activos</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-number">150+</div>
                        <div class="stat-label">Usuarios Activos</div>
                    </div>
                </div>
            </div>

             
            <!-- Órdenes de Compra Pendientes -->
            @if(($permiso2 == 'revision_ordenesCompra' || $permiso1 == 'acargo_ordenesCompra'))
                <div class="col">
                    @php
                        $ordenesPendientes = collect();
                        if($permiso2 == 'revision_ordenesCompra') {
                            $ordenesPendientes = $ordenesPendientes->merge($ordenesRevision ?? collect());
                        }
                        if($permiso1 == 'acargo_ordenesCompra') {
                            $ordenesPendientes = $ordenesPendientes->merge($ordenesAceptadas ?? collect());
                            $ordenesPendientes = $ordenesPendientes->merge($ordenesEnviadas ?? collect());
                        }
                    @endphp
                
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="stat-card" style="height: 430px; display: flex; flex-direction: column;">
                                <div class="d-flex align-items-center justify-content-between mb-0" style="flex-shrink: 0;">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon me-3">
                                            <i class="fas fa-shopping-cart"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-1 text-orange">Órdenes de Compra Pendientes</h5>
                                            <p class="mb-0 text-muted">Tienes {{ $ordenesPendientes->count() }} orden(es) que requieren tu atención</p>
                                        </div>
                                    </div>
                                    
                                </div>

                                <div class="row p-3">
                                    <a href="/OrdenCompras" class="btn btn-baseColor btn-sm">
                                        <i class="fas fa-external-link-alt me-1"></i>
                                        Ver Todas
                                    </a>
                                </div>
                                
                                @if($ordenesPendientes->count() > 0)
                                    <div class="flex-grow-1 overflow-auto" style="max-height: 280px;">
                                        <div class="list-group list-group-flush">
                                            @foreach($ordenesPendientes as $orden)
                                            <div class="card p-3 m-1 rounded-4">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <span class="badge 
                                                        @if($orden->estado == 'revision') bg-warning text-dark
                                                        @elseif($orden->estado == 'aceptado') bg-success
                                                        @elseif($orden->estado == 'enviado_proveedor') bg-info
                                                        @else bg-secondary
                                                        @endif">
                                                        @if($orden->estado == 'revision') Revisión
                                                        @elseif($orden->estado == 'aceptado') Aceptada
                                                        @elseif($orden->estado == 'enviado_proveedor') Enviada
                                                        @else {{ ucfirst($orden->estado) }}
                                                        @endif
                                                    </span>
                                                    <small class="text-muted">{{ $orden->folio }}</small>
                                                </div>
                                                
                                                <h6 class="mb-2 text-truncate text-start" title="{{ $orden->nombre }}">
                                                    {{ Str::limit($orden->nombre, 40) }}
                                                </h6>
                                                
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="fas fa-building me-1"></i>
                                                        {{ Str::limit($orden->proveedor->nombre ?? 'Sin proveedor', 25) }}
                                                    </small>
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        {{ \Carbon\Carbon::parse($orden->fecha_limite)->format('d/m') }}
                                                    </small>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                                
                                @if($ordenesPendientes->count() > 0)
                                <div class="text-center mt-2" style="flex-shrink: 0; border-top: 1px solid #e9ecef; padding-top: 10px;">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Total: {{ $ordenesPendientes->count() }} orden(es) pendiente(s)
                                    </small>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            
        </div>

        <!-- Charts Section -->
        <div class="charts-section">
            <h4 class="chart-title">Indicadores Generales del ERP</h4>
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-center mb-3">Operaciones Mensuales</h6>
                    <div class="chart-container">
                        <canvas id="productionChart"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="text-center mb-3">Distribución por Área</h6>
                    <div class="chart-container">
                        <canvas id="efficiencyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="row mb-3">
            <div class="col-md-6 mb-4">
                <div class="stat-card">
                    <h6 class="text-center mb-3">Rendimiento Operativo</h6>
                    <div class="metric-item">
                        <span class="metric-label">Eficiencia</span>
                        <span class="metric-value">93%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 93%"></div>
                    </div>
                    
                    <div class="metric-item">
                        <span class="metric-label">Seguimiento</span>
                        <span class="metric-value">89%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 89%"></div>
                    </div>
                    
                    <div class="metric-item">
                        <span class="metric-label">Cumplimiento</span>
                        <span class="metric-value">91%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 91%"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="stat-card">
                    <h6 class="text-center mb-3">Indicadores Administrativos</h6>
                    <div class="metric-item">
                        <span class="metric-label">Solicitudes pendientes</span>
                        <span class="metric-value">12</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-label">Procesos automatizados</span>
                        <span class="metric-value">24</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-label">Tareas programadas</span>
                        <span class="metric-value">8</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-label">Módulos activos</span>
                        <span class="metric-value">100%</span>
                    </div>
                </div>
            </div>
        </div>

       

        <!-- Education Visualization and Mission Statement -->
        <div class="row">
          
            <div class="col-lg-6">
                <div class="mission-statement mb-2">
                    <p class="mb-0">
                        "Centraliza tu operación, consulta indicadores clave y da seguimiento a los procesos de tu organización."
                    </p>
                </div>

                <!-- Suggestion Box -->
                <div class="suggestion-box mb-4">
                    <h6 class="mb-3 text-center">Buzón de Sugerencias</h6>
                    <div class="row">
                        <textarea class="suggestion-textarea" placeholder="Comparte ideas para mejorar la operación del sistema..."></textarea>

                        <div class="align-items-end mt-2">
                            <button class="send-btn text-truncate">
                                <i class="fas fa-paper-plane me-2"></i>
                                Enviar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Add some interactive animations
document.addEventListener('DOMContentLoaded', function() {
    // Animate stat cards on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.stat-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'all 0.6s ease';
        observer.observe(card);
    });
    
    // Add click effects to action buttons
   // document.querySelectorAll('.action-btn').forEach(btn => {
      //  btn.addEventListener('click', function(e) {
    //        e.preventDefault();
    //        this.style.transform = 'scale(0.95)';
     //       setTimeout(() => {
     //           this.style.transform = 'translateY(-3px)';
      //      }, 150);
       // });
    //
    //});

    // Operations Chart
    const productionCtx = document.getElementById('productionChart').getContext('2d');
    const productionChart = new Chart(productionCtx, {
        type: 'line',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
            datasets: [{
                label: 'Operaciones',
                data: [320, 410, 380, 520, 480, 560],
                borderColor: '#d946ef',
                backgroundColor: 'rgba(217, 70, 239, 0.16)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Meta',
                data: [300, 400, 400, 500, 500, 550],
                borderColor: '#f472b6',
                backgroundColor: 'rgba(244, 114, 182, 0.12)',
                borderDash: [5, 5],
                tension: 0.4,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: '#d4d4d8'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(244, 244, 245, 0.08)'
                    },
                    ticks: {
                        color: '#d4d4d8'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(244, 244, 245, 0.08)'
                    },
                    ticks: {
                        color: '#d4d4d8'
                    }
                }
            }
        }
    });

    // Area Distribution Chart
    const efficiencyCtx = document.getElementById('efficiencyChart').getContext('2d');
    const efficiencyChart = new Chart(efficiencyCtx, {
        type: 'doughnut',
        data: {
            labels: ['Ventas', 'Compras', 'Inventario', 'Administración'],
            datasets: [{
                data: [40, 30, 20, 10],
                backgroundColor: [
                    '#7e22ce',
                    '#c026d3',
                    '#db2777',
                    '#18181b'
                ],
                borderWidth: 2,
                borderColor: '#12091d'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#d4d4d8'
                    }
                }
            }
        }
    });

    // Animate progress bars
    setTimeout(() => {
        document.querySelectorAll('.progress-fill').forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0%';
            setTimeout(() => {
                bar.style.width = width;
            }, 500);
        });
    }, 1000);
});

</script>

@endsection