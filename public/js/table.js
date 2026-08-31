$(document).ready( function () {
  var $table = $('#table');
  if (!$table.length) {
    return;
  }

  if ($.fn.DataTable.isDataTable($table)) {
    return;
  }

  var columnCount = $table.find('thead tr:first th').length;
  var filasInvalidas = false;

  $table.find('tbody tr').each(function () {
    var $cells = $(this).find('td');
    if ($cells.length === 1 && $cells.attr('colspan')) {
      filasInvalidas = true;
      return false;
    }
    if ($cells.length > 0 && $cells.length !== columnCount) {
      filasInvalidas = true;
      return false;
    }
  });

  if (filasInvalidas) {
    return;
  }

  $table.DataTable( {
      responsive: true,
      scrollX:false,
      select: false,
      order: [[]], // Ordenar por la primera columna (ID) en orden descendente
      layout: {
              topStart: {
                  buttons: [ 
                      {
                          extend: 'copy',
                          text:'<i class="fa-regular fa-copy"></i>',
                          titleAttr:'Copiar',
                          className:'btn btn-tool-copy push'
                      },
  
                      {
                          extend: 'excel',
                          text:'<i class="fa-regular fa-file-excel"></i>',
                          titleAttr:'Excel',
                          className:'btn btn-tool-excel push'
                      },
  
                      {
                          extend: 'pdf',
                          text:'<i class="fa-regular fa-file-pdf"></i>',
                          titleAttr:'PDF',
                          className:'btn btn-tool-pdf push'
                      },
  
                      {
                          extend: 'print',
                          text:'<i class="fa-solid fa-print"></i>',
                          titleAttr:'Imprimir',
                          className:'btn btn-tool-print push'
                      },
  
                      {
                          extend: 'colvis',
                          text:'<i class="fa-solid fa-filter"></i>',
                          titleAttr:'Filtrar',
                          className:'btn btn-tool-colvis push'
                      }
                  ]
          }
      },
      "oLanguage": {
      "sSearch": '<i class="fa-solid fa-magnifying-glass"></i>'
      },
      "language": {
        "url": "https://cdn.datatables.net/plug-ins/1.13.5/i18n/es-MX.json",
        "emptyTable": "Sin registros disponibles"
    },
  } );
} );


$(document).ready( function () {
  $('#tablenomina').DataTable( {
      responsive: true,
      scrollX:false,
      select: false,
      order: [[2, 'desc']],
      layout: {
              topStart: {
                  buttons: [ 
                      {
                          extend: 'copy',
                          text:'<i class="fa-regular fa-copy"></i>',
                          titleAttr:'Copiar',
                          className:'btn btn-tool-copy push'
                      },
  
                      {
                          extend: 'excel',
                          text:'<i class="fa-regular fa-file-excel"></i>',
                          titleAttr:'Excel',
                          className:'btn btn-tool-excel push'
                      },
  
                      {
                          extend: 'pdf',
                          text:'<i class="fa-regular fa-file-pdf"></i>',
                          titleAttr:'PDF',
                          className:'btn btn-tool-pdf push'
                      },
  
                      {
                          extend: 'print',
                          text:'<i class="fa-solid fa-print"></i>',
                          titleAttr:'Imprimir',
                          className:'btn btn-tool-print push'
                      },
  
                      {
                          extend: 'colvis',
                          text:'<i class="fa-solid fa-filter"></i>',
                          titleAttr:'Filtrar',
                          className:'btn btn-tool-colvis push'
                      }
                  ]
          }
      },
      "oLanguage": {
      "sSearch": '<i class="fa-solid fa-magnifying-glass"></i>'
      },
      "language": {
        "url": "https://cdn.datatables.net/plug-ins/1.13.5/i18n/es-MX.json"
    },
  } );
} );

