@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid format_page">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h2 class="mb-1 text-marino fw-bold">Mis documentos</h2>
            <p class="text-muted mb-1">{{ $proveedor->nombre ?? '' }}</p>
            <span class="badge {{ $estado['clase'] }}">{{ $estado['etiqueta'] }}</span>
            @if(!empty($estado['contrato_fin']))
                <span class="badge bg-secondary ms-1">
                    Contrato vence: {{ \Carbon\Carbon::parse($estado['contrato_fin'])->format('d/m/Y') }}
                </span>
            @endif
        </div>
        <a href="{{ route('ext_licitaciones') }}" class="btn btn-outline-secondary btn-sm">Mis licitaciones</a>
    </div>

    @if(session('success')) <div class="alert alert-success py-2">{{ session('success') }}</div> @endif
    @if(session('warningBD')) <div class="alert alert-warning py-2">{{ session('warningBD') }}</div> @endif

    <div class="alert alert-info py-2 small">
        Debes tener la documentación obligatoria y el contrato vigente para poder ser invitado a licitaciones.
        La vigencia del <strong>Contrato</strong> la define Compras al editar el proveedor.
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post" action="{{ route('ext_documentos.store') }}" enctype="multipart/form-data" id="formDocsLote">
                @csrf
                <input type="hidden" name="modo" value="lote">
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Documento</th>
                                <th>Obligatorio</th>
                                <th>Estado</th>
                                <th>Vigencia actual</th>
                                <th>Archivo</th>
                                <th>Nueva vigencia</th>
                                <th>Archivo a subir</th>
                                <th style="min-width:100px">Uno a uno</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($matriz as $row)
                                <tr>
                                    <td>
                                        <strong>{{ $row->nombre }}</strong>
                                        @if($row->es_contrato) <span class="badge bg-dark">Contrato</span> @endif
                                    </td>
                                    <td>{{ $row->obligatorio ? 'Sí' : 'No' }}</td>
                                    <td>
                                        @if($row->cargado)
                                            <span class="badge bg-success">Cargado</span>
                                        @elseif($row->obligatorio)
                                            <span class="badge bg-danger">Falta</span>
                                        @else
                                            <span class="badge bg-secondary">Opcional</span>
                                        @endif
                                    </td>
                                    <td>{{ $row->vigencia ? \Carbon\Carbon::parse($row->vigencia)->format('d/m/Y') : '—' }}</td>
                                    <td>
                                        @if($row->archivo)
                                            <a href="{{ asset($row->archivo) }}" target="_blank">Ver</a>
                                        @else —
                                        @endif
                                    </td>
                                    <td style="min-width:150px">
                                        @if($row->es_contrato)
                                            <span class="small text-muted">Definida por Compras</span>
                                        @else
                                            <input type="date"
                                                   name="vigencias[{{ $row->tipo_id }}]"
                                                   id="vigencia_{{ $row->tipo_id }}"
                                                   class="form-control form-control-sm"
                                                   value="{{ $row->vigencia }}"
                                                   form="formDocsLote"
                                                   title="Vigencia">
                                        @endif
                                    </td>
                                    <td style="min-width:220px">
                                        <input type="file"
                                               name="archivos[{{ $row->tipo_id }}]"
                                               id="archivo_{{ $row->tipo_id }}"
                                               class="form-control form-control-sm"
                                               accept=".pdf,.jpg,.jpeg,.png"
                                               form="formDocsLote">
                                    </td>
                                    <td>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-dark js-subir-uno"
                                                data-tipo="{{ $row->tipo_id }}"
                                                data-contrato="{{ $row->es_contrato ? 1 : 0 }}">
                                            Subir
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2">
                    <small class="text-muted">Puedes cargar varios y usar «Guardar todos», o «Subir» en cada línea.</small>
                    <button type="submit" class="btn btn-baseColor">
                        Guardar todos los seleccionados
                    </button>
                </div>
            </form>

            <form method="post" action="{{ route('ext_documentos.store') }}" enctype="multipart/form-data" id="formDocsUno" class="d-none">
                @csrf
                <input type="hidden" name="modo" value="uno">
                <input type="hidden" name="tipo_id" id="uno_tipo_id">
                <input type="date" name="vigencia" id="uno_vigencia">
                <input type="file" name="archivo" id="uno_archivo">
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const formUno = document.getElementById('formDocsUno');
    const tipoInput = document.getElementById('uno_tipo_id');
    const vigenciaInput = document.getElementById('uno_vigencia');
    const archivoInput = document.getElementById('uno_archivo');

    document.querySelectorAll('.js-subir-uno').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const tipoId = btn.getAttribute('data-tipo');
            const esContrato = btn.getAttribute('data-contrato') === '1';
            const fileEl = document.getElementById('archivo_' + tipoId);
            const vigEl = document.getElementById('vigencia_' + tipoId);

            if (!fileEl || !fileEl.files || !fileEl.files.length) {
                alert('Selecciona un archivo en esa línea antes de subir.');
                return;
            }

            const dt = new DataTransfer();
            dt.items.add(fileEl.files[0]);
            archivoInput.files = dt.files;
            tipoInput.value = tipoId;
            vigenciaInput.value = (!esContrato && vigEl) ? vigEl.value : '';
            formUno.submit();
        });
    });
})();
</script>
@endsection
