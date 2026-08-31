<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Traits\DatosimpleTraits;
use App\Traits\SistemasTraits;
use App\Traits\CancelacionesTraits;
use Log;

class CreditosDictamenDist extends Component
{
    use DatosimpleTraits;
    use SistemasTraits;
    use CancelacionesTraits;

    public $varmesadecredito = [];
    public $permisos = [];
    public $selectedPermiso;
    public $varSucursalesUser = [];
    public $editar_credito;
    public $revisar_solicitud;
    public $enviar_mesa_cred;
    public $descargar_contrato;
    public $creditos_dictamen;




    public function mount()
    {
        $this->getCreditosDictamenDist();
    }
    public function render()
    {
        return view('livewire.creditos-dictamen-dist', [
            'varmesadecredito' => $this->varmesadecredito,
            'permisos' => $this->permisos,
            'varSucursalesUser' => $this->varSucursalesUser,
            'editar_credito' => $this->editar_credito,
            'revisar_solicitud' => $this->revisar_solicitud,
            'enviar_mesa_cred' => $this->enviar_mesa_cred,
            'descargar_contrato' => $this->descargar_contrato,
            'creditos_dictamen' => $this->creditos_dictamen

        ]
    );
    }


    public function updatedSelectedPermiso($value)
    {
        $this->getCreditosDictamenDist($value);
    }

    public function getCreditosDictamenDist(int $id_status = null)
    {
        try{
            $idusuario=auth()->user()->id;
            $this->varSucursalesUser = $this->obtenerSucursalesxUser($idusuario);   
            $permisos1 = $this->forpermisos('creditos_dictamen'); 
            $permisos2 = $this->forpermisos('descargar_contrato'); 
            $permisos3 = $this->forpermisos('revisar_solicitud'); 
            $permisos4 = $this->forpermisos('editar_credito'); 
            $permisos5 = $this->forpermisos('enviar_mesa_cred');
            if($permisos1=="creditos_dictamen"){$this->creditos_dictamen ="A";}else{$this->creditos_dictamen ="I";}
            if($permisos2=="descargar_contrato"){$this->descargar_contrato ="A";}else{$this->descargar_contrato ="I";}
            if($permisos3=="revisar_solicitud"){$this->revisar_solicitud ="A";}else{$this->revisar_solicitud ="I";}
            if($permisos4=="editar_credito"){$this->editar_credito ="A";}else{$this->editar_credito ="I";}
            if($permisos5=="enviar_mesa_cred"){$this->enviar_mesa_cred ="A";}else{$this->enviar_mesa_cred ="I";}
            Log::info($this->editar_credito);
            Log::info($this->revisar_solicitud);
            

            if($this->editar_credito == "A" && $this->revisar_solicitud == "A" ){
                if($id_status != null){
                    $this->varmesadecredito = $this->obtenermesacredxpermiso($id_status);
                    $this->permisos = $this->obtenerStatusdist(2);
                    return;
                }else{
                $this->varmesadecredito = $this->obtenermesacredxpermisos(1);
                $this->permisos = $this->obtenerStatusdist(2);
                }
            }
            else if($this->editar_credito == "A"){
                if($id_status != null){
                    $this->varmesadecredito = $this->obtenermesacredxpermiso($id_status);
                    $this->permisos = $this->obtenerStatusdist(1);
                    return;
                }else{
                $this->varmesadecredito = $this->obtenermesacredxpermisos(2);
                $this->permisos = $this->obtenerStatusdist(1);
                }
            }
          } catch(\Illuminate\Database\QueryException $ex){ return back()->with("warningBD","no guardado correctamente");} 
    }
}
