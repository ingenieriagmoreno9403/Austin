//Tabla completa
$(document).ready(function () {
  var table = $('#table').DataTable({
    "dom": 'B<"float-left"l><"float-right"f>t<"float-left"i><"float-right"p><"clearfix">',
    responsive: true,
    scrollY: 700,
    scrollX: false,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },
  });

  $(window).on('resize', function () {
    $('#table').css('width', '100%');
    table.draw(true);
  });
});

$(document).ready(function () {
  var table = $('#minitable').DataTable({
    "dom": 'B<"float-left"l><"float-right"f>t<"float-left"i><"float-right"p><"clearfix">',
    responsive: true,
    scrollY: 600,
    scrollX: false,
    paging: false,
    select: false,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },
    buttons: [],
  });
});

// Tabla para global
$(document).ready(function () {
  var table = $('#table2').DataTable({
    "dom": 'B<"float-left"l><"float-right"f>t<"float-left"i><"float-right"p><"clearfix">',
    responsive: true,
    // columnDefs: [{ width: '20%', targets: 0 }],
    scrollCollapse: true,
    scrollY: '75vh',
    scrollX: true,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },
    paging: false,
    select: false,
    buttons: [
      {
        extend: 'excelHtml5',
        text: '<i class="fa-solid fa-download"></i> &nbsp; Exportar',
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
    scrollY: '75vh',
    scrollX: true,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },
    paging: false,
    select: false,
    buttons: [],
  });
});

$(document).ready( function () {
  $('#tableManejo').DataTable( {
      responsive: true,
      scrollX:false,
      select: false,
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

      footerCallback: function (row, data, start, end, display) {
        let api = this.api();
        let tfoot = $(api.table().footer());
    
        // Remove the formatting to get integer data for summation
        let intVal = function (i) {
          return typeof i === 'string'
            ? i.replace(/[\$,]/g, '') * 1
            : typeof i === 'number'
              ? i
              : 0;
        };
    
        if (tfoot.length > 0) {
          let rows = tfoot.find('tr');
    
          const array = [5, 6, 7, 8];
    
          array.forEach((column) => {
            let thSubtotal = rows.eq(0).find(`th[data-dt-column="${column}"]`);  // Columna 4 en la primera fila (Subtotal)
            let thTotal = rows.eq(1).find(`th[data-dt-column="${column}"]`);     // Columna 4 en la segunda fila (Total)
    
            total = api
              .column(column)
              .data()
              .reduce((a, b) => intVal(a) + intVal(b), 0);
    
            // Total over this page
            pageTotal = api
              .column(column, { page: 'current' })
              .data()
              .reduce((a, b) => intVal(a) + intVal(b), 0);
    
            totalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(total);
            pageTotalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(pageTotal);
    
            // Ahora escribimos en la columna 4 de ambas filas
            thSubtotal.html(pageTotalFormat);
            thTotal.html(totalFormat);
          });
        } else {
          console.warn("No se encontró el TFOOT en la tabla");
        }
      },
  } );
} );