// Tabla para global
$(document).ready(function () {
  var table = $('#table2').DataTable({
    "dom": 'B<"float-left"l><"float-right"f>t<"float-left"i><"float-right"p><"clearfix">',
    responsive: true,
    // columnDefs: [{ width: '20%', targets: 0 }],
    scrollCollapse: true,
    scrollY: '70vh',
    scrollX: false,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },
    paging: false,
    select: false,
    buttons: [
      {
        extend: 'excelHtml5',
        text: '<i class="fas fa-file-excel"></i> Exportar',
        titleAttr: 'Exportar a Excel',
        className: 'btn btn-success fs-9',
      },],
  });
});

//Tabla general
$(document).ready(function () {
  var table = $('#table3').DataTable({
    "dom": 'B<"float-left"l><"float-right"f>t<"float-left"i><"float-right"p><"clearfix">',
    responsive: true,
    // columnDefs: [{ width: '20%', targets: 0 }],
    scrollCollapse: true,
    scrollY: '70vh',
    scrollX: false,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },
    paging: false,
    select: false,
    buttons: [],
  });
});

//Tabla sucursal
$(document).ready(function () {
  new DataTable('#tableSucursales', {
    responsive: true,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },
    layout: {
      topStart: {
        buttons: ['excel']
      }
    }
  });

  new DataTable('#tableNomina', {
    responsive: true,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },
  });
});


$(document).ready( function () {
  $('#tableLicitaciones').DataTable( {
      responsive: true,
      scrollX:false,
      select: false,
      order: [[0, 'desc']],
      layout: {
              topStart: {
                  buttons: [ 
                      {
                          extend: 'copy',
                          text:'<i class="fa-regular fa-copy"></i>',
                          titleAttr:'Copiar',
                          className:'btn btn-tool-copy push'
                      },
  
                      {
                          extend: 'excel',
                          text:'<i class="fa-regular fa-file-excel"></i>',
                          titleAttr:'Excel',
                          className:'btn btn-tool-excel push'
                      },
  
                      {
                          extend: 'pdf',
                          text:'<i class="fa-regular fa-file-pdf"></i>',
                          titleAttr:'PDF',
                          className:'btn btn-tool-pdf push'
                      },
  
                      {
                          extend: 'print',
                          text:'<i class="fa-solid fa-print"></i>',
                          titleAttr:'Imprimir',
                          className:'btn btn-tool-print push'
                      },
  
                      {
                          extend: 'colvis',
                          text:'<i class="fa-solid fa-filter"></i>',
                          titleAttr:'Filtrar',
                          className:'btn btn-tool-colvis push'
                      }
                  ]
          }
      },
      "oLanguage": {
      "sSearch": '<i class="fa-solid fa-magnifying-glass"></i>'
      },
      "language": {
        "url": "https://cdn.datatables.net/plug-ins/1.13.5/i18n/es-MX.json"
    },
  } );
} );


$(document).ready( function () {
  $('#tableOC').DataTable( {
      responsive: true,
      scrollX:false,
      select: false,
      order: [[0, 'desc']],
      layout: {
              topStart: {
                  buttons: [ 
                      {
                          extend: 'copy',
                          text:'<i class="fa-regular fa-copy"></i>',
                          titleAttr:'Copiar',
                          className:'btn btn-tool-copy push'
                      },
  
                      {
                          extend: 'excel',
                          text:'<i class="fa-regular fa-file-excel"></i>',
                          titleAttr:'Excel',
                          className:'btn btn-tool-excel push'
                      },
  
                      {
                          extend: 'pdf',
                          text:'<i class="fa-regular fa-file-pdf"></i>',
                          titleAttr:'PDF',
                          className:'btn btn-tool-pdf push'
                      },
  
                      {
                          extend: 'print',
                          text:'<i class="fa-solid fa-print"></i>',
                          titleAttr:'Imprimir',
                          className:'btn btn-tool-print push'
                      },
  
                      {
                          extend: 'colvis',
                          text:'<i class="fa-solid fa-filter"></i>',
                          titleAttr:'Filtrar',
                          className:'btn btn-tool-colvis push'
                      }
                  ]
          }
      },
      "oLanguage": {
      "sSearch": '<i class="fa-solid fa-magnifying-glass"></i>'
      },
      "language": {
        "url": "https://cdn.datatables.net/plug-ins/1.13.5/i18n/es-MX.json"
    },
  } );
} );


