@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid p-4 pt-2 movimientos-tesoreria-page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg modern-header slide-in-left">
                <div class="card-body text-light p-4">
                    <div class="row align-items-center">
                        <div class="col-md-7">
                            <h2 class="fw-bold mb-2">
                                <i class="fas fa-chart-line me-3"></i>
                                Historial de Cuentas
                            </h2>
                            <p class="lead mb-0 fs-8">Gestión y control de movimientos financieros</p>
                        </div>
                        <div class="col-md-5 text-md-end mt-3 mt-md-0">
                            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-end gap-2">
                                @php($c1 = count($pendientes))
                                @php($c2 = count($solicitudes))
                                @if($permisos1 == "ver_detalles_movcuentas")
                                    <button class="btn btn-light btn-sm position-relative" onclick="toggleNotifications()">
                                        <i class="fas fa-bell"></i>
                                        @if($c1 + $c2 > 0)
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge">
                                                {{ $c1 + $c2 }}
                                            </span>
                                        @endif
                                    </button>
                                @endif
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb justify-content-md-end mb-0">
                                        <li class="breadcrumb-item">
                                            <a href="/home" class="text-light text-decoration-none">
                                                <i class="fas fa-home me-1"></i>Inicio
                                            </a>
                                        </li>
                                        <li class="breadcrumb-item active text-light" aria-current="page">
                                            <i class="fas fa-coins me-1"></i>Tesorería
                                        </li>
                                    </ol>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel Lateral de Notificaciones -->
    <div class="notification-panel" id="notificationPanel">
        <div class="notification-header">
            <h5 class="mb-0">
                <i class="fas fa-bell me-2" style="color: var(--accent-orange);"></i>
                Notificaciones
            </h5>
            <button class="btn btn-sm btn-outline-secondary" onclick="markAllAsRead()">
                <i class="fas fa-check-double me-1"></i>Todas
            </button>
            <button class="btn btn-sm btn-close" onclick="toggleNotifications()"></button>
        </div>
        <div class="notification-body">
            @foreach($pendientes as $item)
                <!-- Notificación Pendiente Arqueo -->
                <div class="notification-item unread" data-id="1">
                    <div class="notification-icon" style="background-color: rgba(109, 40, 217, 0.12);">
                        <i class="fas fa-exclamation-triangle" style="color: var(--secondary-orange);"></i>
                    </div>
                    <div class="notification-content">
                        <h6 class="fw-bold mb-1">Arqueo Pendiente por Autorizar</h6>
                        <p class="text-muted mb-2 small">La caja "{{$item->nombre}}" tiene su arqueo <b>generado</b> y pendiente por <b>autorizar</b>, recuerde que <b>no podrá continuar labores</b> la caja hasta que se autorice.</p>
                        <a href="/Tesoreria/Movimientos/Manejo/Cajas"><i class="fa-solid fa-arrow-up-right-from-square"></i> Ver Información</a> <br><br>
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="badge" style="background-color: var(--secondary-orange);">Crítica</span>
                            <small class="text-muted fs-9"> {{date('d/m/Y h:i:s A ', strtotime($item->created_at))}}</small>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-link text-muted notification-action" onclick="markAsRead(1)">
                        <i class="fas fa-check"></i>
                    </button>
                </div>
            @endforeach

            @foreach($solicitudes as $item)
                <!-- Notificación Pendiente Arqueo -->
                <div class="notification-item unread" data-id="1">
                    <div class="notification-icon" style="background-color: rgba(109, 40, 217, 0.12);">
                        <i class="fas fa-exclamation-triangle" style="color: var(--secondary-orange);"></i>
                    </div>
                    <div class="notification-content">
                        <h6 class="fw-bold mb-1">Solicitud de 
                            @if($item->concepto == "EFECTIVO")
                                actualización de efectivo en el arqueo
                            @else
                                cancelación  de arqueo
                            @endif
                        </h6>
                        <p class="text-muted mb-2 small">La caja "{{$item->nombre}}" quiere 
                            @if($item->concepto == "EFECTIVO")
                                actualizar el efectivo en el arqueo
                            @else
                                cancelar el arqueo
                            @endif
                            , menciona lo siguiente: <br> 
                            <b><i class="fa-solid fa-user"></i> {{$item->created_by}}</b>&nbsp; {{ucfirst(strtolower($item->comentario))}} 
                        </p>
                        <a href="/Tesoreria/Movimientos/Manejo/Cajas"><i class="fa-solid fa-arrow-up-right-from-square"></i> Ver Información</a> <br><br>
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="badge" style="background-color: var(--secondary-orange);">Crítica</span>
                            <small class="text-muted fs-9"> {{date('d/m/Y h:i:s A ', strtotime($item->created_at))}}</small>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-link text-muted notification-action" onclick="markAsRead(1)">
                        <i class="fas fa-check"></i>
                    </button>
                </div>
            @endforeach
        </div>
        <div class="notification-footer">
            <a href="#" class="text-decoration-none" style="color: var(--accent-orange);">
                <i class="fas fa-eye me-1"></i>Ver todas
            </a>
        </div>
    </div>

    <!-- Overlay para cerrar notificaciones -->
    <div class="notification-overlay" id="notificationOverlay" onclick="toggleNotifications()"></div>

    <div class="row mb-4">
        <div class="col-md-9">
            <!-- Gráfica de Movimientos -->
            @if($permisos1 == "ver_detalles_movcuentas")
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0">
                            <h5 class="card-title mb-0 p-2">
                                <i class="fas fa-chart-area me-2" style="color: var(--primary-orange);"></i>
                                Movimientos Financieros (Últimos 7 días)
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="movementsChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Estadísticas de la Semana -->
            @if($permisos1 == "ver_detalles_movcuentas")
            @php($balanceSemanal = $datosGrafica['balance'] ?? 0)
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <div class="card border-0 shadow-sm h-100 teso-stat-card teso-stat-card--ingresos">
                        <div class="card-body">
                            <div class="teso-stat-card__top">
                                <span class="teso-stat-card__label">Total Ingresos Semana</span>
                                <div class="teso-stat-card__icon">
                                    <i class="fas fa-arrow-up"></i>
                                </div>
                            </div>
                            <div class="teso-stat-card__amount">${{ number_format($datosGrafica['totalIngresos'] ?? 0, 2) }}</div>
                            <div class="teso-stat-card__meta">
                                <i class="fas fa-calendar-week me-1"></i>Últimos 7 días
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="card border-0 shadow-sm h-100 teso-stat-card teso-stat-card--egresos">
                        <div class="card-body">
                            <div class="teso-stat-card__top">
                                <span class="teso-stat-card__label">Total Egresos Semana</span>
                                <div class="teso-stat-card__icon">
                                    <i class="fas fa-arrow-down"></i>
                                </div>
                            </div>
                            <div class="teso-stat-card__amount">${{ number_format($datosGrafica['totalEgresos'] ?? 0, 2) }}</div>
                            <div class="teso-stat-card__meta">
                                <i class="fas fa-calendar-week me-1"></i>Últimos 7 días
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="card border-0 shadow-sm h-100 teso-stat-card {{ $balanceSemanal >= 0 ? 'teso-stat-card--balance-pos' : 'teso-stat-card--balance-neg' }}">
                        <div class="card-body">
                            <div class="teso-stat-card__top">
                                <span class="teso-stat-card__label">Balance Semanal</span>
                                <div class="teso-stat-card__icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div>
                            <div class="teso-stat-card__amount">${{ number_format($balanceSemanal, 2) }}</div>
                            <div class="teso-stat-card__meta">
                                <i class="fas fa-{{ $balanceSemanal >= 0 ? 'arrow-up' : 'arrow-down' }} me-1"></i>
                                {{ $balanceSemanal >= 0 ? 'Positivo' : 'Negativo' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Sección de Reportes -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header mb-0 pb-0 bg-white border-0 p-3">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-file-lines me-2" style="color: var(--accent-orange);"></i>
                                Reportes
                            </h5>
                            <div class="text-muted small mb-0">Resumen financiero</div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-4 mb-4">
                                    <div class="card h-100 teso-report-card teso-report-card--posicion" onclick="window.location.href='/Tesoreria/Movimientos/Reportes/PosicionFinanciera'">
                                        <div class="card-body p-4">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h5 class="fw-bold mb-2 teso-report-card__title">Posición Financiera</h5>
                                                </div>
                                                <div class="rounded-circle p-3 teso-report-card__icon-wrap teso-report-card__icon-wrap--posicion">
                                                    <i class="fas fa-scale-balanced fa-2x teso-report-card__icon teso-report-card__icon--posicion"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-4 mb-4">
                                    <div class="card h-100 teso-report-card teso-report-card--estado" onclick="window.location.href='/Tesoreria/Movimientos/Reportes/EstadoCuenta'">
                                        <div class="card-body p-4">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h5 class="fw-bold mb-2 teso-report-card__title teso-report-card__title--estado">Estado de Cuenta</h5>
                                                </div>
                                                <div class="rounded-circle p-3 teso-report-card__icon-wrap teso-report-card__icon-wrap--estado">
                                                    <i class="fas fa-file-invoice-dollar fa-2x teso-report-card__icon teso-report-card__icon--estado"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-4 mb-4">
                                    <div class="card h-100 teso-report-card teso-report-card--arqueo" onclick="window.location.href='/Tesoreria/Movimientos/Reportes/ArqueoCajas'">
                                        <div class="card-body p-4">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h5 class="fw-bold mb-2 teso-report-card__title teso-report-card__title--arqueo">Arqueo de Cajas</h5>
                                                </div>
                                                <div class="rounded-circle p-3 teso-report-card__icon-wrap teso-report-card__icon-wrap--arqueo">
                                                    <i class="fas fa-cash-register fa-2x teso-report-card__icon teso-report-card__icon--arqueo"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <!-- Sección de Manejo -->
            @if($varValidaPermisoCuentas != "no")
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white border-0">
                                <h5 class="card-title mb-0 p-2 text-center">
                                    <i class="fas fa-cogs me-2" style="color: var(--secondary-orange);"></i>
                                    Gestión y Control
                                </h5>
                                <div class="text-muted small text-center">Movimientos Hoy</div>
                                <div class="h6 fw-bold text-center" style="color: var(--light-orange);">{{($movmientos_cuenta + $movmientos_cajas) ?? 0}}</div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    @foreach($varValidaPermisoCuentas as $data1)
                                        @if($data1->tipo == "cuenta")@php($cuenta = "si")@break
                                        @else @php($cuenta = "no")@endif
                                    @endforeach

                                    @foreach($varValidaPermisoCuentas as $data2)
                                        @if($data2->tipo == "caja")@php($caja = "si")@break
                                        @else @php($caja = "no")@endif
                                    @endforeach

                                    @foreach($varValidaPermisoCuentas as $data3)
                                        @if($data3->tipo == "caja_chica") @php($caja_chica = "si")@break
                                        @else @php($caja_chica = "no")@endif
                                    @endforeach

                                    <div class="col-lg-12 mb-4">
                                        <div class="card border-0 h-100 teso-action-card teso-action-card--cuentas" onclick="window.location.href='/Tesoreria/Movimientos/Manejo/Cuentas'">
                                            <div class="card-body text-white p-4">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <h4 class="fw-bold mb-2">Cuentas</h4>
                                                        <div class="d-flex align-items-center">
                                                            <span class="badge bg-white bg-opacity-25 me-2">{{$cuentas ?? 0}} Activas</span>
                                                            <i class="fas fa-arrow-right"></i>
                                                        </div>
                                                    </div>
                                                    <div class="bg-white bg-opacity-25 rounded-circle p-3">
                                                        <i class="fas fa-building-columns fa-2x"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12 mb-4">
                                        <div class="card border-0 h-100 teso-action-card teso-action-card--cajas" onclick="window.location.href='/Tesoreria/Movimientos/Manejo/Cajas'">
                                            <div class="card-body text-white p-4">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <h4 class="fw-bold mb-2">Cajas</h4>
                                                        <div class="d-flex align-items-center">
                                                            <span class="badge bg-white bg-opacity-25 me-2">{{$cajas ?? 0}} Activas</span>
                                                            <i class="fas fa-arrow-right"></i>
                                                        </div>
                                                    </div>
                                                    <div class="bg-white bg-opacity-25 rounded-circle p-3">
                                                        <i class="fas fa-cash-register fa-2x"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12 mb-4">
                                        <div class="card border-0 h-100 teso-action-card teso-action-card--cajas-chicas" onclick="window.location.href='/Tesoreria/Movimientos/Manejo/Cajas Chicas'">
                                            <div class="card-body text-dark p-4">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <h4 class="fw-bold mb-2">Cajas Chicas</h4>
                                                        <div class="d-flex align-items-center">
                                                            <span class="badge me-2">{{$cajas_chicas ?? 0}} Activas</span>
                                                            <i class="fas fa-arrow-right"></i>
                                                        </div>
                                                    </div>
                                                    <div class="teso-action-card__icon-wrap rounded-circle p-3">
                                                        <i class="fas fa-circle-dollar-to-slot fa-2x"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Sección de Responsables -->
            @if($varValidaResponsableCaja != "no")
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white border-0 p-3">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user-tie me-2" style="color: var(--secondary-orange);"></i>
                                    Responsables de Caja
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    @foreach ($varValidaResponsableCaja as $caja)
                                        @if($caja->tipo == "caja")
                                            <div class="col-lg-12 mb-4">
                                                <div class="card border-0 h-100 teso-action-card teso-action-card--responsable" onclick="window.location.href='/Tesoreria/Movimientos/Responsable/Caja/{{$caja->idempresa}}/{{$caja->id}}'">
                                                    <div class="card-body text-white p-4">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div class="flex-grow-1">
                                                                <div class="d-flex align-items-center mb-2">
                                                                    <span class="badge bg-white bg-opacity-25 me-2">Responsable</span>
                                                                    <i class="fas fa-user-check"></i>
                                                                </div>
                                                                <h4 class="fw-bold mb-2">{{$caja->nombre}}</h4>
                                                                <p class="mb-3 opacity-75">Gestión de caja principal</p>
                                                                <div class="d-flex align-items-center">
                                                                    <span class="badge bg-white bg-opacity-25 me-2">Caja Principal</span>
                                                                    <i class="fas fa-arrow-right"></i>
                                                                </div>
                                                            </div>
                                                            <div class="bg-white bg-opacity-25 rounded-circle p-3">
                                                                <i class="fas fa-cash-register fa-2x"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="col-lg-12 mb-4">
                                                <div class="card border-0 h-100 teso-action-card teso-action-card--caja-chica-resp" onclick="window.location.href='/Tesoreria/Movimientos/ResponsableCajaChica/Caja Chica/{{$caja->idempresa}}/{{$caja->id}}'">
                                                    <div class="card-body text-white p-4">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div class="flex-grow-1">
                                                                <div class="d-flex align-items-center mb-2">
                                                                    <span class="badge bg-white bg-opacity-25 me-2">Caja Chica</span>
                                                                    <i class="fas fa-coins"></i>
                                                                </div>
                                                                <h4 class="fw-bold mb-2">{{$caja->nombre}}</h4>
                                                                <p class="mb-3 opacity-75">Gestión de caja chica</p>
                                                                <div class="d-flex align-items-center">
                                                                    <span class="badge bg-white bg-opacity-25 me-2">Fondo Menor</span>
                                                                    <i class="fas fa-arrow-right"></i>
                                                                </div>
                                                            </div>
                                                            <div class="bg-white bg-opacity-25 rounded-circle p-3">
                                                                <i class="fas fa-circle-dollar-to-slot fa-2x"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            
        </div>
    </div>
</div>

<!-- Scripts para las gráficas -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gráfica de movimientos financieros
        const movementsCtx = document.getElementById('movementsChart').getContext('2d');
        new Chart(movementsCtx, {
            type: 'line',
                            data: {
                        labels: @json($datosGrafica['fechas']),
                        datasets: [{
                            label: 'Ingresos',
                            data: @json($datosGrafica['ingresos']),
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.12)',
                            tension: 0.4,
                            fill: true
                        }, {
                            label: 'Egresos',
                            data: @json($datosGrafica['egresos']),
                            borderColor: '#6d28d9',
                            backgroundColor: 'rgba(109, 40, 217, 0.12)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Monto ($)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += '$' + context.parsed.y.toLocaleString();
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });

        // Gráfica de distribución por tipo
        const distributionCanvas = document.getElementById('distributionChart');
        if (distributionCanvas) {
            const distributionCtx = distributionCanvas.getContext('2d');
            new Chart(distributionCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Cuentas', 'Cajas', 'Cajas Chicas'],
                    datasets: [{
                        data: [24, 8, 12],
                        backgroundColor: [
                            '#3b82f6',
                            '#6d28d9',
                            '#10b981'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });
        }
    });

    // Funciones para el panel de notificaciones
    function toggleNotifications() {
        const panel = document.getElementById('notificationPanel');
        const overlay = document.getElementById('notificationOverlay');
        
        if (panel.classList.contains('show')) {
            panel.classList.remove('show');
            overlay.classList.remove('show');
        } else {
            panel.classList.add('show');
            overlay.classList.add('show');
        }
    }

    function markAsRead(notificationId) {
        const notification = document.querySelector(`[data-id="${notificationId}"]`);
        if (notification) {
            notification.classList.remove('unread');
            notification.classList.add('read');
            notification.style.opacity = '0.7';
            updateNotificationCount();
        }
    }

    function markAllAsRead() {
        const unreadNotifications = document.querySelectorAll('.notification-item.unread');
        unreadNotifications.forEach(notification => {
            notification.classList.remove('unread');
            notification.classList.add('read');
            notification.style.opacity = '0.7';
        });
        updateNotificationCount();
    }

    function updateNotificationCount() {
        const unreadCount = document.querySelectorAll('.notification-item.unread').length;
        const badge = document.getElementById('notificationBadge');
        if (badge) {
            if (unreadCount > 0) {
                badge.textContent = unreadCount;
                badge.classList.remove('bg-secondary');
                badge.classList.add('bg-primary');
            } else {
                badge.textContent = '0';
                badge.classList.remove('bg-primary');
                badge.classList.add('bg-secondary');
            }
        }
    }
</script>

<style>
:root {
    --teso-blue: #3b82f6;
    --teso-blue-dark: #1e3a8a;
    --teso-purple: #6d28d9;
    --teso-purple-dark: #4c1d95;
    --teso-green: #10b981;
    --teso-green-dark: #059669;
    --teso-sky: #60a5fa;
    --primary-orange: var(--teso-blue);
    --secondary-orange: var(--teso-purple);
    --light-orange: var(--teso-sky);
    --accent-orange: var(--teso-green);
}

.movimientos-tesoreria-page .teso-stat-card {
    border-radius: 12px;
    overflow: hidden;
    border-left: 4px solid transparent !important;
}

.movimientos-tesoreria-page .teso-stat-card .card-body {
    padding: 1.1rem 1.15rem;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    min-height: 132px;
}

.movimientos-tesoreria-page .teso-stat-card__top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
}

.movimientos-tesoreria-page .teso-stat-card__label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #6b7280;
    line-height: 1.3;
}

.movimientos-tesoreria-page .teso-stat-card__icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1rem;
}

.movimientos-tesoreria-page .teso-stat-card__amount {
    font-size: clamp(1.15rem, 1.8vw, 1.55rem);
    font-weight: 700;
    line-height: 1.2;
    letter-spacing: -0.02em;
    word-break: break-word;
    font-variant-numeric: tabular-nums;
}

.movimientos-tesoreria-page .teso-stat-card__meta {
    font-size: 0.78rem;
    font-weight: 500;
    margin-top: auto;
}

.movimientos-tesoreria-page .teso-stat-card--ingresos {
    border-left-color: var(--teso-green) !important;
}

.movimientos-tesoreria-page .teso-stat-card--ingresos .teso-stat-card__amount,
.movimientos-tesoreria-page .teso-stat-card--ingresos .teso-stat-card__meta {
    color: var(--teso-green);
}

.movimientos-tesoreria-page .teso-stat-card--ingresos .teso-stat-card__icon {
    background-color: rgba(16, 185, 129, 0.12);
    color: var(--teso-green);
}

.movimientos-tesoreria-page .teso-stat-card--egresos {
    border-left-color: var(--teso-purple) !important;
}

.movimientos-tesoreria-page .teso-stat-card--egresos .teso-stat-card__amount,
.movimientos-tesoreria-page .teso-stat-card--egresos .teso-stat-card__meta {
    color: var(--teso-purple);
}

.movimientos-tesoreria-page .teso-stat-card--egresos .teso-stat-card__icon {
    background-color: rgba(109, 40, 217, 0.12);
    color: var(--teso-purple);
}

.movimientos-tesoreria-page .teso-stat-card--balance-pos {
    border-left-color: var(--teso-green) !important;
}

.movimientos-tesoreria-page .teso-stat-card--balance-pos .teso-stat-card__amount,
.movimientos-tesoreria-page .teso-stat-card--balance-pos .teso-stat-card__meta {
    color: var(--teso-green);
}

.movimientos-tesoreria-page .teso-stat-card--balance-pos .teso-stat-card__icon {
    background-color: rgba(16, 185, 129, 0.12);
    color: var(--teso-green);
}

.movimientos-tesoreria-page .teso-stat-card--balance-neg {
    border-left-color: var(--teso-purple) !important;
}

.movimientos-tesoreria-page .teso-stat-card--balance-neg .teso-stat-card__amount,
.movimientos-tesoreria-page .teso-stat-card--balance-neg .teso-stat-card__meta {
    color: var(--teso-purple);
}

.movimientos-tesoreria-page .teso-stat-card--balance-neg .teso-stat-card__icon {
    background-color: rgba(109, 40, 217, 0.12);
    color: var(--teso-purple);
}

.movimientos-tesoreria-page .teso-action-card {
    cursor: pointer;
    border: 0 !important;
    color: #ffffff !important;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.movimientos-tesoreria-page .teso-action-card .card-body {
    background: transparent !important;
    color: #ffffff !important;
}

.movimientos-tesoreria-page .teso-action-card--cuentas {
    background-color: #030712 !important;
    box-shadow: 0 10px 24px rgba(3, 7, 18, 0.28);
}

.movimientos-tesoreria-page .teso-action-card--cajas {
    background-color: #4b5563 !important;
    box-shadow: 0 10px 24px rgba(75, 85, 99, 0.28);
}

.movimientos-tesoreria-page .teso-action-card--cajas-chicas {
    background-color: #d1d5db !important;
    color: #111827 !important;
    box-shadow: 0 10px 24px rgba(209, 213, 219, 0.45);
}

.movimientos-tesoreria-page .teso-action-card--cajas-chicas .card-body {
    color: #111827 !important;
}

.movimientos-tesoreria-page .teso-action-card--cajas-chicas .badge {
    background-color: rgba(17, 24, 39, 0.12) !important;
    color: #111827 !important;
}

.movimientos-tesoreria-page .teso-action-card--cajas-chicas .teso-action-card__icon-wrap {
    background-color: rgba(17, 24, 39, 0.1) !important;
    color: #111827 !important;
}

.movimientos-tesoreria-page .teso-action-card--responsable {
    background-color: #030712 !important;
    box-shadow: 0 10px 24px rgba(3, 7, 18, 0.28);
}

.movimientos-tesoreria-page .teso-action-card--caja-chica-resp {
    background-color: #4b5563 !important;
    box-shadow: 0 10px 24px rgba(75, 85, 99, 0.28);
}

.movimientos-tesoreria-page .teso-action-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 14px 30px rgba(17, 24, 39, 0.32);
}

