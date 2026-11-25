<?php

namespace App\Services;

use App\Models\ReqNivel;
use App\Models\ReqRuta;
use App\Models\ReqSuplente;
use App\Models\Requisicion;
use App\Models\RequisicionAprobacion;
use App\Models\RequisicionHistorial;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class RequisicionWorkflowService
{
    public function siguienteFolio(): string
    {
        $anio = (int) date('Y');
        $prefijo = 'REQ-'.$anio.'-';
        $ultimo = Requisicion::query()
            ->where('folio', 'like', $prefijo.'%')
            ->orderByDesc('folio')
            ->value('folio');
        $n = 1;
        if ($ultimo && preg_match('/(\d+)$/', $ultimo, $m)) {
            $n = ((int) $m[1]) + 1;
        }

        return $prefijo.str_pad((string) $n, 5, '0', STR_PAD_LEFT);
    }

    public function resolverRuta(?string $departamento): ReqRuta
    {
        $departamento = trim((string) $departamento);
        if ($departamento !== '') {
            $ruta = ReqRuta::query()
                ->where('activo', 1)
                ->whereRaw('UPPER(departamento) = ?', [mb_strtoupper($departamento)])
                ->with('niveles')
                ->first();
            if ($ruta) {
                return $ruta;
            }
        }

        $ruta = ReqRuta::query()
            ->where('activo', 1)
            ->where('es_default', 1)
            ->with('niveles')
            ->first();

        if (! $ruta) {
            $ruta = ReqRuta::query()->where('activo', 1)->with('niveles')->orderBy('id')->first();
        }

        if (! $ruta || $ruta->niveles->isEmpty()) {
            throw ValidationException::withMessages([
                'ruta' => 'No hay una ruta de autorización configurada. Pide a Sistemas que asigne responsables.',
            ]);
        }

        return $ruta;
    }

    public function responsableEfectivo(ReqNivel $nivel): ?int
    {
        $hoy = now()->toDateString();
        $suplente = ReqSuplente::query()
            ->where('nivel_id', $nivel->id)
            ->where('activo', 1)
            ->where(function ($q) use ($hoy) {
                $q->whereNull('vigencia_desde')->orWhere('vigencia_desde', '<=', $hoy);
            })
            ->where(function ($q) use ($hoy) {
                $q->whereNull('vigencia_hasta')->orWhere('vigencia_hasta', '>=', $hoy);
            })
            ->orderByDesc('id')
            ->first();

        if ($suplente) {
            return (int) $suplente->user_id;
        }

        return $nivel->responsable_user_id ? (int) $nivel->responsable_user_id : null;
    }

    public function registrarHistorial(
        Requisicion $req,
        string $accion,
        ?string $antes,
        ?string $nuevo,
        ?string $comentario = null,
        ?array $meta = null,
        ?int $userId = null
    ): void {
        RequisicionHistorial::query()->create([
            'requisicion_id' => $req->id,
            'usuario_id' => $userId ?? auth()->id(),
            'accion' => $accion,
            'estado_anterior' => $antes,
            'estado_nuevo' => $nuevo,
            'comentario' => $comentario,
            'meta' => $meta,
            'created_at' => now(),
        ]);
    }

    public function enviar(Requisicion $req, int $userId): Requisicion
    {
        if ((int) $req->solicitante_id !== $userId) {
            throw ValidationException::withMessages(['envio' => 'Solo el solicitante puede enviar esta requisición.']);
        }
        if (! in_array($req->estado, ['borrador', 'devuelta'], true)) {
            throw ValidationException::withMessages(['envio' => 'La requisición no está en un estado enviable.']);
        }
        if ($req->detalles()->count() < 1) {
            throw ValidationException::withMessages(['partidas' => 'Agrega al menos una partida para enviar.']);
        }

        return DB::transaction(function () use ($req, $userId) {
            $req->refresh();
            $ruta = $this->resolverRuta($req->departamento);
            $niveles = $ruta->niveles()->orderBy('orden')->get();
            if ($niveles->isEmpty()) {
                throw ValidationException::withMessages(['ruta' => 'La ruta no tiene niveles configurados.']);
            }

            foreach ($niveles as $nivel) {
                if ($nivel->obligatorio && ! $this->responsableEfectivo($nivel)) {
                    throw ValidationException::withMessages([
                        'ruta' => 'El nivel “'.$nivel->nombre_nivel.'” no tiene responsable ni suplente vigente. Configúralo antes de enviar.',
                    ]);
                }
            }

            $antes = $req->estado;
            $req->aprobaciones()->delete();

            $primerResponsable = null;
            $primerOrden = null;
            foreach ($niveles as $nivel) {
                $responsable = $this->responsableEfectivo($nivel);
                if (! $responsable && ! $nivel->obligatorio) {
                    continue;
                }
                if (! $responsable) {
                    throw ValidationException::withMessages([
                        'ruta' => 'Falta responsable en “'.$nivel->nombre_nivel.'”.',
                    ]);
                }
                if ((int) $responsable === (int) $req->solicitante_id) {
                    throw ValidationException::withMessages([
                        'ruta' => 'El solicitante no puede ser el responsable del nivel “'.$nivel->nombre_nivel.'”.',
                    ]);
                }

                RequisicionAprobacion::query()->create([
                    'requisicion_id' => $req->id,
                    'nivel_id' => $nivel->id,
                    'orden' => $nivel->orden,
                    'nombre_nivel' => $nivel->nombre_nivel,
                    'responsable_id' => $responsable,
                    'estado' => 'pendiente',
                    'obligatorio' => $nivel->obligatorio,
                ]);

                if ($primerResponsable === null) {
                    $primerResponsable = $responsable;
                    $primerOrden = $nivel->orden;
                }
            }

            $estadoNuevo = $antes === 'devuelta' ? 'reenviada' : 'pendiente_autorizacion';
            $req->ruta_id = $ruta->id;
            $req->nivel_actual_orden = $primerOrden;
            $req->responsable_actual_id = $primerResponsable;
            $req->estado = 'pendiente_autorizacion';
            $req->enviado_at = now();
            $req->updated_by = $userId;
            $req->save();

            $this->registrarHistorial(
                $req,
                $antes === 'devuelta' ? 'reenviar' : 'enviar',
                $antes,
                'pendiente_autorizacion',
                $estadoNuevo === 'reenviada' ? 'Reenviada tras corrección' : 'Enviada a autorización',
                ['ruta_id' => $ruta->id],
                $userId
            );

            return $req->fresh(['detalles', 'aprobaciones', 'historial', 'solicitante', 'responsableActual']);
        });
    }

    public function puedeDecidir(Requisicion $req, int $userId): bool
    {
        if (! in_array($req->estado, ['pendiente_autorizacion', 'reenviada'], true)) {
            return false;
        }

        return (int) $req->responsable_actual_id === $userId;
    }

    public function autorizar(Requisicion $req, int $userId, ?string $comentario = null): Requisicion
    {
        if (! $this->puedeDecidir($req, $userId)) {
            throw ValidationException::withMessages(['autorizar' => 'No tienes una autorización pendiente en esta requisición.']);
        }

        return DB::transaction(function () use ($req, $userId, $comentario) {
            $req->refresh();
            $pendiente = $req->aprobaciones()
                ->where('estado', 'pendiente')
                ->where('responsable_id', $userId)
                ->orderBy('orden')
                ->first();
            if (! $pendiente) {
                throw ValidationException::withMessages(['autorizar' => 'No hay nivel pendiente asignado a tu usuario.']);
            }

            $antes = $req->estado;
            $pendiente->estado = 'autorizado';
            $pendiente->decision = 'autorizar';
            $pendiente->comentario = $comentario;
            $pendiente->fecha_decision = now();
            $pendiente->save();

            $this->registrarHistorial(
                $req,
                'autorizar',
                $antes,
                $antes,
                $comentario,
                ['nivel' => $pendiente->nombre_nivel, 'orden' => $pendiente->orden],
                $userId
            );

            $siguiente = $req->aprobaciones()
                ->where('estado', 'pendiente')
                ->orderBy('orden')
                ->first();

            if ($siguiente) {
                $req->nivel_actual_orden = $siguiente->orden;
                $req->responsable_actual_id = $siguiente->responsable_id;
                $req->estado = 'pendiente_autorizacion';
                $req->updated_by = $userId;
                $req->save();
            } else {
                $req->nivel_actual_orden = null;
                $req->responsable_actual_id = null;
                $req->estado = 'autorizada';
                $req->autorizado_at = now();
                $req->updated_by = $userId;
                $req->save();
                $this->registrarHistorial($req, 'autorizacion_final', $antes, 'autorizada', 'Completó la ruta de autorización', null, $userId);
            }

            return $req->fresh(['detalles', 'aprobaciones', 'historial', 'solicitante', 'responsableActual']);
        });
    }

    public function rechazar(Requisicion $req, int $userId, string $comentario): Requisicion
    {
        if (trim($comentario) === '') {
            throw ValidationException::withMessages(['comentario' => 'El rechazo requiere un motivo.']);
        }
        if (! $this->puedeDecidir($req, $userId)) {
            throw ValidationException::withMessages(['rechazar' => 'No tienes una autorización pendiente en esta requisición.']);
        }

        return DB::transaction(function () use ($req, $userId, $comentario) {
            $req->refresh();
            $pendiente = $req->aprobaciones()
                ->where('estado', 'pendiente')
                ->where('responsable_id', $userId)
                ->orderBy('orden')
                ->first();
            if (! $pendiente) {
                throw ValidationException::withMessages(['rechazar' => 'No hay nivel pendiente asignado a tu usuario.']);
            }

            $antes = $req->estado;
            $pendiente->estado = 'rechazado';
            $pendiente->decision = 'rechazar';
            $pendiente->comentario = $comentario;
            $pendiente->fecha_decision = now();
            $pendiente->save();

            $req->aprobaciones()->where('estado', 'pendiente')->update(['estado' => 'cancelado']);
            $req->estado = 'rechazada';
            $req->responsable_actual_id = null;
            $req->nivel_actual_orden = null;
            $req->updated_by = $userId;
            $req->save();

            $this->registrarHistorial($req, 'rechazar', $antes, 'rechazada', $comentario, [
                'nivel' => $pendiente->nombre_nivel,
            ], $userId);

            return $req->fresh(['detalles', 'aprobaciones', 'historial', 'solicitante', 'responsableActual']);
        });
    }

    public function devolver(Requisicion $req, int $userId, string $comentario): Requisicion
    {
        if (trim($comentario) === '') {
            throw ValidationException::withMessages(['comentario' => 'La devolución requiere un motivo.']);
        }
        if (! $this->puedeDecidir($req, $userId)) {
            throw ValidationException::withMessages(['devolver' => 'No tienes una autorización pendiente en esta requisición.']);
        }

        return DB::transaction(function () use ($req, $userId, $comentario) {
            $req->refresh();
            $pendiente = $req->aprobaciones()
                ->where('estado', 'pendiente')
                ->where('responsable_id', $userId)
                ->orderBy('orden')
                ->first();
            if (! $pendiente) {
                throw ValidationException::withMessages(['devolver' => 'No hay nivel pendiente asignado a tu usuario.']);
            }

            $antes = $req->estado;
            $pendiente->estado = 'devuelto';
            $pendiente->decision = 'devolver';
            $pendiente->comentario = $comentario;
            $pendiente->fecha_decision = now();
            $pendiente->save();

            $req->aprobaciones()->where('estado', 'pendiente')->update(['estado' => 'cancelado']);
            $req->estado = 'devuelta';
            $req->responsable_actual_id = null;
            $req->nivel_actual_orden = null;
            $req->updated_by = $userId;
            $req->save();

            $this->registrarHistorial($req, 'devolver', $antes, 'devuelta', $comentario, [
                'nivel' => $pendiente->nombre_nivel,
            ], $userId);

            return $req->fresh(['detalles', 'aprobaciones', 'historial', 'solicitante', 'responsableActual']);
        });
    }

    public function cancelar(Requisicion $req, int $userId, string $comentario): Requisicion
    {
        if (trim($comentario) === '') {
            throw ValidationException::withMessages(['comentario' => 'La cancelación requiere un motivo.']);
        }
        if ((int) $req->solicitante_id !== $userId && ! $this->esAdminCompras($userId)) {
            throw ValidationException::withMessages(['cancelar' => 'No puedes cancelar esta requisición.']);
        }
        if (in_array($req->estado, ['atendida', 'cancelada'], true)) {
            throw ValidationException::withMessages(['cancelar' => 'La requisición ya está cerrada.']);
        }

        return DB::transaction(function () use ($req, $userId, $comentario) {
            $antes = $req->estado;
            $req->aprobaciones()->where('estado', 'pendiente')->update(['estado' => 'cancelado']);
            $req->estado = 'cancelada';
            $req->responsable_actual_id = null;
            $req->nivel_actual_orden = null;
            $req->updated_by = $userId;
            $req->save();
            $this->registrarHistorial($req, 'cancelar', $antes, 'cancelada', $comentario, null, $userId);

            return $req->fresh(['detalles', 'aprobaciones', 'historial']);
        });
    }

    protected function esAdminCompras(int $userId): bool
    {
        try {
            $user = auth()->user();
            if ($user && (int) $user->id === $userId) {
                $tipo = strtolower((string) ($user->tipo ?? ''));
                if (in_array($tipo, ['master', 'empresa'], true)) {
                    return true;
                }
            }
        } catch (Throwable $e) {
            // ignore
        }

        return false;
    }
}
