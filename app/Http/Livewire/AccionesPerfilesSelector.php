<?php

namespace App\Http\Livewire;

use App\Models\Acciones;
use App\Models\perfil_acciones;
use App\Models\perfiles;
use App\Traits\EmpresaCatalogoTrait;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AccionesPerfilesSelector extends Component
{
    use EmpresaCatalogoTrait;

    public $perfiles;
    public $perfilSeleccionado = '';
    public $accionesSeleccionadas = [];
    public $esMasterEmpresa = false;

    public function mount()
    {
        $this->esMasterEmpresa = $this->esSesionMasterEmpresa();
        $this->perfiles = $this->consultarPerfilesDisponibles();
    }

    public function updatedPerfilSeleccionado($perfil)
    {
        $this->accionesSeleccionadas = [];

        if (!$perfil || !$this->perfilPermitido((int) $perfil)) {
            $this->perfilSeleccionado = '';
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

        if (!$this->perfilPermitido((int) $this->perfilSeleccionado)) {
            session()->flash('warningGuardar', 'Ese perfil no está habilitado para tu empresa.');
            return;
        }

        $accionesSeleccionadas = collect($this->accionesSeleccionadas)
            ->map(function ($idaccion) {
                return (int) $idaccion;
            })
            ->filter()
            ->unique()
            ->values();

        $accionesVisibles = $this->idsAccionesVisibles();
        if (is_array($accionesVisibles)) {
            $accionesSeleccionadas = $accionesSeleccionadas
                ->intersect($accionesVisibles)
                ->values();
        }

        DB::transaction(function () use ($accionesSeleccionadas, $accionesVisibles) {
            $asignaciones = perfil_acciones::where('idperfil', $this->perfilSeleccionado);

            if (is_array($accionesVisibles)) {
                if (!empty($accionesVisibles)) {
                    $asignaciones->whereIn('idaccion', $accionesVisibles)
                        ->when($accionesSeleccionadas->isNotEmpty(), fn ($q) => $q->whereNotIn('idaccion', $accionesSeleccionadas))
                        ->delete();
                }
            } elseif ($accionesSeleccionadas->isEmpty()) {
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
        $query = Acciones::join('tblvistas', 'tblacciones.idvista', '=', 'tblvistas.id')
            ->join('tbldepartamentos', 'tblvistas.iddepartamento', '=', 'tbldepartamentos.id')
            ->select(
                'tblacciones.id',
                'tblacciones.nombre_accion',
                'tblacciones.descripcion_accion',
                'tblvistas.nombre as vista',
                'tbldepartamentos.nombre as departamento'
            );

        $vistasPermitidas = $this->idsVistasVisibles();
        if (is_array($vistasPermitidas)) {
            $query->whereIn('tblvistas.id', $vistasPermitidas ?: [0]);
        }

        return $query
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

    private function consultarPerfilesDisponibles()
    {
        $query = perfiles::select('id', 'nombre')->orderBy('nombre');
        $permitidos = $this->idsPerfilesVisibles();
        if (is_array($permitidos)) {
            $query->whereIn('id', $permitidos ?: [0]);
        }

        return $query->get();
    }

    private function perfilPermitido(int $idPerfil): bool
    {
        $permitidos = $this->idsPerfilesVisibles();
        if (!is_array($permitidos)) {
            return true;
        }

        return in_array($idPerfil, $permitidos, true);
    }

    private function idsPerfilesVisibles(): ?array
    {
        if (!$this->esSesionMasterEmpresa()) {
            return null;
        }

        return $this->perfilesPermitidosEmpresa($this->empresaIdSesion()) ?? [];
    }

    private function idsVistasVisibles(): ?array
    {
        if (!$this->esSesionMasterEmpresa()) {
            return null;
        }

        return $this->vistasPermitidasEmpresa($this->empresaIdSesion()) ?? [];
    }

    private function idsAccionesVisibles(): ?array
    {
        $vistas = $this->idsVistasVisibles();
        if (!is_array($vistas)) {
            return null;
        }

        if (!$vistas) {
            return [];
        }

        return DB::table('tblacciones')
            ->whereIn('idvista', $vistas)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
