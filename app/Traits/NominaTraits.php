<?php
namespace App\Traits;

use Illuminate\Support\Facades\Request;
use Carbon\Carbon;
use App\Traits\DatosimpleTraits;
use App\Models\Nominas_pagosenc;
use App\Models\Nominas_pagosdet;
use DB;
use App\Models\aguinaldos_enc;
use App\Models\aguinaldos_det;
use App\Models\conceptos_nomina;
use App\Models\cesantia_vejez;
use App\Models\aportaciones_patronales_enc;
use App\Models\aportaciones_patronales_det;
use App\Models\NominaAsistencias;

trait NominaTraits
{
    use DatosimpleTraits;

    public function calcular_nomia(string $paso, int $id, string $fecha_inicio, string $fecha_fin)
    {
        $idTipoNomina = (int) (Nominas_pagosenc::where('id', $id)->value('idtiponomina') ?? 2);

        return match ($idTipoNomina) {
            1 => $this->calcular_nomina_semanal($paso, $id, $fecha_inicio, $fecha_fin),
            3 => $this->calcular_nomina_mensual($paso, $id, $fecha_inicio, $fecha_fin),
            default => $this->calcular_nomina_quincenal($paso, $id, $fecha_inicio, $fecha_fin),
        };
    }

    public function calcular_nomina_semanal(string $paso, int $id, string $fecha_inicio, string $fecha_fin)
    {
        return $this->ejecutarCalculoNomina($paso, $id, $fecha_inicio, $fecha_fin, 1);
    }

    public function calcular_nomina_quincenal(string $paso, int $id, string $fecha_inicio, string $fecha_fin)
    {
        return $this->ejecutarCalculoNomina($paso, $id, $fecha_inicio, $fecha_fin, 2);
    }

    public function calcular_nomina_mensual(string $paso, int $id, string $fecha_inicio, string $fecha_fin)
    {
        return $this->ejecutarCalculoNomina($paso, $id, $fecha_inicio, $fecha_fin, 3);
    }

    public function validarRangoFechasTipoNomina(int $idTipoNomina, string $fecha_inicio, string $fecha_fin): bool
    {
        $dias = Carbon::parse($fecha_inicio)->diffInDays(Carbon::parse($fecha_fin)) + 1;

        return match ($idTipoNomina) {
            1 => $dias >= 6 && $dias <= 8,
            2 => $dias >= 13 && $dias <= 16,
            3 => $dias >= 28 && $dias <= 31,
            default => true,
        };
    }

    protected function diasEnPeriodo(string $fecha_inicio, string $fecha_fin): int
    {
        return Carbon::parse($fecha_inicio)->diffInDays(Carbon::parse($fecha_fin)) + 1;
    }

    protected function diasSolapadosEnPeriodo(
        string $inicioRegistro,
        string $finRegistro,
        string $inicioPeriodo,
        string $finPeriodo
    ): int {
        $inicio = Carbon::parse($inicioRegistro);
        $fin = Carbon::parse($finRegistro);
        $inicioPeriodoCarbon = Carbon::parse($inicioPeriodo);
        $finPeriodoCarbon = Carbon::parse($finPeriodo);

        if ($inicio->lt($inicioPeriodoCarbon)) {
            $inicio = $inicioPeriodoCarbon->copy();
        }

        if ($fin->gt($finPeriodoCarbon)) {
            $fin = $finPeriodoCarbon->copy();
        }

        if ($inicio->gt($fin)) {
            return 0;
        }

        return $inicio->diffInDays($fin) + 1;
    }

    protected function obtenerDiasIncapacidadEmpleadoEnPeriodo(
        int $idempleado,
        string $fecha_inicio,
        string $fecha_fin
    ): int {
        $registros = DB::table('tblincapacidades')
            ->where('id_empleado', $idempleado)
            ->where('fecha_inicio', '<=', $fecha_fin)
            ->where('fecha_fin', '>=', $fecha_inicio)
            ->get(['fecha_inicio', 'fecha_fin']);

        $total = 0;

        foreach ($registros as $registro) {
            $total += $this->diasSolapadosEnPeriodo(
                $registro->fecha_inicio,
                $registro->fecha_fin,
                $fecha_inicio,
                $fecha_fin
            );
        }

        return $total;
    }

    protected function obtenerDiasVacacionesEmpleadoEnPeriodo(
        int $idempleado,
        string $fecha_inicio,
        string $fecha_fin
    ): int {
        $registros = DB::table('tblvacaciones')
            ->where('id_empleado', $idempleado)
            ->where('fecha_inicio', '<=', $fecha_fin)
            ->where('fecha_fin', '>=', $fecha_inicio)
            ->get(['fecha_inicio', 'fecha_fin']);

        $total = 0;

        foreach ($registros as $registro) {
            $total += $this->diasSolapadosEnPeriodo(
                $registro->fecha_inicio,
                $registro->fecha_fin,
                $fecha_inicio,
                $fecha_fin
            );
        }

        return $total;
    }

    protected function resolverAusenciasDesdeTablas(
        int $idempleado,
        string $fecha_inicio,
        string $fecha_fin
    ): array {
        $diasIncapacidad = $this->obtenerDiasIncapacidadEmpleadoEnPeriodo($idempleado, $fecha_inicio, $fecha_fin);
        $diasVacaciones = $this->obtenerDiasVacacionesEmpleadoEnPeriodo($idempleado, $fecha_inicio, $fecha_fin);

        return [
            'dias_incapacidad' => $diasIncapacidad,
            'dias_vaciones' => $diasVacaciones,
            'faltas_reta_aus' => $diasIncapacidad + $diasVacaciones,
        ];
    }

    protected function parametrosTipoNominaSemanal(string $fecha_inicio, string $fecha_fin): array
    {
        $dias = max($this->diasEnPeriodo($fecha_inicio, $fecha_fin), 7);

        return [
            'id_tipo_nomina' => 1,
            'dias_calculo' => $dias,
            'subsidio_tipo' => 'Semanal',
        ];
    }

    protected function parametrosTipoNominaQuincenal(string $fecha_inicio, string $fecha_fin): array
    {
        $dias = max($this->diasEnPeriodo($fecha_inicio, $fecha_fin), 15);

        return [
            'id_tipo_nomina' => 2,
            'dias_calculo' => $dias,
            'subsidio_tipo' => 'Quincenal',
        ];
    }

    protected function parametrosTipoNominaMensual(string $fecha_inicio, string $fecha_fin): array
    {
        $fecha1 = Carbon::parse($fecha_inicio);
        $dias = max($this->diasEnPeriodo($fecha_inicio, $fecha_fin), cal_days_in_month(CAL_GREGORIAN, (int) $fecha1->format('m'), (int) $fecha1->format('Y')));

        return [
            'id_tipo_nomina' => 3,
            'dias_calculo' => $dias,
            'subsidio_tipo' => 'Mensual',
        ];
    }

    protected function resolverParametrosTipoNomina(int $idTipoNomina, string $fecha_inicio, string $fecha_fin): array
    {
        return match ($idTipoNomina) {
            1 => $this->parametrosTipoNominaSemanal($fecha_inicio, $fecha_fin),
            3 => $this->parametrosTipoNominaMensual($fecha_inicio, $fecha_fin),
            default => $this->parametrosTipoNominaQuincenal($fecha_inicio, $fecha_fin),
        };
    }

    /**
     * Salario diario mínimo según zona del empleado (tblnominas.zona).
     * RP  -> salario_diario_minimo  (S. D. Minimo (RP))
     * ZFN -> salario_diario_minimo_2 (S.D. Minimo (ZFN))
     */
    protected function resolverSalarioDiarioMinimoPorZona(?string $zona): float
    {
        static $salarioMinimoRp = null;
        static $salarioMinimoZfn = null;

        if ($salarioMinimoRp === null) {
            $salarioMinimoRp = (float) (conceptos_nomina::where('nombre', 'salario_diario_minimo')->value('valor') ?? 0);
            $salarioMinimoZfn = (float) (conceptos_nomina::where('nombre', 'salario_diario_minimo_2')->value('valor') ?? $salarioMinimoRp);
        }

        $zonaNormalizada = strtoupper(trim((string) ($zona ?? 'RP')));

        return $zonaNormalizada === 'ZFN' ? $salarioMinimoZfn : $salarioMinimoRp;
    }