//Tabla
$(function () {
  new DataTable('#tableEmpresas', {
    responsive: true,
    columnDefs: [
      { responsivePriority: 1, targets: 1 },
      { responsivePriority: 2, targets: 0 }
    ],
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },
    layout: {
      topStart: {
        buttons: ['excel']
      }
    }
  });

  new DataTable('#tableCaja', {
    responsive: true,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },

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

    footerCallback: function (row, data, start, end, display) {
      let api = this.api();
      let tfoot = $(api.table().footer());

      // Remove the formatting to get integer data for summation
      let intVal = function (i) {
        return typeof i === 'string'
          ? i.replace(/[\$,]/g, '') * 1
          : typeof i === 'number'
            ? i
            : 0;
      };

      if (tfoot.length > 0) {
        let rows = tfoot.find('tr');

        const array = [3, 4];

        array.forEach((column) => {
          let thSubtotal = rows.eq(0).find(`th[data-dt-column="${column}"]`);  // Columna 4 en la primera fila (Subtotal)
          let thTotal = rows.eq(1).find(`th[data-dt-column="${column}"]`);     // Columna 4 en la segunda fila (Total)

          total = api
            .column(column)
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          // Total over this page
          pageTotal = api
            .column(column, { page: 'current' })
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          totalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(total);
          pageTotalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(pageTotal);

          // Ahora escribimos en la columna 4 de ambas filas
          thSubtotal.html(pageTotalFormat);
          thTotal.html(totalFormat);
        });
      } else {
        console.warn("No se encontró el TFOOT en la tabla");
      }
    }

  });

  new DataTable('#tableGastos', {
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

  new DataTable('#tableDistribuidores', {
    responsive: true,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },
    //2,3,4
    footerCallback: function (row, data, start, end, display) {
      let api = this.api();
      let tfoot = $(api.table().footer());

      // Remove the formatting to get integer data for summation
      let intVal = function (i) {
        return typeof i === 'string'
          ? i.replace(/[\$,]/g, '') * 1
          : typeof i === 'number'
            ? i
            : 0;
      };

      if (tfoot.length > 0) {
        let rows = tfoot.find('tr');

        const array = [2, 3, 4];

        array.forEach((column) => {
          let thSubtotal = rows.eq(0).find(`th[data-dt-column="${column}"]`);  // Columna 4 en la primera fila (Subtotal)
          let thTotal = rows.eq(1).find(`th[data-dt-column="${column}"]`);     // Columna 4 en la segunda fila (Total)

          total = api
            .column(column)
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          // Total over this page
          pageTotal = api
            .column(column, { page: 'current' })
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          totalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(total);
          pageTotalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(pageTotal);

          // Ahora escribimos en la columna 4 de ambas filas
          thSubtotal.html(pageTotalFormat);
          thTotal.html(totalFormat);
        });
      } else {
        console.warn("No se encontró el TFOOT en la tabla");
      }
    },
  });

  new DataTable('#tableAumentoCapital', {
    responsive: true,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },
    footerCallback: function (row, data, start, end, display) {
      let api = this.api();
      let tfoot = $(api.table().footer());

      // Remove the formatting to get integer data for summation
      let intVal = function (i) {
        return typeof i === 'string'
          ? i.replace(/[\$,]/g, '') * 1
          : typeof i === 'number'
            ? i
            : 0;
      };

      if (tfoot.length > 0) {
        let rows = tfoot.find('tr');

        const array = [1, 2];

        array.forEach((column) => {
          let thSubtotal = rows.eq(0).find(`th[data-dt-column="${column}"]`);  // Columna 4 en la primera fila (Subtotal)
          let thTotal = rows.eq(1).find(`th[data-dt-column="${column}"]`);     // Columna 4 en la segunda fila (Total)

          total = api
            .column(column)
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          // Total over this page
          pageTotal = api
            .column(column, { page: 'current' })
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          totalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(total);
          pageTotalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(pageTotal);

          // Ahora escribimos en la columna 4 de ambas filas
          thSubtotal.html(pageTotalFormat);
          thTotal.html(totalFormat);
        });
      } else {
        console.warn("No se encontró el TFOOT en la tabla");
      }
    },
  });

  new DataTable('#tableSolicitudAumento', {
    responsive: true,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },
    footerCallback: function (row, data, start, end, display) {
      let api = this.api();

      // Remove the formatting to get integer data for summation
      let intVal = function (i) {
        return typeof i === 'string'
          ? i.replace(/[\$,]/g, '') * 1
          : typeof i === 'number'
            ? i
            : 0;
      };

      // Total over all pages
      total = api
        .column(4)
        .data()
        .reduce((a, b) => intVal(a) + intVal(b), 0);

      // Total over this page
      pageTotal = api
        .column(4, { page: 'current' })
        .data()
        .reduce((a, b) => intVal(a) + intVal(b), 0);

      // Update footer
      api.column(4).footer().innerHTML =
        '$' + pageTotal + ' ( $' + total + ' total)';



      // Total over all pages
      total = api
        .column(5)
        .data()
        .reduce((a, b) => intVal(a) + intVal(b), 0);

      // Total over this page
      pageTotal = api
        .column(5, { page: 'current' })
        .data()
        .reduce((a, b) => intVal(a) + intVal(b), 0);

      // Update footer
      api.column(5).footer().innerHTML =
        '$' + pageTotal + ' ( $' + total + ' total)';



      // Total over all pages
      total = api
        .column(6)
        .data()
        .reduce((a, b) => intVal(a) + intVal(b), 0);

      // Total over this page
      pageTotal = api
        .column(6, { page: 'current' })
        .data()
        .reduce((a, b) => intVal(a) + intVal(b), 0);

      // Update footer
      api.column(6).footer().innerHTML =
        '$' + pageTotal + ' ( $' + total + ' total)';



      // Total over all pages
      total = api
        .column(7)
        .data()
        .reduce((a, b) => intVal(a) + intVal(b), 0);

      // Total over this page
      pageTotal = api
        .column(7, { page: 'current' })
        .data()
        .reduce((a, b) => intVal(a) + intVal(b), 0);

      // Update footer
      api.column(7).footer().innerHTML =
        '$' + pageTotal + ' ( $' + total + ' total)';
    },
  });

  new DataTable('#tablePrestamoCliente', {
    responsive: true,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },
  });

  new DataTable('#tableCatalogoCliente', {
    responsive: true,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },
    //8, 10, 11

    footerCallback: function (row, data, start, end, display) {
      let api = this.api();
      let tfoot = $(api.table().footer());

      // Remove the formatting to get integer data for summation
      let intVal = function (i) {
        return typeof i === 'string'
          ? i.replace(/[\$,]/g, '') * 1
          : typeof i === 'number'
            ? i
            : 0;
      };

      if (tfoot.length > 0) {
        let rows = tfoot.find('tr');

        const array = [7, 9, 10];

        array.forEach((column) => {
          let thSubtotal = rows.eq(0).find(`th[data-dt-column="${column}"]`);  // Columna 4 en la primera fila (Subtotal)
          let thTotal = rows.eq(1).find(`th[data-dt-column="${column}"]`);     // Columna 4 en la segunda fila (Total)

          total = api
            .column(column)
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          // Total over this page
          pageTotal = api
            .column(column, { page: 'current' })
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          totalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(total);
          pageTotalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(pageTotal);

          // Ahora escribimos en la columna 4 de ambas filas
          thSubtotal.html(pageTotalFormat);
          thTotal.html(totalFormat);
        });
      } else {
        console.warn("No se encontró el TFOOT en la tabla");
      }
    },
  });

  new DataTable('#tableEmpleados', {
    responsive: true,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },

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

    footerCallback: function (row, data, start, end, display) {
      let api = this.api();
      let tfoot = $(api.table().footer());

      // Remove the formatting to get integer data for summation
      let intVal = function (i) {
        return typeof i === 'string'
          ? i.replace(/[\$,]/g, '') * 1
          : typeof i === 'number'
            ? i
            : 0;
      };

      if (tfoot.length > 0) {
        let rows = tfoot.find('tr');

        const array = [7];

        array.forEach((column) => {
          let thSubtotal = rows.eq(0).find(`th[data-dt-column="${column}"]`);
          let thTotal = rows.eq(1).find(`th[data-dt-column="${column}"]`);

          total = api
            .column(column)
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          // Total over this page
          pageTotal = api
            .column(column, { page: 'current' })
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          totalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(total);
          pageTotalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(pageTotal);

          thSubtotal.html(pageTotalFormat);
          thTotal.html(totalFormat);
        });
      } else {
        console.warn("No se encontró el TFOOT en la tabla");
      }
    },
  });

  new DataTable('#tablePrestamoNomina', {
    responsive: true,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },
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

    footerCallback: function (row, data, start, end, display) {
      let api = this.api();
      let tfoot = $(api.table().footer());

      // Remove the formatting to get integer data for summation
      let intVal = function (i) {
        return typeof i === 'string'
          ? i.replace(/[\$,]/g, '') * 1
          : typeof i === 'number'
            ? i
            : 0;
      };

      if (tfoot.length > 0) {
        let rows = tfoot.find('tr');

        const array = [6, 8, 9];

        array.forEach((column) => {
          let thSubtotal = rows.eq(0).find(`th[data-dt-column="${column}"]`);  // Columna 4 en la primera fila (Subtotal)
          let thTotal = rows.eq(1).find(`th[data-dt-column="${column}"]`);     // Columna 4 en la segunda fila (Total)

          total = api
            .column(column)
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          // Total over this page
          pageTotal = api
            .column(column, { page: 'current' })
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          totalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(total);
          pageTotalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(pageTotal);

          // Ahora escribimos en la columna 4 de ambas filas
          thSubtotal.html(pageTotalFormat);
          thTotal.html(totalFormat);
        });
      } else {
        console.warn("No se encontró el TFOOT en la tabla");
      }
    },
  });

  new DataTable('#tablePrestamoNominaDetalle', {
    responsive: true,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },
  });

  new DataTable('#tableNominas', {
    responsive: true,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },
    layout: {
      topStart: {
          buttons: [ 
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

    //4, 5, 6, 7
    footerCallback: function (row, data, start, end, display) {
      let api = this.api();
      let tfoot = $(api.table().footer());

      // Remove the formatting to get integer data for summation
      let intVal = function (i) {
        return typeof i === 'string'
          ? i.replace(/[\$,]/g, '') * 1
          : typeof i === 'number'
            ? i
            : 0;
      };

      if (tfoot.length > 0) {
        let rows = tfoot.find('tr');

        const array = [4, 5, 6, 7];

        array.forEach((column) => {
          let thSubtotal = rows.eq(0).find(`th[data-dt-column="${column}"]`);  // Columna 4 en la primera fila (Subtotal)
          let thTotal = rows.eq(1).find(`th[data-dt-column="${column}"]`);     // Columna 4 en la segunda fila (Total)

          total = api
            .column(column)
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          // Total over this page
          pageTotal = api
            .column(column, { page: 'current' })
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          totalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(total);
          pageTotalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(pageTotal);

          // Ahora escribimos en la columna 4 de ambas filas
          thSubtotal.html(pageTotalFormat);
          thTotal.html(totalFormat);
        });
      } else {
        console.warn("No se encontró el TFOOT en la tabla");
      }
    },
  });

  new DataTable('#tableNominasExcel', {
    responsive: true,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },

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

    footerCallback: function (row, data, start, end, display) {
      let api = this.api();
      let tfoot = $(api.table().footer());

      // Remove the formatting to get integer data for summation
      let intVal = function (i) {
        return typeof i === 'string'
          ? i.replace(/[\$,]/g, '') * 1
          : typeof i === 'number'
            ? i
            : 0;
      };

      if (tfoot.length > 0) {
        let rows = tfoot.find('tr');

        const array = [4, 5, 6, 7];

        array.forEach((column) => {
          let thSubtotal = rows.eq(0).find(`th[data-dt-column="${column}"]`);  // Columna 4 en la primera fila (Subtotal)
          let thTotal = rows.eq(1).find(`th[data-dt-column="${column}"]`);     // Columna 4 en la segunda fila (Total)

          total = api
            .column(column)
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          // Total over this page
          pageTotal = api
            .column(column, { page: 'current' })
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          totalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(total);
          pageTotalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(pageTotal);

          // Ahora escribimos en la columna 4 de ambas filas
          thSubtotal.html(pageTotalFormat);
          thTotal.html(totalFormat);
        });
      } else {
        console.warn("No se encontró el TFOOT en la tabla");
      }
    },
  });

  new DataTable('#tableSolicitudesCancelaciones', {
    responsive: true,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },
  });

  new DataTable('#tableCapturaPrestamos', {
    responsive: true,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },

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
    //8, 11, 13

    footerCallback: function (row, data, start, end, display) {
      let api = this.api();
      let tfoot = $(api.table().footer());

      // Remove the formatting to get integer data for summation
      let intVal = function (i) {
        return typeof i === 'string'
          ? i.replace(/[\$,]/g, '') * 1
          : typeof i === 'number'
            ? i
            : 0;
      };

      if (tfoot.length > 0) {
        let rows = tfoot.find('tr');

        const array = [8, 11, 13];

        array.forEach((column) => {
          let thSubtotal = rows.eq(0).find(`th[data-dt-column="${column}"]`);
          let thTotal = rows.eq(1).find(`th[data-dt-column="${column}"]`);

          total = api
            .column(column)
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          // Total over this page
          pageTotal = api
            .column(column, { page: 'current' })
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          totalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(total);
          pageTotalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(pageTotal);

          thSubtotal.html(pageTotalFormat);
          thTotal.html(totalFormat);
        });
      } else {
        console.warn("No se encontró el TFOOT en la tabla");
      }
    },

  });

  new DataTable('#tableAdministracionCierres', {
    responsive: true,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },

    footerCallback: function (row, data, start, end, display) {
      let api = this.api();
      let tfoot = $(api.table().footer());

      let intVal = function (i) {
        return typeof i === 'string'
          ? i.replace(/[\$,]/g, '') * 1
          : typeof i === 'number'
            ? i
            : 0;
      };

      if (tfoot.length > 0) {
        let rows = tfoot.find('tr');
        const array = [7, 8, 9, 10, 11, 12, 13, 15];
        //const array = [7]

        array.forEach((column) => {
          let thSubtotal = rows.eq(0).find(`th[data-dt-column="${column}"]`);  // Columna 4 en la primera fila (Subtotal)
          let thTotal = rows.eq(1).find(`th[data-dt-column="${column}"]`);     // Columna 4 en la segunda fila (Total)

          let total = api
            .column(column)
            .nodes() // Obtiene los nodos de la columna
            .toArray()
            .reduce((sum, node) => {
              let text = $(node).text().trim(); // Toma el texto visible del <td>
              return sum + intVal(text);
            }, 0);

          // Total over this page
          let pageTotal = api
            .column(column, { page: 'current' })
            .nodes()
            .toArray()
            .reduce((sum, node) => {
              let text = $(node).text().trim(); // Toma el texto visible del <td>
              return sum + intVal(text);
            }, 0);

          totalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(total);
          pageTotalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(pageTotal);

          thSubtotal.html(pageTotalFormat);
          thTotal.html(totalFormat);
        });
      } else {
        console.warn("No se encontró el TFOOT en la tabla");
      }
    },

    columnDefs: [
      {
        targets: 0,
        visible: false,
        searchable: false
      },
      {
        targets: 2,
        visible: false,
        searchable: false
      },
      {
        targets: 4,
        visible: false,
        searchable: false
      },
      {
        targets: 5,
        visible: false,
        searchable: false
      },
      {
        targets: 26,
        visible: false,
        searchable: false
      },
      {
        targets: 27,
        visible: false,
        searchable: false
      }
    ],
  });

  new DataTable('#tableReportesHistorialCanjes', {
    responsive: true,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },

    footerCallback: function (row, data, start, end, display) {
      let api = this.api();
      let tfoot = $(api.table().footer());

      // Remove the formatting to get integer data for summation
      let intVal = function (i) {
        return typeof i === 'string'
          ? i.replace(/[\$,]/g, '') * 1
          : typeof i === 'number'
            ? i
            : 0;
      };

      if (tfoot.length > 0) {
        let rows = tfoot.find('tr');

        const array = [4];

        array.forEach((column) => {
          let thSubtotal = rows.eq(0).find(`th[data-dt-column="${column}"]`);
          let thTotal = rows.eq(1).find(`th[data-dt-column="${column}"]`);

          total = api
            .column(column)
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          // Total over this page
          pageTotal = api
            .column(column, { page: 'current' })
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          totalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(total);
          pageTotalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(pageTotal);

          thSubtotal.html(pageTotalFormat);
          thTotal.html(totalFormat);
        });
      } else {
        console.warn("No se encontró el TFOOT en la tabla");
      }
    },
  });

  new DataTable('#tableGestionCoord', {
    responsive: true,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },

    footerCallback: function (row, data, start, end, display) {
      let api = this.api();
      let tfoot = $(api.table().footer());

      // Remove the formatting to get integer data for summation
      let intVal = function (i) {
        return typeof i === 'string'
          ? i.replace(/[\$,]/g, '') * 1
          : typeof i === 'number'
            ? i
            : 0;
      };

      if (tfoot.length > 0) {
        let rows = tfoot.find('tr');

        const array = [3, 4, 6, 7, 8, 10, 11];

        array.forEach((column) => {
          let thSubtotal = rows.eq(0).find(`th[data-dt-column="${column}"]`);
          let thTotal = rows.eq(1).find(`th[data-dt-column="${column}"]`);

          total = api
            .column(column)
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          // Total over this page
          pageTotal = api
            .column(column, { page: 'current' })
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          totalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(total);
          pageTotalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(pageTotal);

          thSubtotal.html(pageTotalFormat);
          thTotal.html(totalFormat);
        });
      } else {
        console.warn("No se encontró el TFOOT en la tabla");
      }
    },
  });

  new DataTable('#tableReportesSucursales', {
    responsive: true,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },

    footerCallback: function (row, data, start, end, display) {
      let api = this.api();
      let tfoot = $(api.table().footer());

      // Remove the formatting to get integer data for summation
      let intVal = function (i) {
        return typeof i === 'string'
          ? i.replace(/[\$,]/g, '') * 1
          : typeof i === 'number'
            ? i
            : 0;
      };

      if (tfoot.length > 0) {
        let rows = tfoot.find('tr');

        const array = [3, 4, 6, 7, 8, 9, 11, 12];

        array.forEach((column) => {
          let thSubtotal = rows.eq(0).find(`th[data-dt-column="${column}"]`);
          let thTotal = rows.eq(1).find(`th[data-dt-column="${column}"]`);

          total = api
            .column(column)
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          // Total over this page
          pageTotal = api
            .column(column, { page: 'current' })
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          totalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(total);
          pageTotalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(pageTotal);

          thSubtotal.html(pageTotalFormat);
          thTotal.html(totalFormat);
        });
      } else {
        console.warn("No se encontró el TFOOT en la tabla");
      }
    },
  });

  new DataTable('#tableReporteDesembolsos', {
    responsive: true,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },

    footerCallback: function (row, data, start, end, display) {
      let api = this.api();
      let tfoot = $(api.table().footer());

      // Función para convertir valores
      let intVal = function (i) {
        return typeof i === 'string'
          ? i.replace(/[\$,]/g, '') * 1
          : typeof i === 'number'
            ? i
            : 0;
      };

      if (tfoot.length > 0) {
        let rows = tfoot.find('tr');

        const array = [6, 7, 8, 10, 11, 12, 13];

        array.forEach((column) => {
          let thSubtotal = rows.eq(0).find(`th[data-dt-column="${column}"]`);  // Columna 4 en la primera fila (Subtotal)
          let thTotal = rows.eq(1).find(`th[data-dt-column="${column}"]`);     // Columna 4 en la segunda fila (Total)

          total = api
            .column(column)
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          // Total over this page
          pageTotal = api
            .column(column, { page: 'current' })
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          totalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(total);
          pageTotalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(pageTotal);

          // Ahora escribimos en la columna 4 de ambas filas
          thSubtotal.html(pageTotalFormat);
          thTotal.html(totalFormat);
        });
      } else {
        console.warn("No se encontró el TFOOT en la tabla");
      }
    }
  });

  new DataTable('#tableReporteGastos', {
    responsive: true,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },
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
    
    footerCallback: function (row, data, start, end, display) {
      let api = this.api();
      let tfoot = $(api.table().footer());

      // Función para convertir valores
      let intVal = function (i) {
        return typeof i === 'string'
          ? i.replace(/[\$,]/g, '') * 1
          : typeof i === 'number'
            ? i
            : 0;
      };

      if (tfoot.length > 0) {
        let rows = tfoot.find('tr');

        const array = [4, 5];

        array.forEach((column) => {
          let thSubtotal = rows.eq(0).find(`th[data-dt-column="${column}"]`);  // Columna 4 en la primera fila (Subtotal)
          let thTotal = rows.eq(1).find(`th[data-dt-column="${column}"]`);     // Columna 4 en la segunda fila (Total)

          total = api
            .column(column)
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          // Total over this page
          pageTotal = api
            .column(column, { page: 'current' })
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          totalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(total);
          pageTotalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(pageTotal);

          // Ahora escribimos en la columna 4 de ambas filas
          thSubtotal.html(pageTotalFormat);
          thTotal.html(totalFormat);
        });
      } else {
        console.warn("No se encontró el TFOOT en la tabla");
      }

    }
  });

  new DataTable('#tableReporteIngresos', {
    responsive: true,
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
    },
    footerCallback: function (row, data, start, end, display) {
      let api = this.api();
      let tfoot = $(api.table().footer());

      // Función para convertir valores
      let intVal = function (i) {
        return typeof i === 'string'
          ? i.replace(/[\$,]/g, '') * 1
          : typeof i === 'number'
            ? i
            : 0;
      };

      if (tfoot.length > 0) {
        let rows = tfoot.find('tr');

        const array = [7, 8, 9, 10, 11, 12, 13, 14];

        array.forEach((column) => {
          let thSubtotal = rows.eq(0).find(`th[data-dt-column="${column}"]`);
          let thTotal = rows.eq(1).find(`th[data-dt-column="${column}"]`);

          total = api
            .column(column)
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          // Total over this page
          pageTotal = api
            .column(column, { page: 'current' })
            .data()
            .reduce((a, b) => intVal(a) + intVal(b), 0);

          totalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(total);
          pageTotalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(pageTotal);

          // Ahora escribimos en la columna 4 de ambas filas
          thSubtotal.html(pageTotalFormat);
          thTotal.html(totalFormat);
        });
      } else {
        console.warn("No se encontró el TFOOT en la tabla");
      }
    }
  });
});

