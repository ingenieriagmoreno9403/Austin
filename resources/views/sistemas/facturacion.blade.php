@extends('layouts.app')
@section('content')
<div class="container-fluid format_page">
    <div class="row mb-3 align-items-center">
        <div class="col-lg-8 col-12 start-center">
            <h3 class="mt-1 animate__animated animate__backInLeft">Datos Fiscales de Empresas</h3>
            <span class="p-0 m-0 d-none d-md-block fs-8">Administra los datos fiscales de las empresas emisoras para facturación.</span>
        </div>
        <div class="col-lg-4 col-12 center-end mt-lg-0 mt-2 d-flex justify-content-lg-end gap-2 flex-wrap">
            <button type="button" class="btn btn-baseColor fs-8" id="btnNuevaEmpresa">
                <i class="fa-solid fa-plus"></i> Nueva empresa
            </button>
        </div>
    </div>

    @php
        $apiDatosFiscales = url('/Sistemas/api/datos-fiscales');
    @endphp

    <div class="bg-body rounded-2 p-4 mt-2">
        <div class="row g-2 mb-3">
            <div class="col-lg-4 col-md-6">
                <input type="text" class="form-control form-control-sm" id="inputBuscar" placeholder="Buscar por razón social o RFC...">
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-sm btn-baseColor" id="btnBuscar">
                    <i class="fa-solid fa-magnifying-glass"></i> Buscar
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-striped table-hover mb-0">
                <thead>
                    <tr class="text-tr">
                        <th>#</th>
                        <th>Razón Social</th>
                        <th>RFC</th>
                        <th>Régimen Fiscal</th>
                        <th>C.P.</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Estatus</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbodyEmpresas">
                    <tr><td colspan="9" class="text-muted text-center">Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal: Crear / Editar empresa --}}
    <div class="modal fade" id="modalEmpresaFiscal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="modalEmpresaFiscalTitulo">Nueva empresa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formEmpresaFiscal" novalidate>
                        <input type="hidden" id="empresaFiscalId" value="">

                        <h6 class="text-orange mb-3"><i class="fa-solid fa-building"></i> Datos generales</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label class="form-label small mb-1">Razón Social <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="inputRazonSocial" required maxlength="300">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">RFC <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="inputRfc" required maxlength="13" style="text-transform:uppercase;">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Régimen Fiscal <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="selectRegimenFiscal" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="601">601 - General de Ley Personas Morales</option>
                                    <option value="603">603 - Personas Morales con Fines no Lucrativos</option>
                                    <option value="605">605 - Sueldos y Salarios</option>
                                    <option value="606">606 - Arrendamiento</option>
                                    <option value="607">607 - Régimen de Enajenación</option>
                                    <option value="608">608 - Demás ingresos</option>
                                    <option value="610">610 - Residentes en el Extranjero</option>
                                    <option value="612">612 - Personas Físicas con Act. Empresariales</option>
                                    <option value="614">614 - Ingresos por intereses</option>
                                    <option value="616">616 - Sin obligaciones fiscales</option>
                                    <option value="620">620 - Soc. Cooperativas de Producción</option>
                                    <option value="621">621 - Incorporación Fiscal</option>
                                    <option value="625">625 - Actividades Empresariales (RESICO)</option>
                                    <option value="626">626 - Régimen Simplificado de Confianza</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Teléfono</label>
                                <input type="text" class="form-control form-control-sm" id="inputTelefono" maxlength="20">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Correo</label>
                                <input type="email" class="form-control form-control-sm" id="inputCorreo" maxlength="200">
                            </div>
                        </div>

                        <h6 class="text-orange mb-3"><i class="fa-solid fa-location-dot"></i> Dirección fiscal</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small mb-1">Calle</label>
                                <input type="text" class="form-control form-control-sm" id="inputCalle" maxlength="200">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">No. Exterior</label>
                                <input type="text" class="form-control form-control-sm" id="inputNumExt" maxlength="20">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">No. Interior</label>
                                <input type="text" class="form-control form-control-sm" id="inputNumInt" maxlength="20">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Colonia</label>
                                <input type="text" class="form-control form-control-sm" id="inputColonia" maxlength="150">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Municipio</label>
                                <input type="text" class="form-control form-control-sm" id="inputMunicipio" maxlength="150">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Estado</label>
                                <input type="text" class="form-control form-control-sm" id="inputEstado" maxlength="100">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-1">C.P. <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="inputCp" required maxlength="10">
                            </div>
                        </div>

                        <h6 class="text-orange mb-3"><i class="fa-solid fa-key"></i> Credenciales Facturama</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small mb-1">Usuario Facturama</label>
                                <input type="text" class="form-control form-control-sm" id="inputUsrFacturama" maxlength="150" autocomplete="off">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-1">Contraseña Facturama</label>
                                <div class="input-group input-group-sm">
                                    <input type="password" class="form-control form-control-sm" id="inputPwdFacturama" maxlength="255" autocomplete="new-password" placeholder="Dejar en blanco para no cambiar (edición)">
                                    <button type="button" class="btn btn-outline-secondary" id="btnTogglePwdFacturama" title="Mostrar contraseña" aria-label="Mostrar contraseña">
                                        <i class="fa-solid fa-eye" id="iconTogglePwdFacturama"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Estatus</label>
                                <select class="form-select form-select-sm" id="selectEstatus">
                                    <option value="ACTIVO">ACTIVO</option>
                                    <option value="INACTIVO">INACTIVO</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-baseColor btn-sm" id="btnGuardarEmpresaFiscal">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const apiBase = @json($apiDatosFiscales);
    const tbodyEl = document.getElementById('tbodyEmpresas');
    const inputBuscar = document.getElementById('inputBuscar');
    const btnBuscar = document.getElementById('btnBuscar');
    const btnNueva = document.getElementById('btnNuevaEmpresa');
    const btnGuardar = document.getElementById('btnGuardarEmpresaFiscal');
    const modalEl = document.getElementById('modalEmpresaFiscal');
    const modalTitulo = document.getElementById('modalEmpresaFiscalTitulo');

    const REGIMENES = {
        '601': 'General de Ley Personas Morales',
        '603': 'Personas Morales con Fines no Lucrativos',
        '605': 'Sueldos y Salarios',
        '606': 'Arrendamiento',
        '607': 'Régimen de Enajenación',
        '608': 'Demás ingresos',
        '610': 'Residentes en el Extranjero',
        '612': 'Personas Físicas con Act. Empresariales',
        '614': 'Ingresos por intereses',
        '616': 'Sin obligaciones fiscales',
        '620': 'Soc. Cooperativas de Producción',
        '621': 'Incorporación Fiscal',
        '625': 'Actividades Empresariales (RESICO)',
        '626': 'Régimen Simplificado de Confianza'
    };

    function getCsrfToken() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function escapeHtml(txt) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(txt || ''));
        return d.innerHTML;
    }

    async function cargarEmpresas(buscar) {
        tbodyEl.innerHTML = '<tr><td colspan="9" class="text-muted text-center"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</td></tr>';
        try {
            var url = apiBase + (buscar ? '?buscar=' + encodeURIComponent(buscar) : '');
            var r = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            var j = await r.json();
            var data = j.data || [];

            tbodyEl.innerHTML = '';
            if (!data.length) {
                tbodyEl.innerHTML = '<tr><td colspan="9" class="text-muted text-center">No se encontraron registros.</td></tr>';
                return;
            }

            data.forEach(function (e, idx) {
                var badgeEstatus = e.estatus === 'ACTIVO'
                    ? '<span class="badge bg-success">ACTIVO</span>'
                    : '<span class="badge bg-secondary">INACTIVO</span>';
                var regimenTxt = e.regimen_fiscal + (REGIMENES[e.regimen_fiscal] ? ' - ' + REGIMENES[e.regimen_fiscal] : '');
                var tr = document.createElement('tr');
                tr.innerHTML =
                    '<td>' + (idx + 1) + '</td>' +
                    '<td class="text-nowrap">' + escapeHtml(e.razon_social) + '</td>' +
                    '<td class="text-nowrap">' + escapeHtml(e.rfc) + '</td>' +
                    '<td class="small">' + escapeHtml(regimenTxt) + '</td>' +
                    '<td>' + escapeHtml(e.codigo_postal) + '</td>' +
                    '<td>' + escapeHtml(e.telefono || '—') + '</td>' +
                    '<td>' + escapeHtml(e.correo || '—') + '</td>' +
                    '<td>' + badgeEstatus + '</td>' +
                    '<td class="text-nowrap">' +
                        '<button class="btn btn-sm btn-outline-primary btn-editar-fiscal me-1" data-id="' + e.id + '" title="Editar"><i class="fa-solid fa-pen"></i></button>' +
                        '<button class="btn btn-sm btn-outline-danger btn-eliminar-fiscal" data-id="' + e.id + '" data-nombre="' + escapeHtml(e.razon_social) + '" title="Eliminar"><i class="fa-solid fa-trash"></i></button>' +
                    '</td>';
                tbodyEl.appendChild(tr);
            });
        } catch (err) {
            console.error(err);
            tbodyEl.innerHTML = '<tr><td colspan="9" class="text-danger text-center">Error al cargar datos.</td></tr>';
        }
    }

    function resetTogglePwdFacturama() {
        var inputPwd = document.getElementById('inputPwdFacturama');
        var iconPwd = document.getElementById('iconTogglePwdFacturama');
        var btnToggle = document.getElementById('btnTogglePwdFacturama');
        if (!inputPwd || !iconPwd) return;
        inputPwd.type = 'password';
        iconPwd.classList.remove('fa-eye-slash');
        iconPwd.classList.add('fa-eye');
        if (btnToggle) btnToggle.title = 'Mostrar contraseña';
    }

    function limpiarFormulario() {
        document.getElementById('empresaFiscalId').value = '';
        document.getElementById('inputRazonSocial').value = '';
        document.getElementById('inputRfc').value = '';
        document.getElementById('selectRegimenFiscal').value = '';
        document.getElementById('inputTelefono').value = '';
        document.getElementById('inputCorreo').value = '';
        document.getElementById('inputCalle').value = '';
        document.getElementById('inputNumExt').value = '';
        document.getElementById('inputNumInt').value = '';
        document.getElementById('inputColonia').value = '';
        document.getElementById('inputMunicipio').value = '';
        document.getElementById('inputEstado').value = '';
        document.getElementById('inputCp').value = '';
        document.getElementById('inputUsrFacturama').value = '';
        document.getElementById('inputPwdFacturama').value = '';
        resetTogglePwdFacturama();
        document.getElementById('selectEstatus').value = 'ACTIVO';
    }

    function llenarFormulario(e) {
        document.getElementById('empresaFiscalId').value = e.id;
        document.getElementById('inputRazonSocial').value = e.razon_social || '';
        document.getElementById('inputRfc').value = e.rfc || '';
        document.getElementById('selectRegimenFiscal').value = e.regimen_fiscal || '';
        document.getElementById('inputTelefono').value = e.telefono || '';
        document.getElementById('inputCorreo').value = e.correo || '';
        document.getElementById('inputCalle').value = e.calle || '';
        document.getElementById('inputNumExt').value = e.numero_exterior || '';
        document.getElementById('inputNumInt').value = e.numero_interior || '';
        document.getElementById('inputColonia').value = e.colonia || '';
        document.getElementById('inputMunicipio').value = e.municipio || '';
        document.getElementById('inputEstado').value = e.estado || '';
        document.getElementById('inputCp').value = e.codigo_postal || '';
        document.getElementById('inputUsrFacturama').value = e.usr_facturama || '';
        document.getElementById('inputPwdFacturama').value = '';
        resetTogglePwdFacturama();
        document.getElementById('selectEstatus').value = e.estatus || 'ACTIVO';
    }

    function obtenerDatosFormulario() {
        var regimen = document.getElementById('selectRegimenFiscal').value;
        return {
            razon_social: document.getElementById('inputRazonSocial').value.trim(),
            rfc: document.getElementById('inputRfc').value.trim().toUpperCase(),
            regimen_fiscal: regimen,
            regimen_fiscal_descripcion: REGIMENES[regimen] || '',
            telefono: document.getElementById('inputTelefono').value.trim() || null,
            correo: document.getElementById('inputCorreo').value.trim() || null,
            calle: document.getElementById('inputCalle').value.trim() || null,
            numero_exterior: document.getElementById('inputNumExt').value.trim() || null,
            numero_interior: document.getElementById('inputNumInt').value.trim() || null,
            colonia: document.getElementById('inputColonia').value.trim() || null,
            municipio: document.getElementById('inputMunicipio').value.trim() || null,
            estado: document.getElementById('inputEstado').value.trim() || null,
            codigo_postal: document.getElementById('inputCp').value.trim(),
            estatus: document.getElementById('selectEstatus').value,
            usr_facturama: document.getElementById('inputUsrFacturama').value.trim() || null,
            pwd_facturama: document.getElementById('inputPwdFacturama').value || null,
        };
    }

    btnNueva.addEventListener('click', function () {
        limpiarFormulario();
        modalTitulo.textContent = 'Nueva empresa';
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });

    document.getElementById('btnTogglePwdFacturama').addEventListener('click', function () {
        var inputPwd = document.getElementById('inputPwdFacturama');
        var iconPwd = document.getElementById('iconTogglePwdFacturama');
        var mostrar = inputPwd.type === 'password';
        inputPwd.type = mostrar ? 'text' : 'password';
        iconPwd.classList.toggle('fa-eye', !mostrar);
        iconPwd.classList.toggle('fa-eye-slash', mostrar);
        this.title = mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña';
        this.setAttribute('aria-label', this.title);
    });

    btnGuardar.addEventListener('click', async function () {
        var datos = obtenerDatosFormulario();
        if (!datos.razon_social || !datos.rfc || !datos.regimen_fiscal || !datos.codigo_postal) {
            alert('Completa los campos obligatorios: Razón Social, RFC, Régimen Fiscal y C.P.');
            return;
        }

        var id = document.getElementById('empresaFiscalId').value;
        var esEdicion = id && id !== '';
        var url = esEdicion ? apiBase + '/' + id : apiBase;
        var method = esEdicion ? 'PUT' : 'POST';

        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

        try {
            var r = await fetch(url, {
                method: method,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(datos),
            });
            var j = await r.json();
            if (!r.ok) {
                var msg = j.message || 'Error al guardar.';
                if (j.errors) msg = Object.values(j.errors).flat().join('\n');
                alert('Error: ' + msg);
                return;
            }
            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            cargarEmpresas(inputBuscar.value.trim());
        } catch (err) {
            console.error(err);
            alert('Error de red al guardar.');
        } finally {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar';
        }
    });

    tbodyEl.addEventListener('click', async function (e) {
        var btnEditar = e.target.closest('.btn-editar-fiscal');
        if (btnEditar) {
            var id = btnEditar.getAttribute('data-id');
            try {
                var r = await fetch(apiBase + '/' + id, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                var j = await r.json();
                llenarFormulario(j.data);
                modalTitulo.textContent = 'Editar empresa';
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            } catch (err) {
                console.error(err);
                alert('Error al cargar los datos.');
            }
            return;
        }

        var btnEliminar = e.target.closest('.btn-eliminar-fiscal');
        if (btnEliminar) {
            var id = btnEliminar.getAttribute('data-id');
            var nombre = btnEliminar.getAttribute('data-nombre');
            if (!confirm('¿Eliminar los datos fiscales de "' + nombre + '"?')) return;
            try {
                var r = await fetch(apiBase + '/' + id, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                var j = await r.json();
                if (!r.ok) {
                    alert('Error: ' + (j.message || 'No se pudo eliminar.'));
                    return;
                }
                cargarEmpresas(inputBuscar.value.trim());
            } catch (err) {
                console.error(err);
                alert('Error de red al eliminar.');
            }
        }
    });

    btnBuscar.addEventListener('click', function () {
        cargarEmpresas(inputBuscar.value.trim());
    });

    inputBuscar.addEventListener('keyup', function (e) {
        if (e.key === 'Enter') cargarEmpresas(inputBuscar.value.trim());
    });

    cargarEmpresas();
});
</script>
@endsection
