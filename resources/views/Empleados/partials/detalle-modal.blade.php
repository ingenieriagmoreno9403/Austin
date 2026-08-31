@php
    $nombreCompleto = trim(implode(' ', array_filter([
        $vis->primer_nombre,
        $vis->segundo_nombre,
        $vis->apellido_paterno,
        $vis->apellido_materno,
    ])));
    $d = fn ($v) => filled($v) ? $v : '—';
@endphp

<div class="modal fade empleado-detalle-modal" id="datosClientes{{ $vis->idempleado }}" tabindex="-1"
    aria-labelledby="detalleEmpleadoLabel{{ $vis->idempleado }}" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header empleado-detalle-header">
                <div class="empleado-detalle-profile">
                    <img class="empleado-detalle-avatar"
                        src="{{ asset('Images/Perfil/' . $vis->nombre_foto) }}"
                        alt="Foto de {{ $nombreCompleto }}">
                    <div class="empleado-detalle-intro">
                        <span class="empleado-detalle-kicker">Detalle del empleado</span>
                        <h2 class="empleado-detalle-name" id="detalleEmpleadoLabel{{ $vis->idempleado }}">
                            {{ $nombreCompleto }}
                        </h2>
                        <div class="empleado-detalle-badges">
                            @if ($vis->estado == 'A')
                                <span class="badge badge-success-dark">Activo</span>
                            @else
                                <span class="badge badge-danger-dark">Inactivo</span>
                            @endif
                            <span class="empleado-detalle-chip">{{ $d($vis->puesto) }}</span>
                            <span class="empleado-detalle-chip">{{ $d($vis->sucursal) }}</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body empleado-detalle-body">
                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <section class="empleado-detalle-section">
                            <h3 class="empleado-detalle-section-title">
                                <i class="fa-solid fa-id-card"></i> Identificación
                            </h3>
                            <dl class="empleado-detalle-grid">
                                <div class="empleado-detalle-item"><dt>No.</dt><dd>{{ $d($vis->idempleado ?? $vis->id) }}</dd></div>
                                <div class="empleado-detalle-item"><dt>RFC</dt><dd>{{ $d($vis->rfc) }}</dd></div>
                                <div class="empleado-detalle-item"><dt>NSS</dt><dd>{{ $d($vis->nss) }}</dd></div>
                                <div class="empleado-detalle-item"><dt>CURP</dt><dd>{{ $d($vis->curp) }}</dd></div>
                            </dl>
                        </section>
                    </div>

                    <div class="col-12 col-lg-6">
                        <section class="empleado-detalle-section">
                            <h3 class="empleado-detalle-section-title">
                                <i class="fa-solid fa-address-book"></i> Contacto
                            </h3>
                            <dl class="empleado-detalle-grid">
                                <div class="empleado-detalle-item"><dt>Teléfono</dt><dd>{{ $d($vis->telefono) }}</dd></div>
                                <div class="empleado-detalle-item"><dt>Correo</dt><dd>{{ $d($vis->correo) }}</dd></div>
                                <div class="empleado-detalle-item"><dt>Nacionalidad</dt><dd>{{ $d($vis->nacionalidad) }}</dd></div>
                            </dl>
                        </section>
                    </div>

                    <div class="col-12 col-lg-6">
                        <section class="empleado-detalle-section">
                            <h3 class="empleado-detalle-section-title">
                                <i class="fa-solid fa-briefcase"></i> Información laboral
                            </h3>
                            <dl class="empleado-detalle-grid">
                                <div class="empleado-detalle-item"><dt>Puesto</dt><dd>{{ $d($vis->puesto) }}</dd></div>
                                <div class="empleado-detalle-item"><dt>Sucursal</dt><dd>{{ $d($vis->sucursal) }}</dd></div>
                                <div class="empleado-detalle-item"><dt>Empresa</dt><dd>{{ $d($vis->empresa) }}</dd></div>
                                <div class="empleado-detalle-item"><dt>Zona</dt>
                                    <dd>
                                        @if (($vis->zona ?? '') === 'ZFN')
                                            Zona Libre de la Frontera Norte (ZFN)
                                        @elseif (($vis->zona ?? '') === 'RP')
                                            Resto del país (RP)
                                        @else
                                            —
                                        @endif
                                    </dd>
                                </div>
                                <div class="empleado-detalle-item"><dt>Fecha ingreso</dt><dd>{{ $d($vis->fecha_ingreso ?? $vis->fecha_alta ?? null) }}</dd></div>
                                <div class="empleado-detalle-item"><dt>Tipo contratación</dt>
                                    <dd>
                                        @if ($vis->tipo_contratacion == 'INDEFINIDA')
                                            <span class="badge badge-primary fs-9">INDEFINIDA</span>
                                        @elseif ($vis->tipo_contratacion == 'DEFINIDA')
                                            <span class="badge badge-orange fs-9">DEFINIDA</span>
                                        @else
                                            {{ $d($vis->tipo_contratacion) }}
                                        @endif
                                    </dd>
                                </div>
                                <div class="empleado-detalle-item"><dt>Vencimiento contrato</dt>
                                    <dd>
                                        @if ($vis->tipo_contratacion == 'DEFINIDA' && filled($vis->fecha_determinado))
                                            {{ $vis->fecha_determinado }}
                                        @else
                                            —
                                        @endif
                                    </dd>
                                </div>
                                <div class="empleado-detalle-item"><dt>Fecha ingreso IMSS</dt><dd>{{ $d($vis->fecha_ingreso_imss) }}</dd></div>
                                <div class="empleado-detalle-item"><dt>Grado de estudios</dt><dd>{{ $d($vis->grado_estudio) }}</dd></div>
                            </dl>
                        </section>
                    </div>

                    <div class="col-12 col-lg-6">
                        <section class="empleado-detalle-section">
                            <h3 class="empleado-detalle-section-title">
                                <i class="fa-solid fa-location-dot"></i> Domicilio
                            </h3>
                            <dl class="empleado-detalle-grid">
                                <div class="empleado-detalle-item empleado-detalle-item--full"><dt>Calle</dt><dd>{{ $d($vis->calle) }}</dd></div>
                                <div class="empleado-detalle-item"><dt>Colonia</dt><dd>{{ $d($vis->colonia) }}</dd></div>
                                <div class="empleado-detalle-item"><dt>Ciudad</dt><dd>{{ $d($vis->ciudad) }}</dd></div>
                                <div class="empleado-detalle-item"><dt>No. interior</dt><dd>{{ $d($vis->numero_interior) }}</dd></div>
                                <div class="empleado-detalle-item"><dt>No. exterior</dt><dd>{{ $d($vis->numero_exterior) }}</dd></div>
                                <div class="empleado-detalle-item"><dt>C.P.</dt><dd>{{ $d($vis->codigo_postal) }}</dd></div>
                            </dl>
                        </section>
                    </div>

                    <div class="col-12 col-lg-6">
                        <section class="empleado-detalle-section">
                            <h3 class="empleado-detalle-section-title">
                                <i class="fa-solid fa-user"></i> Datos personales
                            </h3>
                            <dl class="empleado-detalle-grid">
                                <div class="empleado-detalle-item"><dt>Sexo</dt><dd>{{ $d($vis->sexo) }}</dd></div>
                                <div class="empleado-detalle-item"><dt>Tipo de sangre</dt><dd>{{ $d($vis->tipo_sangre) }}</dd></div>
                                <div class="empleado-detalle-item"><dt>Fecha de nacimiento</dt><dd>{{ $d($vis->fecha_nacimiento) }}</dd></div>
                                <div class="empleado-detalle-item"><dt>Estado civil</dt><dd>{{ $d($vis->estado_civil) }}</dd></div>
                            </dl>
                        </section>
                    </div>

                    <div class="col-12 col-lg-6">
                        <section class="empleado-detalle-section">
                            <h3 class="empleado-detalle-section-title">
                                <i class="fa-solid fa-money-bill-wave"></i> Nómina e Infonavit
                            </h3>
                            <dl class="empleado-detalle-grid">
                                <div class="empleado-detalle-item"><dt>Sueldo mensual</dt><dd>$ {{ number_format($vis->salario_bruto, 2) }}</dd></div>
                                <div class="empleado-detalle-item"><dt>Salario diario</dt><dd>{{ $d($vis->salario_diario) }}</dd></div>
                                <div class="empleado-detalle-item"><dt>Cuota fija</dt><dd>{{ $d($vis->factor_sua) }}</dd></div>
                                <div class="empleado-detalle-item"><dt>Infonavit</dt><dd>{{ $d($vis->nombreinfonavit) }}</dd></div>
                                <div class="empleado-detalle-item"><dt>No. Infonavit</dt><dd>{{ $d($vis->numero_credito_infonavit) }}</dd></div>
                            </dl>
                        </section>
                    </div>

                    <div class="col-12 col-lg-6">
                        <section class="empleado-detalle-section">
                            <h3 class="empleado-detalle-section-title">
                                <i class="fa-solid fa-building-columns"></i> Datos bancarios
                            </h3>
                            <dl class="empleado-detalle-grid">
                                <div class="empleado-detalle-item"><dt>Banco</dt><dd>{{ $d($vis->banco) }}</dd></div>
                                <div class="empleado-detalle-item"><dt>Número de tarjeta</dt><dd>{{ $d($vis->numero_tarjeta) }}</dd></div>
                                <div class="empleado-detalle-item"><dt>Número de cuenta</dt><dd>{{ $d($vis->numero_cuenta) }}</dd></div>
                            </dl>
                        </section>
                    </div>

                    <div class="col-12 col-lg-6">
                        <section class="empleado-detalle-section">
                            <h3 class="empleado-detalle-section-title">
                                <i class="fa-solid fa-kit-medical"></i> Emergencia
                            </h3>
                            <dl class="empleado-detalle-grid">
                                <div class="empleado-detalle-item"><dt>Contacto</dt><dd>{{ $d($vis->contacto_emergencia) }}</dd></div>
                                <div class="empleado-detalle-item"><dt>Teléfono</dt><dd>{{ $d($vis->telefono_emergencia) }}</dd></div>
                                <div class="empleado-detalle-item empleado-detalle-item--full"><dt>Descripción</dt><dd>{{ $d($vis->descripcion) }}</dd></div>
                            </dl>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
