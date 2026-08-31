<?php

namespace App\Imports\Concerns;

use App\Models\bancos;
use App\Models\Ciudades;
use App\Models\Empresas;
use App\Models\Puestos;
use App\Models\Sucursales;
use App\Models\tipo_descuento_infonavit;
use Illuminate\Support\Facades\DB;

trait ResolvesCatalogReferences
{
    /** @var array<string, array<string, int>> */
    protected array $catalogCache = [];

    protected function resolvePuestoId(?string $valor): ?int
    {
        return $this->resolveCatalogId(
            Puestos::class,
            'puesto',
            $valor,
            function (string $nombre) {
                $puesto = new Puestos();
                $puesto->nombre = $nombre;
                $puesto->descripcion = 'Importado desde Excel';
                $puesto->estado = 'A';
                $puesto->created_by = $this->usuarioImportacion();
                $puesto->save();

                return (int) $puesto->id;
            }
        );
    }

    protected function resolveCiudadId(?string $valor): ?int
    {
        return $this->resolveCatalogId(
            Ciudades::class,
            'ciudad',
            $valor,
            function (string $nombre) {
                $idEstado = (int) (DB::table('tblestados')->orderBy('id')->value('id') ?? 1);

                $ciudad = new Ciudades();
                $ciudad->nombre = $nombre;
                $ciudad->idestado = $idEstado;
                $ciudad->created_by = $this->usuarioImportacion();
                $ciudad->save();

                return (int) $ciudad->id;
            },
            'nombre'
        );
    }

    protected function resolveBancoId(?string $valor): ?int
    {
        return $this->resolveCatalogId(
            bancos::class,
            'banco',
            $valor,
            function (string $nombre) {
                $banco = new bancos();
                $banco->nombre = $nombre;
                $banco->descripcion = 'Importado desde Excel';
                $banco->save();

                return (int) $banco->id;
            }
        );
    }

    protected function resolveSucursalId(?string $valor, ?int $idCiudad = null): ?int
    {
        if ($valor === null || trim($valor) === '') {
            return null;
        }

        return $this->resolveCatalogId(
            Sucursales::class,
            'sucursal',
            $valor,
            function (string $nombre) use ($idCiudad) {
                $consecutivo = (int) (DB::table('tblsucursales')->max('consecutivo') ?? 0) + 1;
                $idEmpresa = (int) (DB::table('tblempresas')->where('estado', 'A')->orderBy('id')->value('id') ?? 1);
                $ciudadId = $idCiudad ?? (int) (DB::table('tblciudades')->orderBy('id')->value('id') ?? 1);

                $sucursal = new Sucursales();
                $sucursal->consecutivo = $consecutivo;
                $sucursal->nombre = $nombre;
                $sucursal->telefono = '0';
                $sucursal->idciudad = $ciudadId;
                $sucursal->idempresa = $idEmpresa;
                $sucursal->colonia = 'N/A';
                $sucursal->calle = 'N/A';
                $sucursal->numero_interior = '0';
                $sucursal->numero_exterior = 'S/N';
                $sucursal->codigo_postal = '00000';
                $sucursal->estado = 'A';
                $sucursal->created_by = $this->usuarioImportacion();
                $sucursal->save();

                return (int) $sucursal->id;
            }
        );
    }

    protected function resolveEmpresaId(?string $valor): ?int
    {
        if ($valor === null || trim($valor) === '') {
            return null;
        }

        return $this->resolveCatalogId(
            Empresas::class,
            'empresa',
            $valor,
            function (string $nombre) {
                $empresa = new Empresas();
                $empresa->nombre_empresa = $nombre;
                $empresa->representada = $nombre;
                $empresa->descripcion = 'Importado desde Excel';
                $empresa->rfc = 'XAXX010101000';
                $empresa->direccion_fiscal = 'N/A';
                $empresa->efectivo = 0;
                $empresa->estado = 'A';
                $empresa->created_by = $this->usuarioImportacion();
                $empresa->save();

                return (int) $empresa->id;
            },
            'nombre_empresa'
        );
    }

    protected function resolveTipoInfonavitId(?string $valor): ?int
    {
        if ($valor === null || trim($valor) === '') {
            return null;
        }

        return $this->resolveCatalogId(
            tipo_descuento_infonavit::class,
            'tipoinfonavit',
            $valor,
            function (string $nombre) {
                $tipo = new tipo_descuento_infonavit();
                $tipo->Nombre = $nombre;
                $tipo->save();

                return (int) $tipo->id;
            },
            'Nombre'
        );
    }

    /**
     * @param  class-string  $modelClass
     */
    protected function resolveCatalogId(
        string $modelClass,
        string $cacheGroup,
        ?string $valor,
        callable $createCallback,
        string $nameColumn = 'nombre'
    ): ?int {
        if ($valor === null || trim($valor) === '') {
            return null;
        }

        $raw = trim($valor);
        $cacheKey = $this->normalizarHeader($raw);

        if (isset($this->catalogCache[$cacheGroup][$cacheKey])) {
            return $this->catalogCache[$cacheGroup][$cacheKey];
        }

        if (is_numeric($raw)) {
            $id = (int) $raw;
            $exists = $modelClass::query()->where('id', $id)->exists();
            if ($exists) {
                $this->catalogCache[$cacheGroup][$cacheKey] = $id;

                return $id;
            }
        }

        $record = $modelClass::query()
            ->whereRaw("LOWER(TRIM({$nameColumn})) = ?", [mb_strtolower($raw, 'UTF-8')])
            ->first();

        if (!$record) {
            $records = $modelClass::query()->get([$nameColumn, 'id']);
            foreach ($records as $item) {
                if ($this->normalizarHeader((string) $item->{$nameColumn}) === $cacheKey) {
                    $record = $item;
                    break;
                }
            }
        }

        if ($record) {
            $this->catalogCache[$cacheGroup][$cacheKey] = (int) $record->id;

            return (int) $record->id;
        }

        $newId = (int) $createCallback($raw);
        $this->catalogCache[$cacheGroup][$cacheKey] = $newId;

        return $newId;
    }
}
