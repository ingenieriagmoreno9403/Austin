<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>AUSTIN POWDER  ® - MBNTAS ERP</title>
    <link rel="icon" type="image/png" href="{{ asset('Images/AUSTIN_POWDER.png') }}">

    <link href="{{ asset('css/menu.css') }}" rel="stylesheet">
    <link href="{{ asset('css/main.css') }}" rel="stylesheet">
    <link href="{{ asset('css/tables.css') }}" rel="stylesheet">
    <link href="{{ asset('css/forms.css') }}" rel="stylesheet">

    <!-- Scripts -->
    <!-- JQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>


    <!-- Datatables -->
    <link href="https://cdn.datatables.net/v/dt/dt-2.1.8/datatables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.0/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.0/js/buttons.dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.0/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.0/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.1/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.0/css/buttons.dataTables.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.bootstrap5.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.1/css/buttons.bootstrap5.css" />

    <!-- Select 2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    {{-- Styles --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>


    <!-- animate -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.15.2/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.15.2/dist/sweetalert2.all.min.js"></script>

    <!-- alerts -->
    <script src=" https://cdn.jsdelivr.net/npm/sweetalert2@11.7.31/dist/sweetalert2.all.min.js "></script>
    <link href=" https://cdn.jsdelivr.net/npm/sweetalert2@11.7.31/dist/sweetalert2.min.css " rel="stylesheet">

    {{-- select 2 --}}
   <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
   <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Bootstrap-->
    <link href="{{ asset('bootstrap-5.3.3/css/bootstrap.min.css') }}" rel="stylesheet">

    @livewireStyles
    @yield('css')

</head>

<body class="contenedor">

    <div id="app">
        <div class="loadingio-spinner-dual-ball-7gtmjftscjj loader" id="loader">
            <div class="ldio-dks9e6pqvml">
                <div></div>
                <div></div>
                <div></div>
            </div>
        </div>

    @if($mensaje = Session::get('exito'))
      <script>
        const Toast = Swal.mixin({
        toast: true,position: "top-end",showConfirmButton: false,timer: 5000,timerProgressBar: true,
        didOpen: (toast) => {
          toast.onmouseenter = Swal.stopTimer;
          toast.onmouseleave = Swal.resumeTimer;}});
        Toast.fire({ icon: "success",title: "¡Exito!", text: "Acción completada, realizada correctamente."});
      </script> 
    @elseif($mensaje = Session::get('success'))
       <script>
         const Toast = Swal.mixin({
         toast: true,position: "top-end",showConfirmButton: false,timer: 5000,timerProgressBar: true,
         didOpen: (toast) => {
           toast.onmouseenter = Swal.stopTimer;
           toast.onmouseleave = Swal.resumeTimer;}});
         Toast.fire({ icon: "success",title: "¡Exito!", text: "Acción completada, realizada correctamente."});
       </script> 
    @elseif($mensaje = Session::get('error'))
        <script>
          const Toast = Swal.mixin({
          toast: true,position: "top-end",showConfirmButton: false,timer: 8000,timerProgressBar: true,
          didOpen: (toast) => {
          toast.onmouseenter = Swal.stopTimer;
          toast.onmouseleave = Swal.resumeTimer;}});
          Toast.fire({ icon: "error",title: "Oops...!", text: "Operación no procesada, intente otra vez o en otro momento."});
        </script>
    @elseif($mensaje = Session::get('valida'))
      <script>
          const Toast = Swal.mixin({
          toast: true,position: "top-end",showConfirmButton: false,timer: 8000,timerProgressBar: true,
          didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;}});
          Toast.fire({ icon: "warning",title: "Oops...!", text: "No se ha enviado la información correctamente, asegurase de enviar la información correcta."});
        </script> 
    @elseif($mensaje = Session::get('errorBD'))
      <script>
          const Toast = Swal.mixin({
          toast: true,position: "top-end",showConfirmButton: false,timer: 8000,timerProgressBar: true,
          didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;}});
          Toast.fire({ icon: "warning",title: "Oops...!", text: "Error de procesamiento de datos o conectividad, asegurase de enviar la información correcta o intente en otro momento."});
        </script> 
    @endif

    @if (Session::has('success_msg'))
        <script>
          const Toast = Swal.mixin({
              toast: true,
              position: "top-end",
              showConfirmButton: false,
              timer: 8000,
              timerProgressBar: true,
              didOpen: (toast) => { toast.onmouseenter = Swal.stopTimer;toast.onmouseleave = Swal.resumeTimer;
            }});

            Toast.fire({ 
              icon: "success",
              title: "¡Exito, felicidades!", 
              text: "{{ Session::get('success_msg') }}"
            });
        </script>
    @elseif (Session::has('info_msg'))
        <script>
          const Toast = Swal.mixin({
              toast: true,
              position: "top-end",
              showConfirmButton: false,
              timer: 8000,
              timerProgressBar: true,
              didOpen: (toast) => { toast.onmouseenter = Swal.stopTimer;toast.onmouseleave = Swal.resumeTimer;
            }});

            Toast.fire({ 
              icon: "info",
              title: "¡Información importante!", 
              text: "{{ Session::get('info_msg') }}"
            });
        </script>
    @elseif (Session::has('warning_msg'))
        <script>
          const Toast = Swal.mixin({
              toast: true,
              position: "top-end",
              showConfirmButton: false,
              timer: 8000,
              timerProgressBar: true,
              didOpen: (toast) => { toast.onmouseenter = Swal.stopTimer;toast.onmouseleave = Swal.resumeTimer;
            }});

            Toast.fire({ 
              icon: "warning",
              title: "Oops...!", 
              text: "{{ Session::get('warning_msg') }}"
            });
        </script>
    @elseif (Session::has('error_msg'))
        <script>
          const Toast = Swal.mixin({
              toast: true,
              position: "top-end",
              showConfirmButton: false,
              timer: 8000,
              timerProgressBar: true,
              didOpen: (toast) => { toast.onmouseenter = Swal.stopTimer;toast.onmouseleave = Swal.resumeTimer;
            }});

            Toast.fire({ 
              icon: "error",
              title: "Oops...!", 
              text: "{{ Session::get('error_msg') }}"
            });
        </script>
    @endif

     @if (Session::has('success_msg_large'))
        <script>
            Swal.fire({ 
              icon: "success",
              title: "¡Éxito!", 
              text: "{{ Session::get('success_msg_large') }}"
            });
        </script>

      @elseif (Session::has('error_msg_large'))
        <script>
            Swal.fire({
              icon: "error",
              title: "Error",
              text: @json(Session::get('error_msg_large')),
            });
        </script>
       @elseif (Session::has('info_msg_large'))
        <script>
            Swal.fire({
              icon: "info",
              title: "Información",
               text: "{{ Session::get('info_msg_large') }}",
            });
        </script>
      @elseif (Session::has('warning_msg_large'))
        <script>
            Swal.fire({
              icon: "warning",
              title: "Atención",
              text: "{{ Session::get('warning_msg_large') }}",
            });
        </script>
      @endif



        <div class="wrapper">
            <aside id="sidebar">
                <div class="d-flex">
                    <button class="toggle-btn" type="button" aria-label="Abrir menú">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div class="sidebar-logo">
                        <h4>AUSTIN POWDER ®</h4>
                    </div>
                </div>
                <ul class="sidebar-nav">
                    @if(auth()->user()->tipo != "ext")
                        <li class="sidebar-item">
                            <a href="/home" class="sidebar-link">
                                <i class="fas fa-home icon"></i>
                                <span>Principal</span>
                            </a>
                        </li>
                    @endif

                    @php
                        $varpantallas = $varpantallas ?? collect();
                        $varsubmenus = $varsubmenus ?? collect();
                        $varcontador = 1;
                    @endphp
                    @foreach ($varpantallas as $vis)
                        <li class="sidebar-item">
                            <a href="#" class="sidebar-link collapsed has-dropdown" data-bs-toggle="collapse"
                                data-bs-target="#menu-{{ $varcontador }}" aria-expanded="false"
                                aria-controls="menu-{{ $varcontador }}">
                                <i class ="{{$vis->icon}}"></i>
                                <span>{{ $vis->nombre }}</span>
                            </a>
                            <ul id="menu-{{ $varcontador }}" class="sidebar-dropdown list-unstyled collapse">
                                @foreach ($varsubmenus as $submenus)
                                    @if ($vis->nombre == $submenus->nombre)
                                        <li class="sidebar-item">
                                            @if (in_array(strtolower(trim($vis->nombre)), ['gestion academica', 'gestión academica']) && strtolower(trim($submenus->nom)) === 'alumnos')
                                                <a href="/Gestion_alumnos/alumnos" class="sidebar-link">{{ $submenus->nom }}</a>
                                            @elseif (in_array(strtolower(trim($vis->nombre)), ['gestion academica', 'gestión academica']) && strtolower(trim($submenus->nom)) === 'empresas')
                                                <a href="/Gestion_alumnos/empresas" class="sidebar-link">{{ $submenus->nom }}</a>
                                            @elseif (in_array(strtolower(trim($vis->nombre)), ['gestion academica', 'gestión academica']) && strtolower(trim($submenus->nom)) === 'cursos')
                                                <a href="/Gestion_alumnos/cursos" class="sidebar-link">{{ $submenus->nom }}</a>
                                            @elseif (in_array(strtolower(trim($vis->nombre)), ['gestion academica', 'gestión academica']) && in_array(strtolower(trim($submenus->nom)), ['intranet', 'intranet empresas', 'portal empresas']))
                                                <a href="/intranet" class="sidebar-link">{{ $submenus->nom }}</a>
                                            @else
                                                <a href="/{{ $submenus->descripcion }}" class="sidebar-link">{{ $submenus->nom }}</a>
                                            @endif
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </li>
                        @php($varcontador++)
                    @endforeach

                    @guest
                        <li class="sidebar-item">
                            <a class="sidebar-link"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fa fa-solid fa-arrow-right icon"></i>
                                <span>Salir</span>
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </li>
                    @else
                        <li class="sidebar-item">
                            <a href="#" class="sidebar-link sidebar-link--profile">
                                @if(file_exists(public_path('Images/Perfil/' . Auth::user()->nombre_foto)) && Auth::user()->nombre_foto)
                                    <img src="{{ asset('Images/Perfil/' . Auth::user()->nombre_foto) }}" alt="">
                                @else
                                    <img src="{{ asset('Images/Perfil/0.png') }}" alt="">
                                @endif
                                <span>{{ Auth::user()->name }}</span>
                            </a>
                        </li>
                    @endguest
                </ul>

                <div class="sidebar-footer">
                    <a class="sidebar-link"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        <span>Salir</span>
                    </a>

                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>

            </aside>
            
            <div class="main">
                    @yield('content')
            </div>
        </div>
    </div>

    @livewireScripts
    @stack('scripts')
    <script type="text/javascript">
        const hamBurger = document.querySelector(".toggle-btn");
        const sidebar = document.querySelector("#sidebar");

        function closeOtherSidebarSections(activeTarget) {
            document.querySelectorAll(".sidebar-link.has-dropdown").forEach(function(link) {
                const targetSelector = link.getAttribute("data-bs-target");
                const target = targetSelector ? document.querySelector(targetSelector) : null;
                const isActive = target === activeTarget;

                link.classList.toggle("collapsed", !isActive);
                link.setAttribute("aria-expanded", isActive ? "true" : "false");
            });

            document.querySelectorAll(".sidebar-dropdown.show").forEach(function(menu) {
                if (menu !== activeTarget) {
                    if (typeof bootstrap !== "undefined") {
                        const instance = bootstrap.Collapse.getInstance(menu);
                        if (instance) instance.hide();
                    }
                    menu.classList.remove("show");
                }
            });
        }

        function openSidebarSection(toggleLink) {
            const targetSelector = toggleLink.getAttribute("data-bs-target");
            const target = document.querySelector(targetSelector);
            if (!target) return;

            sidebar.classList.add("expand");
            closeOtherSidebarSections(target);

            if (typeof bootstrap !== "undefined") {
                bootstrap.Collapse.getOrCreateInstance(target, { toggle: false }).show();
            } else {
                target.classList.add("show");
            }
        }

        hamBurger.addEventListener("click", function() {
            sidebar.classList.toggle("expand");
        });

        document.querySelectorAll(".sidebar-link.has-dropdown").forEach(function(link) {
            link.addEventListener("click", function(e) {
                if (!sidebar.classList.contains("expand")) {
                    e.preventDefault();
                    openSidebarSection(link);
                }
            });
        });

        document.querySelectorAll(".sidebar-dropdown").forEach(function(menu) {
            menu.addEventListener("show.bs.collapse", function(e) {
                closeOtherSidebarSections(e.target);
            });
        });

        function mostrarPassword() {
            var cambio = document.getElementById("txtPassword");
            if (cambio.type == "password") {
                cambio.type = "text";
                $('.icon').removeClass('fa fa-eye-slash').addClass('fa fa-eye');
            } else {
                cambio.type = "password";
                $('.icon').removeClass('fa fa-eye').addClass('fa fa-eye-slash');
            }
        }

        $(document).ready(function() {
            //CheckBox mostrar contrase?a
            $('#ShowPassword').click(function() {
                $('#Password').attr('type', $(this).is(':checked') ? 'text' : 'password');
            });


        });

        $('#loading').hide();
        $(".btnsubmit").on("click", function() {
            $('#loading').show();
        });

        $(window).on('load', function() {
            $(".loader").fadeOut("slow");
        });
    </script>

    {{-- mayusculas --}}
    <script>
        $(function() {
            $('input[type=text]').keyup(function() {
                if ($(this).closest('.acciones-config-page').length) {
                    return;
                }
                this.value = this.value.toLocaleUpperCase();
            });
        });
  
        $(function() {
            $('textarea').keyup(function() {
                if ($(this).closest('.acciones-config-page').length) {
                    return;
                }
                this.value = this.value.toLocaleUpperCase();
            });
        });
    </script>

    {{-- datatables --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js" type="text/Javascript"></script>
    <script src="https://cdn.datatables.net/2.2.1/js/dataTables.js" type="text/Javascript"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.js" type="text/Javascript"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.js" type="text/Javascript"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.0/js/dataTables.buttons.js" type="text/Javascript"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.0/js/buttons.dataTables.js" type="text/Javascript"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.0/js/buttons.colVis.min.js" type="text/Javascript"></script>

    <script src="https://cdn.datatables.net/2.2.2/js/dataTables.js" type="text/Javascript"></script>
    <script src="https://cdn.datatables.net/2.2.2/js/dataTables.bootstrap5.js" type="text/Javascript"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.1/js/dataTables.buttons.js" type="text/Javascript"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.1/js/buttons.bootstrap5.js" type="text/Javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js" type="text/Javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js" type="text/Javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js" type="text/Javascript"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.1/js/buttons.html5.min.js" type="text/Javascript"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.1/js/buttons.print.min.js" type="text/Javascript"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.1/js/buttons.colVis.min.js" type="text/Javascript"></script>

    <script type="text/javascript">
        if (typeof DataTable !== 'undefined' && DataTable.ext) {
            DataTable.ext.errMode = 'none';
        }
        if (typeof jQuery !== 'undefined' && jQuery.fn.dataTable && jQuery.fn.dataTable.ext) {
            jQuery.fn.dataTable.ext.errMode = 'none';
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            $('.select2').select2({})
        })
    </script>

    <!-- Bootstrap-->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    {{-- <script src="{{ asset('bootstrap-5.3.3/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('bootstrap-5.3.3/js/bootstrap.min.js') }}"></script> --}}
    @yield('js')

    <script>
        // Evitar conflictos de variables globales
        if (typeof window.livewireStarted === 'undefined') {
            window.livewireStarted = true;
        }
        
        // Reiniciar los componentes Livewire cuando hay errores CSRF (Livewire v2)
        window.addEventListener('load', function() {
            if (typeof Livewire !== 'undefined') {
                // En Livewire v2, usamos window.addEventListener para errores
                window.addEventListener('livewire:load', function() {
                    // Manejar errores CSRF
                    document.addEventListener('livewire:error', function(event) {
                        if (event.detail && event.detail.status === 419) {
                            // CSRF token mismatch - recargar la p?gina
                            location.reload();
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>
