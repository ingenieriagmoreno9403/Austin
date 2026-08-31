<div class="ent-form">
    @if($modoSoloVista)
        <div class="bg-body shadow-sm rounded-3 p-4 mb-4 oc-general">
            <div class="oc-general__header">
                <h4 class="mb-0"><b class="fs-3 text-orange">1. </b> Datos de la recepción</h4>
            </div>

            <div class="oc-general__grid">
                <div class="oc-general__card">
                    <h5 class="oc-general__card-title">
                        <i class="fa-solid fa-clipboard-list"></i>
                        Información general
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="oc-general__field">
                                <span class="oc-general__label"><i class="fa-solid fa-calendar-day"></i> Fecha de recepción</span>
                                <div class="ent-form__readonly">{{ $fechaRecepcion ? \Carbon\Carbon::parse($fechaRecepcion)->format('d/m/Y') : '—' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="oc-general__field">
                                <span class="oc-general__label"><i class="fa-solid fa-comment"></i> Observaciones</span>
                                <div class="ent-form__readonly">{{ $observaciones ?: 'Sin observaciones' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="oc-general__card">
                    <h5 class="oc-general__card-title">
                        <i class="fa-solid fa-warehouse"></i>
                        Destino en inventario
                    </h5>
                    <div class="oc-general__field">
                        <span class="oc-general__label"><i class="fa-solid fa-building"></i> Almacén</span>
                        <div class="ent-form__readonly">{{ optional($entrada->almacen)->folio_interno ?? 'No especificado' }}</div>
                    </div>
                    <div class="oc-general__field">
                        <span class="oc-general__label"><i class="fa-solid fa-map-marker-alt"></i> Ubicación</span>
                        <div class="ent-form__readonly">
                            @php $ubicacionEntrada = optional($entrada->ubicacion); @endphp
                            @if($ubicacionEntrada)
                                {{ $ubicacionEntrada->folio_interno }}{{ $ubicacionEntrada->descripcion ? ' — '.$ubicacionEntrada->descripcion : '' }}
                            @else
                                No especificado
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-body shadow-sm rounded-3 p-4 mb-4 oc-general">
            <div class="oc-general__header">
                <h4 class="mb-0"><b class="fs-3 text-orange">2. </b> Productos recibidos</h4>
            </div>

            <div class="table-responsive ent-form__table-wrap">
                <table class="table table-hover align-middle mb-0 ent-form__table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-center">Pendiente</th>
                            <th class="text-center">Recibida</th>
                            <th>Comentario</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($filas as $fila)
                            @php
                                $claseFila = '';
                                if ($fila['cantidad_recibida'] >= $fila['cantidad_pendiente'] && $fila['cantidad_pendiente'] > 0) {
                                    $claseFila = 'table-success';
                                } elseif ($fila['cantidad_recibida'] > 0) {
                                    $claseFila = 'table-warning';
                                }
                            @endphp
                            <tr class="{{ $claseFila }}">
                                <td class="fw-semibold">{{ $fila['nombre_producto'] }}</td>
                                <td class="text-center">{{ number_format($fila['cantidad_pendiente'], 2) }}</td>
                                <td class="text-center">{{ number_format($fila['cantidad_recibida'], 2) }}</td>
                                <td class="text-muted">{{ $fila['comentario'] ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No hay productos en esta recepción.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @php
                $totalCompletos = collect($filas)->filter(fn($fila) => $fila['cantidad_recibida'] >= $fila['cantidad_pendiente'])->count();
                $totalPendientes = collect($filas)->count() - $totalCompletos;
            @endphp
            <div class="ent-form__summary mt-3">
                <i class="fa-solid fa-circle-info me-1"></i>
                <strong>{{ $totalCompletos }}</strong> completo(s) ·
                <strong>{{ $totalPendientes }}</strong> pendiente(s)
            </div>
        </div>

        <div class="d-flex justify-content-end">
            <a href="{{ route('Entradas.index') }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Volver
            </a>
        </div>
    @else
        <form wire:submit.prevent="guardar" class="form">
            <div class="bg-body shadow-sm rounded-3 p-4 mb-4 oc-general">
                <div class="oc-general__header">
                    <h4 class="mb-0"><b class="fs-3 text-orange">1. </b> Datos de la recepción</h4>
                </div>

                <div class="oc-general__grid">
                    <div class="oc-general__stack">
                        <div class="oc-general__card">
                            <h5 class="oc-general__card-title">
                                <i class="fa-solid fa-clipboard-list"></i>
                                Información general
                            </h5>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="oc-general__field">
                                        <label for="fechaRecepcion" class="oc-general__label">
                                            <i class="fa-solid fa-calendar-day"></i> Fecha de recepción <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" wire:model="fechaRecepcion" id="fechaRecepcion" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="oc-general__field">
                                        <label for="observaciones" class="oc-general__label">
                                            <i class="fa-solid fa-comment"></i> Observaciones
                                        </label>
                                        <textarea wire:model="observaciones" id="observaciones" class="form-control" rows="2" placeholder="Notas opcionales de la recepción"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="oc-general__stack">
                        <div class="oc-general__card">
                            <h5 class="oc-general__card-title">
                                <i class="fa-solid fa-warehouse"></i>
                                Destino en inventario
                            </h5>

                            <div class="oc-general__field">
                                <label for="id_almacen" class="oc-general__label">
                                    <i class="fa-solid fa-building"></i> Almacén <span class="text-danger">*</span>
                                </label>
                                <select wire:model="id_almacen" id="id_almacen" class="form-select" required>
                                    <option value="">Seleccione un almacén</option>
                                    @foreach($this->almacenes as $almacen)
                                        <option value="{{ $almacen->id }}">{{ $almacen->folio_interno }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="oc-general__field">
                                <label for="id_ubicacion" class="oc-general__label">
                                    <i class="fa-solid fa-map-marker-alt"></i> Ubicación <span class="text-danger">*</span>
                                </label>
                                <select wire:model="id_ubicacion" id="id_ubicacion" class="form-select" required {{ empty($id_almacen) ? 'disabled' : '' }}>
                                    <option value="">Seleccione una ubicación</option>
                                    @foreach($this->ubicaciones as $ubicacion)
                                        <option value="{{ $ubicacion->id }}">
                                            {{ $ubicacion->folio_interno }}{{ $ubicacion->descripcion ? ' — '.$ubicacion->descripcion : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="oc-general__hint">El folio interno aparece antes de la descripción.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-body shadow-sm rounded-3 p-4 mb-4 oc-general">
                <div class="oc-general__header">
                    <h4 class="mb-0"><b class="fs-3 text-orange">2. </b> Productos a recepcionar</h4>
                    <span class="badge bg-secondary-subtle text-secondary border">{{ count($filas) }} producto(s)</span>
                </div>

                <div class="table-responsive ent-form__table-wrap">
                    <table class="table table-hover align-middle mb-0 ent-form__table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="text-center" style="width: 120px;">Pendiente</th>
                                <th class="text-center" style="width: 150px;">Recibida</th>
                                <th style="width: 280px;">Comentario</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($filas as $index => $fila)
                                @php
                                    $claseFila = '';
                                    if ($fila['cantidad_recibida'] >= $fila['cantidad_pendiente'] && $fila['cantidad_pendiente'] > 0) {
                                        $claseFila = 'table-success';
                                    } elseif ($fila['cantidad_recibida'] > 0) {
                                        $claseFila = 'table-warning';
                                    }
                                @endphp
                                <tr class="{{ $claseFila }}">
                                    <td class="fw-semibold">{{ $fila['nombre_producto'] }}</td>
                                    <td class="text-center">{{ number_format($fila['cantidad_pendiente'], 2) }}</td>
                                    <td>
                                        <input type="number"
                                            min="0"
                                            max="{{ $fila['cantidad_pendiente'] }}"
                                            step="0.01"
                                                    wire:model="filas.{{ $index }}.cantidad_recibida"
                                            class="form-control text-end"
                                            placeholder="0.00">
                                    </td>
                                    <td>
                                        <input type="text"
                                            wire:model="filas.{{ $index }}.comentario"
                                            class="form-control"
                                            placeholder="Observación del producto...">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        No hay productos pendientes por recibir en esta orden.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @php
                    $totalCompletos = collect($filas)->filter(fn($fila) => $fila['cantidad_recibida'] >= $fila['cantidad_pendiente'])->count();
                    $totalPendientes = collect($filas)->count() - $totalCompletos;
                    $porcentajeCompletado = collect($filas)->count() > 0
                        ? round(($totalCompletos / collect($filas)->count()) * 100)
                        : 0;
                @endphp

                <div class="ent-form__progress mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="ent-form__progress-label">Progreso de recepción</span>
                        <span class="fw-bold text-marino">{{ $porcentajeCompletado }}%</span>
                    </div>
                    <div class="progress ent-form__progress-bar">
                        <div class="progress-bar @if($porcentajeCompletado >= 100) bg-success @elseif($porcentajeCompletado > 0) bg-info @else bg-secondary @endif"
                             role="progressbar"
                             style="width: {{ $porcentajeCompletado }}%"></div>
                    </div>
                    <div class="ent-form__summary mt-2">
                        <i class="fa-solid fa-circle-check text-success me-1"></i> {{ $totalCompletos }} completo(s)
                        <span class="mx-2 text-muted">·</span>
                        <i class="fa-solid fa-circle-exclamation text-warning me-1"></i> {{ $totalPendientes }} pendiente(s)
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary" id="btnRegresar">
                    <i class="fa-solid fa-arrow-left me-1"></i> Cancelar
                </button>
                <button type="submit" class="btn btn-baseColor" @if(count($filas) === 0) disabled @endif>
                    <i class="fa-solid fa-save me-1"></i> Guardar recepción
                </button>
            </div>
        </form>
    @endif

    @push('scripts')
    <script>
        const entradasIndexUrl = @json(route('Entradas.index'));

        Livewire.on('propertyChanged', () => {});

        document.getElementById('btnRegresar')?.addEventListener('click', function() {
            const salir = () => @this.call('confirmarSalida', entradasIndexUrl);

            if (@this.hasChanges) {
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: 'Si regresas perderás los cambios realizados',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, regresar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        salir();
                    }
                });
            } else {
                salir();
            }
        });

        if (!@this.modoSoloVista) {
            document.addEventListener('click', function (e) {
                const link = e.target.closest('a');
                if (!link) return;

                const href = link.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
                if (link.target === '_blank') return;

                e.preventDefault();
                const targetHref = href;

                if (@this.hasChanges) {
                    Swal.fire({
                        title: '¿Estás seguro?',
                        text: 'Si sales perderás los cambios no guardados',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Sí, salir',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            @this.call('confirmarSalida', targetHref);
                        }
                    });
                } else {
                    @this.call('confirmarSalida', targetHref);
                }
            });
        }

        Livewire.on('errorEvent', message => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#d33'
            });
        });

        Livewire.on('successEvent', message => {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: message,
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#198754'
            });
        });

        Livewire.on('warningEvent', message => {
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: message,
                confirmButtonText: 'Entendido'
            });
        });
    </script>
    @endpush
</div>
