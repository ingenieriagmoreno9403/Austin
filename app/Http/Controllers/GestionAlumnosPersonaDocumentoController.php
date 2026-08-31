<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Models\PersonaDocumento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GestionAlumnosPersonaDocumentoController extends Controller
{
    public function subirConvenioFirmado(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'alumno_id' => ['required', 'integer', 'min:1'],
            'tipo_convenio' => ['required', 'in:aprendizaje,colaboracion'],
            'archivo' => ['required', 'file', 'max:15360'],
        ]);

        $tipo = (string) $validated['tipo_convenio'];
        $needle = $tipo === 'aprendizaje'
            ? 'convenio de aprendizaje'
            : 'convenio de colaboracion';

        $docTipo = Documento::query()
            ->whereRaw('LOWER(nombre_documento) LIKE ?', ['%'.$needle.'%'])
            ->orderByDesc('id')
            ->first();

        if (! $docTipo) {
            return response()->json([
                'message' => 'No existe el tipo de documento para '.$needle.'. Regístralo en catálogo de documentos.',
            ], 422);
        }

        $alumnoId = (int) $validated['alumno_id'];
        $file = $request->file('archivo');
        $dir = 'gestion_alumnos/alumno_'.$alumnoId.'/alumno';
        Storage::disk('local')->makeDirectory($dir);
        $safe = $this->nombreArchivoSeguro($file->getClientOriginalName());
        $storedName = date('Ymd_His').'_convenio_'.$tipo.'_'.$safe;
        $rutaRelativa = $file->storeAs($dir, $storedName, 'local');
        if ($rutaRelativa === false) {
            return response()->json(['message' => 'No se pudo guardar el archivo.'], 500);
        }

        $row = PersonaDocumento::query()
            ->where('id_persona', $alumnoId)
            ->where('id_documento', (int) $docTipo->id)
            ->whereRaw('LOWER(tipos_persona) LIKE ?', ['%alumno%'])
            ->first();

        if ($row) {
            if ($row->ruta_documento && Storage::disk('local')->exists($row->ruta_documento)) {
                Storage::disk('local')->delete($row->ruta_documento);
            }
            $row->update([
                'fecha_alta' => now()->format('Y-m-d'),
                'estado_documento' => 'a',
                'ruta_documento' => $rutaRelativa,
            ]);
        } else {
            $row = PersonaDocumento::create([
                'id_documento' => (int) $docTipo->id,
                'id_persona' => $alumnoId,
                'tipos_persona' => 'alumno',
                'fecha_alta' => now()->format('Y-m-d'),
                'estado_documento' => 'a',
                'ruta_documento' => $rutaRelativa,
            ]);
        }

        return response()->json([
            'message' => 'Convenio firmado subido correctamente.',
            'data' => [
                'id' => $row->id,
                'id_documento' => $row->id_documento,
                'ruta_documento' => $row->ruta_documento,
                'url_descargar' => url('/Gestion_alumnos/api/personas-documentos/'.$row->id.'/descargar'),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $row = PersonaDocumento::with('tipoDocumento:id,nombre_documento')->findOrFail($id);
        $out = $row->toArray();
        $out['doc_label'] = $row->tipoDocumento ? $row->tipoDocumento->nombre_documento : null;
        $base = url('/Gestion_alumnos/api/personas-documentos/'.$id);
        $out['url_visualizar'] = $base.'/archivo';
        $out['url_descargar'] = $base.'/descargar';
        if (! empty($out['ruta_documento'])) {
            $out['nombre_archivo'] = basename((string) $out['ruta_documento']);
        }

        return response()->json(['data' => $out]);
    }

    public function index(Request $request): JsonResponse
    {
        $raw = $request->query('id_personas', '');
        if ($raw === '' || $raw === null) {
            return response()->json(['data' => []]);
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', is_array($raw) ? implode(',', $raw) : (string) $raw)))));
        if ($ids === []) {
            return response()->json(['data' => []]);
        }

        $rows = PersonaDocumento::query()
            ->leftJoin('tbldocumentos as d', 'tblpersonas_documentos.id_documento', '=', 'd.id')
            ->whereIn('tblpersonas_documentos.id_persona', $ids)
            ->orderByDesc('tblpersonas_documentos.id')
            ->select([
                'tblpersonas_documentos.id',
                'tblpersonas_documentos.id_documento',
                'tblpersonas_documentos.id_persona',
                'tblpersonas_documentos.tipos_persona',
                'tblpersonas_documentos.fecha_alta',
                'tblpersonas_documentos.estado_documento',
                'tblpersonas_documentos.otros_conceptos2',
                'tblpersonas_documentos.otros_conceptos34',
                'tblpersonas_documentos.ruta_documento',
                'tblpersonas_documentos.created_at',
                'tblpersonas_documentos.updated_at',
                'd.nombre_documento as doc_label',
            ])
            ->get();

        $base = url('/Gestion_alumnos/api/personas-documentos');
        $data = $rows->map(function ($row) use ($base) {
            $arr = is_object($row) && method_exists($row, 'toArray') ? $row->toArray() : (array) $row;
            $id = $arr['id'] ?? null;
            if ($id) {
                $arr['url_visualizar'] = $base.'/'.$id.'/archivo';
                $arr['url_descargar'] = $base.'/'.$id.'/descargar';
            } else {
                $arr['url_visualizar'] = null;
                $arr['url_descargar'] = null;
            }
            if (! empty($arr['ruta_documento'])) {
                $arr['nombre_archivo'] = basename((string) $arr['ruta_documento']);
            }

            return $arr;
        });

        return response()->json(['data' => $data->values()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_alumno' => ['required', 'integer', 'min:1'],
            'id_persona_alumno' => ['required', 'integer', 'min:1'],
            'carpeta_tutor' => ['nullable', 'string', 'max:120'],
            'id_documento' => ['required', 'integer', 'exists:tbldocumentos,id'],
            'id_persona' => ['required', 'integer', 'min:1'],
            'tipos_persona' => ['required', 'string', 'max:200'],
            'fecha_alta' => ['required', 'date'],
            'estado_documento' => ['nullable', 'string', 'max:2'],
            'otros_conceptos2' => ['nullable', 'string', 'max:250'],
            'otros_conceptos34' => ['nullable', 'numeric'],
            'archivo' => ['required', 'file', 'max:15360'],
        ]);

        $validated['fecha_alta'] = \Carbon\Carbon::parse($validated['fecha_alta'])->format('Y-m-d');

        $estAlta = $validated['estado_documento'] ?? null;
        if ($estAlta === null || trim((string) $estAlta) === '') {
            $validated['estado_documento'] = 'a';
        }

        // id_persona puede repetirse entre tblalumnos y tbltutores (autoincrementos distintos);
        // la combinación con tipos_persona distingue alumno vs tutor (y el texto del combo).
        $tipoNorm = trim((string) $validated['tipos_persona']);
        $dup = PersonaDocumento::query()
            ->where('id_persona', (int) $validated['id_persona'])
            ->where('id_documento', (int) $validated['id_documento'])
            ->where('tipos_persona', $tipoNorm)
            ->exists();
        if ($dup) {
            return response()->json([
                'message' => 'Ya existe un documento de este tipo para esta persona.',
                'errors' => ['id_documento' => ['Ya existe un registro con el mismo tipo de documento para la persona seleccionada.']],
            ], 422);
        }

        $validated['tipos_persona'] = $tipoNorm;

        $idAlumno = (int) $validated['id_alumno'];
        $idPerAl = (int) $validated['id_persona_alumno'];
        $idPer = (int) $validated['id_persona'];
        $carpetaTutor = isset($validated['carpeta_tutor']) ? trim((string) $validated['carpeta_tutor']) : '';

        $relativeDir = $this->directorioRelativo($idAlumno, $idPer, $idPerAl, $carpetaTutor);
        Storage::disk('local')->makeDirectory($relativeDir);

        $file = $request->file('archivo');
        $original = $file->getClientOriginalName();
        $safe = $this->nombreArchivoSeguro($original);
        $storedName = date('Ymd_His').'_'.$safe;

        $rutaRelativa = $file->storeAs($relativeDir, $storedName, 'local');
        if ($rutaRelativa === false) {
            return response()->json(['message' => 'No se pudo guardar el archivo.'], 500);
        }

        $validated['ruta_documento'] = $rutaRelativa;
        unset($validated['id_alumno'], $validated['id_persona_alumno'], $validated['carpeta_tutor'], $validated['archivo']);

        $row = DB::transaction(function () use ($validated) {
            return PersonaDocumento::create($validated);
        });

        $row->load('tipoDocumento:id,nombre_documento');
        $label = $row->tipoDocumento ? $row->tipoDocumento->nombre_documento : null;
        $out = $row->toArray();
        $out['doc_label'] = $label;
        $b = url('/Gestion_alumnos/api/personas-documentos/'.$row->id);
        $out['url_visualizar'] = $b.'/archivo';
        $out['url_descargar'] = $b.'/descargar';

        return response()->json([
            'message' => 'Documento registrado correctamente.',
            'data' => $out,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $row = PersonaDocumento::findOrFail($id);

        $validated = $request->validate([
            'id_documento' => ['required', 'integer', 'exists:tbldocumentos,id'],
            'tipos_persona' => ['required', 'string', 'max:200'],
            'fecha_alta' => ['required', 'date'],
            'estado_documento' => ['nullable', 'string', 'max:2'],
            'otros_conceptos2' => ['nullable', 'string', 'max:250'],
            'otros_conceptos34' => ['nullable', 'numeric'],
            'archivo' => ['nullable', 'file', 'max:15360'],
        ]);

        if (isset($validated['fecha_alta'])) {
            $validated['fecha_alta'] = \Carbon\Carbon::parse($validated['fecha_alta'])->format('Y-m-d');
        }

        $estEd = $validated['estado_documento'] ?? null;
        if ($estEd === null || trim((string) $estEd) === '') {
            $validated['estado_documento'] = 'a';
        }

        $tipoUpd = trim((string) $validated['tipos_persona']);
        $dupUpdate = PersonaDocumento::query()
            ->where('id_persona', $row->id_persona)
            ->where('id_documento', (int) $validated['id_documento'])
            ->where('tipos_persona', $tipoUpd)
            ->where('id', '!=', $row->id)
            ->exists();
        if ($dupUpdate) {
            return response()->json([
                'message' => 'Ya existe otro documento de este tipo para esta persona.',
                'errors' => ['id_documento' => ['Ya existe un registro con el mismo tipo de documento para la persona seleccionada.']],
            ], 422);
        }

        if ($request->hasFile('archivo')) {
            $file = $request->file('archivo');
            $dir = $row->ruta_documento ? dirname($row->ruta_documento) : null;
            if (! $dir) {
                return response()->json(['message' => 'El registro no tiene carpeta base; no se puede reemplazar el archivo.'], 422);
            }
            Storage::disk('local')->makeDirectory($dir);
            if ($row->ruta_documento && Storage::disk('local')->exists($row->ruta_documento)) {
                Storage::disk('local')->delete($row->ruta_documento);
            }
            $safe = $this->nombreArchivoSeguro($file->getClientOriginalName());
            $storedName = date('Ymd_His').'_'.$safe;
            $rutaNueva = $file->storeAs($dir, $storedName, 'local');
            if ($rutaNueva === false) {
                return response()->json(['message' => 'No se pudo guardar el nuevo archivo.'], 500);
            }
            $validated['ruta_documento'] = $rutaNueva;
        }

        unset($validated['archivo']);
        $row->update($validated);

        $row = $row->fresh();
        $row->load('tipoDocumento:id,nombre_documento');
        $label = $row->tipoDocumento ? $row->tipoDocumento->nombre_documento : null;
        $out = $row->toArray();
        $out['doc_label'] = $label;
        $b = url('/Gestion_alumnos/api/personas-documentos/'.$row->id);
        $out['url_visualizar'] = $b.'/archivo';
        $out['url_descargar'] = $b.'/descargar';

        return response()->json([
            'message' => 'Documento actualizado correctamente.',
            'data' => $out,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $row = PersonaDocumento::findOrFail($id);
        $path = $row->ruta_documento ? (string) $row->ruta_documento : '';

        DB::transaction(function () use ($row, $path) {
            if ($path !== '' && Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }
            $row->delete();
        });

        return response()->json([
            'message' => 'Documento eliminado correctamente.',
        ]);
    }

    /**
     * Sirve el archivo con Content-Disposition: inline para ver en el navegador (PDF, imágenes, etc.).
     */
    public function visualizar(int $id): StreamedResponse
    {
        $row = PersonaDocumento::findOrFail($id);
        if (! $row->ruta_documento || ! Storage::disk('local')->exists($row->ruta_documento)) {
            abort(404, 'Archivo no encontrado.');
        }

        $nombre = basename((string) $row->ruta_documento);

        return Storage::disk('local')->response($row->ruta_documento, $nombre, [], 'inline');
    }

    /**
     * Descarga forzada (adjunto).
     */
    public function descargar(int $id): StreamedResponse
    {
        $row = PersonaDocumento::findOrFail($id);
        if (! $row->ruta_documento || ! Storage::disk('local')->exists($row->ruta_documento)) {
            abort(404, 'Archivo no encontrado.');
        }

        return Storage::disk('local')->download($row->ruta_documento);
    }

    /**
     * Carpeta relativa al disco local: gestion_alumnos/alumno_{id}/alumno | tutores/{slug}
     */
    protected function directorioRelativo(int $idAlumno, int $idPersona, int $idPersonaAlumno, string $carpetaTutor): string
    {
        $base = 'gestion_alumnos/alumno_'.$idAlumno;

        if ($idPersona === $idPersonaAlumno) {
            return $base.'/alumno';
        }

        $slug = $carpetaTutor !== '' ? $this->sanearNombreCarpeta($carpetaTutor) : 'persona_'.$idPersona;

        return $base.'/tutores/'.$slug;
    }

    protected function sanearNombreCarpeta(string $nombre): string
    {
        $slug = Str::slug($nombre, '_', 'es');
        if ($slug === '') {
            return 'tutor';
        }

        return Str::limit($slug, 100, '');
    }

    protected function nombreArchivoSeguro(string $original): string
    {
        $base = basename($original);
        $base = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $base);

        return $base !== '' ? Str::limit($base, 180, '') : 'documento.bin';
    }
}
