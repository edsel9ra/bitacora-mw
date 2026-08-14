<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../public/config/admin_destinatarios_helpers.php';

function recipient_admin_integration_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$pdo = Conexion::Conectar();
$suffix = bin2hex(random_bytes(5));
$firstEmail = 'admin_first_' . $suffix . '@example.test';
$secondEmail = 'admin_second_' . $suffix . '@example.test';
$sectionEmail = 'admin_section_' . $suffix . '@example.test';
$recipientIds = [];
$sectionIds = [];

try {
    $companyConfig = app_bitacora_config(8);
    recipient_admin_integration_assert(is_array($companyConfig), 'La empresa de integración no tiene configuración.');
    recipient_admin_integration_assert(($companyConfig['type'] ?? '') === 'operational', 'La empresa de integración debe ser operativa.');
    $scopeOptions = bit_admin_scope_options(8, $companyConfig);
    recipient_admin_integration_assert((int) ($scopeOptions['PANCE']['id'] ?? 0) === 2, 'La sede administrativa no resolvió el idSede correcto.');
    $supervisionConfig = app_bitacora_config(6);
    recipient_admin_integration_assert(is_array($supervisionConfig), 'La empresa de supervisión no tiene configuración.');
    $sectionRejected = false;
    try {
        bit_admin_save_section_recipient(6, [
            'email' => 'supervision_section_' . $suffix . '@example.test',
            'tipo' => 'to',
            'scope' => BIT_ADMIN_GLOBAL_SCOPE,
            'section_key' => 'supervision',
            'activo' => 1,
        ], $supervisionConfig);
    } catch (InvalidArgumentException $e) {
        $sectionRejected = true;
    }
    recipient_admin_integration_assert($sectionRejected, 'Las asignaciones por sección no deben habilitarse en supervisión.');

    bit_admin_save_recipient(8, [
        'email' => $firstEmail,
        'tipo' => 'to',
        'scope' => BIT_ADMIN_GLOBAL_SCOPE,
        'activo' => 1,
    ], $companyConfig);
    bit_admin_save_recipient(8, [
        'email' => $secondEmail,
        'tipo' => 'to',
        'scope' => BIT_ADMIN_GLOBAL_SCOPE,
        'activo' => 1,
    ], $companyConfig);

    $stmt = $pdo->prepare('SELECT id, orden FROM bitacora_destinatarios WHERE idEmpresa = 8 AND email IN (:firstEmail, :secondEmail) ORDER BY orden, id');
    $stmt->execute(['firstEmail' => $firstEmail, 'secondEmail' => $secondEmail]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    recipient_admin_integration_assert(count($rows) === 2, 'No se guardaron los destinatarios de prueba.');
    $recipientIds = array_map(static fn(array $row): int => (int) $row['id'], $rows);
    $adminRows = bit_admin_recipient_rows(8, null);
    recipient_admin_integration_assert(
        count(array_filter($adminRows, static fn(array $row): bool => in_array((string) $row['email'], [$firstEmail, $secondEmail], true))) === 2,
        'La vista administrativa no recuperó los destinatarios guardados.'
    );

    $firstId = $recipientIds[0];
    $secondId = $recipientIds[1];
    bit_admin_move_recipient(8, $secondId, 'up');
    $orderStmt = $pdo->prepare('SELECT id, orden FROM bitacora_destinatarios WHERE idEmpresa = 8 AND id IN (:firstId, :secondId) ORDER BY orden, id');
    $orderStmt->execute(['firstId' => $firstId, 'secondId' => $secondId]);
    $orderedIds = array_map('intval', $orderStmt->fetchAll(PDO::FETCH_COLUMN, 0));
    recipient_admin_integration_assert($orderedIds === [$secondId, $firstId], 'El reordenamiento no conservó la nueva posición.');

    bit_admin_toggle_recipient(8, $firstId, 0);
    $activeStmt = $pdo->prepare('SELECT activo FROM bitacora_destinatarios WHERE idEmpresa = 8 AND id = :id');
    $activeStmt->execute(['id' => $firstId]);
    recipient_admin_integration_assert((int) $activeStmt->fetchColumn() === 0, 'No se pudo desactivar el destinatario.');

    $sectionKey = array_key_first(bit_admin_section_options(8, $companyConfig));
    recipient_admin_integration_assert(is_string($sectionKey) && $sectionKey !== '', 'No hay secciones disponibles para la prueba.');
    bit_admin_save_section_recipient(8, [
        'email' => $sectionEmail,
        'tipo' => 'to',
        'scope' => BIT_ADMIN_GLOBAL_SCOPE,
        'section_key' => $sectionKey,
        'activo' => 1,
    ], $companyConfig);

    $sectionStmt = $pdo->prepare('SELECT id FROM bitacora_seccion_destinatarios WHERE idEmpresa = 8 AND email = :email');
    $sectionStmt->execute(['email' => $sectionEmail]);
    $sectionId = $sectionStmt->fetchColumn();
    recipient_admin_integration_assert($sectionId !== false, 'No se guardó la asignación por sección.');
    $sectionIds[] = (int) $sectionId;
    $adminSectionRows = bit_admin_section_recipient_rows(8, null, bit_admin_section_order(bit_admin_section_options(8, $companyConfig)));
    recipient_admin_integration_assert(
        count(array_filter($adminSectionRows, static fn(array $row): bool => (string) $row['email'] === $sectionEmail)) === 1,
        'La vista administrativa no recuperó la asignación por sección.'
    );

    $effectiveRecipients = app_bitacora_recipients_for_sede(8, 'PANCE');
    foreach (['to', 'cc', 'bcc'] as $type) {
        recipient_admin_integration_assert(
            !in_array($sectionEmail, (array) ($effectiveRecipients[$type] ?? []), true),
            'El destinatario por sección no debe recibir el correo completo.'
        );
    }

    echo "Recipient admin integration tests OK\n";
} finally {
    $recipientEmails = [$firstEmail, $secondEmail];
    $recipientPlaceholders = implode(',', array_fill(0, count($recipientEmails), '?'));
    $pdo->prepare('DELETE FROM bitacora_destinatarios WHERE email IN (' . $recipientPlaceholders . ')')->execute($recipientEmails);
    $pdo->prepare('DELETE FROM bitacora_seccion_destinatarios WHERE email = ?')->execute([$sectionEmail]);
    $auditTargets = array_merge(
        [$firstEmail, $secondEmail, $sectionEmail],
        array_map('strval', $recipientIds),
        array_map('strval', $sectionIds)
    );
    $auditPlaceholders = implode(',', array_fill(0, count($auditTargets), '?'));
    $pdo->prepare('DELETE FROM bitacora_admin_audit WHERE objetivo IN (' . $auditPlaceholders . ')')->execute($auditTargets);
}
