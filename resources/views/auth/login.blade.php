@extends('layouts.appfront')
@section('content')
<div class="ap-login">
    <aside class="ap-login-brand">
        <div class="ap-login-fx" aria-hidden="true">
            <span class="ap-glow"></span>
            <span class="ap-ring"></span>
            <span class="ap-shine"></span>
            <span class="ap-grid"></span>
        </div>
        <p class="ap-login-brand-kicker">Used in 1833 · &amp; ever since</p>
        <img class="ap-login-brand-logo" src="{{ asset('Images/AUSTIN_POWDER.png') }}" alt="Austin Powder">
        <div class="ap-login-brand-foot">
            <strong>Austin Powder</strong>
            <span>MBNTAS ERP</span>
        </div>
    </aside>

    <section class="ap-login-panel">
        <div class="ap-login-card">
            <img class="ap-login-mark" src="{{ asset('Images/AUSTIN_POWDER.png') }}" alt="">
            <p class="ap-login-kicker">Austin Powder</p>
            <h1>Ingresar</h1>
            <p class="ap-login-sub">Usa tu correo y contraseña para entrar al sistema.</p>

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <label class="ap-label" for="user1">Correo electrónico</label>
                <input
                    id="user1"
                    type="email"
                    class="ap-input @error('email') is-invalid @enderror"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="username"
                    autofocus
                >
                @error('email')
                    <span class="ap-error" role="alert">{{ $message }}</span>
                @enderror

                <label class="ap-label" for="txtPassword">Contraseña</label>
                <div class="ap-pass">
                    <input
                        id="txtPassword"
                        type="password"
                        class="ap-input @error('password') is-invalid @enderror"
                        name="password"
                        required
                        autocomplete="current-password"
                    >
                    <button class="ap-pass-toggle" id="show_password" type="button" onclick="mostrarPassword()" aria-label="Mostrar contraseña">
                        <span class="fa fa-eye-slash icon"></span>
                    </button>
                </div>
                @error('password')
                    <span class="ap-error" role="alert">{{ $message }}</span>
                @enderror

                <label class="ap-check">
                    <input id="check" type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <span>Mantener sesión iniciada</span>
                </label>

                <button type="submit" class="ap-btn">Ingresar</button>
            </form>
        </div>
    </section>
</div>
@endsection