new DataTable('#tableArqueosCajas', {
  responsive: true,
  "language": {
    "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
  },

  footerCallback: function (row, data, start, end, display) {
    let api = this.api();
    let tfoot = $(api.table().footer());

    // Remove the formatting to get integer data for summation
    let intVal = function (i) {
      return typeof i === 'string'
        ? i.replace(/[\$,]/g, '') * 1
        : typeof i === 'number'
          ? i
          : 0;
    };

    if (tfoot.length > 0) {
      let rows = tfoot.find('tr');

      // Define the columns to sum, excluding the first column (index 0)
      const array = [ 6,7];

      array.forEach((column) => {
        let thSubtotal = rows.eq(0).find(`th[data-dt-column="${column}"]`);  // Columna en la primera fila (Subtotal)
        let thTotal = rows.eq(1).find(`th[data-dt-column="${column}"]`);     // Columna en la segunda fila (Total)

        // Total over all pages
        let total = api
          .column(column)
          .data()
          .reduce((a, b) => intVal(a) + intVal(b), 0);

        // Total over this page
        let pageTotal = api
          .column(column, { page: 'current' })
          .data()
          .reduce((a, b) => intVal(a) + intVal(b), 0);

        // Format the totals
        let totalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(total);
        let pageTotalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(pageTotal);

        // Ahora escribimos en la columna de ambas filas
        thSubtotal.html(pageTotalFormat);
        thTotal.html(totalFormat);
      });
    } else {
      console.warn("No se encontró el TFOOT en la tabla");
    }
  },
});

