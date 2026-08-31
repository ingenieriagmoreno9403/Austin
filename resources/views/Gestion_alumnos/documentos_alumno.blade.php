@extends('layouts.app')
@section('content')
    @php
        $alumnoId = (int) ($alumnoId ?? request()->query('alumno_id', 347));
        $alumnoId = $alumnoId > 0 ? $alumnoId : 347;
        $alumno = $alumno ?? null;
        $registroDualUrl = url('/Gestion_alumnos/documentos_alumno/registro_aspirante_dual') . '?' . http_build_query(['alumno_id' => $alumnoId]);
        $testPersonalidadUrl = url('/Gestion_alumnos/documentos_alumno/test_personalidad_aula_mixta_simple') . '?' . http_build_query(['alumno_id' => $alumnoId]);
        $fotoUsuario = Auth::check() && Auth::user()->nombre_foto && file_exists(public_path('Images/Perfil/' . Auth::user()->nombre_foto))
            ? asset('Images/Perfil/' . Auth::user()->nombre_foto)
            : asset('Images/Perfil/0.png');
        $fotoUsuarioDefault = asset('Images/Perfil/0.png');
        $nombrePartesAlumno = $alumno ? array_values(array_filter([
            data_get($alumno, 'nombres'),
            data_get($alumno, 'apellido_paterno'),
            data_get($alumno, 'apellido_materno'),
        ], function ($valor) {
            $valor = trim((string) $valor);

            return $valor !== '' && $valor !== '.';
        })) : [];
        $nombreAlumno = trim(preg_replace('/\s+/', ' ', implode(' ', $nombrePartesAlumno)));
        $nombreAlumno = $nombreAlumno !== '' ? $nombreAlumno : 'Alumno no localizado';
        $nombreAlumnoParaIniciales = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $nombreAlumno);
        $palabrasNombreAlumno = preg_split('/\s+/', $nombreAlumnoParaIniciales, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $heroAlumnoNombre = $palabrasNombreAlumno[0] ?? 'alumno';
        $inicialesAlumno = count($palabrasNombreAlumno) === 1
            ? mb_strtoupper(mb_substr($palabrasNombreAlumno[0], 0, 2, 'UTF-8'), 'UTF-8')
            : mb_strtoupper(mb_substr($palabrasNombreAlumno[0] ?? 'A', 0, 1, 'UTF-8') . mb_substr($palabrasNombreAlumno[count($palabrasNombreAlumno) - 1] ?? 'L', 0, 1, 'UTF-8'), 'UTF-8');
        $estadoAlumno = data_get($alumno, 'estado') ?: 'Portal activo';
        $matriculaAlumno = data_get($alumno, 'numero_matricula') ?: '-';
        $semestreAlumno = data_get($alumno, 'semestre');
        $especialidadAlumno = data_get($alumno, 'especialidad.nombre_especialidad') ?: '-';
        $escuelaAlumno = data_get($alumno, 'escuela.nombre') ?: '-';
        $correoAlumno = data_get($alumno, 'correo') ?: '-';
        $telefonoAlumno = data_get($alumno, 'telefono') ?: '-';
        $fotoAlumnoRaw = data_get($alumno, 'foto_url') ?: data_get($alumno, 'foto') ?: data_get($alumno, 'imagen') ?: data_get($alumno, 'avatar') ?: data_get($alumno, 'foto_perfil');
        $fotoAlumno = $fotoAlumnoRaw ? (preg_match('/^(https?:)?\/\//', (string) $fotoAlumnoRaw) || str_starts_with((string) $fotoAlumnoRaw, '/') ? $fotoAlumnoRaw : asset((string) $fotoAlumnoRaw)) : null;

        $documentCards = [
            [
                'titulo' => 'Registro alumno dual',
                'descripcion' => 'Completa el cuestionario inicial para integrar tu expediente al programa dual.',
                'url' => $registroDualUrl,
                'icono' => 'fa-file-signature',
                'estado' => 'Activo',
                'accent' => 'blue',
                'meta' => 'Formato inicial',
            ],
            [
                'titulo' => 'Test de personalidad aula mixta',
                'descripcion' => 'Responde la evaluacion de personalidad para continuar con tu proceso de vinculacion.',
                'url' => $testPersonalidadUrl,
                'icono' => 'fa-brain',
                'estado' => 'Pendiente',
                'accent' => 'purple',
                'meta' => 'Alumno #' . $alumnoId,
            ],
        ];
    @endphp

    <style>
        .student-portal {
            --portal-primary: #1d4ed8;
            --portal-primary-soft: #dbeafe;
            --portal-accent: #0ea5e9;
            --portal-purple: #7c3aed;
            --portal-ink: #0f172a;
            --portal-muted: #64748b;
            --portal-border: rgba(37, 99, 235, 0.14);
            --portal-shadow: 0 22px 55px rgba(15, 23, 42, 0.11);
            --portal-radius: 24px;
            color: var(--portal-ink);
            font-family: "Poppins", sans-serif;
        }

        .student-hero {
            position: relative;
            overflow: hidden;
            border-radius: var(--portal-radius);
            padding: 2rem;
            min-height: 255px;
            color: #fff;
            background:
                radial-gradient(circle at 15% 20%, rgba(255, 255, 255, 0.2), transparent 28%),
                linear-gradient(135deg, #172554 0%, #1d4ed8 48%, #0ea5e9 100%);
            box-shadow: var(--portal-shadow);
        }

        .student-hero::before,
        .student-hero::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.11);
        }

        .student-hero::before {
            width: 310px;
            height: 310px;
            right: -90px;
            top: -105px;
        }

        .student-hero::after {
            width: 180px;
            height: 180px;
            right: 18%;
            bottom: -95px;
        }

        .student-hero-content {
            position: relative;
            z-index: 1;
        }

        .student-kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.35rem 0.85rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.24);
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .hero-user-heading {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .hero-user-photo {
            width: 62px;
            height: 62px;
            border-radius: 18px;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.72);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.18);
            background: rgba(255, 255, 255, 0.14);
        }

        .student-avatar-card {
            position: relative;
            z-index: 1;
            border-radius: 22px;
            padding: 1.1rem;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.22);
            backdrop-filter: blur(10px);
        }

        .student-profile-summary {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            min-width: 0;
        }

        .student-profile-info {
            flex: 1 1 auto;
            min-width: 0;
        }

        .student-photo {
            width: 112px;
            height: 112px;
            flex: 0 0 112px;
            border-radius: 28px;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.72);
            box-shadow: 0 12px 26px rgba(15, 23, 42, 0.18);
        }

        .student-photo-placeholder {
            width: 112px;
            height: 112px;
            flex: 0 0 112px;
            border-radius: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 2.15rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            line-height: 1;
            white-space: nowrap;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.26), rgba(255, 255, 255, 0.1));
            border: 3px solid rgba(255, 255, 255, 0.72);
            box-shadow: 0 12px 26px rgba(15, 23, 42, 0.18);
        }

        .student-name {
            white-space: normal;
            overflow-wrap: anywhere;
            line-height: 1.2;
        }

        .student-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 999px;
            padding: 0.28rem 0.7rem;
            font-size: 0.74rem;
            font-weight: 700;
            background: rgba(255, 255, 255, 0.16);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.22);
        }

        .student-stat {
            padding: 0.9rem 1rem;
            border-radius: 17px;
            background: rgba(255, 255, 255, 0.13);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .student-stat strong {
            display: block;
            font-size: 1.35rem;
            line-height: 1.1;
        }

        .student-stat span {
            font-size: 0.76rem;
            opacity: 0.88;
        }

        .portal-panel {
            background: #fff;
            border: 1px solid var(--portal-border);
            border-radius: var(--portal-radius);
            box-shadow: 0 14px 40px rgba(15, 23, 42, 0.07);
        }

        .portal-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            padding: 1.25rem 1.35rem;
            border-bottom: 1px solid var(--portal-border);
        }

        .portal-panel-body {
            padding: 1.35rem;
        }

        .portal-section-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--portal-primary);
            background: var(--portal-primary-soft);
        }

        .document-card {
            position: relative;
            display: block;
            height: 100%;
            overflow: hidden;
            border-radius: 20px;
            padding: 1.15rem;
            color: var(--portal-ink);
            text-decoration: none;
            background: linear-gradient(180deg, #fff 0%, #f8fbff 100%);
            border: 1px solid var(--portal-border);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .document-card:hover {
            color: var(--portal-ink);
            transform: translateY(-5px);
            border-color: rgba(37, 99, 235, 0.35);
            box-shadow: 0 18px 38px rgba(37, 99, 235, 0.14);
        }

        .document-card::before {
            content: "";
            position: absolute;
            inset: 0 0 auto 0;
            height: 5px;
            background: linear-gradient(90deg, var(--portal-primary), var(--portal-accent));
        }

        .document-card.accent-purple::before {
            background: linear-gradient(90deg, var(--portal-purple), #a855f7);
        }

        .card-icon {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: #fff;
            background: linear-gradient(135deg, var(--portal-primary), var(--portal-accent));
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.22);
        }

        .accent-purple .card-icon {
            background: linear-gradient(135deg, var(--portal-purple), #a855f7);
            box-shadow: 0 12px 24px rgba(124, 58, 237, 0.2);
        }

        .portal-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            border-radius: 999px;
            padding: 0.25rem 0.65rem;
            font-size: 0.72rem;
            font-weight: 700;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .portal-chip.success {
            background: #dcfce7;
            color: #15803d;
        }

        .portal-chip.warning {
            background: #fef3c7;
            color: #b45309;
        }

        .portal-chip.soft {
            background: #f1f5f9;
            color: #475569;
        }

        .info-tile {
            border-radius: 18px;
            padding: 1rem;
            background: #f8fafc;
            border: 1px solid rgba(100, 116, 139, 0.14);
        }

        .info-tile small {
            display: block;
            color: var(--portal-muted);
            font-size: 0.74rem;
            margin-bottom: 0.25rem;
        }

        .info-tile strong {
            display: block;
            color: var(--portal-ink);
            font-size: 0.92rem;
        }

        @media (max-width: 767.98px) {
            .student-hero {
                padding: 1.35rem;
            }

            .student-avatar-card {
                margin-top: 1.25rem;
            }
        }

        @media (max-width: 575.98px) {
            .student-profile-summary {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .student-profile-info {
                width: 100%;
            }

            .student-pill {
                justify-content: center;
            }
        }
    </style>

    <div class="container-fluid format_page student-portal p-4 pt-2">
        <section class="student-hero mb-4">
            <div class="student-hero-content">
                <div class="row align-items-center g-4">
                    <div class="col-xl-8 col-lg-6">
                        <span class="student-kicker mb-3">
                            <i class="fa-solid fa-graduation-cap"></i> Portal de alumnos
                        </span>
                        <div class="hero-user-heading mb-3">
                            <img src="{{ $fotoUsuario }}" alt="Foto de perfil" class="hero-user-photo" onerror="this.onerror=null;this.src='{{ $fotoUsuarioDefault }}';">
                            <h2 class="fw-bold mb-0">Hola, <span>{{ $heroAlumnoNombre }}</span></h2>
                        </div>
                        <p class="mb-4 opacity-90 pe-lg-5">
                            Accede a tus documentos y evaluaciones desde un solo tablero. Mantente al dia con los formatos clave de tu proceso academico.
                        </p>

                        <div class="row g-2">
                            <div class="col-sm-6">
                                <div class="student-stat">
                                    <strong id="statDocumentos">{{ count($documentCards) }}</strong>
                                    <span>Documentos</span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="student-stat">
                                    <strong id="statAlumnoId">{{ $alumnoId }}</strong>
                                    <span>ID alumno</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-lg-6">
                        <article class="student-avatar-card">
                            <div class="student-profile-summary mb-3">
                                @if ($fotoAlumno)
                                    <img src="{{ $fotoAlumno }}" alt="Foto del alumno" class="student-photo" onerror="this.classList.add('d-none');this.nextElementSibling.classList.remove('d-none');">
                                    <span class="student-photo-placeholder d-none">{{ $inicialesAlumno }}</span>
                                @else
                                    <span class="student-photo-placeholder">{{ $inicialesAlumno }}</span>
                                @endif
                                <div class="student-profile-info">
                                    <h4 class="fw-bold mb-2 student-name">{{ $nombreAlumno }}</h4>
                                    <span class="student-pill">
                                        <i class="fa-solid fa-circle-check"></i> {{ $estadoAlumno }}
                                    </span>
                                </div>
                            </div>

                            <div class="row g-2">
                                <div class="col-sm-6">
                                    <span class="student-pill w-100">
                                        <i class="fa-solid fa-hashtag"></i>
                                        <span>Matricula {{ $matriculaAlumno }}</span>
                                    </span>
                                </div>
                                <div class="col-sm-6">
                                    <span class="student-pill w-100">
                                        <i class="fa-solid fa-layer-group"></i>
                                        <span>{{ $semestreAlumno !== null ? 'Semestre ' . $semestreAlumno : 'Semestre -' }}</span>
                                    </span>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="portal-panel mb-4">
            <div class="portal-panel-header">
                <div class="d-flex align-items-center gap-3">
                    <span class="portal-section-icon">
                        <i class="fa-solid fa-id-card-clip"></i>
                    </span>
                    <div>
                        <h5 class="mb-0 fw-semibold">Perfil del alumno</h5>
                        <small class="text-muted">Informacion general para validar tu expediente.</small>
                    </div>
                </div>
                <span class="portal-chip soft">
                    <i class="fa-solid fa-user-graduate"></i> Alumno #{{ $alumnoId }}
                </span>
            </div>
            <div class="portal-panel-body">
                @if (! $alumno)
                    <div class="alert alert-warning mb-3" role="alert">
                        No se encontro la informacion del alumno relacionada con este usuario.
                    </div>
                @endif
                <div class="row g-3">
                    <div class="col-xl-3 col-md-6">
                        <div class="info-tile">
                            <small>Especialidad</small>
                            <strong>{{ $especialidadAlumno }}</strong>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="info-tile">
                            <small>Escuela</small>
                            <strong>{{ $escuelaAlumno }}</strong>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="info-tile">
                            <small>Correo</small>
                            <strong>{{ $correoAlumno }}</strong>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="info-tile">
                            <small>Telefono</small>
                            <strong>{{ $telefonoAlumno }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="portal-panel mb-4">
            <div class="portal-panel-header">
                <div class="d-flex align-items-center gap-3">
                    <span class="portal-section-icon">
                        <i class="fa-solid fa-file-circle-check"></i>
                    </span>
                    <div>
                        <h5 class="mb-0 fw-semibold">Documentos y evaluaciones</h5>
                        <small class="text-muted">Accesos principales para completar tu proceso.</small>
                    </div>
                </div>
                <span class="portal-chip success">
                    <i class="fa-solid fa-bolt"></i> Disponibles
                </span>
            </div>
            <div class="portal-panel-body">
                <div class="row g-3">
                    @foreach ($documentCards as $card)
                        <div class="col-xl-4 col-md-6 col-12">
                            <a href="{{ $card['url'] }}" class="document-card accent-{{ $card['accent'] }}">
                                <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                                    <span class="card-icon">
                                        <i class="fa-solid {{ $card['icono'] }}"></i>
                                    </span>
                                    <span class="portal-chip {{ $card['estado'] === 'Activo' ? 'success' : 'warning' }}">
                                        {{ $card['estado'] }}
                                    </span>
                                </div>
                                <span class="portal-chip soft mb-3">{{ $card['meta'] }}</span>
                                <h4 class="h6 fw-bold mb-2">{{ $card['titulo'] }}</h4>
                                <p class="small text-muted mb-4">{{ $card['descripcion'] }}</p>
                                <div class="d-flex align-items-center justify-content-between">
                                    <strong class="small text-primary">Abrir formato</strong>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
@endsection
