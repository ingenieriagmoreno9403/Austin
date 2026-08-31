@php
    $horariosEmpleado = $horariosEmpleado ?? collect();
    $horariosDefaults = $horariosDefaults ?? [];
@endphp

<div class="mb-2 pb-4 border-bottom">
    <h6 class="empleado-subsection-title">Horario por día de la semana</h6>
    <p class="text-muted fs-8 mb-3">Selecciona el horario que aplicará para cada día del empleado.</p>

    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0 empleado-horarios-table text-center">
            <thead>
                <tr>
                    @foreach ($vardias as $dia)
                        <th class="text-uppercase fs-8 px-2">{{ $dia->dias }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    @foreach ($vardias as $dia)
                        @php
                            $registro = $horariosEmpleado->get((int) $dia->id);
                            $selectedHorario = $registro->id_horario ?? ($horariosDefaults[$dia->id] ?? '');
                        @endphp
                        <td class="px-2" style="min-width: 140px;">
                            @if (!empty($registro) && !empty($registro->id))
                                <input type="hidden" name="horarios_registro[{{ $dia->id }}]" value="{{ $registro->id }}">
                            @endif
                            <select name="horarios[{{ $dia->id }}]" class="form-select form-select-sm" required>
                                <option value="">Seleccionar...</option>
                                @foreach ($varhorarios as $horario)
                                    <option value="{{ $horario->id }}" {{ (string) $selectedHorario === (string) $horario->id ? 'selected' : '' }}>
                                        {{ $horario->tipo }}
                                        ({{ substr((string) $horario->entrada, 0, 5) }} - {{ substr((string) $horario->salida, 0, 5) }})
                                    </option>
                                @endforeach
                            </select>
                        </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
</div>