new DataTable('#tableCuentasManejo', {
  responsive: true,
  "language": {
    "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
  },

  footerCallback: function (row, data, start, end, display) {
    let api = this.api();
    let tfoot = $(api.table().footer());

    // Función para convertir valores
    let intVal = function (i) {
      return typeof i === 'string'
        ? i.replace(/[\$,]/g, '') * 1
        : typeof i === 'number'
          ? i
          : 0;
    };

    if (tfoot.length > 0) {
      let rows = tfoot.find('tr');

      const array = [5, 6, 7, 8, 10, 11, 12, 13];

      array.forEach((column) => {
        let thSubtotal = rows.eq(0).find(`th[data-dt-column="${column}"]`);  // Columna 4 en la primera fila (Subtotal)
        let thTotal = rows.eq(1).find(`th[data-dt-column="${column}"]`);     // Columna 4 en la segunda fila (Total)

        total = api
          .column(column)
          .data()
          .reduce((a, b) => intVal(a) + intVal(b), 0);

        // Total over this page
        pageTotal = api
          .column(column, { page: 'current' })
          .data()
          .reduce((a, b) => intVal(a) + intVal(b), 0);

        totalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(total);
        pageTotalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(pageTotal);

        // Ahora escribimos en la columna 4 de ambas filas
        thSubtotal.html(pageTotalFormat);
        thTotal.html(totalFormat);
      });
    } else {
      console.warn("No se encontró el TFOOT en la tabla");
    }

  }
});

