<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Secuencia de carga {{ $ruta->folio }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1a1a1a; margin: 18px; }
        h1 { font-size: 18px; margin: 0 0 4px; color: #0b3d5c; }
        h2 { font-size: 13px; margin: 18px 0 8px; color: #0b3d5c; border-bottom: 1px solid #c5d5e0; padding-bottom: 4px; }
        .meta { color: #555; margin-bottom: 12px; line-height: 1.45; }
        .nota { background: #eef5fa; border: 1px solid #c5d5e0; padding: 8px 10px; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th, td { border: 1px solid #b8c7d4; padding: 6px 7px; vertical-align: top; }
        th { background: #0b3d5c; color: #fff; font-size: 10px; text-align: left; }
        td.num { width: 36px; text-align: center; font-weight: bold; background: #f4f7fa; }
        .muted { color: #666; font-size: 10px; }
        .pie { margin-top: 22px; font-size: 9px; color: #777; text-align: center; }
        .cols { width: 100%; }
        .cols td { width: 50%; border: none; vertical-align: top; padding: 0 6px 0 0; }
        .cols td + td { padding: 0 0 0 6px; }
        .box { border: 1px solid #b8c7d4; padding: 8px; min-height: 80px; }
        .box ol { margin: 0; padding-left: 18px; }
        .box li { margin-bottom: 5px; }
    </style>
</head>
<body>
    <h1>Secuencia de carga — {{ $ruta->folio }}</h1>
    <div class="meta">
        Fecha ruta: {{ optional($ruta->fecha)->format('d/m/Y') ?: '—' }}
        · Estatus: {{ $ruta->estatusTexto }}
        @if ($ruta->camion)
            · Camión: {{ $ruta->camion->etiqueta_con_capacidad }}
        @elseif ($ruta->unidad)
            · Unidad: {{ $ruta->unidad }}
        @endif
        @if ($ruta->chofer_nombre) · Chofer: {{ $ruta->chofer_nombre }} @endif
        <br>
        Impreso: {{ now()->format('d/m/Y H:i') }}
        @if ($ruta->creadoPor) · Armó: {{ $ruta->creadoPor->name ?? $ruta->creadoPor->email }} @endif
    </div>

    @php $capPdf = $ruta->resumenCapacidad(); @endphp
    <div class="nota">
        <strong>Capacidad:</strong> {{ $capPdf['mensaje'] }}
        <br>
        <strong>Regla de ruta:</strong> entrega de cerca a lejos (el chofer baja conforme avanza).
        Carga al revés: lo de la última parada va al fondo del camión (sale al final).
    </div>

    <h2>1) Cómo cargar el camión (orden de subida)</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Cliente / pedido</th>
                <th>OP / producción</th>
                <th>Destino</th>
                <th>Entrega</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($porCarga as $carga)
                @php
                    $op = $carga->ordenProduccion;
                    $destino = $carga->destino_texto
                        ?: ($carga->pedido?->cliente?->direccionCompleta() ?: '—');
                @endphp
                <tr>
                    <td class="num">{{ $carga->orden_carga }}</td>
                    <td>
                        <strong>{{ $carga->pedido->cliente->nombre ?? '—' }}</strong><br>
                        {{ $carga->pedido->folio ?? ('Ped #' . $carga->pedido_id) }}
                        <span class="muted"> · {{ $carga->folio }}</span>
                    </td>
                    <td>
                        @if ($op)
                            OP {{ $op->folio }}<br>
                            {{ number_format($carga->metrosCarga(), 1) }} m · {{ (int) $op->total_piezas_producidas }} pzas
                            @if ($op->nave_destino)<br><span class="muted">{{ $op->nave_destino }}</span>@endif
                        @else
                            {{ number_format($carga->metrosCarga(), 1) }} m · Existencia / sin OP
                        @endif
                    </td>
                    <td>{{ $destino }}</td>
                    <td>
                        Parada #{{ $carga->orden_entrega }}
                        @if ($carga->distancia_km !== null)
                            <br><span class="muted">{{ number_format((float) $carga->distancia_km, 1) }} km</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="muted">#1 = primero al camión (fondo). El último número sale primero en la primera parada.</p>

    <h2>2) Cómo entregar (orden de paradas)</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Cliente / pedido</th>
                <th>Destino</th>
                <th>Km</th>
                <th>Carga #</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($porEntrega as $carga)
                @php
                    $destino = $carga->destino_texto
                        ?: ($carga->pedido?->cliente?->direccionCompleta() ?: '—');
                @endphp
                <tr>
                    <td class="num">{{ $carga->orden_entrega }}</td>
                    <td>
                        <strong>{{ $carga->pedido->cliente->nombre ?? '—' }}</strong><br>
                        {{ $carga->pedido->folio ?? ('Ped #' . $carga->pedido_id) }}
                    </td>
                    <td>{{ $destino }}</td>
                    <td>{{ $carga->distancia_km !== null ? number_format((float) $carga->distancia_km, 1) : '—' }}</td>
                    <td>{{ $carga->orden_carga }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="cols" style="margin-top:16px">
        <tr>
            <td>
                <div class="box">
                    <strong>Resumen carga</strong>
                    <ol>
                        @foreach ($porCarga as $carga)
                            <li>#{{ $carga->orden_carga }} {{ $carga->pedido->cliente->nombre ?? '—' }}</li>
                        @endforeach
                    </ol>
                </div>
            </td>
            <td>
                <div class="box">
                    <strong>Resumen entrega</strong>
                    <ol>
                        @foreach ($porEntrega as $carga)
                            <li>#{{ $carga->orden_entrega }} {{ $carga->pedido->cliente->nombre ?? '—' }}</li>
                        @endforeach
                    </ol>
                </div>
            </td>
        </tr>
    </table>

    @if ($ruta->observaciones)
        <p style="margin-top:14px"><strong>Observaciones:</strong> {{ $ruta->observaciones }}</p>
    @endif

    <div class="pie">IOHISA · Planeación de cargas · {{ $ruta->folio }}</div>
</body>
</html>
