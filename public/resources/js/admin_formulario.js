(function ($) {
    'use strict';

    var ADMIN_URL = $('body').data('admin-url') || 'admin_formulario.php';
    var EMPRESA_ID = $('body').data('admin-bitacora-id') || '';
    var CSRF = $('body').data('admin-csrf') || '';

    function adminQuery(params) {
        var parts = [];
        $.each(params || {}, function (key, value) {
            if (value === '' || value === null || value === undefined) return;
            parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
        });
        return ADMIN_URL + (parts.length ? '?' + parts.join('&') : '');
    }

    /* ===== Pestañas ===== */
    function activateTab(panelId) {
        $('.admin-tab').each(function () {
            var on = $(this).data('tab-panel') === panelId;
            $(this).toggleClass('is-active', on).attr('aria-selected', on ? 'true' : 'false');
        });
        $('.admin-panel').each(function () {
            $(this).toggleClass('is-active', this.id === panelId);
        });
    }

    $('.admin-tab').on('click', function () {
        activateTab($(this).data('tab-panel'));
    });

    /* ===== Modal ===== */
    var $overlay = $('#adminModalOverlay');
    var $dialog = $overlay.find('.admin-modal');
    var $box = $('#adminModalBox');
    var $title = $('#adminModalTitle');
    var lastModalFocus = null;

    function adminOpenModal(title, url) {
        lastModalFocus = document.activeElement;
        $title.text(title || '');
        $box.html('<p class="admin-muted">Cargando...</p>');
        $overlay.prop('hidden', false);
        $dialog.trigger('focus');

        $.ajax({ url: url, dataType: 'html', cache: false })
            .done(function (html) {
                $box.html(html);
                if (url.indexOf('preview=') !== -1) {
                    adminDisablePreview($box);
                } else {
                    adminInitForm($box);
                }
            })
            .fail(function () {
                $box.html('<p class="admin-muted">No se pudo cargar el formulario. Intenta nuevamente.</p>');
            });
    }

    function adminCloseModal() {
        $overlay.prop('hidden', true);
        $box.empty();
        if (lastModalFocus && document.contains(lastModalFocus)) {
            lastModalFocus.focus();
        }
        lastModalFocus = null;
    }

    function adminDisablePreview($root) {
        $root.find('.form-bitacora').find('input, select, textarea').prop('disabled', true);
        $root.find('.bit-actions').hide();
    }

    /* ===== Inicialización de formularios dentro del modal ===== */
    function adminInitForm($root) {
        $root.find('input[name="group_fields[row_key][]"]').each(function () {
            var match = String($(this).val() || '').match(/^gf_(\d+)$/);
            if (match) {
                window.__adminGroupFieldUid = Math.max(window.__adminGroupFieldUid || 0, Number(match[1]));
            }
        });

        $root.find('select.admin-select2, select[multiple]').select2({
            width: '100%',
            placeholder: 'Selecciona una o varias opciones...',
            dropdownParent: $root.closest('.admin-modal')
        });

        var $typeSelect = $root.find('#type');
        if ($typeSelect.length) {
            $typeSelect.off('change').on('change', function () {
                adminRefreshTypePanels($root, $(this).val());
            });
            adminRefreshTypePanels($root, $typeSelect.val());
        }

        $root.off('submit', 'form').on('submit', 'form', function (e) {
            e.preventDefault();
            adminSubmitForm($(this), $root);
        });
    }

    function adminRefreshTypePanels($root, type) {
        $root.find('[data-type-panel]').each(function () {
            var types = String($(this).attr('data-type-panel') || '').split(/\s+/);
            $(this).toggle(types.indexOf(type) !== -1);
        });
        $root.find('[data-quantity-mode]').each(function () {
            var active = $(this).attr('data-quantity-mode') === type;
            $(this).toggle(active).find(':input').prop('disabled', !active);
        });
        $root.find('[data-input-field-only]').toggle(type !== 'subsection');
    }

    /* ===== Sub-campos del grupo Sí/No con cantidad ===== */
    var ADMIN_GROUP_FIELD_TYPES = [
        ['text', 'Texto corto'],
        ['textarea', 'Texto largo'],
        ['number', 'Número'],
        ['select', 'Lista'],
        ['date', 'Fecha'],
        ['time', 'Hora'],
        ['simple_radio', 'Radio Sí / No']
    ];

    function adminGroupFieldTypeOptions(selected) {
        var html = '';
        $.each(ADMIN_GROUP_FIELD_TYPES, function (i, pair) {
            html += '<option value="' + pair[0] + '"' + (selected === pair[0] ? ' selected' : '') + '>' + pair[1] + '</option>';
        });
        return html;
    }

    function adminGroupFieldRow() {
        var uid = 'gf_' + ((window.__adminGroupFieldUid = (window.__adminGroupFieldUid || 0) + 1));
        return $(
            '<div class="admin-group-field-row border rounded p-2 mb-2">' +
            '<div class="form-row">' +
            '<div class="form-group col-md-3 mb-2"><label>Nombre técnico</label><input type="text" class="form-control" name="group_fields[name][]" placeholder="ej. nombre"></div>' +
            '<div class="form-group col-md-4 mb-2"><label>Etiqueta</label><input type="text" class="form-control" name="group_fields[label][]" placeholder="Nombre del visitante"></div>' +
            '<div class="form-group col-md-3 mb-2"><label>Tipo</label><select class="form-control admin-group-field-type" name="group_fields[type][]">' +
            adminGroupFieldTypeOptions('text') +
            '</select></div>' +
            '<div class="form-group col-md-2 mb-2 d-flex align-items-center"><label class="form-check-label mr-2 mb-0"><input type="checkbox" class="form-check-input" name="group_fields[required][]" value="' + uid + '" checked> Obligatorio</label><button type="button" class="admin-btn-sm admin-btn-danger-ghost admin-group-field-remove" title="Quitar sub-campo">Quitar</button></div>' +
            '</div>' +
            '<input type="hidden" name="group_fields[row_key][]" value="' + uid + '">' +
            '<div class="form-group mb-0 admin-group-field-options-wrap" data-options-for="select" hidden><label>Opciones de la lista (una por línea)</label><textarea class="form-control" name="group_fields[options][]" rows="2"></textarea></div>' +
            '</div>'
        );
    }

    $(document).on('click', '#adminAddGroupField', function () {
        $('#adminGroupFieldsList').append(adminGroupFieldRow());
    });

    $(document).on('click', '.admin-group-field-remove', function () {
        $(this).closest('.admin-group-field-row').remove();
    });

    $(document).on('change', '.admin-group-field-type', function () {
        var $row = $(this).closest('.admin-group-field-row');
        $row.find('.admin-group-field-options-wrap').prop('hidden', $(this).val() !== 'select');
    });

    /* ===== Envío de formularios vía AJAX ===== */
    function adminSubmitForm($form, $root) {
        var $btn = $form.find('button[type="submit"]').first();
        var defaultText = $btn.data('default-text') || $btn.text();
        var data = $form.serializeArray();
        data.push({ name: 'ajax', value: '1' });

        $btn.prop('disabled', true).text('Guardando...');

        $.ajax({
            url: adminQuery({ empresa: EMPRESA_ID }),
            type: 'POST',
            data: data,
            dataType: 'json'
        })
            .done(function (resp) {
                if (!resp || !resp.ok) {
                    $btn.prop('disabled', false).text(defaultText);
                    adminNotify(resp);
                    return;
                }
                adminCloseModal();
                adminNotify(resp);
            })
            .fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo guardar. Intenta nuevamente.';
                $btn.prop('disabled', false).text(defaultText);
                Swal.fire({ icon: 'error', title: 'Error', text: msg });
            });
    }

    function adminNotify(resp) {
        var ok = resp && resp.ok;
        var notification = Swal.fire({
            icon: ok ? 'success' : 'error',
            title: ok ? 'Listo' : 'Error',
            text: (resp && resp.message) || (ok ? 'Cambios guardados.' : 'No se pudo completar la acción.')
        });
        if (ok) {
            notification.then(function () {
                window.location.reload();
            });
        }
    }

    function adminPost(params) {
        return $.ajax({
            url: adminQuery({ empresa: EMPRESA_ID }),
            type: 'POST',
            data: $.extend({ ajax: '1', csrf_token: CSRF, empresa_id: EMPRESA_ID }, params || {}),
            dataType: 'json'
        });
    }

    /* ===== Formularios con confirmación (eliminar / restaurar) ===== */
    $(document).on('submit', 'form[data-confirm]', function (e) {
        e.preventDefault();
        var $form = $(this);
        var message = $form.data('confirm') || '¿Confirmar acción?';

        Swal.fire({
            icon: 'warning',
            title: '¿Confirmar?',
            text: message,
            showCancelButton: true,
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (!result.value) return;

            var data = $form.serializeArray();
            data.push({ name: 'ajax', value: '1' });

            $.ajax({
                url: adminQuery({ empresa: EMPRESA_ID }),
                type: 'POST',
                data: data,
                dataType: 'json'
            })
                .done(function (resp) {
                    adminNotify(resp);
                })
                .fail(function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo completar la acción.';
                    Swal.fire({ icon: 'error', title: 'Error', text: msg });
                });
        });
    });

    /* ===== Formulario de visibilidad ===== */
    $('#adminHiddenForm').on('submit', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]').first();
        var defaultText = $btn.data('default-text') || $btn.text();
        var data = $form.serializeArray();
        data.push({ name: 'ajax', value: '1' });

        $btn.prop('disabled', true).text('Guardando...');

        $.ajax({
            url: adminQuery({ empresa: EMPRESA_ID }),
            type: 'POST',
            data: data,
            dataType: 'json'
        })
            .done(function (resp) {
                adminNotify(resp);
            })
            .fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo guardar.';
                $btn.prop('disabled', false).text(defaultText);
                Swal.fire({ icon: 'error', title: 'Error', text: msg });
            });
    });

    /* ===== Apertura de modales (agregar / editar / vista previa) ===== */
    $('#adminAddField').on('click', function () {
        adminOpenModal('Agregar campo dinámico', adminQuery({ empresa: EMPRESA_ID, ajax: '1' }));
    });

    $(document).on('click', '[data-edit-field]', function () {
        var name = $(this).data('edit-field');
        adminOpenModal('Editar campo dinámico', adminQuery({ empresa: EMPRESA_ID, ajax: '1', edit: name }));
    });

    $(document).on('click', '[data-edit-base]', function () {
        var name = $(this).data('edit-base');
        adminOpenModal('Editar campo base', adminQuery({ empresa: EMPRESA_ID, ajax: '1', edit_base: name }));
    });

    $('#adminPreviewBtn').on('click', function () {
        adminOpenModal('Vista previa del formulario', adminQuery({ empresa: EMPRESA_ID, ajax: '1', preview: '1' }));
    });

    /* ===== Duplicar campo ===== */
    $(document).on('click', '[data-dup-field]', function () {
        var name = $(this).data('dup-field');

        Swal.fire({
            icon: 'question',
            title: '¿Duplicar campo?',
            text: 'Se creará una copia del campo "' + name + '".',
            showCancelButton: true,
            confirmButtonText: 'Duplicar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (!result.value) return;

            adminPost({ action: 'duplicate_field', name: name })
                .done(function (resp) {
                    adminNotify(resp);
                })
                .fail(function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo duplicar el campo.';
                    Swal.fire({ icon: 'error', title: 'Error', text: msg });
                });
        });
    });

    /* ===== Reordenar ===== */
    $(document).on('click', '[data-reorder]', function () {
        var $btn = $(this);
        var $row = $btn.closest('tr');
        var direction = $btn.data('reorder');
        var $tbody = $row.closest('tbody');
        var $target = direction === 'up' ? $row.prev('tr') : $row.next('tr');
        if (!$target.length) return;

        var rows = $tbody.find('tr').toArray();
        var index = rows.indexOf($row[0]);
        var targetIndex = rows.indexOf($target[0]);
        rows[index] = $target[0];
        rows[targetIndex] = $row[0];

        var scope = $tbody.closest('table').data('scope') || 'dynamic';
        var names = $.map(rows, function (row) {
            return $(row).data('field-name');
        });

        adminPost({ action: 'save_order', scope: scope, order: JSON.stringify(names) })
            .done(function (resp) {
                if (resp && resp.ok) {
                    $tbody.empty().append(rows);
                    Swal.fire({ icon: 'success', title: 'Orden actualizado', timer: 1200, showConfirmButton: false });
                } else {
                    adminNotify(resp);
                }
            })
            .fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo actualizar el orden.';
                Swal.fire({ icon: 'error', title: 'Error', text: msg });
            });
    });

    /* ===== Búsqueda ===== */
    $('#adminFieldSearch').on('input', function () {
        var term = $.trim($(this).val()).toLowerCase();
        $('#adminDynamicTable tbody tr').each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(term) !== -1);
        });
    });

    /* ===== Cierre del modal ===== */
    $('#adminModalClose').on('click', adminCloseModal);
    $overlay.on('click', function (e) {
        if (e.target === this) adminCloseModal();
    });
    $(document).on('keydown', function (e) {
        if ($overlay.prop('hidden')) return;
        if (e.key === 'Escape') {
            adminCloseModal();
            return;
        }
        if (e.key !== 'Tab') return;

        var $focusable = $dialog.find('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])').filter(':visible');
        if (!$focusable.length) {
            e.preventDefault();
            $dialog.trigger('focus');
            return;
        }

        var first = $focusable[0];
        var last = $focusable[$focusable.length - 1];
        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    });

    /* ===== Auto-submit (selector de empresa) ===== */
    $('[data-auto-submit="1"]').on('change', function () {
        this.form.submit();
    });

    /* ===== Enlaces profundos (?edit= / ?edit_base=) ===== */
    var deepEdit = null;
    var deepEditBase = null;
    try {
        var params = new URLSearchParams(window.location.search);
        deepEdit = params.get('edit');
        deepEditBase = params.get('edit_base');
    } catch (e) {
        var matchEdit = window.location.search.match(/[?&]edit=([^&]*)/);
        var matchBase = window.location.search.match(/[?&]edit_base=([^&]*)/);
        deepEdit = matchEdit ? decodeURIComponent(matchEdit[1]) : null;
        deepEditBase = matchBase ? decodeURIComponent(matchBase[1]) : null;
    }

    if (deepEdit) {
        activateTab('tab-dynamic');
        adminOpenModal('Editar campo dinámico', adminQuery({ empresa: EMPRESA_ID, ajax: '1', edit: deepEdit }));
    } else if (deepEditBase) {
        activateTab('tab-base');
        adminOpenModal('Editar campo base', adminQuery({ empresa: EMPRESA_ID, ajax: '1', edit_base: deepEditBase }));
    }

    if (deepEdit || deepEditBase) {
        try {
            var url = new URL(window.location.href);
            url.searchParams.delete('edit');
            url.searchParams.delete('edit_base');
            history.replaceState({}, '', url.toString());
        } catch (e) { /* noop */ }
    }
})(jQuery);
