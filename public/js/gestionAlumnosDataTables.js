(function () {
    const idiomaEspanol = 'https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json';

    const botonesCatalogo = [
        {
            extend: 'copy',
            text: '<i class="fa-regular fa-copy"></i>',
            titleAttr: 'Copiar',
            className: 'btn btn-tool-copy push',
        },
        {
            extend: 'excel',
            text: '<i class="fa-regular fa-file-excel"></i>',
            titleAttr: 'Excel',
            className: 'btn btn-tool-excel push',
        },
        {
            extend: 'pdf',
            text: '<i class="fa-regular fa-file-pdf"></i>',
            titleAttr: 'PDF',
            className: 'btn btn-tool-pdf push',
        },
        {
            extend: 'print',
            text: '<i class="fa-solid fa-print"></i>',
            titleAttr: 'Imprimir',
            className: 'btn btn-tool-print push',
        },
        {
            extend: 'colvis',
            text: '<i class="fa-solid fa-filter"></i>',
            titleAttr: 'Filtrar',
            className: 'btn btn-tool-colvis push',
        },
    ];

    const existeDataTables = function () {
        return typeof window.DataTable === 'function' || Boolean(window.jQuery && window.jQuery.fn && window.jQuery.fn.DataTable);
    };

    const tablaInicializada = function (selector) {
        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.DataTable) {
            if (window.jQuery.fn.DataTable.isDataTable) {
                return window.jQuery.fn.DataTable.isDataTable(selector);
            }
            if (window.jQuery.fn.dataTable && window.jQuery.fn.dataTable.isDataTable) {
                return window.jQuery.fn.dataTable.isDataTable(selector);
            }
        }

        return typeof window.DataTable === 'function'
            && typeof window.DataTable.isDataTable === 'function'
            && window.DataTable.isDataTable(selector);
    };

    const destruir = function (selector) {
        if (!existeDataTables() || !tablaInicializada(selector)) {
            return;
        }

        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.DataTable) {
            window.jQuery(selector).DataTable().destroy();
            return;
        }

        new window.DataTable(selector).destroy();
    };

    const inicializar = function (selector, opciones = {}) {
        const tabla = document.querySelector(selector);
        if (!tabla || !existeDataTables()) {
            return null;
        }

        destruir(selector);

        const configuracion = {
            responsive: true,
            language: {
                url: idiomaEspanol,
            },
            layout: {
                topStart: {
                    buttons: botonesCatalogo,
                },
            },
            oLanguage: {
                sSearch: '<i class="fa-solid fa-magnifying-glass"></i>',
            },
            ...opciones,
        };

        if (typeof window.DataTable === 'function') {
            return new window.DataTable(selector, configuracion);
        }

        return window.jQuery(selector).DataTable(configuracion);
    };

    window.GestionAlumnosDataTables = {
        init: inicializar,
        destroy: destruir,
    };
})();
