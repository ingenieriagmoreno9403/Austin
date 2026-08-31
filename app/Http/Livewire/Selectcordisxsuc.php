<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Sucursales;
use App\Models\User;
use App\Models\distribuidores;
use App\Traits\DatosimpleTraits;
use App\Models\valeras;
use App\Models\empleados;
use DB;
class Selectcordisxsuc extends Component
{
    use DatosimpleTraits;
    public $sucursales;
    public $cordinadores;
    public $distribuidores;
    public $valeras;

    public $selectsucursales;
    public $selectcordinadores;
    public $selecdistribuidores;
    public $selecvaleras;

    public function mount()
    {
    $this->selectsucursales = Sucursales::all();
    $this->selectcordinadores =null;
    $this->selecdistribuidores =null;
    $this->selecvaleras = null;
    }

    public function updatedsucursales($value)
    {
        // $condiciones = [
        //     ['tblempleados.idpuesto','=',19],
        //     ['tblempleados.idsucursal','=',$value]
        // ];
        // $this->selectcordinadores = empleados::select('tblempleados.id as idem','tblempleados.primer_nombre','tblempleados.segundo_nombre','tblempleados.apellido_paterno','tblempleados.apellido_materno','tblempleados.idsucursal')
        // ->where($condiciones)
        // ->get();
        
         $this->selectcordinadores = DB::select("SELECT tblempleados.id as idem, tblempleados.primer_nombre,tblempleados.segundo_nombre,tblempleados.apellido_paterno,tblempleados.apellido_materno,tblempleados.idsucursal
          FROM tblempleados where tblempleados.idpuesto = 19 and tblempleados.idsucursal = ?;",[$value]);

        
          $condiciones2 = [
            ['tblvaleras.status_valera','=',"I"],
            ['tblvaleras.idsucursal','=',$value]
        ];
        
        
        $this->selecvaleras = valeras::where($condiciones2)->get();
 
    }


    public function updatedcordinadores($value22)
    {
        $condiciones = [
            ['tbldistribuidores.idstatus','=',8],
            ['tbldistribuidores.id_responsable','=',$value22]
        ];
        
         $sucursal2="";
        //  $this->selecdistribuidores = distribuidores::where($condiciones)->get();
         $this->selecdistribuidores = distribuidores::select('tbldistribuidores.id','tbldistribuidores.primer_nombre','tbldistribuidores.segundo_nombre','tbldistribuidores.apellido_paterno','tbldistribuidores.apellido_materno')
        ->where($condiciones)
        ->get();
     

        $obtenersucursalxempleado = $this->obtenerlistaempleadoid($value22);
        foreach($obtenersucursalxempleado as $lista)
        {
            $sucursal2=$lista->idsucursal;
        }

        $condiciones = [
            ['tblempleados.idpuesto','=',19],
            ['tblempleados.idsucursal','=',$sucursal2]
        ];
        $this->selectcordinadores = DB::select("SELECT tblempleados.id as idem, tblempleados.primer_nombre,tblempleados.segundo_nombre,tblempleados.apellido_paterno,tblempleados.apellido_materno,tblempleados.idsucursal
          FROM tblempleados where tblempleados.idpuesto = 19 and tblempleados.idsucursal = ?;",[$sucursal2]);
          
        //   empleados::select('tblempleados.id as idem','tblempleados.primer_nombre','tblempleados.segundo_nombre','tblempleados.apellido_paterno','tblempleados.apellido_materno','tblempleados.idsucursal')
        // ->where($condiciones)
        // ->get();

    }
    

  
    public function render()
    {
       
        return view('livewire.selectcordisxsuc');
    }
}
