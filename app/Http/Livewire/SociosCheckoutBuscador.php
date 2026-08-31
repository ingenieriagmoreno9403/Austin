<?php

namespace App\Http\Livewire;

use App\Models\PagoSocioDet;
use App\Models\PagoSocioEnc;
use App\Models\Socio;
use App\Models\SocioCheckoutDet;
use App\Models\SocioCheckoutEnc;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class SociosCheckoutBuscador extends Component
{
    public $busqueda = '';

    public function getResultadosProperty()
    {
        $q = trim((string) $this->busqueda);

        if ($q === '') {
            return collect();
        }

        return Socio::query()
            ->where(function ($query) use ($q) {
                $query->where('numero_socio', 'like', "%{$q}%")
                    ->orWhere('nombre', 'like', "%{$q}%")
                    ->orWhere('segund_nom', 'like', "%{$q}%")
                    ->orWhere('ap_paterno', 'like', "%{$q}%")
                    ->orWhere('ap_materno', 'like', "%{$q}%");
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(function (Socio $socio) {
                $estado = $this->estadoPagoSocio((int) $socio->id);

                return [
                    'id' => $socio->id,
                    'numero_socio' => $socio->numero_socio,
                    'numero_dependiente' => $socio->numero_dependiente,
                    'nombre_completo' => $this->nombreCompleto($socio),
                    'status' => $socio->status,
                    'pago_al_corriente' => $estado['al_corriente'],
                    'motivo_pago' => $estado['motivo'],
                ];
            });
    }

    public function seleccionarSocio(int $idSocio): void
    {
        $socio = Socio::find($idSocio);

        if (!$socio) {
            $this->busqueda = '';
            $this->dispatchBrowserEvent('checkout-socio-alert', [
                'icon' => 'error',
                'title' => 'Socio no encontrado',
                'message' => 'No se pudo localizar el socio seleccionado.',
                'reloadPage' => true,
            ]);
            return;
        }

        $estado = $this->estadoPagoSocio((int) $socio->id);

        if (!$estado['al_corriente']) {
            $this->busqueda = '';
            $this->dispatchBrowserEvent('checkout-socio-alert', [
                'icon' => 'warning',
                'title' => 'Acceso no permitido',
                'message' => 'El socio ' . $this->nombreCompleto($socio) . ' tiene atraso en pagos y no tiene acceso. ' . $estado['motivo'],
                'reloadPage' => true,
            ]);
            return;
        }

        $usuario = optional(auth()->user())->name;

        DB::transaction(function () use ($socio, $estado, $usuario) {
            $encabezado = SocioCheckoutEnc::create([
                'id_socio' => $socio->id,
                'fecha_hora_entrada' => now(),
                'pago_al_corriente' => true,
                'resultado' => 'permitido',
                'motivo' => $estado['motivo'],
                'created_by' => $usuario,
                'updated_by' => $usuario,
            ]);

            SocioCheckoutDet::create([
                'id_checkout_enc' => $encabezado->id,
                'tipo_evento' => 'entrada',
                'fecha_hora_evento' => now(),
                'observaciones' => null,
                'created_by' => $usuario,
                'updated_by' => $usuario,
            ]);
        });

        $this->busqueda = '';
        $this->dispatchBrowserEvent('checkout-socio-alert', [
            'icon' => 'success',
            'title' => 'Entrada registrada',
            'message' => 'Entrada marcada correctamente para ' . $this->nombreCompleto($socio) . '.',
            'reloadPage' => true,
        ]);
    }

    public function render()
    {
        return view('livewire.socios-checkout-buscador', [
            'resultados' => $this->resultados,
        ]);
    }

    private function estadoPagoSocio(int $idSocio): array
    {
        $plan = PagoSocioEnc::query()
            ->where('id_socio', $idSocio)
            ->orderByDesc('id')
            ->first();

        if (!$plan) {
            return ['al_corriente' => false, 'motivo' => 'El socio no tiene plan de pagos.'];
        }

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

    private function nombreCompleto(Socio $socio): string
    {
        return trim(preg_replace('/\s+/', ' ', implode(' ', [
            $socio->nombre,
            $socio->segund_nom,
            $socio->ap_paterno,
            $socio->ap_materno,
        ])));
    }
}
