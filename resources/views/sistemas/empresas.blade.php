@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}?v={{ (int) @filemtime(public_path('css/vistas.css')) }}" rel="stylesheet">

<div class="container-fluid" id="empModulosApp"
    data-catalogo-url="{{ url('/Sistemas/Empresas') }}"
    data-csrf="{{ csrf_token() }}">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-building"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Empresas y módulos</h2>
                        <p class="text-muted mb-0">Tú, dueño del ERP, defines qué se le vendió a cada cliente y quién es su superusuario.</p>
                    </div>
                </div>
                <div class="header-actions">
                    <a class="btn btn-baseColor-light fs-7 mb-2" href="/Panel">
                        <i class="fa-solid fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow p-3 mt-2 mb-4 bg-body rounded-5">
        <div class="card-header bg-body border-0 pb-0">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <h5 class="text-secondary mb-1">
                        <i class="fa-solid fa-city me-2"></i>Empresa
                    </h5>
                        <p class="text-muted fs-8 mb-0">Primero la empresa. Después marcas lo vendido y nombras al superusuario de ese cliente.</p>
                </div>
                <button type="button" class="btn btn-baseColor fs-7" data-bs-toggle="modal" data-bs-target="#modalNuevaEmpresa">
                    <i class="fa-solid fa-plus"></i> Nueva empresa
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="emp-modulos-grid" id="empGrid">
                @forelse ($empresas as $empresa)
                    <button type="button" class="emp-modulos-card" data-empresa-id="{{ $empresa->id }}"
                        data-vistas="{{ $empresa->total_vistas }}" data-perfiles="{{ $empresa->total_perfiles }}">
                        <span class="emp-modulos-card__icon"><i class="fa-solid fa-building"></i></span>
                        <span class="emp-modulos-card__name">{{ $empresa->nombre_empresa }}</span>
                        <span class="emp-modulos-card__meta">
                            {{ $empresa->total_vistas }} {{ $empresa->total_vistas === 1 ? 'módulo' : 'módulos' }}
                            · {{ $empresa->total_perfiles }} {{ $empresa->total_perfiles === 1 ? 'perfil' : 'perfiles' }}
                        </span>
                    </button>
                @empty
                    <div class="emp-modulos-empty" id="empGridEmpty">No hay empresas. Crea la primera con el botón de arriba.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div id="empWorkspace" hidden>
        <div class="card border-0 shadow p-3 mt-2 mb-4 bg-body rounded-5">
            <div class="card-header bg-body border-0 pb-0">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div>
                        <h5 class="text-secondary mb-1" id="empWorkspaceTitle">
                            <i class="fa-solid fa-list-check me-2"></i>Catálogo de accesos
                        </h5>
                        <p class="text-muted fs-8 mb-0" id="empWorkspaceHint">Marca solo lo que se puede asignar a usuarios de esta empresa.</p>
                    </div>
                    <button type="button" class="btn btn-baseColor fs-6" id="empGuardar">
                        <i class="fa-solid fa-check"></i> Guardar catálogo
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-stretch">
                    <div class="col-lg-7">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <label class="perfil-section-label mb-0">
                                <i class="fa-solid fa-desktop"></i> Módulos
                            </label>
                            <label class="emp-modulos-select-all mb-0">
                                <input class="form-check-input" type="checkbox" id="empVistasTodas">
                                <span>Todos los módulos</span>
                            </label>
                        </div>
                        <div class="emp-modulos-search mb-2">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="search" class="form-control" id="empVistasQ" placeholder="Buscar módulo o departamento…">
                        </div>
                        <div class="emp-modulos-list" id="empVistasList">
                            @forelse ($vistasAgrupadas as $departamento => $vistas)
                                <details class="emp-modulos-depto" open>
                                    <summary>
                                        <span>{{ $departamento }}</span>
                                        <small>{{ $vistas->count() }}</small>
                                    </summary>
                                    <div class="emp-modulos-depto__items">
                                        @foreach ($vistas as $vista)
                                            <label class="emp-modulos-check" data-search="{{ mb_strtolower($departamento.' '.$vista->nombre) }}">
                                                <input class="form-check-input emp-vista-cb" type="checkbox" value="{{ $vista->id }}">
                                                <span>{{ $vista->nombre }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </details>
                            @empty
                                <p class="text-muted fs-8 mb-0">No hay módulos registrados.</p>
                            @endforelse
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <label class="perfil-section-label mb-0">
                                <i class="fa-solid fa-id-badge"></i> Perfiles
                            </label>
                            <label class="emp-modulos-select-all mb-0">
                                <input class="form-check-input" type="checkbox" id="empPerfilesTodos">
                                <span>Todos los perfiles</span>
                            </label>
                        </div>
                        <div class="emp-modulos-search mb-2">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="search" class="form-control" id="empPerfilesQ" placeholder="Buscar perfil…">
                        </div>
                        <div class="emp-modulos-list emp-modulos-list--perfiles" id="empPerfilesList">
                            @forelse ($varperfiles as $perfil)
                                <label class="emp-modulos-check emp-modulos-check--perfil" data-search="{{ mb_strtolower($perfil->nombre.' '.($perfil->descripcion ?? '')) }}">
                                    <input class="form-check-input emp-perfil-cb" type="checkbox" value="{{ $perfil->id }}">
                                    <span>
                                        <strong>{{ $perfil->nombre }}</strong>
                                        @if (!empty($perfil->descripcion))
                                            <small>{{ $perfil->descripcion }}</small>
                                        @endif
                                    </span>
                                </label>
                            @empty
                                <p class="text-muted fs-8 mb-0">No hay perfiles registrados.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow p-3 mt-2 mb-4 bg-body rounded-5">
            <div class="card-header bg-body border-0 pb-0">
                <div>
                    <h5 class="text-secondary mb-1">
                        <i class="fa-solid fa-user-shield me-2"></i>Superusuario de la empresa
                    </h5>
                    <p class="text-muted fs-8 mb-0">
                        Este usuario (tu cliente) entra a Sistemas y asigna a su gente solo los módulos vendidos.
                        Tú sigues siendo el dueño del ERP.
                    </p>
                </div>
            </div>
            <div class="card-body">
                <div id="empSuperList" class="emp-super-list mb-3"></div>
                <div class="emp-super-empty" id="empSuperEmpty">Todavía no hay superusuario en esta empresa.</div>

                <div class="row g-3 align-items-end">
                    <div class="col-lg-7">
                        <label class="form-label" for="empSuperUser">Usuario del cliente</label>
                        <select id="empSuperUser" class="form-select">
                            <option value="">Seleccionar usuario…</option>
                        </select>
                    </div>
                    <div class="col-lg-5">
                        <button type="button" class="btn btn-baseColor w-100" id="empSuperAsignar">
                            <i class="fa-solid fa-user-check"></i> Asignar como superusuario
                        </button>
                    </div>
                    <div class="col-12">
                        <label class="emp-modulos-select-all mb-0">
                            <input class="form-check-input" type="checkbox" id="empSuperPerfiles" checked>
                            <span>Asignarle también los perfiles vendidos, para que vea esos módulos en su menú.</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevaEmpresa" tabindex="-1" aria-labelledby="modalNuevaEmpresaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-dark" id="modalNuevaEmpresaLabel">Nueva empresa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formNuevaEmpresa" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="empNombre">Nombre oficial</label>
                        <input type="text" class="form-control" name="nombre_empresa" id="empNombre" maxlength="100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="empCorto">Nombre corto</label>
                        <input type="text" class="form-control" name="descripcion" id="empCorto" maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="empRep">Representante</label>
                        <input type="text" class="form-control" name="representada" id="empRep" maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="empRfc">RFC</label>
                        <input type="text" class="form-control text-uppercase" name="rfc" id="empRfc" maxlength="13">
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="empDir">Dirección fiscal</label>
                        <input type="text" class="form-control" name="direccion_fiscal" id="empDir" maxlength="255">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-baseColor" id="empCrearBtn">
                        <i class="fa-solid fa-plus"></i> Registrar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const app = document.getElementById('empModulosApp');
    if (!app) return;

    const baseUrl = app.dataset.catalogoUrl;
    const csrf = app.dataset.csrf;
    let empresaId = null;

    const grid = document.getElementById('empGrid');
    const workspace = document.getElementById('empWorkspace');
    const title = document.getElementById('empWorkspaceTitle');
    const hint = document.getElementById('empWorkspaceHint');
    const vistaCbs = () => Array.from(document.querySelectorAll('.emp-vista-cb'));
    const perfilCbs = () => Array.from(document.querySelectorAll('.emp-perfil-cb'));

    function toast(icon, titleText, text) {
        if (window.Swal) {
            const Toast = Swal.mixin({
                toast: true, position: 'top-end', showConfirmButton: false, timer: 3200, timerProgressBar: true
            });
            Toast.fire({ icon: icon, title: titleText, text: text || '' });
            return;
        }
        alert(text || titleText);
    }

    function headers(json) {
        const h = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf
        };
        if (json) h['Content-Type'] = 'application/json';
        return h;
    }

    function syncSelectAll() {
        const vistas = vistaCbs();
        const perfiles = perfilCbs();
        const vistasTodas = document.getElementById('empVistasTodas');
        const perfilesTodos = document.getElementById('empPerfilesTodos');
        if (vistasTodas && vistas.length) vistasTodas.checked = vistas.every((cb) => cb.checked);
        if (perfilesTodos && perfiles.length) perfilesTodos.checked = perfiles.every((cb) => cb.checked);
    }

    function limpiarChecks() {
        vistaCbs().forEach((cb) => { cb.checked = false; });
        perfilCbs().forEach((cb) => { cb.checked = false; });
        syncSelectAll();
    }

    function metaTexto(vistas, perfiles) {
        const v = Number(vistas) || 0;
        const p = Number(perfiles) || 0;
        return v + (v === 1 ? ' módulo' : ' módulos') + ' · ' + p + (p === 1 ? ' perfil' : ' perfiles');
    }

    function actualizarCard(id, vistas, perfiles) {
        const card = grid.querySelector('[data-empresa-id="' + id + '"]');
        if (!card) return;
        card.dataset.vistas = String(vistas);
        card.dataset.perfiles = String(perfiles);
        const meta = card.querySelector('.emp-modulos-card__meta');
        if (meta) meta.textContent = metaTexto(vistas, perfiles);
    }

    async function cargarCatalogo(id, nombre) {
        empresaId = id;
        workspace.hidden = false;
        title.innerHTML = '<i class="fa-solid fa-list-check me-2"></i>' + nombre;
        hint.textContent = 'Marca solo los módulos y perfiles que se pueden asignar a usuarios de ' + nombre + '.';
        limpiarChecks();

        try {
            const response = await fetch(baseUrl + '/' + encodeURIComponent(id) + '/catalogo', { headers: headers(false) });
            if (!response.ok) throw new Error('No se pudo cargar el catálogo');
            const payload = await response.json();
            const data = payload.data || {};
            const vistas = (data.vistas || []).map(String);
            const perfiles = (data.perfiles || []).map(String);
            vistaCbs().forEach((cb) => { cb.checked = vistas.includes(String(cb.value)); });
            perfilCbs().forEach((cb) => { cb.checked = perfiles.includes(String(cb.value)); });
            syncSelectAll();
            pintarSuperusuarios(data);
        } catch (error) {
            console.error(error);
            toast('error', 'No se cargó el catálogo', 'Intenta de nuevo.');
        }
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function pintarSuperusuarios(data) {
        const list = document.getElementById('empSuperList');
        const empty = document.getElementById('empSuperEmpty');
        const select = document.getElementById('empSuperUser');
        if (!list || !select) return;

        const supers = data.superusuarios || [];
        const candidatos = data.usuarios || [];
        list.innerHTML = supers.map(function (user) {
            return '<div class="emp-super-row">' +
                '<div class="emp-super-row__info">' +
                    '<strong>' + escapeHtml(user.name) + '</strong>' +
                    '<small>' + escapeHtml(user.email || user.tipo || 'Superusuario') + '</small>' +
                '</div>' +
                '<button type="button" class="btn btn-outline-secondary fs-8 emp-super-quitar" data-user-id="' + user.id + '">' +
                    '<i class="fa-solid fa-xmark"></i> Quitar' +
                '</button>' +
            '</div>';
        }).join('');
        if (empty) empty.hidden = supers.length > 0;

        const previous = select.value;
        select.innerHTML = '<option value="">Seleccionar usuario…</option>';
        candidatos.forEach(function (user) {
            const opt = document.createElement('option');
            opt.value = String(user.id);
            let label = user.name || ('Usuario ' + user.id);
            if (user.empresa) label += ' — ' + user.empresa;
            else label += ' — sin empresa';
            label += ' · ' + (user.tipo || 'sin tipo');
            if (!user.idempleado) label += ' · sin empleado';
            if (user.estado_user && String(user.estado_user).toUpperCase() !== 'A') {
                label += ' · inactivo';
            }
            opt.textContent = label;
            select.appendChild(opt);
        });
        if (previous && Array.from(select.options).some(function (opt) { return opt.value === previous; })) {
            select.value = previous;
        }
        if (window.jQuery) {
            const $el = window.jQuery(select);
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }
            $el.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Buscar usuario…',
                allowClear: true
            });
        }
    }

    grid.addEventListener('click', function (event) {
        const card = event.target.closest('.emp-modulos-card');
        if (!card) return;
        grid.querySelectorAll('.emp-modulos-card').forEach((el) => el.classList.remove('is-selected'));
        card.classList.add('is-selected');
        cargarCatalogo(card.dataset.empresaId, card.querySelector('.emp-modulos-card__name').textContent.trim());
    });

    document.getElementById('empVistasTodas').addEventListener('change', function () {
        vistaCbs().forEach((cb) => { cb.checked = this.checked; });
    });
    document.getElementById('empPerfilesTodos').addEventListener('change', function () {
        perfilCbs().forEach((cb) => { cb.checked = this.checked; });
    });
    document.getElementById('empVistasList').addEventListener('change', syncSelectAll);
    document.getElementById('empPerfilesList').addEventListener('change', syncSelectAll);

    function filtrarLista(inputId, listId) {
        const q = (document.getElementById(inputId).value || '').trim().toLowerCase();
        document.querySelectorAll('#' + listId + ' .emp-modulos-check').forEach(function (row) {
            const hay = (row.dataset.search || '').indexOf(q) !== -1;
            row.hidden = q !== '' && !hay;
        });
        if (listId === 'empVistasList') {
            document.querySelectorAll('#empVistasList .emp-modulos-depto').forEach(function (depto) {
                const visible = Array.from(depto.querySelectorAll('.emp-modulos-check')).some((row) => !row.hidden);
                depto.hidden = q !== '' && !visible;
            });
        }
    }
    document.getElementById('empVistasQ').addEventListener('input', function () { filtrarLista('empVistasQ', 'empVistasList'); });
    document.getElementById('empPerfilesQ').addEventListener('input', function () { filtrarLista('empPerfilesQ', 'empPerfilesList'); });

    document.getElementById('empGuardar').addEventListener('click', async function () {
        if (!empresaId) return;
        const btn = this;
        btn.disabled = true;
        try {
            const response = await fetch(baseUrl + '/' + encodeURIComponent(empresaId) + '/catalogo', {
                method: 'POST',
                headers: headers(true),
                body: JSON.stringify({
                    vistas: vistaCbs().filter((cb) => cb.checked).map((cb) => Number(cb.value)),
                    perfiles: perfilCbs().filter((cb) => cb.checked).map((cb) => Number(cb.value))
                })
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'No se pudo guardar');
            actualizarCard(empresaId, payload.data.total_vistas, payload.data.total_perfiles);
            toast('success', 'Guardado', payload.message);
        } catch (error) {
            console.error(error);
            toast('error', 'No se guardó', error.message || 'Revisa la selección.');
        } finally {
            btn.disabled = false;
        }
    });

    async function aplicarSuperusuarios(payload) {
        if (payload && payload.data) {
            pintarSuperusuarios(payload.data);
        }
        toast('success', 'Listo', payload.message || 'Actualizado');
    }

    document.getElementById('empSuperAsignar').addEventListener('click', async function () {
        if (!empresaId) return;
        const select = document.getElementById('empSuperUser');
        const idUsuario = Number((window.jQuery ? window.jQuery(select).val() : select.value) || 0);
        if (!idUsuario) {
            toast('warning', 'Selecciona un usuario', 'Elige al cliente que será superusuario de esta empresa.');
            return;
        }
        const btn = this;
        btn.disabled = true;
        try {
            const asignarPerfiles = document.getElementById('empSuperPerfiles').checked;
            const response = await fetch(baseUrl + '/' + encodeURIComponent(empresaId) + '/superusuario', {
                method: 'POST',
                headers: headers(true),
                body: JSON.stringify({
                    id_usuario: idUsuario,
                    asignar_perfiles: asignarPerfiles,
                    perfiles: asignarPerfiles
                        ? perfilCbs().filter((cb) => cb.checked).map((cb) => Number(cb.value))
                        : []
                })
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'No se pudo asignar');
            await aplicarSuperusuarios(payload);
        } catch (error) {
            console.error(error);
            toast('error', 'No se asignó', error.message || 'Intenta de nuevo.');
        } finally {
            btn.disabled = false;
        }
    });

    document.getElementById('empSuperList').addEventListener('click', async function (event) {
        const btn = event.target.closest('.emp-super-quitar');
        if (!btn || !empresaId) return;
        const idUsuario = Number(btn.dataset.userId || 0);
        if (!idUsuario) return;
        btn.disabled = true;
        try {
            const response = await fetch(
                baseUrl + '/' + encodeURIComponent(empresaId) + '/superusuario/' + encodeURIComponent(idUsuario) + '/quitar',
                { method: 'POST', headers: headers(true), body: JSON.stringify({}) }
            );
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'No se pudo quitar');
            await aplicarSuperusuarios(payload);
        } catch (error) {
            console.error(error);
            toast('error', 'No se quitó', error.message || 'Intenta de nuevo.');
        } finally {
            btn.disabled = false;
        }
    });

    document.getElementById('formNuevaEmpresa').addEventListener('submit', async function (event) {
        event.preventDefault();
        const form = this;
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }
        const btn = document.getElementById('empCrearBtn');
        btn.disabled = true;
        const body = {
            nombre_empresa: document.getElementById('empNombre').value,
            descripcion: document.getElementById('empCorto').value,
            representada: document.getElementById('empRep').value,
            rfc: document.getElementById('empRfc').value,
            direccion_fiscal: document.getElementById('empDir').value
        };
        try {
            const response = await fetch(baseUrl, {
                method: 'POST',
                headers: headers(true),
                body: JSON.stringify(body)
            });
            const payload = await response.json();
            if (!response.ok) {
                const firstError = payload.errors ? Object.values(payload.errors)[0][0] : (payload.message || 'No se pudo registrar');
                throw new Error(firstError);
            }
            const empty = document.getElementById('empGridEmpty');
            if (empty) empty.remove();
            const data = payload.data;
            const card = document.createElement('button');
            card.type = 'button';
            card.className = 'emp-modulos-card';
            card.dataset.empresaId = String(data.id);
            card.dataset.vistas = '0';
            card.dataset.perfiles = '0';
            card.innerHTML = '<span class="emp-modulos-card__icon"><i class="fa-solid fa-building"></i></span>' +
                '<span class="emp-modulos-card__name"></span>' +
                '<span class="emp-modulos-card__meta">' + metaTexto(0, 0) + '</span>';
            card.querySelector('.emp-modulos-card__name').textContent = data.nombre_empresa;
            grid.appendChild(card);
            form.reset();
            form.classList.remove('was-validated');
            const modalEl = document.getElementById('modalNuevaEmpresa');
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.hide();
            toast('success', 'Empresa creada', payload.message);
            grid.querySelectorAll('.emp-modulos-card').forEach((el) => el.classList.remove('is-selected'));
            card.classList.add('is-selected');
            cargarCatalogo(data.id, data.nombre_empresa);
        } catch (error) {
            console.error(error);
            toast('error', 'No se registró', error.message || 'Revisa los datos.');
        } finally {
            btn.disabled = false;
        }
    });
})();
</script>
@endsection
