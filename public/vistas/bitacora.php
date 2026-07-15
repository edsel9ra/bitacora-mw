<?php
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/bitacora.php';

app_require_login();

$empresaId = (int) ($_SESSION['s_idEmpresa'] ?? 0);
$config = app_bitacora_config($empresaId);
$pageTitle = $config['title'] ?? 'Bitácora Mister Wings';

function bit_view_options(array $options, ?string $selected = null): string
{
    $html = '<option value="">Seleccionar...</option>';
    foreach ($options as $value => $label) {
        if (is_int($value)) {
            $value = $label;
        }
        $isSelected = ((string) $value === (string) $selected) ? ' selected' : '';
        $html .= '<option value="' . app_h($value) . '"' . $isSelected . '>' . app_h($label) . '</option>';
    }
    return $html;
}

function bit_view_sede_attr(array $field): string
{
    $sedes = array_filter((array) ($field['sedes'] ?? []));
    if ($sedes === []) {
        return '';
    }
    return ' data-sede="' . app_h(implode(',', $sedes)) . '"';
}

function bit_view_dynamic_attr(array $field): string
{
    return !empty($field['dynamic']) ? ' data-dynamic-field="1"' : '';
}

function bit_view_wrapper_start(array $field, string $fallbackCol = 'col-md-6'): void
{
    $col = $field['col'] ?? $fallbackCol;
    $style = !empty($field['sedes']) ? ' style="display:none"' : '';
    echo '<div class="form-group bit-field ' . app_h($col) . '"' . bit_view_sede_attr($field) . $style . '>';
}

function bit_view_label(string $for, string $label, bool $required): void
{
    echo '<label class="bit-label" for="' . app_h($for) . '">' . app_h($label) . ($required ? ' <span class="text-danger">*</span>' : '') . '</label>';
}

function bit_view_control_attrs(array $field, string $name = ''): string
{
    $name = $name !== '' ? $name : (string) ($field['name'] ?? '');
    return ' name="' . app_h($name) . '"' . bit_view_sede_attr($field) . bit_view_dynamic_attr($field) . (!empty($field['required']) ? ' required' : '');
}

function bit_view_simple_input(array $field, string $type): void
{
    $name = (string) $field['name'];
    bit_view_wrapper_start($field, $type === 'number' ? 'col-md-3' : 'col-md-6');
    bit_view_label($name, (string) $field['label'], (bool) ($field['required'] ?? false));
    echo '<input type="' . app_h($type) . '" class="form-control bit-input" id="' . app_h($name) . '"' . ($type === 'number' ? ' step="any"' : '') . bit_view_control_attrs($field) . '>';
    echo '</div>';
}

function bit_view_textarea_field(array $field): void
{
    $name = (string) $field['name'];
    bit_view_wrapper_start($field);
    bit_view_label($name, (string) $field['label'], (bool) ($field['required'] ?? false));
    echo '<textarea class="form-control bit-input" rows="4" id="' . app_h($name) . '"' . bit_view_control_attrs($field) . '></textarea>';
    echo '</div>';
}

function bit_view_select_field(array $field): void
{
    $name = (string) $field['name'];
    $id = (string) ($field['id'] ?? $name);
    bit_view_wrapper_start($field);
    bit_view_label($id, (string) $field['label'], (bool) ($field['required'] ?? false));
    echo '<select id="' . app_h($id) . '" class="form-control bit-input"' . bit_view_control_attrs($field) . '>';
    echo bit_view_options((array) ($field['options'] ?? []), isset($field['selected']) ? (string) $field['selected'] : null);
    echo '</select></div>';
}

function bit_view_multiselect_field(array $field): void
{
    $name = (string) $field['name'];
    $id = (string) ($field['id'] ?? $name);
    bit_view_wrapper_start($field);
    bit_view_label($id, (string) $field['label'], false);
    echo '<select id="' . app_h($id) . '" class="form-control bit-input select2-field" name="' . app_h($name) . '[]" multiple' . bit_view_dynamic_attr($field) . '>';
    foreach ((array) ($field['options'] ?? []) as $value => $label) {
        if (is_int($value)) {
            $value = $label;
        }
        echo '<option value="' . app_h($value) . '">' . app_h($label) . '</option>';
    }
    echo '</select></div>';
}

