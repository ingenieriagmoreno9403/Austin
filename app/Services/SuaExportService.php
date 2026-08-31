<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SuaExportService
{
    public const TIPO_TRABAJADORES = 'trabajadores';
    public const TIPO_AFILIATORIOS = 'afiliatorios';
    public const TIPO_MOVIMIENTOS = 'movimientos';
    public const TIPO_CREDITO = 'credito';
    public const TIPO_INCAPACITADOS = 'incapacitados';

    public const TIPOS_DISPONIBLES = [
        self::TIPO_TRABAJADORES => 'Trabajadores (altas)',
        self::TIPO_AFILIATORIOS => 'Datos Afiliatorios',
        self::TIPO_MOVIMIENTOS => 'Movimientos',
        self::TIPO_CREDITO => 'Movimientos de Crédito',
        self::TIPO_INCAPACITADOS => 'Datos de Incapacidades',
    ];

    public const TIPOS_IMPLEMENTADOS = [
        self::TIPO_TRABAJADORES,
        self::TIPO_AFILIATORIOS,
        self::TIPO_MOVIMIENTOS,
        self::TIPO_CREDITO,
        self::TIPO_INCAPACITADOS,
    ];

    /** Tipos de movimiento SUA (afiliación/laboral). */
    public const TIPOS_MOVIMIENTO = [
        '02' => '02 Baja',
        '07' => '07 Modificación de Salario',
        '08' => '08 Reingreso',
        '11' => '11 Ausentismo',
        '12' => '12 Incapacidad',
    ];

    /** Tipos de movimiento de crédito INFONAVIT. */
    public const TIPOS_MOVIMIENTO_CREDITO = [
        '15' => '15 Inicio de Crédito de Vivienda (ICV)',
        '16' => '16 Fecha de Suspensión de Descuento (FS)',
        '17' => '17 Reinicio de Descuento (RD)',
        '18' => '18 Modificación de Tipo de Descuento (MTD)',
        '19' => '19 Modificación de Valor de Descuento (MVD)',
        '20' => '20 Modificación de Número de Crédito (MND)',
    ];

    /** Tipos de descuento INFONAVIT. */
    public const TIPOS_DESCUENTO = [
        '1' => '1 Porcentaje',
        '2' => '2 Cuota Fija Monetaria',
        '3' => '3 Factor de descuento',
    ];

    /** Rama / ramo de seguro SUA (incapacidades). */
    public const RAMAS_INCAPACIDAD = [
        '1' => '1 Riesgo de Trabajo',
        '2' => '2 Enfermedad General',
        '3' => '3 Maternidad',
        '4' => '4 Licencia 140 Bis',
    ];

    /** Tipo de riesgo (sólo aplica a Riesgo de Trabajo). */
    public const TIPOS_RIESGO = [
        '0' => '0 No aplica',
        '1' => '1 Accidente de trabajo',
        '2' => '2 Accidente de trayecto',
        '3' => '3 Enfermedad de trabajo',
    ];

    /** Secuela o consecuencia. */
    public const SECUELAS_INCAPACIDAD = [
        '0' => '0 Ninguna',
        '1' => '1 Incapacidad temporal',
        '2' => '2 Valuación inicial provisional',
        '3' => '3 Valuación inicial definitiva',
        '4' => '4 Defunción',
        '5' => '5 Recaída',
        '6' => '6 Valuación post. a la fecha de alta',
        '7' => '7 Revaluación provisional',
        '8' => '8 Recaída sin alta médica',
        '9' => '9 Revaluación definitiva',
    ];

    /** Control de incapacidad. */
    public const CONTROLES_INCAPACIDAD = [
        '0' => '0 Ninguna',
        '1' => '1 Única',
        '2' => '2 Inicial',
        '3' => '3 Subsecuente',
        '4' => '4 Alta médica o ST-2',
        '5' => '5 Valuación o ST-3',
        '6' => '6 Prenatal / Defunción o ST-3',
        '7' => '7 Enlace',
        '8' => '8 Postnatal',
    ];

    /** Etiquetas SUA para Tipo de Trabajador. */
    public const TIPOS_TRABAJADOR = [
        '1' => 'Permanente',
        '2' => 'Eventual',
        '3' => 'Construcción',
        '4' => 'Eventuales del Campo',
    ];

    /** Etiquetas SUA para Jornada / Semana Reducida. */
    public const JORNADAS = [
        '0' => 'Semana Completa',
        '1' => '1 Día',
        '2' => '2 Días',
        '3' => '3 Días',
        '4' => '4 Días',
        '5' => '5 Días',
        '6' => 'Menor a 8 Horas',
    ];

    /**
     * Genera el contenido TXT para el tipo de exportación SUA indicado.
     *
     * @param  Collection<int, object>  $empleados
     */
    public function generar(string $tipo, Collection $empleados, array $opciones = []): string
    {
        return match ($tipo) {
            self::TIPO_TRABAJADORES => $this->generarTrabajadoresAltas($empleados, $opciones),
            self::TIPO_AFILIATORIOS => $this->generarDatosAfiliatorios($empleados, $opciones),
            self::TIPO_MOVIMIENTOS => $this->generarMovimientos($empleados, $opciones),
            self::TIPO_CREDITO => $this->generarMovimientosCredito($empleados, $opciones),
            self::TIPO_INCAPACITADOS => $this->generarIncapacitados($empleados, $opciones),
            default => throw new \InvalidArgumentException('Tipo de exportación SUA no implementado: ' . $tipo),
        };
    }

    /**
     * Formato SUA Importación de Trabajadores (altas) — 164 caracteres por registro.
     *
     * @param  Collection<int, object>  $empleados
     */
    public function generarTrabajadoresAltas(Collection $empleados, array $opciones = []): string
    {
        $registroPatronalDefault = $this->alpha($opciones['registro_patronal'] ?? '', 11);

        $lineas = [];

        foreach ($empleados as $empleado) {
            $registroPatronalEmpleado = preg_replace(
                '/[^A-Z0-9]/i',
                '',
                strtoupper(trim((string) ($empleado->registro_patronal_imss ?? '')))
            ) ?? '';
            $registroPatronal = $this->alpha(
                $registroPatronalEmpleado !== '' ? $registroPatronalEmpleado : $registroPatronalDefault,
                11
            );
            $nss = $this->digits($empleado->nss ?? '', 11);
            $rfc = $this->alpha($empleado->rfc ?? '', 13);
            $curp = $this->alpha($empleado->curp ?? '', 18);
            $nombre = $this->formatNombreSua(
                $empleado->apellido_paterno ?? '',
                $empleado->apellido_materno ?? '',
                $empleado->primer_nombre ?? '',
                $empleado->segundo_nombre ?? ''
            );
            $tipoTrabajador = $this->resolverTipoTrabajador(
                $empleado->tipo_contratacion ?? '',
                (string) ($empleado->tipo_trabajador_sua ?? '2')
            );
            $jornada = $this->digits((string) ($empleado->jornada_sua ?? '0'), 1);
            if (!array_key_exists($jornada, self::JORNADAS)) {
                $jornada = '0';
            }
            $fechaAlta = $this->formatFechaSua(
                $empleado->fecha_ingreso_imss ?? $empleado->fecha_ingreso ?? null
            );
            $sdi = $this->formatSalarioDiarioIntegrado($empleado->salario_fijo ?? 0);
            $ubicacion = $this->alpha($empleado->puesto ?? '', 17);

            $tieneCredito = $this->tieneCreditoInfonavit($empleado);
            if ($tieneCredito) {
                $credito = $this->alpha($empleado->numero_credito_infonavit ?? '', 10);
                $fechaInicioDesc = $this->formatFechaInicioDescuento(
                    $empleado->fecha_inicio_descuento
                        ?? $empleado->fecha_ingreso_imss
                        ?? $empleado->fecha_ingreso
                        ?? null
                );
                $tipoDesc = $this->mapTipoDescuentoInfonavit(
                    (string) ($empleado->nombreinfonavit ?? ''),
                    $empleado->id_tipoinfonavit ?? null
                );
                $valorDesc = $this->formatValorDescuento(
                    $tipoDesc,
                    (float) ($empleado->factor_sua ?? 0)
                );
            } else {
                $credito = str_repeat(' ', 10);
                $fechaInicioDesc = str_repeat('0', 8);
                $tipoDesc = '0';
                $valorDesc = str_repeat('0', 8);
            }

            $linea = $registroPatronal
                . $nss
                . $rfc
                . $curp
                . $nombre
                . $tipoTrabajador
                . $jornada
                . $fechaAlta
                . $sdi
                . $ubicacion
                . $credito
                . $fechaInicioDesc
                . $tipoDesc
                . $valorDesc;

            if (strlen($linea) !== 164) {
                throw new \RuntimeException(
                    'La línea SUA generada no tiene 164 caracteres (obtuvo ' . strlen($linea) . ').'
                );
            }

            $lineas[] = $linea;
        }

        return implode("\r\n", $lineas) . (count($lineas) ? "\r\n" : '');
    }

    /**
     * Formato SUA Datos Afiliatorios — 80 caracteres por registro.
     *
     * @param  Collection<int, object>  $empleados
     */
    public function generarDatosAfiliatorios(Collection $empleados, array $opciones = []): string
    {
        $registroPatronalDefault = preg_replace(
            '/[^A-Z0-9]/i',
            '',
            strtoupper(trim((string) ($opciones['registro_patronal'] ?? '')))
        ) ?? '';

        $lineas = [];

        foreach ($empleados as $empleado) {
            $rpRaw = preg_replace(
                '/[^A-Z0-9]/i',
                '',
                strtoupper(trim((string) ($empleado->registro_patronal_imss ?? '')))
            ) ?? '';
            if ($rpRaw === '') {
                $rpRaw = $registroPatronalDefault;
            }
            $rpRaw = str_pad(substr($rpRaw, 0, 11), 11, '0', STR_PAD_RIGHT);
            $registroPatronal = $this->alphaAfiliatorio(substr($rpRaw, 0, 10), 10);
            $digitoRegistro = $this->digits(substr($rpRaw, 10, 1), 1);

            $nssRaw = $this->digits($empleado->nss ?? '', 11);
            $nss = substr($nssRaw, 0, 10);
            $digitoNss = substr($nssRaw, 10, 1);

            $codigoPostal = $this->digits($empleado->codigo_postal ?? '', 5);
            $fechaNacimiento = $this->formatFechaSua(
                $empleado->fecha_nacimiento ?? null
            );
            $lugarNacimiento = $this->alphaAfiliatorio(
                (string) ($empleado->lugar_nacimiento ?? $empleado->estado ?? $empleado->ciudad ?? ''),
                25
            );
            $claveLugar = $this->alphaAfiliatorio(
                $this->claveLugarNacimiento($empleado),
                2
            );
            $umf = $this->digits((string) ($empleado->unidad_medicina_familiar ?? '0'), 3);
            $ocupacion = $this->alphaAfiliatorio((string) ($empleado->puesto ?? ''), 12);
            $sexo = $this->formatSexoSua($empleado->sexo ?? null, $empleado->curp ?? null);
            $tipoSalario = $this->resolverTipoSalario($empleado);
            $hora = $this->digits((string) ($empleado->hora_sua ?? '0'), 1);

            $linea = $registroPatronal
                . $digitoRegistro
                . $nss
                . $digitoNss
                . $codigoPostal
                . $fechaNacimiento
                . $lugarNacimiento
                . $claveLugar
                . $umf
                . $ocupacion
                . $sexo
                . $tipoSalario
                . $hora;

            if (strlen($linea) !== 80) {
                throw new \RuntimeException(
                    'La línea SUA afiliatorios no tiene 80 caracteres (obtuvo ' . strlen($linea) . ').'
                );
            }

            $lineas[] = $linea;
        }

        return implode("\r\n", $lineas) . (count($lineas) ? "\r\n" : '');
    }

    /**
     * Formato SUA Importación de Movimientos — 49 caracteres por registro.
     *
     * @param  Collection<int, object>  $empleados
     */
    public function generarMovimientos(Collection $empleados, array $opciones = []): string
    {
        $registroPatronalDefault = $this->alpha($opciones['registro_patronal'] ?? '', 11);
        $tipoMovimientoDefault = $this->digits((string) ($opciones['tipo_movimiento'] ?? '02'), 2);

        $lineas = [];

        foreach ($empleados as $empleado) {
            $rpRaw = preg_replace(
                '/[^A-Z0-9]/i',
                '',
                strtoupper(trim((string) ($empleado->registro_patronal_imss ?? '')))
            ) ?? '';
            $registroPatronal = $this->alpha(
                $rpRaw !== '' ? $rpRaw : $registroPatronalDefault,
                11
            );
            $nss = $this->digits($empleado->nss ?? '', 11);

            $tipoMov = $this->digits(
                (string) ($empleado->tipo_movimiento_sua ?? $tipoMovimientoDefault),
                2
            );
            if (!array_key_exists($tipoMov, self::TIPOS_MOVIMIENTO)) {
                $tipoMov = '02';
            }

            $fechaMov = $this->formatFechaSua(
                $empleado->fecha_movimiento_sua
                    ?? $empleado->fecha_incidencia
                    ?? $empleado->fecha_baja
                    ?? $empleado->fecha_ingreso_imss
                    ?? $empleado->fecha_ingreso
                    ?? null
            );

            // Folio solo aplica a 12 Incapacidad; si no, espacios.
            if ($tipoMov === '12') {
                $folio = $this->alphaAfiliatorio((string) ($empleado->folio_incapacidad_sua ?? ''), 8);
            } else {
                $folio = str_repeat(' ', 8);
            }

            // Días solo aplica a 11 Ausentismo y 12 Incapacidad.
            if (in_array($tipoMov, ['11', '12'], true)) {
                $dias = $this->digits((string) ($empleado->dias_incidencia_sua ?? '0'), 2);
            } else {
                $dias = '00';
            }

            // SDI solo aplica a 07 Modificación de Salario y 08 Reingreso.
            if (in_array($tipoMov, ['07', '08'], true)) {
                $sdi = $this->formatSalarioDiarioIntegrado(
                    $empleado->sdi_sua ?? $empleado->salario_fijo ?? 0
                );
            } else {
                $sdi = str_repeat('0', 7);
            }

            $linea = $registroPatronal
                . $nss
                . $tipoMov
                . $fechaMov
                . $folio
                . $dias
                . $sdi;

            if (strlen($linea) !== 49) {
                throw new \RuntimeException(
                    'La línea SUA movimientos no tiene 49 caracteres (obtuvo ' . strlen($linea) . ').'
                );
            }

            $lineas[] = $linea;
        }

        return implode("\r\n", $lineas) . (count($lineas) ? "\r\n" : '');
    }

    /**
     * Formato SUA Movimientos de Crédito INFONAVIT — 52 caracteres por registro.
     *
     * RP(11) + NSS(11) + Crédito(10) + TipoMov(2) + Fecha(8) + TipoDesc(1) + ValorDesc(8) + Aplica(1)
     *
     * @param  Collection<int, object>  $empleados
     */
    public function generarMovimientosCredito(Collection $empleados, array $opciones = []): string
    {
        $registroPatronalDefault = $this->alpha($opciones['registro_patronal'] ?? '', 11);
        $tipoMovimientoDefault = $this->digits((string) ($opciones['tipo_movimiento_credito'] ?? '15'), 2);

        $lineas = [];
        $creditosUsados = [];

        foreach ($empleados as $empleado) {
            $rpRaw = preg_replace(
                '/[^A-Z0-9]/i',
                '',
                strtoupper(trim((string) ($empleado->registro_patronal_imss ?? '')))
            ) ?? '';
            $registroPatronal = $this->alpha(
                $rpRaw !== '' ? $rpRaw : $registroPatronalDefault,
                11
            );
            $nss = $this->digits($empleado->nss ?? '', 11);

            $numeroCredito = $this->digits(
                (string) ($empleado->numero_credito_sua ?? $empleado->numero_credito_infonavit ?? '0'),
                10
            );
            $this->assertNumeroCreditoInfonavit($numeroCredito, $nss);

            if (isset($creditosUsados[$numeroCredito])) {
                throw new \InvalidArgumentException(
                    'Número de crédito INFONAVIT duplicado en el archivo: ' . $numeroCredito
                );
            }
            $creditosUsados[$numeroCredito] = true;

            $tipoMov = $this->digits(
                (string) ($empleado->tipo_movimiento_credito_sua ?? $tipoMovimientoDefault),
                2
            );
            if (!array_key_exists($tipoMov, self::TIPOS_MOVIMIENTO_CREDITO)) {
                throw new \InvalidArgumentException(
                    'Tipo de movimiento de crédito inválido (debe ser 15–20). NSS ' . $nss . '.'
                );
            }

            $fechaMov = $this->formatFechaMovimientoCredito(
                $empleado->fecha_movimiento_sua
                    ?? $empleado->fecha_ingreso_imss
                    ?? $empleado->fecha_ingreso
                    ?? null,
                $nss
            );

            $tipoDesc = $this->digits(
                (string) ($empleado->tipo_descuento_sua
                    ?? $this->mapTipoDescuentoInfonavit(
                        (string) ($empleado->nombreinfonavit ?? ''),
                        $empleado->id_tipoinfonavit ?? null
                    )),
                1
            );
            if (!array_key_exists($tipoDesc, self::TIPOS_DESCUENTO)) {
                throw new \InvalidArgumentException(
                    'Tipo de descuento inválido (sólo 1, 2 o 3). NSS ' . $nss . '.'
                );
            }

            $valorNumerico = (float) ($empleado->valor_descuento_sua
                ?? $empleado->descuento_quincenal
                ?? $empleado->factor_sua
                ?? 0);
            if ($valorNumerico <= 0) {
                throw new \InvalidArgumentException(
                    'El valor de descuento debe ser mayor a cero (NSS ' . $nss . ').'
                );
            }

            $valorDesc = $this->formatValorDescuento($tipoDesc, $valorNumerico);
            if ((int) $valorDesc <= 0) {
                throw new \InvalidArgumentException(
                    'El valor de descuento debe ser mayor a cero (NSS ' . $nss . ').'
                );
            }

            $aplicaTabla = strtoupper(trim((string) ($empleado->aplica_tabla_sua ?? 'N')));
            if (!in_array($aplicaTabla, ['S', 'N'], true)) {
                throw new \InvalidArgumentException(
                    'Aplica tabla de disminución debe ser S o N (NSS ' . $nss . ').'
                );
            }

            $linea = $registroPatronal
                . $nss
                . $numeroCredito
                . $tipoMov
                . $fechaMov
                . $tipoDesc
                . $valorDesc
                . $aplicaTabla;

            if (strlen($linea) !== 52) {
                throw new \RuntimeException(
                    'La línea SUA crédito no tiene 52 caracteres (obtuvo ' . strlen($linea) . ').'
                );
            }

            $lineas[] = $linea;
        }

        return implode("\r\n", $lineas) . (count($lineas) ? "\r\n" : '');
    }

    /**
     * Formato SUA Datos de Incapacidades — 57 caracteres por registro.
     *
     * RP(11)+NSS(11)+TipoInc(1)+FInicio(8)+Folio(8)+Días(3)+%(3)+Rama(1)+Riesgo(1)+Secuela(1)+Control(1)+FTérmino(8)
     *
     * @param  Collection<int, object>  $registros
     */
    public function generarIncapacitados(Collection $registros, array $opciones = []): string
    {
        $registroPatronalDefault = $this->alpha($opciones['registro_patronal'] ?? '', 11);
        $lineas = [];

        foreach ($registros as $registro) {
            $rpRaw = preg_replace(
                '/[^A-Z0-9]/i',
                '',
                strtoupper(trim((string) ($registro->registro_patronal_imss ?? '')))
            ) ?? '';
            $registroPatronal = $this->alpha(
                $rpRaw !== '' ? $rpRaw : $registroPatronalDefault,
                11
            );
            $nss = $this->digits($registro->nss ?? '', 11);

            // Manual SUA: Tipo de Incidencia siempre "1".
            $tipoIncidencia = $this->alpha(
                (string) ($registro->tipo_incidencia_sua ?? '1'),
                1
            );
            if ($tipoIncidencia === '') {
                $tipoIncidencia = '1';
            }

            $fechaInicio = $this->formatFechaIncapacidadSua(
                $registro->fecha_inicio_sua
                    ?? $registro->fecha_inicio
                    ?? $registro->fecha_incidencia
                    ?? null,
                $nss,
                'inicio'
            );
            $fechaTermino = $this->formatFechaIncapacidadSua(
                $registro->fecha_termino_sua
                    ?? $registro->fecha_fin
                    ?? $registro->fecha_termino
                    ?? null,
                $nss,
                'término'
            );

            $folio = $this->alphaAfiliatorio(
                (string) ($registro->folio_incapacidad_sua ?? $registro->folio ?? ''),
                8
            );
            if (trim($folio) === '') {
                throw new \InvalidArgumentException(
                    'El folio de incapacidad es obligatorio (NSS ' . $nss . ').'
                );
            }

            $dias = $this->digits(
                (string) ($registro->dias_subsidiados_sua ?? $registro->dias_calculados ?? 0),
                3
            );
            if ((int) $dias <= 0) {
                throw new \InvalidArgumentException(
                    'Los días subsidiados deben ser mayores a cero (NSS ' . $nss . ').'
                );
            }

            $porcentaje = $this->digits(
                (string) ($registro->porcentaje_incapacidad_sua ?? 0),
                3
            );

            $rama = $this->digits(
                (string) ($registro->rama_incapacidad_sua
                    ?? self::mapRamaIncapacidad($registro->ramo_seguro ?? '')),
                1
            );
            if (!array_key_exists($rama, self::RAMAS_INCAPACIDAD)) {
                throw new \InvalidArgumentException(
                    'Rama de incapacidad inválida (1–4). NSS ' . $nss . '.'
                );
            }

            $tipoRiesgo = $this->digits(
                (string) ($registro->tipo_riesgo_sua ?? ($rama === '1' ? '1' : '0')),
                1
            );
            if (!array_key_exists($tipoRiesgo, self::TIPOS_RIESGO)) {
                $tipoRiesgo = '0';
            }
            if ($rama !== '1') {
                $tipoRiesgo = '0';
            }

            $secuela = $this->digits(
                (string) ($registro->secuela_sua ?? ($rama === '1' ? '1' : '0')),
                1
            );
            if (!array_key_exists($secuela, self::SECUELAS_INCAPACIDAD)) {
                $secuela = '0';
            }
            if ($rama !== '1') {
                $secuela = '0';
            }

            $control = $this->digits(
                (string) ($registro->control_incapacidad_sua
                    ?? self::mapControlIncapacidad($registro->tipo ?? '', $rama)),
                1
            );
            if (!array_key_exists($control, self::CONTROLES_INCAPACIDAD)) {
                $control = $rama === '3' ? '6' : '1';
            }

            $linea = $registroPatronal
                . $nss
                . $tipoIncidencia
                . $fechaInicio
                . $folio
                . $dias
                . $porcentaje
                . $rama
                . $tipoRiesgo
                . $secuela
                . $control
                . $fechaTermino;

            if (strlen($linea) !== 57) {
                throw new \RuntimeException(
                    'La línea SUA incapacidades no tiene 57 caracteres (obtuvo ' . strlen($linea) . ').'
                );
            }

            $lineas[] = $linea;
        }

        return implode("\r\n", $lineas) . (count($lineas) ? "\r\n" : '');
    }

    public function nombreArchivo(string $tipo): string
    {
        $etiqueta = match ($tipo) {
            self::TIPO_TRABAJADORES => 'TRABAJADORES_ALTAS',
            self::TIPO_AFILIATORIOS => 'DATOS_AFILIATORIOS',
            self::TIPO_MOVIMIENTOS => 'MOVIMIENTOS',
            self::TIPO_CREDITO => 'MOVIMIENTOS_CREDITO',
            self::TIPO_INCAPACITADOS => 'DATOS_INCAPACIDADES',
            default => strtoupper($tipo),
        };

        return 'SUA_' . $etiqueta . '_' . date('Ymd_His') . '.txt';
    }

    public static function mapRamaIncapacidad(string $ramoSeguro): string
    {
        $ramo = strtoupper(trim($ramoSeguro));

        return match (true) {
            str_contains($ramo, 'RIESGO') => '1',
            str_contains($ramo, 'MATERNIDAD') => '3',
            str_contains($ramo, 'LICENCIA') || str_contains($ramo, '140') => '4',
            str_contains($ramo, 'ENFERMEDAD') || $ramo === 'EG' => '2',
            default => '2',
        };
    }

    public static function mapControlIncapacidad(string $tipoCertificado, string $rama): string
    {
        $tipo = strtoupper(trim($tipoCertificado));

        if ($rama === '3') {
            return match (true) {
                str_contains($tipo, 'ENLACE') => '7',
                str_contains($tipo, 'POST') => '8',
                default => '6',
            };
        }

        if ($rama === '4') {
            return '0';
        }

        return match (true) {
            str_contains($tipo, 'SUBSEC') => '3',
            str_contains($tipo, 'FINAL') || str_contains($tipo, 'ALTA') => '4',
            str_contains($tipo, 'UNICA') || str_contains($tipo, 'ÚNICA') => '1',
            str_contains($tipo, 'INICIAL') => '2',
            default => '2',
        };
    }

    /**
     * INDEFINIDA → 1 (Permanente). Cualquier otro tipo usa el valor seleccionado en el modal.
     */
    protected function resolverTipoTrabajador(string $tipoContratacion, string $tipoManual): string
    {
        if (strtoupper(trim($tipoContratacion)) === 'INDEFINIDA') {
            return '1';
        }

        $tipoManual = $this->digits($tipoManual, 1);
        if (!array_key_exists($tipoManual, self::TIPOS_TRABAJADOR)) {
            return '2';
        }

        return $tipoManual;
    }

    /**
     * Sugiere jornada SUA por empleado a partir de tblempleados_horarios.
     *
     * @param  array<int|string>  $idsEmpleados
     * @return array<int, string> idempleado => código jornada (0-6)
     */
    public function sugerirJornadasPorEmpleados(array $idsEmpleados): array
    {
        $ids = array_values(array_unique(array_map('intval', $idsEmpleados)));
        $resultado = [];
        foreach ($ids as $id) {
            $resultado[$id] = '0';
        }

        if ($ids === []) {
            return $resultado;
        }

        $filas = DB::table('tblempleados_horarios as eh')
            ->leftJoin('tblhorarios as h', 'h.id', '=', 'eh.id_horario')
            ->whereIn('eh.id_empleado', $ids)
            ->select(
                'eh.id_empleado',
                'eh.id_dia',
                'eh.id_horario',
                'h.tipo',
                'h.entrada',
                'h.salida'
            )
            ->get()
            ->groupBy(fn ($row) => (int) $row->id_empleado);

        foreach ($ids as $id) {
            $resultado[$id] = $this->sugerirJornadaDesdeHorarios($filas->get($id, collect()));
        }

        return $resultado;
    }

    /**
     * Valor más frecuente entre jornadas sugeridas (para preseleccionar el select).
     *
     * @param  array<int, string>  $jornadasPorEmpleado
     */
    public function jornadaSugeridaMayoritaria(array $jornadasPorEmpleado, string $fallback = '0'): string
    {
        if ($jornadasPorEmpleado === []) {
            return $fallback;
        }

        $conteo = array_count_values(array_map('strval', array_values($jornadasPorEmpleado)));
        arsort($conteo);
        $codigo = (string) array_key_first($conteo);

        return array_key_exists($codigo, self::JORNADAS) ? $codigo : $fallback;
    }

    /**
     * @param  Collection<int, object>  $horariosEmpleado
     */
    public function sugerirJornadaDesdeHorarios(Collection $horariosEmpleado): string
    {
        if ($horariosEmpleado->isEmpty()) {
            return '0';
        }

        $diasLaborales = 0;
        $horasValidas = [];

        foreach ($horariosEmpleado as $fila) {
            $tipo = strtoupper(trim((string) ($fila->tipo ?? '')));
            $horas = $this->calcularHorasJornada($fila->entrada ?? null, $fila->salida ?? null);
            $esDescanso = str_contains($tipo, 'DESCANSO')
                || (int) ($fila->id_horario ?? 0) === 0
                || ($horas !== null && $horas <= 0.01 && $tipo === '');

            if ($esDescanso) {
                continue;
            }

            // Día con horario asignado distinto a descanso = día laboral.
            if ($tipo !== '' || (int) ($fila->id_horario ?? 0) > 0) {
                $diasLaborales++;
                if ($horas !== null && $horas >= 0.5 && $horas <= 24) {
                    $horasValidas[] = $horas;
                }
            }
        }

        if ($diasLaborales <= 0) {
            return '0';
        }

        // Si todos los días con horas conocidas son menores a 8 → jornada reducida.
        if ($horasValidas !== [] && max($horasValidas) < 8) {
            return '6';
        }

        // 5 o más días laborales se consideran Semana Completa.
        if ($diasLaborales >= 5) {
            return '0';
        }

        return (string) $diasLaborales;
    }

    protected function calcularHorasJornada($entrada, $salida): ?float
    {
        if (empty($entrada) || empty($salida)) {
            return null;
        }

        try {
            $inicio = Carbon::createFromFormat('H:i:s', strlen((string) $entrada) === 5 ? $entrada . ':00' : (string) $entrada);
            $fin = Carbon::createFromFormat('H:i:s', strlen((string) $salida) === 5 ? $salida . ':00' : (string) $salida);
            if ($fin->lte($inicio)) {
                $fin->addDay();
            }

            return round($inicio->diffInMinutes($fin) / 60, 2);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function tieneCreditoInfonavit(object $empleado): bool
    {
        $tipo = strtoupper(trim((string) ($empleado->nombreinfonavit ?? '')));
        $credito = preg_replace('/\s+/', '', (string) ($empleado->numero_credito_infonavit ?? ''));

        if ($tipo === '' || $tipo === 'N/A' || $tipo === 'NA') {
            return false;
        }

        if ($credito === '' || $credito === '0' || preg_match('/^0+$/', $credito)) {
            return false;
        }

        return true;
    }

    /**
     * Mapea tblnominas.id_tipoinfonavit / tbltipoinfonavit.Nombre a tipo SUA:
     * 1 Porcentaje, 2 Cuota fija monetaria, 3 Factor de descuento.
     *
     * Catálogo IOHISA actual:
     * 1 Cuota Fija Monetaria → 2
     * 2 Factor de Descuento → 3
     * 3 N/A → 0
     */
    public static function tipoDescuentoDesdeCatalogo(string $nombre, $idTipoInfonavit = null): string
    {
        $id = (int) $idTipoInfonavit;
        if ($id === 1) {
            return '2';
        }
        if ($id === 2) {
            return '3';
        }
        if ($id === 3) {
            return '0';
        }

        $nombre = strtoupper(trim($nombre));
        $nombre = preg_replace('/\s+/', ' ', $nombre) ?? $nombre;

        return match ($nombre) {
            'CF', 'CUOTA FIJA', 'CUOTA FIJA MONETARIA' => '2',
            'FD', 'FACTOR', 'FACTOR DE DESCUENTO', 'VSM', 'CUOTA FIJA EN VSM' => '3',
            'PORCENTAJE', 'P', '%' => '1',
            'N/A', 'NA', 'N / A' => '0',
            default => '0',
        };
    }

    protected function mapTipoDescuentoInfonavit(string $nombre, $idTipoInfonavit = null): string
    {
        return self::tipoDescuentoDesdeCatalogo($nombre, $idTipoInfonavit);
    }

    /**
     * Número de crédito INFONAVIT: 10 dígitos > 0.
     * Prefijo 01–32 o > 72 según criterios SUA (no bloquea; solo estructura).
     * El DV oficial no se valida aquí: la rutina pública no coincide con créditos reales.
     */
    protected function assertNumeroCreditoInfonavit(string $numeroCredito, string $nss): void
    {
        if (!preg_match('/^\d{10}$/', $numeroCredito) || (int) $numeroCredito <= 0) {
            throw new \InvalidArgumentException(
                'El número de crédito INFONAVIT debe ser numérico de 10 dígitos y mayor a cero (NSS ' . $nss . ').'
            );
        }
    }

    /**
     * Fecha de movimiento de crédito: DDMMAAAA, día/mes válidos, año > 1972.
     */
    protected function formatFechaMovimientoCredito($fecha, string $nss): string
    {
        return $this->assertFechaSua($fecha, $nss, 'movimiento de crédito', 1972);
    }

    /**
     * Fecha de incapacidad: DDMMAAAA, día/mes válidos, año > 1997.
     */
    protected function formatFechaIncapacidadSua($fecha, string $nss, string $etiqueta): string
    {
        return $this->assertFechaSua($fecha, $nss, 'de ' . $etiqueta . ' de incapacidad', 1997);
    }

    protected function assertFechaSua($fecha, string $nss, string $contexto, int $anioMinimoExclusivo): string
    {
        $fechaFmt = $this->formatFechaSua($fecha);
        if ($fechaFmt === str_repeat('0', 8) || (int) $fechaFmt <= 0) {
            throw new \InvalidArgumentException(
                'La fecha ' . $contexto . ' es obligatoria y debe ser mayor a cero (NSS ' . $nss . ').'
            );
        }

        $dia = (int) substr($fechaFmt, 0, 2);
        $mes = (int) substr($fechaFmt, 2, 2);
        $anio = (int) substr($fechaFmt, 4, 4);

        if ($mes < 1 || $mes > 12) {
            throw new \InvalidArgumentException(
                'El mes de la fecha ' . $contexto . ' debe estar entre 01 y 12 (NSS ' . $nss . ').'
            );
        }

        if ($anio <= $anioMinimoExclusivo) {
            throw new \InvalidArgumentException(
                'El año de la fecha ' . $contexto . ' debe ser mayor a ' . $anioMinimoExclusivo . ' (NSS ' . $nss . ').'
            );
        }

        if (!checkdate($mes, $dia, $anio)) {
            throw new \InvalidArgumentException(
                'La fecha ' . $contexto . ' no es válida (día/mes/año, incl. bisiestos). NSS ' . $nss . '.'
            );
        }

        return $fechaFmt;
    }

    protected function formatValorDescuento(string $tipoDesc, float $valor): string
    {
        $valor = max(0, $valor);

        $raw = match ($tipoDesc) {
            // 00EEDD00 — 2 enteros + 2 decimales
            '1' => '00' . str_pad((string) min((int) round($valor * 100), 9999), 4, '0', STR_PAD_LEFT) . '00',
            // EEEEEDD0 — 5 enteros + 2 decimales
            '2' => str_pad((string) min((int) round($valor * 100), 9999999), 7, '0', STR_PAD_LEFT) . '0',
            // 0EEEDDDD — 3 enteros + 4 decimales
            '3' => '0' . str_pad((string) min((int) round($valor * 10000), 9999999), 7, '0', STR_PAD_LEFT),
            default => str_repeat('0', 8),
        };

        return str_pad(substr($raw, 0, 8), 8, '0', STR_PAD_LEFT);
    }

    protected function formatNombreSua(
        string $apellidoPaterno,
        string $apellidoMaterno,
        string $primerNombre,
        string $segundoNombre = ''
    ): string {
        $paterno = $this->normalizarTextoSua($apellidoPaterno);
        $materno = $this->normalizarTextoSua($apellidoMaterno);
        $nombres = trim($this->normalizarTextoSua($primerNombre) . ' ' . $this->normalizarTextoSua($segundoNombre));
        $nombres = preg_replace('/\s+/', ' ', $nombres) ?: '';

        if ($paterno === '' && $materno !== '') {
            $compuesto = $materno . '$$' . $nombres;
        } else {
            $compuesto = $paterno . '$' . $materno . '$' . $nombres;
        }

        return $this->alpha($compuesto, 50);
    }

    protected function formatSalarioDiarioIntegrado($salario): string
    {
        $monto = max(0, (float) $salario);
        $centavos = (int) round($monto * 100);

        return str_pad((string) $centavos, 7, '0', STR_PAD_LEFT);
    }

    protected function formatFechaSua($fecha): string
    {
        if (empty($fecha) || $fecha === '0000-00-00') {
            return str_repeat('0', 8);
        }

        try {
            if (is_string($fecha) && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fecha)) {
                $carbon = Carbon::createFromFormat('d/m/Y', $fecha);
            } else {
                $carbon = Carbon::parse($fecha);
            }

            return $carbon->format('dmY');
        } catch (\Throwable $e) {
            return str_repeat('0', 8);
        }
    }

    /**
     * Si la fecha del aviso es anterior al 1/jul/1997 → 30061997.
     */
    protected function formatFechaInicioDescuento($fecha): string
    {
        if (empty($fecha) || $fecha === '0000-00-00') {
            return str_repeat('0', 8);
        }

        try {
            if (is_string($fecha) && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fecha)) {
                $carbon = Carbon::createFromFormat('d/m/Y', $fecha);
            } else {
                $carbon = Carbon::parse($fecha);
            }

            $limite = Carbon::create(1997, 7, 1)->startOfDay();
            if ($carbon->lt($limite)) {
                return '30061997';
            }

            return $carbon->format('dmY');
        } catch (\Throwable $e) {
            return str_repeat('0', 8);
        }
    }

    protected function claveLugarNacimiento(object $empleado): string
    {
        $clave = strtoupper(trim((string) ($empleado->clave_lugar_nacimiento ?? '')));
        if ($clave !== '') {
            return $clave;
        }

        $curp = strtoupper(preg_replace('/\s+/', '', (string) ($empleado->curp ?? '')) ?? '');
        if (strlen($curp) >= 13) {
            return substr($curp, 11, 2);
        }

        return '';
    }

    protected function formatSexoSua($sexo, $curp = null): string
    {
        $sexo = strtoupper(trim((string) $sexo));
        if (in_array($sexo, ['M', 'F'], true)) {
            return $sexo;
        }
        if (in_array($sexo, ['H', 'HOMBRE', 'MASCULINO'], true)) {
            return 'M';
        }
        if (in_array($sexo, ['MUJER', 'FEMENINO'], true)) {
            return 'F';
        }

        $curp = strtoupper(preg_replace('/\s+/', '', (string) $curp) ?? '');
        if (strlen($curp) >= 11) {
            $sexoCurp = $curp[10];
            if ($sexoCurp === 'H') {
                return 'M';
            }
            if ($sexoCurp === 'M') {
                return 'F';
            }
        }

        return 'M';
    }

    /**
     * 0 Fijo, 1 Variable, 2 Mixto.
     */
    protected function resolverTipoSalario(object $empleado): string
    {
        $fijo = (float) ($empleado->salario_fijo ?? 0) > 0.009;
        $variable = (float) ($empleado->excedente ?? 0) > 0.009
            || (float) ($empleado->efectivo ?? 0) > 0.009;

        if ($fijo && $variable) {
            return '2';
        }
        if ($variable && !$fijo) {
            return '1';
        }

        return '0';
    }

    protected function normalizarTextoSua(string $texto): string
    {
        $texto = trim($texto);
        if ($texto === '') {
            return '';
        }

        $texto = mb_strtoupper($texto, 'UTF-8');
        $texto = str_replace(['Ñ', 'ñ'], '/', $texto);

        return $texto;
    }

    protected function alpha(string $valor, int $longitud): string
    {
        $valor = $this->normalizarTextoSua($valor);
        $valor = preg_replace('/\s+/', ' ', $valor) ?? '';

        if (mb_strlen($valor, 'UTF-8') > $longitud) {
            $valor = mb_substr($valor, 0, $longitud, 'UTF-8');
        }

        // SUA trabaja en longitudes de bytes/caracteres fijos (ASCII ampliado).
        $ascii = $this->toSingleByte($valor);

        return str_pad(substr($ascii, 0, $longitud), $longitud, ' ', STR_PAD_RIGHT);
    }

    /**
     * Alfanumérico afiliatorios: mayúsculas sin acentos, sin caracteres especiales.
     */
    protected function alphaAfiliatorio(string $valor, int $longitud): string
    {
        $valor = $this->normalizarTextoSua($valor);
        $ascii = $this->toSingleByte($valor);
        // Quita acentos residuales y especiales (; , & * " etc.), conserva letras, números, espacio y /.
        $ascii = preg_replace('/[^A-Z0-9 \/]/', '', $ascii) ?? '';
        $ascii = preg_replace('/\s+/', ' ', $ascii) ?? '';
        $ascii = trim($ascii);

        return str_pad(substr($ascii, 0, $longitud), $longitud, ' ', STR_PAD_RIGHT);
    }

    protected function digits(string $valor, int $longitud): string
    {
        $digitos = preg_replace('/\D+/', '', $valor) ?? '';

        if (strlen($digitos) > $longitud) {
            $digitos = substr($digitos, -$longitud);
        }

        return str_pad($digitos, $longitud, '0', STR_PAD_LEFT);
    }

    protected function toSingleByte(string $valor): string
    {
        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $valor);
        if ($converted === false) {
            $converted = preg_replace('/[^\x20-\x7E\/$]/', '', $valor) ?? '';
        }

        return $converted;
    }
}
