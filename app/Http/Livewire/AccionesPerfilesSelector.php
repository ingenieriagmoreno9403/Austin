<?php

namespace App\Http\Livewire;

use App\Models\Acciones;
use App\Models\perfil_acciones;
use App\Models\perfiles;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AccionesPerfilesSelector extends Component
{
    public $perfiles;
    public $perfilSeleccionado = '';
    public $accionesSeleccionadas = [];

    public function mount()
    {
        $this->perfiles = perfiles::select('id', 'nombre')
            ->orderBy('nombre')
            ->get();
    }

    public function updatedPerfilSeleccionado($perfil)
    {
        $this->accionesSeleccionadas = [];

        if (!$perfil) {
            return;
        }

        $this->accionesSeleccionadas = perfil_acciones::where('idperfil', $perfil)
            ->pluck('idaccion')
            ->map(function ($idaccion) {
                return (string) $idaccion;
            })
            ->toArray();
    }

    public function guardar()
    {
        if (!$this->perfilSeleccionado) {
            session()->flash('warningGuardar', 'Selecciona un perfil para continuar.');
            return;
        }

        $accionesSeleccionadas = collect($this->accionesSeleccionadas)
            ->map(function ($idaccion) {
                return (int) $idaccion;
            })
            ->filter()
            ->unique()
            ->values();

        DB::transaction(function () use ($accionesSeleccionadas) {
            $asignaciones = perfil_acciones::where('idperfil', $this->perfilSeleccionado);

            if ($accionesSeleccionadas->isEmpty()) {
                $asignaciones->delete();
            } else {
                $asignaciones->whereNotIn('idaccion', $accionesSeleccionadas)->delete();
            }

            foreach ($accionesSeleccionadas as $idaccion) {
                $existeAsignacion = perfil_acciones::where('idperfil', $this->perfilSeleccionado)
                    ->where('idaccion', $idaccion)
                    ->exists();

                if (!$existeAsignacion) {
                    $perfilAccion = new perfil_acciones();
                    $perfilAccion->idperfil = $this->perfilSeleccionado;
                    $perfilAccion->idaccion = $idaccion;
                    $perfilAccion->created_by = auth()->user()->name;
                    $perfilAccion->save();
                }
            }
        });

        $this->updatedPerfilSeleccionado($this->perfilSeleccionado);
        session()->flash('success_msg_large', '¡Se guardaron los cambios correctamente!');
    }

    public function getAccionesAgrupadasProperty()
    {
        return Acciones::join('tblvistas', 'tblacciones.idvista', '=', 'tblvistas.id')
            ->join('tbldepartamentos', 'tblvistas.iddepartamento', '=', 'tbldepartamentos.id')
            ->select(
                'tblacciones.id',
                'tblacciones.nombre_accion',
                'tblacciones.descripcion_accion',
                'tblvistas.nombre as vista',
                'tbldepartamentos.nombre as departamento'
            )
            ->orderBy('tbldepartamentos.nombre')
            ->orderBy('tblvistas.nombre')
            ->orderBy('tblacciones.descripcion_accion')
            ->get()
            ->groupBy(['departamento', 'vista']);
    }

    public function render()
    {
        return view('livewire.acciones-perfiles-selector');
    }
}
