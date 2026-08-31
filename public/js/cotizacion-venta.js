/**
 * cotizacion-venta.js
 * -------------------
 * Búsqueda por coincidencias (JSON) + agregar producto al borrador sin recargar.
 *
 * Requiere:
 *  - Meta tag csrf-token en el layout
 *  - #cotizacion-busqueda-productos con data-url-* 
 *  - Contenedores #cotizacion-lineas-tbody y #cotizacion-totales
 */
(function () {
    'use strict';

    var SELECTOR_FORM_AGREGAR = '.js-form-agregar-cotizacion';
    var SELECTOR_FORM_ACTUALIZAR = '.js-form-actualizar-linea-cotizacion';
    var SELECTOR_CONTENEDOR_LINEAS = '#cotizacion-lineas-tbody';
    var SELECTOR_CONTENEDOR_TOTALES = '#cotizacion-totales';
    var SELECTOR_ALERTA = '#cotizacion-alerta-ajax';
    var SELECTOR_MODAL_BORRADOR = '#cotizacion-borrador-panel';
    var SELECTOR_BUSQUEDA = '#cotizacion-busqueda-productos';
    var DEBOUNCE_MS = 300;

    var temporizadorBusqueda = null;
    var peticionCoincidencias = null;

    function obtenerCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : null;
    }

    function sincronizarEncabezadoEnFormData(formAgregar, formData) {
        var panel = document.querySelector(SELECTOR_MODAL_BORRADOR);
        if (!panel) {
            return;
        }

        var encabezado = panel.querySelector('#cotizacion-encabezado');
        if (!encabezado) {
            return;
        }

        ['cliente_id', 'fecha_vencimiento', 'observaciones', 'descuento', 'tiempo_entrega', 'persona_atencion', 'moneda_id', 'condicion_pago_id', 'tipo_flete_id', 'tipo_iva_id', 'importe_flete'].forEach(function (nombre) {
            var input = encabezado.querySelector('[name="' + nombre + '"]');
            if (input) {
                formData.set(nombre, input.value);
            }
        });
        var checkFlete = encabezado.querySelector('input[type="checkbox"][name="flete_en_precios"]');
        formData.set('flete_en_precios', (checkFlete && checkFlete.checked) ? '1' : '0');
        var checkIva = encabezado.querySelector('input[type="checkbox"][name="iva_en_precios"]');
        formData.set('iva_en_precios', (checkIva && checkIva.checked) ? '1' : '0');
    }

    function mostrarAlerta(mensaje, tipo) {
        var contenedor = document.querySelector(SELECTOR_ALERTA);
        if (!contenedor) {
            return;
        }

        contenedor.className = 'alert alert-' + tipo + ' border-0 rounded-3 py-2 mb-2 fs-8';
        contenedor.textContent = mensaje;
        contenedor.classList.remove('d-none');

        window.setTimeout(function () {
            contenedor.classList.add('d-none');
        }, 3500);
    }

    function actualizarVistaCotizacion(data) {
        var tbody = document.querySelector(SELECTOR_CONTENEDOR_LINEAS);
        var totales = document.querySelector(SELECTOR_CONTENEDOR_TOTALES);

        if (tbody && data.html_lineas) {
            tbody.innerHTML = data.html_lineas;
            if (typeof window.inicializarValidacionExistenciaCotizacion === 'function') {
                window.inicializarValidacionExistenciaCotizacion();
            }
        }

        if (totales && data.html_totales) {
            totales.innerHTML = data.html_totales;
        }
    }

    function recalcularTotalesCotizacion() {
        var encabezado = document.querySelector('#cotizacion-encabezado');
        if (!encabezado) {
            return;
        }

        var url = encabezado.getAttribute('data-url-recalcular');
        var csrf = obtenerCsrfToken();
        if (!url || !csrf) {
            return;
        }

        // Sin líneas aún no hay totales que refrescar.
        if (!document.querySelector(SELECTOR_CONTENEDOR_LINEAS + ' tr.cotizacion-linea')) {
            return;
        }

        var formData = new FormData();
        formData.set('_token', csrf);

        var vendedor = document.querySelector('#form-guardar-cotizacion [name="vendedor_id"]')
            || document.querySelector('[name="vendedor_id"]');
        if (vendedor) {
            formData.set('vendedor_id', vendedor.value);
        }

        sincronizarEncabezadoEnFormData(null, formData);

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
            .then(function (respuesta) {
                return respuesta.json().then(function (data) {
                    return { ok: respuesta.ok, data: data };
                });
            })
            .then(function (resultado) {
                if (!resultado.ok || !resultado.data.success) {
                    return;
                }
                actualizarVistaCotizacion(resultado.data);
                if (typeof window.syncCotizacionGuardarEncabezado === 'function') {
                    window.syncCotizacionGuardarEncabezado();
                }
            })
            .catch(function () {
                // Silencioso: el usuario puede forzar con el botón rotar.
            });
    }

    var temporizadorRecalculo = null;

    function programarRecalculoTotales() {
        if (temporizadorRecalculo) {
            window.clearTimeout(temporizadorRecalculo);
        }
        temporizadorRecalculo = window.setTimeout(recalcularTotalesCotizacion, 350);
    }

    function inicializarRecalculoEncabezado() {
        var encabezado = document.querySelector('#cotizacion-encabezado');
        if (!encabezado) {
            return;
        }

        encabezado.addEventListener('change', function (evento) {
            var t = evento.target;
            if (!t || !t.name) {
                return;
            }
            var relevantes = {
                tipo_iva_id: 1,
                iva_en_precios: 1,
                flete_en_precios: 1,
                importe_flete: 1,
                descuento: 1,
                moneda_id: 1
            };
            if (relevantes[t.name]) {
                programarRecalculoTotales();
            }
        });

        encabezado.addEventListener('input', function (evento) {
            var t = evento.target;
            if (!t || !t.name) {
                return;
            }
            if (t.name === 'importe_flete' || t.name === 'descuento') {
                programarRecalculoTotales();
            }
        });
    }

    function alternarEstadoBoton(boton, ocupado) {
        if (!boton) {
            return;
        }

        boton.disabled = ocupado;
        if (ocupado) {
            boton.dataset.textoOriginal = boton.innerHTML;
            boton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        } else if (boton.dataset.textoOriginal) {
            boton.innerHTML = boton.dataset.textoOriginal;
        }
    }

    function agregarProductoAjax(form, opciones) {
        opciones = opciones || {};
        var csrf = obtenerCsrfToken();
        if (!csrf) {
            mostrarAlerta('No se encontró el token CSRF. Recargue la página.', 'danger');
            return;
        }

        var boton = form.querySelector('button[type="submit"]');
        var formData = new FormData(form);

        sincronizarEncabezadoEnFormData(form, formData);
        alternarEstadoBoton(boton, true);

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
            .then(function (respuesta) {
                return respuesta.json().then(function (data) {
                    return { ok: respuesta.ok, data: data };
                });
            })
            .then(function (resultado) {
                if (!resultado.ok) {
                    var mensaje = resultado.data.message || 'No se pudo agregar el producto.';
                    if (resultado.data.errors) {
                        var lista = [];
                        Object.keys(resultado.data.errors).forEach(function (campo) {
                            lista = lista.concat(resultado.data.errors[campo]);
                        });
                        if (lista.length) {
                            mensaje = lista.join(' ');
                        }
                    }
                    throw new Error(mensaje);
                }

                actualizarVistaCotizacion(resultado.data);
                mostrarAlerta(resultado.data.message || 'Producto agregado.', 'success');

                if (typeof window.syncCotizacionGuardarEncabezado === 'function') {
                    window.syncCotizacionGuardarEncabezado();
                }

                if (typeof opciones.alExito === 'function') {
                    opciones.alExito();
                }

                var detalle = document.getElementById('cotizacion-detalle-titulo');
                if (detalle) {
                    detalle.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            })
            .catch(function (error) {
                mostrarAlerta(error.message || 'Error al agregar el producto.', 'danger');
                var alerta = document.querySelector(SELECTOR_ALERTA);
                if (alerta) {
                    alerta.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            })
            .finally(function () {
                alternarEstadoBoton(boton, false);
            });
    }

    function escaparHtml(texto) {
        var div = document.createElement('div');
        div.textContent = texto == null ? '' : String(texto);
        return div.innerHTML;
    }

    function formatearMoneda(valor) {
        var numero = parseFloat(valor);
        if (isNaN(numero)) {
            numero = 0;
        }
        return '$' + numero.toFixed(2);
    }

    function obtenerContenedorBusqueda() {
        return document.querySelector(SELECTOR_BUSQUEDA);
    }

    function esModoProduccion(contenedor) {
        return !!contenedor && contenedor.dataset.tipoVenta === 'PRODUCCION';
    }

    function limpiarProductoSeleccionado() {
        var panel = document.getElementById('cotizacion-producto-seleccionado');
        if (panel) {
            panel.innerHTML = '';
            panel.classList.add('d-none');
        }
    }

    function ocultarSugerencias() {
        var lista = document.getElementById('cotizacion-sugerencias');
        if (lista) {
            lista.innerHTML = '';
            lista.classList.add('d-none');
        }
    }

    function mostrarSugerencias(coincidencias) {
        var lista = document.getElementById('cotizacion-sugerencias');
        if (!lista) {
            return;
        }

        if (!coincidencias.length) {
            lista.innerHTML = '<div class="list-group-item text-muted">Sin coincidencias</div>';
            lista.classList.remove('d-none');
            return;
        }

        lista.innerHTML = coincidencias.map(function (item) {
            return '<button type="button" class="list-group-item list-group-item-action" data-producto-id="' +
                escaparHtml(item.producto_id) + '">' + escaparHtml(item.etiqueta) + '</button>';
        }).join('');
        lista.classList.remove('d-none');
    }

    function buscarCoincidencias(contenedor, termino) {
        var produccion = esModoProduccion(contenedor);
        var selectUbicacion = contenedor.querySelector('#cotizacion-id-ubicacion');
        var idUbicacion = selectUbicacion ? selectUbicacion.value : '';

        if (termino.length < 2 || (!produccion && !idUbicacion)) {
            ocultarSugerencias();
            return;
        }

        if (peticionCoincidencias) {
            peticionCoincidencias.abort();
        }

        var controlador = new AbortController();
        peticionCoincidencias = controlador;

        var url = produccion
            ? contenedor.dataset.urlCoincidenciasProduccion + '?q=' + encodeURIComponent(termino)
            : contenedor.dataset.urlCoincidencias +
                '?id_ubicacion=' + encodeURIComponent(idUbicacion) +
                '&q=' + encodeURIComponent(termino);

        fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            signal: controlador.signal
        })
            .then(function (respuesta) {
                return respuesta.json();
            })
            .then(function (data) {
                if (!data.success) {
                    throw new Error(data.message || 'No se pudieron cargar coincidencias.');
                }
                mostrarSugerencias(data.coincidencias || []);
            })
            .catch(function (error) {
                if (error.name === 'AbortError') {
                    return;
                }
                ocultarSugerencias();
            })
            .finally(function () {
                peticionCoincidencias = null;
            });
    }

    function renderizarProductoSeleccionado(contenedor, producto) {
        var panel = document.getElementById('cotizacion-producto-seleccionado');
        if (!panel) {
            return;
        }

        var idUbicacion = contenedor.querySelector('#cotizacion-id-ubicacion').value;
        var vendedorId = contenedor.dataset.vendedorId || '';
        var urlAgregar = contenedor.dataset.urlAgregar;
        var claseFila = producto.sin_existencia ? 'text-danger' : '';
        var badgeSinStock = producto.sin_existencia
            ? '<span class="badge bg-danger ms-1">Sin existencia</span>'
            : '';

        var descDefault = producto.descripcion || producto.nombre || '';
        panel.innerHTML =
            '<form method="POST" action="' + escaparHtml(urlAgregar) + '" class="js-form-agregar-cotizacion">' +
                '<input type="hidden" name="_token" value="' + escaparHtml(obtenerCsrfToken()) + '">' +
                '<input type="hidden" name="vendedor_id" value="' + escaparHtml(vendedorId) + '">' +
                '<input type="hidden" name="id_ubicacion" value="' + escaparHtml(idUbicacion) + '">' +
                '<input type="hidden" name="producto_id" value="' + escaparHtml(producto.producto_id) + '">' +
                '<input type="hidden" name="precio_unitario" value="' + escaparHtml(producto.precio_unitario) + '">' +
                '<div class="row g-2 align-items-center">' +
                    '<div class="col-md-4 ' + claseFila + '">' +
                        '<div class="fw-semibold fs-8">' + escaparHtml(producto.sku || '—') + ' — ' + escaparHtml(producto.nombre) + badgeSinStock + '</div>' +
                        '<div class="text-muted fs-8">Existencia: <strong>' + escaparHtml(Number(producto.disponible).toFixed(2)) + '</strong> · Precio: <strong>' + formatearMoneda(producto.precio_unitario) + '</strong></div>' +
                    '</div>' +
                    '<div class="col-md-4">' +
                        '<label class="form-label fs-8 mb-1">Descripción</label>' +
                        '<input type="text" name="descripcion" class="form-control form-control-sm" maxlength="1000" ' +
                            'value="' + escaparHtml(descDefault) + '" placeholder="Descripción por producto" autocomplete="off">' +
                    '</div>' +
                    '<div class="col-md-4">' +
                        '<div class="d-flex gap-2 align-items-end justify-content-md-end">' +
                            '<div>' +
                                '<label class="form-label fs-8 mb-1 text-nowrap">Cantidad</label>' +
                                '<input type="number" name="cantidad" class="form-control form-control-sm" style="width:90px" value="1" min="0.01" step="0.01" required>' +
                            '</div>' +
                            '<button type="submit" class="btn btn-blue btn-sm text-nowrap"><i class="fa-solid fa-plus"></i> Agregar</button>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</form>';

        panel.classList.remove('d-none');
        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function renderizarProductoProduccion(contenedor, data) {
        var panel = document.getElementById('cotizacion-producto-seleccionado');
        if (!panel) {
            return;
        }

        var vendedorId = contenedor.dataset.vendedorId || '';
        var urlAgregar = contenedor.dataset.urlAgregar;
        var producto = data.producto || {};
        var materiales = data.materiales || [];

        var filasMateriales = materiales.length
            ? materiales.map(function (m) {
                var claseFila = m.suficiente ? '' : 'table-danger';
                var badge = m.suficiente
                    ? '<span class="badge bg-success">OK</span>'
                    : '<span class="badge bg-danger">Falta ' + Number(m.faltante).toFixed(2) + '</span>';
                return '<tr class="' + claseFila + '">' +
                    '<td class="fs-8">' + escaparHtml(m.sku || '—') + ' — ' + escaparHtml(m.nombre) + '</td>' +
                    '<td class="fs-8 text-end">' + Number(m.requerido).toFixed(2) + ' ' + escaparHtml(m.unidad || '') + '</td>' +
                    '<td class="fs-8 text-end">' + Number(m.disponible).toFixed(2) + '</td>' +
                    '<td class="fs-8 text-center">' + badge + '</td>' +
                    '</tr>';
            }).join('')
            : '<tr><td colspan="4" class="text-muted fs-8 text-center py-2">La receta no tiene materiales registrados.</td></tr>';

        var precioCatalogo = Number(producto.precio_unitario || 0);
        var sinPrecio = !(precioCatalogo > 0);

        var alertaMateriales;
        if (!data.tiene_receta && data.fabricacion_por_especificacion) {
            var urlReceta = '/produccion/recetas?producto_id=' + encodeURIComponent(producto.producto_id || '') + '&nueva=1';
            alertaMateriales = '<div class="alert alert-info border-0 py-1 px-2 fs-8 mb-2 d-flex flex-wrap align-items-center gap-2">' +
                '<span><i class="fa-solid fa-info-circle me-1"></i> Producto fabricable por especificación (tubo / flange / conexión). No tiene receta de materiales; se puede agregar a la cotización.</span>' +
                '<a class="btn btn-outline-primary btn-sm py-0 px-2" href="' + urlReceta + '" target="_blank" rel="noopener">' +
                '<i class="fa-solid fa-flask me-1"></i>Añadir receta</a></div>';
        } else if (data.producible) {
            alertaMateriales = '<div class="alert alert-success border-0 py-1 px-2 fs-8 mb-2"><i class="fa-solid fa-circle-check me-1"></i> Hay materiales suficientes para producir la cantidad indicada.</div>';
        } else {
            alertaMateriales = '<div class="alert alert-warning border-0 py-1 px-2 fs-8 mb-2"><i class="fa-solid fa-triangle-exclamation me-1"></i> Faltan materiales para producir la cantidad indicada. Se agregará de todas formas; la orden quedará en espera de materiales / OC.</div>';
        }

        var alertaPrecio = sinPrecio
            ? '<div class="alert alert-danger border-0 py-1 px-2 fs-8 mb-2"><i class="fa-solid fa-ban me-1"></i> Este producto no tiene precio de venta. Capture el precio unitario para poder agregarlo a la cotización.</div>'
            : '';

        var tablaMateriales = '';
        if (data.tiene_receta) {
            tablaMateriales = '<div class="table-responsive mb-2"><table class="table table-sm table-bordered mb-0">' +
                '<thead><tr><th class="fs-8">Material</th><th class="fs-8 text-end">Requerido</th><th class="fs-8 text-end">Disponible</th><th class="fs-8 text-center">Estado</th></tr></thead>' +
                '<tbody>' + filasMateriales + '</tbody>' +
            '</table></div>';
        }

        var descDefaultProd = producto.descripcion || producto.nombre || '';
        panel.innerHTML =
            '<div class="d-flex justify-content-between align-items-start mb-2">' +
                '<div><div class="fw-semibold fs-8"><i class="fa-solid fa-industry me-1"></i>' + escaparHtml(producto.sku || '—') + ' — ' + escaparHtml(producto.nombre) + '</div>' +
                '<div class="text-muted fs-8">Producto a fabricar</div></div>' +
            '</div>' +
            alertaMateriales +
            alertaPrecio +
            tablaMateriales +
            '<form method="POST" action="' + escaparHtml(urlAgregar) + '" class="js-form-agregar-cotizacion">' +
                '<input type="hidden" name="_token" value="' + escaparHtml(obtenerCsrfToken()) + '">' +
                '<input type="hidden" name="vendedor_id" value="' + escaparHtml(vendedorId) + '">' +
                '<input type="hidden" name="tipo_venta" value="PRODUCCION">' +
                '<input type="hidden" name="producto_id" value="' + escaparHtml(producto.producto_id) + '">' +
                '<div class="row g-2 align-items-end">' +
                    '<div class="col-md-5">' +
                        '<label class="form-label fs-8 mb-1">Descripción</label>' +
                        '<input type="text" name="descripcion" class="form-control form-control-sm" maxlength="1000" ' +
                            'value="' + escaparHtml(descDefaultProd) + '" placeholder="Descripción por producto" autocomplete="off">' +
                    '</div>' +
                    '<div class="col-md-2">' +
                        '<label class="form-label fs-8 mb-1 text-nowrap">Cantidad</label>' +
                        '<input type="number" name="cantidad" class="form-control form-control-sm" value="' + escaparHtml(data.cantidad || 1) + '" min="0.01" step="0.01" required data-recalcular-materiales="1">' +
                    '</div>' +
                    '<div class="col-md-3">' +
                        '<label class="form-label fs-8 mb-1 text-nowrap">Precio unitario</label>' +
                        '<input type="number" name="precio_unitario" class="form-control form-control-sm' + (sinPrecio ? ' is-invalid' : '') + '" value="' + (sinPrecio ? '' : escaparHtml(precioCatalogo.toFixed(2))) + '" min="0.01" step="0.01" required title="Precio de venta del tubo">' +
                    '</div>' +
                    '<div class="col-md-2">' +
                        '<button type="submit" class="btn btn-blue btn-sm text-nowrap w-100"><i class="fa-solid fa-plus"></i> Agregar</button>' +
                    '</div>' +
                '</div>' +
            '</form>';

        panel.classList.remove('d-none');
        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        var inputCantidad = panel.querySelector('[data-recalcular-materiales]');
        if (inputCantidad) {
            inputCantidad.addEventListener('change', function () {
                cargarMaterialesProduccion(contenedor, producto.producto_id, parseFloat(inputCantidad.value || '1'));
            });
        }
    }

    function cargarMaterialesProduccion(contenedor, productoId, cantidad) {
        var urlBase = contenedor.dataset.urlMateriales;
        var url = urlBase + '/' + encodeURIComponent(productoId) + '/materiales?cantidad=' + encodeURIComponent(cantidad || 1);

        fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (respuesta) {
                return respuesta.json().then(function (data) {
                    return { ok: respuesta.ok, data: data };
                });
            })
            .then(function (resultado) {
                if (!resultado.ok || !resultado.data.success) {
                    throw new Error((resultado.data && resultado.data.message) || 'No se pudo cargar la receta.');
                }
                if (!resultado.data.tiene_receta && !resultado.data.es_producible && !resultado.data.fabricacion_por_especificacion) {
                    limpiarProductoSeleccionado();
                    mostrarAlerta('El producto no es fabricable (sin receta ni especificación de tubo/flange/conexión).', 'warning');
                    return;
                }
                renderizarProductoProduccion(contenedor, resultado.data);
            })
            .catch(function (error) {
                mostrarAlerta(error.message || 'Error al cargar la receta.', 'danger');
            });
    }

    function cargarProductoSeleccionado(contenedor, productoId, etiqueta) {
        var inputBuscar = contenedor.querySelector('#cotizacion-buscar-producto');

        ocultarSugerencias();
        if (inputBuscar && etiqueta) {
            inputBuscar.value = etiqueta;
        }

        if (esModoProduccion(contenedor)) {
            cargarMaterialesProduccion(contenedor, productoId, 1);
            return;
        }

        var idUbicacion = contenedor.querySelector('#cotizacion-id-ubicacion').value;
        var urlBase = contenedor.dataset.urlDetalle;
        var url = urlBase + '/' + encodeURIComponent(productoId) + '?id_ubicacion=' + encodeURIComponent(idUbicacion);

        fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (respuesta) {
                return respuesta.json().then(function (data) {
                    return { ok: respuesta.ok, data: data };
                });
            })
            .then(function (resultado) {
                if (!resultado.ok || !resultado.data.success) {
                    throw new Error((resultado.data && resultado.data.message) || 'No se pudo cargar el producto.');
                }
                renderizarProductoSeleccionado(contenedor, resultado.data.producto);
            })
            .catch(function (error) {
                limpiarProductoSeleccionado();
                mostrarAlerta(error.message || 'Error al cargar el producto.', 'danger');
            });
    }

    function inicializarBusquedaProductos() {
        var contenedor = obtenerContenedorBusqueda();
        if (!contenedor) {
            return;
        }

        var selectUbicacion = contenedor.querySelector('#cotizacion-id-ubicacion');
        var inputBuscar = contenedor.querySelector('#cotizacion-buscar-producto');
        var listaSugerencias = contenedor.querySelector('#cotizacion-sugerencias');

        if (!inputBuscar) {
            return;
        }

        if (selectUbicacion) {
            selectUbicacion.addEventListener('change', function () {
                var habilitado = !!selectUbicacion.value;
                inputBuscar.disabled = !habilitado;
                inputBuscar.value = '';
                limpiarProductoSeleccionado();
                ocultarSugerencias();
            });
        }

        inputBuscar.addEventListener('input', function () {
            limpiarProductoSeleccionado();
            var termino = inputBuscar.value.trim();

            if (temporizadorBusqueda) {
                window.clearTimeout(temporizadorBusqueda);
            }

            if (termino.length < 2) {
                ocultarSugerencias();
                return;
            }

            temporizadorBusqueda = window.setTimeout(function () {
                buscarCoincidencias(contenedor, termino);
            }, DEBOUNCE_MS);
        });

        if (listaSugerencias) {
            listaSugerencias.addEventListener('click', function (evento) {
                var boton = evento.target.closest('[data-producto-id]');
                if (!boton) {
                    return;
                }
                cargarProductoSeleccionado(
                    contenedor,
                    boton.getAttribute('data-producto-id'),
                    boton.textContent.trim()
                );
            });
        }

        document.addEventListener('click', function (evento) {
            if (!contenedor.contains(evento.target)) {
                ocultarSugerencias();
            }
        });
    }

    function inicializarAgregar() {
        document.addEventListener('submit', function (evento) {
            var form = evento.target;
            if (!form.matches) {
                return;
            }

            var esAgregar = form.matches(SELECTOR_FORM_AGREGAR);
            var esActualizar = form.matches(SELECTOR_FORM_ACTUALIZAR);
            if (!esAgregar && !esActualizar) {
                return;
            }

            evento.preventDefault();

            var contenedor = obtenerContenedorBusqueda();
            var inputBuscar = contenedor ? contenedor.querySelector('#cotizacion-buscar-producto') : null;

            agregarProductoAjax(form, {
                alExito: function () {
                    if (esAgregar) {
                        if (inputBuscar) {
                            inputBuscar.value = '';
                        }
                        limpiarProductoSeleccionado();
                        ocultarSugerencias();
                    }
                    inicializarValidacionExistenciaLineas();
                }
            });
        });
    }

    /**
     * Marca la fila en rojo si la cantidad supera la existencia disponible.
     * @param {HTMLInputElement} inputCantidad
     */
    function validarExistenciaFila(inputCantidad) {
        var fila = inputCantidad.closest('tr.cotizacion-linea');
        if (!fila) {
            return;
        }

        if (fila.getAttribute('data-produccion') === '1') {
            return;
        }

        var existencia = parseFloat(fila.getAttribute('data-existencia') || '0');
        var cantidad = parseFloat(inputCantidad.value || '0');
        var excede = !isNaN(cantidad) && cantidad > existencia;
        var alerta = fila.querySelector('.cotizacion-existencia-alerta');

        fila.classList.toggle('table-danger', excede);
        inputCantidad.classList.toggle('is-invalid', excede);

        if (alerta) {
            if (excede) {
                alerta.classList.remove('d-none');
                alerta.textContent = 'No hay tantos en existencia (disponible: ' + existencia.toFixed(2) + ').';
            } else {
                alerta.classList.add('d-none');
            }
        }
    }

    function inicializarValidacionExistenciaLineas() {
        document.querySelectorAll('.js-cotizacion-cantidad').forEach(function (input) {
            validarExistenciaFila(input);
        });
    }

    function registrarValidacionExistenciaLineas() {
        document.addEventListener('input', function (evento) {
            var input = evento.target;
            if (input.matches && input.matches('.js-cotizacion-cantidad')) {
                validarExistenciaFila(input);
            }
        });

        document.addEventListener('change', function (evento) {
            var input = evento.target;
            if (input.matches && input.matches('.js-cotizacion-cantidad')) {
                validarExistenciaFila(input);
            }
        });
    }

    function inicializar() {
        inicializarAgregar();
        inicializarBusquedaProductos();
        registrarValidacionExistenciaLineas();
        inicializarValidacionExistenciaLineas();
        inicializarRecalculoEncabezado();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializar);
    } else {
        inicializar();
    }

    window.inicializarValidacionExistenciaCotizacion = inicializarValidacionExistenciaLineas;
})();
