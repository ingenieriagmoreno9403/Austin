<?php

namespace App\Http\Controllers;

use App\Models\GestionAlumnosEmpresa;
use App\Models\GestionAlumnosVisitaProspeccion;
use App\Models\EventoSocio;
use App\Traits\DatosimpleTraits;
use App\Traits\MenuTrait;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

class GestionAlumnosViewController extends Controller
{
    use MenuTrait;
    use DatosimpleTraits;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function alumnos(): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        return view('Gestion_alumnos.alumnos', compact('varpantallas', 'varsubmenus'));
    }

    public function empresas(): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        return view('Gestion_alumnos.empresas', compact('varpantallas', 'varsubmenus'));
    }

    public function escuelas(): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        return view('Gestion_alumnos.escuelas', compact('varpantallas', 'varsubmenus'));
    }

    public function especialidades(): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        return view('Gestion_alumnos.especialidades', compact('varpantallas', 'varsubmenus'));
    }

    public function documentosSolicitados(): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        return view('Gestion_alumnos.documentos_solicitados', compact('varpantallas', 'varsubmenus'));
    }

    public function socios(): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        return view('Gestion_alumnos.socios', compact('varpantallas', 'varsubmenus'));
    }

    public function sociosCheckout(): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        return view('Gestion_alumnos.socios_checkout', compact('varpantallas', 'varsubmenus'));
    }

    public function sociosReportesDiarios(): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        return view('Gestion_alumnos.socios_reportes_diarios', compact('varpantallas', 'varsubmenus'));
    }

    public function sociosEventos(): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        return view('Gestion_alumnos.socios_eventos', compact('varpantallas', 'varsubmenus'));
    }

    public function sociosEventoDetalle(int $id): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $evento = EventoSocio::query()->findOrFail($id);

        return view('Gestion_alumnos.socios_evento_detalle', compact('varpantallas', 'varsubmenus', 'evento'));
    }

    public function cursos(): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        return view('Gestion_alumnos.cursos', compact('varpantallas', 'varsubmenus'));
    }

    public function docentes(): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        return view('Gestion_alumnos.docentes', compact('varpantallas', 'varsubmenus'));
    }

    public function calendarioVisitas(): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        $visitasCalendario = [];
        if (Schema::hasTable('tblvisitas_prospeccion') && Schema::hasTable('tblga_empresas')) {
            $visitasCalendario = GestionAlumnosVisitaProspeccion::query()
                ->with(['empresa:id,nombre,estado'])
                ->orderBy('fecha_visita')
                ->get(['id', 'empresa_id', 'fecha_visita', 'acepta_adoptar_sed'])
                ->map(function ($v) {
                    return [
                        'fecha_visita' => optional($v->fecha_visita)->toDateString(),
                        'empresa' => optional($v->empresa)->nombre ?: 'Empresa sin nombre',
                        'estado' => optional($v->empresa)->estado ?: 'Sin estado',
                        'estatus' => $v->acepta_adoptar_sed ?: 'Pendiente',
                    ];
                })
                ->values()
                ->all();
        }

        return view('Gestion_alumnos.calendario_visitas', compact('varpantallas', 'varsubmenus', 'visitasCalendario'));
    }

    public function convenios(): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        return view('Gestion_alumnos.convenios', compact('varpantallas', 'varsubmenus'));
    }

    public function conveniosAsignar(): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        return view('Gestion_alumnos.convenios_asignar', compact('varpantallas', 'varsubmenus'));
    }

    public function documentosAlumno(): View
    {
        if(auth()->user()->tipo == "alumno") {
             $id_alumno = auth()->user()->id_tipo;
        }else{
             abort(403, 'No tienes permiso para acceder a esta página, debes contar con perfil de alumno.');
        }
        
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $alumnoIdRequest = (int) request()->query('alumno_id', $id_alumno);
        $alumnoIdRequest = $alumnoIdRequest > 0 ? $alumnoIdRequest : $id_alumno;
        $alumnoId = $this->obtenerAlumnoPortalIdDesdeUsuario(auth()->user(), $alumnoIdRequest);
        $alumno = $this->obtenerAlumnoPortal($alumnoId);

        return view('Gestion_alumnos.documentos_alumno', compact('varpantallas', 'varsubmenus', 'alumnoId', 'alumno'));
    }

    public function registroAspiranteDual(): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        return view('Gestion_alumnos.documentos.registro_aspirante_dual', compact('varpantallas', 'varsubmenus'));
    }

    public function testPersonalidadAulaMixta(): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        return view('Gestion_alumnos.documentos.test_personalidad_aula_mixta', compact('varpantallas', 'varsubmenus'));
    }

    public function testPersonalidadAulaMixtaSimple(): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        return view('Gestion_alumnos.documentos.test_personalidad_aula_mixta_simple', compact('varpantallas', 'varsubmenus'));
    }

    public function controlAsistencias(): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $usuario = auth()->user();
        $empresaIdUsuario = $usuario && strtolower((string) $usuario->tipo) === 'empresa'
            ? (int) $usuario->id_tipo
            : null;

        return view('Gestion_alumnos.control_asistencias', compact('varpantallas', 'varsubmenus', 'empresaIdUsuario'));
    }

    public function calculoPagos(): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $usuario = auth()->user();
        $empresaIdUsuario = $usuario && strtolower((string) $usuario->tipo) === 'empresa'
            ? (int) $usuario->id_tipo
            : null;

        return view('Gestion_alumnos.calculo_pagos', compact('varpantallas', 'varsubmenus', 'empresaIdUsuario'));
    }

    public function facturacionEmpresa(): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $usuario = auth()->user();
        $empresaIdUsuario = $usuario && strtolower((string) $usuario->tipo) === 'empresa'
            ? (int) $usuario->id_tipo
            : null;

        return view('Gestion_alumnos.facturacion_empresa', compact('varpantallas', 'varsubmenus', 'empresaIdUsuario'));
    }

    public function intranet(): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $empresaPerfil = null;
        $usuario = auth()->user();

        if ($usuario && strtolower((string) $usuario->tipo) === 'empresa' && $usuario->id_tipo) {
            $empresaPerfil = GestionAlumnosEmpresa::query()->find($usuario->id_tipo);
        }

        return view('Gestion_alumnos.intranet', compact('varpantallas', 'varsubmenus', 'empresaPerfil'));
    }

}
