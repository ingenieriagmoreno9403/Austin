<?php

namespace App\Traits;

use App\Models\EmpresaPerfil;
use App\Models\EmpresaVista;
use App\Models\User;
use App\Models\usuario_perfiles;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait EmpresaCatalogoTrait
{
    public function empresaIdDeUsuario(int $idUsuario): ?int
    {
        if (Schema::hasColumn('users', 'id_empresa')) {
            $idDirecto = (int) (DB::table('users')->where('id', $idUsuario)->value('id_empresa') ?? 0);
            if ($idDirecto > 0) {
                return $idDirecto;
            }
        }

        $row = DB::selectOne(
            'SELECT users.tipo, users.id_tipo, tblsucursales.idempresa as id_empresa
            FROM users
            LEFT JOIN tblempleados ON users.idempleado = tblempleados.id
            LEFT JOIN tblsucursales ON tblempleados.idsucursal = tblsucursales.id
            WHERE users.id = ?
            LIMIT 1;',
            [$idUsuario]
        );

        if (!$row) {
            return null;
        }

        $idSucursalEmpresa = (int) ($row->id_empresa ?? 0);
        if ($idSucursalEmpresa > 0) {
            return $idSucursalEmpresa;
        }

        return null;
    }

    public function empresaIdSesion(): ?int
    {
        $usuario = auth()->user();
        if (!$usuario) {
            return null;
        }

        return $this->empresaIdDeUsuario((int) $usuario->id);
    }

    public function esSesionMasterEmpresa(): bool
    {
        $usuario = auth()->user();
        if (!$usuario) {
            return false;
        }

        $tipo = strtolower((string) ($usuario->tipo ?? ''));
        if (!in_array($tipo, ['empresa', 'master'], true)) {
            return false;
        }

        return (int) ($usuario->id_empresa ?? 0) > 0;
    }

    public function catalogoAccesosActivo(?int $idEmpresa): bool
    {
        if (!$idEmpresa || !Schema::hasTable('tblempresas')) {
            return false;
        }

        if (!Schema::hasColumn('tblempresas', 'accesos_configurados')) {
            return EmpresaPerfil::where('id_empresa', $idEmpresa)->exists()
                || EmpresaVista::where('id_empresa', $idEmpresa)->exists();
        }

        $valor = DB::table('tblempresas')->where('id', $idEmpresa)->value('accesos_configurados');

        return (int) $valor === 1;
    }

    public function perfilesPermitidosEmpresa(?int $idEmpresa): ?array
    {
        if (!$idEmpresa || !$this->catalogoAccesosActivo($idEmpresa)) {
            return null;
        }

        return EmpresaPerfil::where('id_empresa', $idEmpresa)
            ->pluck('id_perfil')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function vistasPermitidasEmpresa(?int $idEmpresa): ?array
    {
        if (!$idEmpresa || !$this->catalogoAccesosActivo($idEmpresa)) {
            return null;
        }

        return EmpresaVista::where('id_empresa', $idEmpresa)
            ->pluck('id_vista')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function filtrarUsuariosDeEmpresa($usuarios, ?int $idEmpresa): Collection
    {
        $lista = collect($usuarios);
        if (!$idEmpresa) {
            return $lista;
        }

        return $lista->filter(function ($usuario) use ($idEmpresa) {
            return (int) ($usuario->id_empresa ?? 0) === (int) $idEmpresa;
        })->values();
    }

    public function esUsuarioAdminErp($usuario): bool
    {
        if (!$usuario) {
            return false;
        }

        $tipo = strtolower((string) ($usuario->tipo ?? ''));

        return $tipo === 'master' && (int) ($usuario->id_empresa ?? 0) === 0;
    }

    public function superusuariosDeEmpresa(int $idEmpresa): Collection
    {
        if (!Schema::hasColumn('users', 'id_empresa')) {
            return collect();
        }

        return collect(DB::table('users')
            ->where('id_empresa', $idEmpresa)
            ->whereIn('tipo', ['empresa', 'master'])
            ->where(function ($q) {
                $q->whereNull('estado_user')->orWhere('estado_user', 'A');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'tipo', 'id_empresa']));
    }

    public function candidatosSuperusuarioEmpresa(int $idEmpresa): Collection
    {
        if (!Schema::hasColumn('users', 'id_empresa')) {
            return collect();
        }

        $superIds = $this->superusuariosDeEmpresa($idEmpresa)->pluck('id')->all();

        $query = DB::table('users')
            ->leftJoin('tblempresas', 'tblempresas.id', '=', 'users.id_empresa')
            ->where(function ($q) {
                $q->whereNull('users.estado_user')->orWhere('users.estado_user', 'A');
            })
            ->where(function ($q) {
                $q->whereRaw('LOWER(COALESCE(users.tipo, "")) <> ?', ['master'])
                    ->orWhere(function ($q2) {
                        $q2->whereRaw('LOWER(users.tipo) = ?', ['master'])
                            ->whereNotNull('users.id_empresa')
                            ->where('users.id_empresa', '>', 0);
                    });
            })
            ->when($superIds, fn ($q) => $q->whereNotIn('users.id', $superIds))
            ->orderBy('users.name')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'users.tipo',
                'users.id_empresa',
                'tblempresas.nombre_empresa'
            );

        return collect($query->get());
    }

    public function promoverSuperusuarioEmpresa(int $idUsuario, int $idEmpresa, array $perfiles = []): array
    {
        if (!Schema::hasColumn('users', 'id_empresa')) {
            throw new \RuntimeException('Falta el campo id_empresa en users.');
        }

        $user = User::find($idUsuario);
        if (!$user) {
            throw new \InvalidArgumentException('No se encontró el usuario.');
        }

        if ($this->esUsuarioAdminErp($user)) {
            throw new \InvalidArgumentException('Ese usuario es administrador del ERP. No puede ser superusuario de un cliente.');
        }

        $tipo = strtolower((string) ($user->tipo ?? ''));
        if (!in_array($tipo, ['empresa', 'master'], true)) {
            $user->tipo = 'empresa';
        }

        $user->id_empresa = $idEmpresa;
        if (Schema::hasColumn('users', 'updated_by') && auth()->user()) {
            $user->updated_by = auth()->user()->name;
        }
        $user->save();

        $asignados = $this->asignarPerfilesAUsuario($idUsuario, $perfiles);

        return [
            'id' => (int) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'tipo' => $user->tipo,
            'perfiles_asignados' => $asignados,
        ];
    }

    public function quitarRolSuperusuarioEmpresa(int $idUsuario, ?int $idEmpresa = null): void
    {
        $user = User::find($idUsuario);
        if (!$user) {
            throw new \InvalidArgumentException('No se encontró el usuario.');
        }

        if ($this->esUsuarioAdminErp($user)) {
            throw new \InvalidArgumentException('No se puede quitar el rol del administrador del ERP.');
        }

        if ($idEmpresa && (int) ($user->id_empresa ?? 0) !== (int) $idEmpresa) {
            throw new \InvalidArgumentException('Ese usuario no es superusuario de esta empresa.');
        }

        $tipo = strtolower((string) ($user->tipo ?? ''));
        $user->id_empresa = null;
        if ($tipo === 'master') {
            $user->tipo = 'empresa';
        }
        if (Schema::hasColumn('users', 'updated_by') && auth()->user()) {
            $user->updated_by = auth()->user()->name;
        }
        $user->save();
    }

    public function asignarPerfilesAUsuario(int $idUsuario, array $perfiles): int
    {
        $ids = collect($perfiles)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return 0;
        }

        $agregados = 0;
        foreach ($ids as $idPerfil) {
            $existe = usuario_perfiles::where('id_usuario', $idUsuario)
                ->where('id_perfil', $idPerfil)
                ->exists();
            if ($existe) {
                continue;
            }

            $row = new usuario_perfiles();
            $row->id_usuario = $idUsuario;
            $row->id_perfil = $idPerfil;
            $row->created_by = auth()->user()->name ?? 'sistema';
            $row->save();
            $agregados++;
        }

        return $agregados;
    }
}
