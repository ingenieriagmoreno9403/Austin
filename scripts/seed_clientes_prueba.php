<?php

/**
 * Alta de clientes ficticios + personas de atención para pruebas.
 * Uso: php scripts/seed_clientes_prueba.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$now = now();

$clientes = [
    [
        'nombre' => 'TUBERIA DEL NORTE SA DE CV',
        'alias' => 'PRUEBA-TN',
        'razon_social' => 'TUBERIA DEL NORTE SA DE CV',
        'rfc' => 'TNO850101AB1',
        'telefono' => '8711110001',
        'correo_electronico' => 'compras@tuberianorte-prueba.mx',
        'id_ciudad' => 1, // Torreón
        'colonia' => 'CENTRO',
        'calle' => 'BLVD INDEPENDENCIA',
        'numero_int' => '0',
        'numero_ext' => '1200',
        'cp' => '27000',
        'moneda_id' => 1,
        'condicion_pago_id' => 3,
        'atenciones' => [
            [
                'primer_nombre' => 'LAURA',
                'segundo_nombre' => 'ELENA',
                'apellido_paterno' => 'MARTINEZ',
                'apellido_materno' => 'RUIZ',
                'telefono' => '8711110101',
                'correo' => 'laura.martinez@tuberianorte-prueba.mx',
            ],
            [
                'primer_nombre' => 'CARLOS',
                'segundo_nombre' => null,
                'apellido_paterno' => 'HERNANDEZ',
                'apellido_materno' => 'LOPEZ',
                'telefono' => '8711110102',
                'correo' => 'carlos.hernandez@tuberianorte-prueba.mx',
            ],
        ],
    ],
    [
        'nombre' => 'HIDRAULICA LAGUNA SPR DE RL',
        'alias' => 'PRUEBA-HL',
        'razon_social' => 'HIDRAULICA LAGUNA SPR DE RL',
        'rfc' => 'HLA920315CD2',
        'telefono' => '8712220002',
        'correo_electronico' => 'ventas@hidraulicalaguna-prueba.mx',
        'id_ciudad' => 2, // Gómez Palacio
        'colonia' => 'LAS QUINTAS',
        'calle' => 'CALZADA DIEZ DE MAYO',
        'numero_int' => 'B',
        'numero_ext' => '450',
        'cp' => '35070',
        'moneda_id' => 1,
        'condicion_pago_id' => 1,
        'atenciones' => [
            [
                'primer_nombre' => 'MARIA',
                'segundo_nombre' => 'FERNANDA',
                'apellido_paterno' => 'GARCIA',
                'apellido_materno' => 'SOTO',
                'telefono' => '8712220201',
                'correo' => 'mf.garcia@hidraulicalaguna-prueba.mx',
            ],
        ],
    ],
    [
        'nombre' => 'CONSTRUCCIONES SALTILLO SA',
        'alias' => 'PRUEBA-CS',
        'razon_social' => 'CONSTRUCCIONES SALTILLO SA DE CV',
        'rfc' => 'CSA780520EF3',
        'telefono' => '8443330003',
        'correo_electronico' => 'compras@consaltillo-prueba.mx',
        'id_ciudad' => 8, // Saltillo
        'colonia' => 'REPUBLICA',
        'calle' => 'BLVD FUNDADORES',
        'numero_int' => '0',
        'numero_ext' => '880',
        'cp' => '25230',
        'moneda_id' => 1,
        'condicion_pago_id' => 5,
        'atenciones' => [
            [
                'primer_nombre' => 'JORGE',
                'segundo_nombre' => 'ALBERTO',
                'apellido_paterno' => 'RAMIREZ',
                'apellido_materno' => 'PEÑA',
                'telefono' => '8443330301',
                'correo' => 'jorge.ramirez@consaltillo-prueba.mx',
            ],
            [
                'primer_nombre' => 'ANA',
                'segundo_nombre' => null,
                'apellido_paterno' => 'CASTILLO',
                'apellido_materno' => 'NUNEZ',
                'telefono' => '8443330302',
                'correo' => 'ana.castillo@consaltillo-prueba.mx',
            ],
        ],
    ],
    [
        'nombre' => 'AGROIRRIGACION DURANGO SA',
        'alias' => 'PRUEBA-AD',
        'razon_social' => 'AGROIRRIGACION DURANGO SA DE CV',
        'rfc' => 'AGD010812GH4',
        'telefono' => '6184440004',
        'correo_electronico' => 'contacto@agrodgo-prueba.mx',
        'id_ciudad' => 6, // Durango
        'colonia' => 'EL CIELO',
        'calle' => 'AV UNIVERSIDAD',
        'numero_int' => '0',
        'numero_ext' => '210',
        'cp' => '34120',
        'moneda_id' => 1,
        'condicion_pago_id' => 2,
        'atenciones' => [
            [
                'primer_nombre' => 'ROBERTO',
                'segundo_nombre' => null,
                'apellido_paterno' => 'SANCHEZ',
                'apellido_materno' => 'DIAZ',
                'telefono' => '6184440401',
                'correo' => 'roberto.sanchez@agrodgo-prueba.mx',
            ],
        ],
    ],
    [
        'nombre' => 'PIPELINE EXPORT MX SA DE CV',
        'alias' => 'PRUEBA-PX',
        'razon_social' => 'PIPELINE EXPORT MX SA DE CV',
        'rfc' => 'PEM150901IJ5',
        'telefono' => '8715550005',
        'correo_electronico' => 'export@pipelineexport-prueba.mx',
        'id_ciudad' => 1, // Torreón
        'colonia' => 'INDUSTRIAL',
        'calle' => 'CARR TORREON MATAMOROS',
        'numero_int' => '0',
        'numero_ext' => 'KM12',
        'cp' => '27400',
        'moneda_id' => 2, // USD
        'condicion_pago_id' => 4,
        'atenciones' => [
            [
                'primer_nombre' => 'SOFIA',
                'segundo_nombre' => 'ISABEL',
                'apellido_paterno' => 'MENDOZA',
                'apellido_materno' => 'CRUZ',
                'telefono' => '8715550501',
                'correo' => 'sofia.mendoza@pipelineexport-prueba.mx',
            ],
        ],
    ],
    [
        'nombre' => 'MUNICIPIO PRUEBA LAGUNA',
        'alias' => 'PRUEBA-MP',
        'razon_social' => 'MUNICIPIO PRUEBA LAGUNA',
        'rfc' => 'MPL850101KL6',
        'telefono' => '8716660006',
        'correo_electronico' => 'obras@muniprueba-laguna.gob.mx',
        'id_ciudad' => 1,
        'colonia' => 'CENTRO',
        'calle' => 'PLAZA PRINCIPAL',
        'numero_int' => '0',
        'numero_ext' => '1',
        'cp' => '27000',
        'moneda_id' => 1,
        'condicion_pago_id' => 3,
        'atenciones' => [
            [
                'primer_nombre' => 'PEDRO',
                'segundo_nombre' => 'ANTONIO',
                'apellido_paterno' => 'VAZQUEZ',
                'apellido_materno' => 'MORALES',
                'telefono' => '8716660601',
                'correo' => 'p.vazquez@muniprueba-laguna.gob.mx',
            ],
            [
                'primer_nombre' => 'LUCIA',
                'segundo_nombre' => null,
                'apellido_paterno' => 'ORTEGA',
                'apellido_materno' => 'FLORES',
                'telefono' => '8716660602',
                'correo' => 'l.ortega@muniprueba-laguna.gob.mx',
            ],
        ],
    ],
];

DB::beginTransaction();

try {
    $creados = [];

    foreach ($clientes as $data) {
        $alias = $data['alias'];
        $existente = DB::table('tblclientes')->where('alias', $alias)->first();
        if ($existente) {
            echo "SKIP (ya existe alias {$alias}): id {$existente->id}\n";
            continue;
        }

        // Evitar RFC duplicado
        if (DB::table('tblclientes')->where('rfc', $data['rfc'])->exists()) {
            $data['rfc'] = substr($data['rfc'], 0, 10) . random_int(10, 99);
        }

        $atenciones = $data['atenciones'];
        unset($data['atenciones']);

        $id = DB::table('tblclientes')->insertGetId(array_merge($data, [
            'estado' => 'A',
            'tipo' => 'BUENO',
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => 'seed_prueba',
            'updated_by' => 'seed_prueba',
        ]));

        $nombresAtencion = [];
        foreach ($atenciones as $p) {
            DB::table('tblclientes_atencion')->insert([
                'id_cliente' => $id,
                'primer_nombre' => $p['primer_nombre'],
                'segundo_nombre' => $p['segundo_nombre'],
                'apellido_paterno' => $p['apellido_paterno'],
                'apellido_materno' => $p['apellido_materno'],
                'telefono' => $p['telefono'],
                'correo' => $p['correo'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $nombresAtencion[] = trim(
                $p['primer_nombre'] . ' ' . ($p['segundo_nombre'] ? $p['segundo_nombre'] . ' ' : '')
                . $p['apellido_paterno'] . ' ' . $p['apellido_materno']
            );
        }

        $creados[] = [
            'id' => $id,
            'alias' => $alias,
            'nombre' => $data['nombre'],
            'atenciones' => $nombresAtencion,
        ];
        echo "OK cliente #{$id} {$alias} — {$data['nombre']}\n";
        foreach ($nombresAtencion as $n) {
            echo "   · Atención: {$n}\n";
        }
    }

    DB::commit();

    echo "\nTotal creados: " . count($creados) . "\n";
    echo "Clientes activos ahora: " . DB::table('tblclientes')->where('estado', 'A')->count() . "\n";
    echo "Personas atención: " . DB::table('tblclientes_atencion')->count() . "\n";
} catch (Throwable $e) {
    DB::rollBack();
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
