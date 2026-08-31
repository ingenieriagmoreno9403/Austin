@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-file-signature"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Nueva Comparativa</h2>
                        <p class="text-muted mb-0">Registro de una nueva comparativa de precios</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="form-licitacion" action="{{ route('licitaciones.store') }}" method="POST" enctype="multipart/form-data"
        class="needs-validation" novalidate>
        @csrf
        <input type="hidden" id="detalle_json" name="detalle_json">

        <div class="lic-form__section">
            <div class="lic-form__section-head">
                <div class="lic-form__section-title-wrap">
                    <div class="lic-form__section-icon" aria-hidden="true">
                        <i class="fa-solid fa-clipboard-list"></i>
                    </div>
                    <div>
                        <h3 class="lic-form__section-title">1. Información general</h3>
                        <p class="lic-form__section-subtitle">Datos básicos de la comparativa.</p>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="lic-form__label" for="nombre">Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nombre" id="nombre"
                        minlength="3" maxlength="50" required>
                </div>
                <div class="col-md-3">
                    <label class="lic-form__label" for="fecha_creacion">Fecha creación <span class="text-danger">*</span></label>
                    <input type="date" id="fecha_creacion" name="fecha_creacion"
                        class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="lic-form__label" for="fecha_limite">Fecha límite <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_limite" id="fecha_limite" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="lic-form__label" for="descripcion_detalle">Descripción <span class="text-danger">*</span></label>
                    <textarea name="descripcion_detalle" id="descripcion_detalle" class="form-control" required></textarea>
                </div>
            </div>
        </div>

        <div class="lic-form__section lic-form__section--proveedores" id="seccion-proveedores">
            <div class="lic-form__section-head">
                <div class="lic-form__section-title-wrap">
                    <div class="lic-form__section-icon" aria-hidden="true">
                        <i class="fa-solid fa-handshake"></i>
                    </div>
                    <div>
                        <h3 class="lic-form__section-title">2. Proveedores invitados</h3>
                        <p class="lic-form__section-subtitle">Selecciona al menos un proveedor para continuar.</p>
                    </div>
                </div>
                <span class="lic-form__badge-req">
                    <i class="fa-solid fa-asterisk"></i> Obligatorio
                </span>
            </div>

            @livewire('proveedor-selector', ['sololectura' => false])
            @error('proveedoresSeleccionados')
                <div class="alert alert-danger mt-3 mb-0">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div id="productos-container">
            <div id="alerta-productos-vacios" class="alert alert-warning mb-3" style="display: none;">
                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                <strong>Atención:</strong> Debe agregar al menos un producto a la licitación.
            </div>

            @livewire('licitacion-detalle', ['sololectura' => false])
        </div>

        <div class="lic-form__actions">
            <button type="button" class="btn btn-outline-secondary"
                onclick="window.location='{{ route('licitaciones.index') }}'">
                <i class="fa-solid fa-arrow-left me-1"></i> Volver
            </button>

            <button type="button" id="btn-guardar" onclick="guardarLicitacion()" class="btn btn-baseColor">
                <i class="fa-solid fa-check me-1"></i> Crear Comparativa
            </button>

            <button type="button" id="btn-guardando" class="btn btn-baseColor" style="display: none;" disabled>
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                Guardando...
            </button>
        </div>
    </form>
</div>

<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/validaPDF.js') }}"></script>
<script>
    function toggleBotonesGuardar(mostrarSpinner) {
        const btnGuardar = document.getElementById('btn-guardar');
        const btnGuardando = document.getElementById('btn-guardando');

        if (mostrarSpinner) {
            btnGuardar.style.display = 'none';
            btnGuardando.style.display = 'inline-block';
        } else {
            btnGuardar.style.display = 'inline-block';
            btnGuardando.style.display = 'none';
        }
    }

    function guardarLicitacion() {
        document.getElementById('alerta-productos-vacios').style.display = 'none';
        toggleBotonesGuardar(true);
        Livewire.emit('guardarTodo');
    }

    window.addEventListener('producto-duplicado', event => {
        toggleBotonesGuardar(false);

        const toast = document.createElement('div');
        toast.className = 'position-fixed bottom-0 end-0 p-3';
        toast.style.zIndex = 11;
        toast.innerHTML = `
            <div class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fa-solid fa-circle-exclamation me-2"></i>
                        ${event.detail.mensaje}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        document.body.appendChild(toast);

        const toastElement = toast.querySelector('.toast');
        const bsToast = new bootstrap.Toast(toastElement, {autohide: true, delay: 5000});
        bsToast.show();

        toastElement.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    });

    window.addEventListener('proveedores-validos', function () {
        document.querySelector('#form-licitacion').submit();
    });

    window.addEventListener('proveedores-no-validos', function (event) {
        toggleBotonesGuardar(false);
        const seccion = document.getElementById('seccion-proveedores');
        if (seccion) {
            seccion.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        Swal.fire({
            icon: 'warning',
            title: 'Proveedores requeridos',
            text: event.detail.mensaje || 'Debes seleccionar al menos un proveedor.',
            confirmButtonColor: '#0284c7'
        });
    });

    window.addEventListener('error-evento', function (event) {
        toggleBotonesGuardar(false);

        Swal.fire({
            icon: 'error',
            title: '¡Error!',
            text: event.detail.mensaje,
            confirmButtonColor: '#3085d6'
        });

        document.getElementById('productos-container').scrollIntoView({behavior: 'smooth'});
    });

    window.addEventListener('set-detalle-json', function (event) {
        document.getElementById('detalle_json').value = event.detail.detalle;
    });

    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date();
        today.setDate(today.getDate() + 1);
        const tomorrow = today.toISOString().split('T')[0];
        document.getElementById('fecha_limite').min = tomorrow;
    });
</script>
@endsection
