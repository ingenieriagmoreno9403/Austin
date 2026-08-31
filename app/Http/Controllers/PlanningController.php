<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;
use App\Traits\SistemasTraits;
use App\Traits\DatosimpleTraits;
use App\Traits\MenuTrait;

class PlanningController extends Controller
{
    use DatosimpleTraits;
    use SistemasTraits;
    use MenuTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(){
        try{
            $varpantallas =  $this->Traermenuenc();
            $varsubmenus =   $this->Traermenudet();
            $date = Carbon::now();
            $date = $date->format('Y-m-d');
            $idusuario=auth()->user()->id;
            
            // Datos de ejemplo para el planning
            $activities = [
                [
                    'id' => 1,
                    'title' => 'Reunión con Cliente',
                    'description' => 'Presentación del proyecto final',
                    'date' => '2024-01-15',
                    'time' => '14:00',
                    'priority' => 'high',
                    'status' => 'pending'
                ],
                [
                    'id' => 2,
                    'title' => 'Revisar Propuesta',
                    'description' => 'Análisis de la propuesta técnica',
                    'date' => '2024-01-16',
                    'time' => '09:00',
                    'priority' => 'medium',
                    'status' => 'pending'
                ],
                [
                    'id' => 3,
                    'title' => 'Enviar Facturas',
                    'description' => 'Facturación mensual',
                    'date' => '2024-01-14',
                    'time' => '16:00',
                    'priority' => 'urgent',
                    'status' => 'completed'
                ],
                [
                    'id' => 4,
                    'title' => 'Revisión de Inventario',
                    'description' => 'Conteo físico de productos',
                    'date' => '2024-01-20',
                    'time' => '10:00',
                    'priority' => 'medium',
                    'status' => 'pending'
                ],
                [
                    'id' => 5,
                    'title' => 'Entrevista Personal',
                    'description' => 'Entrevista para puesto de desarrollador',
                    'date' => '2024-01-18',
                    'time' => '15:30',
                    'priority' => 'high',
                    'status' => 'pending'
                ]
            ];
            
            // Estadísticas
            $stats = [
                'pending' => count(array_filter($activities, fn($a) => $a['status'] === 'pending')),
                'completed' => count(array_filter($activities, fn($a) => $a['status'] === 'completed')),
                'urgent' => count(array_filter($activities, fn($a) => $a['priority'] === 'urgent')),
                'total' => count($activities)
            ];
           
            return view('Planning.index', compact('varpantallas', 'varsubmenus', 'activities', 'stats'));
        } catch(\Illuminate\Database\QueryException $ex){  
            return back()->with("warningBD","no guardado correctamente"); 
        }
    }
}
