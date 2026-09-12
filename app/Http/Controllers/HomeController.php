<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vistas;
use App\Models\Formatos;
use App\Models\usuario_pantallas;
use App\Models\OrdenCompra;
use App\Traits\MenuTrait;
use App\Traits\SistemasTraits;
use App\Traits\DatosimpleTraits;
use DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use App\Models\CcAsignacion;
use App\Models\CcAsignacionCuenta;
use App\Models\CcCiclo;
use App\Models\PvAsignacion;
use App\Models\PvAsignacionProducto;
use App\Models\PvCiclo;
use App\Models\GestionAlumnosVisitaProspeccion;
use App\Models\Mantenimiento;
use App\Models\ProgramacionMantenimiento;

class HomeController extends Controller
{
    use MenuTrait;
    use DatosimpleTraits;
    use SistemasTraits;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
    
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $status = auth()->user()->estado_user;
            $formato = Carbon::now()->format('a');
            $date = Carbon::now()->format('h');
            $permiso1 =   $this->forpermisos("acargo_ordenesCompra");
            $permiso2 =   $this->forpermisos("revision_ordenesCompra");

            // Obtener órdenes de compra pendientes según permisos
            $ordenesRevision = collect();
            $ordenesAceptadas = collect();
            $ordenesEnviadas = collect();

            if($permiso2 == 'revision_ordenesCompra') {
                $ordenesRevision = OrdenCompra::with('proveedor')
                    ->where('estado', 'revision')
                    ->orderBy('created_at', 'desc')
                    ->get();
            }

            if($permiso1 == 'acargo_ordenesCompra') {
                $ordenesAceptadas = OrdenCompra::with('proveedor')
                    ->where('estado', 'aceptado')
                    ->orderBy('created_at', 'desc')
                    ->get();
                
                $ordenesEnviadas = OrdenCompra::with('proveedor')
                    ->where('estado', 'enviado_proveedor')
                    ->orderBy('created_at', 'desc')
                    ->get();
            }

            $visitasEmpresasCalendario = [];
            if (Schema::hasTable('tblvisitas_prospeccion') && Schema::hasTable('tblga_empresas')) {
                $inicioMes = Carbon::now()->startOfMonth()->toDateString();
                $finMes = Carbon::now()->endOfMonth()->toDateString();

                $visitasEmpresasCalendario = GestionAlumnosVisitaProspeccion::query()
                    ->with(['empresa:id,nombre'])
                    ->whereBetween('fecha_visita', [$inicioMes, $finMes])
                    ->orderBy('fecha_visita')
                    ->get(['id', 'empresa_id', 'fecha_visita', 'acepta_adoptar_sed'])
                    ->map(function ($v) {
                        return [
                            'fecha_visita' => optional($v->fecha_visita)->toDateString(),
                            'empresa' => optional($v->empresa)->nombre ?: 'Empresa sin nombre',
                            'estatus' => $v->acepta_adoptar_sed ?: 'Pendiente',
                        ];
                    })
                    ->values()
                    ->all();
            }

            $datosMantenimientoMaquinas = $this->obtenerDatosCalendarioMantenimiento();
            $resumenCentros = $this->obtenerResumenCentrosHome();
            $resumenPv = $this->obtenerResumenPvHome();

