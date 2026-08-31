<?php

namespace App\Http\Controllers;

use App\Models\PagoSocioDet;
use App\Models\PagoSocioEnc;
use App\Models\Socio;
use App\Models\SocioCheckoutDet;
use App\Models\SocioCheckoutEnc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GestionAlumnosCheckoutSocioController extends Controller
{
    public function buscar(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $query = Socio::query()->orderByDesc('id');
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('numero_socio', 'like', "%{$q}%")
                    ->orWhere('nombre', 'like', "%{$q}%")
                    ->orWhere('ap_paterno', 'like', "%{$q}%")
                    ->orWhere('ap_materno', 'like', "%{$q}%");
            });
        }

        $rows = $query->limit(50)->get()->map(function (Socio $s) {
            $estado = $this->estadoPagoSocio($s->id);
            return [
                'id' => $s->id,
                'numero_socio' => $s->numero_socio,
                'numero_dependiente' => $s->numero_dependiente,
                'nombre_completo' => trim(($s->nombre ?? '') . ' ' . ($s->segund_nom ?? '') . ' ' . ($s->ap_paterno ?? '') . ' ' . ($s->ap_materno ?? '')),
                'status' => $s->status,
                'pago_al_corriente' => $estado['al_corriente'],
                'motivo_pago' => $estado['motivo'],
            ];
        });

        return response()->json(['data' => $rows]);
    }

    public function registrarEntrada(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_socio' => ['required', 'integer', 'exists:tblsocios,id'],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ]);

        $socio = Socio::findOrFail((int) $validated['id_socio']);
        $estado = $this->estadoPagoSocio($socio->id);
        if (!$estado['al_corriente']) {
            return response()->json(['message' => $estado['motivo']], 422);
        }

        $usuario = optional(auth()->user())->name;
        $enc = DB::transaction(function () use ($socio, $estado, $usuario, $validated) {
            $enc = SocioCheckoutEnc::create([
                'id_socio' => $socio->id,
                'fecha_hora_entrada' => now(),
                'pago_al_corriente' => true,
                'resultado' => 'permitido',
                'motivo' => $estado['motivo'],
                'created_by' => $usuario,
                'updated_by' => $usuario,
            ]);

            SocioCheckoutDet::create([
                'id_checkout_enc' => $enc->id,
                'tipo_evento' => 'entrada',
                'fecha_hora_evento' => now(),
                'observaciones' => $validated['observaciones'] ?? null,
                'created_by' => $usuario,
                'updated_by' => $usuario,
            ]);

            return $enc;
        });

        return response()->json([
            'message' => 'Entrada registrada correctamente.',
            'data' => $enc->fresh(),
        ], 201);
    }

    public function historialHoy(Request $request): JsonResponse
    {
        $fecha = trim((string) $request->query('fecha', ''));
        $q = trim((string) $request->query('q', ''));

        $query = SocioCheckoutEnc::query()
            ->with('socio:id,numero_socio,numero_dependiente,nombre,ap_paterno,ap_materno')
            ->orderByDesc('id');

        if ($fecha !== '') {
            $query->whereDate('fecha_hora_entrada', $fecha);
        } else {
            $query->whereDate('fecha_hora_entrada', Carbon::today()->toDateString());
        }

        if ($q !== '') {
            $query->whereHas('socio', function ($socioQuery) use ($q) {
                $socioQuery->where('numero_socio', 'like', "%{$q}%")
                    ->orWhere('nombre', 'like', "%{$q}%")
                    ->orWhere('ap_paterno', 'like', "%{$q}%")
                    ->orWhere('ap_materno', 'like', "%{$q}%");
            });
        }

        $data = $query->limit(300)->get();

        return response()->json(['data' => $data]);
    }

    private function estadoPagoSocio(int $idSocio): array
    {
        // Si no tiene plan, no está al corriente.
        $plan = PagoSocioEnc::query()->where('id_socio', $idSocio)->orderByDesc('id')->first();
        if (!$plan) {
            return ['al_corriente' => false, 'motivo' => 'El socio no tiene plan de pagos.'];
        }

        // Consideramos no corriente si hay pendientes/vencidos con fecha ya vencida.
        $hayAtrasos = PagoSocioDet::query()
            ->where('id_pago_enc', $plan->id)
            ->whereIn('status', ['pendiente', 'vencido'])
            ->whereDate('fecha_programada', '<', Carbon::today()->toDateString())
            ->exists();

        if ($hayAtrasos) {
            return ['al_corriente' => false, 'motivo' => 'El socio tiene pagos vencidos o atrasados.'];
        }

        return ['al_corriente' => true, 'motivo' => 'Pagos al corriente.'];
    }
}