function bit_view_yes_no_field(array $field): void
{
    $name = (string) $field['name'];
    $detailName = (string) ($field['detail_name'] ?? ($name . '_detalle'));
    $groupId = (string) ($field['group_id'] ?? ($detailName . 'Group'));
    $detailType = (string) ($field['detail_type'] ?? 'textarea');

    bit_view_wrapper_start($field);
    echo '<label class="bit-label">' . app_h((string) $field['label']) . ' <span class="text-danger">*</span></label>';
    echo '<div class="bit-radio-group"><label class="bit-radio-pill"><input type="radio" value="Si" data-toggle-detail="#' . app_h($groupId) . '"' . bit_view_control_attrs($field) . '> Si</label>';
    echo '<label class="bit-radio-pill"><input type="radio" value="No" data-toggle-detail="#' . app_h($groupId) . '"' . bit_view_control_attrs($field) . '> No</label></div>';
    echo '<div class="bit-detail-panel" id="' . app_h($groupId) . '" style="display:none;">';
    bit_view_label($detailName, (string) ($field['detail_label'] ?? 'Detalle'), false);
    if ($detailType === 'number') {
        echo '<input class="form-control bit-input" type="number" step="any" id="' . app_h($detailName) . '" name="' . app_h($detailName) . '"' . bit_view_dynamic_attr($field) . '>';
    } else {
        echo '<textarea class="form-control bit-input" rows="3" id="' . app_h($detailName) . '" name="' . app_h($detailName) . '"' . bit_view_dynamic_attr($field) . '></textarea>';
    }
    echo '</div></div>';
}

function bit_view_simple_radio_field(array $field): void
{
    $name = (string) $field['name'];
    bit_view_wrapper_start($field, 'col-md-3');
    echo '<label class="bit-label">' . app_h((string) $field['label']) . ' <span class="text-danger">*</span></label>';
    echo '<div class="bit-radio-group"><label class="bit-radio-pill"><input type="radio" value="Si"' . bit_view_control_attrs($field) . '> Si</label>';
    echo '<label class="bit-radio-pill"><input type="radio" value="No"' . bit_view_control_attrs($field) . '> No</label></div>';
    echo '</div>';
}

function bit_view_supervisor_detail_field(array $field): void
{
    echo '<div id="contenedor_sup" class="form-group bit-field col-md-6" style="display:none;">';
    echo '<label class="bit-label">' . app_h((string) $field['label']) . '</label>';
    echo '<div class="form-row">';
    echo '<div class="form-group bit-field col-md-6"><input class="form-control bit-input" type="time" id="hora_entrada" name="hora_entrada"></div>';
    echo '<div class="form-group bit-field col-md-6"><input class="form-control bit-input" type="time" id="hora_salida" name="hora_salida"></div>';
    echo '<div class="form-group bit-field col-md-12"><textarea class="form-control bit-input" rows="3" id="act_sup" name="act_sup" placeholder="Actividades realizadas"></textarea></div>';
    echo '</div></div>';
}

function bit_view_conditional_textarea_field(array $field): void
{
    $name = (string) $field['name'];
    $containerId = (string) ($field['container_id'] ?? ('contenedor_' . $name));
    echo '<div id="' . app_h($containerId) . '" class="form-group bit-field col-md-6" style="display:none;">';
    bit_view_label($name, (string) $field['label'], false);
    echo '<textarea class="form-control bit-input" rows="3" id="' . app_h($name) . '" name="' . app_h($name) . '"' . bit_view_dynamic_attr($field) . '></textarea>';
    echo '</div>';
}

function bit_view_plant_field(array $field): void
{
    bit_view_wrapper_start($field);
    echo '<label class="bit-label">' . app_h((string) $field['label']) . ' <span class="text-danger">*</span></label>';
    echo '<div class="bit-radio-group"><label class="bit-radio-pill"><input type="radio" name="planta_elect" value="Si" required> Si</label><label class="bit-radio-pill"><input type="radio" name="planta_elect" value="No" required> No</label></div>';
    echo '<div class="bit-detail-panel" id="plantaGroup" style="display:none;">';
    echo '<label class="bit-label" for="mant5">Hora encendido</label>';
    echo '<input class="form-control bit-input mb-2" type="time" id="mant5" name="mant5">';
    echo '<label class="bit-label" for="mant6">Hora apagado</label>';
    echo '<input class="form-control bit-input mb-2" type="time" id="mant6" name="mant6">';
    echo '<label class="bit-label" for="mant7">Tiempo de uso (minutos)</label>';
    echo '<input class="form-control bit-input mb-2" type="number" id="mant7" name="mant7" placeholder="Calculado automáticamente" readonly>';
    echo '<textarea class="form-control bit-input" rows="3" id="mant8" name="mant8" placeholder="Novedades planta eléctrica"></textarea>';
    echo '</div></div>';
}

