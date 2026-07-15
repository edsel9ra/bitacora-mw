<?php

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/bitacora.php';

function bit_e($s): string
{
    return nl2br(htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'));
}

function bit_h($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function bit_upper_clean(string $value): string
{
    return trim(mb_strtoupper($value, 'UTF-8'));
}

function bit_safe_filename(string $value): string
{
    $value = trim($value);
    $value = preg_replace('/[^\p{L}\p{N}\-_\. ]/u', '_', $value);
    $value = preg_replace('/\s+/', '_', $value);
    return trim($value, '_');
}

function bit_ensure_dir(string $dir): void
{
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('No fue posible crear el directorio de almacenamiento.');
    }

    if (!is_writable($dir)) {
        throw new RuntimeException('El directorio de almacenamiento no tiene permisos de escritura.');
    }
}

function bit_storage_base_dir(): string
{
    $baseDir = app_env('BITACORA_STORAGE_PATH', __DIR__ . '/../../storage/bitacoras_pdf');
    return rtrim((string) $baseDir, '/\\');
}

function bit_pdf_logo_src(): string
{
    $logos = [
        __DIR__ . '/../logo.png' => 'image/png',
        __DIR__ . '/../logo.jpg' => 'image/jpeg',
        __DIR__ . '/../resources/img/logo_app.png' => 'image/png',
        __DIR__ . '/../resources/img/LOGO ALITAS-09.png' => 'image/png',
        __DIR__ . '/../resources/img/ALITAS.png' => 'image/png',
    ];

    foreach ($logos as $path => $mime) {
        if (!is_file($path) || !is_readable($path)) {
            continue;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            continue;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }

    return '';
}

function bit_normalize_array_value($value): string
{
    if (is_array($value)) {
        $value = array_map(static function ($v) {
            return trim((string)$v);
        }, $value);

        $value = array_filter($value, static function ($v) {
            return $v !== '';
        });

        return implode(', ', $value);
    }

    return trim((string)$value);
}

function bit_get_default_texts(): array
{
    return [
        'DEFAULT_BPM'          => 'Sin novedad.',
        'DEFAULT_TI'           => 'Sin novedades con los equipos.',
        'DEFAULT_TI1'          => 'Las facturas electrónicas se integran con código CUFE.',
        'DEFAULT_TI2'          => 'Sin novedades.',
        'DEFAULT_TI3'          => 'No se reportan casos o solicitudes ni pendientes.',
        'DEFAULT_GH'           => 'Sin novedad en Gestión Humana.',
        'DEFAULT_SST'          => 'Sin novedades.',
        'DEFAULT_MANT'         => 'No se presentan novedades.',
        'DEFAULT_PE'           => 'No se presentaron novedades relacionadas a la planta eléctrica.',
        'DEFAULT_BH_ENVIADAS'  => 'No se enviaron bolsas a otras sedes.',
        'DEFAULT_BH_RECIBIDAS' => 'No se recibieron bolsas de otras sedes.',
        'DEFAULT_FACTURAS'     => 'No se anularon facturas.',
        'DEFAULT_BONOS'        => 'No se canjearon bonos Coomeva.',
        'DEFAULT_RESERVAS'     => 'No se realizaron reservas.',
        'DEFAULT_EASYPEDIDO'   => 'No se realizaron pedidos por EasyPedido.',
    ];
}

/**
 * Mantiene la lógica existente de backend para planta eléctrica:
 * calcula minutos entre HH:MM y soporta cruce de medianoche.
 */
function bit_calcular_minutos_planta(string $inicio = '', string $fin = ''): int
{
    if ($inicio === '' || $fin === '') {
        return 0;
    }

    if (!preg_match('/^\d{2}:\d{2}$/', $inicio) || !preg_match('/^\d{2}:\d{2}$/', $fin)) {
        return 0;
    }

    [$h1, $m1] = array_map('intval', explode(':', $inicio));
    [$h2, $m2] = array_map('intval', explode(':', $fin));

    $min1 = ($h1 * 60) + $m1;
    $min2 = ($h2 * 60) + $m2;

    $diff = $min2 - $min1;

    if ($diff < 0) {
        $diff += 24 * 60;
    }

    return $diff;
}

function bit_handle_planta_electrica(array &$post, array $defaults): void
{
    $planta = trim((string)($post['planta_elect'] ?? ''));

    if ($planta === 'No') {
        if (!isset($post['mant5']) || trim((string)$post['mant5']) === '') {
            $post['mant5'] = '00:00';
        }
        if (!isset($post['mant6']) || trim((string)$post['mant6']) === '') {
            $post['mant6'] = '00:00';
        }
        if (!isset($post['mant7']) || trim((string)$post['mant7']) === '') {
            $post['mant7'] = 0;
        }
        if (!isset($post['mant8']) || trim((string)$post['mant8']) === '') {
            $post['mant8'] = $defaults['DEFAULT_PE'];
        }
        return;
    }

    if ($planta === 'Si') {
        $ini  = trim((string)($post['mant5'] ?? ''));
        $fin  = trim((string)($post['mant6'] ?? ''));

        $post['mant7'] = ($ini !== '' && $fin !== '') ? bit_calcular_minutos_planta($ini, $fin) : 0;

        if (isset($post['mant8']) && trim((string)$post['mant8']) === $defaults['DEFAULT_PE']) {
            $post['mant8'] = '';
        }
    }
}

function bit_get_config(int $empresaId, string $sede): array
{
    $sede = bit_upper_clean($sede);
    $companyConfig = app_bitacora_config($empresaId) ?? [];
    $sections = app_bitacora_form_sections($empresaId, $companyConfig);
    $config = [
        'fields' => app_bitacora_collect_field_names($sections, $sede),
        'sections' => [
            'chetano' => false,
            'torito' => false,
            'reunion_calidad' => false,
        ],
        'dynamic_fields' => app_bitacora_dynamic_render_fields($sections, $sede),
    ];

    if (app_bitacora_extra_enabled($companyConfig, 'chetano', $sede)) {
        $config['sections']['chetano'] = true;
    }

    if (app_bitacora_extra_enabled($companyConfig, 'torito', $sede)) {
        $config['sections']['torito'] = true;
    }

    if (app_bitacora_extra_enabled($companyConfig, 'reunion_calidad', $sede)) {
        $config['sections']['reunion_calidad'] = true;
    }

    return $config;
}

function bit_get_conditional_rules(array $defaults): array
{
    return [
        ['radio' => 'visita_ss',         'field' => 'bpm1',    'default' => $defaults['DEFAULT_BPM']],
        ['radio' => 'visita_dagma',      'field' => 'bpm2',    'default' => $defaults['DEFAULT_BPM']],
        ['radio' => 'visita_west',       'field' => 'bpm3',    'default' => $defaults['DEFAULT_BPM']],
        ['radio' => 'novedad_grameras',  'field' => 'bpm8',    'default' => $defaults['DEFAULT_BPM']],

        ['radio' => 'equipos_ti',        'field' => 'ti',      'default' => $defaults['DEFAULT_TI']],
        ['radio' => 'facturas_ti',       'field' => 'ti1',     'default' => $defaults['DEFAULT_TI1']],
        ['radio' => 'novedades_ti',      'field' => 'ti2',     'default' => $defaults['DEFAULT_TI2']],
        ['radio' => 'casos_ti',          'field' => 'ti3',     'default' => $defaults['DEFAULT_TI3']],

        ['radio' => 'accidentes_sst',    'field' => 'sst1',    'default' => $defaults['DEFAULT_SST']],
        ['radio' => 'incapacidades_sst', 'field' => 'sst2',    'default' => $defaults['DEFAULT_SST']],
        ['radio' => 'ambiente_laboral',  'field' => 'sst3',    'default' => $defaults['DEFAULT_SST']],
        ['radio' => 'senal_sst',         'field' => 'sst4',    'default' => $defaults['DEFAULT_SST']],
        ['radio' => 'entrega_epp',       'field' => 'sst6',    'default' => $defaults['DEFAULT_SST']],
        ['radio' => 'novedades_sst',     'field' => 'sst7',    'default' => $defaults['DEFAULT_SST']],
        ['radio' => 'casos_sst',         'field' => 'sst8',    'default' => $defaults['DEFAULT_SST']],

        ['radio' => 'equipos_cocina',    'field' => 'mant',    'default' => $defaults['DEFAULT_MANT']],
        ['radio' => 'equipos_bar',       'field' => 'mant1',   'default' => $defaults['DEFAULT_MANT']],
        ['radio' => 'equipos_salon',     'field' => 'mant2',   'default' => $defaults['DEFAULT_MANT']],
        ['radio' => 'locativos',         'field' => 'mant3',   'default' => $defaults['DEFAULT_MANT']],
        ['radio' => 'pendientes',        'field' => 'mant4',   'default' => $defaults['DEFAULT_MANT']],

        ['radio' => 'hielo_enviado',     'field' => 'hielo4',  'default' => $defaults['DEFAULT_BH_ENVIADAS']],
        ['radio' => 'hielo_recibido',    'field' => 'hielo5',  'default' => $defaults['DEFAULT_BH_RECIBIDAS']],

        ['radio' => 'facturas_mesas',    'field' => 'fa_mesas', 'default' => $defaults['DEFAULT_FACTURAS']],
        ['radio' => 'facturas_domic',    'field' => 'fa_dom',   'default' => $defaults['DEFAULT_FACTURAS']],
        ['radio' => 'facturas_rappi',    'field' => 'fa_rappi', 'default' => $defaults['DEFAULT_FACTURAS']],

        ['radio' => 'bonos_coomeva',     'field' => 'tesor1',  'default' => $defaults['DEFAULT_BONOS']],
        ['radio' => 'reservas_15',       'field' => 'mer4',    'default' => $defaults['DEFAULT_RESERVAS']],
        ['radio' => 'easypedido',        'field' => 'tesor2',  'default' => $defaults['DEFAULT_EASYPEDIDO']],
    ];
}

function bit_apply_conditional_defaults(array &$post, array $rules): void
{
    foreach ($rules as $rule) {
        $radio = trim((string)($post[$rule['radio']] ?? ''));
        $value = trim((string)($post[$rule['field']] ?? ''));

        if ($radio === 'No' && $value === '') {
            $post[$rule['field']] = $rule['default'];
        }

        if ($radio === 'Si' && $value === $rule['default']) {
            $post[$rule['field']] = '';
        }
    }
}

function bit_normalize_data(array $post, array $config): array
{
    $data = [];

    foreach ($config['fields'] as $field) {
        $data[$field] = isset($post[$field]) ? bit_normalize_array_value($post[$field]) : '';
    }

    $data['sede']        = bit_upper_clean($data['sede'] ?? '');
    $data['responsable'] = bit_upper_clean($data['responsable'] ?? '');
    $data['cargo']       = trim((string)($data['cargo'] ?? ''));

    $data['fecha'] = '';
    if (!empty($post['fechab'])) {
        $timestamp = strtotime((string)$post['fechab']);
        if ($timestamp !== false) {
            $data['fecha'] = date('d-m-Y', $timestamp);
        }
    }

    return $data;
}

function bit_render_detail(string $title, string $value, bool $mostrarSiVacio = false): string
{
    $value = trim((string)$value);

    if (!$mostrarSiVacio && $value === '') {
        return '';
    }
    return '<div class="sub-item"><strong>' . bit_h($title) . ':</strong> ' . bit_e($value) . '</div>';
}

function bit_render_detail_if(bool $condition, string $title, string $value, bool $mostrarSiVacio = false): string
{
    if (!$condition) {
        return '';
    }

    return bit_render_detail($title, $value, $mostrarSiVacio);
}

function bit_render_section(string $title, array $rows): string
{
    $rows = array_filter($rows, function ($row) {
        return trim((string)$row) !== '';
    });

    if (empty($rows)) {
        return '';
    }
    
    $html = '<div class="area-section">';
    $html .= '<div class="area-title">' . bit_h($title) . '</div>';
    foreach ($rows as $row) {
        $html .= $row;
    }
    $html .= '</div>';
    return $html;
}

function bit_render_html(array $data, array $config, bool $includeLogo = false): string
{
    $tieneSupervision = !empty(trim($data['supervisores'] ?? ''));
    $usoPlanta = trim((string) ($data['planta_elect'] ?? '')) === 'Si';
    $tieneTeamCalidad = !empty(trim($data['equipo_bpm']));
    $logoSrc = $includeLogo ? bit_pdf_logo_src() : '';
    $logoHtml = $logoSrc !== '' ? '<td class="logo-cell"><img class="logo" src="' . bit_h($logoSrc) . '" alt="Logo"></td>' : '';
    
    $chetanoSection = '';
    if (!empty($config['sections']['chetano'])) {
        $chetanoSection = bit_render_section('CHETANO', [
            bit_render_detail('Novedades Chetano', $data['nov_chetano'] ?? ''),
            bit_render_detail('Venta de Productos', $data['ventas_chetano'] ?? ''),
            bit_render_detail('Venta Domicilios', $data['dom_chetano'] ?? ''),
            bit_render_detail('Materias Primas', $data['mp_chetano'] ?? ''),
        ]);
    }

    $toritoSection = '';
    if (!empty($config['sections']['torito'])) {
        $toritoSection = bit_render_section('TORITO', [
            bit_render_detail('Novedades Torito', $data['nov_torito'] ?? ''),
            bit_render_detail('Venta de Productos', $data['ventas_torito'] ?? ''),
        ]);
    }

    $reunionCalidadSection = '';
    if (!empty($config['sections']['reunion_calidad'])) {
        $reunionCalidadSection = bit_render_section('REUNIÓN DE CALIDAD 3:00 P.M.', [
            bit_render_detail('Novedades', $data['reu'] ?? ''),
        ]);
    }

    $html = '
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body{
                font-family: DejaVu Sans, Arial, sans-serif;
                font-size: 14px;
                color: #222;
                margin: 20px;
            }
            .header{
                border-bottom: 2px solid #8B1E1E;
                padding-bottom: 10px;
                margin-bottom: 20px;
            }
            .header-table{
                width: 100%;
                border-collapse: collapse;
            }
            .logo-cell{
                width: 115px;
                vertical-align: middle;
                padding-right: 16px;
            }
            .logo{
                width: 95px;
                height: auto;
            }
            .header-info{
                vertical-align: middle;
            }
            .title{
                font-size: 24px;
                font-weight: bold;
                color: #8B1E1E;
                margin-bottom: 6px;
            }
            .meta{
                margin-bottom: 3px;
            }
            .area-section{
                margin-bottom: 14px;
                border: 1px solid #ddd;
                border-radius: 6px;
                padding: 10px;
                page-break-inside: avoid;
            }
            .area-title{
                font-size: 16px;
                font-weight: bold;
                margin-bottom: 8px;
                color: #8B1E1E;
            }
            .sub-item{
                margin-bottom: 6px;
                line-height: 1.4;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <table class="header-table"><tr>' . $logoHtml . '<td class="header-info">
                <div class="title">BITÁCORA DIARIA ' . bit_h($data['sede'] ?? '') . '</div>
                <div class="meta"><strong>Fecha:</strong> ' . bit_h($data['fecha'] ?? '') . '</div>
                <div class="meta"><strong>Responsable:</strong> ' . bit_h($data['responsable'] ?? '') . '</div>
                <div class="meta"><strong>Cargo:</strong> ' . bit_h($data['cargo'] ?? '') . '</div>
            </td></tr></table>
        </div>';

    
    $html .= bit_render_section('OPERACIONES', [
        bit_render_detail('Servicio al cliente', $data['sac'] ?? ''),
        bit_render_detail('Visita de Entrenadores/Supervisores', $data['supervisores'] ?? ''),
        bit_render_detail_if($tieneSupervision, 'Hora de ingreso de la entrenadora/supervisora/coordinador', $data['hora_entrada'] ?? ''),
        bit_render_detail_if($tieneSupervision, 'Hora de salida de la entrenadora/supervisora/coordinador', $data['hora_salida'] ?? ''),
        bit_render_detail_if($tieneSupervision, 'Actividades Realizadas', $data['act_sup'] ?? ''),
        bit_render_detail('Devoluciones', $data['devo'] ?? ''),
    ]);
    
    $html .= bit_render_section('AFLUENCIA DE COMENSALES', [
        bit_render_detail('Medio día', $data['comens'] ?? ''),
        bit_render_detail('Tarde', $data['comens1'] ?? ''),
        bit_render_detail('Noche', $data['comens2'] ?? ''),
    ]);
    
    $html .= bit_render_section('OBSERVACIONES DE JEFES', [
        bit_render_detail('Mesas', $data['mesas'] ?? ''),
        bit_render_detail('Bar', $data['bar'] ?? ''),
        bit_render_detail('Cocina', $data['cocina'] ?? ''),
    ]);

    $html .= bit_render_section('MERCADEO', [
        bit_render_detail('Venta de Coctelería', $data['coc'] ?? ''),
        bit_render_detail('Venta de Productos Foco', $data['mer'] ?? ''),
        bit_render_detail('Ventas de Productos Nuevos', $data['mer1'] ?? ''),
        bit_render_detail('Campañas del Mes', $data['mer2'] ?? ''),
        bit_render_detail('Casos HelpDesk', $data['mer3'] ?? ''),
        bit_render_detail('Reservas', $data['mer4'] ?? ''),
    ]);

    $html .= bit_render_section('GESTIÓN HUMANA', [
        bit_render_detail('Novedades', $data['gh'] ?? ''),
    ]);

    $html .= bit_render_section('SEGURIDAD Y SALUD EN EL TRABAJO - SST', [
        bit_render_detail('Eventos por incidentes laborales, accidentes laborales y de transito', $data['sst1'] ?? ''),
        bit_render_detail('Incapacidades iguales o mayores a 15 días', $data['sst2'] ?? ''),
        bit_render_detail('Hallazgos Ambiente Laboral', $data['sst3'] ?? ''),
        bit_render_detail('Reportes de extintores y señalización', $data['sst4'] ?? ''),
        bit_render_detail('Entrega de EPP', $data['sst6'] ?? ''),
        bit_render_detail('Visita a la sede del equipo SST', $data['equipo_sst'] ?? ''),
        bit_render_detail('Actividades de Acompañamiento', $data['sst5'] ?? ''),
        bit_render_detail('Otras Novedades (Situaciones de Salud, Condiciones y actos inseguros, etc)', $data['sst7'] ?? ''),
        bit_render_detail('Casos HelpDesk SST', $data['sst8'] ?? ''),
    ]);

    $html .= bit_render_section('SISTEMAS - TI', [
        bit_render_detail('Equipos de Cómputo', $data['ti'] ?? ''),
        bit_render_detail('Facturas FE', $data['ti1'] ?? ''),
        bit_render_detail('Otras Novedades', $data['ti2'] ?? ''),
        bit_render_detail('Casos HelpDesk', $data['ti3'] ?? ''),
    ]);

    $html .= bit_render_section('MANTENIMIENTO', [
        bit_render_detail('Equipos Cocina', $data['mant'] ?? ''),
        bit_render_detail('Equipos Bar', $data['mant1'] ?? ''),
        bit_render_detail('Equipos Salón', $data['mant2'] ?? ''),
        bit_render_detail('Locativos', $data['mant3'] ?? ''),
        bit_render_detail('Uso de planta eléctrica', $data['planta_elect'] ?? ''),
        bit_render_detail_if($usoPlanta, 'Hora encendido', $data['mant5'] ?? ''),
        bit_render_detail_if($usoPlanta, 'Hora apagado', $data['mant6'] ?? ''),
        bit_render_detail_if($usoPlanta, 'Tiempo de uso (minutos)', $data['mant7'] ?? ''),
        bit_render_detail_if($usoPlanta, 'Novedades Planta Eléctrica', $data['mant8'] ?? ''),
        bit_render_detail('Pendientes', $data['mant4'] ?? ''),
    ]);

    $html .= bit_render_section('MEJORAMIENTO Y ESTANDARIZACIÓN', [
        bit_render_detail('Visita a la sede del equipo', $data['equipo_bpm'] ?? ''),
        bit_render_detail_if($tieneTeamCalidad, 'Actividades durante la visita', $data['bpm'] ?? ''),
        bit_render_detail('¿Hubo visita de la Secretaría de Salud?',$data['visita_ss'].'. '.$data['bpm1'] ?? ''),
        bit_render_detail('¿Hubo visita del DAGMA?', $data['visita_dagma'].'. '.$data['bpm2'] ?? ''),
        bit_render_detail('¿Hubo visita del proveedor West - Klaxen?', $data['visita_west'].'. '.$data['bpm3'] ?? ''),
        bit_render_detail('Entrega de ACU', $data['bpm4'] ?? ''),
        bit_render_detail('Entrega de residuos aprovechables', $data['bpm5'] ?? ''),
        bit_render_detail('Entrega de residuos orgánicos', $data['bpm6'] ?? ''),
        bit_render_detail('Control de plagas', $data['bpm7'] ?? ''),
        bit_render_detail('¿Novedades con grameras y/o termómetros? ', $data['novedad_grameras'].'. '.$data['bpm8'] ?? ''),
    ]);
    
    $html .= bit_render_section('ÁREA DE BAR', [
        bit_render_detail('Producción de Bolsas en el día', $data['hielo'].' bolsas' ?? ''),
        bit_render_detail('Compra de Hielo Kolbitos', $data['hielo1'].' bolsas' ?? ''),
        bit_render_detail('Consumo Bolsas del día', $data['hielo2'].' bolsas' ?? ''),
        bit_render_detail('Inventario Final de Bolsas de hielo/día', $data['hielo3'].' bolsas' ?? ''),
        bit_render_detail('Hielo trasladado a otra sede', $data['hielo4'] ?? ''),
        bit_render_detail('Hielo recibido otra sede', $data['hielo5'] ?? ''),
    ]);
    
    $html .= bit_render_section('DESPENSA', [
        bit_render_detail('Novedades', $data['desp'] ?? ''),
    ]);
    
    $html .= bit_render_section('NOVEDADES DOMICILIOS', [
        bit_render_detail('RAPPI', $data['dorp'] ?? ''),
        bit_render_detail('Domicilios Propios', $data['dorp1'] ?? ''),
    ]);

    $html .= bit_render_section('TESORERÍA', [
        bit_render_detail('Novedades', $data['tesor'] ?? ''),
        bit_render_detail('Bonos Coomeva', $data['tesor1'] ?? ''),
        bit_render_detail('Pagos EasyPedido', $data['tesor2'] ?? ''),
    ]);
    
    $html .= bit_render_section('FACTURAS ANULADAS', [
        bit_render_detail('Mesas', $data['fa_mesas'] ?? ''),
        bit_render_detail('Domicilios', $data['fa_dom'] ?? ''),
        bit_render_detail('RAPPI', $data['fa_rappi'] ?? ''),
    ]);
    
    $html .= bit_render_section('MÉTRICAS DE SERVICIO', [
        bit_render_detail('Nº ordenes Rappi', $data['rappi'] ?? ''),
        bit_render_detail('Nº domicilios', $data['domi'] ?? ''),
        bit_render_detail('Nº domiciliarios de DomiExpress', $data['domiexpress'] ?? ''),
        bit_render_detail('Nº horas trabajadas DomiExpress', $data['hdomi'] ?? ''),
    ]);
    
    $html .= bit_render_section('INDICADORES DE DESEMPEÑO', [
        bit_render_detail('Cumplimiento Presupuesto Diario', $data['pd'].'%' ?? ''),
        bit_render_detail('Nº Ticket Promedio', '$'.$data['tp'] ?? ''),
        
    ]);

    $html .= $chetanoSection;
    $html .= $toritoSection;
    $html .= $reunionCalidadSection;

    $dynamicBySection = [];
    foreach ((array) ($config['dynamic_fields'] ?? []) as $field) {
        $sectionTitle = (string) ($field['section_title'] ?? 'Campos adicionales');
        $type = (string) ($field['type'] ?? 'text');
        $label = (string) ($field['label'] ?? $field['name'] ?? 'Campo');
        $name = (string) ($field['name'] ?? '');
        $suffix = (string) ($field['suffix'] ?? '');

        if ($name === '') {
            continue;
        }

        if ($type === 'yes_no') {
            $detailName = (string) ($field['detail_name'] ?? '');
            $value = trim((string) ($data[$name] ?? ''));
            $detail = $detailName !== '' ? trim((string) ($data[$detailName] ?? '')) : '';
            $renderValue = trim($value . ($detail !== '' ? '. ' . $detail : ''));
        } else {
            $renderValue = trim((string) ($data[$name] ?? ''));
            if ($renderValue !== '' && $suffix !== '') {
                $renderValue .= ' ' . $suffix;
            }
        }

        $dynamicBySection[$sectionTitle][] = bit_render_detail($label, $renderValue);
    }

    foreach ($dynamicBySection as $sectionTitle => $rows) {
        $html .= bit_render_section($sectionTitle, $rows);
    }

    $html .= '
    </body>
    </html>';

    return $html;
}

function bit_find_dompdf_autoload(): ?string
{
    $paths = [
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/../dompdf/autoload.inc.php',
        __DIR__ . '/vendor/autoload.php',
        __DIR__ . '/dompdf/autoload.inc.php',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }

    return null;
}

function bit_build_pdf_info(int $empresaId, string $sede, string $fecha, string $responsable): array
{
    $year  = date('Y');
    $month = date('m');

    $baseDir = bit_storage_base_dir() . '/' . $empresaId . '/' . $year . '/' . $month;
    bit_ensure_dir($baseDir);

    $fileName = 'BITACORA_' .
        bit_safe_filename($sede ?: 'SIN_SEDE') . '_' .
        bit_safe_filename($fecha ?: date('d-m-Y')) . '_' .
        bit_safe_filename($responsable ?: 'SIN_RESPONSABLE') . '.pdf';

    return [
        'dir'       => $baseDir,
        'path'      => $baseDir . '/' . $fileName,
        'fileName'  => $fileName,
        'year'      => $year,
        'month'     => $month,
        'empresaId' => $empresaId,
    ];
}

function bit_generate_pdf(string $html, string $outputPath): void
{
    $autoload = bit_find_dompdf_autoload();
    $tempDir = bit_storage_base_dir() . '/tmp';
    bit_ensure_dir($tempDir);

    if ($autoload === null) {
        throw new RuntimeException('No se encontró Dompdf. Instala la librería antes de usar la generación PDF.');
    }

    require_once $autoload;

    if (class_exists(\Mpdf\Mpdf::class)) {
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 13,
            'margin_bottom' => 13,
            'margin_left' => 11,
            'margin_right' => 11,
            'tempDir' => $tempDir,
        ]);
        $mpdf->WriteHTML($html);
        $mpdf->Output($outputPath, 'F');
        return;
    }

    if (!class_exists(\Dompdf\Dompdf::class)) {
        throw new RuntimeException('No hay una librería PDF disponible después de cargar el autoload.');
    }

    $options = new \Dompdf\Options();
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('tempDir', $tempDir);

    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    file_put_contents($outputPath, $dompdf->output());
}

function bit_add_recipients_by_sede(\PHPMailer\PHPMailer\PHPMailer $mail, string $sede): void
{
    $sede = bit_upper_clean($sede);

    /*switch ($sede) {
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
    }*/

    $mail->addBCC('coordinador.sistemas@misterwings.com');
}