            if(auth()->user()->tipo != "ext"){
            
                return view('home', compact(
                    'varpantallas',
                    'varsubmenus',
                    'status',
                    'date',
                    'formato',
                    'permiso1',
                    'permiso2',
                    'ordenesRevision',
                    'ordenesAceptadas',
                    'ordenesEnviadas',
                    'visitasEmpresasCalendario',
                    'datosMantenimientoMaquinas',
                    'resumenCentros',
                    'resumenPv'
                ));
            }else{
                return redirect()->route('ext_licitaciones');
            }
           
    }
    public function chart1($labels, $saldos, $total_prestamos)
    {
        return [
            'type' => 'pie',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Total',
                        'backgroundColor' => [
                            '#2E90A4', // Suave rojo
                            '#FFB74D', // Suave naranja
                            '#FFD54F', // Suave amarillo
                            '#81C784', // Suave verde
                            '#64B5F6', // Suave azul
                            '#BA68C8', // Suave púrpura
                            '#4DD0E1', // Suave cian
                            '#F06292',
                            '#FFD700', 
                            '#A1887F'
                        ],
      
                        'borderColor' => [
                            '#000000', // Suave rojo
                        ],
                        
                        'borderWidth' => 0.3,
                        'data' => $saldos
                    ],
                    [
                        'label' => 'Número de canjes',
                        'backgroundColor' => [
                            '#2E90A4', // Suave rojo
                            '#FFB74D', // Suave naranja
                            '#FFD54F', // Suave amarillo
                            '#81C784', // Suave verde
                            '#64B5F6', // Suave azul
                            '#BA68C8', // Suave púrpura
                            '#4DD0E1', // Suave cian
                            '#F06292', // Suave magenta
                            '#FFD700', // Dorado
                            '#A1887F'
                        ],
                        'borderColor' => [
                            '#000000',
                        ],
                        'borderWidth' => 0.3,
                        'data' => $total_prestamos
                    ],
           
                ]
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'bottom'
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Préstamos Activos'
                    ],
                ]
            ]
        ];
    }
    public function chart3($labels, $saldos)
    {
        return [
            'type' => 'pie',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Total',
                        'backgroundColor' => [
                            '#FFB74D',
                            '#2E90A4', // Suave naranja
                            '#FFD54F', // Suave amarillo
                            '#81C784', // Suave verde
                            '#64B5F6', // Suave azul
                            '#BA68C8', // Suave púrpura
                            '#4DD0E1', // Suave cian
                            '#F06292', // Suave magenta
                            '#FFD700', // Dorado
                            '#A1887F'
                        ],

                        'borderColor' => [
                            '#000000', // Suave rojo
                        ],
                        
                        'borderWidth' => 0.3,
                        'data' => $saldos
                    ],
                ]
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'bottom'
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Cobranza requerida y recibida'
                    ],
                    'tooltips' => [
                        
                    ]
                ]
            ]
        ];
    }
    public function chart4($labels, $saldos)
    {
        return [
            'type' => 'pie',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Total',
                        'backgroundColor' => [
                            '#2E90A4',
                            '#4CAF50',// Suave rojo
                            '#D32F2F', // Suave naranja
                            '#FFD54F', // Suave amarillo
                            '#4DD0E1', // Suave verde
                            '#64B5F6', // Suave azul
                            '#BA68C8', // Suave púrpura
                            '#4DD0E1', // Suave cian
                            '#F06292', // Suave magenta
                            '#FFD700', // Dorado
                            '#A1887F'
                        ],

                        'borderColor' => [
                            '#000000', // Suave rojo
                        ],
                        
                        'borderWidth' => 0.3,
                        'data' => $saldos
                    ],
                ]
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'bottom'
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Saldos activos'
                    ],
                    'tooltips' => [
                        
                    ]
                ]
            ]
        ];
    }
    public function chart5($labels, $saldos, $total_prestamos, $total_capital)
    {
        return [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Capital autorizado',
                        'backgroundColor' => '#2E90A4',
                        'borderColor' => '#000000',
                        'borderWidth' => 0.3,
                        'data' => $saldos,
                        'stack' => 'stack1' // Añadir stack
                    ],
                    [
                        'label' => 'Capital disponible',
                        'backgroundColor' => '#FFD54F',
                        'borderColor' => '#000000',
                        'borderWidth' => 0.3,
                        'data' => $total_capital,
                        'stack' => 'stack2' // Añadir stack
                    ],
                    [
                        'label' => 'Total de distribuidores',
                        'backgroundColor' => '#FFB74D',
                        'borderColor' => '#000000',
                        'borderWidth' => 0.3,
                        'data' => $total_prestamos,
                        'stack' => 'stack3' // Añadir stack
                    ],
                ]
            ],
            'options' => [
                'maintainAspectRatio' => false,
                'scales' => [
                    'x' => [
                        'ticks' => [
                            'font' => [
                                'size' => 10 // Cambia el tamaño de la fuente aquí (en px)
                            ]
                        ]
                    ],
                    'y' => [
                        'beginAtZero' => true,
                    ]
                ],
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'bottom',
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Coordinadores y sus distribuidoras por límite de crédito'
                    ],
                ]
            ],
        ];
    }
    public function chart6($labels, $total_prestamos)
    {
        return [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Total distribuidores atrasados',
                        'backgroundColor' => '#2E90A4',
                        'borderColor' => '#000000',
                        'borderWidth' => 0.3,
                        'data' => $total_prestamos
                    ],
                ]
            ],
            'options' => [
                'maintainAspectRatio' => false,
                'scales' => [
                    'x' => [
                        'stacked' => true,
                        'ticks' => [
                            'font' => [
                                'size' => 10
                            ]
                        ]
                    ],
                    'y' => [
                        'stacked' => true,
                        'beginAtZero' => true,
                        
                    ]
                ],
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'bottom',
                        
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Coordinadores con distribuidoras atrasadas'
                    ],
                ]
            ],

        ];
    }
    public function chart7($labels, $total_prestamos, $monto)
    {
        return [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Total Prestamos',
                        'backgroundColor' => '#2E90A4',
                        'borderColor' => '#000000',
                        'borderWidth' => 0.3,
                        'data' => $monto
                    ], 
                    [
                        'label' => 'Numero de Prestamos',
                        'backgroundColor' => '#FFB74D',
                        'borderColor' => '#000000',
                        'borderWidth' => 0.3,
                        'data' => $total_prestamos
                    ],
                    
                ]
            ],
            'options' => [
                'maintainAspectRatio' => false,
                'scales' => [
                    'x' => [
                        'stacked' => true,
                        'ticks' => [
                            'font' => [
                                'size' => 10
                            ]
                        
                        ]
                    ],
                    'y' => [
                        'stacked' => true,
                        'beginAtZero' => true,
                        
                    ]
                ],
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'bottom',
                        
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Distribuidoras con más prestamos'
                    ],
                ]
            ],

        ];
    }

    private function createChart($type, $label, $labels, $data)
    {
        return [
            'type' => $type,
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => $label,
                    'backgroundColor' => [
                        '#FF6F61', // Suave rojo
                        '#FFB74D', // Suave naranja
                        '#FFD54F', // Suave amarillo
                        '#81C784', // Suave verde
                        '#64B5F6', // Suave azul
                        '#BA68C8', // Suave púrpura
                        '#4DD0E1', // Suave cian
                        '#F06292', // Suave magenta
                        '#FFD700', // Dorado
                        '#A1887F' 
                    ],
                    'borderWith' => 0,
                    'data' => $data
                ]]
            ],
            'hoverOffset' => 4,
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'scales' => [
                    'x' => ['beginAtZero' => true, 'grid' => ['display' => false], 'ticks' => ['display' => false, 'beginAtZero' => true]],
                    'y' => ['beginAtZero' => true, 'grid' => ['display' => false], 'ticks' => ['display' => false , 'beginAtZero' => true]]
                ],
                'plugins' => [
                    'legend' => ['display' => false],
                    'title' => ['display' => true, 'text' => $label]
                ]   
            ]
        ];
    }

    public function Formatos()
    {
        try {
            $permisos1 = $this->forpermisos('subir_formatos');
            $permisos2 = $this->forpermisos('eliminar_formatos');
            $varpantallas = $this->Traermenuenc();
            $varsubmenus = $this->Traermenudet();
            $vardepartamentos = $this->obtenerdepartamentos();
            $departamentos = DB::select("
            SELECT DISTINCT tblformatos.id_departamento id, tbldepartamentos.nombre AS nombre
            FROM tblformatos
            INNER JOIN tbldepartamentos ON tblformatos.id_departamento = tbldepartamentos.id
            ORDER BY tblformatos.id_departamento ASC");
            $formatos = DB::select("select tblformatos.id, tblformatos.nombre,  tblformatos.id_departamento, tbldepartamentos.nombre as departamento, tblformatos.ruta_archivo, tblformatos.created_by,tblformatos.created_at from tblformatos inner join tbldepartamentos on tblformatos.id_departamento = tbldepartamentos.id order by tblformatos.id_departamento asc");

            return view('Formatos.index', compact('varpantallas', 'varsubmenus', 'formatos', 'vardepartamentos', 'permisos1', 'permisos2', 'departamentos'));
        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function agregarFormato(Request $request)
    {

        try {
            $departamento = "";

            $sql = DB::select("select nombre from tbldepartamentos where id = ? limit 1", [$request->get('departamento')]);
            foreach ($sql as $row) {
                $departamento = $row->nombre;
            }
            $Rutacarpeta = "Formatos/" . $departamento;

            if ($request->hasFile("formato")) {
                if (!file_exists(public_path($Rutacarpeta))) {
                    File::makeDirectory($Rutacarpeta, 0777, true, true);
                }


                $file_doc = $request->file("formato");
                $NombreArchivo = $request->get('nombre') . "." . $file_doc->guessExtension();
                $ruta_doc = public_path($Rutacarpeta . "/" . $NombreArchivo);
                copy($file_doc, $ruta_doc);

                $historialcuent = new Formatos();
                $historialcuent->id_departamento = $request->get('departamento');
                $historialcuent->nombre = $request->get('nombre');
                $historialcuent->ruta_archivo = $NombreArchivo;
                $historialcuent->created_by = auth()->user()->name;
                $historialcuent->save();
                return back()->with("success", "¡se aplico!");

            } else {
                Log::error("Error en HomeController@agregarFormato: No se subio el archivo");
                return back()->with("warning", "¡No se aplica!");
            }
        } catch (\Exception $ex) {
            Log::error("Error en HomeController@agregarFormato: " . $ex->getMessage());
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function eliminarFormato(int $id)
    {
        $departamento = "";
        $ruta_archivo = "";

        $sql = DB::select("select tblformatos.id, tblformatos.nombre,  tblformatos.id_departamento, tbldepartamentos.nombre as departamento, tblformatos.ruta_archivo, tblformatos.created_by,tblformatos.created_at from tblformatos 
        inner join tbldepartamentos on tblformatos.id_departamento = tbldepartamentos.id where tblformatos.id = ?", [$id]);

        foreach ($sql as $row) {
            $departamento = $row->departamento;
            $ruta_archivo = $row->ruta_archivo;
        }
        $Rutacarpeta = "Formatos/" . $departamento . "/" . $ruta_archivo;

        if (file_exists(public_path($Rutacarpeta))) {
            File::delete(public_path($Rutacarpeta, 0777, true, true));
            $Borrartbl3 = DB::select('delete from tblformatos where id = ? ', [$id]);

            return back()->with("success", "¡se aplico!");
        } else {
            return back()->with("warning", "¡No se aplica!");
        }
    }

    public function DescargarSolicitud()
    {
        try {
            $ruta = public_path("Formatos/Vales/SOLICITUD_CREDITO.pdf");
            if (file_exists($ruta)) {
                return response()->download($ruta);
            } else {
                return back()->with("warningNoexiste", "no guardado correctamente");
            }

        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }


    public function DescargarVerificacionDomAval()
    {
        try {
            $ruta = public_path("Formatos/Vales/VERIFICACION DOMICILIARIA_AVAL.pdf");
            if (file_exists($ruta)) {
                return response()->download($ruta);
            } else {
                return back()->with("warningNoexiste", "no guardado correctamente");
            }

        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    public function DescargarVerificacionDomDist()
    {
        try {
            $ruta = public_path("Formatos/Vales/VERIFICACION DOMICILIARIA_DIS.pdf");
            if (file_exists($ruta)) {
                return response()->download($ruta);
            } else {
                return back()->with("warningNoexiste", "no guardado correctamente");
            }

        } catch (\Illuminate\Database\QueryException $ex) {
            return back()->with("warningBD", "no guardado correctamente");
        }
    }

    /**
     * Resumen de alto nivel del ciclo de centros (conteos, sin montos ni nombres).
     *
     * @return array<string, mixed>
     */
    private function obtenerResumenCentrosHome(): array
    {
        $labels = [
            'abierto' => 'Abierto',
            'en_proceso' => 'Abierto',
            'en_revision' => 'En revisión',
            'terminado' => 'Cerrado',
            'cerrado' => 'Cerrado',
        ];

        $empty = [
            'hayCiclo' => false,
            'nombre' => 'Centros de costos',
            'anioRef' => 2026,
            'anioPpto' => 2027,
            'estado' => '',
            'estadoLabel' => 'Sin ciclo',
            'enVentana' => false,
            'diasRestantes' => null,
            'capturaHasta' => null,
            'centros' => 0,
            'cuentas' => 0,
            'usuarios' => 0,
            'empresas' => 0,
            'misCentros' => 0,
        ];

        try {
            if (! Schema::hasTable('tbl_cc_ciclos')) {
                return $empty;
            }

            $hoy = Carbon::today();
            $ciclos = CcCiclo::query()->orderByDesc('anio_presupuesto')->orderByDesc('id')->get();
            if ($ciclos->isEmpty()) {
                return $empty;
            }

            $ciclo = $ciclos->first(function (CcCiclo $c) use ($hoy) {
                $inicio = $c->fecha_inicio;
                $cierre = $c->captura_hasta ?: $c->fecha_fin;
                if ($inicio && $cierre) {
                    return $hoy->between($inicio, $cierre);
                }

                return in_array((string) $c->estado, ['abierto', 'en_proceso'], true);
            }) ?: $ciclos->first();

            $cierre = $ciclo->captura_hasta ?: $ciclo->fecha_fin;
            $enVentana = false;
            $dias = null;
            if ($cierre) {
                $enVentana = $hoy->lte($cierre) && in_array((string) $ciclo->estado, ['abierto', 'en_proceso'], true);
                $dias = (int) $hoy->diffInDays($cierre, false);
                $dias = max(0, $dias);
            }

            $centros = 0;
            $cuentas = 0;
            $usuarios = 0;
            $empresas = 0;
            $misCentros = 0;

            if (Schema::hasTable('tbl_cc_asignaciones')) {
                $asigs = CcAsignacion::query()
                    ->where('ciclo_codigo', $ciclo->codigo)
                    ->get(['id', 'user_id', 'empresa', 'centro_codigo']);

                $centros = $asigs->unique(function ($a) {
                    return strtolower((string) $a->empresa).'|'.$a->centro_codigo;
                })->count();
                $usuarios = $asigs->pluck('user_id')->unique()->filter()->count();
                $empresas = $asigs->pluck('empresa')->map(fn ($e) => strtolower(trim((string) $e)))->filter()->unique()->count();
                $misCentros = $asigs->where('user_id', auth()->id())->unique(function ($a) {
                    return strtolower((string) $a->empresa).'|'.$a->centro_codigo;
                })->count();

                if (Schema::hasTable('tbl_cc_asignacion_cuentas') && $asigs->isNotEmpty()) {
                    $cuentas = (int) CcAsignacionCuenta::query()
                        ->whereIn('asignacion_id', $asigs->pluck('id'))
                        ->count();
                }
            }

            return [
                'hayCiclo' => true,
                'nombre' => $ciclo->nombre ?: ('Presupuesto '.(int) $ciclo->anio_presupuesto),
                'anioRef' => (int) $ciclo->anio_referencia,
                'anioPpto' => (int) $ciclo->anio_presupuesto,
                'estado' => (string) $ciclo->estado,
                'estadoLabel' => $labels[(string) $ciclo->estado] ?? ucfirst((string) $ciclo->estado),
                'enVentana' => $enVentana,
                'diasRestantes' => $dias,
                'capturaHasta' => $cierre ? $cierre->format('d/m/Y') : null,
                'centros' => $centros,
                'cuentas' => $cuentas,
                'usuarios' => $usuarios,
                'empresas' => $empresas,
                'misCentros' => $misCentros,
            ];
        } catch (\Throwable $e) {
            Log::warning('Resumen centros home: '.$e->getMessage());

            return $empty;
        }
    }

    /**
     * Resumen de alto nivel del ciclo de proyecciones de ventas.
     *
     * @return array<string, mixed>
     */
    private function obtenerResumenPvHome(): array
    {
        $labels = [
            'abierto' => 'Abierto',
            'en_proceso' => 'Abierto',
            'en_revision' => 'En revisión',
            'terminado' => 'Cerrado',
            'cerrado' => 'Cerrado',
        ];

        $empty = [
            'hayCiclo' => false,
            'nombre' => 'Proyecciones de ventas',
            'anioRef' => 2026,
            'anioPpto' => 2027,
            'estado' => '',
            'estadoLabel' => 'Sin ciclo',
            'enVentana' => false,
            'diasRestantes' => null,
            'capturaHasta' => null,
            'clientes' => 0,
            'productos' => 0,
            'usuarios' => 0,
            'empresas' => 0,
            'misClientes' => 0,
        ];

        try {
            if (! Schema::hasTable('tbl_pv_ciclos')) {
                return $empty;
            }

            $hoy = Carbon::today();
            $ciclos = PvCiclo::query()->orderByDesc('anio_presupuesto')->orderByDesc('id')->get();
            if ($ciclos->isEmpty()) {
                return $empty;
            }

            $ciclo = $ciclos->first(function (PvCiclo $c) use ($hoy) {
                $inicio = $c->fecha_inicio;
                $cierre = $c->captura_hasta ?: $c->fecha_fin;
                if ($inicio && $cierre) {
                    return $hoy->between($inicio, $cierre);
                }

                return in_array((string) $c->estado, ['abierto', 'en_proceso'], true);
            }) ?: $ciclos->first();

            $cierre = $ciclo->captura_hasta ?: $ciclo->fecha_fin;
            $enVentana = false;
            $dias = null;
            if ($cierre) {
                $enVentana = $hoy->lte($cierre) && in_array((string) $ciclo->estado, ['abierto', 'en_proceso'], true);
                $dias = (int) $hoy->diffInDays($cierre, false);
                $dias = max(0, $dias);
            }

            $clientes = 0;
            $productos = 0;
            $usuarios = 0;
            $empresas = 0;
            $misClientes = 0;

            if (Schema::hasTable('tbl_pv_asignaciones')) {
                $asigs = PvAsignacion::query()
                    ->where('ciclo_codigo', $ciclo->codigo)
                    ->get(['id', 'user_id', 'empresa', 'cliente_codigo']);

                $clientes = $asigs->unique(function ($a) {
                    return strtolower((string) $a->empresa).'|'.$a->cliente_codigo;
                })->count();
                $usuarios = $asigs->pluck('user_id')->unique()->filter()->count();
                $empresas = $asigs->pluck('empresa')->map(fn ($e) => strtolower(trim((string) $e)))->filter()->unique()->count();
                $misClientes = $asigs->where('user_id', auth()->id())->unique(function ($a) {
                    return strtolower((string) $a->empresa).'|'.$a->cliente_codigo;
                })->count();

                if (Schema::hasTable('tbl_pv_asignacion_productos') && $asigs->isNotEmpty()) {
                    $productos = (int) PvAsignacionProducto::query()
                        ->whereIn('asignacion_id', $asigs->pluck('id'))
                        ->count();
                }
            }

            return [
                'hayCiclo' => true,
                'nombre' => $ciclo->nombre ?: ('Proyección '.(int) $ciclo->anio_presupuesto),
                'anioRef' => (int) $ciclo->anio_referencia,
                'anioPpto' => (int) $ciclo->anio_presupuesto,
                'estado' => (string) $ciclo->estado,
                'estadoLabel' => $labels[(string) $ciclo->estado] ?? ucfirst((string) $ciclo->estado),
                'enVentana' => $enVentana,
                'diasRestantes' => $dias,
                'capturaHasta' => $cierre ? $cierre->format('d/m/Y') : null,
                'clientes' => $clientes,
                'productos' => $productos,
                'usuarios' => $usuarios,
                'empresas' => $empresas,
                'misClientes' => $misClientes,
            ];
        } catch (\Throwable $e) {
            Log::warning('Resumen proyecciones home: '.$e->getMessage());

            return $empty;
        }
    }

    /**
     * Resumen de mantenimientos preventivos semanales para el dashboard.
     *
     * @return array<string, mixed>
     */
    private function obtenerDatosCalendarioMantenimiento(): array
    {
        $defaults = [
            'mantenimientosCalendario' => [],
            'mantenimientoSemanalRealizados' => [],
            'mantenimientoSemanalPendientes' => [],
            'cumplimientoSemanal' => 0,
            'graficaCumplimiento' => ['labels' => [], 'valores' => []],
            'totalSemanal' => 0,
            'realizadosSemana' => 0,
            'pendientesSemana' => 0,
        ];

        if (!Schema::hasTable('tbl_programacion_mantenimiento') || !Schema::hasTable('tbl_mantenimientos')) {
            return $defaults;
        }

        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();
        $inicioSemana = Carbon::now()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $finSemana = Carbon::now()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $programaciones = ProgramacionMantenimiento::with(['maquina:id,codigo,nombre'])
            ->where('activo', 1)
            ->where('frecuencia', 'SEMANAL')
            ->get();

        $nombreMaquina = function ($maquina, int $id): string {
            if (!$maquina) {
                return 'Máquina #' . $id;
            }

            $etiqueta = trim(($maquina->codigo ? $maquina->codigo . ' — ' : '') . ($maquina->nombre ?: ''));

            return $etiqueta !== '' ? $etiqueta : 'Máquina #' . $id;
        };

        $realizados = [];
        $pendientes = [];

        foreach ($programaciones as $programacion) {
            $ultima = $programacion->ultima_ejecucion
                ? Carbon::parse($programacion->ultima_ejecucion)->startOfDay()
                : null;

            $item = [
                'programacion_id' => $programacion->id,
                'maquina_id' => $programacion->maquina_id,
                'maquina' => $nombreMaquina($programacion->maquina, (int) $programacion->maquina_id),
                'actividad' => $programacion->actividad,
                'proxima_ejecucion' => optional($programacion->proxima_ejecucion)->toDateString(),
                'ultima_ejecucion' => $ultima?->toDateString(),
            ];

            if ($ultima && $ultima->gte($inicioSemana) && $ultima->lte($finSemana)) {
                $item['estatus'] = 'REALIZADO';
                $realizados[] = $item;
            } else {
                $proxima = $programacion->proxima_ejecucion
                    ? Carbon::parse($programacion->proxima_ejecucion)->startOfDay()
                    : null;
                $item['estatus'] = ($proxima && $proxima->lt(Carbon::today())) ? 'VENCIDO' : 'PENDIENTE';
                $pendientes[] = $item;
            }
        }

        $totalSemanal = $programaciones->count();
        $realizadosSemana = count($realizados);
        $pendientesSemana = count($pendientes);
        $cumplimientoSemanal = $totalSemanal > 0
            ? round(($realizadosSemana / $totalSemanal) * 100, 1)
            : 0;

        $eventos = collect();

        Mantenimiento::with(['maquina:id,codigo,nombre'])
            ->where('tipo', 'PREVENTIVO')
            ->whereBetween('fecha_programada', [$inicioMes->toDateString(), $finMes->toDateString()])
            ->orderBy('fecha_programada')
            ->get()
            ->each(function ($mantenimiento) use ($eventos, $nombreMaquina) {
                $estatus = match ($mantenimiento->estatus) {
                    'FINALIZADO' => 'REALIZADO',
                    'CANCELADO' => 'CANCELADO',
                    default => 'PENDIENTE',
                };

                $eventos->push([
                    'fecha' => $mantenimiento->fecha_programada->toDateString(),
                    'maquina' => $nombreMaquina($mantenimiento->maquina, (int) $mantenimiento->maquina_id),
                    'maquina_id' => $mantenimiento->maquina_id,
                    'actividad' => \Illuminate\Support\Str::limit((string) $mantenimiento->descripcion, 100),
                    'estatus' => $estatus,
                    'tipo' => 'preventivo',
                ]);
            });

        foreach ($programaciones as $programacion) {
            if (!$programacion->proxima_ejecucion) {
                continue;
            }

            $fecha = Carbon::parse($programacion->proxima_ejecucion)->startOfDay();
            if ($fecha->lt($inicioMes) || $fecha->gt($finMes)) {
                continue;
            }

            $ultima = $programacion->ultima_ejecucion
                ? Carbon::parse($programacion->ultima_ejecucion)->startOfDay()
                : null;

            if ($ultima && $ultima->gte($inicioSemana)) {
                continue;
            }

            $eventos->push([
                'fecha' => $fecha->toDateString(),
                'maquina' => $nombreMaquina($programacion->maquina, (int) $programacion->maquina_id),
                'maquina_id' => $programacion->maquina_id,
                'actividad' => $programacion->actividad,
                'estatus' => $fecha->lt(Carbon::today()) ? 'VENCIDO' : 'PENDIENTE',
                'tipo' => 'programado',
            ]);
        }

        $labels = [];
        $valores = [];
        for ($i = 5; $i >= 0; $i--) {
            $wStart = Carbon::now()->subWeeks($i)->startOfWeek(Carbon::MONDAY)->startOfDay();
            $wEnd = Carbon::now()->subWeeks($i)->endOfWeek(Carbon::SUNDAY)->endOfDay();
            $labels[] = $wStart->format('d/m') . ' – ' . $wEnd->format('d/m');

            $hechos = $programaciones->filter(function ($programacion) use ($wStart, $wEnd) {
                if (!$programacion->ultima_ejecucion) {
                    return false;
                }
                $ultima = Carbon::parse($programacion->ultima_ejecucion)->startOfDay();

                return $ultima->gte($wStart) && $ultima->lte($wEnd);
            })->count();

            $valores[] = $totalSemanal > 0 ? round(($hechos / $totalSemanal) * 100, 1) : 0;
        }

        return [
            'mantenimientosCalendario' => $eventos->values()->all(),
            'mantenimientoSemanalRealizados' => $realizados,
            'mantenimientoSemanalPendientes' => $pendientes,
            'cumplimientoSemanal' => $cumplimientoSemanal,
            'graficaCumplimiento' => ['labels' => $labels, 'valores' => $valores],
            'totalSemanal' => $totalSemanal,
            'realizadosSemana' => $realizadosSemana,
            'pendientesSemana' => $pendientesSemana,
        ];
    }

}
