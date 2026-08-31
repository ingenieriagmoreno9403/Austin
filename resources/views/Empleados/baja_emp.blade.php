@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

@if($mensaje = Session::get('warningDescription'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "warning",title: "¡No se guardo la baja!", text: "Necesita escribir una descripción para poder guardar"});';
            echo '</script>'; 
    @endphp
@endif
 
<div class="container-fluid format_page empleado-form-page">
  <!-- Header Section -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center header flex-wrap gap-2">
        <div class="d-flex align-items-center">
          <div class="header-icon me-3">
            <i class="fas fa-user-slash"></i>
          </div>
          <div>
            <div class="mb-1">
              <a href="/Empleados" class="text-muted text-decoration-none fs-8">
                <i class="fa-solid fa-chevron-left me-1"></i>Empleados
              </a>
            </div>
            <h2 class="mb-0 text-marino fw-bold">Baja de Empleado</h2>
            <p class="text-muted mb-0">Cálculo de percepciones y deducciones</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="empleado-form-toolbar mb-3">
    <nav id="navbar-empleado-baja" class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <ul class="nav nav-pills empleado-step-nav gap-1 mb-0">
        <li class="nav-item">
          <a class="nav-link active" href="#paso-baja-1"><span>1</span> Detalles</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#paso-baja-2"><span>2</span> Percepciones</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#paso-baja-3"><span>3</span> Deducciones</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#paso-baja-4"><span>4</span> Total</a>
        </li>
      </ul>
      <div class="empleado-ingreso-progress-wrap">
        <div class="d-flex justify-content-between fs-8 text-muted mb-1">
          <span>Avance</span>
          <span id="empleadoFormProgressText">0%</span>
        </div>
        <div class="progress empleado-ingreso-progress">
          <div id="empleadoFormProgress" class="progress-bar" role="progressbar"
            style="width:0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
      </div>
    </nav>
  </div>

  <div class="p-2 mt-2"> 
      <form action="/Empleados/cacula_baja" name="formulario1" class="form g-3 needs-validation modern-form empleado-form-shell" novalidate>
        @csrf
        <div class="row">
            <div class="col">
              <div class="card shadow-sm border-0 mb-4 rounded-4 empleado-form-card bloque">
                    <div class="card-body p-4 bg-white rounded-3">
                    <h4 id="paso-baja-1" class="empleado-section-title">
                        <i class="fa-solid fa-clipboard-list text-orange"></i>
                        <span><b class="text-orange">Paso 1.</b> Detalles de baja</span>
                    </h4>

                    <div class="row">
                          <div class="col-md-3 mb-3">
                            <label class="form-label" for="fecha_baja">Fecha de Baja</label> 
                            <input type="date" name="fecha_baja" id="fecha_baja" value="{{$fecha_baja}}" class="form-control" required />
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                          </div>

                          <div class="col-md-3 mb-3">
                            <label class="form-label" for="tipo_baja">Tipo de Baja</label> 
                            <select name="tipo_baja" id="tipo_baja" class="form-select" onchange="getComboA(this)" required>
                                <option  value="">Seleccionar...</option>
                                @if(is_null($tipo_baja) || $tipo_baja == "")
                                  <option  value="finiquito">Finiquito</option>
                                  <option  value="no_aplica">No aplica</option>
                                @else
                                    @if($tipo_baja == "finiquito")
                                      <option  value="finiquito" selected>Finiquito</option>
                                      <option  value="no_aplica">No aplica</option>
                                    @else
                                      <option  value="finiquito">Finiquito</option>
                                      <option  value="no_aplica" selected>No aplica</option>
                                    @endif
                                @endif
                            </select>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                          </div>

                          <div class="col-md-6 mb-3">
                            <div class="form-outline">
                            <label class="form-label" for="descripcion_baja">Descripción de la Baja</label>         
                              <input class="form-control text" id="descripcion_baja" name="descripcion_baja" required value="{{$descripcion_baja}}" placeholder="Redacte descripción de baja" maxlength="250" minlength="5">
                              <div class="valid-feedback">¡Se ve bien!</div>
                              <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                            </div>
                          </div>
                          
                    </div>
                    </div>
              </div>
              
              {{------------------ Percepciones--------------------}}
              <div class="card shadow-sm border-0 mb-4 rounded-4 empleado-form-card bloque">
                    <div class="card-body p-4 bg-white rounded-3">
                    <h4 id="paso-baja-2" class="empleado-section-title">
                        <i class="fa-solid fa-hand-holding-dollar text-orange"></i>
                        <span><b class="text-orange">Paso 2.</b> Percepciones</span>
                    </h4>

                    <div class="row">
                      <div class="col-md-4 empleado-perception-block mb-3">
                          <div class="form-outline mb-3">
                            <label class="form-label">Dias de Gratificacion</label>
                            <input type="text" name="dias_gratificacion" id="dias_gratificacion" value="{{$dias_gratificacion}}" class="form-control text" placeholder="00" required/>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                          </div>

                          <div class="form-outline mb-3">
                            <label class="form-label">Cantidad Gratificacion</label>
                            <div class="empleado-readonly-display {{ empty($total_gratificacion) ? 'empty' : '' }}">
                              {{ $total_gratificacion ? '$' . number_format($total_gratificacion, 2) : 'Sin calcular' }}
                            </div>
                            <input type="hidden" name="total_gratificacion" id="total_gratificacion" value="{{$total_gratificacion}}"/>
                          </div>

                          <div class="form-outline mb-3">
                            <label class="form-label">Dias de Vacaciones No Tomados</label>
                            <input type="text" name="dias_vacaciones_no_tomadas" id="dias_vacaciones_no_tomadas" value="{{$dias_vacaciones_no_tomadas}}" class="form-control text" placeholder="00" required/>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                          </div>

                          <div class="form-outline">
                            <label class="form-label">Vacaciones No Tomadas</label>
                            <div class="empleado-readonly-display {{ empty($total_vacaciones_no_tomadas) ? 'empty' : '' }}">
                              {{ $total_vacaciones_no_tomadas ? '$' . number_format($total_vacaciones_no_tomadas, 2) : 'Sin calcular' }}
                            </div>
                            <input type="hidden" name="total_vacaciones_no_tomadas" id="total_vacaciones_no_tomadas" value="{{$total_vacaciones_no_tomadas}}"/>
                          </div>
                      </div>
                    
                      <div class="col">
                          <div class="row mb-3">
                            <div class="col">
                              <div class="form-outline">
                                <label class="form-label">Total Días Trabajados</label>
                                <div class="empleado-readonly-display {{ empty($total_dias_trabajados) ? 'empty' : '' }}">
                                  {{ $total_dias_trabajados ? $total_dias_trabajados . ' días' : 'Sin calcular' }}
                                </div>
                                <input type="hidden" name="total_dias_trabajados" id="total_dias_trabajados" value="{{$total_dias_trabajados}}"/>
                              </div>
                            </div>
                          </div>

                          <div class="row">
                            <div class="col">
                              <div class="form-outline">
                                <label class="form-label">Prima Vacacional</label>
                                <div class="empleado-readonly-display {{ empty($prima_vacional) ? 'empty' : '' }}">
                                  {{ $prima_vacional ? '$' . number_format($prima_vacional, 2) : 'Sin calcular' }}
                                </div>
                                <input type="hidden" name="prima_vacional" id="prima_vacional" value="{{$prima_vacional}}"/>
                              </div>
                            </div>

                            <div class="mt-3 mb-3 text-end">
                              <label class="empleado-checkbox-option">
                               
                                @if($check2 == 1)
                                  <input class="form-check-input" type="checkbox" id="no_diasvc" value="1" name="no_diasvc" checked/>
                                @else
                                  <input class="form-check-input" type="checkbox" id="no_diasvc" value="1" name="no_diasvc"/>
                                @endif
                                 No Aplicar Prima Vacacional
                              </label>
                            </div>
                          </div>
                          
                          <div class="row mb-3">
                            <div class="col">
                              <div class="form-outline">
                                <label class="form-label">Dias Trabajados Aguinaldo</label>
                                <div class="empleado-readonly-display {{ empty($dias_trabajados_año) ? 'empty' : '' }}">
                                  {{ $dias_trabajados_año ? $dias_trabajados_año . ' días' : 'Sin calcular' }}
                                </div>
                                <input type="hidden" name="dias_trabajados_año" id="dias_trabajados_año" value="{{$dias_trabajados_año}}"/>
                              </div>
                            </div>

                            <div class="col">
                              <div class="form-outline">
                                <label class="form-label">Aguinaldo Proporcional</label>
                                <div class="empleado-readonly-display {{ empty($aguinaldo_proporcional) ? 'empty' : '' }}">
                                  {{ $aguinaldo_proporcional ? '$' . number_format($aguinaldo_proporcional, 2) : 'Sin calcular' }}
                                </div>
                                <input type="hidden" name="aguinaldo_proporcional" id="aguinaldo_proporcional" value="{{$aguinaldo_proporcional}}"/>
                              </div>
                            </div>
                          </div>

                          
                          <div class="mt-3 mb-3 text-end">
                            <label class="empleado-checkbox-option">
                              @if($check3 == 1)
                                <input class="form-check-input" type="checkbox" id="no_aguinaldo" value="1" name="no_aguinaldo" checked/>
                              @else
                                <input class="form-check-input" type="checkbox" id="no_aguinaldo" value="1" name="no_aguinaldo"/>
                              @endif
                              No Aplicar Aguinaldo Proporcional
                            </label>
                          </div>

                          <div class="row">
                            <div class="col">
                              <div class="form-outline">
                                <label class="form-label">Dias Trabajados</label>
                                <div class="empleado-readonly-display {{ empty($dias_trabajados_quin) ? 'empty' : '' }}">
                                  {{ $dias_trabajados_quin ? $dias_trabajados_quin . ' días' : 'Sin calcular' }}
                                </div>
                                <input type="hidden" name="dias_trabajados_quin" id="dias_trabajados_quin" value="{{$dias_trabajados_quin}}"/>
                              </div>
                            </div>

                            <div class="col">
                              <div class="form-outline">
                                <label class="form-label">Sueldo Proporcional</label>
                                <div class="empleado-readonly-display {{ empty($sueldo_proporcional) ? 'empty' : '' }}">
                                  {{ $sueldo_proporcional ? '$' . number_format($sueldo_proporcional, 2) : 'Sin calcular' }}
                                </div>
                                <input type="hidden" name="sueldo_proporcional" id="sueldo_proporcional" value="{{$sueldo_proporcional}}"/>
                              </div>
                            </div> 
                            
                            <div class="text-end mt-3 mb-3">
                              <label class="empleado-checkbox-option">
                                
                                @if($check == 1)
                                  <input class="form-check-input" type="checkbox" name="no_diastr" id="no_diastr" value="1" checked/>
                                @else
                                  <input class="form-check-input" type="checkbox" name="no_diastr" id="no_diastr" value="1"/>
                                @endif
                                Sin Días Trabajados
                              </label>
                            </div>
                          </div>
                      </div>
                    </div>

                    <div class="row mt-4">
                      <div class="form-outline">
                        <label class="form-label fw-bold text-secondary">Total de Percepciones</label>
                        <div class="empleado-total-display">
                          {{ $total_percepciones ? '$' . number_format($total_percepciones, 2) : '$0.00' }}
                        </div>
                        <input type="hidden" name="total_percepciones" id="total_percepciones" value="{{$total_percepciones}}"/>
                      </div>
                    </div>
                    </div>
              </div>

              {{------------------ Deducciones--------------------}}               
              <div class="card shadow-sm border-0 mb-4 rounded-4 empleado-form-card bloque">
                      <div class="card-body p-4 bg-white rounded-3">
                      <h4 id="paso-baja-3" class="empleado-section-title">
                          <i class="fa-solid fa-minus-circle text-orange"></i>
                          <span><b class="text-orange">Paso 3.</b> Deducciones</span>
                      </h4>
                  
                      <div class="row mb-3">
                          <div class="col form-outline">
                            <label class="form-label">IMSS</label>
                            <input type="text" name="deduccion_imms" id="deduccion_imms" class="form-control text" placeholder="00.00" value="{{$deduccion_imms}}" required />
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                          </div>

                          <div class="col form-outline">
                            <label class="form-label">INFONAVIT</label>
                            <div class="empleado-readonly-display {{ empty($total_infonavit) ? 'empty' : '' }}">
                              {{ $total_infonavit ? '$' . number_format($total_infonavit, 2) : 'Sin calcular' }}
                            </div>
                            <input type="hidden" name="total_infonavit" id="total_infonavit" value="{{$total_infonavit}}"/>
                          </div>

                          <input type="hidden" name="factor_sua" id="factor_sua" value="{{$factor_sua}}"/>
                      </div>
                  
                      <div class="row mb-3">
                        <div class="col form-outline">
                          <label class="form-label">Prestamo</label>
                            @if($prestamo > 0) @php($prestamo = $prestamo)
                            @else @php($prestamo = 0) @endif
                          <input type="text" name="prestamo" id="prestamo" class="form-control text" placeholder="00.00" value="{{$prestamo}}" required />
                          <div class="valid-feedback">¡Se ve bien!</div>
                          <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                        </div>
                     
                        <div class="col form-outline">
                          <label class="form-label">Otras Deducciones</label>
                          <input type="text" name="otros" id="otros" class="form-control text" placeholder="00.00" value="{{$otros}}" required />
                          <div class="valid-feedback">¡Se ve bien!</div>
                          <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                        </div>

                        <input type="hidden" name="transporte" id="transporte" value="{{$transporte}}"/>
                      </div>

                      <div class="row mt-4">
                          <div class="form-outline">
                            <label class="form-label fw-bold text-secondary">Total de Deducciones</label>
                            <div class="empleado-total-display empleado-total-dark">
                              {{ $total_deducciones ? '$' . number_format($total_deducciones, 2) : '$0.00' }}
                            </div>
                            <input type="hidden" name="total_deducciones" id="total_deducciones" value="{{$total_deducciones}}"/>
                          </div>
                      </div>
                      </div>
              </div>

              {{------------------ Totales--------------------}} 
              <div class="card shadow-sm border-0 mb-4 rounded-4 empleado-form-card bloque">
                      <div class="card-body p-4 bg-white rounded-3">
                      <h4 id="paso-baja-4" class="empleado-section-title">
                          <i class="fa-solid fa-sack-dollar text-orange"></i>
                          <span><b class="text-orange">Paso 4.</b> Cantidad a Entregar</span>
                      </h4>

                      <div class="row text-center">
                        <div class="col-md-8 mx-auto">
                          <div class="empleado-total-display empleado-total-lg">
                            {{ $total ? '$' . number_format($total, 2) : '$0.00' }}
                          </div>
                          <input type="hidden" name="total" id="total" value="{{$total}}"/>
                        </div>
                      </div>
                      </div>
              </div>
            </div>

            <div class="col-md-4">
                {{------------------ Detalles de la baja--------------------}}
                <div class="card shadow-sm border-0 mb-4 rounded-4 empleado-form-card empleado-info-panel p-4 bloque">
                     <h5 class="empleado-info-title">Información del Empleado</h5>

                    <table class="table empleado-info-table mb-0">
                      <tbody>
                        <input type="hidden" name="id" id="id" value="{{$id}}">
                        <tr><th>Nombre</th></tr>
                        <tr><td>{{$nombre}}</td></tr>
                        <input type="hidden" name="nombre" id="nombre" value="{{$nombre}}">
                        <tr><th>Puesto</th></tr>
                        <tr><td>{{$puesto}}</td></tr>
                        <input type="hidden" name="puesto" id="puesto" value="{{$puesto}}">
                        <tr><th>Empresa</th></tr>
                        <tr><td>{{$nombre_empresa}}</td></tr>
                        <input type="hidden" name="nombre_empresa" id="nombre_empresa" value="{{$nombre_empresa}}">
                        <tr><th>Tipo Infonavit</th></tr>
                        <tr><td>{{$tipo_infonavit}}</td></tr>
                        <input type="hidden" name="tipo_infonavit" id="tipo_infonavit" value="{{$tipo_infonavit}}">
                        <tr><th>Sueldo Mensual</th></tr>
                        <tr><td>${{ $salario_mensual ? number_format($salario_mensual, 2) : '0.00' }}</td></tr>
                        <input type="hidden" name="salario_mensual" id="salario_mensual" value="{{$salario_mensual}}">
                        <tr><th>Salario Diario</th></tr>
                        <tr><td>${{ $salario ? number_format($salario, 2) : '0.00' }}</td></tr>
                        <input type="hidden" name="salario" id="salario" value="{{$salario}}">
                        <tr><th>Fecha de Ingreso</th></tr>
                        <tr><td>{{ $fecha_ingreso ? \Carbon\Carbon::parse($fecha_ingreso)->format('d/m/Y') : 'N/A' }}</td></tr>
                        <input type="hidden" name="fecha_ingreso" id="fecha_ingreso" value="{{$fecha_ingreso}}">
                      </tbody>
                    </table>
                </div> 

                {{------------------ Herramientas--------------------}}
                <div class="card shadow-sm border-0 mb-4 rounded-4 empleado-form-card p-4 empleado-baja-actions">
                  <h6 class="empleado-subsection-title mb-3">Acciones</h6>
                  <div class="d-grid gap-2">
                      <button class="btn btn-success" type="submit">
                        <i class="fas fa-calculator"></i> Calcular
                      </button>
                      <a class="btn btn-outline-secondary" href="/Empleados/Baja/{{$id}}">
                        <i class="fas fa-broom"></i> Limpiar
                      </a>
                      <button onclick="abrirPagina()" class="btn btn-baseColor" type="button">
                        <i class="fas fa-check"></i> Guardar
                      </button>
                  </div>

                  <input class="d-none" id="guardar" name="guardar" type="checkbox" value="1">
              </div>
            </div>
        </div>
      </form>
  </div> 
