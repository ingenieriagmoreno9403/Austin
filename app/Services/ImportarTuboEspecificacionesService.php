<?php

namespace App\Services;

use App\Models\ProductoTuboEspecificacion;
use App\Models\Productos;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportarTuboEspecificacionesService
{
    private const HOJA = 'Tabla de especificaciones pulg';

    private const PREFIJO_SKU = 'TUBO-PE4710-';

    /**
     * @return array{material:string, productos:int, importados:int}
     */
    public function importar(string $rutaArchivo, ?int $productoId = null, string $material = 'HDPE PE4710'): array
    {
        if (!is_file($rutaArchivo)) {
            throw new \InvalidArgumentException("No se encontró el archivo: {$rutaArchivo}");
        }

        $spreadsheet = IOFactory::load($rutaArchivo);
        $hoja = $spreadsheet->getSheetByName(self::HOJA) ?? $spreadsheet->getActiveSheet();

        $grados = $this->extraerGrados($hoja);
        $filas = $this->extraerFilas($hoja, $grados);

        if ($productoId) {
            $filas = $this->filtrarFilasPorProductoExistente($filas, $productoId);
        }

        return $this->persistirFilasAgrupadas($filas, $material);
    }

    /**
     * Vincula especificaciones ya importadas (un solo producto) a productos por diámetro.
     *
     * @return array{material:string, productos:int, importados:int}
     */
    public function vincularProductosPorDiametro(string $material = 'HDPE PE4710'): array
    {
        $filas = ProductoTuboEspecificacion::query()
            ->orderBy('diametro_exterior_pulg')
            ->orderByDesc('psi')
            ->get()
            ->map(fn (ProductoTuboEspecificacion $spec) => [
                'diametro_nominal' => $spec->diametro_nominal,
                'diametro_exterior_pulg' => $spec->diametro_exterior_pulg,
                'psi' => $spec->psi,
                'rd' => $spec->rd,
                'espesor_pulg' => $spec->espesor_pulg,
                'peso_kg_m' => $spec->peso_kg_m,
            ])
            ->all();

        if ($filas === []) {
            throw new \RuntimeException('No hay especificaciones para vincular. Importe el Excel primero.');
        }

        return $this->persistirFilasAgrupadas($filas, $material);
    }

    /**
     * @param list<array<string, mixed>> $filas
     * @return array{material:string, productos:int, importados:int}
     */
    private function persistirFilasAgrupadas(array $filas, string $material): array
    {
        $agrupadas = collect($filas)->groupBy('diametro_nominal');
        $productosCreados = 0;
        $importados = 0;
        $productosIds = [];

        DB::transaction(function () use ($agrupadas, $material, &$productosCreados, &$importados, &$productosIds) {
            ProductoTuboEspecificacion::query()->delete();

            foreach ($agrupadas as $diametroNominal => $lineas) {
                $primera = $lineas->first();
                $resultadoProducto = $this->crearObtenerProductoPorDiametro(
                    $material,
                    (string) $diametroNominal,
                    (float) $primera['diametro_exterior_pulg']
                );
                $producto = $resultadoProducto['producto'];

                if ($resultadoProducto['nuevo']) {
                    $productosCreados++;
                }

                $productosIds[] = $producto->id;

                foreach ($lineas as $fila) {
                    ProductoTuboEspecificacion::create([
                        'producto_id' => $producto->id,
                        'material' => $material,
                        'diametro_nominal' => $fila['diametro_nominal'],
                        'diametro_exterior_pulg' => $fila['diametro_exterior_pulg'],
                        'psi' => $fila['psi'],
                        'rd' => $fila['rd'],
                        'espesor_pulg' => $fila['espesor_pulg'],
                        'peso_kg_m' => $fila['peso_kg_m'],
                        'estatus' => 'ACTIVO',
                        'created_at' => now(),
                    ]);
                    $importados++;
                }
            }

            $this->limpiarProductoGenericoSinEspecificaciones();
        });

        return [
            'material' => $material,
            'productos' => count(array_unique($productosIds)),
            'productos_nuevos' => $productosCreados,
            'importados' => $importados,
        ];
    }

    private function resolverOCrearProductoPorDiametro(string $material, string $diametroNominal, float $diametroExterior): Productos
    {
        return $this->crearObtenerProductoPorDiametro($material, $diametroNominal, $diametroExterior)['producto'];
    }

    /**
     * @return array{producto: Productos, nuevo: bool}
     */
    private function crearObtenerProductoPorDiametro(string $material, string $diametroNominal, float $diametroExterior): array
    {
        $sku = $this->skuPorDiametro($diametroNominal);
        $nombre = $this->nombreProductoPorDiametro($diametroNominal);

        $producto = Productos::where('sku', $sku)->first();
        if ($producto) {
            return ['producto' => $producto, 'nuevo' => false];
        }

        $categoriaId = (int) (DB::table('tblcategoria_producto')->orderBy('id')->value('id') ?? 0);
        $unidadId = (int) (DB::table('tblunidadesmedida')->orderBy('id')->value('id') ?? 0);
        $diametroTexto = $this->textoDiametro($diametroNominal);

        $productoId = DB::table('tblproductos')->insertGetId([
            'sku' => $sku,
            'nombre' => $nombre,
            'descripcion' => "Tubo {$material} de {$diametroTexto} pulgada. Diámetro exterior: " . number_format($diametroExterior, 4) . ' pulg.',
            'codigo_barras' => $sku,
            'precio_unitario' => 0,
            'id_categoria' => $categoriaId ?: null,
            'id_unidad_medida' => $unidadId ?: null,
            'id_proveedor' => null,
            'costo_compra' => 0,
            'costo_venta' => 0,
            'minima_existencia' => 0,
            'maxima_existencia' => 0,
        ]);

        return [
            'producto' => Productos::findOrFail($productoId),
            'nuevo' => true,
        ];
    }

    public function nombreProductoPorDiametro(string $diametroNominal): string
    {
        return 'Tubo PE4710 ' . $this->textoDiametro($diametroNominal) . ' pulgada';
    }

    public function skuPorDiametro(string $diametroNominal): string
    {
        $texto = $this->textoDiametro($diametroNominal);
        $slug = strtolower($texto);
        $slug = str_replace([' ', '/'], ['', '-'], $slug);

        return self::PREFIJO_SKU . $slug;
    }

    private function textoDiametro(string $diametroNominal): string
    {
        return trim(str_replace('"', '', $diametroNominal));
    }

    private function limpiarProductoGenericoSinEspecificaciones(): void
    {
        $genericos = Productos::where('sku', 'TUBO-PE4710')->pluck('id');

        foreach ($genericos as $id) {
            $tieneSpecs = ProductoTuboEspecificacion::where('producto_id', $id)->exists();
            if (!$tieneSpecs) {
                Productos::where('id', $id)->delete();
            }
        }
    }

    /**
     * @param list<array<string, mixed>> $filas
     * @return list<array<string, mixed>>
     */
    private function filtrarFilasPorProductoExistente(array $filas, int $productoId): array
    {
        $producto = Productos::findOrFail($productoId);
        $diametro = null;

        if (preg_match('/Tubo PE4710 (.+) pulgada/i', (string) $producto->nombre, $matches)) {
            $diametro = trim($matches[1]) . '"';
        }

        if (!$diametro) {
            return $filas;
        }

        return array_values(array_filter(
            $filas,
            fn (array $fila) => $this->textoDiametro($fila['diametro_nominal']) === $this->textoDiametro($diametro)
        ));
    }

    /**
     * @return list<array{col:int, psi:float, rd:float|null}>
     */
    private function extraerGrados($hoja): array
    {
        $grados = [];
        $maxCol = 30;

        for ($col = 3; $col <= $maxCol; $col += 2) {
            $psi = $hoja->getCellByColumnAndRow($col, 7)->getCalculatedValue();
            $rdRaw = $hoja->getCellByColumnAndRow($col, 8)->getCalculatedValue();

            if ($psi === null || $psi === '' || !is_numeric($psi)) {
                continue;
            }

            $grados[] = [
                'col' => $col,
                'psi' => (float) $psi,
                'rd' => $this->parsearRd($rdRaw),
            ];
        }

        if ($grados === []) {
            throw new \RuntimeException('No se pudieron leer los grados PSI/RD del Excel.');
        }

        return $grados;
    }

    /**
     * @param list<array{col:int, psi:float, rd:float|null}> $grados
     * @return list<array<string, mixed>>
     */
    private function extraerFilas($hoja, array $grados): array
    {
        $resultado = [];
        $maxRow = (int) $hoja->getHighestRow();

        for ($row = 11; $row <= $maxRow; $row++) {
            $diametroNominal = trim((string) $hoja->getCellByColumnAndRow(1, $row)->getCalculatedValue());
            if ($diametroNominal === '') {
                continue;
            }

            $diametroExterior = $this->numero($hoja->getCellByColumnAndRow(2, $row)->getCalculatedValue());
            if ($diametroExterior === null) {
                continue;
            }

            foreach ($grados as $grado) {
                $espesor = $this->numero($hoja->getCellByColumnAndRow($grado['col'], $row)->getCalculatedValue());
                $peso = $this->numero($hoja->getCellByColumnAndRow($grado['col'] + 1, $row)->getCalculatedValue());

                if ($espesor === null || $peso === null) {
                    continue;
                }

                $resultado[] = [
                    'diametro_nominal' => $diametroNominal,
                    'diametro_exterior_pulg' => $diametroExterior,
                    'psi' => $grado['psi'],
                    'rd' => $grado['rd'],
                    'espesor_pulg' => $espesor,
                    'peso_kg_m' => $peso,
                ];
            }
        }

        return $resultado;
    }

    private function parsearRd(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_numeric($valor)) {
            return (float) $valor;
        }

        if (preg_match('/([\d.]+)/', (string) $valor, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }

    private function numero(mixed $valor): ?float
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim((string) $valor);
        if ($texto === '' || $texto === '-') {
            return null;
        }

        if (!is_numeric($texto)) {
            return null;
        }

        return (float) $texto;
    }
}
