<div>
    @if (session()->has('success_msg_large'))
        <div class="alert alert-success border-0 shadow-sm rounded-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success_msg_large') }}
        </div>
    @endif

    @if (session()->has('warningGuardar'))
        <div class="alert alert-warning border-0 shadow-sm rounded-4">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>{{ session('warningGuardar') }}
        </div>
    @endif

    <div class="card border-0 shadow p-3 mt-2 mb-4 bg-body rounded-5">
        <div class="card-header bg-body border-0 pb-0">
            <h5 class="text-secondary mb-1">
                <i class="fa-solid fa-id-badge me-2"></i>Seleccionar perfil
            </h5>
            <p class="text-muted fs-8 mb-0">Primero elige el perfil para ver y editar sus acciones asignadas.</p>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-lg-8 col-12">
                    <label class="form-label" for="perfilSeleccionado">Perfil</label>
                    <select class="form-select" id="perfilSeleccionado" wire:model="perfilSeleccionado">
                        <option value="">Seleccionar perfil...</option>
                        @foreach ($perfiles as $perfil)
                            <option value="{{ $perfil->id }}">{{ $perfil->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-4 col-12">
                    <div class="acciones-perfiles-summary">
                        <span class="acciones-perfiles-summary__number" id="accionesSeleccionadasTotal">{{ count($accionesSeleccionadas) }}</span>
                        <span class="acciones-perfiles-summary__text">acciones seleccionadas</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($perfilSeleccionado)
        <form wire:submit.prevent="guardar">
            <div class="card border-0 shadow p-3 mt-2 mb-4 bg-body rounded-5">
                <div class="card-header bg-body border-0 pb-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h5 class="text-secondary mb-1">
                                <i class="fa-solid fa-list-check me-2"></i>Acciones por departamento
                            </h5>
                            <p class="text-muted fs-8 mb-0">Marca o desmarca las acciones que tendrá el perfil seleccionado.</p>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <label class="acciones-perfiles-select-all mb-0" for="seleccionarTodasAcciones">
                                <input class="form-check-input" type="checkbox" id="seleccionarTodasAcciones"
                                    onclick="toggleTodasAccionesPerfil(this)">
                                <span>Seleccionar todo</span>
                            </label>
                            <button class="btn btn-baseColor fs-6" type="submit" wire:loading.attr="disabled">
                                <span wire:loading.remove>
                                    <i class="fa-solid fa-floppy-disk"></i> Guardar selección
                                </span>
                                <span wire:loading>
                                    <i class="fa-solid fa-spinner fa-spin"></i> Guardando...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="acciones-perfiles-search mb-3">
                        <label class="form-label mb-1" for="buscarDepartamento">
                            <i class="fa-solid fa-magnifying-glass me-1"></i> Buscar departamento
                        </label>
                        <div class="acciones-perfiles-search__field">
                            <i class="fa-solid fa-building acciones-perfiles-search__icon"></i>
                            <input type="search" class="form-control" id="buscarDepartamento"
                                placeholder="Escribe el nombre del departamento..."
                                autocomplete="off"
                                oninput="filtrarDepartamentosAcciones(this.value)">
                        </div>
                    </div>

                    <div wire:loading.delay class="alert alert-info border-0 rounded-4">
                        <i class="fa-solid fa-spinner fa-spin me-2"></i>Cargando acciones...
                    </div>

                    <div wire:loading.delay.remove class="row g-3 acciones-perfiles-grid" id="accionesPerfilGrid">
                        @forelse ($this->accionesAgrupadas as $departamento => $vistas)
                            @php
                                $departamentoId = 'departamento' . md5($departamento);
                                $totalDepartamento = $vistas->flatten(1)->count();
                            @endphp
                            <div class="col-xl-6 col-12 acciones-perfiles-depto-col"
                                wire:key="departamento-{{ $departamentoId }}"
                                data-departamento="{{ mb_strtolower($departamento) }}">
                                <details class="acciones-perfiles-depto-card" data-depto-card="{{ $departamentoId }}">
                                    <summary class="acciones-perfiles-depto-card__header">
                                        <div class="acciones-perfiles-depto-card__title">
                                            <span class="acciones-perfiles-depto-card__icon">
                                                <i class="fa-solid fa-building"></i>
                                            </span>
                                            <div class="min-w-0">
                                                <p class="acciones-perfiles-depto-card__name text-truncate mb-0">{{ $departamento }}</p>
                                                <span class="acciones-perfiles-count">
                                                    {{ $totalDepartamento }} {{ $totalDepartamento === 1 ? 'acción' : 'acciones' }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="acciones-perfiles-depto-card__actions">
                                            <label class="acciones-perfiles-depto-all mb-0"
                                                for="toggleDepto{{ $departamentoId }}"
                                                onclick="event.preventDefault(); event.stopPropagation(); var cb = this.querySelector('input'); cb.checked = !cb.checked; toggleAccionesDepartamento('{{ $departamentoId }}', cb);">
                                                <input class="form-check-input acciones-perfiles-depto-all__input"
                                                    type="checkbox"
                                                    id="toggleDepto{{ $departamentoId }}"
                                                    data-depto-toggle="{{ $departamentoId }}"
                                                    onclick="event.stopPropagation(); toggleAccionesDepartamento('{{ $departamentoId }}', this);">
                                                <span>Todas</span>
                                            </label>
                                            <i class="fa-solid fa-chevron-down acciones-perfiles-depto-card__chevron"></i>
                                        </div>
                                    </summary>

                                    <div class="acciones-perfiles-depto-card__body">
                                        <div class="acciones-perfiles-pantallas">
                                            @foreach ($vistas as $vista => $acciones)
                                                @php($vistaId = 'vista' . md5($departamento . $vista))
                                                <details class="acciones-perfiles-vista" wire:key="vista-{{ $vistaId }}">
                                                    <summary class="acciones-perfiles-vista__summary">
                                                        <span class="acciones-perfiles-vista__summary-main">
                                                            <i class="fa-solid fa-desktop"></i>
                                                            <span class="acciones-perfiles-vista__name">{{ $vista }}</span>
                                                        </span>
                                                        <span class="acciones-perfiles-vista__summary-meta">
                                                            <span class="acciones-perfiles-count">
                                                                {{ $acciones->count() }} {{ $acciones->count() === 1 ? 'acción' : 'acciones' }}
                                                            </span>
                                                            <i class="fa-solid fa-chevron-down acciones-perfiles-vista__chevron"></i>
                                                        </span>
                                                    </summary>
                                                    <div class="acciones-perfiles-vista__content">
                                                        <div class="acciones-perfiles-vista__list">
                                                            @foreach ($acciones as $accion)
                                                                @php($accionSeleccionada = in_array((string) $accion->id, $accionesSeleccionadas, true))
                                                                <label class="acciones-perfiles-check{{ $accionSeleccionada ? ' is-checked' : '' }}"
                                                                    for="accionPerfil{{ $accion->id }}"
                                                                    wire:key="accion-{{ $accion->id }}">
                                                                    <input class="form-check-input acciones-perfiles-check__input" type="checkbox"
                                                                        id="accionPerfil{{ $accion->id }}"
                                                                        data-depto="{{ $departamentoId }}"
                                                                        wire:model.defer="accionesSeleccionadas"
                                                                        value="{{ (string) $accion->id }}"
                                                                        onclick="syncAccionesSeleccionadas(this)">
                                                                    <span class="acciones-perfiles-check__content">
                                                                        <span class="acciones-perfiles-check__title">
                                                                            {{ $accion->descripcion_accion }}
                                                                        </span>
                                                                        <span class="acciones-perfiles-check__meta">
                                                                            {{ $accion->nombre_accion }}
                                                                        </span>
                                                                    </span>
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </details>
                                            @endforeach
                                        </div>
                                    </div>
                                </details>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-info border-0 rounded-4 mb-0">
                                    <i class="fa-solid fa-circle-info me-2"></i>No hay acciones registradas para asignar.
                                </div>
                            </div>
                        @endforelse

                        <div class="col-12 d-none" id="accionesPerfilSinResultados">
                            <div class="alert alert-light border rounded-4 mb-0 text-center text-muted">
                                <i class="fa-solid fa-magnifying-glass me-2"></i>No se encontraron departamentos con ese nombre.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    @else
        <div class="card border-0 shadow-sm p-4 bg-body rounded-5 text-center">
            <div class="text-muted">
                <i class="fa-solid fa-arrow-up-wide-short fs-2 mb-3 d-block"></i>
                Selecciona un perfil para mostrar sus acciones.
            </div>
        </div>
    @endif

    <script>
        function filtrarDepartamentosAcciones(termino) {
            const query = (termino || '').trim().toLowerCase();
            const cards = Array.from(document.querySelectorAll('.acciones-perfiles-depto-col'));
            let visibles = 0;

            cards.forEach(function(card) {
                const nombre = (card.getAttribute('data-departamento') || '').toLowerCase();
                const coincide = !query || nombre.includes(query);
                card.classList.toggle('d-none', !coincide);
                if (coincide) {
                    visibles += 1;
                }
            });

            const vacio = document.getElementById('accionesPerfilSinResultados');
            if (vacio) {
                vacio.classList.toggle('d-none', visibles > 0 || cards.length === 0);
            }
        }

        function toggleAccionesDepartamento(departamentoId, source) {
            const checkboxes = Array.from(
                document.querySelectorAll('.acciones-perfiles-check__input[data-depto="' + departamentoId + '"]')
            );

            checkboxes.forEach(function(checkbox) {
                checkbox.checked = source.checked;
                checkbox.dispatchEvent(new Event('input', { bubbles: true }));
                checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            });

            syncAccionesSeleccionadas();
        }

        function toggleTodasAccionesPerfil(source) {
            const checkboxes = Array.from(document.querySelectorAll('.acciones-perfiles-check__input'));

            checkboxes.forEach(function(checkbox) {
                checkbox.checked = source.checked;
                checkbox.dispatchEvent(new Event('input', { bubbles: true }));
                checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            });

            syncAccionesSeleccionadas();
        }

        function syncTogglesDepartamento() {
            document.querySelectorAll('[data-depto-toggle]').forEach(function(toggle) {
                const departamentoId = toggle.getAttribute('data-depto-toggle');
                const checkboxes = Array.from(
                    document.querySelectorAll('.acciones-perfiles-check__input[data-depto="' + departamentoId + '"]')
                );

                if (!checkboxes.length) {
                    toggle.checked = false;
                    toggle.indeterminate = false;
                    return;
                }

                const seleccionadas = checkboxes.filter(function(checkbox) {
                    return checkbox.checked;
                }).length;

                toggle.checked = seleccionadas === checkboxes.length;
                toggle.indeterminate = seleccionadas > 0 && seleccionadas < checkboxes.length;
            });
        }

        function syncAccionesSeleccionadas(source) {
            const selectAll = document.getElementById('seleccionarTodasAcciones');
            const counter = document.getElementById('accionesSeleccionadasTotal');
            const checkboxes = Array.from(document.querySelectorAll('.acciones-perfiles-check__input'));

            if (source) {
                const label = source.closest('.acciones-perfiles-check');
                if (label) {
                    label.classList.toggle('is-checked', source.checked);
                }
            }

            const seleccionadas = checkboxes.filter(function(checkbox) {
                const label = checkbox.closest('.acciones-perfiles-check');
                if (label) {
                    label.classList.toggle('is-checked', checkbox.checked);
                }
                return checkbox.checked;
            }).length;

            if (counter) {
                counter.textContent = seleccionadas;
            }

            if (selectAll && checkboxes.length > 0) {
                selectAll.checked = seleccionadas === checkboxes.length;
                selectAll.indeterminate = seleccionadas > 0 && seleccionadas < checkboxes.length;
            }

            syncTogglesDepartamento();
        }

        document.addEventListener('livewire:load', function() {
            syncAccionesSeleccionadas();

            if (typeof Livewire !== 'undefined') {
                Livewire.hook('message.processed', function() {
                    syncAccionesSeleccionadas();
                    const buscador = document.getElementById('buscarDepartamento');
                    if (buscador) {
                        filtrarDepartamentosAcciones(buscador.value);
                    }
                });
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            syncAccionesSeleccionadas();
        });
    </script>
</div>
