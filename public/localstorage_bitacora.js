$(function () {
    'use strict';

    var $form = $('.form-bitacora').first();
    var $bar = $('#bitDraftBar');
    if (!$form.length || !$bar.length) return;

    var form = $form[0];
    var endpoint = String($bar.data('endpoint') || '../scripts/bitacora_draft.php');
    var empresaId = String($form.find('input[name="empresa_id"]').val() || '');
    var csrfToken = String($form.find('input[name="csrf_token"]').val() || '');
    var debounceMs = 5000;
    var retryDelays = [1000, 2500, 5000];
    var autosaveTimer = null;
    var onlineRetryTimer = null;
    var activeRequest = null;
    var savePromise = null;
    var initialLoadPromise = null;
    var initialLoadComplete = false;
    var restoring = false;
    var dirty = false;
    var changeSerial = 0;
    var token = null;
    var version = 0;
    var serverExists = false;
    var serverPayload = null;
    var lastSavedJson = null;
    var conflict = null;
    var metadataGeneration = 0;

    function purgeLegacyStorage(storage) {
        try {
            for (var i = storage.length - 1; i >= 0; i--) {
                var key = storage.key(i) || '';
                if (key === 'bitacora' || key.indexOf('bitacora_') === 0) {
                    storage.removeItem(key);
                }
            }
        } catch (e) {
            // Storage can be unavailable; drafts no longer depend on it.
        }
    }

    purgeLegacyStorage(window.localStorage);
    purgeLegacyStorage(window.sessionStorage);

    function stableValue(value) {
        if (Array.isArray(value)) {
            return value.map(stableValue);
        }
        if (value && typeof value === 'object') {
            var sorted = {};
            Object.keys(value).sort().forEach(function (key) {
                sorted[key] = stableValue(value[key]);
            });
            return sorted;
        }
        return value;
    }

    function stableJson(value) {
        return JSON.stringify(stableValue(value));
    }

    function hashJson(value) {
        var hash = 2166136261;
        for (var i = 0; i < value.length; i++) {
            hash ^= value.charCodeAt(i);
            hash = Math.imul(hash, 16777619);
        }
        return (hash >>> 0).toString(36) + ':' + value.length;
    }

    function formPayload() {
        var payload = {};
        var processed = {};

        $form.find(':input[name]').each(function () {
            var $control = $(this);
            var rawName = String($control.attr('name') || '');
            var name = rawName.replace(/\[\]$/, '');
            if (!name || processed[name] || name === 'csrf_token' || name === 'empresa_id') return;
            if ($control.is(':button, button, input[type="button"], input[type="submit"], input[type="reset"], input[type="file"]')) return;
            processed[name] = true;

            var $sameName = $form.find(':input[name]').filter(function () {
                return String($(this).attr('name') || '').replace(/\[\]$/, '') === name;
            });
            if ($sameName.filter(':not(:disabled)').length === 0) return;

            if ($control.is('input[type="radio"]')) {
                payload[name] = $sameName.filter(':checked').val() || '';
            } else if ($control.is('input[type="checkbox"]')) {
                var checked = $sameName.filter(':checked').map(function () { return $(this).val(); }).get();
                payload[name] = checked.length > 1 || /\[\]$/.test(rawName) ? checked : (checked[0] || null);
            } else if ($control.is('select[multiple]')) {
                payload[name] = $control.val() || [];
            } else {
                payload[name] = $control.val() == null ? '' : $control.val();
            }
        });

        var nestedPayload = {};
        Object.keys(payload).forEach(function (name) {
            var match;
            var parts = [];
            var pattern = /([^\[\]]+)/g;
            while ((match = pattern.exec(name)) !== null) parts.push(match[1]);
            if (parts.length < 2) {
                nestedPayload[name] = payload[name];
                return;
            }
            var target = nestedPayload;
            parts.forEach(function (part, index) {
                if (index === parts.length - 1) {
                    target[part] = payload[name];
                } else {
                    if (!target[part] || typeof target[part] !== 'object') target[part] = {};
                    target = target[part];
                }
            });
        });

        return nestedPayload;
    }

    function currentPayloadState() {
        var payload = formPayload();
        var json = stableJson(payload);
        return { payload: payload, json: json, hash: hashJson(json) };
    }

    function setStatus(state, label, detail) {
        $bar.attr('data-state', state);
        $('#bitDraftStatus').text(label);
        $('#bitDraftStatusDetail').text(detail || '');
    }

    function formatUpdatedAt(value) {
        if (!value) return '';
        var date = new Date(value);
        if (isNaN(date.getTime())) return String(value);
        return date.toLocaleString('es-CO', { dateStyle: 'short', timeStyle: 'short' });
    }

    function updateButtons() {
        $('#bit_draft_restore, #bit_draft_delete').prop('hidden', !serverExists);
        $('#bitDraftConflictActions').prop('hidden', !conflict);
    }

    function responseError(message, response, data) {
        var error = new Error(message);
        error.response = response || null;
        error.data = data || null;
        error.status = response ? response.status : 0;
        error.transient = !response || response.status === 408 || response.status === 425 || response.status === 429 || response.status >= 500;
        return error;
    }

    function delay(ms) {
        return new Promise(function (resolve) { window.setTimeout(resolve, ms); });
    }

    function postAttempt(params, attempt) {
        var body = new URLSearchParams();
        Object.keys(params).forEach(function (key) {
            if (params[key] !== undefined && params[key] !== null) body.append(key, String(params[key]));
        });

        return window.fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-CSRF-Token': csrfToken,
                'Accept': 'application/json'
            },
            body: body.toString()
        }).then(function (response) {
            return response.text().then(function (text) {
                var data = null;
                try {
                    data = text ? JSON.parse(text) : {};
                } catch (e) {
                    throw responseError('El servidor respondió con datos inválidos.', response, null);
                }

                if (!response.ok || !data.ok) {
                    var error = responseError(data.message || 'No fue posible procesar el borrador.', response, data);
                    if (response.status === 409 && data.code === 'draft_conflict') error.conflict = true;
                    throw error;
                }
                return data;
            });
        }).catch(function (error) {
            if (!(error instanceof Error) || error.status === undefined) {
                error = responseError('No fue posible contactar el servidor.', null, null);
            }
            if (error.transient && attempt < retryDelays.length) {
                setStatus(navigator.onLine ? 'error' : 'offline', navigator.onLine ? 'Error' : 'Sin conexión', 'Reintentando...');
                return delay(retryDelays[attempt]).then(function () {
                    return postAttempt(params, attempt + 1);
                });
            }
            throw error;
        });
    }

    function request(params) {
        var predecessor = activeRequest ? activeRequest.catch(function () {}) : Promise.resolve();
        var $draftButtons = $('#bit_draft_save, #bit_draft_restore, #bit_draft_delete, #bit_draft_load_server, #bit_draft_overwrite');
        $draftButtons.prop('disabled', true);
        var operation = predecessor.then(function () { return postAttempt(params, 0); });
        activeRequest = operation;
        operation.then(function () {
            if (activeRequest === operation) {
                activeRequest = null;
                $draftButtons.prop('disabled', false);
            }
        }, function () {
            if (activeRequest === operation) {
                activeRequest = null;
                $draftButtons.prop('disabled', false);
            }
        });
        return operation;
    }

    function controlsByName(name) {
        return $form.find(':input[name]').filter(function () {
            return String($(this).attr('name') || '').replace(/\[\]$/, '') === name;
        });
    }

    function setFieldValue(name, value) {
        var $controls = controlsByName(name);
        if (!$controls.length) return;
        var $first = $controls.first();

        if ($first.is('input[type="radio"]')) {
            $controls.prop('checked', false).filter(function () {
                return String($(this).val()) === String(value == null ? '' : value);
            }).prop('checked', true);
            return;
        }
        if ($first.is('input[type="checkbox"]')) {
            var values = Array.isArray(value) ? value.map(String) : [String(value == null ? '' : value)];
            $controls.each(function () {
                $(this).prop('checked', values.indexOf(String($(this).val())) !== -1);
            });
            return;
        }
        if ($first.is('select[multiple]')) {
            var selected = Array.isArray(value) ? value : [];
            selected.forEach(function (item) {
                if (item === null || item === undefined || String(item).trim() === '') return;
                var exists = $first.find('option').filter(function () { return this.value === String(item); }).length > 0;
                if (!exists) $('<option>').val(String(item)).text(String(item)).attr('data-select2-tag', 'true').appendTo($first);
            });
            $first.val(selected).trigger('change');
            return;
        }
        $first.val(value == null ? '' : value);
    }

    function refreshConditionalUi() {
        if (window.bitacoraSede && window.bitacoraSede.refresh) window.bitacoraSede.refresh();
        $form.find('[data-toggle-detail]:checked').trigger('change');
        $form.find('input[name="planta_elect"]:checked').trigger('change');
        $form.find('select[multiple]').trigger('change');
        $form.trigger('bitacora:refreshConditional');
    }

    function flattenPayload(payload) {
        var flat = {};
        function visit(value, name) {
            if (value && typeof value === 'object' && !Array.isArray(value)) {
                Object.keys(value).forEach(function (key) {
                    visit(value[key], name ? name + '[' + key + ']' : key);
                });
                return;
            }
            flat[name] = value;
        }
        Object.keys(payload).forEach(function (name) { visit(payload[name], name); });
        return flat;
    }

    function restorePayload(payload, response) {
        var flatPayload = flattenPayload(payload);
        restoring = true;
        form.reset();
        $form.find('.select2-field').val(null).trigger('change');

        ['sede', 'idSede'].forEach(function (name) {
            if (Object.prototype.hasOwnProperty.call(payload, name)) setFieldValue(name, payload[name]);
        });
        if (window.bitacoraSede && window.bitacoraSede.refresh) window.bitacoraSede.refresh();

        Object.keys(flatPayload).filter(function (name) { return name.indexOf('[') === -1; }).forEach(function (name) {
            setFieldValue(name, flatPayload[name]);
        });
        refreshConditionalUi();
        Object.keys(flatPayload).forEach(function (name) { setFieldValue(name, flatPayload[name]); });
        refreshConditionalUi();
        restoring = false;

        dirty = false;
        conflict = null;
        lastSavedJson = stableJson(payload);
        serverPayload = payload;
        updateButtons();
        if (window.bitacoraUi) {
            if (window.bitacoraUi.updateProgress) window.bitacoraUi.updateProgress();
            if (window.bitacoraUi.snapshot) window.bitacoraUi.snapshot();
        }
        var updated = formatUpdatedAt(response && (response.updatedAt || response.updated_at));
        setStatus('saved', 'Guardado', updated ? 'Restaurado del ' + updated : 'Borrador restaurado');
    }

    function schemaChanged(response) {
        var changed = response.schemaChanged;
        if (changed === undefined && response.schema_hash && response.current_schema_hash) {
            changed = response.schema_hash !== response.current_schema_hash;
        }
        return !!changed;
    }

    function schemaNotice(response) {
        if (!schemaChanged(response)) return '';
        var omitted = Array.isArray(response.omittedFields) ? response.omittedFields : [];
        var detail = omitted.length
            ? '<br><small>No se restaurarán: ' + $('<div>').text(omitted.join(', ')).html() + '.</small>'
            : '';
        return '<br><strong>El formulario cambió desde el último guardado.</strong>' + detail;
    }

    function offerInitialDraft(response) {
        var updated = formatUpdatedAt(response.updatedAt || response.updated_at);
        return Swal.fire({
            icon: schemaChanged(response) ? 'warning' : 'info',
            title: 'Hay un borrador guardado',
            html: (updated ? 'Última actualización: ' + $('<div>').text(updated).html() + '.' : 'Puedes continuar donde quedaste.') + schemaNotice(response),
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: 'Restaurar',
            denyButtonText: 'Empezar en blanco',
            cancelButtonText: 'Descartar',
            allowOutsideClick: false,
            allowEscapeKey: false,
            customClass: { popup: 'bit-swal-popup' }
        }).then(function (result) {
            if (result.isConfirmed || result.value === true) {
                restorePayload(serverPayload || {}, response);
                return;
            }
            if (result.isDenied) {
                setStatus(dirty ? 'changes' : 'saved', dirty ? 'Cambios' : 'Guardado', dirty ? 'Se guardarán en 5 segundos' : 'El borrador sigue disponible para restaurarlo');
                if (dirty) scheduleAutosave();
                return;
            }
            return deleteDraft(false);
        });
    }

    function acceptLoad(response, offer) {
        initialLoadComplete = true;
        conflict = null;
        serverExists = !!response.exists;
        token = response.token || null;
        version = Number(response.version || 0);
        serverPayload = response.payload && typeof response.payload === 'object' ? response.payload : null;
        lastSavedJson = serverPayload ? stableJson(serverPayload) : null;
        updateButtons();

        if (!serverExists) {
            setStatus('saved', 'Guardado', 'Aún no hay un borrador en el servidor');
            if (dirty) scheduleAutosave();
            return Promise.resolve();
        }
        if (offer) return offerInitialDraft(response);
        return Promise.resolve(response);
    }

    function loadDraft(offer) {
        setStatus('searching', 'Buscando', 'Consultando el servidor...');
        return request({ action: 'load', empresa_id: empresaId }).then(function (response) {
            return acceptLoad(response, offer);
        }).catch(function (error) {
            initialLoadComplete = false;
            setStatus(navigator.onLine ? 'error' : 'offline', navigator.onLine ? 'Error' : 'Sin conexión', 'No se pudo consultar el borrador');
            throw error;
        });
    }

    function markConflict(error) {
        var data = error.data || {};
        conflict = {
            serverVersion: Number(data.serverVersion || data.current_version || version || 0),
            updatedAt: data.updatedAt || data.updated_at || null
        };
        dirty = true;
        if (autosaveTimer) window.clearTimeout(autosaveTimer);
        autosaveTimer = null;
        updateButtons();
        var updated = formatUpdatedAt(conflict.updatedAt);
        setStatus('conflict', 'Conflicto', updated ? 'El servidor cambió el ' + updated : 'Hay una versión más reciente en el servidor');
    }

    function performSave(force) {
        if (conflict && !force) {
            var blocked = new Error('Debes resolver el conflicto antes de guardar.');
            blocked.conflict = true;
            return Promise.reject(blocked);
        }

        var state = currentPayloadState();
        if (serverExists && state.json === lastSavedJson) {
            dirty = false;
            setStatus('saved', 'Guardado', 'Sin cambios pendientes');
            return Promise.resolve(getSubmissionMetadata());
        }

        var sentSerial = changeSerial;
        var saveGeneration = metadataGeneration;
        var expectedVersion = force && conflict ? conflict.serverVersion : version;
        setStatus('saving', 'Guardando', 'Enviando cambios...');

        return request({
            action: 'save',
            empresa_id: empresaId,
            payload: state.json,
            token: token || '',
            expected_version: expectedVersion || 0,
            force: force ? 1 : 0
        }).then(function (response) {
            if (saveGeneration !== metadataGeneration) return getSubmissionMetadata();
            token = response.token || token;
            version = Number(response.version || expectedVersion || 0);
            serverExists = true;
            serverPayload = state.payload;
            lastSavedJson = state.json;
            conflict = null;
            var current = currentPayloadState();
            dirty = sentSerial !== changeSerial || current.hash !== state.hash || current.json !== state.json;
            updateButtons();
            var updated = formatUpdatedAt(response.updatedAt || response.updated_at);
            setStatus(dirty ? 'changes' : 'saved', dirty ? 'Cambios' : 'Guardado', dirty ? 'Hay cambios nuevos pendientes' : (updated ? updated : 'Borrador actualizado'));
            if (dirty && !autosaveTimer) scheduleAutosave();
            return getSubmissionMetadata();
        }).catch(function (error) {
            if (saveGeneration !== metadataGeneration) return getSubmissionMetadata();
            if (error.conflict) {
                markConflict(error);
            } else {
                dirty = true;
                setStatus(navigator.onLine ? 'error' : 'offline', navigator.onLine ? 'Error' : 'Sin conexión', 'Los cambios siguen pendientes');
            }
            throw error;
        });
    }

    function saveNow(force) {
        if (autosaveTimer) window.clearTimeout(autosaveTimer);
        autosaveTimer = null;
        if (!initialLoadComplete && initialLoadPromise) {
            return initialLoadPromise.catch(function () {
                return loadDraft(true);
            }).then(function () {
                return saveNow(force);
            });
        }
        if (savePromise) {
            return savePromise.then(function () {
                return dirty || force ? saveNow(force) : getSubmissionMetadata();
            });
        }
        savePromise = performSave(!!force);
        savePromise.then(function () { savePromise = null; }, function () { savePromise = null; });
        return savePromise;
    }

    function scheduleAutosave(delayMs) {
        if (!initialLoadComplete || conflict || restoring) return;
        if (autosaveTimer) window.clearTimeout(autosaveTimer);
        autosaveTimer = window.setTimeout(function () {
            autosaveTimer = null;
            saveNow(false).catch(function () {});
        }, delayMs === undefined ? debounceMs : delayMs);
    }

    function deleteDraft(confirmFirst) {
        var confirmation = confirmFirst ? Swal.fire({
            icon: 'warning',
            title: '¿Eliminar el borrador?',
            text: 'El formulario actual no se borrará, pero el borrador del servidor no podrá recuperarse.',
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar',
            customClass: { popup: 'bit-swal-popup' }
        }) : Promise.resolve({ isConfirmed: true });

        return confirmation.then(function (result) {
            if (!result.isConfirmed && result.value !== true) return;
            setStatus('saving', 'Guardando', 'Eliminando borrador...');
            return request({
                action: 'delete',
                empresa_id: empresaId,
                token: token || '',
                expected_version: version || 0,
                force: 0
            }).then(function () {
                metadataGeneration++;
                if (autosaveTimer) window.clearTimeout(autosaveTimer);
                if (onlineRetryTimer) window.clearTimeout(onlineRetryTimer);
                autosaveTimer = null;
                onlineRetryTimer = null;
                token = null;
                version = 0;
                serverExists = false;
                serverPayload = null;
                lastSavedJson = null;
                conflict = null;
                dirty = false;
                updateButtons();
                setStatus('saved', 'Guardado', 'Borrador eliminado del servidor');
            }).catch(function (error) {
                if (error.conflict) markConflict(error);
                else setStatus(navigator.onLine ? 'error' : 'offline', navigator.onLine ? 'Error' : 'Sin conexión', 'No se pudo eliminar el borrador');
                throw error;
            });
        });
    }

    function loadServerCopy() {
        return Swal.fire({
            icon: 'warning',
            title: '¿Cargar la versión del servidor?',
            text: 'Los cambios pendientes de este formulario se reemplazarán.',
            showCancelButton: true,
            confirmButtonText: 'Cargar servidor',
            cancelButtonText: 'Cancelar',
            customClass: { popup: 'bit-swal-popup' }
        }).then(function (result) {
            if (!result.isConfirmed && result.value !== true) return;
            return loadDraft(false).then(function (response) {
                if (!response || !response.exists) {
                    Swal.fire('Sin borrador', 'Ya no hay un borrador disponible en el servidor.', 'info');
                    return;
                }
                restorePayload(serverPayload || {}, response);
            });
        });
    }

    function overwriteServerCopy() {
        return Swal.fire({
            icon: 'warning',
            title: '¿Sobrescribir el borrador del servidor?',
            text: 'Se reemplazará la versión más reciente con los datos actuales.',
            showCancelButton: true,
            confirmButtonText: 'Sí, sobrescribir',
            cancelButtonText: 'Cancelar',
            customClass: { popup: 'bit-swal-popup' }
        }).then(function (result) {
            if (!result.isConfirmed && result.value !== true) return;
            return saveNow(true);
        });
    }

    function flush() {
        if (autosaveTimer) window.clearTimeout(autosaveTimer);
        autosaveTimer = null;
        var ready = initialLoadPromise || Promise.resolve();
        return ready.catch(function () {
            return loadDraft(true);
        }).then(function () {
            if (conflict) {
                var blocked = new Error('El borrador tiene un conflicto pendiente.');
                blocked.conflict = true;
                throw blocked;
            }
            return saveNow(false);
        }).then(function () {
            if (dirty) return flush();
            return getSubmissionMetadata();
        });
    }

    function clearLocalMetadata() {
        metadataGeneration++;
        if (autosaveTimer) window.clearTimeout(autosaveTimer);
        if (onlineRetryTimer) window.clearTimeout(onlineRetryTimer);
        autosaveTimer = null;
        onlineRetryTimer = null;
        restoring = true;
        dirty = false;
        token = null;
        version = 0;
        serverExists = false;
        serverPayload = null;
        lastSavedJson = null;
        conflict = null;
        updateButtons();
        setStatus('saved', 'Finalizado', 'El envío fue aceptado y el borrador se eliminó');
        window.setTimeout(function () { restoring = false; }, 0);
    }

    function getSubmissionMetadata() {
        return {
            token: serverExists ? token : null,
            version: serverExists ? version : null
        };
    }

    $form.on('input change', ':input[name]', function () {
        if (restoring) return;
        changeSerial++;
        dirty = true;
        setStatus(conflict ? 'conflict' : 'changes', conflict ? 'Conflicto' : 'Cambios', conflict ? 'Resuelve el conflicto para continuar' : 'Se guardarán en 5 segundos');
        scheduleAutosave();
    });

    $('#bit_draft_save').on('click', function () {
        saveNow(false).catch(function (error) {
            if (error.conflict) return;
            Swal.fire('No se pudo guardar', error.message || 'Intenta nuevamente.', 'error');
        });
    });
    $('#bit_draft_restore').on('click', loadServerCopy);
    $('#bit_draft_delete').on('click', function () { deleteDraft(true).catch(function () {}); });
    $('#bit_draft_load_server').on('click', function () { loadServerCopy().catch(function () {}); });
    $('#bit_draft_overwrite').on('click', function () { overwriteServerCopy().catch(function () {}); });

    $(window).on('offline', function () {
        if (dirty || !initialLoadComplete) setStatus('offline', 'Sin conexión', 'Los cambios siguen pendientes');
    });
    $(window).on('online', function () {
        if (onlineRetryTimer) window.clearTimeout(onlineRetryTimer);
        onlineRetryTimer = window.setTimeout(function () {
            onlineRetryTimer = null;
            if (!initialLoadComplete) {
                initialLoadPromise = loadDraft(true);
                initialLoadPromise.catch(function () {});
            } else if (dirty && !conflict) {
                scheduleAutosave(0);
            }
        }, 300);
    });

    $(window).on('pagehide', function () {
        if (!dirty || conflict || !initialLoadComplete || activeRequest || !navigator.sendBeacon) return;
        var state = currentPayloadState();
        if (serverExists && state.json === lastSavedJson) return;
        var body = new URLSearchParams();
        body.append('action', 'save');
        body.append('empresa_id', empresaId);
        body.append('payload', state.json);
        body.append('token', token || '');
        body.append('expected_version', String(version || 0));
        body.append('force', '0');
        body.append('csrf_token', csrfToken);
        navigator.sendBeacon(endpoint, new Blob([body.toString()], { type: 'application/x-www-form-urlencoded;charset=UTF-8' }));
    });

    window.bitacoraDraftStore = {
        flush: flush,
        clear: clearLocalMetadata,
        getSubmissionMetadata: getSubmissionMetadata,
        hasUnsavedChanges: function () { return dirty; }
    };

    initialLoadPromise = loadDraft(true);
    initialLoadPromise.catch(function () {});
});
