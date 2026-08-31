$(document).ready(function () {
    var $tableEl = $('#tablePreviewCierre');

    if (!$tableEl.length || $.fn.DataTable.isDataTable($tableEl)) {
        return;
    }

    var exportTitle = $tableEl.data('export-title') || 'Previsualizacion cierre nomina';
    var isMobileTable = window.matchMedia('(max-width: 768px)').matches;

    var exportButtons = [
        {
            extend: 'copy',
            text: '<i class="fa-regular fa-copy"></i>',
            titleAttr: 'Copiar',
            className: 'btn btn-tool-copy push',
            exportOptions: { columns: ':visible' }
        },
        {
            extend: 'excel',
            text: '<i class="fa-regular fa-file-excel"></i>',
            titleAttr: 'Excel',
            className: 'btn btn-tool-excel push',
            title: exportTitle,
            exportOptions: { columns: ':visible' }
        },
        {
            extend: 'pdf',
            text: '<i class="fa-regular fa-file-pdf"></i>',
            titleAttr: 'PDF',
            className: 'btn btn-tool-pdf push',
            title: exportTitle,
            exportOptions: { columns: ':visible' }
        },
        {
            extend: 'print',
            text: '<i class="fa-solid fa-print"></i>',
            titleAttr: 'Imprimir',
            className: 'btn btn-tool-print push',
            title: exportTitle,
            exportOptions: { columns: ':visible' }
        },
        {
            extend: 'colvis',
            text: '<i class="fa-solid fa-filter"></i>',
            titleAttr: 'Columnas',
            className: 'btn btn-tool-colvis push'
        }
    ];

    $tableEl.DataTable({
        responsive: false,
        scrollX: true,
        scrollCollapse: true,
        autoWidth: false,
        select: false,
        order: [[2, 'asc']],
        scrollY: isMobileTable ? false : '480px',
        paging: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Todos']],
        fixedHeader: false,
        layout: {
            topStart: ['pageLength', { buttons: exportButtons }],
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
        columnDefs: [
            {
                targets: 0,
                orderable: false,
                className: 'text-center col-accion-recibo',
                width: '52px'
            },
            {
                targets: 1,
                orderable: false,
                className: 'text-center col-accion-entrega',
                width: '64px'
            },
            {
                targets: '_all',
                defaultContent: ''
            }
        ]
    });
});
