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
            </div>

            <p class="small text-muted mb-3">
                Versión cargada desde el archivo <code>.docx</code> compartido (80 reactivos).
            </p>

            <form id="formTestAulaMixtaSimple" class="g-3 form">
                <div class="row">
                    <div class="col-md-4 col-12 mt-2">
                        <label class="form-label">Alumno</label>
                        <select id="test_alumno_id" name="alumno_id" class="form-select" required disabled>
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
                        <input type="date" class="form-control text" name="fecha_aplicacion" readonly>
                    </div>
                </div>

            </form>
        </div>

        {{-- Sección colapsable: Resultados del test --}}
        <div class="card rounded-4 shadowbg-body rounded-2 p-4 mt-4" id="seccionResultadosTest" style="display: none;">
            <button class="btn btn-baseColor fs-7 w-100 d-flex align-items-center justify-content-between mb-0"
                    type="button" data-bs-toggle="collapse" data-bs-target="#collapseResultadosTest"
                    aria-expanded="false" aria-controls="collapseResultadosTest">
                <span><i class="fa-solid fa-chart-column"></i> Resultados del test</span>
                <i class="fa-solid fa-chevron-down" id="iconCollapseResultados" style="transition: transform 0.3s ease;"></i>
            </button>
            <div class="collapse" id="collapseResultadosTest">
                <div class="pt-4">
                    <h6 class="mb-2">Rúbrica (según formato)</h6>
                    <p class="small text-muted mb-2">
                        - Se suma <strong>SI</strong> en todas las columnas, excepto columna <strong>V (CFS)</strong> donde se suma <strong>NO</strong>.<br>
                        - Rasgo positivo cuando la columna es <strong>mayor a 5</strong> (6, 7, 8).<br>
                        - Baremo por columna: 6-8 suficiente, 4-5 necesita fortalecer, 0-3 rasgo contrario.
                    </p>
                    <div class="row">
                        <div class="col-md-4 col-12 mt-2">
                            <label class="form-label">Bajo riesgo (mín-máx)</label>
                            <input type="text" class="form-control text" id="rangoBajo" value="0-26" readonly>
                        </div>
                        <div class="col-md-4 col-12 mt-2">
                            <label class="form-label">Riesgo medio (mín-máx)</label>
                            <input type="text" class="form-control text" id="rangoMedio" value="27-53" readonly>
                        </div>
                        <div class="col-md-4 col-12 mt-2">
                            <label class="form-label">Riesgo alto (mín-máx)</label>
                            <input type="text" class="form-control text" id="rangoAlto" value="54-80" readonly>
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
                </div>
            </div>
        </div>

        {{-- Sección colapsable: Reactivos del test --}}
        <div class="card rounded-4 shadow bg-body rounded-2 p-4 mt-4">
            <button class="btn btn-baseColor fs-7 w-100 d-flex align-items-center justify-content-between mb-0"
                    type="button" data-bs-toggle="collapse" data-bs-target="#collapseReactivosTest"
                    aria-expanded="false" aria-controls="collapseReactivosTest" id="btnToggleReactivos">
                <span><i class="fa-solid fa-list-check"></i> Reactivos del test (80 preguntas)</span>
                <i class="fa-solid fa-chevron-down" id="iconCollapseReactivos" style="transition: transform 0.3s ease;"></i>
            </button>
            <div class="collapse" id="collapseReactivosTest">
                <div class="pt-4">
                <form id="formTestReactivos" class="g-3 form">
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
                </form>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-3">
            <button type="button" class="btn btn-baseColor" id="btnGuardarTestSimple">
                <i class="fa-solid fa-floppy-disk"></i> Guardar test
            </button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const apiAlumnos = @json($apiAlumnosBase);
            const apiTestsAulaMixta = @json($apiTestsAulaMixtaBase);
            const formDatos = document.getElementById('formTestAulaMixtaSimple');
            const form = document.getElementById('formTestReactivos');
            const btnGuardarTestSimple = document.getElementById('btnGuardarTestSimple');
            const testAlumnoId = document.getElementById('test_alumno_id');
            const testMatriculaView = document.getElementById('test_matricula_view');
            let alumnos = [];

            const parseJsonResponse = async function (r) {
                const text = await r.text();
                if (!text) return {};
                try { return JSON.parse(text); } catch (e) { return {}; }
            };

            const getCsrfToken = function () {
                const m = document.querySelector('meta[name="csrf-token"]');
                return m ? m.getAttribute('content') : '';
            };

            const fechaInput = formDatos.querySelector('[name="fecha_aplicacion"]');
            if (fechaInput) {
                const hoy = new Date();
                const yyyy = hoy.getFullYear();
                const mm = String(hoy.getMonth() + 1).padStart(2, '0');
                const dd = String(hoy.getDate()).padStart(2, '0');
                fechaInput.value = yyyy + '-' + mm + '-' + dd;
            }

            const seccionResultadosTest = document.getElementById('seccionResultadosTest');
            const puntajeTotalEl = document.getElementById('puntajeTotal');
            const interpretacionResultadoEl = document.getElementById('interpretacionResultado');
            const rangoBajoEl = document.getElementById('rangoBajo');
            const rangoMedioEl = document.getElementById('rangoMedio');
            const rangoAltoEl = document.getElementById('rangoAlto');
            const tbodyResultadosColumnas = document.getElementById('tbodyResultadosColumnas');
            const iconCollapseResultados = document.getElementById('iconCollapseResultados');
            const collapseResultadosEl = document.getElementById('collapseResultadosTest');

            if (collapseResultadosEl) {
                collapseResultadosEl.addEventListener('shown.bs.collapse', function () {
                    if (iconCollapseResultados) iconCollapseResultados.style.transform = 'rotate(180deg)';
                });
                collapseResultadosEl.addEventListener('hidden.bs.collapse', function () {
                    if (iconCollapseResultados) iconCollapseResultados.style.transform = 'rotate(0deg)';
                });
            }

            const collapseReactivosEl = document.getElementById('collapseReactivosTest');
            const iconCollapseReactivos = document.getElementById('iconCollapseReactivos');
            if (collapseReactivosEl) {
                collapseReactivosEl.addEventListener('shown.bs.collapse', function () {
                    if (iconCollapseReactivos) iconCollapseReactivos.style.transform = 'rotate(180deg)';
                });
                collapseReactivosEl.addEventListener('hidden.bs.collapse', function () {
                    if (iconCollapseReactivos) iconCollapseReactivos.style.transform = 'rotate(0deg)';
                });
            }

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

            const calcularResultados = function () {
                var respuestas = [];
                for (var i = 1; i <= 80; i++) {
                    var sel = form.querySelector('input[name="reactivo_' + i + '"]:checked');
                    respuestas.push(sel ? (parseInt(sel.value, 10) || 0) : null);
                }

                var total = 0;
                respuestas.forEach(function (v) { if (v !== null) total += v; });
                if (puntajeTotalEl) puntajeTotalEl.textContent = String(total);

                var txt = 'Pendiente de responder';
                var contestados = respuestas.filter(function (v) { return v !== null; }).length;
                if (contestados > 0) {
                    if (total >= 0 && total <= 26) txt = 'Bajo riesgo socioemocional';
                    else if (total >= 27 && total <= 53) txt = 'Riesgo medio socioemocional';
                    else if (total >= 54 && total <= 80) txt = 'Riesgo alto socioemocional';
                }
                if (interpretacionResultadoEl) interpretacionResultadoEl.textContent = txt;

                if (tbodyResultadosColumnas) {
                    tbodyResultadosColumnas.innerHTML = '';
                    columnasRasgos.forEach(function (col) {
                        var valor = 0;
                        for (var j = col.idx; j <= 80; j += 10) {
                            var r = respuestas[j - 1];
                            if (r === null) continue;
                            if (col.idx === 5) { if (r === 0) valor += 1; }
                            else { if (r === 1) valor += 1; }
                        }
                        var baremo = 'Rasgo contrario (0-3)';
                        if (valor >= 6) baremo = 'Suficiente (6-8)';
                        else if (valor >= 4) baremo = 'Necesita fortalecer (4-5)';

                        var tr = document.createElement('tr');
                        tr.innerHTML =
                            '<td><strong>' + col.clave + '</strong></td>' +
                            '<td>' + col.nombre + '</td>' +
                            '<td>' + String(valor) + '</td>' +
                            '<td>' + baremo + '</td>' +
                            '<td>' + (valor > 5 ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-secondary">No</span>') + '</td>';
                        tbodyResultadosColumnas.appendChild(tr);
                    });
                }
            };

            const cargarTestAlumno = async function (alumnoId) {
                if (!alumnoId) return;
                try {
                    var r = await fetch(apiTestsAulaMixta + '/alumno/' + encodeURIComponent(alumnoId), {
                        headers: { 'Accept': 'application/json' },
                    });
                    var j = await parseJsonResponse(r);
                    if (!r.ok || !j.data) return;

                    var test = j.data;
                    if (test.grupo) formDatos.querySelector('[name="grupo"]').value = test.grupo;
                    if (test.fecha_aplicacion) formDatos.querySelector('[name="fecha_aplicacion"]').value = String(test.fecha_aplicacion).substring(0, 10);
                    if (test.rango_bajo && rangoBajoEl) rangoBajoEl.value = test.rango_bajo;
                    if (test.rango_medio && rangoMedioEl) rangoMedioEl.value = test.rango_medio;
                    if (test.rango_alto && rangoAltoEl) rangoAltoEl.value = test.rango_alto;

                    if (Array.isArray(test.respuestas)) {
                        test.respuestas.forEach(function (resp) {
                            var radio = form.querySelector('input[name="reactivo_' + resp.reactivo_numero + '"][value="' + resp.respuesta_valor + '"]');
                            if (radio) radio.checked = true;
                        });
                    }

                    if (seccionResultadosTest) seccionResultadosTest.style.display = '';
                    calcularResultados();
                } catch (err) {
                    console.error(err);
                }
            };

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
                    const verResultados = params.get('ver_resultados') === '1';
                    if (preselect && alumnos.some(function (a) { return String(a.id) === preselect; })) {
                        testAlumnoId.value = preselect;
                        testAlumnoId.dispatchEvent(new Event('change'));
                        if (verResultados) {
                            await cargarTestAlumno(preselect);
                        }
                    }
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al cargar alumnos.');
                }
            };

            if (testAlumnoId) {
                testAlumnoId.addEventListener('change', function () {
                    const id = String(testAlumnoId.value || '').trim();
                    const a = alumnos.find(function (x) { return String(x.id) === id; });
                    testMatriculaView.value = a ? String(a.numero_matricula || '') : '';
                });
            }

            if (btnGuardarTestSimple) {
                btnGuardarTestSimple.addEventListener('click', function () {
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

                    const puntajeTotal = respuestas.reduce(function (sum, v) { return sum + v; }, 0);

                    const rangoBajo = '0-26';
                    const rangoMedio = '27-53';
                    const rangoAlto = '54-80';
                    var interpretacion = 'Sin rango configurado';
                    if (puntajeTotal >= 0 && puntajeTotal <= 26) interpretacion = 'Bajo riesgo socioemocional';
                    else if (puntajeTotal >= 27 && puntajeTotal <= 53) interpretacion = 'Riesgo medio socioemocional';
                    else if (puntajeTotal >= 54 && puntajeTotal <= 80) interpretacion = 'Riesgo alto socioemocional';

                    var columnasIdx = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
                    var resultadosColumnas = [];
                    var rasgosPositivosTotal = 0;
                    columnasIdx.forEach(function (col) {
                        var valor = 0;
                        for (var i = col; i <= 80; i += 10) {
                            var r = respuestas[i - 1];
                            if (col === 5) { if (r === 0) valor += 1; }
                            else { if (r === 1) valor += 1; }
                        }
                        if (valor > 5) rasgosPositivosTotal += 1;
                        resultadosColumnas.push({ columna: col, valor: valor, es_positivo: valor > 5 });
                    });

                    const payload = {
                        alumno_id: parseInt(alumnoId, 10),
                        grupo: (formDatos.querySelector('[name="grupo"]').value || '').trim(),
                        fecha_aplicacion: (formDatos.querySelector('[name="fecha_aplicacion"]').value || '').trim(),
                        puntaje_total: puntajeTotal,
                        interpretacion: interpretacion,
                        resultados_columnas: resultadosColumnas,
                        rango_bajo: rangoBajo,
                        rango_medio: rangoMedio,
                        rango_alto: rangoAlto,
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
        });
    </script>
@endsection
