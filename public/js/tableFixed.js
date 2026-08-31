$(document).ready(function () {
    var isMobileTable = window.matchMedia('(max-width: 768px)').matches;

    // FixedColumns 5 requiere contenedor dt-scroll (scrollX/scrollY).
    // scrollY mantiene el thead dentro del viewport de la tabla;
    // FixedHeader fija el thead al hacer scroll de la página.
    var table = $('#table').DataTable({
        responsive: false,
        scrollX: true,
        scrollCollapse: true,
        autoWidth: false,
        select: true,
        order: [[]],
        scrollY: isMobileTable ? false : '500px',
        paging: true,
        columnDefs: [
            {
                targets: 0,
                orderable: false,
                className: 'td-actions',
                width: isMobileTable ? '120px' : '140px'
            },
            {
                targets: 1,
                width: '70px'
            },
            {
                targets: 2,
                className: 'td-nombre',
                width: isMobileTable ? '160px' : '220px'
            }
        ],
        fixedColumns: isMobileTable ? false : { start: 3, end: 0 },
        fixedHeader: isMobileTable
            ? false
            : {
                header: true,
                footer: false
            },
        layout: {
            topStart: [
                'pageLength',
                {
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
            ],
            topEnd: 'search',
            bottomStart: 'info',
            bottomEnd: 'paging'
        },
        oLanguage: {
            sSearch: '<i class="fa-solid fa-magnifying-glass"></i>'
        },
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.5/i18n/es-MX.json'
        },
        initComplete: function () {
            adjustEmpleadosTable(this.api());
        }
    });

    function adjustEmpleadosTable(api) {
        api = api || table;
        api.columns.adjust();

        // FixedColumns 5 no tiene relayout(); reaplicar sticky vía API start()
        if (!isMobileTable && typeof api.fixedColumns === 'function') {
            var fc = api.settings()[0]._fixedColumns;
            if (fc && typeof fc.start === 'function') {
                fc.start(fc.start());
            }
        }

        if (typeof api.fixedHeader === 'function') {
            try {
                var fh = api.fixedHeader();
                if (fh && typeof fh.adjust === 'function') {
                    fh.adjust();
                }
            } catch (e) {
                // FixedHeader puede no estar activo con scrollY
            }
        }
    }

    $(window).on('resize', function () {
        adjustEmpleadosTable();
    });

    table.on('draw.dt column-visibility.dt length.dt', function () {
        adjustEmpleadosTable();
    });

    table.on('click', 'tbody tr', function (e) {
        if ($(e.target).closest('button, a, input, label, select, textarea').length) {
            return;
        }
        e.currentTarget.classList.toggle('selected');
    });

    var selectedButton = document.querySelector('#button');
    if (selectedButton) {
        selectedButton.addEventListener('click', function () {
            alert(table.rows('.selected').data().length + ' row(s) selected');
        });
    }
});
