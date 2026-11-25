@extends('layouts.app')

@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">
<style>
    .req-hub { max-width: 1100px; margin: 0 auto; }
    .req-hub-head { margin-bottom: 1.1rem; }
    .req-hub-head h1 { font-size: 1.55rem; font-weight: 800; margin: 0; color: #111827; }
    .req-hub-head p { margin: .35rem 0 0; color: #6b7280; font-size: .92rem; }
    .req-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: .9rem; }
    .req-card {
        display: block; text-decoration: none; color: inherit;
        background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
        padding: 1rem 1.05rem; transition: border-color .15s, box-shadow .15s, transform .15s;
        min-height: 150px;
    }
    .req-card:hover { border-color: #111827; box-shadow: 0 10px 24px rgba(0,0,0,.06); transform: translateY(-2px); color: inherit; }
    .req-card .ico {
        width: 2.4rem; height: 2.4rem; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center;
        background: #f3f4f6; color: #111827; margin-bottom: .7rem;
    }
    .req-card h3 { font-size: 1.05rem; font-weight: 800; margin: 0 0 .35rem; }
    .req-card p { margin: 0; color: #6b7280; font-size: .84rem; line-height: 1.35; }
    .req-card .meta { margin-top: .75rem; font-size: .78rem; font-weight: 700; color: #374151; }
    .badge-soft {
        display: inline-block; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;
        border-radius: 999px; padding: .15rem .55rem; font-size: .72rem; font-weight: 800;
    }
    .badge-warn {
        display: inline-block; background: #fffbeb; color: #b45309; border: 1px solid #fde68a;
        border-radius: 999px; padding: .15rem .55rem; font-size: .72rem; font-weight: 800;
    }
</style>

<div class="container-fluid py-3 req-hub">
    <div class="req-hub-head">
        <h1>Requisiciones</h1>
        <p>Solicitud, autorización por niveles y seguimiento hasta dejarlas listas para Compras.</p>
    </div>

    <div class="req-grid">
        <a class="req-card" href="{{ route('requisiciones.index') }}">
            <div class="ico"><i class="fa-solid fa-file-signature"></i></div>
            <h3>Mis requisiciones</h3>
            <p>Consulta, edita borradores y da seguimiento a tus solicitudes.</p>
            <div class="meta"><span class="badge-soft">{{ $reqMiasAbiertas }} abiertas</span></div>
        </a>

        <a class="req-card" href="{{ route('requisiciones.create') }}">
            <div class="ico"><i class="fa-solid fa-plus"></i></div>
            <h3>Nueva requisición</h3>
            <p>Captura una necesidad con partidas y justificación.</p>
            <div class="meta"><span class="badge-soft">Crear borrador</span></div>
        </a>

        <a class="req-card" href="{{ route('requisiciones.index', ['vista' => 'autorizar']) }}">
            <div class="ico"><i class="fa-solid fa-stamp"></i></div>
            <h3>Bandeja de autorización</h3>
            <p>Pendientes asignadas a tu usuario: autorizar, devolver o rechazar.</p>
            <div class="meta">
                <span class="{{ $reqPendientesAuth ? 'badge-warn' : 'badge-soft' }}">{{ $reqPendientesAuth }} pendientes</span>
            </div>
        </a>

        <a class="req-card" href="{{ route('requisiciones.index', ['vista' => 'autorizadas']) }}">
            <div class="ico"><i class="fa-solid fa-circle-check"></i></div>
            <h3>Listas para Compras</h3>
            <p>Requisiciones completamente autorizadas, listas para cotizar u OC.</p>
            <div class="meta"><span class="badge-soft">{{ $reqAutorizadas }} autorizadas</span></div>
        </a>

        @if(in_array(strtolower((string) (auth()->user()->tipo ?? '')), ['master', 'empresa'], true))
        <a class="req-card" href="{{ route('requisiciones.config') }}">
            <div class="ico"><i class="fa-solid fa-route"></i></div>
            <h3>Rutas de autorización</h3>
            <p>Configura niveles, responsables y suplentes por departamento.</p>
            <div class="meta"><span class="badge-soft">Administración</span></div>
        </a>
        @endif
    </div>
</div>
@endsection
