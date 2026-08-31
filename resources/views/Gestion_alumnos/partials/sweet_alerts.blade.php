<script>
    (function () {
        if (window.__gestionAlumnosSweetAlerts) {
            return;
        }

        window.__gestionAlumnosSweetAlerts = true;
        const nativeAlert = window.alert ? window.alert.bind(window) : null;

        function normalizeMessage(message) {
            if (message === null || typeof message === 'undefined') {
                return '';
            }

            return String(message);
        }

        function resolveIcon(message) {
            const text = message.toLowerCase();

            if (
                text.includes('guardad') ||
                text.includes('registrad') ||
                text.includes('actualizad') ||
                text.includes('eliminad') ||
                text.includes('finalizada') ||
                text.includes('correctamente') ||
                text.includes('satisfactoriamente')
            ) {
                return 'success';
            }

            if (
                text.includes('error') ||
                text.includes('no se pudo') ||
                text.includes('no se pudieron') ||
                text.includes('fallo') ||
                text.includes('falló')
            ) {
                return 'error';
            }

            if (
                text.includes('selecciona') ||
                text.includes('indica') ||
                text.includes('falta') ||
                text.includes('no hay') ||
                text.includes('no se encontró') ||
                text.includes('no se identificó')
            ) {
                return 'warning';
            }

            return 'info';
        }

        function resolveTitle(icon) {
            if (icon === 'success') {
                return 'Listo';
            }

            if (icon === 'error') {
                return 'Error';
            }

            if (icon === 'warning') {
                return 'Atención';
            }

            return 'Aviso';
        }

        window.alert = function (message) {
            const text = normalizeMessage(message);
            const icon = resolveIcon(text);

            if (typeof Swal === 'undefined' || !Swal.fire) {
                if (nativeAlert) {
                    nativeAlert(text);
                }

                return;
            }

            return Swal.fire({
                icon: icon,
                title: resolveTitle(icon),
                text: text,
                didOpen: function () {
                    const htmlContainer = Swal.getHtmlContainer();

                    if (htmlContainer) {
                        htmlContainer.style.whiteSpace = 'pre-line';
                    }
                }
            });
        };
    })();
</script>
