<?php
declare(strict_types=1);

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

function bit_storage_resolve_path(string $relativePath): ?string
{
    $relativePath = str_replace('\\', '/', trim($relativePath));
    if ($relativePath === '' || str_starts_with($relativePath, '/') || preg_match('~(^|/)\.\.(/|$)~', $relativePath)) {
        return null;
    }

    $baseDir = bit_storage_base_dir();
    $path = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    $realBase = realpath($baseDir);
    $realPath = realpath($path);

    if ($realBase === false || $realPath === false || !str_starts_with($realPath, $realBase . DIRECTORY_SEPARATOR) || !is_file($realPath)) {
        return null;
    }

    return $realPath;
}

function bit_pdf_logo_src(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

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

        return $cached = 'data:' . $mime . ';base64,' . base64_encode($contents);
    }

    return $cached = '';
}

function bit_find_dompdf_autoload(): ?string
{
    $path = __DIR__ . '/../../vendor/autoload.php';
    return file_exists($path) ? $path : null;
}

function bit_build_pdf_info(int $empresaId, string $sede, string $fecha, string $responsable): array
{
    $year = date('Y');
    $month = date('m');

    $baseDir = bit_storage_base_dir() . '/' . $empresaId . '/' . $year . '/' . $month;
    bit_ensure_dir($baseDir);

    $fileName = 'BITACORA_'
        . bit_safe_filename($sede ?: 'SIN_SEDE') . '_'
        . bit_safe_filename($fecha ?: date('d-m-Y')) . '_'
        . bit_safe_filename($responsable ?: 'SIN_RESPONSABLE') . '_'
        . bin2hex(random_bytes(6)) . '.pdf';

    return [
        'dir' => $baseDir,
        'path' => $baseDir . '/' . $fileName,
        'fileName' => $fileName,
        'relativePath' => $empresaId . '/' . $year . '/' . $month . '/' . $fileName,
        'year' => $year,
        'month' => $month,
        'empresaId' => $empresaId,
    ];
}

function bit_normalize_pdf_date(string $date): ?string
{
    $date = trim($date);
    if ($date === '') {
        return null;
    }

    foreach (['Y-m-d', 'd-m-Y'] as $format) {
        $parsed = DateTimeImmutable::createFromFormat($format, $date);
        if ($parsed instanceof DateTimeImmutable && $parsed->format($format) === $date) {
            return $parsed->format('Y-m-d');
        }
    }

    $timestamp = strtotime($date);
    return $timestamp === false ? null : date('Y-m-d', $timestamp);
}

function bit_pdf_expiration_datetime(): ?string
{
    $days = app_env_int('BITACORA_PDF_TTL_DAYS', 90);
    if ($days <= 0) {
        return null;
    }

    return gmdate('Y-m-d H:i:s', time() + ($days * 86400));
}

function bit_pdfs_have_expires_at(PDO $pdo): bool
{
    static $hasColumn = null;
    if ($hasColumn !== null) {
        return $hasColumn;
    }

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM bitacora_pdfs LIKE 'expires_at'");
        return $hasColumn = $stmt !== false && $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    } catch (Throwable $e) {
        error_log('No fue posible verificar columna expires_at de bitacora_pdfs: ' . $e->getMessage());
        return $hasColumn = false;
    }
}

function bit_register_pdf(array $pdfInfo, string $sede, string $fecha, string $responsable, string $usuario, ?PDO $pdo = null): ?array
{
    if (empty($pdfInfo['path']) || !is_file((string) $pdfInfo['path'])) {
        return null;
    }

    $externalPdo = $pdo !== null;
    try {
        require_once __DIR__ . '/../bd/conexion.php';
        $pdo = $pdo ?? Conexion::Conectar();
        $token = bin2hex(random_bytes(32));
        $relativePath = str_replace('\\', '/', (string) ($pdfInfo['relativePath'] ?? ''));
        if ($relativePath === '') {
            $relativePath = (int) ($pdfInfo['empresaId'] ?? 0) . '/' . (string) ($pdfInfo['year'] ?? date('Y')) . '/' . (string) ($pdfInfo['month'] ?? date('m')) . '/' . (string) ($pdfInfo['fileName'] ?? basename((string) $pdfInfo['path']));
        }

        $hasExpiresAt = bit_pdfs_have_expires_at($pdo);
        $columns = 'token, idEmpresa, sede, fecha_bitacora, responsable, created_by, relative_path, file_name';
        $values = ':token, :idEmpresa, :sede, :fecha_bitacora, :responsable, :created_by, :relative_path, :file_name';
        $params = [
            'token' => $token,
            'idEmpresa' => (int) ($pdfInfo['empresaId'] ?? 0),
            'sede' => $sede,
            'fecha_bitacora' => bit_normalize_pdf_date($fecha),
            'responsable' => $responsable !== '' ? $responsable : null,
            'created_by' => $usuario,
            'relative_path' => $relativePath,
            'file_name' => (string) ($pdfInfo['fileName'] ?? basename((string) $pdfInfo['path'])),
        ];
        if ($hasExpiresAt) {
            $columns .= ', expires_at';
            $values .= ', :expires_at';
            $params['expires_at'] = bit_pdf_expiration_datetime();
        }

        $stmt = $pdo->prepare('INSERT INTO bitacora_pdfs (' . $columns . ') VALUES (' . $values . ')');
        $stmt->execute($params);

        $pdfInfo['token'] = $token;
        $pdfInfo['pdfId'] = (int) $pdo->lastInsertId();
        $pdfInfo['relativePath'] = $relativePath;
        return $pdfInfo;
    } catch (Throwable $e) {
        if (!empty($pdfInfo['path']) && is_file((string) $pdfInfo['path'])) {
            @unlink((string) $pdfInfo['path']);
        }
        if ($externalPdo) {
            throw $e;
        }
        error_log('No fue posible registrar PDF de bitácora: ' . $e->getMessage());
        return null;
    }
}

function bit_pdf_download_url(?array $pdfRecord, string $scriptPath = '../scripts/download_bitacora.php'): ?string
{
    $token = (string) ($pdfRecord['token'] ?? '');
    if ($token === '') {
        return null;
    }

    return $scriptPath . '?token=' . urlencode($token);
}

function bit_generate_pdf(string $html, string $outputPath): void
{
    $autoload = bit_find_dompdf_autoload();
    $tempDir = bit_storage_base_dir() . '/tmp';
    bit_ensure_dir($tempDir);

    if ($autoload === null) {
        throw new RuntimeException('No se encontró el autoload de Composer. Instala las dependencias antes de generar PDFs.');
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
    $options->set('isRemoteEnabled', false);
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('tempDir', $tempDir);

    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    if (file_put_contents($outputPath, $dompdf->output()) === false) {
        throw new RuntimeException('No fue posible escribir el PDF generado.');
    }
}