.movimientos-tesoreria-page .teso-report-card:hover {
    transform: translateY(-4px);
}

.movimientos-tesoreria-page .teso-report-card {
    cursor: pointer;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.movimientos-tesoreria-page .teso-report-card .card-body {
    background: transparent !important;
}

.movimientos-tesoreria-page .teso-report-card--posicion {
    background: #e0f2fe !important;
    border: 2px solid #38bdf8 !important;
    color: #0284c7 !important;
}

.movimientos-tesoreria-page .teso-report-card--estado {
    background: #ede9fe !important;
    border: 2px solid #a78bfa !important;
    color: #7c3aed !important;
}

.movimientos-tesoreria-page .teso-report-card--arqueo {
    background: #dcfce7 !important;
    border: 2px solid #4ade80 !important;
    color: #16a34a !important;
}

.movimientos-tesoreria-page .teso-report-card__title {
    color: #0284c7 !important;
}

.movimientos-tesoreria-page .teso-report-card__title--estado {
    color: #7c3aed !important;
}

.movimientos-tesoreria-page .teso-report-card__title--arqueo {
    color: #16a34a !important;
}

.movimientos-tesoreria-page .teso-report-card__icon-wrap--posicion {
    background-color: rgba(14, 165, 233, 0.15) !important;
}

.movimientos-tesoreria-page .teso-report-card__icon-wrap--estado {
    background-color: rgba(139, 92, 246, 0.15) !important;
}

.movimientos-tesoreria-page .teso-report-card__icon-wrap--arqueo {
    background-color: rgba(34, 197, 94, 0.15) !important;
}

.movimientos-tesoreria-page .teso-report-card__icon--posicion {
    color: #0284c7 !important;
}

.movimientos-tesoreria-page .teso-report-card__icon--estado {
    color: #7c3aed !important;
}

.movimientos-tesoreria-page .teso-report-card__icon--arqueo {
    color: #16a34a !important;
}

.movimientos-tesoreria-page .card {
    transition: transform 0.2s ease-in-out;
}

.movimientos-tesoreria-page .card[onclick]:hover {
    transform: translateY(-3px) scale(1.01);
}

.movimientos-tesoreria-page .list-group-item:hover {
    background-color: #f8f9fa;
}

/* Panel lateral de notificaciones */
.notification-panel {
    position: fixed;
    top: 0;
    right: -400px;
    width: 380px;
    height: 100vh;
    background: white;
    box-shadow: -5px 0 15px rgba(0,0,0,0.1);
    z-index: 1050;
    transition: right 0.3s ease;
    display: flex;
    flex-direction: column;
}

.notification-panel.show {
    right: 0;
}

.notification-header {
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8f9fa;
}

.notification-body {
    flex: 1;
    overflow-y: auto;
    padding: 0;
}

.notification-footer {
    padding: 1rem;
    border-top: 1px solid #dee2e6;
    text-align: center;
    background: #f8f9fa;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    padding: 1rem;
    border-bottom: 1px solid #f1f3f4;
    transition: all 0.3s ease;
    position: relative;
}

.notification-item.unread {
    background-color: #f8f9fa;
    border-left: 4px solid var(--secondary-orange);
}

.notification-item.unread:hover {
    background-color: #e9ecef;
}

.notification-item.read {
    opacity: 0.7;
}

.notification-item.read:hover {
    opacity: 1;
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.75rem;
    flex-shrink: 0;
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-action {
    padding: 0.25rem;
    margin-left: 0.5rem;
    flex-shrink: 0;
}

/* Overlay */
.notification-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1040;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.notification-overlay.show {
    opacity: 1;
    visibility: visible;
}

/* Responsive */
@media (max-width: 768px) {
    .notification-panel {
        width: 100%;
        right: -100%;
    }
}

/* Animaciones personalizadas */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeInUp 0.6s ease-out;
}

.card:nth-child(1) { animation-delay: 0.1s; }
.card:nth-child(2) { animation-delay: 0.2s; }
.card:nth-child(3) { animation-delay: 0.3s; }
.card:nth-child(4) { animation-delay: 0.4s; }

/* Estilos para breadcrumb personalizado */
.breadcrumb-item a:hover {
    text-decoration: underline !important;
}
</style>

<script src="{{ asset('js/simpleTabla2.js') }}"></script>
<script src="{{ asset('js/validation.js') }}"></script>
@endsection