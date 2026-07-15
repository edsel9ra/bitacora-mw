/**
 * bitacora_pdf.js
    * ================================================================================
 * Script universal para todas las vistas(mes_group.php, inversiones_valquin.php,
 * lebor_sas.php, les_group.php, mes_soluciones_hcqc.php, mes_trilogia.php).
 *
 * Ubicación: raíz del proyecto(junto a localstorage_bitacora.js)
    * Incluir en cada vista antes de </body >:
 * <script src="../localstorage_bitacora.js"></script>
    * <script src="../bitacora_pdf.js"></script>
    *
 * La empresa se identifica automáticamente en el servidor a través de
    * $_SESSION['s_idEmpresa'] — NO requiere ningún campo oculto en los formularios.
 *
 * Funcionalidades:
 * 1. Botón "⬇ Descargar PDF" independiente(cualquier momento del llenado).
 * 2. Descarga automática del PDF cuando el envío del correo falla.
 * ================================================================================
 */
(function ($) {
    'use strict';
 
    // ── Ruta del endpoint (vistas/ → ../scripts/) ────────────────────────────
    var PDF_URL = '../scripts/generar_pdf.php';
 
    // ─────────────────────────────────────────────────────────────────────────
    // Descarga el PDF enviando el FormData capturado antes del reset
    // ─────────────────────────────────────────────────────────────────────────
    function descargarPDF(formData) {
        return fetch(PDF_URL, { method: 'POST', body: formData })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Error del servidor al generar el PDF (HTTP ' + response.status + ')');
                }
                var disposition = response.headers.get('Content-Disposition') || '';
                var filename    = 'Bitacora_respaldo.pdf';
                var match       = disposition.match(/filename="?([^";\n]+)"?/i);
                if (match) filename = decodeURIComponent(match[1]);
 
                return response.blob().then(function (blob) {
                    var url = URL.createObjectURL(blob);
                    var a   = document.createElement('a');
                    a.href     = url;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    setTimeout(function () { URL.revokeObjectURL(url); }, 1500);
                });
            });
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // BOTÓN INDEPENDIENTE "⬇ Descargar PDF"
    // Solo activo si existe el botón #btn_descargar_pdf en la página
    // ─────────────────────────────────────────────────────────────────────────
    $(document).ready(function () {
 
        $('#btn_descargar_pdf').on('click', function (e) {
            e.preventDefault();
 
            var $btn     = $(this);
            var formData = new FormData($('.form-bitacora')[0]);
 
            $btn.prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm"></span> Generando…');
 
            descargarPDF(formData)
                .catch(function (err) {
                    Swal.fire({
                        icon:  'error',
                        title: 'Error al generar PDF',
                        text:  err.message || 'Inténtelo nuevamente.'
                    });
                })
                .finally(function () {
                    $btn.prop('disabled', false).html('⬇ Descargar PDF');
                });
        });
 
    });
 
    // ─────────────────────────────────────────────────────────────────────────
    // API PÚBLICA — llamada desde el $.ajax de cada vista
    // ─────────────────────────────────────────────────────────────────────────
 
    /**
     * window.BitacoraPDF.manejarRespuesta(raw, formDataCapturado, callbackReset)
     *
     * Llamar desde el success del $.ajax en cada vista, pasando:
     *   raw              → la respuesta del servidor (string o object)
     *   formDataCapturado → new FormData(formEl) capturado ANTES del serialize()
     *   callbackReset    → función que resetea el formulario (ya existe en cada vista)
     */
    window.BitacoraPDF = {
 
        manejarRespuesta: function (raw, formDataCapturado, callbackReset) {
            var resp;
            try {
                resp = (typeof raw === 'object') ? raw : JSON.parse(raw);
            } catch (ex) {
                // Respuesta HTML/JS legacy → asumir envío exitoso
                resp = { status: 'ok' };
            }
 
            if (resp.status === 'ok') {
                Swal.fire({
                    icon:             'success',
                    title:            'Bitácora enviada',
                    text:             '¡Se ha enviado la bitácora con éxito!',
                    timerProgressBar: true,
                    allowOutsideClick: false
                });
                if (typeof callbackReset === 'function') callbackReset();
 
            } else {
                // Correo falló → descarga PDF de respaldo
                Swal.fire({
                    icon:             'warning',
                    title:            'No se pudo enviar el correo',
                    html:             (resp.msg || 'Hubo un problema al enviar la bitácora.') +
                                      '<br><br>Se descargará un <strong>PDF de respaldo</strong>.',
                    confirmButtonText: 'Descargar PDF',
                    allowOutsideClick: false
                }).then(function () {
                    descargarPDF(formDataCapturado).catch(function (err) {
                        Swal.fire({ icon: 'error', title: 'Error al generar PDF', text: err.message });
                    });
                });
            }
        },
 
        /**
         * window.BitacoraPDF.manejarError(formDataCapturado)
         *
         * Llamar desde el error del $.ajax (fallo de red / servidor caído).
         */
        manejarError: function (formDataCapturado) {
            Swal.fire({
                icon:             'error',
                title:            'Error de conexión',
                html:             'No se pudo contactar el servidor.<br><br>' +
                                  'Se descargará un <strong>PDF de respaldo</strong>.',
                confirmButtonText: 'Descargar PDF',
                allowOutsideClick: false
            }).then(function () {
                descargarPDF(formDataCapturado).catch(function (err) {
                    Swal.fire({ icon: 'error', title: 'Error al generar PDF', text: err.message });
                });
            });
        }
    };
 
}(jQuery));