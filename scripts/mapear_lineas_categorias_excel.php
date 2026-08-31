<?php

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function normalizarTexto(?string $texto): string
{
    $texto = trim((string) $texto);
    $texto = mb_strtoupper($texto, 'UTF-8');
    $reemplazos = [
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
        'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U', 'ü' => 'U', 'ñ' => 'N',
    ];
    $texto = strtr($texto, $reemplazos);
    $texto = preg_replace('/\s+/', ' ', $texto) ?? $texto;
    $texto = rtrim($texto, '.');

    return $texto;
}

$equivalencias = [
    'ASA' => 51,
    'BR' => 16,
    'BRIDAS PVC' => 16,
    'DESCUENTOS O BONIFICACIONES' => 51,
    'FIBRA OPTICA' => 24,
    'REDUCCIONES P.E.A.D' => 43,
    'TANQUES DE P.E.A.D' => 51,
    'TAPAS' => 51,
    'TAPON' => 51,
    'TAPON P.E.A.D' => 51,
    'TEE' => 51,
    'TEES P.E.A.D' => 29,
    'TUBERIA DE POLIPRO' => 40,
    'TUBERIA ELECTRICA' => 51,
    'TUBERIA P/DRENAJE' => 50,
    'TUBERIA PARA GAS' => 41,
    'TUBERIA PARA TELECOMUNICACIONES' => 24,
    'TUBO' => 50,
    'VALVULA' => 51,
    'YEES P.E.A.D' => 29,
];

$mapaNombre = [];
foreach (DB::table('tblcategoria_producto')->orderBy('id')->get() as $categoria) {
    $mapaNombre[normalizarTexto($categoria->nombre)] = (int) $categoria->id;
}

$origPath = $argv[1] ?? 'c:\\Users\\gamo9\\Downloads\\IOHISA\\PRODUCTOS O SERVICIOS SAT.xlsx';
$destPath = $argv[2] ?? 'c:\\Users\\gamo9\\Downloads\\IOHISA\\PRODUCTOS_SAT_CATEGORIAS_UNIDADES_MYSQL.xlsx';

$origSheet = IOFactory::load($origPath)->getActiveSheet();
$origRows = $origSheet->toArray(null, true, true, true);
array_shift($origRows);

$lineaPorClave = [];
foreach ($origRows as $row) {
    $clave = trim((string) ($row['F'] ?? ''));
    $linea = trim((string) ($row['B'] ?? ''));
    if ($clave !== '') {
        $lineaPorClave[$clave] = $linea;
    }
}

function resolverCategoriaId(string $linea, array $mapaNombre, array $equivalencias): ?int
{
    $normalizada = normalizarTexto($linea);
    if ($normalizada === '') {
        return null;
    }

    if (isset($equivalencias[$normalizada])) {
        return $equivalencias[$normalizada];
    }

    if (isset($mapaNombre[$normalizada])) {
        return $mapaNombre[$normalizada];
    }

    if (ctype_digit($normalizada)) {
        $id = (int) $normalizada;
        if (isset(array_flip($mapaNombre)[$id]) || in_array($id, $mapaNombre, true)) {
            return $id;
        }
    }

    return 51;
}

$spreadsheet = IOFactory::load($destPath);
$sheet = null;
foreach ($spreadsheet->getSheetNames() as $nombreHoja) {
    if (stripos($nombreHoja, 'art') !== false) {
        $sheet = $spreadsheet->getSheetByName($nombreHoja);
        break;
    }
}

if (! $sheet) {
    fwrite(STDERR, "No se encontro hoja Articulos en el archivo destino.\n");
    exit(1);
}

$highestRow = $sheet->getHighestDataRow();
$stats = [
    'exactas' => 0,
    'equivalencia' => 0,
    'varios' => 0,
    'sin_linea' => 0,
    'sin_match_fila' => 0,
];
$equivalenciasUsadas = [];
$sinResolver = [];

for ($row = 2; $row <= $highestRow; $row++) {
    $clave = trim((string) $sheet->getCell("H{$row}")->getValue());
    $sku = trim((string) $sheet->getCell("A{$row}")->getValue());
    $key = $clave !== '' ? $clave : $sku;

    $linea = $lineaPorClave[$key] ?? '';
    if ($linea === '') {
        $stats['sin_match_fila']++;
        continue;
    }

    $normalizada = normalizarTexto($linea);
    if ($normalizada === '') {
        $stats['sin_linea']++;
        $sheet->setCellValue("D{$row}", 51);
        continue;
    }

    if (isset($equivalencias[$normalizada])) {
        $id = $equivalencias[$normalizada];
        $stats['equivalencia']++;
        $equivalenciasUsadas[$linea] = $id;
    } elseif (isset($mapaNombre[$normalizada])) {
        $id = $mapaNombre[$normalizada];
        $stats['exactas']++;
    } else {
        $id = 51;
        $stats['varios']++;
        $sinResolver[$linea] = ($sinResolver[$linea] ?? 0) + 1;
    }

    $sheet->setCellValue("D{$row}", $id);
}

$resumen = $spreadsheet->getSheetByName('Resumen categorias');
if ($resumen) {
    $resumen->setCellValue('A1', 'Concepto');
    $resumen->setCellValue('B1', 'Valor');
    $fila = 2;
    $lineasResumen = [
        ['Registros procesados', $highestRow - 1],
        ['Coincidencias exactas', $stats['exactas']],
        ['Asignados por equivalencia', $stats['equivalencia']],
        ['Asignados a VARIOS por defecto', $stats['varios']],
        ['Filas sin linea en origen', $stats['sin_match_fila']],
        ['', ''],
        ['Equivalencias aplicadas', 'ID categoría'],
    ];
    foreach ($lineasResumen as $item) {
        $resumen->setCellValue("A{$fila}", $item[0]);
        $resumen->setCellValue("B{$fila}", $item[1]);
        $fila++;
    }
    ksort($equivalenciasUsadas);
    foreach ($equivalenciasUsadas as $texto => $id) {
        $resumen->setCellValue("A{$fila}", $texto);
        $resumen->setCellValue("B{$fila}", $id);
        $fila++;
    }
    if (! empty($sinResolver)) {
        $fila++;
        $resumen->setCellValue("A{$fila}", 'Sin equivalencia especifica detectada');
        $resumen->setCellValue("B{$fila}", 'Cantidad');
        $fila++;
        arsort($sinResolver);
        foreach ($sinResolver as $texto => $cantidad) {
            $resumen->setCellValue("A{$fila}", $texto);
            $resumen->setCellValue("B{$fila}", $cantidad);
            $fila++;
        }
    }
}

$writer = new Xlsx($spreadsheet);
$writer->save($destPath);

echo "Archivo actualizado: {$destPath}\n";
echo "Exactas: {$stats['exactas']}\n";
echo "Equivalencia: {$stats['equivalencia']}\n";
echo "VARIOS: {$stats['varios']}\n";
echo "Sin linea en origen: {$stats['sin_match_fila']}\n";

if (! empty($sinResolver)) {
    echo "\nLineas enviadas a VARIOS:\n";
    foreach ($sinResolver as $texto => $cantidad) {
        echo "  [{$cantidad}] {$texto}\n";
    }
}
