<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../public/config/mailer.php';
require_once __DIR__ . '/../public/config/bitacora.php';
require_once __DIR__ . '/../public/scripts/bitacora_helpers.php';
require_once __DIR__ . '/../public/bd/conexion.php';

const QUEUE_STALE_LOCK_ERROR = 'El correo quedó bloqueado y superó el máximo de intentos.';

function queue_resolve_attachment(string $relativePath): ?string
{
    return bit_storage_resolve_path($relativePath);
}

function queue_fetch_next_query(PDO $pdo, bool $skipLocked): PDOStatement
{
    $sql = "SELECT * FROM bitacora_email_queue WHERE estado = 'pendiente' AND available_at <= NOW() AND attempts < max_attempts ORDER BY id LIMIT 1 FOR UPDATE";
    if ($skipLocked) {
        $sql .= ' SKIP LOCKED';
    }

    return $pdo->query($sql);
}

function queue_fetch_next(PDO $pdo, string $workerId): ?array
{
    static $supportsSkipLocked = null;
    $useSkipLocked = $supportsSkipLocked !== false;

    $pdo->beginTransaction();
    try {
        try {
            $stmt = queue_fetch_next_query($pdo, $useSkipLocked);
            if ($supportsSkipLocked === null) {
                $supportsSkipLocked = $useSkipLocked;
            }
        } catch (Throwable $e) {
            if (!$useSkipLocked) {
                throw $e;
            }

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $supportsSkipLocked = false;

            return queue_fetch_next($pdo, $workerId);
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            $pdo->commit();
            return null;
        }

        $update = $pdo->prepare("UPDATE bitacora_email_queue SET estado = 'procesando', attempts = attempts + 1, locked_by = :worker, locked_at = NOW() WHERE id = :id AND estado = 'pendiente'");
        $update->execute([
            'worker' => $workerId,
            'id' => (int) $row['id'],
        ]);
        if ($update->rowCount() !== 1) {
            throw new RuntimeException('El trabajo de correo fue tomado por otro proceso.');
        }
        $pdo->commit();

        $row['attempts'] = (int) $row['attempts'] + 1;
        $row['locked_by'] = $workerId;
        return $row;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function queue_mark_sent(PDO $pdo, array $row): void
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE bitacora_email_queue SET estado = 'enviado', sent_at = NOW(), locked_by = NULL, locked_at = NULL, last_error = NULL WHERE id = :id AND estado = 'procesando' AND locked_by = :worker");
        $stmt->execute([
            'id' => (int) $row['id'],
            'worker' => (string) ($row['locked_by'] ?? ''),
        ]);
        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('Se perdió el bloqueo del trabajo después del envío SMTP.');
        }

        $envioId = (int) ($row['idEnvio'] ?? 0);
        if ($envioId > 0) {
            if ((string) ($row['job_type'] ?? 'main') === 'section') {
                $envio = $pdo->prepare('UPDATE bitacora_envios SET correos_seccion_enviados = correos_seccion_enviados + 1 WHERE id = :id');
                $envio->execute(['id' => $envioId]);
            } else {
                $sectionFailed = $pdo->prepare("SELECT 1 FROM bitacora_email_queue WHERE idEnvio = :id AND job_type = 'section' AND estado = 'fallido' LIMIT 1");
                $sectionFailed->execute(['id' => $envioId]);
                $hasSectionFailure = $sectionFailed->fetchColumn() !== false;
                if (!$hasSectionFailure) {
                    $envio = $pdo->prepare("UPDATE bitacora_envios SET estado = CASE WHEN tipo_formulario = 'supervision' OR pdf_generado = 1 THEN 'completado' ELSE 'parcial' END, correo_enviado = 1 WHERE id = :id");
                } else {
                    $envio = $pdo->prepare("UPDATE bitacora_envios SET estado = 'parcial', correo_enviado = 1 WHERE id = :id");
                }
                $envio->execute(['id' => $envioId]);
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function queue_mark_failed(PDO $pdo, array $row, string $error): void
{
    $attempts = (int) ($row['attempts'] ?? 1);
    $maxAttempts = max(1, (int) ($row['max_attempts'] ?? 3));
    $final = $attempts >= $maxAttempts;
    $estado = $final ? 'fallido' : 'pendiente';
    $delay = min(3600, 60 * (2 ** max(0, $attempts - 1)));

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE bitacora_email_queue SET estado = :estado, available_at = DATE_ADD(NOW(), INTERVAL {$delay} SECOND), locked_by = NULL, locked_at = NULL, last_error = :error WHERE id = :id AND estado = 'procesando' AND locked_by = :worker");
        $stmt->execute([
            'estado' => $estado,
            'error' => mb_substr($error, 0, 60000, 'UTF-8'),
            'id' => (int) $row['id'],
            'worker' => (string) ($row['locked_by'] ?? ''),
        ]);
        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('No fue posible actualizar el trabajo porque su bloqueo cambió.');
        }

        $envioId = (int) ($row['idEnvio'] ?? 0);
        if ($final && $envioId > 0) {
            $isSection = (string) ($row['job_type'] ?? 'main') === 'section';
            $envio = $isSection
                ? $pdo->prepare("UPDATE bitacora_envios SET estado = CASE WHEN estado = 'completado' THEN 'parcial' ELSE estado END, error_mensaje = CONCAT_WS('\n', NULLIF(error_mensaje, ''), :error) WHERE id = :id")
                : $pdo->prepare("UPDATE bitacora_envios SET estado = CASE WHEN pdf_generado = 1 THEN 'parcial' ELSE 'fallido' END, error_mensaje = CONCAT_WS('\n', NULLIF(error_mensaje, ''), :error) WHERE id = :id");
            $envio->execute([
                'error' => mb_substr($error, 0, 60000, 'UTF-8'),
                'id' => $envioId,
            ]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function queue_release_stale_locks(PDO $pdo): void
{
    $pdo->beginTransaction();
    try {
        $pdo->exec("UPDATE bitacora_email_queue SET estado = 'pendiente', locked_by = NULL, locked_at = NULL WHERE estado = 'procesando' AND locked_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE) AND attempts < max_attempts");

        $stale = $pdo->query("SELECT id, idEnvio, job_type, last_error FROM bitacora_email_queue WHERE estado = 'procesando' AND locked_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE) AND attempts >= max_attempts FOR UPDATE")->fetchAll(PDO::FETCH_ASSOC);
        $failQueue = $pdo->prepare("UPDATE bitacora_email_queue SET estado = 'fallido', locked_by = NULL, locked_at = NULL, last_error = :error WHERE id = :id AND estado = 'procesando'");
        $failMain = $pdo->prepare("UPDATE bitacora_envios SET estado = CASE WHEN pdf_generado = 1 THEN 'parcial' ELSE 'fallido' END, error_mensaje = CONCAT_WS('\n', NULLIF(error_mensaje, ''), :error) WHERE id = :id");
        $failSection = $pdo->prepare("UPDATE bitacora_envios SET estado = CASE WHEN estado = 'completado' THEN 'parcial' ELSE estado END, error_mensaje = CONCAT_WS('\n', NULLIF(error_mensaje, ''), :error) WHERE id = :id");

        foreach ($stale as $row) {
            $error = trim((string) ($row['last_error'] ?? '')) ?: QUEUE_STALE_LOCK_ERROR;
            $envioId = (int) ($row['idEnvio'] ?? 0);
            if ($envioId > 0) {
                if ((string) ($row['job_type'] ?? 'main') === 'section') {
                    $failSection->execute(['error' => 'Correo de sección #' . (int) $row['id'] . ': ' . $error, 'id' => $envioId]);
                } else {
                    $failMain->execute(['error' => $error, 'id' => $envioId]);
                }
            }
            $failQueue->execute(['error' => $error, 'id' => (int) $row['id']]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function queue_send_email(PHPMailer $mail, array $row): void
{
    $mail->clearAllRecipients();
    $mail->clearAttachments();

    $recipients = json_decode((string) ($row['recipients_json'] ?? ''), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($recipients)) {
        throw new RuntimeException('Destinatarios invalidos.');
    }

    app_bitacora_add_recipient_list($mail, $recipients);
    $mail->Subject = (string) ($row['subject'] ?? 'Bitacora');
    $mail->Body = (string) ($row['body_html'] ?? '');

    $attachments = [];
    $rawAttachments = $row['attachments_json'] ?? null;
    if (is_string($rawAttachments) && trim($rawAttachments) !== '') {
        $decoded = json_decode($rawAttachments, true, 512, JSON_THROW_ON_ERROR);
        $attachments = is_array($decoded) ? $decoded : [];
    }

    foreach ($attachments as $attachment) {
        $path = queue_resolve_attachment((string) ($attachment['relative_path'] ?? ''));
        if ($path === null) {
            throw new RuntimeException('Adjunto no encontrado o invalido.');
        }
        $fileName = basename((string) ($attachment['file_name'] ?? basename($path)));
        $mail->addAttachment($path, $fileName !== '' ? $fileName : basename($path));
    }

    $mail->send();
}

function queue_main(array $argv): int
{
    $limit = max(1, (int) ($argv[1] ?? app_env_int('BITACORA_MAIL_WORKER_LIMIT', 20)));
    $workerId = substr((gethostname() ?: 'worker') . ':' . getmypid(), 0, 120);

    try {
        $pdo = Conexion::Conectar();
        queue_release_stale_locks($pdo);

        $mail = new PHPMailer(true);
        app_configure_mailer($mail);
        $mail->SMTPKeepAlive = true;

        $processed = 0;
        while ($processed < $limit) {
            $row = queue_fetch_next($pdo, $workerId);
            if ($row === null) {
                break;
            }

            try {
                queue_send_email($mail, $row);
                queue_mark_sent($pdo, $row);
                echo 'enviado #' . $row['id'] . PHP_EOL;
            } catch (Throwable $e) {
                queue_mark_failed($pdo, $row, $e->getMessage());
                echo 'fallo #' . $row['id'] . ': ' . $e->getMessage() . PHP_EOL;
                $mail->smtpClose();
            }

            $processed++;
        }

        $mail->smtpClose();
        echo 'Procesados: ' . $processed . PHP_EOL;
        return 0;
    } catch (Throwable $e) {
        fwrite(STDERR, 'Error procesando cola de correos: ' . $e->getMessage() . PHP_EOL);
        return 1;
    }
}

if (realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === realpath(__FILE__)) {
    exit(queue_main($argv));
}
