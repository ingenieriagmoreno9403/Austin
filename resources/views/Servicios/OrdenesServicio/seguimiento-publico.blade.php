<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Seguimiento de Orden - ControlAT</title>
    <link href="{{ asset('bootstrap-5.3.3/css/bootstrap.min.css') }}" rel="stylesheet">
    <style>
        body {
            background: #f5f8fc;
            color: #1f2937;
        }
        .track-wrap {
            max-width: 1100px;
            margin: 24px auto;
            padding: 0 12px;
        }
        .track-card {
            border: 1px solid #dbeafe;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }
        .track-head {
            background: linear-gradient(145deg, #4fc3ff 0%, #38b6ff 45%, #2a9fe8 100%);
            color: #fff;
            border-radius: 14px 14px 0 0;
            padding: 16px 20px;
        }
        .status-chip {
            border-radius: 999px;
            padding: .35rem .65rem;
            font-weight: 700;
            font-size: .78rem;
            border: 1px solid transparent;
            display: inline-block;
        }
        .status-normal { background: #dbeafe; color: #1e3a8a; border-color: #bfdbfe; }
        .status-alerta { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
        .status-espera { background: #fef3c7; color: #854d0e; border-color: #fde68a; }
        .status-ok { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
        .status-otro { background: #e5e7eb; color: #374151; border-color: #d1d5db; }
        .foto-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .foto-thumb {
            width: 70px;
            height: 70px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #dbeafe;
        }
    </style>
</head>
<body>
    @php
        $estado = (string) ($orden->estado ?? '');
        $estadoUp = strtoupper(trim($estado));
        $chipEstado = 'status-otro';
        if ($estadoUp === 'FINALIZADO') {
            $chipEstado = 'status-ok';
        } elseif ($estadoUp === 'EN PROCESO' || $estadoUp === 'PROCESO') {
            $chipEstado = 'status-normal';
        } elseif ($estadoUp === 'EN ESPERA') {
            $chipEstado = 'status-espera';
        }
    @endphp

    <div class="track-wrap">
        <div class="track-card">
            <div class="track-head">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                    <div>
                        <h4 class="m-0 fw-bold">Seguimiento de Orden</h4>
                        <div class="small">ControlAT - Consulta de avance de servicio</div>
                    </div>
                    <div class="text-md-end">
                        <div><strong>Orden:</strong> {{ $orden->no_orden ?? '-' }}</div>
                        <div><strong>Estado:</strong> <span class="status-chip {{ $chipEstado }}">{{ $orden->estado ?? 'Sin estado' }}</span></div>
                    </div>
                </div>
            </div>
            <div class="p-3 p-md-4">
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-3">
                        <div class="border rounded p-2 h-100 bg-light">
                            <div class="text-secondary small">Vehiculo</div>
                            <div class="fw-semibold">{{ $orden->tipo_vehiculo ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="border rounded p-2 h-100 bg-light">
                            <div class="text-secondary small">Torre</div>
                            <div class="fw-semibold">{{ $orden->codigo_torre ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="border rounded p-2 h-100 bg-light">
                            <div class="text-secondary small">Fecha/Hora</div>
                            <div class="fw-semibold">{{ $orden->fecha_hora ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="border rounded p-2 h-100 bg-light">
                            <div class="text-secondary small">Fecha Promesa</div>
                            <div class="fw-semibold">{{ $orden->fecha_promesa ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th class="text-center">No.S</th>
                                <th>Servicio</th>
                                <th>Descripcion</th>
                                <th class="text-center">Proceso</th>
                                <th>Empleado</th>
                                <th>Incidencia</th>
                                <th>Fotos avance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($detalles as $d)
                                @php
                                    $proc = strtoupper(trim((string) ($d->proceso_estado ?? '')));
                                    $chip = 'status-otro';
                                    if ($proc === 'PROCESO A PUNTO DE ENTREGAR') $chip = 'status-ok';
                                    elseif ($proc === 'PROCESO EXPIRADO X TECNICO') $chip = 'status-alerta';
                                    elseif (in_array($proc, ['PROCESO NORMAL', 'EN PROCESO', 'PROCESO'], true)) $chip = 'status-normal';
                                    elseif (in_array($proc, ['PENDIENTE X AUTORIZAR', 'PENDIENTE X REFACCIONES', 'X REFACCIONES, YA SURTIDO', 'PENDIENTE (OTRO)'], true)) $chip = 'status-espera';
                                @endphp
                                <tr>
                                    <td class="text-center fw-semibold">{{ $d->no_s }}</td>
                                    <td>{{ $d->servicio }}</td>
                                    <td>{{ $d->descripcion }}</td>
                                    <td class="text-center"><span class="status-chip {{ $chip }}">{{ $d->proceso_estado ?? 'Sin proceso' }}</span></td>
                                    <td>{{ trim((string) ($d->empleado_nombre ?? '')) !== '' ? $d->empleado_nombre : '-' }}</td>
                                    <td>{{ $d->nombre_incidencia ?? '-' }}</td>
                                    <td>
                                        @if(!empty($d->fotos) && count($d->fotos) > 0)
                                            <div class="foto-grid">
                                                @foreach($d->fotos as $f)
                                                    <a href="{{ $f->foto_url }}" target="_blank">
                                                        <img src="{{ $f->foto_url }}" alt="foto avance" class="foto-thumb">
                                                    </a>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-secondary">Sin fotos</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-secondary">No hay detalles registrados para esta orden.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
