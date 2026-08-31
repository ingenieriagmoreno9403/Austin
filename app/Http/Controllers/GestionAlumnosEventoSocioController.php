<?php

namespace App\Http\Controllers;

use App\Models\EventoSocio;
use App\Models\EventoSocioEncargado;
use App\Models\Socio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GestionAlumnosEventoSocioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $query = EventoSocio::query()
            ->with(['encargados.socio:id,numero_socio,numero_dependiente,nombre,ap_paterno,ap_materno'])
            ->orderByDesc('id');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('nombre_evento', 'like', "%{$q}%")
                    ->orWhere('tipo_evento', 'like', "%{$q}%")
                    ->orWhere('descripcion', 'like', "%{$q}%");
            });
        }

        return response()->json(['data' => $query->limit(200)->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre_evento' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
            'fecha_evento' => ['nullable', 'date'],
            'tipo_evento' => ['nullable', 'string', 'max:120'],
            'lugar_evento' => ['required', 'string', 'max:200'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i'],
            'monto_estimado' => ['nullable', 'numeric', 'min:0'],
            'encargados' => ['required', 'array', 'min:1'],
            'encargados.*.id_socio' => ['required', 'integer', 'exists:tblsocios,id'],
            'encargados.*.monto_aportacion' => ['required', 'numeric', 'min:0'],
            'encargados.*.rol_encargado' => ['nullable', 'string', 'max:120'],
        ]);

        $encargados = collect($validated['encargados'])->values();
        $idsUnicos = $encargados->pluck('id_socio')->unique();
        if ($idsUnicos->count() !== $encargados->count()) {
            return response()->json(['message' => 'No repitas socios en encargados.'], 422);
        }
        if (!empty($validated['hora_inicio']) && !empty($validated['hora_fin']) && $validated['hora_fin'] <= $validated['hora_inicio']) {
            return response()->json(['message' => 'La hora fin debe ser mayor que la hora inicio.'], 422);
        }

        $usuario = optional(auth()->user())->name;
        $montoComprometido = (float) $encargados->sum(function ($e) {
            return (float) ($e['monto_aportacion'] ?? 0);
        });

        $evento = DB::transaction(function () use ($validated, $encargados, $usuario, $montoComprometido) {
            $evento = EventoSocio::create([
                'nombre_evento' => $validated['nombre_evento'],
                'descripcion' => $validated['descripcion'] ?? null,
                'fecha_evento' => $validated['fecha_evento'] ?? null,
                'tipo_evento' => $validated['tipo_evento'] ?? null,
                'lugar_evento' => $validated['lugar_evento'] ?? null,
                'hora_inicio' => $validated['hora_inicio'] ?? null,
                'hora_fin' => $validated['hora_fin'] ?? null,
                'monto_estimado' => (float) ($validated['monto_estimado'] ?? 0),
                'monto_comprometido' => $montoComprometido,
                'status' => 'activo',
                'created_by' => $usuario,
                'updated_by' => $usuario,
            ]);

            foreach ($encargados as $encargado) {
                EventoSocioEncargado::create([
                    'id_evento' => $evento->id,
                    'id_socio' => (int) $encargado['id_socio'],
                    'monto_aportacion' => (float) $encargado['monto_aportacion'],
                    'rol_encargado' => $encargado['rol_encargado'] ?? null,
                    'created_by' => $usuario,
                    'updated_by' => $usuario,
                ]);
            }

            return $evento;
        });

        return response()->json([
            'message' => 'Evento registrado correctamente.',
            'data' => $evento->load(['encargados.socio:id,numero_socio,numero_dependiente,nombre,ap_paterno,ap_materno']),
        ], 201);
    }

    public function catalogoEncargados(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $query = Socio::query()->orderBy('nombre');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('numero_socio', 'like', "%{$q}%")
                    ->orWhere('nombre', 'like', "%{$q}%")
                    ->orWhere('ap_paterno', 'like', "%{$q}%")
                    ->orWhere('ap_materno', 'like', "%{$q}%");
            });
        }

        $data = $query->limit(100)->get(['id', 'numero_socio', 'numero_dependiente', 'nombre', 'ap_paterno', 'ap_materno']);

        return response()->json(['data' => $data]);
    }

    public function show(int $id): JsonResponse
    {
        $data = EventoSocio::query()
            ->with(['encargados.socio:id,numero_socio,numero_dependiente,nombre,ap_paterno,ap_materno'])
            ->findOrFail($id);

        return response()->json(['data' => $data]);
    }
}
