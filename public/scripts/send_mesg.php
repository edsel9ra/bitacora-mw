<?php
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require_once __DIR__ . '/../config/security.php';
    require_once __DIR__ . '/../config/mailer.php';
    app_require_post_login(1);

    if($_POST){
        
        $fields = [
            'fechab', 'sede', 'responsable', 'cargo', 'sac', 'comens', 'comens1', 
            'comens2', 'mesas', 'bar', 'cocina', 'coc', 'mer', 'mer1', 'mer2', 'mer3', 
            'gh', 'sst1', 'sst2', 'sst3', 'sst4', 'sst5', 'sst6', 'sst7', 'sst8', 'ti', 
            'ti1', 'ti2', 'ti3', 'mant', 'mant1', 'mant2', 'mant3', 'mant4', 'bpm', 'desp', 'dorp', 
            'dorp1', 'fa_mesas', 'fa_dom', 'fa_rappi', 'tesor', 'rappi', 'domi', 'tp', 'domiexpress', 
            'hdomi', 'pd', 'supervisores', 'act_sup', 'hielo', 'hielo1', 'hielo2', 'hielo3', 
            'hielo4', 'hielo5', 'devo', 'tesor1', 'bpm1', 'bpm2', 'bpm3', 'bpm4', 'bpm5', 'bpm6', 'bpm7', 
            'bpm8', 'equipo_bpm', 'visita_ss', 'visita_dagma', 'visita_west', 'novedad_grameras',
            'equipos_ti', 'facturas_ti', 'novedades_ti', 'casos_ti','accidentes_sst','incapacidades_sst',
            'ambiente_laboral','senal_sst','equipo_sst','entrega_epp','novedades_sst','casos_sst',
            'equipos_cocina','equipos_bar','equipos_salon','locativos','pendientes','hielo_produ',
            'hielo_kolbitos','hielo_consumo','hielo_enviado','hielo_recibido','facturas_mesas',
            'facturas_domic','facturas_rappi','bonos_coomeva','reservas_15','mer4','easypedido',
            'tesor2', 'nov_chetano', 'ventas_chetano', 'dom_chetano','mp_chetano',
            'planta_elect','mant5','mant6','mant7','mant8','hora_entrada','hora_salida'
        ];
        
        // VALORES POR DEFECTO SEGÚN ÁREA
        $DEFAULT_BPM = 'Sin novedad.';
        $DEFAULT_TI = 'Sin novedades con los equipos.';
        $DEFAULT_TI1 = 'Las facturas electrónicas se integran con código CUFE.';
        $DEFAULT_TI2 = 'Sin novedades.';
        $DEFAULT_TI3 = 'No se reportan casos o solicitudes ni pendientes.';
        $DEFAULT_GH = 'Sin novedad en Gestión Humana.';
        $DEFAULT_SST = 'Sin novedades.';
        $DEFAULT_MANT = 'No se presentan novedades.';
        $DEFAULT_PE = 'No se presentaron novedades relacionadas a la planta eléctrica.';
        $DEFAULT_BH_ENVIADAS = 'No se enviaron bolsas a otras sedes.';
        $DEFAULT_BH_RECIBIDAS = 'No se recibieron bolsas de otras sedes.';
        $DEFAULT_FACTURAS = 'No se anularon facturas.';
        $DEFAULT_BONOS = 'No se canjearon bonos Coomeva.';
        $DEFAULT_RESERVAS = 'No se realizaron reservas.';
        $DEFAULT_EASYPEDIDO = 'No se realizaron pedidos por EasyPedido.';
        
        // Función para calcular minutos entre dos horas HH:MM (soporta cruce de medianoche)
        function calcularMinutosPlanta($inicio, $fin)
        {
            if (!$inicio || !$fin) {
                return 0;
            }
        
            list($h1, $m1) = explode(':', $inicio);
            list($h2, $m2) = explode(':', $fin);
        
            $min1 = (int)$h1 * 60 + (int)$m1;
            $min2 = (int)$h2 * 60 + (int)$m2;
        
            $diff = $min2 - $min1;
            if ($diff < 0) {
                $diff += 24 * 60; // si se cruzó medianoche
            }
            return $diff;
        }
    
        // CAMPOS MEJORAMIENTO
        // --- Secretaría de Salud (bpm1)
        $visita_ss = $_POST['visita_ss'] ?? null;
        if ($visita_ss === 'No') {
            if (!isset($_POST['bpm1']) || trim($_POST['bpm1']) === '') {
                $_POST['bpm1'] = $DEFAULT_BPM;
            }
        } elseif ($visita_ss === 'Si') {
            if (isset($_POST['bpm1']) && trim($_POST['bpm1']) === $DEFAULT_BPM) {
                $_POST['bpm1'] = '';
            }
        }
    
        // --- DAGMA (bpm2)
        $visita_dagma = $_POST['visita_dagma'] ?? null;
        if ($visita_dagma === 'No') {
            if (!isset($_POST['bpm2']) || trim($_POST['bpm2']) === '') {
                $_POST['bpm2'] = $DEFAULT_BPM;
            }
        } elseif ($visita_dagma === 'Si') {
            if (isset($_POST['bpm2']) && trim($_POST['bpm2']) === $DEFAULT_BPM) {
                $_POST['bpm2'] = '';
            }
        }
    
        // --- West-Klaxen (bpm3)
        $visita_west = $_POST['visita_west'] ?? null;
        if ($visita_west === 'No') {
            if (!isset($_POST['bpm3']) || trim($_POST['bpm3']) === '') {
                $_POST['bpm3'] = $DEFAULT_BPM;
            }
        } elseif ($visita_west === 'Si') {
            if (isset($_POST['bpm3']) && trim($_POST['bpm3']) === $DEFAULT_BPM) {
                $_POST['bpm3'] = '';
            }
        }
    
        // --- Grameras/termómetros (bpm8)
        $novedad_grameras = $_POST['novedad_grameras'] ?? null;
        if ($novedad_grameras === 'No') {
            if (!isset($_POST['bpm8']) || trim($_POST['bpm8']) === '') {
                $_POST['bpm8'] = $DEFAULT_BPM;
            }
        } elseif ($novedad_grameras === 'Si') {
            if (isset($_POST['bpm8']) && trim($_POST['bpm8']) === $DEFAULT_BPM) {
                $_POST['bpm8'] = '';
            }
        }
    
        // CAMPOS TI
        // --- Facturas Electrónicas (ti1)
        $facturas_ti = $_POST['facturas_ti'] ?? null;
        if ($facturas_ti === 'No') {
            if (!isset($_POST['ti1']) || trim($_POST['ti1']) === '') {
                $_POST['ti1'] = $DEFAULT_TI1;
            }
        } elseif ($facturas_ti === 'Si') {
            if (isset($_POST['ti1']) && trim($_POST['ti1']) === $DEFAULT_TI1) {
                $_POST['ti1'] = '';
            }
        }
    
        // --- Otras novedades TI (ti2)
        $novedades_ti = $_POST['novedades_ti'] ?? null;
        if ($novedades_ti === 'No') {
            if (!isset($_POST['ti2']) || trim($_POST['ti2']) === '') {
                $_POST['ti2'] = $DEFAULT_TI2;
            }
        } elseif ($novedades_ti === 'Si') {
            if (isset($_POST['ti2']) && trim($_POST['ti2']) === $DEFAULT_TI2) {
                $_POST['ti2'] = '';
            }
        }
    
        // --- Solicitudes - Casos TI (ti3)
        $casos_ti = $_POST['casos_ti'] ?? null;
        if ($casos_ti === 'No') {
            if (!isset($_POST['ti3']) || trim($_POST['ti3']) === '') {
                $_POST['ti3'] = $DEFAULT_TI3;
            }
        } elseif ($casos_ti === 'Si') {
            if (isset($_POST['ti3']) && trim($_POST['ti3']) === $DEFAULT_TI3) {
                $_POST['ti3'] = '';
            }
        }
    
        // CAMPOS SST
        // --- Accidentes laborares y transito
        $accidentes_sst = $_POST['accidentes_sst'] ?? null;
        if ($accidentes_sst === 'No') {
            if (!isset($_POST['sst1']) || trim($_POST['sst1']) === '') {
                $_POST['sst1'] = $DEFAULT_SST;
            }
        } elseif ($accidentes_sst === 'Si') {
            if (isset($_POST['sst1']) && trim($_POST['sst1']) === $DEFAULT_SST) {
                $_POST['sst1'] = '';
            }
        }
    
        // --- Incapacidades
        $incapacidades_sst = $_POST['incapacidades_sst'] ?? null;
        if ($incapacidades_sst === 'No') {
            if (!isset($_POST['sst2']) || trim($_POST['sst2']) === '') {
                $_POST['sst2'] = $DEFAULT_SST;
            }
        } elseif ($incapacidades_sst === 'Si') {
            if (isset($_POST['sst2']) && trim($_POST['sst2']) === $DEFAULT_SST) {
                $_POST['sst2'] = '';
            }
        }
    
        // --- Ambiente Laboral
        $ambiente_laboral = $_POST['ambiente_laboral'] ?? null;
        if ($ambiente_laboral === 'No') {
            if (!isset($_POST['sst3']) || trim($_POST['sst3']) === '') {
                $_POST['sst3'] = $DEFAULT_SST;
            }
        } elseif ($ambiente_laboral === 'Si') {
            if (isset($_POST['sst3']) && trim($_POST['sst3']) === $DEFAULT_SST) {
                $_POST['sst3'] = '';
            }
        }
    
        // --- Señalización y Extintores
        $senal_sst = $_POST['senal_sst'] ?? null;
        if ($senal_sst === 'No') {
            if (!isset($_POST['sst4']) || trim($_POST['sst4']) === '') {
                $_POST['sst4'] = $DEFAULT_SST;
            }
        } elseif ($senal_sst === 'Si') {
            if (isset($_POST['sst4']) && trim($_POST['sst4']) === $DEFAULT_SST) {
                $_POST['sst4'] = '';
            }
        }
    
        // --- Entrega EPP
        $entrega_epp = $_POST['entrega_epp'] ?? null;
        if ($entrega_epp === 'No') {
            if (!isset($_POST['sst6']) || trim($_POST['sst6']) === '') {
                $_POST['sst6'] = $DEFAULT_SST;
            }
        } elseif ($entrega_epp === 'Si') {
            if (isset($_POST['sst6']) && trim($_POST['sst6']) === $DEFAULT_SST) {
                $_POST['sst6'] = '';
            }
        }
    
        // --- Novedades SST
        $novedades_sst = $_POST['novedades_sst'] ?? null;
        if ($novedades_sst === 'No') {
            if (!isset($_POST['sst7']) || trim($_POST['sst7']) === '') {
                $_POST['sst7'] = $DEFAULT_SST;
            }
        } elseif ($novedades_sst === 'Si') {
            if (isset($_POST['sst7']) && trim($_POST['sst7']) === $DEFAULT_SST) {
                $_POST['sst7'] = '';
            }
        }
    
        // --- Casos SST
        $casos_sst = $_POST['casos_sst'] ?? null;
        if ($casos_sst === 'No') {
            if (!isset($_POST['sst8']) || trim($_POST['sst8']) === '') {
                $_POST['sst8'] = $DEFAULT_SST;
            }
        } elseif ($casos_sst === 'Si') {
            if (isset($_POST['sst8']) && trim($_POST['sst8']) === $DEFAULT_SST) {
                $_POST['sst8'] = '';
            }
        }
    
        // CAMPOS MANTENIMIENTO
        // --- Equipos de Cocina
        $equipos_cocina = $_POST['equipos_cocina'] ?? null;
        if ($equipos_cocina === 'No') {
            if (!isset($_POST['mant']) || trim($_POST['mant']) === '') {
                $_POST['mant'] = $DEFAULT_MANT;
            }
        } elseif ($equipos_cocina === 'Si') {
            if (isset($_POST['mant']) && trim($_POST['mant']) === $DEFAULT_MANT) {
                $_POST['mant'] = '';
            }
        }
    
        // --- Equipos de Bar
        $equipos_bar = $_POST['equipos_bar'] ?? null;
        if ($equipos_bar === 'No') {
            if (!isset($_POST['mant1']) || trim($_POST['mant1']) === '') {
                $_POST['mant1'] = $DEFAULT_MANT;
            }
        } elseif ($equipos_bar === 'Si') {
            if (isset($_POST['mant1']) && trim($_POST['mant1']) === $DEFAULT_MANT) {
                $_POST['mant1'] = '';
            }
        }
    
        // --- Equipos de Salón
        $equipos_salon = $_POST['equipos_salon'] ?? null;
        if ($equipos_salon === 'No') {
            if (!isset($_POST['mant2']) || trim($_POST['mant2']) === '') {
                $_POST['mant2'] = $DEFAULT_MANT;
            }
        } elseif ($equipos_salon === 'Si') {
            if (isset($_POST['mant2']) && trim($_POST['mant2']) === $DEFAULT_MANT) {
                $_POST['mant2'] = '';
            }
        }
    
        // ---- Locativos
        $locativos = $_POST['locativos'] ?? null;
        if ($locativos === 'No') {
            if (!isset($_POST['mant3']) || trim($_POST['mant3']) === '') {
                $_POST['mant3'] = $DEFAULT_MANT;
            }
        } elseif ($locativos === 'Si') {
            if (isset($_POST['mant3']) && trim($_POST['mant3']) === $DEFAULT_MANT) {
                $_POST['mant3'] = '';
            }
        }
        
        // --- PLANTA ELÉCTRICA
        $planta_elect = $_POST['planta_elect'] ?? null;
        
        // Si NO hubo uso de planta: poner defaults
        if ($planta_elect === 'No') {
            if (!isset($_POST['mant5']) || trim($_POST['mant5']) === '') {
                $_POST['mant5'] = '00:00'; // hora encendido
            }
            if (!isset($_POST['mant6']) || trim($_POST['mant6']) === '') {
                $_POST['mant6'] = '00:00'; // hora apagado
            }
            if (!isset($_POST['mant7']) || trim($_POST['mant7']) === '') {
                $_POST['mant7'] = 0;       // minutos de uso
            }
            if (!isset($_POST['mant8']) || trim($_POST['mant8']) === '') {
                $_POST['mant8'] = $DEFAULT_PE; // texto novedad planta
            }
        // Si SÍ hubo uso de planta: si hay horas pero no minutos, calcularlos
        } elseif ($planta_elect === 'Si') {
        
            $ini = $_POST['mant5'] ?? '';
            $fin = $_POST['mant6'] ?? '';
            $minsActual = trim($_POST['mant7'] ?? '');
            if ($ini !== '' && $fin !== '' && $minsActual === '') {
                $_POST['mant7'] = calcularMinutosPlanta($ini, $fin);
            }
            // Si alguien dejó el texto exacto del default por error, lo limpiamos
            if (isset($_POST['mant8']) && trim($_POST['mant8']) === $DEFAULT_PE) {
                $_POST['mant8'] = '';
            }
        }
    
        // ---- Pendientes de Mantenimiento
        $pendientes = $_POST['pendientes'] ?? null;
        if ($pendientes === 'No') {
            if (!isset($_POST['mant4']) || trim($_POST['mant4']) === '') {
                $_POST['mant4'] = $DEFAULT_MANT;
            }
        } elseif ($pendientes === 'Si') {
            if (isset($_POST['mant4']) && trim($_POST['mant4']) === $DEFAULT_MANT) {
                $_POST['mant4'] = '';
            }
        }
    
        // CAMPOS ÁREA BAR
        // --- Hielo enviado a otras sedes
        $hielo_enviado = $_POST['hielo_enviado'] ?? null;
        if ($hielo_enviado === 'No') {
            if (!isset($_POST['hielo4']) || trim($_POST['hielo4']) === '') {
                $_POST['hielo4'] = $DEFAULT_BH_ENVIADAS;
            }
        } elseif ($hielo_enviado === 'Si') {
            if (isset($_POST['hielo4']) && trim($_POST['hielo4']) === $DEFAULT_BH_ENVIADAS) {
                $_POST['hielo4'] = '';
            }
        }
    
        // --- Hielo recibido de otras sedes
        $hielo_recibido = $_POST['hielo_recibido'] ?? null;
        if ($hielo_recibido === 'No') {
            if (!isset($_POST['hielo5']) || trim($_POST['hielo5']) === '') {
                $_POST['hielo5'] = $DEFAULT_BH_RECIBIDAS;
            }
        } elseif ($hielo_recibido === 'Si') {
            if (isset($_POST['hielo5']) && trim($_POST['hielo5']) === $DEFAULT_BH_RECIBIDAS) {
                $_POST['hielo5'] = '';
            }
        }
    
        // CAMPOS TESORERIA
        // --- Facturas anuladas Mesas
        $facturas_mesas = $_POST['facturas_mesas'] ?? null;
        if ($facturas_mesas === 'No') {
            if (!isset($_POST['fa_mesas']) || trim($_POST['fa_mesas']) === '') {
                $_POST['fa_mesas'] = $DEFAULT_FACTURAS;
            }
        } elseif ($facturas_mesas === 'Si') {
            if (isset($_POST['fa_mesas']) && trim($_POST['fa_mesas']) === $DEFAULT_FACTURAS) {
                $_POST['fa_mesas'] = '';
            }
        }
    
        // --- Facturas anuladas Domicilios
        $facturas_domic = $_POST['facturas_domic'] ?? null;
        if ($facturas_domic === 'No') {
            if (!isset($_POST['fa_dom']) || trim($_POST['fa_dom']) === '') {
                $_POST['fa_dom'] = $DEFAULT_FACTURAS;
            }
        } elseif ($facturas_domic === 'Si') {
            if (isset($_POST['fa_dom']) && trim($_POST['fa_dom']) === $DEFAULT_FACTURAS) {
                $_POST['fa_dom'] = '';
            }
        }
    
        // --- Facturas anuladas Rappi
        $facturas_rappi = $_POST['facturas_rappi'] ?? null;
        if ($facturas_rappi === 'No') {
            if (!isset($_POST['fa_rappi']) || trim($_POST['fa_rappi']) === '') {
                $_POST['fa_rappi'] = $DEFAULT_FACTURAS;
            }
        } elseif ($facturas_rappi === 'Si') {
            if (isset($_POST['fa_rappi']) && trim($_POST['fa_rappi']) === $DEFAULT_FACTURAS) {
                $_POST['fa_rappi'] = '';
            }
        }
    
        // --- Bonos Coomeva canjeados
        $bonos_coomeva = $_POST['bonos_coomeva'] ?? null;
        if ($bonos_coomeva === 'No') {
            if (!isset($_POST['tesor1']) || trim($_POST['tesor1']) === '') {
                $_POST['tesor1'] = $DEFAULT_BONOS;
            }
        } elseif ($bonos_coomeva === 'Si') {
            if (isset($_POST['tesor1']) && trim($_POST['tesor1']) === $DEFAULT_BONOS) {
                $_POST['tesor1'] = '';
            }
        }
        
        // --- Pagos EasyPedido
        $easypedido = $_POST['easypedido'] ?? null;
        if ($easypedido === 'No') {
            if (!isset($_POST['tesor2']) || trim($_POST['tesor2']) === '') {
                $_POST['tesor2'] = $DEFAULT_EASYPEDIDO;
            }
        } elseif ($easypedido === 'Si') {
            if (isset($_POST['tesor2']) && trim($_POST['tesor2']) === $DEFAULT_EASYPEDIDO) {
                $_POST['tesor2'] = '';
            }
        }
        
        // CAMPO MERCADEO
        // --- Reservas
        $reservas_15 = $_POST['reservas_15'] ?? null;
        if ($reservas_15 === 'No') {
            if (!isset($_POST['mer4']) || trim($_POST['mer4']) === '') {
                $_POST['mer4'] = $DEFAULT_RESERVAS;
            }
        } elseif ($reservas_15 === 'Si') {
            if (isset($_POST['mer4']) && trim($_POST['mer4']) === $DEFAULT_RESERVAS) {
                $_POST['mer4'] = '';
            }
        }
    
        function hasEmptyFields($data, $fields) {
            foreach ($fields as $field) {
                if (isset($data[$field])){
                    if ($field === 'act_sup' && isset($data['supervisores']) && in_array("No hay visita por parte de los supervisores", $data['supervisores'])) {
                        continue;
                    }
                    
                    if ($field === 'bpm' && isset($data['equipo_bpm']) && in_array("No hay visita por parte del área", $data['equipo_bpm'])) {
                        continue;
                    }
                    
                    if ($field === 'sst5' && isset($data['equipo_sst']) && in_array("No hay visita por parte del área", $data['equipo_sst'])) {
                        continue;
                    }
    
                    // Campo "gh" no obligatorio
                    if ($field === 'gh')
                        continue;
    
                    // CAMPOS MEJORAMIENTO
                    if ($field === 'bpm1' && isset($data['visita_ss']) && $data['visita_ss'] === 'No')
                        continue;
                    if ($field === 'bpm2' && isset($data['visita_dagma']) && $data['visita_dagma'] === 'No')
                        continue;
                    if ($field === 'bpm3' && isset($data['visita_west']) && $data['visita_west'] === 'No')
                        continue;
                    if ($field === 'bpm8' && isset($data['novedad_grameras']) && $data['novedad_grameras'] === 'No')
                        continue;
    
                    // CAMPOS TI
                    if ($field === 'ti' && isset($data['equipos_ti']) && $data['equipos_ti'] === 'No')
                        continue;
                    if ($field === 'ti1' && isset($data['facturas_ti']) && $data['facturas_ti'] === 'No')
                        continue;
                    if ($field === 'ti2' && isset($data['novedades_ti']) && $data['novedades_ti'] === 'No')
                        continue;
                    if ($field === 'ti3' && isset($data['casos_ti']) && $data['casos_ti'] === 'No')
                        continue;
    
                    // CAMPOS SST
                    if ($field === 'sst1' && isset($data['accidentes_sst']) && $data['accidentes_sst'] === 'No')
                        continue;
                    if ($field === 'sst2' && isset($data['incapacidades_sst']) && $data['incapacidades_sst'] === 'No')
                        continue;
                    if ($field === 'sst3' && isset($data['ambiente_laboral']) && $data['ambiente_laboral'] === 'No')
                        continue;
                    if ($field === 'sst4' && isset($data['senal_sst']) && $data['senal_sst'] === 'No')
                        continue;
                    if ($field === 'sst6' && isset($data['entrega_epp']) && $data['entrega_epp'] === 'No')
                        continue;
                    if ($field === 'sst7' && isset($data['novedades_sst']) && $data['novedades_sst'] === 'No')
                        continue;
                    if ($field === 'sst8' && isset($data['casos_sst']) && $data['casos_sst'] === 'No')
                        continue;
    
                    // CAMPOS MANTENIMIENTO
                    if ($field === 'mant' && isset($data['equipos_cocina']) && $data['equipos_cocina'] === 'No')
                        continue;
                    if ($field === 'mant1' && isset($data['equipos_bar']) && $data['equipos_bar'] === 'No')
                        continue;
                    if ($field === 'mant2' && isset($data['equipos_salon']) && $data['equipos_salon'] === 'No')
                        continue;
                    if ($field === 'mant3' && isset($data['locativos']) && $data['locativos'] === 'No')
                        continue;
                    if ($field === 'mant4' && isset($data['pendientes']) && $data['pendientes'] === 'No')
                        continue;
                    // PLANTA ELÉCTRICA
                    if (in_array($field, ['mant5', 'mant6', 'mant7', 'mant8'], true) && isset($data['planta_elect']) && $data['planta_elect'] === 'No')
                        continue;
    
                    // ÁREA DE BAR
                    if ($field === 'hielo' && isset($data['hielo_produ']) && $data['hielo_produ'] === 'No')
                        continue;
                    if ($field === 'hielo1' && isset($data['hielo_kolbitos']) && $data['hielo_kolbitos'] === 'No')
                        continue;
                    if ($field === 'hielo2' && isset($data['hielo_consumo']) && $data['hielo_consumo'] === 'No')
                        continue;
                    if ($field === 'hielo4' && isset($data['hielo_enviado']) && $data['hielo_enviado'] === 'No')
                        continue;
                    if ($field === 'hielo5' && isset($data['hielo_recibido']) && $data['hielo_recibido'] === 'No')
                        continue;
    
                    // CAMPOS TESORERIA
                    if ($field === 'fa_mesas' && isset($data['facturas_mesas']) && $data['facturas_mesas'] === 'No')
                        continue;
                    if ($field === 'fa_dom' && isset($data['facturas_domic']) && $data['facturas_domic'] === 'No')
                        continue;
                    if ($field === 'fa_rappi' && isset($data['facturas_rappi']) && $data['facturas_rappi'] === 'No')
                        continue;
                    if ($field === 'tesor1' && isset($data['bonos_coomeva']) && $data['bonos_coomeva'] === 'No')
                        continue;
                    if ($field === 'tesor2' && isset($data['easypedido']) && $data['easypedido'] === 'No')
                        continue;
                
                    // CAMPO MERCADEO
                    if ($field === 'mer4' && isset($data['reservas_15']) && $data['reservas_15'] === 'No')
                        continue;
                    
                    if (is_array($data[$field])){
                        if (empty($data[$field])){
                            return true;
                        }
                    } else{
                        if (strlen(trim($data[$field])) === 0){
                            return true;
                        }
                    }
                }
            }
            return false;
        }
    
        if (!$_POST || hasEmptyFields($_POST, $fields)){
            echo "<script>
                    $(document).ready(function(){
                    Swal.fire({
                    icon: 'warning',
                    title: 'Advertencia',
                    text: 'No puedes dejar campos vacíos, todos los campos son obligatorios!',
                    })
                });
                </script>";
        } else {
            $data = [];
            foreach ($fields as $field) {
                if (in_array($field, ['hora_entrada', 'hora_salida'], true)) {
                    $sup = $data['supervisores'] ?? null;
                    $noVisita = is_array($sup)
                        ? in_array('No hay visita por parte de los supervisores', $sup, true)
                        : ($sup === 'No hay visita por parte de los supervisores');
            
                    if ($noVisita){
                        continue;
                    };
                }
            
                if ($field === 'bpm') {
                    $eq = $_POST['equipo_bpm'] ?? null;
                    $sinVisita = is_array($eq)
                        ? in_array('No hay visita por parte del área', $eq, true)
                        : ($eq === 'No hay visita por parte del área');
            
                    $data['bpm'] = $sinVisita ? 'Sin novedad.' : trim($_POST['bpm'] ?? '');
                    continue;
                }
            
                if ($field === 'sst5') {
                    $eq1 = $_POST['equipo_sst'] ?? null;
                    $sinVisita = is_array($eq1)
                        ? in_array('No hay visita por parte del área', $eq1, true)
                        : ($eq1 === 'No hay visita por parte del área');
    
                    $data['sst5'] = $sinVisita ? 'Sin novedades.' : trim($_POST['sst5'] ?? '');
                    continue;
                }
    
                // GESTIÓN HUMANA
                if ($field === 'gh') {
                    $ghValue = trim($_POST['gh'] ?? '');
                    $data['gh'] = empty($ghValue) ? $DEFAULT_GH : $ghValue;
                    continue;
                }
    
                // MEJORAMIENTO
                if ($field === 'bpm1') {
                    $data['bpm1'] = (($_POST['visita_ss'] ?? '') === 'No')
                        ? $DEFAULT_BPM
                        : trim($_POST['bpm1'] ?? '');
                    continue;
                }
    
                if ($field === 'bpm2') {
                    $data['bpm2'] = (($_POST['visita_dagma'] ?? '') === 'No')
                        ? $DEFAULT_BPM
                        : trim($_POST['bpm2'] ?? '');
                    continue;
                }
    
                if ($field === 'bpm3') {
                    $data['bpm3'] = (($_POST['visita_west'] ?? '') === 'No')
                        ? $DEFAULT_BPM
                        : trim($_POST['bpm3'] ?? '');
                    continue;
                }
    
                if ($field === 'bpm8') {
                    $data['bpm8'] = (($_POST['novedad_grameras'] ?? '') === 'No')
                        ? $DEFAULT_BPM
                        : trim($_POST['bpm8'] ?? '');
                    continue;
                }
    
                // TI
                if ($field === 'ti') {
                    $data['ti'] = (($_POST['equipos_ti'] ?? '') === 'No')
                        ? $DEFAULT_TI
                        : trim($_POST['ti'] ?? '');
                    continue;
                }
    
                if ($field === 'ti1') {
                    $data['ti1'] = (($_POST['facturas_ti'] ?? '') === 'No')
                        ? $DEFAULT_TI1
                        : trim($_POST['ti1'] ?? '');
                    continue;
                }
    
                if ($field === 'ti2') {
                    $data['ti2'] = (($_POST['novedades_ti'] ?? '') === 'No')
                        ? $DEFAULT_TI2
                        : trim($_POST['ti2'] ?? '');
                    continue;
                }
    
                if ($field === 'ti3') {
                    $data['ti3'] = (($_POST['casos_ti'] ?? '') === 'No')
                        ? $DEFAULT_TI3
                        : trim($_POST['ti3'] ?? '');
                    continue;
                }
    
                // SST
                if ($field === 'sst1') {
                    $data['sst1'] = (($_POST['accidentes_sst'] ?? '') === 'No')
                        ? $DEFAULT_SST
                        : trim($_POST['sst1'] ?? '');
                    continue;
                }
    
                if ($field === 'sst2') {
                    $data['sst2'] = (($_POST['incapacidades_sst'] ?? '') === 'No')
                        ? $DEFAULT_SST
                        : trim($_POST['sst2'] ?? '');
                    continue;
                }
    
                if ($field === 'sst3') {
                    $data['sst3'] = (($_POST['ambiente_laboral'] ?? '') === 'No')
                        ? $DEFAULT_SST
                        : trim($_POST['sst3'] ?? '');
                    continue;
                }
    
                if ($field === 'sst4') {
                    $data['sst4'] = (($_POST['senal_sst'] ?? '') === 'No')
                        ? $DEFAULT_SST
                        : trim($_POST['sst4'] ?? '');
                    continue;
                }
    
                if ($field === 'sst6') {
                    $data['sst6'] = (($_POST['entrega_epp'] ?? '') === 'No')
                        ? $DEFAULT_SST
                        : trim($_POST['sst6'] ?? '');
                    continue;
                }
    
                if ($field === 'sst7') {
                    $data['sst7'] = (($_POST['novedades_sst'] ?? '') === 'No')
                        ? $DEFAULT_SST
                        : trim($_POST['sst7'] ?? '');
                    continue;
                }
    
                if ($field === 'sst8') {
                    $data['sst8'] = (($_POST['casos_sst'] ?? '') === 'No')
                        ? $DEFAULT_SST
                        : trim($_POST['sst8'] ?? '');
                    continue;
                }
    
                //MANTENIMIENTO
                if ($field === 'mant') {
                    $data['mant'] = (($_POST['equipos_cocina'] ?? '') === 'No')
                        ? $DEFAULT_MANT
                        : trim($_POST['mant'] ?? '');
                    continue;
                }
    
                if ($field === 'mant1') {
                    $data['mant1'] = (($_POST['equipos_bar'] ?? '') === 'No')
                        ? $DEFAULT_MANT
                        : trim($_POST['mant1'] ?? '');
                    continue;
                }
    
                if ($field === 'mant2') {
                    $data['mant2'] = (($_POST['equipos_salon'] ?? '') === 'No')
                        ? $DEFAULT_MANT
                        : trim($_POST['mant2'] ?? '');
                    continue;
                }
    
                if ($field === 'mant3') {
                    $data['mant3'] = (($_POST['locativos'] ?? '') === 'No')
                        ? $DEFAULT_MANT
                        : trim($_POST['mant3'] ?? '');
                    continue;
                }
    
                if ($field === 'mant4') {
                    $data['mant4'] = (($_POST['pendientes'] ?? '') === 'No')
                        ? $DEFAULT_MANT
                        : trim($_POST['mant4'] ?? '');
                    continue;
                }
                
                // PLANTA ELÉCTRICA - texto de novedades
                if ($field === 'mant8') {
                    $data['mant8'] = (($_POST['planta_elect'] ?? '') === 'No')
                        ? $DEFAULT_PE
                        : trim($_POST['mant8'] ?? '');
                    continue;
                }
    
                // AREA DE BAR
                if ($field === 'hielo4') {
                    $data['hielo4'] = (($_POST['hielo_enviado'] ?? '') === 'No')
                        ? $DEFAULT_BH_ENVIADAS
                        : trim($_POST['hielo4'] ?? '');
                    continue;
                }
    
                if ($field === 'hielo5') {
                    $data['hielo5'] = (($_POST['hielo_recibido'] ?? '') === 'No')
                        ? $DEFAULT_BH_RECIBIDAS
                        : trim($_POST['hielo5'] ?? '');
                    continue;
                }
    
                // TESORERIA
                if ($field === 'fa_mesas') {
                    $data['fa_mesas'] = (($_POST['facturas_mesas'] ?? '') === 'No')
                        ? $DEFAULT_FACTURAS
                        : trim($_POST['fa_mesas'] ?? '');
                    continue;
                }
    
                if ($field === 'fa_dom') {
                    $data['fa_dom'] = (($_POST['facturas_domic'] ?? '') === 'No')
                        ? $DEFAULT_FACTURAS
                        : trim($_POST['fa_dom'] ?? '');
                    continue;
                }
                if ($field === 'fa_rappi') {
                    $data['fa_rappi'] = (($_POST['facturas_rappi'] ?? '') === 'No')
                        ? $DEFAULT_FACTURAS
                        : trim($_POST['fa_rappi'] ?? '');
                    continue;
                }
    
                if ($field === 'tesor1') {
                    $data['tesor1'] = (($_POST['bonos_coomeva'] ?? '') === 'No')
                        ? $DEFAULT_BONOS
                        : trim($_POST['tesor1'] ?? '');
                    continue;
                }
                
                if ($field === 'tesor2') {
                    $data['tesor2'] = (($_POST['easypedido'] ?? '') === 'No')
                        ? $DEFAULT_EASYPEDIDO
                        : trim($_POST['tesor2'] ?? '');
                    continue;
                }
                
                if ($field === 'mer4') {
                    $data['mer4'] = (($_POST['reservas_15'] ?? '') === 'No')
                        ? $DEFAULT_RESERVAS
                        : trim($_POST['mer4'] ?? '');
                    continue;
                }
            
                // Default para el resto
                if (isset($_POST[$field])) {
                    $data[$field] = is_array($_POST[$field])
                        ? implode(', ', $_POST[$field])
                        : trim($_POST[$field]);
                } else {
                    $data[$field] = '';
                }
            }
            $data['fecha'] = date('d-m-Y', strtotime($_POST['fechab']));
                
            $mail = new PHPMailer(true);
            app_configure_mailer($mail);
            
            //Recipients
            /*Envia al correo del coordinador dependiendo de la sede escogida*/
            switch($data['sede']){
                case 'PANCE':
                    $mail->addAddress('adminpance@misterwings.com');
                    $mail->addAddress('pance@misterwings.com');
                    break;
                case 'CIUDAD JARDÍN':
                    $mail->addAddress('adminciudadjardin@misterwings.com');
                    $mail->addAddress('ciudadjardin@misterwings.com');
                    break;
                case 'JARDÍN PLAZA':
                    $mail->addAddress('adminjardinplaza@misterwings.com');
                    $mail->addAddress('jardinplaza@misterwings.com');
                    break;
                case 'BOCHALEMA':
                    $mail->addAddress('adminbochalema@misterwings.com');
                    $mail->addAddress('coor.bochalema@misterwings.com');
                    $mail->addAddress('bochalema@misterwings.com');
                    break;
            }
            
            $email_list = [
                'coordinador.sistemas@misterwings.com','soporte@misterwings.com',
                'subgerente@misterwings.com','coordinadora.sg-sst@misterwings.com','jefegestionhumana@misterwings.com',
                'ambiental@misterwings.com','gestionhumana@misterwings.com',
                'supervisora.cocinas@misterwings.com','operaciones.supervisor@misterwings.com','coordinador.operaciones@misterwings.com',
                'mejoramiento@misterwings.com','mercadeo@misterwings.com','auxiliar.sg-sst@misterwings.com',
                'gerencia@misterwings.com','mantenimiento@misterwings.com', 'coord.inventarios@misterwings.com','visual@misterwings.com',
                'tesoreria@misterwings.com', 'comercial@misterwings.com','contabilidad@misterwings.com',
                'supervisor.cocinas2@misterwings.com','capacitacionmw@misterwings.com',
                'auxiliar1.sg-sst@misterwings.com', 'auxiliar.sistemas@misterwings.com','comercial.gerencia@misterwings.com',
                'aux.tesoreria@misterwings.com','aux.contable2@misterwings.com','colquingroup@hotmail.com',
                'aux.gestionhumana@misterwings.com', 'coordinador.procesos@misterwings.com','apr.mejoramiento@misterwings.com',
                'director.administrativosedes@misterwings.com','asistente.operativo@misterwings.com','grafica@misterwings.com'
            ];
            
            foreach($email_list as $email){
                $mail->addAddress($email);
            }
            
            //Realizar pruebas de envio. Comentar después de validar.
            //$mail->addBCC('coordinador.sistemas@misterwings.com');
    
            //Content
            $mail->isHTML(true);                                  //Set email format to HTML
            $mail->CharSet = 'UTF-8';
    
            $mail->Subject = 'BITÁCORA SEDE '.$data['sede'];
            
            function e($s)
            {
                return nl2br(htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'));
            }
            
            $chetanoSection = '';
            if (isset($data['sede']) && strtoupper($data['sede']) === 'PANCE') {
                $chetanoSection = 
                    '<div class="area-section">
                        <div class="area-title">CHETANO PANCE</div>
                        <div class="stats-section">
                            <div class="stat-box"><strong>Novedades Chetano: </strong>' . e($data['nov_chetano'] ?? '') . '</div>
                            <div class="stat-box"><strong>Venta de Productos: </strong>' . e($data['ventas_chetano'] ?? '') . '</div>
                            <div class="stat-box"><strong>Venta Domicilios: </strong>' . e($data['dom_chetano'] ?? '') . '</div>
                            <div class="stat-box"><strong>Materias Primas: </strong>' . e($data['mp_chetano'] ?? '') . '</div>
                        </div>
                    </div>';
            }
            
            // --- MEJORAMIENTO
            // Secretaría de Salud
            $ss_estado = $_POST['visita_ss'] ?? ($data['visita_ss'] ?? '—');
            $ss_texto = trim($_POST['bpm1'] ?? ($data['bpm1'] ?? ''));
            $ss_detalle = ($ss_estado === 'Si' && $ss_texto !== '') ? $ss_texto : $DEFAULT_BPM;
    
            // DAGMA
            $dg_estado = $_POST['visita_dagma'] ?? ($data['visita_dagma'] ?? '—');
            $dg_texto = trim($_POST['bpm2'] ?? ($data['bpm2'] ?? ''));
            $dg_detalle = ($dg_estado === 'Si' && $dg_texto !== '') ? $dg_texto : $DEFAULT_BPM;
    
            // West - Klaxen
            $wk_estado = $_POST['visita_west'] ?? ($data['visita_west'] ?? '—');
            $wk_texto = trim($_POST['bpm3'] ?? ($data['bpm3'] ?? ''));
            $wk_detalle = ($wk_estado === 'Si' && $wk_texto !== '') ? $wk_texto : $DEFAULT_BPM;
    
            // Grameras / termómetros
            $gr_estado = $_POST['novedad_grameras'] ?? ($data['novedad_grameras'] ?? '—');
            $gr_texto = trim($_POST['bpm8'] ?? ($data['bpm8'] ?? ''));
            $gr_detalle = ($gr_estado === 'Si' && $gr_texto !== '') ? $gr_texto : $DEFAULT_BPM;
    
            // --- BOLSAS DE HIELO
            // --- ÁREA DE BAR: Hielo (numérico)
            function sanitize_number($val, $is_int = false)
            {
                if ($val === null)
                    return $is_int ? 0 : 0.0;
                $s = trim((string) $val);
                if ($s === '')
                    return $is_int ? 0 : 0.0;
                $s = str_replace(['$', '%', ' '], '', $s);
                if (preg_match('/^\d{1,3}(\.\d{3})+,\d+$/', $s)) {
                    $s = str_replace('.', '', $s);
                    $s = str_replace(',', '.', $s);
                } else {
                    if (substr_count($s, ',') > 0 && substr_count($s, '.') === 0) {
                        $s = str_replace(',', '.', $s);
                    } else {
                        $s = str_replace(',', '', $s);
                    }
                }
                $num = (float) $s;
                return $is_int ? (int) round($num) : $num;
            }
    
            $DEFAULT_BOLSAS_HIELO = $DEFAULT_BOLSAS_HIELO ?? 0;
    
            // Radios
            $hi_estado = $_POST['hielo_produ'] ?? ($data['hielo_produ'] ?? '—'); // producción del día (hielo)
            $hk_estado = $_POST['hielo_kolbitos'] ?? ($data['hielo_kolbitos'] ?? '—'); // compra hielo kolbitos (hielo1)
            $hc_estado = $_POST['hielo_consumo'] ?? ($data['hielo_consumo'] ?? '—'); // consumo del día (hielo2)
    
            // Valores crudos (enteros). Si ya normalizas $_POST con sanitize_number, puedes castear directo.
            $hi0 = isset($_POST['hielo']) ? sanitize_number($_POST['hielo'], true) : (isset($data['hielo']) ? (int) $data['hielo'] : null);
            $hi1 = isset($_POST['hielo1']) ? sanitize_number($_POST['hielo1'], true) : (isset($data['hielo1']) ? (int) $data['hielo1'] : null);
            $hi2 = isset($_POST['hielo2']) ? sanitize_number($_POST['hielo2'], true) : (isset($data['hielo2']) ? (int) $data['hielo2'] : null);
            $hi3 = isset($_POST['hielo3']) ? sanitize_number($_POST['hielo3'], true) : (isset($data['hielo3']) ? (int) $data['hielo3'] : null);
    
            // Defaults por estado de radio
            // Producción del día (hielo) depende de 'hielo_produ'
            if ($hi_estado === 'No') {
                $hi0 = $hi0 ?? $DEFAULT_BOLSAS_HIELO;
            } elseif ($hi_estado === 'Si') {
                $hi0 = $hi0 ?? $DEFAULT_BOLSAS_HIELO; // cambia por null si quieres exigir dato
            } else {
                $hi0 = $hi0 ?? $DEFAULT_BOLSAS_HIELO;
            }
    
            // Compra Kolbitos (hielo1) depende de 'hielo_kolbitos'
            if ($hk_estado === 'No') {
                $hi1 = $hi1 ?? 0;
            } elseif ($hk_estado === 'Si') {
                $hi1 = $hi1 ?? 0; // cambia por null si quieres exigir dato
            } else {
                $hi1 = $hi1 ?? 0;
            }
    
            // Consumo del día (hielo2) depende de 'hielo_consumo'
            if ($hc_estado === 'No') {
                $hi2 = $hi2 ?? 0;
            } elseif ($hc_estado === 'Si') {
                $hi2 = $hi2 ?? 0; // cambia por null si quieres exigir dato
            } else {
                $hi2 = $hi2 ?? 0;
            }
    
            // Inventario final (hielo3) — SIN radio, toma 0 si vacío
            $hi3 = $hi3 ?? 0;
    
            // Formateo para mostrar
            $hi0_fmt = number_format((float) $hi0, 0, ',', '.');
            $hi1_fmt = number_format((float) $hi1, 0, ',', '.');
            $hi2_fmt = number_format((float) $hi2, 0, ',', '.');
            $hi3_fmt = number_format((float) $hi3, 0, ',', '.');
            
            //Condicional para planta eléctrica
            $pe_estado = $_POST['planta_elect'] ?? ($data['planta_elect'] ?? '—');
            $pe_texto = trim($_POST['mant8'] ?? ($data['mant8'] ?? ''));
            $pe_detalle = ($pe_estado === 'Si' && $pe_texto !== '') ? $pe_texto : $DEFAULT_PE;
            
            // Condicional para horas de ingreso/salida de entrenadora/supervisora/coordinador
            // Nota: si llega "00:00" se considera vacío (para evitar que se muestre como default)
            $hora_entrada = trim($_POST['hora_entrada'] ?? ($data['hora_entrada'] ?? ''));
            $hora_salida  = trim($_POST['hora_salida'] ?? ($data['hora_salida'] ?? ''));
            
            if ($hora_entrada === '00:00') $hora_entrada = '';
            if ($hora_salida  === '00:00') $hora_salida  = '';
            
            $horas_sup_html = '';
            if ($hora_entrada !== '') {
                $horas_sup_html .= '<div class="sub-item"><strong>Hora de ingreso de la entrenadora/supervisora/coordinador: </strong>' . e($hora_entrada) . '</div>';
            }
            if ($hora_salida !== '') {
                $horas_sup_html .= '<div class="sub-item"><strong>Hora de salida de la entrenadora/supervisora/coordinador: </strong>' . e($hora_salida) . '</div>';
            }
            
            $mail->Body =
            '<!DOCTYPE html>
                <html>
                <head>
                <style>
                    body {
                        font-family: Arial, Helvetica, sans-serif;
                        max-width: 1200px;
                        margin: 0 auto;
                        padding: 20px;
                        background-color: #f5f5f5;
                    }
                    h2 {
                        color: #2c3e50;
                        text-align: center;
                        padding: 10px;
                        margin: 20px 0;
                        border-bottom: 2px solid #3498db;
                    }
                    .header-info {
                        background-color: #fff;
                        padding: 20px;
                        border-radius: 8px;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                        margin-bottom: 20px;
                    }
                    .area-section {
                        background-color: #fff;
                        padding: 20px;
                        margin: 15px 0;
                        border-radius: 8px;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    }
                    .area-title {
                        color: #2c3e50;
                        font-weight: bold;
                        margin-bottom: 10px;
                        padding-bottom: 5px;
                        border-bottom: 1px solid #e0e0e0;
                    }
                    .sub-item {
                        margin: 10px 0;
                        padding-left: 20px;
                    }
                    .stats-section {
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                        gap: 15px;
                        margin: 15px 0;
                    }
                    .stat-box {
                        background-color: #f8f9fa;
                        padding: 15px;
                        border-radius: 6px;
                        border-left: 4px solid #3498db;
                    }
                    strong { color: #2c3e50; }
                    li { list-style-type: none; margin-bottom: 10px; }
                    .highlight {
                        background-color: #e8f4fc;
                        padding: 10px;
                        border-radius: 4px;
                        margin: 5px 0;
                    }
                    .inventory-subsection {
                        margin: 15px 0;
                        padding: 15px;
                        background-color: #f8f9fa;
                        border-radius: 6px;
                        border-left: 4px solid #2ecc71;
                    }
                    .inventory-title {
                        font-weight: bold;
                        color: #2c3e50;
                        margin-bottom: 10px;
                        font-size: 1.1em;
                    }
                    .inventory-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                        gap: 10px;
                        margin: 10px 0;
                    }
                    .product-inventory {
                        display: grid;
                        grid-template-columns: repeat(2, 1fr);
                        gap: 10px;
                        margin: 10px 0;
                    }
                    .product-item {
                        background-color: #fff;
                        padding: 8px;
                        border-radius: 4px;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                    }
                </style>
                </head>
                <body>' .
        
            // Encabezado
            '<div class="header-info">
                        <h2>Bitácora diaria ' . e($data['sede'] ?? '') . '</h2>
                        <p><strong>Fecha de bitácora: </strong>' . e($data['fecha'] ?? '') . '</p>
                        <p><strong>Sede: </strong>' . e($data['sede'] ?? '') . '</p>
                        <p><strong>Responsable: </strong>' . e($data['responsable'] ?? '') . '</p>
                        <p><strong>Cargo: </strong>' . e($data['cargo'] ?? '') . '</p>
                    </div>' .
        
            // OPERACIONES
            '<div class="area-section">
                        <div class="area-title">ÁREA DE OPERACIONES</div>
                        <div class="sub-item"><strong>Servicio al Cliente: </strong>' . e($data['sac'] ?? '') . '</div>
                        <div class="sub-item"><strong>Visita de Entrenadores/Supervisores: </strong>' . e($data['supervisores'] ?? '') . '</div>
                        <div class="sub-item"><strong>Actividades Realizadas: </strong>' . e($data['act_sup'] ?? '') . '</div>'.
                        $horas_sup_html .
                        '<div class="sub-item"><strong>Devoluciones de Producto (Retorno a Cocina): </strong>' . e($data['devo'] ?? '') . '</div>
                    </div>' .
        
            // AFLUENCIA
            '<div class="area-section">
                        <div class="area-title">AFLUENCIA DE COMENSALES</div>
                        <div class="stats-section">
                            <div class="stat-box"><strong>Medio día: </strong>' . e($data['comens'] ?? '') . '</div>
                            <div class="stat-box"><strong>Tarde: </strong>' . e($data['comens1'] ?? '') . '</div>
                            <div class="stat-box"><strong>Noche: </strong>' . e($data['comens2'] ?? '') . '</div>
                        </div>
                    </div>' .
        
            // JEFES
            '<div class="area-section">
                        <div class="area-title">OBSERVACIONES DE JEFES</div>
                        <div class="sub-item"><strong>Jefe de mesas: </strong>' . e($data['mesas'] ?? '') . '</div>
                        <div class="sub-item"><strong>Jefe de bar: </strong>' . e($data['bar'] ?? '') . '</div>
                        <div class="sub-item"><strong>Jefe de cocina: </strong>' . e($data['cocina'] ?? '') . '</div>
                    </div>' .
        
            // MERCADEO
            '<div class="area-section">
                        <div class="area-title">ÁREA DE MERCADEO</div>
                        <div class="sub-item"><strong>Venta de Coctelería: </strong>' . e($data['coc'] ?? '') . '</div>
                        <div class="sub-item"><strong>Venta de Productos Foco: </strong>' . e($data['mer'] ?? '') . '</div>
                        <div class="sub-item"><strong>Ventas de Productos Nuevos: </strong>' . e($data['mer1'] ?? '') . '</div>
                        <div class="sub-item"><strong>Campañas del Mes: </strong>' . e($data['mer2'] ?? '') . '</div>
                        <div class="sub-item"><strong>Casos HelpDesk: </strong>' . e($data['mer3'] ?? '') . '</div>
                        <div class="sub-item"><strong>Reservas: </strong>' . e($data['mer4'] ?? '') . '</div>
                    </div>' .
        
            // GESTIÓN HUMANA
            '<div class="area-section">
                        <div class="area-title">ÁREAS DE GESTIÓN HUMANA</div>
                        <div class="sub-item"><strong>Novedades: </strong>' . e($data['gh'] ?? '') . '</div>
                    </div>' .
        
            // SST
            '<div class="area-section">
                <div class="area-title">ÁREAS DE SEGURIDAD Y SALUD</div>
                <div class="sub-item"><strong>Eventos por incidentes laborales, accidentes laborales y de transito: </strong>' . e($data['sst1'] ?? '') . '</div>
                <div class="sub-item"><strong>Incapacidades iguales o mayores a 15 días: </strong>' . e($data['sst2'] ?? '') . '</div>
                <div class="sub-item"><strong>Hallazgos por ambiente laboral: </strong>' . e($data['sst3'] ?? '') . '</div>
                <div class="sub-item"><strong>Reportes de extintores y señalización: </strong>' . e($data['sst4'] ?? '') . '</div>
                <div class="sub-item"><strong>Entrega de EPP: </strong>' . e($data['sst6'] ?? '') . '</div>
                <div class="sub-item"><strong>Visita a la sede del equipo: </strong>' . e($data['equipo_sst'] ?? '') . '</div>
                <div class="sub-item"><strong>Actividades de Acompañamiento: </strong>' . e($data['sst5'] ?? '') . '</div>
                <div class="sub-item"><strong>Otras Novedades (Situaciones de Salud, Condiciones y actos inseguros, etc): </strong>' . e($data['sst7'] ?? '') . '</div>
                <div class="sub-item"><strong>Casos HelpDesk: </strong>' . e($data['sst8'] ?? '') . '</div>
            </div>' .
        
            // SISTEMAS
            '<div class="area-section">
                        <div class="area-title">ÁREA DE SISTEMAS - TI</div>
                        <div class="sub-item"><strong>Equipos de Cómputo: </strong>' . e($data['ti'] ?? '') . '</div>
                        <div class="sub-item"><strong>Facturas FE: </strong>' . e($data['ti1'] ?? '') . '</div>
                        <div class="sub-item"><strong>Otros: </strong>' . e($data['ti2'] ?? '') . '</div>
                        <div class="sub-item"><strong>Casos HelpDesk: </strong>' . e($data['ti3'] ?? '') . '</div>
                    </div>' .
        
            // MANTENIMIENTO
            '<div class="area-section">
                        <div class="area-title">ÁREA DE MANTENIMIENTO E INFRAESTRUCTURA</div>
                        <div class="sub-item"><strong>Equipos Cocina: </strong>' . e($data['mant'] ?? '') . '</div>
                        <div class="sub-item"><strong>Equipos Bar: </strong>' . e($data['mant1'] ?? '') . '</div>
                        <div class="sub-item"><strong>Equipos Salón: </strong>' . e($data['mant2'] ?? '') . '</div>
                        <div class="sub-item"><strong>Equipos Locativos: </strong>' . e($data['mant3'] ?? '') . '</div>
                        <div class="sub-item"><strong>Uso de planta eléctrica: </strong>' . e($data['planta_elect'] ?? '') . '</div>'
                        .($pe_estado === 'Si'
                            ? '<div class="sub-item"><strong>Hora de encendido: </strong>' . e($data['mant5'] ?? '') . '</div>
                            <div class="sub-item"><strong>Hora de apagado: </strong>' . e($data['mant6'] ?? '') . '</div>
                            <div class="sub-item"><strong>Tiempo de uso (minutos): </strong>' . e($data['mant7'] ?? '') . '</div>
                            <div class="sub-item"><strong>Novedades de Planta Eléctrica: </strong>' . e($data['mant8'] ?? '') . '</div>'
                            : '<div class="sub-item"><strong>Novedades de Planta Eléctrica: </strong>' . e($pe_detalle) . '</div>'
                        ).'<div class="sub-item"><strong>Pendientes: </strong>' . e($data['mant4'] ?? '') . '</div>
                    </div>' .
        
            // MEJORAMIENTO Y ESTANDARIZACIÓN
            '<div class="area-section">
                        <div class="area-title">ÁREA DE MEJORAMIENTO Y ESTANDARIZACIÓN</div>
                        <div class="sub-item"><strong>Visita a la sede del equipo: </strong>' . e($data['equipo_bpm'] ?? '') . '</div>
                        <div class="sub-item"><strong>Actividades durante la visita: </strong>' . e($data['bpm'] ?? '') . '</div>
                        <div class="sub-item"><strong>¿Hubo visita de la Secretaría de Salud?: </strong>' . e($ss_estado) . '</div>'
            . ($ss_estado === 'Si'
                ? '<div class="highlight"><strong>Detalle de la visita:</strong> ' . e($ss_detalle) . '</div>'
                : '<div class="highlight">' . e($ss_detalle) . '</div>'
            )
            . '<div class="sub-item"><strong>¿Hubo visita del DAGMA?: </strong>' . e($dg_estado) . '</div>'
            . ($dg_estado === 'Si'
                ? '<div class="highlight"><strong>Detalle de la visita:</strong> ' . e($dg_detalle) . '</div>'
                : '<div class="highlight">' . e($dg_detalle) . '</div>'
            )
            . '<div class="sub-item"><strong>¿Hubo visita del proveedor West - Klaxen?: </strong>' . e($wk_estado) . '</div>'
            . ($wk_estado === 'Si'
                ? '<div class="highlight"><strong>Detalle de la visita:</strong> ' . e($wk_detalle) . '</div>'
                : '<div class="highlight">' . e($wk_detalle) . '</div>'
            )
            .
            '<div class="sub-item"><strong>Entrega de ACU: </strong>' . e($data['bpm4'] ?? '') . '</div>
                        <div class="sub-item"><strong>Entrega de residuos aprovechables: </strong>' . e($data['bpm5'] ?? '') . '</div>
                        <div class="sub-item"><strong>Entrega de residuos orgánicos: </strong>' . e($data['bpm6'] ?? '') . '</div>
                        <div class="sub-item"><strong>Control de plagas: </strong>' . e($data['bpm7'] ?? '') . '</div>
                        <div class="sub-item"><strong>¿Novedades con grameras y/o termómetros?: </strong>' . e($gr_estado) . '</div>'
            . ($gr_estado === 'Si'
                ? '<div class="highlight"><strong>Detalle:</strong> ' . e($gr_detalle) . '</div>'
                : '<div class="highlight">' . e($gr_detalle) . '</div>'
            )
            . '</div>' .
        
            // BAR
            '<div class="area-section">
                <div class="area-title">ÁREA DE BAR</div>
                <div class="sub-item"><strong>Producción de Bolsas en el día: </strong>' . (
                $hi_estado === 'Si' ? e($hi0_fmt) : e($hi0_fmt)
            )
            . ' bolsas</div>
                <div class="sub-item"><strong>Compra de Hielo Kolbitos: </strong>' . (
                $hk_estado === 'Si' ? e($hi1_fmt) : e($hi1_fmt)
            ) . ' bolsas</div>
                <div class="sub-item"><strong>Consumo Bolsas del día: </strong>' . (
                $hc_estado === 'Si' ? e($hi2_fmt) : e($hi2_fmt)
            ) . ' bolsas</div>
                <div class="sub-item"><strong>Inventario Final de Bolsas de hielo día: </strong>' . e($hi3_fmt) . ' bolsas</div>
                <div class="sub-item"><strong>Hielo trasladado a otra sede: </strong>' . e($data['hielo4'] ?? '') . '</div>
                <div class="sub-item"><strong>Hielo recibido otra sede: </strong>' . e($data['hielo5'] ?? '') . '</div>
            </div>' .
        
            // DESPENSA
            '<div class="area-section">
                        <div class="area-title">ÁREA DE DESPENSA</div>
                        <div class="sub-item"><strong>Novedades: </strong>' . e($data['desp'] ?? '') . '</div>
                    </div>' .
        
            // NOVEDADES DOMICILIOS
            '<div class="area-section">
                        <div class="area-title">NOVEDADES DOMICILIOS</div>
                        <div class="stats-section">
                            <div class="stat-box"><strong>RAPPI: </strong>' . e($data['dorp'] ?? '') . '</div>
                            <div class="stat-box"><strong>Domicilios Propios: </strong>' . e($data['dorp1'] ?? '') . '</div>
                        </div>
                    </div>' .
        
            // TESORERÍA
            '<div class="area-section">
                        <div class="area-title">ÁREA DE TESORERÍA</div>
                        <div class="sub-item"><strong>Novedades: </strong>' . e($data['tesor'] ?? '') . '</div>
                        <div class="sub-item"><strong>Bonos Coomeva: </strong>' . e($data['tesor1'] ?? '') . '</div>
                        <div class="sub-item"><strong>Pagos Easypedido: </strong>' . e($data['tesor2'] ?? '') . '</div>
                    </div>' .
        
            // FACTURAS ANULADAS
            '<div class="area-section">
                        <div class="area-title">FACTURAS ANULADAS</div>
                        <div class="stats-section">
                            <div class="stat-box"><strong>Mesas: </strong>' . e($data['fa_mesas'] ?? '') . '</div>
                            <div class="stat-box"><strong>Domicilios Propios: </strong>' . e($data['fa_dom'] ?? '') . '</div>
                            <div class="stat-box"><strong>RAPPI: </strong>' . e($data['fa_rappi'] ?? '') . '</div>
                        </div>
                    </div>' .
        
            // MÉTRICAS
            '<div class="area-section">
                        <div class="area-title">MÉTRICAS DE SERVICIO</div>
                        <div class="stats-section">
                            <div class="stat-box"><strong>Nº ordenes Rappi: </strong>' . e($data['rappi'] ?? '') . '</div>
                            <div class="stat-box"><strong>Nº domicilios: </strong>' . e($data['domi'] ?? '') . '</div>
                            <div class="stat-box"><strong>Nº domiciliarios de DomiExpress: </strong>' . e($data['domiexpress'] ?? '') . '</div>
                            <div class="stat-box"><strong>Nº horas trabajadas DomiExpress: </strong>' . e($data['hdomi'] ?? '') . '</div>
                        </div>
                    </div>' .
        
            // INDICADORES
            '<div class="area-section">
                        <div class="area-title">INDICADORES DE DESEMPEÑO</div>
                        <div class="stats-section">
                            <div class="stat-box"><strong>Cumplimiento Presupuesto Diario: </strong>' . e($data['pd'] ?? '') . '%</div>
                            <div class="stat-box"><strong>Nº Ticket Promedio: </strong>$' . e($data['tp'] ?? '') . '</div>
                        </div>
                    </div>'.
            // CHETANO PANCE
            $chetanoSection.
                '</body>
                </html>';
            if (!$mail->send()) {
                echo "<script>
                        $(document).ready(function(){
                            Swal.fire({
                            icon: 'error',
                            title: 'La bitácora no fue enviada :(',
                            text: 'Hubo un error al enviar la bitácora por favor intentalo nuevamente.',
                        })
                    });
                    </script>";
            } else {
                echo "<script>
                        $(document).ready(function(){
                            Swal.fire({
                            icon: 'success',
                            title: 'Bitácora enviada',
                            text: '!Se ha enviado la bitácora con éxito!',
                            timerProgressBar: true,
                            allowOutsideClick: false,
                        })
                    });
                    </script>";
            }
        }
    }
?>
