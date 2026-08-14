<?php
declare(strict_types=1);

require_once __DIR__ . '/bitacora.php';
require_once __DIR__ . '/admin_formulario_helpers.php';
require_once __DIR__ . '/../bd/conexion.php';

const BIT_ADMIN_GLOBAL_SCOPE = '__global__';

function bit_admin_recipient_type_options(): array
{
    return [
        'to' => 'Para',
        'cc' => 'CC',
        'bcc' => 'CCO',
    ];
}

function bit_admin_scope_options(int $empresaId, array $companyConfig): array
{
    $options = [
        BIT_ADMIN_GLOBAL_SCOPE => [
            'label' => 'Global (todas las sedes)',
            'id' => null,
        ],
    ];

    foreach ((array) ($companyConfig['sedes'] ?? []) as $sede) {
        $sede = trim((string) $sede);
        if ($sede === '' || isset($options[$sede])) {
            continue;
        }

        $options[$sede] = [
            'label' => $sede,
            'id' => null,
        ];
    }

    try {
        $pdo = Conexion::Conectar();
        $stmt = $pdo->prepare('SELECT idSede, valor_form FROM empresa_sedes WHERE idEmpresa = :idEmpresa AND activo = 1');
        $stmt->execute(['idEmpresa' => $empresaId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $value = trim((string) ($row['valor_form'] ?? ''));
            if ($value !== '' && isset($options[$value])) {
                $options[$value]['id'] = (int) $row['idSede'];
            }
        }
    } catch (Throwable $e) {
        error_log('No fue posible resolver sedes para administración de correos: ' . $e->getMessage());
    }

    return $options;
}

function bit_admin_scope_sede_id(int $empresaId, array $companyConfig, string $scope): ?int
{
    if ($scope === BIT_ADMIN_GLOBAL_SCOPE) {
        return null;
    }

    $scope = trim($scope);
    $allowedSedes = array_map('strval', (array) ($companyConfig['sedes'] ?? []));
    if ($scope === '' || !in_array($scope, $allowedSedes, true)) {
        throw new InvalidArgumentException('La sede seleccionada no pertenece a la empresa.');
    }

    $pdo = Conexion::Conectar();
    $stmt = $pdo->prepare('SELECT idSede FROM empresa_sedes WHERE idEmpresa = :idEmpresa AND valor_form = :sede AND activo = 1 ORDER BY id LIMIT 1');
    $stmt->execute([
        'idEmpresa' => $empresaId,
        'sede' => $scope,
    ]);
    $id = $stmt->fetchColumn();
    if ($id === false) {
        throw new InvalidArgumentException('La sede seleccionada no está disponible.');
    }

    return (int) $id;
}

function bit_admin_scope_from_sede_id(?int $idSede, array $scopeOptions): string
{
    if ($idSede === null) {
        return BIT_ADMIN_GLOBAL_SCOPE;
    }

    foreach ($scopeOptions as $scope => $option) {
        if ((int) ($option['id'] ?? 0) === $idSede) {
            return (string) $scope;
        }
    }

    return BIT_ADMIN_GLOBAL_SCOPE;
}

function bit_admin_section_options(int $empresaId, array $companyConfig): array
{
    $options = [];
    foreach (app_bitacora_form_sections($empresaId, $companyConfig) as $section) {
        $key = trim((string) ($section['key'] ?? ''));
        $title = trim((string) ($section['title'] ?? $key));
        if ($key !== '' && $title !== '') {
            $options[$key] = $title;
        }
    }

    return $options;
}

function bit_admin_section_order(array $sectionOptions): array
{
    $order = [];
    foreach (array_keys($sectionOptions) as $index => $key) {
        $order[$key] = $index;
    }

    return $order;
}

function bit_admin_recipient_rows(int $empresaId, ?int $idSede): array
{
    $pdo = Conexion::Conectar();
    $where = 'bd.idEmpresa = :idEmpresa';
    $params = ['idEmpresa' => $empresaId];
    if ($idSede === null) {
        $where .= ' AND bd.idSede IS NULL';
    } else {
        $where .= ' AND bd.idSede = :idSede';
        $params['idSede'] = $idSede;
    }

    $stmt = $pdo->prepare(
        "SELECT bd.id, bd.idSede, bd.tipo, bd.email, bd.orden, bd.activo, es.valor_form
         FROM bitacora_destinatarios bd
         LEFT JOIN empresa_sedes es ON es.idEmpresa = bd.idEmpresa AND es.idSede = bd.idSede
         WHERE " . $where . "
         ORDER BY CASE bd.tipo WHEN 'to' THEN 1 WHEN 'cc' THEN 2 WHEN 'bcc' THEN 3 ELSE 4 END, bd.orden, bd.id"
    );
    $stmt->execute($params);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $positions = [];
    foreach ($rows as &$row) {
        $type = (string) ($row['tipo'] ?? 'to');
        $positions[$type] = ($positions[$type] ?? 0) + 1;
        $row['position'] = $positions[$type];
    }
    unset($row);

    return $rows;
}

function bit_admin_section_recipient_rows(int $empresaId, ?int $idSede, array $sectionOrder): array
{
    $pdo = Conexion::Conectar();
    $where = 'bsd.idEmpresa = :idEmpresa';
    $params = ['idEmpresa' => $empresaId];
    if ($idSede === null) {
        $where .= ' AND bsd.idSede IS NULL';
    } else {
        $where .= ' AND bsd.idSede = :idSede';
        $params['idSede'] = $idSede;
    }

    $stmt = $pdo->prepare('
        SELECT bsd.id, bsd.idSede, bsd.section_key, bsd.tipo, bsd.email, bsd.activo, es.valor_form
        FROM bitacora_seccion_destinatarios bsd
        LEFT JOIN empresa_sedes es ON es.idEmpresa = bsd.idEmpresa AND es.idSede = bsd.idSede
        WHERE ' . $where . '
        ORDER BY bsd.email, bsd.section_key, bsd.tipo, bsd.id
    ');
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    usort($rows, static function (array $left, array $right) use ($sectionOrder): int {
        $leftOrder = $sectionOrder[(string) ($left['section_key'] ?? '')] ?? PHP_INT_MAX;
        $rightOrder = $sectionOrder[(string) ($right['section_key'] ?? '')] ?? PHP_INT_MAX;
        return ($leftOrder <=> $rightOrder)
            ?: strcasecmp((string) ($left['email'] ?? ''), (string) ($right['email'] ?? ''))
            ?: ((int) ($left['id'] ?? 0) <=> (int) ($right['id'] ?? 0));
    });

    return $rows;
}

function bit_admin_recipient_by_id(int $empresaId, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }

    $pdo = Conexion::Conectar();
    $stmt = $pdo->prepare('SELECT id, idSede, tipo, email, orden, activo FROM bitacora_destinatarios WHERE idEmpresa = :idEmpresa AND id = :id LIMIT 1');
    $stmt->execute(['idEmpresa' => $empresaId, 'id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row === false ? null : $row;
}

function bit_admin_section_recipient_by_id(int $empresaId, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }

    $pdo = Conexion::Conectar();
    $stmt = $pdo->prepare('SELECT id, idSede, section_key, tipo, email, activo FROM bitacora_seccion_destinatarios WHERE idEmpresa = :idEmpresa AND id = :id LIMIT 1');
    $stmt->execute(['idEmpresa' => $empresaId, 'id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row === false ? null : $row;
}

function bit_admin_validate_email(string $email): string
{
    $email = strtolower(trim($email));
    if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        throw new InvalidArgumentException('Ingresa un correo electrónico válido.');
    }

    return $email;
}

function bit_admin_validate_type(string $type): string
{
    if (!array_key_exists($type, bit_admin_recipient_type_options())) {
        throw new InvalidArgumentException('El tipo de destinatario no es válido.');
    }

    return $type;
}

function bit_admin_scope_condition(?int $idSede, array &$params, string $prefix = ''): string
{
    if ($idSede === null) {
        return 'idSede IS NULL';
    }

    $name = $prefix . 'idSede';
    $params[$name] = $idSede;
    return 'idSede = :' . $name;
}

function bit_admin_recipient_order_max(PDO $pdo, int $empresaId, ?int $idSede, string $type, ?int $excludeId = null): int
{
    $params = [
        'idEmpresa' => $empresaId,
        'tipo' => $type,
    ];
    $where = 'idEmpresa = :idEmpresa AND tipo = :tipo AND ' . bit_admin_scope_condition($idSede, $params);
    if ($excludeId !== null) {
        $where .= ' AND id <> :excludeId';
        $params['excludeId'] = $excludeId;
    }

    $stmt = $pdo->prepare('SELECT COALESCE(MAX(orden), 0) FROM bitacora_destinatarios WHERE ' . $where);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function bit_admin_assert_recipient_unique(PDO $pdo, int $empresaId, ?int $idSede, string $type, string $email, ?int $excludeId = null): void
{
    $params = [
        'idEmpresa' => $empresaId,
        'tipo' => $type,
        'email' => $email,
    ];
    $where = 'idEmpresa = :idEmpresa AND tipo = :tipo AND LOWER(email) = LOWER(:email) AND ' . bit_admin_scope_condition($idSede, $params);
    if ($excludeId !== null) {
        $where .= ' AND id <> :excludeId';
        $params['excludeId'] = $excludeId;
    }

    $stmt = $pdo->prepare('SELECT id FROM bitacora_destinatarios WHERE ' . $where . ' LIMIT 1');
    $stmt->execute($params);
    if ($stmt->fetchColumn() !== false) {
        throw new InvalidArgumentException('Ya existe ese correo con el mismo alcance y tipo.');
    }
}

function bit_admin_assert_section_unique(PDO $pdo, int $empresaId, ?int $idSede, string $sectionKey, string $type, string $email, ?int $excludeId = null): void
{
    $params = [
        'idEmpresa' => $empresaId,
        'sectionKey' => $sectionKey,
        'tipo' => $type,
        'email' => $email,
    ];
    $where = 'idEmpresa = :idEmpresa AND section_key = :sectionKey AND tipo = :tipo AND LOWER(email) = LOWER(:email) AND ' . bit_admin_scope_condition($idSede, $params);
    if ($excludeId !== null) {
        $where .= ' AND id <> :excludeId';
        $params['excludeId'] = $excludeId;
    }

    $stmt = $pdo->prepare('SELECT id FROM bitacora_seccion_destinatarios WHERE ' . $where . ' LIMIT 1');
    $stmt->execute($params);
    if ($stmt->fetchColumn() !== false) {
        throw new InvalidArgumentException('Ya existe ese correo para la sección, alcance y tipo seleccionados.');
    }
}

function bit_admin_save_recipient(int $empresaId, array $input, array $companyConfig): void
{
    $id = max(0, (int) ($input['id'] ?? 0));
    $wasExisting = $id > 0;
    $email = bit_admin_validate_email((string) ($input['email'] ?? ''));
    $type = bit_admin_validate_type(trim((string) ($input['tipo'] ?? 'to')));
    $scope = trim((string) ($input['scope'] ?? BIT_ADMIN_GLOBAL_SCOPE));
    $idSede = bit_admin_scope_sede_id($empresaId, $companyConfig, $scope);
    $active = !empty($input['activo']) ? 1 : 0;

    $pdo = Conexion::Conectar();
    $pdo->beginTransaction();
    try {
        bit_admin_assert_recipient_unique($pdo, $empresaId, $idSede, $type, $email, $id > 0 ? $id : null);
        $existing = null;
        if ($id > 0) {
            $stmt = $pdo->prepare('SELECT idSede, tipo, orden FROM bitacora_destinatarios WHERE idEmpresa = :idEmpresa AND id = :id FOR UPDATE');
            $stmt->execute(['idEmpresa' => $empresaId, 'id' => $id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($existing === false) {
                throw new InvalidArgumentException('El destinatario no existe para esta empresa.');
            }
        }

        $order = $existing !== null
            && (int) ($existing['idSede'] ?? 0) === (int) ($idSede ?? 0)
            && (string) ($existing['tipo'] ?? '') === $type
            ? (int) ($existing['orden'] ?? 0)
            : bit_admin_recipient_order_max($pdo, $empresaId, $idSede, $type, $id > 0 ? $id : null) + 10;

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE bitacora_destinatarios SET idSede = :idSede, tipo = :tipo, email = :email, orden = :orden, activo = :activo WHERE idEmpresa = :idEmpresa AND id = :id');
            $stmt->execute([
                'idSede' => $idSede,
                'tipo' => $type,
                'email' => $email,
                'orden' => $order,
                'activo' => $active,
                'idEmpresa' => $empresaId,
                'id' => $id,
            ]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO bitacora_destinatarios (idEmpresa, idSede, tipo, email, orden, activo) VALUES (:idEmpresa, :idSede, :tipo, :email, :orden, :activo)');
            $stmt->execute([
                'idEmpresa' => $empresaId,
                'idSede' => $idSede,
                'tipo' => $type,
                'email' => $email,
                'orden' => $order,
                'activo' => $active,
            ]);
            $id = (int) $pdo->lastInsertId();
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    bit_admin_audit_log($empresaId, $wasExisting ? 'update_recipient' : 'create_recipient', (string) $email, [
        'id' => $id,
        'scope' => $scope,
        'tipo' => $type,
        'activo' => $active,
    ]);
}

function bit_admin_save_section_recipient(int $empresaId, array $input, array $companyConfig): void
{
    if (($companyConfig['type'] ?? '') !== 'operational') {
        throw new InvalidArgumentException('Las asignaciones por sección solo aplican a empresas operativas.');
    }

    $id = max(0, (int) ($input['id'] ?? 0));
    $wasExisting = $id > 0;
    $email = bit_admin_validate_email((string) ($input['email'] ?? ''));
    $type = bit_admin_validate_type(trim((string) ($input['tipo'] ?? 'to')));
    $scope = trim((string) ($input['scope'] ?? BIT_ADMIN_GLOBAL_SCOPE));
    $sectionKey = trim((string) ($input['section_key'] ?? ''));
    $sectionOptions = bit_admin_section_options($empresaId, $companyConfig);
    if ($sectionKey === '' || !isset($sectionOptions[$sectionKey])) {
        throw new InvalidArgumentException('La sección seleccionada no existe en el formulario actual.');
    }
    $idSede = bit_admin_scope_sede_id($empresaId, $companyConfig, $scope);
    $active = !empty($input['activo']) ? 1 : 0;

    $pdo = Conexion::Conectar();
    $pdo->beginTransaction();
    try {
        bit_admin_assert_section_unique($pdo, $empresaId, $idSede, $sectionKey, $type, $email, $id > 0 ? $id : null);
        if ($id > 0) {
            $exists = $pdo->prepare('SELECT id FROM bitacora_seccion_destinatarios WHERE idEmpresa = :idEmpresa AND id = :id FOR UPDATE');
            $exists->execute(['idEmpresa' => $empresaId, 'id' => $id]);
            if ($exists->fetchColumn() === false) {
                throw new InvalidArgumentException('La asignación por sección no existe para esta empresa.');
            }

            $stmt = $pdo->prepare('UPDATE bitacora_seccion_destinatarios SET idSede = :idSede, section_key = :sectionKey, tipo = :tipo, email = :email, activo = :activo WHERE idEmpresa = :idEmpresa AND id = :id');
            $stmt->execute([
                'idSede' => $idSede,
                'sectionKey' => $sectionKey,
                'tipo' => $type,
                'email' => $email,
                'activo' => $active,
                'idEmpresa' => $empresaId,
                'id' => $id,
            ]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO bitacora_seccion_destinatarios (idEmpresa, idSede, section_key, tipo, email, activo) VALUES (:idEmpresa, :idSede, :sectionKey, :tipo, :email, :activo)');
            $stmt->execute([
                'idEmpresa' => $empresaId,
                'idSede' => $idSede,
                'sectionKey' => $sectionKey,
                'tipo' => $type,
                'email' => $email,
                'activo' => $active,
            ]);
            $id = (int) $pdo->lastInsertId();
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    bit_admin_audit_log($empresaId, $wasExisting ? 'update_section_recipient' : 'create_section_recipient', $email, [
        'id' => $id,
        'scope' => $scope,
        'section_key' => $sectionKey,
        'tipo' => $type,
        'activo' => $active,
    ]);
}

function bit_admin_toggle_recipient(int $empresaId, int $id, int $active, bool $section = false): void
{
    if ($id <= 0) {
        throw new InvalidArgumentException('El destinatario no es válido.');
    }

    $table = $section ? 'bitacora_seccion_destinatarios' : 'bitacora_destinatarios';
    $stmt = Conexion::Conectar()->prepare('UPDATE ' . $table . ' SET activo = :activo WHERE idEmpresa = :idEmpresa AND id = :id');
    $stmt->execute([
        'activo' => $active === 1 ? 1 : 0,
        'idEmpresa' => $empresaId,
        'id' => $id,
    ]);
    if ($stmt->rowCount() !== 1) {
        throw new InvalidArgumentException('El destinatario no existe para esta empresa.');
    }

    bit_admin_audit_log($empresaId, $section ? 'toggle_section_recipient' : 'toggle_recipient', (string) $id, [
        'id' => $id,
        'activo' => $active === 1 ? 1 : 0,
    ]);
}

function bit_admin_move_recipient(int $empresaId, int $id, string $direction): void
{
    if (!in_array($direction, ['up', 'down'], true)) {
        throw new InvalidArgumentException('Dirección de orden inválida.');
    }

    $pdo = Conexion::Conectar();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT id, idSede, tipo FROM bitacora_destinatarios WHERE idEmpresa = :idEmpresa AND id = :id FOR UPDATE');
        $stmt->execute(['idEmpresa' => $empresaId, 'id' => $id]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($current === false) {
            throw new InvalidArgumentException('El destinatario no existe para esta empresa.');
        }

        $idSede = $current['idSede'] === null ? null : (int) $current['idSede'];
        $params = ['idEmpresa' => $empresaId, 'tipo' => (string) $current['tipo']];
        $where = 'idEmpresa = :idEmpresa AND tipo = :tipo AND ' . bit_admin_scope_condition($idSede, $params);
        $list = $pdo->prepare('SELECT id FROM bitacora_destinatarios WHERE ' . $where . ' ORDER BY orden, id FOR UPDATE');
        $list->execute($params);
        $ids = array_map('intval', $list->fetchAll(PDO::FETCH_COLUMN));
        $index = array_search($id, $ids, true);
        if ($index === false) {
            throw new RuntimeException('No fue posible ubicar el destinatario en su orden.');
        }

        $target = $direction === 'up' ? $index - 1 : $index + 1;
        if ($target >= 0 && $target < count($ids)) {
            [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];
            $update = $pdo->prepare('UPDATE bitacora_destinatarios SET orden = :orden WHERE idEmpresa = :idEmpresa AND id = :id');
            foreach ($ids as $position => $rowId) {
                $update->execute([
                    'orden' => ($position + 1) * 10,
                    'idEmpresa' => $empresaId,
                    'id' => $rowId,
                ]);
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    bit_admin_audit_log($empresaId, 'move_recipient', (string) $id, ['id' => $id, 'direction' => $direction]);
}

function bit_admin_destinatarios_redirect(int $empresaId, string $scope, string $tab, string $type, string $message): void
{
    $_SESSION['admin_destinatarios_flash'] = ['type' => $type, 'message' => $message];
    $query = http_build_query([
        'empresa' => $empresaId,
        'scope' => $scope,
        'tab' => $tab,
    ]);
    header('Location: admin_destinatarios.php?' . $query);
    exit;
}
