<?php

namespace App\Traits;

trait CargaMenuTrait
{
    protected function cargarDatosComunes()
    {
        return [
            'varpantallas' => $this->Traermenuenc(),
            'varsubmenus' => $this->Traermenudet(),
            'varlistausers' => $this->obtenerusuarios(),
            'idusuarioid' => auth()->user()->id,
            'idusuario' => auth()->user()->name,
        ];
    }
}
