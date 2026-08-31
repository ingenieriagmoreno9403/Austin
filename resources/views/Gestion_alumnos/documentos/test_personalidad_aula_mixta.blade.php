@extends('layouts.app')
@section('content')
    @include('Gestion_alumnos.partials.sweet_alerts')

    <link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

    @php
        $apiAlumnosBase = url('/Gestion_alumnos/api/alumnos');
        $apiTestsAulaMixtaBase = url('/Gestion_alumnos/api/tests-personalidad-aula-mixta');
    @endphp
    <div class="container-fluid acciones-config-page">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fa-solid fa-brain"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Test de personalidad aula mixta</h2>
                            <p class="text-muted mb-0">Evaluación orientativa del estado socioemocional del alumno (no diagnóstico clínico).</p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <a href="javascript:history.back()" class="btn btn-baseColor-light fs-7 mb-2">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card rounded-4 shadow bg-body rounded-2 p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h5 class="m-0">TEST DE PERSONALIDAD AULA MIXTA COPARMEX LAGUNA</h5>
                <button type="button" class="btn btn-baseColor" id="btnGuardarTestDisenio">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar test
                </button>
            </div>

            <p class="small text-muted mb-3">
                Versión cargada desde el archivo `.docx` compartido (80 reactivos). Puedes ajustar los rangos de interpretación debajo.
            </p>

            <form id="formTestAulaMixta" class="g-3 form">
                <div class="row">
                    <div class="col-md-4 col-12 mt-2">
                        <label class="form-label">Alumno</label>
                        <select id="test_alumno_id" name="alumno_id" class="form-select" required>
                            <option value="">Selecciona alumno...</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-12 mt-2">
                        <label class="form-label">Matrícula</label>
                        <input type="text" class="form-control text" id="test_matricula_view" placeholder="Matrícula" readonly>
                    </div>
                    <div class="col-md-3 col-12 mt-2">
                        <label class="form-label">Grupo</label>
                        <input type="text" class="form-control text" name="grupo" placeholder="Grupo">
                    </div>
                    <div class="col-md-3 col-12 mt-2">
                        <label class="form-label">Fecha</label>
                        <input type="date" class="form-control text" name="fecha_aplicacion">
                    </div>
                </div>

                <hr class="my-4">
                <h6 class="mb-2">Instrucciones para el alumno</h6>
                <p class="small text-muted mb-2">
                    Responde cada reactivo marcando <strong>Sí</strong> o <strong>No</strong>.
                </p>

                @php
                    $reactivos = [
                        'En general soy optimista y alegre.',
                        'En las fiestas me divierto.',
                        'Soy más sensible que la mayoría.',
                        'Siempre soy puntual en las horas de llegada.',
                        'Me resfrío con mucha frecuencia.',
                        'Tomo mis decisiones sin consultar.',
                        'Siempre termino lo que empiezo.',
                        'Mantengo mi pieza ordenada.',
                        'Consulto a menudo textos, diccionarios y enciclopedias.',
                        'Me aburre mucho la rutina.',
                        'Mi estado de ánimo es muy constante.',
                        'Participo bastante en las reuniones.',
                        'Me conmuevo con facilidad.',
                        'Cumplo mis promesas aunque me cueste.',
                        'Soy bastante alérgico.',
                        'Cuando discuto casi siempre tengo razón.',
                        'Rara vez dejo algo a medio hacer.',
                        'Siempre sé donde están mis cosas.',
                        'Estudio o leo sobre cosas que me interesan.',
                        'Cambio de gustos con frecuencia.',
                        'Puedo dormir bien aunque haya problemas.',
                        'Tengo muchos amigos.',
                        'Me disgustan los juegos violentos.',
                        'Si no puedo cumplir algo doy explicaciones.',
                        'Me canso muy pronto.',
                        'Me gusta viajar solo y no echo de menos.',
                        'Me agrada trabajar o estudiar con energía.',
                        'Doblo mi ropa cuando me la saco.',
                        'Me agrada investigar.',
                        'Me agrada mucho viajar.',
                        'Me llevo bien con casi toda la gente.',
                        'Me es fácil iniciar una conversación.',
                        'Ciertos cuadros o dibujos me impresionan.',
                        'Me preocupo de hacer bien las cosas.',
                        'Me afectan los viajes largos.',
                        'Tengo puntos de vista muy diferentes.',
                        'Lucho a fondo por conseguir lo que me propongo.',
                        'Después de usar algo, lo guardo en su lugar.',
                        'Me hago muchas preguntas sin respuestas.',
                        'Mis tareas cambian a menudo.',
                        'Me concentro fácilmente.',
                        'Hay personas superiores a mí.',
                        'Prefiero escuchar un coro antes que una marcha.',
                        'Llego a casa a la hora indicada.',
                        'Me desanimo fácilmente.',
                        'A veces estoy en desacuerdo con el profesor.',
                        'Cuando algo me sale mal lo hago de nuevo.',
                        'Dejo mis cosas listas en la noche.',
                        'Trato de saber a fondo lo que interesa.',
                        'Realizo muchas actividades diferentes.',
                        'Si algo me disgusta lo digo.',
                        'Prefiero que alguien me enseñe a leer yo mismo.',
                        'Ciertas melodías cambian mi estado de ánimo.',
                        'Siempre cumplo con mis deberes.',
                        'El trabajo físico me afecta.',
                        'Tengo bastante paciencia, no soy impulsivo.',
                        'Cuando empiezo a leer un libro lo termino.',
                        'Hago las cosas a tiempo y no a última hora.',
                        'Me gustan mucho ver ciertos documentales.',
                        'Cambio de amigos con frecuencia.',
                        'A veces lloro por cosas insignificantes.',
                        'En general confío en la gente.',
                        'Me importa el qué dirán.',
                        'Muchas veces me sacrifico con tal de terminar algo.',
                        'Sufro malestares difusos con frecuencia.',
                        'Sigo más por mis propias ideas.',
                        'Puedo repetir algo mil veces sin enojarme.',
                        'Trazo un plan antes de iniciar algo.',
                        'Cuando empiezo un libro siempre lo termino.',
                        'Pruebo diferentes métodos para hacer lo mismo.',
                        'Me hace bien confiar en mis semejantes.',
                        'Tengo bastantes amigos de otro sexo.',
                        'Muchas cosas me inquietan.',
                        'Reúno todo antes de empezar a hacer algo.',
                        'Me canso al caminar.',
                        'Hago lo más importante primero.',
                        'A veces desarmo objetos para ver cómo funcionan.',
                        'Hago las cosas con tiempo.',
                        'Me gusta ayudar a resolver problemas de otros.',
                        'Me agradaría vivir en diversos lugares.',
                    ];
                @endphp

                <div class="table-responsive mt-2">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr class="text-tr">
                                <th style="min-width: 24rem;">Reactivo</th>
                                <th class="text-center">Sí (1)</th>
                                <th class="text-center">No (0)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reactivos as $idx => $reactivo)
                                <tr>
                                    <td>{{ $idx + 1 }}. {{ $reactivo }}</td>
                                    <td class="text-center">
                                        <input type="radio" name="reactivo_{{ $idx + 1 }}" value="1" class="form-check-input puntaje-reactivo" required>
                                    </td>
                                    <td class="text-center">
                                        <input type="radio" name="reactivo_{{ $idx + 1 }}" value="0" class="form-check-input puntaje-reactivo" required>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <hr class="my-4">
                <h6 class="mb-2">Rúbrica (según formato)</h6>
                <p class="small text-muted mb-2">
                    - Se suma <strong>SI</strong> en todas las columnas, excepto columna <strong>V (CFS)</strong> donde se suma <strong>NO</strong>.<br>
                    - Rasgo positivo cuando la columna es <strong>mayor a 5</strong> (6, 7, 8).<br>
                    - Baremo por columna: 6-8 suficiente, 4-5 necesita fortalecer, 0-3 rasgo contrario.
                </p>
                <div class="row">
                    <div class="col-md-4 col-12 mt-2">
                        <label class="form-label">Bajo riesgo (mín-máx)</label>
                        <input type="text" class="form-control text" id="rangoBajo" value="0-26">
                    </div>
                    <div class="col-md-4 col-12 mt-2">
                        <label class="form-label">Riesgo medio (mín-máx)</label>
                        <input type="text" class="form-control text" id="rangoMedio" value="27-53">
                    </div>
                    <div class="col-md-4 col-12 mt-2">
                        <label class="form-label">Riesgo alto (mín-máx)</label>
                        <input type="text" class="form-control text" id="rangoAlto" value="54-80">
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-4 col-12">
                        <div class="border rounded-2 p-3 bg-light">
                            <div class="small text-muted">Puntaje total (SI)</div>
                            <div class="h4 m-0" id="puntajeTotal">0</div>
                        </div>
                    </div>
                    <div class="col-md-8 col-12">
                        <div class="border rounded-2 p-3 bg-light">
                            <div class="small text-muted">Interpretación</div>
                            <div class="h5 m-0" id="interpretacionResultado">Pendiente de responder</div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-12">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr class="text-tr">
                                        <th>Columna</th>
                                        <th>Rasgo</th>
                                        <th>Valor</th>
                                        <th>Baremo</th>
                                        <th>Positivo (&gt;5)</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyResultadosColumnas"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const apiAlumnos = @json($apiAlumnosBase);
            const apiTestsAulaMixta = @json($apiTestsAulaMixtaBase);
            const form = document.getElementById('formTestAulaMixta');
            const puntajeTotal = document.getElementById('puntajeTotal');
            const interpretacionResultado = document.getElementById('interpretacionResultado');
            const rangoBajo = document.getElementById('rangoBajo');
            const rangoMedio = document.getElementById('rangoMedio');
            const rangoAlto = document.getElementById('rangoAlto');
            const btnGuardarTestDisenio = document.getElementById('btnGuardarTestDisenio');
            const testAlumnoId = document.getElementById('test_alumno_id');
            const testMatriculaView = document.getElementById('test_matricula_view');
            const tbodyResultadosColumnas = document.getElementById('tbodyResultadosColumnas');
            let alumnos = [];
            let resultadosColumnasActual = [];

            const columnasRasgos = [
                { idx: 1, clave: 'E', nombre: 'Estabilidad emocional' },
                { idx: 2, clave: 'SO', nombre: 'Sociabilidad' },
                { idx: 3, clave: 'SE', nombre: 'Sensibilidad' },
                { idx: 4, clave: 'R', nombre: 'Responsabilidad' },
                { idx: 5, clave: 'CFS', nombre: 'Condiciones físicas de salud' },
                { idx: 6, clave: 'I', nombre: 'Independencia' },
                { idx: 7, clave: 'P', nombre: 'Persistencia' },
                { idx: 8, clave: 'O', nombre: 'Orden' },
                { idx: 9, clave: 'C', nombre: 'Curiosidad' },
                { idx: 10, clave: 'CA', nombre: 'Cambio' },
            ];

            const parseJsonResponse = async function (r) {
                const text = await r.text();
                if (!text) return {};
                try { return JSON.parse(text); } catch (e) { return {}; }
            };

            const getCsrfToken = function () {
                const m = document.querySelector('meta[name="csrf-token"]');
                return m ? m.getAttribute('content') : '';
            };

            const parseRange = function (txt) {
                const m = String(txt || '').trim().match(/^(\d+)\s*-\s*(\d+)$/);
                if (!m) return null;
                const min = parseInt(m[1], 10);
                const max = parseInt(m[2], 10);
                if (Number.isNaN(min) || Number.isNaN(max)) return null;
                return { min: min, max: max };
            };

            const calcular = function () {
                let total = 0;
                const radios = form.querySelectorAll('.puntaje-reactivo:checked');
                radios.forEach(function (r) { total += parseInt(r.value, 10) || 0; });
                puntajeTotal.textContent = String(total);

                const rb = parseRange(rangoBajo.value);
                const rm = parseRange(rangoMedio.value);
                const ra = parseRange(rangoAlto.value);
                let txt = 'Pendiente de responder';
                if (radios.length > 0) {
                    txt = 'Sin rango configurado';
                    if (rb && total >= rb.min && total <= rb.max) txt = 'Bajo riesgo socioemocional';
                    else if (rm && total >= rm.min && total <= rm.max) txt = 'Riesgo medio socioemocional';
                    else if (ra && total >= ra.min && total <= ra.max) txt = 'Riesgo alto socioemocional';
                }
                interpretacionResultado.textContent = txt;

                const respuestas = [];
                for (let i = 1; i <= 80; i++) {
                    const sel = form.querySelector('input[name="reactivo_' + i + '"]:checked');
                    respuestas.push(sel ? (parseInt(sel.value, 10) || 0) : null);
                }

                resultadosColumnasActual = columnasRasgos.map(function (col) {
                    let valor = 0;
                    for (let i = col.idx; i <= 80; i += 10) {
                        const resp = respuestas[i - 1];
                        if (resp === null) continue;
                        if (col.idx === 5) {
                            // CFS: suma NO (0)
                            if (resp === 0) valor += 1;
                        } else {
                            // Resto columnas: suma SI (1)
                            if (resp === 1) valor += 1;
                        }
                    }

                    let baremo = 'Rasgo contrario (0-3)';
                    if (valor >= 6) baremo = 'Suficiente (6-8)';
                    else if (valor >= 4) baremo = 'Necesita fortalecer (4-5)';

                    return {
                        columna: col.clave,
                        rasgo: col.nombre,
                        valor: valor,
                        baremo: baremo,
                        es_positivo: valor > 5,
                    };
                });

                if (tbodyResultadosColumnas) {
                    tbodyResultadosColumnas.innerHTML = '';
                    resultadosColumnasActual.forEach(function (r) {
                        const tr = document.createElement('tr');
                        tr.innerHTML =
                            '<td><strong>' + r.columna + '</strong></td>' +
                            '<td>' + r.rasgo + '</td>' +
                            '<td>' + String(r.valor) + '</td>' +
                            '<td>' + r.baremo + '</td>' +
                            '<td>' + (r.es_positivo ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-secondary">No</span>') + '</td>';
                        tbodyResultadosColumnas.appendChild(tr);
                    });
                }
            };

            form.addEventListener('change', calcular);
            rangoBajo.addEventListener('input', calcular);
            rangoMedio.addEventListener('input', calcular);
            rangoAlto.addEventListener('input', calcular);

            const cargarAlumnos = async function () {
                try {
                    const r = await fetch(apiAlumnos, { headers: { 'Accept': 'application/json' } });
                    const j = await parseJsonResponse(r);
                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudieron cargar alumnos.');
                        return;
                    }
                    alumnos = Array.isArray(j.data) ? j.data : [];
                    alumnos.forEach(function (a) {
                        const nombre = [a.nombres || '', a.apellido_paterno || '', a.apellido_materno || ''].join(' ').replace(/\s+/g, ' ').trim();
                        const o = document.createElement('option');
                        o.value = String(a.id);
                        o.textContent = (a.numero_matricula || 'S/M') + ' - ' + (nombre || 'Alumno');
                        testAlumnoId.appendChild(o);
                    });

                    const params = new URLSearchParams(window.location.search);
                    const preselect = params.get('alumno_id');
                    if (preselect && alumnos.some(function (a) { return String(a.id) === preselect; })) {
                        testAlumnoId.value = preselect;
                        testAlumnoId.dispatchEvent(new Event('change'));
                    }
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al cargar alumnos.');
                }
            };

            const limpiarReactivos = function () {
                for (let i = 1; i <= 80; i++) {
                    const radios = form.querySelectorAll('input[name="reactivo_' + i + '"]');
                    radios.forEach(function (r) { r.checked = false; });
                }
                form.querySelector('[name="grupo"]').value = '';
                form.querySelector('[name="fecha_aplicacion"]').value = '';
                calcular();
            };

            const cargarTestAlumno = async function (alumnoId) {
                limpiarReactivos();
                if (!alumnoId) return;

                try {
                    const r = await fetch(apiTestsAulaMixta + '/alumno/' + encodeURIComponent(alumnoId), {
                        headers: { 'Accept': 'application/json' },
                    });
                    const j = await parseJsonResponse(r);
                    if (!r.ok || !j.data) return;

                    const test = j.data;
                    if (test.grupo) form.querySelector('[name="grupo"]').value = test.grupo;
                    if (test.fecha_aplicacion) form.querySelector('[name="fecha_aplicacion"]').value = String(test.fecha_aplicacion).substring(0, 10);
                    if (test.rango_bajo && rangoBajo) rangoBajo.value = test.rango_bajo;
                    if (test.rango_medio && rangoMedio) rangoMedio.value = test.rango_medio;
                    if (test.rango_alto && rangoAlto) rangoAlto.value = test.rango_alto;

                    if (Array.isArray(test.respuestas)) {
                        test.respuestas.forEach(function (resp) {
                            const num = resp.reactivo_numero;
                            const val = String(resp.respuesta_valor);
                            const radio = form.querySelector('input[name="reactivo_' + num + '"][value="' + val + '"]');
                            if (radio) radio.checked = true;
                        });
                    }

                    calcular();
                } catch (err) {
                    console.error(err);
                }
            };

            if (testAlumnoId) {
                testAlumnoId.addEventListener('change', function () {
                    const id = String(testAlumnoId.value || '').trim();
                    const a = alumnos.find(function (x) { return String(x.id) === id; });
                    testMatriculaView.value = a ? String(a.numero_matricula || '') : '';
                    void cargarTestAlumno(id);
                });
            }

            if (btnGuardarTestDisenio) {
                btnGuardarTestDisenio.addEventListener('click', function () {
                    const alumnoId = testAlumnoId ? String(testAlumnoId.value || '').trim() : '';
                    if (!alumnoId) {
                        window.alert('Selecciona un alumno.');
                        return;
                    }

                    const respuestas = [];
                    for (let i = 1; i <= 80; i++) {
                        const sel = form.querySelector('input[name="reactivo_' + i + '"]:checked');
                        if (!sel) {
                            window.alert('Falta responder el reactivo ' + i + '.');
                            return;
                        }
                        respuestas.push(parseInt(sel.value, 10) || 0);
                    }

                    const payload = {
                        alumno_id: parseInt(alumnoId, 10),
                        grupo: (form.querySelector('[name="grupo"]').value || '').trim(),
                        fecha_aplicacion: (form.querySelector('[name="fecha_aplicacion"]').value || '').trim(),
                        puntaje_total: parseInt(puntajeTotal.textContent || '0', 10) || 0,
                        interpretacion: (interpretacionResultado.textContent || '').trim(),
                        resultados_columnas: resultadosColumnasActual,
                        rango_bajo: (rangoBajo.value || '').trim(),
                        rango_medio: (rangoMedio.value || '').trim(),
                        rango_alto: (rangoAlto.value || '').trim(),
                        respuestas: respuestas,
                    };

                    fetch(apiTestsAulaMixta, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(payload),
                    }).then(async function (r) {
                        const j = await parseJsonResponse(r);
                        if (!r.ok) {
                            let msg = (j && j.message) ? j.message : 'No se pudo guardar el test.';
                            if (j && j.errors) {
                                msg = Object.values(j.errors).flat().join('\n');
                            }
                            window.alert(msg);
                            return;
                        }
                        window.alert(j.message || 'Test guardado correctamente.');
                    }).catch(function (err) {
                        console.error(err);
                        window.alert('Error de red al guardar el test.');
                    });
                });
            }

            void cargarAlumnos();
            calcular();
        });
    </script>
@endsection
