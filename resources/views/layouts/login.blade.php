<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="ap-login-html">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AUSTIN POWDER ® - MBNTAS ERP</title>
    <link rel="icon" type="image/png" href="{{ asset('Images/AUSTIN_POWDER.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="{{ asset('css/login.css') }}?v={{ @filemtime(public_path('css/login.css')) ?: time() }}" rel="stylesheet">
</head>
<body class="ap-login-page">
    @yield('content')

    <script>
        function mostrarPassword() {
            var cambio = document.getElementById('txtPassword');
            var icon = document.querySelector('#show_password .icon');
            if (!cambio) return;
            if (cambio.type === 'password') {
                cambio.type = 'text';
                if (icon) icon.className = 'fa fa-eye icon';
            } else {
                cambio.type = 'password';
                if (icon) icon.className = 'fa fa-eye-slash icon';
            }
        }
    </script>
</body>
</html>
