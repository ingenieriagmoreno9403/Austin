<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

use App\Models\Vistas;
use App\Models\Acciones;
use App\Models\usuario_pantallas;
use App\Models\usuario_acciones;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Traits\MenuTrait;
use Carbon\Carbon;
use App\Traits\DatosimpleTraits;
use App\Traits\SistemasTraits;
use App\Models\perfil_acciones;
use App\Models\perfiles;
use App\Models\Nominas_pagosenc;
use App\Models\Nominas_pagosdet;
use DateTime;
use SimpleXMLElement;
use Illuminate\Support\Facades\Storage;
use SoapClient;
use App\Models\ReciboNomina; 
use App\Models\NominaNotimbrados;
use App\Models\DatosFiscalesEmpresa;
use App\Models\facturacionproductos;
use Illuminate\Support\Facades\Response;
use function PHPUnit\Framework\isNull;
use App\Traits\NominaTraits;
use App\Traits\FacturamaTraits;
use Exception;
use App\Models\conceptos_nomina;
use App\Models\AguinaldoNotimbrado;
use App\Models\ReciboAguinaldo;
use App\Models\ComplementoPago;
use App\Models\VentaPedido;
use App\Models\Clientes;
use App\Models\Camion;
use App\Models\Chofer;
use App\Models\RutaCarga;
use Luecano\NumeroALetras\NumeroALetras;


class TimbrarNominaController extends Controller
{
    use MenuTrait;
    use DatosimpleTraits;
    use NominaTraits;
    use FacturamaTraits;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function TimbarEmpleado(int $id_pago_det, int $dnomina_enc)
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $varnominas =  $this->obtenernominasporid($dnomina_enc);
        $Nominaenc = DB::select("select fecha_inicio, fecha_fin, nombre_nomina from tblnominas_pagoenc where id = ?;",[$dnomina_enc]);
        $pruebaempleado = DB::select("select * from tblnominas_pagodet where id = ?;",[$id_pago_det]);

        
        $fechanominainicio = $Nominaenc[0]->fecha_inicio;
        $fechanminafin = $Nominaenc[0]->fecha_fin;
        $nombrenomina = $Nominaenc[0]->nombre_nomina;
        

                //echo $pruebaempleado[0]->id." ".$pruebaempleado[0]->idempleado;
        /**Pruebas------------------------------------------------------ */
        $idetalle = $pruebaempleado[0]->id;
        $emp = $pruebaempleado[0]->idempleado;

        $verificacion = $this->verificarCalculosNominas($varnominas);

        if ($verificacion) {
            return $verificacion; // Retorna la redirección con errores
        }
        
        // $respuesta = $this->nominasimple($idetalle,$emp,$dnomina_enc);
        $respuesta = $this->verdatos($idetalle,$emp,$dnomina_enc); // ver los detalles de la factura a timbrar
        dd($respuesta);

        if(is_array($respuesta))
            {
            /*
            echo "Factura Generada con Exito";
            dd($respuesta);
            */
            $item = $respuesta['Items'][0];
            $taxStamp = $respuesta['Complement']['TaxStamp'];
            
            $tblrecibosnomina = new ReciboNomina();
            $tblrecibosnomina->id_facturafacil = $respuesta['Id'];
            $tblrecibosnomina->subtotal = $respuesta['Subtotal'];
            $tblrecibosnomina->descuentos = $respuesta['Discount'];
            $tblrecibosnomina->total = $item ['Total'];
            $tblrecibosnomina->observaciones = $respuesta['Observations'];
            $tblrecibosnomina->nombre_receptor = $respuesta['Receiver']['Name'];
            $tblrecibosnomina->rfc_receptor = $respuesta['Receiver']['Rfc'];
            $tblrecibosnomina->descripcion_factura = $item['Description'];
            $tblrecibosnomina->uuid_factura = $taxStamp['Uuid'];
            $tblrecibosnomina->cfdisign = $taxStamp['CfdiSign'];
            $tblrecibosnomina->satcertnumber = $taxStamp['SatCertNumber'];
            $tblrecibosnomina->satsign = $taxStamp['SatSign'];
            $tblrecibosnomina->rfcprovcertif = $taxStamp['RfcProvCertif'];
            $tblrecibosnomina->satus_factura = $respuesta['Status'];
            $tblrecibosnomina->original_string = $respuesta['OriginalString'];
            $tblrecibosnomina->id_tblnominas_pagodet = $id_pago_det;
            //$tblrecibosnomina->folio_int = $respuesta['Folio']; //verifivar si se necesitar crear los folios
            $tblrecibosnomina->created_at = now();
            $tblrecibosnomina->created_by = auth()->id();
            $tblrecibosnomina->save();
            }
            else
            {
        
            echo "Error al Generar la Factura";
            dd($respuesta);
        
                        // guardaar datos de del emplado y nomina no timbrado
                        // Buscar el registro existente (ajusta el criterio de búsqueda según necesites)
                        $tblNominaNotimbrada = NominaNotimbrados::where('idnominapago_enc', $dnomina_enc)
                        ->where('id_nominapago_det', $id_pago_det)
                        ->first();

                        // Si existe el registro, lo actualizamos
                        if ($tblNominaNotimbrada) {
                        $tblNominaNotimbrada->mensaje_error = $respuesta;
                        $tblNominaNotimbrada->fecha_inicio = $fechanominainicio;
                        $tblNominaNotimbrada->fecha_fin = $fechanminafin;
                        $tblNominaNotimbrada->nombre_nomina = $nombrenomina;
                        $tblNominaNotimbrada->id_empleado = $emp;
                        $tblNominaNotimbrada->updated_at = now(); // Cambiado de created_at
                        $tblNominaNotimbrada->updated_by = auth()->id(); // Cambiado de created_by
                        $tblNominaNotimbrada->save();
                        } else {
                        // Opcional: puedes manejar el caso cuando no existe el registro
                        // throw new Exception("Registro no encontrado para actualizar");
                        // o crear uno nuevo si es necesario
                        return back()->with("Error","Error no existe el registro");
                        }

                        return back()->with("error","Error");
            }

            return back()->with("success","Movimiento Generado con Exito");

    }

    public function indextimrado(int $id)
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $varnominas =  $this->obtenernominasporid($id);
        $Nominaenc = DB::select("select fecha_inicio, fecha_fin, nombre_nomina from tblnominas_pagoenc where id = ?;",[$id]);
        $pruebaempleado = DB::select("select * from tblnominas_pagodet where id = 1109;"); //161 rebeca 154 IMER 152 GALA  159
        //dd($varnominas);
        $verificacion = $this->verificarCalculosNominas($varnominas);
        
        if ($verificacion) {
            return $verificacion; // Retorna la redirección con errores
        }

        $fechanominainicio = $Nominaenc[0]->fecha_inicio;
        $fechanminafin = $Nominaenc[0]->fecha_fin;
        $nombrenomina = $Nominaenc[0]->nombre_nomina;
        //dd($varnominas);
        
        

        //echo $pruebaempleado[0]->id." ".$pruebaempleado[0]->idempleado;
        /**Pruebas------------------------------------------------------ 
        $idetalle = $pruebaempleado[0]->id;
        $emp = $pruebaempleado[0]->idempleado;

        //$respuesta = $this->nominasimple($idetalle,$emp,$id);
        $respuesta = $this->verdatos($idetalle,$emp,$id);
        dd($respuesta);

        foreach($varnominas as $ltsnominas)
        {
        $respuesta = $this->verdatos($ltsnominas->id,$ltsnominas->idempleado,$id);
        $arrayrespuesta = json_encode($respuesta, JSON_PRETTY_PRINT);
        echo $arrayrespuesta;
        }
        */    
        /**Aquie empieza la facturacion masiva */
        foreach($varnominas as $ltsnominas)
        {

            $respuesta = $this->nominasimple($ltsnominas->id,$ltsnominas->idempleado,$id);
            
            // dd($respuesta);
            if(is_array($respuesta))
            {
            
            $item = $respuesta['Items'][0];
            $taxStamp = $respuesta['Complement']['TaxStamp'];
            
            $tblrecibosnomina = new ReciboNomina();
            $tblrecibosnomina->id_facturafacil = $respuesta['Id'];
            $tblrecibosnomina->subtotal = $respuesta['Subtotal'];
            $tblrecibosnomina->descuentos = $respuesta['Discount'];
            $tblrecibosnomina->total = $item ['Total'];
            $tblrecibosnomina->observaciones = $respuesta['Observations'];
            $tblrecibosnomina->nombre_receptor = $respuesta['Receiver']['Name'];
            $tblrecibosnomina->rfc_receptor = $respuesta['Receiver']['Rfc'];
            $tblrecibosnomina->descripcion_factura = $item['Description'];
            $tblrecibosnomina->uuid_factura = $taxStamp['Uuid'];
            $tblrecibosnomina->cfdisign = $taxStamp['CfdiSign'];
            $tblrecibosnomina->satcertnumber = $taxStamp['SatCertNumber'];
            $tblrecibosnomina->satsign = $taxStamp['SatSign'];
            $tblrecibosnomina->rfcprovcertif = $taxStamp['RfcProvCertif'];
            $tblrecibosnomina->satus_factura = $respuesta['Status'];
            $tblrecibosnomina->original_string = $respuesta['OriginalString'];
            $tblrecibosnomina->id_tblnominas_pagodet = $ltsnominas->id;
            //$tblrecibosnomina->folio_int = $respuesta['Folio']; //verifivar si se necesitar crear los folios
            $tblrecibosnomina->created_at = now();
            $tblrecibosnomina->created_by = auth()->id();
            $tblrecibosnomina->save();
            
            /*
            $empleado = DB::select("SELECT primer_nombre, segundo_nombre, apellido_paterno, apellido_materno FROM tblempleados WHERE id = ?;",[$ltsnominas->idempleado]);
            echo " EL EMPLEADO :  ".$empleado[0]->primer_nombre." ".$empleado[0]->segundo_nombre." ".$empleado[0]->apellido_paterno." ".$empleado[0]->apellido_materno." "." GENERO FACTURA CON EXITO"."<br>";
            */
            }
            else
            { 
                /*
                $empleado = DB::select("SELECT primer_nombre, segundo_nombre, apellido_paterno, apellido_materno FROM tblempleados WHERE id = ?;",[$ltsnominas->idempleado]);
                echo " El Empledo :  ".$empleado[0]->primer_nombre." ".$empleado[0]->segundo_nombre." ".$empleado[0]->apellido_paterno." ".$empleado[0]->apellido_materno." "." NO genero factura"."<br>";
                */

            // guardaar datos de del emplado y nomina no timbrado
            
            $tblNominaNotimbrada = new NominaNotimbrados();
            $tblNominaNotimbrada->mensaje_error = $respuesta;
            $tblNominaNotimbrada->idnominapago_enc = $id;
            $tblNominaNotimbrada->id_nominapago_det = $ltsnominas->id;
            $tblNominaNotimbrada->fecha_inicio = $fechanominainicio;
            $tblNominaNotimbrada->fecha_fin = $fechanminafin;
            $tblNominaNotimbrada->nombre_nomina = $nombrenomina;
            $tblNominaNotimbrada->id_empleado= $ltsnominas->idempleado;
            $tblNominaNotimbrada->created_at = now();
            $tblNominaNotimbrada->created_by = auth()->id();
            $tblNominaNotimbrada->save();
            }

        }


        return back()->with("success","Movimiento Generado con Exito");
    }

    // timbra nomina dimple
    public function nominasimple($idpagodet, $idempleado, $idenc){
        // /nomina simple son vacaciones y otros conceptos adicionales isste o imss/
        $nominaenc = DB::select("select * from tblnominas_pagoenc where id = ?;",[$idenc]);
        $nominadetempleado = DB::select("select * from tblnominas_pagodet where id = ?;",[$idpagodet]);
        $datemp = DB::select("select * from tblempleados where id = ?;",[$idempleado]);
        // Configuración de credenciales
        $username = env('USER_FAC');  // Reemplaza con tu usuario real
        $password = env('PWD'); // Reemplaza con tu password real

        //variables de la factura
        $fechainicio = $nominaenc[0]->fecha_inicio;
        $fechaf = new DateTime($nominaenc[0]->fecha_fin);
        $fechafin = $fechaf->format('Y-m-d'); //este
        $sumfecha = $fechaf->modify('1 day');
        $fechapago =  $sumfecha->format('Y-m-d');  //este

        ini_set('max_execution_time', 0); // sin limite de tiempo
        set_time_limit(0);
        $client = new Client([
            'base_uri' => 'https://api.facturama.mx',
            'timeout'  => 30.0,
            'auth' => [$username, $password] // Autenticación básica
        ]);
            
        foreach($nominadetempleado as $ltsempleado)
        {
            foreach( $datemp as $ltsdatemp)
            {
                //dd($ltsempleado->pago_prima_vacacional);
                //----------------------------------------------------------
                //PERSEPCIONES--------------------------------------------
                $sueldos = $ltsempleado->sueldo_fiscal;
                $aguinaldo = 0;
                $despensa = $ltsempleado->despensa;
                $vacaciones = 0;//$ltsempleado->pago_prima_vacacional se paga en la baja
                /* Horas extra */
                $h_extra_total= $ltsempleado->total_horas_extras;
                $h_extra_exentas= $ltsempleado->horas_extras_pago_f;
                $h_extra_taxed= 0;
                $h_extra_dias= $ltsempleado->horas_extras;
                /*Horas Extra */
                $subsidio =$ltsempleado->pago_subsidio; 
                $otros = $ltsempleado->otros;
                $pagoExtraordinario = $ltsempleado->percepcion_extraordinaria;
                $antiguedad = 0; //no hay
                $pago_separacion = 0;
                $indemnizacion = 0;
                // Se anexan los pagos por prima vacacional y dias de vacaciones
                $prima_vacacional = $ltsempleado->pago_prima_vacacional_fis;
                $pago_dias_vacaciones = $ltsempleado->pago_dias_vacaciones_fis;

                        
                // /-----------------------------------------------------------/

                $resultdo_primavacacional = $this->calcularImpuestosPrimaVacacional2($prima_vacacional);

                $montoSujetoAImpuestos_primavacacional = $resultdo_primavacacional['monto_sujeto_impuestos'];
                $montoLibreAImpuestos_primavacacional = $resultdo_primavacacional['monto_libre_impuestos'];

                $PerceptionsDetails = $this->percepciones($sueldos,$despensa,$h_extra_total,$otros,$h_extra_dias,$h_extra_exentas,$h_extra_taxed,$subsidio,$pagoExtraordinario,$prima_vacacional,$pago_dias_vacaciones,$montoSujetoAImpuestos_primavacacional,$montoLibreAImpuestos_primavacacional);
        
                //DEDUCCIONES --------------------------------------------
                //----------------------------------------------------------
                $isr = $ltsempleado->pago_isr;
                $imss = $ltsempleado->pago_imss;
                $infonavit = $ltsempleado->pago_infonavit;
                $fonacot = $ltsempleado->fonacot;
                $prestamoemp = $ltsempleado->total_deudores;
                
                $deductionsDetails = $this->deducciones($isr,$imss,$infonavit,$fonacot,$prestamoemp);
            
                if($sueldos > $salario_minimo_quincenal)
                {
                    $subsidio = 0;
                }
                //variables para Datos de Empleados
                $puestoe = DB::select("SELECT nombre FROM tblpuestos WHERE id = ?;",[$ltsdatemp->idpuesto]);
                $puestoEmpleado = $puestoe[0]->nombre;
                
                //Datos bancarios
                $datosbancarios = DB::select("select banco.nombre, nomi.numero_cuenta as cuenta, nomi.numero_tarjeta as tarjeta from tblnominas_pagodet pagodet 
                join tblnominas nomi on pagodet.idempleado = nomi.idempleado
                join tblbancos banco on nomi.idbancos = banco.id where pagodet.id = ? limit 1;",[$idpagodet]);
                $nombrebanco = $datosbancarios[0]->nombre;
                $cuenta = $datosbancarios[0]->cuenta;
                $tarjeta = $datosbancarios[0]->tarjeta;
                
                //escribir el nombre en una linea
                if(!is_null($ltsdatemp->segundo_nombre) && $ltsdatemp->segundo_nombre != " " )
                {
                    $nombrecompleto = $ltsdatemp->primer_nombre." ".$ltsdatemp->segundo_nombre." ".$ltsdatemp->apellido_paterno." ".$ltsdatemp->apellido_materno;
                }
                else
                {
                $nombrecompleto = $ltsdatemp->primer_nombre." ".$ltsdatemp->apellido_paterno." ".$ltsdatemp->apellido_materno;
                }

                
                // Agregar Deductions solo si hay deducciones (no es null)
                /*   $data['Complemento']['Payroll'][0]['Deductions'] = [
                        'Details' => $deductionsDetails
                ]; */
                if ($deductionsDetails !== null) {
                    $data = [
                        'NameId' => 16,
                        'ExpeditionPlace' => '27023',
                        'CfdiType' => 'N',
                        'PaymentMethod' => 'PUE',
                        'Folio' => Null,
                        'Receiver' => [
                            'Rfc' => $ltsdatemp->rfc,
                            'Name' => $nombrecompleto,
                            'CfdiUse' => 'CN01',
                            'TaxZipCode' => $ltsdatemp->codigo_postal,
                            'FiscalRegime' => '605'
                        ],
                        'Complemento' => [
                            'Payroll' => [
                                'Type' => 'O',
                                'PaymentDate' => $fechapago.'T21:43:59.4011985-06:00',
                                'InitialPaymentDate' => $fechainicio.'T13:43:59.4011985-06:00',
                                'FinalPaymentDate' => $fechafin.'T13:43:59.4011985-06:00',
                                'DaysPaid' => $ltsempleado->dias_laborados,
                                'Issuer' => [
                                    'EmployerRegistration' => 'A4078680105',
                                    "FromEmployerRfc" => "UMM200127ME3"
                                ],            
                                'Employee' => [
                                    'Curp' => $ltsdatemp->curp,
                                    'SocialSecurityNumber' => $ltsdatemp->nss,
                                    'StartDateLaborRelations' => $ltsdatemp->fecha_ingreso.'T00:00:00.3952019-06:00',
                                    'ContractType' => '01',
                                    'RegimeType' => '02',
                                    'Unionized' => false,
                                    'TypeOfJourney' => '03',
                                    'EmployeeNumber' => $ltsdatemp->id,
                                    'Department' => 'General',
                                    'Position' => $puestoEmpleado,
                                    'PositionRisk' => '4',
                                    'FrequencyPayment' => '04',
                                    'Bank' => $nombrebanco,
                                    'BankAccount' => $cuenta,
                                    'BaseSalary' => $ltsempleado->sueldo_fiscal,
                                    'DailySalary' => $ltsempleado->salario_diario_integrado,
                                    'FederalEntityKey' => 'COA'
                                ],
                                'Perceptions' => [
                                    'Details' => $PerceptionsDetails
                                ],
                                'Deductions' => [
                                    'Details' => $deductionsDetails
                                ],
                                'OtherPayments' => [
                                    [
                                        'OtherPaymentType' => '002',
                                        'Code' => '00101',
                                        'Description' => 'otros',
                                        'Amount' => 0,
                                        'EmploymentSubsidy' => [
                                            'Amount' => 0
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ];
                }
                else{
                    $data = [
                        'NameId' => 16,
                        'ExpeditionPlace' => '27023',
                        'CfdiType' => 'N',
                        'PaymentMethod' => 'PUE',
                        'Folio' => Null,
                        'Receiver' => [
                            'Rfc' => $ltsdatemp->rfc,
                            'Name' => $nombrecompleto,
                            'CfdiUse' => 'CN01',
                            'TaxZipCode' => $ltsdatemp->codigo_postal,
                            'FiscalRegime' => '605'
                        ],
                        'Complemento' => [
                            'Payroll' => [
                                'Type' => 'O',
                                'PaymentDate' => $fechapago.'T21:43:59.4011985-06:00',
                                'InitialPaymentDate' => $fechainicio.'T13:43:59.4011985-06:00',
                                'FinalPaymentDate' => $fechafin.'T13:43:59.4011985-06:00',
                                'DaysPaid' => $ltsempleado->dias_laborados,
                                'Issuer' => [
                                    'EmployerRegistration' => 'A4078680105',
                                    "FromEmployerRfc" => "UMM200127ME3"
                                ],            
                                'Employee' => [
                                    'Curp' => $ltsdatemp->curp,
                                    'SocialSecurityNumber' => $ltsdatemp->nss,
                                    'StartDateLaborRelations' => $ltsdatemp->fecha_ingreso.'T00:00:00.3952019-06:00',
                                    'ContractType' => '01',
                                    'RegimeType' => '02',
                                    'Unionized' => false,
                                    'TypeOfJourney' => '03',
                                    'EmployeeNumber' => $ltsdatemp->id,
                                    'Department' => 'General',
                                    'Position' => $puestoEmpleado,
                                    'PositionRisk' => '4',
                                    'FrequencyPayment' => '04',
                                    'Bank' => $nombrebanco,
                                    'BankAccount' => $cuenta,
                                    'BaseSalary' => $ltsempleado->sueldo_fiscal,
                                    'DailySalary' => $ltsempleado->salario_diario_integrado,
                                    'FederalEntityKey' => 'COA'
                                ],
                                'Perceptions' => [
                                    'Details' => $PerceptionsDetails
                                ],
                                'OtherPayments' => [
                                    [
                                        'OtherPaymentType' => '002',
                                        'Code' => '00101',
                                        'Description' => 'otros',
                                        'Amount' => 0,
                                        'EmploymentSubsidy' => [
                                            'Amount' => 0
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ];
                }

            }
        }

        $headers = [
            'Content-Type' => 'application/json'
        ];

        //echo dd($data);

        try {
            $response = $client->post('/3/cfdis', [
                'headers' => $headers,
                'json' => $data
            ]);
            
            //echo dd(json_decode($response->getBody(), true));
            return json_decode($response->getBody(), true);
            //echo $response->getBody();
        } catch (RequestException $e) {
            //echo 'Error: ' . $e->getMessage();
            if ($e->hasResponse()) {
                $respuesta = (string)$e->getResponse()->getBody();
                $otrarespuesta = json_decode($e->getResponse()->getBody(), true);
                
                /*if($respuesta  = " {Message:El campo DomicilioFiscalReceptor del receptor, debe encontrarse en la lista de RFC inscritos no cancelados en el SAT.}" )
                {
                    echo "El RFC esta Incorrecto o no se encuentra en la lista de RFC inscritos no cancelados en el SAT";
                }
                else
                {
                echo "\nDetalles del error:\n" . $e->getResponse()->getBody();
                }*/

                return $respuesta;
            }
        }

    }
        
    public function verdatos($idpagodet, $idempleado, $idenc)
    {
            //   /nomina simple son vacaciones y otros conceptos adicionales isste o imss/
            $nominaenc = DB::select("select * from tblnominas_pagoenc where id = ?;",[$idenc]);
            $nominadetempleado = DB::select("select * from tblnominas_pagodet where id = ?;",[$idpagodet]);
            $datemp = DB::select("select * from tblempleados where id = ?;",[$idempleado]);
            // Configuración de credenciales
            $username = env('USER_FAC');  // Reemplaza con tu usuario real
            $password = env('PWD'); // Reemplaza con tu password real
    
            //variables de la factura
            $fechainicio = $nominaenc[0]->fecha_inicio;
            $fechaf = new DateTime($nominaenc[0]->fecha_fin);
            $fechafin = $fechaf->format('Y-m-d'); //este
            $sumfecha = $fechaf->modify('1 day');
            $fechapago =  $sumfecha->format('Y-m-d');  //este
            
            foreach($nominadetempleado as $ltsempleado)
            {
                foreach( $datemp as $ltsdatemp)
                {
                        //dd($ltsempleado->pago_prima_vacacional);
                        //PERSEPCIONES-------------------------------------------- 029 vales de despensa
                        //persepciones basicas sueldo mas despensa
                        $sueldos = $ltsempleado->sueldo_fiscal;
                        $aguinaldo = 0;
                        $despensa = $ltsempleado->despensa;
                        $vacaciones = 0;//$ltsempleado->pago_prima_vacacional se paga en la baja
                        $h_extra_total= $ltsempleado->total_horas_extras;
                        $h_extra_exentas= $ltsempleado->horas_extras_pago_f;
                        $h_extra_taxed= 0;
                        $subsidio =$ltsempleado->pago_subsidio;  // falta validar como se va usar el subsidio

                        $h_extra_dias= $ltsempleado->horas_extras;
                        $otros = $ltsempleado->otros;
                        $pagoExtraordinario = $ltsempleado->percepcion_extraordinaria; //pago extraordinario exento        $antiguedad = 0; //no hay
                        $pago_separacion = 0;
                        $indemnizacion = 0;

                        // Se anexan los pagos por prima vacacional y dias de vacaciones
                        $prima_vacacional = $ltsempleado->pago_prima_vacacional_fis;
                        $pago_dias_vacaciones = $ltsempleado->pago_dias_vacaciones_fis;
                        
                        // /-----------------------------------------------------------/

                        $resultdo_primavacacional = $this->calcularImpuestosPrimaVacacional2($prima_vacacional);
                        //dd($resultdo_primavacacional);
                        $montoSujetoAImpuestos_primavacacional = $resultdo_primavacacional['monto_sujeto_impuestos'];
                        $montoLibreAImpuestos_primavacacional = $resultdo_primavacacional['monto_libre_impuestos'];

                    // dd($montoSujetoAImpuestos_primavacacional." ".$montoLibreAImpuestos_primavacacional);

                        $PerceptionsDetails = $this->percepciones($sueldos,$despensa,$h_extra_total,$otros,$h_extra_dias,$h_extra_exentas,$h_extra_taxed,$subsidio,$pagoExtraordinario,$prima_vacacional,$pago_dias_vacaciones,$montoSujetoAImpuestos_primavacacional,$montoLibreAImpuestos_primavacacional);
                        
                        if($sueldos > $salario_minimo_quincenal)
                        {
                            $subsidio = 0;
                        }
                        //----------------------------------------------------------
                        /* Agregar total_deudores a deducciones */
                        $isr = $ltsempleado->pago_isr;
                        $imss = $ltsempleado->pago_imss;
                        $infonavit = $ltsempleado->pago_infonavit;
                        $fonacot = $ltsempleado->fonacot;
                        $prestamoemp = $ltsempleado->total_deudores;
                        
                        $deductionsDetails = $this->deducciones($isr,$imss,$infonavit,$fonacot,$prestamoemp);
                        
                        //variables para Datos de Empleados
                        $puestoe = DB::select("SELECT nombre FROM tblpuestos WHERE id = ?;",[$ltsdatemp->idpuesto]);
                        $puestoEmpleado = $puestoe[0]->nombre;

                        //Datos bancarios
                        $datosbancarios = DB::select("select banco.nombre, nomi.numero_cuenta as cuenta, nomi.numero_tarjeta as tarjeta from tblnominas_pagodet pagodet 
                        join tblnominas nomi on pagodet.idempleado = nomi.idempleado
                        join tblbancos banco on nomi.idbancos = banco.id where pagodet.id = ? limit 1;",[$idpagodet]);
                        $nombrebanco = $datosbancarios[0]->nombre;
                        $cuenta = $datosbancarios[0]->cuenta;
                        $tarjeta = $datosbancarios[0]->tarjeta;


                        //escribir el nombre en una linea
                        if(!is_null($ltsdatemp->segundo_nombre) && $ltsdatemp->segundo_nombre != " ")
                        {
                        $nombrecompleto = $ltsdatemp->primer_nombre." ".$ltsdatemp->segundo_nombre." ".$ltsdatemp->apellido_paterno." ".$ltsdatemp->apellido_materno;
                        }
                        else
                        {
                            $nombrecompleto = $ltsdatemp->primer_nombre." ".$ltsdatemp->apellido_paterno." ".$ltsdatemp->apellido_materno;
                        }


                        if ($deductionsDetails !== null) {
                            $data = [
                                'NameId' => 16,
                                'ExpeditionPlace' => '27023',
                                'CfdiType' => 'N',
                                'PaymentMethod' => 'PUE',
                                'Folio' => Null,
                                'Receiver' => [
                                    'Rfc' => $ltsdatemp->rfc,
                                    'Name' => $nombrecompleto,
                                    'CfdiUse' => 'CN01',
                                    'TaxZipCode' => $ltsdatemp->codigo_postal,
                                    'FiscalRegime' => '605'
                                ],
                                'Complemento' => [
                                    'Payroll' => [
                                        'Type' => 'O',
                                        'PaymentDate' => $fechapago.'T21:43:59.4011985-06:00',
                                        'InitialPaymentDate' => $fechainicio.'T13:43:59.4011985-06:00',
                                        'FinalPaymentDate' => $fechafin.'T13:43:59.4011985-06:00',
                                        'DaysPaid' => $ltsempleado->dias_laborados,
                                        'Issuer' => [
                                            'EmployerRegistration' => 'A4078680105',
                                            "FromEmployerRfc" => "UMM200127ME3"
                                        ],            
                                        'Employee' => [
                                            'Curp' => $ltsdatemp->curp,
                                            'SocialSecurityNumber' => $ltsdatemp->nss,
                                            'StartDateLaborRelations' => $ltsdatemp->fecha_ingreso.'T00:00:00.3952019-06:00',
                                            'ContractType' => '01',
                                            'RegimeType' => '02',
                                            'Unionized' => false,
                                            'TypeOfJourney' => '03',
                                            'EmployeeNumber' => $ltsdatemp->id,
                                            'Department' => 'General',
                                            'Position' => $puestoEmpleado,
                                            'PositionRisk' => '4',
                                            'FrequencyPayment' => '04',
                                            'Bank' => $nombrebanco,
                                            'BankAccount' => $cuenta,
                                            'BaseSalary' => $ltsempleado->sueldo_fiscal,
                                            'DailySalary' => $ltsempleado->salario_diario_integrado,
                                            'FederalEntityKey' => 'COA'
                                        ],
                                        'Perceptions' => [
                                            'Details' => $PerceptionsDetails
                                        ],
                                        'Deductions' => [
                                            'Details' => $deductionsDetails
                                        ],
                                        'OtherPayments' => [
                                            [
                                                'OtherPaymentType' => '002',
                                                'Code' => '00101',
                                                'Description' => 'otros',
                                                'Amount' => 0,
                                                'EmploymentSubsidy' => [
                                                    'Amount' => 0
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ];
                        }
                        else{
                            $data = [
                                'NameId' => 16,
                                'ExpeditionPlace' => '27023',
                                'CfdiType' => 'N',
                                'PaymentMethod' => 'PUE',
                                'Folio' => Null,
                                'Receiver' => [
                                    'Rfc' => $ltsdatemp->rfc,
                                    'Name' => $nombrecompleto,
                                    'CfdiUse' => 'CN01',
                                    'TaxZipCode' => $ltsdatemp->codigo_postal,
                                    'FiscalRegime' => '605'
                                ],
                                'Complemento' => [
                                    'Payroll' => [
                                        'Type' => 'O',
                                        'PaymentDate' => $fechapago.'T21:43:59.4011985-06:00',
                                        'InitialPaymentDate' => $fechainicio.'T13:43:59.4011985-06:00',
                                        'FinalPaymentDate' => $fechafin.'T13:43:59.4011985-06:00',
                                        'DaysPaid' => $ltsempleado->dias_laborados,
                                        'Issuer' => [
                                            'EmployerRegistration' => 'A4078680105',
                                            "FromEmployerRfc" => "UMM200127ME3"
                                        ],            
                                        'Employee' => [
                                            'Curp' => $ltsdatemp->curp,
                                            'SocialSecurityNumber' => $ltsdatemp->nss,
                                            'StartDateLaborRelations' => $ltsdatemp->fecha_ingreso.'T00:00:00.3952019-06:00',
                                            'ContractType' => '01',
                                            'RegimeType' => '02',
                                            'Unionized' => false,
                                            'TypeOfJourney' => '03',
                                            'EmployeeNumber' => $ltsdatemp->id,
                                            'Department' => 'General',
                                            'Position' => $puestoEmpleado,
                                            'PositionRisk' => '4',
                                            'FrequencyPayment' => '04',
                                            'Bank' => $nombrebanco,
                                            'BankAccount' => $cuenta,
                                            'BaseSalary' => $ltsempleado->sueldo_fiscal,
                                            'DailySalary' => $ltsempleado->salario_diario_integrado,
                                            'FederalEntityKey' => 'COA'
                                        ],
                                        'Perceptions' => [
                                            'Details' => $PerceptionsDetails
                                        ],
                                        'OtherPayments' => [
                                            [
                                                'OtherPaymentType' => '002',
                                                'Code' => '00101',
                                                'Description' => 'otros',
                                                'Amount' => 0,
                                                'EmploymentSubsidy' => [
                                                    'Amount' => 0
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ];
                        }

                    return $data;
                }

                //echo dd($data);
            }
    }

    public function Datosempleado($id)
    {

    }

    /*--------------------------------------------------------------------------------------------------------- */
    //PERSEPCIONES
    /*
    $h_extra_total= $ltsempleado->total_horas_extras;
    $h_extra_exentas= $ltsempleado->horas_extras_pago_e;
    $h_extra_taxed= $ltsempleado->horas_extras_pago_f;
    /*--------------------------------------------------------------------------------------------------------- */
    public function percepciones($sueldos,$despensa,$h_extra_total,$otros,$h_extra_dias,$h_extra_exentas,$h_extra_taxed,$subsidio,$pagoExtraordinario,$prima_vacacional,$pago_dias_vacaciones,$montoSujetoAImpuestos_primavacacional,$montoLibreAImpuestos_primavacacional)
    {
        // Inicializar la variable para evitar error de variable indefinida
        $PerceptionsDetails = null;
        $UMA = (float)conceptos_nomina::where('nombre', 'uma_diaria')->first()->valor;
        $salario_minimo_quincenal = (float)conceptos_nomina::where('nombre', 'salario_minimo_quincenal')->first()->valor;
        
        /* Sueldo Minimo  sin impuestos*/
        if($h_extra_total < 1 && $pagoExtraordinario < 1 && $otros < 1 && $pago_dias_vacaciones < 1 && $prima_vacacional < 1 && $despensa < 1 && $sueldos <= $salario_minimo_quincenal)
        {
         $PerceptionsDetails = [
            [
                'PerceptionType' => '001',
                'Code' => '001',
                'Description' => 'Salario Quincenal',
                'TaxedAmount' => 0,
                'ExemptAmount' => $sueldos
            ]
        ];
        }
        
        /* Sueldo con impuestos*/
        if($h_extra_total < 1 && $pagoExtraordinario < 1 && $otros < 1 && $pago_dias_vacaciones < 1 && $prima_vacacional < 1 && $despensa < 1 && $sueldos > $salario_minimo_quincenal)
        {
         $PerceptionsDetails = [
            [
                'PerceptionType' => '001',
                'Code' => '001',
                'Description' => 'Salario Quincenal',
                'TaxedAmount' => $sueldos,
                'ExemptAmount' => 0
            ]
        ];
        }
        //persepciones sueldo + despensa + otros
        if($h_extra_total < 1 && $pagoExtraordinario < 1 && $otros > 1 && $pago_dias_vacaciones < 1 && $prima_vacacional < 1 && $despensa > 0)
        {
        if($sueldos > $salario_minimo_quincenal)
         {
            $PerceptionsDetails = [
                [
                    'PerceptionType' => '001',
                    'Code' => '001',
                    'Description' => 'Salario Quincenal',
                    'TaxedAmount' => $sueldos,
                    'ExemptAmount' => 0
                ],
                [
                    'PerceptionType' => '029',
                    'Code' => '029',
                    'Description' => 'Despensa',
                    'TaxedAmount' => $despensa,
                    'ExemptAmount' => 0
                ],
                [
                    'PerceptionType' => '038',
                    'Code' => '038',
                    'Description' => 'Percepción Excenta',
                    'TaxedAmount' => 0,
                    'ExemptAmount' => $otros
                ]
            ];
         } else if($sueldos <= $salario_minimo_quincenal){
            $PerceptionsDetails = [
                [
                    'PerceptionType' => '001',
                    'Code' => '001',
                    'Description' => 'Salario Quincenal',
                    'TaxedAmount' => 0,
                    'ExemptAmount' => $sueldos
                ],
                [
                    'PerceptionType' => '029',
                    'Code' => '029',
                    'Description' => 'Despensa',
                    'TaxedAmount' => $despensa,
                    'ExemptAmount' => 0
                ],
                [
                    'PerceptionType' => '038',
                    'Code' => '038',
                    'Description' => 'Percepción Excenta',
                    'TaxedAmount' => 0,
                    'ExemptAmount' => $otros
                ]
            ];
        }
        } else if($h_extra_total < 1 && $pagoExtraordinario < 1 && $otros > 1 && $pago_dias_vacaciones < 1 && $prima_vacacional < 1 && $despensa < 1)
        {  //persepciones sueldo  + otros
            if($sueldos > $salario_minimo_quincenal)
            {
            $PerceptionsDetails = [
                [
                    'PerceptionType' => '001',
                    'Code' => '001',
                    'Description' => 'Salario Quincenal',
                    'TaxedAmount' => $sueldos,
                    'ExemptAmount' => 0
                ],
                [
                    'PerceptionType' => '038',
                    'Code' => '038',
                    'Description' => 'Percepción Excenta',
                    'TaxedAmount' => 0,
                    'ExemptAmount' => $otros
                ]
            ];
            }
            else if($sueldos <= $salario_minimo_quincenal){
                $PerceptionsDetails = [
                    [
                        'PerceptionType' => '001',
                        'Code' => '001',
                        'Description' => 'Salario Quincenal',
                        'TaxedAmount' => 0,
                        'ExemptAmount' => $sueldos
                    ],
                    [
                        'PerceptionType' => '038',
                        'Code' => '038',
                        'Description' => 'Percepción Excenta',
                        'TaxedAmount' => 0,
                        'ExemptAmount' => $otros
                    ]
                ];
            }
        }
                //persepciones sueldo + despensa + horas Extra
        if($h_extra_total > 1 && $pagoExtraordinario < 1 && $otros < 1 && $pago_dias_vacaciones < 1 && $prima_vacacional < 1 && $despensa > 0)
        {
            if($sueldos > $salario_minimo_quincenal)
            {
                $PerceptionsDetails = [
                    [
                        'PerceptionType' => '001',
                        'Code' => '001',
                        'Description' => 'Salario Quincenal',
                        'TaxedAmount' => $sueldos,
                        'ExemptAmount' => 0
                    ],
                    [
                        'PerceptionType' => '029',
                        'Code' => '029',
                        'Description' => 'Despensa',
                        'TaxedAmount' => $despensa,
                        'ExemptAmount' => 0
                    ],
                    [
                        'PerceptionType' => '019',
                        'Code' => '019',
                        'Description' => 'Horas Extra',
                        'TaxedAmount' => 0,
                        'ExemptAmount' => $h_extra_exentas,
                        "ExtraHours"=> [
                        [
                          "Days"=> 2,
                          "HoursType"=> "01",
                          "ExtraHours"=> $h_extra_dias,
                          "PaidAmount"=> $h_extra_exentas
                        ]
                      ]
                    ]
                ];
            }
            else if($sueldos <= $salario_minimo_quincenal){
                $PerceptionsDetails = [
                    [
                        'PerceptionType' => '001',
                        'Code' => '001',
                        'Description' => 'Salario Quincenal',
                        'TaxedAmount' => 0,
                        'ExemptAmount' => $sueldos
                    ],
                    [
                        'PerceptionType' => '029',
                        'Code' => '029',
                        'Description' => 'Despensa',
                        'TaxedAmount' => $despensa,
                        'ExemptAmount' => 0
                    ],
                    [
                        'PerceptionType' => '019',
                        'Code' => '019',
                        'Description' => 'Horas Extra',
                        'TaxedAmount' => 0,
                        'ExemptAmount' => $h_extra_exentas,
                        "ExtraHours"=> [
                        [
                          "Days"=> 2,
                          "HoursType"=> "01",
                          "ExtraHours"=> $h_extra_dias,
                          "PaidAmount"=> $h_extra_exentas
                        ]
                      ]
                    ]
                ];
            }
        }
        else if($h_extra_total > 1 && $pagoExtraordinario < 1 && $otros < 1 && $pago_dias_vacaciones < 1 && $prima_vacacional < 1 && $despensa < 1)
        {
           if($sueldos > $salario_minimo_quincenal)
           {
            $PerceptionsDetails = [
                [
                    'PerceptionType' => '001',
                    'Code' => '001',
                    'Description' => 'Salario Quincenal',
                    'TaxedAmount' => $sueldos,
                    'ExemptAmount' => 0
                ],
                [
                    'PerceptionType' => '019',
                    'Code' => '019',
                    'Description' => 'Horas Extra',
                    'TaxedAmount' => 0,
                    'ExemptAmount' => $h_extra_exentas,
                    "ExtraHours"=> [
                    [
                      "Days"=> 2,
                      "HoursType"=> "01",
                      "ExtraHours"=> $h_extra_dias,
                      "PaidAmount"=> $h_extra_exentas
                    ]
                  ]
                ]
            ];
           }
           else if($sueldos <= $salario_minimo_quincenal){
            $PerceptionsDetails = [
                [
                    'PerceptionType' => '001',
                    'Code' => '001',
                    'Description' => 'Salario Quincenal',
                    'TaxedAmount' => 0,
                    'ExemptAmount' => $sueldos
                ],
                [
                    'PerceptionType' => '019',
                    'Code' => '019',
                    'Description' => 'Horas Extra',
                    'TaxedAmount' => 0,
                    'ExemptAmount' => $h_extra_exentas,
                    "ExtraHours"=> [
                    [
                      "Days"=> 2,
                      "HoursType"=> "01",
                      "ExtraHours"=> $h_extra_dias,
                      "PaidAmount"=> $h_extra_exentas
                    ]
                  ]
                ]
            ];
           }
        }
            //persepciones sueldo + despensa + otros + horas extra
        if($h_extra_total > 1 && $pagoExtraordinario < 1 && $otros > 1 && $pago_dias_vacaciones < 1 && $prima_vacacional < 1 && $despensa > 0)
        {
            if($sueldos > $salario_minimo_quincenal)
            {
                $PerceptionsDetails = [
                    [
                        'PerceptionType' => '001',
                        'Code' => '001',
                        'Description' => 'Salario Quincenal',
                        'TaxedAmount' => $sueldos,
                        'ExemptAmount' => 0
                    ],
                    [
                        'PerceptionType' => '029',
                        'Code' => '029',
                        'Description' => 'Despensa',
                        'TaxedAmount' => $despensa,
                        'ExemptAmount' => 0
                    ],
                    [
                        'PerceptionType' => '038',
                        'Code' => '038',
                        'Description' => 'Percepción Excenta',
                        'TaxedAmount' => 0,
                        'ExemptAmount' => $otros
                    ],
                    [
                        'PerceptionType' => '019',
                        'Code' => '019',
                        'Description' => 'Horas Extra',
                        'TaxedAmount' => 0,
                        'ExemptAmount' => $h_extra_exentas,
                        "ExtraHours"=> [
                        [
                          "Days"=> 2,
                          "HoursType"=> "01",
                          "ExtraHours"=> $h_extra_dias,
                          "PaidAmount"=> $h_extra_exentas
                        ]
                      ]
                    ]
                ];
            }
            else if($sueldos <= $salario_minimo_quincenal){
                $PerceptionsDetails = [
                    [
                        'PerceptionType' => '001',
                        'Code' => '001',
                        'Description' => 'Salario Quincenal',
                        'TaxedAmount' => 0,
                        'ExemptAmount' => $sueldos
                    ],
                    [
                        'PerceptionType' => '029',
                        'Code' => '029',
                        'Description' => 'Despensa',
                        'TaxedAmount' => $despensa,
                        'ExemptAmount' => 0
                    ],
                    [
                        'PerceptionType' => '038',
                        'Code' => '038',
                        'Description' => 'Percepción Excenta',
                        'TaxedAmount' => 0,
                        'ExemptAmount' => $otros
                    ],
                    [
                        'PerceptionType' => '019',
                        'Code' => '019',
                        'Description' => 'Horas Extra',
                        'TaxedAmount' => 0,
                        'ExemptAmount' => $h_extra_exentas,
                        "ExtraHours"=> [
                        [
                          "Days"=> 2,
                          "HoursType"=> "01",
                          "ExtraHours"=> $h_extra_dias,
                          "PaidAmount"=> $h_extra_exentas
                        ]
                      ]
                    ]
                ];
            }
        }else if($h_extra_total > 1 && $pagoExtraordinario < 1 && $otros > 1 && $pago_dias_vacaciones < 1 && $prima_vacacional < 1 && $despensa < 1)
        {
            if($sueldos > $salario_minimo_quincenal)
            {
                $PerceptionsDetails = [
                    [
                        'PerceptionType' => '001',
                        'Code' => '001',
                        'Description' => 'Salario Quincenal',
                        'TaxedAmount' => $sueldos,
                        'ExemptAmount' => 0
                    ],
                    [
                        'PerceptionType' => '038',
                        'Code' => '038',
                        'Description' => 'Percepción Excenta',
                        'TaxedAmount' => 0,
                        'ExemptAmount' => $otros
                    ],
                    [
                        'PerceptionType' => '019',
                        'Code' => '019',
                        'Description' => 'Horas Extra',
                        'TaxedAmount' => 0,
                        'ExemptAmount' => $h_extra_exentas,
                        "ExtraHours"=> [
                        [
                          "Days"=> 2,
                          "HoursType"=> "01",
                          "ExtraHours"=> $h_extra_dias,
                          "PaidAmount"=> $h_extra_exentas
                        ]
                      ]
                    ]
                ];   
            }
            else if($sueldos <= $salario_minimo_quincenal)
            {
                $PerceptionsDetails = [
                    [
                        'PerceptionType' => '001',
                        'Code' => '001',
                        'Description' => 'Salario Quincenal',
                        'TaxedAmount' => 0,
                        'ExemptAmount' => $sueldos
                    ],
                    [
                        'PerceptionType' => '038',
                        'Code' => '038',
                        'Description' => 'Percepción Excenta',
                        'TaxedAmount' => 0,
                        'ExemptAmount' => $otros
                    ],
                    [
                        'PerceptionType' => '019',
                        'Code' => '019',
                        'Description' => 'Horas Extra',
                        'TaxedAmount' => 0,
                        'ExemptAmount' => $h_extra_exentas,
                        "ExtraHours"=> [
                        [
                          "Days"=> 2,
                          "HoursType"=> "01",
                          "ExtraHours"=> $h_extra_dias,
                          "PaidAmount"=> $h_extra_exentas
                        ]
                      ]
                    ]
                ];
            }
        }

                  // persepciones sueldo + despensa + h_extra + pago extraordinario + otros
                  if($h_extra_total > 1 && $pagoExtraordinario >1 && $otros > 1 && $pago_dias_vacaciones < 1 && $prima_vacacional < 1 && $despensa > 0)
                  {
                     if($sueldos > $salario_minimo_quincenal)
                     {
                        $PerceptionsDetails = [
                            [
                                'PerceptionType' => '001',
                                'Code' => '001',
                                'Description' => 'Salario Quincenal',
                                'TaxedAmount' => $sueldos,
                                'ExemptAmount' => 0
                            ],
                            [
                                'PerceptionType' => '029',
                                'Code' => '029',
                                'Description' => 'Despensa',
                                'TaxedAmount' => $despensa,
                                'ExemptAmount' => 0
                            ],
                            [
                                'PerceptionType' => '038',
                                'Code' => '038',
                                'Description' => 'Percepción Excenta',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $otros
                            ],
                            [
                                'PerceptionType' => '038',
                                'Code' => '038',
                                'Description' => 'Percepción Excenta',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $pagoExtraordinario
                            ],
                            [
                                'PerceptionType' => '019',
                                'Code' => '019',
                                'Description' => 'Horas Extra',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $h_extra_exentas,
                                "ExtraHours"=> [
                                [
                                  "Days"=> 2,
                                  "HoursType"=> "01",
                                  "ExtraHours"=> $h_extra_dias,
                                  "PaidAmount"=> $h_extra_exentas
                                ]
                              ]
                            ]
                        ];  
                     }
                     else if($sueldos <= $salario_minimo_quincenal){
                        $PerceptionsDetails = [
                            [
                                'PerceptionType' => '001',
                                'Code' => '001',
                                'Description' => 'Salario Quincenal',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $sueldos
                            ],
                            [
                                'PerceptionType' => '029',
                                'Code' => '029',
                                'Description' => 'Despensa',
                                'TaxedAmount' => $despensa,
                                'ExemptAmount' => 0
                            ],
                            [
                                'PerceptionType' => '038',
                                'Code' => '038',
                                'Description' => 'Percepción Excenta',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $otros
                            ],
                            [
                                'PerceptionType' => '038',
                                'Code' => '038',
                                'Description' => 'Percepción Excenta',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $pagoExtraordinario
                            ],
                            [
                                'PerceptionType' => '019',
                                'Code' => '019',
                                'Description' => 'Horas Extra',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $h_extra_exentas,
                                "ExtraHours"=> [
                                [
                                  "Days"=> 2,
                                  "HoursType"=> "01",
                                  "ExtraHours"=> $h_extra_dias,
                                  "PaidAmount"=> $h_extra_exentas
                                ]
                              ]
                            ]
                        ];
                    }
                  }
                  else if($h_extra_total > 1 && $pagoExtraordinario >1 && $otros > 1 && $pago_dias_vacaciones < 1 && $prima_vacacional < 1 && $despensa < 1)
                  {
                    if($sueldos > $salario_minimo_quincenal)
                    {
                        $PerceptionsDetails = [
                            [
                                'PerceptionType' => '001',
                                'Code' => '001',
                                'Description' => 'Salario Quincenal',
                                'TaxedAmount' => $sueldos,
                                'ExemptAmount' => 0
                            ],
                            [
                                'PerceptionType' => '038',
                                'Code' => '038',
                                'Description' => 'Percepción Excenta',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $otros
                            ],
                            [
                                'PerceptionType' => '038',
                                'Code' => '038',
                                'Description' => 'Percepción Excenta',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $pagoExtraordinario
                            ],
                            [
                                'PerceptionType' => '019',
                                'Code' => '019',
                                'Description' => 'Horas Extra',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $h_extra_exentas,
                                "ExtraHours"=> [
                                [
                                  "Days"=> 2,
                                  "HoursType"=> "01",
                                  "ExtraHours"=> $h_extra_dias,
                                  "PaidAmount"=> $h_extra_exentas
                                ]
                              ]
                            ]
                        ];
                    }
                    else if($sueldos <= $salario_minimo_quincenal){
                        $PerceptionsDetails = [
                            [
                                'PerceptionType' => '001',
                                'Code' => '001',
                                'Description' => 'Salario Quincenal',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $sueldos
                            ],
                            [
                                'PerceptionType' => '038',
                                'Code' => '038',
                                'Description' => 'Percepción Excenta',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $otros
                            ],
                            [
                                'PerceptionType' => '038',
                                'Code' => '038',
                                'Description' => 'Percepción Excenta',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $pagoExtraordinario
                            ],
                            [
                                'PerceptionType' => '019',
                                'Code' => '019',
                                'Description' => 'Horas Extra',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $h_extra_exentas,
                                "ExtraHours"=> [
                                [
                                  "Days"=> 2,
                                  "HoursType"=> "01",
                                  "ExtraHours"=> $h_extra_dias,
                                  "PaidAmount"=> $h_extra_exentas
                                ]
                              ]
                            ]
                        ];
                    }
                  }
              
                  // persepciones sueldo + despensa + h_extra + persepcionexcentas  nacozari 
                  if($h_extra_total > 1 && $pagoExtraordinario >1 && $otros < 1 && $pago_dias_vacaciones < 1 && $prima_vacacional < 1 && $despensa > 0)
                  {
                    if($sueldos > $salario_minimo_quincenal)
                    {
                        $PerceptionsDetails = [
                            [
                                'PerceptionType' => '001',
                                'Code' => '001',
                                'Description' => 'Salario Quincenal',
                                'TaxedAmount' => $sueldos,
                                'ExemptAmount' => 0
                            ],
                            [
                                'PerceptionType' => '029',
                                'Code' => '029',
                                'Description' => 'Despensa',
                                'TaxedAmount' => $despensa,
                                'ExemptAmount' => 0
                            ],
                            [
                                'PerceptionType' => '038',
                                'Code' => '038',
                                'Description' => 'Percepción Excenta',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $pagoExtraordinario
                            ],
                            [
                                'PerceptionType' => '019',
                                'Code' => '019',
                                'Description' => 'Horas Extra',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $h_extra_exentas,
                                "ExtraHours"=> [
                                [
                                  "Days"=> 2,
                                  "HoursType"=> "01",
                                  "ExtraHours"=> $h_extra_dias,
                                  "PaidAmount"=> $h_extra_exentas
                                ]
                              ]
                            ]
                        ];
                    }
                    else if($sueldos <= $salario_minimo_quincenal){
                        $PerceptionsDetails = [
                            [
                                'PerceptionType' => '001',
                                'Code' => '001',
                                'Description' => 'Salario Quincenal',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $sueldos
                            ],
                            [
                                'PerceptionType' => '029',
                                'Code' => '029',
                                'Description' => 'Despensa',
                                'TaxedAmount' => $despensa,
                                'ExemptAmount' => 0
                            ],
                            [
                                'PerceptionType' => '038',
                                'Code' => '038',
                                'Description' => 'Percepción Excenta',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $pagoExtraordinario
                            ],
                            [
                                'PerceptionType' => '019',
                                'Code' => '019',
                                'Description' => 'Horas Extra',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $h_extra_exentas,
                                "ExtraHours"=> [
                                [
                                  "Days"=> 2,
                                  "HoursType"=> "01",
                                  "ExtraHours"=> $h_extra_dias,
                                  "PaidAmount"=> $h_extra_exentas
                                ]
                              ]
                            ]
                        ];
                    }
                  }else if($h_extra_total > 1 && $pagoExtraordinario >1 && $otros < 1 && $pago_dias_vacaciones < 1 && $prima_vacacional < 1 && $despensa < 1)
                  {
                    if($sueldos > $salario_minimo_quincenal)
                    {
                        $PerceptionsDetails = [
                            [
                                'PerceptionType' => '001',
                                'Code' => '001',
                                'Description' => 'Salario Quincenal',
                                'TaxedAmount' => $sueldos,
                                'ExemptAmount' => 0
                            ],
                            [
                                'PerceptionType' => '038',
                                'Code' => '038',
                                'Description' => 'Percepción Excenta',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $pagoExtraordinario
                            ],
                            [
                                'PerceptionType' => '019',
                                'Code' => '019',
                                'Description' => 'Horas Extra',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $h_extra_exentas,
                                "ExtraHours"=> [
                                [
                                  "Days"=> 2,
                                  "HoursType"=> "01",
                                  "ExtraHours"=> $h_extra_dias,
                                  "PaidAmount"=> $h_extra_exentas
                                ]
                              ]
                            ]
                        ];
                    }
                    else if($sueldos <= $salario_minimo_quincenal){
                        $PerceptionsDetails = [
                            [
                                'PerceptionType' => '001',
                                'Code' => '001',
                                'Description' => 'Salario Quincenal',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $sueldos
                            ],
                            [
                                'PerceptionType' => '038',
                                'Code' => '038',
                                'Description' => 'Percepción Excenta',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $pagoExtraordinario
                            ],
                            [
                                'PerceptionType' => '019',
                                'Code' => '019',
                                'Description' => 'Horas Extra',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $h_extra_exentas,
                                "ExtraHours"=> [
                                [
                                  "Days"=> 2,
                                  "HoursType"=> "01",
                                  "ExtraHours"=> $h_extra_dias,
                                  "PaidAmount"=> $h_extra_exentas
                                ]
                              ]
                            ]
                        ];
                    }
                  }

                         // persepciones sueldo + despensa + pago extraordinario  nacozari 
             if($h_extra_total < 1 && $pagoExtraordinario >1 && $otros < 1 && $pago_dias_vacaciones < 1 && $prima_vacacional < 1 && $despensa > 0)
             {
                if($sueldos > $salario_minimo_quincenal)
                {
                    $PerceptionsDetails = [
                        [
                            'PerceptionType' => '001',
                            'Code' => '001',
                            'Description' => 'Salario Quincenal',
                            'TaxedAmount' => $sueldos,
                            'ExemptAmount' => 0
                        ],
                        [
                            'PerceptionType' => '029',
                            'Code' => '029',
                            'Description' => 'Despensa',
                            'TaxedAmount' => $despensa,
                            'ExemptAmount' => 0
                        ],
                        [
                            'PerceptionType' => '038',
                            'Code' => '038',
                            'Description' => 'Percepción Excenta',
                            'TaxedAmount' => 0,
                            'ExemptAmount' => $pagoExtraordinario
                        ]
                    ];
                }
                else if($sueldos <= $salario_minimo_quincenal)
                {
                    $PerceptionsDetails = [
                        [
                            'PerceptionType' => '001',
                            'Code' => '001',
                            'Description' => 'Salario Quincenal',
                            'TaxedAmount' => 0,
                            'ExemptAmount' => $sueldos
                        ],
                        [
                            'PerceptionType' => '029',
                            'Code' => '029',
                            'Description' => 'Despensa',
                            'TaxedAmount' => $despensa,
                            'ExemptAmount' => 0
                        ],
                        [
                            'PerceptionType' => '038',
                            'Code' => '038',
                            'Description' => 'Percepción Excenta',
                            'TaxedAmount' => 0,
                            'ExemptAmount' => $pagoExtraordinario
                        ]
                    ];
                }
             }
             else if($h_extra_total < 1 && $pagoExtraordinario >1 && $otros < 1 && $pago_dias_vacaciones < 1 && $prima_vacacional < 1 && $despensa < 1)
             {
                if($sueldos > $salario_minimo_quincenal)
                {
                    $PerceptionsDetails = [
                        [
                            'PerceptionType' => '001',
                            'Code' => '001',
                            'Description' => 'Salario Quincenal',
                            'TaxedAmount' => $sueldos,
                            'ExemptAmount' => 0
                        ],
                        [
                            'PerceptionType' => '038',
                            'Code' => '038',
                            'Description' => 'Percepción Excenta',
                            'TaxedAmount' => 0,
                            'ExemptAmount' => $pagoExtraordinario
                        ]
                    ];
                }
                else if($sueldos <= $salario_minimo_quincenal)
                {
                    $PerceptionsDetails = [
                        [
                            'PerceptionType' => '001',
                            'Code' => '001',
                            'Description' => 'Salario Quincenal',
                            'TaxedAmount' => 0,
                            'ExemptAmount' => $sueldos
                        ],
                        [
                            'PerceptionType' => '038',
                            'Code' => '038',
                            'Description' => 'Percepción Excenta',
                            'TaxedAmount' => 0,
                            'ExemptAmount' => $pagoExtraordinario
                        ]
                    ];
                }
             }

   
                     //-----------------------------------------------------------------------------------------------------------------
                         // Sualdo normal + despensa + prima vacacional Nuevo
                         if($prima_vacacional > 1 && $h_extra_total < 1 && $pagoExtraordinario < 1 && $otros < 1 && $pago_dias_vacaciones < 1 && $despensa > 0)
                         {
                           if($sueldos > $salario_minimo_quincenal)
                           {
                            $PerceptionsDetails = [
                                [
                                    'PerceptionType' => '001',
                                    'Code' => '001',
                                    'Description' => 'Salario Quincenal',
                                    'TaxedAmount' => $sueldos,
                                    'ExemptAmount' => 0
                                ],
                                [
                                    'PerceptionType' => '029',
                                    'Code' => '029',
                                    'Description' => 'Despensa',
                                    'TaxedAmount' => $despensa,
                                    'ExemptAmount' => 0
                                ],
                                [
                                    'PerceptionType' => '021',
                                    'Code' => '021',
                                    'Description' => 'Prima Vacacional',
                                    'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                    'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                ]
                            ];
                           }
                           else if($sueldos <= $salario_minimo_quincenal){
                            $PerceptionsDetails = [
                                [
                                    'PerceptionType' => '001',
                                    'Code' => '001',
                                    'Description' => 'Salario Quincenal',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $sueldos
                                ],
                                [
                                    'PerceptionType' => '029',
                                    'Code' => '029',
                                    'Description' => 'Despensa',
                                    'TaxedAmount' => $despensa,
                                    'ExemptAmount' => 0
                                ],
                                [
                                    'PerceptionType' => '021',
                                    'Code' => '021',
                                    'Description' => 'Prima Vacacional',
                                    'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                    'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                ]
                            ];
                            }
                         }
                         else if($prima_vacacional > 1 && $h_extra_total < 1 && $pagoExtraordinario < 1 && $otros < 1 && $pago_dias_vacaciones < 1 && $despensa < 1)
                         {
                            if($sueldos > $salario_minimo_quincenal)
                            {
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => $sueldos,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '021',
                                        'Code' => '021',
                                        'Description' => 'Prima Vacacional',
                                        'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                        'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                    ]
                                ];
                            }
                            else if($sueldos <= $salario_minimo_quincenal){
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $sueldos
                                    ],
                                    [
                                        'PerceptionType' => '021',
                                        'Code' => '021',
                                        'Description' => 'Prima Vacacional',
                                        'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                        'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                    ]
                                ];
                            }
                         }
                         // Sualdo normal + despensa + prima vacacional + Otros
                         if($prima_vacacional > 1 && $otros > 1 && $h_extra_total < 1 && $pagoExtraordinario < 1 && $pago_dias_vacaciones < 1 && $despensa > 0)
                         {
                            if($sueldos > $salario_minimo_quincenal)
                            {
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => $sueldos,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '029',
                                        'Code' => '029',
                                        'Description' => 'Despensa',
                                        'TaxedAmount' => $despensa,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '038',
                                        'Code' => '038',
                                        'Description' => 'Percepción Excenta',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $otros
                                    ],
                                    [
                                        'PerceptionType' => '021',
                                        'Code' => '021',
                                        'Description' => 'Prima Vacacional',
                                        'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                        'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                    ]
                                ];
                            }
                            else if($sueldos <= $salario_minimo_quincenal){
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $sueldos
                                    ],
                                    [
                                        'PerceptionType' => '029',
                                        'Code' => '029',
                                        'Description' => 'Despensa',
                                        'TaxedAmount' => $despensa,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '038',
                                        'Code' => '038',
                                        'Description' => 'Percepción Excenta',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $otros
                                    ],
                                    [
                                        'PerceptionType' => '021',
                                        'Code' => '021',
                                        'Description' => 'Prima Vacacional',
                                        'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                        'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                    ]
                                ];
                            }
                         }
                         else if($prima_vacacional > 1 && $otros > 1 && $h_extra_total < 1 && $pagoExtraordinario < 1 && $pago_dias_vacaciones < 1 &&$despensa < 1)
                         {
                            if($sueldos > $salario_minimo_quincenal)
                            {
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => $sueldos,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '038',
                                        'Code' => '038',
                                        'Description' => 'Percepción Excenta',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $otros
                                    ],
                                    [
                                        'PerceptionType' => '021',
                                        'Code' => '021',
                                        'Description' => 'Prima Vacacional',
                                        'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                        'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                    ]
                                ];
                            }
                            else if($sueldos <= $salario_minimo_quincenal){
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $sueldos
                                    ],
                                    [
                                        'PerceptionType' => '038',
                                        'Code' => '038',
                                        'Description' => 'Percepción Excenta',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $otros
                                    ],
                                    [
                                        'PerceptionType' => '021',
                                        'Code' => '021',
                                        'Description' => 'Prima Vacacional',
                                        'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                        'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                    ]
                                ];
                            }
                         }
                           // persepciones sueldo + despensa + prima vacacional + Pago extraordinario + otros
                           if($prima_vacacional > 1 && $pagoExtraordinario > 1 && $otros > 1 && $h_extra_total < 1 && $pago_dias_vacaciones < 1 && $despensa > 0)
                           {
                               if($sueldos > $salario_minimo_quincenal)
                               {
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => $sueldos,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '029',
                                        'Code' => '029',
                                        'Description' => 'Despensa',
                                        'TaxedAmount' => $despensa,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '038',
                                        'Code' => '038',
                                        'Description' => 'Percepción Excenta',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $otros
                                    ],
                                    [
                                        'PerceptionType' => '038',
                                        'Code' => '038',
                                        'Description' => 'Percepción Excenta',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $pagoExtraordinario
                                    ],
                                    [
                                      'PerceptionType' => '021',
                                      'Code' => '021',
                                      'Description' => 'Prima Vacacional',
                                      'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                      'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                  ]
                                ];
                               }
                               else if($sueldos <= $salario_minimo_quincenal){
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $sueldos
                                    ],
                                    [
                                        'PerceptionType' => '029',
                                        'Code' => '029',
                                        'Description' => 'Despensa',
                                        'TaxedAmount' => $despensa,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '038',
                                        'Code' => '038',
                                        'Description' => 'Percepción Excenta',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $otros
                                    ],
                                    [
                                        'PerceptionType' => '038',
                                        'Code' => '038',
                                        'Description' => 'Percepción Excenta',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $pagoExtraordinario
                                    ],
                                    [
                                      'PerceptionType' => '021',
                                      'Code' => '021',
                                      'Description' => 'Prima Vacacional',
                                      'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                      'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                  ]
                                ];
                            }
                           }
                           else if($prima_vacacional > 1 && $pagoExtraordinario > 1 && $otros > 1 && $h_extra_total < 1 && $pago_dias_vacaciones < 1 && $despensa < 1)
                           {
                              if($sueldos > $salario_minimo_quincenal)
                              {
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => $sueldos,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '038',
                                        'Code' => '038',
                                        'Description' => 'Percepción Excenta',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $otros
                                    ],
                                    [
                                        'PerceptionType' => '038',
                                        'Code' => '038',
                                        'Description' => 'Percepción Excenta',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $pagoExtraordinario
                                    ],
                                    [
                                      'PerceptionType' => '021',
                                      'Code' => '021',
                                      'Description' => 'Prima Vacacional',
                                      'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                      'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                  ]
                                ];
                              }
                              else if($sueldos <= $salario_minimo_quincenal){
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $sueldos
                                    ],
                                    [
                                        'PerceptionType' => '038',
                                        'Code' => '038',
                                        'Description' => 'Percepción Excenta',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $otros
                                    ],
                                    [
                                        'PerceptionType' => '038',
                                        'Code' => '038',
                                        'Description' => 'Percepción Excenta',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $pagoExtraordinario
                                    ],
                                    [
                                      'PerceptionType' => '021',
                                      'Code' => '021',
                                      'Description' => 'Prima Vacacional',
                                      'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                      'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                  ]
                                ];
                            }
                           }
                           // Sualdo normal + despensa + prima vacacional + Otros + pago extraordinario + horas extra
                           if($pago_dias_vacaciones < 1 && $prima_vacacional > 1 && $pagoExtraordinario > 1 && $otros > 1 && $h_extra_total > 1 && $despensa > 0)
                           {
                              if($sueldos > $salario_minimo_quincenal)
                              {
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => $sueldos,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '029',
                                        'Code' => '029',
                                        'Description' => 'Despensa',
                                        'TaxedAmount' => $despensa,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '038',
                                        'Code' => '038',
                                        'Description' => 'Percepción Excenta',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $otros
                                    ],
                                    [
                                        'PerceptionType' => '038',
                                        'Code' => '038',
                                        'Description' => 'Percepción Excenta',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $pagoExtraordinario
                                    ],
                                    [
                                        'PerceptionType' => '021',
                                        'Code' => '021',
                                        'Description' => 'Prima Vacacional',
                                        'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                        'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                    ],
                                    [
                                        'PerceptionType' => '019',
                                        'Code' => '019',
                                        'Description' => 'Horas Extra',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $h_extra_exentas,
                                        "ExtraHours"=> [
                                        [
                                          "Days"=> 2,
                                          "HoursType"=> "01",
                                          "ExtraHours"=> $h_extra_dias,
                                          "PaidAmount"=> $h_extra_exentas
                                        ]
                                      ]
                                    ]
                                ];
                              }
                              else if($sueldos <= $salario_minimo_quincenal){
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $sueldos
                                    ],
                                    [
                                        'PerceptionType' => '029',
                                        'Code' => '029',
                                        'Description' => 'Despensa',
                                        'TaxedAmount' => $despensa,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '038',
                                        'Code' => '038',
                                        'Description' => 'Percepción Excenta',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $otros
                                    ],
                                    [
                                        'PerceptionType' => '038',
                                        'Code' => '038',
                                        'Description' => 'Percepción Excenta',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $pagoExtraordinario
                                    ],
                                    [
                                        'PerceptionType' => '021',
                                        'Code' => '021',
                                        'Description' => 'Prima Vacacional',
                                        'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                        'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                    ],
                                    [
                                        'PerceptionType' => '019',
                                        'Code' => '019',
                                        'Description' => 'Horas Extra',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $h_extra_exentas,
                                        "ExtraHours"=> [
                                        [
                                          "Days"=> 2,
                                          "HoursType"=> "01",
                                          "ExtraHours"=> $h_extra_dias,
                                          "PaidAmount"=> $h_extra_exentas
                                        ]
                                      ]
                                    ]
                                ];
                            }
                           }
                           else if($pago_dias_vacaciones < 1 && $prima_vacacional > 1 && $pagoExtraordinario > 1 && $otros > 1 && $h_extra_total > 1 && $despensa < 1)
                           {
                              if($sueldos > $salario_minimo_quincenal)
                              {
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => $sueldos,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '038',
                                        'Code' => '038',
                                        'Description' => 'Percepción Excenta',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $otros
                                    ],
                                    [
                                        'PerceptionType' => '038',
                                        'Code' => '038',
                                        'Description' => 'Percepción Excenta',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $pagoExtraordinario
                                    ],
                                    [
                                        'PerceptionType' => '021',
                                        'Code' => '021',
                                        'Description' => 'Prima Vacacional',
                                        'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                        'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                    ],
                                    [
                                        'PerceptionType' => '019',
                                        'Code' => '019',
                                        'Description' => 'Horas Extra',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $h_extra_exentas,
                                        "ExtraHours"=> [
                                        [
                                          "Days"=> 2,
                                          "HoursType"=> "01",
                                          "ExtraHours"=> $h_extra_dias,
                                          "PaidAmount"=> $h_extra_exentas
                                        ]
                                      ]
                                    ]
                                ];
                              }
                              else if($sueldos <= $salario_minimo_quincenal){
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $sueldos
                                    ],
                                    [
                                        'PerceptionType' => '038',
                                        'Code' => '038',
                                        'Description' => 'Percepción Excenta',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $otros
                                    ],
                                    [
                                        'PerceptionType' => '038',
                                        'Code' => '038',
                                        'Description' => 'Percepción Excenta',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $pagoExtraordinario
                                    ],
                                    [
                                        'PerceptionType' => '021',
                                        'Code' => '021',
                                        'Description' => 'Prima Vacacional',
                                        'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                        'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                    ],
                                    [
                                        'PerceptionType' => '019',
                                        'Code' => '019',
                                        'Description' => 'Horas Extra',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $h_extra_exentas,
                                        "ExtraHours"=> [
                                        [
                                          "Days"=> 2,
                                          "HoursType"=> "01",
                                          "ExtraHours"=> $h_extra_dias,
                                          "PaidAmount"=> $h_extra_exentas
                                        ]
                                      ]
                                    ]
                                ];
                            }
                           }

            //comenzamos con las percepciones con + dias de vacaciones
             //-----------------------------------------------------------------------------------------------------------------
                         // Sualdo normal + despensa + prima vacacional Nuevo
                         if($pago_dias_vacaciones > 1 && $prima_vacacional < 1 && $h_extra_total < 1 && $pagoExtraordinario < 1 && $otros < 1 && $despensa > 0 )
                         {
                            if($sueldos > $salario_minimo_quincenal)
                            {
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => $sueldos,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '029',
                                        'Code' => '029',
                                        'Description' => 'Despensa',
                                        'TaxedAmount' => $despensa,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Dias de Vacaciones',
                                        'TaxedAmount' => $pago_dias_vacaciones,
                                        'ExemptAmount' => 0
                                    ]
                                ];
                            }
                            else if($sueldos <= $salario_minimo_quincenal){
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $sueldos
                                    ],
                                    [
                                        'PerceptionType' => '029',
                                        'Code' => '029',
                                        'Description' => 'Despensa',
                                        'TaxedAmount' => $despensa,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Dias de Vacaciones',
                                        'TaxedAmount' => $pago_dias_vacaciones,
                                        'ExemptAmount' => 0
                                    ]
                                ];
                            }
                         } else if($pago_dias_vacaciones > 1 && $prima_vacacional < 1 && $h_extra_total < 1 && $pagoExtraordinario < 1 && $otros < 1 && $despensa < 1)
                         {
                            if($sueldos > $salario_minimo_quincenal)
                            {
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => $sueldos,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Dias de Vacaciones',
                                        'TaxedAmount' => $pago_dias_vacaciones,
                                        'ExemptAmount' => 0
                                    ]
                                ];
                            }
                            else if($sueldos <= $salario_minimo_quincenal){
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $sueldos
                                    ],
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Dias de Vacaciones',
                                        'TaxedAmount' => $pago_dias_vacaciones,
                                        'ExemptAmount' => 0
                                    ]
                                ];
                            }
                         }
                        /*---------------------------------------------------------------------------------- */
                         if($pago_dias_vacaciones > 1 && $prima_vacacional > 1 && $h_extra_total < 1 && $pagoExtraordinario < 1 && $otros < 1 && $despensa > 0 )
                         {
                            if($sueldos > $salario_minimo_quincenal)
                            {
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => $sueldos,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '029',
                                        'Code' => '029',
                                        'Description' => 'Despensa',
                                        'TaxedAmount' => $despensa,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Dias de Vacaciones',
                                        'TaxedAmount' => $pago_dias_vacaciones,
                                        'ExemptAmount' => 0
                                    ], 
                                    [
                                        'PerceptionType' => '021',
                                        'Code' => '021',
                                        'Description' => 'Prima Vacacional',
                                        'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                        'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                    ]
                                ];
                            } 
                            else if($sueldos <= $salario_minimo_quincenal)
                            {
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $sueldos
                                    ],
                                    [
                                        'PerceptionType' => '029',
                                        'Code' => '029',
                                        'Description' => 'Despensa',
                                        'TaxedAmount' => $despensa,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Dias de Vacaciones',
                                        'TaxedAmount' => $pago_dias_vacaciones,
                                        'ExemptAmount' => 0
                                    ], 
                                    [
                                        'PerceptionType' => '021',
                                        'Code' => '021',
                                        'Description' => 'Prima Vacacional',
                                        'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                        'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                    ]
                                ];
                            }
                         } else if($pago_dias_vacaciones > 1 && $prima_vacacional > 1 && $h_extra_total < 1 && $pagoExtraordinario < 1 && $otros < 1 && $despensa < 1)
                         {
                            if($sueldos > $salario_minimo_quincenal)
                            {
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => $sueldos,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Dias de Vacaciones',
                                        'TaxedAmount' => $pago_dias_vacaciones,
                                        'ExemptAmount' => 0
                                    ], 
                                    [
                                        'PerceptionType' => '021',
                                        'Code' => '021',
                                        'Description' => 'Prima Vacacional',
                                        'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                        'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                    ]
                                ];
                            }
                            else if($sueldos <= $salario_minimo_quincenal){
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $sueldos
                                    ],
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Dias de Vacaciones',
                                        'TaxedAmount' => $pago_dias_vacaciones,
                                        'ExemptAmount' => 0
                                    ], 
                                    [
                                        'PerceptionType' => '021',
                                        'Code' => '021',
                                        'Description' => 'Prima Vacacional',
                                        'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                        'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                    ]
                                ];
                            }
                         }
                         
                         
                         // Sueldo normal + despensa + prima vacacional + dias de vacaciones + pago extraordinario
                         if($pago_dias_vacaciones > 1 && $prima_vacacional > 1 && $h_extra_total < 1 && $pagoExtraordinario > 1 && $otros < 1 && $despensa > 0 )
                         {
                             if($sueldos > $salario_minimo_quincenal)
                             {
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => $sueldos,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '029',
                                        'Code' => '029',
                                        'Description' => 'Despensa',
                                        'TaxedAmount' => $despensa,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Dias de Vacaciones',
                                        'TaxedAmount' => $pago_dias_vacaciones,
                                        'ExemptAmount' => 0
                                    ], 
                                    [
                                        'PerceptionType' => '021',
                                        'Code' => '021',
                                        'Description' => 'Prima Vacacional',
                                        'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                        'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                    ],
                                    [
                                        'PerceptionType' => '038',
                                        'Code' => '038',
                                        'Description' => 'Percepción Excenta',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $pagoExtraordinario
                                    ]
                                ];
                             }
                           else if($sueldos <= $salario_minimo_quincenal)
                           {
                            $PerceptionsDetails = [
                                [
                                    'PerceptionType' => '001',
                                    'Code' => '001',
                                    'Description' => 'Salario Quincenal',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $sueldos
                                ],
                                [
                                    'PerceptionType' => '029',
                                    'Code' => '029',
                                    'Description' => 'Despensa',
                                    'TaxedAmount' => $despensa,
                                    'ExemptAmount' => 0
                                ],
                                [
                                    'PerceptionType' => '001',
                                    'Code' => '001',
                                    'Description' => 'Dias de Vacaciones',
                                    'TaxedAmount' => $pago_dias_vacaciones,
                                    'ExemptAmount' => 0
                                ], 
                                [
                                    'PerceptionType' => '021',
                                    'Code' => '021',
                                    'Description' => 'Prima Vacacional',
                                    'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                    'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                ],
                                [
                                    'PerceptionType' => '038',
                                    'Code' => '038',
                                    'Description' => 'Percepción Excenta',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $pagoExtraordinario
                                ]
                            ];
                           }
                         }
                        else if($pago_dias_vacaciones > 1 && $prima_vacacional > 1 && $h_extra_total < 1 && $pagoExtraordinario > 1 && $otros < 1 && $despensa < 1 )
                         {
                            if($sueldos > $salario_minimo_quincenal)
                            {
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => $sueldos,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Dias de Vacaciones',
                                        'TaxedAmount' => $pago_dias_vacaciones,
                                        'ExemptAmount' => 0
                                    ], 
                                    [
                                        'PerceptionType' => '021',
                                        'Code' => '021',
                                        'Description' => 'Prima Vacacional',
                                        'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                        'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                    ],
                                    [
                                        'PerceptionType' => '038',
                                        'Code' => '038',
                                        'Description' => 'Percepción Excenta',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $pagoExtraordinario
                                    ]
                                ];
                            }else if($sueldos <= $salario_minimo_quincenal)
                            {
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $sueldos
                                    ],
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Dias de Vacaciones',
                                        'TaxedAmount' => $pago_dias_vacaciones,
                                        'ExemptAmount' => 0
                                    ], 
                                    [
                                        'PerceptionType' => '021',
                                        'Code' => '021',
                                        'Description' => 'Prima Vacacional',
                                        'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                        'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                    ],
                                    [
                                        'PerceptionType' => '038',
                                        'Code' => '038',
                                        'Description' => 'Percepción Excenta',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $pagoExtraordinario
                                    ]
                                ];
                            }
                         }

                            // Sueldo normal + despensa + prima vacacional + dias de vacaciones + pago extraordinario + otros
                            if($pago_dias_vacaciones > 1 && $prima_vacacional > 1 && $h_extra_total < 1 && $pagoExtraordinario > 1 && $otros > 1 && $despensa > 0 )
                            {
                               if($sueldos > $salario_minimo_quincenal)
                               {
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => $sueldos,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '029',
                                        'Code' => '029',
                                        'Description' => 'Despensa',
                                        'TaxedAmount' => $despensa,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Dias de Vacaciones',
                                        'TaxedAmount' => $pago_dias_vacaciones,
                                        'ExemptAmount' => 0
                                    ], 
                                    [
                                        'PerceptionType' => '021',
                                        'Code' => '021',
                                        'Description' => 'Prima Vacacional',
                                        'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                        'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                    ],
                                    [
                                        'PerceptionType' => '038',
                                        'Code' => '038',
                                        'Description' => 'Percepción Excenta',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $pagoExtraordinario
                                    ],
                                    [
                                        'PerceptionType' => '038',
                                        'Code' => '038',
                                        'Description' => 'Percepción Excenta',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $otros
                                    ]
                                ];
                               }
                               else if($sueldos <= $salario_minimo_quincenal)
                               {
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $sueldos
                                    ],
                                    [
                                        'PerceptionType' => '029',
                                        'Code' => '029',
                                        'Description' => 'Despensa',
                                        'TaxedAmount' => $despensa,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Dias de Vacaciones',
                                        'TaxedAmount' => $pago_dias_vacaciones,
                                        'ExemptAmount' => 0
                                    ], 
                                    [
                                        'PerceptionType' => '021',
                                        'Code' => '021',
                                        'Description' => 'Prima Vacacional',
                                        'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                        'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                    ],
                                    [
                                        'PerceptionType' => '038',
                                        'Code' => '038',
                                        'Description' => 'Percepción Excenta',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $pagoExtraordinario
                                    ],
                                    [
                                        'PerceptionType' => '038',
                                        'Code' => '038',
                                        'Description' => 'Percepción Excenta',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $otros
                                    ]
                                ];
                               }
                            }
                            else if($pago_dias_vacaciones > 1 && $prima_vacacional > 1 && $h_extra_total < 1 && $pagoExtraordinario > 1 && $otros > 1 && $despensa < 1)
                            {
                                if($sueldos > $salario_minimo_quincenal)
                                {
                                    $PerceptionsDetails = [
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Salario Quincenal',
                                            'TaxedAmount' => $sueldos,
                                            'ExemptAmount' => 0
                                        ],
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Dias de Vacaciones',
                                            'TaxedAmount' => $pago_dias_vacaciones,
                                            'ExemptAmount' => 0
                                        ], 
                                        [
                                            'PerceptionType' => '021',
                                            'Code' => '021',
                                            'Description' => 'Prima Vacacional',
                                            'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                            'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                        ],
                                        [
                                            'PerceptionType' => '038',
                                            'Code' => '038',
                                            'Description' => 'Percepción Excenta',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $pagoExtraordinario
                                        ],
                                        [
                                            'PerceptionType' => '038',
                                            'Code' => '038',
                                            'Description' => 'Percepción Excenta',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $otros
                                        ]
                                    ];
                                }
                                else if($sueldos <= $salario_minimo_quincenal){
                                    $PerceptionsDetails = [
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Salario Quincenal',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $sueldos
                                        ],
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Dias de Vacaciones',
                                            'TaxedAmount' => $pago_dias_vacaciones,
                                            'ExemptAmount' => 0
                                        ], 
                                        [
                                            'PerceptionType' => '021',
                                            'Code' => '021',
                                            'Description' => 'Prima Vacacional',
                                            'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                            'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                        ],
                                        [
                                            'PerceptionType' => '038',
                                            'Code' => '038',
                                            'Description' => 'Percepción Excenta',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $pagoExtraordinario
                                        ],
                                        [
                                            'PerceptionType' => '038',
                                            'Code' => '038',
                                            'Description' => 'Percepción Excenta',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $otros
                                        ]
                                    ];
                                }
                            }
                              // Sueldo normal + despensa + prima vacacional + dias de vacaciones + pago extraordinario + otros + horas extra
                              if($pago_dias_vacaciones > 1 && $prima_vacacional > 1 && $h_extra_total > 1 && $pagoExtraordinario > 1 && $otros > 1 && $despensa > 0 )
                              {
                                 if($sueldos > $salario_minimo_quincenal)
                                 {
                                    $PerceptionsDetails = [
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Salario Quincenal',
                                            'TaxedAmount' => $sueldos,
                                            'ExemptAmount' => 0
                                        ],
                                        [
                                            'PerceptionType' => '029',
                                            'Code' => '029',
                                            'Description' => 'Despensa',
                                            'TaxedAmount' => $despensa,
                                            'ExemptAmount' => 0
                                        ],
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Dias de Vacaciones',
                                            'TaxedAmount' => $pago_dias_vacaciones,
                                            'ExemptAmount' => 0
                                        ], 
                                        [
                                            'PerceptionType' => '021',
                                            'Code' => '021',
                                            'Description' => 'Prima Vacacional',
                                            'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                            'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                        ],
                                        [
                                            'PerceptionType' => '038',
                                            'Code' => '038',
                                            'Description' => 'Percepción Excenta',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $pagoExtraordinario
                                        ],
                                        [
                                            'PerceptionType' => '038',
                                            'Code' => '038',
                                            'Description' => 'Percepción Excenta',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $otros
                                        ],
                                        [
                                            'PerceptionType' => '019',
                                            'Code' => '019',
                                            'Description' => 'Horas Extra',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $h_extra_exentas,
                                            "ExtraHours"=> [
                                            [
                                              "Days"=> 2,
                                              "HoursType"=> "01",
                                              "ExtraHours"=> $h_extra_dias,
                                              "PaidAmount"=> $h_extra_exentas
                                            ]
                                          ]
                                        ]
                                    ];
                                 }
                                 else if($sueldos <= $salario_minimo_quincenal){
                                    $PerceptionsDetails = [
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Salario Quincenal',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $sueldos
                                        ],
                                        [
                                            'PerceptionType' => '029',
                                            'Code' => '029',
                                            'Description' => 'Despensa',
                                            'TaxedAmount' => $despensa,
                                            'ExemptAmount' => 0
                                        ],
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Dias de Vacaciones',
                                            'TaxedAmount' => $pago_dias_vacaciones,
                                            'ExemptAmount' => 0
                                        ], 
                                        [
                                            'PerceptionType' => '021',
                                            'Code' => '021',
                                            'Description' => 'Prima Vacacional',
                                            'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                            'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                        ],
                                        [
                                            'PerceptionType' => '038',
                                            'Code' => '038',
                                            'Description' => 'Percepción Excenta',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $pagoExtraordinario
                                        ],
                                        [
                                            'PerceptionType' => '038',
                                            'Code' => '038',
                                            'Description' => 'Percepción Excenta',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $otros
                                        ],
                                        [
                                            'PerceptionType' => '019',
                                            'Code' => '019',
                                            'Description' => 'Horas Extra',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $h_extra_exentas,
                                            "ExtraHours"=> [
                                            [
                                              "Days"=> 2,
                                              "HoursType"=> "01",
                                              "ExtraHours"=> $h_extra_dias,
                                              "PaidAmount"=> $h_extra_exentas
                                            ]
                                          ]
                                        ]
                                    ];
                                }
                              }
                              else if($pago_dias_vacaciones > 1 && $prima_vacacional > 1 && $h_extra_total > 1 && $pagoExtraordinario > 1 && $otros > 1 && $despensa < 1)
                              {
                                if($sueldos > $salario_minimo_quincenal)
                                {
                                    $PerceptionsDetails = [
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Salario Quincenal',
                                            'TaxedAmount' => $sueldos,
                                            'ExemptAmount' => 0
                                        ],
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Dias de Vacaciones',
                                            'TaxedAmount' => $pago_dias_vacaciones,
                                            'ExemptAmount' => 0
                                        ], 
                                        [
                                            'PerceptionType' => '021',
                                            'Code' => '021',
                                            'Description' => 'Prima Vacacional',
                                            'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                            'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                        ],
                                        [
                                            'PerceptionType' => '038',
                                            'Code' => '038',
                                            'Description' => 'Percepción Excenta',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $pagoExtraordinario
                                        ],
                                        [
                                            'PerceptionType' => '038',
                                            'Code' => '038',
                                            'Description' => 'Percepción Excenta',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $otros
                                        ],
                                        [
                                            'PerceptionType' => '019',
                                            'Code' => '019',
                                            'Description' => 'Horas Extra',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $h_extra_exentas,
                                            "ExtraHours"=> [
                                            [
                                              "Days"=> 2,
                                              "HoursType"=> "01",
                                              "ExtraHours"=> $h_extra_dias,
                                              "PaidAmount"=> $h_extra_exentas
                                            ]
                                          ]
                                        ]
                                    ];
                                }
                                else if($sueldos <= $salario_minimo_quincenal){
                                    $PerceptionsDetails = [
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Salario Quincenal',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $sueldos
                                        ],
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Dias de Vacaciones',
                                            'TaxedAmount' => $pago_dias_vacaciones,
                                            'ExemptAmount' => 0
                                        ], 
                                        [
                                            'PerceptionType' => '021',
                                            'Code' => '021',
                                            'Description' => 'Prima Vacacional',
                                            'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                            'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                        ],
                                        [
                                            'PerceptionType' => '038',
                                            'Code' => '038',
                                            'Description' => 'Percepción Excenta',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $pagoExtraordinario
                                        ],
                                        [
                                            'PerceptionType' => '038',
                                            'Code' => '038',
                                            'Description' => 'Percepción Excenta',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $otros
                                        ],
                                        [
                                            'PerceptionType' => '019',
                                            'Code' => '019',
                                            'Description' => 'Horas Extra',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $h_extra_exentas,
                                            "ExtraHours"=> [
                                            [
                                              "Days"=> 2,
                                              "HoursType"=> "01",
                                              "ExtraHours"=> $h_extra_dias,
                                              "PaidAmount"=> $h_extra_exentas
                                            ]
                                          ]
                                        ]
                                    ];
                                }
                              }
                                // Sueldo normal + despensa + dias de vacaciones  + horas extra
                                if($pago_dias_vacaciones > 1 && $prima_vacacional < 1 && $h_extra_total > 1 && $pagoExtraordinario < 1 && $otros < 1 && $despensa > 0 )
                                {
                                    if($sueldos > $salario_minimo_quincenal)
                                    {
                                        $PerceptionsDetails = [
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Salario Quincenal',
                                                'TaxedAmount' => $sueldos,
                                                'ExemptAmount' => 0
                                            ],
                                            [
                                                'PerceptionType' => '029',
                                                'Code' => '029',
                                                'Description' => 'Despensa',
                                                'TaxedAmount' => $despensa,
                                                'ExemptAmount' => 0
                                            ],
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Dias de Vacaciones',
                                                'TaxedAmount' => $pago_dias_vacaciones,
                                                'ExemptAmount' => 0
                                            ], 
                                            [
                                                'PerceptionType' => '019',
                                                'Code' => '019',
                                                'Description' => 'Horas Extra',
                                                'TaxedAmount' => 0,
                                                'ExemptAmount' => $h_extra_exentas,
                                                "ExtraHours"=> [
                                                [
                                                  "Days"=> 2,
                                                  "HoursType"=> "01",
                                                  "ExtraHours"=> $h_extra_dias,
                                                  "PaidAmount"=> $h_extra_exentas
                                                ]
                                              ]
                                            ]
                                        ];
                                    }
                                    else if($sueldos <= $salario_minimo_quincenal){
                                        $PerceptionsDetails = [
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Salario Quincenal',
                                                'TaxedAmount' => 0,
                                                'ExemptAmount' => $sueldos
                                            ],
                                            [
                                                'PerceptionType' => '029',
                                                'Code' => '029',
                                                'Description' => 'Despensa',
                                                'TaxedAmount' => $despensa,
                                                'ExemptAmount' => 0
                                            ],
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Dias de Vacaciones',
                                                'TaxedAmount' => $pago_dias_vacaciones,
                                                'ExemptAmount' => 0
                                            ], 
                                            [
                                                'PerceptionType' => '019',
                                                'Code' => '019',
                                                'Description' => 'Horas Extra',
                                                'TaxedAmount' => 0,
                                                'ExemptAmount' => $h_extra_exentas,
                                                "ExtraHours"=> [
                                                [
                                                  "Days"=> 2,
                                                  "HoursType"=> "01",
                                                  "ExtraHours"=> $h_extra_dias,
                                                  "PaidAmount"=> $h_extra_exentas
                                                ]
                                              ]
                                            ]
                                        ];
                                    }
                                }
                                else if($pago_dias_vacaciones > 1 && $prima_vacacional < 1 && $h_extra_total > 1 && $pagoExtraordinario < 1 && $otros < 1 && $despensa < 1)
                                {
                                    if($sueldos > $salario_minimo_quincenal)
                                    {
                                        $PerceptionsDetails = [
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Salario Quincenal',
                                                'TaxedAmount' => $sueldos,
                                                'ExemptAmount' => 0
                                            ],
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Dias de Vacaciones',
                                                'TaxedAmount' => $pago_dias_vacaciones,
                                                'ExemptAmount' => 0
                                            ], 
                                            [
                                                'PerceptionType' => '019',
                                                'Code' => '019',
                                                'Description' => 'Horas Extra',
                                                'TaxedAmount' => 0,
                                                'ExemptAmount' => $h_extra_exentas,
                                                "ExtraHours"=> [
                                                [
                                                  "Days"=> 2,
                                                  "HoursType"=> "01",
                                                  "ExtraHours"=> $h_extra_dias,
                                                  "PaidAmount"=> $h_extra_exentas
                                                ]
                                              ]
                                            ]
                                        ];
                                    }
                                    else if($sueldos <= $salario_minimo_quincenal){
                                        $PerceptionsDetails = [
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Salario Quincenal',
                                                'TaxedAmount' => 0,
                                                'ExemptAmount' => $sueldos
                                            ],
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Dias de Vacaciones',
                                                'TaxedAmount' => $pago_dias_vacaciones,
                                                'ExemptAmount' => 0
                                            ], 
                                            [
                                                'PerceptionType' => '019',
                                                'Code' => '019',
                                                'Description' => 'Horas Extra',
                                                'TaxedAmount' => 0,
                                                'ExemptAmount' => $h_extra_exentas,
                                                "ExtraHours"=> [
                                                [
                                                  "Days"=> 2,
                                                  "HoursType"=> "01",
                                                  "ExtraHours"=> $h_extra_dias,
                                                  "PaidAmount"=> $h_extra_exentas
                                                ]
                                              ]
                                            ]
                                        ];
                                    }
                                }
            
                                // Sueldo normal + despensa + dias de vacaciones  + Pago extraordinario
                                if($pago_dias_vacaciones > 1 && $prima_vacacional < 1 && $h_extra_total < 1 && $pagoExtraordinario > 1 && $otros < 1 && $despensa > 0 )
                                {
                                    if($sueldos > $salario_minimo_quincenal)
                                    {
                                        $PerceptionsDetails = [
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Salario Quincenal',
                                                'TaxedAmount' => $sueldos,
                                                'ExemptAmount' => 0
                                            ],
                                            [
                                                'PerceptionType' => '029',
                                                'Code' => '029',
                                                'Description' => 'Despensa',
                                                'TaxedAmount' => $despensa,
                                                'ExemptAmount' => 0
                                            ],
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Dias de Vacaciones',
                                                'TaxedAmount' => $pago_dias_vacaciones,
                                                'ExemptAmount' => 0
                                            ], 
                                            [
                                             'PerceptionType' => '038',
                                             'Code' => '038',
                                             'Description' => 'Percepción Excenta',
                                             'TaxedAmount' => 0,
                                             'ExemptAmount' => $pagoExtraordinario
                                            ]
                                        ]; 
                                    }
                                    else if($sueldos <= $salario_minimo_quincenal)
                                        {
                                            $PerceptionsDetails = [
                                                [
                                                    'PerceptionType' => '001',
                                                    'Code' => '001',
                                                    'Description' => 'Salario Quincenal',
                                                    'TaxedAmount' => 0,
                                                    'ExemptAmount' => $sueldos
                                                ],
                                                [
                                                    'PerceptionType' => '029',
                                                    'Code' => '029',
                                                    'Description' => 'Despensa',
                                                    'TaxedAmount' => $despensa,
                                                    'ExemptAmount' => 0
                                                ],
                                                [
                                                    'PerceptionType' => '001',
                                                    'Code' => '001',
                                                    'Description' => 'Dias de Vacaciones',
                                                    'TaxedAmount' => $pago_dias_vacaciones,
                                                    'ExemptAmount' => 0
                                                ], 
                                                [
                                                 'PerceptionType' => '038',
                                                 'Code' => '038',
                                                 'Description' => 'Percepción Excenta',
                                                 'TaxedAmount' => 0,
                                                 'ExemptAmount' => $pagoExtraordinario
                                                ]
                                            ];
                                        }
                                }
                                else if($pago_dias_vacaciones > 1 && $prima_vacacional < 1 && $h_extra_total < 1 && $pagoExtraordinario > 1 && $otros < 1 && $despensa < 1)
                                {
                                    if($sueldos > $salario_minimo_quincenal)
                                    {
                                        $PerceptionsDetails = [
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Salario Quincenal',
                                                'TaxedAmount' => $sueldos,
                                                'ExemptAmount' => 0
                                            ],
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Dias de Vacaciones',
                                                'TaxedAmount' => $pago_dias_vacaciones,
                                                'ExemptAmount' => 0
                                            ], 
                                            [
                                             'PerceptionType' => '038',
                                             'Code' => '038',
                                             'Description' => 'Percepción Excenta',
                                             'TaxedAmount' => 0,
                                             'ExemptAmount' => $pagoExtraordinario
                                            ]
                                        ];
                                    } 
                                    else if($sueldos <= $salario_minimo_quincenal)
                                    {
                                        $PerceptionsDetails = [
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Salario Quincenal',
                                                'TaxedAmount' => 0,
                                                'ExemptAmount' => $sueldos
                                            ],
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Dias de Vacaciones',
                                                'TaxedAmount' => $pago_dias_vacaciones,
                                                'ExemptAmount' => 0
                                            ], 
                                            [
                                             'PerceptionType' => '038',
                                             'Code' => '038',
                                             'Description' => 'Percepción Excenta',
                                             'TaxedAmount' => 0,
                                             'ExemptAmount' => $pagoExtraordinario
                                            ]
                                        ];
                                    }
                                }
                                  // Sueldo normal + despensa + dias de vacaciones  + otros
                                  if($pago_dias_vacaciones > 1 && $prima_vacacional < 1 && $h_extra_total < 1 && $pagoExtraordinario < 1 && $otros > 1 && $despensa > 0 )
                                  {
                                    if($sueldos > $salario_minimo_quincenal)
                                    {
                                        $PerceptionsDetails = [
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Salario Quincenal',
                                                'TaxedAmount' => $sueldos,
                                                'ExemptAmount' => 0
                                            ],
                                            [
                                                'PerceptionType' => '029',
                                                'Code' => '029',
                                                'Description' => 'Despensa',
                                                'TaxedAmount' => $despensa,
                                                'ExemptAmount' => 0
                                            ],
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Dias de Vacaciones',
                                                'TaxedAmount' => $pago_dias_vacaciones,
                                                'ExemptAmount' => 0
                                            ], 
                                            [
                                               'PerceptionType' => '038',
                                               'Code' => '038',
                                               'Description' => 'Percepción Excenta',
                                               'TaxedAmount' => 0,
                                               'ExemptAmount' => $otros
                                           ]
                                        ];
                                    } else if($sueldos <= $salario_minimo_quincenal){
                                        $PerceptionsDetails = [
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Salario Quincenal',
                                                'TaxedAmount' => 0,
                                                'ExemptAmount' => $sueldos
                                            ],
                                            [
                                                'PerceptionType' => '029',
                                                'Code' => '029',
                                                'Description' => 'Despensa',
                                                'TaxedAmount' => $despensa,
                                                'ExemptAmount' => 0
                                            ],
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Dias de Vacaciones',
                                                'TaxedAmount' => $pago_dias_vacaciones,
                                                'ExemptAmount' => 0
                                            ], 
                                            [
                                               'PerceptionType' => '038',
                                               'Code' => '038',
                                               'Description' => 'Percepción Excenta',
                                               'TaxedAmount' => 0,
                                               'ExemptAmount' => $otros
                                           ]
                                        ];
                                  }
                                  }
                                  else if($pago_dias_vacaciones > 1 && $prima_vacacional < 1 && $h_extra_total < 1 && $pagoExtraordinario < 1 && $otros > 1 && $despensa < 1)
                                  {
                                    if($sueldos > $salario_minimo_quincenal)
                                    {
                                        $PerceptionsDetails = [
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Salario Quincenal',
                                                'TaxedAmount' => $sueldos,
                                                'ExemptAmount' => 0
                                            ],
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Dias de Vacaciones',
                                                'TaxedAmount' => $pago_dias_vacaciones,
                                                'ExemptAmount' => 0
                                            ], 
                                            [
                                               'PerceptionType' => '038',
                                               'Code' => '038',
                                               'Description' => 'Percepción Excenta',
                                               'TaxedAmount' => 0,
                                               'ExemptAmount' => $otros
                                           ]
                                        ];
                                    }
                                    else if($sueldos <= $salario_minimo_quincenal){
                                        $PerceptionsDetails = [
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Salario Quincenal',
                                                'TaxedAmount' => 0,
                                                'ExemptAmount' => $sueldos
                                            ],
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Dias de Vacaciones',
                                                'TaxedAmount' => $pago_dias_vacaciones,
                                                'ExemptAmount' => 0
                                            ], 
                                            [
                                               'PerceptionType' => '038',
                                               'Code' => '038',
                                               'Description' => 'Percepción Excenta',
                                               'TaxedAmount' => 0,
                                               'ExemptAmount' => $otros
                                           ]
                                        ];
                                    }
                                  }
                                   // Sueldo normal + despensa + prima vacacional + Pago extraordinario
                                  if($pago_dias_vacaciones < 1 && $prima_vacacional > 1 && $h_extra_total < 1 && $pagoExtraordinario > 1 && $otros < 1 && $despensa > 0 )
                                  {
                                    if($sueldos > $salario_minimo_quincenal)
                                    {
                                        $PerceptionsDetails = [
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Salario Quincenal',
                                                'TaxedAmount' => $sueldos,
                                                'ExemptAmount' => 0
                                            ],
                                            [
                                                'PerceptionType' => '029',
                                                'Code' => '029',
                                                'Description' => 'Despensa',
                                                'TaxedAmount' => $despensa,
                                                'ExemptAmount' => 0
                                            ],
                                            [
                                               'PerceptionType' => '021',
                                               'Code' => '021',
                                               'Description' => 'Prima Vacacional',
                                               'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                               'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                           ],
                                           [
                                               'PerceptionType' => '038',
                                               'Code' => '038',
                                               'Description' => 'Percepción Excenta',
                                               'TaxedAmount' => 0,
                                               'ExemptAmount' => $pagoExtraordinario
                                           ]
                                        ];
                                    }
                                    else if($sueldos <= $salario_minimo_quincenal){
                                        $PerceptionsDetails = [
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Salario Quincenal',
                                                'TaxedAmount' => 0,
                                                'ExemptAmount' => $sueldos
                                            ],
                                            [
                                                'PerceptionType' => '029',
                                                'Code' => '029',
                                                'Description' => 'Despensa',
                                                'TaxedAmount' => $despensa,
                                                'ExemptAmount' => 0
                                            ],
                                            [
                                               'PerceptionType' => '021',
                                               'Code' => '021',
                                               'Description' => 'Prima Vacacional',
                                               'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                               'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                           ],
                                           [
                                               'PerceptionType' => '038',
                                               'Code' => '038',
                                               'Description' => 'Percepción Excenta',
                                               'TaxedAmount' => 0,
                                               'ExemptAmount' => $pagoExtraordinario
                                           ]
                                        ];
                                    }
                                  } else if($pago_dias_vacaciones < 1 && $prima_vacacional > 1 && $h_extra_total < 1 && $pagoExtraordinario > 1 && $otros < 1 && $despensa < 1)
                                  {
                                    if($sueldos > $salario_minimo_quincenal)
                                    {
                                        $PerceptionsDetails = [
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Salario Quincenal',
                                                'TaxedAmount' => $sueldos,
                                                'ExemptAmount' => 0
                                            ],
                                            [
                                               'PerceptionType' => '021',
                                               'Code' => '021',
                                               'Description' => 'Prima Vacacional',
                                               'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                               'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                           ],
                                           [
                                               'PerceptionType' => '038',
                                               'Code' => '038',
                                               'Description' => 'Percepción Excenta',
                                               'TaxedAmount' => 0,
                                               'ExemptAmount' => $pagoExtraordinario
                                           ]
                                        ];
                                    } else if($sueldos <= $salario_minimo_quincenal){
                                        $PerceptionsDetails = [
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Salario Quincenal',
                                                'TaxedAmount' => 0,
                                                'ExemptAmount' => $sueldos
                                            ],
                                            [
                                               'PerceptionType' => '021',
                                               'Code' => '021',
                                               'Description' => 'Prima Vacacional',
                                               'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                               'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                           ],
                                           [
                                               'PerceptionType' => '038',
                                               'Code' => '038',
                                               'Description' => 'Percepción Excenta',
                                               'TaxedAmount' => 0,
                                               'ExemptAmount' => $pagoExtraordinario
                                           ]
                                        ];
                                    }
                                  }

                                 // Sueldo normal + despensa + prima vacacional + Pago extraordinario
                                  if($pago_dias_vacaciones < 1 && $prima_vacacional < 1 && $h_extra_total < 1 && $pagoExtraordinario > 1 && $otros > 1 && $despensa > 0 )
                                  {
                                    if($sueldos > $salario_minimo_quincenal)
                                    {
                                        $PerceptionsDetails = [
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Salario Quincenal',
                                                'TaxedAmount' => $sueldos,
                                                'ExemptAmount' => 0
                                            ],
                                            [
                                                'PerceptionType' => '029',
                                                'Code' => '029',
                                                'Description' => 'Despensa',
                                                'TaxedAmount' => $despensa,
                                                'ExemptAmount' => 0
                                            ],
                                            [
                                               'PerceptionType' => '038',
                                               'Code' => '038',
                                               'Description' => 'Percepción Excenta',
                                               'TaxedAmount' => 0,
                                               'ExemptAmount' => $otros
                                           ],
                                           [
                                               'PerceptionType' => '038',
                                               'Code' => '038',
                                               'Description' => 'Percepción Excenta',
                                               'TaxedAmount' => 0,
                                               'ExemptAmount' => $pagoExtraordinario
                                           ]
                                        ];
                                    } 
                                    else if($sueldos <= $salario_minimo_quincenal){
                                        $PerceptionsDetails = [
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Salario Quincenal',
                                                'TaxedAmount' => 0,
                                                'ExemptAmount' => $sueldos
                                            ],
                                            [
                                                'PerceptionType' => '029',
                                                'Code' => '029',
                                                'Description' => 'Despensa',
                                                'TaxedAmount' => $despensa,
                                                'ExemptAmount' => 0
                                            ],
                                            [
                                               'PerceptionType' => '038',
                                               'Code' => '038',
                                               'Description' => 'Percepción Excenta',
                                               'TaxedAmount' => 0,
                                               'ExemptAmount' => $otros
                                           ],
                                           [
                                               'PerceptionType' => '038',
                                               'Code' => '038',
                                               'Description' => 'Percepción Excenta',
                                               'TaxedAmount' => 0,
                                               'ExemptAmount' => $pagoExtraordinario
                                           ]
                                        ];
                                    }
                                  }
                                  else if($pago_dias_vacaciones < 1 && $prima_vacacional < 1 && $h_extra_total < 1 && $pagoExtraordinario > 1 && $otros > 1 && $despensa < 1)
                                  {
                                    if($sueldos > $salario_minimo_quincenal)
                                    {
                                        $PerceptionsDetails = [
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Salario Quincenal',
                                                'TaxedAmount' => $sueldos,
                                                'ExemptAmount' => 0
                                            ],
                                            [
                                               'PerceptionType' => '038',
                                               'Code' => '038',
                                               'Description' => 'Percepción Excenta',
                                               'TaxedAmount' => 0,
                                               'ExemptAmount' => $otros
                                           ],
                                           [
                                               'PerceptionType' => '038',
                                               'Code' => '038',
                                               'Description' => 'Percepción Excenta',
                                               'TaxedAmount' => 0,
                                               'ExemptAmount' => $pagoExtraordinario
                                           ]
                                        ];
                                    }
                                    else if($sueldos <= $salario_minimo_quincenal){
                                        $PerceptionsDetails = [
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Salario Quincenal',
                                                'TaxedAmount' => 0,
                                                'ExemptAmount' => $sueldos
                                            ],
                                            [
                                               'PerceptionType' => '038',
                                               'Code' => '038',
                                               'Description' => 'Percepción Excenta',
                                               'TaxedAmount' => 0,
                                               'ExemptAmount' => $otros
                                           ],
                                           [
                                               'PerceptionType' => '038',
                                               'Code' => '038',
                                               'Description' => 'Percepción Excenta',
                                               'TaxedAmount' => 0,
                                               'ExemptAmount' => $pagoExtraordinario
                                           ]
                                        ];
                                    }
                                  }
                              //-------------------------------- Ternas --------------------------------
                              // Sueldo normal + despensa + dias de vacaciones + prima vacacional + horas extra
                              if($pago_dias_vacaciones > 1 && $prima_vacacional > 1 && $h_extra_total > 1 && $pagoExtraordinario < 1 && $otros < 1 && $despensa > 0 )
                              {
                                if($sueldos > $salario_minimo_quincenal)
                                {
                                    $PerceptionsDetails = [
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Salario Quincenal',
                                            'TaxedAmount' => $sueldos,
                                            'ExemptAmount' => 0
                                        ],
                                        [
                                            'PerceptionType' => '029',
                                            'Code' => '029',
                                            'Description' => 'Despensa',
                                            'TaxedAmount' => $despensa,
                                            'ExemptAmount' => 0
                                        ],
                                        [
                                           'PerceptionType' => '001',
                                           'Code' => '001',
                                           'Description' => 'Dias de Vacaciones',
                                           'TaxedAmount' => $pago_dias_vacaciones,
                                           'ExemptAmount' => 0
                                       ], 
                                       [
                                           'PerceptionType' => '021',
                                           'Code' => '021',
                                           'Description' => 'Prima Vacacional',
                                           'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                           'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                       ],
                                       [
                                           'PerceptionType' => '019',
                                           'Code' => '019',
                                           'Description' => 'Horas Extra',
                                           'TaxedAmount' => 0,
                                           'ExemptAmount' => $h_extra_exentas,
                                           "ExtraHours"=> [
                                           [
                                             "Days"=> 2,
                                             "HoursType"=> "01",
                                             "ExtraHours"=> $h_extra_dias,
                                             "PaidAmount"=> $h_extra_exentas
                                           ]
                                         ]
                                       ]
                                    ];
                                }
                                else if($sueldos <= $salario_minimo_quincenal){
                                    $PerceptionsDetails = [
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Salario Quincenal',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $sueldos
                                        ],
                                        [
                                            'PerceptionType' => '029',
                                            'Code' => '029',
                                            'Description' => 'Despensa',
                                            'TaxedAmount' => $despensa,
                                            'ExemptAmount' => 0
                                        ],
                                        [
                                           'PerceptionType' => '001',
                                           'Code' => '001',
                                           'Description' => 'Dias de Vacaciones',
                                           'TaxedAmount' => $pago_dias_vacaciones,
                                           'ExemptAmount' => 0
                                       ], 
                                       [
                                           'PerceptionType' => '021',
                                           'Code' => '021',
                                           'Description' => 'Prima Vacacional',
                                           'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                           'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                       ],
                                       [
                                           'PerceptionType' => '019',
                                           'Code' => '019',
                                           'Description' => 'Horas Extra',
                                           'TaxedAmount' => 0,
                                           'ExemptAmount' => $h_extra_exentas,
                                           "ExtraHours"=> [
                                           [
                                             "Days"=> 2,
                                             "HoursType"=> "01",
                                             "ExtraHours"=> $h_extra_dias,
                                             "PaidAmount"=> $h_extra_exentas
                                           ]
                                         ]
                                       ]
                                    ];
                                }
                              }
                              else if($pago_dias_vacaciones > 1 && $prima_vacacional > 1 && $h_extra_total > 1 && $pagoExtraordinario < 1 && $otros < 1 && $despensa < 1)
                              {
                                if($sueldos > $salario_minimo_quincenal)
                                {
                                    $PerceptionsDetails = [
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Salario Quincenal',
                                            'TaxedAmount' => $sueldos,
                                            'ExemptAmount' => 0
                                        ],
                                        [
                                           'PerceptionType' => '001',
                                           'Code' => '001',
                                           'Description' => 'Dias de Vacaciones',
                                           'TaxedAmount' => $pago_dias_vacaciones,
                                           'ExemptAmount' => 0
                                       ], 
                                       [
                                           'PerceptionType' => '021',
                                           'Code' => '021',
                                           'Description' => 'Prima Vacacional',
                                           'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                           'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                       ],
                                       [
                                           'PerceptionType' => '019',
                                           'Code' => '019',
                                           'Description' => 'Horas Extra',
                                           'TaxedAmount' => 0,
                                           'ExemptAmount' => $h_extra_exentas,
                                           "ExtraHours"=> [
                                           [
                                             "Days"=> 2,
                                             "HoursType"=> "01",
                                             "ExtraHours"=> $h_extra_dias,
                                             "PaidAmount"=> $h_extra_exentas
                                           ]
                                         ]
                                       ]
                                    ];
                                }
                                else if($sueldos <= $salario_minimo_quincenal){
                                    $PerceptionsDetails = [
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Salario Quincenal',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $sueldos
                                        ],
                                        [
                                           'PerceptionType' => '001',
                                           'Code' => '001',
                                           'Description' => 'Dias de Vacaciones',
                                           'TaxedAmount' => $pago_dias_vacaciones,
                                           'ExemptAmount' => 0
                                       ], 
                                       [
                                           'PerceptionType' => '021',
                                           'Code' => '021',
                                           'Description' => 'Prima Vacacional',
                                           'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                           'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                       ],
                                       [
                                           'PerceptionType' => '019',
                                           'Code' => '019',
                                           'Description' => 'Horas Extra',
                                           'TaxedAmount' => 0,
                                           'ExemptAmount' => $h_extra_exentas,
                                           "ExtraHours"=> [
                                           [
                                             "Days"=> 2,
                                             "HoursType"=> "01",
                                             "ExtraHours"=> $h_extra_dias,
                                             "PaidAmount"=> $h_extra_exentas
                                           ]
                                         ]
                                       ]
                                    ];
                                }
                              }
                              // Sueldo normal + despensa + dias de vacaciones + prima vacacional + otros
                              if($pago_dias_vacaciones > 1 && $prima_vacacional > 1 && $h_extra_total < 1 && $pagoExtraordinario < 1 && $otros > 1 && $despensa > 0 )
                              {
                                if($sueldos > $salario_minimo_quincenal)
                                {
                                    $PerceptionsDetails = [
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Salario Quincenal',
                                            'TaxedAmount' => $sueldos,
                                            'ExemptAmount' => 0
                                        ],
                                        [
                                            'PerceptionType' => '029',
                                            'Code' => '029',
                                            'Description' => 'Despensa',
                                            'TaxedAmount' => $despensa,
                                            'ExemptAmount' => 0
                                        ],
                                        [
                                           'PerceptionType' => '001',
                                           'Code' => '001',
                                           'Description' => 'Dias de Vacaciones',
                                           'TaxedAmount' => $pago_dias_vacaciones,
                                           'ExemptAmount' => 0
                                       ], 
                                       [
                                           'PerceptionType' => '021',
                                           'Code' => '021',
                                           'Description' => 'Prima Vacacional',
                                           'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                           'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                       ],
                                       [
                                           'PerceptionType' => '038',
                                           'Code' => '038',
                                           'Description' => 'Percepción Excenta',
                                           'TaxedAmount' => 0,
                                           'ExemptAmount' => $otros
                                       ]
                                    ];
                                }
                                else if($sueldos <= $salario_minimo_quincenal){
                                    $PerceptionsDetails = [
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Salario Quincenal',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $sueldos
                                        ],
                                        [
                                            'PerceptionType' => '029',
                                            'Code' => '029',
                                            'Description' => 'Despensa',
                                            'TaxedAmount' => $despensa,
                                            'ExemptAmount' => 0
                                        ],
                                        [
                                           'PerceptionType' => '001',
                                           'Code' => '001',
                                           'Description' => 'Dias de Vacaciones',
                                           'TaxedAmount' => $pago_dias_vacaciones,
                                           'ExemptAmount' => 0
                                       ], 
                                       [
                                           'PerceptionType' => '021',
                                           'Code' => '021',
                                           'Description' => 'Prima Vacacional',
                                           'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                           'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                       ],
                                       [
                                           'PerceptionType' => '038',
                                           'Code' => '038',
                                           'Description' => 'Percepción Excenta',
                                           'TaxedAmount' => 0,
                                           'ExemptAmount' => $otros
                                       ]
                                    ];
                                }
                              }  
                              else if($pago_dias_vacaciones > 1 && $prima_vacacional > 1 && $h_extra_total < 1 && $pagoExtraordinario < 1 && $otros > 1 && $despensa < 1)
                              {
                                if($sueldos > $salario_minimo_quincenal)
                                {
                                    $PerceptionsDetails = [
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Salario Quincenal',
                                            'TaxedAmount' => $sueldos,
                                            'ExemptAmount' => 0
                                        ],
                                        [
                                           'PerceptionType' => '001',
                                           'Code' => '001',
                                           'Description' => 'Dias de Vacaciones',
                                           'TaxedAmount' => $pago_dias_vacaciones,
                                           'ExemptAmount' => 0
                                       ], 
                                       [
                                           'PerceptionType' => '021',
                                           'Code' => '021',
                                           'Description' => 'Prima Vacacional',
                                           'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                           'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                       ],
                                       [
                                           'PerceptionType' => '038',
                                           'Code' => '038',
                                           'Description' => 'Percepción Excenta',
                                           'TaxedAmount' => 0,
                                           'ExemptAmount' => $otros
                                       ]
                                    ];  
                                }
                                else if($sueldos <= $salario_minimo_quincenal){
                                    $PerceptionsDetails = [
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Salario Quincenal',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $sueldos
                                        ],
                                        [
                                           'PerceptionType' => '001',
                                           'Code' => '001',
                                           'Description' => 'Dias de Vacaciones',
                                           'TaxedAmount' => $pago_dias_vacaciones,
                                           'ExemptAmount' => 0
                                       ], 
                                       [
                                           'PerceptionType' => '021',
                                           'Code' => '021',
                                           'Description' => 'Prima Vacacional',
                                           'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                           'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                       ],
                                       [
                                           'PerceptionType' => '038',
                                           'Code' => '038',
                                           'Description' => 'Percepción Excenta',
                                           'TaxedAmount' => 0,
                                           'ExemptAmount' => $otros
                                       ]
                                    ];
                                }
                              }
                             // Sueldo normal + despensa + dias de vacaciones + horas extra + pago extraordinario
                              if($pago_dias_vacaciones > 1 && $prima_vacacional < 1 && $h_extra_total > 1 && $pagoExtraordinario > 1 && $otros < 1 && $despensa > 0 )
                              {
                                if($sueldos > $salario_minimo_quincenal)
                                {
                                    $PerceptionsDetails = [
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Salario Quincenal',
                                            'TaxedAmount' => $sueldos,
                                            'ExemptAmount' => 0
                                        ],
                                        [
                                            'PerceptionType' => '029',
                                            'Code' => '029',
                                            'Description' => 'Despensa',
                                            'TaxedAmount' => $despensa,
                                            'ExemptAmount' => 0
                                        ],
                                        [
                                           'PerceptionType' => '001',
                                           'Code' => '001',
                                           'Description' => 'Dias de Vacaciones',
                                           'TaxedAmount' => $pago_dias_vacaciones,
                                           'ExemptAmount' => 0
                                       ],
                                       [
                                           'PerceptionType' => '038',
                                           'Code' => '038',
                                           'Description' => 'Percepción Excenta',
                                           'TaxedAmount' => 0,
                                           'ExemptAmount' => $pagoExtraordinario
                                       ],
                                       [
                                           'PerceptionType' => '019',
                                           'Code' => '019',
                                           'Description' => 'Horas Extra',
                                           'TaxedAmount' => 0,
                                           'ExemptAmount' => $h_extra_exentas,
                                           "ExtraHours"=> [
                                           [
                                             "Days"=> 2,
                                             "HoursType"=> "01",
                                             "ExtraHours"=> $h_extra_dias,
                                             "PaidAmount"=> $h_extra_exentas
                                           ]
                                         ]
                                       ]
                                    ];
                                }
                                else if($sueldos <= $salario_minimo_quincenal)
                                {
                                    $PerceptionsDetails = [
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Salario Quincenal',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $sueldos
                                        ],
                                        [
                                            'PerceptionType' => '029',
                                            'Code' => '029',
                                            'Description' => 'Despensa',
                                            'TaxedAmount' => $despensa,
                                            'ExemptAmount' => 0
                                        ],
                                        [
                                           'PerceptionType' => '001',
                                           'Code' => '001',
                                           'Description' => 'Dias de Vacaciones',
                                           'TaxedAmount' => $pago_dias_vacaciones,
                                           'ExemptAmount' => 0
                                       ],
                                       [
                                           'PerceptionType' => '038',
                                           'Code' => '038',
                                           'Description' => 'Percepción Excenta',
                                           'TaxedAmount' => 0,
                                           'ExemptAmount' => $pagoExtraordinario
                                       ],
                                       [
                                           'PerceptionType' => '019',
                                           'Code' => '019',
                                           'Description' => 'Horas Extra',
                                           'TaxedAmount' => 0,
                                           'ExemptAmount' => $h_extra_exentas,
                                           "ExtraHours"=> [
                                           [
                                             "Days"=> 2,
                                             "HoursType"=> "01",
                                             "ExtraHours"=> $h_extra_dias,
                                             "PaidAmount"=> $h_extra_exentas
                                           ]
                                         ]
                                       ]
                                    ];
                                }
                              }   
                              else if($pago_dias_vacaciones > 1 && $prima_vacacional < 1 && $h_extra_total > 1 && $pagoExtraordinario > 1 && $otros < 1 && $despensa < 1)
                              {
                                if($sueldos > $salario_minimo_quincenal)
                                {
                                    $PerceptionsDetails = [
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Salario Quincenal',
                                            'TaxedAmount' => $sueldos,
                                            'ExemptAmount' => 0
                                        ],
                                        [
                                            'PerceptionType' => '029',
                                            'Code' => '029',
                                            'Description' => 'Despensa',
                                            'TaxedAmount' => $despensa,
                                            'ExemptAmount' => 0
                                        ],
                                        [
                                           'PerceptionType' => '001',
                                           'Code' => '001',
                                           'Description' => 'Dias de Vacaciones',
                                           'TaxedAmount' => $pago_dias_vacaciones,
                                           'ExemptAmount' => 0
                                       ],
                                       [
                                           'PerceptionType' => '038',
                                           'Code' => '038',
                                           'Description' => 'Percepción Excenta',
                                           'TaxedAmount' => 0,
                                           'ExemptAmount' => $pagoExtraordinario
                                       ],
                                       [
                                           'PerceptionType' => '019',
                                           'Code' => '019',
                                           'Description' => 'Horas Extra',
                                           'TaxedAmount' => 0,
                                           'ExemptAmount' => $h_extra_exentas,
                                           "ExtraHours"=> [
                                           [
                                             "Days"=> 2,
                                             "HoursType"=> "01",
                                             "ExtraHours"=> $h_extra_dias,
                                             "PaidAmount"=> $h_extra_exentas
                                           ]
                                         ]
                                       ]
                                    ];
                                }
                                else if($sueldos <= $salario_minimo_quincenal){
                                    $PerceptionsDetails = [
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Salario Quincenal',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $sueldos
                                        ],
                                        [
                                            'PerceptionType' => '029',
                                            'Code' => '029',
                                            'Description' => 'Despensa',
                                            'TaxedAmount' => $despensa,
                                            'ExemptAmount' => 0
                                        ],
                                        [
                                           'PerceptionType' => '001',
                                           'Code' => '001',
                                           'Description' => 'Dias de Vacaciones',
                                           'TaxedAmount' => $pago_dias_vacaciones,
                                           'ExemptAmount' => 0
                                       ],
                                       [
                                           'PerceptionType' => '038',
                                           'Code' => '038',
                                           'Description' => 'Percepción Excenta',
                                           'TaxedAmount' => 0,
                                           'ExemptAmount' => $pagoExtraordinario
                                       ],
                                       [
                                           'PerceptionType' => '019',
                                           'Code' => '019',
                                           'Description' => 'Horas Extra',
                                           'TaxedAmount' => 0,
                                           'ExemptAmount' => $h_extra_exentas,
                                           "ExtraHours"=> [
                                           [
                                             "Days"=> 2,
                                             "HoursType"=> "01",
                                             "ExtraHours"=> $h_extra_dias,
                                             "PaidAmount"=> $h_extra_exentas
                                           ]
                                         ]
                                       ]
                                    ];
                                }
                              }
                     
                              
                              // Sueldo normal + despensa + dias de vacaciones + horas extra + otros
                     if($pago_dias_vacaciones > 1 && $prima_vacacional < 1 && $h_extra_total > 1 && $pagoExtraordinario < 1 && $otros > 1 && $despensa > 0 )
                     {
                        if($sueldos > $salario_minimo_quincenal)
                        {
                            $PerceptionsDetails = [
                                [
                                    'PerceptionType' => '001',
                                    'Code' => '001',
                                    'Description' => 'Salario Quincenal',
                                    'TaxedAmount' => $sueldos,
                                    'ExemptAmount' => 0
                                ],
                                [
                                    'PerceptionType' => '029',
                                    'Code' => '029',
                                    'Description' => 'Despensa',
                                    'TaxedAmount' => $despensa,
                                    'ExemptAmount' => 0
                                ],
                                [
                                   'PerceptionType' => '001',
                                   'Code' => '001',
                                   'Description' => 'Dias de Vacaciones',
                                   'TaxedAmount' => $pago_dias_vacaciones,
                                   'ExemptAmount' => 0
                               ],
                               [
                                'PerceptionType' => '038',
                                'Code' => '038',
                                'Description' => 'Percepción Excenta',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $otros
                               ],
                               [
                                   'PerceptionType' => '019',
                                   'Code' => '019',
                                   'Description' => 'Horas Extra',
                                   'TaxedAmount' => 0,
                                   'ExemptAmount' => $h_extra_exentas,
                                   "ExtraHours"=> [
                                   [
                                     "Days"=> 2,
                                     "HoursType"=> "01",
                                     "ExtraHours"=> $h_extra_dias,
                                     "PaidAmount"=> $h_extra_exentas
                                   ]
                                 ]
                               ]
                            ];
                        }
                        else if($sueldos <= $salario_minimo_quincenal)
                        {
                            $PerceptionsDetails = [
                                [
                                    'PerceptionType' => '001',
                                    'Code' => '001',
                                    'Description' => 'Salario Quincenal',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $sueldos
                                ],
                                [
                                    'PerceptionType' => '029',
                                    'Code' => '029',
                                    'Description' => 'Despensa',
                                    'TaxedAmount' => $despensa,
                                    'ExemptAmount' => 0
                                ],
                                [
                                   'PerceptionType' => '001',
                                   'Code' => '001',
                                   'Description' => 'Dias de Vacaciones',
                                   'TaxedAmount' => $pago_dias_vacaciones,
                                   'ExemptAmount' => 0
                               ],
                               [
                                'PerceptionType' => '038',
                                'Code' => '038',
                                'Description' => 'Percepción Excenta',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $otros
                               ],
                               [
                                   'PerceptionType' => '019',
                                   'Code' => '019',
                                   'Description' => 'Horas Extra',
                                   'TaxedAmount' => 0,
                                   'ExemptAmount' => $h_extra_exentas,
                                   "ExtraHours"=> [
                                   [
                                     "Days"=> 2,
                                     "HoursType"=> "01",
                                     "ExtraHours"=> $h_extra_dias,
                                     "PaidAmount"=> $h_extra_exentas
                                   ]
                                 ]
                               ]
                            ];
                        }
                     }  
                     else if($pago_dias_vacaciones > 1 && $prima_vacacional < 1 && $h_extra_total > 1 && $pagoExtraordinario < 1 && $otros > 1 && $despensa < 1)
                     {
                        if($sueldos > $salario_minimo_quincenal)
                        {
                            $PerceptionsDetails = [
                                [
                                    'PerceptionType' => '001',
                                    'Code' => '001',
                                    'Description' => 'Salario Quincenal',
                                    'TaxedAmount' => $sueldos,
                                    'ExemptAmount' => 0
                                ],
                                [
                                   'PerceptionType' => '001',
                                   'Code' => '001',
                                   'Description' => 'Dias de Vacaciones',
                                   'TaxedAmount' => $pago_dias_vacaciones,
                                   'ExemptAmount' => 0
                               ],
                               [
                                'PerceptionType' => '038',
                                'Code' => '038',
                                'Description' => 'Percepción Excenta',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $otros
                               ],
                               [
                                   'PerceptionType' => '019',
                                   'Code' => '019',
                                   'Description' => 'Horas Extra',
                                   'TaxedAmount' => 0,
                                   'ExemptAmount' => $h_extra_exentas,
                                   "ExtraHours"=> [
                                   [
                                     "Days"=> 2,
                                     "HoursType"=> "01",
                                     "ExtraHours"=> $h_extra_dias,
                                     "PaidAmount"=> $h_extra_exentas
                                   ]
                                 ]
                               ]
                            ];
                        }
                        else if($sueldos <= $salario_minimo_quincenal)
                        {
                            $PerceptionsDetails = [
                                [
                                    'PerceptionType' => '001',
                                    'Code' => '001',
                                    'Description' => 'Salario Quincenal',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $sueldos
                                ],
                                [
                                   'PerceptionType' => '001',
                                   'Code' => '001',
                                   'Description' => 'Dias de Vacaciones',
                                   'TaxedAmount' => $pago_dias_vacaciones,
                                   'ExemptAmount' => 0
                               ],
                               [
                                'PerceptionType' => '038',
                                'Code' => '038',
                                'Description' => 'Percepción Excenta',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $otros
                               ],
                               [
                                   'PerceptionType' => '019',
                                   'Code' => '019',
                                   'Description' => 'Horas Extra',
                                   'TaxedAmount' => 0,
                                   'ExemptAmount' => $h_extra_exentas,
                                   "ExtraHours"=> [
                                   [
                                     "Days"=> 2,
                                     "HoursType"=> "01",
                                     "ExtraHours"=> $h_extra_dias,
                                     "PaidAmount"=> $h_extra_exentas
                                   ]
                                 ]
                               ]
                            ];
                        }
                     }
                        
                     // Sueldo normal + despensa + dias de vacaciones + pago extraordinario + otros
                         if($pago_dias_vacaciones > 1 && $prima_vacacional < 1 && $h_extra_total < 1 && $pagoExtraordinario > 1 && $otros > 1 && $despensa > 0 )
                         {
                            if($sueldos > $salario_minimo_quincenal)
                            {
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => $sueldos,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                        'PerceptionType' => '029',
                                        'Code' => '029',
                                        'Description' => 'Despensa',
                                        'TaxedAmount' => $despensa,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                       'PerceptionType' => '001',
                                       'Code' => '001',
                                       'Description' => 'Dias de Vacaciones',
                                       'TaxedAmount' => $pago_dias_vacaciones,
                                       'ExemptAmount' => 0
                                   ],
                                   [
                                    'PerceptionType' => '038',
                                    'Code' => '038',
                                    'Description' => 'Percepción Excenta',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $otros
                                   ],
                                   [
                                    'PerceptionType' => '038',
                                    'Code' => '038',
                                    'Description' => 'Percepción Excenta',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $pagoExtraordinario
                                    ]
                                   ];
                            }
                            else if($sueldos <= $salario_minimo_quincenal)
                            {
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $sueldos
                                    ],
                                    [
                                        'PerceptionType' => '029',
                                        'Code' => '029',
                                        'Description' => 'Despensa',
                                        'TaxedAmount' => $despensa,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                       'PerceptionType' => '001',
                                       'Code' => '001',
                                       'Description' => 'Dias de Vacaciones',
                                       'TaxedAmount' => $pago_dias_vacaciones,
                                       'ExemptAmount' => 0
                                   ],
                                   [
                                    'PerceptionType' => '038',
                                    'Code' => '038',
                                    'Description' => 'Percepción Excenta',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $otros
                                   ],
                                   [
                                    'PerceptionType' => '038',
                                    'Code' => '038',
                                    'Description' => 'Percepción Excenta',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $pagoExtraordinario
                                    ]
                                ];
                            }
                         } 
                         else if($pago_dias_vacaciones > 1 && $prima_vacacional < 1 && $h_extra_total < 1 && $pagoExtraordinario > 1 && $otros > 1 && $despensa < 1)
                         {
                            if($sueldos > $salario_minimo_quincenal)
                            {
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => $sueldos,
                                        'ExemptAmount' => 0
                                    ],
                                    [
                                       'PerceptionType' => '001',
                                       'Code' => '001',
                                       'Description' => 'Dias de Vacaciones',
                                       'TaxedAmount' => $pago_dias_vacaciones,
                                       'ExemptAmount' => 0
                                   ],
                                   [
                                    'PerceptionType' => '038',
                                    'Code' => '038',
                                    'Description' => 'Percepción Excenta',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $otros
                                   ],
                                   [
                                    'PerceptionType' => '038',
                                    'Code' => '038',
                                    'Description' => 'Percepción Excenta',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $pagoExtraordinario
                                    ]
                                   ];
                            }
                            else if($sueldos <= $salario_minimo_quincenal)
                            {
                                $PerceptionsDetails = [
                                    [
                                        'PerceptionType' => '001',
                                        'Code' => '001',
                                        'Description' => 'Salario Quincenal',
                                        'TaxedAmount' => 0,
                                        'ExemptAmount' => $sueldos
                                    ],
                                    [
                                       'PerceptionType' => '001',
                                       'Code' => '001',
                                       'Description' => 'Dias de Vacaciones',
                                       'TaxedAmount' => $pago_dias_vacaciones,
                                       'ExemptAmount' => 0
                                   ],
                                   [
                                    'PerceptionType' => '038',
                                    'Code' => '038',
                                    'Description' => 'Percepción Excenta',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $otros
                                   ],
                                   [
                                    'PerceptionType' => '038',
                                    'Code' => '038',
                                    'Description' => 'Percepción Excenta',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $pagoExtraordinario
                                    ]
                                ];
                            }
                         }
                     // Sueldo normal + despensa + prima vacacional + horas extra + pago extraordinario
                     if($pago_dias_vacaciones < 1 && $prima_vacacional > 1 && $h_extra_total > 1 && $pagoExtraordinario > 1 && $otros < 1 && $despensa > 0 )
                     {
                        if($sueldos > $salario_minimo_quincenal)
                        {
                            $PerceptionsDetails = [
                                [
                                    'PerceptionType' => '001',
                                    'Code' => '001',
                                    'Description' => 'Salario Quincenal',
                                    'TaxedAmount' => $sueldos,
                                    'ExemptAmount' => 0
                                ],
                                [
                                    'PerceptionType' => '029',
                                    'Code' => '029',
                                    'Description' => 'Despensa',
                                    'TaxedAmount' => $despensa,
                                    'ExemptAmount' => 0
                                ],
                                [
                                'PerceptionType' => '038',
                                'Code' => '038',
                                'Description' => 'Percepción Excenta',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $pagoExtraordinario
                                ],
                                [
                                    'PerceptionType' => '021',
                                    'Code' => '021',
                                    'Description' => 'Prima Vacacional',
                                    'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                    'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                ],
                                [
                                    'PerceptionType' => '019',
                                    'Code' => '019',
                                    'Description' => 'Horas Extra',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $h_extra_exentas,
                                    "ExtraHours"=> [
                                    [
                                      "Days"=> 2,
                                      "HoursType"=> "01",
                                      "ExtraHours"=> $h_extra_dias,
                                      "PaidAmount"=> $h_extra_exentas
                                    ]
                                  ]
                                ]
                               ];
                        } 
                        else if($sueldos <= $salario_minimo_quincenal)
                        {
                            $PerceptionsDetails = [
                                [
                                    'PerceptionType' => '001',
                                    'Code' => '001',
                                    'Description' => 'Salario Quincenal',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $sueldos
                                ],
                                [
                                    'PerceptionType' => '029',
                                    'Code' => '029',
                                    'Description' => 'Despensa',
                                    'TaxedAmount' => $despensa,
                                    'ExemptAmount' => 0
                                ],
                                [
                                'PerceptionType' => '038',
                                'Code' => '038',
                                'Description' => 'Percepción Excenta',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $pagoExtraordinario
                                ],
                                [
                                    'PerceptionType' => '021',
                                    'Code' => '021',
                                    'Description' => 'Prima Vacacional',
                                    'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                    'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                ],
                                [
                                    'PerceptionType' => '019',
                                    'Code' => '019',
                                    'Description' => 'Horas Extra',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $h_extra_exentas,
                                    "ExtraHours"=> [
                                    [
                                      "Days"=> 2,
                                      "HoursType"=> "01",
                                      "ExtraHours"=> $h_extra_dias,
                                      "PaidAmount"=> $h_extra_exentas
                                    ]
                                  ]
                                ]
                            ];
                        }
                     } 
                     else if($pago_dias_vacaciones < 1 && $prima_vacacional > 1 && $h_extra_total > 1 && $pagoExtraordinario > 1 && $otros < 1 && $despensa < 1)
                     {
                        if($sueldos > $salario_minimo_quincenal)
                        {
                            $PerceptionsDetails = [
                                [
                                    'PerceptionType' => '001',
                                    'Code' => '001',
                                    'Description' => 'Salario Quincenal',
                                    'TaxedAmount' => $sueldos,
                                    'ExemptAmount' => 0
                                ],
                                [
                                'PerceptionType' => '038',
                                'Code' => '038',
                                'Description' => 'Percepción Excenta',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $pagoExtraordinario
                                ],
                                [
                                    'PerceptionType' => '021',
                                    'Code' => '021',
                                    'Description' => 'Prima Vacacional',
                                    'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                    'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                ],
                                [
                                    'PerceptionType' => '019',
                                    'Code' => '019',
                                    'Description' => 'Horas Extra',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $h_extra_exentas,
                                    "ExtraHours"=> [
                                    [
                                      "Days"=> 2,
                                      "HoursType"=> "01",
                                      "ExtraHours"=> $h_extra_dias,
                                      "PaidAmount"=> $h_extra_exentas
                                    ]
                                  ]
                                ]
                               ];
                        }
                        else if($sueldos <= $salario_minimo_quincenal)
                        {
                            $PerceptionsDetails = [
                                [
                                    'PerceptionType' => '001',
                                    'Code' => '001',
                                    'Description' => 'Salario Quincenal',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $sueldos
                                ],
                                [
                                'PerceptionType' => '038',
                                'Code' => '038',
                                'Description' => 'Percepción Excenta',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $pagoExtraordinario
                                ],
                                [
                                    'PerceptionType' => '021',
                                    'Code' => '021',
                                    'Description' => 'Prima Vacacional',
                                    'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                    'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                ],
                                [
                                    'PerceptionType' => '019',
                                    'Code' => '019',
                                    'Description' => 'Horas Extra',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $h_extra_exentas,
                                    "ExtraHours"=> [
                                    [
                                      "Days"=> 2,
                                      "HoursType"=> "01",
                                      "ExtraHours"=> $h_extra_dias,
                                      "PaidAmount"=> $h_extra_exentas
                                    ]
                                  ]
                                ]
                            ];
                        }
                     }
                 // Sueldo normal + despensa + prima vacacional + horas extra + otros
                 if($pago_dias_vacaciones < 1 && $prima_vacacional > 1 && $h_extra_total > 1 && $pagoExtraordinario < 1 && $otros > 1 && $despensa > 0 )
                 {
                    if($sueldos > $salario_minimo_quincenal)
                    {
                        $PerceptionsDetails = [
                            [
                                'PerceptionType' => '001',
                                'Code' => '001',
                                'Description' => 'Salario Quincenal',
                                'TaxedAmount' => $sueldos,
                                'ExemptAmount' => 0
                            ],
                            [
                                'PerceptionType' => '029',
                                'Code' => '029',
                                'Description' => 'Despensa',
                                'TaxedAmount' => $despensa,
                                'ExemptAmount' => 0
                            ],
                            [
                                'PerceptionType' => '038',
                                'Code' => '038',
                                'Description' => 'Percepción Excenta',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $otros
                               ],
                            [
                                'PerceptionType' => '021',
                                'Code' => '021',
                                'Description' => 'Prima Vacacional',
                                'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                            ],
                            [
                                'PerceptionType' => '019',
                                'Code' => '019',
                                'Description' => 'Horas Extra',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $h_extra_exentas,
                                "ExtraHours"=> [
                                [
                                  "Days"=> 2,
                                  "HoursType"=> "01",
                                  "ExtraHours"=> $h_extra_dias,
                                  "PaidAmount"=> $h_extra_exentas
                                ]
                              ]
                            ]
                           ];
                    } 
                    else if($sueldos <= $salario_minimo_quincenal)
                    {
                        $PerceptionsDetails = [
                            [
                                'PerceptionType' => '001',
                                'Code' => '001',
                                'Description' => 'Salario Quincenal',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $sueldos
                            ],
                            [
                                'PerceptionType' => '029',
                                'Code' => '029',
                                'Description' => 'Despensa',
                                'TaxedAmount' => $despensa,
                                'ExemptAmount' => 0
                            ],
                            [
                                'PerceptionType' => '038',
                                'Code' => '038',
                                'Description' => 'Percepción Excenta',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $otros
                               ],
                            [
                                'PerceptionType' => '021',
                                'Code' => '021',
                                'Description' => 'Prima Vacacional',
                                'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                            ],
                            [
                                'PerceptionType' => '019',
                                'Code' => '019',
                                'Description' => 'Horas Extra',
                                'TaxedAmount' => 0,
                                'ExemptAmount' => $h_extra_exentas,
                                "ExtraHours"=> [
                                [
                                  "Days"=> 2,
                                  "HoursType"=> "01",
                                  "ExtraHours"=> $h_extra_dias,
                                  "PaidAmount"=> $h_extra_exentas
                                ]
                              ]
                            ]
                        ];
                    }
                 }
                 else if($pago_dias_vacaciones < 1 && $prima_vacacional > 1 && $h_extra_total > 1 && $pagoExtraordinario < 1 && $otros > 1 && $despensa < 1)
                 {
                     if($sueldos > $salario_minimo_quincenal)
                     {
                         $PerceptionsDetails = [
                             [
                                 'PerceptionType' => '001',
                                 'Code' => '001',
                                 'Description' => 'Salario Quincenal',
                                 'TaxedAmount' => $sueldos,
                                 'ExemptAmount' => 0
                             ],
                             [
                                 'PerceptionType' => '038',
                                 'Code' => '038',
                                 'Description' => 'Percepción Excenta',
                                 'TaxedAmount' => 0,
                                 'ExemptAmount' => $otros
                                ],
                             [
                                 'PerceptionType' => '021',
                                 'Code' => '021',
                                 'Description' => 'Prima Vacacional',
                                 'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                 'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                             ],
                             [
                                 'PerceptionType' => '019',
                                 'Code' => '019',
                                 'Description' => 'Horas Extra',
                                 'TaxedAmount' => 0,
                                 'ExemptAmount' => $h_extra_exentas,
                                 "ExtraHours"=> [
                                 [
                                   "Days"=> 2,
                                   "HoursType"=> "01",
                                   "ExtraHours"=> $h_extra_dias,
                                   "PaidAmount"=> $h_extra_exentas
                                 ]
                               ]
                             ]
                            ];
                     }
                     else if($sueldos <= $salario_minimo_quincenal)
                     {
                         $PerceptionsDetails = [
                             [
                                 'PerceptionType' => '001',
                                 'Code' => '001',
                                 'Description' => 'Salario Quincenal',
                                 'TaxedAmount' => 0,
                                 'ExemptAmount' => $sueldos
                             ],
                             [
                                 'PerceptionType' => '038',
                                 'Code' => '038',
                                 'Description' => 'Percepción Excenta',
                                 'TaxedAmount' => 0,
                                 'ExemptAmount' => $otros
                                ],
                             [
                                 'PerceptionType' => '021',
                                 'Code' => '021',
                                 'Description' => 'Prima Vacacional',
                                 'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                 'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                             ],
                             [
                                 'PerceptionType' => '019',
                                 'Code' => '019',
                                 'Description' => 'Horas Extra',
                                 'TaxedAmount' => 0,
                                 'ExemptAmount' => $h_extra_exentas,
                                 "ExtraHours"=> [
                                 [
                                   "Days"=> 2,
                                   "HoursType"=> "01",
                                   "ExtraHours"=> $h_extra_dias,
                                   "PaidAmount"=> $h_extra_exentas
                                 ]
                               ]
                             ]
                         ];
                     }
                 } 

                    //-------------------------------- Cuartetas: --------------------------------
                                   // Sueldo normal + despensa + prima vacacional + dias de vacaciones + pago extraordinario + horas extra
                                   if($pago_dias_vacaciones > 1 && $prima_vacacional > 1 && $h_extra_total > 1 && $pagoExtraordinario > 1 && $otros < 1 && $despensa > 0 )
                                   {
                                     if($sueldos > $salario_minimo_quincenal)
                                     {
                                        $PerceptionsDetails = [
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Salario Quincenal',
                                                'TaxedAmount' => $sueldos,
                                                'ExemptAmount' => 0
                                            ],
                                            [
                                                'PerceptionType' => '029',
                                                'Code' => '029',
                                                'Description' => 'Despensa',
                                                'TaxedAmount' => $despensa,
                                                'ExemptAmount' => 0
                                            ],
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Dias de Vacaciones',
                                                'TaxedAmount' => $pago_dias_vacaciones,
                                                'ExemptAmount' => 0
                                            ], 
                                            [
                                                'PerceptionType' => '021',
                                                'Code' => '021',
                                                'Description' => 'Prima Vacacional',
                                                'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                                'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                            ],
                                            [
                                                'PerceptionType' => '038',
                                                'Code' => '038',
                                                'Description' => 'Percepción Excenta',
                                                'TaxedAmount' => 0,
                                                'ExemptAmount' => $pagoExtraordinario
                                            ],
                                            [
                                                'PerceptionType' => '019',
                                                'Code' => '019',
                                                'Description' => 'Horas Extra',
                                                'TaxedAmount' => 0,
                                                'ExemptAmount' => $h_extra_exentas,
                                                "ExtraHours"=> [
                                                [
                                                  "Days"=> 2,
                                                  "HoursType"=> "01",
                                                  "ExtraHours"=> $h_extra_dias,
                                                  "PaidAmount"=> $h_extra_exentas
                                                ]
                                              ]
                                            ]
                                        ];
                                     }
                                     else if($sueldos <= $salario_minimo_quincenal){
                                        $PerceptionsDetails = [
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Salario Quincenal',
                                                'TaxedAmount' => 0,
                                                'ExemptAmount' => $sueldos
                                            ],
                                            [
                                                'PerceptionType' => '029',
                                                'Code' => '029',
                                                'Description' => 'Despensa',
                                                'TaxedAmount' => $despensa,
                                                'ExemptAmount' => 0
                                            ],
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Dias de Vacaciones',
                                                'TaxedAmount' => $pago_dias_vacaciones,
                                                'ExemptAmount' => 0
                                            ], 
                                            [
                                                'PerceptionType' => '021',
                                                'Code' => '021',
                                                'Description' => 'Prima Vacacional',
                                                'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                                'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                            ],
                                            [
                                                'PerceptionType' => '038',
                                                'Code' => '038',
                                                'Description' => 'Percepción Excenta',
                                                'TaxedAmount' => 0,
                                                'ExemptAmount' => $pagoExtraordinario
                                            ],
                                            [
                                                'PerceptionType' => '019',
                                                'Code' => '019',
                                                'Description' => 'Horas Extra',
                                                'TaxedAmount' => 0,
                                                'ExemptAmount' => $h_extra_exentas,
                                                "ExtraHours"=> [
                                                [
                                                  "Days"=> 2,
                                                  "HoursType"=> "01",
                                                  "ExtraHours"=> $h_extra_dias,
                                                  "PaidAmount"=> $h_extra_exentas
                                                ]
                                              ]
                                            ]
                                        ];
                                    }
                                   }
                                   else if($pago_dias_vacaciones > 1 && $prima_vacacional > 1 && $h_extra_total > 1 && $pagoExtraordinario > 1 && $otros < 1 && $despensa < 1)
                                   {
                                    if($sueldos > $salario_minimo_quincenal)
                                    {
                                        $PerceptionsDetails = [
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Salario Quincenal',
                                                'TaxedAmount' => 0,
                                                'ExemptAmount' => $sueldos
                                            ],
                                            [
                                                'PerceptionType' => '001',
                                                'Code' => '001',
                                                'Description' => 'Dias de Vacaciones',
                                                'TaxedAmount' => $pago_dias_vacaciones,
                                                'ExemptAmount' => 0
                                            ], 
                                            [
                                                'PerceptionType' => '021',
                                                'Code' => '021',
                                                'Description' => 'Prima Vacacional',
                                                'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                                'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                            ],
                                            [
                                                'PerceptionType' => '038',
                                                'Code' => '038',
                                                'Description' => 'Percepción Excenta',
                                                'TaxedAmount' => 0,
                                                'ExemptAmount' => $pagoExtraordinario
                                            ],
                                            [
                                                'PerceptionType' => '019',
                                                'Code' => '019',
                                                'Description' => 'Horas Extra',
                                                'TaxedAmount' => 0,
                                                'ExemptAmount' => $h_extra_exentas,
                                                "ExtraHours"=> [
                                                [
                                                  "Days"=> 2,
                                                  "HoursType"=> "01",
                                                  "ExtraHours"=> $h_extra_dias,
                                                  "PaidAmount"=> $h_extra_exentas
                                                ]
                                              ]
                                            ]
                                        ];
                                    }
                                   else if($sueldos <= $salario_minimo_quincenal)
                                   {
                                    $PerceptionsDetails = [
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Salario Quincenal',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $sueldos
                                        ],
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Dias de Vacaciones',
                                            'TaxedAmount' => $pago_dias_vacaciones,
                                            'ExemptAmount' => 0
                                        ], 
                                        [
                                            'PerceptionType' => '021',
                                            'Code' => '021',
                                            'Description' => 'Prima Vacacional',
                                            'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                            'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                        ],
                                        [
                                            'PerceptionType' => '038',
                                            'Code' => '038',
                                            'Description' => 'Percepción Excenta',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $pagoExtraordinario
                                        ],
                                        [
                                            'PerceptionType' => '019',
                                            'Code' => '019',
                                            'Description' => 'Horas Extra',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $h_extra_exentas,
                                            "ExtraHours"=> [
                                            [
                                              "Days"=> 2,
                                              "HoursType"=> "01",
                                              "ExtraHours"=> $h_extra_dias,
                                              "PaidAmount"=> $h_extra_exentas
                                            ]
                                          ]
                                        ]
                                    ];
                                   }
                                   }
                            

                                
                             // Sueldo normal + despensa + prima vacacional + dias de vacaciones + otros + horas extra
                             if($pago_dias_vacaciones > 1 && $prima_vacacional > 1 && $h_extra_total > 1 && $pagoExtraordinario < 1 && $otros > 1 && $despensa > 0 )
                             {
                                if($sueldos > $salario_minimo_quincenal)
                                {
                                    $PerceptionsDetails = [
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Salario Quincenal',
                                            'TaxedAmount' => $sueldos,
                                            'ExemptAmount' => 0
                                        ],
                                        [
                                            'PerceptionType' => '029',
                                            'Code' => '029',
                                            'Description' => 'Despensa',
                                            'TaxedAmount' => $despensa,
                                            'ExemptAmount' => 0
                                        ],
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Dias de Vacaciones',
                                            'TaxedAmount' => $pago_dias_vacaciones,
                                            'ExemptAmount' => 0
                                        ], 
                                        [
                                            'PerceptionType' => '021',
                                            'Code' => '021',
                                            'Description' => 'Prima Vacacional',
                                            'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                            'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                        ],
                                        [
                                            'PerceptionType' => '038',
                                            'Code' => '038',
                                            'Description' => 'Percepción Excenta',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $otros
                                        ],
                                        [
                                            'PerceptionType' => '019',
                                            'Code' => '019',
                                            'Description' => 'Horas Extra',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $h_extra_exentas,
                                            "ExtraHours"=> [
                                            [
                                              "Days"=> 2,
                                              "HoursType"=> "01",
                                              "ExtraHours"=> $h_extra_dias,
                                              "PaidAmount"=> $h_extra_exentas
                                            ]
                                          ]
                                        ]
                                    ];
                                }
                                else if($sueldos <= $salario_minimo_quincenal)
                                {
                                    $PerceptionsDetails = [
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Salario Quincenal',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $sueldos
                                        ],
                                        [
                                            'PerceptionType' => '029',
                                            'Code' => '029',
                                            'Description' => 'Despensa',
                                            'TaxedAmount' => $despensa,
                                            'ExemptAmount' => 0
                                        ],
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Dias de Vacaciones',
                                            'TaxedAmount' => $pago_dias_vacaciones,
                                            'ExemptAmount' => 0
                                        ], 
                                        [
                                            'PerceptionType' => '021',
                                            'Code' => '021',
                                            'Description' => 'Prima Vacacional',
                                            'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                            'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                        ],
                                        [
                                            'PerceptionType' => '038',
                                            'Code' => '038',
                                            'Description' => 'Percepción Excenta',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $otros
                                        ],
                                        [
                                            'PerceptionType' => '019',
                                            'Code' => '019',
                                            'Description' => 'Horas Extra',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $h_extra_exentas,
                                            "ExtraHours"=> [
                                            [
                                              "Days"=> 2,
                                              "HoursType"=> "01",
                                              "ExtraHours"=> $h_extra_dias,
                                              "PaidAmount"=> $h_extra_exentas
                                            ]
                                          ]
                                        ]
                                    ];
                                }
                             }
                             else if($pago_dias_vacaciones > 1 && $prima_vacacional > 1 && $h_extra_total > 1 && $pagoExtraordinario < 1 && $otros > 1 && $despensa < 1)
                             {
                                if($sueldos > $salario_minimo_quincenal)
                                {
                                    $PerceptionsDetails = [
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Salario Quincenal',
                                            'TaxedAmount' => $sueldos,
                                            'ExemptAmount' => 0
                                        ],
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Dias de Vacaciones',
                                            'TaxedAmount' => $pago_dias_vacaciones,
                                            'ExemptAmount' => 0
                                        ], 
                                        [
                                            'PerceptionType' => '021',
                                            'Code' => '021',
                                            'Description' => 'Prima Vacacional',
                                            'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                            'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                        ],
                                        [
                                            'PerceptionType' => '038',
                                            'Code' => '038',
                                            'Description' => 'Percepción Excenta',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $otros
                                        ],
                                        [
                                            'PerceptionType' => '019',
                                            'Code' => '019',
                                            'Description' => 'Horas Extra',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $h_extra_exentas,
                                            "ExtraHours"=> [
                                            [
                                              "Days"=> 2,
                                              "HoursType"=> "01",
                                              "ExtraHours"=> $h_extra_dias,
                                              "PaidAmount"=> $h_extra_exentas
                                            ]
                                          ]
                                        ]
                                    ];
                                }
                                else if($sueldos <= $salario_minimo_quincenal)
                                {
                                    $PerceptionsDetails = [
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Salario Quincenal',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $sueldos
                                        ],
                                        [
                                            'PerceptionType' => '001',
                                            'Code' => '001',
                                            'Description' => 'Dias de Vacaciones',
                                            'TaxedAmount' => $pago_dias_vacaciones,
                                            'ExemptAmount' => 0
                                        ], 
                                        [
                                            'PerceptionType' => '021',
                                            'Code' => '021',
                                            'Description' => 'Prima Vacacional',
                                            'TaxedAmount' => $montoSujetoAImpuestos_primavacacional,
                                            'ExemptAmount' => $montoLibreAImpuestos_primavacacional
                                        ],
                                        [
                                            'PerceptionType' => '038',
                                            'Code' => '038',
                                            'Description' => 'Percepción Excenta',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $otros
                                        ],
                                        [
                                            'PerceptionType' => '019',
                                            'Code' => '019',
                                            'Description' => 'Horas Extra',
                                            'TaxedAmount' => 0,
                                            'ExemptAmount' => $h_extra_exentas,
                                            "ExtraHours"=> [
                                            [
                                              "Days"=> 2,
                                              "HoursType"=> "01",
                                              "ExtraHours"=> $h_extra_dias,
                                              "PaidAmount"=> $h_extra_exentas
                                            ]
                                          ]
                                        ]
                                    ];
                                }
                             }

                     // Sueldo normal + despensa  + dias de vacaciones + pago extraordinario + otros + horas extra
                     if($pago_dias_vacaciones > 1 && $prima_vacacional > 1 && $h_extra_total > 1 && $pagoExtraordinario > 1 && $otros > 1 && $despensa > 0 )
                     {
                        if($sueldos > $salario_minimo_quincenal)
                        {
                            $PerceptionsDetails = [
                                [
                                    'PerceptionType' => '001',
                                    'Code' => '001',
                                    'Description' => 'Salario Quincenal',
                                    'TaxedAmount' => $sueldos,
                                    'ExemptAmount' => 0
                                ],
                                [
                                    'PerceptionType' => '029',
                                    'Code' => '029',
                                    'Description' => 'Despensa',
                                    'TaxedAmount' => $despensa,
                                    'ExemptAmount' => 0
                                ],
                                [
                                    'PerceptionType' => '001',
                                    'Code' => '001',
                                    'Description' => 'Dias de Vacaciones',
                                    'TaxedAmount' => $pago_dias_vacaciones,
                                    'ExemptAmount' => 0
                                ], 
                                [
                                    'PerceptionType' => '038',
                                    'Code' => '038',
                                    'Description' => 'Percepción Excenta',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $pagoExtraordinario
                                ],
                                [
                                    'PerceptionType' => '038',
                                    'Code' => '038',
                                    'Description' => 'Percepción Excenta',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $otros
                                ],
                                [
                                    'PerceptionType' => '019',
                                    'Code' => '019',
                                    'Description' => 'Horas Extra',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $h_extra_exentas,
                                    "ExtraHours"=> [
                                    [
                                      "Days"=> 2,
                                      "HoursType"=> "01",
                                      "ExtraHours"=> $h_extra_dias,
                                      "PaidAmount"=> $h_extra_exentas
                                    ]
                                  ]
                                ]
                            ];
                        }
                        else if($sueldos <= $salario_minimo_quincenal){
                            $PerceptionsDetails = [
                                [
                                    'PerceptionType' => '001',
                                    'Code' => '001',
                                    'Description' => 'Salario Quincenal',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $sueldos
                                ],
                                [
                                    'PerceptionType' => '029',
                                    'Code' => '029',
                                    'Description' => 'Despensa',
                                    'TaxedAmount' => $despensa,
                                    'ExemptAmount' => 0
                                ],
                                [
                                    'PerceptionType' => '001',
                                    'Code' => '001',
                                    'Description' => 'Dias de Vacaciones',
                                    'TaxedAmount' => $pago_dias_vacaciones,
                                    'ExemptAmount' => 0
                                ], 
                                [
                                    'PerceptionType' => '038',
                                    'Code' => '038',
                                    'Description' => 'Percepción Excenta',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $pagoExtraordinario
                                ],
                                [
                                    'PerceptionType' => '038',
                                    'Code' => '038',
                                    'Description' => 'Percepción Excenta',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $otros
                                ],
                                [
                                    'PerceptionType' => '019',
                                    'Code' => '019',
                                    'Description' => 'Horas Extra',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $h_extra_exentas,
                                    "ExtraHours"=> [
                                    [
                                      "Days"=> 2,
                                      "HoursType"=> "01",
                                      "ExtraHours"=> $h_extra_dias,
                                      "PaidAmount"=> $h_extra_exentas
                                    ]
                                  ]
                                ]
                            ];
                        }
                     }      
                     else if($pago_dias_vacaciones > 1 && $prima_vacacional > 1 && $h_extra_total > 1 && $pagoExtraordinario > 1 && $otros > 1 && $despensa < 1)
                     {
                        if($sueldos > $salario_minimo_quincenal)
                        {
                            $PerceptionsDetails = [
                                [
                                    'PerceptionType' => '001',
                                    'Code' => '001',
                                    'Description' => 'Salario Quincenal',
                                    'TaxedAmount' => $sueldos,
                                    'ExemptAmount' => 0
                                ],
                                [
                                    'PerceptionType' => '001',
                                    'Code' => '001',
                                    'Description' => 'Dias de Vacaciones',
                                    'TaxedAmount' => $pago_dias_vacaciones,
                                    'ExemptAmount' => 0
                                ], 
                                [
                                    'PerceptionType' => '038',
                                    'Code' => '038',
                                    'Description' => 'Percepción Excenta',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $pagoExtraordinario
                                ],
                                [
                                    'PerceptionType' => '038',
                                    'Code' => '038',
                                    'Description' => 'Percepción Excenta',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $otros
                                ],
                                [
                                    'PerceptionType' => '019',
                                    'Code' => '019',
                                    'Description' => 'Horas Extra',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $h_extra_exentas,
                                    "ExtraHours"=> [
                                    [
                                      "Days"=> 2,
                                      "HoursType"=> "01",
                                      "ExtraHours"=> $h_extra_dias,
                                      "PaidAmount"=> $h_extra_exentas
                                    ]
                                  ]
                                ]
                            ];
                        }
                        else if($sueldos <= $salario_minimo_quincenal){
                            $PerceptionsDetails = [
                                [
                                    'PerceptionType' => '001',
                                    'Code' => '001',
                                    'Description' => 'Salario Quincenal',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $sueldos
                                ],
                                [
                                    'PerceptionType' => '001',
                                    'Code' => '001',
                                    'Description' => 'Dias de Vacaciones',
                                    'TaxedAmount' => $pago_dias_vacaciones,
                                    'ExemptAmount' => 0
                                ], 
                                [
                                    'PerceptionType' => '038',
                                    'Code' => '038',
                                    'Description' => 'Percepción Excenta',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $pagoExtraordinario
                                ],
                                [
                                    'PerceptionType' => '038',
                                    'Code' => '038',
                                    'Description' => 'Percepción Excenta',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $otros
                                ],
                                [
                                    'PerceptionType' => '019',
                                    'Code' => '019',
                                    'Description' => 'Horas Extra',
                                    'TaxedAmount' => 0,
                                    'ExemptAmount' => $h_extra_exentas,
                                    "ExtraHours"=> [
                                    [
                                      "Days"=> 2,
                                      "HoursType"=> "01",
                                      "ExtraHours"=> $h_extra_dias,
                                      "PaidAmount"=> $h_extra_exentas
                                    ]
                                  ]
                                ]
                            ];
                        }
                     }
                   /*  if($PerceptionsDetails == null)
                     {
                        $PerceptionsDetails = [
                            [
                                'PerceptionType' => '001',
                                'Code' => '001',
                                'Description' => 'Salario Quincenal',
                                'TaxedAmount' => 'monto no encontrado',
                                'ExemptAmount' => 0
                            ]
                        ];
                        return $PerceptionsDetails;
                     }
                     */

                return $PerceptionsDetails;
    
    } // llave final de la funcion percepciones

     public function deducciones($isr,$imss,$infonavit,$fonacot,$prestamoemp)
    {
            //DEDUCCIONES --------------------------------------------

        //pago de ISR
        if($imss < 1 && $infonavit < 1 && $fonacot <1 && $prestamoemp <1 && $isr > 1)
        {
        $deductionsDetails = [
            [
                'DeduccionType' => '002',
                'Code' => 'ISR',
                'Description' => 'Impuesto Sobre la Renta',
                'Amount' => $isr    
            ]
        ];
        }
        else
        {
            $deductionsDetails = null;
        }
        //deducciones // verificar si esiste pago infonovit imss e isr en deducciones
        // pago de IMMS + ISR
        if($imss >= 1 && $infonavit < 1 && $fonacot <1 && $prestamoemp < 1 && $isr > 1)
        {
        $deductionsDetails = [
            [
                'DeduccionType' => '002',
                'Code' => 'ISR',
                'Description' => 'Impuesto Sobre la Renta',
                'Amount' => $isr
            ],
            [
                'DeduccionType' => '001',
                'Code' => 'IMSS',
                'Description' => 'Seguridad Social',
                'Amount' => $imss 
            ]
        ];
        }
        else if($imss >= 1 && $infonavit < 1 && $fonacot <1 && $prestamoemp < 1 && $isr < 1)
        {
            $deductionsDetails = [
                [
                    'DeduccionType' => '001',
                    'Code' => 'IMSS',
                    'Description' => 'Seguridad Social',
                    'Amount' => $imss 
                ]
            ];
        }
        // infonavit -----------------------------------------------------------------------------
        // infonavit + ISR
        if( $infonavit >= 1 && $imss < 1 && $fonacot <1 && $prestamoemp < 1 && $isr > 1)
        {
        $deductionsDetails = [
            [
                'DeduccionType' => '002',
                'Code' => 'ISR',
                'Description' => 'Impuesto Sobre la Renta',
                'Amount' => $isr
            ],
            [
                'DeduccionType' => '005',
                'Code' => 'INFONAVIT',
                'Description' => 'Aportaciones a Fondo de vivienda',
                'Amount' => $infonavit
            ]
        ];
        } else if( $infonavit >= 1 && $imss < 1 && $fonacot <1 && $prestamoemp < 1 && $isr < 1)
        {
            $deductionsDetails = [
                [
                    'DeduccionType' => '005',
                    'Code' => 'INFONAVIT',
                    'Description' => 'Aportaciones a Fondo de vivienda',
                    'Amount' => $infonavit
                ]
            ];
        }
        // tiene isr + imss +infonavit
        if($imss >= 1 && $infonavit >= 1 && $fonacot <1 && $prestamoemp < 1 && $isr > 1)
        {
        $deductionsDetails = [
            [
                'DeduccionType' => '002',
                'Code' => 'ISR',
                'Description' => 'Impuesto Sobre la Renta',
                'Amount' => $isr
            ],
            [
                'DeduccionType' => '001',
                'Code' => 'IMSS',
                'Description' => 'Seguridad Social',
                'Amount' => $imss
            ],
            [
                'DeduccionType' => '005',
                'Code' => 'INFONAVIT',
                'Description' => 'INFONAVIT',
                'Amount' => $infonavit
            ]
        ];
        }
        else if($imss >= 1 && $infonavit >= 1 && $fonacot <1 && $prestamoemp < 1 && $isr < 1)
        {
            $deductionsDetails = [
                [
                    'DeduccionType' => '001',
                    'Code' => 'IMSS',
                    'Description' => 'Seguridad Social',
                    'Amount' => $imss
                ],
                [
                    'DeduccionType' => '005',
                    'Code' => 'INFONAVIT',
                    'Description' => 'INFONAVIT',
                    'Amount' => $infonavit
                ]
            ];
        }
        // ISR + infonavit + fonacot
        if($infonavit >= 1 && $fonacot >= 1 && $prestamoemp < 1 && $imss < 1 && $isr > 1)
        {
        $deductionsDetails = [
            [
                'DeduccionType' => '002',
                'Code' => 'ISR',
                'Description' => 'Impuesto Sobre la Renta',
                'Amount' => $isr
            ],
            [
                'DeduccionType' => '011',
                'Code' => 'Fonacot',
                'Description' => 'Fonacot',
                'Amount' => $fonacot 
            ],
            [
                'DeduccionType' => '005',
                'Code' => 'INFONAVIT',
                'Description' => 'INFONAVIT',
                'Amount' => $infonavit
            ]
        ];
        }else if($infonavit >= 1 && $fonacot >= 1 && $prestamoemp < 1 && $imss < 1 && $isr < 1)
        {
            $deductionsDetails = [
                [
                    'DeduccionType' => '011',
                    'Code' => 'Fonacot',
                    'Description' => 'Fonacot',
                    'Amount' => $fonacot 
                ],
                [
                    'DeduccionType' => '005',
                    'Code' => 'INFONAVIT',
                    'Description' => 'INFONAVIT',
                    'Amount' => $infonavit
                ]
            ];
        }

        //se anexa el fonacot y prestamo de empresa
        //ISR + Fonacot ----------------------------------------------
        if($fonacot > 1 && $imss < 1 && $infonavit <1 && $prestamoemp < 1 && $isr > 1)
        {
        $deductionsDetails = [
            [
                'DeduccionType' => '002',
                'Code' => 'ISR',
                'Description' => 'Impuesto Sobre la Renta',
                'Amount' => $isr
            ],
            [
                'DeduccionType' => '011',
                'Code' => 'Fonacot',
                'Description' => 'Fonacot',
                'Amount' => $fonacot 
            ]
        ];
        }else if($fonacot > 1 && $imss < 1 && $infonavit <1 && $prestamoemp < 1 && $isr < 1)
        {
            $deductionsDetails = [
                [
                    'DeduccionType' => '011',
                    'Code' => 'Fonacot',
                    'Description' => 'Fonacot',
                    'Amount' => $fonacot 
                ]
            ];
        }
            // isr + IMMS + fonacot
            if($fonacot > 1 && $imss >= 1 && $infonavit <1 && $prestamoemp < 1 && $isr > 1)
        {
        $deductionsDetails = [
            [
                'DeduccionType' => '002',
                'Code' => 'ISR',
                'Description' => 'Impuesto Sobre la Renta',
                'Amount' => $isr
            ],
            [
                'DeduccionType' => '001',
                'Code' => 'IMSS',
                'Description' => 'Seguridad Social',
                'Amount' => $imss
            ],
            [
                'DeduccionType' => '011',
                'Code' => 'Fonacot',
                'Description' => 'Fonacot',
                'Amount' => $fonacot 
            ]
        ];
        }else if($fonacot > 1 && $imss >= 1 && $infonavit <1 && $prestamoemp < 1 && $isr < 1)
        {
            $deductionsDetails = [
                [
                    'DeduccionType' => '001',
                    'Code' => 'IMSS',
                    'Description' => 'Seguridad Social',
                    'Amount' => $imss
                ],
                [
                    'DeduccionType' => '011',
                    'Code' => 'Fonacot',
                    'Description' => 'Fonacot',
                    'Amount' => $fonacot 
                ]
            ];
        }

        // isr + prestamo -----------------------------------------------------------
        if($prestamoemp > 1 && $imss < 1 && $infonavit <1 && $fonacot < 1 && $isr > 1)
        {
        $deductionsDetails = [
            [
                'DeduccionType' => '002',
                'Code' => 'ISR',
                'Description' => 'Impuesto Sobre la Renta',
                'Amount' => $isr
            ],
            [
                'DeduccionType' => '012',
                'Code' => 'prestamo',
                'Description' => 'Prestamo Empresarial',
                'Amount' => $prestamoemp 
            ]
        ];
        }else if($prestamoemp > 1 && $imss < 1 && $infonavit <1 && $fonacot < 1 && $isr < 1)
        {
            $deductionsDetails = [
                [
                    'DeduccionType' => '012',
                    'Code' => 'prestamo',
                    'Description' => 'Prestamo Empresarial',
                    'Amount' => $prestamoemp 
                ]
            ];
        }

        //isr + imms + prestamo
        if($prestamoemp > 1 && $imss > 1 && $infonavit <1 && $fonacot < 1 && $isr > 1)
        {
        $deductionsDetails = [
            [
                'DeduccionType' => '002',
                'Code' => 'ISR',
                'Description' => 'Impuesto Sobre la Renta',
                'Amount' => $isr
            ],
            [
                'DeduccionType' => '001',
                'Code' => 'IMSS',
                'Description' => 'Seguridad Social',
                'Amount' => $imss
            ],
            [
                'DeduccionType' => '012',
                'Code' => 'prestamo',
                'Description' => 'Prestamo Empresarial',
                'Amount' => $prestamoemp 
            ]
        ];
        }else if($prestamoemp > 1 && $imss > 1 && $infonavit <1 && $fonacot < 1 && $isr < 1)
        {
            $deductionsDetails = [
                [
                    'DeduccionType' => '001',
                    'Code' => 'IMSS',
                    'Description' => 'Seguridad Social',
                    'Amount' => $imss
                ],
                [
                    'DeduccionType' => '012',
                    'Code' => 'prestamo',
                    'Description' => 'Prestamo Empresarial',
                    'Amount' => $prestamoemp 
                ]
            ];
        }
        // isr + infonavit + prestamo
            if($prestamoemp > 1 && $imss < 1 && $infonavit >1 && $fonacot < 1 && $isr > 1)
        {
        $deductionsDetails = [
            [
                'DeduccionType' => '002',
                'Code' => 'ISR',
                'Description' => 'Impuesto Sobre la Renta',
                'Amount' => $isr
            ],
            [
                'DeduccionType' => '005',
                'Code' => 'INFONAVIT',
                'Description' => 'Aportaciones a Fondo de vivienda',
                'Amount' => $infonavit
            ],
            [
                'DeduccionType' => '012',
                'Code' => 'prestamo',
                'Description' => 'Prestamo Empresarial',
                'Amount' => $prestamoemp 
            ]
        ];
        }else if($prestamoemp > 1 && $imss < 1 && $infonavit >1 && $fonacot < 1 && $isr < 1)
        {
            $deductionsDetails = [
                [
                    'DeduccionType' => '005',
                    'Code' => 'INFONAVIT',
                    'Description' => 'Aportaciones a Fondo de vivienda',
                    'Amount' => $infonavit
                ],
                [
                    'DeduccionType' => '012',
                    'Code' => 'prestamo',
                    'Description' => 'Prestamo Empresarial',
                    'Amount' => $prestamoemp 
                ]
            ];
        }
        //isr + fonacot + prestamo 
        if($prestamoemp > 1 && $imss < 1 && $infonavit <1 && $fonacot > 1 && $isr > 1)
        {
        $deductionsDetails = [
            [
                'DeduccionType' => '002',
                'Code' => 'ISR',
                'Description' => 'Impuesto Sobre la Renta',
                'Amount' => $isr
            ],
            [
                'DeduccionType' => '011',
                'Code' => 'Fonacot',
                'Description' => 'Fonacot',
                'Amount' => $fonacot 
            ],
            [
                'DeduccionType' => '012',
                'Code' => 'prestamo',
                'Description' => 'Prestamo Empresarial',
                'Amount' => $prestamoemp 
            ]
        ];
        }else if($prestamoemp > 1 && $imss < 1 && $infonavit <1 && $fonacot > 1 &&  $isr < 1)
        {
            $deductionsDetails = [
                [
                    'DeduccionType' => '011',
                    'Code' => 'Fonacot',
                    'Description' => 'Fonacot',
                    'Amount' => $fonacot 
                ],
                [
                    'DeduccionType' => '012',
                    'Code' => 'prestamo',
                    'Description' => 'Prestamo Empresarial',
                    'Amount' => $prestamoemp 
                ]
            ];
        }
        // todas las deducciones isr  + infonavit + fonacot + prestamo
        if($prestamoemp > 1 && $imss < 1 && $infonavit > 1 && $fonacot > 1 && $isr > 1)
        {
        $deductionsDetails = [
            [
                'DeduccionType' => '002',
                'Code' => 'ISR',
                'Description' => 'Impuesto Sobre la Renta',
                'Amount' => $isr
            ],
            [
                'DeduccionType' => '011',
                'Code' => 'Fonacot',
                'Description' => 'Fonacot',
                'Amount' => $fonacot 
            ],
            [
                'DeduccionType' => '012',
                'Code' => 'prestamo',
                'Description' => 'Prestamo Empresarial',
                'Amount' => $prestamoemp 
            ],
            [
                'DeduccionType' => '005',
                'Code' => 'INFONAVIT',
                'Description' => 'Aportaciones a Fondo de vivienda',
                'Amount' => $infonavit
            ]
        ];
        }
        else if($prestamoemp > 1 && $imss < 1 && $infonavit > 1 && $fonacot > 1 && $isr < 1)
        {
            $deductionsDetails = [
                [
                    'DeduccionType' => '011',
                    'Code' => 'Fonacot',
                    'Description' => 'Fonacot',
                    'Amount' => $fonacot 
                ],
                [
                    'DeduccionType' => '012',
                    'Code' => 'prestamo',
                    'Description' => 'Prestamo Empresarial',
                    'Amount' => $prestamoemp 
                ],
                [
                    'DeduccionType' => '005',
                    'Code' => 'INFONAVIT',
                    'Description' => 'Aportaciones a Fondo de vivienda',
                    'Amount' => $infonavit
                ]
            ];
        }
            //deducciones isr + imss + infonavit + fonacot
            if($prestamoemp < 1 && $imss > 1 && $infonavit >1 && $fonacot > 1 && $isr > 1)
            {
            $deductionsDetails = [
                [
                    'DeduccionType' => '002',
                    'Code' => 'ISR',
                    'Description' => 'Impuesto Sobre la Renta',
                    'Amount' => $isr
                ],
                [
                    'DeduccionType' => '011',
                    'Code' => 'Fonacot',
                    'Description' => 'Fonacot',
                    'Amount' => $fonacot 
                ],
                [
                'DeduccionType' => '001',
                'Code' => 'IMSS',
                'Description' => 'Seguridad Social',
                'Amount' => $imss
                ],
                [
                    'DeduccionType' => '005',
                    'Code' => 'INFONAVIT',
                    'Description' => 'Aportaciones a Fondo de vivienda',
                    'Amount' => $infonavit
                ]
            ];
            }
            else if($prestamoemp < 1 && $imss > 1 && $infonavit >1 && $fonacot > 1 && $isr < 1)
            {
            $deductionsDetails = [
                [
                    'DeduccionType' => '011',
                    'Code' => 'Fonacot',
                    'Description' => 'Fonacot',
                    'Amount' => $fonacot 
                ],
                [
                    'DeduccionType' => '001',
                    'Code' => 'IMSS',
                    'Description' => 'Seguridad Social',
                    'Amount' => $imss
                ],
                [
                    'DeduccionType' => '005',
                    'Code' => 'INFONAVIT',
                    'Description' => 'Aportaciones a Fondo de vivienda',
                    'Amount' => $infonavit
                ]
            ];
            }
            // todas las deducciones isr + imss + infonavit + prestamo
            if($prestamoemp > 1 && $imss > 1 && $infonavit >1 && $fonacot < 1 && $isr > 1)
            {
            $deductionsDetails = [
                [
                    'DeduccionType' => '002',
                    'Code' => 'ISR',
                    'Description' => 'Impuesto Sobre la Renta',
                    'Amount' => $isr
                ],
                [
                'DeduccionType' => '012',
                'Code' => 'prestamo',
                'Description' => 'Prestamo Empresarial',
                'Amount' => $prestamoemp 
                ],
                [
                'DeduccionType' => '001',
                'Code' => 'IMSS',
                'Description' => 'Seguridad Social',
                'Amount' => $imss
                ],
                [
                    'DeduccionType' => '005',
                    'Code' => 'INFONAVIT',
                    'Description' => 'Aportaciones a Fondo de vivienda',
                    'Amount' => $infonavit
                ]
            ];
            } 
            else if($prestamoemp > 1 && $imss > 1 && $infonavit >1 && $fonacot < 1 && $isr < 1)
            {
            $deductionsDetails = [
                [
                    'DeduccionType' => '012',
                    'Code' => 'prestamo',
                    'Description' => 'Prestamo Empresarial',
                    'Amount' => $prestamoemp 
                    ],
                    [
                    'DeduccionType' => '001',
                    'Code' => 'IMSS',
                    'Description' => 'Seguridad Social',
                    'Amount' => $imss
                    ],
                    [
                        'DeduccionType' => '005',
                        'Code' => 'INFONAVIT',
                        'Description' => 'Aportaciones a Fondo de vivienda',
                        'Amount' => $infonavit
                    ]
            ];
            }
                // todas las deducciones isr + fonacot + infonavit + prestamo
                if($prestamoemp > 1 && $imss < 1 && $infonavit > 1 && $fonacot > 1 && $isr > 1)
                {
                $deductionsDetails = [
                    [
                        'DeduccionType' => '002',
                        'Code' => 'ISR',
                        'Description' => 'Impuesto Sobre la Renta',
                        'Amount' => $isr
                    ],
                    [
                    'DeduccionType' => '012',
                    'Code' => 'prestamo',
                    'Description' => 'Prestamo Empresarial',
                    'Amount' => $prestamoemp 
                    ],
                    [
                    'DeduccionType' => '011',
                    'Code' => 'Fonacot',
                    'Description' => 'Fonacot',
                    'Amount' => $fonacot 
                    ],
                    [
                        'DeduccionType' => '005',
                        'Code' => 'INFONAVIT',
                        'Description' => 'Aportaciones a Fondo de vivienda',
                        'Amount' => $infonavit
                    ]
                ];
                }
                else if($prestamoemp > 1 && $imss < 1 && $infonavit > 1 && $fonacot > 1 && $isr < 1)
                {
                $deductionsDetails = [
                    [
                        'DeduccionType' => '012',
                        'Code' => 'prestamo',
                        'Description' => 'Prestamo Empresarial',
                        'Amount' => $prestamoemp 
                        ],
                        [
                        'DeduccionType' => '011',
                        'Code' => 'Fonacot',
                        'Description' => 'Fonacot',
                        'Amount' => $fonacot 
                        ],
                        [
                            'DeduccionType' => '005',
                            'Code' => 'INFONAVIT',
                            'Description' => 'Aportaciones a Fondo de vivienda',
                            'Amount' => $infonavit
                        ]
                ];
                }
                // todas las deducciones isr + fonacot + imss + prestamo
                if($prestamoemp > 1 && $imss < 1 && $infonavit < 1 && $fonacot > 1 && $isr > 1)
                {
                $deductionsDetails = [
                    [
                        'DeduccionType' => '002',
                        'Code' => 'ISR',
                        'Description' => 'Impuesto Sobre la Renta',
                        'Amount' => $isr
                    ],
                    [
                        'DeduccionType' => '012',
                        'Code' => 'prestamo',
                        'Description' => 'Prestamo Empresarial',
                        'Amount' => $prestamoemp 
                    ],
                    [
                        'DeduccionType' => '011',
                        'Code' => 'Fonacot',
                        'Description' => 'Fonacot',
                        'Amount' => $fonacot 
                    ],
                    [
                    'DeduccionType' => '001',
                    'Code' => 'IMSS',
                    'Description' => 'Seguridad Social',
                    'Amount' => $imss
                    ]
                ];
                }
                else if($prestamoemp > 1 && $imss < 1 && $infonavit < 1 && $fonacot > 1 && $isr < 1)
                {
                $deductionsDetails = [
                    [
                        'DeduccionType' => '012',
                        'Code' => 'prestamo',
                        'Description' => 'Prestamo Empresarial',
                        'Amount' => $prestamoemp 
                        ],
                        [
                        'DeduccionType' => '011',
                        'Code' => 'Fonacot',
                        'Description' => 'Fonacot',
                        'Amount' => $fonacot 
                        ],
                        [
                        'DeduccionType' => '001',
                        'Code' => 'IMSS',
                        'Description' => 'Seguridad Social',
                        'Amount' => $imss
                        ]
                ];
                }
                    // todas las deducciones isr + fonacot + imss + prestamo
                    if($prestamoemp > 1 && $imss > 1 && $infonavit > 1 && $fonacot > 1 && $isr > 1)
                    {
                    $deductionsDetails = [
                        [
                            'DeduccionType' => '002',
                            'Code' => 'ISR',
                            'Description' => 'Impuesto Sobre la Renta',
                            'Amount' => $isr
                        ],
                        [
                            'DeduccionType' => '012',
                            'Code' => 'prestamo',
                            'Description' => 'Prestamo Empresarial',
                            'Amount' => $prestamoemp 
                        ],
                        [
                            'DeduccionType' => '011',
                            'Code' => 'Fonacot',
                            'Description' => 'Fonacot',
                            'Amount' => $fonacot 
                        ],
                        [
                            'DeduccionType' => '001',
                            'Code' => 'IMSS',
                            'Description' => 'Seguridad Social',
                            'Amount' => $imss
                        ],
                        [
                        'DeduccionType' => '005',
                        'Code' => 'INFONAVIT',
                        'Description' => 'Aportaciones a Fondo de vivienda',
                        'Amount' => $infonavit
                        ]
                    ];
                    }
                    else if($prestamoemp > 1 && $imss > 1 && $infonavit > 1 && $fonacot > 1 && $isr < 1)
                    {
                        $deductionsDetails = [
                            [
                                'DeduccionType' => '012',
                                'Code' => 'prestamo',
                                'Description' => 'Prestamo Empresarial',
                                'Amount' => $prestamoemp 
                                ],
                                [
                                'DeduccionType' => '011',
                                'Code' => 'Fonacot',
                                'Description' => 'Fonacot',
                                'Amount' => $fonacot 
                                ],
                                [
                                'DeduccionType' => '001',
                                'Code' => 'IMSS',
                                'Description' => 'Seguridad Social',
                                'Amount' => $imss
                                ],
                                [
                                'DeduccionType' => '005',
                                'Code' => 'INFONAVIT',
                                'Description' => 'Aportaciones a Fondo de vivienda',
                                'Amount' => $infonavit
                                ]
                        ];
                    }
        return $deductionsDetails;
    }

    public function vernominatimbrada($id)
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $varnominas =  $this->obtenernominasporid($id);
        $empleadosnotimbrados = $this->Listadoempleadosnotimbrados($id);
        $nominatimbrada =  $this->obtnernominatimbrada($id);
        $validaTimbradofallido =  $this->validaTimbradofallido($id);
        $obtnertimbradosFallidos =  $this->obtnertimbradosFallidos($id);
        
        $empleadostimbrados =  DB::select("SELECT id_tblnominas_pagodet from  recibos_nomina;");
        // verificar si hay datos facturados o ya se timbro la nomina
        $comparacion = $this->compararIdsNominas($varnominas, $empleadostimbrados);
        //dd($comparacion);

        //     if ($comparacion['coincidentes'] == 0) {
        //     $mensaje = "No Esta Timbrada La Nomina";
            
        //     return Redirect::back()
        //            ->withInput()
        //            ->withErrors(['comparacion_ids' => $mensaje])
        //            ->with('comparacion_detalle', $comparacion);
        // }
    
   
        //dd($comparacion);
        //return $id. $empleadosnotimbrados;

        return view('nominas/vernominatimbrada', compact('varpantallas', 'varsubmenus', 'varnominas',
        'empleadosnotimbrados','nominatimbrada','validaTimbradofallido','obtnertimbradosFallidos'));

    }

    public function verfacturanomina($id)
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $datosfactura = DB::select("SELECT id, id_facturafacil FROM `recibos_nomina` where id_tblnominas_pagodet = ? order by(id) desc limit 1",[$id]);

       // dd($datosfactura);

        if(!empty($datosfactura) && isset($datosfactura[0]->id))
        {
            //dd($datosfactura);
            // Configura tu API Key o tus credenciales
            $usuario = env('USER_FAC'); // Tu usuario Facturama Sandbox
            $password = env('PWD'); // Tu contraseña Facturama Sandbox
            $cfdi = $datosfactura[0]->id_facturafacil;

            $client = new Client([
                'base_uri' => 'https://api.facturama.mx',
                'auth' => [$usuario, $password] 
            ]);

            try {
                $response = $client->request('GET', "cfdi/pdf/payroll/{$cfdi}", [
                    'headers' => [
                        'Accept' => 'application/json',
                    ]
                ]);

                
                
                if ($response->getStatusCode() == 200) {
                    $pdfContent = $response->getBody()->getContents();

                    // Codificar el contenido binario a base64
                // $pdfBase64 = base64_encode($pdfContent);

                    // Devolver el contenido en un array como JSON
                    $data = json_decode($pdfContent, true);
                    $cadena = $data['Content'];
                    //dd($cadena);
                    
                    return  $this->obtenerfacturanomina($cadena);
                    
                    echo $pdfBase64;
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se pudo obtener el PDF'
                    ], 400);
                }
        
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
         
        }
        else
        {
            //buscar en la tabla de facturas no timbradas
            $nofacturado = DB::select("select * from tblnomina_notimbrados where id_nominapago_det = ?;",[$id]);
            echo "No hay factura";
            return back()->with("Errofac","no guardado correctamente");
            
        }

    }

    public function obtenerfacturanomina($cadena)
    {

        // Cadena Base64 recibida
        $base64_string = $cadena;

        // (Opcional) Elimina el encabezado si viene en formato data URI
        $base64_string = preg_replace('/^data:application\/pdf;base64,/', '', $base64_string);

        // Decodifica la cadena Base64
        $pdf_data = base64_decode($base64_string);

        // Devolver el PDF al navegador como respuesta
        return Response::make($pdf_data, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Comprobante_Nomina.pdf"',
            'Content-Length' => strlen($pdf_data),
        ]);
    }

    public function validardatosSat($Rfc,$nombre,$cp,$regimen_fiscal )
    {
        try {
            $usuario = env('USER_FAC'); // Tu usuario Facturama Sandbox
            $password = env('PWD'); // Tu contraseña Facturama Sandbox

            $client = new Client();
            

            
            $headers = [
                'Authorization' => 'Basic '.base64_encode($usuario.':'. $password),
                'Cookie' => '.ASPXAUTH=426A9A94F213C661E70390456292BFA29FABCAB6B41BDF3902BDC145A7D6C4C6146E12A7E7F90CA5B831C27B00B8FEDD8105C1901C32B957618283B12E391EE3147C156D63DC6E2CFC904C4650C329C5E228462BD83AFE6D88777123CFEDC251'
            ];

            $body = [
                'Rfc' => 'EKU9003173C9',
                'Name' => 'ESCUELA KEMPER URGATE',
                'ZipCode' => '42501',
                'FiscalRegime' => '601'
            ];

            //cambiar url de pruebas por la de produccion
            $response = $client->post('https://api.facturama.mx/customers/validate', [
                'headers' => $headers,
                'json' => $body
            ]);

            $responseBody = $response->getBody()->getContents();
            
            return response()->json([
                'success' => true,
                'data' => json_decode($responseBody)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

public function verificarCalculosNominas($nominasCollection)
{
    $errores = [];
    
    foreach ($nominasCollection as $index => $nomina) {
        // Calcular sueldo total (percepciones)
        $sueldoTotal = $nomina->sueldo_fiscal  //1
                      + $nomina->otros //3648
                      + $nomina->despensa //0
                      + $nomina->percepcion_extraordinaria //2,261.22
                      + $nomina->horas_extras_pago_e
                      + $nomina->pago_subsidio ;//0
        // si el 
        // Calcular total deducciones
        $totalDeducciones = $nomina->total_deudores 
                          + $nomina->pago_infonavit 
                          + $nomina->pago_imss 
                          + $nomina->pago_isr 
                          + $nomina->fonacot;
        
        // Calcular saldo final
        $saldoFinal = $sueldoTotal - $totalDeducciones;
        
        if ($saldoFinal < 0) {
            // Desglose de percepciones
            $percepciones = [
                'sueldo_fiscal' => $nomina->sueldo_fiscal,
                'otros' => $nomina->otros,
                'despensa' => $nomina->despensa,
                'percepcion_extraordinaria' => $nomina->percepcion_extraordinaria,
                'horas_extras_pago_e' => $nomina->horas_extras_pago_e
            ];
            
            // Desglose de deducciones
            $deducciones = [
                'total_deudores' => $nomina->total_deudores,
                'pago_infonavit' => $nomina->pago_infonavit,
                'pago_imss' => $nomina->pago_imss,
                'pago_isr' => $nomina->pago_isr,
                'pago_subsidio' => $nomina->pago_subsidio,
                'fonacot' => $nomina->fonacot
            ];
            
            $errores[] = [
                'empleado' => $nomina->primer_nombre . ' ' . $nomina->apellido_paterno,
                'saldo_negativo' => number_format($saldoFinal, 2),
                'sueldo_total' => number_format($sueldoTotal, 2),
                'total_deducciones' => number_format($totalDeducciones, 2),
                'percepciones' => $percepciones,
                'deducciones' => $deducciones,
                'indice' => $index
            ];
        }
    }
    
    if (!empty($errores)) {
        $mensaje = "Errores en los cálculos de facturación. Saldos negativos encontrados:\n\n";
        
        foreach ($errores as $error) {
            $mensaje .= "═══════════════════════════════════════════════════════════════\n";
            $mensaje .= "EMPLEADO: {$error['empleado']}\n";
            $mensaje .= "Saldo negativo: \${$error['saldo_negativo']}\n\n";
            
            // Desglose de percepciones
            $mensaje .= "PERCEPCIONES:\n";
            $mensaje .= "• Sueldo fiscal: $" . number_format($error['percepciones']['sueldo_fiscal'], 2) . "\n";
            $mensaje .= "• Otros: $" . number_format($error['percepciones']['otros'], 2) . "\n";
            $mensaje .= "• Despensa: $" . number_format($error['percepciones']['despensa'], 2) . "\n";
            $mensaje .= "• Percepción extraordinaria: $" . number_format($error['percepciones']['percepcion_extraordinaria'], 2) . "\n";
            $mensaje .= "• Horas extras: $" . number_format($error['percepciones']['horas_extras_pago_e'], 2) . "\n";
            $mensaje .= "TOTAL PERCEPCIONES: \${$error['sueldo_total']}\n\n";
            
            // Desglose de deducciones
            $mensaje .= "DEDUCCIONES:\n";
            $mensaje .= "• Total deudores: $" . number_format($error['deducciones']['total_deudores'], 2) . "\n";
            $mensaje .= "• Pago Infonavit: $" . number_format($error['deducciones']['pago_infonavit'], 2) . "\n";
            $mensaje .= "• Pago IMSS: $" . number_format($error['deducciones']['pago_imss'], 2) . "\n";
            $mensaje .= "• Pago ISR: $" . number_format($error['deducciones']['pago_isr'], 2) . "\n";
            $mensaje .= "• Pago subsidio: $" . number_format($error['deducciones']['pago_subsidio'], 2) . "\n";
            $mensaje .= "• Fonacot: $" . number_format($error['deducciones']['fonacot'], 2) . "\n";
            $mensaje .= "TOTAL DEDUCCIONES: \${$error['total_deducciones']}\n\n";
        }
        
        return Redirect::back()
               ->withInput()
               ->withErrors(['calculos' => $mensaje])
               ->with('errores_detallados', $errores);
    }
    
    return null; // No hay errores
}

public function compararIdsNominas($nominasCollection, $segundoArreglo)
{
    // Convertir a colecciones para mejor manejo
    $idsNominas = $nominasCollection->pluck('id');
    $idsSegundoArreglo = collect($segundoArreglo)->pluck('id_tblnominas_pagodet');
    
    // Encontrar coincidencias y diferencias
    $idsCoincidentes = $idsNominas->intersect($idsSegundoArreglo);
    $idsFaltantes = $idsNominas->diff($idsSegundoArreglo);
    
    // Obtener detalles de los faltantes
    $detallesFaltantes = $nominasCollection
        ->whereIn('id', $idsFaltantes)
        ->map(function($nomina) {
            return [
                'id' => $nomina->id,
                'empleado' => $nomina->primer_nombre.' '.$nomina->apellido_paterno,
                'puesto' => $nomina->puesto
            ];
        });
    
    // Preparar resultado
    $resultado = [
        'total' => $idsNominas->count(),
        'coincidentes' => $idsCoincidentes->count(),
        'faltantes' => $idsFaltantes->count(),
        'detalles_faltantes' => $detallesFaltantes->values()->toArray(),
        'porcentaje_coincidencia' => round(($idsCoincidentes->count() / $idsNominas->count()) * 100, 2)
    ];
    
    return $resultado;
}

// Función para calcular la parte sujeta a impuestos de la prima vacacional
function calcularPrimaVacacionalSujetaAImpuestos($primaVacacional) {
    $UMA = (float)conceptos_nomina::where('nombre', 'uma_diaria')->first()->valor;
    $umaDiaria = $UMA;
    $montoLibreImpuestos = 15 * $umaDiaria;

    if ($primaVacacional <= $montoLibreImpuestos) {
        return 0; // No hay impuestos si no excede el monto libre
    } else {
        return $primaVacacional - $montoLibreImpuestos; // Solo el exceso paga impuestos
    }
}

function calcularImpuestosPrimaVacacional2($primaVacacional) {
    // Calcular el monto libre de impuestos (15 UMAs)
    $UMA = (float)conceptos_nomina::where('nombre', 'uma_diaria')->first()->valor;
    $umaDiaria = $UMA;
    $montoLibreImpuestos = 15 * $umaDiaria;
    //dd($umaDiaria," monto libre de impuestos: ".$montoLibreImpuestos);
    // Calcular la parte sujeta a impuestos
    $montoSujetoImpuestos = max($primaVacacional - $montoLibreImpuestos, 0);
    
    // Asegurar que el monto libre no sea mayor que la prima total
    $montoLibre = min($primaVacacional, $montoLibreImpuestos);
    
    return [
        'uma_diaria' => $umaDiaria,
        'prima_vacacional' => $primaVacacional,
        'monto_libre_impuestos' => $montoLibre,
        'monto_sujeto_impuestos' => $montoSujetoImpuestos,
        'explicacion' => $montoSujetoImpuestos > 0 ? 
            "La prima excede el monto libre en {$montoSujetoImpuestos}" : 
            "Toda la prima está libre de impuestos"
    ];
}

/*-------------------------------------------------------------------------------
--------------------------------------------------------------------------------- */
public function crearFacturaCFDI40($datosFactura, $id_servicio_enc, $tipo_servicio)
{
    // Configuración de credenciales
    $username = env('USER_FAC');
    $password = env('PWD');

    // Configurar cliente HTTP
    $client = new Client([
        'base_uri' => 'https://apisandbox.facturama.mx',
        'timeout'  => 3000.0,
        'auth' => [$username, $password],
        'verify' => false
    ]);

    // Estructura base de la factura CFDI 4.0
    $data = [
        'CfdiType' => $datosFactura['CfdiType'] ?? 'I', // I = Ingreso
        'PaymentForm' => $datosFactura['PaymentForm'] ?? '01', // 01 = Efectivo
        'PaymentMethod' => $datosFactura['PaymentMethod'] ?? 'PUE', // PUE = Pago en una sola exhibición
        'ExpeditionPlace' => $datosFactura['ExpeditionPlace'], // Código postal del lugar de expedición
        'Date' => $datosFactura['Date'] ?? date('Y-m-d H:i:s'),
        'Folio' => $datosFactura['Folio'] ?? null,
        'Issuer' => [
            'FiscalRegime' => $datosFactura['Issuer']['FiscalRegime'],
            'Rfc' => $datosFactura['Issuer']['Rfc'],
            'Name' => $datosFactura['Issuer']['Name']
        ],
        'Receiver' => [
            'Rfc' => $datosFactura['Receiver']['Rfc'],
            'CfdiUse' => $datosFactura['Receiver']['CfdiUse'] ?? 'G03', // Uso por defecto
            'Name' => $datosFactura['Receiver']['Name'],
            'FiscalRegime' => $datosFactura['Receiver']['FiscalRegime'],
            'TaxZipCode' => $datosFactura['Receiver']['TaxZipCode'],
            'Address' => [
                'Street' => $datosFactura['Receiver']['Address']['Street'],
                'ExteriorNumber' => $datosFactura['Receiver']['Address']['ExteriorNumber'],
                'InteriorNumber' => $datosFactura['Receiver']['Address']['InteriorNumber'] ?? '',
                'Neighborhood' => $datosFactura['Receiver']['Address']['Neighborhood'],
                'ZipCode' => $datosFactura['Receiver']['Address']['ZipCode'],
                'Municipality' => $datosFactura['Receiver']['Address']['Municipality'],
                'State' => $datosFactura['Receiver']['Address']['State'],
                'Country' => $datosFactura['Receiver']['Address']['Country'] ?? 'México'
            ]
        ],
        'Items' => []
    ];

    
    //return response()->json(['success' => false, 'message' => json_encode($data)]);
    // Agregar items (conceptos)
    foreach ($datosFactura['Items'] as $item) {
        $taxes = [];
        
        // Procesar impuestos (solo si existen)
        if (!empty($item['Taxes']) && is_array($item['Taxes'])) {
            foreach ($item['Taxes'] as $tax) {
                $taxes[] = [
                    'Total' => $tax['Total'],
                    'Name' => $tax['Name'],
                    'Base' => $tax['Base'],
                    'Rate' => $tax['Rate'],
                    'IsRetention' => $tax['IsRetention'] ?? false
                ];
            }
        }

        $itemData = [
            'ProductCode' => $item['ProductCode'],
            'Description' => $item['Description'],
            'UnitCode' => $item['UnitCode'],
            'Quantity' => $item['Quantity'],
            'UnitPrice' => $item['UnitPrice'],
            'Subtotal' => $item['Subtotal'],
            'TaxObject' => $item['TaxObject'] ?? '02',
            'Total' => $item['Total']
        ];

        if (!empty($taxes)) {
            $itemData['Taxes'] = $taxes;
        }

        $data['Items'][] = $itemData;
    }

    if (!empty($datosFactura['Relations'])) {
        $data['Relations'] = $datosFactura['Relations'];
    }

    $headers = ['Content-Type' => 'application/json'];
    //dd($tipo_servicio);
    try {
        $response = $client->post('/3/cfdis', [
            'headers' => $headers,
            'json' => $data
        ]);
         //dd($response); // Comentado para permitir que continúe la ejecución
         
        $datos = json_decode($response->getBody(), true);

        $finanzasService = app(\App\Services\FacturacionFinanzasService::class);
        $metodoPago = $datosFactura['PaymentMethod'] ?? $datos['PaymentMethod'] ?? 'PUE';
        $estadoCobranza = $finanzasService->resolverEstadoCobranzaInicial($metodoPago);

        // Guardar datos en la tabla tblfacturacionproductos
        $facturacionProducto = facturacionproductos::create([
            'facturama_id' => $datos['Id'] ?? $datos['id'] ?? null,
            'folio' => $datos['Folio'] ?? $datosFactura['Folio'] ?? null,
            'date' => date('Y-m-d', strtotime($datos['Date'] ?? $datosFactura['Date'] ?? now())),
            'reciver_rfc' => $datosFactura['Receiver']['Rfc'] ?? null,
            'reciver_nombre' => $datosFactura['Receiver']['Name'] ?? null,
            'subtotal' => $datos['Subtotal'] ?? 0,
            'total' => $datos['Total'] ?? 0,
            'Uuid' => $datos['Complement']['TaxStamp']['Uuid'] ?? null,
            'CfdiSign' => $datos['Complement']['TaxStamp']['CfdiSign'] ?? null,
            'SatCertNumber' => $datos['Complement']['TaxStamp']['SatCertNumber'] ?? null,
            'SatSign' => $datos['Complement']['TaxStamp']['SatSign'] ?? null,
            'RfcProvCertif' => $datos['Complement']['TaxStamp']['RfcProvCertif'] ?? null,
            'OriginalString' => $datos['OriginalString'] ?? null,
            'estado' => $estadoCobranza,
            'cancelada' => 'A',
            'id_serv_enc' => $id_servicio_enc,
            'tipo_serv' => $tipo_servicio,
            'metodo_pago' => $metodoPago,
            'forma_pago' => $datosFactura['PaymentForm'] ?? $datos['PaymentForm'] ?? '99',
            'referencia_factura' => $datosFactura['referencia_factura'] ?? null,
        ]);

        try {
            $finanzasService->procesarPostTimbrado($facturacionProducto);
        } catch (\Throwable $e) {
            Log::error('Error al integrar factura con Finanzas: ' . $e->getMessage());
        }

        return json_decode($response->getBody(), true);
        
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            return ['error' => $this->extraerErrorFacturama($e)];
        }

        return ['error' => $e->getMessage()];
    }
}
// ejemplo de como timbrar una factura de productos.
public function FacturaProductos()
{
    // Ejemplo de datos para crear una factura CFDI 4.0
    $datosFactura = [
        'CfdiType' => 'I',
        'PaymentForm' => '01',
        'PaymentMethod' => 'PUE',
        'ExpeditionPlace' => '27110',
        'Date' => '2025-06-18 18:09:00',
        'Folio' => 1,
        'Issuer' => [
            'FiscalRegime' => '612',
            'Rfc' => 'MOOE920522PM8',
            'Name' => 'erik alan morenno orona'
        ],
        'Receiver' => [
            'Rfc' => 'MOOG940308IS2',
            'CfdiUse' => 'CN01',
            'Name' => 'GILBERTO ALEJANDRO MORENO ORONA',
            'FiscalRegime' => '605',
            'TaxZipCode' => '27110',
            'Address' => [
                'Street' => 'aguilas',
                'ExteriorNumber' => '218',
                'InteriorNumber' => 'A',
                'Neighborhood' => 'jacarandas',
                'ZipCode' => '27110',
                'Municipality' => 'TORREON',
                'State' => 'COAHUILA',
                'Country' => 'México'
            ]
        ],
        'Items' => [
            [
                'ProductCode' => '25173108',
                'Description' => 'GPS estandar pruebas',
                'UnitCode' => 'E48',
                'Quantity' => 1.0,
                'UnitPrice' => 100.0,
                'Subtotal' => 100.00,
                'TaxObject' => '02',
                'Taxes' => [
                    [
                        'Total' => 16,
                        'Name' => 'IVA',
                        'Base' => 100,
                        'Rate' => 0.16,
                        'IsRetention' => false
                    ]
                ],
                'Total' => 116
            ],
            [
                'ProductCode' => '25173108',
                'Description' => 'GPS estandar pruebas 2',
                'UnitCode' => 'E48',
                'Quantity' => 1.0,
                'UnitPrice' => 100.0,
                'Subtotal' => 100.00,
                'TaxObject' => '02',
                'Taxes' => [
                    [
                        'Total' => 16,
                        'Name' => 'IVA',
                        'Base' => 100,
                        'Rate' => 0.16,
                        'IsRetention' => false
                    ]
                ],
                'Total' => 116
            ]
        ]
    ];

    // Llamar al método
    $resultado = $this->crearFacturaCFDI40($datosFactura);
}

public function facturacion2(int $id)
{
    $varpantallas = $this->Traermenuenc();
    $varsubmenus = $this->Traermenudet();
    $serv_prod = DB::select("select * from tblservicios_productos where id_servicio_enc = ?;",[$id]);
    $datosFacturacion = DB::table('tblservicios_enc as se')
    ->join('tblservicios_productos as sp', 'se.id', '=', 'sp.id_servicio_enc')
    ->join('tblproductos as p', 'sp.id_producto', '=', 'p.id')
    ->select(
        'se.id',
        'se.folio',
        'se.nombre as nombre_servicio',
        'se.fecha_inicio',
        'se.fecha_limite',
        'p.sku',
        'p.nombre as nombre_producto',
        'p.precio_unitario',
        'sp.cantidad_total',
        DB::raw('(p.precio_unitario * sp.cantidad_total) as importe'),
        DB::raw('SUM(p.precio_unitario * sp.cantidad_total) over () as subtotal'),
        DB::raw('SUM(p.precio_unitario * sp.cantidad_total * 0.16) over () as iva'),
        DB::raw('SUM(p.precio_unitario * sp.cantidad_total * 1.16) over () as total')
    )
    ->where('se.id', $id)
    ->get();

    
    //return view('Servicios.facturacion', compact('varpantallas', 'varsubmenus', 'serv_prod'));
}
/* Notas para mañana hayq ue buscar la manera de hacer 2 consultas uno donde vienen los sumisnitros y otro donde me regrese los coneptops ya se crear 
una nuevo metodo para que instercale entre los que si tienen conceptops y los que solo tienen suministros */

    /**
     * Obtiene los datos del emisor desde tbl_datos_fiscales_empresa para la vista de facturación.
     */
    private function obtenerDatosEmisorFacturacion(): array
    {
        $fiscal = DatosFiscalesEmpresa::query()
            ->where('estatus', 'ACTIVO')
            ->orderByDesc('id')
            ->first();

        if (!$fiscal) {
            $fiscal = DatosFiscalesEmpresa::query()->orderByDesc('id')->first();
        }

        if ($fiscal) {
            $partesDireccion = array_filter([
                trim(($fiscal->calle ?? '') . ' ' . ($fiscal->numero_exterior ?? '')),
                !empty($fiscal->numero_interior) ? 'Int. ' . $fiscal->numero_interior : null,
                $fiscal->colonia ?? null,
                $fiscal->municipio ?? null,
                $fiscal->estado ?? null,
                $fiscal->pais ?? 'Mexico',
            ]);
            $direccion = implode(', ', $partesDireccion);
            if (!empty($fiscal->codigo_postal)) {
                $direccion .= ($direccion ? '. ' : '') . 'C.P. ' . $fiscal->codigo_postal;
            }

            $regimenFiscal = $fiscal->regimen_fiscal ?? '601';
            if (!empty($fiscal->regimen_fiscal_descripcion)) {
                $regimenFiscal .= ' - ' . $fiscal->regimen_fiscal_descripcion;
            }

            return [
                'nombre' => $fiscal->razon_social ?? '',
                'rfc' => $fiscal->rfc ?? '',
                'direccion' => $direccion,
                'lugar_expedicion' => $fiscal->codigo_postal ?? '',
                'regimen_fiscal' => $regimenFiscal,
                'telefono' => $fiscal->telefono ?? '',
            ];
        }

        return [
            'nombre' => env('FACTURAMA_EMISOR_NOMBRE', 'UMMININGN'),
            'rfc' => env('FACTURAMA_EMISOR_RFC', 'UMM200127ME3'),
            'direccion' => 'BLVD. PASEO DEL ALGODON 325 LOS VIÑEDOS TORREÓN, COAHUILA. Mexico. C.P. ' . env('FACTURAMA_EXPEDITION_PLACE', '27023'),
            'lugar_expedicion' => env('FACTURAMA_EXPEDITION_PLACE', '27023'),
            'regimen_fiscal' => env('FACTURAMA_EMISOR_REGIMEN', '601') . ' - General de Ley Personas Morales',
            'telefono' => '',
        ];
    }

    private function resolverClaveSatDesdeProducto(?string $claveSat, ?string $sku = null, string $fallback = '01010101'): string
    {
        $clave = trim((string) ($claveSat ?? ''));
        if ($clave !== '') {
            return $clave;
        }

        $skuVal = trim((string) ($sku ?? ''));
        return $skuVal !== '' ? $skuVal : $fallback;
    }

    private function resolverUnidadMedSatDesdeProducto(?string $unidadMedSat, string $fallback = 'H87'): string
    {
        $unidad = trim((string) ($unidadMedSat ?? ''));
        return $unidad !== '' ? $unidad : $fallback;
    }

public function facturacion(int $id, $servicio)
{
      // Si el tipo de servicio es "Proyecto", redirigir al método específico
    if (strtolower($servicio) === 'proyecto') {
        // Para proyectos, necesitamos obtener el id_division
        // Por ahora redirigimos con id_division = 0 (todas las divisiones)
        return redirect()->route('facturacion.proyecto', [
            'id' => $id, 
            'servicio' => $servicio, 
            'id_division' => 0
        ]);
    }

    if (in_array(strtolower((string) $servicio), ['pedido', 'pedido_venta'], true)) {
        return $this->facturacionPedido($id);
    }
    
    $varpantallas = $this->Traermenuenc();
    $varsubmenus = $this->Traermenudet();
    $ServDet = DB::table('tblservicios_det as sd')
    ->where('id_servicio_enc', $id)
    ->get();
    //dd($ServDet[0]->monto_proyectado);
    // Obtener datos del servicio y sus productos relacionados
    $datosFacturacion = DB::table('tblservicios_enc as se')
        ->join('tblservicios_productos as sp', 'se.id', '=', 'sp.id_servicio_enc')
        ->join('tblproductos as p', 'sp.id_producto', '=', 'p.id')
        ->join('tblclientes as c', 'se.id_cliente', '=', 'c.id')
        ->select(
            'se.id',
            'se.folio',
            'se.nombre as nombre_servicio',
            'se.fecha_inicio',
            'se.fecha_limite',
            'se.costo_externo',
            'p.sku',
            'p.clave_sat',
            'p.Unidad_med_sat as unidad_med_sat',
            'p.nombre as nombre_producto',
            'p.precio_unitario',
            'sp.cantidad_total',
            DB::raw('(p.precio_unitario * sp.cantidad_total) as importe'),
            DB::raw('SUM(p.precio_unitario * sp.cantidad_total) over () as subtotal'),
            DB::raw('SUM(p.precio_unitario * sp.cantidad_total * 0.16) over () as iva'),
            DB::raw('SUM(p.precio_unitario * sp.cantidad_total * 1.16) over () as total'),
            // Datos del cliente
            'c.nombre as cliente_nombre',
            'c.razon_social as cliente_razon_social',
            'c.rfc as cliente_rfc',
            'c.telefono as cliente_telefono',
            'c.correo_electronico as cliente_email',
            'c.calle as cliente_calle',
            'c.numero_ext as cliente_numero_ext',
            'c.numero_int as cliente_numero_int',
            'c.colonia as cliente_colonia',
            'c.cp as cliente_cp'
        )
        ->where('se.id', $id)
        ->get();

        $obtenerconceptos = DB::table('tblservicios_integracion_conceptos as sic')
        ->join('tblconceptos_servicios as cs', 'sic.id_concepto', '=', 'cs.id')
        ->where('sic.id_servicio_enc', $id)
        ->get();

        //dd($obtenerconceptos);

    if ($datosFacturacion->isEmpty()) {
        return redirect()->back()->with('error', 'No se encontraron datos para la facturación');
    }

    // Determinar qué conceptos mostrar según el tipo de servicio
    $conceptos = collect();
    $subtotal = 0;
    $iva = 0;
    $total = 0;
    
    // Verificar si el servicio contiene la palabra "Integración" (insensible a mayúsculas/minúsculas)
    if (stripos($servicio, 'integracion') !== false || stripos($servicio, 'integración') !== false) {
        // Para servicios de integración: solo conceptos de $obtenerconceptos
        $conceptos = $obtenerconceptos->map(function($item) {
            return [
                'producto' => $item->nombre ?? 'CONCEPTO',
                'cantidad' => 1,
                'unidad' => 'PZA',
                'concepto' => $item->descripcion ?? 'Concepto de servicio',
                'precio' => number_format($item->monto ?? 0, 2),
                'importe' => number_format($item->monto ?? 0, 2)
            ];
        });
        
        // Calcular totales basándose en $obtenerconceptos
        $subtotal = $ServDet[0]->monto_proyectado ?? 0;
        $iva = $subtotal * 0.16;
        $total = $subtotal + $iva;
        
    } elseif (strtolower($servicio) === 'suministros') {
        // Para suministros: solo productos de $datosFacturacion
        $conceptos = $datosFacturacion->map(function($item) {
            return [
                'producto' => $this->resolverClaveSatDesdeProducto($item->clave_sat ?? null, $item->sku ?? null),
                'cantidad' => $item->cantidad_total,
                'unidad' => $this->resolverUnidadMedSatDesdeProducto($item->unidad_med_sat ?? null),
                'concepto' => $item->nombre_producto,
                'precio' => number_format($item->precio_unitario, 2),
                'importe' => number_format($item->importe, 2)
            ];
        });
        
        // Usar totales de $datosFacturacion
        $subtotal = $datosFacturacion[0]->subtotal ?? 0;
        $iva = $datosFacturacion[0]->iva ?? 0;
        $total = $datosFacturacion[0]->total ?? 0;
        
    } else {
        // Por defecto: combinar ambos (comportamiento anterior)
        $conceptos = collect()
            ->concat($datosFacturacion->map(function($item) {
                return [
                    'producto' => $this->resolverClaveSatDesdeProducto($item->clave_sat ?? null, $item->sku ?? null),
                    'cantidad' => $item->cantidad_total,
                    'unidad' => $this->resolverUnidadMedSatDesdeProducto($item->unidad_med_sat ?? null),
                    'concepto' => $item->nombre_producto,
                    'precio' => number_format($item->precio_unitario, 2),
                    'importe' => number_format($item->importe, 2)
                ];
            }))
            ->concat($obtenerconceptos->map(function($item) {
                return [
                    'producto' => $item->nombre ?? 'CONCEPTO',
                    'cantidad' => 1,
                    'unidad' => 'PZA',
                    'concepto' => $item->descripcion ?? 'Concepto de servicio',
                    'precio' => number_format($item->monto ?? 0, 2),
                    'importe' => number_format($item->monto ?? 0, 2)
                ];
            }));
        
        // Usar totales de $datosFacturacion (comportamiento anterior)
        $subtotal = $datosFacturacion[0]->subtotal ?? 0;
        $iva = $datosFacturacion[0]->iva ?? 0;
        $total = $datosFacturacion[0]->total ?? 0;
    }

    // Formatear los datos para la vista
    $datos = [
        'emisor' => $this->obtenerDatosEmisorFacturacion(),
        'servicio' => [
            'id' => $datosFacturacion[0]->id,
            'folio' => $datosFacturacion[0]->folio,
            'nombre' => $datosFacturacion[0]->nombre_servicio,
            'fecha_inicio' => $datosFacturacion[0]->fecha_inicio,
            'fecha_limite' => $datosFacturacion[0]->fecha_limite,
            'tipo' => $servicio
        ],
        'conceptos' => $conceptos,
        'totales' => [
            'subtotal' => number_format($subtotal, 2),
            'iva' => number_format($iva, 2),
            'total' => number_format($total, 2)
        ],
        'pago' => [
            'moneda' => 'MXN - Peso Mexicano',
            'forma_pago' => '99 - Por definir',
            'metodo_pago' => 'PUE'
        ],
        'receptor' => [
            'nombre' => $datosFacturacion[0]->cliente_nombre,
            'razon_social' => $datosFacturacion[0]->cliente_razon_social,
            'rfc' => $datosFacturacion[0]->cliente_rfc,
            'telefono' => $datosFacturacion[0]->cliente_telefono,
            'email' => $datosFacturacion[0]->cliente_email,
            'direccion' => sprintf('%s %s%s, %s',
                $datosFacturacion[0]->cliente_calle,
                $datosFacturacion[0]->cliente_numero_ext,
                $datosFacturacion[0]->cliente_numero_int ? " Int. " . $datosFacturacion[0]->cliente_numero_int : "",
                $datosFacturacion[0]->cliente_colonia
            ),
            'codigo_postal' => $datosFacturacion[0]->cliente_cp,
            'uso_cfdi' => 'G03 - Gastos en general', // Valor por defecto, podría venir de otra tabla
            'regimen_fiscal' => '605 - Sueldos y Salarios e Ingresos Asimilados a Salarios' // Valor por defecto
        ]
    ];

    return view('Servicios.facturacion', compact('datos', 'varpantallas', 'varsubmenus', 'id'));
}

    /**
     * Prepara la vista de facturación CFDI para un pedido de venta (editable antes de timbrar).
     */
    public function facturacionPedido(int $id)
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $requiereCartaPorte = request()->boolean('carta_porte');

        $pedido = VentaPedido::with(['detalles.producto', 'cliente'])
            ->where('id', $id)
            ->first();

        if (!$pedido) {
            return redirect()->route('ventas.pedidos')
                ->withErrors(['timbrado' => 'Pedido no encontrado.']);
        }

        if ($pedido->estatus === 'CANCELADO') {
            return redirect()->route('ventas.pedidos', [
                'vendedor_id' => $pedido->usuario_id,
                'abrir_pedido' => 1,
                'pedido_id' => $pedido->id,
            ])->withErrors(['timbrado' => 'No se puede facturar un pedido cancelado.']);
        }

        if ($pedido->detalles->isEmpty()) {
            return redirect()->route('ventas.pedidos', [
                'vendedor_id' => $pedido->usuario_id,
                'abrir_pedido' => 1,
                'pedido_id' => $pedido->id,
            ])->withErrors(['timbrado' => 'El pedido no tiene productos para facturar.']);
        }

        $cliente = DB::table('tblclientes as c')
            ->leftJoin('tblciudades as ci', 'ci.id', '=', 'c.id_ciudad')
            ->leftJoin('tblestados as e', 'e.id', '=', 'ci.idestado')
            ->where('c.id', $pedido->cliente_id)
            ->select('c.*', 'ci.nombre as ciudad_nombre', 'e.nombre as estado_nombre')
            ->first();

        if (!$cliente || empty(trim((string) ($cliente->rfc ?? '')))) {
            return redirect()->route('ventas.pedidos', [
                'vendedor_id' => $pedido->usuario_id,
                'abrir_pedido' => 1,
                'pedido_id' => $pedido->id,
            ])->withErrors(['timbrado' => 'El cliente del pedido no tiene RFC configurado.']);
        }

        $factorDescuento = (float) $pedido->subtotal > 0
            ? max(0, ((float) $pedido->subtotal - (float) $pedido->descuento) / (float) $pedido->subtotal)
            : 1;

        $conceptos = $pedido->detalles->map(function ($detalle) use ($factorDescuento) {
            $cantidad = (float) $detalle->cantidad;
            $subtotalItem = round((float) $detalle->importe * $factorDescuento, 2);
            $unitPrice = $cantidad > 0 ? round($subtotalItem / $cantidad, 2) : 0;
            $producto = $detalle->producto;

            return [
                'producto' => $this->resolverClaveSatDesdeProducto(
                    $producto->clave_sat ?? null,
                    $producto->sku ?? null
                ),
                'cantidad' => $cantidad,
                'unidad' => $this->resolverUnidadMedSatDesdeProducto($producto->Unidad_med_sat ?? null),
                'concepto' => trim((string) ($detalle->descripcion ?: optional($detalle->producto)->nombre ?: 'Producto')),
                'precio' => number_format($unitPrice, 2),
                'importe' => number_format($subtotalItem, 2),
            ];
        })->filter(fn ($c) => (float) str_replace(',', '', $c['importe']) > 0)->values();

        $subtotal = round((float) $pedido->subtotal - (float) $pedido->descuento, 2);
        $iva = round((float) $pedido->iva, 2);
        $total = round((float) $pedido->total, 2);

        $direccionCliente = sprintf(
            '%s %s%s, %s',
            $cliente->calle ?? '',
            $cliente->numero_ext ?? '',
            !empty($cliente->numero_int) ? ' Int. ' . $cliente->numero_int : '',
            $cliente->colonia ?? ''
        );

        $datos = [
            'emisor' => $this->obtenerDatosEmisorFacturacion(),
            'servicio' => [
                'id' => $pedido->id,
                'folio' => $pedido->folio,
                'nombre' => 'Pedido de venta ' . ($pedido->folio ?: $pedido->id),
                'fecha_inicio' => $pedido->fecha,
                'fecha_limite' => $pedido->fecha_entrega,
                'tipo' => 'pedido_venta',
                'requiere_carta_porte' => $requiereCartaPorte,
            ],
            'conceptos' => $conceptos,
            'totales' => [
                'subtotal' => number_format($subtotal, 2),
                'iva' => number_format($iva, 2),
                'total' => number_format($total, 2),
            ],
            'pago' => [
                'moneda' => 'MXN - Peso Mexicano',
                'forma_pago' => '03 - Transferencia electrónica de fondos',
                'metodo_pago' => 'PUE',
            ],
            'receptor' => [
                'nombre' => $cliente->nombre,
                'razon_social' => $cliente->razon_social ?: $cliente->nombre,
                'rfc' => $cliente->rfc,
                'telefono' => $cliente->telefono,
                'email' => $cliente->correo_electronico,
                'direccion' => trim($direccionCliente),
                'codigo_postal' => $cliente->cp,
                'uso_cfdi' => 'G03 - Gastos en general',
                'regimen_fiscal' => '601 - General de Ley Personas Morales',
            ],
        ];

        $facturasTimbradas = facturacionproductos::query()
            ->where('id_serv_enc', $pedido->id)
            ->where('tipo_serv', 'pedido_venta')
            ->whereNotNull('Uuid')
            ->where('Uuid', '!=', '')
            ->orderByDesc('id')
            ->get();

        return view('Servicios.facturacion', compact(
            'datos',
            'varpantallas',
            'varsubmenus',
            'id',
            'facturasTimbradas',
            'requiereCartaPorte'
        ));
    }

    /**
     * Vista de facturación de pedido con complemento Carta Porte 3.1 (Facturama).
     * Si viene ruta_id + costo_transporte (desde Logística), muestra los pedidos de la ruta
     * y el total del CFDI es el costo de transporte.
     * @see https://apisandbox.facturama.mx/guias/complementos/complemento-carta-porte-31
     */
    public function facturacionPedidoCartaPorte(Request $request, int $id)
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        $rutaId = (int) $request->input('ruta_id', 0);
        $costoTransporte = (float) str_replace([',', '$', ' '], '', (string) $request->input('costo_transporte', 0));
        $rutaLogistica = null;
        $pedidosRuta = collect();

        if ($rutaId > 0) {
            $rutaLogistica = RutaCarga::with([
                'camion',
                'chofer',
                'cargas' => fn ($q) => $q->orderBy('orden_entrega')->orderBy('id'),
                'cargas.pedido.detalles.producto.unidadMedida',
                'cargas.pedido.cliente',
            ])->find($rutaId);

            if (!$rutaLogistica) {
                return redirect()->route('ventas.pedidos.logistica')
                    ->withErrors(['timbrado' => 'Ruta no encontrada.']);
            }

            $pedidosRuta = $rutaLogistica->cargas
                ->map(fn ($c) => $c->pedido)
                ->filter()
                ->values();

            $idsPedidosRuta = $pedidosRuta->pluck('id')->map(fn ($v) => (int) $v)->all();
            if (!in_array($id, $idsPedidosRuta, true) && $pedidosRuta->isNotEmpty()) {
                $id = (int) $pedidosRuta->first()->id;
            }

            if ($costoTransporte <= 0) {
                return redirect()->route('ventas.pedidos.logistica', ['ruta_id' => $rutaId])
                    ->withErrors(['timbrado' => 'Indique un costo de transporte válido mayor a cero.']);
            }
        }

        $pedido = VentaPedido::with([
            'detalles.producto.unidadMedida',
            'cliente',
            'carga.ruta.camion',
            'carga.ruta.chofer',
        ])->where('id', $id)->first();

        if (!$pedido) {
            return redirect()->route('ventas.pedidos')
                ->withErrors(['timbrado' => 'Pedido no encontrado.']);
        }

        if ($pedido->estatus === 'CANCELADO') {
            return redirect()->route('ventas.pedidos', [
                'vendedor_id' => $pedido->usuario_id,
                'abrir_pedido' => 1,
                'pedido_id' => $pedido->id,
            ])->withErrors(['timbrado' => 'No se puede facturar un pedido cancelado.']);
        }

        $detallesParaConceptos = $rutaLogistica
            ? $pedidosRuta->flatMap(fn ($p) => $p->detalles)
            : $pedido->detalles;

        if ($detallesParaConceptos->isEmpty()) {
            return redirect()->route($rutaLogistica ? 'ventas.pedidos.logistica' : 'ventas.pedidos', $rutaLogistica ? ['ruta_id' => $rutaId] : [
                'vendedor_id' => $pedido->usuario_id,
                'abrir_pedido' => 1,
                'pedido_id' => $pedido->id,
            ])->withErrors(['timbrado' => 'No hay productos para armar la carta porte.']);
        }

        $cliente = DB::table('tblclientes as c')
            ->leftJoin('tblciudades as ci', 'ci.id', '=', 'c.id_ciudad')
            ->leftJoin('tblestados as e', 'e.id', '=', 'ci.idestado')
            ->where('c.id', $pedido->cliente_id)
            ->select('c.*', 'ci.nombre as ciudad_nombre', 'e.nombre as estado_nombre')
            ->first();

        if (!$cliente || empty(trim((string) ($cliente->rfc ?? '')))) {
            return redirect()->route($rutaLogistica ? 'ventas.pedidos.logistica' : 'ventas.pedidos', $rutaLogistica ? ['ruta_id' => $rutaId] : [
                'vendedor_id' => $pedido->usuario_id,
                'abrir_pedido' => 1,
                'pedido_id' => $pedido->id,
            ])->withErrors(['timbrado' => 'El cliente del pedido no tiene RFC configurado.']);
        }

        $conceptos = collect();
        if ($rutaLogistica) {
            foreach ($pedidosRuta as $pedidoRutaItem) {
                foreach ($pedidoRutaItem->detalles as $detalle) {
                    $cantidad = (float) $detalle->cantidad;
                    if ($cantidad <= 0) {
                        continue;
                    }
                    $subtotalItem = round((float) $detalle->importe, 2);
                    $unitPrice = $cantidad > 0 ? round($subtotalItem / $cantidad, 2) : 0;
                    $producto = $detalle->producto;
                    $unidadSat = $this->resolverUnidadMedSatDesdeProducto(
                        $producto->Unidad_med_sat
                            ?? optional($producto->unidadMedida)->c_unidad_medida
                            ?? null
                    );
                    $conceptos->push([
                        'producto' => $this->resolverClaveSatDesdeProducto(
                            $producto->clave_sat ?? null,
                            null,
                            '01010101'
                        ),
                        'sku' => $producto->sku ?? '',
                        'cantidad' => $cantidad,
                        'unidad' => $unidadSat,
                        'concepto' => trim((string) ($detalle->descripcion ?: optional($producto)->nombre ?: 'Producto')),
                        'nombre' => trim(
                            ($pedidoRutaItem->folio ?: ('Ped #' . $pedidoRutaItem->id))
                            . ' · '
                            . (string) (optional($producto)->nombre ?: $detalle->descripcion ?: 'Producto')
                        ),
                        'precio' => number_format($unitPrice, 2, '.', ''),
                        'importe' => number_format($subtotalItem, 2, '.', ''),
                        'peso_kg' => '',
                        'material_peligroso' => 'No',
                    ]);
                }
            }
            $conceptos = $conceptos->values();
        } else {
            $factorDescuento = (float) $pedido->subtotal > 0
                ? max(0, ((float) $pedido->subtotal - (float) $pedido->descuento) / (float) $pedido->subtotal)
                : 1;

            $conceptos = $pedido->detalles->map(function ($detalle) use ($factorDescuento) {
                $cantidad = (float) $detalle->cantidad;
                $subtotalItem = round((float) $detalle->importe * $factorDescuento, 2);
                $unitPrice = $cantidad > 0 ? round($subtotalItem / $cantidad, 2) : 0;
                $producto = $detalle->producto;
                $unidadSat = $this->resolverUnidadMedSatDesdeProducto(
                    $producto->Unidad_med_sat
                        ?? optional($producto->unidadMedida)->c_unidad_medida
                        ?? null
                );

                return [
                    'producto' => $this->resolverClaveSatDesdeProducto(
                        $producto->clave_sat ?? null,
                        null,
                        '01010101'
                    ),
                    'sku' => $producto->sku ?? '',
                    'cantidad' => $cantidad,
                    'unidad' => $unidadSat,
                    'concepto' => trim((string) ($detalle->descripcion ?: optional($producto)->nombre ?: 'Producto')),
                    'nombre' => trim((string) (optional($producto)->nombre ?: $detalle->descripcion ?: 'Producto')),
                    'precio' => number_format($unitPrice, 2, '.', ''),
                    'importe' => number_format($subtotalItem, 2, '.', ''),
                    'peso_kg' => '',
                    'material_peligroso' => 'No',
                ];
            })->filter(fn ($c) => (float) $c['importe'] > 0)->values();
        }

        if ($rutaLogistica && $costoTransporte > 0) {
            // El total del CFDI es el costo de transporte (IVA incluido).
            $total = round($costoTransporte, 2);
            $subtotal = round($total / 1.16, 2);
            $iva = round($total - $subtotal, 2);

            $sumaImportes = $conceptos->sum(fn ($c) => (float) $c['importe']);
            if ($sumaImportes > 0 && $conceptos->isNotEmpty()) {
                $factor = $subtotal / $sumaImportes;
                $conceptos = $conceptos->values();
                $acumulado = 0.0;
                $ultimo = $conceptos->count() - 1;
                $conceptos = $conceptos->map(function ($c, $idx) use ($factor, $subtotal, &$acumulado, $ultimo) {
                    $cantidad = (float) $c['cantidad'];
                    if ($idx === $ultimo) {
                        $importe = round($subtotal - $acumulado, 2);
                    } else {
                        $importe = round((float) $c['importe'] * $factor, 2);
                        $acumulado += $importe;
                    }
                    $precio = $cantidad > 0 ? round($importe / $cantidad, 6) : 0;
                    $c['importe'] = number_format($importe, 2, '.', '');
                    $c['precio'] = number_format($precio, 6, '.', '');
                    return $c;
                });
            } else {
                $conceptos = collect([[
                    'producto' => '78101800',
                    'sku' => $rutaLogistica->folio,
                    'cantidad' => 1,
                    'unidad' => 'E48',
                    'concepto' => 'Servicio de transporte — ruta ' . $rutaLogistica->folio,
                    'nombre' => 'Servicio de transporte — ruta ' . $rutaLogistica->folio,
                    'precio' => number_format($subtotal, 2, '.', ''),
                    'importe' => number_format($subtotal, 2, '.', ''),
                    'peso_kg' => '1',
                    'material_peligroso' => 'No',
                ]]);
            }
        } else {
            $subtotal = round((float) $pedido->subtotal - (float) $pedido->descuento, 2);
            $iva = round((float) $pedido->iva, 2);
            $total = round((float) $pedido->total, 2);
        }

        $direccionCliente = sprintf(
            '%s %s%s, %s',
            $cliente->calle ?? '',
            $cliente->numero_ext ?? '',
            !empty($cliente->numero_int) ? ' Int. ' . $cliente->numero_int : '',
            $cliente->colonia ?? ''
        );

        $emisor = $this->obtenerDatosEmisorFacturacion();
        $fiscal = DatosFiscalesEmpresa::query()
            ->where('estatus', 'ACTIVO')
            ->orderByDesc('id')
            ->first() ?? DatosFiscalesEmpresa::query()->orderByDesc('id')->first();

        $ruta = $rutaLogistica ?: optional(optional($pedido->carga)->ruta);
        $camion = $ruta->camion ?? null;
        $chofer = $ruta->chofer ?? null;

        $camiones = Camion::activos()->orderBy('placas')->get();
        $choferes = Chofer::activos()->orderBy('nombre')->get();

        $folioServicio = $rutaLogistica
            ? ($rutaLogistica->folio . ' / ' . ($pedido->folio ?: $pedido->id))
            : ($pedido->folio ?: $pedido->id);

        $datos = [
            'emisor' => $emisor,
            'servicio' => [
                'id' => $pedido->id,
                'folio' => $folioServicio,
                'nombre' => $rutaLogistica
                    ? ('Carta porte ruta ' . $rutaLogistica->folio)
                    : ('Pedido de venta ' . ($pedido->folio ?: $pedido->id)),
                'tipo' => 'pedido_venta',
                'requiere_carta_porte' => true,
            ],
            'conceptos' => $conceptos,
            'totales' => [
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'iva' => number_format($iva, 2, '.', ''),
                'total' => number_format($total, 2, '.', ''),
            ],
            'pago' => [
                'forma_pago' => '03 - Transferencia electrónica de fondos',
                'metodo_pago' => 'PUE',
            ],
            'receptor' => [
                'nombre' => $cliente->nombre,
                'razon_social' => $cliente->razon_social ?: $cliente->nombre,
                'rfc' => $cliente->rfc,
                'telefono' => $cliente->telefono,
                'email' => $cliente->correo_electronico,
                'direccion' => trim($direccionCliente),
                'codigo_postal' => $cliente->cp,
                'calle' => $cliente->calle ?? '',
                'numero_ext' => $cliente->numero_ext ?? '',
                'numero_int' => $cliente->numero_int ?? '',
                'colonia' => $cliente->colonia ?? '',
                'municipio' => $cliente->ciudad_nombre ?? ($cliente->municipio ?? ''),
                'estado' => $cliente->estado_nombre ?? '',
                'uso_cfdi' => 'G03 - Gastos en general',
                'regimen_fiscal' => '601 - General de Ley Personas Morales',
            ],
            'carta_porte' => [
                'transp_internac' => 'No',
                'camion_id' => optional($camion)->id,
                'chofer_id' => optional($chofer)->id,
                'origen' => [
                    'rfc' => $emisor['rfc'] ?? '',
                    'nombre' => $emisor['nombre'] ?? '',
                    'fecha' => now()->format('Y-m-d\TH:i'),
                    'cp' => $fiscal->codigo_postal ?? ($emisor['lugar_expedicion'] ?? ''),
                    'estado' => $this->mapearEstadoSat($fiscal->estado ?? 'DURANGO'),
                    'municipio' => '',
                    'localidad' => '',
                    'colonia' => $fiscal->colonia ?? '',
                    'calle' => $fiscal->calle ?? '',
                    'num_ext' => $fiscal->numero_exterior ?? '',
                    'num_int' => $fiscal->numero_interior ?? '',
                    'pais' => 'MEX',
                ],
                'destino' => [
                    'rfc' => $cliente->rfc ?? '',
                    'nombre' => $cliente->razon_social ?: ($cliente->nombre ?? ''),
                    'fecha' => now()->addHours(4)->format('Y-m-d\TH:i'),
                    'distancia' => '1',
                    'cp' => $cliente->cp ?? '',
                    'estado' => $this->mapearEstadoSat($cliente->estado_nombre ?? ''),
                    'municipio' => '',
                    'localidad' => '',
                    'colonia' => $cliente->colonia ?? '',
                    'calle' => $cliente->calle ?? '',
                    'num_ext' => $cliente->numero_ext ?? '',
                    'num_int' => $cliente->numero_int ?? '',
                    'pais' => 'MEX',
                ],
                'autotransporte' => [
                    'perm_sct' => optional($camion)->perm_sct ?: 'TPAF01',
                    'num_permiso_sct' => optional($camion)->num_permiso_sct ?? '',
                    'config_vehicular' => optional($camion)->config_vehicular ?: 'VL',
                    'peso_bruto_vehicular' => optional($camion)->capacidad_ton ?? '1',
                    'placa_vm' => optional($camion)->placas ?? '',
                    'anio_modelo_vm' => optional($camion)->anio_modelo ?: date('Y'),
                    'asegura_resp_civil' => optional($camion)->asegura_resp_civil ?? '',
                    'poliza_resp_civil' => optional($camion)->poliza_resp_civil ?? '',
                ],
                'figura' => [
                    'tipo_figura' => '01',
                    'rfc_figura' => optional($chofer)->rfc ?? '',
                    'num_licencia' => optional($chofer)->licencia ?? '',
                    'nombre_figura' => optional($chofer)->nombre ?? ($ruta->chofer_nombre ?? ''),
                ],
                'unidad_peso' => 'KGM',
            ],
        ];

        $facturasTimbradas = facturacionproductos::query()
            ->where('id_serv_enc', $pedido->id)
            ->where('tipo_serv', 'pedido_venta')
            ->whereNotNull('Uuid')
            ->where('Uuid', '!=', '')
            ->orderByDesc('id')
            ->get();

        return view('Servicios.facturacion_carta_porte', compact(
            'datos',
            'varpantallas',
            'varsubmenus',
            'id',
            'facturasTimbradas',
            'pedido',
            'camiones',
            'choferes',
            'rutaLogistica',
            'pedidosRuta',
            'costoTransporte'
        ));
    }

    /**
     * Timbrado aparte de CFDI de ingreso con complemento Carta Porte 3.1.
     */
    public function procesarFacturaCartaPorte(Request $request)
    {
        $request->validate([
            'id_servicio_enc' => ['required', 'integer'],
            'tipo_servicio' => ['required', 'string'],
            'folio' => ['nullable', 'string'],
            'emisor_nombre' => ['required', 'string'],
            'emisor_rfc' => ['required', 'string'],
            'emisor_lugar_expedicion' => ['required', 'string'],
            'emisor_regimen_fiscal' => ['required', 'string'],
            'receptor_nombre' => ['required', 'string'],
            'receptor_rfc' => ['required', 'string'],
            'receptor_codigo_postal' => ['required', 'string'],
            'receptor_uso_cfdi' => ['required', 'string'],
            'receptor_regimen_fiscal' => ['required', 'string'],
            'forma_pago' => ['required', 'string'],
            'metodo_pago' => ['required', 'string'],
            'conceptos' => ['required', 'string'],
            'cp_transp_internac' => ['required', 'in:Sí,Si,No'],
            'cp_origen_rfc' => ['required', 'string'],
            'cp_origen_fecha' => ['required', 'string'],
            'cp_origen_cp' => ['required', 'string'],
            'cp_origen_estado' => ['required', 'string'],
            'cp_destino_rfc' => ['required', 'string'],
            'cp_destino_fecha' => ['required', 'string'],
            'cp_destino_cp' => ['required', 'string'],
            'cp_destino_estado' => ['required', 'string'],
            'cp_destino_distancia' => ['required', 'numeric', 'min:0.01'],
            'cp_perm_sct' => ['required', 'string'],
            'cp_num_permiso_sct' => ['required', 'string'],
            'cp_config_vehicular' => ['required', 'string'],
            'cp_placa_vm' => ['required', 'string'],
            'cp_anio_modelo_vm' => ['required', 'string'],
            'cp_peso_bruto_vehicular' => ['required', 'numeric', 'min:0.01'],
            'cp_asegura_resp_civil' => ['required', 'string'],
            'cp_poliza_resp_civil' => ['required', 'string'],
            'cp_figura_tipo' => ['required', 'string'],
            'cp_figura_nombre' => ['required', 'string'],
            'cp_figura_rfc' => ['required', 'string'],
            'cp_figura_licencia' => ['required', 'string'],
            'cp_unidad_peso' => ['required', 'string'],
        ]);

        $idServicio = (int) $request->input('id_servicio_enc');
        $tipoServicio = (string) $request->input('tipo_servicio');
        $conceptos = json_decode((string) $request->input('conceptos'), true) ?: [];

        if (empty($conceptos)) {
            return back()->with('facturaerror', 'No hay conceptos para timbrar con carta porte.');
        }

        $usoCfdi = explode(' - ', (string) $request->input('receptor_uso_cfdi'))[0] ?: 'G03';
        $regimenReceptor = explode(' - ', (string) $request->input('receptor_regimen_fiscal'))[0] ?: '601';
        $regimenEmisor = explode(' - ', (string) $request->input('emisor_regimen_fiscal'))[0] ?: '601';
        $formaPago = explode(' - ', (string) $request->input('forma_pago'))[0] ?: '03';
        $metodoPago = (string) $request->input('metodo_pago');

        $items = [];
        $mercancias = [];
        foreach ($conceptos as $concepto) {
            $cantidad = (float) str_replace([',', '$', ' '], '', (string) ($concepto['cantidad'] ?? 0));
            $precio = (float) str_replace([',', '$', ' '], '', (string) ($concepto['precio'] ?? 0));
            if ($cantidad <= 0 || $precio <= 0) {
                continue;
            }

            $productCode = trim((string) ($concepto['producto'] ?? '01010101'));
            if (str_contains($productCode, ' - ')) {
                $productCode = explode(' - ', $productCode)[0];
            }
            $unitCode = trim((string) ($concepto['unidad'] ?? 'H87'));
            if (str_contains($unitCode, ' - ')) {
                $unitCode = explode(' - ', $unitCode)[0];
            }

            $subtotalItem = round($cantidad * $precio, 2);
            $ivaItem = round($subtotalItem * 0.16, 2);
            $totalItem = round($subtotalItem + $ivaItem, 2);
            $descripcion = trim((string) ($concepto['concepto'] ?? $concepto['nombre'] ?? 'Producto'));
            $pesoKg = (float) ($concepto['peso_kg'] ?? 0);
            if ($pesoKg <= 0) {
                $pesoKg = max(1, $cantidad);
            }

            $items[] = [
                'ProductCode' => $productCode,
                'IdentificationNumber' => (string) ($concepto['sku'] ?? ''),
                'Description' => $descripcion,
                'UnitCode' => $unitCode,
                'Quantity' => $cantidad,
                'UnitPrice' => $precio,
                'Subtotal' => $subtotalItem,
                'TaxObject' => '02',
                'Taxes' => [[
                    'Total' => $ivaItem,
                    'Name' => 'IVA',
                    'Base' => $subtotalItem,
                    'Rate' => 0.16,
                    'IsRetention' => false,
                ]],
                'Total' => $totalItem,
            ];

            $mercancia = [
                'BienesTransp' => $productCode,
                'Descripcion' => $descripcion,
                'Cantidad' => (string) $cantidad,
                'ClaveUnidad' => $unitCode,
                'PesoEnKg' => (string) $pesoKg,
                'CantidadTransporta' => [[
                    'Cantidad' => (string) $cantidad,
                    'IDOrigen' => 'OR000001',
                    'IDDestino' => 'DE000001',
                ]],
            ];

            // Solo enviar MaterialPeligroso cuando sí aplica.
            // Si la clave BienesTransp tiene Material peligroso = 0 en c_ClaveProdServCP,
            // Facturama rechaza incluso el valor "No".
            $esPeligroso = in_array(
                (string) ($concepto['material_peligroso'] ?? 'No'),
                ['Sí', 'Si', 'SI', 'sí', 'si'],
                true
            );
            if ($esPeligroso) {
                $mercancia['MaterialPeligroso'] = 'Sí';
            }

            $mercancias[] = $mercancia;
        }

        if (empty($items)) {
            return back()->with('facturaerror', 'No hay conceptos válidos para timbrar con carta porte.');
        }

        $transpInternac = in_array($request->input('cp_transp_internac'), ['Sí', 'Si'], true) ? 'Sí' : 'No';
        $origenFecha = str_replace('T', ' ', (string) $request->input('cp_origen_fecha'));
        $destinoFecha = str_replace('T', ' ', (string) $request->input('cp_destino_fecha'));

        $cartaPorte = [
            'TranspInternac' => $transpInternac,
            'Ubicaciones' => [
                [
                    'TipoUbicacion' => 'Origen',
                    'IDUbicacion' => 'OR000001',
                    'RFCRemitenteDestinatario' => strtoupper(trim((string) $request->input('cp_origen_rfc'))),
                    'NombreRemitenteDestinatario' => trim((string) $request->input('cp_origen_nombre', '')),
                    'FechaHoraSalidaLlegada' => $origenFecha,
                    'Domicilio' => array_filter([
                        'Calle' => trim((string) $request->input('cp_origen_calle', '')),
                        'NumeroExterior' => trim((string) $request->input('cp_origen_num_ext', '')),
                        'NumeroInterior' => trim((string) $request->input('cp_origen_num_int', '')),
                        'Colonia' => trim((string) $request->input('cp_origen_colonia', '')),
                        'Localidad' => trim((string) $request->input('cp_origen_localidad', '')),
                        'Municipio' => trim((string) $request->input('cp_origen_municipio', '')),
                        'Estado' => strtoupper(trim((string) $request->input('cp_origen_estado'))),
                        'Pais' => strtoupper(trim((string) $request->input('cp_origen_pais', 'MEX'))) ?: 'MEX',
                        'CodigoPostal' => trim((string) $request->input('cp_origen_cp')),
                    ], fn ($v) => $v !== null && $v !== ''),
                ],
                [
                    'TipoUbicacion' => 'Destino',
                    'IDUbicacion' => 'DE000001',
                    'RFCRemitenteDestinatario' => strtoupper(trim((string) $request->input('cp_destino_rfc'))),
                    'NombreRemitenteDestinatario' => trim((string) $request->input('cp_destino_nombre', '')),
                    'FechaHoraSalidaLlegada' => $destinoFecha,
                    'DistanciaRecorrida' => (string) $request->input('cp_destino_distancia'),
                    'Domicilio' => array_filter([
                        'Calle' => trim((string) $request->input('cp_destino_calle', '')),
                        'NumeroExterior' => trim((string) $request->input('cp_destino_num_ext', '')),
                        'NumeroInterior' => trim((string) $request->input('cp_destino_num_int', '')),
                        'Colonia' => trim((string) $request->input('cp_destino_colonia', '')),
                        'Localidad' => trim((string) $request->input('cp_destino_localidad', '')),
                        'Municipio' => trim((string) $request->input('cp_destino_municipio', '')),
                        'Estado' => strtoupper(trim((string) $request->input('cp_destino_estado'))),
                        'Pais' => strtoupper(trim((string) $request->input('cp_destino_pais', 'MEX'))) ?: 'MEX',
                        'CodigoPostal' => trim((string) $request->input('cp_destino_cp')),
                    ], fn ($v) => $v !== null && $v !== ''),
                ],
            ],
            'Mercancias' => [
                'UnidadPeso' => (string) $request->input('cp_unidad_peso', 'KGM'),
                'Mercancia' => $mercancias,
                'Autotransporte' => [
                    'PermSCT' => (string) $request->input('cp_perm_sct'),
                    'NumPermisoSCT' => (string) $request->input('cp_num_permiso_sct'),
                    'IdentificacionVehicular' => [
                        'ConfigVehicular' => (string) $request->input('cp_config_vehicular'),
                        'PesoBrutoVehicular' => (string) $request->input('cp_peso_bruto_vehicular'),
                        'PlacaVM' => strtoupper(trim((string) $request->input('cp_placa_vm'))),
                        'AnioModeloVM' => (string) $request->input('cp_anio_modelo_vm'),
                    ],
                    'Seguros' => [
                        'AseguraRespCivil' => (string) $request->input('cp_asegura_resp_civil'),
                        'PolizaRespCivil' => (string) $request->input('cp_poliza_resp_civil'),
                    ],
                ],
            ],
            'FiguraTransporte' => [[
                'TipoFigura' => (string) $request->input('cp_figura_tipo'),
                'RFCFigura' => strtoupper(trim((string) $request->input('cp_figura_rfc'))),
                'NumLicencia' => (string) $request->input('cp_figura_licencia'),
                'NombreFigura' => (string) $request->input('cp_figura_nombre'),
            ]],
        ];

        $datosFactura = [
            'CfdiType' => 'I',
            'NameId' => '36',
            'PaymentForm' => $formaPago,
            'PaymentMethod' => $metodoPago,
            'ExpeditionPlace' => (string) $request->input('emisor_lugar_expedicion'),
            'Date' => date('Y-m-d H:i:s'),
            'Folio' => (string) $request->input('folio'),
            'Issuer' => [
                'FiscalRegime' => $regimenEmisor,
                'Rfc' => (string) $request->input('emisor_rfc'),
                'Name' => (string) $request->input('emisor_nombre'),
            ],
            'Receiver' => [
                'Rfc' => (string) $request->input('receptor_rfc'),
                'CfdiUse' => $usoCfdi,
                'Name' => (string) $request->input('receptor_nombre'),
                'FiscalRegime' => $regimenReceptor,
                'TaxZipCode' => (string) $request->input('receptor_codigo_postal'),
                'Address' => [
                    'Street' => (string) $request->input('receptor_calle', $request->input('cp_destino_calle', '')),
                    'ExteriorNumber' => (string) $request->input('receptor_num_ext', $request->input('cp_destino_num_ext', '')),
                    'InteriorNumber' => (string) $request->input('receptor_num_int', ''),
                    'Neighborhood' => (string) $request->input('receptor_colonia', $request->input('cp_destino_colonia', '')),
                    'ZipCode' => (string) $request->input('receptor_codigo_postal'),
                    'Municipality' => (string) $request->input('receptor_municipio', $request->input('cp_destino_municipio', 'TORREÓN')),
                    'State' => (string) $request->input('receptor_estado', $request->input('cp_destino_estado', 'COAHUILA')),
                    'Country' => 'México',
                ],
            ],
            'Items' => $items,
            'Complemento' => [
                'CartaPorte31' => $cartaPorte,
            ],
        ];

        try {
            $resultado = $this->crearFacturaCartaPorteCFDI40($datosFactura, $idServicio, $tipoServicio);

            if (is_array($resultado) && isset($resultado['error'])) {
                $errorData = json_decode($resultado['error'], true);
                Log::error('Error Facturama Carta Porte', [
                    'error_data' => $errorData,
                    'datos_factura' => $datosFactura,
                ]);

                if ($errorData && isset($errorData['Message'])) {
                    $mensajeError = 'Error al timbrar con Carta Porte: ' . $errorData['Message'] . '<br>';
                    if (!empty($errorData['ModelState']) && is_array($errorData['ModelState'])) {
                        foreach ($errorData['ModelState'] as $errores) {
                            foreach ((array) $errores as $error) {
                                $mensajeError .= '• ' . $error . '<br>';
                            }
                        }
                    }
                    return back()->withInput()->with('facturaerror', $mensajeError);
                }

                return back()->withInput()->with('facturaerror', 'Error al timbrar con Carta Porte: ' . $resultado['error']);
            }

            if ($tipoServicio === 'pedido_venta') {
                return redirect()->route('ventas.pedidos', [
                    'vendedor_id' => auth()->id(),
                    'abrir_pedido' => 1,
                    'pedido_id' => $idServicio,
                ])->with('success', 'Factura con Carta Porte timbrada exitosamente');
            }

            return back()->with('success', 'Factura con Carta Porte timbrada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error en procesarFacturaCartaPorte', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withInput()->with('facturaerror', 'Error al timbrar con Carta Porte: ' . $e->getMessage());
        }
    }

    /**
     * Envía a Facturama un CFDI 4.0 con NameId 36 y Complemento CartaPorte31.
     */
    public function crearFacturaCartaPorteCFDI40(array $datosFactura, $id_servicio_enc, $tipo_servicio)
    {
        $username = env('USER_FAC');
        $password = env('PWD');
        $baseUri = rtrim((string) env('FACTURAMA_BASE_URI', 'https://apisandbox.facturama.mx'), '/');

        $client = new Client([
            'base_uri' => $baseUri,
            'timeout' => 3000.0,
            'auth' => [$username, $password],
            'verify' => false,
        ]);

        $data = [
            'CfdiType' => $datosFactura['CfdiType'] ?? 'I',
            'NameId' => $datosFactura['NameId'] ?? '36',
            'PaymentForm' => $datosFactura['PaymentForm'] ?? '03',
            'PaymentMethod' => $datosFactura['PaymentMethod'] ?? 'PUE',
            'ExpeditionPlace' => $datosFactura['ExpeditionPlace'],
            'Date' => $datosFactura['Date'] ?? date('Y-m-d H:i:s'),
            'Folio' => $datosFactura['Folio'] ?? null,
            'Issuer' => [
                'FiscalRegime' => $datosFactura['Issuer']['FiscalRegime'],
                'Rfc' => $datosFactura['Issuer']['Rfc'],
                'Name' => $datosFactura['Issuer']['Name'],
            ],
            'Receiver' => [
                'Rfc' => $datosFactura['Receiver']['Rfc'],
                'CfdiUse' => $datosFactura['Receiver']['CfdiUse'] ?? 'G03',
                'Name' => $datosFactura['Receiver']['Name'],
                'FiscalRegime' => $datosFactura['Receiver']['FiscalRegime'],
                'TaxZipCode' => $datosFactura['Receiver']['TaxZipCode'],
                'Address' => [
                    'Street' => $datosFactura['Receiver']['Address']['Street'] ?? '',
                    'ExteriorNumber' => $datosFactura['Receiver']['Address']['ExteriorNumber'] ?? '',
                    'InteriorNumber' => $datosFactura['Receiver']['Address']['InteriorNumber'] ?? '',
                    'Neighborhood' => $datosFactura['Receiver']['Address']['Neighborhood'] ?? '',
                    'ZipCode' => $datosFactura['Receiver']['Address']['ZipCode'] ?? $datosFactura['Receiver']['TaxZipCode'],
                    'Municipality' => $datosFactura['Receiver']['Address']['Municipality'] ?? '',
                    'State' => $datosFactura['Receiver']['Address']['State'] ?? '',
                    'Country' => $datosFactura['Receiver']['Address']['Country'] ?? 'México',
                ],
            ],
            'Items' => [],
            'Complemento' => $datosFactura['Complemento'] ?? [],
        ];

        foreach ($datosFactura['Items'] as $item) {
            $taxes = [];
            if (!empty($item['Taxes']) && is_array($item['Taxes'])) {
                foreach ($item['Taxes'] as $tax) {
                    $taxes[] = [
                        'Total' => $tax['Total'],
                        'Name' => $tax['Name'],
                        'Base' => $tax['Base'],
                        'Rate' => $tax['Rate'],
                        'IsRetention' => $tax['IsRetention'] ?? false,
                    ];
                }
            }

            $itemData = [
                'ProductCode' => $item['ProductCode'],
                'Description' => $item['Description'],
                'UnitCode' => $item['UnitCode'],
                'Quantity' => $item['Quantity'],
                'UnitPrice' => $item['UnitPrice'],
                'Subtotal' => $item['Subtotal'],
                'TaxObject' => $item['TaxObject'] ?? '02',
                'Total' => $item['Total'],
            ];
            if (!empty($item['IdentificationNumber'])) {
                $itemData['IdentificationNumber'] = $item['IdentificationNumber'];
            }
            if (!empty($taxes)) {
                $itemData['Taxes'] = $taxes;
            }
            $data['Items'][] = $itemData;
        }

        try {
            $response = $client->post('/3/cfdis', [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $data,
            ]);

            $datos = json_decode($response->getBody(), true);
            $finanzasService = app(\App\Services\FacturacionFinanzasService::class);
            $metodoPago = $datosFactura['PaymentMethod'] ?? $datos['PaymentMethod'] ?? 'PUE';
            $estadoCobranza = $finanzasService->resolverEstadoCobranzaInicial($metodoPago);

            $facturacionProducto = facturacionproductos::create([
                'facturama_id' => $datos['Id'] ?? $datos['id'] ?? null,
                'folio' => $datos['Folio'] ?? $datosFactura['Folio'] ?? null,
                'date' => date('Y-m-d', strtotime($datos['Date'] ?? $datosFactura['Date'] ?? now())),
                'reciver_rfc' => $datosFactura['Receiver']['Rfc'] ?? null,
                'reciver_nombre' => $datosFactura['Receiver']['Name'] ?? null,
                'subtotal' => $datos['Subtotal'] ?? 0,
                'total' => $datos['Total'] ?? 0,
                'Uuid' => $datos['Complement']['TaxStamp']['Uuid'] ?? null,
                'CfdiSign' => $datos['Complement']['TaxStamp']['CfdiSign'] ?? null,
                'SatCertNumber' => $datos['Complement']['TaxStamp']['SatCertNumber'] ?? null,
                'SatSign' => $datos['Complement']['TaxStamp']['SatSign'] ?? null,
                'RfcProvCertif' => $datos['Complement']['TaxStamp']['RfcProvCertif'] ?? null,
                'OriginalString' => $datos['OriginalString'] ?? null,
                'estado' => $estadoCobranza,
                'cancelada' => 'A',
                'id_serv_enc' => $id_servicio_enc,
                'tipo_serv' => $tipo_servicio,
                'metodo_pago' => $metodoPago,
                'forma_pago' => $datosFactura['PaymentForm'] ?? $datos['PaymentForm'] ?? '99',
                'referencia_factura' => $datosFactura['referencia_factura'] ?? null,
            ]);

            try {
                $finanzasService->procesarPostTimbrado($facturacionProducto);
            } catch (\Throwable $e) {
                Log::error('Error al integrar factura Carta Porte con Finanzas: ' . $e->getMessage());
            }

            return $datos;
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                return ['error' => $this->extraerErrorFacturama($e)];
            }
            return ['error' => $e->getMessage()];
        }
    }

    private function mapearEstadoSat(?string $estado): string
    {
        $estado = mb_strtoupper(trim((string) $estado));
        $mapa = [
            'AGUASCALIENTES' => 'AGU',
            'BAJA CALIFORNIA' => 'BCN',
            'BAJA CALIFORNIA SUR' => 'BCS',
            'CAMPECHE' => 'CAM',
            'CHIAPAS' => 'CHP',
            'CHIHUAHUA' => 'CHH',
            'CIUDAD DE MEXICO' => 'CMX',
            'CIUDAD DE MÉXICO' => 'CMX',
            'CDMX' => 'CMX',
            'COAHUILA' => 'COA',
            'COAHUILA DE ZARAGOZA' => 'COA',
            'COLIMA' => 'COL',
            'DURANGO' => 'DUR',
            'DGO' => 'DUR',
            'GUANAJUATO' => 'GUA',
            'GUERRERO' => 'GRO',
            'HIDALGO' => 'HID',
            'JALISCO' => 'JAL',
            'MEXICO' => 'MEX',
            'MÉXICO' => 'MEX',
            'ESTADO DE MEXICO' => 'MEX',
            'MICHOACAN' => 'MIC',
            'MICHOACÁN' => 'MIC',
            'MORELOS' => 'MOR',
            'NAYARIT' => 'NAY',
            'NUEVO LEON' => 'NLE',
            'NUEVO LEÓN' => 'NLE',
            'OAXACA' => 'OAX',
            'PUEBLA' => 'PUE',
            'QUERETARO' => 'QUE',
            'QUERÉTARO' => 'QUE',
            'QUINTANA ROO' => 'ROO',
            'SAN LUIS POTOSI' => 'SLP',
            'SAN LUIS POTOSÍ' => 'SLP',
            'SINALOA' => 'SIN',
            'SONORA' => 'SON',
            'TABASCO' => 'TAB',
            'TAMAULIPAS' => 'TAM',
            'TLAXCALA' => 'TLA',
            'VERACRUZ' => 'VER',
            'YUCATAN' => 'YUC',
            'YUCATÁN' => 'YUC',
            'ZACATECAS' => 'ZAC',
        ];

        if (isset($mapa[$estado])) {
            return $mapa[$estado];
        }
        if (strlen($estado) === 3) {
            return $estado;
        }

        return 'COA';
    }

    /**
     * Catálogo Facturama de códigos postales (Estado/Municipio/Localidad SAT).
     * GET /Catalogs/PostalCodes?keyword={cp}
     */
    public function buscarCodigoPostal(Request $request)
    {
        try {
            $keyword = preg_replace('/\D+/', '', (string) $request->input('keyword', ''));

            if (strlen($keyword) < 4) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Indique un código postal de al menos 4 dígitos.',
                    'data' => [],
                ], 422);
            }

            $username = env('USER_FAC');
            $password = env('PWD');
            $baseUri = rtrim((string) env('FACTURAMA_BASE_URI', 'https://apisandbox.facturama.mx'), '/');
            $url = $baseUri . '/Catalogs/PostalCodes?keyword=' . urlencode($keyword);

            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', $url, [
                'auth' => [$username, $password],
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->getBody()->getContents();
            $decoded = json_decode($content, true);

            if ($statusCode !== 200) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Error al consultar código postal en Facturama',
                    'status' => $statusCode,
                    'data' => [],
                ], $statusCode);
            }

            $lista = is_array($decoded) ? $decoded : [];
            $normalizados = [];
            foreach ($lista as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $cp = (string) ($item['Value'] ?? $item['Name'] ?? '');
                $normalizados[] = [
                    'cp' => $cp,
                    'estado' => (string) ($item['StateCode'] ?? ''),
                    'municipio' => (string) ($item['MunicipalityCode'] ?? ''),
                    'localidad' => (string) ($item['LocationCode'] ?? ''),
                    'StateCode' => (string) ($item['StateCode'] ?? ''),
                    'MunicipalityCode' => (string) ($item['MunicipalityCode'] ?? ''),
                    'LocationCode' => (string) ($item['LocationCode'] ?? ''),
                    'Name' => (string) ($item['Name'] ?? $cp),
                    'Value' => $cp,
                ];
            }

            // Preferir coincidencia exacta del CP capturado
            $exactos = array_values(array_filter($normalizados, function ($row) use ($keyword) {
                return $row['cp'] === $keyword;
            }));

            return response()->json([
                'ok' => true,
                'keyword' => $keyword,
                'data' => !empty($exactos) ? $exactos : $normalizados,
                'sugerido' => !empty($exactos) ? $exactos[0] : ($normalizados[0] ?? null),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => 'Error en la búsqueda de código postal: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function buscarProductoServicio(Request $request)
    {
        try {
            $keyword = $request->input('keyword');
            
            // Configuración de credenciales
            $username = env('USER_FAC');
            $password = env('PWD');
            
            // URL de la API
            $url = "https://apisandbox.facturama.mx/catalogs/ProductsOrServices?keyword=" . urlencode($keyword);
            
            // Crear el cliente HTTP con autenticación básica
            $client = new \GuzzleHttp\Client();
            
            // Realizar la petición GET
            $response = $client->request('GET', $url, [
                'auth' => [$username, $password],
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ]);
            
            // Obtener y decodificar la respuesta
            $statusCode = $response->getStatusCode();
            $content = $response->getBody()->getContents();
            
            if ($statusCode == 200) {
                return response()->json(json_decode($content));
            } else {
                return response()->json([
                    'error' => 'Error en la búsqueda',
                    'status' => $statusCode
                ], $statusCode);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error en la búsqueda: ' . $e->getMessage()
            ], 500);
        }
    }

    public function buscarUnidadMedida(Request $request)
    {
        try {
            $keyword = $request->input('keyword');
            
            // Configuración de credenciales
            $username = env('USER_FAC');
            $password = env('PWD');
            
            // URL de la API
            $url = "https://apisandbox.facturama.mx/catalogs/Units?keyword=" . urlencode($keyword);
            
            // Crear el cliente HTTP con autenticación básica
            $client = new \GuzzleHttp\Client();
            
            // Realizar la petición GET
            $response = $client->request('GET', $url, [
                'auth' => [$username, $password],
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ]);
            
            // Obtener y decodificar la respuesta
            $statusCode = $response->getStatusCode();
            $content = $response->getBody()->getContents();
            
            if ($statusCode == 200) {
                return response()->json(json_decode($content));
            } else {
                return response()->json([
                    'error' => 'Error en la búsqueda de unidades',
                    'status' => $statusCode
                ], $statusCode);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error en la búsqueda de unidades: ' . $e->getMessage()
            ], 500);
        }
    }

    public function buscarUsoCFDI(Request $request)
    {
        try {
            $keyword = $request->input('keyword');
            
            // Configuración de credenciales
            $username = env('USER_FAC');
            $password = env('PWD');
            
            // URL de la API
            $url = "https://api.facturama.mx/catalogs/CfdiUses?keyword=" . urlencode($keyword);
            
            // Crear el cliente HTTP con autenticación básica
            $client = new \GuzzleHttp\Client();
            
            // Realizar la petición GET
            $response = $client->request('GET', $url, [
                'auth' => [$username, $password],
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ]);
            
            // Obtener y decodificar la respuesta
            $statusCode = $response->getStatusCode();
            $content = $response->getBody()->getContents();
            
            if ($statusCode == 200) {
                return response()->json(json_decode($content));
            } else {
                return response()->json([
                    'error' => 'Error en la búsqueda de usos CFDI',
                    'status' => $statusCode
                ], $statusCode);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error en la búsqueda de usos CFDI: ' . $e->getMessage()
            ], 500);
        }
    }

    public function buscarFormaPago(Request $request)
    {
        try {
            // Configuración de credenciales
            $username = env('USER_FAC');
            $password = env('PWD');
            
            // URL de la API (no requiere keyword)
            $url = "https://apisandbox.facturama.mx/catalogs/PaymentForms";
            
            // Crear el cliente HTTP con autenticación básica
            $client = new \GuzzleHttp\Client();
            
            // Realizar la petición GET
            $response = $client->request('GET', $url, [
                'auth' => [$username, $password],
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ]);
            
            // Obtener y decodificar la respuesta
            $statusCode = $response->getStatusCode();
            $content = $response->getBody()->getContents();
            
            if ($statusCode == 200) {
                return response()->json(json_decode($content));
            } else {
                return response()->json([
                    'error' => 'Error en la búsqueda de formas de pago',
                    'status' => $statusCode
                ], $statusCode);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error en la búsqueda de formas de pago: ' . $e->getMessage()
            ], 500);
        }
    }

    public function buscarRegimenFiscal(Request $request)
    {
        try {
            // Configuración de credenciales
            $username = env('USER_FAC');
            $password = env('PWD');
            
            // URL de la API (no requiere keyword)
            $url = "https://api.facturama.mx/catalogs/FiscalRegimens";
            
            // Crear el cliente HTTP con autenticación básica
            $client = new \GuzzleHttp\Client();
            
            // Realizar la petición GET
            $response = $client->request('GET', $url, [
                'auth' => [$username, $password],
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ]);
            
            // Obtener y decodificar la respuesta
            $statusCode = $response->getStatusCode();
            $content = $response->getBody()->getContents();
            
            if ($statusCode == 200) {
                return response()->json(json_decode($content));
            } else {
                return response()->json([
                    'error' => 'Error en la búsqueda de regímenes fiscales',
                    'status' => $statusCode
                ], $statusCode);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error en la búsqueda de regímenes fiscales: ' . $e->getMessage()
            ], 500);
        }
    }

    public function obtenerMetodosPago()
    {
        // Métodos de pago estáticos según el SAT
        $metodosPago = [
            [
                "Name" => "Pago en parcialidades ó diferido",
                "Value" => "PPD"
            ],
            [
                "Name" => "Pago en una sola exhibición",
                "Value" => "PUE"
            ]
        ];
        
        return response()->json($metodosPago);
    }

    public function previsualizarFactura(Request $request)
    {
        dd($request->all());
    }

    public function procesarFactura(Request $request)
    {
        //dd($request->all());
        // Recopilar datos del formulario
        $emisorNombre = $request->input('emisor_nombre');
        $emisorRfc = $request->input('emisor_rfc');
        $emisorDireccion = $request->input('emisor_direccion');
        $emisorLugarExpedicion = $request->input('emisor_lugar_expedicion');
        $emisorRegimenFiscal = $request->input('emisor_regimen_fiscal');
        $emisorTelefono = $request->input('emisor_telefono');
        
        $folio = $request->input('folio');
        
        $receptorNombre = $request->input('receptor_nombre');
        $receptorRfc = $request->input('receptor_rfc');
        $receptorDireccion = $request->input('receptor_direccion');
        $receptorCodigoPostal = $request->input('receptor_codigo_postal');
        $receptorUsoCfdi = $request->input('receptor_uso_cfdi');
        $receptorRegimenFiscal = $request->input('receptor_regimen_fiscal');
        
        $subtotal = $request->input('subtotal');
        $iva = $request->input('iva');
        $total = $request->input('total');
        
        $formaPago = $request->input('forma_pago');
        $metodoPago = $request->input('metodo_pago');
        $id_servicio_enc = $request->input('id_servicio_enc');
        $tipo_servicio = $request->input('tipo_servicio');
        if ($tipo_servicio !== 'pedido_venta') {
            $decrementoP = $this->decrementarInventarioPorServicio($request->get('id_servicio_enc'));
        }
        //dd($tipo_servicio);
        
        // Procesar conceptos
        $conceptosJson = $request->input('conceptos');
        $conceptos = [];
        if ($conceptosJson) {
            $conceptos = json_decode($conceptosJson, true);
        }
        
        // Extraer código de uso CFDI (ej: "G03 - Gastos en general" -> "G03")
        $usoCfdiCodigo = explode(' - ', $receptorUsoCfdi)[0] ?? 'G03';
        
        // Extraer código de régimen fiscal (ej: "605 - Sueldos y Salarios..." -> "605")
        $regimenFiscalCodigo = explode(' - ', $receptorRegimenFiscal)[0] ?? '605';
        
        // Extraer código de régimen fiscal del emisor
        $emisorRegimenFiscalCodigo = explode(' - ', $emisorRegimenFiscal)[0] ?? '601';
        
        // Extraer código de forma de pago (ej: "99 - Por definir" -> "99")
        $formaPagoCodigo = explode(' - ', $formaPago)[0] ?? '01';
        
        // Procesar dirección del receptor
        $direccionPartes = explode(', ', $receptorDireccion);
        $calleNumero = $direccionPartes[0] ?? '';
        $colonia = $direccionPartes[1] ?? '';
        
        // Separar calle y número
        $calleNumeroPartes = explode(' ', $calleNumero);
        $numeroExterior = end($calleNumeroPartes);
        $calle = implode(' ', array_slice($calleNumeroPartes, 0, -1));
        
        // Crear el objeto para crearFacturaCFDI40
        $datosFactura = [
            'CfdiType' => 'I', // I = Ingreso
            'PaymentForm' => $formaPagoCodigo,
            'PaymentMethod' => $metodoPago,
            'ExpeditionPlace' => $emisorLugarExpedicion,
            'Date' => date('Y-m-d H:i:s'),
            'Folio' => $folio,
            'Issuer' => [
                'FiscalRegime' => $emisorRegimenFiscalCodigo,
                'Rfc' => $emisorRfc,
                'Name' => $emisorNombre
            ],
            'Receiver' => [
                'Rfc' => $receptorRfc,
                'CfdiUse' => $usoCfdiCodigo,
                'Name' => $receptorNombre,
                'FiscalRegime' => $regimenFiscalCodigo,
                'TaxZipCode' => $receptorCodigoPostal,
                'Address' => [
                    'Street' => $calle,
                    'ExteriorNumber' => $numeroExterior,
                    'InteriorNumber' => '',
                    'Neighborhood' => $colonia,
                    'ZipCode' => $receptorCodigoPostal,
                    'Municipality' => 'TORREÓN',
                    'State' => 'COAHUILA',
                    'Country' => 'México'
                ]
            ],
            'Items' => []
        ];
        
        // Procesar conceptos
        foreach ($conceptos as $concepto) {
            // Limpiar y validar cantidad
            $cantidadStr = str_replace(['$', ',', ' '], '', $concepto['cantidad']);
            $cantidad = is_numeric($cantidadStr) ? floatval($cantidadStr) : 0;
            
            // Limpiar y validar precio
            $precioStr = str_replace(['$', ',', ' '], '', $concepto['precio']);
            $precio = is_numeric($precioStr) ? floatval($precioStr) : 0;
            
            // Limpiar y validar importe
            $importeStr = str_replace(['$', ',', ' '], '', $concepto['importe']);
            $importe = is_numeric($importeStr) ? floatval($importeStr) : 0;
            
            // Validar que los valores sean válidos
            if ($cantidad <= 0 || $precio <= 0) {
                continue; // Saltar conceptos inválidos
            }
            
            // Calcular valores
            $subtotalItem = $cantidad * $precio;
            $ivaItem = $subtotalItem * 0.16;
            $totalItem = $subtotalItem + $ivaItem;
            
            // Validar que el ProductCode sea válido
            $productCode = trim($concepto['producto']);
            if (empty($productCode) || $productCode === 'Buscar producto SAT') {
                $productCode = '25173108'; // Código por defecto
            }
            
            // Extraer solo el código numérico si viene con descripción
            if (strpos($productCode, ' - ') !== false) {
                $productCode = explode(' - ', $productCode)[0];
            }
            
            // Validar que la unidad sea válida
            $unitCode = trim($concepto['unidad']);
            if (empty($unitCode)) {
                $unitCode = 'H87'; // Pieza por defecto
            }
            
            // Extraer solo el código de unidad si viene con descripción
            if (strpos($unitCode, ' - ') !== false) {
                $unitCode = explode(' - ', $unitCode)[0];
            }
            
            $datosFactura['Items'][] = [
                'ProductCode' => $productCode,
                'Description' => trim($concepto['concepto']) ?: 'Producto/Servicio',
                'UnitCode' => $unitCode,
                'Quantity' => $cantidad,
                'UnitPrice' => $precio,
                'Subtotal' => $subtotalItem,
                'TaxObject' => '02',
                'Taxes' => [
                    [
                        'Total' => round($ivaItem, 2),
                        'Name' => 'IVA',
                        'Base' => $subtotalItem,
                        'Rate' => 0.16,
                        'IsRetention' => false
                    ]
                ],
                'Total' => round($totalItem, 2)
            ];
        }
        
        // Validar que haya al menos un concepto válido
        if (empty($datosFactura['Items'])) {
            dd([
                'error' => 'No hay conceptos válidos para procesar',
                'conceptos_recibidos' => $conceptos,
                'datos_factura' => $datosFactura
            ]);
        }
        
        // Mostrar la estructura generada
        //dd($datosFactura);
        
        // Llamar al método crearFacturaCFDI40
        try {
            //dd($datosFactura);
            $resultado = $this->crearFacturaCFDI40($datosFactura, $id_servicio_enc, $tipo_servicio);
            
            // Verificar si el resultado contiene un error
            if (is_array($resultado) && isset($resultado['error'])) {
                // Decodificar el JSON que está dentro de la clave "error"
                $errorData = json_decode($resultado['error'], true);
                
                // Log del error completo para debugging
                Log::error('Error de Facturama:', [
                    'error_data' => $errorData,
                    'datos_factura' => $datosFactura
                ]);
                
                // Verificar si hay datos de error válidos
                if ($errorData && isset($errorData['Message'])) {
                    $message = $errorData['Message'];
                    $erroresDetallados = [];
                    
                    // Recopilar todos los errores de validación
                    if (isset($errorData['ModelState']) && is_array($errorData['ModelState'])) {
                        foreach ($errorData['ModelState'] as $campo => $errores) {
                            if (is_array($errores)) {
                                foreach ($errores as $error) {
                                    $erroresDetallados[] = $error;
                                }
                            } else {
                                $erroresDetallados[] = $errores;
                            }
                        }
                    }
                    
                    // Si hay errores detallados, mostrarlos todos
                    if (!empty($erroresDetallados)) {
                        $mensajeError = 'Error al timbrar la factura:<br><br>';
                        $mensajeError .= '<strong>Errores de validación:</strong><br>';
                        foreach ($erroresDetallados as $error) {
                            $mensajeError .= '• ' . $error . '<br>';
                        }
                        
                        return back()->with('facturaerror', $mensajeError);
                    } else {
                        // Si no hay errores detallados, mostrar el mensaje general
                        return back()->with('facturaerror', 'Error al timbrar la factura: ' . $message);
                    }
                } else {
                    // Si no se puede decodificar el error, mostrar el error raw
                    return back()->with('facturaerror', 'Error al timbrar la factura: ' . $resultado['error']);
                }
            }
            if ($tipo_servicio !== 'pedido_venta') {
                $decrementoP = $this->decrementarInventarioPorServicio($request->input('id_servicio_enc'));
            }

            if ($tipo_servicio === 'pedido_venta') {
                return redirect()->route('ventas.pedidos', [
                    'vendedor_id' => auth()->id(),
                    'abrir_pedido' => 1,
                    'pedido_id' => $id_servicio_enc,
                ])->with('success', 'Factura del pedido timbrada exitosamente');
            }

            return back()->with('success', 'Factura timbrada exitosamente');
        } catch (\Exception $e) {
            // Log del error
            Log::error('Error en procesarFactura:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Mostrar error detallado
            return back()->with('facturaerror', 'Error al timbrar la factura: ' . $e->getMessage());
        }
    }
    
    public function ProcesarFacturaBeca(Request $request)
    {
        
      try {
        $emisorNombre = $request->input('emisor_nombre', '');
        $emisorRfc = $request->input('emisor_rfc', '');
        $emisorLugarExpedicion = $request->input('emisor_lugar_expedicion', '');
        $emisorRegimenFiscal = $request->input('emisor_regimen_fiscal', '');

        $receptorNombre = $request->input('receptor_nombre', '');
        $receptorRazonSocial = $request->input('receptor_razon_social', '');
        $receptorRfc = $request->input('receptor_rfc', '');
        $receptorCodigoPostal = $request->input('receptor_codigo_postal', '');
        $receptorUsoCfdi = $request->input('receptor_uso_cfdi', '');
        $receptorRegimenFiscal = $request->input('receptor_regimen_fiscal', '');
        $receptorDireccion = $request->input('receptor_direccion', '');
        $receptorCalle = $request->input('receptor_calle', '');
        $receptorNumExt = $request->input('receptor_numero_exterior', '');
        $receptorNumInt = $request->input('receptor_numero_interior', '');
        $receptorColonia = $request->input('receptor_colonia', '');
        $receptorMunicipio = $request->input('receptor_municipio', '');
        $receptorEstado = $request->input('receptor_estado', '');

        $formaPago = $request->input('forma_pago', '03');
        $metodoPago = $request->input('metodo_pago', 'PUE');
        $sinIva = filter_var($request->input('sin_iva', false), FILTER_VALIDATE_BOOLEAN);
        $empresaId = $request->input('empresa_id', '0');
        $mes = $request->input('mes', date('m'));
        $anio = $request->input('anio', date('Y'));

        $conceptosJson = $request->input('conceptos', '[]');
        $conceptos = json_decode($conceptosJson, true) ?: [];

        $emisorRegimenFiscalCodigo = explode(' - ', (string)$emisorRegimenFiscal)[0] ?: '601';
        $usoCfdiCodigo = $receptorUsoCfdi ?: 'G03';
        $regimenFiscalCodigo = $receptorRegimenFiscal ?: '601';
        $formaPagoCodigo = explode(' - ', (string)$formaPago)[0] ?: '03';

        $nombreReceptor = $receptorRazonSocial ?: $receptorNombre;

        $calle = $receptorCalle ?: '';
        $numExt = $receptorNumExt ?: '';
        $numInt = $receptorNumInt ?: '';
        $colonia = $receptorColonia ?: '';
        $municipio = $receptorMunicipio ?: 'TORREÓN';
        $estado = $receptorEstado ?: 'COAHUILA';
        $diadehoy = date('Y-m-d');

        if (empty($calle) && !empty($receptorDireccion)) {
            $partes = explode(', ', (string)$receptorDireccion);
            $calle = $partes[0] ?? '';
            $colonia = $partes[1] ?? $colonia;
        }

        $folio = 'BECA-' . $empresaId . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT) . $anio;

        $datosFactura = [
            'CfdiType' => 'I',
            'PaymentForm' => $formaPagoCodigo,
            'PaymentMethod' => $metodoPago,
            'ExpeditionPlace' => $emisorLugarExpedicion,
            'Date' => $diadehoy.'T13:43:59.4011985-06:00',
            'Folio' => $folio,
            'Issuer' => [
                'FiscalRegime' => $emisorRegimenFiscalCodigo,
                'Rfc' => $emisorRfc,
                'Name' => $emisorNombre,
            ],
            'Receiver' => [
                'Rfc' => $receptorRfc,
                'CfdiUse' => $usoCfdiCodigo,
                'Name' => $nombreReceptor,
                'FiscalRegime' => $regimenFiscalCodigo,
                'TaxZipCode' => $receptorCodigoPostal,
                'Address' => [
                    'Street' => $calle,
                    'ExteriorNumber' => $numExt,
                    'InteriorNumber' => $numInt,
                    'Neighborhood' => $colonia,
                    'ZipCode' => $receptorCodigoPostal,
                    'Municipality' => $municipio,
                    'State' => $estado,
                    'Country' => 'México',
                ],
            ],
            'Items' => [],
        ];

        foreach ($conceptos as $concepto) {
            $cantidadStr = str_replace(['$', ',', ' '], '', $concepto['cantidad'] ?? '0');
            $cantidad = is_numeric($cantidadStr) ? floatval($cantidadStr) : 0;

            $precioStr = str_replace(['$', ',', ' '], '', $concepto['precio'] ?? '0');
            $precio = is_numeric($precioStr) ? floatval($precioStr) : 0;

            if ($cantidad <= 0 || $precio <= 0) {
                continue;
            }

            $subtotalItem = $cantidad * $precio;

            $productCode = trim($concepto['producto'] ?? '');
            if (empty($productCode) || $productCode === 'Buscar producto SAT') {
                $productCode = '80141600';
            }
            if (strpos($productCode, ' - ') !== false) {
                $productCode = explode(' - ', $productCode)[0];
            }

            $unitCode = trim($concepto['unidad'] ?? '');
            if (empty($unitCode)) {
                $unitCode = 'E48';
            }
            if (strpos($unitCode, ' - ') !== false) {
                $unitCode = explode(' - ', $unitCode)[0];
            }

            $item = [
                'ProductCode' => $productCode,
                'Description' => trim($concepto['concepto'] ?? '') ?: 'Cuota programa capacitación',
                'UnitCode' => $unitCode,
                'Quantity' => $cantidad,
                'UnitPrice' => $precio,
                'Subtotal' => round($subtotalItem, 2),
            ];

            if ($sinIva) {
                $item['TaxObject'] = '01';
                $item['Total'] = round($subtotalItem, 2);
            } else {
                $ivaItem = round($subtotalItem * 0.16, 2);
                $item['TaxObject'] = '02';
                $item['Taxes'] = [
                    [
                        'Total' => $ivaItem,
                        'Name' => 'IVA',
                        'Base' => round($subtotalItem, 2),
                        'Rate' => 0.16,
                        'IsRetention' => false,
                    ],
                ];
                $item['Total'] = round($subtotalItem + $ivaItem, 2);
            }

            $datosFactura['Items'][] = $item;
        }

        if (empty($datosFactura['Items'])) {
            return response()->json([
                'success' => false,
                'message' => 'No hay conceptos válidos para procesar.',
            ], 422);
        }

        $resultado = $this->crearFacturaCFDI40($datosFactura, $empresaId, 'beca');

        // Calcular montos para guardar
        $subtotalTotal = 0;
        $ivaTotal = 0;
        foreach ($datosFactura['Items'] as $it) {
            $subtotalTotal += $it['Subtotal'] ?? 0;
            if (!empty($it['Taxes'])) {
                foreach ($it['Taxes'] as $tx) {
                    $ivaTotal += $tx['Total'] ?? 0;
                }
            }
        }
        $totalTotal = $subtotalTotal + $ivaTotal;

        if (is_array($resultado) && isset($resultado['error'])) {
            $errorData = json_decode($resultado['error'], true);

            $mensajeError = 'Error al timbrar la factura.';
            $errorDetails = null;

            if ($errorData && isset($errorData['Message'])) {
                $mensajeError = $errorData['Message'];
                if (isset($errorData['ModelState']) && is_array($errorData['ModelState'])) {
                    $detalles = [];
                    foreach ($errorData['ModelState'] as $errores) {
                        $detalles = array_merge($detalles, is_array($errores) ? $errores : [$errores]);
                    }
                    if (!empty($detalles)) {
                        $mensajeError .= ' | ' . implode(' | ', $detalles);
                    }
                    $errorDetails = json_encode($errorData['ModelState']);
                }
            }

            \App\Models\FacturaBecaError::create([
                'empresa_id' => $empresaId,
                'mes' => $mes,
                'anio' => $anio,
                'folio' => $folio,
                'emisor_rfc' => $emisorRfc,
                'emisor_nombre' => $emisorNombre,
                'receptor_rfc' => $receptorRfc,
                'receptor_nombre' => $nombreReceptor,
                'subtotal' => $subtotalTotal,
                'total' => $totalTotal,
                'error_message' => mb_substr($mensajeError, 0, 65000),
                'error_details' => $errorDetails ?: $resultado['error'],
                'request_payload' => json_encode($datosFactura),
            ]);

            return response()->json([
                'success' => false,
                'message' => $mensajeError,
            ], 422);
        }

        // Timbrado exitoso — guardar en tabla de facturas beca
        $datos = $resultado;

        \App\Models\FacturaBeca::create([
            'empresa_id' => $empresaId,
            'mes' => $mes,
            'anio' => $anio,
            'folio' => $folio,
            'facturama_id' => $datos['Id'] ?? $datos['id'] ?? null,
            'uuid' => $datos['Complement']['TaxStamp']['Uuid'] ?? null,
            'cfdi_sign' => $datos['Complement']['TaxStamp']['CfdiSign'] ?? null,
            'sat_cert_number' => $datos['Complement']['TaxStamp']['SatCertNumber'] ?? null,
            'sat_sign' => $datos['Complement']['TaxStamp']['SatSign'] ?? null,
            'rfc_prov_certif' => $datos['Complement']['TaxStamp']['RfcProvCertif'] ?? null,
            'original_string' => $datos['OriginalString'] ?? null,
            'fecha_timbrado' => isset($datos['Date']) ? date('Y-m-d H:i:s', strtotime($datos['Date'])) : now(),
            'emisor_rfc' => $emisorRfc,
            'emisor_nombre' => $emisorNombre,
            'emisor_regimen_fiscal' => $emisorRegimenFiscalCodigo,
            'receptor_rfc' => $receptorRfc,
            'receptor_nombre' => $nombreReceptor,
            'receptor_uso_cfdi' => $usoCfdiCodigo,
            'receptor_regimen_fiscal' => $regimenFiscalCodigo,
            'receptor_codigo_postal' => $receptorCodigoPostal,
            'subtotal' => $datos['Subtotal'] ?? $subtotalTotal,
            'iva' => $ivaTotal,
            'total' => $datos['Total'] ?? $totalTotal,
            'sin_iva' => $sinIva,
            'forma_pago' => $formaPagoCodigo,
            'metodo_pago' => $metodoPago,
            'conceptos_json' => json_encode($datosFactura['Items']),
            'respuesta_api_json' => json_encode($datos),
            'estado' => 'Activo',
        ]);

        $facturamaId = $datos['Id'] ?? $datos['id'] ?? null;

        return response()->json([
            'success' => true,
            'message' => 'Factura timbrada exitosamente.',
            'folio' => $folio,
            'uuid' => $datos['Complement']['TaxStamp']['Uuid'] ?? null,
            'facturama_id' => $facturamaId,
            'redirect_url' => $facturamaId ? route('verfactura.becas', ['uuid' => $facturamaId]) : null,
        ]);

      } catch (\Exception $e) {
          \Log::error('Error en ProcesarFacturaBeca:', [
              'error' => $e->getMessage(),
              'file' => $e->getFile(),
              'line' => $e->getLine(),
          ]);

          try {
              \App\Models\FacturaBecaError::create([
                  'empresa_id' => $empresaId ?? null,
                  'mes' => $mes ?? null,
                  'anio' => $anio ?? null,
                  'folio' => $folio ?? null,
                  'error_message' => 'Excepción: ' . $e->getMessage(),
                  'error_details' => $e->getTraceAsString(),
                  'request_payload' => json_encode($datosFactura ?? []),
              ]);
          } catch (\Exception $logEx) {
              \Log::error('No se pudo guardar error de factura beca: ' . $logEx->getMessage());
          }

          return response()->json([
              'success' => false,
              'message' => 'Error en el servidor: ' . $e->getMessage(),
          ], 500);
      }
    }

    public function procesarFacturaProyecto(Request $request)
    {

       
        try {
            // Recopilar datos del formulario
            $emisorNombre = $request->input('emisor_nombre');
            $emisorRfc = $request->input('emisor_rfc');
            $emisorDireccion = $request->input('emisor_direccion');
            $emisorLugarExpedicion = $request->input('emisor_lugar_expedicion');
            $emisorRegimenFiscal = $request->input('emisor_regimen_fiscal');
            $emisorTelefono = $request->input('emisor_telefono');
            
            $folio = $request->input('folio');
            $idProyecto = $request->input('id_proyecto');
            $idPartida = $request->input('id_partida');
            $tipoServicio = $request->input('tipo_servicio');
            
            $receptorNombre = $request->input('receptor_nombre');
            $receptorRfc = $request->input('receptor_rfc');
            $receptorDireccion = $request->input('receptor_direccion');
            $receptorCodigoPostal = $request->input('receptor_codigo_postal');
            $receptorUsoCfdi = $request->input('receptor_uso_cfdi');
            $receptorRegimenFiscal = $request->input('receptor_regimen_fiscal');
            
            $subtotal = $request->input('subtotal');
            $iva = $request->input('iva');
            $total = $request->input('total');
            
            $formaPago = $request->input('forma_pago');
            $metodoPago = $request->input('metodo_pago');
            $moneda = $request->input('moneda');
            
            // Procesar conceptos
            $conceptosJson = $request->input('conceptos');
            $conceptos = [];
            if ($conceptosJson) {
                $conceptos = json_decode($conceptosJson, true);
            }
            
            // Extraer códigos
            $usoCfdiCodigo = explode(' - ', $receptorUsoCfdi)[0] ?? 'G03';
            $regimenFiscalCodigo = explode(' - ', $receptorRegimenFiscal)[0] ?? '601';
            $emisorRegimenFiscalCodigo = explode(' - ', $emisorRegimenFiscal)[0] ?? '601';
            $formaPagoCodigo = explode(' - ', $formaPago)[0] ?? '99';
            
            // Procesar dirección del receptor
            $direccionPartes = explode(', ', $receptorDireccion);
            $calleNumero = $direccionPartes[0] ?? '';
            $colonia = $direccionPartes[1] ?? '';
            
            // Separar calle y número
            $calleNumeroPartes = explode(' ', $calleNumero);
            $numeroExterior = end($calleNumeroPartes);
            $calle = implode(' ', array_slice($calleNumeroPartes, 0, -1));
            
            // Crear el objeto para crearFacturaCFDI40
            $datosFactura = [
                'CfdiType' => 'I', // I = Ingreso
                'PaymentForm' => $formaPagoCodigo,
                'PaymentMethod' => $metodoPago,
                'ExpeditionPlace' => $emisorLugarExpedicion,
                'Date' => date('Y-m-d H:i:s'),
                'Folio' => $folio,
                'Issuer' => [
                    'FiscalRegime' => $emisorRegimenFiscalCodigo,
                    'Rfc' => $emisorRfc,
                    'Name' => $emisorNombre
                ],
                'Receiver' => [
                    'Rfc' => $receptorRfc,
                    'CfdiUse' => $usoCfdiCodigo,
                    'Name' => $receptorNombre,
                    'FiscalRegime' => $regimenFiscalCodigo,
                    'TaxZipCode' => $receptorCodigoPostal,
                    'Address' => [
                        'Street' => $calle,
                        'ExteriorNumber' => $numeroExterior,
                        'InteriorNumber' => '',
                        'Neighborhood' => $colonia,
                        'ZipCode' => $receptorCodigoPostal,
                        'Municipality' => 'TORREÓN',
                        'State' => 'COAHUILA',
                        'Country' => 'México'
                    ]
                ],
                'Items' => []
            ];
            
            // Procesar conceptos
            foreach ($conceptos as $concepto) {
                // Limpiar y validar cantidad
                $cantidadStr = str_replace(['$', ',', ' '], '', $concepto['cantidad']);
                $cantidad = is_numeric($cantidadStr) ? floatval($cantidadStr) : 0;
                
                // Limpiar y validar precio
                $precioStr = str_replace(['$', ',', ' '], '', $concepto['precio']);
                $precio = is_numeric($precioStr) ? floatval($precioStr) : 0;
                
                // Limpiar y validar importe
                $importeStr = str_replace(['$', ',', ' '], '', $concepto['importe']);
                $importe = is_numeric($importeStr) ? floatval($importeStr) : 0;
                
                // Validar que los valores sean válidos
                if ($cantidad <= 0 || $precio <= 0) {
                    continue; // Saltar conceptos inválidos
                }
                
                // Calcular valores
                $subtotalItem = $cantidad * $precio;
                $ivaItem = $subtotalItem * 0.16;
                $totalItem = $subtotalItem + $ivaItem;
                
                // Validar que el ProductCode sea válido
                $productCode = trim($concepto['producto']);
                if (empty($productCode) || $productCode === 'Buscar producto SAT') {
                    $productCode = '25173108'; // Código por defecto para servicios
                }
                
                // Extraer solo el código numérico si viene con descripción
                if (strpos($productCode, ' - ') !== false) {
                    $productCode = explode(' - ', $productCode)[0];
                }
                
                // Validar que la unidad sea válida
                $unitCode = trim($concepto['unidad']);
                if (empty($unitCode)) {
                    $unitCode = 'E48'; // Unidad de servicio por defecto
                }
                
                // Extraer solo el código de unidad si viene con descripción
                if (strpos($unitCode, ' - ') !== false) {
                    $unitCode = explode(' - ', $unitCode)[0];
                }
                
                $datosFactura['Items'][] = [
                    'ProductCode' => $productCode,
                    'Description' => trim($concepto['concepto']) ?: 'Servicios de Proyecto',
                    'UnitCode' => $unitCode,
                    'Quantity' => $cantidad,
                    'UnitPrice' => $precio,
                    'Subtotal' => $subtotalItem,
                    'TaxObject' => '02',
                    'Taxes' => [
                        [
                            'Total' => round($ivaItem, 2),
                            'Name' => 'IVA',
                            'Base' => $subtotalItem,
                            'Rate' => 0.16,
                            'IsRetention' => false
                        ]
                    ],
                    'Total' => round($totalItem, 2)
                ];
            }
            
            // Validar que haya al menos un concepto válido
            if (empty($datosFactura['Items'])) {
                return back()->with('facturaerror', 'No hay conceptos válidos para procesar la factura del proyecto');
            }
            
            //dd($datosFactura);
            // Llamar al método crearFacturaCFDI40
            $resultado = $this->crearFacturaCFDI40($datosFactura, $idProyecto, $tipoServicio);
            
            
            // Verificar si el resultado contiene un error
            if (is_array($resultado) && isset($resultado['error'])) {
                // Decodificar el JSON que está dentro de la clave "error"
                $errorData = json_decode($resultado['error'], true);
                
                // Log del error completo para debugging
                Log::error('Error de Facturama en Proyecto:', [
                    'error_data' => $errorData,
                    'datos_factura' => $datosFactura,
                    'id_proyecto' => $idProyecto,
                    'id_partida' => $idPartida
                ]);
                
                // Verificar si hay datos de error válidos
                if ($errorData && isset($errorData['Message'])) {
                    $message = $errorData['Message'];
                    $erroresDetallados = [];
                    
                    // Recopilar todos los errores de validación
                    if (isset($errorData['ModelState']) && is_array($errorData['ModelState'])) {
                        foreach ($errorData['ModelState'] as $campo => $errores) {
                            if (is_array($errores)) {
                                foreach ($errores as $error) {
                                    $erroresDetallados[] = $error;
                                }
                            } else {
                                $erroresDetallados[] = $errores;
                            }
                        }
                    }
                    
                    // Si hay errores detallados, mostrarlos todos
                    if (!empty($erroresDetallados)) {
                        $mensajeError = 'Error al timbrar la factura del proyecto:<br><br>';
                        $mensajeError .= '<strong>Errores de validación:</strong><br>';
                        foreach ($erroresDetallados as $error) {
                            $mensajeError .= '• ' . $error . '<br>';
                        }
                        
                        return back()->with('facturaerror', $mensajeError);
                    } else {
                        // Si no hay errores detallados, mostrar el mensaje general
                        return back()->with('facturaerror', 'Error al timbrar la factura del proyecto: ' . $message);
                    }
                } else {
                    // Si no se puede decodificar el error, mostrar el error raw
                    return back()->with('facturaerror', 'Error al timbrar la factura del proyecto: ' . $resultado['error']);
                }
            }
            
            // Si no hay error, mostrar el resultado
            return back()->with('success', 'Factura del proyecto timbrada exitosamente');
            
        } catch (\Exception $e) {
            // Log del error
            Log::error('Error en procesarFacturaProyecto:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            // Mostrar error detallado
            return back()->with('facturaerror', 'Error al timbrar la factura del proyecto: ' . $e->getMessage());
        }
    }

    public function facturama(Request $request)
    {

        dd($request->all());
        try {
            // Configuración de credenciales
            $username = env('USER_FAC');
            $password = env('PWD');

            // Configurar cliente HTTP
            $client = new Client([
                'base_uri' => 'https://apisandbox.facturama.mx',
                'timeout'  => 30.0,
                'auth' => [$username, $password]
            ]);

            // Obtener los datos del request
            $datosFactura = $request->all();

            

            // Validar que los datos requeridos estén presentes
            if (empty($datosFactura)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se recibieron datos para procesar'
                ], 400);
            }

            // Validar estructura básica
            $camposRequeridos = ['CfdiType', 'PaymentForm', 'PaymentMethod', 'ExpeditionPlace', 'Issuer', 'Receiver', 'Items'];
            foreach ($camposRequeridos as $campo) {
                if (!isset($datosFactura[$campo])) {
                    return response()->json([
                        'success' => false,
                        'error' => "Campo requerido faltante: {$campo}"
                    ], 400);
                }
            }

            // Validar que haya al menos un item
            if (empty($datosFactura['Items']) || !is_array($datosFactura['Items'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Debe incluir al menos un item en la factura'
                ], 400);
            }

            // Validar estructura de cada item
            foreach ($datosFactura['Items'] as $index => $item) {
                $itemCamposRequeridos = ['ProductCode', 'Description', 'UnitCode', 'Quantity', 'UnitPrice', 'Subtotal', 'Taxes', 'Total'];
                foreach ($itemCamposRequeridos as $campo) {
                    if (!isset($item[$campo])) {
                        return response()->json([
                            'success' => false,
                            'error' => "Campo requerido faltante en item {$index}: {$campo}"
                        ], 400);
                    }
                }
            }

            // Agregar fecha si no viene
            if (!isset($datosFactura['Date'])) {
                $datosFactura['Date'] = date('Y-m-d H:i:s');
            }

            $headers = ['Content-Type' => 'application/json'];

            // Realizar la petición a la API de Facturama
            $response = $client->post('/3/cfdis', [
                'headers' => $headers,
                'json' => $datosFactura
            ]);

            // Procesar la respuesta exitosa
            if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) {
                $respuesta = json_decode($response->getBody(), true);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Factura creada exitosamente',
                    'data' => $respuesta
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Error en la respuesta de la API',
                    'status_code' => $response->getStatusCode(),
                    'response' => $response->getBody()->getContents()
                ], $response->getStatusCode());
            }

        } catch (RequestException $e) {
            // Manejar errores de la API
            if ($e->hasResponse()) {
                $respuesta = (string)$e->getResponse()->getBody();
                $statusCode = $e->getResponse()->getStatusCode();
                
                return response()->json([
                    'success' => false,
                    'error' => 'Error en la API de Facturama',
                    'status_code' => $statusCode,
                    'response' => $respuesta,
                    'details' => json_decode($respuesta, true)
                ], $statusCode);
            }
            
            return response()->json([
                'success' => false,
                'error' => 'Error de conexión: ' . $e->getMessage()
            ], 500);

        } catch (\Exception $e) {
            // Manejar otros errores
            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }


    public function createCfdi(Request $request)
    {
        try {
            // Configuración de credenciales
            $username = env('USER_FAC');
            $password = env('PWD');
            $apiUrl = 'https://apisandbox.facturama.mx/3/cfdis';
            
            // Datos del CFDI (pueden venir del request o hardcodeados como en el ejemplo)
            $cfdiData = [
                "CfdiType" => "I",
                "PaymentForm" => "02",
                "PaymentMethod" => "PUE",
                "ExpeditionPlace" => "27023",
                "Date" => now()->format('Y-m-d H:i:s'),
                "Folio" => "A00021",
                "Issuer" => [
                    "FiscalRegime" => "601",
                    "Rfc" => "UMM200127ME3",
                    "Name" => "UMMININGN"
                ],
                "Receiver" => [
                    "FiscalRegime" => "601",
                    "Rfc" => "XEXX010101000",
                    "Name" => "CLIENTE DEMO",
                    "CfdiUse" => "G03"
                ],
                "Items" => [
                    [
                        "ProductKey" => "01010101",
                        "IdentificationNumber" => "EDL",
                        "Description" => "Esto es una descripción de ejemplo",
                        "Unit" => "H87",
                        "UnitValue" => 100.00,
                        "Quantity" => 1,
                        "Subtotal" => 100.00,
                        "Taxes" => [
                            [
                                "Total" => 16.00,
                                "Name" => "IVA",
                                "Base" => 100.00,
                                "Rate" => 0.16,
                                "IsRetention" => false
                            ]
                        ],
                        "Total" => 116.00
                    ]
                ]
            ];
            
            // Crear cliente HTTP
            $client = new Client();
            
            // Realizar petición POST
            $response = $client->post($apiUrl, [
                'auth' => [$username, $password],
                'json' => $cfdiData,
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ]);
            
            // Procesar respuesta
            $datos = json_decode($response->getBody(), true);
            
            return response()->json([
                'success' => true,
                'message' => 'CFDI creado exitosamente',
                'data' => $datos
            ]);
            
        } catch (Exception $e) {
            // Log del error
            Log::error('Error al crear CFDI en Facturama: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener factura impresa en PDF
     */
    public function obtenerFacturaImpresion($folio)
    {
        try {
            // Buscar la factura en la base de datos
            $factura = facturacionproductos::where('folio', $folio)->first();
            
            if (!$factura) {
                return response()->json([
                    'success' => false,
                    'message' => 'Factura no encontrada'
                ], 404);
            }
            
            // Configuración de credenciales para Facturama
            $username = env('USER_FAC');
            $password = env('PWD');
            
            // URL para obtener el PDF de la factura
            $apiUrl = "https://apisandbox.facturama.mx/3/cfdis/{$factura->facturama_id}/pdf";
            
            // Crear cliente HTTP
            $client = new Client();
            
            // Realizar petición GET para obtener el PDF
            $response = $client->get($apiUrl, [
                'auth' => [$username, $password],
                'headers' => [
                    'Accept' => 'application/pdf'
                ]
            ]);
            
            // Obtener el contenido del PDF
            $pdfContent = $response->getBody()->getContents();
            
            // Generar nombre del archivo
            $filename = "Factura_{$folio}.pdf";
            
            // Retornar el PDF como respuesta
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
                ->header('Content-Length', strlen($pdfContent));
                
        } catch (Exception $e) {
            // Log del error
            Log::error('Error al obtener factura impresa: ' . $e->getMessage());
            
            // Si no se puede obtener de Facturama, generar un PDF básico
            return $this->generarPDFBasico($factura, $folio);
        }
    }
    
    /**
     * Generar PDF básico como fallback
     */
    private function generarPDFBasico($factura, $folio)
    {
        try {
            // Obtener valores con fallback
            $uuid = $factura->Uuid ?? 'N/A';
            $facturamaId = $factura->facturama_id ?? 'N/A';
            $receiverRfc = $factura->reciver_rfc ?? 'N/A';
            $receiverNombre = $factura->reciver_nombre ?? 'N/A';
            $subtotal = number_format($factura->subtotal ?? 0, 2);
            $total = number_format($factura->total ?? 0, 2);
            $fecha = $factura->date ? date('d/m/Y', strtotime($factura->date)) : 'N/A';
            
            // Crear contenido HTML básico para el PDF
            $html = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='utf-8'>
                <title>Factura {$folio}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
                    .info { margin-bottom: 20px; }
                    .info-row { margin-bottom: 10px; }
                    .total { font-weight: bold; font-size: 18px; margin-top: 20px; border-top: 1px solid #ccc; padding-top: 10px; }
                    .uuid { background-color: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 5px; }
                </style>
            </head>
            <body>
                <div class='header'>
                    <h1>FACTURA</h1>
                    <h2>Folio: {$folio}</h2>
                </div>
                
                <div class='info'>
                    <div class='info-row'>
                        <strong>UUID:</strong> {$uuid}
                    </div>
                    <div class='info-row'>
                        <strong>Facturama ID:</strong> {$facturamaId}
                    </div>
                    <div class='info-row'>
                        <strong>RFC Receptor:</strong> {$receiverRfc}
                    </div>
                    <div class='info-row'>
                        <strong>Nombre Receptor:</strong> {$receiverNombre}
                    </div>
                    <div class='info-row'>
                        <strong>Fecha:</strong> {$fecha}
                    </div>
                </div>
                
                <div class='total'>
                    <div class='info-row'>
                        <strong>Subtotal:</strong> \${$subtotal}
                    </div>
                    <div class='info-row'>
                        <strong>Total:</strong> \${$total}
                    </div>
                </div>
                
                <div class='uuid'>
                    <strong>UUID del CFDI:</strong><br>
                    {$uuid}
                </div>
                
                <div style='margin-top: 30px; text-align: center; font-size: 12px; color: #666;'>
                    <p>Este es un documento generado automáticamente por el sistema.</p>
                    <p>Para obtener el PDF oficial del SAT, contacte al administrador del sistema.</p>
                </div>
            </body>
            </html>
            ";
            
            // Generar nombre del archivo
            $filename = "Factura_{$folio}_basico.pdf";
            
            // Retornar el HTML como PDF (el navegador lo interpretará como PDF)
            return response($html)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
                
        } catch (Exception $e) {
            Log::error('Error al generar PDF básico: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el PDF de la factura'
            ], 500);
        }
    }

    /*------------------------------------------------------------------------------------------------------------------------------------------
    -------------------------------------------------------------------------------------------------------------------------------------------*/

    public function verfacturaproductos($folio,$uuid)
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $datosfactura = DB::select("SELECT id, facturama_id FROM `tblfacturacionproductos` where folio = ? and Uuid = ?;",[$folio,$uuid]);

        $id = $datosfactura[0]->id;
       // dd($datosfactura);

        if(!empty($datosfactura) && isset($datosfactura[0]->id))
        {
            //dd($datosfactura);
            // Configura tu API Key o tus credenciales
    $usuario = env('USER_FAC'); // Tu usuario Facturama Sandbox
    $password = env('PWD'); // Tu contraseña Facturama Sandbox
   
    $cfdi = $datosfactura[0]->facturama_id;

    $client = new Client([
        'base_uri' => 'https://apisandbox.facturama.mx/',
        'auth' => [$usuario, $password],
        'verify' => false,
    ]);

    try {
        $response = $client->request('GET', "cfdi/pdf/issued/{$cfdi}", [
            'headers' => [
                'Accept' => 'application/json',
            ]
        ]);

        
        
        if ($response->getStatusCode() == 200) {
            $pdfContent = $response->getBody()->getContents();

            // Codificar el contenido binario a base64
           // $pdfBase64 = base64_encode($pdfContent);

            // Devolver el contenido en un array como JSON
            $data = json_decode($pdfContent, true);
            $cadena = $data['Content'];
            //dd($cadena);
            
            return  $this->obtenerfacturanomina($cadena);
            
            echo $pdfBase64;
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo obtener el PDF'
            ], 400);
        }
  
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
         
        }
        else
        {
            //buscar en la tabla de facturas no timbradas
            $nofacturado = DB::select("select * from tblnomina_notimbrados where id_nominapago_det = ?;",[$id]);
            echo "No hay factura";
            return back()->with("Errofac","no guardado correctamente");
            
        }

    }

    // obtener factura Becas
    public function verfacturaBecas($uuid)
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        

        
       // dd($datosfactura);

        if(!empty($uuid))
        {
            //dd($datosfactura);
            // Configura tu API Key o tus credenciales
    $usuario = env('USER_FAC'); // Tu usuario Facturama Sandbox
    $password = env('PWD'); // Tu contraseña Facturama Sandbox
   
   

    $client = new Client([
        'base_uri' => 'https://apisandbox.facturama.mx/',
        'auth' => [$usuario, $password],
        'verify' => false
    ]);

    try {
        $response = $client->request('GET', "cfdi/pdf/issued/{$uuid}", [
            'headers' => [
                'Accept' => 'application/json',
            ]
        ]);

        
        
        if ($response->getStatusCode() == 200) {
            $pdfContent = $response->getBody()->getContents();

            // Codificar el contenido binario a base64
           // $pdfBase64 = base64_encode($pdfContent);

            // Devolver el contenido en un array como JSON
            $data = json_decode($pdfContent, true);
            $cadena = $data['Content'];
            //dd($cadena);
            
            return  $this->obtenerfacturanomina($cadena);
            
           // echo $pdfBase64;
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo obtener el PDF'
            ], 400);
        }
  
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
         
        }
        else
        {
            //buscar en la tabla de facturas no timbradas
            $nofacturado = DB::select("select * from tblnomina_notimbrados where id_nominapago_det = ?;",[$id]);
            echo "No hay factura";
            return back()->with("Errofac","no guardado correctamente");
            
        }

    }

    /**
     * Decrementa el inventario de los productos de un servicio
     */
    private function decrementarInventarioPorServicio($id_servicio_enc)
    {

       
        // 1. Obtener los productos y cantidades del servicio
        $productos = DB::table('tblservicios_productos')
            ->select('id_producto', 'cantidad_total')
            ->where('id_servicio_enc', $id_servicio_enc)
            ->get();
        
        foreach ($productos as $producto) {
            // 2. Buscar la existencia actual del producto
            $existencia = DB::table('tblexistencias')
                ->where('id_producto', $producto->id_producto)
                ->first();
                
            if ($existencia) {
                // 3. Calcular la nueva cantidad
                $nuevaCantidad = $existencia->cantidad_existente - $producto->cantidad_total;
                $cantidadreservada = $existencia->cantidad_reservada - $producto->cantidad_total;
                // 5. Actualizar la existencia
                DB::table('tblexistencias')
                    ->where('id_producto', $producto->id_producto)
                    ->update(['cantidad_existente' => $nuevaCantidad],['cantidad_reservada' => $cantidadreservada]);
            }
        }
    }
    
    public function FacturacionProyecto(int $id, $servicio, $id_division)
    {
        // Validar que el servicio sea "Proyecto"
        if (strtolower($servicio) !== 'proyecto') {
            return redirect()->back()->with('error', 'Este método solo es válido para servicios de tipo Proyecto');
        }

        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        
        // Obtener datos del servicio y cliente para proyectos
        $datosFacturacion = DB::table('tblservicios_enc as se')
            ->join('tblclientes as c', 'se.id_cliente', '=', 'c.id')
            ->select([
                'se.id',
                'se.folio',
                'se.nombre as nombre_servicio',
                'se.fecha_inicio',
                'se.fecha_limite',
                'se.costo_externo',
                // Datos del cliente
                'c.nombre as cliente_nombre',
                'c.razon_social as cliente_razon_social',
                'c.rfc as cliente_rfc',
                'c.telefono as cliente_telefono',
                'c.correo_electronico as cliente_email',
                'c.calle as cliente_calle',
                'c.numero_ext as cliente_numero_ext',
                'c.numero_int as cliente_numero_int',
                'c.colonia as cliente_colonia',
                'c.cp as cliente_cp'
            ])
            ->where('se.id', $id)
            ->first();

        // Obtener las divisiones del proyecto
        $divisionesProyecto = DB::table('tdivisiontiemposproyecto as dtp')
            ->join('tblservicios_enc as se', 'dtp.id_servicio', '=', 'se.id')
            ->join('tblclientes as c', 'se.id_cliente', '=', 'c.id')
            ->leftJoin(DB::raw('(SELECT id_partida_proyecto, COUNT(*) as cnt FROM tingresosxpartidaproyecto GROUP BY id_partida_proyecto) as tip'), 'tip.id_partida_proyecto', '=', 'dtp.id')
            ->leftJoin(DB::raw('(SELECT id_partida_proyecto, SUM(monto_ingreso) as total_ingresos FROM tingresosxpartidaproyecto GROUP BY id_partida_proyecto) as sumi'), 'sumi.id_partida_proyecto', '=', 'dtp.id')
            ->select([
                'dtp.id',
                'dtp.id_servicio',
                'dtp.id_tipo_plazo',
                'dtp.plazo',
                'dtp.monto',
                'dtp.otros_conceptos1 as fecha_inicio',
                'dtp.otros_concetpos2 as fecha_fin',
                'dtp.otros_conceptos3 as titulo_division',
                'dtp.created_at',
                DB::raw('COALESCE(tip.cnt, 0) as partidas_count'),
                DB::raw('COALESCE(sumi.total_ingresos, 0) as total_ingresos'),
                'se.folio',
                'se.estado as estado_servicio',
                'c.nombre as cliente'
            ])
            ->where('dtp.id_servicio', $id)
            ->orderBy('dtp.plazo', 'asc')
            ->get();

        // Si se especifica un ID de división, filtrar solo esa división
        if ($id_division && $id_division > 0) {
            $divisionesProyecto = $divisionesProyecto->where('id', $id_division);
        }

        if ($divisionesProyecto->isEmpty()) {
            return redirect()->back()->with('error', 'No se encontraron divisiones para este proyecto');
        }

        // Mapear las divisiones a conceptos de facturación
        $conceptos = $divisionesProyecto->map(function($item) {
            return [
                'producto' => 'DIV-' . $item->plazo,
                'cantidad' => 1,
                'unidad' => 'PZA',
                'concepto' => $item->titulo_division ?? 'División ' . $item->plazo,
                'precio' => number_format($item->monto, 2),
                'importe' => number_format($item->monto, 2)
            ];
        });

        // Calcular totales basándose en las divisiones del proyecto
        $subtotal = $divisionesProyecto->sum('monto');
        $iva = $subtotal * 0.16;
        $total = $subtotal + $iva;

        // Formatear los datos para la vista
        $datos = [
            'emisor' => $this->obtenerDatosEmisorFacturacion(),
            'servicio' => [
                'id' => $datosFacturacion->id ?? 0,
                'folio' => $datosFacturacion->folio ?? '',
                'nombre' => $datosFacturacion->nombre_servicio ?? '',
                'fecha_inicio' => $datosFacturacion->fecha_inicio ?? '',
                'fecha_limite' => $datosFacturacion->fecha_limite ?? '',
                'tipo' => $servicio
            ],
            'conceptos' => $conceptos,
            'totales' => [
                'subtotal' => number_format($subtotal, 2),
                'iva' => number_format($iva, 2),
                'total' => number_format($total, 2)
            ],
            'pago' => [
                'moneda' => 'MXN - Peso Mexicano',
                'forma_pago' => '99 - Por definir',
                'metodo_pago' => 'PUE'
            ],
            'receptor' => [
                'nombre' => $datosFacturacion->cliente_nombre ?? '',
                'razon_social' => $datosFacturacion->cliente_razon_social ?? '',
                'rfc' => $datosFacturacion->cliente_rfc ?? '',
                'telefono' => $datosFacturacion->cliente_telefono ?? '',
                'email' => $datosFacturacion->cliente_email ?? '',
                'direccion' => sprintf('%s %s%s, %s',
                    $datosFacturacion->cliente_calle ?? '',
                    $datosFacturacion->cliente_numero_ext ?? '',
                    $datosFacturacion->cliente_numero_int ? " Int. " . $datosFacturacion->cliente_numero_int : "",
                    $datosFacturacion->cliente_colonia ?? ''
                ),
                'codigo_postal' => $datosFacturacion->cliente_cp ?? '',
                'uso_cfdi' => 'G03 - Gastos en general',
                'regimen_fiscal' => '605 - Sueldos y Salarios e Ingresos Asimilados a Salarios'
            ]
        ];

        return view('Servicios.facturacion', compact('datos', 'varpantallas', 'varsubmenus', 'id', 'servicio', 'id_division'));
    }
    
    
    /*--------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
         public function obtenerfacturaaguinaldo($cadena)
    {

        // Cadena Base64 recibida
        $base64_string = $cadena;

        // (Opcional) Elimina el encabezado si viene en formato data URI
        $base64_string = preg_replace('/^data:application\/pdf;base64,/', '', $base64_string);

        // Decodifica la cadena Base64
        $pdf_data = base64_decode($base64_string);

        // Devolver el PDF al navegador como respuesta
        return Response::make($pdf_data, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Comprobante_Aguinaldo.pdf"',
            'Content-Length' => strlen($pdf_data),
        ]);
    }
        
        public function verfacturaaguinaldo($id)
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $datosfactura = DB::select("SELECT id, id_facturafacil FROM `recibos_aguinaldo` where id = ? order by(id) desc limit 1",[$id]);

       // dd($datosfactura);

        if(!empty($datosfactura) && isset($datosfactura[0]->id))
        {
            //dd($datosfactura);
            // Configura tu API Key o tus credenciales
    $usuario = env('USER_FAC'); // Tu usuario Facturama Sandbox
    $password = env('PWD'); // Tu contraseña Facturama Sandbox
    $cfdi = $datosfactura[0]->id_facturafacil;

    $client = new Client([
        'base_uri' => 'https://api.facturama.mx/',
        'auth' => [$usuario, $password]
    ]);

    try {
        $response = $client->request('GET', "cfdi/pdf/payroll/{$cfdi}", [
            'headers' => [
                'Accept' => 'application/json',
            ]
        ]);

        
        
        if ($response->getStatusCode() == 200) {
            $pdfContent = $response->getBody()->getContents();

            // Codificar el contenido binario a base64
           // $pdfBase64 = base64_encode($pdfContent);

            // Devolver el contenido en un array como JSON
            $data = json_decode($pdfContent, true);
            $cadena = $data['Content'];
            //dd($cadena);
            
            return  $this->obtenerfacturaaguinaldo($cadena);
            
            echo $pdfBase64;
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo obtener el PDF'
            ], 400);
        }
  
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
         
        }
        else
        {
            //buscar en la tabla de aguinaldos no timbrados
            $nofacturado = DB::select("select * from tblaguinaldo_notimbrado where id_aguinaldo_det = ?;",[$id]);
            echo "No hay factura";
            return back()->with("Errofac","no guardado correctamente");
            
        }

    }
    /**------------------------------------------------------------------------------------------------------------------------------------------------------------------- */
    /**------                                               TIMBRADO DEL AGUINALDO                                                 --------------------------------------- */
    /**------------------------------------------------------------------------------------------------------------------------------------------------------------------- */
    /**------------------------------------------------------------------------------------------------------------------------------------------------------------------- */
       public function TimbrarAguinaldo(int $id)
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $varnominas =  $this->obtener_aguinaldos_det($id);
        $Nominaenc = DB::select("select * from tblaguinaldos_enc WHERE id = ?;",[$id]);
        $pruebaempleado = DB::select("select * from tblaguinaldos_det where id = 1;"); //16 
        
        
        //verificar si me sirve re verificar si esta bien calculado el aguinaldo
    //$verificacion = $this->verificarCalculosNominas($varnominas);
    /*
    if ($verificacion) {
        return $verificacion; // Retorna la redirección con errores
    }*/

       // Crear objeto DateTime
       $fecha_actual = new DateTime();
       $anio_actual = $fecha_actual->format('Y');

       // En una línea
       $anio_actual = (new DateTime())->format('Y');

        $fechanominainicio = $anio_actual."-12-01"; // $Nominaenc[0]->fecha_inicio;
        $fechanminafin = $anio_actual."-12-15"; // $Nominaenc[0]->fecha_fin;
        $nombrenomina = $Nominaenc[0]->nombre;
       
       
        

        //echo $pruebaempleado[0]->id." ".$pruebaempleado[0]->idempleado;
        /**Pruebas------------------------------------------------------
        $idetalle = $pruebaempleado[0]->id;
        $emp = $pruebaempleado[0]->id_empleado;

        $respuesta = $this->NominasimpleAguinaldo($idetalle,$emp,$id);
        //$respuesta = $this->verdatosAguinaldo($idetalle,$emp,$id);
        dd($respuesta);*/

        /**Aquie empieza la facturacion masiva */
        
       foreach($varnominas as $ltsnominas)
       {
         


          $respuesta = $this->NominasimpleAguinaldo($ltsnominas->id,$ltsnominas->idempleado,$id);
          

          if(is_array($respuesta))
          {
            // Validar que la respuesta tenga la estructura esperada
            if (!isset($respuesta['Items']) || !is_array($respuesta['Items']) || empty($respuesta['Items'])) {
                \Illuminate\Support\Facades\Log::error('Error en timbrado masivo: La respuesta no contiene Items válidos', [
                    'empleado_id' => $ltsnominas->idempleado,
                    'detalle_id' => $ltsnominas->id,
                    'respuesta' => $respuesta
                ]);
                continue; // Saltar este empleado y continuar con el siguiente
            }
            
            if (!isset($respuesta['Complement']['TaxStamp'])) {
                \Illuminate\Support\Facades\Log::error('Error en timbrado masivo: La respuesta no contiene TaxStamp', [
                    'empleado_id' => $ltsnominas->idempleado,
                    'detalle_id' => $ltsnominas->id,
                    'respuesta' => $respuesta
                ]);
                continue;
            }
            
            if (!isset($respuesta['Id'])) {
                \Illuminate\Support\Facades\Log::error('Error en timbrado masivo: La respuesta no contiene ID de factura', [
                    'empleado_id' => $ltsnominas->idempleado,
                    'detalle_id' => $ltsnominas->id,
                    'respuesta' => $respuesta
                ]);
                continue;
            }
            
            if (!isset($respuesta['Receiver']['Name']) || !isset($respuesta['Receiver']['Rfc'])) {
                \Illuminate\Support\Facades\Log::error('Error en timbrado masivo: La respuesta no contiene datos del receptor', [
                    'empleado_id' => $ltsnominas->idempleado,
                    'detalle_id' => $ltsnominas->id,
                    'respuesta' => $respuesta
                ]);
                continue;
            }
           
          $item = $respuesta['Items'][0];
          $taxStamp = $respuesta['Complement']['TaxStamp'];
          
          // Guardar en tabla recibos_aguinaldo
          $tblrecibosaguinaldo = new ReciboAguinaldo();
          $tblrecibosaguinaldo->id_facturafacil = $respuesta['Id'];
          $tblrecibosaguinaldo->subtotal = $respuesta['Subtotal'];
          $tblrecibosaguinaldo->descuentos = $respuesta['Discount'];
          $tblrecibosaguinaldo->total = $item ['Total'];
          $tblrecibosaguinaldo->observaciones = $respuesta['Observations'];
          $tblrecibosaguinaldo->nombre_receptor = $respuesta['Receiver']['Name'];
          $tblrecibosaguinaldo->rfc_receptor = $respuesta['Receiver']['Rfc'];
          $tblrecibosaguinaldo->descripcion_factura = $item['Description'];
          $tblrecibosaguinaldo->uuid_factura = $taxStamp['Uuid'];
          $tblrecibosaguinaldo->cfdisign = $taxStamp['CfdiSign'];
          $tblrecibosaguinaldo->satcertnumber = $taxStamp['SatCertNumber'];
          $tblrecibosaguinaldo->satsign = $taxStamp['SatSign'];
          $tblrecibosaguinaldo->rfcprovcertif = $taxStamp['RfcProvCertif'];
          $tblrecibosaguinaldo->satus_factura = $respuesta['Status'];
          $tblrecibosaguinaldo->original_string = $respuesta['OriginalString'];
          $tblrecibosaguinaldo->id_tblnominas_pagodet = $ltsnominas->id;
          //$tblrecibosaguinaldo->folio_int = $respuesta['Folio']; //verifivar si se necesitar crear los folios
          $tblrecibosaguinaldo->estado = 'Activo';
          $tblrecibosaguinaldo->created_at = now();
          $tblrecibosaguinaldo->created_by = auth()->user()->name ?? auth()->id();
          $tblrecibosaguinaldo->save();
          
          /*
          $empleado = DB::select("SELECT primer_nombre, segundo_nombre, apellido_paterno, apellido_materno FROM tblempleados WHERE id = ?;",[$ltsnominas->idempleado]);
          echo " EL EMPLEADO :  ".$empleado[0]->primer_nombre." ".$empleado[0]->segundo_nombre." ".$empleado[0]->apellido_paterno." ".$empleado[0]->apellido_materno." "." GENERO FACTURA CON EXITO"."<br>";
         */
         }
          else
          { 
              /*
              $empleado = DB::select("SELECT primer_nombre, segundo_nombre, apellido_paterno, apellido_materno FROM tblempleados WHERE id = ?;",[$ltsnominas->idempleado]);
              echo " El Empledo :  ".$empleado[0]->primer_nombre." ".$empleado[0]->segundo_nombre." ".$empleado[0]->apellido_paterno." ".$empleado[0]->apellido_materno." "." NO genero factura"."<br>";
              */

            // guardaar datos de del emplado y aguinaldo no timbrado
            
            $tblAguinaldoNotimbrado = new AguinaldoNotimbrado();
            $tblAguinaldoNotimbrado->mensaje_error = is_array($respuesta) ? json_encode($respuesta) : $respuesta;
            $tblAguinaldoNotimbrado->idaguinaldo_enc = $id;
            $tblAguinaldoNotimbrado->id_aguinaldo_det = $ltsnominas->id;
            $tblAguinaldoNotimbrado->fecha_inicio = $fechanominainicio;
            $tblAguinaldoNotimbrado->fecha_fin = $fechanminafin;
            $tblAguinaldoNotimbrado->nombre_aguinaldo = $nombrenomina;
            $tblAguinaldoNotimbrado->id_empleado = $ltsnominas->idempleado;
            $tblAguinaldoNotimbrado->created_at = now();
            $tblAguinaldoNotimbrado->created_by = auth()->id();
            $tblAguinaldoNotimbrado->save();
          }

       }


       return back()->with("success","Movimiento Generado con Exito");

      

    }

    /* Comensamos la estrucutura para el timbrado de Aguinaldo */

        // timbra nomina dimple
        public function NominasimpleAguinaldo($idpagodet, $idempleado, $idenc)
        {
            
            /*nomina simple son vacaciones y otros conceptos adicionales isste o imss*/
            $nominaenc = DB::select("select * from tblaguinaldos_enc where id = ?",[$idenc]);
            $nominadetempleado = DB::select("select * from tblaguinaldos_det where id = ?;",[$idpagodet]);
            $datemp = DB::select("select * from tblempleados where id = ?;",[$idempleado]);
            $ultimo_sueldo_base_integrado = DB::select("SELECT salario_diario_integrado FROM `tblnominas_pagodet` WHERE idempleado = ? LIMIT 1;",[$idempleado]);
            $salario_fijo = DB::select("SELECT salario_fijo FROM `tblnominas` WHERE idempleado = ? LIMIT 1;",[$idempleado]);
            //variables sualdos mexico
            $UMA = (float)conceptos_nomina::where('nombre', 'uma_diaria')->first()->valor;
            $salario_minimo_quincenal = (float)conceptos_nomina::where('nombre', 'salario_minimo_quincenal')->first()->valor;
            // Configuración de credenciales
            $username = env('USER_FAC');  // Reemplaza con tu usuario real
            $password = env('PWD'); // Reemplaza con tu password real
    

            // Crear objeto DateTime
            $fecha_actual = new DateTime();
            $anio_actual = $fecha_actual->format('Y');
  
            // En una línea
            $anio_actual = (new DateTime())->format('Y');
              
            $fechanominainicio = $anio_actual . "-01-01"; // $Nominaenc[0]->fecha_inicio;
            $fechanminafin = "2025-12-15";
            //variables de la factura
            $fechainicio = "2025-12-01";
            $fechaf = new DateTime($anio_actual."-12-15");
            $fechafin = $anio_actual."-12-15"; //este
            $sumfecha = $fechaf->modify('1 day');
            $fechapago =  $sumfecha->format('Y-m-d');  //este
    
            //dd($nominadetempleado);
            
        ini_set('max_execution_time', 0); // sin limite de tiempo
        set_time_limit(0);
    $client = new Client([
        'base_uri' => 'https://api.facturama.mx',
        'timeout'  => 30.0,
        'auth' => [$username, $password] // Deshabilitar verificación SSL en desarrollo
    ]);
    foreach($nominadetempleado as $ltsempleado)
    {
        foreach( $datemp as $ltsdatemp)
        {
            //dd($ltsempleado->pago_prima_vacacional);
             //PERSEPCIONES--------------------------------------------
             $sueldos = $ltsempleado->aguinaldo_exento;
             $aguinaldo_isr = $ltsempleado->aguinaldo_gravado;
             $isr = $ltsempleado->isr_calculado;
             /**---------------------------------------------------------- */
             if( $salario_fijo[0]->salario_fijo > $salario_minimo_quincenal || $ltsempleado->dias_aguinaldo_pagados > 15)
             {
             $PerceptionsDetails = [
                [
                    'PerceptionType' => '002',
                    'Code' => '002',
                    'Description' => 'AGUINALDO O GRATIFICACION FIN DE AÑO',
                    'TaxedAmount' => $aguinaldo_isr,
                    'ExemptAmount' => $sueldos
                ]
            ];
                     
             /*-----------------------------------------------------------*/
             //DEDUCCIONES--------------------------------------------
             $deductionsDetails = [
                 [
                     'DeduccionType' => '002',
                     'Code' => 'ISR',
                     'Description' => 'IMPUESTO SOBRE LA RENTA',
                     'Amount' => $isr
                 ]
             ];
            }

            if($salario_fijo[0]->salario_fijo <= $salario_minimo_quincenal && $ltsempleado->dias_aguinaldo_pagados <= 15)
            {
                $PerceptionsDetails = [
                    [
                        'PerceptionType' => '002',
                        'Code' => '002',
                        'Description' => 'AGUINALDO O GRATIFICACION FIN DE AÑO',
                        'TaxedAmount' => 0,
                        'ExemptAmount' => $ltsempleado->aguinaldo_f
                    ]
                ];
                         
            
            }
              
            
    
              //variables para Datos de Empleados
              $puestoe = DB::select("SELECT nombre FROM `tblpuestos` WHERE id = ?;",[$ltsdatemp->idpuesto]);
              $puestoEmpleado = $puestoe[0]->nombre;
      
              //Datos bancarios
              $datosbancarios = DB::select("select banco.nombre, nomi.numero_cuenta, nomi.numero_tarjeta from tblaguinaldos_det agi_det 
      join tblnominas nomi on agi_det.id_nomina = nomi.id
      join tblbancos banco on nomi.idbancos = banco.id where agi_det.id = ?  limit 1;",[$idpagodet]);
      
            $nombrebanco = $datosbancarios[0]->nombre;
            $cuenta = $datosbancarios[0]->numero_cuenta;
            $tarjeta = $datosbancarios[0]->numero_tarjeta;
            
            //escribir el nombre en una linea
             if(!is_null($ltsdatemp->segundo_nombre) && $ltsdatemp->segundo_nombre != " " )
            {
                $nombrecompleto = $ltsdatemp->primer_nombre." ".$ltsdatemp->segundo_nombre." ".$ltsdatemp->apellido_paterno." ".$ltsdatemp->apellido_materno;
            }
            else
            {
               $nombrecompleto = $ltsdatemp->primer_nombre." ".$ltsdatemp->apellido_paterno." ".$ltsdatemp->apellido_materno;
            }
            $base_salary = number_format(floatval($salario_fijo[0]->salario_fijo * 30), 2);

            if( $salario_fijo[0]->salario_fijo > $salario_minimo_quincenal || $ltsempleado->dias_aguinaldo_pagados > 15)
            {
                $data = [
                    'NameId' => 16,
                    'ExpeditionPlace' => '27023',
                    'CfdiType' => 'N',
                    'PaymentMethod' => 'PUE',
                    'Folio' => Null,
                    'Receiver' => [
                        'Rfc' => $ltsdatemp->rfc,
                        'Name' =>$nombrecompleto,
                        'CfdiUse' => 'CN01',
                        'TaxZipCode' => $ltsdatemp->codigo_postal,
                        'FiscalRegime' => '605'
                    ],
                    'Complemento' => [
                        'Payroll' => [
                            'Type' => 'E',
                            'PaymentDate' => $fechapago.'T21:43:59.4011985-06:00',
                            'InitialPaymentDate' => $fechainicio.'T13:43:59.4011985-06:00',
                            'FinalPaymentDate' => $fechafin.'T13:43:59.4011985-06:00',
                            'DaysPaid' => intval($ltsempleado->dias_aguinaldo_pagados),
                            'Issuer' => [
                                'EmployerRegistration' => 'A4078680105',
                                "FromEmployerRfc" => "UMM200127ME3"
                            ],
                            
                            'Employee' => [
                                'Curp' => $ltsdatemp->curp,
                                'SocialSecurityNumber' => $ltsdatemp->nss,
                                'StartDateLaborRelations' => $ltsdatemp->fecha_ingreso.'T00:00:00.3952019-06:00',
                                'ContractType' => '01',
                                'RegimeType' => '02',
                                'Unionized' => false,
                                'TypeOfJourney' => '03',
                                'EmployeeNumber' => $ltsdatemp->id,
                                'Department' => 'General',
                                'Position' => $puestoEmpleado,
                                'PositionRisk' => '4',
                                'FrequencyPayment' => '99',
                                'Bank' => $nombrebanco,
                                'BankAccount' => $cuenta,
                                'BaseSalary' =>  $base_salary,
                                'DailySalary' => $ultimo_sueldo_base_integrado[0]->salario_diario_integrado,
                                'FederalEntityKey' => 'COA'
                            ],
                            'Perceptions' => [
                                'Details' => $PerceptionsDetails
                            ],
                            'Deductions' => [
                                'Details' => $deductionsDetails
                            ],
                            'OtherPayments' => [
                                [
                                    'OtherPaymentType' => '002',
                                    'Code' => '002',
                                    'Description' => 'otro pago',
                                    'Amount' => 0,
                                    'EmploymentSubsidy' => [
                                        'Amount' => 0
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];
            } // termina el if de isr
            if($salario_fijo[0]->salario_fijo <= $salario_minimo_quincenal&& $ltsempleado->dias_aguinaldo_pagados <= 15)
            {
                $data = [
                    'NameId' => 16,
                    'ExpeditionPlace' => '27023',
                    'CfdiType' => 'N',
                    'PaymentMethod' => 'PUE',
                    'Folio' => Null,
                    'Receiver' => [
                        'Rfc' => $ltsdatemp->rfc,
                        'Name' =>$nombrecompleto,
                        'CfdiUse' => 'CN01',
                        'TaxZipCode' => $ltsdatemp->codigo_postal,
                        'FiscalRegime' => '605'
                    ],
                    'Complemento' => [
                        'Payroll' => [
                            'Type' => 'O',
                            'PaymentDate' => $fechapago.'T21:43:59.4011985-06:00',
                            'InitialPaymentDate' => $fechainicio.'T13:43:59.4011985-06:00',
                            'FinalPaymentDate' => $fechafin.'T13:43:59.4011985-06:00',
                            'DaysPaid' => intval($ltsempleado->dias_aguinaldo_pagados),
                            'Issuer' => [
                                'EmployerRegistration' => 'A4078680105',
                                "FromEmployerRfc" => "UMM200127ME3"
                            ],
                            
                            'Employee' => [
                                'Curp' => $ltsdatemp->curp,
                                'SocialSecurityNumber' => $ltsdatemp->nss,
                                'StartDateLaborRelations' => $ltsdatemp->fecha_ingreso.'T00:00:00.3952019-06:00',
                                'ContractType' => '01',
                                'RegimeType' => '02',
                                'Unionized' => false,
                                'TypeOfJourney' => '03',
                                'EmployeeNumber' => $ltsdatemp->id,
                                'Department' => 'General',
                                'Position' => $puestoEmpleado,
                                'PositionRisk' => '4',
                                'FrequencyPayment' => '04',
                                'Bank' => $nombrebanco,
                                'BankAccount' => $cuenta,
                                'BaseSalary' =>  $base_salary,
                                'DailySalary' => $ultimo_sueldo_base_integrado[0]->salario_diario_integrado,
                                'FederalEntityKey' => 'COA'
                            ],
                            'Perceptions' => [
                                'Details' => $PerceptionsDetails
                            ],
                            'OtherPayments' => [
                                [
                                    'OtherPaymentType' => '002',
                                    'Code' => '002',
                                    'Description' => 'otro pago',
                                    'Amount' => 0,
                                    'EmploymentSubsidy' => [
                                        'Amount' => 0
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];
            }
    
    }
    }
    $headers = [
        'Content-Type' => 'application/json'
    ];
    
    //echo dd($data);
    
    try {
        $response = $client->post('/3/cfdis', [
            'headers' => $headers,
            'json' => $data
        ]);
        // dd($response); // Comentado para permitir que continúe la ejecución
         
        $datos = json_decode($response->getBody(), true);

        
        // Guardar datos en la tabla tblfacturacionproductos
        /*$facturacionProducto = facturacionproductos::create([
            'facturama_id' => $datos['Id'] ?? $datos['id'] ?? null,
            'folio' => $datos['Folio'] ?? $datosFactura['Folio'] ?? null,
            'date' => date('Y-m-d', strtotime($datos['Date'] ?? $datosFactura['Date'] ?? now())),
            'reciver_rfc' => $datosFactura['Receiver']['Rfc'] ?? null,
            'reciver_nombre' => $datosFactura['Receiver']['Name'] ?? null,
            'subtotal' => $datos['Subtotal'] ?? 0,
            'total' => $datos['Total'] ?? 0,
            'Uuid' => $datos['Complement']['TaxStamp']['Uuid'] ?? null,
            'CfdiSign' => $datos['Complement']['TaxStamp']['CfdiSign'] ?? null,
            'SatCertNumber' => $datos['Complement']['TaxStamp']['SatCertNumber'] ?? null,
            'SatSign' => $datos['Complement']['TaxStamp']['SatSign'] ?? null,
            'RfcProvCertif' => $datos['Complement']['TaxStamp']['RfcProvCertif'] ?? null,
            'OriginalString' => $datos['OriginalString'] ?? null,
            'estado' => "Activo"
        ]);*/
    
        
        return json_decode($response->getBody(), true);
        
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $respuesta = (string)$e->getResponse()->getBody();
            // dd($respuesta); // Comentado para permitir que continúe la ejecución
            return ['error' => $respuesta];
        }
        
        return ['error' => $e->getMessage()];
    
    }
    
    
    
        }

        /**----------------------------------------------------------------------------------------------------------------------------------------------------------- */

        /**---------------------------------------- Metodos para el timbrado de aguinaldo ----------------------------------------------------------------------------- */
        
        public function verdatosAguinaldo($idpagodet, $idempleado, $idenc)
        {
              /*nomina simple son vacaciones y otros conceptos adicionales isste o imss*/

              $nominaenc = DB::select("select * from tblaguinaldos_enc where id = ?",[$idenc]);
              $nominadetempleado = DB::select("select * from tblaguinaldos_det where id = ?;",[$idpagodet]);
              $datemp = DB::select("select * from tblempleados where id = ?;",[$idempleado]);
              $ultimo_sueldo_base_integrado = DB::select("SELECT salario_diario_integrado FROM `tblnominas_pagodet` WHERE idempleado = ? LIMIT 1;",[$idempleado]);
              $salario_fijo = DB::select("SELECT salario_fijo FROM `tblnominas` WHERE idempleado = ? LIMIT 1;",[$idempleado]);

              // Configuración de credenciales
              $username = env('USER_FAC');  // Reemplaza con tu usuario real
              $password = env('PWD'); // Reemplaza con tu password real

              //dd($datemp);

              // Crear objeto DateTime
             $fecha_actual = new DateTime();
             $anio_actual = $fecha_actual->format('Y');

              // En una línea
             $anio_actual = (new DateTime())->format('Y');
            
              $fechanominainicio = $anio_actual . "-01-01"; // $Nominaenc[0]->fecha_inicio;
              $fechanminafin = "2025-12-15";
              //variables de la factura
              $fechainicio = "2025-12-01";
              $fechaf = new DateTime($anio_actual."-12-15");
              $fechafin = $anio_actual."-12-15"; //este
              $sumfecha = $fechaf->modify('1 day');
              $fechapago =  $sumfecha->format('Y-m-d');  //este
           
        foreach($nominadetempleado as $ltsempleado)
        {
            foreach( $datemp as $ltsdatemp)
            {
            //dd($ltsempleado->pago_prima_vacacional);

             //$respuestaAguinaldo = $this->calcularAguinaldo($ltsempleado->sueldo_diario,0,$ltsdatemp->fecha_ingreso,$ltsempleado->dias_aguinaldo_pagados,"2025-12-16");

             //PERSEPCIONES--------------------------------------------
             $sueldos = $ltsempleado->aguinaldo_exento;
             $aguinaldo_isr = $ltsempleado->aguinaldo_gravado;
             $isr = $ltsempleado->isr_calculado;
             /**---------------------------------------------------------- */
             if( $salario_fijo[0]->salario_fijo > $salario_minimo_quincenal || $ltsempleado->dias_aguinaldo_pagados > 15)
             {
             $PerceptionsDetails = [
                [
                    'PerceptionType' => '002',
                    'Code' => '002',
                    'Description' => 'AGUINALDO O GRATIFICACION FIN DE AÑO',
                    'TaxedAmount' => $aguinaldo_isr,
                    'ExemptAmount' => $sueldos
                ]
            ];
                     
             /*-----------------------------------------------------------*/
             //DEDUCCIONES--------------------------------------------
             $deductionsDetails = [
                 [
                     'DeduccionType' => '002',
                     'Code' => 'ISR',
                     'Description' => 'IMPUESTO SOBRE LA RENTA',
                     'Amount' => $isr
                 ]
             ];
            }

            if($salario_fijo[0]->salario_fijo <= $salario_minimo_quincenal && $ltsempleado->dias_aguinaldo_pagados <= 15)
            {
                $PerceptionsDetails = [
                    [
                        'PerceptionType' => '002',
                        'Code' => '002',
                        'Description' => 'AGUINALDO O GRATIFICACION FIN DE AÑO',
                        'TaxedAmount' => 0,
                        'ExemptAmount' => $ltsempleado->aguinaldo_f
                    ]
                ];
                         
            
            }
              
            //----------------------------------------------------------
            /* Agregar total_deudores a deducciones */
            
            
            
           
             //variables para Datos de Empleados
            $puestoe = DB::select("SELECT nombre FROM `tblpuestos` WHERE id = ?;",[$ltsdatemp->idpuesto]);
            $puestoEmpleado = $puestoe[0]->nombre;
    
            //Datos bancarios
            $datosbancarios = DB::select("select banco.nombre, nomi.numero_cuenta, nomi.numero_tarjeta from tblaguinaldos_det agi_det 
    join tblnominas nomi on agi_det.id_nomina = nomi.id
    join tblbancos banco on nomi.idbancos = banco.id where agi_det.id = ?  limit 1;",[$idpagodet]);
    
    $nombrebanco = $datosbancarios[0]->nombre;
    $cuenta = $datosbancarios[0]->numero_cuenta;
    $tarjeta = $datosbancarios[0]->numero_tarjeta;
    
    
            //escribir el nombre en una linea
            if(!is_null($ltsdatemp->segundo_nombre) && $ltsdatemp->segundo_nombre != " ")
            {
            $nombrecompleto = $ltsdatemp->primer_nombre." ".$ltsdatemp->segundo_nombre." ".$ltsdatemp->apellido_paterno." ".$ltsdatemp->apellido_materno;
            }
            else
            {
                $nombrecompleto = $ltsdatemp->primer_nombre." ".$ltsdatemp->apellido_paterno." ".$ltsdatemp->apellido_materno;
            }
    
            
            $base_salary = number_format(floatval($salario_fijo[0]->salario_fijo * 30), 2);
    
        if( $salario_fijo[0]->salario_fijo > $salario_minimo_quincenal || $ltsempleado->dias_aguinaldo_pagados > 15)
        {
            $data = [
                'NameId' => 16,
                'ExpeditionPlace' => '27023',
                'CfdiType' => 'N',
                'PaymentMethod' => 'PUE',
                'Folio' => Null,
                'Receiver' => [
                    'Rfc' => $ltsdatemp->rfc,
                    'Name' =>$nombrecompleto,
                    'CfdiUse' => 'CN01',
                    'TaxZipCode' => $ltsdatemp->codigo_postal,
                    'FiscalRegime' => '605'
                ],
                'Complemento' => [
                    'Payroll' => [
                        'Type' => 'E',
                        'PaymentDate' => $fechapago.'T21:43:59.4011985-06:00',
                        'InitialPaymentDate' => $fechainicio.'T13:43:59.4011985-06:00',
                        'FinalPaymentDate' => $fechafin.'T13:43:59.4011985-06:00',
                        'DaysPaid' => intval($ltsempleado->dias_aguinaldo_pagados),
                        'Issuer' => [
                            'EmployerRegistration' => 'A4078680105',
                            "FromEmployerRfc" => "UMM200127ME3"
                        ],
                        
                        'Employee' => [
                            'Curp' => $ltsdatemp->curp,
                            'SocialSecurityNumber' => $ltsdatemp->nss,
                            'StartDateLaborRelations' => $ltsdatemp->fecha_ingreso.'T00:00:00.3952019-06:00',
                            'ContractType' => '01',
                            'RegimeType' => '02',
                            'Unionized' => false,
                            'TypeOfJourney' => '03',
                            'EmployeeNumber' => $ltsdatemp->id,
                            'Department' => 'General',
                            'Position' => $puestoEmpleado,
                            'PositionRisk' => '4',
                            'FrequencyPayment' => '04',
                            'Bank' => $nombrebanco,
                            'BankAccount' => $cuenta,
                            'BaseSalary' =>  $base_salary,
                            'DailySalary' => $ultimo_sueldo_base_integrado[0]->salario_diario_integrado,
                            'FederalEntityKey' => 'COA'
                        ],
                        'Perceptions' => [
                            'Details' => $PerceptionsDetails
                        ],
                        'Deductions' => [
                            'Details' => $deductionsDetails
                        ],
                        'OtherPayments' => [
                            [
                                'OtherPaymentType' => '002',
                                'Code' => '002',
                                'Description' => 'otro pago',
                                'Amount' => 0,
                                'EmploymentSubsidy' => [
                                    'Amount' => 0
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        } // termina el if de isr
        if($salario_fijo[0]->salario_fijo <= $salario_minimo_quincenal&& $ltsempleado->dias_aguinaldo_pagados <= 15)
        {
            $data = [
                'NameId' => 16,
                'ExpeditionPlace' => '27023',
                'CfdiType' => 'N',
                'PaymentMethod' => 'PUE',
                'Folio' => Null,
                'Receiver' => [
                    'Rfc' => $ltsdatemp->rfc,
                    'Name' =>$nombrecompleto,
                    'CfdiUse' => 'CN01',
                    'TaxZipCode' => $ltsdatemp->codigo_postal,
                    'FiscalRegime' => '605'
                ],
                'Complemento' => [
                    'Payroll' => [
                        'Type' => 'O',
                        'PaymentDate' => $fechapago.'T21:43:59.4011985-06:00',
                        'InitialPaymentDate' => $fechainicio.'T13:43:59.4011985-06:00',
                        'FinalPaymentDate' => $fechafin.'T13:43:59.4011985-06:00',
                        'DaysPaid' => intval($ltsempleado->dias_aguinaldo_pagados),
                        'Issuer' => [
                            'EmployerRegistration' => 'A4078680105',
                            "FromEmployerRfc" => "UMM200127ME3"
                        ],
                        
                        'Employee' => [
                            'Curp' => $ltsdatemp->curp,
                            'SocialSecurityNumber' => $ltsdatemp->nss,
                            'StartDateLaborRelations' => $ltsdatemp->fecha_ingreso.'T00:00:00.3952019-06:00',
                            'ContractType' => '01',
                            'RegimeType' => '02',
                            'Unionized' => false,
                            'TypeOfJourney' => '03',
                            'EmployeeNumber' => $ltsdatemp->id,
                            'Department' => 'General',
                            'Position' => $puestoEmpleado,
                            'PositionRisk' => '4',
                            'FrequencyPayment' => '04',
                            'Bank' => $nombrebanco,
                            'BankAccount' => $cuenta,
                            'BaseSalary' =>  $base_salary,
                            'DailySalary' => $ultimo_sueldo_base_integrado[0]->salario_diario_integrado,
                            'FederalEntityKey' => 'COA'
                        ],
                        'Perceptions' => [
                            'Details' => $PerceptionsDetails
                        ],
                        'OtherPayments' => [
                            [
                                'OtherPaymentType' => '002',
                                'Code' => '002',
                                'Description' => 'otro pago',
                                'Amount' => 0,
                                'EmploymentSubsidy' => [
                                    'Amount' => 0
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }
    }
    dd($data);
    return $data;
    }
    
    //echo dd($data);
    
        } // llave 


   public function veraguinaldotimbrado($id)
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $varnominas =  $this->obtener_aguinaldos_det($id);
        $empleadosnotimbrados = $this->listadoAguinaldosNotimbrados($id);
        $aguinaldotimbrado =  $this->obteneraguinaldotimbrado($id);
        $validaTimbradofallido =  $this->validaAguinaldoTimbradofallido($id);
        $obtnertimbradosFallidos =  $this->obtenerAguinaldosTimbradosFallidos($id);
        
        $empleadostimbrados =  DB::select("SELECT id_tblnominas_pagodet from  recibos_aguinaldo;");
        // verificar si hay datos facturados o ya se timbro el aguinaldo
        $comparacion = $this->compararIdsAguinaldos($varnominas, $empleadostimbrados);

        return view('nominas/aguinaldo/veraguinaldotimbrado', compact('varpantallas', 'varsubmenus', 'varnominas',
        'empleadosnotimbrados','aguinaldotimbrado','validaTimbradofallido','obtnertimbradosFallidos'));

    }
    
    public function compararIdsAguinaldos($aguinaldosCollection, $segundoArreglo)
    {
        // Convertir a colecciones para mejor manejo
        $idsAguinaldos = $aguinaldosCollection->pluck('id_aguinaldo_det');
        $idsSegundoArreglo = collect($segundoArreglo)->pluck('id_tblnominas_pagodet');
        
        // Encontrar coincidencias y diferencias
        $idsCoincidentes = $idsAguinaldos->intersect($idsSegundoArreglo);
        $idsFaltantes = $idsAguinaldos->diff($idsSegundoArreglo);
        
        // Obtener detalles de los faltantes
        $detallesFaltantes = $aguinaldosCollection
            ->whereIn('id_aguinaldo_det', $idsFaltantes)
            ->map(function($aguinaldo) {
                return [
                    'id' => $aguinaldo->id_aguinaldo_det,
                    'empleado' => $aguinaldo->nombre_empleado,
                    'puesto' => $aguinaldo->puesto
                ];
            });
        
        // Preparar resultado
        $resultado = [
            'total' => $idsAguinaldos->count(),
            'coincidentes' => $idsCoincidentes->count(),
            'faltantes' => $idsFaltantes->count(),
            'detalles_faltantes' => $detallesFaltantes->values()->toArray(),
            'porcentaje_coincidencia' => round(($idsCoincidentes->count() / $idsAguinaldos->count()) * 100, 2)
        ];
        
        return $resultado;
    }
    //termina timbrado de AGUINALDO------------------------------------------------------------------------------------

    /**
     * Timbrar Complemento de Pago 2.0 (CFDI tipo P) en Facturama.
     *
     * Documentación: https://apisandbox.facturama.mx/guias/cfdi40/complementos/complemento-pago-20
     *
     * @param array $datosComplemento Estructura con Receiver, Payments (RelatedDocuments), ExpeditionPlace, etc.
     * @param int|null $id_factura_original ID en tblfacturacionproductos para persistir en tblcomplementos_pago
     * @return array Respuesta de Facturama o array con clave 'error'
     */
    public function CrearComplementoPago(array $datosComplemento, ?int $id_factura_original = null)
    {
        $username = env('USER_FAC');
        $password = env('PWD');

        $client = new Client([
            'base_uri' => 'https://apisandbox.facturama.mx',
            'timeout'  => 3000.0,
            'auth' => [$username, $password],
            'verify' => false,
        ]);

        // CFDI tipo P: sin Items, PaymentMethod, PaymentForm ni Currency a nivel raíz
        $data = [
            'CfdiType' => 'P',
            'NameId' => $datosComplemento['NameId'] ?? '14',
            'ExpeditionPlace' => $datosComplemento['ExpeditionPlace'],
            'Folio' => $datosComplemento['Folio'] ?? null,
            'Receiver' => [
                'Rfc' => $datosComplemento['Receiver']['Rfc'],
                'CfdiUse' => $datosComplemento['Receiver']['CfdiUse'] ?? 'CP01',
                'Name' => $datosComplemento['Receiver']['Name'],
                'FiscalRegime' => $datosComplemento['Receiver']['FiscalRegime'],
                'TaxZipCode' => $datosComplemento['Receiver']['TaxZipCode'],
            ],
            'Complemento' => [
                'Payments' => [],
            ],
        ];

        if (!empty($datosComplemento['Issuer'])) {
            $data['Issuer'] = [
                'FiscalRegime' => $datosComplemento['Issuer']['FiscalRegime'],
                'Rfc' => $datosComplemento['Issuer']['Rfc'],
                'Name' => $datosComplemento['Issuer']['Name'],
            ];
        }

        foreach ($datosComplemento['Payments'] as $pago) {
            $paymentData = [
                'Date' => $pago['Date'],
                'PaymentForm' => $pago['PaymentForm'],
                'Amount' => $pago['Amount'],
                'RelatedDocuments' => [],
            ];

            if (!empty($pago['Currency'])) {
                $paymentData['Currency'] = $pago['Currency'];
            }

            foreach ($pago['RelatedDocuments'] as $doc) {
                $relatedDoc = [
                    'TaxObject' => $doc['TaxObject'] ?? '01',
                    'Uuid' => $doc['Uuid'],
                    'PaymentMethod' => $doc['PaymentMethod'] ?? 'PPD',
                    'PartialityNumber' => (string) ($doc['PartialityNumber'] ?? '1'),
                    'PreviousBalanceAmount' => $doc['PreviousBalanceAmount'],
                    'AmountPaid' => $doc['AmountPaid'],
                    'ImpSaldoInsoluto' => $doc['ImpSaldoInsoluto'],
                ];

                if (!empty($doc['Serie'])) {
                    $relatedDoc['Serie'] = $doc['Serie'];
                }
                if (!empty($doc['Folio'])) {
                    $relatedDoc['Folio'] = $doc['Folio'];
                }
                if (!empty($doc['Currency'])) {
                    $relatedDoc['Currency'] = $doc['Currency'];
                }
                if (isset($doc['EquivalenceDocRel'])) {
                    $relatedDoc['EquivalenceDocRel'] = $doc['EquivalenceDocRel'];
                }

                if (!empty($doc['Taxes']) && is_array($doc['Taxes'])) {
                    $relatedDoc['Taxes'] = [];
                    foreach ($doc['Taxes'] as $tax) {
                        $relatedDoc['Taxes'][] = [
                            'Name' => $tax['Name'],
                            'Rate' => $tax['Rate'],
                            'Total' => $tax['Total'],
                            'Base' => $tax['Base'],
                            'IsRetention' => $tax['IsRetention'] ?? false,
                        ];
                    }
                }

                $paymentData['RelatedDocuments'][] = $relatedDoc;
            }

            $data['Complemento']['Payments'][] = $paymentData;
        }

        $headers = ['Content-Type' => 'application/json'];

        try {
            $response = $client->post('/3/cfdis', [
                'headers' => $headers,
                'json' => $data,
            ]);

            $datos = json_decode($response->getBody()->getContents(), true);

            if ($id_factura_original !== null) {
                $this->guardarComplementoPagoLocal($datosComplemento, $datos, $id_factura_original);
            }

            return $datos;
        } catch (RequestException $e) {
            return ['error' => $this->extraerErrorFacturama($e)];
        }
    }

    /**
     * Persiste el complemento de pago timbrado en tblcomplementos_pago.
     */
    private function guardarComplementoPagoLocal(array $datosComplemento, array $respuestaFacturama, int $idFacturaOriginal): void
    {
        $factura = facturacionproductos::find($idFacturaOriginal);
        if (!$factura) {
            return;
        }

        $pago = $datosComplemento['Payments'][0] ?? [];
        $doc = $pago['RelatedDocuments'][0] ?? [];

        ComplementoPago::create([
            'id_factura_original' => $idFacturaOriginal,
            'factura_uuid' => $doc['Uuid'] ?? $factura->Uuid,
            'folio_factura' => $doc['Folio'] ?? $factura->folio,
            'folio_complemento' => $respuestaFacturama['Folio'] ?? $datosComplemento['Folio'] ?? null,
            'facturama_id' => $respuestaFacturama['Id'] ?? $respuestaFacturama['id'] ?? null,
            'uuid' => $respuestaFacturama['Complement']['TaxStamp']['Uuid'] ?? null,
            'fecha_pago' => isset($pago['Date']) ? Carbon::parse($pago['Date']) : now(),
            'forma_pago' => $pago['PaymentForm'] ?? '03',
            'numero_parcialidad' => (int) ($doc['PartialityNumber'] ?? 1),
            'saldo_anterior' => (float) ($doc['PreviousBalanceAmount'] ?? 0),
            'monto_pagado' => (float) ($doc['AmountPaid'] ?? $pago['Amount'] ?? 0),
            'saldo_insoluto' => (float) ($doc['ImpSaldoInsoluto'] ?? 0),
            'receptor_rfc' => $datosComplemento['Receiver']['Rfc'] ?? $factura->reciver_rfc,
            'receptor_nombre' => $datosComplemento['Receiver']['Name'] ?? $factura->reciver_nombre,
            'estado' => 'Timbrado',
            'respuesta_api' => json_encode($respuestaFacturama),
            'created_by' => auth()->user()->name ?? 'system',
        ]);

        $saldoInsoluto = (float) ($doc['ImpSaldoInsoluto'] ?? 0);
        if ($saldoInsoluto <= 0.01) {
            $factura->update(['estado' => 'Pagado']);
        } elseif ($factura->estado !== 'Cobrando') {
            $factura->update(['estado' => 'Cobrando']);
        }

        try {
            $montoPagado = (float) ($doc['AmountPaid'] ?? $pago['Amount'] ?? 0);
            $formaPago = $pago['PaymentForm'] ?? '03';
            $fechaPago = isset($pago['Date']) ? Carbon::parse($pago['Date']) : now();
            app(\App\Services\FacturacionFinanzasService::class)->procesarPostComplemento(
                $factura->fresh(),
                $montoPagado,
                $formaPago,
                $fechaPago
            );
        } catch (\Throwable $e) {
            Log::error('Error al registrar complemento en Finanzas: ' . $e->getMessage());
        }
    }

    public function indexFacturas(Request $request)
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();

            $query = facturacionproductos::query()
                ->whereNotNull('Uuid')
                ->where('Uuid', '!=', '')
                // Solo facturas raíz: las relacionadas (sustitutos) van en sublista
                ->where(function ($q) {
                    $q->whereNull('referencia_factura')
                        ->orWhere('referencia_factura', '=', '');
                });

            if ($request->filled('cliente')) {
                $cliente = $request->cliente;
                $query->where(function ($q) use ($cliente) {
                    $q->where('reciver_nombre', 'like', '%' . $cliente . '%')
                        ->orWhere('reciver_rfc', 'like', '%' . $cliente . '%');
                });
            }

            if ($request->filled('folio')) {
                $query->where('folio', 'like', '%' . $request->folio . '%');
            }

            if ($request->filled('metodo_pago')) {
                $query->where('metodo_pago', $request->metodo_pago);
            }

            if ($request->filled('fecha_desde')) {
                $query->where('date', '>=', $request->fecha_desde);
            }

            if ($request->filled('fecha_hasta')) {
                $query->where('date', '<=', $request->fecha_hasta);
            }

            $facturasEmitidas = $query
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->get();

            $facturaIds = $facturasEmitidas->pluck('id')->all();
            $facturaUuids = $facturasEmitidas->pluck('Uuid')->filter()->values()->all();

            $complementosPorFactura = collect();
            $notasCreditoPorFactura = collect();
            $notasDebitoPorFactura = collect();
            $sustitutosPorUuid = collect();

            if (!empty($facturaIds)) {
                $complementosPorFactura = ComplementoPago::query()
                    ->whereIn('id_factura_original', $facturaIds)
                    ->orderBy('numero_parcialidad')
                    ->orderByDesc('id')
                    ->get()
                    ->groupBy('id_factura_original');

                $notasCreditoPorFactura = \App\Models\NotaCredito::query()
                    ->whereIn('id_factura_original', $facturaIds)
                    ->orderByDesc('id')
                    ->get()
                    ->groupBy('id_factura_original');

                $notasDebitoPorFactura = \App\Models\NotaDebito::query()
                    ->whereIn('id_factura_original', $facturaIds)
                    ->orderByDesc('id')
                    ->get()
                    ->groupBy('id_factura_original');
            }

            if (!empty($facturaUuids)) {
                $sustitutosPorUuid = facturacionproductos::query()
                    ->whereIn('referencia_factura', $facturaUuids)
                    ->whereNotNull('Uuid')
                    ->where('Uuid', '!=', '')
                    ->orderByDesc('date')
                    ->orderByDesc('id')
                    ->get()
                    ->groupBy('referencia_factura');
            }

            $facturasEmitidas->each(function ($factura) use (
                $complementosPorFactura,
                $notasCreditoPorFactura,
                $notasDebitoPorFactura,
                $sustitutosPorUuid
            ) {
                $relacionados = collect();

                foreach ($complementosPorFactura->get($factura->id, collect()) as $cp) {
                    $relacionados->push((object) [
                        'tipo' => 'complemento',
                        'tipo_label' => 'Complemento de pago',
                        'badge' => 'info',
                        'folio' => $cp->folio_complemento ?? ('CP-' . $cp->id),
                        'fecha' => $cp->fecha_pago,
                        'detalle' => 'Parc. ' . ($cp->numero_parcialidad ?? '-') . ' · Forma ' . ($cp->forma_pago ?? '—'),
                        'uuid' => $cp->uuid,
                        'monto' => (float) ($cp->monto_pagado ?? 0),
                        'estado' => $cp->estado ?? '—',
                        'ver_url' => null,
                        'pdf_url' => ($cp->estado === 'Timbrado' && $cp->facturama_id)
                            ? route('complementosPago.pdf', $cp->id)
                            : null,
                    ]);
                }

                foreach ($notasCreditoPorFactura->get($factura->id, collect()) as $nc) {
                    $relacionados->push((object) [
                        'tipo' => 'nota_credito',
                        'tipo_label' => 'Nota de crédito',
                        'badge' => 'success',
                        'folio' => $nc->folio_nota ?? ('NC-' . $nc->id),
                        'fecha' => $nc->created_at,
                        'detalle' => $nc->motivo_descripcion ?? $nc->motivo ?? '—',
                        'uuid' => $nc->uuid,
                        'monto' => (float) ($nc->total ?? 0),
                        'estado' => $nc->estado ?? '—',
                        'ver_url' => ($nc->estado === 'Timbrada' && $nc->facturama_id)
                            ? route('notasCredito.ver', $nc->id)
                            : null,
                        'pdf_url' => ($nc->estado === 'Timbrada' && $nc->facturama_id)
                            ? route('notasCredito.pdf', $nc->id)
                            : null,
                    ]);
                }

                foreach ($notasDebitoPorFactura->get($factura->id, collect()) as $nd) {
                    $esFiscal = ($nd->tipo_documento ?? '') === 'fiscal';
                    $relacionados->push((object) [
                        'tipo' => 'nota_debito',
                        'tipo_label' => $esFiscal ? 'Nota de débito fiscal' : 'Nota de débito comercial',
                        'badge' => 'warning',
                        'folio' => $nd->folio_nota ?? ('ND-' . $nd->id),
                        'fecha' => $nd->created_at,
                        'detalle' => $nd->motivo_descripcion ?? $nd->motivo ?? '—',
                        'uuid' => $nd->uuid,
                        'monto' => (float) ($nd->total ?? 0),
                        'estado' => $nd->estado ?? '—',
                        'ver_url' => ($nd->estado === 'Timbrada' && $nd->facturama_id)
                            ? route('notasDebito.ver', $nd->id)
                            : null,
                        'pdf_url' => ($nd->estado === 'Timbrada' && $nd->facturama_id)
                            ? route('notasDebito.pdf', $nd->id)
                            : null,
                    ]);
                }

                foreach ($sustitutosPorUuid->get($factura->Uuid, collect()) as $sust) {
                    $relacionados->push((object) [
                        'tipo' => 'sustituto',
                        'tipo_label' => 'CFDI sustituto',
                        'badge' => 'secondary',
                        'folio' => $sust->folio ?? ('F-' . $sust->id),
                        'fecha' => $sust->date,
                        'detalle' => 'Método ' . ($sust->metodo_pago ?? '—') . ' · Relación 04',
                        'uuid' => $sust->Uuid,
                        'monto' => (float) ($sust->total ?? 0),
                        'estado' => (($sust->cancelada ?? 'A') !== 'A') ? 'Cancelada' : ($sust->estado ?? 'Timbrada'),
                        'ver_url' => !empty($sust->facturama_id) ? route('facturas.ver', $sust->id) : null,
                        'pdf_url' => !empty($sust->facturama_id) ? route('facturas.pdf', $sust->id) : null,
                    ]);
                }

                $factura->documentos_relacionados = $relacionados;
                $factura->tiene_relacionados = $relacionados->isNotEmpty();
                $factura->total_relacionados = $relacionados->count();
            });

            return view('Tesoreria.Facturas', compact(
                'varpantallas',
                'varsubmenus',
                'facturasEmitidas'
            ));
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with('warningBD', 'no guardado correctamente');
        }
    }

    public function verFacturaEmitida(int $id)
    {
        $factura = facturacionproductos::findOrFail($id);

        if (empty($factura->Uuid)) {
            return back()->with('warning', 'La factura no está timbrada (sin UUID)');
        }

        if (empty($factura->facturama_id)) {
            return back()->with('warning', 'La factura no tiene identificador de Facturama');
        }

        try {
            $base64 = $this->obtenerPdfBase64Facturama($factura->facturama_id);
            $filename = 'Factura_' . ($factura->folio ?? $factura->id) . '.pdf';

            return $this->respuestaPdfDesdeBase64($base64, $filename, true);
        } catch (\Exception $e) {
            Log::error('Error al ver PDF factura', ['id' => $id, 'error' => $e->getMessage()]);
            return back()->with('warning', 'No se pudo abrir el PDF de la factura: ' . $e->getMessage());
        }
    }

    public function descargarPdfFacturaEmitida(int $id)
    {
        $factura = facturacionproductos::findOrFail($id);

        if (empty($factura->Uuid)) {
            return back()->with('warning', 'La factura no está timbrada (sin UUID)');
        }

        if (empty($factura->facturama_id)) {
            return back()->with('warning', 'La factura no tiene identificador de Facturama');
        }

        try {
            $base64 = $this->obtenerPdfBase64Facturama($factura->facturama_id);
            $filename = 'Factura_' . ($factura->folio ?? $factura->id) . '.pdf';

            return $this->respuestaPdfDesdeBase64($base64, $filename, false);
        } catch (\Exception $e) {
            Log::error('Error al descargar PDF factura', ['id' => $id, 'error' => $e->getMessage()]);
            return back()->with('warning', 'No se pudo descargar el PDF de la factura: ' . $e->getMessage());
        }
    }

    /**
     * Representación impresa personalizada (formato IOHISA) de una factura timbrada.
     */
    public function representacionImpresaFactura(int $id)
    {
        $factura = facturacionproductos::findOrFail($id);

        if (empty($factura->Uuid)) {
            return back()->with('warning', 'La factura no está timbrada (sin UUID).');
        }

        try {
            $datos = $this->construirDatosRepresentacionFactura($factura);
            return response()->view('Tesoreria.FacturaRepresentacion', $datos);
        } catch (\Exception $e) {
            Log::error('Error al generar representación de factura', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'No se pudo generar la representación: ' . $e->getMessage());
        }
    }

    private function construirDatosRepresentacionFactura(facturacionproductos $factura): array
    {
        $cfdi = $this->obtenerCfdiEmitidoFacturama($factura->facturama_id);
        $emisorFiscal = $this->obtenerDatosEmisorFiscal();
        $fiscalRow = DatosFiscalesEmpresa::query()
            ->where('estatus', 'ACTIVO')
            ->orderByDesc('id')
            ->first() ?? DatosFiscalesEmpresa::query()->orderByDesc('id')->first();

        $emisor = [
            'nombre' => $emisorFiscal['nombre'] ?? '',
            'rfc' => $emisorFiscal['rfc'] ?? '',
            'calle' => trim(($fiscalRow->calle ?? '') . ' ' . ($fiscalRow->numero_exterior ?? '')),
            'colonia' => !empty($fiscalRow->colonia) ? ('Col. ' . $fiscalRow->colonia) : '',
            'municipio' => $fiscalRow->municipio ?? '',
            'estado' => $fiscalRow->estado ?? '',
            'codigo_postal' => $fiscalRow->codigo_postal ?? ($emisorFiscal['lugar_expedicion'] ?? ''),
            'correo' => $fiscalRow->correo ?? ($emisorFiscal['correo'] ?? ''),
            'telefono' => $fiscalRow->telefono ?? ($emisorFiscal['telefono'] ?? ''),
            'regimen_fiscal_texto' => $emisorFiscal['regimen_fiscal_texto']
                ?? (($emisorFiscal['regimen_fiscal'] ?? '601') . '/General de Ley Personas Morales'),
        ];

        if (!empty($cfdi['Issuer']['Name'])) {
            $emisor['nombre'] = $cfdi['Issuer']['Name'];
        }
        if (!empty($cfdi['Issuer']['Rfc'])) {
            $emisor['rfc'] = $cfdi['Issuer']['Rfc'];
        }

        $receptorBase = $this->obtenerDatosReceptorFiscal($factura, $cfdi);
        $cliente = !empty($receptorBase['rfc'])
            ? Clientes::query()->where('rfc', $receptorBase['rfc'])->first()
            : null;

        $dirLinea1 = trim(($cliente->calle ?? '') . ' ' . ($cliente->numero_ext ?? ''));
        if (!empty($cliente->numero_int)) {
            $dirLinea1 .= ' Int. ' . $cliente->numero_int;
        }
        $dirLinea2 = $cliente->colonia ?? '';
        $ciudadEstado = trim(implode(', ', array_filter([
            $cliente->estado ?? null,
            !empty($receptorBase['codigo_postal']) ? ('CP: ' . $receptorBase['codigo_postal']) : null,
        ])));

        $receptor = array_merge($receptorBase, [
            'direccion_linea1' => $dirLinea1 !== '' ? $dirLinea1 : ($receptorBase['direccion'] ?? ''),
            'direccion_linea2' => $dirLinea2,
            'ciudad_estado' => $ciudadEstado,
            'regimen_fiscal_texto' => str_replace(' - ', '/', (string) ($receptorBase['regimen_fiscal_texto'] ?? '')),
        ]);

        $conceptos = $this->resolverConceptosRepresentacion($factura, $cfdi);
        $subtotal = (float) ($cfdi['Subtotal'] ?? $factura->subtotal ?? 0);
        $total = (float) ($cfdi['Total'] ?? $factura->total ?? 0);
        $iva = round(max(0, $total - $subtotal), 2);
        if ($iva <= 0 && $subtotal > 0) {
            $iva = round($subtotal * 0.16, 2);
        }

        $fechaRaw = $cfdi['Date'] ?? $factura->date ?? now();
        $fecha = Carbon::parse($fechaRaw);
        $meses = [
            1 => 'ene.', 2 => 'feb.', 3 => 'mar.', 4 => 'abr.', 5 => 'may.', 6 => 'jun.',
            7 => 'jul.', 8 => 'ago.', 9 => 'sep.', 10 => 'oct.', 11 => 'nov.', 12 => 'dic.',
        ];
        $fechaEmision = $fecha->format('d') . '/' . ($meses[(int) $fecha->format('n')] ?? $fecha->format('m')) . '/' . $fecha->format('Y H:i:s');
        $fechaEmisionCorta = $fecha->format('d/m/Y');

        $taxStamp = $cfdi['Complement']['TaxStamp'] ?? [];
        $fechaCert = !empty($taxStamp['Date'])
            ? Carbon::parse($taxStamp['Date'])
            : $fecha;
        $fechaCertificacion = $fechaCert->format('d') . '/' . ($meses[(int) $fechaCert->format('n')] ?? $fechaCert->format('m')) . '/' . $fechaCert->format('Y H:i:s');

        $metodo = strtoupper((string) ($cfdi['PaymentMethod'] ?? $factura->metodo_pago ?? 'PUE'));
        $forma = (string) ($cfdi['PaymentForm'] ?? $factura->forma_pago ?? '99');
        $uso = (string) ($cfdi['Receiver']['CfdiUse'] ?? $receptorBase['uso_cfdi'] ?? 'G03');
        $usoCodigo = explode(' - ', $uso)[0] ?? $uso;

        $formas = [
            '01' => '01/Efectivo',
            '02' => '02/Cheque nominativo',
            '03' => '03/Transferencia electrónica de fondos',
            '04' => '04/Tarjeta de crédito',
            '28' => '28/Tarjeta de débito',
            '99' => '99/Por definir',
        ];
        $usos = [
            'G01' => 'G01/Adquisición de mercancías',
            'G02' => 'G02/Devoluciones, descuentos o bonificaciones',
            'G03' => 'G03/Gastos en general',
            'I01' => 'I01/Construcciones',
            'I08' => 'I08/Otra maquinaria y equipo',
            'S01' => 'S01/Sin efectos fiscales',
            'CP01' => 'CP01/Pagos',
            'CN01' => 'CN01/Nómina',
        ];

        $formatter = new NumeroALetras();
        $totalEnLetras = $formatter->toInvoice($total, 2, 'PESOS');

        $relacionados = [];
        $tipoRelacion = null;
        if (!empty($cfdi['Relations']['Cfdis']) && is_array($cfdi['Relations']['Cfdis'])) {
            $tipoRelacion = $cfdi['Relations']['Type'] ?? null;
            foreach ($cfdi['Relations']['Cfdis'] as $rel) {
                $uuidRel = is_array($rel) ? ($rel['Uuid'] ?? $rel['uuid'] ?? null) : $rel;
                if ($uuidRel) {
                    $relLocal = facturacionproductos::query()->where('Uuid', $uuidRel)->first();
                    $relacionados[] = [
                        'uuid' => $uuidRel,
                        'folio' => $relLocal->folio ?? null,
                    ];
                }
            }
        } elseif (!empty($factura->referencia_factura)) {
            $orig = facturacionproductos::find($factura->referencia_factura);
            if ($orig && !empty($orig->Uuid)) {
                $tipoRelacion = '04';
                $relacionados[] = ['uuid' => $orig->Uuid, 'folio' => $orig->folio];
            }
        }

        $tiposRelacion = [
            '01' => '01/Nota de crédito de los documentos relacionados',
            '02' => '02/Nota de débito de los documentos relacionados',
            '03' => '03/Devolución de mercancía',
            '04' => '04/Sustitución de los CFDI previos',
            '07' => '07/CFDI por aplicación de anticipo',
        ];

        $logoUrl = asset('Images/IOHISA.png');
        if (!empty($fiscalRow->logo)) {
            $logoPath = $fiscalRow->logo;
            if (str_starts_with($logoPath, 'http')) {
                $logoUrl = $logoPath;
            } elseif (file_exists(public_path($logoPath))) {
                $logoUrl = asset($logoPath);
            } elseif (file_exists(public_path('storage/' . ltrim($logoPath, '/')))) {
                $logoUrl = asset('storage/' . ltrim($logoPath, '/'));
            }
        }

        $ordenCompra = '';
        $condiciones = $metodo === 'PUE' ? 'CONTADO' : 'CRÉDITO';
        $tipoPago = $metodo === 'PUE' ? 'Contado' : 'Crédito';
        $esCredito = $metodo === 'PPD';
        $diasCredito = 0;
        $pedido = $this->resolverPedidoDeFactura($factura, ['cotizacion', 'condicionPago']);
        if ($pedido) {
            $ordenCompra = $pedido->folio ?? '';
            if (!empty($pedido->observaciones) && stripos($pedido->observaciones, 'OC') !== false) {
                $ordenCompra = $pedido->observaciones;
            }

            $condicion = $pedido->condicionPago;
            if ($condicion) {
                $condiciones = $condicion->nombre
                    ?: ($condicion->descripcion ?: $condiciones);
                $diasCredito = (int) ($condicion->dias_credito ?? 0);
                if ($diasCredito > 0) {
                    $esCredito = true;
                    $tipoPago = 'Crédito a ' . $diasCredito . ' día' . ($diasCredito === 1 ? '' : 's');
                } else {
                    $esCredito = false;
                    $tipoPago = 'Contado';
                    $condiciones = $condicion->nombre ?: 'CONTADO';
                }
            }
        }

        $lugarExpedicion = trim(($emisor['municipio'] ?? '') . (!empty($emisor['estado']) ? ', ' . $emisor['estado'] : ''));
        if ($lugarExpedicion === '') {
            $lugarExpedicion = 'CP ' . ($emisor['codigo_postal'] ?? '');
        }

        return [
            'factura' => $factura,
            'emisor' => $emisor,
            'receptor' => $receptor,
            'conceptos' => $conceptos,
            'folio' => $factura->folio ?? $factura->id,
            'fechaEmision' => $fechaEmision,
            'fechaEmisionCorta' => $fechaEmisionCorta,
            'fechaCertificacion' => $fechaCertificacion,
            'lugarExpedicion' => $lugarExpedicion,
            'lugarExpedicionCp' => $emisor['codigo_postal'] ?? '',
            'subtotal' => $subtotal,
            'iva' => $iva,
            'total' => $total,
            'totalEnLetras' => $totalEnLetras,
            'metodoPagoLabel' => $metodo === 'PPD'
                ? 'PPD/Pago en parcialidades o diferido'
                : 'PUE/Pago en una sola exhibición',
            'formaPagoLabel' => $formas[$forma] ?? ($forma . '/Forma de pago'),
            'tipoComprobanteLabel' => 'Ingreso',
            'usoCfdiLabel' => $usos[$usoCodigo] ?? ($usoCodigo . '/Uso CFDI'),
            'uuid' => $factura->Uuid ?? ($taxStamp['Uuid'] ?? '—'),
            'certificadoEmisor' => $cfdi['CertNumber'] ?? ($cfdi['Issuer']['CertificateNumber'] ?? ''),
            'certificadoSat' => $factura->SatCertNumber ?? ($taxStamp['SatCertNumber'] ?? ''),
            'cadenaOriginal' => $factura->OriginalString ?? ($cfdi['OriginalString'] ?? ''),
            'selloCfdi' => $factura->CfdiSign ?? ($taxStamp['CfdiSign'] ?? ''),
            'selloSat' => $factura->SatSign ?? ($taxStamp['SatSign'] ?? ''),
            'relacionados' => $relacionados,
            'tipoRelacionLabel' => $tiposRelacion[$tipoRelacion] ?? $tipoRelacion,
            'ordenCompra' => $ordenCompra,
            'condiciones' => $condiciones,
            'tipoPago' => $tipoPago,
            'esCredito' => $esCredito,
            'diasCredito' => $diasCredito,
            'logoUrl' => $logoUrl,
        ];
    }

    private function resolverPedidoDeFactura(facturacionproductos $factura, array $with = []): ?VentaPedido
    {
        if ($factura->tipo_serv !== 'pedido_venta') {
            return null;
        }

        if (!empty($factura->id_serv_enc)) {
            $pedido = VentaPedido::with($with)->find($factura->id_serv_enc);
            if ($pedido) {
                return $pedido;
            }
        }

        $folio = trim((string) ($factura->folio ?? ''));
        if ($folio === '') {
            return null;
        }

        return VentaPedido::with($with)
            ->where('folio', $folio)
            ->orderByDesc('id')
            ->first();
    }

    private function resolverConceptosRepresentacion(facturacionproductos $factura, ?array $cfdi): array
    {
        $pedido = $this->resolverPedidoDeFactura($factura, ['detalles.producto.unidadMedida']);
        $itemsPedido = $this->conceptosDesdePedidoRepresentacion($pedido);
        if (!empty($itemsPedido)) {
            return $itemsPedido;
        }

        $items = [];
        if (!empty($cfdi['Items']) && is_array($cfdi['Items'])) {
            foreach ($cfdi['Items'] as $item) {
                $desc = trim((string) ($item['Description'] ?? ''));
                $items[] = [
                    'clave' => (string) ($item['IdentificationNumber'] ?? ''),
                    'nombre' => $desc !== '' ? $desc : ($item['ProductCode'] ?? 'Concepto'),
                    'descripcion_extra' => '',
                    'clave_prod_sat' => $item['ProductCode'] ?? '',
                    'clave_prod_sat_desc' => '',
                    'precio' => (float) ($item['UnitPrice'] ?? 0),
                    'unidad' => $item['Unit'] ?? ($item['UnitCode'] ?? 'E48'),
                    'unidad_sat' => $item['UnitCode'] ?? '',
                    'unidad_desc' => $item['Unit'] ?? '',
                    'cantidad' => $item['Quantity'] ?? 1,
                    'importe' => (float) ($item['Subtotal'] ?? $item['Total'] ?? 0),
                    'descuento' => (float) ($item['Discount'] ?? 0),
                ];
            }

            $sonGenericos = collect($items)->every(function ($row) use ($factura) {
                $nombre = mb_strtolower(trim((string) ($row['nombre'] ?? '')));
                $folio = mb_strtolower(trim((string) ($factura->folio ?? '')));
                return $nombre === ''
                    || str_contains($nombre, 'concepto factura folio')
                    || ($folio !== '' && $nombre === $folio);
            });

            if (!empty($items) && !$sonGenericos) {
                return $items;
            }
        }

        $subtotal = (float) ($factura->subtotal ?? 0);
        if ($subtotal <= 0 && (float) ($factura->total ?? 0) > 0) {
            $subtotal = round((float) $factura->total / 1.16, 2);
        }

        return [[
            'clave' => '',
            'nombre' => 'Concepto factura folio ' . ($factura->folio ?? $factura->id),
            'descripcion_extra' => '',
            'clave_prod_sat' => '01010101',
            'clave_prod_sat_desc' => '',
            'precio' => $subtotal,
            'unidad' => 'E48',
            'unidad_sat' => 'E48',
            'unidad_desc' => 'Servicio',
            'cantidad' => 1,
            'importe' => $subtotal,
            'descuento' => 0,
        ]];
    }

    private function conceptosDesdePedidoRepresentacion(?VentaPedido $pedido): array
    {
        if (!$pedido || $pedido->detalles->isEmpty()) {
            return [];
        }

        $factorDescuento = (float) $pedido->subtotal > 0
            ? max(0, ((float) $pedido->subtotal - (float) $pedido->descuento) / (float) $pedido->subtotal)
            : 1;

        $items = [];
        foreach ($pedido->detalles as $detalle) {
            $cantidad = (float) $detalle->cantidad;
            $importe = round((float) $detalle->importe * $factorDescuento, 2);
            $precio = $cantidad > 0 ? round($importe / $cantidad, 2) : 0;
            $producto = $detalle->producto;
            $unidadMedida = optional($producto)->unidadMedida;

            $nombreProducto = trim((string) (optional($producto)->nombre ?: ''));
            $descDetalle = trim((string) ($detalle->descripcion ?: ''));
            $descProducto = trim((string) (optional($producto)->descripcion ?: ''));

            $nombre = $nombreProducto !== '' ? $nombreProducto : ($descDetalle !== '' ? $descDetalle : 'Producto');
            $extra = '';
            if ($descDetalle !== '' && mb_strtolower($descDetalle) !== mb_strtolower($nombre)) {
                $extra = $descDetalle;
            } elseif ($descProducto !== '' && mb_strtolower($descProducto) !== mb_strtolower($nombre)) {
                $extra = $descProducto;
            }

            $claveSat = trim((string) (optional($producto)->clave_sat ?: ''));
            $unidadSat = trim((string) (
                optional($producto)->Unidad_med_sat
                ?: optional($unidadMedida)->c_unidad_medida
                ?: ''
            ));
            $unidadComercial = trim((string) (
                optional($unidadMedida)->abreviacion
                ?: optional($unidadMedida)->nombre
                ?: $unidadSat
                ?: 'PZA'
            ));
            $unidadDesc = trim((string) (
                optional($unidadMedida)->nombre
                ?: optional($unidadMedida)->descripcion
                ?: ''
            ));

            $items[] = [
                'clave' => (string) (optional($producto)->sku ?? ''),
                'nombre' => $nombre,
                'descripcion_extra' => $extra,
                'clave_prod_sat' => $claveSat !== ''
                    ? $claveSat
                    : $this->resolverClaveSatDesdeProducto(null, null, '01010101'),
                'clave_prod_sat_desc' => '',
                'precio' => $precio,
                'unidad' => $unidadComercial,
                'unidad_sat' => $unidadSat !== ''
                    ? $this->resolverUnidadMedSatDesdeProducto($unidadSat)
                    : $this->resolverUnidadMedSatDesdeProducto(null),
                'unidad_desc' => $unidadDesc,
                'cantidad' => $cantidad,
                'importe' => $importe,
                'descuento' => 0,
            ];
        }

        return $items;
    }

    private function obtenerCfdiEmitidoFacturama(?string $facturamaId): ?array
    {
        if (empty($facturamaId)) {
            return null;
        }

        try {
            $client = new Client([
                'base_uri' => rtrim((string) env('FACTURAMA_BASE_URI', 'https://apisandbox.facturama.mx'), '/') . '/',
                'timeout' => 60.0,
                'auth' => [env('USER_FAC'), env('PWD')],
                'verify' => false,
            ]);
            $response = $client->get("3/cfdis/{$facturamaId}");
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::warning('No se pudo obtener CFDI de Facturama para representación', [
                'facturama_id' => $facturamaId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function cancelarFacturaEmitida(Request $request, int $id)
    {
        $request->validate([
            'motivo' => 'required|in:01,02,03,04',
            'uuid_reemplazo' => 'nullable|string|size:36',
        ]);

        if ($request->motivo === '01' && empty($request->uuid_reemplazo)) {
            return back()->with('error', 'El motivo 01 requiere el UUID del CFDI sustituto.');
        }

        if ($request->motivo === '04' && empty($request->uuid_reemplazo)) {
            return back()->with('error', 'El motivo 04 requiere el UUID del CFDI nominativo relacionado.');
        }

        $factura = facturacionproductos::find($id);

        if (!$factura) {
            return back()->with('error', 'Factura no encontrada.');
        }

        if ($factura->cancelada === 'C') {
            return back()->with('warning', 'La factura ya está cancelada.');
        }

        if (empty($factura->Uuid)) {
            return back()->with('error', 'La factura no tiene UUID; no se puede cancelar ante el SAT.');
        }

        $resultado = $this->cancelarCfdiEnFacturama($id, $request->motivo, $request->uuid_reemplazo);

        if (!$resultado['success']) {
            return back()->with('error', 'Error al cancelar la factura: ' . $resultado['message']);
        }

        DB::table('tblfacturacionproductos')
            ->where('id', $id)
            ->update([
                'cancelada' => 'C',
                'dia_cancelacion' => now(),
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Factura cancelada correctamente ante el SAT y en el sistema.');
    }

    public function facturacionSustitucion(int $id)
    {
        try {
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $facturaOriginal = facturacionproductos::find($id);

            if (!$facturaOriginal) {
                return redirect()->route('facturas.index')->with('error', 'Factura no encontrada.');
            }

            if ($facturaOriginal->cancelada === 'C') {
                return redirect()->route('facturas.index')->with('warning', 'La factura ya está cancelada.');
            }

            if (empty($facturaOriginal->Uuid)) {
                return redirect()->route('facturas.index')->with('error', 'La factura no está timbrada.');
            }

            $datos = $this->prepararDatosSustitucionDesdeFactura($facturaOriginal);
            $formasPago = [
                '01' => '01 - Efectivo',
                '02' => '02 - Cheque nominativo',
                '03' => '03 - Transferencia electrónica',
                '04' => '04 - Tarjeta de crédito',
                '28' => '28 - Tarjeta de débito',
                '99' => '99 - Por definir',
            ];
            $resultadoSustitucion = session('resultado_sustitucion');

            $anioEmision = $facturaOriginal->date
                ? (int) \Carbon\Carbon::parse($facturaOriginal->date)->format('Y')
                : (int) date('Y');
            $hoy = now();
            $limiteLegal = \Carbon\Carbon::create($anioEmision, 12, 31)->endOfDay();
            $limiteFacilidad = \Carbon\Carbon::create($anioEmision + 1, 3, 31)->endOfDay(); // personas morales (marzo)
            $dentroPlazoLegal = $hoy->lte($limiteLegal);
            $dentroFacilidad = $hoy->lte($limiteFacilidad);
            $horasDesdeEmision = $facturaOriginal->date
                ? \Carbon\Carbon::parse($facturaOriginal->date)->diffInHours($hoy)
                : null;
            $cancelacionDirecta24h = $horasDesdeEmision !== null && $horasDesdeEmision <= 24;
            $montoBajoUmbral = ((float) ($facturaOriginal->total ?? 0)) <= 1000;

            $infoSat = [
                'anio_emision' => $anioEmision,
                'limite_legal' => $limiteLegal->format('d/m/Y'),
                'limite_facilidad' => $limiteFacilidad->format('d/m/Y'),
                'dentro_plazo_legal' => $dentroPlazoLegal,
                'dentro_facilidad' => $dentroFacilidad,
                'cancelacion_directa_24h' => $cancelacionDirecta24h,
                'monto_bajo_umbral' => $montoBajoUmbral,
                'horas_desde_emision' => $horasDesdeEmision,
            ];

            return view('Tesoreria.FacturacionSustitucion', compact(
                'varpantallas',
                'varsubmenus',
                'facturaOriginal',
                'datos',
                'formasPago',
                'resultadoSustitucion',
                'infoSat'
            ));
        } catch (\Exception $e) {
            Log::error('Error al cargar sustitución CFDI', ['id' => $id, 'error' => $e->getMessage()]);
            return redirect()->route('facturas.index')->with('error', 'No se pudo cargar la sustitución: ' . $e->getMessage());
        }
    }

    public function procesarSustitucionCfdi(Request $request, int $id)
    {
        $facturaOriginal = facturacionproductos::find($id);

        if (!$facturaOriginal) {
            return redirect()->route('facturas.index')->with('error', 'Factura no encontrada.');
        }

        if ($facturaOriginal->cancelada === 'C') {
            return redirect()->route('facturas.sustitucion', $id)->with('warning', 'La factura original ya está cancelada.');
        }

        if (empty($facturaOriginal->Uuid)) {
            return redirect()->route('facturas.sustitucion', $id)->with('error', 'La factura original no tiene UUID.');
        }

        $conceptosJson = $request->input('conceptos');
        $conceptos = $conceptosJson ? json_decode($conceptosJson, true) : [];

        if (empty($conceptos) || !is_array($conceptos)) {
            return back()->with('error', 'Debe incluir al menos un concepto para el CFDI sustituto.');
        }

        $emisorRegimenFiscalCodigo = explode(' - ', $request->input('emisor_regimen_fiscal', '601'))[0] ?? '601';
        $usoCfdiCodigo = explode(' - ', $request->input('receptor_uso_cfdi', 'G03'))[0] ?? 'G03';
        $regimenFiscalCodigo = explode(' - ', $request->input('receptor_regimen_fiscal', '601'))[0] ?? '601';
        $formaPagoCodigo = explode(' - ', $request->input('forma_pago', '03'))[0] ?? '03';
        $metodoPago = $request->input('metodo_pago', $facturaOriginal->metodo_pago ?? 'PUE');

        $receptorDireccion = $request->input('receptor_direccion', '');
        $direccionPartes = explode(', ', $receptorDireccion);
        $calleNumero = $direccionPartes[0] ?? '';
        $colonia = $direccionPartes[1] ?? '';
        $calleNumeroPartes = explode(' ', $calleNumero);
        $numeroExterior = count($calleNumeroPartes) > 1 ? end($calleNumeroPartes) : 'S/N';
        $calle = count($calleNumeroPartes) > 1 ? implode(' ', array_slice($calleNumeroPartes, 0, -1)) : $calleNumero;

        $datosFactura = [
            'CfdiType' => 'I',
            'PaymentForm' => $formaPagoCodigo,
            'PaymentMethod' => $metodoPago,
            'ExpeditionPlace' => $request->input('emisor_lugar_expedicion'),
            'Date' => date('Y-m-d H:i:s'),
            'Folio' => $request->input('folio'),
            'referencia_factura' => $facturaOriginal->Uuid,
            'Relations' => [
                'Type' => '04',
                'Cfdis' => [
                    ['Uuid' => $facturaOriginal->Uuid],
                ],
            ],
            'Issuer' => [
                'FiscalRegime' => $emisorRegimenFiscalCodigo,
                'Rfc' => $request->input('emisor_rfc'),
                'Name' => $request->input('emisor_nombre'),
            ],
            'Receiver' => [
                'Rfc' => $request->input('receptor_rfc'),
                'CfdiUse' => $usoCfdiCodigo,
                'Name' => $request->input('receptor_nombre'),
                'FiscalRegime' => $regimenFiscalCodigo,
                'TaxZipCode' => $request->input('receptor_codigo_postal'),
                'Address' => [
                    'Street' => $calle,
                    'ExteriorNumber' => $numeroExterior,
                    'InteriorNumber' => '',
                    'Neighborhood' => $colonia,
                    'ZipCode' => $request->input('receptor_codigo_postal'),
                    'Municipality' => 'TORREÓN',
                    'State' => 'COAHUILA',
                    'Country' => 'México',
                ],
            ],
            'Items' => [],
        ];

        foreach ($conceptos as $concepto) {
            $cantidadStr = str_replace(['$', ',', ' '], '', $concepto['cantidad'] ?? '0');
            $precioStr = str_replace(['$', ',', ' '], '', $concepto['precio'] ?? '0');
            $cantidad = is_numeric($cantidadStr) ? (float) $cantidadStr : 0;
            $precio = is_numeric($precioStr) ? (float) $precioStr : 0;

            if ($cantidad <= 0 || $precio <= 0) {
                continue;
            }

            $subtotalItem = round($cantidad * $precio, 2);
            $ivaItem = round($subtotalItem * 0.16, 2);
            $totalItem = round($subtotalItem + $ivaItem, 2);

            $productCode = trim($concepto['producto'] ?? '');
            if (empty($productCode) || $productCode === 'Buscar producto SAT') {
                $productCode = '01010101';
            }
            if (strpos($productCode, ' - ') !== false) {
                $productCode = explode(' - ', $productCode)[0];
            }

            $unitCode = trim($concepto['unidad'] ?? 'H87');
            if (strpos($unitCode, ' - ') !== false) {
                $unitCode = explode(' - ', $unitCode)[0];
            }

            $datosFactura['Items'][] = [
                'ProductCode' => $productCode,
                'Description' => trim($concepto['concepto'] ?? '') ?: 'Producto/Servicio',
                'UnitCode' => $unitCode,
                'Quantity' => $cantidad,
                'UnitPrice' => $precio,
                'Subtotal' => $subtotalItem,
                'TaxObject' => '02',
                'Taxes' => [[
                    'Total' => $ivaItem,
                    'Name' => 'IVA',
                    'Base' => $subtotalItem,
                    'Rate' => 0.16,
                    'IsRetention' => false,
                ]],
                'Total' => $totalItem,
            ];
        }

        if (empty($datosFactura['Items'])) {
            return back()->with('error', 'No hay conceptos válidos para timbrar el CFDI sustituto.');
        }

        try {
            $resultado = $this->crearFacturaCFDI40(
                $datosFactura,
                $facturaOriginal->id_serv_enc,
                $facturaOriginal->tipo_serv
            );

            if (is_array($resultado) && isset($resultado['error'])) {
                return back()->with('error', 'Error al timbrar CFDI sustituto: ' . $resultado['error']);
            }

            $uuidSustituto = $resultado['Complement']['TaxStamp']['Uuid'] ?? null;
            $folioSustituto = $resultado['Folio'] ?? $request->input('folio');

            if (empty($uuidSustituto)) {
                return back()->with('error', 'El CFDI sustituto se timbró pero no se obtuvo UUID.');
            }

            $cancelacion = $this->cancelarCfdiEnFacturama($id, '01', $uuidSustituto);

            if ($cancelacion['success']) {
                DB::table('tblfacturacionproductos')
                    ->where('id', $id)
                    ->update([
                        'cancelada' => 'C',
                        'dia_cancelacion' => now(),
                        'updated_at' => now(),
                    ]);
            }

            return redirect()
                ->route('facturas.sustitucion', $id)
                ->with('resultado_sustitucion', [
                    'timbrado_ok' => true,
                    'cancelacion_ok' => $cancelacion['success'],
                    'uuid_sustituto' => $uuidSustituto,
                    'folio_sustituto' => $folioSustituto,
                    'uuid_original' => $facturaOriginal->Uuid,
                    'folio_original' => $facturaOriginal->folio,
                    'mensaje_cancelacion' => $cancelacion['message'] ?? '',
                ]);
        } catch (\Exception $e) {
            Log::error('Error en procesarSustitucionCfdi', ['id' => $id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Error al procesar sustitución: ' . $e->getMessage());
        }
    }

    private function prepararDatosSustitucionDesdeFactura(facturacionproductos $factura): array
    {
        $emisor = $this->obtenerDatosEmisorFacturacion();
        $conceptos = collect();
        $subtotal = (float) ($factura->subtotal ?? 0);
        $iva = round($subtotal * 0.16, 2);
        $total = (float) ($factura->total ?? 0);

        $receptor = [
            'nombre' => $factura->reciver_nombre ?? '',
            'rfc' => $factura->reciver_rfc ?? '',
            'direccion' => '',
            'codigo_postal' => '',
            'uso_cfdi' => 'G03 - Gastos en general',
            'regimen_fiscal' => '601 - General de Ley Personas Morales',
        ];

        if ($factura->tipo_serv === 'pedido_venta' && $factura->id_serv_enc) {
            $pedido = VentaPedido::with(['detalles.producto', 'cliente'])->find($factura->id_serv_enc);

            if ($pedido && $pedido->cliente) {
                $cliente = $pedido->cliente;
                $factorDescuento = (float) $pedido->subtotal > 0
                    ? max(0, ((float) $pedido->subtotal - (float) $pedido->descuento) / (float) $pedido->subtotal)
                    : 1;

                $conceptos = $pedido->detalles->map(function ($detalle) use ($factorDescuento) {
                    $cantidad = (float) $detalle->cantidad;
                    $subtotalItem = round((float) $detalle->importe * $factorDescuento, 2);
                    $unitPrice = $cantidad > 0 ? round($subtotalItem / $cantidad, 2) : 0;
                    $producto = $detalle->producto;

                    return [
                        'producto' => $this->resolverClaveSatDesdeProducto($producto->clave_sat ?? null, $producto->sku ?? null),
                        'cantidad' => $cantidad,
                        'unidad' => $this->resolverUnidadMedSatDesdeProducto($producto->Unidad_med_sat ?? null),
                        'concepto' => trim((string) ($detalle->descripcion ?: optional($detalle->producto)->nombre ?: 'Producto')),
                        'precio' => number_format($unitPrice, 2, '.', ''),
                        'importe' => number_format($subtotalItem, 2, '.', ''),
                    ];
                })->filter(fn ($c) => (float) ($c['importe'] ?? 0) > 0)->values();

                $subtotal = round((float) $pedido->subtotal - (float) $pedido->descuento, 2);
                $iva = round((float) $pedido->iva, 2);
                $total = round((float) $pedido->total, 2);

                $receptor = [
                    'nombre' => $cliente->nombre,
                    'rfc' => $cliente->rfc,
                    'direccion' => trim(sprintf('%s %s%s, %s',
                        $cliente->calle ?? '',
                        $cliente->numero_ext ?? '',
                        !empty($cliente->numero_int) ? ' Int. ' . $cliente->numero_int : '',
                        $cliente->colonia ?? ''
                    )),
                    'codigo_postal' => $cliente->cp ?? '',
                    'uso_cfdi' => 'G03 - Gastos en general',
                    'regimen_fiscal' => '601 - General de Ley Personas Morales',
                ];
            }
        }

        if ($conceptos->isEmpty()) {
            $conceptos = collect([[
                'producto' => '01010101',
                'cantidad' => 1,
                'unidad' => 'E48',
                'concepto' => 'Sustitución CFDI folio ' . ($factura->folio ?? $factura->id),
                'precio' => number_format($subtotal > 0 ? $subtotal : max(0, $total / 1.16), 2, '.', ''),
                'importe' => number_format($subtotal > 0 ? $subtotal : max(0, $total / 1.16), 2, '.', ''),
            ]]);
        }

        $formaPago = $factura->forma_pago ?? '03';
        $formasLabels = [
            '01' => '01 - Efectivo',
            '02' => '02 - Cheque nominativo',
            '03' => '03 - Transferencia electrónica',
            '04' => '04 - Tarjeta de crédito',
            '28' => '28 - Tarjeta de débito',
            '99' => '99 - Por definir',
        ];

        return [
            'emisor' => $emisor,
            'receptor' => $receptor,
            'conceptos' => $conceptos,
            'totales' => [
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'iva' => number_format($iva, 2, '.', ''),
                'total' => number_format($total, 2, '.', ''),
            ],
            'pago' => [
                'forma_pago' => $formasLabels[$formaPago] ?? ($formaPago . ' - Forma de pago'),
                'metodo_pago' => $factura->metodo_pago ?? 'PUE',
            ],
            'folio_sustituto' => ($factura->folio ?? 'F') . '-S',
        ];
    }

    public function iniciarSustitucionCfdi(int $id)
    {
        return $this->facturacionSustitucion($id);
    }

    public function cancelacionHub(int $id)
    {
        $factura = $this->obtenerFacturaCancelable($id);
        if ($factura instanceof \Illuminate\Http\RedirectResponse) {
            return $factura;
        }

        $infoSat = $this->resolverInfoSatCancelacion($factura);

        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        return view('Tesoreria.FacturacionCancelacionHub', compact(
            'varpantallas',
            'varsubmenus',
            'factura',
            'infoSat'
        ));
    }

    public function cancelacionMotivo02(int $id)
    {
        return $this->mostrarVistaCancelacionMotivo($id, '02');
    }

    public function procesarCancelacionMotivo02(Request $request, int $id)
    {
        $request->validate([
            'confirmacion_motivo' => 'accepted',
            'confirmacion_relacion' => 'accepted',
            'observaciones' => 'nullable|string|max:500',
        ]);

        return $this->ejecutarCancelacionGuiada($id, '02', null, $request->input('observaciones'));
    }

    public function cancelacionMotivo03(int $id)
    {
        return $this->mostrarVistaCancelacionMotivo($id, '03');
    }

    public function procesarCancelacionMotivo03(Request $request, int $id)
    {
        $request->validate([
            'confirmacion_motivo' => 'accepted',
            'confirmacion_riesgo' => 'accepted',
            'confirmacion_sin_relacion' => 'accepted',
            'observaciones' => 'nullable|string|max:500',
        ]);

        return $this->ejecutarCancelacionGuiada($id, '03', null, $request->input('observaciones'));
    }

    public function cancelacionMotivo04(int $id)
    {
        return $this->mostrarVistaCancelacionMotivo($id, '04');
    }

    public function procesarCancelacionMotivo04(Request $request, int $id)
    {
        $request->validate([
            'uuid_nominativo' => 'required|string|size:36',
            'confirmacion_motivo' => 'accepted',
            'confirmacion_relacion' => 'accepted',
            'observaciones' => 'nullable|string|max:500',
        ]);

        return $this->ejecutarCancelacionGuiada(
            $id,
            '04',
            $request->input('uuid_nominativo'),
            $request->input('observaciones')
        );
    }

    private function mostrarVistaCancelacionMotivo(int $id, string $motivo)
    {
        $factura = $this->obtenerFacturaCancelable($id);
        if ($factura instanceof \Illuminate\Http\RedirectResponse) {
            return $factura;
        }

        $infoSat = $this->resolverInfoSatCancelacion($factura);
        $resultadoCancelacion = session('resultado_cancelacion');
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        $vistas = [
            '02' => 'Tesoreria.FacturacionCancelacion02',
            '03' => 'Tesoreria.FacturacionCancelacion03',
            '04' => 'Tesoreria.FacturacionCancelacion04',
        ];

        return view($vistas[$motivo], compact(
            'varpantallas',
            'varsubmenus',
            'factura',
            'infoSat',
            'resultadoCancelacion'
        ));
    }

    private function ejecutarCancelacionGuiada(int $id, string $motivo, ?string $uuidRelacion, ?string $observaciones)
    {
        $factura = $this->obtenerFacturaCancelable($id);
        if ($factura instanceof \Illuminate\Http\RedirectResponse) {
            return $factura;
        }

        $resultado = $this->cancelarCfdiEnFacturama($id, $motivo, $uuidRelacion);

        if ($resultado['success']) {
            $obs = is_string($observaciones) ? trim($observaciones) : '';
            DB::table('tblfacturacionproductos')
                ->where('id', $id)
                ->update([
                    'cancelada' => 'C',
                    'dia_cancelacion' => now(),
                    'observaciones_cancelacion' => $obs !== '' ? $obs : null,
                    'updated_at' => now(),
                ]);
        }

        $ruta = match ($motivo) {
            '02' => 'facturas.cancelacion.02',
            '03' => 'facturas.cancelacion.03',
            '04' => 'facturas.cancelacion.04',
            default => 'facturas.cancelacion',
        };

        return redirect()
            ->route($ruta, $id)
            ->with('resultado_cancelacion', [
                'ok' => $resultado['success'],
                'motivo' => $motivo,
                'uuid_relacion' => $uuidRelacion,
                'folio' => $factura->folio,
                'uuid' => $factura->Uuid,
                'mensaje' => $resultado['message'] ?? '',
                'observaciones' => $observaciones,
            ])
            ->with($resultado['success'] ? 'success' : 'error', $resultado['success']
                ? "Cancelación con motivo {$motivo} procesada correctamente."
                : ('Error al cancelar: ' . ($resultado['message'] ?? 'desconocido')));
    }

    private function obtenerFacturaCancelable(int $id)
    {
        $factura = facturacionproductos::find($id);

        if (!$factura) {
            return redirect()->route('facturas.index')->with('error', 'Factura no encontrada.');
        }

        if ($factura->cancelada === 'C') {
            return redirect()->route('facturas.index')->with('warning', 'La factura ya está cancelada.');
        }

        if (empty($factura->Uuid)) {
            return redirect()->route('facturas.index')->with('error', 'La factura no está timbrada (sin UUID).');
        }

        return $factura;
    }

    private function resolverInfoSatCancelacion(facturacionproductos $factura): array
    {
        $anioEmision = $factura->date
            ? (int) Carbon::parse($factura->date)->format('Y')
            : (int) date('Y');
        $hoy = now();
        $limiteLegal = Carbon::create($anioEmision, 12, 31)->endOfDay();
        $limiteFacilidad = Carbon::create($anioEmision + 1, 3, 31)->endOfDay();
        $horasDesdeEmision = $factura->date
            ? Carbon::parse($factura->date)->diffInHours($hoy)
            : null;
        $rfc = strtoupper(trim((string) ($factura->reciver_rfc ?? '')));
        $esPublicoGeneral = in_array($rfc, ['XAXX010101000', 'XEXX010101000'], true);

        return [
            'anio_emision' => $anioEmision,
            'limite_legal' => $limiteLegal->format('d/m/Y'),
            'limite_facilidad' => $limiteFacilidad->format('d/m/Y'),
            'dentro_plazo_legal' => $hoy->lte($limiteLegal),
            'dentro_facilidad' => $hoy->lte($limiteFacilidad),
            'cancelacion_directa_24h' => $horasDesdeEmision !== null && $horasDesdeEmision <= 24,
            'monto_bajo_umbral' => ((float) ($factura->total ?? 0)) <= 1000,
            'horas_desde_emision' => $horasDesdeEmision,
            'es_publico_general' => $esPublicoGeneral,
            'rfc' => $rfc,
        ];
    }

    private function cancelarCfdiEnFacturama(int $facturaId, string $motive, ?string $uuidReplacement = null): array
    {
        try {
            $factura = facturacionproductos::find($facturaId);

            if (!$factura || empty($factura->Uuid)) {
                return ['success' => false, 'message' => 'Factura no encontrada o sin UUID'];
            }

            $usuario = env('USER_FAC');
            $password = env('PWD');

            if (!$usuario || !$password) {
                return ['success' => false, 'message' => 'Credenciales de Facturama no configuradas'];
            }

            $client = new Client([
                'base_uri' => env('FACTURAMA_BASE_URI', 'https://apisandbox.facturama.mx') . '/',
                'auth' => [$usuario, $password],
                'timeout' => 30,
                'verify' => false,
            ]);

            $url = 'cfdi/' . $factura->Uuid . '?type=issued&motive=' . $motive;
            if (!empty($uuidReplacement)) {
                $url .= '&uuidReplacement=' . urlencode($uuidReplacement);
            }

            $response = $client->request('DELETE', $url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                return [
                    'success' => true,
                    'message' => 'Factura cancelada en Facturama',
                    'data' => json_decode($response->getBody()->getContents(), true),
                ];
            }

            return ['success' => false, 'message' => 'Error en Facturama: código ' . $response->getStatusCode()];
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $errorBody = $response ? $response->getBody()->getContents() : 'Sin respuesta del servidor';

            Log::error('Error al cancelar factura en Facturama', [
                'factura_id' => $facturaId,
                'error' => $e->getMessage(),
                'response_body' => $errorBody,
            ]);

            return ['success' => false, 'message' => $e->getMessage() . ' - ' . $errorBody];
        } catch (\Exception $e) {
            Log::error('Error inesperado al cancelar factura', [
                'factura_id' => $facturaId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

} // ultima llave