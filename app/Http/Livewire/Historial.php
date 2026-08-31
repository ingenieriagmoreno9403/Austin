<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Carbon\Carbon;
use App\Traits\MenuTrait;
use App\Traits\GlobalTraits;
use App\Traits\SistemasTraits;
use App\Traits\DatosimpleTraits;
use App\Models\historial_cuentas;
use App\Models\historial_cajas;
use App\Models\cuentas;
use App\Models\Cajas;
use App\Exports\MovimientosHistorial;
use Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;
use DB;
use App\Models\arqueocajas;
use App\Models\arqueorelacion_efect;
use App\Http\Controllers\MovimientosController; 


class Historial extends Component
{
    use MenuTrait;
    use DatosimpleTraits;
    use SistemasTraits;
    use GlobalTraits;
    public $fecha;
    public $tipo;
    public $recordId;
    public $empresaid;
    public $obtenerHistorial = [];

    public $varaqueo;
    public $validaAutorizar;
    public $permisos1;

    public $mesNombre;



    public function mount(string $tipo, int $recordId, int $empresaId, string $fecha = null)
    {
        $this->tipo = $tipo;
        $this->recordId = $recordId;
        $this->empresaid = $empresaId;
        $this->fecha = $fecha ?? Carbon::now()->format('Y-m-d'); 
        $this->actualizarHistorial($this->fecha, $recordId);
    }
   


    public function updatedFecha($value)
    {
        $this->fecha = $value;
        $this->actualizarHistorial($this->fecha, $this->recordId);
    }

    public function actualizarHistorial(string $fecha, int $id)
    {
        try {
            $this->obtenerHistorial = $this->obtenerResponsableCajaDiario($id, $fecha);
            $this->varaqueo = $this->obtenerArqueoCajas($fecha,$id);
            $this->validaAutorizar = $this->validarArqueoRealizado($id);
                if($this->varaqueo->isEmpty()){ $this->varaqueo= "null";
                }
                $date = Carbon::now();
            if($fecha == "null"){ $fecha = $date->format('Y-m-d');}
            $fecha = $date->format('Y-m-d');
            $mes = $date->format('m');
            $año = $date->format('Y');
            $fecha_inicio = $año."-".$mes."-01";
            $fecha_fin = $año."-".$mes."-31";
            $permisos1 = $this->forpermisos('movimientos_exportar');
            $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
            $this->mesNombre = $meses[$mes-1];
        } catch (\Illuminate\Database\QueryException $ex) {
        }
    }

    public function render()
    {
        $this->permisos1 = $this->forpermisos('filtrar_arqueos');
        return view('livewire.historial', [
            'obtenerHistorial' => $this->obtenerHistorial,
            'permisos1' => $this->permisos1,
            'fecha' => $this->fecha,
            'varaqueo' => $this->varaqueo,
            'mesNombre' => $this->mesNombre,
            'id' => $this->recordId,
            'empresaid' => $this->empresaid,
            'validaAutorizar' => $this->validaAutorizar
        ]);
    }
}
