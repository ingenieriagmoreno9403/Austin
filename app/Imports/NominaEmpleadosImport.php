<?php

namespace App\Imports;

use App\Imports\Concerns\ImportExcelHelpers;
use App\Imports\Concerns\ResolvesCatalogReferences;
use App\Models\Empleados;
use App\Models\nominas;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class NominaEmpleadosImport implements ToCollection, WithHeadingRow, WithCalculatedFormulas
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
        $idEmpleado = $this->extraerIdNumerico($row, [
            'idempleado',
            'id_empleado',
            'no_empleado',
            'id',
            '0',
        ]);

        if (!$idEmpleado) {
            $this->errores[] = "Fila {$excelRow}: idempleado inválido o vacío.";
            return;
        }
        if (!Empleados::query()->where('id', $idEmpleado)->exists()) {
            $this->errores[] = "Fila {$excelRow}: no existe el empleado con id {$idEmpleado}.";
            return;
        }

        $idEmpresa = $this->resolveEmpresaId($this->valorFila($row, ['idempresa', 'empresa']));
        $idBanco = $this->resolveBancoId($this->valorFila($row, ['idbancos', 'idbanco', 'banco']));
        $idTipoInfonavit = $this->resolveTipoInfonavitId($this->valorFila($row, ['id_tipoinfonavit', 'tipo_infonavit', 'tipo_infonavit']));

        $nomina = nominas::query()->where('idempleado', $idEmpleado)->first();
        $esNuevo = !$nomina;

        if ($esNuevo) {
            $nomina = new nominas();
            $nomina->idempleado = $idEmpleado;
            $this->creados++;
        } else {
            $this->actualizados++;
        }

        if ($idEmpresa !== null) {
            $nomina->idempresa = $idEmpresa;
        } elseif ($esNuevo) {
            $nomina->idempresa = (int) (DB::table('tblempresas')->where('estado', 'A')->orderBy('id')->value('id') ?? 1);
        }

        if ($idBanco !== null) {
            $nomina->idbancos = $idBanco;
        }

        if ($idTipoInfonavit !== null) {
            $nomina->id_tipoinfonavit = $idTipoInfonavit;
        } elseif ($esNuevo) {
            $nomina->id_tipoinfonavit = (int) (DB::table('tbltipoinfonavit')->orderBy('id')->value('id') ?? 1);
        }

        $salarioBruto = $this->parseDecimal($row['salario_bruto'] ?? $row['sueldo_mensual'] ?? null);
        if ($salarioBruto !== null) {
            $nomina->salario_bruto = $salarioBruto;
        }

        $salarioFijo = $this->parseDecimal($row['salario_fijo'] ?? $row['salario_diario'] ?? null);
        if ($salarioFijo !== null) {
            $nomina->salario_fijo = $salarioFijo;
        }

        $excedente = $this->parseDecimal($row['excedente'] ?? $row['salario_diario_excedente'] ?? null);
        if ($excedente !== null) {
            $nomina->excedente = $excedente;
        }

        $efectivo = $this->parseDecimal($row['efectivo'] ?? $row['salario_diario_efectivo'] ?? null);
        if ($efectivo !== null) {
            $nomina->efectivo = $efectivo;
        }

        $factorSua = $this->parseDecimal($row['factor_sua'] ?? $row['cuota_fija'] ?? null);
        if ($factorSua !== null) {
            $nomina->factor_sua = $factorSua;
        }

        $descuentoQuincenal = $this->parseDecimal($row['descuento_quincenal'] ?? null);
        if ($descuentoQuincenal !== null) {
            $nomina->descuento_quincenal = $descuentoQuincenal;
        }

        $deudoresFiscal = $this->parseDecimal($row['deudores_fiscal'] ?? null);
        if ($deudoresFiscal !== null) {
            $nomina->deudores_fiscal = $deudoresFiscal;
        }

        $deudoresNoFiscal = $this->parseDecimal($row['deudores_no_fiscal'] ?? null);
        if ($deudoresNoFiscal !== null) {
            $nomina->deudores_no_fiscal = $deudoresNoFiscal;
        }

        $this->asignarCampo($nomina, 'idbanca', $this->valorFila($row, ['idbanca']) ?? (isset($row['idbanca']) ? trim((string) $row['idbanca']) : null), $esNuevo, '0');
        $this->asignarCampo($nomina, 'numero_tarjeta', $this->valorFila($row, ['numero_tarjeta']), $esNuevo);
        $this->asignarCampo($nomina, 'numero_cuenta', $this->valorFila($row, ['numero_cuenta']), $esNuevo);
        $this->asignarCampo($nomina, 'numero_credito_infonavit', $this->valorFila($row, ['numero_credito_infonavit']), $esNuevo, '0');

        $diasVacaciones = $this->valorFila($row, ['dias_vacaciones']);
        if ($diasVacaciones !== null && is_numeric($diasVacaciones)) {
            $nomina->dias_vacaciones = (int) $diasVacaciones;
        }

        $fechaImss = $this->parseFechaExcel($row['fecha_ingreso_imss'] ?? $this->valorFila($row, ['fecha_ingreso_imss']));
        if ($fechaImss !== null) {
            $nomina->fecha_ingreso_imss = $fechaImss;
        }

        if ($esNuevo) {
            $nomina->created_by = $this->valorFila($row, ['created_by']) ?? $this->usuarioImportacion();
        } else {
            $nomina->updated_by = $this->valorFila($row, ['updated_by']) ?? $this->usuarioImportacion();
        }

        $nomina->save();
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