// new DataTable('#tableCuentasCajasManejo', {
//   responsive: true,
//   "language": {
//     "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
//   },
//   "oLanguage": {
//   "sSearch": '<i class="fa-solid fa-magnifying-glass"></i>'
//   },
//   layout: {
//     topStart: {
//         buttons: [ 
//             {
//                 extend: 'copy',
//                 text:'<i class="fa-regular fa-copy"></i>',
//                 titleAttr:'Copiar',
//                 className:'btn btn-tool-copy push'
//             },

//             {
//                 extend: 'excel',
//                 text:'<i class="fa-regular fa-file-excel"></i>',
//                 titleAttr:'Excel',
//                 className:'btn btn-tool-excel push'
//             },

//             {
//                 extend: 'pdf',
//                 text:'<i class="fa-regular fa-file-pdf"></i>',
//                 titleAttr:'PDF',
//                 className:'btn btn-tool-pdf push'
//             },

//             {
//                 extend: 'print',
//                 text:'<i class="fa-solid fa-print"></i>',
//                 titleAttr:'Imprimir',
//                 className:'btn btn-tool-print push'
//             },

//             {
//                 extend: 'colvis',
//                 text:'<i class="fa-solid fa-filter"></i>',
//                 titleAttr:'Filtrar',
//                 className:'btn btn-tool-colvis push'
//             }
//         ]
// }
//   },