$(document).ready( function () {
  $('#tablaProyectos').DataTable( {
      responsive: true,
      scrollX:false,
      select: false,
      order: [[3, 'asc']], // Ordenar por fecha límite
      pageLength: 10,
      lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
      layout: {
              topStart: {
                  buttons: [ 
                      {
                          extend: 'copy',
                          text:'<i class="fa-regular fa-copy"></i>',
                          titleAttr:'Copiar',
                          className:'btn btn-tool-copy push'
                      },
  
                      {
                          extend: 'excel',
                          text:'<i class="fa-regular fa-file-excel"></i>',
                          titleAttr:'Excel',
                          className:'btn btn-tool-excel push'
                      },
  
                      {
                          extend: 'pdf',
                          text:'<i class="fa-regular fa-file-pdf"></i>',
                          titleAttr:'PDF',
                          className:'btn btn-tool-pdf push'
                      },
  
                      {
                          extend: 'print',
                          text:'<i class="fa-solid fa-print"></i>',
                          titleAttr:'Imprimir',
                          className:'btn btn-tool-print push'
                      },
  
                      {
                          extend: 'colvis',
                          text:'<i class="fa-solid fa-filter"></i>',
                          titleAttr:'Filtrar',
                          className:'btn btn-tool-colvis push'
                      }
                  ]
          }
      },
      "oLanguage": {
      "sSearch": '<i class="fa-solid fa-magnifying-glass"></i>'
      },
      "language": {
        "url": "https://cdn.datatables.net/plug-ins/1.13.5/i18n/es-MX.json"
    },
  } );
} );

$(document).ready( function () {
  $('#tableaguinaldo').DataTable( {
      responsive: true,
      scrollX:false,
      select: false,
      order: [[2, 'asc']],
      layout: {
              topStart: {
                  buttons: [ 
                      {
                          extend: 'copy',
                          text:'<i class="fa-regular fa-copy"></i>',
                          titleAttr:'Copiar',
                          className:'btn btn-tool-copy push'
                      },
  
                      {
                          extend: 'excel',
                          text:'<i class="fa-regular fa-file-excel"></i>',
                          titleAttr:'Excel',
                          className:'btn btn-tool-excel push'
                      },
  
                      {
                          extend: 'pdf',
                          text:'<i class="fa-regular fa-file-pdf"></i>',
                          titleAttr:'PDF',
                          className:'btn btn-tool-pdf push'
                      },
  
                      {
                          extend: 'print',
                          text:'<i class="fa-solid fa-print"></i>',
                          titleAttr:'Imprimir',
                          className:'btn btn-tool-print push'
                      },
  
                      {
                          extend: 'colvis',
                          text:'<i class="fa-solid fa-filter"></i>',
                          titleAttr:'Filtrar',
                          className:'btn btn-tool-colvis push'
                      }
                  ]
          }
      },
      "oLanguage": {
      "sSearch": '<i class="fa-solid fa-magnifying-glass"></i>'
      },
      "language": {
        "url": "https://cdn.datatables.net/plug-ins/1.13.5/i18n/es-MX.json"
    },
  } );
} );