    protected function ejecutarCalculoNomina(string $paso, int $id, string $fecha_inicio, string $fecha_fin, int $idTipoNomina)
    {
        $save = 0;
        $insert = 0;
        $date = Carbon::now();
        $date = $date->format('Y-m-d');
        $idpagonom = $id;
        $UMA = conceptos_nomina::where('nombre', 'uma_diaria')->first()->valor;

         //INICIO DE CALCULO
            if($paso == "Calcular"){
                $nominaEnc = Nominas_pagosenc::find($idpagonom);
                $tipoEmpresa = strtoupper((string) ($nominaEnc->tipo_empresa ?? 'GENERAL'));
                $idEmpresa = $nominaEnc->id_empresa ? (int) $nominaEnc->id_empresa : null;
                $vardatos_nomina = $this->datos_nomina($tipoEmpresa, $idEmpresa);

            }elseif($paso == "Recalcular"){
                $vardatos_nomina =  $this->datos_nomina_insertada($idpagonom);
                foreach($vardatos_nomina as $key){
                    $fecha_inicio = $key->fecha_inicio;
                    $fecha_fin = $key->fecha_fin;
                }

            }else{
                return "error";
            }

        //dias de la nomina x mes
            $fecha1 =  Carbon::parse("$fecha_inicio");
            $fecha2 = Carbon::parse($fecha_fin);
            $parametros = $this->resolverParametrosTipoNomina($idTipoNomina, $fecha_inicio, $fecha_fin);
            $diascalculo = (int) $parametros['dias_calculo'];
            $subsidioTipo = $parametros['subsidio_tipo'];


        //AÑADE PAGO DE PRESTAMO EN DESUDORES NO FISCAL
            $listacreditoniom =$this->obtenerprestmoempxfecha($fecha_inicio,$fecha_fin);
            $update =  DB::select('update  tblnominas set deudores_no_fiscal = ?;', [0]);
            if ($listacreditoniom->isEmpty()) {
            }else{
                foreach($listacreditoniom as $lista)
                {
                    $update =  DB::select('update  tblnominas set deudores_no_fiscal = ? where idempleado = ? ', [$lista->pago_quincenal,$lista->id_empleado]);
                }
            }

        //RECORRER EMPLEADOS 
        foreach($vardatos_nomina as $item){
            // if($item->idempleado == 31){
            $idempleado = $item->idempleado;
            $idnomina = $item->id;
            $dias_laborados = $diascalculo;

            //SALARIO DIARIO MINIMO SEGUN ZONA (RP / ZFN)
                $salarioDiarioMinimo = $this->resolverSalarioDiarioMinimoPorZona($item->zona ?? null);
                $salario_minimo_periodo = $salarioDiarioMinimo * $diascalculo;
            
            //SALARIO DIARIO y EXCEDENTE
                $salario_diario = $item->salario_fijo;
                $salario_excedente = $item->excedente;

            //SALARIO POR HORA
                $hora_fiscal = $salario_diario/8;

                if($salario_excedente > 0){
                    $hora_excedente = $salario_excedente/8;
                }else{
                    $hora_excedente = 0;
                }

            //SUELDO QUINCENAL
                $sueldo_fiscal_quincenal = $salario_diario*15;
                $sueldo_excedente_quincenal = $salario_excedente*15;

            //AUSENCIAS DESDE tblincapacidades Y tblvacaciones
                $ausencias = $this->resolverAusenciasDesdeTablas($idempleado, $fecha_inicio, $fecha_fin);
                $dias_incapacidad = $ausencias['dias_incapacidad'];
                $dias_vaciones = $ausencias['dias_vaciones'];
                $faltas_reta_aus = $ausencias['faltas_reta_aus'];
                $usaAsistenciasImportadas = false;

            //DIAS Y FALTAS DESDE ASISTENCIAS IMPORTADAS
                $resumenAsistencias = NominaAsistencias::obtenerResumenEmpleado(
                    $idempleado,
                    $idpagonom,
                    $fecha_inicio,
                    $fecha_fin
                );

                if ($resumenAsistencias['tiene_asistencias']) {
                    $usaAsistenciasImportadas = true;
                    $dias_laborados = $resumenAsistencias['dias_laborados'];
                    $faltas_reta_aus = $resumenAsistencias['faltas'] + $dias_incapacidad + $dias_vaciones;
                }

            //CARGOS EXTRAS
                if($paso == "Recalcular"){
                    //SUMAN
                        $percepcion_extraordinaria = $item->percepcion_extraordinaria;
                        $otros = $item->otros;
                        $horas_extras = $item->horas_extras;
                        $dias_prima_dominical = $item->dias_prima_dominical;
                        $dias_descanso = $item->dias_descanso;
                        $bono = $item->bono;
                        $viaticos = $item->viaticos ?? 0;
                        $dias_prima_vacacional = $item->dias_prima_vacacional;
                 
                    
                    //RESTAN
                        $deudores_fiscal = $item->deudores_fiscal;
                        $fonacot = $item->fonacot;
                }else{
                    //SUMAN
                        $percepcion_extraordinaria = 0;
                        $otros = 0;
                        $horas_extras = 0;
                        $dias_prima_dominical = 0;
                        $dias_descanso = 0;
                        $bono = 0;
                        $viaticos = 0;
                        $dias_prima_vacacional = 0;
                    
                    //RESTAN
                        $deudores_fiscal = 0;
                        $fonacot = 0;
                }

            //VACACIONES
                    //DIAS DE VACIONES PAGADOS
                    if($dias_vaciones > 0){
                            $pago_dias_vacaciones_fis = $dias_vaciones * $salario_diario;
                            $pago_dias_vacaciones_exce = $dias_vaciones * $salario_excedente; 
                            $pago_dias_vacaciones = $pago_dias_vacaciones_fis + $pago_dias_vacaciones_exce;
                    }else{
                        $pago_dias_vacaciones = 0;
                        $pago_dias_vacaciones_fis = 0;
                        $pago_dias_vacaciones_exce = 0;
                    }

                    //PRIMA VACACIONAL
                    if($dias_prima_vacacional > 0){
                        //PRIMA FIS
                        $pago_prima_vacacional_fis = $salario_diario * $dias_prima_vacacional * 0.25;
                        
                        //PRIMA EXCE
                        $pago_prima_vacacional_exce = $salario_excedente * $dias_prima_vacacional * 0.25;

                        //TOTAL PRIMA VACACIONAL
                        $pago_prima_vacacional = $pago_prima_vacacional_fis + $pago_prima_vacacional_exce;

                        //SALDO GRAVABLE PARA ISR
                        $condicion_prima = ($UMA * $diascalculo);
                        $prima_vacacional_gravable = 0;

                        if($pago_prima_vacacional_fis <= $condicion_prima){
                            $prima_vacacional_gravable = 0;
                        }else{
                            $prima_vacacional_gravable = $pago_prima_vacacional_fis - $condicion_prima;
                        }

                    }else{
                        $prima_vacacional_gravable = 0;
                        $pago_prima_vacacional = 0;
                        $pago_prima_vacacional_fis = 0;
                        $pago_prima_vacacional_exce = 0;
                    }

            //DIAS DE DESCANSO
                $pago_dias_descanso = 0;
                if($dias_descanso > 0 ){
                    $pago_dias_descanso = ($salario_diario + $salario_excedente)*$dias_descanso;
                }

            //PRIMA DOMINICAL 
                $pago_prima_dominical = 0;
                if($dias_prima_dominical > 0 ){
                    $pago_prima_dominical =  (($salario_diario + $salario_excedente)*.25)*$dias_prima_dominical;
                }
           
            //HORAS EXTRAS A PAGAR
                if($horas_extras > 0 && $horas_extras <= 8){
                    $horas_extras_pago_f =  $hora_fiscal * $horas_extras;
                    $horas_extras_pago_e = $hora_excedente * $horas_extras;
                    $total_horas_extras = $horas_extras_pago_f + $horas_extras_pago_e;                    
                }elseif($horas_extras > 0 && $horas_extras > 8){
                    $horas_residuo = $horas_extras - 8; 
                    $pago_horas_residuo = ($hora_fiscal + $hora_excedente) * $horas_residuo;

                    $horas_extras_pago_f =  $hora_fiscal * 8;
                    $horas_extras_pago_e = ($hora_excedente * 8) + $pago_horas_residuo;
                    $total_horas_extras = $horas_extras_pago_f + $horas_extras_pago_e;
                }else{
                    $horas_extras_pago_f = 0;
                    $horas_extras_pago_e = 0;
                    $total_horas_extras = 0;
                }
            
            //DEDUCCION POR FALTAS (INCAPACIDAD + VACACIONES EN EL PERIODO)
                if($faltas_reta_aus > 0){
                    $total_faltas_reta_aus = ($salario_diario + $salario_excedente) * $faltas_reta_aus;
                }else{
                    $total_faltas_reta_aus = 0;
                }
        
            //DIAS LABORADOS
                if (!$usaAsistenciasImportadas) {
                    $dias_laborados = max($dias_laborados - $faltas_reta_aus, 0);
                }

            //TOTAL SUELDO (SUELDO BASADO EN DIAS LABORADOS)
                $total_sueldo = ($salario_diario + $salario_excedente ) * $dias_laborados;

            //SUELDOS QUINCENALES (EN BASE A DIAS LABORADOS)
                $sueldo_fiscal = $salario_diario * $dias_laborados;
                $sueldo_excedente = $salario_excedente * $dias_laborados;
                

            //DEUDORES
                $deudores_no_fiscal = $item->deudores_no_fiscal;
                $total_deudores = $deudores_fiscal + $deudores_no_fiscal;

            //INFONAVIT
                $factor_sua = $item->factor_sua;
                $INFONAVIT = 0;
                if($item->id_tipoinfonavit == 1){
                    $INFONAVIT = $item->factor_sua;
                }
                elseif($item->id_tipoinfonavit == 2){
                    $INFONAVIT = ((($item->factor_sua * 100.81) + 7.5)/30)*$diascalculo;
                }
                else{
                    $INFONAVIT = 0;
                }
            
            //IMSS
                $fecha_ingreso_imss = Carbon::parse($item->fecha_ingreso_imss ?? $item->fecha_ingreso);
                $diferencia = $fecha_ingreso_imss->diffInYears($date);
                $selecciona_tarifas_integracion = $this->selecciona_tarifas_integracion($diferencia);
                foreach($selecciona_tarifas_integracion  as $key){
                    $factor_integracion = $key->factor_integracion;
                }

                $IMSS = 0;
                $salario_diario_integrado = 0;
                $salario_diario_integrado = ($salario_diario * $factor_integracion); 
                $limite = ($UMA*3); //325.71
                $condicion = ($salario_diario * $factor_integracion);//1.0493

                if($condicion > $limite || $salario_diario > $salario_minimo_periodo){
                    $IMSS =(((($salario_diario_integrado)-($UMA*3))*0.0040)*$diascalculo) +
                                ((($salario_diario_integrado)*0.0025)*$diascalculo) +
                                ((($salario_diario_integrado)*0.003750)*$diascalculo) +
                                ((($salario_diario_integrado)*0.006250)*$diascalculo)+
                                ((($salario_diario_integrado)*0.011250)*$diascalculo);
                }else{ 
                    $IMSS = 0;
                } 

                
                 
            //DESPENSA
                $despensa = 0;
                // ($salario_diario_integrado* $dias_laborados) * .10

            //ISR
                $ISR = 0;
                $limite_inferior = 0;
                $cuota_fija = 0;
                $porcentaje = 0;
                $limite_ingresos = PHP_FLOAT_MAX;
                $sueldo_isr =  ($salario_diario * $dias_laborados) + $despensa + $pago_dias_vacaciones_fis + $prima_vacacional_gravable;
                $seleciona_tarifa_isr = $this->seleciona_tarifa_isr($sueldo_isr, $idTipoNomina);

                foreach($seleciona_tarifa_isr  as $key){
                    $limite_inferior = $key->limite_inferior;
                    $limite_superior = $key->limite_superior;
                    $cuota_fija = $key->cuota_fija;
                    $porcentaje = ($key->porcentaje/100);
                }

                $excedente = $sueldo_isr - $limite_inferior;
                $impuesto_marginal = $excedente * $porcentaje;
                $impuesto = ($cuota_fija + $impuesto_marginal);
           
            //SUBSIDIO
                $subsidio = 0;
                $seleciona_subsidio = $this->seleciona_subsidio_por_tipo($subsidioTipo);
                foreach($seleciona_subsidio  as $sub){
                    $cuota_fija = $sub->cuota_fija;
                    $limite_ingresos = $sub->limite_ingresos;
                }

                if($sueldo_isr <= $limite_ingresos ){
                    $subsidio = $cuota_fija;
                }else{
                    $subsidio = 0;
                }
            
            //ISR MENOS EL SUBSIDO 
                if($sueldo_isr <= $salario_minimo_periodo){
                    $ISR = 0;
                }else{
                    $ISR = ($impuesto  - $subsidio);

                    if($ISR < 0){
                        $ISR = 0;
                    }
                }

            //TOTALES
                $total_nomina_fiscal = 
                ($sueldo_fiscal + $horas_extras_pago_f + $percepcion_extraordinaria + $otros + 
                $despensa + $pago_dias_vacaciones_fis + $pago_prima_vacacional_fis) - 
                ($ISR + $IMSS + $INFONAVIT + $deudores_no_fiscal + $deudores_fiscal  + $fonacot);

                $pago_nomina_fiscal_global = $total_nomina_fiscal;

                $total_apagar_excedente = ($sueldo_excedente + $horas_extras_pago_e + 
                $pago_dias_descanso + $pago_prima_dominical +  $pago_dias_vacaciones_exce +  
                $pago_prima_vacacional_exce + $bono + $viaticos);

                $pago_nomina_excedente_global = $total_nomina_fiscal;

                $total_apagar = 
                ($total_sueldo + $total_horas_extras + $percepcion_extraordinaria + $otros + $bono + $viaticos  
                + $despensa + $pago_dias_descanso + $pago_prima_dominical + $pago_dias_vacaciones
                + $pago_prima_vacacional) - 
                ($ISR + $IMSS + $INFONAVIT + $deudores_no_fiscal + $deudores_fiscal  + $fonacot);

            //INSERTAR CALCULO
                if($paso == "Recalcular"){
                    $pagonomenc = Nominas_pagosdet::find($item->idpago_detalle);
                }else{
                    $pagonomenc = new Nominas_pagosdet();
                    $pagonomenc->idpagonomina = $idpagonom;
                    $pagonomenc->idempleado = $idempleado;
                    $pagonomenc->idnomina = $idnomina; 
                }

                $pagonomenc->salario_diario_integrado = $salario_diario_integrado;

                $pagonomenc->dias_laborados = $dias_laborados;
                $pagonomenc->sueldo_fiscal = $sueldo_fiscal;
                $pagonomenc->sueldo_excedente = $sueldo_excedente;
                $pagonomenc->total_sueldo = $total_sueldo;

                $pagonomenc->faltas_reta_aus = $faltas_reta_aus;
                $pagonomenc->dias_incapacidad = $dias_incapacidad;
                $pagonomenc->total_faltas_reta_aus = $total_faltas_reta_aus;

                $pagonomenc->horas_extras = $horas_extras;
                $pagonomenc->horas_extras_pago_f = $horas_extras_pago_f;
                $pagonomenc->horas_extras_pago_e = $horas_extras_pago_e;
                $pagonomenc->total_horas_extras = $total_horas_extras;
                
                
                $pagonomenc->deudores_fiscal = $deudores_fiscal;
                $pagonomenc->deudores_no_fiscal = $deudores_no_fiscal;
                $pagonomenc->total_deudores = $total_deudores;

                $pagonomenc->pago_infonavit = $INFONAVIT;
                $pagonomenc->pago_imss = $IMSS;
                $pagonomenc->pago_subsidio = $subsidio;
                $pagonomenc->pago_isr = $ISR;
                $pagonomenc->fonacot = $fonacot;
              
                $pagonomenc->percepcion_extraordinaria = $percepcion_extraordinaria;
                $pagonomenc->despensa = $despensa;
                $pagonomenc->otros = $otros;
                $pagonomenc->bono = $bono;
                $pagonomenc->viaticos = $viaticos;

                $pagonomenc->dias_prima_vacacional = $dias_prima_vacacional;
                $pagonomenc->pago_prima_vacacional_fis = $pago_prima_vacacional_fis;
                $pagonomenc->pago_prima_vacacional_exce = $pago_prima_vacacional_exce;
                $pagonomenc->pago_prima_vacacional = $pago_prima_vacacional;

                $pagonomenc->dias_vaciones = $dias_vaciones;
                $pagonomenc->pago_dias_vacaciones_fis = $pago_dias_vacaciones_fis;
                $pagonomenc->pago_dias_vacaciones_exce = $pago_dias_vacaciones_exce;
                $pagonomenc->pago_dias_vacaciones = $pago_dias_vacaciones;

                $pagonomenc->dias_descanso = $dias_descanso;
                $pagonomenc->pago_dias_descanso = $pago_dias_descanso;
                $pagonomenc->dias_prima_dominical = $dias_prima_dominical;
                $pagonomenc->pago_prima_dominical = $pago_prima_dominical;
               
                $pagonomenc->total_nomina_fiscal = $total_nomina_fiscal;
                $pagonomenc->total_apagar_excedente = $total_apagar_excedente;

                $pagonomenc->pago_nomina_fiscal_global = $pago_nomina_fiscal_global;
                $pagonomenc->pago_nomina_excedente_global = $pago_nomina_excedente_global;

                $pagonomenc->total_apagar = $total_apagar;
                $pagonomenc->updated_by=auth()->user()->name;
            
                if($pagonomenc->save()){
                    $save += 1;
                }
        }
        

        if($save >= $insert){
            $pagonomenc = Nominas_pagosenc::find($idpagonom);
            $pagonomenc->estado_nomina = 'Edicion';
            $pagonomenc->updated_by=auth()->user()->name;
            $pagonomenc->save();

            return "exito";
        }else{
            return "error";
        }

    }

