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
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Gestión de Socios</h2>
                            <p class="text-muted mb-0">Alta, consulta y administración de socios registrados.</p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <a href="{{ route('gestion_alumnos.socios.eventos') }}" class="btn btn-baseColor-light fs-7 mb-2">
                            <i class="fa-solid fa-calendar-check"></i> Eventos
                        </a>

                        <button type="button" class="btn btn-baseColor-light fs-7 mb-2" data-bs-toggle="modal" data-bs-target="#modalCargaMasivaSocios">
                            <i class="fa-solid fa-file-excel"></i> Carga masiva
                        </button>
                        <button type="button" class="btn btn-baseColor fs-7 mb-2" data-bs-toggle="modal" data-bs-target="#modalSocio" id="btnNuevoTitular">
                            <i class="fa-solid fa-users"></i> Nuevo titular
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @php
            $apiSociosBase = url('/Gestion_alumnos/api/socios');
            $apiTiposSocioBase = url('/Gestion_alumnos/api/tipos-socio');
            $apiGruposSocioBase = url('/Gestion_alumnos/api/grupos-socio');
            $apiDescuentosBase = url('/Gestion_alumnos/api/descuentos');
            $apiSociosPagosBase = url('/Gestion_alumnos/api/socios');
        @endphp

        <div class="card border-0 shadow p-3 mt-2 mb-2 bg-body rounded-5">
            <div class="card-header text-start bg-body border-0">
                <h5 class="text-secondary mb-1">
                    <i class="fa-solid fa-list-check me-2"></i>Listado de socios
                </h5>
                <p class="text-muted fs-8 mb-0">Consulta socios, familiares y planes de pago registrados.</p>
            </div>
            <div class="card-body">
                {{-- Buscador adicional comentado: DataTables ya agrega su búsqueda principal.
                <div class="row mb-3">
                    <div class="col-md-5 col-12">
                        <div class="form-outline">
                            <label class="form-label">Buscar socio</label>
                            <input type="text" id="buscadorSocios" class="form-control text" placeholder="No. socio, nombre, empresa...">
                        </div>
                    </div>
                </div>
                --}}

                <div class="table-responsive">
                    <table class="table table-stripped table-hover display mb-0" id="tableSocios">
                        <thead>
                            <tr class="text-tr">
                                <th class="text-truncate">No. socio</th>
                                <th class="text-truncate">Foto</th>
                                <th class="text-truncate">Nombre completo</th>
                                <th class="text-truncate">Tipo</th>
                                <th class="text-truncate">Grupo</th>
                                <th class="text-truncate">Empresa</th>
                                <th class="text-truncate">Teléfono</th>
                                <th class="text-truncate">Correo</th>
                                <th class="text-truncate">Cuota</th>
                                <th class="text-truncate">Facturación</th>
                                <th class="text-truncate">Domicilio</th>
                                <th class="text-truncate">Edad</th>
                                <th class="text-truncate">Descuento</th>
                                <th class="text-truncate">%</th>
                                <th class="text-truncate">Status</th>
                                <th class="text-truncate">Titular</th>
                                <th class="text-truncate">Opciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodySociosListado">
                            <tr>
                                <td class="text-muted" colspan="17">Sin registros por el momento.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCargaMasivaSocios" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title text-secondary">
                            <i class="fa-solid fa-file-excel me-2"></i>Carga masiva de socios
                        </h5>
                        <p class="text-muted fs-8 mb-0">
                            Compatible con el directorio COPARMEX (hoja <strong>SOCIOS ACTIVOS</strong>).
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="formCargaMasivaSocios" class="g-3 form needs-validation" novalidate>
                    <div class="modal-body pt-0">
                        <div class="alert alert-light border fs-8 mb-3">
                            <strong>Columnas reconocidas:</strong>
                            # Socio, Nombre del socio (empresa), Representante/contacto, Teléfono, Correo, Cuota, Fecha facturación, Domicilio.
                            <br>Los socios se registran como <em>titular</em> con estatus <em>activo</em>. Si el número de socio ya existe, se actualiza (opcional).
                        </div>
                        <div class="col-12 mt-2">
                            <label class="form-label">Archivo Excel <span class="text-danger">*</span></label>
                            <input type="file" class="form-control text" id="archivoMasivoSocios" name="archivo" accept=".xlsx,.xls,.csv" required>
                        </div>
                        <div class="col-12 mt-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="actualizarExistentesSocios" name="actualizar_existentes" value="1" checked>
                                <label class="form-check-label" for="actualizarExistentesSocios">
                                    Actualizar socios titulares existentes (mismo # socio)
                                </label>
                            </div>
                        </div>
                        <pre id="resultadoCargaMasivaSocios" class="bg-light border rounded p-3 mt-3 mb-0 fs-8 d-none" style="max-height:220px;overflow:auto;"></pre>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary btn-moderno" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-baseColor fs-8 rounded-1" id="btnImportarSociosMasivo">
                            <i class="fa-solid fa-upload"></i> Importar directorio
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalSocio" tabindex="-1" aria-labelledby="modalSocioLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title text-secondary" id="modalSocioLabel">
                            <i class="fa-solid fa-user-plus me-2"></i>Alta de socio
                        </h5>
                        <p class="text-muted fs-8 mb-0">Captura la información del socio.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <form id="formSocio" class="g-3 form needs-validation" novalidate>
                        <input type="hidden" id="socio_edit_id" value="">
                        <input type="hidden" id="id_titular_hidden" name="id_titular" value="">
                        <div class="row">
                            <div class="col-md-3 col-12 mt-2">
                                <label class="form-label">Número socio <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text" name="numero_socio" maxlength="60" required>
                                <div class="invalid-feedback">Número de socio obligatorio.</div>
                            </div>
                            <div class="col-md-2 col-12 mt-2">
                                <label class="form-label">No. dependiente</label>
                                <input type="number" class="form-control text" name="numero_dependiente" min="1" max="9999" placeholder="Opcional">
                            </div>
                            <div class="col-md-3 col-12 mt-2">
                                <label class="form-label">Tipo socio <span class="text-danger">*</span></label>
                                <select class="form-select" name="id_tipo_socio" id="selectTipoSocio" required>
                                    <option value="">Selecciona...</option>
                                </select>
                                <div class="invalid-feedback">Tipo de socio obligatorio.</div>
                            </div>
                            <div class="col-md-2 col-12 mt-2">
                                <label class="form-label">Grupo</label>
                                <select class="form-select" name="id_grupo" id="selectGrupoSocio">
                                    <option value="">Selecciona...</option>
                                </select>
                            </div>
                            <div class="col-md-2 col-12 mt-2">
                                <label class="form-label">Descuento</label>
                                <select class="form-select" name="id_descuento" id="selectDescuentoSocio">
                                    <option value="">Selecciona...</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 col-12 mt-2">
                                <label class="form-label">Foto (cámara o archivo) <span class="text-danger">*</span></label>
                                <input type="file" class="form-control text" name="foto_socio" id="fotoSocioInput" accept="image/*" capture="environment">
                                <div class="form-text">Se guarda en carpeta por número de socio. Si hay dependiente: socio_dependiente.</div>
                                <div class="d-flex gap-2 mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnIniciarCamaraSocio">
                                        <i class="fa-solid fa-camera"></i> Usar cámara
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-success" id="btnCapturarCamaraSocio" style="display:none;">
                                        <i class="fa-solid fa-circle-check"></i> Capturar
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCerrarCamaraSocio" style="display:none;">
                                        Cerrar cámara
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4 col-12 mt-2">
                                <video id="videoCamaraSocio" autoplay playsinline muted style="width:100%;max-width:260px;border-radius:10px;border:1px solid #ddd;display:none;"></video>
                                <canvas id="canvasCamaraSocio" style="display:none;"></canvas>
                            </div>
                            <div class="col-md-2 col-12 mt-2">
                                <img id="fotoSocioPreview" src="" alt="Preview foto socio" style="width:80px;height:80px;object-fit:cover;border-radius:10px;border:1px solid #ddd;display:none;">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 col-12 mt-2">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text" name="nombre" maxlength="120" required>
                            </div>
                            <div class="col-md-3 col-12 mt-2">
                                <label class="form-label">Segundo nombre</label>
                                <input type="text" class="form-control text" name="segund_nom" maxlength="120">
                            </div>
                            <div class="col-md-3 col-12 mt-2">
                                <label class="form-label">Apellido paterno</label>
                                <input type="text" class="form-control text" name="ap_paterno" maxlength="120">
                            </div>
                            <div class="col-md-3 col-12 mt-2">
                                <label class="form-label">Apellido materno</label>
                                <input type="text" class="form-control text" name="ap_materno" maxlength="120">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 col-12 mt-2">
                                <label class="form-label">Teléfono</label>
                                <input type="text" class="form-control text" name="telefono" maxlength="40">
                            </div>
                            <div class="col-md-4 col-12 mt-2">
                                <label class="form-label">Correo</label>
                                <input type="email" class="form-control text" name="correo" maxlength="250" autocomplete="email">
                            </div>
                            <div class="col-md-2 col-12 mt-2">
                                <label class="form-label">Edad</label>
                                <input type="number" class="form-control text" name="edad" min="0" max="120">
                            </div>
                            <div class="col-md-5 col-12 mt-2">
                                <label class="form-label">Empresa</label>
                                <input type="text" class="form-control text" name="empresa" maxlength="200">
                            </div>
                            <div class="col-md-3 col-12 mt-2">
                                <label class="form-label">Cuota / periodicidad</label>
                                <input type="text" class="form-control text" name="cuota_periodicidad" maxlength="80" placeholder="Ej. Anual, Semestral">
                            </div>
                            <div class="col-md-4 col-12 mt-2">
                                <label class="form-label">Fecha facturación</label>
                                <input type="text" class="form-control text" name="fecha_facturacion" maxlength="120" placeholder="Ej. Enero, Ene-Jun">
                            </div>
                            <div class="col-md-12 col-12 mt-2">
                                <label class="form-label">Domicilio</label>
                                <textarea class="form-control text" name="domicilio" rows="2" maxlength="2000"></textarea>
                            </div>
                            <div class="col-md-2 col-12 mt-2">
                                <label class="form-label">% descuento</label>
                                <input type="number" class="form-control text" name="id_porcentaje_des" min="0" max="100">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-moderno" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formSocio" class="btn btn-baseColor fs-8 rounded-1" id="btnSubmitSocio">
                        <i class="fa-solid fa-check"></i> Guardar socio
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalPagosSocio" tabindex="-1" aria-labelledby="modalPagosSocioLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title text-secondary" id="modalPagosSocioLabel">
                            <i class="fa-solid fa-money-bill-wave me-2"></i>Plan de pagos de socio
                        </h5>
                        <p class="text-muted fs-8 mb-0"><span id="nombreSocioPagos">-</span></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <form id="formPlanPagosSocio" class="g-3 form needs-validation mb-3" novalidate>
                        <input type="hidden" id="pagos_socio_id" value="">
                        <div class="row">
                            <div class="col-md-3 col-12 mt-2">
                                <label class="form-label">Periodicidad <span class="text-danger">*</span></label>
                                <select class="form-select" id="periodicidadPagoSocio" name="periodicidad" required>
                                    <option value="quincenal">Quincenal</option>
                                    <option value="mensual" selected>Mensual</option>
                                    <option value="bimestral">Bimestral</option>
                                    <option value="trimestral">Trimestral</option>
                                    <option value="semestral">Semestral</option>
                                    <option value="anual">Anual</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-12 mt-2">
                                <label class="form-label">Fecha inicio <span class="text-danger">*</span></label>
                                <input type="date" class="form-control text" id="fechaInicioPagoSocio" name="fecha_inicio" required>
                            </div>
                            <div class="col-md-2 col-12 mt-2">
                                <label class="form-label">Plazos</label>
                                <input type="number" class="form-control text" id="plazosPagoSocio" name="plazos" min="1" max="60" value="12">
                            </div>
                            <div class="col-md-4 col-12 mt-2">
                                <label class="form-label">Monto base / descuento / final</label>
                                <div class="form-control bg-light d-flex justify-content-between align-items-center">
                                    <span id="montoBaseSocio">$0.00</span>
                                    <span id="montoDescSocio">- 0%</span>
                                    <strong id="montoFinalSocio">$0.00</strong>
                                </div>
                            </div>
                        </div>
                    </form>

                    <h6 class="mb-2">Próximos pagos</h6>
                    <div class="row mb-3">
                        <div class="col-md-4 col-12 mt-2">
                            <div class="bg-light rounded-2 p-2">
                                <small class="text-muted d-block">Total pagado</small>
                                <strong id="cardTotalPagadoSocio">$0.00</strong>
                            </div>
                        </div>
                        <div class="col-md-4 col-12 mt-2">
                            <div class="bg-light rounded-2 p-2">
                                <small class="text-muted d-block">Total cancelado</small>
                                <strong id="cardTotalCanceladoSocio">$0.00</strong>
                            </div>
                        </div>
                        <div class="col-md-4 col-12 mt-2">
                            <div class="bg-light rounded-2 p-2">
                                <small class="text-muted d-block">Saldo neto plan</small>
                                <strong id="cardSaldoNetoSocio">$0.00</strong>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-stripped table-hover mb-0">
                            <thead>
                                <tr class="text-tr">
                                    <th>Plan</th>
                                    <th>Plazo</th>
                                    <th>Fecha programada</th>
                                    <th>Monto</th>
                                    <th>Status</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyPagosSocioDetalle">
                                <tr><td class="text-muted" colspan="6">Sin plan generado.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-moderno" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" form="formPlanPagosSocio" class="btn btn-baseColor" id="btnGenerarPlanSocio">
                        <i class="fa-solid fa-calendar-check"></i> Generar plan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/gestionAlumnosDataTables.js') }}?v=1"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            const apiSocios = @json($apiSociosBase);
            const apiTiposSocio = @json($apiTiposSocioBase);
            const apiGruposSocio = @json($apiGruposSocioBase);
            const apiDescuentos = @json($apiDescuentosBase);
            const apiSociosPagos = @json($apiSociosPagosBase);

            const formCargaMasivaSocios = document.getElementById('formCargaMasivaSocios');
            const archivoMasivoSocios = document.getElementById('archivoMasivoSocios');
            const actualizarExistentesSocios = document.getElementById('actualizarExistentesSocios');
            const resultadoCargaMasivaSocios = document.getElementById('resultadoCargaMasivaSocios');
            const modalCargaMasivaSocios = document.getElementById('modalCargaMasivaSocios');
            const btnImportarSociosMasivo = document.getElementById('btnImportarSociosMasivo');

            const modalSocio = document.getElementById('modalSocio');
            const modalSocioLabel = document.getElementById('modalSocioLabel');
            const btnNuevoTitular = document.getElementById('btnNuevoTitular');
            const formSocio = document.getElementById('formSocio');
            const socioEditId = document.getElementById('socio_edit_id');
            const idTitularHidden = document.getElementById('id_titular_hidden');
            const buscadorSocios = document.getElementById('buscadorSocios');
            const tbodySociosListado = document.getElementById('tbodySociosListado');
            const btnSubmitSocio = document.getElementById('btnSubmitSocio');
            const selectTipoSocio = document.getElementById('selectTipoSocio');
            const selectGrupoSocio = document.getElementById('selectGrupoSocio');
            const selectDescuentoSocio = document.getElementById('selectDescuentoSocio');
            const fotoSocioInput = document.getElementById('fotoSocioInput');
            const fotoSocioPreview = document.getElementById('fotoSocioPreview');
            const btnIniciarCamaraSocio = document.getElementById('btnIniciarCamaraSocio');
            const btnCapturarCamaraSocio = document.getElementById('btnCapturarCamaraSocio');
            const btnCerrarCamaraSocio = document.getElementById('btnCerrarCamaraSocio');
            const videoCamaraSocio = document.getElementById('videoCamaraSocio');
            const canvasCamaraSocio = document.getElementById('canvasCamaraSocio');
            const modalPagosSocio = document.getElementById('modalPagosSocio');
            const nombreSocioPagos = document.getElementById('nombreSocioPagos');
            const pagosSocioId = document.getElementById('pagos_socio_id');
            const formPlanPagosSocio = document.getElementById('formPlanPagosSocio');
            const periodicidadPagoSocio = document.getElementById('periodicidadPagoSocio');
            const fechaInicioPagoSocio = document.getElementById('fechaInicioPagoSocio');
            const plazosPagoSocio = document.getElementById('plazosPagoSocio');
            const montoBaseSocio = document.getElementById('montoBaseSocio');
            const montoDescSocio = document.getElementById('montoDescSocio');
            const montoFinalSocio = document.getElementById('montoFinalSocio');
            const tbodyPagosSocioDetalle = document.getElementById('tbodyPagosSocioDetalle');
            const btnGenerarPlanSocio = document.getElementById('btnGenerarPlanSocio');
            const cardTotalPagadoSocio = document.getElementById('cardTotalPagadoSocio');
            const cardTotalCanceladoSocio = document.getElementById('cardTotalCanceladoSocio');
            const cardSaldoNetoSocio = document.getElementById('cardSaldoNetoSocio');
            let catalogoTiposSocio = [];

            const parseJsonResponse = async function (r) {
                const text = await r.text();
                if (!text) return {};
                try { return JSON.parse(text); } catch (e) { return { _parseError: true, _raw: text }; }
            };
            const getCsrfToken = function () {
                const m = document.querySelector('meta[name="csrf-token"]');
                return m ? m.getAttribute('content') : '';
            };
            const fieldVal = function (name) {
                const el = formSocio.querySelector('[name="' + name + '"]');
                return el ? String(el.value || '').trim() : '';
            };
            const setFieldVal = function (name, value) {
                const el = formSocio.querySelector('[name="' + name + '"]');
                if (el) el.value = value != null ? String(value) : '';
            };
            const esc = function (txt) {
                if (txt == null) return '';
                return String(txt).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            };
            const money = function (value) {
                const n = Number(value || 0);
                return '$' + n.toFixed(2);
            };

            const nombreCompleto = function (s) {
                return [s.nombre || '', s.segund_nom || '', s.ap_paterno || '', s.ap_materno || '']
                    .join(' ')
                    .replace(/\s+/g, ' ')
                    .trim();
            };
            const esTitular = function (s) {
                return !s.id_titular;
            };
            const getTipoTitularId = function () {
                const t = (catalogoTiposSocio || []).find(function (x) {
                    return String(x.tipo_socio || '').trim().toLowerCase() === 'titular';
                });
                return t ? String(t.id) : '';
            };
            const setModoTitular = function () {
                if (idTitularHidden) idTitularHidden.value = '';
                setFieldVal('numero_dependiente', '');
                const tipoId = getTipoTitularId();
                if (tipoId) setFieldVal('id_tipo_socio', tipoId);
                if (selectTipoSocio) selectTipoSocio.disabled = true;
                const numSocio = formSocio.querySelector('[name="numero_socio"]');
                const numDep = formSocio.querySelector('[name="numero_dependiente"]');
                if (numSocio) numSocio.readOnly = false;
                if (numDep) numDep.readOnly = true;
            };
            let streamCamaraSocio = null;

            const detenerCamaraSocio = function () {
                if (streamCamaraSocio) {
                    streamCamaraSocio.getTracks().forEach(function (track) { track.stop(); });
                    streamCamaraSocio = null;
                }
                if (videoCamaraSocio) {
                    videoCamaraSocio.srcObject = null;
                    videoCamaraSocio.style.display = 'none';
                }
                if (btnCapturarCamaraSocio) btnCapturarCamaraSocio.style.display = 'none';
                if (btnCerrarCamaraSocio) btnCerrarCamaraSocio.style.display = 'none';
            };

            const iniciarCamaraSocio = async function () {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    window.alert('Este navegador no soporta cámara en tiempo real.');
                    return;
                }
                try {
                    detenerCamaraSocio();
                    streamCamaraSocio = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: 'user' },
                        audio: false,
                    });
                    if (videoCamaraSocio) {
                        videoCamaraSocio.srcObject = streamCamaraSocio;
                        videoCamaraSocio.style.display = '';
                    }
                    if (btnCapturarCamaraSocio) btnCapturarCamaraSocio.style.display = '';
                    if (btnCerrarCamaraSocio) btnCerrarCamaraSocio.style.display = '';
                } catch (err) {
                    console.error(err);
                    window.alert('No se pudo abrir la cámara. Revisa permisos del navegador.');
                }
            };

            const capturarCamaraSocio = function () {
                if (!videoCamaraSocio || !canvasCamaraSocio || !fotoSocioInput) return;
                const w = videoCamaraSocio.videoWidth || 640;
                const h = videoCamaraSocio.videoHeight || 480;
                canvasCamaraSocio.width = w;
                canvasCamaraSocio.height = h;
                const ctx = canvasCamaraSocio.getContext('2d');
                if (!ctx) return;
                ctx.drawImage(videoCamaraSocio, 0, 0, w, h);
                canvasCamaraSocio.toBlob(function (blob) {
                    if (!blob) return;
                    const archivo = new File([blob], 'foto_camara_' + Date.now() + '.jpg', { type: 'image/jpeg' });
                    const dt = new DataTransfer();
                    dt.items.add(archivo);
                    fotoSocioInput.files = dt.files;
                    if (fotoSocioPreview) {
                        fotoSocioPreview.src = URL.createObjectURL(blob);
                        fotoSocioPreview.style.display = '';
                    }
                    detenerCamaraSocio();
                }, 'image/jpeg', 0.92);
            };

            const limpiarFormulario = function () {
                formSocio.reset();
                formSocio.classList.remove('was-validated');
                socioEditId.value = '';
                modalSocioLabel.innerHTML = '<i class="fa-solid fa-user-plus me-2"></i>Alta de socio';
                btnSubmitSocio.innerHTML = '<i class="fa-solid fa-check"></i> Guardar socio';
                setModoTitular();
                if (fotoSocioPreview) {
                    fotoSocioPreview.src = '';
                    fotoSocioPreview.style.display = 'none';
                }
                detenerCamaraSocio();
            };

            const renderSelect = function (select, data, labelBuilder) {
                if (!select) return;
                select.innerHTML = '<option value="">Selecciona...</option>';
                (data || []).forEach(function (item) {
                    const opt = document.createElement('option');
                    opt.value = String(item.id);
                    opt.textContent = labelBuilder(item);
                    select.appendChild(opt);
                });
            };

            const cargarCatalogos = async function () {
                try {
                    const [rTipos, rGrupos, rDescs] = await Promise.all([
                        fetch(apiTiposSocio, { headers: { 'Accept': 'application/json' } }),
                        fetch(apiGruposSocio, { headers: { 'Accept': 'application/json' } }),
                        fetch(apiDescuentos, { headers: { 'Accept': 'application/json' } }),
                    ]);
                    const [jTipos, jGrupos, jDescs] = await Promise.all([
                        parseJsonResponse(rTipos), parseJsonResponse(rGrupos), parseJsonResponse(rDescs),
                    ]);
                    catalogoTiposSocio = jTipos.data || [];
                    renderSelect(selectTipoSocio, jTipos.data || [], function (x) { return x.tipo_socio || ('Tipo ' + x.id); });
                    renderSelect(selectGrupoSocio, jGrupos.data || [], function (x) { return x.tipo_socio || ('Grupo ' + x.id); });
                    renderSelect(selectDescuentoSocio, jDescs.data || [], function (x) {
                        return (x.tipo_socio || ('Descuento ' + x.id)) + ' (' + String(x.porcentaje_des || 0) + '%)';
                    });
                    setModoTitular();
                } catch (err) {
                    console.error(err);
                    window.alert('No se pudieron cargar los catálogos de socios.');
                }
            };

            const celdaTextoLargo = function (valor, max) {
                const t = String(valor || '').trim();
                if (!t) {
                    return '—';
                }
                const corto = t.length > (max || 40) ? t.substring(0, max || 40) + '…' : t;
                return '<span class="text-truncate d-inline-block" style="max-width:180px;" title="' + esc(t) + '">' + esc(corto) + '</span>';
            };

            const renderTabla = function (lista) {
                if (window.GestionAlumnosDataTables) {
                    window.GestionAlumnosDataTables.destroy('#tableSocios');
                }

                tbodySociosListado.innerHTML = '';
                if (!lista.length) {
                    tbodySociosListado.innerHTML = '<tr><td class="text-muted" colspan="17">Sin registros por el momento.</td></tr>';
                    return;
                }
                lista.forEach(function (s) {
                    const foto = s.foto_path ? ('/' + String(s.foto_path)) : null;
                    const nombreTitular = s.titular ? nombreCompleto(s.titular) : '—';
                    const botonAllegado = esTitular(s)
                        ? '<button class="btn btn-outline-secondary border-0 m-0 btn-allegado-socio" data-id="' + esc(s.id) + '" data-numero-socio="' + esc(s.numero_socio || '') + '" data-nombre="' + esc(nombreCompleto(s) || s.nombre || 'Titular') + '" title="Agregar familiar"><i class="fa-solid fa-user-plus fs-8"></i></button>'
                        : '';
                    const tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td>' + esc(s.numero_socio || '—') + '</td>' +
                        '<td>' + (foto ? ('<img src="' + esc(foto) + '" alt="Foto" style="width:36px;height:36px;object-fit:cover;border-radius:999px;">') : '—') + '</td>' +
                        '<td>' + esc(nombreCompleto(s) || '—') + '</td>' +
                        '<td>' + esc((s.tipo_socio && s.tipo_socio.tipo_socio) ? s.tipo_socio.tipo_socio : '—') + '</td>' +
                        '<td>' + esc((s.grupo && s.grupo.tipo_socio) ? s.grupo.tipo_socio : '—') + '</td>' +
                        '<td>' + esc(s.empresa || '—') + '</td>' +
                        '<td>' + esc(s.telefono || '—') + '</td>' +
                        '<td>' + celdaTextoLargo(s.correo, 35) + '</td>' +
                        '<td>' + esc(s.cuota_periodicidad || '—') + '</td>' +
                        '<td>' + esc(s.fecha_facturacion || '—') + '</td>' +
                        '<td>' + celdaTextoLargo(s.domicilio, 50) + '</td>' +
                        '<td>' + esc(s.edad != null ? s.edad : '—') + '</td>' +
                        '<td>' + esc((s.descuento && s.descuento.tipo_socio) ? s.descuento.tipo_socio : '—') + '</td>' +
                        '<td>' + esc(s.id_porcentaje_des != null ? s.id_porcentaje_des : ((s.descuento && s.descuento.porcentaje_des != null) ? s.descuento.porcentaje_des : '—')) + '</td>' +
                        '<td>' + esc(s.status || 'pendiente') + '</td>' +
                        '<td>' + esc(nombreTitular || '—') + '</td>' +
                        '<td class="text-end">' +
                        '  ' + botonAllegado +
                        '  <button class="btn btn-baseColor-light border-0 m-0 btn-pagos-socio" data-id="' + esc(s.id) + '" data-nombre="' + esc(nombreCompleto(s) || s.nombre || "Socio") + '" title="Pagos"><i class="fa-solid fa-money-bill-wave fs-8"></i></button>' +
                        '  <button class="btn btn-primary border-0 m-0 btn-editar-socio" data-id="' + esc(s.id) + '" title="Editar"><i class="fa-solid fa-pen fs-8"></i></button>' +
                        '</td>';
                    tbodySociosListado.appendChild(tr);
                });

                if (window.GestionAlumnosDataTables) {
                    window.GestionAlumnosDataTables.init('#tableSocios');
                }
            };

            const cargarTabla = async function () {
                try {
                    const q = buscadorSocios ? (buscadorSocios.value || '').trim() : '';
                    const url = q ? (apiSocios + '?q=' + encodeURIComponent(q)) : apiSocios;
                    const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const j = await parseJsonResponse(r);
                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudo cargar el listado de socios.');
                        renderTabla([]);
                        return;
                    }
                    renderTabla(j.data || []);
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al cargar socios.');
                    renderTabla([]);
                }
            };

            const endpointMontoSugerido = function (id, periodicidad) {
                return apiSociosPagos + '/' + encodeURIComponent(String(id)) + '/monto-sugerido?periodicidad=' + encodeURIComponent(periodicidad);
            };
            const endpointSiguienteDependiente = function (id) {
                return apiSociosPagos + '/' + encodeURIComponent(String(id)) + '/siguiente-dependiente';
            };
            const endpointPlanesPago = function (id) {
                return apiSociosPagos + '/' + encodeURIComponent(String(id)) + '/planes-pago';
            };
            const endpointPagarDetalle = function (id, detalleId) {
                return apiSociosPagos + '/' + encodeURIComponent(String(id)) + '/planes-pago/detalle/' + encodeURIComponent(String(detalleId)) + '/pagar';
            };
            const endpointCancelarDetalle = function (id, detalleId) {
                return apiSociosPagos + '/' + encodeURIComponent(String(id)) + '/planes-pago/detalle/' + encodeURIComponent(String(detalleId)) + '/cancelar';
            };
            const plazosDefault = function (periodicidad) {
                if (periodicidad === 'quincenal') return 24;
                if (periodicidad === 'mensual') return 12;
                if (periodicidad === 'bimestral') return 6;
                if (periodicidad === 'trimestral') return 4;
                if (periodicidad === 'semestral') return 2;
                if (periodicidad === 'anual') return 1;
                return 12;
            };

            const renderPagosDetalle = function (planes) {
                if (!tbodyPagosSocioDetalle) return;
                tbodyPagosSocioDetalle.innerHTML = '';
                let totalPagado = 0;
                let totalCancelado = 0;
                let totalProgramado = 0;
                if (!planes || !planes.length) {
                    tbodyPagosSocioDetalle.innerHTML = '<tr><td class="text-muted" colspan="6">Sin plan generado.</td></tr>';
                    if (cardTotalPagadoSocio) cardTotalPagadoSocio.textContent = '$0.00';
                    if (cardTotalCanceladoSocio) cardTotalCanceladoSocio.textContent = '$0.00';
                    if (cardSaldoNetoSocio) cardSaldoNetoSocio.textContent = '$0.00';
                    return;
                }
                planes.forEach(function (plan) {
                    (plan.detalles || []).forEach(function (d) {
                        const st = String(d.status || 'pendiente').toLowerCase();
                        const montoProgramado = Number(d.monto_programado || 0);
                        const montoPagado = Number(d.monto_pagado || montoProgramado || 0);
                        totalProgramado += montoProgramado;
                        if (st === 'pagado') {
                            totalPagado += montoPagado;
                        }
                        if (st === 'cancelado') {
                            totalCancelado += montoPagado;
                        }
                        const esVencido = st === 'vencido';
                        const esPagado = st === 'pagado';
                        const esCancelado = st === 'cancelado';
                        const esInactivo = false;
                        const rowClass = esVencido ? 'table-danger' : '';
                        const badgeClass = esPagado ? 'bg-success' : (esVencido ? 'bg-danger' : (esCancelado ? 'bg-secondary' : 'bg-warning text-dark'));
                        const botonAccion = esPagado
                            ? '<button type="button" class="btn btn-sm btn-outline-danger btn-cancelar-pago-socio" data-detalle-id="' + esc(d.id) + '">Cancelar</button>'
                            : (esInactivo
                                ? '<button type="button" class="btn btn-sm btn-secondary" disabled>Inactivo</button>'
                                : '<div class="d-flex gap-1">' +
                                  '  <button type="button" class="btn btn-sm btn-outline-success btn-marcar-pagado-socio" data-detalle-id="' + esc(d.id) + '">Pagar</button>' +
                                  '</div>');
                        const tr = document.createElement('tr');
                        tr.className = rowClass;
                        tr.innerHTML =
                            '<td>#' + esc(plan.id) + '</td>' +
                            '<td>' + esc(d.num_plazo) + '/' + esc(plan.plazos) + '</td>' +
                            '<td>' + esc(d.fecha_programada || '—') + '</td>' +
                            '<td>' + money(d.monto_programado) + '</td>' +
                            '<td><span class="badge ' + badgeClass + '">' + esc(d.status || 'pendiente') + '</span></td>' +
                            '<td>' + botonAccion + '</td>';
                        tbodyPagosSocioDetalle.appendChild(tr);
                    });
                });
                const saldoNeto = totalProgramado - totalPagado + totalCancelado;
                if (cardTotalPagadoSocio) cardTotalPagadoSocio.textContent = money(totalPagado);
                if (cardTotalCanceladoSocio) cardTotalCanceladoSocio.textContent = money(totalCancelado);
                if (cardSaldoNetoSocio) cardSaldoNetoSocio.textContent = money(saldoNeto);
            };

            const cargarPlanesPagoSocio = async function (id) {
                try {
                    const r = await fetch(endpointPlanesPago(id), { headers: { 'Accept': 'application/json' } });
                    const j = await parseJsonResponse(r);
                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudieron cargar los pagos del socio.');
                        renderPagosDetalle([]);
                        if (btnGenerarPlanSocio) {
                            btnGenerarPlanSocio.disabled = false;
                            btnGenerarPlanSocio.innerHTML = '<i class="fa-solid fa-calendar-check"></i> Generar plan';
                        }
                        return;
                    }
                    const planes = j.data || [];
                    renderPagosDetalle(planes);
                    const tienePlan = Array.isArray(planes) && planes.length > 0;
                    if (btnGenerarPlanSocio) {
                        btnGenerarPlanSocio.disabled = tienePlan;
                        btnGenerarPlanSocio.innerHTML = tienePlan
                            ? '<i class="fa-solid fa-lock"></i> Plan ya generado'
                            : '<i class="fa-solid fa-calendar-check"></i> Generar plan';
                    }
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al cargar pagos del socio.');
                    renderPagosDetalle([]);
                    if (btnGenerarPlanSocio) {
                        btnGenerarPlanSocio.disabled = false;
                        btnGenerarPlanSocio.innerHTML = '<i class="fa-solid fa-calendar-check"></i> Generar plan';
                    }
                }
            };

            const cargarMontoSugerido = async function () {
                const id = pagosSocioId ? String(pagosSocioId.value || '').trim() : '';
                const periodicidad = periodicidadPagoSocio ? String(periodicidadPagoSocio.value || 'mensual') : 'mensual';
                if (!id) return;
                try {
                    const r = await fetch(endpointMontoSugerido(id, periodicidad), { headers: { 'Accept': 'application/json' } });
                    const j = await parseJsonResponse(r);
                    if (!r.ok) {
                        return;
                    }
                    const data = j.data || {};
                    if (montoBaseSocio) montoBaseSocio.textContent = money(data.monto_base || 0);
                    if (montoDescSocio) montoDescSocio.textContent = '- ' + String(data.porcentaje_descuento || 0) + '%';
                    if (montoFinalSocio) montoFinalSocio.textContent = money(data.monto_final_periodo || 0);
                } catch (err) {
                    console.error(err);
                }
            };

            const abrirModalPagos = function (id, nombre) {
                if (!pagosSocioId || !modalPagosSocio) return;
                pagosSocioId.value = String(id);
                if (nombreSocioPagos) nombreSocioPagos.textContent = nombre || 'Socio';
                const hoy = new Date();
                const iso = hoy.getFullYear() + '-' + String(hoy.getMonth() + 1).padStart(2, '0') + '-' + String(hoy.getDate()).padStart(2, '0');
                if (fechaInicioPagoSocio) fechaInicioPagoSocio.value = iso;
                if (periodicidadPagoSocio) {
                    periodicidadPagoSocio.value = 'mensual';
                }
                if (plazosPagoSocio) {
                    plazosPagoSocio.value = String(plazosDefault('mensual'));
                }
                if (formPlanPagosSocio) {
                    formPlanPagosSocio.classList.remove('was-validated');
                }
                if (window.bootstrap) {
                    window.bootstrap.Modal.getOrCreateInstance(modalPagosSocio).show();
                }
                void cargarMontoSugerido();
                void cargarPlanesPagoSocio(id);
            };

            const abrirAltaAllegado = async function (titularId, titularNombre, numeroSocio) {
                limpiarFormulario();
                if (idTitularHidden) idTitularHidden.value = String(titularId);
                setFieldVal('numero_socio', numeroSocio || '');
                const numSocio = formSocio.querySelector('[name="numero_socio"]');
                const numDep = formSocio.querySelector('[name="numero_dependiente"]');
                if (numSocio) numSocio.readOnly = true;
                if (numDep) numDep.readOnly = true;
                if (selectTipoSocio) selectTipoSocio.disabled = true;
                modalSocioLabel.innerHTML = '<i class="fa-solid fa-user-plus me-2"></i>Alta de familiar de ' + (titularNombre || 'titular');
                try {
                    const r = await fetch(endpointSiguienteDependiente(titularId), { headers: { 'Accept': 'application/json' } });
                    const j = await parseJsonResponse(r);
                    if (r.ok && j && j.data) {
                        setFieldVal('numero_socio', j.data.numero_socio || numeroSocio || '');
                        setFieldVal('numero_dependiente', j.data.numero_dependiente || '');
                    }
                } catch (err) {
                    console.error(err);
                }
                if (window.bootstrap && modalSocio) {
                    window.bootstrap.Modal.getOrCreateInstance(modalSocio).show();
                }
            };

            const abrirEdicion = async function (id) {
                try {
                    const r = await fetch(apiSocios + '/' + encodeURIComponent(id), { headers: { 'Accept': 'application/json' } });
                    const j = await parseJsonResponse(r);
                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudo cargar el socio.');
                        return;
                    }
                    const s = j.data;
                    socioEditId.value = String(s.id);
                    setFieldVal('numero_socio', s.numero_socio || '');
                    setFieldVal('numero_dependiente', s.numero_dependiente || '');
                    if (idTitularHidden) {
                        idTitularHidden.value = s.id_titular ? String(s.id_titular) : '';
                    }
                    setFieldVal('id_tipo_socio', s.id_tipo_socio || '');
                    setFieldVal('nombre', s.nombre || '');
                    setFieldVal('segund_nom', s.segund_nom || '');
                    setFieldVal('ap_paterno', s.ap_paterno || '');
                    setFieldVal('ap_materno', s.ap_materno || '');
                    setFieldVal('telefono', s.telefono || '');
                    setFieldVal('correo', s.correo || '');
                    setFieldVal('cuota_periodicidad', s.cuota_periodicidad || '');
                    setFieldVal('fecha_facturacion', s.fecha_facturacion || '');
                    setFieldVal('domicilio', s.domicilio || '');
                    setFieldVal('edad', s.edad != null ? s.edad : '');
                    setFieldVal('empresa', s.empresa || '');
                    setFieldVal('id_grupo', s.id_grupo || '');
                    setFieldVal('id_descuento', s.id_descuento || '');
                    setFieldVal('id_porcentaje_des', s.id_porcentaje_des != null ? s.id_porcentaje_des : '');
                    if (fotoSocioInput) {
                        fotoSocioInput.required = false;
                    }
                    const numSocio = formSocio.querySelector('[name="numero_socio"]');
                    const numDep = formSocio.querySelector('[name="numero_dependiente"]');
                    if (numSocio) numSocio.readOnly = !!s.id_titular;
                    if (numDep) numDep.readOnly = true;
                    if (selectTipoSocio) selectTipoSocio.disabled = true;
                    if (fotoSocioPreview) {
                        if (s.foto_path) {
                            fotoSocioPreview.src = '/' + String(s.foto_path);
                            fotoSocioPreview.style.display = '';
                        } else {
                            fotoSocioPreview.src = '';
                            fotoSocioPreview.style.display = 'none';
                        }
                    }
                    modalSocioLabel.innerHTML = '<i class="fa-solid fa-pen me-2"></i>Editar socio';
                    btnSubmitSocio.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Actualizar socio';
                    if (window.bootstrap && modalSocio) {
                        window.bootstrap.Modal.getOrCreateInstance(modalSocio).show();
                    }
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al cargar el socio.');
                }
            };

            if (formSocio) {
                formSocio.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    if (!formSocio.checkValidity()) {
                        formSocio.classList.add('was-validated');
                        return;
                    }
                    const editId = socioEditId && socioEditId.value ? String(socioEditId.value).trim() : '';
                    const formData = new FormData(formSocio);
                    if (selectTipoSocio && selectTipoSocio.value) {
                        formData.set('id_tipo_socio', selectTipoSocio.value);
                    }
                    ['id_titular', 'numero_dependiente', 'segund_nom', 'ap_paterno', 'ap_materno', 'telefono', 'edad', 'empresa', 'id_grupo', 'id_descuento', 'id_porcentaje_des']
                        .forEach(function (key) {
                            if (!formData.get(key)) {
                                formData.delete(key);
                            }
                        });
                    try {
                        const url = editId ? (apiSocios + '/' + encodeURIComponent(editId)) : apiSocios;
                        if (editId) {
                            formData.append('_method', 'PUT');
                        }
                        const r = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': getCsrfToken(),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: formData,
                        });
                        const j = await parseJsonResponse(r);
                        if (!r.ok) {
                            let msg = (j && j.message) ? j.message : 'No se pudo guardar.';
                            if (j && j.errors) {
                                msg = Object.values(j.errors).flat().join('\n');
                            }
                            window.alert(msg);
                            return;
                        }
                        window.alert((j && j.message) ? j.message : 'Guardado correctamente.');
                        limpiarFormulario();
                        await cargarTabla();
                        if (window.bootstrap && modalSocio) {
                            window.bootstrap.Modal.getOrCreateInstance(modalSocio).hide();
                        }
                    } catch (err) {
                        console.error(err);
                        window.alert('Error de red al guardar socio.');
                    }
                });
            }

            if (tbodySociosListado) {
                tbodySociosListado.addEventListener('click', function (event) {
                    const btnEdit = event.target.closest('.btn-editar-socio');
                    if (btnEdit) {
                        const id = btnEdit.getAttribute('data-id');
                        if (id) {
                            void abrirEdicion(id);
                        }
                        return;
                    }

                    const btnPagos = event.target.closest('.btn-pagos-socio');
                    if (btnPagos) {
                        const id = btnPagos.getAttribute('data-id');
                        const nombre = btnPagos.getAttribute('data-nombre') || 'Socio';
                        if (id) {
                            abrirModalPagos(id, nombre);
                        }
                        return;
                    }

                    const btnAllegado = event.target.closest('.btn-allegado-socio');
                    if (btnAllegado) {
                        const titularId = btnAllegado.getAttribute('data-id');
                        const titularNombre = btnAllegado.getAttribute('data-nombre') || 'titular';
                        const numeroSocio = btnAllegado.getAttribute('data-numero-socio') || '';
                        if (titularId) {
                            void abrirAltaAllegado(titularId, titularNombre, numeroSocio);
                        }
                    }
                });
            }

            if (buscadorSocios) {
                buscadorSocios.addEventListener('input', function () {
                    void cargarTabla();
                });
            }

            if (fotoSocioInput) {
                fotoSocioInput.addEventListener('change', function () {
                    const file = fotoSocioInput.files && fotoSocioInput.files[0] ? fotoSocioInput.files[0] : null;
                    if (!file || !fotoSocioPreview) {
                        return;
                    }
                    detenerCamaraSocio();
                    const reader = new FileReader();
                    reader.onload = function (ev) {
                        fotoSocioPreview.src = String(ev.target && ev.target.result ? ev.target.result : '');
                        fotoSocioPreview.style.display = '';
                    };
                    reader.readAsDataURL(file);
                });
            }

            if (btnIniciarCamaraSocio) {
                btnIniciarCamaraSocio.addEventListener('click', function () {
                    void iniciarCamaraSocio();
                });
            }
            if (btnCapturarCamaraSocio) {
                btnCapturarCamaraSocio.addEventListener('click', function () {
                    capturarCamaraSocio();
                });
            }
            if (btnCerrarCamaraSocio) {
                btnCerrarCamaraSocio.addEventListener('click', function () {
                    detenerCamaraSocio();
                });
            }

            if (periodicidadPagoSocio) {
                periodicidadPagoSocio.addEventListener('change', function () {
                    if (plazosPagoSocio) {
                        plazosPagoSocio.value = String(plazosDefault(periodicidadPagoSocio.value));
                    }
                    void cargarMontoSugerido();
                });
            }

            if (formPlanPagosSocio) {
                formPlanPagosSocio.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    if (!formPlanPagosSocio.checkValidity()) {
                        formPlanPagosSocio.classList.add('was-validated');
                        return;
                    }
                    const id = pagosSocioId ? String(pagosSocioId.value || '').trim() : '';
                    if (!id) {
                        window.alert('No hay socio seleccionado.');
                        return;
                    }
                    const payload = {
                        periodicidad: periodicidadPagoSocio ? periodicidadPagoSocio.value : 'mensual',
                        fecha_inicio: fechaInicioPagoSocio ? fechaInicioPagoSocio.value : null,
                        plazos: plazosPagoSocio && plazosPagoSocio.value ? Number(plazosPagoSocio.value) : null,
                    };
                    try {
                        const r = await fetch(endpointPlanesPago(id), {
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
                            let msg = (j && j.message) ? j.message : 'No se pudo generar el plan de pagos.';
                            if (j && j.errors) {
                                msg = Object.values(j.errors).flat().join('\n');
                            }
                            window.alert(msg);
                            return;
                        }
                        window.alert((j && j.message) ? j.message : 'Plan de pagos generado.');
                        await cargarPlanesPagoSocio(id);
                    } catch (err) {
                        console.error(err);
                        window.alert('Error de red al generar el plan de pagos.');
                    }
                });
            }

            if (tbodyPagosSocioDetalle) {
                tbodyPagosSocioDetalle.addEventListener('click', async function (event) {
                    const btnPagar = event.target.closest('.btn-marcar-pagado-socio');
                    const btnCancelar = event.target.closest('.btn-cancelar-pago-socio');
                    if (!btnPagar && !btnCancelar) return;
                    const id = pagosSocioId ? String(pagosSocioId.value || '').trim() : '';
                    const detalleId = btnPagar
                        ? btnPagar.getAttribute('data-detalle-id')
                        : btnCancelar.getAttribute('data-detalle-id');
                    if (!id || !detalleId) return;
                    try {
                        const endpoint = btnPagar ? endpointPagarDetalle(id, detalleId) : endpointCancelarDetalle(id, detalleId);
                        const r = await fetch(endpoint, {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': getCsrfToken(),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                        const j = await parseJsonResponse(r);
                        if (!r.ok) {
                            window.alert((j && j.message) ? j.message : 'No se pudo actualizar el pago.');
                            return;
                        }
                        window.alert((j && j.message) ? j.message : 'Pago actualizado.');
                        await cargarPlanesPagoSocio(id);
                    } catch (err) {
                        console.error(err);
                        window.alert('Error de red al actualizar pago.');
                    }
                });
            }

            if (modalSocio) {
                modalSocio.addEventListener('show.bs.modal', function (event) {
                    const trigger = event.relatedTarget;
                    const alta = trigger && trigger.id === 'btnNuevoTitular';
                    if (alta && (!socioEditId || !socioEditId.value)) {
                        limpiarFormulario();
                        if (fotoSocioInput) {
                            fotoSocioInput.required = true;
                        }
                        setModoTitular();
                    }
                });
                modalSocio.addEventListener('hidden.bs.modal', function () {
                    limpiarFormulario();
                    if (fotoSocioInput) {
                        fotoSocioInput.required = true;
                    }
                    detenerCamaraSocio();
                });
            }

            if (formCargaMasivaSocios) {
                formCargaMasivaSocios.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    if (!formCargaMasivaSocios.checkValidity()) {
                        formCargaMasivaSocios.classList.add('was-validated');
                        return;
                    }
                    if (!archivoMasivoSocios || !archivoMasivoSocios.files || !archivoMasivoSocios.files[0]) {
                        window.alert('Selecciona un archivo Excel.');
                        return;
                    }

                    const fd = new FormData();
                    fd.append('archivo', archivoMasivoSocios.files[0]);
                    if (actualizarExistentesSocios && actualizarExistentesSocios.checked) {
                        fd.append('actualizar_existentes', '1');
                    }

                    if (btnImportarSociosMasivo) {
                        btnImportarSociosMasivo.disabled = true;
                    }

                    try {
                        const r = await fetch(apiSocios + '/importar-masivo', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': getCsrfToken(),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: fd,
                        });
                        const j = await parseJsonResponse(r);
                        if (!r.ok) {
                            let msg = (j && j.message) ? j.message : 'No se pudo importar el directorio.';
                            if (j && j.errors) {
                                msg += '\n' + Object.values(j.errors).flat().join('\n');
                            }
                            window.alert(msg);
                            return;
                        }

                        const d = (j && j.data) ? j.data : {};
                        const lineas = [
                            (j && j.message) ? j.message : 'Importación finalizada.',
                            'Hoja: ' + (d.hoja || '—'),
                            'Creados: ' + (d.creados ?? 0),
                            'Actualizados: ' + (d.actualizados ?? 0),
                            'Omitidos: ' + (d.omitidos ?? 0),
                        ];
                        if (Array.isArray(d.errores) && d.errores.length) {
                            lineas.push('Errores:');
                            d.errores.slice(0, 15).forEach(function (e) { lineas.push('• ' + e); });
                            if (d.errores.length > 15) {
                                lineas.push('… y ' + (d.errores.length - 15) + ' más.');
                            }
                        }
                        if (resultadoCargaMasivaSocios) {
                            resultadoCargaMasivaSocios.textContent = lineas.join('\n');
                            resultadoCargaMasivaSocios.classList.remove('d-none');
                        }
                        await cargarTabla();
                        if (window.bootstrap && modalCargaMasivaSocios && (d.errores || []).length === 0) {
                            setTimeout(function () {
                                bootstrap.Modal.getOrCreateInstance(modalCargaMasivaSocios).hide();
                            }, 1200);
                        }
                    } catch (err) {
                        console.error(err);
                        window.alert('Error de red al importar socios.');
                    } finally {
                        if (btnImportarSociosMasivo) {
                            btnImportarSociosMasivo.disabled = false;
                        }
                    }
                });
            }

            await cargarCatalogos();
            await cargarTabla();
        });
    </script>
@endsection
