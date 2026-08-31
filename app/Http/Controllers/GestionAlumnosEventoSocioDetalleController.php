<?php

namespace App\Http\Controllers;

use App\Models\EventoSocio;
use App\Models\EventoSocioDetalle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GestionAlumnosEventoSocioDetalleController extends Controller
{
    public function index(int $idEvento): JsonResponse
    {
        EventoSocio::query()->findOrFail($idEvento);
        $data = EventoSocioDetalle::query()
            ->where('id_evento', $idEvento)
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $data]);
    }

    public function store(Request $request, int $idEvento): JsonResponse
    {
        EventoSocio::query()->findOrFail($idEvento);
        $validated = $request->validate([
            'concepto' => ['required', 'string', 'max:200'],
            'categoria' => ['nullable', 'string', 'max:120'],
            'cantidad' => ['required', 'numeric', 'min:0.01'],
            'costo_unitario' => ['required', 'numeric', 'min:0'],
            'empresa_proveedor' => ['nullable', 'string', 'max:200'],
            'lugar_entrega' => ['required', 'string', 'max:200'],
            'fecha_recibido' => ['required', 'date'],
            'hora_recibido' => ['required', 'date_format:H:i'],
            'fecha_recogida' => ['required', 'date'],
            'hora_recogida' => ['required', 'date_format:H:i'],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ]);

        $inicio = ($validated['fecha_recibido'] ?? '') . ' ' . ($validated['hora_recibido'] ?? '');
        $fin = ($validated['fecha_recogida'] ?? '') . ' ' . ($validated['hora_recogida'] ?? '');
        if ($inicio !== ' ' && $fin !== ' ' && strtotime($fin) < strtotime($inicio)) {
            return response()->json(['message' => 'La fecha/hora de recogida debe ser mayor o igual a recibido.'], 422);
        }

        $cantidad = (float) $validated['cantidad'];
        $costoUnitario = (float) $validated['costo_unitario'];
        $costoTotal = round($cantidad * $costoUnitario, 2);
        $usuario = optional(auth()->user())->name;

        $row = EventoSocioDetalle::query()->create([
            'id_evento' => $idEvento,
            'concepto' => $validated['concepto'],
            'categoria' => $validated['categoria'] ?? null,
            'cantidad' => $cantidad,
            'costo_unitario' => $costoUnitario,
            'costo_total' => $costoTotal,
            'empresa_proveedor' => $validated['empresa_proveedor'] ?? null,
            'lugar_entrega' => $validated['lugar_entrega'] ?? null,
            'fecha_recibido' => $validated['fecha_recibido'] ?? null,
            'hora_recibido' => $validated['hora_recibido'] ?? null,
            'fecha_recogida' => $validated['fecha_recogida'] ?? null,
            'hora_recogida' => $validated['hora_recogida'] ?? null,
            'observaciones' => $validated['observaciones'] ?? null,
            'created_by' => $usuario,
            'updated_by' => $usuario,
        ]);

        return response()->json([
            'message' => 'Detalle agregado correctamente.',
            'data' => $row,
        ], 201);
    }
}