    public function Listadoinfolayout(int $id)
    {
        $listaempleadoslayout = DB::select("select 'D' AS tipo_registro,
        date(now()) as fechaaplicacion,
        b.idempleado as numero_emppleado,
        c.idbanca,
        '                                                                                ' as referencia_servicioyOrdenante,
        b.total_nomina_fiscal as importe,
        '072' as numero_banco_receptor,
        '01' as tipo_cuenta,
        c.numero_cuenta,
        '0' as tipo_movimiento,
        ' ' as accion ,
        '00000000' as importe_ivadelaoperacion,
        '                  ' as filtro
        from tblnominas_pagoenc a 
        inner join tblnominas_pagodet b on a.id = b.idpagonomina
        inner join tblnominas c on c.idempleado = b.idempleado
        where a.id = ? and c.idbancos = 5
        order by c.idbanca ASC;",[$id]);
         return collect($listaempleadoslayout);
    }

    /**
     * Layout Banorte: pago a proveedores/terceros (texto delimitado por tabulaciones).
     * Incluye empleados Banorte (idbancos = 5) con importe fiscal > 0.
     */
    public function ListadoinfolayoutBanorte(int $id)
    {
        $lista = DB::select("
            SELECT
                b.idempleado,
                c.idbanca,
                c.numero_cuenta,
                b.total_nomina_fiscal AS importe,
                a.nombre_nomina,
                emp.primer_nombre,
                emp.segundo_nombre,
                emp.apellido_paterno,
                emp.apellido_materno,
                e.rfc AS rfc_ordenante
            FROM tblnominas_pagoenc a
            INNER JOIN tblnominas_pagodet b ON a.id = b.idpagonomina
            INNER JOIN tblnominas c ON c.idempleado = b.idempleado
            INNER JOIN tblempleados emp ON emp.id = b.idempleado
            LEFT JOIN tblempresas e ON e.id = c.idempresa
            WHERE a.id = ?
              AND c.idbancos = 5
              AND b.total_nomina_fiscal > 0
            ORDER BY c.idbanca ASC
        ", [$id]);

        return collect($lista);
    }

    public function obtenerCuentaOrigenBanorte()
    {
        $cuenta = DB::table('tblcuentas')
            ->where('status', 'A')
            ->where('tipo', 'BANCO')
            ->whereRaw('UPPER(nombre) LIKE ?', ['%BANORTE%'])
            ->orderBy('id')
            ->first();

        if (!$cuenta || empty($cuenta->id_cuenta)) {
            return '';
        }

        $digitos = preg_replace('/\D+/', '', (string) $cuenta->id_cuenta);
        if (strlen($digitos) > 10) {
            return substr($digitos, -10);
        }

        return $digitos;
    }

     public function EncabezadoTotales(int $id)
    {
        $var = DB::select('select count(b.id) as registros,                                                          
            sum(b.total_nomina_fiscal) as total
            from tblnominas_pagoenc a 
            inner join tblnominas_pagodet b on a.id = b.idpagonomina
            inner join tblnominas c on c.idempleado = b.idempleado
            where a.id = ? and c.idbancos = 5
            order by c.idbanca ASC;',[$id]);
       $var = collect($var);

         return $var;
    }


    public function ListadoDispersionExcel(int $id)
    {
        $var = DB::select('select 
        nom.idbanca,
        case 
        when emp.segundo_nombre = " " then CONCAT (emp.primer_nombre, " ",emp.apellido_paterno," ",emp.apellido_materno ) 
        else
        CONCAT (emp.primer_nombre," ",emp.segundo_nombre," ",emp.apellido_paterno," ",emp.apellido_materno ) 
        end as Nombre_Empleado,
        nomdet.total_nomina_fiscal as IMPORTE,
        "072" as "No. DE BANCO",
        "01" as "TIPO DE CUENTA",
        nom.numero_cuenta  as "No DE CUENTA"
        from tblnominas_pagodet nomdet 
        inner join tblempleados emp on nomdet.idempleado = emp.id
        inner join tblnominas nom on emp.id = nom.idempleado
        where idpagonomina = ? and nom.idbancos = 5 and nomdet.total_nomina_fiscal > 0 ORDER BY idbanca ASC;',[$id]);
         return collect($var);
    }


    public function validaTimbrado(int $id)
    {
        $var = DB::select('select tblnominas_pagodet.idpagonomina, count(recibos_nomina.id_tblnominas_pagodet) as timbradas FROM tblnominas_pagoenc
        inner join tblnominas_pagodet on tblnominas_pagoenc.id = tblnominas_pagodet.idpagonomina
        inner join recibos_nomina on tblnominas_pagodet.id = recibos_nomina.id_tblnominas_pagodet where tblnominas_pagoenc.id = ?;',[$id]);
        $var = collect($var);

         foreach($var as $item){
            $timbro = $item->timbradas;
         }

         return $timbro;
    }

    public function obtnernominatimbrada($id)
    {
       $var = DB::select('select
                tblnominas_pagoenc.id as pagoenc_id,
                tblnominas_pagoenc.idtiponomina,
                tblnominas_pagoenc.estado_nomina,
                tblnominas_pagoenc.fecha_fin,
                tblnominas_pagoenc.fecha_inicio,
                tblnominas_pagoenc.nombre_nomina,
                tblnominas_pagoenc.created_at as created,
                tblempleados.id as idempleado,
                tblnominas_pagodet.id,
                tblnominas_pagodet.idpagonomina,
                tblempleados.primer_nombre,
                tblempleados.segundo_nombre,
                tblempleados.apellido_paterno,
                tblempleados.apellido_materno,
                tblpuestos.nombre as puesto,
                tblsucursales.id as idsucursal,
                tblsucursales.nombre as sucursal,
                tblnominas_pagodet.horas_extras,
                tblnominas_pagodet.horas_extras_pago_f,
                tblnominas_pagodet.horas_extras_pago_e,
                tblnominas_pagodet.total_horas_extras,
                tblnominas_pagodet.dias_incapacidad,
                tblnominas_pagodet.faltas_reta_aus,
                tblnominas_pagodet.total_faltas_reta_aus,
                tblnominas_pagodet.dias_laborados,
                tblnominas.excedente,
                tblnominas.efectivo,
                tblnominas.salario_fijo,
                tblnominas_pagodet.salario_diario_integrado,
                tblnominas_pagodet.total_sueldo,
                tblnominas_pagodet.sueldo_fiscal,
                tblnominas_pagodet.sueldo_excedente,
                tblnominas_pagodet.sueldo_efectivo,
                tblnominas_pagodet.deudores_fiscal,
                tblnominas_pagodet.deudores_no_fiscal,
                tblnominas_pagodet.total_deudores,
                tblnominas_pagodet.pago_infonavit,
                tblnominas_pagodet.pago_imss,
                tblnominas_pagodet.pago_isr,
                tblnominas_pagodet.pago_subsidio,
                tblnominas_pagodet.ahorro,
                tblnominas_pagodet.pago_prima_vacacional,
                tblnominas_pagodet.pago_prima_vacacional_exce,
                tblnominas_pagodet.pago_prima_vacacional_efec,
                tblnominas_pagodet.dias_pendiente,
                tblnominas_pagodet.bono,
                tblnominas_pagodet.viaticos,
                tblnominas_pagodet.transporte,
                tblnominas_pagodet.percepcion_extraordinaria,
                tblnominas_pagodet.pago_prima_vacacional,
                tblnominas_pagodet.despensa,
                tblnominas_pagodet.fonacot,
                tblnominas_pagodet.otros,
                tblnominas_pagodet.dias_descanso,
                tblnominas_pagodet.pago_dias_descanso,
                tblnominas_pagodet.dias_prima_dominical,
                tblnominas_pagodet.pago_prima_dominical,
                tblnominas_pagodet.total_nomina_fiscal,
                tblnominas_pagodet.total_apagar_excedente,
                tblnominas_pagodet.total_efectivo,
                tblnominas_pagodet.pago_nomina_fiscal_global,
                tblnominas_pagodet.pago_nomina_excedente_global,
                tblnominas_pagodet.pago_efectivo_cajas,
                tblnominas_pagodet.total_apagar,
                tblbancos.nombre as banco,
                tblnominas.idbanca,
                tblnominas.numero_tarjeta,
                tblnominas.numero_cuenta
            
           from tblnominas_pagodet 
           inner join tblempleados on tblempleados.id = tblnominas_pagodet.idempleado
       
            inner join tblpuestos on tblempleados.idpuesto = tblpuestos.id
            inner join tblsucursales on  tblempleados.idsucursal = tblsucursales.id
            inner join tblnominas on tblempleados.id = tblnominas.idempleado
            inner join tblbancos on tblempleados.idbanco = tblbancos.id
            inner join tblnominas_pagoenc on tblnominas_pagodet.idpagonomina = tblnominas_pagoenc.id
            inner join recibos_nomina on tblnominas_pagodet.id = recibos_nomina.id_tblnominas_pagodet
            where tblnominas_pagodet.idpagonomina = ? 
            order by tblempleados.id asc;',[$id]);
        return collect($var);
        // and tblnominas_pagodet.id > 381
    }


     public function validaTimbradofallido(int $id)
    {
        $var = DB::select('select tblnominas_pagodet.idpagonomina, count(tblnomina_notimbrados.id_nominapago_det) as no_timbrado FROM tblnominas_pagoenc
        inner join tblnominas_pagodet on tblnominas_pagoenc.id = tblnominas_pagodet.idpagonomina
        inner join tblnomina_notimbrados on tblnominas_pagodet.id = tblnomina_notimbrados.id_nominapago_det where tblnominas_pagoenc.id = ?;',[$id]);
        $var = collect($var);

         foreach($var as $item){
            $no_timbrado = $item->no_timbrado;
         }

         return $no_timbrado;
    }


     public function obtnertimbradosFallidos(int $id)
    {
        $var = DB::select('select 
                tblempleados.primer_nombre,
                tblempleados.segundo_nombre,
                tblempleados.apellido_paterno,
                tblempleados.apellido_materno,
                tblnomina_notimbrados.* 
			FROM tblnominas_pagoenc
			inner join tblnominas_pagodet on tblnominas_pagoenc.id = tblnominas_pagodet.idpagonomina
			inner join tblnomina_notimbrados on tblnominas_pagodet.id = tblnomina_notimbrados.id_nominapago_det 
            inner join tblempleados on tblempleados.id = tblnominas_pagodet.idempleado
            where tblnominas_pagoenc.id = ?;',[$id]);
        return collect($var);
    }

    public function validaNominasenCero(int $id)
    {
        $var = DB::select(' select tblnominas_pagodet.idpagonomina, count(tblnominas_pagodet.id) as ceros 
            FROM tblnominas_pagodet where tblnominas_pagodet.idpagonomina = ? and tblnominas_pagodet.total_nomina_fiscal <= 0;',[$id]);
        $var = collect($var);

         foreach($var as $item){
            $ceros = $item->ceros;
         }

         return $ceros;
    }
    
    
    //AGUINALDOS
    public function datos_empleados_activos(){
        $var = DB::select("
        select nom.idempleado , nom.id as id_nomina ,nom.*,emp.* from tblempleados emp
        inner join tblnominas nom on emp.id = nom.idempleado 
        WHERE  emp.estado = 'A';");
        return collect($var);
    }

    public function obtener_aguinaldos_enc(){
        $var = DB::select('select * from tblaguinaldos_enc;');
        return collect($var);
    }

    public function obtener_aguinaldos_det(int $id){
        $var = DB::select("
        select
        tblempleados.id as idempleado,
        CONCAT_WS(' ',tblempleados.primer_nombre,tblempleados.segundo_nombre,tblempleados.apellido_paterno,tblempleados.apellido_materno) AS nombre_empleado,
        tblempleados.fecha_ingreso,
        tblaguinaldos_enc.id as id_aguinaldo_enc,
        tblaguinaldos_enc.nombre,
        tblaguinaldos_enc.fecha_pago,
 		tblaguinaldos_enc.fecha_cierre,
 		
        tblnominas.*,
        tblnominas.id as idnomina,
        
        tblaguinaldos_det.id as id_aguinaldo_det,
        tblaguinaldos_det.*,

        tblpuestos.nombre as puesto,
        tblsucursales.id as idsucursal,
        tblsucursales.nombre as sucursal,
        tblbancos.nombre as banco

        from tblaguinaldos_enc 
        inner join tblaguinaldos_det on tblaguinaldos_enc.id = tblaguinaldos_det.id_aguinaldo 
        inner join tblempleados on  tblempleados.id = tblaguinaldos_det.id_empleado
        inner join tblnominas on tblnominas.id = tblaguinaldos_det.id_nomina 

        inner join tblpuestos on tblempleados.idpuesto = tblpuestos.id
        inner join tblsucursales on  tblempleados.idsucursal = tblsucursales.id
        inner join tblbancos on tblempleados.idbanco = tblbancos.id

        WHERE  tblempleados.estado = 'A' and tblaguinaldos_enc.id = ?;",[$id]);
        return collect($var);
    }

    function calcularISR($ingreso)
    {
        $tablaISR = [
            ["lim_inf" => 0.01,    "lim_sup" => 746.04,   "cuota_fija" => 0,        "porcentaje" => 1.92],
            ["lim_inf" => 746.05,  "lim_sup" => 6332.05,  "cuota_fija" => 14.32,    "porcentaje" => 6.40],
            ["lim_inf" => 6332.06, "lim_sup" => 11128.01, "cuota_fija" => 371.83,   "porcentaje" => 10.88],
            ["lim_inf" => 11128.02,"lim_sup" => 12935.82, "cuota_fija" => 893.63,   "porcentaje" => 16.00],
            ["lim_inf" => 12935.83,"lim_sup" => 15487.71, "cuota_fija" => 1182.88,  "porcentaje" => 17.92],
            ["lim_inf" => 15487.72,"lim_sup" => 31236.49, "cuota_fija" => 1640.18,  "porcentaje" => 21.36],
            ["lim_inf" => 31236.50,"lim_sup" => 49233.00, "cuota_fija" => 5004.12,  "porcentaje" => 23.52],
            ["lim_inf" => 49233.01,"lim_sup" => 93993.90, "cuota_fija" => 9236.89,  "porcentaje" => 30.00],
            ["lim_inf" => 93993.91,"lim_sup" => 125325.20,"cuota_fija" => 22665.17, "porcentaje" => 32.00],
            ["lim_inf" => 125325.21,"lim_sup" => 375975.61,"cuota_fija" => 32691.18,"porcentaje" => 34.00],
            ["lim_inf" => 375975.62,"lim_sup" => 99999999,"cuota_fija" => 117912.32,"porcentaje" => 35.00]
        ];

        foreach ($tablaISR as $fila) {
            if ($ingreso >= $fila["lim_inf"] && $ingreso <= $fila["lim_sup"]) {
                $excedente = $ingreso - $fila["lim_inf"];
                $impuesto_marginal = $excedente * ($fila["porcentaje"] / 100);
                return $fila["cuota_fija"] + $impuesto_marginal;
            }
        }
        return 0;
    }

    public function calcular_aguinaldo(string $paso,int $id,$idempleado ,$idnomina,$fecha_ingreso,$salario_diario_f,$salario_excedente,$sueldo_mensual,$dias_aguinaldo_pagados){
        $save = 0;
        $date = Carbon::now();
        $date = $date->format('Y-m-d');
        $fecha = "31-12-".Carbon::now()->year;
        $fecha1 =  Carbon::parse($fecha);
        $aguinaldos_enc = aguinaldos_enc::find($id);

        $dias_aguinaldo = conceptos_nomina::where('nombre', 'dias_aguinaldo')->first();
        $dias_aguinaldo = $dias_aguinaldo->valor;
        $UMA = conceptos_nomina::where('nombre', 'uma_diaria')->first();
        $UMA = $UMA->valor;
        $zonaEmpleado = DB::table('tblnominas')->where('id', $idnomina)->value('zona');
        $salario_diario_minimo = $this->resolverSalarioDiarioMinimoPorZona($zonaEmpleado);
        $aguinaldo_bruto = 0;
        $sueldo_diario = 0;
        
            $fecha2 = Carbon::parse($fecha_ingreso);
            $dias_trabajados = $fecha1->diffInDays($fecha2);
            if($dias_trabajados > 365){ $dias_trabajados = 365;}
            if($dias_aguinaldo_pagados == 0){
                $dias_aguinaldo_pagados = ceil(round($dias_aguinaldo * ($dias_trabajados / 365),2));
            }

            //AGUINALDO BRUTO CALCULADO
            $sueldo_diario = $sueldo_mensual / 30;
            $aguinaldo_bruto =  ($sueldo_diario * $dias_aguinaldo_pagados);

            //FISCAL
                //Aguinaldo bruto (proporcional si aplica)
                    $aguinaldo_bruto_f = ($salario_diario_f * $dias_aguinaldo_pagados);

                //Parte exenta y gravada
                    if($salario_diario_f > $salario_diario_minimo || $dias_aguinaldo_pagados > $dias_aguinaldo){
                        $exento = min($aguinaldo_bruto_f, ($UMA * 30));
                        $gravado = max($aguinaldo_bruto_f - $exento, 0);

                        //ISR aguinaldo
                
                        // 3. Obtener sueldo mensual del empleado (sueldo diario × 30.4 días promedio)
                        $ordinario_mensual = $salario_diario_f * 30;

                        if($gravado > 0){
                            // 4. ISR normal sin aguinaldo
                            $fracc1 = ($gravado/365)*30.4;
                            $fracc2 = $fracc1 + $ordinario_mensual;
                            $isr174 = $this->calcularISR($fracc2);
                            $isr96 = $this->calcularISR($ordinario_mensual);
                            $fracc3 = $isr174 - $isr96 ;
                            $fracc5 = $fracc3 / $fracc1 ;

                            // 6. ISR del aguinaldo
                            $ISR_aguinaldo = $gravado * $fracc5;
                        }else{
                            $ISR_aguinaldo = 0;
                        }
                    
                    }else{
                        $exento = ($UMA * 30);
                        $gravado = 0;
                        $ISR_aguinaldo = 0;   
                    }

                //Aguinaldo fiscal a pagar
                    $total_pagar_f = $aguinaldo_bruto_f - $ISR_aguinaldo;

            //EXCEDENTE
                if($salario_excedente > 0){
                    $aguinaldo_bruto_e = $aguinaldo_bruto - $total_pagar_f;
                    $total_pagar_e = $aguinaldo_bruto_e;
                }else{
                    $aguinaldo_bruto_e = 0;
                    $total_pagar_e = 0; 
                }


            //TOTAL A PAGAR
                $aguinaldo_neto = $total_pagar_f + $total_pagar_e;

        
            // INSERTAR O ACTUALIZAR REGISTRO EN aguinaldos_det
            if($paso == "RECALCULAR"){
                $aguinaldo_det = aguinaldos_det::where('id_aguinaldo', $id)
                    ->where('id_empleado', $idempleado)
                    ->first();
            }else{
                $aguinaldo_det = new aguinaldos_det();
                $aguinaldo_det->id_aguinaldo = $id;
                $aguinaldo_det->id_empleado = $idempleado;
                $aguinaldo_det->id_nomina = $idnomina;
            }

            $aguinaldo_det->sueldo_diario = $sueldo_diario;
            $aguinaldo_det->sueldo_mensual = $sueldo_mensual;
            $aguinaldo_det->dias_trabajados = $dias_trabajados;
            $aguinaldo_det->dias_aguinaldo_correspondientes = $dias_aguinaldo;
            $aguinaldo_det->dias_aguinaldo_pagados = $dias_aguinaldo_pagados;
            $aguinaldo_det->aguinaldo_f = $aguinaldo_bruto_f;
            $aguinaldo_det->aguinaldo_e = $aguinaldo_bruto_e;
            $aguinaldo_det->aguinaldo_gravado = $gravado;
            $aguinaldo_det->aguinaldo_exento = $exento;
            $aguinaldo_det->aguinaldo_total = $aguinaldo_bruto_f+$aguinaldo_bruto_e;
            $aguinaldo_det->isr_calculado = $ISR_aguinaldo;
            $aguinaldo_det->total_pagar_f = $total_pagar_f;
            $aguinaldo_det->total_pagar_e = $total_pagar_e;
            $aguinaldo_det->total_pagar = $aguinaldo_neto;
            $aguinaldo_det->created_by = auth()->user()->name;
            
            if($aguinaldo_det->save()){
                return "exito";
            }else{
                return "error";
            }   
    }

    public function validaAguinaldoCero(int $id)
    {
        $var = DB::select(' select tblaguinaldos_det.id, ifnull(count(tblaguinaldos_det.id),0) as ceros 
            FROM tblaguinaldos_det where tblaguinaldos_det.id_aguinaldo = ? and tblaguinaldos_det.total_pagar_f <= 0;',[$id]);
        $var = collect($var)->first();
        $ceros = $var->ceros ?? 0;
     
         return $ceros;
    }

    public function ListadoinfolayoutAguinaldo(int $id)
    {
        $listaempleadoslayout = DB::select("select 'D' AS tipo_registro,
        date(now()) as fechaaplicacion,
        b.id_empleado as numero_emppleado,
        c.idbanca,
        '                                                                                ' as referencia_servicioyOrdenante,
        b.total_pagar_f as importe,
        '072' as numero_banco_receptor,
        '01' as tipo_cuenta,
        c.numero_cuenta,
        '0' as tipo_movimiento,
        ' ' as accion ,
        '00000000' as importe_ivadelaoperacion,
        '                  ' as filtro
        from tblaguinaldos_enc a 
        inner join tblaguinaldos_det b on a.id = b.id_aguinaldo
        inner join tblnominas c on c.idempleado = b.id_empleado
        where a.id = ? and c.idbancos = 5
        order by c.idbanca ASC;",[$id]);
         return collect($listaempleadoslayout);
    }

     public function EncabezadoTotalesAguinaldo(int $id)
    {
        $var = DB::select('select count(b.id) as registros,                                                          
            sum(b.total_pagar) as total
            from tblaguinaldos_enc a 
            inner join tblaguinaldos_det b on a.id = b.id_aguinaldo
            inner join tblnominas c on c.idempleado = b.id_empleado
            where a.id = ? and c.idbancos = 5
            order by c.idbanca ASC;',[$id]);
       $var = collect($var);

         return $var;
    }

    public function reciboaguinaldo($id)
    {
        $varempl = DB::select('
        select 
		tblempleados.id as id_empleado,
  
        CONCAT_WS (" ",tblempleados.primer_nombre,tblempleados.segundo_nombre,tblempleados.apellido_paterno,tblempleados.apellido_materno ) as nombre_empleado,
		
		tblaguinaldos_enc.id as aguinaldo_enc_id,
        tblaguinaldos_det.id as aguinaldo_det_id,
        tblsucursales.nombre as sucursal,
        tblpuestos.nombre as puesto,
     
        tblempleados.nss,
        tblempleados.rfc,
        tblempleados.curp,
        tblempleados.fecha_ingreso,
        tblnominas.fecha_ingreso_imss,
        tblempleados.codigo_postal as domicilio_fiscal,

        tblnominas.salario_fijo as salario_diario,
        tblnominas.excedente as salario_excedente,
       
  
        tblbancos.nombre as banco,
        tblnominas.numero_cuenta,
        tblaguinaldos_enc.fecha_pago,
        tblaguinaldos_enc.fecha_cierre,

        tblaguinaldos_det.*

        from tblaguinaldos_enc 
        inner join tblaguinaldos_det on tblaguinaldos_enc.id = tblaguinaldos_det.id_aguinaldo
        inner join tblempleados on  tblempleados.id = tblaguinaldos_det.id_empleado
        inner join tblnominas on tblnominas.id = tblaguinaldos_det.id_nomina 
        inner join tblsucursales on  tblsucursales.id = tblempleados.idsucursal
        inner join tblpuestos on  tblpuestos.id = tblempleados.idpuesto
		inner join tblbancos on  tblbancos.id = tblnominas.idbancos 
        where tblaguinaldos_det.id_aguinaldo = ? ORDER BY tblempleados.id ASC;', [$id]);
        return collect($varempl);
    }

    public function obteneraguinaldotimbrado($id)
    {
       $var = DB::select('select
                tblaguinaldos_enc.id as aguinaldo_enc_id,
                tblaguinaldos_enc.nombre as nombre_aguinaldo,
                tblaguinaldos_enc.fecha_pago,
                tblaguinaldos_enc.fecha_cierre,
                tblaguinaldos_enc.estado,
                tblempleados.id as idempleado,
                tblaguinaldos_det.id,
                tblaguinaldos_det.id_aguinaldo,
                tblempleados.primer_nombre,
                tblempleados.segundo_nombre,
                tblempleados.apellido_paterno,
                tblempleados.apellido_materno,
                tblpuestos.nombre as puesto,
                tblsucursales.id as idsucursal,
                tblsucursales.nombre as sucursal,
                tblaguinaldos_det.dias_aguinaldo_correspondientes,
                tblaguinaldos_det.dias_aguinaldo_pagados,
                tblaguinaldos_det.sueldo_diario,
                tblnominas.excedente,
                tblaguinaldos_det.aguinaldo_f,
                tblaguinaldos_det.aguinaldo_e,
                tblaguinaldos_det.aguinaldo_gravado,
                tblaguinaldos_det.aguinaldo_exento,
                tblaguinaldos_det.aguinaldo_total,
                tblaguinaldos_det.isr_calculado,
                tblaguinaldos_det.total_pagar_f,
                tblaguinaldos_det.total_pagar_e,
                tblaguinaldos_det.total_pagar,
                tblbancos.nombre as banco,
                tblnominas.idbancos as idbanca,
                tblnominas.numero_tarjeta,
                tblnominas.numero_cuenta,
                recibos_aguinaldo.uuid_factura,
                recibos_aguinaldo.id as id_recibo
            
           from tblaguinaldos_det 
           inner join tblempleados on tblempleados.id = tblaguinaldos_det.id_empleado
           inner join tblpuestos on tblempleados.idpuesto = tblpuestos.id
           inner join tblsucursales on  tblempleados.idsucursal = tblsucursales.id
           inner join tblnominas on tblaguinaldos_det.id_nomina = tblnominas.id
           inner join tblbancos on tblnominas.idbancos = tblbancos.id
           inner join tblaguinaldos_enc on tblaguinaldos_det.id_aguinaldo = tblaguinaldos_enc.id
           inner join recibos_aguinaldo on tblaguinaldos_det.id = recibos_aguinaldo.id_tblnominas_pagodet
           where tblaguinaldos_det.id_aguinaldo = ? 
           order by tblempleados.id asc;',[$id]);
        return collect($var);
    }

    public function listadoAguinaldosNotimbrados(int $id)
    {
        $lista = DB::select("select * from tblaguinaldo_notimbrado where idaguinaldo_enc = ?;",[$id]);
        return collect($lista);
    }

    public function validaAguinaldoTimbradofallido(int $id)
    {
        $var = DB::select('select tblaguinaldos_det.id_aguinaldo, count(tblaguinaldo_notimbrado.id_aguinaldo_det) as no_timbrado 
        FROM tblaguinaldos_enc
        inner join tblaguinaldos_det on tblaguinaldos_enc.id = tblaguinaldos_det.id_aguinaldo
        inner join tblaguinaldo_notimbrado on tblaguinaldos_det.id = tblaguinaldo_notimbrado.id_aguinaldo_det 
        where tblaguinaldos_enc.id = ?;',[$id]);
        $var = collect($var);

         foreach($var as $item){
            $no_timbrado = $item->no_timbrado;
         }

         return $no_timbrado ?? 0;
    }

    public function obtenerAguinaldosTimbradosFallidos(int $id)
    {
        $var = DB::select('select 
                tblempleados.primer_nombre,
                tblempleados.segundo_nombre,
                tblempleados.apellido_paterno,
                tblempleados.apellido_materno,
                tblaguinaldo_notimbrado.* 
			FROM tblaguinaldos_enc
			inner join tblaguinaldos_det on tblaguinaldos_enc.id = tblaguinaldos_det.id_aguinaldo
			inner join tblaguinaldo_notimbrado on tblaguinaldos_det.id = tblaguinaldo_notimbrado.id_aguinaldo_det 
            inner join tblempleados on tblempleados.id = tblaguinaldos_det.id_empleado
            where tblaguinaldos_enc.id = ?;',[$id]);
        return collect($var);
    }

    public function obtener_aportaciones_patronales_enc()
    {
        return aportaciones_patronales_enc::orderByDesc('fecha_inicio')->get();
    }

    public function obtener_aportaciones_patronales_det(int $idEnc)
    {
        return DB::select("
            SELECT
                det.*,
                CONCAT_WS(' ', emp.primer_nombre, emp.segundo_nombre, emp.apellido_paterno, emp.apellido_materno) AS nombre_empleado,
                emp.id AS numero_empleado
            FROM tblnomina_aportaciones_patronales_det det
            INNER JOIN tblempleados emp ON emp.id = det.id_empleado
            WHERE det.id_patronales_enc = ?
            ORDER BY nombre_empleado
        ", [$idEnc]);
    }

    protected function tasaConceptoPatronal(string $nombre): float
    {
        $concepto = conceptos_nomina::where('nombre', $nombre)->first();

        return $concepto ? ((float) $concepto->valor / 100) : 0;
    }

    protected function tasaExcedenteTresUma(): float
    {
        $concepto = conceptos_nomina::where('nombre', 'like', 'excedente%3_uma')->first();

        return $concepto ? ((float) $concepto->valor / 100) : 0;
    }

    protected function cuotaPatronalCesantiaVejez(float $sbc): float
    {
        $tarifa = cesantia_vejez::query()
            ->where('sbc_pesos_minimo', '<=', $sbc)
            ->where('sbc_pesos_maximo', '>=', $sbc)
            ->orderBy('sbc_pesos_minimo')
            ->first();

        if (!$tarifa) {
            $uma = (float) (conceptos_nomina::where('nombre', 'uma_diaria')->value('valor') ?: 1);
            $sbcUma = $uma > 0 ? ($sbc / $uma) : 0;

            $tarifa = cesantia_vejez::query()
                ->where('sbc_minimo', '<=', $sbcUma)
                ->where('sbc_maximo', '>=', $sbcUma)
                ->orderBy('sbc_minimo')
                ->first();
        }

        return $tarifa ? ((float) $tarifa->cuota_patronal / 100) : 0;
    }

    public function calcular_aportaciones_patronales(int $idEnc, string $fechaInicio, string $fechaFin): int
    {
        aportaciones_patronales_det::where('id_patronales_enc', $idEnc)->delete();

        $uma = (float) (conceptos_nomina::where('nombre', 'uma_diaria')->value('valor') ?: 0);
        $tresUma = $uma * 3;

        $tasas = [
            'prima_riesgo' => $this->tasaConceptoPatronal('prima_riesgo'),
            'cuota_fija' => $this->tasaConceptoPatronal('cuota_fija'),
            'excedente_3_uma' => $this->tasaExcedenteTresUma(),
            'prestaciones_dinero' => $this->tasaConceptoPatronal('prestaciones_dinero'),
            'gastos_medicos_pensionados' => $this->tasaConceptoPatronal('gastos_medicos_pensionados'),
            'invalidez_vida' => $this->tasaConceptoPatronal('invalidez_vida'),
            'guarderias_prestaciones_sociales' => $this->tasaConceptoPatronal('guarderias_prestaciones_sociales'),
            'retiro' => $this->tasaConceptoPatronal('retiro'),
            'aportacion_infonavit' => $this->tasaConceptoPatronal('aportacion_infonavit'),
        ];

        $lineas = DB::table('tblnominas_pagodet as det')
            ->join('tblnominas_pagoenc as enc', 'det.idpagonomina', '=', 'enc.id')
            ->where('enc.estado_nomina', 'Cerrada')
            ->where('enc.fecha_inicio', '<=', $fechaFin)
            ->where('enc.fecha_fin', '>=', $fechaInicio)
            ->select(
                'det.idempleado',
                'det.salario_diario_integrado',
                'det.dias_laborados'
            )
            ->get()
            ->groupBy('idempleado');

        $usuario = auth()->user()->name;
        $insertados = 0;

        $totalSbc = 0;
        $totalRiesgoTrabajo = 0;
        $totalCuotaFija = 0;
        $totalEnfMatExcedente = 0;
        $totalEnfMatDinero = 0;
        $totalEnfMatGastos = 0;
        $totalInvalidezVida = 0;
        $totalGuarderia = 0;
        $totalRetiro = 0;
        $totalCesantiaVejez = 0;
        $totalInfonavit = 0;
        $totalFinal = 0;

        foreach ($lineas as $idempleado => $registros) {
            $basePonderada = 0;
            $diasTotal = 0;

            $riesgoTrabajo = 0;
            $cuotaFija = 0;
            $enfMatExcedente = 0;
            $enfMatDinero = 0;
            $enfMatGastos = 0;
            $invalidezVida = 0;
            $guarderia = 0;
            $retiro = 0;
            $cesantiaVejez = 0;
            $infonavit = 0;

            foreach ($registros as $registro) {
                $sbc = (float) $registro->salario_diario_integrado;
                $dias = (float) $registro->dias_laborados;

                if ($dias <= 0) {
                    continue;
                }

                $base = $sbc * $dias;
                $diasTotal += $dias;
                $basePonderada += $base;

                $riesgoTrabajo += $base * $tasas['prima_riesgo'];
                $cuotaFija += $uma * $dias * $tasas['cuota_fija'];

                $excedenteSbc = max(0, $sbc - $tresUma);
                $enfMatExcedente += $excedenteSbc * $dias * $tasas['excedente_3_uma'];

                $enfMatDinero += $base * $tasas['prestaciones_dinero'];
                $enfMatGastos += $base * $tasas['gastos_medicos_pensionados'];
                $invalidezVida += $base * $tasas['invalidez_vida'];
                $guarderia += $base * $tasas['guarderias_prestaciones_sociales'];
                $retiro += $base * $tasas['retiro'];
                $cesantiaVejez += $base * $this->cuotaPatronalCesantiaVejez($sbc);
                $infonavit += $base * $tasas['aportacion_infonavit'];
            }

            if ($diasTotal <= 0) {
                continue;
            }

            $sbcPromedio = $basePonderada / $diasTotal;
            $total = $riesgoTrabajo + $cuotaFija + $enfMatExcedente + $enfMatDinero + $enfMatGastos
                + $invalidezVida + $guarderia + $retiro + $cesantiaVejez + $infonavit;

            $sbcRedondeado = round($sbcPromedio, 2);
            $diasRedondeados = round($diasTotal, 2);
            $riesgoTrabajoRedondeado = round($riesgoTrabajo, 2);
            $cuotaFijaRedondeada = round($cuotaFija, 2);
            $enfMatExcedenteRedondeado = round($enfMatExcedente, 2);
            $enfMatDineroRedondeado = round($enfMatDinero, 2);
            $enfMatGastosRedondeado = round($enfMatGastos, 2);
            $invalidezVidaRedondeado = round($invalidezVida, 2);
            $guarderiaRedondeado = round($guarderia, 2);
            $retiroRedondeado = round($retiro, 2);
            $cesantiaVejezRedondeado = round($cesantiaVejez, 2);
            $infonavitRedondeado = round($infonavit, 2);
            $totalRedondeado = round($total, 2);

            aportaciones_patronales_det::create([
                'id_patronales_enc' => $idEnc,
                'id_empleado' => (int) $idempleado,
                'sbc' => $sbcRedondeado,
                'dias_cotizados' => $diasRedondeados,
                'riesgo_trabajo' => $riesgoTrabajoRedondeado,
                'cuota_fija' => $cuotaFijaRedondeada,
                'enf_mat_excedente' => $enfMatExcedenteRedondeado,
                'enf_mat_dinero' => $enfMatDineroRedondeado,
                'enf_mat_gastos_pensionados' => $enfMatGastosRedondeado,
                'invalidez_vida' => $invalidezVidaRedondeado,
                'guarderia' => $guarderiaRedondeado,
                'retiro' => $retiroRedondeado,
                'cesantia_vejez' => $cesantiaVejezRedondeado,
                'infonavit' => $infonavitRedondeado,
                'total' => $totalRedondeado,
                'created_by' => $usuario,
                'updated_by' => $usuario,
            ]);

            $totalSbc += $sbcRedondeado * $diasRedondeados;
            $totalRiesgoTrabajo += $riesgoTrabajoRedondeado;
            $totalCuotaFija += $cuotaFijaRedondeada;
            $totalEnfMatExcedente += $enfMatExcedenteRedondeado;
            $totalEnfMatDinero += $enfMatDineroRedondeado;
            $totalEnfMatGastos += $enfMatGastosRedondeado;
            $totalInvalidezVida += $invalidezVidaRedondeado;
            $totalGuarderia += $guarderiaRedondeado;
            $totalRetiro += $retiroRedondeado;
            $totalCesantiaVejez += $cesantiaVejezRedondeado;
            $totalInfonavit += $infonavitRedondeado;
            $totalFinal += $totalRedondeado;

            $insertados++;
        }

        $enc = aportaciones_patronales_enc::find($idEnc);
        if ($enc) {
            $enc->fill([
                'total_sbc' => round($totalSbc, 2),
                'total_riesgo_trabajo' => round($totalRiesgoTrabajo, 2),
                'total_cuota_fija' => round($totalCuotaFija, 2),
                'total_enf_mat_excedente' => round($totalEnfMatExcedente, 2),
                'total_enf_mat_dinero' => round($totalEnfMatDinero, 2),
                'total_enf_mat_gastos_pensionados' => round($totalEnfMatGastos, 2),
                'total_invalidez_vida' => round($totalInvalidezVida, 2),
                'total_guarderia' => round($totalGuarderia, 2),
                'total_retiro' => round($totalRetiro, 2),
                'total_cesantia_vejez' => round($totalCesantiaVejez, 2),
                'total_infonavit' => round($totalInfonavit, 2),
                'total_final' => round($totalFinal, 2),
                'updated_by' => $usuario,
            ]);
            $enc->save();
        }

        return $insertados;
    }

}