//   footerCallback: function (row, data, start, end, display) {
//     let api = this.api();
//     let tfoot = $(api.table().footer());

//     // Función para convertir valores
//     let intVal = function (i) {
//       return typeof i === 'string'
//         ? i.replace(/[\$,]/g, '') * 1
//         : typeof i === 'number'
//           ? i
//           : 0;
//     };

//     console.log(tfoot);
//     if (tfoot.length > 0) {
//       let rows = tfoot.find('tr');
//       let array = [4,5];
//       array.forEach((column) => {
//         let thSubtotal = rows.eq(0).find(`th[data-dt-column="${column}"]`); 
//         let thTotal = rows.eq(1).find(`th[data-dt-column="${column}"]`);    
//         total = api
//           .column(column)
//           .data()
//           .reduce((a, b) => intVal(a) + intVal(b), 0);

//         // Total over this page
//         pageTotal = api
//           .column(column, { page: 'current' })
//           .data()
//           .reduce((a, b) => intVal(a) + intVal(b), 0);

//         totalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(total);
//         pageTotalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(pageTotal);

//         thSubtotal.html(pageTotalFormat);
//         thTotal.html(totalFormat);
//       });
//     } else {
//       console.warn("No se encontró el TFOOT en la tabla");
//     }

//   },
 
  
// });

