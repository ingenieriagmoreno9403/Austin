@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

@php
    $modulos = [
        [
            'permiso' => $permisos4,
            'valor' => 'registrar_usuarios',
            'url' => '/Sistemas/Registro',
            'titulo' => 'Nuevo Usuario',
            'desc' => 'Alta y gestión de usuarios por empleado en el sistema.',
            'icono' => 'fa-user-plus',
            'color' => 'success',
        ],
        [
            'permiso' => $permisos3,
            'valor' => 'registrar_perfiles',
            'url' => '/Sistemas/Perfiles',
            'titulo' => 'Asignación de perfiles',
            'desc' => 'Asignación de perfiles a usuarios y sucursales.',
            'icono' => 'fa-id-badge',
            'color' => 'cereza',
        ],
        [
            'permiso' => $permisos3,
            'valor' => 'registrar_perfiles',
            'url' => '/Sistemas/Empresas',
            'titulo' => 'Empresas y módulos',
            'desc' => 'Módulos vendidos por empresa y superusuario de cada cliente.',
            'icono' => 'fa-building',
            'color' => 'orange',
            'admin' => true,
        ],

        [
            'permiso' => $permisos5,
            'valor' => 'editar_permisos',
            'url' => '/Sistemas/Usuarios',
            'titulo' => 'Editar permisos',
            'desc' => 'Permisos de usuarios y acciones.',
            'icono' => 'fa-sitemap',
            'color' => 'secondary',
        ],

        [
            'permiso' => $permisos2,
            'valor' => 'registrar_acciones',
            'url' => '/Sistemas/Acciones',
            'titulo' => 'Registro de Elementos',
            'desc' => 'Alta de departamnetos, vistas, acciones y perfiles en el sistema.',
            'icono' => 'fa-bolt',
            'color' => 'primary',
            'admin' => true,
        ],
        
        [
            'permiso' => $permisos3,
            'valor' => 'registrar_perfiles',
            'url' => '/Sistemas/AccionesPerfiles',
            'titulo' => 'Asignar acciones a perfiles',
            'desc' => 'Añadir o quitar acciones disponibles para cada perfil.',
            'icono' => 'fa-link',
            'color' => 'primary',
            'admin' => true,
        ],
        
        [
            'permiso' => $permisos2,
            'valor' => 'registrar_acciones',
            'url' => '/Sistemas/facturacion',
            'titulo' => 'Configuración de facturación',
            'desc' => 'Configuración del pack de facturación.',
            'icono' => 'fa-file-invoice',
            'color' => 'primary',
            'admin' => true,
        ],

        [
            'permiso' => 'autin_api',
            'valor' => 'autin_api',
            'url' => '/Sistemas/AutinApi',
            'titulo' => 'AutinApi / SAP',
            'desc' => 'Endpoints y catálogos SAP (centros de costo, cuentas, presupuesto).',
            'icono' => 'fa-plug',
            'color' => 'cereza',
            'admin' => true,
        ],
    ];
@endphp

<div class="container-fluid p-4 pt-2">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg modern-header slide-in-left">
                <div class="card-body text-light p-4">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h2 class="fw-bold mb-2">
                                <i class="fa-solid fa-sliders me-3"></i>
                                Panel de configuración
                            </h2>
                            <p class="lead mb-0 fs-8">Módulos de permisos y alta de usuarios en el sistema.</p>
                        </div>
                        <div class="col-md-6 text-md-end mt-3 mt-md-0">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb justify-content-md-end mb-0">
                                    <li class="breadcrumb-item">
                                        <a href="/home" class="text-light text-decoration-none">
                                            <i class="fas fa-home me-1"></i>Inicio
                                        </a>
                                    </li>
                                    <li class="breadcrumb-item active text-light" aria-current="page">
                                        <i class="fa-solid fa-sliders me-1"></i>Sistemas
                                    </li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        @foreach ($modulos as $modulo)
            @if (!empty($esMasterEmpresa) && !empty($modulo['admin']))
                @continue
            @endif
            @php($habilitado = $modulo['permiso'] == $modulo['valor'])
            <div class="col-xl-4 col-lg-4 col-md-6">
                @if ($habilitado)
                    <a href="{{ $modulo['url'] }}" class="sistemas-module-card">
                @else
                    <div class="sistemas-module-card sistemas-module-card--disabled" aria-disabled="true">
                @endif
                        <div class="sistemas-module-card__body">
                            <div class="sistemas-module-card__top">
                                <div class="sistemas-module-card__icon sistemas-module-card__icon--{{ $modulo['color'] }}">
                                    <i class="fa-solid {{ $modulo['icono'] }}"></i>
                                </div>
                                <span class="sistemas-module-card__arrow">
                                    <i class="fa-solid fa-arrow-right"></i>
                                </span>
                            </div>
                            <h3 class="sistemas-module-card__title">{{ $modulo['titulo'] }}</h3>
                            <p class="sistemas-module-card__desc">{{ $modulo['desc'] }}</p>
                        </div>
                @if ($habilitado)
                    </a>
                @else
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection
