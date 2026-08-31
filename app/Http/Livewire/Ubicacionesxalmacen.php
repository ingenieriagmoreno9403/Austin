<?php

namespace App\Http\Livewire;
use App\Models\Almacenes;
use App\Models\Ubicaciones;

use Livewire\Component;

class Ubicacionesxalmacen extends Component
{
   public $almacenes,$ubicaciones;
    public $almacen =[], $ubicacion=[];

  public function mount()
    {

 
         $almacenes= Almacenes::all();
            
          $this->almacen =$almacenes;
          $this->ubicacion = collect();
    }

    public function updatedalmacenes($value)
    {
        $this->ubicacion = Ubicaciones::where('id_almacen',$value)->get();
        $this->ubicaciones = $this->ubicacion->first()->id ?? null;
    }


    public function render()
    {
        return view('livewire.ubicacionesxalmacen');
    }
}
