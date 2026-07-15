$(document).ready(function () {
  // === Configuración de toggles (unificado: inputSel sirve para textarea o number) ===
  const toggles = [
    { radioName: 'equipos_ti',       inputSel: '#ti',    groupSel: '#tiGroup',    defaultText: 'Sin novedades con los equipos.' },
    { radioName: 'facturas_ti',      inputSel: '#ti1',   groupSel: '#ti1Group',   defaultText: 'Las facturas electrónicas se integran con código CUFE.' },
    { radioName: 'novedades_ti',     inputSel: '#ti2',   groupSel: '#ti2Group',   defaultText: 'Sin novedades.' },
    { radioName: 'casos_ti',         inputSel: '#ti3',   groupSel: '#ti3Group',   defaultText: 'No se reportan casos o solicitudes ni pendientes.' },

    // OJO: aquí corregimos bmp -> bpm
    { radioName: 'visita_ss',        inputSel: '#bpm1',  groupSel: '#bpm1Group',  defaultText: 'Sin novedad.' },
    { radioName: 'visita_dagma',     inputSel: '#bpm2',  groupSel: '#bpm2Group',  defaultText: 'Sin novedad.' },
    { radioName: 'visita_west',      inputSel: '#bpm3',  groupSel: '#bpm3Group',  defaultText: 'Sin novedad.' },
    { radioName: 'novedad_grameras', inputSel: '#bpm8',  groupSel: '#bpm8Group',  defaultText: 'Sin novedad.' },

    { radioName: 'accidentes_sst',   inputSel: '#sst1',  groupSel: '#sst1Group',  defaultText: 'Sin novedades.' },
    { radioName: 'incapacidades_sst',inputSel: '#sst2',  groupSel: '#sst2Group',  defaultText: 'Sin novedades.' },
    { radioName: 'ambiente_laboral', inputSel: '#sst3',  groupSel: '#sst3Group',  defaultText: 'Sin novedades.' },
    { radioName: 'senal_sst',        inputSel: '#sst4',  groupSel: '#sst4Group',  defaultText: 'Sin novedades.' },
    { radioName: 'entrega_epp',      inputSel: '#sst6',  groupSel: '#sst6Group',  defaultText: 'Sin novedades.' },
    { radioName: 'novedades_sst',    inputSel: '#sst7',  groupSel: '#sst7Group',  defaultText: 'Sin novedades.' },
    { radioName: 'casos_sst',        inputSel: '#sst8',  groupSel: '#sst8Group',  defaultText: 'Sin novedades.' },

    { radioName: 'equipos_cocina',   inputSel: '#mant',  groupSel: '#mantGroup',  defaultText: 'Sin novedades.' },
    { radioName: 'equipos_bar',      inputSel: '#mant1', groupSel: '#mant1Group', defaultText: 'Sin novedades.' },
    { radioName: 'equipos_salon',    inputSel: '#mant2', groupSel: '#mant2Group', defaultText: 'Sin novedades.' },
    { radioName: 'locativos',        inputSel: '#mant3', groupSel: '#mant3Group', defaultText: 'Sin novedades.' },
    { radioName: 'pendientes',       inputSel: '#mant4', groupSel: '#mant4Group', defaultText: 'Sin novedades.' },

    // Ejemplo numérico: si #hielo es input[type=number]
    { radioName: 'hielo_produ',      inputSel: '#hielo', groupSel: '#hieloGroup', defaultText: 'Sin novedades.', defaultNumber: 0 },
    { radioName: 'hielo_kolbitos',        inputSel: '#hielo1', groupSel: '#hielo1Group', defaultText: 'Sin novedades.', defaultNumber: 0 },
    { radioName: 'hielo_consumo',        inputSel: '#hielo2', groupSel: '#hielo2Group', defaultText: 'Sin novedades.', defaultNumber: 0 },
    { radioName: 'hielo_enviado',        inputSel: '#hielo4', groupSel: '#hielo4Group', defaultText: 'Sin novedades.' },
    { radioName: 'hielo_recibido',        inputSel: '#hielo5', groupSel: '#hielo5Group', defaultText: 'Sin novedades.' },
    
    { radioName: 'facturas_mesas',        inputSel: '#fa_mesas', groupSel: '#fa_mesasGroup', defaultText: 'No se anularon facturas.' },
    { radioName: 'facturas_domic',        inputSel: '#fa_dom', groupSel: '#fa_domGroup', defaultText: 'No se anularon facturas.' },
    { radioName: 'facturas_rappi',        inputSel: '#fa_rappi', groupSel: '#fa_rappiGroup', defaultText: 'No se anularon facturas.' },
    { radioName: 'bonos_coomeva',        inputSel: '#tesor1', groupSel: '#tesor1Group', defaultText: 'No se canjearon bonos Coomeva.' },
    
    { radioName: 'reservas_15', inputSel: '#mer4', groupSel: '#mer4Group', defaultText: 'No se realizaron reservas.' },
    { radioName: 'easypedido', inputSel: '#tesor2', groupSel: '#tesor2Group', defaultText: 'No se realizaron pedidos por EasyPedido.' },
    
    { radioName: 'planta_elect', inputSel: '#mant5', groupSel: '#plantaGroup', defaultTime: '00:00' },
    { radioName: 'planta_elect', inputSel: '#mant6', groupSel: '#plantaGroup', defaultTime: '00:00' },
    { radioName: 'planta_elect', inputSel: '#mant7', groupSel: '#plantaGroup', defaultNumber: 0 },
    { radioName: 'planta_elect', inputSel: '#mant8', groupSel: '#plantaGroup', defaultText: 'No se presentaron novedades relacionadas a la planta eléctrica.' }
  ];

  // Helpers
  const isNumberInput = $el => $el.length && $el.is('input[type=number]');
  function isTimeInput($el) {
      return $el.is('input[type="time"]');
    }
  const coerceNumber = v => (v === null || v === undefined || String(v).trim() === '') ? '' : Number(v);

  // Solo UI (no toca valores)
  function applyToggleState(value, $group, $input) {
    if (value === 'Si') { $group.show();  $input.prop('required', true);  }
    else                { $group.hide();  $input.prop('required', false); }
  }

  // Escribe defaults cuando el radio está en "No" y el campo está vacío
  // Soporta textarea y number.
  function prepararDefaultsAntesDeGuardar() {
      toggles.forEach(t => {
        const val = $(`input[name="${t.radioName}"]:checked`).val();
        const $el = $(t.inputSel);
        if (!val || !$el.length) return;
    
        const actual = $.trim($el.val());
    
        // Solo escribimos default si el radio está en "No" y el campo está vacío
        if (val === 'No' && actual === '') {
          if (isNumberInput($el)) {
            $el.val(t.defaultNumber ?? 0);
          } else if (isTimeInput($el)) {
            // Para inputs type="time" (HH:MM)
            const def = t.defaultTime ?? '00:00';
            $el.val(def);
          } else {
            $el.val(t.defaultText ?? 'Sin novedades.');
          }
        }
      });
    }

  // Enlaza eventos e inicializa UI
  function bindToggle(t) {
    const $group = $(t.groupSel);
    const $input = $(t.inputSel);

    $(`input[name="${t.radioName}"]`).on('change', function () {
      applyToggleState(this.value, $group, $input);
    });

    const checked = $(`input[name="${t.radioName}"]:checked`).val();
    applyToggleState(checked, $group, $input);
  }
  toggles.forEach(bindToggle);

  // Radios simples (sin input asociado)
  const simpleRadios = ['bpm4', 'bpm5', 'bpm6', 'bpm7'];

  // Obtiene valor de un campo por name (convierte a número si corresponde)
  function getByName(name) {
    const $el = $(`[name="${name}"]`);
    if (!$el.length) return null;

    if ($el.is('select[multiple]')) {
      return $el.val() || [];
    }
    if ($el.is('input[type=radio]')) {
      return $(`input[name="${name}"]:checked`).val() || null;
    }
    if (isNumberInput($el)) {
      const raw = $el.val();
      return (raw === '' || raw === null || raw === undefined) ? '' : Number(raw);
    }
    return $el.val();
  }

  // Setea valor en un campo por name (maneja number / texto)
  function setByName(name, value) {
    const $el = $(`[name="${name}"]`);
    if (!$el.length) return;

    if ($el.is('select[multiple]')) {
      $el.val(Array.isArray(value) ? value : []).trigger('change');
      return;
    }
    if ($el.is('input[type=radio]')) {
      if (value) $(`input[name="${name}"][value="${value}"]`).prop('checked', true);
      else $(`input[name="${name}"]`).prop('checked', false);
      return;
    }
    if (isNumberInput($el)) {
      $el.val(value === '' || value === null || value === undefined ? '' : coerceNumber(value));
      return;
    }
    $el.val(value ?? '');
  }

  function obtenerValoresFormulario() {
    let campos = [
      "fechab","sede","responsable","cargo","sac","supervisores[]","act_sup",
      "comens","comens1","comens2","mesas","bar","cocina","coc","mer","mer1",
      "mer2","mer3","gh","sst1","sst2","sst3","sst4","sst5","sst6","sst7","sst8",
      "ti","ti1","ti2","ti3","mant","mant1","mant2","mant3","mant4","bpm","inv","inv1",
      "inv2","inv3","inv4","inv5","inv6","inv7","inv8","inv9","inv10","inv11","inv12",
      "inv13","inv14","inv15","inv16","inv17","inv18","inv19","inv20","inv21","inv23",
      "desp","dorp","dorp1","fa_mesas","fa_dom","fa_rappi","tesor","rappi","domi",
      "domiexpress","hdomi","pd","tp","hielo","hielo1","inv24","hielo2","hielo3","reu",
      "nov_chetano","ventas_chetano","coord","coord1","coord2","coord3","coord4",
      "coord5","coord6","coord7","coord8","coord9","coord10","coord11","coord12",
      "coord13","coord14","coord15","coord16","coord17","coord18","coord19","coord20",
      "coord21","dom_chetano","mp_chetano","nov_torito","ventas_torito","devo","hielo4",
      "hielo5","tesor1",
      "bpm1","bpm2","bpm3","bpm4","bpm5","bpm6","bpm7","bpm8",
      "equipo_bpm[]",
      // radios detalle mejoramiento
      "visita_ss","visita_dagma","visita_west","novedad_grameras",
      // radios detalle TI
      "equipos_ti","facturas_ti","novedades_ti","casos_ti",
      // radios detalle SST
      "accidentes_sst","incapacidades_sst","ambiente_laboral","senal_sst","equipo_sst[]",
      "entrega_epp","novedades_sst","casos_sst",
      // radios detalle MANT
      "equipos_cocina","equipos_bar","equipos_salon","locativos","pendientes",
      // radios del área bar
      "hielo_produ","hielo_kolbitos","hielo_consumo","hielo_enviado","hielo_recibido",
      // radios de tesoreria
      "facturas_mesas","facturas_domic","facturas_rappi","bonos_coomeva",
      //radio y nuevo campo de mercadeo
      "reservas_15","mer4",
      //radio y nuevo campo de tesorería
      "easypedido","tesor2",
      //Nuevo campo para mantenimiento (Planta Eléctrica)
      "planta_elect","mant5","mant6","mant7","mant8",
      //Campos tipo time
      "hora_entrada","hora_salida"
    ];

    let bitacora = {};
    campos.forEach(campo => {
      if (campo === "supervisores[]") {
        bitacora["supervisores"] = $('select[name="supervisores[]"]').val() || [];
      } else if (campo === "equipo_bpm[]") {
        bitacora["equipo_bpm"] = $('select[name="equipo_bpm[]"]').val() || [];
      } else if (campo === "equipo_sst[]") {
        bitacora["equipo_sst"] = $('select[name="equipo_sst[]"]').val() || [];
      } else if (simpleRadios.includes(campo)) {
        bitacora[campo] = $(`input[name="${campo}"]:checked`).val() || null;
      } else {
        bitacora[campo] = getByName(campo);
      }
    });

    const procesadosDinamicos = {};
    $('[data-dynamic-field][name]').each(function () {
      const $el = $(this);
      const rawName = $el.attr('name') || '';
      const name = rawName.replace(/\[\]$/, '');
      if (!name || procesadosDinamicos[name]) return;
      procesadosDinamicos[name] = true;

      if ($el.is('input[type=radio]')) {
        bitacora[name] = $(`input[name="${rawName}"]:checked`).val() || null;
      } else if ($el.is('select[multiple]')) {
        bitacora[name] = $el.val() || [];
      } else if ($el.is('input[type=number]')) {
        const raw = $el.val();
        bitacora[name] = (raw === '' || raw === null || raw === undefined) ? '' : Number(raw);
      } else {
        bitacora[name] = $el.val();
      }
    });
    return bitacora;
  }
  
    function normalizeTime(v) {
      if (v === null || v === undefined) return '';
      const s = String(v).trim();
      // Si quieres que 00:00 se vea vacío al cargar:
      return (s === '' || s === '00:00') ? '' : s;
    }
    
    const NO_APLICA_SUP = 'No hay visita por parte de los supervisores';

    function getSupArr() {
      const v = $('#supervisores').val();
      if (!v) return [];
      return Array.isArray(v) ? v : [v];
    }
    
    function toggleContenedorSup() {
      const arr = getSupArr();
      const noAplica = arr.includes(NO_APLICA_SUP);
      const mostrar = arr.length > 0 && !noAplica; //bool
    
      $('#contenedor_sup').toggle(mostrar);
    
      // ✅ Si se muestra: required + enabled
      // ✅ Si NO se muestra: required=false + disabled=true (clave para que no bloquee el envío)
      $('#act_sup')
        .prop('required', mostrar)
        .prop('disabled', !mostrar);
    
      $('#hora_entrada, #hora_salida')
        .prop('required', mostrar)
        .prop('disabled', !mostrar);
    
      if (!mostrar) {
        $('#act_sup').val('');
        $('#hora_entrada, #hora_salida').val('');
      }
    }
    
    // En vivo cuando cambie el select2
    let fixingSup = false;

    $('#supervisores').on('change', function () {
      if (fixingSup) return;
    
      let arr = getSupArr();
    
      // Si está NO_APLICA y además otros, deja solo NO_APLICA
      if (arr.includes(NO_APLICA_SUP) && arr.length > 1) {
        fixingSup = true;
        $(this).val([NO_APLICA_SUP]).trigger('change');
        fixingSup = false;
        return;
      }
    
      toggleContenedorSup();
    });

  // --- Helpers para data-sede ---
    function includesSede($el, sede) {
      const sedes = ($el.attr('data-sede') || '')
        .split(',')
        .map(s => $.trim(s.toUpperCase()));
      return sedes.includes((sede || '').toUpperCase());
    }
    
    function toggleCamposPorSede(sedeSeleccionada) {
      $('[data-sede]').each(function () {
        const $el = $(this);
        const permitido = includesSede($el, sedeSeleccionada);
    
        const $contenedor = $el.closest('.form-group, [class*="col-"], fieldset, .container, .row').length
          ? $el.closest('.form-group, [class*="col-"], fieldset, .container, .row')
          : $el;
    
        const isInput = $el.is('input, select, textarea');
    
        if (permitido) {
          $contenedor.show();
          if (isInput) {
            if ($el.data('was-required') === true) $el.prop('required', true);
            $el.prop('disabled', false);
          }
        } else {
          $contenedor.hide();
          if (isInput) {
            if ($el.prop('required') && $el.data('was-required') !== true) {
              $el.data('was-required', true);
            }
            $el.prop('required', false).prop('disabled', true);
    
            if ($el.hasClass('select2-hidden-accessible')) {
              $el.val(null).trigger('change');
            } else if ($el.is('select')) {
              $el.prop('selectedIndex', 0);
            } else {
              $el.val('');
            }
          }
        }
      });
    }
    
    function clearCamposPorSede(sede) {
      $('[data-sede]').each(function () {
        const $el = $(this);
        if (!includesSede($el, sede)) return;
    
        const $contenedor = $el.closest('.form-group, [class*="col-"], fieldset, .container, .row').length
          ? $el.closest('.form-group, [class*="col-"], fieldset, .container, .row')
          : $el;
    
        const isInput = $el.is('input, select, textarea');
    
        if (isInput) {
          $el.prop('required', false).prop('disabled', true);
          if ($el.hasClass('select2-hidden-accessible')) {
            $el.val(null).trigger('change');
          } else if ($el.is('select')) {
            $el.prop('selectedIndex', 0);
          } else {
            $el.val('');
          }
        }
        $contenedor.hide();
      });
    }
    
    // Vincula el select de sede para mostrar/ocultar en vivo
    $('#idSede').on('change', function () {
      toggleCamposPorSede($(this).val());
    });
    
    // Ejecuta una vez al cargar la página (por si el select trae valor)
    toggleCamposPorSede($('#idSede').val() || '');

  function asignarValoresFormulario(bitacora) {
    // 1) Radios de los toggles
    toggles.forEach(t => {
      const v = bitacora[t.radioName] || null;
      if (v) $(`input[name="${t.radioName}"][value="${v}"]`).prop('checked', true);
      else   $(`input[name="${t.radioName}"]`).prop('checked', false);
    });

    // 2) Inputs de los toggles (textarea o number)
    toggles.forEach(t => {
      const nameAttr = $(t.inputSel).attr('name') || null;
      if (nameAttr && Object.prototype.hasOwnProperty.call(bitacora, nameAttr)) {
        setByName(nameAttr, bitacora[nameAttr]);
      }
    });

    // 3) Resto de campos (incluye radios simples)
    Object.keys(bitacora).forEach(campo => {
      if (toggles.some(t => t.radioName === campo)) return;
      if (toggles.some(t => ($(t.inputSel).attr('name') || '') === campo)) return;

      const valor = bitacora[campo];
      if (simpleRadios.includes(campo)) {
        if (valor) $(`input[name="${campo}"][value="${valor}"]`).prop('checked', true);
        else       $(`input[name="${campo}"]`).prop('checked', false);
        return;
      }
      setByName(campo, valor);
    });

    // 4) Select2 múltiples (si aplica)
    $('select[name="equipo_bpm[]"]').val(bitacora.equipo_bpm || []).trigger('change');
    $('select[name="equipo_sst[]"]').val(bitacora.equipo_sst || []).trigger('change');
    
    $('select[name="supervisores[]"]').val(bitacora.supervisores || []).trigger('change');
    setByName('hora_entrada', normalizeTime(bitacora.hora_entrada));
    setByName('hora_salida',  normalizeTime(bitacora.hora_salida));
    
    // toggle al final para asegurar estado final
    toggleContenedorSup();

    // 5) Aplica UI final
    toggles.forEach(t => {
      const v = $(`input[name="${t.radioName}"]:checked`).val();
      applyToggleState(v, $(t.groupSel), $(t.inputSel));
    });
  }

  function resetSelect2WithTags($sel, $containerToHide) {
    $sel.val(null).trigger('change');
    $sel.find('option[data-select2-tag="true"]').remove();
    $sel.find('option').prop('disabled', false);
    if ($containerToHide && $containerToHide.length){
        $containerToHide.hide();
        // Si el contenedor es el de supervisores, resetea horas y textarea
        if ($containerToHide.attr('id') === 'contenedor_sup') {
          $('#hora_entrada, #hora_salida').prop('required', false).val('');
          $('#act_sup').val('');
        }
    }
  }

  // --- Botones guardar/cargar ---
  $('#guardar_local_storage').on('click', function () {
    // Rellena defaults cuando radio=No
    prepararDefaultsAntesDeGuardar();

    const bitacora = obtenerValoresFormulario();
    localStorage.setItem('bitacora', JSON.stringify(bitacora));
    Swal.fire('Guardado', 'Los datos han sido guardados temporalmente. Para cargar la información dar clic en "Cargar Información Temporal"', 'success');

    // Reset visual
    $('.form-bitacora')[0].reset();
    toggles.forEach(t => applyToggleState(undefined, $(t.groupSel), $(t.inputSel)));
    simpleRadios.forEach(n => $(`input[name="${n}"]`).prop('checked', false));
    resetSelect2WithTags($('#supervisores'), $('#contenedor_sup'));
    resetSelect2WithTags($('#equipo_bpm'), $('#contenedor_bpm'));
    resetSelect2WithTags($('#equipo_sst'), $('#contenedor_sst'));
    // Además limpia/oculta todo lo de PANCE
    clearCamposPorSede('PANCE');
  });

  $('#cargar_local_storage').on('click', function () {
    let bitacora = localStorage.getItem('bitacora');
    if (!bitacora) {
      Swal.fire('Error', 'No hay datos guardados en Local Storage', 'error');
      return;
    }
    asignarValoresFormulario(JSON.parse(bitacora));
    // Muestra/oculta según la sede cargada
    toggleCamposPorSede($('#idSede').val() || '');
    
  });
});
