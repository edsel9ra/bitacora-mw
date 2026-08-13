<?php
declare(strict_types=1);

namespace PHPMailer\PHPMailer {
    final class PHPMailer
    {
        public static array $instances = [];
        public bool $SMTPKeepAlive = false;
        public string $Subject = '';
        public string $Body = '';
        public array $attachments = [];

        public function __construct(bool $exceptions = false)
        {
            self::$instances[] = $this;
        }

        public function addAddress(string $email): void
        {
        }

        public function addCC(string $email): void
        {
        }

        public function addBCC(string $email): void
        {
        }

        public function addAttachment(string $path, string $name = ''): void
        {
            $this->attachments[] = [$path, $name];
        }

        public function clearAllRecipients(): void
        {
        }

        public function clearAttachments(): void
        {
            $this->attachments = [];
        }

        public function send(): bool
        {
            return true;
        }

        public function smtpClose(): void
        {
        }
    }
}

namespace {
    use PHPMailer\PHPMailer\PHPMailer;

    $sectionQueueJobs = [];

    function app_configure_mailer(PHPMailer $mail, string $fromName = ''): void
    {
    }

    function app_bitacora_db_section_recipients(int $empresaId, string $sede): array
    {
        return [[
            'email' => 'restringido@example.test',
            'type' => 'to',
            'sections' => ['operaciones'],
        ]];
    }

    function app_bitacora_field_visible_for_sede(array $config, string $sede): bool
    {
        return true;
    }

    function app_bitacora_field_available_for_date(array $field, string $date): bool
    {
        return true;
    }

    function app_bitacora_field_is_presentational(array $field): bool
    {
        return (string) ($field['type'] ?? '') === 'subsection';
    }

    function bit_h($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    function bit_report_display_value(string $value): string
    {
        $value = trim($value);
        return $value === 'No' ? 'Sin novedad' : $value;
    }

    function bit_report_yes_no_value(string $answer, string $detail = ''): string
    {
        $answer = trim($answer);
        $detail = trim($detail);
        if ($answer === 'No' && $detail !== '') {
            return $detail;
        }
        return bit_report_display_value(trim($answer . ($detail !== '' ? '. ' . $detail : '')));
    }

    function bit_render_detail(string $title, string $value, bool $mostrarSiVacio = false): string
    {
        $value = bit_report_display_value($value);
        if (!$mostrarSiVacio && $value === '') {
            return '';
        }
        return '<div><strong>' . bit_h($title) . ':</strong> ' . bit_h($value) . '</div>';
    }

    function bit_render_section(string $title, array $rows): string
    {
        return '<section>' . bit_h($title) . implode('', $rows) . '</section>';
    }

    function bit_render_subsection(array $field): string
    {
        return '<div>' . bit_h($field['label'] ?? '') . ' ' . bit_h($field['description'] ?? '') . '</div>';
    }

    function bit_enqueue_email(?int $envioId, int $empresaId, string $sede, string $usuario, string $subject, string $bodyHtml, array $recipients, array $attachments = [], ?PDO $pdo = null, string $jobType = 'main'): ?int
    {
        $GLOBALS['sectionQueueJobs'][] = [
            'attachments_json' => $attachments === [] ? null : json_encode($attachments, JSON_THROW_ON_ERROR),
            'envio_id' => $envioId,
            'job_type' => $jobType,
        ];

        return count($GLOBALS['sectionQueueJobs']);
    }

    function section_mail_assert_same($expected, $actual, string $label): void
    {
        if ($expected !== $actual) {
            fwrite(STDERR, $label . ' fallo. Esperado: ' . var_export($expected, true) . ' Actual: ' . var_export($actual, true) . PHP_EOL);
            exit(1);
        }
    }

    require_once __DIR__ . '/../public/scripts/bitacora_mail_helpers.php';

    $pdfPath = tempnam(sys_get_temp_dir(), 'bitacora_privacy_');
    if ($pdfPath === false) {
        throw new RuntimeException('No fue posible crear el PDF temporal de prueba.');
    }
    file_put_contents($pdfPath, '%PDF-test');

    $pdfInfo = [
        'path' => $pdfPath,
        'relativePath' => '1/2026/08/bitacora.pdf',
        'fileName' => 'bitacora.pdf',
    ];
    $data = [
        'sede' => 'PANCE',
        'fecha' => '03-08-2026',
        'responsable' => 'Prueba',
        'cargo' => 'Pruebas',
    ];
    $sections = [[
        'key' => 'operaciones',
        'title' => 'Operaciones',
        'fields' => [[
            'type' => 'subsection',
            'name' => 'datos_equipo',
            'label' => 'Datos del equipo',
            'description' => 'Incluye novedades',
        ], [
            'type' => 'yes_no',
            'name' => 'novedad_equipo',
            'label' => '¿Hubo novedad?',
            'detail_name' => 'novedad_equipo_detalle',
            'detail_type' => 'textarea',
        ]],
    ]];
    $data['novedad_equipo'] = 'No';
    $data['novedad_equipo_detalle'] = 'Sin novedad.';
    $fullRecipients = ['to' => ['global@example.test'], 'cc' => [], 'bcc' => []];

    $sent = bit_send_section_emails(1, 'PANCE', $data, $sections, $fullRecipients, 'Bitacora');
    section_mail_assert_same(1, $sent, 'correo sincronico por seccion enviado');
    section_mail_assert_same([], PHPMailer::$instances[0]->attachments, 'correo sincronico por seccion sin AddAttachment');
    section_mail_assert_same(true, strpos(PHPMailer::$instances[0]->Body, 'Datos del equipo') !== false, 'subseccion incluida en correo por seccion');
    section_mail_assert_same(true, strpos(PHPMailer::$instances[0]->Body, 'Incluye novedades') !== false, 'descripcion incluida en correo por seccion');
    section_mail_assert_same(true, strpos(PHPMailer::$instances[0]->Body, 'Sin novedad') !== false, 'respuesta No convertida en correo por seccion');
    section_mail_assert_same(false, strpos(PHPMailer::$instances[0]->Body, 'No. Sin novedad.') !== false, 'correo por seccion sin prefijo No');

    $supervisionBody = bit_render_supervision_body($sections, $data);
    section_mail_assert_same(true, strpos($supervisionBody, 'Datos del equipo') !== false, 'subseccion incluida en correo de supervision');
    section_mail_assert_same(true, strpos($supervisionBody, 'Sin novedad') !== false, 'respuesta No convertida en correo de supervision');
    section_mail_assert_same(false, strpos($supervisionBody, 'No. Sin novedad.') !== false, 'correo de supervision sin prefijo No');

    $queued = bit_queue_section_emails(1, 'PANCE', 'prueba', $data, $sections, $fullRecipients, 'Bitacora', null, 42);
    section_mail_assert_same(1, $queued, 'correo asincronico por seccion encolado');
    section_mail_assert_same(null, $sectionQueueJobs[0]['attachments_json'] ?? null, 'trabajo por seccion sin attachments_json');
    section_mail_assert_same(42, $sectionQueueJobs[0]['envio_id'] ?? null, 'trabajo por seccion enlazado al envio');
    section_mail_assert_same('section', $sectionQueueJobs[0]['job_type'] ?? null, 'trabajo por seccion identificado');
    section_mail_assert_same([[
        'relative_path' => '1/2026/08/bitacora.pdf',
        'file_name' => 'bitacora.pdf',
    ]], bit_pdf_queue_attachments($pdfInfo), 'adjunto conservado para correo global');

    unlink($pdfPath);
    echo 'Section mail privacy tests OK' . PHP_EOL;
}