function bit_view_render_field(array $field): void
{
    switch ((string) ($field['type'] ?? 'text')) {
        case 'date':
        case 'time':
        case 'text':
        case 'number':
            bit_view_simple_input($field, (string) $field['type']);
            break;
        case 'textarea':
            bit_view_textarea_field($field);
            break;
        case 'select':
            bit_view_select_field($field);
            break;
        case 'multiselect':
            bit_view_multiselect_field($field);
            break;
        case 'yes_no':
            bit_view_yes_no_field($field);
            break;
        case 'simple_radio':
            bit_view_simple_radio_field($field);
            break;
        case 'supervisor_detail':
            bit_view_supervisor_detail_field($field);
            break;
        case 'conditional_textarea':
            bit_view_conditional_textarea_field($field);
            break;
        case 'plant':
            bit_view_plant_field($field);
            break;
    }
}

function bit_view_section_schema(array $section): void
{
    static $sectionIndex = 0;
    $sectionIndex++;
    $sedes = array_filter((array) ($section['sedes'] ?? []));
    $attr = $sedes === [] ? '' : ' data-sede="' . app_h(implode(',', $sedes)) . '" style="display:none"';
    echo '<div class="col-12"' . $attr . '><div class="bit-section-heading"><span class="bit-section-index">' . app_h((string) $sectionIndex) . '</span><h4>' . app_h((string) ($section['title'] ?? '')) . '</h4></div></div>';
}

function bit_view_operational_form(array $config, int $empresaId): void
{
    $sections = app_bitacora_form_sections($empresaId, $config);
    ?>
    <form method="post" class="form-bitacora" autocomplete="off">
        <?php echo app_csrf_input(); ?>
        <div class="form-row">
            <?php foreach ($sections as $section): ?>
                <?php bit_view_section_schema($section); ?>
                <?php foreach ((array) ($section['fields'] ?? []) as $field): ?>
                    <?php bit_view_render_field($field); ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>

        <div class="bit-actions">
            <button id="cargar_local_storage" type="button" class="bit-btn-ghost">Cargar temporal</button>
            <button id="guardar_local_storage" type="button" class="bit-btn-secondary">Guardar temporal</button>
            <button id="generar_pdf" type="button" class="bit-btn-pdf" data-default-text="Generar PDF">Generar PDF</button>
            <button id="boton" type="submit" class="bit-btn-primary" data-default-text="Enviar bitácora">Enviar bitácora</button>
        </div>
    </form>
    <?php
}

