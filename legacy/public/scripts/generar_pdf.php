<?php
require_once __DIR__ . '/../config/security.php';

/**
 * generar_pdf.php
 * ================================================================================
 * Endpoint universal para descargar el PDF de la bitácora.
 * Ubicación: scripts/generar_pdf.php
 *
 * La empresa se lee directamente de $_SESSION['s_idEmpresa']:
 *   1 → MES GROUP          5 → LEBOR
 *   2 → MES SOLUCIONES     6 → MES GROUP SAS ADMIN
 *   3 → LES GROUP          7 → MES GROUP TRILOGÍA
 *   4 → INVALQUIN          8 → MES TEST             
 *
 * No requiere ningún campo oculto en los formularios.
 * Recibe los mismos datos POST que cada send_*.php.
 * ================================================================================
 */

app_require_post_login();

if (empty($_POST)) {
    http_response_code(400);
    exit('Sin datos');
}

require_once __DIR__ . '/pdf_builder.php';

// ── 1. Empresa desde la sesión (igual que el guard de cada vista) ─────────────
$idEmpresa = (int) ($_SESSION['s_idEmpresa'] ?? 1);

// ── 2. Aplicar los mismos defaults que los send_*.php ───────────────────────
$defaults = [
    // [ radio_que_controla,   campo_de_texto,   texto_default ]
    ['visita_ss', 'bpm1', 'Sin novedad.'],
    ['visita_dagma', 'bpm2', 'Sin novedad.'],
    ['visita_west', 'bpm3', 'Sin novedad.'],
    ['novedad_grameras', 'bpm8', 'Sin novedad.'],
    ['equipos_ti', 'ti', 'Sin novedades con los equipos.'],
    ['facturas_ti', 'ti1', 'Las facturas electrónicas se integran con código CUFE.'],
    ['novedades_ti', 'ti2', 'Sin novedades.'],
    ['casos_ti', 'ti3', 'No se reportan casos o solicitudes ni pendientes.'],
    ['accidentes_sst', 'sst1', 'Sin novedades.'],
    ['incapacidades_sst', 'sst2', 'Sin novedades.'],
    ['ambiente_laboral', 'sst3', 'Sin novedades.'],
    ['senal_sst', 'sst4', 'Sin novedades.'],
    ['entrega_epp', 'sst6', 'Sin novedades.'],
    ['novedades_sst', 'sst7', 'Sin novedades.'],
    ['casos_sst', 'sst8', 'Sin novedades.'],
    ['equipos_cocina', 'mant', 'No se presentan novedades.'],
    ['equipos_bar', 'mant1', 'No se presentan novedades.'],
    ['equipos_salon', 'mant2', 'No se presentan novedades.'],
    ['locativos', 'mant3', 'No se presentan novedades.'],
    ['pendientes', 'mant4', 'No se presentan novedades.'],
    ['hielo_enviado', 'hielo4', 'No se enviaron bolsas a otras sedes.'],
    ['hielo_recibido', 'hielo5', 'No se recibieron bolsas de otras sedes.'],
    ['facturas_mesas', 'fa_mesas', 'No se anularon facturas.'],
    ['facturas_domic', 'fa_dom', 'No se anularon facturas.'],
    ['facturas_rappi', 'fa_rappi', 'No se anularon facturas.'],
    ['bonos_coomeva', 'tesor1', 'No se canjearon bonos Coomeva.'],
    ['easypedido', 'tesor2', 'No se realizaron pedidos por EasyPedido.'],
    ['reservas_15', 'mer4', 'No se realizaron reservas.'],
];

foreach ($defaults as [$radio, $campo, $default]) {
    if (($_POST[$radio] ?? '') === 'No' && trim($_POST[$campo] ?? '') === '') {
        $_POST[$campo] = $default;
    }
}

// Planta eléctrica
$pe = $_POST['planta_elect'] ?? '';
if ($pe === 'No') {
    if (trim($_POST['mant5'] ?? '') === '')
        $_POST['mant5'] = '00:00';
    if (trim($_POST['mant6'] ?? '') === '')
        $_POST['mant6'] = '00:00';
    if (trim($_POST['mant7'] ?? '') === '')
        $_POST['mant7'] = 0;
    if (trim($_POST['mant8'] ?? '') === '')
        $_POST['mant8'] = 'No se presentaron novedades relacionadas a la planta eléctrica.';
} elseif ($pe === 'Si') {
    $ini = $_POST['mant5'] ?? '';
    $fin = $_POST['mant6'] ?? '';
    if ($ini !== '' && $fin !== '' && trim($_POST['mant7'] ?? '') === '') {
        [$h1, $m1] = array_map('intval', explode(':', $ini));
        [$h2, $m2] = array_map('intval', explode(':', $fin));
        $diff = ($h2 * 60 + $m2) - ($h1 * 60 + $m1);
        if ($diff < 0)
            $diff += 1440;
        $_POST['mant7'] = $diff;
    }
}

// ── 3. Normalizar $data (igual que el foreach de los send_*.php) ─────────────
$data = [];
foreach ($_POST as $k => $v) {
    $data[$k] = is_array($v) ? implode(', ', $v) : trim((string) $v);
}

// Fecha formateada
if (!empty($data['fechab'])) {
    $data['fecha'] = date('d-m-Y', strtotime($data['fechab']));
}

// Equipo BPM sin visita
$eq_bpm = is_array($_POST['equipo_bpm'] ?? null) ? $_POST['equipo_bpm'] : [];
if (in_array('No hay visita por parte del área', $eq_bpm, true)) {
    $data['bpm'] = 'Sin novedad.';
}

// Equipo SST sin visita
$eq_sst = is_array($_POST['equipo_sst'] ?? null) ? $_POST['equipo_sst'] : [];
if (in_array('No hay visita por parte del área', $eq_sst, true)) {
    $data['sst5'] = 'Sin novedades.';
}

// Gestión Humana vacío → default
if (empty(trim($data['gh'] ?? ''))) {
    $data['gh'] = 'Sin novedad en Gestión Humana.';
}

// ── 4. Construir y entregar el PDF ───────────────────────────────────────────
$html = BitacoraPDF::buildHTML($data, $idEmpresa);
BitacoraPDF::deliver($html, $data, $idEmpresa);
