(function () {
    'use strict';

    const selector = [
        '.acciones-config-page input:not([type])',
        '.acciones-config-page input[type="text"]',
        '.acciones-config-page input[type="tel"]',
        '.acciones-config-page input[type="search"]',
        '.acciones-config-page input[type="email"]',
        '.acciones-config-page textarea',
    ].join(',');

    const convertirAMayusculas = function (elemento) {
        if (!elemento || elemento.dataset.noMayusculas === '1') {
            return;
        }

        const inicio = elemento.selectionStart;
        const fin = elemento.selectionEnd;
        const valorMayusculas = elemento.value.toLocaleUpperCase('es-MX');

        if (elemento.value === valorMayusculas) {
            return;
        }

        elemento.value = valorMayusculas;

        if (typeof inicio === 'number' && typeof fin === 'number') {
            elemento.setSelectionRange(inicio, fin);
        }
    };

    document.addEventListener('input', function (event) {
        if (event.target.matches(selector)) {
            convertirAMayusculas(event.target);
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll(selector).forEach(convertirAMayusculas);
    });

    window.subirGestionAlumnosArriba = function () {
        const contenedor = document.querySelector('.contenedor');
        const opciones = { top: 0, left: 0, behavior: 'smooth' };

        if (contenedor && typeof contenedor.scrollTo === 'function') {
            contenedor.scrollTo(opciones);
        }

        if (document.scrollingElement && typeof document.scrollingElement.scrollTo === 'function') {
            document.scrollingElement.scrollTo(opciones);
        }

        window.scrollTo(opciones);
    };
})();
