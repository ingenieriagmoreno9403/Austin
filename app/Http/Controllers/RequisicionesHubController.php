<?php

namespace App\Http\Controllers;

use App\Models\Requisicion;
use App\Traits\MenuTrait;
use Illuminate\Support\Facades\Schema;

class RequisicionesHubController extends Controller
{
    use MenuTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        $userId = (int) auth()->id();

        $reqPendientesAuth = 0;
        $reqMiasAbiertas = 0;
        $reqAutorizadas = 0;
        if (Schema::hasTable('tbl_requisiciones')) {
            $reqPendientesAuth = Requisicion::query()
                ->where('responsable_actual_id', $userId)
                ->whereIn('estado', ['pendiente_autorizacion', 'reenviada'])
                ->count();
            $reqMiasAbiertas = Requisicion::query()
                ->where('solicitante_id', $userId)
                ->whereNotIn('estado', ['atendida', 'cancelada', 'rechazada'])
                ->count();
            $reqAutorizadas = Requisicion::query()
                ->where('estado', 'autorizada')
                ->count();
        }

        return view('requisiciones.hub', compact(
            'varpantallas',
            'varsubmenus',
            'reqPendientesAuth',
            'reqMiasAbiertas',
            'reqAutorizadas'
        ));
    }
}
