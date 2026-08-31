@extends('layouts.app')
@section('content')
    @include('Gestion_alumnos.partials.sweet_alerts')

    <link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

    <div class="container-fluid acciones-config-page">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fa-solid fa-building"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Gestión de Empresas</h2>
                            <p class="text-muted mb-0">Alta, consulta y administración de empresas vinculadas al programa.</p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <button type="button" class="btn btn-baseColor-light fs-7 mb-2" data-bs-toggle="modal" data-bs-target="#modalCargaMasivaEmpresas">
                            <i class="fa-solid fa-file-excel"></i> Carga masiva
                        </button>
                        <button type="button" class="btn btn-baseColor fs-7 mb-2" data-bs-toggle="modal" data-bs-target="#modalEmpresa">
                            <i class="fa-solid fa-building-circle-plus"></i> Nueva empresa
                        </button>
                        <a class="btn btn-baseColor-light fs-7 mb-2" href="javascript:history.back()">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @php
            $apiEmpresasBase = url('/Gestion_alumnos/api/empresas');
            $apiVisitasProspeccionBase = url('/Gestion_alumnos/api/empresas');
        @endphp

        <div class="card border-0 shadow p-3 mt-2 mb-2 bg-body rounded-5">
            <div class="card-header text-start bg-body border-0">
                <h5 class="text-secondary mb-1">
                    <i class="fa-solid fa-list-check me-2"></i>Listado de empresas disponibles
                </h5>
                <p class="text-muted fs-8 mb-0">Consulta, edita, elimina o revisa visitas de prospección registradas.</p>
            </div>
            <div class="card-body">
                {{-- Buscador adicional comentado: DataTables ya agrega su búsqueda principal.
                <div class="row mb-3">
                    <div class="col-md-5 col-12">
                        <div class="form-outline">
                            <label class="form-label">Buscar empresa</label>
                            <input type="text" id="buscadorEmpresas" class="form-control text" placeholder="Nombre, estado, municipio, giro...">
                        </div>
                    </div>
                </div>
                --}}

                <div class="table-responsive">
                    <table class="table table-stripped table-hover display mb-0" id="tableEmpresas">
                        <thead>
                            <tr class="text-tr">
                                <th class="text-truncate">Nombre</th>
                                <th class="text-truncate">Dirección</th>
                                <th class="text-truncate">Teléfonos</th>
                                <th class="text-truncate">Correos</th>
                                <th class="text-truncate">Fecha fin</th>
                                <th class="text-truncate">Giro empresarial</th>
                                <th class="text-truncate">Estado</th>
                                <th class="text-truncate">Municipio</th>
                                <th class="text-truncate">Opciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyEmpresasListado">
                            <tr>
                                <td class="text-muted" colspan="9">Sin registros por el momento.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <div class="modal fade" id="modalEmpresa" tabindex="-1" aria-labelledby="modalEmpresaLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title text-secondary" id="modalEmpresaLabel">
                            <i class="fa-solid fa-building-circle-plus me-2"></i>Alta de empresa
                        </h5>
                        <p class="text-muted fs-8 mb-0">Captura los datos generales y fiscales de la empresa.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <form id="formAltaEmpresa" class="g-3 form needs-validation" novalidate>
                        <input type="hidden" id="empresa_edit_id" value="">
                        <div class="row">
                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text" name="nombre" maxlength="200" required>
                                    <div class="invalid-feedback">El nombre de la empresa es obligatorio.</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Teléfonos</label>
                                    <input type="text" class="form-control text" name="telefonos" maxlength="100" placeholder="Ej. 614-000-0000">
                                </div>
                            </div>
                            <div class="col-md-3 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Fecha fin</label>
                                    <input type="date" class="form-control text" name="fecha_fin">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Correos</label>
                                    <input type="text" class="form-control text" name="correos" maxlength="200" placeholder="correo@empresa.com">
                                </div>
                            </div>
                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Dirección</label>
                                    <textarea class="form-control text" name="direccion" rows="2"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Giro empresarial</label>
                                    <input type="text" class="form-control text" name="giro_empresarial" maxlength="200">
                                </div>
                            </div>
                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Municipio</label>
                                    <input type="text" class="form-control text" name="municipio" maxlength="100">
                                </div>
                            </div>
                        </div>

                        <hr class="mt-4 mb-2">
                        <h6 class="text-orange mb-3"><i class="fa-solid fa-file-invoice"></i> Datos Fiscales</h6>

                        <div class="row">
                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Razón Social</label>
                                    <input type="text" class="form-control text" name="razon_social" maxlength="300">
                                </div>
                            </div>
                            <div class="col-md-3 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">RFC</label>
                                    <input type="text" class="form-control text" name="rfc" maxlength="13" style="text-transform:uppercase;">
                                </div>
                            </div>
                            <div class="col-md-3 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Código Postal Fiscal</label>
                                    <input type="text" class="form-control text" name="codigo_postal" maxlength="10">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Régimen Fiscal</label>
                                    <select class="form-select" name="regimen_fiscal">
                                        <option value="">Seleccionar...</option>
                                        <option value="601">601 - General de Ley Personas Morales</option>
                                        <option value="603">603 - Personas Morales con Fines no Lucrativos</option>
                                        <option value="605">605 - Sueldos y Salarios</option>
                                        <option value="606">606 - Arrendamiento</option>
                                        <option value="608">608 - Demás ingresos</option>
                                        <option value="610">610 - Residentes en el Extranjero</option>
                                        <option value="612">612 - Personas Físicas con Act. Empresariales</option>
                                        <option value="616">616 - Sin obligaciones fiscales</option>
                                        <option value="621">621 - Incorporación Fiscal</option>
                                        <option value="625">625 - Actividades Empresariales (RESICO)</option>
                                        <option value="626">626 - Régimen Simplificado de Confianza</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Uso CFDI</label>
                                    <select class="form-select" name="uso_cfdi">
                                        <option value="">Seleccionar...</option>
                                        <option value="G01">G01 - Adquisición de mercancías</option>
                                        <option value="G02">G02 - Devoluciones, descuentos o bonificaciones</option>
                                        <option value="G03">G03 - Gastos en general</option>
                                        <option value="I01">I01 - Construcciones</option>
                                        <option value="I02">I02 - Mobiliario y equipo de oficina</option>
                                        <option value="I04">I04 - Equipo de cómputo y accesorios</option>
                                        <option value="P01">P01 - Por definir</option>
                                        <option value="S01">S01 - Sin efectos fiscales</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Correo fiscal</label>
                                    <input type="email" class="form-control text" name="correo_fiscal" maxlength="200" placeholder="correo@fiscal.com">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Calle fiscal</label>
                                    <input type="text" class="form-control text" name="calle" maxlength="200">
                                </div>
                            </div>
                            <div class="col-md-2 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">No. Exterior</label>
                                    <input type="text" class="form-control text" name="numero_exterior" maxlength="20">
                                </div>
                            </div>
                            <div class="col-md-2 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">No. Interior</label>
                                    <input type="text" class="form-control text" name="numero_interior" maxlength="20">
                                </div>
                            </div>
                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Colonia fiscal</label>
                                    <input type="text" class="form-control text" name="colonia" maxlength="150">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-moderno" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formAltaEmpresa" class="btn btn-baseColor fs-8 rounded-1" id="btnSubmitEmpresa">
                        <i class="fa-solid fa-check"></i> Guardar empresa
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalVisitasEmpresa" tabindex="-1" aria-labelledby="modalVisitasEmpresaLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title text-secondary" id="modalVisitasEmpresaLabel">
                            <i class="fa-solid fa-clipboard-list me-2"></i>Historial de visitas
                        </h5>
                        <p class="text-muted fs-8 mb-0"><span id="tituloEmpresaVisitas">—</span></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <input type="hidden" id="visita_empresa_id" value="">

                    <div class="card border-0 shadow-sm bg-light rounded-4 p-3 mb-3">
                        <h6 class="text-secondary mb-3">
                            <i class="fa-solid fa-calendar-plus me-2"></i>Registrar nueva visita
                        </h6>
                        <form id="formVisitaEmpresa" class="g-3 form needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-3 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Fecha visita <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control text" name="fecha_visita" required>
                                        <div class="invalid-feedback">Indica la fecha de visita.</div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Contacto empresa</label>
                                        <input type="text" class="form-control text" name="contacto_empresa" maxlength="150" placeholder="RH o dueño">
                                    </div>
                                </div>
                                <div class="col-md-3 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Puesto contacto</label>
                                        <input type="text" class="form-control text" name="puesto_contacto" maxlength="100">
                                    </div>
                                </div>
                                <div class="col-md-2 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Vacantes</label>
                                        <input type="number" class="form-control text" name="vacantes_disponibles" min="0" step="1" value="0">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">¿Se presentó SED?</label>
                                        <select class="form-select" name="presentacion_sed">
                                            <option value="1">Sí</option>
                                            <option value="0" selected>No</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Resultado para prácticas</label>
                                        <select class="form-select" name="acepta_adoptar_sed">
                                            <option value="Pendiente" selected>Pendiente</option>
                                            <option value="Aceptado">Aceptado</option>
                                            <option value="Rechazado">Rechazado</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-5 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Perfil buscado</label>
                                        <input type="text" class="form-control text" name="perfil_buscado" maxlength="255" placeholder="Perfil que solicita la empresa">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Observaciones</label>
                                        <textarea class="form-control text" name="observaciones" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row justify-content-end mt-3">
                                <div class="col-md-3 col-12 d-grid">
                                    <button type="submit" class="btn btn-baseColor">
                                        <i class="fa-solid fa-check"></i> Guardar visita
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <h6 class="text-secondary mb-2">
                        <i class="fa-solid fa-list me-2"></i>Visitas registradas
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-stripped table-hover mb-0">
                            <thead>
                                <tr class="text-tr">
                                    <th>Fecha</th>
                                    <th>Contacto</th>
                                    <th>Puesto</th>
                                    <th>SED</th>
                                    <th>Resultado</th>
                                    <th>Vacantes</th>
                                    <th>Perfil</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyVisitasEmpresa">
                                <tr>
                                    <td class="text-muted" colspan="8">Sin visitas registradas.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-moderno" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCargaMasivaEmpresas" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title text-secondary">
                            <i class="fa-solid fa-file-excel me-2"></i>Carga masiva de empresas
                        </h5>
                        <p class="text-muted fs-8 mb-0">Tomará la columna A del Excel (nombre de empresa).</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="formCargaMasivaEmpresas" class="g-3 form needs-validation" novalidate>
                    <div class="modal-body pt-0">
                        <div class="col-12 mt-2">
                            <label class="form-label">Archivo Excel <span class="text-danger">*</span></label>
                            <input type="file" class="form-control text" id="archivoMasivoEmpresas" name="archivo" accept=".xlsx,.xls,.csv" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary btn-moderno" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-baseColor fs-8 rounded-1">
                            <i class="fa-solid fa-upload"></i> Importar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/gestionAlumnosDataTables.js') }}?v=1"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            const apiEmpresas = @json($apiEmpresasBase);
            const apiVisitasBase = @json($apiVisitasProspeccionBase);
            const formAltaEmpresa = document.getElementById('formAltaEmpresa');
            const empresaEditId = document.getElementById('empresa_edit_id');
            const modalEmpresa = document.getElementById('modalEmpresa');
            const modalEmpresaLabel = document.getElementById('modalEmpresaLabel');
            const btnSubmitEmpresa = document.getElementById('btnSubmitEmpresa');
            const tbodyEmpresasListado = document.getElementById('tbodyEmpresasListado');
            const buscadorEmpresas = document.getElementById('buscadorEmpresas');
            const modalVisitasEmpresa = document.getElementById('modalVisitasEmpresa');
            const tituloEmpresaVisitas = document.getElementById('tituloEmpresaVisitas');
            const visitaEmpresaId = document.getElementById('visita_empresa_id');
            const formVisitaEmpresa = document.getElementById('formVisitaEmpresa');
            const tbodyVisitasEmpresa = document.getElementById('tbodyVisitasEmpresa');
            const formCargaMasivaEmpresas = document.getElementById('formCargaMasivaEmpresas');
            const archivoMasivoEmpresas = document.getElementById('archivoMasivoEmpresas');
            const modalCargaMasivaEmpresas = document.getElementById('modalCargaMasivaEmpresas');
            let cacheEmpresas = [];

            const getCsrfToken = function () {
                const m = document.querySelector('meta[name="csrf-token"]');
                return m ? m.getAttribute('content') : '';
            };

            /** Evita colisiones DOM (p. ej. form.elements['estado']) que no apuntan al input. */
            const fieldVal = function (name) {
                const el = formAltaEmpresa.querySelector('[name="' + name + '"]');
                return el ? String(el.value || '').trim() : '';
            };

            const setFieldVal = function (name, value) {
                const el = formAltaEmpresa.querySelector('[name="' + name + '"]');
                if (el) {
                    el.value = value != null ? String(value) : '';
                }
            };

            const REGIMENES_FISCAL = {
                '601': 'General de Ley Personas Morales',
                '603': 'Personas Morales con Fines no Lucrativos',
                '605': 'Sueldos y Salarios',
                '606': 'Arrendamiento',
                '608': 'Demás ingresos',
                '610': 'Residentes en el Extranjero',
                '612': 'Personas Físicas con Act. Empresariales',
                '616': 'Sin obligaciones fiscales',
                '621': 'Incorporación Fiscal',
                '625': 'Actividades Empresariales (RESICO)',
                '626': 'Régimen Simplificado de Confianza'
            };

            const formPayload = function () {
                var regimen = fieldVal('regimen_fiscal');
                return {
                    nombre: fieldVal('nombre'),
                    direccion: fieldVal('direccion'),
                    telefonos: fieldVal('telefonos'),
                    correos: fieldVal('correos'),
                    fecha_fin: fieldVal('fecha_fin'),
                    giro_empresarial: fieldVal('giro_empresarial'),
                    municipio: fieldVal('municipio'),
                    razon_social: fieldVal('razon_social') || null,
                    rfc: fieldVal('rfc') ? fieldVal('rfc').toUpperCase() : null,
                    regimen_fiscal: regimen || null,
                    regimen_fiscal_descripcion: REGIMENES_FISCAL[regimen] || null,
                    calle: fieldVal('calle') || null,
                    numero_exterior: fieldVal('numero_exterior') || null,
                    numero_interior: fieldVal('numero_interior') || null,
                    colonia: fieldVal('colonia') || null,
                    codigo_postal: fieldVal('codigo_postal') || null,
                    uso_cfdi: fieldVal('uso_cfdi') || null,
                    correo_fiscal: fieldVal('correo_fiscal') || null,
                };
            };

            const parseJsonResponse = async function (r) {
                const text = await r.text();
                if (!text) {
                    return {};
                }
                try {
                    return JSON.parse(text);
                } catch (e) {
                    return { _parseError: true, _raw: text };
                }
            };

            const limpiarFormularioEmpresa = function () {
                formAltaEmpresa.reset();
                formAltaEmpresa.classList.remove('was-validated');
                if (empresaEditId) {
                    empresaEditId.value = '';
                }
                if (modalEmpresaLabel) {
                    modalEmpresaLabel.innerHTML = '<i class="fa-solid fa-building-circle-plus me-2"></i>Alta de empresa';
                }
                if (btnSubmitEmpresa) {
                    btnSubmitEmpresa.innerHTML = '<i class="fa-solid fa-check"></i> Guardar empresa';
                }
            };

            const escapeHtml = function (txt) {
                if (txt === null || txt === undefined) {
                    return '';
                }
                const d = document.createElement('div');
                d.textContent = String(txt);
                return d.innerHTML;
            };

            const endpointVisitas = function (empresaId) {
                return apiVisitasBase + '/' + encodeURIComponent(String(empresaId || '').trim()) + '/visitas-prospeccion';
            };


            const limpiarFormularioVisita = function () {
                if (!formVisitaEmpresa) {
                    return;
                }
                formVisitaEmpresa.reset();
                formVisitaEmpresa.classList.remove('was-validated');
                const fechaEl = formVisitaEmpresa.querySelector('[name="fecha_visita"]');
                if (fechaEl) {
                    const hoy = new Date();
                    const mm = String(hoy.getMonth() + 1).padStart(2, '0');
                    const dd = String(hoy.getDate()).padStart(2, '0');
                    fechaEl.value = hoy.getFullYear() + '-' + mm + '-' + dd;
                }
                const vacEl = formVisitaEmpresa.querySelector('[name="vacantes_disponibles"]');
                if (vacEl) {
                    vacEl.value = '0';
                }
            };

            const renderTablaVisitas = function (lista) {
                if (!tbodyVisitasEmpresa) {
                    return;
                }
                tbodyVisitasEmpresa.innerHTML = '';
                if (!lista || !lista.length) {
                    const tr = document.createElement('tr');
                    tr.innerHTML = '<td class="text-muted" colspan="8">Sin visitas registradas.</td>';
                    tbodyVisitasEmpresa.appendChild(tr);
                    return;
                }

                lista.forEach(function (v) {
                    const fecha = v.fecha_visita ? String(v.fecha_visita).slice(0, 10) : '—';
                    const sedTxt = v.presentacion_sed ? 'Sí' : 'No';
                    const res = v.acepta_adoptar_sed || 'Pendiente';
                    const vac = (v.vacantes_disponibles !== null && v.vacantes_disponibles !== undefined) ? String(v.vacantes_disponibles) : '0';
                    const tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td>' + escapeHtml(fecha) + '</td>' +
                        '<td>' + escapeHtml(v.contacto_empresa || '—') + '</td>' +
                        '<td>' + escapeHtml(v.puesto_contacto || '—') + '</td>' +
                        '<td>' + escapeHtml(sedTxt) + '</td>' +
                        '<td>' + escapeHtml(res) + '</td>' +
                        '<td>' + escapeHtml(vac) + '</td>' +
                        '<td class="text-truncate" style="max-width:14rem;" title="' + escapeHtml(v.perfil_buscado || '') + '">' + escapeHtml(v.perfil_buscado || '—') + '</td>' +
                        '<td class="text-truncate" style="max-width:16rem;" title="' + escapeHtml(v.observaciones || '') + '">' + escapeHtml(v.observaciones || '—') + '</td>';
                    tbodyVisitasEmpresa.appendChild(tr);
                });
            };

            const cargarVisitasEmpresa = async function (empresaId) {
                if (!empresaId) {
                    renderTablaVisitas([]);
                    return;
                }
                try {
                    const r = await fetch(endpointVisitas(empresaId), { headers: { 'Accept': 'application/json' } });
                    const j = await parseJsonResponse(r);
                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudo cargar historial de visitas.');
                        renderTablaVisitas([]);
                        return;
                    }
                    renderTablaVisitas(j.data || []);
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al cargar visitas.');
                    renderTablaVisitas([]);
                }
            };

            const renderTablaEmpresas = function (lista) {
                if (!tbodyEmpresasListado) {
                    return;
                }

                if (window.GestionAlumnosDataTables) {
                    window.GestionAlumnosDataTables.destroy('#tableEmpresas');
                }

                tbodyEmpresasListado.innerHTML = '';

                if (!lista.length) {
                    const tr = document.createElement('tr');
                    tr.innerHTML = '<td class="text-muted" colspan="9">Sin registros por el momento.</td>';
                    tbodyEmpresasListado.appendChild(tr);
                    return;
                }

                lista.forEach(function (e) {
                    const tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td>' + (e.nombre || '—') + '</td>' +
                        '<td class="text-truncate" style="max-width:16rem;">' + (e.direccion || '—') + '</td>' +
                        '<td>' + (e.telefonos || '—') + '</td>' +
                        '<td>' + (e.correos || '—') + '</td>' +
                        '<td>' + (e.fecha_fin ? String(e.fecha_fin).slice(0, 10) : '—') + '</td>' +
                        '<td>' + (e.giro_empresarial || '—') + '</td>' +
                        '<td>' + (e.estado || '—') + '</td>' +
                        '<td>' + (e.municipio || '—') + '</td>' +
                        '<td class="text-end text-nowrap">' +
                        '  <button type="button" class="btn btn-baseColor-light border-0 m-0 btn-visitas-empresa" data-bs-toggle="modal" data-bs-target="#modalVisitasEmpresa" data-id="' + e.id + '" data-nombre="' + escapeHtml(e.nombre || '') + '" title="Historial de visitas"><i class="fa-solid fa-clipboard-list fs-8"></i></button>' +
                        '  <button type="button" class="btn btn-primary border-0 m-0 btn-editar-empresa" data-id="' + e.id + '"><i class="fa-solid fa-pen fs-8"></i></button>' +
                        '  <button type="button" class="btn btn-danger m-0 btn-eliminar-empresa" data-id="' + e.id + '"><i class="fa-solid fa-trash fs-8"></i></button>' +
                        '</td>';
                    tbodyEmpresasListado.appendChild(tr);
                });

                if (window.GestionAlumnosDataTables) {
                    window.GestionAlumnosDataTables.init('#tableEmpresas');
                }
            };

            const cargarTablaEmpresas = async function () {
                try {
                    const q = buscadorEmpresas ? (buscadorEmpresas.value || '').trim() : '';
                    const url = q ? (apiEmpresas + '?q=' + encodeURIComponent(q)) : apiEmpresas;
                    const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const j = await parseJsonResponse(r);
                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudo cargar empresas.');
                        renderTablaEmpresas([]);
                        cacheEmpresas = [];
                        return;
                    }
                    const data = Array.isArray(j.data) ? j.data : [];
                    cacheEmpresas = data;
                    renderTablaEmpresas(data);
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al cargar empresas.');
                    renderTablaEmpresas([]);
                    cacheEmpresas = [];
                }
            };

            const abrirEdicionEmpresa = async function (id) {
                try {
                    const r = await fetch(apiEmpresas + '/' + encodeURIComponent(id), { headers: { 'Accept': 'application/json' } });
                    const j = await parseJsonResponse(r);
                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudo cargar la empresa.');
                        return;
                    }
                    const e = j.data;
                    if (empresaEditId) {
                        empresaEditId.value = String(e.id);
                    }
                    setFieldVal('nombre', e.nombre || '');
                    setFieldVal('direccion', e.direccion || '');
                    setFieldVal('telefonos', e.telefonos || '');
                    setFieldVal('correos', e.correos || '');
                    setFieldVal('fecha_fin', e.fecha_fin ? String(e.fecha_fin).slice(0, 10) : '');
                    setFieldVal('giro_empresarial', e.giro_empresarial || '');
                    setFieldVal('municipio', e.municipio || '');
                    setFieldVal('razon_social', e.razon_social || '');
                    setFieldVal('rfc', e.rfc || '');
                    setFieldVal('regimen_fiscal', e.regimen_fiscal || '');
                    setFieldVal('calle', e.calle || '');
                    setFieldVal('numero_exterior', e.numero_exterior || '');
                    setFieldVal('numero_interior', e.numero_interior || '');
                    setFieldVal('colonia', e.colonia || '');
                    setFieldVal('codigo_postal', e.codigo_postal || '');
                    setFieldVal('uso_cfdi', e.uso_cfdi || '');
                    setFieldVal('correo_fiscal', e.correo_fiscal || '');
                    formAltaEmpresa.classList.remove('was-validated');
                    if (modalEmpresaLabel) {
                        modalEmpresaLabel.innerHTML = '<i class="fa-solid fa-pen me-2"></i>Editar empresa';
                    }
                    if (btnSubmitEmpresa) {
                        btnSubmitEmpresa.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Actualizar empresa';
                    }
                    if (window.bootstrap && modalEmpresa) {
                        const instanciaModal = window.bootstrap.Modal.getOrCreateInstance(modalEmpresa);
                        instanciaModal.show();
                    }
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al cargar la empresa.');
                }
            };

            const eliminarEmpresa = async function (id) {
                if (!window.confirm('¿Eliminar empresa?')) {
                    return;
                }
                try {
                    const r = await fetch(apiEmpresas + '/' + encodeURIComponent(id), {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken(),
                        },
                    });
                    const j = await parseJsonResponse(r);
                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudo eliminar.');
                        return;
                    }
                    await cargarTablaEmpresas();
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al eliminar.');
                }
            };

            if (modalVisitasEmpresa && visitaEmpresaId) {
                modalVisitasEmpresa.addEventListener('show.bs.modal', function (event) {
                    const btn = event.relatedTarget;
                    if (!btn) {
                        return;
                    }
                    const id = btn.getAttribute('data-id') || '';
                    const nombre = btn.getAttribute('data-nombre') || 'Empresa';
                    visitaEmpresaId.value = id;
                    if (tituloEmpresaVisitas) {
                        tituloEmpresaVisitas.textContent = nombre;
                    }
                    limpiarFormularioVisita();
                    void cargarVisitasEmpresa(id);
                });
            }

            if (formVisitaEmpresa) {
                formVisitaEmpresa.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    if (!formVisitaEmpresa.checkValidity()) {
                        formVisitaEmpresa.classList.add('was-validated');
                        return;
                    }

                    const empresaId = visitaEmpresaId ? String(visitaEmpresaId.value || '').trim() : '';
                    if (!empresaId) {
                        window.alert('No hay empresa seleccionada.');
                        return;
                    }

                    const payload = {
                        fecha_visita: (formVisitaEmpresa.querySelector('[name="fecha_visita"]').value || '').trim(),
                        contacto_empresa: (formVisitaEmpresa.querySelector('[name="contacto_empresa"]').value || '').trim(),
                        puesto_contacto: (formVisitaEmpresa.querySelector('[name="puesto_contacto"]').value || '').trim(),
                        presentacion_sed: (formVisitaEmpresa.querySelector('[name="presentacion_sed"]').value || '0') === '1',
                        acepta_adoptar_sed: (formVisitaEmpresa.querySelector('[name="acepta_adoptar_sed"]').value || 'Pendiente').trim(),
                        vacantes_disponibles: parseInt((formVisitaEmpresa.querySelector('[name="vacantes_disponibles"]').value || '0').trim(), 10) || 0,
                        perfil_buscado: (formVisitaEmpresa.querySelector('[name="perfil_buscado"]').value || '').trim(),
                        observaciones: (formVisitaEmpresa.querySelector('[name="observaciones"]').value || '').trim(),
                    };

                    try {
                        const r = await fetch(endpointVisitas(empresaId), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': getCsrfToken(),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify(payload),
                        });
                        const j = await parseJsonResponse(r);
                        if (!r.ok) {
                            let msg = (j && j.message) ? j.message : 'No se pudo guardar la visita.';
                            if (j && j.errors) {
                                msg = Object.values(j.errors).flat().join('\n');
                            }
                            window.alert(msg);
                            return;
                        }
                        window.alert(j.message || 'Visita guardada.');
                        limpiarFormularioVisita();
                        await cargarVisitasEmpresa(empresaId);
                    } catch (err) {
                        console.error(err);
                        window.alert('Error de red al guardar la visita.');
                    }
                });
            }

            if (formAltaEmpresa) {
                formAltaEmpresa.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    if (!formAltaEmpresa.checkValidity()) {
                        formAltaEmpresa.classList.add('was-validated');
                        return;
                    }

                    const editId = empresaEditId && empresaEditId.value ? String(empresaEditId.value).trim() : '';
                    const payload = formPayload();

                    try {
                        const url = editId ? (apiEmpresas + '/' + encodeURIComponent(editId)) : apiEmpresas;
                        const r = await fetch(url, {
                            method: editId ? 'PUT' : 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': getCsrfToken(),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify(payload),
                        });
                        const j = await parseJsonResponse(r);
                        if (j._parseError) {
                            window.alert('El servidor devolvió un error al guardar. Revisa la consola o los logs.');
                            console.error(j._raw);
                            return;
                        }
                        if (!r.ok) {
                            let msg = (j && j.message) ? j.message : 'No se pudo guardar.';
                            if (j && j.errors) {
                                msg = Object.values(j.errors).flat().join('\n');
                            }
                            window.alert(msg);
                            return;
                        }
                        if (editId) {
                            window.alert((j && j.message) ? j.message : 'Empresa actualizada correctamente.');
                        } else {
                            window.alert('Empresa registrada satisfactoriamente.');
                        }
                        limpiarFormularioEmpresa();
                        await cargarTablaEmpresas();
                        if (window.bootstrap && modalEmpresa) {
                            const instanciaModal = window.bootstrap.Modal.getOrCreateInstance(modalEmpresa);
                            instanciaModal.hide();
                        }
                    } catch (err) {
                        console.error(err);
                        window.alert('Error de red al guardar.');
                    }
                });
            }

            if (tbodyEmpresasListado) {
                tbodyEmpresasListado.addEventListener('click', function (event) {
                    const btnEdit = event.target.closest('.btn-editar-empresa');
                    if (btnEdit) {
                        const id = btnEdit.getAttribute('data-id');
                        if (id) {
                            void abrirEdicionEmpresa(id);
                        }
                        return;
                    }
                    const btnDel = event.target.closest('.btn-eliminar-empresa');
                    if (btnDel) {
                        const id = btnDel.getAttribute('data-id');
                        if (id) {
                            void eliminarEmpresa(id);
                        }
                    }
                });
            }

            if (buscadorEmpresas) {
                buscadorEmpresas.addEventListener('input', function () {
                    void cargarTablaEmpresas();
                });
            }

            if (modalEmpresa) {
                modalEmpresa.addEventListener('show.bs.modal', function (event) {
                    const trigger = event.relatedTarget;
                    const esAltaDesdeBoton = trigger && trigger.matches('[data-bs-target="#modalEmpresa"]');
                    if (esAltaDesdeBoton && (!empresaEditId || !empresaEditId.value)) {
                        limpiarFormularioEmpresa();
                    }
                });
                modalEmpresa.addEventListener('hidden.bs.modal', function () {
                    limpiarFormularioEmpresa();
                });
            }

            if (formCargaMasivaEmpresas) {
                formCargaMasivaEmpresas.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    if (!formCargaMasivaEmpresas.checkValidity()) {
                        formCargaMasivaEmpresas.classList.add('was-validated');
                        return;
                    }
                    if (!archivoMasivoEmpresas || !archivoMasivoEmpresas.files || !archivoMasivoEmpresas.files[0]) {
                        window.alert('Selecciona un archivo.');
                        return;
                    }
                    try {
                        const fd = new FormData();
                        fd.append('archivo', archivoMasivoEmpresas.files[0]);
                        const r = await fetch(apiEmpresas + '/importar-masivo', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': getCsrfToken(),
                            },
                            body: fd,
                        });
                        const j = await parseJsonResponse(r);
                        if (!r.ok) {
                            let msg = (j && j.message) ? j.message : 'No se pudo importar.';
                            if (j && j.errors) {
                                msg = Object.values(j.errors).flat().join('\n');
                            }
                            window.alert(msg);
                            return;
                        }
                        const d = j.data || {};
                        window.alert(
                            (j.message || 'Importación finalizada.') + '\n' +
                            'Creadas: ' + String(d.creadas || 0) + '\n' +
                            'Ya existentes: ' + String(d.existentes || 0)
                        );
                        formCargaMasivaEmpresas.reset();
                        formCargaMasivaEmpresas.classList.remove('was-validated');
                        if (window.bootstrap && modalCargaMasivaEmpresas) {
                            bootstrap.Modal.getOrCreateInstance(modalCargaMasivaEmpresas).hide();
                        }
                        await cargarTablaEmpresas();
                    } catch (err) {
                        console.error(err);
                        window.alert('Error de red al importar empresas.');
                    }
                });
            }

            await cargarTablaEmpresas();
        });
    </script>
@endsection
