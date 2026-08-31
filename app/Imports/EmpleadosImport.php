<?php

namespace App\Imports;

use App\Imports\Concerns\ImportExcelHelpers;
use App\Imports\Concerns\ResolvesCatalogReferences;
use App\Models\Empleados;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EmpleadosImport implements ToCollection, WithHeadingRow, WithCalculatedFormulas
{
    use ImportExcelHelpers;
    use ResolvesCatalogReferences;

    protected int $creados = 0;

    protected int $actualizados = 0;

    /** @var array<int, string> */
    protected array $errores = [];

    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $index => $row) {
                $excelRow = $index + 2;
                $data = $this->mapRowByHeading($row->toArray());

                if ($this->filaVacia($data)) {
                    continue;
                }

                try {
                    $this->procesarFila($data, $excelRow);
                } catch (\Throwable $e) {
                    $this->errores[] = "Fila {$excelRow}: {$e->getMessage()}";
                }
            }
        });
    }

    protected function procesarFila(array $row, int $excelRow): void
    {
        $id = $this->extraerIdNumerico($row, ['id', '0']);
        $primerNombre = $this->valorFila($row, ['primer_nombre']);
        $apellidoPaterno = $this->valorFila($row, ['apellido_paterno']);

        if (!$id && (!$primerNombre || !$apellidoPaterno)) {
            $this->errores[] = "Fila {$excelRow}: se requiere id o nombre completo mínimo (primer_nombre, apellido_paterno).";
            return;
        }

        $idCiudad = $this->resolveCiudadId($this->valorFila($row, ['idciudad', 'ciudad']));
        $idPuesto = $this->resolvePuestoId($this->valorFila($row, ['idpuesto', 'puesto']));
        $idSucursal = $this->resolveSucursalId($this->valorFila($row, ['idsucursal', 'sucursal']), $idCiudad);
        $idBanco = $this->resolveBancoId($this->valorFila($row, ['idbanco', 'banco']));

        $esNuevo = $id ? !Empleados::query()->where('id', $id)->exists() : true;
        if ($esNuevo) {
            $this->creados++;
            $empleado = new Empleados();
            if ($id) {
                $empleado->id = $id;
            }
        } else {
            $this->actualizados++;
            $empleado = Empleados::query()->findOrFail($id);
        }

        $this->asignarCampo($empleado, 'primer_nombre', $primerNombre, $esNuevo);
        $this->asignarCampo($empleado, 'segundo_nombre', $this->valorFila($row, ['segundo_nombre']), $esNuevo, ' ');
        $this->asignarCampo($empleado, 'apellido_paterno', $apellidoPaterno, $esNuevo);
        $this->asignarCampo($empleado, 'apellido_materno', $this->valorFila($row, ['apellido_materno']), $esNuevo);
        $this->asignarCampo($empleado, 'telefono', $this->valorFila($row, ['telefono']), $esNuevo);
        $this->asignarCampo($empleado, 'correo', $this->valorFila($row, ['correo']), $esNuevo);
        $this->asignarCampo($empleado, 'tipo_contratacion', $this->parseTipoContratacion($this->valorFila($row, ['tipo_contratacion'])), $esNuevo);
        $this->asignarCampo($empleado, 'grado_estudio', $this->valorFila($row, ['grado_estudio']), $esNuevo);
        $this->asignarCampo($empleado, 'nacionalidad', $this->valorFila($row, ['nacionalidad']), $esNuevo);
        $this->asignarCampo($empleado, 'calle', $this->valorFila($row, ['calle']), $esNuevo);
        $this->asignarCampo($empleado, 'colonia', $this->valorFila($row, ['colonia']), $esNuevo);
        $this->asignarCampo($empleado, 'numero_interior', $this->valorFila($row, ['numero_interior']), $esNuevo, '0');
        $this->asignarCampo($empleado, 'numero_exterior', $this->valorFila($row, ['numero_exterior']), $esNuevo);
        $this->asignarCampo($empleado, 'codigo_postal', $this->valorFila($row, ['codigo_postal']), $esNuevo);
        $this->asignarCampo($empleado, 'sexo', $this->valorFila($row, ['sexo']), $esNuevo);
        $this->asignarCampo($empleado, 'nss', $this->valorFila($row, ['nss']) ?? (isset($row['nss']) ? trim((string) $row['nss']) : null), $esNuevo);
        $this->asignarCampo($empleado, 'rfc', $this->valorFila($row, ['rfc']) ?? (isset($row['rfc']) ? trim((string) $row['rfc']) : null), $esNuevo);
        $this->asignarCampo($empleado, 'curp', $this->valorFila($row, ['curp']) ?? (isset($row['curp']) ? trim((string) $row['curp']) : null), $esNuevo);
        $this->asignarCampo($empleado, 'tipo_sangre', $this->valorFila($row, ['tipo_sangre']), $esNuevo);
        $this->asignarCampo($empleado, 'contacto_emergencia', $this->valorFila($row, ['contacto_emergencia', 'contacto_emergencias']), $esNuevo);
        $this->asignarCampo($empleado, 'telefono_emergencia', $this->valorFila($row, ['telefono_emergencia']), $esNuevo);
        $this->asignarCampo($empleado, 'descripcion_estado', $this->valorFila($row, ['descripcion_estado']), $esNuevo, 'ALTA EMPLEADO');
        $this->asignarCampo($empleado, 'estado_civil', $this->valorFila($row, ['estado_civil']), $esNuevo);
        $this->asignarCampo($empleado, 'colonia_f', $this->valorFila($row, ['colonia_f', 'colonia_fiscal']), $esNuevo);
        $this->asignarCampo($empleado, 'calle_f', $this->valorFila($row, ['calle_f', 'calle_fiscal']), $esNuevo);
        $this->asignarCampo($empleado, 'no_interior_f', $this->valorFila($row, ['no_interior_f', 'no_interior_fiscal']), $esNuevo);
        $this->asignarCampo($empleado, 'no_exterior_f', $this->valorFila($row, ['no_exterior_f', 'no_exterior_fiscal']), $esNuevo);
        $this->asignarCampo($empleado, 'codigo_postal_f', $this->valorFila($row, ['codigo_postal_f', 'codigo_postal_fiscal']), $esNuevo);
        $this->asignarCampo($empleado, 'foto', $this->valorFila($row, ['foto']), $esNuevo, '1');
        $this->asignarCampo($empleado, 'nombre_foto', $this->valorFila($row, ['nombre_foto']), $esNuevo, '0.jpg');
        $this->asignarCampo($empleado, 'archivo_baja', $this->valorFila($row, ['archivo_baja']), $esNuevo, '0');
        $this->asignarCampo($empleado, 'ruta_contrato', $this->valorFila($row, ['ruta_contrato']), $esNuevo);
        $this->asignarCampo($empleado, 'status_contrato', $this->valorFila($row, ['status_contrato']), $esNuevo);
        $this->asignarCampo($empleado, 'tipo_tranferencia', $this->valorFila($row, ['tipo_tranferencia', 'tipo_transferencia']), $esNuevo, '1');

        $fechaNacimiento = $this->parseFechaExcel($row['fecha_nacimiento'] ?? $this->valorFila($row, ['fecha_nacimiento']));
        if ($fechaNacimiento !== null || $esNuevo) {
            $empleado->fecha_nacimiento = $fechaNacimiento;
        }

        $fechaIngreso = $this->parseFechaExcel($row['fecha_ingreso'] ?? $row['fecha_alta'] ?? $this->valorFila($row, ['fecha_ingreso', 'fecha_alta']));
        if ($fechaIngreso !== null || $esNuevo) {
            $empleado->fecha_ingreso = $fechaIngreso;
        }

        $fechaBaja = $this->parseFechaExcel($row['fecha_baja'] ?? $this->valorFila($row, ['fecha_baja']));
        if ($fechaBaja !== null) {
            $empleado->fecha_baja = $fechaBaja;
        }

        $estado = $this->parseEstadoEmpleado($this->valorFila($row, ['estado']));
        if ($estado !== null || $esNuevo) {
            $empleado->estado = $estado ?? 'A';
        }

        if ($idPuesto !== null) {
            $empleado->idpuesto = $idPuesto;
        }
        if ($idSucursal !== null) {
            $empleado->idsucursal = $idSucursal;
        }
        if ($idCiudad !== null) {
            $empleado->idciudad = $idCiudad;
        }
        if ($idBanco !== null) {
            $empleado->idbanco = $idBanco;
        }

        if ($esNuevo) {
            $empleado->created_by = $this->valorFila($row, ['created_by']) ?? $this->usuarioImportacion();
        } else {
            $empleado->updated_by = $this->valorFila($row, ['updated_by']) ?? $this->usuarioImportacion();
        }

        $empleado->save();
    }

    protected function asignarCampo(object $model, string $campo, ?string $valor, bool $esNuevo, ?string $default = null): void
    {
        if ($valor !== null) {
            $model->{$campo} = $valor;
            return;
        }

        if ($esNuevo && $default !== null) {
            $model->{$campo} = $default;
        }
    }

    /** @return array{creados:int,actualizados:int,errores:array<int,string>} */
    public function getResumen(): array
    {
        return [
            'creados' => $this->creados,
            'actualizados' => $this->actualizados,
            'errores' => $this->errores,
        ];
    }
}