$(document).ready( function () {
  if (!$('#table22').length) return;

  $('#table22').DataTable( {
      responsive: true,
      scrollX:false,
      select: false,
      order: [[0, 'asc']], // Ordenar por la primera columna (ID) en orden descendente
      layout: {
              topStart: {
                  buttons: [ 
                      {
                          extend: 'copy',
                          text:'<i class="fa-regular fa-copy"></i>',
                          titleAttr:'Copiar',
                          className:'btn btn-tool-copy push'
                      },
  
                      {
                          extend: 'excel',
                          text:'<i class="fa-regular fa-file-excel"></i>',
                          titleAttr:'Excel',
                          className:'btn btn-tool-excel push'
                      },
  
                      {
                          extend: 'pdf',
                          text:'<i class="fa-regular fa-file-pdf"></i>',
                          titleAttr:'PDF',
                          className:'btn btn-tool-pdf push'
                      },
  
                      {
                          extend: 'print',
                          text:'<i class="fa-solid fa-print"></i>',
                          titleAttr:'Imprimir',
                          className:'btn btn-tool-print push'
                      },
  
                      {
                          extend: 'colvis',
                          text:'<i class="fa-solid fa-filter"></i>',
                          titleAttr:'Filtrar',
                          className:'btn btn-tool-colvis push'
                      }
                  ]
          }
      },
      "oLanguage": {
      "sSearch": '<i class="fa-solid fa-magnifying-glass"></i>'
      },
      "language": {
        "url": "https://cdn.datatables.net/plug-ins/1.13.5/i18n/es-MX.json"
    },
  } );
} );

$(document).ready( function () {
  if (!$('#tablaResumenAsistencias').length) return;

  $('#tablaResumenAsistencias').DataTable( {
      responsive: false,
      scrollX: false,
      autoWidth: false,
      select: false,
      order: [[0, 'asc']],
      layout: {
              topStart: {
                  buttons: [
                      {
                          extend: 'copy',
                          text:'<i class="fa-regular fa-copy"></i>',
                          titleAttr:'Copiar',
                          className:'btn btn-tool-copy push'
                      },
                      {
                          extend: 'excel',
                          text:'<i class="fa-regular fa-file-excel"></i>',
                          titleAttr:'Excel',
                          className:'btn btn-tool-excel push'
                      },
                      {
                          extend: 'pdf',
                          text:'<i class="fa-regular fa-file-pdf"></i>',
                          titleAttr:'PDF',
                          className:'btn btn-tool-pdf push'
                      },
                      {
                          extend: 'print',
                          text:'<i class="fa-solid fa-print"></i>',
                          titleAttr:'Imprimir',
                          className:'btn btn-tool-print push'
                      },
                      {
                          extend: 'colvis',
                          text:'<i class="fa-solid fa-filter"></i>',
                          titleAttr:'Filtrar',
                          className:'btn btn-tool-colvis push'
                      }
                  ]
          }
      },
      "oLanguage": {
      "sSearch": '<i class="fa-solid fa-magnifying-glass"></i>'
      },
      "language": {
        "url": "https://cdn.datatables.net/plug-ins/1.13.5/i18n/es-MX.json"
    },
  } );
} );

$(document).ready( function () {
  if (!$('#tablaDetalleAsistencias').length) return;

  $('#tablaDetalleAsistencias').DataTable( {
      responsive: false,
      scrollX: false,
      autoWidth: false,
      select: false,
      order: [[0, 'asc'], [2, 'asc']],
      layout: {
              topStart: {
                  buttons: [
                      {
                          extend: 'copy',
                          text:'<i class="fa-regular fa-copy"></i>',
                          titleAttr:'Copiar',
                          className:'btn btn-tool-copy push'
                      },
                      {
                          extend: 'excel',
                          text:'<i class="fa-regular fa-file-excel"></i>',
                          titleAttr:'Excel',
                          className:'btn btn-tool-excel push'
                      },
                      {
                          extend: 'pdf',
                          text:'<i class="fa-regular fa-file-pdf"></i>',
                          titleAttr:'PDF',
                          className:'btn btn-tool-pdf push'
                      },
                      {
                          extend: 'print',
                          text:'<i class="fa-solid fa-print"></i>',
                          titleAttr:'Imprimir',
                          className:'btn btn-tool-print push'
                      },
                      {
                          extend: 'colvis',
                          text:'<i class="fa-solid fa-filter"></i>',
                          titleAttr:'Filtrar',
                          className:'btn btn-tool-colvis push'
                      }
                  ]
          }
      },
      "oLanguage": {
      "sSearch": '<i class="fa-solid fa-magnifying-glass"></i>'
      },
      "language": {
        "url": "https://cdn.datatables.net/plug-ins/1.13.5/i18n/es-MX.json"
    },
  } );
} );