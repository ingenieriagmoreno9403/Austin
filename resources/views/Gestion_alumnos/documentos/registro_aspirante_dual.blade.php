@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

    <div class="container-fluid acciones-config-page">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fa-solid fa-file-signature"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Registro alumno dual</h2>
                            <p class="text-muted mb-0">Cuestionario de registro del aspirante al programa dual.</p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <a class="btn btn-baseColor-light fs-7 mb-2" href="{{ route('documentos_alumno') }}">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-body rounded-2 p-4">
            <h5 class="mb-3">Registro de aspirante dual</h5>
            <p class="text-muted small mb-3">
                Instrucciones: responde a las preguntas desarrollando tu respuesta de manera breve.
            </p>

            <form class="g-3 form">
                <div class="row">
                    <div class="col-md-8 col-12 mt-2">
                        <label class="form-label">Centro educativo</label>
                        <input type="text" class="form-control text" placeholder="Centro educativo">
                    </div>
                    <div class="col-md-4 col-12 mt-2">
                        <label class="form-label">Fecha</label>
                        <input type="date" class="form-control text">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8 col-12 mt-2">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-control text" placeholder="Nombre completo">
                    </div>
                    <div class="col-md-4 col-12 mt-2">
                        <label class="form-label">Edad</label>
                        <input type="number" class="form-control text" min="1" step="1" placeholder="Edad">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8 col-12 mt-2">
                        <label class="form-label">Domicilio</label>
                        <input type="text" class="form-control text" placeholder="Domicilio">
                    </div>
                    <div class="col-md-4 col-12 mt-2">
                        <label class="form-label">Teléfono</label>
                        <input type="text" class="form-control text" placeholder="Teléfono">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8 col-12 mt-2">
                        <label class="form-label">Carrera</label>
                        <input type="text" class="form-control text" placeholder="Carrera">
                    </div>
                    <div class="col-md-4 col-12 mt-2">
                        <label class="form-label">Matrícula</label>
                        <input type="text" class="form-control text" placeholder="Matrícula">
                    </div>
                </div>

                <div class="row mt-1">
                    <div class="col-md-8 col-12 mt-2">
                        <label class="form-label">Nombre de padre</label>
                        <input type="text" class="form-control text" placeholder="Nombre del padre">
                    </div>
                    <div class="col-md-4 col-12 mt-2">
                        <label class="form-label">Celular</label>
                        <input type="text" class="form-control text" placeholder="Celular">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8 col-12 mt-2">
                        <label class="form-label">Nombre de madre</label>
                        <input type="text" class="form-control text" placeholder="Nombre de la madre">
                    </div>
                    <div class="col-md-4 col-12 mt-2">
                        <label class="form-label">Celular</label>
                        <input type="text" class="form-control text" placeholder="Celular">
                    </div>
                </div>

                @php
                    $preguntas = [
                        '1. ¿Conoces el programa de educación dual? Descríbelo.',
                        '2. ¿Por qué deseas integrarte al programa?',
                        '3. ¿Con quién vives?',
                        '4. ¿Cuántos hermanos tienes?',
                        '5. Describe tus habilidades.',
                        '6. ¿Dónde te ves en 5 años?',
                        '7. ¿Te consideras una persona creativa?',
                        '8. ¿Qué haces en tu tiempo libre?',
                        '9. ¿Qué tipos de libros, música o películas te gustan?',
                        '10. ¿Qué opinas de la capacitación extracurricular?',
                        '11. Menciona algo que te apasione.',
                        '12. Tres adjetivos que te describen.',
                        '13. ¿Cuál es tu opinión de la familia?',
                        '14. Nombra tu pasatiempo favorito.',
                        '15. ¿Cuál es tu reacción cuando algo te molesta?',
                        '16. ¿Te consideras una persona inteligente?',
                        '17. ¿Te aburres con facilidad?',
                        '18. ¿Te gusta trabajar con otras personas?',
                        '19. ¿Qué tipo de personas te desagradan?',
                        '20. ¿Tienes dificultad al comunicarte?',
                        '21. ¿Qué opinas de los códigos de vestimenta?',
                        '22. ¿Eres una persona innovadora?',
                        '23. ¿Tienes algún problema de salud?',
                        '24. ¿Tomas algún medicamento? ¿Cuáles?',
                        '25. ¿Tienes alergias? ¿A qué?',
                    ];
                @endphp

                <div class="mt-4">
                    <h6 class="mb-2">Cuestionario</h6>
                    <div class="row">
                        @foreach ($preguntas as $idx => $pregunta)
                            <div class="col-12 mt-2">
                                <label class="form-label">{{ $pregunta }}</label>
                                <textarea class="form-control text" rows="{{ in_array($idx + 1, [3, 4, 7, 12, 16, 17, 18, 20, 24, 25]) ? 2 : 3 }}" placeholder="Escribe tu respuesta breve..."></textarea>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="row justify-content-end mt-4">
                    <div class="col-md-3 col-12 d-grid">
                        <button type="button" class="btn btn-baseColor">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar (diseño)
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