</div>


<script>
  document.addEventListener('DOMContentLoaded', function () {
    const progressBar = document.getElementById('empleadoFormProgress');
    const progressText = document.getElementById('empleadoFormProgressText');
    const steps = ['paso-baja-1', 'paso-baja-2', 'paso-baja-3', 'paso-baja-4'];
    const toolbar = document.querySelector('.empleado-form-toolbar');

    const updateProgress = () => {
      const scrollTop = window.scrollY || document.documentElement.scrollTop;
      const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
      const percent = scrollHeight > 0 ? Math.min(100, Math.round((scrollTop / scrollHeight) * 100)) : 0;

      if (progressBar) {
        progressBar.style.width = percent + '%';
        progressBar.setAttribute('aria-valuenow', percent.toString());
      }

      if (progressText) {
        progressText.textContent = percent + '%';
      }

      const offset = (toolbar ? toolbar.offsetHeight : 0) + 24;

      steps.forEach((stepId, index) => {
        const section = document.getElementById(stepId);
        const navLink = document.querySelector(`#navbar-empleado-baja a[href="#${stepId}"]`);
        const nextStep = steps[index + 1] ? document.getElementById(steps[index + 1]) : null;

        if (!section || !navLink) {
          return;
        }

        const sectionTop = section.getBoundingClientRect().top + scrollTop;
        const nextTop = nextStep
          ? nextStep.getBoundingClientRect().top + scrollTop
          : document.documentElement.scrollHeight;
        const isActive = scrollTop >= sectionTop - offset && scrollTop < nextTop - offset;

        navLink.classList.toggle('active', isActive);
      });
    };

    document.querySelectorAll('#navbar-empleado-baja a[href^="#"]').forEach((link) => {
      link.addEventListener('click', (event) => {
        const targetId = link.getAttribute('href');
        const target = targetId ? document.querySelector(targetId) : null;

        if (!target) {
          return;
        }

        event.preventDefault();
        const offset = (toolbar ? toolbar.offsetHeight : 0) + 16;
        const scrollTop = window.scrollY || document.documentElement.scrollTop;
        const targetTop = target.getBoundingClientRect().top + scrollTop - offset;

        window.scrollTo({
          top: targetTop,
          behavior: 'smooth'
        });
      });
    });

    window.addEventListener('scroll', updateProgress, { passive: true });
    updateProgress();
  });
</script>

<script>
  function abrirPagina(){
      document.getElementById('guardar').checked = true;
      document.formulario1.submit();
  }

  $(document).ready(function() {
      // Mostrar/ocultar bloques según el tipo de baja inicial
      var tipoBaja = $('#tipo_baja').val();
      if(tipoBaja == "no_aplica"){
          $(".bloque").hide();
      } else {
          $('.bloque').show();
      }
  });

  function getComboA(selectObject) {
      var value = selectObject.value;  

      if(value == "no_aplica"){
         $(".bloque").hide();
      }else{
          $('.bloque').show(); 
      }
  }
</script>

<script src="{{ asset('js/validation.js') }}"></script>
@endsection
