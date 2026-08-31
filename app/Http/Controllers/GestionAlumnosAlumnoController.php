<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Documento;
use App\Models\Escuela;
use App\Models\Especialidad;
use App\Models\GestionAlumnosEmpresa;
use App\Models\PersonaDocumento;
use App\Models\Tutor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class GestionAlumnosAlumnoController extends Controller
{
    public function index(): JsonResponse
    {
        $alumnos = Alumno::query()
            ->with([
                'escuela:id,nombre',
                'especialidad:id,nombre_especialidad',
                'empresa:id,nombre',
            ])
            ->orderByDesc('id')
            ->get();

        $requisitos = $this->obtenerRequisitosDocumentosPorTipo();
        $reqAlumnoIds = $requisitos['alumno'] ?? [];
        $reqTutorIds = $requisitos['tutor'] ?? [];

        $alumnoIds = $alumnos->pluck('id')->map(fn ($x) => (int) $x)->all();
        $docsAlumnoMap = []; // [id_alumno => [id_documento, ...]]
        $tutoresPorAlumno = [];
        $docsTutorMap = []; // [id_tutor => [id_documento, ...]]
        $fotosUsuarioAlumnoMap = []; // [id_alumno => nombre_foto]

        if (! empty($alumnoIds)) {
            $docsAlumnoRows = PersonaDocumento::query()
                ->select(['id_persona', 'id_documento'])
                ->whereIn('id_persona', $alumnoIds)
                ->whereRaw('LOWER(tipos_persona) LIKE ?', ['%alumno%'])
                ->get();
            foreach ($docsAlumnoRows as $row) {
                $pid = (int) $row->id_persona;
                $docId = (int) $row->id_documento;
                if (! isset($docsAlumnoMap[$pid])) {
                    $docsAlumnoMap[$pid] = [];
                }
                $docsAlumnoMap[$pid][$docId] = $docId;
            }
            foreach ($docsAlumnoMap as $pid => $set) {
                $docsAlumnoMap[$pid] = array_values($set);
            }

            $tutores = Tutor::query()
                ->whereIn('alumno_id', $alumnoIds)
                ->get(['id', 'alumno_id']);
            foreach ($tutores as $t) {
                $aid = (int) $t->alumno_id;
                $tid = (int) $t->id;
                if (!isset($tutoresPorAlumno[$aid])) {
                    $tutoresPorAlumno[$aid] = [];
                }
                $tutoresPorAlumno[$aid][] = $tid;
            }
            $tutorIds = $tutores->pluck('id')->map(fn ($x) => (int) $x)->all();
            if (! empty($tutorIds)) {
                $docsTutorRows = PersonaDocumento::query()
                    ->select(['id_persona', 'id_documento'])
                    ->whereIn('id_persona', $tutorIds)
                    ->whereRaw('LOWER(tipos_persona) LIKE ?', ['%tutor%'])
                    ->get();
                foreach ($docsTutorRows as $row) {
                    $pid = (int) $row->id_persona;
                    $docId = (int) $row->id_documento;
                    if (! isset($docsTutorMap[$pid])) {
                        $docsTutorMap[$pid] = [];
                    }
                    $docsTutorMap[$pid][$docId] = $docId;
                }
                foreach ($docsTutorMap as $pid => $set) {
                    $docsTutorMap[$pid] = array_values($set);
                }
            }

            $fotosUsuarioAlumnoMap = DB::table('users')
                ->where('tipo', 'alumno')
                ->whereIn('id_tipo', $alumnoIds)
                ->whereNotNull('nombre_foto')
                ->pluck('nombre_foto', 'id_tipo')
                ->map(fn ($foto) => trim((string) $foto))
                ->all();
        }

        $alumnos->transform(function ($a) use ($docsAlumnoMap, $tutoresPorAlumno, $docsTutorMap, $reqAlumnoIds, $reqTutorIds, $fotosUsuarioAlumnoMap) {
            $aid = (int) $a->id;
            $docsAlumnoIds = $docsAlumnoMap[$aid] ?? [];
            $faltanAlumno = count(array_diff($reqAlumnoIds, $docsAlumnoIds));
            $fotoUsuario = trim((string) ($fotosUsuarioAlumnoMap[$aid] ?? ''));

            $faltanTutor = 0;
            $tutorIds = $tutoresPorAlumno[$aid] ?? [];
            // Solo se cuentan faltantes de tutor si ya hay tutores registrados.
            if (! empty($reqTutorIds) && ! empty($tutorIds)) {
                foreach ($tutorIds as $tid) {
                    $docsTutorIds = $docsTutorMap[$tid] ?? [];
                    $faltanTutor += count(array_diff($reqTutorIds, $docsTutorIds));
                }
            }

            $a->docs_pendientes = ($faltanAlumno + $faltanTutor) > 0;
            $a->docs_faltantes_alumno = $faltanAlumno;
            $a->docs_faltantes_tutor = $faltanTutor;
            $a->foto_perfil = $fotoUsuario !== '' ? 'Images/Perfil/' . $fotoUsuario : null;
            return $a;
        });

        return response()->json(['data' => $alumnos]);
    }

    public function requisitosDocumentos(): JsonResponse
    {
        $requisitos = $this->obtenerRequisitosDocumentosPorTipo();
        $docs = Documento::query()
            ->orderBy('nombre_documento')
            ->get(['id', 'nombre_documento', 'activo']);

        $reqAlumno = $requisitos['alumno'] ?? [];
        $reqTutor = $requisitos['tutor'] ?? [];
        $outDocs = $docs->map(function ($d) use ($reqAlumno, $reqTutor) {
            return [
                'id' => (int) $d->id,
                'nombre_documento' => (string) $d->nombre_documento,
                'activo' => is_null($d->activo) ? true : (bool) $d->activo,
                'obligatorio_alumno' => in_array((int) $d->id, $reqAlumno, true),
                'obligatorio_tutor' => in_array((int) $d->id, $reqTutor, true),
            ];
        })->values();

        return response()->json([
            'data' => [
                'alumno' => $reqAlumno,
                'tutor' => $reqTutor,
                'documentos' => $outDocs,
            ],
        ]);
    }

    public function actualizarRequisitosDocumentos(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'alumno' => ['nullable', 'array'],
            'alumno.*' => ['integer', 'exists:tbldocumentos,id'],
            'tutor' => ['nullable', 'array'],
            'tutor.*' => ['integer', 'exists:tbldocumentos,id'],
        ]);

        if (! Schema::hasTable('tblga_requisitos_documentos_det')) {
            return response()->json(['message' => 'La tabla de requisitos de documentos no existe.'], 422);
        }

        $reqAlumno = array_values(array_unique(array_map('intval', $validated['alumno'] ?? [])));
        $reqTutor = array_values(array_unique(array_map('intval', $validated['tutor'] ?? [])));
        $now = now();
        DB::transaction(function () use ($reqAlumno, $reqTutor, $now) {
            DB::table('tblga_requisitos_documentos_det')->where('tipo_persona', 'alumno')->delete();
            DB::table('tblga_requisitos_documentos_det')->where('tipo_persona', 'tutor')->delete();

            foreach ($reqAlumno as $docId) {
                DB::table('tblga_requisitos_documentos_det')->insert([
                    'tipo_persona' => 'alumno',
                    'id_documento' => $docId,
                    'activo' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            foreach ($reqTutor as $docId) {
                DB::table('tblga_requisitos_documentos_det')->insert([
                    'tipo_persona' => 'tutor',
                    'id_documento' => $docId,
                    'activo' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });

        return response()->json([
            'message' => 'Requisitos de documentos actualizados correctamente.',
            'data' => [
                'alumno' => $reqAlumno,
                'tutor' => $reqTutor,
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $alumno = Alumno::query()
            ->with([
                'escuela:id,nombre',
                'especialidad:id,nombre_especialidad',
                'empresa:id,nombre',
            ])
            ->findOrFail($id);
        $fotoUsuario = trim((string) DB::table('users')
            ->where('tipo', 'alumno')
            ->where('id_tipo', $alumno->id)
            ->value('nombre_foto'));
        $alumno->foto_perfil = $fotoUsuario !== '' ? 'Images/Perfil/' . $fotoUsuario : null;

        return response()->json(['data' => $alumno]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatedAlumnoPayload($request);
        $validated['estado'] = 'iniciado';

        $alumno = Alumno::create($validated);

        $alumno->load(['escuela:id,nombre', 'especialidad:id,nombre_especialidad', 'empresa:id,nombre']);

        return response()->json([
            'message' => 'Alumno registrado correctamente.',
            'data' => $alumno,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $alumno = Alumno::findOrFail($id);

        $validated = $this->validatedAlumnoPayload($request);

        $alumno->update($validated);

        $alumno->load(['escuela:id,nombre', 'especialidad:id,nombre_especialidad', 'empresa:id,nombre']);

        return response()->json([
            'message' => 'Alumno actualizado correctamente.',
            'data' => $alumno->fresh(),
        ]);
    }

    public function inactivar(int $id): JsonResponse
    {
        $alumno = Alumno::findOrFail($id);
        $alumno->estado = 'inactivo';
        $alumno->save();

        $alumno->load(['escuela:id,nombre', 'especialidad:id,nombre_especialidad', 'empresa:id,nombre']);

        return response()->json([
            'message' => 'Alumno inactivado correctamente.',
            'data' => $alumno->fresh(),
        ]);
    }

    public function importarMasivo(Request $request): JsonResponse
    {
        if ((int) optional(auth()->user())->id !== 524) {
            return response()->json(['message' => 'No autorizado para esta carga provisional.'], 403);
        }

        $validated = $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $path = $validated['archivo']->getRealPath();
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if (!$rows || count($rows) < 2) {
            return response()->json(['message' => 'El archivo no tiene filas para importar.'], 422);
        }

        $headerRaw = array_map(static fn($h) => trim((string) $h), $rows[0]);
        $headerNorm = array_map(fn($h) => $this->normalizarHeader($h), $headerRaw);
        $indexByHeader = [];
        foreach ($headerNorm as $i => $h) {
            if ($h !== '') {
                $indexByHeader[$h] = $i;
            }
        }

        $especialidades = Especialidad::query()->get(['id', 'nombre_especialidad']);
        $escuelas = Escuela::query()->get(['id', 'nombre']);
        $empresas = GestionAlumnosEmpresa::query()->get(['id', 'nombre']);
        $especialidadDefaultId = (int) (optional($especialidades->first())->id ?? 0);
        $especialidadByNombre = [];
        foreach ($especialidades as $e) {
            $especialidadByNombre[$this->normalizarHeader((string) $e->nombre_especialidad)] = (int) $e->id;
        }
        $escuelaByNombre = [];
        foreach ($escuelas as $e) {
            $escuelaByNombre[$this->normalizarHeader((string) $e->nombre)] = (int) $e->id;
        }
        $empresaByNombre = [];
        foreach ($empresas as $e) {
            $empresaByNombre[$this->normalizarHeader((string) $e->nombre)] = (int) $e->id;
        }

        $creados = 0;
        $actualizados = 0;
        $errores = [];
        $siguienteMatricula = ((int) (Alumno::query()->max('nunero_matricula') ?? 0)) + 1;

        for ($r = 1; $r < count($rows); $r++) {
            $excelRowNumber = $r + 1;
            $row = $rows[$r] ?? [];
            if ($this->filaVacia($row)) {
                continue;
            }

            $matricula = $this->valorFila($row, $indexByHeader, ['numero_matricula', 'matricula', 'no_matricula', 'nunero_matricula']);
            $nombres = $this->valorFila($row, $indexByHeader, ['nombres', 'nombre']);
            $apellidoPaterno = $this->valorFila($row, $indexByHeader, ['apellido_paterno', 'ap_paterno', 'paterno']);
            $apellidoMaterno = $this->valorFila($row, $indexByHeader, ['apellido_materno', 'ap_materno', 'materno']);
            $semestre = $this->valorFila($row, $indexByHeader, ['semestre']);
            $correo = $this->valorFila($row, $indexByHeader, ['correo', 'email']);
            $telefono = $this->valorFila($row, $indexByHeader, ['telefono', 'celular', 'movil']);
            $fechaNacimiento = $this->valorFila($row, $indexByHeader, ['fecha_nacimiento', 'fecha_de_nacimiento', 'fechanacimiento', 'fecha_nac', 'nacimiento']);
            $especialidadValor = $this->valorFila($row, $indexByHeader, ['id_especialidad', 'especialidad']);
            $escuelaValor = $this->valorFila($row, $indexByHeader, ['id_escuela', 'escuela']);
            $empresaValor = $this->valorFila($row, $indexByHeader, ['id_empresa', 'empresa', 'empresas', 'nombre_empresa']);

            $matriculaInt = is_numeric($matricula) ? (int) $matricula : 0;
            $matriculaGenerada = false;
            if ($matriculaInt <= 0) {
                // Si no viene matrícula válida en Excel, se genera consecutiva.
                $matriculaInt = $siguienteMatricula;
                $siguienteMatricula++;
                $matriculaGenerada = true;
            }

            $idEspecialidad = null;
            if (is_numeric($especialidadValor)) {
                $idEspecialidad = (int) $especialidadValor;
            } else {
                $idEspecialidad = $especialidadByNombre[$this->normalizarHeader((string) $especialidadValor)] ?? null;
            }
            if (!$idEspecialidad) {
                $idEspecialidad = $especialidadDefaultId > 0 ? $especialidadDefaultId : null;
            }
            if (!$idEspecialidad) {
                $errores[] = "Fila {$excelRowNumber}: no existe especialidad en catálogo para usar como default.";
                continue;
            }

            $idEscuela = null;
            if (is_numeric($escuelaValor)) {
                $idEscuela = (int) $escuelaValor;
            } else {
                $idEscuela = $escuelaByNombre[$this->normalizarHeader((string) $escuelaValor)] ?? null;
            }
            if (!$idEscuela) {
                $idEscuela = 2; // Default solicitado para carga masiva actual.
            }

            $idEmpresa = null;
            if (is_numeric($empresaValor)) {
                $idEmpresa = (int) $empresaValor;
            } else {
                $empresaNorm = $this->normalizarHeader((string) $empresaValor);
                $idEmpresa = $empresaByNombre[$empresaNorm] ?? null;
                if (!$idEmpresa && $empresaNorm !== '') {
                    $empresaLike = GestionAlumnosEmpresa::query()
                        ->whereRaw('LOWER(TRIM(nombre)) LIKE ?', ['%' . mb_strtolower(str_replace('_', ' ', $empresaNorm), 'UTF-8') . '%'])
                        ->first(['id']);
                    $idEmpresa = $empresaLike ? (int) $empresaLike->id : null;
                }
            }

            $nombreFinal = trim((string) $nombres);
            if ($nombreFinal === '') {
                $nombreFinal = 'SIN NOMBRE';
            }

            $apellidoPaternoFinal = trim((string) $apellidoPaterno);
            if ($apellidoPaternoFinal === '') {
                $apellidoPaternoFinal = '.';
            }
            $apellidoMaternoFinal = trim((string) $apellidoMaterno) ?: null;
            $semestreInt = is_numeric($semestre) ? max(1, min(20, (int) $semestre)) : 1;
            $correoFinal = trim((string) $correo);
            if ($correoFinal === '') {
                $correoFinal = 'alumno' . $matriculaInt . '@temporal.local';
            }
            $fechaNacimientoFinal = $this->parseFechaExcel($fechaNacimiento);

            $payload = [
                'nunero_matricula' => $matriculaInt,
                'id_especialidad' => $idEspecialidad,
                'semestre' => $semestreInt,
                'nombres' => $nombreFinal,
                'apellido_paterno' => $apellidoPaternoFinal,
                'apellido_materno' => $apellidoMaternoFinal,
                'id_escuela' => $idEscuela,
                'id_empresa' => $idEmpresa,
                'fecha_nacimiento' => $fechaNacimientoFinal,
                'telefono' => trim((string) $telefono) ?: null,
                'correo' => $correoFinal,
                'estado' => 'iniciado',
                'id_beca' => null,
            ];

            $existing = Alumno::query()->where('nunero_matricula', $matriculaInt)->first();
            if (!$existing && $matriculaGenerada) {
                $q = Alumno::query()
                    ->whereRaw('LOWER(TRIM(nombres)) = ?', [mb_strtolower($nombreFinal, 'UTF-8')]);
                if ($fechaNacimientoFinal) {
                    $q->whereDate('fecha_nacimiento', $fechaNacimientoFinal);
                }
                // Solo usamos correo como llave si es correo real (no temporal autogenerado).
                if (!empty($correoFinal) && stripos($correoFinal, '@temporal.local') === false) {
                    $q->whereRaw('LOWER(TRIM(correo)) = ?', [mb_strtolower($correoFinal, 'UTF-8')]);
                }
                $existing = $q->first();
            }
            if ($existing) {
                // En match secundario, conservamos matrícula existente para evitar duplicados.
                $payload['nunero_matricula'] = (int) $existing->nunero_matricula;
                $existing->update($payload);
                $actualizados++;
            } else {
                Alumno::query()->create($payload);
                $creados++;
            }
        }

        return response()->json([
            'message' => 'Importación procesada.',
            'data' => [
                'creados' => $creados,
                'actualizados' => $actualizados,
                'errores' => $errores,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedAlumnoPayload(Request $request): array
    {
        $payload = $request->all();
        if (array_key_exists('id_escuela', $payload) && ($payload['id_escuela'] === '' || $payload['id_escuela'] === null)) {
            $payload['id_escuela'] = null;
        }
        if (array_key_exists('id_beca', $payload) && ($payload['id_beca'] === '' || $payload['id_beca'] === null)) {
            $payload['id_beca'] = null;
        }
        $request->merge($payload);

        $validated = $request->validate([
            'numero_matricula' => ['required', 'integer', 'min:1'],
            'id_especialidad' => ['required', 'integer', 'exists:tblespecialidades,id'],
            'semestre' => ['required', 'integer', 'min:1', 'max:20'],
            'nombres' => ['required', 'string'],
            'apellido_paterno' => ['required', 'string', 'max:100'],
            'apellido_materno' => ['nullable', 'string', 'max:100'],
            'id_escuela' => ['nullable', 'integer', 'exists:tblescuelas,id'],
            'id_empresa' => ['nullable', 'integer', 'exists:tblga_empresas,id'],
            'fecha_nacimiento' => ['nullable', 'date'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'correo' => ['required', 'email', 'max:150'],
            'estado' => ['nullable', 'string', 'max:50'],
            'id_beca' => ['nullable', 'integer'],
        ]);

        $rawEscuela = $validated['id_escuela'] ?? null;
        $validated['id_escuela'] = ($rawEscuela === '' || $rawEscuela === null)
            ? null
            : (string) $rawEscuela;
        $rawEmpresa = $validated['id_empresa'] ?? null;
        $validated['id_empresa'] = ($rawEmpresa === '' || $rawEmpresa === null)
            ? null
            : (int) $rawEmpresa;

        $validated['nunero_matricula'] = $validated['numero_matricula'];
        unset($validated['numero_matricula']);

        return $validated;
    }

    private function normalizarHeader(string $txt): string
    {
        $t = mb_strtolower(trim($txt), 'UTF-8');
        $reemplazos = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ];
        $t = strtr($t, $reemplazos);
        $t = preg_replace('/[^a-z0-9]+/u', '_', $t) ?? '';
        return trim($t, '_');
    }

    private function valorFila(array $row, array $indexByHeader, array $keys): ?string
    {
        foreach ($keys as $k) {
            if (!array_key_exists($k, $indexByHeader)) {
                continue;
            }
            $idx = $indexByHeader[$k];
            $val = $row[$idx] ?? null;
            if ($val === null) {
                return null;
            }
            return trim((string) $val);
        }
        // Fallback flexible por similitud de encabezado (ej: fecha_de_nacimientc).
        foreach ($keys as $k) {
            foreach ($indexByHeader as $hdr => $idx) {
                if (str_contains($hdr, $k) || str_contains($k, $hdr)) {
                    $val = $row[$idx] ?? null;
                    if ($val === null) {
                        return null;
                    }
                    return trim((string) $val);
                }
            }
        }
        return null;
    }

    private function filaVacia(array $row): bool
    {
        foreach ($row as $v) {
            if (trim((string) $v) !== '') {
                return false;
            }
        }
        return true;
    }

    private function parseFechaExcel(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $raw = trim((string) $value);

        if (is_numeric($raw)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $raw)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        $formatos = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'm/d/Y', 'm-d-Y'];
        foreach ($formatos as $f) {
            $dt = \DateTime::createFromFormat($f, $raw);
            if ($dt instanceof \DateTime) {
                return $dt->format('Y-m-d');
            }
        }

        $ts = strtotime($raw);
        if ($ts === false) {
            return null;
        }
        return date('Y-m-d', $ts);
    }

    /**
     * @return array<string, int>
     */
    private function obtenerRequisitosDocumentosPorTipo(): array
    {
        if (! Schema::hasTable('tblga_requisitos_documentos_det')) {
            return ['alumno' => [], 'tutor' => []];
        }

        $rows = DB::table('tblga_requisitos_documentos_det')
            ->whereIn('tipo_persona', ['alumno', 'tutor'])
            ->get(['tipo_persona', 'id_documento']);

        $out = ['alumno' => [], 'tutor' => []];
        foreach ($rows as $row) {
            $tipo = (string) $row->tipo_persona;
            $docId = (int) $row->id_documento;
            if (! isset($out[$tipo])) {
                $out[$tipo] = [];
            }
            $out[$tipo][$docId] = $docId;
        }
        $out['alumno'] = array_values($out['alumno']);
        $out['tutor'] = array_values($out['tutor']);

        return $out;
    }
}
