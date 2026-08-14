-- Persists recipient order and identifies companies managed by the admin module.

CREATE TABLE IF NOT EXISTS bitacora_destinatarios_config (
  idEmpresa INT PRIMARY KEY,
  modo ENUM('php','database') NOT NULL DEFAULT 'php',
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT bitacora_dest_config_empresa_fk FOREIGN KEY (idEmpresa) REFERENCES razones_sociales(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @recipient_order_ddl := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bitacora_destinatarios' AND COLUMN_NAME = 'orden') = 0,
  'ALTER TABLE bitacora_destinatarios ADD COLUMN orden INT NOT NULL DEFAULT 100000 AFTER email',
  'SELECT 1'
);
PREPARE recipient_order_stmt FROM @recipient_order_ddl;
EXECUTE recipient_order_stmt;
DEALLOCATE PREPARE recipient_order_stmt;

SET @recipient_order_idx_exists := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'bitacora_destinatarios'
    AND INDEX_NAME = 'bitacora_destinatario_order_idx'
);
SET @recipient_order_idx_ddl := IF(
  @recipient_order_idx_exists = 0,
  'ALTER TABLE bitacora_destinatarios ADD KEY bitacora_destinatario_order_idx (idEmpresa, idSede, tipo, orden, id)',
  'SELECT 1'
);
PREPARE recipient_order_idx_stmt FROM @recipient_order_idx_ddl;
EXECUTE recipient_order_idx_stmt;
DEALLOCATE PREPARE recipient_order_idx_stmt;

INSERT INTO bitacora_destinatarios_config (idEmpresa, modo)
SELECT id, 'php'
FROM razones_sociales
ON DUPLICATE KEY UPDATE idEmpresa = VALUES(idEmpresa);
