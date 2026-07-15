<?php
/**
 * pdf_builder.php
 * ================================================================================
 * Librería compartida para construir y entregar el PDF de la bitácora.
 * Ubicación: scripts/pdf_builder.php
 *
 * La empresa se obtiene de $_SESSION['s_idEmpresa'], que coincide con la tabla
 * razones_sociales de la base de datos:
 *   1 → MES GROUP
 *   2 → MES SOLUCIONES HCQC
 *   3 → LES GROUP
 *   4 → INVERSIONES VALQUIN
 *   5 → LEBOR
 *   6 → MES GROUP SAS -ADMIN
 *   7 → MES GROUP - TRILOGIA
 *   8 → MES GROUP TEST
 * Uso desde generar_pdf.php:
 *   require_once __DIR__ . '/pdf_builder.php';
 *   $html = BitacoraPDF::buildHTML($data, $_SESSION['s_idEmpresa']);
 *   BitacoraPDF::deliver($html, $data, $_SESSION['s_idEmpresa']);
 * ================================================================================
 */

class BitacoraPDF
{
  /**
   * Mapa id_empresa (de razones_sociales) → configuración visual del PDF.
   * AGREGAR NUEVAS EMPRESAS AQUÍ si se incorporan a la base de datos.
   */
  private static array $empresas = [
    1 => [
      'nombre' => 'MES Group',
      'color' => '#c0392b',
      'subtitulo' => 'Bitácora Operacional Diaria',
    ],
    2 => [
      'nombre' => 'MES Soluciones HCQC',
      'color' => '#d35400',
      'subtitulo' => 'Bitácora Operacional Diaria',
    ],
    3 => [
      'nombre' => 'Les Group',
      'color' => '#16a085',
      'subtitulo' => 'Bitácora Operacional Diaria',
    ],
    4 => [
      'nombre' => 'Inversiones Valquín',
      'color' => '#2980b9',
      'subtitulo' => 'Bitácora Operacional Diaria',
    ],
    5 => [
      'nombre' => 'Lebor',
      'color' => '#8e44ad',
      'subtitulo' => 'Bitácora Operacional Diaria',
    ],
    6 => [
      'nombre' => 'MES Group SAS',
      'color' => '#c0392b',
      'subtitulo' => 'Bitácora Operacional Diaria — Admin',
    ],
    7 => [
      'nombre' => 'MES Group — Trilogía',
      'color' => '#1a5276',
      'subtitulo' => 'Bitácora Operacional Diaria',
    ],
    8 => [
      'nombre' => 'MES Group TEST',
      'color' => '#27ae60',
      'subtitulo' => 'Bitácora Operacional Diaria',
    ],
  ];

  // ─────────────────────────────────────────────────────────────────────────
  // Helpers
  // ─────────────────────────────────────────────────────────────────────────

  /** Igual que la función e() de los send_*.php */
  public static function e($v): string
  {
    return nl2br(htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8'));
  }

  /** Igual que sanitize_number() de send_mesg.php */
  public static function num($val, bool $is_int = false)
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
    return $is_int ? (int) round((float) $s) : (float) $s;
  }

  /** Devuelve la config de empresa o el fallback de id=1 */
  private static function cfg(int $idEmpresa): array
  {
    return self::$empresas[$idEmpresa] ?? self::$empresas[1];
  }

