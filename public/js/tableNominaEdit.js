$(document).ready(function () {
    var $tableEl = $('#tableNomina');

    if (!$tableEl.length || $.fn.DataTable.isDataTable($tableEl)) {
        return;
    }

    var isMobileTable = window.matchMedia('(max-width: 768px)').matches;

    var table = $tableEl.DataTable({
        responsive: false,
        scrollX: true,
        scrollCollapse: true,
        autoWidth: false,
        select: true,
        order: [[1, 'asc']],
        scrollY: isMobileTable ? false : '500px',
        paging: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Todos']],
        columnDefs: [
            {
                targets: 0,
                orderable: false,
                className: 'td-actions',
                width: '52px'
            }
        ],
        fixedColumns: isMobileTable ? undefined : { start: 3 },
        fixedHeader: false,
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
        }
    });

    table.on('length.dt', function () {
        table.columns.adjust();
    });

    table.on('click', 'tbody tr', function (e) {
        if ($(e.target).closest('button, a, input, label, select, textarea').length) {
            return;
        }

        e.currentTarget.classList.toggle('selected');
    });

    var filterButtons = document.querySelectorAll('[data-fiscal-filter-toggle]');
    if (!filterButtons.length) {
        return;
    }

    var filtroFiscalCeroActivo = false;

    $.fn.dataTable.ext.search.push(function (settings, searchData, index) {
        if (settings.nTable.id !== 'tableNomina') {
            return true;
        }
        if (!filtroFiscalCeroActivo) {
            return true;
        }

        var api = new $.fn.dataTable.Api(settings);
        var row = api.row(index).node();
        return row && row.getAttribute('data-fiscal-cero') === '1';
    });

    function actualizarBotonesFiltroFiscal() {
        filterButtons.forEach(function (btn) {
            var label = filtroFiscalCeroActivo
                ? btn.getAttribute('data-label-todos')
                : btn.getAttribute('data-label-filtrar');

            btn.classList.toggle('active', filtroFiscalCeroActivo);
            btn.innerHTML = '<i class="fa-solid fa-filter"></i> ' + label;
        });
    }

    function alternarFiltroFiscalCero() {
        filtroFiscalCeroActivo = !filtroFiscalCeroActivo;
        table.draw();
        actualizarBotonesFiltroFiscal();

        if (filtroFiscalCeroActivo) {
            document.querySelector('.nomina-edit-table-card')?.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    }

    filterButtons.forEach(function (btn) {
        btn.addEventListener('click', alternarFiltroFiscalCero);
    });
});
