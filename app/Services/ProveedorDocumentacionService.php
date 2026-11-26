<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProveedorDocumentacionService
{
    /** Días de anticipación para considerar contrato “por vencer”. */
    public const DIAS_POR_VENCER = 30;

    public function tiposActivos(): Collection
    {
        return DB::table('tbl_prov_doc_tipos')
            ->where('activo', 1)
            ->orderBy('orden')
            ->orderBy('id')
            ->get();
    }

    public function tiposObligatorios(): Collection
    {
        return $this->tiposActivos()->where('obligatorio', 1)->values();
    }

    /**
     * Estado documental de un proveedor para elegibilidad.
     *
     * @return array{
     *   completo: bool,
     *   puede_invitar: bool,
     *   faltantes: array<int, string>,
     *   contrato_fin: ?string,
     *   contrato_estado: string,
     *   etiqueta: string,
     *   clase: string
     * }
     */
    public function estado(int $proveedorId): array
    {
        $tipos = $this->tiposActivos();
        $docs = DB::table('tblproveedor_documentos')
            ->where('proveedor_id', $proveedorId)
            ->get();

        $faltantes = [];
        foreach ($tipos->where('obligatorio', 1) as $tipo) {
            $doc = $this->docDeTipo($docs, (int) $tipo->id, (string) $tipo->clave, (string) $tipo->nombre);
            $tieneArchivo = $doc && ! empty($doc->archivo);
            if (! $tieneArchivo) {
                // Contrato legado en ficha del proveedor
                if ((int) $tipo->es_contrato === 1) {
                    $prov = DB::table('tblprovedores')->where('id', $proveedorId)->first(['contrato_archivo', 'contrato_fin']);
                    if ($prov && ! empty($prov->contrato_archivo)) {
                        continue;
                    }
                }
                $faltantes[] = $tipo->nombre;
            }
        }

        $contratoFin = $this->fechaContrato($proveedorId, $tipos, $docs);
        $contratoEstado = $this->estadoContrato($contratoFin);

        // sin_contrato solo bloquea si hay tipo contrato obligatorio
        $tieneContratoObligatorio = $tipos->contains(fn ($t) => (int) $t->obligatorio === 1 && (int) $t->es_contrato === 1);
        if ($contratoEstado === 'sin_contrato' && ! $tieneContratoObligatorio) {
            $contratoEstado = 'ok';
        }

        $contratoBloquea = in_array($contratoEstado, ['vencido', 'por_vencer', 'sin_contrato'], true);
        $completo = $faltantes === [] && ! $contratoBloquea;
        $puedeInvitar = $completo;

        if ($faltantes !== []) {
            $etiqueta = 'Docs incompletos';
            $clase = 'bg-danger';
        } elseif ($contratoEstado === 'vencido') {
            $etiqueta = 'Contrato vencido';
            $clase = 'bg-danger';
        } elseif ($contratoEstado === 'por_vencer') {
            $etiqueta = 'Contrato por vencer';
            $clase = 'bg-warning text-dark';
        } elseif ($contratoEstado === 'sin_contrato') {
            $etiqueta = 'Sin contrato';
            $clase = 'bg-danger';
        } else {
            $etiqueta = 'Documentación completa';
            $clase = 'bg-success';
        }

        return [
            'completo' => $completo,
            'puede_invitar' => $puedeInvitar,
            'faltantes' => $faltantes,
            'contrato_fin' => $contratoFin,
            'contrato_estado' => $contratoEstado,
            'etiqueta' => $etiqueta,
            'clase' => $clase,
        ];
    }

    /**
     * @return array<int, array>
     */
    public function mapaEstados(array $proveedorIds): array
    {
        $out = [];
        foreach ($proveedorIds as $id) {
            $out[(int) $id] = $this->estado((int) $id);
        }

        return $out;
    }

    public function matrizDocumentos(int $proveedorId): Collection
    {
        $tipos = $this->tiposActivos();
        $docs = DB::table('tblproveedor_documentos')
            ->where('proveedor_id', $proveedorId)
            ->get();

        return $tipos->map(function ($tipo) use ($docs, $proveedorId) {
            $doc = $this->docDeTipo($docs, (int) $tipo->id, (string) $tipo->clave, (string) $tipo->nombre);
            $archivo = $doc->archivo ?? null;
            $vigencia = $doc->vigencia ?? null;

            if ((int) $tipo->es_contrato === 1) {
                $prov = DB::table('tblprovedores')->where('id', $proveedorId)->first(['contrato_archivo', 'contrato_fin']);
                if ($prov) {
                    if (empty($archivo) && ! empty($prov->contrato_archivo)) {
                        $archivo = $prov->contrato_archivo;
                    }
                    // Siempre la fecha de Editar proveedor.
                    $vigencia = ! empty($prov->contrato_fin) ? $prov->contrato_fin : null;
                }
            }

            return (object) [
                'tipo_id' => (int) $tipo->id,
                'nombre' => $tipo->nombre,
                'obligatorio' => (bool) $tipo->obligatorio,
                'es_contrato' => (bool) $tipo->es_contrato,
                'doc_id' => $doc->id ?? null,
                'archivo' => $archivo,
                'vigencia' => $vigencia,
                'cargado' => ! empty($archivo),
            ];
        });
    }

    public function guardarDocumento(
        int $proveedorId,
        int $tipoId,
        $archivo,
        ?string $vigencia,
        ?int $userId
    ): void {
        $tipo = DB::table('tbl_prov_doc_tipos')->where('id', $tipoId)->where('activo', 1)->first();
        if (! $tipo) {
            throw new \InvalidArgumentException('Tipo de documento no válido.');
        }

        $ruta = $this->guardarArchivo($archivo, $proveedorId, (string) ($tipo->clave ?: 'doc'));
        if (! $ruta) {
            throw new \InvalidArgumentException('Archivo no válido (PDF/JPG/PNG).');
        }

        // La vigencia del contrato se captura en Editar proveedor (contrato_fin).
        if ((int) $tipo->es_contrato === 1) {
            $contratoFin = DB::table('tblprovedores')->where('id', $proveedorId)->value('contrato_fin');
            $vigencia = $contratoFin ?: null;
        }

        $existente = DB::table('tblproveedor_documentos')
            ->where('proveedor_id', $proveedorId)
            ->where('tipo_id', $tipoId)
            ->first();

        $payload = [
            'nombre' => $tipo->nombre,
            'tipo_id' => $tipoId,
            'vigencia' => $vigencia ?: null,
            'archivo' => $ruta,
            'uploaded_by' => $userId,
            'updated_at' => now(),
        ];

        if ($existente) {
            if (! empty($existente->archivo)) {
                $old = public_path($existente->archivo);
                if (is_file($old)) {
                    @unlink($old);
                }
            }
            DB::table('tblproveedor_documentos')->where('id', $existente->id)->update($payload);
        } else {
            $payload['proveedor_id'] = $proveedorId;
            $payload['created_at'] = now();
            DB::table('tblproveedor_documentos')->insert($payload);
        }

        if ((int) $tipo->es_contrato === 1) {
            // Solo actualiza el archivo; la fecha se mantiene desde Editar proveedor.
            DB::table('tblprovedores')->where('id', $proveedorId)->update([
                'contrato_archivo' => $ruta,
            ]);
        }
    }

    /**
     * Guarda varios documentos en una sola operación.
     * $archivos: [tipo_id => UploadedFile], $vigencias: [tipo_id => date|null]
     *
     * @return array{ok:int, errores:string[]}
     */
    public function guardarDocumentosLote(
        int $proveedorId,
        array $archivos,
        array $vigencias,
        ?int $userId
    ): array {
        $ok = 0;
        $errores = [];

        foreach ($archivos as $tipoId => $archivo) {
            if (! $archivo || ! method_exists($archivo, 'isValid') || ! $archivo->isValid()) {
                continue;
            }

            $tipoId = (int) $tipoId;
            $vigencia = $vigencias[$tipoId] ?? ($vigencias[(string) $tipoId] ?? null);
            $vigencia = $vigencia !== '' ? $vigencia : null;

            $tipo = DB::table('tbl_prov_doc_tipos')->where('id', $tipoId)->where('activo', 1)->first();
            if (! $tipo) {
                $errores[] = "Tipo #{$tipoId} no válido.";
                continue;
            }

            try {
                $this->guardarDocumento($proveedorId, $tipoId, $archivo, $vigencia, $userId);
                $ok++;
            } catch (\InvalidArgumentException $e) {
                $errores[] = "{$tipo->nombre}: {$e->getMessage()}";
            }
        }

        return ['ok' => $ok, 'errores' => $errores];
    }

    private function docDeTipo(Collection $docs, int $tipoId, string $clave, string $nombre)
    {
        $byTipo = $docs->first(fn ($d) => (int) ($d->tipo_id ?? 0) === $tipoId);
        if ($byTipo) {
            return $byTipo;
        }

        return $docs->first(function ($d) use ($clave, $nombre) {
            $n = mb_strtolower(trim((string) ($d->nombre ?? '')));

            return $n === mb_strtolower($nombre)
                || ($clave !== '' && str_contains($n, mb_strtolower($clave)));
        });
    }

    private function fechaContrato(int $proveedorId, Collection $tipos, Collection $docs): ?string
    {
        // Fuente de verdad: Editar proveedor (contrato_fin).
        $fin = DB::table('tblprovedores')->where('id', $proveedorId)->value('contrato_fin');
        if ($fin) {
            return (string) $fin;
        }

        $tipoContrato = $tipos->first(fn ($t) => (int) $t->es_contrato === 1);
        if ($tipoContrato) {
            $doc = $this->docDeTipo($docs, (int) $tipoContrato->id, (string) $tipoContrato->clave, (string) $tipoContrato->nombre);
            if ($doc && ! empty($doc->vigencia)) {
                return (string) $doc->vigencia;
            }
        }

        return null;
    }

    /**
     * Sincroniza la vigencia del documento-contrato con contrato_fin de la ficha.
     */
    public function sincronizarVigenciaContrato(int $proveedorId): void
    {
        $fin = DB::table('tblprovedores')->where('id', $proveedorId)->value('contrato_fin');
        $tipoContrato = DB::table('tbl_prov_doc_tipos')
            ->where('activo', 1)
            ->where('es_contrato', 1)
            ->first();

        if (! $tipoContrato) {
            return;
        }

        DB::table('tblproveedor_documentos')
            ->where('proveedor_id', $proveedorId)
            ->where('tipo_id', $tipoContrato->id)
            ->update([
                'vigencia' => $fin ?: null,
                'updated_at' => now(),
            ]);
    }

    private function estadoContrato(?string $fechaFin): string
    {
        if (! $fechaFin) {
            return 'sin_contrato';
        }

        $fin = Carbon::parse($fechaFin)->startOfDay();
        $hoy = Carbon::today();
        if ($fin->lt($hoy)) {
            return 'vencido';
        }
        if ($fin->lte($hoy->copy()->addDays(self::DIAS_POR_VENCER))) {
            return 'por_vencer';
        }

        return 'vigente';
    }

    private function guardarArchivo($archivo, int $id, string $prefijo): ?string
    {
        if (! $archivo || ! method_exists($archivo, 'isValid') || ! $archivo->isValid()) {
            return null;
        }

        $ext = strtolower($archivo->getClientOriginalExtension() ?: '');
        if (! in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            return null;
        }

        $dir = public_path('archivos/proveedores/'.$id);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $nombre = $prefijo.'_'.date('YmdHis').'.'.$ext;
        $archivo->move($dir, $nombre);

        return 'archivos/proveedores/'.$id.'/'.$nombre;
    }
}