function bit_view_supervision_form(array $config): void
{
    ?>
    <form method="post" class="form-bitacora" autocomplete="off">
        <?php echo app_csrf_input(); ?>
        <div class="form-row">
            <?php bit_view_simple_input(app_bitacora_field('date', 'fechasup', 'Fecha', ['col' => 'col-md-3']), 'date'); ?>
            <?php bit_view_select_field(app_bitacora_field('select', 'horasup', 'Horario de supervisión', ['options' => ['11:00 AM - 3:00 PM', '3:00 PM - 6:00 PM', '6:00 PM - 10:00 PM', 'Todo el turno']])); ?>
            <?php bit_view_select_field(app_bitacora_field('select', 'sede', 'Sede', ['id' => 'sede', 'options' => $config['sedes'] ?? []])); ?>
            <?php bit_view_select_field(app_bitacora_field('select', 'area', 'Área', ['options' => ['Salon', 'Cocina', 'Bar']])); ?>
            <?php bit_view_select_field(app_bitacora_field('select', 'responsableb', 'Responsable de supervisión', ['options' => ['Angela Mesa - Supervisora de Cocina y Bar', 'Brian Ortiz - Coordinador de Operaciones', 'Gabriel Perez - Supervisor Comercial', 'Maria Conchita - Supervisora de Cocina y Bar', 'Nicol Muñoz - Supervisora de Operaciones']])); ?>
            <?php bit_view_textarea_field(app_bitacora_field('textarea', 'hallazgos', 'Hallazgos encontrados con sus evidencias')); ?>
            <?php bit_view_textarea_field(app_bitacora_field('textarea', 'ryc', 'Retroalimentación y colaboradores')); ?>
            <?php bit_view_textarea_field(app_bitacora_field('textarea', 'tappv', 'Tareas asignadas para próximas visitas')); ?>
            <?php bit_view_textarea_field(app_bitacora_field('textarea', 'pasc', 'Plan de acción o recomendaciones')); ?>
            <?php bit_view_textarea_field(app_bitacora_field('textarea', 'actsup', 'Otras actividades del supervisor')); ?>
        </div>
        <div class="bit-actions"><button id="boton" type="submit" class="bit-btn-primary" data-default-text="Enviar reporte">Enviar reporte</button></div>
    </form>
    <?php
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo app_h($pageTitle); ?></title>
    <link rel="stylesheet" href="../resources/css/bootstrap.min.css">
    <link rel="stylesheet" href="../resources/sweetalert/sweetalert2.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../resources/css/bitacora.css">
    <link rel="shortcut icon" href="../resources/img/LOGO ALITAS-09.png" alt="Logo">
</head>
<body class="bitacora-page">
<main class="bit-shell">
    <header class="bit-topbar">
        <div class="bit-brand">
            <img class="bit-brand-logo" src="../resources/img/LOGO ALITAS-09.png" alt="Logo">
            <div>
                <p class="bit-eyebrow">Bitácora digital</p>
                <h1 class="bit-title"><?php echo app_h($pageTitle); ?></h1>
            </div>
        </div>
        <div class="bit-session">
            <span class="bit-user-pill">Usuario: <strong><?php echo app_h($_SESSION['s_nombre'] ?? ''); ?></strong></span>
            <a class="bit-logout" href="../bd/logout.php" role="button">Cerrar sesión</a>
        </div>
    </header>

    <section class="bit-card">
        <div class="bit-card-header">
            <div>
                <h2><?php echo (($config['type'] ?? '') === 'supervision') ? 'Reporte de supervisión' : 'Registro operativo'; ?></h2>
                <p>Completa los campos requeridos. La información se enviará al correo configurado y se generará el PDF cuando aplique.</p>
            </div>
        </div>
        <div class="bit-card-body">
            <?php if ($config === null): ?>
                <div class="alert alert-warning mb-0">No hay configuración para esta empresa.</div>
            <?php elseif (($config['type'] ?? '') === 'supervision'): ?>
                <?php bit_view_supervision_form($config); ?>
            <?php else: ?>
                <?php bit_view_operational_form($config, $empresaId); ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<script src="../resources/jquery/jquery-3.6.0.min.js"></script>
<script src="../resources/js/bootstrap.min.js"></script>
<script src="../resources/popper/popper.min.js"></script>
<script src="../resources/sweetalert/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<?php if (($config['type'] ?? '') === 'operational'): ?>
<script>
$(function () {
    $('.select2-field').select2({ tags: true, width: '100%', tokenSeparators: [',', ';', '.'] });

    function toggleDetalleSelect($select, noAplica, $container, $field) {
        var value = $select.val() || [];
        var mostrar = value.length > 0 && value.indexOf(noAplica) === -1;
        $container.toggle(mostrar);
        $field.prop('required', mostrar).prop('disabled', !mostrar);
        if (!mostrar) $field.val('');
    }

    function bindDynamicYesNo() {
        $('[data-toggle-detail]').on('change', function () {
            var target = $(this).data('toggle-detail');
            var name = this.name;
            var value = $('input[name="' + name + '"]:checked').val();
            $(target).toggle(value === 'Si');
        }).trigger('change');
    }

    function bindPlantField() {
        var $plantControls = $('#mant5, #mant6, #mant7, #mant8');
        var $plantTimes = $('#mant5, #mant6');

        function calculatePlantMinutes() {
            var inicio = $('#mant5').val();
            var fin = $('#mant6').val();

            if (!inicio || !fin) {
                $('#mant7').val('');
                return;
            }

            var inicioParts = inicio.split(':').map(Number);
            var finParts = fin.split(':').map(Number);
            var minutosInicio = (inicioParts[0] * 60) + inicioParts[1];
            var minutosFin = (finParts[0] * 60) + finParts[1];
            var diff = minutosFin - minutosInicio;

            if (diff < 0) {
                diff += 24 * 60;
            }

            $('#mant7').val(diff);
        }

        $plantTimes.on('change input', calculatePlantMinutes);
        $('input[name="planta_elect"]').on('change', function () {
            var mostrar = $('input[name="planta_elect"]:checked').val() === 'Si';
            $('#plantaGroup').toggle(mostrar);
            if (mostrar) {
                calculatePlantMinutes();
            } else {
                $plantControls.val('');
            }
        }).trigger('change');
    }

    $('#supervisores').on('change', function () { toggleDetalleSelect($(this), 'No hay visita por parte de los supervisores', $('#contenedor_sup'), $('#act_sup, #hora_entrada, #hora_salida')); }).trigger('change');
    $('#equipo_bpm').on('change', function () { toggleDetalleSelect($(this), 'No hay visita por parte del área', $('#contenedor_bpm'), $('#bpm')); }).trigger('change');
    $('#equipo_sst').on('change', function () { toggleDetalleSelect($(this), 'No hay visita por parte del área', $('#contenedor_sst'), $('#sst5')); }).trigger('change');
    bindDynamicYesNo();
    bindPlantField();
});
</script>
<script src="../localstorage_bitacora.js"></script>
<?php endif; ?>
<script>
function bitHandleFormRequest(form, $button, action) {
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    var $buttons = $(form).find('.bit-actions button');
    var defaultText = $button.data('default-text') || $button.text();
    var isPdfOnly = action === 'generate_pdf';
    var formData = new FormData(form);
    formData.append('bitacora_action', action);

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function resetConditionalUi() {
        $(form).find('[data-toggle-detail]').trigger('change');
        $(form).find('input[name="planta_elect"]').trigger('change');
        $('#supervisores, #equipo_bpm, #equipo_sst').trigger('change');
    }

    $buttons.prop('disabled', true);
    $button.text(isPdfOnly ? 'Generando...' : 'Enviando...');
    Swal.fire({
        title: isPdfOnly ? 'Generando PDF' : 'Enviando bitácora',
        html: isPdfOnly ? 'Estamos preparando el PDF con la información diligenciada.' : 'Estamos validando la información y preparando el reporte.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        customClass: { popup: 'bit-swal-popup' },
        didOpen: function () {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: '../scripts/send_bitacora.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function (resp) {
            if (resp.pdfGenerado && resp.downloadUrl) {
                var link = document.createElement('a');
                link.href = resp.downloadUrl;
                link.download = resp.pdfFileName || 'bitacora.pdf';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }

            var safeMessage = escapeHtml(resp.message || 'Proceso finalizado.');
            var pdfBadge = resp.pdfGenerado ? 'ok' : 'warn';
            var summaryHtml = '<div class="bit-swal-summary">'
                + '<div class="bit-swal-row"><span>PDF</span><span class="bit-swal-badge ' + pdfBadge + '">' + (resp.pdfGenerado ? 'Generado' : 'No generado') + '</span></div>'
                + '<div class="bit-swal-row"><span>Estado</span><span>' + safeMessage + '</span></div>'
                + '</div>';

            if (!isPdfOnly) {
                var correoBadge = resp.correoEnviado === false ? 'warn' : 'ok';
                summaryHtml = '<div class="bit-swal-summary">'
                    + '<div class="bit-swal-row"><span>Correo</span><span class="bit-swal-badge ' + correoBadge + '">' + (resp.correoEnviado === false ? 'No enviado' : 'Enviado') + '</span></div>'
                    + '<div class="bit-swal-row"><span>PDF</span><span class="bit-swal-badge ' + pdfBadge + '">' + (resp.pdfGenerado ? 'Generado' : 'No generado') + '</span></div>'
                    + '<div class="bit-swal-row"><span>Estado</span><span>' + safeMessage + '</span></div>'
                    + '</div>';
            }

            Swal.fire({
                icon: resp.ok ? (resp.correoEnviado === false ? 'warning' : 'success') : 'error',
                title: resp.ok ? (isPdfOnly ? 'PDF generado' : 'Proceso finalizado') : (isPdfOnly ? 'No se pudo generar' : 'No se pudo enviar'),
                html: resp.ok ? summaryHtml : escapeHtml(resp.message || 'No fue posible completar el proceso.'),
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#d71920',
                customClass: { popup: 'bit-swal-popup' }
            });

            if (!isPdfOnly && resp.ok && resp.correoEnviado !== false) {
                form.reset();
                $('.select2-field').val(null).trigger('change');
                resetConditionalUi();
            }
        },
        error: function (xhr, textStatus) {
            var message = 'No se pudo completar la solicitud.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            } else if (xhr.status === 0) {
                message = 'No se pudo contactar el servidor.';
            } else if (textStatus === 'parsererror') {
                message = 'El servidor respondió con datos inválidos. Intenta nuevamente o revisa los logs.';
            }

            Swal.fire({
                icon: 'error',
                title: xhr.status === 0 ? 'Error de conexión' : 'Error del servidor',
                text: message,
                confirmButtonText: 'Reintentar',
                confirmButtonColor: '#d71920',
                customClass: { popup: 'bit-swal-popup' }
            });
        },
        complete: function () {
            $buttons.prop('disabled', false);
            $button.text(defaultText);
        }
    });
}

$('.form-bitacora').on('submit', function (event) {
    event.preventDefault();
    bitHandleFormRequest(this, $(this).find('button[type="submit"]'), 'send');
});

$('#generar_pdf').on('click', function () {
    var form = $(this).closest('form')[0];
    bitHandleFormRequest(form, $(this), 'generate_pdf');
});
</script>
</body>
</html>