  // ─────────────────────────────────────────────────────────────────────────
  // buildHTML
  // $data      = array con todos los campos procesados (igual que $data en send_*.php)
  // $idEmpresa = $_SESSION['s_idEmpresa']
  // ─────────────────────────────────────────────────────────────────────────
  public static function buildHTML(array $data, int $idEmpresa = 1): string
  {
    $cfg = self::cfg($idEmpresa);
    $color = $cfg['color'];
    $e = [self::class, 'e'];

    // ── Estados derivados (mismo cálculo que send_*.php) ─────────────────
    $ss_estado = $data['visita_ss'] ?? '—';
    $ss_detalle = ($ss_estado === 'Si' && !empty($data['bpm1'])) ? $data['bpm1'] : 'Sin novedad.';
    $dg_estado = $data['visita_dagma'] ?? '—';
    $dg_detalle = ($dg_estado === 'Si' && !empty($data['bpm2'])) ? $data['bpm2'] : 'Sin novedad.';
    $wk_estado = $data['visita_west'] ?? '—';
    $wk_detalle = ($wk_estado === 'Si' && !empty($data['bpm3'])) ? $data['bpm3'] : 'Sin novedad.';
    $gr_estado = $data['novedad_grameras'] ?? '—';
    $gr_detalle = ($gr_estado === 'Si' && !empty($data['bpm8'])) ? $data['bpm8'] : 'Sin novedad.';
    $pe_estado = $data['planta_elect'] ?? '—';
    $pe_detalle = ($pe_estado === 'Si' && !empty($data['mant8'])) ? $data['mant8']
      : 'No se presentaron novedades relacionadas a la planta eléctrica.';

    // ── Horas supervisora ────────────────────────────────────────────────
    $hora_entrada = trim($data['hora_entrada'] ?? '');
    $hora_salida = trim($data['hora_salida'] ?? '');
    if ($hora_entrada === '00:00')
      $hora_entrada = '';
    if ($hora_salida === '00:00')
      $hora_salida = '';

    // ── Bolsas de hielo ──────────────────────────────────────────────────
    $hi0_fmt = number_format(self::num($data['hielo'] ?? 0, true), 0, ',', '.');
    $hi1_fmt = number_format(self::num($data['hielo1'] ?? 0, true), 0, ',', '.');
    $hi2_fmt = number_format(self::num($data['hielo2'] ?? 0, true), 0, ',', '.');
    $hi3_fmt = number_format(self::num($data['hielo3'] ?? 0, true), 0, ',', '.');

    // ── Sección Chetano (solo sede PANCE, empresa id=1) ──────────────────
    $chetanoSection = '';
    if ($idEmpresa === 1 && isset($data['sede']) && strtoupper(trim($data['sede'])) === 'PANCE') {
      $chetanoSection = '
            <div class="area-section">
                <div class="area-title">CHETANO PANCE</div>
                <div class="stats-section">
                    <div class="stat-box"><strong>Novedades Chetano:</strong> ' . call_user_func($e, $data['nov_chetano'] ?? '') . '</div>
                    <div class="stat-box"><strong>Venta de Productos:</strong> ' . call_user_func($e, $data['ventas_chetano'] ?? '') . '</div>
                    <div class="stat-box"><strong>Venta Domicilios:</strong> ' . call_user_func($e, $data['dom_chetano'] ?? '') . '</div>
                    <div class="stat-box"><strong>Materias Primas:</strong> ' . call_user_func($e, $data['mp_chetano'] ?? '') . '</div>
                </div>
            </div>';
    }

    // ── CSS ──────────────────────────────────────────────────────────────
    $css = "
        * { box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px; color: #222;
            margin: 0; padding: 16px; background: #f5f5f5;
        }
        h2   { color: {$color}; text-align:center; margin:6px 0 2px; font-size:17px; }
        .subtitulo { text-align:center; color:#666; font-size:11px; margin-bottom:12px; }
        .header-info {
            background:#fff; padding:10px 14px; border-radius:6px;
            border-left:5px solid {$color}; margin-bottom:12px;
        }
        .header-info p { margin:3px 0; font-size:11px; }
        .area-section {
            background:#fff; border:1px solid #ddd; border-radius:5px;
            margin-bottom:9px; padding:9px 13px;
        }
        .area-title {
            background:{$color}; color:#fff; font-weight:bold; font-size:10.5px;
            padding:4px 9px; border-radius:3px; margin-bottom:7px;
            text-transform:uppercase; letter-spacing:.4px;
        }
        .sub-item  { margin:3px 0; font-size:10.5px; line-height:1.45; }
        .highlight {
            background:#eaf4fb; border-left:3px solid #3498db;
            padding:4px 8px; margin:3px 0; border-radius:2px; font-size:10.5px;
        }
        .stats-section { display:flex; flex-wrap:wrap; gap:7px; margin:4px 0; }
        .stat-box {
            border:1px solid #e0e0e0; border-radius:4px; padding:5px 9px;
            min-width:100px; background:#fafafa; font-size:10.5px; flex:1;
        }
        .footer {
            text-align:center; font-size:9px; color:#999;
            margin-top:14px; border-top:1px solid #ddd; padding-top:7px;
        }
        ";

    // ── HTML completo ────────────────────────────────────────────────────
    $sede = call_user_func($e, $data['sede'] ?? '');
    $fecha = call_user_func($e, $data['fecha'] ?? ($data['fechab'] ?? ''));

    $html = "<!DOCTYPE html>
<html lang='es'>
<head>
<meta charset='UTF-8'>
<title>Bitácora {$cfg['nombre']} — {$sede}</title>
<style>{$css}</style>
</head>
<body>

<h2>{$cfg['nombre']}</h2>
<div class='subtitulo'>{$cfg['subtitulo']}</div>

<div class='header-info'>
  <p><strong>Fecha:</strong> {$fecha} &nbsp;|&nbsp; <strong>Sede:</strong> {$sede}</p>
  <p><strong>Responsable:</strong> " . call_user_func($e, $data['responsable'] ?? '') . "
     &nbsp;|&nbsp; <strong>Cargo:</strong> " . call_user_func($e, $data['cargo'] ?? '') . "</p>
</div>

<!-- OPERACIONES -->
<div class='area-section'>
  <div class='area-title'>Área de Operaciones</div>
  <div class='sub-item'><strong>Servicio al Cliente (SAC):</strong> " . call_user_func($e, $data['sac'] ?? '') . "</div>
  <div class='sub-item'><strong>Visita de Entrenadores/Supervisores:</strong> " . call_user_func($e, $data['supervisores'] ?? '') . "</div>
  <div class='sub-item'><strong>Actividades realizadas:</strong> " . call_user_func($e, $data['act_sup'] ?? '') . "</div>"
      . ($hora_entrada !== '' ? "<div class='sub-item'><strong>Hora ingreso supervisor/a:</strong> " . call_user_func($e, $hora_entrada) . "</div>" : '')
      . ($hora_salida !== '' ? "<div class='sub-item'><strong>Hora salida supervisor/a:</strong> " . call_user_func($e, $hora_salida) . "</div>" : '') . "
  <div class='sub-item'><strong>Devoluciones de producto:</strong> " . call_user_func($e, $data['devo'] ?? '') . "</div>
</div>

<!-- AFLUENCIA -->
<div class='area-section'>
  <div class='area-title'>Afluencia de Comensales</div>
  <div class='stats-section'>
    <div class='stat-box'><strong>Medio día:</strong> " . call_user_func($e, $data['comens'] ?? '') . "</div>
    <div class='stat-box'><strong>Tarde:</strong> " . call_user_func($e, $data['comens1'] ?? '') . "</div>
    <div class='stat-box'><strong>Noche:</strong> " . call_user_func($e, $data['comens2'] ?? '') . "</div>
  </div>
</div>

<!-- OBSERVACIONES JEFES -->
<div class='area-section'>
  <div class='area-title'>Observaciones de Jefes</div>
  <div class='sub-item'><strong>Jefe de mesas:</strong> " . call_user_func($e, $data['mesas'] ?? '') . "</div>
  <div class='sub-item'><strong>Jefe de bar:</strong> " . call_user_func($e, $data['bar'] ?? '') . "</div>
  <div class='sub-item'><strong>Jefe de cocina:</strong> " . call_user_func($e, $data['cocina'] ?? '') . "</div>
</div>

<!-- MERCADEO -->
<div class='area-section'>
  <div class='area-title'>Área de Mercadeo</div>
  <div class='sub-item'><strong>Venta de Coctelería:</strong> " . call_user_func($e, $data['coc'] ?? '') . "</div>
  <div class='sub-item'><strong>Venta Productos Foco:</strong> " . call_user_func($e, $data['mer'] ?? '') . "</div>
  <div class='sub-item'><strong>Ventas Productos Nuevos:</strong> " . call_user_func($e, $data['mer1'] ?? '') . "</div>
  <div class='sub-item'><strong>Campañas del mes:</strong> " . call_user_func($e, $data['mer2'] ?? '') . "</div>
  <div class='sub-item'><strong>Casos HelpDesk Mercadeo:</strong> " . call_user_func($e, $data['mer3'] ?? '') . "</div>
  <div class='sub-item'><strong>Reservas (≥ 15 pax):</strong> " . call_user_func($e, $data['mer4'] ?? '') . "</div>
</div>

<!-- GESTIÓN HUMANA -->
<div class='area-section'>
  <div class='area-title'>Gestión Humana</div>
  <div class='sub-item'>" . call_user_func($e, $data['gh'] ?? 'Sin novedad en Gestión Humana.') . "</div>
</div>

<!-- SST -->
<div class='area-section'>
  <div class='area-title'>Seguridad y Salud en el Trabajo (SST)</div>
  <div class='sub-item'><strong>Accidentes laborales / tránsito:</strong> " . call_user_func($e, $data['sst1'] ?? '') . "</div>
  <div class='sub-item'><strong>Incapacidades ≥ 15 días:</strong> " . call_user_func($e, $data['sst2'] ?? '') . "</div>
  <div class='sub-item'><strong>Ambiente laboral:</strong> " . call_user_func($e, $data['sst3'] ?? '') . "</div>
  <div class='sub-item'><strong>Señalización y extintores:</strong> " . call_user_func($e, $data['sst4'] ?? '') . "</div>
  <div class='sub-item'><strong>Entrega de EPP:</strong> " . call_user_func($e, $data['sst6'] ?? '') . "</div>
  <div class='sub-item'><strong>Equipo SST visitante:</strong> " . call_user_func($e, $data['equipo_sst'] ?? '') . "</div>
  <div class='sub-item'><strong>Actividades de acompañamiento:</strong> " . call_user_func($e, $data['sst5'] ?? '') . "</div>
  <div class='sub-item'><strong>Otras novedades SST:</strong> " . call_user_func($e, $data['sst7'] ?? '') . "</div>
  <div class='sub-item'><strong>Casos HelpDesk SST:</strong> " . call_user_func($e, $data['sst8'] ?? '') . "</div>
</div>

<!-- TI -->
<div class='area-section'>
  <div class='area-title'>Área de Sistemas — TI</div>
  <div class='sub-item'><strong>Equipos de cómputo:</strong> " . call_user_func($e, $data['ti'] ?? '') . "</div>
  <div class='sub-item'><strong>Facturas electrónicas:</strong> " . call_user_func($e, $data['ti1'] ?? '') . "</div>
  <div class='sub-item'><strong>Otras novedades TI:</strong> " . call_user_func($e, $data['ti2'] ?? '') . "</div>
  <div class='sub-item'><strong>Casos HelpDesk TI:</strong> " . call_user_func($e, $data['ti3'] ?? '') . "</div>
</div>

<!-- MANTENIMIENTO -->
<div class='area-section'>
  <div class='area-title'>Área de Mantenimiento e Infraestructura</div>
  <div class='sub-item'><strong>Equipos Cocina:</strong> " . call_user_func($e, $data['mant'] ?? '') . "</div>
  <div class='sub-item'><strong>Equipos Bar:</strong> " . call_user_func($e, $data['mant1'] ?? '') . "</div>
  <div class='sub-item'><strong>Equipos Salón:</strong> " . call_user_func($e, $data['mant2'] ?? '') . "</div>
  <div class='sub-item'><strong>Locativos:</strong> " . call_user_func($e, $data['mant3'] ?? '') . "</div>
  <div class='sub-item'><strong>Uso planta eléctrica:</strong> " . call_user_func($e, $pe_estado) . "</div>"
      . ($pe_estado === 'Si'
        ? "<div class='sub-item'><strong>Hora encendido:</strong> " . call_user_func($e, $data['mant5'] ?? '') . "</div>
       <div class='sub-item'><strong>Hora apagado:</strong> " . call_user_func($e, $data['mant6'] ?? '') . "</div>
       <div class='sub-item'><strong>Tiempo de uso (min):</strong> " . call_user_func($e, $data['mant7'] ?? '') . "</div>
       <div class='sub-item'><strong>Novedades planta:</strong> " . call_user_func($e, $data['mant8'] ?? '') . "</div>"
        : "<div class='sub-item'><strong>Novedades planta:</strong> " . call_user_func($e, $pe_detalle) . "</div>") . "
  <div class='sub-item'><strong>Pendientes:</strong> " . call_user_func($e, $data['mant4'] ?? '') . "</div>
</div>

<!-- MEJORAMIENTO -->
<div class='area-section'>
  <div class='area-title'>Área de Mejoramiento y Estandarización</div>
  <div class='sub-item'><strong>Equipo BPM visitante:</strong> " . call_user_func($e, $data['equipo_bpm'] ?? '') . "</div>
  <div class='sub-item'><strong>Actividades durante visita:</strong> " . call_user_func($e, $data['bpm'] ?? '') . "</div>

  <div class='sub-item'><strong>¿Visita Secretaría de Salud?:</strong> " . call_user_func($e, $ss_estado) . "</div>
  <div class='highlight'>" . ($ss_estado === 'Si' ? '<strong>Detalle:</strong> ' : '') . call_user_func($e, $ss_detalle) . "</div>

  <div class='sub-item'><strong>¿Visita DAGMA?:</strong> " . call_user_func($e, $dg_estado) . "</div>
  <div class='highlight'>" . ($dg_estado === 'Si' ? '<strong>Detalle:</strong> ' : '') . call_user_func($e, $dg_detalle) . "</div>

  <div class='sub-item'><strong>¿Visita West-Klaxen?:</strong> " . call_user_func($e, $wk_estado) . "</div>
  <div class='highlight'>" . ($wk_estado === 'Si' ? '<strong>Detalle:</strong> ' : '') . call_user_func($e, $wk_detalle) . "</div>

  <div class='sub-item'><strong>Entrega ACU:</strong> " . call_user_func($e, $data['bpm4'] ?? '') . "</div>
  <div class='sub-item'><strong>Residuos aprovechables:</strong> " . call_user_func($e, $data['bpm5'] ?? '') . "</div>
  <div class='sub-item'><strong>Residuos orgánicos:</strong> " . call_user_func($e, $data['bpm6'] ?? '') . "</div>
  <div class='sub-item'><strong>Control de plagas:</strong> " . call_user_func($e, $data['bpm7'] ?? '') . "</div>

  <div class='sub-item'><strong>¿Novedades grameras/termómetros?:</strong> " . call_user_func($e, $gr_estado) . "</div>
  <div class='highlight'>" . ($gr_estado === 'Si' ? '<strong>Detalle:</strong> ' : '') . call_user_func($e, $gr_detalle) . "</div>
</div>

<!-- BAR -->
<div class='area-section'>
  <div class='area-title'>Área de Bar</div>
  <div class='sub-item'><strong>Producción bolsas de hielo:</strong>  {$hi0_fmt} bolsas</div>
  <div class='sub-item'><strong>Compra hielo Kolbitos:</strong>        {$hi1_fmt} bolsas</div>
  <div class='sub-item'><strong>Consumo bolsas del día:</strong>       {$hi2_fmt} bolsas</div>
  <div class='sub-item'><strong>Inventario final bolsas:</strong>      {$hi3_fmt} bolsas</div>
  <div class='sub-item'><strong>Hielo trasladado a otra sede:</strong> " . call_user_func($e, $data['hielo4'] ?? '') . "</div>
  <div class='sub-item'><strong>Hielo recibido de otra sede:</strong>  " . call_user_func($e, $data['hielo5'] ?? '') . "</div>
</div>

<!-- DESPENSA -->
<div class='area-section'>
  <div class='area-title'>Área de Despensa</div>
  <div class='sub-item'>" . call_user_func($e, $data['desp'] ?? '') . "</div>
</div>

<!-- DOMICILIOS -->
<div class='area-section'>
  <div class='area-title'>Novedades Domicilios</div>
  <div class='stats-section'>
    <div class='stat-box'><strong>Rappi:</strong> " . call_user_func($e, $data['dorp'] ?? '') . "</div>
    <div class='stat-box'><strong>Domicilios propios:</strong> " . call_user_func($e, $data['dorp1'] ?? '') . "</div>
  </div>
</div>

<!-- TESORERÍA -->
<div class='area-section'>
  <div class='area-title'>Área de Tesorería</div>
  <div class='sub-item'><strong>Novedades:</strong> " . call_user_func($e, $data['tesor'] ?? '') . "</div>
  <div class='sub-item'><strong>Bonos Coomeva:</strong> " . call_user_func($e, $data['tesor1'] ?? '') . "</div>
  <div class='sub-item'><strong>Pagos EasyPedido:</strong> " . call_user_func($e, $data['tesor2'] ?? '') . "</div>
</div>

<!-- FACTURAS ANULADAS -->
<div class='area-section'>
  <div class='area-title'>Facturas Anuladas</div>
  <div class='stats-section'>
    <div class='stat-box'><strong>Mesas:</strong> " . call_user_func($e, $data['fa_mesas'] ?? '') . "</div>
    <div class='stat-box'><strong>Domicilios:</strong> " . call_user_func($e, $data['fa_dom'] ?? '') . "</div>
    <div class='stat-box'><strong>Rappi:</strong> " . call_user_func($e, $data['fa_rappi'] ?? '') . "</div>
  </div>
</div>

<!-- MÉTRICAS -->
<div class='area-section'>
  <div class='area-title'>Métricas de Servicio</div>
  <div class='stats-section'>
    <div class='stat-box'><strong>Órdenes Rappi:</strong> " . call_user_func($e, $data['rappi'] ?? '') . "</div>
    <div class='stat-box'><strong>Domicilios:</strong> " . call_user_func($e, $data['domi'] ?? '') . "</div>
    <div class='stat-box'><strong>DomiExpress (domicil.):</strong> " . call_user_func($e, $data['domiexpress'] ?? '') . "</div>
    <div class='stat-box'><strong>DomiExpress (horas):</strong> " . call_user_func($e, $data['hdomi'] ?? '') . "</div>
  </div>
</div>

<!-- INDICADORES -->
<div class='area-section'>
  <div class='area-title'>Indicadores de Desempeño</div>
  <div class='stats-section'>
    <div class='stat-box'><strong>Cumplimiento presupuesto:</strong> " . call_user_func($e, $data['pd'] ?? '') . "%</div>
    <div class='stat-box'><strong>Ticket promedio:</strong> \$" . call_user_func($e, $data['tp'] ?? '') . "</div>
  </div>
</div>

{$chetanoSection}

<div class='footer'>
  Generado el " . date('d/m/Y H:i') . " &nbsp;&mdash;&nbsp;
  {$cfg['nombre']} &nbsp;|&nbsp; {$cfg['subtitulo']}
</div>
</body>
</html>";

    return $html;
  }

  // ─────────────────────────────────────────────────────────────────────────
  // deliver — entrega el HTML como PDF (mPDF > Dompdf > fallback HTML)
  // vendor/autoload.php está en scripts/vendor/autoload.php
  // ─────────────────────────────────────────────────────────────────────────
  public static function deliver(
    string $html,
    array $data,
    int $idEmpresa = 1,
    string $mode = 'download'   // 'download' | 'inline'
  ): void {
    $cfg = self::cfg($idEmpresa);
    $sede = preg_replace('/[^A-Za-z0-9_\-]/', '_', $data['sede'] ?? 'sede');
    $fecha = preg_replace('/[^A-Za-z0-9_\-]/', '-', $data['fecha'] ?? ($data['fechab'] ?? date('Y-m-d')));
    $nombre = 'Bitacora_' . preg_replace('/\s+/', '_', $cfg['nombre']) . "_{$sede}_{$fecha}";

    // vendor/ está en la misma carpeta que este archivo (scripts/)
    $autoload = __DIR__ . '/vendor/autoload.php';

    if (file_exists($autoload)) {
      require_once $autoload;

      // ── mPDF (preferida) ─────────────────────────────────────────────
      if (class_exists('\Mpdf\Mpdf')) {
        $mpdf = new \Mpdf\Mpdf([
          'mode' => 'utf-8',
          'format' => 'A4',
          'margin_top' => 13,
          'margin_bottom' => 13,
          'margin_left' => 11,
          'margin_right' => 11,
        ]);
        $mpdf->SetTitle($nombre);
        $mpdf->WriteHTML($html);
        $mpdf->Output("{$nombre}.pdf", $mode === 'inline' ? 'I' : 'D');
        exit;
      }

      // ── Dompdf ───────────────────────────────────────────────────────
      if (class_exists('\Dompdf\Dompdf')) {
        $dompdf = new \Dompdf\Dompdf([
          'isHtml5ParserEnabled' => true,
          'isRemoteEnabled' => false,
        ]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("{$nombre}.pdf", ['Attachment' => ($mode !== 'inline')]);
        exit;
      }
    }

    // ── Fallback: HTML descargable (Ctrl+P → Guardar como PDF) ──────────
    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $nombre . '.html"');
    echo $html;
    exit;
  }
}