new DataTable('#tableCuentasCajasManejo', {
  responsive: true,
  "language": {
    "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
  },

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

  footerCallback: function (row, data, start, end, display) {
    let api = this.api();
    let tfoot = $(api.table().footer());

    // Remove the formatting to get integer data for summation
    let intVal = function (i) {
      return typeof i === 'string'
        ? i.replace(/[\$,]/g, '') * 1
        : typeof i === 'number'
          ? i
          : 0;
    };

    if (tfoot.length > 0) {
      let rows = tfoot.find('tr');

      const array = [4, 5, 6, 7];

      array.forEach((column) => {
        let thSubtotal = rows.eq(0).find(`th[data-dt-column="${column}"]`);  // Columna 4 en la primera fila (Subtotal)
        let thTotal = rows.eq(1).find(`th[data-dt-column="${column}"]`);     // Columna 4 en la segunda fila (Total)

        total = api
          .column(column)
          .data()
          .reduce((a, b) => intVal(a) + intVal(b), 0);

        // Total over this page
        pageTotal = api
          .column(column, { page: 'current' })
          .data()
          .reduce((a, b) => intVal(a) + intVal(b), 0);

        totalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(total);
        pageTotalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(pageTotal);

        // Ahora escribimos en la columna 4 de ambas filas
        thSubtotal.html(pageTotalFormat);
        thTotal.html(totalFormat);
      });
    } else {
      console.warn("No se encontró el TFOOT en la tabla");
    }
  },
});

new DataTable('#tableCuentasTeso', {
  responsive: true,
  "language": {
    "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
  },

  footerCallback: function (row, data, start, end, display) {
    let api = this.api();
    let tfoot = $(api.table().footer());

    // Función para convertir valores
    let intVal = function (i) {
      return typeof i === 'string'
        ? i.replace(/[\$,]/g, '') * 1
        : typeof i === 'number'
          ? i
          : 0;
    };

    console.log(tfoot);
    if (tfoot.length > 0) {
      let rows = tfoot.find('tr');
      let array = [4,5];
      array.forEach((column) => {
        let thSubtotal = rows.eq(0).find(`th[data-dt-column="${column}"]`); 
        let thTotal = rows.eq(1).find(`th[data-dt-column="${column}"]`);    
        total = api
          .column(column)
          .data()
          .reduce((a, b) => intVal(a) + intVal(b), 0);

        // Total over this page
        pageTotal = api
          .column(column, { page: 'current' })
          .data()
          .reduce((a, b) => intVal(a) + intVal(b), 0);

        totalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(total);
        pageTotalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(pageTotal);

        thSubtotal.html(pageTotalFormat);
        thTotal.html(totalFormat);
      });
    } else {
      console.warn("No se encontró el TFOOT en la tabla");
    }

  }
});

