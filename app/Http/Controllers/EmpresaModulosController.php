<?php

namespace App\Http\Controllers;

use App\Models\EmpresaPerfil;
use App\Models\Empresas;
use App\Models\EmpresaVista;
use App\Models\perfiles;
use App\Models\Vistas;
use App\Traits\EmpresaCatalogoTrait;
use App\Traits\MenuTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class EmpresaModulosController extends Controller
{
    use MenuTrait;
    use EmpresaCatalogoTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        if ($this->esSesionMasterEmpresa()) {
            abort(403, 'El catálogo de módulos lo configura el administrador del sistema.');
        }
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        $empresasQuery = Empresas::query()->orderBy('nombre_empresa');
        if (Schema::hasColumn('tblempresas', 'estado')) {
            $empresasQuery->where('estado', 'A');
        }
        $empresas = $empresasQuery->get();

        $vistasPorEmpresa = Schema::hasTable('tblempresa_vistas')
            ? EmpresaVista::query()->selectRaw('id_empresa, COUNT(*) as total')->groupBy('id_empresa')->pluck('total', 'id_empresa')
            : collect();
        $perfilesPorEmpresa = Schema::hasTable('tblempresa_perfiles')
            ? EmpresaPerfil::query()->selectRaw('id_empresa, COUNT(*) as total')->groupBy('id_empresa')->pluck('total', 'id_empresa')
            : collect();

        $empresas = $empresas->map(function ($empresa) use ($vistasPorEmpresa, $perfilesPorEmpresa) {
            $empresa->total_vistas = (int) ($vistasPorEmpresa[$empresa->id] ?? 0);
            $empresa->total_perfiles = (int) ($perfilesPorEmpresa[$empresa->id] ?? 0);
            return $empresa;
        });

        $vistasAgrupadas = Vistas::query()
            ->join('tbldepartamentos', 'tbldepartamentos.id', '=', 'tblvistas.iddepartamento')
            ->select(
                'tblvistas.id',
                'tblvistas.nombre',
                'tbldepartamentos.id as id_departamento',
                'tbldepartamentos.nombre as departamento'
            )
            ->when(Schema::hasColumn('tblvistas', 'estado'), function ($q) {
                $q->where('tblvistas.estado', 1);
            })
            ->when(Schema::hasColumn('tbldepartamentos', 'orden'), function ($q) {
                $q->orderBy('tbldepartamentos.orden');
            })
            ->when(Schema::hasColumn('tblvistas', 'orden'), function ($q) {
                $q->orderBy('tblvistas.orden');
            })
            ->orderBy('tblvistas.nombre')
            ->get()
            ->groupBy('departamento');

        $varperfiles = perfiles::query()->orderBy('nombre')->get(['id', 'nombre', 'descripcion']);

        return view('sistemas.empresas', compact(
            'varpantallas',
            'varsubmenus',
            'empresas',
            'vistasAgrupadas',
            'varperfiles'
        ));
    }

    public function store(Request $request): JsonResponse
    {
        if ($this->esSesionMasterEmpresa()) {
            abort(403, 'El catálogo de módulos lo configura el administrador del sistema.');
        }
        $validated = $request->validate([
            'nombre_empresa' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:100'],
            'representada' => ['nullable', 'string', 'max:100'],
            'rfc' => ['nullable', 'string', 'max:13'],
            'direccion_fiscal' => ['nullable', 'string', 'max:255'],
        ]);

        $empresa = new Empresas();
        $empresa->nombre_empresa = trim($validated['nombre_empresa']);
        $empresa->descripcion = $validated['descripcion'] ?? null;
        $empresa->representada = $validated['representada'] ?? null;
        $empresa->rfc = $this->normalizarRfc($validated['rfc'] ?? null);
        $empresa->direccion_fiscal = $validated['direccion_fiscal'] ?? null;
        $empresa->efectivo = 0;
        if (Schema::hasColumn('tblempresas', 'created_by')) {
            $empresa->created_by = auth()->user()->name;
        }
        if (Schema::hasColumn('tblempresas', 'estado')) {
            $empresa->estado = 'A';
        }
        if (Schema::hasColumn('tblempresas', 'accesos_configurados')) {
            $empresa->accesos_configurados = 0;
        }
        $empresa->save();

        return response()->json([
            'message' => 'Empresa registrada correctamente.',
            'data' => [
                'id' => (int) $empresa->id,
                'nombre_empresa' => $empresa->nombre_empresa,
                'descripcion' => $empresa->descripcion,
                'total_vistas' => 0,
                'total_perfiles' => 0,
            ],
        ], 201);
    }

    public function catalogo(int $id): JsonResponse
    {
        if ($this->esSesionMasterEmpresa()) {
            abort(403, 'El catálogo de módulos lo configura el administrador del sistema.');
        }
        $empresa = Empresas::findOrFail($id);

        return response()->json([
            'data' => [
                'id' => (int) $empresa->id,
                'nombre_empresa' => $empresa->nombre_empresa,
                'accesos_configurados' => $this->catalogoAccesosActivo((int) $empresa->id),
                'vistas' => EmpresaVista::where('id_empresa', $empresa->id)
                    ->pluck('id_vista')
                    ->map(fn ($idVista) => (int) $idVista)
                    ->values(),
                'perfiles' => EmpresaPerfil::where('id_empresa', $empresa->id)
                    ->pluck('id_perfil')
                    ->map(fn ($idPerfil) => (int) $idPerfil)
                    ->values(),
                'superusuarios' => $this->superusuariosDeEmpresa((int) $empresa->id)
                    ->map(fn ($row) => $this->serializarUsuarioEmpresa($row))
                    ->values(),
                'usuarios' => $this->candidatosSuperusuarioEmpresa((int) $empresa->id)
                    ->map(fn ($row) => $this->serializarUsuarioEmpresa($row))
                    ->values(),
            ],
        ]);
    }

    public function guardarCatalogo(Request $request, int $id): JsonResponse
    {
        if ($this->esSesionMasterEmpresa()) {
            abort(403, 'El catálogo de módulos lo configura el administrador del sistema.');
        }
        $empresa = Empresas::findOrFail($id);

        $validated = $request->validate([
            'vistas' => ['nullable', 'array'],
            'vistas.*' => ['integer'],
            'perfiles' => ['nullable', 'array'],
            'perfiles.*' => ['integer'],
        ]);

        $vistas = collect($validated['vistas'] ?? [])
            ->map(fn ($idVista) => (int) $idVista)
            ->filter()
            ->unique()
            ->values();
        $perfilesIds = collect($validated['perfiles'] ?? [])
            ->map(fn ($idPerfil) => (int) $idPerfil)
            ->filter()
            ->unique()
            ->values();

        $usuario = auth()->user()->name;

        DB::transaction(function () use ($empresa, $vistas, $perfilesIds, $usuario) {
            EmpresaVista::where('id_empresa', $empresa->id)
                ->when($vistas->isNotEmpty(), fn ($q) => $q->whereNotIn('id_vista', $vistas))
                ->delete();

            if ($vistas->isEmpty()) {
                EmpresaVista::where('id_empresa', $empresa->id)->delete();
            } else {
                foreach ($vistas as $idVista) {
                    EmpresaVista::firstOrCreate(
                        ['id_empresa' => $empresa->id, 'id_vista' => $idVista],
                        ['created_by' => $usuario]
                    );
                }
            }

            EmpresaPerfil::where('id_empresa', $empresa->id)
                ->when($perfilesIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id_perfil', $perfilesIds))
                ->delete();

            if ($perfilesIds->isEmpty()) {
                EmpresaPerfil::where('id_empresa', $empresa->id)->delete();
            } else {
                foreach ($perfilesIds as $idPerfil) {
                    EmpresaPerfil::firstOrCreate(
                        ['id_empresa' => $empresa->id, 'id_perfil' => $idPerfil],
                        ['created_by' => $usuario]
                    );
                }
            }

            if (Schema::hasColumn('tblempresas', 'accesos_configurados')) {
                $empresa->accesos_configurados = 1;
                if (Schema::hasColumn('tblempresas', 'updated_by')) {
                    $empresa->updated_by = $usuario;
                }
                $empresa->save();
            }
        });

        return response()->json([
            'message' => 'Catálogo guardado para ' . $empresa->nombre_empresa . '.',
            'data' => [
                'id' => (int) $empresa->id,
                'total_vistas' => $vistas->count(),
                'total_perfiles' => $perfilesIds->count(),
            ],
        ]);
    }

    public function asignarSuperusuario(Request $request, int $id): JsonResponse
    {
        if ($this->esSesionMasterEmpresa()) {
            abort(403, 'El superusuario de empresa lo asigna el administrador del ERP.');
        }

        $empresa = Empresas::findOrFail($id);
        $validated = $request->validate([
            'id_usuario' => ['required', 'integer'],
            'asignar_perfiles' => ['nullable', 'boolean'],
            'perfiles' => ['nullable', 'array'],
            'perfiles.*' => ['integer'],
        ]);

        $perfiles = [];
        if ($request->boolean('asignar_perfiles', true)) {
            $perfiles = collect($validated['perfiles'] ?? [])
                ->map(fn ($idPerfil) => (int) $idPerfil)
                ->filter()
                ->unique()
                ->values()
                ->all();
            if (!$perfiles) {
                $perfiles = $this->perfilesPermitidosEmpresa((int) $empresa->id) ?? [];
            }
        }

        try {
            $data = $this->promoverSuperusuarioEmpresa(
                (int) $validated['id_usuario'],
                (int) $empresa->id,
                $perfiles
            );
        } catch (\InvalidArgumentException $ex) {
            return response()->json(['message' => $ex->getMessage()], 422);
        }

        $mensaje = $data['name'] . ' quedó como superusuario de ' . $empresa->nombre_empresa . '.';
        if (($data['perfiles_asignados'] ?? 0) > 0) {
            $mensaje .= ' Se le asignaron los módulos vendidos.';
        }

        return response()->json([
            'message' => $mensaje,
            'data' => [
                'superusuarios' => $this->superusuariosDeEmpresa((int) $empresa->id)
                    ->map(fn ($row) => $this->serializarUsuarioEmpresa($row))
                    ->values(),
                'usuarios' => $this->candidatosSuperusuarioEmpresa((int) $empresa->id)
                    ->map(fn ($row) => $this->serializarUsuarioEmpresa($row))
                    ->values(),
            ],
        ]);
    }

    public function quitarSuperusuario(int $id, int $idUsuario): JsonResponse
    {
        if ($this->esSesionMasterEmpresa()) {
            abort(403, 'El superusuario de empresa lo asigna el administrador del ERP.');
        }

        $empresa = Empresas::findOrFail($id);

        try {
            $this->quitarRolSuperusuarioEmpresa($idUsuario, (int) $empresa->id);
        } catch (\InvalidArgumentException $ex) {
            return response()->json(['message' => $ex->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Se quitó el superusuario de ' . $empresa->nombre_empresa . '.',
            'data' => [
                'superusuarios' => $this->superusuariosDeEmpresa((int) $empresa->id)
                    ->map(fn ($row) => $this->serializarUsuarioEmpresa($row))
                    ->values(),
                'usuarios' => $this->candidatosSuperusuarioEmpresa((int) $empresa->id)
                    ->map(fn ($row) => $this->serializarUsuarioEmpresa($row))
                    ->values(),
            ],
        ]);
    }

    private function serializarUsuarioEmpresa($row): array
    {
        return [
            'id' => (int) $row->id,
            'name' => $row->name,
            'email' => $row->email ?? '',
            'tipo' => $row->tipo,
            'id_empresa' => isset($row->id_empresa) ? ((int) $row->id_empresa ?: null) : null,
            'empresa' => $row->nombre_empresa ?? null,
        ];
    }

    private function normalizarRfc(?string $valor): ?string
    {
        $rfc = strtoupper(preg_replace('/[^A-Za-z0-9Ñ&]/', '', (string) ($valor ?? '')) ?? '');
        $rfc = substr($rfc, 0, 13);

        return $rfc === '' ? null : $rfc;
    }
}
