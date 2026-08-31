<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Traits\CargaMenuTrait;
use App\Traits\MenuTrait;
use App\Traits\DatosimpleTraits;

class PruebaError extends Component
{

    use MenuTrait;
    use DatosimpleTraits;
    use CargaMenuTrait;


    public function lanzarError()
    {
        $comunesymenus = $this->cargarDatosComunes();
        $this->emit('errorEvent', 'Este es un error de prueba.');
    }

    public function render()
    {
        return view('livewire.prueba-error');
    }
}
