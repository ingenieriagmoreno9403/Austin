@extends('layouts.appfront')
@section('content')
<div class="login-wrap">
	<div class="login-html">
        <div class="">
            <div class="positon-absolute mb-3 text-center">
                <img class="animate___animated animate___flipInY" src="{{ asset('Images/IOHISA.png') }}" width="100px" alt=""> 
                <br><br>
            </div>

            <input id="tab-1" type="radio" name="tab" class="sign-in" checked><label for="tab-1" class="tab">Ingresar</label>
            <input id="tab-2" type="radio" name="tab" class="sign-up"><label for="tab-2" class="tab">Registrarse</label>

            <div class="login-form">
                <form method="POST" action="{{ route('login') }}" class="login-form">
                    @csrf
                    <div class="sign-in-htm">
                        <div class="group">
                            <label for="user" class="label">Correo electrónico</label>
                            <input id="user1" type="email" style="width:100%;" class="input @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="current-email" required>

                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <div class="group">
                            <label for="password" class="label">Contraseña</label>
                            <div class="row p-2 pt-0">
                                <input class="col-md-11 col-10 input  @error('password') is-invalid @enderror" ID="txtPassword" id="password" type="password"  name="password" required autocomplete="current-password" required>
                                
                                <div class="col pt-3 p-1">
                                    <a class="" id="show_password" type="button" onclick="mostrarPassword()"> <span class="fa fa-eye-slash icon"></span> </a>
                                </div>
                            </div>
                    
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="group">
                            <input id="check" type="checkbox" class="check" checked>
                            <label for="check"><span class="icon"></span> Mantener sesión iniciada</label>
                        </div>

                        <div class="group">
                            <input type="submit" style="width:100%;" class="button push" value="Ingresar">
                        </div>

                        <div class="hr"></div>
                        <div class="foot-lnk">
                            <a href="#forgot">¿Olvidaste tu contraseña?</a>
                        </div>
                    </div>
                </form>


                <div class="sign-up-htm">
                    <div class="group">
                        <label for="user" class="label">Usuario</label>
                        <input id="user2" type="text" class="input">
                    </div>
                    <div class="group">
                        <label for="pass" class="label">Contraseña</label>
                        <input id="pass" type="password" class="input" data-type="password">
                    </div>
                    <div class="group">
                        <label for="pass" class="label">Repetir contraseña</label>
                        <input id="pass1" type="password" class="input" data-type="password">
                    </div>
                    <div class="group">
                        <label for="pass" class="label">Correo electrónico</label>
                        <input id="pass2" type="text" class="input">
                    </div>
                    <div class="group">
                        <input type="submit" class="button" value="Registrarse">
                    </div>
                    <div class="hr"></div>
                    <div class="foot-lnk">
                        <label for="tab-1">¿Ya tienes cuenta?</label>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>
@endsection