$(document).ready( function () {
  $('#tableConsultarMovimientosCuentasCajas').DataTable( {
      responsive: true,
      scrollX:false,
      select: false,
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

      footerCallback: function (row, data, start, end, display) {
        let api = this.api();
        let tfoot = $(api.table().footer());

        // Función para convertir valores
        let intVal = function (i) {
          return typeof i === 'string'
            ? i.replace(/[\$,]/g, '') * 1
            : typeof i === 'number'
              ? i
              : 0;
        };

        if (tfoot.length > 0) {
          let rows = tfoot.find('tr');
          let array = [2,3,4,5];
          array.forEach((column) => {
            let thSubtotal = rows.eq(0).find(`th[data-dt-column="${column}"]`); 
            let thTotal = rows.eq(1).find(`th[data-dt-column="${column}"]`);    
            
            total = api
              .column(column)
              .data()
              .reduce((a, b) => intVal(a) + intVal(b), 0);

            // Total over this page
            pageTotal = api
              .column(column, { page: 'current' })
              .data()
              .reduce((a, b) => intVal(a) + intVal(b), 0);

            console.log(total)
            console.log(pageTotal)
            totalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(total);
            pageTotalFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(pageTotal);

            thSubtotal.html(pageTotalFormat);
            thTotal.html(totalFormat);
          });
        } else {
          console.warn("No se encontró el TFOOT en la tabla");
        }

      }
  } );
} );

$(document).ready(function () {
  if ($('#historialMov').length === 0) {
    return;
  }

  new DataTable('#historialMov', {
    responsive: true,
    language: {
      url: 'https://cdn.datatables.net/plug-ins/1.13.5/i18n/es-MX.json'
    },
    layout: {
      topStart: {
        buttons: [
          {
            extend: 'copy',
            text: '<i class="fa-regular fa-copy"></i>',
            titleAttr: 'Copiar',
            className: 'btn btn-tool-copy push'
          },
          {
            extend: 'excel',
            text: '<i class="fa-regular fa-file-excel"></i>',
            titleAttr: 'Excel',
            className: 'btn btn-tool-excel push'
          },
          {
            extend: 'pdf',
            text: '<i class="fa-regular fa-file-pdf"></i>',
            titleAttr: 'PDF',
            className: 'btn btn-tool-pdf push'
          },
          {
            extend: 'print',
            text: '<i class="fa-solid fa-print"></i>',
            titleAttr: 'Imprimir',
            className: 'btn btn-tool-print push'
          },
          {
            extend: 'colvis',
            text: '<i class="fa-solid fa-filter"></i>',
            titleAttr: 'Filtrar',
            className: 'btn btn-tool-colvis push'
          }
        ]
      }
    },
    oLanguage: {
      sSearch: '<i class="fa-solid fa-magnifying-glass"></i>'
    },
    footerCallback: function () {
      const api = this.api();
      const tfoot = $(api.table().footer());

      const parseValue = function (value) {
        if (value === null || value === undefined) {
          return 0;
        }
        const str = value.toString().replace(/[$,\s-]/g, '');
        const num = parseFloat(str);
        return isNaN(num) ? 0 : num;
      };

      const formatMoney = function (amount) {
        return new Intl.NumberFormat('es-MX', {
          style: 'currency',
          currency: 'MXN'
        }).format(amount);
      };

      const lastSaldo = function (scope) {
        const data = api.column(5, scope).data();
        if (!data.length) {
          return 0;
        }
        return parseValue(data[data.length - 1]);
      };

      if (tfoot.length === 0) {
        return;
      }

      const rows = tfoot.find('tr');
      const amountColumns = [3, 4];

      amountColumns.forEach((column) => {
        const total = api
          .column(column, { search: 'applied' })
          .data()
          .reduce((acc, val) => acc + parseValue(val), 0);

        const pageTotal = api
          .column(column, { page: 'current' })
          .data()
          .reduce((acc, val) => acc + parseValue(val), 0);

        rows.eq(0).find(`th[data-dt-column="${column}"]`).html(
          pageTotal > 0 ? formatMoney(pageTotal) : '-'
        );
        rows.eq(1).find(`th[data-dt-column="${column}"]`).html(
          total > 0 ? formatMoney(total) : '-'
        );
      });

      rows.eq(0).find('th[data-dt-column="5"]').html(formatMoney(lastSaldo({ page: 'current' })));
      rows.eq(1).find('th[data-dt-column="5"]').html(formatMoney(lastSaldo({ search: 'applied' })));
    }
  });
});

